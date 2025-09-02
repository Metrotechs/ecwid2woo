# Customer & Order Sync Setup Guide

## 🚨 Common Issue: HTTP 403 Error

If you're seeing a **"HTTP 403"** error when trying to use Customer Sync or Order Sync, this means your API token doesn't have the required permissions.

### Quick Fix:

1. **Go to your Ecwid Dashboard**
   - Log into your Ecwid account
   - Navigate to **Apps → My Apps → API**

2. **Create a New API Token**
   - Click **"Create new token"** or **"Generate Token"**
   - **IMPORTANT**: Make sure to check these permissions:
     
     **✅ Required for Basic Sync:**
     - Read catalog
     - Read store profile  
     - Read products
     - Read categories
     
     **✅ Required for Customer Sync:**
     - **Read customers** ← This is essential!
     
     **✅ Required for Order Sync:**
     - **Read orders** ← This is essential!

3. **Update Plugin Settings**
   - Copy the new API token
   - Go to your WordPress admin → Ecwid Sync → Settings
   - Paste the new token and click **"Save Settings"**
   - Click **"Test Connection"** to verify

## Why This Happens

The **Customer** and **Order** APIs in Ecwid require special permissions that are separate from the basic product/category permissions. Many users initially set up tokens with only catalog permissions, which work fine for products and categories but fail when accessing customer or order data.

## Verification Steps

1. **Test Connection** - Should show "Connection successful!"
2. **Try Customer Sync** - Should load customers without 403 errors
3. **Try Order Sync** - Should load orders without 403 errors

## Still Having Issues?

If you're still getting 403 errors after generating a new token with customer/order permissions:

1. **Double-check permissions** - Make sure "Read customers" and "Read orders" are checked
2. **Wait a few minutes** - Sometimes API permissions take a moment to propagate
3. **Clear cache** - If using any caching plugins, clear them
4. **Try a different browser** - Sometimes browser cache can interfere

---

💡 **Pro Tip**: Keep your old API token as a backup until you confirm the new one works correctly!
