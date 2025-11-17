<?php

// Include required dependencies
require_once 'pays.php';

/********************************************************/
/*                         POULES                       */
/********************************************************/
/*
   define( 'RANK_INIT',     0 );
   define( 'NO_IN_POULE',   1 );
   define( 'NB_VICT',       2 );
   define( 'NB_MATCH',      3 );
   define( 'TD',            4 );
   define( 'TR',            5 );
   define( 'RANK_IN_POULE', 6 );
   define( 'RANK_FIN',      7 );
   define( 'STATUT',        8 );
   define( 'FIRST_RES',     9);
 */

// Be kind to var_dump!
define( 'RANK_INIT',    'RANK_INIT');
define( 'NO_IN_POULE',  'NO_IN_POULE');
define( 'NB_VICT',      'NB_VICT');
define( 'NB_MATCH',     'NB_MATCH');
define( 'TD',           'TD');
define( 'TR',           'TR');
define( 'RANK_IN_POULE','RANK_IN_POULE');
define( 'RANK_FIN',     'RANK_FIN');
define( 'STATUT',       'STATUT');
define( 'FIRST_RES',    9);



function getTireurList( $domXml )
{
    $tireurList = array();
    
    $equipesListXml = $domXml->getElementsByTagName( 'Equipes' );
    foreach( $equipesListXml as $equipesXml ) 
    {
	foreach( $equipesXml->childNodes as $equipeXml ) 
	{
	    if( get_class( $equipeXml ) == 'DOMElement' )
		$tireurList[ getAttribut( $equipeXml, 'ID' ) ] = $equipeXml;
	}
    }
    
    if( count( $tireurList ) == 0 )
    {
	$tireursXml	= $domXml->getElementsByTagName( 'Tireurs' );
	foreach ($tireursXml as $tireurs) 
	{
	    $tireurXml = $tireurs->getElementsByTagName( 'Tireur' );
	    foreach ($tireurXml as $tireur) 
	    {
		$tireurList[ getAttribut( $tireur, 'ID' ) ] = $tireur;
	    }
	}
    }
    return $tireurList;
}

