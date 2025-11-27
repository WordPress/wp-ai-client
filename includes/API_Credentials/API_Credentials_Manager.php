<?php
/**
 * Class WordPress\AI_Client\API_Credentials\API_Credentials_Manager
 *
 * @since 0.1.0
 * @package WordPress\AI_Client
 */

namespace WordPress\AI_Client\API_Credentials;

use RuntimeException;
use WordPress\AiClient\AiClient;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\Http\DTO\ApiKeyRequestAuthentication;

/**
 * Class for managing the AI API credentials for the various providers.
 *
 * @since 0.1.0
 *
 * Note about PHPStan: Technically, we want `ProviderExtendedMetadataArrayShape` to be the intersection of
 * `ProviderMetadataArrayShape` and the additional `ai_client_classnames` field. However, PHPStan does not seem to
 * support that, so we manually redeclare the entire array shape below. The references to `ProviderMetadataArrayShape`
 * and `ProviderAiClientMetadataArrayShape` are present for documentation purposes only.
 *
 * @phpstan-import-type ProviderMetadataArrayShape from ProviderMetadata
 * @phpstan-type ProviderAiClientMetadataArrayShape array{
 *     ai_client_classnames: array<string, bool>
 * }
 *
 * @phpstan-type ProviderExtendedMetadataArrayShape array{
 *     id: string,
 *     name: string,
 *     type: string,
 *     credentialsUrl?: ?string,
 *     ai_client_classnames: array<string, bool>
 * }
 */
class API_Credentials_Manager {

	private const OPTION_GROUP                = 'wp-ai-client-settings';
	private const OPTION_PROVIDER_CREDENTIALS = 'wp_ai_client_provider_credentials';

	/**
	 * Initializes the API credentials manager.
	 *
	 * This method needs to be called by the consumer of this package, on the WordPress 'init' action hook.
	 *
	 * @since 0.1.0
	 */
	public function initialize(): void {
		$this->collect_providers();
		$this->register_settings();
		$this->pass_credentials_to_client();

		add_action(
			'admin_menu',
			function () {
				$this->add_admin_screen();
			}
		);
	}

	/**
	 * Collects metadata for all registered providers in the PHP AI Client SDK.
	 *
	 * Since the PHP AI Client SDK as well as the WordPress AI Client package can be loaded multiple times,
	 * including with different namespace or class name prefixes, this method ensures that the provider metadata is
	 * collected only once across all instances of the package.
	 *
	 * This unified collection mechanism allows the WordPress AI Client package to expose a single settings screen in
	 * WordPress for managing API credentials for all providers, regardless of how many times the package is loaded and
	 * regardless of whether a provider is only registered in one of the instances.
	 *
	 * To safely do that, the method uses a global variable. It stores the provider metadata array keyed by the
	 * provider ID, and for each provider metadata it also stores a map of the AiClient class names where the provider
	 * is registered in.
	 *
	 * @since 0.1.0
	 *
	 * @throws RuntimeException If the collected provider metadata is in an invalid format.
	 */
	private function collect_providers(): void {
		/**
		 * The internal global, to collect providers metadata across duplicate clients, including prefixed versions.
		 *
		 * @var array<string, ProviderExtendedMetadataArrayShape>|null $wp_ai_client_providers_metadata
		 */
		global $wp_ai_client_providers_metadata;

		if ( ! isset( $wp_ai_client_providers_metadata ) ) {
			$wp_ai_client_providers_metadata = array();
		}

		$registry = AiClient::defaultRegistry();

		$provider_ids = $registry->getRegisteredProviderIds();
		foreach ( $provider_ids as $provider_id ) {
			// If the provider was already found via another client class, just add this client class name to the list.
			if ( isset( $wp_ai_client_providers_metadata[ $provider_id ] ) ) {
				if ( ! is_array( $wp_ai_client_providers_metadata[ $provider_id ]['ai_client_classnames'] ) ) {
					throw new RuntimeException( 'Invalid format for collected provider AI client class names.' );
				}
				$wp_ai_client_providers_metadata[ $provider_id ]['ai_client_classnames'][ AiClient::class ] = true;
				continue;
			}

			// Otherwise, get the provider metadata and add it to the global.
			$provider_class_name = $registry->getProviderClassName( $provider_id );

			/**
			 * The provider metadata.
			 *
			 * @var ProviderMetadata
			 */
			$provider_metadata = $provider_class_name::metadata();

			$wp_ai_client_providers_metadata[ $provider_id ] = array_merge(
				$provider_metadata->toArray(),
				array(
					'ai_client_classnames' => array( AiClient::class => true ),
				)
			);
		}
	}

	/**
	 * Returns the metadata for all registered providers across all instances of the PHP AI Client SDK.
	 *
	 * See {@see API_Credentials_Manager::collect_providers()} for details on how this works and why it uses a global.
	 *
	 * @since 0.1.0
	 * @see API_Credentials_Manager::collect_providers()
	 *
	 * @return array<string, ProviderMetadata> Array of provider metadata objects, keyed by provider ID.
	 */
	private function get_all_providers_metadata(): array {
		/**
		 * The internal global, to collect providers metadata across duplicate clients, including prefixed versions.
		 *
		 * @var ?array<string, ProviderExtendedMetadataArrayShape> $wp_ai_client_providers_metadata
		 */
		global $wp_ai_client_providers_metadata;

		if ( ! isset( $wp_ai_client_providers_metadata ) ) {
			$wp_ai_client_providers_metadata = array();
		}

		return array_map(
			static function ( array $provider_metadata ) {
				unset( $provider_metadata['ai_client_classnames'] );
				return ProviderMetadata::fromArray( $provider_metadata );
			},
			$wp_ai_client_providers_metadata
		);
	}

