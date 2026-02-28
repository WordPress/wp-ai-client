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
 * Test abilities are registered during bootstrap in tests/phpunit/bootstrap.php:
 * - wpaiclienttests/simple: No parameters, returns { success: true }
 * - wpaiclienttests/with-params: Accepts title parameter, returns { success: true, title: ... }
 * - wpaiclienttests/returns-error: Always returns a WP_Error
 * - wpaiclienttests/hyphen-test: Tests hyphenated names
 */
class Ability_Function_Resolver_Test extends Test_Case {

	/**
	 * Tests is_ability_call returns true for valid ability calls via instance.
	 *
	 * @return void
	 */
	public function test_is_ability_call_returns_true_for_valid_ability(): void {
		$resolver = new Ability_Function_Resolver( 'tec/create_event' );
		$call     = new FunctionCall( 'func_123', 'wpab__tec__create_event', array( 'title' => 'Test Event' ) );
		$this->assertTrue( $resolver->is_ability_call( $call ) );
	}

	/**
	 * Tests is_ability_call returns true for nested namespace abilities via instance.
	 *
	 * @return void
	 */
	public function test_is_ability_call_returns_true_for_nested_namespace(): void {
		$resolver = new Ability_Function_Resolver( 'tec/v1/create_event' );
		$call     = new FunctionCall( 'func_456', 'wpab__tec__v1__create_event', array() );
		$this->assertTrue( $resolver->is_ability_call( $call ) );
	}

	/**
	 * Tests is_ability_call returns false for non-ability calls via instance.
	 *
	 * @return void
	 */
	public function test_is_ability_call_returns_false_for_non_ability(): void {
		$resolver = new Ability_Function_Resolver();
		$call     = new FunctionCall( 'func_789', 'regular_function', array() );
		$this->assertFalse( $resolver->is_ability_call( $call ) );
	}

	/**
	 * Tests is_ability_call returns false when function name is null via instance.
	 *
	 * @return void
	 */
	public function test_is_ability_call_returns_false_when_name_is_null(): void {
		$resolver = new Ability_Function_Resolver();
		$call     = new FunctionCall( 'func_999', null, array() );
		$this->assertFalse( $resolver->is_ability_call( $call ) );
	}

	/**
	 * Tests is_ability_call returns false for partial prefix match via instance.
	 *
	 * @return void
	 */
	public function test_is_ability_call_returns_false_for_partial_prefix(): void {
		$resolver = new Ability_Function_Resolver();
		$call     = new FunctionCall( 'func_111', 'wpab_single_underscore', array() );
		$this->assertFalse( $resolver->is_ability_call( $call ) );
	}

