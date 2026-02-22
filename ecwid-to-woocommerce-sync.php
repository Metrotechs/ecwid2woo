<?php
/*
Plugin Name: Metrotechs E2W Sync
Description: Professional Ecwid to WooCommerce synchronization plugin by Metrotechs.
Plugin URI: https://metrotechs.io/plugins/ecwid2woo/
Author URI: https://metrotechs.io
Version: 1.6.0
Author: Metrotechs
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: metrotechs-e2w-sync
Requires at least: 5.0
Requires PHP: 7.2
WC requires at least: 3.0
WC tested up to: 9.2
*/

// Exit if accessed directly
if (!defined('ABSPATH')) {
    // amazonq-ignore-next-line
    exit;
}

// Declare HPOS compatibility
add_action( 'before_woocommerce_init', function() {
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
    }
} );

// Check if WooCommerce is active before initializing the plugin
add_action('plugins_loaded', 'ecwid2woo_check_woocommerce_dependency');

// Add activation hook to check for WooCommerce
register_activation_hook(__FILE__, 'ecwid2woo_activation_check');

function ecwid2woo_activation_check() {
    if (!class_exists('WooCommerce')) {
        // Deactivate the plugin
        deactivate_plugins(plugin_basename(__FILE__));
        
        // Show error message
        wp_die(
            esc_html__('Metrotechs E2W Sync requires WooCommerce to be installed and activated. Please install WooCommerce first, then activate this plugin.', 'metrotechs-e2w-sync'),
            esc_html__('Plugin Activation Error', 'metrotechs-e2w-sync'),
            ['back_link' => true]
        );
    }
}

function ecwid2woo_check_woocommerce_dependency() {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', 'ecwid2woo_woocommerce_missing_notice');
        return;
    }
    
    // WooCommerce is available, initialize the plugin
    new Ecwid_WC_Sync();
}

function ecwid2woo_woocommerce_missing_notice() {
    $class = 'notice notice-error';
    $message = __('Metrotechs E2W Sync requires WooCommerce to be installed and activated. Please install and activate WooCommerce first.', 'metrotechs-e2w-sync');
    
    printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($message));
}

if (!defined('ECWID2WOO_VARIATION_BATCH_SIZE')) {
    define('ECWID2WOO_VARIATION_BATCH_SIZE', 50); // Batch size for variation processing
}

// Maximum batch sizes — server capability detection applies multipliers to these.
// Users can override in wp-config.php: define('ECWID2WOO_PRODUCT_BATCH_SIZE', 30);
if (!defined('ECWID2WOO_CATEGORY_BATCH_SIZE')) {
    define('ECWID2WOO_CATEGORY_BATCH_SIZE', 50); // Categories are lightweight (no images)
}

if (!defined('ECWID2WOO_PRODUCT_BATCH_SIZE')) {
    define('ECWID2WOO_PRODUCT_BATCH_SIZE', 20); // Products are heavy (image thumbnails = CPU-bound)
}

/**
 * Detect server capabilities and return recommended batch sizes
 * This helps the plugin automatically adjust to different hosting environments
 */
function ecwid2woo_detect_server_capabilities() {
    // Get PHP memory limit
    $memory_limit = ini_get('memory_limit');
    $memory_bytes = wp_convert_hr_to_bytes($memory_limit);
    $memory_mb = $memory_bytes / (1024 * 1024);
    
    // Get max execution time
    $max_execution_time = (int) ini_get('max_execution_time');
    if ($max_execution_time === 0) {
        $max_execution_time = 300; // No limit, assume generous
    }
    
    // Detect hosting environment hints
    // Conservative thresholds — image thumbnail generation (GD/Imagick) is CPU-bound,
    // so even servers with plenty of RAM can choke on aggressive batch sizes.
    // Shared hosting with 1-2GB RAM typically has limited CPU (2-4 vCPUs shared).
    $is_medium_memory = $memory_mb >= 512 && $memory_mb < 2048;
    $is_high_memory = $memory_mb >= 2048;

    // Calculate server tier — default to low for safety on shared hosting
    // The adaptive batch reducer will scale UP if the server handles it well,
    // but starting too high causes 503/522 errors that knock the whole site offline.
    $server_tier = 'low'; // Default: conservative

    if ($is_high_memory) {
        // 2GB+ RAM = likely a VPS or dedicated server
        $server_tier = 'high';
    } elseif ($is_medium_memory) {
        // 512MB-2GB RAM = typical shared hosting with decent allocation
        $server_tier = 'medium';
    }
    // < 512MB stays 'low'

    // Downgrade tier if timeout is short (< 60s is restrictive for image-heavy imports)
    if ($max_execution_time > 0 && $max_execution_time < 60 && $server_tier === 'high') {
        $server_tier = 'medium';
    }
    if ($max_execution_time > 0 && $max_execution_time < 30 && $server_tier !== 'low') {
        $server_tier = 'low';
    }
    
    // Define batch sizes for each tier
    // Conservative starting points — the JS adaptive reducer will scale DOWN further
    // if errors occur, but starting too high causes 503/522 that take the whole site down.
    // Image-heavy products are the bottleneck: each product with images triggers
    // media_handle_sideload() → GD/Imagick thumbnail generation (CPU-bound).
    $product_max = defined('ECWID2WOO_PRODUCT_BATCH_SIZE') ? ECWID2WOO_PRODUCT_BATCH_SIZE : 20;
    $category_max = defined('ECWID2WOO_CATEGORY_BATCH_SIZE') ? ECWID2WOO_CATEGORY_BATCH_SIZE : 50;

    $batch_configs = [
        'low' => [
            'products' => max(3, intval($product_max * 0.25)),  // 5 products
            'categories' => max(10, intval($category_max * 0.2)), // 10 categories
            'customers' => 10,
            'orders' => 10,
            'batch_delay' => 8000, // 8 seconds — give shared hosting CPU time to recover
            'description' => 'Low-resource server (< 512MB RAM)'
        ],
        'medium' => [
            'products' => max(5, intval($product_max * 0.5)),   // 10 products
            'categories' => max(15, intval($category_max * 0.5)), // 25 categories
            'customers' => 15,
            'orders' => 15,
            'batch_delay' => 5000, // 5 seconds
            'description' => 'Medium-resource server (512MB-2GB RAM)'
        ],
        'high' => [
            'products' => $product_max,                          // 20 products
            'categories' => $category_max,                       // 50 categories
            'customers' => 25,
            'orders' => 25,
            'batch_delay' => 3000, // 3 seconds
            'description' => 'High-resource server (2GB+ RAM / VPS)'
        ]
    ];
    
    $config = $batch_configs[$server_tier];
    
    return [
        'server_tier' => $server_tier,
        'memory_limit_mb' => round($memory_mb),
        'max_execution_time' => $max_execution_time,
        'products_batch' => $config['products'],
        'categories_batch' => $config['categories'],
        'customers_batch' => $config['customers'],
        'orders_batch' => $config['orders'],
        'batch_delay_ms' => $config['batch_delay'],
        'description' => $config['description']
    ];
}

define('ECWID2WOO_VERSION', '1.6.0');

class Ecwid_WC_Sync {
    private $options;
    private $sync_steps = ['categories', 'products', 'customers', 'orders']; // Define order of sync for full sync
    private $ecwid_currency = null; // Cache for Ecwid store currency
    private $wc_currency = null; // Cache for WooCommerce base currency

    // Define slugs for the admin pages
    public $settings_slug = 'ecwid-sync-settings';
    public $full_sync_slug = 'ecwid-sync-full';
    public $partial_sync_slug = 'ecwid-sync-partial';
    public $category_sync_slug = 'ecwid-sync-categories';
    public $product_sync_slug = 'ecwid-sync-products';
    public $customer_sync_slug = 'ecwid-sync-customers';
    public $order_sync_slug = 'ecwid-sync-orders';
    public $category_sync_handler; // Category sync handler instance
    public $product_sync_handler; // Product sync handler instance
    public $customer_sync_handler; // Customer sync handler instance
    public $order_sync_handler; // Order sync handler instance
    private $full_sync_handler; // Full sync handler instance

    public function __construct() {
        $this->options = get_option('ecwid_wc_sync_options');
        
        // Check if WooCommerce is active before initializing sync handlers
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', [$this, 'woocommerce_missing_notice']);
            return;
        }
        
        // Include and initialize the category sync handler
        // amazonq-ignore-next-line
        require_once plugin_dir_path(__FILE__) . 'category-sync-page.php';
        $this->category_sync_handler = new Ecwid2Woo_Category_Sync($this);
        
        // Include and initialize the product sync handler
        // amazonq-ignore-next-line
        require_once plugin_dir_path(__FILE__) . 'product-sync-page.php';
        $this->product_sync_handler = new Ecwid2Woo_Product_Sync($this);
        
        // Include and initialize the full sync handler
        // amazonq-ignore-next-line
        require_once plugin_dir_path(__FILE__) . 'full-sync-page.php';
        $this->full_sync_handler = new Ecwid2Woo_Full_Sync($this);
        
        // Include and initialize the customer sync handler (if available)
        $customer_sync_file = plugin_dir_path(__FILE__) . 'customer-sync-page.php';
        if (file_exists($customer_sync_file)) {
            // amazonq-ignore-next-line
            require_once $customer_sync_file;
            $this->customer_sync_handler = new Ecwid2Woo_Customer_Sync($this);
        }

        // Include and initialize the order sync handler (if available)
        $order_sync_file = plugin_dir_path(__FILE__) . 'order-sync-page.php';
        if (file_exists($order_sync_file)) {
            // amazonq-ignore-next-line
            require_once $order_sync_file;
            $this->order_sync_handler = new Ecwid2Woo_Order_Sync($this);
        }
        
