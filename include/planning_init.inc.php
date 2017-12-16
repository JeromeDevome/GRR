<?php
/**
 * planning_init.inc.php
 * Permet l'affichage de la page d'accueil lorsque l'on est en mode d'affichage "semaine".
 * Dernière modification : $Date: 2017-12-16 14:00$
 * @author    JeromeB
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


include "include/connect.inc.php";
include "include/config.inc.php";
include "include/misc.inc.php";
include "include/functions.inc.php";
include "include/$dbsys.inc.php";
include "include/mincals.inc.php";
include "include/mrbs_sql.inc.php";
require_once("./include/settings.class.php");
$settings = new Settings();
if (!$settings)
	die("Erreur chargement settings");
require_once("./include/session.inc.php");
include "include/resume_session.php";
include "include/language.inc.php";
include "include/setdate.php";

//Construction des identifiants de la ressource $room, du domaine $area, du site $id_site
Definition_ressource_domaine_site();

//Récupération des données concernant l'affichage du planning du domaine
get_planning_area_values($area);

// Initilisation des variables
$affiche_pview = '1';
if (!isset($_GET['pview']))
	$_GET['pview'] = 0;
else
	$_GET['pview'] = 1;

if ($_GET['pview'] == 1)
	$class_image = "print_image";
else
	$class_image = "image";

if (empty($month) || empty($year) || !checkdate($month, 1, $year))
{
	$month = date("m");
	$year  = date("Y");
}

if (!isset($day))
	$day = 1;

// Lien de retour
$back = '';
if (isset($_SERVER['HTTP_REFERER']))
	$back = htmlspecialchars($_SERVER['HTTP_REFERER']);

// Type de session
if ((Settings::get("authentification_obli") == 0) && (getUserName() == ''))
	$type_session = "no_session";
else
	$type_session = "with_session";

// Menu du haut
print_header($day, $month, $year, $type_session);

// Debut de la page
echo '<div class="row">'.PHP_EOL;

// Affichage du menu
include("menu_gauche.php");
include("chargement.php");

// Dans le cas d'une selection invalide
if ($area <= 0)
{
	echo '<h1>'.get_vocab("noareas").'</h1>';
	echo '<a href="./admin/admin_accueil.php">'.get_vocab("admin").'</a>'.PHP_EOL.'</body>'.PHP_EOL.'</html>';
	exit();
}


// Calcul du niverau de droit de réservation
$authGetUserLevel			= authGetUserLevel(getUserName(), -1);

//Renseigne les droits de l'utilisateur, si les droits sont insufisants, l'utilisateur est averti.
if (check_begin_end_bookings($day, $month, $year))
{
	showNoBookings($day, $month, $year, $back);
	exit();
}

if ((($authGetUserLevel < 1) && (Settings::get("authentification_obli") == 1)) || authUserAccesArea(getUserName(), $area) == 0)
{
	showAccessDenied($back);
	exit();
}

// On vérifie une fois par jour si le délai de confirmation des réservations est dépassé
	// Si oui, les réservations concernées sont supprimées et un mail automatique est envoyé.
	// On vérifie une fois par jour que les ressources ont été rendue en fin de réservation
	// Si non, une notification email est envoyée
if (Settings::get("verif_reservation_auto") == 0)
{
	verify_confirm_reservation();
	verify_retard_reservation();
}

// Dans le cas de l'affichage d'une unique ressource
if(isset($_GET['room']))
{
	// Calcul du niveau d'accès aux fiche de réservation détaillées des ressources
	$acces_fiche_reservation	= verif_acces_fiche_reservation(getUserName(), $room);
	// Calcul du test si l'utilisateur a la possibilité d'effectuer une réservation, compte tenu des limitations éventuelles de la ressources et du nombre de réservations déjà effectuées.
	$UserRoomMaxBooking			= UserRoomMaxBooking(getUserName(), $room, 1);
	// Determine si un visiteur peut réserver une ressource
	$auth_visiteur				= auth_visiteur(getUserName(), $room);
	// Calcul de l'accès à la ressource en fonction du niveau de l'utilisateur
	$verif_acces_ressource		= verif_acces_ressource(getUserName(), $room);

	if (!$verif_acces_ressource)
	{
		showAccessDenied($back);
		exit();
	}
}

// Selection des ressources
$sql = "SELECT room_name, capacity, id, description, statut_room, show_fic_room, delais_option_reservation, moderate FROM ".TABLE_PREFIX."_room WHERE area_id='".$area."' ORDER BY order_display, room_name";
$ressources = grr_sql_query($sql);

if (!$ressources)
	fatal_error(0, grr_sql_error());

// Contrôle si il y a une ressource dans le domaine
if (grr_sql_count($ressources) == 0)
{
	echo "<h1>".get_vocab("no_rooms_for_area")."</h1>";
	exit;
}

// Page
if ($_GET['pview'] != 1){
		if(Settings::get("menu_gauche") == 0 || Settings::get("menu_gauche") == 2){
			echo '<div class="col-lg-12 col-md-12 col-xs-12">'.PHP_EOL;
		} else{
			echo '<div class="col-lg-9 col-md-12 col-xs-12">'.PHP_EOL;
		}
	echo '<div id="planning">'.PHP_EOL;
}else{
	echo '<div id="print_planning">'.PHP_EOL;
}

?>