	/**
	 * Returns the metadata for all registered cloud providers across all instances of the PHP AI Client SDK.
	 *
	 * @since 0.1.0
	 *
	 * @return array<string, ProviderMetadata> Array of cloud provider metadata objects, keyed by provider ID.
	 */
	private function get_all_cloud_providers_metadata(): array {
		$all_providers = $this->get_all_providers_metadata();

		return array_filter(
			$all_providers,
			static function ( ProviderMetadata $metadata ) {
				return $metadata->getType()->isCloud();
			}
		);
	}

	/**
	 * Registers the settings for storing the API credentials.
	 *
	 * The setting will only be registered once, even if the class is used multiple times.
	 *
	 * @since 0.1.0
	 */
	private function register_settings(): void {
		// Avoid registering the setting multiple times.
		$registered_settings = get_registered_settings();
		if ( isset( $registered_settings[ self::OPTION_PROVIDER_CREDENTIALS ] ) ) {
			return;
		}

		register_setting(
			self::OPTION_GROUP,
			self::OPTION_PROVIDER_CREDENTIALS,
			array(
				'type'              => 'object',
				'default'           => array(),
				'sanitize_callback' => function ( $credentials ) {
					if ( ! is_array( $credentials ) ) {
						return array();
					}

					// Assume that all cloud providers require an API key.
					$providers_metadata_keyed_by_ids = $this->get_all_cloud_providers_metadata();

					$credentials = array_intersect_key( $credentials, $providers_metadata_keyed_by_ids );
					foreach ( $credentials as $provider_id => $api_key ) {
						if ( ! is_string( $api_key ) ) {
							unset( $credentials[ $provider_id ] );
							continue;
						}
						$credentials[ $provider_id ] = sanitize_text_field( $api_key );
					}
					return $credentials;
				},
			)
		);
	}

	/**
	 * Passes the stored API credentials to the PHP AI Client SDK.
	 *
	 * This method should be called on every request, before any API requests are made via the PHP AI Client SDK.
	 *
	 * @since 0.1.0
	 *
	 * @throws RuntimeException If the stored credentials option is in an invalid format.
	 */
	private function pass_credentials_to_client(): void {
		$credentials = get_option( self::OPTION_PROVIDER_CREDENTIALS, array() );
		if ( ! is_array( $credentials ) ) {
			throw new RuntimeException( 'Invalid format for stored provider credentials option.' );
		}

		$registry = AiClient::defaultRegistry();

		// Set available API keys for all registered providers.
		foreach ( $credentials as $provider_id => $api_key ) {
			if ( ! is_string( $api_key ) || '' === $api_key ) {
				continue;
			}

			if ( ! $registry->hasProvider( $provider_id ) ) {
				continue;
			}

			$registry->setProviderRequestAuthentication(
				$provider_id,
				new ApiKeyRequestAuthentication( $api_key )
			);
		}
	}

	/**
	 * Adds the admin screen for managing API credentials.
	 *
	 * The screen will only be added once, even if the class is used multiple times.
	 *
	 * @since 0.1.0
	 */
	private function add_admin_screen(): void {
		global $_wp_submenu_nopriv, $_parent_pages;

		$parent_slug = 'options-general.php'; // Used via `add_options_page()`.
		$screen_slug = 'wp-ai-client';

		// Bail if the screen was already added (e.g. by another instance of this package).
		if (
			(
				is_array( $_wp_submenu_nopriv ) &&
				isset( $_wp_submenu_nopriv[ $parent_slug ] ) &&
				is_array( $_wp_submenu_nopriv[ $parent_slug ] ) &&
				isset( $_wp_submenu_nopriv[ $parent_slug ][ $screen_slug ] )
			) ||
			(
				is_array( $_parent_pages ) &&
				isset( $_parent_pages[ $screen_slug ] )
			)
		) {
			return;
		}

		$screen_title = __( 'AI Client Credentials', 'wp-ai-client' );

		$settings_screen = new API_Credentials_Settings_Screen(
			$screen_slug,
			$screen_title,
			__( 'Paste your API credentials for one or more AI providers you would like to use throughout your site.', 'wp-ai-client' ),
			self::OPTION_GROUP,
			self::OPTION_PROVIDER_CREDENTIALS,
			$this->get_all_cloud_providers_metadata()
		);

		$hook_suffix = add_options_page(
			$screen_title,
			__( 'AI Credentials', 'wp-ai-client' ),
			'manage_options',
			$screen_slug,
			array( $settings_screen, 'render_screen' )
		);

		if ( ! is_string( $hook_suffix ) ) {
			return;
		}

		add_action(
			"load-{$hook_suffix}",
			array( $settings_screen, 'initialize_screen' )
		);
	}
}
