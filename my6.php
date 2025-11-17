<?php


define("NB_ROWS_TIREUR",     "2");

function isPouleFinished ( $pouleXml )
{
    $matches = $pouleXml->getElementsByTagName('Match');
    $no  = 0;
    $yes = 0;
    $isEquipe = ($pouleXml->ownerDocument->documentElement->localName === 'CompetitionParEquipes');
    foreach ($matches as $m)
    {
        $tireurs = $isEquipe ? $m->getElementsByTagName('Equipe') : $m->getElementsByTagName('Tireur');
        $s = array();
        $n = 0;
        foreach ($tireurs as $t)
        {
            $s[$n++] = $t->getAttribute('Statut');
        }
        // On vérifie que les index existent avant de les utiliser
        $v0 = (isset($s[0]) && in_array($s[0], ["V","D","A","E"])) ? true : false;
        $v1 = (isset($s[1]) && in_array($s[1], ["V","D","A","E"])) ? true : false;
        if ($v0 || $v1)
            $yes++;
        else
            $no++;
    }
    if ($yes==0)
        return -1; // not started
    if ($no>0)
        return 0;  // started, not finished
    return 1;      
}

function prepareTablePoule ($poule)
{
    $table = array();
    $matches = $poule->getElementsByTagName('Match');
    $isEquipe = ($poule->ownerDocument->documentElement->localName === 'CompetitionParEquipes');
    $tl      = $isEquipe ? $poule->getElementsByTagName('Equipe') : $poule->getElementsByTagName('Tireur');
    $lu      = array();  // Tireurs ou équipes de la poule
    
    foreach ($tl as $t)
    if ($t->hasAttribute("NoDansLaPoule"))
    {
	$REF = $t->getAttribute('REF');
	$no = $t->getAttribute('NoDansLaPoule');
	$lu [$t->getAttribute("REF")] = $no;
	$table[$no] = array( 'REF' => $REF, 'Ma' => 0, 'Vi' => 0, 'TD' => 0, 'TR' => 0,
			     'Pl' => 0, 'Sco' => array(), 'Sta' => array() ); 
    }

    foreach ($matches as $m)
    {
	$tm = $isEquipe ? $m->getElementsByTagName('Equipe') : $m->getElementsByTagName('Tireur');
	if ($tm->length < 2) continue; // sécurité
	$rf0     = $tm[0]->getAttribute('REF');
	$rf1     = $tm[1]->getAttribute('REF');
	$st0     = $tm[0]->getAttribute('Statut');
	$st1     = $tm[1]->getAttribute('Statut');
	$sc0     = $tm[0]->getAttribute('Score');
	$sc1     = $tm[1]->getAttribute('Score');
	$no0     = $lu[$rf0];
	$no1     = $lu[$rf1];

	$table[$no0]['Sta'][$no1]=$st0;
	$table[$no0]['Sco'][$no1]=$sc0;
	$table[$no1]['Sta'][$no0]=$st1;
	$table[$no1]['Sco'][$no0]=$sc1;

	if (is_numeric($sc0))
	{
	    $table[$no0]['TD'] += $sc0;
	    $table[$no1]['TR'] += $sc0;
	}
	if (is_numeric($sc1))
	{
	    $table[$no1]['TD'] += $sc1;
	    $table[$no0]['TR'] += $sc1;
	}
	
	if ($st0 == 'V')
	{
	    $table[$no0]['Vi']++;
	    $table[$no0]['Ma']++;
	}
	if ($st0 == 'D')
	{
	    $table[$no0]['Ma']++;
	}

	if ($st1 == 'V')
	{
	    $table[$no1]['Vi']++;
	    $table[$no1]['Ma']++;
	}
	if ($st1 == 'D')
	{
	    $table[$no1]['Ma']++;
	}
    }

    $f = array();
    for ($no = 1; $no <= count($table); $no++)
	$f[$no] = -1.0*computeVMfloat (0, $table[$no]['Vi'], $table[$no]['Ma'], $table[$no]['TD'], $table[$no]['TR']);

    asort($f);
    $pl = 1;
    $np = 0;
    $of = 999999999999;
    foreach ($f as $no => $v)
    {
	$np++;
	if ($v>$of)
	    $pl = $np;
	$table[$no]['Pl'] = $pl;
	$of = $v;
    }
    return $table;
}


function renderTourDePoules ($phaseXml, $tireurs,$arbitres, $detail)
{
    $rt = '';
    $scomax = $phaseXml->getAttribute('ScoreMax');
    $poules = $phaseXml->getElementsByTagName('Poule');
    $isEquipe = ($phaseXml->ownerDocument->documentElement->localName === 'CompetitionParEquipes');
    //  $rt = "<article class='lespoules'>";
    foreach ($poules as $p)
    {
	$table = prepareTablePoule ($p);
	
	$pid = $p->getAttribute('ID');
	$pis = $p->getAttribute('Piste');
	$dat = $p->getAttribute('Date').' '.$p->getAttribute('Heure');
	$txt2 = explode(" ", $dat);
	$txt = (count($txt2)>1)? $txt2[1]: $dat;

	$r = "<div  class='poule_div'>";
	
	$r .= "<table class='poule_tab' style='border-collapse:collapse;'>";

	
	$info = "<span class='poule_arb'>Poule </span>$pid<span class='poule_arb'>";
	if (strlen($pis)>0)
	    $info .= " - piste </span>$pis ";
	if (strlen($txt)>0)
	    $info .= "<span class='poule_arb'> - </span>$txt";
	
	$al  = $p->getElementsByTagName('Arbitre');

	if (isset($al[0]) && (1 || $detail))
	{
	    $ref = $al[0]->getAttribute('REF');
	    $nom = $arbitres[$ref]['Nom'];
	    $info .= "<br><span class='poule_arb'>Arbitre: " . $arbitres[$ref]['Nom'] .' '. $arbitres[$ref]['Prenom'] . "</span>";
	}



	// NOM No
	$r .= "<tr><td colspan='2' class='poule_doc'>$info</td>";
	for ($no = 1 ; $no <= count($table); $no++)
	    $r .= "<td class='poule_not'>$no</td>";
	if ($detail)
	{
	    $r .= "<td class='poule_tit'>V/M</td>";
	    $r .= "<td class='poule_tit'>&permil;</td>";
	    $r .= "<td class='poule_tit'>TD</td>";
	    $r .= "<td class='poule_tit'>TR</td>";
	    $r .= "<td class='poule_tit'>Ind</td>";
	    $r .= "<td class='poule_tit'>Pl</td>";
	}
	$r .= "</tr>\n";
	for ($no = 1 ; $no <= count($table); $no++)
	{
	    $r .= "<tr><td class='poule_nom'>";
	    $ref = $table[$no]['REF'];
	    if ($isEquipe && isset($tireurs[$ref]['ACCU']['Nom'])) {
		// Affichage équipe : nom + membres uniquement sur la page lst
		$nomEquipe = $tireurs[$ref]['ACCU']['Nom'];
		$r .= "<strong>" . htmlspecialchars($nomEquipe) . "</strong>";
		if (isset($_GET['lst']) && isset($tireurs[$ref]['MEMBRES']) && is_array($tireurs[$ref]['MEMBRES'])) {
		    $r .= "<ul style='margin:0;padding-left:18px;'>";
		    foreach ($tireurs[$ref]['MEMBRES'] as $membre) {
			$r .= "<li>" . htmlspecialchars($membre['Nom']) . ' ' . htmlspecialchars($membre['Prenom']);
			if (!empty($membre['Club'])) $r .= ' <span class=\"club\">(' . htmlspecialchars($membre['Club']) . ')</span>';
			$r .= "</li>";
		    }
		    $r .= "</ul>";
		}
	    } else {
		// Affichage classique tireur
		$nom = $tireurs[$ref]['ACCU']['Nom'] . ' ' . $tireurs[$ref]['ACCU']['Prenom'];
		$nat = $tireurs[$ref]['ACCU']['Nation'];
		if (!empty($nat)) {
		    $r .= flag_icon($nat, 'flag_icon');
		} else {
		    $r .= ' ';
		}
		$r .= (strlen($nom)>30)?fractureNom($nom):$nom;
	    }
	    $r .=  "</td><td class='poule_nor'>$no</td>";
	    for ($co = 1 ; $co <= count($table); $co++)
	    {
		if ($co == $no)
		    $r .= "<td class='poule_dia'> </td>";
		else 
		{
		    $sta = (isset($table[$no]['Sta'][$co]))?$table[$no]['Sta'][$co]:' ';
		    $sco = (isset($table[$no]['Sta'][$co]))?$table[$no]['Sco'][$co]:' ';
		    switch($sta)
		    {
			case 'V': $r .= ($sco == $scomax)? "<td class='poule_sco'>V</td>" : "<td class='poule_sco'>V$sco</td>"; break;
			case 'E': $r .= "<td class='poule_sco poule_exclusion'>Ex</td>"; break;
 			case 'A': $r .= "<td class='poule_sco poule_abandon'>Ab</td>"; 	 break;
			case 'D': $r .= "<td class='poule_sco'>$sco</td>";  		break;
			default:  $r .= "<td class='poule_sco'></td>";
		    }
		}
	    }

	    if ($table[$no]['Ma']>0)
	    {
		
		$ma = $table[$no]['Ma'];
		$vi = $table[$no]['Vi'];
		$td = $table[$no]['TD'];
		$tr = $table[$no]['TR'];
		$pl = $table[$no]['Pl'];
		$ind = sprintf("%+d",$td-$tr);
		
		$txt =  sprintf("%3.0f",1000*$vi / $ma);
		if ($detail)
		{
		    $r .= "<td class='poule_res'>$vi/$ma</td>";
		    $r .= "<td class='poule_res'>$txt</td>";
		    $r .= "<td class='poule_res'>$td</td>";
		    $r .= "<td class='poule_res'>$tr</td>";
		    $r .= "<td class='poule_res'>$ind</td>";
		    $r .= "<td class='poule_res'>$pl</td>";
		}
		$r .= "</tr>";
	    }
	    else
	    {
		if ($detail)
		{
		    $r .= "<td class='poule_res'></td>"; // Vi
		    $r .= "<td class='poule_res'></td>"; // V/M
		    $r .= "<td class='poule_res'></td>"; // TD
		    $r .= "<td class='poule_res'></td>"; // TR
		    $r .= "<td class='poule_res'></td>"; // IND
		    $r .= "<td class='poule_res'></td>"; // Pl
		}
		$r .= "</tr>";

	    }
	    $r .= "</tr>";
	}
	$r .= "</table></div>\n";
	$rt .= $r;
    }
    //   $rt .= "</article>";
    return $rt;
}

function etatTourDePoulesFinished ($phaseXml)
{
    $poules = $phaseXml->getElementsByTagName('Poule');
    //    echo "<h1>Is tour " . $phaseXml->getAttribute('ID') , " finished?<br></h1>";
    $not_started  = 0;
    $started      = 0;
    $finished     = 0;
    foreach ($poules as $p)
    {
	$f = isPouleFinished ($p);
	//	echo "Poule ID:" . $p->getAttribute('ID') .  "=$f <br>";
	switch ($f)
	{
	    case -1: $not_started++; break;
	    case 0 : $started++;     break;
	    case +1: $finished++;    break;
	}
    }

    //    echo "Started:$started NotSta:$not_started Finished:$finished<br>";
    $r=0;
    if ($not_started==0 && $started==0 && $finished>0)
	$r=1; // all finished
    if ($started==0 && $finished==0)
	$r=-1; // no started

    //   echo "Result=$r<br>";
    return $r;  // on going
}

function getPhaseEnCoursID ($topXml)
{
    $phasesXml = $topXml->getElementsByTagName('Phases');
    return $phasesXml[0]->getAttribute('PhaseEnCours');
}

function getAllPhases ($topXml)
{
    $phasesXml = $topXml->getElementsByTagName('Phases');
    $phases = array();
    foreach ($phasesXml[0]->childNodes as $node)
    if ($node->hasAttributes()) 
	$phases[] = $node;
    return $phases;
}

function countTourDePoules ($topXml )
{
    $phases = getAllPhases($topXml);
    $cnt    = 0;
    foreach ($phases as $phase)
    if ($phase->localName =='TourDePoules')
	$cnt++;
    return $cnt;
}

function getArbitres($xml)
{
    $r = array();
    $arbitres = $xml->getElementsByTagName('Arbitre');
    foreach ($arbitres as $a)
    {
	if ($a->hasAttribute('ID'))
	{
	    $ref = $a->getAttribute('ID');
	    $r[$ref] = array(
		'Nom'    => $a->getAttribute('Nom'),
		'Prenom' => $a->getAttribute('Prenom'),
		'Club'   => $a->getAttribute('Club'),
		'Nation' => $a->getAttribute('Nation'));
	}
    }
    return $r;
}

function affichePoules($xml, $tour,$detail)
{
    $isEquipe = ($xml->documentElement->localName === 'CompetitionParEquipes');
    
    $r = '';
    $head = '';
    $head .= "<div class='tblhd_top' onclick='mickey()'><span class='tbl_banner'>&#9776; POULES (résultats provisoires)</span><br>";
    $head .= "<div class='tblhdpou'>\n";
    $fixed_height = isset($_GET['scroll'])?'fhpou':'';

    $head .= "<div id='scrollme' class='listePoules $fixed_height'>\n";

    $phases  = getAllPhases($xml);
    $tireurs = suitTireurs ($xml);
    $arbitres = getArbitres($xml);
    $cnt = 0;
    foreach ($phases as $phase)
    {
        if ($phase->localName =='TourDePoules')
        {
            $cnt++;
            if ($tour == $cnt)
                $r .= renderTourDePoules($phase,$tireurs,$arbitres,$detail);
        }
    }
    $foot = '</div></div></div>';
    return $head . $r . $foot;
}

function decideOrdre ($topXml)
{
    $encours = getPhaseEnCoursID ($topXml);
    //    $tot     = countTourDePoules ($topXml);
    $phases  = getAllPhases($topXml);

    // These two variables decide how we sort the displayed table
    $r = array('PHA' => 0,      // Phase 0
	       'TAG' => 'ABC'); // Alphabetic ordering

    if ($encours=='')
	echo "Oups, vide alors<br>";
    
    foreach ($phases as $p)
    {
	$pid = $p->getAttribute('PhaseID');
	$pha = $p->localName;
	switch($pha)
	{
	    case 'TourDePoules':
	    $etat = etatTourDePoulesFinished($p);
	    if      ($encours>$pid)  		  // Fini et classé par BellePoule
		$r = array('PHA' => $pid, 'TAG' => 'Rf');
	    else if (($encours==$pid||$encours=='') && $etat==1) // Fini, mais pas classé par BellePoule
		$r = array('PHA' => $pid, 'TAG' => 'VM');
	    else if ($encours==$pid && $etat==0) // En cours
		$r = array('PHA' => $pid, 'TAG' => 'VM');
	    else if ($encours==$pid && $etat==-1) //Pas commencé
		$r = array('PHA' => $pid, 'TAG' => 'Po');
	    else if ($encours==$pid-1) //Pas commencé, en cours de composition
	    {}// On garde l'ancienne façon de classer
	    else
	    {} // On garde l'ancienne façon de classer
	    break;

	    case 'PointDeScission':
	    // On garde l'ancienne façon de classer
	    break;

	    case 'PhaseDeTableaux':
	    if ($encours>$pid)
		$r = array('PHA' => $pid, 'TAG' => 'RangFinal');
	    break;
	    
	    case 'ClassementGeneral':
	    if ($encours==$pid)
		$r = array('PHA' => $pid, 'TAG' => 'RangFinal');
	    break;
	}
    }
    return $r;
}

function addContenu(&$a, $phase, $titre, $element,  $attribut)
{
    if (count($a) < 1)
    {
	$a['TITRES']   =array();
	$a['ELEMENTS'] =array();
	$a['PHASES']   =array();
	$a['ATTRIBUTS']=array();
    }
    array_push ($a['TITRES'],   $titre);
    array_push ($a['ELEMENTS'], $element);
    array_push ($a['PHASES'],   $phase);
    array_push ($a['ATTRIBUTS'],$attribut);
}

function contenuListeTireurs()
{
    $r = array();
    $acc='ACCU';
    addContenu($r,$acc,"Statut","Present","class='VR'");
    addContenu($r,$acc,"Athlete","Flag","");
    addContenu($r,$acc,"Nom","NomPrenom","class='VR'");
    addContenu($r,$acc,"Ranking","Ranking", "class='B RIG VR'");
    addContenu($r,$acc,"Club","Club","class='VR'");
    return $r;
}

function contenuClassementFinal()
{
    $r = array();
    $acc='ACCU';
    //    addContenu($r,$acc,"Athlete","line","");
    addContenu($r,$acc,"Place","PlaTab", "class='B RIG VR'");
    addContenu($r,$acc,"Athlete","Flag","");
    addContenu($r,$acc,"Nom","NomPrenom","class='VR'");
    addContenu($r,$acc,"Club","Club","class='VR'");
    if (isset($_GET['dbg']))
    {
	
	addContenu($r,$acc,"Out","out", "class='B RIG VR'");
	addContenu($r,$acc,"tab","intab", "class='B RIG VR'");
	addContenu($r,$acc,"tab","PlaTabTri", "class='B RIG VR'");
	addContenu($r,$acc,"tdo","tourdone", "class='B RIG VR'");
    }
    addContenu($r,$acc,"Piste","PiTa", "class='B RIG VR'");
    addContenu($r,$acc,"Heure","DaTa", "class='B RIG VR'");
    return $r;
}


