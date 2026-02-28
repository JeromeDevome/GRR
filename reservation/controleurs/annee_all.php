<?php
/**
 * annee_all.php
 * Interface d'accueil avec affichage par mois sur plusieurs mois des réservations de toutes les ressources d'un site
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2024-04-14 17:40 $
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
$grr_script_name = "annee_all.php";

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

$from_month = isset($_GET["from_month"]) ? intval($_GET["from_month"]) : NULL;
$from_year = isset($_GET["from_year"]) ? intval($_GET["from_year"]) : NULL;
$to_month = isset($_GET["to_month"]) ? intval($_GET["to_month"]) : NULL;
$to_year = isset($_GET["to_year"]) ? intval($_GET["to_year"]) : NULL;
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
$month_end = mktime(23, 59, 59, $to_month, 1, $to_year);
$days_in_to_month = date("t", $month_end);
$month_end = mktime(23,59,59,$to_month,$days_in_to_month,$to_year);

if ($d['pview'] != 1)
{
    $d['dateDebutHtml'] = genDateSelectorForm("from_", "", $from_month, $from_year,"");
    $d['dateFintHtml'] = genDateSelectorForm("to_", "", $to_month, $to_year,"");
}

// affichage des données mensuelles
$month_indice =  $month_start;

// construit la liste des ressources et domaines
if ($site == -1) 
{   // cas 1 : le multisite n'est pas activé $site devrait être à -1
    $sql  = "SELECT a.id AS idArea, a.area_name AS nomArea FROM ".TABLE_PREFIX."_area a ORDER BY a.order_display";
}
else
{
    if ($site == 0){
        $site = get_default_site();
    } elseif ($site > 0){
        $nomSite = grr_sql_query1("SELECT sitename FROM ".TABLE_PREFIX."_site WHERE id=".$site);
        $d["nomSite"] = $nomSite. " - ";
    } // si le site n'est pas défini, on le met à la valeur par défaut
    $sql  = "SELECT a.id AS idArea, a.area_name AS nomArea FROM ".TABLE_PREFIX."_area a JOIN ".TABLE_PREFIX."_j_site_area j ON j.id_area = a.id WHERE j.id_site = ".$site." ORDER BY a.order_display";
}

   // $rowD = grr_sql_row_keyed($res_dom, $i);

$lesMois = array();
while ($month_indice < $month_end)
{
    
    $resDom = grr_sql_query($sql);
    if (!$resDom)
        echo grr_sql_error(); // sortie en cas d'erreur de lecture dans la base MySQL
    else
    {
        $lesDomaines = array();
        $i = 0;
        // echo $tables[$month_indice];
        for ($i = 0; ($rowD = grr_sql_row_keyed($resDom, $i)); $i++) 
         {
            $da = Annee::Reservations($month_end, $month_start, $rowD["idArea"]);
            $month_num = date("m", $month_indice);
            $year_num  = date("Y", $month_indice);
            list($joursMois, $ressourcesMois) = Annee::MoisAffichage($month_indice, $rowD["idArea"], $display_day, $da);
            $nomMois = ucfirst(utf8_strftime("%B", $month_indice));
            $lesDomaines[] = array('nomDomaine' => $rowD["nomArea"], 'joursMois' => $joursMois, 'ressourcesMois' => $ressourcesMois);
        }


       
        $month_indice = mktime(0, 0, 0, $month_num + 1, 1, $year_num);

        $lesMois[] = array('numMois' => $month_num, 'annee' => $year_num, 'nomMois' => $nomMois, 'lesDomaines' => $lesDomaines);
    }
}// fin de boucle sur les mois


echo $twig->render('annee_all.twig', array('trad' => $trad, 'd' => $d, 'settings' => $AllSettings, 'lesMois' => $lesMois));

?>