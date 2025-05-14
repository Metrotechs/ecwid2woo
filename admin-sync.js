(function($) {
    $(document).ready(function() {
        if (typeof ecwid_sync_params === 'undefined') {
            console.error('Ecwid Sync Error: ecwid_sync_params is not defined. Ensure wp_localize_script is working correctly.');
            $('#full-sync-status').text('Error: Plugin script parameters not loaded.').css('color', 'red');
            $('#selective-sync-status').text('Error: Plugin script parameters not loaded.').css('color', 'red');
            return;
        }

        const ajax_url = ecwid_sync_params.ajax_url;
        const nonce = ecwid_sync_params.nonce;
        const i18n = ecwid_sync_params.i18n;
        const fullSyncSteps = ecwid_sync_params.sync_steps || ['categories', 'products'];
        const totalFullSyncSteps = fullSyncSteps.length;

        // Full Sync UI Elements
        const fullSyncButton = $('#full-sync-button');
        const fullSyncProgressBar = $('#full-sync-bar');
        const fullSyncStatusDiv = $('#full-sync-status');
        const fullSyncLogDiv = $('#full-sync-log');

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

        // --- Full Sync Logic ---
        fullSyncButton.on('click', function(e) {
            e.preventDefault();
            if (fullSyncButton.hasClass('disabled')) return;

            fullSyncButton.addClass('disabled').text(i18n.syncing_button);
            fullSyncStatusDiv.text(i18n.sync_starting);
            updateProgressBar(fullSyncProgressBar, 0);
            fullSyncLogDiv.html('');

            currentFullSyncStepIndex = 0;
            overallFullSyncProgressOffset = 0;

            logMessage(fullSyncLogDiv, i18n.sync_starting, 'info');
            processNextFullSyncStep();
        });

        function processNextFullSyncStep() {
            if (currentFullSyncStepIndex < totalFullSyncSteps) {
                const syncType = fullSyncSteps[currentFullSyncStepIndex];
                updateStatus(fullSyncStatusDiv, i18n.syncing + ' ' + syncType + '...');
                processFullSyncBatch(syncType, 0);
            } else {
                updateStatus(fullSyncStatusDiv, i18n.sync_complete);
                logMessage(fullSyncLogDiv, i18n.sync_complete, 'success');
                fullSyncButton.removeClass('disabled').text(i18n.start_sync);
                updateProgressBar(fullSyncProgressBar, 100);
            }
        }

        function processFullSyncBatch(syncType, offset) {
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
                    if (response.success) {
                        logMessage(fullSyncLogDiv, response.data.message, 'info');
                        if (response.data.batch_logs && Array.isArray(response.data.batch_logs)) {
                            response.data.batch_logs.forEach(logEntry => categorizeAndLog(fullSyncLogDiv, logEntry));
                        }

                        let currentStepProgressPercentage = 0;
                        if (response.data.total_items > 0) {
                            let effectiveNextOffset = Math.min(response.data.next_offset, response.data.total_items);
                            currentStepProgressPercentage = (effectiveNextOffset / response.data.total_items) * 100;
                        } else if (offset === 0 && !response.data.has_more) {
                            currentStepProgressPercentage = 100;
                        }

                        let overallProgress = overallFullSyncProgressOffset + (currentStepProgressPercentage / totalFullSyncSteps);
                        overallProgress = Math.min(overallProgress, 100);
                        updateProgressBar(fullSyncProgressBar, Math.round(overallProgress));
                        updateStatus(fullSyncStatusDiv, i18n.syncing + ' ' + syncType + ' (' + Math.round(currentStepProgressPercentage) + '%)');

                        if (response.data.has_more) {
                            processFullSyncBatch(syncType, response.data.next_offset);
                        } else {
                            overallFullSyncProgressOffset += (100 / totalFullSyncSteps);
                            currentFullSyncStepIndex++;
                            processNextFullSyncStep();
                        }
                    } else {
                        handleAjaxError(fullSyncStatusDiv, fullSyncLogDiv, fullSyncButton, i18n.start_sync, syncType, response.data);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    handleAjaxError(fullSyncStatusDiv, fullSyncLogDiv, fullSyncButton, i18n.start_sync, syncType, { message: textStatus + (errorThrown ? ' - ' + errorThrown : '') }, true);
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

    });
})(jQuery);