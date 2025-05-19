=== Ecwid2Woo Product Sync ===
Contributors: Metrotechs, Richard Hunting
Donate link: https://metrotechs.io/donate
Tags: ecwid, woocommerce, sync, products, categories, import, migration, ecwid sync, woocommerce sync, product import, category import, product sync, category sync, ecwid to woocommerce, woocommerce ecwid
Requires at least: 5.0
Tested up to: 6.8
Requires PHP: 7.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
WC requires at least: 3.0
WC tested up to: 9.2

Easily Sync Ecwid Product Data (products, categories, images, SKUs, etc.) to WooCommerce.

== Description ==

Ecwid2Woo Product Sync is a robust plugin for migrating and synchronizing your Ecwid product catalog with WooCommerce. It is ideal for store owners moving to WooCommerce or keeping WooCommerce in sync with Ecwid. Key features include:
This plugin provides a comprehensive solution for transferring your product data, including names, descriptions, prices, images, SKUs, stock levels, categories, and product variations from Ecwid to WooCommerce. 
It features a user-friendly interface with real-time progress updates and detailed logging for a smooth and transparent synchronization experience.

== What's New in 0.0.7 ==

* UI/UX: Changed "Load Sync Preview" to "Reload Sync Data" for clarity.
* Feature: Added red STOP SYNC button to allow cancellation of sync in progress.
* Feature: Sync cancellation logic with user feedback in the log.
* Fix: Variation import now auto-creates missing attribute terms for Ecwid options/values.
* Enhance: Improved error handling and feedback for all sync operations.

== Key Features ==

* **Comprehensive Full Sync with Preview:**
    * Automatically fetches and displays a preview of categories and products from Ecwid, with initial counts, on page load.
    * **Phase 1:** Imports all Ecwid categories, preserving parent/child relationships.
    * **Phase 2:** Imports all enabled Ecwid products, supporting both simple and variable product types.
    * Real-time progress bars and status updates for each sync phase.
    * Detailed, categorized logs (info, success, warning, error) for every operation.
* **Category Sync & Hierarchy Management:**
    * Dedicated tab to preview and sync categories independently.
    * "Fix Category Hierarchy" button resolves parent-child relationships if parents were imported after children.
    * Handles complex category structures and ensures accurate replication in WooCommerce.
* **Selective Product Sync:**
    * Load all Ecwid products for selection; choose individual or all products for import/update.
    * "Select All/None" for efficient bulk selection.
    * Per-product logging and progress.
* **Product & Variation Data Handling:**
    * Names, SKUs, descriptions, prices (regular/sale), stock, weight, dimensions, images, and publish status.
    * Ecwid product options (e.g., Size, Color) are mapped to WooCommerce global product attributes and terms.
    * Missing attribute terms are auto-created during sync.
    * Product variations are created for all Ecwid product option combinations, with per-variation data (SKU, price, stock, image, etc.).
    * Stale WooCommerce product variations (those no longer in Ecwid) are cleaned up.
* **AJAX-Powered Batch Processing:**
    * Sync operations are performed in small, configurable batches to prevent server timeouts on large catalogs.
    * Live feedback: progress bars, animated status messages, and real-time logs keep you informed.
* **Idempotent & Safe Re-Syncing:**
    * Existing WooCommerce terms and products are matched by Ecwid ID (or SKU as fallback) to avoid duplicates.
    * Ecwid IDs are stored in WooCommerce meta fields for categories (`_ecwid_category_id`), products (`_ecwid_product_id`), and variations (`_ecwid_variation_id`).
    * This ensures that running the sync multiple times updates existing items rather than creating duplicates.
* **Error Handling & Troubleshooting:**
    * Strict checks for required fields in Ecwid API responses.
    * Detailed error logs for AJAX and API errors.
    * Sync cancellation: a bright red "STOP SYNC" button allows you to halt a sync in progress, with immediate feedback in the log.
* **User-Friendly Interface:**
    * Intuitive admin pages for settings, full sync, category sync, and selective product sync.
    * Clear instructions and feedback throughout the process.
* **Developer Friendly:**
    * Uses WordPress best practices, including actions and filters where appropriate.
    * Code is organized and commented for easier understanding and extension.

== Installation ==

1.  Upload the `ecwid2woo-product-sync` folder to the `/wp-content/plugins/` directory via FTP, or upload the ZIP file through WordPress admin: **Plugins → Add New → Upload Plugin**.
2.  Activate the plugin through the 'Plugins' menu in WordPress.
3.  Go to **Ecwid2Woo Sync → Settings** in your WordPress admin menu.
4.  Enter your Ecwid **Store ID** and **API Secret Token**. Click **Save Settings**.
5.  Navigate to the **Full Sync**, **Category Sync**, or **Product Sync** tabs under "Ecwid2Woo Sync" to perform synchronization tasks.

It is highly recommended to **backup your WordPress database** before running any sync operations, especially the first time.

== Frequently Asked Questions ==

= Does this plugin sync orders or customers? =
No, this plugin is specifically designed for synchronizing products and categories from Ecwid to WooCommerce. Order and customer data are not handled.

