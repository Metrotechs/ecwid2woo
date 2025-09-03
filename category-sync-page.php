<?php
/**
 * Category Sync Page - Metrotechs E2W Sync Plugin
 * 
 * This file contains all the functionality for the Category Sync page,
 * including the page rendering, AJAX handlers, and category import logic.
 * 
 * @package Metrotechs E2W Sync
 * @since 1.1.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Category Sync Page Handler Class
 * 
 * Handles all category sync page functionality including rendering,
 * AJAX handlers, and category import operations.
 */
class Ecwid2Woo_Category_Sync {
    
    private $parent_plugin;
    private $options;
    
    public function __construct($parent_plugin) {
        $this->parent_plugin = $parent_plugin;
        $this->options = get_option('ecwid_wc_sync_options');
        
        // Register AJAX handlers
        add_action('wp_ajax_fix_category_hierarchy', [$this, 'fix_category_hierarchy']);
        add_action('wp_ajax_ecwid_wc_fetch_categories_for_display', [$this, 'ajax_fetch_categories_for_display']);
        add_action('wp_ajax_ecwid_wc_import_selected_categories', [$this, 'ajax_import_selected_categories']);
        add_action('wp_ajax_ecwid_wc_sync_all_categories', [$this, 'ajax_sync_all_categories']);
    }
    
