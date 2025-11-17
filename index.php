<?php
/**
 * Main controller for the BellePoule dynamic display system
 */

// Start session and set headers before any output
session_start();
header('Content-Type: text/html; charset=utf-8');
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Sat, 26 Jul 1997 05:00:00");

// Error reporting for debugging
// Comment these lines in production
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

// Include required files
require_once("tools.php");
require_once("my_phase_pointage.php");
require_once("config.php");
require_once("my6.php"); 
require_once("functions.php");
require_once("pays.php");
require_once("selcot.php");
require_once("screen-detector.php");
require_once("config.php");

// Enable debug logging
$DEBUG_MODE = false;

// Initialize variables
$filename = '';
$fileqry = '';
$xml = null;
$error_message = '';

// Get base directory for cotcot files
$cotcot_dir = getCotcotDirectory();
debugLog("Resolved cotcot directory: " . $cotcot_dir);

// Check for file parameter (file= or cotcot= for backward compatibility) and properly decode it
$fileParam = '';
if (isset($_GET['file'])) {
    $fileParam = urldecode($_GET['file']);
} elseif (isset($_GET['cotcot'])) {
    $fileParam = urldecode($_GET['cotcot']);
}
$fullPath = rtrim($cotcot_dir, '/') . '/' . $fileParam;

debugLog("Raw file parameter: " . (isset($_GET['file']) ? $_GET['file'] : (isset($_GET['cotcot']) ? $_GET['cotcot'] : 'not set')));
debugLog("Decoded file parameter: " . $fileParam);
debugLog("Attempting to load file: " . $fullPath);

if (!file_exists($fullPath)) {
    debugLog("File does not exist: " . $fullPath);
    $error_message = "Fichier non trouvé : " . htmlspecialchars($fullPath);
} else if (!is_readable($fullPath)) {
    debugLog("File is not readable: " . $fullPath);
    $error_message = "Fichier non lisible : " . htmlspecialchars($fullPath);
} else if (!preg_match('/\.cotcot$/', $fullPath)) {
    debugLog("Invalid file extension: " . $fullPath);
    $error_message = "Type de fichier invalide : " . htmlspecialchars($fullPath);
} else {
    // Load XML file
    $xml = new DOMDocument("1.0", "utf-8");
    $previous = libxml_use_internal_errors(true);

    if (file_exists($fullPath) && is_readable($fullPath)) {
        debugLog("File exists and is readable: $fullPath");
        $loaded = $xml->load($fullPath);
        if (!$loaded) {
            $errors = libxml_get_errors();
            $error_message = "Erreur lors du chargement du fichier XML";
            foreach ($errors as $error) {
                debugLog("XML Error: {$error->message} (Line: {$error->line}, Column: {$error->column})");
            }
            libxml_clear_errors();
        } else {
            debugLog("XML file successfully loaded: $fullPath");
        }
    } else {
        $error_message = "Le fichier n'existe pas ou n'est pas lisible : " . htmlspecialchars($fullPath);
        debugLog("File not found or not readable: $fullPath");
    }

    libxml_use_internal_errors($previous);
}

// Parse request parameters
$head_title = 'Cotcot';
$good_zoom  = 1;
$item   = isset($_GET['item']) ? $_GET['item'] : 'menu';
$tour   = isset($_GET['tour']) ? intval($_GET['tour']) : 1;
$ncol   = isset($_GET['ncol']) ? intval($_GET['ncol']) : 1;
$detail = isset($_GET['pack']) ? 0 : 1;
$fold   = isset($_GET['fold']) ? intval($_GET['fold']) : 0;
$abc    = isset($_GET['ABC']) ? intval($_GET['ABC']) : 0;
$scroll = isset($_GET['scroll']) ? intval($_GET['scroll']) : 1; // Défilement automatique activé par défaut
$class = '';

// Load saved parameters from cookies if available
$pageKey = isset($_GET['item']) ? $_GET['item'] : 'menu';
$zoomCookieName = "zoom_$pageKey";
$zoom = isset($_COOKIE[$zoomCookieName]) ? floatval($_COOKIE[$zoomCookieName]) : (isset($_GET['zoom']) ? floatval($_GET['zoom']) : $good_zoom);
$tabStart = isset($_COOKIE['tabStart']) ? intval($_COOKIE['tabStart']) : (isset($_GET['tabStart']) ? intval($_GET['tabStart']) : 256);
$tabEnd = isset($_COOKIE['tabEnd']) ? intval($_COOKIE['tabEnd']) : (isset($_GET['tabEnd']) ? intval($_GET['tabEnd']) : 2);
$scrollSpeed = isset($_COOKIE['scrollSpeed']) ? floatval($_COOKIE['scrollSpeed']) : 1.0; // Default scroll speed

