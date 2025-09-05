<?php
/**
 * Plugin Name: AI Client
 * Plugin URI: https://github.com/WordPress/wp-ai-client/
 * Description: An AI client and API for WordPress to communicate with any generative AI models of various capabilities using a uniform API.
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Version: n.e.x.t
 * Author: WordPress AI Team
 * Author URI: https://make.wordpress.org/ai/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain: wp-ai-client
 *
 * @package wp-ai-client
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

require_once __DIR__ . '/vendor/autoload.php';

// Initialize REST API endpoints
add_action( 'rest_api_init', function() {
	\WordPress\AI_Client\REST\REST_Route_Registrar::register_rest_routes( 'wp-ai-client/v1' );
} );
