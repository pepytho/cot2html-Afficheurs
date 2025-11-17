# Autoscroll Fix for Tab Page

## Problem
After fixing the refresh timing issue, the autoscroll for the "tab" page stopped working.

## Root Causes Found

### 1. Function Accessibility Issues
When we made functions global with `window.functionName`, the calls weren't updated:

```javascript
// Before: Local function calls
if (typeof initAutoScroll === 'function') {
    initAutoScroll('scrollme');
}

// After: Global function calls  
if (typeof window.initAutoScroll === 'function') {
    window.initAutoScroll('scrollme');
}
```

### 2. Conflicting Scroll Systems
Both old (`scrollwindow`) and new (`scroll-refresh.js`) systems were running simultaneously:

- **Old system**: `scrollwindow()` in `functions.js` 
- **New system**: `initAutoScroll()` in `scroll-refresh.js`

## Fixes Applied

### 1. Updated All Function Calls
**Fixed in `js/functions.js`:**
```javascript
// Before
if (item === 'tab' && typeof initAutoScroll === 'function') {
    initAutoScroll('scrollme');
}

// After  
if (item === 'tab' && typeof window.initAutoScroll === 'function') {
    window.initAutoScroll('scrollme');
}
```

**Fixed in `index.php`:**
```javascript
// Before
if (typeof startAutoScroll === 'function') {
    startAutoScroll(container);
}

// After
if (typeof window.startAutoScroll === 'function') {
    window.startAutoScroll(container);
}
```

**Fixed in `js/scroll-refresh.js`:**
```javascript
// Before
setTimeout(() => {
    initAutoScroll('scrollme');
}, 500);

// After
setTimeout(() => {
    window.initAutoScroll('scrollme');
}, 500);
```

### 2. Separated Scroll Systems
**Excluded 'tab' from old system:**
```javascript
// Before: Both systems active for 'tab'
if (!['lst', 'pou', 'poudet', 'clapou', 'clatab', 'finalcla', 'tab'].includes(getCurrentArray())) {
    return;
}

// After: Only new system for 'tab'
if (!['lst', 'pou', 'poudet', 'clapou', 'clatab', 'finalcla'].includes(getCurrentArray())) {
    return;
}
```

### 3. Enhanced Debugging
**Added comprehensive logging:**
```javascript
console.log("initAutoScroll called with containerId:", containerId);
console.log("URL scroll parameter:", scrollParam);
console.log("Container scrollable check - scrollHeight:", container.scrollHeight, "clientHeight:", container.clientHeight);
```

### 4. Multiple Initialization Points
**Added redundant initialization to ensure autoscroll starts:**

1. **DOMContentLoaded** in `scroll-refresh.js`
2. **startit()** function in `functions.js`  
3. **Fallback timeout** in `index.php`
4. **Window load event** in `scroll-refresh.js`

## Current Autoscroll Flow

### For Tab Pages (item=tab):
1. **Page loads** with `scroll=1` parameter
2. **DOMContentLoaded** → `scroll-refresh.js` initializes container
3. **startit()** → `functions.js` detects tab page, calls new system
4. **initAutoScroll()** → checks scroll parameter, calls `startAutoScroll()`
5. **startAutoScroll()** → begins continuous scrolling animation
6. **Window load** → backup initialization if others failed

### For Other Pages:
1. **startit()** → `functions.js` starts old `scrollwindow` system
2. **setInterval(scrollwindow)** → old burst-based scrolling

## Debug Information

**Console logs to look for:**
```
Config loaded - SCROLL_DELAY: 200000 ms, AUTO_REFRESH_INTERVAL: 200000 ms
Tab page detected - initializing new scroll system
initAutoScroll called with containerId: scrollme
URL scroll parameter: 1
Container scrollable check - scrollHeight: [height] clientHeight: [height] isScrollable: true
startAutoScroll called with container: [HTMLDivElement]
Starting autoscroll for #scrollme
```

## Expected Behavior

✅ **Tab page autoscrolls continuously**  
✅ **Smooth scrolling animation**  
✅ **Restarts from top when reaching bottom**  
✅ **No refresh interruptions during scroll**  
✅ **Other pages use old scroll system**  
✅ **Console shows initialization steps**  

## Testing Checklist

1. ✅ Navigate to tab page (`?item=tab&scroll=1`)
2. ✅ Check console for initialization messages  
3. ✅ Verify content starts scrolling automatically
4. ✅ Confirm scroll restarts from top when reaching bottom
5. ✅ Test scroll speed selector changes
6. ✅ Verify page refreshes every 200 seconds (not during scroll)