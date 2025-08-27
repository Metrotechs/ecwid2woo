<?php
/**
 * Test script to validate WooCommerce attribute slug generation
 */

// Simulate WordPress sanitize_title function
function sanitize_title($title) {
    $title = strip_tags($title);
    // Preserve periods within words, but replace other special chars
    $title = preg_replace('/[^a-zA-Z0-9\._\-]/', '-', $title);
    $title = preg_replace('/[\-]+/', '-', $title);
    $title = trim($title, '-');
    return strtolower($title);
}

/**
 * Generate a WooCommerce-compatible attribute slug (max 28 characters)
 * 
 * @param string $attribute_name The attribute name to convert to slug
 * @return string The shortened slug
 */
function generate_wc_attribute_slug($attribute_name) {
    // Start with sanitized title
    $slug = sanitize_title($attribute_name);
    
    // If slug is within limit, return as-is
    if (strlen($slug) <= 28) {
        return $slug;
    }
    
    // If too long, use intelligent truncation
    $slug = substr($slug, 0, 28);
    
    // Remove any trailing hyphen that might be created by truncation
    $slug = rtrim($slug, '-');
    
    // Ensure we still have a meaningful slug
    if (strlen($slug) < 3) {
        // If too short after trimming, use first 28 chars with fallback
        $slug = substr(sanitize_title($attribute_name), 0, 28);
        $slug = rtrim($slug, '-');
    }
    
    return $slug;
}

// Test cases
$test_cases = [
    'Additional Lead Time (Nitride Finishes and Fluting)',
    'Color',
    'Size',
    'A very long attribute name that exceeds the limit',
    'Short',
    'Another-extremely-long-attribute-name-with-many-hyphens-and-words',
    'Special Characters & Symbols!@#$%^&*()',
];

echo "Testing WooCommerce Attribute Slug Generation\n";
echo "=============================================\n\n";

foreach ($test_cases as $test_case) {
    $original_slug = sanitize_title($test_case);
    $new_slug = generate_wc_attribute_slug($test_case);
    
    echo "Attribute Name: '{$test_case}'\n";
    echo "Original Slug:  '{$original_slug}' (length: " . strlen($original_slug) . ")\n";
    echo "New Slug:       '{$new_slug}' (length: " . strlen($new_slug) . ")\n";
    echo "Status:         " . (strlen($new_slug) <= 28 ? "✅ VALID" : "❌ TOO LONG") . "\n";
    echo str_repeat("-", 60) . "\n\n";
}
?>
