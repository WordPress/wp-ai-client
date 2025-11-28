<?php
/**
 * Tests for WordPress\AI_Client\REST_API\AI_REST_Controller
 *
 * @package WordPress\AI_Client
 */

namespace WordPress\AI_Client\PHPUnit\Tests\REST_API;

use ReflectionClass;
use ReflectionMethod;
use WP_REST_Request;
use WordPress\AiClient\AiClient;
use WordPress\AI_Client\REST_API\AI_REST_Controller;
use WordPress\AI_Client\PHPUnit\Includes\Test_Case;
use WordPress\AI_Client\PHPUnit\Includes\Mock_Provider;
use WordPress\AI_Client\PHPUnit\Includes\Mock_Model;

class AI_REST_Controller_Tests extends Test_Case {
	private static $administrator_id;

	public static function set_up_before_class() {
		parent::set_up_before_class();

		self::$administrator_id = self::factory()->user->create(
			array( 'role' => 'administrator' )
		);
	}

	public static function tear_down_after_class() {
		self::delete_user( self::$administrator_id );

		parent::tear_down_after_class();
	}

	/**
	 * Tests the permissions_check method.
	 */
	public function test_permissions_check() {
		$controller = new AI_REST_Controller();

		// Test as administrator.
		wp_set_current_user( self::$administrator_id );
		$this->assertTrue( $controller->permissions_check() );

		// Test as logged out (or user without capability).
		wp_set_current_user( 0 );
		$result = $controller->permissions_check();
		$this->assertWPError( $result );
		$this->assertEquals( 'rest_forbidden', $result->get_error_code() );
	}

	/**
	 * Tests the process_generate_request method.
	 */
	public function test_process_generate_request() {
		$controller = new AI_REST_Controller();
		$request    = new WP_REST_Request( 'POST', '/wp-ai/v1/generate' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				array(
					'messages' => array(
						array(
							'role'  => 'user',
							'parts' => array(
								array(
									'channel' => 'content',
									'type'    => 'text',
									'text'    => 'Hello',
								),
							),
						),
					),
				)
			)
		);

		// We expect this to fail because no providers are configured, but it exercises the code path.
		$response = $controller->process_generate_request( $request );

