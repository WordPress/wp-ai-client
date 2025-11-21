<?php
/**
 * Ability_Function_Resolver class.
 *
 * @since n.e.x.t
 * @package wp-ai-client
 */

namespace WordPress\AI_Client\Builders\Helpers;

use WordPress\AiClient\Messages\DTO\Message;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Messages\DTO\ModelMessage;
use WordPress\AiClient\Tools\DTO\FunctionCall;
use WordPress\AiClient\Tools\DTO\FunctionResponse;
use WP_Ability;

/**
 * Resolves and executes WordPress Abilities API function calls from AI models.
 *
 * @since n.e.x.t
 */
class Ability_Function_Resolver {

	/**
	 * Prefix used to identify ability function calls.
	 *
	 * @since n.e.x.t
	 */
	private const ABILITY_PREFIX = 'wpab__';

	/**
	 * Checks if a function call is an ability call.
	 *
	 * @since n.e.x.t
	 *
	 * @param FunctionCall $call The function call to check.
	 * @return bool True if the function call is an ability call, false otherwise.
	 */
	public function is_ability_call( FunctionCall $call ): bool {
		$name = $call->getName();
		if ( null === $name ) {
			return false;
		}

		return str_starts_with( $name, self::ABILITY_PREFIX );
	}

	/**
	 * Executes a WordPress ability from a function call.
	 *
	 * @since n.e.x.t
	 *
	 * @param FunctionCall $call The function call to execute.
	 * @return FunctionResponse The response from executing the ability.
	 */
	public function execute_ability( FunctionCall $call ): FunctionResponse {
		$function_name = $call->getName() ?? 'unknown';
		$function_id   = $call->getId() ?? 'unknown';

		// Validate that this is an ability call.
		if ( ! $this->is_ability_call( $call ) ) {
			return new FunctionResponse(
				$function_id,
				$function_name,
				array(
					'error' => 'Not an ability function call',
					'code'  => 'invalid_ability_call',
				)
			);
		}

		// Convert function name to ability name.
		$ability_name = $this->function_name_to_ability_name( $function_name );

		// Get the ability.
		$ability = wp_get_ability( $ability_name );

		if ( ! $ability instanceof WP_Ability ) {
			return new FunctionResponse(
				$function_id,
				$function_name,
				array(
					'error' => sprintf( 'Ability "%s" not found', $ability_name ),
					'code'  => 'ability_not_found',
				)
			);
		}

		// Execute the ability.
		$args   = $call->getArgs();
		$result = $ability->execute( $args );

		// Handle WP_Error responses.
		if ( is_wp_error( $result ) ) {
			return new FunctionResponse(
				$function_id,
				$function_name,
				array(
					'error' => $result->get_error_message(),
					'code'  => $result->get_error_code(),
					'data'  => $result->get_error_data(),
				)
			);
		}

		// Return successful response.
		return new FunctionResponse(
			$function_id,
			$function_name,
			$result
		);
	}

	/**
	 * Checks if a message contains any ability function calls.
	 *
	 * @since n.e.x.t
	 *
	 * @param Message $message The message to check.
	 * @return bool True if the message contains ability calls, false otherwise.
	 */
	public function has_ability_calls( Message $message ): bool {
		foreach ( $message->getParts() as $part ) {
			if ( $part->getType()->isFunctionCall() ) {
				$function_call = $part->getFunctionCall();
				if ( $function_call instanceof FunctionCall && $this->is_ability_call( $function_call ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Executes all ability function calls in a message.
	 *
	 * @since n.e.x.t
	 *
	 * @param Message $message The message containing function calls.
	 * @return Message A new message with function responses.
	 */
	public function execute_abilities( Message $message ): Message {
		$response_parts = array();

		foreach ( $message->getParts() as $part ) {
			if ( $part->getType()->isFunctionCall() ) {
				$function_call = $part->getFunctionCall();
				if ( $function_call instanceof FunctionCall ) {
					$function_response = $this->execute_ability( $function_call );
					$response_parts[]  = new MessagePart( $function_response );
				}
			}
		}

		return new ModelMessage( $response_parts );
	}

	/**
	 * Converts a function name to an ability name.
	 *
	 * Transforms "wpab__tec__create_event" to "tec/create_event".
	 *
	 * @since n.e.x.t
	 *
	 * @param string $function_name The function name to convert.
	 * @return string The ability name.
	 */
	private function function_name_to_ability_name( string $function_name ): string {
		// Remove the ability prefix.
		$without_prefix = substr( $function_name, strlen( self::ABILITY_PREFIX ) );

		// Convert double underscores to forward slashes.
		return str_replace( '__', '/', $without_prefix );
	}
}
