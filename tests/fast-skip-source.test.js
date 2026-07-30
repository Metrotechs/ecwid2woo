'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');

const root = path.resolve(__dirname, '..');
const fullSyncPhp = fs.readFileSync(path.join(root, 'full-sync-page.php'), 'utf8');
const productPhp = fs.readFileSync(path.join(root, 'product-sync-page.php'), 'utf8');
const categoryPhp = fs.readFileSync(path.join(root, 'category-sync-page.php'), 'utf8');
const adminJs = fs.readFileSync(path.join(root, 'assets', 'js', 'admin-sync.js'), 'utf8');

for (const handler of [productPhp, categoryPhp]) {
    assert.match(handler, /public function get_source_hash_for_sync\(\$item\)/, 'sync handlers must expose their canonical source fingerprint');
}

assert.match(fullSyncPhp, /\$fast_skip_scan_cap\s*=\s*absint\(apply_filters\('ecwid2woo_fast_skip_scan_limit',\s*100/, 'Fast Skip must scan up to 100 source items');
assert.match(fullSyncPhp, /\$requested_scan_size\s*=\s*isset\(\$_POST\['scan_size'\]\)/, 'the server must accept the browser adaptive scan size');
assert.match(fullSyncPhp, /min\(\$fast_skip_scan_cap,\s*\$requested_scan_size\)/, 'requested scan windows must remain server-capped');
assert.match(fullSyncPhp, /\$mutation_limit_per_request\s*=\s*absint\(apply_filters\('ecwid_wc_sync_batch_api_limit'/, 'actual mutations must retain the safe adaptive batch limit');
assert.match(fullSyncPhp, /LEFT JOIN \{\$wpdb->postmeta\} hash_meta/, 'product hashes must be bulk loaded');
assert.match(fullSyncPhp, /LEFT JOIN \{\$wpdb->termmeta\} hash_meta/, 'category hashes must be bulk loaded');
assert.match(fullSyncPhp, /'fast_skip'\s*=>\s*true/g, 'Fast Skip results must be explicitly identified');
assert.match(fullSyncPhp, /\$time_limit_hit \|\| \$mutation_limit_hit/, 'partial scan windows must preserve exact continuation offsets');
assert.match(fullSyncPhp, /'fast_skipped_count'\s*=>\s*\$fast_skipped_count/, 'the browser must receive Fast Skip counts');
assert.match(fullSyncPhp, /'scan_size_used'\s*=>\s*\$fast_skip_scan_limit/, 'the response must report its comparison window');
assert.doesNotMatch(fullSyncPhp, /\$items_from_api\s*=\s*\$this->sort_categories_parents_first/, 'positional Full Sync API windows must not be reordered');

assert.match(adminJs, /fastSkipOnly:\s*false/, 'browser continuation state must track all-Fast-Skip windows');
assert.match(adminJs, /fastSkipScanSizes:\s*\{ categories:\s*100, products:\s*100 \}/, 'repeat syncs must begin with wide comparison windows');
assert.match(adminJs, /scan_size:\s*adaptiveBatchConfig\.fastSkipScanSizes\[syncType\]/, 'each Full Sync request must send its adaptive scan size');
assert.match(adminJs, /fastSkipScanSizes\[syncType\]\s*=\s*fullSyncParentContinuation\.fastSkipOnly[\s\S]*\? serverScanSize[\s\S]*:\s*currentBatchSize/, 'changed windows must fall back to the safe mutation-sized scan');
assert.match(adminJs, /response\.data\.fast_skipped_count === processedThisBatch/, 'the browser must distinguish fully unchanged windows');
assert.match(adminJs, /const continuationDelayMs = fullSyncParentContinuation\.fastSkipOnly\s*\?\s*500/s, 'all-Fast-Skip windows must use the short cooldown');
assert.match(adminJs, /:\s*adaptiveBatchConfig\.batchDelayMs/, 'mutation batches must keep the detected server cooldown');

console.log('fast skip source invariants passed');
