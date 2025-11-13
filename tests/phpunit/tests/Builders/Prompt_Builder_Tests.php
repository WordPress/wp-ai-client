<?php
/**
 * Tests for WordPress\AI_Client\Builders\Prompt_Builder
 *
 * @package WordPress\AI_Client
 */

namespace WordPress\AI_Client\PHPUnit\Tests\Builders;

use BadMethodCallException;
use ReflectionClass;
use WordPress\AI_Client\Builders\Prompt_Builder;
use WordPress\AI_Client\PHPUnit\Includes\Test_Case;
use WordPress\AiClient\AiClient;
use WordPress\AiClient\Builders\PromptBuilder;
use WordPress\AiClient\Messages\DTO\Message;
use WordPress\AiClient\Providers\Models\DTO\ModelConfig;

class Prompt_Builder_Tests extends Test_Case {

	/**
	 * Test that Prompt_Builder can be instantiated.
	 *
	 * @since n.e.x.t
	 */
	public function test_instantiation(): void {
		$registry       = AiClient::defaultRegistry();
		$prompt_builder = new Prompt_Builder( $registry );

		$this->assertInstanceOf( Prompt_Builder::class, $prompt_builder );

		// Verify the wrapped builder is a PromptBuilder instance.
		$reflection_class = new ReflectionClass( Prompt_Builder::class );
		$builder_property = $reflection_class->getProperty( 'builder' );
		$builder_property->setAccessible( true );
		$wrapped_builder = $builder_property->getValue( $prompt_builder );

		$this->assertInstanceOf( PromptBuilder::class, $wrapped_builder );
	}

	/**
	 * Test that Prompt_Builder can be instantiated with initial prompt content.
	 *
	 * @since n.e.x.t
	 */
	public function test_instantiation_with_prompt(): void {
		$registry       = AiClient::defaultRegistry();
		$prompt_builder = new Prompt_Builder( $registry, 'Initial prompt text' );

		$this->assertInstanceOf( Prompt_Builder::class, $prompt_builder );
	}

	/**
	 * Test method chaining with fluent methods.
	 *
	 * This tests the bug fix where methods that return the PromptBuilder instance
	 * should instead return the Prompt_Builder decorator to allow proper chaining.
	 *
	 * @since n.e.x.t
	 */
	public function test_method_chaining_returns_decorator(): void {
		$registry       = AiClient::defaultRegistry();
		$prompt_builder = new Prompt_Builder( $registry );

		// Test chaining with_text which should return the decorator.
		$result = $prompt_builder->with_text( 'Test text' );
		$this->assertSame( $prompt_builder, $result, 'with_text should return the Prompt_Builder decorator instance' );
		$this->assertInstanceOf( Prompt_Builder::class, $result );

		// Test chaining using_system_instruction.
		$result = $prompt_builder->using_system_instruction( 'System instruction' );
		$this->assertSame( $prompt_builder, $result, 'using_system_instruction should return the Prompt_Builder decorator instance' );
		$this->assertInstanceOf( Prompt_Builder::class, $result );

		// Test chaining using_max_tokens.
		$result = $prompt_builder->using_max_tokens( 100 );
		$this->assertSame( $prompt_builder, $result, 'using_max_tokens should return the Prompt_Builder decorator instance' );
		$this->assertInstanceOf( Prompt_Builder::class, $result );

		// Test chaining using_temperature.
		$result = $prompt_builder->using_temperature( 0.7 );
		$this->assertSame( $prompt_builder, $result, 'using_temperature should return the Prompt_Builder decorator instance' );
		$this->assertInstanceOf( Prompt_Builder::class, $result );

		// Test chaining using_top_p.
		$result = $prompt_builder->using_top_p( 0.9 );
		$this->assertSame( $prompt_builder, $result, 'using_top_p should return the Prompt_Builder decorator instance' );
		$this->assertInstanceOf( Prompt_Builder::class, $result );

		// Test chaining using_top_k.
		$result = $prompt_builder->using_top_k( 50 );
		$this->assertSame( $prompt_builder, $result, 'using_top_k should return the Prompt_Builder decorator instance' );
		$this->assertInstanceOf( Prompt_Builder::class, $result );

		// Test chaining using_presence_penalty.
		$result = $prompt_builder->using_presence_penalty( 0.5 );
		$this->assertSame( $prompt_builder, $result, 'using_presence_penalty should return the Prompt_Builder decorator instance' );
		$this->assertInstanceOf( Prompt_Builder::class, $result );

		// Test chaining using_frequency_penalty.
		$result = $prompt_builder->using_frequency_penalty( 0.5 );
		$this->assertSame( $prompt_builder, $result, 'using_frequency_penalty should return the Prompt_Builder decorator instance' );
		$this->assertInstanceOf( Prompt_Builder::class, $result );

		// Test chaining as_output_mime_type.
		$result = $prompt_builder->as_output_mime_type( 'application/json' );
		$this->assertSame( $prompt_builder, $result, 'as_output_mime_type should return the Prompt_Builder decorator instance' );
		$this->assertInstanceOf( Prompt_Builder::class, $result );
	}

