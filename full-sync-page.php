<?php
/**
 * Full Sync Page Handler for Ecwid2Woo Plugin
 * 
 * Handles all full sync related functionality including:
 * - Full sync page rendering
 * - AJAX handlers for batch sync operations
 * - Sync count fetching and preview
 * - Progress tracking and error handling
 * 
 * @package Ecwid2Woo
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Ecwid2Woo_Full_Sync {
    
    private $parent_plugin;
    private $sync_steps = ['categories', 'products', 'customers', 'orders']; // Define order of sync for full sync
    
    public function __construct($parent_plugin) {
        $this->parent_plugin = $parent_plugin;
        
        // Register AJAX handlers for full sync operations
        add_action('wp_ajax_ecwid_wc_batch_sync', [$this, 'ajax_batch_sync']);
        add_action('wp_ajax_ecwid_wc_fetch_full_sync_counts', [$this, 'ajax_fetch_full_sync_counts']);
    }
    
    /**
     * Render the Full Sync page
     */
    public function render_full_sync_page() {
        ?>
        <div class="ecwid-page-header">
            <h1><?php esc_html_e('Full Data Sync', 'ecwid2woo'); ?></h1>
            <p class="description"><?php esc_html_e('This will sync all categories, products, customers, and orders from Ecwid to WooCommerce. The sync happens in order: Categories → Products → Customers → Orders. It is recommended to backup your WooCommerce data before running a full sync for the first time.', 'ecwid2woo'); ?></p>
        </div>

        <!-- Navigation Bar -->
        <div class="ecwid-page-nav">
            <a href="<?php echo esc_url(admin_url('admin.php?page=' . $this->parent_plugin->settings_slug)); ?>" class="nav-link">
                <span class="nav-icon">⚙️</span> <?php esc_html_e('Settings', 'ecwid2woo'); ?>
            </a>
            <span class="nav-link current">
                <span class="nav-icon">🔄</span> <?php esc_html_e('Full Sync', 'ecwid2woo'); ?>
            </span>
            <a href="<?php echo esc_url(admin_url('admin.php?page=' . $this->parent_plugin->category_sync_slug)); ?>" class="nav-link">
                <span class="nav-icon">📁</span> <?php esc_html_e('Category Sync', 'ecwid2woo'); ?>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=' . $this->parent_plugin->partial_sync_slug)); ?>" class="nav-link">
                <span class="nav-icon">🎯</span> <?php esc_html_e('Product Sync', 'ecwid2woo'); ?>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=' . $this->parent_plugin->customer_sync_slug)); ?>" class="nav-link">
                <span class="nav-icon">👥</span> <?php esc_html_e('Customer Sync', 'ecwid2woo'); ?>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=' . $this->parent_plugin->order_sync_slug)); ?>" class="nav-link">
                <span class="nav-icon">📦</span> <?php esc_html_e('Order Sync', 'ecwid2woo'); ?>
            </a>
        </div>

        <div class="ecwid-sync-container">
            <div id="full-sync-initial-info" class="selective-sync-initial-info">
                <!-- This will be populated by JavaScript -->
            </div>

            <button id="load-full-sync-preview-button" class="button button-primary"><?php esc_html_e('Reload Sync Data', 'ecwid2woo'); ?></button>

            <div id="full-sync-preview-container" class="sync-preview-container">
                <div class="sync-preview-grid">
                    <div class="sync-preview-column">
                        <h3><?php esc_html_e('Categories to be Synced:', 'ecwid2woo'); ?></h3>
                        <div id="full-sync-category-preview-list" class="sync-preview-list"></div>
                    </div>
                    <div class="sync-preview-column">
                        <h3><?php esc_html_e('Products to be Synced:', 'ecwid2woo'); ?></h3>
                        <div id="full-sync-product-preview-list" class="sync-preview-list"></div>
                    </div>
                    <div class="sync-preview-column">
                        <h3><?php esc_html_e('Customers to be Synced:', 'ecwid2woo'); ?></h3>
                        <div id="full-sync-customer-preview-list" class="sync-preview-list"></div>
                    </div>
                    <div class="sync-preview-column">
                        <h3><?php esc_html_e('Orders to be Synced:', 'ecwid2woo'); ?></h3>
                        <div id="full-sync-order-preview-list" class="sync-preview-list"></div>
                    </div>
                </div>
            </div>
            
            <button id="full-sync-button" class="button button-primary sync-button-primary"><?php esc_html_e('Start Full Sync', 'ecwid2woo'); ?></button>
            <button id="stop-full-sync-button" class="button button-secondary sync-button-stop"><?php esc_html_e('STOP SYNC', 'ecwid2woo'); ?></button>
            
            <div id="full-sync-status" class="sync-status margin-top-15"></div>
            <div id="full-sync-counts-info" class="sync-counts-info"><?php esc_html_e('Item counts will be displayed here.', 'ecwid2woo'); ?></div>
            
            <div class="sync-progress-wrapper">
                <label for="full-sync-bar" class="sync-progress-label"><?php esc_html_e('Overall Progress:', 'ecwid2woo'); ?></label>
                <div id="full-sync-progress-container" class="sync-progress-container">
                    <div id="full-sync-bar" class="sync-progress-bar">0%</div>
                </div>
            </div>

            <div id="full-sync-log" class="sync-log"></div>
        </div>
        <?php
    }

    /**
     * AJAX handler for batch sync operations
     */
    public function ajax_batch_sync() {
        // Check WooCommerce availability first
        if (!class_exists('WooCommerce')) {
            wp_send_json_error([
                'message' => __('WooCommerce is not installed or activated. Please install WooCommerce to use this plugin.', 'ecwid2woo'),
                'error_type' => 'missing_dependency'
            ]);
            return;
        }
        
        // Set up error handling for fatal errors (memory/time limits)
        register_shutdown_function([$this, 'handle_sync_fatal_error']);
        
        check_ajax_referer('ecwid_wc_sync_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized', 'ecwid2woo')]); return;
        }
        
        // Enhanced resource management with more aggressive limits
        set_time_limit(300); // phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged -- Legitimate use for bulk variation processing
        
        // Flexible memory management - try to use more memory if available
        $current_memory = ini_get('memory_limit');
        $memory_in_bytes = wp_convert_hr_to_bytes($current_memory);
        $minimum_memory = 128 * 1024 * 1024; // 128MB minimum requirement
        
        // Always try to raise memory limit for better performance if possible
        wp_raise_memory_limit('admin');
        
        // Check if we meet minimum requirements
        $current_memory = ini_get('memory_limit');
        $memory_in_bytes = wp_convert_hr_to_bytes($current_memory);
        if ($memory_in_bytes < $minimum_memory) {
            wp_send_json_error([
                'message' => __('Server memory limit too low for category sync. Current: ', 'ecwid2woo') . $current_memory . __(' Minimum required: 128M', 'ecwid2woo'),
                'error_type' => 'memory_limit',
                'current_limit' => $current_memory,
                'minimum_limit' => '128M'
            ]);
            return;
        }
        
        // Wrap entire function in try-catch for better error handling
        try {

        $api_essentials = $this->parent_plugin->_get_api_essentials();
        if (is_wp_error($api_essentials)) {
            wp_send_json_error(['message' => $api_essentials->get_error_message()]); return;
        }

        // Sync currency at the start of batch operations
        $currency_sync_logs = [];
        $currency_sync_result = $this->parent_plugin->sync_currency_settings($currency_sync_logs);
        if (defined('WP_DEBUG') && WP_DEBUG && !empty($currency_sync_result)) {
            error_log("Ecwid Sync: Currency sync result for batch sync: " . print_r($currency_sync_result, true)); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log,WordPress.PHP.DevelopmentFunctions.error_log_print_r -- Debug logging wrapped in WP_DEBUG check
        }

        // MODIFICATION: Use different batch sizes based on content type for optimal performance
        // Categories are lighter and can handle larger batches, products are heavier due to variations
        $sync_type = isset($_POST['sync_type']) ? sanitize_text_field(wp_unslash($_POST['sync_type'])) : '';
        
        // Determine appropriate batch size based on sync type and available memory
        if ($sync_type === 'categories') {
            $default_batch_size = ECWID2WOO_CATEGORY_BATCH_SIZE;
        } else {
            $default_batch_size = ECWID2WOO_PRODUCT_BATCH_SIZE;
        }
        
        // Adaptive batch sizing based on memory
        $available_memory = wp_convert_hr_to_bytes(ini_get('memory_limit'));
        $used_memory = function_exists('memory_get_usage') ? memory_get_usage(true) : 0;
        $free_memory = $available_memory - $used_memory;
        
        // If we have less than 128MB free, reduce batch size
        if ($free_memory < (128 * 1024 * 1024)) {
            $memory_factor = max(0.5, min(1.0, $free_memory / (128 * 1024 * 1024)));
            $default_batch_size = max(2, intval($default_batch_size * $memory_factor));
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Ecwid Sync: Reducing batch size due to low memory. Free: " . size_format($free_memory) . ", Adjusted batch: $default_batch_size"); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            }
        }
        
        $limit_per_api_call = apply_filters('ecwid_wc_sync_batch_api_limit', $default_batch_size, $sync_type);
        $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Ecwid Sync: FULL BATCH - Type: $sync_type, Offset: $offset, API Limit: $limit_per_api_call, Memory: " . size_format($free_memory) . " free"); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging wrapped in WP_DEBUG check
        }

        $endpoints = ['products' => '/products', 'categories' => '/categories', 'customers' => '/customers', 'orders' => '/orders'];
        if (!isset($endpoints[$sync_type])) {
            wp_send_json_error(['message' => __('Invalid sync type for full sync.', 'ecwid2woo')]); return;
        }

        $endpoint = $endpoints[$sync_type];
        $api_url_base = $api_essentials['base_url'] . $endpoint;
        $query_params_for_url = ['limit' => $limit_per_api_call, 'offset' => $offset];

        if ($sync_type === 'products') {
            $query_params_for_url['enabled'] = 'true';
            $query_params_for_url['responseFields'] = 'items(id,sku,name,price,description,shortDescription,enabled,weight,quantity,unlimited,categoryIds,hdThumbnailUrl,imageUrl,galleryImages,options,combinations(id,sku,price,compareToPrice,defaultDisplayedPrice,defaultDisplayedCompareToPrice,options,quantity),productClassId,attributes,compareToPrice,dimensions,shipping)';
        } elseif ($sync_type === 'categories') {
            $query_params_for_url['responseFields'] = 'items(id,name,parentId,description,hdThumbnailUrl,originalImageUrl)';
        } elseif ($sync_type === 'customers') {
            $query_params_for_url['responseFields'] = 'items(id,email,name,customerGroupId,customerGroupName,acceptMarketing,registered,lang,billingPerson,shippingAddresses)';
        } elseif ($sync_type === 'orders') {
            $query_params_for_url['responseFields'] = 'items(id,orderNumber,vendorOrderNumber,subtotal,total,email,paymentMethod,paymentModule,tax,customerTaxExempt,customerTaxId,customerTaxIdValid,reversedTaxApplied,couponDiscount,paymentStatus,fulfillmentStatus,refererUrl,orderComments,volumeDiscount,customerId,membershipBasedDiscount,totalAndMembershipBasedDiscount,discount,usdTotal,globalReferer,createDate,updateDate,createTimestamp,updateTimestamp,hidden,orderExtraFields,customSurcharges,items,billingPerson,shippingPerson,shippingOption,handlingFee,predictedPackage,shipments,discountCoupon,discountInfo,creditCardStatus,externalTransactionId,paymentReference,paymentRequestId,additionalInfo,paymentParams,acceptMarketing)';
        }

        $api_url = add_query_arg($query_params_for_url, $api_url_base);
        
        // Enhanced API request with retry logic
        $response = $this->parent_plugin->make_api_request_with_retry($api_url, $api_essentials['token'], 'GET', 3);

        if (is_wp_error($response)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Ecwid Sync: API Request WP_Error for $sync_type: " . $response->get_error_message()); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging wrapped in WP_DEBUG check
            }
            // translators: %s is the error message from the WordPress HTTP API
            wp_send_json_error(['message' => sprintf(__('API Request Error: %s', 'ecwid2woo'), $response->get_error_message())]); return;
        }

        $raw_response_body = wp_remote_retrieve_body($response);
        $body = json_decode($raw_response_body, true);
        $http_code = wp_remote_retrieve_response_code($response);

        if ($http_code !== 200 || !is_array($body) || (isset($body['errorMessage']) && !empty($body['errorMessage']))) {
            // Use enhanced error handling
            $error_info = $this->parent_plugin->handle_api_error_response($response, $raw_response_body, $http_code, $sync_type);
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Ecwid Sync: API Error for $sync_type. HTTP Code: $http_code. Raw Body: " . substr($raw_response_body, 0, 500)); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging wrapped in WP_DEBUG check
            }
            
            // Provide user-friendly error message with retry suggestion for server errors
            $error_message = $error_info['user_message'];
            if ($error_info['retry_recommended']) {
                $error_message .= ' ' . __('This appears to be a temporary issue. You can try again in a few minutes.', 'ecwid2woo');
            }
            
            wp_send_json_error([
                'message' => $error_message, 
                'details' => $error_info['error_data'],
                'is_server_error' => $error_info['is_server_error'],
                'retry_recommended' => $error_info['retry_recommended']
            ]); 
            return;
        }

        $items_from_api = [];
        if (isset($body['items']) && is_array($body['items'])) {
            $items_from_api = $body['items'];
        } elseif ($sync_type === 'categories' && !isset($body['total']) && !isset($body['count'])) {
            if(is_array($body) && (empty($body) || isset($body[0]['id']))) {
                $items_from_api = $body;
            } else {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("Ecwid Sync: Categories API response for $sync_type was not in expected 'items' wrapper and not a direct array of categories. Raw Body: " . $raw_response_body); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging wrapped in WP_DEBUG check
                }
            }
        }

        $total_items_reported_by_api = $body['total'] ?? count($items_from_api);
        $count_in_current_api_response = $body['count'] ?? count($items_from_api);

        $imported_count = 0; $updated_count = 0; $skipped_count = 0; $failed_count = 0;
        $batch_detailed_logs = [];
        $batch_item_results = []; // <-- ADDED: To store structured results

        if (!empty($items_from_api)) {
            foreach ($items_from_api as $item_data) {
                if (!is_array($item_data) || !isset($item_data['id'])) {
                    $batch_detailed_logs[] = "--- [CRITICAL ERROR] Encountered invalid item in API response for $sync_type. Skipping. Item data: " . print_r($item_data, true) . " ---"; // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r -- Debug logging for invalid API response data
                    $failed_count++;
                    continue;
                }

                $result_array = null;
                if ($sync_type === 'products') {
                    $item_identifier_for_log = "Product (Ecwid ID: " . ($item_data['id'] ?? 'N/A') . ")";
                } elseif ($sync_type === 'categories') {
                    $item_identifier_for_log = "Category (Ecwid ID: " . ($item_data['id'] ?? 'N/A') . ")";
                } elseif ($sync_type === 'customers') {
                    $item_identifier_for_log = "Customer (Ecwid ID: " . ($item_data['id'] ?? 'N/A') . ")";
                } elseif ($sync_type === 'orders') {
                    $item_identifier_for_log = "Order (Ecwid ID: " . ($item_data['id'] ?? 'N/A') . ")";
                } else {
                    $item_identifier_for_log = "Item (Ecwid ID: " . ($item_data['id'] ?? 'N/A') . ")";
                }

                try {
                    switch ($sync_type) {
                        case 'products':
                            $result_array = $this->parent_plugin->product_sync_handler->import_product($item_data);
                            break;
                        case 'categories':
                            $result_array = $this->parent_plugin->category_sync_handler->import_category($item_data);
                            break;
                        case 'customers':
                            $result_array = $this->parent_plugin->customer_sync_handler->import_customer($item_data);
                            break;
                        case 'orders':
                            $result_array = $this->parent_plugin->order_sync_handler->import_order($item_data);
                            break;
                    }

                    if ($result_array && isset($result_array['status'])) {
                        $batch_item_results[] = $result_array; // <-- ADDED: Store structured result
                        if ($result_array['status'] === 'imported' || $result_array['status'] === 'imported_parent_pending_variations') $imported_count++;
                        elseif ($result_array['status'] === 'updated') $updated_count++;
                        elseif ($result_array['status'] === 'skipped' ) $skipped_count++;
                        else $failed_count++;

                        $log_item_name = esc_html($result_array['item_name'] ?? $item_identifier_for_log);
                        $log_ecwid_id = esc_html($result_array['ecwid_id'] ?? 'N/A');
                        $log_sku_info = isset($result_array['sku']) && $result_array['sku'] !== 'N/A' ? ", SKU: " . esc_html($result_array['sku']) : "";

                        $batch_detailed_logs[] = "--- Processing: {$log_item_name} (Ecwid ID: {$log_ecwid_id}{$log_sku_info}) ---";
                        if (!empty($result_array['logs']) && is_array($result_array['logs'])) {
                            foreach($result_array['logs'] as $log_line) { $batch_detailed_logs[] = "  " . esc_html($log_line); }
                        }
                        $batch_detailed_logs[] = "--- Result for {$log_ecwid_id}: " . strtoupper($result_array['status']) . " ---";
                    } else {
                        $failed_count++;
                        $current_item_log_name = ($item_data['name'] ?? ('Ecwid ID ' . ($item_data['id'] ?? 'Unknown')));
                        $batch_detailed_logs[] = "--- [CRITICAL ERROR] Failed to process item: " . esc_html($current_item_log_name) . ". Import function did not return expected result or status. Result: " . print_r($result_array, true) . " ---"; // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r -- Debug logging for processing failure analysis
                        $batch_item_results[] = [ // <-- ADDED: Store failure result
                            'status' => 'failed',
                            'item_name' => $current_item_log_name,
                            'ecwid_id' => $item_data['id'] ?? 'Unknown',
                            'logs' => ["--- [CRITICAL ERROR] Failed to process item. Import function did not return expected result or status. ---"]
                        ];
                    }
                } catch (Exception $e) {
                    $failed_count++;
                    $batch_detailed_logs[] = "--- [PHP EXCEPTION] During processing of " . esc_html($item_identifier_for_log) . ": " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine() . " ---";
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log("Ecwid Sync: PHP Exception during $sync_type import: " . $e->getMessage() . " Trace: " . $e->getTraceAsString()); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging wrapped in WP_DEBUG check
                    }
                }
                $batch_detailed_logs[] = " ";
            }
        } elseif ($offset === 0 && $limit_per_api_call > 0) {
             $batch_detailed_logs[] = "No items received from Ecwid API for $sync_type with offset $offset and limit $limit_per_api_call. This might be normal if there are no items of this type or all have been processed.";
        }

        $new_offset = $offset + $count_in_current_api_response;
        $has_more = false;
        if ($count_in_current_api_response > 0) {
            if (isset($body['total']) && isset($body['offset']) && isset($body['count'])) {
                 $has_more = ($body['total'] > ($body['offset'] + $body['count']));
            } elseif ($count_in_current_api_response === $limit_per_api_call) {
                $has_more = true;
            }
        }
        if (isset($body['total']) && $new_offset >= $body['total']) {
            $has_more = false;
        }

        wp_send_json_success([
            // translators: %1$s is the sync type, %2$d is items processed, %3$d is imported count, %4$d is updated count, %5$d is skipped count, %6$d is failed count, %7$d is total items
            'message' => sprintf(__('%1$s: Processed %2$d items fetched in this API call (Imported: %3$d, Updated: %4$d, Skipped: %5$d, Failed: %6$d). Total items for this type (Ecwid reported): %7$d.', 'ecwid2woo'), ucfirst($sync_type), count($items_from_api), $imported_count, $updated_count, $skipped_count, $failed_count, $total_items_reported_by_api),
            'next_offset' => $new_offset,
            'total_items' => $total_items_reported_by_api,
            'has_more' => $has_more,
            'processed_type' => $sync_type,
            'imported_count' => $imported_count,
            'updated_count' => $updated_count,
            'skipped_count' => $skipped_count,
            'failed_count' => $failed_count,
            'batch_logs' => $batch_detailed_logs,
            'batch_item_results' => $batch_item_results // <-- ADDED: Send structured results
        ]);
        
        } catch (Error $e) {
            // Handle fatal errors (PHP 7+)
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Ecwid Sync: Fatal Error in ajax_batch_sync: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine()); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging wrapped in WP_DEBUG check
            }
            wp_send_json_error([
                'message' => __('A critical error occurred during sync. Please check your server error logs or try again with a smaller batch size.', 'ecwid2woo'),
                'error_type' => 'fatal_error',
                'error_details' => WP_DEBUG ? $e->getMessage() : 'Enable WP_DEBUG for details'
            ]);
        } catch (Exception $e) {
            // Handle regular exceptions
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Ecwid Sync: Exception in ajax_batch_sync: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine()); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging wrapped in WP_DEBUG check
            }
            wp_send_json_error([
                'message' => __('An error occurred during sync: ', 'ecwid2woo') . $e->getMessage(),
                'error_type' => 'exception',
                'error_details' => WP_DEBUG ? $e->getTraceAsString() : 'Enable WP_DEBUG for details'
            ]);
        }
    }
    
    /**
     * Handle fatal errors during sync operations
     * This catches memory limit exceeded, time limit exceeded, etc.
     */
    public function handle_sync_fatal_error() {
        $error = error_get_last();
        
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
            // Clear any output buffers
            while (ob_get_level()) {
                ob_end_clean();
            }
            
            $error_message = $error['message'];
            $is_memory_error = stripos($error_message, 'memory') !== false || stripos($error_message, 'allowed memory size') !== false;
            $is_time_error = stripos($error_message, 'time') !== false || stripos($error_message, 'execution time') !== false;
            
            $response = [
                'success' => false,
                'data' => [
                    'message' => '',
                    'error_type' => 'fatal_error',
                    'memory_info' => [
                        'limit' => ini_get('memory_limit'),
                        'usage' => function_exists('memory_get_usage') ? size_format(memory_get_usage(true)) : 'unknown',
                        'peak' => function_exists('memory_get_peak_usage') ? size_format(memory_get_peak_usage(true)) : 'unknown'
                    ]
                ]
            ];
            
            if ($is_memory_error) {
                $response['data']['message'] = __('Sync failed due to insufficient server memory. Try reducing the batch size or increase server memory limit.', 'ecwid2woo');
                $response['data']['suggested_action'] = 'increase_memory_or_reduce_batch';
            } elseif ($is_time_error) {
                $response['data']['message'] = __('Sync failed due to server time limit. Try reducing the batch size or increase server execution time.', 'ecwid2woo');
                $response['data']['suggested_action'] = 'reduce_batch_size';
            } else {
                $response['data']['message'] = __('A critical server error occurred during sync. Check server logs for details.', 'ecwid2woo');
                $response['data']['suggested_action'] = 'check_server_logs';
            }
            
            // Log the error for debugging
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Ecwid2Woo Fatal Error: ' . $error_message . ' in ' . $error['file'] . ' on line ' . $error['line']); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            }
            
            // Send JSON response
            wp_send_json($response);
        }
    }

    /**
     * AJAX handler to fetch full sync counts
     */
    public function ajax_fetch_full_sync_counts() {
        // Check WooCommerce availability first
        if (!class_exists('WooCommerce')) {
            wp_send_json_error([
                'message' => __('WooCommerce is not installed or activated. Please install WooCommerce to use this plugin.', 'ecwid2woo'),
                'error_type' => 'missing_dependency'
            ]);
            return;
        }
        
        // Only enable enhanced debugging if WP_DEBUG is enabled and user has sufficient privileges
        $debug_mode = defined('WP_DEBUG') && WP_DEBUG && current_user_can('manage_options');
        
        try {
            check_ajax_referer('ecwid_wc_sync_nonce', 'nonce');
            if (!current_user_can('manage_options')) {
                wp_send_json_error(['message' => __('You do not have permission to perform this action.', 'ecwid2woo')], 403);
                return;
            }
            set_time_limit(300); // phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged -- Legitimate use for bulk category processing
            
            // Flexible memory management - try to use more memory if available
            wp_raise_memory_limit('admin'); // Always try to increase for better performance
            
            // Check minimum requirements for count fetching (less intensive than sync)
            $current_memory = ini_get('memory_limit');
            if (function_exists('wp_convert_hr_to_bytes')) {
                $current_bytes = wp_convert_hr_to_bytes($current_memory);
                $minimum_bytes = 128 * 1024 * 1024; // 128MB minimum requirement
                
                if ($current_bytes < $minimum_bytes) {
                    wp_send_json_error([
                        'message' => __('Server memory limit too low for sync operation. Current: ', 'ecwid2woo') . $current_memory . __(' Minimum required: 128M', 'ecwid2woo'),
                        'error_type' => 'memory_limit',
                        'current_limit' => $current_memory,
                        'minimum_limit' => '128M'
                    ]);
                    return;
                }
                
                if ($debug_mode) {
                    error_log('Ecwid2Woo: Memory available for count fetch operation: ' . $current_memory); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                }
            }

            $api_essentials = $this->parent_plugin->_get_api_essentials();
            if (is_wp_error($api_essentials)) {
                wp_send_json_error(['message' => $api_essentials->get_error_message()], 500);
                return;
            }

            $category_count = 0;
            $product_count = 0;
            $customer_count = 0;
            $order_count = 0;
            $errors = [];

            // Fetch category count and preview
            $categories_url = add_query_arg(['limit' => 5], $api_essentials['base_url'] . '/categories');
            $categories_response = wp_remote_get($categories_url, [
                'timeout' => 60,
                'headers' => ['Authorization' => 'Bearer ' . $api_essentials['token'], 'Accept' => 'application/json'],
            ]);

            $categories_preview = [];
            if (!is_wp_error($categories_response)) {
                $categories_body = json_decode(wp_remote_retrieve_body($categories_response), true);
                $categories_http_code = wp_remote_retrieve_response_code($categories_response);
                
                if ($categories_http_code === 200 && isset($categories_body['total'])) {
                    $category_count = intval($categories_body['total']);
                    if (isset($categories_body['items']) && is_array($categories_body['items'])) {
                        $categories_preview = array_slice($categories_body['items'], 0, 5);
                    }
                } else {
                    $errors[] = sprintf(__('Failed to fetch category count (HTTP %d)', 'ecwid2woo'), $categories_http_code);
                }
            } else {
                $errors[] = sprintf(__('Category count request failed: %s', 'ecwid2woo'), $categories_response->get_error_message());
            }

            // Fetch product count and preview  
            $products_url = add_query_arg([
                'limit' => 5, 
                'enabled' => 'true',
                'responseFields' => 'items(id,sku,name,enabled,price,combinations(id,sku)),total'
            ], $api_essentials['base_url'] . '/products');
            $products_response = wp_remote_get($products_url, [
                'timeout' => 60,
                'headers' => ['Authorization' => 'Bearer ' . $api_essentials['token'], 'Accept' => 'application/json'],
            ]);

            $products_preview = [];
            if (!is_wp_error($products_response)) {
                $products_body = json_decode(wp_remote_retrieve_body($products_response), true);
                $products_http_code = wp_remote_retrieve_response_code($products_response);
                
                if ($products_http_code === 200 && isset($products_body['total'])) {
                    $product_count = intval($products_body['total']);
                    if (isset($products_body['items']) && is_array($products_body['items'])) {
                        $products_preview = array_slice($products_body['items'], 0, 5);
                    }
                } else {
                    $errors[] = sprintf(__('Failed to fetch product count (HTTP %d)', 'ecwid2woo'), $products_http_code);
                }
            } else {
                $errors[] = sprintf(__('Product count request failed: %s', 'ecwid2woo'), $products_response->get_error_message());
            }

            // Fetch customer count and preview
            $customers_url = add_query_arg([
                'limit' => 5,
                'responseFields' => 'items(id,email,name,customerGroupName),total'
            ], $api_essentials['base_url'] . '/customers');
            $customers_response = wp_remote_get($customers_url, [
                'timeout' => 60,
                'headers' => ['Authorization' => 'Bearer ' . $api_essentials['token'], 'Accept' => 'application/json'],
            ]);

            $customers_preview = [];
            if (!is_wp_error($customers_response)) {
                $customers_body = json_decode(wp_remote_retrieve_body($customers_response), true);
                $customers_http_code = wp_remote_retrieve_response_code($customers_response);
                
                if ($customers_http_code === 200 && isset($customers_body['total'])) {
                    $customer_count = intval($customers_body['total']);
                    if (isset($customers_body['items']) && is_array($customers_body['items'])) {
                        $customers_preview = array_slice($customers_body['items'], 0, 5);
                    }
                } elseif ($customers_http_code === 403) {
                    // Handle permission error gracefully for customers
                    $customer_count = 0;
                    $errors[] = __('Customer access requires "Read customers" permission in your Ecwid API token.', 'ecwid2woo');
                } else {
                    $errors[] = sprintf(__('Failed to fetch customer count (HTTP %d)', 'ecwid2woo'), $customers_http_code);
                }
            } else {
                $errors[] = sprintf(__('Customer count request failed: %s', 'ecwid2woo'), $customers_response->get_error_message());
            }

            // Fetch order count and preview
            $orders_url = add_query_arg([
                'limit' => 5,
                'responseFields' => 'items(id,orderNumber,email,total,paymentStatus,fulfillmentStatus,createDate),total'
            ], $api_essentials['base_url'] . '/orders');
            $orders_response = wp_remote_get($orders_url, [
                'timeout' => 60,
                'headers' => ['Authorization' => 'Bearer ' . $api_essentials['token'], 'Accept' => 'application/json'],
            ]);

            $orders_preview = [];
            if (!is_wp_error($orders_response)) {
                $orders_body = json_decode(wp_remote_retrieve_body($orders_response), true);
                $orders_http_code = wp_remote_retrieve_response_code($orders_response);
                
                if ($orders_http_code === 200 && isset($orders_body['total'])) {
                    $order_count = intval($orders_body['total']);
                    if (isset($orders_body['items']) && is_array($orders_body['items'])) {
                        $orders_preview = array_slice($orders_body['items'], 0, 5);
                    }
                } elseif ($orders_http_code === 403) {
                    // Handle permission error gracefully for orders
                    $order_count = 0;
                    $errors[] = __('Order access requires "Read orders" permission in your Ecwid API token.', 'ecwid2woo');
                } else {
                    $errors[] = sprintf(__('Failed to fetch order count (HTTP %d)', 'ecwid2woo'), $orders_http_code);
                }
            } else {
                $errors[] = sprintf(__('Order count request failed: %s', 'ecwid2woo'), $orders_response->get_error_message());
            }

            // Send response
            $response_data = [
                'categories_count' => $category_count,
                'products_count' => $product_count,
                'customers_count' => $customer_count,
                'orders_count' => $order_count,
                'total_items' => $category_count + $product_count + $customer_count + $order_count,
                'categories_preview' => $categories_preview,
                'products_preview' => $products_preview,
                'customers_preview' => $customers_preview,
                'orders_preview' => $orders_preview,
                'success' => empty($errors),
                'debug_info' => [
                    'api_configured' => !is_wp_error($api_essentials),
                    'store_id' => !empty($api_essentials['store_id']) ? substr($api_essentials['store_id'], 0, 4) . '...' : 'Not set',
                    'has_errors' => !empty($errors),
                    'categories_api_status' => isset($categories_http_code) ? $categories_http_code : 'No response',
                    'products_api_status' => isset($products_http_code) ? $products_http_code : 'No response',
                    'customers_api_status' => isset($customers_http_code) ? $customers_http_code : 'No response',
                    'orders_api_status' => isset($orders_http_code) ? $orders_http_code : 'No response',
                    'categories_url' => isset($categories_url) ? $categories_url : 'Not set',
                    'products_url' => isset($products_url) ? $products_url : 'Not set',
                    'customers_url' => isset($customers_url) ? $customers_url : 'Not set',
                    'orders_url' => isset($orders_url) ? $orders_url : 'Not set'
                ]
            ];

            if (!empty($errors)) {
                $response_data['errors'] = $errors;
                $response_data['message'] = implode(' ', $errors);
            }

            wp_send_json_success($response_data);

        } catch (Exception $e) {
            if ($debug_mode) {
                error_log('Ecwid2Woo: Exception in ajax_fetch_full_sync_counts: ' . $e->getMessage()); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            }
            
            wp_send_json_error([
                'message' => __('An error occurred while fetching sync counts: ', 'ecwid2woo') . $e->getMessage(),
                'error_type' => 'exception'
            ], 500);
        }
    }

    /**
     * Get sync steps for full sync process
     */
    public function get_sync_steps() {
        return $this->sync_steps;
    }
}
