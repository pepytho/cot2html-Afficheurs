// Smooth scroll system for all pages
let scrollTimeout;
let isAutoScrolling = false;
let SCROLL_DELAY = 200000; // Default to AUTO_REFRESH_INTERVAL value

// Function to update SCROLL_DELAY from config - make it global
window.updateScrollDelay = function(newDelay) {
    SCROLL_DELAY = newDelay;
    console.log("Updated SCROLL_DELAY to:", SCROLL_DELAY);
}

let isScrollPaused = false;
let userScrolled = false;
let lastScrollTop = 0;
let userInteracting = false;
let userInteractionTimeout;
const scrollThreshold = 40; // pixels from bottom to trigger refresh
const BASE_SCROLL_INCREMENT = 1; // Base pixels to scroll per frame

// Function to get scroll speed from cookie or URL
function getScrollSpeed() {
    // Check URL first
    const urlParams = new URLSearchParams(window.location.search);
    const urlSpeed = urlParams.get('scrollSpeed');
    if (urlSpeed) {
        const speed = parseFloat(urlSpeed);
        if (!isNaN(speed) && speed > 0) {
            // Save to cookie
            setCookie('scrollSpeed', speed.toFixed(1), 30);
            return speed;
        }
    }
    
    // Check cookie
    const cookieSpeed = getCookie('scrollSpeed');
    if (cookieSpeed) {
        const speed = parseFloat(cookieSpeed);
        if (!isNaN(speed) && speed > 0) {
            return speed;
        }
    }
    
    // Default
    return 1.0;
}

// Function to update URL with scroll speed
function updateURLWithScrollSpeed(speed) {
    const url = new URL(window.location);
    url.searchParams.set('scrollSpeed', speed.toFixed(1));
    window.history.replaceState({}, '', url);
}

// Cookie helper functions
function setCookie(name, value, days) {
    const expires = new Date();
    expires.setTime(expires.getTime() + days * 24 * 60 * 60 * 1000);
    document.cookie = name + '=' + value + ';expires=' + expires.toUTCString() + ';path=/';
}

function getCookie(name) {
    const nameEQ = name + '=';
    const ca = document.cookie.split(';');
    for (let i = 0; i < ca.length; i++) {
        let c = ca[i];
        while (c.charAt(0) === ' ') c = c.substring(1, c.length);
        if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
    }
    return null;
}

window.initAutoScroll = function(containerId = 'scrollme') {
    console.log("initAutoScroll called with containerId:", containerId);
    const container = document.getElementById(containerId);
    if (!container) {
        console.error("Autoscroll container not found:", containerId);
        return;
    }

    // Apply smooth scrolling CSS
    container.style.scrollBehavior = 'smooth';
    
    const urlParams = new URLSearchParams(window.location.search);
    const scrollParam = urlParams.get('scroll');
    console.log("URL scroll parameter:", scrollParam);
    
    // Initialize scroll speed from URL or cookie
    const initialSpeed = getScrollSpeed();
    if (typeof window.currentScrollSpeedFactor === 'undefined') {
        window.currentScrollSpeedFactor = initialSpeed;
    }
    console.log("Initialized scroll speed:", window.currentScrollSpeedFactor);
    
    // Autoscroll if scroll=1 (index.php defaults scroll to 1 if not specified)
    if (scrollParam === '1') {
        console.log("Autoscroll enabled by URL parameter for:", containerId);
        window.startAutoScroll(container);
    } else {
        console.log("Autoscroll not starting, scroll parameter is not '1' or is absent. Current value:", scrollParam);
    }
}

// Function to detect user interaction and pause autoscroll
function detectUserInteraction() {
    userInteracting = true;
    clearTimeout(userInteractionTimeout);
    
    // Add visual indicator
    const container = document.getElementById('scrollme');
    if (container) {
        container.classList.add('user-interacting');
    }
    
    // Resume autoscroll after 3 seconds of no interaction
    userInteractionTimeout = setTimeout(() => {
        userInteracting = false;
        if (container) {
            container.classList.remove('user-interacting');
        }
        console.log("User interaction timeout - resuming autoscroll");
    }, 3000);
}

