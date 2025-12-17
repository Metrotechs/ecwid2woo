=== Metrotechs E2W Sync ===
Contributors: Metrotechs, Richard Hunting
Donate link: https://metrotechs.io/donate
Tags: ecwid, woocommerce, migration, sync, ecommerce
Requires at least: 5.0
Tested up to: 6.9
Requires PHP: 7.2
Stable tag: 1.4.6
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
WC requires at least: 3.0
WC tested up to: 9.2

Professional Ecwid to WooCommerce migration with Smart Skip Technology. Sync products, categories, customers, and orders efficiently.

== Description ==

Metrotechs E2W Sync is the most comprehensive WordPress plugin for migrating your entire e-commerce store from Ecwid to WooCommerce.
Go beyond basic product sync with full customer and order migration capabilities, plus revolutionary Smart Skip Technology for large-scale migrations.

**NEW: Smart Skip Technology** - Revolutionary migration recovery system that intelligently skips already imported products, making large store migrations (1000+ products) resume seamlessly from interruptions.

**Perfect for:**
- Complete store migrations from Ecwid to WooCommerce (tested with 6000+ products)
- E-commerce agencies managing client migrations  
- Store owners transferring customer data and order history
- Businesses needing comprehensive data synchronization
- Developers requiring enterprise-grade migration tools
- Large stores needing interruption-resistant migrations

== Key Features ==

**🧠 Smart Skip Technology (NEW)**
- **Intelligent Resume:** Automatically skips imported products and continues migration from interruption point
- **Timestamp Comparison:** Compares Ecwid update dates with local import timestamps
- **Migration Recovery:** 70-90% time savings when restarting large migrations
- **Legacy Product Handling:** Automatically manages products imported before Smart Skip implementation
- **Memory Optimization:** Adaptive batch sizing based on available server memory

**🔄 Complete Migration Suite**
- **Products & Categories:** Full catalog with variations, images, and hierarchies
- **Customer Import:** Complete customer profiles with billing/shipping addresses
- **Order History:** Import orders with automatic customer association
- **Smart Data Handling:** Prevents duplicates and maintains data integrity

**👥 Customer & Order Management**
- Import customer accounts with contact information and order statistics
- Link orders to existing customer accounts automatically
- Preserve billing and shipping addresses
- Filter orders by status, payment status, and date range
- Multi-tier customer matching (email, ID, name similarity)

**🎯 Multiple Sync Options**
- **Full Sync:** Complete catalog migration with preview capabilities and Smart Skip
- **Category Sync:** Import categories independently with hierarchy management
- **Product Sync:** Selective product import with variation support
- **Customer Sync:** Import customer accounts and profiles
- **Order Sync:** Import order history with customer association

**🚀 Professional User Interface**
- Modern admin interface with colorful gradient buttons
- Auto-loading data on all sync pages
- Real-time progress tracking with animated status indicators
- Comprehensive error handling with actionable solutions
- Responsive design works on all devices

**⚡ Advanced Technical Features**
- **High-Speed Processing:** Up to 1,000 items per hour (including variations)
- AJAX-powered batch processing prevents server timeouts
- Smart pagination handles stores with 6000+ products
- Memory-optimized for large catalogs
- Idempotent operations prevent duplicate entries

**🛡️ Reliable & Safe**
- Enhanced error handling with specific 403 permission guidance
- Comprehensive logging for troubleshooting
- Stop sync functionality for user control
- WordPress security best practices

**🔧 Developer Friendly**
- Clean, well-documented code structure
- Organized asset management (CSS/JS separation)
- WordPress coding standards compliant
- Extensible architecture for customization

== What's New in Version 1.1.0+ ==

**MAJOR UPDATE:** Complete Customer & Order Sync + Enhanced UI

- **Customer Sync:** Full customer import with profiles, addresses, and statistics
- **Order Sync:** Complete order history with automatic customer association  
- **Enhanced Admin Interface:** Colorful gradient buttons and professional design
- **Smart Error Handling:** Detailed 403 permission error messages with solutions
- **Auto-loading Pages:** All sync pages load data automatically
- **Improved JavaScript:** Resolved scope issues for better reliability
- **Better Documentation:** Comprehensive setup guides and troubleshooting
- **Security Improvements:** Enhanced input validation and secure API handling
- **WordPress Standards:** Full compliance with WordPress coding and plugin development standards

== Installation ==

