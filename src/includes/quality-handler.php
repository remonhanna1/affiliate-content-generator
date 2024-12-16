<?php
class ACG_Quality_Handler {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function check_content_quality($content) {
        return array(
            'readability' => $this->check_readability($content),
            'seo' => $this->check_seo($content),
            'affiliate_optimization' => $this->check_affiliate_optimization($content),
            'score' => $this->calculate_overall_score($content)
        );
    }

    private function check_readability($content) {
        $stats = $this->get_text_statistics($content);
        
        return array(
            'score' => $this->calculate_readability_score($stats),
            'stats' => $stats,
            'suggestions' => $this->get_readability_suggestions($stats)
        );
    }

    private function check_seo($content) {
        return array(
            'keyword_density' => $this->calculate_keyword_density($content),
            'heading_structure' => $this->analyze_heading_structure($content),
            'meta_data' => $this->analyze_meta_data($content),
            'suggestions' => $this->get_seo_suggestions($content)
        );
    }

    private function check_affiliate_optimization($content) {
        return array(
            'link_count' => substr_count($content, '<a'),
            'disclosure_present' => $this->check_disclosure($content),
            'cta_count' => $this->count_cta($content),
            'suggestions' => $this->get_affiliate_suggestions($content)
        );
    }

    private function get_text_statistics($content) {
        $clean_text = strip_tags($content);
        
        return array(
            'word_count' => str_word_count($clean_text),
            'sentence_count' => preg_match_all('/[.!?]+/', $clean_text, $matches),
            'paragraph_count' => substr_count($clean_text, "\n\n") + 1,
            'avg_sentence_length' => $this->calculate_avg_sentence_length($clean_text),
            'avg_word_length' => $this->calculate_avg_word_length($clean_text)
        );
    }

    private function calculate_readability_score($stats) {
        // Implement Flesch Reading Ease score or similar
        $words_per_sentence = $stats['word_count'] / max(1, $stats['sentence_count']);
        $score = 206.835 - (1.015 * $words_per_sentence);
        return min(100, max(0, $score));
    }

    private function calculate_keyword_density($content) {
        // Implementation for keyword density calculation
        return array(
            'density' => 0,
            'suggestions' => array()
        );
    }

    private function analyze_heading_structure($content) {
        $headings = array();
        preg_match_all('/<h([1-6])[^>]*>(.*?)<\/h\1>/', $content, $matches);
        
        return array(
            'structure' => $matches[0],
            'is_valid' => count($matches[0]) > 0
        );
    }

    private function get_readability_suggestions($stats) {
        $suggestions = array();

        if ($stats['avg_sentence_length'] > 20) {
            $suggestions[] = 'Consider breaking up longer sentences for better readability';
        }

        if ($stats['paragraph_count'] < 5) {
            $suggestions[] = 'Add more paragraphs to improve content structure';
        }

        return $suggestions;
    }

    private function get_seo_suggestions($content) {
        $suggestions = array();

        if (!preg_match('/<h1[^>]*>/', $content)) {
            $suggestions[] = 'Add a main heading (H1) to the content';
        }

        if (str_word_count(strip_tags($content)) < 300) {
            $suggestions[] = 'Content length should be at least 300 words for better SEO';
        }

        return $suggestions;
    }

    private function get_affiliate_suggestions($content) {
        $suggestions = array();

        if (substr_count($content, '<a') < 3) {
            $suggestions[] = 'Add more affiliate links to the content';
        }

        if (!$this->check_disclosure($content)) {
            $suggestions[] = 'Add an affiliate disclosure to the content';
        }

        return $suggestions;
    }

    private function calculate_overall_score($content) {
        $scores = array(
            'readability' => $this->check_readability($content)['score'],
            'seo' => 80, // Example score
            'affiliate' => 70 // Example score
        );

        return array_sum($scores) / count($scores);
    }

    private function check_disclosure($content) {
        $settings = get_option('acg_settings', array());
        $disclosure = $settings['disclosure'] ?? '';
        
        return !empty($disclosure) && strpos($content, $disclosure) !== false;
    }

    private function count_cta($content) {
        // Count call-to-action buttons or links
        preg_match_all('/<a[^>]*class="[^"]*button[^"]*"[^>]*>/', $content, $matches);
        return count($matches[0]);
    }
}
