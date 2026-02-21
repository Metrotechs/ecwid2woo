<?php
/**
 * Ecwid2Woo Product Sync Uninstall
 *
 * Robust uninstall script with enhanced file cleanup.
 *
 * @package Ecwid2Woo
 */

// Exit if accessed directly.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    // amazonq-ignore-next-line
    exit;
}

// Ensure we have access to WordPress functions
if ( ! function_exists( 'delete_option' ) ) {
    return;
}

// Delete plugin options
delete_option( 'ecwid_wc_sync_options' );
delete_option( 'ecwid_wc_sync_missing_parents' );

// Delete custom post type posts only if functions exist
if ( function_exists( 'get_posts' ) && function_exists( 'wp_delete_post' ) ) {
    // amazonq-ignore-next-line
    // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Variable is prefixed with metrotechs_e2w_ and is local to uninstall.php
    $metrotechs_e2w_posts_to_delete = get_posts( array(
        'post_type'      => 'ecwid_placeholder',
        'posts_per_page' => 50,
        'fields'         => 'ids',
        'post_status'    => array( 'any', 'trash', 'auto-draft' ),
    ) );

    if ( ! empty( $metrotechs_e2w_posts_to_delete ) ) {
        // amazonq-ignore-next-line
        foreach ( $metrotechs_e2w_posts_to_delete as $post_id ) {
            wp_delete_post( $post_id, true );
        }
    }
}

// Clean up transients if function exists
if ( function_exists( 'delete_transient' ) ) {
    delete_transient( 'ecwid_wc_sync_cache' );
    delete_transient( 'ecwid_wc_categories_cache' );
    delete_transient( 'ecwid_wc_products_cache' );
}

// Clear any scheduled events if function exists
if ( function_exists( 'wp_clear_scheduled_hook' ) ) {
    wp_clear_scheduled_hook( 'ecwid_wc_sync_cron' );
}

// Database cleanup with direct queries (only if wpdb is available)
global $wpdb;
if ( $wpdb ) {
    // Delete term meta
    if ( isset( $wpdb->termmeta ) ) {
        $wpdb->delete( $wpdb->termmeta, array( 'meta_key' => '_ecwid_category_id' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Direct deletion of plugin meta data during uninstall
        $wpdb->delete( $wpdb->termmeta, array( 'meta_key' => '_ecwid_placeholder_category' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Direct deletion of plugin meta data during uninstall
    }
    
    // Delete post meta
    // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Variable is prefixed with metrotechs_e2w_ and is local to uninstall.php
    $metrotechs_e2w_meta_keys = array(
        '_ecwid_product_id',
        '_ecwid_product_sku_ref',
        '_ecwid_last_sync_time',
        '_ecwid_variation_id',
        '_ecwid_image_source_url',
        '_ecwid_gallery_image_source_url',
        '_ecwid_placeholder_parent_id',
        '_ecwid_placeholder_term_id',
    );
    
    // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Variable is prefixed with metrotechs_e2w_ and is local to uninstall.php
    foreach ( $metrotechs_e2w_meta_keys as $metrotechs_e2w_meta_key ) {
        $wpdb->delete( $wpdb->postmeta, array( 'meta_key' => $metrotechs_e2w_meta_key ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Direct deletion of plugin meta data during uninstall
    }
}

// Additional cleanup: try to remove plugin files if possible
// This helps with the file deletion issue
if ( function_exists( 'wp_filesystem' ) ) {
    global $wp_filesystem;
    if ( ! $wp_filesystem ) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        WP_Filesystem();
    }
    
    if ( $wp_filesystem ) {
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Variable is prefixed with metrotechs_e2w_ and is local to uninstall.php
        $metrotechs_e2w_plugin_dir = plugin_dir_path( __FILE__ );
        // Try to set proper permissions before deletion
        if ( $wp_filesystem->is_dir( $metrotechs_e2w_plugin_dir . 'assets' ) ) {
            $wp_filesystem->chmod( $metrotechs_e2w_plugin_dir . 'assets', 0755, true );
            $wp_filesystem->chmod( $metrotechs_e2w_plugin_dir . 'assets/css', 0755, true );
            $wp_filesystem->chmod( $metrotechs_e2w_plugin_dir . 'assets/js', 0755, true );
            
            // Set file permissions
            if ( $wp_filesystem->exists( $metrotechs_e2w_plugin_dir . 'assets/css/admin-styles.css' ) ) {
                $wp_filesystem->chmod( $metrotechs_e2w_plugin_dir . 'assets/css/admin-styles.css', 0644 );
            }
            if ( $wp_filesystem->exists( $metrotechs_e2w_plugin_dir . 'assets/js/admin-sync.js' ) ) {
                $wp_filesystem->chmod( $metrotechs_e2w_plugin_dir . 'assets/js/admin-sync.js', 0644 );
            }
        }
    }
}

?>