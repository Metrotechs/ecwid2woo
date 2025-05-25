<?php
/*
Plugin Name: Ecwid2Woo Product Sync
Description: Easily Sync Ecwid Product Data (products, categories, images, skus, etc.) to WooCommerce.
Plugin URI: https://metrotechs.io/plugins/ecwid2woo/
Author URI: https://metrotechs.io
Version: 0.8.1
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

// Declare HPOS compatibility
add_action( 'before_woocommerce_init', function() {
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
    }
} );

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if (!defined('ECWID2WOO_VARIATION_BATCH_SIZE')) {
    define('ECWID2WOO_VARIATION_BATCH_SIZE', 50); // Number of variations to process per batch
}

define('ECWID2WOO_VERSION', '0.8.1'); // Define version constant

class Ecwid_WC_Sync {
    private $options;
    private $sync_steps = ['categories', 'products']; // Define order of sync for full sync

    // Define slugs for the admin pages
    private $settings_slug = 'ecwid-sync-settings';
    private $full_sync_slug = 'ecwid-sync-full';
    private $partial_sync_slug = 'ecwid-sync-partial';
    private $category_sync_slug = 'ecwid-sync-categories';

    public function __construct() {
        $this->load_textdomain();
        $this->options = get_option('ecwid_wc_sync_options');
        add_action('init', [$this, 'register_placeholder_cpt']);
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'settings_init']);
        add_action('wp_ajax_ecwid_wc_batch_sync', [$this, 'ajax_batch_sync']);
        add_action('wp_ajax_ecwid_wc_fetch_products_for_selection', [$this, 'ajax_fetch_products_for_selection']);
        add_action('wp_ajax_ecwid_wc_import_selected_products', [$this, 'ajax_import_selected_products']);
        add_action('wp_ajax_fix_category_hierarchy', [$this, 'fix_category_hierarchy']);
        add_action('wp_ajax_ecwid_wc_process_variation_batch', [$this, 'ajax_process_variation_batch']);
        add_action('wp_ajax_ecwid_wc_fetch_full_sync_counts', [$this, 'ajax_fetch_full_sync_counts']);
        add_action('wp_ajax_ecwid_wc_fetch_categories_for_display', [$this, 'ajax_fetch_categories_for_display']);
        add_action('wp_ajax_ecwid_wc_test_connection', [$this, 'ajax_test_api_connection']); // Make sure this line exists
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
                'singular_name' => __('Ecwid Placeholder', 'ecwid2woo-product-sync'),
                'menu_name' => __('Placeholders', 'ecwid2woo-product-sync'), // Shorter menu name
            ],
            'supports' => ['title'],
            'rewrite' => false,
            'show_in_menu' => false, // Prevent automatic menu item creation
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
            $this->settings_slug, // This makes "Settings" link to the main page
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
            __('Selective Product Sync', 'ecwid2woo-product-sync'),
            __('Product Sync', 'ecwid2woo-product-sync'),
            'manage_options',
            $this->partial_sync_slug,
            [$this, 'options_page_router']
        );

        // Add the Placeholders CPT as the last submenu item
        add_submenu_page(
            $this->settings_slug,                         // Parent slug
            __('Ecwid Placeholders', 'ecwid2woo-product-sync'), // Page title
            __('Placeholders', 'ecwid2woo-product-sync'),  // Menu title (from CPT labels)
            'manage_options',                             // Capability
            'edit.php?post_type=ecwid_placeholder',       // Menu slug (links to CPT admin table)
            null                                          // Callback function (null for default CPT screen)
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
            'sync_steps' => $this->sync_steps,
            'variation_batch_size' => defined('ECWID2WOO_VARIATION_BATCH_SIZE') ? ECWID2WOO_VARIATION_BATCH_SIZE : 50,
            'i18n' => [
                'sync_starting' => __('Sync starting...', 'ecwid2woo-product-sync'),
                'sync_complete' => __('Sync Complete!', 'ecwid2woo-product-sync'),
                'sync_error'    => __('Error during sync. Check console or log for details.', 'ecwid2woo-product-sync'),
                'ajax_error'    => __('AJAX Error. Check console or log for details.', 'ecwid2woo-product-sync'),
                'syncing'       => __('Syncing', 'ecwid2woo-product-sync'), // Generic "Syncing"
                'start_sync'    => __('Start Full Sync', 'ecwid2woo-product-sync'),
                'syncing_button'=> __('Syncing...', 'ecwid2woo-product-sync'),
                'fetching_counts' => __('Fetching item counts...', 'ecwid2woo-product-sync'), 
                'categories_to_sync_info' => __('Categories to sync: {count}', 'ecwid2woo-product-sync'), 
                'products_to_sync_info' => __('Products to sync: {count}', 'ecwid2woo-product-sync'), 
                'variations_to_sync_info' => __('Variations to sync: {count}', 'ecwid2woo-product-sync'), 
                'syncing_item_of_total' => __('Syncing {syncType}: {current} of {total}...', 'ecwid2woo-product-sync'), 
                'load_products' => __('Reload Products', 'ecwid2woo-product-sync'),
                'loading_products' => __('Loading Products...', 'ecwid2woo-product-sync'),
                'load_ecwid_categories' => __('Reload Ecwid Categories', 'ecwid2woo-product-sync'), 
                'loading_ecwid_categories' => __('Loading Categories...', 'ecwid2woo-product-sync'), 
                'no_categories_found_display' => __('No categories found in your Ecwid store or an error occurred.', 'ecwid2woo-product-sync'), 
                'categories_loaded_for_display' => __('{count} categories loaded for display.', 'ecwid2woo-product-sync'), 
                'import_selected' => __('Import Selected Products', 'ecwid2woo-product-sync'),
                'importing_selected' => __('Importing Selected...', 'ecwid2woo-product-sync'),
                'no_products_selected' => __('No products selected for import.', 'ecwid2woo-product-sync'),
                'select_all_none' => __('Select All/None', 'ecwid2woo-product-sync'), 
                'no_products_found' => __('No enabled products found in Ecwid store or failed to fetch.', 'ecwid2woo-product-sync'),
                'start_category_sync_page' => __('Start Category Sync', 'ecwid2woo-product-sync'), // RESTORED/KEPT
                'syncing_categories_page_button' => __('Syncing Categories...', 'ecwid2woo-product-sync'),
                'category_sync_page_complete' => __('Category Sync Complete!', 'ecwid2woo-product-sync'),
                'syncing_just_categories_page_status' => __('Syncing categories...', 'ecwid2woo-product-sync'),
                'fix_hierarchy_button' => __('Fix Category Hierarchy', 'ecwid2woo-product-sync'),
                'fixing_hierarchy' => __('Fixing hierarchy...', 'ecwid2woo-product-sync'),
                'hierarchy_fixed' => __('Category hierarchy fix attempt complete.', 'ecwid2woo-product-sync'),
                'importing_variations_status' => __('Importing variations for {productName} ({currentBatch} of {totalBatches})', 'ecwid2woo-product-sync'),
                'processing_variation_batch' => __('Processing variation batch...', 'ecwid2woo-product-sync'),
                'variations_imported_successfully' => __('All variations imported successfully for {productName}.', 'ecwid2woo-product-sync'),
                'error_importing_variations' => __('Error importing variations for {productName}. See log.', 'ecwid2woo-product-sync'),
                'parent_product_imported_pending_variations' => __('Parent product {productName} imported. Starting variation import...', 'ecwid2woo-product-sync'),
                'load_sync_preview' => __('Reload Sync Data', 'ecwid2woo-product-sync'), // MODIFIED
                'loading_sync_preview' => __('Reloading sync data...', 'ecwid2woo-product-sync'), // MODIFIED
                'preview_loaded_ready_to_sync' => __('Preview loaded. Ready to start full sync.', 'ecwid2woo-product-sync'), // ADDED
                'categories_for_preview' => __('Categories to be Synced:', 'ecwid2woo-product-sync'), // ADDED
                'products_for_preview' => __('Products to be Synced:', 'ecwid2woo-product-sync'), // ADDED
                'preview_load_error' => __('Error loading preview data. Please try again or proceed with sync.', 'ecwid2woo-product-sync'), // ADDED
                'variations_count_in_preview' => __('Variation count will be determined when sync starts.', 'ecwid2woo-product-sync'), // ADDED
                'stop_full_sync_button_text' => __('STOP SYNC', 'ecwid2woo-product-sync'), // ADDED
                'sync_stopped_by_user_log' => __('SYNC HAS BEEN STOPPED BY THE USER.', 'ecwid2woo-product-sync'), // ADDED
                'sync_stopped_by_user_status' => __('Sync stopped by user.', 'ecwid2woo-product-sync'), // ADDED
                'sync_cancelled_log_message' => __('Sync cancelled by user, aborting further operations.', 'ecwid2woo-product-sync'), // ADDED
                'testing_connection' => __('Testing...', 'ecwid2woo-product-sync'),
                'connection_successful' => __('CONNECTION SUCCESSFUL!', 'ecwid2woo-product-sync'),
                'connection_failed' => __('CONNECTION UNSUCCESSFUL - PLEASE CHECK YOUR API KEY AND STORE ID AND TRY AGAIN', 'ecwid2woo-product-sync'),
                'save_settings_failed' => __('Failed to save settings. Please try again.', 'ecwid2woo-product-sync'),
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
            default:
                $this->render_settings_page();
                break;
        }
        echo '</div>';
    }    private function render_settings_page() {
        ?>
        <div class="ecwid-settings-header">
            <h1><?php esc_html_e('Ecwid2Woo Sync Settings', 'ecwid2woo-product-sync'); ?></h1>
            <p class="description"><?php esc_html_e('Configure your Ecwid API credentials to enable synchronization between your Ecwid store and WooCommerce.', 'ecwid2woo-product-sync'); ?></p>
        </div>

        <div class="ecwid-settings-container">
            <div class="ecwid-settings-card">
                <div class="card-header">
                    <h2><?php esc_html_e('API Configuration', 'ecwid2woo-product-sync'); ?></h2>
                    <p><?php esc_html_e('Enter your Ecwid store credentials below:', 'ecwid2woo-product-sync'); ?></p>
                </div>
                
                <form action='options.php' method='post' id="ecwid-settings-form">
                    <?php
                    settings_fields('ecwidSyncSettingsGroup');
                    do_settings_sections($this->settings_slug);
                    ?>
                    
                    <div class="settings-actions">
                        <button type="submit" class="button button-primary button-large"><?php esc_html_e('Save Settings', 'ecwid2woo-product-sync'); ?></button>
                        <button type="button" id="test-api-connection" class="button button-secondary button-large"><?php esc_html_e('Test Connection', 'ecwid2woo-product-sync'); ?></button>
                    </div>
                </form>
                
                <div id="test-connection-result" class="connection-status"></div>
                <div id="save-status" class="save-status"></div>
            </div>

            <div class="ecwid-navigation-card">
                <div class="card-header">
                    <h2><?php esc_html_e('Quick Actions', 'ecwid2woo-product-sync'); ?></h2>
                    <p><?php esc_html_e('Navigate to different sync options:', 'ecwid2woo-product-sync'); ?></p>
                </div>
                
                <div class="nav-buttons-grid">
                    <a href="<?php echo admin_url('admin.php?page=' . $this->full_sync_slug); ?>" class="nav-button nav-button-primary">
                        <div class="nav-button-icon">🔄</div>
                        <div class="nav-button-content">
                            <h3><?php esc_html_e('Full Sync', 'ecwid2woo-product-sync'); ?></h3>
                            <p><?php esc_html_e('Sync all categories and products', 'ecwid2woo-product-sync'); ?></p>
                        </div>
                    </a>
                    
                    <a href="<?php echo admin_url('admin.php?page=' . $this->category_sync_slug); ?>" class="nav-button nav-button-secondary">
                        <div class="nav-button-icon">📁</div>
                        <div class="nav-button-content">
                            <h3><?php esc_html_e('Category Sync', 'ecwid2woo-product-sync'); ?></h3>
                            <p><?php esc_html_e('Import and organize categories', 'ecwid2woo-product-sync'); ?></p>
                        </div>
                    </a>
                    
                    <a href="<?php echo admin_url('admin.php?page=' . $this->partial_sync_slug); ?>" class="nav-button nav-button-tertiary">
                        <div class="nav-button-icon">🎯</div>
                        <div class="nav-button-content">
                            <h3><?php esc_html_e('Partial Sync', 'ecwid2woo-product-sync'); ?></h3>
                            <p><?php esc_html_e('Select specific products to import', 'ecwid2woo-product-sync'); ?></p>
                        </div>
                    </a>
                    
                    <a href="<?php echo admin_url('edit.php?post_type=ecwid_placeholder'); ?>" class="nav-button nav-button-quaternary">
                        <div class="nav-button-icon">📋</div>
                        <div class="nav-button-content">
                            <h3><?php esc_html_e('Placeholders', 'ecwid2woo-product-sync'); ?></h3>
                            <p><?php esc_html_e('Manage import placeholders', 'ecwid2woo-product-sync'); ?></p>
                        </div>
                    </a>
                </div>
            </div>
        </div>

        <style>
        .ecwid-settings-header {
            margin-bottom: 30px;
        }
        
        .ecwid-settings-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            max-width: 1200px;
        }
        
        @media (max-width: 1024px) {
            .ecwid-settings-container {
                grid-template-columns: 1fr;
            }
        }
        
        .ecwid-settings-card, .ecwid-navigation-card {
            background: #fff;
            border: 1px solid #e1e1e1;
            border-radius: 8px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .card-header {
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .card-header h2 {
            margin: 0 0 8px 0;
            color: #1d2327;
            font-size: 18px;
        }
        
        .card-header p {
            margin: 0;
            color: #646970;
            font-size: 14px;
        }
        
        .settings-actions {
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #f0f0f0;
            display: flex;
            gap: 15px;
            align-items: center;
        }
        
        .button-large {
            padding: 8px 16px !important;
            font-size: 14px !important;
            height: auto !important;
        }
        
        .connection-status, .save-status {
            margin-top: 15px;
            padding: 12px 16px;
            border-radius: 4px;
            display: none;
            animation: slideDown 0.3s ease-out;
        }
        
        .connection-status.success, .save-status.success {
            background: #d1eddb;
            border: 1px solid #00a32a;
            color: #00a32a;
        }
        
        .connection-status.error, .save-status.error {
            background: #f9dcdc;
            border: 1px solid #d63638;
            color: #d63638;
        }
        
        .nav-buttons-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        @media (max-width: 768px) {
            .nav-buttons-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .nav-button {
            display: flex;
            align-items: center;
            padding: 20px;
            border: 2px solid transparent;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.3s ease;
            min-height: 100px;
        }
        
        .nav-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            text-decoration: none;
        }
        
        .nav-button-primary {
            background: linear-gradient(135deg, #0073aa 0%, #005a87 100%);
            color: white;
        }
        
        .nav-button-primary:hover {
            background: linear-gradient(135deg, #005a87 0%, #004a70 100%);
            color: white;
        }
        
        .nav-button-secondary {
            background: linear-gradient(135deg, #00a32a 0%, #007c20 100%);
            color: white;
        }
        
        .nav-button-secondary:hover {
            background: linear-gradient(135deg, #007c20 0%, #006318 100%);
            color: white;
        }
        
        .nav-button-tertiary {
            background: linear-gradient(135deg, #f56e28 0%, #e55100 100%);
            color: white;
        }
        
        .nav-button-tertiary:hover {
            background: linear-gradient(135deg, #e55100 0%, #cc4400 100%);
            color: white;
        }
        
        .nav-button-quaternary {
            background: linear-gradient(135deg, #8c8f94 0%, #6c7781 100%);
            color: white;
        }
        
        .nav-button-quaternary:hover {
            background: linear-gradient(135deg, #6c7781 0%, #50575e 100%);
            color: white;
        }
        
        .nav-button-icon {
            font-size: 24px;
            margin-right: 15px;
            min-width: 30px;
        }
        
        .nav-button-content h3 {
            margin: 0 0 5px 0;
            font-size: 16px;
            font-weight: 600;
        }
        
        .nav-button-content p {
            margin: 0;
            font-size: 13px;
            opacity: 0.9;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .loading-spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid #0073aa;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-right: 8px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        </style>

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
                
                button.html('<span class="loading-spinner"></span>' + '<?php echo esc_js(__('Testing...', 'ecwid2woo-product-sync')); ?>').prop('disabled', true);
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
                                    .html('<strong>✅ <?php echo esc_js(__('CONNECTION SUCCESSFUL!', 'ecwid2woo-product-sync')); ?></strong><br>' + response.data.message)
                                    .show();
                        } else {
                            resultDiv.addClass('error')
                                    .html('<strong>❌ <?php echo esc_js(__('CONNECTION FAILED', 'ecwid2woo-product-sync')); ?></strong><br>' + response.data.message)
                                    .show();
                        }
                    },
                    error: function() {
                        resultDiv.addClass('error')
                                .html('<strong>❌ <?php echo esc_js(__('CONNECTION ERROR', 'ecwid2woo-product-sync')); ?></strong><br><?php echo esc_js(__('Connection test failed. Please try again.', 'ecwid2woo-product-sync')); ?>')
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
                            .html('<strong>✅ <?php echo esc_js(__('Settings saved successfully!', 'ecwid2woo-product-sync')); ?></strong>')
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
    }    private function render_full_sync_page() {
        ?>
        <div class="ecwid-page-header">
            <h1><?php esc_html_e('Full Data Sync', 'ecwid2woo-product-sync'); ?></h1>
            <p class="description"><?php esc_html_e('This will sync all categories and then all enabled products from Ecwid to WooCommerce. It is recommended to backup your WooCommerce data before running a full sync for the first time.', 'ecwid2woo-product-sync'); ?></p>
        </div>

        <!-- Navigation Bar -->
        <div class="ecwid-page-nav">
            <a href="<?php echo admin_url('admin.php?page=' . $this->settings_slug); ?>" class="nav-link">
                <span class="nav-icon">⚙️</span> <?php esc_html_e('Settings', 'ecwid2woo-product-sync'); ?>
            </a>
            <span class="nav-link current">
                <span class="nav-icon">🔄</span> <?php esc_html_e('Full Sync', 'ecwid2woo-product-sync'); ?>
            </span>
            <a href="<?php echo admin_url('admin.php?page=' . $this->category_sync_slug); ?>" class="nav-link">
                <span class="nav-icon">📁</span> <?php esc_html_e('Category Sync', 'ecwid2woo-product-sync'); ?>
            </a>
            <a href="<?php echo admin_url('admin.php?page=' . $this->partial_sync_slug); ?>" class="nav-link">
                <span class="nav-icon">🎯</span> <?php esc_html_e('Partial Sync', 'ecwid2woo-product-sync'); ?>
            </a>
        </div>

        <div class="ecwid-sync-container">
            <button id="load-full-sync-preview-button" class="button" style="margin-bottom: 15px;"><?php esc_html_e('Reload Sync Data', 'ecwid2woo-product-sync'); ?></button>

            <div id="full-sync-preview-container" style="display:none;">
                <div style="display:flex; flex-wrap: wrap; gap: 20px; margin-bottom:15px;">
                    <div style="flex:1; min-width: 300px;">
                        <h3><?php esc_html_e('Categories to be Synced:', 'ecwid2woo-product-sync'); ?></h3>
                        <div id="full-sync-category-preview-list" style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: #fff;">
                            <?php esc_html_e('Category list will appear here after loading preview...', 'ecwid2woo-product-sync'); ?>
                        </div>
                    </div>
                    <div style="flex:1; min-width: 300px;">
                        <h3><?php esc_html_e('Products to be Synced:', 'ecwid2woo-product-sync'); ?></h3>
                        <div id="full-sync-product-preview-list" style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: #fff;">
                            <?php esc_html_e('Product list will appear here after loading preview...', 'ecwid2woo-product-sync'); ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div id="full-sync-counts-info" style="margin-bottom: 10px; font-style: italic;"><?php esc_html_e('Item counts will be displayed here.', 'ecwid2woo-product-sync'); ?></div>
            <div id="full-sync-status" style="margin-bottom: 10px; font-weight: bold;"></div>
            
            <div style="margin-bottom: 5px;">
                <label for="full-sync-bar" style="display: block; margin-bottom: 2px; font-size: 0.9em;"><?php esc_html_e('Overall Progress:', 'ecwid2woo-product-sync'); ?></label>
                <div id="full-sync-progress-container" style="background: #f1f1f1; width: 100%; height: 24px; border: 1px solid #ccc; box-sizing: border-box; display:none;">
                    <div id="full-sync-bar" style="background: #007cba; width: 0%; height: 100%; text-align: center; color: #fff; line-height: 22px; font-size: 12px; transition: width 0.2s ease-in-out;">0%</div>
                </div>
            </div>

            <button id="full-sync-button" class="button button-primary" style="display:none;"><?php esc_html_e('Start Full Sync', 'ecwid2woo-product-sync'); ?></button>
            <button id="stop-full-sync-button" class="button button-secondary" style="background-color: #dc3545; color: white; border-color: #bd2130; display:none; margin-left: 10px;"><?php esc_html_e('STOP SYNC', 'ecwid2woo-product-sync'); ?></button>
            <div id="full-sync-log" style="margin-top: 15px; max-height: 400px; overflow-y: auto; border: 1px solid #eee; padding: 10px; background: #fafafa; font-size: 0.9em; line-height: 1.6; white-space: pre-wrap;"></div>
        </div>

        <style>
        .ecwid-page-header {
            margin-bottom: 20px;
        }
        
        .ecwid-page-nav {
            display: flex;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 8px;
            margin-bottom: 25px;
            gap: 4px;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            padding: 10px 16px;
            text-decoration: none;
            color: #495057;
            border-radius: 6px;
            transition: all 0.2s ease;
            font-weight: 500;
        }
        
        .nav-link:hover {
            background: #e9ecef;
            color: #212529;
            text-decoration: none;
        }
        
        .nav-link.current {
            background: #007cba;
            color: white;
        }
        
        .nav-icon {
            margin-right: 8px;
            font-size: 16px;
        }
        
        .ecwid-sync-container {
            background: #fff;
            border: 1px solid #e1e1e1;
            border-radius: 8px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        </style>
        <?php
    }    private function render_category_sync_page() {
        ?>
        <div class="ecwid-page-header">
            <h1><?php esc_html_e('Ecwid Category Sync', 'ecwid2woo-product-sync'); ?></h1>
            <p class="description"><?php esc_html_e('This will sync all categories from Ecwid to WooCommerce. You can load the list to see which categories will be imported.', 'ecwid2woo-product-sync'); ?></p>
        </div>

        <!-- Navigation Bar -->
        <div class="ecwid-page-nav">
            <a href="<?php echo admin_url('admin.php?page=' . $this->settings_slug); ?>" class="nav-link">
                <span class="nav-icon">⚙️</span> <?php esc_html_e('Settings', 'ecwid2woo-product-sync'); ?>
            </a>
            <a href="<?php echo admin_url('admin.php?page=' . $this->full_sync_slug); ?>" class="nav-link">
                <span class="nav-icon">🔄</span> <?php esc_html_e('Full Sync', 'ecwid2woo-product-sync'); ?>
            </a>
            <span class="nav-link current">
                <span class="nav-icon">📁</span> <?php esc_html_e('Category Sync', 'ecwid2woo-product-sync'); ?>
            </span>
            <a href="<?php echo admin_url('admin.php?page=' . $this->partial_sync_slug); ?>" class="nav-link">
                <span class="nav-icon">🎯</span> <?php esc_html_e('Partial Sync', 'ecwid2woo-product-sync'); ?>
            </a>
        </div>

        <div class="ecwid-sync-container">
            <div id="category-sync-initial-info" style="margin-bottom: 10px; font-style: italic;">
                <?php esc_html_e('Click "Load Ecwid Category List" to see details.', 'ecwid2woo-product-sync'); ?>
            </div>
            <button id="load-ecwid-categories-button" class="button" style="margin-bottom: 15px;"><?php esc_html_e('Reload Ecwid Categories', 'ecwid2woo-product-sync'); ?></button>
            
            <div id="category-list-container" style="margin-bottom:15px; max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: #fff; display: none;">
                <?php esc_html_e('Category list will appear here...', 'ecwid2woo-product-sync'); ?>
            </div>

            <button id="category-page-sync-button" class="button button-primary" style="margin-bottom:15px;"><?php esc_html_e('Start Category Sync', 'ecwid2woo-product-sync'); ?></button>
            <button id="fix-category-hierarchy-button" class="button" style="margin-left: 10px;"><?php esc_html_e('Fix Category Hierarchy', 'ecwid2woo-product-sync'); ?></button>
            
            <div id="category-page-sync-status" style="margin-bottom: 10px; font-weight: bold;"></div>
            <div id="category-page-sync-progress-container" style="background: #f1f1f1; width: 100%; height: 24px; margin-bottom: 10px; border: 1px solid #ccc; box-sizing: border-box; display:none;">
                <div id="category-page-sync-bar" style="background: #007cba; width: 0%; height: 100%; text-align: center; color: #fff; line-height: 22px; font-size: 12px; transition: width 0.2s ease-in-out;">0%</div>
            </div>
            <div id="category-page-sync-log" style="margin-top: 15px; max-height: 400px; overflow-y: auto; border: 1px solid #eee; padding: 10px; background: #fafafa; font-size: 0.9em; line-height: 1.6; white-space: pre-wrap;"></div>
        </div>

        <style>
        .ecwid-page-header {
            margin-bottom: 20px;
        }
        
        .ecwid-page-nav {
            display: flex;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 8px;
            margin-bottom: 25px;
            gap: 4px;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            padding: 10px 16px;
            text-decoration: none;
            color: #495057;
            border-radius: 6px;
            transition: all 0.2s ease;
            font-weight: 500;
        }
        
        .nav-link:hover {
            background: #e9ecef;
            color: #212529;
            text-decoration: none;
        }
        
        .nav-link.current {
            background: #007cba;
            color: white;
        }
        
        .nav-icon {
            margin-right: 8px;
            font-size: 16px;
        }
        
        .ecwid-sync-container {
            background: #fff;
            border: 1px solid #e1e1e1;
            border-radius: 8px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        </style>
        <?php
    }    private function render_partial_sync_page() {
        ?>
        <div class="ecwid-page-header">
            <h1><?php esc_html_e('Partial Product Sync', 'ecwid2woo-product-sync'); ?></h1>
            <p><?php esc_html_e('Load products from your Ecwid store and select which ones to import or update in WooCommerce.', 'ecwid2woo-product-sync'); ?></p>
        </div>        <!-- Navigation Bar -->
        <div class="ecwid-page-nav">
            <a href="<?php echo admin_url('admin.php?page=' . $this->settings_slug); ?>" class="nav-link">
                <span class="nav-icon">⚙️</span> <?php esc_html_e('Settings', 'ecwid2woo-product-sync'); ?>
            </a>
            <a href="<?php echo admin_url('admin.php?page=' . $this->full_sync_slug); ?>" class="nav-link">
                <span class="nav-icon">🔄</span> <?php esc_html_e('Full Sync', 'ecwid2woo-product-sync'); ?>
            </a>
            <a href="<?php echo admin_url('admin.php?page=' . $this->category_sync_slug); ?>" class="nav-link">
                <span class="nav-icon">📁</span> <?php esc_html_e('Category Sync', 'ecwid2woo-product-sync'); ?>
            </a>
            <span class="nav-link current">
                <span class="nav-icon">🎯</span> <?php esc_html_e('Product Sync', 'ecwid2woo-product-sync'); ?>
            </span>
            <a href="<?php echo admin_url('edit.php?post_type=ecwid_placeholder'); ?>" class="nav-link">
                <span class="nav-icon">🔧</span> <?php esc_html_e('Placeholders', 'ecwid2woo-product-sync'); ?>
            </a>
        </div>

        <div class="ecwid-sync-container">
            <div id="selective-sync-initial-info" style="margin-bottom: 10px; padding: 5px; border: 1px solid #e0e0e0; background-color: #f9f9f9;">
                <!-- This will be populated by JavaScript -->
            </div>            <button id="load-ecwid-products-button" class="button"><?php esc_html_e('Reload Products', 'ecwid2woo-product-sync'); ?></button>
            <div id="selective-product-list-container" style="margin-top: 15px; max-height: 400px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: #fff;">
                <?php esc_html_e('Product list will appear here...', 'ecwid2woo-product-sync'); ?>
            </div>
            <button id="import-selected-products-button" class="button button-primary" style="margin-top: 10px; display: none;"><?php esc_html_e('Import Selected Products', 'ecwid2woo-product-sync'); ?></button>

            <div id="selective-sync-status" style="margin-top:15px; margin-bottom: 10px; font-weight: bold;"></div>
            <div id="selective-sync-progress-container" style="background: #f1f1f1; width: 100%; height: 24px; margin-bottom: 10px; border: 1px solid #ccc; box-sizing: border-box; display:none;">
                <div id="selective-sync-bar" style="background: #007cba; width: 0%; height: 100%; text-align: center; color: #fff; line-height: 22px; font-size: 12px; transition: width 0.2s ease-in-out;">0%</div>
            </div>
            <div id="selective-sync-log" style="margin-top: 15px; max-height: 400px; overflow-y: auto; border: 1px solid #eee; padding: 10px; background: #fafafa; font-size: 0.9em; line-height: 1.6; white-space: pre-wrap;"></div>
        </div>
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
                // MODIFIED: Fetch options and combination IDs
                'responseFields' => 'items(id,sku,name,enabled,options,combinations(id))' 
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
                    // Ensure 'enabled' check is still relevant if API guarantees it
                    // if (isset($item['enabled']) && $item['enabled']) { 
                        $all_products[] = [
                            'id' => $item['id'] ?? null,
                            'name' => $item['name'] ?? 'N/A',
                            'sku' => $item['sku'] ?? 'N/A',
                            'enabled' => $item['enabled'] ?? false, // Ensure default
                            'options' => $item['options'] ?? [], // Add options
                            'combinations' => $item['combinations'] ?? [] // Add combinations (array of {id:val})
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
        set_time_limit(0); // Try to disable time limit for this initial product fetch and parent import

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

        // MODIFICATION: Change the default batch size from 10 to a smaller number, e.g., 5.
        // This will fetch and process fewer items per AJAX call, leading to more frequent updates.
        $limit_per_api_call = apply_filters('ecwid_wc_sync_batch_api_limit', 5); // Changed from 10 to 5
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
        $batch_item_results = []; // <-- ADDED: To store structured results

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
                        $batch_item_results[] = $result_array; // <-- ADDED: Store structured result
                        if ($result_array['status'] === 'imported' || $result_array['status'] === 'imported_parent_pending_variations') $imported_count++;
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
                        $batch_detailed_logs[] = "--- [CRITICAL ERROR] Failed to process item: " . esc_html($current_item_log_name) . ". Import function did not return expected result or status. Result: " . print_r($result_array, true) . " ---";
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
            'batch_logs' => $batch_detailed_logs,
            'batch_item_results' => $batch_item_results // <-- ADDED: Send structured results
        ]);
    }

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

            $parent_wc_term_id = 0; // Default to 0 (no parent)
            if (isset($item['parentId']) && intval($item['parentId']) > 0) {
                $parent_ecwid_id = intval($item['parentId']);
                $parent_wc_term_id_found = $this->get_term_id_by_ecwid_id($parent_ecwid_id, 'product_cat', true); // Bypass cache for this lookup

                if ($parent_wc_term_id_found) {
                    $args['parent'] = $parent_wc_term_id_found;
                    $parent_wc_term_id = $parent_wc_term_id_found; // Keep track of the actual WC parent ID
                    $category_logs[] = "Parent category (Ecwid ID: $parent_ecwid_id) mapped to WC Term ID: {$args['parent']}.";
                } else {
                    // Parent not found by direct Ecwid ID mapping, try/create placeholder
                    $category_logs[] = "Parent category (Ecwid ID: $parent_ecwid_id) not found directly. Attempting placeholder logic.";
                    $missing_parent_placeholder = $this->get_or_create_missing_parent_placeholder($parent_ecwid_id);
                    
                    if ($missing_parent_placeholder && isset($missing_parent_placeholder['term_id'])) {
                        $placeholder_term_id_to_use = (int) $missing_parent_placeholder['term_id'];
                        $category_logs[] = "Placeholder logic returned term ID: $placeholder_term_id_to_use for Ecwid parent ID: $parent_ecwid_id. Placeholder details: " . wp_json_encode($missing_parent_placeholder);

                        // Explicitly check if this placeholder term ID actually exists right now
                        $term_check_result = term_exists($placeholder_term_id_to_use, 'product_cat');
                        
                        if ($term_check_result) {
                            $actual_term_id_from_check = is_array($term_check_result) ? $term_check_result['term_id'] : $term_check_result;
                            if ((int)$actual_term_id_from_check === $placeholder_term_id_to_use) {
                                $category_logs[] = "CONFIRMED: Placeholder WC Term ID $placeholder_term_id_to_use (for Ecwid parent $parent_ecwid_id) EXISTS right before use.";
                                $args['parent'] = $placeholder_term_id_to_use;
                                $parent_wc_term_id = $placeholder_term_id_to_use; // Update actual WC parent ID
                                clean_term_cache($placeholder_term_id_to_use, 'product_cat'); // Keep this cache clean
                                $category_logs[] = $missing_parent_placeholder['is_new']
                                    ? "Created placeholder parent category '{$missing_parent_placeholder['name']}' (WC Term ID: {$args['parent']}) for missing Ecwid parent ID: $parent_ecwid_id."
                                    : "Using existing placeholder parent category '{$missing_parent_placeholder['name']}' (WC Term ID: {$args['parent']}) for Ecwid parent ID: $parent_ecwid_id.";
                            } else {
                                $category_logs[] = "[CRITICAL_ERROR] term_exists() check for placeholder ID $placeholder_term_id_to_use returned a different ID: " . wp_json_encode($term_check_result) . ". This should not happen. Proceeding without parent.";
                                $parent_wc_term_id = 0; // Reset to no parent
                                unset($args['parent']);
                            }
                        } else {
                            $category_logs[] = "[CRITICAL_ERROR] Placeholder WC Term ID $placeholder_term_id_to_use (for Ecwid parent $parent_ecwid_id) DOES NOT EXIST according to term_exists() right before use. Placeholder data from get_or_create: " . wp_json_encode($missing_parent_placeholder) . ". Proceeding without parent.";
                            $parent_wc_term_id = 0; // Reset to no parent
                            unset($args['parent']); // Do not attempt to use a non-existent parent
                        }
                    } else {
                        $category_logs[] = "[WARNING] Parent category (Ecwid ID: $parent_ecwid_id) not found and placeholder logic did not create a valid term ID. This category will be top-level for now.";
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
                        $existing_attachment = $wpdb->get_var($wpdb->prepare(
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

                // 2. Add new gallery images from Ecwid that aren't already processed
                foreach ($item['galleryImages'] as $gallery_image_data) {
                    $gallery_image_url = $gallery_image_data['hdThumbnailUrl'] ?? $gallery_image_data['originalImageUrl'] ?? $gallery_image_data['url'] ?? null;
                    if ($gallery_image_url && !in_array($gallery_image_url, $processed_ecwid_gallery_urls)) {
                        // Check if this image URL already exists in the media library
                        global $wpdb;
                        $existing_gallery_attachment = $wpdb->get_var($wpdb->prepare(
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
                    
                    // MODIFIED: Create the term if it doesn't exist
                    if (!$term_object || is_wp_error($term_object)) {
                        $batch_logs[] = "Term '$term_value_from_ecwid' not found in '$wc_attr_taxonomy_slug'. Creating it now...";
                        
                        $term_slug = sanitize_title($term_value_from_ecwid);
                        $term_result = wp_insert_term($term_value_from_ecwid, $wc_attr_taxonomy_slug, ['slug' => $term_slug]);
                        
                        if (is_wp_error($term_result)) {
                            $batch_logs[] = "[ERROR] Failed to create term '$term_value_from_ecwid' for attribute '$wc_attr_taxonomy_slug': " . $term_result->get_error_message();
                        } else {
                            // Get the newly created term
                            $term_object = get_term_by('id', $term_result['term_id'], $wc_attr_taxonomy_slug);
                            $batch_logs[] = "Successfully created term '$term_value_from_ecwid' with ID {$term_result['term_id']} for attribute '$wc_attr_taxonomy_slug'.";
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

    public function ajax_fetch_full_sync_counts() {
        check_ajax_referer('ecwid_wc_sync_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('You do not have permission to perform this action.', 'ecwid2woo-product-sync')], 403);
            return;
        }
        set_time_limit(300); // 5 minutes

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
            $errors[] = sprintf(__('Error fetching categories from Ecwid: %s', 'ecwid2woo-product-sync'), $cat_response->get_error_message());
        } else {
            $cat_body = wp_remote_retrieve_body($cat_response);
            $cat_data = json_decode($cat_body, true);
            $cat_http_code = wp_remote_retrieve_response_code($cat_response);
            
            if ($cat_http_code === 200 && isset($cat_data['items'])) {
                $category_count = isset($cat_data['total']) ? $cat_data['total'] : count($cat_data['items']);
                $categories_preview = $cat_data['items'];
            } else {
                $errors[] = sprintf(__('Failed to fetch categories. HTTP Status: %s', 'ecwid2woo-product-sync'), $cat_http_code);
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
            $errors[] = sprintf(__('Error fetching products from Ecwid: %s', 'ecwid2woo-product-sync'), $prod_response->get_error_message());
        } else {
            $prod_body = wp_remote_retrieve_body($prod_response);
            $prod_data = json_decode($prod_body, true);
            $prod_http_code = wp_remote_retrieve_response_code($prod_response);
            
            if ($prod_http_code === 200 && isset($prod_data['items'])) {
                $product_count = isset($prod_data['total']) ? $prod_data['total'] : count($prod_data['items']);
                $products_preview = $prod_data['items'];
            } else {
                $errors[] = sprintf(__('Failed to fetch products. HTTP Status: %s', 'ecwid2woo-product-sync'), $prod_http_code);
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
                'products_count' => $product_count
            ]);
        }
    }

    public function ajax_fetch_categories_for_display() {
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

        $all_categories = [];
        $offset = 0;
        $limit = 100;

        do {
            $query_params = [
                'limit' => $limit,
                'offset' => $offset,
                'responseFields' => 'items(id,name,parentId),total'
            ];
            $api_url = add_query_arg($query_params, $api_essentials['base_url'] . '/categories');

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

            $items_from_api = $body['items'] ?? [];
            $all_categories = array_merge($all_categories, $items_from_api);

            $count_in_response = $body['count'] ?? count($items_from_api);
            $total_from_api = $body['total'] ?? count($items_from_api);
            $offset += $count_in_response;

            if ($count_in_response === 0 || $offset >= $total_from_api) {
                break;
            }

        } while (true);

        wp_send_json_success([
            'categories' => $all_categories,
            'total_count' => count($all_categories)
        ]);
    }

    // Add this method to handle AJAX connection testing
    public function ajax_test_api_connection() {
        check_ajax_referer('ecwid_wc_sync_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized', 'ecwid2woo-product-sync')]);
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
            wp_send_json_error(['message' => __('Connection failed: ', 'ecwid2woo-product-sync') . $response->get_error_message()]);
            return;
        }

        $http_code = wp_remote_retrieve_response_code($response);
        if ($http_code === 200) {
            wp_send_json_success(['message' => __('Connection successful!', 'ecwid2woo-product-sync')]);
        } else {
            wp_send_json_error(['message' => __('API returned error code: ', 'ecwid2woo-product-sync') . $http_code]);
        }
    }
}

new Ecwid_WC_Sync();
?>
