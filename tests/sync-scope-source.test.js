'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');

const root = path.resolve(__dirname, '..');
const mainPlugin = fs.readFileSync(path.join(root, 'ecwid-to-woocommerce-sync.php'), 'utf8');
const fullSync = fs.readFileSync(path.join(root, 'full-sync-page.php'), 'utf8');
const productSync = fs.readFileSync(path.join(root, 'product-sync-page.php'), 'utf8');
const categorySync = fs.readFileSync(path.join(root, 'category-sync-page.php'), 'utf8');
const adminScript = fs.readFileSync(path.join(root, 'assets/js/admin-sync.js'), 'utf8');

assert.match(
    mainPlugin,
    /private \$sync_steps = \['categories', 'products'\];/,
    'localized full-sync steps must contain only shipped handlers'
);
assert.doesNotMatch(
    mainPlugin,
    /new Ecwid2Woo_(?:Customer|Order)_Sync/,
    'unshipped customer/order handlers must not be constructed'
);

const batchStart = fullSync.indexOf('public function ajax_batch_sync()');
const batchEnd = fullSync.indexOf('public function handle_sync_fatal_error()', batchStart);
const batchHandler = fullSync.slice(batchStart, batchEnd);

assert.match(batchHandler, /in_array\(\$sync_type, \$this->sync_steps, true\)/, 'batch types must use the supported allowlist');
assert.match(batchHandler, /Invalid sync type for full sync[\s\S]*?400/, 'unsupported batch types must return HTTP 400');
assert.doesNotMatch(batchHandler, /['"]\/(?:customers|orders)['"]/, 'batch handler must not call disabled API endpoints');
assert.doesNotMatch(batchHandler, /(?:customer|order)_sync_handler/, 'batch handler must not dispatch to disabled handlers');

const previewStart = fullSync.indexOf('public function ajax_fetch_full_sync_counts()');
const previewEnd = fullSync.indexOf('public function get_sync_steps()', previewStart);
const previewHandler = fullSync.slice(previewStart, previewEnd);
const previewEndpoints = previewHandler.match(/\$api_essentials\['base_url'\] \. '\/(?:categories|products)'/g) || [];

assert.equal(previewEndpoints.length, 2, 'full-sync preview must issue exactly two supported Ecwid requests');
assert.doesNotMatch(previewHandler, /\/(?:customers|orders)/, 'preview must not call disabled API endpoints');
assert.doesNotMatch(previewHandler, /(?:customers|orders)_(?:count|preview|api_status|url)/, 'preview response must not expose disabled data');
assert.match(previewHandler, /items\(id,name\),total/, 'category preview must request only rendered fields');
assert.match(previewHandler, /items\(id,sku,name,enabled,price,combinations\(id,sku\)\),total/, 'product preview must request only rendered fields');
assert.match(previewHandler, /get_transient\(\$preview_cache_key\)/, 'preview must reuse its short-lived cache');
assert.match(previewHandler, /set_transient\(\$preview_cache_key, \$response_data/, 'successful previews must populate the cache');
assert.match(mainPlugin, /sanitize_options[\s\S]*?delete_transient\('ecwid2woo_full_sync_preview'\)/, 'settings changes must invalidate preview data');
assert.match(batchHandler, /\$sync_type === 'products' && !\$has_more[\s\S]*?delete_transient\('ecwid2woo_full_sync_preview'\)/, 'full sync completion must invalidate preview data');
assert.match(previewHandler, /\$current_bytes !== -1 && \$current_bytes < \$minimum_bytes/, 'unlimited PHP memory must satisfy preview requirements');
assert.match(batchHandler, /\$memory_in_bytes !== -1 && \$memory_in_bytes < \$minimum_memory/, 'unlimited PHP memory must satisfy batch requirements');
assert.match(batchHandler, /\$available_memory === -1 \? PHP_INT_MAX/, 'unlimited PHP memory must not trigger adaptive low-memory reduction');
assert.match(batchHandler, /shipping\),total,count,offset,limit/, 'product batches must request pagination metadata');
assert.match(batchHandler, /updateTimestamp\),total,count,offset,limit/, 'category batches must request pagination metadata');

const productImportStart = productSync.indexOf('public function import_product(');
const productImportEnd = productSync.indexOf('private function handle_product_images(', productImportStart);
const productImport = productSync.slice(productImportStart, productImportEnd);
assert.match(productSync, /normalize_source_value_for_hash/, 'product payload hashing must normalize object key order');
assert.match(productSync, /hash\('sha256', \$encoded\)/, 'product change detection must use a deterministic SHA-256 fingerprint');
assert.match(productImport, /get_post_meta\(\$product_id, '_ecwid_source_hash'/, 'existing products must compare their stored source fingerprint');
assert.match(productImport, /hash_equals\(\$stored_source_hash, \$source_hash\)/, 'fingerprints must be compared safely');
assert.doesNotMatch(productImport, /hours_since_import|within last 24 hours/, 'recent imports must never be guessed unchanged');

const successfulImportPosition = productImport.indexOf("'status' => 'imported'");
const sourceHashWritePosition = productImport.indexOf("update_post_meta($product_id, '_ecwid_source_hash'");
const variationPosition = productImport.indexOf('handle_product_variations(');
assert.ok(sourceHashWritePosition > variationPosition, 'source fingerprint must be stored after variation processing succeeds');
assert.ok(sourceHashWritePosition < successfulImportPosition, 'source fingerprint must be stored before reporting success');

const categoryImportStart = categorySync.indexOf('public function import_category(');
const categoryImportEnd = categorySync.indexOf('private function handle_category_image_import(', categoryImportStart);
const categoryImport = categorySync.slice(categoryImportStart, categoryImportEnd);
assert.match(categoryImport, /get_term_meta\(\$existing_wc_term_id_by_ecwid_meta, '_ecwid_source_hash'/, 'categories must compare their stored source fingerprint');
assert.match(categoryImport, /hash_equals\(\$stored_source_hash, \$source_hash\)/, 'category fingerprints must be compared safely');
assert.doesNotMatch(categoryImport, /hours_since_import|within last 24 hours/, 'category imports must never be guessed unchanged');
assert.match(categoryImport, /update_term_meta\([^;]+_ecwid_source_hash/, 'successful category imports must store their source fingerprint');

const previewScriptStart = adminScript.indexOf('function loadAndDisplayFullSyncPreview()');
const previewScriptEnd = adminScript.indexOf('// Load preview when button is clicked', previewScriptStart);
const previewScript = adminScript.slice(previewScriptStart, previewScriptEnd);

assert.doesNotMatch(previewScript, /(?:customers|orders)_(?:count|preview)/, 'browser preview must ignore disabled data');
assert.match(adminScript, /const supportedFullSyncSteps = \['categories', 'products'\];/, 'browser must maintain a supported-step allowlist');

console.log('sync scope source invariants passed');