// Save parameters to cookies for future use
setcookie($zoomCookieName, $zoom, time() + 3600, '/'); // Expires in 1 hour
setcookie('tabStart', $tabStart, time() + 3600, '/');
setcookie('tabEnd', 2, time() + 3600, '/');
setcookie('scrollSpeed', $scrollSpeed, time() + 3600, '/'); // Save scroll speed to cookie

// Set title and zoom based on selected item
switch ($item) {
    case 'lst':
        $head_title = 'Liste de Présence';
        $good_zoom = ($ncol == 1) ? 2.5 : 1.25;
        break;

    case 'poudet':
    case 'pou':
        $head_title = 'Poules';
        $good_zoom = 1;
        break;

    case 'clapou':
        $head_title = 'Classement Poules provisoire';
        $good_zoom = ($ncol == 1) ? 2 : 1.25;
        break;

    case 'clatab':
        $head_title = 'Classement Tableau provisoire';
        $good_zoom = ($ncol == 1) ? 2 : 1.75;
        break;
        
    case 'finalcla':
        $head_title = 'Classement Final définitif';
        $good_zoom = ($ncol == 1) ? 2 : 1.75;
        break;

    case 'tab':
        $head_title = 'Tableau';
        $good_zoom = 1.5;
        break;

    case 'flag':
        $class = 'flag_page';
        break;
    
    case 'menu':
        $head_title = getTitre($xml);
        break;
}

/**
 * Generate the UI controls for zoom and navigation
 * 
 * @param bool $showTableauSlider Whether to show the tableau slider
 * @return void
 */
function renderUIControls($showTableauSlider = false) {
    global $tabStart, $tabEnd, $zoom, $scrollSpeed; // Add scrollSpeed global
    $item = isset($_GET['item']) ? $_GET['item'] : 'menu';
    
    // Définir tabEnd à 2 par défaut si non défini
    if (!isset($tabEnd)) {
        $tabEnd = 2;
    }
    
    // Ouvre le panneau masqué
    echo '<div id="autohide" class="autohide">';
    
    // slider de zoom
    echo '<div class="slider-zoom" id="slider-zoom"></div><br>';
    
    // Scroll speed selector
    echo '<label for="scrollSpeedSelector" style="color: #000; font-weight: bold;">Scroll Speed:</label>';
    echo '<select id="scrollSpeedSelector" style="margin-bottom: 10px; padding: 5px; border-radius: 4px; border: 1px solid #00917B;">';
    // New speed range: 0.2 to 2.4, step 0.2
    $speeds = [];
    for ($i = 0.2; $i <= 2.4; $i += 0.2) {
        // Use round to avoid floating point precision issues for comparison
        $speeds[] = round($i, 1);
    }

    foreach ($speeds as $speed_val) {
        $selected_attr = (abs($scrollSpeed - $speed_val) < 0.01) ? 'selected' : ''; // Compare with tolerance
        $text = ($speed_val == 1.0) ? number_format($speed_val, 1) . 'x (Normal)' : number_format($speed_val, 1) . 'x';
        echo "<option value=\"" . number_format($speed_val, 1) . "\" $selected_attr>" . htmlspecialchars($text) . "</option>";
    }
    echo '</select><br>';

    if ($showTableauSlider) {
        // slider dual-handle pour tabStart/tabEnd
        echo '<div id="tableauRangeSelector"></div>';
    }

    // boutons de contrôle
    echo "<button class='buttonicon' onClick='scro(0)'>&check;</button>";
    echo "<button class='buttonicon' onClick='scro(1)'>&udarr;</button>";
    echo "<button class='buttonicon' onClick='scro(2)'>&olarr;</button>";

    // bouton vers le classement final
    $disabled = ($item == 'finalcla') ? '_dis' : '';
    echo "<button class='buttonicon{$disabled}' onClick='goToFinalPage()'>";
    echo    "<img class='buttoniconi{$disabled}' src='svg/ban_finalcla.svg' alt='Final'/>";
    echo "</button>";

    // Menu buttons
    $buttons = [
        'lst' => 'svg/liste.svg',
        'pou' => 'svg/pou.svg',
        'poudet' => 'svg/poudet.svg',
        'clapou' => 'svg/clapou.svg',
        'tab' => 'svg/tab1.svg',
        'clatab' => 'svg/clatab.svg'
    ];
    
    foreach ($buttons as $buttonItem => $iconSrc) {
        $disabled = ($item == $buttonItem) ? '_dis' : '';
        echo "<button class='buttonicon$disabled' onClick='item(\"$buttonItem\")'>";
        echo "<img class='buttoniconi$disabled' src='$iconSrc'/>";
        echo "</button>\n";
    }

    echo '</div>'; // ferme #autohide
}

