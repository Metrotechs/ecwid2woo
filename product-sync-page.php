<?php
/**
 * Product Sync Page Handler for Ecwid2Woo Plugin
 * 
 * Handles all product sync related functionality including:
 * - Product sync page rendering
 * - AJAX handlers for product operations
 * - Product import and processing
 * - Product image handling
 * 
 * @package Ecwid2Woo
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Ecwid2Woo_Product_Sync {
    
    private $parent_plugin;
    
    public function __construct($parent_plugin) {
        $this->parent_plugin = $parent_plugin;
        
        // Register AJAX handlers for product operations
        add_action('wp_ajax_ecwid_wc_fetch_products_for_selection', [$this, 'ajax_fetch_products_for_selection']);
        add_action('wp_ajax_ecwid_wc_import_selected_products', [$this, 'ajax_import_selected_products']);
        add_action('wp_ajax_ecwid_wc_sync_all_products', [$this, 'ajax_sync_all_products']);
    }
    
    /**
     * Render the Product Sync page
     */
    public function render_product_sync_page() {
        ?>
        <div class="ecwid-page-header">
            <h1><?php esc_html_e('Partial Product Sync', 'ecwid2woo'); ?></h1>
            <p><?php esc_html_e('Load products from your Ecwid store and select which ones to import or update in WooCommerce.', 'ecwid2woo'); ?></p>
        </div>

        <!-- Navigation Bar -->
        <div class="ecwid-page-nav">
            <a href="<?php echo esc_url(admin_url('admin.php?page=' . $this->parent_plugin->settings_slug)); ?>" class="nav-link">
                <span class="nav-icon">⚙️</span> <?php esc_html_e('Settings', 'ecwid2woo'); ?>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=' . $this->parent_plugin->full_sync_slug)); ?>" class="nav-link">
                <span class="nav-icon">🔄</span> <?php esc_html_e('Full Sync', 'ecwid2woo'); ?>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=' . $this->parent_plugin->category_sync_slug)); ?>" class="nav-link">
                <span class="nav-icon">📁</span> <?php esc_html_e('Category Sync', 'ecwid2woo'); ?>
            </a>
            <span class="nav-link current">
                <span class="nav-icon">🎯</span> <?php esc_html_e('Product Sync', 'ecwid2woo'); ?>
            </span>
            <a href="<?php echo esc_url(admin_url('admin.php?page=' . $this->parent_plugin->customer_sync_slug)); ?>" class="nav-link">
                <span class="nav-icon">👥</span> <?php esc_html_e('Customer Sync', 'ecwid2woo'); ?>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=' . $this->parent_plugin->order_sync_slug)); ?>" class="nav-link">
                <span class="nav-icon">📦</span> <?php esc_html_e('Order Sync', 'ecwid2woo'); ?>
            </a>
        </div>

        <div class="ecwid-sync-container">
            <div id="selective-sync-initial-info" class="selective-sync-initial-info">
                <!-- This will be populated by JavaScript -->
            </div>

            <button id="load-ecwid-products-button" class="button button-primary"><?php esc_html_e('Reload Products', 'ecwid2woo'); ?></button>
            <div id="selective-product-list-container" class="selective-product-list-container">
                <?php esc_html_e('Product list will appear here...', 'ecwid2woo'); ?>
            </div>
            <button id="import-selected-products-button" class="button button-primary import-selected-button"><?php esc_html_e('Import Selected Products', 'ecwid2woo'); ?></button>
            
            <!-- Bulk Actions -->
            <div class="product-bulk-actions" style="margin: 25px 0 15px 0; padding-top: 15px; border-top: 1px solid #ddd;">
                <h3><?php esc_html_e('Bulk Actions', 'ecwid2woo'); ?></h3>
                <button id="sync-all-products-button" class="button button-primary"><?php esc_html_e('Import All Products', 'ecwid2woo'); ?></button>
                <button id="stop-sync-products-button" class="button button-secondary" style="margin-left: 10px; display: none;"><?php esc_html_e('Stop Sync', 'ecwid2woo'); ?></button>
            </div>

            <div id="selective-sync-status" class="sync-status margin-top-15"></div>
            <div id="selective-sync-progress-container" class="sync-progress-container">
                <div id="selective-sync-bar" class="sync-progress-bar">0%</div>
            </div>
            <div id="selective-sync-log" class="sync-log"></div>
        </div>
        <?php
    }

    /**
     * AJAX handler to fetch products for selection
     */
    public function ajax_fetch_products_for_selection() {
        check_ajax_referer('ecwid_wc_sync_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized', 'ecwid2woo')]);
            return;
        }
        // phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged -- Needed for long-running sync operations
        set_time_limit(300);

        $api_essentials = $this->parent_plugin->_get_api_essentials();
        if (is_wp_error($api_essentials)) {
            wp_send_json_error(['message' => $api_essentials->get_error_message()]);
            return;
        }

        // Load all products at once
        $all_products = [];
        $offset = 0;
        $limit = 100;
        $api_calls_made = 0;
        $max_api_calls = 100; // Safety limit to prevent infinite loops
        
        // Variables to capture first API response for debugging
        $first_count = null;
        $first_total = null;
        $first_http_code = null;

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("=== Ecwid Product Sync: STARTING NEW PAGINATION LOGIC (v1.0.3) ==="); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
        }

        do {
            $api_calls_made++;
            
            // Safety check to prevent infinite loops
            if ($api_calls_made > $max_api_calls) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("Ecwid Product Sync: WARNING - Reached maximum API calls limit ($max_api_calls). Stopping to prevent infinite loop."); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                }
                break;
            }
            
            $query_params = [
                'limit' => $limit,
                'offset' => $offset,
                // Remove 'enabled' => 'true' to load ALL products (enabled + disabled)
                'responseFields' => 'items(id,sku,name,enabled,options,combinations(id)),total' 
            ];
            $api_url = add_query_arg($query_params, $api_essentials['base_url'] . '/products');

            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Ecwid Product Sync: API call #$api_calls_made - Fetching products with offset: $offset, limit: $limit"); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                error_log("Ecwid Product Sync: API URL: " . $api_url); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            }

            $response = wp_remote_get($api_url, [
                'timeout' => 120, // Increased timeout for large stores
                'headers' => ['Authorization' => 'Bearer ' . $api_essentials['token'], 'Accept' => 'application/json'],
            ]);

            if (is_wp_error($response)) {
                // translators: %s is the error message from the WordPress HTTP API
                wp_send_json_error(['message' => sprintf(__('API Request Error: %s', 'ecwid2woo'), $response->get_error_message())]);
                return;
            }

            $raw_response_body = wp_remote_retrieve_body($response);
            $body = json_decode($raw_response_body, true);
            $http_code = wp_remote_retrieve_response_code($response);

            // Debug the raw API response
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Ecwid Product Sync: API call #$api_calls_made - HTTP Code: $http_code"); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                error_log("Ecwid Product Sync: API call #$api_calls_made - Raw response: " . substr($raw_response_body, 0, 500) . "..."); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                if (json_last_error() !== JSON_ERROR_NONE) {
                    error_log("Ecwid Product Sync: JSON parsing error: " . json_last_error_msg()); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                }
            }

            if ($http_code !== 200 || (isset($body['errorMessage']) && !empty($body['errorMessage']))) {
                $error_info = $this->parent_plugin->handle_api_error_response($response, $raw_response_body, $http_code, 'products');
                
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

            $items_from_api = $body['items'] ?? [];
            
            // Process and transform the items
            foreach ($items_from_api as $item) {
                $all_products[] = [
                    'id' => $item['id'] ?? null,
                    'name' => $item['name'] ?? 'N/A',
                    'sku' => $item['sku'] ?? 'N/A',
                    'enabled' => $item['enabled'] ?? false,
                    'options' => $item['options'] ?? [],
                    'combinations' => $item['combinations'] ?? []
                ];
            }

            // Get count from actual items returned, not API count field (which may not exist with custom responseFields)
            $count_in_response = count($items_from_api);
            $total_from_api = $body['total'] ?? 0;
            
            // Capture first API response values for debugging
            if ($api_calls_made === 1) {
                $first_count = $count_in_response;
                $first_total = $total_from_api;
                $first_http_code = $http_code;
            }
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Ecwid Product Sync: API call #$api_calls_made - Got $count_in_response products, total available: $total_from_api, current offset: $offset"); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                error_log("Ecwid Product Sync: API response keys: " . implode(', ', array_keys($body))); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                if (isset($body['items'])) {
                    error_log("Ecwid Product Sync: Actual items in response: " . count($body['items'])); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                }
                error_log("Ecwid Product Sync: Loop will continue? " . ($count_in_response > 0 && $offset < $total_from_api ? 'YES' : 'NO')); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                error_log("Ecwid Product Sync: - count_in_response > 0: " . ($count_in_response > 0 ? 'true' : 'false') . " ($count_in_response)"); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                error_log("Ecwid Product Sync: - offset < total_from_api: " . ($offset < $total_from_api ? 'true' : 'false') . " ($offset < $total_from_api)"); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            }
            
            $offset += $count_in_response;

        } while ($count_in_response > 0 && $offset < $total_from_api);

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Ecwid Product Sync: Complete! Made $api_calls_made API calls, loaded " . count($all_products) . " total products"); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
        }

        // Separate enabled and disabled products
        $enabled_products = [];
        $disabled_products = [];
        
        foreach ($all_products as $product) {
            if ($product['enabled']) {
                $enabled_products[] = $product;
            } else {
                $disabled_products[] = $product;
            }
        }

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Ecwid Product Sync: Separated products - Enabled: " . count($enabled_products) . ", Disabled: " . count($disabled_products)); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
        }

        wp_send_json_success([
            'products' => $all_products, // Keep full list for backward compatibility
            'enabled_products' => $enabled_products,
            'disabled_products' => $disabled_products,
            'total_found' => count($all_products),
            'enabled_count' => count($enabled_products),
            'disabled_count' => count($disabled_products),
            'api_calls_made' => $api_calls_made,
            'total_available' => $total_from_api,
            'debug_info' => "New pagination logic v1.0.3 - Made $api_calls_made API calls, Loop condition: count>0 && offset<total - " . gmdate('Y-m-d H:i:s') . 
                             " | First API response: count=$first_count, total=$first_total, offset=0, HTTP=$first_http_code | Products array: " . count($all_products),
            'raw_first_response' => $api_calls_made === 1 ? substr($raw_response_body ?? '', 0, 500) : 'N/A'
        ]);
    }

    /**
     * AJAX handler to import selected products
     */
    public function ajax_import_selected_products() {
        check_ajax_referer('ecwid_wc_sync_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized', 'ecwid2woo')]);
            return;
        }
        set_time_limit(0); // phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged -- Legitimate use for bulk import operations

        $api_essentials = $this->parent_plugin->_get_api_essentials();
        if (is_wp_error($api_essentials)) {
            wp_send_json_error(['message' => $api_essentials->get_error_message()]);
            return;
        }

        // Sync currency before importing products
        $currency_sync_logs = [];
        $currency_sync_result = $this->parent_plugin->sync_currency_settings($currency_sync_logs);
        if (defined('WP_DEBUG') && WP_DEBUG && !empty($currency_sync_result)) {
            error_log("Ecwid Sync: Currency sync result for selected products import: " . print_r($currency_sync_result, true)); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log,WordPress.PHP.DevelopmentFunctions.error_log_print_r -- Debug logging wrapped in WP_DEBUG check
        }

        $ecwid_product_id = isset($_POST['ecwid_product_id']) ? intval($_POST['ecwid_product_id']) : 0;

        if (empty($ecwid_product_id)) {
            wp_send_json_error(['message' => __('No Ecwid Product ID provided for import.', 'ecwid2woo')]);
            return;
        }

        $query_params = ['responseFields' => 'id,sku,name,price,description,shortDescription,enabled,weight,quantity,unlimited,categoryIds,hdThumbnailUrl,imageUrl,galleryImages,options,combinations(id,sku,price,compareToPrice,defaultDisplayedPrice,defaultDisplayedCompareToPrice,options,quantity),productClassId,attributes,compareToPrice,dimensions,shipping'];
        $api_url = add_query_arg($query_params, $api_essentials['base_url'] . '/products/' . $ecwid_product_id);

        $response = wp_remote_get($api_url, [
            'timeout' => 120,
            'headers' => ['Authorization' => 'Bearer ' . $api_essentials['token'], 'Accept' => 'application/json'],
        ]);

        if (is_wp_error($response)) {
            // translators: %1$s is the product ID, %2$s is the error message
            wp_send_json_error(['message' => sprintf(__('API Request Error for product %1$s: %2$s', 'ecwid2woo'), $ecwid_product_id, $response->get_error_message())]);
            return;
        }

        $item_data = json_decode(wp_remote_retrieve_body($response), true);
        $http_code = wp_remote_retrieve_response_code($response);

        if ($http_code !== 200 || (isset($item_data['errorMessage']) && !empty($item_data['errorMessage']))) {
            // translators: %1$s is the product ID, %2$s is the HTTP status code, %3$s is the error message
            wp_send_json_error(['message' => sprintf(__('Ecwid API Error for product %1$s (HTTP %2$s): %3$s', 'ecwid2woo'), $ecwid_product_id, $http_code, ($item_data['errorMessage'] ?? 'Unknown error'))]);
            return;
        }

        if (empty($item_data) || !isset($item_data['id'])) {
             // translators: %s is the Ecwid product ID
             wp_send_json_error(['message' => sprintf(__('Failed to fetch valid data for Ecwid product ID %s.', 'ecwid2woo'), $ecwid_product_id)]);
            return;
        }

        $result_array = $this->import_product($item_data);

        if (isset($result_array['status']) && $result_array['status'] === 'imported_parent_pending_variations') {
            wp_send_json_success([
                'status'           => 'variations_pending', // New status for JS
                'message'          => __('Parent product imported. Variations will be processed in batches.', 'ecwid2woo'),
                'wc_product_id'    => $result_array['wc_product_id'],
                'ecwid_product_id' => $result_array['ecwid_id'],
                'item_name'        => $result_array['item_name'],
                'sku'              => $result_array['sku'],
                'all_combinations' => $item_data['combinations'] ?? [], // Send all combinations to JS
                'total_combinations' => $result_array['total_combinations'] ?? 0,
                'logs'             => $result_array['logs'] ?? ['[INFO] Parent product processed.'],
            ]);
        } elseif (isset($result_array['status']) && ($result_array['status'] === 'imported' || $result_array['status'] === 'skipped' || $result_array['status'] === 'failed')) {
            wp_send_json_success([ // For simple products or if variable product had no variations after all
                'status'     => $result_array['status'],
                'item_name'  => $result_array['item_name'] ?? ($item_data['name'] ?? 'N/A'),
                'ecwid_id'   => $result_array['ecwid_id'] ?? $ecwid_product_id,
                'sku'        => $result_array['sku'] ?? ($item_data['sku'] ?? 'N/A'),
                'logs'       => $result_array['logs'] ?? ['[ERROR] No logs returned from import_product.'],
            ]);
        } else {
            // General error or unexpected status from import_product
            wp_send_json_error([
                'message'    => __('An unexpected error occurred during product import.', 'ecwid2woo'),
                'item_name'  => ($item_data['name'] ?? 'N/A'),
                'ecwid_id'   => $ecwid_product_id,
                'sku'        => ($item_data['sku'] ?? 'N/A'),
                'logs'       => $result_array['logs'] ?? ['[CRITICAL] Unexpected result from import_product function.'],
                'raw_result' => $result_array 
            ]);
        }
    }

    /**
     * AJAX handler to sync all products
     */
    public function ajax_sync_all_products() {
        check_ajax_referer('ecwid_wc_sync_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized', 'ecwid2woo')]);
            return;
        }
        set_time_limit(0); // phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged -- Legitimate use for bulk operations

        $api_essentials = $this->parent_plugin->_get_api_essentials();
        if (is_wp_error($api_essentials)) {
            wp_send_json_error(['message' => $api_essentials->get_error_message()]);
            return;
        }

        // Sync currency before importing products
        $currency_sync_logs = [];
        $currency_sync_result = $this->parent_plugin->sync_currency_settings($currency_sync_logs);
        if (defined('WP_DEBUG') && WP_DEBUG && !empty($currency_sync_result)) {
            error_log("Ecwid Sync: Currency sync result for all products import: " . print_r($currency_sync_result, true)); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log,WordPress.PHP.DevelopmentFunctions.error_log_print_r -- Debug logging wrapped in WP_DEBUG check
        }

        $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
        $limit = 5; // Process in very small batches for all products sync to prevent timeouts

        $query_params = [
            'limit' => $limit,
            'offset' => $offset,
            'enabled' => 'true',
            'responseFields' => 'items(id,sku,name,price,description,shortDescription,enabled,weight,quantity,unlimited,categoryIds,hdThumbnailUrl,imageUrl,galleryImages,options,combinations(id,sku,price,compareToPrice,defaultDisplayedPrice,defaultDisplayedCompareToPrice,options,quantity),productClassId,attributes,compareToPrice,dimensions,shipping),total'
        ];
        $api_url = add_query_arg($query_params, $api_essentials['base_url'] . '/products');

        $response = wp_remote_get($api_url, [
            'timeout' => 120,
            'headers' => ['Authorization' => 'Bearer ' . $api_essentials['token'], 'Accept' => 'application/json'],
        ]);

        if (is_wp_error($response)) {
            // translators: %s is the error message
            wp_send_json_error(['message' => sprintf(__('API Request Error: %s', 'ecwid2woo'), $response->get_error_message())]);
            return;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        $http_code = wp_remote_retrieve_response_code($response);

        if ($http_code !== 200 || (isset($body['errorMessage']) && !empty($body['errorMessage']))) {
            // translators: %1$s is the HTTP status code, %2$s is the error message
            wp_send_json_error(['message' => sprintf(__('Ecwid API Error (HTTP %1$s): %2$s', 'ecwid2woo'), $http_code, ($body['errorMessage'] ?? 'Unknown error'))]);
            return;
        }

        $items_from_api = $body['items'] ?? [];
        $total_items = $body['total'] ?? 0;
        
        $imported_count = 0;
        $updated_count = 0;
        $skipped_count = 0;
        $failed_count = 0;
        $detailed_logs = [];

        foreach ($items_from_api as $item_data) {
            if (!is_array($item_data) || !isset($item_data['id'])) {
                $detailed_logs[] = "[CRITICAL ERROR] Encountered invalid item in API response. Skipping.";
                $failed_count++;
                continue;
            }

            $result_array = $this->import_product($item_data);

            if ($result_array && isset($result_array['status'])) {
                if ($result_array['status'] === 'imported' || $result_array['status'] === 'imported_parent_pending_variations') {
                    $imported_count++;
                } elseif ($result_array['status'] === 'updated') {
                    $updated_count++;
                } elseif ($result_array['status'] === 'skipped') {
                    $skipped_count++;
                } else {
                    $failed_count++;
                }

                $log_item_name = esc_html($result_array['item_name'] ?? 'Unknown');
                $log_ecwid_id = esc_html($result_array['ecwid_id'] ?? 'N/A');
                $log_sku_info = isset($result_array['sku']) && $result_array['sku'] !== 'N/A' ? ", SKU: " . esc_html($result_array['sku']) : "";

                $detailed_logs[] = "--- Processing: {$log_item_name} (Ecwid ID: {$log_ecwid_id}{$log_sku_info}) ---";
                if (!empty($result_array['logs']) && is_array($result_array['logs'])) {
                    foreach($result_array['logs'] as $log_line) {
                        $detailed_logs[] = "  " . esc_html($log_line);
                    }
                }
                $detailed_logs[] = "--- Result for {$log_ecwid_id}: " . strtoupper($result_array['status']) . " ---";
                $detailed_logs[] = " ";
            } else {
                $failed_count++;
                $current_item_log_name = ($item_data['name'] ?? ('Ecwid ID ' . ($item_data['id'] ?? 'Unknown')));
                $detailed_logs[] = "--- [CRITICAL ERROR] Failed to process item: " . esc_html($current_item_log_name) . " ---";
            }
        }

        $new_offset = $offset + count($items_from_api);
        $has_more = $new_offset < $total_items;

        wp_send_json_success([
            // translators: %1$d is items processed, %2$d is imported count, %3$d is updated count, %4$d is skipped count, %5$d is failed count, %6$d is total items
            'message' => sprintf(__('Processed %1$d products (Imported: %2$d, Updated: %3$d, Skipped: %4$d, Failed: %5$d). Total products: %6$d.', 'ecwid2woo'), count($items_from_api), $imported_count, $updated_count, $skipped_count, $failed_count, $total_items),
            'next_offset' => $new_offset,
            'total_items' => $total_items,
            'has_more' => $has_more,
            'imported_count' => $imported_count,
            'updated_count' => $updated_count,
            'skipped_count' => $skipped_count,
            'failed_count' => $failed_count,
            'batch_logs' => $detailed_logs
        ]);
    }

    /**
     * Import a single product from Ecwid data
     * 
     * @param array $item The product data from Ecwid API
     * @return array Result array with status, logs, etc.
     */
    public function import_product($item) {
        $product_logs = [];
        $product_name_for_log = isset($item['name']) ? sanitize_text_field($item['name']) : '[No Name]';
        $ecwid_id_for_log = $item['id'] ?? 'N/A';
        $sku_for_log = $item['sku'] ?? 'N/A';

        // Skip disabled products - they often have incomplete data
        if (isset($item['enabled']) && $item['enabled'] === false) {
            // translators: %s is the Ecwid product ID
            $product_logs[] = sprintf(__("Skipping disabled product (Ecwid ID: %s). Disabled products are not synced to WooCommerce.", 'ecwid2woo'), $ecwid_id_for_log);
            return ['status' => 'skipped', 'logs' => $product_logs, 'item_name' => $product_name_for_log, 'ecwid_id' => $ecwid_id_for_log, 'sku' => $sku_for_log];
        }

        // Basic checks for essential data
        if (!class_exists('WC_Product_Factory')) {
            $product_logs[] = __("[CRITICAL] WooCommerce is not active or WC_Product_Factory class not found.", 'ecwid2woo');
            return ['status' => 'failed', 'logs' => $product_logs, 'item_name' => $product_name_for_log, 'ecwid_id' => $ecwid_id_for_log, 'sku' => $sku_for_log];
        }
        if ($ecwid_id_for_log === 'N/A' || $sku_for_log === 'N/A') {
            // translators: %1$s is the Ecwid ID, %2$s is the SKU, %3$s is the raw item data
            $error_message = __('[CRITICAL] Product missing Ecwid ID or SKU. Ecwid ID: %1$s, SKU: %2$s. Raw item: %3$s', 'ecwid2woo');
            $product_logs[] = sprintf($error_message, $ecwid_id_for_log, $sku_for_log, wp_json_encode($item));
            error_log("Ecwid Sync: Product (Ecwid ID: $ecwid_id_for_log) missing SKU or ID. Data: " . print_r($item, true)); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log,WordPress.PHP.DevelopmentFunctions.error_log_print_r -- Critical error logging for missing product data
            return ['status' => 'failed', 'logs' => $product_logs, 'item_name' => $product_name_for_log, 'ecwid_id' => $ecwid_id_for_log, 'sku' => $sku_for_log];
        }

        $log_product_identifier = "PRODUCT (Ecwid ID: {$ecwid_id_for_log}, SKU: {$sku_for_log}, Name: \"" . esc_html($product_name_for_log) . "\")";
        // translators: %s is the product identifier string with ID, SKU, and name
        $product_logs[] = sprintf(__("Starting import for %s", 'ecwid2woo'), $log_product_identifier);
        
        $product_logs[] = "Raw Ecwid Item Data (for parent product prices): Price Field = " . ($item['price'] ?? 'NOT_SET') . ", CompareToPrice Field = " . ($item['compareToPrice'] ?? 'NOT_SET');

        // --- ENHANCED SKU VALIDATION ---
        $sku_validation_result = $this->validate_sku_integrity($item, $ecwid_id_for_log);
        if (!$sku_validation_result['is_valid']) {
            $product_logs[] = "[SKU VALIDATION ERROR] " . $sku_validation_result['error_message'];
            return ['status' => 'failed', 'logs' => $product_logs, 'item_name' => $product_name_for_log, 'ecwid_id' => $ecwid_id_for_log, 'sku' => $sku_for_log];
        }
        if (!empty($sku_validation_result['warnings'])) {
            foreach ($sku_validation_result['warnings'] as $warning) {
                $product_logs[] = "[SKU WARNING] " . $warning;
            }
        }

        // --- PRODUCT IDENTIFICATION AND TYPE HANDLING ---
        $product_id_by_ecwid_id = null;
        $existing_products_by_ecwid_id_query = get_posts(['post_type' => 'product', 'meta_key' => '_ecwid_product_id', 'meta_value' => $ecwid_id_for_log, 'post_status' => 'any', 'numberposts' => 1, 'fields' => 'ids']);
        if (!empty($existing_products_by_ecwid_id_query)) $product_id_by_ecwid_id = $existing_products_by_ecwid_id_query[0];

        $product_id_by_sku = wc_get_product_id_by_sku($sku_for_log);
        $product_id = $product_id_by_ecwid_id ?: $product_id_by_sku; // Prioritize match by Ecwid ID

        if ($product_id && !$product_id_by_ecwid_id && $product_id_by_sku) {
            // Found by SKU but not by Ecwid ID meta, so update meta for future matches
            update_post_meta($product_id, '_ecwid_product_id', $ecwid_id_for_log);
            $product_logs[] = "Updated Ecwid ID meta for existing WC Product ID $product_id (found by SKU).";
        }

        $is_variable_from_ecwid = isset($item['combinations']) && !empty($item['combinations']);
        $product_logs[] = $is_variable_from_ecwid ? "Ecwid product has combinations, will be treated as Variable." : "Ecwid product has no combinations, will be treated as Simple.";
        
        $product = null;
        if ($product_id) {
            $product = wc_get_product($product_id);
            if ($product) {
                $product_logs[] = "Existing WC Product ID found: $product_id. Current type: " . $product->get_type();
                
                // --- SMART SKIP LOGIC: Check if product needs updating ---
                $should_skip = false;
                $ecwid_updated_timestamp = null;
                $local_import_timestamp = get_post_meta($product_id, '_ecwid_last_import_time', true);
                
                // Check if Ecwid product has an updated/modified timestamp
                if (isset($item['updated'])) {
                    $ecwid_updated_timestamp = strtotime($item['updated']);
                } elseif (isset($item['lastUpdated'])) {
                    $ecwid_updated_timestamp = strtotime($item['lastUpdated']);
                } elseif (isset($item['dateUpdated'])) {
                    $ecwid_updated_timestamp = strtotime($item['dateUpdated']);
                } elseif (isset($item['modifiedDate'])) {
                    $ecwid_updated_timestamp = strtotime($item['modifiedDate']);
                } elseif (isset($item['createTimestamp'])) {
                    $ecwid_updated_timestamp = $item['createTimestamp']; // Already a timestamp
                } elseif (isset($item['created'])) {
                    $ecwid_updated_timestamp = strtotime($item['created']);
                }
                
                // Debug: Log what timestamp fields are available
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    $available_dates = [];
                    foreach (['updated', 'lastUpdated', 'dateUpdated', 'modifiedDate', 'createTimestamp', 'created'] as $field) {
                        if (isset($item[$field])) {
                            $available_dates[] = "$field: " . $item[$field];
                        }
                    }
                    if (!empty($available_dates)) {
                        $product_logs[] = "DEBUG: Available Ecwid timestamps: " . implode(', ', $available_dates);
                    } else {
                        $product_logs[] = "DEBUG: No Ecwid timestamp fields found in API response";
                    }
                }
                
                // If we have both timestamps, compare them
                if ($ecwid_updated_timestamp && $local_import_timestamp) {
                    if ($ecwid_updated_timestamp <= $local_import_timestamp) {
                        $should_skip = true;
                        $product_logs[] = "SKIPPING: Product has not been modified since last import. Ecwid updated: " . gmdate('Y-m-d H:i:s', $ecwid_updated_timestamp) . ", Last imported: " . gmdate('Y-m-d H:i:s', $local_import_timestamp);
                    } else {
                        $product_logs[] = "UPDATE NEEDED: Product has been modified since last import. Ecwid updated: " . gmdate('Y-m-d H:i:s', $ecwid_updated_timestamp) . ", Last imported: " . gmdate('Y-m-d H:i:s', $local_import_timestamp);
                    }
                } elseif ($local_import_timestamp) {
                    // Product was imported before but no Ecwid timestamp - be more conservative about re-processing
                    // Check if it was imported recently (within last 24 hours) - if so, likely safe to skip
                    $hours_since_import = (time() - $local_import_timestamp) / 3600;
                    if ($hours_since_import < 24) {
                        $should_skip = true;
                        $product_logs[] = "SKIPPING: Product was imported recently (" . round($hours_since_import, 1) . " hours ago) with no Ecwid timestamp. Likely unchanged.";
                    } else {
                        $product_logs[] = "Product was previously imported but no Ecwid update timestamp available. Will update to be safe.";
                    }
                } elseif ($product_id_by_ecwid_id) {
                    // Product exists with Ecwid ID but no timestamp - this was imported before timestamp tracking
                    // Skip it to avoid unnecessary re-processing unless we can confirm it needs updating
                    if ($ecwid_updated_timestamp) {
                        // We have Ecwid timestamp but no local timestamp - check product modification date as fallback
                        $product_modified_time = strtotime($product->get_date_modified()->date('Y-m-d H:i:s'));
                        if ($ecwid_updated_timestamp <= $product_modified_time) {
                            $should_skip = true;
                            $product_logs[] = "SKIPPING: Previously imported product with no changes. Ecwid updated: " . gmdate('Y-m-d H:i:s', $ecwid_updated_timestamp) . ", WC modified: " . gmdate('Y-m-d H:i:s', $product_modified_time);
                            // Set timestamp for future reference
                            update_post_meta($product_id, '_ecwid_last_import_time', time());
                        } else {
                            $product_logs[] = "UPDATE NEEDED: Previously imported product needs updating. Ecwid updated: " . gmdate('Y-m-d H:i:s', $ecwid_updated_timestamp) . ", WC modified: " . gmdate('Y-m-d H:i:s', $product_modified_time);
                        }
                    } else {
                        // No timestamps available - skip to avoid unnecessary re-processing
                        $should_skip = true;
                        $product_logs[] = "SKIPPING: Previously imported product with no timestamp data available. Avoiding re-processing.";
                        // Set timestamp for future reference
                        update_post_meta($product_id, '_ecwid_last_import_time', time());
                    }
                } else {
                    // No local timestamp - this is likely a first import or incomplete import
                    $product_logs[] = "No previous import timestamp found. Will process product.";
                }
                
                // Return early if skipping
                if ($should_skip) {
                    return [
                        'status' => 'skipped',
                        'logs' => $product_logs,
                        'item_name' => $product_name_for_log,
                        'ecwid_id' => $ecwid_id_for_log,
                        'sku' => $sku_for_log,
                        'wc_product_id' => $product_id
                    ];
                }
                
                // Handle product type change if necessary
                $current_wc_type = $product->get_type();
                if ($is_variable_from_ecwid && $current_wc_type !== 'variable') {
                    $product_logs[] = "Changing product type from '$current_wc_type' to 'variable'.";
                    wp_set_object_terms($product_id, 'variable', 'product_type');
                    clean_product_caches($product_id); $product = wc_get_product($product_id); // Re-fetch product
                } elseif (!$is_variable_from_ecwid && $current_wc_type === 'variable') {
                    $product_logs[] = "Changing product type from 'variable' to 'simple'. Deleting existing variations.";
                    $variable_product_to_clear = wc_get_product($product_id); // Ensure it's the variable type
                    if ($variable_product_to_clear && $variable_product_to_clear->is_type('variable')) {
                        foreach ($variable_product_to_clear->get_children() as $child_id) {
                            $child_product = wc_get_product($child_id);
                            if ($child_product) { $child_product->delete(true); $product_logs[] = "Deleted variation ID $child_id."; }
                        }
                    }
                    wp_set_object_terms($product_id, 'simple', 'product_type');
                    clean_product_caches($product_id); $product = wc_get_product($product_id); // Re-fetch product
                }
            } else {
                $product_logs[] = "[WARNING] Could not load existing WC Product ID $product_id despite it being found. Treating as new.";
                $product_id = 0; // Reset product_id to create new
            }
        } else {
            $product_logs[] = "No existing WC Product found. Creating new.";
        }

        if (!$product) { // If still no product object (new or failed to load existing)
            $product_class = $is_variable_from_ecwid ? 'WC_Product_Variable' : 'WC_Product_Simple';
            $product_logs[] = "Instantiating new $product_class.";
            $product = new $product_class();
            if ($product_id) $product->set_id($product_id); // This case should ideally not be hit if $product is null
        }

        if (!$product) { // Final check if product object creation failed
            $product_logs[] = "[CRITICAL] Could not get or create WC_Product object.";
            return ['status' => 'failed', 'logs' => $product_logs, 'item_name' => $product_name_for_log, 'ecwid_id' => $ecwid_id_for_log, 'sku' => $sku_for_log];
        }

        try {
            // --- CORE PRODUCT DATA ---
            $product->set_name(sanitize_text_field($item['name'] ?? ''));
            $product->set_sku(sanitize_text_field($item['sku'])); // SKU already used for matching, ensure it's set
            $product->set_description(wp_kses_post($item['description'] ?? ''));
            $product->set_short_description(wp_kses_post($item['shortDescription'] ?? ''));
            $product->set_status((isset($item['enabled']) && $item['enabled']) ? 'publish' : 'draft');
            if (isset($item['weight'])) $product->set_weight(wc_format_decimal($item['weight']));
            
            if (isset($item['dimensions']) && is_array($item['dimensions'])) {
                if (isset($item['dimensions']['length'])) $product->set_length(wc_format_decimal($item['dimensions']['length']));
                if (isset($item['dimensions']['width'])) $product->set_width(wc_format_decimal($item['dimensions']['width']));
                if (isset($item['dimensions']['height'])) $product->set_height(wc_format_decimal($item['dimensions']['height']));
            }

            // --- PRICING AND STOCK (Simple or Parent Variable) ---
            if (!$product->is_type('variable')) { // Simple Product
                $product_logs[] = "Setting details for Simple product.";
                $product->set_regular_price(strval($item['price'] ?? '0'));
                if (isset($item['compareToPrice'])) $product->set_sale_price(strval($item['compareToPrice'])); else $product->set_sale_price('');
                
                if (isset($item['quantity'])) {
                    $product->set_manage_stock(true); 
                    $product->set_stock_quantity(intval($item['quantity']));
                    $product->set_stock_status(intval($item['quantity']) > 0 ? 'instock' : 'outofstock');
                } elseif (isset($item['unlimited']) && $item['unlimited']) {
                    $product->set_manage_stock(false); 
                    $product->set_stock_quantity(null); 
                    $product->set_stock_status('instock');
                } else { // Default if no stock info for simple product
                    $product->set_manage_stock(false); 
                    $product->set_stock_quantity(null); 
                    $product->set_stock_status('outofstock');
                }
            } else { // Variable Product (Parent)
                 $product_logs[] = "Setting details for Variable product (parent). Price will be synced from variations or use base price.";
                 $product->set_manage_stock(false); // Stock is managed at variation level
            }

            // --- CATEGORY ASSIGNMENT ---
            if (isset($item['categoryIds']) && is_array($item['categoryIds']) && !empty($item['categoryIds'])) {
                $product_logs[] = "Product has " . count($item['categoryIds']) . " category IDs from Ecwid: " . implode(', ', $item['categoryIds']);
                $wc_category_ids = [];
                foreach ($item['categoryIds'] as $ecwid_category_id) {
                    $product_logs[] = "Looking for WooCommerce category with Ecwid ID: $ecwid_category_id";
                    
                    // Use the parent plugin's helper function for category lookup
                    $wc_term_id = $this->parent_plugin->get_term_id_by_ecwid_id($ecwid_category_id, 'product_cat', true);
                    
                    if ($wc_term_id) {
                        // Get the category details
                        $category = get_term($wc_term_id, 'product_cat');
                        if ($category && !is_wp_error($category)) {
                            $wc_category_ids[] = $wc_term_id;
                            $product_logs[] = "✓ FOUND and assigned to category: " . $category->name . " (WC ID: $wc_term_id)";
                        } else {
                            $product_logs[] = "✗ Found term ID $wc_term_id but couldn't load category details";
                        }
                    } else {
                        $product_logs[] = "✗ Category with Ecwid ID $ecwid_category_id NOT FOUND via helper function";
                        
                        // Debug: Check total categories
                        $all_imported_categories = get_terms([
                            'taxonomy' => 'product_cat',
                            'meta_key' => '_ecwid_category_id',
                            'hide_empty' => false,
                            'fields' => 'ids'
                        ]);
                        $total_categories = is_array($all_imported_categories) ? count($all_imported_categories) : 0;
                        $product_logs[] = "Total imported categories: $total_categories";
                        
                        // Try to create it automatically
                        $created_category = $this->auto_create_category($ecwid_category_id, $product_logs);
                        if ($created_category) {
                            $wc_category_ids[] = $created_category['term_id'];
                            $product_logs[] = "AUTO-CREATED category: " . $created_category['name'] . " (WC ID: " . $created_category['term_id'] . ")";
                        } else {
                            $product_logs[] = "Category with Ecwid ID $ecwid_category_id not found in WooCommerce and could not be auto-created. Skipping assignment.";
                        }
                    }
                }
                if (!empty($wc_category_ids)) {
                    $product->set_category_ids($wc_category_ids);
                    $product_logs[] = "✓ ASSIGNED product to " . count($wc_category_ids) . " categories: " . implode(', ', $wc_category_ids);
                } else {
                    $product_logs[] = "✗ No valid WooCommerce categories found for assignment - product will be UNCATEGORIZED.";
                }
            } else {
                $product_logs[] = "No categories assigned in Ecwid (categoryIds missing/empty).";
            }

            // --- PRODUCT ATTRIBUTES ---
            if (isset($item['attributes']) && is_array($item['attributes'])) {
                $product_logs[] = "Processing " . count($item['attributes']) . " product attributes.";
                $wc_attributes = [];
                
                foreach ($item['attributes'] as $attr) {
                    if (!isset($attr['name']) || !isset($attr['value'])) continue;
                    
                    $attr_name = $this->parent_plugin->sanitize_attribute_name($attr['name']);
                    $attr_value = sanitize_text_field($attr['value']);
                    
                    // Create WooCommerce attribute
                    $attribute = new WC_Product_Attribute();
                    $attribute->set_id(0);
                    $attribute->set_name($attr_name);
                    $attribute->set_options([$attr_value]);
                    $attribute->set_position(count($wc_attributes));
                    $attribute->set_visible(true);
                    $attribute->set_variation(false);
                    
                    $wc_attributes[] = $attribute;
                    $product_logs[] = "Added attribute: $attr_name = $attr_value";
                }
                
                $product->set_attributes($wc_attributes);
            }

            // --- SAVE PRODUCT ---
            $product_id = $product->save();
            update_post_meta($product_id, '_ecwid_product_id', $ecwid_id_for_log);
            update_post_meta($product_id, '_ecwid_last_import_time', time()); // Track import timestamp for smart skipping
            
            $product_logs[] = "Product saved with WC ID: $product_id";

            // --- HANDLE IMAGES ---
            $product_logs[] = "=== STARTING IMAGE PROCESSING ===";
            $this->handle_product_images($product, $item, $product_logs);
            
            // Re-fetch product after image processing to verify
            $product = wc_get_product($product_id);
            $final_main_image_id = $product->get_image_id();
            $final_gallery_ids = $product->get_gallery_image_ids();
            $product_logs[] = "=== IMAGE PROCESSING COMPLETE ===";
            $product_logs[] = "Final image status - Main image ID: " . ($final_main_image_id ?: 'NONE') . ", Gallery images: " . count($final_gallery_ids);

            // --- HANDLE VARIATIONS (if variable product) ---
            if ($is_variable_from_ecwid && isset($item['combinations']) && !empty($item['combinations'])) {
                $this->handle_product_variations($product, $item, $product_logs);
            }

            return [
                'status' => 'imported',
                'item_name' => $product_name_for_log,
                'ecwid_id' => $ecwid_id_for_log,
                'sku' => $sku_for_log,
                'wc_product_id' => $product_id,
                'logs' => $product_logs
            ];

        } catch (Exception $e) {
            $product_logs[] = "[CRITICAL] Exception during product import: " . $e->getMessage();
            error_log("Ecwid Sync: Exception during product import for ID $ecwid_id_for_log: " . $e->getMessage()); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            
            return [
                'status' => 'failed',
                'item_name' => $product_name_for_log,
                'ecwid_id' => $ecwid_id_for_log,
                'sku' => $sku_for_log,
                'logs' => $product_logs
            ];
        }
    }

    /**
     * Handle product images during import
     */
    private function handle_product_images($product, $item, &$product_logs) {
        // Handle main product image
        if (isset($item['hdThumbnailUrl']) && !empty($item['hdThumbnailUrl'])) {
            $current_main_image_id = $product->get_image_id();
            $current_main_image_url = $current_main_image_id ? wp_get_attachment_url($current_main_image_id) : '';
            
            // Import if we have no main image OR if the image is different
            $should_import_main = !$current_main_image_id || !$this->is_same_image_url($current_main_image_url, $item['hdThumbnailUrl']);
            
            if ($should_import_main) {
                $product_logs[] = "Importing main product image from: " . $item['hdThumbnailUrl'];
                $attachment_id = $this->parent_plugin->attach_image_to_product_from_url($item['hdThumbnailUrl'], $product->get_id(), 'Main product image');
                if ($attachment_id && !is_wp_error($attachment_id)) {
                    $product->set_image_id($attachment_id);
                    $product->save();
                    $product_logs[] = "✓ Main product image imported successfully (ID: $attachment_id).";
                } else {
                    $error_msg = is_wp_error($attachment_id) ? $attachment_id->get_error_message() : 'Unknown error';
                    $product_logs[] = "✗ Failed to import main product image: " . $error_msg;
                }
            } else {
                $product_logs[] = "Main product image already exists and matches Ecwid image, skipping.";
            }
        } else {
            $product_logs[] = "No main product image URL provided from Ecwid.";
        }

        // Handle gallery images - Import all gallery images if none exist
        if (isset($item['galleryImages']) && is_array($item['galleryImages'])) {
            // Get existing gallery images
            $existing_gallery_ids = $product->get_gallery_image_ids();
            
            $product_logs[] = "Processing " . count($item['galleryImages']) . " gallery images from Ecwid. Product currently has " . count($existing_gallery_ids) . " gallery images.";
            
            // If product has no gallery images, force import all gallery images
            if (empty($existing_gallery_ids)) {
                $product_logs[] = "Product has no gallery images - forcing import of all gallery images from Ecwid.";
                $new_gallery_ids = [];
                $imported_count = 0;
                
                foreach ($item['galleryImages'] as $index => $gallery_image) {
                    // Use 'url' field as primary choice (confirmed from API testing), with fallbacks
                    $image_url = null;
                    if (isset($gallery_image['url']) && !empty($gallery_image['url'])) {
                        $image_url = $gallery_image['url'];
                    } elseif (isset($gallery_image['originalImageUrl']) && !empty($gallery_image['originalImageUrl'])) {
                        $image_url = $gallery_image['originalImageUrl'];
                    } elseif (isset($gallery_image['hdUrl']) && !empty($gallery_image['hdUrl'])) {
                        $image_url = $gallery_image['hdUrl'];
                    } elseif (isset($gallery_image['imageUrl']) && !empty($gallery_image['imageUrl'])) {
                        $image_url = $gallery_image['imageUrl'];
                    } else {
                        $product_logs[] = "✗ No valid image URL found in gallery image " . ($index + 1);
                        continue;
                    }
                    
                    $product_logs[] = "Importing gallery image " . ($index + 1) . ": " . $image_url;
                    $attachment_id = $this->parent_plugin->attach_image_to_product_from_url($image_url, $product->get_id(), 'Gallery image ' . ($index + 1));
                    if ($attachment_id && !is_wp_error($attachment_id)) {
                        $new_gallery_ids[] = $attachment_id;
                        $imported_count++;
                        $product_logs[] = "✓ Gallery image " . ($index + 1) . " imported successfully (ID: $attachment_id).";
                    } else {
                        $error_msg = is_wp_error($attachment_id) ? $attachment_id->get_error_message() : 'Unknown error';
                        $product_logs[] = "✗ Failed to import gallery image " . ($index + 1) . ": " . $error_msg;
                    }
                }
                
                if (!empty($new_gallery_ids)) {
                    $product->set_gallery_image_ids($new_gallery_ids);
                    $product->save();
                    $product_logs[] = "✓ SET " . count($new_gallery_ids) . " gallery images on product.";
                }
            } else {
                // Product has existing images - use smart preservation logic
                $existing_gallery_urls = [];
                
                // Get URLs of existing images for comparison
                foreach ($existing_gallery_ids as $existing_id) {
                    $existing_url = wp_get_attachment_url($existing_id);
                    if ($existing_url) {
                        $existing_gallery_urls[$existing_id] = $existing_url;
                    }
                }
                
                $new_gallery_ids = $existing_gallery_ids; // Start with existing images
                $imported_gallery_count = 0;
                
                foreach ($item['galleryImages'] as $index => $gallery_image) {
                    // Use 'url' field as primary choice (confirmed from API testing), with fallbacks
                    $image_url = null;
                    if (isset($gallery_image['url']) && !empty($gallery_image['url'])) {
                        $image_url = $gallery_image['url'];
                    } elseif (isset($gallery_image['originalImageUrl']) && !empty($gallery_image['originalImageUrl'])) {
                        $image_url = $gallery_image['originalImageUrl'];
                    } elseif (isset($gallery_image['hdUrl']) && !empty($gallery_image['hdUrl'])) {
                        $image_url = $gallery_image['hdUrl'];
                    } elseif (isset($gallery_image['imageUrl']) && !empty($gallery_image['imageUrl'])) {
                        $image_url = $gallery_image['imageUrl'];
                    } else {
                        $product_logs[] = "✗ No valid image URL found in gallery image " . ($index + 1);
                        continue;
                    }
                    
                    // Check if this image is already in the gallery
                    $already_exists = false;
                    foreach ($existing_gallery_urls as $existing_url) {
                        if ($this->is_same_image_url($existing_url, $image_url)) {
                            $already_exists = true;
                            break;
                        }
                    }
                    
                    if (!$already_exists) {
                        $product_logs[] = "Importing new gallery image " . ($index + 1) . ": " . $image_url;
                        $attachment_id = $this->parent_plugin->attach_image_to_product_from_url($image_url, $product->get_id(), 'Gallery image ' . ($index + 1));
                        if ($attachment_id && !is_wp_error($attachment_id)) {
                            $new_gallery_ids[] = $attachment_id;
                            $imported_gallery_count++;
                            $product_logs[] = "✓ New gallery image " . ($index + 1) . " imported successfully (ID: $attachment_id).";
                        } else {
                            $error_msg = is_wp_error($attachment_id) ? $attachment_id->get_error_message() : 'Unknown error';
                            $product_logs[] = "✗ Failed to import gallery image " . ($index + 1) . ": " . $error_msg;
                        }
                    } else {
                        $product_logs[] = "Gallery image " . ($index + 1) . " already exists, skipping: " . basename($image_url);
                    }
                }
                
                // Only update if we have changes
                if ($imported_gallery_count > 0) {
                    $product->set_gallery_image_ids($new_gallery_ids);
                    $product->save();
                    $product_logs[] = "✓ ADDED $imported_gallery_count new gallery images. Total gallery images: " . count($new_gallery_ids);
                } else {
                    $product_logs[] = "No new gallery images to import. Existing gallery preserved.";
                }
            }
        } else {
            $product_logs[] = "No gallery images provided from Ecwid.";
        }
    }
    
    /**
     * Compare two image URLs to see if they're the same image
     */
    private function is_same_image_url($url1, $url2) {
        if (empty($url1) || empty($url2)) {
            return false;
        }
        
        // Extract the filename from both URLs
        $filename1 = basename(wp_parse_url($url1, PHP_URL_PATH));
        $filename2 = basename(wp_parse_url($url2, PHP_URL_PATH));
        
        // Remove common WordPress image size suffixes (e.g., -150x150, -300x200, etc.)
        $filename1 = preg_replace('/-\d+x\d+(\.[a-zA-Z]{3,4})?$/', '$1', $filename1);
        $filename2 = preg_replace('/-\d+x\d+(\.[a-zA-Z]{3,4})?$/', '$1', $filename2);
        
        return $filename1 === $filename2;
    }

    /**
     * Handle product variations during import
     */
    private function handle_product_variations($product, $item, &$product_logs) {
        if (!isset($item['combinations']) || empty($item['combinations'])) {
            return;
        }

        $product_logs[] = "Processing " . count($item['combinations']) . " variations.";
        
        foreach ($item['combinations'] as $combination) {
            if (!isset($combination['id']) || !isset($combination['options'])) {
                $product_logs[] = "Skipping invalid combination data.";
                continue;
            }

            // Create or update variation
            $variation_id = $this->get_variation_by_ecwid_id($product->get_id(), $combination['id']);
            
            if ($variation_id) {
                $variation = wc_get_product($variation_id);
                $product_logs[] = "Updating existing variation ID: $variation_id";
            } else {
                $variation = new WC_Product_Variation();
                $variation->set_parent_id($product->get_id());
                $product_logs[] = "Creating new variation for combination ID: " . $combination['id'];
            }

            // Set variation data
            if (isset($combination['sku'])) {
                $variation->set_sku($combination['sku']);
            }
            
            if (isset($combination['price'])) {
                $variation->set_regular_price($combination['price']);
            }
            
            if (isset($combination['compareToPrice'])) {
                $variation->set_sale_price($combination['compareToPrice']);
            }
            
            if (isset($combination['quantity'])) {
                $variation->set_manage_stock(true);
                $variation->set_stock_quantity($combination['quantity']);
                $variation->set_stock_status($combination['quantity'] > 0 ? 'instock' : 'outofstock');
            }

            // Set variation attributes
            $variation_attributes = [];
            if (isset($combination['options']) && is_array($combination['options'])) {
                foreach ($combination['options'] as $option) {
                    if (isset($option['name']) && isset($option['value'])) {
                        $attr_name = $this->parent_plugin->sanitize_attribute_name($option['name']);
                        $variation_attributes[strtolower($attr_name)] = sanitize_text_field($option['value']);
                    }
                }
            }
            $variation->set_attributes($variation_attributes);

            // Save variation
            $variation_id = $variation->save();
            update_post_meta($variation_id, '_ecwid_combination_id', $combination['id']);
            
            $product_logs[] = "Variation saved with ID: $variation_id";
        }

        // Update parent product to refresh variation data
        $product->save();
    }

    /**
     * Get variation ID by Ecwid combination ID
     */
    private function get_variation_by_ecwid_id($parent_id, $ecwid_combination_id) {
        $variations = get_posts([
            'post_type' => 'product_variation',
            'post_parent' => $parent_id,
            'meta_key' => '_ecwid_combination_id',
            'meta_value' => $ecwid_combination_id,
            'numberposts' => 1,
            'fields' => 'ids'
        ]);

        return !empty($variations) ? $variations[0] : false;
    }

    /**
     * Validate SKU integrity for products and variations
     * Ensures SKUs are unique and properly formatted
     */
    private function validate_sku_integrity($item, $ecwid_id) {
        $result = [
            'is_valid' => true,
            'error_message' => '',
            'warnings' => []
        ];

        $main_sku = isset($item['sku']) ? trim($item['sku']) : '';
        $combinations = isset($item['combinations']) ? $item['combinations'] : [];

        // Check main product SKU
        if (empty($main_sku)) {
            $result['is_valid'] = false;
            $result['error_message'] = "Main product SKU is empty for Ecwid ID: $ecwid_id";
            return $result;
        }

        // Check for invalid characters in main SKU
        if (preg_match('/[<>"\']/', $main_sku)) {
            $result['warnings'][] = "Main SKU contains potentially problematic characters: $main_sku";
        }

        // Collect all SKUs for uniqueness check
        $all_skus = [$main_sku];
        
        if (!empty($combinations)) {
            foreach ($combinations as $index => $combination) {
                if (!isset($combination['sku']) || trim($combination['sku']) === '') {
                    $result['warnings'][] = "Variation #$index has empty SKU";
                    continue;
                }
                
                $variation_sku = trim($combination['sku']);
                
                // Check for invalid characters in variation SKU
                if (preg_match('/[<>"\']/', $variation_sku)) {
                    $result['warnings'][] = "Variation SKU contains potentially problematic characters: $variation_sku";
                }
                
                $all_skus[] = $variation_sku;
            }
        }

        // Check for duplicate SKUs within this product
        $sku_counts = array_count_values($all_skus);
        foreach ($sku_counts as $sku => $count) {
            if ($count > 1) {
                $result['is_valid'] = false;
                $result['error_message'] = "Duplicate SKU found within product: $sku (appears $count times)";
                return $result;
            }
        }

        // Check for SKU conflicts with existing WooCommerce products
        foreach ($all_skus as $sku) {
            $existing_product_id = wc_get_product_id_by_sku($sku);
            if ($existing_product_id) {
                // Check if this existing product belongs to the same Ecwid product
                $existing_ecwid_id = get_post_meta($existing_product_id, '_ecwid_product_id', true);
                if ($existing_ecwid_id !== $ecwid_id) {
                    $result['warnings'][] = "SKU '$sku' already exists in WooCommerce (Product ID: $existing_product_id, Ecwid ID: $existing_ecwid_id)";
                }
            }
        }

        return $result;
    }
    
    /**
     * Auto-create a category if it doesn't exist during product import
     */
    private function auto_create_category($ecwid_category_id, &$product_logs) {
        // First, try to fetch the category data from Ecwid
        $category_data = $this->fetch_ecwid_category($ecwid_category_id);
        
        if (!$category_data) {
            $product_logs[] = "Could not fetch category data from Ecwid for ID: $ecwid_category_id";
            return false;
        }
        
        $category_name = sanitize_text_field($category_data['name'] ?? "Category $ecwid_category_id");
        
        // Create the category in WooCommerce
        $term_result = wp_insert_term($category_name, 'product_cat', [
            'description' => $category_data['description'] ?? '',
            'slug' => sanitize_title($category_name . '-' . $ecwid_category_id)
        ]);
        
        if (is_wp_error($term_result)) {
            $product_logs[] = "Failed to create category '$category_name': " . $term_result->get_error_message();
            return false;
        }
        
        $term_id = $term_result['term_id'];
        
        // Store the Ecwid category ID mapping
        update_term_meta($term_id, '_ecwid_category_id', $ecwid_category_id);
        
        return [
            'term_id' => $term_id,
            'name' => $category_name
        ];
    }
    
    /**
     * Fetch category data from Ecwid API
     */
    private function fetch_ecwid_category($ecwid_category_id) {
        $store_id = $this->options['store_id'] ?? '';
        $api_token = $this->options['api_token'] ?? '';
        
        if (empty($store_id) || empty($api_token)) {
            return false;
        }
        
        $api_url = "https://app.ecwid.com/api/v3/{$store_id}/categories/{$ecwid_category_id}?token={$api_token}";
        
        $response = wp_remote_get($api_url, [
            'timeout' => 30,
            'headers' => [
                'Accept' => 'application/json',
            ]
        ]);
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        if ($response_code !== 200) {
            return false;
        }
        
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return false;
        }
        
        return $data ?: false;
    }
}
