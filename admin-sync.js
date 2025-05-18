(function($) {
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
            variations_to_sync_info: 'Variations to sync: {count}',
            syncing_item_of_total: 'Syncing {syncType}: {current} of {total}...',
            load_products: 'Load Ecwid Products for Selection',
            loading_products: 'Loading Products...',
            load_ecwid_categories: 'Load Ecwid Category List',
            loading_ecwid_categories: 'Loading Categories...',
            no_categories_found_display: 'No categories found in your Ecwid store or an error occurred.',
            categories_loaded_for_display: '{count} categories loaded for display.',
            import_selected: 'Import Selected Products',
            importing_selected: 'Importing Selected...',
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
            load_sync_preview: 'Load Sync Preview',
            loading_sync_preview: 'Loading sync preview data...',
            preview_loaded_ready_to_sync: 'Preview loaded. Ready to start full sync.',
            categories_for_preview: 'Categories to be Synced:',
            products_for_preview: 'Products to be Synced:',
            preview_load_error: 'Error loading preview data. Please try again or proceed with sync.',
            variations_count_in_preview: 'Variation count will be determined when sync starts.',
            products_available_info: 'Ecwid products available for selection: {count}',
            categories_step_complete: 'Categories step complete! Starting product sync...',
            products_step_complete: 'Products step complete!',
            // Add any other i18n strings used in the JS with their defaults
        };
        for (const key in i18n_defaults) {
            if (!i18n[key]) {
                i18n[key] = i18n_defaults[key];
            }
        }

        const fullSyncSteps = (ecwid_sync_params.sync_steps && ecwid_sync_params.sync_steps.length > 0) ? ecwid_sync_params.sync_steps : ['categories', 'products'];
        const totalFullSyncSteps = fullSyncSteps.length;
        const variationBatchSize = parseInt(ecwid_sync_params.variation_batch_size) || 10; // Ensure it's an integer

        // --- Utility Functions ---
        function sanitizeHTML(str) {
            const temp = document.createElement('div');
            temp.textContent = str;
            return temp.innerHTML;
        }

        function capitalizeFirstLetter(string) {
            if (!string) return '';
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
                }
            }

            let currentStepWeight = 0;
            if (fullSyncSteps[currentFullSyncStepIndex] === 'categories' && totalCategoriesToSync > 0) {
                currentStepWeight = (currentStepProgressPercent / 100) * totalCategoriesToSync;
            } else if (fullSyncSteps[currentFullSyncStepIndex] === 'products' && totalProductsToSync > 0) {
                currentStepWeight = (currentStepProgressPercent / 100) * totalProductsToSync;
            }
            
            // If current step has no items, but it's completed, consider its "weight" fully contributed if it's not the only step
            if ( (fullSyncSteps[currentFullSyncStepIndex] === 'categories' && totalCategoriesToSync === 0 && currentStepProgressPercent >=100) ||
                 (fullSyncSteps[currentFullSyncStepIndex] === 'products' && totalProductsToSync === 0 && currentStepProgressPercent >=100) ) {
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
        const fullSyncProgressContainer = $('#full-sync-progress-container');

        // Category Sync Page UI Elements
        const categoryPageSyncButton = $('#category-page-sync-button');
        const categoryPageSyncProgressBar = $('#category-page-sync-bar');
        const categoryPageSyncStatusDiv = $('#category-page-sync-status');
        const categoryPageSyncLogDiv = $('#category-page-sync-log');
        const categoryPageSyncProgressBarContainer = $('#category-page-sync-progress-container');
        const loadCategoriesButton = $('#load-ecwid-categories-button');
        const categoryListContainer = $('#category-list-container');
        const categorySyncInitialInfoDiv = $('#category-sync-initial-info');
        const fixHierarchyButton = $('#fix-category-hierarchy-button');

        // Selective Product Sync UI Elements
        const loadProductsButton = $('#load-ecwid-products-button');
        const productListContainer = $('#selective-product-list-container');
        const importSelectedButton = $('#import-selected-products-button');
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
        let grandTotalAllItemsForSync = 0; // This will be categories + products

        let fullSyncVariationQueue = []; // <-- ADDED: Queue for products needing variation sync in Full Sync
        let currentFullSyncVariationProductData = null; // <-- ADDED: Data for the product whose variations are currently being synced

        // Store continuation data for parent batch processing
        let fullSyncParentContinuation = {
            hasMore: false,
            nextOffset: 0,
            syncType: '',
            totalItems: 0
        };


        // Selective Product Sync State
        let ecwidProductsForSelection = []; // For selective product sync
        let productsToImportSelectedIds = []; // For selective product sync
        let currentSelectiveImportProductIndex = 0; // For selective product sync
        let currentProductVariationData = null; // For selective product variation batching

        let animationInterval = null; // For status text animation

        // --- Helper Functions ---
        function sanitizeHTML(str) {
            const temp = document.createElement('div');
            temp.textContent = str;
            return temp.innerHTML;
        }

        function logMessage(logDiv, message, type) {
            let color = 'black';
            if (type === 'success') color = 'green';
            else if (type === 'error') color = 'red';
            else if (type === 'info') color = '#005a9c'; // Darker blue for info
            else if (type === 'warning') color = '#e69500'; // Darker orange for warning

            const cleanMessage = $('<div/>').text(message).html();
            logDiv.append(`<p style="color:${color}; margin: 2px 0; padding: 0; white-space: pre-wrap; word-wrap: break-word;">${cleanMessage}</p>`);
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
            updateStatus(statusDiv, statusMessage);
            logMessage(logDiv, (syncType ? `${syncType}: ` : '') + 'Error - ' + errorMessage, 'error');
            
            if (responseData && responseData.details) {
                console.error("Sync Error Details" + (syncType ? ` for ${syncType}` : '') + ":", responseData.details);
                logMessage(logDiv, "Details: " + JSON.stringify(responseData.details), 'error');
            }
             if (responseData && responseData.logs && Array.isArray(responseData.logs)) {
                responseData.logs.forEach(logEntry => categorizeAndLog(logDiv, logEntry));
            }
            if (buttonElem && buttonText) {
                buttonElem.removeClass('disabled').text(buttonText);
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
            if (!fullSyncCountsInfoDiv.length || !fullSyncButton.length) return; 

            fullSyncCountsInfoDiv.text(i18n.fetching_counts || 'Fetching item counts...');
            
            $.ajax({
                url: ajax_url,
                method: 'POST',
                data: { action: 'ecwid_wc_fetch_full_sync_counts', nonce: nonce },
                success: function(response) {
                    if (response.success) {
                        totalCategoriesToSync = parseInt(response.data.categories_count) || 0;
                        totalProductsToSync = parseInt(response.data.products_count) || 0;
                        grandTotalAllItemsForSync = totalCategoriesToSync + totalProductsToSync; // CALCULATE GRAND TOTAL

                        let countText = (i18n.categories_to_sync_info || 'Categories to sync: {count}').replace('{count}', totalCategoriesToSync) + '<br>' +
                                        (i18n.products_to_sync_info || 'Products to sync: {count}').replace('{count}', totalProductsToSync) + '<br>' +
                                        (i18n.variations_count_in_preview || 'Variation count will be determined when sync starts.');
                        fullSyncCountsInfoDiv.html(countText);
                        fullSyncStatusDiv.text(i18n.preview_loaded_ready_to_sync || 'Preview loaded. Ready to start full sync.');
                        fullSyncButton.removeClass('disabled').prop('disabled', false).show(); 
                    } else {
                        const errorMsg = response.data && response.data.message ? response.data.message : 'Error fetching counts.';
                        fullSyncCountsInfoDiv.html('<span style="color:red;">' + sanitizeHTML(errorMsg) + '</span>');
                        fullSyncStatusDiv.html('<span style="color:red;">' + sanitizeHTML(errorMsg) + '</span>');
                        fullSyncButton.show().removeClass('disabled').prop('disabled', false); // Still show, allow user to try
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    fullSyncCountsInfoDiv.html('<span style="color:red;">AJAX error fetching counts: ' + sanitizeHTML(textStatus) + '</span>');
                    fullSyncStatusDiv.html('<span style="color:red;">AJAX error fetching counts.</span>');
                    fullSyncButton.show().removeClass('disabled').prop('disabled', false); // Still show
                }
            });
        }

        // Function to load and display the sync preview
        function loadAndDisplayFullSyncPreview() {
            // Corrected guard condition: if EITHER is missing, return.
            if (!loadFullSyncPreviewButton.length || !fullSyncPreviewContainer.length) {
                 // If the container is missing but button isn't, it implies we are not on the full sync page or HTML is broken.
                if (loadFullSyncPreviewButton.length && !fullSyncPreviewContainer.length) {
                    console.warn("loadFullSyncPreviewButton exists, but fullSyncPreviewContainer does not. Cannot load preview.");
                }
                return;
            }

            const $button = loadFullSyncPreviewButton; 
            const originalButtonText = $button.length ? $button.text() : i18n.load_sync_preview;

            if ($button.length) {
                $button.text(i18n.loading_sync_preview).addClass('disabled').prop('disabled', true);
            }
            fullSyncStatusDiv.text(i18n.loading_sync_preview);
            fullSyncCountsInfoDiv.text(i18n.loading_sync_preview);
            fullSyncCategoryPreviewList.html(`<p>${i18n.loading_ecwid_categories}</p>`);
            fullSyncProductPreviewList.html(`<p>${i18n.loading_products}</p>`);
            fullSyncPreviewContainer.show();
            fullSyncButton.hide().addClass('disabled').prop('disabled', true); // Hide and disable start button until preview and counts are loaded

            const fetchCategories = $.ajax({
                url: ajax_url,
                method: 'POST',
                data: { action: 'ecwid_wc_fetch_categories_for_display', nonce: nonce }
            });

            const fetchProducts = $.ajax({
                url: ajax_url,
                method: 'POST',
                data: { action: 'ecwid_wc_fetch_products_for_selection', nonce: nonce }
            });

            $.when(fetchCategories, fetchProducts).done(function(categoriesResponse, productsResponse) {
                let catData = categoriesResponse[0]; 
                let prodData = productsResponse[0];
                let catCount = 0;
                let prodCount = 0;

                // Populate Categories
                if (catData.success && catData.data && catData.data.categories) {
                    catCount = catData.data.total_count || catData.data.categories.length;
                    if (catData.data.categories.length > 0) {
                        let catListHtml = '<ul style="list-style: disc; padding-left: 20px; margin:0;">';
                        catData.data.categories.forEach(function(category) {
                            catListHtml += `<li style="padding: 1px 0;">${sanitizeHTML(category.name)} <span style="font-size:0.85em; color:#777;">(ID: ${category.id || 'N/A'})</span></li>`;
                        });
                        catListHtml += '</ul>';
                        fullSyncCategoryPreviewList.html(catListHtml);
                    } else {
                        fullSyncCategoryPreviewList.html(`<p>${i18n.no_categories_found_display}</p>`);
                    }
                } else {
                    fullSyncCategoryPreviewList.html(`<p style="color:red;">${sanitizeHTML((catData.data && catData.data.message) || i18n.ajax_error)}</p>`);
                }

                // Populate Products
                if (prodData.success && prodData.data && prodData.data.products) {
                    prodCount = prodData.data.total_found || prodData.data.products.length;
                    if (prodData.data.products.length > 0) {
                        let prodListHtml = '<ul style="list-style: disc; padding-left: 20px; margin:0;">';
                        prodData.data.products.forEach(function(product) {
                            prodListHtml += `<li style="padding: 1px 0;">${sanitizeHTML(product.name)} <span style="font-size:0.85em; color:#777;">(SKU: ${product.sku || 'N/A'}, ID: ${product.id || 'N/A'})</span></li>`;
                        });
                        prodListHtml += '</ul>';
                        fullSyncProductPreviewList.html(prodListHtml);
                    } else {
                        fullSyncProductPreviewList.html(`<p>${i18n.no_products_found}</p>`);
                    }
                } else {
                    fullSyncProductPreviewList.html(`<p style="color:red;">${sanitizeHTML((prodData.data && prodData.data.message) || i18n.ajax_error)}</p>`);
                }
                
                // Update counts with preview data, then fetch more accurate counts
                fullSyncCountsInfoDiv.html(
                    `${i18n.categories_to_sync_info.replace('{count}', catCount)}<br>` +
                    `${i18n.products_to_sync_info.replace('{count}', prodCount)}<br>` +
                    `${i18n.variations_count_in_preview}`
                );
                // Now fetch the more accurate counts which will also enable the sync button
                fetchAndDisplayFullSyncCounts(); 

            }).fail(function() {
                fullSyncStatusDiv.html(`<span style="color:red;">${i18n.preview_load_error}</span>`);
                fullSyncCountsInfoDiv.text('');
                fullSyncCategoryPreviewList.html(`<p style="color:red;">${i18n.preview_load_error}</p>`);
                fullSyncProductPreviewList.html(`<p style="color:red;">${i18n.preview_load_error}</p>`);
                fullSyncButton.show().removeClass('disabled').prop('disabled', false); // Show button even on fail, user might want to try sync
            }).always(function() {
                if ($button.length) {
                    $button.text(originalButtonText).removeClass('disabled').prop('disabled', false);
                }
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

        if (fullSyncButton.length) { // This check is fine for attaching the click handler
            fullSyncButton.on('click', function(e) {
                e.preventDefault();
                if (fullSyncButton.hasClass('disabled')) return;

                // Ensure totalCategoriesToSync and totalProductsToSync are populated
                // If they are still 0, it means fetchAndDisplayFullSyncCounts might have failed
                // or not completed. For robustness, you could re-trigger it or alert the user.
                // For now, we assume they are set if the button is enabled.

                fullSyncButton.addClass('disabled').text(i18n.syncing_button);
                fullSyncStatusDiv.text(i18n.sync_starting); // Initial status
                updateProgressBar(fullSyncProgressBar, 0);
                fullSyncProgressContainer.show(); // Ensure progress bar container is visible
                fullSyncLogDiv.html('');
                currentFullSyncStepIndex = 0;
                logMessage(fullSyncLogDiv, i18n.sync_starting, 'info');
                // If counts were not fetched successfully, fetchAndDisplayFullSyncCounts might have left button enabled with error.
                // Optionally, re-check counts or ensure they are valid before proceeding.
                // For now, assume if button is clickable, we proceed.
                processNextFullSyncStep();
            });
        }

        function processNextFullSyncStep() {
            if (currentFullSyncStepIndex < totalFullSyncSteps) {
                const syncType = fullSyncSteps[currentFullSyncStepIndex];
                let currentTotalForStep = 0;
                if (syncType === 'categories') {
                    currentTotalForStep = totalCategoriesToSync;
                } else if (syncType === 'products') {
                    currentTotalForStep = totalProductsToSync;
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
                updateStatus(fullSyncStatusDiv, i18n.sync_complete);
                logMessage(fullSyncLogDiv, i18n.sync_complete, 'success');
                fullSyncButton.removeClass('disabled').text(i18n.start_sync);
                // Ensure progress bar hits 100% at the very end
                // It should be very close or at 100 already if grandTotalAllItemsForSync was > 0
                updateProgressBar(fullSyncProgressBar, 100); 
            }
        }

        function processFullSyncBatch(syncType, offset, totalKnownItems) { // totalKnownItems is for the current step
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
                                        original_options: itemResult.original_options || [],
                                        current_variation_offset: 0
                                    });
                                    logMessage(fullSyncLogDiv, `[INFO] Queued product ${itemResult.item_name} (WC ID: ${itemResult.wc_product_id}) for variation processing (${itemResult.total_combinations} variations).`, 'info');
                                }
                            });
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
                    handleAjaxError(fullSyncStatusDiv, fullSyncLogDiv, fullSyncButton, i18n.start_sync, syncType, { message: `${textStatus} ${errorThrown || ''}` }, true);
                }
            });
        }

        // New function to decide what to do after a parent batch or a variation product is processed
        function handleFullSyncContinuation() {
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
            if (!currentFullSyncVariationProductData) {
                logMessage(fullSyncLogDiv, "[ERROR] currentFullSyncVariationProductData is null in processFullSyncVariationBatchLoop. Attempting to continue.", 'error');
                handleFullSyncContinuation(); // Try to continue with next in queue or parent batch
                return;
            }

            const { wc_product_id, ecwid_product_id, item_name, sku, all_combinations, total_combinations, original_options, current_variation_offset } = currentFullSyncVariationProductData;

            if (current_variation_offset >= total_combinations) {
                logMessage(fullSyncLogDiv, i18n.variations_imported_successfully.replace('{productName}', sanitizeHTML(item_name)) + " (Full Sync)", 'success');
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
                .replace('{total}', total_combinations) + " (Full Sync)";
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
                    let errorMsg = i18n.error_importing_variations.replace('{productName}', sanitizeHTML(item_name));
                    errorMsg += `: AJAX Error - ${sanitizeHTML(textStatus)} ${sanitizeHTML(errorThrown || '')}` + " (Full Sync)";
                    logMessage(fullSyncLogDiv, errorMsg, 'error');
                    logMessage(fullSyncLogDiv, `Skipping remaining variations for ${sanitizeHTML(item_name)} (Full Sync) due to AJAX error.`, 'warning');
                    currentFullSyncVariationProductData = null;
                    handleFullSyncContinuation(); // Move to next
                }
            });
        }

        function processNextFullSyncStep() {
            if (currentFullSyncStepIndex < totalFullSyncSteps) {
                const syncType = fullSyncSteps[currentFullSyncStepIndex];
                let currentTotalForStep = 0;
                if (syncType === 'categories') {
                    currentTotalForStep = totalCategoriesToSync;
                } else if (syncType === 'products') {
                    currentTotalForStep = totalProductsToSync;
                }
                // Update status to "Syncing {type}: 0 of {total}..."
                const initialStepStatus = i18n.syncing_item_of_total
                    .replace('{syncType}', capitalizeFirstLetter(syncType)) // Use capitalizeFirstLetter
                    .replace('{current}', 0)
                    .replace('{total}', currentTotalForStep > 0 ? currentTotalForStep : 'N/A');
                updateStatus(fullSyncStatusDiv, initialStepStatus);
                processFullSyncBatch(syncType, 0, currentTotalForStep); // Pass the total for this step
            } else {
                stopBatchStatusAnimation();
                updateStatus(fullSyncStatusDiv, i18n.sync_complete);
                logMessage(fullSyncLogDiv, i18n.sync_complete, 'success');
                fullSyncButton.removeClass('disabled').text(i18n.start_sync);
                // Ensure progress bar hits 100% at the very end
                // It should be very close or at 100 already if grandTotalAllItemsForSync was > 0
                updateProgressBar(fullSyncProgressBar, 100); 
            }
        }

        // --- Category Sync Page Logic ---

        // New function to load and display categories
        function loadAndDisplayCategories() {
            // Ensure the button and container exist before proceeding
            if (!loadCategoriesButton.length || !categoryListContainer.length) {
                if (loadCategoriesButton.length && !categoryListContainer.length) {
                    console.warn("loadCategoriesButton exists, but categoryListContainer does not. Cannot load categories.");
                }
                return;
            }

            const $button = loadCategoriesButton; // Use the existing selector
            if ($button.hasClass('disabled')) return;

            const originalButtonText = $button.text();
            $button.text(i18n.loading_ecwid_categories).addClass('disabled').prop('disabled', true);
            categoryListContainer.html(`<p>${i18n.loading_ecwid_categories}</p>`).show();
            categorySyncInitialInfoDiv.text(i18n.loading_ecwid_categories);
            totalCategoriesForCategoryPageSync = 0; // Reset before loading

            $.ajax({
                url: ajax_url,
                method: 'POST',
                data: {
                    action: 'ecwid_wc_fetch_categories_for_display',
                    nonce: nonce
                },
                success: function(response) {
                    if (response.success) {
                        totalCategoriesForCategoryPageSync = parseInt(response.data.total_count) || 0; // STORE TOTAL
                        const fetchedCategories = response.data.categories || [];
                        categorySyncInitialInfoDiv.text(i18n.categories_to_sync_info.replace('{count}', totalCategoriesForCategoryPageSync));
                        if (fetchedCategories.length > 0) {
                            let listHtml = '<ul style="list-style: disc; padding-left: 20px; margin:0;">';
                            fetchedCategories.forEach(function(category) {
                                listHtml += `<li style="padding: 2px 0;">${sanitizeHTML(category.name)} 
                                                <span style="font-size:0.9em; color:#777;">(ID: ${category.id || 'N/A'}, Parent ID: ${category.parentId || '0'})</span>
                                             </li>`;
                            });
                            listHtml += '</ul>';
                            categoryListContainer.html(listHtml);
                            categoryListContainer.append(`<p style="font-size:0.9em; margin-top:5px; font-style:italic;">${i18n.categories_loaded_for_display.replace('{count}', fetchedCategories.length)}</p>`);
                        } else {
                            categoryListContainer.html(`<p>${i18n.no_categories_found_display}</p>`);
                        }
                    } else {
                        const errorMsg = response.data && response.data.message ? response.data.message : i18n.ajax_error;
                        categorySyncInitialInfoDiv.html(`<span style="color:red;">${sanitizeHTML(errorMsg)}</span>`);
                        categoryListContainer.html(`<p style="color:red;">${sanitizeHTML(errorMsg)}</p>`);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    const errorMsg = `${i18n.ajax_error}: ${textStatus} ${errorThrown || ''}`;
                    categorySyncInitialInfoDiv.html(`<span style="color:red;">${sanitizeHTML(errorMsg)}</span>`);
                    categoryListContainer.html(`<p style="color:red;">${sanitizeHTML(errorMsg)}</p>`);
                },
                complete: function() {
                    // Only re-enable the button if it was the one that initiated the call (not auto-load)
                    // For auto-load, the button might not be the direct trigger.
                    // However, it's safe to always try to reset it.
                    $button.text(originalButtonText).removeClass('disabled').prop('disabled', false);
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

        if (categoryPageSyncButton.length) {
            categoryPageSyncButton.on('click', function(e) {
                e.preventDefault();
                if ($(this).hasClass('disabled')) return;

                if (totalCategoriesForCategoryPageSync === 0 && categoryListContainer.find('ul li').length === 0) {
                    logMessage(categoryPageSyncLogDiv, "Warning: Category list not loaded or appears empty. Totals in status might show as N/A. Consider loading categories first.", 'warning');
                }

                $(this).addClass('disabled').text(i18n.syncing_categories_page_button);
                loadCategoriesButton.addClass('disabled').prop('disabled', true); 
                fixHierarchyButton.addClass('disabled').prop('disabled', true);
                
                categoryPageSyncLogDiv.html('');
                updateProgressBar(categoryPageSyncProgressBar, 0);
                categoryPageSyncProgressBarContainer.show();
                
                const initialStatus = i18n.syncing_item_of_total
                    .replace('{syncType}', 'Categories')
                    .replace('{current}', 0)
                    .replace('{total}', totalCategoriesForCategoryPageSync > 0 ? totalCategoriesForCategoryPageSync : 'N/A');
                updateStatus(categoryPageSyncStatusDiv, initialStatus);
                logMessage(categoryPageSyncLogDiv, i18n.sync_starting, 'info');
                
                processCategoryPageSyncBatch('categories', 0, totalCategoriesForCategoryPageSync); 
            });
        }

        function processCategoryPageSyncBatch(syncType, offset, totalKnownCategories) {
            let baseStatusForAnimation = i18n.syncing_just_categories_page_status;
            
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
                    if (response.success) {
                        (response.data.batch_logs || []).forEach(logEntry => categorizeAndLog(categoryPageSyncLogDiv, logEntry));
                        
                        let currentProgress = 0;
                        const itemsProcessed = response.data.next_offset || 0;
                        
                        // Use the totalKnownCategories (pre-fetched) for progress calculation.
                        // If it's 0, progress will be based on has_more, or jump to 100.
                        const totalForCalc = totalKnownCategories > 0 ? totalKnownCategories : 0;

                        if (totalForCalc > 0) {
                            currentProgress = (itemsProcessed / totalForCalc) * 100;
                        } else if (response.data.has_more === false) { 
                            currentProgress = 100; // If no total known and no more items, assume 100%
                        }
                        currentProgress = Math.min(100, Math.max(0, currentProgress));
                        updateProgressBar(categoryPageSyncProgressBar, currentProgress);
                        
                        const statusUpdate = i18n.syncing_item_of_total
                            .replace('{syncType}', 'Categories')
                            .replace('{current}', itemsProcessed)
                            .replace('{total}', totalForCalc > 0 ? totalForCalc : 'N/A');
                        updateStatus(categoryPageSyncStatusDiv, statusUpdate);

                        if (response.data.has_more) {
                            processCategoryPageSyncBatch(syncType, response.data.next_offset, totalKnownCategories); 
                        } else {
                            updateStatus(categoryPageSyncStatusDiv, i18n.category_sync_page_complete);
                            logMessage(categoryPageSyncLogDiv, i18n.category_sync_page_complete, 'success');
                            categoryPageSyncButton.removeClass('disabled').text(i18n.start_category_sync_page);
                            loadCategoriesButton.removeClass('disabled').prop('disabled', false);
                            fixHierarchyButton.removeClass('disabled').prop('disabled', false);
                            updateProgressBar(categoryPageSyncProgressBar, 100); 
                        }
                    } else {
                        handleAjaxError(categoryPageSyncStatusDiv, categoryPageSyncLogDiv, categoryPageSyncButton, i18n.start_category_sync_page, syncType, response.data);
                        loadCategoriesButton.removeClass('disabled').prop('disabled', false);
                        fixHierarchyButton.removeClass('disabled').prop('disabled', false);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    stopBatchStatusAnimation();
                    handleAjaxError(categoryPageSyncStatusDiv, categoryPageSyncLogDiv, categoryPageSyncButton, i18n.start_category_sync_page, syncType, { message: `${textStatus} ${errorThrown || ''}` }, true);
                    loadCategoriesButton.removeClass('disabled').prop('disabled', false);
                    fixHierarchyButton.removeClass('disabled').prop('disabled', false);
                }
            });
        }


        // --- Selective Product Sync Logic ---

        // New function to load and display products for selection
        function loadAndDisplayProductsForSelection() {
            // Ensure the button and container exist before proceeding
            if (!loadProductsButton.length || !productListContainer.length) {
                if (loadProductsButton.length && !productListContainer.length) {
                    console.warn("loadProductsButton exists, but productListContainer does not. Cannot load products for selection.");
                }
                // Also check for the new info div
                if (selectiveSyncInitialInfoDiv.length) {
                    selectiveSyncInitialInfoDiv.text(''); // Clear it if we can't proceed
                }
                return;
            }

            if (loadProductsButton.hasClass('disabled')) return;

            const originalButtonText = loadProductsButton.text();
            loadProductsButton.addClass('disabled').text(i18n.loading_products);
            productListContainer.html('<p>' + i18n.loading_products + '</p>').show(); // Ensure it's visible
            if (selectiveSyncInitialInfoDiv.length) { // Update initial info div
                selectiveSyncInitialInfoDiv.text(i18n.loading_products);
            }
            importSelectedButton.hide();
            ecwidProductsForSelection = []; // Clear previous list

            $.ajax({
                url: ajax_url,
                method: 'POST',
                data: { action: 'ecwid_wc_fetch_products_for_selection', nonce: nonce },
                success: function(response) {
                    if (response.success && response.data.products) {
                        ecwidProductsForSelection = response.data.products; // Store full product data
                        const totalFound = parseInt(response.data.total_found) || 0; // Get total found from response

                        if (selectiveSyncInitialInfoDiv.length) { // Update initial info div with count
                            selectiveSyncInitialInfoDiv.text(i18n.products_available_info.replace('{count}', totalFound));
                        }

                        renderProductSelectionList(ecwidProductsForSelection);
                        if (ecwidProductsForSelection.length > 0) {
                            importSelectedButton.show();
                        } else {
                            productListContainer.html('<p>' + i18n.no_products_found + '</p>');
                             if (selectiveSyncInitialInfoDiv.length && totalFound === 0) { // If no products found, reflect in info
                                selectiveSyncInitialInfoDiv.text(i18n.no_products_found);
                            }
                        }
                    } else {
                        const errorMsg = response.data && response.data.message ? response.data.message : i18n.no_products_found;
                        productListContainer.html('<p style="color:red;">' + sanitizeHTML(errorMsg) + '</p>');
                        if (selectiveSyncInitialInfoDiv.length) { // Show error in initial info div
                            selectiveSyncInitialInfoDiv.html('<span style="color:red;">' + sanitizeHTML(errorMsg) + '</span>');
                        }
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    const errorText = 'AJAX Error: ' + sanitizeHTML(textStatus) + (errorThrown ? ' - ' + sanitizeHTML(errorThrown) : '');
                    productListContainer.html('<p style="color:red;">' + errorText + '</p>');
                    if (selectiveSyncInitialInfoDiv.length) { // Show AJAX error in initial info div
                         selectiveSyncInitialInfoDiv.html('<span style="color:red;">' + errorText + '</span>');
                    }
                },
                complete: function() {
                    loadProductsButton.removeClass('disabled').text(originalButtonText);
                }
            });
        }

        if (loadProductsButton.length) {
            // Automatically load products if the button exists on page load
            loadAndDisplayProductsForSelection();

            loadProductsButton.on('click', function(e) {
                e.preventDefault();
                // The disabled check is now inside loadAndDisplayProductsForSelection
                loadAndDisplayProductsForSelection(); // Call the main function
            });
        }

        function renderProductSelectionList(products) {
            let html = '<ul style="list-style:none; margin:0; padding:0;">';
            html += `<li style="padding-bottom: 5px; margin-bottom: 5px; border-bottom: 1px solid #ccc;"><label><input type="checkbox" id="select-all-ecwid-products" /> <strong>${i18n.select_all_none}</strong></label></li>`;
            products.forEach(function(product) {
                html += `<li style="padding: 5px 0; border-bottom: 1px solid #eee;">
                            <label>
                                <input type="checkbox" class="ecwid-product-select" value="${product.id}" />
                                ${product.name} (SKU: ${product.sku || 'N/A'}, ID: ${product.id}, Enabled: ${product.enabled})
                                ${product.combinations && product.combinations.length > 0 ? ` - ${product.combinations.length} Variations` : ''}
                            </label>
                         </li>`;
            });
            html += '</ul>';
            productListContainer.html(html);

            $('#select-all-ecwid-products').on('change', function() {
                $('.ecwid-product-select').prop('checked', $(this).prop('checked'));
            });
        }

        importSelectedButton.on('click', function(e) {
            e.preventDefault();
            if (importSelectedButton.hasClass('disabled')) return;

            productsToImportSelectedIds = $('.ecwid-product-select:checked').map(function() {
                return $(this).val();
            }).get();

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

    });
})(jQuery);