function decideContenu ($topXml)
{
    $acc='ACCU';
    $encours = getPhaseEnCoursID ($topXml);
    $tot     = countTourDePoules ($topXml);
    $phases  = getAllPhases($topXml);

    // These variables decide how we sort the displayed table
    $r = array();

 
    $cnt=0;
    $need_acc = 0;
    $need_tab = 0;
    foreach ($phases as $p)
    {
	$pid = $p->getAttribute('PhaseID');
	$pha = $p->localName;
	switch($pha)
	{
	    case 'PhaseDeTableaux':
	    //$need_tab = $pid;
	    break;
	    
	    case 'TourDePoules':
	    $cnt++;
	    $etat = etatTourDePoulesFinished($p);
	    if ($cnt>1 && $tot>1)
	    {
		// Pas le premier de plusieurs tours
		if      ($encours>$pid)  		  // Fini et classé par BellePoule
		{
                       addContenu($r,$acc,"Athlete","Flag","");
    addContenu($r,$acc,"Nom","NomPrenom","class='VR'");
    addContenu($r,$acc,"Club","Club","class='VR'");
		    addContenu($r, $pid, "Tour $cnt fini", "V/M", "class='MID'");
		    addContenu($r, $pid, "Tour $cnt fini", "TD-TR", "class='MID'");
		    addContenu($r, $pid, "Tour $cnt fini", "Pl", "class='RIG VR'");
		    $need_acc = 1;
		}
		else if ($encours==$pid && $etat==1) // Fini, mais pas classé par BellePoule
		{
                       addContenu($r,$acc,"Athlete","Flag","");
    addContenu($r,$acc,"Nom","NomPrenom","class='VR'");
    addContenu($r,$acc,"Club","Club","class='VR'");
		    addContenu($r, $pid, "Tour $cnt", "V/M",    "class='MID'");
		    addContenu($r, $pid, "Tour $cnt", "TD-TR",  "class='VR'");
		    $need_acc=1;
		}
		else if ($encours==$pid && $etat==0) // En cours
		{
                       addContenu($r,$acc,"Athlete","Flag","");
    addContenu($r,$acc,"Nom","NomPrenom","class='VR'");
    addContenu($r,$acc,"Club","Club","class='VR'");
		    addContenu($r, $pid, "Tour $cnt prov.", "Po", "");
		    addContenu($r, $pid, "Tour $cnt prov.", "Pi", "");
		    addContenu($r, $pid, "Tour $cnt prov.", "V/M",    "class='MID'");
		    addContenu($r, $pid, "Tour $cnt prov.", "TD-TR",  "class='VR'");
		    $need_acc=1;
		}
		else if ($encours==$pid && $etat==-1) //Pas commencé
		{
                       addContenu($r,$acc,"Athlete","Flag","");
    addContenu($r,$acc,"Nom","NomPrenom","class='VR'");
    addContenu($r,$acc,"Club","Club","class='VR'");
		    addContenu($r, $pid, "Tour $cnt", "Po", "class='B'");
		    addContenu($r, $pid, "Tour $cnt", "Pi", "class='B'");
		    addContenu($r, $pid, "Tour $cnt", "Da", "class='B VR'");
		}
		else if ($encours==$pid-1) //Pas commencé, en cours de composition
		{
                       addContenu($r,$acc,"Athlete","Flag","");
    addContenu($r,$acc,"Nom","NomPrenom","class='VR'");
    addContenu($r,$acc,"Club","Club","class='VR'");
		    addContenu($r, $pid, "Tour $cnt", "Po", "class='B VR'");
		}
		else // Pas commencé, même pas en composition
		{
                       addContenu($r,$acc,"Athlete","Flag","");
    addContenu($r,$acc,"Nom","NomPrenom","class='VR'");
    addContenu($r,$acc,"Club","Club","class='VR'");
		    addContenu($r, $pid, "Tour $cnt", "-", "class='VR'");
		}
	    }
	    else  if ($cnt<$tot)
	    {
		// Plusieur tours, pas le dernier
		if  ($encours>$pid+1)  		  // Fini et classé par BellePoule, et le tour d'après a commencé
		{
                       addContenu($r,$acc,"Athlete","Flag","");
    addContenu($r,$acc,"Nom","NomPrenom","class='VR'");
    addContenu($r,$acc,"Club","Club","class='VR'");
		    addContenu($r, $pid, "Tour $cnt fini", "V/M", "class='MID'");
		    addContenu($r, $pid, "Tour $cnt fini", "TD-TR", "class='MID'");
		    addContenu($r, $pid, "Tour $cnt fini", "Pl", "class='RIG VR'");
		}
		else if ($encours==$pid+1)  		  // Fini et classé par BellePoule, mais le tour d'après pas commencé
		{
                       addContenu($r,$acc,"Athlete","Flag","");
    addContenu($r,$acc,"Nom","NomPrenom","class='VR'");
    addContenu($r,$acc,"Club","Club","class='VR'");
		    addContenu($r, $pid, "Tour $cnt", "V/M", "class='MID'");
		    addContenu($r, $pid, "Tour $cnt", "TD-TR", "class='MID'");
		    addContenu($r, $pid, "Tour $cnt", "Pl", "class='RIG VR'");
		    $need_acc=1;
		}
		else if ($encours==$pid && $etat==1) // Fini, mais pas classé par BellePoule
		{
                       addContenu($r,$acc,"Athlete","Flag","");
    addContenu($r,$acc,"Nom","NomPrenom","class='VR'");
    addContenu($r,$acc,"Club","Club","class='VR'");
		    addContenu($r, $pid, "Tour $cnt", "V/M", "class='MID'");
		    addContenu($r, $pid, "Tour $cnt", "TD-TR", "class='VR'");
		    $need_acc=1;
		}
		else if ($encours==$pid && $etat==0) // En cours
		{
                       addContenu($r,$acc,"Athlete","Flag","");
    addContenu($r,$acc,"Nom","NomPrenom","class='VR'");
    addContenu($r,$acc,"Club","Club","class='VR'");
		    addContenu($r, $pid, "Tour $cnt", "Po", "class='MID'");
		    addContenu($r, $pid, "Tour $cnt", "V/M", "class='MID'");
		    addContenu($r, $pid, "Tour $cnt", "TD-TR", "class='VR'");
		}
		else if ($encours==$pid && $etat==-1) //Pas commencé
		{
                       addContenu($r,$acc,"Athlete","Flag","");
    addContenu($r,$acc,"Nom","NomPrenom","class='VR'");
    addContenu($r,$acc,"Club","Club","class='VR'");
		    addContenu($r, $pid, "Tour $cnt", "RI", "class='RIG'");
		    addContenu($r, $pid, "Tour $cnt", "Po", "class='B'");
		    addContenu($r, $pid, "Tour $cnt", "Pi", "class='B'");
		    addContenu($r, $pid, "Tour $cnt", "Da", "class='B VR'");
		}
		else if ($encours==$pid-1) //Pas commencé, en cours de composition
		{
                       addContenu($r,$acc,"Athlete","Flag","");
    addContenu($r,$acc,"Nom","NomPrenom","class='VR'");
    addContenu($r,$acc,"Club","Club","class='VR'");
		    addContenu($r, $pid, "Tour $cnt", "RI", "class='RIG'");
		    addContenu($r, $pid, "Tour $cnt", "Po", "class='B'");
		    addContenu($r, $pid, "Tour $cnt", "Pi", "class='B'");
		    addContenu($r, $pid, "Tour $cnt", "Da", "class='B VR'");
		}
		else // Pas commencé, même pas en composition
		{
                       addContenu($r,$acc,"Athlete","Flag","");
    addContenu($r,$acc,"Nom","NomPrenom","class='VR'");
    addContenu($r,$acc,"Club","Club","class='VR'");
		    addContenu($r, $pid, "Tour $cnt", "-", "class='VR'");
		}
	    }
	    else if ($cnt==1 && $tot==1)
	    {
		// Un seul tour
		if      ($encours>$pid)  		  // Fini et classé par BellePoule
		{
		    addContenu($r, $acc, "Total",   "Pl", "class='RIG B VR'");
                       addContenu($r,$acc,"Athlete","Flag","");
    addContenu($r,$acc,"Nom","NomPrenom","class='VR'");
    addContenu($r,$acc,"Club","Club","class='VR'");
		    addContenu($r, $pid, "Poule", "Po", "class='RIG VR'");
		    addContenu($r, $acc, "Total",   "V/M", "class='MID'");
		    addContenu($r, $acc, "Total",   "&permil;", "class='RIG'");
		    addContenu($r, $acc, "Total",   "TD-TR", "class='MID'");
		    addContenu($r, $acc, "Total",   "Ind", "class='RIG VR'");
		}
		else if (($encours==$pid||$encours=='') && $etat==1) // Fini, mais pas classé par BellePoule
		{
		    addContenu($r, $acc, "Total",   "Pl", "class='RIG B VR'");
                       addContenu($r,$acc,"Athlete","Flag","");
    addContenu($r,$acc,"Nom","NomPrenom","class='VR'");
    addContenu($r,$acc,"Club","Club","class='VR'");
		    addContenu($r, $pid, "Poule", "Po", "class='RIG VR'");
		    addContenu($r, $acc, "Total",   "V/M", "class='MID'");
		    addContenu($r, $acc, "Total",   "&permil;", "class='RIG'");
		    addContenu($r, $acc, "Total",   "TD-TR", "class='MID'");
		    addContenu($r, $acc, "Total",   "Ind", "class='RIG VR'");
		}
		else if (($encours==$pid||$encours=='') && $etat==0) // En cours
		{
		    addContenu($r, $acc, "Total prov.",   "Pl", "class='RIG B VR'");
                       addContenu($r,$acc,"Athlete","Flag","");
    addContenu($r,$acc,"Nom","NomPrenom","class='VR'");
    addContenu($r,$acc,"Club","Club","class='VR'");
		    addContenu($r, $acc, "Poule prov.", "Po", "class='MID'");
		    addContenu($r, $acc, "Poule prov.", "Pi", "class='MID '");
		    addContenu($r, $acc, "Poule prov.", "Da", "class='MID VR'");
		    addContenu($r, $acc, "Total prov.",   "V/M", "class='MID'");
		    addContenu($r, $acc, "Total prov.",   "&permil;", "class='RIG'");
		    addContenu($r, $acc, "Total prov.",   "TD-TR", "class='MID'");
		    addContenu($r, $acc, "Total prov.",   "Ind", "class='RIG VR'");
		}
		else if (($encours==$pid||$encours=='') && $etat==-1) //Pas commencé
		{
                       addContenu($r,$acc,"Athlete","Flag","");
    addContenu($r,$acc,"Nom","NomPrenom","class='VR'");
    addContenu($r,$acc,"Club","Club","class='VR'");
		    addContenu($r, $pid, "Poule", "RI", "class='RIG'");
		    addContenu($r, $pid, "Poule", "Po", "class='RIG'");
		    addContenu($r, $pid, "Poule", "Pi", "class='RIG B'");
		    addContenu($r, $pid, "Poule", "Da", "class='RIG B VR'");
		}
		else if ($encours==$pid-1) //Pas commencé, en cours de composition
		{
                       addContenu($r,$acc,"Athlete","Flag","");
    addContenu($r,$acc,"Nom","NomPrenom","class='VR'");
    addContenu($r,$acc,"Club","Club","class='VR'");
		    addContenu($r, $pid, "Poule",   "RI", "class='RIG'");
		    addContenu($r, $pid, "Poule", "Po", "class='RIG B VR'");
		    addContenu($r, $pid, "Poule", "Pi", "class='RIG B VR'");
		    addContenu($r, $pid, "Poule", "Da", "class='RIG B VR'");
		}
		else // Pas commencé, même pas en composition
		{
                       addContenu($r,$acc,"Athlete","Flag","");
    addContenu($r,$acc,"Nom","NomPrenom","class='VR'");
    addContenu($r,$acc,"Club","Club","class='VR'");
		    addContenu($r, $pid, "Poule",   "-", "class='VR'");
		}
	    }
	    
	    break;

	    case 'PointDeScission':
	    break;

	    case 'PhaseDeTableaux':
	    break;
	    
	    case 'ClassementGeneral':
	    break;
	}
    }
    if ($need_acc && !$need_tab)
    {
	addContenu($r, $acc, "Total",   "Pl", "class='RIG B VR'");
           addContenu($r,$acc,"Athlete","Flag","");
    addContenu($r,$acc,"Nom","NomPrenom","class='VR'");
    addContenu($r,$acc,"Club","Club","class='VR'");
	addContenu($r, $acc, "Total",   "V/M", "class='MID'");
	addContenu($r, $acc, "Total",   "&permil;", "class='RIG'");
	addContenu($r, $acc, "Total",   "TD-TR", "class='MID'");
	addContenu($r, $acc, "Total",   "Ind", "class='RIG'");
    }
    else if ($need_acc && $need_tab)
    {
	addContenu($r, $acc, "Tabl.",   "Pl", "class='RIG B VR'");
           addContenu($r,$acc,"Athlete","Flag","");
    addContenu($r,$acc,"Nom","NomPrenom","class='VR'");
    addContenu($r,$acc,"Club","Club","class='VR'");
	addContenu($r, $acc, "Total",   "V/M", "class='MID'");
	addContenu($r, $acc, "Total",   "&permil;", "class='RIG'");
	addContenu($r, $acc, "Total",   "TD-TR", "class='MID'");
	addContenu($r, $acc, "Total",   "Ind", "class='RIG VR'");
    }
    else if ($need_tab)
    {
	addContenu($r, $need_tab, "Tab.", "Pl", "class='RIG B VR'");
    }

    addContenu($r, $acc, "Statut",   "St", "");
    return $r;
}


function renderPair ( $col, $tag )
{
    $out = "";
    $out .= "<$tag";
    $out .= (isset($col['ATT']))? " ".$col['ATT'] : "";
    $out .= ">" . $col['TXT'] . "</$tag>";
    return $out;
}

function renderClassementCombineHead ($r,$multicol)
{
    $labels = array (
	'RI'      => 'Entrée',
	'Nation'  => 'Nation',
	'Ranking' => 'Rang',
	'NomPrenom'  => 'Nom',
	'Prenom'  => 'Prénom',
	'Nom'     => 'Nom',
	'Club'    => 'Club',
	'Flag'    => '',
	'PlaTab'  => 'Place',
	'Po'      => 'Poule',
	'Pi'      => 'Piste',
	'Da'      => 'Heure',
	'PiTa'    => 'Piste',
	'DaTa'    => 'Heure',
	'TR'      => 'TR',
	'TD'      => 'TD',
	'Vi'      => 'V',
	'Ma'      => 'M',
	'St'      => 'Status',
	'Pl'      => 'Place',
	'Ind'     => 'Ind.',
	'TD-TR'   => 'TD-TR'    );
    
    $out = "";

    if ($multicol)
        $out .= "<thead class='multicol'>\n";
    else
        $out .= "<thead class='monocol'>\n";
    
    $out .= "<tr>\n";
    for ($k=0; $k<count($r['ELEMENTS']); $k++)
    {	
        $out .= "<th ". $r['ATTRIBUTS'][$k] . ">";
        $out .= ($multicol)?"":"<div class='tblhead'>";
        
        $e = $r['ELEMENTS'][$k];
        
        if (isset($labels[$e]))
            $e = $labels[$e];
        
        $out .= $e;
        $out .= ($multicol)?"</th>":"</div></th>";
    }
    $out .= "</tr>\n</thead>\n";
    
    $out .= "<tbody id='tblbdy'>\n";
    
    if (!$multicol)
        for ($d = 0; $d<0*2; $d++) // Insert dummy rows hidden by fixed header
    {
        $out .= "<tr><td>X</td>";
	/*        for ($k=0; $k<count($r['ELEMENTS']); $k++)
           {	
           $out .= "<td ". $r['ATTRIBUTS'][$k] . ">";
           $e = $r['ELEMENTS'][$k];
           if (isset($labels[$e]))
           $e = $labels[$e];
           $out .= $e;
           $out .= "</td>";
           }
	 */        $out .= "</tr>\n";
    }
    return $out;
}



function computeVMfloat ($ph, $v, $m, $td, $tr )
{
    if ($m>0)
    {
	$vm  = $v  / $m;
	$ind = 500 + $td - $tr;
	$str = sprintf ("%06.4f%03d%03d",$ph+$vm,$ind,$td);
	//	echo "$str<br>";
    }
    else $str="-1.0000000000";
    return $str;
}


function recomputeClassement (&$a)
{
    $order = array();
    foreach ($a as $ref=>$val)
    $order[$ref] = isset($val['ACCU']['Fl'])?$val['ACCU']['Fl']:1;
    
    asort($order);
    $ligne = 1;
    $place = 1;
    $avant = 0;
    foreach ($order as $id => $val)
    {
	//	echo "LINE:$ligne VAL:$val <br>";
	if ($val != $avant)
	    $place = $ligne;
	$a[$id]['ACCU']['Pl2'] = ($val==INF)?INF:$place;
	$ligne++;
	$avant = $val;
    }
}
function mixteMaleFemale($xml)
{
    $a = suitTireursTableau($xml);  // $a  = suitTireurs($xml);
    
    $femmes      = 0;
    $hommes      = 0;
    foreach ($a as $key=>$val)
    {
        if ($val['ACCU']['St'] == 'F')
            $femmes++;
        if ($val['ACCU']['Sexe'] == 'M')
            $hommes++;
    }
    if ($hommes>0 && $femmes>0)
        return 'FM';
    if ($hommes>0 && $femmes==0)
        return 'M';
    if ($hommes==0 && $femmes>0)
        return 'F';
    return 'E';
}

