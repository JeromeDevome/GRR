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

get_vocab_admin('admin_infos');

if ((authGetUserLevel(getUserName(),-1) < 6) && ($valid != 'yes') && $connexionAdminMAJ == 1)
{
	showAccessDenied($back);
	exit();
}

$result = '';

// Numéro de version effective
$version_old = Settings::get("version");

/* GRR */
get_vocab_admin("num_version");
$trad['dNum_version'] = $version_grr." ".$versionReposite;
get_vocab_admin("num_versionbdd");
$trad['dNum_versionbdd'] = $version_old;
get_vocab_admin("prefixe");
$trad['dPrefixe'] = TABLE_PREFIX;
get_vocab_admin("maj_bdd");
if (verif_version())
	$trad['dMaj_bdd'] = "<a href=\"../installation/maj.php\" target=\"blank\"><span class=\"label label-danger\">".get_vocab("maj_bdd_not_update")." Cliquez ici pour la mettre à jour.</span></a>";
else
	$trad['dMaj_bdd'] = "<span class=\"label label-success\">Aucune</span>";
get_vocab_admin("maj_recherche_grr");

/* Serveur */
get_vocab_admin("system");
$trad['dSystem'] = php_uname();

// PHP
$trad['dVersionPHP'] = phpversion();

if (version_compare(phpversion(), $php_mini, '<')) {
   $trad['dCouleurVersionPHP'] = "bg-red";
} elseif (version_compare(phpversion(), $php_max_valide, '<=')) {
   $trad['dCouleurVersionPHP'] = "bg-green";
} elseif ($php_maxi == "" && version_compare(phpversion(), $php_max_valide, '>')) {
   $trad['dCouleurVersionPHP'] = "bg-orange";
} elseif ($php_maxi != "" && version_compare(phpversion(), $php_maxi, '<=')) {
   $trad['dCouleurVersionPHP'] = "bg-orange";
} elseif ($php_maxi != "" && version_compare(phpversion(), $php_maxi, '>')) {
   $trad['dCouleurVersionPHP'] = "bg-red";
}


ob_start();
phpinfo();
$phpinfo = ob_get_clean();
ob_end_clean();
$phpinfo = preg_replace( '%^.*<body>(.*)</body>.*$%ms','$1',$phpinfo);

// BDD
get_vocab_admin("database");
$trad['dDatabase'] = $dbsys;
$trad['dVersionBDD'] = grr_sql_version();

if (version_compare(grr_sql_version(), $mysql_mini, '<')) {
   $trad['dCouleurVersionMySQL'] = "bg-red";
} elseif (version_compare(grr_sql_version(), $mysql_max_valide, '<=')) {
   $trad['dCouleurVersionMySQL'] = "bg-green";
} elseif ($mysql_maxi == "" && version_compare(grr_sql_version(), $mysql_max_valide, '>')) {
   $trad['dCouleurVersionMySQL'] = "bg-orange";
} elseif ($mysql_maxi != "" && version_compare(grr_sql_version(), $mysql_maxi, '<=')) {
   $trad['dCouleurVersionMySQL'] = "bg-orange";
} elseif ($mysql_maxi != "" && version_compare(grr_sql_version(), $mysql_maxi, '>')) {
   $trad['dCouleurVersionMySQL'] = "bg-red";
}


// Dossier
$trad['dDossierImgRessourcesEcriture'] = Fichier::TestDroitsDossier("../personnalisation/".$gcDossierImg."/ressources/");
$trad['dDossierImgLogosEcriture'] =  Fichier::TestDroitsDossier("../personnalisation/".$gcDossierImg."/logos/");
$trad['dDossierExportEcriture'] =  Fichier::TestDroitsDossier("../export/");
$trad['dDossierTempEcriture'] =  Fichier::TestDroitsDossier("../temp/");
$trad['dDossierModulesEcriture'] =  Fichier::TestDroitsDossier("../personnalisation/modules/");

if(file_exists('../installation/'))
	$trad['dDossierInstallation'] = 1;


// Temps
$trad['dTime'] = time();
$trad['dDate'] = date('d-m-Y');
$trad['dHeure'] = date("H:i");
$trad['dTimezone'] = date_default_timezone_get();


// Recherche mise à jour sur serveur GRR
if($recherche_MAJ == 1)
{
	$fichier = fopen($grr_devel_url.'versiongrr.txt',"rb");

	if ($fichier === FALSE) {
		$trad['dMaj_SiteGRR'] = "<span class=\"label label-info\">".get_vocab("maj_impossible_rechercher")."</span>". get_vocab("maj_go_www")."<a href=\"".$grr_devel_url."\">".get_vocab("mrbs")."</a>";
	} else{
		
		$derniereVersion = '';

		while (!feof($fichier)) {
			$derniereVersion .= fread($fichier, 8192);
		}
		fclose($fichier);

		if (version_compare($version_grr, $derniereVersion, '<')) {
			$trad['dMaj_SiteGRR'] = "<span class=\"label label-warning\">".get_vocab("maj_dispo")."</span>";
		} else{
			$trad['dMaj_SiteGRR'] = "<span class=\"label label-success\">".get_vocab("maj_dispo_aucune")."</span>";
		}
	}
} elseif(!$majscript) {
	$trad['dMaj_SiteGRR'] = "<span class=\"label label-info\">".get_vocab("maj_impossible_rechercher")."</span>". get_vocab("maj_go_www")."<a href=\"".$grr_devel_url."\">".get_vocab("mrbs")."</a>";
}

// Fichier configuration
$trad['dInfosConfigVar'] = '';
$trad['dInfosConfigDef'] = '';

foreach ($config_variables as $config){
	if(is_bool($$config) && $$config == true){
		$$config = "true";
	} elseif(is_bool($$config) && $$config == false){
		$$config = "false";
	}
	$trad['dInfosConfigVar'] .= "<li class=\"list-group-item\"><div class=\"row\"><p class=\"col-sm-6\">".$config."</p><b>".$$config."&nbsp;</b></div></li>"; // $$ Normal
}
unset($config);

	echo $twig->render('admin_infos.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings, 'infosPHP' => $phpinfo));
?>