<?php
/**
 * REST Route Registrar for WordPress AI Client
 *
 * @package WordPress\AI_Client
 * @since n.e.x.t
 */

declare(strict_types=1);

namespace WordPress\AI_Client\REST;

use WordPress\AI_Client\REST\Controllers\Prompt_Controller;

/**
 * Registers REST routes for WordPress AI Client.
 *
 * This class provides a single entry point for registering all AI Client REST routes
 * under a custom namespace, allowing multiple plugins to use different versions
 * without conflicts.
 *
 * @since n.e.x.t
 */
class REST_Route_Registrar {

	/**
	 * Register all REST routes under the specified namespace.
	 *
	 * Creates individual routes for PromptBuilder result methods:
	 * - /prompt/generate-result
	 * - /prompt/generate-text-result
	 * - /prompt/generate-image-result
	 * - /prompt/generate-speech-result
	 *
	 * Shortcut methods (generate-text, generate-texts, etc.) are handled
	 * on the JavaScript side to avoid API duplication.
	 *
	 * @since n.e.x.t
	 *
	 * @param string $api_namespace The REST API namespace (e.g., 'my-plugin/v1').
	 * @return void
	 */
	public static function register_rest_routes( string $api_namespace ): void {
		$controller = new Prompt_Controller( $api_namespace );
		$controller->register_routes();
	}
}
