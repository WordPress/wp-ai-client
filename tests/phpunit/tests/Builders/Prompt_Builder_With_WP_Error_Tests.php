<?php
/**
 * Tests for WordPress\AI_Client\Builders\Prompt_Builder_With_WP_Error
 *
 * @package WordPress\AI_Client
 */

namespace WordPress\AI_Client\PHPUnit\Tests\Builders;

use BadMethodCallException;
use ReflectionClass;
use WordPress\AI_Client\Builders\Prompt_Builder;
use WordPress\AI_Client\Builders\Prompt_Builder_With_WP_Error;
use WordPress\AI_Client\PHPUnit\Includes\Test_Case;
use WordPress\AiClient\AiClient;
use WordPress\AiClient\Builders\PromptBuilder;
use WordPress\AiClient\Messages\DTO\Message;
use WordPress\AiClient\Providers\Models\DTO\ModelConfig;
use WP_Error;

class Prompt_Builder_With_WP_Error_Tests extends Test_Case {

	/**
	 * Test that Prompt_Builder_With_WP_Error can be instantiated.
	 */
	public function test_instantiation(): void {
		$registry       = AiClient::defaultRegistry();
		$prompt_builder = new Prompt_Builder_With_WP_Error( $registry );

		$this->assertInstanceOf( Prompt_Builder_With_WP_Error::class, $prompt_builder );
		$this->assertInstanceOf( Prompt_Builder::class, $prompt_builder );
	}

	/**
	 * Test that Prompt_Builder_With_WP_Error can be instantiated with initial prompt content.
	 */
	public function test_instantiation_with_prompt(): void {
		$registry       = AiClient::defaultRegistry();
		$prompt_builder = new Prompt_Builder_With_WP_Error( $registry, 'Initial prompt text' );

		$this->assertInstanceOf( Prompt_Builder_With_WP_Error::class, $prompt_builder );
	}

	/**
	 * Test method chaining with fluent methods.
	 *
	 * This tests the bug fix where methods that return the PromptBuilder instance
	 * should instead return the Prompt_Builder_With_WP_Error decorator to allow proper chaining.
	 */
	public function test_method_chaining_returns_decorator(): void {
		$registry       = AiClient::defaultRegistry();
		$prompt_builder = new Prompt_Builder_With_WP_Error( $registry );

		// Test chaining with_text which should return the decorator.
		$result = $prompt_builder->with_text( 'Test text' );
		$this->assertSame( $prompt_builder, $result, 'with_text should return the Prompt_Builder_With_WP_Error decorator instance' );
		$this->assertInstanceOf( Prompt_Builder_With_WP_Error::class, $result );

		// Test chaining using_system_instruction.
		$result = $prompt_builder->using_system_instruction( 'System instruction' );
		$this->assertSame( $prompt_builder, $result, 'using_system_instruction should return the Prompt_Builder_With_WP_Error decorator instance' );
		$this->assertInstanceOf( Prompt_Builder_With_WP_Error::class, $result );

		// Test chaining using_max_tokens.
		$result = $prompt_builder->using_max_tokens( 100 );
		$this->assertSame( $prompt_builder, $result, 'using_max_tokens should return the Prompt_Builder_With_WP_Error decorator instance' );
		$this->assertInstanceOf( Prompt_Builder_With_WP_Error::class, $result );

		// Test chaining using_temperature.
		$result = $prompt_builder->using_temperature( 0.7 );
		$this->assertSame( $prompt_builder, $result, 'using_temperature should return the Prompt_Builder_With_WP_Error decorator instance' );
		$this->assertInstanceOf( Prompt_Builder_With_WP_Error::class, $result );

		// Test chaining using_top_p.
		$result = $prompt_builder->using_top_p( 0.9 );
		$this->assertSame( $prompt_builder, $result, 'using_top_p should return the Prompt_Builder_With_WP_Error decorator instance' );
		$this->assertInstanceOf( Prompt_Builder_With_WP_Error::class, $result );

		// Test chaining using_top_k.
		$result = $prompt_builder->using_top_k( 50 );
		$this->assertSame( $prompt_builder, $result, 'using_top_k should return the Prompt_Builder_With_WP_Error decorator instance' );
		$this->assertInstanceOf( Prompt_Builder_With_WP_Error::class, $result );

		// Test chaining using_presence_penalty.
		$result = $prompt_builder->using_presence_penalty( 0.5 );
		$this->assertSame( $prompt_builder, $result, 'using_presence_penalty should return the Prompt_Builder_With_WP_Error decorator instance' );
		$this->assertInstanceOf( Prompt_Builder_With_WP_Error::class, $result );

		// Test chaining using_frequency_penalty.
		$result = $prompt_builder->using_frequency_penalty( 0.5 );
		$this->assertSame( $prompt_builder, $result, 'using_frequency_penalty should return the Prompt_Builder_With_WP_Error decorator instance' );
		$this->assertInstanceOf( Prompt_Builder_With_WP_Error::class, $result );

		// Test chaining as_output_mime_type.
		$result = $prompt_builder->as_output_mime_type( 'application/json' );
		$this->assertSame( $prompt_builder, $result, 'as_output_mime_type should return the Prompt_Builder_With_WP_Error decorator instance' );
		$this->assertInstanceOf( Prompt_Builder_With_WP_Error::class, $result );
	}

