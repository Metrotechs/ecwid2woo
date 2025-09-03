<?php
/**
 * Order Sync Handler for Ecwid2Woo Plugin
 * 
 * Handles syncing order data from Ecwid to WooCommerce.
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
 * Order Sync Handler Class
 */
class Ecwid2Woo_Order_Sync {
    
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
        add_action('wp_ajax_ecwid_wc_fetch_orders_for_display', [$this, 'ajax_fetch_orders_for_display']);
        add_action('wp_ajax_ecwid_wc_import_selected_orders', [$this, 'ajax_import_selected_orders']);
        add_action('wp_ajax_ecwid_wc_sync_all_orders', [$this, 'ajax_sync_all_orders']);
        add_action('wp_ajax_ecwid_wc_fetch_order_counts', [$this, 'ajax_fetch_order_counts']);
    }

    /**
     * Render the order sync page
     */
    public function render_order_sync_page() {
        ?>
        <div class="ecwid-page-header">
            <h1><?php esc_html_e('Order Sync', 'metrotechs-e2w-sync'); ?></h1>
            <p><?php esc_html_e('Load orders from your Ecwid store and select which ones to import or update in WooCommerce.', 'metrotechs-e2w-sync'); ?></p>
        </div>

        <!-- Navigation Bar -->
        <div class="ecwid-page-nav">
            <a href="<?php echo esc_url(admin_url('admin.php?page=' . $this->parent_plugin->settings_slug)); ?>" class="nav-link">
                <span class="nav-icon">⚙️</span> <?php esc_html_e('Settings', 'metrotechs-e2w-sync'); ?>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=' . $this->parent_plugin->full_sync_slug)); ?>" class="nav-link">
                <span class="nav-icon">🔄</span> <?php esc_html_e('Full Sync', 'metrotechs-e2w-sync'); ?>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=' . $this->parent_plugin->category_sync_slug)); ?>" class="nav-link">
                <span class="nav-icon">📁</span> <?php esc_html_e('Category Sync', 'metrotechs-e2w-sync'); ?>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=' . $this->parent_plugin->partial_sync_slug)); ?>" class="nav-link">
                <span class="nav-icon">🎯</span> <?php esc_html_e('Product Sync', 'metrotechs-e2w-sync'); ?>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=' . $this->parent_plugin->customer_sync_slug)); ?>" class="nav-link">
                <span class="nav-icon">👥</span> <?php esc_html_e('Customer Sync', 'metrotechs-e2w-sync'); ?>
            </a>
            <span class="nav-link current">
                <span class="nav-icon">📦</span> <?php esc_html_e('Order Sync', 'metrotechs-e2w-sync'); ?>
            </span>
        </div>

        <div class="ecwid-sync-container">
            <div id="selective-sync-initial-info" class="selective-sync-initial-info">
                <!-- This will be populated by JavaScript -->
            </div>

            <!-- Order Filtering Options -->
            <div class="order-sync-filters" style="margin: 15px 0; padding: 15px; background: #f5f5f5; border-radius: 5px;">
                <h3><?php esc_html_e('Filter Options', 'metrotechs-e2w-sync'); ?></h3>
                <div style="display: flex; flex-wrap: wrap; gap: 15px; align-items: center;">
                    <div>
                        <label for="order-status-filter" style="font-weight: bold; margin-right: 5px;"><?php esc_html_e('Order Status:', 'metrotechs-e2w-sync'); ?></label>
                        <select id="order-status-filter">
                            <option value=""><?php esc_html_e('All Orders', 'metrotechs-e2w-sync'); ?></option>
                            <optgroup label="Payment Status">
                                <option value="PAID"><?php esc_html_e('Paid', 'metrotechs-e2w-sync'); ?></option>
                                <option value="CANCELLED"><?php esc_html_e('Cancelled', 'metrotechs-e2w-sync'); ?></option>
                                <option value="REFUNDED"><?php esc_html_e('Refunded', 'metrotechs-e2w-sync'); ?></option>
                                <option value="PARTIALLY_REFUNDED"><?php esc_html_e('Partially Refunded', 'metrotechs-e2w-sync'); ?></option>
                            </optgroup>
                            <optgroup label="Fulfillment Status">
                                <option value="AWAITING_PROCESSING"><?php esc_html_e('Awaiting Processing', 'metrotechs-e2w-sync'); ?></option>
                                <option value="PROCESSING"><?php esc_html_e('Processing', 'metrotechs-e2w-sync'); ?></option>
                                <option value="SHIPPED"><?php esc_html_e('Shipped', 'metrotechs-e2w-sync'); ?></option>
                                <option value="DELIVERED"><?php esc_html_e('Delivered', 'metrotechs-e2w-sync'); ?></option>
                                <option value="WILL_NOT_DELIVER"><?php esc_html_e('Will Not Deliver', 'metrotechs-e2w-sync'); ?></option>
                                <option value="RETURNED"><?php esc_html_e('Returned', 'metrotechs-e2w-sync'); ?></option>
                                <option value="READY_FOR_PICKUP"><?php esc_html_e('Ready for Pickup', 'metrotechs-e2w-sync'); ?></option>
                            </optgroup>
                        </select>
                    </div>
                    
                    <div>
                        <label for="order-date-from" style="font-weight: bold; margin-right: 5px;"><?php esc_html_e('From Date:', 'metrotechs-e2w-sync'); ?></label>
                        <input type="date" id="order-date-from" />
                    </div>
                    
                    <div>
                        <label for="order-date-to" style="font-weight: bold; margin-right: 5px;"><?php esc_html_e('To Date:', 'metrotechs-e2w-sync'); ?></label>
                        <input type="date" id="order-date-to" />
                    </div>
                </div>
            </div>

            <button id="load-ecwid-orders-button" class="button button-primary"><?php esc_html_e('Reload Orders', 'metrotechs-e2w-sync'); ?></button>
            <div id="selective-order-list-container" class="selective-order-list-container">
                <?php esc_html_e('Order list will appear here...', 'metrotechs-e2w-sync'); ?>
            </div>
            <button id="import-selected-orders-button" class="button button-primary import-selected-button"><?php esc_html_e('Import Selected Orders', 'metrotechs-e2w-sync'); ?></button>
            
            <!-- Bulk Actions -->
            <div class="order-bulk-actions" style="margin: 25px 0 15px 0; padding-top: 15px; border-top: 1px solid #ddd;">
                <h3><?php esc_html_e('Bulk Actions', 'metrotechs-e2w-sync'); ?></h3>
                <button id="sync-all-orders-button" class="button button-primary"><?php esc_html_e('Import All Orders', 'metrotechs-e2w-sync'); ?></button>
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
     * AJAX handler to fetch order counts
     */
    public function ajax_fetch_order_counts() {
        check_ajax_referer('ecwid_wc_sync_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized', 'metrotechs-e2w-sync')]);
            return;
        }

        $api_essentials = $this->parent_plugin->_get_api_essentials();
        if (is_wp_error($api_essentials)) {
            wp_send_json_error(['message' => $api_essentials->get_error_message()]);
            return;
        }

        $order_count = 0;
        $errors = [];

        // Fetch order count
        $orders_url = add_query_arg(['limit' => 1], $api_essentials['base_url'] . '/orders');
        $orders_response = wp_remote_get($orders_url, [
            'timeout' => 60,
            'headers' => ['Authorization' => 'Bearer ' . $api_essentials['token'], 'Accept' => 'application/json'],
        ]);

        if (!is_wp_error($orders_response)) {
            $orders_body = json_decode(wp_remote_retrieve_body($orders_response), true);
            $orders_http_code = wp_remote_retrieve_response_code($orders_response);
            
            if ($orders_http_code === 200 && isset($orders_body['total'])) {
                $order_count = intval($orders_body['total']);
            } else {
                // translators: %d is the HTTP status code
                $errors[] = sprintf(__('Failed to fetch order count (HTTP %d)', 'metrotechs-e2w-sync'), $orders_http_code);
            }
        } else {
            // translators: %s is the error message
            $errors[] = sprintf(__('Order count request failed: %s', 'metrotechs-e2w-sync'), $orders_response->get_error_message());
        }

        $response_data = [
            'order_count' => $order_count,
            'success' => empty($errors)
        ];

        if (!empty($errors)) {
            $response_data['errors'] = $errors;
            $response_data['message'] = implode(' ', $errors);
        }

        wp_send_json_success($response_data);
    }

    /**
     * AJAX handler to fetch orders for display
     */
    public function ajax_fetch_orders_for_display() {
        check_ajax_referer('ecwid_wc_sync_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized', 'metrotechs-e2w-sync')]);
            return;
        }

        $api_essentials = $this->parent_plugin->_get_api_essentials();
        if (is_wp_error($api_essentials)) {
            wp_send_json_error(['message' => $api_essentials->get_error_message()]);
            return;
        }

        // Get filter parameters
        $status_filter = isset($_POST['status_filter']) ? sanitize_text_field(wp_unslash($_POST['status_filter'])) : '';
        $date_from = isset($_POST['date_from']) ? sanitize_text_field(wp_unslash($_POST['date_from'])) : '';
        $date_to = isset($_POST['date_to']) ? sanitize_text_field(wp_unslash($_POST['date_to'])) : '';

        // Fetch all orders with enhanced data similar to product/customer sync
        $all_orders = [];
        $offset = 0;
        $limit = 100; // Process in batches
        $total_available = 0;
        $api_calls = 0;

        do {
            $query_args = [
                'limit' => $limit,
                'offset' => $offset,
                'responseFields' => 'items(orderNumber,vendorOrderNumber,total,email,billingPerson(name,companyName,street,city,countryCode,postalCode,phone),shippingPerson(name,companyName,street,city,countryCode,postalCode,phone),shippingOption(shippingMethodName,shippingRate,estimatedTransitTime),handlingFee(name,value),tax,ipAddress,couponDiscount,paymentStatus,fulfillmentStatus,orderComments,customerComments,hidden,createDate,updateDate,createTimestamp,updateTimestamp,customerId,customerGroupId,customerGroupName,items(id,productId,categoryId,price,productPrice,sku,quantity,shortDescription,tax,shipping,quantityInStock,name,isShippingRequired,weight,trackQuantity,fixedShippingRateOnly,imageUrl,smallThumbnailUrl,hdThumbnailUrl,couponApplied,selectedOptions(name,value,valuesArray(name,value),selections(selectionTitle,selectionModifier,selectionModifierType)),taxes(name,value,total),files(id,name,size,url),productAvailableForSale,productEnabled)),total'
            ];

            // Add status filter if specified
            if (!empty($status_filter)) {
                if (in_array($status_filter, ['PAID', 'CANCELLED', 'REFUNDED', 'PARTIALLY_REFUNDED'])) {
                    $query_args['paymentStatus'] = $status_filter;
                } else {
                    $query_args['fulfillmentStatus'] = $status_filter;
                }
            }

            // Add date filters if specified
            if (!empty($date_from)) {
                $query_args['createdFrom'] = $date_from;
            }
            if (!empty($date_to)) {
                $query_args['createdTo'] = $date_to;
            }

            $orders_url = add_query_arg($query_args, $api_essentials['base_url'] . '/orders');

            $response = wp_remote_get($orders_url, [
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
                        'message' => __('Order API access forbidden (HTTP 403). Your API token needs "Read orders" permission. Please regenerate your API token with order permissions enabled in your Ecwid dashboard (Apps → My Apps → API).', 'metrotechs-e2w-sync')
                    ]);
                } else {
                    // translators: %d is the HTTP status code
                    wp_send_json_error(['message' => sprintf(__('API request failed (HTTP %d)', 'metrotechs-e2w-sync'), $http_code)]);
                }
                return;
            }

            if ($api_calls === 1) {
                $total_available = $body['total'] ?? 0;
            }

            $orders = $body['items'] ?? [];
            $all_orders = array_merge($all_orders, $orders);
            
            $offset += $limit;
            
            // Safety check to prevent infinite loops
            if ($api_calls > 50) {
                break;
            }
            
        } while (count($orders) === $limit && count($all_orders) < $total_available);

        // Enhance orders with customer association information
        $enhanced_orders = [];
        foreach ($all_orders as $order) {
            $customer_info = $this->find_customer_for_order($order);
            $order['customer_association'] = $customer_info;
            $enhanced_orders[] = $order;
        }

        wp_send_json_success([
            'orders' => $enhanced_orders,
            'total_found' => count($enhanced_orders),
            'total_available' => $total_available,
            'api_calls_made' => $api_calls,
            'filters_applied' => [
                'status' => $status_filter,
                'date_from' => $date_from,
                'date_to' => $date_to
            ],
            // translators: %d is the number of orders loaded
            'message' => sprintf(__('%d orders loaded successfully', 'metrotechs-e2w-sync'), count($enhanced_orders))
        ]);
    }

    /**
     * Find existing WordPress customer for an Ecwid order
     */
    private function find_customer_for_order($order) {
        $customer_info = [
            'wp_user_id' => null,
            'wp_user_email' => null,
            'match_method' => 'none',
            'billing_name' => '',
            'order_email' => $order['email'] ?? ''
        ];

        // Extract customer name from order
        $billing_name = '';
        if (isset($order['billingPerson']['name'])) {
            $billing_name = $order['billingPerson']['name'];
        }
        $customer_info['billing_name'] = $billing_name;

        // Method 1: Try to find by email first
        if (!empty($order['email'])) {
            $wp_user = get_user_by('email', $order['email']);
            if ($wp_user) {
                $customer_info['wp_user_id'] = $wp_user->ID;
                $customer_info['wp_user_email'] = $wp_user->user_email;
                $customer_info['match_method'] = 'email';
                return $customer_info;
            }
        }

        // Method 2: Try to find by customer ID if available
        if (isset($order['customerId']) && !empty($order['customerId'])) {
            // Look for users with Ecwid customer ID meta
            $users = get_users([
                'meta_key' => '_ecwid_customer_id',
                'meta_value' => $order['customerId'],
                'number' => 1
            ]);
            
            if (!empty($users)) {
                $wp_user = $users[0];
                $customer_info['wp_user_id'] = $wp_user->ID;
                $customer_info['wp_user_email'] = $wp_user->user_email;
                $customer_info['match_method'] = 'customer_id';
                return $customer_info;
            }
        }

        // Method 3: Try to find by name similarity (fallback)
        if (!empty($billing_name)) {
            $name_parts = explode(' ', $billing_name);
            if (count($name_parts) >= 2) {
                $first_name = trim($name_parts[0]);
                $last_name = trim(implode(' ', array_slice($name_parts, 1)));
                
                $users = get_users([
                    'meta_query' => [
                        'relation' => 'AND',
                        [
                            'key' => 'first_name',
                            'value' => $first_name,
                            'compare' => 'LIKE'
                        ],
                        [
                            'key' => 'last_name', 
                            'value' => $last_name,
                            'compare' => 'LIKE'
                        ]
                    ],
                    'number' => 1
                ]);
                
                if (!empty($users)) {
                    $wp_user = $users[0];
                    $customer_info['wp_user_id'] = $wp_user->ID;
                    $customer_info['wp_user_email'] = $wp_user->user_email;
                    $customer_info['match_method'] = 'name_similarity';
                    return $customer_info;
                }
            }
        }

        return $customer_info;
    }

    /**
     * AJAX handler to import selected orders
     */
    public function ajax_import_selected_orders() {
        check_ajax_referer('ecwid_wc_sync_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized', 'metrotechs-e2w-sync')]);
            return;
        }

        $order_numbers = isset($_POST['order_numbers']) ? array_map('sanitize_text_field', wp_unslash($_POST['order_numbers'])) : [];
        
        if (empty($order_numbers)) {
            wp_send_json_error(['message' => __('No orders selected', 'metrotechs-e2w-sync')]);
            return;
        }

        $import_results = [];
        $imported_count = 0;
        $failed_count = 0;

        foreach ($order_numbers as $order_number) {
            $result = $this->import_order($order_number);
            $import_results[] = $result;
            
            if ($result['status'] === 'success') {
                $imported_count++;
            } else {
                $failed_count++;
            }
        }

        wp_send_json_success([
            // translators: %1$d is the number of successful imports, %2$d is the number of failed imports
            'message' => sprintf(__('Import complete. Success: %1$d, Failed: %2$d', 'metrotechs-e2w-sync'), $imported_count, $failed_count),
            'imported_count' => $imported_count,
            'failed_count' => $failed_count,
            'results' => $import_results
        ]);
    }

    /**
     * AJAX handler to sync all orders
     */
    public function ajax_sync_all_orders() {
        check_ajax_referer('ecwid_wc_sync_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized', 'metrotechs-e2w-sync')]);
            return;
        }

        // This is a placeholder for bulk order sync
        // In a real implementation, this would handle large datasets with pagination
        wp_send_json_success([
            'message' => __('Bulk order sync is not yet implemented', 'metrotechs-e2w-sync')
        ]);
    }

    /**
     * Import a single order from Ecwid to WooCommerce
     * 
     * IMPLEMENTATION NOTES:
     * - Order API endpoint: GET /orders/{orderNumber}
     * - Required access scope: read_orders
     * - Order fields: orderNumber, email, total, orderItems, billingPerson, etc.
     * - Need to create WooCommerce order with proper status mapping
     */
    private function import_order($order_number) {
        // This is a placeholder for order import logic
        // In a real implementation, this would:
        // 1. Fetch order data from Ecwid API: GET /orders/{orderNumber}
        // 2. Check if order already exists in WooCommerce (by order number meta)
        // 3. Create WooCommerce order using wc_create_order()
        // 4. Associate with customer and products (match by SKU)
        // 5. Set order status mapping (Ecwid statuses to WooCommerce statuses)
        // 6. Handle order items, taxes, shipping, discounts
        // 7. Set order meta data and custom fields
        
        return [
            'status' => 'success',
            'order_number' => $order_number,
            // translators: %s is the order number
            'message' => sprintf(__('Order %s imported successfully', 'metrotechs-e2w-sync'), $order_number)
        ];
    }
}