1. **Upload the Plugin:**
   - Via WordPress Admin: Go to **Plugins → Add New → Upload Plugin** and upload the ZIP file
   - Via FTP: Upload the `ecwid2woo` folder to `/wp-content/plugins/`

2. **Activate the Plugin:**
   - Navigate to **Plugins** in your WordPress admin and activate "Ecwid2Woo Product Sync"

3. **Configure Settings:**
   - Go to **Ecwid2Woo Sync → Settings** in your admin menu
   - Enter your Ecwid **Store ID** and **API Secret Token**
   - Click **Save Settings** and test your connection

4. **Start Syncing:**
   - Use **Full Sync** for complete catalog migration
   - Use **Category Sync** for categories only
   - Use **Product Sync** for selective product import

**Important:** Always backup your WordPress database before running any sync operations, especially on production sites.

== Getting Your Ecwid API Credentials ==

1. Log into your Ecwid Control Panel
2. Navigate to **Apps → My Apps → API**
3. Your **Store ID** is displayed at the top
4. Generate a new **Secret Token** with appropriate permissions:
   - Read catalog
   - Read store profile
   - Read products
   - Read categories

== Frequently Asked Questions ==

= Does this plugin sync orders or customers? =

No, this plugin focuses exclusively on product and category synchronization. It does not handle orders, customers, or other store data.

= What happens if products already exist in WooCommerce? =

The plugin uses intelligent matching to prevent duplicates:
1. First, it checks for existing items using stored Ecwid IDs
2. If no match is found, it attempts to match by SKU (products) or name (categories)
3. Existing items are updated with Ecwid data; new items are created if no match exists

= Are product variations supported? =

Yes, full variation support is included:
- Ecwid product options become WooCommerce attributes
- Option values become attribute terms (auto-created if missing)
- All combinations are created as WooCommerce variations
- Variation-specific data (SKU, price, stock, images) is preserved

= Can I sync only certain products? =

Absolutely! The **Product Sync** page allows you to:
- Load all available Ecwid products
- Select specific products for import
- Use "Select All/None" for bulk operations
- Track progress for each selected product

= What about category hierarchies? =

Category parent-child relationships are fully preserved:
- Categories are imported with proper hierarchy
- The "Fix Category Hierarchy" tool resolves any ordering issues
- Complex nested structures are supported

= Can I stop a sync in progress? =

Yes, all sync operations include a prominent **STOP SYNC** button that immediately halts the process and provides user feedback.

= How does the plugin handle large catalogs? =

The plugin uses advanced batch processing:
- Operations are performed in small chunks to prevent timeouts
- Memory usage is optimized for large datasets
- Progress tracking provides real-time updates
- Configurable batch sizes for different server capabilities

= What if my server has limitations? =

The plugin is designed to work within common hosting constraints:
- Batch processing prevents memory issues
- Configurable timeouts accommodate slower servers
- Error recovery handles temporary connection issues
- Detailed logging helps identify and resolve problems

= Are images synchronized? =

Yes, complete image synchronization is supported:
- Main product images
- Product gallery images
- Variation-specific images
- Automatic WordPress media library integration

= How do I troubleshoot sync issues? =

The plugin provides comprehensive debugging tools:
- Real-time logging with color-coded messages
- Connection testing functionality
- Detailed error reporting
- WordPress debug log integration

= Why am I getting "HTTP 403" errors for Customer/Order Sync? =

This is the most common issue with Customer and Order sync. The error means your API token doesn't have the required permissions.

**Quick Fix:**
1. Go to Ecwid Dashboard → Apps → My Apps → API
2. Create new API token with ALL permissions:
   * Read catalog
   * Read store profile
   * Read products  
   * Read categories
   * **Read customers** (required for Customer Sync)
   * **Read orders** (required for Order Sync)
3. Update plugin settings with new token
4. Test connection - should show "Connection successful!"

**Why this happens:** Customer and Order APIs require special permissions that are separate from basic product/category permissions.

= How does Customer/Order association work? =

The plugin uses intelligent multi-tier matching:
- **Primary:** Email address matching
- **Secondary:** Ecwid customer ID matching  
- **Tertiary:** Name similarity matching
- Orders are automatically linked to existing WordPress users when possible

== Screenshots ==