function drapeauxPodium ($xml)
{
    $a = suitTireursTableau($xml);  // $a  = suitTireurs($xml);
    $order = array();
    foreach ($a as $ref=>$val)
    $order[$ref] = isset($val['ACCU']['PlaTabTri'])?1*$val['ACCU']['PlaTabTri']:"INF";

    asort($order);
    
    $r='';
    $cnt = 1;
    foreach ($order as $key=>$val)
    {
        $r .= flag_icon($a[$key]['ACCU']['Nation'],"huge podium$cnt");
        $cnt++;
        if ($cnt>4)
            break;
    }
    return $r;
}
function afficheClassementPoules ($xml, $ncol, $abc, $etape,$titre)
{
    $tri     = decideOrdre($xml);
    if ($abc)
    {
        $tri['TAG'] = 'ABC'; // Force alpabetic sort
    }
    
    if ($etape == -1)
    {
        $tri['TAG'] = 'Ta'; // Sort by tableau classement    
    }
    $nb_bidouilles = 1;
    if ($etape==0)
        $contenu = contenuListeTireurs();
    else if ($etape > 0)
        $contenu = decideContenu($xml);
    else
    {
        $contenu = contenuClassementFinal();
        $nb_bidouilles = 2;
    }
    $a = suitTireursTableau($xml);  // $a  = suitTireurs($xml);
    
    $cnt_present = 0;
    $cnt_total   = 0;
    $hommes      = 0;
    foreach ($a as $key=>$val)
    {
        $cnt_total++;
        if ($val['ACCU']['St'] != 'F')
            $cnt_present++;
	if ($val['ACCU']['Sexe'] == 'M')
            $hommes++;
    }
    if ($etape==0)
    {
        if ($hommes>0)
            $titre = "$cnt_present PRÉSENTS SUR $cnt_total INSCRITS";
        else
            $titre = "$cnt_present PRÉSENTES SUR $cnt_total INSCRITES";
    }
    
    $order = array();
    foreach ($a as $ref=>$val)
    {
        switch ($tri['TAG'])
        {
            case 'ABC':
            $order[$ref] = $val['ACCU']['Nom'] . " ". $val['ACCU']['Prenom'];
            break;

            case 'Po':
            $order[$ref] = isset($val[$tri['PHA']]['Po'])?$val[$tri['PHA']]['Po']:"INF";
            break;

            case 'Ta':
            $order[$ref] = isset($val['ACCU']['PlaTabTri'])?1*$val['ACCU']['PlaTabTri']:"INF";
            break;

            default:
            $order[$ref] = isset($val['ACCU']['Fl'])?$val['ACCU']['Fl']:1;
        }
    }

    $tireurs = getTireurList($xml);
    asort($order);

    /////////////////////////////
    // RECOMPUTE CLASSEMENT HERE
    ////////////////////////////
    recomputeClassement($a);

    $q = qualifiesPourTableau ($xml);
    
    $out = "";
    $ids = array_keys($order);
    
    $nb   = count($ids);
    $div = ($nb / $ncol);
    $cei = ceil($div);
    $npc  = intval($cei);
    //   echo "NB:$nb NCOL:$ncol DIV:$div CEI:$cei NPC:$npc<br>";
    $sta = 0;
    $sto = $npc; 
    $line=1;
    for ($col = 0; $col < $ncol; $col++)
    {
        $head = "";
        $head .= "<div class='tblhd_top' onclick='mickey()'><span class='tbl_banner'>&#9776; $titre</span><br>";
        $head .= "<div class='tblhd'><div></div>\n";
        $fixed_height = isset($_GET['scroll'])?'fh':'';

        $head .= "<table id='scrollme' class='listeTireur $fixed_height'>\n";
        $head .= renderClassementCombineHead($contenu, $ncol>1);
        $head .= "\n";
        $body = "";     
        $foot    = "</table></div></div>";

        $pair    = "impair";
        $opair   = "pair";
        $oqual   = "O";
        
        //    var_dump($tri);
        //    echo "SORT PHASE " . $toks['SORT_PHASE'] . " FIELD " . $toks['SORT_FIELD'] . "<br>";
        //    $order = $acc['RK']; // Start with latested estimated ranking
	for ($bidouille=0;$bidouille<$nb_bidouilles; $bidouille++) // affiche ceux qui sont encore en jeu, puis les éliminés
            for ($idx = $sta; $idx < $sto; $idx++)
        {
            $id = $ids[$idx];
            $elimine = 0;
            $tireur = $tireurs[$id];

            if ($etape!=0)
            {
                if ($a[$id]['ACCU']['Ex'])
                    continue; // Don't display exported 

                if ($a[$id]['ACCU']['St']=='F')
                    continue; // Don't  display failed to show up
            }            
            
            if ($etape == 0)
                $qual = ($a[$id]['ACCU']['St'] == 'F')?'O':'Q';
            
            if ($etape == 1)
            {
                if ($tireur->getAttribute('Statut') == 'E')
                    $qual = 'E';
                else
                    if ( $q[$id])
			$qual = 'Q';
                else 
                    $qual = 'O';
            }
            
            if ($etape == -1)
            {
                if ( $a[$id]['ACCU']['out'] || !$a[$id]['ACCU']['intab'])
                    $qual = 'O';
                else
                    $qual = 'Q';
            }

            if ($qual != $oqual)
                $pair = "impair";
            else
                $pair = $pair == "pair" ? "impair" : "pair";

            $oqual = $qual;
            
            if ($etape==0)
                $style = $pair . $qual.'L';
            
            if ($etape == 1)
                $style = $pair . $qual;

            if ($etape==-1)
                $style = $pair . $qual.'C';
            
            if ($nb_bidouilles>1)
            {
                if ($bidouille == 0)
                {
                    if ($a[$id]['ACCU']['out'])
                        continue;
                }
                else 
                {
                    if (!$a[$id]['ACCU']['out'])
                        continue;  
                }
            }
            $body .= "<tr class='$style'>";

            //	echo "Rank:" .$place. " REF:" .$id. "<br>";
            for ($k=0; $k<count($contenu['ELEMENTS']); $k++)
            {
                $dat = $contenu['ELEMENTS'][$k]; // Data to print
                $pha = $contenu['PHASES'][$k];   //  phase, if relevant
                $att = $contenu['ATTRIBUTS'][$k]; 

                $txt = "";
                switch ($dat)
                {
                    case 'Pl' :
                    $place = (isset($a[$id][$pha]) && isset($a[$id][$pha]['Pl2'])) ? $a[$id][$pha]['Pl2'] : (isset($a[$id]['ACCU']['Pl2']) ? $a[$id]['ACCU']['Pl2'] : INF);
                    if ($place == "PL2")
                        $place = isset($a[$id]['ACCU']['Pl2']) ? $a[$id]['ACCU']['Pl2'] : '-';
                    $txt = ($place<INF)?$place:"-";
                    if ($tireur->getAttribute('Statut') == POULE_STATUT_EXPULSION)
                        $txt = '-';
                    break;
                    case 'NomPrenom' :
                    $txt = (isset($a[$id][$pha]) && isset($a[$id][$pha]['Nom'])) ? $a[$id][$pha]['Nom'] : "-";
                    $txt .= ' ';
                    $txt .= (isset($a[$id][$pha]) && isset($a[$id][$pha]['Prenom'])) ? $a[$id][$pha]['Prenom'] : "-";
                    break;
                    case 'Present' :
                    $txt  = statut_present_absent ($a[$id]['ACCU']['St'],$a[$id]['ACCU']['Sexe']);
                    break;
                    case 'out' :
                    $txt = (isset($a[$id][$pha]) && isset($a[$id][$pha]['out'])) ? $a[$id][$pha]['out'] : '?';
                    break;
                    case 'Flag':
                    $nat = $a[$id]['ACCU']['Nation'];
                    $txt = !empty($nat) ? flag_icon($nat, 'flag_icon') : '';
                    break;
                    case 'PlaTab':
                    $platab    = (isset($a[$id][$pha]) && isset($a[$id][$pha][$dat])) ? $a[$id][$pha][$dat] : INF;
                    $platabtri = (isset($a[$id][$pha]) && isset($a[$id][$pha]['PlaTabTri'])) ? $a[$id][$pha]['PlaTabTri'] : INF;
                    $sorti     = (isset($a[$id][$pha]) && isset($a[$id][$pha]['out'])) ? !(!$a[$id][$pha]['out'] && $a[$id]['ACCU']['St'] == 'Q') : false;
                    $intab     = (isset($a[$id][$pha]) && isset($a[$id][$pha]['intab'])) ? $a[$id][$pha]['intab'] : 0;
                    $txt       = (isset($a[$id][$pha]) && isset($a[$id][$pha][$dat])) ? $a[$id][$pha][$dat] : '-';
                    $T = floor($platabtri/10000.0); // Tableau atteint
                    if ($sorti && $intab)
                    {
                        $rang_max = floor($T/2);      // Rang maximum
                        if ($platab < $rang_max)
                            $txt = "(".($rang_max+1)."&hellip;$T)";
                        else if ((isset($a[$id][$pha]) && isset($a[$id][$pha]['tourdone'])) && !$a[$id][$pha]['tourdone'])
                            $txt = "(".($rang_max+1)."&hellip;$T)"; 
                    }
                    else if (!$sorti && $intab)
                        $txt = "T".$T.""; //($platab) prov";
                    if ($elimine) $txt='-';
                    if ($txt==INF)
                        $txt = 'Expuls.'; 
                    break;
                    case 'line':
                    $txt = $line;
                    $line++;
                    break;
                    case 'PlaTabTri':
                    case 'intab':
                    case 'tourdone':
                    case 'Pl2' :
                    case 'RI' :
                    case 'Club' :
                    case 'Nation' :
                    case 'Ranking' :
                    case 'Prenom' :
                    case 'Nom' :
                    case 'Fl' :
                    case 'TR' :
                    case 'TD' :
                    case 'Vi' :
                    case 'Ma' :
                    case 'Ph' :
                    case 'Ind' :
                    case 'TD-TR' :
                    $txt = (isset($a[$id][$pha]) && isset($a[$id][$pha][$dat])) ? $a[$id][$pha][$dat] : "-";
                    if ($elimine) $txt='-';
                    break;
                    case 'Po' : 
                    case 'Pi' :
                    case 'PiTa' :
                    $txt = '&nbsp;&nbsp;&nbsp;&nbsp;';
                    if (isset($a[$id][$pha]) && isset($a[$id][$pha][$dat]))
                        $txt = $a[$id][$pha][$dat];
                    break;
                    case 'DaTa': // Date 
                    case 'Da': // Date 
                    $txt3 = (isset($a[$id][$pha]) && isset($a[$id][$pha][$dat])) ? $a[$id][$pha][$dat] : "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
                    $txt2 = explode(" ", $txt3);
                    $txt  = isset($txt2[1])?$txt2[1]:$txt3;
                    if (((!isset($a[$id][$pha]) || !isset($a[$id][$pha]['PiTa'])) || $a[$id][$pha]['PiTa']=='') && $txt!='' && (isset($a[$id][$pha]) && isset($a[$id][$pha][$dat])))                        
                        $txt = "dès $txt";
                    if ($elimine) $txt='-';
                    break;
                    case '&permil;' :
                    if (isset($a[$id][$pha]) && isset($a[$id][$pha]['Ma']) && isset($a[$id][$pha]['Vi']) && !$elimine)
                    {
                        $ma = $a[$id][$pha]['Ma'];
                        $vi = $a[$id][$pha]['Vi'];
                        if ($ma>0)
                            $txt .=  sprintf("%3.0f",1000*$vi / $ma);
                        else
                        {
                            $txt .= "-";
                            $elimine = 1;
                        }
                    }
                    else
                    {
                        $txt = "-";
                        $elimine = 1;
                    }
                    break;
                    case 'V/M' :
                    if (isset($a[$id][$pha]) && isset($a[$id][$pha]['Ma']) && isset($a[$id][$pha]['Vi']) && !$elimine)
                    {
                        $ma = $a[$id][$pha]['Ma'];
                        $vi = $a[$id][$pha]['Vi'];
                        if ($ma>0)
                            $txt .=  $vi . "/".$ma;
                        else
                        {
                            $txt .= "-";
                            $elimine = 1;
                        }
                    }
                    else
                    {
                        $txt = "-";
                        $elimine = 1;
                    }
                    break;
                    case '-' :
                    $txt = '-';
                    break;
                    case 'St':
                    if (isset($a[$id][$pha]) && isset($a[$id][$pha]['Pl']) && $a[$id][$pha]['Pl'] == "PL2")
                        $sta = 'V';
                    else
                        $sta = (isset($a[$id][$pha]) && isset($a[$id][$pha]['St'])) ? $a[$id][$pha]['St'] : '';
                    
                    // Determine gender for proper French agreement
                    $sexe = isset($a[$id]['ACCU']['Sexe']) ? $a[$id]['ACCU']['Sexe'] : 'M';
                    $feminin = ($sexe == 'F');
                    
                    switch( $sta )
                    {
                        case 'V':
                        $txt = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'; // Verification
                        break;
                        case STATUT_PRESENT :
                        if ($q[$id]) {
                            $txt = $feminin ? "<span class='status-qualifie'>Qualifiée</span>" : "<span class='status-qualifie'>Qualifié</span>";
                        } else {
                            $txt = $feminin ? "<span class='status-elimine'>Éliminée</span>" : "<span class='status-elimine'>Éliminé</span>";
                        }
                        break;
                        case STATUT_ELIMINE: 
                        $txt = $feminin ? "<span class='status-elimine'>Éliminée</span>" : "<span class='status-elimine'>Éliminé</span>";
                        break;
                        case POULE_STATUT_ABANDON: 
                        if ($q[$id]) {
                            $txt = $feminin ? "<span class='status-qualifie'>Qualifiée</span>" : "<span class='status-qualifie'>Qualifié</span>";
                        } else {
                            $txt = "<span class='status-abandon'>Abandon</span>";
                        }
                        break;
                        case POULE_STATUT_EXPULSION: 
                        $txt = $feminin ? "<span class='status-expulsion'>Exclue</span>" : "<span class='status-expulsion'>Exclu</span>";
                        break;
                        default:
                        // For unknown status, check if qualified
                        if ($q[$id]) {
                            $txt = $feminin ? "<span class='status-qualifie'>Qualifiée</span>" : "<span class='status-qualifie'>Qualifié</span>";
                        } else {
                            $txt = $feminin ? "<span class='status-elimine'>Éliminée</span>" : "<span class='status-elimine'>Éliminé</span>";
                        }
                        break;
                    }
                    if (isset($a[$id][$pha]) && isset($a[$id][$pha]['Ex']) && $a[$id][$pha]['Ex'])
                        $txt .= " (*)";
                    break;
                    default:
                    $txt = $dat ."?";
                } // switch($dat)
                $body .= "<td $att>$txt</td>";    
            }
            $body .= "</tr>\n";
        } // for sta sto
        $sta = $sto;
        $sto = $sto + $npc;
        if ($sto>$nb)
            $sto = $nb;
        
        $body .= "</tbody>";
        
        $out .= $head . $body . $foot;
    } // for cols
    return $out;
}

function qualifiesPourTableau ($xml)
{
    $r = array();
    $inscrits	= $xml->getElementsByTagName( 'Tireurs' );
    foreach ($inscrits as $list) 
    {
        $tireur = $list->getElementsByTagName( 'Tireur' );
        foreach ($tireur as $t) 
        {
            $ref = $t->getAttribute('ID');
            $r[$ref] = 0; // initial
        }
    }
    
    $tableau= $xml->getElementsByTagName( 'PhaseDeTableaux' );
    foreach ($tableau as $list) 
    {
        $tireur = $list->getElementsByTagName( 'Tireur' );
        foreach ($tireur as $t) 
        {
            $ref = $t->getAttribute('REF');
            $r[$ref] = 1; // dans le tableau
        }
    }
    return $r;
}

function getResetPhases($topXml)
{
    $pha = array();
    foreach ($topXml->getElementsByTagName('TourDePoules') as $e)
    $pha[$e->getAttribute('PhaseID')] = 'TourDePoules';

    foreach ($topXml->getElementsByTagName('PointDeScission') as $e)
    $pha[$e->getAttribute('PhaseID')] = 'PointDeScission';

    foreach ($topXml->getElementsByTagName('PhaseDeTableaux') as $e)
    $pha[$e->getAttribute('PhaseID')] = 'PhaseDeTableaux';

    ksort($pha);

    $rst= 1; // Start by clearing accumulated score
    $r  = array();
    foreach ($pha as $pid => $typ)
    {
	switch($typ)
	{
	    case 'TourDePoules':
	    $r[$pid] = $rst;
	    $rst = 0;
	    break;
	    
	    case 'PointDeScission':
	    $rst = 1; // Next TourDePoules starts with a zero accumulated score
	    break;
	}
    }
    return $r;
}