	/**
	 * Tests execute_ability returns error for non-ability call via instance.
	 *
	 * @return void
	 */
	public function test_execute_ability_returns_error_for_non_ability_call(): void {
		$resolver = new Ability_Function_Resolver();
		$call     = new FunctionCall( 'func_123', 'regular_function', array() );
		$response = $resolver->execute_ability( $call );

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
	 * Tests execute_ability returns error when ability not found via instance.
	 *
	 * @return void
	 */
	public function test_execute_ability_returns_error_when_ability_not_found(): void {
		$this->setExpectedIncorrectUsage( 'WP_Abilities_Registry::get_registered' );

		$resolver = new Ability_Function_Resolver( 'nonexistent/ability' );
		$call     = new FunctionCall( 'func_456', 'wpab__nonexistent__ability', array() );
		$response = $resolver->execute_ability( $call );

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
	 * Tests execute_ability handles missing function ID via instance.
	 *
	 * @return void
	 */
	public function test_execute_ability_handles_missing_id(): void {
		$this->setExpectedIncorrectUsage( 'WP_Abilities_Registry::get_registered' );

		$resolver = new Ability_Function_Resolver( 'test/missing' );
		$call     = new FunctionCall( null, 'wpab__test__missing', array() );
		$response = $resolver->execute_ability( $call );

		$this->assertInstanceOf( FunctionResponse::class, $response );
		$this->assertEquals( 'unknown', $response->getId() );
	}

	/**
	 * Tests has_ability_calls returns true when message contains ability calls via instance.
	 *
	 * @return void
	 */
	public function test_has_ability_calls_returns_true_when_present(): void {
		$resolver      = new Ability_Function_Resolver( 'tec/create_event' );
		$function_call = new FunctionCall( 'func_123', 'wpab__tec__create_event', array() );
		$parts         = array(
			new MessagePart( 'Some text' ),
			new MessagePart( $function_call ),
		);
		$message       = new ModelMessage( $parts );

		$this->assertTrue( $resolver->has_ability_calls( $message ) );
	}

	/**
	 * Tests has_ability_calls returns false when no ability calls present via instance.
	 *
	 * @return void
	 */
	public function test_has_ability_calls_returns_false_when_not_present(): void {
		$resolver      = new Ability_Function_Resolver();
		$function_call = new FunctionCall( 'func_456', 'regular_function', array() );
		$parts         = array(
			new MessagePart( 'Some text' ),
			new MessagePart( $function_call ),
		);
		$message       = new ModelMessage( $parts );

		$this->assertFalse( $resolver->has_ability_calls( $message ) );
	}

	/**
	 * Tests has_ability_calls returns false for text-only message via instance.
	 *
	 * @return void
	 */
	public function test_has_ability_calls_returns_false_for_text_only(): void {
		$resolver = new Ability_Function_Resolver();
		$parts    = array( new MessagePart( 'Just text' ) );
		$message  = new UserMessage( $parts );

		$this->assertFalse( $resolver->has_ability_calls( $message ) );
	}

	/**
	 * Tests has_ability_calls returns true with mixed content via instance.
	 *
	 * @return void
	 */
	public function test_has_ability_calls_returns_true_with_mixed_content(): void {
		$resolver     = new Ability_Function_Resolver( 'test/ability' );
		$regular_call = new FunctionCall( 'func_1', 'regular_function', array() );
		$ability_call = new FunctionCall( 'func_2', 'wpab__test__ability', array() );
		$parts        = array(
			new MessagePart( 'Text' ),
			new MessagePart( $regular_call ),
			new MessagePart( $ability_call ),
		);
		$message      = new ModelMessage( $parts );

		$this->assertTrue( $resolver->has_ability_calls( $message ) );
	}

	/**
	 * Tests has_ability_calls with empty message via instance.
	 *
	 * @return void
	 */
	public function test_has_ability_calls_with_empty_message(): void {
		$resolver = new Ability_Function_Resolver();
		$message  = new ModelMessage( array() );
		$this->assertFalse( $resolver->has_ability_calls( $message ) );
	}

	/**
	 * Tests execute_abilities with empty message via instance.
	 *
	 * @return void
	 */
	public function test_execute_abilities_with_empty_message(): void {
		$resolver = new Ability_Function_Resolver();
		$message  = new ModelMessage( array() );
		$result   = $resolver->execute_abilities( $message );

		$this->assertInstanceOf( Message::class, $result );
		$this->assertCount( 0, $result->getParts() );
	}

	/**
	 * Tests execute_abilities handles errors gracefully when ability not found via instance.
	 *
	 * @return void
	 */
	public function test_execute_abilities_handles_errors_gracefully(): void {
		$this->setExpectedIncorrectUsage( 'WP_Abilities_Registry::get_registered' );

		$resolver = new Ability_Function_Resolver( 'nonexistent/ability' );
		$call     = new FunctionCall( 'func_1', 'wpab__nonexistent__ability', array() );
		$parts    = array( new MessagePart( $call ) );
		$message  = new ModelMessage( $parts );

		$result = $resolver->execute_abilities( $message );

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
	 * Tests execute_abilities returns UserMessage via instance.
	 *
	 * @return void
	 */
	public function test_execute_abilities_returns_user_message(): void {
		$this->setExpectedIncorrectUsage( 'WP_Abilities_Registry::get_registered' );

		$resolver = new Ability_Function_Resolver( 'test/ability' );
		$call     = new FunctionCall( 'func_1', 'wpab__test__ability', array() );
		$parts    = array( new MessagePart( $call ) );
		$message  = new ModelMessage( $parts );

		$result = $resolver->execute_abilities( $message );

		$this->assertInstanceOf( UserMessage::class, $result );
	}

	/**
	 * Tests execute_abilities processes multiple function calls via instance.
	 *
	 * @return void
	 */
	public function test_execute_abilities_processes_multiple_calls(): void {
		$this->setExpectedIncorrectUsage( 'WP_Abilities_Registry::get_registered' );

		$resolver = new Ability_Function_Resolver( 'test/one', 'test/two' );
		$call1    = new FunctionCall( 'func_1', 'wpab__test__one', array() );
		$call2    = new FunctionCall( 'func_2', 'wpab__test__two', array() );
		$parts    = array(
			new MessagePart( $call1 ),
			new MessagePart( $call2 ),
		);
		$message  = new ModelMessage( $parts );

		$result = $resolver->execute_abilities( $message );

		$this->assertInstanceOf( Message::class, $result );

		$result_parts = $result->getParts();
		$this->assertCount( 2, $result_parts );

		$response1 = $result_parts[0]->getFunctionResponse();
		$this->assertInstanceOf( FunctionResponse::class, $response1 );
		$this->assertEquals( 'func_1', $response1->getId() );

		$response2 = $result_parts[1]->getFunctionResponse();
		$this->assertInstanceOf( FunctionResponse::class, $response2 );
		$this->assertEquals( 'func_2', $response2->getId() );
	}

	/**
	 * Tests execute_abilities only processes function call parts via instance.
	 *
	 * @return void
	 */
	public function test_execute_abilities_only_processes_function_calls(): void {
		$this->setExpectedIncorrectUsage( 'WP_Abilities_Registry::get_registered' );

		$resolver = new Ability_Function_Resolver( 'test/ability' );
		$call     = new FunctionCall( 'func_1', 'wpab__test__ability', array() );
		$parts    = array(
			new MessagePart( 'Text before' ),
			new MessagePart( $call ),
			new MessagePart( 'Text after' ),
		);
		$message  = new ModelMessage( $parts );

		$result = $resolver->execute_abilities( $message );

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

	/**
	 * Tests execute_ability successfully executes a registered ability via instance.
	 *
	 * @return void
	 */
	public function test_execute_ability_success(): void {
		$resolver = new Ability_Function_Resolver( 'wpaiclienttests/simple' );
		$call     = new FunctionCall( 'func_123', 'wpab__wpaiclienttests__simple', array() );
		$response = $resolver->execute_ability( $call );

		$this->assertInstanceOf( FunctionResponse::class, $response );
		$this->assertEquals( 'func_123', $response->getId() );
		$this->assertEquals( 'wpab__wpaiclienttests__simple', $response->getName() );

		$result = $response->getResponse();
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'success', $result );
		$this->assertTrue( $result['success'] );
	}

	/**
	 * Tests execute_ability passes parameters to ability via instance.
	 *
	 * @return void
	 */
	public function test_execute_ability_with_parameters(): void {
		$resolver = new Ability_Function_Resolver( 'wpaiclienttests/with-params' );
		$call     = new FunctionCall( 'func_456', 'wpab__wpaiclienttests__with-params', array( 'title' => 'Test Title' ) );
		$response = $resolver->execute_ability( $call );

		$this->assertInstanceOf( FunctionResponse::class, $response );

		$result = $response->getResponse();
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'success', $result );
		$this->assertTrue( $result['success'] );
		$this->assertArrayHasKey( 'title', $result );
		$this->assertEquals( 'Test Title', $result['title'] );
	}

	/**
	 * Tests execute_ability handles WP_Error from ability via instance.
	 *
	 * @return void
	 */
	public function test_execute_ability_handles_wp_error(): void {
		$resolver = new Ability_Function_Resolver( 'wpaiclienttests/returns-error' );
		$call     = new FunctionCall( 'func_789', 'wpab__wpaiclienttests__returns-error', array() );
		$response = $resolver->execute_ability( $call );

		$this->assertInstanceOf( FunctionResponse::class, $response );
		$this->assertEquals( 'func_789', $response->getId() );

		$result = $response->getResponse();
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'error', $result );
		$this->assertEquals( 'This is a test error message.', $result['error'] );
		$this->assertArrayHasKey( 'code', $result );
		$this->assertEquals( 'test_error', $result['code'] );
	}

	/**
	 * Tests execute_abilities successfully executes registered abilities via instance.
	 *
	 * @return void
	 */
	public function test_execute_abilities_success(): void {
		$resolver = new Ability_Function_Resolver( 'wpaiclienttests/simple' );
		$call     = new FunctionCall( 'func_1', 'wpab__wpaiclienttests__simple', array() );
		$parts    = array( new MessagePart( $call ) );
		$message  = new ModelMessage( $parts );

		$result = $resolver->execute_abilities( $message );

		$this->assertInstanceOf( UserMessage::class, $result );

		$result_parts = $result->getParts();
		$this->assertCount( 1, $result_parts );

		$response = $result_parts[0]->getFunctionResponse();
		$this->assertInstanceOf( FunctionResponse::class, $response );

		$response_data = $response->getResponse();
		$this->assertArrayHasKey( 'success', $response_data );
		$this->assertTrue( $response_data['success'] );
	}

	/**
	 * Tests execute_abilities with multiple registered abilities via instance.
	 *
	 * @return void
	 */
	public function test_execute_abilities_multiple_success(): void {
		$resolver = new Ability_Function_Resolver( 'wpaiclienttests/simple', 'wpaiclienttests/with-params' );
		$call1    = new FunctionCall( 'func_1', 'wpab__wpaiclienttests__simple', array() );
		$call2    = new FunctionCall( 'func_2', 'wpab__wpaiclienttests__with-params', array( 'title' => 'Test' ) );
		$parts    = array(
			new MessagePart( $call1 ),
			new MessagePart( $call2 ),
		);
		$message  = new ModelMessage( $parts );

		$result = $resolver->execute_abilities( $message );

		$result_parts = $result->getParts();
		$this->assertCount( 2, $result_parts );

		$response1 = $result_parts[0]->getFunctionResponse();
		$this->assertInstanceOf( FunctionResponse::class, $response1 );
		$this->assertEquals( 'func_1', $response1->getId() );
		$response1_data = $response1->getResponse();
		$this->assertTrue( $response1_data['success'] );

		$response2 = $result_parts[1]->getFunctionResponse();
		$this->assertInstanceOf( FunctionResponse::class, $response2 );
		$this->assertEquals( 'func_2', $response2->getId() );
		$response2_data = $response2->getResponse();
		$this->assertTrue( $response2_data['success'] );
		$this->assertEquals( 'Test', $response2_data['title'] );
	}

	/**
	 * Tests execute_ability rejects ability not in allowed list.
	 *
	 * @return void
	 */
	public function test_execute_ability_rejects_ability_not_in_allowed_list(): void {
		$resolver = new Ability_Function_Resolver( 'wpaiclienttests/simple' );
		$call     = new FunctionCall( 'func_1', 'wpab__wpaiclienttests__with-params', array( 'title' => 'Test' ) );
		$response = $resolver->execute_ability( $call );

		$this->assertInstanceOf( FunctionResponse::class, $response );

		$result = $response->getResponse();
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'error', $result );
		$this->assertStringContainsString( 'not specified in the allowed abilities list', $result['error'] );
		$this->assertEquals( 'ability_not_allowed', $result['code'] );
	}

