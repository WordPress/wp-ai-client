<?php
/**
 * Class WordPress\AI_Client\REST_API\AI_REST_Controller
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
 * @since n.e.x.t
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
class AI_REST_Controller {

	/**
	 * Registers the REST routes.
	 *
	 * @since n.e.x.t
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
	 * @since n.e.x.t
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
	 * @since n.e.x.t
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
	 * @since n.e.x.t
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
	 * @since n.e.x.t
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
					'items'       => $this->convert_json_schema_to_wp_schema( Message::getJsonSchema() ),
					'required'    => true,
					'minItems'    => 1,
				),
				'modelConfig'      => $this->convert_json_schema_to_wp_schema( ModelConfig::getJsonSchema() ),
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
				'requestOptions'   => $this->convert_json_schema_to_wp_schema( RequestOptions::getJsonSchema() ),
			),
		);
	}

	/**
	 * Retrieves the generation result schema.
	 *
	 * @since n.e.x.t
	 *
	 * @return array<string, mixed> The result schema.
	 */
	public function get_generation_result_schema(): array {
		$schema            = GenerativeAiResult::getJsonSchema();
		$schema['$schema'] = 'http://json-schema.org/draft-04/schema#';
		$schema['title']   = 'ai_generation_result';

		return $this->convert_json_schema_to_wp_schema( $schema );
	}

	/**
	 * Retrieves the supported check schema.
	 *
	 * @since n.e.x.t
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

		$builder = new Prompt_Builder( AiClient::defaultRegistry(), array_values( $messages ) );

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

	/**
	 * Converts a standard JSON Schema to a WordPress REST API compatible schema.
	 *
	 * Specifically, this converts the "required" array property to "required" boolean attributes
	 * on individual properties, as expected by WordPress REST API validation.
	 *
	 * @since n.e.x.t
	 *
	 * @param array<string, mixed> $schema The standard JSON schema.
	 * @return array<string, mixed> The WordPress compatible schema.
	 */
	private function convert_json_schema_to_wp_schema( array $schema ): array {
		if ( isset( $schema['properties'] ) && is_array( $schema['properties'] ) ) {
			$required_props = isset( $schema['required'] ) && is_array( $schema['required'] )
				? $schema['required']
				: array();

			// Remove the required array from the parent object.
			unset( $schema['required'] );

			foreach ( $schema['properties'] as $prop_name => $prop_schema ) {
				if ( ! is_array( $prop_schema ) ) {
					continue;
				}

				// phpcs:ignore Generic.Commenting.DocComment.MissingShort
				/** @var array<string, mixed> $prop_schema */
				$schema['properties'][ $prop_name ] = $this->convert_json_schema_to_wp_schema( $prop_schema );

				// Set required boolean if property is in required array.
				if ( in_array( $prop_name, $required_props, true ) ) {
					$schema['properties'][ $prop_name ]['required'] = true;
				}
			}
		}

		if ( isset( $schema['items'] ) && is_array( $schema['items'] ) ) {
			// phpcs:ignore Generic.Commenting.DocComment.MissingShort
			/** @var array<string, mixed> $items */
			$items = $schema['items'];

			$schema['items'] = $this->convert_json_schema_to_wp_schema( $items );
		}

		// Handle oneOf, anyOf, allOf.
		foreach ( array( 'oneOf', 'anyOf', 'allOf' ) as $combiner ) {
			if ( isset( $schema[ $combiner ] ) && is_array( $schema[ $combiner ] ) ) {
				foreach ( $schema[ $combiner ] as $index => $sub_schema ) {
					if ( ! is_array( $sub_schema ) ) {
						continue;
					}

					// phpcs:ignore Generic.Commenting.DocComment.MissingShort
					/** @var array<string, mixed> $sub_schema */
					$schema[ $combiner ][ $index ] = $this->convert_json_schema_to_wp_schema( $sub_schema );
				}
			}
		}

		return $schema;
	}
}
