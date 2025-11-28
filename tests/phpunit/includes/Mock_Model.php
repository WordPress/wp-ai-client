<?php
/**
 * Mock Model for testing.
 *
 * @package WordPress\AI_Client
 */

namespace WordPress\AI_Client\PHPUnit\Includes;

use WordPress\AiClient\Providers\Models\Contracts\ModelInterface;
use WordPress\AiClient\Providers\Models\DTO\ModelConfig;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;

class Mock_Model implements ModelInterface {
	private $id;
	private $config;

	public function __construct( $id ) {
		$this->id     = $id;
		$this->config = new ModelConfig();
	}

	public function metadata(): ModelMetadata {
		return new ModelMetadata( $this->id, 'Mock Model', array(), array() );
	}

	public function providerMetadata(): ProviderMetadata {
		return Mock_Provider::metadata();
	}

	public function setConfig( ModelConfig $config ): void {
		$this->config = $config;
	}

	public function getConfig(): ModelConfig {
		return $this->config;
	}
}
