# Tournament Bracket Interconnecting Lines Implementation

## Overview
I've implemented interconnecting lines between fencers in different phases of the tournament bracket, inspired by the example in `exemples/tab-exemple.html`.

## Changes Made

### 1. CSS Styles (`css/style.css`)
Added new CSS classes for bracket interconnecting lines:
- `.tbb` - Top and bottom borders for fencer cells
- `.tbr` - Top, bottom, and right borders for connecting line cells
- `.tbbr` - Top, bottom, and right borders for fencer cells that connect to next round
- `.tscoref` - Special styling for score cells with enhanced visibility

### 2. PHP Logic (`my6.php`)
Added a new function `addBracketLineClasses()` that:
- Detects when a fencer cell should connect to the next round
- Adds appropriate CSS classes for interconnecting lines
- Applies to both top and bottom fencer positions
- Handles connecting line cells between rounds

### 3. JavaScript Enhancement (`js/bracket-lines.js`)
Created interactive features:
- Hover effects to highlight connected matches
- Visual feedback showing the path from one round to the next
- Smooth transitions and highlighting effects

### 4. Visual Enhancements
- White borders with glow effects for better visibility on dark background
- Arrow indicators (→) showing the flow direction
- Gradient backgrounds for connecting cells
- Enhanced spacing and visual hierarchy

## How It Works

1. **Detection**: The system detects fencer cells that advance to the next round
2. **Classification**: Adds appropriate CSS classes based on cell type and position
3. **Styling**: CSS creates visible connecting lines between phases
4. **Interaction**: JavaScript adds hover effects for better user experience

## CSS Classes Applied

- **Fencer cells advancing**: Get `tbbr` class (borders on top, bottom, right)
- **Connecting line cells**: Get `tbr` class (borders on top, bottom, right)
- **Score cells**: Get `tscoref` class (enhanced background and borders)

## Visual Result

The tournament bracket now shows clear visual connections between:
- Fencers in one round and their matches in the next round
- Connecting lines that flow from left to right across phases
- Enhanced readability with proper borders and spacing
- Interactive hover effects for better user experience

## Browser Compatibility

The implementation uses standard CSS and JavaScript features that work in all modern browsers.