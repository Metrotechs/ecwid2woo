=== Ecwid2Woo Product Sync ===
Contributors: Metrotechs, richa (your WordPress.org username)
Donate link: https://metrotechs.io/donate
Tags: ecwid, woocommerce, sync, products, categories, import, migration, ecwid sync, woocommerce sync, product import, category import
Requires at least: 5.0
Tested up to: 6.5
Requires PHP: 7.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
WC requires at least: 3.0
WC tested up to: 8.8

Easily Sync Ecwid Product Data (products, categories, images, SKUs, etc.) to WooCommerce.

== Description ==

Ecwid2Woo Product Sync is a robust plugin for migrating and synchronizing your Ecwid product catalog with WooCommerce. It is ideal for store owners moving to WooCommerce or keeping WooCommerce in sync with Ecwid. Key features include:

== What's New in 2.0.1 ==

* UI/UX: Changed "Load Sync Preview" to "Reload Sync Data" for clarity.
* Feature: Added red STOP SYNC button to allow cancellation of sync in progress.
* Feature: Sync cancellation logic with user feedback in the log.
* Fix: Variation import now auto-creates missing attribute terms for Ecwid options/values.
* Enhance: Improved error handling and feedback for all sync operations.

== Key Features ==

* **Comprehensive Full Sync with Preview:**
    * Automatically fetches and displays a preview of categories and products, with initial counts, on page load.
    * **Phase 1:** Imports all Ecwid categories, preserving parent/child relationships.
    * **Phase 2:** Imports all enabled Ecwid products, supporting both simple and variable product types.
    * Real-time progress bars and status updates for each sync phase.
    * Detailed, categorized logs (info, success, warning, error) for every operation.
* **Category Sync & Hierarchy Management:**
    * Dedicated tab to preview and sync categories independently.
    * "Fix Category Hierarchy" button resolves parent-child relationships if parents were imported after children, using a placeholder system.
    * Temporary "Ecwid Placeholder" posts and terms are created for missing parents, with a dedicated admin menu for review.
* **Selective Product Sync:**
    * Load all Ecwid products for selection; choose individual or all products for import/update.
    * "Select All/None" for efficient bulk selection.
    * Per-product logging and progress.
* **Product & Variation Data Handling:**
    * Names, SKUs, descriptions, prices (regular/sale), stock, weight, dimensions, images, and publish status.
    * Ecwid options are mapped to WooCommerce global attributes and terms.
    * Missing attribute terms are auto-created during sync.
    * Variations are created for all Ecwid combinations, with per-variation data (SKU, price, stock, etc.).
    * Stale WooCommerce variations are cleaned up if removed from Ecwid.
* **AJAX-Powered Batch Processing:**
    * Sync operations are performed in small, configurable batches to prevent server timeouts.
    * Live feedback: progress bars, animated status messages, and real-time logs keep you informed.
* **Idempotent & Safe Re-Syncing:**
    * Existing WooCommerce terms and products are matched by Ecwid ID (or SKU as fallback) to avoid duplicates.
    * Ecwid IDs are stored in WooCommerce meta fields for categories (`_ecwid_category_id`), products (`_ecwid_product_id`), and variations (`_ecwid_variation_id`).
* **Error Handling & Troubleshooting:**
    * Strict checks for required fields in Ecwid API responses.
    * Detailed error logs for AJAX and API errors.
    * Sync cancellation: a bright red "STOP SYNC" button allows you to halt a sync in progress, with immediate feedback in the log.

== Installation ==

1.  Upload the `ecwid2woo-product-sync` folder to the `/wp-content/plugins/` directory via FTP, or upload the ZIP file through WordPress admin: **Plugins → Add New → Upload Plugin**.
2.  Activate the plugin through the 'Plugins' menu in WordPress.
3.  Go to **Ecwid2Woo Sync → Settings** in your WordPress admin menu.
4.  Enter your Ecwid **Store ID** and **API Secret Token**. Click **Save Settings**.
5.  Navigate to the **Full Sync**, **Category Sync**, or **Product Sync** tabs under "Ecwid2Woo Sync" to perform synchronization tasks.

It is highly recommended to **backup your WordPress database** before running any sync operations, especially the first time.

== Frequently Asked Questions ==

= Does this plugin sync orders or customers? =
Currently, this plugin focuses on synchronizing products and categories. Order and customer synchronization is not included in this version.

= What happens if a product or category already exists in WooCommerce? =
The plugin attempts to match existing items by a stored Ecwid ID (meta field) or by SKU (for products) / name (for categories). If a match is found, it will update the existing item. Otherwise, it will create a new one.

