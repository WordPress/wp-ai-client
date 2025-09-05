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
use WordPress\AiClient\Files\DTO\File;
use WordPress\AiClient\Files\Enums\FileTypeEnum;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;
use WordPress\AiClient\Results\DTO\GenerativeAiResult;

/**
 * Single controller handling all PromptBuilder terminate methods via individual routes.
 *
 * This controller provides individual REST endpoints for each PromptBuilder terminate method:
 * - /prompt/generate-text → generateText()
 * - /prompt/generate-texts → generateTexts() 
 * - /prompt/generate-image → generateImage()
 * - /prompt/generate-images → generateImages()
 * - /prompt/generate-speech → generateSpeech()
 * - /prompt/generate-speeches → generateSpeeches()
 * - /prompt/generate-result → generateResult()
 * - /prompt/generate-text-result → generateTextResult()
 * - /prompt/generate-image-result → generateImageResult()
 * - /prompt/generate-speech-result → generateSpeechResult()
 *
 * @since n.e.x.t
 */
class Prompt_Controller extends WP_REST_Controller {

	/**
	 * Register all routes for PromptBuilder terminate methods.
	 *
	 * @since n.e.x.t
	 *
	 * @param string $namespace The REST API namespace.
	 * @return void
	 */
	public function register_routes( string $namespace ): void {
		$common_args = $this->get_common_args();

		// Register individual route for each PromptBuilder terminate method
		$routes = [
			'/prompt/generate-text'         => 'generate_text',
			'/prompt/generate-texts'        => 'generate_texts',
			'/prompt/generate-image'        => 'generate_image',
			'/prompt/generate-images'       => 'generate_images',
			'/prompt/generate-speech'       => 'generate_speech',
			'/prompt/generate-speeches'     => 'generate_speeches',
			'/prompt/generate-result'       => 'generate_result',
			'/prompt/generate-text-result'  => 'generate_text_result',
			'/prompt/generate-image-result' => 'generate_image_result',
			'/prompt/generate-speech-result'=> 'generate_speech_result',
		];

		foreach ( $routes as $route => $callback ) {
			register_rest_route(
				$namespace,
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
	// TEXT GENERATION METHODS
	// =============================================================================

	/**
	 * Handle single text generation → PromptBuilder::generateText()
	 */
	public function generate_text( WP_REST_Request $request ): WP_REST_Response {
		try {
			$builder = $this->build_prompt_from_request( $request );
			$result  = $builder->generateText();

			return $this->format_success_response(
				$result,
				array( 'type' => 'text', 'endpoint' => 'generate-text' )
			);
		} catch ( \Exception $exception ) {
			return $this->handle_generation_error( $exception );
		}
	}

	/**
	 * Handle multiple text generation → PromptBuilder::generateTexts()
	 */
	public function generate_texts( WP_REST_Request $request ): WP_REST_Response {
		try {
			$builder = $this->build_prompt_from_request( $request );
			$candidate_count = $request->get_param( 'candidate_count' );
			$result = $candidate_count ? $builder->generateTexts( (int) $candidate_count ) : $builder->generateTexts();

			return $this->format_success_response(
				$result,
				array( 'type' => 'texts', 'endpoint' => 'generate-texts', 'count' => count( $result ) )
			);
		} catch ( \Exception $exception ) {
			return $this->handle_generation_error( $exception );
		}
	}

	/**
	 * Handle text result generation → PromptBuilder::generateTextResult()
	 */
	public function generate_text_result( WP_REST_Request $request ): WP_REST_Response {
		try {
			$builder = $this->build_prompt_from_request( $request );
			$result  = $builder->generateTextResult();

			return $this->format_success_response(
				$this->format_generative_result( $result ),
				array( 'type' => 'text-result', 'endpoint' => 'generate-text-result' )
			);
		} catch ( \Exception $exception ) {
			return $this->handle_generation_error( $exception );
		}
	}

	// =============================================================================
	// IMAGE GENERATION METHODS  
	// =============================================================================

	/**
	 * Handle single image generation → PromptBuilder::generateImage()
	 */
	public function generate_image( WP_REST_Request $request ): WP_REST_Response {
		try {
			$builder = $this->build_prompt_from_request( $request );
			$result  = $builder->generateImage();

			return $this->format_success_response(
				$this->format_file_response( $result ),
				array( 'type' => 'image', 'endpoint' => 'generate-image' )
			);
		} catch ( \Exception $exception ) {
			return $this->handle_generation_error( $exception );
		}
	}

	/**
	 * Handle multiple image generation → PromptBuilder::generateImages()
	 */
	public function generate_images( WP_REST_Request $request ): WP_REST_Response {
		try {
			$builder = $this->build_prompt_from_request( $request );
			$candidate_count = $request->get_param( 'candidate_count' );
			$result = $candidate_count ? $builder->generateImages( (int) $candidate_count ) : $builder->generateImages();

			$response_data = array_map(
				function ( File $file ) {
					return $this->format_file_response( $file );
				},
				$result
			);

			return $this->format_success_response(
				$response_data,
				array( 'type' => 'images', 'endpoint' => 'generate-images', 'count' => count( $result ) )
			);
		} catch ( \Exception $exception ) {
			return $this->handle_generation_error( $exception );
		}
	}

	/**
	 * Handle image result generation → PromptBuilder::generateImageResult()
	 */
	public function generate_image_result( WP_REST_Request $request ): WP_REST_Response {
		try {
			$builder = $this->build_prompt_from_request( $request );
			$result  = $builder->generateImageResult();

			return $this->format_success_response(
				$this->format_generative_result( $result ),
				array( 'type' => 'image-result', 'endpoint' => 'generate-image-result' )
			);
		} catch ( \Exception $exception ) {
			return $this->handle_generation_error( $exception );
		}
	}

	// =============================================================================
	// SPEECH GENERATION METHODS
	// =============================================================================

	/**
	 * Handle single speech generation → PromptBuilder::generateSpeech()
	 */
	public function generate_speech( WP_REST_Request $request ): WP_REST_Response {
		try {
			$builder = $this->build_prompt_from_request( $request );
			$result  = $builder->generateSpeech();

			return $this->format_success_response(
				$this->format_file_response( $result ),
				array( 'type' => 'speech', 'endpoint' => 'generate-speech' )
			);
		} catch ( \Exception $exception ) {
			return $this->handle_generation_error( $exception );
		}
	}

	/**
	 * Handle multiple speech generation → PromptBuilder::generateSpeeches()
	 */
	public function generate_speeches( WP_REST_Request $request ): WP_REST_Response {
		try {
			$builder = $this->build_prompt_from_request( $request );
			$candidate_count = $request->get_param( 'candidate_count' );
			$result = $candidate_count ? $builder->generateSpeeches( (int) $candidate_count ) : $builder->generateSpeeches();

			$response_data = array_map(
				function ( File $file ) {
					return $this->format_file_response( $file );
				},
				$result
			);

			return $this->format_success_response(
				$response_data,
				array( 'type' => 'speeches', 'endpoint' => 'generate-speeches', 'count' => count( $result ) )
			);
		} catch ( \Exception $exception ) {
			return $this->handle_generation_error( $exception );
		}
	}

	/**
	 * Handle speech result generation → PromptBuilder::generateSpeechResult()
	 */
	public function generate_speech_result( WP_REST_Request $request ): WP_REST_Response {
		try {
			$builder = $this->build_prompt_from_request( $request );
			$result  = $builder->generateSpeechResult();

			return $this->format_success_response(
				$this->format_generative_result( $result ),
				array( 'type' => 'speech-result', 'endpoint' => 'generate-speech-result' )
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
	 */
	public function generate_result( WP_REST_Request $request ): WP_REST_Response {
		try {
			$builder = $this->build_prompt_from_request( $request );
			$capability = $request->get_param( 'capability' );
			
			if ( $capability ) {
				$result = $builder->generateResult( CapabilityEnum::from( $capability ) );
			} else {
				$result = $builder->generateResult();
			}

			return $this->format_success_response(
				$this->format_generative_result( $result ),
				array( 'type' => 'result', 'endpoint' => 'generate-result' )
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
	 * Format a File object for JSON response.
	 *
	 * @param File $file The file object.
	 * @return array<string, mixed> Formatted file data.
	 */
	private function format_file_response( File $file ): array {
		return array(
			'url'       => $file->getUrl(),
			'mime_type' => $file->getMimeType(),
			'file_type' => $file->getFileType()->value,
		);
	}

	/**
	 * Format a GenerativeAiResult object for JSON response.
	 *
	 * @param GenerativeAiResult $result The generation result.
	 * @return array<string, mixed> Formatted result data.
	 */
	private function format_generative_result( GenerativeAiResult $result ): array {
		$response_data = array(
			'candidates' => array(),
			'finish_reason' => null,
			'created_at' => current_time( 'mysql' ),
		);

		// Add candidates with their content and metadata.
		foreach ( $result->getCandidates() as $candidate ) {
			// Get message content from candidate.
			$message = $candidate->getMessage();
			$parts = $message->getParts();
			
			// Extract text content from message parts.
			$content = '';
			foreach ( $parts as $part ) {
				if ( method_exists( $part, 'getText' ) ) {
					$content .= $part->getText();
				}
			}
			
			$candidate_data = array(
				'content' => $content,
				'finish_reason' => $candidate->getFinishReason() ? $candidate->getFinishReason()->value : null,
			);
			$response_data['candidates'][] = $candidate_data;
		}

		// Set primary finish reason from first candidate.
		if ( ! empty( $response_data['candidates'] ) ) {
			$response_data['finish_reason'] = $response_data['candidates'][0]['finish_reason'];
		}

		// Add token usage information.
		$token_usage = $result->getTokenUsage();
		$response_data['token_usage'] = array(
			'prompt_tokens' => $token_usage->getPromptTokens(),
			'completion_tokens' => $token_usage->getCompletionTokens(),
			'total_tokens' => $token_usage->getTotalTokens(),
		);

		return $response_data;
	}

	/**
	 * Format success response with consistent structure.
	 *
	 * @param mixed $data Response data.
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
	 * @param mixed $value Prompt value to validate.
	 * @param WP_REST_Request $request Current request object.
	 * @param string $param Parameter name.
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
	 * Check permissions for AI generation requests.
	 *
	 * @param WP_REST_Request $request The REST request object.
	 * @return bool|WP_Error True if user has permission, WP_Error if not.
	 */
	public function check_permission( WP_REST_Request $request ) {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return new WP_Error(
				'insufficient_permission',
				'You do not have permission to use AI generation',
				array( 'status' => 403 )
			);
		}

		return true;
	}
}