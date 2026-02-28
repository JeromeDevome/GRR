<?php
/**
 * planning.php
 * Préparation et contrôle des paramètres avant le calcul du planning
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2026-01-06 11:47$
 * @author    Laurent Delineau & JeromeB & Yan Naessens
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

// Initilisation des variables
if (!isset($_GET['pview']))
	$d['pview'] = 0;
else
	$d['pview'] = 1;

$d['precedent'] = (isset($_GET['precedent']))? intval($_GET['precedent']) : 0;


// Type de session
if ((Settings::get("authentification_obli") == 0) && (getUserName() == ''))
	$type_session = "no_session";
else
	$type_session = "with_session";

if (!($desactive_VerifNomPrenomUser))
    $desactive_VerifNomPrenomUser = 'n';

if ((!isset($d['pview'])) || ($d['pview'] != 1))
{
    $positionMenu = Settings::get("menu_gauche");
    $d['positionMenu'] = ($positionMenu != 0)? $positionMenu : 1; // il faut bien que le menu puisse s'afficher, par défaut ce sera à gauche sauf choix autre par setting
}

// Vérification de l'authentification obligatoire
if ((Settings::get("authentification_obli") == 1) && (getUserName() == ''))
{
	$url = rawurlencode($_GET['url']);
	header("Location: app.php?p=login&url=".$url);
	exit;
}

// initialisation des paramètres de temps
$date_now = time();

$d['cssTypeResa'] = cssTypeResa();
$d['page'] = $page;
//$d['paramUrl'] = $_SERVER['QUERY_STRING'];
$d['popupMessage'] = "";

//Construction des identifiants de la ressource $room, du domaine $area, du site $id_site
Definition_ressource_domaine_site();
$d['room'] = $room;
$d['area'] = $area;
$d['id_site'] = $id_site;

// contrôle des paramètres $room et $area
$test = grr_sql_query1("SELECT id FROM ".TABLE_PREFIX."_room WHERE id=$room");
if($test == -1) // $room ne définit pas une ressource
{
  $test = grr_sql_query1("SELECT id FROM ".TABLE_PREFIX."_area WHERE id=$area");
  if($test == -1) // $area ne définit pas un domaine
  {
    $lien = page_accueil();
    $d['messageErreur'] = '<h1>'.get_vocab('ressource_ou_domaine_non_defini').'</h1>';
    $d['messageErreur'] .= '<a href="'.$lien.'">'.get_vocab('Portail_accueil').'</a>';
    echo $twig->render('planningerreur.twig', array('trad' => $trad, 'd' => $d, 'settings' => $AllSettings));
	exit();
  }
}
//Récupération des données concernant l'affichage du planning du domaine, $enable_periods
if($area>0)
{
    get_planning_area_values($area);
    $d['usePeriode'] = $enable_periods;
}

// Hors mode prévisualisation de page imprimable, on affiche les menus
if ($d['pview'] != 1) {

    if(isset($_GET['p']))
        $file = $_GET['p'];
    else
    {
        $path = isset($_SERVER['PHP_SELF'])? $_SERVER['PHP_SELF']:(isset($_SERVER['SCRIPT_NAME'])? $_SERVER['SCRIPT_NAME']:"jour");
        $file = basename($path);
    }

    $pageActuel = str_replace(".php","",$file);
    // détermine le contexte d'appel : jour, semaine ou mois
    $pageSimple = str_replace(".php","",$file);
    $pageSimple = str_replace("_all","",$pageSimple);
    $pageSimple = str_replace("2","",$pageSimple);
    if ($pageSimple == "jour") 
        $pageTout = "jour";
    else 
        $pageTout = $pageSimple."_all";
    // les sélecteurs de ressource
    if (isset($_SESSION['default_list_type']) || (Settings::get("authentification_obli") == 1))
        $area_list_format = $_SESSION['default_list_type'];
    else
        $area_list_format = Settings::get("area_list_format");
    $selecteursH = "";
    $selecteursG = "";
    if ($area_list_format != "list")
    {
        if ($area_list_format == "select")
        {
            $selecteursH .= make_site_select_html($pageTout, $id_site, $year, $month, $day, getUserName(),"H");
            $selecteursH .= make_area_select_html($pageTout, $id_site, $area, $year, $month, $day, getUserName(),"H");
            $selecteursH .= make_room_select_html($pageSimple, $area, $room, $year, $month, $day,"H");
            $selecteursG .= make_site_select_html($pageTout, $id_site, $year, $month, $day, getUserName(),"G");
            $selecteursG .= make_area_select_html($pageTout, $id_site, $area, $year, $month, $day, getUserName(),"G");
            $selecteursG .= make_room_select_html($pageSimple, $area, $room, $year, $month, $day,"G");
        }
        else
        {
            $selecteurs = "";
            $selecteurs .= make_site_item_html($pageTout, $id_site, $year, $month, $day, getUserName());
            $selecteurs .= make_area_item_html($pageTout,$id_site, $area, $year, $month, $day, getUserName());
            $selecteurs .= make_room_item_html($pageSimple, $area, $room, $year, $month, $day);
            $selecteursG = $selecteurs;
            $selecteursH = $selecteurs;
        }
    }
    else
    {
        $selecteurs = "";
        $selecteurs .= make_site_list_html($pageTout,$id_site,$year,$month,$day,getUserName());
        $selecteurs .= make_area_list_html($pageTout,$id_site, $area, $year, $month, $day, getUserName());
        $selecteurs .= make_room_list_html($pageSimple, $area, $room, $year, $month, $day);
        $selecteursG = $selecteurs;
        $selecteursH = $selecteurs;
    }


    $d['selecteursH'] = $selecteursH;
    $d['selecteursG'] = $selecteursG;
    if($area>0)
    {
        $d['miniCalentrier'] = minicalsTwig($year, $month, $day, $area, $room, $pageActuel);
        $d['selectionDateDirecte'] = jQuery_DatePickerTwig('');
        $d['legende'] = show_colour_keyTwig($area);
    }
    $d['classImage'] =  "image";

// Page visualisation imprimable
} else {
    $d['classImage'] = "print_image";
}

// Affichage du message d'erreur en cas d'échec de l'envoi de mails automatiques
if ( !(Settings::get("javascript_info_disabled")) && (isset($_SESSION['session_message_error'])) && ($_SESSION['session_message_error'] != ''))
{
    $d['sessionMessageErreur'] = $_SESSION['session_message_error'];
    $_SESSION['session_message_error'] = "";
}

/* Popup */
if ((isset($_SESSION["msg_a_afficher"])) and ($_SESSION["msg_a_afficher"] != ""))
    $d['popupMessage'] = $_SESSION["msg_a_afficher"];

