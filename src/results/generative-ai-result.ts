import { MessagePartChannel, MessagePartType } from '../enums';
import type {
	Candidate,
	File,
	GenerativeAiResult as GenerativeAiResultType,
	Message,
	ModelMetadata,
	ProviderMetadata,
	TokenUsage,
} from '../types';

/**
 * Represents the result of a generative AI operation.
 *
 * This class wraps the raw result object and provides helper methods
 * for accessing content.
 *
 * @since 0.2.0
 */
export class GenerativeAiResult implements GenerativeAiResultType {
	/**
	 * The raw result object.
	 */
	private result: GenerativeAiResultType;

	/**
	 * Constructor.
	 *
	 * @since 0.2.0
	 *
	 * @param result The raw result object.
	 */
	public constructor( result: GenerativeAiResultType ) {
		if ( ! result.candidates || result.candidates.length === 0 ) {
			throw new Error( 'At least one candidate must be provided' );
		}
		this.result = result;
	}

	/**
	 * Gets the unique identifier for this result.
	 *
	 * @since 0.2.0
	 *
	 * @return The ID.
	 */
	public get id(): string {
		return this.result.id;
	}

	/**
	 * Gets the generated candidates.
	 *
	 * @since 0.2.0
	 *
	 * @return The candidates.
	 */
	public get candidates(): Candidate[] {
		return this.result.candidates;
	}

	/**
	 * Gets the token usage statistics.
	 *
	 * @since 0.2.0
	 *
	 * @return The token usage.
	 */
	public get tokenUsage(): TokenUsage {
		return this.result.tokenUsage;
	}

	/**
	 * Gets the provider metadata.
	 *
	 * @since 0.2.0
	 *
	 * @return The provider metadata.
	 */
	public get providerMetadata(): ProviderMetadata {
		return this.result.providerMetadata;
	}

	/**
	 * Gets the model metadata.
	 *
	 * @since 0.2.0
	 *
	 * @return The model metadata.
	 */
	public get modelMetadata(): ModelMetadata {
		return this.result.modelMetadata;
	}

	/**
	 * Gets additional data.
	 *
	 * @since 0.2.0
	 *
	 * @return The additional data.
	 */
	public get additionalData(): Record< string, unknown > | undefined {
		return this.result.additionalData;
	}

	/**
	 * Gets the total number of candidates.
	 *
	 * @since 0.2.0
	 *
	 * @return The total number of candidates.
	 */
	public getCandidateCount(): number {
		return this.result.candidates.length;
	}

	/**
	 * Checks if the result has multiple candidates.
	 *
	 * @since 0.2.0
	 *
	 * @return True if there are multiple candidates, false otherwise.
	 */
	public hasMultipleCandidates(): boolean {
		return this.getCandidateCount() > 1;
	}

	/**
	 * Converts the first candidate to text.
	 *
	 * Only text from the content channel is considered. Text within model thought or reasoning is ignored.
	 *
	 * @since 0.2.0
	 *
	 * @return The text content.
	 * @throws Error If no text content.
	 */
	public toText(): string {
		const message = this.result.candidates[ 0 ].message;
		for ( const part of message.parts ) {
			if (
				part.channel === MessagePartChannel.CONTENT &&
				part.type === MessagePartType.TEXT
			) {
				return part.text;
			}
		}

		throw new Error( 'No text content found in first candidate' );
	}

	/**
	 * Converts the first candidate to a file.
	 *
	 * Only files from the content channel are considered. Files within model thought or reasoning are ignored.
	 *
	 * @since 0.2.0
	 *
	 * @return The file.
	 * @throws Error If no file content.
	 */
	public toFile(): File {
		const message = this.result.candidates[ 0 ].message;
		for ( const part of message.parts ) {
			if (
				part.channel === MessagePartChannel.CONTENT &&
				part.type === MessagePartType.FILE
			) {
				return part.file;
			}
		}

		throw new Error( 'No file content found in first candidate' );
	}