	/**
	 * Tests execute_ability rejects all abilities when constructed with no abilities.
	 *
	 * @return void
	 */
	public function test_execute_ability_rejects_all_when_no_abilities_specified(): void {
		$resolver = new Ability_Function_Resolver();
		$call     = new FunctionCall( 'func_1', 'wpab__wpaiclienttests__simple', array() );
		$response = $resolver->execute_ability( $call );

		$this->assertInstanceOf( FunctionResponse::class, $response );

		$result = $response->getResponse();
		$this->assertIsArray( $result );
		$this->assertEquals( 'ability_not_allowed', $result['code'] );
	}

	/**
	 * Tests execute_abilities filters by allowed list.
	 *
	 * @return void
	 */
	public function test_execute_abilities_filters_by_allowed_list(): void {
		$resolver = new Ability_Function_Resolver( 'wpaiclienttests/simple' );
		$call1    = new FunctionCall( 'func_1', 'wpab__wpaiclienttests__simple', array() );
		$call2    = new FunctionCall( 'func_2', 'wpab__wpaiclienttests__with-params', array( 'title' => 'Test' ) );
		$parts    = array(
			new MessagePart( $call1 ),
			new MessagePart( $call2 ),
		);
		$message  = new ModelMessage( $parts );

		$result = $resolver->execute_abilities( $message );

		$result_parts = $result->getParts();
		$this->assertCount( 2, $result_parts );

		$response1_data = $result_parts[0]->getFunctionResponse()->getResponse();
		$this->assertArrayHasKey( 'success', $response1_data );
		$this->assertTrue( $response1_data['success'] );

		$response2_data = $result_parts[1]->getFunctionResponse()->getResponse();
		$this->assertEquals( 'ability_not_allowed', $response2_data['code'] );
	}

