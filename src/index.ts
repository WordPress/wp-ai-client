import { PromptBuilder } from './builders/prompt-builder';
import * as enums from './enums';
import type { Message, MessagePart } from './types';

/**
 * Creates a new prompt builder for fluent API usage.
 *
 * @since 0.2.0
 *
 * @param promptInput Optional initial prompt content.
 * @return The prompt builder instance.
 */
export function prompt(
	promptInput?: string | Message | Message[] | ( string | MessagePart )[]
): PromptBuilder {
	return new PromptBuilder( promptInput );
}

export { enums };

// Expose the API in the global `wp.aiClient` namespace for external use.
const AiClient = { prompt, enums };

if (
	typeof window !== 'undefined' &&
	'wp' in window &&
	typeof ( window as any ).wp === 'object'
) {
	( ( window as any ).wp as any ).aiClient = AiClient;
}
