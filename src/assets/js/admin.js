$(document).ready(function($) {
    // Add the test button to the first form group in the first card
    const $firstFormGroup = $('#content-generation-form .form-group').first();
    $('<button/>', {
        text: 'Test SEO Features',
        class: 'button button-secondary',
        id: 'test-seo-features',
        type: 'button',  // Add this to prevent form submission
        css: { marginLeft: '10px' }
    }).appendTo($firstFormGroup);

    // Add test button handler
    $('#test-seo-features').on('click', function(e) {
        e.preventDefault();
        $(this).prop('disabled', true);
        
        showNotification('Testing SEO features...', 'info');
        
        $.ajax({
            url: acgData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'test_seo_features',
                nonce: acgData.nonce
            },
            success: function(response) {
                console.log('SEO Features Test Response:', response);
                if (response.success) {
                    let message = 'Test Results:\n';
                    message += 'Cache: ' + (response.data.cache_test.success ? 'Working' : 'Failed') + '\n';
                    message += 'SEO: ' + (response.data.seo_test.success ? 'Working' : 'Failed');
                    showNotification(message, 'success');
                } else {
                    showNotification('Test failed: ' + response.data, 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('Test error:', error);
                showNotification('Test error: ' + error, 'error');
            },
            complete: function() {
                $('#test-seo-features').prop('disabled', false);
            }
        });
    });

    // Auto-detect content type based on topic
    $('#topic').on('input', debounce(function() {
        var topic = $(this).val();
        if (topic) {
            detectContentType(topic);
        }
    }, 500));

    // Update available features based on content type
    $('#content-type').on('change', function() {
        updateFeatures($(this).val());
    });

    function updateFeatures(contentType) {
        var $comparisonTable = $('input[value="comparison_table"]').closest('label');
        
        // Show/hide comparison table option based on content type
        if (['product_comparison', 'product_roundup', 'buyers_guide'].includes(contentType)) {
            $comparisonTable.show();
        } else {
            $comparisonTable.hide();
            $comparisonTable.find('input').prop('checked', false);
        }
    }

    // Form submission handler
    $('#content-generation-form').on('submit', function(e) {
        e.preventDefault();
        console.log('Form submitted');
        
        if (!validateForm()) {
            showNotification('Please fill in all required fields', 'error');
            return;
        }
        
        const topic = $('#topic').val();
        const templateType = $('#content-type').val();
        console.log('Starting analysis for:', topic, templateType);
        
        // Start SEO analysis workflow
        startSEOAnalysis(topic, templateType);
    });

    /**
     * Start SEO analysis workflow
     */
    function startSEOAnalysis(topic, templateType) {
        $('.seo-analysis').removeClass('hidden');
        $('.keyword-data .loading').show();
        $('.keyword-results').empty();
        
        // Reset previous results
        $('.keywords-section, .content-structure, .optimization-tips').addClass('hidden');
        
        // Get template keywords
        $.ajax({
            url: acgData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'get_template_keywords',
                nonce: acgData.nonce,
                topic: topic,
                template_type: templateType
            },
            success: function(response) {
                console.log('Template Keywords Response:', response);
                if (response.success) {
                    handleSEOAnalysisSuccess(response.data);
                    // Start content opportunities analysis
                    analyzeContentOpportunities(topic, templateType);
                } else {
                    handleSEOAnalysisError(response.data);
                }
            },
            error: function(xhr, status, error) {
                console.error('Template Keywords Error:', error);
                handleSEOAnalysisError(error);
            }
        });
    }

    /**
     * Analyze content opportunities
     */
    function analyzeContentOpportunities(topic, templateType) {
        console.log('Analyzing content opportunities');
        
        $.ajax({
            url: acgData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'analyze_seo_opportunities',
                nonce: acgData.nonce,
                topic: topic,
                template_type: templateType
            },
            success: function(response) {
                console.log('SEO Analysis response:', response);
                if (response.success && response.data) {
                    displaySEOOpportunities(response.data);
                    // Proceed with content generation
                    generateContent(new FormData($('#content-generation-form')[0]));
                } else {
                    showNotification('Error analyzing SEO opportunities: ' + (response.data || 'Unknown error'), 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('SEO Analysis error:', error);
                showNotification('Error performing SEO analysis: ' + error, 'error');
            },
            complete: function() {
                $('.keyword-data .loading').hide();
            }
        });
    }

    function displaySEOOpportunities(data) {
        console.log('Displaying SEO opportunities:', data);
        
        if (!data || !data.opportunities) {
            console.error('Invalid data structure:', data);
            showNotification('Error: Invalid data received', 'error');
            return;
        }

        try {
            // Display primary keywords
            if (data.opportunities.primary_keywords?.length > 0) {
                const primaryHtml = buildKeywordsTable(data.opportunities.primary_keywords, 'primary');
                $('.primary-keywords .keywords-table-container').html(primaryHtml);
                $('.primary-keywords').removeClass('hidden');
            }

            // Display secondary keywords
            if (data.opportunities.secondary_keywords?.length > 0) {
                const secondaryHtml = buildKeywordsTable(data.opportunities.secondary_keywords, 'secondary');
                $('.secondary-keywords .keywords-table-container').html(secondaryHtml);
                $('.secondary-keywords').removeClass('hidden');
            }

            // Display content structure
            if (data.content_structure) {
                displayContentStructure(data.content_structure);
                $('.content-structure').removeClass('hidden');
            }

            // Display optimization tips
            if (data.suggestions?.length > 0) {
                displayOptimizationTips(data.suggestions);
                $('.optimization-tips').removeClass('hidden');
            }
        } catch (error) {
            console.error('Error displaying SEO opportunities:', error);
            showNotification('Error displaying SEO analysis results', 'error');
        }
    }

    function buildKeywordsTable(keywords, type) {
        let html = `
            <table class="keywords-table">
                <thead>
                    <tr>
                        <th>Keyword</th>
                        <th>Search Volume</th>
                        <th>Opportunity Score</th>
                        <th>Competition</th>
                    </tr>
                </thead>
                <tbody>
        `;

        keywords.forEach(keyword => {
            if (!keyword) return;
            
            html += `
                <tr>
                    <td>${keyword.keyword || ''}</td>
                    <td>${(keyword.volume || 0).toLocaleString()}</td>
                    <td>
                        <div class="opportunity-score">
                            <div class="score-bar" style="width: ${keyword.opportunity_score || 0}%"></div>
                            <span class="score-text">${keyword.opportunity_score || 0}</span>
                        </div>
                    </td>
                    <td>${keyword.competition || 'Unknown'}</td>
                </tr>
            `;
        });

        html += '</tbody></table>';
        return html;
    }

    function displayContentStructure(structure) {
        if (!structure) return;

        let html = `
            <div class="content-structure-preview">
                <h4>Recommended Title</h4>
                <p class="suggested-title">${structure.recommended_title || ''}</p>
                
                <h4>Main Sections</h4>
                <ul class="suggested-sections">
        `;

        if (structure.main_sections?.length > 0) {
            structure.main_sections.forEach(section => {
                if (!section) return;
                
                html += `
                    <li>
                        <strong>${section.heading || ''}</strong>
                        <span class="section-meta">
                            (${(section.search_volume || 0).toLocaleString()} monthly searches)
                        </span>
                        <ul class="section-details">
                            <li>Recommended length: ${section.suggested_content?.recommended_length || 'N/A'}</li>
                        </ul>
                    </li>
                `;
            });
        }

        html += '</ul></div>';
        $('.content-structure .structure-preview').html(html);
    }

    function displayOptimizationTips(suggestions) {
        let html = '<ul class="optimization-suggestions">';
        
        suggestions.forEach(suggestion => {
            if (!suggestion) return;
            
            html += `
                <li class="suggestion-${suggestion.priority || 'medium'}">
                    <span class="suggestion-type">${suggestion.type || ''}</span>
                    <p>${suggestion.message || ''}</p>
                </li>
            `;
        });

        html += '</ul>';
        $('.optimization-tips .tips-container').html(html);
    }

    function generateContent(formData) {
        $('.generation-progress').removeClass('hidden');
        startProgress();

        const data = {
            action: 'generate_content',
            nonce: acgData.nonce,
            topic: formData.get('topic'),
            content_type: formData.get('content_type'),
            word_count: formData.get('word_count'),
            features: formData.getAll('features[]')
        };

        $.ajax({
            url: acgData.ajaxUrl,
            type: 'POST',
            data: data,
            beforeSend: function() {
                $('#generate-content').prop('disabled', true);
            },
            success: function(response) {
                if (response.success) {
                    completeProgress();
                    handleContentGenerated(response.data);
                } else {
                    showNotification(response.data, 'error');
                    resetProgress();
                }
            },
            error: function(xhr, status, error) {
                showNotification('Error generating content: ' + error, 'error');
                resetProgress();
            },
            complete: function() {
                $('#generate-content').prop('disabled', false);
            }
        });
    }

    // Progress tracking functions
    let progressInterval;
    
    function startProgress() {
        let progress = 0;
        const progressBar = $('.progress-fill');
        
        progressInterval = setInterval(function() {
            if (progress < 90) {
                progress += 5;
                progressBar.css('width', progress + '%');
                updateProgressStatus(progress);
            }
        }, 1000);
    }

    function updateProgressStatus(progress) {
        const status = $('.progress-status');
        if (progress < 30) {
            status.text('Analyzing topic...');
            updateSteps('research');
        } else if (progress < 60) {
            status.text('Generating content...');
            updateSteps('generate');
        } else {
            status.text('Optimizing content...');
            updateSteps('optimize');
        }
    }

    function updateSteps(currentStep) {
        $('.step').each(function() {
            const step = $(this).data('step');
            if (step === currentStep) {
                $(this).addClass('active').removeClass('completed');
            } else if (getStepOrder(step) < getStepOrder(currentStep)) {
                $(this).removeClass('active').addClass('completed');
            } else {
                $(this).removeClass('active completed');
            }
        });
    }

    function getStepOrder(step) {
        const steps = ['research', 'generate', 'optimize'];
        return steps.indexOf(step);
    }

    function completeProgress() {
        clearInterval(progressInterval);
        $('.progress-fill').css('width', '100%');
        $('.progress-status').text('Content generated successfully!');
        $('.step').addClass('completed').removeClass('active');
    }

    function resetProgress() {
        clearInterval(progressInterval);
        $('.progress-fill').css('width', '0%');
        $('.progress-status').text('');
        $('.generation-progress').addClass('hidden');
        $('.step').removeClass('active completed');
    }

    // Form validation
    function validateForm() {
        let isValid = true;
        $('#content-generation-form [required]').each(function() {
            if (!$(this).val()) {
                $(this).addClass('error');
                isValid = false;
            } else {
                $(this).removeClass('error');
            }
        });
        return isValid;
    }

    // Utility functions
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // Content generation success handler
    function handleContentGenerated(data) {
        if (data.post_id) {
            showNotification('Content generated successfully! Redirecting to editor...', 'success');
            setTimeout(function() {
                window.location.href = `post.php?post=${data.post_id}&action=edit`;
            }, 2000);
        }
    }

    // Notification handler
    function showNotification(message, type = 'info') {
        $('.notice').remove();
        const notification = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
        $('#content-generation-form').before(notification);
        
        setTimeout(function() {
            notification.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
    }

    // Initialize features on page load
    updateFeatures($('#content-type').val());
});