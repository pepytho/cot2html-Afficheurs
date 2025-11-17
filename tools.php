<?php
/**
 * Tools and utility functions for BellePoule dynamic display
 */

// Constants for fencer status
define("STATUT_PRESENT", "Q");
define("STATUT_ABSENT", "F");
define("STATUT_ELIMINE", "N");

// Constants for pool status
define("POULE_STATUT_VICTOIRE", "V");
define("POULE_STATUT_DEFAITE", "D");
define("POULE_STATUT_ABANDON", "A");
define("POULE_STATUT_EXPULSION", "E");

// Constants for gender
define("SEXE_MALE", "M");
define("SEXE_FEMALE", "F");

/**
 * Check if a string ends with a given substring
 * 
 * @param string $str The string to check
 * @param string $sub The substring to look for at the end
 * @return bool True if $str ends with $sub, false otherwise
 */
function endsWith($str, $sub) 
{
    return (substr($str, strlen($str) - strlen($sub)) == $sub);
}

/**
 * Recursively explore directories to find .cotcot files
 * 
 * @param string $chemin The path to explore
 * @return array List of .cotcot files found
 */
function explorer($chemin)
{
    $competitionList = [];

    // Check if current path is a .cotcot file
    if (endsWith($chemin, ".cotcot")) {
        $competitionList[] = $chemin;
    }

    // If path is a directory, recursively check its contents
    if (is_dir($chemin)) {
        $me = opendir($chemin);
        while ($child = readdir($me)) {
            if ($child != '.' && $child != '..') {
                $competitionList = array_merge(
                    $competitionList, 
                    explorer($chemin . DIRECTORY_SEPARATOR . $child)
                );
            }
        }
        closedir($me); // Properly close directory handle
    }

    return $competitionList;
}

/**
 * Get the selected competition ID from query parameters
 * 
 * @return int|string The competition ID or -1 if not set
 */
function selectedCompetition()
{
    if (isset($_GET['idCompetition'])) {
        return $_GET['idCompetition'];
    }

    return -1;
}

/**
 * Get the selected phase ID from query parameters
 * 
 * @return int|string The phase ID or -1 if not set
 */
function selectedPhase()
{
    if (isset($_GET['phaseId'])) {
        return $_GET['phaseId'];
    }

    return -1;
}

/**
 * Get the name of the selected phase
 * 
 * @param DOMDocument $domXml XML document to search in
 * @return string The name of the selected phase or 'ListeInscrit' as default
 */
function selectedPhaseName($domXml)
{
    if (isset($_GET['phaseId'])) {
        $phase = getPhaseXmlElement($domXml, $_GET['phaseId']);

        if ($phase != null) {
            return $phase->tagName;
        }
    }

    return 'ListeInscrit';
}

/**
 * Get a phase XML element by its ID
 * 
 * @param DOMDocument $docXml XML document to search in
 * @param int|string $idPhase Phase ID to find
 * @return DOMElement|null The found phase element or null
 */
function getPhaseXmlElement($docXml, $idPhase)
{
    $phasesXml = $docXml->getElementsByTagName('Phases');

    foreach ($phasesXml as $phases) {
        foreach ($phases->childNodes as $phase) {
            if (isset($phase->tagName) && getAttribut($phase, 'PhaseID') == $idPhase) {
                return $phase;
            }
        }
    }

    return null;
}

/**
 * Create a URL with parameters
 * 
 * @param array $paramsOverlay Parameters to include in the URL
 * @param int $clear Whether to start with a clean URL (1) or merge with existing parameters (0)
 * @return string The generated URL
 */
function makeUrl($paramsOverlay, $clear = 0) 
{
    $url = 'index.php?action=affichage';
    
    if ($clear == 0) {
        $params = $_GET;
        
        // Remove refresh parameter if present
        if (isset($params['refresh'])) {
            unset($params['refresh']);
        }
        
        // Merge with overlay parameters
        $params = array_merge($params, $paramsOverlay);
    } else {
        $params = $paramsOverlay;
    }

    // Build query string
    foreach ($params as $k => $v) {
        if ($v !== null) {
            $url .= '&' . urlencode($k) . '=' . urlencode($v);
        }
    }
    
    return $url;
}

/**
 * Get an attribute value from an XML element
 * 
 * @param DOMElement $xmlElement The XML element
 * @param string $attributName The attribute name
 * @return string The attribute value
 */
function getAttribut($xmlElement, $attributName)
{
    return $xmlElement->getAttribute($attributName);
}

/**
 * Fill session with competition titles
 * 
 * @return void
 */
function fillSessionWithTitreLong()
{
    if (isset($_SESSION['cotcotFiles'])) {
        $DOMxml = new DOMDocument('1.0', 'utf-8');
        $titre = [];

        foreach ($_SESSION['cotcotFiles'] as $file) {
            $DOMxml->load($file);
            $competXml = $DOMxml->documentElement;
            $titre[$file] = getAttribut($competXml, 'TitreLong');
        }

        $_SESSION['titreList'] = $titre;
    }
}

/**
 * Get the human-readable label for a category code
 * 
 * @param string $categorie Category code
 * @return string Human-readable category label
 */
function getCategorieLibelle($categorie)
{
    $categories = [
        'B' => 'Benjamin',
        'M' => 'Minime',
        'C' => 'Cadet',
        'J' => 'Junior',
        'S' => 'Sénior',
        'V' => 'Vétéran'
    ];
    
    return $categories[$categorie] ?? $categorie;
}

/**
 * Get the human-readable label for a weapon code
 * 
 * @param string $arme Weapon code
 * @return string Human-readable weapon label
 */
function getArmeLibelle($arme)
{
    $armes = [
        'F' => 'Fleuret',
        'E' => '&Eacute;p&eacute;e',
        'S' => 'Sabre'
    ];
    
    return $armes[$arme] ?? $arme;
}

/**
 * Get the human-readable label for a gender code
 * 
 * @param string $sexe Gender code
 * @return string Human-readable gender label
 */
function getSexeLabel($sexe)
{
    $sexes = [
        SEXE_MALE => 'Homme',
        SEXE_FEMALE => 'Dame'
    ];
    
    return $sexes[$sexe] ?? $sexe;
}
?>