    /**
     * Render the Category Sync page
     */
    public function render_category_sync_page() {
        ?>
        <div class="ecwid-page-header">
            <h1><?php esc_html_e('Partial Category Sync', 'metrotechs-e2w-sync'); ?></h1>
            <p><?php esc_html_e('Load categories from your Ecwid store and select which ones to import or update in WooCommerce.', 'metrotechs-e2w-sync'); ?></p>
        </div>

        <!-- Navigation Bar -->
        <div class="ecwid-page-nav">
            <a href="<?php echo esc_url(admin_url('admin.php?page=ecwid-sync-settings')); ?>" class="nav-link">
                <span class="nav-icon">⚙️</span> <?php esc_html_e('Settings', 'metrotechs-e2w-sync'); ?>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=ecwid-sync-full')); ?>" class="nav-link">
                <span class="nav-icon">🔄</span> <?php esc_html_e('Full Sync', 'metrotechs-e2w-sync'); ?>
            </a>
            <span class="nav-link current">
                <span class="nav-icon">📁</span> <?php esc_html_e('Category Sync', 'metrotechs-e2w-sync'); ?>
            </span>
            <a href="<?php echo esc_url(admin_url('admin.php?page=ecwid-sync-partial')); ?>" class="nav-link">
                <span class="nav-icon">🎯</span> <?php esc_html_e('Product Sync', 'metrotechs-e2w-sync'); ?>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=ecwid-sync-customers')); ?>" class="nav-link">
                <span class="nav-icon">👥</span> <?php esc_html_e('Customer Sync', 'metrotechs-e2w-sync'); ?>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=ecwid-sync-orders')); ?>" class="nav-link">
                <span class="nav-icon">📦</span> <?php esc_html_e('Order Sync', 'metrotechs-e2w-sync'); ?>
            </a>
        </div>

        <div class="ecwid-sync-container">
            <div id="selective-sync-initial-info" class="selective-sync-initial-info">
                <!-- This will be populated by JavaScript -->
            </div>

            <button id="load-ecwid-categories-button" class="button button-primary"><?php esc_html_e('Reload Categories', 'metrotechs-e2w-sync'); ?></button>
            <div id="selective-category-list-container" class="selective-category-list-container">
                <?php esc_html_e('Category list will appear here...', 'metrotechs-e2w-sync'); ?>
            </div>
            <button id="import-selected-categories-button" class="button button-primary import-selected-button"><?php esc_html_e('Import Selected Categories', 'metrotechs-e2w-sync'); ?></button>
            
            <!-- Bulk Actions -->
            <div class="category-bulk-actions" style="margin: 25px 0 15px 0; padding-top: 15px; border-top: 1px solid #ddd;">
                <h3><?php esc_html_e('Bulk Actions', 'metrotechs-e2w-sync'); ?></h3>
                <button id="sync-all-categories-button" class="button button-primary"><?php esc_html_e('Import All Categories', 'metrotechs-e2w-sync'); ?></button>
                <button id="stop-sync-categories-button" class="button button-secondary" style="margin-left: 10px; display: none;"><?php esc_html_e('Stop Sync', 'metrotechs-e2w-sync'); ?></button>
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
     * Sanitize and prepare category name for WordPress
     * Handles special characters, encoding issues, and length limits
     */
    private function prepare_category_name($name) {
        if (empty($name)) {
            return '';
        }
        
        // Remove HTML entities that might have been double-encoded
        $name = html_entity_decode($name, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        // Normalize Unicode characters to prevent encoding issues
        if (class_exists('Normalizer')) {
            $name = Normalizer::normalize($name, Normalizer::FORM_C);
        }
        
        // Trim whitespace
        $name = trim($name);
        
        // WordPress has a practical limit around 200 characters for term names
        if (strlen($name) > 200) {
            // Try to truncate at word boundary
            $truncated = substr($name, 0, 197);
            $last_space = strrpos($truncated, ' ');
            if ($last_space !== false && $last_space > 150) {
                $name = substr($truncated, 0, $last_space) . '...';
            } else {
                $name = $truncated . '...';
            }
        }
        
        return $name;
    }

    /**
     * Generate a robust, multilingual-safe slug for terms.
     * - Transliterates from any script to Latin when possible.
     * - Ensures ASCII-only, lowercase, hyphen-separated slug.
     * - Appends Ecwid ID to guarantee uniqueness.
     * - Truncates to <= 190 chars to avoid utf8mb4 index issues.
     */
    private function generate_term_slug($name, $ecwid_id = null, $max_len = 190) {
        if (!is_string($name) || $name === '') {
            $base = 'category';
        } else {
            $base = html_entity_decode($name, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if (class_exists('Normalizer')) {
                $base = Normalizer::normalize($base, Normalizer::FORM_C);
            }

            // Prefer intl transliterator if available for broad language coverage
            if (class_exists('Transliterator')) {
                $trans = \Transliterator::create('Any-Latin; Latin-ASCII');
                if ($trans) {
                    $base = $trans->transliterate($base);
                }
            } else if (function_exists('iconv')) {
                $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $base);
                if ($converted !== false) {
                    $base = $converted;
                }
            }

            // WP helper to remove accents if available (covers many languages)
            if (function_exists('remove_accents')) {
                $base = remove_accents($base);
            }

            // Lowercase and replace non-alphanumeric with hyphens
            $base = strtolower($base);
            $base = preg_replace('/[^a-z0-9]+/i', '-', $base);
            $base = trim($base, '-');

            if ($base === '' || $base === null) {
                $base = 'category';
            }
        }

        $suffix = '';
        if (!empty($ecwid_id)) {
            $suffix = '-' . preg_replace('/[^0-9]/', '', (string)$ecwid_id);
        }

        // Ensure total length <= $max_len
        $allowed_base_len = $max_len - strlen($suffix);
        if ($allowed_base_len < 1) {
            // Edge case: suffix too long; fallback to a fixed slug with tail of ID
            $suffix = '-' . substr((string)$ecwid_id, -10);
            $allowed_base_len = max(1, $max_len - strlen($suffix));
        }
        if (strlen($base) > $allowed_base_len) {
            $base = substr($base, 0, $allowed_base_len);
            $base = rtrim($base, '-');
        }

        $slug = $base . $suffix;
        if ($slug === '') {
            $slug = 'category' . $suffix;
        }
        return $slug;
    }

    /**
     * Import a single category with minimal logging for missing parent resolution
     * 
     * @param array $category_data The category data from Ecwid API
     * @return array|null Returns array with term_id and name if successful, null if failed
     */
    private function import_single_category($category_data) {
        if (!class_exists('WooCommerce') || !function_exists('wp_insert_term')) {
            return null;
        }
        
        $ecwid_cat_id = $category_data['id'] ?? null;
        $ecwid_cat_name = isset($category_data['name']) ? sanitize_text_field($category_data['name']) : null;
        
        if (!$ecwid_cat_id || !$ecwid_cat_name) {
            return null;
        }
        
        // Prepare category name
        $ecwid_cat_name = $this->prepare_category_name($ecwid_cat_name);
        
        // Check if already exists
        $existing_term_id = $this->parent_plugin->get_term_id_by_ecwid_id($ecwid_cat_id, 'product_cat', true);
        if ($existing_term_id) {
            return [
                'term_id' => $existing_term_id,
                'name' => $ecwid_cat_name,
                'is_existing' => true
            ];
        }
        
        // Create new category
        $args = [
            'name' => wp_slash($ecwid_cat_name),
            'slug' => $this->generate_term_slug($ecwid_cat_name, $ecwid_cat_id),
        ];
        
        if (isset($category_data['description']) && !empty($category_data['description'])) {
            $args['description'] = wp_slash(sanitize_textarea_field($category_data['description']));
        }
        
        // Handle parent if exists - but don't create recursive loops
        $parent_ecwid_id = $category_data['parentId'] ?? null;
        if ($parent_ecwid_id && $parent_ecwid_id !== $ecwid_cat_id) {
            $parent_wc_term_id = $this->parent_plugin->get_term_id_by_ecwid_id($parent_ecwid_id, 'product_cat', true);
            if ($parent_wc_term_id) {
                $args['parent'] = $parent_wc_term_id;
            }
            // Don't recursively fetch parents to avoid infinite loops
        }
        
        $term_result = wp_insert_term($ecwid_cat_name, 'product_cat', $args);
        
        if (is_wp_error($term_result)) {
            $this->parent_plugin->log_message("Failed to create missing parent category {$ecwid_cat_id}: " . $term_result->get_error_message(), 'error');
            return null;
        }
        
        $term_id = $term_result['term_id'];
        
        // Store Ecwid ID mapping
        update_term_meta($term_id, '_ecwid_category_id', $ecwid_cat_id);
        
        clean_term_cache($term_id, 'product_cat');
        
        return [
            'term_id' => $term_id,
            'name' => $ecwid_cat_name,
            'is_new' => true
        ];
    }

    /**
     * Import a category from Ecwid data
     * 
     * @param array $item Category data from Ecwid API
     * @return array Import result with status, logs, and metadata
     */
    public function import_category($item) {
        // Check if WooCommerce is available before trying to create categories
        if (!class_exists('WooCommerce') || !function_exists('wp_insert_term')) {
            return [
                'status' => 'failed', 
                'logs' => ['[CRITICAL] WooCommerce is not available. Cannot create product categories.'], 
                'item_name' => $item['name'] ?? '[No Name]', 
                'ecwid_id' => $item['id'] ?? 'N/A'
            ];
        }
        
        $category_logs = [];
        $ecwid_cat_id = $item['id'] ?? null;
        $ecwid_cat_name = isset($item['name']) ? sanitize_text_field($item['name']) : null;

        // Use the enhanced name preparation
        if ($ecwid_cat_name) {
            $original_name = $ecwid_cat_name;
            $ecwid_cat_name = $this->prepare_category_name($ecwid_cat_name);
            if ($original_name !== $ecwid_cat_name) {
                $category_logs[] = "Category name adjusted: '$original_name' → '$ecwid_cat_name'";
            }
        }

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

            // Proactively provide a robust slug to avoid DB errors on non-Latin/long names
            $args['slug'] = $this->generate_term_slug($ecwid_cat_name, $ecwid_cat_id);

            $parent_wc_term_id = 0; // Default to 0 (no parent)
            if (isset($item['parentId']) && intval($item['parentId']) > 0) {
                $parent_ecwid_id = intval($item['parentId']);
                $parent_wc_term_id_found = $this->parent_plugin->get_term_id_by_ecwid_id($parent_ecwid_id, 'product_cat', true);

                if ($parent_wc_term_id_found) {
                    $args['parent'] = $parent_wc_term_id_found;
                    $parent_wc_term_id = $parent_wc_term_id_found;
                    $category_logs[] = "Parent category (Ecwid ID: $parent_ecwid_id) mapped to WC Term ID: {$args['parent']}.";
                } else {
                    $category_logs[] = "[WARNING] Parent category (Ecwid ID: $parent_ecwid_id) not found. Importing as top-level category for now.";
                    $parent_wc_term_id = 0;
                    unset($args['parent']);
                }
            }

            $existing_wc_term_id_by_ecwid_meta = $this->parent_plugin->get_term_id_by_ecwid_id($ecwid_cat_id, 'product_cat', true);

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
                
                // Handle category image import for existing category
                $this->handle_category_image_import($item, $existing_wc_term_id_by_ecwid_meta, $category_logs);
                
                return ['status' => 'updated', 'logs' => $category_logs, 'item_name' => $item_name_for_return, 'ecwid_id' => $ecwid_id_for_return];
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
                        
                        // Handle category image import for found category
                        $this->handle_category_image_import($item, $wc_term_id_found_by_name, $category_logs);
                        
                    } else {
                        $category_logs[] = "[ERROR] FAILED to link WC term (ID: $wc_term_id_found_by_name) to Ecwid ID $ecwid_cat_id (update_term_meta failed).";
                        return ['status' => 'failed', 'logs' => $category_logs, 'item_name' => $item_name_for_return, 'ecwid_id' => $ecwid_id_for_return];
                    }
                    return ['status' => 'updated', 'logs' => $category_logs, 'item_name' => $item_name_for_return, 'ecwid_id' => $ecwid_id_for_return];
                }
                 $category_logs[] = "Skipped. WC Term ID $wc_term_id_found_by_name (Name: '$ecwid_cat_name') appears already correctly linked to Ecwid ID $ecwid_cat_id (found by name).";
                 return ['status' => 'skipped', 'logs' => $category_logs, 'item_name' => $item_name_for_return, 'ecwid_id' => $ecwid_id_for_return];
            }

