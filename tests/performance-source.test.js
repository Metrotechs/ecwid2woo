'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');

const root = path.resolve(__dirname, '..');
const plugin = fs.readFileSync(path.join(root, 'ecwid-to-woocommerce-sync.php'), 'utf8');
const fullSync = fs.readFileSync(path.join(root, 'full-sync-page.php'), 'utf8');
const productSync = fs.readFileSync(path.join(root, 'product-sync-page.php'), 'utf8');

const dependencyStart = plugin.indexOf('function ecwid2woo_check_woocommerce_dependency()');
const contextGuard = plugin.indexOf('if (!ecwid2woo_is_admin_sync_context())', dependencyStart);
const pluginConstruction = plugin.indexOf('new Ecwid_WC_Sync();', dependencyStart);
const dependencyEnd = plugin.indexOf('function ecwid2woo_woocommerce_missing_notice()', dependencyStart);

assert.notEqual(dependencyStart, -1, 'WooCommerce dependency bootstrap must exist');
assert.ok(
    contextGuard > dependencyStart && contextGuard < pluginConstruction,
    'admin context guard must run before plugin construction'
);
assert.ok(
    pluginConstruction < dependencyEnd,
    'plugin construction must remain inside the guarded dependency bootstrap'
);

const contextStart = plugin.indexOf('function ecwid2woo_is_admin_sync_context()');
const contextEnd = plugin.indexOf('function ecwid2woo_woocommerce_missing_notice()', contextStart);
const contextBody = plugin.slice(contextStart, contextEnd);

assert.match(contextBody, /is_admin\(\)/, 'normal WordPress admin requests must load the sync runtime');
assert.match(contextBody, /wp_doing_ajax\(\)/, 'WordPress AJAX requests must load the sync runtime');

const classStart = plugin.indexOf('class Ecwid_WC_Sync');
for (const handlerFile of [
    'category-sync-page.php',
    'product-sync-page.php',
    'full-sync-page.php',
]) {
    const requirePosition = plugin.indexOf(`require_once plugin_dir_path(__FILE__) . '${handlerFile}';`);
    assert.ok(
        requirePosition > classStart,
        `${handlerFile} must not be required before the guarded plugin class is constructed`
    );
}

assert.match(plugin, /function start_sync_request_deadline\(/, 'bulk requests must share a bounded wall-clock deadline');
assert.match(plugin, /function is_sync_request_deadline_near\(/, 'handlers must reserve time for cleanup and JSON output');
assert.match(fullSync, /make_api_request_with_retry\([^;]+20, \$request_deadline\)/s, 'full sync API retries must honor the request deadline');
assert.match(fullSync, /\$sync_type === 'categories' && \$offset === 0/, 'store currency must sync only once at the start of a full sync');
assert.doesNotMatch(fullSync, /\$batch_start_time = microtime\(true\)/, 'full sync deadline must include API time instead of resetting before imports');
assert.match(productSync, /\$time_limit_hit \? \$offset \+ \$items_actually_processed/, 'product-only sync must resume from the last safely processed item');
assert.match(plugin, /server_tier'\] === 'low'[\s\S]*?min\(10,/, 'low-resource servers must use small variation batches');

console.log('performance source invariants passed');
