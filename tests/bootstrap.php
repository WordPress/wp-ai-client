<?php
/**
 * PHPUnit bootstrap file for WordPress AI Client tests.
 *
 * @package WordPress\AI_Client
 * @since n.e.x.t
 */

// Load Composer autoloader
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

// Manually autoload our classes for testing
spl_autoload_register(function ($class) {
    // Only handle our namespace
    if (strpos($class, 'WordPress\\AI_Client\\') !== 0) {
        return;
    }

    // Remove namespace prefix
    $relative_class = str_replace('WordPress\\AI_Client\\', '', $class);

    // Convert namespace separators to directory separators
    $relative_path = str_replace('\\', DIRECTORY_SEPARATOR, $relative_class);

    // Convert class name to WordPress file naming convention
    $parts = explode(DIRECTORY_SEPARATOR, $relative_path);
    $class_name = array_pop($parts);
    $file_name = 'class-' . strtolower(str_replace('_', '-', $class_name)) . '.php';

    // Build full path
    $file_path = __DIR__ . '/../includes/' . implode(DIRECTORY_SEPARATOR, $parts) . DIRECTORY_SEPARATOR . $file_name;

    if (file_exists($file_path)) {
        require_once $file_path;
    }
});

// Define WordPress constants for testing
if (!defined('ABSPATH')) {
    define('ABSPATH', '/tmp/wordpress/');
}

if (!defined('WP_CONTENT_DIR')) {
    define('WP_CONTENT_DIR', ABSPATH . 'wp-content');
}

if (!defined('WP_PLUGIN_DIR')) {
    define('WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins');
}

// Mock WordPress functions that are commonly used
if (!function_exists('current_user_can')) {
    /**
     * Mock current_user_can function.
     *
     * @param string $capability Capability name.
     * @return bool Always returns true for testing.
     */
    function current_user_can($capability) {
        return true;
    }
}

if (!function_exists('current_time')) {
    /**
     * Mock current_time function.
     *
     * @param string $type Type of time to retrieve.
     * @param int $gmt Whether to use GMT.
     * @return string|int Current time.
     */
    function current_time($type = 'timestamp', $gmt = 0) {
        if ($type === 'mysql') {
            return date('Y-m-d H:i:s');
        }
        return time();
    }
}

if (!function_exists('register_rest_route')) {
    /**
     * Mock register_rest_route function.
     *
     * @param string $namespace REST API namespace.
     * @param string $route REST API route.
     * @param array $args Route arguments.
     * @return bool Always returns true for testing.
     */
    function register_rest_route($namespace, $route, $args = []) {
        // Store registered routes for testing verification
        global $wp_rest_routes_registered;
        if (!isset($wp_rest_routes_registered)) {
            $wp_rest_routes_registered = [];
        }

        $wp_rest_routes_registered[] = [
            'namespace' => $namespace,
            'route' => $route,
            'args' => $args
        ];

        return true;
    }
}

// Mock WordPress classes
if (!class_exists('WP_Error')) {
    /**
     * Mock WP_Error class.
     */
    class WP_Error {
        /**
         * Error codes and messages.
         *
         * @var array
         */
        public $errors = [];

        /**
         * Constructor.
         *
         * @param string $code Error code.
         * @param string $message Error message.
         * @param mixed $data Error data.
         */
        public function __construct($code = '', $message = '', $data = '') {
            if (!empty($code)) {
                $this->errors[$code][] = $message;
            }
        }

        /**
         * Get error code.
         *
         * @return string Error code.
         */
        public function get_error_code() {
            return array_keys($this->errors)[0] ?? '';
        }

        /**
         * Get error message.
         *
         * @param string $code Error code.
         * @return string Error message.
         */
        public function get_error_message($code = '') {
            if (empty($code)) {
                $code = $this->get_error_code();
            }
            return $this->errors[$code][0] ?? '';
        }
    }
}

if (!class_exists('WP_REST_Controller')) {
    /**
     * Mock WP_REST_Controller class.
     */
    abstract class WP_REST_Controller {
        /**
         * The namespace of this controller's route.
         *
         * @var string
         */
        protected $namespace;

        /**
         * The base of this controller's route.
         *
         * @var string
         */
        protected $rest_base;
    }
}

if (!class_exists('WP_REST_Request')) {
    /**
     * Mock WP_REST_Request class.
     */
    class WP_REST_Request {
        /**
         * Request parameters.
         *
         * @var array
         */
        private $params = [];

        /**
         * Set parameter.
         *
         * @param string $key Parameter key.
         * @param mixed $value Parameter value.
         */
        public function set_param($key, $value) {
            $this->params[$key] = $value;
        }

        /**
         * Get parameter.
         *
         * @param string $key Parameter key.
         * @return mixed Parameter value.
         */
        public function get_param($key) {
            return $this->params[$key] ?? null;
        }

        /**
         * Check if parameter exists.
         *
         * @param string $key Parameter key.
         * @return bool Whether parameter exists.
         */
        public function has_param($key) {
            return isset($this->params[$key]);
        }
    }
}

if (!class_exists('WP_REST_Response')) {
    /**
     * Mock WP_REST_Response class.
     */
    class WP_REST_Response {
        /**
         * Response data.
         *
         * @var mixed
         */
        private $data;

        /**
         * Response status code.
         *
         * @var int
         */
        private $status;

        /**
         * Constructor.
         *
         * @param mixed $data Response data.
         * @param int $status HTTP status code.
         */
        public function __construct($data = null, $status = 200) {
            $this->data = $data;
            $this->status = $status;
        }

        /**
         * Get response data.
         *
         * @return mixed Response data.
         */
        public function get_data() {
            return $this->data;
        }

        /**
         * Get response status code.
         *
         * @return int Status code.
         */
        public function get_status() {
            return $this->status;
        }
    }
}
