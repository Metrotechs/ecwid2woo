# Metrotechs E2W Sync - Complete E-commerce Migration Suite 🚀

**Professional-grade WordPress plugin for seamless Ecwid to WooCommerce migration with revolutionary Smart Skip Technology and advanced image preservation**

Transform your e-commerce presence with the most advanced, reliable, and feature-complete Ecwid to WooCommerce synchronization plugin available. Built by industry experts with enterprise-level architecture, Smart Skip Technology for large migrations, bulletproof image preservation, and bulletproof reliability.

[![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue.svg)](https://wordpress.org)
[![WooCommerce](https://img.shields.io/badge/WooCommerce-3.0%2B-purple.svg)](https://woocommerce.com)
[![PHP](https://img.shields.io/badge/PHP-7.2%2B-777BB4.svg)](https://php.net)
[![License](https://img.shields.io/badge/License-GPL%20v2-green.svg)](LICENSE)

---

## 🧠 **NEW: Smart Skip Technology + Image Preservation** 

Revolutionary migration recovery system that intelligently skips already imported products AND preserves all existing images, making large store migrations (1000+ products) resume seamlessly from interruptions. **70-90% time savings** when restarting migrations with **complete image safety**.

### 🎯 **Smart Skip Benefits:**
- **Intelligent Resume** – Automatically continues from where migration left off
- **Image Preservation** – Existing product images are never deleted during updates
- **Timestamp Comparison** – Compares Ecwid update dates with local import records
- **Memory Optimization** – Adaptive batch sizing prevents server errors
- **Legacy Support** – Handles products imported before Smart Skip implementation

---

## 🌟 Why Choose Metrotechs E2W Sync?

**The ONLY plugin that handles complete e-commerce migration with enterprise recovery** – Successfully tested with stores containing 6,974 products across 70+ API calls with advanced pagination, intelligent memory management, plus full customer and order migration capabilities.

### 🎯 **Perfect For:**
- **Large Store Migrations** – Handles 4500+ products with Smart Skip recovery
- **Complete Store Migration** – Products, customers, and orders in one solution
- **E-commerce Agencies** – Reliable client migrations with professional tools
- **Store Owners** – Seamless platform switching without data loss
- **Developers** – Enterprise-grade architecture with comprehensive debugging
- **Enterprise Catalogs** – Handles thousands of products, customers, and orders with interruption recovery

---

## ✨ Key Features

### 🧠 Smart Skip Technology (NEW)

- **Migration Recovery** – Resume interrupted migrations without re-processing existing products
- **Timestamp Intelligence** – Advanced comparison of Ecwid vs. local modification dates
- **Adaptive Processing** – Conservative 24-hour skip rule for recently imported products
- **Legacy Handling** – Automatic timestamp assignment for products imported before Smart Skip
- **Debug Visibility** – Comprehensive logging of skip decisions and timestamp analysis
- **Enterprise Scale** – Tested with 4500+ product migrations

### 🔄 Complete Migration Suite

- **Full Sync** – Complete catalog migration with Smart Skip capabilities
- **Category Sync** – Independent category import with hierarchy management
- **Product Sync** – Selective and bulk product import with variations
- **Customer Sync** – Import customer accounts with billing/shipping information
- **Order Sync** – Import orders with automatic customer association
- **Batch Processing** – Smart chunking prevents server timeouts on large catalogs

### 👥 Customer & Order Management

- **Customer Import** – Full customer profiles with contact information
- **Customer Association** – Smart matching of orders to customer accounts
- **Order History** – Complete order data with status and payment information
- **Address Management** – Billing and shipping addresses preserved
- **Order Filtering** – Filter by status, date range, and payment status

### 📊 Complete Data Synchronization

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
     - ✅ **Read customers** (required for Customer Sync)
     - ✅ **Read orders** (required for Order Sync)
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
- **Enhanced UI Design** – Professional interface with bordered information panels and status messages
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
- **Complete Product Loading** – Handles stores with 6000+ products (tested with 6,974 products)
- **Advanced Pagination** – Makes 70+ API calls to load entire product catalog
- **Enabled/Disabled Toggle** – Separate views for enabled and disabled products
- **Real-time Metrics** – Shows API call count, total products loaded, and performance data
- **Professional Status Panel** – Comprehensive loading statistics and progress feedback
- **Automatic Loading** – All products load immediately when page opens

### 👥 Customer Sync

**Location:** `E2W Sync → Customer Sync`

**Perfect for:** Importing customer accounts and contact information

#### Features:
- **Complete Customer Import** – Full customer profiles with contact details
- **Address Management** – Billing and shipping addresses preserved
- **Customer Statistics** – Order count and total order value imported
- **Automatic Loading** – Customers load immediately when page opens
- **Selective Import** – Choose individual customers to import
- **Professional Interface** – Modern UI matching other sync pages

#### Requirements:
- **API Permission:** Your Ecwid API token must have **"Read customers"** permission
- **403 Error?** See troubleshooting section below

### 📦 Order Sync

**Location:** `E2W Sync → Order Sync`

**Perfect for:** Importing order history with customer association

#### Features:
- **Complete Order Import** – Full order data with items and totals
- **Customer Association** – Automatically links orders to existing customers
- **Smart Filtering** – Filter by order status, payment status, and date range
- **Multi-tier Customer Matching** – Email, customer ID, and name similarity matching
- **Order Status Preservation** – Maintains payment and fulfillment status
- **Professional Interface** – Enhanced UI with filtering capabilities

#### Requirements:
- **API Permission:** Your Ecwid API token must have **"Read orders"** permission
- **403 Error?** See troubleshooting section below
- **Visual Selection Interface** – Enhanced checkbox design with alternating row colors and detailed product information
- **Smart Selection Controls** – "Select All/None" with indeterminate state and dynamic import button text
- **Detailed Product Information** – Shows SKU, ID, enabled status, and variation count with visual indicators
- **Complete Store Support** – Successfully tested with 6,974 products across 70 API calls
- **Enhanced Timeout Handling** – 120-second timeout for large product catalogs
- **Comprehensive Debug Logging** – Raw API response analysis and pagination troubleshooting
- **Variation Support** – Handles complex variable products with batch processing
- **Individual Progress Tracking** – Monitor each selected product with enhanced feedback

#### Technical Improvements:
- **Fixed API responseFields** – Now includes `,total` parameter ensuring correct pagination
- **Improved Count Logic** – Uses actual item count instead of unreliable API count field
- **Enhanced Loop Condition** – Robust pagination logic: `count>0 && offset<total`
- **Raw Response Debugging** – Displays actual API response data for troubleshooting

#### How to Use:
1. Products automatically load when visiting the page (all 6000+ products if applicable)
2. Toggle between **Enabled Products** and **Disabled Products** using the enhanced button interface
3. Review the complete product list with visual selection interface and detailed product information
4. Select individual products using the improved checkbox system
5. Use **Select All/None** for bulk selection with smart state management
6. Click **Import Selected Products** (text dynamically updates: "Import 1 Product" vs "Import 5 Products")
7. Monitor individual product progress with enhanced logging and comprehensive status feedback

#### Performance Example:
- **Large Store:** 6,974 total products loaded via 70 API calls
- **Separation:** 6,748 enabled + 226 disabled products with toggle interface
- **Loading Time:** Complete pagination with real-time progress tracking
- **Debug Output:** "Made 70 API calls, Loop condition: count>0 && offset<total"

### 📋 Placeholders Management

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

#### Customer/Order Sync "HTTP 403" Error
**Most Common Issue**: Missing API permissions

**Quick Fix:**
1. Go to Ecwid Dashboard → Apps → My Apps → API
2. Create new API token with ALL permissions:
   - ✅ Read catalog
   - ✅ Read store profile
   - ✅ Read products
   - ✅ Read categories
   - ✅ **Read customers** (required for Customer Sync)
   - ✅ **Read orders** (required for Order Sync)
3. Update plugin settings with new token
4. Test connection - should show "Connection successful!"

**Why this happens**: Customer and Order APIs require special permissions that are separate from basic product/category permissions.

**See also**: [Customer & Order Sync Setup Guide](CUSTOMER_ORDER_SYNC_SETUP.md) for detailed troubleshooting

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
- **✅ Complete Migration Tested** – 6,974 products + customers + orders processed successfully
- **✅ 70+ API Calls** – Efficient pagination handling for large catalogs
- **✅ Memory Optimized** – Processes large datasets without memory issues
- **✅ Zero Timeouts** – Robust error handling and recovery

### Typical Performance
- **Small Store (1-100 items)** – 2-5 minutes
- **Medium Store (100-1000 items)** – 10-20 minutes  
- **Large Store (1000+ items)** – 20-60 minutes depending on variations
- **Extra Large Store (6000+ items)** – 1-6 hours depending on server specifications

### New Feature Performance
- **Customer Sync** – Imports 1000+ customers in minutes
- **Order Sync** – Processes order history with customer association efficiently
- **Enhanced UI** – Instant loading with professional progress indicators

---

## 🎨 Enhanced Admin Interface

### Professional Design
- **Colorful Gradient Buttons** – Modern, visually appealing navigation
- **Responsive Grid Layout** – Perfect display on all devices
- **Consistent Styling** – Professional look across all sync pages
- **Real-time Status** – Live progress indicators and success/error states

### Navigation Improvements
- **Quick Actions Dashboard** – 6 main sync options accessible from settings
- **Breadcrumb Navigation** – Easy movement between sync pages
- **Visual Hierarchy** – Clear distinction between different sync types
- **Enhanced Typography** – Professional fonts and spacing

### User Experience
- **Auto-loading Data** – Customer, order, product, and category data loads automatically
- **Smart Progress Tracking** – Real-time updates with detailed logging
- **Error Prevention** – Clear warnings and confirmations for destructive actions
- **Accessibility Focus** – Screen reader friendly and keyboard navigable

---

## 🆘 Support & Documentation

### Getting Help
- **Documentation:** This README and [Customer/Order Setup Guide](CUSTOMER_ORDER_SYNC_SETUP.md)
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
- **v1.1.4** – **CRITICAL IMAGE PRESERVATION FIX**: Complete image safety during migrations
  - 🛡️ **Image Preservation** – Fixed critical bug where existing product images were deleted during updates
  - 🔍 **Smart Image Detection** – Intelligently checks if images already exist before importing
  - 📝 **Image URL Comparison** – Advanced filename-based comparison prevents duplicate imports
  - 🖼️ **Gallery Protection** – Gallery images fully preserved during product updates
  - ⚡ **Performance Boost** – Reduced unnecessary image processing for existing images
- **v1.1.3** – **ADVANCED SMART SKIP**: Enhanced timestamp detection & conservative skip logic
  - ✅ **Advanced Timestamp Detection** – Support for 6 different Ecwid timestamp fields
  - ✅ **Conservative Skip Logic** – Products imported within 24 hours skip automatically
  - ✅ **Debug Timestamp Logging** – Comprehensive timestamp field analysis
  - ✅ **Enhanced Legacy Handling** – Better decisions for pre-Smart Skip products
- **v1.1.2** – **SMART SKIP TECHNOLOGY**: Revolutionary migration recovery system
  - ✅ **Migration Recovery** – 70-90% time savings when restarting large migrations
  - ✅ **Timestamp Intelligence** – Compares Ecwid vs. local modification dates
  - ✅ **Resume Support** – Automatically continues from interruption point
  - ✅ **Legacy Product Support** – Handles products imported before Smart Skip
- **v1.1.1** – **COMPLETE MIGRATION SUITE**: Customer & Order Sync + Enhanced UI
  - ✅ **Customer Sync** – Full customer import with profiles and addresses
  - ✅ **Order Sync** – Complete order history with automatic customer association
  - ✅ **Enhanced Admin Interface** – Colorful gradient buttons and professional design
  - ✅ **Smart Error Handling** – Detailed 403 permission error messages with solutions
  - ✅ **Auto-loading Pages** – All sync pages load data automatically
  - ✅ **Improved JavaScript** – Resolved i18n and sanitizeHTML scope issues
  - ✅ **Better Documentation** – Comprehensive setup guides and troubleshooting
- **v1.1.0** – Enhanced product loading system, major pagination improvements
- **v1.0.5** – Advanced technical features, comprehensive error handling
- **v1.0.0** – Initial release with core sync functionality

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
