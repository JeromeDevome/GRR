<?php
/**
 * admin.php
 * Interface permettant à l'administrateur la configuration de certains paramètres généraux
 * Ce script fait partie de l'application GRR.
 * Dernière modification : $Date: 2024-07-15 11:00$
 * @author    JeromeB
 * @copyright Copyright 2003-2024 Team DEVOME - JeromeB
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
require "../include/functions.inc.php";

$page = 'admin_accueil';
if(isset($_GET['p'])){
	$page = alphanum($_GET['p']);
}


include "../include/admin.inc.php";
include "../include/mdp_faciles.inc.php";
include "../include/hook.class.php";
include "./modeles/AdminFonctions.php";

$back = '';
if (isset($_SERVER['HTTP_REFERER']))
	$back = htmlspecialchars($_SERVER['HTTP_REFERER']);
if ((authGetUserLevel(getUserName(), -1, 'area') < 4)&&(authGetUserLevel(getUserName(),-1,'room') < 3) && (authGetUserLevel(getUserName(), -1, 'user') !=  1))
{
	showAccessDenied($back."44");
	exit();
}
print_header_admin("", "", "", $type="with_session");

get_vocab_admin('admin');
get_vocab_admin('grr_version');
get_vocab_admin('retour_planning');
get_vocab_admin("manage_my_account");
get_vocab_admin("display_add_user");
get_vocab_admin('admin_view_connexions');

$d = array();
$d['version'] = $version_grr;
$d['nomAffichage'] = $nomAffichage;
$d['lienRetour'] = $lienRetour;
$d['lienCompte'] = $lienCompte;
$d['nomUtilisateur'] = getUserName();
$AllSettings = Settings::getAll();

// Template Twig
$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/templates');
$twig = new \Twig\Environment($loader,['charset']);
$twig->addExtension(new TwigGRR());

// Menu GRR
$menuAdminT = array();
$menuAdminTN2 = array();
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


if($page === 'admin_change_date_bookings' || $page === 'admin_open_mysql'){ // Config Général => Contenu (Modification Dates) && Config Général => Sécurité (Restauration sauvegarde)
	echo $twig->render($page.'.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings));
} elseif($page === 'admin_type'){
	echo $twig->render($page.'.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings, 'types' => $typesResa, 'listeManquant' => $listeManquant));
} elseif($page === 'admin_type_modify'){
	echo $twig->render($page.'.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings, 'type' => $typeResa, 'lettres' => $lettres));
} elseif($page === 'admin_user'){
	echo $twig->render($page.'.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings, 'utilisateurs' => $col));
}

?>