<?php
/**
 * Class WordPress\AI_Client\AI_Client
 *
 * @since 0.1.0
 * @package WordPress\AI_Client
 */

namespace WordPress\AI_Client;

use WordPress\AI_Client\API_Credentials\API_Credentials_Manager;
use WordPress\AI_Client\Builders\Prompt_Builder;
use WordPress\AI_Client\Builders\Prompt_Builder_With_WP_Error;
use WordPress\AI_Client\Capabilities\Capabilities_Manager;
use WordPress\AI_Client\HTTP\WP_AI_Client_Discovery_Strategy;
use WordPress\AI_Client\REST_API\AI_Prompt_REST_Controller;
use WordPress\AiClient\AiClient;

/**
 * Main AI Client class providing fluent APIs for AI operations.
 *
 * @since 0.1.0
 *
 * @phpstan-import-type Prompt from Prompt_Builder
 *
 * @phpstan-type ScriptMetadata array{
 *   dependencies: list<string>,
 *   version: string
 * }
 */
class AI_Client {

	/**
	 * Indicates whether the AI Client package has been initialized.
	 *
	 * @since 0.1.0
	 * @var bool
	 */
	private static bool $initialized = false;

	/**
	 * Initializes the AI Client package.
	 *
	 * This method needs to be called by the consumer of this package, on the WordPress 'init' action hook.
	 *
	 * @since 0.1.0
	 */
	public static function init(): void {
		if ( self::$initialized ) {
			return;
		}

		// Wire up the WordPress HTTP client with the PHP AI Client SDK.
		WP_AI_Client_Discovery_Strategy::init();

		// Initialize capabilities.
		add_filter( 'user_has_cap', array( Capabilities_Manager::class, 'grant_prompt_ai_to_administrators' ) );

		// Register client-side API script.
		self::register_client_side_api_script();

		// Initialize the API credentials manager and settings screen.
		( new API_Credentials_Manager() )->initialize();

		// Register REST API routes.
		add_action(
			'rest_api_init',
			static function () {
				( new AI_Prompt_REST_Controller() )->register_routes();
			}
		);

		self::$initialized = true;
	}

	/**
	 * Creates a new prompt builder for fluent API usage.
	 *
	 * @since 0.1.0
	 *
	 * @param Prompt $prompt Optional initial prompt content.
	 * @return Prompt_Builder The prompt builder instance.
	 */
	public static function prompt( $prompt = null ): Prompt_Builder {
		if ( ! self::$initialized ) {
			_doing_it_wrong(
				__METHOD__,
				esc_html__( 'You must call AI_Client::init() on the WordPress "init" action hook before using the AI Client.', 'wp-ai-client' ),
				'0.1.0'
			);
		}
		return new Prompt_Builder( AiClient::defaultRegistry(), $prompt );
	}

	/**
	 * Creates a new prompt builder for fluent API usage, returning WP_Error on errors.
	 *
	 * @since 0.1.0
	 *
	 * @param Prompt $prompt Optional initial prompt content.
	 * @return Prompt_Builder_With_WP_Error The prompt builder instance.
	 */
	public static function prompt_with_wp_error( $prompt = null ): Prompt_Builder_With_WP_Error {
		if ( ! self::$initialized ) {
			_doing_it_wrong(
				__METHOD__,
				esc_html__( 'You must call AI_Client::init() on the WordPress "init" action hook before using the AI Client.', 'wp-ai-client' ),
				'0.1.0'
			);
		}
		return new Prompt_Builder_With_WP_Error( AiClient::defaultRegistry(), $prompt );
	}

	/**
	 * Registers the JavaScript asset that loads the client-side API.
	 *
	 * @return void
	 */
	private static function register_client_side_api_script(): void {
		$script_path = dirname( __DIR__ ) . '/build/index.js';

		$script_meta_path = dirname( $script_path ) . '/index.asset.php';
		if ( file_exists( $script_meta_path ) ) {
			// phpcs:ignore Generic.Commenting.DocComment.MissingShort
			/** @var ScriptMetadata $script_meta */
			$script_meta  = require $script_meta_path;
			$dependencies = $script_meta['dependencies'];
			$version      = $script_meta['version'];
		} else {
			$dependencies = array( 'wp-api-fetch' );
			$version      = null;
		}

		wp_register_script(
			'wp-ai-client',
			str_replace( WP_CONTENT_DIR, content_url(), $script_path ),
			$dependencies,
			$version,
			array( 'strategy' => 'defer' )
		);
	}
}