1. **Settings Page** - Configure Ecwid API credentials with connection testing
2. **Enhanced Dashboard** - Colorful gradient navigation with 6 sync options
3. **Full Sync Interface** - Complete catalog synchronization with progress tracking
4. **Category Sync** - Auto-loading categories with professional UI
5. **Product Sync** - Selective product import with advanced filtering
6. **Customer Sync** - Import customer accounts with profiles and addresses
7. **Order Sync** - Import order history with customer association and filtering
8. **Sync in Progress** - Real-time progress bars and detailed logging
6. **Navigation Interface** - Modern, intuitive admin navigation

== Changelog ==

= 1.4.6 =
**Adaptive Batch Sizing & Fast Skip Optimization**

**Major Features:**
* **Adaptive Batch Sizing** - Automatically reduces batch size when server timeouts occur (524, 504, 408)
* **Fast Skip Optimization** - Pre-loads existing Ecwid IDs in single query for 100x faster duplicate detection
* **Intelligent Timeout Recovery** - Detects Cloudflare 524, Gateway 504, Request 408, and jQuery timeouts
* **Higher Default Batches** - Products and categories now start at 100 items per batch for faster syncing
* **Client-Side Batch Control** - Batch sizes dynamically adjust based on server response times

**Technical Enhancements:**
* Batch size halves automatically on timeout (100→50→25→12→6→3→1)
* Up to 8 timeout retries before giving up on a batch
* Single SQL query with IN clause replaces individual lookups
* O(1) hash map lookup for existing product detection
* Reduced AJAX timeout from 300s to 90s for faster error detection

**UX Improvements:**
* Clear status messages when batch size is reduced
* Shows current batch size in sync progress
* Automatic recovery continues sync after batch reduction

= 1.4.3 =
**Enhanced Reliability & Retry Logic**

**Major Improvements:**
* **Exponential Backoff Retry** - Smart retry delays (3s→6s→12s) for better server recovery during 500 errors
* **Category Batch Loading** - Categories now load in batches with full retry logic, matching product sync reliability
* **Full Sync Retry Fix** - Fixed critical bug where retry counter was not reset after successful batches
* **Cloudflare 524 Handling** - Explicit handling for Cloudflare timeout errors with automatic retry
* **Better Status Messages** - Users see retry countdown and server status during transient errors
* **Increased Batch Delays** - 3-second delays between batches to reduce server load

**Technical Enhancements:**
* Reset retry counter immediately after each successful AJAX response
* Added explicit HTTP 524 status code handling for Cloudflare-hosted sites
* Improved user feedback during retry attempts with countdown display
* Extended batch delay from 2 to 3 seconds for server breathing room

= 1.4.2 =
**Batch Loading System**

**Major Features:**
* **Product Batch Loading** - Products load in manageable batches with progress tracking
* **Retry Logic** - Automatic 3-attempt retry on transient 500 errors
* **Progress Display** - Real-time batch progress with item counts
* **Memory Optimization** - Prevents browser and server memory issues with large catalogs

= 1.1.4 =
**Critical Image Preservation Fix**

**CRITICAL BUG FIXES:**
* **Image Preservation** - Fixed major bug where existing product images were deleted during re-import or updates
* **Smart Image Detection** - Now intelligently checks if images already exist before importing to prevent duplicates
* **Image URL Comparison** - Advanced filename-based comparison prevents re-importing identical images
* **Gallery Protection** - Gallery images are now fully preserved when updating existing products
* **Main Image Safety** - Main product images only update when they're actually different from existing ones

**Performance Improvements:**
* Reduced unnecessary image processing for products with existing images
* Eliminated redundant image downloads when images haven't changed
* Better bandwidth utilization during large migration updates

= 1.1.3 =
**Advanced Smart Skip Technology**

**Major Features:**
* **Advanced Timestamp Detection** - Expanded Ecwid timestamp field support (updated, lastUpdated, dateUpdated, modifiedDate, createTimestamp, created)
* **Conservative Skip Logic** - Products imported within 24 hours automatically skip to reduce re-processing overhead
* **Debug Timestamp Logging** - Shows available Ecwid timestamp fields in debug mode for troubleshooting
* **Enhanced Legacy Handling** - Better decisions for products imported before Smart Skip implementation

**Performance Improvements:**
* Reduced processing overhead for recently imported products
* More intelligent skip decisions based on multiple timestamp sources
* Better handling of products with missing Ecwid timestamp data

= 1.1.2 =
**Smart Skip Technology Revolution**

