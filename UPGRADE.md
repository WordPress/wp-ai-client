# Upgrading to WordPress 7.0

WordPress 7.0 includes a built-in AI client, making this plugin unnecessary. When running on WordPress 7.0 or later, this plugin automatically becomes a no-op and core handles everything natively.

## New standard entry point: `wp_ai_client_prompt()`

The `wp_ai_client_prompt()` function is the standard entry point for the WordPress AI Client API. It is available both in this plugin (for WordPress < 7.0) and in WordPress core (7.0+).

```php
$text = wp_ai_client_prompt( 'Write a haiku about WordPress.' )
	->generate_text();

if ( is_wp_error( $text ) ) {
	wp_die( $text->get_error_message() );
}

echo wp_kses_post( $text );
```

This function returns a prompt builder that uses `WP_Error` for error handling, following WordPress conventions.

## Deprecated APIs

The following APIs from this plugin do **not** exist in WordPress core:

- **`AI_Client::prompt()`** - Use `wp_ai_client_prompt()` instead. The core equivalent returns `WP_Error` on failure rather than throwing exceptions.
- **`AI_Client::prompt_with_wp_error()`** - Use `wp_ai_client_prompt()` instead. This was the precursor to the core function.
- **`AI_Client::init()`** - No longer needed. Core initializes the AI client automatically.

The `WordPress\AI_Client\AI_Client` class should not be used going forward. It is not available in WordPress core.

## Behavior on WordPress 7.0+

When this plugin detects WordPress 7.0 or later, it returns early during loading and does not:

- Register any autoloader.
- Register any hooks, settings screens, or REST API routes.
- Define `wp_ai_client_prompt()` (core already provides it).

This means you can safely leave the plugin installed during the transition period without any conflicts.

## Migration checklist

1. Replace all `AI_Client::prompt()` calls with `wp_ai_client_prompt()` and handle `WP_Error` returns.
2. Replace all `AI_Client::prompt_with_wp_error()` calls with `wp_ai_client_prompt()`.
3. Remove any `AI_Client::init()` calls (or the `add_action( 'init', ... )` hook).
4. Remove the `use WordPress\AI_Client\AI_Client;` import statements.
5. Once your site runs WordPress 7.0+, deactivate and remove this plugin.