function getTireurRankingList( $phaseXml )
{
    $tireurList = array();
    
    foreach( $phaseXml->childNodes as $phaseChild )
    {
	if( isset( $phaseChild->localName ) )
	{
	    if( $phaseChild->localName == 'Tireur' || $phaseChild->localName == 'Equipe' )
	    {
		$tireurList[ getAttribut( $phaseChild, 'REF' ) ] = array_fill( 0, 15, "" );
		$tireurList[ getAttribut( $phaseChild, 'REF' ) ][ RANK_INIT ] = getAttribut( $phaseChild, 'RangInitial' );
		$tireurList[ getAttribut( $phaseChild, 'REF' ) ][ RANK_FIN ] = getAttribut( $phaseChild, 'RangFinal' );
		$tireurList[ getAttribut( $phaseChild, 'REF' ) ][ STATUT ] = getAttribut( $phaseChild, 'Statut' );
	    }
	    else if( $phaseChild->localName == 'Poule' )
	    {
		foreach( $phaseChild->childNodes as $pouleChild )
		{

		    if( isset( $pouleChild->localName ) )
		    {

			$tireurList[ getAttribut( $pouleChild, 'REF' ) ][ NO_IN_POULE ] = getAttribut( $pouleChild, 'NoDansLaPoule' );
			$tireurList[ getAttribut( $pouleChild, 'REF' ) ][ NB_VICT ] = getAttribut( $pouleChild, 'NbVictoires' );

			//			$tireurList[ getAttribut( $pouleChild, 'REF' ) ][ NB_MATCH ] = getAttribut( $pouleChild, 'NbMatches' );
			$tireurList[ getAttribut( $pouleChild, 'REF' ) ][ NB_MATCH ] = 0; //LPo
			// When BellePoule writes NbMatches, it still counts Exclusion and Abandon in
			// This leads to wrong V/M indices. Instead, we will increment this field for each valid match we see
			$tireurList[ getAttribut( $pouleChild, 'REF' ) ][ TD ] = getAttribut( $pouleChild, 'TD' );
			$tireurList[ getAttribut( $pouleChild, 'REF' ) ][ TR ] = getAttribut( $pouleChild, 'TR' );
			$tireurList[ getAttribut( $pouleChild, 'REF' ) ][ RANK_IN_POULE ] = getAttribut( $pouleChild, 'RangPoule' );
		    }
		}
	    }
	}
    }
    
    $matchXml = $phaseXml->getElementsByTagName( 'Match' );
    foreach( $matchXml as $match ) 
    {
	//*** 2 tireurs par match
	$tireur1Ref = -1;
	$tireur1Pos = -1;
	$tireur1Mark = -1;
	$tireur2Ref = -1;
	$tireur2Pos = -1;
	$tireur2Mark = -1;
	
	$k = 1;
	foreach( $match->childNodes as $tireur )
	{
	    if( isset( $tireur->tagName ) )
	    {
		if( $k == 1 )
		{
		    $tireur1Ref = getAttribut( $tireur, 'REF' );
		    $tireur1Pos = $tireurList[ $tireur1Ref ][ NO_IN_POULE ];
		    if( getAttribut( $tireur, 'Statut' ) == POULE_STATUT_VICTOIRE )
		    {
			$tireur1Mark = getAttribut( $tireur, 'Statut' );
			$tireurList[ $tireur1Ref ][ NB_MATCH ] ++; //LPo
			
			if( getAttribut( $tireur, 'Score' ) != getAttribut( $phaseXml, 'ScoreMax' ) )
			    $tireur1Mark .= getAttribut( $tireur, 'Score' );
		    }
		    else if( getAttribut( $tireur, 'Statut' ) == POULE_STATUT_ABANDON )
		    {
			$tireur1Mark = POULE_STATUT_ABANDON;
			$tireur2Mark = POULE_STATUT_ABANDON;
		    }
		    else if( getAttribut( $tireur, 'Statut' ) == POULE_STATUT_EXPULSION )
		    {
			$tireur1Mark = POULE_STATUT_EXPULSION;
			$tireur2Mark = POULE_STATUT_EXPULSION;
		    }
		    else
		    {
			if( getAttribut( $tireur, 'Statut' ) == POULE_STATUT_DEFAITE )
		    	    $tireurList[ $tireur1Ref ][ NB_MATCH ] ++; //LPo
			$tireur1Mark = getAttribut( $tireur, 'Score' );
		    }
		}
		else // $k==2
		{
		    $tireur2Ref = getAttribut( $tireur, 'REF' );
		    $tireur2Pos = $tireurList[ $tireur2Ref ][ NO_IN_POULE ];
		    if( getAttribut( $tireur, 'Statut' ) == POULE_STATUT_VICTOIRE )
		    {
			$tireur2Mark = getAttribut( $tireur, 'Statut' );
			$tireurList[ $tireur2Ref ][ NB_MATCH ] ++; //LPo
			if( getAttribut( $tireur, 'Score' ) != getAttribut( $phaseXml, 'ScoreMax' ) )
			    $tireur2Mark .= getAttribut( $tireur, 'Score' );
		    }
		    else if( getAttribut( $tireur, 'Statut' ) == POULE_STATUT_ABANDON )
		    {	
			$tireur1Mark = POULE_STATUT_ABANDON;
			$tireur2Mark = POULE_STATUT_ABANDON;
		    }
		    else if( getAttribut( $tireur, 'Statut' ) == POULE_STATUT_EXPULSION )
		    {	
			$tireur1Mark = POULE_STATUT_EXPULSION;
			$tireur2Mark = POULE_STATUT_EXPULSION;
		    }
		    else if( $tireur2Mark != POULE_STATUT_ABANDON && $tireur2Mark != POULE_STATUT_EXPULSION )
		    {
			if( getAttribut( $tireur, 'Statut' ) == POULE_STATUT_DEFAITE )
			    $tireurList[ $tireur2Ref ][ NB_MATCH ] ++; //LPo
			$tireur2Mark = getAttribut( $tireur, 'Score' );
		    }
		}
		
		$k++;
	    }
	}
	
	$tireurList[ $tireur1Ref ][ FIRST_RES + $tireur2Pos - 1 ] = $tireur1Mark;
	$tireurList[ $tireur2Ref ][ FIRST_RES + $tireur1Pos - 1 ] = $tireur2Mark;
	
	//	echo $tireur1Ref . ' Vs ' . $tireur2Ref . ' -> ' . $tireur1Mark . ' (' . $tireur2Pos . ') ' . ' / ' . $tireur2Mark . ' (' . $tireur1Pos . ')<br/> ';
    }
    
    return $tireurList;
}



