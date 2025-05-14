# Ecwid to WooCommerce Sync

This WordPress plugin facilitates the migration and synchronization of your product data from an Ecwid store to a WooCommerce store. It is designed to transfer a comprehensive set of product information, including SKUs, product names, descriptions, prices, stock levels, categories, product images (featured and gallery), and product variations (based on Ecwid options and combinations).

## Key Features

*   **Comprehensive Data Transfer**: Migrates essential product details:
    *   Product Name, SKU, Long and Short Descriptions
    *   Regular Price and Sale Price (from Ecwid's "Compare to" price)
    *   Stock Quantity and Management Status
    *   Product Weight and Dimensions
    *   Product Status (Published/Draft based on Ecwid's enabled status)
*   **Category Sync**:
    *   Imports Ecwid categories into WooCommerce product categories.
    *   Maintains parent-child category relationships.
    *   Avoids duplicate category creation.
*   **Product Image Handling**:
    *   Imports Ecwid product featured images.
    *   Imports Ecwid product gallery images.
    *   Attempts to prevent re-downloading unchanged images on subsequent syncs.
*   **Variable Product Support**:
    *   Converts Ecwid product options and combinations into WooCommerce product attributes and variations.
    *   Creates global WooCommerce attributes and terms as needed.
    *   Sets variation-specific details like SKU, price, stock, and weight.
*   **Two Sync Modes**:
    *   **Full Sync**: Imports all categories and then all enabled products from Ecwid. Ideal for initial migration or complete data refresh.
    *   **Selective Import**: Allows you to load a list of enabled Ecwid products and choose specific items to import or update.
*   **Batch Processing**: Sync operations are performed in batches to prevent server timeouts and handle large catalogs efficiently.
*   **AJAX Powered UI**: Provides a user-friendly interface within the WordPress admin area with real-time progress updates and detailed logging for sync operations.
*   **Idempotent Operations**: The plugin attempts to update existing WooCommerce products and categories based on their corresponding Ecwid IDs (stored in meta fields) or SKUs, rather than creating duplicates.
*   **Detailed Logging**: Offers insights into the synchronization process, making it easier to troubleshoot any issues.

## Requirements

*   WordPress (tested with version X.X and later - *you might want to specify this*)
*   WooCommerce plugin installed and activated.
*   PHP version X.X or higher (with `curl` extension for API calls - *you might want to specify this*).
*   Valid Ecwid Store ID and API Access Token (Secret Token).

## Installation

1.  **Download**: Download the plugin ZIP file.
2.  **Upload to WordPress**:
    *   Log in to your WordPress admin area.
    *   Navigate to `Plugins` > `Add New`.
    *   Click on the `Upload Plugin` button at the top.
    *   Choose the downloaded ZIP file and click `Install Now`.
3.  **Activate**: After installation, click the `Activate Plugin` button.

Alternatively, you can manually install the plugin:
1.  Unzip the downloaded file.
2.  Upload the `ecwid-to-woocommere` (or the correct plugin folder name) directory to the `/wp-content/plugins/` directory of your WordPress installation.
3.  Log in to your WordPress admin area, navigate to `Plugins`, find "Ecwid to WooCommerce Sync" in the list, and click `Activate`.

## Usage

After activating the plugin, you will find a new menu item in your WordPress admin sidebar: **"Ecwid Sync"**.

### 1. Configuration

*   Navigate to `Ecwid Sync` > `Ecwid Sync` (the main page).
*   Enter your **Ecwid Store ID** and **Ecwid API Secret Token** in the "API Credentials" section.
*   Click `Save Changes`.

### 2. Full Data Sync

This option will sync all categories first, followed by all *enabled* products from your Ecwid store.

*   Navigate to `Ecwid Sync` > `Ecwid Sync`.
*   Under the "Full Data Sync" section, click the **"Start Full Sync"** button.
*   The plugin will first sync categories and then products.
*   Progress, status, and detailed logs will be displayed on the page. Do not navigate away from the page until the process completes or indicates it's safe to do so.

### 3. Selective Product Import

This option allows you to choose specific *enabled* products from Ecwid to import or update in WooCommerce.

*   Navigate to `Ecwid Sync` > `Selective Import`.
*   Click the **"Load Ecwid Products for Selection"** button. This will fetch a list of your enabled products from Ecwid.
*   Once the list is loaded, checkboxes will appear next to each product. Select the products you wish to import/update.
*   After making your selections, click the **"Import Selected Products"** button.
*   Progress, status, and logs for the selected products will be displayed.

## Important Notes

*   **Backup Recommended**: Before running a full sync for the first time, it is highly recommended to back up your WordPress database and `wp-content` directory.
*   **Execution Time**: Syncing a large number of products and images can take a significant amount of time. The plugin uses batch processing to mitigate server timeouts, but ensure your server's PHP `max_execution_time` and memory limits are reasonably configured if you encounter issues. The plugin attempts to set `set_time_limit(300)` for its AJAX operations.
*   **API Limits**: While the plugin is designed to be respectful of API limits, extremely frequent or large syncs could potentially hit Ecwid API rate limits.
*   **Product Updates**:
    *   The sync process updates existing WooCommerce products if a matching Ecwid Product ID (stored in `_ecwid_product_id` meta) or SKU is found.
    *   If a product in Ecwid is disabled, its corresponding product in WooCommerce will be set to "draft" status.
    *   If an Ecwid product is deleted, it is **not** automatically deleted from WooCommerce by this plugin. You would need to manage deletions manually in WooCommerce.
*   **Image Sync**:
    *   The plugin attempts to avoid re-downloading images if the source URL from Ecwid hasn't changed.
    *   If an image is removed from an Ecwid product's gallery, the plugin will attempt to remove it from the WooCommerce product's gallery during the next sync of that product.
*   **Product Type Changes**: If a product changes type in Ecwid (e.g., from simple to variable), the plugin will attempt to update the WooCommerce product accordingly. This might involve deleting existing variations if a product changes from variable to simple.

## Troubleshooting

*   **Check Logs**: The on-page logs during sync operations provide detailed information. Additionally, critical errors might be logged in your server's PHP error log or WordPress debug log (if enabled).
*   **API Credentials**: Ensure your Ecwid Store ID and API Token are correct and have the necessary permissions.
*   **Plugin Conflicts**: Temporarily deactivate other plugins to check for conflicts if you experience unexpected behavior.
*   **Server Resources**: For very large stores, ensure your hosting environment has sufficient resources (CPU, memory, execution time).

---

*This README is based on the observed functionality of the plugin code. Please test thoroughly in a staging environment before using on a live site.*
