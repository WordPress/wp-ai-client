<?php
/**
 * Plugin Name: AI Client
 * Plugin URI: https://github.com/WordPress/wp-ai-client/
 * Description: An AI client and API for WordPress to communicate with any generative AI models of various capabilities using a uniform API.
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Version: 0.1.0
 * Author: WordPress AI Team
 * Author URI: https://make.wordpress.org/ai/
 * License: GPL-2.0-or-later
 * License URI: https://spdx.org/licenses/GPL-2.0-or-later.html
 * Text Domain: wp-ai-client
 *
 * @package WordPress\AI_Client
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

require_once __DIR__ . '/functions.php';

if ( ! wp_has_ai_client() ) {
	// On < 7.0, load the full Composer autoloader (PHP AI Client SDK, PSR
	// packages, and this plugin's own classes).
	require_once __DIR__ . '/vendor/autoload.php';
} else {
	// On 7.0+, only autoload this plugin's own classes. Core provides the
	// AI client SDK natively with scoped PSR dependencies; loading this
	// plugin's vendor autoloader would cause fatal declaration-compatibility
	// errors between unscoped Psr\* types and core's scoped versions.
	spl_autoload_register(
		static function ( $class ) {
			$prefix = 'WordPress\\AI_Client\\';
			$len    = strlen( $prefix );
			if ( strncmp( $class, $prefix, $len ) !== 0 ) {
				return;
			}
			$relative_class = substr( $class, $len );
			$file           = __DIR__ . '/includes/' . str_replace( '\\', '/', $relative_class ) . '.php';
			if ( file_exists( $file ) ) {
				require $file;
			}
		}
	);
}

add_action( 'init', array( WordPress\AI_Client\AI_Client::class, 'init' ) );
