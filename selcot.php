<?php
/**
 * Cotcot files selection functions
 */

// Include configuration and shared functions
require_once("tools.php");
require_once("selcot.php");
require_once("functions.php");
require_once("config.php");
// Include the functions for handling cotcot files


// Include my6.php to use check_filename()
require_once 'my6.php';

/**
 * Explore directories to find .cotcot files, respecting the configured directory.
 * 
 * @return array List of .cotcot files found.
 */
// Ensure the directory is correctly retrieved and scanned
function getAllCotcotFiles() {
    $cotcotDirectory = getCotcotDirectory();

    // Check if the directory exists
    if (!file_exists($cotcotDirectory) || !is_dir($cotcotDirectory)) {
        debugLog("Directory not found or invalid: {$cotcotDirectory}");
        return [];
    }

    // Scan the directory for .cotcot files
    $files = array_filter(
        scandir($cotcotDirectory),
        function ($file) use ($cotcotDirectory) {
            return is_file($cotcotDirectory . DIRECTORY_SEPARATOR . $file) && pathinfo($file, PATHINFO_EXTENSION) === 'cotcot';
        }
    );

    debugLog("Found " . count($files) . " .cotcot files in directory: {$cotcotDirectory}");

    // Return full paths to the files
    return array_map(function ($file) use ($cotcotDirectory) {
        return $cotcotDirectory . DIRECTORY_SEPARATOR . $file;
    }, $files);
}

/**
 * Render the cotcot selection form
 * 
 * @return string HTML of the form
 */
function renderCotcotSelection() {
    $cotcotFiles = getAllCotcotFiles();
    
    $html = '<div class="cotcot-selection">';
    $html .= '<h1>Sélectionnez une compétition</h1>';
    
    if (empty($cotcotFiles)) {
        $html .= '<p class="error">Aucun fichier .cotcot trouvé dans le répertoire configuré.</p>';
        $html .= '<p>Vérifiez le répertoire configuré: <code>' . htmlspecialchars(getCotcotDirectory()) . '</code></p>';
        $html .= '<p>Pour changer ce répertoire, modifiez la variable <code>$COTCOT_DIRECTORY</code> dans le fichier <code>config.php</code>.</p>';
        
        // Create cotcot directory if it doesn't exist
        $defaultDir = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'cotcot';
        if (!is_dir($defaultDir)) {
            if (@mkdir($defaultDir, 0755, true)) {
                $html .= '<p class="success">Le répertoire <code>cotcot</code> a été créé automatiquement. Veuillez y placer vos fichiers .cotcot.</p>';
            } else {
                $html .= '<p class="error">Impossible de créer automatiquement le répertoire <code>cotcot</code>. Veuillez le créer manuellement.</p>';
            }
        }
    } else {
        $html .= '<ul class="cotcot-file-list">';
        foreach ($cotcotFiles as $file) {
            $title = getCotcotTitle($file);
            $html .= '<li><a href="index.php' . getFileQuery($file) . '">' . htmlspecialchars($title) . '</a>';
            $html .= '<span class="file-path">' . htmlspecialchars(basename($file)) . '</span></li>';
        }
        $html .= '</ul>';
    }
    
    $html .= '</div>';
    return $html;
}
?>
