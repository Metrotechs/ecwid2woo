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

Ecwid2Woo Product Sync provides a comprehensive solution for migrating and synchronizing your product catalog from Ecwid to WooCommerce. This plugin allows you to:

*   **Two-Phase Full Sync:**
    1.  Import all Ecwid categories (with parent/child relationships).
    2.  Import all enabled Ecwid products (simple & variable).
*   **Category-Only Sync:** A dedicated “Category Sync” tab to import or update categories without touching products—including a “Fix Category Hierarchy” helper.
*   **Selective Product Sync:** The “Product Sync” tab lets you load your Ecwid catalog, pick individual items, and import/update just those products.
*   **Full Product Data Handled:**
    *   Names, SKUs, long & short descriptions
    *   Regular & sale prices (uses Ecwid’s “Compare-To” price)
    *   Stock quantity & management status
    *   Weight, dimensions, publish status
    *   Featured and gallery images (avoids re-downloading unchanged files)
*   **Variable Products & Attributes:** Translates Ecwid options/combinations into WooCommerce global attributes, terms, and variations with per-variation SKU, price, stock, and weight.
*   **Batch Processing & AJAX UI:** All sync operations run in batches via AJAX to prevent timeouts, with progress bars & live logs for clear feedback.
*   **Idempotent Updates:** Matches existing WooCommerce terms/products by Ecwid ID (stored in meta) or SKU—no duplicates on re-sync.

This plugin is ideal for store owners looking to move their e-commerce operations to WooCommerce or keep a WooCommerce store in sync with an Ecwid catalog.

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
Yes, the plugin supports syncing Ecwid product options and combinations as WooCommerce product attributes and variations. It will attempt to create global attributes in WooCommerce based on your Ecwid product options.

= What if my Ecwid categories have parent-child relationships? =
The plugin attempts to replicate the category hierarchy during the sync. A "Fix Category Hierarchy" tool is also provided on the Category Sync page to resolve potential ordering issues after an initial sync, especially if parent categories were imported after their children in some batches.

= Where do I get my Ecwid API credentials? =
You can find your Store ID and generate an API Secret Token in your Ecwid control panel. Typically, this is under a section like "Apps" > "API" or "Platform" > "API Keys". Please refer to Ecwid's documentation for the most current instructions.

= How does the "Fix Category Hierarchy" tool work? =
During category sync, if a parent category hasn't been imported yet when a child category is processed, the child might temporarily become a top-level category. The "Fix Category Hierarchy" tool re-evaluates these relationships once all categories are imported and attempts to set the correct parent-child links.

== Screenshots ==

1.  The Settings Page: Configure your Ecwid API credentials here.
2.  The Full Sync Page: Interface for running a complete category and product synchronization.
3.  The Category Sync Page: Dedicated interface for syncing only categories and fixing hierarchy.
4.  The Selective Product Sync Page: Load and choose specific products to import or update.
5.  Sync In Progress: Example of the progress bar and live log during a sync operation.

(Note: You will need to create these screenshots and name them `screenshot-1.png`, `screenshot-2.png`, etc., and place them in the `assets` folder of your SVN repository once your plugin is approved.)

== Changelog ==

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

= 2.0.0 =
This major update fixes critical issues with the Full Sync preview page, increases variation batch size for faster syncing, shows more comprehensive previews, and optimizes memory usage across all sync operations. Upgrade recommended for all users.

= 1.9.2 =
This version includes important internationalization fixes, security enhancements, and UI improvements. Please update for improved stability and future compatibility. It is recommended to review your settings after updating.

== Support ==

For support, please use the plugin's support forum on WordPress.org. (This is the standard practice. If you offer premium support elsewhere, you can mention it, but the primary support channel for .org plugins is their forum.)
You can also find more information at https://metrotechs.io.

```

---

**3. `languages/ecwid2woo-product-sync.pot` (Translation Template)**

Creating a full `.pot` file by hand is tedious. It's best to use a tool.
**Recommendation:** Use the `wp i18n make-pot` command via WP-CLI, or a desktop tool like Poedit to scan your plugin files and generate this file.

Here's a very basic structure. A real one would list every translatable string.

````text
// filepath: ecwid2woo-product-sync/languages/ecwid2woo-product-sync.pot
# Copyright (C) 2025 Metrotechs
# This file is distributed under the GPLv2 or later.
msgid ""
msgstr ""
"Project-Id-Version: Ecwid2Woo Product Sync 1.9.2\n"
"Report-Msgid-Bugs-To: https://metrotechs.io/support\n"
"POT-Creation-Date: 2025-05-14 10:00:00+00:00\n"
"MIME-Version: 1.0\n"
"Content-Type: text/plain; charset=UTF-8\n"
"Content-Transfer-Encoding: 8bit\n"
"PO-Revision-Date: YEAR-MO-DA HO:MI+ZONE\n"
"Last-Translator: FULL NAME <EMAIL@ADDRESS>\n"
"Language-Team: LANGUAGE <LL@li.org>\n"
"Language: \n"
"Plural-Forms: nplurals=INTEGER; plural=EXPRESSION;\n"

#: ecwid-to-woocommerce-sync.php:NN
msgid "Ecwid Placeholders"
msgstr ""

#: ecwid-to-woocommerce-sync.php:NN
msgid "Ecwid Placeholder"
msgstr ""

#: ecwid-to-woocommerce-sync.php:NN
msgid "Ecwid2Woo Product Sync Settings"
msgstr ""

#: ecwid-to-woocommerce-sync.php:NN
msgid "Ecwid2Woo Sync"
msgstr ""

# ... and so on for every translatable string ...

#: ecwid-to-woocommerce-sync.php:NN
msgid "Ecwid API Credentials"
msgstr ""

#: ecwid-to-woocommerce-sync.php:NN
msgid "Ecwid Store ID"
msgstr ""

#: ecwid-to-woocommerce-sync.php:NN
msgid "Enter your Ecwid Store ID."
msgstr ""

#: ecwid-to-woocommerce-sync.php:NN
msgid "Ecwid API Token (Secret Token)"
msgstr ""

#: ecwid-to-woocommerce-sync.php:NN
msgid "Your Ecwid API Secret Token. This is sensitive information."
msgstr ""

#: ecwid-to-woocommerce-sync.php:NN
msgid "Save Settings"
msgstr ""

# --- Strings from admin-sync.js (via wp_localize_script) ---
#: ecwid-to-woocommerce-sync.php:NN (line where wp_localize_script is)
msgid "Sync starting..."
msgstr ""

#: ecwid-to-woocommerce-sync.php:NN
msgid "Sync Complete!"
msgstr ""

# ... etc.
````