// Set scrolling parameters based on page type (from config.php)
$burst_length = $BURST_LENGTH;
$burst_extra_delay = $BURST_EXTRA_DELAY;
$burst_speed = $BURST_SPEED;
$burst_end_delay = $BURST_END_DELAY; 
$burst_timer = $BURST_TIMER;
$intra_burst_delay = $INTRA_BURST_DELAY;

// Begin HTML output
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr">
<head>
    <title><?php echo htmlspecialchars($head_title); ?></title>
    <style>
        :root {
            --rescale: <?php printf("%5.3f", $zoom); ?>;
            --couleur-fond: #0A1E3F;
            --couleur-texte: #000;
            --couleur-texte-menu: #fff;
        }
        
        body, html {
            background-color: var(--couleur-fond);
            color: var(--couleur-texte-menu);
            margin: 0;
            padding: 0;
        }
        
        .spu23hp {
            height: 3vh;
            font-size: 3vh;
            font-weight: bolder;
            color: var(--couleur-texte-menu);
            max-width: 85vw;
            max-height: 50px;
            margin-left: 2vw;
            margin-right: 2vw;
            margin-top: 2vh;
            margin-bottom: 2vh;
        }
        
        .u23hp {
            height: 8vh;
            max-width: 85vw;
            max-height: 50px;
            margin-left: 2vw;
            margin-right: 2vw;
            margin-top: 2vh;
            margin-bottom: 2vh;
        }
        
        .button_div {
            margin-left: 2vw;
            margin-right: 2vw;
            margin-top: 2vh;
            margin-bottom: 2vh;
            text-decoration: none;
            background-color: #EEEBEE;
            color: #333333;
            padding: 0.5vh;
        }
        
        .button {
            margin: auto auto;
            font: bold 18px Arial;
            text-decoration: none;
            color: var(--couleur-texte);
        }
        
        /* Style pour le panneau d'outils */
        .autohide {
            visibility: hidden;
            border-radius: 1vw;
            padding: 1.5vw;
            border: solid 0.5vw #00917B;
            background: #42FEDC;
            position: fixed;
            top: 20vw;
            right: 4vw;
            left: auto;
            z-index: 100;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.5);
        }
        
        /* Basic flag styling in case CSS file fails */
        .flag-icon {
            display: inline-block;
            width: 1.33333333em;
            height: 1em;
            background-size: contain;
            background-position: 50%;
            background-repeat: no-repeat;
            vertical-align: middle;
            margin-right: 5px;
        }
        
        /* Error message styling */
        .error-container {
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background-color: rgba(255, 255, 255, 0.1);
            border-left: 4px solid #ff6b6b;
        }
        
        .error-heading {
            color: #ff6b6b;
            margin-top: 0;
        }
        
        .error-details {
            color: #ccc;
            font-family: monospace;
            margin: 20px 0;
            padding: 10px;
            background-color: rgba(0, 0, 0, 0.3);
            border-radius: 4px;
            white-space: pre-wrap;
            word-break: break-all;
        }
        
        .error-back {
            display: inline-block;
            margin-top: 20px;
            padding: 8px 16px;
            background-color: #00917B;
            color: #fff;
            text-decoration: none;
            border-radius: 4px;
        }
        
        .error-back:hover {
            background-color: #007C69;
        }
    </style>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <!-- Meta refresh removed - using JavaScript setInterval with configurable AUTO_REFRESH_INTERVAL instead -->
    <?php
    // Load appropriate CSS based on browser
    if (!IE()) {
        echo '<link rel="stylesheet" type="text/css" title="Design de base" href="css/const.css" />';
        echo '<link rel="stylesheet" type="text/css" title="Design de base" href="css/flag_icons.css" />';
    } else {
        echo '<link rel="stylesheet" type="text/css" title="Design de base" href="css/ie.css" />';
        echo '<link rel="stylesheet" type="text/css" title="Design de base" href="css/flag_icons_ie.css" />';
    }
    ?>
    <link href="css/nouislider.css" rel="stylesheet">
    <link href="css/header.css" rel="stylesheet">
    <?php if ($item == 'finalcla') { ?>
    <link href="css/final-classement.css" rel="stylesheet">
    <?php } ?>
    <?php outputScreenDetectionScript(); ?>
