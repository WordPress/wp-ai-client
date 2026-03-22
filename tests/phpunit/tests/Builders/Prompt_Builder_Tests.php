<?php
/**
 * Tests for WordPress\AI_Client\Builders\Prompt_Builder
 *
 * @package WordPress\AI_Client
 */

namespace WordPress\AI_Client\PHPUnit\Tests\Builders;

use BadMethodCallException;
use Generator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RuntimeException;
use WordPress\AI_Client\Builders\Exception\Prompt_Prevented_Exception;
use WordPress\AI_Client\Builders\Prompt_Builder;
use WordPress\AI_Client\PHPUnit\Includes\Mock_Model_Creation_Trait;
use WordPress\AI_Client\PHPUnit\Includes\Test_Case;
use WordPress\AiClient\AiClient;
use WordPress\AiClient\Builders\PromptBuilder;
use WordPress\AiClient\Files\DTO\File;
use WordPress\AiClient\Files\Enums\FileTypeEnum;
use WordPress\AiClient\Messages\DTO\Message;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Messages\DTO\ModelMessage;
use WordPress\AiClient\Messages\DTO\UserMessage;
use WordPress\AiClient\Messages\Enums\MessageRoleEnum;
use WordPress\AiClient\Messages\Enums\ModalityEnum;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\DTO\ProviderModelsMetadata;
use WordPress\AiClient\Providers\Enums\ProviderTypeEnum;
use WordPress\AiClient\Providers\Http\DTO\RequestOptions;
use WordPress\AiClient\Providers\Models\Contracts\ModelInterface;
use WordPress\AiClient\Providers\Models\DTO\ModelConfig;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Providers\Models\DTO\ModelRequirements;
use WordPress\AiClient\Providers\Models\DTO\SupportedOption;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;
use WordPress\AiClient\Providers\Models\Enums\OptionEnum;
use WordPress\AiClient\Providers\ProviderRegistry;
use WordPress\AiClient\Results\DTO\Candidate;
use WordPress\AiClient\Results\DTO\GenerativeAiResult;
use WordPress\AiClient\Results\DTO\TokenUsage;
use WordPress\AiClient\Results\Enums\FinishReasonEnum;
use WordPress\AiClient\Tools\DTO\FunctionDeclaration;
use WordPress\AiClient\Tools\DTO\FunctionResponse;
use WordPress\AiClient\Tools\DTO\WebSearch;

class Prompt_Builder_Tests extends Test_Case {
	use Mock_Model_Creation_Trait;

	/**
	 * @var ProviderRegistry
	 */
	private ProviderRegistry $registry;

	/**
	 * Creates a test provider metadata instance.
	 *
	 * @return ProviderMetadata
	 */
	private function create_test_provider_metadata(): ProviderMetadata {
		return new ProviderMetadata( 'test-provider', 'Test Provider', ProviderTypeEnum::cloud() );
	}

	/**
	 * Creates text model metadata supporting any input modalities.
	 *
	 * @param string $id The model identifier.
	 * @return ModelMetadata
	 */
	private function create_text_model_metadata_with_input_support( string $id ): ModelMetadata {
		return new ModelMetadata(
			$id,
			'Test Text Model',
			array( CapabilityEnum::textGeneration() ),
			array(
				new SupportedOption( OptionEnum::inputModalities() ),
				new SupportedOption( OptionEnum::outputModalities() ),
			)
		);
	}

	/**
	 * Gets the value of a protected or private property from the wrapped prompt builder from the PHP AI Client SDK.
	 *
	 * @param Prompt_Builder $builder  The WordPress prompt builder instance.
	 * @param string         $property Property to get value for from the wrapped PHP prompt builder instance.
	 */
	private function get_wrapped_prompt_builder_property_value( Prompt_Builder $builder, string $property ) {
		$reflection_class = new ReflectionClass( Prompt_Builder::class );
		$builder_property = $reflection_class->getProperty( 'builder' );
		$builder_property->setAccessible( true );

		$wrapped_builder = $builder_property->getValue( $builder );

		$reflection_class2 = new ReflectionClass( get_class( $wrapped_builder ) );
		$the_property      = $reflection_class2->getProperty( $property );
		$the_property->setAccessible( true );

		return $the_property->getValue( $wrapped_builder );
	}

	/**
	 * Sets up test fixtures.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->registry = $this->createMock( ProviderRegistry::class );
	}

	/**
	 * Test that Prompt_Builder can be instantiated.
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
	 */
	public function test_instantiation_with_prompt(): void {
		$registry       = AiClient::defaultRegistry();
		$prompt_builder = new Prompt_Builder( $registry, 'Initial prompt text' );

		$this->assertInstanceOf( Prompt_Builder::class, $prompt_builder );
	}

	/**
	 * Ensures the wrapped SDK PromptBuilder receives the global event dispatcher after AI_Client initializes.
	 *
	 * Regression test for lifecycle hooks (wp_ai_client_before_generate_result / wp_ai_client_after_generate_result).
	 *
	 * @since n.e.x.t
	 *
	 * @return void
	 */
	public function test_wrapped_prompt_builder_receives_global_event_dispatcher(): void {
		$this->assertNotNull(
			AiClient::getEventDispatcher(),
			'AI_Client::init() should have registered an event dispatcher in the test environment.'
		);

		$prompt_builder   = new Prompt_Builder( AiClient::defaultRegistry() );
		$inner_dispatcher = $this->get_wrapped_prompt_builder_property_value( $prompt_builder, 'eventDispatcher' );

		$this->assertNotNull( $inner_dispatcher );
		$this->assertSame( AiClient::getEventDispatcher(), $inner_dispatcher );
	}

	/**
	 * Test that the constructor sets the default request timeout.
	 */
	public function test_constructor_sets_default_request_timeout(): void {
		$builder = new Prompt_Builder( AiClient::defaultRegistry() );

		/** @var RequestOptions $request_options */
		$request_options = $this->get_wrapped_prompt_builder_property_value( $builder, 'requestOptions' );

		$this->assertInstanceOf( RequestOptions::class, $request_options );
		$this->assertEquals( 30, $request_options->getTimeout() );
	}

	/**
	 * Test that the constructor allows overriding the default request timeout.
	 */
	public function test_constructor_allows_overriding_request_timeout(): void {
		add_filter(
			'wp_ai_client_default_request_timeout',
			static function () {
				return 45;
			}
		);

		$builder = new Prompt_Builder( AiClient::defaultRegistry() );

		/** @var RequestOptions $request_options */
		$request_options = $this->get_wrapped_prompt_builder_property_value( $builder, 'requestOptions' );

		$this->assertInstanceOf( RequestOptions::class, $request_options );
		$this->assertEquals( 45, $request_options->getTimeout() );
	}

	/**
	 * Test method chaining with fluent methods.
	 *
	 * This tests the bug fix where methods that return the PromptBuilder instance
	 * should instead return the Prompt_Builder decorator to allow proper chaining.
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
	 */
	public function test_method_chaining_with_model_config(): void {
		$registry       = AiClient::defaultRegistry();
		$prompt_builder = new Prompt_Builder( $registry );

		$config = new ModelConfig( array( 'maxTokens' => 100 ) );

		$result = $prompt_builder->using_model_config( $config );
		$this->assertSame( $prompt_builder, $result, 'using_model_config should return the Prompt_Builder decorator instance' );
		$this->assertInstanceOf( Prompt_Builder::class, $result );
	}

	/**
	 * Tests constructor with no prompt.
	 *
	 * @return void
	 */
	public function test_constructor_with_no_prompt(): void {
		$builder = new Prompt_Builder( $this->registry );

		$messages = $this->get_wrapped_prompt_builder_property_value( $builder, 'messages' );
		$this->assertEmpty( $messages );
	}

	/**
	 * Tests constructor with string prompt.
	 *
	 * @return void
	 */
	public function test_constructor_with_string_prompt(): void {
		$builder = new Prompt_Builder( $this->registry, 'Hello, world!' );

		/** @var list<Message> $messages */
		$messages = $this->get_wrapped_prompt_builder_property_value( $builder, 'messages' );

		$this->assertCount( 1, $messages );
		$this->assertInstanceOf( Message::class, $messages[0] );
		$this->assertEquals( 'Hello, world!', $messages[0]->getParts()[0]->getText() );
	}

	/**
	 * Tests constructor with MessagePart prompt.
	 *
	 * @return void
	 */
	public function test_constructor_with_message_part_prompt(): void {
		$part    = new MessagePart( 'Test message' );
		$builder = new Prompt_Builder( $this->registry, $part );

		/** @var list<Message> $messages */
		$messages = $this->get_wrapped_prompt_builder_property_value( $builder, 'messages' );

		$this->assertCount( 1, $messages );
		$this->assertInstanceOf( Message::class, $messages[0] );
		$this->assertEquals( 'Test message', $messages[0]->getParts()[0]->getText() );
	}

