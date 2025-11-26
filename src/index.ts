import { PromptBuilder } from './builders/prompt-builder';
import type { Message, MessagePart } from './types';

/**
 * Creates a new prompt builder for fluent API usage.
 *
 * @since n.e.x.t
 *
 * @param promptInput Optional initial prompt content.
 * @return The prompt builder instance.
 */
export function prompt(
	promptInput?: string | Message | Message[] | ( string | MessagePart )[]
): PromptBuilder {
	return new PromptBuilder( promptInput );
}

// Expose the prompt builder in the global `wp.aiClient` namespace for external use.
const AiClient = { prompt };

if (
	typeof window !== 'undefined' &&
	'wp' in window &&
	typeof ( window as any ).wp === 'object'
) {
	( ( window as any ).wp as any ).aiClient = AiClient;
}
