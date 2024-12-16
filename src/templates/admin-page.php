<?php
/**
* Admin page template for Affiliate Content Generator
*/

// Security check
if (!defined('ABSPATH')) exit;

// Get settings
$settings = get_option('acg_settings', array());
?>
<div class="wrap">
    <h1>Content Generator</h1>
    
    <div class="acg-container">
        <!-- Input Form Card -->
        <div class="acg-card">
            <form id="content-generation-form" class="acg-form">
                <!-- Topic Input -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="topic">Main Topic/Title</label>
                        <input type="text" 
                               id="topic" 
                               name="topic" 
                               class="regular-text" 
                               placeholder="e.g., Best Gaming Laptops 2024" 
                               required>
                    </div>
                </div>

                <!-- Content Configuration -->
                <div class="form-row two-columns">
                    <div class="form-group">
                        <label for="content-type">Content Type</label>
                        <select id="content-type" name="content_type" required>
                            <optgroup label="Product-focused">
                                <option value="product_review">Product Review</option>
                                <option value="product_roundup">Product Roundup</option>
                                <option value="product_comparison">Product Comparison</option>
                            </optgroup>
                            <optgroup label="Information-focused">
                                <option value="howto_guide">How-to Guide</option>
                                <option value="buyers_guide">Buyer's Guide</option>
                                <option value="explanation_article">Explanation Article</option>
                            </optgroup>
                        </select>
                        <p class="description">Template will be suggested based on your topic</p>
                    </div>
                    
                    <div class="form-group">
                        <label for="word-count">Word Count</label>
                        <select id="word-count" name="word_count">
                            <option value="<?php echo esc_attr($settings['word_count'] ?? '1500'); ?>">
                                <?php echo number_format($settings['word_count'] ?? 1500); ?> words
                            </option>
                            <option value="2000">2,000 words</option>
                            <option value="2500">2,500 words</option>
                            <option value="3000">3,000 words</option>
                        </select>
                    </div>
                </div>

                <!-- Content Features -->
                <div class="form-row">
                    <div class="form-group">
                        <label>Content Features</label>
                        <div class="checkbox-group">
                            <label>
                                <input type="checkbox" name="features[]" value="comparison_table">
                                Include Comparison Table
                            </label>
                            <?php
                            // Allow plugins/themes to add custom features
                            do_action('acg_content_features');
                            ?>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="form-row">
                    <div class="form-group">
                        <button type="submit" class="button button-primary" id="generate-content">
                            Analyze & Generate Content
                        </button>
                        <?php if (current_user_can('manage_options')): ?>
                            <span class="admin-actions">
                                <?php do_action('acg_admin_actions'); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>

        <!-- SEO Analysis Section -->
        <div class="seo-analysis hidden">
            <div class="acg-card">
                <h2>SEO Analysis Results</h2>
                
                <!-- Progress Indicator -->
                <div class="analysis-progress">
                    <div class="progress-steps">
                        <div class="step" data-step="research">
                            <span class="step-label">Research</span>
                        </div>
                        <div class="step" data-step="analyze">
                            <span class="step-label">Analysis</span>
                        </div>
                        <div class="step" data-step="ready">
                            <span class="step-label">Ready</span>
                        </div>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill"></div>
                    </div>
                    <div class="progress-status">Analyzing topic...</div>
                </div>

                <!-- Keyword Analysis Results -->
                <div class="keyword-analysis">
                    <!-- Primary Keywords Section -->
                    <div class="keywords-section primary-keywords hidden">
                        <h3>High-Value Keywords</h3>
                        <div class="keywords-table-container"></div>
                    </div>

                    <!-- Secondary Keywords Section -->
                    <div class="keywords-section secondary-keywords hidden">
                        <h3>Supporting Keywords</h3>
                        <div class="keywords-table-container"></div>
                    </div>

                    <!-- Content Structure -->
                    <div class="content-structure hidden">
                        <h3>Recommended Content Structure</h3>
                        <div class="structure-preview"></div>
                    </div>

                    <!-- Optimization Tips -->
                    <div class="optimization-tips hidden">
                        <h3>Content Optimization Tips</h3>
                        <div class="tips-container"></div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="opportunity-actions hidden">
                    <button type="button" class="button button-primary" id="use-suggestions">
                        Generate Content with Selected Keywords
                    </button>
                    <button type="button" class="button button-secondary" id="skip-suggestions">
                        Skip Suggestions
                    </button>
                    <?php do_action('acg_after_seo_actions'); ?>
                </div>
            </div>
        </div>

        <!-- Generation Progress -->
        <div class="generation-progress hidden">
            <div class="acg-card">
                <h3>Generating Content</h3>
                <div class="progress-steps">
                    <div class="step" data-step="research">Research</div>
                    <div class="step" data-step="generate">Generate</div>
                    <div class="step" data-step="optimize">Optimize</div>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill"></div>
                </div>
                <div class="progress-status">Analyzing topic...</div>
            </div>
        </div>

        <!-- Loading States -->
        <div class="loading-overlay hidden">
            <div class="spinner"></div>
            <div class="loading-message">Processing...</div>
        </div>

        <?php
        // Allow plugins/themes to add custom content
        do_action('acg_after_main_content');
        ?>
    </div>
</div>
<?php
// Allow plugins/themes to add custom scripts
do_action('acg_admin_footer');
?>