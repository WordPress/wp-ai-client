<?php
/**
 * Custom autoloader for the WordPress AI Client package.
 *
 * Plugins that bundle this package should load this file instead of the Composer autoloader, to avoid conflicts with
 * core's own bundled version of the PHP AI Client SDK and its dependencies on WordPress 7.0+.
 *
 * @package WordPress\AI_Client
 */

require_once __DIR__ . '/functions.php';

if ( ! wp_has_ai_client() ) {
	// On < 7.0, load the full Composer autoloader (PHP AI Client SDK, PSR
	// packages, and this plugin's own classes).
	if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
		// Load autoload.php file from this package.
		require_once __DIR__ . '/vendor/autoload.php';
	} else {
		// Load autoload.php file from parent package that bundles this one.
		require_once dirname( __DIR__, 2 ) . '/autoload.php';
	}
} else {
	// On 7.0+, only autoload this plugin's own classes. Core provides the
	// AI client SDK natively with scoped PSR dependencies; loading this
	// plugin's vendor autoloader would cause fatal declaration-compatibility
	// errors between unscoped PSR types and core's scoped versions.
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
