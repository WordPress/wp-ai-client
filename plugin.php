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

// When WordPress 7.0+ is present, the AI client is provided natively by core.
if ( function_exists( 'wp_get_wp_version' ) && version_compare( wp_get_wp_version(), '7.0-alpha', '>=' ) ) {
	add_action(
		'admin_notices',
		static function () {
			if ( ! current_user_can( 'deactivate_plugins' ) ) {
				return;
			}

			$deactivate_url = wp_nonce_url(
				admin_url( 'plugins.php?action=deactivate&plugin=' . rawurlencode( plugin_basename( __FILE__ ) ) ),
				'deactivate-plugin_' . plugin_basename( __FILE__ )
			);

			printf(
				'<div class="notice notice-info"><p>%s</p><p><a href="%s" class="button">%s</a></p></div>',
				esc_html__( 'The AI Client plugin is no longer needed. WordPress now includes the AI client natively.', 'wp-ai-client' ),
				esc_url( $deactivate_url ),
				esc_html__( 'Deactivate AI Client plugin', 'wp-ai-client' )
			);
		}
	);
	return;
}

require_once __DIR__ . '/vendor/autoload.php';

add_action( 'init', array( WordPress\AI_Client\AI_Client::class, 'init' ) );
