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
 * License: GPL-2.0-or-later
 * License URI: https://spdx.org/licenses/GPL-2.0-or-later.html
 * Text Domain: wp-ai-client
 *
 * @package wp-ai-client
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

require_once __DIR__ . '/vendor/autoload.php';

add_action(
	'init',
	static function () {
		// Wire up the WordPress HTTP client with the PHP AI Client SDK.
		WordPress\AI_Client\HTTP\WP_AI_Client_Discovery_Strategy::init();

		// Initialize the API credentials manager and settings screen.
		$api_credentials_manager = new WordPress\AI_Client\API_Credentials\API_Credentials_Manager();
		$api_credentials_manager->initialize();
	}
);
