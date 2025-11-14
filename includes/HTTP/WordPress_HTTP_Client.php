<?php
/**
 * WordPress AI Client HTTP Client Adapter
 *
 * @package WordPress\AI_Client
 * @since 0.1.0
 */

namespace WordPress\AI_Client\HTTP;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use WordPress\AiClient\Providers\Http\Contracts\ClientWithOptionsInterface;
use WordPress\AiClient\Providers\Http\DTO\RequestOptions;
use WordPress\AiClient\Providers\Http\Exception\NetworkException;

/**
 * PSR-18 HTTP Client adapter using WordPress HTTP API
 *
 * This adapter allows WordPress HTTP functions to be used
 * as a PSR-18 compliant HTTP client.
 *
 * @since 0.1.0
 */
class WordPress_HTTP_Client implements ClientInterface, ClientWithOptionsInterface {

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
	 * @throws NetworkException If the WordPress HTTP request fails.
	 */
	public function sendRequest( RequestInterface $request ): ResponseInterface {
		$args = $this->prepare_wp_args( $request );
		$url  = (string) $request->getUri();

		/** Ignoring PHPStan for WordPress-specific array structure. @phpstan-ignore-next-line */
		$response = \wp_remote_request( $url, $args );

		if ( \is_wp_error( $response ) ) {
			$message = sprintf(
				'Network error occurred while sending %s request to %s: %s',
				$request->getMethod(),
				$url,
				$response->get_error_message()
			);

			throw new NetworkException( $message ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		return $this->create_psr_response( $response );
	}

	/**
	 * Sends a PSR-7 request with transport options and returns a PSR-7 response.
	 *
	 * @since 0.1.0
	 *
	 * @param RequestInterface $request The PSR-7 request.
	 * @param RequestOptions   $options Transport options for the request.
	 *
	 * @return ResponseInterface The PSR-7 response.
	 *
	 * @throws NetworkException If the WordPress HTTP request fails.
	 */
	public function sendRequestWithOptions( RequestInterface $request, RequestOptions $options ): ResponseInterface {
		$args = $this->prepare_wp_args( $request, $options );
		$url  = (string) $request->getUri();

		/** Ignoring PHPStan for WordPress-specific array structure. @phpstan-ignore-next-line */
		$response = \wp_remote_request( $url, $args );

		if ( \is_wp_error( $response ) ) {
			$message = sprintf(
				'Network error occurred while sending request to %s: %s',
				$url,
				$response->get_error_message()
			);

			throw new NetworkException(
				$message, // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				$response->get_error_code() ? (int) $response->get_error_code() : 0
			);
		}

		return $this->create_psr_response( $response );
	}

	/**
	 * Prepare WordPress HTTP API arguments from PSR-7 request.
	 *
	 * @param RequestInterface    $request The PSR-7 request.
	 * @param RequestOptions|null $options Optional transport options for the request.
	 *
	 * @return array<string, mixed> WordPress HTTP API arguments.
	 */
	private function prepare_wp_args( RequestInterface $request, ?RequestOptions $options = null ): array {
		$args = array(
			'method'      => $request->getMethod(),
			'headers'     => $this->prepare_headers( $request ),
			'body'        => $this->prepare_body( $request ),
			'httpversion' => $request->getProtocolVersion(),
			'blocking'    => true,
		);

		// Apply options if provided.
		if ( null !== $options ) {
			// Set timeout if specified.
			if ( null !== $options->getTimeout() ) {
				$args['timeout'] = $options->getTimeout();
			}

			// Set redirection if specified.
			if ( null !== $options->getMaxRedirects() ) {
				$args['redirection'] = $options->getMaxRedirects();
			}
		}

		return $args;
	}

	/**
	 * Prepare headers for WordPress HTTP API.
	 *
	 * @param RequestInterface $request The PSR-7 request.
	 *
	 * @return array<string, string> Headers array for WordPress HTTP API.
	 */
	private function prepare_headers( RequestInterface $request ): array {
		$headers = array();

		foreach ( $request->getHeaders() as $name => $values ) {
			// Skip pseudo headers used for streaming.
			if ( strpos( $name, 'X-Stream' ) === 0 ) {
				continue;
			}

			// WordPress expects headers as name => value pairs.
			$headers[ (string) $name ] = implode( ', ', $values );
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
	 * @param array{headers: \Traversable<string, string|array<string>>|array<string, string|array<string>>, body: string, response: array{code: int|string, message: string}} $wp_response WordPress HTTP API response array.
	 *
	 * @return ResponseInterface PSR-7 response.
	 */
	private function create_psr_response( array $wp_response ): ResponseInterface {
		$status_code   = \wp_remote_retrieve_response_code( $wp_response );
		$reason_phrase = \wp_remote_retrieve_response_message( $wp_response );
		$headers       = \wp_remote_retrieve_headers( $wp_response );
		$body          = \wp_remote_retrieve_body( $wp_response );

		// Create the PSR-7 response.
		$response = $this->response_factory->createResponse( (int) $status_code, $reason_phrase );

		// Add headers to response.
		if ( $headers instanceof \WP_HTTP_Requests_Response ) {
			$headers = $headers->get_headers();
		}

		/**
		 * Headers from WordPress response.
		 *
		 * @var \Traversable<string, string|array<string>>|array<string, string|array<string>> $headers
		 */
		if ( is_array( $headers ) || $headers instanceof \Traversable ) {
			foreach ( $headers as $name => $value ) {
				// PSR-7 expects string name and string|array value.
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
