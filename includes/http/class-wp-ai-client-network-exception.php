<?php
/**
 * WordPress AI Client Network Exception
 *
 * @package WordPress\AI_Client
 * @since 1.0.0
 */

namespace WordPress\AI_Client\HTTP;

use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Message\RequestInterface;

/**
 * Network exception for WordPress HTTP Client adapter.
 *
 * @since 1.0.0
 */
class WP_AI_Client_Network_Exception extends \RuntimeException implements NetworkExceptionInterface {

	/**
	 * The request that caused the exception.
	 *
	 * @var RequestInterface
	 */
	private $request;

	/**
	 * Constructor.
	 *
	 * @param string                 $message  Exception message.
	 * @param RequestInterface       $request  The request that caused the exception.
	 * @param \Throwable|null        $previous Previous exception.
	 * @param int                    $code     Exception code.
	 */
	public function __construct( string $message, RequestInterface $request, ?\Throwable $previous = null, int $code = 0 ) {
		parent::__construct( $message, $code, $previous );
		$this->request = $request;
	}

	/**
	 * Returns the request.
	 *
	 * @return RequestInterface
	 */
	public function getRequest(): RequestInterface {
		return $this->request;
	}
}