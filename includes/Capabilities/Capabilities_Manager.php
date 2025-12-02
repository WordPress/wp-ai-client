<?php
/**
 * Class WordPress\AI_Client\Capabilities\Capabilities_Manager
 *
 * @since 0.2.0
 * @package WordPress\AI_Client
 */

namespace WordPress\AI_Client\Capabilities;

/**
 * Manages capabilities for the AI Client.
 *
 * @since 0.2.0
 */
class Capabilities_Manager {

	/**
	 * Capability to prompt AI models directly.
	 *
	 * @since 0.2.0
	 * @var string
	 */
	public const PROMPT_AI_CAPABILITY = 'prompt_ai';

	/**
	 * Grants the prompt_ai capability to administrators.
	 *
	 * This method is intended to be used as a filter callback for 'user_has_cap'.
	 * It will grant the 'prompt_ai' capability to users who have the 'manage_options' capability.
	 *
	 * For customization, this filter callback can be removed and replaced with custom logic.
	 *
	 * @since 0.2.0
	 *
	 * @param array<string, bool> $allcaps An array of all the user's capabilities.
	 * @return array<string, bool> The filtered array of capabilities.
	 */
	public static function grant_prompt_ai_to_administrators( array $allcaps ): array {
		if ( isset( $allcaps['manage_options'] ) && $allcaps['manage_options'] ) {
			$allcaps[ self::PROMPT_AI_CAPABILITY ] = true;
		}
		return $allcaps;
	}
}
