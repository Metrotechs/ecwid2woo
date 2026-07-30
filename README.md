# Metrotechs E2W Sync - Complete Ecwid to WooCommerce Product Migration Suite 🚀

**Professional-grade WordPress plugin for seamless Ecwid to WooCommerce migration with self-optimizing batch processing, Smart Skip Technology, and advanced image preservation**

Transform your e-commerce presence with the most advanced, reliable, and feature-complete Ecwid to WooCommerce product synchronization plugin available. Built by industry experts with enterprise-level architecture featuring **self-healing adaptive batch sizing** that automatically optimizes for your server's capabilities - no configuration required.

[![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue.svg)](https://wordpress.org)
[![WooCommerce](https://img.shields.io/badge/WooCommerce-3.0%2B-purple.svg)](https://woocommerce.com)
[![PHP](https://img.shields.io/badge/PHP-7.2%2B-777BB4.svg)](https://php.net)
[![License](https://img.shields.io/badge/License-GPL%20v2-green.svg)](LICENSE)

---

## 🆕 What's New in v1.6.1

Version 1.6.1 hardens long-running catalog migrations and refreshes every primary admin workflow:

- **Safe Full Sync Scope** – Full Sync preview and processing now consistently include enabled Ecwid products only
- **Concurrent Sync Protection** – An atomic, owner-checked catalog lock prevents overlapping mutations across tabs and administrators, with stale-lock recovery
- **Deterministic Smart Skip** – Product payload fingerprints replace the previous 24-hour timestamp guess and are saved only after images and variations finish successfully
- **Deadline-Safe Continuation** – Request-wide deadlines include Ecwid API retry time and return exact continuation offsets before the web request times out
- **Safer Image Imports** – Remote images use WordPress's safe HTTP client with host, response, file-size, and image validation
- **Resource-Aware Variations** – Low-resource servers use batches of up to 10 variations, while valid manual variation overrides are honored
- **Redesigned Admin Experience** – Settings and all sync pages now use responsive dashboards, clearer progress context, searchable/filterable activity, and improved controls
- **Reproducible Releases** – The release script packages an explicit production allowlist and writes a SHA-256 checksum next to the ZIP

See [Version History](#version-history) for the complete release summary.

---

## 🌟 Why Choose Metrotechs E2W Sync?

**The ONLY plugin that handles complete e-commerce catalog migration with enterprise recovery** – Successfully tested with stores containing 6,974 products across 70+ API calls with advanced pagination and intelligent memory management.

### 🎯 **Perfect For:**
- **Large Store Migrations** – Handles 4500+ products with Smart Skip recovery
- **Complete Catalog Migration** – Products and categories in one solution
- **E-commerce Agencies** – Reliable client migrations with professional tools
- **Store Owners** – Seamless platform switching without data loss
- **Developers** – Enterprise-grade architecture with comprehensive debugging
- **Enterprise Catalogs** – Handles thousands of products with interruption recovery

---

## ✨ Key Features

### 🧠 Smart Skip Technology (NEW)

- **Products & Categories** – Smart Skip works on both product and category syncs
- **Migration Recovery** – Resume interrupted migrations without re-processing existing items
- **Payload Fingerprints** – Products skip only when the complete normalized Ecwid payload matches the last successful import
- **Conservative Migration** – Existing products without a fingerprint are processed once to establish reliable tracking
- **Failure Safety** – Fingerprints are recorded only after product images and variations finish successfully
- **Debug Visibility** – Comprehensive logging of skip decisions and timestamp analysis
- **Enterprise Scale** – Tested with 4500+ product migrations

### ⚡ Self-Optimizing Batch Processing (v1.5.0) + Manual Override (v1.6.0)

**Enterprise-grade bidirectional adaptive sizing** – Unlike typical plugins with fixed batch sizes, E2W Sync automatically adjusts to your server's real-time capabilities. No configuration needed – but power users can override if they choose.

- **Manual Batch Size Override (v1.6.0)** – Advanced Batch Settings in Settings page lets you set Products, Categories, and Variations batch sizes manually (1–100). Proceeds at your own risk with a persistent warning banner on all sync pages.
- **Real-Time Logging** – All sync pages show detailed logs as each batch processes (not just at the end)
- **Bidirectional Batch Sizing** – Batch size decreases on timeouts AND recovers after stable performance
- **Self-Healing Recovery** – After 5 successful batches, size increases by 50% (e.g., 50→75→100)
- **Zero Configuration** – Works optimally on shared hosting AND high-end VPS without manual tuning
- **Conservative Defaults** – Products start at 5-20/batch, categories at 10-50/batch based on server tier
- **Batch Size Display** – Current batch size shown in status: "Syncing Products: 50/1000 [batch size: 75]"
- **Sync Page Parity** – Full Sync, Product Sync, and Category Sync all use identical adaptive logic
- **Progressive Cooldowns** – Server recovery waits increase with consecutive timeouts (5s→10s→30s)
- **Memory-Aware** – PHP automatically reduces batch size if memory is low (<128MB free)
- **5 Retry Attempts** – Up to 5 retries with batch size reduction before giving up

**Example Recovery Path:** `100 → [timeout] → 50 → [5 successes] → 75 → [5 successes] → 100 ✓`

### 🛑 Stop Sync Control (v1.4.0)

- **Immediate Cancellation** – Stop any running bulk import operation instantly
- **User Control** – Cancel long-running operations without page refresh
- **Smart UI** – Stop buttons appear only during active imports
- **Confirmation Dialogs** – Prevent accidental cancellation of important operations
- **AJAX Abortion** – Category imports stop immediately via request cancellation
- **Batch Interruption** – Product imports stop cleanly between batches
- **Universal Coverage** – Available on both Product and Category sync pages

### 🔧 Enhanced Performance & Reliability (v1.3.8-1.4.8)

- **Auto Server Detection** – Automatically detects server resources and configures optimal batch sizes
- **Server Tier System** – 🚀 High (2GB+/VPS), ⚡ Medium (512MB-2GB), 🐢 Low (<512MB) with conservative defaults
- **Server Crash Recovery** – Handles HTTP 503 and Cloudflare 520-530 errors with 30-120s cooldown periods
- **Fast Skip Optimization** – Pre-loads existing Ecwid IDs in single query for 100x faster duplicate detection
- **Intelligent Timeout Recovery** – Detects Cloudflare 524, Gateway 504, Request 408, and jQuery timeouts
- **Exponential Backoff Retry** – Smart retry logic with increasing delays (3s→6s→12s) for server recovery
- **Batch Loading System** – Products, categories, and sync operations load in manageable batches
- **Dynamic Delays** – Delay between batches auto-adjusted (3-8s) based on server capability
- **Progress Tracking** – Real-time progress bars showing current batch and total progress
- **Server Compatibility** – Works on shared hosting, VPS, and dedicated servers

### 🔄 Complete Migration Suite

- **Full Sync** – Complete catalog migration with Smart Skip capabilities
- **Category Sync** – Independent category import with hierarchy management
- **Product Sync** – Selective and bulk product import with variations
- **Batch Processing** – Smart chunking prevents server timeouts on large catalogs

###  Complete Data Synchronization

- **Product Information** – Names, SKUs, descriptions, prices, stock levels, dimensions, weight
- **Category Hierarchies** – Full parent-child relationships preserved
- **Product Variations** – Complete support for variable products with all option combinations
- **Image Management** – Featured images, galleries, and variation-specific images
- **Inventory Data** – Stock status, quantities, and unlimited stock settings
- **Category Thumbnails** – Imports category images and attaches them to WooCommerce terms
- **Currency Synchronization** – Automatically updates WooCommerce to match your Ecwid store currency

### ⚡ Advanced Technical Features

- **AJAX-Powered Processing** – Non-blocking operations with real-time feedback
- **Memory Optimization** – Efficient handling of large product catalogs
- **Smart Duplicate Prevention** – Uses Ecwid IDs and SKU matching to avoid duplicates
- **Auto-Recovery Systems** – Handles API timeouts and connection issues gracefully
- **Intelligent Attribute Handling** – Automatically handles WooCommerce's 28-character attribute slug limits
- **WordPress Standards Compliant** – Follows all WordPress coding and security best practices
- **Multilingual-Safe Slugs** – Robust transliteration and length-limited slug generation for non‑Latin/long names while preserving original display names

### 🛡️ Reliability & Safety

- **Stop Sync Control** – Immediate cancellation capability for all operations
- **Atomic Catalog Lock** – Prevents overlapping sync jobs and safely recovers abandoned locks
- **Exact Continuation Offsets** – Deadline-limited batches resume at the first unprocessed item
- **Validated Image Downloads** – Restricts remote image imports by host, response type, and size before sideloading
- **Comprehensive Error Handling** – Detailed error reporting and recovery mechanisms
- **Safe Re-syncing** – Idempotent operations prevent data corruption
- **Debug Integration** – Works seamlessly with WordPress debug logging

---

## 📋 Requirements

### Minimum Requirements
- **WordPress:** 5.0 or higher
- **WooCommerce:** 3.0 or higher
- **PHP:** 7.2 or higher (8.0+ recommended)
- **MySQL:** 5.6 or MariaDB 10.0
- **Ecwid Store:** Active store with API access

### Recommended Environment
- **WordPress:** 6.0+
- **WooCommerce:** 7.0+
- **PHP:** 8.0+
- **Server Memory:** 512MB+ (2GB+ recommended for large catalogs with images)
- **Reliable Internet:** Stable connection for API operations

---

## 🚀 Installation

### Via WordPress Admin (Recommended)
1. Navigate to **Plugins → Add New** in your WordPress admin
2. Click **Upload Plugin** and select the plugin ZIP file
3. Click **Install Now** and then **Activate**

### Manual Installation
1. Download and unzip the plugin
2. Upload the `metrotechs-e2w-sync` folder to `/wp-content/plugins/`
3. Activate the plugin through the **Plugins** menu in WordPress

### Post-Installation Setup
1. Go to **E2W Sync → Settings** in your admin menu
2. Enter your Ecwid **Store ID** and **API Token**
3. Click **Save Settings** and test your connection
4. You're ready to start syncing!

---

## 🔧 Getting Your Ecwid API Credentials

### Step-by-Step Guide

1. **Access Ecwid Control Panel**
   - Log into your Ecwid account
   - Navigate to **Apps → My Apps → API**

2. **Locate Your Store ID**
   - Your Store ID is displayed at the top of the API page
   - Copy this number (it's typically 8-9 digits)

3. **Generate API Token**
   - Click **Create new token** or **Generate Token**
   - Note: Both Public and Secret tokens work – what matters are the permissions
   - Ensure the token has these permissions:
     - ✅ Read catalog
     - ✅ Read store profile
     - ✅ Read products
     - ✅ Read categories
   - Copy the generated token immediately (it won't be shown again)

4. **Enter Credentials**
   - Paste both values into the plugin settings
   - Click **Save Settings** and **Test Connection**

---

## 📖 Usage Guide

### 🏠 Settings Page

**Location:** `E2W Sync → Settings`

- Configure your Ecwid API credentials
- Test connection with visual feedback
- **Advanced Batch Settings (v1.6.0)** – Manually override auto-detected batch sizes for Products, Categories, and Variations. Enable the override checkbox, set your sizes (1–100), and save. A warning banner will appear on all sync pages. Use at your own risk.
- Review connection, server tier, memory, and recommended batch context in the responsive status dashboard
- Access quick navigation to all sync options
- Monitor connection status with real-time indicators

### 🔄 Full Sync

**Location:** `E2W Sync → Full Sync`

**Perfect for:** Complete store migrations or comprehensive updates

#### Features:
- **Automatic Preview** – See exactly what will be synced before starting
- **Enabled Products Only** – Preview and processing use the same enabled Ecwid product scope
- **Two-Phase Process** – Categories first, then products
- **Real-time Progress** – Visual progress tracking with detailed logs
- **Activity Controls** – Filter, search, clear, or download the activity log
- **Safe Continuation** – Request deadlines preserve the exact category or product offset for the next batch
- **Sync Ownership** – A catalog lock prevents a second tab or administrator from starting a conflicting job
- **Stop Control** – Cancel operation at any time
- **Smart Batching** – Processes data in optimized chunks

#### How to Use:
1. Page automatically loads preview on visit
2. Review categories and products to be synced
3. Click **Start Full Sync** to begin
4. Monitor progress and logs in real-time
5. Use **STOP SYNC** if needed

### 📁 Category Sync

**Location:** `E2W Sync → Category Sync`

**Perfect for:** Setting up category structure before product import

#### Features:
- **Batch Loading with Retry** – Categories load in batches with automatic retry on 500/524 errors (v1.4.3)
- **Enhanced UI Design** – Professional interface with bordered information panels and status messages
- **Stop Sync Control** – Cancel bulk category imports instantly with confirmation dialog
- **Automatic Category Loading** – Categories load immediately when page opens with "📁 Category Loading Complete" confirmation
- **Enhanced Debug Information** – Shows API call count, total categories loaded, and detailed performance metrics
- **Selective Import** – Choose individual categories to import instead of all at once
- **Visual Selection Interface** – Enhanced checkbox design with alternating row colors and clear visual hierarchy
- **Smart Selection Controls** – "Select All/None" with indeterminate state and dynamic button text
- **Category Information Display** – Shows ID, Parent ID, and visual indicators for root vs subcategories
- **Increased Timeout Handling** – 120-second timeout for large category lists
- **Comprehensive Logging** – Debug logging shows each API call and category loading progress
- **Hierarchy Preservation** – Maintains parent-child relationships
- **Hierarchy Fix Tool** – Resolves any structural issues
- **Independent Operation** – Sync categories without touching products

#### How to Use:
1. Categories automatically load when visiting the page with enhanced status display
2. Click **Reload Categories** to refresh if needed
3. Review the enhanced category list with visual indicators
4. Select individual categories using the improved checkbox interface
5. Use **Select All/None** for bulk selection with smart state indication
6. Click **Import Selected Categories** (text dynamically updates based on selection count)
7. Monitor progress with enhanced status messages and logging
8. Use **Fix Category Hierarchy** if needed after sync

### 🎯 Selective Product Sync

**Location:** `E2W Sync → Product Sync`

**Perfect for:** Targeted imports, testing, or specific product updates

#### Features:
- **Stop Sync Control** – Cancel long-running import operations immediately with dedicated Stop Sync button
- **Bulk Import All Products** – Import entire product catalog with automatic batch processing (v1.4.0)
- **Complete Product Loading** – Handles stores with 6000+ products (tested with 6,974 products)
- **Advanced Pagination** – Makes 70+ API calls to load entire product catalog
- **Enabled/Disabled Toggle** – Separate views for enabled and disabled products
- **Real-time Metrics** – Shows API call count, total products loaded, and performance data
- **Professional Status Panel** – Comprehensive loading statistics and progress feedback
- **Automatic Loading** – All products load immediately when page opens
- **Timeout Prevention** – Ultra-small batch sizes (5 products) prevent server timeouts
- **Visual Selection Interface** – Enhanced checkbox design with detailed product information

#### Workflow:
1. **For Selective Import:** Review the product list, select individual products using checkboxes, and click **Import Selected Products**
2. **For Bulk Import:** Click **Import All Products** to automatically import your entire catalog in batches
3. **Monitor Progress:** Watch real-time progress updates and detailed status messages
4. **Stop if Needed:** Use the **Stop Sync** button to cancel long-running operations immediately
5. **Timeout Protection:** Operations automatically use small batch sizes to prevent server timeouts

###  Placeholders Management

**Location:** `E2W Sync → Placeholders`

**Purpose:** Review and manage temporary placeholder items created during sync

- View placeholder categories created for missing parents
- Clean up temporary items after hierarchy fixes
- Monitor sync-related administrative data

---

## 🔧 Advanced Features

### 🔄 Variation Processing

- **Automatic Attribute Creation** – Missing WooCommerce attributes auto-generated with intelligent slug handling
- **Smart Slug Management** – Automatically truncates long attribute names to comply with WooCommerce's 28-character limit
- **Smart Combination Mapping** – Ecwid options become WooCommerce variations
- **Batch Processing** – Large variation sets processed in optimized chunks
- **Variation-Specific Data** – Individual SKUs, prices, stock, and images

### 🛠️ Error Handling

- **Graceful Degradation** – Continues processing even if individual items fail
- **Detailed Error Reporting** – Clear explanations of any issues encountered
- **Automatic Recovery** – Handles temporary API issues and timeouts
- **Debug Integration** – Works with WordPress WP_DEBUG for troubleshooting

### 🎨 User Experience

- **Responsive Design** – Works perfectly on desktop, tablet, and mobile
- **Visual Feedback** – Loading animations, progress indicators, and status messages
- **Intuitive Navigation** – Clear pathways between different sync options
- **Accessibility** – Follows WordPress accessibility guidelines

---

## 🚨 Important Notes

### Before First Sync

- **Backup Your Database** – Always backup before running large operations
- **Test Connection** – Verify API credentials work correctly
- **Review Preview Data** – Check what will be synced before starting
- **Consider Staging** – Test on staging site first for large catalogs

### Performance Tips

- **Run Category Sync First** – Establishes proper structure for products
- **Use Selective Sync** – For testing or specific updates
- **Monitor Server Resources** – Watch memory usage during large operations
- **Schedule Large Syncs** – Run during low-traffic periods

### Troubleshooting Common Issues

#### "Connection Failed" Error
1. Verify Store ID is correct (8-9 digits)
2. Check API token has required permissions
3. Ensure store is active and accessible
4. Test API connection in Ecwid admin

#### "HTTP 403" Error
**Most Common Issue**: Missing API permissions

**Quick Fix:**
1. Go to Ecwid Dashboard → Apps → My Apps → API
2. Create new API token with required permissions:
   - ✅ Read catalog
   - ✅ Read store profile
   - ✅ Read products
   - ✅ Read categories
3. Update plugin settings with new token
4. Test connection - should show "Connection successful!"

#### "Memory Limit" Errors
1. Increase PHP memory limit (512MB+ recommended)
2. Use selective sync for large catalogs
3. Run category sync separately first
4. Enable debug logging to identify bottlenecks

#### Products Not Appearing
1. Check if products are enabled in Ecwid
2. Verify WooCommerce is properly configured
3. Use "Fix Category Hierarchy" if needed
4. Check for duplicate SKUs

---

## 🔍 Technical Specifications

### API Integration
- **Ecwid REST API v3** – Latest version for optimal performance
- **Rate Limiting Compliance** – Respects Ecwid's API rate limits
- **Automatic Retries** – Handles temporary connection issues
- **Bulk Operations** – Efficient batch processing for large datasets

### WordPress Integration
- **Native WP Hooks** – Uses WordPress standards throughout
- **WooCommerce Compatibility** – Works with all major WooCommerce versions
- **Multisite Support** – Compatible with WordPress multisite networks
- **Translation Ready** – Prepared for internationalization

### Security Features (v1.6.1 Enhanced)
- **Sanitized Inputs** – All data sanitized and validated across all sync pages
- **Output Escaping** – XSS prevention enforced throughout the admin interface
- **Nonce Protection** – CSRF protection strengthened on all AJAX endpoints and admin forms
- **Capability Checks** – Permission verification enforced consistently throughout the plugin
- **Secure Uninstall** – Uninstall routine secured against unauthorized execution
- **Server-Side API Handling** – The Ecwid token remains server-side and is not exposed in localized admin JavaScript
- **Safe Remote Downloads** – Image requests use WordPress SSRF protections plus host, content, and size validation
- **Owner-Checked Sync Lock** – Only the active job owner can mutate or release the catalog lock
- **Idempotent Operations** – Safe re-syncing with duplicate attribute prevention
- **Variation Price Fallback** – Intelligent price handling for variation imports

---

## 📊 Performance Benchmarks

### Real-World Performance
- **⚡ Processing Speed** – Up to 1,000 products per hour (including variations)
- **✅ Complete Migration Tested** – 6,974 products processed successfully
- **✅ 70+ API Calls** – Efficient pagination handling for large catalogs
- **✅ Memory Optimized** – Processes large datasets without memory issues
- **✅ Zero Timeouts** – Robust error handling and recovery

### Typical Performance
- **Small Store (1-100 items)** – 2-5 minutes
- **Medium Store (100-1000 items)** – 10-20 minutes  
- **Large Store (1000+ items)** – 20-60 minutes depending on variations
- **Extra Large Store (6000+ items)** – 1-6 hours depending on server specifications

### Feature Performance
- **Full Sync** – Complete catalog import with Smart Skip optimization
- **Category Sync** – Handles complex hierarchies efficiently
- **Product Sync** – Processes variations and images with precision
- **Enhanced UI** – Instant loading with professional progress indicators

---

## 🎨 Enhanced Admin Interface

### Professional Design
- **Colorful Gradient Buttons** – Modern, visually appealing navigation
- **Responsive Grid Layout** – Perfect display on all devices
- **Consistent Styling** – Professional look across all sync pages
- **Real-time Status** – Live progress indicators and success/error states

### Navigation Improvements
- **Quick Actions Dashboard** – Main sync options accessible from settings
- **Breadcrumb Navigation** – Easy movement between sync pages
- **Visual Hierarchy** – Clear distinction between different sync types
- **Enhanced Typography** – Professional fonts and spacing

### User Experience
- **Auto-loading Data** – Product and category data loads automatically
- **Smart Progress Tracking** – Real-time updates with detailed logging
- **Error Prevention** – Clear warnings and confirmations for destructive actions
- **Accessibility Focus** – Screen reader friendly and keyboard navigable

---

## 🆘 Support & Documentation

### Getting Help
- **Documentation:** This README file
- **Debug Logging:** Enable WordPress debug mode for detailed logs
- **Error Messages:** Clear, actionable error descriptions with troubleshooting steps
- **Community Support:** GitHub Issues for bug reports and feature requests

### Best Practices
1. **Always test on staging first**
2. **Backup your database before major syncs**
3. **Run syncs during low-traffic periods**
4. **Monitor server resources during operations**
5. **Use selective sync for testing new products**

---

## 📈 Roadmap

### Upcoming Features
- **Real-time Sync** – Webhook-based automatic updates
- **Incremental Sync** – Update only changed products
- **Advanced Filtering** – Enhanced filtering options for all sync types
- **Scheduled Sync** – Automated synchronization at specified intervals
- **Multi-currency Support** – Enhanced currency handling and conversion
- **Import/Export Settings** – Backup and restore plugin configurations

### Version History
- **v1.6.1** (2026-07-28) – **PRODUCTION HARDENING**
  - Product payload fingerprints replace the unsafe 24-hour skip heuristic
  - Atomic job locks prevent concurrent catalog mutations across tabs and administrators
  - Request-wide deadlines include Ecwid API retry time and preserve exact continuation offsets
  - Store currency sync runs once per full sync instead of once per batch
  - Low-resource servers use 10-variation batches; manual variation overrides are now honored
  - Admin output escaping, capability checks, image download validation, and public-request loading are hardened
  - Settings, Full Sync, Category Sync, and Product Sync now use the redesigned responsive dashboard interface
  - Duplicate category-import handling was removed and admin assets now use file-based cache busting
  - Production ZIPs are built from an explicit allowlist with a SHA-256 checksum
- **v1.6.0** (2026-02-23) – **SHARED HOSTING PERFORMANCE & MANUAL BATCH CONTROL**
  - 🎛️ **Manual Batch Size Override** – New "Advanced Batch Settings" in Settings page for manual control of Products, Categories, and Variations batch sizes (1–100)
  - ⚠️ **Risk Warning Banner** – Persistent warning displayed on all sync pages when manual override is active, with direct link to settings
  - 🔒 **Safety Cap** – Manual batch sizes hard-capped at 100; adaptive timeout recovery remains active as safety net
  - 🛡️ **CPU Yield After Image Sideload** – 250ms breathing room after thumbnail generation prevents server overload
  - 🎯 **Targeted Cache Clearing** – Replaced full cache flushes with surgical per-product cache invalidation
  - ⏱️ **PHP Worker Protection** – Capped set_time_limit to 300s to prevent indefinitely locked workers
  - 💾 **Cache Suspension During Imports** – Suspended object cache additions during batch loops to reduce memory/CPU waste
  - 🔥 **HTTP 503 Recovery** – 503 Service Unavailable now triggers full server-down recovery with cooldown and batch reduction
  - 📊 **Conservative Server Tiers** – Raised tier thresholds (Low <512MB, Medium 512MB-2GB, High 2GB+/VPS) to prevent CPU overload on shared hosting
  - 📉 **Reduced Batch Sizes** – Products default to 5-20/batch (was 10-50), categories to 10-50/batch (was 15-100), with longer delays between batches
- **v1.5.2** (2026-02-22) – **VARIATION & ATTRIBUTE FIXES**
  - 🎯 **Default Displayed Price on Variations** – Fallback to `defaultDisplayedPrice` field when price is missing
  - ✅ **Skip Duplicate Attributes on Re-import** – Idempotent attribute creation prevents duplicates during re-syncs
- **v1.5.1** (2026-02-21) – **SECURITY HARDENING**
  - Input sanitization improvements across all sync pages
  - Output escaping hardened to prevent XSS vulnerabilities
  - Nonce verification strengthened on all AJAX endpoints
  - Capability checks enforced consistently throughout the plugin
  - Uninstall routine secured against unauthorized execution
- **v1.5.0** (2025-12-18) – **SELF-OPTIMIZING BATCH PROCESSING**
  - 🔄 **Bidirectional Batch Sizing** – Decreases on timeouts AND recovers after stable performance
  - 📈 **Self-Healing Recovery** – Batch size increases by 50% after 5 consecutive successes (e.g., 10→15→20)
  - ⚡ **Real-Time Logging** – All sync pages show detailed logs as each batch processes
  - 📊 **Batch Size Display** – Current batch size shown in status: "[batch size: 75]"
  - 🎯 **Zero Configuration** – Works optimally on shared hosting AND high-end VPS
  - 📦 **Auto-Tuned Batch Defaults** – Batch sizes auto-configured per server tier
  - 🎯 **Sync Page Parity** – Full Sync, Product Sync, Category Sync all use identical adaptive logic
  - ⏳ **Progressive Cooldowns** – Server recovery waits increase with timeouts (5s→10s→30s)
  - 💾 **Memory-Aware Batching** – PHP auto-reduces batch size if memory low (<128MB free)
  - 🏗️ **Parent-First Sorting** – Categories sorted so parents process before children
  - 🌳 **Topological Sort** – Breadth-first traversal ensures parent categories exist first
  - 🔗 **Auto-Fix Orphaned Children** – Orphans re-parented when parent is imported
  - 📝 **Next Step Messaging** – Shows what sync step is coming next
  - 🔍 **Variation SKU Validation** – Properly identifies variations from same product
  - 🐛 **Category Nesting Fix** – Deep nested categories now import correctly
- **v1.4.0** (2025-12-16) – **SERVER INTELLIGENCE & PAUSE/RESUME**
  - ⏸️ **Pause/Resume Sync** – Pause and resume from exact position including variation queue
  - 💻 **Auto Server Detection** – Detects server resources and adjusts batch sizes
  - 🔥 **Server Down Recovery** – Handles Cloudflare 520-530 errors with extended cooldowns
  - 🚀 **Fast Skip Optimization** – 100x faster duplicate detection with single SQL query
  - 🎯 **Server Tier System** – 🚀 High (2GB+), ⚡ Medium (512MB-2GB), 🐢 Low (<512MB) with conservative defaults
  - 🔍 **System Diagnostics** – Memory, disk space, WooCommerce stats panel
- **v1.3.0** (2025-09-02) – **STOP SYNC & STABILITY**
  - 🛑 **Stop Sync Button** – Cancel running product and category imports instantly
  - 📦 **Bulk Import Fix** – Batch processing handles 6756+ products
  - 🖼️ **Gallery Image Fix** – Fixed with correct API field mapping
  - 🏷️ **Plugin Renamed** – "Metrotechs E2W Sync" for WordPress compliance
- **v1.2.0** (2025-09-01) – **SMART SKIP TECHNOLOGY**
  - 🧠 **Smart Skip Technology** – 70-90% time savings when restarting large migrations
  - 🛡️ **Image Preservation** – Existing product images preserved during re-import
  - 🎨 **Enhanced UI** – Professional interface improvements
- **v1.1.0** (2025-08-30) – **COMPLETE PRODUCT LOADING**
  - 📦 **6000+ Products** – Handles stores with thousands of products
  - 🔄 **Pagination Engine** – Makes 70+ API calls to load entire catalog
  - ✅ **Tested at Scale** – 6,974 products across 70+ API calls
- **v1.0.5** (2025-07-22) – 500 error resolution, SKU conflict handling
- **v1.0.4** (2025-06-28) – Client-side pagination, browser freezing fix
- **v1.0.3** (2025-06-10) – UI fixes, memory leak fixes
- **v1.0.2** (2025-05-25) – Multilingual slugs, category thumbnails
- **v1.0.1** (2025-04-30) – Activation fixes, PHP 7.2+ compatibility
- **v1.0.0** (2025-04-15) – Initial release with core sync functionality

---

## 📄 License

This plugin is licensed under the **GPL v2 or later**.

```
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.
```

---

## 🤝 Contributing

We welcome contributions! Please feel free to submit issues, feature requests, or pull requests.

### Development Setup
1. Clone the repository
2. Set up a local WordPress/WooCommerce environment
3. Install the plugin in development mode
4. Enable WordPress debug logging
5. Run the source-level regression suites:

   ```powershell
   Get-ChildItem tests\*.test.js | ForEach-Object { node $_.FullName }
   ```

6. Validate PHP and JavaScript syntax:

   ```powershell
   Get-ChildItem -File *.php | ForEach-Object { php -l $_.FullName }
   Get-ChildItem assets\js\*.js, tests\*.test.js | ForEach-Object { node --check $_.FullName }
   git diff --check
   ```

7. Build a production ZIP and matching SHA-256 checksum:

   ```powershell
   .\scripts\build-release.ps1
   ```

   Release artifacts are written to `dist/`. The package excludes tests, scripts, Git metadata, and internal implementation notes.

---

## 🌟 Show Your Support

If this plugin has helped your business, please consider:
- ⭐ **Starring the repository**
- 🐛 **Reporting bugs and issues**
- 💡 **Suggesting new features**
- 📢 **Spreading the word**

---

**Built with ❤️ by Metrotechs for the WordPress community**

*Transform your e-commerce journey with confidence.*
