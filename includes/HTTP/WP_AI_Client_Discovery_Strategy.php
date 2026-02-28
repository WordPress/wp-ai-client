<?php
/**
 * WordPress AI Client Discovery Strategy
 *
 * @package WordPress\AI_Client
 * @since 0.1.0
 */

namespace WordPress\AI_Client\HTTP;

use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Client\ClientInterface;
use WordPress\AiClient\Providers\Http\Abstracts\AbstractClientDiscoveryStrategy;

/**
 * Discovery strategy for WordPress HTTP client.
 *
 * @since 0.1.0
 */
class WP_AI_Client_Discovery_Strategy extends AbstractClientDiscoveryStrategy {

	/**
	 * Creates an instance of the WordPress HTTP client.
	 *
	 * @since 0.1.0
	 *
	 * @param Psr17Factory $psr17_factory The PSR-17 factory for creating HTTP messages.
	 * @return ClientInterface The WordPress HTTP client instance.
	 */
	protected static function createClient( Psr17Factory $psr17_factory ): ClientInterface {
		return new WordPress_HTTP_Client( $psr17_factory, $psr17_factory );
	}
}
