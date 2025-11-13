# WordPress AI Client

[_Part of the **AI Building Blocks for WordPress** initiative_](https://make.wordpress.org/ai/2025/07/17/ai-building-blocks)

An AI client and API for WordPress to communicate with any generative AI models of various capabilities using a uniform API.

Built on top of the [PHP AI Client](https://github.com/WordPress/php-ai-client), adapted for the WordPress ecosystem.

## Features

- **WordPress-native Prompt Builder**: Alternative version of `Prompt_Builder` using WordPress Coding Standards and supporting additional WordPress paradigms like `WP_Error`.
- **Admin Settings Screen**: Integrated settings screen in WP Admin to provision AI provider API credentials.
- **Automatic Credential Wiring**: Automatic wiring up of AI provider API credentials based on storage in a WordPress database option.
- **PSR-compliant HTTP Client**: HTTP client implementation using the WordPress HTTP API, fully compatible with PSR standards.

## Installation and Configuration

1.  Add this package to your WordPress plugin.
	```bash
	composer require wordpress/wp-ai-client
	```
2.  Initialize the package by hooking up the `WordPress\AI_Client\AI_Client::init()` method to the WordPress `init` action:
	```php
	add_action( 'init', array( \WordPress\AI_Client\AI_Client::class, 'init' ) );
	```
3. With your plugin active, visit _Settings > AI Credentials_ in WP Admin to configure AI provider credentials.
4. Get started prompting various AI models by using the `WordPress\AI_Client\AI_Client::prompt( )` method.

## Configuration

## Code examples

### Text generation using a specific model

```php
use WordPress\AI_Client\AI_Client;

$text = AI_Client::prompt( 'Write a 2-verse poem about PHP.' )
	->using_model( Google::model( 'gemini-2.5-flash' ) )
	->generate_text();
```

### Text generation using any compatible model from a specific provider

```php
use WordPress\AI_Client\AI_Client;

$text = AI_Client::prompt( 'Write a 2-verse poem about PHP.' )
	->using_provider( 'openai' )
	->generate_text();
```

### Text generation using any compatible model

```php
use WordPress\AI_Client\AI_Client;

$text = AI_Client::prompt( 'Write a 2-verse poem about PHP.' )
	->generate_text();
```

### Text generation with additional parameters

```php
use WordPress\AI_Client\AI_Client;

$text = AI_Client::prompt( 'Write a 2-verse poem about PHP.' )
	->using_system_instruction( 'You are a famous poet from the 17th century.' )
	->using_temperature( 0.8 )
	->generate_text();
```

### Text generation with multiple candidates using any compatible model

```php
use WordPress\AI_Client\AI_Client;

$texts = AI_Client::prompt( 'Write a 2-verse poem about PHP.' )
	->generate_texts( 4 );
```

### Image generation using any compatible model

```php
use WordPress\AI_Client\AI_Client;

$imageFile = AI_Client::prompt( 'Generate an illustration of the PHP elephant in the Caribbean sea.' )
	->generate_image();
```

See the [`Prompt_Builder` class](https://github.com/WordPress/wp-ai-client/blob/trunk/includes/Builders/Prompt_Builder.php) and its public methods for all the ways you can configure the prompt.

**More documentation is coming soon.**

## Further reading

See the [contributing documentation](./CONTRIBUTING.md) for more information on how to get involved.