	/**
	 * Test complex method chaining scenario.
	 *
	 * This tests that multiple methods can be chained together fluently.
	 *
	 * @since n.e.x.t
	 */
	public function test_complex_method_chaining(): void {
		$registry       = AiClient::defaultRegistry();
		$prompt_builder = new Prompt_Builder( $registry );

		// Chain multiple methods together.
		$result = $prompt_builder
			->with_text( 'Test prompt' )
			->using_system_instruction( 'You are a helpful assistant' )
			->using_max_tokens( 500 )
			->using_temperature( 0.7 )
			->using_top_p( 0.9 );

		// The final result should still be the same Prompt_Builder instance.
		$this->assertSame( $prompt_builder, $result, 'Chained methods should return the same Prompt_Builder decorator instance' );
		$this->assertInstanceOf( Prompt_Builder::class, $result );
	}

	/**
	 * Test that boolean-returning methods do not return the decorator.
	 *
	 * @since n.e.x.t
	 */
	public function test_boolean_methods_return_boolean(): void {
		$registry       = AiClient::defaultRegistry();
		$prompt_builder = new Prompt_Builder( $registry, 'Test text' );

		// Boolean methods should return boolean, not the decorator.
		$result = $prompt_builder->is_supported_for_text_generation();
		$this->assertIsBool( $result, 'is_supported_for_text_generation should return a boolean' );
		$this->assertNotSame( $prompt_builder, $result, 'is_supported_for_text_generation should not return the decorator' );
	}

	/**
	 * Test snake_case to camelCase conversion.
	 *
	 * This tests that snake_case method names are properly converted to camelCase
	 * when proxying to the underlying PromptBuilder.
	 *
	 * @since n.e.x.t
	 */
	public function test_snake_case_to_camel_case_conversion(): void {
		$registry       = AiClient::defaultRegistry();
		$prompt_builder = new Prompt_Builder( $registry );

		// Test various snake_case patterns.
		$test_cases = array(
			'with_text'                   => 'withText',
			'using_system_instruction'    => 'usingSystemInstruction',
			'using_max_tokens'            => 'usingMaxTokens',
			'as_output_mime_type'         => 'asOutputMimeType',
			'using_model_config'          => 'usingModelConfig',
			'with_message_parts'          => 'withMessageParts',
			'using_stop_sequences'        => 'usingStopSequences',
			'using_candidate_count'       => 'usingCandidateCount',
			'using_function_declarations' => 'usingFunctionDeclarations',
		);

		$reflection_class  = new ReflectionClass( Prompt_Builder::class );
		$conversion_method = $reflection_class->getMethod( 'snake_to_camel_case' );
		$conversion_method->setAccessible( true );

		foreach ( $test_cases as $snake_case => $expected_camel_case ) {
			$actual_camel_case = $conversion_method->invoke( $prompt_builder, $snake_case );
			$this->assertSame( $expected_camel_case, $actual_camel_case, "Failed converting {$snake_case} to {$expected_camel_case}" );
		}
	}

	/**
	 * Test that calling a non-existent method throws an exception.
	 *
	 * @since n.e.x.t
	 */
	public function test_invalid_method_throws_exception(): void {
		$registry       = AiClient::defaultRegistry();
		$prompt_builder = new Prompt_Builder( $registry );

		$this->expectException( BadMethodCallException::class );
		$this->expectExceptionMessage( 'Method non_existent_method does not exist' );

		$prompt_builder->non_existent_method();
	}

	/**
	 * Test that get_builder_callable returns a valid callable.
	 *
	 * @since n.e.x.t
	 */
	public function test_get_builder_callable(): void {
		$registry       = AiClient::defaultRegistry();
		$prompt_builder = new Prompt_Builder( $registry );

		$reflection_class = new ReflectionClass( Prompt_Builder::class );
		$callable_method  = $reflection_class->getMethod( 'get_builder_callable' );
		$callable_method->setAccessible( true );

		$callable = $callable_method->invoke( $prompt_builder, 'with_text' );
		$this->assertTrue( is_callable( $callable ), 'get_builder_callable should return a valid callable' );

		// Verify the callable is an array with the wrapped builder and the camelCase method name.
		$this->assertIsArray( $callable );
		$this->assertCount( 2, $callable );
		$this->assertInstanceOf( PromptBuilder::class, $callable[0] );
		$this->assertSame( 'withText', $callable[1] );
	}

	/**
	 * Test that the wrapped builder is properly configured with the registry.
	 *
	 * @since n.e.x.t
	 */
	public function test_wrapped_builder_has_correct_registry(): void {
		$registry       = AiClient::defaultRegistry();
		$prompt_builder = new Prompt_Builder( $registry );

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
	 * Test method chaining with with_history.
	 *
	 * @since n.e.x.t
	 */
	public function test_method_chaining_with_history(): void {
		$registry       = AiClient::defaultRegistry();
		$prompt_builder = new Prompt_Builder( $registry );

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
		$this->assertSame( $prompt_builder, $result, 'with_history should return the Prompt_Builder decorator instance' );
		$this->assertInstanceOf( Prompt_Builder::class, $result );
	}

	/**
	 * Test method chaining with using_model_config.
	 *
	 * @since n.e.x.t
	 */
	public function test_method_chaining_with_model_config(): void {
		$registry       = AiClient::defaultRegistry();
		$prompt_builder = new Prompt_Builder( $registry );

		$config = new ModelConfig( array( 'maxTokens' => 100 ) );

		$result = $prompt_builder->using_model_config( $config );
		$this->assertSame( $prompt_builder, $result, 'using_model_config should return the Prompt_Builder decorator instance' );
		$this->assertInstanceOf( Prompt_Builder::class, $result );
	}
}
