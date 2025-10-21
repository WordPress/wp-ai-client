<?php
/**
 * WordPress-compatible Prompt Builder with WP_Error support.
 *
 * Extends Prompt_Builder and converts exceptions to WP_Error objects.
 *
 * @since n.e.x.t
 *
 * @package WordPress\AI_Client
 */

declare(strict_types=1);

namespace WordPress\AI_Client;

use WordPress\AiClient\Common\Exception\InvalidArgumentException;
use WordPress\AiClient\Common\Exception\RuntimeException;
use WordPress\AiClient\Files\DTO\File;
use WordPress\AiClient\Providers\Models\Contracts\ModelInterface;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;
use WordPress\AiClient\Providers\ProviderRegistry;
use WordPress\AiClient\Results\DTO\GenerativeAiResult;

/**
 * WordPress-compatible Prompt Builder with WP_Error support.
 *
 * This class extends Prompt_Builder and overrides methods that throw exceptions,
 * converting them to WP_Error objects for WordPress-style error handling.
 * Only methods that can actually throw exceptions have WP_Error in their return type,
 * preserving proper method chaining for fluent methods.
 *
 * @since n.e.x.t
 */
class Prompt_Builder_With_WP_Error extends Prompt_Builder {

	/**
	 * Construction error, if any occurred.
	 *
	 * @since n.e.x.t
	 *
	 * @var \WP_Error|null
	 */
	private ?\WP_Error $construction_error = null;

	/**
	 * Constructor.
	 *
	 * If an error occurs during construction, it is stored and can be checked
	 * with has_error() or retrieved with get_error().
	 *
	 * @since n.e.x.t
	 *
	 * @param ProviderRegistry $registry The provider registry for finding suitable models.
	 * @param mixed            $prompt   Optional initial prompt content.
	 */
	public function __construct( ProviderRegistry $registry, $prompt = null ) {
		try {
			// Parent validates the prompt type.
			parent::__construct( $registry, $prompt ); // @phpstan-ignore-line argument.type
		} catch ( InvalidArgumentException $e ) {
			$this->construction_error = new \WP_Error(
				'prompt_builder_invalid_argument',
				$e->getMessage(), // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				array( 'exception' => $e )
			);
		}
	}

	/**
	 * Checks if an error occurred during construction.
	 *
	 * @since n.e.x.t
	 *
	 * @return bool True if there was a construction error, false otherwise.
	 */
	public function has_error(): bool {
		return null !== $this->construction_error;
	}

	/**
	 * Gets the construction error, if any.
	 *
	 * @since n.e.x.t
	 *
	 * @return \WP_Error|null The error that occurred during construction, or null.
	 */
	public function get_error(): ?\WP_Error {
		return $this->construction_error;
	}

	/**
	 * Sets preferred models to evaluate in order.
	 *
	 * @since n.e.x.t
	 *
	 * @param string|ModelInterface|array{0:string,1:string} ...$preferred_models The preferred models.
	 *
	 * @return self|\WP_Error This builder instance for chaining, or WP_Error on failure.
	 */
	public function using_model_preference( ...$preferred_models ) {
		try {
			parent::usingModelPreference( ...$preferred_models );
			return $this;
		} catch ( InvalidArgumentException $e ) {
			return new \WP_Error(
				'prompt_builder_invalid_argument',
				$e->getMessage(), // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				array( 'exception' => $e )
			);
		}
	}

	/**
	 * Generates a result from the prompt.
	 *
	 * @since n.e.x.t
	 *
	 * @param CapabilityEnum|null $capability Optional capability to use for generation.
	 *
	 * @return GenerativeAiResult|\WP_Error The generated result, or WP_Error on failure.
	 */
	public function generate_result( ?CapabilityEnum $capability = null ) {
		try {
			return parent::generateResult( $capability );
		} catch ( InvalidArgumentException $e ) {
			return new \WP_Error(
				'prompt_builder_invalid_argument',
				$e->getMessage(), // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				array( 'exception' => $e )
			);
		} catch ( RuntimeException $e ) {
			return new \WP_Error(
				'prompt_builder_runtime_error',
				$e->getMessage(), // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				array( 'exception' => $e )
			);
		}
	}