/********************************************************/

/*                   CLASSEMENT GENERAL                 */

/********************************************************/
function renderClassement( $domXml )
{
    $list = '';
    
    $tireurCount = 0;
    
    $searchLabelParent = ( $domXml->documentElement->localName == 'CompetitionParEquipes' ) ? 'Equipes' : 'Tireurs';
    $searchLabelChildren = ( $domXml->documentElement->localName == 'CompetitionParEquipes' ) ? 'Equipe' : 'Tireur';
    
    $tireursXml	= $domXml->getElementsByTagName( $searchLabelParent );
    foreach ($tireursXml as $tireurs) 
    {
	$tireurXml = $tireurs->getElementsByTagName( $searchLabelChildren );
	$tireurCount = 0;
	
	foreach ($tireurXml as $tireur) 
	{
	    if( getAttribut( $tireur, 'Statut' ) != STATUT_ABSENT )
		$tireurCount++;
	}
    }
    
    $list .= '
	<table class="listeTireur">
		<tr>
			<th>Rang</th>
			<th>Nom</th>';
    
    if( $searchLabelChildren == 'Tireur' )
    {
	$list .= '
				<th>Prénom</th>';
    }
    
    $list .= '
			<th>Club</th>
		</tr>';
    
    $i = 1;
    $pair = "pair";
    while( $i <= $tireurCount )
    {
	foreach ($tireursXml as $tireurs) 
	{
	    $tireurXml = $tireurs->getElementsByTagName( $searchLabelChildren );
	    
	    foreach ($tireurXml as $tireur) 
	    {
		if( getAttribut( $tireur, 'Classement' ) == $i )
		{
		    $list .= '
						<tr class="'. $pair . '">
							<td>' . getAttribut( $tireur, 'Classement' ) . '</td>
							<td>' . getAttribut( $tireur, 'Nom' ) . '</td>';
		    
		    if( $searchLabelChildren == 'Tireur' )
		    {
			$list .= '
								<td>' . getAttribut( $tireur, 'Prenom' ) . '</td>';
		    }
		    
		    $list .= '
							<td>' . getAttribut( $tireur, 'Club' ) . '</td>
						</tr>';
		    
		    $pair = $pair == "pair" ? "impair" : "pair";
		}
	    }
	}

	$i++;
    }

    $list .= '
	</table>';

    return $list;

}

/**
 * Utility functions for the BellePoule dynamic display system
 * 
 * This file contains helper functions used throughout the application
 */

/**
 * Check if the browser is Internet Explorer
 * 
 * @return boolean True if the browser is IE, false otherwise
 */
function IE()
{
    $ua = htmlentities($_SERVER['HTTP_USER_AGENT'], ENT_QUOTES, 'UTF-8');
    if (preg_match('~MSIE|Internet Explorer~i', $ua) || (strpos($ua, 'Trident/7.0; rv:11.0') !== false)) {
        // Browser is Internet Explorer
        return true;
    }
    return false;
}

/**
 * Format a fencing score display
 * 
 * @param string $score The score to format
 * @return string Formatted score
 */
function formatScore($score) 
{
    if (!is_numeric($score)) {
        return $score;
    }
    return intval($score);
}

/**
 * Get the current file name with query parameters
 * 
 * @param string $filename The cotcot filename
 * @return string File query string
 */
function getFileQuery($filename) 
{
    return "?file=" . urlencode($filename);
}

/**
 * Get the configured cotcot directory path.
 * Ensures no double appending of the directory.
 * 
 * @return string The sanitized cotcot directory path.
 */
