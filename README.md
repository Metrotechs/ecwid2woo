# Ecwid2Woo Product Sync

**Ecwid2Woo Product Sync** is a professional WordPress plugin that provides seamless synchronization between your Ecwid store and WooCommerce. With a modern, user-friendly interface and robust batch processing capabilities, it's the ideal solution for migrating from Ecwid to WooCommerce or maintaining synchronized product catalogs across both platforms.

---

## 🚀 Key Features

### 💼 Professional Admin Interface
- **Modern, Responsive Design** - Clean, intuitive interface that works perfectly on all devices
- **Real-time Progress Tracking** - Animated progress bars and live status updates
- **Professional Loading Experience** - Animated spinners with success/error animations during API operations
- **Client-Side Pagination** - Smooth navigation through large product catalogs (50 products per page)
- **Visual Connection Testing** - One-click API connection verification with instant feedback
- **Comprehensive Logging** - Color-coded logs with detailed operation tracking
- **Smart Navigation** - Seamless transitions between different sync options

### 🔄 Multiple Sync Options
- **Full Sync** - Complete catalog migration with preview capabilities
- **Category Sync** - Independent category import with hierarchy management
- **Selective Category Import** - Choose individual categories instead of importing all categories at once
- **Selective Product Sync** - Automatically displays all products with ability to import single or multiple products
- **Batch Processing** - Smart chunking prevents server timeouts on large catalogs

### 📊 Complete Data Synchronization
- **Product Information** - Names, SKUs, descriptions, prices, stock levels, dimensions, weight
- **Category Hierarchies** - Full parent-child relationships preserved
- **Product Variations** - Complete support for variable products with all option combinations
- **Image Management** - Featured images, galleries, and variation-specific images
- **Inventory Data** - Stock status, quantities, and unlimited stock settings
- **Category Thumbnails** - Imports category images and attaches them to WooCommerce terms
- **Currency Synchronization** - Automatically updates WooCommerce to match your Ecwid store currency (EUR→EUR, GBP→GBP, USD→USD, etc.)

### ⚡ Advanced Technical Features
- **AJAX-Powered Processing** - Non-blocking operations with real-time feedback
- **Memory Optimization** - Efficient handling of large product catalogs
- **Smart Duplicate Prevention** - Uses Ecwid IDs and SKU matching to avoid duplicates
- **Auto-Recovery Systems** - Handles API timeouts and connection issues gracefully
- **Intelligent Attribute Handling** - Automatically handles WooCommerce's 28-character attribute slug limits
- **WordPress Standards Compliant** - Follows all WordPress coding and security best practices
- **Multilingual-Safe Slugs** - Robust transliteration and length-limited slug generation for non‑Latin/long names while preserving original display names

### 🛡️ Reliability & Safety
- **Stop Sync Control** - Immediate cancellation capability for all operations
- **Comprehensive Error Handling** - Detailed error reporting and recovery mechanisms
- **Safe Re-syncing** - Idempotent operations prevent data corruption
- **Debug Integration** - Works seamlessly with WordPress debug logging

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
2. Upload the `ecwid2woo-product-sync` folder to `/wp-content/plugins/`
3. Activate the plugin through the **Plugins** menu in WordPress

### Post-Installation Setup
1. Go to **Ecwid2Woo Sync → Settings** in your admin menu
2. Enter your **Ecwid Store ID** and **API Token**
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
   - **Note:** Both Public and Secret tokens work - what matters are the permissions
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
**Location:** `Ecwid2Woo Sync → Settings`

- Configure your Ecwid API credentials
- Test connection with visual feedback
- Access quick navigation to all sync options
- Monitor connection status with real-time indicators

### 🔄 Full Sync
**Location:** `Ecwid2Woo Sync → Full Sync`

**Perfect for:** Complete store migrations or comprehensive updates

**Features:**
- **Automatic Preview** - See exactly what will be synced before starting
- **Two-Phase Process** - Categories first, then products
- **Real-time Progress** - Visual progress tracking with detailed logs
- **Stop Control** - Cancel operation at any time
- **Smart Batching** - Processes data in optimized chunks

**How to Use:**
1. Page automatically loads preview on visit
2. Review categories and products to be synced
3. Click **Start Full Sync** to begin
4. Monitor progress and logs in real-time
5. Use **STOP SYNC** if needed

### 📁 Category Sync
**Location:** `Ecwid2Woo Sync → Category Sync`

**Perfect for:** Setting up category structure before product import

