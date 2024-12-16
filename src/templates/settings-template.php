<?php 
$settings = get_option('acg_settings', array());
?>
<div class="wrap">
    <h1>Content Generator Settings</h1>

    <form method="post" id="acg-settings-form" action="options.php">
        <?php
        settings_fields('acg_options_group');
        ?>

        <!-- API Configuration -->
        <div class="acg-card">
            <h2>API Configuration</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="claude_api_key">Claude API Key</label>
                    </th>
                    <td>
                        <input type="password" 
                               id="claude_api_key" 
                               name="acg_settings[claude_api_key]" 
                               value="<?php echo esc_attr($settings['claude_api_key'] ?? ''); ?>" 
                               class="regular-text">
                        <p class="description">Enter your Claude API key from Anthropic</p>
                        <button type="button" class="button button-secondary" id="test-claude-api">Test Connection</button>
                        <span class="api-status claude-api-status"></span>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="dataforseo_login">DataForSEO Login</label>
                    </th>
                    <td>
                        <input type="text" 
                               id="dataforseo_login" 
                               name="acg_settings[dataforseo_login]" 
                               value="<?php echo esc_attr($settings['dataforseo_login'] ?? ''); ?>" 
                               class="regular-text">
                        <p class="description">Your DataForSEO account login</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="dataforseo_password">DataForSEO Password</label>
                    </th>
                    <td>
                        <input type="password" 
                               id="dataforseo_password" 
                               name="acg_settings[dataforseo_password]" 
                               value="<?php echo esc_attr($settings['dataforseo_password'] ?? ''); ?>" 
                               class="regular-text">
                        <p class="description">Your DataForSEO account password</p>
                        <button type="button" class="button button-secondary" id="test-dataforseo-api">Test Connection</button>
                        <span class="api-status dataforseo-api-status"></span>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Content Settings -->
        <div class="acg-card">
            <h2>Content Settings</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="disclosure">Affiliate Disclosure</label>
                    </th>
                    <td>
                        <textarea id="disclosure" 
                                 name="acg_settings[disclosure]" 
                                 rows="3" 
                                 class="large-text"><?php echo esc_textarea($settings['disclosure'] ?? ''); ?></textarea>
                        <p class="description">This disclosure will be added to posts when enabled</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="disclosure_placement">Disclosure Placement</label>
                    </th>
                    <td>
                        <select id="disclosure_placement" name="acg_settings[disclosure_placement]">
                            <option value="top" <?php selected($settings['disclosure_placement'] ?? 'bottom', 'top'); ?>>Top of content</option>
                            <option value="bottom" <?php selected($settings['disclosure_placement'] ?? 'bottom', 'bottom'); ?>>Bottom of content</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Include Disclosure</th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   name="acg_settings[include_disclosure]" 
                                   value="1" 
                                   <?php checked($settings['include_disclosure'] ?? true, 1); ?>>
                            Add affiliate disclosure to generated content
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="word_count">Default Word Count</label>
                    </th>
                    <td>
                        <select id="word_count" name="acg_settings[word_count]">
                            <?php
                            $current_count = $settings['word_count'] ?? '1500';
                            $counts = array('1500', '2000', '2500', '3000');
                            foreach ($counts as $count) {
                                printf(
                                    '<option value="%1$s" %2$s>%1$s words</option>',
                                    esc_attr($count),
                                    selected($current_count, $count, false)
                                );
                            }
                            ?>
                        </select>
                    </td>
                </tr>
            </table>
        </div>

        <?php submit_button('Save Settings'); ?>
    </form>
</div>