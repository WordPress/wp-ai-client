# Upgrading to WordPress 7.0

WordPress 7.0 includes a built-in AI client. When running on WordPress 7.0 or later, the package disables its PHP SDK infrastructure (core handles it natively) but keeps the REST API endpoints and JavaScript API active, since those are not yet in core.

## New standard entry point: `wp_ai_client_prompt()`

The `wp_ai_client_prompt()` function is the standard entry point for the WordPress AI Client API. It is available both in this package (for WordPress < 7.0) and in WordPress core (7.0+).

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

The following APIs from this package do **not** exist in WordPress core:

- **`AI_Client::prompt()`** - Use `wp_ai_client_prompt()` instead. The core equivalent returns `WP_Error` on failure rather than throwing exceptions.
- **`AI_Client::prompt_with_wp_error()`** - Use `wp_ai_client_prompt()` instead. This was the precursor to the core function.
- **`AI_Client::init()`** - No longer needed. Core initializes the AI client automatically.

The `WordPress\AI_Client\AI_Client` class should not be used going forward. It is not available in WordPress core.

## Behavior on WordPress 7.0+

When this package detects WordPress 7.0 or later, it disables its PHP SDK infrastructure (HTTP client wiring, event dispatcher, cache, and API credentials settings screen), since core handles all of that natively.

The following remain active on all WordPress versions:

- **REST API endpoints** (`/wp-ai/v1/generate`, `/wp-ai/v1/is-supported`) — these delegate to `wp_ai_client_prompt()`, so they naturally use core's AI client on 7.0+.
- **Client-side JavaScript API** (`wp-ai-client` script) — not yet available in core.
- **Capability filters** (`prompt_ai`, `list_ai_providers_models`) — idempotent with core.

The package does not define `wp_ai_client_prompt()` on 7.0+ (core already provides it).

This means you can safely keep the package installed as a dependency during the transition period without any conflicts.

## Migration checklist

1. Replace all `AI_Client::prompt()` calls with `wp_ai_client_prompt()` and handle `WP_Error` returns.
2. Replace all `AI_Client::prompt_with_wp_error()` calls with `wp_ai_client_prompt()`.
3. Remove any `AI_Client::init()` calls (or the `add_action( 'init', ... )` hook).
4. Remove the `use WordPress\AI_Client\AI_Client;` import statements.
5. Once your site runs WordPress 7.0+, remove this package from your dependencies.
