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
        $limit = 50; // Process in smaller batches for all products sync

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
            wp_send_json_error(['message' => sprintf(__('API Request Error: %s', 'ecwid2woo'), $response->get_error_message())]);
            return;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        $http_code = wp_remote_retrieve_response_code($response);

        if ($http_code !== 200 || (isset($body['errorMessage']) && !empty($body['errorMessage']))) {
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
                $wc_category_ids = [];
                foreach ($item['categoryIds'] as $ecwid_category_id) {
                    $existing_wc_categories = get_terms([
                        'taxonomy' => 'product_cat',
                        'meta_key' => '_ecwid_category_id',
                        'meta_value' => $ecwid_category_id,
                        'hide_empty' => false,
                        'number' => 1
                    ]);
                    if (!empty($existing_wc_categories)) {
                        $wc_category_ids[] = $existing_wc_categories[0]->term_id;
                        $product_logs[] = "Assigned to category: " . $existing_wc_categories[0]->name . " (WC ID: " . $existing_wc_categories[0]->term_id . ")";
                    } else {
                        $product_logs[] = "Category with Ecwid ID $ecwid_category_id not found in WooCommerce. Skipping assignment.";
                    }
                }
                if (!empty($wc_category_ids)) {
                    $product->set_category_ids($wc_category_ids);
                } else {
                    $product_logs[] = "No valid WooCommerce categories found for assignment.";
                }
            } else {
                $product_logs[] = "No categories assigned in Ecwid.";
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
            
            $product_logs[] = "Product saved with WC ID: $product_id";

            // --- HANDLE IMAGES ---
            $this->handle_product_images($product, $item, $product_logs);

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
            $attachment_id = $this->parent_plugin->attach_image_to_product_from_url($item['hdThumbnailUrl'], $product->get_id(), true);
            if ($attachment_id) {
                $product_logs[] = "Main product image imported and set.";
            } else {
                $product_logs[] = "Failed to import main product image from: " . $item['hdThumbnailUrl'];
            }
        }

        // Handle gallery images
        if (isset($item['galleryImages']) && is_array($item['galleryImages'])) {
            $gallery_ids = [];
            $imported_gallery_count = 0;
            
            foreach ($item['galleryImages'] as $gallery_image) {
                if (isset($gallery_image['hdUrl']) && !empty($gallery_image['hdUrl'])) {
                    $attachment_id = $this->parent_plugin->attach_image_to_product_from_url($gallery_image['hdUrl'], $product->get_id(), false);
                    if ($attachment_id) {
                        $gallery_ids[] = $attachment_id;
                        $imported_gallery_count++;
                    }
                }
            }
            
            if (!empty($gallery_ids)) {
                $product->set_gallery_image_ids($gallery_ids);
                $product->save();
                $product_logs[] = "Imported $imported_gallery_count gallery images.";
            }
        }
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
}
