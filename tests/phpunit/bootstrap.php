<?php
/**
 * PHPUnit bootstrap file
 *
 * @package WordPress\AI_Client
 */

define( 'WP_AI_CLIENT_PROJECT_DIR', dirname( dirname( __DIR__ ) ) );

// Detect where to load the WordPress tests environment from.
if ( false !== getenv( 'WP_TESTS_DIR' ) ) {
	$wp_ai_client_test_root = getenv( 'WP_TESTS_DIR' );
} elseif ( false !== getenv( 'WP_DEVELOP_DIR' ) ) {
	$wp_ai_client_test_root = getenv( 'WP_DEVELOP_DIR' ) . '/tests/phpunit';
} elseif ( false !== getenv( 'WP_PHPUNIT__DIR' ) ) {
	$wp_ai_client_test_root = getenv( 'WP_PHPUNIT__DIR' );
} else { // Fallback.
	$wp_ai_client_test_root = '/tmp/wordpress-tests-lib';
}

/*
 * Do not load the vendor autoloader here. plugin.php handles it conditionally:
 * on < 7.0 it loads the full Composer autoloader, on 7.0+ it registers a
 * minimal PSR-4 autoloader for only this plugin's own classes (to avoid PSR
 * scoping conflicts with core's scoped dependencies). No plugin classes are
 * needed before the WordPress test bootstrap activates the plugin below.
 */

// Force empty test plugin containing the library to be active.
$GLOBALS['wp_tests_options'] = array( // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
	'active_plugins' => array( basename( WP_AI_CLIENT_PROJECT_DIR ) . '/plugin.php' ),
);

// Load WordPress test functions to use tests_add_filter().
require_once $wp_ai_client_test_root . '/includes/functions.php';

/**
 * Registers a test category for the Abilities API.
 *
 * Must be hooked before WordPress bootstrap loads.
 */
tests_add_filter(
	'wp_abilities_api_categories_init',
	static function () {
		wp_register_ability_category(
			'wpaiclienttests',
			array(
				'label'       => 'WP AI Client Tests',
				'description' => 'Test abilities for the WP AI Client plugin.',
			)
		);
	}
);

/**
 * Registers test abilities for the Abilities API.
 *
 * Must be hooked before WordPress bootstrap loads.
 */
tests_add_filter(
	'wp_abilities_api_init',
	static function () {
		// Simple ability with no parameters.
		wp_register_ability(
			'wpaiclienttests/simple',
			array(
				'label'               => 'Simple Test Ability',
				'description'         => 'A simple test ability with no parameters.',
				'category'            => 'wpaiclienttests',
				'permission_callback' => '__return_true',
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(),
				),
				'execute_callback'    => static function () {
					return array( 'success' => true );
				},
			)
		);

		// Ability with input schema.
		wp_register_ability(
			'wpaiclienttests/with-params',
			array(
				'label'               => 'Test Ability With Parameters',
				'description'         => 'A test ability that accepts parameters.',
				'category'            => 'wpaiclienttests',
				'permission_callback' => '__return_true',
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'title'       => array(
							'type'        => 'string',
							'description' => 'The title of the item.',
						),
						'description' => array(
							'type'        => 'string',
							'description' => 'The description of the item.',
						),
					),
					'required'   => array( 'title' ),
				),
				'execute_callback'    => static function ( $input ) {
					return array(
						'success' => true,
						'title'   => $input['title'] ?? '',
					);
				},
			)
		);

		// Ability that returns an error.
		wp_register_ability(
			'wpaiclienttests/returns-error',
			array(
				'label'               => 'Test Ability That Returns Error',
				'description'         => 'A test ability that always returns a WP_Error.',
				'category'            => 'wpaiclienttests',
				'permission_callback' => '__return_true',
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(),
				),
				'execute_callback'    => static function () {
					return new \WP_Error( 'test_error', 'This is a test error message.' );
				},
			)
		);

		// Ability with hyphenated name (for testing name-to-function conversion).
		wp_register_ability(
			'wpaiclienttests/hyphen-test',
			array(
				'label'               => 'Hyphenated Name Test Ability',
				'description'         => 'A test ability with hyphens in the name.',
				'category'            => 'wpaiclienttests',
				'permission_callback' => '__return_true',
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(),
				),
				'execute_callback'    => static function () {
					return array( 'hyphenated' => true );
				},
			)
		);
	}
);

// Start up the WP testing environment.
require $wp_ai_client_test_root . '/includes/bootstrap.php';
