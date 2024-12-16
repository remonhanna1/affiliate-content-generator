jQuery(document).ready(function($) {
    // Test Claude API
    $('#test-claude-api').on('click', function(e) {
        e.preventDefault();
        var button = $(this);
        var status = $('.claude-api-status');
        var apiKey = $('#claude_api_key').val();

        if (!apiKey) {
            showApiStatus('Please enter an API key', 'error', status);
            return;
        }

        // Show loading state
        button.prop('disabled', true);
        showApiStatus('Testing connection...', 'loading', status);

        console.log('Testing Claude API with key:', apiKey);

        $.ajax({
            url: acgData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'test_api_connection',
                nonce: acgData.nonce,
                service: 'claude',
                credentials: apiKey
            },
            success: function(response) {
                console.log('Claude API test response:', response);
                if (response.success) {
                    showApiStatus('Connection successful!', 'success', status);
                } else {
                    showApiStatus(response.data || 'Connection failed', 'error', status);
                }
            },
            error: function(xhr, status, error) {
                console.error('Claude API Test Error:', error);
                showApiStatus('Connection failed: ' + error, 'error', status);
            },
            complete: function() {
                button.prop('disabled', false);
            }
        });
    });

    // Test DataForSEO API
    $('#test-dataforseo-api').on('click', function(e) {
        e.preventDefault();
        var button = $(this);
        var status = $('.dataforseo-api-status');
        var login = $('#dataforseo_login').val();
        var password = $('#dataforseo_password').val();

        if (!login || !password) {
            showApiStatus('Please enter both login and password', 'error', status);
            return;
        }

        // Show loading state
        button.prop('disabled', true);
        showApiStatus('Testing connection...', 'loading', status);

        console.log('Testing DataForSEO API with credentials:', { login: login });

        $.ajax({
            url: acgData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'test_api_connection',
                nonce: acgData.nonce,
                service: 'dataforseo',
                credentials: {
                    login: login,
                    password: password
                }
            },
            success: function(response) {
                console.log('DataForSEO API test response:', response);
                if (response.success) {
                    showApiStatus('Connection successful!', 'success', status);
                } else {
                    showApiStatus(response.data || 'Connection failed', 'error', status);
                }
            },
            error: function(xhr, status, error) {
                console.error('DataForSEO API Test Error:', error);
                showApiStatus('Connection failed: ' + error, 'error', status);
            },
            complete: function() {
                button.prop('disabled', false);
            }
        });
    });

    // Save settings notification
    $('.acg-settings-form').on('submit', function() {
        var notification = $('<div class="notice notice-success is-dismissible"><p>Settings saved successfully!</p></div>');
        $('.wrap > h1').after(notification);
        
        setTimeout(function() {
            notification.fadeOut(function() {
                $(this).remove();
            });
        }, 3000);
    });

    // Helper function to show API status
    function showApiStatus(message, type, statusElement) {
        var statusHtml = '';
        
        if (type === 'loading') {
            statusHtml = '<span class="spinner is-active"></span> ' + message;
        } else if (type === 'success') {
            statusHtml = '<span class="status-success">' + message + '</span>';
        } else if (type === 'error') {
            statusHtml = '<span class="status-error">' + message + '</span>';
        }
        
        statusElement.html(statusHtml);

        // Add debug console log
        console.log('API Status Update:', {
            message: message,
            type: type,
            element: statusElement
        });
    }

    // Debug initialization
    console.log('Settings.js initialized');
    console.log('acgData available:', acgData);
});