	/**
	 * Generates a text result from the prompt.
	 *
	 * @since n.e.x.t
	 *
	 * @return GenerativeAiResult|\WP_Error The generated result, or WP_Error on failure.
	 */
	public function generate_text_result() {
		try {
			return parent::generateTextResult();
		} catch ( InvalidArgumentException $e ) {
			return new \WP_Error(
				'prompt_builder_invalid_argument',
				$e->getMessage(), // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				array( 'exception' => $e )
			);
		} catch ( RuntimeException $e ) {
			return new \WP_Error(
				'prompt_builder_runtime_error',
				$e->getMessage(), // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				array( 'exception' => $e )
			);
		}
	}

	/**
	 * Generates an image result from the prompt.
	 *
	 * @since n.e.x.t
	 *
	 * @return GenerativeAiResult|\WP_Error The generated result, or WP_Error on failure.
	 */
	public function generate_image_result() {
		try {
			return parent::generateImageResult();
		} catch ( InvalidArgumentException $e ) {
			return new \WP_Error(
				'prompt_builder_invalid_argument',
				$e->getMessage(), // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				array( 'exception' => $e )
			);
		} catch ( RuntimeException $e ) {
			return new \WP_Error(
				'prompt_builder_runtime_error',
				$e->getMessage(), // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				array( 'exception' => $e )
			);
		}
	}

	/**
	 * Generates a speech result from the prompt.
	 *
	 * @since n.e.x.t
	 *
	 * @return GenerativeAiResult|\WP_Error The generated result, or WP_Error on failure.
	 */
	public function generate_speech_result() {
		try {
			return parent::generateSpeechResult();
		} catch ( InvalidArgumentException $e ) {
			return new \WP_Error(
				'prompt_builder_invalid_argument',
				$e->getMessage(), // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				array( 'exception' => $e )
			);
		} catch ( RuntimeException $e ) {
			return new \WP_Error(
				'prompt_builder_runtime_error',
				$e->getMessage(), // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				array( 'exception' => $e )
			);
		}
	}

	/**
	 * Converts text to speech and returns the result.
	 *
	 * @since n.e.x.t
	 *
	 * @return GenerativeAiResult|\WP_Error The generated result, or WP_Error on failure.
	 */
	public function convert_text_to_speech_result() {
		try {
			return parent::convertTextToSpeechResult();
		} catch ( InvalidArgumentException $e ) {
			return new \WP_Error(
				'prompt_builder_invalid_argument',
				$e->getMessage(), // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				array( 'exception' => $e )
			);
		} catch ( RuntimeException $e ) {
			return new \WP_Error(
				'prompt_builder_runtime_error',
				$e->getMessage(), // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				array( 'exception' => $e )
			);
		}
	}

	/**
	 * Generates text from the prompt.
	 *
	 * @since n.e.x.t
	 *
	 * @return string|\WP_Error The generated text, or WP_Error on failure.
	 */
	public function generate_text() {
		try {
			return parent::generateText();
		} catch ( InvalidArgumentException | RuntimeException $e ) { // @phpstan-ignore-line catch.neverThrown
			// Catch both exception types for consistency.
			$code = $e instanceof InvalidArgumentException ? 'prompt_builder_invalid_argument' : 'prompt_builder_runtime_error';

			return new \WP_Error(
				$code,
				$e->getMessage(), // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				array( 'exception' => $e )
			);
		}
	}

	/**
	 * Generates multiple text candidates from the prompt.
	 *
	 * @since n.e.x.t
	 *
	 * @param int|null $candidate_count The number of candidates to generate.
	 *
	 * @return list<string>|\WP_Error The generated texts, or WP_Error on failure.
	 */
	public function generate_texts( ?int $candidate_count = null ) {
		try {
			return parent::generateTexts( $candidate_count );
		} catch ( InvalidArgumentException | RuntimeException $e ) { // @phpstan-ignore-line catch.neverThrown
			// Catch both exception types for consistency.
			$code = $e instanceof InvalidArgumentException ? 'prompt_builder_invalid_argument' : 'prompt_builder_runtime_error';

			return new \WP_Error(
				$code,
				$e->getMessage(), // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				array( 'exception' => $e )
			);
		}
	}