	/**
	 * Test complex method chaining scenario.
	 *
	 * This tests that multiple methods can be chained together fluently.
	 */
	public function test_complex_method_chaining(): void {
		$registry       = AiClient::defaultRegistry();
		$prompt_builder = new Prompt_Builder_With_WP_Error( $registry );

		// Chain multiple methods together.
		$result = $prompt_builder
			->with_text( 'Test prompt' )
			->using_system_instruction( 'You are a helpful assistant' )
			->using_max_tokens( 500 )
			->using_temperature( 0.7 )
			->using_top_p( 0.9 );

		// The final result should still be the same Prompt_Builder_With_WP_Error instance.
		$this->assertSame( $prompt_builder, $result, 'Chained methods should return the same Prompt_Builder_With_WP_Error decorator instance' );
		$this->assertInstanceOf( Prompt_Builder_With_WP_Error::class, $result );
	}

	/**
	 * Test that boolean-returning methods do not return the decorator.
	 */
	public function test_boolean_methods_return_boolean(): void {
		$registry       = AiClient::defaultRegistry();
		$prompt_builder = new Prompt_Builder_With_WP_Error( $registry, 'Test text' );

		// Boolean methods should return boolean, not the decorator.
		$result = $prompt_builder->is_supported_for_text_generation();
		$this->assertIsBool( $result, 'is_supported_for_text_generation should return a boolean' );
		$this->assertNotSame( $prompt_builder, $result, 'is_supported_for_text_generation should not return the decorator' );
	}

