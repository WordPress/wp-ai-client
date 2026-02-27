<?php
/**
 * Global functions for the WordPress AI Client plugin.
 *
 * @package WordPress\AI_Client
 */

/**
 * Checks whether the current WordPress version natively provides the AI client.
 *
 * @since n.e.x.t
 * @access private
 *
 * @return bool True if WordPress 7.0+ is present with a native AI client.
 */
function wp_has_ai_client() {
	return function_exists( 'wp_ai_client_prompt' );
}

if ( ! wp_has_ai_client() ) {
	/**
	 * Creates a new AI prompt builder for fluent API usage, returning WP_Error on errors.
	 *
	 * This is the standard entry point for the WordPress AI Client API. It mirrors
	 * core's wp_ai_client_prompt() available in WordPress 7.0+.
	 *
	 * @since n.e.x.t
	 *
	 * @param string|null $prompt Optional initial prompt content.
	 * @return WordPress\AI_Client\Builders\Prompt_Builder_With_WP_Error The prompt builder instance.
	 */
	function wp_ai_client_prompt( $prompt = null ) {
		return new WordPress\AI_Client\Builders\Prompt_Builder_With_WP_Error( WordPress\AiClient\AiClient::defaultRegistry(), $prompt );
	}
}
