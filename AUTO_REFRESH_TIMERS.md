# Auto-Refresh Timer Configuration Guide

This document explains where to find and modify all auto-refresh timer values in the BellePoule dynamic display system.

## Configuration Location

All timer values are now centralized in **`config.php`**:

```php
/**
 * Auto-refresh timer configurations (in milliseconds)
 */
$SCROLL_DELAY = 590000; // Time before page refresh when scrolling reaches bottom (590 seconds = ~9.8 minutes)
$AUTO_REFRESH_INTERVAL = 120000; // General auto-refresh interval (120 seconds = 2 minutes)
$BURST_TIMER = 18; // Timer for scroll burst animation (milliseconds)
$BURST_SPEED = 18; // Speed of scroll animation
$BURST_LENGTH = 6; // Length of scroll burst
$BURST_EXTRA_DELAY = 100; // Extra delay between bursts
$BURST_END_DELAY = 300; // Delay at end of burst
$INTRA_BURST_DELAY = 1; // Delay within burst
```

## Timer Descriptions

### 1. **SCROLL_DELAY** (590,000ms = ~9.8 minutes)
- **Purpose**: Time to wait before refreshing the page when scrolling reaches the bottom
- **Used in**: `js/scroll-refresh.js`
- **When it triggers**: After autoscroll reaches the bottom of content

### 2. **AUTO_REFRESH_INTERVAL** (120,000ms = 2 minutes)
- **Purpose**: General auto-refresh interval for the entire page
- **Used in**: `js/functions.js` in the `setupAutoRefresh()` function
- **When it triggers**: Continuously every 2 minutes (when page is visible)

### 3. **BURST_TIMER** (18ms)
- **Purpose**: Controls the speed of the scroll animation frames
- **Used in**: `js/functions.js` in `setInterval(scrollwindow, burst_timer)`
- **Effect**: Lower values = faster scrolling, higher values = slower scrolling

### 4. **BURST_SPEED** (18 pixels)
- **Purpose**: Number of pixels to scroll per animation frame
- **Used in**: `js/functions.js` in the `scrollwindow()` function
- **Effect**: Higher values = faster scrolling distance

### 5. **BURST_LENGTH** (6)
- **Purpose**: Number of scroll steps in each burst
- **Used in**: `js/functions.js` as `glob_burst_length`

### 6. **BURST_EXTRA_DELAY** (100ms)
- **Purpose**: Extra delay between scroll bursts
- **Used in**: `js/functions.js` as `extra_burst_delay`

### 7. **BURST_END_DELAY** (300ms)
- **Purpose**: Delay at the end of each burst sequence
- **Used in**: `js/functions.js` as `end_delay`

### 8. **INTRA_BURST_DELAY** (1ms)
- **Purpose**: Delay between individual scroll steps within a burst
- **Used in**: `js/functions.js` as `intra_burst_delay`

## How to Modify Timer Values

1. **Edit `config.php`** - Change the values in the configuration section
2. **Restart/Refresh** - The changes will take effect on the next page load

## Common Adjustments

### To make scrolling faster:
- Decrease `BURST_TIMER` (e.g., from 18 to 10)
- Increase `BURST_SPEED` (e.g., from 18 to 25)

### To make scrolling slower:
- Increase `BURST_TIMER` (e.g., from 18 to 30)
- Decrease `BURST_SPEED` (e.g., from 18 to 10)

### To change refresh frequency:
- Modify `AUTO_REFRESH_INTERVAL` (e.g., 60000 for 1 minute, 300000 for 5 minutes)
- Modify `SCROLL_DELAY` for bottom-of-page refresh timing

### To pause longer at top/bottom:
- The pause time is calculated as `8000 / burst_timer` in the code
- With `BURST_TIMER = 18`, pause = ~444 ticks = ~8 seconds

## Files That Use These Timers

1. **`config.php`** - Configuration definitions
2. **`index.php`** - Passes values to JavaScript
3. **`js/scroll-refresh.js`** - Uses SCROLL_DELAY
4. **`js/functions.js`** - Uses AUTO_REFRESH_INTERVAL and all burst parameters

## Time Conversion Reference

- 1000ms = 1 second
- 60000ms = 1 minute  
- 120000ms = 2 minutes
- 300000ms = 5 minutes
- 600000ms = 10 minutes