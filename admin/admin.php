<?php
/**
 * admin.php
 * Interface permettant à l'administrateur la configuration de certains paramètres généraux
 * Ce script fait partie de l'application GRR.
 * Dernière modification : $Date: 2026-05-11 15:00$
 * @author    JeromeB
 * @copyright Since 2003 Team DEVOME - JeromeB
 * @link      http://www.gnu.org/licenses/licenses.html
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 */

$administration = true;
$niveauDossier = 2;

require '../vendor/autoload.php';
require '../include/twiggrr.class.php';

// GRR
require "../include/securite.class.php";
require "../include/functions.inc.php";

$page = 'admin_accueil';
if(isset($_GET['p'])){
	$page = SecuChaine::Alphanumeric($_GET['p']);
}


include "../include/admin.inc.php";
include "../include/hook.class.php";
include "./modeles/AdminFonctions.php";

$room = isset($_GET['room']) ? SecuChaine::Alphanumeric($_GET['room']) : -1;

$back = '';
if (isset($_SERVER['HTTP_REFERER']))
	$back = htmlspecialchars($_SERVER['HTTP_REFERER']);

$acces_config_ress_level = (Settings::get('acces_config'))? Settings::get('acces_config') : 3;
if (	(SecuAccess::UserLevel(getUserName(), -1, 'area') < 4) && 
		(SecuAccess::UserLevel(getUserName(), -1, 'user') !=  1) && 
		( $page <> 'admin_edit_room' ) || (SecuAccess::UserLevel(getUserName(), $room) < $acces_config_ress_level) && ($room != -1)
	)
{
	showAccessDenied($back);
	exit();
}

// If we dont know the right date then make it up
if (!isset($day) || !isset($month) || !isset($year) || ($day == '') || ($month == '') || ($year == ''))
{
	$date_now = time();
	if ($date_now < Settings::get("begin_bookings"))
		$date_ = Settings::get("begin_bookings");
	else if ($date_now > Settings::get("end_bookings"))
		$date_ = Settings::get("end_bookings");
	else
		$date_ = $date_now;
	$day   = date("d",$date_);
	$month = date("m",$date_);
	$year  = date("Y",$date_);
}

// On fabrique une date valide pour la réservation si ce n'est pas le cas
$date_ = mktime(0, 0, 0, $month, $day, $year);
if ($date_ < Settings::get("begin_bookings"))
	$date_ = Settings::get("begin_bookings");
else if ($date_ > Settings::get("end_bookings"))
	$date_ = Settings::get("end_bookings");
$day   = date("d",$date_);
$month = date("m",$date_);
$year  = date("Y",$date_);


get_vocab_admin('admin');
get_vocab_admin('grr_version');
get_vocab_admin('retour_planning');
get_vocab_admin("manage_my_account");
get_vocab_admin("display_add_user");
get_vocab_admin('admin_view_connexions');

$d = array();
$d['version']		= $version_grr;
$d['versionCache']	= hash('sha256', $version_grr.Settings::get("tokenpublic"));
$d['nomAffichage']	= htmlspecialchars($_SESSION['prenom']).' '.htmlspecialchars($_SESSION['nom']);;
$d['lienRetour']	= "../".page_accueil('yes')."day=".$day."&year=".$year."&month=".$month;
$d['lienCompte']	= "../compte/compte.php?day=".$day."&year=".$year."&month=".$month;
$d['nomUtilisateur'] = getUserName();
$AllSettings = Settings::getAll();

// Template Twig
$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/templates');
$twig = new \Twig\Environment($loader,['charset']);
$twig->addExtension(new TwigGRR());

// Menu administrateur
include "admin_col_gauche.php";

// Sécurité
$listeFichiers = array();
$dossierLister = new DirectoryIterator("controleurs/");
foreach ($dossierLister as $fileinfo) {
	if($fileinfo->isFile() && $fileinfo->getExtension() == "php") {
		$listeFichiers[] = $fileinfo->getFilename();
	}
}

if(in_array($page.".php",$listeFichiers))
	include('controleurs/'.$page.'.php');
else
	include('controleurs/index.php');


?>