**Breakthrough Features:**
* **Smart Skip Technology** - Revolutionary migration recovery system for enterprise-scale stores
* **Resume Migration Support** - Automatically skips imported products and continues from interruption point
* **Timestamp-Based Intelligence** - Compares Ecwid product update time with local import timestamp
* **Migration Recovery** - 70-90% time savings when restarting large migrations (4500+ products tested)
* **Import Timestamp Tracking** - Each product saves _ecwid_last_import_time meta for smart decisions
* **Legacy Product Support** - Handles products imported before timestamp tracking implementation

**Enterprise Capabilities:**
* Efficient large batch handling prevents duplicate processing overhead
* Memory optimization and processing for stores with thousands of products
* Seamless recovery from migration interruptions and server errors

= 1.1.1 =
**Complete E-commerce Migration Suite**

**Major Features:**
* **Customer Import System** - Complete customer profile synchronization from Ecwid to WooCommerce
* **Order Import System** - Full order history migration with customer association and status preservation
* **Customer Sync Page** - Dedicated interface for importing customer accounts with filtering options
* **Order Sync Page** - Dedicated interface for importing order history with advanced filtering
* **Full Sync Enhancement** - 4-step sync process: Categories → Products → Customers → Orders
* **Intelligent Customer Matching** - Multi-tier matching using email, ID, and name similarity
* **Order-Customer Association** - Automatic linking of imported orders to existing customer accounts

**UI & Experience:**
* **Navigation Enhancement** - Added Customer Sync and Order Sync buttons to all sync pages
* **UI Consistency** - Full Sync page matches polished interface of other sync pages
* **Professional Loading Interface** - Enhanced loading states and success feedback
* **4-Column Status Display** - Shows Categories, Products, Customers, Orders counts with icons

= 1.0.5 =
**Reliability & Conflict Resolution**

**Major Improvements:**
* **500 Error Resolution** - Comprehensive error handling prevents server timeouts during large imports
* **Dynamic Batch Sizing** - Optimized processing: 15 categories/batch (5x faster), 3 products/batch for stability
* **SKU Conflict Resolution** - Automatic unique SKU generation prevents "Invalid or duplicated SKU" errors
* **Enhanced Error Recovery** - Try-catch blocks with user-friendly error messages and automatic retry logic
* **Memory Management** - Improved resource allocation prevents memory exhaustion during full sync
* **API Retry Logic** - Exponential backoff for rate limiting and server errors with intelligent retry

**Technical Enhancements:**
* Comprehensive fatal error handling for PHP 7+ with graceful degradation
* Enhanced AJAX error classification and automatic retry mechanisms  
* Dynamic resource limits based on content type and batch size
* Unique variation SKU generation with wc_get_product_id_by_sku() validation
* Progressive retry delays for improved API reliability
* Enhanced logging for better debugging and troubleshooting

**User Experience:**
* Faster category synchronization with optimized batch processing
* Reduced sync failures through improved error handling
* Better progress feedback during large import operations
* Automatic conflict resolution without user intervention

= 1.0.4 =
**Performance Optimization & Enhanced UI**

**Major Improvements:**
* **Client-side pagination** - Resolves browser freezing issues with large product catalogs (6000+ products)
* **50 products per page display** - Optimized DOM rendering for smooth performance
* **Cross-page selection tracking** - Maintains product selections across pagination
* **Enhanced loading indicators** - Professional animated spinners with success/error states
* **Improved pagination controls** - Independent positioning outside scroll areas for better UX
* **API token interface clarity** - Updated labeling to support both public and secret tokens
* **Complete API pagination support** - Fixed responseFields to load all products (not just first 100)
* **Enhanced error handling** - Better feedback for API failures and connection issues

**Technical Enhancements:**
* Implemented Set-based selection management for optimal performance
* Independent pagination container for improved UI layout
* Optimized API calls with proper total count retrieval
* Enhanced JavaScript architecture for large dataset handling
* Improved memory management for browser stability

**User Experience:**
* Eliminated browser freezing with large product catalogs
* Faster page load times with pagination
* Clearer progress feedback during sync operations
* Better responsive design for pagination controls
* Improved accessibility for selection management

= 1.0.2 =
**Stability, Multilingual Support, and Category Images**

**New & Improved:**
* Robust multilingual-safe slug generation for categories (transliteration + unique, length-limited slugs); original names are preserved
* Category thumbnail import—downloads and attaches images to WooCommerce category terms
* Better diagnostics for DB insert errors (logs charset/collation and name byte/char lengths)