	/**
	 * Tests constructor with Message prompt.
	 *
	 * @return void
	 */
	public function test_constructor_with_message_prompt(): void {
		$message = new UserMessage( array( new MessagePart( 'User message' ) ) );
		$builder = new Prompt_Builder( $this->registry, $message );

		/** @var list<Message> $messages */
		$messages = $this->get_wrapped_prompt_builder_property_value( $builder, 'messages' );

		$this->assertCount( 1, $messages );
		$this->assertSame( $message, $messages[0] );
	}

	/**
	 * Tests constructor with list of Messages.
	 *
	 * @return void
	 */
	public function test_constructor_with_messages_list(): void {
		$messages = array(
			new UserMessage( array( new MessagePart( 'First' ) ) ),
			new ModelMessage( array( new MessagePart( 'Second' ) ) ),
			new UserMessage( array( new MessagePart( 'Third' ) ) ),
		);
		$builder  = new Prompt_Builder( $this->registry, $messages );

		/** @var list<Message> $actual_messages */
		$actual_messages = $this->get_wrapped_prompt_builder_property_value( $builder, 'messages' );

		$this->assertCount( 3, $actual_messages );
		$this->assertSame( $messages, $actual_messages );
	}

	/**
	 * Tests constructor with MessageArrayShape.
	 *
	 * @return void
	 */
	public function test_constructor_with_message_array_shape(): void {
		$message_array = array(
			'role'  => 'user',
			'parts' => array(
				array(
					'type' => 'text',
					'text' => 'Hello from array',
				),
			),
		);
		$builder       = new Prompt_Builder( $this->registry, $message_array );

		/** @var list<Message> $messages */
		$messages = $this->get_wrapped_prompt_builder_property_value( $builder, 'messages' );

		$this->assertCount( 1, $messages );
		$this->assertInstanceOf( Message::class, $messages[0] );
		$this->assertEquals( 'Hello from array', $messages[0]->getParts()[0]->getText() );
	}

	/**
	 * Tests withText method.
	 *
	 * @return void
	 */
	public function test_with_text(): void {
		$builder = new Prompt_Builder( $this->registry );
		$result  = $builder->with_text( 'Some text' );

		$this->assertSame( $builder, $result ); // Test fluent interface

		/** @var list<Message> $messages */
		$messages = $this->get_wrapped_prompt_builder_property_value( $builder, 'messages' );

		$this->assertCount( 1, $messages );
		$this->assertEquals( 'Some text', $messages[0]->getParts()[0]->getText() );
	}

	/**
	 * Tests withText appends to existing user message.
	 *
	 * @return void
	 */
	public function test_with_text_appends_to_existing_user_message(): void {
		$builder = new Prompt_Builder( $this->registry, 'Initial text' );
		$builder->with_text( ' Additional text' );

		/** @var list<Message> $messages */
		$messages = $this->get_wrapped_prompt_builder_property_value( $builder, 'messages' );

		$this->assertCount( 1, $messages );
		$parts = $messages[0]->getParts();
		$this->assertCount( 2, $parts );
		$this->assertEquals( 'Initial text', $parts[0]->getText() );
		$this->assertEquals( ' Additional text', $parts[1]->getText() );
	}

	/**
	 * Tests withFile method with base64 data.
	 *
	 * @return void
	 */
	public function test_with_inline_file(): void {
		$builder = new Prompt_Builder( $this->registry );
		$base64  = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';
		$result  = $builder->with_file( $base64, 'image/png' );

		$this->assertSame( $builder, $result );

		/** @var list<Message> $messages */
		$messages = $this->get_wrapped_prompt_builder_property_value( $builder, 'messages' );

		$this->assertCount( 1, $messages );
		$file = $messages[0]->getParts()[0]->getFile();
		$this->assertInstanceOf( File::class, $file );
		$this->assertEquals( 'data:image/png;base64,' . $base64, $file->getDataUri() );
		$this->assertEquals( 'image/png', $file->getMimeType() );
	}

	/**
	 * Tests withFile method with remote URL.
	 *
	 * @return void
	 */
	public function test_with_remote_file(): void {
		$builder = new Prompt_Builder( $this->registry );
		$result  = $builder->with_file( 'https://example.com/image.jpg', 'image/jpeg' );

		$this->assertSame( $builder, $result );

		/** @var list<Message> $messages */
		$messages = $this->get_wrapped_prompt_builder_property_value( $builder, 'messages' );

		$this->assertCount( 1, $messages );
		$file = $messages[0]->getParts()[0]->getFile();
		$this->assertInstanceOf( File::class, $file );
		$this->assertEquals( 'https://example.com/image.jpg', $file->getUrl() );
		$this->assertEquals( 'image/jpeg', $file->getMimeType() );
	}

	/**
	 * Tests withFile with data URI.
	 *
	 * @return void
	 */
	public function test_with_inline_file_data_uri(): void {
		$builder  = new Prompt_Builder( $this->registry );
		$data_uri = 'data:image/jpeg;base64,/9j/4AAQSkZJRg==';
		$result   = $builder->with_file( $data_uri );

		$this->assertSame( $builder, $result );

		/** @var list<Message> $messages */
		$messages = $this->get_wrapped_prompt_builder_property_value( $builder, 'messages' );

		$this->assertCount( 1, $messages );
		$file = $messages[0]->getParts()[0]->getFile();
		$this->assertInstanceOf( File::class, $file );
		$this->assertEquals( 'image/jpeg', $file->getMimeType() );
	}

	/**
	 * Tests withFile with URL without explicit MIME type.
	 *
	 * @return void
	 */
	public function test_with_remote_file_without_mime_type(): void {
		$builder = new Prompt_Builder( $this->registry );
		// File extension should be used to determine MIME type
		$result = $builder->with_file( 'https://example.com/audio.mp3' );

		$this->assertSame( $builder, $result );

		/** @var list<Message> $messages */
		$messages = $this->get_wrapped_prompt_builder_property_value( $builder, 'messages' );

		$this->assertCount( 1, $messages );
		$file = $messages[0]->getParts()[0]->getFile();
		$this->assertInstanceOf( File::class, $file );
		$this->assertEquals( 'https://example.com/audio.mp3', $file->getUrl() );
		$this->assertEquals( 'audio/mpeg', $file->getMimeType() );
	}

	/**
	 * Tests withFunctionResponse method.
	 *
	 * @return void
	 */
	public function test_with_function_response(): void {
		$function_response = new FunctionResponse( 'func_id', 'func_name', array( 'result' => 'data' ) );
		$builder           = new Prompt_Builder( $this->registry );
		$result            = $builder->with_function_response( $function_response );

		$this->assertSame( $builder, $result );

		/** @var list<Message> $messages */
		$messages = $this->get_wrapped_prompt_builder_property_value( $builder, 'messages' );

		$this->assertCount( 1, $messages );
		$this->assertSame( $function_response, $messages[0]->getParts()[0]->getFunctionResponse() );
	}

	/**
	 * Tests withMessageParts method.
	 *
	 * @return void
	 */
	public function test_with_message_parts(): void {
		$part1 = new MessagePart( 'Part 1' );
		$part2 = new MessagePart( 'Part 2' );
		$part3 = new MessagePart( 'Part 3' );

		$builder = new Prompt_Builder( $this->registry );
		$result  = $builder->with_message_parts( $part1, $part2, $part3 );

		$this->assertSame( $builder, $result );

		/** @var list<Message> $messages */
		$messages = $this->get_wrapped_prompt_builder_property_value( $builder, 'messages' );

		$this->assertCount( 1, $messages );
		$parts = $messages[0]->getParts();
		$this->assertCount( 3, $parts );
		$this->assertEquals( 'Part 1', $parts[0]->getText() );
		$this->assertEquals( 'Part 2', $parts[1]->getText() );
		$this->assertEquals( 'Part 3', $parts[2]->getText() );
	}

	/**
	 * Tests withHistory method.
	 *
	 * @return void
	 */
	public function test_with_history(): void {
		$history = array(
			new UserMessage( array( new MessagePart( 'User 1' ) ) ),
			new ModelMessage( array( new MessagePart( 'Model 1' ) ) ),
			new UserMessage( array( new MessagePart( 'User 2' ) ) ),
		);

		$builder = new Prompt_Builder( $this->registry );
		$result  = $builder->with_history( ...$history );

		$this->assertSame( $builder, $result );

		/** @var list<Message> $messages */
		$messages = $this->get_wrapped_prompt_builder_property_value( $builder, 'messages' );

		$this->assertCount( 3, $messages );
		$this->assertEquals( 'User 1', $messages[0]->getParts()[0]->getText() );
		$this->assertEquals( 'Model 1', $messages[1]->getParts()[0]->getText() );
		$this->assertEquals( 'User 2', $messages[2]->getParts()[0]->getText() );
	}