require_once 'config.php';

function getCotcotDirectory() {
    global $COTCOT_DIRECTORY;

    // Fallback to default directory if not set
    if (empty($COTCOT_DIRECTORY)) {
        $COTCOT_DIRECTORY = __DIR__ . DIRECTORY_SEPARATOR . 'cotcot';
    }

    // Resolve the directory path
    $resolvedPath = realpath($COTCOT_DIRECTORY);
    if ($resolvedPath === false) {
        debugLog("Failed to resolve cotcot directory: {$COTCOT_DIRECTORY}");
        return __DIR__ . DIRECTORY_SEPARATOR . 'cotcot';
    }

    debugLog("Resolved cotcot directory: {$resolvedPath}");
    return $resolvedPath;
}

/**
 * Sanitize a file path to remove double slashes and ensure proper path construction.
 * 
 * @param string $path The file path to sanitize.
 * @return string The sanitized path.
 */
if (!function_exists('sanitizeFilePath')) {
    function sanitizeFilePath($path) {
        // Function implementation
        $path = realpath($path);
        if ($path === false) {
            return null;
        }
        return $path;
    }
}

/**
 * Extraire les informations d'en-tête du fichier XML de la compétition
 * 
 * @param DOMDocument $xml Le document XML contenant les informations
 * @return array Les informations formatées pour l'en-tête
 */
function getCompetitionHeaderInfo($xml) {
    $info = [];
    
    if (!$xml || !$xml->documentElement) {
        return $info;
    }
    
    $root = $xml->documentElement;
    
    // Extraire les attributs principaux
    $info['TitreLong'] = $root->getAttribute('TitreLong');
    $info['Arme'] = formatWeapon($root->getAttribute('Arme'));
    $info['Sexe'] = formatGender($root->getAttribute('Sexe'));
    $info['Organisateur'] = $root->getAttribute('Organisateur');
    $info['Categorie'] = $root->getAttribute('Categorie');
    $info['Niveau'] = $root->getAttribute('Niveau');
    $info['Date'] = $root->getAttribute('Date');
    $info['Scratch'] = $root->getAttribute('Scratch');
    $info['Debut'] = $root->getAttribute('Debut');
    
    return $info;
}

/**
 * Générer le HTML pour le bandeau d'en-tête de compétition
 * 
 * @param DOMDocument $xml Le document XML contenant les informations
 * @return string Le HTML du bandeau
 */
