<?php
/**
 * Class WordPress\AI_Client\REST_API\AI_Prompt_REST_Controller
 *
 * @since 0.2.0
 * @package WordPress\AI_Client
 */

namespace WordPress\AI_Client\REST_API;

use Exception;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WordPress\AI_Client\AI_Client;
use WordPress\AI_Client\Builders\Prompt_Builder;
use WordPress\AI_Client\Capabilities\Capabilities_Manager;
use WordPress\AiClient\AiClient;
use WordPress\AiClient\Messages\DTO\Message;
use WordPress\AiClient\Providers\Http\DTO\RequestOptions;
use WordPress\AiClient\Providers\Models\Contracts\ModelInterface;
use WordPress\AiClient\Providers\Models\DTO\ModelConfig;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;
use WordPress\AiClient\Results\DTO\GenerativeAiResult;

/**
 * REST Controller for AI operations.
 *
 * @since 0.2.0
 *
 * @phpstan-import-type MessageArrayShape from Message
 * @phpstan-import-type ModelConfigArrayShape from ModelConfig
 * @phpstan-import-type RequestOptionsArrayShape from RequestOptions
 * @phpstan-type GenerationRequestParams array{
 *   messages: list<MessageArrayShape>,
 *   modelConfig?: ModelConfigArrayShape,
 *   providerId?: string,
 *   modelId?: string,
 *   modelPreferences?: list<string|array{0: string, 1: string}>,
 *   capability?: string,
 *   requestOptions?: RequestOptionsArrayShape
 * }
 */
class AI_Prompt_REST_Controller {

