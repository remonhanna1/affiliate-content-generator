<?php
/**
* Plugin Name: Affiliate Content Generator
* Plugin URI: https://yourwebsite.com/
* Description: Automated affiliate content generation using AI
* Version: 1.0.0
* Author: Your Name
* License: GPL v2 or later
*/

if (!defined('ABSPATH')) exit;

// Define plugin constants
define('ACG_VERSION', '1.0.0');
define('ACG_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ACG_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ACG_ENV', defined('WP_DEBUG') && WP_DEBUG ? 'development' : 'production');

class AffiliateContentGenerator {
    private static $instance = null;
    private $version = '1.0.0';
    private $debug_mode = false;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Set debug mode based on environment
        $this->debug_mode = (ACG_ENV === 'development');
        
        $this->load_dependencies();
        $this->init_hooks();

        // Load debug tools in development
        if ($this->debug_mode) {
            $this->load_debug_tools();
        }
    }

    private function load_dependencies() {
        require_once ACG_PLUGIN_DIR . 'includes/content-generator.php';
        require_once ACG_PLUGIN_DIR . 'includes/quality-handler.php';
        require_once ACG_PLUGIN_DIR . 'includes/api-handler.php';
        require_once ACG_PLUGIN_DIR . 'includes/seo-handler.php';
        require_once ACG_PLUGIN_DIR . 'includes/cache-handler.php';
        require_once ACG_PLUGIN_DIR . 'includes/template-handler.php';

        // Load development dependencies if in debug mode
        if ($this->debug_mode && file_exists(ACG_PLUGIN_DIR . 'test-acg.php')) {
            require_once ACG_PLUGIN_DIR . 'test-acg.php';
        }
    }

    private function load_debug_tools() {
        // Create debug directory if it doesn't exist
        $debug_dir = ACG_PLUGIN_DIR . 'debug';
        if (!file_exists($debug_dir)) {
            wp_mkdir_p($debug_dir);
        }

        // Initialize debug logging
        if (!defined('ACG_DEBUG_LOG')) {
            define('ACG_DEBUG_LOG', $debug_dir . '/debug.log');
        }
    }

    private function init_hooks() {
        // Add menu items
        add_action('admin_menu', array($this, 'add_admin_menu'));
        // Add settings
        add_action('admin_init', array($this, 'register_settings'));
        // Add assets
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        // Add AJAX handlers
        add_action('wp_ajax_generate_content', array($this, 'handle_content_generation'));
        add_action('wp_ajax_check_quality', array($this, 'handle_quality_check'));
        add_action('wp_ajax_test_api_connection', array($this, 'handle_test_api'));
        add_action('wp_ajax_detect_content_type', array($this, 'handle_detect_content_type'));
        add_action('wp_ajax_get_template_keywords', array($this, 'handle_template_keywords'));
        add_action('wp_ajax_analyze_seo_opportunities', array($this, 'handle_seo_opportunities'));
        add_action('wp_ajax_test_seo_features', array($this, 'test_seo_features')); 
    }

    public function register_settings() {
        register_setting(
            'acg_options_group',
            'acg_settings',
            array(
                'sanitize_callback' => array($this, 'sanitize_settings')
            )
        );
    }

    public function sanitize_settings($input) {
        $sanitized = array();
        
        if (isset($input['claude_api_key'])) {
            $sanitized['claude_api_key'] = sanitize_text_field($input['claude_api_key']);
        }
        
        if (isset($input['dataforseo_login'])) {
            $sanitized['dataforseo_login'] = sanitize_text_field($input['dataforseo_login']);
        }
        
        if (isset($input['dataforseo_password'])) {
            $sanitized['dataforseo_password'] = sanitize_text_field($input['dataforseo_password']);
        }

        if (isset($input['disclosure'])) {
            $sanitized['disclosure'] = sanitize_textarea_field($input['disclosure']);
        }

        if (isset($input['word_count'])) {
            $sanitized['word_count'] = absint($input['word_count']);
        }

        return $sanitized;
    }

    public function add_settings_link($links) {
        $settings_link = '<a href="admin.php?page=affiliate-generator-settings">Settings</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    public function add_admin_menu() {
        // Add main menu
        add_menu_page(
            'Affiliate Content Generator',
            'Content Generator',
            'manage_options',
            'affiliate-generator',
            array($this, 'render_admin_page'),
            'dashicons-edit',
            30
        );

        // Add settings submenu
        add_submenu_page(
            'affiliate-generator',
            'Settings',
            'Settings',
            'manage_options',
            'affiliate-generator-settings',
            array($this, 'render_settings_page')
        );
    }

    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'affiliate-generator') === false) {
            return;
        }

        $suffix = $this->debug_mode ? '' : '.min';

        wp_enqueue_style(
            'acg-admin-style',
            ACG_PLUGIN_URL . "assets/css/admin{$suffix}.css",
            array(),
            $this->version
        );

        if ($hook === 'toplevel_page_affiliate-generator') {
            wp_enqueue_script(
                'acg-admin-script',
                ACG_PLUGIN_URL . "assets/js/admin{$suffix}.js",
                array('jquery'),
                $this->version,
                true
            );
            
            wp_localize_script('acg-admin-script', 'acgData', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('acg_nonce'),
                'debug' => $this->debug_mode
            ));
        }

        if ($hook === 'content-generator_page_affiliate-generator-settings') {
            wp_enqueue_script(
                'acg-settings-script',
                ACG_PLUGIN_URL . "assets/js/settings{$suffix}.js",
                array('jquery'),
                $this->version,
                true
            );
            
            wp_localize_script('acg-settings-script', 'acgData', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('acg_nonce')
            ));
        }
    }

    public function render_admin_page() {
        require_once ACG_PLUGIN_DIR . 'templates/admin-page.php';
    }

    public function render_settings_page() {
        require_once ACG_PLUGIN_DIR . 'templates/settings-template.php';
    }

    public function handle_content_generation() {
        check_ajax_referer('acg_nonce', 'nonce');

        try {
            $data = $this->validate_request($_POST);
            $generator = ACG_Content_Generator::get_instance();
            $content = $generator->generate_content($data);

            if ($content['status'] === 'success') {
                $post_data = array(
                    'post_title'    => wp_strip_all_tags($data['topic']),
                    'post_content'  => wp_kses_post($content['content']),
                    'post_status'   => 'draft',
                    'post_type'     => 'post'
                );

                $post_id = wp_insert_post($post_data);

                if (is_wp_error($post_id)) {
                    throw new Exception('Failed to create post: ' . $post_id->get_error_message());
                }

                wp_send_json_success(array(
                    'post_id' => $post_id,
                    'message' => 'Content generated successfully'
                ));
            } else {
                throw new Exception('Content generation failed');
            }
        } catch (Exception $e) {
            $this->debug_log('Content Generation Error', $e->getMessage());
            wp_send_json_error($e->getMessage());
        }
    }

    public function handle_test_api() {
        check_ajax_referer('acg_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }

        $service = $_POST['service'];
        $credentials = $_POST['credentials'];

        if ($service === 'claude') {
            $result = $this->test_claude_api($credentials);
        } elseif ($service === 'dataforseo') {
            $result = $this->test_dataforseo_api($credentials);
        } else {
            wp_send_json_error('Invalid service');
        }

        if ($result['success']) {
            wp_send_json_success('API connection successful');
        } else {
            wp_send_json_error($result['message']);
        }
    }

    public function handle_detect_content_type() {
        check_ajax_referer('acg_nonce', 'nonce');
        
        $topic = sanitize_text_field($_POST['topic']);
        $generator = ACG_Content_Generator::get_instance();
        $type = $generator->detect_template_type($topic);
        
        wp_send_json_success($type);
    }

    public function handle_template_keywords() {
        check_ajax_referer('acg_nonce', 'nonce');
        
        try {
            $topic = sanitize_text_field($_POST['topic']);
            $template_type = sanitize_text_field($_POST['template_type']);
            
            $seo_handler = ACG_SEO_Handler::get_instance();
            $data = $seo_handler->get_template_keywords($topic, $template_type);
            
            if ($data['status'] === 'success') {
                wp_send_json_success($data);
            } else {
                wp_send_json_error($data['message']);
            }
        } catch (Exception $e) {
            $this->debug_log('Template Keywords Error', $e->getMessage());
            wp_send_json_error($e->getMessage());
        }
    }

    public function handle_seo_opportunities() {
        check_ajax_referer('acg_nonce', 'nonce');
        
        try {
            $topic = sanitize_text_field($_POST['topic']);
            $template_type = sanitize_text_field($_POST['template_type']);
            
            $seo_handler = ACG_SEO_Handler::get_instance();
            $data = $seo_handler->analyze_content_opportunities($topic, $template_type);
            
            if ($data['status'] === 'success') {
                wp_send_json_success($data);
            } else {
                wp_send_json_error($data['message']);
            }
        } catch (Exception $e) {
            $this->debug_log('SEO Analysis Error', $e->getMessage());
            wp_send_json_error($e->getMessage());
        }
    }

    public function test_seo_features() {
        check_ajax_referer('acg_nonce', 'nonce');
        
        try {
            // Test cache functionality
            $cache_handler = ACG_Cache_Handler::get_instance();
            $test_key = 'test_data_' . time();
            $test_data = array('test' => 'data');
            
            $cache_handler->set_cached_data($test_key, $test_data);
            $cached_data = $cache_handler->get_cached_data($test_key);
            
            // Test SEO handler
            $seo_handler = ACG_SEO_Handler::get_instance();
            $test_topic = 'test keyword';
            $test_type = 'product_review';
            
            $seo_data = $seo_handler->get_template_keywords($test_topic, $test_type);
            
            // Return test results
            wp_send_json_success(array(
                'cache_test' => array(
                    'success' => ($cached_data === $test_data),
                    'data' => $cached_data
                ),
                'seo_test' => array(
                    'success' => ($seo_data['status'] === 'success'),
                    'data' => $seo_data
                )
            ));
            
        } catch (Exception $e) {
            $this->debug_log('SEO Features Test Error', $e->getMessage());
            wp_send_json_error($e->getMessage());
        }
    }

    private function test_claude_api($api_key) {
        try {
            $response = wp_remote_post('https://api.anthropic.com/v1/messages', array(
                'timeout' => 30,
                'headers' => array(
                    'Content-Type' => 'application/json',
                    'x-api-key' => $api_key,
                    'anthropic-version' => '2023-06-01'
                ),
                'body' => json_encode(array(
                    'model' => 'claude-3-opus-20240229',
                    'max_tokens' => 10,
                    'messages' => [
                        ['role' => 'user', 'content' => 'Say "ok" for connection test']
                    ]
                ))
            ));

            if (is_wp_error($response)) {
                return array('success' => false, 'message' => $response->get_error_message());
            }

            $body = json_decode(wp_remote_retrieve_body($response), true);
            if (!empty($body['error'])) {
                return array('success' => false, 'message' => $body['error']['message']);
            }

            return array('success' => true);
        } catch (Exception $e) {
            return array('success' => false, 'message' => $e->getMessage());
        }
    }

    private function test_dataforseo_api($credentials) {
        try {
            $response = wp_remote_post('https://api.dataforseo.com/v3/serp/google/organic/live', array(
                'timeout' => 30,
                'headers' => array(
                    'Authorization' => 'Basic ' . base64_encode($credentials['login'] . ':' . $credentials['password']),
                    'Content-Type' => 'application/json'
                ),
                'body' => json_encode([
                    array(
                        "keyword" => "test",
                        "location_name" => "United States",
						"language_name" => "English",
                        "device" => "desktop"
                    )
                ])
            ));

            if (is_wp_error($response)) {
                return array('success' => false, 'message' => $response->get_error_message());
            }

            $body = json_decode(wp_remote_retrieve_body($response), true);
            if (!empty($body['status_code']) && $body['status_code'] === 20000) {
                return array('success' => true);
            }

            return array('success' => false, 'message' => $body['status_message'] ?? 'Unknown error');
        } catch (Exception $e) {
            return array('success' => false, 'message' => $e->getMessage());
        }
    }

    private function validate_request($data) {
        return array(
            'topic' => sanitize_text_field($data['topic']),
            'content_type' => sanitize_text_field($data['content_type']),
            'word_count' => intval($data['word_count']),
            'features' => isset($data['features']) ? array_map('sanitize_text_field', (array)$data['features']) : array()
        );
    }

    /**
     * Debug logging function
     */
    private function debug_log($message, $data = null) {
        if (!$this->debug_mode) return;

        $timestamp = date('Y-m-d H:i:s');
        $log_entry = "[{$timestamp}] {$message}";
        
        if ($data !== null) {
            $log_entry .= "\nData: " . print_r($data, true);
        }
        
        $log_entry .= "\n" . str_repeat('-', 80) . "\n";
        
        error_log($log_entry, 3, ACG_DEBUG_LOG);
    }
}

