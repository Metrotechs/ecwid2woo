# Ecwid2Woo Product Sync

**Ecwid2Woo Product Sync** is a robust WordPress plugin that synchronizes your Ecwid store’s categories and products into WooCommerce, including SKUs, descriptions, prices, stock levels, images, and variations. Designed for reliability and ease of use, it provides a seamless bridge between your Ecwid storefront and your WooCommerce-powered WordPress site.

---

## Features

### 🟢 Full Data Sync with Preview
- **Automatic Preview:** On page load, fetches and displays a preview of categories and products to be synced, with item counts.
- **Two-Phase Sync:** 
  - **Phase 1:** Imports all Ecwid categories, preserving parent/child relationships.
  - **Phase 2:** Imports all enabled Ecwid products, supporting both simple and variable product types.
- **Progress Tracking:** Real-time progress bars and status updates for each sync phase.
- **Live Logging:** Detailed, categorized logs (info, success, warning, error) for every operation.

### 🟢 Category Sync & Hierarchy Management
- **Category Preview:** Dedicated tab to preview and sync categories independently.
- **Hierarchy Fix Tool:** "Fix Category Hierarchy" button resolves parent-child relationships if parents were imported after children, using a placeholder system.
- **Placeholder Management:** Temporary "Ecwid Placeholder" posts and terms are created for missing parents, with a dedicated admin menu for review.

### 🟢 Selective Product Sync
- **Product Selection:** Load all Ecwid products for selection; choose individual or all products for import/update.
- **Bulk Actions:** "Select All/None" for efficient bulk selection.
- **Per-Product Logging:** See detailed logs for each selected product.

### 🟢 Product & Variation Data Handling
- **Comprehensive Data Import:** Names, SKUs, descriptions, prices (regular/sale), stock, weight, dimensions, images, and publish status.
- **Attribute & Variation Support:** 
  - Ecwid options are mapped to WooCommerce global attributes and terms.
  - Missing attribute terms are auto-created during sync.
  - Variations are created for all Ecwid combinations, with per-variation data (SKU, price, stock, etc.).
  - Stale WooCommerce variations are cleaned up if removed from Ecwid.

### 🟢 AJAX-Powered Batch Processing
- **Batch Size Control:** Sync operations are performed in small, configurable batches to prevent server timeouts.
- **Live Feedback:** Progress bars, animated status messages, and real-time logs keep you informed.

### 🟢 Idempotent & Safe Re-Syncing
- **Duplicate Prevention:** Existing WooCommerce terms and products are matched by Ecwid ID (or SKU as fallback) to avoid duplicates.
- **Meta Fields:** Ecwid IDs are stored in WooCommerce meta fields for categories (`_ecwid_category_id`), products (`_ecwid_product_id`), and variations (`_ecwid_variation_id`).

### 🟢 Error Handling & Troubleshooting
- **API Response Validation:** Strict checks for required fields in Ecwid API responses.
- **Detailed Error Logs:** AJAX and API errors are logged with clear messages.
- **Sync Cancellation:** A bright red "STOP SYNC" button allows you to halt a sync in progress, with immediate feedback in the log.

---

## Requirements

- WordPress 5.0+
- WooCommerce 3.0+
- PHP 7.2+ (with cURL extension)
- A valid Ecwid Store ID and API Secret Token

---

## Installation

1. Download the plugin ZIP from GitHub or your distribution source.
2. In your WordPress Admin, go to **Plugins → Add New**.
3. Click **Upload Plugin**, select the ZIP, and click **Install Now**.
4. Activate the plugin after installation.

_Manual install:_ Unzip and upload the `ecwid2woo` folder to `/wp-content/plugins/`, then activate from the Plugins page.

---

## Usage

### 1. Configure Settings

- Go to **Ecwid2Woo Sync → Settings**.
- Enter your Ecwid **Store ID** and **API Secret Token**.
- Click **Save Settings**.

### 2. Full Sync

- Go to **Ecwid2Woo Sync → Full Sync**.
- The page loads a preview of categories and products to be synced.
- Click **Start Full Sync** to begin.
- Monitor progress via the progress bar and log panel.
- Use the **STOP SYNC** button to cancel at any time.

### 3. Category Sync

- Go to **Ecwid2Woo Sync → Category Sync**.
- Preview your Ecwid categories.
- Click **Start Category Sync** to import/update all categories.
- Use **Fix Category Hierarchy** if needed after sync.

### 4. Selective Product Sync

- Go to **Ecwid2Woo Sync → Product Sync**.
- Preview and select products to import/update.
- Click **Import Selected Products** to process only those products.

### 5. Placeholders

- Go to **Ecwid2Woo Sync → Placeholders** to review any placeholder posts created for missing parent categories.

---

## Troubleshooting

- **Logs:** Each sync tab provides a live log panel for detailed feedback.
- **JavaScript Errors:** Check your browser console (F12) if the UI misbehaves.
- **Server Errors:** Enable `WP_DEBUG` and `WP_DEBUG_LOG` in `wp-config.php` for PHP error logs.
- **Sync Issues:** If variations are skipped due to missing attribute terms, the plugin now auto-creates missing terms during sync.

---

## Screenshots

*(Add screenshots of the Settings, Full Sync, Category Sync, Product Sync, and Placeholders pages for best results.)*

---

## Support

For issues or questions, open an issue on the GitHub repository. Include:
- Steps to reproduce
- Error messages from the log panel or `debug.log`
- Your WordPress, WooCommerce, and PHP versions

---

## Contributing

Contributions are welcome! Fork the repo, make your changes, and submit a pull request.

---

## License

GPLv2 or later.  
See the `LICENSE` file or [License URI](https://www.gnu.org/licenses/gpl-2.0.html) for details.
