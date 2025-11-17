<?php
/**
 * Cotcot selection page
 * 
 * This page displays a list of available cotcot files and allows the user to select one.
 */

// Include required files
require_once 'config.php';
require_once 'functions.php';

// Set page title
$pageTitle = "BellePoule - Sélection de compétition";

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

?><!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr">
<head>
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <style>
        :root {
            --couleur-fond: #0A1E3F;
            --couleur-texte: #000;
            --couleur-texte-menu: #fff;
            --couleur-primaire: #00917B;
        }
        
        body, html {
            background-color: var(--couleur-fond);
            color: var(--couleur-texte-menu);
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2em;
        }
        
        h1 {
            color: #fff;
            border-bottom: 2px solid var(--couleur-primaire);
            padding-bottom: 0.5em;
        }
        
        .error {
            color: #ff6b6b;
            background: rgba(255, 107, 107, 0.1);
            padding: 1em;
            border-left: 4px solid #ff6b6b;
            margin: 1em 0;
        }
        
        ul {
            list-style-type: none;
            padding: 0;
        }
        
        li {
            margin-bottom: 1em;
            transition: transform 0.2s;
        }
        
        li:hover {
            transform: translateX(5px);
        }
        
        a {
            color: #fff;
            text-decoration: none;
            display: block;
            background: rgba(255, 255, 255, 0.1);
            padding: 1em;
            border-left: 4px solid var(--couleur-primaire);
            transition: background 0.2s;
        }
        
        a:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        code {
            font-family: monospace;
            background: rgba(255, 255, 255, 0.1);
            padding: 0.2em 0.4em;
            border-radius: 3px;
        }
        
        .config-info {
            margin-top: 2em;
            font-size: 0.9em;
            color: rgba(255, 255, 255, 0.7);
            padding: 1em;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 5px;
        }
    </style>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
</head>
<body>
    <div class="container">
        <?php
        // Display the list of .cotcot files
        $cotcotFiles = getAllCotcotFiles();
        if (empty($cotcotFiles)) {
            echo "<p>Aucun fichier .cotcot trouvé dans le répertoire configuré.</p>";
        } else {
            echo "<ul>";
            foreach ($cotcotFiles as $file) {
                $fileName = basename($file);
                // Remove any directory prefix and construct the proper path
                echo "<li><a href=\"index.php?file=" . urlencode($fileName) . "&item=lst&tabStart=256&tabEnd=16&zoom=0.8\">" . htmlspecialchars($fileName) . "</a></li>";
            }
            echo "</ul>";
        }
        ?>
        
        <div class="config-info">
            <h3>Information de configuration</h3>
            <p>Répertoire des fichiers cotcot : <code><?php echo htmlspecialchars(getCotcotDirectory()); ?></code></p>
            <p>Pour changer le répertoire des fichiers, modifiez la variable <code>$COTCOT_DIRECTORY</code> dans le fichier <code>config.php</code>.</p>
        </div>
        
        <?php
        // Display the directory being used
        $cotcotDirectory = getCotcotDirectory();
        $debugMessage = "Répertoire des fichiers cotcot utilisé : " . htmlspecialchars($cotcotDirectory);
        ?>

        <div class="config-info">
            <h3>Répertoire des fichiers cotcot</h3>
            <p><?php echo $debugMessage; ?></p>
        </div>
    </div>
</body>
</html>
