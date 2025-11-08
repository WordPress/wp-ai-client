<?php
/**
 * Class WordPress\AI_Client\AI_Client
 *
 * @since n.e.x.t
 * @package wp-ai-client
 */

namespace WordPress\AI_Client;

use WordPress\AI_Client\Builders\Prompt_Builder;
use WordPress\AI_Client\Builders\Prompt_Builder_With_WP_Error;
use WordPress\AiClient\AiClient;

/**
 * Main AI Client class providing fluent APIs for AI operations.
 *
 * @since n.e.x.t
 *
 * @phpstan-import-type Prompt from Prompt_Builder
 */
class AI_Client {

	/**
	 * Creates a new prompt builder for fluent API usage.
	 *
	 * @since n.e.x.t
	 *
	 * @param Prompt $prompt Optional initial prompt content.
	 * @return Prompt_Builder The prompt builder instance.
	 */
	public static function prompt( $prompt = null ): Prompt_Builder {
		return new Prompt_Builder( AiClient::defaultRegistry(), $prompt );
	}

	/**
	 * Creates a new prompt builder for fluent API usage, returning WP_Error on errors.
	 *
	 * @since n.e.x.t
	 *
	 * @param Prompt $prompt Optional initial prompt content.
	 * @return Prompt_Builder_With_WP_Error The prompt builder instance.
	 */
	public static function prompt_with_wp_error( $prompt = null ): Prompt_Builder_With_WP_Error {
		return new Prompt_Builder_With_WP_Error( AiClient::defaultRegistry(), $prompt );
	}
}
