<?php
/**
 * Class WordPress\AI_Client\Builders\Prompt_Builder
 *
 * @since 0.1.0
 * @package WordPress\AI_Client
 */

namespace WordPress\AI_Client\Builders;

use BadMethodCallException;
use WordPress\AiClient\Builders\PromptBuilder;
use WordPress\AiClient\Files\DTO\File;
use WordPress\AiClient\Files\Enums\FileTypeEnum;
use WordPress\AiClient\Messages\DTO\Message;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Messages\Enums\ModalityEnum;
use WordPress\AiClient\Providers\Http\DTO\RequestOptions;
use WordPress\AiClient\Providers\Models\Contracts\ModelInterface;
use WordPress\AiClient\Providers\Models\DTO\ModelConfig;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;
use WordPress\AiClient\Providers\ProviderRegistry;
use WordPress\AiClient\Results\DTO\GenerativeAiResult;
use WordPress\AiClient\Tools\DTO\FunctionDeclaration;
use WordPress\AiClient\Tools\DTO\FunctionResponse;
use WordPress\AiClient\Tools\DTO\WebSearch;
use WordPress\AI_Client\Builders\Helpers\Ability_Function_Resolver;
use WP_Ability;

/**
 * Fluent builder for constructing AI prompts.
 *
 * This class provides a fluent interface for building prompts with various
 * content types and model configurations. It automatically infers model
 * requirements based on the features used in the prompt.
 *
 * Technically, this class wraps an instance of `PromptBuilder` from the WordPress agnostic PHP AI Client SDK.
 * It adds the necessary WordPress flavoring around it, e.g. follow WordPress Coding Standards, as well as
 * integrate with WordPress specific AI primitives such as the Abilities API.
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
 * @method self using_request_options(RequestOptions $options) Sets the request options for HTTP transport.
 * @method self using_top_logprobs(?int $topLogprobs = null) Sets the top log probabilities configuration.
 * @method self as_output_mime_type(string $mimeType) Sets the output MIME type.
 * @method self as_output_schema(array<string, mixed> $schema) Sets the output schema.
 * @method self as_output_modalities(ModalityEnum ...$modalities) Sets the output modalities.
 * @method self as_output_file_type(FileTypeEnum $fileType) Sets the output file type.
 * @method self as_json_response(?array<string, mixed> $schema = null) Configures the prompt for JSON response output.
 * @method bool is_supported(?CapabilityEnum $capability = null) Checks if the prompt is supported for the given capability.
 * @method bool is_supported_for_text_generation() Checks if the prompt is supported for text generation.
 * @method bool is_supported_for_image_generation() Checks if the prompt is supported for image generation.
 * @method bool is_supported_for_text_to_speech_conversion() Checks if the prompt is supported for text to speech conversion.
 * @method bool is_supported_for_video_generation() Checks if the prompt is supported for video generation.
 * @method bool is_supported_for_speech_generation() Checks if the prompt is supported for speech generation.
 * @method bool is_supported_for_music_generation() Checks if the prompt is supported for music generation.
 * @method bool is_supported_for_embedding_generation() Checks if the prompt is supported for embedding generation.
 * @method GenerativeAiResult generate_result(?CapabilityEnum $capability = null) Generates a result from the prompt.
 * @method GenerativeAiResult generate_text_result() Generates a text result from the prompt.
 * @method GenerativeAiResult generate_image_result() Generates an image result from the prompt.
 * @method GenerativeAiResult generate_speech_result() Generates a speech result from the prompt.
 * @method GenerativeAiResult convert_text_to_speech_result() Converts text to speech and returns the result.
 * @method string generate_text() Generates text from the prompt.
 * @method list<string> generate_texts(?int $candidateCount = null) Generates multiple text candidates from the prompt.
 * @method File generate_image() Generates an image from the prompt.
 * @method list<File> generate_images(?int $candidateCount = null) Generates multiple images from the prompt.
 * @method File convert_text_to_speech() Converts text to speech.
 * @method list<File> convert_text_to_speeches(?int $candidateCount = null) Converts text to multiple speech outputs.
 * @method File generate_speech() Generates speech from the prompt.
 * @method list<File> generate_speeches(?int $candidateCount = null) Generates multiple speech outputs from the prompt.
 *
 * @phpstan-import-type MessageArrayShape from Message
 * @phpstan-import-type MessagePartArrayShape from MessagePart
 *
 * @phpstan-type Prompt string|MessagePart|Message|MessageArrayShape|list<string|MessagePart|MessagePartArrayShape>|list<Message>|null
 */