if ($d['popupMessage'] != "")
{

    if (!(Settings::get("javascript_info_disabled")))
    {
        if ((isset($_SESSION['displ_msg'])) && ($_SESSION['displ_msg'] == 'yes'))
            $d['popupAffiche'] = 1;

    }
    $_SESSION['displ_msg'] = "";
    $_SESSION["msg_a_afficher"] = "";
}


// On vérifie que les noms et prénoms ne sont pas vides
VerifNomPrenomUser($type_session);

// HOOK
$resulHook = Hook::Appel("hookPlanning1");
$d['hookPlanning1'] = $resulHook['hookPlanning1'];

// Dans le cas d'une selection invalide
if ($area <= 0)
{
    $d['messageErreur'] = '<h1>'.get_vocab("noareas").'</h1>';
    $d['messageErreur'] .= '<a href="./admin/admin_accueil.php">'.get_vocab("admin").'</a>';
    echo $twig->render('planningerreur.twig', array('trad' => $trad, 'd' => $d, 'settings' => $AllSettings));
	exit();
}

// vérifie si la date est dans la période réservable
if (check_begin_end_bookings($day, $month, $year))
{
    $d['messageErreur'] = showNoBookings_twig($day, $month, $year, $back);
    echo $twig->render('planningerreur.twig', array('trad' => $trad, 'd' => $d, 'settings' => $AllSettings));
	exit();
}

$user_name = getUserName();
// Calcul du niveau de droit de réservation
$authGetUserLevel = authGetUserLevel($user_name, -1);

//Renseigne les droits de l'utilisateur, si les droits sont insuffisants, l'utilisateur est averti.
if ((($authGetUserLevel < 1) && (Settings::get("authentification_obli") == 1)) || authUserAccesArea($user_name, $area) == 0)
{
	$d['messageErreur'] = showAccessDenied_twig($back);
  echo $twig->render('planningerreur.twig', array('trad' => $trad, 'd' => $d, 'settings' => $AllSettings));
	exit();
}
?>