window.startAutoScroll = function(container) {
    console.log("startAutoScroll called with container:", container);
    if (!container) {
        console.error("startAutoScroll: container is null");
        return;
    }
    if (isAutoScrolling) {
        console.log("startAutoScroll: already auto-scrolling, skipping");
        return;
    }
    
    // Add user interaction listeners for the tab page
    container.addEventListener('wheel', detectUserInteraction, { passive: true });
    container.addEventListener('touchstart', detectUserInteraction, { passive: true });
    container.addEventListener('touchmove', detectUserInteraction, { passive: true });
    container.addEventListener('mousedown', detectUserInteraction, { passive: true });
    
    // Add click to pause/resume autoscroll
    container.addEventListener('click', function() {
        if (userInteracting) {
            userInteracting = false;
            clearTimeout(userInteractionTimeout);
            console.log("Click - resuming autoscroll");
        } else {
            detectUserInteraction();
            console.log("Click - pausing autoscroll for manual control");
        }
    });
    
    // Also listen for scroll events on the container itself
    container.addEventListener('scroll', function(e) {
        // If the scroll position was changed by user (not by our autoscroll)
        if (Math.abs(container.scrollTop - scrollPosition) > 5) {
            console.log("Manual scroll detected - pausing autoscroll");
            detectUserInteraction();
        }
    }, { passive: true });

    // Check if content is actually scrollable
    const isScrollable = container.scrollHeight > container.clientHeight;
    console.log("Container scrollable check - scrollHeight:", container.scrollHeight, "clientHeight:", container.clientHeight, "isScrollable:", isScrollable);
    
    if (!isScrollable) {
        console.log("startAutoScroll: Content not scrollable for container #" + (container.id || 'Unnamed') + 
                    ". ScrollHeight: " + container.scrollHeight + ", ClientHeight: " + container.clientHeight +
                    ". Centralized auto-refresh will handle page refresh.");
        return; 
    }
    
    isAutoScrolling = true;
    container.scrollTop = 0; 
    let scrollPosition = 0;
    
    console.log("Autoscroll initialized - container can be manually scrolled when user interacts"); 
    
    // Log the speed factor at the start of this autoscroll session
    let initialSpeedFactorCheck = typeof window.currentScrollSpeedFactor !== 'undefined' ? parseFloat(window.currentScrollSpeedFactor) : 1.0;
    if (isNaN(initialSpeedFactorCheck) || initialSpeedFactorCheck <= 0) {
        initialSpeedFactorCheck = 1.0;
    }
    console.log("startAutoScroll: Starting autoscroll for #" + (container.id || 'Unnamed') + 
                ". ScrollHeight: " + container.scrollHeight + ", ClientHeight: " + container.clientHeight +
                ". Effective speedFactor at start: " + initialSpeedFactorCheck);

    // Smooth scroll with easing
    let lastTimestamp = performance.now();
    const targetFPS = 60;
    const frameTime = 1000 / targetFPS;
    
    function scroll(timestamp) {
        if (!isAutoScrolling) {
            return;
        }
        
        // Pause autoscroll if user is interacting
        if (userInteracting) {
            requestAnimationFrame(scroll);
            return;
        }
        
        // Calculate delta time for smooth scrolling regardless of frame rate
        const deltaTime = timestamp - lastTimestamp;
        lastTimestamp = timestamp;
        
        // Normalize to 60 FPS
        const timeMultiplier = deltaTime / frameTime;
        
        let speedFactor = typeof window.currentScrollSpeedFactor !== 'undefined' ? parseFloat(window.currentScrollSpeedFactor) : 1.0;
        if (isNaN(speedFactor) || speedFactor <= 0) {
            speedFactor = 1.0;
        }
        
        // Smooth increment with time-based adjustment
        const increment = BASE_SCROLL_INCREMENT * speedFactor * timeMultiplier;
        scrollPosition += increment;
        
        // Use smooth scrolling
        container.scrollTop = Math.round(scrollPosition);
        
        // Check if bottom is reached (with a small buffer)
        if (container.scrollTop >= (container.scrollHeight - container.clientHeight - 5)) {
            console.log("Reached bottom, restarting from top");
            // Smooth transition to top
            container.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
            scrollPosition = 0;
            setTimeout(() => {
                requestAnimationFrame(scroll);
            }, 1000); // Wait for smooth scroll to complete
        } else {
            requestAnimationFrame(scroll);
        }
    }
    
    // Start smooth scrolling
    requestAnimationFrame(scroll);
}

