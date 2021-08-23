<?php
/**
 * admin.php
 * Interface permettant à l'administrateur la configuration de certains paramètres généraux
 * Ce script fait partie de l'application GRR.
 * Dernière modification : $Date: 2021-08-23 09:53$
 * @author    JeromeB & Yan Naessens
 * @copyright Copyright 2003-2021 Team DEVOME - JeromeB
 * @link      http://www.gnu.org/licenses/licenses.html
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 */

require '../vendor/autoload.php';
require '../include/twiggrr.class.php';

$page = 'moncompte';
if(isset($_GET['p'])){
	$page = $_GET['p'];
}

// GRR
include "../include/admin.inc.php";
require_once('../include/session.inc.php');
include_once('../include/settings.class.php');
include_once('../include/hook.class.php');

$back = '';
if (isset($_SERVER['HTTP_REFERER']))
	$back = htmlspecialchars($_SERVER['HTTP_REFERER']);
/*if ((authGetUserLevel(getUserName(), -1, 'area') < 4) && (authGetUserLevel(getUserName(), -1, 'user') !=  1))
{
	showAccessDenied($back);
	exit();
}
*/
// a priori inutile, les droits d'accès dépendent d'autres fonctions et l'accès aux données est RGPD compliant

print_header_twig("", "", "", $type="with_session");

$day = isset($_POST['day']) ? $_POST['day'] : (isset($_GET['day']) ? $_GET['day'] : date('d'));
$month = isset($_POST['month']) ? $_POST['month'] : (isset($_GET['month']) ? $_GET['month'] : date('m'));
$year = isset($_POST['year']) ? $_POST['year'] : (isset($_GET['year']) ? $_GET['year'] : date('Y'));

$d['dDay'] = $day;
$d['dMonth'] = $month;
$d['dYear'] = $year;

$d['accesStats'] = verif_access_search(getUserName());

get_vocab_admin('admin');
get_vocab_admin('manage_my_account');
get_vocab_admin('report');
get_vocab_admin('retour_planning');
get_vocab_admin('admin_view_connexions');

$AllSettings = Settings::getAll();

// Template Twig
$loader = new Twig_Loader_Filesystem(__DIR__ . '/templates');
$twig = new Twig_Environment($loader,['charset']);
$twig->addExtension(new TwigGRR());

// Menu GRR
$menuAdminT = array();
$menuAdminTN2 = array();
//include "admin_col_gauche.php";

include('controleurs/'.$page.'.php');


?>