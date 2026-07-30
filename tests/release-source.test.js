'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');

const root = path.resolve(__dirname, '..');
const plugin = fs.readFileSync(path.join(root, 'ecwid-to-woocommerce-sync.php'), 'utf8');
const readme = fs.readFileSync(path.join(root, 'readme.txt'), 'utf8');
const buildScript = fs.readFileSync(path.join(root, 'scripts', 'build-release.ps1'), 'utf8');

const pluginVersion = plugin.match(/^Version:\s*([^\r\n]+)/m)?.[1];
const stableTag = readme.match(/^Stable tag:\s*([^\r\n]+)/m)?.[1];
assert.equal(pluginVersion, '1.6.2', 'main plugin header must carry the release version');
assert.equal(stableTag, pluginVersion, 'WordPress stable tag must match the plugin header');
assert.match(plugin, /^WC tested up to:\s*10\.9$/m, 'WooCommerce compatibility must reflect the staging-tested release');
assert.match(readme, /^Tested up to:\s*7\.0$/m, 'WordPress compatibility must reflect the staging-tested release');
assert.doesNotMatch(readme, /Conservative 24-hour skip rule/i, 'public release documentation must not describe the removed unsafe skip heuristic as current behavior');
assert.match(buildScript, /\$releaseFiles = @\(/, 'release package must be built from an explicit file allowlist');
assert.match(buildScript, /\.Replace\('\\',\s*'\/'\)/, 'release archive entries must normalize Windows path separators');
assert.ok(buildScript.includes(".Contains('\\')"), 'release validation must reject non-portable backslash entry names');
for (const forbidden of ['.git', 'tests', 'scripts', 'SECURITY-PERFORMANCE-IMPLEMENTATION-PLAN.md']) {
    assert.ok(buildScript.includes(`'${forbidden}'`), `release validation must reject ${forbidden}`);
}
assert.match(buildScript, /Get-FileHash[^\r\n]+SHA256/, 'release workflow must generate a SHA-256 checksum');

console.log('release source invariants passed');
