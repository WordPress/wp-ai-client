<?php
/**
 * Tests for WordPress\AI_Client\Events\WordPress_Event_Dispatcher
 *
 * @package WordPress\AI_Client
 */

namespace WordPress\AI_Client\PHPUnit\Tests\Events;

use stdClass;
use WordPress\AI_Client\Events\WordPress_Event_Dispatcher;
use WordPress\AI_Client\PHPUnit\Includes\Mock_Event;
use WordPress\AI_Client\PHPUnit\Includes\Test_Case;

class WordPress_Event_Dispatcher_Tests extends Test_Case {

	/**
	 * Tests that dispatch fires the correct WordPress action hook.
	 */
	public function test_dispatch_fires_action_hook(): void {
		$dispatcher = new WordPress_Event_Dispatcher();
		$event      = new Mock_Event();

		$hook_fired   = false;
		$passed_event = null;

		add_action(
			'wp_ai_client_mock',
			static function ( $event ) use ( &$hook_fired, &$passed_event ) {
				$hook_fired   = true;
				$passed_event = $event;
			}
		);

		$returned_event = $dispatcher->dispatch( $event );

		$this->assertTrue( $hook_fired, 'The action hook should have been fired.' );
		$this->assertSame( $event, $passed_event, 'The event object should be passed to the action hook.' );
		$this->assertSame( $event, $returned_event, 'The dispatch method should return the same event object.' );
	}

	/**
	 * Tests that dispatch returns the event object unmodified when no listeners are attached.
	 */
	public function test_dispatch_returns_event_without_listeners(): void {
		$dispatcher = new WordPress_Event_Dispatcher();
		$event      = new stdClass();

		$event->test_value = 'original';

		$returned_event = $dispatcher->dispatch( $event );

		$this->assertSame( $event, $returned_event, 'The dispatch method should return the same event object.' );
		$this->assertSame( 'original', $returned_event->test_value, 'The event properties should be unchanged.' );
	}
}
