<?php
/**
 * app.php
 * Interface permettant à l'administrateur la configuration de certains paramètres généraux
 * Ce script fait partie de l'application GRR.
 * Dernière modification : $Date: 2021-03-18 15:30$
 * @author    JeromeB
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

 $niveauDossier = 1;

require './vendor/autoload.php';
require './include/twiggrr.class.php';

//GRR
require "./include/functions.inc.php";

$page = 'login';
if(isset($_GET['p'])){
	$page = alphanum($_GET['p']);
}

include "./personnalisation/connect.inc.php";
include "./include/config.inc.php";
include "./include/misc.inc.php";
include "./include/$dbsys.inc.php";
include "./include/mincals.inc.php"; // JeromeB :Pas besoin partout le laisser ici ? 
include "./include/mrbs_sql.inc.php";

require_once("./include/settings.class.php");
if (!Settings::load())
	die("Erreur chargement settings");
require_once("./include/session.inc.php");
include "./include/language.inc.php";

if($page != "deconnexion")
	grr_resumeSession();

// pour le traitement des modules
include "./include/hook.class.php";

$trad = array();
$back = '';

if (isset($_SERVER['HTTP_REFERER']))
	$back = htmlspecialchars($_SERVER['HTTP_REFERER']);
	
/*if ((authGetUserLevel(getUserName(), -1, 'area') < 4) && (authGetUserLevel(getUserName(), -1, 'user') !=  1))
{
	showAccessDenied($back);
	exit();
}
*/

if(getUserName() != '')
	$userConnecte = "with_session";
else
	$userConnecte = "no_session";

print_header_twig("", "", "", $userConnecte);

$day = isset($_POST['day']) ? $_POST['day'] : (isset($_GET['day']) ? intval($_GET['day']) : date('d'));
$month = isset($_POST['month']) ? $_POST['month'] : (isset($_GET['month']) ? intval($_GET['month']) : date('m'));
$year = isset($_POST['year']) ? $_POST['year'] : (isset($_GET['year']) ? intval($_GET['year']) : date('Y'));

$d['gDay'] = $day;
$d['gMonth'] = $month;
$d['gYear'] = $year;

$d['levelUser'] = authGetUserLevel(getUserName(),-1);
$d['accesStats'] = verif_access_search(getUserName());
$AllSettings = Settings::getAll();

$d['gNomUser'] = getUserName();
$d['versionGRR'] = $version_grr;

get_vocab_admin("admin");
get_vocab_admin("manage_my_account");
get_vocab_admin("report");
get_vocab_admin("reserver");

// Template Twig
$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/reservation/templates');
$twig = new \Twig\Environment($loader,['charset']);
$twig->addExtension(new TwigGRR());


// Sécurité
$listeFichiers = array();
$dossierLister = new DirectoryIterator("./reservation/controleurs/");
foreach ($dossierLister as $fileinfo) {
	if($fileinfo->isFile() && $fileinfo->getExtension() == "php") {
		$listeFichiers[] = $fileinfo->getFilename();
	}
}

if(in_array($page.".php",$listeFichiers))
	include('./reservation/controleurs/'.$page.'.php');
else
	include('./reservation/controleurs/index.php');


?>