	/**
	 * Tests usingModel method.
	 *
	 * @return void
	 */
	public function test_using_model(): void {
		// Create a model with empty config
		$model_config = new ModelConfig();
		$model        = $this->createMock( ModelInterface::class );
		$model->method( 'getConfig' )->willReturn( $model_config );

		$builder = new Prompt_Builder( $this->registry );
		$result  = $builder->using_model( $model );

		$this->assertSame( $builder, $result );

		/** @var ModelInterface $actual_model */
		$actual_model = $this->get_wrapped_prompt_builder_property_value( $builder, 'model' );
		$this->assertSame( $model, $actual_model );
	}

	/**
	 * Tests constructor with list of string parts.
	 *
	 * @return void
	 */
	public function test_constructor_with_string_parts_list(): void {
		$builder = new Prompt_Builder( $this->registry, array( 'Part 1', 'Part 2', 'Part 3' ) );

		/** @var list<Message> $messages */
		$messages = $this->get_wrapped_prompt_builder_property_value( $builder, 'messages' );

		$this->assertCount( 1, $messages );
		$this->assertInstanceOf( Message::class, $messages[0] );
		$parts = $messages[0]->getParts();
		$this->assertCount( 3, $parts );
		$this->assertEquals( 'Part 1', $parts[0]->getText() );
		$this->assertEquals( 'Part 2', $parts[1]->getText() );
		$this->assertEquals( 'Part 3', $parts[2]->getText() );
	}

	/**
	 * Tests constructor with mixed parts list.
	 *
	 * @return void
	 */
	public function test_constructor_with_mixed_parts_list(): void {
		$part1       = new MessagePart( 'Part 1' );
		$part2_array = array(
			'type' => 'text',
			'text' => 'Part 2',
		);

		$builder = new Prompt_Builder( $this->registry, array( 'String part', $part1, $part2_array ) );

		/** @var list<Message> $messages */
		$messages = $this->get_wrapped_prompt_builder_property_value( $builder, 'messages' );

		$this->assertCount( 1, $messages );
		$parts = $messages[0]->getParts();
		$this->assertCount( 3, $parts );
		$this->assertEquals( 'String part', $parts[0]->getText() );
		$this->assertEquals( 'Part 1', $parts[1]->getText() );
		$this->assertEquals( 'Part 2', $parts[2]->getText() );
	}

	/**
	 * Tests method chaining.
	 *
	 * @return void
	 */
	public function test_method_chaining(): void {
		$model = $this->createMock( ModelInterface::class );

		$builder = new Prompt_Builder( $this->registry );
		$result  = $builder
			->with_text( 'Start of prompt' )
			->with_file( 'https://example.com/img.jpg', 'image/jpeg' )
			->using_model( $model )
			->using_system_instruction( 'Be helpful' )
			->using_max_tokens( 500 )
			->using_temperature( 0.8 )
			->using_top_p( 0.95 )
			->using_top_k( 50 )
			->using_candidate_count( 2 )
			->as_json_response();

		$this->assertSame( $builder, $result );

		/** @var list<Message> $messages */
		$messages = $this->get_wrapped_prompt_builder_property_value( $builder, 'messages' );
		$this->assertCount( 1, $messages );
		$this->assertCount( 2, $messages[0]->getParts() ); // Text and image

		/** @var ModelInterface $actual_model */
		$actual_model = $this->get_wrapped_prompt_builder_property_value( $builder, 'model' );
		$this->assertSame( $model, $actual_model );

		/** @var ModelConfig $config */
		$config = $this->get_wrapped_prompt_builder_property_value( $builder, 'modelConfig' );

		$this->assertEquals( 'Be helpful', $config->getSystemInstruction() );
		$this->assertEquals( 500, $config->getMaxTokens() );
		$this->assertEquals( 0.8, $config->getTemperature() );
		$this->assertEquals( 0.95, $config->getTopP() );
		$this->assertEquals( 50, $config->getTopK() );
		$this->assertEquals( 2, $config->getCandidateCount() );
		$this->assertEquals( 'application/json', $config->getOutputMimeType() );
	}

	/**
	 * Tests usingModelPreference skips unavailable model IDs and falls back to the next preference.
	 *
	 * @return void
	 */
	public function test_using_model_preference_skips_unavailable_model_id(): void {
		$result            = $this->create_test_result( 'Fallback model result' );
		$other_metadata    = $this->create_text_model_metadata_with_input_support( 'other-id' );
		$fallback_metadata = $this->create_text_model_metadata_with_input_support( 'fallback-id' );
		$model             = $this->create_mock_text_generation_model( $result, $fallback_metadata );

		$this->registry->expects( $this->once() )
			->method( 'getProviderId' )
			->with( 'test-provider' )
			->willReturn( 'test-provider' );

		$this->registry->expects( $this->once() )
			->method( 'findProviderModelsMetadataForSupport' )
			->with( 'test-provider', $this->isInstanceOf( ModelRequirements::class ) )
			->willReturn( array( $other_metadata, $fallback_metadata ) );

		$this->registry->expects( $this->once() )
			->method( 'getProviderModel' )
			->with( 'test-provider', 'fallback-id', $this->isInstanceOf( ModelConfig::class ) )
			->willReturn( $model );

		$this->registry->expects( $this->never() )
			->method( 'findModelsMetadataForSupport' );

		$builder = new Prompt_Builder( $this->registry, 'Test prompt' );
		$builder->using_provider( 'test-provider' );
		$builder->using_model_preference( 'missing-id', 'fallback-id' );

		$actual_result = $builder->generate_text_result();

		$this->assertSame( $result, $actual_result );
	}

	/**
	 * Tests usingModelPreference falls back to discovery when no preferences are available.
	 *
	 * @return void
	 */
	public function test_using_model_preference_falls_back_to_discovery(): void {
		$result                   = $this->create_test_result( 'Discovered model result' );
		$metadata                 = $this->create_text_model_metadata_with_input_support( 'discovered-id' );
		$provider_metadata        = $this->create_test_provider_metadata();
		$provider_models_metadata = new ProviderModelsMetadata( $provider_metadata, array( $metadata ) );

		$model = $this->create_mock_text_generation_model( $result, $metadata );

		$this->registry->expects( $this->once() )
			->method( 'findModelsMetadataForSupport' )
			->with( $this->isInstanceOf( ModelRequirements::class ) )
			->willReturn( array( $provider_models_metadata ) );

		$this->registry->expects( $this->once() )
			->method( 'getProviderModel' )
			->with( $provider_metadata->getId(), 'discovered-id', $this->isInstanceOf( ModelConfig::class ) )
			->willReturn( $model );

		$this->registry->expects( $this->never() )
			->method( 'findProviderModelsMetadataForSupport' );

		$builder = new Prompt_Builder( $this->registry, 'Test prompt' );
		$builder->using_model_preference( 'unavailable-model' );

		$actual_result = $builder->generate_text_result();

		$this->assertSame( $result, $actual_result );
	}

	/**
	 * Tests usingModelPreference respects priority order when multiple preferred models are available.
	 *
	 * @return void
	 */
	public function test_using_model_preference_respects_order_when_multiple_available(): void {
		$result                 = $this->create_test_result( 'Second choice result' );
		$second_choice_metadata = $this->create_text_model_metadata_with_input_support( 'second-choice' );
		$third_choice_metadata  = $this->create_text_model_metadata_with_input_support( 'third-choice' );
		$provider_metadata      = $this->create_test_provider_metadata();

		$model = $this->create_mock_text_generation_model( $result, $second_choice_metadata );

		// Make both second-choice and third-choice available (but not first-choice)
		$provider_models_metadata = new ProviderModelsMetadata(
			$provider_metadata,
			array( $third_choice_metadata, $second_choice_metadata )  // Order shouldn't matter
		);

		$this->registry->expects( $this->once() )
			->method( 'findModelsMetadataForSupport' )
			->with( $this->isInstanceOf( ModelRequirements::class ) )
			->willReturn( array( $provider_models_metadata ) );

		// Should select 'second-choice' (respecting preference order), not 'third-choice'
		$this->registry->expects( $this->once() )
			->method( 'getProviderModel' )
			->with( $provider_metadata->getId(), 'second-choice', $this->isInstanceOf( ModelConfig::class ) )
			->willReturn( $model );

		$this->registry->expects( $this->never() )
			->method( 'findProviderModelsMetadataForSupport' );

		$builder = new Prompt_Builder( $this->registry, 'Test prompt' );
		// Preferences in order: first-choice, second-choice, third-choice
		// Available: second-choice, third-choice
		// Expected: second-choice (respects priority)
		$builder->using_model_preference( 'first-choice', 'second-choice', 'third-choice' );

		$actual_result = $builder->generate_text_result();

		$this->assertSame( $result, $actual_result );
	}

