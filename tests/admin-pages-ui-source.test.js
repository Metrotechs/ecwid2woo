const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');

const root = path.resolve(__dirname, '..');
const category = fs.readFileSync(path.join(root, 'category-sync-page.php'), 'utf8');
const product = fs.readFileSync(path.join(root, 'product-sync-page.php'), 'utf8');
const plugin = fs.readFileSync(path.join(root, 'ecwid-to-woocommerce-sync.php'), 'utf8');
const css = fs.readFileSync(path.join(root, 'assets/css/admin-styles.css'), 'utf8');
const js = fs.readFileSync(path.join(root, 'assets/js/admin-sync.js'), 'utf8');

const pageContracts = [
    {
        name: 'Category Sync',
        source: category,
        dashboardClass: 'e2w-category-dashboard',
        ids: [
            'load-ecwid-categories-button',
            'selective-category-list-container',
            'category-pagination-container',
            'import-selected-categories-button',
            'sync-all-categories-button',
            'stop-sync-categories-button',
            'selective-sync-initial-info',
            'selective-sync-status',
            'selective-sync-progress-container',
            'selective-sync-bar',
            'selective-sync-log'
        ]
    },
    {
        name: 'Product Sync',
        source: product,
        dashboardClass: 'e2w-product-dashboard',
        ids: [
            'load-ecwid-products-button',
            'selective-product-list-container',
            'product-pagination-container',
            'import-selected-products-button',
            'sync-all-products-button',
            'stop-sync-products-button',
            'selective-sync-initial-info',
            'selective-sync-status',
            'selective-sync-progress-container',
            'selective-sync-bar',
            'selective-sync-log'
        ]
    }
];

for (const page of pageContracts) {
    assert.match(page.source, new RegExp(page.dashboardClass), `${page.name} dashboard wrapper is missing`);
    for (const id of page.ids) {
        const matches = page.source.match(new RegExp(`id="${id}"`, 'g')) || [];
        assert.equal(matches.length, 1, `${page.name}: ${id} must appear exactly once`);
        assert.match(js, new RegExp(`#${id}`), `${page.name}: ${id} is not connected to the admin controller`);
    }
}

for (const id of [
    'ecwid-settings-form',
    'test-api-connection',
    'upload-diagnostics-button',
    'test-connection-result',
    'save-status',
    'upload-diagnostics-result',
    'e2w-settings-state'
]) {
    const matches = plugin.match(new RegExp(`id="${id}"`, 'g')) || [];
    assert.equal(matches.length, 1, `Settings: ${id} must appear exactly once`);
}

assert.match(plugin, /e2w-settings-dashboard/);
assert.doesNotMatch(plugin, /jQuery\(document\)\.ready\(function\(\$\)/, 'duplicate inline Settings handlers must not return');
assert.match(css, /Shared selective-sync and settings dashboards/);
assert.match(css, /\.e2w-selection-panel/);
assert.match(css, /\.e2w-settings-form/);
assert.match(js, /function renderSelectiveNotice\(state, title, detail, extraHtml = ''\)/);
assert.match(js, /function setSettingsState\(state, label\)/);
const categoryImportBindings = js.match(/(?:\$\('#import-selected-categories-button'\)|importSelectedCategoriesButton)\.on\('click'/g) || [];
assert.equal(categoryImportBindings.length, 1, 'Category Import Selected must have exactly one click handler');
assert.match(js, /Array\.from\(selectedCategoryIds, id => Number\.parseInt\(id, 10\)\)/);

console.log('remaining admin page UI source invariants passed');
