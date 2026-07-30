'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');

const root = path.resolve(__dirname, '..');
const mainPhp = fs.readFileSync(path.join(root, 'ecwid-to-woocommerce-sync.php'), 'utf8');
const categoryPhp = fs.readFileSync(path.join(root, 'category-sync-page.php'), 'utf8');

function getPublicMethod(source, methodName) {
    const start = source.indexOf(`public function ${methodName}(`);
    assert.ok(start >= 0, `${methodName} must be present`);
    const next = source.indexOf('public function ', start + 20);
    return source.slice(start, next >= 0 ? next : source.length);
}

assert.match(
    categoryPhp,
    /\$should_skip\s*&&\s*\$current_term_data\s*&&\s*\(int\) \$current_term_data->parent !== \(int\) \$parent_wc_term_id/s,
    'Smart Skip must not bypass a WooCommerce parent mismatch'
);

for (const source of [categoryPhp, mainPhp]) {
    const repairMethod = getPublicMethod(source, 'fix_category_hierarchy');

    assert.match(
        repairMethod,
        /\$remaining_missing_parents\s*=\s*\[\]/,
        'hierarchy repair must track unresolved relationships'
    );
    assert.match(
        repairMethod,
        /\$remaining_missing_parents\[\$parent_ecwid_id\]\s*=\s*\$child_ecwid_ids/,
        'missing parents must remain queued'
    );
    assert.match(
        repairMethod,
        /\$remaining_missing_parents\[\$parent_ecwid_id\]\[\]\s*=\s*\$child_ecwid_id/,
        'missing or failed child repairs must remain queued'
    );
    assert.match(
        repairMethod,
        /update_option\('ecwid_wc_sync_missing_parents', \$remaining_missing_parents\)/,
        'only resolved hierarchy entries may be removed'
    );
    assert.doesNotMatch(
        repairMethod,
        /update_option\('ecwid_wc_sync_missing_parents', \[\]\)/,
        'hierarchy repair must not erase unresolved state'
    );
}

console.log('category hierarchy source invariants passed');