	/**
	 * Tests usingModelPreference rejects invalid preference types.
	 *
	 * @return void
	 */
	public function test_using_model_preference_with_invalid_type_throws_exception(): void {
		$builder = new Prompt_Builder( $this->registry );

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage(
			'Model preferences must be model identifiers, instances of ModelInterface, or provider/model tuples.'
		);

		$builder->using_model_preference( 123 );
	}

	/**
	 * Tests usingModelPreference rejects malformed preference tuples.
	 *
	 * @return void
	 */
	public function test_using_model_preference_with_invalid_tuple_throws_exception(): void {
		$builder = new Prompt_Builder( $this->registry );

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Model preference tuple must contain model identifier and provider ID.' );

		$builder->using_model_preference(
			array(
				'provider' => 'test',
				'model'    => 'id',
			)
		);
	}

	/**
	 * Tests usingModelPreference rejects empty preference identifier strings.
	 *
	 * @return void
	 */
	public function test_using_model_preference_with_empty_identifier_throws_exception(): void {
		$builder = new Prompt_Builder( $this->registry );

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Model preference identifiers cannot be empty.' );

		$builder->using_model_preference( '   ' );
	}

	/**
	 * Tests usingModelPreference rejects calls without preferences.
	 *
	 * @return void
	 */
	public function test_using_model_preference_without_arguments_throws_exception(): void {
		$builder = new Prompt_Builder( $this->registry );

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'At least one model preference must be provided.' );