function renderCompetitionHeader($xml) {
    $info = getCompetitionHeaderInfo($xml);
    
    if (empty($info)) {
        return '';
    }
    
    $html = '<header class="competition-header">';
    
    // Première ligne: Arme/Sexe à gauche, Titre au centre, Organisateur à droite
    $html .= '<div class="header-top-row">';
    $html .= '<div class="header-left">' . htmlspecialchars($info['Arme']) . ' ' . htmlspecialchars($info['Sexe']) . '</div>';
    $html .= '<div class="header-center">' . htmlspecialchars($info['TitreLong']) . '</div>';
    $html .= '<div class="header-right">' . htmlspecialchars($info['Organisateur']) . '</div>';
    $html .= '</div>';
    
    // Deuxième ligne: Catégorie, Niveau, Date, Scratch et Début
    $html .= '<div class="header-bottom-row">';
    
    // Ajout du bouton accueil à gauche
    $html .= '<div class="header-buttons-left">';
    $html .= '<a href="selcotpage.php" class="header-button" title="Retour à la sélection de compétition">';
    $html .= '<svg class="header-icon" viewBox="0 0 24 24"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z" /></svg>';
    $html .= '<span>Accueil</span>';
    $html .= '</a>';
    $html .= '</div>';
    
    // Information du milieu
    $infoLine = [];
    if (!empty($info['Categorie'])) {
        $infoLine[] = 'Catégorie: ' . htmlspecialchars($info['Categorie']);
    }
    if (!empty($info['Niveau'])) {
        $infoLine[] = 'Niveau: ' . htmlspecialchars($info['Niveau']);
    }
    if (!empty($info['Date'])) {
        $infoLine[] = 'Date: ' . htmlspecialchars($info['Date']);
    }
    if (!empty($info['Scratch'])) {
        $infoLine[] = 'Scratch: ' . htmlspecialchars($info['Scratch']);
    }
    if (!empty($info['Debut'])) {
        $infoLine[] = 'Début: ' . htmlspecialchars($info['Debut']);
    }
    
    $html .= '<div class="header-info">' . implode(' | ', $infoLine) . '</div>';
      // Ajout des boutons à droite
    $html .= '<div class="header-buttons-right">';
    
    // Bouton plein écran avec logo BellePoule
    $html .= '<button id="fullscreen-button" class="header-button" title="Affichage plein écran">';
    $html .= '<img src="logo/bellpoule.svg" class="header-icon bellepoule-logo" alt="BellePoule" />';
    $html .= '<span>Plein écran</span>';
    $html .= '</button>';
    
    // Bouton outils
    $html .= '<button id="tools-button" class="header-button" title="Afficher les outils">';
    $html .= '<svg class="header-icon" viewBox="0 0 24 24"><path d="M19.14 12.94c.04-.3.06-.61.06-.94 0-.32-.02-.64-.07-.94l2.03-1.58c.18-.14.23-.41.12-.61l-1.92-3.32c-.12-.22-.37-.29-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54c-.04-.24-.24-.41-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.05.3-.09.63-.09.94s.02.64.07.94l-2.03 1.58c-.18.14-.23.41-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z" /></svg>';
    $html .= '<span>Outils</span>';
    $html .= '</button>';
    $html .= '</div>';
    
    $html .= '</div>';
    
    $html .= '</header>';
    
    return $html;
}

/**
 * Affiche le classement final des tireurs
 * 
 * @param DOMDocument $xml Document XML contenant les données
 * @return string HTML du classement final
 */
