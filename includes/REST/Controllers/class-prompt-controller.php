<?php
/**
 * Unified Prompt Controller for WordPress AI Client REST API
 *
 * @package WordPress\AI_Client
 * @since n.e.x.t
 */

declare(strict_types=1);

namespace WordPress\AI_Client\REST\Controllers;

use WP_Error;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WordPress\AiClient\AiClient;
use WordPress\AiClient\Builders\PromptBuilder;
use WordPress\AiClient\Messages\DTO\Message;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Providers\Models\DTO\ModelConfig;
use WordPress\AiClient\Results\DTO\GenerativeAiResult;

/**
 * Single controller handling PromptBuilder result methods via individual routes.
 *
 * This controller provides individual REST endpoints for PromptBuilder result methods:
 * - /prompt/generate-result → generateResult()
 * - /prompt/generate-text-result → generateTextResult()
 * - /prompt/generate-image-result → generateImageResult()
 * - /prompt/generate-speech-result → generateSpeechResult()
 *
 * Shortcut methods (generate-text, generate-texts, etc.) are handled on the JavaScript side
 * to avoid API duplication and reduce surface area.
 *
 * @since n.e.x.t
 */
class Prompt_Controller extends WP_REST_Controller {

	/**
	 * The namespace of this controller's route.
	 *
	 * @since n.e.x.t
	 * @var string
	 */
	protected $namespace;

	/**
	 * Constructor.
	 *
	 * @since n.e.x.t
	 *
	 * @param string $api_namespace The REST API namespace.
	 */
	public function __construct( string $api_namespace ) {
		$this->namespace = $api_namespace;
	}