	/**
	 * Generates an image from the prompt.
	 *
	 * @since n.e.x.t
	 *
	 * @return File|\WP_Error The generated image file, or WP_Error on failure.
	 */
	public function generate_image() {
		try {
			return parent::generateImage();
		} catch ( InvalidArgumentException $e ) {
			return new \WP_Error(
				'prompt_builder_invalid_argument',
				$e->getMessage(), // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				array( 'exception' => $e )
			);
		} catch ( RuntimeException $e ) {
			return new \WP_Error(
				'prompt_builder_runtime_error',
				$e->getMessage(), // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				array( 'exception' => $e )
			);
		}
	}

	/**
	 * Generates multiple images from the prompt.
	 *
	 * @since n.e.x.t
	 *
	 * @param int|null $candidate_count The number of images to generate.
	 *
	 * @return list<File>|\WP_Error The generated image files, or WP_Error on failure.
	 */
	public function generate_images( ?int $candidate_count = null ) {
		try {
			return parent::generateImages( $candidate_count );
		} catch ( InvalidArgumentException $e ) {
			return new \WP_Error(
				'prompt_builder_invalid_argument',
				$e->getMessage(), // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				array( 'exception' => $e )
			);
		} catch ( RuntimeException $e ) {
			return new \WP_Error(
				'prompt_builder_runtime_error',
				$e->getMessage(), // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				array( 'exception' => $e )
			);
		}
	}

	/**
	 * Converts text to speech.
	 *
	 * @since n.e.x.t
	 *
	 * @return File|\WP_Error The generated speech audio file, or WP_Error on failure.
	 */
	public function convert_text_to_speech() {
		try {
			return parent::convertTextToSpeech();
		} catch ( InvalidArgumentException $e ) {
			return new \WP_Error(
				'prompt_builder_invalid_argument',
				$e->getMessage(), // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				array( 'exception' => $e )
			);
		} catch ( RuntimeException $e ) {
			return new \WP_Error(
				'prompt_builder_runtime_error',
				$e->getMessage(), // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				array( 'exception' => $e )
			);
		}
	}

	/**
	 * Converts text to multiple speech outputs.
	 *
	 * @since n.e.x.t
	 *
	 * @param int|null $candidate_count The number of speech outputs to generate.
	 *
	 * @return list<File>|\WP_Error The generated speech audio files, or WP_Error on failure.
	 */
	public function convert_text_to_speeches( ?int $candidate_count = null ) {
		try {
			return parent::convertTextToSpeeches( $candidate_count );
		} catch ( InvalidArgumentException $e ) {
			return new \WP_Error(
				'prompt_builder_invalid_argument',
				$e->getMessage(), // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				array( 'exception' => $e )
			);
		} catch ( RuntimeException $e ) {
			return new \WP_Error(
				'prompt_builder_runtime_error',
				$e->getMessage(), // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				array( 'exception' => $e )
			);
		}
	}

	/**
	 * Generates speech from the prompt.
	 *
	 * @since n.e.x.t
	 *
	 * @return File|\WP_Error The generated speech audio file, or WP_Error on failure.
	 */
	public function generate_speech() {
		try {
			return parent::generateSpeech();
		} catch ( InvalidArgumentException $e ) {
			return new \WP_Error(
				'prompt_builder_invalid_argument',
				$e->getMessage(), // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				array( 'exception' => $e )
			);
		} catch ( RuntimeException $e ) {
			return new \WP_Error(
				'prompt_builder_runtime_error',
				$e->getMessage(), // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				array( 'exception' => $e )
			);
		}
	}

	/**
	 * Generates multiple speech outputs from the prompt.
	 *
	 * @since n.e.x.t
	 *
	 * @param int|null $candidate_count The number of speech outputs to generate.
	 *
	 * @return list<File>|\WP_Error The generated speech audio files, or WP_Error on failure.
	 */
	public function generate_speeches( ?int $candidate_count = null ) {
		try {
			return parent::generateSpeeches( $candidate_count );
		} catch ( InvalidArgumentException $e ) {
			return new \WP_Error(
				'prompt_builder_invalid_argument',
				$e->getMessage(), // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				array( 'exception' => $e )
			);
		} catch ( RuntimeException $e ) {
			return new \WP_Error(
				'prompt_builder_runtime_error',
				$e->getMessage(), // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				array( 'exception' => $e )
			);
		}
	}
}