function handleScroll(e) {
    if (isScrollPaused) return;
    
    const scrollPosition = window.scrollY;
    const windowHeight = window.innerHeight;
    const documentHeight = document.documentElement.scrollHeight;
    
    // Detect scroll direction
    const isScrollingDown = scrollPosition > lastScrollTop;
    lastScrollTop = scrollPosition;
    
    // Only proceed if scrolling down
    if (!isScrollingDown) return;
    
    // Check if we're near the bottom
    if (scrollPosition + windowHeight >= documentHeight - scrollThreshold) {
        // Special handling for tableau page
        if (document.querySelector('.tableau-container')) {
            handleTableauScroll();
        } else {
            refreshPage();
        }
    }
}

function handleTableauScroll() {
    // Only refresh if we're viewing a tableau
    if (document.querySelector('.myTableau')) {
        refreshPage();
    }
}

function refreshPage() {
    if (!userScrolled) {
        isScrollPaused = true;
        location.reload();
    }
}

function initScrollHandling() {
    // Reset flags on page load
    isScrollPaused = false;
    userScrolled = false;
    
    // Disable manual scroll refresh when autoscroll is enabled
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('scroll') === '1') {
        console.log("Autoscroll enabled, disabling manual scroll refresh");
        return; // Don't add scroll listeners when autoscroll is active
    }
    
    // Add scroll event listener only when autoscroll is disabled
    window.addEventListener('scroll', handleScroll, { passive: true });
    
    // Detect user interaction
    document.addEventListener('mousewheel', () => {
        userScrolled = true;
        setTimeout(() => { userScrolled = false; }, 1000);
    }, { passive: true });
    
    document.addEventListener('touchmove', () => {
        userScrolled = true;
        setTimeout(() => { userScrolled = false; }, 1000);
    }, { passive: true });
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    // initAutoScroll('scrollme'); // Removed: This will be called by startit() from index.php via functions.js
    initScrollHandling(); // This is for manual scroll detection and page refresh
    
    // Initialize currentScrollSpeedFactor if not already set by index.php's immediate script block
    if (typeof window.currentScrollSpeedFactor === 'undefined') {
        const speedCookie = document.cookie.split('; ').find(row => row.startsWith('scrollSpeed='));
        window.currentScrollSpeedFactor = speedCookie ? parseFloat(speedCookie.split('=')[1]) : 1.0;
    }

    if (isNaN(window.currentScrollSpeedFactor) || window.currentScrollSpeedFactor <= 0) {
        window.currentScrollSpeedFactor = 1.0; // Ensure it's a valid number
    }


    const urlParams = new URLSearchParams(window.location.search);
    const item = urlParams.get('item');

    // Special handling for tableau page ('tab')
    if (item === 'tab') {
        const tableauContainer = document.getElementById('scrollme');
        if (tableauContainer) {
            // Ensure fixed height for tableau container to make it scrollable
            // This height is crucial for scrollHeight and clientHeight comparison
            if (!tableauContainer.style.height) { // Only set if not already styled (inline)
                tableauContainer.style.height = '85vh'; 
                tableauContainer.style.overflowY = 'auto';
                console.log("JS: Applied fallback height 85vh and overflowY auto to #scrollme.");
            }
            
            // Force autoscroll initialization for tab page
            console.log("JS: Forcing autoscroll initialization for tab page");
            setTimeout(() => {
                window.initAutoScroll('scrollme');
            }, 500); // Small delay to ensure DOM is ready
        }
    }
    // The call to initAutoScroll is expected to be done by startit() in js/functions.js,
    // which is called from body.onload in index.php.
});

// Additional window load event to ensure autoscroll starts for tab pages
window.addEventListener('load', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const item = urlParams.get('item');
    
    if (item === 'tab') {
        console.log("Window load event - checking autoscroll for tab page");
        setTimeout(() => {
            if (typeof window.initAutoScroll === 'function') {
                console.log("Window load - initializing autoscroll");
                window.initAutoScroll('scrollme');
            } else {
                console.error("Window load - initAutoScroll function not found");
            }
        }, 1500); // Longer delay to ensure everything is loaded
    }
});
