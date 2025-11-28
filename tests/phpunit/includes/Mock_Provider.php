<?php
/**
 * Mock Provider for testing.
 *
 * @package WordPress\AI_Client
 */

namespace WordPress\AI_Client\PHPUnit\Includes;

use WordPress\AiClient\Providers\Contracts\ProviderInterface;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\Enums\ProviderTypeEnum;
use WordPress\AiClient\Providers\Models\DTO\ModelConfig;
use WordPress\AiClient\Providers\Models\Contracts\ModelInterface;
use WordPress\AiClient\Providers\Contracts\ProviderAvailabilityInterface;
use WordPress\AiClient\Providers\Contracts\ModelMetadataDirectoryInterface;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;

class Mock_Provider implements ProviderInterface {
	public static function metadata(): ProviderMetadata {
		return new ProviderMetadata( 'mock-provider', 'Mock Provider', ProviderTypeEnum::cloud() );
	}

	// phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
	public static function model( string $modelId, ?ModelConfig $modelConfig = null ): ModelInterface {
		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
		return new Mock_Model( $modelId );
	}

	public static function availability(): ProviderAvailabilityInterface {
		return new class() implements ProviderAvailabilityInterface {
			public function isConfigured(): bool {
				return true;
			}
		};
	}

	public static function modelMetadataDirectory(): ModelMetadataDirectoryInterface {
		return new class() implements ModelMetadataDirectoryInterface {
			public function listModelMetadata(): array {
				return array();
			}

			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
			public function hasModelMetadata( string $modelId ): bool {
				return true;
			}

			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
			public function getModelMetadata( string $modelId ): ModelMetadata {
				// phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
				return new ModelMetadata( $modelId, 'Mock Model', array(), array() );
			}
		};
	}
}
