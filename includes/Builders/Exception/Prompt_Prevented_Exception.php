<?php
/**
 * Class WordPress\AI_Client\Builders\Exception\Prompt_Prevented_Exception
 *
 * @since n.e.x.t
 * @package WordPress\AI_Client
 */

namespace WordPress\AI_Client\Builders\Exception;

use RuntimeException;

/**
 * Exception thrown when a prompt is prevented from executing by the wp_ai_client_prevent_prompt filter.
 *
 * @since n.e.x.t
 */
class Prompt_Prevented_Exception extends RuntimeException {

}
