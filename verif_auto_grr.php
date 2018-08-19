<?php
/**
 * verif_auto_grr.php
 * Exécution de taches automatiques
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2018-08-18 18:00$
 * @author    Laurent Delineau & JeromeB & Yan Naessens
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
$grr_script_name = "verif_auto_grr.php";

$titre = "GRR - Ex&eacute;cution de t&acirc;ches automatiques";
include "include/connect.inc.php";
include "include/config.inc.php";
include "include/misc.inc.php";
include "include/functions.inc.php";
include "include/$dbsys.inc.php";
include "include/mrbs_sql.inc.php";

include("include/settings.class.php");
if (!Settings::load())
	die("Erreur chargement settings");
if ((!isset($_GET['mdp'])) && (!isset($argv[1])))
{
	echo "Il manque des arguments pour exécuter ce script. Reportez-vous à la documentation.";
	die();
}
// Début du script
if (isset($argv[1]))
{
	DEFINE("CHEMIN_COMPLET_GRR",Settings::get("chemin_complet_grr"));
	chdir(CHEMIN_COMPLET_GRR);
}
include "include/language.inc.php";
if (!isset($_GET['mdp']))
	$_GET['mdp'] = $argv[1];
if ((!isset($_GET['mdp'])) || ($_GET['mdp'] != Settings::get("motdepasse_verif_auto_grr")) || (Settings::get("motdepasse_verif_auto_grr") == ''))
{
	if (!isset($argv[1]))
		echo start_page_wo_header($titre, $page = "no_session")."<p>";
	echo "Le mot de passe fourni est invalide.";
	if (!isset($argv[1]))
	{
		echo "</p>";
		//include "include/trailer.inc.php";
        end_page();
	}
	die();
}
if (!isset($argv[1]))
	echo start_page_wo_header($titre,$page = "no_session");
// On vérifie une fois par jour si le délai de confirmation des réservations est dépassé
// Si oui, les réservations concernées sont supprimées et un mail automatique est envoyé.
verify_confirm_reservation();
// On vérifie une fois par jour que les ressources ont été rendues en fin de réservation
// Si non, une notification email est envoyée
verify_retard_reservation();
if (!isset($argv[1]))
{
	echo "<p>Le script a été exécuté.</p>";
	//include "include/trailer.inc.php";
    end_page();
}
else
	echo "Le script a été exécuté.";
?>
