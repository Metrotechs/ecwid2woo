# Ecwid2Woo Product Sync

Synchronize your Ecwid store’s categories and products into WooCommerce, complete with SKUs, descriptions, prices, stock levels, images, and variations. This plugin is designed to provide a seamless bridge between your Ecwid storefront and your WooCommerce-powered WordPress site.

## Key Features

-   **Comprehensive Full Sync with Preview**
    *   Automatically fetches and displays a preview of categories and products, along with initial counts of total categories and products to be synced from your Ecwid store upon page load.
    *   **Phase 1: Category Import:** Imports all Ecwid categories, meticulously preserving parent/child relationships.
    *   **Phase 2: Product Import:** Imports all enabled Ecwid products, supporting both simple and variable product types.

-   **Dedicated Category Sync & Management with Preview**
    *   The “Category Sync” tab automatically loads and displays a list of your Ecwid categories, showing the total count available for sync before the process begins.
    *   Allows importing or updating all categories independently of products.
    *   Includes a “Fix Category Hierarchy” tool to resolve parent-child relationships if parent categories were imported before their children, utilizing placeholder data.
    *   Manages "Placeholder" categories for items whose parents were not yet synced, accessible via a dedicated "Placeholders" admin menu.

-   **Selective Product Sync with Preview**
    *   The “Product Sync” tab automatically loads your Ecwid product catalog for selection upon page load, displaying the total number of products available from Ecwid.
    *   Allows you to select individual products (or all using a "Select All/None" checkbox) for import or update into WooCommerce.

-   **Detailed Product Data Sync**
    *   Syncs essential product information: names, SKUs, and both long (main) and short descriptions.
    *   Handles regular and sale prices, utilizing Ecwid’s “Compare-To” price feature to set WooCommerce sale prices.
    *   Manages stock quantity and stock management status (including "unlimited" stock).
    *   Imports product weight and dimensions (length, width, height).
    *   Sets WooCommerce product publish status ('publish' or 'draft') based on the product's enabled status in Ecwid.
    *   Syncs featured images and gallery images, with intelligent checks to avoid re-downloading unchanged files by comparing source URLs.

