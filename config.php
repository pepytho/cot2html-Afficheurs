<?php
/**
 * Configuration file for BellePoule Affichage Dynamique
 * 
 * This file contains configuration settings for the BellePoule dynamic display system.
 */

// Include functions.php to use getCotcotDirectory()
require_once 'functions.php';

/**
 * Path to the cotcot files directory
 * 
 * If this is empty, the system will use the default paths.
 * To specify a custom path, set the full absolute path to the directory.
 * 
 * Examples:
 *   Windows: 'C:\\Path\\To\\Cotcot\\Files'
 *   Linux: '/var/www/html/cotcot_files'
 *   Relative: '../cotcot'  (Go one level up and then to the 'cotcot' directory)
 *   Relative to web root: '~/cotcot' (Go to the web server root and then to the 'cotcot' directory)
 */
$COTCOT_DIRECTORY = '../cotcot';

/**
 * Flag to enable debug messages
 */
$DEBUG_MODE = false;

/**
 * Path resolution rules
 * 
 * 0 = Only use exact paths as specified
 * 1 = Check for directory one level up if direct path fails
 * 2 = Check for directory relative to web root if previous checks fail
 * 3 = Try all possible path resolution methods (default)
 */
$PATH_RESOLUTION = 3;

/**
 * Footer configuration
 */
$FOOTER_TEXT = "Codé avec passion par Marc (CESTA - Angoulême) | Propulsé par BellePoule - la parade parfaite aux logiciels propriétaires ⚔️ | Licence GPL : code source ouvert, résultats ouverts, esprit ouvert";
$FOOTER_HEIGHT = "3vh"; // Height of the footer (can be px, vh, em, etc.)
$FOOTER_ENABLED = true; // Set to false to disable footer
$HEADER_HEIGHT = "15vh"; // Height of header area (banner + controls)

/**
 * Auto-refresh timer configurations (in milliseconds)
 */
$AUTO_REFRESH_INTERVAL = 200000; // Main auto-refresh interval for ALL pages (200 seconds = 3.33 minutes)

$SCROLL_DELAY = 200000; // Legacy - now uses AUTO_REFRESH_INTERVAL for consistency
$BURST_TIMER = 18; // Timer for scroll burst animation (milliseconds)
$BURST_SPEED = 18; // Speed of scroll animation
$BURST_LENGTH = 4; // Length of scroll burst
$BURST_EXTRA_DELAY = 150; // Extra delay between bursts
$BURST_END_DELAY = 300; // Delay at end of burst
$INTRA_BURST_DELAY = 1; // Delay within burst

/**
 * Generate footer HTML
 * 
 * @return string Footer HTML
 */
function generateFooterHTML() {
    global $FOOTER_ENABLED, $FOOTER_TEXT, $FOOTER_HEIGHT;
    
    if (!$FOOTER_ENABLED) {
        return '';
    }
    
    return '<div class="page-footer" style="height: ' . $FOOTER_HEIGHT . ';">' . 
           htmlspecialchars($FOOTER_TEXT) . 
           '</div>';
}

/**
 * Log debug message if debug mode is enabled
 * 
 * @param string $message The debug message
 * @return void
 */
function debugLog($message) {
    global $DEBUG_MODE;
    
    if ($DEBUG_MODE) {
        error_log('[BellePoule Debug] ' . $message);
    }
}
?>