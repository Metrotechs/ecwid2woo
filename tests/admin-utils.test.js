'use strict';

const assert = require('node:assert/strict');
const utils = require('../assets/js/admin-utils.js');

const fixtures = [
    ['<img src=x onerror=alert(1)>', '&lt;img src=x onerror=alert(1)&gt;'],
    ['"><svg onload=alert(1)>', '&quot;&gt;&lt;svg onload=alert(1)&gt;'],
    ['<script>alert(1)</script>', '&lt;script&gt;alert(1)&lt;/script&gt;'],
    ['Product & "Special" <Edition>', 'Product &amp; &quot;Special&quot; &lt;Edition&gt;'],
    ["Customer's order", 'Customer&#039;s order'],
    [null, ''],
    [undefined, ''],
    [42, '42']
];

fixtures.forEach(([input, expected]) => {
    assert.equal(utils.escapeHtml(input), expected);
});

const original = {
    label: '<b>Unsafe</b>',
    nested: {
        path: 'C:\\uploads\\<script>',
        enabled: true,
        count: 3
    },
    items: ['A&B', '<img>']
};
const escaped = utils.escapeObjectStrings(original);

assert.deepEqual(escaped, {
    label: '&lt;b&gt;Unsafe&lt;/b&gt;',
    nested: {
        path: 'C:\\uploads\\&lt;script&gt;',
        enabled: true,
        count: 3
    },
    items: ['A&amp;B', '&lt;img&gt;']
});
assert.notEqual(escaped, original);
assert.equal(original.label, '<b>Unsafe</b>');

console.log('admin-utils security tests passed');
