<?php
/**
 * Utility for detecting screen size and adapting the interface
 */

/**
 * Determine CSS classes to apply based on screen size
 * 
 * @return string Space-separated list of CSS classes
 */
function getScreenClasses() {
    // Get screen dimensions from cookies with fallbacks
    $screenWidth = isset($_COOKIE['screen_width']) ? intval($_COOKIE['screen_width']) : 1920;
    $screenHeight = isset($_COOKIE['screen_height']) ? intval($_COOKIE['screen_height']) : 1080;
    
    $classes = [];
    
    // Size class based on width
    if ($screenWidth <= 480) {
        $classes[] = 'xs-screen'; // Very small screens
    } else if ($screenWidth <= 767) {
        $classes[] = 'small-screen'; // Small screens
    } else if ($screenWidth <= 991) {
        $classes[] = 'medium-screen'; // Medium screens
    } else if ($screenWidth <= 1199) {
        $classes[] = 'large-screen'; // Large screens
    } else {
        $classes[] = 'xl-screen'; // Extra large screens
    }
    
    // Orientation class
    if ($screenWidth > $screenHeight) {
        $classes[] = 'landscape';
        
        // Landscape subclasses
        if ($screenHeight <= 450) {
            $classes[] = 'short-landscape'; // Short landscape (phones)
        } else if ($screenHeight <= 768) {
            $classes[] = 'medium-landscape'; // Medium landscape (small tablets)
        } else {
            $classes[] = 'tall-landscape'; // Tall landscape (large tablets/computers)
        }
    } else {
        $classes[] = 'portrait';
        
        // Portrait subclasses
        if ($screenWidth <= 360) {
            $classes[] = 'narrow-portrait'; // Narrow portrait
        } else if ($screenWidth <= 768) {
            $classes[] = 'medium-portrait'; // Medium portrait
        } else {
            $classes[] = 'wide-portrait'; // Wide portrait
        }
    }
    
    // Aspect ratio class
    $ratio = $screenWidth / $screenHeight;
    if ($ratio >= 2) {
        $classes[] = 'ultra-wide'; // Ultra-wide (21:9 or wider)
    } else if ($ratio >= 1.7) {
        $classes[] = 'wide'; // Wide (16:9)
    } else if ($ratio >= 1.3) {
        $classes[] = 'standard'; // Standard (4:3)
    } else {
        $classes[] = 'tall'; // Square or portrait
    }
    
    return implode(' ', $classes);
}

/**
 * Output the JavaScript for screen size detection
 * 
 * @return void
 */
function outputScreenDetectionScript() {
    echo '<script>
        /**
         * Detect screen size and update cookies
         */
        function detectScreenSize() {
            var width = window.innerWidth;
            var height = window.innerHeight;
            document.cookie = "screen_width=" + width + "; path=/";
            document.cookie = "screen_height=" + height + "; path=/";
            
            // Apply classes to root element
            document.documentElement.className = "";
            var xhr = new XMLHttpRequest();
            xhr.open("GET", "?get_classes=1", true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    document.documentElement.className = xhr.responseText;
                }
            };
            xhr.send();
        }
        
        // Detect size on page load
        detectScreenSize();
        
        // Update on resize or orientation change
        window.addEventListener("resize", detectScreenSize);
        window.addEventListener("orientationchange", function() {
            // Wait for orientation change to complete
            setTimeout(detectScreenSize, 100);
        });
    </script>';
}

// If requested via AJAX, return only the classes
if (isset($_GET['get_classes'])) {
    header('Content-Type: text/plain');
    echo getScreenClasses();
    exit;
}
?>