= What happens if a product or category already exists in WooCommerce? =
The plugin first checks for an existing WooCommerce product or category using a stored Ecwid ID (saved in a meta field). If not found by ID, it may attempt to match by SKU for products or name for categories as a fallback. If a match is found, the existing item is updated with the data from Ecwid. If no match is found, a new item is created in WooCommerce. This prevents duplicate entries.

= Are product variations (Ecwid options/combinations) supported? =
Yes, absolutely. Ecwid product options (like Size, Color) are converted into WooCommerce global product attributes, and their respective values (like Small, Medium, Large or Red, Blue, Green) become attribute terms. The plugin then creates product variations in WooCommerce for each combination of these options, complete with their specific SKU, price, stock, and image if available. Missing attribute terms are automatically created.

= What if my Ecwid categories have parent-child relationships? =
The plugin is designed to replicate your Ecwid category hierarchy in WooCommerce. Parent-child relationships are preserved. The "Fix Category Hierarchy" tool on the Category Sync page can be used to correct any discrepancies that might occur if, for example, a child category is processed by the sync before its parent.

= Where do I get my Ecwid API credentials? =
You can find your Store ID and generate an API Secret Token in your Ecwid control panel. Typically, this is under a section like "Apps" > "API" or "Platform" > "API Keys". Please refer to Ecwid's documentation for the most current instructions.

= How does the "Fix Category Hierarchy" tool work? =
If a child category is synced before its parent category from Ecwid, it might temporarily appear as a top-level category in WooCommerce. The "Fix Category Hierarchy" tool scans all synced categories and re-establishes the correct parent-child relationships based on the original Ecwid structure.

= Can I stop a sync in progress? =
Yes! The Full Sync page now features a bright red "STOP SYNC" button. Clicking it will immediately halt the sync process and log a message indicating the sync was stopped by the user.

= What happens to products or categories I delete in Ecwid? =
This plugin primarily focuses on syncing data *from* Ecwid *to* WooCommerce. If you delete a product or category in Ecwid, it will not be automatically deleted from WooCommerce by this plugin in the current version. You would need to manage deletions in WooCommerce separately. However, stale product variations (e.g., a "Small, Red" t-shirt variation that no longer exists in Ecwid for a product) *are* removed from the corresponding WooCommerce product during a sync.

= Are product images synced? =
Yes, the main product image and gallery images from Ecwid are synced to the WooCommerce product. For variable products, if variations in Ecwid have their own specific images, these are also synced to the corresponding WooCommerce variations.

= Is there a limit to how many products or categories can be synced? =
The plugin uses AJAX-based batch processing, meaning it syncs data in small chunks to avoid server timeouts. This design allows it to handle large catalogs with many thousands of products and categories. The batch size is configurable in the plugin's internal settings (though not exposed in the UI in this version).

= Will this plugin slow down my site? =
The sync process is resource-intensive while it's running, as it involves making API calls to Ecwid and performing database operations in WordPress. It's best to run syncs during off-peak hours if you have a very busy site. The plugin itself, when not actively syncing, should have minimal to no impact on your site's performance.

= What if an attribute term (e.g., a color like "Fuchsia") exists in Ecwid but not in WooCommerce? =
The plugin will automatically create the missing attribute term (e.g., "Fuchsia" for the "Color" attribute) in WooCommerce during the sync process. You don't need to pre-define all possible terms.

== Screenshots ==

1.  The Settings Page: Configure your Ecwid API credentials here.
2.  The Full Sync Page: Interface for running a complete category and product synchronization.
3.  The Category Sync Page: Dedicated interface for syncing only categories and fixing hierarchy.
4.  The Selective Product Sync Page: Load and choose specific products to import or update.
5.  Sync In Progress: Example of the progress bar and live log during a sync operation.

(Note: You will need to create these screenshots and name them `screenshot-1.png`, `screenshot-2.png`, etc., and place them in the `assets` folder of your SVN repository once your plugin is approved.)

== Changelog ==

= 0.0.6 =
* UI/UX: Changed "Load Sync Preview" to "Reload Sync Data" for clarity.
* Feature: Added red STOP SYNC button to allow cancellation of sync in progress.
* Feature: Sync cancellation logic with user feedback in the log.
* Fix: Variation import now auto-creates missing attribute terms for Ecwid options/values.
* Enhance: Improved error handling and feedback for all sync operations.

= 0.0.5 =
* Fix: Fixed Full Sync preview page to properly display categories and products data
* Enhance: Increased variation batch size from 10 to 50 for faster syncing of variable products
* Enhance: Modified preview pages to show all categories and products instead of limiting the display
* Optimize: Ensured memory usage is optimized across all sync pages (Full Sync, Category Sync, and Partial Sync)
* Fix: Improved API authentication by adding proper Authorization Bearer headers
* Fix: Added better error handling and debug logging throughout the synchronization process
* Fix: Added null/undefined checks for categories and products arrays to prevent JavaScript errors
* Fix: Fixed i18n (internationalization) fallback mechanism to ensure all strings display correctly
* Enhance: Ensured preview data is included in both success and error responses for better UX

= 0.0.4 =
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