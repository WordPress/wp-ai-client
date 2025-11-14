<?php
/**
 * Class WordPress\AI_Client\Builders\Prompt_Builder_With_WP_Error
 *
 * @since 0.1.0
 * @package WordPress\AI_Client
 */

namespace WordPress\AI_Client\Builders;

use Exception;
use WordPress\AiClient\Builders\PromptBuilder;
use WordPress\AiClient\Files\DTO\File;
use WordPress\AiClient\Files\Enums\FileTypeEnum;
use WordPress\AiClient\Messages\DTO\Message;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Messages\Enums\ModalityEnum;
use WordPress\AiClient\Providers\Models\Contracts\ModelInterface;
use WordPress\AiClient\Providers\Models\DTO\ModelConfig;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;
use WordPress\AiClient\Providers\ProviderRegistry;
use WordPress\AiClient\Results\DTO\GenerativeAiResult;
use WordPress\AiClient\Tools\DTO\FunctionDeclaration;
use WordPress\AiClient\Tools\DTO\FunctionResponse;
use WordPress\AiClient\Tools\DTO\WebSearch;
use WP_Error;

/**
 * Fluent builder for constructing AI prompts, returning WP_Error in case of problems instead of throwing exceptions.
 *
 * Only the terminate methods will return a WP_Error, to not break the fluent interface. As soon as any exception is
 * caught in a chain of method calls, the returned instance will be in an error state, and all subsequent method calls
 * will be no-ops that just return the same error state instance. Only when a terminate method is called, the WP_Error
 * will be returned.
 *
 * @since 0.1.0
 *
 * @method self with_text(string $text) Adds text to the current message.
 * @method self with_file($file, ?string $mimeType = null) Adds a file to the current message.
 * @method self with_function_response(FunctionResponse $functionResponse) Adds a function response to the current message.
 * @method self with_message_parts(MessagePart ...$parts) Adds message parts to the current message.
 * @method self with_history(Message ...$messages) Adds conversation history messages.
 * @method self using_model(ModelInterface $model) Sets the model to use for generation.
 * @method self using_model_preference(...$preferredModels) Sets preferred models to evaluate in order.
 * @method self using_model_config(ModelConfig $config) Sets the model configuration.
 * @method self using_provider(string $providerIdOrClassName) Sets the provider to use for generation.
 * @method self using_system_instruction(string $systemInstruction) Sets the system instruction.
 * @method self using_max_tokens(int $maxTokens) Sets the maximum number of tokens to generate.
 * @method self using_temperature(float $temperature) Sets the temperature for generation.
 * @method self using_top_p(float $topP) Sets the top-p value for generation.
 * @method self using_top_k(int $topK) Sets the top-k value for generation.
 * @method self using_stop_sequences(string ...$stopSequences) Sets stop sequences for generation.
 * @method self using_candidate_count(int $candidateCount) Sets the number of candidates to generate.
 * @method self using_function_declarations(FunctionDeclaration ...$functionDeclarations) Sets the function declarations available to the model.
 * @method self using_presence_penalty(float $presencePenalty) Sets the presence penalty for generation.
 * @method self using_frequency_penalty(float $frequencyPenalty) Sets the frequency penalty for generation.
 * @method self using_web_search(WebSearch $webSearch) Sets the web search configuration.
 * @method self using_top_logprobs(?int $topLogprobs = null) Sets the top log probabilities configuration.
 * @method self as_output_mime_type(string $mimeType) Sets the output MIME type.
 * @method self as_output_schema(array<string, mixed> $schema) Sets the output schema.
 * @method self as_output_modalities(ModalityEnum ...$modalities) Sets the output modalities.
 * @method self as_output_file_type(FileTypeEnum $fileType) Sets the output file type.
 * @method self as_json_response(?array<string, mixed> $schema = null) Configures the prompt for JSON response output.
 * @method bool is_supported_for_text_generation() Checks if the prompt is supported for text generation.
 * @method bool is_supported_for_image_generation() Checks if the prompt is supported for image generation.
 * @method bool is_supported_for_text_to_speech_conversion() Checks if the prompt is supported for text to speech conversion.
 * @method bool is_supported_for_video_generation() Checks if the prompt is supported for video generation.
 * @method bool is_supported_for_speech_generation() Checks if the prompt is supported for speech generation.
 * @method bool is_supported_for_music_generation() Checks if the prompt is supported for music generation.
 * @method bool is_supported_for_embedding_generation() Checks if the prompt is supported for embedding generation.
 * @method GenerativeAiResult|WP_Error generate_result(?CapabilityEnum $capability = null) Generates a result from the prompt.
 * @method GenerativeAiResult|WP_Error generate_text_result() Generates a text result from the prompt.
 * @method GenerativeAiResult|WP_Error generate_image_result() Generates an image result from the prompt.
 * @method GenerativeAiResult|WP_Error generate_speech_result() Generates a speech result from the prompt.
 * @method GenerativeAiResult|WP_Error convert_text_to_speech_result() Converts text to speech and returns the result.
 * @method string|WP_Error generate_text() Generates text from the prompt.
 * @method list<string>|WP_Error generate_texts(?int $candidateCount = null) Generates multiple text candidates from the prompt.
 * @method File|WP_Error generate_image() Generates an image from the prompt.
 * @method list<File>|WP_Error generate_images(?int $candidateCount = null) Generates multiple images from the prompt.
 * @method File|WP_Error convert_text_to_speech() Converts text to speech.
 * @method list<File>|WP_Error convert_text_to_speeches(?int $candidateCount = null) Converts text to multiple speech outputs.
 * @method File|WP_Error generate_speech() Generates speech from the prompt.
 * @method list<File>|WP_Error generate_speeches(?int $candidateCount = null) Generates multiple speech outputs from the prompt.
 */
