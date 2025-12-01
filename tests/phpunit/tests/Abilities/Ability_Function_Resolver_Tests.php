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
use WordPress\AiClient\Tools\DTO\FunctionCall;
use WordPress\AiClient\Tools\DTO\FunctionResponse;

/**
 * Test class for Ability_Function_Resolver.
 *
 * Note: Tests that require actual ability registration are not included here
 * because WordPress 6.9 requires abilities to be registered during specific
 * hooks (wp_abilities_api_categories_init and wp_abilities_api_init) which
 * fire during WordPress bootstrap. Integration tests with registered abilities
 * would need to be set up via a bootstrap file.
 */
class Ability_Function_Resolver_Test extends Test_Case {

	/**
	 * Tests is_ability_call returns true for valid ability calls.
	 *
	 * @return void
	 */
	public function test_is_ability_call_returns_true_for_valid_ability(): void {
		$call = new FunctionCall( 'func_123', 'wpab__tec__create_event', array( 'title' => 'Test Event' ) );
		$this->assertTrue( Ability_Function_Resolver::is_ability_call( $call ) );
	}

	/**
	 * Tests is_ability_call returns true for nested namespace abilities.
	 *
	 * @return void
	 */
	public function test_is_ability_call_returns_true_for_nested_namespace(): void {
		$call = new FunctionCall( 'func_456', 'wpab__tec__v1__create_event', array() );
		$this->assertTrue( Ability_Function_Resolver::is_ability_call( $call ) );
	}

	/**
	 * Tests is_ability_call returns false for non-ability calls.
	 *
	 * @return void
	 */
	public function test_is_ability_call_returns_false_for_non_ability(): void {
		$call = new FunctionCall( 'func_789', 'regular_function', array() );
		$this->assertFalse( Ability_Function_Resolver::is_ability_call( $call ) );
	}

	/**
	 * Tests is_ability_call returns false when function name is null.
	 *
	 * @return void
	 */
	public function test_is_ability_call_returns_false_when_name_is_null(): void {
		$call = new FunctionCall( 'func_999', null, array() );
		$this->assertFalse( Ability_Function_Resolver::is_ability_call( $call ) );
	}

	/**
	 * Tests is_ability_call returns false for partial prefix match.
	 *
	 * @return void
	 */
	public function test_is_ability_call_returns_false_for_partial_prefix(): void {
		$call = new FunctionCall( 'func_111', 'wpab_single_underscore', array() );
		$this->assertFalse( Ability_Function_Resolver::is_ability_call( $call ) );
	}