function suitTireurs ($topXml)
{
    $a = array();
    $verbose = 0*1;
    $encours = getPhaseEnCoursID ($topXml);
    $rst = getResetPhases($topXml);
    $isEquipe = ($topXml->documentElement->localName === 'CompetitionParEquipes');
    foreach ($topXml->getElementsByTagName('Tireur') as $t)
    {
        $p   = $t->parentNode;
        $ref = $t->getAttribute('REF');
        if (!is_numeric($ref))
            $ref= $t->getAttribute('ID');
        switch ($p->localName)
        {
            case 'Tireurs':
                $a[$ref] = array();
                $a[$ref]['ACCU']=array();
                $st     = $t->getAttribute('Statut');
                $rk     = $t->getAttribute('Ranking');
                $cl     = $t->getAttribute('Classement');
                $a[$ref]['ACCU']['Vi'] = 0;  // Victoires accumulees
                $a[$ref]['ACCU']['Ma'] = 0;  // Matches accumules
                $a[$ref]['ACCU']['TD'] = 0;  // TD accumules
                $a[$ref]['ACCU']['TR'] = 0;  // TR accumules
                $a[$ref]['ACCU']['Ph'] = 0;  // Phase atteinte
                $a[$ref]['ACCU']['St2']= 0;  // Status recalcule
                $a[$ref]['ACCU']['Ta'] = 0;  // Made it in the table

                $a[$ref]['ACCU']['Sexe']    = $t->getAttribute('Sexe');
                $a[$ref]['ACCU']['Nom']     = $t->getAttribute('Nom');
                $a[$ref]['ACCU']['Prenom']  = $t->getAttribute('Prenom');
                $a[$ref]['ACCU']['Nation']  = $t->getAttribute('Nation');
                $a[$ref]['ACCU']['Club']    = $t->getAttribute('Club');
                $a[$ref]['ACCU']['Ranking'] = ($rk==0)?'-':$rk;

                if (is_numeric($cl))
                {
                    $a[$ref]['ACCU']['Cl'] = $cl;  // Le tireur est définitivement classé
                    $a[$ref]['ACCU']['Pl'] = $cl;  // Le tireur est définitivement classé
                }
                else
                    $a[$ref]['ACCU']['Pl'] = $t->getAttribute('Nom') . " " .$t->getAttribute('PreNom'); // Pas encore classé, on commence en alphabet

                $a[$ref]['ACCU']['Ex'] = $t->getAttribute('Exporte');
                $a[$ref]['ACCU']['St'] = $st;
                if ($st=='E')
                {
                    $a[$ref]['ACCU']['Pl']=INF;
                    $a[$ref]['ACCU']['Cl']=INF;
                }

                if($verbose)
                    echo "Entree  ID:$ref at " . $a[$ref]['ACCU']['Pl']. "<br>"  ;
                break;
            case 'Equipe':
                if ($isEquipe) {
                    $equipeRef = $p->getAttribute('ID');
                    // Initialiser ACCU pour l'équipe si non déjà fait
                    if (!isset($a[$equipeRef]['ACCU'])) {
                        $a[$equipeRef]['ACCU'] = array(
                            'Nom'     => $p->getAttribute('Nom'),
                            'Sexe'    => $p->getAttribute('Sexe'),
                            'Nation'  => $p->getAttribute('Nation'),
                            'Club'    => $p->getAttribute('Club'),
                            'St'      => $p->getAttribute('Statut'),
                            'Prenom'  => '',
                            'Ranking' => $p->getAttribute('Ranking'),
                            'Ex'      => $p->getAttribute('Exporte'),
                            'Cl'      => $p->getAttribute('Classement'),
                            'Pl'      => $p->getAttribute('Classement'),
                        );
                    }
                    if (!isset($a[$equipeRef]['MEMBRES'])) $a[$equipeRef]['MEMBRES'] = [];
                    $a[$equipeRef]['MEMBRES'][] = [
                        'Nom' => $t->getAttribute('Nom'),
                        'Prenom' => $t->getAttribute('Prenom'),
                        'Club' => $t->getAttribute('Club'),
                        'ID' => $t->getAttribute('ID'),
                    ];
                }
                break;
            case 'PhaseDeTableaux':
                $phaid  = $p->getAttribute('PhaseID');
                if ($encours>$phaid)
                {
                    $a[$ref][$phaid]['Pl'] = $t->getAttribute('RangFinal');
                    $a[$ref]['TAB']['Pl']  = $t->getAttribute('RangFinal');
                }
                if($verbose)
                    echo "TAB phase:$phaid ref:$ref<br>";
                break;
            
            case 'TourDePoules':
                $phaid  = $p->getAttribute('PhaseID');
                
                if ($phaid>$a[$ref]['ACCU']['Ph'])
                    $a[$ref]['ACCU']['Ph'] = $phaid;

                if ($rst[$phaid] && $encours >= $phaid)
                {
                    $a[$ref]['ACCU']['Vi'] = 0;  // Victoires accumulees
                    $a[$ref]['ACCU']['Ma'] = 0;  // Matches accumules
                    $a[$ref]['ACCU']['TD'] = 0;  // TD accumules
                    $a[$ref]['ACCU']['TR'] = 0;  // TR accumules
                }
                
                $st = $t->getAttribute('Statut');
                $a[$ref][$phaid]=array();
                $a[$ref][$phaid]['RI'] = $t->getAttribute('RangInitial');
                $a[$ref][$phaid]['RF'] = $t->getAttribute('RangFinal');
                
                if ($encours>$phaid)
                {
                    $a[$ref][$phaid]['St'] = $st;
                    $a[$ref][$phaid]['Pl'] = $t->getAttribute('RangFinal');
                    $a[$ref]['ACCU']['Pl'] = $t->getAttribute('RangFinal');
                }
                else if ($encours==$phaid)
                {
                    $a[$ref]['ACCU']['Pl'] = "PL2"; //$t->getAttribute('RangInitial');
                }

                if($verbose)
                    echo "TDP phase:$phaid ref:$ref<br>";
                break;

                case 'Poule':
                $pouid = $p->getAttribute('ID');
                $poupi = $p->getAttribute('Piste');
                $pouda = $p->getAttribute('Date').' '.$p->getAttribute('Heure');
                $tdp   = $p->parentNode;
                $phaid = $tdp->getAttribute('PhaseID');
                $a[$ref]['ACCU']['Po'] = $pouid;  // Dans quelle poule est ce tireur
                $a[$ref][$phaid]['Po'] = $pouid;  // Dans quelle poule est ce tireur
                
                $a[$ref]['ACCU']['Pi'] = $poupi;  // Sur quelle piste est ce tireur
                $a[$ref][$phaid]['Pi'] = $poupi;  // Sur quelle piste est ce tireur
                
                $a[$ref]['ACCU']['Da'] = $pouda;  // A quelle heure est ce tireur
                $a[$ref][$phaid]['Da'] = $pouda;  // A quelle heure est ce tireur
                
                $vi = $t->getAttribute('NbVictoires');
                $td = $t->getAttribute('TD');
                $tr = $t->getAttribute('TR');
                $a[$ref][$phaid]['Vi'] = $vi;
                $a[$ref][$phaid]['Ma'] = 0;  // On doit compter les matches, car NbMatches s'embrouille avec les abandons et exclusions
                $a[$ref][$phaid]['TD'] = $td;
                $a[$ref][$phaid]['TR'] = $tr;
                $a[$ref][$phaid]['TD-TR'] = $td ."&minus;".$tr;

                if (is_numeric($vi))
                    $a[$ref]['ACCU']['Vi'] += $t->getAttribute('NbVictoires');
                
                if (is_numeric($td) && is_numeric($tr))
                {
                    $a[$ref][$phaid]['Ind']   = $td - $tr;
                    $a[$ref]['ACCU']['TD']   += $t->getAttribute('TD');
                    $a[$ref]['ACCU']['TR']   += $t->getAttribute('TR');
                    $a[$ref]['ACCU']['Ind']   = $a[$ref]['ACCU']['TD'] - $a[$ref]['ACCU']['TR'];
                    $a[$ref]['ACCU']['TD-TR'] = $a[$ref]['ACCU']['TD'] ."&minus;". $a[$ref]['ACCU']['TR'];
                }
                
                if($verbose)
                    echo "TDP Phase:$phaid Poule no:$pouid ref:$ref <br>"; 
                break;

                case 'Match':
                $matid = $p->getAttribute('ID');
                $pp    = $p->parentNode;
                $st    = $t->getAttribute('Statut');
                if ($st=='E')
                {
                    $a[$ref]['ACCU']['Pl'] = INF;
                    $a[$ref]['ACCU']['Cl'] = INF;
                    $a[$ref]['ACCU']['St'] = 'E';
                    $a[$ref][$phaid]['Pl'] = INF;
                }

                switch ($pp->localName)
                {
                    case 'Poule':
                    $pou   = $pp;
                    $pouid = $pou->getAttribute('ID');
                    $tdp   = $pou->parentNode;
                    $phaid = $tdp->getAttribute('PhaseID');
                    $s     = $t->getAttribute('Statut');
                    if ($s=='V' || $s=='D')
                    {
                        $a[$ref][$phaid]['Ma']++;
                        $a[$ref]['ACCU']['Ma']++;
                        $a[$ref]['ACCU']['Fl'] = -computeVMfloat ($a[$ref]['ACCU']['Ph'],
                                                                      $a[$ref]['ACCU']['Vi'],
                                                                      $a[$ref]['ACCU']['Ma'],
                                                                      $a[$ref]['ACCU']['TD'],
                                                                      $a[$ref]['ACCU']['TR']);
                        $a[$ref]['ACCU']['St2']='Q';
                    }
                    if ($s=='A')
                    {
                        //                    echo "REF:$ref POUID:$pouid  PHA:$phaid STATUT:$s <br>";
                        $a[$ref]['ACCU']['St2']='A';
                    }
                    if ($s=='E')
                    {
                        $a[$ref]['ACCU']['Fl'] = INF;
                        $a[$ref]['ACCU']['St2']='E';
                    }
                    if($verbose)
                        echo "TDP Phase:$phaid Poule no:$pouid MATCH $matid ref:$ref <br>"; 
                    break;

                    case 'Tableau':
                    $tab   = $pp;
                    //		$tabid = $tab->getAttribute('ID');
                    $sdt   = $tab->parentNode;
                    $pdt   = $sdt->parentNode;
                    $phaid = $pdt->getAttribute('PhaseID');
                    if($verbose)
                        echo "Tableau Phase:$phaid Poule no:$pouid MATCH $matid ref:$ref <br>"; 
                    break; 
                }
                break; // case 'Match':
            }
        }
		return $a;
    }
    




/**
 * Validate filename to prevent directory traversal.
 * 
 * @param string $filename The filename to check.
 * @return bool True if the filename is valid, false otherwise.
 */
function check_filename($filename) {
    // Sanitize the path
    $filename = sanitizeFilePath($filename);
    
    // Prevent directory traversal
    if (strpos($filename, '..') !== false) {
        debugLog("Directory traversal attempt detected in: $filename");
        return false;
    }
    
    // Only allow .cotcot files
    if (!endsWith($filename, '.cotcot')) {
        debugLog("Invalid file extension in: $filename");
        return false;
    }
    
    // Check if file exists
    if (!file_exists($filename)) {
        debugLog("File not found: $filename");
        return false;
    }
    
    return true;
}


function dump_ranking($ranking)
{
    foreach ($ranking as $ref => $tireur)
    {
	if (isset ($tireur[ RANK_FIN ]))
	{
	    echo "REF = " . $ref . " rang " . $tireur[ RANK_FIN ] ."<br>";
	}
    }
}




function repairTableau($xml )
{
    // Add missing victory to the remaining fencer if his opponent is excluded or gives up
    $phases = getAllPhases($xml);
    foreach ($phases as $phase)
    if ($phase->localName =='PhaseDeTableaux')
    {
	foreach ($phase->getElementsByTagName('Tableau') as $t)
	{
	    foreach ($t->getElementsByTagName('Match') as $m)
	    {
		$tireurs = $m->getElementsByTagName('Tireur');
		if ($tireurs->length == 2)
		{
		    $s0 = $tireurs[0]->getAttribute('Statut');
		    $s1 = $tireurs[1]->getAttribute('Statut');
		    
		    if (($s0=="A" || $s0=="E") && $s1!="A" && $s1!="E")
			$tireurs[1]->setAttribute('Statut','V');
		    if (($s1=="A" || $s1=="E") && $s0!="A" && $s0!="E")
			$tireurs[0]->setAttribute('Statut','V');
		}
		
	    }
	    
	}
    }
}

function autoscaleTableau($xml) {
    $r = array();
    $taille = array();
    $connus = array();
    $finis = array();
    $maxtaille = 0;

    $phases = getAllPhases($xml);
    foreach ($phases as $phase) {
        if ($phase->localName == 'PhaseDeTableaux') {
            foreach ($phase->getElementsByTagName('Tableau') as $t) {
                $tabid = $t->getAttribute('ID');
                $taille[$tabid] = $t->getAttribute('Taille');
                if ($taille[$tabid] > $maxtaille) {
                    $maxtaille = $taille[$tabid];
                }
                $connus[$tabid] = 0;
                $finis[$tabid] = 0;

                foreach ($t->getElementsByTagName('Match') as $m) {
                    $tireurs = $m->getElementsByTagName('Tireur');
                    foreach ($tireurs as $tt) {
                        $connus[$tabid]++;
                    }
                    if ($tireurs->length == 2) {
                        $finis[$tabid] += 2;
                    }
                }
            }

            $tabStart = $maxtaille;
            $tabEnd = 1;

            foreach ($taille as $id => $val) {
                if ($finis[$id] == $val) {
                    $tabStart = min($tabStart, $val);
                } else if ($connus[$id] > 0) {
                    $tabEnd = max($tabEnd, $val);
                }
            }

            $r['tabStart'] = $tabStart;
            $r['tabEnd'] = $tabEnd;
        }
    }
    return $r;
}

function fractureNom($str)
{
    $str = str_replace(' ',' ',$str);
    $str = str_replace('_',' ',$str); 
    $tok = preg_split('/\s/', $str);
    $n   = sizeof($tok);
    $r   = $tok[0];
    $f   = 0;

    for ($k=1;$k<$n;$k++)
    {
	if (($k>=(($n-1)/2))&&($f==0))
	{
	    $r .= '<br>';
	    $f = 1;
	}
	else
	    $r .= ' ';
	
	$r .= $tok[$k];
    }

    return $r;
}

function getTableauPattern($col)
{
    $f = 2*(pow(2,$col+1)-3);
    return array(
	'net' => ($col>1)?pow(2,$col):0,    // Narrow part, empty top
	'nrt' => ($col>1)?1:0,  // Narrow part  row   top
	'nvt' => ($col>1)?pow(2,$col)-4:0,  // Narrow part  vertical top
	'nem' => ($col>1)?6:8,              // Narrow part  empty middle
	'nrb' => ($col>1)?1:0,  // Narrow part  row   bottom
	'nvb' => ($col>1)?pow(2,$col)-4:0,  // Narrow part  row   bottom
	'neb' => ($col>1)?pow(2,$col):0,    // Narrow part  empty bottom
	'wet' => pow(2,$col+1)-3,           // Wide part
	'web' => pow(2,$col+1)-3,           // Wide part
    );
}


function getMatchGroupClass($row, $col, $class) {
    // Apply match colors to both fencer name cells and score cells
    if (
        strpos($class, 'Tableau_wto_lef') !== false ||
        strpos($class, 'Tableau_wbo_lef') !== false ||
        strpos($class, 'Tableau_wto_lef_final') !== false ||
        strpos($class, 'Tableau_wbo_lef_final') !== false ||
        strpos($class, 'Tableau_wto_lef_flip') !== false ||
        strpos($class, 'Tableau_wbo_lef_flip') !== false ||
        strpos($class, 'Tableau_wto_lef_flip_final') !== false ||
        strpos($class, 'Tableau_wbo_lef_flip_final') !== false ||
        strpos($class, 'Tableau_wto') !== false ||
        strpos($class, 'Tableau_wbo') !== false ||
        strpos($class, 'Tableau_wto_final') !== false ||
        strpos($class, 'Tableau_wbo_final') !== false ||
        strpos($class, 'Tableau_wto_flip') !== false ||
        strpos($class, 'Tableau_wbo_flip') !== false ||
        strpos($class, 'Tableau_wto_flip_final') !== false ||
        strpos($class, 'Tableau_wbo_flip_final') !== false
    ) {
        return getUniqueMatchColor($row, $col);
    }
    return '';
}

/**
 * Generate escrime-info style match colors with better segmentation
 * Each match gets a distinct color for clear visual separation
 */
function getUniqueMatchColor($row, $col) {
    // Calculate match index based on row (every 2 rows = 1 match)
    $matchIndex = floor($row / 2);
    
    // Add column offset to ensure different colors across phases
    $phaseOffset = $col * 3;
    $uniqueMatchId = $matchIndex + $phaseOffset;
    
    // Use 6 distinct colors for better variety
    $colorNames = ['tYellow', 'tGreen', 'tBlue', 'tRed', 'tPurple', 'tOrange'];
    $colorIndex = $uniqueMatchId % 6;
    
    return $colorNames[$colorIndex];
}

/**
 * Add colored match boxes with connecting lines
 */