**Features:**
- **Enhanced UI Design** - Professional interface with bordered information panels and status messages
- **Automatic Category Loading** - Categories load immediately when page opens with "📁 Category Loading Complete" confirmation
- **Enhanced Debug Information** - Shows API call count, total categories loaded, and detailed performance metrics
- **Selective Import** - Choose individual categories to import instead of all at once
- **Visual Selection Interface** - Enhanced checkbox design with alternating row colors and clear visual hierarchy
- **Smart Selection Controls** - "Select All/None" with indeterminate state and dynamic button text
- **Category Information Display** - Shows ID, Parent ID, and visual indicators for root vs subcategories
- **Increased Timeout Handling** - 120-second timeout for large category lists
- **Comprehensive Logging** - Debug logging shows each API call and category loading progress
- **Hierarchy Preservation** - Maintains parent-child relationships
- **Hierarchy Fix Tool** - Resolves any structural issues
- **Independent Operation** - Sync categories without touching products

**How to Use:**
1. Categories automatically load when visiting the page with enhanced status display
2. Click **Reload Categories** to refresh if needed
3. Review the enhanced category list with visual indicators
4. Select individual categories using the improved checkbox interface
5. Use **Select All/None** for bulk selection with smart state indication
6. Click **Import Selected Categories** (text dynamically updates based on selection count)
7. Monitor progress with enhanced status messages and logging
8. Use **Fix Category Hierarchy** if needed after sync

### 🎯 Selective Product Sync
**Location:** `Ecwid2Woo Sync → Product Sync`

**Perfect for:** Targeted imports, testing, or specific product updates

**Features:**
- **MAJOR BREAKTHROUGH:** Complete product loading system - handles stores with 6000+ products (previously limited to 100)
- **Advanced Pagination Engine** - Makes 70+ API calls to load entire product catalog instead of stopping at first 100
- **Enabled/Disabled Product Separation** - Toggle between "Enabled Products (6748)" and "Disabled Products (226)" with smart button interface
- **Real-time Loading Metrics** - Shows exact API call count, total products loaded, and performance data
- **Enhanced API Response Handling** - Fixed critical `count` field parsing issue that prevented full product loading
- **Professional Status Panel** - "🎯 Product Loading Complete" with comprehensive loading statistics
- **Advanced Debug Information** - Console and server-side logging showing pagination progress and API response analysis
- **Automatic Product Loading** - All products load immediately when page opens with comprehensive status feedback
- **Visual Selection Interface** - Enhanced checkbox design with alternating row colors and detailed product information
- **Smart Selection Controls** - "Select All/None" with indeterminate state and dynamic import button text
- **Detailed Product Information** - Shows SKU, ID, enabled status, and variation count with visual indicators
- **Complete Store Support** - Successfully tested with 6974 products across 70 API calls
- **Enhanced Timeout Handling** - 120-second timeout for large product catalogs
- **Comprehensive Debug Logging** - Raw API response analysis and pagination troubleshooting
- **Variation Support** - Handles complex variable products with batch processing
- **Individual Progress Tracking** - Monitor each selected product with enhanced feedback

**Technical Improvements:**
- **Fixed API `responseFields`** - Now includes `,total` parameter ensuring correct pagination
- **Improved Count Logic** - Uses actual item count instead of unreliable API `count` field
- **Enhanced Loop Condition** - Robust pagination logic: `count>0 && offset<total`
- **Raw Response Debugging** - Displays actual API response data for troubleshooting

**How to Use:**
1. Products automatically load when visiting the page (all 6000+ products if applicable)
2. Toggle between "Enabled Products" and "Disabled Products" using the enhanced button interface
3. Review the complete product list with visual selection interface and detailed product information
4. Select individual products using the improved checkbox system
5. Use **Select All/None** for bulk selection with smart state management
6. Click **Import Selected Products** (text dynamically updates: "Import 1 Product" vs "Import 5 Products")
7. Monitor individual product progress with enhanced logging and comprehensive status feedback

**Performance Example:**
- **Large Store:** 6974 total products loaded via 70 API calls
- **Separation:** 6748 enabled + 226 disabled products with toggle interface
- **Loading Time:** Complete pagination with real-time progress tracking
- **Debug Output:** "Made 70 API calls, Loop condition: count>0 && offset<total"

### 📋 Placeholders Management
**Location:** `Ecwid2Woo Sync → Placeholders`

**Purpose:** Review and manage temporary placeholder items created during sync

- View placeholder categories created for missing parents
- Clean up temporary items after hierarchy fixes
- Monitor sync-related administrative data

