import apiFetch from '@wordpress/api-fetch';
import {
	Capability,
	FileType,
	MessagePartChannel,
	MessagePartType,
	MessageRole,
	Modality,
} from '../enums';
import { File } from '../files/file';
import { GenerativeAiResult } from '../results/generative-ai-result';
import type {
	FunctionDeclaration,
	FunctionResponse,
	GenerativeAiResult as GenerativeAiResultType,
	Message,
	MessagePart,
	ModelConfig,
	RequestOptions,
	WebSearch,
} from '../types';

/**
 * Fluent builder for constructing AI prompts.
 *
 * This class provides a fluent interface for building prompts with various
 * content types and model configurations.
 *
 * @since n.e.x.t
 */
export class PromptBuilder {
	/**
	 * The messages in the conversation.
	 */
	protected messages: Message[] = [];

	/**
	 * The model configuration.
	 */
	protected modelConfig: ModelConfig = {};

	/**
	 * The provider ID.
	 */
	protected providerId?: string;

	/**
	 * The model ID.
	 */
	protected modelId?: string;

	/**
	 * Ordered list of preference keys to check when selecting a model.
	 */
	protected modelPreferences: ( string | [ string, string ] )[] = [];

	/**
	 * The request options.
	 */
	protected requestOptions?: RequestOptions;

	/**
	 * Constructor.
	 *
	 * @since n.e.x.t
	 *
	 * @param prompt Optional initial prompt content.
	 */
	constructor(
		prompt?: string | Message | Message[] | ( string | MessagePart )[]
	) {
		if ( prompt ) {
			if ( this.isMessagesList( prompt ) ) {
				this.messages = prompt;
			} else {
				this.messages.push(
					this.parseMessage( prompt, MessageRole.USER )
				);
			}
		}
	}

	/**
	 * Adds text to the current message.
	 *
	 * @since n.e.x.t
	 *
	 * @param text The text to add.
	 * @return this
	 */
	public withText( text: string ): this {
		const part: MessagePart = {
			channel: MessagePartChannel.CONTENT,
			type: MessagePartType.TEXT,
			text,
		};
		this.appendPartToMessages( part );
		return this;
	}

	/**
	 * Adds a file to the current message.
	 *
	 * @since n.e.x.t
	 *
	 * @param file The file object.
	 * @return this
	 */
	public withFile( file: File ): this {
		const part: MessagePart = {
			channel: MessagePartChannel.CONTENT,
			type: MessagePartType.FILE,
			file,
		};
		this.appendPartToMessages( part );
		return this;
	}

	/**
	 * Adds a function response to the current message.
	 *
	 * @since n.e.x.t
	 *
	 * @param functionResponse The function response.
	 * @return this
	 */
	public withFunctionResponse( functionResponse: FunctionResponse ): this {
		const part: MessagePart = {
			channel: MessagePartChannel.CONTENT,
			type: MessagePartType.FUNCTION_RESPONSE,
			functionResponse,
		};
		this.appendPartToMessages( part );
		return this;
	}

	/**
	 * Adds message parts to the current message.
	 *
	 * @since n.e.x.t
	 *
	 * @param parts The message parts to add.
	 * @return this
	 */
	public withMessageParts( ...parts: MessagePart[] ): this {
		for ( const part of parts ) {
			this.appendPartToMessages( part );
		}
		return this;
	}

	/**
	 * Adds history messages to the conversation.
	 *
	 * @since n.e.x.t
	 *
	 * @param messages The messages to add.
	 * @return this
	 */
	public withHistory( ...messages: Message[] ): this {
		this.messages.push( ...messages );
		return this;
	}

	/**
	 * Sets the model to use.
	 *
	 * @since n.e.x.t
	 *
	 * @param providerId The provider ID.
	 * @param modelId    The model ID.
	 * @return this
	 */
	public usingModel( providerId: string, modelId: string ): this {
		this.providerId = providerId;
		this.modelId = modelId;
		return this;
	}

	/**
	 * Sets the model preferences.
	 *
	 * @since n.e.x.t
	 *
	 * @param preferredModels The preferred models.
	 * @return this
	 */
	public usingModelPreference(
		...preferredModels: ( string | [ string, string ] )[]
	): this {
		this.modelPreferences = preferredModels;
		return this;
	}