class Prompt_Builder_With_WP_Error extends Prompt_Builder {

	/**
	 * WordPress error instance, if any error occurred during method calls.
	 *
	 * @since 0.1.0
	 * @var WP_Error|null
	 */
	private ?WP_Error $error = null;

	/**
	 * List of methods that terminate the fluent interface and return a result.
	 *
	 * Technically a map, simply for faster lookups.
	 *
	 * @since 0.1.0
	 * @var array<string, bool>
	 */
	private static array $terminate_methods = array(
		'generate_result'               => true,
		'generate_text_result'          => true,
		'generate_image_result'         => true,
		'generate_speech_result'        => true,
		'convert_text_to_speech_result' => true,
		'generate_text'                 => true,
		'generate_texts'                => true,
		'generate_image'                => true,
		'generate_images'               => true,
		'convert_text_to_speech'        => true,
		'convert_text_to_speeches'      => true,
		'generate_speech'               => true,
		'generate_speeches'             => true,
	);

	/**
	 * Magic method to proxy snake_case method calls to their PHP AI Client camelCase counterparts.
	 *
	 * This allows WordPress developers to use snake_case naming conventions. It also catches any exceptions thrown,
	 * stores them, and returns a WP_Error when a terminate method is called.
	 *
	 * @since 0.1.0
	 *
	 * @param string            $name      The method name in snake_case.
	 * @param array<int, mixed> $arguments The method arguments.
	 * @return mixed The result of the parent method call.
	 */
	public function __call( string $name, array $arguments ) {
		// This may throw, which is fine because calls to methods that don't exist always throw.
		$callable = $this->get_builder_callable( $name );

		/*
		 * If an error occurred in a previous method call, either return the error for terminate methods,
		 * or return the same instance for other methods to maintain the fluent interface.
		 */
		if ( null !== $this->error ) {
			if ( self::is_terminating_method( $name ) ) {
				return $this->error;
			}
			return $this;
		}

		try {
			$result = $callable( ...$arguments );

			// If the result is a PromptBuilder, return the current instance to allow method chaining.
			if ( $result instanceof PromptBuilder ) {
				return $this;
			}

			return $result;
		} catch ( Exception $e ) {
			$this->error = new WP_Error(
				'prompt_builder_error',
				$e->getMessage(),
				array(
					'exception_class' => get_class( $e ),
				)
			);

			if ( self::is_terminating_method( $name ) ) {
				return $this->error;
			}
			return $this;
		}
	}

	/**
	 * Checks if a method is a terminating method.
	 *
	 * @since 0.1.0
	 *
	 * @param string $name The method name.
	 * @return bool True if the method is a terminating method, false otherwise.
	 */
	private static function is_terminating_method( string $name ): bool {
		return isset( self::$terminate_methods[ $name ] );
	}
}
