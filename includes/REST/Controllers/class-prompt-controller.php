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
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;
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
				)
			);
		}
	}

	/**
	 * Get common request arguments for all prompt endpoints.
	 *
	 * @since n.e.x.t
	 *
	 * @return array<string, array<string, mixed>> Request arguments schema.
	 */
	protected function get_common_args(): array {
		return array(
			'prompt'             => array(
				'required'          => true,
				'type'              => array( 'string', 'array' ),
				'description'       => 'The prompt text, message parts, or array of messages',
				'validate_callback' => array( $this, 'validate_prompt' ),
			),
			'system_instruction' => array(
				'type'        => 'string',
				'description' => 'System instruction to guide the AI behavior',
			),
			'temperature'        => array(
				'type'        => 'number',
				'minimum'     => 0.0,
				'maximum'     => 2.0,
				'description' => 'Controls randomness in generation (0.0 = deterministic, 2.0 = very random)',
			),
			'max_tokens'         => array(
				'type'        => 'integer',
				'minimum'     => 1,
				'description' => 'Maximum number of tokens to generate',
			),
			'top_p'              => array(
				'type'        => 'number',
				'minimum'     => 0.0,
				'maximum'     => 1.0,
				'description' => 'Nucleus sampling parameter',
			),
			'top_k'              => array(
				'type'        => 'integer',
				'minimum'     => 1,
				'description' => 'Top-k sampling parameter',
			),
			'provider'           => array(
				'type'        => 'string',
				'description' => 'Specific AI provider to use (e.g., "openai", "anthropic")',
			),
			'candidate_count'    => array(
				'type'        => 'integer',
				'minimum'     => 1,
				'maximum'     => 10,
				'description' => 'Number of generation candidates to return (for multi-generation endpoints)',
			),
			'stop_sequences'     => array(
				'type'        => 'array',
				'items'       => array(
					'type' => 'string',
				),
				'description' => 'Sequences that will stop generation when encountered',
			),
			'capability'         => array(
				'type'        => 'string',
				'description' => 'AI capability to use for generation (for generate-result endpoint)',
			),
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
			$builder    = $this->build_prompt_from_request( $request );
			$capability = $request->get_param( 'capability' );

			if ( $capability ) {
				$result = $builder->generateResult( CapabilityEnum::from( $capability ) );
			} else {
				$result = $builder->generateResult();
			}

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
	 * @since n.e.x.t
	 *
	 * @param WP_REST_Request $request The REST request object.
	 * @return PromptBuilder Configured PromptBuilder instance.
	 */
	protected function build_prompt_from_request( WP_REST_Request $request ) {
		// Get prompt content for constructor.
		$prompt = $request->get_param( 'prompt' );

		// Validate and sanitize prompt parameter for AiClient.
		if ( is_string( $prompt ) ) {
			// String prompts are valid as-is.
			$validated_prompt = $prompt;
		} elseif ( is_array( $prompt ) ) {
			// For arrays, ensure they contain only valid message-like structures.
			// This is a basic validation - AiClient will handle detailed validation.
			$validated_prompt = $prompt;
		} else {
			// Invalid type - use null for empty prompt.
			$validated_prompt = null;
		}

		// Create builder with validated prompt.
		$builder = AiClient::prompt( $validated_prompt );

		// Apply system instruction if provided.
		if ( $request->has_param( 'system_instruction' ) ) {
			$builder->usingSystemInstruction( $request->get_param( 'system_instruction' ) );
		}

		// Apply temperature if provided.
		if ( $request->has_param( 'temperature' ) ) {
			$builder->usingTemperature( (float) $request->get_param( 'temperature' ) );
		}

		// Apply max tokens if provided.
		if ( $request->has_param( 'max_tokens' ) ) {
			$builder->usingMaxTokens( (int) $request->get_param( 'max_tokens' ) );
		}

		// Apply top_p if provided.
		if ( $request->has_param( 'top_p' ) ) {
			$builder->usingTopP( (float) $request->get_param( 'top_p' ) );
		}

		// Apply top_k if provided.
		if ( $request->has_param( 'top_k' ) ) {
			$builder->usingTopK( (int) $request->get_param( 'top_k' ) );
		}

		// Apply stop sequences if provided.
		if ( $request->has_param( 'stop_sequences' ) ) {
			$stop_sequences = $request->get_param( 'stop_sequences' );
			if ( is_array( $stop_sequences ) ) {
				$builder->usingStopSequences( ...$stop_sequences );
			}
		}

		// Apply provider if provided.
		if ( $request->has_param( 'provider' ) ) {
			$builder->usingProvider( $request->get_param( 'provider' ) );
		}

		// Apply candidate count if provided.
		if ( $request->has_param( 'candidate_count' ) ) {
			$builder->usingCandidateCount( (int) $request->get_param( 'candidate_count' ) );
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
	 * Validate prompt parameter.
	 *
	 * @param mixed           $value Prompt value to validate.
	 * @param WP_REST_Request $request Current request object.
	 * @param string          $param Parameter name.
	 * @return bool|WP_Error True if valid, WP_Error if invalid.
	 */
	public function validate_prompt( $value, WP_REST_Request $request, string $param ) {
		if ( is_string( $value ) ) {
			if ( empty( trim( $value ) ) ) {
				return new WP_Error(
					'invalid_prompt',
					'Prompt cannot be empty or contain only whitespace',
					array( 'status' => 400 )
				);
			}
			return true;
		}

		if ( is_array( $value ) ) {
			if ( empty( $value ) ) {
				return new WP_Error(
					'invalid_prompt',
					'Prompt array cannot be empty',
					array( 'status' => 400 )
				);
			}
			return true;
		}

		return new WP_Error(
			'invalid_prompt',
			'Prompt must be a string or array',
			array( 'status' => 400 )
		);
	}

	/**
	 * Get the required capability for AI operations.
	 *
	 * Defaults to 'manage_options' (admin-only) but can be filtered by plugins.
	 * This ensures only admins can use AI by default (since they configure
	 * provider credentials), while allowing plugins to customize access control.
	 *
	 * @since n.e.x.t
	 *
	 * @return string The capability required for AI operations.
	 */
	private function get_ai_capability(): string {
		/**
		 * Filters the capability required for AI operations.
		 *
		 * @since n.e.x.t
		 *
		 * @param string $capability The default capability ('manage_options').
		 */
		return apply_filters( 'wp_ai_client_capability', 'manage_options' );
	}

	/**
	 * Check permissions for AI generation requests.
	 *
	 * Uses a custom capability that defaults to admin-only access but can be
	 * filtered by plugins to implement their own permission schemes.
	 *
	 * @param WP_REST_Request $request The REST request object.
	 * @return bool|WP_Error True if user has permission, WP_Error if not.
	 */
	public function check_permission( WP_REST_Request $request ) {
		$required_capability = $this->get_ai_capability();

		if ( ! current_user_can( $required_capability ) ) {
			return new WP_Error(
				'wp_ai_client_insufficient_permission',
				'You do not have permission to use AI generation',
				array( 'status' => 403 )
			);
		}

		return true;
	}
}
