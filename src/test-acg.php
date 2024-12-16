<?php
/**
 * Test Script for Affiliate Content Generator with Debug Features
 */

class ACG_Testing {
    private $results = array();
    private $seo_handler;
    private $cache_handler;
    private $api_handler;
    private $debug_log = array();
    private $is_debug = true; // Set to true to enable debugging

    public function __construct() {
        $this->seo_handler = ACG_SEO_Handler::get_instance();
        $this->cache_handler = ACG_Cache_Handler::get_instance();
        $this->api_handler = ACG_API_Handler::get_instance();
        
        // Initialize debug log file
        if ($this->is_debug) {
            $this->init_debug_log();
        }
    }

    /**
     * Initialize debug logging
     */
    private function init_debug_log() {
        $log_file = plugin_dir_path(__FILE__) . 'debug/acg-debug.log';
        if (!file_exists(dirname($log_file))) {
            mkdir(dirname($log_file), 0755, true);
        }
        $this->debug_log_file = $log_file;
        $this->log_debug('Debug session started: ' . date('Y-m-d H:i:s'));
    }

    /**
     * Debug logging function
     */
    private function log_debug($message, $data = null) {
        if (!$this->is_debug) return;

        $timestamp = date('Y-m-d H:i:s');
        $log_entry = "[{$timestamp}] {$message}";
        
        if ($data !== null) {
            $log_entry .= "\nData: " . print_r($data, true);
        }
        
        $log_entry .= "\n" . str_repeat('-', 80) . "\n";
        
        // Log to file
        file_put_contents($this->debug_log_file, $log_entry, FILE_APPEND);
        
        // Store in memory for display
        $this->debug_log[] = array(
            'timestamp' => $timestamp,
            'message' => $message,
            'data' => $data
        );
    }

    /**
     * Display debug information
     */
    private function display_debug_info() {
        if (!$this->is_debug) return;

        echo "<div class='debug-info' style='margin-top: 20px; padding: 20px; background: #f0f0f1; border: 1px solid #ccc;'>";
        echo "<h3>Debug Information</h3>";
        echo "<pre style='max-height: 400px; overflow-y: auto;'>";
        
        foreach ($this->debug_log as $entry) {
            echo "[{$entry['timestamp']}] {$entry['message']}\n";
            if ($entry['data']) {
                echo "Data: " . print_r($entry['data'], true) . "\n";
            }
            echo str_repeat('-', 80) . "\n";
        }
        
        echo "</pre>";
        echo "</div>";
    }

    /**
     * Test API Connections with Debug Info
     */
    private function test_api_connections() {
        $this->start_test('API Connections');
        $this->log_debug('Starting API connection tests');

        // Test Claude API
        try {
            $settings = get_option('acg_settings', array());
            $this->log_debug('Testing Claude API connection', array(
                'endpoint' => 'https://api.anthropic.com/v1/messages',
                'api_key_exists' => !empty($settings['claude_api_key'])
            ));

            $response = wp_remote_post('https://api.anthropic.com/v1/messages', array(
                'headers' => array(
                    'Content-Type' => 'application/json',
                    'x-api-key' => $settings['claude_api_key'],
                    'anthropic-version' => '2023-06-01'
                ),
                'body' => json_encode(array(
                    'model' => 'claude-3-opus-20240229',
                    'max_tokens' => 10,
                    'messages' => [
                        ['role' => 'user', 'content' => 'Test connection']
                    ]
                ))
            ));

            $this->log_debug('Claude API response received', array(
                'response_code' => wp_remote_retrieve_response_code($response),
                'response_body' => wp_remote_retrieve_body($response)
            ));

            if (!is_wp_error($response)) {
                $this->log_success('Claude API Connection');
            } else {
                $this->log_error('Claude API Connection', $response->get_error_message());
            }
        } catch (Exception $e) {
            $this->log_debug('Claude API error', array(
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ));
            $this->log_error('Claude API Connection', $e->getMessage());
        }

        // Add similar debug logging for other API tests...
    }

    /**
     * Run performance diagnostics
     */
    private function run_diagnostics() {
        $this->log_debug('Running system diagnostics');

        // Check PHP version and extensions
        $diagnostics = array(
            'php_version' => PHP_VERSION,
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'required_extensions' => array(
                'curl' => extension_loaded('curl'),
                'json' => extension_loaded('json'),
                'mbstring' => extension_loaded('mbstring')
            )
        );

        $this->log_debug('System diagnostics results', $diagnostics);
        return $diagnostics;
    }

    /**
     * Monitor API response times
     */
    private function monitor_api_performance($start_time, $end_time, $api_name) {
        $duration = $end_time - $start_time;
        $this->log_debug("API Performance: {$api_name}", array(
            'duration' => $duration,
            'threshold' => 5.0, // 5 seconds threshold
            'status' => $duration > 5.0 ? 'slow' : 'normal'
        ));
    }

    public function run_tests() {
        // Run diagnostics first
        $diagnostics = $this->run_diagnostics();
        $this->display_system_info($diagnostics);

        echo "<h2>Running Content Generator Tests</h2>";
        
        // Add test execution time tracking
        $start_time = microtime(true);
        
        // Run existing tests with debug logging
        $this->test_api_connections();
        $this->test_seo_analysis();
        $this->test_caching();
        $this->test_content_generation();
        
        $end_time = microtime(true);
        $this->log_debug('Total test execution time', array(
            'duration' => $end_time - $start_time,
            'memory_peak' => memory_get_peak_usage(true)
        ));

        // Display results and debug info
        $this->display_results();
        $this->display_debug_info();
    }

    // ... [rest of the existing test methods] ...

    /**
     * Display system information
     */
    private function display_system_info($diagnostics) {
        echo "<div class='system-info' style='margin: 20px 0; padding: 20px; background: #fff; border: 1px solid #ccc;'>";
        echo "<h3>System Information</h3>";
        echo "<ul>";
        echo "<li>PHP Version: {$diagnostics['php_version']}</li>";
        echo "<li>Memory Limit: {$diagnostics['memory_limit']}</li>";
        echo "<li>Max Execution Time: {$diagnostics['max_execution_time']}s</li>";
        echo "<li>Required Extensions:<ul>";
        foreach ($diagnostics['required_extensions'] as $ext => $loaded) {
            $status = $loaded ? '✓' : '✗';
            $color = $loaded ? 'green' : 'red';
            echo "<li style='color: {$color};'>{$ext}: {$status}</li>";
        }
        echo "</ul></li>";
        echo "</ul>";
        echo "</div>";
    }
}

// Run tests if directly accessed
if (!defined('DOING_AJAX')) {
    $tester = new ACG_Testing();
    $tester->run_tests();
}
?>
