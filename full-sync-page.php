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
    // amazonq-ignore-next-line
    exit;
}

class Ecwid2Woo_Full_Sync {
    
    private $parent_plugin;
    // Customer and order sync disabled until fully tested
    private $sync_steps = ['categories', 'products']; // Define order of sync for full sync
    
    public function __construct($parent_plugin) {
        $this->parent_plugin = $parent_plugin;
        
        // Register AJAX handlers for full sync operations
        // amazonq-ignore-next-line
        add_action('wp_ajax_ecwid_wc_batch_sync', [$this, 'ajax_batch_sync']);
        // amazonq-ignore-next-line
        add_action('wp_ajax_ecwid_wc_fetch_full_sync_counts', [$this, 'ajax_fetch_full_sync_counts']);
    }
    
    /**
     * Render the Full Sync page
     */
    public function render_full_sync_page() {
        $settings_url = admin_url('admin.php?page=' . $this->parent_plugin->settings_slug);
        $category_sync_url = admin_url('admin.php?page=' . $this->parent_plugin->category_sync_slug);
        $product_sync_url = admin_url('admin.php?page=' . $this->parent_plugin->partial_sync_slug);
        ?>
        <div class="ecwid-full-sync-dashboard">
            <header class="e2w-command-bar">
                <div class="e2w-command-title">
                    <span class="e2w-brand-mark" aria-hidden="true"><span class="dashicons dashicons-update"></span></span>
                    <div>
                        <span class="e2w-eyebrow"><?php esc_html_e('Ecwid to WooCommerce', 'metrotechs-e2w-sync'); ?></span>
                        <div class="e2w-title-row">
                            <h1><?php esc_html_e('Full Sync', 'metrotechs-e2w-sync'); ?></h1>
                            <span id="e2w-sync-state" class="e2w-state-pill is-loading" role="status" aria-live="polite">
                                <span class="e2w-state-dot" aria-hidden="true"></span>
                                <span id="e2w-sync-state-label"><?php esc_html_e('Loading', 'metrotechs-e2w-sync'); ?></span>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="e2w-command-actions">
                    <button id="full-sync-button" class="button sync-button-primary e2w-button e2w-button-primary" type="button">
                        <?php esc_html_e('Start Full Sync', 'metrotechs-e2w-sync'); ?>
                    </button>
                    <button id="pause-full-sync-button" class="button sync-button-pause e2w-button e2w-button-pause" type="button">
                        <?php esc_html_e('Pause', 'metrotechs-e2w-sync'); ?>
                    </button>
                    <button id="stop-full-sync-button" class="button sync-button-stop e2w-button e2w-button-stop" type="button">
                        <?php esc_html_e('Stop', 'metrotechs-e2w-sync'); ?>
                    </button>
                    <a href="<?php echo esc_url($settings_url); ?>" class="button e2w-button e2w-button-secondary">
                        <span class="dashicons dashicons-admin-generic" aria-hidden="true"></span>
                        <?php esc_html_e('Settings', 'metrotechs-e2w-sync'); ?>
                    </a>
                    <button id="load-full-sync-preview-button" class="button e2w-button e2w-button-secondary" type="button">
                        <span class="dashicons dashicons-update" aria-hidden="true"></span>
                        <span class="e2w-button-label"><?php esc_html_e('Reload Sync Data', 'metrotechs-e2w-sync'); ?></span>
                    </button>
                </div>
            </header>

            <nav class="e2w-subnav" aria-label="<?php esc_attr_e('Sync sections', 'metrotechs-e2w-sync'); ?>">
                <a href="<?php echo esc_url($settings_url); ?>"><span class="dashicons dashicons-admin-generic" aria-hidden="true"></span><?php esc_html_e('Settings', 'metrotechs-e2w-sync'); ?></a>
                <span class="is-current" aria-current="page"><span class="dashicons dashicons-update" aria-hidden="true"></span><?php esc_html_e('Full Sync', 'metrotechs-e2w-sync'); ?></span>
                <a href="<?php echo esc_url($category_sync_url); ?>"><span class="dashicons dashicons-category" aria-hidden="true"></span><?php esc_html_e('Category Sync', 'metrotechs-e2w-sync'); ?></a>
                <a href="<?php echo esc_url($product_sync_url); ?>"><span class="dashicons dashicons-products" aria-hidden="true"></span><?php esc_html_e('Product Sync', 'metrotechs-e2w-sync'); ?></a>
            </nav>

            <div class="e2w-dashboard-body">
                <main class="e2w-dashboard-main">
                    <div id="full-sync-initial-info" class="e2w-system-notice is-loading" aria-live="polite">
                        <span class="loading-spinner" aria-hidden="true"></span>
                        <span><?php esc_html_e('Loading store data from Ecwid…', 'metrotechs-e2w-sync'); ?></span>
                    </div>

                    <section class="e2w-overview-grid" aria-label="<?php esc_attr_e('Sync overview', 'metrotechs-e2w-sync'); ?>">
                        <article class="e2w-panel e2w-scope-panel">
                            <header class="e2w-panel-header">
                                <h2><span class="dashicons dashicons-editor-ul" aria-hidden="true"></span><?php esc_html_e('Scope', 'metrotechs-e2w-sync'); ?></h2>
                            </header>
                            <div class="e2w-scope-stats">
                                <div class="e2w-scope-stat">
                                    <span class="e2w-metric-icon is-category"><span class="dashicons dashicons-category" aria-hidden="true"></span></span>
                                    <div><strong id="e2w-category-count">—</strong><span><?php esc_html_e('Categories', 'metrotechs-e2w-sync'); ?></span></div>
                                </div>
                                <div class="e2w-scope-stat">
                                    <span class="e2w-metric-icon is-product"><span class="dashicons dashicons-products" aria-hidden="true"></span></span>
                                    <div><strong id="e2w-product-count">—</strong><span><?php esc_html_e('Products', 'metrotechs-e2w-sync'); ?></span></div>
                                </div>
                            </div>
                            <div class="e2w-panel-footer">
                                <span><?php esc_html_e('Last loaded', 'metrotechs-e2w-sync'); ?></span>
                                <strong id="e2w-last-loaded"><?php esc_html_e('Not yet loaded', 'metrotechs-e2w-sync'); ?></strong>
                            </div>
                            <div id="full-sync-counts-info" class="sync-counts-info screen-reader-text"><?php esc_html_e('Item counts will be displayed here.', 'metrotechs-e2w-sync'); ?></div>
                        </article>

                        <article class="e2w-panel e2w-progress-panel">
                            <header class="e2w-panel-header">
                                <h2><span class="dashicons dashicons-chart-pie" aria-hidden="true"></span><?php esc_html_e('Progress', 'metrotechs-e2w-sync'); ?></h2>
                                <div class="e2w-progress-number"><span id="e2w-progress-percent">0%</span></div>
                            </header>
                            <div class="sync-progress-wrapper">
                                <span id="e2w-progress-description" class="screen-reader-text"><?php esc_html_e('Overall sync progress', 'metrotechs-e2w-sync'); ?></span>
                                <div id="full-sync-progress-container" class="sync-progress-container" role="progressbar" aria-labelledby="e2w-progress-description" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
                                    <div id="full-sync-bar" class="sync-progress-bar">0%</div>
                                </div>
                            </div>
                            <div class="e2w-progress-legend" aria-hidden="true">
                                <span><i class="is-category"></i><?php esc_html_e('Categories', 'metrotechs-e2w-sync'); ?></span>
                                <span><i class="is-product"></i><?php esc_html_e('Products', 'metrotechs-e2w-sync'); ?></span>
                            </div>
                            <div class="e2w-panel-footer e2w-progress-footer">
                                <strong id="full-sync-status" class="sync-status"><?php esc_html_e('Loading sync data…', 'metrotechs-e2w-sync'); ?></strong>
                                <span id="e2w-progress-total"><?php esc_html_e('Total items: —', 'metrotechs-e2w-sync'); ?></span>
                            </div>
                        </article>
                    </section>

                    <section class="e2w-panel e2w-activity-panel" aria-labelledby="e2w-activity-title">
                        <div class="e2w-activity-tabs" role="tablist" aria-label="<?php esc_attr_e('Activity filters', 'metrotechs-e2w-sync'); ?>">
                            <button class="e2w-activity-tab is-active" type="button" role="tab" aria-selected="true" data-e2w-log-filter="all">
                                <span class="dashicons dashicons-update" aria-hidden="true"></span><span id="e2w-activity-title"><?php esc_html_e('Activity', 'metrotechs-e2w-sync'); ?></span><b data-e2w-log-count="all">0</b>
                            </button>
                            <button class="e2w-activity-tab" type="button" role="tab" aria-selected="false" data-e2w-log-filter="error">
                                <span class="dashicons dashicons-warning" aria-hidden="true"></span><?php esc_html_e('Errors', 'metrotechs-e2w-sync'); ?><b data-e2w-log-count="error">0</b>
                            </button>
                            <button class="e2w-activity-tab" type="button" role="tab" aria-selected="false" data-e2w-log-filter="warning">
                                <span class="dashicons dashicons-flag" aria-hidden="true"></span><?php esc_html_e('Warnings', 'metrotechs-e2w-sync'); ?><b data-e2w-log-count="warning">0</b>
                            </button>
                            <button class="e2w-activity-tab" type="button" role="tab" aria-selected="false" data-e2w-log-filter="skip">
                                <span class="dashicons dashicons-controls-skipforward" aria-hidden="true"></span><?php esc_html_e('Skips', 'metrotechs-e2w-sync'); ?><b data-e2w-log-count="skip">0</b>
                            </button>
                            <button class="e2w-activity-tab" type="button" role="tab" aria-selected="false" data-e2w-log-filter="success">
                                <span class="dashicons dashicons-yes-alt" aria-hidden="true"></span><?php esc_html_e('Success', 'metrotechs-e2w-sync'); ?><b data-e2w-log-count="success">0</b>
                            </button>
                        </div>

                        <div class="e2w-activity-toolbar">
                            <label class="e2w-log-search">
                                <span class="screen-reader-text"><?php esc_html_e('Filter sync activity', 'metrotechs-e2w-sync'); ?></span>
                                <span class="dashicons dashicons-search" aria-hidden="true"></span>
                                <input id="e2w-log-search" type="search" placeholder="<?php esc_attr_e('SKU, Ecwid ID, WooCommerce ID, or message', 'metrotechs-e2w-sync'); ?>">
                            </label>
                            <div class="e2w-toolbar-actions">
                                <label class="e2w-toggle-control" for="e2w-auto-scroll">
                                    <input id="e2w-auto-scroll" type="checkbox" checked>
                                    <span class="e2w-toggle" aria-hidden="true"></span>
                                    <span><?php esc_html_e('Auto-scroll', 'metrotechs-e2w-sync'); ?></span>
                                </label>
                                <button id="e2w-download-log" class="button e2w-button e2w-button-secondary e2w-button-compact" type="button">
                                    <span class="dashicons dashicons-download" aria-hidden="true"></span><?php esc_html_e('Download Log', 'metrotechs-e2w-sync'); ?>
                                </button>
                            </div>
                        </div>

                        <div id="full-sync-log" class="sync-log e2w-activity-log" role="log" aria-live="polite" aria-relevant="additions">
                            <div id="e2w-log-empty" class="e2w-log-empty">
                                <span class="dashicons dashicons-list-view" aria-hidden="true"></span>
                                <strong><?php esc_html_e('Sync activity will appear here', 'metrotechs-e2w-sync'); ?></strong>
                                <span><?php esc_html_e('Load the store data, review the scope, then start a full sync.', 'metrotechs-e2w-sync'); ?></span>
                            </div>
                        </div>
                        <div id="e2w-log-filter-empty" class="e2w-log-filter-empty" hidden><?php esc_html_e('No activity matches this filter.', 'metrotechs-e2w-sync'); ?></div>
                    </section>
                </main>

                <aside class="e2w-context-rail" aria-label="<?php esc_attr_e('Sync context', 'metrotechs-e2w-sync'); ?>">
                    <h2><?php esc_html_e('Context', 'metrotechs-e2w-sync'); ?></h2>

                    <details class="e2w-context-card" open>
                        <summary><span><span class="dashicons dashicons-cloud" aria-hidden="true"></span><?php esc_html_e('What will sync', 'metrotechs-e2w-sync'); ?></span></summary>
                        <div class="e2w-context-card-body">
                            <div class="e2w-context-count-row">
                                <span><i class="is-category"></i><?php esc_html_e('Categories', 'metrotechs-e2w-sync'); ?></span>
                                <strong id="e2w-context-category-count">—</strong>
                            </div>
                            <div class="e2w-context-count-row">
                                <span><i class="is-product"></i><?php esc_html_e('Products', 'metrotechs-e2w-sync'); ?></span>
                                <strong id="e2w-context-product-count">—</strong>
                            </div>
                            <details id="e2w-preview-details" class="e2w-preview-details">
                                <summary><?php esc_html_e('View item preview', 'metrotechs-e2w-sync'); ?></summary>
                                <div id="full-sync-preview-container" class="sync-preview-container">
                                    <div class="sync-preview-grid">
                                        <div class="sync-preview-column">
                                            <h3><?php esc_html_e('Categories', 'metrotechs-e2w-sync'); ?></h3>
                                            <div id="full-sync-category-preview-list" class="sync-preview-list"></div>
                                        </div>
                                        <div class="sync-preview-column">
                                            <h3><?php esc_html_e('Products', 'metrotechs-e2w-sync'); ?></h3>
                                            <div id="full-sync-product-preview-list" class="sync-preview-list"></div>
                                        </div>
                                    </div>
                                </div>
                            </details>
                        </div>
                    </details>

                    <details class="e2w-context-card" open>
                        <summary><span><span class="dashicons dashicons-performance" aria-hidden="true"></span><?php esc_html_e('Sync options', 'metrotechs-e2w-sync'); ?></span></summary>
                        <div class="e2w-context-card-body e2w-option-list">
                            <div><span><?php esc_html_e('Adaptive batching', 'metrotechs-e2w-sync'); ?></span><strong class="e2w-option-on"><?php esc_html_e('On', 'metrotechs-e2w-sync'); ?></strong></div>
                            <div><span><?php esc_html_e('Category batch', 'metrotechs-e2w-sync'); ?></span><strong id="e2w-category-batch-size">—</strong></div>
                            <div><span><?php esc_html_e('Product batch', 'metrotechs-e2w-sync'); ?></span><strong id="e2w-product-batch-size">—</strong></div>
                            <div><span><?php esc_html_e('Retry protection', 'metrotechs-e2w-sync'); ?></span><strong class="e2w-option-on"><?php esc_html_e('On', 'metrotechs-e2w-sync'); ?></strong></div>
                        </div>
                    </details>

                    <details class="e2w-context-card" open>
                        <summary><span><span class="dashicons dashicons-admin-users" aria-hidden="true"></span><?php esc_html_e('Current item', 'metrotechs-e2w-sync'); ?></span></summary>
                        <div class="e2w-context-card-body">
                            <strong id="e2w-current-item-name" class="e2w-current-item-name"><?php esc_html_e('Waiting to start', 'metrotechs-e2w-sync'); ?></strong>
                            <dl class="e2w-current-item-meta">
                                <div><dt><?php esc_html_e('Type', 'metrotechs-e2w-sync'); ?></dt><dd id="e2w-current-item-type">—</dd></div>
                                <div><dt><?php esc_html_e('Ecwid ID', 'metrotechs-e2w-sync'); ?></dt><dd id="e2w-current-item-ecwid-id">—</dd></div>
                                <div><dt><?php esc_html_e('Woo ID', 'metrotechs-e2w-sync'); ?></dt><dd id="e2w-current-item-wc-id">—</dd></div>
                                <div><dt><?php esc_html_e('Result', 'metrotechs-e2w-sync'); ?></dt><dd id="e2w-current-item-result">—</dd></div>
                            </dl>
                        </div>
                    </details>

                    <details class="e2w-context-card" open>
                        <summary><span><span class="dashicons dashicons-sos" aria-hidden="true"></span><?php esc_html_e('Before you start', 'metrotechs-e2w-sync'); ?></span></summary>
                        <div class="e2w-context-card-body e2w-help-copy">
                            <p><?php esc_html_e('Back up WooCommerce before the first full sync. Categories run first so product relationships can be created correctly.', 'metrotechs-e2w-sync'); ?></p>
                            <p><a href="<?php echo esc_url($settings_url); ?>"><?php esc_html_e('Review connection and batch settings', 'metrotechs-e2w-sync'); ?></a></p>
                        </div>
                    </details>
                </aside>
            </div>
        </div>
        <?php
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

        return $sorted;
    }

