<?php

function selectPays()
{
    $r = "";
    $lst = listePays();
    foreach ($lst as $code => $pays)
    {
        $r .= "<option value='$code'>$pays</option>\n";
    }
    return $r;
}

function flag_icon($code, $size = '')
{
    if (empty($code)) {
        return '';
    }
    
    $code = strtoupper(trim($code));
    
    // Map regional codes to country codes
    $regionMapping = [
        'IDF' => 'FRA', // Île-de-France => France
        'ALS' => 'FRA', // Alsace => France
        'FRC' => 'FRA', // Franche-Comté => France
        'PDL' => 'FRA', // Pays de Loire => France
        'NOR' => 'FRA', // Normandie => France 
        'BRE' => 'FRA'  // Bretagne => France
    ];
    
    if (isset($regionMapping[$code])) {
        $code = $regionMapping[$code];
    }
    
    // Map size parameter to CSS classes
    $flagClass = 'flag-icon';
    if ($size === 'small') {
        $flagClass .= ' flag-icon-small';
    } else if ($size === 'huge') {
        $flagClass .= ' flag-icon-large';
    }
    
    if (strpos($size, 'podium') !== false) {
        $flagClass .= ' ' . $size;
    }
    
    // Always use SVG files
    $flagUrl = "flags/$code.svg";
    
    return "<img src='$flagUrl' class='$flagClass' alt='$code'>";
}