function addBracketLineClasses($class, $row, $col, $totalRows, $isTopFencer = false, $isBottomFencer = false, $hasNextRound = false) {
    $lineClasses = '';
    
    // Apply colored match boxes to fencer names and scores
    if (strpos($class, 'Tableau_wto_lef') !== false || 
        strpos($class, 'Tableau_wbo_lef') !== false ||
        strpos($class, 'Tableau_wto') !== false || 
        strpos($class, 'Tableau_wbo') !== false) {
        
        $matchColorClass = ' ' . getUniqueMatchColor($row, $col);
        $lineClasses .= $matchColorClass;
        
        // Add connection indicators for next round
        if ($hasNextRound && $isBottomFencer) {
            $lineClasses .= ' match-connector';
        }
    }
    
    // Add connecting line classes for bracket flow
    if (strpos($class, 'Tableau_n') !== false) {
        if (strpos($class, 'nrt') !== false || strpos($class, 'nrb') !== false) {
            $lineClasses .= ' horizontal-connector';
        } else if (strpos($class, 'nvt') !== false || strpos($class, 'nvb') !== false) {
            $lineClasses .= ' vertical-connector';
        }
    }
    
    return $class . $lineClasses;
}

function renderMyTableau( $xml , $detail, $fold, $titre, $selectedSuiteId = null)
{
    $isEquipe = ($xml->documentElement->localName === 'CompetitionParEquipes');
    $tireurs = array();
    if ($isEquipe) {
        foreach ($xml->getElementsByTagName('Equipes') as $s) {
            foreach ($s->getElementsByTagName('Equipe') as $equipe) {
                $id = $equipe->getAttribute('ID');
                $tireurs[$id] = array('DispNom' => $equipe->getAttribute('Nom'),
                                      'Nation'  => $equipe->getAttribute('Nation'),
                                      'Club'    => $equipe->getAttribute('Club'),
                                      'Membres' => []);
                foreach ($equipe->getElementsByTagName('Tireur') as $membre) {
                    $tireurs[$id]['Membres'][] = $membre->getAttribute('Nom') . ' ' . $membre->getAttribute('Prenom');
                }
            }
        }
    } else {
        foreach ($xml->getElementsByTagName('Tireurs') as $s)
        {
            foreach ($s->getElementsByTagName('Tireur') as $t)
            {
                $id = $t->getAttribute('ID');
                $tireurs[$id] = array('DispNom' => $t->getAttribute('Nom') .' '. $t->getAttribute('Prenom'),
                                      'Nation'  => $t->getAttribute('Nation'),
                                      'Club'    => $t->getAttribute('Club'),
                );
            }
        }
    }

    $arbitres = array();
    foreach ($xml->getElementsByTagName( 'Arbitres' ) as $s)
    {
	foreach ($s->getElementsByTagName('Arbitre') as $t)
	{
	    $id = $t->getAttribute('ID');
	    $arbitres[$id] = array('DispNom' => $t->getAttribute('Nom') .' '. $t->getAttribute('Prenom'),
				   'Nation'  => $t->getAttribute('Nation'),
				   'Club'    => $t->getAttribute('Club'),
	    );
	}
    }
      $full = $fold>0;
    $bb = prepairMyTableau($xml,$full,$selectedSuiteId);
    $b=$bb['b'];
    $a=$bb['a'];
    
    if ($fold > 0)
    {        
        $bb = origami($a,$b,$fold==1);
        $b=$bb['b'];
        $a=$bb['a'];
    }
    if ($fold > 1)
    {    
        $bb = origami2($a,$b);
        $b=$bb['b'];
        $a=$bb['a'];
    } 
    

      $tab  = "<div class='tblhd_top' onclick='mickey()'>";
      $tab .= "<span class='tbl_banner'>&#9776; $titre</span>";
      
      // Add dropdown for suite selection at same level as banner
    $suiteOptions = getSuiteDeTableauxOptions($xml);
    if (count($suiteOptions) > 1) {
        $currentSuiteId = $selectedSuiteId !== null ? $selectedSuiteId : '0'; // Default to main bracket
        
        $tab .= "<div class='suite-selector-container' onclick='event.stopPropagation();'>";
        $tab .= "<select id='suiteSelector' class='suite-selector' onchange='changeSuite(this.value)' onclick='event.stopPropagation();'>";
        
        foreach ($suiteOptions as $option) {
            $selected = ($option['id'] == $currentSuiteId) ? 'selected' : '';
            $tab .= "<option value='{$option['id']}' $selected>{$option['titre']}</option>";
        }
        
        $tab .= "</select>";
        $tab .= "</div>";
    }
    
    $tab .= "</div>"; // Close tblhd_top
    $tab .= "<div class='tblhd_tab'><div></div>\n"; // This div seems to be an empty placeholder or for styling
    $fixed_height = isset($_GET['scroll']) && $_GET['scroll'] == '1' ? 'fhtab' : '';

    // New wrapper div for scrolling, applying id and $fixed_height class here
    $tab .= "<div id='scrollme' class='$fixed_height'>"; 
    
    // Table no longer has id or $fixed_height class, ensure class attribute is properly quoted
    $tab .= "<table class='myTableau'><thead>"; 
    $tab .= "<tr>";
    $keep = 2;
    for ($col=0;$col<count($b);$col++)
    {
	$tab .= "<th class='Tableau_TXX'><div class='tblhead_tab'>".$a[$col]."</div></th>";
    }
    $tab .= "</tr></thead><tbody>\n";
    
    if (count($b)>0)
	for ($row=0;$row<count($b[0]);$row++)
    {
	$tab .= "<tr>";
        $nbc = count($b);
	for ($col=0;$col<count($b);$col++)
	{

	    $cla = $b[$col][$row]['class'];
	    //	    $inl = $b[$col][$row]['inl'];
	    //	    $con = $b[$col][$row]['con'];
	    $rowspan = isset($b[$col][$row]['row'])?$b[$col][$row]['row']:1;
	    $inl = '';
	    $con = '';
	    $class = $cla;
	    if ($cla == 'Tableau_nul' || $cla == 'Tableau_nul_flip')
	    {
	    }
	    else
	    {
		if ($cla == 'Tableau_wbo_lef' || 
                    $cla == 'Tableau_wto_lef' ||
                    $cla == 'Tableau_wbo_lef_flip' || 
                    $cla == 'Tableau_wto_lef_flip' ||
                    $cla == 'Tableau_wbo_lef_final' || 
                    $cla == 'Tableau_wto_lef_final' ||
                    $cla == 'Tableau_wbo_lef_flip_final' || 
                    $cla == 'Tableau_wto_lef_flip_final' )
		{
		    $l = 0;
		    $t = "";
		    if (isset($b[$col][$row]['REF']))
		    {
			$ref = $b[$col][$row]['REF'];
			$t = $tireurs[$ref]['DispNom'];
			$l = strlen($t);
		    }

		    
		    if ($l >40)
		    {
			$class .= ' gros40';
			//			$t = fractureNom($t);
		    }
		    else if ($l>30)
		    {
			$class .= ' gros30';
			//			$t = fractureNom($t);
		    }
		    else if ($l>20)
		    {
			$class .= ' gros20';
			//			$t = fractureNom($t);
		    }
		    else
			$class .= ' gros';

		    if (isset($b[$col][$row]['Fla']))
			$class .= ' avec_rang';
		    else
			$class .= ' sans_rang';
		    
		    $matchGroupClass = getMatchGroupClass($row, $col, $class);
		    
		    // Add bracket interconnecting lines
		    $isTopFencer = (strpos($cla, 'wto_lef') !== false);
		    $isBottomFencer = (strpos($cla, 'wbo_lef') !== false);
		    $hasNextRound = ($col < count($b) - 3); // Check if there's a next round
		    $totalRows = count($b[0]);
		    
		    $classWithLines = addBracketLineClasses($class, $row, $col, $totalRows, $isTopFencer, $isBottomFencer, $hasNextRound);
		    
		    $tab .= "<td $inl class='$classWithLines $matchGroupClass' $rowspan >";
		    
		    $flag="";
		    if ( isset($b[$col][$row]['REF']))
		    {
			$nat = $tireurs[$b[$col][$row]['REF']]['Nation'];
			$flag = ' '.flag_icon($nat,'').' ';
		    }
		    $nom="";
		    if (isset($b[$col][$row]['REF']))
			$nom = $t; //ireurs[$b[$col][$row]['REF']]['DispNom'];
                    
		    if ((isset($b[$col][$row]['Fla']) && $b[$col][$row]['Fla']) && (isset($b[$col][$row]['REF'])))
                    {
			if ($cla == 'Tableau_wbo_lef' || $cla == 'Tableau_wto_lef' || $cla == 'Tableau_wto_lef_final')
			    $nom = "<span class='gros_nobold'>(".$b[$col][$row]['Ran'].") </span>$nom";
                        else
			    $nom = "$nom <span class='gros_nobold'> (".$b[$col][$row]['Ran'].")</span>";
                    }   
                    
                    // Add club name with smaller font and limited to 10 characters
                    if (isset($b[$col][$row]['REF'])) {
                        $club = $tireurs[$b[$col][$row]['REF']]['Club'];
                        if (!empty($club)) {
                            // Limit club name to 10 characters
                            $clubShort = (strlen($club) > 10) ? substr($club, 0, 10) : $club;
                            $nom .= " <span class='club-name'>(" . htmlspecialchars($clubShort) . ")</span>";
                        }
                    }
                    
                    if (!isset($b[$col][$row]['Fla']) || !$b[$col][$row]['Fla'])
                        $flag = '';

                    if ($cla == 'Tableau_wbo_lef' || $cla == 'Tableau_wto_lef' || $cla == 'Tableau_wto_lef_final')
                        $tab .= $flag . $nom;
                    else 
                        $tab .= $nom . $flag;
                    
                    
		    if (isset($b[$col][$row]['Statut']))
			$tab .= " ".$b[$col][$row]['Statut'];
		    if (isset($b[$col][$row]['Score']))
			$tab .= " ".$b[$col][$row]['Score'];

		    // Affichage des membres si équipe
		    if ($isEquipe && isset($b[$col][$row]['REF']) && isset($tireurs[$b[$col][$row]['REF']]['Membres'])) {
		        if (isset($_GET['lst']) || isset($_GET['tab'])) {
		            $tab .= "<ul style='margin:0;padding-left:18px;font-size:0.9em;'>";
		            foreach ($tireurs[$b[$col][$row]['REF']]['Membres'] as $membre) {
		                $tab .= "<li>".htmlspecialchars($membre)."</li>";
		            }
		            $tab .= "</ul>";
		        }
		    }
		}
		else if ( $cla == 'Tableau_wto' || 
                          $cla == 'Tableau_wbo' ||
                          $cla == 'Tableau_wto_flip' || 
                          $cla == 'Tableau_wbo_flip' ||
                          $cla == 'Tableau_wto_final' || 
                          $cla == 'Tableau_wbo_final' ||
                          $cla == 'Tableau_wto_flip_final' || 
                          $cla == 'Tableau_wbo_flip_final' 
                )
		{
		    $tmp='';
		    $sta=0;
		    $dat=0;
		    $pis=0;
		    if (isset($b[$col][$row]['Statut']))
		    {
			$tmp .= $b[$col][$row]['Statut'];
			
			if ($b[$col][$row]['Statut'] != '')
			    $sta=1;
		    }
		    if (isset($b[$col][$row]['Score']))
		    {
			$tmp .= $b[$col][$row]['Score'];
		    }
		    
		    if (isset($b[$col][$row]['Pi']))
			if ($b[$col][$row]['Pi']!='')
			    $pis=1;
		    if (isset($b[$col][$row]['Da']))
			if ($b[$col][$row]['Da']!='')
			    $dat=1;
		    
		    if ($sta)
			$class .= ' score ';
		    else if ($pis)
			$class .= ' piste ';
		    else if ($dat)
			$class .= ' date ';
		    
		    $matchGroupClass = getMatchGroupClass($row, $col, $class);
		    
		    // Add bracket interconnecting lines for score cells
		    $hasNextRound = ($col < count($b) - 3);
		    $totalRows = count($b[0]);
		    $classWithLines = addBracketLineClasses($class, $row, $col, $totalRows, false, false, $hasNextRound);
		    
		    $tab .= "<td $inl class='$classWithLines $matchGroupClass' $rowspan >$tmp ";
		    
		}
		
		else
		{
		    $matchGroupClass = getMatchGroupClass($row, $col, $class);
		    
		    // Add bracket interconnecting lines for connecting cells
		    $hasNextRound = ($col < count($b) - 3);
		    $totalRows = count($b[0]);
		    $classWithLines = addBracketLineClasses($class, $row, $col, $totalRows, false, false, $hasNextRound);
		    
		    $tab .= "<td $inl class='$classWithLines $matchGroupClass' $rowspan>";
		}

		$print_ID = 0;
		if ($print_ID && isset($b[$col][$row]['ID']))
		    $tab .= " ID".$b[$col][$row]['ID'];
		
		if (isset($b[$col][$row]['Pi']))
		{
		    $piste = $b[$col][$row]['Pi'];
		    if (strlen($piste)>0)
			$tab .= "Piste ".$b[$col][$row]['Pi'] ."   ";
		}
		
		if (isset($b[$col][$row]['Da'])) // && $b[$col][$row]['Da'])
		{
		    $txt2 = explode(" ", $b[$col][$row]['Da']);
		    $txt = (count($txt2)>1)? $txt2[1]: $b[$col][$row]['Da'];
		    if (is_string($txt) && strlen($txt)>0)
		    {
                           if (isset($b[$col][$row]['DaApprox']))
                        $txt = 'dès ' . $txt;
			$tab .= ''. $txt;
		    }
		}

		if (isset($b[$col][$row]['ArRef'])) // && $b[$col][$row]['Da'])
		{
		    $aref = $b[$col][$row]['ArRef'];
		    if (isset($arbitres[$aref]))
		    {
			$arb = $arbitres[$aref];
			$txt = $arb['DispNom'];
			if ($detail && strlen($txt)>0)
			    $tab .= "Arbitre: $txt ";
		    }
		}
		
		$tab .= $con . "</td>";
	    }
	}
	$tab .= "</tr>\n";
    }
    $tab .= "</tbody></table>";
    $tab .= "</div>"; // Close the new wrapper div
    $tab .= "</div></div>"; // Closes tblhd_tab and its inner div
    return $tab;
}

function rangEntreeTableau ($phase)
{
    $rank = array();
    foreach($phase->getElementsByTagName('Tireur') as $t)
    {
	$REF = $t->getAttribute('REF');
	$Ran = $t->getAttribute('RangInitial');
	if (is_numeric($Ran))
	{
	    $rank[$REF] = $Ran;
	}
    }
    return $rank;
}