	/**
	 * Tests constructor accepts WP_Ability objects.
	 *
	 * @return void
	 */
	public function test_constructor_accepts_wp_ability_objects(): void {
		$ability  = wp_get_ability( 'wpaiclienttests/simple' );
		$resolver = new Ability_Function_Resolver( $ability );
		$call     = new FunctionCall( 'func_1', 'wpab__wpaiclienttests__simple', array() );
		$response = $resolver->execute_ability( $call );

		$result = $response->getResponse();
		$this->assertArrayHasKey( 'success', $result );
		$this->assertTrue( $result['success'] );
	}

	/**
	 * Tests constructor accepts mixed WP_Ability objects and strings.
	 *
	 * @return void
	 */
	public function test_constructor_accepts_mixed_ability_types(): void {
		$ability  = wp_get_ability( 'wpaiclienttests/simple' );
		$resolver = new Ability_Function_Resolver( $ability, 'wpaiclienttests/with-params' );

		$call1     = new FunctionCall( 'func_1', 'wpab__wpaiclienttests__simple', array() );
		$response1 = $resolver->execute_ability( $call1 );
		$this->assertArrayHasKey( 'success', $response1->getResponse() );

		$call2     = new FunctionCall( 'func_2', 'wpab__wpaiclienttests__with-params', array( 'title' => 'Test' ) );
		$response2 = $resolver->execute_ability( $call2 );
		$this->assertArrayHasKey( 'success', $response2->getResponse() );
	}
}
