# Ecwid2Woo Sync - 500 Error Fixes

This document outlines the comprehensive fixes implemented to resolve the 500 Internal Server Errors occurring during full import operations.

## Root Causes Identified

1. **Memory Exhaustion**: Processing large batches of products/variations exceeded PHP memory limits
2. **Database Connection Timeouts**: Long-running operations exceeded database connection limits  
3. **Missing Error Handling**: Uncaught exceptions during product processing caused fatal errors
4. **API Rate Limiting**: Ecwid API was rejecting rapid requests with 429/503 errors
5. **Term Creation Conflicts**: Creating taxonomy terms concurrently caused database conflicts
6. **Resource Management**: Inadequate time limits and resource allocation

## Implemented Solutions

### 1. Enhanced Resource Management

**PHP Changes:**
- Increased memory limit to 512M in AJAX handlers
- Added proper time limit management (300s for batch, unlimited for variations)
- Enhanced resource allocation for memory-intensive operations

**Constants Updated:**
- Reduced `ECWID2WOO_VARIATION_BATCH_SIZE` from 50 to 25
- Added `ECWID2WOO_CATEGORY_BATCH_SIZE` set to 15 (categories are lighter)
- Added `ECWID2WOO_PRODUCT_BATCH_SIZE` set to 3 (products are heavier)
- Dynamic batch sizing based on content type for optimal performance

### 2. Comprehensive Error Handling

**PHP Improvements:**
- Added try-catch blocks around entire AJAX handler functions
- Separate handling for Fatal Errors (PHP 7+) and regular Exceptions
- Enhanced error reporting with user-friendly messages
- Debug-aware error logging for development vs production

**Error Types Handled:**
- Fatal errors (memory exhaustion, parser errors)
- Regular exceptions (database errors, API failures)
- Network timeouts and connection issues
- API rate limiting responses

### 3. API Request Retry Logic

**New Function: `make_api_request_with_retry()`**
- Exponential backoff for failed requests
- Special handling for rate limiting (429, 503 errors)
- Automatic retry for server errors (5xx codes)
- Maximum retry attempts with progressive delays
- Network error recovery

**Retry Strategy:**
- Rate limiting: Wait and retry with exponential backoff
- Server errors: Retry up to 3 times with increasing delays
- Client errors: No retry (fix required)
- Network errors: Retry with backoff

### 4. Improved Term Creation

**Enhanced Database Operations:**
- Check for existing terms by both name and slug
- Handle concurrent term creation conflicts
- Graceful handling of "term_exists" errors
- Fallback mechanisms for term retrieval

**Conflict Resolution:**
- Detect when terms are created by parallel processes
- Retry term lookup after creation failures
- Log detailed information for debugging

### 5. JavaScript Error Handling

**Client-Side Improvements:**
- Enhanced AJAX error handling with HTTP status code detection
- Automatic retry logic for retryable errors (500, 429, 503)
- Progressive retry attempts with user feedback
- Graceful degradation for non-retryable errors

**Error Classification:**
- Server errors (500-599): Automatic retry with backoff
- Rate limiting (429): Automatic retry with longer delays
- Network errors (0): Connection issue warnings
- Client errors (400-499): No retry, show detailed message

### 6. Batch Processing Optimizations

**Reduced Batch Sizes:**
- Category sync: 15 items per request (balanced for speed and stability)
- Product sync: 3 items per request (conservative due to variations)
- Variation processing: 25 variations per batch (down from 50)
- Dynamic sizing based on content type reduces memory usage appropriately

**Progress Tracking:**
- More frequent progress updates due to smaller batches
- Better user feedback during long operations
- Cancellation support for long-running syncs

## Configuration Constants

```php
// Optimized batch sizes for different content types
define('ECWID2WOO_VARIATION_BATCH_SIZE', 25);  // Was 50
define('ECWID2WOO_CATEGORY_BATCH_SIZE', 15);   // New: Categories are lighter
define('ECWID2WOO_PRODUCT_BATCH_SIZE', 3);     // New: Products are heavier
```

## Error Response Format

The improved error handling provides structured error responses:

```javascript
{
    "message": "User-friendly error message",
    "error_type": "fatal_error|exception|rate_limit|network_error",
    "error_details": "Technical details (if WP_DEBUG enabled)",
    "retry_recommended": true|false
}
```

## Server Requirements

For optimal performance, ensure your server meets these requirements:

- **PHP Memory Limit**: 512M or higher
- **PHP Max Execution Time**: 300 seconds or higher
- **MySQL Max Connections**: Adequate for concurrent operations
- **WordPress Memory Limit**: 256M or higher

## Monitoring and Debugging

**Error Logging:**
- All errors are logged when `WP_DEBUG` is enabled
- Detailed stack traces for exceptions
- API response logging for troubleshooting

**User Feedback:**
- Real-time error reporting in sync interface
- Retry attempt notifications
- Clear indication of permanent vs temporary failures

## Testing Recommendations

1. **Small Batch Testing**: Start with smaller stores to verify fixes
2. **Memory Monitoring**: Watch server memory usage during sync
3. **Error Log Review**: Check WordPress error logs for any remaining issues
4. **Network Stability**: Ensure stable internet connection during large syncs
5. **Server Resources**: Monitor CPU and database performance

## Future Improvements

Potential additional enhancements:
- Background processing for very large imports
- Database connection pooling
- Improved caching mechanisms
- Progressive sync resumption after failures
- Performance metrics collection

These fixes significantly improve the reliability and robustness of the Ecwid2Woo sync process, especially for large stores with many products and variations.