function suitTireursTableau ( $xml)
{
    $phases = getAllPhases($xml);
    $tireurs = suitTireurs ($xml);
    recomputeClassement($tireurs);

    $elimines = array();
    $premier = 0;
    
    $rk = array();
    // Injecte tous les tireurs
    foreach ($tireurs as $ref=>$tireur)
    {
        $rk[$ref] = $tireurs[$ref]['ACCU']['Pl2']+40960000;
    }

    foreach ($phases as $phase)
    if ($phase->localName =='PhaseDeTableaux')
    {
        $entree_tableau = rangEntreeTableau($phase);
        foreach ($tireurs as $id=>$val)
        {
            $tireurs[$id]['ACCU']['intab'] = isset($entree_tableau[$id])?1:0;
            $tireurs[$id]['ACCU']['out']   = isset($entree_tableau[$id])?0:1;
	    //        echo $tireurs[$id]['ACCU']['Nom'] . " is out: " . $tireurs[$id]['ACCU']['out'] ."<br>";
        }
        
	foreach($phase->getElementsByTagName('SuiteDeTableaux') as $sdt)
	{
	    foreach($sdt->getElementsByTagName('Tableau') as $tab)
	    {
		$tabid    = $tab->getAttribute('ID');
		$taille   = $tab->getAttribute('Taille');
		$titre    = $tab->getAttribute('Titre');
		$elimines[$tabid] = array();
		$elimines[$tabid]['taille'] = $taille;
		$elimines[$tabid]['matches'] = 0;
		$elimines[$tabid]['finis'] = 0;
		$elimines[$tabid]['out']    = array();

		foreach($tab->getElementsByTagName('Match') as $m)
    		{
                    $dat  = $m->getAttribute('Date').' '.$m->getAttribute('Heure');
                    $pis  = $m->getAttribute('Piste');
		    $adv = $m->getElementsByTagName('Tireur');
		    $len     = $adv->length;
                    if ($len == 1)
                    {
			$ref0 = $adv[0]->getAttribute('REF');
			$ran0 = $tireurs[$ref0]['ACCU']['Pl2'];
                        $tireurs[$ref0]['ACCU']['PiTa'] = $pis;
                        $tireurs[$ref0]['ACCU']['DaTa'] = $dat;
			switch ($adv[0]->getAttribute('Statut'))
			{
			    case 'V':
			    $premier = $ref0;
			    $elimines[$tabid]['finis']++;
                            $tireurs[$ref0]['ACCU']['out'] = ($taille>2)?0:1;
                            $rk[$ref0]= $tireurs[$ref0]['ACCU']['Pl2']+($taille/2)*10000;
			    break;
                        }
                    }
		    if ($len == 2)
		    {
			$elimines[$tabid]['matches']++;
			// Keep track of eliminated fencers
			$ref0 = $adv[0]->getAttribute('REF');
			$ref1 = $adv[1]->getAttribute('REF');
			$ran0 = $tireurs[$ref0]['ACCU']['Pl2'];
			$ran1 = $tireurs[$ref1]['ACCU']['Pl2'];
                        $dat  = $m->getAttribute('Date').' '.$m->getAttribute('Heure');
                        $pis  = $m->getAttribute('Piste');
                        

			switch ($adv[0]->getAttribute('Statut'))
			{
			    case 'V':
			    $premier = $ref0;
			    $elimines[$tabid]['finis']++;
                            $tireurs[$ref0]['ACCU']['out'] = ($taille>2)?0:1;
                            $rk[$ref0] = $tireurs[$ref0]['ACCU']['Pl2']+($taille/2)*10000;
                            $tireurs[$ref0]['ACCU']['PiTa'] = '';
                            $tireurs[$ref0]['ACCU']['DaTa'] = '';
			    break;
			    
			    case '':
                            $tireurs[$ref0]['ACCU']['PiTa'] = $pis;
                            $tireurs[$ref0]['ACCU']['DaTa'] = $dat;
                            $rk[$ref0] = $tireurs[$ref0]['ACCU']['Pl2']+($taille)*10000;
                            break;
			    
                            case 'E':  // Exclusion means you are not ranked
                            $tireurs[$ref0]['ACCU']['out'] = 1;
                            $rk[$ref0] = INF;
                            $tireurs[$ref0]['ACCU']['PiTa'] = '';
                            $tireurs[$ref0]['ACCU']['DaTa'] = '';
			    break;

			    case 'D':
			    case 'A':
			    $elimines[$tabid]['out'][$ref0] = 1*$ran0;
			    //                                echo "OUT $ran0:" . $tireurs[$ref0]['ACCU']['Nom'] ."<br>";
                            $tireurs[$ref0]['ACCU']['out'] = 1;
                            $rk[$ref0]= $tireurs[$ref0]['ACCU']['Pl2']+($taille)*10000+5000;
                            $tireurs[$ref0]['ACCU']['PiTa'] = '';
                            $tireurs[$ref0]['ACCU']['DaTa'] = '';
			    break;
			}
			switch ($adv[1]->getAttribute('Statut'))
			{
			    case 'V':
			    $premier = $ref1;
			    $elimines[$tabid]['finis']++;
                            $tireurs[$ref1]['ACCU']['out'] = ($taille>2)?0:1;
                            $rk[$ref1] = $tireurs[$ref1]['ACCU']['Pl2']+($taille/2)*10000;
                            $tireurs[$ref1]['ACCU']['PiTa'] = '';
                            $tireurs[$ref1]['ACCU']['DaTa'] = '';

			    break;
			    
			    case '':
                            $tireurs[$ref1]['ACCU']['PiTa'] = $pis;
                            $tireurs[$ref1]['ACCU']['DaTa'] = $dat;
                            $rk[$ref1] = $tireurs[$ref1]['ACCU']['Pl2']+($taille)*10000;
                            break;
                            
			    case 'E':  // Exclusion means you are not ranked
                            $tireurs[$ref1]['ACCU']['out'] = 1;
                            $rk[$ref1] = INF;
                            $tireurs[$ref1]['ACCU']['PiTa'] = '';
                            $tireurs[$ref1]['ACCU']['DaTa'] = '';
                            break;
			    
			    case 'D':
			    case 'A':
			    $elimines[$tabid]['out'][$ref1] = 1*$ran1;
			    //                            echo "OUT $ran1:" . $tireurs[$ref1]['ACCU']['Nom'] ."<br>";
                            $tireurs[$ref1]['ACCU']['out'] = 1;
                            $rk[$ref1]= $tireurs[$ref1]['ACCU']['Pl2']+($taille)*10000+5000;
                            $tireurs[$ref1]['ACCU']['PiTa'] = '';
                            $tireurs[$ref1]['ACCU']['DaTa'] = '';
			    break;
			}
		    } // LEN == 2

		} // foreach matches
	    } // foreach tableaux
	}// suite de tabelau
    } // phases

    asort($rk);
    $pl=1;
    $ln=0;
    $ov=0;
    $Tdone = array();
    $Tprov = array();
    foreach ($rk as $ref => $v)
    {
        $tou = floor($v/10000.0);
        
        if(!isset($Tdone[$tou]))
            $Tdone[$tou]=0;
        if(!isset($Tprov[$tou]))
            $Tprov[$tou]=0;
        
        
        if($tireurs[$ref]['ACCU']['out'])
            $Tdone[$tou]++;
        else
            $Tprov[$tou]++;
        
        $tireurs[$ref]['ACCU']['PlaTabTri'] = $v;
        $ln++;
        if ($v>$ov)
        {
            if ($v == INF)
                $tireurs[$ref]['ACCU']['PlaTab'] = INF;
            else
		$tireurs[$ref]['ACCU']['PlaTab'] = ($ln==4)?3:$ln;
            $pl = $ln;
        }
        else
        {
            if ($v == INF)
                $tireurs[$ref]['ACCU']['PlaTab'] = INF;
            else
		$tireurs[$ref]['ACCU']['PlaTab'] = $pl;
        }   
        $ov = $v;
    }	

    foreach ($rk as $ref => $v)
    {
        $tou = floor($v/10000.0);
        $tireurs[$ref]['ACCU']['tourdone']=1;
        
        if($Tdone[$tou]>0 && $Tprov[$tou]>0)
            $tireurs[$ref]['ACCU']['tourdone']=0;
        
        $rang_max = floor($tou/2);
        if($tireurs[$ref]['ACCU']['PlaTab'] < $rang_max)
            $tireurs[$ref]['ACCU']['tourdone']=0;
        
    }
    
    return $tireurs;
}

//------------
function prepairMyTableau( $xml, $full, $selectedSuiteId = null )
{
    $verbose = 0*1;  // Enable to see who the function works
    $hide_referee_when_done = 1;
    
    $phases = getAllPhases($xml);
    $isEquipe = ($xml->documentElement->localName === 'CompetitionParEquipes');
    $scale = autoscaleTableau($xml); // Automatically decide start and stop columns

    $a = array(); 
    $b = array();

    foreach ($phases as $phase)
    if ($phase->localName =='PhaseDeTableaux')
    {
        $rank = rangEntreeTableau ($phase);        foreach($phase->getElementsByTagName('SuiteDeTableaux') as $sdt)
	{
	    $suiteId = $sdt->getAttribute('ID');
	    
	    // If a specific suite ID is selected, only process that one
	    if ($selectedSuiteId !== null && $suiteId !== $selectedSuiteId) {
	        continue;
	    }
	    
	    $a = array(); 
	    $b = array();
	    $col = 0;
	    $previous = array(); /* When a fencer has no opponent, should it appear as 1st or 2nd fencer? */
	    foreach($sdt->getElementsByTagName('Tableau') as $tab)
	    {
		$tabid    = $tab->getAttribute('ID');
		$taille   = $tab->getAttribute('Taille');
		$titre    = $tab->getAttribute('Titre');
		$tabStart = (isset($_GET['tabStart']))? $_GET['tabStart'] : $scale['tabStart'];
		$tabEnd   = (isset($_GET['tabEnd']))?   $_GET['tabEnd']   : $scale['tabEnd'];
		if ($full)
		    $tabEnd=1;

		if ($taille >= $tabEnd && $taille <= $tabStart )  // AUTOSCALE
		{
		    $entree  = ($taille == $tabStart) || ($col==0);
		    $pat     = getTableauPattern($col+1);
		    $b[$col*3]   = array();
		    $a[$col*3]   = '';
		    $b[$col*3+1] = array();
		    $a[$col*3+1] = 'T'.$taille; //$titre;
		    $b[$col*3+2] = array();
		    $a[$col*3+2] = '';

		    $narow = 0;  // Row counter for narrow part
		    $wirow = 0;  // Row counter for wide part
		    $nbm   = 0;  // Number of matches
		    
		    foreach($tab->getElementsByTagName('Match') as $m)
		    {
			$nbm++;
			$maid    = 1*$m->getAttribute('ID');
			$participants = $isEquipe ? $m->getElementsByTagName('Equipe') : $m->getElementsByTagName('Tireur');
			$arbitre = $m->getElementsByTagName('Arbitre');
			$len     = $participants->length;

			// Last match ID this participant was in
			$pre  = (isset($participants[0]) && (isset($previous[$participants[0]->getAttribute('REF')]))) ?
				$previous[$participants[0]->getAttribute('REF')] : 0;
			
			$tst0 = ($len==2);
			$tst1 = ($len==1 &&  ($col==0 && ($nbm <= $taille/4)));
			$tst2 = ($len==1 &&  ($col>0  && ($previous[$participants[0]->getAttribute('REF')] % 2 == 1)));
			$tst3 = ($len==1 &&  ($col==0 && ($nbm > $taille/4)));
			$tst4 = ($len==1 &&  ($col>0  && ($previous[$participants[0]->getAttribute('REF')] % 2 == 0)));

			// Narrow column
			for ($k = 0; $k < $pat['net']; $k++) $b[$col*3][$narow++] = array('class' => 'Tableau_net');  // Empty Top
			for ($k = 0; $k < $pat['nrt']; $k++) $b[$col*3][$narow++] = array('class' => 'Tableau_nrt');
			for ($k = 0; $k < $pat['nvt']; $k++) $b[$col*3][$narow++] = array('class' => 'Tableau_nvt');
			for ($k = 0; $k < $pat['nem']; $k++) $b[$col*3][$narow++] = array('class' => 'Tableau_nem');  // Empty Mid
			for ($k = 0; $k < $pat['nvb']; $k++) $b[$col*3][$narow++] = array('class' => 'Tableau_nvb');
			for ($k = 0; $k < $pat['nrb']; $k++) $b[$col*3][$narow++] = array('class' => 'Tableau_nrb');
			for ($k = 0; $k < $pat['neb']; $k++) $b[$col*3][$narow++] = array('class' => 'Tableau_neb');  // Empty Bot

			// Wide column
			for ($k = 0; $k < $pat['wet']; $k++)
			{
			    $b[$col*3+1][$wirow]   = array('class' => 'Tableau_wet');  // Empty Top
			    $b[$col*3+2][$wirow++] = array('class' => 'Tableau_wet');  // Empty Top
			}
			
			// Ajout des traits de liaison
			if ($col > 0) {
			    $b[$col*3][$wirow] = array('class' => 'Tableau_nvt trait trait_v');
			    $b[$col*3+1][$wirow] = array('class' => 'Tableau_wet trait trait_h');
			    $b[$col*3+2][$wirow] = array('class' => 'Tableau_wet');
			}
			
			// Top Participant
			if ( $tst0 || $tst1 || $tst2)
			{
			    if ($len==2) // Two opponents
			    {
				$b[$col*3+1][$wirow]   = array('class'  => 'Tableau_wto_lef',
							       'REF'    => $participants[0]->getAttribute('REF'),
							       'Ran'    => (isset($rank[$participants[0]->getAttribute('REF')]) ? $rank[$participants[0]->getAttribute('REF')] : '-'),
							       'Fla' => $entree );
				$b[$col*3+2][$wirow] = array('class'  => 'Tableau_wto',
							     'Statut' => $participants[0]->getAttribute('Statut'),
							     'Score'  => $participants[0]->getAttribute('Score'));
				if($participants[0]->getAttribute('Statut')=='')
				    $b[$col*3+2][$wirow]['Pi']    = $m->getAttribute('Piste');
				$wirow++;
			    }
			    else // Less than two opponents, therefore no score 
			    {
				$b[$col*3+1][$wirow]   = array('class'  => 'Tableau_wto_lef',
							       'REF'    => $participants[0]->getAttribute('REF'),
							       'Ran'    => (isset($rank[$participants[0]->getAttribute('REF')]) ? $rank[$participants[0]->getAttribute('REF')] : '-'),
							       'Fla' => $entree );
				
				$b[$col*3+2][$wirow] = array('class'  => 'Tableau_wto');
				$wirow++;
			    }
			    
			    $previous[$participants[0]->getAttribute('REF')] = $maid;
			}
			else
			{
			    $b[$col*3+1][$wirow]   = array('class'  => 'Tableau_wto_lef');
			    $b[$col*3+2][$wirow++] = array('class'  => 'Tableau_wto');
			}
			for ($nu=0;$nu<NB_ROWS_TIREUR-1;$nu++)
			{
			    $b[$col*3+1][$wirow]   = array('class'  => 'Tableau_nul');
			    $b[$col*3+2][$wirow++] = array('class'  => 'Tableau_nul');
			}

			if (NB_ROWS_TIREUR==2)
			{
			    // Mid Info
			    $arbref = '';
			    if (isset($arbitre[0]))
			    {
				$arbref = $arbitre[0]->getAttribute('REF');
			    }
			    
			    $b[$col*3+1][$wirow] = array('class' => 'Tableau_wmi_lef');
				$b[$col*3+2][$wirow] = array('class' => 'Tableau_wmi',
							 'ID'    => $m->getAttribute('ID')  );

			    if(isset($participants[0]) && $participants[0]->getAttribute('Statut')!='')
			    {
				// Match is finished, show referee
				$b[$col*3+1][$wirow]['ArRef'] = $arbref;
			    }
			    else
			    {
				// Match is planned but not finished, don't show referee
				//	$b[$col*3+2][$wirow]['Pi']    = $m->getAttribute('Piste');
				//	$b[$col*3+2][$wirow]['Da']    = $m->getAttribute('Date');
			    }
			    $wirow++;

			    $b[$col*3+1][$wirow]   = array('class'  => 'Tableau_nul');
			    $b[$col*3+2][$wirow++] = array('class'  => 'Tableau_nul');
			}

			// Bottom Participant
			if ( $tst3 || $tst4) // Gagne sans combattre, donc pas de score
			{
			    $b[$col*3+1][$wirow]   = array('class'  => 'Tableau_wbo_lef',
							       'REF'    => $participants[0]->getAttribute('REF'),
							       'Ran'    => (isset($rank[$participants[0]->getAttribute('REF')]) ? $rank[$participants[0]->getAttribute('REF')] : '-'),
							       'Fla' => $entree );
			    
			    $b[$col*3+2][$wirow] = array('class'  => 'Tableau_wbo');
			    if ($m->getAttribute('Piste')=='')
				$b[$col*3+2][$wirow]['DaApprox'] = 1;
			    $b[$col*3+2][$wirow]['Da']       = $m->getAttribute('Date').' '.$m->getAttribute('Heure');
			    $wirow++;
			    $previous[$participants[0]->getAttribute('REF')] = $maid;
			}
			else if ($participants->length==2)
			{
			    $b[$col*3+1][$wirow]   = array('class'  => 'Tableau_wbo_lef',
							       'REF'    => $participants[1]->getAttribute('REF'),
							       'Ran'    => (isset($rank[$participants[1]->getAttribute('REF')]) ? $rank[$participants[1]->getAttribute('REF')] : '-'),
							       'Fla' => $entree );

			    $b[$col*3+2][$wirow] = array('class'  => 'Tableau_wbo',
							 'Statut' => $participants[1]->getAttribute('Statut'),
							 'Score'  => $participants[1]->getAttribute('Score'));
			    $previous[$participants[1]->getAttribute('REF')] = $maid;

			    if ($participants[1]->getAttribute('Statut')=='')
			    {
				$b[$col*3+2][$wirow]['Da']     = $m->getAttribute('Date').' '.$m->getAttribute('Heure');
				if ($m->getAttribute('Piste')=='')
				    $b[$col*3+2][$wirow]['DaApprox'] = 1;
			    }
			    $wirow++;
			}
			else
			{
			    $b[$col*3+1][$wirow]   = array('class'  => 'Tableau_wbo_lef');
			    $b[$col*3+2][$wirow++] = array('class'  => 'Tableau_wbo');
			}
			
			for ($nu=0;$nu<NB_ROWS_TIREUR-1;$nu++)
			{
			    $b[$col*3+1][$wirow]   = array('class'  => 'Tableau_nul');
			    $b[$col*3+2][$wirow++] = array('class'  => 'Tableau_nul');
			}
			
			// Empty Bottom
			for ($k = 0; $k < $pat['web']; $k++)
			{
			    $b[$col*3+1][$wirow]   = array('class' => 'Tableau_web');
			    $b[$col*3+2][$wirow++] = array('class' => 'Tableau_web');
			}
                    }
                    $col++;
                }
            }
        }
    }
    return array('a'=>$a, 'b'=>$b);
}