	/**
	 * Merges the provided model configuration.
	 *
	 * @since n.e.x.t
	 *
	 * @param config The model configuration to merge.
	 * @return this
	 */
	public usingModelConfig( config: ModelConfig ): this {
		this.modelConfig = { ...this.modelConfig, ...config };
		return this;
	}

	/**
	 * Sets the provider to use.
	 *
	 * @since n.e.x.t
	 *
	 * @param providerId The provider ID.
	 * @return this
	 */
	public usingProvider( providerId: string ): this {
		this.providerId = providerId;
		return this;
	}

	/**
	 * Sets the system instruction.
	 *
	 * @since n.e.x.t
	 *
	 * @param systemInstruction The system instruction.
	 * @return this
	 */
	public usingSystemInstruction( systemInstruction: string ): this {
		this.modelConfig.systemInstruction = systemInstruction;
		return this;
	}

	/**
	 * Sets the max tokens.
	 *
	 * @since n.e.x.t
	 *
	 * @param maxTokens The max tokens.
	 * @return this
	 */
	public usingMaxTokens( maxTokens: number ): this {
		this.modelConfig.maxTokens = maxTokens;
		return this;
	}

	/**
	 * Sets the temperature.
	 *
	 * @since n.e.x.t
	 *
	 * @param temperature The temperature.
	 * @return this
	 */
	public usingTemperature( temperature: number ): this {
		this.modelConfig.temperature = temperature;
		return this;
	}

	/**
	 * Sets the top P.
	 *
	 * @since n.e.x.t
	 *
	 * @param topP The top P.
	 * @return this
	 */
	public usingTopP( topP: number ): this {
		this.modelConfig.topP = topP;
		return this;
	}

	/**
	 * Sets the top K.
	 *
	 * @since n.e.x.t
	 *
	 * @param topK The top K.
	 * @return this
	 */
	public usingTopK( topK: number ): this {
		this.modelConfig.topK = topK;
		return this;
	}

	/**
	 * Sets the stop sequences.
	 *
	 * @since n.e.x.t
	 *
	 * @param stopSequences The stop sequences.
	 * @return this
	 */
	public usingStopSequences( ...stopSequences: string[] ): this {
		const current = this.modelConfig.stopSequences || [];
		this.modelConfig.stopSequences = [ ...current, ...stopSequences ];
		return this;
	}

	/**
	 * Sets the candidate count.
	 *
	 * @since n.e.x.t
	 *
	 * @param candidateCount The candidate count.
	 * @return this
	 */
	public usingCandidateCount( candidateCount: number ): this {
		this.modelConfig.candidateCount = candidateCount;
		return this;
	}

	/**
	 * Sets the function declarations.
	 *
	 * @since n.e.x.t
	 *
	 * @param functionDeclarations The function declarations.
	 * @return this
	 */
	public usingFunctionDeclarations(
		...functionDeclarations: FunctionDeclaration[]
	): this {
		const current = this.modelConfig.functionDeclarations || [];
		this.modelConfig.functionDeclarations = [
			...current,
			...functionDeclarations,
		];
		return this;
	}

	/**
	 * Sets the presence penalty.
	 *
	 * @since n.e.x.t
	 *
	 * @param presencePenalty The presence penalty.
	 * @return this
	 */
	public usingPresencePenalty( presencePenalty: number ): this {
		this.modelConfig.presencePenalty = presencePenalty;
		return this;
	}

	/**
	 * Sets the frequency penalty.
	 *
	 * @since n.e.x.t
	 *
	 * @param frequencyPenalty The frequency penalty.
	 * @return this
	 */
	public usingFrequencyPenalty( frequencyPenalty: number ): this {
		this.modelConfig.frequencyPenalty = frequencyPenalty;
		return this;
	}

	/**
	 * Sets the web search configuration.
	 *
	 * @since n.e.x.t
	 *
	 * @param webSearch The web search configuration.
	 * @return this
	 */
	public usingWebSearch( webSearch: WebSearch ): this {
		this.modelConfig.webSearch = webSearch;
		return this;
	}

	/**
	 * Sets the request options.
	 *
	 * @since n.e.x.t
	 *
	 * @param requestOptions The request options.
	 * @return this
	 */
	public usingRequestOptions( requestOptions: RequestOptions ): this {
		this.requestOptions = requestOptions;
		return this;
	}

