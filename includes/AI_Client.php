<?php
/**
 * Class WordPress\AI_Client\AI_Client
 *
 * @since n.e.x.t
 * @package wp-ai-client
 */

namespace WordPress\AI_Client;

use WordPress\AI_Client\API_Credentials\API_Credentials_Manager;
use WordPress\AI_Client\Builders\Prompt_Builder;
use WordPress\AI_Client\Builders\Prompt_Builder_With_WP_Error;
use WordPress\AI_Client\HTTP\WP_AI_Client_Discovery_Strategy;
use WordPress\AiClient\AiClient;

/**
 * Main AI Client class providing fluent APIs for AI operations.
 *
 * @since n.e.x.t
 *
 * @phpstan-import-type Prompt from Prompt_Builder
 */
class AI_Client {

	/**
	 * Indicates whether the AI Client package has been initialized.
	 *
	 * @since n.e.x.t
	 * @var bool
	 */
	private static bool $initialized = false;

	/**
	 * Initializes the AI Client package.
	 *
	 * This method needs to be called by the consumer of this package, on the WordPress 'init' action hook.
	 *
	 * @since n.e.x.t
	 */
	public static function init(): void {
		if ( self::$initialized ) {
			return;
		}

		// Wire up the WordPress HTTP client with the PHP AI Client SDK.
		WP_AI_Client_Discovery_Strategy::init();

		// Initialize the API credentials manager and settings screen.
		$api_credentials_manager = new API_Credentials_Manager();
		$api_credentials_manager->initialize();

		self::$initialized = true;
	}

	/**
	 * Creates a new prompt builder for fluent API usage.
	 *
	 * @since n.e.x.t
	 *
	 * @param Prompt $prompt Optional initial prompt content.
	 * @return Prompt_Builder The prompt builder instance.
	 */
	public static function prompt( $prompt = null ): Prompt_Builder {
		if ( ! self::$initialized ) {
			_doing_it_wrong(
				__METHOD__,
				esc_html__( 'You must call AI_Client::init() on the WordPress "init" action hook before using the AI Client.', 'wp-ai-client' ),
				'n.e.x.t'
			);
		}
		return new Prompt_Builder( AiClient::defaultRegistry(), $prompt );
	}

	/**
	 * Creates a new prompt builder for fluent API usage, returning WP_Error on errors.
	 *
	 * @since n.e.x.t
	 *
	 * @param Prompt $prompt Optional initial prompt content.
	 * @return Prompt_Builder_With_WP_Error The prompt builder instance.
	 */
	public static function prompt_with_wp_error( $prompt = null ): Prompt_Builder_With_WP_Error {
		if ( ! self::$initialized ) {
			_doing_it_wrong(
				__METHOD__,
				esc_html__( 'You must call AI_Client::init() on the WordPress "init" action hook before using the AI Client.', 'wp-ai-client' ),
				'n.e.x.t'
			);
		}
		return new Prompt_Builder_With_WP_Error( AiClient::defaultRegistry(), $prompt );
	}
}