	/**
	 * Tests execute_ability returns error for non-ability call.
	 *
	 * @return void
	 */
	public function test_execute_ability_returns_error_for_non_ability_call(): void {
		$call     = new FunctionCall( 'func_123', 'regular_function', array() );
		$response = Ability_Function_Resolver::execute_ability( $call );

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
		// WordPress 6.9 triggers an incorrect usage notice when ability is not found.
		$this->setExpectedIncorrectUsage( 'WP_Abilities_Registry::get_registered' );

		$call     = new FunctionCall( 'func_456', 'wpab__nonexistent__ability', array() );
		$response = Ability_Function_Resolver::execute_ability( $call );

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
	 * Tests execute_ability handles missing function ID.
	 *
	 * @return void
	 */
	public function test_execute_ability_handles_missing_id(): void {
		// WordPress 6.9 triggers an incorrect usage notice when ability is not found.
		$this->setExpectedIncorrectUsage( 'WP_Abilities_Registry::get_registered' );

		$call     = new FunctionCall( null, 'wpab__test__missing', array() );
		$response = Ability_Function_Resolver::execute_ability( $call );

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

		$this->assertTrue( Ability_Function_Resolver::has_ability_calls( $message ) );
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

		$this->assertFalse( Ability_Function_Resolver::has_ability_calls( $message ) );
	}

	/**
	 * Tests has_ability_calls returns false for text-only message.
	 *
	 * @return void
	 */
	public function test_has_ability_calls_returns_false_for_text_only(): void {
		$parts   = array( new MessagePart( 'Just text' ) );
		$message = new UserMessage( $parts );

		$this->assertFalse( Ability_Function_Resolver::has_ability_calls( $message ) );
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

		$this->assertTrue( Ability_Function_Resolver::has_ability_calls( $message ) );
	}

	/**
	 * Tests has_ability_calls with empty message.
	 *
	 * @return void
	 */
	public function test_has_ability_calls_with_empty_message(): void {
		$message = new ModelMessage( array() );
		$this->assertFalse( Ability_Function_Resolver::has_ability_calls( $message ) );
	}

	/**
	 * Tests execute_abilities with empty message.
	 *
	 * @return void
	 */
	public function test_execute_abilities_with_empty_message(): void {
		$message = new ModelMessage( array() );
		$result  = Ability_Function_Resolver::execute_abilities( $message );

		$this->assertInstanceOf( Message::class, $result );
		$this->assertCount( 0, $result->getParts() );
	}

	/**
	 * Tests execute_abilities handles errors gracefully when ability not found.
	 *
	 * @return void
	 */
	public function test_execute_abilities_handles_errors_gracefully(): void {
		// WordPress 6.9 triggers an incorrect usage notice when ability is not found.
		$this->setExpectedIncorrectUsage( 'WP_Abilities_Registry::get_registered' );

		$call    = new FunctionCall( 'func_1', 'wpab__nonexistent__ability', array() );
		$parts   = array( new MessagePart( $call ) );
		$message = new ModelMessage( $parts );

		$result = Ability_Function_Resolver::execute_abilities( $message );

		$this->assertInstanceOf( Message::class, $result );
		$this->assertInstanceOf( UserMessage::class, $result );

		$result_parts = $result->getParts();
		$this->assertCount( 1, $result_parts );

		$response = $result_parts[0]->getFunctionResponse();
		$this->assertInstanceOf( FunctionResponse::class, $response );

		$response_data = $response->getResponse();
		$this->assertArrayHasKey( 'error', $response_data );
		$this->assertEquals( 'ability_not_found', $response_data['code'] );
	}

	/**
	 * Tests execute_abilities returns UserMessage.
	 *
	 * @return void
	 */
	public function test_execute_abilities_returns_user_message(): void {
		// WordPress 6.9 triggers an incorrect usage notice when ability is not found.
		$this->setExpectedIncorrectUsage( 'WP_Abilities_Registry::get_registered' );

		$call    = new FunctionCall( 'func_1', 'wpab__test__ability', array() );
		$parts   = array( new MessagePart( $call ) );
		$message = new ModelMessage( $parts );

		$result = Ability_Function_Resolver::execute_abilities( $message );

		$this->assertInstanceOf( UserMessage::class, $result );
	}

	/**
	 * Tests execute_abilities processes multiple function calls.
	 *
	 * @return void
	 */
	public function test_execute_abilities_processes_multiple_calls(): void {
		// WordPress 6.9 triggers incorrect usage notices for each ability not found.
		$this->setExpectedIncorrectUsage( 'WP_Abilities_Registry::get_registered' );

		$call1   = new FunctionCall( 'func_1', 'wpab__test__one', array() );
		$call2   = new FunctionCall( 'func_2', 'wpab__test__two', array() );
		$parts   = array(
			new MessagePart( $call1 ),
			new MessagePart( $call2 ),
		);
		$message = new ModelMessage( $parts );

		$result = Ability_Function_Resolver::execute_abilities( $message );

		$this->assertInstanceOf( Message::class, $result );

		$result_parts = $result->getParts();
		$this->assertCount( 2, $result_parts );

		// Both should be FunctionResponse objects (with errors since abilities aren't registered).
		$response1 = $result_parts[0]->getFunctionResponse();
		$this->assertInstanceOf( FunctionResponse::class, $response1 );
		$this->assertEquals( 'func_1', $response1->getId() );

		$response2 = $result_parts[1]->getFunctionResponse();
		$this->assertInstanceOf( FunctionResponse::class, $response2 );
		$this->assertEquals( 'func_2', $response2->getId() );
	}

	/**
	 * Tests execute_abilities only processes function call parts.
	 *
	 * @return void
	 */
	public function test_execute_abilities_only_processes_function_calls(): void {
		// WordPress 6.9 triggers an incorrect usage notice when ability is not found.
		$this->setExpectedIncorrectUsage( 'WP_Abilities_Registry::get_registered' );

		$call    = new FunctionCall( 'func_1', 'wpab__test__ability', array() );
		$parts   = array(
			new MessagePart( 'Text before' ),
			new MessagePart( $call ),
			new MessagePart( 'Text after' ),
		);
		$message = new ModelMessage( $parts );

		$result = Ability_Function_Resolver::execute_abilities( $message );

		// Only function calls are processed, not text parts.
		$result_parts = $result->getParts();
		$this->assertCount( 1, $result_parts );
		$this->assertInstanceOf( FunctionResponse::class, $result_parts[0]->getFunctionResponse() );
	}

	/**
	 * Tests ability_name_to_function_name converts simple names.
	 *
	 * @return void
	 */
	public function test_ability_name_to_function_name_simple(): void {
		$result = Ability_Function_Resolver::ability_name_to_function_name( 'tec/create_event' );
		$this->assertEquals( 'wpab__tec__create_event', $result );
	}

	/**
	 * Tests ability_name_to_function_name converts nested namespaces.
	 *
	 * @return void
	 */
	public function test_ability_name_to_function_name_nested(): void {
		$result = Ability_Function_Resolver::ability_name_to_function_name( 'tec/v1/create_event' );
		$this->assertEquals( 'wpab__tec__v1__create_event', $result );
	}
}
