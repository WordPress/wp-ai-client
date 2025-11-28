<?php
/**
 * Tests for WordPress\AI_Client\Capabilities\Capabilities_Manager
 *
 * @package WordPress\AI_Client
 */

namespace WordPress\AI_Client\PHPUnit\Tests\Capabilities;

use WordPress\AI_Client\Capabilities\Capabilities_Manager;
use WordPress\AI_Client\PHPUnit\Includes\Test_Case;

class Capabilities_Manager_Tests extends Test_Case {

	private static $administrator_id;
	private static $editor_id;

	public static function set_up_before_class() {
		parent::set_up_before_class();

		self::$administrator_id = self::factory()->user->create(
			array( 'role' => 'administrator' )
		);
		self::$editor_id        = self::factory()->user->create(
			array( 'role' => 'editor' )
		);
	}

	/**
	 * Tests that administrators have the prompt_ai capability.
	 */
	public function test_administrator_has_prompt_ai_capability() {
		$this->assertTrue( user_can( self::$administrator_id, Capabilities_Manager::PROMPT_AI_CAPABILITY ) );
	}

	/**
	 * Tests that editors do not have the prompt_ai capability.
	 */
	public function test_editor_does_not_have_prompt_ai_capability() {
		$this->assertFalse( user_can( self::$editor_id, Capabilities_Manager::PROMPT_AI_CAPABILITY ) );
	}

	/**
	 * Tests that removing the default filter leads to administrators no longer having the prompt_ai capability.
	 */
	public function test_removing_default_filter_removes_prompt_ai_capability() {
		remove_filter( 'user_has_cap', array( Capabilities_Manager::class, 'grant_prompt_ai_to_administrators' ) );

		$this->assertFalse( user_can( self::$administrator_id, Capabilities_Manager::PROMPT_AI_CAPABILITY ) );
	}

	/**
	 * Tests the grant_prompt_ai_to_administrators method directly.
	 */
	public function test_grant_prompt_ai_to_administrators() {
		$allcaps = array( 'manage_options' => true );
		$this->assertEquals(
			array(
				'manage_options'                           => true,
				Capabilities_Manager::PROMPT_AI_CAPABILITY => true,
			),
			Capabilities_Manager::grant_prompt_ai_to_administrators( $allcaps )
		);

		$allcaps = array( 'manage_options' => false );
		$this->assertEquals(
			array( 'manage_options' => false ),
			Capabilities_Manager::grant_prompt_ai_to_administrators( $allcaps )
		);

		$allcaps = array( 'edit_posts' => true );
		$this->assertEquals(
			array( 'edit_posts' => true ),
			Capabilities_Manager::grant_prompt_ai_to_administrators( $allcaps )
		);
	}

	public static function tear_down_after_class() {
		self::delete_user( self::$administrator_id );
		self::delete_user( self::$editor_id );

		parent::tear_down_after_class();
	}
}
