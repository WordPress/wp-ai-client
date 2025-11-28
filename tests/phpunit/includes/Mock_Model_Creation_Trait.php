<?php
/**
 * Trait for creating mock models for testing.
 *
 * @package WordPress\AI_Client
 */

namespace WordPress\AI_Client\PHPUnit\Includes;

use Exception;
use Generator;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Messages\DTO\ModelMessage;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\Enums\ProviderTypeEnum;
use WordPress\AiClient\Providers\Models\Contracts\ModelInterface;
use WordPress\AiClient\Providers\Models\DTO\ModelConfig;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;
use WordPress\AiClient\Providers\Models\ImageGeneration\Contracts\ImageGenerationModelInterface;
use WordPress\AiClient\Providers\Models\SpeechGeneration\Contracts\SpeechGenerationModelInterface;
use WordPress\AiClient\Providers\Models\TextGeneration\Contracts\TextGenerationModelInterface;
use WordPress\AiClient\Providers\Models\TextToSpeechConversion\Contracts\TextToSpeechConversionModelInterface;
use WordPress\AiClient\Providers\ProviderRegistry;
use WordPress\AiClient\Results\DTO\Candidate;
use WordPress\AiClient\Results\DTO\GenerativeAiResult;
use WordPress\AiClient\Results\DTO\TokenUsage;
use WordPress\AiClient\Results\Enums\FinishReasonEnum;
use WordPress\AI_Client\PHPUnit\Includes\Mock_Provider;

/**
 * Trait providing shared mock model creation methods for testing.
 *
 * This trait consolidates common mock model creation logic to reduce
 * code duplication across test classes and improve maintainability.
 */
trait Mock_Model_Creation_Trait {
	/**
	 * Creates a provider registry with the mock provider registered.
	 *
	 * @return ProviderRegistry The registry with mock provider.
	 */
	protected function create_registry_with_mock_provider(): ProviderRegistry {
		$registry = new ProviderRegistry();
		$registry->registerProvider( MockProvider::class );
		return $registry;
	}
	/**
	 * Creates a test GenerativeAiResult for testing purposes.
	 *
	 * @param string $content Optional content for the response.
	 * @return GenerativeAiResult
	 */
	protected function create_test_result( string $content = 'Test response' ): GenerativeAiResult {
		$candidate   = new Candidate(
			new ModelMessage( array( new MessagePart( $content ) ) ),
			FinishReasonEnum::stop()
		);
		$token_usage = new TokenUsage( 10, 20, 30 );

		$provider_metadata = new ProviderMetadata(
			'mock',
			'Mock Provider',
			ProviderTypeEnum::cloud()
		);
		$model_metadata    = new ModelMetadata(
			'mock-model',
			'Mock Model',
			array(),
			array()
		);

		return new GenerativeAiResult(
			'test-result-id',
			array( $candidate ),
			$token_usage,
			$provider_metadata,
			$model_metadata
		);
	}

	/**
	 * Creates a test model metadata instance for text generation.
	 *
	 * @param string $id Optional model ID.
	 * @param string $name Optional model name.
	 * @return ModelMetadata
	 */
	protected function create_test_text_model_metadata(
		string $id = 'test-text-model',
		string $name = 'Test Text Model'
	): ModelMetadata {
		return new ModelMetadata(
			$id,
			$name,
			array( CapabilityEnum::textGeneration() ),
			array()
		);
	}

	/**
	 * Creates a test model metadata instance for image generation.
	 *
	 * @param string $id Optional model ID.
	 * @param string $name Optional model name.
	 * @return ModelMetadata
	 */
	protected function create_test_image_model_metadata(
		string $id = 'test-image-model',
		string $name = 'Test Image Model'
	): ModelMetadata {
		return new ModelMetadata(
			$id,
			$name,
			array( CapabilityEnum::imageGeneration() ),
			array()
		);
	}

	/**
	 * Creates a test model metadata instance for speech generation.
	 *
	 * @param string $id Optional model ID.
	 * @param string $name Optional model name.
	 * @return ModelMetadata
	 */
	protected function create_test_speech_model_metadata(
		string $id = 'test-speech-model',
		string $name = 'Test Speech Model'
	): ModelMetadata {
		return new ModelMetadata(
			$id,
			$name,
			array( CapabilityEnum::speechGeneration() ),
			array()
		);
	}