</head>

<body class="<?php echo $class; ?><?php if ($FOOTER_ENABLED) echo ' with-footer'; ?>"<?php if ($item != 'menu' && empty($error_message)) echo ' onload="startit(true);"'; ?> style="<?php if ($FOOTER_ENABLED) echo '--footer-height: ' . $FOOTER_HEIGHT . '; --header-height: ' . $HEADER_HEIGHT . ';'; ?>">
<?php
// Forcer scroll=1 par défaut
if (!isset($_GET['scroll'])) {
    $_GET['scroll'] = 1;
}
// Display error message if there is one
if (!empty($error_message)) {
    ?>
    <div class="error-container">
        <h2 class="error-heading">Erreur lors du chargement de la compétition</h2>
        <p><?php echo $error_message; ?></p>        <div class="error-details">
            Fichier demandé: <?php echo htmlspecialchars($fileParam); ?>
            Chemin complet: <?php echo htmlspecialchars($fullPath); ?>
        </div>
        <p>
            Vérifiez que:
            <ul>
                <li>Le fichier .cotcot existe à l'emplacement spécifié</li>
                <li>Le serveur web a les droits d'accès au fichier</li>
                <li>Le format XML du fichier est correct</li>
            </ul>
        </p>
        <a href="selcotpage.php" class="error-back">Retourner à la sélection de compétition</a>
    </div>
    <?php
} else {
    // Afficher le bandeau d'en-tête sur toutes les pages si le XML est chargé
    if ($xml) {
        echo renderCompetitionHeader($xml);
    }

    // Main content generation based on selected item
    if ($item == 'menu') {
        // Main menu page
        ?>
        <div class="spu23hp"><?php echo htmlspecialchars($head_title); ?></div>
        
        <a href='index.php<?php echo $fileqry; ?>&item=lst&tabStart=256&tabEnd=2&zoom=1&scroll=1'>
            <div class='button_div'>
                <img src='svg/ban_listel.svg' class='u23hp' alt="Liste des tireurs">
            </div>
        </a>
        
        <a href='index.php<?php echo $fileqry; ?>&item=pou&tabStart=256&tabEnd=2&zoom=1&scroll=1'>
            <div class='button_div'>
                <img src='svg/ban_pou.svg' class='u23hp' alt="Poules">
            </div>
        </a>
        
        <a href='index.php<?php echo $fileqry; ?>&item=clapou&tabStart=256&tabEnd=2&zoom=1&scroll=1'>
            <div class='button_div'>
                <img src='svg/ban_clapou.svg' class='u23hp' alt="Classement poules">
            </div>
        </a>
        
        <a href='index.php<?php echo $fileqry; ?>&item=tab&tabStart=256&tabEnd=2&zoom=1&scroll=1'>
            <div class='button_div'>
                <img src='svg/ban_tab.svg' class='u23hp' alt="Tableau">
            </div>
        </a>
        
        <a href='index.php<?php echo $fileqry; ?>&item=clatab&tabStart=256&tabEnd=2&zoom=1&scroll=1'>
            <div class='button_div'>
                <img src='svg/ban_clatab.svg' class='u23hp' alt="Classement tableau">
            </div>
        </a>
        
        <a href='index.php<?php echo $fileqry; ?>&item=finalcla&zoom=1&scroll=1'>
            <div class='button_div'>
                <img src='svg/ban_finalcla.svg' class='u23hp' alt="Classement final">
                <div style="text-align: center; font-weight: bold; color: #333;">Classement Final</div>
            </div>
        </a>
        
        <div class="button_div">
            <img src="svg/pointe.svg" class="u23hp" alt="Pointe">
        </div>
    <?php
    } else {
        // Content pages
        switch ($item) {
            case 'lst':
                renderUIControls(false);
                // Check if it's a team competition
                $isEquipe = ($xml->documentElement->localName === 'CompetitionParEquipes');
                
                if ($isEquipe) {
                    // For team competitions, show the presence list
                    echo renderListePresenceEquipes($xml);
                } else {
                    // For individual competitions, use the existing function
                    $etape = 0;
                    echo afficheClassementPoules($xml, $ncol, 1, $etape, 'LISTE DE PRÉSENCE');
                }
                break;

            case 'pou':
                renderUIControls(false);
                echo affichePoules($xml, $tour, 0);
                break;
            
            case 'poudet':
                renderUIControls(false);                echo affichePoules($xml, $tour, 1);
                break;

            case 'clapou':
                renderUIControls(false);
                // Check if it's a team competition
                $isEquipe = ($xml->documentElement->localName === 'CompetitionParEquipes');
                
                if ($isEquipe) {
                    // For team competitions, use the dedicated team ranking function
                    echo renderClassementPoulesEquipes($xml, $tour);
                } else {
                    // For individual competitions, use the existing function
                    $etape = 1;
                    echo afficheClassementPoules($xml, $ncol, $abc, $etape, 'CLASSEMENT POULES PROVISOIRE');
                }
                break;            case 'clatab':
                renderUIControls(false);
                // Check if it's a team competition
                $isEquipe = ($xml->documentElement->localName === 'CompetitionParEquipes');
                
                if ($isEquipe) {
                    // For team competitions, use the dedicated team tableau ranking function
                    echo renderClassementTableauxEquipes($xml);
                } else {
                    // For individual competitions, use the dedicated individual tableau ranking function
                    echo renderClassementTableauxIndividuels($xml);
                }
                break;
                
            case 'finalcla':
                renderUIControls(false);
                echo renderFinalClassement($xml);
                break;

            case 'flag':
                repairTableau($xml);
                echo drapeauxPodium($xml);
                break;            case 'tab':
                $burst_end_delay = 10;
                renderUIControls(true);
                repairTableau($xml);

                // Ensure proper scaling and folding logic for elimination tables
                if ($tabStart < $tabEnd || $tabStart > 256 || $tabEnd < 2) {
                    $tabStart = 256;
                    $tabEnd = 2;
                }

                // Get selected suite ID from URL parameter (default to "0" for main bracket)
                $selectedSuiteId = isset($_GET['suite']) ? $_GET['suite'] : '0';
                
                echo renderMyTableau($xml, $detail, $fold, 'TABLEAU', $selectedSuiteId);
                break;

            case 'clafin':
                echo renderClassement($xml);
                break;

            default:
                echo "Fichier en cours " . htmlspecialchars($filename) . "<br>";
                
                $te = IE() ? 2 : 16;
                $mixte = mixteMaleFemale($xml);
                
                // Show appropriate menu text based on gender mix
                switch ($mixte) {
                    case 'F':
                        echo "<a class='home' href='index.php{$fileqry}&item=lst&tabStart=256&tabEnd=2&scroll=1'>Tireuses</a><br>";
                        break;
                    case 'FM':
                        echo "<a class='home' href='index.php{$fileqry}&item=lst&tabStart=256&tabEnd=2&scroll=1'>Tireuses et tireurs</a><br>";
                        break;
                    case 'M':
                        echo "<a class='home' href='index.php{$fileqry}&item=lst&tabStart=256&tabEnd=2&scroll=1'>Tireurs</a><br>";
                        break;
                }

                if ($mixte != 'E') {
                    echo "<a class='home' href='index.php{$fileqry}&item=pou&tabStart=256&tabEnd=2&scroll=1'>Poules</a><br>";
                    echo "<a class='home' href='index.php{$fileqry}&item=clapou&tabStart=256&tabEnd=2&scroll=1'>Classement poules provisoire</a><br>";
                    echo "<a class='home' href='index.php{$fileqry}&item=tab&tabStart=256&tabEnd=2&scroll=1'>Tableau</a><br>";
                    echo "<a class='home' href='index.php{$fileqry}&item=clatab&tabStart=256&tabEnd=2&scroll=1'>Classement tableau provisoire</a><br>";
                    echo "<a class='home' href='index.php{$fileqry}&item=finalcla&scroll=1'>Classement final définitif</a><br>";
                }
                break;
        }
    }
}
?>

