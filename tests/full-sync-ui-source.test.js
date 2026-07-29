const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');

const root = path.resolve(__dirname, '..');
const php = fs.readFileSync(path.join(root, 'full-sync-page.php'), 'utf8');
const plugin = fs.readFileSync(path.join(root, 'ecwid-to-woocommerce-sync.php'), 'utf8');
const css = fs.readFileSync(path.join(root, 'assets/css/admin-styles.css'), 'utf8');
const js = fs.readFileSync(path.join(root, 'assets/js/admin-sync.js'), 'utf8');

const requiredLegacyIds = [
    'full-sync-button',
    'pause-full-sync-button',
    'stop-full-sync-button',
    'load-full-sync-preview-button',
    'full-sync-bar',
    'full-sync-status',
    'full-sync-log',
    'full-sync-counts-info',
    'full-sync-preview-container',
    'full-sync-category-preview-list',
    'full-sync-product-preview-list',
    'full-sync-progress-container',
    'full-sync-initial-info'
];

for (const id of requiredLegacyIds) {
    const matches = php.match(new RegExp(`id="${id}"`, 'g')) || [];
    assert.equal(matches.length, 1, `${id} must remain present exactly once`);
    assert.match(js, new RegExp(`#${id}`), `${id} must remain connected to the JavaScript controller`);
}

for (const dashboardId of [
    'e2w-sync-state',
    'e2w-category-count',
    'e2w-product-count',
    'e2w-log-search',
    'e2w-auto-scroll',
    'e2w-download-log',
    'e2w-current-item-name'
]) {
    assert.match(php, new RegExp(`id="${dashboardId}"`), `${dashboardId} dashboard control is missing`);
}

assert.match(css, /\.ecwid-full-sync-dashboard\s*\{/);
assert.match(css, /@media \(max-width: 782px\)/);
assert.match(css, /@media \(prefers-reduced-motion: reduce\)/);
assert.match(js, /function refreshFullSyncLogView\(\)/);
assert.match(js, /function updateCurrentFullSyncItem\(itemResult, syncType\)/);
assert.match(js, /new Blob\(\[lines\.join\('\\n'\)/);
assert.match(plugin, /\$css_version\s*=\s*ECWID2WOO_VERSION\s*\.\s*'\.'/);
assert.match(plugin, /filemtime\(plugin_dir_path\(__FILE__\)\s*\.\s*'assets\/css\/admin-styles\.css'\)/);

console.log('full sync UI source invariants passed');