= Are product variations (Ecwid options/combinations) supported? =
Yes, the plugin supports syncing Ecwid product options and combinations as WooCommerce product attributes and variations. It will attempt to create global attributes and terms in WooCommerce based on your Ecwid product options. Missing attribute terms are now auto-created during sync.

= What if my Ecwid categories have parent-child relationships? =
The plugin attempts to replicate the category hierarchy during the sync. A "Fix Category Hierarchy" tool is also provided on the Category Sync page to resolve potential ordering issues after an initial sync, especially if parent categories were imported after their children in some batches.

= Where do I get my Ecwid API credentials? =
You can find your Store ID and generate an API Secret Token in your Ecwid control panel. Typically, this is under a section like "Apps" > "API" or "Platform" > "API Keys". Please refer to Ecwid's documentation for the most current instructions.

= How does the "Fix Category Hierarchy" tool work? =
During category sync, if a parent category hasn't been imported yet when a child category is processed, the child might temporarily become a top-level category. The "Fix Category Hierarchy" tool re-evaluates these relationships once all categories are imported and attempts to set the correct parent-child links.

= Can I stop a sync in progress? =
Yes! The Full Sync page now features a bright red "STOP SYNC" button. Clicking it will immediately halt the sync process and log a message indicating the sync was stopped by the user.

== Screenshots ==

1.  The Settings Page: Configure your Ecwid API credentials here.
2.  The Full Sync Page: Interface for running a complete category and product synchronization.
3.  The Category Sync Page: Dedicated interface for syncing only categories and fixing hierarchy.
4.  The Selective Product Sync Page: Load and choose specific products to import or update.
5.  Sync In Progress: Example of the progress bar and live log during a sync operation.

(Note: You will need to create these screenshots and name them `screenshot-1.png`, `screenshot-2.png`, etc., and place them in the `assets` folder of your SVN repository once your plugin is approved.)

== Changelog ==

= 2.0.1 =
* UI/UX: Changed "Load Sync Preview" to "Reload Sync Data" for clarity.
* Feature: Added red STOP SYNC button to allow cancellation of sync in progress.
* Feature: Sync cancellation logic with user feedback in the log.
* Fix: Variation import now auto-creates missing attribute terms for Ecwid options/values.
* Enhance: Improved error handling and feedback for all sync operations.

= 2.0.0 =
* Fix: Fixed Full Sync preview page to properly display categories and products data
* Enhance: Increased variation batch size from 10 to 50 for faster syncing of variable products
* Enhance: Modified preview pages to show all categories and products instead of limiting the display
* Optimize: Ensured memory usage is optimized across all sync pages (Full Sync, Category Sync, and Partial Sync)
* Fix: Improved API authentication by adding proper Authorization Bearer headers
* Fix: Added better error handling and debug logging throughout the synchronization process
* Fix: Added null/undefined checks for categories and products arrays to prevent JavaScript errors
* Fix: Fixed i18n (internationalization) fallback mechanism to ensure all strings display correctly
* Enhance: Ensured preview data is included in both success and error responses for better UX

= 1.9.2 =
* Feat: Enhance sync UX with animated status indicators for AJAX operations.
* Feat: Added 'ajax_error' to i18n localization for improved JavaScript error messaging.
* Fix: Ensured consistent text domain ('ecwid2woo-product-sync') across all internationalization functions.
* Fix: Implemented `load_plugin_textdomain` to correctly load translation files.
* Fix: Added version constant `ECWID2WOO_VERSION` for reliable asset versioning.
* Fix: Sanitized `$_GET['page']` in the admin page router for better security.
* Chore: General code cleanup and preparation for WordPress.org submission.

= 1.0.0 =
* Initial release. (You might want to add more details here if you have them from earlier versions or development milestones)

== Upgrade Notice ==

= 2.0.1 =
This update adds a STOP SYNC button, improves the Full Sync UI, and ensures all variation attribute terms are created automatically. Upgrade recommended for all users.

= 2.0.0 =
This major update fixes critical issues with the Full Sync preview page, increases variation batch size for faster syncing, shows more comprehensive previews, and optimizes memory usage across all sync operations. Upgrade recommended for all users.

= 1.9.2 =
This version includes important internationalization fixes, security enhancements, and UI improvements. Please update for improved stability and future compatibility. It is recommended to review your settings after updating.

== Support ==

For support, please use the plugin's support forum on WordPress.org. (This is the standard practice. If you offer premium support elsewhere, you can mention it, but the primary support channel for .org plugins is their forum.)
You can also find more information at https://metrotechs.io.