            // Enhanced category name validation and preparation
            $sanitized_name = wp_slash($ecwid_cat_name);
            $category_logs[] = "Preparing to create new category. Original name: '$ecwid_cat_name', Sanitized: '$sanitized_name'";
            
            // Check name length (WordPress typically handles up to 200 characters but let's be safe)
            if (strlen($sanitized_name) > 200) {
                $truncated_name = substr($sanitized_name, 0, 197) . '...';
                $category_logs[] = "[WARNING] Category name too long (" . strlen($sanitized_name) . " chars). Truncating to: '$truncated_name'";
                $sanitized_name = $truncated_name;
            }
            
            // Log the arguments being passed to wp_insert_term
            $category_logs[] = "Insert term arguments: " . wp_json_encode(['name' => $sanitized_name, 'args' => $args]);
            
            $new_term_result = wp_insert_term($sanitized_name, 'product_cat', $args);

            if (is_wp_error($new_term_result)) {
                $error_code = $new_term_result->get_error_code();
                $error_message = $new_term_result->get_error_message();
                $error_data = $new_term_result->get_error_data();
                
                $category_logs[] = '[ERROR] Failed to insert new WC category: ' . $error_message;
                $category_logs[] = '[ERROR] Error code: ' . $error_code;
                
                if ($error_data) {
                    $category_logs[] = '[ERROR] Error data: ' . wp_json_encode($error_data);
                }
                
                // Try to provide specific guidance based on error type
                switch ($error_code) {
                    case 'term_exists':
                        $category_logs[] = '[ERROR] Term already exists. This might be a slug collision.';
                        // Try with a modified slug
                        $modified_args = $args;
                        $modified_args['slug'] = sanitize_title($sanitized_name . '-' . $ecwid_cat_id);
                        $category_logs[] = '[RETRY] Attempting with custom slug: ' . $modified_args['slug'];
                        
                        $retry_result = wp_insert_term($sanitized_name, 'product_cat', $modified_args);
                        if (!is_wp_error($retry_result)) {
                            $category_logs[] = '[SUCCESS] Retry with custom slug succeeded.';
                            $new_term_result = $retry_result;
                            // Success, so we can continue with the normal processing
                        } else {
                            $category_logs[] = '[ERROR] Retry also failed: ' . $retry_result->get_error_message();
                        }
                        break;
                        
                    case 'invalid_taxonomy':
                        $category_logs[] = '[ERROR] Invalid taxonomy. This is a critical error.';
                        break;
                        
                    case 'empty_term_name':
                        $category_logs[] = '[ERROR] Empty term name after sanitization.';
                        break;
                        
                    default:
                        $category_logs[] = '[ERROR] Unknown error type. Check database constraints and character encoding.';
                        
                        // Additional debugging for unknown errors
                        global $wpdb;
                        if (isset($wpdb)) {
                            $category_logs[] = '[DEBUG] WordPress DB charset: ' . ($wpdb->charset ?? 'unknown');
                            $category_logs[] = '[DEBUG] WordPress DB collate: ' . ($wpdb->collate ?? 'unknown');
                        }
                        $category_logs[] = '[DEBUG] Name character encoding: ' . mb_detect_encoding($sanitized_name);
                        $category_logs[] = '[DEBUG] Name byte length: ' . strlen($sanitized_name);
                        $category_logs[] = '[DEBUG] Name character length: ' . mb_strlen($sanitized_name);
                        
                        // Try with a completely safe ASCII slug as last resort
                        $ascii_safe_args = $args;
                        $ascii_safe_name = 'Category-' . $ecwid_cat_id;
                        $ascii_safe_args['slug'] = 'category-' . $ecwid_cat_id;
                        
                        // Check if ASCII fallback name already exists
                        $existing_ascii_term = term_exists($ascii_safe_name, 'product_cat', $args['parent'] ?? 0);
                        if ($existing_ascii_term) {
                            $category_logs[] = '[FOUND] ASCII-safe fallback already exists (Term ID: ' . $existing_ascii_term['term_id'] . '). Using existing category.';
                            $new_term_result = ['term_id' => $existing_ascii_term['term_id']];
                            // Success, so we can continue with the normal processing
                        } else {
                            $category_logs[] = '[LAST_RESORT] Attempting with ASCII-safe name: ' . $ascii_safe_name;
                            
                            $ascii_retry_result = wp_insert_term($ascii_safe_name, 'product_cat', $ascii_safe_args);
                            if (!is_wp_error($ascii_retry_result)) {
                                $category_logs[] = '[SUCCESS] ASCII-safe retry succeeded. Original name will be stored in description.';
                                
                                // Store original name in description if not already set
                                if (!isset($args['description']) || empty($args['description'])) {
                                    $desc_update = wp_update_term($ascii_retry_result['term_id'], 'product_cat', [
                                        'description' => sprintf('Original name: %s', $ecwid_cat_name)
                                    ]);
                                    if (!is_wp_error($desc_update)) {
                                        $category_logs[] = '[INFO] Original name stored in category description.';
                                    }
                                }
                                
                                $new_term_result = $ascii_retry_result;
                                // Success, so we can continue with the normal processing
                            } else {
                                $category_logs[] = '[ERROR] Even ASCII-safe retry failed: ' . $ascii_retry_result->get_error_message();
                                return ['status' => 'failed', 'logs' => $category_logs, 'item_name' => $item_name_for_return, 'ecwid_id' => $ecwid_id_for_return];
                            }
                        }
                }
            }

