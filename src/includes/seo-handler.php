<?php
class ACG_SEO_Handler {
    private static $instance = null;
    private $settings;
    private $cache_handler;
    private $api_handler;
    private $last_error;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->settings = get_option('acg_settings', array());
        $this->cache_handler = ACG_Cache_Handler::get_instance();
        $this->api_handler = ACG_API_Handler::get_instance();
        $this->last_error = array();
    }

    /**
     * New method: Get template keywords
     */
    public function get_template_keywords($topic, $template_type) {
        try {
            $cache_key = "template_keywords_{$topic}_{$template_type}";
            
            if ($this->cache_handler->is_cache_valid($cache_key)) {
                return $this->cache_handler->get_cached_data($cache_key);
            }

            // Get template-specific keywords
            $template_keywords = $this->get_template_specific_keywords($topic, $template_type);
            
            // Get search volume for template keywords
            $template_volume = $this->get_search_volume_with_retry($template_keywords);
            
            // Get regular related keywords
            $related_data = $this->get_related_keywords($topic);
            
            // Analyze opportunities
            $opportunities = $this->analyze_keyword_opportunities($template_keywords, $template_volume);
            
            // Build content structure
            $content_structure = $this->build_content_structure($opportunities);
            
            $result = array(
                'status' => 'success',
                'opportunities' => $opportunities,
                'content_structure' => $content_structure,
                'suggestions' => $this->generate_content_suggestions($opportunities)
            );

            // Cache the result
            $this->cache_handler->set_cached_data($cache_key, $result);
            
            return $result;
        } catch (Exception $e) {
            $this->handle_api_failure($e->getMessage());
            return $this->get_fallback_response($topic, $template_type);
        }
    }

    /**
     * Analyze content opportunities
     */
    public function analyze_content_opportunities($topic, $template_type) {
        try {
            $cache_key = "opportunities_{$topic}_{$template_type}";
            
            if ($this->cache_handler->is_cache_valid($cache_key)) {
                return $this->cache_handler->get_cached_data($cache_key);
            }

            // Get template-specific keywords
            $template_keywords = $this->get_template_specific_keywords($topic, $template_type);
            
            // Get search volume with retry mechanism
            $template_volume = $this->get_search_volume_with_retry($template_keywords);
            
            // Get regular related keywords
            $related_data = $this->get_related_keywords($topic);
            
            // Analyze opportunities
            $opportunities = $this->analyze_keyword_opportunities($template_keywords, $template_volume);
            
            // Build content structure
            $content_structure = $this->build_content_structure($opportunities);
            
            $result = array(
                'status' => 'success',
                'opportunities' => $opportunities,
                'content_structure' => $content_structure,
                'suggestions' => $this->generate_content_suggestions($opportunities)
            );

            // Cache the result
            $this->cache_handler->set_cached_data($cache_key, $result);
            
            return $result;
        } catch (Exception $e) {
            error_log('SEO Analysis Error: ' . $e->getMessage());
            return array(
                'status' => 'error',
                'message' => $e->getMessage()
            );
        }
    }

    /**
     * Get related keywords with fallback
     */
    private function get_related_keywords($topic) {
        $cache_key = "related_keywords_{$topic}";
        
        if ($this->cache_handler->is_cache_valid($cache_key)) {
            return $this->cache_handler->get_cached_data($cache_key);
        }

        $endpoints = array(
            'primary' => 'https://api.dataforseo.com/v3/serp/google/organic/live',
            'fallback' => 'https://api.dataforseo.com/v3/keywords_data/google/search_volume/live'
        );

        $error_messages = array();
        foreach ($endpoints as $type => $endpoint) {
            try {
                $response = wp_remote_post($endpoint, array(
                    'timeout' => 30,
                    'headers' => array(
                        'Authorization' => 'Basic ' . base64_encode($this->settings['dataforseo_login'] . ':' . $this->settings['dataforseo_password']),
                        'Content-Type' => 'application/json'
                    ),
                    'body' => json_encode(array(
                        array(
                            "keyword" => $topic,
                            "location_name" => "United States",
                            "language_name" => "English",
                            "device" => "desktop"
                        )
                    ))
                ));

                if (!is_wp_error($response)) {
                    $body = json_decode(wp_remote_retrieve_body($response), true);
                    if (!empty($body['status_code']) && $body['status_code'] === 20000) {
                        $result = $this->process_related_keywords($body['tasks'][0]['result']);
                        $this->cache_handler->set_cached_data($cache_key, $result);
                        return $result;
                    }
                    $error_messages[$type] = $body['status_message'] ?? 'Unknown error';
                } else {
                    $error_messages[$type] = $response->get_error_message();
                }
            } catch (Exception $e) {
                $error_messages[$type] = $e->getMessage();
            }
        }

        $this->notify_admin_of_api_issues($error_messages);
        return array();
    }

    /**
     * Get search volume with retry mechanism
     */
    private function get_search_volume_with_retry($keywords, $max_retries = 3) {
        $attempt = 0;
        while ($attempt < $max_retries) {
            try {
                return $this->get_search_volume($keywords);
            } catch (Exception $e) {
                $attempt++;
                if ($attempt === $max_retries) {
                    $this->log_api_error('search_volume', $e->getMessage());
                    return array();
                }
                sleep(1);
            }
        }
        return array();
    }

    /**
     * Get search volume
     */
    private function get_search_volume($keywords) {
        if (empty($keywords)) {
            return array();
        }

        $cache_key = 'search_volume_' . md5(serialize($keywords));
        
        if ($this->cache_handler->is_cache_valid($cache_key)) {
            return $this->cache_handler->get_cached_data($cache_key);
        }

        try {
            $response = wp_remote_post('https://api.dataforseo.com/v3/serp/google/organic/live', array(
                'timeout' => 30,
                'headers' => array(
                    'Authorization' => 'Basic ' . base64_encode($this->settings['dataforseo_login'] . ':' . $this->settings['dataforseo_password']),
                    'Content-Type' => 'application/json'
                ),
                'body' => json_encode(array(
                    array(
                        "keyword" => implode(',', $keywords),
                        "location_name" => "United States",
                        "language_name" => "English",
                        "device" => "desktop"
                    )
                ))
            ));

            if (is_wp_error($response)) {
                throw new Exception('Search volume API request failed: ' . $response->get_error_message());
            }

            $body = json_decode(wp_remote_retrieve_body($response), true);
            
            if (!empty($body['tasks'][0]['result'])) {
                $result = $body['tasks'][0]['result'];
                $this->cache_handler->set_cached_data($cache_key, $result);
                return $result;
            }

            return array();
        } catch (Exception $e) {
            error_log('Search Volume Error: ' . $e->getMessage());
            return array();
        }
    }

    /**
     * Process related keywords
     */
    private function process_related_keywords($data) {
        $keywords = array();

        foreach ($data as $item) {
            if (isset($item['keyword_data'])) {
                $keywords[] = array(
                    'keyword' => $item['keyword_data']['keyword'],
                    'search_volume' => $item['keyword_data']['monthly_searches'] ?? 0,
                    'cpc' => $item['keyword_data']['cpc'] ?? 0,
                    'competition' => $item['keyword_data']['competition_level'] ?? 'unknown'
                );
            }
        }

        return $keywords;
    }

    /**
     * Analyze keyword opportunities
     */
    private function analyze_keyword_opportunities($keywords, $volume_data) {
        $opportunities = array(
            'primary_keywords' => array(),
            'secondary_keywords' => array(),
            'long_tail_keywords' => array()
        );

        foreach ($keywords as $keyword) {
            $volume_info = $this->find_volume_data($keyword, $volume_data);
            $opportunity_score = $this->calculate_opportunity_score($volume_info);

            $keyword_info = array(
                'keyword' => $keyword,
                'volume' => $volume_info['search_volume'] ?? 0,
                'opportunity_score' => $opportunity_score,
                'competition' => $volume_info['competition'] ?? 'unknown',
                'cpc' => $volume_info['cpc'] ?? 0
            );

            if ($opportunity_score >= 80) {
                $opportunities['primary_keywords'][] = $keyword_info;
            } elseif ($opportunity_score >= 50) {
                $opportunities['secondary_keywords'][] = $keyword_info;
            } else {
                $opportunities['long_tail_keywords'][] = $keyword_info;
            }
        }

        return $opportunities;
    }

    /**
     * Build content structure
     */
    private function build_content_structure($opportunities) {
        $structure = array(
            'recommended_title' => $this->generate_optimized_title($opportunities),
            'main_sections' => array(),
            'subsections' => array()
        );

        foreach ($opportunities['primary_keywords'] as $keyword) {
            $structure['main_sections'][] = array(
                'heading' => $this->generate_heading($keyword['keyword']),
                'target_keyword' => $keyword['keyword'],
                'search_volume' => $keyword['volume'],
                'suggested_content' => $this->generate_section_suggestions($keyword)
            );
        }

        foreach ($opportunities['secondary_keywords'] as $keyword) {
            $structure['subsections'][] = array(
                'heading' => $this->generate_heading($keyword['keyword']),
                'target_keyword' => $keyword['keyword'],
                'search_volume' => $keyword['volume']
            );
        }

        return $structure;
    }

    /**
     * Generate content suggestions
     */
    private function generate_content_suggestions($opportunities) {
        $suggestions = array();

        foreach ($opportunities['primary_keywords'] as $keyword) {
            $suggestions[] = array(
                'type' => 'primary_focus',
                'message' => "Focus on '{$keyword['keyword']}' as main topic ({$keyword['volume']} monthly searches)",
                'priority' => 'high'
            );
        }

        foreach ($opportunities['secondary_keywords'] as $keyword) {
            $suggestions[] = array(
                'type' => 'secondary_focus',
                'message' => "Include '{$keyword['keyword']}' as supporting topic ({$keyword['volume']} monthly searches)",
                'priority' => 'medium'
            );
        }

        return $suggestions;
    }

    /**
     * Handle API failure
     */
    private function handle_api_failure($error_message) {
        update_option('acg_last_api_error', array(
            'time' => current_time('timestamp'),
            'errors' => array(
                'api_error' => $error_message
            )
        ));

        $this->maybe_send_admin_notification($error_message);
    }

    /**
     * Send admin notification
     */
    private function maybe_send_admin_notification($error_message) {
        $last_notification = get_option('acg_last_error_notification', 0);
        $notification_threshold = 3600; // 1 hour
        
        if (time() - $last_notification > $notification_threshold) {
            $admin_email = get_option('admin_email');
            $site_url = get_site_url();
            
            $message = "SEO API issues detected on {$site_url}\n\n";
            $message .= "Error: {$error_message}\n\n";
            $message .= "Please check your API configuration and service status.";
            
            wp_mail(
                $admin_email,
                'SEO API Issues Detected - Action Required',
                $message,
                array('Content-Type: text/plain; charset=UTF-8')
            );
            
            update_option('acg_last_error_notification', time());
        }
    }

    /**
     * Log API errors
     */
    private function log_api_error($type, $message) {
        $this->last_error[$type] = $message;
        error_log("SEO API Error ({$type}): {$message}");
    }

    /**
     * Get fallback response
     */
    private function get_fallback_response($topic, $template_type) {
        return array(
            'status' => 'limited',
            'message' => 'Using basic keyword suggestions due to service unavailability',
            'keywords' => array(
                'primary' => array($topic),
                'secondary' => $this->get_template_specific_keywords($topic, $template_type)
            )
        );
    }

    /**
     * Get template specific keywords
     */
    private function get_template_specific_keywords($topic, $template_type) {
        $modifiers = array(
            'product_review' => array(
                'review', 'vs', 'worth it', 'pros and cons', 'alternatives'
            ),
            'product_roundup' => array(
                'best', 'top', 'cheapest', 'comparison', 'for'
            ),
            'product_comparison' => array(
                'vs', 'versus', 'comparison', 'difference between', 'which is better'
            ),
            'howto_guide' => array(
                'how to', 'guide', 'tutorial', 'steps', 'tips'
            ),
            'buyers_guide' => array(
                'how to choose', 'buying guide', 'what to look for', 'before buying', 'features'
            ),
            'explanation_article' => array(
                'what is', 'explained', 'definition', 'meaning', 'examples'
            )
        );

        $keywords = array($topic);
        if (isset($modifiers[$template_type])) {
            foreach ($modifiers[$template_type] as $modifier) {
                $keywords[] = trim($topic . ' ' . $modifier);
                $keywords[] = trim($modifier . ' ' . $topic);
            }
        }

        return array_unique($keywords);
    }

    /**
     * Helper methods
     */
    private function calculate_recommended_length($volume) {
        if ($volume > 10000) return '2000-2500';
        if ($volume > 5000) return '1500-2000';
        return '1000-1500';
    }

    private function generate_optimized_title($opportunities) {
        if (empty($opportunities['primary_keywords'])) {
            return '';
        }

        $primary_keyword = $opportunities['primary_keywords'][0]['keyword'];
        return ucwords($primary_keyword) . ': Complete Guide [' . date('Y') . ']';
    }

    private function generate_heading($keyword) {
        return ucwords($keyword);
    }

    private function generate_section_suggestions($keyword) {
        return array(
            'recommended_length' => $this->calculate_recommended_length($keyword['volume']),
            'key_points' => array(
                "Main features and benefits",
                "Comparison with alternatives",
                "Expert opinions and analysis",
                "Real user experiences",
                "Pricing and value assessment"
            )
        );
    }

    /**
     * Notify admin of API issues
     */
    private function notify_admin_of_api_issues($error_messages) {
        update_option('acg_last_api_error', array(
            'time' => current_time('timestamp'),
            'errors' => $error_messages
        ));

        $admin_email = get_option('admin_email');
        $site_url = get_site_url();
        
        $message = "DataForSEO API issues detected on {$site_url}\n\n";
        $message .= "The following errors occurred:\n\n";
        
        foreach ($error_messages as $type => $error) {
            $message .= "{$type}: {$error}\n";
        }
        
        $message .= "\nPlease check your DataForSEO API configuration and endpoint availability.";
        
        wp_mail(
            $admin_email,
            'SEO API Issues Detected - Action Required',
            $message,
            array('Content-Type: text/plain; charset=UTF-8')
        );
    }

    /**
     * Find volume data for a specific keyword
     */
    private function find_volume_data($keyword, $volume_data) {
        if (empty($volume_data)) {
            return array('search_volume' => 0, 'competition' => 'unknown', 'cpc' => 0);
        }

        foreach ($volume_data as $data) {
            if ($data['keyword'] === $keyword) {
                return $data;
            }
        }

        return array('search_volume' => 0, 'competition' => 'unknown', 'cpc' => 0);
    }

    /**
     * Calculate opportunity score
     */
    private function calculate_opportunity_score($volume_data) {
        $score = 0;
        
        // Volume score (up to 50 points)
        $volume = $volume_data['search_volume'] ?? 0;
        if ($volume > 10000) $score += 50;
        elseif ($volume > 5000) $score += 40;
        elseif ($volume > 1000) $score += 30;
        elseif ($volume > 500) $score += 20;
        else $score += 10;

        // Competition score (up to 30 points)
        $competition = $volume_data['competition'] ?? 'unknown';
        if ($competition === 'low') $score += 30;
        elseif ($competition === 'medium') $score += 15;

        // CPC score (up to 20 points)
        $cpc = $volume_data['cpc'] ?? 0;
        if ($cpc > 2) $score += 20;
        elseif ($cpc > 1) $score += 10;

        return $score;
    }
}