---

## 🔧 Advanced Features

### 🔄 Variation Processing
- **Automatic Attribute Creation** - Missing WooCommerce attributes auto-generated with intelligent slug handling
- **Smart Slug Management** - Automatically truncates long attribute names to comply with WooCommerce's 28-character limit
- **Smart Combination Mapping** - Ecwid options become WooCommerce variations
- **Batch Processing** - Large variation sets processed in optimized chunks
- **Variation-Specific Data** - Individual SKUs, prices, stock, and images

### 🛠️ Error Handling
- **Graceful Degradation** - Continues processing even if individual items fail
- **Detailed Error Reporting** - Clear explanations of any issues encountered
- **Automatic Recovery** - Handles temporary API issues and timeouts
- **Debug Integration** - Works with WordPress WP_DEBUG for troubleshooting

### 🎨 User Experience
- **Responsive Design** - Works perfectly on desktop, tablet, and mobile
- **Visual Feedback** - Loading animations, progress indicators, and status messages
- **Intuitive Navigation** - Clear pathways between different sync options
- **Accessibility** - Follows WordPress accessibility guidelines

---

## 🚨 Important Notes

### Before First Sync
- **Backup Your Database** - Always backup before running large operations
- **Test Connection** - Verify API credentials work correctly
- **Review Preview Data** - Check what will be synced before starting
- **Consider Staging** - Test on staging site first for large catalogs

### Performance Tips
- **Optimal Timing** - Run syncs during low-traffic periods
- **Monitor Progress** - Keep browser tab active during sync operations
- **Server Resources** - Ensure adequate PHP memory and execution time
- **Network Stability** - Stable internet connection recommended for large syncs

### Data Handling
- **Duplicate Prevention** - Plugin intelligently matches existing items
- **Safe Re-syncing** - Running sync multiple times updates rather than duplicates
- **Ecwid ID Storage** - Items tagged with Ecwid IDs for future matching
- **Non-Destructive** - Only creates/updates items, never deletes existing data

---

## 🔍 Troubleshooting

### Common Issues

**Connection Test Fails**
- Verify Store ID and API Token are correct
- Check API token permissions include read access
- Ensure Ecwid store is active and accessible

**Sync Stops Unexpectedly**
- Check server PHP memory and execution time limits
- Verify stable internet connection
- Review WordPress debug logs for specific errors
- Try syncing smaller batches via Selective Sync

**Product Loading Limited to 100 Items (Fixed in v1.0.3)**
- **Issue:** Previous versions could only load first 100 products due to API pagination bug
- **Solution:** Update to v1.0.3+ which includes complete pagination system overhaul
- **Verification:** Check browser console for "API calls made: 70" (instead of "API calls made: 1")
- **Expected Result:** Should see total products matching your Ecwid store count

**Browser Freezing with Large Product Lists (Fixed in v1.0.4)**
- **Issue:** Browser becomes unresponsive when displaying 1000+ products simultaneously in DOM
- **Solution:** Update to v1.0.4+ which includes client-side pagination (50 products per page)
- **Verification:** Check for pagination controls under "Reload Products" button
- **Expected Result:** Smooth navigation through products without browser freezing

**Large Store Performance**
- **6000+ Products:** Plugin now handles large stores efficiently with API pagination + client-side pagination
- **Browser Optimization:** Products displayed in pages of 50 items for instant loading (v1.0.4+)
- **Multiple API Calls:** Monitor console output showing "Made 70 API calls" for large catalogs
- **Cross-Page Selection:** Smart selection tracking maintains choices across all pagination pages
- **Enabled/Disabled Separation:** Toggle buttons allow easy navigation between product types
- **Real-time Progress:** Watch pagination progress in browser console debug output

**Attribute Creation Errors**
- Plugin automatically handles WooCommerce's 28-character attribute slug limit
- Long attribute names are intelligently truncated while maintaining readability
- Duplicate slug prevention ensures unique attribute creation

**Images Not Importing**
- Check server has adequate disk space
- Verify PHP file upload limits
- Ensure WordPress media upload permissions are correct

**Categories Missing Hierarchy**
- Use the **Fix Category Hierarchy** tool after category sync
- Check for circular references in Ecwid category structure
- Verify parent categories were successfully imported

### Debug Mode
Enable WordPress debug mode for detailed troubleshooting:

```php
// Add to wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

Check debug logs at `/wp-content/debug.log` for detailed error information.

---

## 🤝 Support & Development

### Community Support
- **WordPress.org Support Forum** - For general questions and community help
- **Plugin Reviews** - Share your experience and help others

### Professional Support
- **Priority Support** - Available through [Metrotechs.io](https://metrotechs.io)
- **Custom Development** - Tailored solutions for specific requirements
- **Enterprise Solutions** - Large-scale implementations and customizations

### Contributing
- **GitHub Repository** - Contribute code improvements and bug fixes
- **Feature Requests** - Suggest new functionality
- **Bug Reports** - Help improve reliability and compatibility

---

## 📄 License & Legal

### Dual Licensing Model
**Plugin Code:** Licensed under **GNU General Public License v2.0 or later**
- **Free to Use** - Core plugin functionality always free
- **Modify & Extend** - Full source code available for customization  
- **Redistribute** - Share with others under same license terms

**Brand & Trademark:** **"Ecwid2Woo Product Sync"** is a trademark of Metrotechs
- **Protected Brand** - Name, logo, and brand identity are trademarked
- **Commercial Services** - Business services available under separate terms
- **Quality Assurance** - Trademark ensures authentic software and support

### Business Services
While the plugin is free and open source, professional services are available:
- **Premium Support** - Priority technical assistance and troubleshooting
- **Custom Development** - Tailored features and integrations
- **Migration Services** - Professional store migration assistance
- **Enterprise Solutions** - Large-scale implementations and custom licensing
- **Training & Consulting** - Expert guidance and best practices

### Privacy & Data Handling
- **Minimal Data Collection** - Only processes product and category data
- **No Personal Data** - Does not handle customer or order information
- **Local Storage Only** - All data stays in your WordPress database
- **API Read-Only** - Never modifies your Ecwid store data

### Credits
- **Developed by:** [Metrotechs](https://metrotechs.io) ™
- **Maintained by:** Richard Hunting
- **Trademark Owner:** Metrotechs
- **Plugin License:** GPL v2.0+
- **Brand Rights:** All Rights Reserved

**"Ecwid2Woo Product Sync" is a trademark of Metrotechs. Ecwid, WordPress, and WooCommerce are trademarks of their respective owners.**

---

## 🎯 Version Information
**Current Version:** 1.0.5  
**Compatibility:** WordPress 5.0+ | WooCommerce 3.0+ | PHP 7.2+  
**Release Date:** August 29, 2025  
**Update Policy:** Regular updates for compatibility and feature enhancements

### Recent Updates (v1.0.5)
- **CRITICAL FIX:** Eliminated 500 Internal Server Errors during full sync operations through comprehensive error handling and resource management
- **MAJOR IMPROVEMENT:** Dynamic batch sizing - Categories sync 15 items per batch (5x faster), Products use 3 items for stability  
- **NEW:** Intelligent SKU conflict resolution - Automatically handles duplicate SKUs during variation processing with multi-level fallback
- **ENHANCED:** API request retry logic with exponential backoff for rate limiting (429) and server errors (5xx)
- **IMPROVED:** Memory management - Increased to 512MB for sync operations with better resource allocation
- **ADDED:** Comprehensive exception handling - Fatal errors and regular exceptions caught with user-friendly error messages
- **NEW:** Enhanced term creation with conflict resolution - Handles concurrent database operations gracefully
- **IMPROVED:** Variation batch processing reduced from 50 to 25 items for better stability
- **ADDED:** Emergency SKU generation - Timestamp-based unique SKUs when conflicts cannot be resolved normally
- **ENHANCED:** JavaScript error handling - Automatic retry for retryable errors with progressive delays
- **IMPROVED:** Database operation robustness - Better handling of term creation conflicts and race conditions
- **ADDED:** Professional error classification - Server errors, rate limits, and network issues handled distinctly
- **ENHANCED:** Sync reliability for large stores - Handles memory exhaustion and database timeout scenarios
- **IMPROVED:** Real-time error feedback - Clear indication of temporary vs permanent failures with retry recommendations

### Recent Updates (v1.0.4)
- **CRITICAL FIX:** Client-side pagination prevents browser freezing with large product catalogs (6000+ products)
- **MAJOR UX IMPROVEMENT:** Product lists now display 50 items per page instead of rendering all products simultaneously
- **NEW:** Independent pagination controls positioned outside scrollable areas for optimal navigation
- **ENHANCED:** Smart selection tracking across all pages - maintains selected products when navigating
- **IMPROVED:** Pagination positioned directly under "Reload Products" button for intuitive access
- **ADDED:** Cross-page selection counter showing "📋 X products selected across all pages"
- **OPTIMIZED:** Browser performance dramatically improved - instant page loads vs previous freezing
- **ENHANCED:** "Select All/None" functionality works per page while maintaining global selection tracking
- **IMPROVED:** Import button accurately reflects total selections across all pages
- **ADDED:** Professional pagination interface with Previous/Next navigation and page indicators
- **FIXED:** Memory optimization prevents DOM overload when displaying thousands of products
- **ENHANCED:** Enabled/Disabled product switching resets pagination and clears selections for clean transitions
- **IMPROVED:** Error handling clears pagination controls during loading failures
- **ADDED:** Visual feedback showing current page range: "Showing 1-50 of 6748 products (Page 1 of 135)"

### Recent Updates (v1.0.3)
- **MAJOR FIX:** Complete product pagination system overhaul - now loads ALL products from large stores (6000+ products)
- **BREAKTHROUGH:** Fixed critical API response parsing issue where `count` field wasn't returned with custom `responseFields`
- **NEW:** Enhanced Product Sync with Enabled/Disabled product separation and toggle buttons
- **ADDED:** Advanced pagination with proper API call chaining (70+ API calls for large stores vs previous 1 call limit)
- **ENHANCED:** Product Sync page shows exact counts: "6748 Enabled Products" and "226 Disabled Products" with toggle interface
- **NEW:** Professional Loading UX - Animated spinner with real-time status updates during API calls (prevents "broken page" appearance)
- **ADDED:** Loading interface with success/error animations - ✅ checkmark on completion, ❌ for errors with auto-hide
- **ENHANCED:** Visual feedback during 70+ API calls showing "Loading Products from Ecwid" with professional animated dashicons spinner
- **IMPROVED:** Smart loading completion - displays final product count "✅ 6974 products ready for sync!" with smooth fade-out
- **ADDED:** Error handling animations - connection errors show clear messages with auto-hide after 5 seconds
- **IMPROVED:** Real-time debug information showing API calls made, total products available, and pagination progress
- **ADDED:** Server-side and browser console debug logging with detailed API response analysis
- **FIXED:** API `responseFields` now includes `,total` parameter ensuring correct total count from Ecwid API
- **ENHANCED:** Product loading now uses actual item count `count($items_from_api)` instead of unreliable API `count` field
- **IMPROVED:** Professional UI with "🎯 Product Loading Complete" status showing loading performance and API metrics
- **ADDED:** Raw API response debugging to help troubleshoot pagination and loading issues
- **ENHANCED:** Comprehensive debug output: "Made 70 API calls, Loop condition: count>0 && offset<total"
- **ADDED:** Enhanced Category Sync page with "📁 Category Loading Complete" status panel matching product page design
- **IMPROVED:** Visual selection interfaces with alternating row colors and enhanced checkbox design
- **ENHANCED:** Smart selection controls with indeterminate states and dynamic button text updates
- **ADDED:** Automatic page loading - products and categories load immediately when visiting their respective pages
- **IMPROVED:** Increased API timeout to 120 seconds for handling large stores efficiently
- **ENHANCED:** Status messages show API performance data and loading completion details

### Recent Updates (v1.0.2)
- NEW: Robust multilingual support for category slugs with transliteration and safe, unique, length-limited generation (keeps full original names)
- FIX: Category import status now correctly reports success after ASCII-safe retry (no false FAILED statuses)
- IMPROVED: Duplicate handling for ASCII fallback names (e.g., Category-{EcwidID})—reuses existing terms instead of failing
- ADDED: Category image import—downloads and attaches thumbnails to WooCommerce categories
- ENHANCED: Error diagnostics for DB insert issues—including DB charset/collation and byte/char lengths in logs
- FIX: Activation error caused by invalid "break 2" usage removed
- STABILITY: Better handling of Ecwid HTTP 500 and server-side errors with clearer user feedback

### Recent Updates (v1.0.1)
- **NEW:** Selective category import - Choose individual categories instead of importing all at once
- **IMPROVED:** Intelligent attribute slug handling for WooCommerce's 28-character limit
- **ENHANCED:** Better error handling for attribute creation with long names
- **ADDED:** Checkbox-based category selection interface with select all/none controls
- **FIXED:** "Invalid taxonomy" errors for attributes with names exceeding character limits

---

*Transform your e-commerce platform with confidence. Ecwid2Woo Product Sync makes store migration and synchronization straightforward, reliable, and professional.*