	/**
	 * Sets the top logprobs.
	 *
	 * @since n.e.x.t
	 *
	 * @param topLogprobs The top logprobs.
	 * @return this
	 */
	public usingTopLogprobs( topLogprobs?: number ): this {
		if ( topLogprobs !== undefined ) {
			this.modelConfig.topLogprobs = topLogprobs;
			this.modelConfig.logprobs = true;
		} else {
			this.modelConfig.logprobs = true;
		}
		return this;
	}

	/**
	 * Sets the output MIME type.
	 *
	 * @since n.e.x.t
	 *
	 * @param mimeType The MIME type.
	 * @return this
	 */
	public asOutputMimeType( mimeType: string ): this {
		this.modelConfig.outputMimeType = mimeType;
		return this;
	}

	/**
	 * Sets the output schema.
	 *
	 * @since n.e.x.t
	 *
	 * @param schema The output schema.
	 * @return this
	 */
	public asOutputSchema( schema: Record< string, unknown > ): this {
		this.modelConfig.outputSchema = schema;
		return this;
	}

	/**
	 * Sets the output modalities.
	 *
	 * @since n.e.x.t
	 *
	 * @param modalities The output modalities.
	 * @return this
	 */
	public asOutputModalities( ...modalities: Modality[] ): this {
		this.includeOutputModalities( ...modalities );
		return this;
	}

	/**
	 * Sets the output file type.
	 *
	 * @since n.e.x.t
	 *
	 * @param fileType The output file type.
	 * @return this
	 */
	public asOutputFileType( fileType: FileType ): this {
		this.modelConfig.outputFileType = fileType as string;
		return this;
	}

	/**
	 * Configures the response as JSON.
	 *
	 * @since n.e.x.t
	 *
	 * @param schema Optional schema for the JSON response.
	 * @return this
	 */
	public asJsonResponse( schema?: Record< string, unknown > ): this {
		this.asOutputMimeType( 'application/json' );
		if ( schema ) {
			this.asOutputSchema( schema );
		}
		return this;
	}

	/**
	 * Checks if the current prompt is supported by the selected model.
	 *
	 * @since n.e.x.t
	 *
	 * @param capability Optional capability to check support for.
	 * @return True if supported, false otherwise.
	 */
	public async isSupported( capability?: Capability ): Promise< boolean > {
		const response = await apiFetch< { supported: boolean } >( {
			path: '/wp-ai/v1/is-supported',
			method: 'POST',
			data: {
				messages: this.messages,
				modelConfig: this.modelConfig,
				providerId: this.providerId,
				modelId: this.modelId,
				modelPreferences: this.modelPreferences,
				capability,
				requestOptions: this.requestOptions,
			},
		} );

		return response.supported;
	}

	/**
	 * Checks if the prompt is supported for text generation.
	 *
	 * @since n.e.x.t
	 *
	 * @return True if text generation is supported.
	 */
	public async isSupportedForTextGeneration(): Promise< boolean > {
		return this.isSupported( Capability.TEXT_GENERATION );
	}

	/**
	 * Checks if the prompt is supported for image generation.
	 *
	 * @since n.e.x.t
	 *
	 * @return True if image generation is supported.
	 */
	public async isSupportedForImageGeneration(): Promise< boolean > {
		return this.isSupported( Capability.IMAGE_GENERATION );
	}

	/**
	 * Checks if the prompt is supported for text to speech conversion.
	 *
	 * @since n.e.x.t
	 *
	 * @return True if text to speech conversion is supported.
	 */
	public async isSupportedForTextToSpeechConversion(): Promise< boolean > {
		return this.isSupported( Capability.TEXT_TO_SPEECH_CONVERSION );
	}

	/**
	 * Checks if the prompt is supported for video generation.
	 *
	 * @since n.e.x.t
	 *
	 * @return True if video generation is supported.
	 */
	public async isSupportedForVideoGeneration(): Promise< boolean > {
		return this.isSupported( Capability.VIDEO_GENERATION );
	}

	/**
	 * Checks if the prompt is supported for speech generation.
	 *
	 * @since n.e.x.t
	 *
	 * @return True if speech generation is supported.
	 */
	public async isSupportedForSpeechGeneration(): Promise< boolean > {
		return this.isSupported( Capability.SPEECH_GENERATION );
	}