**Fixes:**
* Correctly mark category imports as success after ASCII-safe retry (no false FAILED status)
* Reuse existing ASCII fallback categories (e.g., Category-{EcwidID}) instead of failing on duplicates
* Resolved activation fatal error caused by invalid "break 2" usage
* Improved handling of Ecwid HTTP 500 with clearer feedback and resilience

= 1.0.0 =
**Initial Official Release - Complete Rewrite**

**New Features:**
* Professional, responsive admin interface with modern design
* Modular code architecture with clean separation of concerns
* Enhanced connection testing with visual feedback
* Stop sync functionality for user control
* Comprehensive error handling and recovery systems
* Real-time progress tracking with animated indicators
* Advanced batch processing for large catalogs
* Smart duplicate prevention using Ecwid IDs
* Auto-creation of missing WooCommerce attributes and terms

**Technical Improvements:**
* Organized asset structure (CSS/JS in dedicated folders)
* WordPress coding standards compliance
* Enhanced security with proper input validation
* Optimized memory usage and performance
* Improved API error handling and debugging
* Clean, well-documented codebase for maintainability

**User Experience:**
* Intuitive navigation between sync options
* Color-coded logging system for easy troubleshooting
* One-click connection testing
* Clear progress indicators and status messages
* Responsive design for all screen sizes

== Upgrade Notice ==

= 1.4.3 =
Enhanced reliability update! Adds exponential backoff retry logic, fixes retry counter bug in full sync, adds Cloudflare 524 timeout handling, and improves category batch loading. Recommended for all users experiencing intermittent 500 errors during sync.

= 1.4.2 =
Batch loading system for products and categories with automatic retry on transient errors. Improves reliability for large store migrations.

= 1.0.5 =
Critical reliability update! Fixes 500 errors during sync, adds dynamic batch sizing for faster category processing, and automatic SKU conflict resolution. Strongly recommended for users experiencing sync failures or timeouts.

= 1.0.4 =
Major performance update! Fixes browser freezing with large product catalogs through client-side pagination. Adds enhanced loading indicators, improved pagination controls, and support for 6000+ products. Recommended for all users, especially those with large Ecwid stores.

= 1.0.2 =
Recommended update. Adds multilingual-safe category slugs, category thumbnail import, improved error diagnostics, and multiple stability fixes including activation error resolution and correct success reporting after fallbacks.

= 1.0.0 =
First official release with a complete rewrite, professional UI, better performance, improved error handling, and WordPress standards compliance. Please backup your database before upgrading and test the connection after activation.

== Support ==

**Community Support:**
For general questions and community support, please use the plugin's support forum on WordPress.org.

**Documentation & Resources:**
Visit https://metrotechs.io for additional documentation, tutorials, and resources.

**Professional Support:**
For priority support, custom development, or enterprise solutions, contact us through https://metrotechs.io/contact.

== Technical Requirements ==

**Minimum Requirements:**
- WordPress 5.0 or higher
- WooCommerce 3.0 or higher
- PHP 7.2 or higher
- MySQL 5.6 or MariaDB 10.0
- Active Ecwid store with API access

**Recommended:**
- WordPress 6.0+
- WooCommerce 7.0+
- PHP 8.0+
- Adequate server memory (512MB+ for large catalogs)
- Reliable internet connection for API calls

== Privacy & Data Handling ==

This plugin:
- Accesses product, category, customer, and order data from your Ecwid store (based on API permissions)
- Does not collect or transmit personal user data beyond the migration process
- Stores Ecwid IDs in WordPress meta fields for sync tracking and duplicate prevention
- Customer and order data is only processed locally within your WordPress installation
- All API communications use secure HTTPS connections

== Changelog ==

= 1.3.5 - Critical Disabled Products Fix =
* **CRITICAL FIX** - Added check to skip disabled products that have incomplete data
* Fixed disabled products (enabled=false) being properly skipped instead of causing import failures
* Eliminated "[CRITICAL] Product missing Ecwid ID or SKU" errors for disabled products
* Performance improvement by skipping non-relevant disabled products
* Enhanced stability preventing sync failures due to incomplete disabled product data

= 1.3.4 - PHP Warning Resolution =
* **CRITICAL FIX** - Fixed "Undefined array key 'error_data'" PHP warning in error handling
* Enhanced error handling with consistent data structure for all API responses
* Improved stability eliminating PHP warnings in server error logs
* Better API error response handling throughout the plugin