<?php if ($FOOTER_ENABLED): ?>
<div class="page-footer" style="height: <?php echo $FOOTER_HEIGHT; ?>;">
    <?php echo htmlspecialchars($FOOTER_TEXT); ?>
</div>
<?php endif; ?>

</body>

<!-- Include JavaScript libraries and set up parameters -->
<script src="js/nouislider.js"></script>
<script src="js/functions.js" type="text/javascript"></script>
<script src="js/scroll-refresh.js"></script>
<script src="js/bracket-lines.js"></script>
<script>
    // Timer configuration from config.php
    var intra_burst_delay = <?php echo $intra_burst_delay; ?>;
    var speed = <?php echo $burst_speed; ?>;
    var burst_timer = <?php echo $burst_timer; ?>;
    var glob_burst_length = <?php echo $burst_length; ?>;
    var extra_burst_delay = <?php echo $burst_extra_delay; ?>;
    var end_delay = <?php echo $burst_end_delay; ?>;
    window.scroll_delay = <?php echo $SCROLL_DELAY; ?>;
    window.auto_refresh_interval = <?php echo $AUTO_REFRESH_INTERVAL; ?>;
    
    console.log("Config loaded - SCROLL_DELAY:", window.scroll_delay, "ms, AUTO_REFRESH_INTERVAL:", window.auto_refresh_interval, "ms");
    console.log("Expected refresh every", window.auto_refresh_interval/1000, "seconds");
    
    // Update SCROLL_DELAY in scroll-refresh.js
    if (typeof window.updateScrollDelay === 'function') {
        window.updateScrollDelay(window.auto_refresh_interval); // Use AUTO_REFRESH_INTERVAL for all refreshes
    }
    
    // Set up centralized auto-refresh for all pages
    console.log("Setting up centralized auto-refresh with interval:", window.auto_refresh_interval, "ms (" + (window.auto_refresh_interval/1000) + " seconds)");
    console.log("Meta refresh tag removed - using JavaScript timer only");
    
    setInterval(function() {
        console.log("Centralized AUTO_REFRESH_INTERVAL triggered - refreshing page after", window.auto_refresh_interval/1000, "seconds");
        window.location.reload();
    }, window.auto_refresh_interval);

    // Cookie helper function
    function getCookie(name) {
        var value = "; " + document.cookie;
        var parts = value.split("; " + name + "=");
        if (parts.length == 2) return parts.pop().split(";").shift();
        return null;
    }
    
    // Function to set cookie
    function setCookie(name, value, days) {
        var expires = "";
        if (days) {
            var date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            expires = "; expires=" + date.toUTCString();
        }
        document.cookie = name + "=" + (value || "") + expires + "; path=/";
    }

    // Initialize currentScrollSpeedFactor from cookie or default
    var initialCookieSpeed = getCookie('scrollSpeed');
    console.log("index.php script: Raw scrollSpeed cookie value:", initialCookieSpeed);
    window.currentScrollSpeedFactor = parseFloat(initialCookieSpeed); 

    if (isNaN(window.currentScrollSpeedFactor) || window.currentScrollSpeedFactor <= 0) {
        window.currentScrollSpeedFactor = 1.0; // Default to 1.0 if cookie is invalid, not set, or not positive
        console.log("index.php script: scrollSpeed from cookie was invalid ('" + initialCookieSpeed + "'), zero, or negative. Defaulted window.currentScrollSpeedFactor to 1.0.");
        // Set the default value in cookie
        setCookie('scrollSpeed', '1.0', 30);
    } else {
        console.log("index.php script: Initialized window.currentScrollSpeedFactor from cookie to:", window.currentScrollSpeedFactor);
    }
    // The check `if (isNaN(window.currentScrollSpeedFactor) || window.currentScrollSpeedFactor <= 0)`
    // in scroll-refresh.js's DOMContentLoaded will also verify this.

    // Save zoom parameter to cookies when updated by noUiSlider
    var zoomSlider = document.getElementById('slider-zoom');
    if (zoomSlider != null) {
        noUiSlider.create(zoomSlider, {
            start: [<?php echo $zoom; ?>],
            range: {
                'min': [0.01],
                'max': [4.00]
            }
        });

        zoomSlider.noUiSlider.on('update', function(values, handle) {
            var zoomValue = values[handle];
            var pageKey = "<?php echo $pageKey; ?>";
            document.cookie = `zoom_${pageKey}=${zoomValue}; path=/; max-age=3600`; // Save zoom to cookie
            rescale(zoomValue); // Update CSS variable
        });
    }

    // Function to navigate to the "Classement Final" page
    function goToFinalPage() {
        var qry = parseQuery(location.search);
        qry["item"] = "finalcla";
        qry["zoom"] = getZoom();
        location.replace(rebuildQuery(qry));
    }

    document.addEventListener('DOMContentLoaded', function() {
        startit(true); // Enable autoscroll by default
        
        // Additional fallback for tab page autoscroll
        var urlParams = new URLSearchParams(window.location.search);
        var item = urlParams.get('item');
        if (item === 'tab') {
            setTimeout(function() {
                var scrollContainer = document.getElementById('scrollme');
                if (scrollContainer && typeof window.initAutoScroll === 'function') {
                    console.log('Fallback: Initializing autoscroll for tab page');
                    window.initAutoScroll('scrollme');
                }
            }, 1000);
        }

        // Event listener for scroll speed selector
        var scrollSpeedSelector = document.getElementById('scrollSpeedSelector');
        if (scrollSpeedSelector) {
            // Set initial value from global variable, ensuring it's formatted to one decimal place for matching
            scrollSpeedSelector.value = parseFloat(window.currentScrollSpeedFactor).toFixed(1);

            scrollSpeedSelector.addEventListener('change', function() {
                var newSpeed = parseFloat(this.value);
                window.currentScrollSpeedFactor = newSpeed;
                
                // Save to cookie
                document.cookie = `scrollSpeed=${newSpeed}; path=/; max-age=3600`;
                
                // Update URL with new speed
                const url = new URL(window.location);
                url.searchParams.set('scrollSpeed', newSpeed.toFixed(1));
                window.history.replaceState({}, '', url);
                
                console.log("Scroll speed changed to:", newSpeed, "- saved to cookie and URL");

                // Restart autoscroll if it's active to apply new speed immediately
                const container = document.getElementById('scrollme');
                if (container && typeof isAutoScrolling !== 'undefined' && isAutoScrolling) {
                    console.log("Restarting autoscroll with new speed:", newSpeed);
                    isAutoScrolling = false;
                    if (typeof scrollTimeout !== 'undefined') {
                         clearTimeout(scrollTimeout);
                    }
                   
                    setTimeout(() => {
                        if (typeof window.startAutoScroll === 'function') {
                            window.startAutoScroll(container);
                        } else {
                            console.error("startAutoScroll function not found");
                        }
                    }, 50); 
                } else if (container) {
                    let reason = "";
                    if (typeof isAutoScrolling === 'undefined') reason += "isAutoScrolling is undefined. ";
                    if (!isAutoScrolling) reason += "isAutoScrolling is false. ";
                    console.log("index.php DOMContentLoaded: Autoscroll not active or variables not accessible. " + reason + "New speed will apply on next auto-initiation.");
                } else {
                     console.log("index.php DOMContentLoaded: #scrollme container not found, cannot restart autoscroll.");
                }
            });
        }
    });

    // Initialize tableau slider if present
    var tableauSlider = document.getElementById('tableauSizeSelector');
    if (tableauSlider != null) {
        noUiSlider.create(tableauSlider, {
            start: [<?php echo $tabStart; ?>, <?php echo $tabEnd; ?>],
            connect: true,
            range: {
                'min': 2,
                'max': 512
            },
            step: 2,
            pips: {
                mode: 'values',
                values: [2, 4, 8, 16, 32, 64, 128, 256, 512],
                density: 100,
                format: {
                    to: function(value) {
                        return value;
                    },
                    from: function(value) {
                        return Number(value);
                    }
                }
            }
        });

        // When the slider value changes, update the URL
        tableauSlider.noUiSlider.on('update', function(values, handle) {
            var qry = parseQuery(location.search);
            // Arrondir à la valeur la plus proche en puissance de 2
            var powerOf2 = [2, 4, 8, 16, 32, 64, 128, 256, 512];
            var value = Math.round(values[handle]);
            var closest = powerOf2.reduce(function(prev, curr) {
                return (Math.abs(curr - value) < Math.abs(prev - value) ? curr : prev);
            });
            
            if (handle === 0) {
                qry["tabStart"] = closest;
            } else {
                qry["tabEnd"] = closest;
            }
            qry["zoom"] = getZoom();
            location.replace(rebuildQuery(qry));
        });
    }
</script>
</html>