	/**
	 * Converts the first candidate to an image file.
	 *
	 * @since 0.2.0
	 *
	 * @return The image file.
	 * @throws Error If no image content.
	 */
	public toImageFile(): File {
		const file = this.toFile();

		if ( ! this.isImage( file ) ) {
			throw new Error(
				`File is not an image. MIME type: ${ file.mimeType }`
			);
		}

		return file;
	}

	/**
	 * Converts the first candidate to an audio file.
	 *
	 * @since 0.2.0
	 *
	 * @return The audio file.
	 * @throws Error If no audio content.
	 */
	public toAudioFile(): File {
		const file = this.toFile();

		if ( ! this.isAudio( file ) ) {
			throw new Error(
				`File is not an audio file. MIME type: ${ file.mimeType }`
			);
		}

		return file;
	}

	/**
	 * Converts the first candidate to a video file.
	 *
	 * @since 0.2.0
	 *
	 * @return The video file.
	 * @throws Error If no video content.
	 */
	public toVideoFile(): File {
		const file = this.toFile();

		if ( ! this.isVideo( file ) ) {
			throw new Error(
				`File is not a video file. MIME type: ${ file.mimeType }`
			);
		}

		return file;
	}

	/**
	 * Converts the first candidate to a message.
	 *
	 * @since 0.2.0
	 *
	 * @return The message.
	 */
	public toMessage(): Message {
		return this.result.candidates[ 0 ].message;
	}

	/**
	 * Converts all candidates to text.
	 *
	 * @since 0.2.0
	 *
	 * @return Array of text content.
	 */
	public toTexts(): string[] {
		const texts: string[] = [];
		for ( const candidate of this.result.candidates ) {
			const message = candidate.message;
			for ( const part of message.parts ) {
				if (
					part.channel === MessagePartChannel.CONTENT &&
					part.type === MessagePartType.TEXT
				) {
					texts.push( part.text );
					break;
				}
			}
		}
		return texts;
	}

	/**
	 * Converts all candidates to files.
	 *
	 * @since 0.2.0
	 *
	 * @return Array of files.
	 */
	public toFiles(): File[] {
		const files: File[] = [];
		for ( const candidate of this.result.candidates ) {
			const message = candidate.message;
			for ( const part of message.parts ) {
				if (
					part.channel === MessagePartChannel.CONTENT &&
					part.type === MessagePartType.FILE
				) {
					files.push( part.file );
					break;
				}
			}
		}
		return files;
	}

	/**
	 * Converts all candidates to image files.
	 *
	 * @since 0.2.0
	 *
	 * @return Array of image files.
	 */
	public toImageFiles(): File[] {
		return this.toFiles().filter( ( file ) => this.isImage( file ) );
	}

	/**
	 * Converts all candidates to audio files.
	 *
	 * @since 0.2.0
	 *
	 * @return Array of audio files.
	 */
	public toAudioFiles(): File[] {
		return this.toFiles().filter( ( file ) => this.isAudio( file ) );
	}

	/**
	 * Converts all candidates to video files.
	 *
	 * @since 0.2.0
	 *
	 * @return Array of video files.
	 */
	public toVideoFiles(): File[] {
		return this.toFiles().filter( ( file ) => this.isVideo( file ) );
	}

	/**
	 * Converts all candidates to messages.
	 *
	 * @since 0.2.0
	 *
	 * @return Array of messages.
	 */
	public toMessages(): Message[] {
		return this.result.candidates.map( ( candidate ) => candidate.message );
	}

	/**
	 * Checks if a file is an image.
	 *
	 * @since 0.2.0
	 *
	 * @param file The file to check.
	 * @return True if the file is an image.
	 */
	private isImage( file: File ): boolean {
		return file.mimeType.startsWith( 'image/' );
	}

	/**
	 * Checks if a file is an audio file.
	 *
	 * @since 0.2.0
	 *
	 * @param file The file to check.
	 * @return True if the file is an audio file.
	 */
	private isAudio( file: File ): boolean {
		return file.mimeType.startsWith( 'audio/' );
	}

	/**
	 * Checks if a file is a video file.
	 *
	 * @since 0.2.0
	 *
	 * @param file The file to check.
	 * @return True if the file is a video file.
	 */
	private isVideo( file: File ): boolean {
		return file.mimeType.startsWith( 'video/' );
	}
}
