/**
 * Admin JavaScript for Preload Assist
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        try {
            // Initialize tabs with a slight delay to ensure dependencies are loaded
            setTimeout(function() {
                console.log('Initializing Preload Assist tabs');
                
                // Initialize main tabs
                if ($('#preload-assist-tabs').length) {
                    $('#preload-assist-tabs').tabs();
                } else {
                    console.warn('preload-assist-tabs element not found');
                }
                
                // Initialize category tabs
                if ($('#categories-tabs').length) {
                    $('#categories-tabs').tabs();
                } else {
                    console.warn('categories-tabs element not found');
                }
                
                // Initialize UI elements
                initUI();
        
                // Bind event handlers
                bindEvents();
            }, 100);
        } catch (error) {
            console.error('Error initializing Preload Assist UI:', error);
        }
    });

    /**
     * Initialize UI elements
     */
    function initUI() {
        // Hide notices on load
        $('.preload-assist-notice').hide();
    }

    /**
     * Bind event handlers
     */
    function bindEvents() {
        // FacetWP Tab
        $('.import-facets').on('click', importFacets);
        $('.toggle-facet').on('click', toggleFacet);
        $('body').on('click', '.toggle-values', toggleValues);

        // Parameters Tab
        $('.add-param-value').on('click', addParamValue);
        $('.save-parameter').on('click', saveParameter);
        $('body').on('click', '.delete-parameter', deleteParameter);
        $('body').on('change', '.parameter-position', updateParameterPosition);

        // Categories Tab
        $('.sync-categories').on('click', syncCategories);
        $('.toggle-category').on('click', toggleCategory);
        $('.toggle-all-facets').on('click', toggleAllFacets);
        $('.toggle-all-params').on('click', toggleAllParams);
        $('.save-category-settings').on('click', saveCategorySettings);
        $('.toggle-facet-values, .toggle-param-values').on('click', toggleValuesDisplay);
        $('.search-facets-input').on('keyup', searchFacets);
        $('.search-params-input').on('keyup', searchParams);
        
        // Facet Value Selection
        $('body').on('click', '.toggle-all-facet-values', toggleAllFacetValues);
        $('body').on('keyup', '.search-facet-values-input', searchFacetValues);
        $('body').on('change', '.facet-value-checkbox', saveSelectedFacetValues);

        // URL Generation Tab
        $('.generate-urls').on('click', generateUrls);
        $('.select-file').on('click', selectFile);
        $('.preview-file').on('click', previewFile);
        $('.export-file').on('click', exportFile);
        $('.delete-file').on('click', deleteFile);
        $('#enable-flyingpress-integration').on('change', toggleFlyingPressIntegration);
        $('.trigger-preload').on('click', triggerPreload);
        $('.cleanup-files').on('click', cleanupFiles);
        $('.prev-page').on('click', prevPage);
        $('.next-page').on('click', nextPage);

        // System Tab
        $('.delete-all-data').on('click', deleteAllData);
    }

    /**
     * Show notice message
     * 
     * @param {string} message The message to display
     * @param {string} type The notice type (success, error)
     */
    function showNotice(message, type = 'success') {
        const $notice = $('.preload-assist-notice');
        $notice.removeClass('notice-success notice-error').addClass('notice-' + type);
        $notice.html('<p>' + message + '</p>').show();
        
        // Scroll to notice
        $('html, body').animate({
            scrollTop: $notice.offset().top - 50
        }, 300);
        
        // Auto-hide after 5 seconds
        setTimeout(function() {
            $notice.fadeOut();
        }, 5000);
    }

    /**
     * Toggle values display
     */
    function toggleValues() {
        const $button = $(this);
        const $values = $button.next('.facet-values, .param-values');
        
        if ($values.is(':visible')) {
            $values.hide();
            $button.text($button.text().replace('Hide', 'Show'));
        } else {
            $values.show();
            $button.text($button.text().replace('Show', 'Hide'));
        }
    }

    /**
     * Import FacetWP facets from JSON
     */
    function importFacets() {
        const $button = $(this);
        const jsonString = $('#facetwp-import-json').val().trim();
        
        if (jsonString === '') {
            showNotice('Please paste your FacetWP export JSON', 'error');
            return;
        }
        
        $button.prop('disabled', true).text('Importing...');
        
        $.ajax({
            url: preloadAssist.ajaxUrl,
            type: 'POST',
            data: {
                action: 'preload_assist_import_facets',
                nonce: preloadAssist.nonce,
                json_string: jsonString
            },
            success: function(response) {
                if (response.success) {
                    showNotice(response.data.message);
                    
                    // Clear the textarea
                    $('#facetwp-import-json').val('');
                    
                    // Reload the page to update the facets table
                    location.reload();
                } else {
                    showNotice(response.data.message || 'Failed to import facets', 'error');
                    $button.prop('disabled', false).text('Import Facets');
                }
            },
            error: function() {
                showNotice(preloadAssist.i18n.errorOccurred, 'error');
                $button.prop('disabled', false).text('Import Facets');
            }
        });
    }

    /**
     * Toggle facet enabled state
     */
    function toggleFacet() {
        const $checkbox = $(this);
        const facetName = $checkbox.data('facet');
        const enabled = $checkbox.prop('checked');
        
        $checkbox.prop('disabled', true);
        
        $.ajax({
            url: preloadAssist.ajaxUrl,
            type: 'POST',
            data: {
                action: 'preload_assist_toggle_facet',
                nonce: preloadAssist.nonce,
                facet_name: facetName,
                enabled: enabled ? 1 : 0
            },
            success: function(response) {
                if (response.success) {
                    showNotice('Facet updated successfully!');
                } else {
                    showNotice(response.data.message || 'Failed to update facet', 'error');
                    $checkbox.prop('checked', !enabled);
                }
                $checkbox.prop('disabled', false);
            },
            error: function() {
                showNotice(preloadAssist.i18n.errorOccurred, 'error');
                $checkbox.prop('checked', !enabled);
                $checkbox.prop('disabled', false);
            }
        });
    }

    /**
     * Add parameter value
     */
    function addParamValue() {
        const $input = $('.param-value');
        const value = $input.val().trim();
        
        if (value === '') {
            showNotice('Please enter a parameter value', 'error');
            return;
        }
        
        // Check if value already exists
        let exists = false;
        $('.param-values-list .param-value-text').each(function() {
            if ($(this).text() === value) {
                exists = true;
                return false;
            }
        });
        
        if (exists) {
            showNotice('This value already exists', 'error');
            return;
        }
        
        const $valueItem = $('<div class="param-value-item"></div>');
        $valueItem.append('<span class="param-value-text">' + value + '</span>');
        $valueItem.append('<button type="button" class="button remove-param-value">Remove</button>');
        
        $('.param-values-list').append($valueItem);
        $input.val('').focus();
        
        // Bind remove event
        $('.remove-param-value').off('click').on('click', function() {
            $(this).closest('.param-value-item').remove();
        });
    }

    /**
     * Save parameter
     */
    function saveParameter() {
        const $button = $(this);
        const $nameInput = $('#param-name');
        const $enabledInput = $('#param-enabled');
        const position = $('input[name="param-position"]:checked').val();
        
        const paramName = $nameInput.val().trim();
        const enabled = $enabledInput.prop('checked');
        
        if (paramName === '') {
            showNotice('Please enter a parameter name', 'error');
            return;
        }
        
        // Get values
        const values = [];
        $('.param-values-list .param-value-text').each(function() {
            values.push($(this).text());
        });
        
        if (values.length === 0) {
            showNotice('Please add at least one parameter value', 'error');
            return;
        }
        
        $button.prop('disabled', true).text(preloadAssist.i18n.savingSettings);
        
        $.ajax({
            url: preloadAssist.ajaxUrl,
            type: 'POST',
            data: {
                action: 'preload_assist_save_parameter',
                nonce: preloadAssist.nonce,
                param_name: paramName,
                param_values: JSON.stringify(values),
                enabled: enabled ? 1 : 0,
                position: position
            },
            success: function(response) {
                if (response.success) {
                    showNotice('Parameter saved successfully!');
                    // Reset form
                    $nameInput.val('');
                    $('.param-values-list').empty();
                    // Reload the parameters table
                    location.reload();
                } else {
                    showNotice(response.data.message || 'Failed to save parameter', 'error');
                }
                $button.prop('disabled', false).text('Add Parameter');
            },
            error: function() {
                showNotice(preloadAssist.i18n.errorOccurred, 'error');
                $button.prop('disabled', false).text('Add Parameter');
            }
        });
    }

    /**
     * Delete parameter
     */
    function deleteParameter() {
        const $button = $(this);
        const paramName = $button.data('param');
        
        if (!confirm(preloadAssist.i18n.confirm)) {
            return;
        }
        
        $button.prop('disabled', true);
        
        $.ajax({
            url: preloadAssist.ajaxUrl,
            type: 'POST',
            data: {
                action: 'preload_assist_delete_parameter',
                nonce: preloadAssist.nonce,
                param_name: paramName
            },
            success: function(response) {
                if (response.success) {
                    showNotice('Parameter deleted successfully!');
                    // Remove row from table
                    $button.closest('tr').remove();
                    
                    // If no parameters left, add empty row
                    if ($('.parameters-table tbody tr').length === 0) {
                        $('.parameters-table tbody').append('<tr><td colspan="4">No parameters found. Add a custom parameter above.</td></tr>');
                    }
                } else {
                    showNotice(response.data.message || 'Failed to delete parameter', 'error');
                    $button.prop('disabled', false);
                }
            },
            error: function() {
                showNotice(preloadAssist.i18n.errorOccurred, 'error');
                $button.prop('disabled', false);
            }
        });
    }

    /**
     * Sync categories
     */
    function syncCategories() {
        const $button = $(this);
        $button.prop('disabled', true).text(preloadAssist.i18n.syncingCategories);
        
        $.ajax({
            url: preloadAssist.ajaxUrl,
            type: 'POST',
            data: {
                action: 'preload_assist_sync_categories',
                nonce: preloadAssist.nonce
            },
            success: function(response) {
                if (response.success) {
                    showNotice('Categories synced successfully!');
                    // Reload the page to update the categories tabs
                    location.reload();
                } else {
                    showNotice(response.data.message || 'Failed to sync categories', 'error');
                    $button.prop('disabled', false).text('Sync Categories');
                }
            },
            error: function() {
                showNotice(preloadAssist.i18n.errorOccurred, 'error');
                $button.prop('disabled', false).text('Sync Categories');
            }
        });
    }

    /**
     * Toggle category enabled state
     */
    function toggleCategory() {
        const $checkbox = $(this);
        const categoryId = $checkbox.data('category');
        const enabled = $checkbox.prop('checked');
        
        $checkbox.prop('disabled', true);
        
        // Show/hide settings section
        const $settingsContainer = $('#category-tab-' + categoryId + ' .category-settings-container');
        if (enabled) {
            $settingsContainer.slideDown();
        } else {
            $settingsContainer.slideUp();
        }
        
        $.ajax({
            url: preloadAssist.ajaxUrl,
            type: 'POST',
            data: {
                action: 'preload_assist_toggle_category',
                nonce: preloadAssist.nonce,
                category_id: categoryId,
                enabled: enabled ? 1 : 0
            },
            success: function(response) {
                if (response.success) {
                    showNotice('Category updated successfully!');
                } else {
                    showNotice(response.data.message || 'Failed to update category', 'error');
                    $checkbox.prop('checked', !enabled);
                    
                    // Revert settings container visibility
                    if (!enabled) {
                        $settingsContainer.slideDown();
                    } else {
                        $settingsContainer.slideUp();
                    }
                }
                $checkbox.prop('disabled', false);
            },
            error: function() {
                showNotice(preloadAssist.i18n.errorOccurred, 'error');
                $checkbox.prop('checked', !enabled);
                $checkbox.prop('disabled', false);
                
                // Revert settings container visibility
                if (!enabled) {
                    $settingsContainer.slideDown();
                } else {
                    $settingsContainer.slideUp();
                }
            }
        });
    }
    
    /**
     * Toggle values display for facets and parameters
     */
    function toggleValuesDisplay(e) {
        e.preventDefault();
        const $link = $(this);
        
        if ($link.hasClass('toggle-facet-values')) {
            // For facet values container
            const $values = $link.next('.facet-values-container');
            
            if ($values.is(':visible')) {
                $values.slideUp();
                $link.text('Select values');
            } else {
                $values.slideDown();
                $link.text('Hide values');
            }
        } else {
            // For parameter values preview
            const $values = $link.next('.param-values-preview');
            
            if ($values.is(':visible')) {
                $values.slideUp();
                $link.text('Show values');
            } else {
                $values.slideDown();
                $link.text('Hide values');
            }
        }
    }
    
    /**
     * Toggle all facets for a category
     */
    function toggleAllFacets() {
        const $checkbox = $(this);
        const categoryId = $checkbox.data('category');
        const checked = $checkbox.prop('checked');
        
        $('#category-tab-' + categoryId + ' .category-facets input[type="checkbox"]:not(:disabled)').prop('checked', checked);
    }
    
    /**
     * Toggle all parameters for a category
     */
    function toggleAllParams() {
        const $checkbox = $(this);
        const categoryId = $checkbox.data('category');
        const checked = $checkbox.prop('checked');
        
        $('#category-tab-' + categoryId + ' .category-parameters input[type="checkbox"]:not(:disabled)').prop('checked', checked);
    }
    
    /**
     * Toggle all values for a facet
     */
    function toggleAllFacetValues() {
        const $checkbox = $(this);
        const categoryId = $checkbox.data('category');
        const facetName = $checkbox.data('facet');
        const checked = $checkbox.prop('checked');
        
        // Find all value checkboxes for this facet and toggle them
        const $valueCheckboxes = $('.facet-value-checkbox[data-category="' + categoryId + '"][data-facet="' + facetName + '"]');
        $valueCheckboxes.prop('checked', checked);
        
        // Save the changes
        saveFacetValues(categoryId, facetName);
    }
    
    /**
     * Search facet values
     */
    function searchFacetValues() {
        const $input = $(this);
        const categoryId = $input.data('category');
        const facetName = $input.data('facet');
        const query = $input.val().toLowerCase();
        
        // Find value items in this facet's values list
        $('.facet-value-item').each(function() {
            const $item = $(this);
            const $checkbox = $item.find('.facet-value-checkbox');
            
            // Check if this facet value belongs to the current facet
            if ($checkbox.data('category') == categoryId && $checkbox.data('facet') == facetName) {
                const value = $item.find('.facet-value-text').text().toLowerCase();
                
                if (value.indexOf(query) !== -1) {
                    $item.show();
                } else {
                    $item.hide();
                }
            }
        });
    }
    
    /**
     * Save selected facet values
     */
    function saveSelectedFacetValues() {
        const $checkbox = $(this);
        const categoryId = $checkbox.data('category');
        const facetName = $checkbox.data('facet');
        
        // Debounce to avoid multiple AJAX calls
        if (this.saveTimeout) {
            clearTimeout(this.saveTimeout);
        }
        
        this.saveTimeout = setTimeout(() => {
            saveFacetValues(categoryId, facetName);
        }, 500);
    }
    
    /**
     * Save facet values to the server
     */
    function saveFacetValues(categoryId, facetName) {
        // Get all selected values
        const selectedValues = [];
        $('.facet-value-checkbox[data-category="' + categoryId + '"][data-facet="' + facetName + '"]:checked').each(function() {
            selectedValues.push($(this).val());
        });
        
        // Get all values for this facet
        const allValues = [];
        $('.facet-value-checkbox[data-category="' + categoryId + '"][data-facet="' + facetName + '"]').each(function() {
            allValues.push($(this).val());
        });
        
        $.ajax({
            url: preloadAssist.ajaxUrl,
            type: 'POST',
            data: {
                action: 'preload_assist_save_facet_values',
                nonce: preloadAssist.nonce,
                category_id: categoryId,
                facet_name: facetName,
                selected_values: JSON.stringify(selectedValues),
                all_values: JSON.stringify(allValues)
            },
            success: function(response) {
                if (!response.success) {
                    showNotice(response.data.message || 'Failed to save facet values', 'error');
                }
            },
            error: function() {
                showNotice(preloadAssist.i18n.errorOccurred, 'error');
            }
        });
    }
    
    /**
     * Update parameter position
     */
    function updateParameterPosition() {
        const $select = $(this);
        const paramName = $select.data('param');
        const position = $select.val();
        
        $.ajax({
            url: preloadAssist.ajaxUrl,
            type: 'POST',
            data: {
                action: 'preload_assist_update_parameter_position',
                nonce: preloadAssist.nonce,
                param_name: paramName,
                position: position
            },
            success: function(response) {
                if (response.success) {
                    showNotice('Parameter position updated successfully!');
                } else {
                    showNotice(response.data.message || 'Failed to update parameter position', 'error');
                    // Revert the select if it failed
                    $select.val(position === 'before' ? 'after' : 'before');
                }
            },
            error: function() {
                showNotice(preloadAssist.i18n.errorOccurred, 'error');
                // Revert the select
                $select.val(position === 'before' ? 'after' : 'before');
            }
        });
    }
    
    /**
     * Search facets within a category tab
     */
    function searchFacets() {
        const $input = $(this);
        const categoryId = $input.data('category');
        const query = $input.val().toLowerCase();
        
        $('#category-tab-' + categoryId + ' .category-facet').each(function() {
            const $facet = $(this);
            const facetName = $facet.data('facet-name').toLowerCase();
            const facetLabel = $facet.data('facet-label').toLowerCase();
            
            if (facetName.indexOf(query) !== -1 || facetLabel.indexOf(query) !== -1) {
                $facet.show();
            } else {
                $facet.hide();
            }
        });
    }
    
    /**
     * Search parameters within a category tab
     */
    function searchParams() {
        const $input = $(this);
        const categoryId = $input.data('category');
        const query = $input.val().toLowerCase();
        
        $('#category-tab-' + categoryId + ' .category-parameter').each(function() {
            const $param = $(this);
            const paramName = $param.data('param-name').toLowerCase();
            
            if (paramName.indexOf(query) !== -1) {
                $param.show();
            } else {
                $param.hide();
            }
        });
    }

    /**
     * Save category settings
     */
    function saveCategorySettings() {
        const $button = $(this);
        const categoryId = $button.data('category');
        
        // Get selected facets
        const facets = [];
        $('#category-tab-' + categoryId + ' .category-facets input[type="checkbox"][name^="category_facets_"]:checked').each(function() {
            facets.push($(this).val());
        });
        
        // Get selected parameters
        const parameters = [];
        $('#category-tab-' + categoryId + ' .category-parameters input[type="checkbox"]:checked').each(function() {
            parameters.push($(this).val());
        });
        
        // Save all the current facet value selections
        $('#category-tab-' + categoryId + ' .category-facets .toggle-facet-values').each(function() {
            const $link = $(this);
            const $facetItem = $link.closest('.category-facet');
            const facetName = $facetItem.data('facet-name');
            
            // Save the facet values selection
            saveFacetValues(categoryId, facetName);
        });
        
        $button.prop('disabled', true).text(preloadAssist.i18n.savingSettings);
        
        $.ajax({
            url: preloadAssist.ajaxUrl,
            type: 'POST',
            data: {
                action: 'preload_assist_save_category_settings',
                nonce: preloadAssist.nonce,
                category_id: categoryId,
                settings: JSON.stringify({
                    facets: facets,
                    parameters: parameters
                })
            },
            success: function(response) {
                if (response.success) {
                    showNotice('Category settings saved successfully!');
                } else {
                    showNotice(response.data.message || 'Failed to save category settings', 'error');
                }
                $button.prop('disabled', false).text('Save Category Settings');
            },
            error: function() {
                showNotice(preloadAssist.i18n.errorOccurred, 'error');
                $button.prop('disabled', false).text('Save Category Settings');
            }
        });
    }

    /**
     * Generate URLs
     */
    function generateUrls() {
        const $button = $(this);
        const maxUrls = $('#max-urls').val();
        const includeEmpty = $('#include-empty').prop('checked');
        const categories = $('#categories').val() || [];
        const facets = $('#facets').val() || [];
        const parameters = $('#parameters').val() || [];
        
        $button.prop('disabled', true).text(preloadAssist.i18n.generatingUrls);
        
        $.ajax({
            url: preloadAssist.ajaxUrl,
            type: 'POST',
            data: {
                action: 'preload_assist_generate_urls',
                nonce: preloadAssist.nonce,
                max_urls: maxUrls,
                include_empty: includeEmpty ? 1 : 0,
                categories: categories,
                facets: facets,
                parameters: parameters
            },
            success: function(response) {
                if (response.success) {
                    showNotice('URLs generated successfully! Generated ' + 
                               response.data.result.url_count + ' URLs.');
                    
                    // Update files table
                    updateFilesTable(response.data.files);
                } else {
                    showNotice(response.data.message || 'Failed to generate URLs', 'error');
                }
                $button.prop('disabled', false).text('Generate URLs');
            },
            error: function() {
                showNotice(preloadAssist.i18n.errorOccurred, 'error');
                $button.prop('disabled', false).text('Generate URLs');
            }
        });
    }

    /**
     * Select file for preloading
     */
    function selectFile() {
        const $radio = $(this);
        const fileId = $radio.data('file');
        
        $radio.prop('disabled', true);
        
        $.ajax({
            url: preloadAssist.ajaxUrl,
            type: 'POST',
            data: {
                action: 'preload_assist_select_file',
                nonce: preloadAssist.nonce,
                file_id: fileId
            },
            success: function(response) {
                if (response.success) {
                    showNotice('File selected successfully!');
                    updateFilesTable(response.data.files);
                } else {
                    showNotice(response.data.message || 'Failed to select file', 'error');
                    $radio.prop('disabled', false);
                }
            },
            error: function() {
                showNotice(preloadAssist.i18n.errorOccurred, 'error');
                $radio.prop('disabled', false);
            }
        });
    }

    /**
     * Preview file
     */
    function previewFile() {
        const $button = $(this);
        const fileId = $button.data('file');
        
        $button.prop('disabled', true);
        
        $.ajax({
            url: preloadAssist.ajaxUrl,
            type: 'POST',
            data: {
                action: 'preload_assist_get_file_preview',
                nonce: preloadAssist.nonce,
                file_id: fileId,
                limit: 100,
                offset: 0
            },
            success: function(response) {
                if (response.success) {
                    showFilePreview(response.data);
                } else {
                    showNotice(response.data.message || 'Failed to load file preview', 'error');
                }
                $button.prop('disabled', false);
            },
            error: function() {
                showNotice(preloadAssist.i18n.errorOccurred, 'error');
                $button.prop('disabled', false);
            }
        });
    }

    /**
     * Show file preview modal
     */
    function showFilePreview(data) {
        const $modal = $('#file-preview-modal');
        const $fileInfo = $modal.find('.file-info');
        const $fileUrls = $modal.find('.file-urls');
        const $pageInfo = $modal.find('.page-info');
        
        // Clear previous content
        $fileInfo.empty();
        $fileUrls.empty();
        
        // Add file info
        $fileInfo.html(
            '<p><strong>File:</strong> ' + data.file.file_name + '</p>' +
            '<p><strong>Total URLs:</strong> ' + data.total + '</p>'
        );
        
        // Add URLs
        $.each(data.urls, function(index, url) {
            $fileUrls.append('<div class="url-item">' + url + '</div>');
        });
        
        // Update pagination
        const currentPage = Math.floor(data.file.offset / 100) + 1;
        const totalPages = Math.ceil(data.total / 100);
        $pageInfo.text('Page ' + currentPage + ' of ' + totalPages);
        
        // Store data for pagination
        $modal.data('file-id', data.file.id);
        $modal.data('total', data.total);
        $modal.data('current-page', currentPage);
        $modal.data('total-pages', totalPages);
        
        // Show modal
        $modal.dialog({
            modal: true,
            width: 800,
            height: 600,
            title: 'URL Preview: ' + data.file.file_name
        });
    }

    /**
     * Previous page in file preview
     */
    function prevPage() {
        const $modal = $('#file-preview-modal');
        const fileId = $modal.data('file-id');
        const currentPage = $modal.data('current-page');
        
        if (currentPage <= 1) {
            return;
        }
        
        const newPage = currentPage - 1;
        const offset = (newPage - 1) * 100;
        
        $.ajax({
            url: preloadAssist.ajaxUrl,
            type: 'POST',
            data: {
                action: 'preload_assist_get_file_preview',
                nonce: preloadAssist.nonce,
                file_id: fileId,
                limit: 100,
                offset: offset
            },
            success: function(response) {
                if (response.success) {
                    updateFilePreview(response.data, newPage);
                } else {
                    showNotice(response.data.message || 'Failed to load file preview', 'error');
                }
            },
            error: function() {
                showNotice(preloadAssist.i18n.errorOccurred, 'error');
            }
        });
    }

    /**
     * Next page in file preview
     */
    function nextPage() {
        const $modal = $('#file-preview-modal');
        const fileId = $modal.data('file-id');
        const currentPage = $modal.data('current-page');
        const totalPages = $modal.data('total-pages');
        
        if (currentPage >= totalPages) {
            return;
        }
        
        const newPage = currentPage + 1;
        const offset = (newPage - 1) * 100;
        
        $.ajax({
            url: preloadAssist.ajaxUrl,
            type: 'POST',
            data: {
                action: 'preload_assist_get_file_preview',
                nonce: preloadAssist.nonce,
                file_id: fileId,
                limit: 100,
                offset: offset
            },
            success: function(response) {
                if (response.success) {
                    updateFilePreview(response.data, newPage);
                } else {
                    showNotice(response.data.message || 'Failed to load file preview', 'error');
                }
            },
            error: function() {
                showNotice(preloadAssist.i18n.errorOccurred, 'error');
            }
        });
    }

    /**
     * Update file preview content
     */
    function updateFilePreview(data, newPage) {
        const $modal = $('#file-preview-modal');
        const $fileUrls = $modal.find('.file-urls');
        const $pageInfo = $modal.find('.page-info');
        
        // Clear URLs
        $fileUrls.empty();
        
        // Add URLs
        $.each(data.urls, function(index, url) {
            $fileUrls.append('<div class="url-item">' + url + '</div>');
        });
        
        // Update pagination
        const totalPages = Math.ceil(data.total / 100);
        $pageInfo.text('Page ' + newPage + ' of ' + totalPages);
        
        // Update stored data
        $modal.data('current-page', newPage);
    }

    /**
     * Export file
     */
    function exportFile() {
        const $button = $(this);
        const fileId = $button.data('file');
        
        $button.prop('disabled', true);
        
        $.ajax({
            url: preloadAssist.ajaxUrl,
            type: 'POST',
            data: {
                action: 'preload_assist_export_file',
                nonce: preloadAssist.nonce,
                file_id: fileId
            },
            success: function(response) {
                if (response.success) {
                    // Create a temporary link and click it to download the file
                    const link = document.createElement('a');
                    link.href = response.data.url;
                    link.download = response.data.filename;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    
                    showNotice('File export prepared. Download should start automatically.');
                } else {
                    showNotice(response.data.message || 'Failed to export file', 'error');
                }
                $button.prop('disabled', false);
            },
            error: function() {
                showNotice(preloadAssist.i18n.errorOccurred, 'error');
                $button.prop('disabled', false);
            }
        });
    }

    /**
     * Delete file
     */
    function deleteFile() {
        const $button = $(this);
        const fileId = $button.data('file');
        
        if (!confirm(preloadAssist.i18n.confirm)) {
            return;
        }
        
        $button.prop('disabled', true);
        
        $.ajax({
            url: preloadAssist.ajaxUrl,
            type: 'POST',
            data: {
                action: 'preload_assist_delete_file',
                nonce: preloadAssist.nonce,
                file_id: fileId
            },
            success: function(response) {
                if (response.success) {
                    showNotice('File deleted successfully!');
                    updateFilesTable(response.data.files);
                } else {
                    showNotice(response.data.message || 'Failed to delete file', 'error');
                    $button.prop('disabled', false);
                }
            },
            error: function() {
                showNotice(preloadAssist.i18n.errorOccurred, 'error');
                $button.prop('disabled', false);
            }
        });
    }

    /**
     * Toggle FlyingPress integration
     */
    function toggleFlyingPressIntegration() {
        const $checkbox = $(this);
        const enabled = $checkbox.prop('checked');
        
        // Show/hide integration options
        if (enabled) {
            $('.flyingpress-integration-options').slideDown();
        } else {
            $('.flyingpress-integration-options').slideUp();
        }
        
        // Save setting
        $.ajax({
            url: preloadAssist.ajaxUrl,
            type: 'POST',
            data: {
                action: 'preload_assist_toggle_flyingpress_integration',
                nonce: preloadAssist.nonce,
                enabled: enabled ? 1 : 0
            },
            success: function(response) {
                if (response.success) {
                    showNotice('FlyingPress integration ' + (enabled ? 'enabled' : 'disabled') + ' successfully!');
                } else {
                    showNotice(response.data.message || 'Failed to update setting', 'error');
                    $checkbox.prop('checked', !enabled);
                    $('.flyingpress-integration-options').toggle(!enabled);
                }
            },
            error: function() {
                showNotice(preloadAssist.i18n.errorOccurred, 'error');
                $checkbox.prop('checked', !enabled);
                $('.flyingpress-integration-options').toggle(!enabled);
            }
        });
    }

    /**
     * Trigger FlyingPress preload
     */
    function triggerPreload() {
        const $button = $(this);
        
        $button.prop('disabled', true).text(preloadAssist.i18n.triggeringPreload);
        
        $.ajax({
            url: preloadAssist.ajaxUrl,
            type: 'POST',
            data: {
                action: 'preload_assist_trigger_preload',
                nonce: preloadAssist.nonce
            },
            success: function(response) {
                if (response.success) {
                    showNotice('Preload triggered successfully!');
                } else {
                    showNotice(response.data.message || 'Failed to trigger preload', 'error');
                }
                $button.prop('disabled', false).text('Trigger FlyingPress Preload');
            },
            error: function() {
                showNotice(preloadAssist.i18n.errorOccurred, 'error');
                $button.prop('disabled', false).text('Trigger FlyingPress Preload');
            }
        });
    }

    /**
     * Clean up old files
     */
    function cleanupFiles() {
        const $button = $(this);
        const keepCount = $('#keep-count').val();
        
        if (!confirm(preloadAssist.i18n.confirm)) {
            return;
        }
        
        $button.prop('disabled', true).text(preloadAssist.i18n.cleaningFiles);
        
        $.ajax({
            url: preloadAssist.ajaxUrl,
            type: 'POST',
            data: {
                action: 'preload_assist_cleanup_files',
                nonce: preloadAssist.nonce,
                keep_count: keepCount
            },
            success: function(response) {
                if (response.success) {
                    showNotice('Cleaned up ' + response.data.deleted + ' files successfully!');
                    updateFilesTable(response.data.files);
                } else {
                    showNotice(response.data.message || 'Failed to clean up files', 'error');
                }
                $button.prop('disabled', false).text('Clean Up Files');
            },
            error: function() {
                showNotice(preloadAssist.i18n.errorOccurred, 'error');
                $button.prop('disabled', false).text('Clean Up Files');
            }
        });
    }

    /**
     * Delete all plugin data
     */
    function deleteAllData() {
        if (!confirm(preloadAssist.i18n.confirmDeleteAll)) {
            return;
        }
        
        // Double confirm
        if (!confirm('Are you REALLY sure? This will delete ALL plugin data and cannot be undone!')) {
            return;
        }
        
        const $button = $(this);
        $button.prop('disabled', true);
        
        $.ajax({
            url: preloadAssist.ajaxUrl,
            type: 'POST',
            data: {
                action: 'preload_assist_delete_all_data',
                nonce: preloadAssist.nonce
            },
            success: function(response) {
                if (response.success) {
                    showNotice('All plugin data deleted successfully! Reloading page...');
                    
                    // Reload page after a short delay
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    showNotice(response.data.message || 'Failed to delete plugin data', 'error');
                    $button.prop('disabled', false);
                }
            },
            error: function() {
                showNotice(preloadAssist.i18n.errorOccurred, 'error');
                $button.prop('disabled', false);
            }
        });
    }

    /**
     * Update files table
     */
    function updateFilesTable(files) {
        const $tbody = $('.files-table tbody');
        
        // Clear table
        $tbody.empty();
        
        if (files.length === 0) {
            $tbody.append('<tr><td colspan="6">No URL files found. Generate URLs using the form above.</td></tr>');
            return;
        }
        
        // Add files
        $.each(files, function(index, file) {
            const $row = $('<tr data-file="' + file.id + '"></tr>');
            
            $row.append('<td><input type="radio" name="selected_file" class="select-file" data-file="' + 
                       file.id + '" ' + (file.is_selected == 1 ? 'checked' : '') + '></td>');
            $row.append('<td>' + file.file_name + '</td>');
            $row.append('<td>' + formatFileSize(file.file_size) + '</td>');
            $row.append('<td>' + file.url_count + '</td>');
            $row.append('<td>' + formatDate(file.generated_at) + '</td>');
            
            const $actions = $('<td></td>');
            $actions.append('<button type="button" class="button preview-file" data-file="' + 
                           file.id + '">Preview</button> ');
            $actions.append('<button type="button" class="button export-file" data-file="' + 
                           file.id + '">Export</button> ');
            $actions.append('<button type="button" class="button delete-file" data-file="' + 
                           file.id + '">Delete</button>');
            
            $row.append($actions);
            $tbody.append($row);
        });
        
        // Rebind events
        $('.select-file').off('click').on('click', selectFile);
        $('.preview-file').off('click').on('click', previewFile);
        $('.export-file').off('click').on('click', exportFile);
        $('.delete-file').off('click').on('click', deleteFile);
    }

    /**
     * Format file size
     */
    function formatFileSize(size) {
        const units = ['B', 'KB', 'MB', 'GB', 'TB'];
        let i = 0;
        
        while (size >= 1024 && i < units.length - 1) {
            size /= 1024;
            i++;
        }
        
        return Math.round(size * 100) / 100 + ' ' + units[i];
    }

    /**
     * Format date
     */
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
    }

})(jQuery);