<?php
class ACG_Template_Handler {
    private static $instance = null;
    private $templates = array();

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->load_templates();
    }

    private function load_templates() {
        $this->templates = array(
            'product_review' => array(
                'name' => 'Product Review',
                'structure' => array(
                    'introduction' => array(
                        'title' => 'Introduction',
                        'elements' => array(
                            'product_overview',
                            'key_features',
                            'target_audience',
                            'value_proposition'
                        )
                    ),
                    'details' => array(
                        'title' => 'Product Details',
                        'elements' => array(
                            'specifications',
                            'features_breakdown',
                            'use_cases',
                            'package_contents'
                        )
                    ),
                    'analysis' => array(
                        'title' => 'Performance Analysis',
                        'elements' => array(
                            'testing_methodology',
                            'performance_results',
                            'user_experience',
                            'benchmark_comparisons'
                        )
                    ),
                    'evaluation' => array(
                        'title' => 'Pros and Cons',
                        'elements' => array(
                            'advantages',
                            'limitations',
                            'deal_breakers',
                            'best_use_cases'
                        )
                    ),
                    'pricing' => array(
                        'title' => 'Price Analysis',
                        'elements' => array(
                            'current_pricing',
                            'price_comparison',
                            'value_assessment',
                            'deals_availability'
                        )
                    ),
                    'verdict' => array(
                        'title' => 'Final Verdict',
                        'elements' => array(
                            'overall_rating',
                            'recommendations',
                            'purchase_advice',
                            'alternatives'
                        )
                    )
                )
            ),
            'comparison' => array(
                'name' => 'Product Comparison',
                'structure' => array(
                    'overview' => array(
                        'title' => 'Overview',
                        'elements' => array(
                            'products_introduction',
                            'market_positioning',
                            'target_audience',
                            'price_ranges'
                        )
                    )
                )
            )
        );

        // Allow themes/plugins to modify templates
        $this->templates = apply_filters('acg_content_templates', $this->templates);
    }

    public function get_template($type) {
        return isset($this->templates[$type]) 
               ? $this->templates[$type] 
               : $this->templates['product_review'];
    }

    /**
     * Get all templates
     */
    public function get_all_templates() {
        return $this->templates;
    }

    /**
     * Get template options
     */
    public function get_template_options($type) {
        $common_options = array(
            'comparison_table' => true,
            'pros_cons' => true,
            'pricing_table' => true,
            'faq_section' => false
        );

        $template_specific_options = array(
            'product_review' => array(
                'user_reviews' => true,
                'alternatives' => true
            ),
            'comparison' => array(
                'feature_breakdown' => true,
                'verdict_summary' => true
            ),
            'buyers_guide' => array(
                'buying_checklist' => true,
                'price_comparison' => true
            )
        );

        return array_merge(
            $common_options,
            isset($template_specific_options[$type]) ? $template_specific_options[$type] : array()
        );
    }

    /**
     * Generate template structure
     */
    public function generate_structure($type, $data = array()) {
        $template = $this->get_template($type);
        $structure = '';

        foreach ($template['structure'] as $section => $content) {
            $structure .= $this->generate_section($content, $data);
        }

        return $structure;
    }

    /**
     * Generate template section
     */
    private function generate_section($section, $data) {
        $output = "\n## {$section['title']}\n\n";

        foreach ($section['elements'] as $element) {
            if (isset($data[$element])) {
                $output .= $data[$element] . "\n\n";
            }
        }

        return $output;
    }

    /**
     * Validate content against template
     */
    public function validate_content($content, $type) {
        $template = $this->get_template($type);
        $missing_sections = array();

        foreach ($template['structure'] as $section => $section_data) {
            if (!$this->section_exists($section_data['title'], $content)) {
                $missing_sections[] = $section_data['title'];
            }
        }

        return array(
            'is_valid' => empty($missing_sections),
            'missing_sections' => $missing_sections
        );
    }

    /**
     * Check if section exists in content
     */
    private function section_exists($title, $content) {
        return strpos($content, "## {$title}") !== false;
    }
}