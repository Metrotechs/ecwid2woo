=== Ecwid2Woo Product Sync ===
Contributors: Metrotechs, Richard Hunting
Donate link: https://metrotechs.io/donate
Tags: ecwid, woocommerce, sync, products, categories, import, migration, ecwid sync, woocommerce sync, product import, category import, product sync, category sync, ecwid to woocommerce, woocommerce ecwid
Requires at least: 5.0
Tested up to: 6.8
Requires PHP: 7.2
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
WC requires at least: 3.0
WC tested up to: 9.2

Easily sync Ecwid product data (products, categories, images, SKUs, variations) to WooCommerce with a professional, user-friendly interface.

== Description ==

Ecwid2Woo Product Sync is a comprehensive WordPress plugin designed for seamlessly migrating and synchronizing your Ecwid product catalog with WooCommerce.
Whether you're transitioning from Ecwid to WooCommerce or maintaining dual platforms, this plugin provides a robust, reliable solution for product data transfer.

**Perfect for:**
- Store owners migrating from Ecwid to WooCommerce
- Businesses maintaining both Ecwid and WooCommerce stores
- Developers managing client store migrations
- Anyone needing reliable product data synchronization

== Key Features ==

**🔄 Complete Product Synchronization**
- Transfer products, categories, images, prices, SKUs, stock levels, and variations
- Preserve complex category hierarchies and parent-child relationships
- Handle both simple and variable products with full option support
- Auto-create missing WooCommerce attributes and terms

**🎯 Multiple Sync Options**
- **Full Sync:** Complete catalog migration with preview capabilities
- **Category Sync:** Import categories independently with hierarchy management
- **Selective Sync:** Choose specific products for targeted import/updates

**🚀 Professional User Interface**
- Modern, responsive admin interface with intuitive navigation
- Real-time progress tracking with animated status indicators
- Comprehensive logging system with color-coded messages
- One-click connection testing with visual feedback

**⚡ Advanced Technical Features**
- AJAX-powered batch processing prevents server timeouts
- Idempotent operations prevent duplicate entries
- Memory-optimized for large catalogs
- Smart matching system using Ecwid IDs and SKU fallbacks

**🛡️ Reliable & Safe**
- Stop sync functionality for user control
- Comprehensive error handling and recovery
- Detailed logging for troubleshooting
- WordPress security best practices

**🔧 Developer Friendly**
- Clean, well-documented code structure
- Organized asset management (CSS/JS separation)
- WordPress coding standards compliant
- Extensible architecture for customization

== What's New in Version 1.0.0 ==

This is the first official release featuring a completely rewritten codebase with:

- **Professional UI/UX:** Modern, responsive interface with enhanced visual design
- **Modular Architecture:** Clean separation of PHP, CSS, and JavaScript for better maintainability
- **Enhanced Performance:** Optimized asset loading and improved memory management
- **Better Error Handling:** Comprehensive error reporting with user-friendly messages
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

== Screenshots ==

1. **Settings Page** - Configure Ecwid API credentials with connection testing
2. **Full Sync Interface** - Complete catalog synchronization with progress tracking
3. **Category Sync** - Dedicated category import with hierarchy management
4. **Product Sync** - Selective product import with filtering options
5. **Sync in Progress** - Real-time progress bars and detailed logging
6. **Navigation Interface** - Modern, intuitive admin navigation

== Changelog ==

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

= 1.0.0 =
This is the first official release featuring a complete rewrite with professional UI, enhanced performance, better error handling, and improved WordPress standards compliance. If you've been using a previous version, please backup your database before upgrading and test the connection after activation.

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
- Only accesses product and category data from your Ecwid store
- Does not collect or transmit personal user data
- Stores Ecwid IDs in WordPress meta fields for sync tracking
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