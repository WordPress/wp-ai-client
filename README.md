# WordPress AI Client

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

You must initialize the client on the WordPress `init` hook. This sets up the HTTP client integration and registers the settings screen.

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
use WordPress\AI_Client\AI_Client;

$text = AI_Client::prompt( 'Write a haiku about WordPress.' )
	->generate_text();

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
use WordPress\AI_Client\AI_Client;

$image_file = AI_Client::prompt( 'A futuristic WordPress logo in neon style' )
	->generate_image();

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
use WordPress\AI_Client\AI_Client;

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

$json = AI_Client::prompt( 'List 5 popular WordPress plugins with their primary category.' )
	->using_temperature( 0.2 ) // Lower temperature for more deterministic result.
	->as_json_response( $schema )
	->generate_text();

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
use WordPress\AI_Client\AI_Client;

$images = AI_Client::prompt( 'Aerial shot of snowy plains, cinematic.' )
	->generate_images( 4 );

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
use WordPress\AI_Client\AI_Client;
use WordPress\AiClient\Messages\Enums\ModalityEnum;

$result = AI_Client::prompt( 'Create a recipe for a chocolate cake and include photos for the steps.' )
	->as_output_modalities( ModalityEnum::text(), ModalityEnum::image() )
	->generate_result();

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

Pass preferences as an array of `[ provider_id, model_id ]` to ensure the correct provider is targeted.

**PHP:**

```php
use WordPress\AI_Client\AI_Client;

$summary = AI_Client::prompt( 'Summarize the history of the printing press.' )
	->using_temperature( 0.1 )
	->using_model_preference(
		array( 'anthropic', 'claude-sonnet-4-5' ),
		array( 'google', 'gemini-3-pro-preview' ),
		array( 'openai', 'gpt-5.1' )
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

### Using a Specific Model

Enforcing a single specific model using `using_model()` restricts your feature to sites that have that specific provider configured. For most scenarios, this is unnecessarily opinionated. Only use this approach if you really only want to offer the feature in combination with that model.

**PHP:**

```php
use WordPress\AI_Client\AI_Client;
use WordPress\AiClient\ProviderImplementations\Anthropic\AnthropicProvider as Anthropic;

$text = AI_Client::prompt( 'Explain quantum computing in simple terms.' )
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
use WordPress\AI_Client\AI_Client;

$prompt = AI_Client::prompt( 'Explain quantum computing in simple terms.' )
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

In PHP, the SDK offers two ways to handle errors.

### 1. Exception Based

`AI_Client::prompt()` throws exceptions on failure.

**PHP:**

```php
try {
	$text = AI_Client::prompt( 'Hello' )->generate_text();
} catch ( \Exception $e ) {
	wp_die( $e->getMessage() );
}

echo wp_kses_post( $text );
```

### 2. `WP_Error` Based

`AI_Client::prompt_with_wp_error()` returns a `WP_Error` object on failure.

> **Note:** This error handling mechanism is experimental. We are gathering feedback to decide whether the SDK should primarily focus on exceptions or `WP_Error` objects.

```php
$text = AI_Client::prompt_with_wp_error( 'Hello' )
	->generate_text();

if ( is_wp_error( $text ) ) {
	wp_die( $text->get_error_message() );
}

echo wp_kses_post( $text );
```

## Architecture

This library is a WordPress-specific wrapper around the [PHP AI Client](https://github.com/WordPress/php-ai-client).

*   **`WordPress\AI_Client\AI_Client`**: The main entry point.
*   **`WordPress\AI_Client\Builders\Prompt_Builder`**: A fluent builder for constructing AI requests. It maps WordPress-style `snake_case` methods to the underlying SDK's `camelCase` methods.
*   **`WordPress\AI_Client\Builders\Prompt_Builder_With_WP_Error`**: A wrapper around `Prompt_Builder` that catches exceptions and returns `WP_Error` objects.

## Further reading

See the [`Prompt_Builder` class](https://github.com/WordPress/wp-ai-client/blob/trunk/includes/Builders/Prompt_Builder.php) and its public methods for all the ways you can configure the prompt.

See the [contributing documentation](./CONTRIBUTING.md) for more information on how to get involved.
