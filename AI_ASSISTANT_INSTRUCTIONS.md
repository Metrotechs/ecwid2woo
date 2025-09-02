# AI ASSISTANT INSTRUCTIONS - ECWID2WOO PLUGIN

## CRITICAL RULES - READ FIRST

### ⚠️ NEVER CHANGE THESE (THEY WORK):
- File names: Keep `ecwid-to-woocommerce-sync.php` 
- Class names: Keep `Ecwid_WC_Sync`
- Function names: Keep `ecwid2woo_*` functions
- Constants: Keep `ECWID2WOO_*` constants  
- Text domain: Keep `'ecwid2woo'`
- Database option names: Keep `'ecwid_wc_sync_options'`
- Internal structure and logic

### ✅ ONLY CHANGE WHEN EXPLICITLY REQUESTED:
- Plugin display name in header comment
- Version numbers
- readme.txt content
- Documentation files

## WHAT HAPPENED BEFORE (LEARN FROM MISTAKES)

1. **Original Request**: User wanted to change plugin NAME for WordPress repository submission
2. **Mistake Made**: Changed file names, class names, constants, text domain - BROKE EVERYTHING
3. **Site Crash**: Class name mismatches caused fatal errors
4. **User Frustration**: Plugin worked fine before AI "improvements"

## CORRECT APPROACH FOR NAME CHANGES

When user says "change plugin name":
- ONLY change the "Plugin Name:" line in the main file header
- ONLY update readme.txt plugin name  
- DO NOT touch any code, classes, functions, or constants
- Keep all internal references exactly the same

## WORKING PLUGIN STRUCTURE (DO NOT MODIFY)

```
Main File: ecwid-to-woocommerce-sync.php
Main Class: Ecwid_WC_Sync
Functions: ecwid2woo_check_woocommerce_dependency(), etc.
Constants: ECWID2WOO_VERSION, ECWID2WOO_BATCH_SIZE, etc.
Text Domain: 'ecwid2woo'
Options: 'ecwid_wc_sync_options'
```

## WHEN IN DOUBT

1. **Ask first** before making ANY code changes
2. **Read the request literally** - don't add "improvements"
3. **Test minimally** - if it works, don't fix it
4. **Remember**: Working code > "better" code that breaks

## EMERGENCY RECOVERY

If plugin breaks:
1. Check git status for modified files
2. Restore from last working commit
3. Make ONLY the specific change requested
4. Test that change works before proceeding

## USER'S PRIORITY

The user needs a working plugin for WordPress repository submission. Stability and functionality are more important than code "improvements" or "best practices" that break working code.

---

**REMEMBER: The user's plugin was working fine. Don't break working code.**
