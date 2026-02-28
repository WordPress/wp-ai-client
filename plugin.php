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

require_once __DIR__ . '/vendor/autoload.php';

add_action( 'init', array( WordPress\AI_Client\AI_Client::class, 'init' ) );
