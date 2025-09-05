<?php
/**
 * Tests for Prompt Controller
 *
 * @package WordPress\AI_Client
 */

declare(strict_types=1);

namespace WordPress\AI_Client\Tests\REST\Controllers;

use PHPUnit\Framework\TestCase;
use WP_REST_Request;
use WP_REST_Response;
use WordPress\AI_Client\REST\Controllers\Prompt_Controller;

/**
 * Test case for unified Prompt_Controller functionality.
 */
class Prompt_Controller_Test extends TestCase
{
    /**
     * Controller instance for testing.
     *
     * @var Prompt_Controller
     */
    private $controller;

    /**
     * Set up test fixtures.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new Prompt_Controller();
    }

    /**
     * Test that all 10 routes are registered.
     */
    public function test_register_routes_creates_all_endpoints(): void
    {
        // Clear any existing registered routes
        global $wp_rest_routes_registered;
        $wp_rest_routes_registered = [];

        $namespace = 'test/v1';
        $this->controller->register_routes($namespace);

        // Verify all 10 expected routes were registered
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
            '/prompt/generate-speech-result',
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
     * Test generate_text method.
     */
    public function test_generate_text_success(): void
    {
        // Create a partial mock of the controller to mock the PromptBuilder interaction
        $controller = $this->getMockBuilder(Prompt_Controller::class)
                          ->onlyMethods(['build_prompt_from_request'])
                          ->getMock();

        // Mock PromptBuilder that returns a string
        $mockBuilder = $this->createMock(\WordPress\AiClient\Builders\PromptBuilder::class);
        $mockBuilder->method('generateText')->willReturn('Generated text result');

        $controller->expects($this->once())
                   ->method('build_prompt_from_request')
                   ->willReturn($mockBuilder);

        $request = new WP_REST_Request();
        $request->set_param('prompt', 'Test prompt');

        $response = $controller->generate_text($request);

        $this->assertInstanceOf(WP_REST_Response::class, $response);
        
        $data = $response->get_data();
        $this->assertTrue($data['success']);
        $this->assertEquals('Generated text result', $data['data']);
        $this->assertEquals('text', $data['metadata']['type']);
        $this->assertEquals('generate-text', $data['metadata']['endpoint']);
    }

    /**
     * Test exception handling.
     */
    public function test_generate_text_handles_exceptions(): void
    {
        // Create a partial mock that throws an exception
        $controller = $this->getMockBuilder(Prompt_Controller::class)
                          ->onlyMethods(['build_prompt_from_request'])
                          ->getMock();

        $controller->expects($this->once())
                   ->method('build_prompt_from_request')
                   ->willThrowException(new \Exception('Test exception'));

        $request = new WP_REST_Request();
        $request->set_param('prompt', 'Test prompt');

        $response = $controller->generate_text($request);

        $this->assertInstanceOf(WP_REST_Response::class, $response);
        
        $data = $response->get_data();
        $this->assertFalse($data['success']);
        $this->assertEquals('generation_failed', $data['error']['code']);
        $this->assertEquals('Test exception', $data['error']['message']);
        $this->assertEquals(500, $response->get_status());
    }

    /**
     * Test prompt validation.
     */
    public function test_validate_prompt(): void
    {
        $request = new WP_REST_Request();
        
        // Valid string prompt
        $this->assertTrue($this->controller->validate_prompt('Valid prompt', $request, 'prompt'));
        
        // Valid array prompt  
        $this->assertTrue($this->controller->validate_prompt(['message1', 'message2'], $request, 'prompt'));
        
        // Invalid empty string
        $result = $this->controller->validate_prompt('', $request, 'prompt');
        $this->assertInstanceOf(\WP_Error::class, $result);
        
        // Invalid empty array
        $result = $this->controller->validate_prompt([], $request, 'prompt');
        $this->assertInstanceOf(\WP_Error::class, $result);
    }

    /**
     * Test permission checking.
     */
    public function test_check_permission(): void
    {
        $request = new WP_REST_Request();
        
        // Should return true since current_user_can is mocked to return true
        $this->assertTrue($this->controller->check_permission($request));
    }
}