function origami ($a,$b,$showfinal)
{
    $aa = array();
    $bb = array();

    $nbc = count($b)-2;
    
    if ($nbc<4)
        return array('a'=>$a, 'b' => $b);
    $nbr = count($b[0]);
    $hnr = ceil($nbr/2);
    for ($col=0;$col<$nbc;$col++)
    {
	$new_col =2*$nbc-$col-1;
	$aa[$col]     = $a[$col];
	$aa[$new_col] = $a[$col];           
        
	for ($row=0;$row<$hnr;$row++)
	{
	    $old_row = $hnr + $row;
	    $bb[$col][$row]     = $b[$col][$row];
	    $bb[$new_col][$row] = $b[$col][$old_row];
	    $bb[$new_col][$row]['class'] .=  '_flip';
	}
    }

    // erase vertical line originally routing to final
    for ($row = 0; $row<$hnr/2;$row++)
    {
        $bb[$nbc][$row]['class'] = 'Tableau_net';
        $bb[$nbc-1][$row]['class'] = 'Tableau_net';
    }
    
    if (!$showfinal)
    {
        for ($row = 0; $row<$hnr;$row++)
        {
            $bb[$nbc-1][$row]['class']   = 'Tableau_net';
        }
    }
    // Greffe les finalistes quelque part
    // Finaliste gauche
    
    if ($showfinal)
    {
        $bb[$nbc][($hnr/2)]['class'] = 'Tableau_nrt_flip';
        $vrow = $hnr-3;
        for ($kr = 0;$kr<4;$kr++)
        {
            for ($kc = 0; $kc <2; $kc++)
            {
                $bb[$nbc-3+$kc][$vrow+$kr] = $b[$nbc+$kc][$hnr+$kr-3];
                $bb[$nbc-3+$kc][$vrow+$kr]['class'] .= '_final';
            }
        }

        // Finaliste droite
        for ($kr = 0;$kr<4;$kr++)
        {
            for ($kc = 0; $kc <2; $kc++)
            {
                $bb[$nbc+2-$kc][$vrow+$kr] = $b[$nbc+$kc][$hnr+$kr+1];
                $bb[$nbc+2-$kc][$vrow+$kr]['class'] .= '_flip_final';
            }
        }
    }
    return array('a'=>$aa, 'b' => $bb);
}

function origami2 ($a,$b)
{
    $aa = array();
    $bb = array();
    $nbc = count($b);
    $nbr = count($b[0]);
    $hnr = ceil($nbr/2);
    for ($col=0;$col<$nbc;$col++)
    {
	$new_col      = $nbc+$col+1;
	$aa[$col]     = $a[$col];
	$aa[$new_col] = $a[$col];           
        $aa[$nbc]     = '   ';
	for ($row=0;$row<$hnr;$row++)
	{
	    $old_row = $hnr + $row;
	    $bb[$nbc][$row]     = array('class'  => 'Tableau_sep');
	    $bb[$col][$row]     = $b[$col][$row];
	    $bb[$new_col][$row] = $b[$col][$old_row];
	}
    }
    return array('a'=>$aa, 'b' => $bb);
}


function getTitre($xml)
{
    if (!$xml || !$xml->documentElement) {
        return "Compétition BellePoule";
    }
    
    $comp = $xml->getElementsByTagName('Competition');
    
    // Si comp n'est pas un tableau valide ou est vide, utiliser directement documentElement
    if (!$comp || $comp->length == 0) {
        $title = $xml->documentElement->getAttribute('TitreLong');
        if (empty($title)) {
            $arme = $xml->documentElement->getAttribute('Arme');
            $cate = $xml->documentElement->getAttribute('Categorie');
            $sexe = $xml->documentElement->getAttribute('Sexe');
            
            // Formater le titre à partir des attributs
            $title = formatWeapon($arme);
            if (!empty($sexe)) {
                $title .= " " . formatGender($sexe);
            }
            if (!empty($cate)) {
                $title .= " " . $cate;
            }
        }
        return $title ?: "Compétition BellePoule";
    }
    
    // Sinon utiliser le premier élément de comp
    $title = $comp->item(0)->getAttribute('TitreLong');
    
    // Si le titre long est vide, construire un titre à partir des autres attributs
    if (empty($title)) {
        $arme = $comp->item(0)->getAttribute('Arme');
        $cate = $comp->item(0)->getAttribute('Categorie');
        $sexe = $comp->item(0)->getAttribute('Sexe');
        
        // Formater le titre à partir des attributs
        $title = formatWeapon($arme);
        if (!empty($sexe)) {
            $title .= " " . formatGender($sexe);
        }
        if (!empty($cate)) {
            $title .= " " . $cate;
        }
    }
    
    return $title ?: "Compétition BellePoule";
}

/**
 * Récupère les données des équipes pour une compétition par équipes
 */
function getEquipesData($xml)
{
    $equipes = array();
    foreach ($xml->getElementsByTagName('Equipes') as $equipesNode) {
        foreach ($equipesNode->getElementsByTagName('Equipe') as $equipe) {
            $id = $equipe->getAttribute('ID');            $equipes[$id] = array(
                'Nom' => $equipe->getAttribute('Nom'),
                'Nation' => $equipe->getAttribute('Nation'),
                'Region' => $equipe->getAttribute('Region') ?: '',
                'Statut' => $equipe->getAttribute('Statut') ?: 'Q', // Default to present if no status
                'Club' => '',
                'Membres' => array()
            );
            
            // Récupérer les membres pour déterminer le club principal
            $clubs = array();
            foreach ($equipe->getElementsByTagName('Tireur') as $tireur) {
                $club = $tireur->getAttribute('Club');
                if (!empty($club)) {
                    $clubs[$club] = isset($clubs[$club]) ? $clubs[$club] + 1 : 1;
                }
                $equipes[$id]['Membres'][] = array(
                    'Nom' => $tireur->getAttribute('Nom'),
                    'Prenom' => $tireur->getAttribute('Prenom'),
                    'Club' => $club,
                    'Nation' => $tireur->getAttribute('Nation'),
                    'Statut' => $tireur->getAttribute('Statut') ?: 'Q'
                );
            }
            
            // Prendre le club le plus représenté
            if (!empty($clubs)) {
                arsort($clubs);
                $equipes[$id]['Club'] = array_keys($clubs)[0];
            }
        }
    }
    return $equipes;
}

/**
 * Affiche la liste de présence des équipes
 */
function renderListePresenceEquipes($xml)
{
    $equipes = getEquipesData($xml);
    
    if (empty($equipes)) {
        return "<div class='tblhd_top'><span class='tbl_banner'>&#9776; LISTE DE PRÉSENCE</span><br><div class='tblhd'><p>Aucune équipe trouvée.</p></div></div>";
    }
    
    // Compter les équipes présentes vs totales
    $cnt_total = count($equipes);
    $cnt_present = 0;
    foreach ($equipes as $equipe) {
        if ($equipe['Statut'] != 'F') { // 'F' = Forfait/Absent
            $cnt_present++;
        }
    }
    
    $titre = "$cnt_present ÉQUIPES PRÉSENTES SUR $cnt_total INSCRITES";
    
    $r = '';
    $r .= "<div class='tblhd_top' onclick='mickey()'><span class='tbl_banner'>&#9776; LISTE DE PRÉSENCE</span><br>";
    $r .= "<div class='tblhd'><div></div>\n";
    $r .= "<div style='text-align:center;font-weight:bold;margin-bottom:10px;'>$titre</div>";
    
    $fixed_height = isset($_GET['scroll']) ? 'fh' : '';
    $r .= "<table id='scrollme' class='listeTireur $fixed_height'>\n";
    
    // En-tête du tableau
    $r .= "<thead class='monocol'>\n";
    $r .= "<tr>\n";
    $r .= "<th class='VR'><div class='tblhead'>Équipe</div></th>";
    $r .= "<th class='VR'><div class='tblhead'>Tireurs (Club)</div></th>";
    $r .= "<th class='VR'><div class='tblhead'>Club Principal</div></th>";
    $r .= "<th class='VR'><div class='tblhead'>Région</div></th>";
    $r .= "<th class='VR'><div class='tblhead'>Nation</div></th>";
    $r .= "<th class='VR'><div class='tblhead'>Statut</div></th>";
    $r .= "</tr>\n</thead>\n";
    
    $r .= "<tbody id='tblbdy'>\n";
    
    // Corps du tableau - trier par nom d'équipe
    $equipesArray = array_values($equipes);
    usort($equipesArray, function($a, $b) {
        return strcasecmp($a['Nom'], $b['Nom']);
    });
    
    $pair = "impair";
    foreach ($equipesArray as $equipe) {
        $pair = $pair == "pair" ? "impair" : "pair";
        $style = $pair . "QL";
        
        // Ajouter une classe pour les équipes absentes
        if ($equipe['Statut'] == 'F') {
            $style .= " absent";
        }
        
        $r .= "<tr class='$style'>";
        
        // Nom équipe
        $r .= "<td class='VR'>";
        $r .= "<strong>" . htmlspecialchars($equipe['Nom']) . "</strong>";
        $r .= "</td>";
        
        // Colonne Tireurs (Club)
        $r .= "<td class='VR'>";
        if (!empty($equipe['Membres'])) {
            $r .= "<ul style='margin:0;padding-left:18px;font-size:0.9em;'>";
            foreach ($equipe['Membres'] as $membre) {
                $fencerName = htmlspecialchars($membre['Nom']) . ' ' . htmlspecialchars($membre['Prenom']);
                $club = !empty($membre['Club']) ? ' (' . htmlspecialchars($membre['Club']) . ')' : '';
                $r .= "<li>" . $fencerName . $club . "</li>";
            }
            $r .= "</ul>";
        }
        $r .= "</td>";
        
        // Club Principal
        $r .= "<td class='VR'>" . htmlspecialchars($equipe['Club']) . "</td>";
        
        // Région
        $r .= "<td class='VR'>" . htmlspecialchars($equipe['Region']) . "</td>";
        
        // Nation avec drapeau
        $r .= "<td class='VR'>";
        if (!empty($equipe['Nation'])) {
            $r .= flag_icon($equipe['Nation'], 'flag_icon') . ' ' . htmlspecialchars($equipe['Nation']);
        }
        $r .= "</td>";
        
        // Statut
        $r .= "<td class='VR'>";
        switch ($equipe['Statut']) {
            case 'F':
                $r .= "<span style='color:#ff6b6b;'>Forfait</span>";
                break;
            case 'E':
                $r .= "<span style='color:#ff6b6b;'>Exclu</span>";
                break;
            case 'Q':
            case 'N':
            case 'A':
            default:
                $r .= "<span style='color:#4CAF50;'>Présent</span>";
                break;
        }
        $r .= "</td>";
        
        $r .= "</tr>\n";
    }
    
    $r .= "</tbody></table></div></div>";
    
    return $r;
}

/**
 * Récupère le classement détaillé des poules pour les équipes d'un tour spécifique
 */
function getClassementPoulesEquipesDetaille($xml, $tourId)
{
    $classement = array();
    $phases = getAllPhases($xml);
    $equipes = getEquipesData($xml);
    $equipesStats = array(); // Pour agréger les stats de toutes les poules
    
    $tourCnt = 0;
    foreach ($phases as $phase) {
        if ($phase->localName == 'TourDePoules') {
            $tourCnt++;
            if ($tourCnt == $tourId) {
                // Première passe: collecter toutes les équipes et leurs infos de base
                foreach ($phase->getElementsByTagName('Equipe') as $equipe) {
                    $ref = $equipe->getAttribute('REF');
                    $rangFinal = $equipe->getAttribute('RangFinal');
                    $statut = $equipe->getAttribute('Statut') ?: 'Q';
                    
                    if ($ref && isset($equipes[$ref]) && !isset($equipesStats[$ref])) {
                        $equipesStats[$ref] = array(
                            'REF' => $ref,
                            'RangFinal' => intval($rangFinal ?: 999),
                            'Nom' => $equipes[$ref]['Nom'],
                            'Club' => $equipes[$ref]['Club'],
                            'Nation' => $equipes[$ref]['Nation'],
                            'Region' => $equipes[$ref]['Region'],
                            'Membres' => $equipes[$ref]['Membres'],
                            'Statut' => $statut,
                            'Victoires' => 0,
                            'Matches' => 0,
                            'TD' => 0,
                            'TR' => 0,
                            'Place' => intval($equipe->getAttribute('Place') ?: 0)
                        );
                    }
                }
                
                // Deuxième passe: agréger les statistiques de toutes les poules
                foreach ($phase->getElementsByTagName('Poule') as $poule) {
                    $pouleStats = getPouleStatsEquipes($poule);
                    
                    foreach ($pouleStats as $ref => $stats) {
                        if (isset($equipesStats[$ref])) {
                            $equipesStats[$ref]['Victoires'] += $stats['Vi'];
                            $equipesStats[$ref]['Matches'] += $stats['Ma'];
                            $equipesStats[$ref]['TD'] += $stats['TD'];
                            $equipesStats[$ref]['TR'] += $stats['TR'];
                        }
                    }
                }
                
                // Convertir en tableau et calculer les indicateurs finaux
                foreach ($equipesStats as $ref => $equipeData) {
                    $equipeData['Ind'] = $equipeData['TD'] - $equipeData['TR'];
                    $equipeData['VM'] = $equipeData['Matches'] > 0 ? round(($equipeData['Victoires'] / $equipeData['Matches']) * 1000) : 0;
                    $classement[] = $equipeData;
                }
                
                break;
            }
        }
    }
    
    // Trier par rang final
    usort($classement, function($a, $b) {
        return $a['RangFinal'] - $b['RangFinal'];
    });
    
    return $classement;
}

/**
 * Vérifie si une équipe est qualifiée pour les tableaux
 */
function isEquipeQualifieeTableau($xml, $equipeRef)
{
    $tableaux = $xml->getElementsByTagName('PhaseDeTableaux');
    foreach ($tableaux as $tableau) {
        $equipes = $tableau->getElementsByTagName('Equipe');
        foreach ($equipes as $equipe) {
            if ($equipe->getAttribute('REF') == $equipeRef) {
                return true;
            }
        }
    }
    return false;
}

/**
 * Récupère les statistiques d'une poule pour les équipes
 */
function getPouleStatsEquipes($poule)
{
    $stats = array();
    $matches = $poule->getElementsByTagName('Match');
    
    // Initialiser les stats pour chaque équipe dans la poule
    foreach ($poule->getElementsByTagName('Equipe') as $equipe) {
        $ref = $equipe->getAttribute('REF');
        $stats[$ref] = array(
            'Vi' => 0, 'Ma' => 0, 'TD' => 0, 'TR' => 0, 'Pl' => intval($equipe->getAttribute('Place') ?: 0)
        );
    }
    
    // Calculer les statistiques à partir des matches
    foreach ($matches as $match) {
        $equipes = $match->getElementsByTagName('Equipe');
        if ($equipes->length == 2) {
            $equipe1 = $equipes->item(0);
            $equipe2 = $equipes->item(1);
            
            $ref1 = $equipe1->getAttribute('REF');
            $ref2 = $equipe2->getAttribute('REF');
            $score1 = intval($equipe1->getAttribute('Score') ?: 0);
            $score2 = intval($equipe2->getAttribute('Score') ?: 0);
            $statut1 = $equipe1->getAttribute('Statut');
            $statut2 = $equipe2->getAttribute('Statut');
            
            // Vérifier si le match est terminé
            if ($statut1 && $statut2 && ($statut1 == 'V' || $statut1 == 'D') && ($statut2 == 'V' || $statut2 == 'D')) {
                if (isset($stats[$ref1]) && isset($stats[$ref2])) {
                    // Compter les matches
                    $stats[$ref1]['Ma']++;
                    $stats[$ref2]['Ma']++;
                    
                    // Compter les victoires
                    if ($statut1 == 'V') {
                        $stats[$ref1]['Vi']++;
                    } else {
                        $stats[$ref2]['Vi']++;
                    }
                    
                    // Compter les touches
                    $stats[$ref1]['TD'] += $score1;
                    $stats[$ref1]['TR'] += $score2;
                    $stats[$ref2]['TD'] += $score2;
                    $stats[$ref2]['TR'] += $score1;
                }
            }
        }
    }
    
    return $stats;
}

/**
 * Récupère le classement des poules pour les équipes d'un tour spécifique (version simple)
 */
function getClassementPoulesEquipes($xml, $tourId)
{
    $classement = array();
    $phases = getAllPhases($xml);
    $equipes = getEquipesData($xml);
    
    $tourCnt = 0;
    foreach ($phases as $phase) {
        if ($phase->localName == 'TourDePoules') {
            $tourCnt++;
            if ($tourCnt == $tourId) {
                foreach ($phase->getElementsByTagName('Equipe') as $equipe) {
                    $ref = $equipe->getAttribute('REF');
                    $rangFinal = $equipe->getAttribute('RangFinal');
                    
                    if ($ref && $rangFinal && isset($equipes[$ref])) {
                        $classement[] = array(
                            'REF' => $ref,
                            'RangFinal' => intval($rangFinal),
                            'Nom' => $equipes[$ref]['Nom'],
                            'Club' => $equipes[$ref]['Club'],
                            'Nation' => $equipes[$ref]['Nation'],
                            'Membres' => $equipes[$ref]['Membres']
                        );
                    }
                }
                break;
            }
        }
    }
    
    // Trier par rang final
    usort($classement, function($a, $b) {
        return $a['RangFinal'] - $b['RangFinal'];
    });
    
    return $classement;
}

/**
 * Affiche le classement des poules pour les équipes
 */
