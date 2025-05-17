(function($) {
    $(document).ready(function() {
        if (typeof ecwid_sync_params === 'undefined') {
            console.error('Ecwid Sync Params not defined. Ensure wp_localize_script is working.');
            // Disable UI elements or show an error message on the page
            $('#full-sync-button, #load-ecwid-products-button, #category-page-sync-button').addClass('disabled').prop('disabled', true);
            $('#full-sync-status, #selective-sync-status, #category-page-sync-status').text('Error: Plugin scripts not loaded correctly. Please check browser console.');
            return;
        }

        const ajax_url = ecwid_sync_params.ajax_url;
        const nonce = ecwid_sync_params.nonce;
        const i18n = ecwid_sync_params.i18n;
        const fullSyncSteps = ecwid_sync_params.sync_steps || ['categories', 'products'];
        const totalFullSyncSteps = fullSyncSteps.length;

        // Full Sync UI Elements
        const fullSyncButton = $('#full-sync-button');
        // const categorySyncButton = $('#category-sync-button'); // Removed: This button was on the full sync page
        const fullSyncProgressBar = $('#full-sync-bar');
        const fullSyncStatusDiv = $('#full-sync-status');
        const fullSyncLogDiv = $('#full-sync-log');

        // Category Sync Page UI Elements (New)
        const categoryPageSyncButton = $('#category-page-sync-button');
        const categoryPageSyncProgressBar = $('#category-page-sync-bar');
        const categoryPageSyncStatusDiv = $('#category-page-sync-status');
        const categoryPageSyncLogDiv = $('#category-page-sync-log');
        
        // Customer Sync Page UI Elements (New)
        const customerSyncButton = $('#customer-sync-button');
        const customerSyncProgressBar = $('#customer-sync-bar');
        const customerSyncStatusDiv = $('#customer-sync-status');
        const customerSyncLogDiv = $('#customer-sync-log');

        // Order Sync Page UI Elements (New)
        const orderSyncButton = $('#order-sync-button');
        const orderSyncProgressBar = $('#order-sync-bar');
        const orderSyncStatusDiv = $('#order-sync-status');
        const orderSyncLogDiv = $('#order-sync-log');
        
        // Selective Sync UI Elements
        const loadProductsButton = $('#load-ecwid-products-button');
        const productListContainer = $('#selective-product-list-container');
        const importSelectedButton = $('#import-selected-products-button');
        const selectiveSyncStatusDiv = $('#selective-sync-status');
        const selectiveSyncProgressBarContainer = $('#selective-sync-progress-container');
        const selectiveSyncProgressBar = $('#selective-sync-bar');
        const selectiveSyncLogDiv = $('#selective-sync-log');

        let currentFullSyncStepIndex = 0;
        let overallFullSyncProgressOffset = 0; // Accumulates completed steps' contribution for full sync

        let ecwidProductsForSelection = [];
        let productsToImportSelected = [];
        let currentSelectiveImportIndex = 0;
        let processingIndicatorInterval = null; // For simple animation
        let batchProcessingIndicatorInterval = null; // For batch process animations

        // Helper functions for batch status animation
        function startBatchStatusAnimation(statusDiv, baseText) {
            if (batchProcessingIndicatorInterval) clearInterval(batchProcessingIndicatorInterval);
            let dots = 0;
            // Set initial text immediately, animation will append dots
            statusDiv.text(baseText + " ");
            batchProcessingIndicatorInterval = setInterval(function() {
                dots = (dots + 1) % 4;
                // Ensure baseText itself doesn't get re-evaluated if it's complex
                statusDiv.text(baseText + " " + '.'.repeat(dots) + ' '.repeat(3 - dots));
            }, 500);
        }

        function stopBatchStatusAnimation() {
            if (batchProcessingIndicatorInterval) {
                clearInterval(batchProcessingIndicatorInterval);
                batchProcessingIndicatorInterval = null;
            }
        }

        // --- Full Sync Logic ---
        fullSyncButton.on('click', function(e) {
            e.preventDefault();
            if (fullSyncButton.hasClass('disabled')) return;

            fullSyncButton.addClass('disabled').text(i18n.syncing_button);
            // Consider disabling other sync buttons if necessary
            // categoryPageSyncButton.addClass('disabled');

            fullSyncStatusDiv.text(i18n.sync_starting);
            updateProgressBar(fullSyncProgressBar, 0);
            fullSyncLogDiv.html('');

            currentFullSyncStepIndex = 0;
            overallFullSyncProgressOffset = 0;

            logMessage(fullSyncLogDiv, i18n.sync_starting, 'info');
            processNextFullSyncStep();
        });

        // --- Category Sync Page Logic (New) ---
        if (categoryPageSyncButton.length) { // Ensure the button exists on the current page
            categoryPageSyncButton.on('click', function(e) {
                e.preventDefault();
                if (categoryPageSyncButton.hasClass('disabled')) return;

                categoryPageSyncButton.addClass('disabled').text(i18n.syncing_categories_page_button);
                // fullSyncButton.addClass('disabled'); // Optionally disable other buttons

                categoryPageSyncStatusDiv.text(i18n.syncing_just_categories_page_status);
                updateProgressBar(categoryPageSyncProgressBar, 0);
                categoryPageSyncLogDiv.html('');

                logMessage(categoryPageSyncLogDiv, i18n.syncing_just_categories_page_status, 'info');
                processCategoryPageSyncBatch('categories', 0); 
            });
        }

        // --- Customer Sync Page Logic (New) ---
        if (customerSyncButton.length) {
            customerSyncButton.on('click', function(e) {
                e.preventDefault();
                if (customerSyncButton.hasClass('disabled')) return;

                customerSyncButton.addClass('disabled').text(i18n.syncing_customers);
                customerSyncStatusDiv.text(i18n.syncing_customers);
                updateProgressBar(customerSyncProgressBar, 0);
                customerSyncLogDiv.html('');
                logMessage(customerSyncLogDiv, i18n.syncing_customers + ' ' + i18n.sync_starting, 'info');
                processCustomerSyncBatch(0);
            });
        }

        // --- Order Sync Page Logic (New) ---
        if (orderSyncButton.length) {
            orderSyncButton.on('click', function(e) {
                e.preventDefault();
                if (orderSyncButton.hasClass('disabled')) return;

                orderSyncButton.addClass('disabled').text(i18n.syncing_orders);
                orderSyncStatusDiv.text(i18n.syncing_orders);
                updateProgressBar(orderSyncProgressBar, 0);
                orderSyncLogDiv.html('');
                logMessage(orderSyncLogDiv, i18n.syncing_orders + ' ' + i18n.sync_starting, 'info');
                processOrderSyncBatch(0);
            });
        }

        function processNextFullSyncStep() { // For the FULL sync sequence
            if (currentFullSyncStepIndex < totalFullSyncSteps) {
                const syncType = fullSyncSteps[currentFullSyncStepIndex];
                updateStatus(fullSyncStatusDiv, i18n.syncing + ' ' + syncType + '...');
                // Note: The 'isCategoryOnlyRun' flag is removed as this function is now only for full sync
                processFullSyncBatch(syncType, 0); 
            } else {
                // Full sync completion
                updateStatus(fullSyncStatusDiv, i18n.sync_complete);
                logMessage(fullSyncLogDiv, i18n.sync_complete, 'success');
                fullSyncButton.removeClass('disabled').text(i18n.start_sync);
                // categoryPageSyncButton.removeClass('disabled'); // Re-enable if it was disabled
                updateProgressBar(fullSyncProgressBar, 100);
            }
        }

        // Modified processFullSyncBatch - now only for the full sync page
        function processFullSyncBatch(syncType, offset) {
            const baseStatusMessage = i18n.syncing + ' ' + syncType;
            startBatchStatusAnimation(fullSyncStatusDiv, baseStatusMessage);

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
                    stopBatchStatusAnimation(); // Stop animation
                    if (response.success) {
                        if (response.data.batch_logs && Array.isArray(response.data.batch_logs)) {
                            response.data.batch_logs.forEach(logEntry => categorizeAndLog(fullSyncLogDiv, logEntry));
                        } else {
                            logMessage(fullSyncLogDiv, `Batch for ${syncType} (offset ${offset}) processed. No detailed logs provided.`, 'info');
                        }

                        let currentStepProgress = 0;
                        if (response.data.total_items > 0) {
                            currentStepProgress = (response.data.next_offset / response.data.total_items) * 100;
                        } else if (response.data.has_more === false) {
                            currentStepProgress = 100; 
                        }
                        currentStepProgress = Math.min(100, Math.round(currentStepProgress));
                        
                        // Full sync progress calculation
                        let progressPerStep = 100 / totalFullSyncSteps;
                        let overallProgress = overallFullSyncProgressOffset + (currentStepProgress / 100 * progressPerStep);
                        updateProgressBar(fullSyncProgressBar, Math.round(overallProgress));
                        updateStatus(fullSyncStatusDiv, i18n.syncing + ' ' + syncType + `... ${Math.round(currentStepProgress)}%`);


                        if (response.data.has_more) {
                            processFullSyncBatch(syncType, response.data.next_offset);
                        } else {
                            // Current syncType (e.g., 'categories' or 'products') is complete for full sync
                            overallFullSyncProgressOffset += (100 / totalFullSyncSteps);
                            currentFullSyncStepIndex++;
                            processNextFullSyncStep();
                        }
                    } else {
                        // stopBatchStatusAnimation(); // Already called at the beginning of success/error
                        logMessage(fullSyncLogDiv, `Error syncing ${syncType}: ${response.data.message || 'Unknown error.'}`, 'error');
                        fullSyncButton.removeClass('disabled').text(i18n.start_sync);
                        updateStatus(fullSyncStatusDiv, i18n.sync_error + ` (${syncType})`); // Update status on error
                        // categoryPageSyncButton.removeClass('disabled');
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    stopBatchStatusAnimation(); // Stop animation
                    logMessage(fullSyncLogDiv, `AJAX Error syncing ${syncType}: ${textStatus} ${errorThrown || ''}`, 'error');
                    fullSyncButton.removeClass('disabled').text(i18n.start_sync);
                    updateStatus(fullSyncStatusDiv, i18n.ajax_error + ` (${syncType})`); // Update status on AJAX error
                    // categoryPageSyncButton.removeClass('disabled');
                }
            });
        }

        // New function for Category Sync Page
        function processCategoryPageSyncBatch(syncType, offset) { // syncType will always be 'categories'
            const baseStatusMessage = i18n.syncing_just_categories_page_status;
            startBatchStatusAnimation(categoryPageSyncStatusDiv, baseStatusMessage);

            $.ajax({
                url: ajax_url, // Uses the same backend AJAX handler
                method: 'POST',
                data: {
                    action: 'ecwid_wc_batch_sync',
                    nonce: nonce,
                    sync_type: syncType, // 'categories'
                    offset: offset
                },
                success: function(response) {
                    stopBatchStatusAnimation(); // Stop animation
                    if (response.success) {
                        if (response.data.batch_logs && Array.isArray(response.data.batch_logs)) {
                            response.data.batch_logs.forEach(logEntry => categorizeAndLog(categoryPageSyncLogDiv, logEntry));
                        } else {
                            logMessage(categoryPageSyncLogDiv, `Batch for ${syncType} (offset ${offset}) processed. No detailed logs provided.`, 'info');
                        }

                        let currentProgress = 0;
                        if (response.data.total_items > 0) {
                            currentProgress = (response.data.next_offset / response.data.total_items) * 100;
                        } else if (response.data.has_more === false) { // No items or all done
                            currentProgress = 100;
                        }
                        currentProgress = Math.min(100, Math.round(currentProgress));

                        updateProgressBar(categoryPageSyncProgressBar, currentProgress);
                        categoryPageSyncStatusDiv.text(i18n.syncing_just_categories_page_status + ` ${currentProgress}%`);

                        if (response.data.has_more) {
                            processCategoryPageSyncBatch(syncType, response.data.next_offset);
                        } else {
                            // Category sync complete for this page
                            categoryPageSyncStatusDiv.text(i18n.category_sync_page_complete);
                            logMessage(categoryPageSyncLogDiv, i18n.category_sync_page_complete, 'success');
                            categoryPageSyncButton.removeClass('disabled').text(i18n.start_category_sync_page);
                            updateProgressBar(categoryPageSyncProgressBar, 100); // Ensure it hits 100%
                        }
                    } else {
                        // stopBatchStatusAnimation(); // Already called
                        logMessage(categoryPageSyncLogDiv, `Error syncing ${syncType}: ${response.data.message || 'Unknown error.'}`, 'error');
                        categoryPageSyncButton.removeClass('disabled').text(i18n.start_category_sync_page);
                        updateStatus(categoryPageSyncStatusDiv, i18n.sync_error + ` (${syncType})`); // Update status on error
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    stopBatchStatusAnimation(); // Stop animation
                    logMessage(categoryPageSyncLogDiv, `AJAX Error syncing ${syncType}: ${textStatus} ${errorThrown || ''}`, 'error');
                    categoryPageSyncButton.removeClass('disabled').text(i18n.start_category_sync_page);
                    updateStatus(categoryPageSyncStatusDiv, i18n.ajax_error + ` (${syncType})`); // Update status on AJAX error
                }
            });
        }

        // --- Customer Sync Page Logic (New) ---
        if (customerSyncButton.length) {
            customerSyncButton.on('click', function(e) {
                e.preventDefault();
                if (customerSyncButton.hasClass('disabled')) return;

                customerSyncButton.addClass('disabled').text(i18n.syncing_customers);
                customerSyncStatusDiv.text(i18n.syncing_customers);
                updateProgressBar(customerSyncProgressBar, 0);
                customerSyncLogDiv.html('');
                logMessage(customerSyncLogDiv, i18n.syncing_customers + ' ' + i18n.sync_starting, 'info');
                processCustomerSyncBatch(0);
            });
        }

        function processCustomerSyncBatch(offset) {
            const baseStatusMessage = i18n.syncing_customers;
            startBatchStatusAnimation(customerSyncStatusDiv, baseStatusMessage);

            $.ajax({
                url: ajax_url,
                method: 'POST',
                data: {
                    action: 'ecwid_wc_customer_batch_sync', // New AJAX action
                    nonce: nonce,
                    offset: offset
                },
                success: function(response) {
                    stopBatchStatusAnimation();
                    if (response.success) {
                        if (response.data.batch_logs && Array.isArray(response.data.batch_logs)) {
                            response.data.batch_logs.forEach(logEntry => categorizeAndLog(customerSyncLogDiv, logEntry));
                        } else {
                            logMessage(customerSyncLogDiv, `Customer batch (offset ${offset}) processed. No detailed logs.`, 'info');
                        }

                        let currentProgress = 0;
                        if (response.data.total_items > 0) {
                            currentProgress = (response.data.next_offset / response.data.total_items) * 100;
                        } else if (response.data.has_more === false) {
                            currentProgress = 100;
                        }
                        currentProgress = Math.min(100, Math.round(currentProgress));

                        updateProgressBar(customerSyncProgressBar, currentProgress);
                        customerSyncStatusDiv.text(i18n.syncing_customers + ` ${currentProgress}%`);

                        if (response.data.has_more) {
                            processCustomerSyncBatch(response.data.next_offset);
                        } else {
                            customerSyncStatusDiv.text(i18n.customer_sync_complete);
                            logMessage(customerSyncLogDiv, i18n.customer_sync_complete, 'success');
                            customerSyncButton.removeClass('disabled').text(i18n.start_customer_sync);
                            updateProgressBar(customerSyncProgressBar, 100);
                        }
                    } else {
                        handleAjaxError(customerSyncStatusDiv, customerSyncLogDiv, customerSyncButton, i18n.start_customer_sync, 'Customers', response.data);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    stopBatchStatusAnimation();
                    const responseData = { message: `${textStatus} ${errorThrown || ''}` };
                    handleAjaxError(customerSyncStatusDiv, customerSyncLogDiv, customerSyncButton, i18n.start_customer_sync, 'Customers', responseData, true);
                }
            });
        }

        // --- Order Sync Page Logic (New) ---
        if (orderSyncButton.length) {
            orderSyncButton.on('click', function(e) {
                e.preventDefault();
                if (orderSyncButton.hasClass('disabled')) return;

                orderSyncButton.addClass('disabled').text(i18n.syncing_orders);
                orderSyncStatusDiv.text(i18n.syncing_orders);
                updateProgressBar(orderSyncProgressBar, 0);
                orderSyncLogDiv.html('');
                logMessage(orderSyncLogDiv, i18n.syncing_orders + ' ' + i18n.sync_starting, 'info');
                processOrderSyncBatch(0);
            });
        }

        function processOrderSyncBatch(offset) {
            const baseStatusMessage = i18n.syncing_orders;
            startBatchStatusAnimation(orderSyncStatusDiv, baseStatusMessage);

            $.ajax({
                url: ajax_url,
                method: 'POST',
                data: {
                    action: 'ecwid_wc_order_batch_sync', // New AJAX action
                    nonce: nonce,
                    offset: offset
                },
                success: function(response) {
                    stopBatchStatusAnimation();
                    if (response.success) {
                        if (response.data.batch_logs && Array.isArray(response.data.batch_logs)) {
                            response.data.batch_logs.forEach(logEntry => categorizeAndLog(orderSyncLogDiv, logEntry));
                        } else {
                            logMessage(orderSyncLogDiv, `Order batch (offset ${offset}) processed. No detailed logs.`, 'info');
                        }

                        let currentProgress = 0;
                        if (response.data.total_items > 0) {
                            currentProgress = (response.data.next_offset / response.data.total_items) * 100;
                        } else if (response.data.has_more === false) {
                            currentProgress = 100;
                        }
                        currentProgress = Math.min(100, Math.round(currentProgress));

                        updateProgressBar(orderSyncProgressBar, currentProgress);
                        orderSyncStatusDiv.text(i18n.syncing_orders + ` ${currentProgress}%`);

                        if (response.data.has_more) {
                            processOrderSyncBatch(response.data.next_offset);
                        } else {
                            orderSyncStatusDiv.text(i18n.order_sync_complete);
                            logMessage(orderSyncLogDiv, i18n.order_sync_complete, 'success');
                            orderSyncButton.removeClass('disabled').text(i18n.start_order_sync);
                            updateProgressBar(orderSyncProgressBar, 100);
                        }
                    } else {
                        handleAjaxError(orderSyncStatusDiv, orderSyncLogDiv, orderSyncButton, i18n.start_order_sync, 'Orders', response.data);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    stopBatchStatusAnimation();
                    const responseData = { message: `${textStatus} ${errorThrown || ''}` };
                    handleAjaxError(orderSyncStatusDiv, orderSyncLogDiv, orderSyncButton, i18n.start_order_sync, 'Orders', responseData, true);
                }
            });
        }

        // --- Selective Product Import Logic ---
        loadProductsButton.on('click', function(e) {
            e.preventDefault();
            if (loadProductsButton.hasClass('disabled')) return;

            loadProductsButton.addClass('disabled').text(i18n.loading_products);
            productListContainer.html('<p>' + i18n.loading_products + '</p>');
            importSelectedButton.hide();

            $.ajax({
                url: ajax_url,
                method: 'POST',
                data: {
                    action: 'ecwid_wc_fetch_products_for_selection',
                    nonce: nonce
                },
                success: function(response) {
                    loadProductsButton.removeClass('disabled').text(i18n.load_products);
                    if (response.success && response.data.products) {
                        ecwidProductsForSelection = response.data.products;
                        renderProductSelectionList(ecwidProductsForSelection);
                        if (ecwidProductsForSelection.length > 0) {
                            importSelectedButton.show();
                        } else {
                            // Use the localized string from ecwid_sync_params.i18n
                            productListContainer.html('<p>' + i18n.no_products_found + '</p>');
                        }
                    } else {
                        const errorMsg = response.data && response.data.message ? response.data.message : 'Could not load products.';
                        productListContainer.html('<p style="color:red;">' + errorMsg + '</p>');
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    loadProductsButton.removeClass('disabled').text(i18n.load_products);
                    productListContainer.html('<p style="color:red;">AJAX Error: ' + textStatus + (errorThrown ? ' - ' + errorThrown : '') + '</p>');
                }
            });
        });

        function renderProductSelectionList(products) {
            let html = '<ul style="list-style:none; margin:0; padding:0;">';
            // Use the localized string from ecwid_sync_params.i18n
            html += '<li style="padding-bottom: 5px; margin-bottom: 5px; border-bottom: 1px solid #ccc;"><label><input type="checkbox" id="select-all-ecwid-products" /> <strong>' + i18n.select_all_none + '</strong></label></li>';
            products.forEach(function(product) {
                html += `<li style="padding: 5px 0; border-bottom: 1px solid #eee;">
                            <label>
                                <input type="checkbox" class="ecwid-product-select" value="${product.id}" />
                                ${product.name} (SKU: ${product.sku || 'N/A'}, ID: ${product.id}, Enabled: ${product.enabled})
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

            productsToImportSelected = $('.ecwid-product-select:checked').map(function() {
                return $(this).val();
            }).get();

            if (productsToImportSelected.length === 0) {
                alert(i18n.no_products_selected);
                return;
            }

            importSelectedButton.addClass('disabled').text(i18n.importing_selected);
            selectiveSyncStatusDiv.text(i18n.sync_starting);
            selectiveSyncProgressBarContainer.show();
            updateProgressBar(selectiveSyncProgressBar, 0);
            selectiveSyncLogDiv.html('');
            currentSelectiveImportIndex = 0;

            logMessage(selectiveSyncLogDiv, i18n.sync_starting + ' ' + productsToImportSelected.length + ' products.', 'info');
            processNextSelectedProduct();
        });

        function processNextSelectedProduct() {
            if (processingIndicatorInterval) clearInterval(processingIndicatorInterval); // Clear previous interval

            if (currentSelectiveImportIndex < productsToImportSelected.length) {
                const ecwidProductId = productsToImportSelected[currentSelectiveImportIndex];
                const productData = ecwidProductsForSelection.find(p => p.id.toString() === ecwidProductId.toString());
                const productName = productData ? productData.name : `ID ${ecwidProductId}`;

                let dots = 0;
                const baseStatusText = i18n.importing_selected + ` (${currentSelectiveImportIndex + 1}/${productsToImportSelected.length}): ${productName}`;
                updateStatus(selectiveSyncStatusDiv, baseStatusText + " ");
                
                processingIndicatorInterval = setInterval(function() {
                    dots = (dots + 1) % 4;
                    selectiveSyncStatusDiv.text(baseStatusText + " " + '.'.repeat(dots) + ' '.repeat(3 - dots));
                }, 500);

                $.ajax({
                    url: ajax_url,
                    method: 'POST',
                    data: {
                        action: 'ecwid_wc_import_selected_products',
                        nonce: nonce,
                        ecwid_product_id: ecwidProductId
                    },
                    success: function(response) {
                        clearInterval(processingIndicatorInterval);
                        processingIndicatorInterval = null;
                        if (response.success) {
                            logMessage(selectiveSyncLogDiv, `Importing ${response.data.item_name || productName} (Ecwid ID: ${response.data.ecwid_id}, SKU: ${response.data.sku || 'N/A'}): Status - ${response.data.status}`, response.data.status === 'imported' || response.data.status === 'skipped' ? 'success' : 'info');
                            if (response.data.logs && Array.isArray(response.data.logs)) {
                                response.data.logs.forEach(logEntry => categorizeAndLog(selectiveSyncLogDiv, logEntry));
                            }
                        } else {
                            logMessage(selectiveSyncLogDiv, `Failed to import product ID ${ecwidProductId}: ${response.data.message}`, 'error');
                        }
                        currentSelectiveImportIndex++;
                        let progress = (currentSelectiveImportIndex / productsToImportSelected.length) * 100;
                        updateProgressBar(selectiveSyncProgressBar, Math.round(progress));
                        processNextSelectedProduct();
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        clearInterval(processingIndicatorInterval);
                        processingIndicatorInterval = null;
                        logMessage(selectiveSyncLogDiv, `AJAX Error importing product ID ${ecwidProductId}: ${textStatus} ${errorThrown || ''}`, 'error');
                        currentSelectiveImportIndex++;
                        let progress = (currentSelectiveImportIndex / productsToImportSelected.length) * 100;
                        updateProgressBar(selectiveSyncProgressBar, Math.round(progress));
                        processNextSelectedProduct(); // Try next one even if one fails
                    }
                });
            } else {
                if (processingIndicatorInterval) clearInterval(processingIndicatorInterval);
                updateStatus(selectiveSyncStatusDiv, i18n.sync_complete);
                logMessage(selectiveSyncLogDiv, i18n.sync_complete, 'success');
                importSelectedButton.removeClass('disabled').text(i18n.import_selected);
                updateProgressBar(selectiveSyncProgressBar, 100);
                // selectiveSyncProgressBarContainer.hide(); // Optionally hide after completion
            }
        }

        // --- Helper Functions ---
        function logMessage(logDiv, message, type) {
            let color = 'black';
            if (type === 'success') color = 'green';
            else if (type === 'error') color = 'red';
            else if (type === 'info') color = '#005a9c';
            else if (type === 'warning') color = '#ffa500';

            const cleanMessage = $('<div/>').text(message).html(); // Basic sanitization
            logDiv.append(`<p style="color:${color}; margin: 2px 0; padding: 0; white-space: pre-wrap; word-wrap: break-word;">${cleanMessage}</p>`);
            logDiv.scrollTop(logDiv[0].scrollHeight);
        }
        
        function categorizeAndLog(logDiv, logEntry) {
            let logType = 'info'; // Default
            if (typeof logEntry === 'string') {
                const upperLogEntry = logEntry.toUpperCase(); // Convert once for efficiency
                if (upperLogEntry.includes('[CRITICAL]') || upperLogEntry.includes('[ERROR]') || upperLogEntry.includes('FAILED TO PROCESS') || upperLogEntry.includes('FAILED TO SAVE')) {
                    logType = 'error';
                } else if (upperLogEntry.includes('IMPORTED SUCCESSFULLY') || upperLogEntry.includes('SKIPPED.') || upperLogEntry.includes('SUCCESSFULLY PROCESSED')) {
                    logType = 'success';
                } else if (upperLogEntry.includes('[WARNING]')) {
                    logType = 'warning';
                }
            }
            logMessage(logDiv, logEntry, logType);
        }

        function updateStatus(statusDiv, statusText) {
            statusDiv.text(statusText);
        }

        function updateProgressBar(progressBarElem, percentage) {
            percentage = Math.max(0, Math.min(100, percentage)); // Clamp between 0 and 100
            progressBarElem.css('width', percentage + '%').text(percentage + '%');
        }

        function handleAjaxError(statusDiv, logDiv, buttonElem, buttonText, syncType, responseData, isNetworkError = false) {
            const errorMessage = responseData && responseData.message ? responseData.message : (isNetworkError ? 'Network error' : i18n.sync_error);
            updateStatus(statusDiv, i18n.sync_error + (syncType ? ` (${syncType})` : ''));
            logMessage(logDiv, (syncType ? `${syncType}: ` : '') + 'Error - ' + errorMessage, 'error');
            if (responseData && responseData.details) {
                console.error("Sync Error Details" + (syncType ? ` for ${syncType}` : '') + ":", responseData.details);
                logMessage(logDiv, "Details: " + JSON.stringify(responseData.details), 'error');
            }
            if (buttonElem) {
                buttonElem.removeClass('disabled').text(buttonText);
            }
        }

        // --- Fix Category Hierarchy Logic (New) ---
        $('#fix-category-hierarchy-button').on('click', function(e) {
            e.preventDefault();
            if ($(this).hasClass('disabled')) return;
            
            $(this).addClass('disabled').text('Fixing Hierarchies...');
            categoryPageSyncStatusDiv.text('Fixing Category Hierarchies...');
            
            $.ajax({
                url: ajax_url,
                method: 'POST',
                data: {
                    action: 'fix_category_hierarchy',
                    nonce: nonce
                },
                success: function(response) {
                    if (response.success) {
                        categoryPageSyncStatusDiv.text('Category hierarchies fixed! ' + response.data.fixed_count + ' categories updated.');
                        response.data.logs.forEach(log => logMessage(categoryPageSyncLogDiv, log, 'info'));
                    } else {
                        logMessage(categoryPageSyncLogDiv, 'Error fixing hierarchies: ' + (response.data.message || 'Unknown error'), 'error');
                    }
                    $('#fix-category-hierarchy-button').removeClass('disabled').text('Fix Category Hierarchy');
                },
                error: function() {
                    logMessage(categoryPageSyncLogDiv, 'AJAX error while fixing hierarchies', 'error');
                    $('#fix-category-hierarchy-button').removeClass('disabled').text('Fix Category Hierarchy');
                }
            });
        });

    });
})(jQuery);