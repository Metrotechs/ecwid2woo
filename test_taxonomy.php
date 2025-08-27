<?php
/**
 * Test script to validate attribute slug and taxonomy name generation
 */

// Simulate WordPress functions
function sanitize_title($title) {
    $title = strip_tags($title);
    $title = preg_replace('/[^a-zA-Z0-9\._\-]/', '-', $title);
    $title = preg_replace('/[\-]+/', '-', $title);
    $title = trim($title, '-');
    return strtolower($title);
}

function wc_attribute_taxonomy_name($slug) {
    return 'pa_' . $slug;
}

// Simulate the plugin methods
function generate_wc_attribute_slug($attribute_name) {
    $slug = sanitize_title($attribute_name);
    
    if (strlen($slug) <= 28) {
        return $slug;
    }
    
    $slug = substr($slug, 0, 28);
    $slug = rtrim($slug, '-');
    
    if (strlen($slug) < 3) {
        $slug = substr(sanitize_title($attribute_name), 0, 28);
        $slug = rtrim($slug, '-');
    }
    
    return $slug;
}

function get_wc_attribute_taxonomy_name($attribute_name) {
    $original_slug = sanitize_title($attribute_name);
    if (strlen($original_slug) <= 28) {
        return wc_attribute_taxonomy_name($original_slug);
    }
    
    $shortened_slug = generate_wc_attribute_slug($attribute_name);
    return wc_attribute_taxonomy_name($shortened_slug);
}

// Test cases - these are the problematic attribute names
$test_cases = [
    'Acknowledgement of Lead Times',
    'Additional Lead Time (Nitride Finishes and Fluting)',
    'Color',
    'Size',
    'Another very long attribute name that definitely exceeds limits',
];

echo "Testing WooCommerce Attribute and Taxonomy Name Generation\n";
echo "========================================================\n\n";

foreach ($test_cases as $test_case) {
    $original_slug = sanitize_title($test_case);
    $generated_slug = generate_wc_attribute_slug($test_case);
    $taxonomy_name = get_wc_attribute_taxonomy_name($test_case);
    
    echo "Attribute Name: '{$test_case}'\n";
    echo "Original Slug:  '{$original_slug}' (length: " . strlen($original_slug) . ")\n";
    echo "Generated Slug: '{$generated_slug}' (length: " . strlen($generated_slug) . ")\n";
    echo "Taxonomy Name:  '{$taxonomy_name}' (length: " . strlen($taxonomy_name) . ")\n";
    echo "Slug Valid:     " . (strlen($generated_slug) <= 28 ? "✅ YES" : "❌ NO") . "\n";
    echo "Taxonomy Valid: " . (strlen($taxonomy_name) <= 32 ? "✅ YES" : "❌ NO") . " (pa_ + 28 chars max)\n";
    echo str_repeat("-", 70) . "\n\n";
}
?>