= 1.3.3 - Product Sync Restoration =
* **CRITICAL FIX** - Removed 'enabled=true' filter that was causing product sync to be skipped
* Product sync now includes all products to match Ecwid behavior
* Enhanced debugging for empty API responses
* Resolved issue where sync would skip from categories directly to customers

= 1.3.2 - Server Performance Optimization =
* **CRITICAL FIX** - Reduced batch sizes to prevent server overload (Categories: 50→10, Products: 25→5)
* Added 1-2 second delays between AJAX requests to prevent rapid-fire errors
* Enhanced 404 error handling for AJAX endpoints
* Added 60-second timeout to AJAX requests
* Improved error detection and retry logic for server connectivity

= 1.3.1 - AJAX Handler Registration Fix =
* **CRITICAL FIX** - Added missing 'ecwid_wc_process_variation_batch' AJAX handler
* Fixed 404 errors in admin-ajax.php for variation processing
* Complete AJAX handler coverage for all sync functionality
* Resolved admin console errors for missing endpoints

= 1.3.0 - Gallery Image Import Fix =
* **CRITICAL FIX** - Fixed gallery images not importing during full sync
* Corrected API field mapping from 'hdUrl' to 'url' field
* Added comprehensive fallback support for gallery image URL detection
* Smart field selection with multiple fallback options
* Enhanced gallery image preservation logic

= 1.1.0+ - MAJOR UPDATE: Complete Migration Suite =

**New Features:**
* Customer Sync - Full customer import with profiles, addresses, and statistics
* Order Sync - Complete order history with automatic customer association
* Enhanced Admin Interface - Colorful gradient buttons and professional design
* Auto-loading Pages - All sync pages load data automatically on page visit

**Improvements:**
* Enhanced error handling with specific 403 permission guidance
* Improved JavaScript reliability (resolved i18n and sanitizeHTML scope issues)
* Smart customer-order association with multi-tier matching
* Professional UI consistency across all sync pages
* Better documentation with comprehensive setup guides

**Technical:**
* Moved utility functions to global scope for better accessibility
* Enhanced API error messages with actionable solutions
* Updated README with customer/order sync requirements
* Added troubleshooting documentation for common permission issues

= 1.1.0 - Enhanced Product Loading System =
* Major pagination improvements for large catalogs
* Support for stores with 6000+ products
* Advanced API call optimization
* Memory usage improvements

= 1.0.5 - Advanced Technical Features =
* Comprehensive error handling
* Enhanced debugging capabilities
* Performance optimizations
* Security improvements

= 1.0.0 - Initial Release =
* Core sync functionality for products and categories
* Professional admin interface
* AJAX-powered batch processing
* WordPress security best practices
- Does not modify your Ecwid store data (read-only access)
- Follows WordPress privacy best practices

== License ==

**Dual Licensing: GPLv2+ for Code, Trademark for Brand**

The Ecwid2Woo Product Sync plugin operates under a dual licensing model to provide both open-source freedom and brand protection:

**1. Plugin Code (Software): GNU General Public License v2.0 or later (GPLv2+)**
   - This plugin's PHP, JavaScript, and CSS code is licensed under the GPLv2+.
   - You are free to use, study, modify, and redistribute the code under the terms of the GPLv2+.
   - This ensures the plugin remains open-source and community-driven.
   - Full License Text: https://www.gnu.org/licenses/gpl-2.0.html

**2. Brand & Trademark: "Ecwid2Woo Product Sync"™**
   - The name "Ecwid2Woo Product Sync", the associated logo(s), and other brand assets are trademarks of Metrotechs.
   - These trademarks are protected to ensure users can identify official versions of the plugin and related services from Metrotechs.
   - Use of the trademark "Ecwid2Woo Product Sync" in any derivative works or services must comply with Metrotechs' brand guidelines and may require permission.

**Why this model?**
This approach allows us to offer the core software freely to the community while building a sustainable business around official services, premium extensions (if any in the future), 
and support, all under a recognizable and trusted brand.

While the plugin software is free, Metrotechs offers professional services such as premium support, custom development, and migration assistance. 
Please see the "Support" section for more details or visit https://metrotechs.io.

---
"Ecwid2Woo" is a trademark of Metrotechs. Ecwid, WordPress, and WooCommerce are trademarks of their respective owners.