<?php
/**
 * WordPress AI Client Discovery Strategy
 *
 * @package WordPress\AI_Client
 * @since 0.1.0
 */

namespace WordPress\AI_Client\HTTP;

use Http\Discovery\Psr18ClientDiscovery;
use Http\Discovery\Strategy\DiscoveryStrategy;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Client\ClientInterface;

/**
 * Discovery strategy for WordPress HTTP client.
 *
 * @since 0.1.0
 */
class WP_AI_Client_Discovery_Strategy implements DiscoveryStrategy {

	/**
	 * Initialize and register the discovery strategy.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function init() {
		// Check if discovery is available.
		if ( ! class_exists( '\Http\Discovery\Psr18ClientDiscovery' ) ) {
			return;
		}

		// Register our discovery strategy.
		Psr18ClientDiscovery::prependStrategy( self::class );
	}

	/**
	 * Get candidates for discovery.
	 *
	 * @param string $type The type of discovery.
	 *
	 * @return array<array<string, mixed>>
	 */
	public static function getCandidates( $type ) {
		// PSR-18 HTTP Client.
		if ( ClientInterface::class === $type ) {
			return array(
				array(
					'class' => static function () {
						return self::createWordPressClient();
					},
				),
			);
		}

		// PSR-17 factories - Nyholm's Psr17Factory implements all of them.
		$psr17_factories = array(
			'Psr\Http\Message\RequestFactoryInterface',
			'Psr\Http\Message\ResponseFactoryInterface',
			'Psr\Http\Message\ServerRequestFactoryInterface',
			'Psr\Http\Message\StreamFactoryInterface',
			'Psr\Http\Message\UploadedFileFactoryInterface',
			'Psr\Http\Message\UriFactoryInterface',
		);

		if ( in_array( $type, $psr17_factories, true ) ) {
			return array(
				array(
					'class' => Psr17Factory::class,
				),
			);
		}

		return array();
	}

	/**
	 * Create an instance of the WordPress HTTP client.
	 *
	 * @return WordPress_HTTP_Client
	 */
	private static function createWordPressClient() {
		$psr17_factory = new Psr17Factory();
		return new WordPress_HTTP_Client(
			$psr17_factory, // Response factory.
			$psr17_factory  // Stream factory.
		);
	}
}