	/**
	 * Creates a test model metadata instance for speech generation.
	 *
	 * @param string $id Optional model ID.
	 * @param string $name Optional model name.
	 * @return ModelMetadata
	 */
	protected function create_test_text_to_speech_model_metadata(
		string $id = 'test-text-to-speech-model',
		string $name = 'Test Text-to-Speech Model'
	): ModelMetadata {
		return new ModelMetadata(
			$id,
			$name,
			array( CapabilityEnum::textToSpeechConversion() ),
			array()
		);
	}

	/**
	 * Creates a mock text generation model using anonymous class.
	 *
	 * @param GenerativeAiResult $result The result to return from generation.
	 * @param ModelMetadata|null $metadata Optional metadata (uses default if not provided).
	 * @return ModelInterface&TextGenerationModelInterface The mock model.
	 */
	protected function create_mock_text_generation_model(
		GenerativeAiResult $result,
		?ModelMetadata $metadata = null
	): ModelInterface {
		$metadata = $metadata ?? $this->create_test_text_model_metadata();

		$provider_metadata = new ProviderMetadata(
			'mock',
			'Mock Provider',
			ProviderTypeEnum::cloud()
		);

		return new class(
			$metadata,
			$provider_metadata,
			$result
		) implements ModelInterface, TextGenerationModelInterface {
			private ModelMetadata $metadata;
			private ProviderMetadata $provider_metadata;
			private GenerativeAiResult $result;
			private ModelConfig $config;

			public function __construct(
				ModelMetadata $metadata,
				ProviderMetadata $provider_metadata,
				GenerativeAiResult $result
			) {
				$this->metadata          = $metadata;
				$this->provider_metadata = $provider_metadata;
				$this->result            = $result;
				$this->config            = new ModelConfig();
			}

			public function metadata(): ModelMetadata {
				return $this->metadata;
			}

			public function providerMetadata(): ProviderMetadata {
				return $this->provider_metadata;
			}

			public function setConfig( ModelConfig $config ): void {
				$this->config = $config;
			}

			public function getConfig(): ModelConfig {
				return $this->config;
			}

			public function generateTextResult( array $prompt ): GenerativeAiResult { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
				return $this->result;
			}

			public function streamGenerateTextResult( array $prompt ): Generator { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
				yield $this->result;
			}
		};
	}

	/**
	 * Creates a mock image generation model using anonymous class.
	 *
	 * @param GenerativeAiResult $result The result to return from generation.
	 * @param ModelMetadata|null $metadata Optional metadata (uses default if not provided).
	 * @return ModelInterface&ImageGenerationModelInterface The mock model.
	 */
	protected function create_mock_image_generation_model(
		GenerativeAiResult $result,
		?ModelMetadata $metadata = null
	): ModelInterface {
		$metadata = $metadata ?? $this->create_test_image_model_metadata();

		$provider_metadata = new ProviderMetadata(
			'mock',
			'Mock Provider',
			ProviderTypeEnum::cloud()
		);

		return new class(
			$metadata,
			$provider_metadata,
			$result
		) implements ModelInterface, ImageGenerationModelInterface {
			private ModelMetadata $metadata;
			private ProviderMetadata $provider_metadata;
			private GenerativeAiResult $result;
			private ModelConfig $config;

			public function __construct(
				ModelMetadata $metadata,
				ProviderMetadata $provider_metadata,
				GenerativeAiResult $result
			) {
				$this->metadata          = $metadata;
				$this->provider_metadata = $provider_metadata;
				$this->result            = $result;
				$this->config            = new ModelConfig();
			}

			public function metadata(): ModelMetadata {
				return $this->metadata;
			}

			public function providerMetadata(): ProviderMetadata {
				return $this->provider_metadata;
			}

			public function setConfig( ModelConfig $config ): void {
				$this->config = $config;
			}

			public function getConfig(): ModelConfig {
				return $this->config;
			}

			public function generateImageResult( array $prompt ): GenerativeAiResult { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
				return $this->result;
			}
		};
	}