-   **Advanced Variable Products & Attributes Handling**
    *   Translates Ecwid product options (e.g., Size, Color) and their chosen values into WooCommerce global product attributes and terms.
    *   Creates corresponding product variations in WooCommerce based on Ecwid product combinations.
    *   Syncs per-variation SKU, price (regular and sale, respecting Ecwid's default displayed prices for combinations), stock quantity, and weight.
    *   Cleans up stale WooCommerce variations if their corresponding Ecwid combinations are removed.

-   **Robust AJAX-Powered UI & Batch Processing**
    *   All synchronization operations (Full, Category, Product, and Variation batches) are performed in configurable batches using AJAX to prevent server timeouts and ensure smooth processing, even for large catalogs.
    *   Provides real-time feedback through dynamic progress bars for overall and step-specific progress.
    *   Displays live, detailed logging for each operation within the respective sync tab, categorizing messages by type (info, success, warning, error).
    *   Uses animated status messages (e.g., "Syncing...") for better visual feedback during operations.
    *   Standardizes status messages, consistently using "N/A" for totals if specific counts could not be determined.

-   **Idempotent Operations for Safe Re-Syncing**
    *   Intelligently matches existing WooCommerce terms (categories) and products to avoid duplicates.
    *   Uses the Ecwid ID (stored in WooCommerce meta fields: `_ecwid_category_id` for categories, `_ecwid_product_id` for products, `_ecwid_variation_id` for variations) as the primary identifier. *   For products, it can fall back to SKU if an Ecwid ID meta field is not yet present on an existing WooCommerce product.
    *   Prevents duplicate entries on subsequent syncs, updating existing items instead with the latest data from Ecwid.

-   **Enhanced API Interaction & Error Handling**
    *   Improved reliability in fetching item counts from Ecwid, with stricter validation of API responses. The system now reports an error if essential 'total' count fields are missing from an API response, even if the HTTP status is 200 (OK), preventing misleading "0 count" displays.
    *   Detailed error messages and logging for AJAX failures and API issues.

-   **Placeholder System for Category Hierarchy**
    *   If a category is synced before its parent, a temporary "Ecwid Placeholder" post (Custom Post Type) and a placeholder WooCommerce category term are created.
    *   The "Fix Category Hierarchy" tool uses these placeholders to correctly assign parent-child relationships once the actual parent category is synced.

## Requirements

-   WordPress 5.0+
-   WooCommerce 3.0+
-   PHP 7.2+ with the cURL extension enabled
-   A valid Ecwid Store ID and API Secret Token

## Installation

1.  Download the latest release ZIP file from the GitHub repository.
2.  In your WordPress Admin dashboard, navigate to **Plugins → Add New**.
3.  Click the **Upload Plugin** button.
4.  Choose the downloaded ZIP file and click **Install Now**.
5.  After installation, click **Activate Plugin**.

Alternatively, you can manually unzip the plugin and upload the `ecwid2woo` folder to your `/wp-content/plugins/` directory, then activate it from the Plugins page.

## Usage

Once activated, the plugin adds a new top-level menu item in your WordPress admin sidebar: **Ecwid2Woo Sync**.

### 1. Configure Settings

1.  Navigate to **Ecwid2Woo Sync → Settings**.
2.  Enter your Ecwid **Store ID**.
3.  Enter your Ecwid **API Secret Token**.
4.  Click **Save Settings**.
    *   _These credentials are required for the plugin to communicate with your Ecwid store._

### 2. Perform a Full Sync

This is recommended for the initial setup or when you want to synchronize everything from Ecwid to WooCommerce.

1.  Navigate to **Ecwid2Woo Sync → Full Sync**.
2.  Upon page load, the plugin automatically:
    *   Loads a preview list of categories and products that will be synced.
    *   Fetches and displays the total counts of categories and products from your Ecwid store.
3.  Once the preview and counts are loaded, the **Start Full Sync** button will become active. Click it to begin.
4.  Monitor the progress:
    *   An overall progress bar tracks the entire sync process (Phase 1: Categories, then Phase 2: Products).
    *   The status text will show "Syncing {Type}: {current} of {total}..." for the current step (e.g., "Syncing Categories: 50 of 100...").
    *   A detailed log panel provides real-time updates on each item being processed, including successes, warnings, and errors.

### 3. Sync Categories Only

Use this tab if you only need to import or update your category structure from Ecwid.

1.  Navigate to **Ecwid2Woo Sync → Category Sync**.
2.  The page automatically loads a list of your Ecwid categories and displays the total count available for sync.
3.  Click **Start Category Sync** to import or update all categories.
4.  **Fix Category Hierarchy:** If some categories were imported before their parents, their hierarchy might be incorrect. After the category sync, click the **Fix Category Hierarchy** button. This tool attempts to resolve these relationships using the placeholder data created during the sync.

### 4. Sync Specific Products (Selective Sync)

Use this tab to import or update only selected products from Ecwid.

1.  Navigate to **Ecwid2Woo Sync → Product Sync**.
2.  The page automatically loads a list of all enabled products from your Ecwid store and displays the total count of products available for selection.
3.  Once the list appears, you can select individual products using the checkboxes. A "Select All/None" checkbox at the top of the list allows for bulk selection/deselection. Product details like name, SKU, ID, and variation count (if applicable) are shown in the list.
4.  After making your selections, click **Import Selected Products**.
5.  The plugin will then process only the chosen products. For variable products, the parent product is imported first, followed by its variations in batches. Progress and detailed logs are displayed.

### 5. Manage Placeholders

During category sync, if a category's parent is not yet synced from Ecwid, a temporary placeholder term and a corresponding "Ecwid Placeholder" post are created to maintain the relationship data.

1.  Navigate to **Ecwid2Woo Sync → Placeholders**.
2.  This screen lists all "Ecwid Placeholder" posts. While direct management here is limited, these placeholders are primarily used by the "Fix Category Hierarchy" tool. Once hierarchies are fixed for a given placeholder, it may no longer be actively referenced for hierarchy resolution but will remain listed unless manually deleted.

---

**Troubleshooting & Logs:**

-   Each sync tab (Full, Category, Product) provides a live log panel displaying detailed information about the ongoing process. These logs are crucial for understanding what the plugin is doing and for diagnosing any issues.
-   Check your browser's developer console (usually F12) for any JavaScript errors, especially if the UI is not behaving as expected.
-   For server-side debugging, ensure `WP_DEBUG` and `WP_DEBUG_LOG` are enabled in your `wp-config.php` file. PHP errors and plugin-specific `error_log` messages will be written to `/wp-content/debug.log` (or the configured log path).

## Screenshots

*(Consider adding screenshots of the Settings page, Full Sync page with preview, Category Sync page with list, and Product Sync page with selection list and progress.)*

## Support

If you encounter issues or have questions, please open an issue on the GitHub repository. Provide as much detail as possible, including steps to reproduce the problem, error messages from the log panel or `debug.log`, and your WordPress/WooCommerce/PHP versions.

## Contributing

Contributions are welcome! Please feel free to fork the repository, make your changes, and submit a pull request.

## License

This plugin is licensed under the GPLv2 or later.
See the `LICENSE` file for more details (if one is included, otherwise state: License URI: https://www.gnu.org/licenses/gpl-2.0.html).