	/**
	 * Checks if the prompt is supported for music generation.
	 *
	 * @since n.e.x.t
	 *
	 * @return True if music generation is supported.
	 */
	public async isSupportedForMusicGeneration(): Promise< boolean > {
		return this.isSupported( Capability.MUSIC_GENERATION );
	}

	/**
	 * Checks if the prompt is supported for embedding generation.
	 *
	 * @since n.e.x.t
	 *
	 * @return True if embedding generation is supported.
	 */
	public async isSupportedForEmbeddingGeneration(): Promise< boolean > {
		return this.isSupported( Capability.EMBEDDING_GENERATION );
	}

	/**
	 * Generates a result using the configured model and prompt.
	 *
	 * @since n.e.x.t
	 *
	 * @param capability Optional capability to use.
	 * @return The generation result.
	 */
	public async generateResult(
		capability?: Capability
	): Promise< GenerativeAiResult > {
		const result: GenerativeAiResultType = await apiFetch( {
			path: '/wp-ai/v1/generate',
			method: 'POST',
			data: {
				messages: this.messages,
				modelConfig: this.modelConfig,
				providerId: this.providerId,
				modelId: this.modelId,
				modelPreferences: this.modelPreferences,
				capability,
				requestOptions: this.requestOptions,
			},
		} );

		return new GenerativeAiResult( result );
	}

	/**
	 * Generates a text result.
	 *
	 * @since n.e.x.t
	 *
	 * @return The generation result.
	 */
	public async generateTextResult(): Promise< GenerativeAiResult > {
		this.includeOutputModalities( Modality.TEXT );
		return this.generateResult( Capability.TEXT_GENERATION );
	}

	/**
	 * Generates an image result.
	 *
	 * @since n.e.x.t
	 *
	 * @return The generation result.
	 */
	public async generateImageResult(): Promise< GenerativeAiResult > {
		this.includeOutputModalities( Modality.IMAGE );
		return this.generateResult( Capability.IMAGE_GENERATION );
	}

	/**
	 * Generates a speech result.
	 *
	 * @since n.e.x.t
	 *
	 * @return The generation result.
	 */
	public async generateSpeechResult(): Promise< GenerativeAiResult > {
		this.includeOutputModalities( Modality.AUDIO );
		return this.generateResult( Capability.SPEECH_GENERATION );
	}

	/**
	 * Converts text to speech result.
	 *
	 * @since n.e.x.t
	 *
	 * @return The generation result.
	 */
	public async convertTextToSpeechResult(): Promise< GenerativeAiResult > {
		this.includeOutputModalities( Modality.AUDIO );
		return this.generateResult( Capability.TEXT_TO_SPEECH_CONVERSION );
	}

	/**
	 * Generates text.
	 *
	 * @since n.e.x.t
	 *
	 * @return The generated text.
	 */
	public async generateText(): Promise< string > {
		const result = await this.generateTextResult();
		return result.toText();
	}

	/**
	 * Generates multiple texts.
	 *
	 * @since n.e.x.t
	 *
	 * @param candidateCount Optional candidate count.
	 * @return The generated texts.
	 */
	public async generateTexts( candidateCount?: number ): Promise< string[] > {
		if ( candidateCount ) {
			this.usingCandidateCount( candidateCount );
		}
		const result = await this.generateTextResult();
		return result.toTexts();
	}

	/**
	 * Generates an image.
	 *
	 * @since n.e.x.t
	 *
	 * @return The generated image file.
	 */
	public async generateImage(): Promise< File > {
		const result = await this.generateImageResult();
		return new File( result.toImageFile() );
	}

	/**
	 * Generates multiple images.
	 *
	 * @since n.e.x.t
	 *
	 * @param candidateCount Optional candidate count.
	 * @return The generated image files.
	 */
	public async generateImages( candidateCount?: number ): Promise< File[] > {
		if ( candidateCount ) {
			this.usingCandidateCount( candidateCount );
		}
		const result = await this.generateImageResult();
		return result.toImageFiles().map( ( file ) => new File( file ) );
	}

	/**
	 * Converts text to speech.
	 *
	 * @since n.e.x.t
	 *
	 * @return The generated speech file.
	 */
	public async convertTextToSpeech(): Promise< File > {
		const result = await this.convertTextToSpeechResult();
		return new File( result.toAudioFile() );
	}

