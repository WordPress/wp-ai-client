<?php
/**
 * PHPUnit bootstrap file
 *
 * @package WordPress\AI_Client
 */

define( 'WP_AI_CLIENT_PROJECT_DIR', dirname( dirname( __DIR__ ) ) );

require_once WP_AI_CLIENT_PROJECT_DIR . '/vendor/autoload.php';

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

// Force empty test plugin containing the library to be active.
$GLOBALS['wp_tests_options'] = array( // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
	'active_plugins' => array( basename( WP_AI_CLIENT_PROJECT_DIR ) . '/plugin.php' ),
);

// Start up the WP testing environment.
require $wp_ai_client_test_root . '/includes/bootstrap.php';
