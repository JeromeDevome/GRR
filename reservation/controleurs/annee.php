<?php
/**
 * annee.php
 * Interface d'accueil avec affichage par mois sur plusieurs mois des réservation de toutes les ressources d'un domaine
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2024-04-14 17:20$
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
$grr_script_name = "annee.php";

$trad = $vocab;

include "include/resume_session.php";
include "include/planning.php";
include "reservation/modeles/annee.php";

if (isset($_GET['area']))
{
    $area = mysqli_real_escape_string($GLOBALS['db_c'], $_GET['area']);
    settype($area, "integer");
    $site = mrbsGetAreaSite($area);
}
else
{
    if (isset($_GET["site"]))
    {
        $site = mysqli_real_escape_string($GLOBALS['db_c'], $_GET["site"]);
        settype($site, "integer");
        $area = get_default_area($site);
    }
    else
    {
        $site = get_default_site();
        $area = get_default_area($site);
    }
}

$from_month = isset($_GET["from_month"]) ? $_GET["from_month"] : NULL;
$from_year = isset($_GET["from_year"]) ? $_GET["from_year"] : NULL;
$to_month = isset($_GET["to_month"]) ? $_GET["to_month"] : NULL;
$to_year = isset($_GET["to_year"]) ? $_GET["to_year"] : NULL;
$day = 1;
$date_now = time();
//Default parameters:
if (empty($debug_flag))
	$debug_flag = 0;
if (empty($from_month) || empty($from_year) || !checkdate($from_month, 1, $from_year))
{
	if ($date_now < Settings::get('begin_bookings'))
		$date_ = Settings::get('begin_bookings');
	else if ($date_now > Settings::get('end_bookings'))
		$date_ = Settings::get('end_bookings');
	else
		$date_ = $date_now;
	$day   = date('d',$date_);
	$from_month = date('m',$date_);
	$from_year  = date('Y',$date_);
}
else
{
	$date_ = mktime(0, 0, 0, $from_month, $day, $from_year);
	if ($date_ < Settings::get('begin_bookings'))
		$date_ = Settings::get('begin_bookings');
	else if ($date_ > Settings::get('end_bookings'))
		$date_ = Settings::get('end_bookings');
	$day   = date('d',$date_);
	$from_month = date('m',$date_);
	$from_year  = date('Y',$date_);
}
if (empty($to_month) || empty($to_year) || !checkdate($to_month, 1, $to_year))
{
	$to_month = $from_month;
	$to_year  = $from_year;
}
else
{
	$date_ = mktime(0, 0, 0, $to_month, 1, $to_year);
	if ($date_ < Settings::get('begin_bookings'))
		$date_ = Settings::get('begin_bookings');
	else if ($date_ > Settings::get('end_bookings'))
		$date_ = Settings::get('end_bookings');
	$to_month = date('m',$date_);
	$to_year  = date('Y',$date_);
}

//Month view start time. This ignores morningstarts/eveningends because it
//doesn't make sense to not show all entries for the day, and it messes
//things up when entries cross midnight.
$month_start = mktime(0, 0, 0, $from_month, 1, $from_year);
//What column the month starts in: 0 means $weekstarts weekday.
$weekday_start = (date("w", $month_start) - $weekstarts + 7) % 7;
$month_end = mktime(23, 59, 59, $to_month, 1, $to_year);
$days_in_to_month = date("t", $month_end);
$month_end = mktime(23,59,59,$to_month,$days_in_to_month,$to_year);

// calcul des données à afficher
get_planning_area_values($area);

if ($enable_periods == 'y')
{
	$resolution = 60;
	$morningstarts = 12;
	$eveningends = 12;
	$eveningends_minutes = count($periods_name) - 1;
}

$da = Annee::Reservations($month_end, $month_start, $area);

// Debut de la page
$d['nomDomaine'] = grr_sql_query1("SELECT area_name FROM ".TABLE_PREFIX."_area WHERE id=$area");
// Si format imprimable ($_GET['pview'] = 1), on n'affiche pas cette partie 
if ($d['pview'] != 1)
{
    $d['listeSiteHtml'] = make_site_select_html('annee',$site,$from_year,$from_month,$day,$user_name);
    $d['listeDomaineHtml'] = make_area_select_all_html('annee',$site, $area, $from_year, $from_month, $day, $user_name);
    $d['dateDebutHtml'] = genDateSelectorForm("from_", "", $from_month, $from_year,"");
    $d['dateFintHtml'] = genDateSelectorForm("to_", "", $to_month, $to_year,"");
}

// Boucle sur les mois
$month_indice = $month_start;
$lesMois = array();
while ($month_indice < $month_end)
{
    $month_num = date("m", $month_indice);
    $year_num  = date("Y", $month_indice);
    list($joursMois, $ressourcesMois) = Annee::MoisAffichage($month_indice, $area, $display_day, $da);
    $nomMois = ucfirst(utf8_strftime("%B", $month_indice));
	$month_indice = mktime(0, 0, 0, $month_num + 1, 1, $year_num);
    $lesMois[] = array('numMois' => $month_num, 'annee' => $year_num, 'nomMois' => $nomMois, 'joursMois' => $joursMois, 'ressourcesMois' => $ressourcesMois);
} // Fin de la boucle sur les mois

echo $twig->render('annee.twig', array('trad' => $trad, 'd' => $d, 'settings' => $AllSettings, 'lesMois' => $lesMois));
?>