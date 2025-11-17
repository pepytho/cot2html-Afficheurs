# ROOT CAUSE FOUND: Meta Refresh Tag

## The Real Problem

The 59-second refresh was NOT caused by JavaScript timers, but by a **hardcoded HTML meta refresh tag**:

```html
<meta http-equiv="refresh" content="60">
```

This tag was in `index.php` line 369 and was **overriding all JavaScript-based refresh mechanisms**.

## Why This Was Hard to Find

1. **Meta refresh runs at browser level** - completely independent of JavaScript
2. **60 seconds vs 59 seconds** - slight timing difference made it seem like a different mechanism
3. **Multiple JavaScript timers** - we were focused on fixing JavaScript when the issue was HTML
4. **Browser caching** - meta refresh works even when JavaScript is disabled

## The Fix

**Removed the meta refresh tag completely:**

```html
<!-- Before -->
<meta http-equiv="refresh" content="60">

<!-- After -->
<!-- Meta refresh removed - using JavaScript setInterval with configurable AUTO_REFRESH_INTERVAL instead -->
```

## Why JavaScript Timer is Better

1. **Configurable** - Uses `AUTO_REFRESH_INTERVAL` from config.php
2. **Conditional** - Can be disabled or modified based on page state
3. **Debuggable** - Console logs show when refresh triggers
4. **Flexible** - Can be paused, restarted, or modified dynamically

## Current System

**Single refresh mechanism:**
```javascript
setInterval(function() {
    console.log("Centralized AUTO_REFRESH_INTERVAL triggered - refreshing page after", window.auto_refresh_interval/1000, "seconds");
    window.location.reload();
}, window.auto_refresh_interval);
```

**Configuration:**
```php
$AUTO_REFRESH_INTERVAL = 200000; // 200 seconds = 3.33 minutes
```

## Expected Behavior Now

✅ **Pages refresh every 200 seconds** (3.33 minutes)  
✅ **No more 59/60 second refreshes**  
✅ **Console logs confirm timing**  
✅ **Configurable via config.php**  
✅ **Single refresh mechanism**  

## Debug Information

**Console will show:**
```
Config loaded - SCROLL_DELAY: 200000 ms, AUTO_REFRESH_INTERVAL: 200000 ms
Expected refresh every 200 seconds
Setting up centralized auto-refresh with interval: 200000 ms (200 seconds)
Meta refresh tag removed - using JavaScript timer only
Centralized AUTO_REFRESH_INTERVAL triggered - refreshing page after 200 seconds
```

## Lesson Learned

Always check for **HTML-level refresh mechanisms** (meta tags) when debugging JavaScript timer issues. Meta refresh tags operate at the browser level and will override any JavaScript-based refresh logic.

## Testing

1. Open browser console
2. Look for "Expected refresh every 200 seconds" message
3. Wait 200 seconds (3.33 minutes)
4. Confirm refresh happens with console message
5. Verify no refreshes occur at 59-60 second intervals