		$builder->using_model_preference();
	}

	/**
	 * Tests usingModelConfig method.
	 *
	 * @return void
	 */
	public function test_using_model_config(): void {
		$builder = new Prompt_Builder( $this->registry );

		// Set some initial config values on the builder
		$builder->using_system_instruction( 'Builder instruction' )
				->using_max_tokens( 500 )
				->using_temperature( 0.5 );

		// Create a config to merge
		$config = new ModelConfig();
		$config->setSystemInstruction( 'Config instruction' );
		$config->setMaxTokens( 1000 );
		$config->setTopP( 0.9 );
		$config->setTopK( 40 );

		$result = $builder->using_model_config( $config );

		// Assert fluent interface
		$this->assertSame( $builder, $result );

		/** @var ModelConfig $merged_config */
		$merged_config = $this->get_wrapped_prompt_builder_property_value( $builder, 'modelConfig' );

		// Check that builder's additional config was included
		// Assert builder values take precedence
		$this->assertEquals( 'Builder instruction', $merged_config->getSystemInstruction() );
		$this->assertEquals( 500, $merged_config->getMaxTokens() );
		$this->assertEquals( 0.5, $merged_config->getTemperature() );

		// Assert config values are used when builder doesn't have them
		$this->assertEquals( 0.9, $merged_config->getTopP() );
		$this->assertEquals( 40, $merged_config->getTopK() );
	}

	/**
	 * Tests usingModelConfig with custom options.
	 *
	 * @return void
	 */
	public function test_using_model_config_with_custom_options(): void {
		$builder = new Prompt_Builder( $this->registry );

		// Create a config with custom options
		$config = new ModelConfig();
		$config->setCustomOption( 'stopSequences', array( 'CONFIG_STOP' ) );
		$config->setCustomOption( 'otherOption', 'value' );

		$builder->using_model_config( $config );

		/** @var ModelConfig $merged_config */
		$merged_config  = $this->get_wrapped_prompt_builder_property_value( $builder, 'modelConfig' );
		$custom_options = $merged_config->getCustomOptions();

		// Assert config custom options are preserved
		$this->assertArrayHasKey( 'stopSequences', $custom_options );
		$this->assertIsArray( $custom_options['stopSequences'] );
		$this->assertEquals( array( 'CONFIG_STOP' ), $custom_options['stopSequences'] );
		$this->assertArrayHasKey( 'otherOption', $custom_options );
		$this->assertEquals( 'value', $custom_options['otherOption'] );

		// Now set a builder value that overrides one of the custom options
		$builder->using_stop_sequences( 'STOP' );

		/** @var ModelConfig $merged_config */
		$merged_config  = $this->get_wrapped_prompt_builder_property_value( $builder, 'modelConfig' );
		$custom_options = $merged_config->getCustomOptions();

		// Assert builder's stop sequences override the config's
		$this->assertArrayHasKey( 'stopSequences', $custom_options );
		$this->assertIsArray( $custom_options['stopSequences'] );
		$this->assertEquals( array( 'STOP' ), $custom_options['stopSequences'] );

		// Assert other custom options are still preserved
		$this->assertArrayHasKey( 'otherOption', $custom_options );
		$this->assertEquals( 'value', $custom_options['otherOption'] );
	}

	/**
	 * Tests usingProvider method.
	 *
	 * @return void
	 */
	public function test_using_provider(): void {
		$builder = new Prompt_Builder( $this->registry );
		$result  = $builder->using_provider( 'test-provider' );

		$this->assertSame( $builder, $result );

		$actual_provider = $this->get_wrapped_prompt_builder_property_value( $builder, 'providerIdOrClassName' );
		$this->assertEquals( 'test-provider', $actual_provider );
	}

	/**
	 * Tests usingSystemInstruction method.
	 *
	 * @return void
	 */
	public function test_using_system_instruction(): void {
		$builder = new Prompt_Builder( $this->registry );
		$result  = $builder->using_system_instruction( 'You are a helpful assistant.' );

		$this->assertSame( $builder, $result );

		/** @var ModelConfig $config */
		$config = $this->get_wrapped_prompt_builder_property_value( $builder, 'modelConfig' );

		$this->assertEquals( 'You are a helpful assistant.', $config->getSystemInstruction() );
	}

	/**
	 * Tests usingMaxTokens method.
	 *
	 * @return void
	 */
	public function test_using_max_tokens(): void {
		$builder = new Prompt_Builder( $this->registry );
		$result  = $builder->using_max_tokens( 1000 );

		$this->assertSame( $builder, $result );

		/** @var ModelConfig $config */
		$config = $this->get_wrapped_prompt_builder_property_value( $builder, 'modelConfig' );

		$this->assertEquals( 1000, $config->getMaxTokens() );
	}

	/**
	 * Tests usingTemperature method.
	 *
	 * @return void
	 */
	public function test_using_temperature(): void {
		$builder = new Prompt_Builder( $this->registry );
		$result  = $builder->using_temperature( 0.7 );

		$this->assertSame( $builder, $result );

		/** @var ModelConfig $config */
		$config = $this->get_wrapped_prompt_builder_property_value( $builder, 'modelConfig' );

		$this->assertEquals( 0.7, $config->getTemperature() );
	}

	/**
	 * Tests usingTopP method.
	 *
	 * @return void
	 */
	public function test_using_top_p(): void {
		$builder = new Prompt_Builder( $this->registry );
		$result  = $builder->using_top_p( 0.9 );

		$this->assertSame( $builder, $result );

		/** @var ModelConfig $config */
		$config = $this->get_wrapped_prompt_builder_property_value( $builder, 'modelConfig' );

		$this->assertEquals( 0.9, $config->getTopP() );
	}

	/**
	 * Tests usingTopK method.
	 *
	 * @return void
	 */
	public function test_using_top_k(): void {
		$builder = new Prompt_Builder( $this->registry );
		$result  = $builder->using_top_k( 40 );

		$this->assertSame( $builder, $result );

		/** @var ModelConfig $config */
		$config = $this->get_wrapped_prompt_builder_property_value( $builder, 'modelConfig' );

		$this->assertEquals( 40, $config->getTopK() );
	}

	/**
	 * Tests usingStopSequences method.
	 *
	 * @return void
	 */
	public function test_using_stop_sequences(): void {
		$builder = new Prompt_Builder( $this->registry );
		$result  = $builder->using_stop_sequences( 'STOP', 'END', '###' );

		$this->assertSame( $builder, $result );

		/** @var ModelConfig $config */
		$config = $this->get_wrapped_prompt_builder_property_value( $builder, 'modelConfig' );

		$custom_options = $config->getCustomOptions();
		$this->assertArrayHasKey( 'stopSequences', $custom_options );
		$this->assertEquals( array( 'STOP', 'END', '###' ), $custom_options['stopSequences'] );
	}

	/**
	 * Tests usingCandidateCount method.
	 *
	 * @return void
	 */
	public function test_using_candidate_count(): void {
		$builder = new Prompt_Builder( $this->registry );
		$result  = $builder->using_candidate_count( 3 );

		$this->assertSame( $builder, $result );

		/** @var ModelConfig $config */
		$config = $this->get_wrapped_prompt_builder_property_value( $builder, 'modelConfig' );

		$this->assertEquals( 3, $config->getCandidateCount() );
	}

	/**
	 * Tests usingOutputMime method.
	 *
	 * @return void
	 */
	public function test_using_output_mime(): void {
		$builder = new Prompt_Builder( $this->registry );
		$result  = $builder->as_output_mime_type( 'application/json' );

		$this->assertSame( $builder, $result );

		/** @var ModelConfig $config */
		$config = $this->get_wrapped_prompt_builder_property_value( $builder, 'modelConfig' );

		$this->assertEquals( 'application/json', $config->getOutputMimeType() );
	}

	/**
	 * Tests usingOutputSchema method.
	 *
	 * @return void
	 */
	public function test_using_output_schema(): void {
		$schema = array(
			'type'       => 'object',
			'properties' => array(
				'name' => array( 'type' => 'string' ),
			),
		);

		$builder = new Prompt_Builder( $this->registry );
		$result  = $builder->as_output_schema( $schema );

		$this->assertSame( $builder, $result );

		/** @var ModelConfig $config */
		$config = $this->get_wrapped_prompt_builder_property_value( $builder, 'modelConfig' );

		$this->assertEquals( $schema, $config->getOutputSchema() );
	}

	/**
	 * Tests usingOutputModalities method.
	 *
	 * @return void
	 */
	public function test_using_output_modalities(): void {
		$builder = new Prompt_Builder( $this->registry );
		$result  = $builder->as_output_modalities(
			ModalityEnum::text(),
			ModalityEnum::image()
		);

		$this->assertSame( $builder, $result );

		/** @var ModelConfig $config */
		$config = $this->get_wrapped_prompt_builder_property_value( $builder, 'modelConfig' );

		$modalities = $config->getOutputModalities();
		$this->assertCount( 2, $modalities );
		$this->assertTrue( $modalities[0]->isText() );
		$this->assertTrue( $modalities[1]->isImage() );
	}

	/**
	 * Tests asJsonResponse method.
	 *
	 * @return void
	 */
	public function test_as_json_response(): void {
		$builder = new Prompt_Builder( $this->registry );
		$result  = $builder->as_json_response();

		$this->assertSame( $builder, $result );

		/** @var ModelConfig $config */
		$config = $this->get_wrapped_prompt_builder_property_value( $builder, 'modelConfig' );

		$this->assertEquals( 'application/json', $config->getOutputMimeType() );
	}

	/**
	 * Tests asJsonResponse with schema.
	 *
	 * @return void
	 */
	public function test_as_json_response_with_schema(): void {
		$schema  = array( 'type' => 'array' );
		$builder = new Prompt_Builder( $this->registry );
		$result  = $builder->as_json_response( $schema );

		$this->assertSame( $builder, $result );

		/** @var ModelConfig $config */
		$config = $this->get_wrapped_prompt_builder_property_value( $builder, 'modelConfig' );

		$this->assertEquals( 'application/json', $config->getOutputMimeType() );
		$this->assertEquals( $schema, $config->getOutputSchema() );
	}


	/**
	 * Tests validateMessages with empty messages throws exception.
	 *
	 * @return void
	 */
	public function test_validate_messages_empty_throws_exception(): void {
		$builder = new Prompt_Builder( $this->registry );

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Cannot generate from an empty prompt' );

		$builder->generate_result();
	}

	/**
	 * Tests validateMessages with non-user first message throws exception.
	 *
	 * @return void
	 */
	public function test_validate_messages_non_user_first_throws_exception(): void {
		$builder = new Prompt_Builder(
			$this->registry,
			array(
				new ModelMessage( array( new MessagePart( 'Model says hi' ) ) ),
				new UserMessage( array( new MessagePart( 'User response' ) ) ),
			)
		);

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'The first message must be from a user role' );

		$builder->generate_result();
	}

	/**
	 * Tests validateMessages with non-user last message throws exception.
	 *
	 * @return void
	 */
	public function test_validate_messages_non_user_last_throws_exception(): void {
		// Start with a user message
		$builder = new Prompt_Builder( $this->registry );
		$builder->with_text( 'Initial user message' );

		// Add history that will make the last message a model message
		$builder->with_history(
			new UserMessage( array( new MessagePart( 'Historical user message' ) ) ),
			new ModelMessage( array( new MessagePart( 'Historical model response' ) ) )
		);

		// Now add a model message manually to be the last message
		$reflection_class = new ReflectionClass( Prompt_Builder::class );
		$builder_property = $reflection_class->getProperty( 'builder' );
		$builder_property->setAccessible( true );
		$wrapped_builder   = $builder_property->getValue( $builder );
		$reflection_class2 = new ReflectionClass( get_class( $wrapped_builder ) );
		$messages_property = $reflection_class2->getProperty( 'messages' );
		$messages_property->setAccessible( true );

		$messages   = $messages_property->getValue( $wrapped_builder );
		$messages[] = new ModelMessage( array( new MessagePart( 'Final model message' ) ) );
		$messages_property->setValue( $wrapped_builder, $messages );

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'The last message must be from a user role' );

		$builder->generate_result();
	}

	/**
	 * Tests parseMessage with empty string throws exception.
	 *
	 * @return void
	 */
	public function test_parse_message_empty_string_throws_exception(): void {
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Cannot create a message from an empty string' );

		new Prompt_Builder( $this->registry, '   ' );
	}

	/**
	 * Tests parseMessage with empty array throws exception.
	 *
	 * @return void
	 */
	public function test_parse_message_empty_array_throws_exception(): void {
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Cannot create a message from an empty array' );

		new Prompt_Builder( $this->registry, array() );
	}

	/**
	 * Tests parseMessage with invalid type throws exception.
	 *
	 * @return void
	 */
	public function test_parse_message_invalid_type_throws_exception(): void {
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Input must be a string, MessagePart, MessagePartArrayShape' );

		new Prompt_Builder( $this->registry, 123 );
	}



	/**
	 * Tests generateResult with text output modality.
	 *
	 * @return void
	 */
	public function test_generate_result_with_text_modality(): void {
		$result = $this->createMock( GenerativeAiResult::class );

		$metadata = $this->createMock( ModelMetadata::class );
		$metadata->method( 'getId' )->willReturn( 'test-model' );

		$model = $this->create_mock_text_generation_model( $result, $metadata );

		$builder = new Prompt_Builder( $this->registry, 'Test prompt' );
		$builder->using_model( $model );

		$actual_result = $builder->generate_result();
		$this->assertSame( $result, $actual_result );
	}

	/**
	 * Tests generateResult with image output modality.
	 *
	 * @return void
	 */
	public function test_generate_result_with_image_modality(): void {
		$result = new GenerativeAiResult(
			'test-result',
			array(
				new Candidate(
					new ModelMessage( array( new MessagePart( new File( 'data:image/png;base64,iVBORw0KGgo=', 'image/png' ) ) ) ),
					FinishReasonEnum::stop()
				),
			),
			new TokenUsage( 100, 50, 150 ),
			$this->create_test_provider_metadata(),
			$this->create_test_text_model_metadata()
		);

		$metadata = $this->createMock( ModelMetadata::class );
		$metadata->method( 'getId' )->willReturn( 'test-model' );

		$model = $this->create_mock_image_generation_model( $result, $metadata );

		$builder = new Prompt_Builder( $this->registry, 'Generate an image' );
		$builder->using_model( $model );
		$builder->as_output_modalities( ModalityEnum::image() );

		$actual_result = $builder->generate_result();
		$this->assertSame( $result, $actual_result );
	}

	/**
	 * Tests generateResult with audio output modality.
	 *
	 * @return void
	 */
	public function test_generate_result_with_audio_modality(): void {
		$result = new GenerativeAiResult(
			'test-result',
			array(
				new Candidate(
					new ModelMessage( array( new MessagePart( new File( 'data:audio/wav;base64,UklGRigE=', 'audio/wav' ) ) ) ),
					FinishReasonEnum::stop()
				),
			),
			new TokenUsage( 100, 50, 150 ),
			$this->create_test_provider_metadata(),
			$this->create_test_text_model_metadata()
		);

		$metadata = $this->createMock( ModelMetadata::class );
		$metadata->method( 'getId' )->willReturn( 'test-model' );

		$model = $this->create_mock_speech_generation_model( $result, $metadata );

		$builder = new Prompt_Builder( $this->registry, 'Generate speech' );
		$builder->using_model( $model );
		$builder->as_output_modalities( ModalityEnum::audio() );

		$actual_result = $builder->generate_result();
		$this->assertSame( $result, $actual_result );
	}

	/**
	 * Tests generateResult with multimodal output.
	 *
	 * @return void
	 */
	public function test_generate_result_with_multimodal_output(): void {
		$result = new GenerativeAiResult(
			'test-result',
			array( new Candidate( new ModelMessage( array( new MessagePart( 'Generated text' ) ) ), FinishReasonEnum::stop() ) ),
			new TokenUsage( 100, 50, 150 ),
			$this->create_test_provider_metadata(),
			$this->create_test_text_model_metadata()
		);

		$metadata = $this->createMock( ModelMetadata::class );
		$metadata->method( 'getId' )->willReturn( 'test-model' );

		$model = $this->create_mock_text_generation_model( $result, $metadata );

		$builder = new Prompt_Builder( $this->registry, 'Generate multimodal' );
		$builder->using_model( $model );
		$builder->as_output_modalities( ModalityEnum::text(), ModalityEnum::image() );

		$actual_result = $builder->generate_result();
		$this->assertSame( $result, $actual_result );
	}

	/**
	 * Tests generateResult throws exception when model doesn't support modality.
	 *
	 * @return void
	 */
	public function test_generate_result_throws_exception_for_unsupported_modality(): void {
		$metadata = $this->createMock( ModelMetadata::class );
		$metadata->method( 'getId' )->willReturn( 'test-model' );

		// Model that only implements ModelInterface, not TextGenerationModelInterface
		$model = $this->createMock( ModelInterface::class );
		$model->method( 'metadata' )->willReturn( $metadata );

		$builder = new Prompt_Builder( $this->registry, 'Test prompt' );
		$builder->using_model( $model );

		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'Model "test-model" does not support text generation' );

		$builder->generate_result();
	}

	/**
	 * Tests generateResult throws exception for unsupported output modality.
	 *
	 * @return void
	 */
	public function test_generate_result_throws_exception_for_unsupported_output_modality(): void {
		$metadata = $this->createMock( ModelMetadata::class );
		$metadata->method( 'getId' )->willReturn( 'test-model' );

		$model = $this->createMock( ModelInterface::class );
		$model->method( 'metadata' )->willReturn( $metadata );

		$builder = new Prompt_Builder( $this->registry, 'Test prompt' );
		$builder->using_model( $model );
		$builder->as_output_modalities( ModalityEnum::video() );

		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'Output modality "video" is not yet supported' );

		$builder->generate_result();
	}

	/**
	 * Tests generateTextResult method.
	 *
	 * @return void
	 */
	public function test_generate_text_result(): void {
		$result = new GenerativeAiResult(
			'test-result',
			array( new Candidate( new ModelMessage( array( new MessagePart( 'Generated text' ) ) ), FinishReasonEnum::stop() ) ),
			new TokenUsage( 100, 50, 150 ),
			$this->create_test_provider_metadata(),
			$this->create_test_text_model_metadata()
		);

		$metadata = $this->createMock( ModelMetadata::class );
		$metadata->method( 'getId' )->willReturn( 'test-model' );

		$model = $this->create_mock_text_generation_model( $result, $metadata );

		$builder = new Prompt_Builder( $this->registry, 'Test prompt' );
		$builder->using_model( $model );

		$actual_result = $builder->generate_text_result();
		$this->assertSame( $result, $actual_result );

		/** @var ModelConfig $config */
		$config = $this->get_wrapped_prompt_builder_property_value( $builder, 'modelConfig' );

		$modalities = $config->getOutputModalities();
		$this->assertNotNull( $modalities );
		$this->assertTrue( $modalities[0]->isText() );
	}

	/**
	 * Tests generateImageResult method.
	 *
	 * @return void
	 */
	public function test_generate_image_result(): void {
		$result = new GenerativeAiResult(
			'test-result',
			array(
				new Candidate(
					new ModelMessage( array( new MessagePart( new File( 'data:image/png;base64,iVBORw0KGgo=', 'image/png' ) ) ) ),
					FinishReasonEnum::stop()
				),
			),
			new TokenUsage( 100, 50, 150 ),
			$this->create_test_provider_metadata(),
			$this->create_test_text_model_metadata()
		);

		$metadata = $this->createMock( ModelMetadata::class );
		$metadata->method( 'getId' )->willReturn( 'test-model' );

		$model = $this->create_mock_image_generation_model( $result, $metadata );

		$builder = new Prompt_Builder( $this->registry, 'Generate image' );
		$builder->using_model( $model );

		$actual_result = $builder->generate_image_result();
		$this->assertSame( $result, $actual_result );

		/** @var ModelConfig $config */
		$config = $this->get_wrapped_prompt_builder_property_value( $builder, 'modelConfig' );

		$modalities = $config->getOutputModalities();
		$this->assertNotNull( $modalities );
		$this->assertTrue( $modalities[0]->isImage() );
	}



	/**
	 * Tests generateText throws exception when no candidates.
	 *
	 * @return void
	 */
	public function test_generate_text_throws_exception_when_no_candidates(): void {
		// Since GenerativeAiResult constructor requires at least one candidate,
		// we need to create a mock that throws an exception or test a different scenario
		$metadata = $this->createMock( ModelMetadata::class );
		$metadata->method( 'getId' )->willReturn( 'test-model' );

		$model = $this->create_mock_text_generation_model_with_exception(
			new RuntimeException( 'No candidates were generated' ),
			$metadata
		);

		$builder = new Prompt_Builder( $this->registry, 'Generate text' );
		$builder->using_model( $model );

		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'No candidates were generated' );

		$builder->generate_text();
	}

	/**
	 * Tests generateText throws exception when message has no parts.
	 *
	 * @return void
	 */
	public function test_generate_text_throws_exception_when_no_parts(): void {
		$message   = new ModelMessage( array() );
		$candidate = new Candidate( $message, FinishReasonEnum::stop() );

		$result = new GenerativeAiResult(
			'test-result',
			array( $candidate ),
			new TokenUsage( 100, 50, 150 ),
			$this->create_test_provider_metadata(),
			$this->create_test_text_model_metadata()
		);

		$metadata = $this->createMock( ModelMetadata::class );
		$metadata->method( 'getId' )->willReturn( 'test-model' );

		$model = $this->create_mock_text_generation_model( $result, $metadata );

		$builder = new Prompt_Builder( $this->registry, 'Generate text' );
		$builder->using_model( $model );

		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'No text content found in first candidate' );

		$builder->generate_text();
	}

	/**
	 * Tests generateText throws exception when part has no text.
	 *
	 * @return void
	 */
	public function test_generate_text_throws_exception_when_part_has_no_text(): void {
		$file         = new File( 'https://example.com/image.jpg', 'image/jpeg' );
		$message_part = new MessagePart( $file );
		$message      = new ModelMessage( array( $message_part ) );
		$candidate    = new Candidate( $message, FinishReasonEnum::stop() );

		$result = new GenerativeAiResult(
			'test-result',
			array( $candidate ),
			new TokenUsage( 100, 50, 150 ),
			$this->create_test_provider_metadata(),
			$this->create_test_text_model_metadata()
		);

		$metadata = $this->createMock( ModelMetadata::class );
		$metadata->method( 'getId' )->willReturn( 'test-model' );

		$model = $this->create_mock_text_generation_model( $result, $metadata );

		$builder = new Prompt_Builder( $this->registry, 'Generate text' );
		$builder->using_model( $model );

		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'No text content found in first candidate' );

		$builder->generate_text();
	}

	/**
	 * Tests generateTexts method.
	 *
	 * @return void
	 */
	public function test_generate_texts(): void {
		$candidates = array(
			new Candidate(
				new ModelMessage( array( new MessagePart( 'Text 1' ) ) ),
				FinishReasonEnum::stop()
			),
			new Candidate(
				new ModelMessage( array( new MessagePart( 'Text 2' ) ) ),
				FinishReasonEnum::stop()
			),
			new Candidate(
				new ModelMessage( array( new MessagePart( 'Text 3' ) ) ),
				FinishReasonEnum::stop()
			),
		);

		$result = new GenerativeAiResult(
			'test-result-id',
			$candidates,
			new TokenUsage( 100, 50, 150 ),
			$this->create_test_provider_metadata(),
			$this->create_test_text_model_metadata()
		);

		$metadata = $this->createMock( ModelMetadata::class );
		$metadata->method( 'getId' )->willReturn( 'test-model' );

		$model = $this->create_mock_text_generation_model( $result, $metadata );

		$builder = new Prompt_Builder( $this->registry, 'Generate texts' );
		$builder->using_model( $model );

		$texts = $builder->generate_texts( 3 );

		$this->assertCount( 3, $texts );
		$this->assertEquals( 'Text 1', $texts[0] );
		$this->assertEquals( 'Text 2', $texts[1] );
		$this->assertEquals( 'Text 3', $texts[2] );

		/** @var ModelConfig $config */
		$config = $this->get_wrapped_prompt_builder_property_value( $builder, 'modelConfig' );

		$this->assertEquals( 3, $config->getCandidateCount() );
	}

	/**
	 * Tests generateTexts throws exception when no text generated.
	 *
	 * @return void
	 */
	public function test_generate_texts_throws_exception_when_no_text_generated(): void {
		$metadata = $this->createMock( ModelMetadata::class );
		$metadata->method( 'getId' )->willReturn( 'test-model' );

		$model = $this->create_mock_text_generation_model_with_exception(
			new RuntimeException( 'No text was generated from any candidates' ),
			$metadata
		);

		$builder = new Prompt_Builder( $this->registry, 'Generate texts' );
		$builder->using_model( $model );

		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'No text was generated from any candidates' );

		$builder->generate_texts();
	}

	/**
	 * Tests generateImage method.
	 *
	 * @return void
	 */
	public function test_generate_image(): void {
		$file         = new File( 'https://example.com/generated.jpg', 'image/jpeg' );
		$message_part = new MessagePart( $file );
		$message      = new ModelMessage( array( $message_part ) );
		$candidate    = new Candidate( $message, FinishReasonEnum::stop() );

		$result = new GenerativeAiResult(
			'test-result',
			array( $candidate ),
			new TokenUsage( 100, 50, 150 ),
			$this->create_test_provider_metadata(),
			$this->create_test_text_model_metadata()
		);

		$metadata = $this->createMock( ModelMetadata::class );
		$metadata->method( 'getId' )->willReturn( 'test-model' );

		$model = $this->create_mock_image_generation_model( $result, $metadata );

		$builder = new Prompt_Builder( $this->registry, 'Generate image' );
		$builder->using_model( $model );

		$generated_file = $builder->generate_image();
		$this->assertSame( $file, $generated_file );
	}

	/**
	 * Tests generateImage throws exception when no image file.
	 *
	 * @return void
	 */
	public function test_generate_image_throws_exception_when_no_file(): void {
		$message_part = new MessagePart( 'Text instead of image' );
		$message      = new ModelMessage( array( $message_part ) );
		$candidate    = new Candidate( $message, FinishReasonEnum::stop() );

		$result = new GenerativeAiResult(
			'test-result',
			array( $candidate ),
			new TokenUsage( 100, 50, 150 ),
			$this->create_test_provider_metadata(),
			$this->create_test_text_model_metadata()
		);

		$metadata = $this->createMock( ModelMetadata::class );
		$metadata->method( 'getId' )->willReturn( 'test-model' );

		$model = $this->create_mock_image_generation_model( $result, $metadata );

		$builder = new Prompt_Builder( $this->registry, 'Generate image' );
		$builder->using_model( $model );

		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'No file content found in first candidate' );

		$builder->generate_image();
	}

	/**
	 * Tests generateImages method.
	 *
	 * @return void
	 */
	public function test_generate_images(): void {
		$files = array(
			new File( 'https://example.com/img1.jpg', 'image/jpeg' ),
			new File( 'https://example.com/img2.jpg', 'image/jpeg' ),
		);

		$candidates = array();
		foreach ( $files as $file ) {
			$candidates[] = new Candidate(
				new Message( MessageRoleEnum::model(), array( new MessagePart( $file ) ) ),
				FinishReasonEnum::stop()
			);
		}

		$result = new GenerativeAiResult(
			'test-result-id',
			$candidates,
			new TokenUsage( 100, 50, 150 ),
			$this->create_test_provider_metadata(),
			$this->create_test_text_model_metadata()
		);

		$metadata = $this->createMock( ModelMetadata::class );
		$metadata->method( 'getId' )->willReturn( 'test-model' );

		$model = $this->create_mock_image_generation_model( $result, $metadata );

		$builder = new Prompt_Builder( $this->registry, 'Generate images' );
		$builder->using_model( $model );

		$generated_files = $builder->generate_images( 2 );

		$this->assertCount( 2, $generated_files );
		$this->assertSame( $files[0], $generated_files[0] );
		$this->assertSame( $files[1], $generated_files[1] );
	}

	/**
	 * Tests convertTextToSpeech method.
	 *
	 * @return void
	 */
	public function test_convert_text_to_speech(): void {
		$file         = new File( 'https://example.com/audio.mp3', 'audio/mp3' );
		$message_part = new MessagePart( $file );
		$message      = new Message( MessageRoleEnum::model(), array( $message_part ) );
		$candidate    = new Candidate( $message, FinishReasonEnum::stop() );

		$result = new GenerativeAiResult(
			'test-result',
			array( $candidate ),
			new TokenUsage( 100, 50, 150 ),
			$this->create_test_provider_metadata(),
			$this->create_test_text_model_metadata()
		);

		$metadata = $this->createMock( ModelMetadata::class );
		$metadata->method( 'getId' )->willReturn( 'test-model' );

		$model = $this->create_mock_text_to_speech_model( $result, $metadata );

		$builder = new Prompt_Builder( $this->registry, 'Convert this text' );
		$builder->using_model( $model );

		$audio_file = $builder->convert_text_to_speech();
		$this->assertSame( $file, $audio_file );
	}

	/**
	 * Tests convertTextToSpeeches method.
	 *
	 * @return void
	 */
	public function test_convert_text_to_speeches(): void {
		$files = array(
			new File( 'https://example.com/audio1.mp3', 'audio/mp3' ),
			new File( 'https://example.com/audio2.mp3', 'audio/mp3' ),
		);

		$candidates = array();
		foreach ( $files as $file ) {
			$candidates[] = new Candidate(
				new Message( MessageRoleEnum::model(), array( new MessagePart( $file ) ) ),
				FinishReasonEnum::stop()
			);
		}

		$result = new GenerativeAiResult(
			'test-result-id',
			$candidates,
			new TokenUsage( 100, 50, 150 ),
			$this->create_test_provider_metadata(),
			$this->create_test_text_model_metadata()
		);

		$metadata = $this->createMock( ModelMetadata::class );
		$metadata->method( 'getId' )->willReturn( 'test-model' );

		$model = $this->create_mock_text_to_speech_model( $result, $metadata );

		$builder = new Prompt_Builder( $this->registry, 'Convert this text' );
		$builder->using_model( $model );

		$audio_files = $builder->convert_text_to_speeches( 2 );

		$this->assertCount( 2, $audio_files );
		$this->assertSame( $files[0], $audio_files[0] );
		$this->assertSame( $files[1], $audio_files[1] );
	}

	/**
	 * Tests generateSpeech method.
	 *
	 * @return void
	 */
	public function test_generate_speech(): void {
		$file         = new File( 'https://example.com/speech.mp3', 'audio/mp3' );
		$message_part = new MessagePart( $file );
		$message      = new Message( MessageRoleEnum::model(), array( $message_part ) );
		$candidate    = new Candidate( $message, FinishReasonEnum::stop() );

		$result = new GenerativeAiResult(
			'test-result',
			array( $candidate ),
			new TokenUsage( 100, 50, 150 ),
			$this->create_test_provider_metadata(),
			$this->create_test_text_model_metadata()
		);

		$metadata = $this->createMock( ModelMetadata::class );
		$metadata->method( 'getId' )->willReturn( 'test-model' );

		$model = $this->create_mock_speech_generation_model( $result, $metadata );

		$builder = new Prompt_Builder( $this->registry, 'Generate speech' );
		$builder->using_model( $model );

		$speech_file = $builder->generate_speech();
		$this->assertSame( $file, $speech_file );
	}

	/**
	 * Tests generateSpeeches method.
	 *
	 * @return void
	 */
	public function test_generate_speeches(): void {
		$files = array(
			new File( 'https://example.com/speech1.mp3', 'audio/mp3' ),
			new File( 'https://example.com/speech2.mp3', 'audio/mp3' ),
			new File( 'https://example.com/speech3.mp3', 'audio/mp3' ),
		);

		$candidates = array();
		foreach ( $files as $file ) {
			$candidates[] = new Candidate(
				new Message( MessageRoleEnum::model(), array( new MessagePart( $file ) ) ),
				FinishReasonEnum::stop(),
				10
			);
		}

		$result = new GenerativeAiResult(
			'test-result-id',
			$candidates,
			new TokenUsage( 100, 50, 150 ),
			$this->create_test_provider_metadata(),
			$this->create_test_text_model_metadata()
		);

		$metadata = $this->createMock( ModelMetadata::class );
		$metadata->method( 'getId' )->willReturn( 'test-model' );

		$model = $this->create_mock_speech_generation_model( $result, $metadata );

		$builder = new Prompt_Builder( $this->registry, 'Generate speech' );
		$builder->using_model( $model );

		$speech_files = $builder->generate_speeches( 3 );

		$this->assertCount( 3, $speech_files );
		$this->assertSame( $files[0], $speech_files[0] );
		$this->assertSame( $files[1], $speech_files[1] );
		$this->assertSame( $files[2], $speech_files[2] );
	}

	/**
	 * Gets the function declarations from the builder's model config.
	 *
	 * @param Prompt_Builder $builder The builder to get declarations from.
	 * @return list<FunctionDeclaration>|null The function declarations or null if not set.
	 */
	private function get_function_declarations( Prompt_Builder $builder ): ?array {
		/** @var ModelConfig $config */
		$config = $this->get_wrapped_prompt_builder_property_value( $builder, 'modelConfig' );
		return $config->getFunctionDeclarations();
	}

	/**
	 * Tests using_ability with ability name string.
	 *
	 * @return void
	 */
	public function test_using_ability_with_string(): void {
		$builder = new Prompt_Builder( $this->registry );
		$result  = $builder->using_abilities( 'wpaiclienttests/simple' );

		$this->assertSame( $builder, $result );

		$declarations = $this->get_function_declarations( $builder );

		$this->assertNotNull( $declarations );
		$this->assertCount( 1, $declarations );
		$this->assertEquals( 'wpab__wpaiclienttests__simple', $declarations[0]->getName() );
		$this->assertEquals( 'A simple test ability with no parameters.', $declarations[0]->getDescription() );
	}

	/**
	 * Tests using_ability with WP_Ability object.
	 *
	 * @return void
	 */
	public function test_using_ability_with_wp_ability_object(): void {
		$ability = wp_get_ability( 'wpaiclienttests/with-params' );

		$builder = new Prompt_Builder( $this->registry );
		$result  = $builder->using_abilities( $ability );

		$this->assertSame( $builder, $result );

		$declarations = $this->get_function_declarations( $builder );

		$this->assertNotNull( $declarations );
		$this->assertCount( 1, $declarations );
		$this->assertEquals( 'wpab__wpaiclienttests__with-params', $declarations[0]->getName() );
		$this->assertEquals( 'A test ability that accepts parameters.', $declarations[0]->getDescription() );

		// Verify input schema is passed through.
		$params = $declarations[0]->getParameters();
		$this->assertNotNull( $params );
		$this->assertArrayHasKey( 'properties', $params );
		$this->assertArrayHasKey( 'title', $params['properties'] );
	}

	/**
	 * Tests using_ability with multiple abilities.
	 *
	 * @return void
	 */
	public function test_using_ability_with_multiple_abilities(): void {
		$builder = new Prompt_Builder( $this->registry );
		$result  = $builder->using_abilities(
			'wpaiclienttests/simple',
			'wpaiclienttests/with-params',
			'wpaiclienttests/returns-error'
		);

		$this->assertSame( $builder, $result );

		$declarations = $this->get_function_declarations( $builder );

		$this->assertNotNull( $declarations );
		$this->assertCount( 3, $declarations );
		$this->assertEquals( 'wpab__wpaiclienttests__simple', $declarations[0]->getName() );
		$this->assertEquals( 'wpab__wpaiclienttests__with-params', $declarations[1]->getName() );
		$this->assertEquals( 'wpab__wpaiclienttests__returns-error', $declarations[2]->getName() );
	}

	/**
	 * Tests using_ability skips non-existent abilities.
	 *
	 * @return void
	 */
	public function test_using_ability_skips_nonexistent_abilities(): void {
		$this->setExpectedIncorrectUsage( 'WP_Abilities_Registry::get_registered' );

		$builder = new Prompt_Builder( $this->registry );
		$result  = $builder->using_abilities(
			'wpaiclienttests/simple',
			'nonexistent/ability',
			'wpaiclienttests/with-params'
		);

		$this->assertSame( $builder, $result );

		$declarations = $this->get_function_declarations( $builder );

		// Only 2 valid abilities should be registered.
		$this->assertNotNull( $declarations );
		$this->assertCount( 2, $declarations );
		$this->assertEquals( 'wpab__wpaiclienttests__simple', $declarations[0]->getName() );
		$this->assertEquals( 'wpab__wpaiclienttests__with-params', $declarations[1]->getName() );
	}

	/**
	 * Tests using_ability with empty arguments returns self.
	 *
	 * @return void
	 */
	public function test_using_ability_with_no_arguments_returns_self(): void {
		$builder = new Prompt_Builder( $this->registry );
		$result  = $builder->using_abilities();

		$this->assertSame( $builder, $result );

		$declarations = $this->get_function_declarations( $builder );

		$this->assertNull( $declarations );
	}

	/**
	 * Tests using_ability with mixed strings and WP_Ability objects.
	 *
	 * @return void
	 */
	public function test_using_ability_with_mixed_types(): void {
		$ability = wp_get_ability( 'wpaiclienttests/with-params' );

		$builder = new Prompt_Builder( $this->registry );
		$result  = $builder->using_abilities(
			'wpaiclienttests/simple',
			$ability
		);

		$this->assertSame( $builder, $result );

		$declarations = $this->get_function_declarations( $builder );

		$this->assertNotNull( $declarations );
		$this->assertCount( 2, $declarations );
		$this->assertEquals( 'wpab__wpaiclienttests__simple', $declarations[0]->getName() );
		$this->assertEquals( 'wpab__wpaiclienttests__with-params', $declarations[1]->getName() );
	}

	/**
	 * Tests using_ability with hyphenated ability name.
	 *
	 * @return void
	 */
	public function test_using_ability_with_hyphenated_name(): void {
		$builder = new Prompt_Builder( $this->registry );
		$result  = $builder->using_abilities( 'wpaiclienttests/hyphen-test' );

		$this->assertSame( $builder, $result );

		$declarations = $this->get_function_declarations( $builder );

		$this->assertNotNull( $declarations );
		$this->assertCount( 1, $declarations );
		$this->assertEquals( 'wpab__wpaiclienttests__hyphen-test', $declarations[0]->getName() );
	}

	/**
	 * Tests using_ability can be chained with other methods.
	 *
	 * @return void
	 */
	public function test_using_ability_method_chaining(): void {
		$builder = new Prompt_Builder( $this->registry );
		$result  = $builder
			->with_text( 'Test prompt' )
			->using_abilities( 'wpaiclienttests/simple' )
			->using_system_instruction( 'You are a helpful assistant' )
			->using_max_tokens( 500 );

		$this->assertSame( $builder, $result );

		$declarations = $this->get_function_declarations( $builder );

		$this->assertNotNull( $declarations );
		$this->assertCount( 1, $declarations );
		$this->assertEquals( 'wpab__wpaiclienttests__simple', $declarations[0]->getName() );

		/** @var ModelConfig $config */
		$config = $this->get_wrapped_prompt_builder_property_value( $builder, 'modelConfig' );

		$this->assertEquals( 'You are a helpful assistant', $config->getSystemInstruction() );
		$this->assertEquals( 500, $config->getMaxTokens() );
	}

	/**
	 * Tests that is_supported returns false when prevent prompt filter returns true.
	 *
	 * @return void
	 */
	public function test_is_supported_returns_false_when_filter_prevents_prompt(): void {
		add_filter( 'wp_ai_client_prevent_prompt', '__return_true' );

		$builder = new Prompt_Builder( AiClient::defaultRegistry(), 'Test prompt' );

		$this->assertFalse( $builder->is_supported() );
	}

	/**
	 * Tests that generate_result throws Prompt_Prevented_Exception when prevent prompt filter returns true.
	 *
	 * @return void
	 */
	public function test_generate_result_throws_exception_when_filter_prevents_prompt(): void {
		add_filter( 'wp_ai_client_prevent_prompt', '__return_true' );

		$builder = new Prompt_Builder( AiClient::defaultRegistry(), 'Test prompt' );

		$this->expectException( Prompt_Prevented_Exception::class );
		$this->expectExceptionMessage( 'Prompt execution was prevented by a filter.' );

		$builder->generate_result();
	}

	/**
	 * Tests that prevent prompt filter receives a clone of the builder instance.
	 *
	 * @return void
	 */
	public function test_prevent_prompt_filter_receives_cloned_builder_instance(): void {
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

		$builder = new Prompt_Builder( AiClient::defaultRegistry(), 'Test prompt' );
		$builder->is_supported();

		$this->assertNotSame( $builder, $captured_builder, 'Filter should receive a clone, not the same instance' );
		$this->assertInstanceOf( Prompt_Builder::class, $captured_builder );
	}
}
