<?php
/**
 * admin_maj.php
 * interface permettant la mise à jour de la base de données
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2018-04-11 11:30$
 * @author    JeromeB & Laurent Delineau & Yan Naessens
 * @author    Arnaud Fornerot pour l'intégation au portail Envole http://ent-envole.com/
 * @copyright Copyright 2003-2018 Team DEVOME - JeromeB
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
if ($version_old == "")
	$version_old = "1.3";

// Numéro de RC
$version_old_RC = Settings::get("versionRC");

// Calcul du numéro de version actuel de la base qui sert aux test de comparaison et de la chaine à afficher
if ($version_old_RC == "")
{
	$version_old_RC = 9;
	$display_version_old = $version_old;
}
else
	$display_version_old = $version_old."_RC".$version_old_RC;

$version_old .= ".".$version_old_RC;

// Calcul de la chaine à afficher
if ($version_grr_RC == "")
	$display_version_grr = $version_grr.$sous_version_grr;
else
	$display_version_grr = $version_grr." RC".$version_grr_RC;


/* GRR */
get_vocab_admin("num_version");
$trad['dNum_version'] = $display_version_grr;
get_vocab_admin("num_versionbdd");
$trad['dNum_versionbdd'] = $display_version_old;
get_vocab_admin("prefixe");
$trad['dPrefixe'] = TABLE_PREFIX;
get_vocab_admin("maj_bdd");
if (verif_version())
	$trad['dMaj_bdd'] = "<a href=\"?p=admin_maj\"><span class=\"label label-danger\">".get_vocab("maj_bdd_not_update")." Cliquez ici pour la mettre à jour.</span></a>";
else
	$trad['dMaj_bdd'] = "<span class=\"label label-success\">Aucune</span>";
get_vocab_admin("maj_recherche_grr");

/* Serveur */
get_vocab_admin("system");
$trad['dSystem'] = php_uname();
$trad['dVersionPHP'] = phpversion();
get_vocab_admin("database");
$trad['dDatabase'] = $dbsys;
$trad['dVersionBDD'] = grr_sql_version();
$trad['dTime'] = time();
$trad['dDate'] = date('d-m-Y');
$trad['dHeure'] = date("H:i");
$trad['dTimezone'] = date_default_timezone_get();




// Recherche mise à jour sur serveur GRR
if($recherche_MAJ == 1)
{
	$fichier = $grr_devel_url.'versiongrr.xml';
	
	if (!$fp = @fopen($fichier,"r")) {
		$trad['dMaj_SiteGRR'] = "<span class=\"label label-info\">".get_vocab("maj_impossible_rechercher")."</span>". get_vocab("maj_go_www")."<a href=\"".$grr_devel_url."\">".get_vocab("mrbs")."</a>";
	} else{
		$reader = new XMLReader();
		$reader->open($fichier);

		while ($reader->read()) {
			if ($reader->nodeType == XMLREADER::ELEMENT){
				if ($reader->name == "numero"){
					$reader->read();
					$derniereVersion = $reader->value;
				}
				if ($reader->name == "sousversion"){
					$reader->read();
					$derniereSousVersion = $reader->value;
				}
				if ($reader->name == "rc"){
					$reader->read();
					$derniereRC = $reader->value;
				}
			}
		}

		if($version_grr != $derniereVersion || $sous_version_grr != $derniereSousVersion || $version_grr_RC != $derniereRC){
			if($derniereRC <> ""){
				$derniereRC = " RC ".$derniereRC;
			}
			$trad['dMaj_SiteGRR'] = "<span class=\"label label-warning\">".get_vocab("maj_dispo")."</span>";
		} else{
			$trad['dMaj_SiteGRR'] = "<span class=\"label label-success\">".get_vocab("maj_dispo_aucune")."</span>";
		}

		$reader->close();
	}
} elseif(!$majscript) {
	$trad['dMaj_SiteGRR'] = "<span class=\"label label-info\">".get_vocab("maj_impossible_rechercher")."</span>". get_vocab("maj_go_www")."<a href=\"".$grr_devel_url."\">".get_vocab("mrbs")."</a>";
}

?>