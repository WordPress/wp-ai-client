<?php
/**
 * Tests for WordPress\AI_Client\REST_API\JSON_Schema_To_WP_Schema_Converter
 *
 * @package WordPress\AI_Client
 */

namespace WordPress\AI_Client\PHPUnit\Tests\REST_API;

use WordPress\AI_Client\PHPUnit\Includes\Test_Case;
use WordPress\AI_Client\REST_API\JSON_Schema_To_WP_Schema_Converter;

/**
 * Tests for JSON_Schema_To_WP_Schema_Converter.
 */
class JSON_Schema_To_WP_Schema_Converter_Tests extends Test_Case {

	/**
	 * Tests converting a basic object with required properties.
	 */
	public function test_convert_basic_object_with_required_properties() {
		$schema = array(
			'type'       => 'object',
			'properties' => array(
				'foo' => array( 'type' => 'string' ),
				'bar' => array( 'type' => 'integer' ),
			),
			'required'   => array( 'foo' ),
		);

		$expected = array(
			'type'       => 'object',
			'properties' => array(
				'foo' => array(
					'type'     => 'string',
					'required' => true,
				),
				'bar' => array( 'type' => 'integer' ),
			),
		);

		$this->assertEquals( $expected, JSON_Schema_To_WP_Schema_Converter::convert( $schema ) );
	}

	/**
	 * Tests converting an object with a sub-object with required properties.
	 */
	public function test_convert_object_with_sub_object_with_required_properties() {
		$schema = array(
			'type'       => 'object',
			'properties' => array(
				'sub' => array(
					'type'       => 'object',
					'properties' => array(
						'baz' => array( 'type' => 'boolean' ),
					),
					'required'   => array( 'baz' ),
				),
			),
		);

		$expected = array(
			'type'       => 'object',
			'properties' => array(
				'sub' => array(
					'type'       => 'object',
					'properties' => array(
						'baz' => array(
							'type'     => 'boolean',
							'required' => true,
						),
					),
				),
			),
		);

		$this->assertEquals( $expected, JSON_Schema_To_WP_Schema_Converter::convert( $schema ) );
	}

	/**
	 * Tests converting an array of objects with required properties.
	 */
	public function test_convert_array_of_objects_with_required_properties() {
		$schema = array(
			'type'  => 'array',
			'items' => array(
				'type'       => 'object',
				'properties' => array(
					'item_prop' => array( 'type' => 'string' ),
				),
				'required'   => array( 'item_prop' ),
			),
		);

		$expected = array(
			'type'  => 'array',
			'items' => array(
				'type'       => 'object',
				'properties' => array(
					'item_prop' => array(
						'type'     => 'string',
						'required' => true,
					),
				),
			),
		);

		$this->assertEquals( $expected, JSON_Schema_To_WP_Schema_Converter::convert( $schema ) );
	}

	/**
	 * Tests converting a oneOf construct where one option is an object with required properties.
	 */
	public function test_convert_oneof_with_object_with_required_properties() {
		$schema = array(
			'oneOf' => array(
				array( 'type' => 'string' ),
				array(
					'type'       => 'object',
					'properties' => array(
						'option_prop' => array( 'type' => 'number' ),
					),
					'required'   => array( 'option_prop' ),
				),
			),
		);

		$expected = array(
			'oneOf' => array(
				array( 'type' => 'string' ),
				array(
					'type'       => 'object',
					'properties' => array(
						'option_prop' => array(
							'type'     => 'number',
							'required' => true,
						),
					),
				),
			),
		);

		$this->assertEquals( $expected, JSON_Schema_To_WP_Schema_Converter::convert( $schema ) );
	}
}
