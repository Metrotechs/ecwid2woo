# Ecwid2Woo Product Sync

Synchronize your Ecwid store’s categories and products into WooCommerce, complete with SKUs, descriptions, prices, stock levels, images, and variations.

## Key Features

- **Two-Phase Full Sync**  
  1. Import all Ecwid categories (with parent/child relationships).  
  2. Import all enabled Ecwid products (simple & variable).

- **Category-Only Sync**  
  A dedicated “Category Sync” tab to import or update categories without touching products—including a “Fix Category Hierarchy” helper.

- **Selective Product Sync**  
  The “Product Sync” tab lets you load your Ecwid catalog, pick individual items, and import/update just those products.

- **Full Product Data**  
  - Names, SKUs, long & short descriptions  
  - Regular & sale prices (uses Ecwid’s “Compare-To” price)  
  - Stock quantity & management status  
  - Weight, dimensions, publish status  
  - Featured and gallery images (avoids re-downloading unchanged files)

- **Variable Products & Attributes**  
  Translates Ecwid options/combinations into WooCommerce global attributes, terms, and variations with per-variation SKU, price, stock, and weight.

- **Batch Processing & AJAX UI**  
  All sync operations run in batches via AJAX to prevent timeouts, with progress bars & live logs for clear feedback.

- **Idempotent Updates**  
  Matches existing WooCommerce terms/products by Ecwid ID (stored in meta) or SKU—no duplicates on re-sync.

## Requirements

- WordPress 5.0+  
- WooCommerce 3.0+  
- PHP 7.2+ with cURL extension  
- Valid Ecwid Store ID & Secret Token

## Installation

1. Download the ZIP from GitHub.  
2. In WP Admin → **Plugins → Add New** → **Upload Plugin**, choose the ZIP → **Install Now** → **Activate**.  
   _Or manually unzip into `/wp-content/plugins/ecwid2woo-product-sync/` and activate._

## Usage

After activation you’ll see a top-level menu in WooCommerce admin:

**Ecwid2Woo Product Sync**

### 1. Sync Settings

1. Go to **Ecwid2Woo Product Sync → Settings**.  
2. Enter your Ecwid **Store ID** and **API Secret Token**.  
3. Save Changes.

### 2. Full Sync

1. Navigate to **Ecwid2Woo Product Sync → Full Sync**.  
2. Click **Start Full Sync**.  
3. Watch the progress bar and log for category import, then product import.

### 3. Category Sync

1. Open **Ecwid2Woo Product Sync → Category Sync**.  
2. Click **Start Category Sync** to import/update only categories.  
3. If any parents were missing in the initial pass, use **Fix Category Hierarchy** to re-assign or create placeholders.

### 4. Product Sync

1. Select **Ecwid2Woo Product Sync → Product Sync**.  
2. Click **Load Ecwid Products for Selection**.  
3. Choose one or more products from the list.  
4. Click **Import Selected Products**—only those items will be fetched and synced.

---

_For detailed troubleshooting, enable WP_DEBUG_LOG to capture server-side errors in `wp-content/debug.log`. Logs appear live in each sync tab’s log panel._