	/**
	 * Test that calling a non-existent method returns WP_Error.
	 */
	public function test_invalid_method_returns_wp_error(): void {
		$registry       = AiClient::defaultRegistry();
		$prompt_builder = new Prompt_Builder_With_WP_Error( $registry );

		// Invalid method call should store error but return $this for chaining.
		$result = $prompt_builder->non_existent_method();
		$this->assertSame( $prompt_builder, $result );

		// Calling a terminate method should return the stored WP_Error.
		$result = $prompt_builder->generate_text();
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'prompt_builder_error', $result->get_error_code() );
		$this->assertStringContainsString( 'non_existent_method does not exist', $result->get_error_message() );
	}

	/**
	 * Test method chaining with with_history.
	 */
	public function test_method_chaining_with_history(): void {
		$registry       = AiClient::defaultRegistry();
		$prompt_builder = new Prompt_Builder_With_WP_Error( $registry );

		$message1 = Message::fromArray(
			array(
				'role'  => 'user',
				'parts' => array(
					array(
						'text' => 'Hello',
					),
				),
			)
		);
		$message2 = Message::fromArray(
			array(
				'role'  => 'user',
				'parts' => array(
					array(
						'text' => 'How are you?',
					),
				),
			)
		);

		$result = $prompt_builder->with_history( $message1, $message2 );
		$this->assertSame( $prompt_builder, $result, 'with_history should return the Prompt_Builder_With_WP_Error decorator instance' );
		$this->assertInstanceOf( Prompt_Builder_With_WP_Error::class, $result );
	}

	/**
	 * Test method chaining with using_model_config.
	 */
	public function test_method_chaining_with_model_config(): void {
		$registry       = AiClient::defaultRegistry();
		$prompt_builder = new Prompt_Builder_With_WP_Error( $registry );

		$config = new ModelConfig( array( 'maxTokens' => 100 ) );

		$result = $prompt_builder->using_model_config( $config );
		$this->assertSame( $prompt_builder, $result, 'using_model_config should return the Prompt_Builder_With_WP_Error decorator instance' );
		$this->assertInstanceOf( Prompt_Builder_With_WP_Error::class, $result );
	}

	/**
	 * Test that once in error state, subsequent fluent calls return the same instance.
	 *
	 * This test simulates an error state by directly setting the error property,
	 * since fluent methods typically don't throw exceptions.
	 */
	public function test_error_state_fluent_calls_return_same_instance(): void {
		$registry       = AiClient::defaultRegistry();
		$prompt_builder = new Prompt_Builder_With_WP_Error( $registry );

		// Simulate an error state by directly setting the error property.
		$reflection_class = new ReflectionClass( Prompt_Builder_With_WP_Error::class );
		$error_property   = $reflection_class->getProperty( 'error' );
		$error_property->setAccessible( true );
		$error_property->setValue( $prompt_builder, new WP_Error( 'test_error', 'Test error message' ) );

		// Subsequent fluent calls should return the same instance.
		$result = $prompt_builder->with_text( 'Test' );
		$this->assertSame( $prompt_builder, $result, 'Fluent method should return same instance when in error state' );

		$result = $prompt_builder->using_max_tokens( 100 );
		$this->assertSame( $prompt_builder, $result, 'Fluent method should return same instance when in error state' );
	}

	/**
	 * Test that terminating methods return WP_Error when in error state.
	 *
	 * This test simulates an error state by directly setting the error property.
	 */
	public function test_terminating_methods_return_wp_error_in_error_state(): void {
		$registry       = AiClient::defaultRegistry();
		$prompt_builder = new Prompt_Builder_With_WP_Error( $registry );

		// Simulate an error state by directly setting the error property.
		$test_error       = new WP_Error( 'test_error', 'Test error message' );
		$reflection_class = new ReflectionClass( Prompt_Builder_With_WP_Error::class );
		$error_property   = $reflection_class->getProperty( 'error' );
		$error_property->setAccessible( true );
		$error_property->setValue( $prompt_builder, $test_error );

		// Terminating methods should return the WP_Error.
		$result = $prompt_builder->generate_text();
		$this->assertInstanceOf( WP_Error::class, $result, 'generate_text should return WP_Error when in error state' );
		$this->assertSame( $test_error, $result, 'Should return the same WP_Error instance' );
	}

	/**
	 * Test that exception in terminating method is caught and returned as WP_Error.
	 */
	public function test_exception_in_terminating_method_caught_and_returned(): void {
		$registry       = AiClient::defaultRegistry();
		$prompt_builder = new Prompt_Builder_With_WP_Error( $registry );

		// Calling a terminating method without proper setup should cause an exception.
		// This will fail because no model/provider is configured.
		$error = $prompt_builder->generate_text();

		$this->assertInstanceOf( WP_Error::class, $error, 'generate_text should return WP_Error when exception occurs' );
		$this->assertSame( 'prompt_builder_error', $error->get_error_code() );

		// Check that error data contains exception class.
		$error_data = $error->get_error_data();
		$this->assertIsArray( $error_data );
		$this->assertArrayHasKey( 'exception_class', $error_data );
		$this->assertNotEmpty( $error_data['exception_class'] );
	}

	/**
	 * Test that exception in chained method is caught and returned by the terminating method as WP_Error.
	 */
	public function test_exception_in_chained_method_caught_and_returned_by_terminating_method(): void {
		$registry       = AiClient::defaultRegistry();
		$prompt_builder = new Prompt_Builder_With_WP_Error( $registry );

		$result = $prompt_builder
			->with_text( 'Start of prompt' )
			->with_file( 'https://example.com/img.jpg', 'image/jpeg' )
			// The line below is incorrect: Only provider and model ID must be given.
			->using_model_preference( array( 'test-provider', 'test-model', 'test-version' ) )
			->using_system_instruction( 'Be helpful' )
			->generate_text();

		$this->assertInstanceOf( WP_Error::class, $result, 'generate_text should return WP_Error when exception occurs' );
		$this->assertSame( 'prompt_builder_error', $result->get_error_code() );
		$this->assertSame( 'Model preference tuple must contain model identifier and provider ID.', $result->get_error_message() );

		// Check that error data contains exception class.
		$error_data = $result->get_error_data();
		$this->assertIsArray( $error_data );
		$this->assertArrayHasKey( 'exception_class', $error_data );
		$this->assertNotEmpty( $error_data['exception_class'] );
	}

	/**
	 * Test that the wrapped builder is properly configured with the registry.
	 */
	public function test_wrapped_builder_has_correct_registry(): void {
		$registry       = AiClient::defaultRegistry();
		$prompt_builder = new Prompt_Builder_With_WP_Error( $registry );

		$reflection_class = new ReflectionClass( Prompt_Builder::class );
		$builder_property = $reflection_class->getProperty( 'builder' );
		$builder_property->setAccessible( true );
		$wrapped_builder = $builder_property->getValue( $prompt_builder );

		$wrapped_builder_reflection = new ReflectionClass( get_class( $wrapped_builder ) );
		$registry_property          = $wrapped_builder_reflection->getProperty( 'registry' );
		$registry_property->setAccessible( true );

		$this->assertSame( $registry, $registry_property->getValue( $wrapped_builder ), 'Wrapped builder should have the same registry' );
	}

	/**
	 * Test that generate_result returns WP_Error when prevent prompt filter returns true.
	 */
	public function test_generate_result_returns_wp_error_when_filter_prevents_prompt(): void {
		add_filter( 'wp_ai_client_prevent_prompt', '__return_true' );

		$prompt_builder = new Prompt_Builder_With_WP_Error( AiClient::defaultRegistry(), 'Test prompt' );

		$result = $prompt_builder->generate_result();

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'prompt_prevented', $result->get_error_code() );
		$this->assertSame( 'Prompt execution was prevented by a filter.', $result->get_error_message() );
	}

	/**
	 * Test that is_supported returns false when prevent prompt filter returns true.
	 */
	public function test_is_supported_returns_false_when_filter_prevents_prompt(): void {
		add_filter( 'wp_ai_client_prevent_prompt', '__return_true' );

		$prompt_builder = new Prompt_Builder_With_WP_Error( AiClient::defaultRegistry(), 'Test prompt' );

		$this->assertFalse( $prompt_builder->is_supported() );
	}

	/**
	 * Test that prevent prompt filter receives a clone of the builder instance.
	 */
	public function test_prevent_prompt_filter_receives_cloned_wp_error_builder_instance(): void {
		$captured_builder = null;

		add_filter(
			'wp_ai_client_prevent_prompt',
			static function ( $prevent, $builder ) use ( &$captured_builder ) {
				$captured_builder = $builder;
				return $prevent;
			},
			10,
			2
		);

		$prompt_builder = new Prompt_Builder_With_WP_Error( AiClient::defaultRegistry(), 'Test prompt' );
		$prompt_builder->generate_result();

		$this->assertNotSame( $prompt_builder, $captured_builder, 'Filter should receive a clone, not the same instance' );
		$this->assertInstanceOf( Prompt_Builder_With_WP_Error::class, $captured_builder );
	}
}