function renderClassementPoulesEquipes($xml, $tour)
{
    $totalTours = countTourDePoules($xml);
    $classement = getClassementPoulesEquipesDetaille($xml, $tour);
    
    if (empty($classement)) {
        return "<div class='tblhd_top'><span class='tbl_banner'>&#9776; CLASSEMENT POULES (résultat provisoire)</span><br><div class='tblhd'><p>Aucun classement disponible pour ce tour.</p></div></div>";
    }
    
    $titre = "CLASSEMENT POULES - Tour $tour - (résultat provisoire)";
    
    $r = '';
    $r .= "<div class='tblhd_top' onclick='mickey()'><span class='tbl_banner'>&#9776; $titre</span><br>";
    $r .= "<div class='tblhd'><div></div>\n";
      // Sélecteur de tour si plusieurs tours
    if ($totalTours > 1) {
        $r .= "<div class='tour-selector' style='margin-bottom:10px;'>";
        $r .= "<label>Sélectionner le tour : </label>";
        $r .= "<select id='tourSelect' onchange='changeTour(this.value)' style='padding:5px;'>";
        for ($i = 1; $i <= $totalTours; $i++) {
            $selected = ($i == $tour) ? 'selected' : '';
            $r .= "<option value='$i' $selected>Tour $i</option>";
        }
        $r .= "</select>";
        $r .= "</div>";
    }
    
    $fixed_height = isset($_GET['scroll']) ? 'fh' : '';
    $r .= "<table id='scrollme' class='listeTireur $fixed_height'>\n";
    
    // En-tête du tableau avec colonnes détaillées
    $r .= "<thead class='monocol'>\n";
    $r .= "<tr>\n";
    $r .= "<th class='B RIG VR'><div class='tblhead'>Place</div></th>";
    $r .= "<th class='VR'><div class='tblhead'>Équipe</div></th>";
    $r .= "<th class='VR'><div class='tblhead'>Nation</div></th>";
    $r .= "<th class='VR'><div class='tblhead'>Région</div></th>";
    $r .= "<th class='VR'><div class='tblhead'>Membres</div></th>";
    $r .= "<th class='MID'><div class='tblhead'>V/M</div></th>";
    $r .= "<th class='MID'><div class='tblhead'>TD-TR</div></th>";
    $r .= "<th class='MID'><div class='tblhead'>Ind.</div></th>";
    $r .= "<th class='VR'><div class='tblhead'>Statut</div></th>";
    $r .= "</tr>\n</thead>\n";
    
    $r .= "<tbody id='tblbdy'>\n";
    
    // Corps du tableau
    $pair = "impair";
    foreach ($classement as $equipe) {
        $pair = $pair == "pair" ? "impair" : "pair";
        $style = $pair . "QL";
        
        // Ajouter une classe pour les équipes éliminées
        if ($equipe['Statut'] == 'E' || $equipe['Statut'] == 'F') {
            $style .= " elimine";
        }
        
        $r .= "<tr class='$style'>";
        
        // Place
        $r .= "<td class='B RIG VR'>{$equipe['RangFinal']}</td>";
        
        // Nom équipe
        $r .= "<td class='VR'>";
        $r .= "<strong>" . htmlspecialchars($equipe['Nom']) . "</strong>";
        $r .= "</td>";
        
        // Nation avec drapeau
        $r .= "<td class='VR'>";
        if (!empty($equipe['Nation'])) {
            $r .= flag_icon($equipe['Nation'], 'flag_icon') . ' ' . htmlspecialchars($equipe['Nation']);
        }
        $r .= "</td>";
        
        // Région
        $r .= "<td class='VR'>" . htmlspecialchars($equipe['Region']) . "</td>";
        
        // Membres de l'équipe
        $r .= "<td class='VR'>";
        if (!empty($equipe['Membres'])) {
            $r .= "<ul style='margin:0;padding-left:18px;font-size:0.9em;'>";
            foreach ($equipe['Membres'] as $membre) {
                $fencerName = htmlspecialchars($membre['Nom']) . ' ' . htmlspecialchars($membre['Prenom']);
                $club = !empty($membre['Club']) ? ' (' . htmlspecialchars($membre['Club']) . ')' : '';
                $r .= "<li>" . $fencerName . $club . "</li>";
            }
            $r .= "</ul>";
        }
        $r .= "</td>";
        
        // V/M (Victoires/Matches)
        $r .= "<td class='MID'>";
        if ($equipe['Matches'] > 0) {
            $r .= $equipe['Victoires'] . '/' . $equipe['Matches'];
        } else {
            $r .= '-';
        }
        $r .= "</td>";
        
        // TD-TR (Touches Données - Touches Reçues)
        $r .= "<td class='MID'>";
        if ($equipe['Matches'] > 0) {
            $r .= $equipe['TD'] . '-' . $equipe['TR'];
        } else {
            $r .= '-';
        }
        $r .= "</td>";
        
        // Ind. (Indicateur)
        $r .= "<td class='MID'>";
        if ($equipe['Matches'] > 0) {
            $ind = $equipe['Ind'];
            $r .= ($ind >= 0 ? '+' : '') . $ind;
        } else {
            $r .= '-';
        }
        $r .= "</td>";
        
        // Statut (Qualifié ou non)
        $r .= "<td class='VR'>";
        
        // Vérifier si l'équipe est qualifiée pour les tableaux
        $isQualified = isEquipeQualifieeTableau($xml, $equipe['REF']);
        
        switch ($equipe['Statut']) {
            case 'Q':
                if ($isQualified) {
                    $r .= "<span style='color:#4CAF50; font-weight:bold;'>Qualifié</span>";
                } else {
                    $r .= "<span style='color:#2196F3;'>En attente</span>";
                }
                break;
            case 'E':
                $r .= "<span style='color:#ff6b6b;'>Éliminé</span>";
                break;
            case 'F':
                $r .= "<span style='color:#ff6b6b;'>Forfait</span>";
                break;
            case 'A':
                $r .= "<span style='color:#ff9800;'>Abandon</span>";
                break;
            case 'N':
                $r .= "<span style='color:#9E9E9E;'>Non qualifié</span>";
                break;
            default:
                // Déterminer le statut basé sur la qualification pour les tableaux
                if ($isQualified) {
                    $r .= "<span style='color:#4CAF50; font-weight:bold;'>Qualifié</span>";
                } else {
                    $r .= "<span style='color:#9E9E9E;'>Non qualifié</span>";
                }
                break;
        }
        $r .= "</td>";
        
        $r .= "</tr>\n";
    }
    
    $r .= "</tbody></table></div></div>";
    
    // Script JavaScript pour le changement de tour
    if ($totalTours > 1) {
        $r .= "<script>
        function changeTour(tour) {
            var url = new URL(window.location);
            url.searchParams.set('tour', tour);
            window.location = url;
        }
        </script>";
    }
    
    return $r;
}

/**
 * Récupère le classement des tableaux pour les compétitions individuelles
 */
function getClassementTableauxIndividuels($xml)
{
    $classement = array();
    $tireurs = getTireurList($xml);
    $hasRangFinal = false;
    
    $phases = getAllPhases($xml);
    foreach ($phases as $phase) {
        if ($phase->localName == 'PhaseDeTableaux') {
            foreach ($phase->getElementsByTagName('Tireur') as $tireur) {
                $ref = $tireur->getAttribute('REF');
                $rangFinal = $tireur->getAttribute('RangFinal');
                
                if ($ref && $rangFinal && isset($tireurs[$ref])) {
                    $hasRangFinal = true;
                    $tireurElement = $tireurs[$ref];
                      $classement[] = array(
                        'REF' => $ref,
                        'RangFinal' => intval($rangFinal),
                        'Nom' => $tireurElement->getAttribute('Nom'),
                        'Prenom' => $tireurElement->getAttribute('Prenom'),
                        'Club' => $tireurElement->getAttribute('Club'),
                        'Nation' => $tireurElement->getAttribute('Nation'),
                        'Region' => $tireurElement->getAttribute('Region') ?: '',
                        'Departement' => $tireurElement->getAttribute('Departement')
                    );
                }
            }
            break; // Premier tableau trouvé
        }
    }
    
    if (!$hasRangFinal) {
        return null; // Pas de RangFinal trouvé
    }
    
    // Trier par rang final
    usort($classement, function($a, $b) {
        return $a['RangFinal'] - $b['RangFinal'];
    });
    
    return $classement;
}

/**
 * Affiche le classement des tableaux pour les compétitions individuelles
 */
function renderClassementTableauxIndividuels($xml)
{
    $classement = getClassementTableauxIndividuels($xml);
    
    if ($classement === null) {
        return "<div class='tblhd_top'><span class='tbl_banner'>&#9776; CLASSEMENT TABLEAUX (résultat provisoire)</span><br><div class='tblhd'><div style='padding:20px;text-align:center;font-size:16px;color:#666;'>Les Phases de Tableaux ne sont pas terminées, l'affichage du classement aura lieu une fois les phases terminées.</div></div></div>";
    }
    
    if (empty($classement)) {
        return "<div class='tblhd_top'><span class='tbl_banner'>&#9776; CLASSEMENT TABLEAUX (résultat provisoire)</span><br><div class='tblhd'><p>Aucun classement disponible.</p></div></div>";
    }
    
    $titre = "CLASSEMENT TABLEAUX ÉLIMINATOIRES (résultat provisoire)";
    
    $r = '';
    $r .= "<div class='tblhd_top' onclick='mickey()'><span class='tbl_banner'>&#9776; $titre</span><br>";
    $r .= "<div class='tblhd'><div></div>\n";
    
    $fixed_height = isset($_GET['scroll']) ? 'fh' : '';
    $r .= "<table id='scrollme' class='listeTireur $fixed_height'>\n";
      // En-tête du tableau
    $r .= "<thead class='monocol'>\n";
    $r .= "<tr>\n";
    $r .= "<th class='B RIG VR'><div class='tblhead'>Rang</div></th>";
    $r .= "<th class='VR'><div class='tblhead'>Nom</div></th>";
    $r .= "<th class='VR'><div class='tblhead'>Prénom</div></th>";
    $r .= "<th class='VR'><div class='tblhead'>Club</div></th>";
    $r .= "<th class='VR'><div class='tblhead'>Région</div></th>";
    $r .= "<th class='VR'><div class='tblhead'>Nation</div></th>";
    $r .= "</tr>\n</thead>\n";
    
    $r .= "<tbody id='tblbdy'>\n";
    
    // Corps du tableau
    $pair = "impair";
    foreach ($classement as $tireur) {
        $pair = $pair == "pair" ? "impair" : "pair";
        $style = $pair . "QL";
        
        $r .= "<tr class='$style'>";
        
        // Rang
        $r .= "<td class='B RIG VR'>{$tireur['RangFinal']}</td>";
          // Nom
        $r .= "<td class='VR'>" . htmlspecialchars($tireur['Nom']) . "</td>";
        
        // Prénom
        $r .= "<td class='VR'>" . htmlspecialchars($tireur['Prenom']) . "</td>";
          // Club
        $r .= "<td class='VR'>" . htmlspecialchars($tireur['Club']) . "</td>";
        
        // Région
        $r .= "<td class='VR'>" . htmlspecialchars($tireur['Region']) . "</td>";
        
        // Nation avec drapeau
        $r .= "<td class='VR'>";
        if (!empty($tireur['Nation'])) {
            $r .= flag_icon($tireur['Nation'], 'flag_icon') . ' ' . htmlspecialchars($tireur['Nation']);
        }
        $r .= "</td>";
        
        $r .= "</tr>\n";
    }
    
    $r .= "</tbody></table></div></div>";
    
    return $r;
}

/**
 * Récupère le classement des tableaux pour les équipes
 */
function getClassementTableauxEquipes($xml)
{
    $classement = array();
    $equipes = getEquipesData($xml);
    $hasRangFinal = false;
    
    $phases = getAllPhases($xml);
    foreach ($phases as $phase) {
        if ($phase->localName == 'PhaseDeTableaux') {
            foreach ($phase->getElementsByTagName('Equipe') as $equipe) {
                $ref = $equipe->getAttribute('REF');
                $rangFinal = $equipe->getAttribute('RangFinal');
                  if ($ref && $rangFinal && isset($equipes[$ref])) {
                    $hasRangFinal = true;
                    $classement[] = array(
                        'REF' => $ref,
                        'RangFinal' => intval($rangFinal),
                        'Nom' => $equipes[$ref]['Nom'],
                        'Club' => $equipes[$ref]['Club'],
                        'Nation' => $equipes[$ref]['Nation'],
                        'Region' => isset($equipes[$ref]['Region']) ? $equipes[$ref]['Region'] : '',
                        'Membres' => $equipes[$ref]['Membres']
                    );
                }
            }
            break; // Premier tableau trouvé
        }
    }
    
    if (!$hasRangFinal) {
        return null; // Pas de RangFinal trouvé
    }
    
    // Trier par rang final
    usort($classement, function($a, $b) {
        return $a['RangFinal'] - $b['RangFinal'];
    });
    
    return $classement;
}



/**
 * Affiche le classement des tableaux pour les équipes
 */
function renderClassementTableauxEquipes($xml)
{
    $classement = getClassementTableauxEquipes($xml);
    
    if ($classement === null) {
        return "<div class='tblhd_top'><span class='tbl_banner'>&#9776; CLASSEMENT TABLEAUX (résultat provisoire)</span><br><div class='tblhd'><div style='padding:20px;text-align:center;font-size:16px;color:#666;'>Les Phases de Tableaux ne sont pas terminées, l'affichage du classement aura lieu une fois les phases terminées.</div></div></div>";
    }
    
    if (empty($classement)) {
        return "<div class='tblhd_top'><span class='tbl_banner'>&#9776; CLASSEMENT TABLEAUX (résultat provisoire)</span><br><div class='tblhd'><p>Aucun classement disponible.</p></div></div>";
    }
      $titre = "CLASSEMENT TABLEAUX ÉLIMINATOIRES (résultat provisoire)";
    
    $r = '';
    $r .= "<div class='tblhd_top' onclick='mickey()'><span class='tbl_banner'>&#9776; $titre</span><br>";
    $r .= "<div class='tblhd'><div></div>\n";
    
    $fixed_height = isset($_GET['scroll']) ? 'fh' : '';
    $r .= "<table id='scrollme' class='listeTireur $fixed_height'>\n";
    
    // En-tête du tableau
    $r .= "<thead class='monocol'>\n";
    $r .= "<tr>\n";
    $r .= "<th class='B RIG VR'><div class='tblhead'>Rang</div></th>";
    $r .= "<th class='VR'><div class='tblhead'>Équipe</div></th>";
    if (isset($_GET['lst'])) {
        $r .= "<th class='VR'><div class='tblhead'>Tireurs (Club)</div></th>";
    }
    $r .= "<th class='VR'><div class='tblhead'>Club</div></th>";
    $r .= "<th class='VR'><div class='tblhead'>Région</div></th>";
    $r .= "<th class='VR'><div class='tblhead'>Nation</div></th>";
    $r .= "</tr>\n</thead>\n";
    
    $r .= "<tbody id='tblbdy'>\n";
    
    // Corps du tableau
    $pair = "impair";
    foreach ($classement as $equipe) {
        $pair = $pair == "pair" ? "impair" : "pair";
        $style = $pair . "QL";
        
        $r .= "<tr class='$style'>";
        
        // Rang
        $r .= "<td class='B RIG VR'>{$equipe['RangFinal']}</td>";
        
        // Nom équipe avec membres si demandé
        $r .= "<td class='VR'>";
        $r .= "<strong>" . htmlspecialchars($equipe['Nom']) . "</strong>";
        if (isset($_GET['lst']) && !empty($equipe['Membres'])) {
            $r .= "<ul style='margin:0;padding-left:18px;font-size:0.9em;'>";
            foreach ($equipe['Membres'] as $membre) {
                $r .= "<li>" . htmlspecialchars($membre['Nom']) . ' ' . htmlspecialchars($membre['Prenom']) . "</li>";
            }
            $r .= "</ul>";
        }
        $r .= "</td>";
        
        // Colonne Tireurs (Club) - seulement sur la page lst
        if (isset($_GET['lst'])) {
            $r .= "<td class='VR'>";
            if (!empty($equipe['Membres'])) {
                $r .= "<ul style='margin:0;padding-left:18px;font-size:0.9em;'>";
                foreach ($equipe['Membres'] as $membre) {
                    $fencerName = htmlspecialchars($membre['Nom']) . ' ' . htmlspecialchars($membre['Prenom']);
                    $club = !empty($membre['Club']) ? ' (' . htmlspecialchars($membre['Club']) . ')' : '';
                    $r .= "<li>" . $fencerName . $club . "</li>";
                }
                $r .= "</ul>";
            }
            $r .= "</td>";
        }
        
          // Club
        $r .= "<td class='VR'>" . htmlspecialchars($equipe['Club']) . "</td>";
        
        // Région
        $r .= "<td class='VR'>" . htmlspecialchars(isset($equipe['Region']) ? $equipe['Region'] : '') . "</td>";
        
        // Nation avec drapeau
        $r .= "<td class='VR'>";
        if (!empty($equipe['Nation'])) {
            $r .= flag_icon($equipe['Nation'], 'flag_icon') . ' ' . htmlspecialchars($equipe['Nation']);
        }
        $r .= "</td>";
        
        $r .= "</tr>\n";
    }
    
    $r .= "</tbody></table></div></div>";
    
    return $r;
}

/**
 * Récupère les options disponibles pour les SuiteDeTableaux dans un tableau
 */
function getSuiteDeTableauxOptions($xml)
{
    $options = array();
    $phases = getAllPhases($xml);
    
    foreach ($phases as $phase) {
        if ($phase->localName == 'PhaseDeTableaux') {
            foreach ($phase->getElementsByTagName('SuiteDeTableaux') as $sdt) {
                $id = $sdt->getAttribute('ID');
                $titre = $sdt->getAttribute('Titre');
                $nbTableaux = $sdt->getAttribute('NbDeTableaux');
                
                $options[] = array(
                    'id' => $id,
                    'titre' => $titre,
                    'nbTableaux' => $nbTableaux
                );
            }
        }
    }
    
    return $options;
}