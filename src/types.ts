/**
 * TypeScript definitions for PHP AI Client SDK DTOs.
 *
 * This file is auto-generated based on the PHP DTO classes. DO NOT MODIFY IT MANUALLY.
 */

import { MessagePartType } from './enums';
import type {
	Capability,
	FileType,
	FinishReason,
	MessagePartChannel,
	MessageRole,
	OperationState,
	ProviderType,
	MediaOrientation,
	Modality,
} from './enums';

export type File = {
	fileType: FileType;
	mimeType: string;
	url?: string;
	base64Data?: string;
};

export type TokenUsage = {
	promptTokens: number;
	completionTokens: number;
	totalTokens: number;
};

export type ProviderMetadata = {
	id: string;
	name: string;
	type: ProviderType;
	credentialsUrl?: string;
};

export type SupportedOption = {
	name: string;
	supportedValues?: unknown[];
};

export type ModelMetadata = {
	id: string;
	name: string;
	supportedCapabilities: Capability[];
	supportedOptions: SupportedOption[];
};

export type FunctionCall = {
	id?: string;
	name?: string;
	args?: unknown;
};

export type FunctionResponse = {
	id: string;
	name: string;
	response: unknown;
};

export type MessagePart =
	| {
			channel: MessagePartChannel;
			type: typeof MessagePartType.TEXT;
			text: string;
	  }
	| {
			channel: MessagePartChannel;
			type: typeof MessagePartType.FILE;
			file: File;
	  }
	| {
			channel: MessagePartChannel;
			type: typeof MessagePartType.FUNCTION_CALL;
			functionCall: FunctionCall;
	  }
	| {
			channel: MessagePartChannel;
			type: typeof MessagePartType.FUNCTION_RESPONSE;
			functionResponse: FunctionResponse;
	  };

export type Message = {
	role: MessageRole;
	parts: MessagePart[];
};

export type Candidate = {
	message: Message;
	finishReason: FinishReason;
};

export type GenerativeAiResult = {
	id: string;
	candidates: Candidate[];
	tokenUsage: TokenUsage;
	providerMetadata: ProviderMetadata;
	modelMetadata: ModelMetadata;
	additionalData?: Record< string, unknown >;
};

export type GenerativeAiOperation = {
	id: string;
	state: OperationState;
	result?: GenerativeAiResult;
};

export type FunctionDeclaration = {
	name: string;
	description: string;
	parameters?: Record< string, unknown >;
};

export type WebSearch = {
	allowedDomains?: string[];
	disallowedDomains?: string[];
};

export type ModelConfig = {
	outputModalities?: Modality[];
	systemInstruction?: string;
	candidateCount?: number;
	maxTokens?: number;
	temperature?: number;
	topP?: number;
	topK?: number;
	stopSequences?: string[];
	presencePenalty?: number;
	frequencyPenalty?: number;
	logprobs?: boolean;
	topLogprobs?: number;
	functionDeclarations?: FunctionDeclaration[];
	webSearch?: WebSearch;
	outputFileType?: FileType;
	outputMimeType?: string;
	outputSchema?: Record< string, unknown >;
	outputMediaOrientation?: MediaOrientation;
	outputMediaAspectRatio?: string;
	outputSpeechVoice?: string;
	customOptions?: Record< string, unknown >;
};

export type RequestOptions = {
	timeout?: number;
	connectTimeout?: number;
	maxRedirects?: number;
};
