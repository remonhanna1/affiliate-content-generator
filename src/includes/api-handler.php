<?php
/**
 * API Handler for external services
 */
class ACG_API_Handler {
    private static $instance = null;
    private $api_key;
    private $model;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $settings = get_option('acg_settings', array());
        $this->api_key = $settings['api_key'] ?? '';
        $this->model = $settings['model'] ?? 'claude-3-opus-20240229';
    }

    /**
     * Make request to Claude API
     */
    public function generate_content($prompt) {
        $response = wp_remote_post('https://api.anthropic.com/v1/messages', array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'x-api-key' => $this->api_key,
                'anthropic-version' => '2023-06-01'
            ),
            'body' => json_encode(array(
                'model' => $this->model,
                'max_tokens' => 4000,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt]
                ]
            ))
        ));

        if (is_wp_error($response)) {
            throw new Exception('API request failed: ' . $response->get_error_message());
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (!empty($body['error'])) {
            throw new Exception('API error: ' . $body['error']['message']);
        }

        return $body['content'][0]['text'];
    }

    /**
     * Get keyword data from DataForSEO
     */
    public function get_keyword_data($topic) {
        $credentials = $this->get_dataforseo_credentials();
        
        $response = wp_remote_post('https://api.dataforseo.com/v3/keywords_data/google/search_volume/live', array(
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode($credentials['login'] . ':' . $credentials['password']),
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode(array(
                array(
                    "location_name" => "United States",
                    "keywords" => array($topic)
                )
            ))
        ));

        if (is_wp_error($response)) {
            throw new Exception('Keyword data request failed: ' . $response->get_error_message());
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (!isset($body['tasks'][0]['result'])) {
            throw new Exception('No keyword data available');
        }

        return $body['tasks'][0]['result'];
    }

    /**
     * Get credentials for DataForSEO
     */
    private function get_dataforseo_credentials() {
        $settings = get_option('acg_settings', array());
        return array(
            'login' => $settings['dataforseo_login'] ?? '',
            'password' => $settings['dataforseo_password'] ?? ''
        );
    }

    /**
     * Test API connection
     */
    public function test_connection() {
        try {
            $test_prompt = "Test connection. Reply with 'ok'.";
            $response = $this->generate_content($test_prompt);
            return array(
                'success' => true,
                'message' => 'API connection successful'
            );
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => $e->getMessage()
            );
        }
    }
}
