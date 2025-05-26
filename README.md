# Ecwid2Woo Product Sync

**Ecwid2Woo Product Sync** is a professional WordPress plugin that provides seamless synchronization between your Ecwid store and WooCommerce. With a modern, user-friendly interface and robust batch processing capabilities, it's the ideal solution for migrating from Ecwid to WooCommerce or maintaining synchronized product catalogs across both platforms.

---

## 🚀 Key Features

### 💼 Professional Admin Interface
- **Modern, Responsive Design** - Clean, intuitive interface that works perfectly on all devices
- **Real-time Progress Tracking** - Animated progress bars and live status updates
- **Visual Connection Testing** - One-click API connection verification with instant feedback
- **Comprehensive Logging** - Color-coded logs with detailed operation tracking
- **Smart Navigation** - Seamless transitions between different sync options

### 🔄 Multiple Sync Options
- **Full Sync** - Complete catalog migration with preview capabilities
- **Category Sync** - Independent category import with hierarchy management
- **Selective Product Sync** - Choose specific products for targeted import/updates
- **Batch Processing** - Smart chunking prevents server timeouts on large catalogs

### 📊 Complete Data Synchronization
- **Product Information** - Names, SKUs, descriptions, prices, stock levels, dimensions, weight
- **Category Hierarchies** - Full parent-child relationships preserved
- **Product Variations** - Complete support for variable products with all option combinations
- **Image Management** - Featured images, galleries, and variation-specific images
- **Inventory Data** - Stock status, quantities, and unlimited stock settings

### ⚡ Advanced Technical Features
- **AJAX-Powered Processing** - Non-blocking operations with real-time feedback
- **Memory Optimization** - Efficient handling of large product catalogs
- **Smart Duplicate Prevention** - Uses Ecwid IDs and SKU matching to avoid duplicates
- **Auto-Recovery Systems** - Handles API timeouts and connection issues gracefully
- **WordPress Standards Compliant** - Follows all WordPress coding and security best practices

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
2. Enter your **Ecwid Store ID** and **API Secret Token**
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
   - Click **Create new token** or **Generate Secret Token**
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
- **Category Preview** - See all categories before importing
- **Hierarchy Preservation** - Maintains parent-child relationships
- **Hierarchy Fix Tool** - Resolves any structural issues
- **Independent Operation** - Sync categories without touching products

**How to Use:**
1. Click **Reload Ecwid Categories** to preview
2. Review the category list and hierarchy
3. Click **Start Category Sync** to import
4. Use **Fix Category Hierarchy** if needed after sync

### 🎯 Selective Product Sync
**Location:** `Ecwid2Woo Sync → Product Sync`

**Perfect for:** Targeted imports, testing, or specific product updates

**Features:**
- **Product Selection Interface** - Choose exactly which products to sync
- **Bulk Selection Tools** - Select all or none with one click
- **Variation Support** - Handles complex variable products
- **Individual Progress Tracking** - Monitor each selected product

**How to Use:**
1. Click **Reload Products** to load available products
2. Select individual products or use **Select All/None**
3. Click **Import Selected Products** to begin
4. Monitor individual product progress

### 📋 Placeholders Management
**Location:** `Ecwid2Woo Sync → Placeholders`

**Purpose:** Review and manage temporary placeholder items created during sync

- View placeholder categories created for missing parents
- Clean up temporary items after hierarchy fixes
- Monitor sync-related administrative data

---

## 🔧 Advanced Features

### 🔄 Variation Processing
- **Automatic Attribute Creation** - Missing WooCommerce attributes auto-generated
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

**"Ecwid2Woo Product Sync" is a trademark of Metrotechs. WordPress and WooCommerce are trademarks of their respective owners.**

---

## 🎯 Version Information

**Current Version:** 1.0.0  
**Compatibility:** WordPress 5.0+ | WooCommerce 3.0+ | PHP 7.2+  
**Release Date:** 5/25/2025  
**Update Policy:** Regular updates for compatibility and feature enhancements

---

*Transform your e-commerce platform with confidence. Ecwid2Woo Product Sync makes store migration and synchronization straightforward, reliable, and professional.*