		if ( is_wp_error( $response ) ) {
			$this->assertEquals( 'ai_generate_error', $response->get_error_code() );
		} else {
			$this->assertEquals( 200, $response->get_status() );
		}
	}

	/**
	 * Tests the process_is_supported_request method.
	 */
	public function test_process_is_supported_request() {
		$controller = new AI_REST_Controller();
		$request    = new WP_REST_Request( 'POST', '/wp-ai/v1/is-supported' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				array(
					'messages' => array(
						array(
							'role'  => 'user',
							'parts' => array(
								array(
									'channel' => 'content',
									'type'    => 'text',
									'text'    => 'Hello',
								),
							),
						),
					),
				)
			)
		);

		$response = $controller->process_is_supported_request( $request );

		if ( is_wp_error( $response ) ) {
			$this->assertEquals( 'ai_is_supported_error', $response->get_error_code() );
		} else {
			$this->assertEquals( 200, $response->get_status() );
			$data = $response->get_data();
			$this->assertArrayHasKey( 'supported', $data );
		}
	}

	/**
	 * Tests the create_builder_from_params method.
	 */
	public function test_create_builder_from_params() {
		$controller = new AI_REST_Controller();
		$method     = new ReflectionMethod( $controller, 'create_builder_from_params' );
		$method->setAccessible( true );

		$params = array(
			'messages'         => array(
				array(
					'role'  => 'user',
					'parts' => array(
						array(
							'channel' => 'content',
							'type'    => 'text',
							'text'    => 'Hello',
						),
					),
				),
			),
			'providerId'       => 'openai',
			'modelPreferences' => array( 'gpt-5.1' ),
		);

		$builder = $method->invoke( $controller, $params );

		$this->assertInstanceOf( 'WordPress\AI_Client\Builders\Prompt_Builder', $builder );

		// Verify internal state.
		$inner_builder = $this->get_private_property( $builder, 'builder' );
		$messages      = $this->get_private_property( $inner_builder, 'messages' );
		$provider_id   = $this->get_private_property( $inner_builder, 'providerIdOrClassName' );
		$preferences   = $this->get_private_property( $inner_builder, 'modelPreferenceKeys' );

		$this->assertCount( 1, $messages );
		$this->assertEquals( 'user', $this->get_private_property( $messages[0], 'role' )->value );
		$this->assertEquals( 'openai', $provider_id );
		$this->assertContains( 'model::gpt-5.1', $preferences );
	}

	/**
	 * Tests the create_builder_from_params method with config and options.
	 */
	public function test_create_builder_from_params_with_config_and_options() {
		$controller = new AI_REST_Controller();
		$method     = new ReflectionMethod( $controller, 'create_builder_from_params' );
		$method->setAccessible( true );

		$params = array(
			'messages'       => array(
				array(
					'role'  => 'user',
					'parts' => array(
						array(
							'channel' => 'content',
							'type'    => 'text',
							'text'    => 'Hello',
						),
					),
				),
			),
			'modelConfig'    => array(
				'temperature' => 0.7,
				'maxTokens'   => 100,
			),
			'requestOptions' => array(
				'timeout' => 60,
			),
		);

		$builder = $method->invoke( $controller, $params );

		$this->assertInstanceOf( 'WordPress\AI_Client\Builders\Prompt_Builder', $builder );

		// Verify internal state.
		$inner_builder   = $this->get_private_property( $builder, 'builder' );
		$model_config    = $this->get_private_property( $inner_builder, 'modelConfig' );
		$request_options = $this->get_private_property( $inner_builder, 'requestOptions' );

		$this->assertEquals( 0.7, $this->get_private_property( $model_config, 'temperature' ) );
		$this->assertEquals( 100, $this->get_private_property( $model_config, 'maxTokens' ) );
		$this->assertEquals( 60, $this->get_private_property( $request_options, 'timeout' ) );
	}

	/**
	 * Tests the convert_json_schema_to_wp_schema method.
	 */
	public function test_convert_json_schema_to_wp_schema() {
		$controller = new AI_REST_Controller();
		$method     = new ReflectionMethod( $controller, 'convert_json_schema_to_wp_schema' );
		$method->setAccessible( true );

		$json_schema = array(
			'type'       => 'object',
			'properties' => array(
				'foo' => array( 'type' => 'string' ),
				'bar' => array( 'type' => 'integer' ),
			),
			'required'   => array( 'foo' ),
		);

		$wp_schema = $method->invoke( $controller, $json_schema );

		$this->assertArrayNotHasKey( 'required', $wp_schema );
		$this->assertArrayHasKey( 'required', $wp_schema['properties']['foo'] );
		$this->assertTrue( $wp_schema['properties']['foo']['required'] );
	}

	/**
	 * Tests the create_builder_from_params method with providerId and modelId.
	 */
	public function test_create_builder_from_params_with_provider_and_model() {
		// Register mock provider.
		AiClient::defaultRegistry()->registerProvider( Mock_Provider::class );

		$controller = new AI_REST_Controller();
		$method     = new ReflectionMethod( $controller, 'create_builder_from_params' );
		$method->setAccessible( true );

		$params = array(
			'messages'   => array(
				array(
					'role'  => 'user',
					'parts' => array(
						array(
							'channel' => 'content',
							'type'    => 'text',
							'text'    => 'Hello',
						),
					),
				),
			),
			'providerId' => 'mock-provider',
			'modelId'    => 'mock-model',
		);

		$builder = $method->invoke( $controller, $params );

		$this->assertInstanceOf( 'WordPress\AI_Client\Builders\Prompt_Builder', $builder );

		// Verify internal state.
		$inner_builder = $this->get_private_property( $builder, 'builder' );
		$model         = $this->get_private_property( $inner_builder, 'model' );

		$this->assertInstanceOf( Mock_Model::class, $model );
		$this->assertEquals( 'mock-model', $model->metadata()->getId() );
	}

	/**
	 * Helper to access private property of an object.
	 *
	 * @param object $obj      The object.
	 * @param string $property The property name.
	 * @return mixed The property value.
	 */
	private function get_private_property( $obj, $property ) {
		$reflection = new ReflectionClass( $obj );
		$prop       = $reflection->getProperty( $property );
		$prop->setAccessible( true );
		return $prop->getValue( $obj );
	}
}
