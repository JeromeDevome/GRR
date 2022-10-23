<?php
/**
 * installation/maj.php
 * interface permettant la mise à jour de la base de données
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2021-05-31 18:26$
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

$grr_script_name = "maj.php";

require_once("../personnalisation/connect.inc.php");
require_once("../include/config.inc.php");
require_once("../include/misc.inc.php");
require_once("../include/functions.inc.php");
require_once("../include/$dbsys.inc.php");
require_once("../include/settings.class.php");
include("../include/language.inc.php");
include("./fonctions/maj.php");

//Chargement des valeurs de la table settings
$settings = new Settings();
if (!$settings)
	die("Erreur chargement settings");


$valid		= isset($_POST["valid"]) ? $_POST["valid"] : 'no';
$majscript	= false;
$force		= false;

// Définition depuis quelle version on met à jour
if (isset($_POST["version_depart"])) // Etape 2
{
	$version_depart = $_POST["version_depart"];
}
elseif (isset($_GET["forcemaj"]) && $forcer_MAJ == 1) // On force la MaJ en passant le numéro de la version de dépar en paramètre  
{
	$version_depart	= $_GET["forcemaj"];
	$force			= true;
}
else // On prend la dernière version installé
{
	$version_depart = Settings::get("version");
}

if ($version_depart == "")
	$version_depart = "1.3";

// Formulaire avant MaJ
if(!$majscript)
{
	echo '<!doctype html>';
	echo '<html>';
	echo '<head>';
	echo '<meta http-equiv="content-type" content="text/html; charset=';
	if ($unicode_encoding)
		echo "utf-8";
	else
		echo $charset_html;

	echo '<link rel="stylesheet" href="../themes/default/css/style.css" type="text/css">';
	echo '<link rel="shortcut icon" href="favicon.ico">';
	echo '<title>GRR</title>';
	echo '</head>';
	echo '<body>';

	// Mise à jour de la base de données
	echo "<h3>".get_vocab("maj_bdd")."</h3>";

	// Vérification du numéro de version
	if (verif_version() || $force == true)
	{
		echo "<form action=\"maj.php\" method=\"post\">";
		echo "<p><span style=\"color:red;\"><b>".get_vocab("maj_bdd_not_update");
		echo " ".get_vocab("maj_version_bdd").$version_depart;
		echo "</b></span><br />";
		echo get_vocab("maj_do_update")."<b>".$version_bdd."</b></p>";
		echo "<input type=\"submit\" value=\"".get_vocab("maj_submit_update")."\" />";
		echo "<input type=\"hidden\" name=\"maj\" value=\"yes\" />";
		echo "<input type=\"hidden\" name=\"version_depart\" value=\"$version_depart\" />";
		echo "<input type=\"hidden\" name=\"valid\" value=\"$valid\" />";
		echo "</form>";
	}
	else
	{
		echo "<p>".get_vocab("maj_no_update_to_do")."</p>";
		echo "<p>Forcer MaJ (ATTENTION) <a href=\"maj.php?forcemaj=".$version_depart."\">Cliquez ici</a></p>";
		echo "<p style=\"text-align:center;\"><a href=\"../\">".get_vocab("welcome")."</a></p>";
	}
}


// On effectue la MaJ
if (isset($_POST['maj']) || $majscript)
{
	//Re-Chargement des valeurs de la table settings
	if (!Settings::load())
		die("Erreur chargement settings");


	if (strpos($version_depart,".")){

		$result = execute_maj3($version_depart, $version_grr);

		echo "<h2>".encode_message_utf8("Résultat de la mise à jour < GRR 4")."</h2>";
		echo "<h3>".$version_depart." => 3.5.0</h3>";
		echo encode_message_utf8($result);
		$version_depart = 4000;
	}
	
	echo "<h2>".encode_message_utf8("Résultat de la mise à jour GRR 4 et +")."</h2>";
	echo "<h3>".$version_depart." => ".$version_bdd."</h3>";
	$result2 = execute_maj4($version_depart, $version_bdd);
	echo encode_message_utf8($result2);
}


// Test de cohérence des types de réservation
if (version_compare($version_grr, '1.9.1', '<'))
{
	$res = grr_sql_query("SELECT DISTINCT type FROM ".TABLE_PREFIX."_entry ORDER BY type");
	if ($res)
	{
		$liste = "";
		for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
		{
			$test = grr_sql_query1("SELECT type_letter FROM ".TABLE_PREFIX."_type_area WHERE type_letter='".$row[0]."'");
			if ($test == -1) $liste .= $row[0]." ";
		}
		if ($liste != "")
		{
			echo encode_message_utf8("<table border=\"1\" cellpadding=\"5\"><tr><td><p><span style=\"color:red;\"><b>ATTENTION : votre table des types de réservation n'est pas à jour :</b></span></p>");
			echo encode_message_utf8("<p>Depuis la version 1.9.2, les types de réservation ne sont plus définis dans le fichier config.inc.php
				mais directement en ligne. Un ou plusieurs types sont actuellement utilisés dans les réservations
				mais ne figurent pas dans la tables des types. Cela risque d'engendrer des messages d'erreur. <b>Il s'agit du ou des types suivants : ".$liste."</b>");
			echo encode_message_utf8("<br /><br />Vous devez donc définir dans <a href= './admin_type.php'>l'interface de gestion des types</a>, le ou les types manquants, en vous aidant éventuellement des informations figurant dans votre ancien fichier config.inc.php.</p></td></tr></table>");
		}
	}
}
?>