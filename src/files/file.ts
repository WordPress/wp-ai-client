import { FileType as FileTypeEnum } from '../enums';
import type { File as FileType } from '../types';

/**
 * Represents a file in the AI client.
 *
 * This class wraps the raw file object and provides helper methods
 * for accessing content.
 *
 * @since 0.2.0
 */
export class File implements FileType {
	/**
	 * The raw file object.
	 */
	protected file: FileType;

	/**
	 * Constructor.
	 *
	 * @since 0.2.0
	 *
	 * @param file The raw file object.
	 */
	public constructor( file: FileType ) {
		this.file = file;
	}

	/**
	 * Gets the type of file storage.
	 *
	 * @since 0.2.0
	 *
	 * @return The file type.
	 */
	public get fileType(): FileTypeEnum {
		return this.file.fileType;
	}

	/**
	 * Gets the MIME type of the file.
	 *
	 * @since 0.2.0
	 *
	 * @return The MIME type.
	 */
	public get mimeType(): string {
		return this.file.mimeType;
	}

	/**
	 * Gets the URL for remote files.
	 *
	 * @since 0.2.0
	 *
	 * @return The URL.
	 */
	public get url(): string | undefined {
		return this.file.url;
	}

	/**
	 * Gets the base64 data for inline files.
	 *
	 * @since 0.2.0
	 *
	 * @return The base64 data.
	 */
	public get base64Data(): string | undefined {
		return this.file.base64Data;
	}

	/**
	 * Checks if the file is an inline file.
	 *
	 * @since 0.2.0
	 *
	 * @return True if the file is inline (base64/data URI).
	 */
	public isInline(): boolean {
		return this.fileType === FileTypeEnum.INLINE;
	}

	/**
	 * Checks if the file is a remote file.
	 *
	 * @since 0.2.0
	 *
	 * @return True if the file is remote (URL).
	 */
	public isRemote(): boolean {
		return this.fileType === FileTypeEnum.REMOTE;
	}

	/**
	 * Gets the data as a data URI for inline files.
	 *
	 * @since 0.2.0
	 *
	 * @return The data URI in format: data:[mimeType];base64,[data], or undefined if not an inline file.
	 */
	public getDataUri(): string | undefined {
		if ( ! this.base64Data ) {
			return undefined;
		}

		return `data:${ this.mimeType };base64,${ this.base64Data }`;
	}

	/**
	 * Checks if the file is a video.
	 *
	 * @since 0.2.0
	 *
	 * @return True if the file is a video.
	 */
	public isVideo(): boolean {
		return this.mimeType.startsWith( 'video/' );
	}

	/**
	 * Checks if the file is an image.
	 *
	 * @since 0.2.0
	 *
	 * @return True if the file is an image.
	 */
	public isImage(): boolean {
		return this.mimeType.startsWith( 'image/' );
	}

	/**
	 * Checks if the file is audio.
	 *
	 * @since 0.2.0
	 *
	 * @return True if the file is audio.
	 */
	public isAudio(): boolean {
		return this.mimeType.startsWith( 'audio/' );
	}

	/**
	 * Checks if the file is text.
	 *
	 * @since 0.2.0
	 *
	 * @return True if the file is text.
	 */
	public isText(): boolean {
		return this.mimeType.startsWith( 'text/' );
	}

	/**
	 * Checks if the file is a document.
	 *
	 * @since 0.2.0
	 *
	 * @return True if the file is a document.
	 */
	public isDocument(): boolean {
		// Basic check for common document types
		return (
			this.mimeType === 'application/pdf' ||
			this.mimeType.startsWith( 'application/msword' ) ||
			this.mimeType.startsWith(
				'application/vnd.openxmlformats-officedocument'
			) ||
			this.mimeType.startsWith( 'application/vnd.ms-' )
		);
	}

	/**
	 * Checks if the file is a specific MIME type.
	 *
	 * @since 0.2.0
	 *
	 * @param type The mime type to check (e.g. 'image', 'text', 'video', 'audio').
	 *
	 * @return True if the file is of the specified type.
	 */
	public isMimeType( type: string ): boolean {
		return this.mimeType.startsWith( type + '/' ) || this.mimeType === type;
	}
}
