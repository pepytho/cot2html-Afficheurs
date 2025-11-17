# Content Height Fix - Preventing Footer Overlap

## Problem Identified
The content was being cut off at the bottom because the scroll container height calculation only accounted for the footer but not the header space, causing the last rows of the tournament bracket to be hidden behind the footer.

## Root Cause
The height calculation was:
```css
.fhtab {
    height: calc(100vh - var(--footer-height, 40px)); /* Only footer accounted for */
}
```

This didn't account for the header area which includes:
- Main banner header (~4vh)
- Suite selector dropdown (~3vh) 
- Padding and margins (~3vh)
- **Total header space: ~10vh**

## Solution Applied

### 1. Added Header Height Variable
```css
:root {
    --header-height: 10vh; /* Height of header area (banner + controls) */
}
```

### 2. Updated Height Calculations
```css
/* Base scroll containers */
.fh {
    height: calc(100vh - var(--footer-height, 40px) - var(--header-height, 10vh));
}

.fhtab {
    height: calc(100vh - var(--footer-height, 40px) - var(--header-height, 10vh));
}

/* With footer class overrides */
body.with-footer .fh,
body.with-footer .fhtab,
body.with-footer .fhpou {
    height: calc(100vh - var(--footer-height, 40px) - var(--header-height, 10vh));
}
```

### 3. Made Header Height Configurable
```php
// In config.php
$HEADER_HEIGHT = "10vh"; // Height of header area (banner + controls)
```

```html
<!-- In index.php body style -->
<body style="--footer-height: 30px; --header-height: 10vh;">
```

### 4. Added Content Protection
```css
/* Ensure content doesn't get cut off at bottom */
.myTableau {
    margin-bottom: 20px; /* Add some bottom margin to ensure last content is visible */
}

/* Make sure the scroll container has proper spacing */
#scrollme {
    padding-bottom: 10px; /* Add padding at bottom of scroll area */
}
```

## Current Layout Calculation

```
┌─────────────────────────────────────┐
│ Header Area                  10vh   │ ← Banner, controls, padding
├─────────────────────────────────────┤
│                                     │
│ Scrollable Content Area             │ ← calc(100vh - 40px - 10vh)
│ height: calc(100vh - 40px - 10vh)   │   = ~85vh available space
│                                     │
│                                     │
├─────────────────────────────────────┤
│ Footer                       40px   │ ← Fixed footer
└─────────────────────────────────────┘
```

## Expected Results

✅ **No content cut-off** - All tournament bracket rows visible  
✅ **Proper scrolling** - Content scrolls within available space  
✅ **No footer overlap** - Last content visible above footer  
✅ **Configurable heights** - Both header and footer heights adjustable  
✅ **Responsive design** - Works on different screen sizes  

## Fine-Tuning

If content is still cut off or there's too much space, adjust the header height:

```php
// In config.php - adjust as needed
$HEADER_HEIGHT = "8vh";  // Less header space
$HEADER_HEIGHT = "12vh"; // More header space
```

## Debug Helper

To visualize the scroll container boundaries, uncomment this line in CSS:
```css
.fhtab {
    border: 2px solid red; /* Shows scroll container boundaries */
}
```

## Testing Checklist

1. ✅ Navigate to tab page with tournament bracket
2. ✅ Scroll to bottom of content
3. ✅ Verify last rows are visible above footer
4. ✅ Check that content doesn't overlap footer
5. ✅ Test on different screen sizes
6. ✅ Verify autoscroll works properly in new height