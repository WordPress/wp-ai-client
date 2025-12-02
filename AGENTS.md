# WordPress AI Client - Coding Agent Guide

## Project Overview

An AI client and API for WordPress to communicate with any generative AI models of various capabilities using a uniform API. Built on top of the [PHP AI Client](https://github.com/WordPress/php-ai-client), it provides a WordPress-native Prompt Builder, an Admin Settings Screen for credentials, automatic credential wiring, a PSR-compliant HTTP client, and a client-side JavaScript API.

## Commands & Scripts

*   `composer lint`: Lints the PHP code (PHPCodeSniffer and PHPStan).
*   `composer phpcs`: Runs PHPCodeSniffer on the PHP code.
*   `composer phpstan`: Runs PHPStan on the PHP code.
*   `npm run build`: Builds the JavaScript assets.
*   `npm run format-js`: Formats the JavaScript code using Prettier.
*   `npm run lint-js`: Lints the JavaScript code.
*   `npm run lint-php`: Lints the PHP code using Composer inside the `wp-env` environment.
*   `npm run test-php`: Runs PHPUnit tests with single site config inside the `wp-env` environment.
*   `npm run test-php-multisite`: Runs PHPUnit tests with Multisite config inside the `wp-env` environment.

**Note:** In order to follow the WordPress code style in JavaScript, the project uses a WordPress-specific fork of Prettier (`wp-prettier`) under the hood.

## Coding Standards & Compatibility Constraints

This project adheres to the WordPress Coding Standards with specific exceptions for PSR-4 autoloading (file names match class names). More details about coding and documentation standards are outlined in the `CONTRIBUTING.md` file. Any agent working on this project MUST read and follow the guidelines in that file before starting any work.

*   **PHP Version:** Code must be compatible with **PHP 7.4**.
*   **Code Style:** All code (PHP and JavaScript) must be compliant with the WordPress Coding Standards.
*   **Type Hints:** Use explicit type hints for parameters, return values, and properties where supported by PHP 7.4.

## Core Principles

*   **Uniform API:** Provide a consistent interface for interacting with various AI providers.
*   **WordPress Native:** Integrate deeply with WordPress APIs (Abilities API, HTTP API, Settings API).
*   **Security:** Restrict access to powerful features (like arbitrary prompt generation via REST API) using capabilities (`prompt_ai`).
*   **Standards Compliance:** Adhere to PSR standards where applicable (e.g., HTTP Client) while maintaining WordPress compatibility.

## Project Architecture Overview

The project consists of three main layers:

1.  **PHP SDK (`includes/`)**: The core logic, built on `wordpress/php-ai-client`. It handles prompt building, request execution, and response parsing. It also manages API credentials and integrates with WordPress core.
2.  **REST API (`includes/REST_API/`)**: Exposes the AI capabilities to the client-side. It handles authentication and validation.
3.  **JavaScript SDK (`src/`)**: A client-side library that mirrors the PHP Prompt Builder interface, communicating with the backend via the REST API.

## Directory Structure

*   `build/`: Compiled JavaScript and CSS assets.
*   `includes/`: Main PHP source files (PSR-4 autoloaded).
    *   `API_Credentials/`: Manages API keys and settings.
    *   `Builders/`: Prompt builder implementations.
    *   `Capabilities/`: Capability management.
    *   `HTTP/`: HTTP client integrations.
    *   `REST_API/`: REST API controllers.
*   `src/`: Source TypeScript/JavaScript files.
*   `tests/`: PHPUnit tests.
    *   `phpunit/`: Test suite configuration and test files.
*   `vendor/`: Composer dependencies.

## Git Repo

The main branch for this project is called "trunk".

## Agent Guidelines

*   **DO:** Read `CONTRIBUTING.md` before writing any code.
*   **DO:** Ensure all PHP code is compatible with PHP 7.4.
*   **DO:** Use `wp_enqueue_script( 'wp-ai-client' )` to load the JS SDK.
*   **DO:** Add unit tests for new PHP functionality in `tests/phpunit/tests/`.
*   **DO:** Run `npm run lint-php` and `npm run test-php` before submitting changes.
*   **DON'T:** Use PHP 8.0+ features (e.g., constructor property promotion, union types, match expressions, enums).
*   **DON'T:** Create new top-level directories without a strong reason.
*   **DON'T:** Hardcode API credentials.

## Common Pitfalls

*   **Ignoring the Fluent API:** While the traditional API is available, the fluent API is the preferred way for implementers to use the client. Avoid writing complex, nested method calls when the fluent API provides a cleaner alternative.
*   **Duplicating Interface Documentation:** Manually writing PHPDoc descriptions for methods that implement an interface is a common pitfall. The `{@inheritDoc}` tag should be used instead to inherit the documentation from the interface.
*   **Missing Capability Checks in REST:** Exposing AI generation endpoints without proper capability checks. Always ensure `prompt_ai` capability is checked for privileged operations.