	/**
	 * Registers the REST routes.
	 *
	 * @since 0.2.0
	 */
	public function register_routes(): void {
		$generation_request_schema = $this->get_generation_request_schema();

		register_rest_route(
			'wp-ai/v1',
			'/generate',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'process_generate_request' ),
					'permission_callback' => array( $this, 'permissions_check' ),
					'args'                => $generation_request_schema['properties'],
				),
				'schema' => array( $this, 'get_generation_result_schema' ),
			)
		);

		register_rest_route(
			'wp-ai/v1',
			'/is-supported',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'process_is_supported_request' ),
					'permission_callback' => array( $this, 'permissions_check' ),
					'args'                => $generation_request_schema['properties'],
				),
				'schema' => array( $this, 'get_is_supported_schema' ),
			)
		);
	}

	/**
	 * Checks if the user has permission to prompt AI models.
	 *
	 * @since 0.2.0
	 *
	 * @return bool|WP_Error True if authorized, WP_Error otherwise.
	 */
	public function permissions_check() {
		if ( current_user_can( Capabilities_Manager::PROMPT_AI_CAPABILITY ) ) {
			return true;
		}

		return new WP_Error(
			'rest_forbidden',
			__( 'Sorry, you are not allowed to prompt AI models directly.', 'wp-ai-client' ),
			array( 'status' => rest_authorization_required_code() )
		);
	}

	/**
	 * Generates content using an AI model.
	 *
	 * @since 0.2.0
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response|WP_Error The response object or error.
	 *
	 * @phpstan-param WP_REST_Request<GenerationRequestParams> $request
	 */
	public function process_generate_request( WP_REST_Request $request ) {
		// phpcs:ignore Generic.Commenting.DocComment.MissingShort
		/** @var GenerationRequestParams $params */
		$params = $request->get_json_params();

		try {
			$builder = $this->create_builder_from_params( $params );

			$capability = null;
			if ( ! empty( $params['capability'] ) ) {
				$capability = CapabilityEnum::tryFrom( (string) $params['capability'] );
			}

			$result = $builder->generate_result( $capability );

			return new WP_REST_Response( $result, 200 );
		} catch ( Exception $e ) {
			return new WP_Error( 'ai_generate_error', $e->getMessage(), array( 'status' => 500 ) );
		}
	}

	/**
	 * Checks if the prompt and its configuration is supported by any available AI models.
	 *
	 * @since 0.2.0
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response|WP_Error The response object or error.
	 *
	 * @phpstan-param WP_REST_Request<GenerationRequestParams> $request
	 */
	public function process_is_supported_request( WP_REST_Request $request ) {
		// phpcs:ignore Generic.Commenting.DocComment.MissingShort
		/** @var GenerationRequestParams $params */
		$params = $request->get_json_params();

		try {
			$builder = $this->create_builder_from_params( $params );

			// Check specific capability if provided.
			if ( ! empty( $params['capability'] ) ) {
				$capability = CapabilityEnum::tryFrom( (string) $params['capability'] );
				if ( ! $capability ) {
					return new WP_Error(
						'ai_invalid_capability',
						__( 'Invalid capability.', 'wp-ai-client' ),
						array( 'status' => 400 )
					);
				}

				$supported = $builder->is_supported( $capability );
				return new WP_REST_Response( array( 'supported' => $supported ), 200 );
			}

			$supported = $builder->is_supported();
			return new WP_REST_Response( array( 'supported' => $supported ), 200 );
		} catch ( Exception $e ) {
			return new WP_Error( 'ai_is_supported_error', $e->getMessage(), array( 'status' => 500 ) );
		}
	}

	/**
	 * Retrieves the generation request schema.
	 *
	 * @since 0.2.0
	 *
	 * @return array<string, mixed> The request schema.
	 */
	public function get_generation_request_schema(): array {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'ai_generation_request',
			'type'       => 'object',
			'properties' => array(
				'messages'         => array(
					'description' => __( 'The messages to generate content from.', 'wp-ai-client' ),
					'type'        => 'array',
					'items'       => JSON_Schema_To_WP_Schema_Converter::convert( Message::getJsonSchema() ),
					'required'    => true,
					'minItems'    => 1,
				),
				'modelConfig'      => JSON_Schema_To_WP_Schema_Converter::convert( ModelConfig::getJsonSchema() ),
				'providerId'       => array(
					'description' => __( 'The provider ID, to enforce using a model from that provider.', 'wp-ai-client' ),
					'type'        => 'string',
				),
				'modelId'          => array(
					'description' => __( 'The model ID, to enforce using that model. If given, a providerId must also be present.', 'wp-ai-client' ),
					'type'        => 'string',
				),
				'modelPreferences' => array(
					'description' => __( 'List of preferred models.', 'wp-ai-client' ),
					'type'        => 'array',
					'items'       => array(
						'oneOf' => array(
							array(
								'type' => 'string',
							),
							array(
								'type'     => 'array',
								'items'    => array(
									'type' => 'string',
								),
								'minItems' => 2,
								'maxItems' => 2,
							),
						),
					),
				),
				'capability'       => array(
					'description' => __( 'The capability to use.', 'wp-ai-client' ),
					'type'        => 'string',
					'enum'        => CapabilityEnum::getValues(),
				),
				'requestOptions'   => JSON_Schema_To_WP_Schema_Converter::convert( RequestOptions::getJsonSchema() ),
			),
		);
	}

	/**
	 * Retrieves the generation result schema.
	 *
	 * @since 0.2.0
	 *
	 * @return array<string, mixed> The result schema.
	 */
	public function get_generation_result_schema(): array {
		$schema            = GenerativeAiResult::getJsonSchema();
		$schema['$schema'] = 'http://json-schema.org/draft-04/schema#';
		$schema['title']   = 'ai_generation_result';

		return JSON_Schema_To_WP_Schema_Converter::convert( $schema );
	}

	/**
	 * Retrieves the supported check schema.
	 *
	 * @since 0.2.0
	 *
	 * @return array<string, mixed> The supported check schema.
	 */
	public function get_is_supported_schema(): array {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'ai_is_supported_response',
			'type'       => 'object',
			'properties' => array(
				'supported' => array(
					'description' => __( 'Whether the capability is supported.', 'wp-ai-client' ),
					'type'        => 'boolean',
					'required'    => true,
				),
			),
		);
	}

	/**
	 * Helper to create builder from params.
	 *
	 * @param array<string, mixed> $params The parameters.
	 * @phpstan-param GenerationRequestParams $params
	 * @return Prompt_Builder The builder instance.
	 */
	private function create_builder_from_params( array $params ): Prompt_Builder {
		// Messages are required by schema.
		$messages_data = $params['messages'];

		$messages = array_map(
			fn( $message ) => Message::fromArray( $message ),
			$messages_data
		);

		$builder = AI_Client::prompt( array_values( $messages ) );

		if ( ! empty( $params['modelConfig'] ) && is_array( $params['modelConfig'] ) ) {
			$model_config_data = $params['modelConfig'];
			$config            = ModelConfig::fromArray( $model_config_data );
			$builder->using_model_config( $config );
		}

		// If both providerId and modelId are provided, this model must be used.
		if ( ! empty( $params['providerId'] ) && ! empty( $params['modelId'] ) ) {
			$provider_id = (string) $params['providerId'];
			$model_id    = (string) $params['modelId'];

			$provider_class_name = AiClient::defaultRegistry()->getProviderClassName( $provider_id );

			// phpcs:ignore Generic.Commenting.DocComment.MissingShort
			/** @var ModelInterface $model */
			$model = $provider_class_name::model( $model_id );

			return $builder->using_model( $model );
		}

		if ( ! empty( $params['providerId'] ) ) {
			$builder->using_provider( (string) $params['providerId'] );
		}

		if ( ! empty( $params['modelPreferences'] ) && is_array( $params['modelPreferences'] ) ) {
			$builder->using_model_preference( ...$params['modelPreferences'] );
		}

		if ( ! empty( $params['requestOptions'] ) && is_array( $params['requestOptions'] ) ) {
			$request_options = RequestOptions::fromArray( $params['requestOptions'] );
			$builder->using_request_options( $request_options );
		}

		return $builder;
	}
}
