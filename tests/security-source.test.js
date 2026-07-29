'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');

const root = path.resolve(__dirname, '..');
const mainPhp = fs.readFileSync(path.join(root, 'ecwid-to-woocommerce-sync.php'), 'utf8');
const categoryPhp = fs.readFileSync(path.join(root, 'category-sync-page.php'), 'utf8');
const productPhp = fs.readFileSync(path.join(root, 'product-sync-page.php'), 'utf8');
const fullSyncPhp = fs.readFileSync(path.join(root, 'full-sync-page.php'), 'utf8');
const adminJs = fs.readFileSync(path.join(root, 'assets/js/admin-sync.js'), 'utf8');

function getPublicMethod(source, methodName) {
    const start = source.indexOf(`public function ${methodName}(`);
    assert.ok(start >= 0, `${methodName} must be present`);
    const next = source.indexOf('public function ', start + 20);
    return source.slice(start, next >= 0 ? next : source.length);
}

const activeAjaxHandlers = [
    [mainPhp, 'ajax_test_api_connection'],
    [mainPhp, 'ajax_diagnose_uploads'],
    [mainPhp, 'ajax_debug_info'],
    [mainPhp, 'ajax_process_variation_batch'],
    [mainPhp, 'ajax_release_sync_lock'],
    [categoryPhp, 'fix_category_hierarchy'],
    [categoryPhp, 'ajax_fetch_categories_for_display'],
    [categoryPhp, 'ajax_import_selected_categories'],
    [categoryPhp, 'ajax_sync_all_categories'],
    [categoryPhp, 'ajax_batch_category_sync'],
    [categoryPhp, 'ajax_get_category_count'],
    [productPhp, 'ajax_fetch_products_for_selection'],
    [productPhp, 'ajax_import_selected_products'],
    [productPhp, 'ajax_sync_all_products'],
    [fullSyncPhp, 'ajax_batch_sync'],
    [fullSyncPhp, 'ajax_fetch_full_sync_counts'],
];

for (const [source, methodName] of activeAjaxHandlers) {
    const method = getPublicMethod(source, methodName);
    assert.match(method, /check_ajax_referer|wp_verify_nonce/, `${methodName} must verify a nonce`);
    assert.match(method, /current_user_can\('manage_options'\)/, `${methodName} must require manage_options`);
    assert.match(method, /wp_send_json_error\([^;]+,\s*403\s*\)/s, `${methodName} must return HTTP 403 when unauthorized`);
}

assert.doesNotMatch(mainPhp, /\?token=/, 'API tokens must not be placed in URLs');
assert.doesNotMatch(mainPhp, /get_option\(['"]ecwid_wc_(?:store_id|api_token)['"]\)/, 'legacy credential options must not be read at runtime');
assert.match(mainPhp, /'Authorization'\s*=>\s*'Bearer '\s*\.\s*\$api_essentials\['token'\]/, 'canonical bearer authentication must remain in use');

const categoryHandlerStart = categoryPhp.indexOf('public function ajax_sync_all_categories()');
const categoryCredentialCheck = categoryPhp.indexOf('// Check for required API credentials', categoryHandlerStart);
assert.ok(categoryHandlerStart >= 0 && categoryCredentialCheck > categoryHandlerStart, 'category sync handler must be discoverable');
const categoryAuthorization = categoryPhp.slice(categoryHandlerStart, categoryCredentialCheck);
assert.match(categoryAuthorization, /wp_verify_nonce|check_ajax_referer/, 'category sync must verify a nonce');
assert.match(categoryAuthorization, /current_user_can\('manage_options'\)/, 'category sync must require manage_options');
assert.match(categoryAuthorization, /wp_send_json_error\([^;]+,\s*403\s*\)/s, 'category sync must return HTTP 403 when unauthorized');

assert.match(mainPhp, /wp_safe_remote_get\(/, 'image downloads must use the safe WordPress HTTP client');
assert.match(mainPhp, /'limit_response_size'\s*=>\s*\$max_bytes\s*\+\s*1/, 'image downloads must enforce a streaming byte limit');
assert.match(mainPhp, /'redirection'\s*=>\s*0/, 'image redirects must be handled explicitly');
assert.match(mainPhp, /validate_remote_image_url\(\$current_url,\s*\$allowed_hosts\)/, 'every image redirect destination must be revalidated');
assert.match(mainPhp, /wp_get_image_mime\(\$tmp\)/, 'downloaded images must be inspected by content');

assert.match(adminJs, /name:\s*sanitizeHTML\(String\(product\.name/, 'product names must be escaped before HTML rendering');
assert.match(adminJs, /sku:\s*sanitizeHTML\(String\(product\.sku/, 'product SKUs must be escaped before HTML rendering');
assert.match(adminJs, /name:\s*sanitizeHTML\(String\(category\.name/, 'category names must be escaped before HTML rendering');
assert.match(adminJs, /escapeObjectStrings\(response\.data\.diagnostics/, 'diagnostic strings must be recursively escaped');
assert.doesNotMatch(adminJs, /\.html\([^\n]*\+\s*response\.data\.message/, 'AJAX messages must not be concatenated into HTML without escaping');

assert.match(mainPhp, /add_option\(\$option_name, \$new_value, '', false\)/, 'sync lock acquisition must use atomic option insertion');
assert.match(mainPhp, /UPDATE \{\$wpdb->options\} SET option_value = %s WHERE option_name = %s AND option_value = %s/, 'stale sync locks must use atomic compare-and-swap');
assert.match(mainPhp, /DELETE FROM \{\$wpdb->options\} WHERE option_name = %s AND option_value = %s/, 'sync lock release must verify the current owner value atomically');
assert.match(mainPhp, /add_action\('wp_ajax_' \. \$locked_ajax_action, \[\$this, 'ajax_enforce_sync_lock'\], 1\)/, 'catalog mutation handlers must enforce the lock before processing');
assert.match(mainPhp, /current_user_can\('manage_options'\)/, 'sync lock endpoints must require administrative capability');
assert.match(adminJs, /function startSyncJob\(scope\)/, 'browser sync workflows must create a job-scoped lock owner');
assert.match(adminJs, /action: 'ecwid_wc_release_sync_lock'/, 'browser sync workflows must explicitly release completed jobs');
assert.match(adminJs, /sync_id: getSyncJobId\('full-sync'\)/, 'full sync mutation requests must send their lock owner');
assert.match(adminJs, /sync_id: getSyncJobId\('all-products'\)/, 'bulk product mutation requests must send their lock owner');
assert.match(adminJs, /sync_id: getSyncJobId\('all-categories'\)/, 'bulk category mutation requests must send their lock owner');

console.log('security source invariants passed');