            if (isset($new_term_result['term_id'])) {
                $new_term_id = $new_term_result['term_id'];
                $meta_update_result = update_term_meta($new_term_id, '_ecwid_category_id', $ecwid_cat_id);
                if ($meta_update_result) {
                    clean_term_cache($new_term_id, 'product_cat');
                    $category_logs[] = "Imported successfully (New WC Term ID: $new_term_id). Meta update successful. Cache cleaned.";
                    
                    // Handle category image import
                    $this->handle_category_image_import($item, $new_term_id, $category_logs);
                    
                } else {
                     $category_logs[] = "[ERROR] Imported successfully (New WC Term ID: $new_term_id). BUT FAILED to set _ecwid_category_id meta (update_term_meta failed).";
                     return ['status' => 'failed', 'logs' => $category_logs, 'item_name' => $item_name_for_return, 'ecwid_id' => $ecwid_id_for_return];
                }
                return ['status' => 'imported', 'logs' => $category_logs, 'item_name' => $item_name_for_return, 'ecwid_id' => $ecwid_id_for_return];
            }

            $category_logs[] = "[ERROR] wp_insert_term did not return term_id after attempting to create '$ecwid_cat_name'.";
            return ['status' => 'failed', 'logs' => $category_logs, 'item_name' => $item_name_for_return, 'ecwid_id' => $ecwid_id_for_return];

        } catch (Exception $e) {
            $category_logs[] = "[PHP EXCEPTION] During category import for Ecwid ID $ecwid_id_for_return: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine();
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Ecwid Sync: PHP Exception during category import for Ecwid ID $ecwid_id_for_return: " . $e->getMessage() . " Trace: " . $e->getTraceAsString()); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging wrapped in WP_DEBUG check
            }
            return ['status' => 'failed', 'logs' => $category_logs, 'item_name' => $item_name_for_return, 'ecwid_id' => $ecwid_id_for_return];
        }
    }

    /**
     * Attach image to WooCommerce category from URL
     * Uses the WooCommerce category thumbnail system
     */
    private function attach_image_to_category_from_url($image_url, $term_id, $desc = null) {
        if (empty($image_url) || empty($term_id)) {
            return new WP_Error('missing_params', __('Image URL or term ID is missing.', 'metrotechs-e2w-sync'));
        }
        
        // Use the parent plugin's image attachment function to download and create the attachment
        $attachment_id = $this->parent_plugin->attach_image_to_product_from_url($image_url, 0, $desc);
        
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

    /**
     * AJAX handler to fix category hierarchy
     */
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
            $parent_wc_term_id = $this->parent_plugin->get_term_id_by_ecwid_id($parent_ecwid_id, 'product_cat', true);

            if (!$parent_wc_term_id) {
                // translators: %s is the Ecwid category ID
                $logs[] = sprintf(__('Parent Ecwid ID %s still missing, cannot fix its children.', 'metrotechs-e2w-sync'), $parent_ecwid_id);
                continue;
            }

            foreach ($child_ecwid_ids as $child_ecwid_id) {
                $child_wc_term_id = $this->parent_plugin->get_term_id_by_ecwid_id($child_ecwid_id, 'product_cat', true);

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
     * AJAX handler to fetch categories for display in the sync page
     */
    public function ajax_fetch_categories_for_display() {
        check_ajax_referer('ecwid_wc_sync_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized', 'metrotechs-e2w-sync')]);
            return;
        }
        set_time_limit(300); // phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged -- Legitimate use for category fetch operations

        $api_essentials = $this->parent_plugin->_get_api_essentials();
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
                'responseFields' => 'items(id,name,parentId),total'
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
                $error_info = $this->parent_plugin->handle_api_error_response($response, $raw_response_body, $http_code, 'categories');
                
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

    /**
     * AJAX handler to import selected categories
     */
    public function ajax_import_selected_categories() {
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

        $api_essentials = $this->parent_plugin->_get_api_essentials();
        if (is_wp_error($api_essentials)) {
            wp_send_json_error(['message' => $api_essentials->get_error_message()]);
            return;
        }

        // Sync currency before importing categories
        $currency_sync_logs = [];
        $currency_sync_result = $this->parent_plugin->sync_currency_settings($currency_sync_logs);
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
            $query_params = ['responseFields' => 'id,name,parentId,description,hdThumbnailUrl,originalImageUrl'];
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
    public function ajax_sync_all_categories() {
        // Verify nonce for security
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'ecwid_wc_sync_nonce')) {
            wp_send_json_error(['message' => __('Security check failed. Please refresh the page and try again.', 'metrotechs-e2w-sync')]);
        }

        // Check for required API credentials
        $api_essentials = $this->parent_plugin->_get_api_essentials();
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
                    'responseFields' => 'items(id,name,parentId,description,hdThumbnailUrl,originalImageUrl),total'
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
                $debug_data = $this->parent_plugin->format_debug_data(array_keys($data));
                if ($debug_data) {
                    $detailed_logs[] = "[DEBUG] API Response structure: " . $debug_data;
                }
                if (isset($data['total'])) {
                    $detailed_logs[] = "[DEBUG] Total categories reported by API: " . $data['total'];
                }

                if (!isset($data['items']) || !is_array($data['items'])) {
                    $detailed_logs[] = "[ERROR] Invalid response format from Ecwid API";
                    $debug_data = $this->parent_plugin->format_debug_data($data);
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
                'logs' => $detailed_logs,
                'results' => $import_results
            ]);

        } catch (Exception $e) {
            $detailed_logs[] = "[FATAL EXCEPTION] " . $e->getMessage();
            wp_send_json_error([
                'message' => __('A fatal error occurred during category sync. Please check the logs.', 'metrotechs-e2w-sync'),
                'logs' => $detailed_logs
            ]);
        }
    }
}

?>
