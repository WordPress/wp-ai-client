<?php
/**
 * Tests for WordPress\AI_Client\Builders\Helpers\Ability_Function_Resolver
 *
 * @package WordPress\AI_Client
 */

namespace WordPress\AI_Client\PHPUnit\Tests\Abilities;

use WordPress\AI_Client\Builders\Helpers\Ability_Function_Resolver;
use WordPress\AI_Client\PHPUnit\Includes\Test_Case;
use WordPress\AiClient\Messages\DTO\Message;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Messages\DTO\ModelMessage;
use WordPress\AiClient\Messages\DTO\UserMessage;
use WordPress\AiClient\Messages\Enums\MessageRoleEnum;
use WordPress\AiClient\Tools\DTO\FunctionCall;
use WordPress\AiClient\Tools\DTO\FunctionResponse;
use WP_Ability;
use WP_Error;

/**
 * Test class for Ability_Function_Resolver.
 */
class Ability_Function_Resolver_Test extends Test_Case {

	/**
	 * The resolver instance.
	 *
	 * @var Ability_Function_Resolver
	 */
	private Ability_Function_Resolver $resolver;

	/**
	 * Sets up test fixtures.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->resolver = new Ability_Function_Resolver();
	}

	/**
	 * Checks if WordPress Abilities API is available.
	 *
	 * @return bool True if the Abilities API is available, false otherwise.
	 */
	private function is_abilities_api_available(): bool {
		return function_exists( 'wp_register_ability' ) && function_exists( 'wp_get_ability' ) && class_exists( 'WP_Ability' );
	}

	/**
	 * Tests is_ability_call returns true for valid ability calls.
	 *
	 * @return void
	 */
	public function test_is_ability_call_returns_true_for_valid_ability(): void {
		$call = new FunctionCall( 'func_123', 'wpab__tec__create_event', array( 'title' => 'Test Event' ) );
		$this->assertTrue( $this->resolver->is_ability_call( $call ) );
	}

	/**
	 * Tests is_ability_call returns true for nested namespace abilities.
	 *
	 * @return void
	 */
	public function test_is_ability_call_returns_true_for_nested_namespace(): void {
		$call = new FunctionCall( 'func_456', 'wpab__tec__v1__create_event', array() );
		$this->assertTrue( $this->resolver->is_ability_call( $call ) );
	}

	/**
	 * Tests is_ability_call returns false for non-ability calls.
	 *
	 * @return void
	 */
	public function test_is_ability_call_returns_false_for_non_ability(): void {
		$call = new FunctionCall( 'func_789', 'regular_function', array() );
		$this->assertFalse( $this->resolver->is_ability_call( $call ) );
	}

	/**
	 * Tests is_ability_call returns false when function name is null.
	 *
	 * @return void
	 */
	public function test_is_ability_call_returns_false_when_name_is_null(): void {
		$call = new FunctionCall( 'func_999', null, array() );
		$this->assertFalse( $this->resolver->is_ability_call( $call ) );
	}

	/**
	 * Tests is_ability_call returns false for partial prefix match.
	 *
	 * @return void
	 */
	public function test_is_ability_call_returns_false_for_partial_prefix(): void {
		$call = new FunctionCall( 'func_111', 'wpab_single_underscore', array() );
		$this->assertFalse( $this->resolver->is_ability_call( $call ) );
	}