function listePays()
{
    return array(
''    => '- vide -',
'AFG' => 'AFG Afghanistan',
'ALB' => 'ALB Albanie',
'ALG' => 'ALG Algérie',
'AND' => 'AND Andorre',
'ANG' => 'ANG Angola',
'ANT' => 'ANT Antigua-et-Barbuda',
'ARG' => 'ARG Argentine',
'ARM' => 'ARM Arménie',
'ARU' => 'ARU Aruba',
'ASA' => 'ASA Samoa américaines',
'AUS' => 'AUS Australie',
'AUT' => 'AUT Autriche',
'AZE' => 'AZE Azerbaïdjan',
'BAH' => 'BAH Bahamas',
'BAN' => 'BAN Bangladesh',
'BAR' => 'BAR Barbade',
'BDI' => 'BDI Burundi',
'BEL' => 'BEL Belgique',
'BEN' => 'BEN Bénin',
'BER' => 'BER Bermudes',
'BHU' => 'BHU Bhoutan',
'BIH' => 'BIH Bosnie-Herzégovine',
'BIZ' => 'BIZ Belize',
'BLR' => 'BLR Biélorussie',
'BOL' => 'BOL Bolivie',
'BOT' => 'BOT Botswana',
'BRA' => 'BRA Brésil',
'BRN' => 'BRN Bahreïn',
'BRU' => 'BRU Brunei',
'BUL' => 'BUL Bulgarie',
'BUR' => 'BUR Burkina Faso',
'CAF' => 'CAF République centrafricaine',
'CAM' => 'CAM Cambodge',
'CAN' => 'CAN Canada',
'CAY' => 'CAY Îles Caïmans',
'CGO' => 'CGO République du Congo',
'CHA' => 'CHA Tchad',
'CHI' => 'CHI Chili',
'CHN' => 'CHN Chine',
'CIV' => 'CIV Côte d\'Ivoire',
'CMR' => 'CMR Cameroun',
'COD' => 'COD République démocratique du Congo',
'COK' => 'COK Îles Cook',
'COL' => 'COL Colombie',
'COM' => 'COM Comores',
'COR' => 'COR Corée unifiée',
'CPV' => 'CPV Cap-Vert',
'CRC' => 'CRC Costa Rica',
'CRO' => 'CRO Croatie',
'CUB' => 'CUB Cuba',
'CYP' => 'CYP Chypre',
'CZE' => 'CZE République tchèque',
'DEN' => 'DEN Danemark',
'DJI' => 'DJI Djibouti',
'DMA' => 'DMA Dominique',
'DOM' => 'DOM République dominicaine',
'ECU' => 'ECU Équateur',
'EGY' => 'EGY Égypte',
'ERI' => 'ERI Érythrée',
'ESA' => 'ESA Salvador',
'ESP' => 'ESP Espagne',
'EST' => 'EST Estonie',
'ETH' => 'ETH Éthiopie',
'FIJ' => 'FIJ Fidji',
'FIN' => 'FIN Finlande',
'FRA' => 'FRA France',
'FSM' => 'FSM États fédérés de Micronésie',
'GAB' => 'GAB Gabon',
'GAM' => 'GAM Gambie',
'GBR' => 'GBR Royaume-Uni',
'GBS' => 'GBS Guinée-Bissau',
'GEO' => 'GEO Géorgie',
'GEQ' => 'GEQ Guinée équatoriale',
'GER' => 'GER Allemagne',
'GHA' => 'GHA Ghana',
'GRE' => 'GRE Grèce',
'GRN' => 'GRN Grenade',
'GUA' => 'GUA Guatemala',
'GUI' => 'GUI Guinée',
'GUM' => 'GUM Guam',
'GUY' => 'GUY Guyana',
'HAI' => 'HAI Haïti',
'HKG' => 'HKG Hong Kong',
'HON' => 'HON Honduras',
'HUN' => 'HUN Hongrie',
'INA' => 'INA Indonésie',
'IND' => 'IND Inde',
'IRI' => 'IRI Iran',
'IRL' => 'IRL Irlande',
'IRQ' => 'IRQ Irak',
'ISL' => 'ISL Islande',
'ISR' => 'ISR Israël',
'ISV' => 'ISV Îles Vierges des États-Unis',
'ITA' => 'ITA Italie',
'IVB' => 'IVB Îles Vierges britanniques',
'JAM' => 'JAM Jamaïque',
'JOR' => 'JOR Jordanie',
'JPN' => 'JPN Japon',
'KAZ' => 'KAZ Kazakhstan',
'KEN' => 'KEN Kenya',
'KGZ' => 'KGZ Kirghizistan',
'KIR' => 'KIR Kiribati',
'KOS' => 'KOS Kosovo',
'KOR' => 'KOR Corée du Sud',
'KSA' => 'KSA Arabie saoudite',
'KUW' => 'KUW Koweït',
'LAO' => 'LAO Laos',
'LAT' => 'LAT Lettonie',
'LBA' => 'LBA Libye',
'LBN' => 'LBN Liban',
'LBR' => 'LBR Liberia',
'LCA' => 'LCA Sainte-Lucie',
'LES' => 'LES Lesotho',
'LIE' => 'LIE Liechtenstein',
'LTU' => 'LTU Lituanie',
'LUX' => 'LUX Luxembourg',
'MAD' => 'MAD Madagascar',
'MAR' => 'MAR Maroc',
'MAS' => 'MAS Malaisie',
'MAW' => 'MAW Malawi',
'MDA' => 'MDA Moldavie',
'MDV' => 'MDV Maldives',
'MEX' => 'MEX Mexique',
'MGL' => 'MGL Mongolie',
'MHL' => 'MHL Îles Marshall',
'MKD' => 'MKD Macédoine du Nord',
'MLI' => 'MLI Mali',
'MLT' => 'MLT Malte',
'MNE' => 'MNE Monténégro',
'MON' => 'MON Monaco',
'MOZ' => 'MOZ Mozambique',
'MRI' => 'MRI Maurice',
'MTN' => 'MTN Mauritanie',
'MYA' => 'MYA Birmanie',
'NAM' => 'NAM Namibie',
'NCA' => 'NCA Nicaragua',
'NED' => 'NED Pays-Bas',
'NEP' => 'NEP Népal',
'NGR' => 'NGR Nigeria',
'NIG' => 'NIG Niger',
'NOR' => 'NOR Norvège',
'NRU' => 'NRU Nauru',
'NZL' => 'NZL Nouvelle-Zélande',
'OMA' => 'OMA Oman',
'PAK' => 'PAK Pakistan',
'PAN' => 'PAN Panama',
'PAR' => 'PAR Paraguay',
'PER' => 'PER Pérou',
'PHI' => 'PHI Philippines',
'PLE' => 'PLE Palestine',
'PLW' => 'PLW Palaos',
'PNG' => 'PNG Papouasie-Nouvelle-Guinée',
'POL' => 'POL Pologne',
'POR' => 'POR Portugal',
'PRK' => 'PRK Corée du Nord',
'PUR' => 'PUR Porto Rico',
'QAT' => 'QAT Qatar',
'ROU' => 'ROU Roumanie',
'RSA' => 'RSA Afrique du Sud',
'RUS' => 'RUS Russie',
'RWA' => 'RWA Rwanda',
'SAM' => 'SAM Samoa',
'SEN' => 'SEN Sénégal',
'SEY' => 'SEY Seychelles',
'SIN' => 'SIN Singapour',
'SKN' => 'SKN Saint-Christophe-et-Niévès',
'SLE' => 'SLE Sierra Leone',
'SLO' => 'SLO Slovénie',
'SMR' => 'SMR Saint-Marin',
'SOL' => 'SOL Salomon',
'SOM' => 'SOM Somalie',
'SRB' => 'SRB Serbie',
'SRI' => 'SRI Sri Lanka',
'SSD' => 'SSD Soudan du Sud',
'STP' => 'STP Sao Tomé-et-Principe',
'SUD' => 'SUD Soudan',
'SUI' => 'SUI Suisse',
'SUR' => 'SUR Suriname',
'SVK' => 'SVK Slovaquie',
'SWE' => 'SWE Suède',
'SWZ' => 'SWZ Swaziland',
'SYR' => 'SYR Syrie',
'TAN' => 'TAN Tanzanie',
'TGA' => 'TGA Tonga',
'THA' => 'THA Thaïlande',
'TJK' => 'TJK Tadjikistan',
'TKM' => 'TKM Turkménistan',
'TLS' => 'TLS Timor oriental',
'TOG' => 'TOG Togo',
'TPE' => 'TPE Taipei chinois',
'TTO' => 'TTO Trinité-et-Tobago',
'TUN' => 'TUN Tunisie',
'TUR' => 'TUR Turquie',
'TUV' => 'TUV Tuvalu',
'UAE' => 'UAE Émirats arabes unis',
'UGA' => 'UGA Ouganda',
'UKR' => 'UKR Ukraine',
'URU' => 'URU Uruguay',
'USA' => 'USA États-Unis',
'UZB' => 'UZB Ouzbékistan',
'VAN' => 'VAN Vanuatu',
'VEN' => 'VEN Venezuela',
'VIE' => 'VIE Viêt Nam',
'VIN' => 'VIN Saint-Vincent-et-les-Grenadines',
'YEM' => 'YEM Yémen',
'ZAM' => 'ZAM Zambie',
'ZIM' => 'ZIM Zimbabwe');

}