function renderFinalClassement($xml) 
{
    $output = "<div class='tblhd_top' onclick='mickey()'><span class='tbl_banner'>&#9776; CLASSEMENT FINAL</span><br>";
    $output .= "<div class='tblhd'><div></div>\n";
    $fixed_height = isset($_GET['scroll'])?'fh':'';
    $output .= "<table id='scrollme' class='listeTireur $fixed_height'>\n";
    
    $isEquipe = ($xml->documentElement->localName === 'CompetitionParEquipes');
      if ($isEquipe) {
        // Vérifier d'abord si des équipes ont un attribut Classement
        $equipes = $xml->getElementsByTagName('Equipe');
        $equipesData = array();
        $hasClassement = false;
        
        foreach ($equipes as $equipe) {
            $classement = $equipe->getAttribute('Classement');
            if ($classement && is_numeric($classement)) {
                $hasClassement = true;
                $membres = [];
                foreach ($equipe->getElementsByTagName('Tireur') as $tireur) {
                    $membres[] = htmlspecialchars($tireur->getAttribute('Nom')) . ' ' . htmlspecialchars($tireur->getAttribute('Prenom')) .
                        ( ($club = $tireur->getAttribute('Club')) ? ' <span class=\"club\">(' . htmlspecialchars($club) . ')</span>' : '' );
                }                $equipesData[] = array(
                    'classement' => intval($classement),
                    'nom' => $equipe->getAttribute('Nom'),
                    'club' => $equipe->getAttribute('Club'),
                    'region' => $equipe->getAttribute('Region') ?: '',
                    'nation' => $equipe->getAttribute('Nation'),
                    'membres' => $membres
                );
            }
        }
          if (!$hasClassement) {
            // Aucune équipe n'a d'attribut Classement, afficher le message
            $output .= "<tbody>";
            $output .= "<tr class='impairQL'>";
            $output .= "<td colspan='6' class='CENTER VR' style='padding: 40px; font-size: 1.2em; font-weight: bold;'>";
            $output .= "La compétition n'est pas terminée, l'affichage du classement aura lieu une fois toutes les phases terminées et validées.";
            $output .= "</td>";
            $output .= "</tr>";
            $output .= "</tbody>";
        } else {            // Affichage pour les compétitions par équipes avec classement
            $output .= "<thead>
                <tr>
                    <th class='RIG VR'>Rang</th>
                    <th class='LEF VR'>Équipe</th>
                    <th class='LEF VR'>Club</th>
                    <th class='LEF VR'>Région</th>
                    <th class='LEF VR'>Nation</th>
                    <th class='LEF VR'>Membres</th>
                </tr>
            </thead>
            <tbody>";
            
            // Tri par classement
            usort($equipesData, function($a, $b) { return $a['classement'] - $b['classement']; });
            $rowClass = "impairQL";            foreach ($equipesData as $equipe) {
                // Déterminer la couleur de médaille
                $medalStyle = '';
                $medalIcon = '';
                switch ($equipe['classement']) {
                    case 1:
                        $medalStyle = 'background-color: #FFF8DC; color: #8B4513; font-weight: bold;'; // Light gold background
                        $medalIcon = '🥇 ';
                        break;
                    case 2:
                        $medalStyle = 'font-weight: bold;'; // Keep current background, just bold text
                        $medalIcon = '🥈 ';
                        break;
                    case 3:
                        $medalStyle = 'background-color: #DEB887; color: #8B4513; font-weight: bold;'; // Light bronze background
                        $medalIcon = '🥉 ';
                        break;
                }
                
                $output .= "<tr class='$rowClass' style='$medalStyle'>";
                $output .= "<td class='RIG VR B'>" . $medalIcon . $equipe['classement'] . "</td>";
                $output .= "<td class='LEF VR'><strong>" . htmlspecialchars($equipe['nom']) . "</strong></td>";
                $output .= "<td class='LEF VR'>" . htmlspecialchars($equipe['club']) . "</td>";
                $output .= "<td class='LEF VR'>" . htmlspecialchars($equipe['region']) . "</td>";
                $output .= "<td class='LEF VR'>";
                if (!empty($equipe['nation'])) {
                    $output .= flag_icon($equipe['nation'], 'flag_icon') . ' ' . htmlspecialchars($equipe['nation']);
                }
                $output .= "</td>";
                $output .= "<td class='LEF VR'>";
                $output .= "<ul style='margin:0;padding-left:18px;'>";
                foreach ($equipe['membres'] as $membre) {
                    $output .= "<li>" . $membre . "</li>";
                }
                $output .= "</ul>";
                $output .= "</td>";                $output .= "</tr>";
                $rowClass = ($rowClass == "impairQL") ? "pairQL" : "impairQL";            }
            $output .= "</tbody>";
        }
    } else {
        // Vérifier d'abord si des tireurs ont un attribut Classement
        $tireurs = $xml->getElementsByTagName('Tireur');
        $tireursData = array();
        $hasClassement = false;
        
        foreach ($tireurs as $tireur) {
            $classement = $tireur->getAttribute('Classement');
            if ($classement && is_numeric($classement)) {
                $hasClassement = true;                $tireursData[] = array(
                    'classement' => intval($classement),
                    'nom' => $tireur->getAttribute('Nom') . ' ' . $tireur->getAttribute('Prenom'),
                    'club' => $tireur->getAttribute('Club'),
                    'region' => $tireur->getAttribute('Region') ?: '',
                    'nation' => $tireur->getAttribute('Nation')
                );
            }
        }
          if (!$hasClassement) {
            // Aucun tireur n'a d'attribut Classement, afficher le message
            $output .= "<tbody>";
            $output .= "<tr class='impairQL'>";
            $output .= "<td colspan='5' class='CENTER VR' style='padding: 40px; font-size: 1.2em; font-weight: bold;'>";
            $output .= "La compétition n'est pas terminée, l'affichage du classement aura lieu une fois toutes les phases terminées et validées.";
            $output .= "</td>";
            $output .= "</tr>";
            $output .= "</tbody>";
        } else {            // Affichage pour les compétitions individuelles avec classement
            $output .= "<thead>
                <tr>
                    <th class='RIG VR'>Rang</th>
                    <th class='LEF VR'>Athlète</th>
                    <th class='LEF VR'>Club</th>
                    <th class='LEF VR'>Région</th>
                    <th class='LEF VR'>Nation</th>
                </tr>
            </thead>
            <tbody>";
            
            // Tri par classement
            usort($tireursData, function($a, $b) { return $a['classement'] - $b['classement']; });
            $rowClass = "impairQL";            foreach ($tireursData as $tireur) {
                // Déterminer la couleur de médaille
                $medalStyle = '';
                $medalIcon = '';
                switch ($tireur['classement']) {
                    case 1:
                        $medalStyle = 'background-color: #FFF8DC; color: #8B4513; font-weight: bold;'; // Light gold background
                        $medalIcon = '🥇 ';
                        break;
                    case 2:
                        $medalStyle = 'font-weight: bold;'; // Keep current background, just bold text
                        $medalIcon = '🥈 ';
                        break;
                    case 3:
                        $medalStyle = 'background-color: #DEB887; color: #8B4513; font-weight: bold;'; // Light bronze background
                        $medalIcon = '🥉 ';
                        break;
                }
                
                $output .= "<tr class='$rowClass' style='$medalStyle'>";
                $output .= "<td class='RIG VR B'>" . $medalIcon . $tireur['classement'] . "</td>";
                $output .= "<td class='LEF VR'>";
                if (!empty($tireur['nation'])) {
                    $output .= flag_icon($tireur['nation'], 'flag_icon');
                }
                $output .= htmlspecialchars($tireur['nom']) . "</td>";
                $output .= "<td class='LEF VR'>" . htmlspecialchars($tireur['club']) . "</td>";
                $output .= "<td class='LEF VR'>" . htmlspecialchars($tireur['region']) . "</td>";
                $output .= "<td class='LEF VR'>";
                if (!empty($tireur['nation'])) {
                    $output .= flag_icon($tireur['nation'], 'flag_icon') . ' ' . htmlspecialchars($tireur['nation']);
                }
                $output .= "</td>";
                $output .= "</tr>";
                $rowClass = ($rowClass == "impairQL") ? "pairQL" : "impairQL";
            }
            $output .= "</tbody>";
        }
    }
    $output .= "</table></div></div>";
    return $output;
}

