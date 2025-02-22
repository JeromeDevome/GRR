<?php
/**
 * admin_maj.php
 * interface permettant la mise à jour de la base de données
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2018-04-11 11:30$
 * @author    JeromeB & Laurent Delineau & Yan Naessens
 * @author    Arnaud Fornerot pour l'intégation au portail Envole http://ent-envole.com/
 * @copyright Copyright 2003-2020 Team DEVOME - JeromeB
 * @link      http://www.gnu.org/licenses/licenses.html
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 */

$grr_script_name = "admin_infos.php";

include('../include/fichier.class.php');

$valid = isset($_POST["valid"]) ? $_POST["valid"] : 'no';
$version_old = isset($_POST["version_old"]) ? $_POST["version_old"] : '';

$trad = $vocab;

if ((authGetUserLevel(getUserName(),-1) < 6) && ($valid != 'yes') && $connexionAdminMAJ == 1)
{
	showAccessDenied($back);
	exit();
}

$result = '';

// Numéro de version effective
$version_old = Settings::get("version");

/* GRR */
$d['num_version'] = $version_grr." - ".$versionReposite;
$d['num_versionbdd'] = $version_old;
$d['prefixe'] = TABLE_PREFIX;

if (verif_version())
	$d['maj_bdd'] = "<a href=\"../installation/maj.php\" target=\"blank\"><span class=\"label label-danger\">".get_vocab("maj_bdd_not_update")." Cliquez ici pour la mettre à jour.</span></a>";
else
	$d['maj_bdd'] = "<span class=\"label label-success\">Aucune</span>";

/* Serveur */
$d['system'] = php_uname();

// PHP
$d['versionPHP'] = phpversion();

if (version_compare(phpversion(), $php_mini, '<')) {
   $d['couleurVersionPHP'] = "bg-red";
} elseif (version_compare(phpversion(), $php_max_valide, '<=')) {
   $d['couleurVersionPHP'] = "bg-green";
} elseif ($php_maxi == "" && version_compare(phpversion(), $php_max_valide, '>')) {
   $d['couleurVersionPHP'] = "bg-orange";
} elseif ($php_maxi != "" && version_compare(phpversion(), $php_maxi, '<=')) {
   $d['couleurVersionPHP'] = "bg-orange";
} elseif ($php_maxi != "" && version_compare(phpversion(), $php_maxi, '>')) {
   $d['couleurVersionPHP'] = "bg-red";
}

$d['phpfileinfo'] = extension_loaded("fileinfo");
$d['phpmbstring'] = extension_loaded("mbstring");
$d['phpmysqli'] = extension_loaded("mysqli");
$d['phpmysqlnd'] = extension_loaded("mysqlnd");
$d['phpxml'] = extension_loaded("xml");
$d['phpintl'] = extension_loaded("intl");
$d['phpgd'] = extension_loaded("gd");

ob_start();
phpinfo();
$phpinfo = ob_get_clean();
ob_end_clean();
$phpinfo = preg_replace( '%^.*<body>(.*)</body>.*$%ms','$1',$phpinfo);

// BDD
$d['database'] = $dbsys;
$d['versionBDD'] = grr_sql_version();

if (version_compare(grr_sql_version(), $mysql_mini, '<')) {
   $d['couleurVersionMySQL'] = "bg-red";
} elseif (version_compare(grr_sql_version(), $mysql_max_valide, '<=')) {
   $d['couleurVersionMySQL'] = "bg-green";
} elseif ($mysql_maxi == "" && version_compare(grr_sql_version(), $mysql_max_valide, '>')) {
   $d['couleurVersionMySQL'] = "bg-orange";
} elseif ($mysql_maxi != "" && version_compare(grr_sql_version(), $mysql_maxi, '<=')) {
   $d['couleurVersionMySQL'] = "bg-orange";
} elseif ($mysql_maxi != "" && version_compare(grr_sql_version(), $mysql_maxi, '>')) {
   $d['couleurVersionMySQL'] = "bg-red";
}


// Dossier
$d['dossierImgRessourcesEcriture'] = Fichier::TestDroitsDossier("../personnalisation/".$gcDossierImg."/ressources/");
$d['dossierImgLogosEcriture'] =  Fichier::TestDroitsDossier("../personnalisation/".$gcDossierImg."/logos/");
$d['dossierExportEcriture'] =  Fichier::TestDroitsDossier("../export/");
$d['dossierTempEcriture'] =  Fichier::TestDroitsDossier("../temp/");
$d['dossierModulesEcriture'] =  Fichier::TestDroitsDossier("../personnalisation/modules/");

if(file_exists('../installation/'))
	$d['dossierInstallation'] = 1;


// Temps
$d['time'] = time();
$d['date'] = date('d-m-Y');
$d['heure'] = date("H:i");
$d['timezone'] = date_default_timezone_get();


// Recherche mise à jour sur serveur GRR
if($recherche_MAJ == 1)
{

	$url = "https://grr.devome.com/API/majgrr.php";
	$opts = [
			'http' => [
					'method' => 'GET',
					'timeout' => 2,
					'header' => [
							'User-Agent: PHP'
					]
			]
	];
	
	$ctx = stream_context_create($opts);
	$json = @file_get_contents( $url, 0, $ctx );
	
	$myObj = json_decode($json);

	if($json === FALSE) {
		$d['maj_SiteGRR'] = "<span class=\"label label-info\">".get_vocab("maj_impossible_rechercher")."</span>". get_vocab("maj_go_www")."<a href=\"".$grr_devel_url."\">".get_vocab("mrbs")."</a>";
	} else{

		$derniereVersion = substr($myObj->tag_name,1);

		if (version_compare($version_grr, $derniereVersion, '<')) {
			$d['maj_SiteGRR'] = "<span class=\"label label-warning\">".get_vocab("maj_dispo")." : ".$myObj->tag_name." - ".$myObj->published_at."</span>";
		} else{
			$d['maj_SiteGRR'] = "<span class=\"label label-success\">".get_vocab("maj_dispo_aucune")."</span>";
		}
	}
} elseif(!$majscript) {
	$d['maj_SiteGRR'] = "<span class=\"label label-info\">".get_vocab("maj_impossible_rechercher")."</span>". get_vocab("maj_go_www")."<a href=\"".$grr_devel_url."\">".get_vocab("mrbs")."</a>";
}

// Fichier configuration
$d['infosConfigVar'] = '';
$d['infosConfigDef'] = '';

foreach ($config_variables as $config){
	if(is_bool($config) && $config == true){
		$config = "true";
	} elseif(is_bool($config) && $config == false){
		$config = "false";
	}
	$d['infosConfigVar'] .= "<li class=\"list-group-item\"><div class=\"row\"><p class=\col col-sm-6\">".$config."</p><b>".$$config."&nbsp;</b></div></li>"; // $$ Normal
}
unset($config);

	echo $twig->render('admin_infos.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings, 'infosPHP' => $phpinfo));
?>