class Prompt_Builder {

	/**
	 * Wrapped prompt builder instance.
	 *
	 * @since 0.1.0
	 * @var PromptBuilder
	 */
	private PromptBuilder $builder;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param ProviderRegistry $registry The provider registry for finding suitable models.
	 * @param Prompt           $prompt   Optional initial prompt content.
	 */
	public function __construct( ProviderRegistry $registry, $prompt = null ) {
		$this->builder = new PromptBuilder( $registry, $prompt );

		/**
		 * Filters the default request timeout in seconds for AI Client HTTP requests.
		 *
		 * @since 0.2.0
		 *
		 * @param int $default_timeout The default timeout in seconds.
		 */
		$default_timeout = (int) apply_filters( 'wp_ai_client_default_request_timeout', 30 );

		$this->builder->usingRequestOptions(
			RequestOptions::fromArray(
				array(
					RequestOptions::KEY_TIMEOUT => $default_timeout,
				)
			)
		);
	}

	/**
	 * Registers WordPress abilities as function declarations for the AI model.
	 *
	 * Converts each WP_Ability to a FunctionDeclaration using the wpab__ prefix
	 * naming convention and passes them to the underlying prompt builder.
	 *
	 * @since 0.2.0
	 *
	 * @param WP_Ability|string ...$abilities The abilities to register, either as WP_Ability objects or ability name strings.
	 * @return self The current instance for method chaining.
	 */
	public function using_ability( ...$abilities ): self {
		$declarations = array();

		foreach ( $abilities as $ability ) {
			if ( is_string( $ability ) ) {
				$ability = wp_get_ability( $ability );
			}

			if ( ! $ability instanceof WP_Ability ) {
				continue;
			}

			$function_name = Ability_Function_Resolver::ability_name_to_function_name( $ability->get_name() );
			$input_schema  = $ability->get_input_schema();

			$declarations[] = new FunctionDeclaration(
				$function_name,
				$ability->get_description(),
				! empty( $input_schema ) ? $input_schema : null
			);
		}

		if ( ! empty( $declarations ) ) {
			return $this->using_function_declarations( ...$declarations );
		}

		return $this;
	}

	/**
	 * Magic method to proxy snake_case method calls to their PHP AI Client camelCase counterparts.
	 *
	 * This allows WordPress developers to use snake_case naming conventions.
	 *
	 * @since 0.1.0
	 *
	 * @param string            $name      The method name in snake_case.
	 * @param array<int, mixed> $arguments The method arguments.
	 * @return mixed The result of the parent method call.
	 */
	public function __call( string $name, array $arguments ) {
		$callable = $this->get_builder_callable( $name );
		$result   = $callable( ...$arguments );

		// If the result is a PromptBuilder, return the current instance to allow method chaining.
		if ( $result instanceof PromptBuilder ) {
			return $this;
		}

		return $result;
	}

	/**
	 * Retrieves a callable for a given PHP AI Client SDK prompt builder method name.
	 *
	 * @since 0.1.0
	 *
	 * @param string $name The method name in snake_case.
	 * @return callable The callable for the specified method.
	 *
	 * @throws BadMethodCallException If the method does not exist.
	 */
	protected function get_builder_callable( string $name ): callable {
		$camel_case_name = $this->snake_to_camel_case( $name );

		if ( ! is_callable( array( $this->builder, $camel_case_name ) ) ) {
			throw new BadMethodCallException(
				sprintf(
					'Method %s does not exist on %s',
					$name, // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
					get_class( $this->builder ) // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				)
			);
		}

		// @phpstan-ignore-next-line return.type (This is a valid callable)
		return array( $this->builder, $camel_case_name );
	}

	/**
	 * Converts snake_case to camelCase.
	 *
	 * @since 0.1.0
	 *
	 * @param string $snake_case The snake_case string.
	 * @return string The camelCase string.
	 */
	private function snake_to_camel_case( string $snake_case ): string {
		$parts = explode( '_', $snake_case );

		$camel_case  = $parts[0];
		$parts_count = count( $parts );
		for ( $i = 1; $i < $parts_count; $i++ ) {
			$camel_case .= ucfirst( $parts[ $i ] );
		}

		return $camel_case;
	}
}