	/**
	 * Converts text to multiple speeches.
	 *
	 * @since n.e.x.t
	 *
	 * @param candidateCount Optional candidate count.
	 * @return The generated speech files.
	 */
	public async convertTextToSpeeches(
		candidateCount?: number
	): Promise< File[] > {
		if ( candidateCount ) {
			this.usingCandidateCount( candidateCount );
		}
		const result = await this.convertTextToSpeechResult();
		return result.toAudioFiles().map( ( file ) => new File( file ) );
	}

	/**
	 * Generates speech.
	 *
	 * @since n.e.x.t
	 *
	 * @return The generated speech file.
	 */
	public async generateSpeech(): Promise< File > {
		const result = await this.generateSpeechResult();
		return new File( result.toAudioFile() );
	}

	/**
	 * Generates multiple speeches.
	 *
	 * @since n.e.x.t
	 *
	 * @param candidateCount Optional candidate count.
	 * @return The generated speech files.
	 */
	public async generateSpeeches(
		candidateCount?: number
	): Promise< File[] > {
		if ( candidateCount ) {
			this.usingCandidateCount( candidateCount );
		}
		const result = await this.generateSpeechResult();
		return result.toAudioFiles().map( ( file ) => new File( file ) );
	}

	/**
	 * Appends a MessagePart to the messages array.
	 *
	 * @since n.e.x.t
	 *
	 * @param part The part to append.
	 */
	protected appendPartToMessages( part: MessagePart ): void {
		const lastMessage = this.messages[ this.messages.length - 1 ];

		if ( lastMessage && lastMessage.role === MessageRole.USER ) {
			lastMessage.parts.push( part );
			return;
		}

		this.messages.push( {
			role: MessageRole.USER,
			parts: [ part ],
		} );
	}

	/**
	 * Parses input into a Message.
	 *
	 * @since n.e.x.t
	 *
	 * @param input       The input to parse.
	 * @param defaultRole The default role.
	 * @return The parsed message.
	 */
	private parseMessage(
		input: string | MessagePart | Message | ( string | MessagePart )[],
		defaultRole: MessageRole
	): Message {
		if ( ( input as Message ).role && ( input as Message ).parts ) {
			return input as Message;
		}

		if ( ( input as MessagePart ).type ) {
			return { role: defaultRole, parts: [ input as MessagePart ] };
		}

		if ( typeof input === 'string' ) {
			if ( input.trim() === '' ) {
				throw new Error(
					'Cannot create a message from an empty string.'
				);
			}
			return {
				role: defaultRole,
				parts: [
					{
						channel: MessagePartChannel.CONTENT,
						type: MessagePartType.TEXT,
						text: input,
					},
				],
			};
		}

		if ( Array.isArray( input ) ) {
			if ( input.length === 0 ) {
				throw new Error(
					'Cannot create a message from an empty array.'
				);
			}
			const parts: MessagePart[] = [];
			for ( const item of input ) {
				if ( typeof item === 'string' ) {
					parts.push( {
						channel: MessagePartChannel.CONTENT,
						type: MessagePartType.TEXT,
						text: item,
					} );
				} else {
					parts.push( item as MessagePart );
				}
			}
			return { role: defaultRole, parts };
		}

		throw new Error( 'Invalid input for message.' );
	}

	/**
	 * Checks if the value is a list of Message objects.
	 *
	 * @since n.e.x.t
	 *
	 * @param value The value to check.
	 * @return True if the value is a list of Message objects.
	 */
	private isMessagesList( value: unknown ): value is Message[] {
		if ( ! Array.isArray( value ) || value.length === 0 ) {
			return false;
		}
		// Check if the first item looks like a Message (has role)
		return ( value[ 0 ] as Message ).role !== undefined;
	}

	/**
	 * Includes output modalities if not already present.
	 *
	 * @since n.e.x.t
	 *
	 * @param modalities The modalities to include.
	 */
	private includeOutputModalities( ...modalities: Modality[] ): void {
		const current = this.modelConfig.outputModalities || [];
		const newModalities = modalities.map( ( m ) => m as string );
		const merged = Array.from(
			new Set( [ ...current, ...newModalities ] )
		);
		this.modelConfig.outputModalities = merged;
	}
}