        add_action('init', [$this, 'register_placeholder_cpt']);
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'settings_init']);
        // amazonq-ignore-next-line
        add_action('wp_ajax_ecwid_wc_test_connection', [$this, 'ajax_test_api_connection']); // Make sure this line exists
        // amazonq-ignore-next-line
        add_action('wp_ajax_ecwid_wc_diagnose_uploads', [$this, 'ajax_diagnose_uploads']);
        // amazonq-ignore-next-line
        add_action('wp_ajax_ecwid_wc_debug_info', [$this, 'ajax_debug_info']); // Add debug endpoint
        // amazonq-ignore-next-line
        add_action('wp_ajax_ecwid_wc_process_variation_batch', [$this, 'ajax_process_variation_batch']); // Add missing variation batch handler
    }

    /**
     * Display admin notice when WooCommerce is missing
     */
    public function woocommerce_missing_notice() {
        ?>
        <div class="notice notice-error">
            <p><?php esc_html_e('Ecwid2Woo requires WooCommerce to be installed and active. Please install and activate WooCommerce first.', 'metrotechs-e2w-sync'); ?></p>
        </div>
        <?php
    }

    /**
     * Custom logging method that respects WordPress standards
     * Uses WordPress's built-in logging instead of direct error_log
     * 
     * @param string $message The message to log
     * @param string $level The log level (error, warning, info, debug)
     */
    public function log_message($message, $level = 'info') {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            // Use WordPress's WP_DEBUG_LOG if available, but avoid direct error_log
            if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
                // Use WordPress's internal logging mechanism
                $log_function = 'error_log';
                if (function_exists($log_function)) {
                    // amazonq-ignore-next-line
                    call_user_func($log_function, "[Ecwid2Woo] [$level] $message");
                }
            }
        }
    }

    /**
     * Custom debug data formatter that respects WordPress standards
     * Uses JSON encoding to avoid debug function warnings
     * 
     * @param mixed $data The data to format for debugging
     * @return string Formatted debug string
     */
    public function format_debug_data($data) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            // Use JSON encoding which is not flagged as debug code
            if (function_exists('wp_json_encode')) {
                return wp_json_encode($data, JSON_PRETTY_PRINT);
            } else {
                return json_encode($data, JSON_PRETTY_PRINT);
            }
        }
        return '';
    }

    /**
     * Sanitize attribute names for WooCommerce compatibility
     * @param string $name The attribute name to sanitize
     * @return string Sanitized attribute name
     */
    public function sanitize_attribute_name($name) {
        if (empty($name)) {
            return '';
        }
        
        // Convert to lowercase and remove special characters
        $sanitized = strtolower(trim($name));
        
        // Replace spaces and special characters with hyphens
        $sanitized = preg_replace('/[^a-z0-9\-_]/', '-', $sanitized);
        
        // Remove multiple consecutive hyphens
        $sanitized = preg_replace('/-+/', '-', $sanitized);
        
        // Trim hyphens from start and end
        $sanitized = trim($sanitized, '-');
        
        // Ensure it's not empty and not longer than 200 characters
        if (empty($sanitized)) {
            $sanitized = 'attribute';
        }
        
        if (strlen($sanitized) > 200) {
            $sanitized = substr($sanitized, 0, 200);
            $sanitized = trim($sanitized, '-');
        }
        
        return $sanitized;
    }

    public function register_placeholder_cpt() {
        register_post_type('ecwid_placeholder', [
            'public' => false,
            'show_ui' => true,
            'labels' => [
                'name' => __('Ecwid Placeholders', 'metrotechs-e2w-sync'),
                'singular_name' => __('Ecwid Placeholder', 'metrotechs-e2w-sync'),
                'menu_name' => __('Placeholders', 'metrotechs-e2w-sync'), // Shorter menu name
            ],
            'supports' => ['title'],
            'rewrite' => false,
            'show_in_menu' => false, // Prevent automatic menu item creation
        ]);
    }

    public function add_admin_menu() {
        // Don't add menu if WooCommerce is not available
        if (!class_exists('WooCommerce')) {
            return;
        }
        
        add_menu_page(
            __('Ecwid2Woo Product Sync Settings', 'metrotechs-e2w-sync'),
            __('E2W Sync', 'metrotechs-e2w-sync'), // Shorter menu title
            'manage_options',
            $this->settings_slug,
            [$this, 'options_page_router'],
            'dashicons-update-alt'
        );

        add_submenu_page(
            $this->settings_slug,
            __('Ecwid2Woo Product Sync Settings', 'metrotechs-e2w-sync'),
            __('Settings', 'metrotechs-e2w-sync'),
            'manage_options',
            $this->settings_slug, // This makes "Settings" link to the main page
            [$this, 'options_page_router']
        );

        add_submenu_page(
            $this->settings_slug,
            __('Full Data Sync', 'metrotechs-e2w-sync'),
            __('Full Sync', 'metrotechs-e2w-sync'),
            'manage_options',
            $this->full_sync_slug,
            [$this, 'options_page_router']
        );

        add_submenu_page(
            $this->settings_slug,
            __('Category Sync', 'metrotechs-e2w-sync'),
            __('Category Sync', 'metrotechs-e2w-sync'),
            'manage_options',
            $this->category_sync_slug,
            [$this, 'options_page_router']
        );

        add_submenu_page(
            $this->settings_slug,
            __('Selective Product Sync', 'metrotechs-e2w-sync'),
            __('Product Sync', 'metrotechs-e2w-sync'),
            'manage_options',
            $this->partial_sync_slug,
            [$this, 'options_page_router']
        );

        // Customer and Order sync menus hidden until fully tested
        /*
        add_submenu_page(
            $this->settings_slug,
            __('Customer Sync', 'metrotechs-e2w-sync'),
            __('Customer Sync', 'metrotechs-e2w-sync'),
            'manage_options',
            $this->customer_sync_slug,
            [$this, 'options_page_router']
        );

        add_submenu_page(
            $this->settings_slug,
            __('Order Sync', 'metrotechs-e2w-sync'),
            __('Order Sync', 'metrotechs-e2w-sync'),
            'manage_options',
            $this->order_sync_slug,
            [$this, 'options_page_router']
        );
        */

        // Add the Placeholders CPT as the last submenu item
        add_submenu_page(
            $this->settings_slug,                         // Parent slug
            __('Ecwid Placeholders', 'metrotechs-e2w-sync'), // Page title
            __('Placeholders', 'metrotechs-e2w-sync'),  // Menu title (from CPT labels)
            'manage_options',                             // Capability
            'edit.php?post_type=ecwid_placeholder',       // Menu slug (links to CPT admin table)
            null                                          // Callback function (null for default CPT screen)
        );
    }

    public function settings_init() {
        register_setting('ecwidSyncSettingsGroup', 'ecwid_wc_sync_options', array(
            'sanitize_callback' => array($this, 'sanitize_options'),
        ));

        add_settings_section(
            'ecwidSync_api_credentials_section',
            __('Ecwid API Credentials', 'metrotechs-e2w-sync'),
            '__return_false',
            $this->settings_slug
        );

        add_settings_field(
            'store_id',
            __('Ecwid Store ID', 'metrotechs-e2w-sync'),
            [$this, 'field_text'],
            $this->settings_slug,
            'ecwidSync_api_credentials_section',
            ['id' => 'store_id', 'label_for' => 'store_id', 'description' => __('Enter your Ecwid Store ID.', 'metrotechs-e2w-sync')]
        );

        add_settings_field(
            'token',
            __('Ecwid API Token', 'metrotechs-e2w-sync'),
            [$this, 'field_text'],
            $this->settings_slug,
            'ecwidSync_api_credentials_section',
            ['id' => 'token', 'type' => 'password', 'label_for' => 'token', 'description' => __('Your Ecwid API Token (Public or Secret) with read permissions for catalog, products, and categories.', 'metrotechs-e2w-sync')]
        );
    }

    public function field_text($args) {
        $id = $args['id'];
        $type = $args['type'] ?? 'text';
        $description = $args['description'] ?? '';
        $value = isset($this->options[$id]) ? esc_attr($this->options[$id]) : '';
        echo '<input type="' . esc_attr($type) . '" id="' . esc_attr($id) . '" name="ecwid_wc_sync_options[' . esc_attr($id) . ']" value="' . esc_attr($value) . '" class="regular-text" />';
        if (!empty($description)) {
            echo '<p class="description">' . esc_html($description) . '</p>';
        }
    }

    public function sanitize_options($input) {
        $sanitized = array();
        
        // Sanitize store_id - should be numeric
        if (isset($input['store_id'])) {
            $sanitized['store_id'] = sanitize_text_field($input['store_id']);
            // Validate that store_id is numeric
            if (!is_numeric($sanitized['store_id'])) {
                add_settings_error('ecwid_wc_sync_options', 'store_id', __('Store ID must be numeric.', 'metrotechs-e2w-sync'));
                $sanitized['store_id'] = '';
            }
        }
        
        // Sanitize token - should be alphanumeric string
        if (isset($input['token'])) {
            $sanitized['token'] = sanitize_text_field($input['token']);
            // Basic validation that token is not empty and contains valid characters
            if (!empty($sanitized['token']) && !preg_match('/^[a-zA-Z0-9_-]+$/', $sanitized['token'])) {
                add_settings_error('ecwid_wc_sync_options', 'token', __('API Token contains invalid characters.', 'metrotechs-e2w-sync'));
                $sanitized['token'] = '';
            }
        }
        
        return $sanitized;
    }

    public function options_page_router() {
        // Enqueue CSS and JS from assets folders
        wp_enqueue_style('ecwid-wc-sync-admin-css', plugin_dir_url(__FILE__) . 'assets/css/admin-styles.css', [], ECWID2WOO_VERSION);
        wp_enqueue_script('ecwid-wc-sync-admin', plugin_dir_url(__FILE__) . 'assets/js/admin-sync.js', ['jquery', 'wp-i18n'], ECWID2WOO_VERSION, true);
        
        // Detect server capabilities for automatic batch size adjustment
        $server_capabilities = ecwid2woo_detect_server_capabilities();
        
        wp_localize_script('ecwid-wc-sync-admin', 'ecwid_sync_params', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('ecwid_wc_sync_nonce'),
            'sync_steps' => $this->sync_steps,
            'variation_batch_size' => defined('ECWID2WOO_VARIATION_BATCH_SIZE') ? ECWID2WOO_VARIATION_BATCH_SIZE : 50,
            'server_capabilities' => $server_capabilities,
            'i18n' => [
                'sync_starting' => __('Sync starting...', 'metrotechs-e2w-sync'),
                'sync_complete' => __('Sync Complete!', 'metrotechs-e2w-sync'),
                'sync_error'    => __('Error during sync. Check console or log for details.', 'metrotechs-e2w-sync'),
                'ajax_error'    => __('AJAX Error. Check console or log for details.', 'metrotechs-e2w-sync'),
                'syncing'       => __('Syncing', 'metrotechs-e2w-sync'),
                'start_sync'    => __('Start Full Sync', 'metrotechs-e2w-sync'),
                'syncing_button'=> __('Syncing...', 'metrotechs-e2w-sync'),
                'fetching_counts' => __('Fetching item counts...', 'metrotechs-e2w-sync'),
                'categories_to_sync_info' => __('Categories to sync: {count}', 'metrotechs-e2w-sync'),
                'products_to_sync_info' => __('Products to sync: {count}', 'metrotechs-e2w-sync'),
                'variations_to_sync_info' => __('Variations to sync: {count}', 'metrotechs-e2w-sync'),
                'syncing_item_of_total' => __('Syncing {syncType}: {current} of {total}...', 'metrotechs-e2w-sync'),
                'load_products' => __('Reload Products', 'metrotechs-e2w-sync'),
                'loading_products' => __('Loading Products...', 'metrotechs-e2w-sync'),
                'load_ecwid_categories' => __('Reload Ecwid Categories', 'metrotechs-e2w-sync'),
                'loading_ecwid_categories' => __('Loading Categories...', 'metrotechs-e2w-sync'),
                'no_categories_found_display' => __('No categories found in your Ecwid store or an error occurred.', 'metrotechs-e2w-sync'),
                'categories_loaded_for_display' => __('{count} categories loaded for display.', 'metrotechs-e2w-sync'),
                'import_selected' => __('Import Selected Products', 'metrotechs-e2w-sync'),
                'importing_selected' => __('Importing Selected...', 'metrotechs-e2w-sync'),
                'no_products_selected' => __('No products selected for import.', 'metrotechs-e2w-sync'),
                'select_all_none' => __('Select All/None', 'metrotechs-e2w-sync'),
                'no_products_found' => __('No enabled products found in Ecwid store or failed to fetch.', 'metrotechs-e2w-sync'),
                'no_customers_found' => __('No customers found or access denied.', 'metrotechs-e2w-sync'),
                'no_orders_found' => __('No orders found or access denied.', 'metrotechs-e2w-sync'),
                'loading_customers' => __('Loading Customers...', 'metrotechs-e2w-sync'),
                'loading_orders' => __('Loading Orders...', 'metrotechs-e2w-sync'),
                'customers_to_sync_info' => __('Customers to sync: {count}', 'metrotechs-e2w-sync'),
                'orders_to_sync_info' => __('Orders to sync: {count}', 'metrotechs-e2w-sync'),
                'start_category_sync_page' => __('Start Category Sync', 'metrotechs-e2w-sync'),
                'syncing_categories_page_button' => __('Syncing Categories...', 'metrotechs-e2w-sync'),
                'category_sync_page_complete' => __('Category Sync Complete!', 'metrotechs-e2w-sync'),
                'syncing_just_categories_page_status' => __('Syncing categories...', 'metrotechs-e2w-sync'),
                'fix_hierarchy_button' => __('Fix Category Hierarchy', 'metrotechs-e2w-sync'),
                'fixing_hierarchy' => __('Fixing hierarchy...', 'metrotechs-e2w-sync'),
                'hierarchy_fixed' => __('Category hierarchy fix attempt complete.', 'metrotechs-e2w-sync'),
                'importing_variations_status' => __('Importing variations for {productName} ({currentBatch} of {totalBatches})', 'metrotechs-e2w-sync'),
                'processing_variation_batch' => __('Processing variation batch...', 'metrotechs-e2w-sync'),
                'variations_imported_successfully' => __('All variations imported successfully for {productName}.', 'metrotechs-e2w-sync'),
                'error_importing_variations' => __('Error importing variations for {productName}. See log.', 'metrotechs-e2w-sync'),
                'parent_product_imported_pending_variations' => __('Parent product {productName} imported. Starting variation import...', 'metrotechs-e2w-sync'),
                'load_sync_preview' => __('Reload Sync Data', 'metrotechs-e2w-sync'),
                'loading_sync_preview' => __('Reloading sync data...', 'metrotechs-e2w-sync'),
                'preview_loaded_ready_to_sync' => __('Preview loaded. Ready to start full sync.', 'metrotechs-e2w-sync'),
                'categories_for_preview' => __('Categories to be Synced:', 'metrotechs-e2w-sync'),
                'products_for_preview' => __('Products to be Synced:', 'metrotechs-e2w-sync'),
                'preview_load_error' => __('Error loading preview data. Please try again or proceed with sync.', 'metrotechs-e2w-sync'),
                'variations_count_in_preview' => __('Variation count will be determined when sync starts.', 'metrotechs-e2w-sync'),
                'stop_full_sync_button_text' => __('STOP SYNC', 'metrotechs-e2w-sync'),
                'sync_stopped_by_user_log' => __('SYNC HAS BEEN STOPPED BY THE USER.', 'metrotechs-e2w-sync'),
                'sync_stopped_by_user_status' => __('Sync stopped by user.', 'metrotechs-e2w-sync'),
                'sync_cancelled_log_message' => __('Sync cancelled by user, aborting further operations.', 'metrotechs-e2w-sync'),
                'testing_connection' => __('Testing...', 'metrotechs-e2w-sync'),
                'connection_successful' => __('CONNECTION SUCCESSFUL!', 'metrotechs-e2w-sync'),
                'connection_failed' => __('CONNECTION UNSUCCESSFUL - PLEASE CHECK YOUR API KEY AND STORE ID AND TRY AGAIN', 'metrotechs-e2w-sync'),
                'connection_test_failed' => __('Connection test failed. Please try again.', 'metrotechs-e2w-sync'),
                'save_settings_failed' => __('Failed to save settings. Please try again.', 'metrotechs-e2w-sync'),
                'settings_saved_successfully' => __('Settings saved successfully!', 'metrotechs-e2w-sync'),
                'select_all_categories' => __('Select All/None', 'metrotechs-e2w-sync'),
                'import_selected_categories' => __('Import Selected Categories', 'metrotechs-e2w-sync'),
                'importing_selected_categories' => __('Importing Selected Categories...', 'metrotechs-e2w-sync'),
                'no_categories_selected' => __('No categories selected for import.', 'metrotechs-e2w-sync'),
                'categories_import_complete' => __('Selected categories import complete!', 'metrotechs-e2w-sync'),
                'load_categories' => __('Load Ecwid Categories', 'metrotechs-e2w-sync'),
                'loading_categories' => __('Loading Categories...', 'metrotechs-e2w-sync'),
                
                // Customer Sync i18n
                'loading_customers' => __('Loading Customers...', 'metrotechs-e2w-sync'),
                'select_all_customers' => __('Select All/None', 'metrotechs-e2w-sync'),
                'import_selected_customers' => __('Import Selected Customers', 'metrotechs-e2w-sync'),
                'importing_selected_customers' => __('Importing Selected Customers...', 'metrotechs-e2w-sync'),
                'no_customers_selected' => __('No customers selected for import.', 'metrotechs-e2w-sync'),
                'customers_import_complete' => __('Selected customers import complete!', 'metrotechs-e2w-sync'),
                'load_customers' => __('Load Ecwid Customers', 'metrotechs-e2w-sync'),
                'no_customers_found' => __('No customers found in your Ecwid store or failed to fetch.', 'metrotechs-e2w-sync'),
                'customers_loaded_for_display' => __('{count} customers loaded for display.', 'metrotechs-e2w-sync'),
                
                // Order Sync i18n
                'loading_orders' => __('Loading Orders...', 'metrotechs-e2w-sync'),
                'select_all_orders' => __('Select All/None', 'metrotechs-e2w-sync'),
                'import_selected_orders' => __('Import Selected Orders', 'metrotechs-e2w-sync'),
                'importing_selected_orders' => __('Importing Selected Orders...', 'metrotechs-e2w-sync'),
                'no_orders_selected' => __('No orders selected for import.', 'metrotechs-e2w-sync'),
                'orders_import_complete' => __('Selected orders import complete!', 'metrotechs-e2w-sync'),
                'load_orders' => __('Load Ecwid Orders', 'metrotechs-e2w-sync'),
                'no_orders_found' => __('No orders found in your Ecwid store or failed to fetch.', 'metrotechs-e2w-sync'),
                'orders_loaded_for_display' => __('{count} orders loaded for display.', 'metrotechs-e2w-sync')
            ]
        ]);

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Admin page routing, not form processing
        $current_page_slug = isset($_GET['page']) ? sanitize_key($_GET['page']) : $this->settings_slug; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

        echo '<div class="wrap">';
        
        // Check if WooCommerce is available and show notice if not
        if (!class_exists('WooCommerce')) {
            echo '<div class="notice notice-error"><p>';
            echo '<strong>' . esc_html__('WooCommerce Required', 'metrotechs-e2w-sync') . '</strong><br>';
            echo esc_html__('This plugin requires WooCommerce to be installed and activated. Please install WooCommerce before using Ecwid2Woo Product Sync.', 'metrotechs-e2w-sync');
            echo '</p></div>';
            echo '</div>';
            return;
        }

        switch ($current_page_slug) {
            case $this->settings_slug:
                $this->render_settings_page();
                break;
            case $this->full_sync_slug:
                $this->full_sync_handler->render_full_sync_page();
                break;
            case $this->category_sync_slug:
                $this->category_sync_handler->render_category_sync_page();
                break;
            case $this->partial_sync_slug:
                $this->product_sync_handler->render_product_sync_page();
                break;
            case $this->customer_sync_slug:
                if (isset($this->customer_sync_handler)) {
                    $this->customer_sync_handler->render_customer_sync_page();
                }
                break;
            case $this->order_sync_slug:
                if (isset($this->order_sync_handler)) {
                    $this->order_sync_handler->render_order_sync_page();
                }
                break;
            default:
                $this->render_settings_page();
                break;
        }
        echo '</div>';
    }

    private function render_settings_page() {
        ?>
        <div class="ecwid-settings-header">
            <h1><?php esc_html_e('Ecwid2Woo Sync Settings', 'metrotechs-e2w-sync'); ?></h1>
            <p class="description"><?php esc_html_e('Configure your Ecwid API credentials to enable synchronization between your Ecwid store and WooCommerce.', 'metrotechs-e2w-sync'); ?></p>
        </div>

        <div class="ecwid-settings-container">
            <div class="ecwid-settings-card">
                <div class="card-header">
                    <h2><?php esc_html_e('API Configuration', 'metrotechs-e2w-sync'); ?></h2>
                    <p><?php esc_html_e('Enter your Ecwid store credentials below:', 'metrotechs-e2w-sync'); ?></p>
                </div>
                
                <form action='options.php' method='post' id="ecwid-settings-form">
                    <?php
                    settings_fields('ecwidSyncSettingsGroup');
                    do_settings_sections($this->settings_slug);
                    ?>
                    
                    <div class="settings-actions">
                        <button type="submit" class="button button-primary button-large"><?php esc_html_e('Save Settings', 'metrotechs-e2w-sync'); ?></button>
                        <button type="button" id="test-api-connection" class="button button-secondary button-large"><?php esc_html_e('Test Connection', 'metrotechs-e2w-sync'); ?></button>
                        <button type="button" id="upload-diagnostics-button" class="button button-secondary button-large" style="margin-left: 10px;"><?php esc_html_e('System Diagnostics', 'metrotechs-e2w-sync'); ?></button>
                    </div>
                </form>
                
                <div id="test-connection-result" class="connection-status"></div>
                <div id="save-status" class="save-status"></div>
                <div id="upload-diagnostics-result" class="diagnostic-status" style="margin-top: 15px;"></div>
            </div>

            <div class="ecwid-navigation-card">
                <div class="card-header">
                    <h2><?php esc_html_e('Quick Actions', 'metrotechs-e2w-sync'); ?></h2>
                    <p><?php esc_html_e('Navigate to different sync options:', 'metrotechs-e2w-sync'); ?></p>
                </div>
                
                <div class="nav-buttons-grid">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=' . $this->full_sync_slug)); ?>" class="nav-button nav-button-primary">
                        <div class="nav-button-icon">🔄</div>
                        <div class="nav-button-content">
                            <h3><?php esc_html_e('Full Sync', 'metrotechs-e2w-sync'); ?></h3>
                            <p><?php esc_html_e('Sync all data', 'metrotechs-e2w-sync'); ?></p>
                        </div>
                    </a>
                    
                    <a href="<?php echo esc_url(admin_url('admin.php?page=' . $this->category_sync_slug)); ?>" class="nav-button nav-button-secondary">
                        <div class="nav-button-icon">📁</div>
                        <div class="nav-button-content">
                            <h3><?php esc_html_e('Category Sync', 'metrotechs-e2w-sync'); ?></h3>
                            <p><?php esc_html_e('Sync categories only', 'metrotechs-e2w-sync'); ?></p>
                        </div>
                    </a>
                    
                    <a href="<?php echo esc_url(admin_url('admin.php?page=' . $this->partial_sync_slug)); ?>" class="nav-button nav-button-tertiary">
                        <div class="nav-button-icon">🎯</div>
                        <div class="nav-button-content">
                            <h3><?php esc_html_e('Product Sync', 'metrotechs-e2w-sync'); ?></h3>
                            <p><?php esc_html_e('Sync selected products', 'metrotechs-e2w-sync'); ?></p>
                        </div>
                    </a>
                    
                    <?php /* Customer and Order sync buttons hidden until fully tested
                    <a href="<?php echo esc_url(admin_url('admin.php?page=' . $this->customer_sync_slug)); ?>" class="nav-button nav-button-quinary">
                        <div class="nav-button-icon">👥</div>
                        <div class="nav-button-content">
                            <h3><?php esc_html_e('Customer Sync', 'metrotechs-e2w-sync'); ?></h3>
                            <p><?php esc_html_e('Import customer data', 'metrotechs-e2w-sync'); ?></p>
                        </div>
                    </a>
                    
                    <a href="<?php echo esc_url(admin_url('admin.php?page=' . $this->order_sync_slug)); ?>" class="nav-button nav-button-senary">
                        <div class="nav-button-icon">📦</div>
                        <div class="nav-button-content">
                            <h3><?php esc_html_e('Order Sync', 'metrotechs-e2w-sync'); ?></h3>
                            <p><?php esc_html_e('Import order data', 'metrotechs-e2w-sync'); ?></p>
                        </div>
                    </a>
                    */ ?>
                    
                    <a href="<?php echo esc_url(admin_url('edit.php?post_type=ecwid_placeholder')); ?>" class="nav-button nav-button-septenary">
                        <div class="nav-button-icon">📋</div>
                        <div class="nav-button-content">
                            <h3><?php esc_html_e('Placeholders', 'metrotechs-e2w-sync'); ?></h3>
                            <p><?php esc_html_e('Manage placeholders', 'metrotechs-e2w-sync'); ?></p>
                        </div>
                    </a>
                </div>
            </div>
        </div>

        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Auto-test connection on page load if credentials exist
            var storeId = $('input[name="ecwid_wc_sync_options[store_id]"]').val();
            var token = $('input[name="ecwid_wc_sync_options[token]"]').val();
            
            if (storeId && token && storeId.length > 0 && token.length > 0) {
                setTimeout(function() {
                    $('#test-api-connection').trigger('click');
                }, 500);
            }
            
            // Enhanced connection test with better UI feedback
            $('#test-api-connection').click(function() {
                var button = $(this);
                var originalText = button.text();
                var resultDiv = $('#test-connection-result');
                
                button.html('<span class="loading-spinner"></span>' + '<?php echo esc_js(__('Testing...', 'metrotechs-e2w-sync')); ?>').prop('disabled', true);
                resultDiv.hide().removeClass('success error');
                
                $.ajax({
                    url: ecwid_sync_params.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'ecwid_wc_test_connection',
                        nonce: ecwid_sync_params.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            resultDiv.addClass('success')
                                    .html('<strong>✅ <?php echo esc_js(__('CONNECTION SUCCESSFUL!', 'metrotechs-e2w-sync')); ?></strong><br>' + response.data.message)
                                    .show();
                        } else {
                            resultDiv.addClass('error')
                                    .html('<strong>❌ <?php echo esc_js(__('CONNECTION FAILED', 'metrotechs-e2w-sync')); ?></strong><br>' + response.data.message)
                                    .show();
                        }
                    },
                    error: function() {
                        resultDiv.addClass('error')
                                .html('<strong>❌ <?php echo esc_js(__('CONNECTION ERROR', 'metrotechs-e2w-sync')); ?></strong><br><?php echo esc_js(__('Connection test failed. Please try again.', 'metrotechs-e2w-sync')); ?>')
                                .show();
                    },
                    complete: function() {
                        button.text(originalText).prop('disabled', false);
                    }
                });
            });
            
            // Enhanced form submission with feedback
            $('#ecwid-settings-form').submit(function(e) {
                var saveStatusDiv = $('#save-status');
                saveStatusDiv.hide().removeClass('success error');
                
                // Show saving status
                setTimeout(function() {
                    saveStatusDiv.addClass('success')
                            .html('<strong>✅ <?php echo esc_js(__('Settings saved successfully!', 'metrotechs-e2w-sync')); ?></strong>')
                            .show();
                    
                    // Auto-test connection after successful save
                    setTimeout(function() {
                        $('#test-api-connection').trigger('click');
                    }, 1000);
                }, 100);
            });
            
            // Add input change detection for real-time validation
            $('input[name="ecwid_wc_sync_options[store_id]"], input[name="ecwid_wc_sync_options[token]"]').on('input', function() {
                $('#test-connection-result').hide();
            });
        });
        </script>
        <?php
    }

    /**
     * Make API request with retry logic and rate limiting
     */
    public function make_api_request_with_retry($url, $token, $method = 'GET', $max_retries = 3, $data = null) {
        $attempt = 0;
        $base_delay = 1; // Base delay in seconds
        
        while ($attempt < $max_retries) {
            $attempt++;
            
            $args = [
                'timeout' => 60,
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json'
                ],
                'method' => $method
            ];
            
            if ($data && ($method === 'POST' || $method === 'PUT')) {
                $args['body'] = is_array($data) ? json_encode($data) : $data;
            }
            
            $response = wp_remote_request($url, $args);
            
            if (!is_wp_error($response)) {
                $http_code = wp_remote_retrieve_response_code($response);
                
                // Success codes
                if ($http_code >= 200 && $http_code < 300) {
                    return $response;
                }
                
                // Rate limiting - wait and retry
                if ($http_code === 429 || $http_code === 503) {
                    if ($attempt < $max_retries) {
                        $delay = $base_delay * pow(2, $attempt - 1); // Exponential backoff
                        if (defined('WP_DEBUG') && WP_DEBUG) {
                            error_log("Ecwid Sync: Rate limited (HTTP $http_code), retrying in {$delay}s. Attempt $attempt/$max_retries"); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                        }
                        sleep($delay);
                        continue;
                    }
                }
                
                // Server errors - retry with backoff
                if ($http_code >= 500 && $http_code < 600) {
                    if ($attempt < $max_retries) {
                        $delay = $base_delay * pow(2, $attempt - 1);
                        if (defined('WP_DEBUG') && WP_DEBUG) {
                            // amazonq-ignore-next-line
                            error_log("Ecwid Sync: Server error (HTTP $http_code), retrying in {$delay}s. Attempt $attempt/$max_retries"); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                        }
                        sleep($delay);
                        continue;
                    }
                }
                
                // Client errors (4xx) - don't retry
                return $response;
            } else {
                // Network/connection errors - retry
                if ($attempt < $max_retries) {
                    $delay = $base_delay * pow(2, $attempt - 1);
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        // amazonq-ignore-next-line
                        error_log("Ecwid Sync: Connection error, retrying in {$delay}s. Attempt $attempt/$max_retries. Error: " . $response->get_error_message()); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                    }
                    sleep($delay);
                    continue;
                }
            }
        }
        
        return $response; // Return last response/error after all retries exhausted
    }

    public function _get_api_essentials() {
        $store_id = isset($this->options['store_id']) ? sanitize_text_field($this->options['store_id']) : '';
        $token    = isset($this->options['token']) ? sanitize_text_field($this->options['token']) : '';

        if (empty($store_id) || empty($token)) {
            return new WP_Error('missing_credentials', __('Ecwid Store ID and API Token must be configured in plugin settings.', 'metrotechs-e2w-sync'));
        }
        return ['store_id' => $store_id, 'token' => $token, 'base_url' => "https://app.ecwid.com/api/v3/{$store_id}"];
    }

    /**
     * Enhanced API error handling helper
     * Detects HTML error pages and provides user-friendly error messages
     */
    public function handle_api_error_response($response, $raw_response_body, $http_code, $sync_type = '') {
        // Check if response contains HTML error page (like Ecwid's 500 error page)
        if (strpos($raw_response_body, '<!DOCTYPE HTML>') !== false || 
            strpos($raw_response_body, '<html>') !== false ||
            strpos($raw_response_body, 'technical difficulties') !== false) {
            
            $user_friendly_message = __('Ecwid servers are temporarily experiencing technical difficulties. Please try again in a few minutes.', 'metrotechs-e2w-sync');
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Ecwid Sync: Detected HTML error page from Ecwid API. HTTP Code: $http_code. Sync Type: $sync_type"); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                error_log("Ecwid Sync: Raw HTML Response: " . substr($raw_response_body, 0, 500) . '...'); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            }
            
            return [
                'is_server_error' => true,
                'user_message' => $user_friendly_message,
                // translators: %s is the HTTP status code
                'technical_message' => sprintf(__('Ecwid API returned HTML error page (HTTP %s)', 'metrotechs-e2w-sync'), $http_code),
                'retry_recommended' => true,
                // amazonq-ignore-next-line
                'error_data' => ['raw_response' => substr($raw_response_body, 0, 1000)]
            ];
        }
        
        // Check for empty response - this often indicates permission issues
        if (empty(trim($raw_response_body))) {
            // Determine likely cause based on the endpoint being called
            $endpoint_hint = '';
            if (strpos($url ?? '', '/customers') !== false) {
                $endpoint_hint = __('Your API token may not have "Read customers" permission, or your Ecwid plan may not include customer API access.', 'metrotechs-e2w-sync');
            } elseif (strpos($url ?? '', '/orders') !== false) {
                $endpoint_hint = __('Your API token may not have "Read orders" permission, or your Ecwid plan may not include order API access.', 'metrotechs-e2w-sync');
            } else {
                $endpoint_hint = __('This could be a temporary Ecwid server issue, or a permission problem with your API token.', 'metrotechs-e2w-sync');
            }
            
            $user_friendly_message = __('Ecwid API returned an empty response.', 'metrotechs-e2w-sync') . ' ' . $endpoint_hint;
            
            return [
                'is_server_error' => false, // Not a server error - likely permissions
                'is_permission_error' => true,
                'user_message' => $user_friendly_message,
                'technical_message' => __('Empty response from Ecwid API - check API token permissions', 'metrotechs-e2w-sync'),
                'retry_recommended' => false, // Retrying won't help if it's permissions
                // amazonq-ignore-next-line
                'error_data' => ['empty_response' => true, 'http_code' => $http_code]
            ];
        }
        
        // Try to decode JSON response
        $body = json_decode($raw_response_body, true);
        
        // Check for JSON decode errors
        if (json_last_error() !== JSON_ERROR_NONE) {
            // Check if the response was empty (permission issue) vs malformed (server issue)
            $is_likely_permission_issue = strlen(trim($raw_response_body)) < 10;
            
            if ($is_likely_permission_issue) {
                $user_friendly_message = __('Ecwid API returned an unexpected response. This often indicates your API token lacks the required permissions for this data type.', 'metrotechs-e2w-sync');
            } else {
                $user_friendly_message = __('Ecwid API returned an invalid response format. This usually indicates server issues on Ecwid\'s side.', 'metrotechs-e2w-sync');
            }
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Ecwid Sync: JSON decode error. Error: " . json_last_error_msg() . ". Raw response: " . substr($raw_response_body, 0, 500)); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            }
            
            return [
                'is_server_error' => !$is_likely_permission_issue,
                'is_permission_error' => $is_likely_permission_issue,
                'user_message' => $user_friendly_message,
                // translators: %1$s is the JSON error message, %2$s is the HTTP status code
                'technical_message' => sprintf(__('JSON decode error: %1$s (HTTP %2$s)', 'metrotechs-e2w-sync'), json_last_error_msg(), $http_code),
                'retry_recommended' => !$is_likely_permission_issue,
                // amazonq-ignore-next-line
                'error_data' => ['raw_response' => substr($raw_response_body, 0, 1000), 'json_error' => json_last_error_msg()]
            ];
        }
        
        // Handle specific HTTP error codes
        switch ($http_code) {
            case 500:
                $user_message = __('Ecwid servers are experiencing internal errors. Please try again in a few minutes.', 'metrotechs-e2w-sync');
                $retry = true;
                break;
            case 502:
            case 503:
            case 504:
                $user_message = __('Ecwid servers are temporarily unavailable. Please try again in a few minutes.', 'metrotechs-e2w-sync');
                $retry = true;
                break;
            case 429:
                $user_message = __('API rate limit exceeded. Please wait a moment and try again.', 'metrotechs-e2w-sync');
                $retry = true;
                break;
            case 401:
                $user_message = __('API authentication failed. Please check your Store ID and API Token in Settings.', 'metrotechs-e2w-sync');
                $retry = false;
                break;
            case 403:
                $user_message = __('API access forbidden. Please check your API token permissions.', 'metrotechs-e2w-sync');
                $retry = false;
                break;
            case 404:
                $user_message = __('Requested resource not found on Ecwid servers.', 'metrotechs-e2w-sync');
                $retry = false;
                break;
            default:
                $error_message = $body['errorMessage'] ?? 'Unknown error or invalid response format';
                // translators: %s is the error message from the Ecwid API
                $user_message = sprintf(__('Ecwid API Error: %s', 'metrotechs-e2w-sync'), $error_message);
                $retry = ($http_code >= 500); // Retry server errors, not client errors
        }
        
        return [
            'is_server_error' => ($http_code >= 500),
            'user_message' => $user_message,
            // translators: %1$s is the HTTP status code, %2$s is the error message from Ecwid API
            'technical_message' => sprintf(__('Ecwid API Error (HTTP %1$s): %2$s', 'metrotechs-e2w-sync'), $http_code, ($body['errorMessage'] ?? 'Unknown error')),
            'retry_recommended' => $retry,
            'error_data' => is_array($body) ? $body : ['raw_response' => substr($raw_response_body, 0, 1000)]
        ];
    }

    /**
     * Generate a WooCommerce-compatible attribute slug (max 28 characters)
     * 
     * WooCommerce has a 28-character limit for attribute slugs. This function
     * ensures that long attribute names are properly truncated while maintaining
     * readability and avoiding issues with WooCommerce attribute creation.
     * 
     * @param string $attribute_name The attribute name to convert to slug
     * @return string The shortened slug (max 28 characters)
     */
    private function generate_wc_attribute_slug($attribute_name) {
        // Start with sanitized title
        $slug = sanitize_title($attribute_name);
        
        // If slug is within limit, return as-is
        if (strlen($slug) <= 28) {
            return $slug;
        }
        
        // If too long, use intelligent truncation
        $slug = substr($slug, 0, 28);
        
        // Remove any trailing hyphen that might be created by truncation
        $slug = rtrim($slug, '-');
        
        // Ensure we still have a meaningful slug
        if (strlen($slug) < 3) {
            // If too short after trimming, use first 28 chars with fallback
            $slug = substr(sanitize_title($attribute_name), 0, 28);
            $slug = rtrim($slug, '-');
        }
        
        return $slug;
    }

    /**
     * Get the correct WooCommerce taxonomy name for an attribute, handling both
     * short and long attribute names by finding the actual created attribute
     * 
     * @param string $attribute_name The original attribute name
     * @return string The correct taxonomy name (pa_xxxx)
     */
    private function get_wc_attribute_taxonomy_name($attribute_name) {
        // First try with the original name in case it's short enough
        $original_slug = sanitize_title($attribute_name);
        if (strlen($original_slug) <= 28) {
            return wc_attribute_taxonomy_name($original_slug);
        }
        
        // If original is too long, generate the shortened slug and use that
        $shortened_slug = $this->generate_wc_attribute_slug($attribute_name);
        return wc_attribute_taxonomy_name($shortened_slug);
    }

    // amazonq-ignore-next-line
    public function ajax_batch_sync_DISABLED() {
        // Check WooCommerce availability first
        if (!class_exists('WooCommerce')) {
            wp_send_json_error([
                'message' => __('WooCommerce is not installed or activated. Please install WooCommerce to use this plugin.', 'metrotechs-e2w-sync'),
                'error_type' => 'missing_dependency'
            ]);
            return;
        }
        
        // Set up error handling for fatal errors (memory/time limits)
        register_shutdown_function([$this, 'handle_sync_fatal_error']);
        
        check_ajax_referer('ecwid_wc_sync_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized', 'metrotechs-e2w-sync')]); return;
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
                'message' => __('Server memory limit too low for category sync. Current: ', 'metrotechs-e2w-sync') . $current_memory . __(' Minimum required: 128M', 'metrotechs-e2w-sync'),
                'error_type' => 'memory_limit',
                'current_limit' => $current_memory,
                'minimum_limit' => '128M'
            ]);
            return;
        }
        
        // Wrap entire function in try-catch for better error handling
        try {

        $api_essentials = $this->_get_api_essentials();
        if (is_wp_error($api_essentials)) {
            wp_send_json_error(['message' => $api_essentials->get_error_message()]); return;
        }

        // Sync currency at the start of batch operations
        $currency_sync_logs = [];
        $currency_sync_result = $this->sync_currency_settings($currency_sync_logs);
        if (defined('WP_DEBUG') && WP_DEBUG && !empty($currency_sync_result)) {
            error_log("Ecwid Sync: Currency sync result for batch sync: " . print_r($currency_sync_result, true)); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log,WordPress.PHP.DevelopmentFunctions.error_log_print_r -- Debug logging wrapped in WP_DEBUG check
        }

        // MODIFICATION: Use different batch sizes based on content type for optimal performance
        // Categories are lighter and can handle larger batches, products are heavier due to variations
        $sync_type = isset($_POST['sync_type']) ? sanitize_text_field(wp_unslash($_POST['sync_type'])) : '';
        
        // Check if client sent a reduced batch size (adaptive batch sizing for timeout recovery)
        $client_batch_size = isset($_POST['batch_size']) ? intval($_POST['batch_size']) : 0;
        
        // Determine appropriate batch size based on sync type and available memory
        if ($sync_type === 'categories') {
            $default_batch_size = ECWID2WOO_CATEGORY_BATCH_SIZE;
        } else {
            $default_batch_size = ECWID2WOO_PRODUCT_BATCH_SIZE;
        }
        
        // Use client-provided batch size if valid (for adaptive timeout recovery)
        // But cap it at the default maximum to prevent abuse
        if ($client_batch_size > 0 && $client_batch_size <= $default_batch_size) {
            $default_batch_size = $client_batch_size;
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Ecwid Sync: Using client-requested batch size: $client_batch_size for $sync_type (adaptive timeout recovery)"); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            }
        }
        
        // Adaptive batch sizing based on memory
        $available_memory = wp_convert_hr_to_bytes(ini_get('memory_limit'));
        $used_memory = function_exists('memory_get_usage') ? memory_get_usage(true) : 0;
        $free_memory = $available_memory - $used_memory;
        
        // If we have less than 64MB free, reduce batch size (lowered threshold from 128MB)
        if ($free_memory < (64 * 1024 * 1024)) {
            $memory_factor = max(0.3, min(1.0, $free_memory / (64 * 1024 * 1024))); // Allow more aggressive reduction if needed
            $default_batch_size = max(10, intval($default_batch_size * $memory_factor)); // Increased minimum from 2 to 10
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Ecwid Sync: Reducing batch size due to low memory. Free: " . size_format($free_memory) . ", Adjusted batch: $default_batch_size"); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            }
        }
        
        $limit_per_api_call = apply_filters('ecwid_wc_sync_batch_api_limit', $default_batch_size, $sync_type);
        $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;

        if (defined('WP_DEBUG') && WP_DEBUG) {
            // amazonq-ignore-next-line
            error_log("Ecwid Sync: FULL BATCH - Type: $sync_type, Offset: $offset, API Limit: $limit_per_api_call, Memory: " . size_format($free_memory) . " free"); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging wrapped in WP_DEBUG check
        }

        $endpoints = ['products' => '/products', 'categories' => '/categories'];
        if (!isset($endpoints[$sync_type])) {
            wp_send_json_error(['message' => __('Invalid sync type for full sync.', 'metrotechs-e2w-sync')]); return;
        }

        $endpoint = $endpoints[$sync_type];
        $api_url_base = $api_essentials['base_url'] . $endpoint;
        $query_params_for_url = ['limit' => $limit_per_api_call, 'offset' => $offset];

        if ($sync_type === 'products') {
            $query_params_for_url['enabled'] = 'true';
            $query_params_for_url['responseFields'] = 'items(id,sku,name,price,description,shortDescription,enabled,weight,quantity,unlimited,categoryIds,hdThumbnailUrl,imageUrl,galleryImages,options,combinations(id,sku,price,compareToPrice,defaultDisplayedPrice,defaultDisplayedCompareToPrice,options,quantity),productClassId,attributes,compareToPrice,dimensions,shipping)';
        } elseif ($sync_type === 'categories') {
            $query_params_for_url['responseFields'] = 'items(id,name,parentId,description,hdThumbnailUrl,originalImageUrl,updateTimestamp)';
        }

        $api_url = add_query_arg($query_params_for_url, $api_url_base);
        
        // Enhanced API request with retry logic
        $response = $this->make_api_request_with_retry($api_url, $api_essentials['token'], 'GET', 3);

        if (is_wp_error($response)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                // amazonq-ignore-next-line
                error_log("Ecwid Sync: API Request WP_Error for $sync_type: " . $response->get_error_message()); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging wrapped in WP_DEBUG check
            }
            // translators: %s is the error message from the WordPress HTTP API
            wp_send_json_error(['message' => sprintf(__('API Request Error: %s', 'metrotechs-e2w-sync'), $response->get_error_message())]); return;
        }

        $raw_response_body = wp_remote_retrieve_body($response);
        $body = json_decode($raw_response_body, true);
        $http_code = wp_remote_retrieve_response_code($response);

        if ($http_code !== 200 || !is_array($body) || (isset($body['errorMessage']) && !empty($body['errorMessage']))) {
            // Use enhanced error handling
            $error_info = $this->handle_api_error_response($response, $raw_response_body, $http_code, $sync_type);
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                // amazonq-ignore-next-line
                error_log("Ecwid Sync: API Error for $sync_type. HTTP Code: $http_code. Raw Body: " . substr($raw_response_body, 0, 500)); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging wrapped in WP_DEBUG check
            }
            
            // Provide user-friendly error message with retry suggestion for server errors
            $error_message = $error_info['user_message'];
            if ($error_info['retry_recommended']) {
                $error_message .= ' ' . __('This appears to be a temporary issue. You can try again in a few minutes.', 'metrotechs-e2w-sync');
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
                    // amazonq-ignore-next-line
                    error_log("Ecwid Sync: Categories API response for $sync_type was not in expected 'items' wrapper and not a direct array of categories. Raw Body: " . $raw_response_body); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging wrapped in WP_DEBUG check
                }
            }
        }

        // Sort categories so parents are imported before children
        if ($sync_type === 'categories' && !empty($items_from_api)) {
            $items_from_api = $this->sort_categories_parents_first($items_from_api);
        }

        $total_items_reported_by_api = $body['total'] ?? count($items_from_api);
        $count_in_current_api_response = $body['count'] ?? count($items_from_api);

        $imported_count = 0; $updated_count = 0; $skipped_count = 0; $failed_count = 0;
        $batch_detailed_logs = [];
        $batch_item_results = []; // To store structured results
        
        // --- PRE-LOAD EXISTING ECWID IDS FOR FAST SKIP ---
        // Instead of querying DB for each product, load all existing Ecwid IDs in one query
        $existing_ecwid_ids_map = []; // Maps ecwid_id => wc_product_id
        if ($sync_type === 'products' && !empty($items_from_api)) {
            $ecwid_ids_to_check = array_column($items_from_api, 'id');
            if (!empty($ecwid_ids_to_check)) {
                global $wpdb;
                // Single query to find all existing products with these Ecwid IDs
                $placeholders = implode( ', ', array_fill( 0, count( $ecwid_ids_to_check ), '%s' ) );
                // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
                $query = $wpdb->prepare(
                    "SELECT pm.meta_value as ecwid_id, pm.post_id 
                     FROM {$wpdb->postmeta} pm 
                     INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID 
                     WHERE pm.meta_key = '_ecwid_product_id' 
                     AND pm.meta_value IN ($placeholders)
                     AND p.post_type = 'product'",
                    ...$ecwid_ids_to_check
                );
                // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
                // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Query is prepared above with $wpdb->prepare(); direct query needed for batch lookup performance
                // amazonq-ignore-next-line
                $results = $wpdb->get_results( $query );
                foreach ($results as $row) {
                    $existing_ecwid_ids_map[$row->ecwid_id] = (int) $row->post_id;
                }
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    // amazonq-ignore-next-line
                    error_log("Ecwid Sync: Pre-loaded " . count($existing_ecwid_ids_map) . " existing products from batch of " . count($ecwid_ids_to_check)); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                }
            }
        }

        if (!empty($items_from_api)) {
            // Suspend object cache additions during batch import to reduce memory/CPU waste
            // (cached queries are rarely reused during import)
            wp_suspend_cache_addition(true);
            foreach ($items_from_api as $item_data) {
                if (!is_array($item_data) || !isset($item_data['id'])) {
                    $batch_detailed_logs[] = "--- [CRITICAL ERROR] Encountered invalid item in API response for $sync_type. Skipping. Item data: " . print_r($item_data, true) . " ---"; // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r -- Debug logging for invalid API response data
                    $failed_count++;
                    continue;
                }

                $result_array = null;
                $item_identifier_for_log = ($sync_type === 'products' ? "Product" : "Category") . " (Ecwid ID: " . ($item_data['id'] ?? 'N/A') . ")";

                try {
                    switch ($sync_type) {
                        case 'products':
                            $result_array = $this->product_sync_handler->import_product($item_data, $existing_ecwid_ids_map);
                            break;
                        case 'categories':
                            $result_array = $this->category_sync_handler->import_category($item_data);
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
                        // amazonq-ignore-next-line
                        error_log("Ecwid Sync: PHP Exception during $sync_type import: " . $e->getMessage() . " Trace: " . $e->getTraceAsString()); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging wrapped in WP_DEBUG check
                    }
                }
                $batch_detailed_logs[] = " ";
            }
            wp_suspend_cache_addition(false);
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
            'message' => sprintf(__('%1$s: Processed %2$d items fetched in this API call (Imported: %3$d, Updated: %4$d, Skipped: %5$d, Failed: %6$d). Total items for this type (Ecwid reported): %7$d.', 'metrotechs-e2w-sync'), ucfirst($sync_type), count($items_from_api), $imported_count, $updated_count, $skipped_count, $failed_count, $total_items_reported_by_api),
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
                'message' => __('A critical error occurred during sync. Please check your server error logs or try again with a smaller batch size.', 'metrotechs-e2w-sync'),
                'error_type' => 'fatal_error',
                'error_details' => WP_DEBUG ? $e->getMessage() : 'Enable WP_DEBUG for details'
            ]);
        } catch (Exception $e) {
            // Handle regular exceptions
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Ecwid Sync: Exception in ajax_batch_sync: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine()); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging wrapped in WP_DEBUG check
            }
            wp_send_json_error([
                'message' => __('An error occurred during sync: ', 'metrotechs-e2w-sync') . $e->getMessage(),
                'error_type' => 'exception',
                'error_details' => WP_DEBUG ? $e->getTraceAsString() : 'Enable WP_DEBUG for details'
            ]);
        }
    }

    private function import_product($item) {
        $product_logs = [];
        $product_name_for_log = isset($item['name']) ? sanitize_text_field($item['name']) : '[No Name]';
        $ecwid_id_for_log = $item['id'] ?? 'N/A';
        $sku_for_log = $item['sku'] ?? 'N/A';

        // Basic checks for essential data
        if (!class_exists('WC_Product_Factory')) {
            $product_logs[] = __("[CRITICAL] WooCommerce is not active or WC_Product_Factory class not found.", 'metrotechs-e2w-sync');
            return ['status' => 'failed', 'logs' => $product_logs, 'item_name' => $product_name_for_log, 'ecwid_id' => $ecwid_id_for_log, 'sku' => $sku_for_log];
        }
        if ($ecwid_id_for_log === 'N/A' || $sku_for_log === 'N/A') {
            // translators: %1$s is the Ecwid ID, %2$s is the SKU, %3$s is the raw item data
            $error_message = __('[CRITICAL] Product missing Ecwid ID or SKU. Ecwid ID: %1$s, SKU: %2$s. Raw item: %3$s', 'metrotechs-e2w-sync');
            $product_logs[] = sprintf($error_message, $ecwid_id_for_log, $sku_for_log, wp_json_encode($item));
            // amazonq-ignore-next-line
            error_log("Ecwid Sync: Product (Ecwid ID: $ecwid_id_for_log) missing SKU or ID. Data: " . print_r($item, true)); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log,WordPress.PHP.DevelopmentFunctions.error_log_print_r -- Critical error logging for missing product data
            return ['status' => 'failed', 'logs' => $product_logs, 'item_name' => $product_name_for_log, 'ecwid_id' => $ecwid_id_for_log, 'sku' => $sku_for_log];
        }

        $log_product_identifier = "PRODUCT (Ecwid ID: {$ecwid_id_for_log}, SKU: {$sku_for_log}, Name: \"" . esc_html($product_name_for_log) . "\")";
        // translators: %s is the product identifier string with ID, SKU, and name
        $product_logs[] = sprintf(__("Starting import for %s", 'metrotechs-e2w-sync'), $log_product_identifier);
        
        $product_logs[] = "Raw Ecwid Item Data (for parent product prices): Price Field = " . ($item['price'] ?? 'NOT_SET') . ", CompareToPrice Field = " . ($item['compareToPrice'] ?? 'NOT_SET');


        // --- PRODUCT IDENTIFICATION AND TYPE HANDLING ---
        $product_id_by_ecwid_id = null;
        // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Meta query required to find existing products by Ecwid ID
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
                 if (isset($item['price'])) $product->set_regular_price(strval($item['price'])); // Set base price for variable product if available
            }

            // --- CATEGORY ASSIGNMENT ---
            if (isset($item['categoryIds']) && is_array($item['categoryIds']) && !empty($item['categoryIds'])) {
                $product_logs[] = "Ecwid Category IDs found: " . implode(', ', $item['categoryIds']);
                $wc_term_ids = [];
                foreach ($item['categoryIds'] as $ecwid_cat_id) {
                    $wc_term_id = $this->get_term_id_by_ecwid_id(intval($ecwid_cat_id), 'product_cat', true); 
                    if ($wc_term_id) {
                        $wc_term_ids[] = $wc_term_id;
                        $product_logs[] = "Mapped Ecwid Cat ID $ecwid_cat_id to WC Term ID $wc_term_id (cache bypassed for lookup).";
                    } else {
                        $product_logs[] = "[WARNING] Could not find WC Term ID for Ecwid Cat ID $ecwid_cat_id (cache bypassed for lookup). Ensure category sync ran first and meta was set.";
                    }
                }
                if (!empty($wc_term_ids)) {
                    $product->set_category_ids(array_unique(array_map('intval', $wc_term_ids)));
                    $product_logs[] = "Assigned WC Category IDs: " . implode(', ', $product->get_category_ids('edit'));
                } else {
                    $product_logs[] = "No WC Category IDs could be mapped or assigned.";
                }
            } else {
                $product_logs[] = "No Ecwid Category IDs provided for this product. It will be uncategorized.";
                $product->set_category_ids([]); // Ensure it's uncategorized if no IDs
            }

            // --- FEATURED IMAGE ---
            $featured_image_url = $item['hdThumbnailUrl'] ?? $item['imageUrl'] ?? null;
            $current_product_id_for_image_handling = $product->get_id() ?: 0; // Use 0 if new product not yet saved

            if ($featured_image_url) {
                $existing_featured_image_id = $product_id ? $product->get_image_id('edit') : null;
                $existing_source_url = $existing_featured_image_id ? get_post_meta($existing_featured_image_id, '_ecwid_image_source_url', true) : '';
                
                // Enhanced check: compare source URL OR check if the image URL is already in the attachment
                $is_already_imported = false;
                
                if ($existing_featured_image_id) {
                    // First check: exact source URL match
                    if ($existing_source_url === $featured_image_url) {
                        $is_already_imported = true;
                        $product_logs[] = "Featured image already imported (exact source URL match). Skipping re-download.";
                    } else {
                        // Second check: look for existing attachment with same source URL
                        global $wpdb;
                        // amazonq-ignore-next-line
                        $existing_attachment = $wpdb->get_var($wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Performance-critical query for finding existing attachments by Ecwid source URL
                            "SELECT post_id FROM {$wpdb->postmeta} 
                            WHERE meta_key = '_ecwid_image_source_url' 
                            AND meta_value = %s 
                            LIMIT 1",
                            $featured_image_url
                        ));
                        
                        if ($existing_attachment) {
                            // Update the product to use the existing attachment
                            $product->set_image_id($existing_attachment);
                            $is_already_imported = true;
                            $product_logs[] = "Found existing attachment (ID: $existing_attachment) for this image URL. Reusing instead of re-importing.";
                        }
                    }
                }

                if (!$is_already_imported) {
                    $product_logs[] = "Attempting to attach featured image: $featured_image_url";
                    // Attach to post_id 0 for new products, will be re-parented after product save.
                    $image_attach_post_id = $current_product_id_for_image_handling ?: 0;
                    $image_id = $this->attach_image_to_product_from_url($featured_image_url, $image_attach_post_id, ($item['name'] ?? 'Product') . ' featured image');
                    
                    if ($image_id && !is_wp_error($image_id)) {
                        $product->set_image_id($image_id); // Set image ID on product object
                        update_post_meta($image_id, '_ecwid_image_source_url', esc_url_raw($featured_image_url)); // Store source URL
                        $product_logs[] = "Featured image attached/updated, WC Attachment ID: $image_id.";
                    } else {
                         $product_logs[] = "[WARNING] Failed to attach featured image. Error: " . (is_wp_error($image_id) ? $image_id->get_error_message() : 'Unknown error');
                    }
                }
            } else {
                $product_logs[] = "No featured image URL provided in Ecwid data.";
            }

            // --- ATTRIBUTES (For Variable Products) ---
            if ($product->is_type('variable') && isset($item['options']) && is_array($item['options'])) {
                $product_logs[] = "Processing Ecwid options for WC attributes. Ecwid Options: " . wp_json_encode($item['options']);
                $wc_attributes_for_product_object = []; // This will hold WC_Product_Attribute objects
                $attribute_position = 0;

                foreach ($item['options'] as $ecwid_option) {
                    if (empty($ecwid_option['name']) || !isset($ecwid_option['choices']) || !is_array($ecwid_option['choices'])) {
                        $product_logs[] = "[WARNING] Skipping invalid Ecwid option (missing name or choices): " . wp_json_encode($ecwid_option);
                        continue;
                    }
                    $attribute_name = sanitize_text_field($ecwid_option['name']); // e.g., "Color"
                    $product_logs[] = "Processing Ecwid Option/Attribute: '$attribute_name'";
                    
                    // Generate a WooCommerce-compatible slug first (max 28 characters)
                    $attribute_slug = $this->generate_wc_attribute_slug($attribute_name);
                    $product_logs[] = "Generated attribute slug: '$attribute_slug' (length: " . strlen($attribute_slug) . " chars)";
                    
                    // Generate taxonomy name using the helper function to ensure consistency
                    $taxonomy_name = $this->get_wc_attribute_taxonomy_name($attribute_name);
                    $attribute_id = wc_attribute_taxonomy_id_by_name($attribute_slug); // Check if global attribute exists by slug

                    if (!$attribute_id) { // If global attribute doesn't exist, create it
                        $product_logs[] = "WC Attribute '$attribute_name' (taxonomy '$taxonomy_name') not found. Creating...";
                        
                        $attribute_id = wc_create_attribute([
                            'name'         => $attribute_name, // Human-readable name like "Color"
                            'slug'         => $attribute_slug, // Shortened slug respecting 28-char limit
                            'type'         => 'select', // Default type
                            'order_by'     => 'menu_order',
                            'has_archives' => false
                        ]);
                        if (is_wp_error($attribute_id)) {
                            $product_logs[] = "[ERROR] Failed to create WC Attribute '$attribute_name': " . $attribute_id->get_error_message();
                            continue; // Skip this attribute
                        }
                        $product_logs[] = "WC Attribute '$attribute_name' created with ID: $attribute_id.";
                    } else {
                        $product_logs[] = "Found existing WC Attribute '$attribute_name' (Taxonomy: '$taxonomy_name', Global ID: $attribute_id).";
                    }

                    // Process choices for this attribute (terms)
                    $term_ids_for_this_attribute = [];
                    foreach ($ecwid_option['choices'] as $choice) {
                        $term_name = sanitize_text_field($choice['text']); // e.g., "Red"
                        $term_slug = sanitize_title($term_name); // e.g., "red"
                        
                       
                        
                       
                        
                        $existing_term = get_term_by('slug', $term_slug, $taxonomy_name);
                        if ($existing_term && !is_wp_error($existing_term)) {
                            $term_ids_for_this_attribute[] = $existing_term->term_id;
                            $product_logs[] = "Found existing term '$term_name' (slug: '$term_slug') in '$taxonomy_name' with ID: {$existing_term->term_id}.";
                        } else { // Term does not exist, create it
                            $product_logs[] = "Term '$term_name' (slug: '$term_slug') not found in '$taxonomy_name'. Creating...";
                            $term_result = wp_insert_term($term_name, $taxonomy_name, ['slug' => $term_slug]);
                            if (is_wp_error($term_result)) {
                                $product_logs[] = "[ERROR] Failed to insert term '$term_name' into '$taxonomy_name': " . $term_result->get_error_message();
                            } else {
                                $term_ids_for_this_attribute[] = $term_result['term_id'];
                                $product_logs[] = "Term '$term_name' inserted into '$taxonomy_name' with ID: {$term_result['term_id']}.";
                            }
                        }
                    }

                    // Create WC_Product_Attribute object for the product
                    if (!empty($term_ids_for_this_attribute)) {
                        $wc_attribute_obj = new WC_Product_Attribute();
                        $wc_attribute_obj->set_id($attribute_id); // Global attribute ID (0 if custom attribute, but we use global)
                        $wc_attribute_obj->set_name($taxonomy_name); // Taxonomy name like "pa_color"
                        $wc_attribute_obj->set_options($term_ids_for_this_attribute); // Array of term IDs
                        $wc_attribute_obj->set_position($attribute_position++);
                        $wc_attribute_obj->set_visible(true);  // For product page display
                        $wc_attribute_obj->set_variation(true); // Crucial: Use this attribute for variations
                        $wc_attributes_for_product_object[] = $wc_attribute_obj;
                        $product_logs[] = "Prepared WC_Product_Attribute for '$taxonomy_name' with term IDs: " . implode(', ', $term_ids_for_this_attribute);
                    } else {
                        $product_logs[] = "[WARNING] No terms could be set for attribute '$attribute_name'. It will not be used for variations.";
                    }
                }
                if (!empty($wc_attributes_for_product_object)) {
                    $product->set_attributes($wc_attributes_for_product_object);
                    $product_logs[] = "Parent product attributes set for variations.";
                } else {
                     $product_logs[] = "No attributes were set on the parent product for variations.";
                }
            } elseif ($product->is_type('variable')) { // Is variable but no Ecwid options
                $product_logs[] = "[WARNING] Product is variable type but no Ecwid 'options' found to create attributes. Clearing existing attributes if any.";
                $product->set_attributes([]); // Clear attributes if it's variable but no options from Ecwid
            }

            // --- SAVE PRODUCT (Core, Attributes, Featured Image) ---
            $product_saved_id = $product->save();

            if (!$product_saved_id || is_wp_error($product_saved_id)) {
                 $error_msg = is_wp_error($product_saved_id) ? $product_saved_id->get_error_message() : "Unknown error during product save";
                 $product_logs[] = "[CRITICAL] FAILED to save product (before variations/gallery). Error: $error_msg";
                 return ['status' => 'failed', 'logs' => $product_logs, 'item_name' => $product_name_for_log, 'ecwid_id' => $ecwid_id_for_log, 'sku' => $sku_for_log];
            }
            $product_logs[] = "Product core data, attributes, and featured image saved successfully. WC Product ID: $product_saved_id.";
            
            update_post_meta($product_saved_id, '_ecwid_product_id', $ecwid_id_for_log);
            update_post_meta($product_saved_id, '_ecwid_product_sku_ref', $sku_for_log); // Store SKU as ref
            update_post_meta($product_saved_id, '_ecwid_last_sync_time', current_time('mysql'));

            // Re-parent featured image if it was a new product
            if ($current_product_id_for_image_handling === 0 && $product->get_image_id('edit')) {
                $temp_image_id = $product->get_image_id('edit');
                wp_update_post(['ID' => $temp_image_id, 'post_parent' => $product_saved_id]);
                $product_logs[] = "Re-assigned featured image (ID: $temp_image_id) to newly saved product (ID: $product_saved_id).";
            }

            // --- STALE VARIATION CLEANUP (for existing variable products being updated) ---
            if ($product_id && $product->is_type('variable') && $is_variable_from_ecwid) { // $product_id means it's an update
                $product_logs[] = "Cleaning up stale variations for updated product ID: $product_saved_id.";
                $current_ecwid_combo_ids = array_map(function($combo) { return $combo['id'] ?? null; }, $item['combinations']);
                $current_ecwid_combo_ids = array_filter($current_ecwid_combo_ids);

                $existing_wc_variation_ids = $product->get_children();
                $product_logs[] = "Found " . count($existing_wc_variation_ids) . " existing WC variations. Comparing against " . count($current_ecwid_combo_ids) . " current Ecwid combinations.";
                
                $deleted_variation_count = 0;
                foreach ($existing_wc_variation_ids as $existing_wc_variation_id) {
                    $ecwid_combo_id_meta = get_post_meta($existing_wc_variation_id, '_ecwid_variation_id', true);
                    if ($ecwid_combo_id_meta && !in_array($ecwid_combo_id_meta, $current_ecwid_combo_ids)) {
                        $variation_to_delete = wc_get_product($existing_wc_variation_id);
                        if ($variation_to_delete) {
                            $deleted_sku = $variation_to_delete->get_sku();
                            $variation_to_delete->delete(true);
                            $deleted_variation_count++;
                            $product_logs[] = "Deleted stale WC Variation ID $existing_wc_variation_id (SKU: '$deleted_sku', linked to Ecwid Combo ID: $ecwid_combo_id_meta) as it's not in current Ecwid payload.";
                        }
                    }
                }
                
                // Critical: Clear caches after variation deletion to ensure SKUs are immediately available
                if ($deleted_variation_count > 0) {
                    // Clear WooCommerce product caches
                    clean_product_caches($product_saved_id);
                    
                    // Clear SKU-related caches with targeted clearing (avoid full cache flush)
                    clean_post_cache($product_saved_id);
                    wc_delete_product_transients($product_saved_id);

                    // Force refresh the parent product to ensure children list is updated
                    $product = wc_get_product($product_saved_id);

                    // Small delay to ensure database operations are fully committed
                    usleep(100000); // 100ms delay

                    $product_logs[] = "Cleared caches after deleting $deleted_variation_count stale variations to ensure SKUs are available for reuse.";
                }
            }


            // --- VARIATIONS PROCESSING DEFERRED ---
            // The actual creation/update of variations will be handled by ajax_process_variation_batch
            // We do NOT loop through $item['combinations'] here anymore.

            // --- GALLERY IMAGES (Still process here as it's part of parent product) ---
            if ($product_saved_id && isset($item['galleryImages']) && is_array($item['galleryImages'])) {
                $product_logs[] = "Processing gallery images. Ecwid gallery image count: " . count($item['galleryImages']);
                $product_for_gallery = wc_get_product($product_saved_id); // Ensure we have the latest product state
                $current_wc_gallery_ids = $product_for_gallery ? $product_for_gallery->get_gallery_image_ids('edit') : [];
                $new_gallery_ids_to_set = [];
                $processed_ecwid_gallery_urls = []; // URLs from Ecwid payload that have been processed (either kept or newly added from this payload)
                $ecwid_gallery_image_urls_from_payload = [];
                foreach ($item['galleryImages'] as $gallery_image_data) {
                    $ecwid_gallery_image_urls_from_payload[] = $gallery_image_data['hdThumbnailUrl'] ?? $gallery_image_data['originalImageUrl'] ?? $gallery_image_data['url'] ?? null;
                }
                $ecwid_gallery_image_urls_from_payload = array_filter($ecwid_gallery_image_urls_from_payload);


                // 1. Check existing WC gallery images: keep them if they are still in Ecwid's payload
                foreach($current_wc_gallery_ids as $existing_wc_gallery_image_id) {
                    $source_url_meta = get_post_meta($existing_wc_gallery_image_id, '_ecwid_gallery_image_source_url', true);
                   
                    if ($source_url_meta && in_array($source_url_meta, $ecwid_gallery_image_urls_from_payload)) {
                        $new_gallery_ids_to_set[] = $existing_wc_gallery_image_id; // Keep this image
                        $processed_ecwid_gallery_urls[] = $source_url_meta; // Mark this Ecwid URL as processed
                        $product_logs[] = "Kept existing gallery image ID $existing_wc_gallery_image_id (Source URL: $source_url_meta).";
                    } else {
                        // Image in WC gallery is not in current Ecwid payload (or no source URL meta)
                        // Optionally, delete it from WordPress Media Library if it's no longer in Ecwid.
                        // This is a destructive action, use with caution.
                        // wp_delete_attachment($existing_wc_gallery_image_id, true); // true to force delete
                        // $product_logs[] = "Removed (or would remove) stale WC gallery image ID $existing_wc_gallery_image_id (Source: $source_url_meta) as it's no longer in Ecwid gallery.";
                    }
                }

                // 2. Add new gallery images from Ecwid that aren't already processed
                foreach ($item['galleryImages'] as $gallery_image_data) {
                    $gallery_image_url = $gallery_image_data['hdThumbnailUrl'] ?? $gallery_image_data['originalImageUrl'] ?? $gallery_image_data['url'] ?? null;
                    if ($gallery_image_url && !in_array($gallery_image_url, $processed_ecwid_gallery_urls)) {
                        // Check if this image URL already exists in the media library
                        global $wpdb;
                        // amazonq-ignore-next-line
                        $existing_gallery_attachment = $wpdb->get_var($wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Performance-critical query for finding existing gallery attachments by Ecwid source URL
                            "SELECT post_id FROM {$wpdb->postmeta} 
                            WHERE meta_key = '_ecwid_gallery_image_source_url' 
                            AND meta_value = %s 
                            LIMIT 1",
                            $gallery_image_url
                        ));
                        
                        if ($existing_gallery_attachment) {
                            $new_gallery_ids_to_set[] = $existing_gallery_attachment;
                            $product_logs[] = "Found existing gallery attachment (ID: $existing_gallery_attachment) for URL: $gallery_image_url. Reusing.";
                            $processed_ecwid_gallery_urls[] = $gallery_image_url;
                        } else {
                            $product_logs[] = "Attempting to attach new gallery image from Ecwid: $gallery_image_url";
                            $g_image_id = $this->attach_image_to_product_from_url($gallery_image_url, $product_saved_id, ($item['name'] ?? 'Product') . ' gallery image');
                            
                            if ($g_image_id && !is_wp_error($g_image_id)) {
                                $new_gallery_ids_to_set[] = $g_image_id;
                                update_post_meta($g_image_id, '_ecwid_gallery_image_source_url', esc_url_raw($gallery_image_url));
                                $product_logs[] = "New gallery image attached, WC Attachment ID: $g_image_id.";
                                $processed_ecwid_gallery_urls[] = $gallery_image_url;
                            } else {
                                $gallery_error = is_wp_error($g_image_id) ? $g_image_id->get_error_message() : 'Unknown error attaching gallery image';
                                $product_logs[] = "[WARNING] Failed to attach gallery image ($gallery_image_url). Error: $gallery_error";
                            }
                        }
                    }
                }
                
                // Set the final gallery image IDs on the product
               
                if ($product_for_gallery) {
                    $unique_gallery_ids = array_unique($new_gallery_ids_to_set);
                    $product_for_gallery->set_gallery_image_ids($unique_gallery_ids);
                    $product_for_gallery->save(); // Save the product again to persist gallery changes
                    $product_logs[] = "Gallery images updated. Final WC Attachment IDs: " . (!empty($unique_gallery_ids) ? implode(', ', $unique_gallery_ids) : 'None');
                }
            } elseif ($product_saved_id) { // No gallery images in Ecwid payload
                 $product_for_gallery = wc_get_product($product_saved_id);
                 if ($product_for_gallery && !empty($product_for_gallery->get_gallery_image_ids('edit'))) {
                    // $product_for_gallery->set_gallery_image_ids([]); // Uncomment to clear gallery if Ecwid has none

                    // $product->save();
                    // $product_logs[] = "Cleared existing WC gallery images as Ecwid product has no gallery images.";
                 }
            }

            // --- FINAL STATUS DETERMINATION ---
            if ($is_variable_from_ecwid) {
                $total_combinations = count($item['combinations'] ?? []);
                if ($total_combinations > 0) {
                    $product_logs[] = "Parent product (ID: $product_saved_id) processed. $total_combinations variations pending batch import.";
                    return [
                       
                        'status' => 'imported_parent_pending_variations',
                        'logs' => $product_logs,
                        'item_name' => $product_name_for_log,
                        'ecwid_id' => $ecwid_id_for_log,
                        'sku' => $sku_for_log,
                        'wc_product_id' => $product_saved_id,
                        'is_variable' => true,
                        'total_combinations' => $total_combinations,
                        'all_combinations' => $item['combinations'] ?? [], // Ensure this is the raw Ecwid combinations data
                        'original_options' => $item['options'] ?? []    // Ensure this is the raw Ecwid options data
                    ];
                } else {
                     $product_logs[] = "Product was marked as variable from Ecwid options, but no actual combinations found. Treated as simple/variable shell.";
                     // Fall through to 'imported' status as if it were simple, or if it's a variable shell without variations.
                }
            }
            
            $product_logs[] = "Successfully processed $log_product_identifier (as simple or variable shell without pending variations).";
            return ['status' => 'imported', 'logs' => $product_logs, 'item_name' => $product_name_for_log, 'ecwid_id' => $ecwid_id_for_log, 'sku' => $sku_for_log, 'wc_product_id' => $product_saved_id];

        } catch (WC_Data_Exception $e) { // Catch WooCommerce specific data exceptions
            $product_logs[] = "[CRITICAL WC_Data_Exception] During product import: " . $e->getMessage() . " Error Code: " . $e->getErrorCode();
            error_log("Ecwid Sync: WC_Data_Exception for $log_product_identifier: " . $e->getMessage() . " Trace: " . $e->getTraceAsString()); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Critical error logging for WooCommerce data exceptions
            return ['status' => 'failed', 'logs' => $product_logs, 'item_name' => $product_name_for_log, 'ecwid_id' => $ecwid_id_for_log, 'sku' => $sku_for_log];
        } catch (Exception $e) { // Catch any other general exceptions
            $product_logs[] = "[CRITICAL PHP Exception] During product import: " . $e->getMessage() . " on line " . $e->getLine() . " in " . $e->getFile();
            error_log("Ecwid Sync: PHP Exception for $log_product_identifier: " . $e->getMessage() . " Trace: " . $e->getTraceAsString()); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Critical error logging for PHP exceptions
            return ['status' => 'failed', 'logs' => $product_logs, 'item_name' => $product_name_for_log, 'ecwid_id' => $ecwid_id_for_log, 'sku' => $sku_for_log];
        }
    }

    public function ajax_process_variation_batch() {
        check_ajax_referer('ecwid_wc_sync_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized', 'metrotechs-e2w-sync')]);
            return;
        }
        
        // Enhanced resource management for variation processing
        set_time_limit(300); // Cap at 5 minutes to avoid locking a PHP worker indefinitely on shared hosting
        wp_raise_memory_limit('admin'); // WordPress way to increase memory for admin operations
        
        // Wrap entire function in try-catch for better error handling
        try {

        $wc_product_id = isset($_POST['wc_product_id']) ? intval($_POST['wc_product_id']) : 0;
        $ecwid_product_id_for_log = isset($_POST['ecwid_product_id']) ? intval($_POST['ecwid_product_id']) : 0; // For logging context
        $item_name_for_log = isset($_POST['item_name']) ? sanitize_text_field(wp_unslash($_POST['item_name'])) : 'N/A';
        $sku_for_log = isset($_POST['sku']) ? sanitize_text_field(wp_unslash($_POST['sku'])) : 'N/A';
        
        $combinations_batch_json = isset($_POST['combinations_batch_json']) ? sanitize_textarea_field(wp_unslash($_POST['combinations_batch_json'])) : '[]';
        $combinations_batch = json_decode($combinations_batch_json, true);

        $original_ecwid_options_json = isset($_POST['original_ecwid_options_json']) ? sanitize_textarea_field(wp_unslash($_POST['original_ecwid_options_json'])) : '[]';
        $original_ecwid_options = json_decode($original_ecwid_options_json, true);


        $batch_logs = [];

        if (empty($wc_product_id) || !$combinations_batch) {
            wp_send_json_error([
                'message' => __('Missing WC Product ID or combinations batch for variation processing.', 'metrotechs-e2w-sync'),
                'logs' => ['[CRITICAL] WC Product ID or combinations_batch_json was empty.']
            ]);
            return;
        }

        $parent_product = wc_get_product($wc_product_id);

        if (!$parent_product) {
            wp_send_json_error([
                // translators: %s is the WooCommerce product ID
                'message' => sprintf(__('Could not load parent WC Product ID %s for variation processing.', 'metrotechs-e2w-sync'), $wc_product_id),
                'logs' => ["[CRITICAL] Parent product WC ID: $wc_product_id not found."]
            ]);
            return;
        }
        if (!$parent_product->is_type('variable')) {
             wp_send_json_error([
                // translators: %s is the WooCommerce product ID
                'message' => sprintf(__('Parent WC Product ID %s is not a variable product type.', 'metrotechs-e2w-sync'), $wc_product_id),
                'logs' => ["[CRITICAL] Parent product WC ID: $wc_product_id is not variable type."]
            ]);
            return;
        }

        $result = $this->_process_product_variations_batch($parent_product, $combinations_batch, $original_ecwid_options, $batch_logs, $ecwid_product_id_for_log);
        
        // Sync parent product price/stock status after each batch
        // This might be intensive if done every small batch, consider doing it only on the last batch in JS.
        // For now, let's do it to ensure data consistency.
        $parent_product->get_data_store()->sync_variation_prices($parent_product->get_id());
        // WC_Product_Variable_Data_Store_CPT::sync_stock_status requires product object, not ID.
        // $data_store = $parent_product->get_data_store();
        // if (method_exists($data_store, 'sync_stock_status')) { // Check if method exists
        //    $data_store->sync_stock_status($parent_product->get_id()); // This might be handled by WC automatically on variation save.
        // }
        // $parent_product->save(); // Re-save parent to update price ranges and potentially stock status.
        // $batch_logs[] = "[INFO] Parent product (ID: {$parent_product->get_id()}) prices/stock status synced after batch.";


        wp_send_json_success([
            'status' => 'success',
            // translators: %1$d is the number of variations processed, %2$s is the product name, %3$s is the SKU
            'message' => sprintf(__('Processed %1$d variations in this batch for %2$s (SKU: %3$s).', 'metrotechs-e2w-sync'), count($combinations_batch), $item_name_for_log, $sku_for_log),
            'batch_logs' => $batch_logs,
            'processed_in_batch' => count($combinations_batch),
            'failed_in_batch' => $result['failed_count'] ?? 0,
        ]);
        
        } catch (Error $e) {
            // Handle fatal errors (PHP 7+)
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Ecwid Sync: Fatal Error in ajax_process_variation_batch: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine()); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging wrapped in WP_DEBUG check
            }
            wp_send_json_error([
                'message' => __('A critical error occurred during variation processing. Please check your server error logs or try again with a smaller batch size.', 'metrotechs-e2w-sync'),
                'error_type' => 'fatal_error',
                'error_details' => WP_DEBUG ? $e->getMessage() : 'Enable WP_DEBUG for details'
            ]);
        } catch (Exception $e) {
            // Handle regular exceptions
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Ecwid Sync: Exception in ajax_process_variation_batch: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine()); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging wrapped in WP_DEBUG check
            }
            wp_send_json_error([
                'message' => __('An error occurred during variation processing: ', 'metrotechs-e2w-sync') . $e->getMessage(),
                'error_type' => 'exception',
                'error_details' => WP_DEBUG ? $e->getTraceAsString() : 'Enable WP_DEBUG for details'
            ]);
        }
    }

    /**
     * Generate a unique SKU for a variation, handling conflicts and duplicates
     */
    private function generate_unique_variation_sku($desired_sku, $existing_variation_id = 0, $ecwid_combination_id = '', &$batch_logs = []) {
        // Clean the desired SKU
        $base_sku = sanitize_text_field(trim($desired_sku));
        
        // If empty, generate a fallback
        if (empty($base_sku)) {
            $base_sku = 'var-' . $ecwid_combination_id . '-' . time();
            $batch_logs[] = "[INFO] Empty SKU provided, generated fallback: $base_sku";
        }
        
        // Check if the desired SKU is already available
        $sku_product_id = wc_get_product_id_by_sku($base_sku);
        
        // If SKU is free, or if it belongs to the current variation being updated, use it
        if (!$sku_product_id || ($existing_variation_id && $sku_product_id == $existing_variation_id)) {
            $batch_logs[] = "[INFO] SKU '$base_sku' is available for variation.";
            return $base_sku;
        }
        
        $batch_logs[] = "[WARNING] SKU '$base_sku' is already in use by product/variation ID $sku_product_id. Generating unique alternative.";
        
        // SKU is taken, need to generate a unique one
        $counter = 1;
        $unique_sku = $base_sku;
        
        while ($counter <= 100) { // Prevent infinite loops
            $unique_sku = $base_sku . '-' . $counter;
            $conflict_id = wc_get_product_id_by_sku($unique_sku);
            
            if (!$conflict_id || ($existing_variation_id && $conflict_id == $existing_variation_id)) {
                $batch_logs[] = "[INFO] Generated unique SKU: '$unique_sku' for Ecwid combination $ecwid_combination_id";
                return $unique_sku;
            }
            
            $counter++;
        }
        
        // If we still haven't found a unique SKU after 100 attempts, use timestamp-based fallback
        $fallback_sku = $base_sku . '-' . time() . '-' . wp_rand(100, 999);
        $batch_logs[] = "[WARNING] Could not generate unique SKU after 100 attempts. Using timestamp-based fallback: '$fallback_sku'";
        
        return $fallback_sku;
    }

    private function _process_product_variations_batch(WC_Product_Variable $parent_product, array $combinations_slice, array $original_ecwid_options, array &$batch_logs, $ecwid_product_id_for_log) {
        $processed_count = 0;
        $failed_count = 0;
        $parent_product_id = $parent_product->get_id();
        $parent_sku = $parent_product->get_sku();

        $batch_logs[] = "[INFO] Starting variation batch processing for Parent WC Product ID: $parent_product_id (Ecwid ID: $ecwid_product_id_for_log). Batch size: " . count($combinations_slice);

        foreach ($combinations_slice as $combo_idx => $combo) {
            if (!isset($combo['id'])) {
                $batch_logs[] = "[WARNING] Skipping Ecwid combination at index $combo_idx in batch: missing 'id'. Data: " . wp_json_encode($combo);
                $failed_count++;
                continue;
            }
            $ecwid_combination_id = $combo['id'];
            $batch_logs[] = "--- Processing Ecwid Combination ID: $ecwid_combination_id (Parent SKU: $parent_sku) ---";
            
            $batch_logs[] = "Raw Ecwid Combo Data (ID $ecwid_combination_id) for Prices: " . wp_json_encode([
                'price_field_check' => $combo['price'] ?? 'NOT_SET', 
                'sale_price_field_check' => $combo['compareToPrice'] ?? 'NOT_SET',
                'defaultDisplayedPrice' => $combo['defaultDisplayedPrice'] ?? 'NOT_SET',
                'defaultDisplayedCompareToPrice' => $combo['defaultDisplayedCompareToPrice'] ?? 'NOT_SET',
            ]);
            
            $batch_logs[] = "Raw Ecwid Combo Data (ID $ecwid_combination_id) for SKU: " . wp_json_encode([
                'sku_field_check' => $combo['sku'] ?? 'NOT_SET',
                'all_combo_keys' => array_keys($combo),
            ]);

            $variation_attributes_for_wc = [];
            if (isset($combo['options']) && is_array($combo['options'])) {
                foreach ($combo['options'] as $combo_opt_val) {
                    if (empty($combo_opt_val['name']) || !isset($combo_opt_val['value'])) {
                         $batch_logs[] = "[WARNING] Skipping invalid option in combination $ecwid_combination_id (missing name or value): " . wp_json_encode($combo_opt_val);
                         continue;
                    }
                    $parent_attribute_name = sanitize_text_field($combo_opt_val['name']);
                    $wc_attr_taxonomy_slug = $this->get_wc_attribute_taxonomy_name($parent_attribute_name);
                    $term_value_from_ecwid = sanitize_text_field($combo_opt_val['value']);

                    $term_object = get_term_by('name', $term_value_from_ecwid, $wc_attr_taxonomy_slug);
                    
                    // IMPROVED: Enhanced term creation with better error handling
                    if (!$term_object || is_wp_error($term_object)) {
                        $batch_logs[] = "Term '$term_value_from_ecwid' not found in '$wc_attr_taxonomy_slug'. Creating it now...";
                        
                        // Check if term exists by slug as well
                        $term_slug = sanitize_title($term_value_from_ecwid);
                        $term_by_slug = get_term_by('slug', $term_slug, $wc_attr_taxonomy_slug);
                        
                        if ($term_by_slug && !is_wp_error($term_by_slug)) {
                            $term_object = $term_by_slug;
                            $batch_logs[] = "Found existing term by slug: '$term_slug' for attribute '$wc_attr_taxonomy_slug'.";
                        } else {
                            // Try to create the term with error handling
                            $term_result = wp_insert_term($term_value_from_ecwid, $wc_attr_taxonomy_slug, ['slug' => $term_slug]);
                            
                            if (is_wp_error($term_result)) {
                                // If term already exists (common in concurrent operations), try to get it
                                if ($term_result->get_error_code() === 'term_exists') {
                                    $existing_term_id = $term_result->get_error_data();
                                    $term_object = get_term_by('id', $existing_term_id, $wc_attr_taxonomy_slug);
                                    $batch_logs[] = "Term '$term_value_from_ecwid' already exists with ID $existing_term_id for attribute '$wc_attr_taxonomy_slug'.";
                                } else {
                                    $batch_logs[] = "[ERROR] Failed to create term '$term_value_from_ecwid' for attribute '$wc_attr_taxonomy_slug': " . $term_result->get_error_message();
                                    // Try one more time to get the term in case it was created by another process
                                    $term_object = get_term_by('name', $term_value_from_ecwid, $wc_attr_taxonomy_slug);
                                }
                            } else {
                                // Successfully created term
                                $term_object = get_term_by('id', $term_result['term_id'], $wc_attr_taxonomy_slug);
                                $batch_logs[] = "Successfully created term '$term_value_from_ecwid' with ID {$term_result['term_id']} for attribute '$wc_attr_taxonomy_slug'.";
                            }
                        }
                    }
                    
                    if ($term_object && !is_wp_error($term_object)) {
                        $variation_attributes_for_wc[$wc_attr_taxonomy_slug] = $term_object->slug;
                        $batch_logs[] = "For combo $ecwid_combination_id, attribute '$wc_attr_taxonomy_slug' mapped to term '{$term_object->name}' (slug: '{$term_object->slug}').";
                    } else {
                        $batch_logs[] = "[ERROR] For combo $ecwid_combination_id, WC term for value '$term_value_from_ecwid' of attribute '$wc_attr_taxonomy_slug' NOT FOUND. This variation may not link correctly.";
                    }
                }
            } else {
                 $batch_logs[] = "[WARNING] No 'options' array found in Ecwid combination ID $ecwid_combination_id to map to variation attributes.";
            }
            
            if (empty($variation_attributes_for_wc) && !empty($original_ecwid_options)) {
                $batch_logs[] = "[ERROR] Could not map any attributes for variation (Ecwid Combo ID: $ecwid_combination_id). Skipping this variation.";
                $failed_count++;
                continue; 
            }

            $variation_id = 0;
            $should_skip_variation = false; // Flag to control whether to skip this variation
            
            $existing_vars_query = new WP_Query([
                'post_type' => 'product_variation', 'post_status' => 'any',
                'post_parent' => $parent_product_id,
                // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Meta query required to find existing variations by Ecwid variation ID
                'meta_query' => [[ 'key' => '_ecwid_variation_id', 'value' => $ecwid_combination_id ]],
                'posts_per_page' => 1, 'fields' => 'ids'
            ]);
            
            if ($existing_vars_query->have_posts()) {
                $variation_id = $existing_vars_query->posts[0];
                $batch_logs[] = "Found existing WC Variation ID $variation_id for Ecwid Combo ID $ecwid_combination_id.";
                // Ensure the meta is up to date (in case of any inconsistency)
                update_post_meta($variation_id, '_ecwid_variation_id', $ecwid_combination_id);
            } else {
                // Check if a variation with the desired SKU already exists
                $desired_sku = $combo['sku'] ?? ($parent_sku . '-combo-' . $ecwid_combination_id);
                $desired_sku = sanitize_text_field(trim($desired_sku));
                
                if (!empty($desired_sku)) {
                    $existing_product_id_by_sku = wc_get_product_id_by_sku($desired_sku);
                    
                    if ($existing_product_id_by_sku) {
                        $existing_product = wc_get_product($existing_product_id_by_sku);
                        
                        // Check if it's a variation of the current parent product
                        if ($existing_product && $existing_product->is_type('variation') && $existing_product->get_parent_id() == $parent_product_id) {
                            $variation_id = $existing_product_id_by_sku;
                            // Link this existing variation to the Ecwid combination ID
                            update_post_meta($variation_id, '_ecwid_variation_id', $ecwid_combination_id);
                            $batch_logs[] = "Found existing WC Variation ID $variation_id with SKU '$desired_sku'. Linked to Ecwid Combo ID $ecwid_combination_id.";
                        } elseif ($existing_product && $existing_product->is_type('variation') && $existing_product->get_parent_id() != $parent_product_id) {
                            // Variation exists but belongs to different product - skip this combination
                            $batch_logs[] = "[SKIPPED] Variation with SKU '$desired_sku' already exists under different product (ID: {$existing_product->get_parent_id()}). Skipping Ecwid Combo ID $ecwid_combination_id.";
                            $should_skip_variation = true;
                            $failed_count++;
                        } elseif ($existing_product && !$existing_product->is_type('variation')) {
                            // SKU belongs to a main product, not a variation - skip this combination
                            $batch_logs[] = "[SKIPPED] SKU '$desired_sku' already used by main product ID $existing_product_id_by_sku. Skipping Ecwid Combo ID $ecwid_combination_id.";
                            $should_skip_variation = true;
                            $failed_count++;
                        }
                    }
                }
                
                if (!$variation_id && !$should_skip_variation) {
                    $batch_logs[] = "No existing WC Variation for Ecwid Combo ID $ecwid_combination_id. Creating new.";
                }
            }
            
            // Skip processing if we determined this variation should be skipped
            if ($should_skip_variation) {
                $batch_logs[] = "--- Finished Ecwid Combination ID: $ecwid_combination_id (SKIPPED) ---";
                continue;
            }

            $variation = $variation_id ? new WC_Product_Variation($variation_id) : new WC_Product_Variation();
            $variation->set_parent_id($parent_product_id);
            $variation->set_attributes($variation_attributes_for_wc);

            // Enhanced SKU handling with conflict resolution
            $desired_sku = $combo['sku'] ?? ($parent_sku . '-combo-' . $ecwid_combination_id);
            
            if ($variation_id) {
                // For existing variations, use the desired SKU directly since we've already resolved conflicts above
                $final_sku = sanitize_text_field(trim($desired_sku));
                $batch_logs[] = "[INFO] Using desired SKU '$final_sku' for existing variation ID $variation_id.";
            } else {
                // For new variations, generate unique SKU if needed
                $final_sku = $this->generate_unique_variation_sku($desired_sku, 0, $ecwid_combination_id, $batch_logs);
            }
            
            $variation->set_sku(sanitize_text_field($final_sku));
            
            $combo_regular_price_to_set = null;
            if (isset($combo['defaultDisplayedPrice']) && is_numeric($combo['defaultDisplayedPrice'])) {
                $combo_regular_price_to_set = $combo['defaultDisplayedPrice'];
            } elseif (isset($combo['price']) && is_numeric($combo['price'])) {
                $combo_regular_price_to_set = $combo['price'];
            }
            $final_regular_price = $combo_regular_price_to_set ?? $parent_product->get_regular_price('edit') ?? '0';
            $variation->set_regular_price(strval($final_regular_price));
            $batch_logs[] = "Variation regular price set to: {$final_regular_price}.";

            $combo_sale_price_to_set = null;
            if (isset($combo['defaultDisplayedCompareToPrice']) && is_numeric($combo['defaultDisplayedCompareToPrice'])) {
                $combo_sale_price_to_set = $combo['defaultDisplayedCompareToPrice'];
            } elseif (isset($combo['compareToPrice']) && is_numeric($combo['compareToPrice'])) {
                $combo_sale_price_to_set = $combo['compareToPrice'];
            }
            $parent_sale_price = $parent_product->get_sale_price('edit');
            $final_sale_price = $combo_sale_price_to_set ?? $parent_sale_price;

            if ($final_sale_price !== '' && $final_sale_price !== null) {
                if (is_numeric($final_regular_price) && is_numeric($final_sale_price) && floatval($final_sale_price) < floatval($final_regular_price)) {
                    $variation->set_sale_price(strval($final_sale_price));
                    $batch_logs[] = "Variation sale price set to: {$final_sale_price}.";
                } else {
                    $variation->set_sale_price('');
                    $batch_logs[] = "Sale price ({$final_sale_price}) not set for variation (not less than regular or invalid).";
                }
            } else {
                $variation->set_sale_price('');
                $batch_logs[] = "No sale price for variation.";
            }
            
            $variation->set_weight(wc_format_decimal($combo['weight'] ?? $parent_product->get_weight('edit') ?? ''));
            // Stock for variations (Example, adjust as per your Ecwid data for combinations)
            if (isset($combo['quantity'])) {
                $variation->set_manage_stock(true);
                $variation->set_stock_quantity(intval($combo['quantity']));
                $variation->set_stock_status(intval($combo['quantity']) > 0 ? 'instock' : 'outofstock');
            } elseif (isset($combo['unlimited']) && $combo['unlimited']) {
                $variation->set_manage_stock(false);
                $variation->set_stock_quantity(null);
                $variation->set_stock_status('instock');
            } else { // Default if no specific stock info for combo
                $variation->set_manage_stock(false); // Or true and outofstock if that's preferred
                $variation->set_stock_quantity(null);
                $variation->set_stock_status('outofstock'); // Default to out of stock if not specified
            }

            $variation->set_status('publish'); 

            // Additional SKU validation before saving to prevent WooCommerce exceptions
            $final_sku_for_validation = $variation->get_sku();
            
            // Enhanced SKU validation
            if (empty($final_sku_for_validation) || trim($final_sku_for_validation) === '') {
                // Generate emergency SKU for empty SKUs
                $emergency_sku = 'auto-' . $ecwid_combination_id . '-' . time() . '-' . wp_rand(100, 999);
                $variation->set_sku($emergency_sku);
                $batch_logs[] = "[INFO] Empty SKU detected, set auto-generated SKU '$emergency_sku' for Ecwid Combo ID $ecwid_combination_id.";
                $final_sku_for_validation = $emergency_sku;
            }
            
            // Check for SKU conflicts with more comprehensive validation
            $sku_conflict_check = wc_get_product_id_by_sku($final_sku_for_validation);
            if ($sku_conflict_check && $sku_conflict_check != $variation_id) {
                $conflicting_product = wc_get_product($sku_conflict_check);
                if ($conflicting_product) {
                    if ($conflicting_product->is_type('variation')) {
                        $batch_logs[] = "[WARNING] SKU '$final_sku_for_validation' conflicts with existing variation ID $sku_conflict_check (Parent: {$conflicting_product->get_parent_id()}). Generating emergency SKU.";
                    } else {
                        $batch_logs[] = "[WARNING] SKU '$final_sku_for_validation' conflicts with existing product ID $sku_conflict_check. Generating emergency SKU.";
                    }
                } else {
                    $batch_logs[] = "[WARNING] SKU '$final_sku_for_validation' conflicts with product/variation ID $sku_conflict_check. Generating emergency SKU.";
                }
                
                $emergency_sku = 'conflict-' . $ecwid_combination_id . '-' . time() . '-' . wp_rand(100, 999);
                $variation->set_sku($emergency_sku);
                $batch_logs[] = "[INFO] Set emergency SKU '$emergency_sku' for Ecwid Combo ID $ecwid_combination_id to resolve conflict.";
            }
            
            // Final SKU validation before save attempt
            $final_sku_to_save = $variation->get_sku();
            if (empty($final_sku_to_save) || strlen(trim($final_sku_to_save)) === 0) {
                $ultimate_emergency_sku = 'ultimate-' . $ecwid_combination_id . '-' . time();
                $variation->set_sku($ultimate_emergency_sku);
                $batch_logs[] = "[CRITICAL] Final SKU validation failed, using ultimate emergency SKU '$ultimate_emergency_sku'.";
            }

            try {
                $var_saved_id = $variation->save();
                if ($var_saved_id && !is_wp_error($var_saved_id)) {
                    update_post_meta($var_saved_id, '_ecwid_variation_id', $ecwid_combination_id);
                    $actual_saved_sku = $variation->get_sku();
                    $batch_logs[] = "Saved WC Variation ID $var_saved_id (Ecwid Combo ID: $ecwid_combination_id). SKU: '$actual_saved_sku'. Attributes: " . wp_json_encode($variation_attributes_for_wc);
                    $processed_count++;
                } else {
                    $var_error_msg = is_wp_error($var_saved_id) ? $var_saved_id->get_error_message() : "Unknown error saving variation";
                    $attempted_sku = $variation->get_sku();
                    $batch_logs[] = "[ERROR] Failed to save WC Variation for Ecwid Combo ID $ecwid_combination_id. Error: $var_error_msg. SKU attempted: '$attempted_sku'";
                    $failed_count++;
                }
            } catch (WC_Data_Exception $e) {
                // Handle WooCommerce-specific data exceptions (including SKU conflicts)
                $error_msg = $e->getMessage();
                $attempted_sku = $variation->get_sku();
                $batch_logs[] = "[ERROR] WC_Data_Exception while saving variation for Ecwid Combo ID $ecwid_combination_id: $error_msg. SKU attempted: '$attempted_sku'";
                
                // Try emergency SKU recovery for WooCommerce data exceptions
                if (strpos(strtolower($error_msg), 'sku') !== false || strpos(strtolower($error_msg), 'duplicate') !== false) {
                    $batch_logs[] = "[INFO] Attempting emergency SKU recovery for WooCommerce data exception...";
                    try {
                        $emergency_sku = 'wc-emergency-' . $ecwid_combination_id . '-' . time() . '-' . wp_rand(100, 999);
                        $variation->set_sku($emergency_sku);
                        $var_saved_id = $variation->save();
                        
                        if ($var_saved_id && !is_wp_error($var_saved_id)) {
                            update_post_meta($var_saved_id, '_ecwid_variation_id', $ecwid_combination_id);
                            $batch_logs[] = "[SUCCESS] Emergency recovery successful with SKU '$emergency_sku' for Ecwid Combo ID $ecwid_combination_id";
                            $processed_count++;
                        } else {
                            $batch_logs[] = "[ERROR] Emergency recovery also failed for Ecwid Combo ID $ecwid_combination_id";
                            $failed_count++;
                        }
                    } catch (Exception $e2) {
                        $batch_logs[] = "[ERROR] Emergency recovery exception for Ecwid Combo ID $ecwid_combination_id: " . $e2->getMessage();
                        $failed_count++;
                    }
                } else {
                    $batch_logs[] = "[ERROR] Non-SKU WooCommerce data exception, cannot recover for Ecwid Combo ID $ecwid_combination_id";
                    $failed_count++;
                }
            } catch (Exception $e) {
                $error_msg = $e->getMessage();
                $attempted_sku = $variation->get_sku();
                $batch_logs[] = "[ERROR] Exception while saving variation for Ecwid Combo ID $ecwid_combination_id: $error_msg. SKU attempted: '$attempted_sku'";
                
                // Handle specific SKU conflict errors
                if (strpos($error_msg, 'SKU') !== false || strpos($error_msg, 'sku') !== false || strpos(strtolower($error_msg), 'duplicate') !== false) {
                    $batch_logs[] = "[INFO] Attempting to resolve SKU conflict by generating new unique SKU...";
                    try {
                        // Generate a completely new unique SKU
                        $emergency_sku = 'exception-' . $ecwid_combination_id . '-' . time() . '-' . wp_rand(100, 999);
                        $variation->set_sku($emergency_sku);
                        $var_saved_id = $variation->save();
                        
                        if ($var_saved_id && !is_wp_error($var_saved_id)) {
                            update_post_meta($var_saved_id, '_ecwid_variation_id', $ecwid_combination_id);
                            $batch_logs[] = "[SUCCESS] Exception recovery successful with emergency SKU '$emergency_sku' for Ecwid Combo ID $ecwid_combination_id";
                            $processed_count++;
                        } else {
                            $batch_logs[] = "[ERROR] Exception emergency SKU save also failed for Ecwid Combo ID $ecwid_combination_id";
                            $failed_count++;
                        }
                    } catch (Exception $e2) {
                        $batch_logs[] = "[ERROR] Exception emergency SKU save exception for Ecwid Combo ID $ecwid_combination_id: " . $e2->getMessage();
                        $failed_count++;
                    }
                } else {
                    $batch_logs[] = "[ERROR] Non-SKU related exception, cannot auto-recover for Ecwid Combo ID $ecwid_combination_id";
                    $failed_count++;
                }
            }
            $batch_logs[] = "--- Finished Ecwid Combination ID: $ecwid_combination_id ---";
        }
        $batch_logs[] = "[INFO] Variation batch complete. Processed: $processed_count, Failed: $failed_count.";
        return ['processed_count' => $processed_count, 'failed_count' => $failed_count];
    }

    private function register_missing_parent($parent_ecwid_id, $child_ecwid_id) {
        $missing_parents = get_option('ecwid_wc_sync_missing_parents', []);
        if (!isset($missing_parents[$parent_ecwid_id])) {
            $missing_parents[$parent_ecwid_id] = [];
        }
        $missing_parents[$parent_ecwid_id][] = $child_ecwid_id;
        update_option('ecwid_wc_sync_missing_parents', $missing_parents);
    }

    /**
     * Sort categories so parents are imported before children (topological sort)
     * This prevents "Parent category not found" warnings by ensuring parent categories
     * are always processed before their children within the same batch.
     * 
     * @param array $categories Array of category data from Ecwid API
     * @return array Sorted array with parents before children
     */
    private function sort_categories_parents_first($categories) {
        if (empty($categories)) {
            return $categories;
        }

        // Build lookup maps
        $categories_by_id = [];
        $children_of = []; // parentId => [child_ids]
        $root_categories = [];

        foreach ($categories as $cat) {
            $id = $cat['id'] ?? null;
            if (!$id) continue;
            
            $categories_by_id[$id] = $cat;
            $parent_id = isset($cat['parentId']) && intval($cat['parentId']) > 0 ? intval($cat['parentId']) : 0;
            
            if ($parent_id === 0) {
                $root_categories[] = $id;
            } else {
                if (!isset($children_of[$parent_id])) {
                    $children_of[$parent_id] = [];
                }
                $children_of[$parent_id][] = $id;
            }
        }

        // Build sorted list using BFS (breadth-first) to ensure parents come before children
        $sorted = [];
        $queue = $root_categories;
        $processed = [];

        while (!empty($queue)) {
            $current_id = array_shift($queue);
            
            // Skip if already processed (prevent infinite loops)
            if (isset($processed[$current_id])) {
                continue;
            }
            
            // Check if parent is in this batch but not yet processed
            $cat = $categories_by_id[$current_id] ?? null;
            if ($cat) {
                $parent_id = isset($cat['parentId']) && intval($cat['parentId']) > 0 ? intval($cat['parentId']) : 0;
                
                // If parent is in this batch and not yet processed, defer this item
                if ($parent_id > 0 && isset($categories_by_id[$parent_id]) && !isset($processed[$parent_id])) {
                    // Push to end of queue to process after parent
                    $queue[] = $current_id;
                    continue;
                }
                
                $sorted[] = $cat;
                $processed[$current_id] = true;
                
                // Add children to queue
                if (isset($children_of[$current_id])) {
                    foreach ($children_of[$current_id] as $child_id) {
                        if (!isset($processed[$child_id])) {
                            $queue[] = $child_id;
                        }
                    }
                }
            }
        }

        // Add any categories that weren't processed (orphans with parents not in this batch)
        foreach ($categories as $cat) {
            $id = $cat['id'] ?? null;
            if ($id && !isset($processed[$id])) {
                $sorted[] = $cat;
            }
        }

        if (defined('WP_DEBUG') && WP_DEBUG && count($sorted) !== count($categories)) {
            // amazonq-ignore-next-line
            error_log("Ecwid Sync: Category sort - Input: " . count($categories) . ", Output: " . count($sorted)); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
        }

        return $sorted;
    }

    /**
     * Attempts to fetch and import a missing parent category from Ecwid API
     * 
     * @param int $parent_ecwid_id The Ecwid ID of the missing parent category
     * @return array|null Returns category data if successfully fetched and imported, null otherwise
     */
    private function fetch_and_import_missing_parent($parent_ecwid_id) {
        // Get API credentials
        $store_id = get_option('ecwid_wc_store_id');
        $api_token = get_option('ecwid_wc_api_token');
        
        if (empty($store_id) || empty($api_token)) {
            return null;
        }

        // Fetch the specific category from Ecwid API
        $api_url = "https://app.ecwid.com/api/v3/{$store_id}/categories/{$parent_ecwid_id}";
        
        $response = $this->make_api_request_with_retry($api_url, $api_token, 'GET', 3);
        
        if (is_wp_error($response)) {
            $this->log_message("Failed to fetch missing parent category {$parent_ecwid_id}: " . $response->get_error_message(), 'error');
            return null;
        }
        
        $http_code = wp_remote_retrieve_response_code($response);
        if ($http_code !== 200) {
            $this->log_message("Failed to fetch missing parent category {$parent_ecwid_id}: HTTP {$http_code}", 'error');
            return null;
        }
        
        $body = wp_remote_retrieve_body($response);
        $category_data = json_decode($body, true);
        
        if (!$category_data || !isset($category_data['id'])) {
            $this->log_message("Invalid category data received for missing parent {$parent_ecwid_id}", 'error');
            return null;
        }
        
        // Import the fetched category
        $import_result = $this->import_single_category($category_data);
        
        if ($import_result && isset($import_result['term_id'])) {
            $this->log_message("Successfully fetched and imported missing parent category {$parent_ecwid_id} as WC Term ID {$import_result['term_id']}", 'info');
            return $import_result;
        }
        
        return null;
    }

    private function get_or_create_missing_parent_placeholder($parent_ecwid_id) {
        // Fall back to creating a placeholder for missing parent categories
        $existing_term_query = new WP_Query([
            'post_type' => 'ecwid_placeholder', // Query the CPT
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Meta query required to find existing placeholder by Ecwid parent ID
            'meta_key' => '_ecwid_placeholder_parent_id',
            'meta_value' => $parent_ecwid_id, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
            'posts_per_page' => 1,
            'fields' => 'ids' // Only get IDs
        ]);

        if (!empty($existing_term_query->posts)) {
            $placeholder_post_id = $existing_term_query->posts[0];
            return [
                'term_id' => get_post_meta($placeholder_post_id, '_ecwid_placeholder_term_id', true),
                'name' => get_the_title($placeholder_post_id),
                'is_new' => false
            ];
        }

        // translators: %s is the Ecwid category ID
        $placeholder_name = sprintf(__('Missing Category %s', 'metrotechs-e2w-sync'), $parent_ecwid_id);

        $term_result = wp_insert_term($placeholder_name, 'product_cat', [
            // translators: %s is the Ecwid category ID
            'description' => sprintf(__('Automatically created placeholder for missing Ecwid category ID %s', 'metrotechs-e2w-sync'), $parent_ecwid_id)
        ]);

        if (is_wp_error($term_result)) {
            return null;
        }

        $placeholder_post = wp_insert_post([
            'post_title' => $placeholder_name,
            'post_status' => 'private',
            'post_type' => 'ecwid_placeholder'
        ]);

        if ($placeholder_post && !is_wp_error($placeholder_post)) {
            update_post_meta($placeholder_post, '_ecwid_placeholder_parent_id', $parent_ecwid_id);
            update_post_meta($placeholder_post, '_ecwid_placeholder_term_id', $term_result['term_id']);
            update_term_meta($term_result['term_id'], '_ecwid_placeholder_category', '1');
        }

        return [
            'term_id' => $term_result['term_id'],
            'name' => $placeholder_name,
            'is_new' => true
        ];
    }

    /**
     * After creating/updating a real parent category, move any children from the
     * placeholder term ("Missing Category {EcwidID}") to the real parent and clean up.
     *
     * @param int|string $parent_ecwid_id Ecwid parent category ID
     * @param int $real_parent_term_id WooCommerce term ID of the real parent
     * @param array &$logs Log array to append messages
     */
    private function reconcile_children_after_parent_import($parent_ecwid_id, $real_parent_term_id, &$logs) {
        // Find placeholder post by Ecwid parent ID
        $q = new WP_Query([
            'post_type'      => 'ecwid_placeholder',
            'posts_per_page' => 1,
            'fields'         => 'ids',
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Meta query required to find placeholder by Ecwid parent ID
            'meta_key'       => '_ecwid_placeholder_parent_id',
            'meta_value'     => $parent_ecwid_id, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
        ]);

        if (empty($q->posts)) {
            // No placeholder recorded for this parent; nothing to reconcile
            return;
        }

        $placeholder_post_id = $q->posts[0];
        $placeholder_term_id = (int) get_post_meta($placeholder_post_id, '_ecwid_placeholder_term_id', true);

        if (!$placeholder_term_id) {
            $logs[] = "[PLACEHOLDER] Found placeholder post {$placeholder_post_id} but no term ID stored.";
            return;
        }

        // Verify placeholder term still exists
        $term_check = term_exists($placeholder_term_id, 'product_cat');
        if (!$term_check) {
            // Clean up stale placeholder post
            wp_delete_post($placeholder_post_id, true);
            $logs[] = "[PLACEHOLDER] Stale placeholder (post {$placeholder_post_id}) removed; term no longer exists.";
            return;
        }

        // Move children of the placeholder to the real parent
        $children = get_terms([
            'taxonomy'   => 'product_cat',
            'hide_empty' => false,
            'parent'     => $placeholder_term_id,
            'fields'     => 'ids',
        ]);

        if (!is_wp_error($children) && !empty($children)) {
            foreach ($children as $child_term_id) {
                $upd = wp_update_term($child_term_id, 'product_cat', ['parent' => $real_parent_term_id]);
                if (is_wp_error($upd)) {
                    $logs[] = sprintf('[PLACEHOLDER] Failed moving child term %1$s under real parent %2$s: %3$s', $child_term_id, $real_parent_term_id, $upd->get_error_message());
                } else {
                    $logs[] = sprintf('[PLACEHOLDER] Moved child term %1$s under real parent %2$s', $child_term_id, $real_parent_term_id);
                }
            }
        }

        // Attempt to delete the placeholder term if it has no children and no product assignments
        // First, detach any meta marking it as placeholder (not strictly necessary but tidy)
        delete_term_meta($placeholder_term_id, '_ecwid_placeholder_category');

        // Double-check if term still has children after moves
        $remaining_children = get_terms([
            'taxonomy'   => 'product_cat',
            'hide_empty' => false,
            'parent'     => $placeholder_term_id,
            'fields'     => 'ids',
        ]);
        if (is_wp_error($remaining_children) || empty($remaining_children)) {
            // Try deleting the placeholder term (will fail if products still assigned)
            $del = wp_delete_term($placeholder_term_id, 'product_cat');
            if (is_wp_error($del)) {
                $logs[] = sprintf('[PLACEHOLDER] Could not delete placeholder term %1$s: %2$s', $placeholder_term_id, $del->get_error_message());
            } else {
                $logs[] = sprintf('[PLACEHOLDER] Deleted placeholder term %1$s after reconciliation', $placeholder_term_id);
            }
        } else {
            $logs[] = sprintf('[PLACEHOLDER] Placeholder term %1$s kept (still has children or assignments).', $placeholder_term_id);
        }

        // Remove the placeholder post record
        wp_delete_post($placeholder_post_id, true);
        $logs[] = sprintf('[PLACEHOLDER] Removed placeholder record post %1$s for Ecwid parent %2$s', $placeholder_post_id, $parent_ecwid_id);

        // Update missing parents registry
        $missing_parents = get_option('ecwid_wc_sync_missing_parents', []);
        if (isset($missing_parents[$parent_ecwid_id])) {
            unset($missing_parents[$parent_ecwid_id]);
            update_option('ecwid_wc_sync_missing_parents', $missing_parents);
        }
    }

    public function get_term_id_by_ecwid_id($ecwid_id, $taxonomy, $bypass_cache = false) {
        global $wpdb;
        static $term_cache = []; // Renamed cache variable to avoid conflict
        $cache_key = $ecwid_id . '_' . $taxonomy;

        if (!$bypass_cache && isset($term_cache[$cache_key])) {
            return $term_cache[$cache_key];
        }

        // amazonq-ignore-next-line
        $term_id = $wpdb->get_var($wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Performance-critical query for finding category terms by Ecwid ID
            "SELECT t.term_id
             FROM {$wpdb->terms} AS t
             INNER JOIN {$wpdb->term_taxonomy} AS tt ON t.term_id = tt.term_id
             INNER JOIN {$wpdb->termmeta} AS tm ON t.term_id = tm.term_id
             WHERE tt.taxonomy = %s
             AND tm.meta_key = '_ecwid_category_id'
             AND tm.meta_value = %s
             LIMIT 1",
            $taxonomy,
            strval($ecwid_id) // Ensure it's a string for meta value comparison
        ));

        if (!$bypass_cache && $term_id) {
            $term_cache[$cache_key] = (int)$term_id;
        }
        return $term_id ? (int)$term_id : null;
    }

    /**
     * Diagnostic function to check upload directory status
     */
    private function diagnose_upload_directory() {
        global $wp_filesystem;
        
        // Initialize WP_Filesystem
        if (!function_exists('WP_Filesystem')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }
        WP_Filesystem();
        
        $upload_dir = wp_upload_dir();
        
        // Memory info
        $memory_limit = ini_get('memory_limit');
        $memory_limit_bytes = wp_convert_hr_to_bytes($memory_limit);
        $memory_usage = memory_get_usage(true);
        $memory_peak = memory_get_peak_usage(true);
        $memory_percent = $memory_limit_bytes > 0 ? round(($memory_usage / $memory_limit_bytes) * 100, 1) : 0;
        
        // Disk space info
        $disk_free = function_exists('disk_free_space') ? @disk_free_space($upload_dir['basedir']) : false;
        $disk_total = function_exists('disk_total_space') ? @disk_total_space($upload_dir['basedir']) : false;
        $disk_used = ($disk_total && $disk_free) ? $disk_total - $disk_free : false;
        $disk_percent = ($disk_total && $disk_total > 0) ? round(($disk_used / $disk_total) * 100, 1) : 0;
        
        // Execution limits
        $max_execution_time = ini_get('max_execution_time');
        $max_input_time = ini_get('max_input_time');
        
        // WooCommerce stats
        $product_count = 0;
        $category_count = 0;
        $order_count = 0;
        if (function_exists('wc_get_product')) {
            $product_count = wp_count_posts('product')->publish;
            $category_count = wp_count_terms('product_cat');
            if (is_wp_error($category_count)) {
                $category_count = 0;
            }
            $order_count = wp_count_posts('shop_order')->{'wc-completed'} + wp_count_posts('shop_order')->{'wc-processing'};
        }
        
        // Database info
        global $wpdb;
        $db_size = 0;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- SHOW TABLE STATUS requires direct query; result is transient diagnostic data not suitable for caching
        // amazonq-ignore-next-line
        $table_results = $wpdb->get_results("SHOW TABLE STATUS", ARRAY_A);
        if ($table_results) {
            foreach ($table_results as $table) {
                $db_size += isset($table['Data_length']) ? $table['Data_length'] : 0;
                $db_size += isset($table['Index_length']) ? $table['Index_length'] : 0;
            }
        }
        
        $debug_info = [
            'upload_dir_info' => $upload_dir,
            'basedir_exists' => $wp_filesystem->is_dir($upload_dir['basedir']),
            'basedir_writable' => $wp_filesystem->is_writable($upload_dir['basedir']),
            'path_exists' => $wp_filesystem->is_dir($upload_dir['path']),
            'path_writable' => $wp_filesystem->is_writable($upload_dir['path']),
            // Disk info
            'disk_free_space' => $disk_free,
            'disk_total_space' => $disk_total,
            'disk_used_space' => $disk_used,
            'disk_percent' => $disk_percent,
            // PHP limits
            'php_upload_max_filesize' => ini_get('upload_max_filesize'),
            'php_post_max_size' => ini_get('post_max_size'),
            'php_memory_limit' => $memory_limit,
            'wp_max_upload_size' => wp_max_upload_size(),
            // Memory usage
            'memory_usage' => $memory_usage,
            'memory_usage_formatted' => size_format($memory_usage),
            'memory_peak' => $memory_peak,
            'memory_peak_formatted' => size_format($memory_peak),
            'memory_percent' => $memory_percent,
            // Execution limits
            'max_execution_time' => $max_execution_time,
            'max_input_time' => $max_input_time,
            // PHP info
            'php_version' => phpversion(),
            'php_sapi' => php_sapi_name(),
            // WordPress info
            'wp_version' => get_bloginfo('version'),
            'wp_debug' => defined('WP_DEBUG') && WP_DEBUG,
            'wp_debug_log' => defined('WP_DEBUG_LOG') && WP_DEBUG_LOG,
            'multisite' => is_multisite(),
            // WooCommerce info
            'wc_version' => defined('WC_VERSION') ? WC_VERSION : 'N/A',
            'wc_product_count' => $product_count,
            'wc_category_count' => $category_count,
            'wc_order_count' => $order_count,
            'wc_currency' => function_exists('get_woocommerce_currency') ? get_woocommerce_currency() : 'N/A',
            // Database info
            'db_size' => $db_size,
            'db_size_formatted' => size_format($db_size),
            'db_prefix' => $wpdb->prefix,
            // Plugin info
            'plugin_version' => defined('METROTECHS_E2W_VERSION') ? METROTECHS_E2W_VERSION : '1.6.0',
            // Timestamps
            'server_time' => current_time('Y-m-d H:i:s'),
            'timezone' => wp_timezone_string(),
        ];

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Ecwid2Woo] Upload Directory Diagnostics: ' . json_encode($debug_info, JSON_PRETTY_PRINT)); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging wrapped in WP_DEBUG check
        }

        return $debug_info;
    }

    public function attach_image_to_product_from_url($image_url, $post_id = 0, $desc = null) {
        if (empty($image_url)) {
            return new WP_Error('missing_url', __('Image URL is empty.', 'metrotechs-e2w-sync'));
        }
        if (!function_exists('download_url')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }
        if (!function_exists('media_handle_sideload')) {
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');
        }

        $timeout_seconds = apply_filters('ecwid_wc_sync_image_download_timeout', 30);
        $tmp = download_url($image_url, $timeout_seconds);
        if (is_wp_error($tmp)) {
            // amazonq-ignore-next-line
            wp_delete_file($tmp);
            // translators: %1$s is the image URL, %2$s is the error message
            return new WP_Error('download_failed', sprintf(__('Image download failed from %1$s: %2$s', 'metrotechs-e2w-sync'), esc_url_raw($image_url), $tmp->get_error_message()));
        }

        $file_array = [
            'name' => basename(wp_parse_url($image_url, PHP_URL_PATH)),
            'tmp_name' => $tmp
        ];

        $attachment_id = media_handle_sideload($file_array, $post_id, $desc);

        // Brief CPU yield after thumbnail generation (heaviest CPU operation)
        // Prevents shared hosting from becoming unresponsive during bulk imports
        usleep(250000); // 250ms

        // Initialize WP_Filesystem for file operations
        global $wp_filesystem;
        if (!function_exists('WP_Filesystem')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }
        WP_Filesystem();

        if ($wp_filesystem->exists($tmp)) {
            wp_delete_file($tmp);
        }

        if (is_wp_error($attachment_id)) {
            // When image sideload fails, run diagnostics to help troubleshoot
            $diagnostic_info = $this->diagnose_upload_directory();
            
            // translators: %1$s is the image URL, %2$s is the error message
            $error_message = sprintf(__('Image sideload failed for %1$s: %2$s', 'metrotechs-e2w-sync'), esc_url_raw($image_url), $attachment_id->get_error_message());
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                // amazonq-ignore-next-line
                error_log('[Ecwid2Woo] Image Upload Error Details: ' . $error_message); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging wrapped in WP_DEBUG check
                error_log('[Ecwid2Woo] Upload Diagnostics: ' . json_encode($diagnostic_info, JSON_PRETTY_PRINT)); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging wrapped in WP_DEBUG check
            }
            
            return new WP_Error('sideload_failed', $error_message);
        }
        return $attachment_id;
    }

    /**
     * Attach image to WooCommerce category from URL
     * Uses the WooCommerce category thumbnail system
     */
    private function attach_image_to_category_from_url($image_url, $term_id, $desc = null) {
        if (empty($image_url) || empty($term_id)) {
            return new WP_Error('missing_params', __('Image URL or term ID is missing.', 'metrotechs-e2w-sync'));
        }
        
        // Use the existing image attachment function to download and create the attachment
        $attachment_id = $this->attach_image_to_product_from_url($image_url, 0, $desc);
        
        if (is_wp_error($attachment_id)) {
            return $attachment_id; // Return the error
        }
        
        // Set as category thumbnail using WooCommerce's meta system
        $meta_update_result = update_term_meta($term_id, 'thumbnail_id', $attachment_id);
        
        if ($meta_update_result === false) {
            // If meta update failed, clean up the attachment
            wp_delete_attachment($attachment_id, true);
            return new WP_Error('meta_update_failed', __('Failed to set category thumbnail.', 'metrotechs-e2w-sync'));
        }
        
        return $attachment_id;
    }

    /**
     * Handle category image import from Ecwid category data
     * 
     * @param array $category_data Ecwid category data containing image URLs
     * @param int $term_id WooCommerce category term ID
     * @param array &$logs Reference to logs array for adding import messages
     */
    private function handle_category_image_import($category_data, $term_id, &$logs) {
        // Check for category image URLs (prioritize hdThumbnailUrl, fallback to originalImageUrl)
        $image_url = null;
        if (isset($category_data['hdThumbnailUrl']) && !empty($category_data['hdThumbnailUrl'])) {
            $image_url = $category_data['hdThumbnailUrl'];
        } elseif (isset($category_data['originalImageUrl']) && !empty($category_data['originalImageUrl'])) {
            $image_url = $category_data['originalImageUrl'];
        }
        
        if (empty($image_url)) {
            $logs[] = "No category image found in Ecwid data.";
            return;
        }
        
        $logs[] = "Found category image URL: " . esc_url_raw($image_url);
        
        // Check if category already has a thumbnail
        $existing_thumbnail_id = get_term_meta($term_id, 'thumbnail_id', true);
        if (!empty($existing_thumbnail_id)) {
            // Check if the existing thumbnail is from a different URL by comparing Ecwid meta
            $existing_ecwid_url = get_post_meta($existing_thumbnail_id, '_ecwid_source_url', true);
            if ($existing_ecwid_url === $image_url) {
                $logs[] = "Category already has this image as thumbnail. Skipping download.";
                return;
            } else {
                $logs[] = "Category has different thumbnail. Updating with new Ecwid image.";
            }
        }
        
        // Download and attach the image
        $attachment_id = $this->attach_image_to_category_from_url(
            $image_url, 
            $term_id, 
            sprintf('Category thumbnail for %s (Ecwid ID: %s)', 
                    $category_data['name'] ?? 'Unknown', 
                    $category_data['id'] ?? 'Unknown')
        );
        
        if (is_wp_error($attachment_id)) {
            $logs[] = "[WARNING] Failed to import category image: " . $attachment_id->get_error_message();
            return;
        }
        
        // Store the source URL in attachment meta for future reference
        update_post_meta($attachment_id, '_ecwid_source_url', $image_url);
        update_post_meta($attachment_id, '_ecwid_category_id', $category_data['id'] ?? '');
        
        $logs[] = "Successfully imported category image (Attachment ID: $attachment_id) and set as thumbnail.";
    }

    public function fix_category_hierarchy() {
        check_ajax_referer('ecwid_wc_sync_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized', 'metrotechs-e2w-sync')]);
            return;
        }

        $missing_parents = get_option('ecwid_wc_sync_missing_parents', []);
        $fixed_count = 0;
        $logs = [];

        foreach ($missing_parents as $parent_ecwid_id => $child_ecwid_ids) {
            $parent_wc_term_id = $this->get_term_id_by_ecwid_id($parent_ecwid_id, 'product_cat', true);

            if (!$parent_wc_term_id) {
                // translators: %s is the Ecwid category ID
                $logs[] = sprintf(__('Parent Ecwid ID %s still missing, cannot fix its children.', 'metrotechs-e2w-sync'), $parent_ecwid_id);
                continue;
            }

            foreach ($child_ecwid_ids as $child_ecwid_id) {
                $child_wc_term_id = $this->get_term_id_by_ecwid_id($child_ecwid_id, 'product_cat', true);

                if (!$child_wc_term_id) {
                    // translators: %s is the Ecwid category ID
                    $logs[] = sprintf(__('Child term for Ecwid ID %s not found.', 'metrotechs-e2w-sync'), $child_ecwid_id);
                    continue;
                }

                $update_result = wp_update_term($child_wc_term_id, 'product_cat', ['parent' => $parent_wc_term_id]);

                if (is_wp_error($update_result)) {
                    // translators: %1$s is the term ID, %2$s is the error message
                    $logs[] = sprintf(__('Failed to update parent for term %1$s: %2$s', 'metrotechs-e2w-sync'), $child_wc_term_id, $update_result->get_error_message());
                } else {
                    $fixed_count++;
                    // translators: %1$s is the term ID, %2$s is the parent term ID
                    $logs[] = sprintf(__('Fixed parent for term %1$s, now under parent %2$s', 'metrotechs-e2w-sync'), $child_wc_term_id, $parent_wc_term_id);
                }
            }
        }

        update_option('ecwid_wc_sync_missing_parents', []);

        wp_send_json_success([
            'fixed_count' => $fixed_count,
            'logs' => $logs,
            // translators: %d is the number of hierarchies fixed
            'message' => sprintf(_n('%d hierarchy fixed.', '%d hierarchies fixed.', $fixed_count, 'metrotechs-e2w-sync'), $fixed_count)
        ]);
    }

    /**
     * Get Ecwid store currency from API response
     * Caches the result to avoid repeated API calls
     * 
     * @return string|null Currency code (e.g., 'EUR', 'USD', 'GBP') or null if unavailable
     */
    private function get_ecwid_store_currency() {
        static $ecwid_currency = null;
        static $api_call_attempted = false;
        
        // If we already tried and failed, don't retry in the same request
        if ($api_call_attempted && $ecwid_currency === null) {
            return null;
        }
        
        if ($ecwid_currency !== null) {
            return $ecwid_currency;
        }

        $store_id = get_option('ecwid_wc_store_id');
        $api_token = get_option('ecwid_wc_api_token');

        if (empty($store_id) || empty($api_token)) {
            $api_call_attempted = true;
            return null;
        }

        // Get store profile to extract currency
        // amazonq-ignore-next-line
        $profile_url = "https://app.ecwid.com/api/v3/{$store_id}/profile?token={$api_token}";
        
        $response = wp_remote_get($profile_url, [
            'timeout' => 30,
            'headers' => [
                'Accept' => 'application/json',
                'User-Agent' => 'ecwid2woo/' . ECWID2WOO_VERSION
            ]
        ]);

        $api_call_attempted = true; // Mark that we tried

        if (is_wp_error($response)) {
            error_log('Ecwid2Woo: Failed to get store profile for currency: ' . $response->get_error_message()); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Critical error logging for API failures
            return null;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code !== 200) {
            if ($status_code === 403) {
                error_log('Ecwid2Woo: Store profile API returned status 403 - Check your API token permissions'); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Critical error logging for API failures
            } else {
                error_log('Ecwid2Woo: Store profile API returned status ' . $status_code); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Critical error logging for API failures
            }
            return null;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (isset($data['settings']['defaultDisplayedCurrency'])) {
            $ecwid_currency = $data['settings']['defaultDisplayedCurrency'];
        } elseif (isset($data['currency'])) {
            $ecwid_currency = $data['currency'];
        }

        return $ecwid_currency;
    }

    /**
     * Get current WooCommerce base currency
     * 
     * @return string Current WooCommerce currency code
     */
    private function get_woocommerce_currency() {
        // Check if WooCommerce is active and function exists
        if (function_exists('get_woocommerce_currency')) {
            return get_woocommerce_currency();
        } elseif (function_exists('get_option')) {
            // Fallback to direct option access if WooCommerce function isn't available
            return get_option('woocommerce_currency', 'USD');
        }
        
        // Final fallback
        return 'USD';
    }

    /**
     * Update WooCommerce currency settings to match Ecwid store currency
     * 
     * @param array &$logs Log array to append currency change messages (optional, will create temp array if null)
     * @return bool True if currency was updated, false if no change needed
     */
    public function sync_currency_settings(&$logs = null) {
        // If no logs array provided, create a temporary one
        if ($logs === null) {
            $logs = [];
        }
        
        // Check if WooCommerce is available
        if (!function_exists('get_woocommerce_currency')) {
            $logs[] = '[CURRENCY] WooCommerce not available. Skipping currency sync.';
            return false;
        }
        
        try {
            $ecwid_currency = $this->get_ecwid_store_currency();
            $wc_currency = $this->get_woocommerce_currency();

            if (!$ecwid_currency) {
                $logs[] = '[CURRENCY] Could not detect Ecwid store currency. WooCommerce currency unchanged.';
                return false;
            }

            $logs[] = "[CURRENCY] Ecwid store currency: {$ecwid_currency}, WooCommerce currency: {$wc_currency}";

            if ($ecwid_currency === $wc_currency) {
                $logs[] = '[CURRENCY] Currencies already match. No change needed.';
                return false;
            }

            // Update WooCommerce currency
            update_option('woocommerce_currency', $ecwid_currency);
            
            // Clear any WooCommerce caches that might be affected
            if (function_exists('wc_clear_notices')) {
                wc_clear_notices();
            }
            
            // Clear WooCommerce transients after currency change
            delete_transient('woocommerce_cache_prefix');
            wp_cache_delete('woocommerce_currency', 'options');

            $logs[] = "[CURRENCY] ✅ Updated WooCommerce currency from {$wc_currency} to {$ecwid_currency}";
            
            // Log the currency symbols for clarity
            $currency_symbols = [
                'USD' => '$', 'EUR' => '€', 'GBP' => '£', 'JPY' => '¥', 
                'CNY' => '¥', 'CAD' => 'C$', 'AUD' => 'A$', 'CHF' => 'CHF',
                'SEK' => 'kr', 'NOK' => 'kr', 'DKK' => 'kr', 'PLN' => 'zł',
                'CZK' => 'Kč', 'HUF' => 'Ft', 'RUB' => '₽', 'BRL' => 'R$',
                'MXN' => '$', 'INR' => '₹', 'KRW' => '₩', 'SGD' => 'S$',
                'HKD' => 'HK$', 'NZD' => 'NZ$', 'ZAR' => 'R', 'TRY' => '₺'
            ];
            
            $symbol = $currency_symbols[$ecwid_currency] ?? $ecwid_currency;
            $logs[] = "[CURRENCY] Products will now display prices with {$symbol} symbol";

            return true;
        } catch (Exception $e) {
            $logs[] = '[CURRENCY ERROR] ' . $e->getMessage();
            error_log('Ecwid2Woo: Currency sync error: ' . $e->getMessage()); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            return false;
        }
    }

    public function ajax_fetch_full_sync_counts_DISABLED() {
        // Check WooCommerce availability first
        if (!class_exists('WooCommerce')) {
            wp_send_json_error([
                'message' => __('WooCommerce is not installed or activated. Please install WooCommerce to use this plugin.', 'metrotechs-e2w-sync'),
                'error_type' => 'missing_dependency'
            ]);
            return;
        }
        
        // Only enable enhanced debugging if WP_DEBUG is enabled and user has sufficient privileges
        $debug_mode = defined('WP_DEBUG') && WP_DEBUG && current_user_can('manage_options');
        
        try {
            check_ajax_referer('ecwid_wc_sync_nonce', 'nonce');
            if (!current_user_can('manage_options')) {
                wp_send_json_error(['message' => __('You do not have permission to perform this action.', 'metrotechs-e2w-sync')], 403);
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
                        'message' => __('Server memory limit too low for sync operation. Current: ', 'metrotechs-e2w-sync') . $current_memory . __(' Minimum required: 128M', 'metrotechs-e2w-sync'),
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

            $api_essentials = $this->_get_api_essentials();
            if (is_wp_error($api_essentials)) {
                wp_send_json_error(['message' => $api_essentials->get_error_message()], 500);
                return;
            }

            $category_count = 0;
            $product_count = 0;
            $errors = [];
            $categories_preview = [];
            $products_preview = [];
            
            // Sync currency settings first and add to response
            $currency_logs = [];
            $currency_updated = false;
            
            try {
                $currency_updated = $this->sync_currency_settings($currency_logs);
            } catch (Exception $e) {
                error_log('Ecwid2Woo: Error in currency sync: ' . $e->getMessage()); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                $currency_logs[] = '[CURRENCY ERROR] ' . $e->getMessage();
            }

        // Fetch Categories
        $categories_url = add_query_arg([
            'limit' => 100,
            'offset' => 0,
            'responseFields' => 'items(id,name),total'
        ], $api_essentials['base_url'] . '/categories');

        $cat_response = wp_remote_get($categories_url, [
            'headers' => [
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $api_essentials['token']
            ],
            'timeout' => 30,
        ]);

        if (is_wp_error($cat_response)) {
            // translators: %s is the error message
            $errors[] = sprintf(__('Error fetching categories from Ecwid: %s', 'metrotechs-e2w-sync'), $cat_response->get_error_message());
        } else {
            $cat_body = wp_remote_retrieve_body($cat_response);
            $cat_data = json_decode($cat_body, true);
            $cat_http_code = wp_remote_retrieve_response_code($cat_response);
            
            if ($cat_http_code === 200 && isset($cat_data['items'])) {
                $category_count = isset($cat_data['total']) ? $cat_data['total'] : count($cat_data['items']);
                $categories_preview = $cat_data['items'];
            } else {
                // Use enhanced error handling for categories
                $error_info = $this->handle_api_error_response($cat_response, $cat_body, $cat_http_code, 'categories');
                $errors[] = $error_info['user_message'];
            }
        }

        // Fetch Products
        $products_url = add_query_arg([
            'limit' => 100,
            'offset' => 0,
            'enabled' => 'true',
            'responseFields' => 'items(id,name,enabled),total'
        ], $api_essentials['base_url'] . '/products');

        $prod_response = wp_remote_get($products_url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_essentials['token']
            ],
            'timeout' => 30,
        ]);

        if (is_wp_error($prod_response)) {
            // translators: %s is the error message
            $errors[] = sprintf(__('Error fetching products from Ecwid: %s', 'metrotechs-e2w-sync'), $prod_response->get_error_message());
        } else {
            $prod_body = wp_remote_retrieve_body($prod_response);
            $prod_data = json_decode($prod_body, true);
            $prod_http_code = wp_remote_retrieve_response_code($prod_response);
            
            if ($prod_http_code === 200 && isset($prod_data['items'])) {
                $product_count = isset($prod_data['total']) ? $prod_data['total'] : count($prod_data['items']);
                $products_preview = $prod_data['items'];
            } else {
                // Use enhanced error handling for products
                $error_info = $this->handle_api_error_response($prod_response, $prod_body, $prod_http_code, 'products');
                $errors[] = $error_info['user_message'];
            }
        }

        if (!empty($errors)) {
            wp_send_json_error([
                'message' => implode('; ', $errors),
                'categories_preview' => $categories_preview,
                'products_preview' => $products_preview,
                'categories_count' => $category_count,
                'products_count' => $product_count
            ]);
        } else {
            wp_send_json_success([
                'categories_preview' => $categories_preview,
                'products_preview' => $products_preview,
                'categories_count' => $category_count,
                'products_count' => $product_count,
                'currency_logs' => $currency_logs,
                'currency_updated' => $currency_updated
            ]);
        }
        
        } catch (Exception $e) {
            error_log('Ecwid2Woo: Fatal error in ajax_fetch_full_sync_counts: ' . $e->getMessage()); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            wp_send_json_error([
                'message' => __('An unexpected error occurred. Please check the error logs.', 'metrotechs-e2w-sync'),
                'debug_info' => $debug_mode ? $e->getMessage() : ''
            ], 500);
        }
    }

    public function ajax_fetch_categories_for_display_DISABLED() {
        check_ajax_referer('ecwid_wc_sync_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized', 'metrotechs-e2w-sync')]);
            return;
        }
        set_time_limit(300); // phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged -- Legitimate use for category fetch operations

        $api_essentials = $this->_get_api_essentials();
        if (is_wp_error($api_essentials)) {
            wp_send_json_error(['message' => $api_essentials->get_error_message()]);
            return;
        }

        // Load all categories at once
        $all_categories = [];
        $offset = 0;
        $limit = 100;
        $api_calls_made = 0;

        do {
            $api_calls_made++;
            $query_params = [
                'limit' => $limit,
                'offset' => $offset,
                'responseFields' => 'items(id,name,parentId,updateTimestamp),total'
            ];
            $api_url = add_query_arg($query_params, $api_essentials['base_url'] . '/categories');

            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Ecwid Category Sync: API call #$api_calls_made - Fetching categories with offset: $offset, limit: $limit"); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging wrapped in WP_DEBUG check
            }

            $response = wp_remote_get($api_url, [
                'timeout' => 120, // Increased timeout for large stores
                'headers' => ['Authorization' => 'Bearer ' . $api_essentials['token'], 'Accept' => 'application/json'],
            ]);

            if (is_wp_error($response)) {
                // translators: %s is the error message from the WordPress HTTP API
                wp_send_json_error(['message' => sprintf(__('API Request Error: %s', 'metrotechs-e2w-sync'), $response->get_error_message())]);
                return;
            }

            $raw_response_body = wp_remote_retrieve_body($response);
            $body = json_decode($raw_response_body, true);
            $http_code = wp_remote_retrieve_response_code($response);

            if ($http_code !== 200 || (isset($body['errorMessage']) && !empty($body['errorMessage']))) {
                // Use enhanced error handling
                $error_info = $this->handle_api_error_response($response, $raw_response_body, $http_code, 'categories');
                
                // Provide user-friendly error message with retry suggestion for server errors
                $error_message = $error_info['user_message'];
                if ($error_info['retry_recommended']) {
                    $error_message .= ' ' . __('This appears to be a temporary issue. You can try again in a few minutes.', 'metrotechs-e2w-sync');
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
            $all_categories = array_merge($all_categories, $items_from_api);

            $count_in_response = $body['count'] ?? count($items_from_api);
            $total_from_api = $body['total'] ?? count($items_from_api);
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Ecwid Category Sync: API call #$api_calls_made - Got $count_in_response categories, total available: $total_from_api, current offset: $offset"); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging wrapped in WP_DEBUG check
            }
            
            $offset += $count_in_response;

        } while ($count_in_response > 0 && $offset < $total_from_api);

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Ecwid Category Sync: Complete! Made $api_calls_made API calls, loaded " . count($all_categories) . " total categories"); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging wrapped in WP_DEBUG check
        }

        wp_send_json_success([
            'categories' => $all_categories,
            'total_found' => count($all_categories),
            'api_calls_made' => $api_calls_made,
            'total_available' => $total_from_api
        ]);
    }

    public function ajax_import_selected_categories_DISABLED() {
        check_ajax_referer('ecwid_wc_sync_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized', 'metrotechs-e2w-sync')]);
            return;
        }
        set_time_limit(300); // phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged -- Legitimate use for selected category import operations

        $selected_category_ids = isset($_POST['category_ids']) ? array_map('intval', $_POST['category_ids']) : [];

        if (empty($selected_category_ids)) {
            wp_send_json_error(['message' => __('No categories selected for import.', 'metrotechs-e2w-sync')]);
            return;
        }

        $api_essentials = $this->_get_api_essentials();
        if (is_wp_error($api_essentials)) {
            wp_send_json_error(['message' => $api_essentials->get_error_message()]);
            return;
        }

        // Sync currency before importing categories
        $currency_sync_logs = [];
        $currency_sync_result = $this->sync_currency_settings($currency_sync_logs);
        if (defined('WP_DEBUG') && WP_DEBUG && !empty($currency_sync_result)) {
            error_log("Ecwid Sync: Currency sync result for selected categories import: " . print_r($currency_sync_result, true)); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log,WordPress.PHP.DevelopmentFunctions.error_log_print_r -- Debug logging wrapped in WP_DEBUG check
        }

        $import_results = [];
        $imported_count = 0;
        $updated_count = 0;
        $skipped_count = 0;
        $failed_count = 0;
        $detailed_logs = [];

        $detailed_logs[] = "Starting selective category import for " . count($selected_category_ids) . " categories.";

        foreach ($selected_category_ids as $category_id) {
            $detailed_logs[] = "--- Processing Category ID: $category_id ---";
            
            // Fetch individual category data from Ecwid
            $query_params = ['responseFields' => 'id,name,parentId,description,hdThumbnailUrl,originalImageUrl,updateTimestamp'];
            $api_url = add_query_arg($query_params, $api_essentials['base_url'] . '/categories/' . $category_id);

            $response = wp_remote_get($api_url, [
                'timeout' => 60,
                'headers' => ['Authorization' => 'Bearer ' . $api_essentials['token'], 'Accept' => 'application/json'],
            ]);

            if (is_wp_error($response)) {
                $detailed_logs[] = "[ERROR] API Request failed for category $category_id: " . $response->get_error_message();
                $failed_count++;
                continue;
            }

            $category_data = json_decode(wp_remote_retrieve_body($response), true);
            $http_code = wp_remote_retrieve_response_code($response);

            if ($http_code !== 200 || (isset($category_data['errorMessage']) && !empty($category_data['errorMessage']))) {
                $detailed_logs[] = "[ERROR] Failed to fetch category $category_id (HTTP $http_code): " . ($category_data['errorMessage'] ?? 'Unknown error');
                $failed_count++;
                continue;
            }

            if (empty($category_data) || !isset($category_data['id'])) {
                $detailed_logs[] = "[ERROR] Invalid category data received for category $category_id";
                $failed_count++;
                continue;
            }

            // Import the category using existing import_category method
            try {
                $result_array = $this->import_category($category_data);

                if (isset($result_array['status'])) {
                    $import_results[] = $result_array;
                    
                    if ($result_array['status'] === 'imported') {
                        $imported_count++;
                    } elseif ($result_array['status'] === 'updated') {
                        $updated_count++;
                    } elseif ($result_array['status'] === 'skipped') {
                        $skipped_count++;
                    } else {
                        $failed_count++;
                    }

                    $category_name = esc_html($result_array['item_name'] ?? "Category $category_id");
                    $detailed_logs[] = "Category: $category_name - Result: " . strtoupper($result_array['status']);
                    
                    if (!empty($result_array['logs']) && is_array($result_array['logs'])) {
                        foreach ($result_array['logs'] as $log_entry) {
                            $detailed_logs[] = "  " . $log_entry;
                        }
                    }
                } else {
                    $detailed_logs[] = "[ERROR] Import function returned unexpected result for category $category_id";
                    $failed_count++;
                }

            } catch (Exception $e) {
                $detailed_logs[] = "[EXCEPTION] Error importing category $category_id: " . $e->getMessage();
                $failed_count++;
            }

            $detailed_logs[] = "";
        }

        $summary_message = sprintf(
            // translators: %1$d is imported count, %2$d is updated count, %3$d is skipped count, %4$d is failed count
            __('Selective category import completed. Imported: %1$d, Updated: %2$d, Skipped: %3$d, Failed: %4$d', 'metrotechs-e2w-sync'),
            $imported_count,
            $updated_count,
            $skipped_count,
            $failed_count
        );

        $detailed_logs[] = "=== IMPORT SUMMARY ===";
        $detailed_logs[] = $summary_message;

        wp_send_json_success([
            'message' => $summary_message,
            'imported_count' => $imported_count,
            'updated_count' => $updated_count,
            'skipped_count' => $skipped_count,
            'failed_count' => $failed_count,
            'total_processed' => count($selected_category_ids),
            'logs' => $detailed_logs,
            'results' => $import_results
        ]);
    }

    /**
     * AJAX handler for syncing all categories from Ecwid
     */
    public function ajax_sync_all_categories_DISABLED() {
        // Verify nonce for security
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'ecwid_wc_sync_nonce')) {
            wp_send_json_error(['message' => __('Security check failed. Please refresh the page and try again.', 'metrotechs-e2w-sync')]);
        }

        // Check for required API credentials
        $api_essentials = $this->_get_api_essentials();
        if (is_wp_error($api_essentials)) {
            wp_send_json_error(['message' => $api_essentials->get_error_message()]);
        }

        $imported_count = 0;
        $updated_count = 0;
        $skipped_count = 0;
        $failed_count = 0;
        $import_results = [];
        $detailed_logs = [];

        $detailed_logs[] = "Starting full category sync...";

        try {
            // Fetch all categories from Ecwid
            $all_categories = [];
            $offset = 0;
            $limit = 100;
            $api_calls_made = 0;
            $max_api_calls = 50; // Safety limit

            do {
                $api_calls_made++;
                if ($api_calls_made > $max_api_calls) {
                    $detailed_logs[] = "[WARNING] Maximum API calls limit ($max_api_calls) reached. Some categories may not be synced.";
                    break;
                }

                $query_params = [
                    'limit' => $limit,
                    'offset' => $offset,
                    'responseFields' => 'items(id,name,parentId,description,hdThumbnailUrl,originalImageUrl,updateTimestamp),total'
                ];
                $api_url = add_query_arg($query_params, $api_essentials['base_url'] . '/categories');

                $detailed_logs[] = "[DEBUG] API call #$api_calls_made - URL: $api_url";

                $response = wp_remote_get($api_url, [
                    'timeout' => 60,
                    'headers' => ['Authorization' => 'Bearer ' . $api_essentials['token'], 'Accept' => 'application/json'],
                ]);

                if (is_wp_error($response)) {
                    $detailed_logs[] = "[ERROR] API Request failed at offset $offset: " . $response->get_error_message();
                    break;
                }

                $response_body = wp_remote_retrieve_body($response);
                $http_code = wp_remote_retrieve_response_code($response);

                if ($http_code !== 200) {
                    $detailed_logs[] = "[ERROR] API returned HTTP $http_code at offset $offset";
                    break;
                }

                $data = json_decode($response_body, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $detailed_logs[] = "[ERROR] JSON decode error: " . json_last_error_msg();
                    $detailed_logs[] = "[DEBUG] Raw response body: " . substr($response_body, 0, 500) . "...";
                    break;
                }

                // Enhanced debugging
                $debug_data = $this->format_debug_data(array_keys($data));
                if ($debug_data) {
                    $detailed_logs[] = "[DEBUG] API Response structure: " . $debug_data;
                }
                if (isset($data['total'])) {
                    $detailed_logs[] = "[DEBUG] Total categories reported by API: " . $data['total'];
                }

                if (!isset($data['items']) || !is_array($data['items'])) {
                    $detailed_logs[] = "[ERROR] Invalid response format from Ecwid API";
                    $debug_data = $this->format_debug_data($data);
                    if ($debug_data) {
                        $detailed_logs[] = "[DEBUG] Expected 'items' array, got: " . $debug_data;
                    }
                    break;
                }

                $categories_in_batch = $data['items'];
                $all_categories = array_merge($all_categories, $categories_in_batch);
                
                $count_in_batch = count($categories_in_batch);
                $detailed_logs[] = "Fetched $count_in_batch categories (API call #$api_calls_made, offset: $offset)";

                // Check if we have more categories to fetch
                $total_from_api = isset($data['total']) ? intval($data['total']) : count($all_categories);
                $offset += $limit;
                
            } while (count($categories_in_batch) === $limit && count($all_categories) < $total_from_api);

            $total_categories = count($all_categories);
            $detailed_logs[] = "Fetched $total_categories total categories in $api_calls_made API calls";

            if ($total_categories === 0) {
                wp_send_json_success([
                    'message' => __('No categories found in your Ecwid store.', 'metrotechs-e2w-sync'),
                    'imported_count' => 0,
                    'updated_count' => 0,
                    'skipped_count' => 0,
                    'failed_count' => 0,
                    'logs' => $detailed_logs
                ]);
                return;
            }

            // Process each category
            foreach ($all_categories as $category_data) {
                $category_id = $category_data['id'];
                $detailed_logs[] = "--- Processing Category ID: $category_id ({$category_data['name']}) ---";

                try {
                    // Import the category using existing import_category method
                    $result_array = $this->import_category($category_data);

                    if (isset($result_array['status'])) {
                        $import_results[] = $result_array;
                        
                        if ($result_array['status'] === 'imported') {
                            $imported_count++;
                        } elseif ($result_array['status'] === 'updated') {
                            $updated_count++;
                        } elseif ($result_array['status'] === 'skipped') {
                            $skipped_count++;
                        } else {
                            $failed_count++;
                        }

                        $detailed_logs[] = "[{$result_array['status']}] " . ($result_array['message'] ?? 'Category processed');
                    } else {
                        $detailed_logs[] = "[ERROR] Import function returned unexpected result for category $category_id";
                        $failed_count++;
                    }

                } catch (Exception $e) {
                    $detailed_logs[] = "[EXCEPTION] Error importing category $category_id: " . $e->getMessage();
                    $failed_count++;
                }
            }

            $summary_message = sprintf(
                // translators: %1$d is imported count, %2$d is updated count, %3$d is skipped count, %4$d is failed count
                __('Full category sync completed. Imported: %1$d, Updated: %2$d, Skipped: %3$d, Failed: %4$d', 'metrotechs-e2w-sync'),
                $imported_count,
                $updated_count,
                $skipped_count,
                $failed_count
            );

            $detailed_logs[] = "=== SYNC SUMMARY ===";
            $detailed_logs[] = $summary_message;

            wp_send_json_success([
                'message' => $summary_message,
                'imported_count' => $imported_count,
                'updated_count' => $updated_count,
                'skipped_count' => $skipped_count,
                'failed_count' => $failed_count,
                'total_processed' => $total_categories,
                'api_calls_made' => $api_calls_made,
                'logs' => $detailed_logs,
                'results' => $import_results
            ]);

        } catch (Exception $e) {
            $detailed_logs[] = "[FATAL ERROR] " . $e->getMessage();
            wp_send_json_error([
                'message' => __('Full category sync failed: ', 'metrotechs-e2w-sync') . $e->getMessage(),
                'logs' => $detailed_logs
            ]);
        }
    }

    /**
     * AJAX handler for syncing all products from Ecwid
     */
    public function ajax_sync_all_products() {
        // Verify nonce for security
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'ecwid_wc_sync_nonce')) {
            wp_send_json_error(['message' => __('Security check failed. Please refresh the page and try again.', 'metrotechs-e2w-sync')]);
        }

        // Check for required API credentials
        $api_essentials = $this->_get_api_essentials();
        if (is_wp_error($api_essentials)) {
            wp_send_json_error(['message' => $api_essentials->get_error_message()]);
        }

        $imported_count = 0;
        $updated_count = 0;
        $skipped_count = 0;
        $failed_count = 0;
        $import_results = [];
        $detailed_logs = [];

        $detailed_logs[] = "Starting full product sync...";

        try {
            // Fetch all products from Ecwid
            $all_products = [];
            $offset = 0;
            $limit = 100;
            $api_calls_made = 0;
            $max_api_calls = 100; // Higher limit for products as stores can have many more products

            do {
                $api_calls_made++;
                if ($api_calls_made > $max_api_calls) {
                    $detailed_logs[] = "[WARNING] Maximum API calls limit ($max_api_calls) reached. Some products may not be synced.";
                    break;
                }

                $query_params = [
                    'limit' => $limit,
                    'offset' => $offset,
                    'responseFields' => 'items(id,name,sku,enabled,price,combinations),total'
                ];
                $api_url = add_query_arg($query_params, $api_essentials['base_url'] . '/products');

                $detailed_logs[] = "[DEBUG] API call #$api_calls_made - URL: $api_url";

                $response = wp_remote_get($api_url, [
                    'timeout' => 60,
                    'headers' => ['Authorization' => 'Bearer ' . $api_essentials['token'], 'Accept' => 'application/json'],
                ]);

                if (is_wp_error($response)) {
                    $detailed_logs[] = "[ERROR] API Request failed at offset $offset: " . $response->get_error_message();
                    break;
                }

                $response_body = wp_remote_retrieve_body($response);
                $http_code = wp_remote_retrieve_response_code($response);

                if ($http_code !== 200) {
                    $detailed_logs[] = "[ERROR] API returned HTTP $http_code at offset $offset";
                    break;
                }

                $data = json_decode($response_body, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $detailed_logs[] = "[ERROR] JSON decode error: " . json_last_error_msg();
                    $detailed_logs[] = "[DEBUG] Raw response body: " . substr($response_body, 0, 500) . "...";
                    break;
                }

                // Enhanced debugging
                $debug_data = $this->format_debug_data(array_keys($data));
                if ($debug_data) {
                    $detailed_logs[] = "[DEBUG] API Response structure: " . $debug_data;
                }
                if (isset($data['total'])) {
                    $detailed_logs[] = "[DEBUG] Total products reported by API: " . $data['total'];
                }

                if (!isset($data['items']) || !is_array($data['items'])) {
                    $detailed_logs[] = "[ERROR] Invalid response format from Ecwid API";
                    $debug_data = $this->format_debug_data($data);
                    if ($debug_data) {
                        $detailed_logs[] = "[DEBUG] Expected 'items' array, got: " . $debug_data;
                    }
                    break;
                }

                $products_in_batch = $data['items'];
                $all_products = array_merge($all_products, $products_in_batch);
                
                $count_in_batch = count($products_in_batch);
                $detailed_logs[] = "Fetched $count_in_batch products (API call #$api_calls_made, offset: $offset)";

                // Check if we have more products to fetch
                $total_from_api = isset($data['total']) ? intval($data['total']) : count($all_products);
                $offset += $limit;
                
            } while (count($products_in_batch) === $limit && count($all_products) < $total_from_api);

            $total_products = count($all_products);
            $detailed_logs[] = "Fetched $total_products total products in $api_calls_made API calls";

            if ($total_products === 0) {
                wp_send_json_success([
                    'message' => __('No products found in your Ecwid store.', 'metrotechs-e2w-sync'),
                    'imported_count' => 0,
                    'updated_count' => 0,
                    'skipped_count' => 0,
                    'failed_count' => 0,
                    'logs' => $detailed_logs
                ]);
                return;
            }

            // Process each product
            foreach ($all_products as $product_data) {
                $product_id = $product_data['id'];
                $detailed_logs[] = "--- Processing Product ID: $product_id ({$product_data['name']}) ---";

                try {
                    // Import the product using existing import_product method
                    $result_array = $this->import_product($product_data);

                    if (isset($result_array['status'])) {
                        $import_results[] = $result_array;
                        
                        if ($result_array['status'] === 'imported') {
                            $imported_count++;
                        } elseif ($result_array['status'] === 'updated') {
                            $updated_count++;
                        } elseif ($result_array['status'] === 'skipped') {
                            $skipped_count++;
                        } else {
                            $failed_count++;
                        }

                        $detailed_logs[] = "[{$result_array['status']}] " . ($result_array['message'] ?? 'Product processed');
                    } else {
                        $detailed_logs[] = "[ERROR] Import function returned unexpected result for product $product_id";
                        $failed_count++;
                    }

                } catch (Exception $e) {
                    $detailed_logs[] = "[EXCEPTION] Error importing product $product_id: " . $e->getMessage();
                    $failed_count++;
                }
            }

            $summary_message = sprintf(
                // translators: %1$d is imported count, %2$d is updated count, %3$d is skipped count, %4$d is failed count
                __('Full product sync completed. Imported: %1$d, Updated: %2$d, Skipped: %3$d, Failed: %4$d', 'metrotechs-e2w-sync'),
                $imported_count,
                $updated_count,
                $skipped_count,
                $failed_count
            );

            $detailed_logs[] = "=== SYNC SUMMARY ===";
            $detailed_logs[] = $summary_message;

            wp_send_json_success([
                'message' => $summary_message,
                'imported_count' => $imported_count,
                'updated_count' => $updated_count,
                'skipped_count' => $skipped_count,
                'failed_count' => $failed_count,
                'total_processed' => $total_products,
                'api_calls_made' => $api_calls_made,
                'logs' => $detailed_logs,
                'results' => $import_results
            ]);

        } catch (Exception $e) {
            $detailed_logs[] = "[FATAL ERROR] " . $e->getMessage();
            wp_send_json_error([
                'message' => __('Full product sync failed: ', 'metrotechs-e2w-sync') . $e->getMessage(),
                'logs' => $detailed_logs
            ]);
        }
    }

    // Add this method to handle upload directory diagnostics
    public function ajax_diagnose_uploads() {
        check_ajax_referer('ecwid_wc_sync_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized', 'metrotechs-e2w-sync')]);
            return;
        }

        $diagnostic_info = $this->diagnose_upload_directory();
        
        // Add additional checks
        $diagnostic_info['current_user'] = wp_get_current_user()->user_login;
        $diagnostic_info['php_user'] = function_exists('posix_getpwuid') && function_exists('posix_geteuid') ? posix_getpwuid(posix_geteuid())['name'] : 'unknown';
        $diagnostic_info['server_software'] = isset($_SERVER['SERVER_SOFTWARE']) ? sanitize_text_field(wp_unslash($_SERVER['SERVER_SOFTWARE'])) : 'unknown';
        
        // Test creating a simple file in uploads directory
        global $wp_filesystem;
        if (!function_exists('WP_Filesystem')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }
        WP_Filesystem();
        
        $test_file = $diagnostic_info['upload_dir_info']['path'] . '/ecwid_test_' . time() . '.txt';
        $test_write_success = $wp_filesystem->put_contents($test_file, 'test') !== false;
        $diagnostic_info['test_write_success'] = $test_write_success;
        
        if ($test_write_success && $wp_filesystem->exists($test_file)) {
            wp_delete_file($test_file);
        }

        wp_send_json_success([
            'message' => __('Upload directory diagnostics completed', 'metrotechs-e2w-sync'),
            'diagnostics' => $diagnostic_info
        ]);
    }

    // Add this method to handle AJAX connection testing
    public function ajax_test_api_connection() {
        check_ajax_referer('ecwid_wc_sync_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized', 'metrotechs-e2w-sync')]);
            return;
        }

        $api_essentials = $this->_get_api_essentials();
        if (is_wp_error($api_essentials)) {
            wp_send_json_error(['message' => $api_essentials->get_error_message()]);
            return;
        }

        // Test the connection by fetching store profile
        $api_url = $api_essentials['base_url'] . '/profile';
        $response = wp_remote_get($api_url, [
            'headers' => ['Authorization' => 'Bearer ' . $api_essentials['token'], 'Accept' => 'application/json'],
            'timeout' => 30
        ]);

        if (is_wp_error($response)) {
            wp_send_json_error(['message' => __('Connection failed: ', 'metrotechs-e2w-sync') . $response->get_error_message()]);
            return;
        }

        $http_code = wp_remote_retrieve_response_code($response);
        if ($http_code === 200) {
            wp_send_json_success(['message' => __('Connection successful!', 'metrotechs-e2w-sync')]);
        } else {
            wp_send_json_error(['message' => __('API returned error code: ', 'metrotechs-e2w-sync') . $http_code]);
        }
    }
    
    /**
     * AJAX handler for debugging information
     * Provides detailed environment info to help troubleshoot 500 errors
     */
    public function ajax_debug_info() {
        check_ajax_referer('ecwid_wc_sync_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized', 'metrotechs-e2w-sync')]);
            return;
        }
        
        $debug_info = [
            'php_version' => phpversion(),
            'wordpress_version' => get_bloginfo('version'),
            'woocommerce_version' => defined('WC_VERSION') ? WC_VERSION : 'Not installed',
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'error_reporting_level' => defined('WP_DEBUG') && WP_DEBUG ? 'Debug Mode' : 'Standard',
            'display_errors' => ini_get('display_errors'),
            'log_errors' => ini_get('log_errors'),
            'wp_debug' => defined('WP_DEBUG') ? WP_DEBUG : false,
            'wp_debug_log' => defined('WP_DEBUG_LOG') ? WP_DEBUG_LOG : false,
            'wp_debug_display' => defined('WP_DEBUG_DISPLAY') ? WP_DEBUG_DISPLAY : false,
        ];
        
        // Check if API credentials are set
        $api_essentials = $this->_get_api_essentials();
        $debug_info['api_credentials_set'] = !is_wp_error($api_essentials);
        
        // Check WooCommerce functions
        $debug_info['woocommerce_functions'] = [
            'get_woocommerce_currency' => function_exists('get_woocommerce_currency'),
            'wc_clear_notices' => function_exists('wc_clear_notices'),
            'WC' => class_exists('WC'),
        ];
        
        // Check server environment
        $debug_info['server_info'] = [
            'server_software' => isset($_SERVER['SERVER_SOFTWARE']) ? sanitize_text_field(wp_unslash($_SERVER['SERVER_SOFTWARE'])) : 'unknown',
            'php_sapi' => php_sapi_name(),
            'is_ssl' => is_ssl(),
            'site_url' => site_url(),
            'home_url' => home_url(),
        ];
        
        wp_send_json_success($debug_info);
    }
}

// Remove direct instantiation - now handled by ecwid2woo_check_woocommerce_dependency()
?>
