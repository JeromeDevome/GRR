<?php
/**
 * verif_auto_grr.php
 * Exécution de taches automatiques
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2009-10-09 07:55:48 $
 * @author    Laurent Delineau <laurent.delineau@ac-poitiers.fr>
 * @copyright Copyright 2003-2008 Laurent Delineau
 * @link      http://www.gnu.org/licenses/licenses.html
 * @package   root
 * @version   $Id: verif_auto_grr.php,v 1.5 2009-10-09 07:55:48 grr Exp $
 * @filesource
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GRR is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GRR; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */
$titre = "GRR - Ex&eacute;cution de t&acirc;ches automatiques";
include "include/connect.inc.php";
include "include/config.inc.php";
include "include/misc.inc.php";
include "include/functions.inc.php";
include "include/$dbsys.inc.php";
include "include/mrbs_sql.inc.php"; include 'include/twigInit.php';
$grr_script_name = "verif_auto_grr.php";
include("include/settings.class.php");
if (!Settings::load())
	die("Erreur chargement settings");
if ((!isset($_GET['mdp'])) && (!isset($argv[1])))
{
	echo "Il manque des arguments pour executer ce script. Reportez-vous a la documentation.";
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
		echo begin_page($titre, $page = "no_session")."<p>";
	echo "Le mot de passe fourni est invalide.";
	if (!isset($argv[1]))
	{
		echo "</p>";
		include "include/trailer.inc.php";
	}
	die();
}
if (!isset($argv[1]))
	echo begin_page($titre,$page = "no_session");
// On vérifie une fois par jour si le délai de confirmation des réservations est dépassé
// Si oui, les réservations concernées sont supprimées et un mail automatique est envoyé.
verify_confirm_reservation();
// On vérifie une fois par jour que les ressources ont été rendue en fin de réservation
// Si non, une notification email est envoyée
verify_retard_reservation();
if (!isset($argv[1]))
{
	echo "<p>Le script a été exécuté.</p>";
	include "include/trailer.inc.php";
}
else
	echo "Le script a ete execute.";
?>
