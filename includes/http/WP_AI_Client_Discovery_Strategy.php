<?php
/**
 * WordPress AI Client Discovery Strategy
 *
 * @package WordPress\AI_Client
 * @since n.e.x.t
 */

namespace WordPress\AI_Client\HTTP;

use Http\Discovery\Psr18ClientDiscovery;
use Http\Discovery\Strategy\DiscoveryStrategy;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Client\ClientInterface;

/**
 * Discovery strategy for WordPress HTTP client.
 *
 * @since n.e.x.t
 */
class WP_AI_Client_Discovery_Strategy implements DiscoveryStrategy {

	/**
	 * Initialize and register the discovery strategy.
	 *
	 * @since n.e.x.t
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
					'class'     => array( __CLASS__, 'createWordPressClient' ),
					'condition' => array(
						WP_AI_Client_Client_Adapter::class,
						Psr17Factory::class,
					),
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
					'class'     => Psr17Factory::class,
					'condition' => Psr17Factory::class,
				),
			);
		}

		return array();
	}

	/**
	 * Create an instance of the WordPress HTTP client.
	 *
	 * @return WP_AI_Client_Client_Adapter
	 */
	public static function createWordPressClient() {
		$psr17_factory = new Psr17Factory();
		return new WP_AI_Client_Client_Adapter(
			$psr17_factory, // Response factory.
			$psr17_factory  // Stream factory.
		);
	}
}
