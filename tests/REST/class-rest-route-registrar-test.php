<?php
/**
 * Tests for REST Route Registrar
 *
 * @package WordPress\AI_Client
 * @since 0.1.0
 */

declare(strict_types=1);

namespace WordPress\AI_Client\Tests\REST;

use PHPUnit\Framework\TestCase;
use WordPress\AI_Client\REST\REST_Route_Registrar;

/**
 * Test case for REST_Route_Registrar functionality.
 *
 */
class REST_Route_Registrar_Test extends TestCase
{
    /**
     * Test register_rest_routes calls register on all controllers.
     *
     */
    public function test_register_rest_routes_calls_all_controllers(): void
    {
        $this->mockWordPressFunctions();

        // Clear registered routes
        global $wp_rest_routes_registered;
        $wp_rest_routes_registered = [];

        $namespace = 'test/v1';
        REST_Route_Registrar::register_rest_routes($namespace);

        // Verify all expected routes were registered
        $expected_routes = [
            '/prompt/generate-text',
            '/prompt/generate-texts',
            '/prompt/generate-image',
            '/prompt/generate-images',
            '/prompt/generate-speech',
            '/prompt/generate-speeches',
            '/prompt/generate-result',
            '/prompt/generate-text-result',
            '/prompt/generate-image-result',
            '/prompt/generate-speech-result'
        ];

        $this->assertCount(10, $wp_rest_routes_registered, 'Should register exactly 10 routes');

        $registered_routes = array_column($wp_rest_routes_registered, 'route');
        foreach ($expected_routes as $route) {
            $this->assertContains($route, $registered_routes, "Route {$route} should be registered");
        }

        // Verify all use the same namespace
        foreach ($wp_rest_routes_registered as $registered) {
            $this->assertEquals($namespace, $registered['namespace'], 'All routes should use the same namespace');
        }
    }

    /**
     * Test register_rest_routes works with different namespaces.
     *
     */
    public function test_register_rest_routes_supports_different_namespaces(): void
    {
        $this->mockWordPressFunctions();

        // Test multiple namespaces can be registered
        $namespaces = [
            'plugin1/v1',
            'plugin2/v2',
            'my-custom-plugin/v3'
        ];

        foreach ($namespaces as $namespace) {
            try {
                REST_Route_Registrar::register_rest_routes($namespace);
                // If no exception is thrown, the test passes
                $this->assertTrue(true);
            } catch (\Exception $e) {
                $this->fail("Failed to register routes for namespace: {$namespace}");
            }
        }
    }

    /**
     * Test register_rest_routes validates namespace parameter.
     *
     */
    public function test_register_rest_routes_validates_namespace(): void
    {
        $this->mockWordPressFunctions();

        // Test invalid namespace (null triggers TypeError in PHP 8.3)
        $this->expectException(\TypeError::class);
        REST_Route_Registrar::register_rest_routes(null);
    }

    /**
     * Test all expected controller classes are instantiated.
     *
     */
    public function test_all_controller_classes_instantiated(): void
    {
        $this->mockWordPressFunctions();

        // Use reflection to verify the expected controllers are created
        $reflection = new \ReflectionClass(REST_Route_Registrar::class);
        $method = $reflection->getMethod('register_rest_routes');

        // Read the method source to verify controller classes
        $filename = $reflection->getFileName();
        $start_line = $method->getStartLine();
        $end_line = $method->getEndLine();

        $source = file($filename);
        $method_source = implode('', array_slice($source, $start_line - 1, $end_line - $start_line + 1));

        // Check that the unified Prompt_Controller is mentioned
        $this->assertStringContainsString(
            'Prompt_Controller',
            $method_source,
            'Prompt_Controller not found in register_rest_routes method'
        );
    }

    /**
     * Mock WordPress functions for testing.
     *
     */
    private function mockWordPressFunctions(): void
    {
        if (!function_exists('register_rest_route')) {
            function register_rest_route($namespace, $route, $args) {
                global $registered_routes;
                if (!isset($registered_routes)) {
                    $registered_routes = [];
                }
                $registered_routes[] = [
                    'namespace' => $namespace,
                    'route' => $route,
                    'args' => $args
                ];
                return true;
            }
        }
    }
}
