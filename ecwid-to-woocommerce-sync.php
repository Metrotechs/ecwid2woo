<?php
/*
Plugin Name: Ecwid to WooCommerce Sync
Description: Sync Ecwid store data (products, categories) to WooCommerce via Ecwid REST API.
Version: 1.7
Author: Metrotechs
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Ecwid_WC_Sync {
    private $options;
    private $sync_steps = ['categories', 'products']; // Define order of sync for full sync
    private $main_slug = 'ecwid-sync';
    private $selective_slug = 'ecwid-sync-selective';

    public function __construct() {
        $this->options = get_option('ecwid_wc_sync_options');
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'settings_init']);
        add_action('wp_ajax_ecwid_wc_batch_sync', [$this, 'ajax_batch_sync']);
        add_action('wp_ajax_ecwid_wc_fetch_products_for_selection', [$this, 'ajax_fetch_products_for_selection']);
        add_action('wp_ajax_ecwid_wc_import_selected_products', [$this, 'ajax_import_selected_products']);
    }

    public function add_admin_menu() {
        // Main Page: Settings & Full Sync
        add_menu_page(
            __('Ecwid Sync Settings & Full Sync', 'ecwid-wc-sync'),
            __('Ecwid Sync', 'ecwid-wc-sync'), // Main Menu Title
            'manage_options',
            $this->main_slug,
            [$this, 'options_page_router'], // Router function
            'dashicons-update'
        );

        // Submenu Page: Selective Import
        add_submenu_page(
            $this->main_slug, // Parent slug
            __('Selective Product Import', 'ecwid-wc-sync'),
            __('Selective Import', 'ecwid-wc-sync'), // Submenu Title
            'manage_options',
            $this->selective_slug,
            [$this, 'options_page_router'] // Router function
        );
    }

    public function settings_init() {
        register_setting('ecwidSync', 'ecwid_wc_sync_options');
        // Settings section will be displayed on the main page
        add_settings_section(
            'ecwidSync_section',
            __('API Credentials', 'ecwid-wc-sync'),
            '__return_false', // Callback for section description (none needed here)
            $this->main_slug // Page slug where this section should be shown
        );
        add_settings_field('store_id', __('Ecwid Store ID', 'ecwid-wc-sync'), [$this, 'field_text'], $this->main_slug, 'ecwidSync_section', ['id' => 'store_id']);
        add_settings_field('token', __('Ecwid API Token', 'ecwid-wc-sync'), [$this, 'field_text'], $this->main_slug, 'ecwidSync_section', ['id' => 'token']);
    }

    public function field_text($args) {
        $id = $args['id'];
        $value = isset($this->options[$id]) ? esc_attr($this->options[$id]) : '';
        echo "<input type='text' id='$id' name='ecwid_wc_sync_options[$id]' value='$value' style='width: 300px;' />";
    }

    public function options_page_router() {
        // Common script enqueueing for both pages
        wp_enqueue_script('ecwid-wc-sync-admin', plugin_dir_url(__FILE__) . 'admin-sync.js', ['jquery', 'wp-i18n'], '1.7', true);
        wp_localize_script('ecwid-wc-sync-admin', 'ecwid_sync_params', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('ecwid_wc_sync_nonce'),
            'sync_steps' => $this->sync_steps,
            'i18n' => [
                'sync_starting' => __('Sync starting...', 'ecwid-wc-sync'),
                'sync_complete' => __('Sync Complete!', 'ecwid-wc-sync'),
                'sync_error'    => __('Error during sync. Check console or log for details.', 'ecwid-wc-sync'),
                'syncing'       => __('Syncing', 'ecwid-wc-sync'),
                'start_sync'    => __('Start Full Sync', 'ecwid-wc-sync'),
                'syncing_button'=> __('Syncing...', 'ecwid-wc-sync'),
                'load_products' => __('Load Ecwid Products for Selection', 'ecwid-wc-sync'),
                'loading_products' => __('Loading Products...', 'ecwid-wc-sync'),
                'import_selected' => __('Import Selected Products', 'ecwid-wc-sync'),
                'importing_selected' => __('Importing Selected...', 'ecwid-wc-sync'),
                'no_products_selected' => __('No products selected for import.', 'ecwid-wc-sync'),
                'select_all_none' => __('Select All/None', 'ecwid-wc-sync'),
                'no_products_found' => __('No products found in Ecwid store.', 'ecwid-wc-sync')
            ]
        ]);

        // Determine which page to render
        $current_page = $_GET['page'] ?? $this->main_slug;

        echo '<div class="wrap">';
        if ($current_page === $this->main_slug) {
            $this->render_main_page();
        } elseif ($current_page === $this->selective_slug) {
            $this->render_selective_import_page();
        }
        echo '</div>'; // close .wrap
    }

    private function render_main_page() {
        ?>
        <h1><?php _e('Ecwid to WooCommerce Sync - Settings & Full Sync', 'ecwid-wc-sync'); ?></h1>
        
        <h2><?php _e('API Credentials', 'ecwid-wc-sync'); ?></h2>
        <form action='options.php' method='post'>
            <?php
            settings_fields('ecwidSync'); // Group name for settings
            do_settings_sections($this->main_slug); // Page slug for settings sections
            submit_button();
            ?>
        </form>

        <hr>
        <h2><?php _e('Full Data Sync', 'ecwid-wc-sync'); ?></h2>
        <p><?php _e('This will sync all categories and then all enabled products from Ecwid to WooCommerce.', 'ecwid-wc-sync'); ?></p>
        <div id="full-sync-status" style="margin-bottom: 10px; font-weight: bold;"></div>
        <div id="full-sync-progress" style="background: #f1f1f1; width: 100%; height: 24px; margin-bottom: 10px; border: 1px solid #ccc; box-sizing: border-box;">
            <div id="full-sync-bar" style="background: #007cba; width: 0%; height: 100%; text-align: center; color: #fff; line-height: 22px; font-size: 12px; transition: width 0.2s ease-in-out;">0%</div>
        </div>
        <button id="full-sync-button" class="button button-primary"><?php _e('Start Full Sync', 'ecwid-wc-sync'); ?></button>
        <div id="full-sync-log" style="margin-top: 15px; max-height: 300px; overflow-y: auto; border: 1px solid #eee; padding: 10px; background: #fafafa; font-size: 0.9em; line-height: 1.6; white-space: pre-wrap;"></div>
        <?php
    }

    private function render_selective_import_page() {
        ?>
        <h1><?php _e('Selective Product Import', 'ecwid-wc-sync'); ?></h1>
        <p><?php _e('Load enabled products from Ecwid and select which ones to import or update.', 'ecwid-wc-sync'); ?></p>
        <button id="load-ecwid-products-button" class="button"><?php _e('Load Ecwid Products for Selection', 'ecwid-wc-sync'); ?></button>
        <div id="selective-product-list-container" style="margin-top: 15px; max-height: 400px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: #fff;">
            <?php _e('Product list will appear here...', 'ecwid-wc-sync'); ?>
        </div>
        <button id="import-selected-products-button" class="button button-primary" style="margin-top: 10px; display: none;"><?php _e('Import Selected Products', 'ecwid-wc-sync'); ?></button>
        
        <div id="selective-sync-status" style="margin-top:15px; margin-bottom: 10px; font-weight: bold;"></div>
        <div id="selective-sync-progress" style="background: #f1f1f1; width: 100%; height: 24px; margin-bottom: 10px; border: 1px solid #ccc; box-sizing: border-box; display:none;">
            <div id="selective-sync-bar" style="background: #007cba; width: 0%; height: 100%; text-align: center; color: #fff; line-height: 22px; font-size: 12px; transition: width 0.2s ease-in-out;">0%</div>
        </div>
        <div id="selective-sync-log" style="margin-top: 15px; max-height: 300px; overflow-y: auto; border: 1px solid #eee; padding: 10px; background: #fafafa; font-size: 0.9em; line-height: 1.6; white-space: pre-wrap;"></div>
        <?php
    }

    private function _get_api_essentials() {
        $store_id = isset($this->options['store_id']) ? sanitize_text_field($this->options['store_id']) : '';
        $token    = isset($this->options['token']) ? sanitize_text_field($this->options['token']) : '';

        if (empty($store_id) || empty($token)) {
            return new WP_Error('missing_credentials', __('Ecwid Store ID and API Token must be configured in plugin settings.', 'ecwid-wc-sync'));
        }
        return ['store_id' => $store_id, 'token' => $token, 'base_url' => "https://app.ecwid.com/api/v3/{$store_id}"];
    }

    public function ajax_fetch_products_for_selection() {
        check_ajax_referer('ecwid_wc_sync_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized', 'ecwid-wc-sync')]);
            return;
        }
        set_time_limit(300);

        $api_essentials = $this->_get_api_essentials();
        if (is_wp_error($api_essentials)) {
            wp_send_json_error(['message' => $api_essentials->get_error_message()]);
            return;
        }

        $all_products = [];
        $offset = 0;
        $limit = 100; // Ecwid's max limit per request

        do {
            // MODIFIED: Added 'enabled' => 'true'
            $query_params = [
                'limit' => $limit,
                'offset' => $offset,
                'enabled' => 'true', // Only fetch enabled products
                'responseFields' => 'items(id,sku,name,enabled)' // Keep fields minimal
            ];
            $api_url = add_query_arg($query_params, $api_essentials['base_url'] . '/products');

            $response = wp_remote_get($api_url, [
                'timeout' => 60,
                'headers' => ['Authorization' => 'Bearer ' . $api_essentials['token'], 'Accept' => 'application/json'],
            ]);

            if (is_wp_error($response)) {
                wp_send_json_error(['message' => sprintf(__('API Request Error: %s', 'ecwid-wc-sync'), $response->get_error_message())]);
                return;
            }

            $body = json_decode(wp_remote_retrieve_body($response), true);
            $http_code = wp_remote_retrieve_response_code($response);

            if ($http_code !== 200 || (isset($body['errorMessage']) && !empty($body['errorMessage']))) {
                wp_send_json_error(['message' => sprintf(__('Ecwid API Error (HTTP %s): %s', 'ecwid-wc-sync'), $http_code, ($body['errorMessage'] ?? 'Unknown error'))]);
                return;
            }

            if (isset($body['items']) && is_array($body['items'])) {
                foreach ($body['items'] as $item) {
                     // We expect 'enabled' to be true here due to the API filter, but double-check for safety
                    if (isset($item['enabled']) && $item['enabled']) {
                        $all_products[] = [
                            'id' => $item['id'],
                            'name' => $item['name'] ?? 'N/A',
                            'sku' => $item['sku'] ?? 'N/A',
                            'enabled' => $item['enabled'] // Should be true
                        ];
                    }
                }
            }

            $count_in_response = $body['count'] ?? 0;
            $total_from_api = $body['total'] ?? 0; // This total will be for enabled products
            $offset += $count_in_response;

        } while ($count_in_response > 0 && $offset < $total_from_api);

        wp_send_json_success(['products' => $all_products, 'total_found' => count($all_products)]);
    }

    public function ajax_import_selected_products() {
        check_ajax_referer('ecwid_wc_sync_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized', 'ecwid-wc-sync')]);
            return;
        }
        set_time_limit(300);

        $api_essentials = $this->_get_api_essentials();
        if (is_wp_error($api_essentials)) {
            wp_send_json_error(['message' => $api_essentials->get_error_message()]);
            return;
        }

        $ecwid_product_id = isset($_POST['ecwid_product_id']) ? intval($_POST['ecwid_product_id']) : 0;

        if (empty($ecwid_product_id)) {
            wp_send_json_error(['message' => __('No Ecwid Product ID provided for import.', 'ecwid-wc-sync')]);
            return;
        }

        // Fetch full product data for this single product ID
        $query_params = ['responseFields' => 'id,sku,name,price,description,shortDescription,enabled,weight,quantity,unlimited,categoryIds,hdThumbnailUrl,imageUrl,galleryImages,options,combinations,productClassId,attributes,compareToPrice,dimensions,shipping'];
        $api_url = add_query_arg($query_params, $api_essentials['base_url'] . '/products/' . $ecwid_product_id);

        $response = wp_remote_get($api_url, [
            'timeout' => 60,
            'headers' => ['Authorization' => 'Bearer ' . $api_essentials['token'], 'Accept' => 'application/json'],
        ]);

        if (is_wp_error($response)) {
            wp_send_json_error(['message' => sprintf(__('API Request Error for product %s: %s', 'ecwid-wc-sync'), $ecwid_product_id, $response->get_error_message())]);
            return;
        }

        $item_data = json_decode(wp_remote_retrieve_body($response), true);
        $http_code = wp_remote_retrieve_response_code($response);

        if ($http_code !== 200 || (isset($item_data['errorMessage']) && !empty($item_data['errorMessage']))) {
            wp_send_json_error(['message' => sprintf(__('Ecwid API Error for product %s (HTTP %s): %s', 'ecwid-wc-sync'), $ecwid_product_id, $http_code, ($item_data['errorMessage'] ?? 'Unknown error'))]);
            return;
        }
        
        if (empty($item_data) || !isset($item_data['id'])) {
             wp_send_json_error(['message' => sprintf(__('Failed to fetch valid data for Ecwid product ID %s.', 'ecwid-wc-sync'), $ecwid_product_id)]);
            return;
        }

        $result_array = $this->import_product($item_data); // This is your existing detailed import function

        wp_send_json_success([
            'status' => $result_array['status'] ?? 'failed',
            'item_name' => $result_array['item_name'] ?? ($item_data['name'] ?? 'N/A'),
            'ecwid_id' => $result_array['ecwid_id'] ?? $ecwid_product_id,
            'sku' => $result_array['sku'] ?? ($item_data['sku'] ?? 'N/A'),
            'logs' => $result_array['logs'] ?? ['[ERROR] No logs returned from import_product.'],
        ]);
    }


    public function ajax_batch_sync() { // This is for FULL sync
        check_ajax_referer('ecwid_wc_sync_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized', 'ecwid-wc-sync')]); return;
        }
        set_time_limit(300);

        $api_essentials = $this->_get_api_essentials();
        if (is_wp_error($api_essentials)) {
            wp_send_json_error(['message' => $api_essentials->get_error_message()]); return;
        }

        $limit = apply_filters('ecwid_wc_sync_batch_limit', 1); // Still 1 for debugging
        $sync_type = isset($_POST['sync_type']) ? sanitize_text_field($_POST['sync_type']) : '';
        $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;

        error_log("Ecwid Sync: FULL BATCH - Type: $sync_type, Offset: $offset, Limit: $limit");

        $endpoints = ['products' => '/products', 'categories' => '/categories'];
        if (!isset($endpoints[$sync_type])) {
            wp_send_json_error(['message' => __('Invalid sync type for full sync.', 'ecwid-wc-sync')]); return;
        }

        $endpoint = $endpoints[$sync_type];
        $api_url_base = $api_essentials['base_url'] . $endpoint;
        $query_params_for_url = ['limit' => $limit, 'offset' => $offset];

        if ($sync_type === 'products') {
            $query_params_for_url['enabled'] = 'true';
            $query_params_for_url['responseFields'] = 'items(id,sku,name,price,description,shortDescription,enabled,weight,quantity,unlimited,categoryIds,hdThumbnailUrl,imageUrl,galleryImages,options,combinations,productClassId,attributes,compareToPrice,dimensions,shipping)';
        } elseif ($sync_type === 'categories') {
            $query_params_for_url['responseFields'] = 'items(id,name,parentId,description,hdThumbnailUrl,originalImageUrl)';
        }

        $api_url = add_query_arg($query_params_for_url, $api_url_base);
        $response = wp_remote_get($api_url, [
            'timeout' => 60,
            'headers' => ['Authorization' => 'Bearer ' . $api_essentials['token'], 'Accept' => 'application/json'],
        ]);

        if (is_wp_error($response)) {
            error_log("Ecwid Sync: API Request WP_Error for $sync_type: " . $response->get_error_message());
            wp_send_json_error(['message' => sprintf(__('API Request Error: %s', 'ecwid-wc-sync'), $response->get_error_message())]); return;
        }
        
        $raw_response_body = wp_remote_retrieve_body($response);
        $body = json_decode($raw_response_body, true);
        $http_code = wp_remote_retrieve_response_code($response);

        if ($http_code !== 200 || !is_array($body) || (isset($body['errorMessage']) && !empty($body['errorMessage']))) {
            $error_message = sprintf(__('Ecwid API Error (HTTP %s): %s', 'ecwid-wc-sync'), $http_code, ($body['errorMessage'] ?? 'Unknown error or invalid response format'));
            error_log("Ecwid Sync: API Error for $sync_type. HTTP Code: $http_code. Raw Body: " . $raw_response_body);
            wp_send_json_error(['message' => $error_message, 'details' => is_array($body) ? $body : ['raw_response' => $raw_response_body]]); return;
        }
        
        $items = [];
        if (isset($body['items']) && is_array($body['items'])) {
            $items = $body['items'];
        } elseif ($sync_type === 'categories' && !isset($body['total']) && !isset($body['count'])) {
            // Handle cases where Ecwid categories API might return a simple array for root/small sets
            // Ensure $body itself is the array of items
            if(is_array($body) && (empty($body) || isset($body[0]['id']))) { // Basic check if it looks like an array of category items
                $items = $body;
            } else {
                error_log("Ecwid Sync: Categories API response for $sync_type was not in expected 'items' wrapper and not a direct array of categories. Raw Body: " . $raw_response_body);
            }
        }


        $total_items = $body['total'] ?? count($items); // If 'total' isn't set, estimate from current items (especially for the direct category array case)
        $count_in_response = $body['count'] ?? count($items); // If 'count' isn't set, use actual count of items retrieved

        $imported_count = 0; $skipped_count = 0; $failed_count = 0;
        $batch_detailed_logs = [];

        if (!empty($items) && is_array($items)) { // Ensure $items is an array and not empty
            foreach ($items as $item_data) {
                if (!is_array($item_data)) { // Safeguard against non-array items
                    $batch_detailed_logs[] = "--- [CRITICAL ERROR] Encountered non-array item in API response for $sync_type. Skipping. Item data: " . print_r($item_data, true) . " ---";
                    $failed_count++;
                    continue;
                }

                $result_array = null;
                $item_identifier_for_log = '';
                try {
                    switch ($sync_type) {
                        case 'products':
                            $item_identifier_for_log = "Product (Ecwid ID: " . ($item_data['id'] ?? 'N/A') . ")";
                            $result_array = $this->import_product($item_data);
                            break;
                        case 'categories':
                            $item_identifier_for_log = "Category (Ecwid ID: " . ($item_data['id'] ?? 'N/A') . ")";
                            $result_array = $this->import_category($item_data);
                            break;
                    }

                    if ($result_array && isset($result_array['status'])) {
                        if ($result_array['status'] === 'imported') $imported_count++;
                        elseif ($result_array['status'] === 'skipped') $skipped_count++;
                        else $failed_count++;
                        $batch_detailed_logs[] = "--- Processing: " . esc_html($result_array['item_name'] ?? $item_identifier_for_log) . " (Ecwid ID: " . esc_html($result_array['ecwid_id'] ?? 'N/A') . ($result_array['sku'] ?? '' ? ", SKU: " . esc_html($result_array['sku']) : "") . ") ---";
                        if (!empty($result_array['logs']) && is_array($result_array['logs'])) {
                            foreach($result_array['logs'] as $log_line) { $batch_detailed_logs[] = "  " . esc_html($log_line); }
                        }
                        $batch_detailed_logs[] = "--- Result for " . esc_html($result_array['ecwid_id'] ?? 'N/A') .": " . strtoupper($result_array['status']) . " ---";
                    } else {
                        $failed_count++;
                        $batch_detailed_logs[] = "--- [CRITICAL ERROR] Failed to process item: " . esc_html($item_identifier_for_log) . ". Import function did not return expected result or status. Result: " . print_r($result_array, true) . " ---";
                    }
                } catch (Exception $e) {
                    $failed_count++;
                    $batch_detailed_logs[] = "--- [PHP EXCEPTION] During processing of " . esc_html($item_identifier_for_log) . ": " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine() . " ---";
                    error_log("Ecwid Sync: PHP Exception during $sync_type import: " . $e->getMessage() . " Trace: " . $e->getTraceAsString());
                }
                $batch_detailed_logs[] = " "; 
            }
        } elseif (empty($items) && $offset === 0 && $limit > 0) {
             $batch_detailed_logs[] = "No items received from Ecwid API for $sync_type with offset $offset and limit $limit. This might be normal if there are no items of this type or all have been processed.";
        }


        $new_offset = $offset + $count_in_response;
        // Determine 'has_more' more carefully
        $has_more = false;
        if ($count_in_response > 0) { // Only if we actually got items in this batch
            if (isset($body['total']) && isset($body['offset']) && isset($body['count'])) { // Standard Ecwid pagination
                 $has_more = ($body['total'] > ($body['offset'] + $body['count']));
            } elseif ($sync_type === 'categories' && !isset($body['total']) && !isset($body['count']) && $offset === 0 && !empty($items) && $count_in_response == $limit) {
                // For the direct array category case, if we got a full batch, assume there might be more.
                // This is a heuristic and might need adjustment if Ecwid API behaves differently.
                // A safer bet if 'total' is missing is to assume no more if count_in_response < limit.
                $has_more = ($count_in_response === $limit);
            } elseif ($count_in_response === $limit) {
                // General fallback: if we received a full batch, assume there might be more.
                $has_more = true;
            }
        }
        // If total_items is definitively known and new_offset has reached or exceeded it, then no more.
        if (isset($body['total']) && $new_offset >= $body['total']) {
            $has_more = false;
        }


        wp_send_json_success([
            'message' => sprintf(__('%1$s: Processed %2$d items in this batch (Imported: %3$d, Skipped: %4$d, Failed: %5$d). Total items for this type (estimated/reported): %6$d.', 'ecwid-wc-sync'), ucfirst($sync_type), $count_in_response, $imported_count, $skipped_count, $failed_count, $total_items),
            'next_offset' => $new_offset, 'total_items' => $total_items, 'has_more' => $has_more,    
            'processed_type' => $sync_type, 'batch_logs' => $batch_detailed_logs
        ]);
    }
    
    // --- IMPORT CATEGORY ---
    private function import_category($item) {
        $category_logs = [];
        $ecwid_cat_id = $item['id'] ?? null;
        $ecwid_cat_name = $item['name'] ?? null;
        $item_name_for_return = $ecwid_cat_name ?? 'N/A';
        $ecwid_id_for_return = $ecwid_cat_id ?? 'N/A';

        try { // Added try-catch block
            if (!$ecwid_cat_id || !$ecwid_cat_name) {
                $category_logs[] = "[CRITICAL] Missing ID or Name.";
                return ['status' => 'failed', 'logs' => $category_logs, 'item_name' => $item_name_for_return, 'ecwid_id' => $ecwid_id_for_return];
            }
            $category_logs[] = "Starting import for Category: \"$ecwid_cat_name\" (Ecwid ID: $ecwid_cat_id)";

            $existing_terms_by_ecwid_id = get_terms(['taxonomy' => 'product_cat', 'meta_key' => '_ecwid_category_id', 'meta_value' => $ecwid_cat_id, 'hide_empty' => false, 'fields' => 'ids', 'number' => 1]);

            if (!empty($existing_terms_by_ecwid_id) && !is_wp_error($existing_terms_by_ecwid_id)) {
                $wc_term_id = $existing_terms_by_ecwid_id[0];
                $category_logs[] = "Skipped. Already exists in WC with this Ecwid ID (WC Term ID: $wc_term_id).";
                return ['status' => 'skipped', 'logs' => $category_logs, 'item_name' => $item_name_for_return, 'ecwid_id' => $ecwid_id_for_return];
            }
            
            $term_by_name_result = term_exists($ecwid_cat_name, 'product_cat');
            if ($term_by_name_result !== 0 && $term_by_name_result !== null) {
                $wc_term_id = is_array($term_by_name_result) ? $term_by_name_result['term_id'] : $term_by_name_result;
                update_term_meta($wc_term_id, '_ecwid_category_id', $ecwid_cat_id);
                $category_logs[] = "Skipped. Existing WC term (ID: $wc_term_id) found by name and linked with Ecwid ID.";
                return ['status' => 'skipped', 'logs' => $category_logs, 'item_name' => $item_name_for_return, 'ecwid_id' => $ecwid_id_for_return];
            }
            
            $args = [];
            if (isset($item['description'])) $args['description'] = wp_kses_post($item['description']);
            if (isset($item['parentId']) && $item['parentId'] > 0) {
                $parent_wc_terms = get_terms(['taxonomy' => 'product_cat', 'meta_key' => '_ecwid_category_id', 'meta_value' => $item['parentId'], 'hide_empty' => false, 'fields' => 'ids', 'number' => 1]);
                if (!empty($parent_wc_terms) && !is_wp_error($parent_wc_terms)) $args['parent'] = $parent_wc_terms[0];
            }

            $new_term_result = wp_insert_term(wp_slash($ecwid_cat_name), 'product_cat', $args);

            if (is_wp_error($new_term_result)) {
                $category_logs[] = '[ERROR] Failed to insert new WC category: ' . $new_term_result->get_error_message();
                return ['status' => 'failed', 'logs' => $category_logs, 'item_name' => $item_name_for_return, 'ecwid_id' => $ecwid_id_for_return];
            }

            if (isset($new_term_result['term_id'])) {
                update_term_meta($new_term_result['term_id'], '_ecwid_category_id', $ecwid_cat_id);
                $category_logs[] = "Imported successfully (New WC Term ID: {$new_term_result['term_id']}).";
                return ['status' => 'imported', 'logs' => $category_logs, 'item_name' => $item_name_for_return, 'ecwid_id' => $ecwid_id_for_return];
            }
            
            $category_logs[] = "[ERROR] wp_insert_term did not return term_id.";
            return ['status' => 'failed', 'logs' => $category_logs, 'item_name' => $item_name_for_return, 'ecwid_id' => $ecwid_id_for_return];
        
        } catch (Exception $e) { // Catch any unexpected exceptions
            $category_logs[] = "[PHP EXCEPTION] During category import: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine();
            error_log("Ecwid Sync: PHP Exception during category import for Ecwid ID $ecwid_cat_id: " . $e->getMessage() . " Trace: " . $e->getTraceAsString());
            return ['status' => 'failed', 'logs' => $category_logs, 'item_name' => $item_name_for_return, 'ecwid_id' => $ecwid_id_for_return];
        }
    }

    // --- IMPORT PRODUCT (largely unchanged, but ensure it returns all necessary fields for logging) ---
    private function import_product($item) {
        $product_logs = [];
        $product_name_for_log = isset($item['name']) ? sanitize_text_field($item['name']) : '[No Name]';
        $ecwid_id_for_log = $item['id'] ?? 'N/A';
        $sku_for_log = $item['sku'] ?? 'N/A';

        if (!class_exists('WC_Product_Factory')) {
            $product_logs[] = "[CRITICAL] WC_Product_Factory class not found.";
            return ['status' => 'failed', 'logs' => $product_logs, 'item_name' => $product_name_for_log, 'ecwid_id' => $ecwid_id_for_log, 'sku' => $sku_for_log];
        }
        if (!isset($item['sku']) || !isset($item['id'])) {
            $product_logs[] = "[CRITICAL] Product missing SKU or ID. Ecwid ID: $ecwid_id_for_log, SKU: $sku_for_log";
            error_log("Ecwid Sync: Product (Ecwid ID: $ecwid_id_for_log) missing SKU or ID. Data: " . print_r($item, true));
            return ['status' => 'failed', 'logs' => $product_logs, 'item_name' => $product_name_for_log, 'ecwid_id' => $ecwid_id_for_log, 'sku' => $sku_for_log];
        }

        $log_product_identifier = "PRODUCT (Ecwid ID: {$item['id']}, SKU: {$item['sku']}, Name: \"" . esc_html($product_name_for_log) . "\")";
        $product_logs[] = "Starting import for $log_product_identifier";
        // $product_logs[] = "Raw Ecwid Item Data: " . wp_json_encode($item); // Uncomment for very verbose debugging of incoming data

        $product_id_by_ecwid_id = null;
        $existing_products_by_ecwid_id = get_posts(['post_type' => 'product', 'meta_key' => '_ecwid_product_id', 'meta_value' => $item['id'], 'post_status' => 'any', 'numberposts' => 1, 'fields' => 'ids']);
        if (!empty($existing_products_by_ecwid_id)) $product_id_by_ecwid_id = $existing_products_by_ecwid_id[0];

        $product_id_by_sku = wc_get_product_id_by_sku($item['sku']);
        $product_id = $product_id_by_ecwid_id ?: $product_id_by_sku;

        if ($product_id && !$product_id_by_ecwid_id && $product_id_by_sku) {
            update_post_meta($product_id, '_ecwid_product_id', $item['id']);
            $product_logs[] = "Updated Ecwid ID meta for WC Product ID $product_id based on SKU match.";
        }

        $is_variable_from_ecwid = isset($item['combinations']) && !empty($item['combinations']);
        $product_logs[] = $is_variable_from_ecwid ? "Ecwid product has combinations, treating as Variable." : "Ecwid product has no combinations, treating as Simple/Other.";
        $product = null;

        if ($product_id) {
            $product_logs[] = "Existing WC Product ID found: $product_id (by Ecwid ID: " . ($product_id_by_ecwid_id ? 'Yes' : 'No') . ", by SKU: " . ($product_id_by_sku ? 'Yes' : 'No') . ")";
            $product = wc_get_product($product_id);
            if ($product) {
                $current_wc_type = $product->get_type();
                $product_logs[] = "Existing WC Product type: $current_wc_type.";
                if ($is_variable_from_ecwid && $current_wc_type !== 'variable') {
                    $product_logs[] = "Changing product type to 'variable'.";
                    wp_set_object_terms($product_id, 'variable', 'product_type'); clean_product_caches($product_id); $product = wc_get_product($product_id);
                } elseif (!$is_variable_from_ecwid && $current_wc_type === 'variable') {
                    $product_logs[] = "Changing product type from 'variable' to 'simple'. Deleting existing variations.";
                    $temp_var_product = wc_get_product($product_id); // Re-fetch to be sure
                    if ($temp_var_product && $temp_var_product->is_type('variable')) {
                        foreach ($temp_var_product->get_children() as $child_id) {
                            $child = wc_get_product($child_id); if ($child) { $child->delete(true); $product_logs[] = "Deleted variation ID $child_id."; }
                        }
                    }
                    wp_set_object_terms($product_id, 'simple', 'product_type'); clean_product_caches($product_id); $product = wc_get_product($product_id);
                }
            } else {
                $product_logs[] = "[WARNING] Could not load existing WC Product ID $product_id. Treating as new.";
                $product_id = 0; // Failed to load, treat as new
            }
        } else {
            $product_logs[] = "No existing WC Product found. Creating new.";
        }

        if (!$product) {
            $product_class = $is_variable_from_ecwid ? 'WC_Product_Variable' : 'WC_Product_Simple';
            $product_logs[] = "Instantiating new $product_class.";
            $product = new $product_class();
            if ($product_id) $product->set_id($product_id); // Should not happen if $product is null, but for safety
        }

        if (!$product) {
            $product_logs[] = "[CRITICAL] Could not get or create WC_Product object.";
            return ['status' => 'failed', 'logs' => $product_logs, 'item_name' => $product_name_for_log, 'ecwid_id' => $ecwid_id_for_log, 'sku' => $sku_for_log];
        }

        try {
            $product->set_name(sanitize_text_field($item['name'] ?? ''));
            $product->set_sku(sanitize_text_field($item['sku']));
            $product->set_description(wp_kses_post($item['description'] ?? ''));
            $product->set_short_description(wp_kses_post($item['shortDescription'] ?? ''));
            $product->set_status(isset($item['enabled']) && $item['enabled'] ? 'publish' : 'draft');
            if (isset($item['weight'])) $product->set_weight(wc_format_decimal($item['weight']));
            if (isset($item['dimensions']) && is_array($item['dimensions'])) {
                if (isset($item['dimensions']['length'])) $product->set_length(wc_format_decimal($item['dimensions']['length']));
                if (isset($item['dimensions']['width'])) $product->set_width(wc_format_decimal($item['dimensions']['width']));
                if (isset($item['dimensions']['height'])) $product->set_height(wc_format_decimal($item['dimensions']['height']));
            }

            if (!$product->is_type('variable')) {
                $product_logs[] = "Setting details for Simple product.";
                $product->set_regular_price(strval($item['price'] ?? '0'));
                if (isset($item['compareToPrice'])) $product->set_sale_price(strval($item['compareToPrice'])); else $product->set_sale_price('');
                if (isset($item['quantity'])) {
                    $product->set_manage_stock(true); $product->set_stock_quantity(intval($item['quantity']));
                    $product->set_stock_status(intval($item['quantity']) > 0 ? 'instock' : 'outofstock');
                } elseif (isset($item['unlimited']) && $item['unlimited']) {
                    $product->set_manage_stock(false); $product->set_stock_quantity(null); $product->set_stock_status('instock');
                } else {
                    $product->set_manage_stock(false); $product->set_stock_quantity(null); $product->set_stock_status('outofstock'); // Default to out of stock if no info
                }
            } else {
                 $product_logs[] = "Setting details for Variable product (parent). Price will be synced from variations if possible, or use base price.";
                 $product->set_manage_stock(false); // Stock managed at variation level
                 if (isset($item['price'])) $product->set_regular_price(strval($item['price'])); // Base price for variable product
            }

            // CATEGORY ASSIGNMENT
            if (isset($item['categoryIds']) && is_array($item['categoryIds']) && !empty($item['categoryIds'])) {
                $product_logs[] = "Ecwid Category IDs found: " . implode(', ', $item['categoryIds']);
                $term_ids = [];
                foreach ($item['categoryIds'] as $ecwid_cat_id) {
                    if (empty($ecwid_cat_id) || $ecwid_cat_id == 0) continue;
                    $wc_term_id = $this->get_term_id_by_ecwid_id($ecwid_cat_id, 'product_cat');
                    if ($wc_term_id) {
                        $term_ids[] = $wc_term_id;
                        $product_logs[] = "Mapped Ecwid Cat ID $ecwid_cat_id to WC Term ID $wc_term_id.";
                    } else {
                        $product_logs[] = "[WARNING] Could not find WC Term ID for Ecwid Cat ID $ecwid_cat_id.";
                    }
                }
                if (!empty($term_ids)) {
                    $unique_term_ids = array_unique(array_map('intval', $term_ids));
                    $product->set_category_ids($unique_term_ids);
                    $product_logs[] = "Assigned WC Category IDs: " . implode(', ', $unique_term_ids);
                } else {
                    $product_logs[] = "No WC Category IDs could be mapped or assigned.";
                }
            } else {
                $product_logs[] = "No Ecwid Category IDs provided for this product.";
            }

            $featured_image_url = $item['hdThumbnailUrl'] ?? $item['imageUrl'] ?? null;
            $current_product_id_for_image = $product->get_id() ?: 0; // Use 0 if new product not yet saved
            if ($featured_image_url) {
                $existing_featured_image_id = $product_id ? $product->get_image_id('edit') : null; // Only check if product exists
                $featured_already_imported = $existing_featured_image_id && (get_post_meta($existing_featured_image_id, '_ecwid_image_source_url', true) === $featured_image_url);

                if (!$featured_already_imported) {
                    $product_logs[] = "Attempting to attach featured image: $featured_image_url";
                    // Note: $current_product_id_for_image might be 0 here if it's a new product.
                    // Image will be attached to post 0, then re-assigned after product save if needed.
                    $image_id = $this->attach_image_to_product_from_url($featured_image_url, 0, ($item['name'] ?? 'Product') . ' featured image');
                    if ($image_id && !is_wp_error($image_id)) {
                        $product->set_image_id($image_id); // Temporarily set, will be finalized after save
                        update_post_meta($image_id, '_ecwid_image_source_url', esc_url_raw($featured_image_url));
                        $product_logs[] = "Featured image attached, WC Attachment ID: $image_id.";
                    } else {
                         $product_logs[] = "[WARNING] Failed to attach featured image. Error: " . (is_wp_error($image_id) ? $image_id->get_error_message() : 'Unknown error');
                    }
                } else {
                    $product_logs[] = "Featured image already imported and matches URL.";
                }
            }

            // ATTRIBUTES (for Variable Products)
            if ($product->is_type('variable') && isset($item['options']) && is_array($item['options'])) {
                $product_logs[] = "Processing Ecwid options for WC attributes. Ecwid Options: " . wp_json_encode($item['options']);
                $wc_attributes_for_product = [];
                $position = 0;
                foreach ($item['options'] as $ecwid_option) {
                    if (empty($ecwid_option['name']) || !isset($ecwid_option['choices']) || !is_array($ecwid_option['choices'])) {
                        $product_logs[] = "[WARNING] Skipping invalid Ecwid option: " . wp_json_encode($ecwid_option);
                        continue;
                    }
                    $attribute_name = sanitize_text_field($ecwid_option['name']);
                    $product_logs[] = "Processing Ecwid Option/Attribute: '$attribute_name'";
                    $taxonomy_name = wc_attribute_taxonomy_name($attribute_name); // Generates pa_... slug
                    $attribute_id = wc_attribute_taxonomy_id_by_name($attribute_name);

                    if (!$attribute_id) {
                        $product_logs[] = "WC Attribute '$attribute_name' (taxonomy '$taxonomy_name') not found. Creating...";
                        $attribute_id = wc_create_attribute([
                            'name'         => $attribute_name, // Human-readable name
                            'slug'         => sanitize_title($attribute_name), // Attribute slug
                            'type'         => 'select',
                            'order_by'     => 'menu_order',
                            'has_archives' => false
                        ]);
                        if (is_wp_error($attribute_id)) {
                            $product_logs[] = "[ERROR] Failed to create WC Attribute '$attribute_name': " . $attribute_id->get_error_message();
                            continue;
                        }
                        $product_logs[] = "WC Attribute '$attribute_name' created with ID: $attribute_id.";
                    } else {
                        $product_logs[] = "Found existing WC Attribute '$attribute_name' (Taxonomy: '$taxonomy_name', ID: $attribute_id).";
                    }

                    $term_ids_for_attribute = [];
                    foreach ($ecwid_option['choices'] as $choice) {
                        $term_name = sanitize_text_field($choice['text']);
                        $term_slug = sanitize_title($term_name);
                        $existing_term = get_term_by('slug', $term_slug, $taxonomy_name);

                        if ($existing_term && !is_wp_error($existing_term)) {
                            $term_ids_for_attribute[] = $existing_term->term_id;
                            $product_logs[] = "Found existing term '$term_name' (slug: '$term_slug') in '$taxonomy_name' with ID: {$existing_term->term_id}.";
                        } else {
                            $product_logs[] = "Term '$term_name' (slug: '$term_slug') not found in '$taxonomy_name'. Creating...";
                            $term_result = wp_insert_term($term_name, $taxonomy_name, ['slug' => $term_slug]);
                            if (is_wp_error($term_result)) {
                                $product_logs[] = "[ERROR] Failed to insert term '$term_name' into '$taxonomy_name': " . $term_result->get_error_message();
                            } else {
                                $term_ids_for_attribute[] = $term_result['term_id'];
                                $product_logs[] = "Term '$term_name' inserted into '$taxonomy_name' with ID: {$term_result['term_id']}.";
                            }
                        }
                    }

                    if (!empty($term_ids_for_attribute)) {
                        $wc_attribute_obj = new WC_Product_Attribute();
                        $wc_attribute_obj->set_id($attribute_id); // Global attribute ID
                        $wc_attribute_obj->set_name($taxonomy_name); // Taxonomy name (pa_...)
                        $wc_attribute_obj->set_options($term_ids_for_attribute); // Array of term IDs
                        $wc_attribute_obj->set_position($position++);
                        $wc_attribute_obj->set_visible(true);
                        $wc_attribute_obj->set_variation(true); // Crucial for variations
                        $wc_attributes_for_product[] = $wc_attribute_obj;
                        $product_logs[] = "Prepared WC_Product_Attribute for '$taxonomy_name' with term IDs: " . implode(', ', $term_ids_for_attribute);
                    } else {
                        $product_logs[] = "[WARNING] No terms could be set for attribute '$attribute_name'.";
                    }
                }
                if (!empty($wc_attributes_for_product)) {
                    $product->set_attributes($wc_attributes_for_product);
                    $product_logs[] = "Parent product attributes set.";
                } else {
                     $product_logs[] = "No attributes were set on the parent product.";
                }
            } elseif ($product->is_type('variable')) {
                $product_logs[] = "[WARNING] Product is variable but no Ecwid 'options' found to create attributes.";
            }


            $product_saved_id = $product->save();

            if (!$product_saved_id || is_wp_error($product_saved_id)) {
                 $error_msg = is_wp_error($product_saved_id) ? $product_saved_id->get_error_message() : "Unknown error";
                 $product_logs[] = "[CRITICAL] FAILED to save product (before variations/gallery). Error: $error_msg";
                 return ['status' => 'failed', 'logs' => $product_logs, 'item_name' => $product_name_for_log, 'ecwid_id' => $ecwid_id_for_log, 'sku' => $sku_for_log];
            }
            $product_logs[] = "Product core data saved successfully. WC Product ID: $product_saved_id.";
            update_post_meta($product_saved_id, '_ecwid_product_id', $item['id']);
            update_post_meta($product_saved_id, '_ecwid_product_sku_ref', $item['sku']);
            update_post_meta($product_saved_id, '_ecwid_last_sync_time', current_time('mysql'));

            // If it was a new product and image was attached to post 0, re-assign it.
            if ($current_product_id_for_image === 0 && $product->get_image_id()) {
                $temp_image_id = $product->get_image_id();
                wp_update_post(['ID' => $temp_image_id, 'post_parent' => $product_saved_id]);
                $product_logs[] = "Re-assigned featured image $temp_image_id to product $product_saved_id.";
            }


            // VARIATIONS
            if ($product->is_type('variable') && isset($item['combinations']) && is_array($item['combinations'])) {
                $product_logs[] = "Processing Ecwid combinations for WC variations. Ecwid Combinations: " . wp_json_encode($item['combinations']);
                $parent_product = wc_get_product($product_saved_id); // Re-fetch parent product
                if ($parent_product && $parent_product->is_type('variable')) {
                    $ecwid_combo_ids_in_payload = array_map(function($combo) { return $combo['id'] ?? null; }, $item['combinations']);
                    $ecwid_combo_ids_in_payload = array_filter($ecwid_combo_ids_in_payload);

                    // Delete existing WC variations not present in current Ecwid combinations
                    foreach ($parent_product->get_children() as $existing_wc_variation_id) {
                        $ecwid_combo_id_meta = get_post_meta($existing_wc_variation_id, '_ecwid_variation_id', true);
                        if ($ecwid_combo_id_meta && !in_array($ecwid_combo_id_meta, $ecwid_combo_ids_in_payload)) {
                            $variation_to_delete = wc_get_product($existing_wc_variation_id);
                            if ($variation_to_delete) {
                                $variation_to_delete->delete(true);
                                $product_logs[] = "Deleted stale WC Variation ID $existing_wc_variation_id (Ecwid Combo ID: $ecwid_combo_id_meta).";
                            }
                        }
                    }

                    foreach ($item['combinations'] as $combo_idx => $combo) {
                        if (!isset($combo['id'])) {
                            $product_logs[] = "[WARNING] Skipping Ecwid combination at index $combo_idx: missing 'id'. Data: " . wp_json_encode($combo);
                            continue;
                        }
                        $ecwid_combination_id = $combo['id'];
                        $product_logs[] = "Processing Ecwid Combination ID: $ecwid_combination_id. Data: " . wp_json_encode($combo);

                        $variation_attributes_for_wc = [];
                        if (isset($combo['options']) && is_array($combo['options'])) {
                            foreach ($combo['options'] as $combo_opt_val) {
                                if (empty($combo_opt_val['name']) || !isset($combo_opt_val['value'])) {
                                     $product_logs[] = "[WARNING] Skipping invalid option in combination $ecwid_combination_id: " . wp_json_encode($combo_opt_val);
                                     continue;
                                }
                                $parent_option_name = sanitize_text_field($combo_opt_val['name']); // e.g., "Color"
                                $wc_attr_taxonomy_slug = wc_attribute_taxonomy_name($parent_option_name); // e.g., "pa_color"
                                $term_value_for_wc = sanitize_text_field($combo_opt_val['value']); // e.g., "Red"

                                $term_object = get_term_by('name', $term_value_for_wc, $wc_attr_taxonomy_slug);
                                if ($term_object && !is_wp_error($term_object)) {
                                    $variation_attributes_for_wc[$wc_attr_taxonomy_slug] = $term_object->slug; // Use slug for variation attribute
                                    $product_logs[] = "For combo $ecwid_combination_id, attribute '$wc_attr_taxonomy_slug' mapped to term slug '{$term_object->slug}'.";
                                } else {
                                    // This case is problematic. The term should have been created when processing parent attributes.
                                    // If it's not found, the variation might not link correctly.
                                    $product_logs[] = "[ERROR] For combo $ecwid_combination_id, term '$term_value_for_wc' for attribute '$wc_attr_taxonomy_slug' not found. This variation may not work correctly.";
                                    // Fallback to raw value, though this is unlikely to work for taxonomy attributes
                                    // $variation_attributes_for_wc[$wc_attr_taxonomy_slug] = sanitize_title($term_value_for_wc);
                                }
                            }
                        } else {
                             $product_logs[] = "[WARNING] No 'options' found in Ecwid combination ID $ecwid_combination_id to map to variation attributes.";
                        }
                        
                        if (empty($variation_attributes_for_wc) && !empty($item['options'])) {
                            $product_logs[] = "[ERROR] Could not map any attributes for variation (Ecwid Combo ID: $ecwid_combination_id). Skipping variation creation. Check if terms exist for attributes.";
                            continue; // Skip this variation if no attributes could be mapped
                        }


                        $variation_id = 0;
                        $existing_vars_query_args = [
                            'post_type' => 'product_variation',
                            'post_status' => 'any',
                            'meta_query' => [
                                [
                                    'key' => '_ecwid_variation_id',
                                    'value' => $ecwid_combination_id,
                                ]
                            ],
                            'post_parent' => $parent_product->get_id(),
                            'posts_per_page' => 1,
                            'fields' => 'ids'
                        ];
                        $existing_vars = get_posts($existing_vars_query_args);
                        if (!empty($existing_vars)) {
                            $variation_id = $existing_vars[0];
                            $product_logs[] = "Found existing WC Variation ID $variation_id for Ecwid Combo ID $ecwid_combination_id.";
                        } else {
                            $product_logs[] = "No existing WC Variation for Ecwid Combo ID $ecwid_combination_id. Creating new.";
                        }

                        $variation = $variation_id ? new WC_Product_Variation($variation_id) : new WC_Product_Variation();
                        $variation->set_parent_id($parent_product->get_id());
                        $variation->set_attributes($variation_attributes_for_wc); // Expects [ 'pa_color' => 'red_slug', ... ]

                        $variation_sku = $combo['sku'] ?? ($parent_product->get_sku() . '-combo-' . $ecwid_combination_id);
                        $variation->set_sku(sanitize_text_field($variation_sku));
                        $variation->set_regular_price(strval($combo['price'] ?? $parent_product->get_regular_price() ?? '0'));
                        if (isset($combo['compareToPrice'])) $variation->set_sale_price(strval($combo['compareToPrice'])); else $variation->set_sale_price('');
                        $variation->set_weight(wc_format_decimal($combo['weight'] ?? $parent_product->get_weight('edit') ?? '')); // Inherit from parent if not set

                        if (isset($combo['quantity'])) {
                            $variation->set_manage_stock(true); $variation->set_stock_quantity(intval($combo['quantity']));
                            $variation->set_stock_status(intval($combo['quantity']) > 0 ? 'instock' : 'outofstock');
                        } elseif (isset($combo['unlimited']) && $combo['unlimited']) {
                            $variation->set_manage_stock(false); $variation->set_stock_quantity(null); $variation->set_stock_status('instock');
                        } else { // Default if no stock info
                            $variation->set_manage_stock(false); $variation->set_stock_quantity(null); $variation->set_stock_status('outofstock');
                        }
                        $variation->set_status('publish'); // Variations are usually published if parent is

                        $var_saved_id = $variation->save();
                        if ($var_saved_id && !is_wp_error($var_saved_id)) {
                            update_post_meta($var_saved_id, '_ecwid_variation_id', $ecwid_combination_id);
                            $product_logs[] = "Saved WC Variation ID $var_saved_id (Ecwid Combo ID: $ecwid_combination_id). Attributes: " . wp_json_encode($variation_attributes_for_wc);
                        } else {
                            $var_error_msg = is_wp_error($var_saved_id) ? $var_saved_id->get_error_message() : "Unknown error";
                            $product_logs[] = "[ERROR] Failed to save WC Variation for Ecwid Combo ID $ecwid_combination_id. Error: $var_error_msg. Attributes attempted: " . wp_json_encode($variation_attributes_for_wc);
                        }
                    }
                    // After all variations are processed for a variable product
                    $parent_product->get_data_store()->sync_variation_prices($parent_product->get_id());
                    $product_logs[] = "Synced variation prices for parent product ID {$parent_product->get_id()}.";
                    // Ensure parent stock status reflects variations
                    wc_product_synchronize_stock_status($parent_product->get_id());
                    $product_logs[] = "Synced stock status for parent product ID {$parent_product->get_id()}.";


                } else {
                    $product_logs[] = "[ERROR] Parent product (ID: $product_saved_id) is not identifiable as a variable product after saving. Cannot process variations.";
                }
            } elseif ($product->is_type('variable')) {
                 $product_logs[] = "[WARNING] Product is variable but no Ecwid 'combinations' found to create variations.";
            }


            // GALLERY IMAGES
            if ($product_saved_id && isset($item['galleryImages']) && is_array($item['galleryImages'])) {
                $product_logs[] = "Processing gallery images.";
                $current_gallery_ids = $product->get_gallery_image_ids('edit');
                $new_gallery_ids_to_set = [];
                $processed_gallery_urls = [];

                // Keep existing gallery images that are still in the Ecwid payload
                foreach($current_gallery_ids as $existing_wc_gallery_image_id) {
                    $source_url = get_post_meta($existing_wc_gallery_image_id, '_ecwid_gallery_image_source_url', true);
                    if ($source_url) {
                        $still_exists_in_ecwid = false;
                        foreach ($item['galleryImages'] as $gallery_image_data) {
                            $ecwid_gallery_url = $gallery_image_data['hdThumbnailUrl'] ?? $gallery_image_data['originalImageUrl'] ?? $gallery_image_data['url'] ?? null;
                            if ($ecwid_gallery_url === $source_url) {
                                $still_exists_in_ecwid = true;
                                break;
                            }
                        }
                        if ($still_exists_in_ecwid) {
                            $new_gallery_ids_to_set[] = $existing_wc_gallery_image_id;
                            $processed_gallery_urls[] = $source_url; // Mark as processed
                            $product_logs[] = "Kept existing gallery image ID $existing_wc_gallery_image_id (URL: $source_url).";
                        } else {
                            // Optionally delete if not in Ecwid payload anymore, or just leave it. For now, we just don't re-add.
                            // wp_delete_attachment($existing_wc_gallery_image_id);
                            // $product_logs[] = "Removed gallery image ID $existing_wc_gallery_image_id as it's no longer in Ecwid gallery.";
                        }
                    }
                }

                // Add new gallery images from Ecwid
                foreach ($item['galleryImages'] as $gallery_image_data) {
                    $gallery_image_url = $gallery_image_data['hdThumbnailUrl'] ?? $gallery_image_data['originalImageUrl'] ?? $gallery_image_data['url'] ?? null;
                    if ($gallery_image_url && !in_array($gallery_image_url, $processed_gallery_urls)) {
                        $product_logs[] = "Attempting to attach gallery image: $gallery_image_url";
                        $g_image_id = $this->attach_image_to_product_from_url($gallery_image_url, $product_saved_id, ($item['name'] ?? 'Product') . ' gallery');
                        if ($g_image_id && !is_wp_error($g_image_id)) {
                            $new_gallery_ids_to_set[] = $g_image_id;
                            update_post_meta($g_image_id, '_ecwid_gallery_image_source_url', esc_url_raw($gallery_image_url));
                            $product_logs[] = "Gallery image attached, WC Attachment ID: $g_image_id.";
                        } else {
                            $product_logs[] = "[WARNING] Failed to attach gallery image. Error: " . (is_wp_error($g_image_id) ? $g_image_id->get_error_message() : 'Unknown error');
                        }
                        $processed_gallery_urls[] = $gallery_image_url; // Mark as processed
                    }
                }
                // Fetch the product again before setting gallery to ensure we have the latest state
                $product_to_update_gallery = wc_get_product($product_saved_id);
                if ($product_to_update_gallery) {
                    $product_to_update_gallery->set_gallery_image_ids(array_unique($new_gallery_ids_to_set));
                    $product_to_update_gallery->save();
                    $product_logs[] = "Gallery images updated. IDs: " . implode(', ', $new_gallery_ids_to_set);
                }
            }
            $product_logs[] = "Successfully processed $log_product_identifier";
            return ['status' => 'imported', 'logs' => $product_logs, 'item_name' => $product_name_for_log, 'ecwid_id' => $ecwid_id_for_log, 'sku' => $sku_for_log];
        } catch (Exception $e) {
            $product_logs[] = "[CRITICAL] Exception during product import: " . $e->getMessage() . " on line " . $e->getLine() . " in " . $e->getFile();
            // $product_logs[] = "Trace: " . $e->getTraceAsString(); // Very verbose
            return ['status' => 'failed', 'logs' => $product_logs, 'item_name' => $product_name_for_log, 'ecwid_id' => $ecwid_id_for_log, 'sku' => $sku_for_log];
        }
    }

    // --- HELPER: ATTACH IMAGE ---
    private function attach_image_to_product_from_url($image_url, $post_id = 0, $desc = null) {
        if (empty($image_url)) return new WP_Error('missing_url', 'Image URL empty.');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $tmp = download_url($image_url, 15);
        if (is_wp_error($tmp)) { @unlink($tmp); return $tmp; }
        $file_array = ['name' => basename(parse_url($image_url, PHP_URL_PATH)), 'tmp_name' => $tmp];
        $attachment_id = media_handle_sideload($file_array, $post_id, $desc);
        if (is_wp_error($attachment_id)) @unlink($file_array['tmp_name']);
        return $attachment_id;
    }

    // --- HELPER: GET TERM ID ---
    private function get_term_id_by_ecwid_id($ecwid_id, $taxonomy) {
        $term = get_terms(['taxonomy' => $taxonomy, 'meta_key' => '_ecwid_category_id', 'meta_value' => $ecwid_id, 'hide_empty' => false, 'number' => 1, 'fields' => 'ids']);
        return !empty($term) && !is_wp_error($term) ? $term[0] : null;
    }
}

new Ecwid_WC_Sync();
?>
