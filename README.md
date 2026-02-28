# WordPress AI Client

> **Deprecated:** This package is deprecated in favor of the built-in AI client in WordPress 7.0+. On 7.0+, the PHP SDK infrastructure is disabled (core handles it natively), but the REST API endpoints and JavaScript API remain active since they are not yet in core. See [UPGRADE.md](./UPGRADE.md) for migration details.

[_Part of the **AI Building Blocks for WordPress** initiative_](https://make.wordpress.org/ai/2025/07/17/ai-building-blocks)

An AI client and API for WordPress to communicate with any generative AI models of various capabilities using a uniform API.

Built on top of the [PHP AI Client](https://github.com/WordPress/php-ai-client), adapted for the WordPress ecosystem.

## Features

- **WordPress-native Prompt Builder**: Fluent API for building and configuring AI prompts, built directly on top of the PHP AI Client while following WordPress Coding Standards and best practices.
- **Admin Settings Screen**: Integrated settings screen in WP Admin to provision AI provider API credentials.
- **Automatic Credential Wiring**: Automatic wiring up of AI provider API credentials based on storage in a WordPress database option.
- **PSR-compliant HTTP Client**: HTTP client implementation using the WordPress HTTP API, fully compatible with PSR standards.
- **Client-side JavaScript API**: A JavaScript API with a similar prompt builder, using REST endpoints under the hood to connect to the server-side infrastructure.

**Note:** The client-side JavaScript API and REST endpoints are by default limited to only administrators since they allow arbitrary prompts and configuration. A `prompt_ai` capability is used to control access, and sites are able to customize how that capability is granted to users or specific roles.

## Installation

```bash
composer require wordpress/wp-ai-client
```

## Configuration

### 1. Initialize the Client

You must initialize the client on the WordPress `init` hook. This sets up the HTTP client integration and registers the settings screen. If you are using this as a plugin, this is already handled for you in `plugin.php`.

```php
add_action( 'init', array( 'WordPress\AI_Client\AI_Client', 'init' ) );
```

### 2. Configure API Credentials

Before making requests, you need to configure API keys for your desired providers (e.g. Anthropic, Google, OpenAI).

1. Go to **Settings > AI Credentials** in the WordPress Admin.
2. Enter your API keys for the providers you intend to use.
3. Save changes.

### 3. Load the JavaScript API (Optional)

To use the client-side JavaScript API, you need to enqueue the script.

```php
add_action(
	'admin_enqueue_scripts',
	static function () {
		wp_enqueue_script( 'wp-ai-client' );
	}
);
```

## Usage

The SDK provides a fluent `Prompt_Builder` interface to construct and execute AI requests.

### Text Generation

**PHP:**

```php
$text = wp_ai_client_prompt( 'Write a haiku about WordPress.' )
	->generate_text();

if ( is_wp_error( $text ) ) {
	wp_die( $text->get_error_message() );
}

echo wp_kses_post( $text );
```

**JavaScript:**

```javascript
text = await wp.aiClient.prompt( 'Write a haiku about WordPress.' )
	.generateText();

console.log( text );
```

### Image Generation

**PHP:**

```php
$image_file = wp_ai_client_prompt( 'A futuristic WordPress logo in neon style' )
	->generate_image();

if ( is_wp_error( $image_file ) ) {
	wp_die( $image_file->get_error_message() );
}

$data_uri = $image_file->getDataUri();

echo '<img src="' . esc_url( $data_uri ) . '" alt="A futuristic WordPress logo in neon style">';
```

**JavaScript:**

```javascript
const imageFile = await wp.aiClient.prompt( 'A futuristic WordPress logo in neon style' )
	.generateImage();

const dataUri = imageFile.getDataUri();

console.log( `<img src="${ dataUri }" alt="A futuristic WordPress logo in neon style">` );
```

### Advanced Usage

#### JSON Output and Temperature

**PHP:**

```php
$schema = array(
	'type'       => 'array',
	'items'      => array(
		'type'       => 'object',
		'properties' => array(
			'plugin_name' => array( 'type' => 'string' ),
			'category'    => array( 'type' => 'string' ),
		),
		'required'   => array( 'plugin_name', 'category' ),
	),
);

$json = wp_ai_client_prompt( 'List 5 popular WordPress plugins with their primary category.' )
	->using_temperature( 0.2 ) // Lower temperature for more deterministic result.
	->as_json_response( $schema )
	->generate_text();

if ( is_wp_error( $json ) ) {
	wp_die( $json->get_error_message() );
}

// Output will be a JSON string adhering to the schema.
$data = json_decode( $json, true );
```

**JavaScript:**

```javascript
const schema = {
	type: 'array',
	items: {
		type: 'object',
		properties: {
			plugin_name: { type: 'string' },
			category: { type: 'string' },
		},
		required: [ 'plugin_name', 'category' ],
	},
};

const json = await wp.aiClient.prompt( 'List 5 popular WordPress plugins with their primary category.' )
	.usingTemperature( 0.2 ) // Lower temperature for more deterministic result.
	.asJsonResponse( schema )
	.generateText();

// Output will be a JSON string adhering to the schema.
const data = JSON.parse( json );
```

#### Generating Multiple Image Candidates

**PHP:**

```php
$images = wp_ai_client_prompt( 'Aerial shot of snowy plains, cinematic.' )
	->generate_images( 4 );

if ( is_wp_error( $images ) ) {
	wp_die( $images->get_error_message() );
}

foreach ( $images as $image_file ) {
	echo '<img src="' . esc_url( $image_file->getDataUri() ) . '" alt="Aerial shot of snowy plains">';
}
```

**JavaScript:**

```javascript
const images = await wp.aiClient.prompt( 'Aerial shot of snowy plains, cinematic.' )
	.generateImages( 4 );

for ( const imageFile of images ) {
	console.log( `<img src="${ imageFile.getDataUri() }" alt="Aerial shot of snowy plains">` );
}
```

#### Multimodal Output (Text & Image)

**PHP:**

```php
use WordPress\AiClient\Messages\Enums\ModalityEnum;

$result = wp_ai_client_prompt( 'Create a recipe for a chocolate cake and include photos for the steps.' )
	->as_output_modalities( ModalityEnum::text(), ModalityEnum::image() )
	->generate_result();

if ( is_wp_error( $result ) ) {
	wp_die( $result->get_error_message() );
}

// Iterate through the message parts.
foreach ( $result->toMessage()->getParts() as $part ) {
	if ( $part->isText() ) {
		echo wp_kses_post( $part->getText() );
	} elseif ( $part->isFile() && $part->getFile()->isImage() ) {
		echo '<img src="' . esc_url( $part->getFile()->getDataUri() ) . '" alt="">';
	}
}
```

**JavaScript:**

```javascript
const { Modality, MessagePartType } = wp.aiClient.enums;

const result = await wp.aiClient.prompt( 'Create a recipe for a chocolate cake and include photos for the steps.' )
	.asOutputModalities( Modality.TEXT, Modality.IMAGE )
	.generateResult();

// Iterate through the message parts.
for ( const part of result.toMessage().parts ) {
	if ( part.type === MessagePartType.TEXT ) {
		console.log( part.text );
	} else if ( part.type === MessagePartType.FILE && part.file.isImage() ) {
		console.log( `<img src="${ part.file.getDataUri() }" alt="">` );
	}
}
```

## Best Practices

### Automatic Model Selection

By default, the SDK automatically chooses a suitable model based on the prompt's requirements (e.g., text vs. image) and the configured providers on the site. This makes your plugin **provider-agnostic**, allowing it to work on any site regardless of which AI provider the admin has configured.

### Using Model Preferences

If you prefer specific models for better performance or capabilities, use `using_model_preference()`. The SDK will try to use the first available model from your list. If none are available (e.g., provider not configured), it falls back to automatic selection.

Pass preferences as either a model ID string, or as an array of `[ provider_id, model_id ]`. For broader compatibility, it is recommended to pass only the model ID string, since there can be different providers offering the same model - e.g. many AI cloud services proxy through calls to models from third party model labs. Only pass the array of provider ID and model ID if you (for whichever reason) _only_ want to allow the model to be served from the specific provider.

**PHP:**

```php
$summary = wp_ai_client_prompt( 'Summarize the history of the printing press.' )
	->using_temperature( 0.1 )
	->using_model_preference(
		'claude-sonnet-4-5',
		'gemini-3-pro-preview',
		'gpt-5.1'
	)
	->generate_text();
```

**JavaScript:**

```javascript
const summary = await wp.aiClient.prompt( 'Summarize the history of the printing press.' )
	.usingTemperature( 0.1 )
	.usingModelPreference(
		[ 'anthropic', 'claude-sonnet-4-5' ],
		[ 'google', 'gemini-3-pro-preview' ],
		[ 'openai', 'gpt-5.1' ]
	)
	.generateText();
```

### Setting max tokens

Control the maximum number of tokens the AI model can generate in its response. Higher values allow for longer, more detailed outputs, while lower values help keep responses concise and reduce API costs.

**PHP:**

```php
use WordPress\AI_Client\AI_Client;

$text = AI_Client::prompt( 'Explain quantum computing in complicated terms.' )
	->using_max_tokens( 8000 )
	->generate_text();
```

**JavaScript:**

```javascript
const text = await wp.aiClient
	.prompt("Explain quantum computing in complicated terms.")
	.usingMaxTokens(8000)
	.generateText();
```

### Using a Specific Model

Enforcing a single specific model using `using_model()` restricts your feature to sites that have that specific provider configured. For most scenarios, this is unnecessarily opinionated. Only use this approach if you really only want to offer the feature in combination with that model.

**PHP:**

```php
use WordPress\AiClient\ProviderImplementations\Anthropic\AnthropicProvider as Anthropic;

$text = wp_ai_client_prompt( 'Explain quantum computing in simple terms.' )
	->using_model( Anthropic::model( 'claude-sonnet-4-5' ) )
	->generate_text();
```

**JavaScript:**

```javascript
const text = await wp.aiClient.prompt( 'Explain quantum computing in simple terms.' )
	.usingModel( 'anthropic', 'claude-sonnet-4-5' )
	.generateText();
```

### Feature Detection

Before actually sending an AI prompt and getting a response, always check if the prompt is supported before execution.

This is always recommended, but especially crucial if you require the use of a specific model.

**PHP:**

```php
$prompt = wp_ai_client_prompt( 'Explain quantum computing in simple terms.' )
	->using_temperature( 0.2 );

if ( $prompt->is_supported_for_text_generation() ) {
	// Safe to generate.
	$text = $prompt->generate_text();
} else {
	// Fallback: Hide feature or show setup instructions.
}
```

**JavaScript:**

```javascript
const prompt = wp.aiClient.prompt( 'Explain quantum computing in simple terms.' )
	.usingTemperature( 0.2 );

if ( await prompt.isSupportedForTextGeneration() ) {
	// Safe to generate.
	const text = await prompt.generateText();
} else {
	// Fallback: Hide feature or show setup instructions.
}
```

The above condition will only evaluate to `true` if the site has one or more providers configured with models that support text generation including a temperature configuration.

Generally, using `is_supported_for_text_generation()` (or `is_supported_for_image_generation()`, etc.) ensures you only expose AI features that can actually run on the current site configuration.

## Error Handling

The `wp_ai_client_prompt()` function returns `WP_Error` on failure, following WordPress conventions.

```php
$text = wp_ai_client_prompt( 'Hello' )
	->generate_text();

if ( is_wp_error( $text ) ) {
	wp_die( $text->get_error_message() );
}

echo wp_kses_post( $text );
```

### Exception-based (advanced)

If you prefer exceptions, use `AI_Client::prompt()` directly.

```php
use WordPress\AI_Client\AI_Client;

try {
	$text = AI_Client::prompt( 'Hello' )->generate_text();
} catch ( \Exception $e ) {
	wp_die( $e->getMessage() );
}

echo wp_kses_post( $text );
```

## Architecture

This library is a WordPress-specific wrapper around the [PHP AI Client](https://github.com/WordPress/php-ai-client).

*   **`wp_ai_client_prompt()`**: The recommended entry point function. Returns a `Prompt_Builder_With_WP_Error`.
*   **`WordPress\AI_Client\AI_Client`**: The class-based entry point (for advanced use cases).
*   **`WordPress\AI_Client\Builders\Prompt_Builder`**: A fluent builder for constructing AI requests. It maps WordPress-style `snake_case` methods to the underlying SDK's `camelCase` methods.
*   **`WordPress\AI_Client\Builders\Prompt_Builder_With_WP_Error`**: A wrapper around `Prompt_Builder` that catches exceptions and returns `WP_Error` objects.

## Further reading

See the [`Prompt_Builder` class](https://github.com/WordPress/wp-ai-client/blob/trunk/includes/Builders/Prompt_Builder.php) and its public methods for all the ways you can configure the prompt.

See the [contributing documentation](./CONTRIBUTING.md) for more information on how to get involved.
