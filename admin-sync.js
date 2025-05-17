(function($) {
    $(document).ready(function() {
        if (typeof ecwid_sync_params === 'undefined') {
            console.error('Ecwid Sync Params not defined. Ensure wp_localize_script is working.');
            $('#full-sync-button, #load-ecwid-products-button, #category-page-sync-button, #import-selected-products-button').addClass('disabled').prop('disabled', true);
            $('#full-sync-status, #selective-sync-status, #category-page-sync-status').text('Error: Plugin scripts not loaded correctly. Please check browser console.');
            return;
        }

        const ajax_url = ecwid_sync_params.ajax_url;
        const nonce = ecwid_sync_params.nonce;
        const i18n = ecwid_sync_params.i18n || {}; // Ensure i18n exists
        // Corrected initialization of fullSyncSteps
        const fullSyncSteps = (ecwid_sync_params.sync_steps && ecwid_sync_params.sync_steps.length > 0) ? ecwid_sync_params.sync_steps : ['categories', 'products'];
        const totalFullSyncSteps = fullSyncSteps.length;
        const variationBatchSize = ecwid_sync_params.variation_batch_size || 10; // Default batch size if not provided

        // Default i18n strings if not provided by wp_localize_script
        i18n.importing_variations_status = i18n.importing_variations_status || 'Importing variations for {productName} ({currentBatch} of {totalBatches})';
        i18n.processing_variation_batch = i18n.processing_variation_batch || 'Processing variation batch...';
        i18n.variations_imported_successfully = i18n.variations_imported_successfully || 'All variations imported successfully for {productName}.';
        i18n.error_importing_variations = i18n.error_importing_variations || 'Error importing variations for {productName}. See log.';
        i18n.parent_product_imported_pending_variations = i18n.parent_product_imported_pending_variations || 'Parent product {productName} imported. Starting variation import...';
        i18n.syncing_button = i18n.syncing_button || 'Syncing...';
        i18n.sync_starting = i18n.sync_starting || 'Sync starting...';
        i18n.sync_complete = i18n.sync_complete || 'Sync complete!';
        i18n.sync_error = i18n.sync_error || 'Sync error';
        i18n.ajax_error = i18n.ajax_error || 'AJAX error';
        i18n.start_sync = i18n.start_sync || 'Start Full Sync';
        i18n.syncing_categories_page_button = i18n.syncing_categories_page_button || 'Syncing Categories...';
        i18n.syncing_just_categories_page_status = i18n.syncing_just_categories_page_status || 'Syncing categories...';
        i18n.category_sync_page_complete = i18n.category_sync_page_complete || 'Category sync complete!';
        i18n.start_category_sync_page = i18n.start_category_sync_page || 'Start Category Sync';
        i18n.loading_products = i18n.loading_products || 'Loading products...';
        i18n.load_products = i18n.load_products || 'Load Ecwid Products';
        i18n.no_products_found = i18n.no_products_found || 'No products found in your Ecwid store or an error occurred.';
        i18n.select_all_none = i18n.select_all_none || 'Select All / None';
        i18n.no_products_selected = i18n.no_products_selected || 'Please select at least one product to import.';
        i18n.importing_selected = i18n.importing_selected || 'Importing Selected...';
        i18n.import_selected = i18n.import_selected || 'Import Selected Products';


        // Full Sync UI Elements
        const fullSyncButton = $('#full-sync-button');
        const fullSyncProgressBar = $('#full-sync-bar');
        const fullSyncStatusDiv = $('#full-sync-status');
        const fullSyncLogDiv = $('#full-sync-log');
        const fullSyncStepProgressBar = $('#full-sync-step-bar'); // New progress bar element

        // Category Sync Page UI Elements
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
        let overallFullSyncProgressOffset = 0; 

        let ecwidProductsForSelection = []; // Holds all products fetched for selection {id, name, sku, enabled, options}
        let productsToImportSelectedIds = []; // Holds IDs of products selected for import
        let currentSelectiveImportProductIndex = 0; // Index for productsToImportSelectedIds

        // State for current product's variation processing
        let currentProductVariationData = null; 
        // { wc_product_id, ecwid_product_id, item_name, sku, all_combinations, total_combinations, original_options, current_variation_offset }

        let batchProcessingIndicatorInterval = null; 

        function startBatchStatusAnimation(statusDiv, baseText) {
            if (batchProcessingIndicatorInterval) clearInterval(batchProcessingIndicatorInterval);
            let dots = 0;
            statusDiv.text(baseText + " ");
            batchProcessingIndicatorInterval = setInterval(function() {
                dots = (dots + 1) % 4;
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
            fullSyncStatusDiv.text(i18n.sync_starting);
            updateProgressBar(fullSyncProgressBar, 0);
            updateProgressBar(fullSyncStepProgressBar, 0); // Reset step progress bar
            fullSyncLogDiv.html('');
            currentFullSyncStepIndex = 0;
            overallFullSyncProgressOffset = 0;
            logMessage(fullSyncLogDiv, i18n.sync_starting, 'info');
            processNextFullSyncStep();
        });

<<<<<<< HEAD
        function processNextFullSyncStep() {
=======
<<<<<<< HEAD
        function processNextFullSyncStep() {
=======
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
>>>>>>> 5fd7a481a2475a16180fd1de27782e4603391e37
>>>>>>> 2905b58016ecd9d31cafe464ab3446328e3ef78c
            if (currentFullSyncStepIndex < totalFullSyncSteps) {
                const syncType = fullSyncSteps[currentFullSyncStepIndex];
                updateStatus(fullSyncStatusDiv, i18n.syncing + ' ' + syncType + '...');
                if (currentFullSyncStepIndex > 0) { // Reset step bar for subsequent steps
                    updateProgressBar(fullSyncStepProgressBar, 0);
                }
                processFullSyncBatch(syncType, 0); 
            } else {
                stopBatchStatusAnimation();
                updateStatus(fullSyncStatusDiv, i18n.sync_complete);
                logMessage(fullSyncLogDiv, i18n.sync_complete, 'success');
                fullSyncButton.removeClass('disabled').text(i18n.start_sync);
                updateProgressBar(fullSyncProgressBar, 100);
                updateProgressBar(fullSyncStepProgressBar, 100); // Show last step as 100%
            }
        }

        function processFullSyncBatch(syncType, offset) {
            const baseStatusMessage = i18n.syncing + ' ' + syncType;
            startBatchStatusAnimation(fullSyncStatusDiv, baseStatusMessage);

            $.ajax({
                url: ajax_url,
                method: 'POST',
                data: { action: 'ecwid_wc_batch_sync', nonce: nonce, sync_type: syncType, offset: offset },
                success: function(response) {
                    stopBatchStatusAnimation();
                    if (response.success) {
                        (response.data.batch_logs || []).forEach(logEntry => categorizeAndLog(fullSyncLogDiv, logEntry));
                        
                        let rawCurrentStepProgress = 0; 
                        if (response.data.total_items > 0) {
                            rawCurrentStepProgress = (response.data.next_offset / response.data.total_items) * 100;
                        } else if (response.data.has_more === false) { 
                            rawCurrentStepProgress = 100;
                        }
                        rawCurrentStepProgress = Math.max(0, Math.min(100, rawCurrentStepProgress));
                        let displayStepProgress = Math.round(rawCurrentStepProgress);
                        
                        let progressPerStep = 100 / totalFullSyncSteps;
                        let overallProgress = overallFullSyncProgressOffset + (rawCurrentStepProgress / 100 * progressPerStep);
                        
                        updateProgressBar(fullSyncProgressBar, overallProgress); 
                        updateProgressBar(fullSyncStepProgressBar, displayStepProgress); // Update current step progress bar
                        updateStatus(fullSyncStatusDiv, i18n.syncing + ' ' + syncType + `... ${displayStepProgress}%`);

                        if (response.data.has_more) {
                            processFullSyncBatch(syncType, response.data.next_offset);
                        } else {
                            updateProgressBar(fullSyncStepProgressBar, 100); // Ensure step bar shows 100% on completion
                            overallFullSyncProgressOffset += progressPerStep;
                            overallFullSyncProgressOffset = Math.min(100, overallFullSyncProgressOffset); 
                            currentFullSyncStepIndex++;
                            processNextFullSyncStep();
                        }
                    } else {
                        handleAjaxError(fullSyncStatusDiv, fullSyncLogDiv, fullSyncButton, i18n.start_sync, syncType, response.data);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    stopBatchStatusAnimation();
                    handleAjaxError(fullSyncStatusDiv, fullSyncLogDiv, fullSyncButton, i18n.start_sync, syncType, { message: `${textStatus} ${errorThrown || ''}` }, true);
                }
            });
        }

        // --- Category Sync Page Logic ---
        if (categoryPageSyncButton.length) {
            categoryPageSyncButton.on('click', function(e) {
                e.preventDefault();
                if (categoryPageSyncButton.hasClass('disabled')) return;

                categoryPageSyncButton.addClass('disabled').text(i18n.syncing_categories_page_button);
                categoryPageSyncStatusDiv.text(i18n.syncing_just_categories_page_status);
                updateProgressBar(categoryPageSyncProgressBar, 0);
                categoryPageSyncLogDiv.html('');
                logMessage(categoryPageSyncLogDiv, i18n.syncing_just_categories_page_status, 'info');
                processCategoryPageSyncBatch('categories', 0); 
            });
        }
        
        function processCategoryPageSyncBatch(syncType, offset) {
            const baseStatusMessage = i18n.syncing_just_categories_page_status;
            startBatchStatusAnimation(categoryPageSyncStatusDiv, baseStatusMessage);

            $.ajax({
                url: ajax_url,
                method: 'POST',
                data: { action: 'ecwid_wc_batch_sync', nonce: nonce, sync_type: syncType, offset: offset },
                success: function(response) {
                    stopBatchStatusAnimation();
                    if (response.success) {
                        (response.data.batch_logs || []).forEach(logEntry => categorizeAndLog(categoryPageSyncLogDiv, logEntry));

                        let currentProgress = 0;
                        if (response.data.total_items > 0) {
                            currentProgress = (response.data.next_offset / response.data.total_items) * 100;
                        } else if (response.data.has_more === false) {
                            currentProgress = 100;
                        }
                        currentProgress = Math.min(100, Math.round(currentProgress));

                        updateProgressBar(categoryPageSyncProgressBar, currentProgress);
                        categoryPageSyncStatusDiv.text(i18n.syncing_just_categories_page_status + ` ${currentProgress}%`);

                        if (response.data.has_more) {
                            processCategoryPageSyncBatch(syncType, response.data.next_offset);
                        } else {
                            categoryPageSyncStatusDiv.text(i18n.category_sync_page_complete);
                            logMessage(categoryPageSyncLogDiv, i18n.category_sync_page_complete, 'success');
                            categoryPageSyncButton.removeClass('disabled').text(i18n.start_category_sync_page);
                            updateProgressBar(categoryPageSyncProgressBar, 100);
                        }
                    } else {
                         handleAjaxError(categoryPageSyncStatusDiv, categoryPageSyncLogDiv, categoryPageSyncButton, i18n.start_category_sync_page, syncType, response.data);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    stopBatchStatusAnimation();
                    handleAjaxError(categoryPageSyncStatusDiv, categoryPageSyncLogDiv, categoryPageSyncButton, i18n.start_category_sync_page, syncType, { message: `${textStatus} ${errorThrown || ''}` }, true);
<<<<<<< HEAD
=======
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
>>>>>>> 2905b58016ecd9d31cafe464ab3446328e3ef78c
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
            ecwidProductsForSelection = []; // Clear previous list

            $.ajax({
                url: ajax_url,
                method: 'POST',
                data: { action: 'ecwid_wc_fetch_products_for_selection', nonce: nonce },
                success: function(response) {
                    loadProductsButton.removeClass('disabled').text(i18n.load_products);
                    if (response.success && response.data.products) {
                        ecwidProductsForSelection = response.data.products; // Store full product data including options
                        renderProductSelectionList(ecwidProductsForSelection);
                        if (ecwidProductsForSelection.length > 0) {
                            importSelectedButton.show();
                        } else {
                            productListContainer.html('<p>' + i18n.no_products_found + '</p>');
                        }
                    } else {
                        const errorMsg = response.data && response.data.message ? response.data.message : i18n.no_products_found;
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
                logMessage(selectiveSyncLogDiv, i18n.variations_imported_successfully.replace('{productName}', item_name), 'success');
                currentProductVariationData = null; // Clear variation state
                currentSelectiveImportProductIndex++; // Mark parent product as fully done
                updateOverallSelectiveProgress();
                processNextSelectedProduct(); // Move to the next product in the main list
                return;
            }

            const combinationsBatch = all_combinations.slice(current_variation_offset, current_variation_offset + variationBatchSize);
            const currentBatchNumber = Math.floor(current_variation_offset / variationBatchSize) + 1;
            const totalBatches = Math.ceil(total_combinations / variationBatchSize);
            
            const statusMsg = i18n.importing_variations_status
                .replace('{productName}', item_name)
                .replace('{currentBatch}', currentBatchNumber)
                .replace('{totalBatches}', totalBatches);
            startBatchStatusAnimation(selectiveSyncStatusDiv, statusMsg);

            $.ajax({
                url: ajax_url,
                method: 'POST',
                data: {
                    action: 'ecwid_wc_process_variation_batch',
                    nonce: nonce,
                    wc_product_id: wc_product_id,
                    ecwid_product_id: ecwid_product_id, // For logging context on server
                    item_name: item_name, // For logging context on server
                    sku: sku, // For logging context on server
                    combinations_batch_json: JSON.stringify(combinationsBatch),
                    original_ecwid_options_json: JSON.stringify(original_options || [])
                },
                success: function(response) {
                    stopBatchStatusAnimation();
                    (response.data.batch_logs || []).forEach(logEntry => categorizeAndLog(selectiveSyncLogDiv, logEntry));

                    if (response.success) {
                        currentProductVariationData.current_variation_offset += combinationsBatch.length;
                        updateOverallSelectiveProgress(); // Update progress based on variations
                        processProductVariationBatch(); // Process next batch or complete
                    } else {
                        logMessage(selectiveSyncLogDiv, i18n.error_importing_variations.replace('{productName}', item_name) + (response.data.message ? `: ${response.data.message}` : ''), 'error');
                        // Decide to skip remaining variations for this product and move on
                        currentProductVariationData = null; // Stop processing variations for this product
                        currentSelectiveImportProductIndex++; // Mark parent product as "done" (with errors)
                        updateOverallSelectiveProgress();
                        processNextSelectedProduct();
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    stopBatchStatusAnimation();
                    logMessage(selectiveSyncLogDiv, `AJAX Error during variation batch for ${item_name}: ${textStatus} ${errorThrown || ''}`, 'error');
                    // Decide to skip remaining variations for this product and move on
                    currentProductVariationData = null;
                    currentSelectiveImportProductIndex++;
                    updateOverallSelectiveProgress();
                    processNextSelectedProduct();
                }
            });
        }
        
        function updateOverallSelectiveProgress() {
            let overallProgress = 0;
            const totalProductsToImport = productsToImportSelectedIds.length;

            if (totalProductsToImport === 0) {
                updateProgressBar(selectiveSyncProgressBar, 0);
                return;
            }
            
            if (currentProductVariationData) { 
                 overallProgress = ( (currentSelectiveImportProductIndex) / totalProductsToImport) * 100;
                 const { total_combinations, current_variation_offset } = currentProductVariationData;
                 if (total_combinations > 0) {
                     const variationProgressForCurrentProduct = (current_variation_offset / total_combinations) * (1 / totalProductsToImport) * 100;
                     overallProgress += variationProgressForCurrentProduct;
                 }
            } else { 
                 overallProgress = (currentSelectiveImportProductIndex / totalProductsToImport) * 100;
            }

            // Pass the potentially float overallProgress to allow smoother animation
            updateProgressBar(selectiveSyncProgressBar, overallProgress); 
        }


        // --- Helper Functions ---
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

        // --- Fix Category Hierarchy Logic ---
        $('#fix-category-hierarchy-button').on('click', function(e) {
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

    });
})(jQuery);