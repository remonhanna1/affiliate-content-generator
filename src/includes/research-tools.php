<?php
/**
 * Research Tools Handler
 * Manages keyword research and competitor analysis
 */
class ACG_Research_Tools_Handler {
    private static $instance = null;
    private $api_handler;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->api_handler = ACG_API_Handler::get_instance();
    }

    /**
     * Research a topic for content creation
     */
    public function research_topic($topic) {
        try {
            return array(
                'keywords' => $this->analyze_keywords($topic),
                'competitors' => $this->analyze_competitors($topic),
                'market_data' => $this->get_market_data($topic),
                'content_suggestions' => $this->generate_content_suggestions($topic)
            );
        } catch (Exception $e) {
            throw new Exception('Research failed: ' . $e->getMessage());
        }
    }

    /**
     * Analyze keywords for a topic
     */
    private function analyze_keywords($topic) {
        $keyword_data = $this->api_handler->get_keyword_data($topic);
        
        return array(
            'main_keywords' => $this->extract_main_keywords($keyword_data),
            'long_tail' => $this->extract_long_tail_keywords($keyword_data),
            'questions' => $this->extract_question_keywords($keyword_data),
            'buyer_intent' => $this->identify_buyer_intent_keywords($keyword_data)
        );
    }

    /**
     * Extract main keywords
     */
    private function extract_main_keywords($data) {
        return array_filter($data, function($keyword) {
            return $keyword['volume'] > 1000 && $keyword['difficulty'] < 70;
        });
    }

    /**
     * Extract long-tail keywords
     */
    private function extract_long_tail_keywords($data) {
        return array_filter($data, function($keyword) {
            return str_word_count($keyword['term']) >= 3;
        });
    }

    /**
     * Identify buyer intent keywords
     */
    private function identify_buyer_intent_keywords($data) {
        $buyer_intent_terms = array('buy', 'price', 'review', 'best', 'vs', 'compare');
        
        return array_filter($data, function($keyword) use ($buyer_intent_terms) {
            foreach ($buyer_intent_terms as $term) {
                if (stripos($keyword['term'], $term) !== false) {
                    return true;
                }
            }
            return false;
        });
    }

    /**
     * Analyze competitors
     */
    private function analyze_competitors($topic) {
        try {
            $serp_data = $this->api_handler->get_serp_data($topic);
            
            return array(
                'top_competitors' => $this->identify_competitors($serp_data),
                'content_gaps' => $this->find_content_gaps($serp_data),
                'keyword_gaps' => $this->find_keyword_gaps($serp_data)
            );
        } catch (Exception $e) {
            return array('error' => $e->getMessage());
        }
    }

    /**
     * Identify main competitors
     */
    private function identify_competitors($serp_data) {
        $competitors = array();
        
        foreach ($serp_data as $result) {
            $competitors[] = array(
                'url' => $result['url'],
                'title' => $result['title'],
                'snippet' => $result['snippet'],
                'score' => $this->calculate_competitor_score($result)
            );
        }

        return array_slice($competitors, 0, 5);
    }

    /**
     * Calculate competitor score
     */
    private function calculate_competitor_score($result) {
        $score = 0;
        
        // Position score
        $score += (10 - min(10, $result['position'])) * 10;
        
        // Content relevance score
        if (isset($result['snippet'])) {
            $score += $this->calculate_relevance_score($result['snippet']);
        }
        
        return $score;
    }

    /**
     * Find content gaps
     */
    private function find_content_gaps($serp_data) {
        $gaps = array();
        $covered_topics = array();
        
        foreach ($serp_data as $result) {
            $topics = $this->extract_topics($result['title'] . ' ' . $result['snippet']);
            $covered_topics = array_merge($covered_topics, $topics);
        }
        
        $covered_topics = array_unique($covered_topics);
        
        // Compare with potential topics
        $all_topics = $this->get_potential_topics();
        $gaps = array_diff($all_topics, $covered_topics);
        
        return array_values($gaps);
    }

    /**
     * Generate content suggestions
     */
    private function generate_content_suggestions($topic) {
        $prompt = "Generate content ideas for '{$topic}' considering:
                  1. Different content types (reviews, comparisons, guides)
                  2. User search intent
                  3. Affiliate marketing opportunities
                  4. Trending angles";
        
        try {
            $suggestions = $this->api_handler->generate_content($prompt);
            return $this->parse_content_suggestions($suggestions);
        } catch (Exception $e) {
            return array('error' => $e->getMessage());
        }
    }

    /**
     * Parse content suggestions
     */
    private function parse_content_suggestions($suggestions) {
        return array_map(function($suggestion) {
            return array(
                'title' => $suggestion['title'],
                'type' => $suggestion['type'],
                'angle' => $suggestion['angle'],
                'difficulty' => $this->estimate_difficulty($suggestion)
            );
        }, $suggestions);
    }

    /**
     * Get market data
     */
    private function get_market_data($topic) {
        return array(
            'trends' => $this->analyze_trends($topic),
            'seasonality' => $this->check_seasonality($topic),
            'opportunities' => $this->identify_opportunities($topic)
        );
    }

    /**
     * Analyze market trends
     */
    private function analyze_trends($topic) {
        // Implementation for trend analysis
        return array(
            'trending_up' => array(),
            'trending_down' => array(),
            'stable' => array()
        );
    }

    /**
     * Check topic seasonality
     */
    private function check_seasonality($topic) {
        // Implementation for seasonality check
        return array(
            'peak_months' => array(),
            'low_months' => array(),
            'is_seasonal' => false
        );
    }

    /**
     * Identify market opportunities
     */
    private function identify_opportunities($topic) {
        // Implementation for opportunity identification
        return array(
            'gaps' => array(),
            'emerging_trends' => array(),
            'underserved_segments' => array()
        );
    }
}
