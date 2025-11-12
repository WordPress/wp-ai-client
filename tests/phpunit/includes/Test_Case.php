<?php
/**
 * Class WordPress\AI_Client\PHPUnit\Includes\Test_Case
 *
 * @package WordPress\AI_Client
 */

namespace WordPress\AI_Client\PHPUnit\Includes;

use WP_UnitTestCase;

/**
 * Basic class for unit tests of the plugin.
 */
abstract class Test_Case extends WP_UnitTestCase {

	/**
	 * Asserts that the given hook has one or more actions added.
	 *
	 * @param string $hook_name Action hook name.
	 * @param string $message   Optional. Message to display when the assertion fails.
	 */
	public function assertHasAction( $hook_name, $message = '' ) {
		if ( ! $message ) {
			$message = sprintf( 'Failed asserting that any action is added to the %s hook.', $hook_name );
		}
		$this->assertTrue( has_action( $hook_name ), $message );
	}

	/**
	 * Asserts that the given hook has no actions added.
	 *
	 * @param string $hook_name Action hook name.
	 * @param string $message   Optional. Message to display when the assertion fails.
	 */
	public function assertNotHasAction( $hook_name, $message = '' ) {
		if ( ! $message ) {
			$message = sprintf( 'Failed asserting that no action is added to the %s hook.', $hook_name );
		}
		$this->assertFalse( has_action( $hook_name ), $message );
	}

	/**
	 * Asserts that the given hook has one or more filters added.
	 *
	 * @param string $hook_name Filter hook name.
	 * @param string $message   Optional. Message to display when the assertion fails.
	 */
	public function assertHasFilter( $hook_name, $message = '' ) {
		if ( ! $message ) {
			$message = sprintf( 'Failed asserting that any filter is added to the %s hook.', $hook_name );
		}
		$this->assertTrue( has_filter( $hook_name ), $message );
	}

	/**
	 * Asserts that the given hook has no filters added.
	 *
	 * @param string $hook_name Filter hook name.
	 * @param string $message   Optional. Message to display when the assertion fails.
	 */
	public function assertNotHasFilter( $hook_name, $message = '' ) {
		if ( ! $message ) {
			$message = sprintf( 'Failed asserting that no filter is added to the %s hook.', $hook_name );
		}
		$this->assertFalse( has_filter( $hook_name ), $message );
	}

	/**
	 * Asserts that the given hook has one or more actions added.
	 *
	 * @param string   $hook_name Action hook name.
	 * @param callable $callback  Hook callback to check for.
	 * @param string   $message   Optional. Message to display when the assertion fails.
	 */
	public function assertHasActionCallback( $hook_name, $callback, $message = '' ) {
		if ( ! $message ) {
			$message = sprintf( 'Failed asserting that %s is added to the %s action hook.', $this->get_callback_name( $callback ), $hook_name );
		}
		$this->assertTrue( (bool) has_action( $hook_name, $callback ), $message );
	}

	/**
	 * Asserts that the given hook has no actions added.
	 *
	 * @param string   $hook_name Action hook name.
	 * @param callable $callback  Hook callback to check for.
	 * @param string   $message   Optional. Message to display when the assertion fails.
	 */
	public function assertNotHasActionCallback( $hook_name, $callback, $message = '' ) {
		if ( ! $message ) {
			$message = sprintf( 'Failed asserting that %s is not added to the %s action hook.', $this->get_callback_name( $callback ), $hook_name );
		}
		$this->assertFalse( has_action( $hook_name, $callback ), $message );
	}

	/**
	 * Asserts that the given hook has one or more filters added.
	 *
	 * @param string   $hook_name Filter hook name.
	 * @param callable $callback  Hook callback to check for.
	 * @param string   $message   Optional. Message to display when the assertion fails.
	 */
	public function assertHasFilterCallback( $hook_name, $callback, $message = '' ) {
		if ( ! $message ) {
			$message = sprintf( 'Failed asserting that %s is added to the %s filter hook.', $this->get_callback_name( $callback ), $hook_name );
		}
		$this->assertTrue( (bool) has_filter( $hook_name, $callback ), $message );
	}

	/**
	 * Asserts that the given hook has no filters added.
	 *
	 * @param string   $hook_name Filter hook name.
	 * @param callable $callback  Hook callback to check for.
	 * @param string   $message   Optional. Message to display when the assertion fails.
	 */
	public function assertNotHasFilterCallback( $hook_name, $callback, $message = '' ) {
		if ( ! $message ) {
			$message = sprintf( 'Failed asserting that %s is not added to the %s filter hook.', $this->get_callback_name( $callback ), $hook_name );
		}
		$this->assertFalse( has_filter( $hook_name, $callback ), $message );
	}

	/**
	 * Expects a function to trigger a _doing_it_wrong() notice.
	 *
	 * @param string $function_name Function or method name expected to trigger the notice.
	 */
	public function expectDoingItWrong( string $function_name ) {
		$this->expected_doing_it_wrong[] = $function_name;
	}
}