// Initialize plugin
add_action('plugins_loaded', array('AffiliateContentGenerator', 'get_instance'));

// Activation hook
register_activation_hook(__FILE__, function() {
    // Set default options
    add_option('acg_settings', array(
        'claude_api_key' => '',
        'dataforseo_login' => '',
        'dataforseo_password' => '',
        'disclosure' => 'This post may contain affiliate links.',
        'word_count' => 1500,
        'include_disclosure' => true,
        'disclosure_placement' => 'bottom'
    ));

    // Create necessary directories
    $debug_dir = plugin_dir_path(__FILE__) . 'debug';
    if (!file_exists($debug_dir)) {
        wp_mkdir_p($debug_dir);
    }

    // Set up initial debug log if in development
    if (defined('WP_DEBUG') && WP_DEBUG) {
        $log_file = $debug_dir . '/debug.log';
        if (!file_exists($log_file)) {
            file_put_contents($log_file, "Plugin activated: " . date('Y-m-d H:i:s') . "\n");
        }
    }
});

// Deactivation hook
register_deactivation_hook(__FILE__, function() {
    // Cleanup if needed
    if (defined('WP_DEBUG') && WP_DEBUG) {
        // Log deactivation in debug mode
        $debug_log = plugin_dir_path(__FILE__) . 'debug/debug.log';
        if (file_exists($debug_log)) {
            file_put_contents($debug_log, "Plugin deactivated: " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
        }
    }
});