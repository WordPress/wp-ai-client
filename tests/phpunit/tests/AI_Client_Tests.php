<?php
/**
 * Tests for WordPress\AI_Client\AI_Client
 *
 * @package wp-oop-plugin-lib
 */

namespace WordPress\AI_Client\PHPUnit\Tests;

use ReflectionClass;
use WordPress\AI_Client\AI_Client;
use WordPress\AI_Client\PHPUnit\Includes\Test_Case;

class AI_Client_Tests extends Test_Case {

	public function test_init_initializes_only_once(): void {
		// Ensure not initialized yet.
		$reflection_class     = new ReflectionClass( AI_Client::class );
		$initialized_property = $reflection_class->getProperty( 'initialized' );
		$initialized_property->setAccessible( true );
		$initialized_property->setValue( false );

		// The init call will indirectly add a hook to 'admin_menu', so we remove all actions first.
		remove_all_actions( 'admin_menu' );

		// First init call.
		AI_Client::init();
		$this->assertTrue( $initialized_property->getValue() );
		$this->assertHasAction( 'admin_menu' );

		// Now we remove the added action again to verify that the second init call does not add it again.
		remove_all_actions( 'admin_menu' );

		// Second init call.
		AI_Client::init();
		$this->assertTrue( $initialized_property->getValue() );
		$this->assertNotHasAction( 'admin_menu' );
	}
}
