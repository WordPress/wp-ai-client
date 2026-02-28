<?php
/**
 * Tests for WordPress\AI_Client\AI_Client
 *
 * @package WordPress\AI_Client
 */

namespace WordPress\AI_Client\PHPUnit\Tests;

use ReflectionClass;
use WordPress\AI_Client\AI_Client;
use WordPress\AI_Client\Builders\Prompt_Builder;
use WordPress\AI_Client\Builders\Prompt_Builder_With_WP_Error;
use WordPress\AI_Client\PHPUnit\Includes\Test_Case;
use WordPress\AiClient\AiClient;

class AI_Client_Tests extends Test_Case {

	public function test_init(): void {
		// Make internal 'initialized' property accessible.
		$reflection_class     = new ReflectionClass( AI_Client::class );
		$initialized_property = $reflection_class->getProperty( 'initialized' );
		$initialized_property->setAccessible( true );

		// Since the plugin is initialized during bootstrap, it should already be initialized here.
		$this->assertTrue( $initialized_property->getValue() );

		// Reset to not initialized for testing purposes.
		$initialized_property->setValue( false );

		// The init call will indirectly add a hook to 'admin_menu', so we remove all actions first.
		remove_all_actions( 'admin_menu' );

		// First init call.
		AI_Client::init();
		$this->assertTrue( $initialized_property->getValue() );

		// On < 7.0, API_Credentials_Manager adds an admin_menu action.
		// On 7.0+, SDK infrastructure is skipped (core handles it).
		if ( wp_has_ai_client() ) {
			$this->assertNotHasAction( 'admin_menu' );
		} else {
			$this->assertHasAction( 'admin_menu' );
		}

		// Now we remove the added action again to verify that the second init call does not add it again.
		remove_all_actions( 'admin_menu' );

		// Second init call.
		AI_Client::init();
		$this->assertTrue( $initialized_property->getValue() );
		$this->assertNotHasAction( 'admin_menu' );
	}

	public function test_prompt(): void {
		$prompt_builder = AI_Client::prompt();
		$this->assertInstanceOf( Prompt_Builder::class, $prompt_builder );

		// Ensure registry of the prompt builder is the default one from the PHP AI Client SDK.
		$reflection_class = new ReflectionClass( Prompt_Builder::class );
		$builder_property = $reflection_class->getProperty( 'builder' );
		$builder_property->setAccessible( true );
		$wrapped_builder   = $builder_property->getValue( $prompt_builder );
		$reflection_class2 = new ReflectionClass( get_class( $wrapped_builder ) );
		$registry_property = $reflection_class2->getProperty( 'registry' );
		$registry_property->setAccessible( true );
		$this->assertSame( AiClient::defaultRegistry(), $registry_property->getValue( $wrapped_builder ) );
	}

	public function test_prompt_with_wp_error(): void {
		$prompt_builder = AI_Client::prompt_with_wp_error();
		$this->assertInstanceOf( Prompt_Builder_With_WP_Error::class, $prompt_builder );
		$this->assertInstanceOf( Prompt_Builder::class, $prompt_builder );

		// Ensure registry of the prompt builder is the default one from the PHP AI Client SDK.
		$reflection_class = new ReflectionClass( Prompt_Builder::class );
		$builder_property = $reflection_class->getProperty( 'builder' );
		$builder_property->setAccessible( true );
		$wrapped_builder   = $builder_property->getValue( $prompt_builder );
		$reflection_class2 = new ReflectionClass( get_class( $wrapped_builder ) );
		$registry_property = $reflection_class2->getProperty( 'registry' );
		$registry_property->setAccessible( true );
		$this->assertSame( AiClient::defaultRegistry(), $registry_property->getValue( $wrapped_builder ) );
	}
}
