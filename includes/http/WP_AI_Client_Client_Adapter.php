<?php
/**
 * WordPress AI Client HTTP Client Adapter
 *
 * @package WordPress\AI_Client
 * @since 1.0.0
 */

namespace WordPress\AI_Client\HTTP;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * PSR-18 HTTP Client adapter using WordPress HTTP API
 *
 * This adapter allows WordPress HTTP functions to be used
 * as a PSR-18 compliant HTTP client.
 *
 * @since 1.0.0
 */
class WP_AI_Client_Client_Adapter implements ClientInterface {

	/**
	 * Response factory instance.
	 *
	 * @var ResponseFactoryInterface
	 */
	private $response_factory;

	/**
	 * Stream factory instance.
	 *
	 * @var StreamFactoryInterface
	 */
	private $stream_factory;

	/**
	 * Constructor.
	 *
	 * @param ResponseFactoryInterface $response_factory PSR-17 Response factory.
	 * @param StreamFactoryInterface   $stream_factory   PSR-17 Stream factory.
	 */
	public function __construct( ResponseFactoryInterface $response_factory, StreamFactoryInterface $stream_factory ) {
		$this->response_factory = $response_factory;
		$this->stream_factory   = $stream_factory;
	}

	/**
	 * Sends a PSR-7 request and returns a PSR-7 response.
	 *
	 * @param RequestInterface $request The PSR-7 request.
	 *
	 * @return ResponseInterface The PSR-7 response.
	 *
	 * @throws ClientExceptionInterface If an error happens while processing the request.
	 */
	public function sendRequest( RequestInterface $request ): ResponseInterface {
		$args = $this->prepare_wp_args( $request );
		$url  = (string) $request->getUri();

		$response = \wp_remote_request( $url, $args );

		if ( \is_wp_error( $response ) ) {
			// TODO: Update to use PHP AI Client exceptions.
			throw new \Exception(
				$response->get_error_message(),
				$response->get_error_code() ? (int) $response->get_error_code() : 0
			);
		}

		return $this->create_psr_response( $response );
	}

	/**
	 * Prepare WordPress HTTP API arguments from PSR-7 request.
	 *
	 * @param RequestInterface $request The PSR-7 request.
	 *
	 * @return array WordPress HTTP API arguments.
	 */
	private function prepare_wp_args( RequestInterface $request ): array {
		$args = array(
			'method'      => $request->getMethod(),
			'headers'     => $this->prepare_headers( $request ),
			'body'        => $this->prepare_body( $request ),
			'timeout'     => 30,
			'redirection' => 5,
			'httpversion' => $request->getProtocolVersion(),
			'blocking'    => true,
		);

		// Handle streaming requests if needed.
		if ( $request->hasHeader( 'X-Stream' ) ) {
			$args['stream']   = true;
			$args['filename'] = $request->getHeaderLine( 'X-Stream-Filename' );
		}

		return $args;
	}

	/**
	 * Prepare headers for WordPress HTTP API.
	 *
	 * @param RequestInterface $request The PSR-7 request.
	 *
	 * @return array Headers array for WordPress HTTP API.
	 */
	private function prepare_headers( RequestInterface $request ): array {
		$headers = array();

		foreach ( $request->getHeaders() as $name => $values ) {
			// Skip pseudo headers used for streaming.
			if ( strpos( $name, 'X-Stream' ) === 0 ) {
				continue;
			}

			// WordPress expects headers as name => value pairs.
			$headers[ $name ] = implode( ', ', $values );
		}

		return $headers;
	}

	/**
	 * Prepare request body for WordPress HTTP API.
	 *
	 * @param RequestInterface $request The PSR-7 request.
	 *
	 * @return string|null The request body.
	 */
	private function prepare_body( RequestInterface $request ): ?string {
		$body = $request->getBody();

		if ( $body->getSize() === 0 ) {
			return null;
		}

		// Rewind the stream to ensure we read from the beginning.
		if ( $body->isSeekable() ) {
			$body->rewind();
		}

		return (string) $body;
	}

	/**
	 * Create PSR-7 response from WordPress HTTP response.
	 *
	 * @param array $wp_response WordPress HTTP API response array.
	 *
	 * @return ResponseInterface PSR-7 response.
	 */
	private function create_psr_response( array $wp_response ): ResponseInterface {
		$status_code   = \wp_remote_retrieve_response_code( $wp_response );
		$reason_phrase = \wp_remote_retrieve_response_message( $wp_response );
		$headers       = \wp_remote_retrieve_headers( $wp_response );
		$body          = \wp_remote_retrieve_body( $wp_response );

		// Create the PSR-7 response.
		$response = $this->response_factory->createResponse( $status_code, $reason_phrase );

		// Add headers to response.
		if ( $headers instanceof \WP_HTTP_Requests_Response ) {
			$headers = $headers->get_headers();
		}

		if ( is_array( $headers ) || $headers instanceof \ArrayAccess ) {
			foreach ( $headers as $name => $value ) {
				$response = $response->withHeader( $name, $value );
			}
		}

		// Set the response body.
		if ( ! empty( $body ) ) {
			$stream   = $this->stream_factory->createStream( $body );
			$response = $response->withBody( $stream );
		}

		return $response;
	}
}