/**
 * Helper function to add empty rows to a table
 */
function addEmptyRows($lastClass = 'impairO') {
    $output = '';
    $classes = ['impairO', 'pairO'];
    $currentClass = array_search($lastClass, $classes);
    
    // Add 5 empty rows
    for ($i = 0; $i < 5; $i++) {
        $class = $classes[($currentClass + $i) % 2];
        $output .= "<tr class='$class'><td colspan='20'>&nbsp;</td></tr>\n";
    }
    return $output;
}


/**
 * Formate le nom d'une arme
 */
function formatWeapon($arme) {
    $weapons = array(
        'E' => 'Épée',
        'F' => 'Fleuret', 
        'S' => 'Sabre'
    );
    
    return isset($weapons[$arme]) ? $weapons[$arme] : $arme;
}

/**
 * Formate le genre
 */
function formatGender($sexe) {
    $genders = array(
        'M' => 'Hommes',
        'F' => 'Dames',
        'X' => 'Mixte'
    );
    
    return isset($genders[$sexe]) ? $genders[$sexe] : $sexe;
}

/**
 * Nettoie et sécurise un chemin de fichier
 */
function sanitizeFilePath($path) {
    // Supprimer les caractères dangereux
    $path = str_replace(array('..', '\\'), '', $path);
    $path = preg_replace('/[^a-zA-Z0-9._\-\/]/', '', $path);
    return $path;
}

/**
 * Vérifie si une chaîne se termine par un suffixe donné
 */
if (!function_exists('endsWith')) {
    function endsWith($haystack, $needle) {
        $length = strlen($needle);
        if ($length == 0) {
            return true;
        }
        return (substr($haystack, -$length) === $needle);
    }
}

/**
 * Log de débogage
 */
if (!function_exists('debugLog')) {
    function debugLog($message) {
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            error_log("[DEBUG] " . $message);
        }
    }
}
?>
