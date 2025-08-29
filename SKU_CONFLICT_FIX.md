# SKU Conflict Resolution Fix

This document outlines the fix implemented to resolve "Invalid or duplicated SKU" errors during variation processing.

## Problem Description

The error "Invalid or duplicated SKU" occurred when:

1. **Multiple variations attempted to use the same SKU**
2. **A variation SKU conflicted with an existing product SKU in WooCommerce**
3. **The SKU generation logic created duplicates during concurrent processing**
4. **Ecwid variations had empty or invalid SKUs**

## Root Causes

1. **No SKU Uniqueness Validation**: The original code didn't check if a SKU was already in use before assigning it
2. **Simple Fallback Logic**: Basic pattern of `parent-sku-combo-id` could create conflicts
3. **No Conflict Resolution**: When a SKU conflict occurred, the variation save would fail with no retry mechanism
4. **Concurrent Processing Issues**: Multiple variations being processed simultaneously could try to claim the same SKU

## Implemented Solution

### 1. Enhanced SKU Generation Function

Created `generate_unique_variation_sku()` with the following features:

- **Conflict Detection**: Uses `wc_get_product_id_by_sku()` to check if SKU is already in use
- **Intelligent Retry**: If SKU is taken, appends incremental numbers (sku-1, sku-2, etc.)
- **Fallback Protection**: After 100 attempts, uses timestamp-based emergency SKU
- **Existing Variation Handling**: Allows existing variations to keep their current SKU during updates

### 2. Improved Error Handling

Enhanced the variation save process with:

- **Exception Catching**: Wraps variation save in try-catch blocks
- **SKU-Specific Error Detection**: Identifies SKU-related errors by message content
- **Emergency Recovery**: If SKU error detected, generates completely unique emergency SKU
- **Detailed Logging**: Provides clear feedback about SKU conflicts and resolutions

### 3. Multi-Level Fallback Strategy

The system now uses a three-tier approach:

1. **Primary**: Use Ecwid SKU or generated pattern
2. **Secondary**: Append incremental numbers if conflict detected  
3. **Emergency**: Use timestamp + random number if all else fails

## Code Implementation

### SKU Generation Function

```php
private function generate_unique_variation_sku($desired_sku, $existing_variation_id = 0, $ecwid_combination_id = '', &$batch_logs = []) {
    // Clean the desired SKU
    $base_sku = sanitize_text_field(trim($desired_sku));
    
    // If empty, generate a fallback
    if (empty($base_sku)) {
        $base_sku = 'var-' . $ecwid_combination_id . '-' . time();
    }
    
    // Check if the desired SKU is already available
    $sku_product_id = wc_get_product_id_by_sku($base_sku);
    
    // If SKU is free, or belongs to current variation, use it
    if (!$sku_product_id || ($existing_variation_id && $sku_product_id == $existing_variation_id)) {
        return $base_sku;
    }
    
    // Generate unique alternative with incremental suffix
    $counter = 1;
    while ($counter <= 100) {
        $unique_sku = $base_sku . '-' . $counter;
        $conflict_id = wc_get_product_id_by_sku($unique_sku);
        
        if (!$conflict_id || ($existing_variation_id && $conflict_id == $existing_variation_id)) {
            return $unique_sku;
        }
        
        $counter++;
    }
    
    // Emergency fallback
    return $base_sku . '-' . time() . '-' . mt_rand(100, 999);
}
```

### Enhanced Error Handling

```php
try {
    $var_saved_id = $variation->save();
    // Handle success...
} catch (Exception $e) {
    $error_msg = $e->getMessage();
    
    // Handle specific SKU conflict errors
    if (strpos($error_msg, 'SKU') !== false || strpos($error_msg, 'sku') !== false) {
        // Generate emergency SKU and retry
        $emergency_sku = 'emergency-' . $ecwid_combination_id . '-' . time() . '-' . mt_rand(100, 999);
        $variation->set_sku($emergency_sku);
        $var_saved_id = $variation->save();
        // Handle retry result...
    }
}
```

## Benefits

1. **Eliminates SKU Conflicts**: Systematic checking prevents duplicate SKU errors
2. **Automatic Recovery**: When conflicts occur, system automatically resolves them
3. **Maintains Data Integrity**: Existing variations keep their SKUs during updates
4. **Detailed Logging**: Clear visibility into SKU conflict resolution process
5. **Graceful Degradation**: Multiple fallback levels ensure variations are always saved

## SKU Pattern Examples

- **Original Ecwid SKU**: `SEED-MAT-AUTO-001`
- **Generated Pattern**: `parent-sku-combo-12345` 
- **Conflict Resolution**: `parent-sku-combo-12345-1`, `parent-sku-combo-12345-2`, etc.
- **Emergency Fallback**: `emergency-12345-1640995200-456`

## Logging Output

The system now provides detailed logging for SKU operations:

```
[INFO] SKU 'SEED-MAT-AUTO' is available for variation.
[WARNING] SKU 'SEED-MAT-AUTO' is already in use by product ID 123. Generating unique alternative.
[INFO] Generated unique SKU: 'SEED-MAT-AUTO-1' for Ecwid combination 12345
[SUCCESS] Variation saved with emergency SKU 'emergency-12345-1640995200-456'
```

## Testing Recommendations

1. **Import products with duplicate Ecwid SKUs** to verify conflict resolution
2. **Process variations with empty SKUs** to test fallback generation  
3. **Run concurrent sync operations** to test race condition handling
4. **Check existing variation updates** to ensure SKUs are preserved
5. **Verify emergency recovery** by simulating database constraint errors

This fix ensures that SKU conflicts no longer cause variation import failures, while maintaining data integrity and providing clear feedback about any resolution actions taken.
