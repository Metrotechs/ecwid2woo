<?php
/**
 * Ecwid2Woo Product Sync Uninstall
 *
 * Uninstalls the plugin and deletes its data.
 *
 * @package Ecwid2Woo
 */

// Exit if accessed directly.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;

// 1. Delete Plugin Options
delete_option( 'ecwid_wc_sync_options' );
delete_option( 'ecwid_wc_sync_missing_parents' );
// Add any other options your plugin might create here.

// 2. Delete Custom Post Type Posts ('ecwid_placeholder')
$placeholder_posts_args = array(
    'post_type'      => 'ecwid_placeholder',
    'posts_per_page' => -1,
    'fields'         => 'ids', // Only get post IDs for efficiency.
    'post_status'    => 'any', // Include private, trash, etc.
);
$placeholder_posts = get_posts( $placeholder_posts_args );

if ( ! empty( $placeholder_posts ) ) {
    foreach ( $placeholder_posts as $post_id ) {
        wp_delete_post( $post_id, true ); // true to force delete, bypass trash.
    }
}

// 3. Delete Term Meta
// Meta key for Ecwid category ID link
$wpdb->delete( $wpdb->termmeta, array( 'meta_key' => '_ecwid_category_id' ) );
// Meta key for placeholder category identification
$wpdb->delete( $wpdb->termmeta, array( 'meta_key' => '_ecwid_placeholder_category' ) );

// 4. Delete Post Meta (for products and variations)
$post_meta_keys_to_delete = array(
    '_ecwid_product_id',
    '_ecwid_product_sku_ref',
    '_ecwid_last_sync_time',
    '_ecwid_variation_id',
    '_ecwid_image_source_url',         // For featured images
    '_ecwid_gallery_image_source_url', // For gallery images
    '_ecwid_placeholder_parent_id',    // For CPT 'ecwid_placeholder'
    '_ecwid_placeholder_term_id',      // For CPT 'ecwid_placeholder'
);

foreach ( $post_meta_keys_to_delete as $meta_key ) {
    $wpdb->delete( $wpdb->postmeta, array( 'meta_key' => $meta_key ) );
}

// Clear any cached data related to the plugin if necessary
wp_cache_flush();

?>