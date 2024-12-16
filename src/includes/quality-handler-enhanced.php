<?php
class ACG_Quality_Handler_Enhanced extends ACG_Quality_Handler {
    /**
     * Enhanced quality checking with AI assistance
     */
    public function check_content_quality_enhanced($content) {
        $basic_check = parent::check_content_quality($content);
        
        // Add enhanced checks
        $enhanced_checks = array(
            'ai_analysis' => $this->analyze_with_ai($content),
            'sentiment' => $this->analyze_sentiment($content),
            'buyer_intent' => $this->check_buyer_intent($content),
            'uniqueness' => $this->check_uniqueness($content)
        );

        return array_merge($basic_check, $enhanced_checks);
    }

    /**
     * Analyze content using AI
     */
    private function analyze_with_ai($content) {
        try {
            $api_handler = ACG_API_Handler::get_instance();
            
            $prompt = "Analyze this content for:
                      1. Writing quality
                      2. Persuasiveness
                      3. Commercial intent
                      4. User engagement
                      
                      Content: " . substr($content, 0, 1000);
            
            $analysis = $api_handler->generate_content($prompt);
            return $this->parse_ai_analysis($analysis);
        } catch (Exception $e) {
            return array('error' => $e->getMessage());
        }
    }

    /**
     * Analyze content sentiment
     */
    private function analyze_sentiment($content) {
        $positive_words = array('best', 'great', 'excellent', 'amazing', 'perfect');
        $negative_words = array('worst', 'bad', 'poor', 'terrible', 'avoid');
        
        $positive_count = 0;
        $negative_count = 0;
        
        foreach ($positive_words as $word) {
            $positive_count += substr_count(strtolower($content), $word);
        }
        
        foreach ($negative_words as $word) {
            $negative_count += substr_count(strtolower($content), $word);
        }

        return array(
            'score' => ($positive_count - $negative_count) / max(1, $positive_count + $negative_count),
            'positive_count' => $positive_count,
            'negative_count' => $negative_count
        );
    }

    /**
     * Check buyer intent signals
     */
    private function check_buyer_intent($content) {
        $intent_markers = array(
            'purchase_intent' => array('buy', 'price', 'cost', 'purchase', 'order'),
            'comparison_intent' => array('vs', 'versus', 'compared to', 'alternative'),
            'research_intent' => array('review', 'guide', 'how to', 'tutorial')
        );

        $intent_scores = array();
        foreach ($intent_markers as $type => $markers) {
            $score = 0;
            foreach ($markers as $marker) {
                $score += substr_count(strtolower($content), $marker);
            }
            $intent_scores[$type] = $score;
        }

        return array(
            'scores' => $intent_scores,
            'primary_intent' => array_search(max($intent_scores), $intent_scores)
        );
    }

    /**
     * Check content uniqueness
     */
    private function check_uniqueness($content) {
        // In a real implementation, this would use a plagiarism checking service
        // For now, we'll do a basic check
        $sentences = preg_split('/[.!?]+/', $content);
        $unique_sentences = array_unique($sentences);
        
        return array(
            'uniqueness_score' => count($unique_sentences) / count($sentences) * 100,
            'duplicate_count' => count($sentences) - count($unique_sentences)
        );
    }

    /**
     * Generate improvement suggestions
     */
    public function get_improvement_suggestions($content) {
        $quality_check = $this->check_content_quality_enhanced($content);
        $suggestions = array();

        // Add suggestions based on checks
        if ($quality_check['readability']['score'] < 60) {
            $suggestions[] = $this->get_readability_improvements($content);
        }

        if ($quality_check['buyer_intent']['scores']['purchase_intent'] < 3) {
            $suggestions[] = $this->get_buyer_intent_improvements();
        }

        if ($quality_check['sentiment']['score'] < 0) {
            $suggestions[] = $this->get_sentiment_improvements();
        }

        return array(
            'suggestions' => $suggestions,
            'priority' => $this->prioritize_improvements($suggestions)
        );
    }

    /**
     * Get specific improvement suggestions
     */
    private function get_readability_improvements($content) {
        $improvements = array();
        $stats = $this->get_text_statistics($content);

        if ($stats['avg_sentence_length'] > 20) {
            $improvements[] = 'Break down sentences longer than 20 words';
        }

        if ($stats['avg_word_length'] > 5) {
            $improvements[] = 'Use simpler words where possible';
        }

        return array(
            'type' => 'readability',
            'suggestions' => $improvements
        );
    }

    private function get_buyer_intent_improvements() {
        return array(
            'type' => 'buyer_intent',
            'suggestions' => array(
                'Add clear call-to-action phrases',
                'Include pricing information',
                'Add comparison tables',
                'Include purchase links'
            )
        );
    }

    private function get_sentiment_improvements() {
        return array(
            'type' => 'sentiment',
            'suggestions' => array(
                'Add more positive product features',
                'Balance negative points with solutions',
                'Include success stories or testimonials',
                'Highlight value propositions'
            )
        );
    }
}
