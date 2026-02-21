# Metrotechs E2W Sync - Complete Ecwid to WooCommerce Product Migration Suite 🚀

**Professional-grade WordPress plugin for seamless Ecwid to WooCommerce migration with self-optimizing batch processing, Smart Skip Technology, and advanced image preservation**

Transform your e-commerce presence with the most advanced, reliable, and feature-complete Ecwid to WooCommerce product synchronization plugin available. Built by industry experts with enterprise-level architecture featuring **self-healing adaptive batch sizing** that automatically optimizes for your server's capabilities - no configuration required.

[![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue.svg)](https://wordpress.org)
[![WooCommerce](https://img.shields.io/badge/WooCommerce-3.0%2B-purple.svg)](https://woocommerce.com)
[![PHP](https://img.shields.io/badge/PHP-7.2%2B-777BB4.svg)](https://php.net)
[![License](https://img.shields.io/badge/License-GPL%20v2-green.svg)](LICENSE)

---

## 🧠 **NEW: Smart Skip Technology + Self-Healing Batch Processing** 

Revolutionary migration system featuring **intelligent skip technology** for products AND categories, plus **self-optimizing batch processing** that automatically adapts to your server's capabilities. No configuration required – it just works.

### 🎯 **Smart Skip Benefits:**
- **Intelligent Resume** – Automatically continues from where migration left off
- **Products & Categories** – Smart Skip works on both product and category syncs
- **Image Preservation** – Existing product images are never deleted during updates
- **Timestamp Comparison** – Compares Ecwid update dates with local import records
- **70-90% Time Savings** – Skip already-imported items when restarting migrations
- **Legacy Support** – Handles items imported before Smart Skip implementation

### ⚡ **Self-Healing Batch Processing:**
- **Bidirectional Sizing** – Batch size decreases on timeouts AND recovers after stability
- **Auto-Recovery** – After 5 successful batches, size increases by 50% (50→75→100)
- **Zero Configuration** – Works optimally on shared hosting AND high-end VPS
- **Timeout Protection** – Automatically halves batch size on 524/504/408 errors
- **Progressive Cooldowns** – Server recovery waits increase with consecutive timeouts
- **Memory-Aware** – Reduces batch size automatically if memory is low

**Example:** `100 → [timeout] → 50 → [5 successes] → 75 → [5 successes] → 100 ✓`

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
- **Timestamp Intelligence** – Advanced comparison of Ecwid vs. local modification dates
- **Adaptive Processing** – Conservative 24-hour skip rule for recently imported products
- **Legacy Handling** – Automatic timestamp assignment for items imported before Smart Skip
- **Debug Visibility** – Comprehensive logging of skip decisions and timestamp analysis
- **Enterprise Scale** – Tested with 4500+ product migrations

### ⚡ Self-Optimizing Batch Processing (v1.5.0)

**Enterprise-grade bidirectional adaptive sizing** – Unlike typical plugins with fixed batch sizes, E2W Sync automatically adjusts to your server's real-time capabilities. No configuration needed.

- **Real-Time Logging** – All sync pages show detailed logs as each batch processes (not just at the end)
- **Bidirectional Batch Sizing** – Batch size decreases on timeouts AND recovers after stable performance
- **Self-Healing Recovery** – After 5 successful batches, size increases by 50% (e.g., 50→75→100)
- **Zero Configuration** – Works optimally on shared hosting AND high-end VPS without manual tuning
- **100 Items/Batch Default** – Products and Categories start at 100 items per batch for faster syncs
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
- **Server Tier System** – 🚀 High (512MB+), ⚡ Medium (256-512MB), 🐢 Low (<256MB) with auto-tuned settings
- **Server Crash Recovery** – Handles Cloudflare 520/521/522/523/525 errors with 30-120s cooldown periods
- **Fast Skip Optimization** – Pre-loads existing Ecwid IDs in single query for 100x faster duplicate detection
- **Intelligent Timeout Recovery** – Detects Cloudflare 524, Gateway 504, Request 408, and jQuery timeouts
- **Exponential Backoff Retry** – Smart retry logic with increasing delays (3s→6s→12s) for server recovery
- **Batch Loading System** – Products, categories, and sync operations load in manageable batches
- **Dynamic Delays** – Delay between batches auto-adjusted (2-5s) based on server capability
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
- **Server Memory:** 512MB+ for large catalogs
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
- Access quick navigation to all sync options
- Monitor connection status with real-time indicators

### 🔄 Full Sync

**Location:** `E2W Sync → Full Sync`

**Perfect for:** Complete store migrations or comprehensive updates

#### Features:
- **Automatic Preview** – See exactly what will be synced before starting
- **Two-Phase Process** – Categories first, then products
- **Real-time Progress** – Visual progress tracking with detailed logs
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

### Security Features
- **Sanitized Inputs** – All data properly sanitized and validated
- **Nonce Protection** – CSRF protection on all admin forms
- **Capability Checks** – Proper permission verification
- **Secure API Handling** – Encrypted credential storage

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
- **Advanced Filtering** – Sync based on categories, tags, or custom criteria
- **Scheduled Sync** – Automated synchronization at specified intervals
- **Multi-currency Support** – Enhanced currency handling and conversion

### Upcoming Features
- **Real-time Sync** – Webhook-based automatic updates
- **Incremental Sync** – Update only changed products
- **Advanced Filtering** – Enhanced filtering options for all sync types
- **Scheduled Sync** – Automated synchronization at specified intervals
- **Multi-currency Support** – Enhanced currency handling and conversion
- **Import/Export Settings** – Backup and restore plugin configurations

### Version History
- **v1.5.0** (2025-12-18) – **SELF-OPTIMIZING BATCH PROCESSING**
  - 🔄 **Bidirectional Batch Sizing** – Decreases on timeouts AND recovers after stable performance
  - 📈 **Self-Healing Recovery** – Batch size increases by 50% after 5 consecutive successes
  - ⚡ **Real-Time Logging** – All sync pages show detailed logs as each batch processes
  - 📊 **Batch Size Display** – Current batch size shown in status: "[batch size: 75]"
  - 🎯 **Zero Configuration** – Works optimally on shared hosting AND high-end VPS
  - 📦 **100 Items/Batch Default** – Products and Categories start at 100 per batch
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
  - 🎯 **Server Tier System** – 🚀 High, ⚡ Medium, 🐢 Low with auto-tuned settings
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
5. Follow WordPress coding standards

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
