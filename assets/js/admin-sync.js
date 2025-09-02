(function($) {
    // Debug Mode: Set to true to enable console debugging
    window.ecwidDebugMode = false; // Debugging completed, back to production mode
    
    // --- Global Utility Functions ---
    function sanitizeHTML(str) {
        const temp = document.createElement('div');
        temp.textContent = str;
        return temp.innerHTML;
    }
    
    $(document).ready(function() {
        if (typeof ecwid_sync_params === 'undefined') {
            console.error('Ecwid Sync Error: Localization parameters (ecwid_sync_params) not found. Ensure the plugin is activated and scripts are enqueued correctly.');
            // Optionally, display an error message to the user on the page
            $('#full-sync-status').html('<p style="color:red;"><strong>Critical Error:</strong> Plugin localization parameters not found. Sync functionality will not work. Please contact support.</p>');
            return; // Stop further execution if params are missing
        }

        const ajax_url = ecwid_sync_params.ajax_url;
        const nonce = ecwid_sync_params.nonce;
        const i18n = ecwid_sync_params.i18n || {};
        // Ensure all i18n strings have fallbacks to prevent errors if a string is missing
        // (This is a more robust way than checking each one individually later)
        const i18n_defaults = {
            sync_starting: 'Sync starting...',
            sync_complete: 'Sync Complete!',
            sync_error: 'Error during sync. Check console or log for details.',
            ajax_error: 'AJAX Error. Check console or log for details.',
            syncing: 'Syncing',
            start_sync: 'Start Full Sync',
            syncing_button: 'Syncing...',
            fetching_counts: 'Fetching item counts...',
            categories_to_sync_info: 'Categories to sync: {count}',
            products_to_sync_info: 'Products to sync: {count}',
            // variations_to_sync_info: 'Variations to sync: {count}', // This one is for actual count, keep if used
            syncing_item_of_total: 'Syncing {syncType}: {current} of {total}...',
            load_products: 'Reload Products',
            loading_products: 'Loading Products...',
            load_ecwid_categories: 'Reload Ecwid Categories', // CHANGED from 'Load Ecwid Category List'
            loading_ecwid_categories: 'Loading Categories...',
            no_categories_found_display: 'No categories found in your Ecwid store or an error occurred.',
            categories_loaded_for_display: '{count} categories loaded for display.',
            import_selected: 'Import Selected Products',
            importing_selected: 'Importing Selected...',
            import_selected_categories: 'Import Selected Categories',
            no_categories_selected: 'No categories selected for import.',
            no_categories_found: 'No categories found in your Ecwid store or an error occurred.',
            no_products_selected: 'No products selected for import.',
            select_all_none: 'Select All/None',
            no_products_found: 'No enabled products found in Ecwid store or failed to fetch.',
            start_category_sync_page: 'Start Category Sync',
            syncing_categories_page_button: 'Syncing Categories...',
            category_sync_page_complete: 'Category Sync Complete!',
            syncing_just_categories_page_status: 'Syncing categories...',
            fix_hierarchy_button: 'Fix Category Hierarchy',
            fixing_hierarchy: 'Fixing hierarchy...',
            hierarchy_fixed: 'Category hierarchy fix attempt complete.',
            importing_variations_status: 'Importing variations for {productName} ({currentBatch} of {totalBatches})',
            processing_variation_batch: 'Processing variation batch...',
            variations_imported_successfully: 'All variations imported successfully for {productName}.',
            error_importing_variations: 'Error importing variations for {productName}. See log.',
            parent_product_imported_pending_variations: 'Parent product {productName} imported. Starting variation import...',
            load_sync_preview: 'Reload Sync Data', // MODIFIED
            loading_sync_preview: 'Reloading sync data...', // MODIFIED
            preview_loaded_ready_to_sync: 'Preview loaded. Ready to start full sync.',
            categories_for_preview: 'Categories to be Synced:',
            products_for_preview: 'Products to be Synced:',
            preview_load_error: 'Error loading preview data. Please try again or proceed with sync.',
            // REMOVED: variations_count_in_preview: 'Variation count will be determined when sync starts.',
            products_available_info: 'Ecwid products available for selection: {count}',
            categories_step_complete: 'Categories step complete! Starting product sync...',
            products_step_complete: 'Products step complete!',
            stop_full_sync_button_text: 'STOP SYNC',
            sync_stopped_by_user_log: 'SYNC HAS BEEN STOPPED BY THE USER.',
            sync_stopped_by_user_status: 'Sync stopped by user.',
            sync_cancelled_log_message: 'Sync cancelled by user, aborting further operations.',
            // Add the new connection test strings
            testing_connection: 'Testing...',
            connection_successful: 'CONNECTION SUCCESSFUL!',
            connection_failed: 'CONNECTION UNSUCCESSFUL - PLEASE CHECK YOUR API KEY AND STORE ID AND TRY AGAIN',
            save_settings_failed: 'Failed to save settings. Please try again.',
            // Add any other i18n strings used in the JS with their defaults
        };
        for (const key in i18n_defaults) {
            if (!i18n[key]) {
                i18n[key] = i18n_defaults[key];
            }
        }        const fullSyncSteps = (ecwid_sync_params.sync_steps && ecwid_sync_params.sync_steps.length > 0) ? ecwid_sync_params.sync_steps : ['categories', 'products', 'customers', 'orders'];
        const totalFullSyncSteps = fullSyncSteps.length;
        const variationBatchSize = parseInt(ecwid_sync_params.variation_batch_size) || 50; // Ensure it's an integer

        // --- Utility Functions ---
        function capitalizeFirstLetter(string) {
            if (!string) return ''; // Handle empty or null string
            return string.charAt(0).toUpperCase() + string.slice(1);
        }

        // Define updateOverallFullSyncProgress here
        function updateOverallFullSyncProgress(currentStepProgressPercent) {
            if (grandTotalAllItemsForSync <= 0) {
                // If total items is 0, progress is either 0 or 100 if the step is done
                let overallPercentage = 0;
                if (currentFullSyncStepIndex >= totalFullSyncSteps -1 && currentStepProgressPercent >= 100) {
                    overallPercentage = 100;
                }
                updateProgressBar(fullSyncProgressBar, overallPercentage);
                return;
            }

            let completedStepsWeight = 0;
            // Calculate weight of completed steps
            for (let i = 0; i < currentFullSyncStepIndex; i++) {
                if (fullSyncSteps[i] === 'categories' && totalCategoriesToSync > 0) {
                    completedStepsWeight += totalCategoriesToSync;
                } else if (fullSyncSteps[i] === 'products' && totalProductsToSync > 0) {
                    completedStepsWeight += totalProductsToSync;
                } else if (fullSyncSteps[i] === 'customers' && totalCustomersToSync > 0) {
                    completedStepsWeight += totalCustomersToSync;
                } else if (fullSyncSteps[i] === 'orders' && totalOrdersToSync > 0) {
                    completedStepsWeight += totalOrdersToSync;
                }
            }

            let currentStepWeight = 0;
            if (fullSyncSteps[currentFullSyncStepIndex] === 'categories' && totalCategoriesToSync > 0) {
                currentStepWeight = (currentStepProgressPercent / 100) * totalCategoriesToSync;
            } else if (fullSyncSteps[currentFullSyncStepIndex] === 'products' && totalProductsToSync > 0) {
                currentStepWeight = (currentStepProgressPercent / 100) * totalProductsToSync;
            } else if (fullSyncSteps[currentFullSyncStepIndex] === 'customers' && totalCustomersToSync > 0) {
                currentStepWeight = (currentStepProgressPercent / 100) * totalCustomersToSync;
            } else if (fullSyncSteps[currentFullSyncStepIndex] === 'orders' && totalOrdersToSync > 0) {
                currentStepWeight = (currentStepProgressPercent / 100) * totalOrdersToSync;
            }
            
            // If current step has no items, but it's completed, consider its "weight" fully contributed if it's not the only step
            if ( (fullSyncSteps[currentFullSyncStepIndex] === 'categories' && totalCategoriesToSync === 0 && currentStepProgressPercent >=100) ||
                 (fullSyncSteps[currentFullSyncStepIndex] === 'products' && totalProductsToSync === 0 && currentStepProgressPercent >=100) ||
                 (fullSyncSteps[currentFullSyncStepIndex] === 'customers' && totalCustomersToSync === 0 && currentStepProgressPercent >=100) ||
                 (fullSyncSteps[currentFullSyncStepIndex] === 'orders' && totalOrdersToSync === 0 && currentStepProgressPercent >=100) ) {
                // This logic might need refinement if a step with 0 items shouldn't contribute to progress unless it's the *only* step.
                // For now, if a step with 0 items is "100% complete", it doesn't add to currentStepWeight unless explicitly handled.
                // The overall progress will mostly be driven by steps that *do* have items.
            }


            const totalProgressValue = completedStepsWeight + currentStepWeight;
            let overallPercentage = (totalProgressValue / grandTotalAllItemsForSync) * 100;
            
            overallPercentage = Math.min(100, Math.max(0, overallPercentage)); // Clamp between 0 and 100

            updateProgressBar(fullSyncProgressBar, overallPercentage);
            fullSyncOverallProgress = overallPercentage; // Update the state variable if needed elsewhere
        }


        let batchStatusInterval = null;

        // --- UI Element Selectors ---
        // Full Sync UI Elements
        const fullSyncButton = $('#full-sync-button');
        const fullSyncProgressBar = $('#full-sync-bar');
        const fullSyncStatusDiv = $('#full-sync-status');
        const fullSyncLogDiv = $('#full-sync-log');
        const fullSyncCountsInfoDiv = $('#full-sync-counts-info');
        const loadFullSyncPreviewButton = $('#load-full-sync-preview-button');
        const fullSyncPreviewContainer = $('#full-sync-preview-container');
        const fullSyncCategoryPreviewList = $('#full-sync-category-preview-list');
        const fullSyncProductPreviewList = $('#full-sync-product-preview-list');
        const fullSyncCustomerPreviewList = $('#full-sync-customer-preview-list');
        const fullSyncOrderPreviewList = $('#full-sync-order-preview-list');
        const fullSyncProgressContainer = $('#full-sync-progress-container');
        const stopFullSyncButton = $('#stop-full-sync-button'); // ADDED
        const fullSyncInitialInfoDiv = $('#full-sync-initial-info'); // ADDED

        // Category Sync Page UI Elements
        const categoryPageSyncButton = $('#category-page-sync-button');
        const categoryPageSyncProgressBar = $('#category-page-sync-bar');
        const categoryPageSyncStatusDiv = $('#category-page-sync-status');
        const categoryPageSyncLogDiv = $('#category-page-sync-log');
        const categoryPageSyncProgressBarContainer = $('#category-page-sync-progress-container');
        
        // Enhanced Category Sync UI Elements
        const categorySyncActivity = $('#category-sync-activity');
        const categoryCurrentBatchInfo = $('#category-current-batch-info');
        const categoryProcessingIndicator = $('#category-processing-indicator');
        const categoryProcessingText = $('#category-processing-text');
        const categorySyncStats = $('#category-sync-stats');
        const loadCategoriesButton = $('#load-ecwid-categories-button');
        const categoryListContainer = $('#selective-category-list-container');
        
        // Create or get pagination container for categories (place it right after the load button)
        let categoryPaginationContainer = $('#category-pagination-container');
        if (!categoryPaginationContainer.length) {
            // Create pagination container and insert it after the load button
            loadCategoriesButton.after('<div id="category-pagination-container" style="margin-top: 10px;"></div>');
            categoryPaginationContainer = $('#category-pagination-container');
        }
        
        const categorySyncInitialInfoDiv = $('#selective-sync-initial-info');
        const fixHierarchyButton = $('#fix-category-hierarchy-button');
        const importSelectedCategoriesButton = $('#import-selected-categories-button');
        const syncAllCategoriesButton = $('#sync-all-categories-button');

        // Selective Product Sync UI Elements
        const loadProductsButton = $('#load-ecwid-products-button');
        const productListContainer = $('#selective-product-list-container');
        
        // Create or get pagination container (place it right after the load button)
        let paginationContainer = $('#product-pagination-container');
        if (!paginationContainer.length) {
            // Create pagination container and insert it after the load button
            loadProductsButton.after('<div id="product-pagination-container" style="margin-top: 10px;"></div>');
            paginationContainer = $('#product-pagination-container');
        }
        const importSelectedButton = $('#import-selected-products-button');
        const syncAllProductsButton = $('#sync-all-products-button');
        const selectiveSyncStatusDiv = $('#selective-sync-status');
        const selectiveSyncProgressBar = $('#selective-sync-bar');
        const selectiveSyncProgressBarContainer = $('#selective-sync-progress-container');
        const selectiveSyncLogDiv = $('#selective-sync-log');
        const selectiveSyncInitialInfoDiv = $('#selective-sync-initial-info'); // ADD OR CONFIRM THIS SELECTOR

        // --- State Variables ---
        let currentFullSyncStepIndex = 0;
        let currentFullSyncStepType = '';
        let currentFullSyncStepOffset = 0;
        let currentFullSyncStepTotalItems = 0;
        let fullSyncOverallProgress = 0; // This variable can be used by the new function

        let totalCategoriesToSync = 0; // Ensure these are declared in a scope accessible by updateOverallFullSyncProgress
        let totalProductsToSync = 0;   // and are updated by fetchAndDisplayFullSyncCounts
        let totalCustomersToSync = 0;
        let totalOrdersToSync = 0;
        let grandTotalAllItemsForSync = 0;
        let fullSyncVariationQueue = [];
        let currentFullSyncVariationProductData = null;
        let isSyncCancelledByUser = false; // ADDED: Flag to control sync cancellation
        let fullSyncRetryCount = 0; // ADDED: Track retry attempts for better error handling
        // Store continuation data for parent batch processing
        let fullSyncParentContinuation = {
            hasMore: false,
            nextOffset: 0,
            syncType: '',
            totalItems: 0
        };

        let totalCategoriesForCategoryPageSync = 0; // Add this line

        // Selective Product Sync State
        let ecwidProductsForSelection = []; // For selective product sync
        let productsToImportSelectedIds = []; // For selective product sync
        let currentSelectiveImportProductIndex = 0; // For selective product sync
        let currentProductVariationData = null; // For selective product variation batching
        
        // Product Pagination State
        let currentProductPage = 1;
        let productsPerPage = 50; // Show 50 products per page to prevent browser freeze
        let currentlyDisplayedProducts = []; // Currently displayed product subset
        let selectedProductIds = new Set(); // Track selected products across all pages

        // Selective Category Sync State
        let ecwidCategoriesForSelection = []; // For selective category sync
        
        // Category Pagination State
        let currentCategoryPage = 1;
        let categoriesPerPage = 50; // Show 50 categories per page to prevent browser freeze
        let currentlyDisplayedCategories = []; // Currently displayed category subset
        let selectedCategoryIds = new Set(); // Track selected categories across all pages

        let animationInterval = null; // For status text animation

        // --- Helper Functions ---
        // REMOVED duplicate sanitizeHTML function

        const MAX_LOG_LINES = 500; // Maximum number of log lines to keep in the DOM

        function logMessage(logDiv, message, type) {
            if (!logDiv || !logDiv.length) return; // Guard against missing logDiv

            let color = 'black';
            let prefix = '';
            switch (type) {
                case 'success':
                    color = 'green';
                    prefix = 'SUCCESS: ';
                    break;
                case 'error':
                    color = 'red';
                    prefix = 'ERROR: ';
                    break;
                case 'warning':
                    color = 'orange';
                    prefix = 'WARNING: ';
                    break;
                case 'info':
                default:
                    color = 'black';
                    break;
            }
        
            // Sanitize the message content, not the HTML structure of the paragraph
            const cleanMessage = sanitizeHTML(message); 
            logDiv.append(`<p style="color:${color}; margin: 2px 0; padding: 0; white-space: pre-wrap; word-wrap: break-word;"><strong>${prefix}</strong>${cleanMessage}</p>`);
        
            // Limit log lines
            const lines = logDiv.children('p');
            if (lines.length > MAX_LOG_LINES) {
                lines.slice(0, lines.length - MAX_LOG_LINES).remove();
            }
            logDiv.scrollTop(logDiv[0].scrollHeight);
        }
        
        function categorizeAndLog(logDiv, logEntry) {
            let logType = 'info'; 
            if (typeof logEntry === 'string') {
                const upperLogEntry = logEntry.toUpperCase();
                if (upperLogEntry.includes('[CRITICAL]') || upperLogEntry.includes('[ERROR]') || upperLogEntry.includes('FAILED TO') || upperLogEntry.includes('FAILURE')) {
                    logType = 'error';
                } else if (upperLogEntry.includes('SUCCESSFULLY') || upperLogEntry.includes('IMPORTED') || upperLogEntry.includes('SKIPPED') || upperLogEntry.includes('COMPLETED')) {
                    logType = 'success';
                } else if (upperLogEntry.includes('[WARNING]')) {
                    logType = 'warning';
                }
            } else if (typeof logEntry === 'object' && logEntry !== null && logEntry.message) {
                // If logEntry is an object with a message and type (custom format)
                logMessage(logDiv, logEntry.message, logEntry.type || 'info');
                return;
            }
            logMessage(logDiv, logEntry, logType);
        }

        function updateStatus(statusDiv, statusText) {
            statusDiv.text(statusText);
        }

        function updateProgressBar(progressBarElem, percentage, duration = 200) {
            let displayPercentage = Math.round(percentage);
            // Clamp the displayPercentage to be between 0 and 100
            displayPercentage = Math.max(0, Math.min(100, displayPercentage));

            // Clamp the animationPercentage (original precise value) to be between 0 and 100
            let animationPercentage = Math.max(0, Math.min(100, percentage));

            // Stop any ongoing animation on this element, clear the queue, but don't jump to end
            progressBarElem.stop(true, false).animate({
                width: animationPercentage + '%'
            }, duration);
            
            // Update the text immediately to the rounded and clamped value
            progressBarElem.text(displayPercentage + '%');
        }

        function handleAjaxError(statusDiv, logDiv, buttonElem, buttonText, syncType, responseData, isNetworkError = false) {
            const errorMessage = responseData && responseData.message ? responseData.message : (isNetworkError ? 'Network error' : i18n.sync_error);
            const statusMessage = i18n.sync_error + (syncType ? ` (${syncType})` : '') + (responseData && responseData.message ? `: ${responseData.message}` : '');
            
            // Check if this is a server error that should suggest retry
            let isServerError = false;
            let retryRecommended = false;
            
            if (responseData) {
                isServerError = responseData.is_server_error || false;
                retryRecommended = responseData.retry_recommended || false;
            }
            
            // Enhanced status message for server errors
            let enhancedStatusMessage = statusMessage;
            if (isServerError && retryRecommended) {
                enhancedStatusMessage += ' 🔄 You can try again in a few minutes.';
            }
            
            updateStatus(statusDiv, enhancedStatusMessage);
            logMessage(logDiv, (syncType ? `${syncType}: ` : '') + 'Error - ' + errorMessage, 'error');
            
            // Special handling for server errors
            if (isServerError) {
                logMessage(logDiv, '⚠️ This appears to be a temporary server issue on Ecwid\'s side.', 'warning');
                if (retryRecommended) {
                    logMessage(logDiv, '🔄 Recommendation: Wait a few minutes and try again.', 'info');
                }
            }
            
            if (responseData && responseData.details) {
                console.error("Sync Error Details" + (syncType ? ` for ${syncType}` : '') + ":", responseData.details);
                logMessage(logDiv, "Details: " + JSON.stringify(responseData.details), 'error');
            }
            
            if (responseData && responseData.logs && Array.isArray(responseData.logs)) {
                responseData.logs.forEach(logEntry => categorizeAndLog(logDiv, logEntry));
            }
            
            if (buttonElem && buttonText) {
                buttonElem.removeClass('disabled').html(buttonText);
            }
        }

        function startBatchStatusAnimation(statusDiv, baseText) {
            if (!statusDiv || !statusDiv.length) return; // Ensure statusDiv exists
            stopBatchStatusAnimation(); // Clear any existing animation
            let dots = 0;
            statusDiv.text(baseText); // Set initial text
            animationInterval = setInterval(function() {
                dots = (dots + 1) % 4;
                let newText = baseText + '.'.repeat(dots);
                statusDiv.text(newText);
            }, 500);
        }

        function stopBatchStatusAnimation() {
            if (animationInterval) {
                clearInterval(animationInterval);
                animationInterval = null;
            }
            // Optionally, you might want to reset the text of the statusDiv here if it's left with dots,
            // but usually, the calling function will set a new status text.
        }

        // --- Full Sync Page Logic ---

        // Define fetchAndDisplayFullSyncCounts first
        function fetchAndDisplayFullSyncCounts() {
            if (isSyncCancelledByUser) return; // ADDED: Check cancellation
            if (!loadFullSyncPreviewButton.length) return;

            updateStatus(fullSyncStatusDiv, i18n.fetching_counts || 'Fetching item counts...');
            // Actual AJAX call to get counts (e.g., total categories, total products)
            // and update fullSyncCountsInfoDiv.
            // This function seems to be more about the *counts* than the preview *lists*.
            // The preview lists are handled by loadAndDisplayFullSyncPreview.
        }

        // Function to load and display the sync preview
        function loadAndDisplayFullSyncPreview() {
            if (isSyncCancelledByUser) return;
            if (!loadFullSyncPreviewButton.length) return;

            loadFullSyncPreviewButton.prop('disabled', true).text(i18n.loading_sync_preview || 'Reloading sync data...');
            updateStatus(fullSyncStatusDiv, i18n.loading_sync_preview || 'Reloading sync data...');
            
            // Show loading state in initial info container
            if (fullSyncInitialInfoDiv.length) {
                fullSyncInitialInfoDiv.html(`
                    <div style="padding: 15px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px; margin: 10px 0;">
                        <strong>⏳ Loading Full Sync Data...</strong><br>
                        <span style="font-size: 12px; color: #856404;">Fetching Categories, Products, Customers, and Orders from Ecwid API</span>
                    </div>
                `);
            }
            
            // Only update these if they exist
            if (fullSyncCategoryPreviewList.length) {
                fullSyncCategoryPreviewList.html('<em>' + (i18n.loading_ecwid_categories || 'Loading Categories...') + '</em>');
            }
            if (fullSyncProductPreviewList.length) {
                fullSyncProductPreviewList.html('<em>' + (i18n.loading_products || 'Loading Products...') + '</em>');
            }
            if (fullSyncCustomerPreviewList.length) {
                fullSyncCustomerPreviewList.html('<em>' + (i18n.loading_customers || 'Loading Customers...') + '</em>');
            }
            if (fullSyncOrderPreviewList.length) {
                fullSyncOrderPreviewList.html('<em>' + (i18n.loading_orders || 'Loading Orders...') + '</em>');
            }

            $.ajax({
                url: ajax_url,
                type: 'POST',
                data: {
                    action: 'ecwid_wc_fetch_full_sync_counts',
                    nonce: nonce,
                },
                dataType: 'json'
            })
            .done(function(response) {
                console.log('Full sync preview response:', response);
                
                if (response.success && response.data) {
                    totalCategoriesToSync = parseInt(response.data.categories_count) || 0;
                    totalProductsToSync = parseInt(response.data.products_count) || 0;
                    totalCustomersToSync = parseInt(response.data.customers_count) || 0;
                    totalOrdersToSync = parseInt(response.data.orders_count) || 0;
                    grandTotalAllItemsForSync = totalCategoriesToSync + totalProductsToSync + totalCustomersToSync + totalOrdersToSync;

                    const categories = response.data.categories_preview || [];
                    const products = response.data.products_preview || [];
                    const customers = response.data.customers_preview || [];
                    const orders = response.data.orders_preview || [];
                    
                    console.log('Categories preview data available:', categories ? categories.length : 0);
                    console.log('Products preview data available:', products ? products.length : 0);
                    console.log('Customers preview data available:', customers ? customers.length : 0);
                    console.log('Orders preview data available:', orders ? orders.length : 0);

                    // Only update preview lists if the elements exist
                    if (fullSyncCategoryPreviewList.length) {
                        fullSyncCategoryPreviewList.empty();
                        if (categories && categories.length > 0) {
                            categories.forEach(cat => {
                                fullSyncCategoryPreviewList.append(`<div>${sanitizeHTML(cat.name || 'Unnamed Category')}</div>`);
                            });
                            fullSyncCategoryPreviewList.append(`<hr><p><strong>Total categories to sync: ${totalCategoriesToSync}</strong></p>`);
                        } else {
                            fullSyncCategoryPreviewList.html('<em>' + (i18n.no_categories_found_display || 'No categories found or an error occurred.') + '</em>');
                        }
                    }

                    if (fullSyncProductPreviewList.length) {
                        fullSyncProductPreviewList.empty();
                        if (products && products.length > 0) {
                            products.forEach(prod => {
                                const productName = sanitizeHTML(prod.name || 'Unnamed Product');
                                const productId = prod.id || 'N/A';
                                const productSku = prod.sku ? ` | SKU: ${sanitizeHTML(prod.sku)}` : '';
                                const variationCount = prod.combinations ? ` | ${prod.combinations.length} variations` : '';
                                fullSyncProductPreviewList.append(`<div>${productName} (ID: ${productId}${productSku}${variationCount})</div>`);
                            });
                            fullSyncProductPreviewList.append(`<hr><p><strong>Total products to sync: ${totalProductsToSync}</strong></p>`);
                        } else {
                            fullSyncProductPreviewList.html('<em>' + (i18n.no_products_found || 'No enabled products found or failed to fetch.') + '</em>');
                        }
                    }

                    if (fullSyncCustomerPreviewList.length) {
                        fullSyncCustomerPreviewList.empty();
                        if (customers && customers.length > 0) {
                            customers.forEach(customer => {
                                const customerName = sanitizeHTML(customer.name || 'Unnamed Customer');
                                const customerEmail = sanitizeHTML(customer.email || 'No email');
                                const customerId = customer.id || 'N/A';
                                fullSyncCustomerPreviewList.append(`<div>${customerName} (${customerEmail}) - ID: ${customerId}</div>`);
                            });
                            fullSyncCustomerPreviewList.append(`<hr><p><strong>Total customers to sync: ${totalCustomersToSync}</strong></p>`);
                        } else {
                            fullSyncCustomerPreviewList.html('<em>' + (i18n.no_customers_found || 'No customers found or access denied.') + '</em>');
                        }
                    }

                    if (fullSyncOrderPreviewList.length) {
                        fullSyncOrderPreviewList.empty();
                        if (orders && orders.length > 0) {
                            orders.forEach(order => {
                                const orderNumber = sanitizeHTML(order.orderNumber || 'N/A');
                                const customerEmail = sanitizeHTML(order.email || 'No email');
                                const orderId = order.id || 'N/A';
                                const total = order.total ? `$${order.total}` : 'N/A';
                                fullSyncOrderPreviewList.append(`<div>Order #${orderNumber} (${customerEmail}) - ${total} - ID: ${orderId}</div>`);
                            });
                            fullSyncOrderPreviewList.append(`<hr><p><strong>Total orders to sync: ${totalOrdersToSync}</strong></p>`);
                        } else {
                            fullSyncOrderPreviewList.html('<em>' + (i18n.no_orders_found || 'No orders found or access denied.') + '</em>');
                        }
                    }
                    
                    let countText = (i18n.categories_to_sync_info || 'Categories to sync: {count}').replace('{count}', totalCategoriesToSync) + ', ' +
                                    (i18n.products_to_sync_info || 'Products to sync: {count}').replace('{count}', totalProductsToSync) + ', ' +
                                    (i18n.customers_to_sync_info || 'Customers to sync: {count}').replace('{count}', totalCustomersToSync) + ', ' +
                                    (i18n.orders_to_sync_info || 'Orders to sync: {count}').replace('{count}', totalOrdersToSync);
                    fullSyncCountsInfoDiv.text(countText);

                    updateStatus(fullSyncStatusDiv, i18n.preview_loaded_ready_to_sync || 'Preview loaded. Ready to start full sync.');
                    
                    // Update initial info container with success state
                    if (fullSyncInitialInfoDiv.length) {
                        const styledStatusHtml = `
                            <div style="padding: 15px; background: #d1ecf1; border: 1px solid #bee5eb; border-radius: 4px; margin: 10px 0;">
                                <p style="margin: 0 0 5px 0; font-weight: bold;">🎯 Full Sync Data Loaded Successfully!</p>
                                <p style="margin: 0 0 10px 0; font-size: 12px; color: #0c5460; font-style: normal;">Ready to sync all store data: Categories, Products, Customers, and Orders</p>
                                <div style="margin: 10px 0; display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; font-size: 11px;">
                                    <div style="background: #f8f9fa; padding: 8px; border-radius: 3px; text-align: center;">
                                        <strong>📂 Categories</strong><br>
                                        <span style="color: #28a745; font-weight: bold;">${totalCategoriesToSync}</span>
                                    </div>
                                    <div style="background: #f8f9fa; padding: 8px; border-radius: 3px; text-align: center;">
                                        <strong>📦 Products</strong><br>
                                        <span style="color: #28a745; font-weight: bold;">${totalProductsToSync}</span>
                                    </div>
                                    <div style="background: #f8f9fa; padding: 8px; border-radius: 3px; text-align: center;">
                                        <strong>👥 Customers</strong><br>
                                        <span style="color: #28a745; font-weight: bold;">${totalCustomersToSync}</span>
                                    </div>
                                    <div style="background: #f8f9fa; padding: 8px; border-radius: 3px; text-align: center;">
                                        <strong>🛒 Orders</strong><br>
                                        <span style="color: #28a745; font-weight: bold;">${totalOrdersToSync}</span>
                                    </div>
                                </div>
                                <details style="margin-top: 10px; font-size: 11px; color: #6c757d;">
                                    <summary style="cursor: pointer;">🔍 Full Sync Details (click to expand)</summary>
                                    <div style="margin-top: 5px; padding: 5px; background: #f9f9f9; border-radius: 3px;">
                                        <strong>Total Items:</strong> ${grandTotalAllItemsForSync}<br>
                                        <strong>Sync Steps:</strong> Categories → Products → Customers → Orders<br>
                                        <strong>Progress Tracking:</strong> Real-time with weighted calculations<br>
                                        <strong>Data Source:</strong> Ecwid Store API
                                    </div>
                                </details>
                            </div>
                        `;
                        fullSyncInitialInfoDiv.html(styledStatusHtml);
                    }
                    
                    // Only show preview container if it exists
                    if (fullSyncPreviewContainer.length) {
                        fullSyncPreviewContainer.slideDown();
                    }
                    fullSyncButton.show();
                } else {
                    const errorMsg = response.data && response.data.message ? response.data.message : (i18n.preview_load_error || 'Error loading preview data.');
                    updateStatus(fullSyncStatusDiv, errorMsg);
                    logMessage(fullSyncLogDiv, 'Preview Error: ' + errorMsg, 'error');
                    
                    // Update initial info container with error state
                    if (fullSyncInitialInfoDiv.length) {
                        fullSyncInitialInfoDiv.html(`
                            <div style="padding: 15px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px; margin: 10px 0;">
                                <strong>❌ Failed to Load Full Sync Data</strong><br>
                                <span style="font-size: 12px; color: #721c24;">${sanitizeHTML(errorMsg)}</span>
                            </div>
                        `);
                    }
                    
                    // Handle error case but still try to show preview data if available
                    if (response.data) {
                        const categories = response.data.categories_preview || [];
                        const products = response.data.products_preview || [];
                        
                        if (fullSyncCategoryPreviewList.length && categories && categories.length > 0) {
                            fullSyncCategoryPreviewList.empty();
                            categories.forEach(cat => {
                                fullSyncCategoryPreviewList.append(`<div>${sanitizeHTML(cat.name || 'Unnamed Category')}</div>`);
                            });
                        } else if (fullSyncCategoryPreviewList.length) {
                            fullSyncCategoryPreviewList.html('<em>' + (i18n.no_categories_found_display || 'No categories found or an error occurred.') + '</em>');
                        }
                        
                        if (fullSyncProductPreviewList.length && products && products.length > 0) {
                            fullSyncProductPreviewList.empty();
                            products.forEach(prod => {
                                fullSyncProductPreviewList.append(`<div>${sanitizeHTML(prod.name || 'Unnamed Product')} (ID: ${prod.id || 'N/A'})</div>`);
                            });
                        } else if (fullSyncProductPreviewList.length) {
                            fullSyncProductPreviewList.html('<em>' + (i18n.no_products_found || 'No enabled products found or failed to fetch.') + '</em>');
                        }
                    } else {
                        fullSyncCategoryPreviewList.html('<em>Error loading categories.</em>');
                        fullSyncProductPreviewList.html('<em>Error loading products.</em>');
                    }
                }
            })
            .fail(function(jqXHR, textStatus, errorThrown) {
                const errorMsg = i18n.ajax_error || 'AJAX Error. Check console or log for details.';
                updateStatus(fullSyncStatusDiv, errorMsg + ` (${textStatus})`);
                logMessage(fullSyncLogDiv, `Failed to load sync preview: ${textStatus}, ${errorThrown}`, 'error');
                console.error('AJAX error details:', jqXHR.responseText);
                
                // Update initial info container with AJAX error state
                if (fullSyncInitialInfoDiv.length) {
                    fullSyncInitialInfoDiv.html(`
                        <div style="padding: 15px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px; margin: 10px 0;">
                            <strong>❌ Connection Error</strong><br>
                            <span style="font-size: 12px; color: #721c24;">Failed to connect to Ecwid API. Please check your connection and try again.</span>
                        </div>
                    `);
                }
                
                if (fullSyncCategoryPreviewList.length) {
                    fullSyncCategoryPreviewList.html('<em>AJAX error loading categories.</em>');
                }
                if (fullSyncProductPreviewList.length) {
                    fullSyncProductPreviewList.html('<em>AJAX error loading products.</em>');
                }
            })
            .always(function() {
                loadFullSyncPreviewButton.prop('disabled', false).text(i18n.load_sync_preview || 'Reload Sync Data');
            });
        }


        if (loadFullSyncPreviewButton.length) {
            // Automatically load the preview when the page is ready if the button exists
            loadAndDisplayFullSyncPreview(); 

            // Keep the original click handler in case the user wants to manually refresh
            loadFullSyncPreviewButton.on('click', function(e) {
                e.preventDefault();
                if ($(this).hasClass('disabled')) return;
                loadAndDisplayFullSyncPreview(); 
            });
        }

        if (fullSyncButton.length) {
            fullSyncButton.on('click', function() {
                isSyncCancelledByUser = false; // Reset cancellation flag
                logMessage(fullSyncLogDiv, i18n.sync_starting || 'Sync starting...', 'info');
                fullSyncButton.prop('disabled', true).text(i18n.syncing_button || 'Syncing...');
                stopFullSyncButton.show(); // Show STOP button
                loadFullSyncPreviewButton.prop('disabled', true); // Disable reload preview during sync

                currentFullSyncStepIndex = 0;
                currentFullSyncStepOffset = 0;
                fullSyncOverallProgress = 0;
                grandTotalAllItemsForSync = totalCategoriesToSync + totalProductsToSync + totalCustomersToSync + totalOrdersToSync; // Recalculate here or ensure it's fresh

                fullSyncProgressBar.css('width', '0%').text('0%');
                fullSyncProgressContainer.show();
                fullSyncLogDiv.html(''); // Clear previous logs

                processNextFullSyncStep();
            });
        }

        if (stopFullSyncButton.length) { // ADDED: Stop button handler
            stopFullSyncButton.on('click', function() {
                isSyncCancelledByUser = true;
                logMessage(fullSyncLogDiv, i18n.sync_stopped_by_user_log || 'SYNC HAS BEEN STOPPED BY THE USER.', 'warning');
                updateStatus(fullSyncStatusDiv, i18n.sync_stopped_by_user_status || 'Sync stopped by user.');
                
                stopFullSyncButton.hide();
                fullSyncButton.text(i18n.start_sync || 'Start Full Sync').prop('disabled', false);
                loadFullSyncPreviewButton.prop('disabled', false); // Re-enable reload preview

                // Reset progress and state
                fullSyncProgressBar.css('width', '0%').text('0%');
                // fullSyncProgressContainer.hide(); // Optionally hide progress bar
                stopBatchStatusAnimation();
                
                // Clear queues and reset relevant state variables
                fullSyncVariationQueue = [];
                currentFullSyncVariationProductData = null;
                currentFullSyncStepIndex = 0; 
                currentFullSyncStepOffset = 0;
                fullSyncOverallProgress = 0;
                // Add any other specific full sync state resets here if needed
            });
        }

        function processNextFullSyncStep() {
            if (isSyncCancelledByUser) { // ADDED: Check cancellation
                logMessage(fullSyncLogDiv, i18n.sync_cancelled_log_message || 'Sync cancelled, not proceeding to next step.', 'info');
                return;
            }

            if (currentFullSyncStepIndex < totalFullSyncSteps) {
                const syncType = fullSyncSteps[currentFullSyncStepIndex];
                let currentTotalForStep = 0;
                if (syncType === 'categories') {
                    currentTotalForStep = totalCategoriesToSync;
                } else if (syncType === 'products') {
                    currentTotalForStep = totalProductsToSync;
                } else if (syncType === 'customers') {
                    currentTotalForStep = totalCustomersToSync;
                } else if (syncType === 'orders') {
                    currentTotalForStep = totalOrdersToSync;
                }
                // Update status to "Syncing {type}: 0 of {total}..."
                const initialStepStatus = i18n.syncing_item_of_total
                    .replace('{syncType}', syncType.charAt(0).toUpperCase() + syncType.slice(1))
                    .replace('{current}', 0)
                    .replace('{total}', currentTotalForStep > 0 ? currentTotalForStep : 'N/A');
                updateStatus(fullSyncStatusDiv, initialStepStatus);
                processFullSyncBatch(syncType, 0, currentTotalForStep); // Pass the total for this step
            } else {
                stopBatchStatusAnimation();
                updateStatus(fullSyncStatusDiv, i18n.sync_complete || 'Sync Complete!');
                logMessage(fullSyncLogDiv, '✅ Full synchronization completed successfully! All categories and products have been processed.', 'success');
                fullSyncButton.text(i18n.start_sync || 'Start Full Sync').prop('disabled', false);
                stopFullSyncButton.hide(); // Hide STOP button on completion
                loadFullSyncPreviewButton.prop('disabled', false); // Re-enable reload preview
                updateOverallFullSyncProgress(100); // Ensure it hits 100%
                return;
            }
        }

        function processFullSyncBatch(syncType, offset, totalKnownItems) {
            if (isSyncCancelledByUser) { // ADDED: Check cancellation
                logMessage(fullSyncLogDiv, i18n.sync_cancelled_log_message || 'Sync cancelled, aborting batch processing.', 'info');
                return;
            }
            currentFullSyncStepType = syncType; 
            currentFullSyncStepOffset = offset;
            // currentFullSyncStepTotalItems = totalKnownItems; // This was for the step, not used by updateOverallFullSyncProgress directly

            const statusMsg = i18n.syncing_item_of_total
                .replace('{syncType}', capitalizeFirstLetter(syncType))
                .replace('{current}', offset)
                .replace('{total}', totalKnownItems > 0 ? totalKnownItems : 'N/A');
            startBatchStatusAnimation(fullSyncStatusDiv, statusMsg);

            $.ajax({
                url: ajax_url,
                method: 'POST',
                data: { action: 'ecwid_wc_batch_sync', nonce: nonce, sync_type: syncType, offset: offset },
                success: function(response) {
                    stopBatchStatusAnimation();
                    // Log the main batch_logs from PHP
                    (response.data.batch_logs || []).forEach(logEntry => categorizeAndLog(fullSyncLogDiv, logEntry));

                    if (response.success) {
                        // const itemsProcessedInBatch = (response.data.next_offset - offset); // Not directly used for overall progress
                        let currentStepProgressPercent = 0;
                        if (syncType === 'categories' && totalCategoriesToSync > 0) {
                            currentStepProgressPercent = (response.data.next_offset / totalCategoriesToSync) * 100;
                        } else if (syncType === 'products' && totalProductsToSync > 0) {
                            currentStepProgressPercent = (response.data.next_offset / totalProductsToSync) * 100;
                        } else if (response.data.has_more === false) { // Step has no items or API error, but it's "done"
                            currentStepProgressPercent = 100;
                        }
                        currentStepProgressPercent = Math.min(100, currentStepProgressPercent);
                        
                        updateOverallFullSyncProgress(currentStepProgressPercent); // Call the new function
                        
                        const itemsInStepForStatus = syncType === 'categories' ? totalCategoriesToSync : totalProductsToSync;
                        const statusUpdate = i18n.syncing_item_of_total
                            .replace('{syncType}', capitalizeFirstLetter(syncType))
                            .replace('{current}', response.data.next_offset)
                            .replace('{total}', itemsInStepForStatus > 0 ? itemsInStepForStatus : 'N/A');
                        updateStatus(fullSyncStatusDiv, statusUpdate);

                        // Populate variation queue from structured results
                        if (response.data.batch_item_results && response.data.batch_item_results.length > 0) {
                            response.data.batch_item_results.forEach(itemResult => {
                                if (itemResult.status === 'imported_parent_pending_variations' && itemResult.total_combinations > 0) {
                                    fullSyncVariationQueue.push({
                                        wc_product_id: itemResult.wc_product_id,
                                        ecwid_product_id: itemResult.ecwid_id,
                                        item_name: itemResult.item_name,
                                        sku: itemResult.sku,
                                        all_combinations: itemResult.all_combinations || [],
                                        total_combinations: itemResult.total_combinations,
                                        original_options: itemResult.original_options || [], // Get options from the initially fetched list
                                        current_variation_offset: 0
                                    });
                                    logMessage(fullSyncLogDiv, `[INFO] Queued product ${itemResult.item_name} (WC ID: ${itemResult.wc_product_id}) for variation processing (${itemResult.total_combinations} variations).`, 'info');
                                }
                            });
                        }
                        
                        // Check for product import errors and log a recommendation if needed
                        if (response.data.processed_type === 'products') {
                            let productImportErrorOccurred = false;
                            if (response.data.batch_item_results && response.data.batch_item_results.length > 0) {
                                response.data.batch_item_results.forEach(function(item_result) {
                                    if (item_result.status === 'failed') {
                                        productImportErrorOccurred = true;
                                    }
                                    // Log individual item results if needed
                                    // categorizeAndLog(fullSyncLogDiv, item_result);
                                });
                            }

                            // Check if this is the first time a product import error is noted in this full sync session
                            if (productImportErrorOccurred && !window.ecwidFullSyncProductErrorNoted) {
                                logMessage(ecwid_sync_params.i18n.second_import_recommended_after_product_error, 'error');
                                window.ecwidFullSyncProductErrorNoted = true; // Set a flag to avoid repeating this message
                            }
                        }

                        // Store parent continuation data
                        fullSyncParentContinuation.hasMore = response.data.has_more;
                        fullSyncParentContinuation.nextOffset = response.data.next_offset;
                        fullSyncParentContinuation.syncType = syncType;
                        fullSyncParentContinuation.totalItems = totalKnownItems;

                        handleFullSyncContinuation();

                    } else {
                        handleAjaxError(fullSyncStatusDiv, fullSyncLogDiv, fullSyncButton, i18n.start_sync, syncType, response.data);
                        fullSyncButton.show(); 
                        loadFullSyncPreviewButton.removeClass('disabled').prop('disabled', false);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    stopBatchStatusAnimation();
                    
                    // Enhanced error handling for different scenarios
                    let errorData = { message: `${textStatus} ${errorThrown || ''}` };
                    let shouldRetry = false;
                    
                    // Handle 500 Internal Server Errors specifically
                    if (jqXHR.status === 500) {
                        let errorMessage = 'Server Error (500) - This usually indicates a server memory limit, timeout, or processing issue.';
                        let suggestions = [];
                        
                        // Try to extract more specific error info from response
                        if (jqXHR.responseJSON && jqXHR.responseJSON.data) {
                            const responseData = jqXHR.responseJSON.data;
                            
                            if (responseData.error_type === 'fatal_error') {
                                errorMessage = responseData.message || errorMessage;
                                
                                // Add specific suggestions based on error type
                                if (responseData.suggested_action === 'increase_memory_or_reduce_batch') {
                                    suggestions.push('• Increase server memory limit to 128MB+ (recommended 256MB+) or contact your hosting provider');
                                    suggestions.push('• Try syncing categories and products separately');
                                    suggestions.push('• Reduce the number of items processed at once');
                                } else if (responseData.suggested_action === 'reduce_batch_size') {
                                    suggestions.push('• Try syncing smaller batches of items');
                                    suggestions.push('• Increase server execution time limit');
                                } else if (responseData.suggested_action === 'check_server_logs') {
                                    suggestions.push('• Check your server error logs for more details');
                                    suggestions.push('• Contact your hosting provider for assistance');
                                }
                                
                                // Add memory info if available
                                if (responseData.memory_info) {
                                    errorMessage += `\n\nMemory Info: Limit: ${responseData.memory_info.limit}, Usage: ${responseData.memory_info.usage}, Peak: ${responseData.memory_info.peak}`;
                                }
                            } else if (jqXHR.responseJSON.message) {
                                errorMessage = jqXHR.responseJSON.message;
                            }
                        }
                        
                        // Add general suggestions if none were provided
                        if (suggestions.length === 0) {
                            suggestions = [
                                '• Try refreshing the page and syncing again',
                                '• Check if other plugins are causing conflicts',
                                '• Contact your hosting provider to increase memory limits'
                            ];
                        }
                        
                        errorData.message = errorMessage;
                        if (suggestions.length > 0) {
                            errorData.message += '\n\nSuggestions:\n' + suggestions.join('\n');
                        }
                        
                        errorData.error_type = 'server_error';
                        errorData.retry_recommended = true;
                        shouldRetry = true;
                    } else if (jqXHR.status === 429) {
                        errorData.message = 'API rate limit exceeded. Will retry automatically.';
                        errorData.error_type = 'rate_limit';
                        shouldRetry = true;
                    } else if (jqXHR.status === 0) {
                        errorData.message = 'Network connection error. Please check your internet connection.';
                        errorData.error_type = 'network_error';
                    } else if (jqXHR.status >= 500) {
                        errorData.message = `Server error (${jqXHR.status}). This appears to be a temporary server issue.`;
                        errorData.retry_recommended = true;
                        shouldRetry = true;
                    }
                    
                    // Log the error with appropriate level
                    const logLevel = shouldRetry ? 'warning' : 'error';
                    logMessage(fullSyncLogDiv, `[${logLevel.toUpperCase()}] ${syncType} sync error: ${errorData.message}`, logLevel);
                    
                    // If this is a retryable error, try again after a delay
                    if (shouldRetry && fullSyncRetryCount < 3) {
                        fullSyncRetryCount++;
                        logMessage(fullSyncLogDiv, `[INFO] Retrying in 3 seconds... (Attempt ${fullSyncRetryCount}/3)`, 'info');
                        setTimeout(() => {
                            processFullSyncBatch(syncType, offset, totalKnownItems);
                        }, 3000);
                        return;
                    }
                    
                    // Reset retry count for next batch
                    fullSyncRetryCount = 0;
                    
                    handleAjaxError(fullSyncStatusDiv, fullSyncLogDiv, fullSyncButton, i18n.start_sync, syncType, errorData, true);
                }
            });
        }

        // New function to decide what to do after a parent batch or a variation product is processed
        function handleFullSyncContinuation() {
            if (isSyncCancelledByUser) { // ADDED: Check cancellation
                logMessage(fullSyncLogDiv, i18n.sync_cancelled_log_message || 'Sync cancelled, not continuing.', 'info');
                return;
            }

            // If there are items in the variation queue, process them first
            if (fullSyncVariationQueue.length > 0) {
                currentFullSyncVariationProductData = fullSyncVariationQueue.shift(); // Get and remove first item
                logMessage(fullSyncLogDiv, `[INFO] Starting variation processing for ${currentFullSyncVariationProductData.item_name} (Full Sync Queue).`, 'info');
                processFullSyncVariationBatchLoop(); // Start processing its variations
            } else {
                // Variation queue is empty, continue with parent items or next step
                if (fullSyncParentContinuation.hasMore) {
                    // Pass the correct total for the parent step type
                    const totalForNextParentBatch = fullSyncParentContinuation.syncType === 'categories' ? totalCategoriesToSync : totalProductsToSync;
                    processFullSyncBatch(fullSyncParentContinuation.syncType, fullSyncParentContinuation.nextOffset, totalForNextParentBatch);
                } else {
                    // Current step is fully complete (no more parent items and variation queue is empty)
                    updateStatus(fullSyncStatusDiv, i18n[currentFullSyncStepType + '_step_complete'] || `Step ${capitalizeFirstLetter(currentFullSyncStepType)} complete!`);
                    updateOverallFullSyncProgress(100); // Ensure step progress is 100% for overall calculation
                    currentFullSyncStepIndex++; // Move to next step
                    processNextFullSyncStep();
                }
            }
        }

        // New function to process variations for a single product from the fullSyncVariationQueue
        function processFullSyncVariationBatchLoop() {
            if (isSyncCancelledByUser) { // ADDED: Check cancellation
                logMessage(fullSyncLogDiv, i18n.sync_cancelled_log_message || 'Sync cancelled, stopping variation loop.', 'info');
                currentFullSyncVariationProductData = null; // Clear current product data
                fullSyncVariationQueue = []; // Clear the queue
                return;
            }

            if (!currentFullSyncVariationProductData && fullSyncVariationQueue.length > 0) {
                currentFullSyncVariationProductData = fullSyncVariationQueue.shift(); // Get and remove first item
                logMessage(fullSyncLogDiv, `[INFO] Starting variation processing for ${currentFullSyncVariationProductData.item_name} (Full Sync Queue).`, 'info');
            }

            if (!currentFullSyncVariationProductData) {
                logMessage(fullSyncLogDiv, "[ERROR] currentFullSyncVariationProductData is null in processFullSyncVariationBatchLoop. Attempting to continue.", 'error');
                handleFullSyncContinuation(); // Try to continue with next in queue or parent batch
                return;
            }

            const { wc_product_id, ecwid_product_id, item_name, sku, all_combinations, total_combinations, original_options, current_variation_offset } = currentFullSyncVariationProductData;

            if (current_variation_offset >= total_combinations) {
                logMessage(fullSyncLogDiv, i18n.variations_imported_successfully.replace('{productName}', sanitizeHTML(item_name)), 'success');
                currentFullSyncVariationProductData = null; // Done with this product
                // TODO: Potentially update a sub-progress bar for variations of this product
                handleFullSyncContinuation(); // Move to next in queue or parent batch
                return;
            }

            const combinationsBatch = all_combinations.slice(current_variation_offset, current_variation_offset + variationBatchSize);
            // const currentBatchNumber = Math.floor(current_variation_offset / variationBatchSize) + 1; // Not used for new status
            // const totalBatches = Math.ceil(total_combinations / variationBatchSize); // Not used for new status

            // Use a more descriptive status showing actual variation counts
            const statusMsg = i18n.syncing_item_of_total
                .replace('{syncType}', `Variations for '${sanitizeHTML(item_name)}'`)
                .replace('{current}', current_variation_offset) // Variations processed *before* this batch
                .replace('{total}', total_combinations);
            startBatchStatusAnimation(fullSyncStatusDiv, statusMsg);

            $.ajax({
                url: ajax_url,
                method: 'POST',
                data: {
                    action: 'ecwid_wc_process_variation_batch', // Reuse existing PHP action
                    nonce: nonce,
                    wc_product_id: wc_product_id,
                    ecwid_product_id: ecwid_product_id,
                    item_name: item_name, // item_name is used by PHP for logging
                    sku: sku,
                    combinations_batch_json: JSON.stringify(combinationsBatch),
                    original_ecwid_options_json: JSON.stringify(original_options || [])
                },
                success: function(response) {
                    stopBatchStatusAnimation();
                    (response.data.batch_logs || []).forEach(logEntry => categorizeAndLog(fullSyncLogDiv, logEntry));

                    if (response.success) {
                        currentFullSyncVariationProductData.current_variation_offset += combinationsBatch.length;
                        // Update status to reflect new count after batch completion for the next iteration's display
                        const nextStatusPreview = i18n.syncing_item_of_total
                            .replace('{syncType}', `Variations for '${sanitizeHTML(item_name)}'`)
                            .replace('{current}', currentFullSyncVariationProductData.current_variation_offset)
                            .replace('{total}', total_combinations);
                        updateStatus(fullSyncStatusDiv, `${nextStatusPreview} (Full Sync)`);
                        processFullSyncVariationBatchLoop(); // Process next batch for this product
                    } else {
                        let errorMsg = i18n.error_importing_variations.replace('{productName}', sanitizeHTML(item_name));
                        if (response.data && response.data.message) errorMsg += `: ${sanitizeHTML(response.data.message)}`;
                        logMessage(fullSyncLogDiv, errorMsg + " (Full Sync)", 'error');
                        logMessage(fullSyncLogDiv, `Skipping remaining variations for ${sanitizeHTML(item_name)} (Full Sync) due to error.`, 'warning');
                        currentFullSyncVariationProductData = null; // Stop processing this product's variations
                        handleFullSyncContinuation(); // Move to next
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    stopBatchStatusAnimation();
                    
                    // Enhanced error handling for different HTTP status codes
                    let errorMsg = '';
                    let shouldRetry = false;
                    
                    if (jqXHR.status === 500) {
                        errorMsg = i18n.error_importing_variations.replace('{productName}', sanitizeHTML(item_name));
                        errorMsg += ': Server Error (500) - This may be due to memory limits or processing timeout. ';
                        
                        // Check if this is a retry-able error
                        if (jqXHR.responseJSON && jqXHR.responseJSON.error_type === 'fatal_error') {
                            errorMsg += 'Consider reducing batch sizes or check server error logs.';
                        } else {
                            errorMsg += 'The sync will continue with the next item.';
                            shouldRetry = true;
                        }
                    } else if (jqXHR.status === 0) {
                        errorMsg = 'Network connection error. Please check your internet connection.';
                    } else if (jqXHR.status === 429) {
                        errorMsg = 'API rate limit exceeded. The sync will automatically retry.';
                        shouldRetry = true;
                    } else {
                        errorMsg = i18n.error_importing_variations.replace('{productName}', sanitizeHTML(item_name));
                        errorMsg += `: HTTP ${jqXHR.status} - ${sanitizeHTML(textStatus)} ${sanitizeHTML(errorThrown || '')}`;
                    }
                    
                    logMessage(fullSyncLogDiv, errorMsg + " (Full Sync)", 'error');
                    
                    if (!shouldRetry) {
                        logMessage(fullSyncLogDiv, `Skipping remaining variations for ${sanitizeHTML(item_name)} (Full Sync) due to error.`, 'warning');
                        currentFullSyncVariationProductData = null;
                    }
                    
                    handleFullSyncContinuation(); // Move to next or retry
                }
            });
        }

        // --- Category Sync Page Logic ---

        // New function to load and display categories
        function loadAndDisplayCategories() {
            // Ensure the button and container exist before proceeding
            if (!loadCategoriesButton.length || !categoryListContainer.length) {
                if (loadCategoriesButton.length && !categoryListContainer.length) {
                    console.warn("loadCategoriesButton exists, but categoryListContainer does not. Cannot load categories.");
                }
                if (categorySyncInitialInfoDiv.length) {
                    categorySyncInitialInfoDiv.text('');
                }
                return;
            }

            if (loadCategoriesButton.hasClass('disabled')) return;

            const originalButtonText = loadCategoriesButton.text();
            loadCategoriesButton.addClass('disabled').html('<span class="loading-spinner"></span>' + (i18n.loading_ecwid_categories || 'Loading Categories...'));
            
            // Create enhanced loading interface matching Product Sync
            const loadingHtml = `
                <div class="ecwid-loading-container" style="text-align: center; padding: 40px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 5px; margin: 20px 0;">
                    <div class="ecwid-loading-spinner" style="font-size: 48px; color: #0073aa; margin-bottom: 20px;">
                        <span class="dashicons dashicons-update" style="animation: ecwid-spin 1s linear infinite;"></span>
                    </div>
                    <div class="ecwid-loading-title" style="font-size: 18px; font-weight: bold; color: #23282d; margin-bottom: 10px;">
                        📁 Loading Categories from Ecwid
                    </div>
                    <div class="ecwid-loading-status" style="font-size: 14px; color: #666; margin-bottom: 15px;">
                        Making API calls to fetch all categories...
                    </div>
                    <div class="ecwid-loading-progress" style="font-size: 12px; color: #999;">
                        This may take a moment for large stores (700+ categories require ~8 API calls)
                    </div>
                </div>
                <style>
                    @keyframes ecwid-spin {
                        from { transform: rotate(0deg); }
                        to { transform: rotate(360deg); }
                    }
                </style>
            `;
            
            categoryListContainer.html(loadingHtml).show();
            if (categorySyncInitialInfoDiv.length) {
                categorySyncInitialInfoDiv.html(`
                    <div style="padding: 15px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px; margin: 10px 0;">
                        <strong>⏳ Loading Categories...</strong><br>
                        <span style="font-size: 12px; color: #856404;">Fetching complete category catalog from Ecwid API</span>
                    </div>
                `);
            }
            importSelectedCategoriesButton.hide();
            ecwidCategoriesForSelection = [];
            totalCategoriesForCategoryPageSync = 0;
            
            // Clear any existing pagination and reset pagination state
            const categoryPaginationContainer = $('#category-pagination-container');
            if (categoryPaginationContainer.length) {
                categoryPaginationContainer.html('');
            }
            currentCategoryPage = 1;
            selectedCategoryIds.clear(); // Reset category selection across all pages

            $.ajax({
                url: ajax_url,
                method: 'POST',
                data: {
                    action: 'ecwid_wc_fetch_categories_for_display',
                    nonce: nonce
                },
                success: function(response) {
                    if (response.success && response.data.categories) {
                        ecwidCategoriesForSelection = response.data.categories;
                        const totalFound = parseInt(response.data.total_found) || 0;
                        const apiCalls = parseInt(response.data.api_calls_made) || 1;
                        const totalAvailable = parseInt(response.data.total_available) || totalFound;
                        totalCategoriesForCategoryPageSync = totalFound;

                        // Enhanced status message with debugging info
                        let statusMessage = `All ${totalFound} categories loaded`;
                        if (apiCalls > 1) {
                            statusMessage += ` (${apiCalls} API calls)`;
                        }
                        if (totalAvailable && totalAvailable !== totalFound) {
                            statusMessage += ` out of ${totalAvailable} available`;
                        }
                        statusMessage += '. Select individual categories or multiple categories to import.';

                        if (categorySyncInitialInfoDiv.length) {
                            // Create styled info box matching the Product Sync page
                            const styledStatusHtml = `
                                <div style="background: #f9f9f9; padding: 10px; margin-bottom: 10px; border: 1px solid #ddd; border-radius: 4px;">
                                    <p style="margin: 0 0 5px 0; font-weight: bold;">📁 Category Loading Complete:</p>
                                    <p style="margin: 0; font-size: 12px; color: #666; font-style: normal;">${statusMessage}</p>
                                </div>
                            `;
                            categorySyncInitialInfoDiv.html(styledStatusHtml);
                        }

                        // Category loading complete
                        if (window.ecwidDebugMode) {
                            console.log('Category loading debug:', {
                                categoriesReceived: totalFound,
                                apiCallsMade: apiCalls,
                                totalAvailable: totalAvailable,
                                actualArrayLength: ecwidCategoriesForSelection.length
                            });
                        }

                        renderCategorySelectionList(ecwidCategoriesForSelection);
                        if (ecwidCategoriesForSelection.length > 0) {
                            importSelectedCategoriesButton.show();
                        } else {
                            categoryListContainer.html('<p>' + i18n.no_categories_found + '</p>');
                            if (categorySyncInitialInfoDiv.length && totalFound === 0) {
                                categorySyncInitialInfoDiv.text(i18n.no_categories_found);
                            }
                        }
                    } else {
                        const errorMsg = response.data && response.data.message ? response.data.message : i18n.no_categories_found;
                        categoryListContainer.html('<p style="color:red;">' + sanitizeHTML(errorMsg) + '</p>');
                        if (categorySyncInitialInfoDiv.length) {
                            categorySyncInitialInfoDiv.html('<span style="color:red;">' + sanitizeHTML(errorMsg) + '</span>');
                        }
                        // Clear pagination on error
                        const categoryPaginationContainer = $('#category-pagination-container');
                        if (categoryPaginationContainer.length) {
                            categoryPaginationContainer.html('');
                        }
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    const errorText = 'AJAX Error: ' + sanitizeHTML(textStatus) + (errorThrown ? ' - ' + sanitizeHTML(errorThrown) : '');
                    categoryListContainer.html('<p style="color:red;">' + errorText + '</p>');
                    if (categorySyncInitialInfoDiv.length) {
                         categorySyncInitialInfoDiv.html('<span style="color:red;">' + errorText + '</span>');
                    }
                    // Clear pagination on error
                    const categoryPaginationContainer = $('#category-pagination-container');
                    if (categoryPaginationContainer.length) {
                        categoryPaginationContainer.html('');
                    }
                },
                complete: function() {
                    loadCategoriesButton.removeClass('disabled').html(originalButtonText);
                }
            });
        }

        if (loadCategoriesButton.length) {
            // Automatically load categories if the button exists on page load
            loadAndDisplayCategories();

            loadCategoriesButton.on('click', function(e) {
                e.preventDefault();
                // The disabled check is now inside loadAndDisplayCategories
                loadAndDisplayCategories(); // Call the main function
            });
        }

        // Initialize category selection controls
        function initializeCategorySelectionControls() {
            // Select All/None functionality
            $('#select-all-categories-checkbox').off('change').on('change', function() {
                const isChecked = $(this).is(':checked');
                $('.ecwid-category-select').prop('checked', isChecked);
                updateSelectedCategoriesCount();
            });

            // Individual checkbox changes
            $(document).off('change', '.ecwid-category-select').on('change', '.ecwid-category-select', function() {
                updateSelectedCategoriesCount();
                
                // Update select all checkbox state
                const totalCheckboxes = $('.ecwid-category-select').length;
                const checkedCheckboxes = $('.ecwid-category-select:checked').length;
                const selectAllCheckbox = $('#select-all-categories-checkbox');
                
                if (checkedCheckboxes === 0) {
                    selectAllCheckbox.prop('indeterminate', false).prop('checked', false);
                } else if (checkedCheckboxes === totalCheckboxes) {
                    selectAllCheckbox.prop('indeterminate', false).prop('checked', true);
                } else {
                    selectAllCheckbox.prop('indeterminate', true).prop('checked', false);
                }
            });

            // Initial count update
            updateSelectedCategoriesCount();
        }

        function updateSelectedCategoriesCount() {
            const selectedCount = $('.ecwid-category-select:checked').length;
            const totalCount = $('.ecwid-category-select').length;
            const importButton = $('#import-selected-categories-button');
            
            if (selectedCount > 0) {
                importButton.text(`${i18n.import_selected_categories || 'Import Selected Categories'} (${selectedCount})`);
                importButton.prop('disabled', false).removeClass('disabled');
            } else {
                importButton.text(i18n.import_selected_categories || 'Import Selected Categories');
                importButton.prop('disabled', true).addClass('disabled');
            }
        }

        // Handle select all/none button click
        if ($('#select-all-categories-button').length) {
            $('#select-all-categories-button').on('click', function(e) {
                e.preventDefault();
                const checkboxes = $('.ecwid-category-select');
                const checkedCount = checkboxes.filter(':checked').length;
                const shouldCheck = checkedCount === 0;
                
                checkboxes.prop('checked', shouldCheck);
                $('#select-all-categories-checkbox').prop('checked', shouldCheck).prop('indeterminate', false);
                updateSelectedCategoriesCount();
            });
        }

        // Handle import selected categories button click
        if ($('#import-selected-categories-button').length) {
            $('#import-selected-categories-button').on('click', function(e) {
                e.preventDefault();
                if ($(this).hasClass('disabled')) return;

                const selectedCategoryIds = [];
                $('.ecwid-category-select:checked').each(function() {
                    selectedCategoryIds.push(parseInt($(this).val()));
                });

                if (window.ecwidDebugMode) {
                    console.log('DEBUG: Selected category IDs:', selectedCategoryIds);
                    console.log('DEBUG: Number of selected categories:', selectedCategoryIds.length);
                }

                if (selectedCategoryIds.length === 0) {
                    alert(i18n.no_categories_selected || 'No categories selected for import.');
                    return;
                }

                // Disable buttons during import
                const importButton = $(this);
                const originalText = importButton.text();
                importButton.addClass('disabled').html('<span class="loading-spinner"></span>' + (i18n.importing_selected_categories || 'Importing Selected Categories...'));
                loadCategoriesButton.addClass('disabled').prop('disabled', true);
                categoryPageSyncButton.addClass('disabled').prop('disabled', true);

                // Show enhanced activity display for selective import
                categorySyncActivity.show();
                categoryPageSyncLogDiv.html('');
                updateProgressBar(categoryPageSyncProgressBar, 0);
                categoryPageSyncProgressBarContainer.show();
                
                // Update activity indicators
                categoryCurrentBatchInfo.html(`Preparing to import ${selectedCategoryIds.length} selected categories...`);
                categoryProcessingText.text('Starting selective import...');
                
                // Initialize stats for selective import
                resetCategorySyncStats();
                updateCategorySyncStats(0, 0, 0, selectedCategoryIds.length);
                
                updateStatus(categoryPageSyncStatusDiv, `Importing ${selectedCategoryIds.length} selected categories...`);

                // Start import
                importSelectedCategories(selectedCategoryIds, importButton, originalText);
            });
        }

        function importSelectedCategories(categoryIds, importButton, originalButtonText) {
            // Use the same UI elements as Product Sync for consistency
            selectiveSyncStatusDiv.empty();
            selectiveSyncLogDiv.empty();
            selectiveSyncProgressBarContainer.show();
            updateProgressBar(selectiveSyncProgressBar, 0);
            updateStatus(selectiveSyncStatusDiv, 'Starting category import...');
            
            // Check if we need to use batching for large imports
            const batchSize = 50; // Process 50 categories at a time
            const useBatching = categoryIds.length > batchSize;
            
            if (useBatching) {
                importCategoriesInBatches(categoryIds, importButton, originalButtonText, batchSize);
                return;
            }
            
            // For smaller imports, process all at once
            processCategoryBatch(categoryIds, importButton, originalButtonText, 1, 1);
        }
        
        function importCategoriesInBatches(categoryIds, importButton, originalButtonText, batchSize) {
            const totalBatches = Math.ceil(categoryIds.length / batchSize);
            let currentBatch = 1;
            let allResults = {
                imported_count: 0,
                updated_count: 0,
                skipped_count: 0,
                failed_count: 0,
                logs: []
            };
            
            // Update activity info for batched import
            updateStatus(selectiveSyncStatusDiv, `Processing ${categoryIds.length} categories in ${totalBatches} batches...`);
            
            function processBatch(batchNumber) {
                const startIndex = (batchNumber - 1) * batchSize;
                const endIndex = Math.min(startIndex + batchSize, categoryIds.length);
                const batchIds = categoryIds.slice(startIndex, endIndex);
                
                // Update activity display
                updateStatus(selectiveSyncStatusDiv, `Processing batch ${batchNumber} of ${totalBatches} (${batchIds.length} categories)...`);
                
                processCategoryBatch(batchIds, importButton, originalButtonText, batchNumber, totalBatches, function(batchResults) {
                    // Accumulate results
                    allResults.imported_count += batchResults.imported_count || 0;
                    allResults.updated_count += batchResults.updated_count || 0;
                    allResults.skipped_count += batchResults.skipped_count || 0;
                    allResults.failed_count += batchResults.failed_count || 0;
                    if (batchResults.logs) {
                        allResults.logs = allResults.logs.concat(batchResults.logs);
                    }
                    
                    // Update progress
                    const overallProgress = (batchNumber / totalBatches) * 100;
                    updateProgressBar(selectiveSyncProgressBar, overallProgress);
                    
                    if (batchNumber < totalBatches) {
                        // Process next batch
                        setTimeout(() => processBatch(batchNumber + 1), 500); // Small delay between batches
                    } else {
                        // All batches completed
                        updateProgressBar(selectiveSyncProgressBar, 100);
                        updateStatus(selectiveSyncStatusDiv, 'All categories imported successfully!');
                        
                        const finalMessage = `Batch import completed. Imported: ${allResults.imported_count}, Updated: ${allResults.updated_count}, Skipped: ${allResults.skipped_count}, Failed: ${allResults.failed_count}`;
                        logMessage(selectiveSyncLogDiv, finalMessage, 'success');
                        
                        // Display all accumulated logs
                        if (allResults.logs && allResults.logs.length > 0) {
                            allResults.logs.forEach(function(logEntry) {
                                logMessage(selectiveSyncLogDiv, logEntry, 'info');
                            });
                        }
                        
                        // Re-enable buttons
                        importButton.removeClass('disabled').html(originalButtonText);
                        loadCategoriesButton.removeClass('disabled').prop('disabled', false);
                        updateSelectedCategoriesCount();
                    }
                });
            }
            
            // Start processing batches
            processBatch(1);
        }
        
        function processCategoryBatch(categoryIds, importButton, originalButtonText, batchNumber, totalBatches, callback) {
            // Update activity info
            if (totalBatches === 1) {
                updateStatus(selectiveSyncStatusDiv, 'Processing categories...');
            }
            
            logMessage(selectiveSyncLogDiv, `Starting import of ${categoryIds.length} categories${totalBatches > 1 ? ` (batch ${batchNumber}/${totalBatches})` : ''}...`, 'info');
            
            // Update progress bar to show processing
            if (totalBatches === 1) {
                updateProgressBar(selectiveSyncProgressBar, 10);
                updateStatus(selectiveSyncStatusDiv, 'Processing categories...');
            }
            
            $.ajax({
                url: ajax_url,
                method: 'POST',
                timeout: 300000, // 5 minutes timeout
                data: {
                    action: 'ecwid_wc_import_selected_categories',
                    nonce: nonce,
                    category_ids: categoryIds
                },
                beforeSend: function() {
                    if (totalBatches === 1) {
                        updateProgressBar(selectiveSyncProgressBar, 25);
                        updateStatus(selectiveSyncStatusDiv, 'Importing categories from Ecwid...');
                    }
                },
                success: function(response) {
                    if (window.ecwidDebugMode) {
                        console.log('DEBUG: AJAX response received:', response);
                    }
                    
                    if (response.success) {
                        const data = response.data;
                        
                        if (totalBatches === 1) {
                            updateProgressBar(selectiveSyncProgressBar, 100);
                            updateStatus(selectiveSyncStatusDiv, i18n.categories_import_complete || 'Selected categories import complete!');
                        }
                        
                        // Extract counts with proper fallbacks
                        const importedCount = parseInt(data.imported_count) || 0;
                        const updatedCount = parseInt(data.updated_count) || 0;
                        const skippedCount = parseInt(data.skipped_count) || 0;
                        const failedCount = parseInt(data.failed_count) || 0;
                        const totalProcessed = importedCount + updatedCount + skippedCount + failedCount;
                        
                        logMessage(selectiveSyncLogDiv, data.message, 'success');
                        
                        if (totalBatches === 1) {
                            // Display detailed logs
                            if (data.logs && data.logs.length > 0) {
                                data.logs.forEach(function(logEntry) {
                                    logMessage(selectiveSyncLogDiv, logEntry, 'info');
                                });
                            }
                        }
                        
                        // Call callback for batching system (return the raw data)
                        if (callback) {
                            callback({
                                imported_count: importedCount,
                                updated_count: updatedCount,
                                skipped_count: skippedCount,
                                failed_count: failedCount,
                                logs: data.logs
                            });
                        }
                    } else {
                        const errorMsg = response.data && response.data.message ? response.data.message : i18n.ajax_error;
                        
                        if (totalBatches === 1) {
                            updateStatus(selectiveSyncStatusDiv, 'Import failed');
                            logMessage(selectiveSyncLogDiv, `Error: ${errorMsg}`, 'error');
                        }
                        
                        if (callback) {
                            callback({ failed_count: categoryIds.length, logs: [`Error: ${errorMsg}`] });
                        }
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    const errorMsg = `${i18n.ajax_error}: ${textStatus} ${errorThrown || ''}`;
                    
                    if (totalBatches === 1) {
                        updateStatus(selectiveSyncStatusDiv, 'Import failed');
                        logMessage(selectiveSyncLogDiv, `Error: ${errorMsg}`, 'error');
                    }
                    
                    if (callback) {
                        callback({ failed_count: categoryIds.length, logs: [`Error: ${errorMsg}`] });
                    }
                },
                complete: function() {
                    if (totalBatches === 1) {
                        // Re-enable buttons for single batch
                        importButton.removeClass('disabled').html(originalButtonText);
                        loadCategoriesButton.removeClass('disabled').prop('disabled', false);
                        updateSelectedCategoriesCount();
                    }
                }
            });
        }

        if (categoryPageSyncButton.length) {
            categoryPageSyncButton.on('click', function(e) {
                e.preventDefault();
                if ($(this).hasClass('disabled')) return;

                if (totalCategoriesForCategoryPageSync === 0 && categoryListContainer.find('ul li').length === 0) {
                    logMessage(categoryPageSyncLogDiv, "Warning: Category list not loaded or appears empty. Totals in status might show as N/A. Consider loading categories first.", 'warning');
                }

                $(this).addClass('disabled').html('<span class="loading-spinner"></span>' + (i18n.syncing_categories_page_button || 'Syncing Categories...'));
                loadCategoriesButton.addClass('disabled').prop('disabled', true); 
                fixHierarchyButton.add('disabled').prop('disabled', true);
                
                // Show enhanced activity display
                categorySyncActivity.show();
                categoryPageSyncLogDiv.html('');
                updateProgressBar(categoryPageSyncProgressBar, 0);
                categoryPageSyncProgressBarContainer.show();
                
                // Reset and initialize stats
                resetCategorySyncStats();
                updateCategorySyncStats(0, 0, 0, totalCategoriesForCategoryPageSync);
                
                const initialStatus = i18n.syncing_item_of_total
                    .replace('{syncType}', 'Categories')
                    .replace('{current}', 0)
                    .replace('{total}', totalCategoriesForCategoryPageSync > 0 ? totalCategoriesForCategoryPageSync : 'N/A');
                updateStatus(categoryPageSyncStatusDiv, initialStatus);
                logMessage(categoryPageSyncLogDiv, i18n.sync_starting, 'info');
                
                processCategoryPageSyncBatch('categories', 0, totalCategoriesForCategoryPageSync); 
            });
        }

        // Enhanced category sync stats display with cumulative tracking
        let categorySyncCumulativeStats = { processed: 0, created: 0, updated: 0, errors: 0 };
        
        function updateCategorySyncStats(processed = null, created = null, updated = null, total = null) {
            // Use provided values or current cumulative stats
            const displayProcessed = processed !== null ? processed : categorySyncCumulativeStats.processed;
            const displayCreated = created !== null ? created : categorySyncCumulativeStats.created;
            const displayUpdated = updated !== null ? updated : categorySyncCumulativeStats.updated;
            const displayErrors = categorySyncCumulativeStats.errors;
            
            const statsHtml = `
                <div class="stat-item">
                    <div class="stat-value">${displayProcessed}</div>
                    <div class="stat-label">Processed</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">${displayCreated}</div>
                    <div class="stat-label">Created</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">${displayUpdated}</div>
                    <div class="stat-label">Updated</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">${displayErrors}</div>
                    <div class="stat-label">Errors</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">${total !== null && total > 0 ? total : 'N/A'}</div>
                    <div class="stat-label">Total</div>
                </div>
            `;
            categorySyncStats.html(statsHtml);
        }
        
        function resetCategorySyncStats() {
            categorySyncCumulativeStats = { processed: 0, created: 0, updated: 0, errors: 0 };
        }

        function processCategoryPageSyncBatch(syncType, offset, totalKnownCategories) {
            let baseStatusForAnimation = i18n.syncing_just_categories_page_status;
            
            // Update batch info
            const batchNumber = Math.floor(offset / 50) + 1; // Assuming 50 categories per batch
            categoryCurrentBatchInfo.html(`Processing batch ${batchNumber} starting from category ${offset + 1}...`);
            categoryProcessingText.text('Fetching categories from Ecwid...');
            
            if (offset === 0) { // Initial status already set by button click handler
                // For animation, use a generic base if numbers are involved or N/A
                 baseStatusForAnimation = i18n.syncing_just_categories_page_status;
            } else {
                // For subsequent calls, update status before animation
                const currentStatusUpdate = i18n.syncing_item_of_total
                    .replace('{syncType}', 'Categories')
                    .replace('{current}', offset) // Show current offset
                    .replace('{total}', totalKnownCategories > 0 ? totalKnownCategories : 'N/A');
                updateStatus(categoryPageSyncStatusDiv, currentStatusUpdate);
                // Use a generic base for animation if numbers are involved
                baseStatusForAnimation = i18n.syncing_just_categories_page_status;
            }
            startBatchStatusAnimation(categoryPageSyncStatusDiv, baseStatusForAnimation);

            $.ajax({
                url: ajax_url,
                method: 'POST',
                data: {
                    action: 'ecwid_wc_batch_sync', 
                    nonce: nonce,
                    sync_type: syncType, 
                    offset: offset
                },
                success: function(response) {
                    stopBatchStatusAnimation();
                    categoryProcessingText.text('Processing categories...');
                    
                    if (response.success) {
                        // Use actual counts from the response
                        const batchImportedCount = parseInt(response.data.imported_count) || 0;
                        const batchUpdatedCount = parseInt(response.data.updated_count) || 0;
                        const batchSkippedCount = parseInt(response.data.skipped_count) || 0;
                        const batchFailedCount = parseInt(response.data.failed_count) || 0;
                        const batchProcessedCount = batchImportedCount + batchUpdatedCount + batchSkippedCount + batchFailedCount;
                        
                        // Log the batch results
                        (response.data.batch_logs || []).forEach(logEntry => {
                            categorizeAndLog(categoryPageSyncLogDiv, logEntry);
                        });
                        
                        // Update cumulative stats with actual counts
                        categorySyncCumulativeStats.created += batchImportedCount;
                        categorySyncCumulativeStats.updated += batchUpdatedCount + batchSkippedCount; // Combine updated and skipped for display
                        categorySyncCumulativeStats.errors += batchFailedCount;
                        categorySyncCumulativeStats.processed = response.data.next_offset || offset + batchProcessedCount;
                        
                        // Calculate progress based on items processed so far
                        let currentProgress = 0;
                        const itemsProcessed = categorySyncCumulativeStats.processed;
                        const totalForCalc = totalKnownCategories > 0 ? totalKnownCategories : 0;

                        if (totalForCalc > 0) {
                            currentProgress = (itemsProcessed / totalForCalc) * 100;
                        } else if (response.data.has_more === false) { 
                            currentProgress = 100; // If no total known and no more items, assume 100%
                        } else {
                            // If we don't know total, show incremental progress
                            currentProgress = Math.min(95, (itemsProcessed / 100) * 100); // Cap at 95% until done
                        }
                        
                        currentProgress = Math.min(100, Math.max(0, currentProgress));
                        updateProgressBar(categoryPageSyncProgressBar, currentProgress);
                        
                        // Update stats display with cumulative counts
                        updateCategorySyncStats(
                            categorySyncCumulativeStats.processed, 
                            categorySyncCumulativeStats.created, 
                            categorySyncCumulativeStats.updated, 
                            totalForCalc
                        );
                        
                        const statusUpdate = i18n.syncing_item_of_total
                            .replace('{syncType}', 'Categories')
                            .replace('{current}', categorySyncCumulativeStats.processed)
                            .replace('{total}', totalForCalc > 0 ? totalForCalc : 'N/A');
                        updateStatus(categoryPageSyncStatusDiv, statusUpdate);

                        if (response.data.has_more) {
                            processCategoryPageSyncBatch(syncType, response.data.next_offset, totalKnownCategories); 
                        } else {
                            // Sync complete
                            categoryProcessingText.text('Sync completed!');
                            categoryCurrentBatchInfo.html('✅ All categories have been processed successfully.');
                            updateStatus(categoryPageSyncStatusDiv, i18n.category_sync_page_complete);
                            logMessage(categoryPageSyncLogDiv, i18n.category_sync_page_complete, 'success');
                            categoryPageSyncButton.removeClass('disabled').html(i18n.start_category_sync_page || 'Sync All Categories');
                            loadCategoriesButton.removeClass('disabled').prop('disabled', false);
                            fixHierarchyButton.removeClass('disabled').prop('disabled', false);
                            updateProgressBar(categoryPageSyncProgressBar, 100);
                            
                            // Hide sync activity display after completion
                            setTimeout(function() {
                                categorySyncActivity.hide();
                            }, 3000);
                        }
                    } else {
                        categoryProcessingText.text('Error occurred during sync');
                        handleAjaxError(categoryPageSyncStatusDiv, categoryPageSyncLogDiv, categoryPageSyncButton, i18n.start_category_sync_page, syncType, response.data);
                        loadCategoriesButton.removeClass('disabled').prop('disabled', false);
                        fixHierarchyButton.removeClass('disabled').prop('disabled', false);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    stopBatchStatusAnimation();
                    categoryProcessingText.text('Network error occurred');
                    handleAjaxError(categoryPageSyncStatusDiv, categoryPageSyncLogDiv, categoryPageSyncButton, i18n.start_category_sync_page, syncType, { message: `${textStatus} ${errorThrown || ''}` }, true);
                    loadCategoriesButton.removeClass('disabled').prop('disabled', false);
                    fixHierarchyButton.removeClass('disabled').prop('disabled', false);
                }
            });
        }


        // --- Selective Product Sync Logic ---

        // New function to load and display all products for selection
        function loadAndDisplayProductsForSelection() {
            // Ensure the button and container exist before proceeding
            if (!loadProductsButton.length || !productListContainer.length) {
                if (loadProductsButton.length && !productListContainer.length) {
                    console.warn("loadProductsButton exists, but productListContainer does not. Cannot load products for selection.");
                }
                if (selectiveSyncInitialInfoDiv.length) {
                    selectiveSyncInitialInfoDiv.text('');
                }
                return;
            }

            if (loadProductsButton.hasClass('disabled')) return;

            const originalButtonText = loadProductsButton.text();
            loadProductsButton.addClass('disabled').text(i18n.loading_products);
            
            // Clear any existing pagination before showing loading
            const paginationContainer = $('#product-pagination-container');
            if (paginationContainer.length) {
                paginationContainer.html('');
            }
            
            // Create enhanced loading interface
            const loadingHtml = `
                <div class="ecwid-loading-container" style="text-align: center; padding: 40px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 5px; margin: 20px 0;">
                    <div class="ecwid-loading-spinner" style="font-size: 48px; color: #0073aa; margin-bottom: 20px;">
                        <span class="dashicons dashicons-update" style="animation: ecwid-spin 1s linear infinite;"></span>
                    </div>
                    <div class="ecwid-loading-title" style="font-size: 18px; font-weight: bold; color: #23282d; margin-bottom: 10px;">
                        🔄 Loading Products from Ecwid
                    </div>
                    <div class="ecwid-loading-status" style="font-size: 14px; color: #666; margin-bottom: 15px;">
                        Making API calls to fetch all products...
                    </div>
                    <div class="ecwid-loading-progress" style="font-size: 12px; color: #999;">
                        This may take a moment for large stores (6000+ products require ~70 API calls)
                    </div>
                </div>
                <style>
                    @keyframes ecwid-spin {
                        from { transform: rotate(0deg); }
                        to { transform: rotate(360deg); }
                    }
                </style>
            `;
            
            productListContainer.html(loadingHtml).show();
            if (selectiveSyncInitialInfoDiv.length) {
                selectiveSyncInitialInfoDiv.html(`
                    <div style="padding: 15px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px; margin: 10px 0;">
                        <strong>⏳ Loading Products...</strong><br>
                        <span style="font-size: 12px; color: #856404;">Fetching complete product catalog from Ecwid API</span>
                    </div>
                `);
            }
            importSelectedButton.hide();
            ecwidProductsForSelection = [];

            $.ajax({
                url: ajax_url,
                method: 'POST',
                cache: false,
                data: { 
                    action: 'ecwid_wc_fetch_products_for_selection', 
                    nonce: nonce,
                    cache_bust: Date.now()
                },
                success: function(response) {
                    // Show completion animation
                    $('.ecwid-loading-spinner .dashicons').removeClass('dashicons-update').addClass('dashicons-yes-alt');
                    $('.ecwid-loading-title').html('✅ Products Loaded Successfully!');
                    $('.ecwid-loading-status').html('Processing product data...');
                    
                    if (response.success && response.data.products) {
                        ecwidProductsForSelection = response.data.products;
                        const totalFound = parseInt(response.data.total_found) || 0;
                        const apiCalls = parseInt(response.data.api_calls_made) || 1;
                        const totalAvailable = parseInt(response.data.total_available) || totalFound;
                        
                        // Get enabled and disabled products from response
                        const enabledProducts = response.data.enabled_products || [];
                        const disabledProducts = response.data.disabled_products || [];
                        const enabledCount = parseInt(response.data.enabled_count) || 0;
                        const disabledCount = parseInt(response.data.disabled_count) || 0;

                        // Console logging for debugging
                        if (window.ecwidDebugMode) {
                            console.log('=== PRODUCT LOADING DEBUG ===');
                            console.log('Total products found:', totalFound);
                            console.log('Enabled products:', enabledCount);
                            console.log('Disabled products:', disabledCount);
                            console.log('API calls made:', apiCalls);
                            console.log('Total available in Ecwid:', totalAvailable);
                            console.log('Actual array length:', ecwidProductsForSelection.length);
                            console.log('Response data:', response.data);
                        }
                        
                        // Show server debug info if available
                        if (response.data.debug_info && window.ecwidDebugMode) {
                            console.log('🔧 Server Debug Info:', response.data.debug_info);
                        }
                        
                        // Show raw API response if available  
                        if (response.data.raw_first_response && response.data.raw_first_response !== 'N/A') {
                            console.log('📄 Raw First API Response:', response.data.raw_first_response);
                        }
                        
                        console.log('==============================');

                        // Enhanced status message with enabled/disabled breakdown
                        let statusMessage = `Loaded ${enabledCount} enabled products`;
                        if (disabledCount > 0) {
                            statusMessage += ` and ${disabledCount} disabled products`;
                        }
                        if (apiCalls > 1) {
                            statusMessage += ` (${apiCalls} API calls)`;
                        }
                        statusMessage += '. Select individual products to import.';

                        if (selectiveSyncInitialInfoDiv.length) {
                            // Create clean status message with enabled/disabled button
                            const disabledButtonStyle = disabledCount > 0 ? '' : 'display: none;';
                            const styledStatusHtml = `
                                <p style="margin: 0 0 5px 0; font-weight: bold;">🎯 Product Loading Complete:</p>
                                <p style="margin: 0 0 10px 0; font-size: 12px; color: #666; font-style: normal;">${statusMessage}</p>
                                <div style="margin: 10px 0;">
                                    <button id="show-enabled-products" class="button button-primary" style="margin-right: 10px;">📦 Enabled Products (${enabledCount})</button>
                                    <button id="show-disabled-products" class="button button-secondary" style="${disabledButtonStyle}">❌ Disabled Products (${disabledCount})</button>
                                </div>
                                <details style="margin-top: 10px; font-size: 11px; color: #999;">
                                    <summary style="cursor: pointer;">🔍 Debug Info (click to expand)</summary>
                                    <div style="margin-top: 5px; padding: 5px; background: #f9f9f9; border-radius: 3px;">
                                        <strong>Total Found:</strong> ${totalFound}<br>
                                        <strong>Enabled:</strong> ${enabledCount}<br>
                                        <strong>Disabled:</strong> ${disabledCount}<br>
                                        <strong>API Calls:</strong> ${apiCalls}<br>
                                        <strong>Available in Store:</strong> ${totalAvailable}<br>
                                        <strong>Array Length:</strong> ${ecwidProductsForSelection.length}
                                    </div>
                                </details>
                            `;
                            selectiveSyncInitialInfoDiv.html(styledStatusHtml);
                        }

                        // Store the product arrays for later use
                        window.ecwidEnabledProducts = enabledProducts;
                        window.ecwidDisabledProducts = disabledProducts;

                        // Reset pagination and selection state
                        currentProductPage = 1;
                        selectedProductIds.clear();

                        // Show enabled products by default
                        renderProductSelectionList(enabledProducts);
                        
                        // Show final success message with fade out
                        $('.ecwid-loading-status').html(`✅ ${totalFound} products ready for sync!`);
                        setTimeout(function() {
                            $('.ecwid-loading-container').fadeOut(800);
                        }, 2000);
                        
                        // Set up button click handlers for switching between enabled/disabled
                        $('#show-enabled-products').click(function() {
                            $(this).removeClass('button-secondary').addClass('button-primary');
                            $('#show-disabled-products').removeClass('button-primary').addClass('button-secondary');
                            currentProductPage = 1; // Reset to first page
                            selectedProductIds.clear(); // Clear selections when switching
                            renderProductSelectionList(enabledProducts);
                            if (enabledProducts.length > 0) {
                                importSelectedButton.show();
                            }
                        });
                        
                        $('#show-disabled-products').click(function() {
                            $(this).removeClass('button-secondary').addClass('button-primary');
                            $('#show-enabled-products').removeClass('button-primary').addClass('button-secondary');
                            currentProductPage = 1; // Reset to first page
                            selectedProductIds.clear(); // Clear selections when switching
                            renderProductSelectionList(disabledProducts);
                            if (disabledProducts.length > 0) {
                                importSelectedButton.show();
                            }
                        });

                        if (enabledProducts.length > 0) {
                            importSelectedButton.show();
                        } else if (disabledProducts.length > 0) {
                            // If no enabled products but there are disabled ones, show message
                            productListContainer.html('<p style="color: orange;">No enabled products found. Click "Disabled Products" to view disabled products.</p>');
                            importSelectedButton.hide();
                        } else {
                            productListContainer.html('<p>' + i18n.no_products_found + '</p>');
                            if (selectiveSyncInitialInfoDiv.length && totalFound === 0) {
                                selectiveSyncInitialInfoDiv.text(i18n.no_products_found);
                            }
                        }
                    } else {
                        $('.ecwid-loading-spinner .dashicons').removeClass('dashicons-update').addClass('dashicons-no-alt');
                        $('.ecwid-loading-title').html('❌ Loading Failed');
                        $('.ecwid-loading-status').html('Error: ' + (response.data && response.data.message ? response.data.message : 'Unknown error occurred'));
                        
                        // Clear pagination on error
                        const paginationContainer = $('#product-pagination-container');
                        if (paginationContainer.length) {
                            paginationContainer.html('');
                        }
                        
                        console.log('=== PRODUCT LOADING ERROR ===');
                        console.log('Response success:', response.success);
                        console.log('Response data:', response.data);
                        console.log('==============================');
                        
                        const errorMsg = response.data && response.data.message ? response.data.message : i18n.no_products_found;
                        productListContainer.html('<p style="color:red;">' + sanitizeHTML(errorMsg) + '</p>');
                        if (selectiveSyncInitialInfoDiv.length) {
                            selectiveSyncInitialInfoDiv.html('<span style="color:red;">' + sanitizeHTML(errorMsg) + '</span>');
                        }
                        
                        // Auto-hide error after 5 seconds
                        setTimeout(function() {
                            $('.ecwid-loading-container').fadeOut(800);
                        }, 5000);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    $('.ecwid-loading-spinner .dashicons').removeClass('dashicons-update').addClass('dashicons-no-alt');
                    $('.ecwid-loading-title').html('❌ Connection Error');
                    $('.ecwid-loading-status').html('Failed to connect to Ecwid API');
                    
                    // Clear pagination on error
                    const paginationContainer = $('#product-pagination-container');
                    if (paginationContainer.length) {
                        paginationContainer.html('');
                    }
                    
                    console.log('=== AJAX ERROR ===');
                    console.log('Status:', textStatus);
                    console.log('Error:', errorThrown);
                    console.log('Response:', jqXHR.responseText);
                    console.log('==================');
                    
                    const errorText = 'AJAX Error: ' + sanitizeHTML(textStatus) + (errorThrown ? ' - ' + sanitizeHTML(errorThrown) : '');
                    productListContainer.html('<p style="color:red;">' + errorText + '</p>');
                    if (selectiveSyncInitialInfoDiv.length) {
                         selectiveSyncInitialInfoDiv.html('<span style="color:red;">' + errorText + '</span>');
                    }
                    
                    // Auto-hide error after 5 seconds
                    setTimeout(function() {
                        $('.ecwid-loading-container').fadeOut(800);
                    }, 5000);
                },
                complete: function() {
                    loadProductsButton.removeClass('disabled').text(originalButtonText);
                }
            });
        }

        if (loadProductsButton.length) {
            // Automatically load all products when page loads
            loadAndDisplayProductsForSelection();

            loadProductsButton.on('click', function(e) {
                e.preventDefault();
                loadAndDisplayProductsForSelection();
            });
        }

        function renderProductSelectionList(products, page = 1) {
            // Store the full product list for pagination
            currentlyDisplayedProducts = products;
            currentProductPage = page;
            
            // Calculate pagination
            const totalProducts = products.length;
            const totalPages = Math.ceil(totalProducts / productsPerPage);
            const startIndex = (page - 1) * productsPerPage;
            const endIndex = Math.min(startIndex + productsPerPage, totalProducts);
            const productsForPage = products.slice(startIndex, endIndex);
            
            // Determine if we're showing enabled or disabled products
            const isEnabledList = totalProducts > 0 && products[0].enabled !== false;
            const listType = isEnabledList ? 'Enabled' : 'Disabled';
            const listIcon = isEnabledList ? '📦' : '❌';
            const headerColor = isEnabledList ? '#0073aa' : '#d63638';
            const headerBg = isEnabledList ? '#f0f8ff' : '#fef2f2';
            
            // First, render pagination controls in the separate container above the product list
            const paginationContainer = $('#product-pagination-container');
            if (totalPages > 1) {
                const paginationHtml = `
                    <div class="product-pagination" style="text-align: center; margin-bottom: 15px; padding: 10px; background: #fff; border: 1px solid #ddd; border-radius: 4px;">
                        <button id="prev-page-btn" class="button button-secondary" ${page <= 1 ? 'disabled' : ''} style="margin-right: 10px;">← Previous</button>
                        <span style="margin: 0 15px; font-weight: bold;">Page ${page} of ${totalPages}</span>
                        <button id="next-page-btn" class="button button-secondary" ${page >= totalPages ? 'disabled' : ''} style="margin-left: 10px;">Next →</button>
                        ${selectedProductIds.size > 0 ? `<div style="margin-top: 8px; font-size: 12px; color: #0073aa;">📋 ${selectedProductIds.size} products selected across all pages</div>` : ''}
                    </div>
                `;
                paginationContainer.html(paginationHtml);
            } else {
                paginationContainer.html('');
            }
            
            let html = `<div style="background: #f9f9f9; padding: 10px; margin-bottom: 10px; border: 1px solid #ddd; border-radius: 4px;">`;
            html += `<p style="margin: 0 0 5px 0; font-weight: bold;">${listIcon} Select ${listType} Products to Import:</p>`;
            html += '<p style="margin: 0; font-size: 12px; color: #666;">✓ Check individual products to import them one by one, or select multiple products for batch import.</p>';
            if (totalPages > 1) {
                html += `<p style="margin: 5px 0 0 0; font-size: 12px; color: #0073aa; font-weight: bold;">📄 Showing ${startIndex + 1}-${endIndex} of ${totalProducts} products (Page ${page} of ${totalPages})</p>`;
            }
            html += '</div>';
            
            html += '<ul style="list-style:none; margin:0; padding:0;">';
            html += `<li style="padding-bottom: 8px; margin-bottom: 8px; border-bottom: 2px solid ${headerColor}; background: ${headerBg}; padding: 8px;">
                        <label style="font-weight: bold; color: ${headerColor};">
                            <input type="checkbox" id="select-all-ecwid-products" style="margin-right: 8px;" /> 
                            ${i18n.select_all_none} (${productsForPage.length} on this page${totalPages > 1 ? ` / ${totalProducts} total` : ''})
                        </label>
                     </li>`;
            
            productsForPage.forEach(function(product, index) {
                const bgColor = index % 2 === 0 ? '#fff' : '#f9f9f9';
                const isSelected = selectedProductIds.has(product.id.toString());
                html += `<li style="padding: 8px; border-bottom: 1px solid #eee; background: ${bgColor};">
                            <label style="display: flex; align-items: center; cursor: pointer;">
                                <input type="checkbox" class="ecwid-product-select" value="${product.id}" ${isSelected ? 'checked' : ''} style="margin-right: 8px;" />
                                <div>
                                    <strong>${product.name}</strong><br>
                                    <small style="color: #666;">
                                        SKU: ${product.sku || 'N/A'} | ID: ${product.id} | 
                                        Status: ${product.enabled ? '✅ Enabled' : '❌ Disabled'}
                                        ${product.combinations && product.combinations.length > 0 ? ` | 🔄 ${product.combinations.length} Variations` : ' | 📦 Simple Product'}
                                    </small>
                                </div>
                            </label>
                         </li>`;
            });
            html += '</ul>';
            
            // Render the product list in its own container (pagination is separate)
            productListContainer.html(html);

            // Enhanced Select All/None functionality
            $('#select-all-ecwid-products').on('change', function() {
                const isChecked = $(this).prop('checked');
                $('.ecwid-product-select').each(function() {
                    const productId = $(this).val();
                    $(this).prop('checked', isChecked);
                    if (isChecked) {
                        selectedProductIds.add(productId);
                    } else {
                        selectedProductIds.delete(productId);
                    }
                });
                updateImportButtonText();
            });
            
            // Update button text when individual checkboxes change
            $('.ecwid-product-select').on('change', function() {
                const productId = $(this).val();
                const isChecked = $(this).prop('checked');
                
                if (isChecked) {
                    selectedProductIds.add(productId);
                } else {
                    selectedProductIds.delete(productId);
                }
                
                updateImportButtonText();
                
                // Update "Select All" checkbox state based on current page
                const totalCheckboxes = $('.ecwid-product-select').length;
                const checkedCheckboxes = $('.ecwid-product-select:checked').length;
                
                if (checkedCheckboxes === 0) {
                    $('#select-all-ecwid-products').prop('indeterminate', false).prop('checked', false);
                } else if (checkedCheckboxes === totalCheckboxes) {
                    $('#select-all-ecwid-products').prop('indeterminate', false).prop('checked', true);
                } else {
                    $('#select-all-ecwid-products').prop('indeterminate', true);
                }
            });
            
            // Initialize button text
            updateImportButtonText();
            
            // Add pagination button handlers
            $('#prev-page-btn').on('click', function() {
                if (currentProductPage > 1) {
                    renderProductSelectionList(currentlyDisplayedProducts, currentProductPage - 1);
                }
            });
            
            $('#next-page-btn').on('click', function() {
                const totalPages = Math.ceil(currentlyDisplayedProducts.length / productsPerPage);
                if (currentProductPage < totalPages) {
                    renderProductSelectionList(currentlyDisplayedProducts, currentProductPage + 1);
                }
            });
        }
        
        // Function to render category selection list with pagination and enhanced UI
        function renderCategorySelectionList(categories, page = 1) {
            currentCategoryPage = page;
            currentlyDisplayedCategories = categories;
            
            const totalCategories = categories.length;
            const totalPages = Math.ceil(totalCategories / categoriesPerPage);
            const startIndex = (page - 1) * categoriesPerPage;
            const endIndex = Math.min(startIndex + categoriesPerPage, totalCategories);
            const categoriesForPage = categories.slice(startIndex, endIndex);
            
            // First, render pagination controls in the separate container above the category list
            const categoryPaginationContainer = $('#category-pagination-container');
            if (totalPages > 1) {
                const paginationHtml = `
                    <div class="category-pagination" style="text-align: center; margin-bottom: 15px; padding: 10px; background: #fff; border: 1px solid #ddd; border-radius: 4px;">
                        <button id="prev-category-page-btn" class="button button-secondary" ${page <= 1 ? 'disabled' : ''} style="margin-right: 10px;">← Previous</button>
                        <span style="margin: 0 15px; font-weight: bold;">Page ${page} of ${totalPages}</span>
                        <button id="next-category-page-btn" class="button button-secondary" ${page >= totalPages ? 'disabled' : ''} style="margin-left: 10px;">Next →</button>
                        ${selectedCategoryIds.size > 0 ? `<div style="margin-top: 8px; font-size: 12px; color: #0073aa;">📋 ${selectedCategoryIds.size} categories selected across all pages</div>` : ''}
                    </div>
                `;
                categoryPaginationContainer.html(paginationHtml);
            } else {
                categoryPaginationContainer.html('');
            }
            
            let html = `<div style="background: #f9f9f9; padding: 10px; margin-bottom: 10px; border: 1px solid #ddd; border-radius: 4px;">`;
            html += `<p style="margin: 0 0 5px 0; font-weight: bold;">📁 Select Categories to Import:</p>`;
            html += '<p style="margin: 0; font-size: 12px; color: #666;">✓ Check individual categories to import them one by one, or select multiple categories for batch import.</p>';
            if (totalPages > 1) {
                html += `<p style="margin: 5px 0 0 0; font-size: 12px; color: #0073aa; font-weight: bold;">📄 Showing ${startIndex + 1}-${endIndex} of ${totalCategories} categories (Page ${page} of ${totalPages})</p>`;
            }
            html += '</div>';
            
            html += '<ul style="list-style:none; margin:0; padding:0;">';
            html += `<li style="padding-bottom: 8px; margin-bottom: 8px; border-bottom: 2px solid #0073aa; background: #f0f8ff; padding: 8px;">
                        <label style="font-weight: bold; color: #0073aa;">
                            <input type="checkbox" id="select-all-ecwid-categories" style="margin-right: 8px;" /> 
                            ${i18n.select_all_none} (${categoriesForPage.length} on this page${totalPages > 1 ? ` / ${totalCategories} total` : ''})
                        </label>
                     </li>`;
            
            categoriesForPage.forEach(function(category, index) {
                const bgColor = index % 2 === 0 ? '#fff' : '#f9f9f9';
                const isSelected = selectedCategoryIds.has(category.id.toString());
                html += `<li style="padding: 8px; border-bottom: 1px solid #eee; background: ${bgColor};">
                            <label style="display: flex; align-items: center; cursor: pointer;">
                                <input type="checkbox" class="ecwid-category-select" value="${category.id}" ${isSelected ? 'checked' : ''} style="margin-right: 8px;" />
                                <div>
                                    <strong>${category.name}</strong><br>
                                    <small style="color: #666;">
                                        ID: ${category.id} | Parent ID: ${category.parentId || '0 (Root)'}
                                        ${category.parentId ? ' | 📂 Subcategory' : ' | 🏠 Root Category'}
                                    </small>
                                </div>
                            </label>
                         </li>`;
            });
            html += '</ul>';
            
            categoryListContainer.html(html);

            // Enhanced Select All/None functionality for current page
            $('#select-all-ecwid-categories').on('change', function() {
                const isChecked = $(this).prop('checked');
                $('.ecwid-category-select').each(function() {
                    const categoryId = $(this).val();
                    $(this).prop('checked', isChecked);
                    if (isChecked) {
                        selectedCategoryIds.add(categoryId);
                    } else {
                        selectedCategoryIds.delete(categoryId);
                    }
                });
                updateCategoryImportButtonText();
            });
            
            // Update button text and track selections when individual checkboxes change
            $('.ecwid-category-select').on('change', function() {
                const categoryId = $(this).val();
                const isChecked = $(this).prop('checked');
                
                if (isChecked) {
                    selectedCategoryIds.add(categoryId);
                } else {
                    selectedCategoryIds.delete(categoryId);
                }
                
                updateCategoryImportButtonText();
                
                // Update "Select All" checkbox state for current page
                const totalCheckboxes = $('.ecwid-category-select').length;
                const checkedCheckboxes = $('.ecwid-category-select:checked').length;
                
                if (checkedCheckboxes === 0) {
                    $('#select-all-ecwid-categories').prop('indeterminate', false).prop('checked', false);
                } else if (checkedCheckboxes === totalCheckboxes) {
                    $('#select-all-ecwid-categories').prop('indeterminate', false).prop('checked', true);
                } else {
                    $('#select-all-ecwid-categories').prop('indeterminate', true);
                }
                
                // Update pagination info if it exists
                if (totalPages > 1) {
                    categoryPaginationContainer.find('div').html(`📋 ${selectedCategoryIds.size} categories selected across all pages`);
                }
            });
            
            // Initialize button text
            updateCategoryImportButtonText();
            
            // Add pagination button handlers
            $('#prev-category-page-btn').on('click', function() {
                if (currentCategoryPage > 1) {
                    renderCategorySelectionList(currentlyDisplayedCategories, currentCategoryPage - 1);
                }
            });
            
            $('#next-category-page-btn').on('click', function() {
                const totalPages = Math.ceil(currentlyDisplayedCategories.length / categoriesPerPage);
                if (currentCategoryPage < totalPages) {
                    renderCategorySelectionList(currentlyDisplayedCategories, currentCategoryPage + 1);
                }
            });
        }
        
        // Function to update category import button text based on selection
        function updateCategoryImportButtonText() {
            if (!importSelectedCategoriesButton.length) return;
            
            const selectedCount = selectedCategoryIds.size; // Use our maintained Set
            if (selectedCount === 0) {
                importSelectedCategoriesButton.text(i18n.import_selected_categories || 'Import Selected Categories');
            } else if (selectedCount === 1) {
                importSelectedCategoriesButton.text(`Import 1 Category`);
            } else {
                importSelectedCategoriesButton.text(`Import ${selectedCount} Categories`);
            }
        }
        
        // Function to update import button text based on selection
        function updateImportButtonText() {
            if (!importSelectedButton.length) return;
            
            const selectedCount = selectedProductIds.size; // Use our maintained Set
            if (selectedCount === 0) {
                importSelectedButton.text(i18n.import_selected || 'Import Selected Products');
            } else if (selectedCount === 1) {
                importSelectedButton.text(`Import 1 Product`);
            } else {
                importSelectedButton.text(`Import ${selectedCount} Products`);
            }
        }

        importSelectedButton.on('click', function(e) {
            e.preventDefault();
            if (importSelectedButton.hasClass('disabled')) return;

            // Collect selected product IDs from our maintained Set (works across all pages)
            productsToImportSelectedIds = Array.from(selectedProductIds);

            if (productsToImportSelectedIds.length === 0) {
                alert(i18n.no_products_selected);
                return;
            }

            importSelectedButton.addClass('disabled').text(i18n.importing_selected);
            loadProductsButton.addClass('disabled'); // Disable load while importing
            selectiveSyncStatusDiv.text(i18n.sync_starting);
            selectiveSyncProgressBarContainer.show();
            updateProgressBar(selectiveSyncProgressBar, 0);
            selectiveSyncLogDiv.html('');
            currentSelectiveImportProductIndex = 0;
            currentProductVariationData = null; // Reset variation state

            logMessage(selectiveSyncLogDiv, i18n.sync_starting + ' ' + productsToImportSelectedIds.length + ' products.', 'info');
            processNextSelectedProduct();
        });

        function processNextSelectedProduct() {
            stopBatchStatusAnimation(); // Stop any previous animation

            // If there's pending variation data, process that first
            if (currentProductVariationData && currentProductVariationData.current_variation_offset < currentProductVariationData.total_combinations) {
                processProductVariationBatch();
                return;
            }

            // All variations for the previous product are done, or it was a simple product.
            // Reset variation data and move to the next product in the main list.
            currentProductVariationData = null;

            if (currentSelectiveImportProductIndex < productsToImportSelectedIds.length) {
                const ecwidProductId = productsToImportSelectedIds[currentSelectiveImportProductIndex];
                const productFullData = ecwidProductsForSelection.find(p => p.id.toString() === ecwidProductId.toString());
                
                if (!productFullData) {
                    logMessage(selectiveSyncLogDiv, `Error: Could not find full data for product ID ${ecwidProductId}. Skipping.`, 'error');
                    currentSelectiveImportProductIndex++;
                    updateOverallSelectiveProgress();
                    processNextSelectedProduct(); // Process next
                    return;
                }

                const productName = productFullData.name || `ID ${ecwidProductId}`;
                const baseStatusText = i18n.importing_selected + ` (${currentSelectiveImportProductIndex + 1}/${productsToImportSelectedIds.length}): ${productName} (Importing parent...)`;
                startBatchStatusAnimation(selectiveSyncStatusDiv, baseStatusText);

                $.ajax({
                    url: ajax_url,
                    method: 'POST',
                    data: {
                        action: 'ecwid_wc_import_selected_products',
                        nonce: nonce,
                        ecwid_product_id: ecwidProductId
                    },
                    success: function(response) {
                        stopBatchStatusAnimation();
                        if (response.success) {
                            logMessage(selectiveSyncLogDiv, `Parent Import for ${response.data.item_name || productName} (Ecwid ID: ${response.data.ecwid_id}, SKU: ${response.data.sku || 'N/A'}): Status - ${response.data.status}`, 
                                (response.data.status === 'imported' || response.data.status === 'skipped' || response.data.status === 'variations_pending') ? 'success' : 'info');
                            

                            (response.data.logs || []).forEach(logEntry => categorizeAndLog(selectiveSyncLogDiv, logEntry));

                            if (response.data.status === 'variations_pending') {
                                logMessage(selectiveSyncLogDiv, i18n.parent_product_imported_pending_variations.replace('{productName}', response.data.item_name || productName), 'info');
                                currentProductVariationData = {
                                    wc_product_id: response.data.wc_product_id,
                                    ecwid_product_id: response.data.ecwid_product_id,
                                    item_name: response.data.item_name || productName,
                                    sku: response.data.sku,
                                    all_combinations: response.data.all_combinations || [],
                                    total_combinations: response.data.total_combinations || 0,
                                    original_options: productFullData.options || [], // Get options from the initially fetched list
                                    current_variation_offset: 0
                                };
                                if (currentProductVariationData.total_combinations > 0) {
                                     processProductVariationBatch(); // Start variation batching
                                } else {
                                    logMessage(selectiveSyncLogDiv, `Product ${currentProductVariationData.item_name} marked for variations but none found. Moving to next.`, 'warning');
                                    currentProductVariationData = null; // Clear as no variations to process
                                    currentSelectiveImportProductIndex++;
                                    updateOverallSelectiveProgress();
                                    processNextSelectedProduct(); // Process next main product
                                }
                            } else {
                                // Simple product or variable product with no variations processed, or error in parent import
                                currentSelectiveImportProductIndex++;
                                updateOverallSelectiveProgress();
                                processNextSelectedProduct(); // Process next main product
                            }
                        } else {
                            handleAjaxError(selectiveSyncStatusDiv, selectiveSyncLogDiv, null, null, `Product ID ${ecwidProductId}`, response.data);
                            currentSelectiveImportProductIndex++;
                            updateOverallSelectiveProgress();
                            processNextSelectedProduct(); // Try next one
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        stopBatchStatusAnimation();
                        handleAjaxError(selectiveSyncStatusDiv, selectiveSyncLogDiv, null, null, `Product ID ${ecwidProductId}`, { message: `${textStatus} ${errorThrown || ''}` }, true);
                        currentSelectiveImportProductIndex++;
                        updateOverallSelectiveProgress();
                        processNextSelectedProduct(); // Try next one
                    }
                });
            } else {
                // All products in the main list (and their variations) are processed
                stopBatchStatusAnimation();
                updateStatus(selectiveSyncStatusDiv, i18n.sync_complete);
                logMessage(selectiveSyncLogDiv, i18n.sync_complete, 'success');
                importSelectedButton.removeClass('disabled').text(i18n.import_selected);
                loadProductsButton.removeClass('disabled');
                updateProgressBar(selectiveSyncProgressBar, 100);
            }
        }

        function processProductVariationBatch() {
            if (!currentProductVariationData) {
                console.error("processProductVariationBatch called without currentProductVariationData.");
                processNextSelectedProduct(); // Attempt to recover by moving to the next product
                return;
            }

            const { wc_product_id, ecwid_product_id, item_name, sku, all_combinations, total_combinations, original_options, current_variation_offset } = currentProductVariationData;

            if (current_variation_offset >= total_combinations) {
                logMessage(selectiveSyncLogDiv, i18n.variations_imported_successfully.replace('{productName}', sanitizeHTML(item_name)), 'success');
                currentProductVariationData = null; // Clear variation state
                currentSelectiveImportProductIndex++; // Mark parent product as fully done
                updateOverallSelectiveProgress();
                processNextSelectedProduct(); // Move to the next product in the main list
                return;
            }

            const combinationsBatch = all_combinations.slice(current_variation_offset, current_variation_offset + variationBatchSize);
            // const currentBatchNumber = Math.floor(current_variation_offset / variationBatchSize) + 1; // Not used for new status
            // const totalBatches = Math.ceil(total_combinations / variationBatchSize); // Not used for new status
            
            // Use a more descriptive status showing actual variation counts
            const statusMsg = i18n.syncing_item_of_total
                .replace('{syncType}', `Variations for '${sanitizeHTML(item_name)}'`)
                .replace('{current}', current_variation_offset) // Variations processed *before* this batch
                .replace('{total}', total_combinations);
            startBatchStatusAnimation(selectiveSyncStatusDiv, statusMsg);

            $.ajax({
                url: ajax_url,
                method: 'POST',
                data: {
                    action: 'ecwid_wc_process_variation_batch',
                    nonce: nonce,
                    wc_product_id: wc_product_id,
                    ecwid_product_id: ecwid_product_id, 
                    item_name: item_name, // item_name is used by PHP for logging
                    sku: sku, 
                    combinations_batch_json: JSON.stringify(combinationsBatch),
                    original_ecwid_options_json: JSON.stringify(original_options || [])
                },
                success: function(response) {
                    stopBatchStatusAnimation();
                    (response.data.batch_logs || []).forEach(logEntry => categorizeAndLog(selectiveSyncLogDiv, logEntry));

                    if (response.success) {
                        currentProductVariationData.current_variation_offset += combinationsBatch.length;
                        // Update status to reflect new count after batch completion for the next iteration's display
                        const nextStatusPreview = i18n.syncing_item_of_total
                            .replace('{syncType}', `Variations for '${sanitizeHTML(item_name)}'`)
                            .replace('{current}', currentProductVariationData.current_variation_offset)
                            .replace('{total}', total_combinations);
                        updateStatus(selectiveSyncStatusDiv, nextStatusPreview);
                        updateOverallSelectiveProgress(); 
                        processProductVariationBatch(); 
                    } else {
                        let errorMessage = i18n.error_importing_variations.replace('{productName}', sanitizeHTML(item_name));
                        if (response.data && response.data.message) {
                            errorMessage += `: ${sanitizeHTML(response.data.message)}`;
                        }
                        logMessage(selectiveSyncLogDiv, errorMessage, 'error');
                        
                        logMessage(selectiveSyncLogDiv, `Skipping remaining variations for ${sanitizeHTML(item_name)} due to error.`, 'warning');
                        currentProductVariationData = null; 
                        currentSelectiveImportProductIndex++; 
                        updateOverallSelectiveProgress(); 
                        processNextSelectedProduct(); 
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    stopBatchStatusAnimation();
                    let errorMessage = i18n.error_importing_variations.replace('{productName}', sanitizeHTML(item_name));
                    errorMessage += `: AJAX Error - ${sanitizeHTML(textStatus)} ${sanitizeHTML(errorThrown || '')}`;
                    logMessage(selectiveSyncLogDiv, errorMessage, 'error');

                    logMessage(selectiveSyncLogDiv, `Skipping remaining variations for ${sanitizeHTML(item_name)} due to AJAX error.`, 'warning');
                    currentProductVariationData = null; 
                    currentSelectiveImportProductIndex++; 
                    updateOverallSelectiveProgress();
                    processNextSelectedProduct();
                }
            });
        }

        // Ensure updateOverallSelectiveProgress is defined correctly and separately
        function updateOverallSelectiveProgress() {
            let overallProgress = 0;
            const totalProductsToImport = productsToImportSelectedIds.length;

            if (totalProductsToImport === 0) {
                updateProgressBar(selectiveSyncProgressBar, 0);
                return;
            }
            
            let completedParentProductCount = currentSelectiveImportProductIndex;

            if (currentProductVariationData) { 
                overallProgress = (completedParentProductCount / totalProductsToImport) * 100; 

                const { total_combinations, current_variation_offset } = currentProductVariationData;
                if (total_combinations > 0) {
                    const variationProgressForCurrentProduct = (current_variation_offset / total_combinations) * (1 / totalProductsToImport) * 100;
                    overallProgress += variationProgressForCurrentProduct;
                }
            } else { 
                 overallProgress = (currentSelectiveImportProductIndex / totalProductsToImport) * 100;
            }

            overallProgress = Math.min(overallProgress, 100); 
            updateProgressBar(selectiveSyncProgressBar, overallProgress); 
        }

        // Category Import Button Handler
        importSelectedCategoriesButton.on('click', function(e) {
            e.preventDefault();
            if (importSelectedCategoriesButton.hasClass('disabled')) return;

            const categoriesToImportIds = Array.from(selectedCategoryIds); // Use global selection across all pages

            if (categoriesToImportIds.length === 0) {
                alert(i18n.no_categories_selected);
                return;
            }

            importSelectedCategoriesButton.addClass('disabled').text('Importing Categories...');
            loadCategoriesButton.addClass('disabled');
            categorySyncInitialInfoDiv.text('Importing selected categories...');
            categoryListContainer.hide();

            // Create a simple status message
            const statusMessage = `Importing ${categoriesToImportIds.length} selected categories...`;
            if (selectiveSyncStatusDiv.length) {
                selectiveSyncStatusDiv.text(statusMessage);
            }

            $.ajax({
                url: ajax_url,
                method: 'POST',
                data: {
                    action: 'ecwid_wc_import_selected_categories',
                    nonce: nonce,
                    category_ids: categoriesToImportIds
                },
                success: function(response) {
                    if (response.success) {
                        const resultMessage = `Successfully imported ${categoriesToImportIds.length} categories!`;
                        if (selectiveSyncStatusDiv.length) {
                            selectiveSyncStatusDiv.text(resultMessage);
                        }
                        if (categorySyncInitialInfoDiv.length) {
                            categorySyncInitialInfoDiv.text(resultMessage + ' You can select more categories or reload the list.');
                        }
                        
                        // Log details if available
                        if (selectiveSyncLogDiv.length) {
                            (response.data.logs || []).forEach(log => categorizeAndLog(selectiveSyncLogDiv, log));
                        }
                    } else {
                        const errorMsg = response.data && response.data.message ? response.data.message : 'Unknown error occurred.';
                        if (selectiveSyncStatusDiv.length) {
                            selectiveSyncStatusDiv.text('Error: ' + errorMsg);
                        }
                        if (categorySyncInitialInfoDiv.length) {
                            categorySyncInitialInfoDiv.html('<span style="color:red;">Error: ' + sanitizeHTML(errorMsg) + '</span>');
                        }
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    const errorText = 'AJAX Error: ' + textStatus + (errorThrown ? ' - ' + errorThrown : '');
                    if (selectiveSyncStatusDiv.length) {
                        selectiveSyncStatusDiv.text(errorText);
                    }
                    if (categorySyncInitialInfoDiv.length) {
                        categorySyncInitialInfoDiv.html('<span style="color:red;">' + sanitizeHTML(errorText) + '</span>');
                    }
                },
                complete: function() {
                    importSelectedCategoriesButton.removeClass('disabled').text(i18n.import_selected_categories || 'Import Selected Categories');
                    loadCategoriesButton.removeClass('disabled');
                    categoryListContainer.show();
                }
            });
        });

        // Sync All Categories Button Handler
        if (syncAllCategoriesButton.length) {
            syncAllCategoriesButton.on('click', function(e) {
                e.preventDefault();
                if (syncAllCategoriesButton.hasClass('disabled')) return;

                // Show confirmation dialog
                const confirmMessage = 'This will import ALL categories from your Ecwid store to WooCommerce. This may take several minutes for large catalogs. Continue?';
                if (!confirm(confirmMessage)) {
                    return;
                }

                // Disable buttons and show progress
                syncAllCategoriesButton.addClass('disabled').text('Importing All Categories...');
                loadCategoriesButton.addClass('disabled');
                importSelectedCategoriesButton.addClass('disabled');
                
                // Clear and reset UI elements
                selectiveSyncStatusDiv.empty();
                selectiveSyncLogDiv.empty();
                selectiveSyncProgressBarContainer.show();
                updateProgressBar(selectiveSyncProgressBar, 0);
                updateStatus(selectiveSyncStatusDiv, 'Starting full category import...');
                
                // Hide category list during sync
                categoryListContainer.hide();
                const categoryPaginationContainer = $('#category-pagination-container');
                if (categoryPaginationContainer.length) {
                    categoryPaginationContainer.hide();
                }

                $.ajax({
                    url: ajax_url,
                    method: 'POST',
                    timeout: 300000, // 5 minutes timeout for bulk operations
                    data: {
                        action: 'ecwid_wc_sync_all_categories',
                        nonce: nonce
                    },
                    beforeSend: function() {
                        updateProgressBar(selectiveSyncProgressBar, 10);
                        updateStatus(selectiveSyncStatusDiv, 'Fetching all categories from Ecwid...');
                    },
                    success: function(response) {
                        if (response.success) {
                            const data = response.data;
                            updateProgressBar(selectiveSyncProgressBar, 100);
                            updateStatus(selectiveSyncStatusDiv, 'Full category import completed successfully!');
                            
                            // Display results
                            const resultMessage = data.message || 'Category import completed';
                            logMessage(selectiveSyncLogDiv, resultMessage, 'success');
                            
                            // Show detailed statistics if available
                            if (data.imported_count !== undefined || data.updated_count !== undefined) {
                                const importedCount = parseInt(data.imported_count) || 0;
                                const updatedCount = parseInt(data.updated_count) || 0;
                                const skippedCount = parseInt(data.skipped_count) || 0;
                                const failedCount = parseInt(data.failed_count) || 0;
                                
                                const statsMessage = `Results: ${importedCount} imported, ${updatedCount} updated, ${skippedCount} skipped, ${failedCount} failed`;
                                logMessage(selectiveSyncLogDiv, statsMessage, 'info');
                            }
                            
                            // Display detailed logs if available
                            if (data.logs && data.logs.length > 0) {
                                data.logs.forEach(function(logEntry) {
                                    logMessage(selectiveSyncLogDiv, logEntry, 'info');
                                });
                            }
                            
                            // Reload category list to show updated data
                            setTimeout(function() {
                                if (loadCategoriesButton.length) {
                                    loadAndDisplayCategories();
                                }
                            }, 2000);
                            
                        } else {
                            const errorMsg = response.data && response.data.message ? response.data.message : 'Unknown error occurred during category import';
                            updateStatus(selectiveSyncStatusDiv, 'Import failed');
                            logMessage(selectiveSyncLogDiv, `Error: ${errorMsg}`, 'error');
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        const errorText = `AJAX Error: ${textStatus}${errorThrown ? ' - ' + errorThrown : ''}`;
                        updateStatus(selectiveSyncStatusDiv, 'Import failed');
                        logMessage(selectiveSyncLogDiv, `Error: ${errorText}`, 'error');
                    },
                    complete: function() {
                        // Re-enable buttons
                        syncAllCategoriesButton.removeClass('disabled').text('Import All Categories');
                        loadCategoriesButton.removeClass('disabled');
                        importSelectedCategoriesButton.removeClass('disabled');
                        
                        // Show category list and pagination again
                        categoryListContainer.show();
                        if (categoryPaginationContainer.length) {
                            categoryPaginationContainer.show();
                        }
                    }
                });
            });
        }

        // Sync All Products Button Handler
        if (syncAllProductsButton.length) {
            syncAllProductsButton.on('click', function(e) {
                e.preventDefault();
                if (syncAllProductsButton.hasClass('disabled')) return;

                // Show confirmation dialog
                const confirmMessage = 'This will import ALL products from your Ecwid store to WooCommerce. This may take several minutes for large catalogs. Continue?';
                if (!confirm(confirmMessage)) {
                    return;
                }

                // Disable buttons and show progress
                syncAllProductsButton.addClass('disabled').text('Importing All Products...');
                loadProductsButton.addClass('disabled');
                importSelectedButton.addClass('disabled');
                
                // Clear and reset UI elements
                selectiveSyncStatusDiv.empty();
                selectiveSyncLogDiv.empty();
                selectiveSyncProgressBarContainer.show();
                updateProgressBar(selectiveSyncProgressBar, 0);
                updateStatus(selectiveSyncStatusDiv, 'Starting full product import...');
                
                // Hide product list during sync
                productListContainer.hide();
                const productPaginationContainer = $('#product-pagination-container');
                if (productPaginationContainer.length) {
                    productPaginationContainer.hide();
                }

                $.ajax({
                    url: ajax_url,
                    method: 'POST',
                    timeout: 600000, // 10 minutes timeout for bulk product operations
                    data: {
                        action: 'ecwid_wc_sync_all_products',
                        nonce: nonce
                    },
                    beforeSend: function() {
                        updateProgressBar(selectiveSyncProgressBar, 10);
                        updateStatus(selectiveSyncStatusDiv, 'Fetching all products from Ecwid...');
                    },
                    success: function(response) {
                        if (response.success) {
                            const data = response.data;
                            updateProgressBar(selectiveSyncProgressBar, 100);
                            updateStatus(selectiveSyncStatusDiv, 'Full product import completed successfully!');
                            
                            // Display results
                            const resultMessage = data.message || 'Product import completed';
                            logMessage(selectiveSyncLogDiv, resultMessage, 'success');
                            
                            // Show detailed statistics if available
                            if (data.imported_count !== undefined || data.updated_count !== undefined) {
                                const importedCount = parseInt(data.imported_count) || 0;
                                const updatedCount = parseInt(data.updated_count) || 0;
                                const skippedCount = parseInt(data.skipped_count) || 0;
                                const failedCount = parseInt(data.failed_count) || 0;
                                
                                const statsMessage = `Results: ${importedCount} imported, ${updatedCount} updated, ${skippedCount} skipped, ${failedCount} failed`;
                                logMessage(selectiveSyncLogDiv, statsMessage, 'info');
                            }
                            
                            // Display detailed logs if available
                            if (data.logs && data.logs.length > 0) {
                                data.logs.forEach(function(logEntry) {
                                    logMessage(selectiveSyncLogDiv, logEntry, 'info');
                                });
                            }
                            
                            // Reload product list to show updated data
                            setTimeout(function() {
                                if (loadProductsButton.length) {
                                    loadAndDisplayProductsForSelection();
                                }
                            }, 2000);
                            
                        } else {
                            const errorMsg = response.data && response.data.message ? response.data.message : 'Unknown error occurred during product import';
                            updateStatus(selectiveSyncStatusDiv, 'Import failed');
                            logMessage(selectiveSyncLogDiv, `Error: ${errorMsg}`, 'error');
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        const errorText = `AJAX Error: ${textStatus}${errorThrown ? ' - ' + errorThrown : ''}`;
                        updateStatus(selectiveSyncStatusDiv, 'Import failed');
                        logMessage(selectiveSyncLogDiv, `Error: ${errorText}`, 'error');
                    },
                    complete: function() {
                        // Re-enable buttons
                        syncAllProductsButton.removeClass('disabled').text('Import All Products');
                        loadProductsButton.removeClass('disabled');
                        importSelectedButton.removeClass('disabled');
                        
                        // Show product list and pagination again
                        productListContainer.show();
                        if (productPaginationContainer.length) {
                            productPaginationContainer.show();
                        }
                    }
                });
            });
        }


        // --- Fix Category Hierarchy Logic ---
        if (fixHierarchyButton.length) {
            fixHierarchyButton.on('click', function(e) {
                e.preventDefault();
                const $button = $(this);
                if ($button.hasClass('disabled')) return;
                
                $button.addClass('disabled').text('Fixing Hierarchies...');
                categoryPageSyncStatusDiv.text('Fixing Category Hierarchies...');
                logMessage(categoryPageSyncLogDiv, 'Starting category hierarchy fix...', 'info');
                
                $.ajax({
                    url: ajax_url,
                    method: 'POST',
                    data: { action: 'fix_category_hierarchy', nonce: nonce },
                    success: function(response) {
                        if (response.success) {
                            categoryPageSyncStatusDiv.text('Category hierarchies fixed! ' + (response.data.fixed_count || 0) + ' categories updated.');
                            logMessage(categoryPageSyncLogDiv, 'Hierarchy fix completed. ' + (response.data.message || ''), 'success');
                            (response.data.logs || []).forEach(log => categorizeAndLog(categoryPageSyncLogDiv, log));
                        } else {
                            handleAjaxError(categoryPageSyncStatusDiv, categoryPageSyncLogDiv, null, null, 'Fix Hierarchy', response.data);
                        }
                        $button.removeClass('disabled').text('Fix Category Hierarchy');
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                         handleAjaxError(categoryPageSyncStatusDiv, categoryPageSyncLogDiv, null, null, 'Fix Hierarchy', { message: `${textStatus} ${errorThrown || ''}` }, true);
                        $button.removeClass('disabled').text('Fix Category Hierarchy');
                    }
                });
            });
        }

        // Enhanced Settings Page Functionality
        function initializeSettingsPage() {
            // Auto-test connection on page load if both credentials exist
            const storeIdInput = $('input[name="ecwid_wc_sync_options[store_id]"]');
            const tokenInput = $('input[name="ecwid_wc_sync_options[token]"]');
            
            if (storeIdInput.length && tokenInput.length) {
                const storeId = storeIdInput.val();
                const token = tokenInput.val();
                
                if (storeId && token && storeId.length > 0 && token.length > 0) {
                    // Delay auto-test to ensure page is fully loaded
                    setTimeout(function() {
                        performConnectionTest(true); // true indicates auto-test
                    }, 800);
                }
                
                // Clear connection status when inputs change
                storeIdInput.add(tokenInput).on('input', function() {
                    $('#test-connection-result').hide().removeClass('success error');
                });
            }
            
            // Enhanced connection test button handler
            $(document).on('click', '#test-api-connection', function(e) {
                e.preventDefault();
                performConnectionTest(false);
            });
            
            // Enhanced form submission with visual feedback
            $('#ecwid-settings-form').on('submit', function(e) {
                const saveStatusDiv = $('#save-status');
                saveStatusDiv.hide().removeClass('success error');
                
                // Show immediate feedback
                setTimeout(function() {
                    saveStatusDiv.addClass('success')
                            .html('<strong>✅ ' + (i18n.settings_saved_successfully || 'Settings saved successfully!') + '</strong>')
                            .show();
                    
                    // Auto-test connection after successful save
                    setTimeout(function() {
                        performConnectionTest(true);
                    }, 1200);
                }, 200);
            });
        }
        
        function performConnectionTest(isAutoTest = false) {
            const button = $('#test-api-connection');
            const originalText = button.text();
            const resultDiv = $('#test-connection-result');
            
            if (!isAutoTest) {
                button.html('<span class="loading-spinner"></span>' + (i18n.testing_connection || 'Testing...')).prop('disabled', true);
            }
            
            resultDiv.hide().removeClass('success error');
            
            $.ajax({
                url: ajax_url,
                type: 'POST',
                data: {
                    action: 'ecwid_wc_test_connection',
                    nonce: nonce
                },
                success: function(response) {
                    if (response.success) {
                        resultDiv.addClass('success')
                                .html('<strong>✅ ' + (i18n.connection_successful || 'CONNECTION SUCCESSFUL!') + '</strong><br>' + response.data.message)
                                .show();
                        
                        // Add subtle success animation for nav buttons
                        $('.nav-buttons-grid .nav-button').addClass('connection-success');
                        setTimeout(function() {
                            $('.nav-buttons-grid .nav-button').removeClass('connection-success');
                        }, 2000);
                    } else {
                        resultDiv.addClass('error')
                                .html('<strong>❌ ' + (i18n.connection_failed || 'CONNECTION FAILED') + '</strong><br>' + response.data.message)
                                .show();
                    }
                },
                error: function() {
                    resultDiv.addClass('error')
                            .html('<strong>❌ CONNECTION ERROR</strong><br>' + (i18n.connection_test_failed || 'Connection test failed. Please try again.'))
                            .show();
                },
                complete: function() {
                    if (!isAutoTest) {
                        button.text(originalText).prop('disabled', false);
                    }
                }
            });
        }

        // Add upload diagnostics function
        function runUploadDiagnostics() {
            const diagButton = $('#upload-diagnostics-button');
            const resultDiv = $('#upload-diagnostics-result');
            
            if (!diagButton.length || !resultDiv.length) return;

            const originalText = diagButton.text();
            diagButton.text('Running Diagnostics...').prop('disabled', true);
            resultDiv.removeClass('success error').hide();

            $.ajax({
                url: ajax_url,
                type: 'POST',
                data: {
                    action: 'ecwid_wc_diagnose_uploads',
                    nonce: nonce
                },
                success: function(response) {
                    if (response.success) {
                        const diag = response.data.diagnostics;
                        let html = '<h4>Upload Directory Diagnostics</h4>';
                        
                        // Basic directory info
                        html += '<div class="diagnostic-section">';
                        html += '<h5>Directory Information:</h5>';
                        html += '<ul>';
                        html += '<li><strong>Upload Directory:</strong> ' + diag.upload_dir_info.path + '</li>';
                        html += '<li><strong>Base Directory:</strong> ' + diag.upload_dir_info.basedir + '</li>';
                        html += '<li><strong>URL:</strong> ' + diag.upload_dir_info.url + '</li>';
                        html += '</ul>';
                        html += '</div>';

                        // Permission checks
                        html += '<div class="diagnostic-section">';
                        html += '<h5>Permission Checks:</h5>';
                        html += '<ul>';
                        html += '<li><strong>Base directory exists:</strong> ' + (diag.basedir_exists ? '✅ Yes' : '❌ No') + '</li>';
                        html += '<li><strong>Base directory writable:</strong> ' + (diag.basedir_writable ? '✅ Yes' : '❌ No') + '</li>';
                        html += '<li><strong>Upload path exists:</strong> ' + (diag.path_exists ? '✅ Yes' : '❌ No') + '</li>';
                        html += '<li><strong>Upload path writable:</strong> ' + (diag.path_writable ? '✅ Yes' : '❌ No') + '</li>';
                        html += '<li><strong>Test file write:</strong> ' + (diag.test_write_success ? '✅ Success' : '❌ Failed') + '</li>';
                        html += '</ul>';
                        html += '</div>';

                        // Server info
                        html += '<div class="diagnostic-section">';
                        html += '<h5>Server Information:</h5>';
                        html += '<ul>';
                        html += '<li><strong>Free disk space:</strong> ' + (diag.disk_free_space ? Math.round(diag.disk_free_space / 1024 / 1024) + ' MB' : 'Unknown') + '</li>';
                        html += '<li><strong>PHP upload limit:</strong> ' + diag.php_upload_max_filesize + '</li>';
                        html += '<li><strong>PHP post limit:</strong> ' + diag.php_post_max_size + '</li>';
                        html += '<li><strong>WP max upload:</strong> ' + Math.round(diag.wp_max_upload_size / 1024 / 1024) + ' MB</li>';
                        html += '<li><strong>PHP memory limit:</strong> ' + diag.php_memory_limit + '</li>';
                        html += '</ul>';
                        html += '</div>';

                        // User info
                        html += '<div class="diagnostic-section">';
                        html += '<h5>User Information:</h5>';
                        html += '<ul>';
                        html += '<li><strong>WordPress user:</strong> ' + diag.current_user + '</li>';
                        html += '<li><strong>PHP/System user:</strong> ' + diag.php_user + '</li>';
                        html += '<li><strong>Server software:</strong> ' + diag.server_software + '</li>';
                        html += '</ul>';
                        html += '</div>';

                        // Issues detection
                        const issues = [];
                        if (!diag.basedir_exists) issues.push('Upload base directory does not exist');
                        if (!diag.basedir_writable) issues.push('Upload base directory is not writable');
                        if (!diag.path_exists) issues.push('Upload path does not exist');
                        if (!diag.path_writable) issues.push('Upload path is not writable');
                        if (!diag.test_write_success) issues.push('Cannot write test files');
                        if (diag.disk_free_space && diag.disk_free_space < 100 * 1024 * 1024) issues.push('Low disk space (less than 100MB)');

                        if (issues.length > 0) {
                            html += '<div class="diagnostic-issues">';
                            html += '<h5 style="color: red;">⚠️ Issues Detected:</h5>';
                            html += '<ul style="color: red;">';
                            issues.forEach(issue => {
                                html += '<li>' + issue + '</li>';
                            });
                            html += '</ul>';
                            html += '</div>';
                        } else {
                            html += '<div style="color: green;"><h5>✅ No obvious issues detected</h5></div>';
                        }

                        resultDiv.addClass('success').html(html).show();
                    } else {
                        resultDiv.addClass('error')
                                .html('<strong>❌ Diagnostics Failed</strong><br>' + response.data.message)
                                .show();
                    }
                },
                error: function() {
                    resultDiv.addClass('error')
                            .html('<strong>❌ Diagnostics Error</strong><br>Failed to run diagnostics. Please try again.')
                            .show();
                },
                complete: function() {
                    diagButton.text(originalText).prop('disabled', false);
                }
            });
        }

        // Bind diagnostics button if it exists
        $(document).on('click', '#upload-diagnostics-button', runUploadDiagnostics);

        // Initialize page-specific functionality
        if (window.location.href.indexOf('ecwid-sync-settings') !== -1) {
            initializeSettingsPage();
        }

    });
    
    // Debug utility function that can be called from browser console
    window.ecwid2wooDebug = function() {
        if (typeof ecwid_sync_params === 'undefined') {
            console.error('Ecwid Sync Error: Localization parameters not found.');
            return;
        }
        
        if (window.ecwidDebugMode) {
            console.log('🔧 Running Ecwid2Woo Debug...');
        }
        
        $.ajax({
            url: ecwid_sync_params.ajax_url,
            type: 'POST',
            data: {
                action: 'ecwid_wc_debug_info',
                nonce: ecwid_sync_params.nonce,
            },
            dataType: 'json'
        })
        .done(function(response) {
            if (window.ecwidDebugMode) {
                console.log('✅ Debug Info:', response);
                console.table(response.data);
            }
        })
        .fail(function(jqXHR, textStatus, errorThrown) {
            if (window.ecwidDebugMode) {
                console.error('❌ Debug request failed:', textStatus, errorThrown);
            }
            console.error('Response details:', jqXHR.responseText);
        });
    };
    
    // ================== ORDER SYNC FUNCTIONALITY ==================
    
    // Order Sync Page Elements
    const loadOrdersButton = $('#load-ecwid-orders-button');
    const orderListContainer = $('#selective-order-list-container');
    const orderSyncInitialInfoDiv = $('#selective-sync-initial-info');
    const importSelectedOrdersButton = $('#import-selected-orders-button');
    const syncAllOrdersButton = $('#sync-all-orders-button');
    const orderStatusFilter = $('#order-status-filter');
    const orderDateFrom = $('#order-date-from');
    const orderDateTo = $('#order-date-to');
    
    // Order sync state
    let ecwidOrdersForSelection = [];
    let currentOrderPage = 1;
    let selectedOrderIds = new Set();
    const ordersPerPage = 15;
    
    // Function to load and display all orders for selection
    function loadAndDisplayOrdersForSelection() {
        if (!loadOrdersButton.length || !orderListContainer.length) {
            if (loadOrdersButton.length && !orderListContainer.length) {
                console.warn("loadOrdersButton exists, but orderListContainer does not. Cannot load orders.");
            }
            if (orderSyncInitialInfoDiv.length) {
                orderSyncInitialInfoDiv.text('');
            }
            return;
        }

        if (loadOrdersButton.hasClass('disabled')) return;

        const originalButtonText = loadOrdersButton.text();
        loadOrdersButton.addClass('disabled').text(ecwid_sync_params.i18n.loading_orders || 'Loading Orders...');
        
        // Get filter values
        const statusFilter = orderStatusFilter.val() || '';
        const dateFrom = orderDateFrom.val() || '';
        const dateTo = orderDateTo.val() || '';
        
        // Create enhanced loading interface
        const loadingHtml = `
            <div class="ecwid-loading-container" style="text-align: center; padding: 40px 20px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px;">
                <div class="ecwid-loading-spinner" style="margin-bottom: 15px;">
                    <span class="dashicons dashicons-update" style="font-size: 24px; animation: ecwid-spin 1s linear infinite; color: #0073aa;"></span>
                </div>
                <div class="ecwid-loading-title" style="font-size: 16px; font-weight: bold; margin-bottom: 8px; color: #333;">
                    ⏳ Loading Order Data
                </div>
                <div class="ecwid-loading-status" style="font-size: 14px; color: #666; margin-bottom: 10px;">
                    Making API calls to fetch orders with customer associations...
                </div>
                <div class="ecwid-loading-progress" style="font-size: 12px; color: #999;">
                    This may take a moment for large stores with many orders
                </div>
            </div>
            <style>
                @keyframes ecwid-spin {
                    from { transform: rotate(0deg); }
                    to { transform: rotate(360deg); }
                }
            </style>
        `;
        
        orderListContainer.html(loadingHtml).show();
        if (orderSyncInitialInfoDiv.length) {
            orderSyncInitialInfoDiv.html(`
                <div style="padding: 15px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px; margin: 10px 0;">
                    <strong>⏳ Loading Orders...</strong><br>
                    <span style="font-size: 12px; color: #666;">Fetching order data and matching with customers...</span>
                </div>
            `);
        }

        // AJAX call to fetch orders
        $.ajax({
            url: ecwid_sync_params.ajax_url,
            type: 'POST',
            data: {
                action: 'ecwid_wc_fetch_orders_for_display',
                nonce: ecwid_sync_params.nonce,
                status_filter: statusFilter,
                date_from: dateFrom,
                date_to: dateTo
            },
            dataType: 'json',
            timeout: 180000 // 3 minutes timeout for large stores
        })
        .done(function(response) {
            if (response.success && response.data && response.data.orders) {
                ecwidOrdersForSelection = response.data.orders;
                const totalFound = parseInt(response.data.total_found) || 0;
                const apiCalls = parseInt(response.data.api_calls_made) || 1;
                const totalAvailable = parseInt(response.data.total_available) || totalFound;
                const filtersApplied = response.data.filters_applied || {};

                // Enhanced status message
                let statusMessage = `${totalFound} orders loaded`;
                if (apiCalls > 1) {
                    statusMessage += ` (${apiCalls} API calls)`;
                }
                if (totalAvailable && totalAvailable !== totalFound) {
                    statusMessage += ` out of ${totalAvailable} available`;
                }
                
                // Add filter info
                let filterInfo = '';
                if (filtersApplied.status) {
                    filterInfo += ` | Status: ${filtersApplied.status}`;
                }
                if (filtersApplied.date_from || filtersApplied.date_to) {
                    filterInfo += ` | Date filtered`;
                }
                statusMessage += filterInfo + '. Select individual orders to import.';

                if (orderSyncInitialInfoDiv.length) {
                    const styledStatusHtml = `
                        <div style="background: #f9f9f9; padding: 10px; margin-bottom: 10px; border: 1px solid #ddd; border-radius: 4px;">
                            <p style="margin: 0 0 5px 0; font-weight: bold;">📦 Order Loading Complete:</p>
                            <p style="margin: 0; font-size: 12px; color: #666; font-style: normal;">${statusMessage}</p>
                            <p style="margin: 5px 0 0 0; font-size: 11px; color: #0073aa;"><strong>Note:</strong> Orders will be matched with existing customers and attached to their accounts.</p>
                        </div>
                    `;
                    orderSyncInitialInfoDiv.html(styledStatusHtml);
                }

                // Reset pagination and selection state
                currentOrderPage = 1;
                selectedOrderIds.clear();

                renderOrderSelectionList(ecwidOrdersForSelection);
                
                // Show final success message with fade out
                $('.ecwid-loading-status').html(`✅ ${totalFound} orders ready for import!`);
                setTimeout(function() {
                    $('.ecwid-loading-container').fadeOut(800);
                }, 2000);

                if (ecwidOrdersForSelection.length > 0) {
                    importSelectedOrdersButton.show();
                } else {
                    orderListContainer.html('<p style="color: orange;">No orders found matching your criteria.</p>');
                    importSelectedOrdersButton.hide();
                }
            } else {
                $('.ecwid-loading-spinner .dashicons').removeClass('dashicons-update').addClass('dashicons-no-alt');
                $('.ecwid-loading-title').html('❌ Loading Failed');
                $('.ecwid-loading-status').html('Error: ' + (response.data && response.data.message ? response.data.message : 'Unknown error occurred'));
                
                console.log('=== ORDER LOADING ERROR ===');
                console.log('Response success:', response.success);
                console.log('Response data:', response.data);
                console.log('==============================');
                
                const errorMsg = response.data && response.data.message ? response.data.message : 'No orders found or failed to fetch.';
                orderListContainer.html('<p style="color:red;">' + sanitizeHTML(errorMsg) + '</p>');
                if (orderSyncInitialInfoDiv.length) {
                    orderSyncInitialInfoDiv.html('<span style="color:red;">' + sanitizeHTML(errorMsg) + '</span>');
                }
                
                // Auto-hide error after 5 seconds
                setTimeout(function() {
                    $('.ecwid-loading-container').fadeOut(800);
                }, 5000);
            }
        })
        .fail(function(jqXHR, textStatus, errorThrown) {
            console.error('Order loading AJAX failed:', textStatus, errorThrown);
            const errorMessage = `AJAX Error: ${textStatus}. ${errorThrown}`;
            
            $('.ecwid-loading-spinner .dashicons').removeClass('dashicons-update').addClass('dashicons-no-alt');
            $('.ecwid-loading-title').html('❌ Connection Failed');
            $('.ecwid-loading-status').html(errorMessage);
            
            orderListContainer.html('<p style="color:red;">Failed to load orders. Please check your connection and try again.</p>');
            if (orderSyncInitialInfoDiv.length) {
                orderSyncInitialInfoDiv.html('<span style="color:red;">Failed to load orders.</span>');
            }
            
            setTimeout(function() {
                $('.ecwid-loading-container').fadeOut(800);
            }, 5000);
        })
        .always(function() {
            loadOrdersButton.removeClass('disabled').text(originalButtonText);
        });
    }

    // Function to render order selection list with pagination
    function renderOrderSelectionList(orders, page = 1) {
        currentOrderPage = page;
        
        const totalOrders = orders.length;
        const totalPages = Math.ceil(totalOrders / ordersPerPage);
        const startIndex = (page - 1) * ordersPerPage;
        const endIndex = Math.min(startIndex + ordersPerPage, totalOrders);
        const ordersForPage = orders.slice(startIndex, endIndex);
        
        // Create pagination container if it doesn't exist
        let orderPaginationContainer = $('#order-pagination-container');
        if (!orderPaginationContainer.length) {
            loadOrdersButton.after('<div id="order-pagination-container" style="margin-top: 10px;"></div>');
            orderPaginationContainer = $('#order-pagination-container');
        }
        
        // Render pagination controls
        if (totalPages > 1) {
            const paginationHtml = `
                <div class="order-pagination" style="text-align: center; margin-bottom: 15px; padding: 10px; background: #fff; border: 1px solid #ddd; border-radius: 4px;">
                    <button id="prev-order-page-btn" class="button button-secondary" ${page <= 1 ? 'disabled' : ''} style="margin-right: 10px;">← Previous</button>
                    <span style="margin: 0 15px; font-weight: bold;">Page ${page} of ${totalPages}</span>
                    <button id="next-order-page-btn" class="button button-secondary" ${page >= totalPages ? 'disabled' : ''} style="margin-left: 10px;">Next →</button>
                    ${selectedOrderIds.size > 0 ? `<div style="margin-top: 8px; font-size: 12px; color: #0073aa;">📋 ${selectedOrderIds.size} orders selected across all pages</div>` : ''}
                </div>
            `;
            orderPaginationContainer.html(paginationHtml);
        } else {
            orderPaginationContainer.html('');
        }
        
        let html = `<div style="background: #f9f9f9; padding: 10px; margin-bottom: 10px; border: 1px solid #ddd; border-radius: 4px;">`;
        html += `<p style="margin: 0 0 5px 0; font-weight: bold;">📦 Select Orders to Import:</p>`;
        html += '<p style="margin: 0; font-size: 12px; color: #666;">✓ Check individual orders to import them. Orders will be linked to their respective customer accounts.</p>';
        if (totalPages > 1) {
            html += `<p style="margin: 5px 0 0 0; font-size: 12px; color: #0073aa; font-weight: bold;">📄 Showing ${startIndex + 1}-${endIndex} of ${totalOrders} orders (Page ${page} of ${totalPages})</p>`;
        }
        html += '</div>';
        
        html += '<ul style="list-style:none; margin:0; padding:0;">';
        html += `<li style="padding-bottom: 8px; margin-bottom: 8px; border-bottom: 2px solid #0073aa; background: #f0f8ff; padding: 8px;">
                    <label style="font-weight: bold; color: #0073aa;">
                        <input type="checkbox" id="select-all-ecwid-orders" style="margin-right: 8px;" /> 
                        Select All/None (${ordersForPage.length} on this page${totalPages > 1 ? ` / ${totalOrders} total` : ''})
                    </label>
                 </li>`;
        
        ordersForPage.forEach(function(order, index) {
            const bgColor = index % 2 === 0 ? '#fff' : '#f9f9f9';
            const isSelected = selectedOrderIds.has(order.orderNumber.toString());
            const orderNumber = order.orderNumber || order.vendorOrderNumber || 'N/A';
            const orderEmail = order.email || 'N/A';
            const orderTotal = order.total ? `$${parseFloat(order.total).toFixed(2)}` : 'N/A';
            const paymentStatus = order.paymentStatus || 'Unknown';
            const fulfillmentStatus = order.fulfillmentStatus || 'Unknown';
            const createDate = order.createDate ? new Date(order.createDate).toLocaleDateString() : 'N/A';
            const customerAssociation = order.customer_association || {};
            
            // Customer association display
            let customerInfo = '';
            if (customerAssociation.wp_user_id) {
                customerInfo = `👤 <span style="color: green;">Customer: ${customerAssociation.wp_user_email} (${customerAssociation.match_method})</span>`;
            } else {
                customerInfo = `👤 <span style="color: orange;">No customer match found</span>`;
            }
            
            html += `<li style="padding: 8px; border-bottom: 1px solid #eee; background: ${bgColor};">
                        <label style="display: flex; align-items: flex-start; cursor: pointer;">
                            <input type="checkbox" class="ecwid-order-select" value="${orderNumber}" ${isSelected ? 'checked' : ''} style="margin-right: 8px; margin-top: 2px;" />
                            <div style="flex: 1;">
                                <strong>Order #${sanitizeHTML(orderNumber.toString())}</strong> - ${orderTotal}<br>
                                <small style="color: #666; line-height: 1.4;">
                                    Email: ${sanitizeHTML(orderEmail)} | Date: ${createDate}<br>
                                    Payment: <span style="color: ${paymentStatus === 'PAID' ? 'green' : 'orange'};">${paymentStatus}</span> | 
                                    Fulfillment: <span style="color: ${fulfillmentStatus === 'SHIPPED' ? 'green' : 'orange'};">${fulfillmentStatus}</span><br>
                                    ${customerInfo}
                                </small>
                            </div>
                        </label>
                     </li>`;
        });
        html += '</ul>';
        
        orderListContainer.html(html);

        // Enhanced Select All/None functionality
        $('#select-all-ecwid-orders').on('change', function() {
            const isChecked = $(this).prop('checked');
            $('.ecwid-order-select').each(function() {
                const orderNumber = $(this).val();
                $(this).prop('checked', isChecked);
                if (isChecked) {
                    selectedOrderIds.add(orderNumber);
                } else {
                    selectedOrderIds.delete(orderNumber);
                }
            });
            updateOrderImportButtonText();
        });

        // Individual order selection
        $('.ecwid-order-select').on('change', function() {
            const orderNumber = $(this).val();
            const isChecked = $(this).prop('checked');
            
            if (isChecked) {
                selectedOrderIds.add(orderNumber);
            } else {
                selectedOrderIds.delete(orderNumber);
            }
            updateOrderImportButtonText();
        });

        // Pagination handlers
        $('#prev-order-page-btn').on('click', function() {
            if (currentOrderPage > 1) {
                renderOrderSelectionList(orders, currentOrderPage - 1);
            }
        });

        $('#next-order-page-btn').on('click', function() {
            if (currentOrderPage < totalPages) {
                renderOrderSelectionList(orders, currentOrderPage + 1);
            }
        });
    }

    // Function to update import button text
    function updateOrderImportButtonText() {
        if (selectedOrderIds.size > 0) {
            importSelectedOrdersButton.text(`${ecwid_sync_params.i18n.import_selected_orders || 'Import Selected'} (${selectedOrderIds.size})`).show();
        } else {
            importSelectedOrdersButton.text(ecwid_sync_params.i18n.import_selected_orders || 'Import Selected Orders').hide();
        }
    }

    // Initialize order sync functionality
    if (loadOrdersButton.length) {
        // Automatically load orders when page loads
        loadAndDisplayOrdersForSelection();

        loadOrdersButton.on('click', function(e) {
            e.preventDefault();
            loadAndDisplayOrdersForSelection();
        });
        
        // Filter change handlers
        orderStatusFilter.on('change', function() {
            loadAndDisplayOrdersForSelection();
        });
        
        orderDateFrom.on('change', function() {
            loadAndDisplayOrdersForSelection();
        });
        
        orderDateTo.on('change', function() {
            loadAndDisplayOrdersForSelection();
        });
    }
    
    // ================== CUSTOMER SYNC FUNCTIONALITY ==================
    
    // Customer Sync Page Elements
    const loadCustomersButton = $('#load-ecwid-customers-button');
    const customerListContainer = $('#selective-customer-list-container');
    const customerSyncInitialInfoDiv = $('#selective-sync-initial-info');
    const importSelectedCustomersButton = $('#import-selected-customers-button');
    const syncAllCustomersButton = $('#sync-all-customers-button');
    
    // Customer sync state
    let ecwidCustomersForSelection = [];
    let currentCustomerPage = 1;
    let selectedCustomerIds = new Set();
    const customersPerPage = 20;
    
    // Function to load and display all customers for selection
    function loadAndDisplayCustomersForSelection() {
        if (!loadCustomersButton.length || !customerListContainer.length) {
            if (loadCustomersButton.length && !customerListContainer.length) {
                console.warn("loadCustomersButton exists, but customerListContainer does not. Cannot load customers.");
            }
            if (customerSyncInitialInfoDiv.length) {
                customerSyncInitialInfoDiv.text('');
            }
            return;
        }

        if (loadCustomersButton.hasClass('disabled')) return;

        const originalButtonText = loadCustomersButton.text();
        loadCustomersButton.addClass('disabled').text(ecwid_sync_params.i18n.loading_customers || 'Loading Customers...');
        
        // Create enhanced loading interface
        const loadingHtml = `
            <div class="ecwid-loading-container" style="text-align: center; padding: 40px 20px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px;">
                <div class="ecwid-loading-spinner" style="margin-bottom: 15px;">
                    <span class="dashicons dashicons-update" style="font-size: 24px; animation: ecwid-spin 1s linear infinite; color: #0073aa;"></span>
                </div>
                <div class="ecwid-loading-title" style="font-size: 16px; font-weight: bold; margin-bottom: 8px; color: #333;">
                    ⏳ Loading Customer Data
                </div>
                <div class="ecwid-loading-status" style="font-size: 14px; color: #666; margin-bottom: 10px;">
                    Making API calls to fetch all customers...
                </div>
                <div class="ecwid-loading-progress" style="font-size: 12px; color: #999;">
                    This may take a moment for large stores
                </div>
            </div>
            <style>
                @keyframes ecwid-spin {
                    from { transform: rotate(0deg); }
                    to { transform: rotate(360deg); }
                }
            </style>
        `;
        
        customerListContainer.html(loadingHtml).show();
        if (customerSyncInitialInfoDiv.length) {
            customerSyncInitialInfoDiv.html(`
                <div style="padding: 15px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px; margin: 10px 0;">
                    <strong>⏳ Loading Customers...</strong><br>
                    <span style="font-size: 12px; color: #666;">Fetching customer data from your Ecwid store...</span>
                </div>
            `);
        }

        // AJAX call to fetch customers
        $.ajax({
            url: ecwid_sync_params.ajax_url,
            type: 'POST',
            data: {
                action: 'ecwid_wc_fetch_customers_for_display',
                nonce: ecwid_sync_params.nonce,
            },
            dataType: 'json',
            timeout: 120000 // 2 minutes timeout for large stores
        })
        .done(function(response) {
            if (response.success && response.data && response.data.customers) {
                ecwidCustomersForSelection = response.data.customers;
                const totalFound = parseInt(response.data.total_found) || 0;
                const apiCalls = parseInt(response.data.api_calls_made) || 1;
                const totalAvailable = parseInt(response.data.total_available) || totalFound;

                // Enhanced status message
                let statusMessage = `All ${totalFound} customers loaded`;
                if (apiCalls > 1) {
                    statusMessage += ` (${apiCalls} API calls)`;
                }
                if (totalAvailable && totalAvailable !== totalFound) {
                    statusMessage += ` out of ${totalAvailable} available`;
                }
                statusMessage += '. Select individual customers to import.';

                if (customerSyncInitialInfoDiv.length) {
                    const styledStatusHtml = `
                        <div style="background: #f9f9f9; padding: 10px; margin-bottom: 10px; border: 1px solid #ddd; border-radius: 4px;">
                            <p style="margin: 0 0 5px 0; font-weight: bold;">👥 Customer Loading Complete:</p>
                            <p style="margin: 0; font-size: 12px; color: #666; font-style: normal;">${statusMessage}</p>
                        </div>
                    `;
                    customerSyncInitialInfoDiv.html(styledStatusHtml);
                }

                // Reset pagination and selection state
                currentCustomerPage = 1;
                selectedCustomerIds.clear();

                renderCustomerSelectionList(ecwidCustomersForSelection);
                
                // Show final success message with fade out
                $('.ecwid-loading-status').html(`✅ ${totalFound} customers ready for import!`);
                setTimeout(function() {
                    $('.ecwid-loading-container').fadeOut(800);
                }, 2000);

                if (ecwidCustomersForSelection.length > 0) {
                    importSelectedCustomersButton.show();
                } else {
                    customerListContainer.html('<p style="color: orange;">No customers found in your Ecwid store.</p>');
                    importSelectedCustomersButton.hide();
                }
            } else {
                $('.ecwid-loading-spinner .dashicons').removeClass('dashicons-update').addClass('dashicons-no-alt');
                $('.ecwid-loading-title').html('❌ Loading Failed');
                $('.ecwid-loading-status').html('Error: ' + (response.data && response.data.message ? response.data.message : 'Unknown error occurred'));
                
                console.log('=== CUSTOMER LOADING ERROR ===');
                console.log('Response success:', response.success);
                console.log('Response data:', response.data);
                console.log('==============================');
                
                const errorMsg = response.data && response.data.message ? response.data.message : 'No customers found or failed to fetch.';
                customerListContainer.html('<p style="color:red;">' + sanitizeHTML(errorMsg) + '</p>');
                if (customerSyncInitialInfoDiv.length) {
                    customerSyncInitialInfoDiv.html('<span style="color:red;">' + sanitizeHTML(errorMsg) + '</span>');
                }
                
                // Auto-hide error after 5 seconds
                setTimeout(function() {
                    $('.ecwid-loading-container').fadeOut(800);
                }, 5000);
            }
        })
        .fail(function(jqXHR, textStatus, errorThrown) {
            console.error('Customer loading AJAX failed:', textStatus, errorThrown);
            const errorMessage = `AJAX Error: ${textStatus}. ${errorThrown}`;
            
            $('.ecwid-loading-spinner .dashicons').removeClass('dashicons-update').addClass('dashicons-no-alt');
            $('.ecwid-loading-title').html('❌ Connection Failed');
            $('.ecwid-loading-status').html(errorMessage);
            
            customerListContainer.html('<p style="color:red;">Failed to load customers. Please check your connection and try again.</p>');
            if (customerSyncInitialInfoDiv.length) {
                customerSyncInitialInfoDiv.html('<span style="color:red;">Failed to load customers.</span>');
            }
            
            setTimeout(function() {
                $('.ecwid-loading-container').fadeOut(800);
            }, 5000);
        })
        .always(function() {
            loadCustomersButton.removeClass('disabled').text(originalButtonText);
        });
    }

    // Function to render customer selection list with pagination
    function renderCustomerSelectionList(customers, page = 1) {
        currentCustomerPage = page;
        
        const totalCustomers = customers.length;
        const totalPages = Math.ceil(totalCustomers / customersPerPage);
        const startIndex = (page - 1) * customersPerPage;
        const endIndex = Math.min(startIndex + customersPerPage, totalCustomers);
        const customersForPage = customers.slice(startIndex, endIndex);
        
        // Create pagination container if it doesn't exist
        let customerPaginationContainer = $('#customer-pagination-container');
        if (!customerPaginationContainer.length) {
            loadCustomersButton.after('<div id="customer-pagination-container" style="margin-top: 10px;"></div>');
            customerPaginationContainer = $('#customer-pagination-container');
        }
        
        // Render pagination controls
        if (totalPages > 1) {
            const paginationHtml = `
                <div class="customer-pagination" style="text-align: center; margin-bottom: 15px; padding: 10px; background: #fff; border: 1px solid #ddd; border-radius: 4px;">
                    <button id="prev-customer-page-btn" class="button button-secondary" ${page <= 1 ? 'disabled' : ''} style="margin-right: 10px;">← Previous</button>
                    <span style="margin: 0 15px; font-weight: bold;">Page ${page} of ${totalPages}</span>
                    <button id="next-customer-page-btn" class="button button-secondary" ${page >= totalPages ? 'disabled' : ''} style="margin-left: 10px;">Next →</button>
                    ${selectedCustomerIds.size > 0 ? `<div style="margin-top: 8px; font-size: 12px; color: #0073aa;">📋 ${selectedCustomerIds.size} customers selected across all pages</div>` : ''}
                </div>
            `;
            customerPaginationContainer.html(paginationHtml);
        } else {
            customerPaginationContainer.html('');
        }
        
        let html = `<div style="background: #f9f9f9; padding: 10px; margin-bottom: 10px; border: 1px solid #ddd; border-radius: 4px;">`;
        html += `<p style="margin: 0 0 5px 0; font-weight: bold;">👥 Select Customers to Import:</p>`;
        html += '<p style="margin: 0; font-size: 12px; color: #666;">✓ Check individual customers to import them, or select multiple customers for batch import.</p>';
        if (totalPages > 1) {
            html += `<p style="margin: 5px 0 0 0; font-size: 12px; color: #0073aa; font-weight: bold;">📄 Showing ${startIndex + 1}-${endIndex} of ${totalCustomers} customers (Page ${page} of ${totalPages})</p>`;
        }
        html += '</div>';
        
        html += '<ul style="list-style:none; margin:0; padding:0;">';
        html += `<li style="padding-bottom: 8px; margin-bottom: 8px; border-bottom: 2px solid #0073aa; background: #f0f8ff; padding: 8px;">
                    <label style="font-weight: bold; color: #0073aa;">
                        <input type="checkbox" id="select-all-ecwid-customers" style="margin-right: 8px;" /> 
                        Select All/None (${customersForPage.length} on this page${totalPages > 1 ? ` / ${totalCustomers} total` : ''})
                    </label>
                 </li>`;
        
        customersForPage.forEach(function(customer, index) {
            const bgColor = index % 2 === 0 ? '#fff' : '#f9f9f9';
            const isSelected = selectedCustomerIds.has(customer.id.toString());
            const customerName = customer.name || 'N/A';
            const customerEmail = customer.email || 'N/A';
            const ordersCount = customer.stats && customer.stats.ordersCount ? customer.stats.ordersCount : 0;
            const totalOrderValue = customer.stats && customer.stats.totalOrderValue ? customer.stats.totalOrderValue : 0;
            const registeredDate = customer.registered ? new Date(customer.registered).toLocaleDateString() : 'N/A';
            
            html += `<li style="padding: 8px; border-bottom: 1px solid #eee; background: ${bgColor};">
                        <label style="display: flex; align-items: center; cursor: pointer;">
                            <input type="checkbox" class="ecwid-customer-select" value="${customer.id}" ${isSelected ? 'checked' : ''} style="margin-right: 8px;" />
                            <div>
                                <strong>${sanitizeHTML(customerName)}</strong><br>
                                <small style="color: #666;">
                                    Email: ${sanitizeHTML(customerEmail)} | ID: ${customer.id} | 
                                    Orders: ${ordersCount} | Total Value: $${totalOrderValue.toFixed(2)} | 
                                    Registered: ${registeredDate}
                                </small>
                            </div>
                        </label>
                     </li>`;
        });
        html += '</ul>';
        
        customerListContainer.html(html);

        // Enhanced Select All/None functionality
        $('#select-all-ecwid-customers').on('change', function() {
            const isChecked = $(this).prop('checked');
            $('.ecwid-customer-select').each(function() {
                const customerId = $(this).val();
                $(this).prop('checked', isChecked);
                if (isChecked) {
                    selectedCustomerIds.add(customerId);
                } else {
                    selectedCustomerIds.delete(customerId);
                }
            });
            updateCustomerImportButtonText();
        });

        // Individual customer selection
        $('.ecwid-customer-select').on('change', function() {
            const customerId = $(this).val();
            const isChecked = $(this).prop('checked');
            
            if (isChecked) {
                selectedCustomerIds.add(customerId);
            } else {
                selectedCustomerIds.delete(customerId);
            }
            updateCustomerImportButtonText();
        });

        // Pagination handlers
        $('#prev-customer-page-btn').on('click', function() {
            if (currentCustomerPage > 1) {
                renderCustomerSelectionList(customers, currentCustomerPage - 1);
            }
        });

        $('#next-customer-page-btn').on('click', function() {
            if (currentCustomerPage < totalPages) {
                renderCustomerSelectionList(customers, currentCustomerPage + 1);
            }
        });
    }

    // Function to update import button text
    function updateCustomerImportButtonText() {
        if (selectedCustomerIds.size > 0) {
            importSelectedCustomersButton.text(`${ecwid_sync_params.i18n.import_selected_customers || 'Import Selected'} (${selectedCustomerIds.size})`).show();
        } else {
            importSelectedCustomersButton.text(ecwid_sync_params.i18n.import_selected_customers || 'Import Selected Customers').hide();
        }
    }

    // Initialize customer sync functionality
    if (loadCustomersButton.length) {
        // Automatically load all customers when page loads
        loadAndDisplayCustomersForSelection();

        loadCustomersButton.on('click', function(e) {
            e.preventDefault();
            loadAndDisplayCustomersForSelection();
        });
    }

    // Initialize page-specific functionality
    if (window.location.href.indexOf('ecwid-sync-settings') !== -1) {
        initializeSettingsPage();
    }
    
})(jQuery);