	/**
	 * Tests execute_ability returns error for non-ability call.
	 *
	 * @return void
	 */
	public function test_execute_ability_returns_error_for_non_ability_call(): void {
		$call     = new FunctionCall( 'func_123', 'regular_function', array() );
		$response = $this->resolver->execute_ability( $call );

		$this->assertInstanceOf( FunctionResponse::class, $response );
		$this->assertEquals( 'func_123', $response->getId() );
		$this->assertEquals( 'regular_function', $response->getName() );

		$result = $response->getResponse();
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'error', $result );
		$this->assertEquals( 'Not an ability function call', $result['error'] );
		$this->assertEquals( 'invalid_ability_call', $result['code'] );
	}

	/**
	 * Tests execute_ability returns error when ability not found.
	 *
	 * @return void
	 */
	public function test_execute_ability_returns_error_when_ability_not_found(): void {
		if ( ! $this->is_abilities_api_available() ) {
			$this->markTestSkipped( 'WordPress Abilities API is not available (requires WordPress 6.9+)' );
		}

		$call     = new FunctionCall( 'func_456', 'wpab__nonexistent__ability', array() );
		$response = $this->resolver->execute_ability( $call );

		$this->assertInstanceOf( FunctionResponse::class, $response );
		$this->assertEquals( 'func_456', $response->getId() );
		$this->assertEquals( 'wpab__nonexistent__ability', $response->getName() );

		$result = $response->getResponse();
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'error', $result );
		$this->assertStringContainsString( 'not found', $result['error'] );
		$this->assertEquals( 'ability_not_found', $result['code'] );
	}

	/**
	 * Tests execute_ability with successful execution.
	 *
	 * @return void
	 */
	public function test_execute_ability_successful_execution(): void {
		if ( ! $this->is_abilities_api_available() ) {
			$this->markTestSkipped( 'WordPress Abilities API is not available (requires WordPress 6.9+)' );
		}

		// Register a test ability.
		wp_register_ability(
			'test/hello',
			array(
				'label'               => 'Test Hello Ability',
				'description'         => 'Says hello',
				'category'            => 'general',
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'name' => array( 'type' => 'string' ),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'message' => array( 'type' => 'string' ),
					),
				),
				'execute_callback'    => function ( $input ) {
					return array( 'message' => 'Hello ' . $input['name'] );
				},
				'permission_callback' => '__return_true',
			)
		);

		$call     = new FunctionCall( 'func_789', 'wpab__test__hello', array( 'name' => 'World' ) );
		$response = $this->resolver->execute_ability( $call );

		$this->assertInstanceOf( FunctionResponse::class, $response );
		$this->assertEquals( 'func_789', $response->getId() );
		$this->assertEquals( 'wpab__test__hello', $response->getName() );

		$result = $response->getResponse();
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'message', $result );
		$this->assertEquals( 'Hello World', $result['message'] );
	}

	/**
	 * Tests execute_ability with WP_Error response.
	 *
	 * @return void
	 */
	public function test_execute_ability_handles_wp_error(): void {
		if ( ! $this->is_abilities_api_available() ) {
			$this->markTestSkipped( 'WordPress Abilities API is not available (requires WordPress 6.9+)' );
		}

		// Register an ability that returns WP_Error.
		wp_register_ability(
			'test/error',
			array(
				'label'               => 'Test Error Ability',
				'description'         => 'Returns an error',
				'category'            => 'general',
				'input_schema'        => array( 'type' => 'object' ),
				'output_schema'       => array( 'type' => 'object' ),
				'execute_callback'    => function () {
					return new WP_Error( 'test_error', 'This is a test error', array( 'extra' => 'data' ) );
				},
				'permission_callback' => '__return_true',
			)
		);

		$call     = new FunctionCall( 'func_999', 'wpab__test__error', array() );
		$response = $this->resolver->execute_ability( $call );

		$this->assertInstanceOf( FunctionResponse::class, $response );

		$result = $response->getResponse();
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'error', $result );
		$this->assertEquals( 'This is a test error', $result['error'] );
		$this->assertEquals( 'test_error', $result['code'] );
		$this->assertArrayHasKey( 'data', $result );
		$this->assertEquals( array( 'extra' => 'data' ), $result['data'] );
	}

	/**
	 * Tests execute_ability with nested namespace.
	 *
	 * @return void
	 */
	public function test_execute_ability_with_nested_namespace(): void {
		if ( ! $this->is_abilities_api_available() ) {
			$this->markTestSkipped( 'WordPress Abilities API is not available (requires WordPress 6.9+)' );
		}

		// Register an ability with nested namespace.
		wp_register_ability(
			'test/v1/nested',
			array(
				'label'               => 'Test Nested Ability',
				'description'         => 'Nested namespace test',
				'category'            => 'general',
				'input_schema'        => array( 'type' => 'object' ),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'result' => array( 'type' => 'string' ),
					),
				),
				'execute_callback'    => function () {
					return array( 'result' => 'nested success' );
				},
				'permission_callback' => '__return_true',
			)
		);

		$call     = new FunctionCall( 'func_nested', 'wpab__test__v1__nested', array() );
		$response = $this->resolver->execute_ability( $call );

		$this->assertInstanceOf( FunctionResponse::class, $response );

		$result = $response->getResponse();
		$this->assertIsArray( $result );
		$this->assertEquals( 'nested success', $result['result'] );
	}

	/**
	 * Tests execute_ability handles missing function ID.
	 *
	 * @return void
	 */
	public function test_execute_ability_handles_missing_id(): void {
		if ( ! $this->is_abilities_api_available() ) {
			$this->markTestSkipped( 'WordPress Abilities API is not available (requires WordPress 6.9+)' );
		}

		$call     = new FunctionCall( null, 'wpab__test__missing', array() );
		$response = $this->resolver->execute_ability( $call );

		$this->assertInstanceOf( FunctionResponse::class, $response );
		$this->assertEquals( 'unknown', $response->getId() );
	}

	/**
	 * Tests has_ability_calls returns true when message contains ability calls.
	 *
	 * @return void
	 */
	public function test_has_ability_calls_returns_true_when_present(): void {
		$function_call = new FunctionCall( 'func_123', 'wpab__tec__create_event', array() );
		$parts         = array(
			new MessagePart( 'Some text' ),
			new MessagePart( $function_call ),
		);
		$message       = new ModelMessage( $parts );

		$this->assertTrue( $this->resolver->has_ability_calls( $message ) );
	}

	/**
	 * Tests has_ability_calls returns false when no ability calls present.
	 *
	 * @return void
	 */
	public function test_has_ability_calls_returns_false_when_not_present(): void {
		$function_call = new FunctionCall( 'func_456', 'regular_function', array() );
		$parts         = array(
			new MessagePart( 'Some text' ),
			new MessagePart( $function_call ),
		);
		$message       = new ModelMessage( $parts );

		$this->assertFalse( $this->resolver->has_ability_calls( $message ) );
	}

	/**
	 * Tests has_ability_calls returns false for text-only message.
	 *
	 * @return void
	 */
	public function test_has_ability_calls_returns_false_for_text_only(): void {
		$parts   = array( new MessagePart( 'Just text' ) );
		$message = new UserMessage( $parts );

		$this->assertFalse( $this->resolver->has_ability_calls( $message ) );
	}

	/**
	 * Tests has_ability_calls returns true with mixed content.
	 *
	 * @return void
	 */
	public function test_has_ability_calls_returns_true_with_mixed_content(): void {
		$regular_call = new FunctionCall( 'func_1', 'regular_function', array() );
		$ability_call = new FunctionCall( 'func_2', 'wpab__test__ability', array() );
		$parts        = array(
			new MessagePart( 'Text' ),
			new MessagePart( $regular_call ),
			new MessagePart( $ability_call ),
		);
		$message      = new ModelMessage( $parts );

		$this->assertTrue( $this->resolver->has_ability_calls( $message ) );
	}

	/**
	 * Tests has_ability_calls with empty message.
	 *
	 * @return void
	 */
	public function test_has_ability_calls_with_empty_message(): void {
		$message = new ModelMessage( array() );
		$this->assertFalse( $this->resolver->has_ability_calls( $message ) );
	}

	/**
	 * Tests execute_abilities processes all function calls.
	 *
	 * @return void
	 */
	public function test_execute_abilities_processes_all_calls(): void {
		if ( ! $this->is_abilities_api_available() ) {
			$this->markTestSkipped( 'WordPress Abilities API is not available (requires WordPress 6.9+)' );
		}

		// Register test abilities.
		wp_register_ability(
			'test/one',
			array(
				'label'               => 'Test One',
				'description'         => 'First test',
				'category'            => 'general',
				'input_schema'        => array( 'type' => 'object' ),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'value' => array( 'type' => 'string' ),
					),
				),
				'execute_callback'    => function () {
					return array( 'value' => 'one' );
				},
				'permission_callback' => '__return_true',
			)
		);

		wp_register_ability(
			'test/two',
			array(
				'label'               => 'Test Two',
				'description'         => 'Second test',
				'category'            => 'general',
				'input_schema'        => array( 'type' => 'object' ),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'value' => array( 'type' => 'string' ),
					),
				),
				'execute_callback'    => function () {
					return array( 'value' => 'two' );
				},
				'permission_callback' => '__return_true',
			)
		);

		$call1   = new FunctionCall( 'func_1', 'wpab__test__one', array() );
		$call2   = new FunctionCall( 'func_2', 'wpab__test__two', array() );
		$parts   = array(
			new MessagePart( $call1 ),
			new MessagePart( $call2 ),
		);
		$message = new ModelMessage( $parts );

		$result = $this->resolver->execute_abilities( $message );

		$this->assertInstanceOf( Message::class, $result );
		$this->assertTrue( $result->getRole()->isModel() );

		$result_parts = $result->getParts();
		$this->assertCount( 2, $result_parts );

		$response1 = $result_parts[0]->getFunctionResponse();
		$this->assertInstanceOf( FunctionResponse::class, $response1 );
		$this->assertEquals( 'func_1', $response1->getId() );
		$this->assertEquals( array( 'value' => 'one' ), $response1->getResponse() );

		$response2 = $result_parts[1]->getFunctionResponse();
		$this->assertInstanceOf( FunctionResponse::class, $response2 );
		$this->assertEquals( 'func_2', $response2->getId() );
		$this->assertEquals( array( 'value' => 'two' ), $response2->getResponse() );
	}

	/**
	 * Tests execute_abilities returns ModelMessage.
	 *
	 * @return void
	 */
	public function test_execute_abilities_returns_model_message(): void {
		if ( ! $this->is_abilities_api_available() ) {
			$this->markTestSkipped( 'WordPress Abilities API is not available (requires WordPress 6.9+)' );
		}

		wp_register_ability(
			'test/msg',
			array(
				'label'               => 'Test Message',
				'description'         => 'Test',
				'category'            => 'general',
				'input_schema'        => array( 'type' => 'object' ),
				'output_schema'       => array( 'type' => 'object' ),
				'execute_callback'    => function () {
					return array( 'ok' => true );
				},
				'permission_callback' => '__return_true',
			)
		);

		$call    = new FunctionCall( 'func_1', 'wpab__test__msg', array() );
		$parts   = array( new MessagePart( $call ) );
		$message = new ModelMessage( $parts );

		$result = $this->resolver->execute_abilities( $message );

		$this->assertInstanceOf( ModelMessage::class, $result );
	}

	/**
	 * Tests execute_abilities with empty message.
	 *
	 * @return void
	 */
	public function test_execute_abilities_with_empty_message(): void {
		$message = new ModelMessage( array() );
		$result  = $this->resolver->execute_abilities( $message );

		$this->assertInstanceOf( Message::class, $result );
		$this->assertCount( 0, $result->getParts() );
	}

	/**
	 * Tests execute_abilities handles errors gracefully.
	 *
	 * @return void
	 */
	public function test_execute_abilities_handles_errors_gracefully(): void {
		if ( ! $this->is_abilities_api_available() ) {
			$this->markTestSkipped( 'WordPress Abilities API is not available (requires WordPress 6.9+)' );
		}

		$call    = new FunctionCall( 'func_1', 'wpab__nonexistent__ability', array() );
		$parts   = array( new MessagePart( $call ) );
		$message = new ModelMessage( $parts );

		$result = $this->resolver->execute_abilities( $message );

		$this->assertInstanceOf( Message::class, $result );

		$result_parts = $result->getParts();
		$this->assertCount( 1, $result_parts );

		$response = $result_parts[0]->getFunctionResponse();
		$this->assertInstanceOf( FunctionResponse::class, $response );

		$response_data = $response->getResponse();
		$this->assertArrayHasKey( 'error', $response_data );
		$this->assertEquals( 'ability_not_found', $response_data['code'] );
	}

	/**
	 * Tests execute_abilities with mixed content including non-function parts.
	 *
	 * @return void
	 */
	public function test_execute_abilities_with_mixed_content(): void {
		if ( ! $this->is_abilities_api_available() ) {
			$this->markTestSkipped( 'WordPress Abilities API is not available (requires WordPress 6.9+)' );
		}

		wp_register_ability(
			'test/mixed',
			array(
				'label'               => 'Test Mixed',
				'description'         => 'Mixed content test',
				'category'            => 'general',
				'input_schema'        => array( 'type' => 'object' ),
				'output_schema'       => array( 'type' => 'object' ),
				'execute_callback'    => function () {
					return array( 'result' => 'mixed' );
				},
				'permission_callback' => '__return_true',
			)
		);

		$call    = new FunctionCall( 'func_1', 'wpab__test__mixed', array() );
		$parts   = array(
			new MessagePart( 'Text before' ),
			new MessagePart( $call ),
			new MessagePart( 'Text after' ),
		);
		$message = new ModelMessage( $parts );

		$result = $this->resolver->execute_abilities( $message );

		// Only function calls are processed, not text parts.
		$result_parts = $result->getParts();
		$this->assertCount( 1, $result_parts );
		$this->assertInstanceOf( FunctionResponse::class, $result_parts[0]->getFunctionResponse() );
	}

	/**
	 * Tests namespace transformation: simple namespace.
	 *
	 * @return void
	 */
	public function test_namespace_transformation_simple(): void {
		if ( ! $this->is_abilities_api_available() ) {
			$this->markTestSkipped( 'WordPress Abilities API is not available (requires WordPress 6.9+)' );
		}

		wp_register_ability(
			'plugin/action',
			array(
				'label'               => 'Test Action',
				'description'         => 'Test namespace transformation',
				'category'            => 'general',
				'input_schema'        => array( 'type' => 'object' ),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'namespace' => array( 'type' => 'string' ),
					),
				),
				'execute_callback'    => function () {
					return array( 'namespace' => 'plugin/action' );
				},
				'permission_callback' => '__return_true',
			)
		);

		$call     = new FunctionCall( 'func_1', 'wpab__plugin__action', array() );
		$response = $this->resolver->execute_ability( $call );

		$result = $response->getResponse();
		$this->assertEquals( 'plugin/action', $result['namespace'] );
	}

	/**
	 * Tests namespace transformation: deeply nested namespace.
	 *
	 * @return void
	 */
	public function test_namespace_transformation_deeply_nested(): void {
		if ( ! $this->is_abilities_api_available() ) {
			$this->markTestSkipped( 'WordPress Abilities API is not available (requires WordPress 6.9+)' );
		}

		wp_register_ability(
			'org/plugin/v2/feature/action',
			array(
				'label'               => 'Test Deeply Nested',
				'description'         => 'Test deeply nested namespace',
				'category'            => 'general',
				'input_schema'        => array( 'type' => 'object' ),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'depth' => array( 'type' => 'integer' ),
					),
				),
				'execute_callback'    => function () {
					return array( 'depth' => 5 );
				},
				'permission_callback' => '__return_true',
			)
		);

		$call     = new FunctionCall( 'func_1', 'wpab__org__plugin__v2__feature__action', array() );
		$response = $this->resolver->execute_ability( $call );

		$result = $response->getResponse();
		$this->assertEquals( 5, $result['depth'] );
	}
}