    /**
     * AJAX handler for batch sync operations
     */
    public function ajax_batch_sync() {
        // Start output buffering to prevent PHP notices/warnings from corrupting JSON response
        ob_start();
        
        // Check WooCommerce availability first
        if (!class_exists('WooCommerce')) {
            ob_end_clean(); // Discard any output
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
            ob_end_clean(); // Discard any output
            wp_send_json_error(['message' => __('Unauthorized', 'metrotechs-e2w-sync')], 403); return;
        }

        $sync_type = isset($_POST['sync_type']) ? sanitize_key(wp_unslash($_POST['sync_type'])) : '';
        if (!in_array($sync_type, $this->sync_steps, true)) {
            ob_end_clean();
            wp_send_json_error(['message' => __('Invalid sync type for full sync.', 'metrotechs-e2w-sync')], 400);
            return;
        }

        $request_started_at = microtime(true);
        $request_deadline = $this->parent_plugin->start_sync_request_deadline(55);
        $max_batch_seconds = max(1, (int) floor($request_deadline - $request_started_at));
        
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
        if ($memory_in_bytes !== -1 && $memory_in_bytes < $minimum_memory) {
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

        $api_essentials = $this->parent_plugin->_get_api_essentials();
        if (is_wp_error($api_essentials)) {
            wp_send_json_error(['message' => $api_essentials->get_error_message()]); return;
        }

        $offset = isset($_POST['offset']) ? max(0, intval($_POST['offset'])) : 0;

        // Currency is store-level data. Sync it once at the beginning of the
        // category phase, not before every one of thousands of catalog batches.
        if ($sync_type === 'categories' && $offset === 0) {
            $currency_sync_logs = [];
            $currency_sync_result = $this->parent_plugin->sync_currency_settings($currency_sync_logs);
            if (defined('WP_DEBUG') && WP_DEBUG && !empty($currency_sync_result)) {
                error_log("Ecwid Sync: Currency sync result for batch sync: " . print_r($currency_sync_result, true)); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log,WordPress.PHP.DevelopmentFunctions.error_log_print_r -- Debug logging wrapped in WP_DEBUG check
            }
        }

        // MODIFICATION: Use different batch sizes based on content type for optimal performance
        // Categories are lighter and can handle larger batches, products are heavier due to variations
        // Check if client sent a reduced batch size (adaptive batch sizing for timeout recovery)
        $client_batch_size = isset($_POST['batch_size']) ? intval($_POST['batch_size']) : 0;
        
        // Determine appropriate batch size based on sync type and available memory
        if ($sync_type === 'categories') {
            $default_batch_size = ECWID2WOO_CATEGORY_BATCH_SIZE;
        } else {
            $default_batch_size = ECWID2WOO_PRODUCT_BATCH_SIZE;
        }
        
        // Use client-provided batch size if valid (for adaptive timeout recovery)
        // When manual batch override is enabled in settings, allow exceeding the default cap
        $options = get_option('ecwid_wc_sync_options');
        $manual_override = !empty($options['manual_batch_override']);

        if ($client_batch_size > 0) {
            if ($manual_override) {
                // Manual override: trust the user's configured batch size (hard cap at 100)
                $default_batch_size = min($client_batch_size, 100);
            } elseif ($client_batch_size <= $default_batch_size) {
                // Adaptive recovery: only allow reducing below default
                $default_batch_size = $client_batch_size;
            }
            if (defined('WP_DEBUG') && WP_DEBUG) {
                // amazonq-ignore-next-line
                error_log("Ecwid Sync: Using client-requested batch size: $default_batch_size for $sync_type" . ($manual_override ? ' (manual override)' : ' (adaptive timeout recovery)')); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            }
        }
        
        // Adaptive batch sizing based on memory
        $available_memory = wp_convert_hr_to_bytes(ini_get('memory_limit'));
        $used_memory = function_exists('memory_get_usage') ? memory_get_usage(true) : 0;
        $free_memory = $available_memory === -1 ? PHP_INT_MAX : ($available_memory - $used_memory);
        
        // If we have less than 128MB free, reduce batch size
        if ($free_memory < (128 * 1024 * 1024)) {
            $memory_factor = max(0.5, min(1.0, $free_memory / (128 * 1024 * 1024)));
            $default_batch_size = max(2, intval($default_batch_size * $memory_factor));
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Ecwid Sync: Reducing batch size due to low memory. Free: " . size_format($free_memory) . ", Adjusted batch: $default_batch_size"); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            }
        }
        
        // The mutation limit remains conservative for image-heavy imports.
        // Fast Skip uses a separate, larger read-only comparison window.
        $mutation_limit_per_request = absint(apply_filters('ecwid_wc_sync_batch_api_limit', $default_batch_size, $sync_type));
        $mutation_limit_per_request = max(1, min(100, $mutation_limit_per_request));
        $fast_skip_scan_cap = absint(apply_filters('ecwid2woo_fast_skip_scan_limit', 100, $sync_type));
        $fast_skip_scan_cap = max($mutation_limit_per_request, min(100, $fast_skip_scan_cap));
        $requested_scan_size = isset($_POST['scan_size']) ? absint($_POST['scan_size']) : $fast_skip_scan_cap;
        $fast_skip_scan_limit = max($mutation_limit_per_request, min($fast_skip_scan_cap, $requested_scan_size));

        if (defined('WP_DEBUG') && WP_DEBUG) {
            // amazonq-ignore-next-line
            error_log("Ecwid Sync: FULL BATCH - Type: $sync_type, Offset: $offset, Mutation Limit: $mutation_limit_per_request, Fast Skip Scan: $fast_skip_scan_limit, Memory: " . size_format($free_memory) . " free"); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging wrapped in WP_DEBUG check
        }

        $endpoints = ['products' => '/products', 'categories' => '/categories'];

        $endpoint = $endpoints[$sync_type];
        $api_url_base = $api_essentials['base_url'] . $endpoint;
        $query_params_for_url = ['limit' => $fast_skip_scan_limit, 'offset' => $offset];

        if ($sync_type === 'products') {
            // Keep execution aligned with the preview and product-page behavior:
            // disabled Ecwid products are reviewable, but Full Sync imports enabled products only.
            $query_params_for_url['enabled'] = 'true';
            $query_params_for_url['responseFields'] = 'items(id,sku,name,price,description,shortDescription,enabled,weight,quantity,unlimited,categoryIds,hdThumbnailUrl,imageUrl,galleryImages,options,combinations(id,sku,price,compareToPrice,defaultDisplayedPrice,defaultDisplayedCompareToPrice,options,quantity),productClassId,attributes,compareToPrice,dimensions,shipping),total,count,offset,limit';
        } else {
            $query_params_for_url['responseFields'] = 'items(id,name,parentId,description,hdThumbnailUrl,originalImageUrl,updateTimestamp),total,count,offset,limit';
        }

        $api_url = add_query_arg($query_params_for_url, $api_url_base);
        
        // Enhanced API request with retry logic
        $response = $this->parent_plugin->make_api_request_with_retry($api_url, $api_essentials['token'], 'GET', 3, null, 20, $request_deadline);

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
            $error_info = $this->parent_plugin->handle_api_error_response($response, $raw_response_body, $http_code, $sync_type);
            
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
                'details' => $error_info['error_data'] ?? null,
                'is_server_error' => $error_info['is_server_error'] ?? false,
                'retry_recommended' => $error_info['retry_recommended'] ?? false
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

        // Full Sync must preserve Ecwid's positional API order. Fast Skip may
        // stop after the safe mutation limit, so reordering a scan window would
        // make next_offset skip or repeat source items. The category importer
        // already tracks and repairs children whose parents arrive later.

        $total_items_reported_by_api = $body['total'] ?? count($items_from_api);
        $count_in_current_api_response = $body['count'] ?? count($items_from_api);

        $imported_count = 0;
        $updated_count = 0;
        $skipped_count = 0;
        $fast_skipped_count = 0;
        $failed_count = 0;
        $batch_detailed_logs = [];
        $batch_item_results = [];

        // --- BULK FAST SKIP LOOKUPS ---
        // Load target IDs, stored source hashes, and category parents once for
        // the entire comparison window. Unchanged items never enter the normal
        // WooCommerce import path.
        $existing_ecwid_ids_map = [];
        $existing_product_hashes_map = [];
        $existing_category_map = [];

        if ($sync_type === 'products' && !empty($items_from_api)) {
            $ecwid_ids_to_check = array_values(array_filter(array_map('strval', array_column($items_from_api, 'id'))));
            if (!empty($ecwid_ids_to_check)) {
                global $wpdb;
                $placeholders = implode(', ', array_fill(0, count($ecwid_ids_to_check), '%s'));
                // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
                $query = $wpdb->prepare(
                    "SELECT id_meta.meta_value AS ecwid_id, id_meta.post_id,
                            COALESCE(hash_meta.meta_value, '') AS source_hash
                     FROM {$wpdb->postmeta} id_meta
                     INNER JOIN {$wpdb->posts} p ON id_meta.post_id = p.ID
                     LEFT JOIN {$wpdb->postmeta} hash_meta
                       ON hash_meta.post_id = id_meta.post_id
                      AND hash_meta.meta_key = '_ecwid_source_hash'
                     WHERE id_meta.meta_key = '_ecwid_product_id'
                       AND id_meta.meta_value IN ($placeholders)
                       AND p.post_type = 'product'",
                    ...$ecwid_ids_to_check
                );
                // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
                // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Prepared bulk lookup is required for Fast Skip.
                $results = $wpdb->get_results($query);
                foreach ($results as $row) {
                    $ecwid_id = (string) $row->ecwid_id;
                    $existing_ecwid_ids_map[$ecwid_id] = (int) $row->post_id;
                    $existing_product_hashes_map[$ecwid_id] = (string) $row->source_hash;
                }
            }
        } elseif ($sync_type === 'categories' && !empty($items_from_api)) {
            $category_ids_to_check = [];
            foreach ($items_from_api as $category_item) {
                if (isset($category_item['id'])) {
                    $category_ids_to_check[] = (string) $category_item['id'];
                }
                if (!empty($category_item['parentId'])) {
                    $category_ids_to_check[] = (string) $category_item['parentId'];
                }
            }
            $category_ids_to_check = array_values(array_unique(array_filter($category_ids_to_check)));
            if (!empty($category_ids_to_check)) {
                global $wpdb;
                $placeholders = implode(', ', array_fill(0, count($category_ids_to_check), '%s'));
                // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
                $query = $wpdb->prepare(
                    "SELECT id_meta.meta_value AS ecwid_id, id_meta.term_id,
                            COALESCE(hash_meta.meta_value, '') AS source_hash,
                            term_taxonomy.parent
                     FROM {$wpdb->termmeta} id_meta
                     INNER JOIN {$wpdb->term_taxonomy} term_taxonomy
                       ON term_taxonomy.term_id = id_meta.term_id
                      AND term_taxonomy.taxonomy = 'product_cat'
                     LEFT JOIN {$wpdb->termmeta} hash_meta
                       ON hash_meta.term_id = id_meta.term_id
                      AND hash_meta.meta_key = '_ecwid_source_hash'
                     WHERE id_meta.meta_key = '_ecwid_category_id'
                       AND id_meta.meta_value IN ($placeholders)",
                    ...$category_ids_to_check
                );
                // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
                // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Prepared bulk lookup is required for Fast Skip.
                $results = $wpdb->get_results($query);
                foreach ($results as $row) {
                    $existing_category_map[(string) $row->ecwid_id] = [
                        'term_id' => (int) $row->term_id,
                        'source_hash' => (string) $row->source_hash,
                        'parent' => (int) $row->parent,
                    ];
                }
            }
        }

        $items_actually_processed = 0;
        $mutation_attempts = 0;
        $time_limit_hit = false;
        $mutation_limit_hit = false;
        if (!empty($items_from_api)) {
            // Time-based circuit breaker: stop processing before Cloudflare's 100s timeout.
            // This ensures we return a valid response even if a batch has image-heavy products.
            // The client will continue from the last processed item via has_more/next_offset.
            // Suspend object cache additions during batch import to reduce memory/CPU waste
            // (cached queries are rarely reused during import)
            wp_suspend_cache_addition(true);
            foreach ($items_from_api as $item_data) {
                // Check time circuit breaker BEFORE processing next item
                $elapsed = microtime(true) - $request_started_at;
                if ($this->parent_plugin->is_sync_request_deadline_near(5)) {
                    $time_limit_hit = true;
                    $batch_detailed_logs[] = "⏱ Time limit reached ({$max_batch_seconds}s) after processing $items_actually_processed of " . count($items_from_api) . " items. Returning early to avoid Cloudflare 524 timeout. Remaining items will be processed in next batch.";
                    break;
                }

                if (!is_array($item_data) || !isset($item_data['id'])) {
                    $batch_detailed_logs[] = "--- [CRITICAL ERROR] Encountered invalid item in API response for $sync_type. Skipping. Item data: " . print_r($item_data, true) . " ---"; // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r -- Debug logging for invalid API response data
                    $failed_count++;
                    $items_actually_processed++;
                    continue;
                }

                $result_array = null;
                if ($sync_type === 'products') {
                    $item_identifier_for_log = "Product (Ecwid ID: " . ($item_data['id'] ?? 'N/A') . ")";
                } elseif ($sync_type === 'categories') {
                    $item_identifier_for_log = "Category (Ecwid ID: " . ($item_data['id'] ?? 'N/A') . ")";
                } else {
                    $item_identifier_for_log = "Item (Ecwid ID: " . ($item_data['id'] ?? 'N/A') . ")";
                }

                // Fast Skip compares the canonical source hash against the
                // bulk-loaded target hash before SKU validation, WC object
                // loading, image handling, or variation work.
                $is_fast_skip = false;
                $item_ecwid_id = (string) $item_data['id'];

                if (
                    $sync_type === 'products'
                    && isset($existing_ecwid_ids_map[$item_ecwid_id], $existing_product_hashes_map[$item_ecwid_id])
                ) {
                    $source_hash = $this->parent_plugin->product_sync_handler->get_source_hash_for_sync($item_data);
                    $stored_hash = $existing_product_hashes_map[$item_ecwid_id];
                    if ($source_hash !== '' && $stored_hash !== '' && hash_equals($stored_hash, $source_hash)) {
                        $is_fast_skip = true;
                        $result_array = [
                            'status' => 'skipped',
                            'fast_skip' => true,
                            'logs' => ['FAST SKIP: Bulk source fingerprint matched; normal product import was bypassed.'],
                            'item_name' => sanitize_text_field($item_data['name'] ?? '[No Name]'),
                            'ecwid_id' => $item_data['id'],
                            'sku' => $item_data['sku'] ?? 'N/A',
                            'wc_product_id' => $existing_ecwid_ids_map[$item_ecwid_id],
                        ];
                    }
                } elseif ($sync_type === 'categories' && isset($existing_category_map[$item_ecwid_id])) {
                    $source_hash = $this->parent_plugin->category_sync_handler->get_source_hash_for_sync($item_data);
                    $stored_hash = $existing_category_map[$item_ecwid_id]['source_hash'];
                    $parent_ecwid_id = !empty($item_data['parentId']) ? (string) $item_data['parentId'] : '';
                    $expected_parent_term_id = (
                        $parent_ecwid_id !== ''
                        && isset($existing_category_map[$parent_ecwid_id])
                    ) ? (int) $existing_category_map[$parent_ecwid_id]['term_id'] : 0;
                    $actual_parent_term_id = (int) $existing_category_map[$item_ecwid_id]['parent'];

                    if (
                        $source_hash !== ''
                        && $stored_hash !== ''
                        && hash_equals($stored_hash, $source_hash)
                        && $actual_parent_term_id === $expected_parent_term_id
                    ) {
                        $is_fast_skip = true;
                        $result_array = [
                            'status' => 'skipped',
                            'fast_skip' => true,
                            'logs' => ['FAST SKIP: Bulk source fingerprint and category hierarchy matched; normal category import was bypassed.'],
                            'item_name' => sanitize_text_field($item_data['name'] ?? '[No Name]'),
                            'ecwid_id' => $item_data['id'],
                            'wc_term_id' => $existing_category_map[$item_ecwid_id]['term_id'],
                        ];
                    }
                }

                if (!$is_fast_skip && $mutation_attempts >= $mutation_limit_per_request) {
                    $mutation_limit_hit = true;
                    $batch_detailed_logs[] = "[FAST SKIP] Safe mutation limit reached after scanning $items_actually_processed items. The next request will resume at this exact source offset.";
                    break;
                }
                if (!$is_fast_skip) {
                    $mutation_attempts++;
                }

                try {
                    if (!$is_fast_skip) {
                    switch ($sync_type) {
                        case 'products':
                            // Enhanced debugging for gallery image issue
                            if (defined('WP_DEBUG') && WP_DEBUG) {
                                $gallery_count = isset($item_data['galleryImages']) && is_array($item_data['galleryImages']) ? count($item_data['galleryImages']) : 0;
                                // amazonq-ignore-next-line
                                error_log("Ecwid Full Sync DEBUG: Product ID " . ($item_data['id'] ?? 'N/A') . " has $gallery_count gallery images in API data"); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging wrapped in WP_DEBUG check
                                if ($gallery_count > 0) {
                                    error_log("Ecwid Full Sync DEBUG: Gallery images data: " . json_encode($item_data['galleryImages'])); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging wrapped in WP_DEBUG check
                                }
                            }
                            $result_array = $this->parent_plugin->product_sync_handler->import_product($item_data, $existing_ecwid_ids_map);
                            break;
                        case 'categories':
                            $result_array = $this->parent_plugin->category_sync_handler->import_category($item_data);
                            break;
                    }
                    }

                    if ($result_array && isset($result_array['status'])) {
                        $batch_item_results[] = $result_array;
                        if (!empty($result_array['fast_skip'])) {
                            $fast_skipped_count++;
                        }
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
                        $batch_item_results[] = [
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
                $items_actually_processed++;
            }
            wp_suspend_cache_addition(false);
        } elseif ($offset === 0 && $fast_skip_scan_limit > 0) {
             $batch_detailed_logs[] = "No items received from Ecwid API for $sync_type with offset $offset and scan limit $fast_skip_scan_limit. This might be normal if there are no items of this type or all have been processed.";
             $batch_detailed_logs[] = "API Response Debug: HTTP Code: $http_code, Total reported: $total_items_reported_by_api, Count in response: $count_in_current_api_response";
             if (defined('WP_DEBUG') && WP_DEBUG) {
                 error_log("Ecwid Sync: Empty items for $sync_type. API URL: $api_url, HTTP Code: $http_code, Raw Response: " . substr($raw_response_body, 0, 500)); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging wrapped in WP_DEBUG check
             }
        }

        // If the deadline or safe mutation limit was hit, advance only by
        // inspected items so the next request resumes at the exact API position.
        if ($time_limit_hit || $mutation_limit_hit) {
            $new_offset = $offset + $items_actually_processed;
            $has_more = true; // Uninspected items remain in the current scan window
        } else {
            $new_offset = $offset + $count_in_current_api_response;
            $has_more = false;
            if ($count_in_current_api_response > 0) {
                if (isset($body['total']) && isset($body['offset']) && isset($body['count'])) {
                     $has_more = ($body['total'] > ($body['offset'] + $body['count']));
                } elseif ($count_in_current_api_response === $fast_skip_scan_limit) {
                    $has_more = true;
                }
            }
            if (isset($body['total']) && $new_offset >= $body['total']) {
                $has_more = false;
            }
        }

        if ($sync_type === 'products' && !$has_more) {
            delete_transient('ecwid2woo_full_sync_preview');
        }

        // Clean output buffer before sending JSON response
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        wp_send_json_success([
            // translators: %1$s is the sync type, %2$d is items processed, %3$d is imported count, %4$d is updated count, %5$d is skipped count, %6$d is failed count, %7$d is total items
            'message' => sprintf(__('%1$s: Processed %2$d items in this request (Imported: %3$d, Updated: %4$d, Skipped: %5$d, Failed: %6$d). Total items for this type (Ecwid reported): %7$d.', 'metrotechs-e2w-sync'), ucfirst($sync_type), $items_actually_processed, $imported_count, $updated_count, $skipped_count, $failed_count, $total_items_reported_by_api),
            'next_offset' => $new_offset,
            'total_items' => $total_items_reported_by_api,
            'has_more' => $has_more,
            'processed_type' => $sync_type,
            'imported_count' => $imported_count,
            'updated_count' => $updated_count,
            'skipped_count' => $skipped_count,
            'fast_skipped_count' => $fast_skipped_count,
            'failed_count' => $failed_count,
            'batch_logs' => $batch_detailed_logs,
            'batch_item_results' => $batch_item_results,
            'batch_size_used' => $mutation_limit_per_request,
            'scan_size_used' => $fast_skip_scan_limit
        ]);
        
        } catch (Error $e) {
            // Clean output buffer before sending error response
            if (ob_get_level()) {
                ob_end_clean();
            }
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
            // Clean output buffer before sending error response
            if (ob_get_level()) {
                ob_end_clean();
            }
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
                $response['data']['message'] = __('Sync failed due to insufficient server memory. Try reducing the batch size or increase server memory limit.', 'metrotechs-e2w-sync');
                $response['data']['suggested_action'] = 'increase_memory_or_reduce_batch';
            } elseif ($is_time_error) {
                $response['data']['message'] = __('Sync failed due to server time limit. Try reducing the batch size or increase server execution time.', 'metrotechs-e2w-sync');
                $response['data']['suggested_action'] = 'reduce_batch_size';
            } else {
                $response['data']['message'] = __('A critical server error occurred during sync. Check server logs for details.', 'metrotechs-e2w-sync');
                $response['data']['suggested_action'] = 'check_server_logs';
            }
            
            // Log the error for debugging
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Ecwid2Woo Fatal Error: ' . $error_message . ' in ' . $error['file'] . ' on line ' . $error['line']); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            }
            
            // Send JSON response
            // amazonq-ignore-next-line
            wp_send_json($response);
        }
    }

    /**
     * AJAX handler to fetch full sync counts
     */
    public function ajax_fetch_full_sync_counts() {
        // Start output buffering to prevent PHP notices/warnings from corrupting JSON response
        ob_start();
        
        // Check WooCommerce availability first
        if (!class_exists('WooCommerce')) {
            ob_end_clean();
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
                ob_end_clean();
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
                
                if ($current_bytes !== -1 && $current_bytes < $minimum_bytes) {
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

            $api_essentials = $this->parent_plugin->_get_api_essentials();
            if (is_wp_error($api_essentials)) {
                wp_send_json_error(['message' => $api_essentials->get_error_message()], 500);
                return;
            }

            $preview_cache_key = 'ecwid2woo_full_sync_preview';
            $cached_preview = get_transient($preview_cache_key);
            if (is_array($cached_preview)) {
                $cached_preview['cached'] = true;
                if (ob_get_level()) {
                    ob_end_clean();
                }
                wp_send_json_success($cached_preview);
                return;
            }

            $category_count = 0;
            $product_count = 0;
            $errors = [];

            // Fetch category count and preview
            $categories_url = add_query_arg([
                'limit' => 5,
                'responseFields' => 'items(id,name),total',
            ], $api_essentials['base_url'] . '/categories');
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
                    // translators: %d is the HTTP status code
                    $errors[] = sprintf(__('Failed to fetch category count (HTTP %d)', 'metrotechs-e2w-sync'), $categories_http_code);
                }
            } else {
                // translators: %s is the error message
                $errors[] = sprintf(__('Category count request failed: %s', 'metrotechs-e2w-sync'), $categories_response->get_error_message());
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
                    // translators: %d is the HTTP status code
                    $errors[] = sprintf(__('Failed to fetch product count (HTTP %d)', 'metrotechs-e2w-sync'), $products_http_code);
                }
            } else {
                // translators: %s is the error message
                $errors[] = sprintf(__('Product count request failed: %s', 'metrotechs-e2w-sync'), $products_response->get_error_message());
            }

            // Send response
            $response_data = [
                'categories_count' => $category_count,
                'products_count' => $product_count,
                'total_items' => $category_count + $product_count,
                'categories_preview' => $categories_preview,
                'products_preview' => $products_preview,
                'success' => empty($errors),
                'cached' => false,
                'debug_info' => [
                    'api_configured' => !is_wp_error($api_essentials),
                    'store_id' => !empty($api_essentials['store_id']) ? substr($api_essentials['store_id'], 0, 4) . '...' : 'Not set',
                    'has_errors' => !empty($errors),
                    'categories_api_status' => isset($categories_http_code) ? $categories_http_code : 'No response',
                    'products_api_status' => isset($products_http_code) ? $products_http_code : 'No response',
                    'categories_url' => isset($categories_url) ? $categories_url : 'Not set',
                    'products_url' => isset($products_url) ? $products_url : 'Not set'
                ]
            ];

            if (!empty($errors)) {
                $response_data['errors'] = $errors;
                $response_data['message'] = implode(' ', $errors);
            } else {
                $preview_cache_ttl = absint(apply_filters('ecwid_wc_sync_preview_cache_ttl', 5 * MINUTE_IN_SECONDS));
                if ($preview_cache_ttl > 0) {
                    set_transient($preview_cache_key, $response_data, min($preview_cache_ttl, HOUR_IN_SECONDS));
                }
            }

            // Clean output buffer before sending JSON response
            if (ob_get_level()) {
                ob_end_clean();
            }
            
            wp_send_json_success($response_data);

        } catch (Exception $e) {
            // Clean output buffer before sending error response
            if (ob_get_level()) {
                ob_end_clean();
            }
            
            if ($debug_mode) {
                error_log('Ecwid2Woo: Exception in ajax_fetch_full_sync_counts: ' . $e->getMessage()); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            }
            
            wp_send_json_error([
                'message' => __('An error occurred while fetching sync counts: ', 'metrotechs-e2w-sync') . $e->getMessage(),
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
