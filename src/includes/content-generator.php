<?php
// Increase PHP execution time limit
set_time_limit(120); // 2 minutes

class ACG_Content_Generator {
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

    public function generate_content($data) {
        try {
            // Get settings
            $settings = get_option('acg_settings', array());
            $api_key = $settings['claude_api_key'];

            if (empty($api_key)) {
                throw new Exception('Claude API key is not configured');
            }

            // Detect template type if not set
            if (empty($data['content_type'])) {
                $data['content_type'] = $this->detect_template_type($data['topic']);
            }

            // Build prompt based on content type and features
            $prompt = $this->build_prompt($data);

            // Make API request to generate content
            $response = wp_remote_post('https://api.anthropic.com/v1/messages', array(
                'timeout' => 60,
                'headers' => array(
                    'Content-Type' => 'application/json',
                    'x-api-key' => $api_key,
                    'anthropic-version' => '2023-06-01'
                ),
                'body' => json_encode(array(
                    'model' => 'claude-3-opus-20240229',
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

            // Process and format content
            $content = $body['content'][0]['text'];
            $processed_content = $this->process_content($content, $data, $settings);

            return array(
                'content' => $processed_content,
                'word_count' => str_word_count(strip_tags($processed_content)),
                'status' => 'success'
            );

        } catch (Exception $e) {
            throw new Exception('Content generation failed: ' . $e->getMessage());
        }
    }

    public function detect_template_type($topic) {
        $topic = strtolower($topic);
        
        $patterns = [
            // Product-focused patterns
            'product_review' => [
                '/review/i',
                '/worth it/i',
                '/experience with/i',
                '/hands[\s-]?on/i'
            ],
            'product_roundup' => [
                '/^best\s/i',
                '/top\s*\d+/i',
                '/best\s+\w+\s+for/i',
                '/recommended\s/i'
            ],
            'product_comparison' => [
                '/\svs\.?\s/i',
                '/versus/i',
                '/compare/i',
                '/difference between/i'
            ],
            
            // Information-focused patterns
            'howto_guide' => [
                '/^how\s+to\s/i',
                '/step[\s-]?by[\s-]?step/i',
                '/guide\s+to\s/i',
                '/tutorial/i'
            ],
            'buyers_guide' => [
                '/buying\s+guide/i',
                '/how\s+to\s+choose/i',
                '/shopping\s+guide/i',
                '/what\s+to\s+look\s+for/i'
            ],
            'explanation_article' => [
                '/what\s+is/i',
                '/why\s+do/i',
                '/explained/i',
                '/understanding/i',
                '/introduction\s+to/i'
            ]
        ];

        foreach ($patterns as $type => $typePatterns) {
            foreach ($typePatterns as $pattern) {
                if (preg_match($pattern, $topic)) {
                    return $type;
                }
            }
        }

        return 'product_review'; // Default type
    }

    private function build_prompt($data) {
        $template = $this->get_content_template($data['content_type']);
        
        $prompt = "Write a detailed {$data['content_type']} about {$data['topic']}.\n\n";
        $prompt .= "Use this structure:\n";
        $prompt .= $template;
        $prompt .= "\nAdditional requirements:\n";
        $prompt .= "- Use clear and engaging language\n";
        $prompt .= "- Include specific examples and evidence\n";
        $prompt .= "- Add natural affiliate opportunities where relevant\n";
        $prompt .= "- Use proper headings with ## for main sections\n";

        if (!empty($data['features']) && in_array('comparison_table', $data['features'])) {
            $prompt .= "\nFor comparison tables, use this exact format:\n";
            $prompt .= "Feature | Option A | Option B\n";
            $prompt .= "---|---|---\n";
            $prompt .= "Feature 1 | Detail A1 | Detail B1\n";
        }
        
        return $prompt;
    }

    private function get_content_template($type) {
        $templates = [
            'product_review' => "
## Introduction
- Product overview
- Key specifications
- Target audience

## Main Features
- Core capabilities
- Standout features
- Technical specifications

## Performance
- Real-world testing
- User experience
- Key findings

## Pros and Cons
- Key advantages
- Notable limitations

## Verdict
- Final rating
- Recommendations
- Who should buy",

            'product_roundup' => "
## Introduction
- Overview of category
- Why these products
- Selection criteria

## Best Overall Pick
- Product name and specs
- Why it's the best
- Key features
- Who it's for

## Best Value Pick
- Product name and specs
- Value proposition
- Key features
- Who it's for

## Premium Choice
- Product name and specs
- Premium features
- Why it's worth more
- Who it's for

## Comparison Overview
[Comparison table if enabled]

## Buying Advice
- What to consider
- Common features
- Price expectations",

            'product_comparison' => "
## Overview
- Products introduction
- Main differences
- Target users

## Direct Comparison
[Comparison table if enabled]
- Design and build
- Features
- Performance
- Price

## Real-World Usage
- Practical differences
- User experiences
- Common scenarios

## Which One to Choose
- Use case scenarios
- Best value option
- Final verdict",

            'howto_guide' => "
## Introduction
- Purpose
- Difficulty level
- Time needed
- Required items

## Preparation Steps
- What you need
- Setup required
- Safety considerations

## Step-by-Step Instructions
### Step 1: Getting Started
- Detailed instructions
- Tips and tricks
- Common mistakes

### Step 2: Main Process
[Continue with relevant steps]

## Tips for Success
- Expert advice
- Best practices
- Troubleshooting

## Conclusion
- Final tips
- Expected results
- Next steps",

            'buyers_guide' => "
## Introduction
- Market overview
- Price ranges
- Key considerations

## What to Look For
- Essential features
- Optional features
- Red flags

## Top Recommendations
- Best overall
- Best value
- Premium pick
- Budget option

## How to Choose
- Decision factors
- Common pitfalls
- Shopping tips

## Conclusion
- Final advice
- Where to buy
- Additional resources",

            'explanation_article' => "
## Introduction
- Topic overview
- Why it matters
- Basic concept

## Core Concepts
- Main ideas
- Key terminology
- Fundamental principles

## Detailed Explanation
- In-depth analysis
- Examples
- Applications

## Common Questions
- FAQ section
- Misconceptions
- Expert insights

## Practical Applications
- Real-world uses
- Examples
- Best practices

## Summary
- Key takeaways
- Further reading
- Next steps"
        ];

        return isset($templates[$type]) ? $templates[$type] : $templates['product_review'];
    }

    private function process_content($content, $data, $settings) {
        // Convert markdown headings to HTML
        $lines = explode("\n", $content);
        $processed_lines = array();
        $in_list = false;

        foreach ($lines as $line) {
            $line = trim($line);
            
            // Convert h2 (##)
            if (preg_match('/^##\s+(.*)$/', $line, $matches)) {
                if ($in_list) {
                    $processed_lines[] = '</ul>';
                    $in_list = false;
                }
                $processed_lines[] = '<h2>' . esc_html($matches[1]) . '</h2>';
            }
            // Convert h3 (###)
            elseif (preg_match('/^###\s+(.*)$/', $line, $matches)) {
                if ($in_list) {
                    $processed_lines[] = '</ul>';
                    $in_list = false;
                }
                $processed_lines[] = '<h3>' . esc_html($matches[1]) . '</h3>';
            }
            // Convert bullet points
            elseif (preg_match('/^\s*-\s+(.*)$/', $line, $matches)) {
                if (!$in_list) {
                    $processed_lines[] = '<ul>';
                    $in_list = true;
                }
                $processed_lines[] = '<li>' . esc_html(trim($matches[1])) . '</li>';
            }
            // Regular paragraph
            elseif (!empty($line)) {
                if ($in_list) {
                    $processed_lines[] = '</ul>';
                    $in_list = false;
                }
                $processed_lines[] = '<p>' . esc_html($line) . '</p>';
            }
        }

        if ($in_list) {
            $processed_lines[] = '</ul>';
        }

        $content = implode("\n", $processed_lines);

        // Add comparison table formatting if requested
        if (!empty($data['features']) && in_array('comparison_table', $data['features'])) {
            $content = $this->format_comparison_tables($content);
        }

        // Add affiliate disclosure if enabled
        if (!empty($settings['include_disclosure']) && !empty($settings['disclosure'])) {
            $disclosure = '<div class="affiliate-disclosure">' . esc_html($settings['disclosure']) . '</div>';
            if (($settings['disclosure_placement'] ?? 'bottom') === 'top') {
                $content = $disclosure . "\n\n" . $content;
            } else {
                $content = $content . "\n\n" . $disclosure;
            }
        }

        return '<div class="acg-content">' . $content . '</div>';
    }

    private function format_comparison_tables($content) {
        if (preg_match_all('/(\|[^\n]+\|\n)(\|[\-\s]+\|\n)(\|[^\n]+\|(\n|$))+/', $content, $matches)) {
            foreach ($matches[0] as $table_text) {
                $rows = array_filter(array_map('trim', explode("\n", $table_text)));
                
                $html_table = '<div class="table-wrapper"><table class="comparison-table">';
                $is_header = true;

                foreach ($rows as $row) {
                    if (strpos($row, '---') !== false) continue;
                    
                    $cells = array_map('trim', explode('|', trim($row, '|')));
                    
                    if ($is_header) {
                        $html_table .= '<thead><tr>';
                        foreach ($cells as $cell) {
                            $html_table .= '<th>' . esc_html($cell) . '</th>';
                        }
                        $html_table .= '</tr></thead><tbody>';
                        $is_header = false;
                    } else {
                        $html_table .= '<tr>';
                        foreach ($cells as $cell) {
                            $html_table .= '<td>' . esc_html($cell) . '</td>';
                        }
                        $html_table .= '</tr>';
                    }
                }
                
                $html_table .= '</tbody></table></div>';
                $content = str_replace($table_text, $html_table, $content);
            }
        }

        return $content;
    }
}