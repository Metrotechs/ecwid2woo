# Ecwid2Woo Product Sync

Synchronize your Ecwid store’s categories and products into WooCommerce, complete with SKUs, descriptions, prices, stock levels, images, and variations.

## Key Features

- **Comprehensive Full Sync**
  -   Fetches and displays initial counts of total categories, products, and variations to be synced.
  -   **Phase 1: Category Import:** Imports all Ecwid categories, preserving parent/child relationships.
  -   **Phase 2: Product Import:** Imports all enabled Ecwid products (both simple and variable types).

- **Dedicated Category Sync & Management**
  -   A “Category Sync” tab allows importing or updating categories independently of products.
  -   Includes a “Fix Category Hierarchy” tool to resolve parent-child relationships if parent categories were initially missing.
  -   Manages "Placeholder" categories for items whose parents were not yet synced, accessible via a "Placeholders" admin menu.

- **Selective Product Sync**
  -   The “Product Sync” tab enables loading your Ecwid product catalog.
  -   Allows you to select individual products (or all) for import or update.

- **Detailed Product Data Sync**
  -   Syncs product names, SKUs, and both long and short descriptions.
  -   Handles regular and sale prices (utilizing Ecwid’s “Compare-To” price for sale prices).
  -   Manages stock quantity and stock management status.
  -   Imports product weight and dimensions.
  -   Sets WooCommerce product publish status based on Ecwid's enabled status.
  -   Syncs featured images and gallery images, with checks to avoid re-downloading unchanged files.

- **Advanced Variable Products & Attributes Handling**
  -   Translates Ecwid product options and combinations into WooCommerce global product attributes and terms.
  -   Creates corresponding product variations in WooCommerce.
  -   Syncs per-variation SKU, price (regular and sale), stock quantity, and weight.

- **Robust AJAX-Powered UI & Batch Processing**
  -   All synchronization operations (Full, Category, Product) are performed in batches using AJAX to prevent server timeouts.
  -   Provides real-time feedback through progress bars for overall and step-specific progress.
  -   Displays live, detailed logging for each operation within the respective sync tab.

- **Idempotent Operations for Safe Re-Syncing**
  -   Intelligently matches existing WooCommerce terms (categories) and products.
  -   Uses Ecwid ID (stored in WooCommerce meta fields) as the primary identifier, falling back to SKU for products.
  -   Prevents duplicate entries on subsequent syncs, updating existing items instead.

## Requirements

- WordPress 5.0+
- WooCommerce 3.0+
- PHP 7.2+ with the cURL extension enabled
- A valid Ecwid Store ID and API Secret Token

## Installation

1.  Download the latest release ZIP file from the GitHub repository.
2.  In your WordPress Admin dashboard, navigate to **Plugins → Add New**.
3.  Click the **Upload Plugin** button.
4.  Choose the downloaded ZIP file and click **Install Now**.
5.  After installation, click **Activate Plugin**.

Alternatively, you can manually unzip the plugin and upload the `ecwid2woo-product-sync` folder to your `/wp-content/plugins/` directory, then activate it from the Plugins page.

## Usage

Once activated, the plugin adds a new top-level menu item in your WordPress admin sidebar: **Ecwid2Woo Sync**.

### 1. Configure Settings

1.  Navigate to **Ecwid2Woo Sync → Settings**.
2.  Enter your Ecwid **Store ID**.
3.  Enter your Ecwid **API Secret Token**.
4.  Click **Save Settings**.
    *   _These credentials are required for the plugin to communicate with your Ecwid store._

### 2. Perform a Full Sync

This is recommended for the initial setup or when you want to synchronize everything.

1.  Navigate to **Ecwid2Woo Sync → Full Sync**.
2.  The page will first attempt to fetch and display the total number of categories, products, and variations found in your Ecwid store.
3.  Click the **Start Full Sync** button.
4.  Monitor the progress:
    *   An overall progress bar tracks the entire sync process (categories, then products).
    *   The status text will show "X of Y" for the current step (e.g., "Syncing Categories: 50 of 100...").
    *   A detailed log provides real-time updates on each item being processed.

### 3. Sync Categories Only

Use this if you only need to update your category structure.

1.  Navigate to **Ecwid2Woo Sync → Category Sync**.
2.  Click **Start Category Sync** to import or update all categories.
3.  **Fix Category Hierarchy:** If some categories were imported before their parents, their hierarchy might be incorrect. After the category sync, click the **Fix Category Hierarchy** button to attempt to resolve these relationships. This tool uses placeholder data created during the sync.

### 4. Sync Specific Products (Selective Sync)

Use this to import or update only selected products.

1.  Navigate to **Ecwid2Woo Sync → Product Sync**.
2.  Click **Load Ecwid Products for Selection**. This will fetch a list of all enabled products from your Ecwid store.
3.  Once the list appears, you can select individual products using the checkboxes. A "Select All/None" option is also available.
4.  After making your selections, click **Import Selected Products**.
5.  The plugin will then process only the chosen products, with progress and logs displayed.

### 5. Manage Placeholders

During category sync, if a category's parent is not yet synced, a temporary placeholder term and a corresponding "Ecwid Placeholder" post are created.

1.  Navigate to **Ecwid2Woo Sync → Placeholders**.
2.  This screen lists all placeholder posts. While direct management here is limited, these placeholders are primarily used by the "Fix Category Hierarchy" tool. Once hierarchies are fixed, these placeholders may no longer be actively referenced.

---

**Troubleshooting & Logs:**

-   Each sync tab (Full, Category, Product) provides a live log panel displaying detailed information about the ongoing process, including successes, warnings, and errors.
-   For server-side debugging, ensure `WP_DEBUG` and `WP_DEBUG_LOG` are enabled in your `wp-config.php` file. PHP errors and plugin-specific error_log messages will be written to `/wp-content/debug.log`.