	/**
	 * Creates a mock model that implements both ModelInterface and SpeechGenerationModelInterface.
	 *
	 * @param GenerativeAiResult $result The result to return from generation.
	 * @param ModelMetadata|null $metadata Optional metadata (uses default if not provided).
	 * @return ModelInterface&SpeechGenerationModelInterface The mock model.
	 */
	private function create_mock_speech_generation_model( GenerativeAiResult $result, ?ModelMetadata $metadata = null ): ModelInterface {
		$metadata = $metadata ?? $this->create_test_speech_model_metadata();

		$provider_metadata = new ProviderMetadata(
			'mock-provider',
			'Mock Provider',
			ProviderTypeEnum::cloud()
		);

		return new class(
			$metadata,
			$provider_metadata,
			$result
		) implements ModelInterface, SpeechGenerationModelInterface {
			private ModelMetadata $metadata;
			private ProviderMetadata $provider_metadata;
			private GenerativeAiResult $result;
			private ModelConfig $config;

			public function __construct(
				ModelMetadata $metadata,
				ProviderMetadata $provider_metadata,
				GenerativeAiResult $result
			) {
				$this->metadata          = $metadata;
				$this->provider_metadata = $provider_metadata;
				$this->result            = $result;
				$this->config            = new ModelConfig();
			}

			public function metadata(): ModelMetadata {
				return $this->metadata;
			}

			public function providerMetadata(): ProviderMetadata {
				return $this->provider_metadata;
			}

			public function setConfig( ModelConfig $config ): void {
				$this->config = $config;
			}

			public function getConfig(): ModelConfig {
				return $this->config;
			}

			public function generateSpeechResult( array $prompt ): GenerativeAiResult { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
				return $this->result;
			}
		};
	}

	/**
	 * Creates a mock model that implements both ModelInterface and TextToSpeechConversionModelInterface.
	 *
	 * @param GenerativeAiResult $result The result to return from generation.
	 * @param ModelMetadata|null $metadata Optional metadata (uses default if not provided).
	 * @return ModelInterface&TextToSpeechConversionModelInterface The mock model.
	 */
	private function create_mock_text_to_speech_model( GenerativeAiResult $result, ?ModelMetadata $metadata = null ): ModelInterface {
		$metadata = $metadata ?? $this->create_test_text_to_speech_model_metadata();

		$provider_metadata = new ProviderMetadata(
			'mock-provider',
			'Mock Provider',
			ProviderTypeEnum::cloud()
		);

		return new class(
			$metadata,
			$provider_metadata,
			$result
		) implements ModelInterface, TextToSpeechConversionModelInterface {
			private ModelMetadata $metadata;
			private ProviderMetadata $provider_metadata;
			private GenerativeAiResult $result;
			private ModelConfig $config;

			public function __construct(
				ModelMetadata $metadata,
				ProviderMetadata $provider_metadata,
				GenerativeAiResult $result
			) {
				$this->metadata          = $metadata;
				$this->provider_metadata = $provider_metadata;
				$this->result            = $result;
				$this->config            = new ModelConfig();
			}

			public function metadata(): ModelMetadata {
				return $this->metadata;
			}

			public function providerMetadata(): ProviderMetadata {
				return $this->provider_metadata;
			}

			public function setConfig( ModelConfig $config ): void {
				$this->config = $config;
			}

			public function getConfig(): ModelConfig {
				return $this->config;
			}

			public function convertTextToSpeechResult( array $prompt ): GenerativeAiResult { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
				return $this->result;
			}
		};
	}

	/**
	 * Creates a mock text generation model using anonymous class that throws an exception.
	 *
	 * @param Exception $exception The exception to throw from generation.
	 * @param ModelMetadata|null $metadata Optional metadata (uses default if not provided).
	 * @return ModelInterface&TextGenerationModelInterface The mock model.
	 */
	protected function create_mock_text_generation_model_with_exception(
		Exception $exception,
		?ModelMetadata $metadata = null
	): ModelInterface {
		$metadata = $metadata ?? $this->create_test_text_model_metadata();

		$provider_metadata = new ProviderMetadata(
			'mock',
			'Mock Provider',
			ProviderTypeEnum::cloud()
		);

		return new class(
			$metadata,
			$provider_metadata,
			$exception
		) implements ModelInterface, TextGenerationModelInterface {
			private ModelMetadata $metadata;
			private ProviderMetadata $provider_metadata;
			private Exception $exception;
			private ModelConfig $config;

			public function __construct(
				ModelMetadata $metadata,
				ProviderMetadata $provider_metadata,
				Exception $exception
			) {
				$this->metadata          = $metadata;
				$this->provider_metadata = $provider_metadata;
				$this->exception         = $exception;
				$this->config            = new ModelConfig();
			}

			public function metadata(): ModelMetadata {
				return $this->metadata;
			}

			public function providerMetadata(): ProviderMetadata {
				return $this->provider_metadata;
			}

			public function setConfig( ModelConfig $config ): void {
				$this->config = $config;
			}

			public function getConfig(): ModelConfig {
				return $this->config;
			}

			public function generateTextResult( array $prompt ): GenerativeAiResult { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
				throw $this->exception;
			}

			public function streamGenerateTextResult( array $prompt ): Generator { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
				throw $this->exception;
			}
		};
	}
}
