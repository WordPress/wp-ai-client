<?php
/**
 * Class WordPress\AI_Client\REST_API\AI_Providers_Models_REST_Controller
 *
 * @since n.e.x.t
 * @package WordPress\AI_Client
 */

namespace WordPress\AI_Client\REST_API;

use Exception;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WordPress\AI_Client\AI_Client;
use WordPress\AI_Client\Capabilities\Capabilities_Manager;
use WordPress\AiClient\AiClient;
use WordPress\AiClient\Providers\Contracts\ModelMetadataDirectoryInterface;
use WordPress\AiClient\Providers\Contracts\ProviderAvailabilityInterface;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;

/**
 * REST Controller for AI Providers and Models.
 *
 * @since n.e.x.t
 *
 * @phpstan-type ProviderRequestParams array{
 *   providerId: string
 * }
 *
 * @phpstan-type ProviderModelRequestParams array{
 *   providerId: string,
 *   modelId: string
 * }
 */
class AI_Providers_Models_REST_Controller {

	/**
	 * Registers the REST routes.
	 *
	 * @since n.e.x.t
	 */
	public function register_routes(): void {
		register_rest_route(
			AI_Client::REST_NAMESPACE,
			'/providers',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'process_get_providers_request' ),
					'permission_callback' => array( $this, 'permissions_check_providers' ),
				),
				'schema' => array( $this, 'get_provider_schema' ),
			)
		);

		register_rest_route(
			AI_Client::REST_NAMESPACE,
			'/providers/(?P<providerId>[^/]+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'process_get_provider_request' ),
					'permission_callback' => array( $this, 'permissions_check_providers' ),
				),
				'args'   => array(
					'providerId' => array(
						'description' => __( 'The provider ID.', 'wp-ai-client' ),
						'type'        => 'string',
					),
				),
				'schema' => array( $this, 'get_provider_schema' ),
			)
		);

		register_rest_route(
			AI_Client::REST_NAMESPACE,
			'/providers/(?P<providerId>[^/]+)/models',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'process_get_models_request' ),
					'permission_callback' => array( $this, 'permissions_check_models' ),
				),
				'schema' => array( $this, 'get_model_schema' ),
			)
		);

		register_rest_route(
			AI_Client::REST_NAMESPACE,
			'/providers/(?P<providerId>[^/]+)/models/(?P<modelId>[^/]+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'process_get_model_request' ),
					'permission_callback' => array( $this, 'permissions_check_models' ),
				),
				'args'   => array(
					'providerId' => array(
						'description' => __( 'The provider ID.', 'wp-ai-client' ),
						'type'        => 'string',
					),
					'modelId'    => array(
						'description' => __( 'The model ID.', 'wp-ai-client' ),
						'type'        => 'string',
					),
				),
				'schema' => array( $this, 'get_model_schema' ),
			)
		);
	}

	/**
	 * Checks if the user has permission to list AI providers.
	 *
	 * @since n.e.x.t
	 *
	 * @return bool|WP_Error True if authorized, WP_Error otherwise.
	 */
	public function permissions_check_providers() {
		if ( current_user_can( Capabilities_Manager::LIST_AI_PROVIDERS_CAPABILITY ) ) {
			return true;
		}

		return new WP_Error(
			'rest_forbidden',
			__( 'Sorry, you are not allowed to list AI providers.', 'wp-ai-client' ),
			array( 'status' => rest_authorization_required_code() )
		);
	}

	/**
	 * Checks if the user has permission to list AI models.
	 *
	 * @since n.e.x.t
	 *
	 * @return bool|WP_Error True if authorized, WP_Error otherwise.
	 */
	public function permissions_check_models() {
		if ( current_user_can( Capabilities_Manager::LIST_AI_MODELS_CAPABILITY ) ) {
			return true;
		}

		return new WP_Error(
			'rest_forbidden',
			__( 'Sorry, you are not allowed to list AI models.', 'wp-ai-client' ),
			array( 'status' => rest_authorization_required_code() )
		);
	}

	/**
	 * Retrieves a list of AI providers.
	 *
	 * @since n.e.x.t
	 *
	 * @return WP_REST_Response|WP_Error The response object or error.
	 */
	public function process_get_providers_request() {
		$registry = AiClient::defaultRegistry();

		$provider_ids              = $registry->getRegisteredProviderIds();
		$provider_metadata_objects = array_map(
			function ( $id ) use ( $registry ) {
				$classname = $registry->getProviderClassName( $id );
				return $classname::metadata();
			},
			$provider_ids
		);

		return new WP_REST_Response( $provider_metadata_objects, 200 );
	}

	/**
	 * Retrieves a specific AI provider.
	 *
	 * @since n.e.x.t
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response|WP_Error The response object or error.
	 *
	 * @phpstan-param WP_REST_Request<ProviderRequestParams> $request
	 */
	public function process_get_provider_request( WP_REST_Request $request ) {
		// phpcs:ignore Generic.Commenting.DocComment.MissingShort
		/** @var string $provider_id */
		$provider_id = $request['providerId'];
		$registry    = AiClient::defaultRegistry();

		if ( ! $registry->hasProvider( $provider_id ) ) {
			return new WP_Error(
				'rest_not_found',
				__( 'AI provider not found.', 'wp-ai-client' ),
				array( 'status' => 404 )
			);
		}

		$provider_classname = $registry->getProviderClassName( $provider_id );
		$provider_metadata  = $provider_classname::metadata();

		return new WP_REST_Response( $provider_metadata, 200 );
	}

	/**
	 * Retrieves a list of models for a specific provider.
	 *
	 * @since n.e.x.t
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response|WP_Error The response object or error.
	 *
	 * @phpstan-param WP_REST_Request<ProviderRequestParams> $request
	 */
	public function process_get_models_request( WP_REST_Request $request ) {
		// phpcs:ignore Generic.Commenting.DocComment.MissingShort
		/** @var string $provider_id */
		$provider_id = $request['providerId'];
		$registry    = AiClient::defaultRegistry();

		if ( ! $registry->hasProvider( $provider_id ) ) {
			return new WP_Error(
				'rest_not_found',
				__( 'AI provider not found.', 'wp-ai-client' ),
				array( 'status' => 404 )
			);
		}

		$provider_classname = $registry->getProviderClassName( $provider_id );

		try {
			// phpcs:ignore Generic.Commenting.DocComment.MissingShort
			/** @var ProviderAvailabilityInterface $provider_availability */
			$provider_availability = $provider_classname::availability();
			if ( ! $provider_availability->isConfigured() ) {
				return new WP_Error(
					'ai_provider_not_configured',
					__( 'AI provider not configured - missing API credentials.', 'wp-ai-client' ),
					array( 'status' => 400 )
				);
			}

			// phpcs:ignore Generic.Commenting.DocComment.MissingShort
			/** @var ModelMetadataDirectoryInterface $model_metadata_directory */
			$model_metadata_directory = $provider_classname::modelMetadataDirectory();
			$model_metadata_objects   = $model_metadata_directory->listModelMetadata();

			return new WP_REST_Response( $model_metadata_objects, 200 );
		} catch ( Exception $e ) {
			return new WP_Error(
				'ai_list_models_error',
				sprintf(
					/* translators: %s: Error message. */
					__( 'Could not list models for provider - are the API credentials invalid? Error: %s', 'wp-ai-client' ),
					$e->getMessage()
				),
				array( 'status' => 500 )
			);
		}
	}

	/**
	 * Retrieves a specific model for a specific provider.
	 *
	 * @since n.e.x.t
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response|WP_Error The response object or error.
	 *
	 * @phpstan-param WP_REST_Request<ProviderModelRequestParams> $request
	 */
	public function process_get_model_request( WP_REST_Request $request ) {
		// phpcs:ignore Generic.Commenting.DocComment.MissingShort
		/** @var string $provider_id */
		$provider_id = $request['providerId'];

		// phpcs:ignore Generic.Commenting.DocComment.MissingShort
		/** @var string $model_id */
		$model_id = $request['modelId'];

		// phpcs:ignore Generic.Commenting.DocComment.MissingShort
		/** @var WP_REST_Request<array{providerId: string}> $sub_request */
		$sub_request = new WP_REST_Request( 'GET', '/wp-ai/v1/providers/' . $provider_id . '/models' );
		$sub_request->set_url_params( array( 'providerId' => $provider_id ) );

		$get_models_response = $this->process_get_models_request( $sub_request );
		if ( is_wp_error( $get_models_response ) ) {
			return $get_models_response;
		}

		// phpcs:ignore Generic.Commenting.DocComment.MissingShort
		/** @var list<ModelMetadata> $models_metadata_objects */
		$models_metadata_objects = $get_models_response->get_data();
		foreach ( $models_metadata_objects as $model_metadata ) {
			if ( $model_metadata->getId() === $model_id ) {
				return new WP_REST_Response( $model_metadata, 200 );
			}
		}

		return new WP_Error(
			'rest_not_found',
			__( 'AI model not found.', 'wp-ai-client' ),
			array( 'status' => 404 )
		);
	}

	/**
	 * Retrieves the provider schema.
	 *
	 * @since n.e.x.t
	 *
	 * @return array<string, mixed> The provider schema.
	 */
	public function get_provider_schema(): array {
		$schema            = ProviderMetadata::getJsonSchema();
		$schema['$schema'] = 'http://json-schema.org/draft-04/schema#';
		$schema['title']   = 'ai_provider';

		return JSON_Schema_To_WP_Schema_Converter::convert( $schema );
	}

	/**
	 * Retrieves the model schema.
	 *
	 * @since n.e.x.t
	 *
	 * @return array<string, mixed> The model schema.
	 */
	public function get_model_schema(): array {
		$schema            = ModelMetadata::getJsonSchema();
		$schema['$schema'] = 'http://json-schema.org/draft-04/schema#';
		$schema['title']   = 'ai_model';

		return JSON_Schema_To_WP_Schema_Converter::convert( $schema );
	}
}
