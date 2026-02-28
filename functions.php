<?php
/**
 * Global functions for the WordPress AI Client package.
 *
 * @package WordPress\AI_Client
 */

if ( ! function_exists( 'wp_has_ai_client' ) ) {
	/**
	 * Checks whether the current WordPress version natively provides the AI client.
	 *
	 * @since n.e.x.t
	 * @access private
	 *
	 * @return bool True if WordPress 7.0+ is present with a native AI client.
	 */
	function wp_has_ai_client() {
		return function_exists( 'wp_get_wp_version' ) && version_compare( wp_get_wp_version(), '7.0-alpha', '>=' );
	}
}

if ( ! function_exists( 'wp_ai_client_prompt' ) ) {
	/**
	 * Creates a new AI prompt builder for fluent API usage, returning WP_Error on errors.
	 *
	 * This is the standard entry point for the WordPress AI Client API. It mirrors
	 * core's wp_ai_client_prompt() available in WordPress 7.0+.
	 *
	 * @since n.e.x.t
	 *
	 * @param string|array<int, mixed>|null $prompt Optional initial prompt content.
	 * @return WordPress\AI_Client\Builders\Prompt_Builder_With_WP_Error The prompt builder instance.
	 */
	function wp_ai_client_prompt( $prompt = null ) {
		return new WordPress\AI_Client\Builders\Prompt_Builder_With_WP_Error( WordPress\AiClient\AiClient::defaultRegistry(), $prompt );
	}
}
