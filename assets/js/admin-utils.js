(function(root, factory) {
    'use strict';

    var utils = factory();

    if (typeof module === 'object' && module.exports) {
        module.exports = utils;
    }

    if (root) {
        root.ecwid2wooAdminUtils = utils;
    }
})(typeof window !== 'undefined' ? window : (typeof global !== 'undefined' ? global : this), function() {
    'use strict';

    var htmlEntities = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };

    function escapeHtml(value) {
        if (value === null || typeof value === 'undefined') {
            return '';
        }

        return String(value).replace(/[&<>"']/g, function(character) {
            return htmlEntities[character];
        });
    }

    function escapeObjectStrings(value) {
        if (typeof value === 'string') {
            return escapeHtml(value);
        }

        if (Array.isArray(value)) {
            return value.map(escapeObjectStrings);
        }

        if (value && Object.prototype.toString.call(value) === '[object Object]') {
            return Object.keys(value).reduce(function(result, key) {
                result[key] = escapeObjectStrings(value[key]);
                return result;
            }, {});
        }

        return value;
    }

    return {
        escapeHtml: escapeHtml,
        escapeObjectStrings: escapeObjectStrings
    };
});
