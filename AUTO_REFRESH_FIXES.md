# Auto-Refresh Issues Fixed

## Problems Identified:

1. **AUTO_REFRESH_INTERVAL not working correctly** - Set to 200000ms but refreshing every ~59 seconds
2. **Scroll speed not persisting** - NoUiSlider values not saved to cookies properly

## Root Causes Found:

### 1. Multiple Refresh Mechanisms
There were **3 different refresh mechanisms** running simultaneously:

- **AUTO_REFRESH_INTERVAL** (200 seconds) - The intended general refresh timer
- **SCROLL_DELAY** (10 seconds in config) - When autoscroll reaches bottom  
- **Manual scroll refresh** - When user scrolls near bottom (40px threshold)

### 2. Config Values Not Loading Properly
- `scroll-refresh.js` was loaded before config values were set
- Falling back to hardcoded 59000ms instead of config value

### 3. Conflicting Refresh Triggers
- Manual scroll detection was interfering with autoscroll
- Multiple timers competing and causing unexpected refresh timing

## Fixes Applied:

### 1. Fixed Config Value Loading
```javascript
// Before: Hardcoded fallback
const SCROLL_DELAY = window.scroll_delay || 59000;

// After: Dynamic loading with proper fallback
let SCROLL_DELAY = 59000; // Default fallback
function updateScrollDelay(newDelay) {
    SCROLL_DELAY = newDelay;
}
```

### 2. Disabled Conflicting Refresh Mechanisms
```javascript
function initScrollHandling() {
    // Disable manual scroll refresh when autoscroll is enabled
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('scroll') === '1') {
        console.log("Autoscroll enabled, disabling manual scroll refresh");
        return; // Don't add scroll listeners when autoscroll is active
    }
    // ... rest of manual scroll handling
}
```

### 3. Enhanced Cookie Handling for Scroll Speed
```javascript
// Added proper cookie functions
function getCookie(name) { /* ... */ }
function setCookie(name, value, days) { /* ... */ }

// Improved scroll speed persistence
scrollSpeedSelector.addEventListener('change', function() {
    var newSpeed = parseFloat(this.value);
    window.currentScrollSpeedFactor = newSpeed;
    setCookie('scrollSpeed', newSpeed.toString(), 30); // 30 days
    // ... restart autoscroll with new speed
});
```

### 4. Added Debug Logging
```javascript
// Added logging to track which refresh mechanism triggers
console.log("AUTO_REFRESH_INTERVAL timeout triggered - refreshing page. Interval was:", window.auto_refresh_interval || 120000, "ms");
console.log("SCROLL_DELAY timeout triggered - refreshing page. Delay was:", window.scroll_delay || SCROLL_DELAY, "ms");
```

### 5. Proper Config Integration
```javascript
// In index.php - ensure config values are available globally
window.scroll_delay = <?php echo $SCROLL_DELAY; ?>;
window.auto_refresh_interval = <?php echo $AUTO_REFRESH_INTERVAL; ?>;

// Update scroll-refresh.js with config values
if (typeof updateScrollDelay === 'function') {
    updateScrollDelay(window.scroll_delay);
}
```

## Current Timer Configuration:

From `config.php`:
- `$AUTO_REFRESH_INTERVAL = 200000` (200 seconds = 3.33 minutes)
- `$SCROLL_DELAY = 10000` (10 seconds)

## Expected Behavior Now:

1. **General auto-refresh**: Every 200 seconds (3.33 minutes)
2. **Bottom-reached refresh**: 10 seconds after autoscroll reaches bottom
3. **No manual scroll interference** when autoscroll is enabled
4. **Scroll speed persists** across page refreshes via cookies
5. **Debug logging** shows which mechanism triggered each refresh

## Testing:

1. Check browser console for refresh trigger logs
2. Verify scroll speed selector value persists after refresh
3. Confirm refresh timing matches config values
4. Ensure only one refresh mechanism is active at a time

## Configuration:

To adjust timing, edit `config.php`:
```php
$AUTO_REFRESH_INTERVAL = 300000; // 5 minutes
$SCROLL_DELAY = 15000; // 15 seconds after reaching bottom
```