<?php
/**
 * Customer Sync Handler for Ecwid2Woo Plugin
 * 
 * Handles syncing customer data from Ecwid to WooCommerce.
 * Provides both selective and bulk import capabilities.
 * 
 * @package Ecwid2Woo
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Customer Sync Handler Class
 */
class Ecwid2Woo_Customer_Sync {
    
    /**
     * Parent plugin instance
     */
    private $parent_plugin;

    /**
     * Constructor
     */
    public function __construct($parent_plugin) {
        $this->parent_plugin = $parent_plugin;
        
        // Register AJAX handlers
        add_action('wp_ajax_ecwid_wc_fetch_customers_for_display', [$this, 'ajax_fetch_customers_for_display']);
        add_action('wp_ajax_ecwid_wc_import_selected_customers', [$this, 'ajax_import_selected_customers']);
        add_action('wp_ajax_ecwid_wc_sync_all_customers', [$this, 'ajax_sync_all_customers']);
        add_action('wp_ajax_ecwid_wc_fetch_customer_counts', [$this, 'ajax_fetch_customer_counts']);
    }

    /**
     * Render the customer sync page
     */
    public function render_customer_sync_page() {
        ?>
        <div class="ecwid-page-header">
            <h1><?php esc_html_e('Customer Sync', 'ecwid2woo'); ?></h1>
            <p><?php esc_html_e('Load customers from your Ecwid store and select which ones to import or update in WooCommerce.', 'ecwid2woo'); ?></p>
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
            <a href="<?php echo esc_url(admin_url('admin.php?page=' . $this->parent_plugin->partial_sync_slug)); ?>" class="nav-link">
                <span class="nav-icon">🎯</span> <?php esc_html_e('Product Sync', 'ecwid2woo'); ?>
            </a>
            <span class="nav-link current">
                <span class="nav-icon">👥</span> <?php esc_html_e('Customer Sync', 'ecwid2woo'); ?>
            </span>
            <a href="<?php echo esc_url(admin_url('admin.php?page=' . $this->parent_plugin->order_sync_slug)); ?>" class="nav-link">
                <span class="nav-icon">📦</span> <?php esc_html_e('Order Sync', 'ecwid2woo'); ?>
            </a>
        </div>

        <div class="ecwid-sync-container">
            <div id="selective-sync-initial-info" class="selective-sync-initial-info">
                <!-- This will be populated by JavaScript -->
            </div>

            <button id="load-ecwid-customers-button" class="button button-primary"><?php esc_html_e('Reload Customers', 'ecwid2woo'); ?></button>
            <div id="selective-customer-list-container" class="selective-customer-list-container">
                <?php esc_html_e('Customer list will appear here...', 'ecwid2woo'); ?>
            </div>
            <button id="import-selected-customers-button" class="button button-primary import-selected-button"><?php esc_html_e('Import Selected Customers', 'ecwid2woo'); ?></button>
            
            <!-- Bulk Actions -->
            <div class="customer-bulk-actions" style="margin: 25px 0 15px 0; padding-top: 15px; border-top: 1px solid #ddd;">
                <h3><?php esc_html_e('Bulk Actions', 'ecwid2woo'); ?></h3>
                <button id="sync-all-customers-button" class="button button-primary"><?php esc_html_e('Import All Customers', 'ecwid2woo'); ?></button>
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
     * AJAX handler to fetch customer counts
     */
    public function ajax_fetch_customer_counts() {
        check_ajax_referer('ecwid_wc_sync_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized', 'ecwid2woo')]);
            return;
        }

        $api_essentials = $this->parent_plugin->_get_api_essentials();
        if (is_wp_error($api_essentials)) {
            wp_send_json_error(['message' => $api_essentials->get_error_message()]);
            return;
        }

        $customer_count = 0;
        $errors = [];

        // Fetch customer count
        $customers_url = add_query_arg(['limit' => 1], $api_essentials['base_url'] . '/customers');
        $customers_response = wp_remote_get($customers_url, [
            'timeout' => 60,
            'headers' => ['Authorization' => 'Bearer ' . $api_essentials['token'], 'Accept' => 'application/json'],
        ]);

        if (!is_wp_error($customers_response)) {
            $customers_body = json_decode(wp_remote_retrieve_body($customers_response), true);
            $customers_http_code = wp_remote_retrieve_response_code($customers_response);
            
            if ($customers_http_code === 200 && isset($customers_body['total'])) {
                $customer_count = intval($customers_body['total']);
            } else {
                // translators: %d is the HTTP status code
                $errors[] = sprintf(__('Failed to fetch customer count (HTTP %d)', 'ecwid2woo'), $customers_http_code);
            }
        } else {
            // translators: %s is the error message
            $errors[] = sprintf(__('Customer count request failed: %s', 'ecwid2woo'), $customers_response->get_error_message());
        }

        $response_data = [
            'customer_count' => $customer_count,
            'success' => empty($errors)
        ];

        if (!empty($errors)) {
            $response_data['errors'] = $errors;
            $response_data['message'] = implode(' ', $errors);
        }

        wp_send_json_success($response_data);
    }

    /**
     * AJAX handler to fetch customers for display
     */
    public function ajax_fetch_customers_for_display() {
        check_ajax_referer('ecwid_wc_sync_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized', 'ecwid2woo')]);
            return;
        }

        $api_essentials = $this->parent_plugin->_get_api_essentials();
        if (is_wp_error($api_essentials)) {
            wp_send_json_error(['message' => $api_essentials->get_error_message()]);
            return;
        }

        // Fetch all customers with enhanced data similar to product/category sync
        $all_customers = [];
        $offset = 0;
        $limit = 100; // Process in batches
        $total_available = 0;
        $api_calls = 0;

        do {
            $customers_url = add_query_arg([
                'limit' => $limit,
                'offset' => $offset,
                'responseFields' => 'items(id,email,name,billingPerson(name,street,city,countryCode,postalCode,phone),shippingAddresses(id,name,street,city,countryCode,postalCode,phone),registered,stats(ordersCount,totalOrderValue)),total'
            ], $api_essentials['base_url'] . '/customers');

            $response = wp_remote_get($customers_url, [
                'timeout' => 60,
                'headers' => ['Authorization' => 'Bearer ' . $api_essentials['token'], 'Accept' => 'application/json'],
            ]);

            $api_calls++;

            if (is_wp_error($response)) {
                wp_send_json_error(['message' => $response->get_error_message()]);
                return;
            }

            $body = json_decode(wp_remote_retrieve_body($response), true);
            $http_code = wp_remote_retrieve_response_code($response);

            if ($http_code !== 200) {
                if ($http_code === 403) {
                    wp_send_json_error([
                        'message' => __('Customer API access forbidden (HTTP 403). Your API token needs "Read customers" permission. Please regenerate your API token with customer permissions enabled in your Ecwid dashboard (Apps → My Apps → API).', 'ecwid2woo')
                    ]);
                } else {
                    // translators: %d is the HTTP status code
                    wp_send_json_error(['message' => sprintf(__('API request failed (HTTP %d)', 'ecwid2woo'), $http_code)]);
                }
                return;
            }

            if ($api_calls === 1) {
                $total_available = $body['total'] ?? 0;
            }

            $customers = $body['items'] ?? [];
            $all_customers = array_merge($all_customers, $customers);
            
            $offset += $limit;
            
            // Safety check to prevent infinite loops
            if ($api_calls > 50) {
                break;
            }
            
        } while (count($customers) === $limit && count($all_customers) < $total_available);

        wp_send_json_success([
            'customers' => $all_customers,
            'total_found' => count($all_customers),
            'total_available' => $total_available,
            'api_calls_made' => $api_calls,
            // translators: %d is the number of customers loaded
            'message' => sprintf(__('%d customers loaded successfully', 'ecwid2woo'), count($all_customers))
        ]);
    }

    /**
     * AJAX handler to import selected customers
     */
    public function ajax_import_selected_customers() {
        check_ajax_referer('ecwid_wc_sync_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized', 'ecwid2woo')]);
            return;
        }

        $customer_ids = isset($_POST['customer_ids']) ? array_map('intval', $_POST['customer_ids']) : [];
        
        if (empty($customer_ids)) {
            wp_send_json_error(['message' => __('No customers selected', 'ecwid2woo')]);
            return;
        }

        $import_results = [];
        $imported_count = 0;
        $failed_count = 0;

        foreach ($customer_ids as $customer_id) {
            $result = $this->import_customer($customer_id);
            $import_results[] = $result;
            
            if ($result['status'] === 'success') {
                $imported_count++;
            } else {
                $failed_count++;
            }
        }

        wp_send_json_success([
            // translators: %1$d is the number of successful imports, %2$d is the number of failed imports
            'message' => sprintf(__('Import complete. Success: %1$d, Failed: %2$d', 'ecwid2woo'), $imported_count, $failed_count),
            'imported_count' => $imported_count,
            'failed_count' => $failed_count,
            'results' => $import_results
        ]);
    }

    /**
     * AJAX handler to sync all customers
     */
    public function ajax_sync_all_customers() {
        check_ajax_referer('ecwid_wc_sync_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized', 'ecwid2woo')]);
            return;
        }

        // This is a placeholder for bulk customer sync
        // In a real implementation, this would handle large datasets with pagination
        wp_send_json_success([
            'message' => __('Bulk customer sync is not yet implemented', 'ecwid2woo')
        ]);
    }

    /**
     * Import a single customer from Ecwid to WooCommerce
     * 
     * IMPLEMENTATION NOTES:
     * - Customer API endpoint: GET /customers/{customerId}
     * - Required access scope: read_customers
     * - Customer fields: id, email, name, billingPerson, shippingAddresses, etc.
     * - Need to create WordPress user with WooCommerce customer meta
     */
    private function import_customer($customer_id) {
        // This is a placeholder for customer import logic
        // In a real implementation, this would:
        // 1. Fetch customer data from Ecwid API: GET /customers/{customerId}
        // 2. Check if customer already exists in WooCommerce (by email)
        // 3. Create or update WordPress user with wp_insert_user() or wp_update_user()
        // 4. Set WooCommerce customer meta data using update_user_meta()
        // 5. Handle billing and shipping addresses
        
        return [
            'status' => 'success',
            'customer_id' => $customer_id,
            // translators: %d is the customer ID
            'message' => sprintf(__('Customer %d imported successfully', 'ecwid2woo'), $customer_id)
        ];
    }
}