	/**
	 * Register routes for PromptBuilder result methods.
	 *
	 * @since n.e.x.t
	 *
	 * @return void
	 */
	public function register_routes(): void {
		$common_args = $this->get_common_args();

		// Register routes for result methods.
		$routes = array(
			'/prompt/generate-result'        => 'generate_result',
			'/prompt/generate-text-result'   => 'generate_text_result',
			'/prompt/generate-image-result'  => 'generate_image_result',
			'/prompt/generate-speech-result' => 'generate_speech_result',
		);

		foreach ( $routes as $route => $callback ) {
			register_rest_route(
				$this->namespace,
				$route,
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, $callback ),
					'args'                => $common_args,
					'permission_callback' => array( $this, 'check_permission' ),
					'schema'              => array( $this, 'get_response_schema' ),
				)
			);
		}
	}

	/**
	 * Get common request arguments for all prompt endpoints.
	 *
	 * Leverages schemas from PHP AI Client SDK DTOs to maintain
	 * consistency with the underlying SDK data structures.
	 *
	 * @since n.e.x.t
	 *
	 * @return array<string, array<string, mixed>> Request arguments schema.
	 */
	protected function get_common_args(): array {
		return array(
			'prompt' => array(
				'required'    => true,
				'description' => 'The prompt content as string, message part, message, or array of messages',
				'oneOf'       => array(
					// String prompt
					array(
						'type'        => 'string',
						'minLength'   => 1,
						'description' => 'Simple text prompt',
					),
					// Single MessagePart DTO
					MessagePart::getJsonSchema(),
					// Single Message DTO
					Message::getJsonSchema(),
					// Array of Message DTOs
					array(
						'type'        => 'array',
						'items'       => Message::getJsonSchema(),
						'minItems'    => 1,
						'description' => 'Array of messages for conversation',
					),
				),
			),
			'config' => array_merge(
				ModelConfig::getJsonSchema(),
				array(
					'description' => 'Model configuration options',
				)
			),
		);
	}

	/**
	 * Get response schema for all prompt endpoints.
	 *
	 * All endpoints return the same response format based on GenerativeAiResult DTO,
	 * wrapped in a consistent success/error response structure.
	 *
	 * @since n.e.x.t
	 *
	 * @return array<string, mixed> Response schema.
	 */
	public function get_response_schema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'success'   => array(
					'type'        => 'boolean',
					'description' => 'Whether the request was successful',
				),
				'data'      => GenerativeAiResult::getJsonSchema(),
				'timestamp' => array(
					'type'        => 'string',
					'description' => 'Response timestamp',
				),
				'metadata'  => array(
					'type'                 => 'object',
					'description'          => 'Additional response metadata',
					'additionalProperties' => true,
				),
			),
			'required'   => array( 'success', 'data', 'timestamp' ),
		);
	}

	// =============================================================================
	// RESULT GENERATION METHODS
	// =============================================================================

	/**
	 * Handle text result generation → PromptBuilder::generateTextResult()
	 *
	 * @param WP_REST_Request $request The REST request object.
	 * @return WP_REST_Response The response object.
	 */
	public function generate_text_result( WP_REST_Request $request ): WP_REST_Response {
		try {
			$builder = $this->build_prompt_from_request( $request );
			$result  = $builder->generateTextResult();

			return $this->format_success_response(
				$this->format_generative_result( $result ),
				array(
					'type'     => 'text-result',
					'endpoint' => 'generate-text-result',
				)
			);
		} catch ( \Exception $exception ) {
			return $this->handle_generation_error( $exception );
		}
	}

	/**
	 * Handle image result generation → PromptBuilder::generateImageResult()
	 *
	 * @param WP_REST_Request $request The REST request object.
	 * @return WP_REST_Response The response object.
	 */
	public function generate_image_result( WP_REST_Request $request ): WP_REST_Response {
		try {
			$builder = $this->build_prompt_from_request( $request );
			$result  = $builder->generateImageResult();

			return $this->format_success_response(
				$this->format_generative_result( $result ),
				array(
					'type'     => 'image-result',
					'endpoint' => 'generate-image-result',
				)
			);
		} catch ( \Exception $exception ) {
			return $this->handle_generation_error( $exception );
		}
	}

	/**
	 * Handle speech result generation → PromptBuilder::generateSpeechResult()
	 *
	 * @param WP_REST_Request $request The REST request object.
	 * @return WP_REST_Response The response object.
	 */
	public function generate_speech_result( WP_REST_Request $request ): WP_REST_Response {
		try {
			$builder = $this->build_prompt_from_request( $request );
			$result  = $builder->generateSpeechResult();

			return $this->format_success_response(
				$this->format_generative_result( $result ),
				array(
					'type'     => 'speech-result',
					'endpoint' => 'generate-speech-result',
				)
			);
		} catch ( \Exception $exception ) {
			return $this->handle_generation_error( $exception );
		}
	}

	// =============================================================================
	// GENERAL GENERATION METHOD
	// =============================================================================

	/**
	 * Handle general result generation → PromptBuilder::generateResult()
	 *
	 * @param WP_REST_Request $request The REST request object.
	 * @return WP_REST_Response The response object.
	 */
	public function generate_result( WP_REST_Request $request ): WP_REST_Response {
		try {
			$builder = $this->build_prompt_from_request( $request );
			$result  = $builder->generateResult();

			return $this->format_success_response(
				$this->format_generative_result( $result ),
				array(
					'type'     => 'result',
					'endpoint' => 'generate-result',
				)
			);
		} catch ( \Exception $exception ) {
			return $this->handle_generation_error( $exception );
		}
	}

	// =============================================================================
	// SHARED HELPER METHODS
	// =============================================================================

	/**
	 * Build a PromptBuilder instance from the REST request.
	 *
	 * Uses the config parameter which maps directly to ModelConfig DTO,
	 * providing a clean interface between REST API and the SDK.
	 *
	 * @since n.e.x.t
	 *
	 * @param WP_REST_Request $request The REST request object.
	 * @return PromptBuilder Configured PromptBuilder instance.
	 */
	protected function build_prompt_from_request( WP_REST_Request $request ) {
		// Get prompt content (string, MessagePart, Message, or Message[]).
		$prompt = $request->get_param( 'prompt' );

		// Create builder with prompt - AiClient handles all prompt types.
		$builder = AiClient::prompt( $prompt );

		// Apply configuration if provided - maps directly to ModelConfig DTO.
		$config = $request->get_param( 'config' );
		if ( ! empty( $config ) && is_array( $config ) ) {
			$model_config = ModelConfig::fromArray( $config );
			$builder->usingModelConfig( $model_config );
		}

		return $builder;
	}

	/**
	 * Format a GenerativeAiResult object for JSON response.
	 *
	 * @param GenerativeAiResult $result The generation result.
	 * @return array<string, mixed> Formatted result data.
	 */
	private function format_generative_result( GenerativeAiResult $result ): array {
		$response_data = array(
			'candidates'    => array(),
			'finish_reason' => null,
			'created_at'    => current_time( 'mysql' ),
		);

		// Add candidates with their content and metadata.
		foreach ( $result->getCandidates() as $candidate ) {
			// Get message content from candidate.
			$message = $candidate->getMessage();
			$parts   = $message->getParts();

			// Extract text content from message parts.
			$content = '';
			foreach ( $parts as $part ) {
				if ( method_exists( $part, 'getText' ) ) {
					$content .= $part->getText();
				}
			}

			$candidate_data                = array(
				'content'       => $content,
				'finish_reason' => $candidate->getFinishReason() ? $candidate->getFinishReason()->value : null,
			);
			$response_data['candidates'][] = $candidate_data;
		}

		// Set primary finish reason from first candidate.
		if ( ! empty( $response_data['candidates'] ) ) {
			$response_data['finish_reason'] = $response_data['candidates'][0]['finish_reason'];
		}

		// Add token usage information.
		$token_usage                  = $result->getTokenUsage();
		$response_data['token_usage'] = array(
			'prompt_tokens'     => $token_usage->getPromptTokens(),
			'completion_tokens' => $token_usage->getCompletionTokens(),
			'total_tokens'      => $token_usage->getTotalTokens(),
		);

		return $response_data;
	}

	/**
	 * Format success response with consistent structure.
	 *
	 * @param mixed                $data Response data.
	 * @param array<string, mixed> $metadata Additional metadata.
	 * @return WP_REST_Response Success response.
	 */
	private function format_success_response( $data, array $metadata = array() ): WP_REST_Response {
		$response_data = array(
			'success'   => true,
			'data'      => $data,
			'timestamp' => current_time( 'mysql' ),
			'metadata'  => $metadata,
		);

		return new WP_REST_Response( $response_data, 200 );
	}

	/**
	 * Handle generation errors with consistent error response.
	 *
	 * @param \Exception $exception The caught exception.
	 * @return WP_REST_Response Error response.
	 */
	private function handle_generation_error( \Exception $exception ): WP_REST_Response {
		$error_data = array(
			'success'   => false,
			'error'     => array(
				'code'    => 'generation_failed',
				'message' => $exception->getMessage(),
			),
			'timestamp' => current_time( 'mysql' ),
		);

		return new WP_REST_Response( $error_data, 500 );
	}


	/**
	 * Check permissions for AI generation requests.
	 *
	 * Only administrators can use AI generation since they configure
	 * provider credentials and API keys.
	 *
	 * @param WP_REST_Request $request The REST request object.
	 * @return bool|WP_Error True if user has permission, WP_Error if not.
	 */
	public function check_permission( WP_REST_Request $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_forbidden',
				'You do not have permission to use AI generation',
				array( 'status' => 403 )
			);
		}

		return true;
	}
}
