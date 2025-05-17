<?php
/*
Plugin Name: Ecwid2Woo Product Sync
Description: Easily Sync Ecwid Product Data (products, categories, images, skus, etc.) to WooCommerce.
Plugin URI: https://metrotechs.io/plugins/ecwid2woo-product-sync/
Author URI: https://metrotechs.io
Version: 1.9.2
Author: Metrotechs
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: ecwid2woo-product-sync
Domain Path: /languages
Requires at least: 5.0
Requires PHP: 7.2
WC requires at least: 3.0
WC tested up to: 8.8 
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

<<<<<<< HEAD
if (!defined('ECWID2WOO_VARIATION_BATCH_SIZE')) {
    define('ECWID2WOO_VARIATION_BATCH_SIZE', 20); // Number of variations to process per batch
}
=======
// --- Speculative Fix Attempt ---
// The error backtrace indicates a function `ecwid_wc_sync_init` (not defined in this file)
// is hooked to 'plugins_loaded' and seems to be causing a fatal error by trying to call
// AdminPage::init(). This is likely old or conflicting code.
// The following attempts to remove this action. This might not work if the original
// action was added with a different priority or is a class method.
// The best solution is to find and remove the source of `add_action('plugins_loaded', 'ecwid_wc_sync_init', ...)`
// from your WordPress installation (e.g., old plugin files, theme).
if (function_exists('ecwid_wc_sync_init')) {
    // Assuming default priority of 10. If it's different, this won't work.
    $removed = remove_action('plugins_loaded', 'ecwid_wc_sync_init', 10);
    // You could add some logging here if WP_DEBUG is on to see if removal was attempted/successful,
    // but fatal errors might prevent further execution anyway.
}
// --- End of Speculative Fix Attempt ---
>>>>>>> 5fd7a481a2475a16180fd1de27782e4603391e37

define('ECWID2WOO_VERSION', '1.9.2'); // Define version constant

class Ecwid_WC_Sync {
    private $options;
    private $sync_steps = ['categories', 'products']; // Define order of sync for full sync

    // Define slugs for the admin pages
    private $settings_slug = 'ecwid-sync-settings';
    private $full_sync_slug = 'ecwid-sync-full';
    private $partial_sync_slug = 'ecwid-sync-partial';
    private $category_sync_slug = 'ecwid-sync-categories';
<<<<<<< HEAD
=======
    private $customer_sync_slug = 'ecwid-sync-customers'; // New
    private $order_sync_slug = 'ecwid-sync-orders';     // New
>>>>>>> 5fd7a481a2475a16180fd1de27782e4603391e37

    public function __construct() {
        $this->load_textdomain(); // Load text domain
        $this->options = get_option('ecwid_wc_sync_options');
        add_action('init', [$this, 'register_placeholder_cpt']);
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'settings_init']); // This is line 34 from the error context
        add_action('wp_ajax_ecwid_wc_batch_sync', [$this, 'ajax_batch_sync']);
        add_action('wp_ajax_ecwid_wc_fetch_products_for_selection', [$this, 'ajax_fetch_products_for_selection']);
        add_action('wp_ajax_ecwid_wc_import_selected_products', [$this, 'ajax_import_selected_products']);
        add_action('wp_ajax_fix_category_hierarchy', [$this, 'fix_category_hierarchy']);
<<<<<<< HEAD
        add_action('wp_ajax_ecwid_wc_process_variation_batch', [$this, 'ajax_process_variation_batch']);
=======
        add_action('wp_ajax_ecwid_wc_customer_batch_sync', [$this, 'ajax_customer_batch_sync']); // New
        add_action('wp_ajax_ecwid_wc_order_batch_sync', [$this, 'ajax_order_batch_sync']);       // New
>>>>>>> 5fd7a481a2475a16180fd1de27782e4603391e37
    }

    public function load_textdomain() {
        load_plugin_textdomain(
            'ecwid2woo-product-sync',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages/'
        );
    }

    public function register_placeholder_cpt() {
        register_post_type('ecwid_placeholder', [
            'public' => false,
            'show_ui' => true,
            'labels' => [
                'name' => __('Ecwid Placeholders', 'ecwid2woo-product-sync'),
<<<<<<< HEAD
                'singular_name' => __('Ecwid Placeholder', 'ecwid2woo-product-sync'),
                'menu_name' => __('Placeholders', 'ecwid2woo-product-sync'), // Shorter menu name
            ],
            'supports' => ['title'],
            'rewrite' => false,
            'show_in_menu' => false, // Prevent automatic menu item creation
=======
                'singular_name' => __('Ecwid Placeholder', 'ecwid2woo-product-sync')
            ],
            'supports' => ['title'],
            'rewrite' => false,
>>>>>>> 5fd7a481a2475a16180fd1de27782e4603391e37
        ]);
    }

    public function add_admin_menu() {
        add_menu_page(
            __('Ecwid2Woo Product Sync Settings', 'ecwid2woo-product-sync'),
            __('Ecwid2Woo Sync', 'ecwid2woo-product-sync'), // Shorter menu title
            'manage_options',
            $this->settings_slug,
            [$this, 'options_page_router'],
            'dashicons-update-alt' // Changed icon slightly
        );

        add_submenu_page(
            $this->settings_slug,
            __('Ecwid2Woo Product Sync Settings', 'ecwid2woo-product-sync'),
            __('Settings', 'ecwid2woo-product-sync'),
            'manage_options',
<<<<<<< HEAD
            $this->settings_slug, // This makes "Settings" link to the main page
=======
            $this->settings_slug,
>>>>>>> 5fd7a481a2475a16180fd1de27782e4603391e37
            [$this, 'options_page_router']
        );

        add_submenu_page(
            $this->settings_slug,
            __('Full Data Sync', 'ecwid2woo-product-sync'),
            __('Full Sync', 'ecwid2woo-product-sync'),
            'manage_options',
            $this->full_sync_slug,
            [$this, 'options_page_router']
        );

        add_submenu_page(
            $this->settings_slug,
            __('Category Sync', 'ecwid2woo-product-sync'),
            __('Category Sync', 'ecwid2woo-product-sync'),
            'manage_options',
            $this->category_sync_slug,
            [$this, 'options_page_router']
        );

        add_submenu_page(
            $this->settings_slug,
<<<<<<< HEAD
            __('Selective Product Sync', 'ecwid2woo-product-sync'),
=======
            __('Selective Product Sync', 'ecwid2woo-product-sync'), // More descriptive title
>>>>>>> 5fd7a481a2475a16180fd1de27782e4603391e37
            __('Product Sync', 'ecwid2woo-product-sync'),
            'manage_options',
            $this->partial_sync_slug,
            [$this, 'options_page_router']
        );

<<<<<<< HEAD
        // Add the Placeholders CPT as the last submenu item
        add_submenu_page(
            $this->settings_slug,                         // Parent slug
            __('Ecwid Placeholders', 'ecwid2woo-product-sync'), // Page title
            __('Placeholders', 'ecwid2woo-product-sync'),  // Menu title (from CPT labels)
            'manage_options',                             // Capability
            'edit.php?post_type=ecwid_placeholder',       // Menu slug (links to CPT admin table)
            null                                          // Callback function (null for default CPT screen)
=======
        // New Submenu for Customer Sync
        add_submenu_page(
            $this->settings_slug,
            __('Customer Sync', 'ecwid2woo-product-sync'),
            __('Customer Sync', 'ecwid2woo-product-sync'),
            'manage_options',
            $this->customer_sync_slug,
            [$this, 'options_page_router']
        );

        // New Submenu for Order Sync
        add_submenu_page(
            $this->settings_slug,
            __('Order Sync', 'ecwid2woo-product-sync'),
            __('Order Sync', 'ecwid2woo-product-sync'),
            'manage_options',
            $this->order_sync_slug,
            [$this, 'options_page_router']
>>>>>>> 5fd7a481a2475a16180fd1de27782e4603391e37
        );
    }

    public function settings_init() {
        register_setting('ecwidSyncSettingsGroup', 'ecwid_wc_sync_options');

        add_settings_section(
            'ecwidSync_api_credentials_section',
            __('Ecwid API Credentials', 'ecwid2woo-product-sync'),
            '__return_false',
            $this->settings_slug
        );

        add_settings_field(
            'store_id',
            __('Ecwid Store ID', 'ecwid2woo-product-sync'),
            [$this, 'field_text'],
            $this->settings_slug,
            'ecwidSync_api_credentials_section',
            ['id' => 'store_id', 'label_for' => 'store_id', 'description' => __('Enter your Ecwid Store ID.', 'ecwid2woo-product-sync')]
        );

        add_settings_field(
            'token',
            __('Ecwid API Token (Secret Token)', 'ecwid2woo-product-sync'),
            [$this, 'field_text'],
            $this->settings_slug,
            'ecwidSync_api_credentials_section',
            ['id' => 'token', 'type' => 'password', 'label_for' => 'token', 'description' => __('Your Ecwid API Secret Token. This is sensitive information.', 'ecwid2woo-product-sync')]
        );
    }

    public function field_text($args) {
        $id = $args['id'];
        $type = $args['type'] ?? 'text';
        $description = $args['description'] ?? '';
        $value = isset($this->options[$id]) ? esc_attr($this->options[$id]) : '';
        echo "<input type='{$type}' id='$id' name='ecwid_wc_sync_options[$id]' value='$value' class='regular-text' />";
        if (!empty($description)) {
            echo '<p class="description">' . esc_html($description) . '</p>';
        }
    }

    public function options_page_router() {
        wp_enqueue_script('ecwid-wc-sync-admin', plugin_dir_url(__FILE__) . 'admin-sync.js', ['jquery', 'wp-i18n'], ECWID2WOO_VERSION, true);
        wp_localize_script('ecwid-wc-sync-admin', 'ecwid_sync_params', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('ecwid_wc_sync_nonce'),
<<<<<<< HEAD
            'sync_steps' => $this->sync_steps,
            'variation_batch_size' => defined('ECWID2WOO_VARIATION_BATCH_SIZE') ? ECWID2WOO_VARIATION_BATCH_SIZE : 10, // Pass batch size
=======
            'sync_steps' => $this->sync_steps, // Consider if customers/orders should be in full sync
>>>>>>> 5fd7a481a2475a16180fd1de27782e4603391e37
            'i18n' => [
                'sync_starting' => __('Sync starting...', 'ecwid2woo-product-sync'),
                'sync_complete' => __('Sync Complete!', 'ecwid2woo-product-sync'),
                'sync_error'    => __('Error during sync. Check console or log for details.', 'ecwid2woo-product-sync'),
                'ajax_error'    => __('AJAX Error. Check console or log for details.', 'ecwid2woo-product-sync'),
                'syncing'       => __('Syncing', 'ecwid2woo-product-sync'),
                'start_sync'    => __('Start Full Sync', 'ecwid2woo-product-sync'),
                'syncing_button'=> __('Syncing...', 'ecwid2woo-product-sync'),
                'load_products' => __('Load Ecwid Products for Selection', 'ecwid2woo-product-sync'),
                'loading_products' => __('Loading Products...', 'ecwid2woo-product-sync'),
                'import_selected' => __('Import Selected Products', 'ecwid2woo-product-sync'),
                'importing_selected' => __('Importing Selected...', 'ecwid2woo-product-sync'),
                'no_products_selected' => __('No products selected for import.', 'ecwid2woo-product-sync'),
                'select_all_none' => __('Select All/None', 'ecwid2woo-product-sync'),
                'no_products_found' => __('No enabled products found in Ecwid store or failed to fetch.', 'ecwid2woo-product-sync'),
                'start_category_sync_page' => __('Start Category Sync', 'ecwid2woo-product-sync'),
                'syncing_categories_page_button' => __('Syncing Categories...', 'ecwid2woo-product-sync'),
                'category_sync_page_complete' => __('Category Sync Complete!', 'ecwid2woo-product-sync'),
                'syncing_just_categories_page_status' => __('Syncing categories...', 'ecwid2woo-product-sync'),
                'fix_hierarchy_button' => __('Fix Category Hierarchy', 'ecwid2woo-product-sync'),
                'fixing_hierarchy' => __('Fixing hierarchy...', 'ecwid2woo-product-sync'),
                'hierarchy_fixed' => __('Category hierarchy fix attempt complete.', 'ecwid2woo-product-sync'),
<<<<<<< HEAD
                'importing_variations_status' => __('Importing variations for {productName} ({currentBatch} of {totalBatches})', 'ecwid2woo-product-sync'),
                'processing_variation_batch' => __('Processing variation batch...', 'ecwid2woo-product-sync'),
                'variations_imported_successfully' => __('All variations imported successfully for {productName}.', 'ecwid2woo-product-sync'),
                'error_importing_variations' => __('Error importing variations for {productName}. See log.', 'ecwid2woo-product-sync'),
                'parent_product_imported_pending_variations' => __('Parent product {productName} imported. Starting variation import...', 'ecwid2woo-product-sync'),
=======
                // New i18n strings
                'start_customer_sync' => __('Start Customer Sync', 'ecwid2woo-product-sync'),
                'syncing_customers' => __('Syncing Customers...', 'ecwid2woo-product-sync'),
                'customer_sync_complete' => __('Customer Sync Complete!', 'ecwid2woo-product-sync'),
                'start_order_sync' => __('Start Order Sync', 'ecwid2woo-product-sync'),
                'syncing_orders' => __('Syncing Orders...', 'ecwid2woo-product-sync'),
                'order_sync_complete' => __('Order Sync Complete!', 'ecwid2woo-product-sync'),
>>>>>>> 5fd7a481a2475a16180fd1de27782e4603391e37
            ]
        ]);

        $current_page_slug = isset($_GET['page']) ? sanitize_key($_GET['page']) : $this->settings_slug;

        echo '<div class="wrap">';
        // Page title is handled by WordPress or within render methods

        switch ($current_page_slug) {
            case $this->settings_slug:
                $this->render_settings_page();
                break;
            case $this->full_sync_slug:
                $this->render_full_sync_page();
                break;
            case $this->category_sync_slug:
                $this->render_category_sync_page();
                break;
            case $this->partial_sync_slug:
                $this->render_partial_sync_page();
                break;
            case $this->customer_sync_slug: // New
                $this->render_customer_sync_page();
                break;
            case $this->order_sync_slug:    // New
                $this->render_order_sync_page();
                break;
            default:
                $this->render_settings_page();
                break;
        }
        echo '</div>';
    }

    private function render_settings_page() {
        ?>
        <h1><?php esc_html_e('Ecwid Sync Settings', 'ecwid2woo-product-sync'); ?></h1>
        <form action='options.php' method='post'>
            <?php
            settings_fields('ecwidSyncSettingsGroup');
            do_settings_sections($this->settings_slug);
            submit_button(__('Save Settings', 'ecwid2woo-product-sync'));
            ?>
        </form>
        <?php
    }

    private function render_full_sync_page() {
        ?>
        <h1><?php esc_html_e('Full Data Sync', 'ecwid2woo-product-sync'); ?></h1>
        <p><?php esc_html_e('This will sync all categories and then all enabled products from Ecwid to WooCommerce. It is recommended to backup your WooCommerce data before running a full sync for the first time.', 'ecwid2woo-product-sync'); ?></p>
<<<<<<< HEAD
        
=======
>>>>>>> 5fd7a481a2475a16180fd1de27782e4603391e37
        <div id="full-sync-status" style="margin-bottom: 10px; font-weight: bold;"></div>
        
        <div style="margin-bottom: 5px;">
            <label for="full-sync-bar" style="display: block; margin-bottom: 2px; font-size: 0.9em;"><?php esc_html_e('Overall Progress:', 'ecwid2woo-product-sync'); ?></label>
            <div id="full-sync-progress-container" style="background: #f1f1f1; width: 100%; height: 24px; border: 1px solid #ccc; box-sizing: border-box;">
                <div id="full-sync-bar" style="background: #007cba; width: 0%; height: 100%; text-align: center; color: #fff; line-height: 22px; font-size: 12px; transition: width 0.2s ease-in-out;">0%</div>
            </div>
        </div>
<<<<<<< HEAD

        <div style="margin-top: 10px; margin-bottom: 10px;">
            <label for="full-sync-step-bar" style="display: block; margin-bottom: 2px; font-size: 0.9em;"><?php esc_html_e('Current Step Progress:', 'ecwid2woo-product-sync'); ?></label>
            <div id="full-sync-step-progress-container" style="background: #e0e0e0; width: 100%; height: 20px; border: 1px solid #bbb; box-sizing: border-box;">
                <div id="full-sync-step-bar" style="background: #4CAF50; width: 0%; height: 100%; text-align: center; color: #fff; line-height: 18px; font-size: 11px; transition: width 0.2s ease-in-out;">0%</div>
            </div>
        </div>
        
=======
>>>>>>> 5fd7a481a2475a16180fd1de27782e4603391e37
        <button id="full-sync-button" class="button button-primary"><?php esc_html_e('Start Full Sync', 'ecwid2woo-product-sync'); ?></button>
        <div id="full-sync-log" style="margin-top: 15px; max-height: 400px; overflow-y: auto; border: 1px solid #eee; padding: 10px; background: #fafafa; font-size: 0.9em; line-height: 1.6; white-space: pre-wrap;"></div>
        <?php
    }

    private function render_category_sync_page() {
        ?>
        <h1><?php esc_html_e('Ecwid Category Sync', 'ecwid2woo-product-sync'); ?></h1>
        <p><?php esc_html_e('This will sync all categories from Ecwid to WooCommerce. Products will not be affected by this operation. This is useful for ensuring categories are up-to-date before syncing products.', 'ecwid2woo-product-sync'); ?></p>
        <div id="category-page-sync-status" style="margin-bottom: 10px; font-weight: bold;"></div>
        <div id="category-page-sync-progress-container" style="background: #f1f1f1; width: 100%; height: 24px; margin-bottom: 10px; border: 1px solid #ccc; box-sizing: border-box;">
            <div id="category-page-sync-bar" style="background: #007cba; width: 0%; height: 100%; text-align: center; color: #fff; line-height: 22px; font-size: 12px; transition: width 0.2s ease-in-out;">0%</div>
        </div>
        <button id="category-page-sync-button" class="button button-primary"><?php esc_html_e('Start Category Sync', 'ecwid2woo-product-sync'); ?></button>
        <button id="fix-category-hierarchy-button" class="button" style="margin-left: 10px;"><?php esc_html_e('Fix Category Hierarchy', 'ecwid2woo-product-sync'); ?></button>
        <div id="category-page-sync-log" style="margin-top: 15px; max-height: 400px; overflow-y: auto; border: 1px solid #eee; padding: 10px; background: #fafafa; font-size: 0.9em; line-height: 1.6; white-space: pre-wrap;"></div>
        <?php
    }

    private function render_partial_sync_page() {
        ?>
        <h1><?php esc_html_e('Selective Product Sync', 'ecwid2woo-product-sync'); ?></h1>
        <p><?php esc_html_e('Load enabled products from Ecwid and select which ones to import or update.', 'ecwid2woo-product-sync'); ?></p>
        <button id="load-ecwid-products-button" class="button"><?php esc_html_e('Load Ecwid Products for Selection', 'ecwid2woo-product-sync'); ?></button>
        <div id="selective-product-list-container" style="margin-top: 15px; max-height: 400px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: #fff;">
            <?php esc_html_e('Product list will appear here...', 'ecwid2woo-product-sync'); ?>
        </div>
        <button id="import-selected-products-button" class="button button-primary" style="margin-top: 10px; display: none;"><?php esc_html_e('Import Selected Products', 'ecwid2woo-product-sync'); ?></button>

        <div id="selective-sync-status" style="margin-top:15px; margin-bottom: 10px; font-weight: bold;"></div>
        <div id="selective-sync-progress-container" style="background: #f1f1f1; width: 100%; height: 24px; margin-bottom: 10px; border: 1px solid #ccc; box-sizing: border-box; display:none;">
            <div id="selective-sync-bar" style="background: #007cba; width: 0%; height: 100%; text-align: center; color: #fff; line-height: 22px; font-size: 12px; transition: width 0.2s ease-in-out;">0%</div>
        </div>
        <div id="selective-sync-log" style="margin-top: 15px; max-height: 400px; overflow-y: auto; border: 1px solid #eee; padding: 10px; background: #fafafa; font-size: 0.9em; line-height: 1.6; white-space: pre-wrap;"></div>
        <?php
    }

    // New render method for Customer Sync page
    private function render_customer_sync_page() {
        ?>
        <h1><?php esc_html_e('Ecwid Customer Sync', 'ecwid2woo-product-sync'); ?></h1>
        <p><?php esc_html_e('This will sync customer data from Ecwid to WooCommerce. Ensure your API token has permissions for customer data.', 'ecwid2woo-product-sync'); ?></p>
        <div id="customer-sync-status" style="margin-bottom: 10px; font-weight: bold;"></div>
        <div id="customer-sync-progress-container" style="background: #f1f1f1; width: 100%; height: 24px; margin-bottom: 10px; border: 1px solid #ccc; box-sizing: border-box;">
            <div id="customer-sync-bar" style="background: #007cba; width: 0%; height: 100%; text-align: center; color: #fff; line-height: 22px; font-size: 12px; transition: width 0.2s ease-in-out;">0%</div>
        </div>
        <button id="customer-sync-button" class="button button-primary"><?php esc_html_e('Start Customer Sync', 'ecwid2woo-product-sync'); ?></button>
        <div id="customer-sync-log" style="margin-top: 15px; max-height: 400px; overflow-y: auto; border: 1px solid #eee; padding: 10px; background: #fafafa; font-size: 0.9em; line-height: 1.6; white-space: pre-wrap;"></div>
        <?php
    }

    // New render method for Order Sync page
    private function render_order_sync_page() {
        ?>
        <h1><?php esc_html_e('Ecwid Order Sync', 'ecwid2woo-product-sync'); ?></h1>
        <p><?php esc_html_e('This will sync order data (including payments, refunds if available) from Ecwid to WooCommerce. This can be a lengthy process for stores with many orders. Ensure API token has order permissions.', 'ecwid2woo-product-sync'); ?></p>
        <div id="order-sync-status" style="margin-bottom: 10px; font-weight: bold;"></div>
        <div id="order-sync-progress-container" style="background: #f1f1f1; width: 100%; height: 24px; margin-bottom: 10px; border: 1px solid #ccc; box-sizing: border-box;">
            <div id="order-sync-bar" style="background: #007cba; width: 0%; height: 100%; text-align: center; color: #fff; line-height: 22px; font-size: 12px; transition: width 0.2s ease-in-out;">0%</div>
        </div>
        <button id="order-sync-button" class="button button-primary"><?php esc_html_e('Start Order Sync', 'ecwid2woo-product-sync'); ?></button>
        <div id="order-sync-log" style="margin-top: 15px; max-height: 400px; overflow-y: auto; border: 1px solid #eee; padding: 10px; background: #fafafa; font-size: 0.9em; line-height: 1.6; white-space: pre-wrap;"></div>
        <?php
    }

    private function _get_api_essentials() {
        $store_id = isset($this->options['store_id']) ? sanitize_text_field($this->options['store_id']) : '';
        $token    = isset($this->options['token']) ? sanitize_text_field($this->options['token']) : '';

        if (empty($store_id) || empty($token)) {
            return new WP_Error('missing_credentials', __('Ecwid Store ID and API Token must be configured in plugin settings.', 'ecwid2woo-product-sync'));
        }
        return ['store_id' => $store_id, 'token' => $token, 'base_url' => "https://app.ecwid.com/api/v3/{$store_id}"];
    }

    public function ajax_fetch_products_for_selection() {
        check_ajax_referer('ecwid_wc_sync_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized', 'ecwid2woo-product-sync')]);
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
        $limit = 100;

        do {
            $query_params = [
                'limit' => $limit,
                'offset' => $offset,
                'enabled' => 'true',
<<<<<<< HEAD
                // MODIFIED: Fetch options and combination IDs
                'responseFields' => 'items(id,sku,name,enabled,options,combinations(id))' 
=======
                'responseFields' => 'items(id,sku,name,enabled)'
>>>>>>> 5fd7a481a2475a16180fd1de27782e4603391e37
            ];
            $api_url = add_query_arg($query_params, $api_essentials['base_url'] . '/products');

            $response = wp_remote_get($api_url, [
                'timeout' => 60,
                'headers' => ['Authorization' => 'Bearer ' . $api_essentials['token'], 'Accept' => 'application/json'],
            ]);

            if (is_wp_error($response)) {
                wp_send_json_error(['message' => sprintf(__('API Request Error: %s', 'ecwid2woo-product-sync'), $response->get_error_message())]);
                return;
            }

            $body = json_decode(wp_remote_retrieve_body($response), true);
            $http_code = wp_remote_retrieve_response_code($response);

            if ($http_code !== 200 || (isset($body['errorMessage']) && !empty($body['errorMessage']))) {
                wp_send_json_error(['message' => sprintf(__('Ecwid API Error (HTTP %s): %s', 'ecwid2woo-product-sync'), $http_code, ($body['errorMessage'] ?? 'Unknown error'))]);
                return;
            }

            if (isset($body['items']) && is_array($body['items'])) {
                foreach ($body['items'] as $item) {
<<<<<<< HEAD
                    // Ensure 'enabled' check is still relevant if API guarantees it
                    // if (isset($item['enabled']) && $item['enabled']) { 
=======
                    if (isset($item['enabled']) && $item['enabled']) {
>>>>>>> 5fd7a481a2475a16180fd1de27782e4603391e37
                        $all_products[] = [
                            'id' => $item['id'] ?? null,
                            'name' => $item['name'] ?? 'N/A',
                            'sku' => $item['sku'] ?? 'N/A',
<<<<<<< HEAD
                            'enabled' => $item['enabled'] ?? false, // Ensure default
                            'options' => $item['options'] ?? [], // Add options
                            'combinations' => $item['combinations'] ?? [] // Add combinations (array of {id:val})
=======
                            'enabled' => $item['enabled']
>>>>>>> 5fd7a481a2475a16180fd1de27782e4603391e37
                        ];
                    // }
                }
            }

            $count_in_response = $body['count'] ?? 0;
            $total_from_api = $body['total'] ?? 0;
            $offset += $count_in_response;

        } while ($count_in_response > 0 && $offset < $total_from_api);

        wp_send_json_success(['products' => $all_products, 'total_found' => count($all_products)]);
    }

    public function ajax_import_selected_products() {
        check_ajax_referer('ecwid_wc_sync_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized', 'ecwid2woo-product-sync')]);
            return;
        }
<<<<<<< HEAD
        set_time_limit(0); // Try to disable time limit for this initial product fetch and parent import
=======
        set_time_limit(300);
>>>>>>> 5fd7a481a2475a16180fd1de27782e4603391e37

        $api_essentials = $this->_get_api_essentials();
        if (is_wp_error($api_essentials)) {
            wp_send_json_error(['message' => $api_essentials->get_error_message()]);
            return;
        }

        $ecwid_product_id = isset($_POST['ecwid_product_id']) ? intval($_POST['ecwid_product_id']) : 0;

        if (empty($ecwid_product_id)) {
            wp_send_json_error(['message' => __('No Ecwid Product ID provided for import.', 'ecwid2woo-product-sync')]);
            return;
        }

        $query_params = ['responseFields' => 'id,sku,name,price,description,shortDescription,enabled,weight,quantity,unlimited,categoryIds,hdThumbnailUrl,imageUrl,galleryImages,options,combinations,productClassId,attributes,compareToPrice,dimensions,shipping'];
        $api_url = add_query_arg($query_params, $api_essentials['base_url'] . '/products/' . $ecwid_product_id);

        $response = wp_remote_get($api_url, [
            'timeout' => 120,
            'headers' => ['Authorization' => 'Bearer ' . $api_essentials['token'], 'Accept' => 'application/json'],
        ]);

        if (is_wp_error($response)) {
            wp_send_json_error(['message' => sprintf(__('API Request Error for product %s: %s', 'ecwid2woo-product-sync'), $ecwid_product_id, $response->get_error_message())]);
            return;
        }

        $item_data = json_decode(wp_remote_retrieve_body($response), true);
        $http_code = wp_remote_retrieve_response_code($response);

        if ($http_code !== 200 || (isset($item_data['errorMessage']) && !empty($item_data['errorMessage']))) {
            wp_send_json_error(['message' => sprintf(__('Ecwid API Error for product %s (HTTP %s): %s', 'ecwid2woo-product-sync'), $ecwid_product_id, $http_code, ($item_data['errorMessage'] ?? 'Unknown error'))]);
            return;
        }

        if (empty($item_data) || !isset($item_data['id'])) {
             wp_send_json_error(['message' => sprintf(__('Failed to fetch valid data for Ecwid product ID %s.', 'ecwid2woo-product-sync'), $ecwid_product_id)]);
            return;
        }

        $result_array = $this->import_product($item_data);

        if (isset($result_array['status']) && $result_array['status'] === 'imported_parent_pending_variations') {
            wp_send_json_success([
                'status'           => 'variations_pending', // New status for JS
                'message'          => __('Parent product imported. Variations will be processed in batches.', 'ecwid2woo-product-sync'),
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
                'message'    => __('An unexpected error occurred during product import.', 'ecwid2woo-product-sync'),
                'item_name'  => ($item_data['name'] ?? 'N/A'),
                'ecwid_id'   => $ecwid_product_id,
                'sku'        => ($item_data['sku'] ?? 'N/A'),
                'logs'       => $result_array['logs'] ?? ['[CRITICAL] Unexpected result from import_product function.'],
                'raw_result' => $result_array 
            ]);
        }
    }

    public function ajax_batch_sync() {
        check_ajax_referer('ecwid_wc_sync_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized', 'ecwid2woo-product-sync')]); return;
        }
        set_time_limit(300);

        $api_essentials = $this->_get_api_essentials();
        if (is_wp_error($api_essentials)) {
            wp_send_json_error(['message' => $api_essentials->get_error_message()]); return;
        }

<<<<<<< HEAD
        // MODIFICATION: Change the default batch size from 10 to a smaller number, e.g., 5.
        // This will fetch and process fewer items per AJAX call, leading to more frequent updates.
        $limit_per_api_call = apply_filters('ecwid_wc_sync_batch_api_limit', 5); // Changed from 10 to 5
=======
        $limit_per_api_call = apply_filters('ecwid_wc_sync_batch_api_limit', 10);
>>>>>>> 5fd7a481a2475a16180fd1de27782e4603391e37
        $sync_type = isset($_POST['sync_type']) ? sanitize_text_field($_POST['sync_type']) : '';
        $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Ecwid Sync: FULL BATCH - Type: $sync_type, Offset: $offset, API Limit: $limit_per_api_call");
        }

        $endpoints = ['products' => '/products', 'categories' => '/categories'];
        if (!isset($endpoints[$sync_type])) {
            wp_send_json_error(['message' => __('Invalid sync type for full sync.', 'ecwid2woo-product-sync')]); return;
        }

        $endpoint = $endpoints[$sync_type];
        $api_url_base = $api_essentials['base_url'] . $endpoint;
        $query_params_for_url = ['limit' => $limit_per_api_call, 'offset' => $offset];

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
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Ecwid Sync: API Request WP_Error for $sync_type: " . $response->get_error_message());
            }
            wp_send_json_error(['message' => sprintf(__('API Request Error: %s', 'ecwid2woo-product-sync'), $response->get_error_message())]); return;
        }

        $raw_response_body = wp_remote_retrieve_body($response);
        $body = json_decode($raw_response_body, true);
        $http_code = wp_remote_retrieve_response_code($response);

        if ($http_code !== 200 || !is_array($body) || (isset($body['errorMessage']) && !empty($body['errorMessage']))) {
            $error_message = sprintf(__('Ecwid API Error (HTTP %s): %s', 'ecwid2woo-product-sync'), $http_code, ($body['errorMessage'] ?? 'Unknown error or invalid response format'));
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Ecwid Sync: API Error for $sync_type. HTTP Code: $http_code. Raw Body: " . $raw_response_body);
            }
            wp_send_json_error(['message' => $error_message, 'details' => is_array($body) ? $body : ['raw_response' => $raw_response_body]]); return;
        }

        $items_from_api = [];
        if (isset($body['items']) && is_array($body['items'])) {
            $items_from_api = $body['items'];
        } elseif ($sync_type === 'categories' && !isset($body['total']) && !isset($body['count'])) {
            if(is_array($body) && (empty($body) || isset($body[0]['id']))) {
                $items_from_api = $body;
            } else {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("Ecwid Sync: Categories API response for $sync_type was not in expected 'items' wrapper and not a direct array of categories. Raw Body: " . $raw_response_body);
                }
            }
        }

        $total_items_reported_by_api = $body['total'] ?? count($items_from_api);
        $count_in_current_api_response = $body['count'] ?? count($items_from_api);

        $imported_count = 0; $skipped_count = 0; $failed_count = 0;
        $batch_detailed_logs = [];

        if (!empty($items_from_api)) {
            foreach ($items_from_api as $item_data) {
                if (!is_array($item_data) || !isset($item_data['id'])) {
                    $batch_detailed_logs[] = "--- [CRITICAL ERROR] Encountered invalid item in API response for $sync_type. Skipping. Item data: " . print_r($item_data, true) . " ---";
                    $failed_count++;
                    continue;
                }

                $result_array = null;
                $item_identifier_for_log = ($sync_type === 'products' ? "Product" : "Category") . " (Ecwid ID: " . ($item_data['id'] ?? 'N/A') . ")";

                try {
                    switch ($sync_type) {
                        case 'products':
                            $result_array = $this->import_product($item_data);
                            break;
                        case 'categories':
                            $result_array = $this->import_category($item_data);
                            break;
                    }

                    if ($result_array && isset($result_array['status'])) {
                        if ($result_array['status'] === 'imported') $imported_count++;
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
                        $batch_detailed_logs[] = "--- [CRITICAL ERROR] Failed to process item: " . esc_html($item_identifier_for_log) . ". Import function did not return expected result or status. Result: " . print_r($result_array, true) . " ---";
                    }
                } catch (Exception $e) {
                    $failed_count++;
                    $batch_detailed_logs[] = "--- [PHP EXCEPTION] During processing of " . esc_html($item_identifier_for_log) . ": " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine() . " ---";
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log("Ecwid Sync: PHP Exception during $sync_type import: " . $e->getMessage() . " Trace: " . $e->getTraceAsString());
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
            'message' => sprintf(__('%1$s: Processed %2$d items fetched in this API call (Imported: %3$d, Skipped: %4$d, Failed: %5$d). Total items for this type (Ecwid reported): %6$d.', 'ecwid2woo-product-sync'), ucfirst($sync_type), count($items_from_api), $imported_count, $skipped_count, $failed_count, $total_items_reported_by_api),
            'next_offset' => $new_offset,
            'total_items' => $total_items_reported_by_api,
            'has_more' => $has_more,
            'processed_type' => $sync_type,
            'batch_logs' => $batch_detailed_logs
        ]);
    }

<<<<<<< HEAD
=======
    // New AJAX handler for Customer Batch Sync
    public function ajax_customer_batch_sync() {
        check_ajax_referer('ecwid_wc_sync_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized', 'ecwid2woo-product-sync')]); return;
        }
        set_time_limit(300); // Potentially increase for customer data

        $api_essentials = $this->_get_api_essentials();
        if (is_wp_error($api_essentials)) {
            wp_send_json_error(['message' => $api_essentials->get_error_message()]); return;
        }

        $limit_per_api_call = apply_filters('ecwid_wc_sync_customer_api_limit', 50); // Customers might be lighter
        $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
        $sync_type = 'customers'; // Fixed for this handler

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Ecwid Sync: CUSTOMER BATCH - Offset: $offset, API Limit: $limit_per_api_call");
        }

        $api_url_base = $api_essentials['base_url'] . '/customers';
        // Define desired fields for customers to optimize API calls
        $responseFields = 'items(id,email,billingPerson,shippingPerson,registered,updatedDate,customerGroupId,taxExempt,taxId)'; // Add more as needed
        $query_params_for_url = ['limit' => $limit_per_api_call, 'offset' => $offset, 'responseFields' => $responseFields];

        $api_url = add_query_arg($query_params_for_url, $api_url_base);
        $response = wp_remote_get($api_url, [
            'timeout' => 60,
            'headers' => ['Authorization' => 'Bearer ' . $api_essentials['token'], 'Accept' => 'application/json'],
        ]);

        if (is_wp_error($response)) {
            wp_send_json_error(['message' => sprintf(__('API Request Error: %s', 'ecwid2woo-product-sync'), $response->get_error_message())]); return;
        }
        $raw_response_body = wp_remote_retrieve_body($response);
        $body = json_decode($raw_response_body, true);
        $http_code = wp_remote_retrieve_response_code($response);

        if ($http_code !== 200 || !is_array($body) || (isset($body['errorMessage']) && !empty($body['errorMessage']))) {
            wp_send_json_error(['message' => sprintf(__('Ecwid API Error (HTTP %s): %s', 'ecwid2woo-product-sync'), $http_code, ($body['errorMessage'] ?? 'Unknown error'))]); return;
        }
        
        $items_from_api = $body['items'] ?? [];
        $total_items_reported_by_api = $body['total'] ?? 0;
        $count_in_current_api_response = $body['count'] ?? count($items_from_api);

        $imported_count = 0; $skipped_count = 0; $failed_count = 0;
        $batch_detailed_logs = [];

        if (!empty($items_from_api)) {
            foreach ($items_from_api as $item_data) {
                if (!is_array($item_data) || !isset($item_data['id']) || !isset($item_data['email'])) {
                    $batch_detailed_logs[] = "--- [CRITICAL ERROR] Encountered invalid customer data in API response. Skipping. Item data: " . print_r($item_data, true) . " ---";
                    $failed_count++;
                    continue;
                }
                $result_array = $this->import_customer($item_data);
                // ... (Log handling similar to ajax_batch_sync for products/categories) ...
                if ($result_array && isset($result_array['status'])) {
                    if ($result_array['status'] === 'imported') $imported_count++;
                    elseif ($result_array['status'] === 'skipped') $skipped_count++;
                    else $failed_count++;

                    $log_item_name = esc_html($result_array['item_name'] ?? ('Customer ' . $item_data['email']));
                    $log_ecwid_id = esc_html($result_array['ecwid_id'] ?? $item_data['id']);

                    $batch_detailed_logs[] = "--- Processing: {$log_item_name} (Ecwid ID: {$log_ecwid_id}) ---";
                    if (!empty($result_array['logs']) && is_array($result_array['logs'])) {
                        foreach($result_array['logs'] as $log_line) { $batch_detailed_logs[] = "  " . esc_html($log_line); }
                    }
                    $batch_detailed_logs[] = "--- Result for {$log_ecwid_id}: " . strtoupper($result_array['status']) . " ---";
                } else {
                    $failed_count++;
                     $batch_detailed_logs[] = "--- [CRITICAL ERROR] Failed to process customer: " . esc_html($item_data['email']) . ". Import function did not return expected result. ---";
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
            'message' => sprintf(__('%1$s: Processed %2$d items (Imported: %3$d, Skipped: %4$d, Failed: %5$d). Total (Ecwid): %6$d.', 'ecwid2woo-product-sync'), ucfirst($sync_type), count($items_from_api), $imported_count, $skipped_count, $failed_count, $total_items_reported_by_api),
            'next_offset' => $new_offset,
            'total_items' => $total_items_reported_by_api,
            'has_more' => $has_more,
            'processed_type' => $sync_type,
            'batch_logs' => $batch_detailed_logs
        ]);
    }

    // New AJAX handler for Order Batch Sync
    public function ajax_order_batch_sync() {
        check_ajax_referer('ecwid_wc_sync_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized', 'ecwid2woo-product-sync')]); return;
        }
        set_time_limit(600); // Orders can be very heavy

        $api_essentials = $this->_get_api_essentials();
        if (is_wp_error($api_essentials)) {
            wp_send_json_error(['message' => $api_essentials->get_error_message()]); return;
        }

        $limit_per_api_call = apply_filters('ecwid_wc_sync_order_api_limit', 10); // Orders are complex
        $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
        $sync_type = 'orders'; // Fixed

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Ecwid Sync: ORDER BATCH - Offset: $offset, API Limit: $limit_per_api_call");
        }
        
        $api_url_base = $api_essentials['base_url'] . '/orders';
        // Define desired fields for orders - this needs to be comprehensive
        $responseFields = 'items(orderNumber,vendorOrderNumber,customerId,email,customerGroup,ipAddress,paymentMethod,paymentModule,paymentStatus,fulfillmentStatus,orderComments,privateAdminNotes,items(productId,sku,name,price,quantity,options,taxes),billingPerson,shippingPerson,total,subtotal,tax,shipping,couponDiscount,volumeDiscount,discount,handlingFee,createDate,updateDate,refundedAmount,paymentParams,taxesOnShipping)'; // Highly detailed
        $query_params_for_url = [
            'limit' => $limit_per_api_call, 
            'offset' => $offset, 
            'responseFields' => $responseFields,
            'sortBy' => 'UPDATE_DATE_DESC' // Or CREATION_DATE_ASC/DESC as preferred
        ];

        $api_url = add_query_arg($query_params_for_url, $api_url_base);
        $response = wp_remote_get($api_url, [
            'timeout' => 120, // Longer timeout for orders
            'headers' => ['Authorization' => 'Bearer ' . $api_essentials['token'], 'Accept' => 'application/json'],
        ]);

        if (is_wp_error($response)) {
            wp_send_json_error(['message' => sprintf(__('API Request Error: %s', 'ecwid2woo-product-sync'), $response->get_error_message())]); return;
        }
        $raw_response_body = wp_remote_retrieve_body($response);
        $body = json_decode($raw_response_body, true);
        $http_code = wp_remote_retrieve_response_code($response);

        if ($http_code !== 200 || !is_array($body) || (isset($body['errorMessage']) && !empty($body['errorMessage']))) {
            wp_send_json_error(['message' => sprintf(__('Ecwid API Error (HTTP %s): %s', 'ecwid2woo-product-sync'), $http_code, ($body['errorMessage'] ?? 'Unknown error'))]); return;
        }

        $items_from_api = $body['items'] ?? [];
        $total_items_reported_by_api = $body['total'] ?? 0;
        $count_in_current_api_response = $body['count'] ?? count($items_from_api);
        
        $imported_count = 0; $skipped_count = 0; $failed_count = 0;
        $batch_detailed_logs = [];

        if (!empty($items_from_api)) {
            foreach ($items_from_api as $item_data) {
                 if (!is_array($item_data) || !isset($item_data['orderNumber'])) { // Ecwid uses orderNumber as a key identifier
                    $batch_detailed_logs[] = "--- [CRITICAL ERROR] Encountered invalid order data in API response. Skipping. Item data: " . print_r($item_data, true) . " ---";
                    $failed_count++;
                    continue;
                }
                $result_array = $this->import_order($item_data);
                // ... (Log handling similar to other batch syncs) ...
                if ($result_array && isset($result_array['status'])) {
                    if ($result_array['status'] === 'imported') $imported_count++;
                    elseif ($result_array['status'] === 'skipped') $skipped_count++;
                    else $failed_count++;

                    $log_item_name = esc_html($result_array['item_name'] ?? ('Order #' . $item_data['orderNumber']));
                    $log_ecwid_id = esc_html($result_array['ecwid_id'] ?? $item_data['orderNumber']); // Using orderNumber as a proxy for Ecwid ID here for logs

                    $batch_detailed_logs[] = "--- Processing: {$log_item_name} (Ecwid OrderNumber: {$log_ecwid_id}) ---";
                    if (!empty($result_array['logs']) && is_array($result_array['logs'])) {
                        foreach($result_array['logs'] as $log_line) { $batch_detailed_logs[] = "  " . esc_html($log_line); }
                    }
                    $batch_detailed_logs[] = "--- Result for {$log_ecwid_id}: " . strtoupper($result_array['status']) . " ---";
                } else {
                    $failed_count++;
                     $batch_detailed_logs[] = "--- [CRITICAL ERROR] Failed to process order: " . esc_html('Order #' . $item_data['orderNumber']) . ". Import function did not return expected result. ---";
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
            'message' => sprintf(__('%1$s: Processed %2$d items (Imported: %3$d, Skipped: %4$d, Failed: %5$d). Total (Ecwid): %6$d.', 'ecwid2woo-product-sync'), ucfirst($sync_type), count($items_from_api), $imported_count, $skipped_count, $failed_count, $total_items_reported_by_api),
            'next_offset' => $new_offset,
            'total_items' => $total_items_reported_by_api,
            'has_more' => $has_more,
            'processed_type' => $sync_type,
            'batch_logs' => $batch_detailed_logs
        ]);
    }

>>>>>>> 5fd7a481a2475a16180fd1de27782e4603391e37
    private function import_category($item) {
        $category_logs = [];
        $ecwid_cat_id = $item['id'] ?? null;
        $ecwid_cat_name = isset($item['name']) ? sanitize_text_field($item['name']) : null;

        $item_name_for_return = $ecwid_cat_name ?? '[No Name]';
        $ecwid_id_for_return = $ecwid_cat_id ?? 'N/A';

        try {
            if (!$ecwid_cat_id || !$ecwid_cat_name) {
                $category_logs[] = "[CRITICAL] Category missing ID or Name. Ecwid ID: $ecwid_id_for_return, Name: $item_name_for_return.";
                return ['status' => 'failed', 'logs' => $category_logs, 'item_name' => $item_name_for_return, 'ecwid_id' => $ecwid_id_for_return];
            }
            $category_logs[] = "Starting import for Category: \"$ecwid_cat_name\" (Ecwid ID: $ecwid_cat_id)";

            $args = [];
            if (isset($item['description'])) $args['description'] = wp_kses_post($item['description']);

            $parent_wc_term_id = 0;
            if (isset($item['parentId']) && intval($item['parentId']) > 0) {
                $parent_ecwid_id = intval($item['parentId']);
                $parent_wc_term_id_found = $this->get_term_id_by_ecwid_id($parent_ecwid_id, 'product_cat', true);

                if ($parent_wc_term_id_found) {
                    $args['parent'] = $parent_wc_term_id_found;
                    $parent_wc_term_id = $parent_wc_term_id_found;
                    $category_logs[] = "Parent category (Ecwid ID: $parent_ecwid_id) mapped to WC Term ID: {$args['parent']}.";
                } else {
                    $missing_parent_placeholder = $this->get_or_create_missing_parent_placeholder($parent_ecwid_id);
                    if ($missing_parent_placeholder) {
                        $args['parent'] = $missing_parent_placeholder['term_id'];
                        $parent_wc_term_id = $missing_parent_placeholder['term_id'];
                        $category_logs[] = $missing_parent_placeholder['is_new']
                            ? "Created placeholder parent category '{$missing_parent_placeholder['name']}' (WC Term ID: {$missing_parent_placeholder['term_id']}) for missing Ecwid parent ID: $parent_ecwid_id."
                            : "Using existing placeholder parent category '{$missing_parent_placeholder['name']}' (WC Term ID: {$missing_parent_placeholder['term_id']}) for Ecwid parent ID: $parent_ecwid_id.";
                    } else {
                        $category_logs[] = "[WARNING] Parent category (Ecwid ID: $parent_ecwid_id) not yet imported or found in WC. This category will be top-level for now.";
                        $this->register_missing_parent($parent_ecwid_id, $ecwid_cat_id);
                    }
                }
            }

            $existing_wc_term_id_by_ecwid_meta = $this->get_term_id_by_ecwid_id($ecwid_cat_id, 'product_cat', true);

            if ($existing_wc_term_id_by_ecwid_meta) {
                $category_logs[] = "Existing WC Term ID $existing_wc_term_id_by_ecwid_meta found linked to Ecwid ID $ecwid_cat_id. Updating...";
                $update_args = ['name' => wp_slash($ecwid_cat_name)];
                if (isset($args['description'])) $update_args['description'] = $args['description'];

                $current_term_data = get_term($existing_wc_term_id_by_ecwid_meta, 'product_cat');
                if ($current_term_data && $current_term_data->parent != $parent_wc_term_id) {
                    $update_args['parent'] = $parent_wc_term_id;
                    $category_logs[] = "Updating parent for WC Term ID $existing_wc_term_id_by_ecwid_meta. Old parent: {$current_term_data->parent}, New parent target: $parent_wc_term_id.";
                } elseif ($current_term_data) {
                    $category_logs[] = "Parent for WC Term ID $existing_wc_term_id_by_ecwid_meta is already {$current_term_data->parent}, matches target $parent_wc_term_id. No parent update needed.";
                }

                $update_result = wp_update_term($existing_wc_term_id_by_ecwid_meta, 'product_cat', $update_args);

                if (is_wp_error($update_result)) {
                    $category_logs[] = "[ERROR] Failed to update existing WC category (ID: $existing_wc_term_id_by_ecwid_meta): " . $update_result->get_error_message();
                    return ['status' => 'failed', 'logs' => $category_logs, 'item_name' => $item_name_for_return, 'ecwid_id' => $ecwid_id_for_return];
                }
                clean_term_cache($existing_wc_term_id_by_ecwid_meta, 'product_cat');
                $category_logs[] = "Updated successfully (WC Term ID: $existing_wc_term_id_by_ecwid_meta). Cache cleaned.";
                return ['status' => 'imported', 'logs' => $category_logs, 'item_name' => $item_name_for_return, 'ecwid_id' => $ecwid_id_for_return];
            }

            $term_by_name_result = term_exists($ecwid_cat_name, 'product_cat', $args['parent'] ?? 0);
            if ($term_by_name_result) {
                $wc_term_id_found_by_name = is_array($term_by_name_result) ? $term_by_name_result['term_id'] : $term_by_name_result;
                $meta_ecwid_id_on_named_term = get_term_meta($wc_term_id_found_by_name, '_ecwid_category_id', true);

                if ($meta_ecwid_id_on_named_term && $meta_ecwid_id_on_named_term != $ecwid_cat_id) {
                    $category_logs[] = "[WARNING] Conflict: WC Term ID $wc_term_id_found_by_name (Name: '$ecwid_cat_name') is already linked to a different Ecwid ID '$meta_ecwid_id_on_named_term'. Cannot link to current Ecwid ID '$ecwid_cat_id'. Please resolve naming conflict or manually link.";
                    return ['status' => 'failed', 'logs' => $category_logs, 'item_name' => $item_name_for_return, 'ecwid_id' => $ecwid_id_for_return];
                } elseif (!$meta_ecwid_id_on_named_term) {
                    $category_logs[] = "Existing WC term (ID: $wc_term_id_found_by_name, Name: '$ecwid_cat_name') found by name. Linking to Ecwid ID $ecwid_cat_id and updating details.";
                    $update_args_for_named = ['name' => wp_slash($ecwid_cat_name)];
                    if (isset($args['description'])) $update_args_for_named['description'] = $args['description'];
                    if (isset($args['parent'])) $update_args_for_named['parent'] = $args['parent'];

                    $update_named_result = wp_update_term($wc_term_id_found_by_name, 'product_cat', $update_args_for_named);

                    if (is_wp_error($update_named_result)) {
                         $category_logs[] = "[ERROR] Failed to update details for WC term (ID: $wc_term_id_found_by_name) found by name: " . $update_named_result->get_error_message();
                    }

                    $meta_update_result = update_term_meta($wc_term_id_found_by_name, '_ecwid_category_id', $ecwid_cat_id);
                    if ($meta_update_result) {
                        clean_term_cache($wc_term_id_found_by_name, 'product_cat');
                        $category_logs[] = "Successfully linked and updated WC term (ID: $wc_term_id_found_by_name) to Ecwid ID $ecwid_cat_id. Meta update successful. Cache cleaned.";
                    } else {
                        $category_logs[] = "[ERROR] FAILED to link WC term (ID: $wc_term_id_found_by_name) to Ecwid ID $ecwid_cat_id (update_term_meta failed).";
                        return ['status' => 'failed', 'logs' => $category_logs, 'item_name' => $item_name_for_return, 'ecwid_id' => $ecwid_id_for_return];
                    }
                    return ['status' => 'imported', 'logs' => $category_logs, 'item_name' => $item_name_for_return, 'ecwid_id' => $ecwid_id_for_return];
                }
                 $category_logs[] = "Skipped. WC Term ID $wc_term_id_found_by_name (Name: '$ecwid_cat_name') appears already correctly linked to Ecwid ID $ecwid_cat_id (found by name).";
                 return ['status' => 'skipped', 'logs' => $category_logs, 'item_name' => $item_name_for_return, 'ecwid_id' => $ecwid_id_for_return];
            }

            $new_term_result = wp_insert_term(wp_slash($ecwid_cat_name), 'product_cat', $args);

            if (is_wp_error($new_term_result)) {
                $category_logs[] = '[ERROR] Failed to insert new WC category: ' . $new_term_result->get_error_message();
                return ['status' => 'failed', 'logs' => $category_logs, 'item_name' => $item_name_for_return, 'ecwid_id' => $ecwid_id_for_return];
            }

            if (isset($new_term_result['term_id'])) {
                $meta_update_result = update_term_meta($new_term_result['term_id'], '_ecwid_category_id', $ecwid_cat_id);
                if ($meta_update_result) {
                    clean_term_cache($new_term_result['term_id'], 'product_cat');
                    $category_logs[] = "Imported successfully (New WC Term ID: {$new_term_result['term_id']}). Meta update successful. Cache cleaned.";
                } else {
                     $category_logs[] = "[ERROR] Imported successfully (New WC Term ID: {$new_term_result['term_id']}). BUT FAILED to set _ecwid_category_id meta (update_term_meta failed).";
                     return ['status' => 'failed', 'logs' => $category_logs, 'item_name' => $item_name_for_return, 'ecwid_id' => $ecwid_id_for_return];
                }
                return ['status' => 'imported', 'logs' => $category_logs, 'item_name' => $item_name_for_return, 'ecwid_id' => $ecwid_id_for_return];
            }

            $category_logs[] = "[ERROR] wp_insert_term did not return term_id after attempting to create '$ecwid_cat_name'.";
            return ['status' => 'failed', 'logs' => $category_logs, 'item_name' => $item_name_for_return, 'ecwid_id' => $ecwid_id_for_return];

        } catch (Exception $e) {
            $category_logs[] = "[PHP EXCEPTION] During category import for Ecwid ID $ecwid_id_for_return: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine();
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Ecwid Sync: PHP Exception during category import for Ecwid ID $ecwid_id_for_return: " . $e->getMessage() . " Trace: " . $e->getTraceAsString());
            }
            return ['status' => 'failed', 'logs' => $category_logs, 'item_name' => $item_name_for_return, 'ecwid_id' => $ecwid_id_for_return];
        }
    }

    private function import_customer($item_data) {
        $customer_logs = [];
        $ecwid_customer_id = $item_data['id'] ?? null;
        $email = $item_data['email'] ?? null;

        if (!$ecwid_customer_id || !$email) {
            $customer_logs[] = "[CRITICAL] Customer data missing Ecwid ID or Email.";
            return ['status' => 'failed', 'logs' => $customer_logs, 'item_name' => ($email ?? 'Unknown'), 'ecwid_id' => ($ecwid_customer_id ?? 'N/A')];
        }
        $customer_logs[] = "Starting import for Customer: $email (Ecwid ID: $ecwid_customer_id)";

        $user_id = null;
        // Try to find existing user by Ecwid customer ID meta
        $user_query_by_meta = new WP_User_Query([
            'meta_key' => '_ecwid_customer_id',
            'meta_value' => $ecwid_customer_id,
            'number' => 1,
            'fields' => 'ID',
        ]);
        if (!empty($user_query_by_meta->get_results())) {
            $user_id = $user_query_by_meta->get_results()[0];
            $customer_logs[] = "Found existing WC User ID $user_id by _ecwid_customer_id meta.";
        }

        // If not found by meta, try by email
        if (!$user_id) {
            $existing_user = get_user_by('email', $email);
            if ($existing_user) {
                $user_id = $existing_user->ID;
                $customer_logs[] = "Found existing WC User ID $user_id by email $email. Will link to Ecwid ID $ecwid_customer_id.";
                // Link it now if found by email but not meta
                update_user_meta($user_id, '_ecwid_customer_id', $ecwid_customer_id);
            }
        }

        $billing_person = $item_data['billingPerson'] ?? [];
        $shipping_person = $item_data['shippingPerson'] ?? [];

        $user_data = [
            'user_email' => $email,
            'first_name' => $billing_person['name'] ?? ($shipping_person['name'] ?? ''), // Ecwid often has full name in 'name'
            // WooCommerce typically splits first/last name. Ecwid's 'name' field might need parsing logic.
            // For simplicity, using full name as first_name or consider parsing logic.
            'billing_first_name' => $billing_person['name'] ?? '',
            'billing_company'    => $billing_person['companyName'] ?? '',
            'billing_address_1'  => $billing_person['street'] ?? '',
            'billing_city'       => $billing_person['city'] ?? '',
            'billing_postcode'   => $billing_person['postalCode'] ?? '',
            'billing_country'    => $billing_person['countryCode'] ?? '',
            'billing_state'      => $billing_person['stateOrRegionCode'] ?? '',
            'billing_phone'      => $billing_person['phone'] ?? '',
            'shipping_first_name'=> $shipping_person['name'] ?? '',
            'shipping_company'   => $shipping_person['companyName'] ?? '',
            'shipping_address_1' => $shipping_person['street'] ?? '',
            'shipping_city'      => $shipping_person['city'] ?? '',
            'shipping_postcode'  => $shipping_person['postalCode'] ?? '',
            'shipping_country'   => $shipping_person['countryCode'] ?? '',
            'shipping_state'     => $shipping_person['stateOrRegionCode'] ?? '',
            // 'shipping_phone'  // WC doesn't have a default shipping_phone on user meta directly, often on order
        ];
        // Split name if possible (basic example)
        if (!empty($user_data['first_name'])) {
            $name_parts = explode(' ', $user_data['first_name'], 2);
            $user_data['first_name'] = $name_parts[0];
            $user_data['last_name'] = $name_parts[1] ?? '';
            if (empty($user_data['billing_last_name']) && !empty($user_data['last_name'])) $user_data['billing_last_name'] = $user_data['last_name'];
            if (empty($user_data['shipping_last_name']) && !empty($user_data['last_name'])) $user_data['shipping_last_name'] = $user_data['last_name'];
        }


        if ($user_id) { // Update existing user
            $customer_logs[] = "Updating existing user ID: $user_id.";
            $user_data['ID'] = $user_id;
            $result = wp_update_user($user_data); // wp_update_user handles core fields
            if (is_wp_error($result)) {
                $customer_logs[] = "[ERROR] Failed to update user: " . $result->get_error_message();
                return ['status' => 'failed', 'logs' => $customer_logs, 'item_name' => $email, 'ecwid_id' => $ecwid_customer_id];
            }
            // Update user meta for billing/shipping
            foreach ($user_data as $key => $value) {
                if (strpos($key, 'billing_') === 0 || strpos($key, 'shipping_') === 0) {
                    update_user_meta($user_id, $key, $value);
                }
            }
            $customer_logs[] = "User updated successfully.";
        } else { // Create new user
            $customer_logs[] = "Creating new user for email: $email.";
            $user_data['user_login'] = $email; // Or generate a unique username
            $user_data['user_pass'] = wp_generate_password(); // Generate a random password
            $user_data['role'] = 'customer';
            
            $new_user_id = wp_insert_user($user_data);
            if (is_wp_error($new_user_id)) {
                $customer_logs[] = "[ERROR] Failed to create user: " . $new_user_id->get_error_message();
                return ['status' => 'failed', 'logs' => $customer_logs, 'item_name' => $email, 'ecwid_id' => $ecwid_customer_id];
            }
            $user_id = $new_user_id;
            // Update user meta for billing/shipping for new user too
             foreach ($user_data as $key => $value) {
                if (strpos($key, 'billing_') === 0 || strpos($key, 'shipping_') === 0) {
                    update_user_meta($user_id, $key, $value);
                }
            }
            update_user_meta($user_id, '_ecwid_customer_id', $ecwid_customer_id);
            $customer_logs[] = "User created successfully with ID: $user_id.";
        }
        
        // Additional meta from Ecwid if needed
        if (isset($item_data['customerGroupId'])) update_user_meta($user_id, '_ecwid_customer_group_id', $item_data['customerGroupId']);
        if (isset($item_data['taxExempt'])) update_user_meta($user_id, '_ecwid_tax_exempt', $item_data['taxExempt']);
        if (isset($item_data['taxId'])) update_user_meta($user_id, '_ecwid_tax_id', $item_data['taxId']);
        if (isset($item_data['updatedDate'])) update_user_meta($user_id, '_ecwid_updated_date', gmdate('Y-m-d H:i:s', $item_data['updatedDate'] / 1000));


        return ['status' => 'imported', 'logs' => $customer_logs, 'item_name' => $email, 'ecwid_id' => $ecwid_customer_id];
    }

    // New method to import/update a single order (Skeleton with key areas)
    private function import_order($item_data) {
        $order_logs = [];
        $ecwid_order_number = $item_data['orderNumber'] ?? null; // Ecwid's primary order identifier
        // Ecwid also has an internal 'id' for orders, but 'orderNumber' is often what's exposed/used.
        // Let's assume 'orderNumber' is the unique key we'll store. If Ecwid provides a separate stable 'id', prefer that.
        // For this example, we'll use 'orderNumber' as the reference.
        $ecwid_internal_order_id = $item_data['id'] ?? $ecwid_order_number; // Prefer 'id' if available, fallback to orderNumber

        if (!$ecwid_order_number) {
            $order_logs[] = "[CRITICAL] Order data missing Ecwid Order Number.";
            return ['status' => 'failed', 'logs' => $order_logs, 'item_name' => 'Unknown Order', 'ecwid_id' => 'N/A'];
        }
        $order_logs[] = "Starting import for Order: #$ecwid_order_number (Ecwid Internal ID: $ecwid_internal_order_id)";

        // 1. Find existing WC Order by Ecwid Order Number/ID meta
        $wc_order_id = null;
        $order_query_args = [
            'post_type'   => 'shop_order',
            'post_status' => 'any', // wc_get_order_statuses() keys
            'meta_query'  => [
                [
                    'key'     => '_ecwid_order_number', // Or _ecwid_order_id if using internal ID
                    'value'   => $ecwid_order_number,
                ]
            ],
            'posts_per_page' => 1,
            'fields' => 'ids'
        ];
        $existing_orders = get_posts($order_query_args);
        if (!empty($existing_orders)) {
            $wc_order_id = $existing_orders[0];
            $order_logs[] = "Found existing WC Order ID $wc_order_id for Ecwid Order #$ecwid_order_number.";
        }

        try {
            $order = $wc_order_id ? wc_get_order($wc_order_id) : null;
            $is_new_order = !$order;

            if ($is_new_order) {
                $order = wc_create_order(['status' => 'pending']); // Create with a default status
                if (!$order) {
                    $order_logs[] = "[CRITICAL] Failed to create new WC_Order object.";
                    return ['status' => 'failed', 'logs' => $order_logs, 'item_name' => "#$ecwid_order_number", 'ecwid_id' => $ecwid_internal_order_id];
                }
                $wc_order_id = $order->get_id();
                $order_logs[] = "Created new WC Order ID: $wc_order_id.";
                update_post_meta($wc_order_id, '_ecwid_order_number', $ecwid_order_number);
                update_post_meta($wc_order_id, '_ecwid_internal_order_id', $ecwid_internal_order_id); // Store internal ID too
            } else {
                $order_logs[] = "Updating existing WC Order ID: $wc_order_id.";
                // Clear existing items, fees, shipping to rebuild from Ecwid data (or implement more granular update)
                $order->remove_order_items('line_item');
                $order->remove_order_items('fee');
                $order->remove_order_items('shipping');
                // $order->remove_order_items('tax'); // Be careful with tax if WC calculates it
            }

            // 2. Customer Association
            $ecwid_customer_id_on_order = $item_data['customerId'] ?? null;
            $customer_email_on_order = $item_data['email'] ?? null;
            $wc_customer_id = 0;

            if ($ecwid_customer_id_on_order) {
                $user_query = new WP_User_Query([
                    'meta_key' => '_ecwid_customer_id', 'meta_value' => $ecwid_customer_id_on_order,
                    'number' => 1, 'fields' => 'ID'
                ]);
                if (!empty($user_query->get_results())) $wc_customer_id = $user_query->get_results()[0];
            }
            if (!$wc_customer_id && $customer_email_on_order) {
                $user = get_user_by('email', $customer_email_on_order);
                if ($user) $wc_customer_id = $user->ID;
            }
            if ($wc_customer_id) {
                $order->set_customer_id($wc_customer_id);
                $order_logs[] = "Associated order with WC Customer ID: $wc_customer_id.";
            } else {
                $order_logs[] = "No WC customer found to associate. Order will be guest order or use billing email.";
                // For guest orders, WC uses billing email.
            }



            // 3. Billing & Shipping Addresses
            $billing_person = $item_data['billingPerson'] ?? [];
            $shipping_person = $item_data['shippingPerson'] ?? [];
            $order->set_billing_first_name($this->parse_name_part($billing_person['name'] ?? '', 'first'));
            $order->set_billing_last_name($this->parse_name_part($billing_person['name'] ?? '', 'last'));
            $order->set_billing_company($billing_person['companyName'] ?? '');
            $order->set_billing_address_1($billing_person['street'] ?? '');
            // $order->set_billing_address_2(''); // Ecwid street might contain both lines
            $order->set_billing_city($billing_person['city'] ?? '');
            $order->set_billing_state($billing_person['stateOrRegionCode'] ?? '');
            $order->set_billing_postcode($billing_person['postalCode'] ?? '');
            $order->set_billing_country($billing_person['countryCode'] ?? '');
            $order->set_billing_email($customer_email_on_order ?? ($billing_person['email'] ?? '')); // Ecwid might have email on billingPerson too
            $order->set_billing_phone($billing_person['phone'] ?? '');

            if (!empty($shipping_person)) {
                $order->set_shipping_first_name($this->parse_name_part($shipping_person['name'] ?? '', 'first'));
                $order->set_shipping_last_name($this->parse_name_part($shipping_person['name'] ?? '', 'last'));
                $order->set_shipping_company($shipping_person['companyName'] ?? '');
                $order->set_shipping_address_1($shipping_person['street'] ?? '');
                $order->set_shipping_city($shipping_person['city'] ?? '');
                $order->set_shipping_state($shipping_person['stateOrRegionCode'] ?? '');
                $order->set_shipping_postcode($shipping_person['postalCode'] ?? '');
                $order->set_shipping_country($shipping_person['countryCode'] ?? '');
                // $order->set_shipping_phone($shipping_person['phone'] ?? ''); // WC_Order doesn't have set_shipping_phone
            } else {
                // If no shipping person, copy from billing (common WooCommerce behavior)
                $order->set_shipping_first_name($order->get_billing_first_name());
                $order->set_shipping_last_name($order->get_billing_last_name());
                // ... and so on for all shipping fields
            }
            $order_logs[] = "Billing and shipping addresses set.";

            // 4. Order Items
            if (isset($item_data['items']) && is_array($item_data['items'])) {
                foreach ($item_data['items'] as $ecwid_item) {
                    $product_id = 0;
                    $sku = $ecwid_item['sku'] ?? null;
                    $ecwid_product_id = $ecwid_item['productId'] ?? null;

                    if ($ecwid_product_id) {
                         $product_posts = get_posts(['post_type' => 'product', 'meta_key' => '_ecwid_product_id', 'meta_value' => $ecwid_product_id, 'post_status' => 'any', 'numberposts' => 1, 'fields' => 'ids']);
                         if (!empty($product_posts)) $product_id = $product_posts[0];
                    }
                    if (!$product_id && $sku) {
                        $product_id = wc_get_product_id_by_sku($sku);
                    }

                    if ($product_id) {
                        $product = wc_get_product($product_id);
                        if ($product) {
                            $item_args = [
                                'name' => $ecwid_item['name'] ?? $product->get_name(),
                                'quantity' => $ecwid_item['quantity'] ?? 1,
                                'total' => floatval($ecwid_item['price'] ?? 0) * intval($ecwid_item['quantity'] ?? 1), // Ecwid 'price' is usually per item before quantity
                                'subtotal' => floatval($ecwid_item['price'] ?? 0) * intval($ecwid_item['quantity'] ?? 1),
                                // 'taxes' => [], // Handle taxes separately if needed
                            ];
                            // If it's a variation, find variation ID
                            // This part is complex if Ecwid item options need to map to WC variation attributes
                            // For simplicity, assuming simple product or already matched variation if $product_id is a variation_id
                            $line_item_id = $order->add_product($product, $item_args['quantity'], $item_args);
                            if ($line_item_id) {
                                $order_logs[] = "Added product '{$item_args['name']}' (WC ID: $product_id) to order. Line item ID: $line_item_id.";
                                // Add line item meta if Ecwid provides options, etc.
                                if(isset($ecwid_item['options']) && is_array($ecwid_item['options'])){
                                    foreach($ecwid_item['options'] as $opt){
                                        wc_add_order_item_meta($line_item_id, sanitize_text_field($opt['name']), sanitize_text_field($opt['value']));
                                    }
                                }
                            } else {
                                $order_logs[] = "[ERROR] Failed to add product '{$item_args['name']}' (WC ID: $product_id) to order.";
                            }
                        } else {
                            $order_logs[] = "[WARNING] WC Product not found for ID: $product_id (Ecwid SKU: $sku). Item '{$ecwid_item['name']}' not added.";
                        }
                    } else {
                        $order_logs[] = "[WARNING] Could not find WC product for Ecwid item SKU: $sku, Name: '{$ecwid_item['name']}'. Item not added.";
                        // Optionally, add as a simple line item with name and price if product not found
                    }
                }
            }
            $order_logs[] = "Order items processed.";

            // 5. Shipping
            if (isset($item_data['shippingOption']['shippingMethodName']) || isset($item_data['shipping'])) {
                $shipping_method_title = $item_data['shippingOption']['shippingMethodName'] ?? 'Shipping';
                $shipping_total = floatval($item_data['shipping'] ?? 0);
                $shipping_item = new WC_Order_Item_Shipping();
                $shipping_item->set_method_title($shipping_method_title);
                $shipping_item->set_total($shipping_total);
                // $shipping_item->set_taxes(...); // If Ecwid provides shipping tax details
                $order->add_item($shipping_item);
                $order_logs[] = "Shipping method '{$shipping_method_title}' with cost {$shipping_total} added.";
            }

            // 6. Fees, Discounts (Coupons) - Simplified
            // Ecwid has couponDiscount, volumeDiscount, discount. WC has coupon lines.
            if (isset($item_data['couponDiscount']) && floatval($item_data['couponDiscount']) > 0) {
                $fee_item = new WC_Order_Item_Fee();
                $fee_item->set_name(__('Coupon Discount', 'ecwid2woo-product-sync')); // Or Ecwid coupon code if available
                $fee_item->set_total(-floatval($item_data['couponDiscount'])); // Negative for discount
                $fee_item->set_tax_status('none'); // Or apply tax if applicable
                $order->add_item($fee_item);
                $order_logs[] = "Added coupon discount: " . $item_data['couponDiscount'];
            }
            // Other discounts might be harder to map directly to WC coupon system.

            // 7. Taxes - Complex: WC can calculate taxes or use imported ones.
            // Ecwid provides 'tax'. If you want to use Ecwid's tax amount:
            if (isset($item_data['tax'])) {
                // This is a simplified way. WC tax handling is intricate.
                // $order->set_total_tax(floatval($item_data['tax']));
                // Or add as a tax line item if you know the rate and label
                // $tax_item = new WC_Order_Item_Tax();
                // $tax_item->set_rate_id(...); $tax_item->set_label(...); $tax_item->set_tax_total(...);
                // $order->add_item($tax_item);
                $order_logs[] = "Ecwid tax amount: " . $item_data['tax'] . ". Manual or specific tax line handling might be needed.";
            }


            // 8. Totals
            $order->set_discount_total(floatval($item_data['discount'] ?? 0) + floatval($item_data['couponDiscount'] ?? 0) + floatval($item_data['volumeDiscount'] ?? 0));
            // $order->set_shipping_total(floatval($item_data['shipping'] ?? 0)); // Already handled by shipping item
            $order->set_total(floatval($item_data['total'] ?? 0)); // This should be the final amount
            $order_logs[] = "Order totals set. Grand Total: " . $order->get_total();

            // 9. Order Status Mapping
            $ecwid_payment_status = $item_data['paymentStatus'] ?? 'AWAITING_PAYMENT';
            $ecwid_fulfillment_status = $item_data['fulfillmentStatus'] ?? 'AWAITING_PROCESSING';
            $wc_status = 'pending'; // Default

            // Basic mapping (this needs to be robust and cover all Ecwid statuses)
            if ($ecwid_payment_status === 'PAID') {
                if ($ecwid_fulfillment_status === 'SHIPPED' || $ecwid_fulfillment_status === 'DELIVERED') {
                    $wc_status = 'completed';
                } else {
                    $wc_status = 'processing';
                }
            } elseif ($ecwid_payment_status === 'CANCELLED' || $ecwid_payment_status === 'DECLINED') {
                $wc_status = 'cancelled';
            } elseif ($ecwid_payment_status === 'REFUNDED' || $ecwid_payment_status === 'PARTIALLY_REFUNDED') {
                $wc_status = 'refunded'; // Or custom status if partially
            }
            // Add more mappings as needed
            $order->set_status($wc_status, 'Imported from Ecwid.', false); // false to not save yet
            $order_logs[] = "Ecwid Payment Status: $ecwid_payment_status, Fulfillment: $ecwid_fulfillment_status. Mapped to WC Status: $wc_status.";

            // 10. Payment Information
            $order->set_payment_method($item_data['paymentModule'] ?? ($item_data['paymentMethod'] ?? 'unknown'));
            $order->set_payment_method_title($item_data['paymentMethod'] ?? 'Unknown');
            if (isset($item_data['paymentParams']) && is_array($item_data['paymentParams'])) {
                foreach($item_data['paymentParams'] as $key => $value) {
                    if (is_scalar($value)) { // Only store scalar values
                         update_post_meta($wc_order_id, '_ecwid_payment_param_' . sanitize_key($key), sanitize_text_field($value));
                    }
                }
            }
            if ($ecwid_payment_status === 'PAID' && !$order->is_paid()) {
                // $order->payment_complete($transaction_id_if_available_from_ecwid); // Mark as paid
                // Ecwid might not provide a transaction ID directly in the main order object easily.
                // It might be in paymentParams or require another API call.
                // For now, just log.
                $order_logs[] = "Order marked as PAID in Ecwid. Consider calling \$order->payment_complete().";
            }

            // 11. Order Notes / Comments
            if (!empty($item_data['orderComments'])) {
                $order->add_order_note('Customer Comment (from Ecwid): ' . esc_html($item_data['orderComments']), false, false); // is_customer_note = false
            }
            if (!empty($item_data['privateAdminNotes'])) {
                $order->add_order_note('Private Admin Note (from Ecwid): ' . esc_html($item_data['privateAdminNotes']), false, false);
            }

            // 12. Dates
            if (isset($item_data['createDate'])) { // Ecwid timestamp is in milliseconds
                $order->set_date_created(gmdate('Y-m-d H:i:s', $item_data['createDate'] / 1000));
            }
            if (isset($item_data['updateDate'])) {
                $order->set_date_modified(gmdate('Y-m-d H:i:s', $item_data['updateDate'] / 1000));
            }
            
            // 13. Refunds
            // Ecwid's `refundedAmount` field indicates total refunded.
            // Creating actual WC_Refund objects is more involved.
            // If `refundedAmount` > 0 and no WC refunds exist for this order matching that amount,
            // you might create a WC refund. This is a simplified check.
            $ecwid_refunded_amount = floatval($item_data['refundedAmount'] ?? 0);
            if ($ecwid_refunded_amount > 0) {
                $order_logs[] = "Ecwid order has refundedAmount: $ecwid_refunded_amount. Manual refund creation in WC might be needed or further logic to create WC_Refund objects.";
                // Example: wc_create_refund( array( 'amount' => $ecwid_refunded_amount, 'reason' => 'Refunded in Ecwid', 'order_id' => $wc_order_id, 'line_items' => array() ) );
                // This requires careful handling of line items for refunds.
            }

            // 14. Save Order
            $order->save();
            $order_logs[] = "WC Order #$wc_order_id saved successfully.";

            return ['status' => 'imported', 'logs' => $order_logs, 'item_name' => "#$ecwid_order_number", 'ecwid_id' => $ecwid_internal_order_id];

        } catch (Exception $e) {
            $order_logs[] = "[PHP EXCEPTION] During order import for Ecwid Order #$ecwid_order_number: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine();
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Ecwid Sync: PHP Exception during order import for Ecwid Order #$ecwid_order_number: " . $e->getMessage() . " Trace: " . $e->getTraceAsString());
            }
            return ['status' => 'failed', 'logs' => $order_logs, 'item_name' => "#$ecwid_order_number", 'ecwid_id' => $ecwid_internal_order_id];
        }
    }

    // Helper to parse name from Ecwid's single 'name' field
    private function parse_name_part($full_name, $part = 'first') {
        $name_parts = explode(' ', trim($full_name), 2);
        if ($part === 'first') {
            return $name_parts[0] ?? '';
        } elseif ($part === 'last') {
            return $name_parts[1] ?? '';
        }
        return $full_name;
    }


    private function import_product($item) {
        $product_logs = [];
        $product_name_for_log = isset($item['name']) ? sanitize_text_field($item['name']) : '[No Name]';
        $ecwid_id_for_log = $item['id'] ?? 'N/A';
        $sku_for_log = $item['sku'] ?? 'N/A';

        // Basic checks for essential data
        if (!class_exists('WC_Product_Factory')) {
            $product_logs[] = __("[CRITICAL] WooCommerce is not active or WC_Product_Factory class not found.", 'ecwid2woo-product-sync');
            return ['status' => 'failed', 'logs' => $product_logs, 'item_name' => $product_name_for_log, 'ecwid_id' => $ecwid_id_for_log, 'sku' => $sku_for_log];
        }
        if ($ecwid_id_for_log === 'N/A' || $sku_for_log === 'N/A') {
            $product_logs[] = __("[CRITICAL] Product missing Ecwid ID or SKU. Ecwid ID: $ecwid_id_for_log, SKU: $sku_for_log. Raw item: " . wp_json_encode($item), 'ecwid2woo-product-sync');
            error_log("Ecwid Sync: Product (Ecwid ID: $ecwid_id_for_log) missing SKU or ID. Data: " . print_r($item, true));
            return ['status' => 'failed', 'logs' => $product_logs, 'item_name' => $product_name_for_log, 'ecwid_id' => $ecwid_id_for_log, 'sku' => $sku_for_log];
        }

        $log_product_identifier = "PRODUCT (Ecwid ID: {$ecwid_id_for_log}, SKU: {$sku_for_log}, Name: \"" . esc_html($product_name_for_log) . "\")";
        $product_logs[] = sprintf(__("Starting import for %s", 'ecwid2woo-product-sync'), $log_product_identifier);
        
        $product_logs[] = "Raw Ecwid Item Data (for parent product prices): Price Field = " . ($item['price'] ?? 'NOT_SET') . ", CompareToPrice Field = " . ($item['compareToPrice'] ?? 'NOT_SET');


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
                 if (isset($item['price'])) $product->set_regular_price(strval($item['price'])); // Set base price for variable product if available
            }

            // --- CATEGORY ASSIGNMENT ---
            if (isset($item['categoryIds']) && is_array($item['categoryIds']) && !empty($item['categoryIds'])) {
                $product_logs[] = "Ecwid Category IDs found: " . implode(', ', $item['categoryIds']);
                $wc_term_ids = [];
                foreach ($item['categoryIds'] as $ecwid_cat_id) {
                    if (empty($ecwid_cat_id) || intval($ecwid_cat_id) == 0) continue;
                    // MODIFICATION: Pass true to bypass cache when looking up term IDs for product assignment
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
                $is_already_imported = $existing_featured_image_id && (get_post_meta($existing_featured_image_id, '_ecwid_image_source_url', true) === $featured_image_url);

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
                } else {
                    $product_logs[] = "Featured image already imported and matches source URL. Skipped re-download.";
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
                    
                    // Get or create global WooCommerce attribute
                    $taxonomy_name = wc_attribute_taxonomy_name($attribute_name); // Generates "pa_color"
                    $attribute_id = wc_attribute_taxonomy_id_by_name($attribute_name); // Check if global attribute exists

                    if (!$attribute_id) { // If global attribute doesn't exist, create it
                        $product_logs[] = "WC Attribute '$attribute_name' (taxonomy '$taxonomy_name') not found. Creating...";
                        $attribute_id = wc_create_attribute([
                            'name'         => $attribute_name, // Human-readable name like "Color"
                            'slug'         => sanitize_title($attribute_name), // Attribute slug like "color"
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
                
                foreach ($existing_wc_variation_ids as $existing_wc_variation_id) {
                    $ecwid_combo_id_meta = get_post_meta($existing_wc_variation_id, '_ecwid_variation_id', true);
                    if ($ecwid_combo_id_meta && !in_array($ecwid_combo_id_meta, $current_ecwid_combo_ids)) {
                        $variation_to_delete = wc_get_product($existing_wc_variation_id);
                        if ($variation_to_delete) {
                            $variation_to_delete->delete(true);
                            $product_logs[] = "Deleted stale WC Variation ID $existing_wc_variation_id (linked to Ecwid Combo ID: $ecwid_combo_id_meta) as it's not in current Ecwid payload.";
                        }
                    }
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

                // 2. Add new gallery images from Ecwid that aren't already processed (i.e., kept or previously added from this payload)
                foreach ($item['galleryImages'] as $gallery_image_data) {
                    $gallery_image_url = $gallery_image_data['hdThumbnailUrl'] ?? $gallery_image_data['originalImageUrl'] ?? $gallery_image_data['url'] ?? null;
                    if ($gallery_image_url && !in_array($gallery_image_url, $processed_ecwid_gallery_urls)) {
                        $product_logs[] = "Attempting to attach new gallery image from Ecwid: $gallery_image_url";
                        $g_image_id = $this->attach_image_to_product_from_url($gallery_image_url, $product_saved_id, ($item['name'] ?? 'Product') . ' gallery image');
                        
                        if ($g_image_id && !is_wp_error($g_image_id)) {
                            $new_gallery_ids_to_set[] = $g_image_id;
                            update_post_meta($g_image_id, '_ecwid_gallery_image_source_url', esc_url_raw($gallery_image_url));
                            $product_logs[] = "New gallery image attached, WC Attachment ID: $g_image_id.";
                            $processed_ecwid_gallery_urls[] = $gallery_image_url; // Mark as processed
                        } else {
                            $gallery_error = is_wp_error($g_image_id) ? $g_image_id->get_error_message() : 'Unknown error attaching gallery image';
                            $product_logs[] = "[WARNING] Failed to attach gallery image ($gallery_image_url). Error: $gallery_error";
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
                        'total_combinations' => $total_combinations
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
            error_log("Ecwid Sync: WC_Data_Exception for $log_product_identifier: " . $e->getMessage() . " Trace: " . $e->getTraceAsString());
            return ['status' => 'failed', 'logs' => $product_logs, 'item_name' => $product_name_for_log, 'ecwid_id' => $ecwid_id_for_log, 'sku' => $sku_for_log];
        } catch (Exception $e) { // Catch any other general exceptions
            $product_logs[] = "[CRITICAL PHP Exception] During product import: " . $e->getMessage() . " on line " . $e->getLine() . " in " . $e->getFile();
            error_log("Ecwid Sync: PHP Exception for $log_product_identifier: " . $e->getMessage() . " Trace: " . $e->getTraceAsString());
            return ['status' => 'failed', 'logs' => $product_logs, 'item_name' => $product_name_for_log, 'ecwid_id' => $ecwid_id_for_log, 'sku' => $sku_for_log];
        }
    }

    public function ajax_process_variation_batch() {
        check_ajax_referer('ecwid_wc_sync_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized', 'ecwid2woo-product-sync')]);
            return;
        }
        set_time_limit(0); // Attempt to disable time limit for variation batch

        $wc_product_id = isset($_POST['wc_product_id']) ? intval($_POST['wc_product_id']) : 0;
        $ecwid_product_id_for_log = isset($_POST['ecwid_product_id']) ? intval($_POST['ecwid_product_id']) : 0; // For logging context
        $item_name_for_log = isset($_POST['item_name']) ? sanitize_text_field($_POST['item_name']) : 'N/A';
        $sku_for_log = isset($_POST['sku']) ? sanitize_text_field($_POST['sku']) : 'N/A';
        
        $combinations_batch_json = isset($_POST['combinations_batch_json']) ? stripslashes($_POST['combinations_batch_json']) : '[]';
        $combinations_batch = json_decode($combinations_batch_json, true);

        $original_ecwid_options_json = isset($_POST['original_ecwid_options_json']) ? stripslashes($_POST['original_ecwid_options_json']) : '[]';
        $original_ecwid_options = json_decode($original_ecwid_options_json, true);


        $batch_logs = [];

        if (empty($wc_product_id) || !$combinations_batch) {
            wp_send_json_error([
                'message' => __('Missing WC Product ID or combinations batch for variation processing.', 'ecwid2woo-product-sync'),
                'logs' => ['[CRITICAL] WC Product ID or combinations_batch_json was empty.']
            ]);
            return;
        }

        $parent_product = wc_get_product($wc_product_id);

        if (!$parent_product) {
            wp_send_json_error([
                'message' => sprintf(__('Could not load parent WC Product ID %s for variation processing.', 'ecwid2woo-product-sync'), $wc_product_id),
                'logs' => ["[CRITICAL] Parent product WC ID: $wc_product_id not found."]
            ]);
            return;
        }
        if (!$parent_product->is_type('variable')) {
             wp_send_json_error([
                'message' => sprintf(__('Parent WC Product ID %s is not a variable product type.', 'ecwid2woo-product-sync'), $wc_product_id),
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
            'message' => sprintf(__('Processed %d variations in this batch for %s (SKU: %s).', 'ecwid2woo-product-sync'), count($combinations_batch), $item_name_for_log, $sku_for_log),
            'batch_logs' => $batch_logs,
            'processed_in_batch' => count($combinations_batch),
            'failed_in_batch' => $result['failed_count'] ?? 0,
        ]);
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

            $variation_attributes_for_wc = [];
            if (isset($combo['options']) && is_array($combo['options'])) {
                foreach ($combo['options'] as $combo_opt_val) {
                    if (empty($combo_opt_val['name']) || !isset($combo_opt_val['value'])) {
                         $batch_logs[] = "[WARNING] Skipping invalid option in combination $ecwid_combination_id (missing name or value): " . wp_json_encode($combo_opt_val);
                         continue;
                    }
                    $parent_attribute_name = sanitize_text_field($combo_opt_val['name']);
                    $wc_attr_taxonomy_slug = wc_attribute_taxonomy_name($parent_attribute_name);
                    $term_value_from_ecwid = sanitize_text_field($combo_opt_val['value']);

                    $term_object = get_term_by('name', $term_value_from_ecwid, $wc_attr_taxonomy_slug);
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
            $existing_vars_query = new WP_Query([
                'post_type' => 'product_variation', 'post_status' => 'any',
                'post_parent' => $parent_product_id,
                'meta_query' => [[ 'key' => '_ecwid_variation_id', 'value' => $ecwid_combination_id ]],
                'posts_per_page' => 1, 'fields' => 'ids'
            ]);
            if ($existing_vars_query->have_posts()) {
                $variation_id = $existing_vars_query->posts[0];
                $batch_logs[] = "Found existing WC Variation ID $variation_id for Ecwid Combo ID $ecwid_combination_id.";
            } else {
                $batch_logs[] = "No existing WC Variation for Ecwid Combo ID $ecwid_combination_id. Creating new.";
            }

            $variation = $variation_id ? new WC_Product_Variation($variation_id) : new WC_Product_Variation();
            $variation->set_parent_id($parent_product_id);
            $variation->set_attributes($variation_attributes_for_wc);

            $variation_sku = $combo['sku'] ?? ($parent_sku . '-combo-' . $ecwid_combination_id);
            $variation->set_sku(sanitize_text_field($variation_sku));
            
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

            try {
                $var_saved_id = $variation->save();
                if ($var_saved_id && !is_wp_error($var_saved_id)) {
                    update_post_meta($var_saved_id, '_ecwid_variation_id', $ecwid_combination_id);
                    $batch_logs[] = "Saved WC Variation ID $var_saved_id (Ecwid Combo ID: $ecwid_combination_id). Attributes: " . wp_json_encode($variation_attributes_for_wc);
                    $processed_count++;
                } else {
                    $var_error_msg = is_wp_error($var_saved_id) ? $var_saved_id->get_error_message() : "Unknown error saving variation";
                    $batch_logs[] = "[ERROR] Failed to save WC Variation for Ecwid Combo ID $ecwid_combination_id. Error: $var_error_msg.";
                    $failed_count++;
                }
            } catch (Exception $e) {
                $batch_logs[] = "[EXCEPTION] Saving WC Variation for Ecwid Combo ID $ecwid_combination_id. Error: " . $e->getMessage();
                $failed_count++;
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

    private function get_or_create_missing_parent_placeholder($parent_ecwid_id) {
        $existing_term_query = new WP_Query([ // Changed from get_posts to WP_Query for consistency
            'post_type' => 'ecwid_placeholder', // Query the CPT
            'meta_key' => '_ecwid_placeholder_parent_id',
            'meta_value' => $parent_ecwid_id,
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

        $placeholder_name = sprintf(__('Missing Category %s', 'ecwid2woo-product-sync'), $parent_ecwid_id);

        $term_result = wp_insert_term($placeholder_name, 'product_cat', [
            'description' => sprintf(__('Automatically created placeholder for missing Ecwid category ID %s', 'ecwid2woo-product-sync'), $parent_ecwid_id)
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

    private function get_term_id_by_ecwid_id($ecwid_id, $taxonomy, $bypass_cache = false) {
        global $wpdb;
        static $term_cache = []; // Renamed cache variable to avoid conflict
        $cache_key = $ecwid_id . '_' . $taxonomy;

        if (!$bypass_cache && isset($term_cache[$cache_key])) {
            return $term_cache[$cache_key];
        }

        $query = $wpdb->prepare(
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
        );
        $term_id = $wpdb->get_var($query);

        if (!$bypass_cache && $term_id) {
            $term_cache[$cache_key] = (int)$term_id;
        }
        return $term_id ? (int)$term_id : null;
    }

    private function attach_image_to_product_from_url($image_url, $post_id = 0, $desc = null) {
        if (empty($image_url)) {
            return new WP_Error('missing_url', __('Image URL is empty.', 'ecwid2woo-product-sync'));
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
            @unlink($tmp);
            return new WP_Error('download_failed', sprintf(__('Image download failed from %s: %s', 'ecwid2woo-product-sync'), esc_url_raw($image_url), $tmp->get_error_message()));
        }

        $file_array = [
            'name' => basename(parse_url($image_url, PHP_URL_PATH)),
            'tmp_name' => $tmp
        ];

        $attachment_id = media_handle_sideload($file_array, $post_id, $desc);

        if (file_exists($tmp)) {
            @unlink($tmp);
        }

        if (is_wp_error($attachment_id)) {
            return new WP_Error('sideload_failed', sprintf(__('Image sideload failed for %s: %s', 'ecwid2woo-product-sync'), esc_url_raw($image_url), $attachment_id->get_error_message()));
        }
        return $attachment_id;
    }

    public function fix_category_hierarchy() {
        check_ajax_referer('ecwid_wc_sync_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized', 'ecwid2woo-product-sync')]);
            return;
        }

        $missing_parents = get_option('ecwid_wc_sync_missing_parents', []);
        $fixed_count = 0;
        $logs = [];

        foreach ($missing_parents as $parent_ecwid_id => $child_ecwid_ids) {
            $parent_wc_term_id = $this->get_term_id_by_ecwid_id($parent_ecwid_id, 'product_cat', true);

            if (!$parent_wc_term_id) {
                $logs[] = sprintf(__('Parent Ecwid ID %s still missing, cannot fix its children.', 'ecwid2woo-product-sync'), $parent_ecwid_id);
                continue;
            }

            foreach ($child_ecwid_ids as $child_ecwid_id) {
                $child_wc_term_id = $this->get_term_id_by_ecwid_id($child_ecwid_id, 'product_cat', true);

                if (!$child_wc_term_id) {
                    $logs[] = sprintf(__('Child term for Ecwid ID %s not found.', 'ecwid2woo-product-sync'), $child_ecwid_id);
                    continue;
                }

                $update_result = wp_update_term($child_wc_term_id, 'product_cat', ['parent' => $parent_wc_term_id]);

                if (is_wp_error($update_result)) {
                    $logs[] = sprintf(__('Failed to update parent for term %1$s: %2$s', 'ecwid2woo-product-sync'), $child_wc_term_id, $update_result->get_error_message());
                } else {
                    $fixed_count++;
                    $logs[] = sprintf(__('Fixed parent for term %1$s, now under parent %2$s', 'ecwid2woo-product-sync'), $child_wc_term_id, $parent_wc_term_id);
                }
            }
        }

        update_option('ecwid_wc_sync_missing_parents', []);

        wp_send_json_success([
            'fixed_count' => $fixed_count,
            'logs' => $logs,
            'message' => sprintf(_n('%d hierarchy fixed.', '%d hierarchies fixed.', $fixed_count, 'ecwid2woo-product-sync'), $fixed_count)
        ]);
    }
}

new Ecwid_WC_Sync();
?>
