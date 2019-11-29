<?php
/**
 * week_all.php
 * Permet l'affichage des réservation d'une semaine pour toutes les ressources d'un domaine.
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2019-11-29 11:00$
 * @author    Laurent Delineau & JeromeB & Yan Naessens
 * @copyright Copyright 2003-2019 Team DEVOME - JeromeB
 * @link      http://www.gnu.org/licenses/licenses.html
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 */

$grr_script_name = "week_all.php";

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
// initialisation des paramètres de temps
$date_now = time();
$day = (isset($_GET['day']))? $_GET['day'] : date("d"); // ou 1 ? YN le 07/03/2018
$month = (isset($_GET['month']))? $_GET['month'] : date("m");
$year = (isset($_GET['year']))? $_GET['year'] : date("Y");
// définition de variables globales
global $racine, $racineAd, $desactive_VerifNomPrenomUser;

// Lien de retour
$back = (isset($_SERVER['HTTP_REFERER']))? $_SERVER['HTTP_REFERER'] : page_accueil() ;

// Type de session
if ((Settings::get("authentification_obli") == 0) && (getUserName() == ''))
	$type_session = "no_session";
else
	$type_session = "with_session";
// autres initialisations
if (@file_exists('./admin_access_area.php')){
    $adm = 1;
    $racine = "../";
    $racineAd = "./";
}else{
    $adm = 0;
    $racine = "./";
    $racineAd = "./admin/";
}
// pour le traitement des modules
include $racine."/include/hook.class.php";

if (!($desactive_VerifNomPrenomUser))
    $desactive_VerifNomPrenomUser = 'n';
// On vérifie que les noms et prénoms ne sont pas vides
VerifNomPrenomUser($type_session);

// code html
echo '<!DOCTYPE html>'.PHP_EOL;
echo '<html lang="fr">'.PHP_EOL;
// section <head>
if ($type_session == "with_session")
    echo pageHead2(Settings::get("company"),"with_session");
else
    echo pageHead2(Settings::get("company"),"no_session");
// section <body>
echo "<body>";
// Menu du haut = section <header>
echo "<header>";
pageHeader2($day, $month, $year, $type_session);
echo "</header>";
// Debut de la page
echo '<section>'.PHP_EOL;

// Affichage du menu
include("menu_gauche2.php");
include("chargement.php");

// Dans le cas d'une selection invalide
if ($area <= 0)
{
	echo '<h1>'.get_vocab("noareas").'</h1>';
	echo '<a href="./admin/admin_accueil.php">'.get_vocab("admin").'</a>'.PHP_EOL.'</body>'.PHP_EOL.'</html>';
	exit();
}

// Calcul du niveau de droit de réservation
$authGetUserLevel = authGetUserLevel(getUserName(), -1);
// vérifie si la date est dans la période réservable
if (check_begin_end_bookings($day, $month, $year))
{
	showNoBookings($day, $month, $year, $back);
	exit();
}
//Renseigne les droits de l'utilisateur, si les droits sont insuffisants, l'utilisateur est averti.
if ((($authGetUserLevel < 1) && (Settings::get("authentification_obli") == 1)) || authUserAccesArea(getUserName(), $area) == 0)
{
	showAccessDenied($back);
	exit();
}

// On vérifie une fois par jour si le délai de confirmation des réservations est dépassé
	// Si oui, les réservations concernées sont supprimées et un mail automatique est envoyé.
	// On vérifie une fois par jour que les ressources ont été rendues en fin de réservation
	// Si non, une notification email est envoyée
if (Settings::get("verif_reservation_auto") == 0)
{
	verify_confirm_reservation();
	verify_retard_reservation();
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
	die();
}

// calcul du contenu du planning
if ($enable_periods == 'y')
{
	$resolution = 60;
	$morningstarts = 12;
	$morningstarts_minutes = 0;
	$eveningends = 12;
	$eveningends_minutes = count($periods_name)-1;
}
$time = mktime(0, 0, 0, $month, $day, $year);
$time_old = $time;
if (($weekday = (date("w", $time) - $weekstarts + 7) % 7) > 0)
	$time -= $weekday * 86400;
if (!isset($correct_heure_ete_hiver) || ($correct_heure_ete_hiver == 1))
{
	if ((heure_ete_hiver("ete", $year,0) <= $time_old) && (heure_ete_hiver("ete",$year,0) >= $time) && ($time_old != $time) && (date("H", $time) == 23))
		$decal = 3600;
	else
		$decal = 0;
	$time += $decal;
}
$day_week   = date("d", $time);
$month_week = date("m", $time);
$year_week  = date("Y", $time);
$date_start = mktime($morningstarts, 0, 0, $month_week, $day_week, $year_week);
$days_in_month = date("t", $date_start);
$date_end = mktime($eveningends, $eveningends_minutes, 0, $month_week, $day_week + 6, $year_week);
$this_area_name = grr_sql_query1("SELECT area_name FROM ".TABLE_PREFIX."_area WHERE id=$area");
switch ($dateformat)
{
	case "en":
	$dformat = "%A, %b %d";
	break;
	case "fr":
	$dformat = "%A %d %b";
	break;
}
$i = mktime(0, 0, 0, $month_week, $day_week - 7, $year_week);
$yy = date("Y", $i);
$ym = date("m", $i);
$yd = date("d", $i);
$i = mktime(0, 0, 0, $month_week, $day_week +7 , $year_week);
$ty = date("Y", $i);
$tm = date("m", $i);
$td = date("d", $i);
$all_day = preg_replace("/ /", " ", get_vocab("all_day2"));
$sql = "SELECT start_time, end_time, ".TABLE_PREFIX."_entry.id, name, beneficiaire, ".TABLE_PREFIX."_room.id,type, statut_entry, ".TABLE_PREFIX."_entry.description, ".TABLE_PREFIX."_entry.option_reservation, ".TABLE_PREFIX."_room.delais_option_reservation, ".TABLE_PREFIX."_entry.moderate, beneficiaire_ext, clef, ".TABLE_PREFIX."_entry.courrier
FROM ".TABLE_PREFIX."_entry, ".TABLE_PREFIX."_room, ".TABLE_PREFIX."_area
where
".TABLE_PREFIX."_entry.room_id=".TABLE_PREFIX."_room.id and
".TABLE_PREFIX."_area.id = ".TABLE_PREFIX."_room.area_id and
".TABLE_PREFIX."_area.id = '".$area."' and
start_time <= $date_end AND
end_time > $date_start
ORDER by start_time, end_time, ".TABLE_PREFIX."_entry.id";
/* contenu de la réponse si succès :
    $row[0] : start_time
    $row[1] : end_time
    $row[2] : entry id
    $row[3] : name
    $row[4] : beneficiaire
    $row[5] : room id
    $row[6] : type
    $row[7] : statut_entry
    $row[8] : entry description
    $row[9] : entry option_reservation
    $row[10]: room delais_option_reservation
    $row[11]: entry moderate
    $row[12]: beneficiaire_ext
    $row[13]: clef
    $row[14]: courrier
*/
$res2 = grr_sql_query($sql);
if (!$res2)
	echo grr_sql_error();
else
{
	for ($i = 0; ($row = grr_sql_row($res2, $i)); $i++)
	{
		$t = max((int)$row['0'], $date_start);
		$end_t = min((int)$row['1'], $date_end);
		$day_num = date("j", $t);
		$month_num = date("m", $t);
		$year_num = date("Y", $t);
		if ($enable_periods == 'y')
			$midnight = mktime(12, 0, 0, $month_num, $day_num, $year_num);
		else
			$midnight = mktime(0, 0, 0, $month_num, $day_num, $year_num);
		while ($t <= $end_t)
		{
			$d[$day_num]["id"][] = $row['2'];
			if (Settings::get("display_info_bulle") == 1)
				$d[$day_num]["who"][] = get_vocab("reservee au nom de").affiche_nom_prenom_email($row['4'], $row['12'], "nomail");
			else if (Settings::get("display_info_bulle") == 2)
				$d[$day_num]["who"][] = $row['8'];
			else
				$d[$day_num]["who"][] = "";
			$d[$day_num]["who1"][] = affichage_lien_resa_planning($row['3'], $row['2']);
			$d[$day_num]["id_room"][]=$row['5'] ;
			$d[$day_num]["color"][]=$row['6'];
			$d[$day_num]["res"][] = $row['7'];
			$descro = affichage_resa_planning($row['8'], $row['2']);
            $clef = $row[13];
            $courrier = $row[14];
			if ($clef == 1 || $courrier == 1)
                $descro .= '<br />'.PHP_EOL;
			if ($clef == 1)
				$descro .= '<img src="img_grr/skey.png" alt="clef">'.PHP_EOL;
            if (Settings::get('show_courrier') == 'y')
            {
                if ($courrier == 1)
                    $descro .= '<img src="img_grr/scourrier.png" alt="courrier">'.PHP_EOL;
                else
                    $descro .= '<br /><img src="img_grr/hourglass.png" alt="buzy">'.PHP_EOL;
            }
            $d[$day_num]["description"][] = $descro;
			if ($row['10'] > 0)
				$d[$day_num]["option_reser"][] = $row['9'];
			else
				$d[$day_num]["option_reser"][] = -1;
			$d[$day_num]["moderation"][] = $row['11'];
			$midnight_tonight = $midnight + 86400;
			if (!isset($correct_heure_ete_hiver) || ($correct_heure_ete_hiver == 1))
			{
				if (heure_ete_hiver("hiver",$year_num, 0) == mktime(0, 0, 0, $month_num, $day_num, $year_num))
					$midnight_tonight += 3600;
				if (date("H",$midnight_tonight) == "01")
					$midnight_tonight -= 3600;
			}
			if ($enable_periods == 'y')
			{
				$start_str = preg_replace("/ /", " ", period_time_string($row['0']));
				$end_str   = preg_replace("/ /", " ", period_time_string($row['1'], -1));
				switch (cmp3($row['0'], $midnight) . cmp3($row['1'], $midnight_tonight))
				{
					case "> < ":
					case "= < ":
					if ($start_str == $end_str)
						$d[$day_num]["data"][] = $start_str;
					else
						$d[$day_num]["data"][] = $start_str . get_vocab("to") . $end_str;
					break;
					case "> = ":
					$d[$day_num]["data"][] = $start_str . get_vocab("to")."24:00";
					break;
					case "> > ":
					$d[$day_num]["data"][] = $start_str . get_vocab("to")."==>";
					break;
					case "= = ":
					$d[$day_num]["data"][] = $all_day;
					break;
					case "= > ":
					$d[$day_num]["data"][] = $all_day . "==>";
					break;
					case "< < ":
					$d[$day_num]["data"][] = "<==".get_vocab("to") . $end_str;
					break;
					case "< = ":
					$d[$day_num]["data"][] = "<==" . $all_day;
					break;
					case "< > ":
					$d[$day_num]["data"][] = "<==" . $all_day . "==>";
					break;
				}
			}
			else
			{
				switch (cmp3($row[0], $midnight) . cmp3($row[1], $midnight_tonight))
				{
					case "> < ":
					case "= < ":
					$d[$day_num]["data"][] = date(hour_min_format(), $row[0]) . get_vocab("to") . date(hour_min_format(), $row[1]);
					break;
					case "> = ":
					$d[$day_num]["data"][] = date(hour_min_format(), $row[0]) . get_vocab("to")."24:00";
					break;
					case "> > ":
					$d[$day_num]["data"][] = date(hour_min_format(), $row[0]) . get_vocab("to")."==>";
					break;
					case "= = ":
					$d[$day_num]["data"][] = $all_day;
					break;
					case "= > ":
					$d[$day_num]["data"][] = $all_day . "==>";
					break;
					case "< < ":
					$d[$day_num]["data"][] = "<==".get_vocab("to") . date(hour_min_format(), $row[1]);
					break;
					case "< = ":
					$d[$day_num]["data"][] = "<==" . $all_day;
					break;
					case "< > ":
					$d[$day_num]["data"][] = "<==" . $all_day . "==>";
					break;
				}
			}
			if ($row[1] <= $midnight_tonight)
				break;
			$t = $midnight = $midnight_tonight;
			$day_num = date("j", $t);
		}
	}
}

if (isset($_GET['precedent']))
{
	if ($_GET['pview'] == 1 && $_GET['precedent'] == 1)
	{
		echo '<span id="lienPrecedent">'.PHP_EOL;
		echo '<button class="btn btn-default btn-xs" onclick="charger();javascript:history.back();">Précedent</button>'.PHP_EOL;
		echo '</span>'.PHP_EOL;
	}
}

// affichage des données du planning
// Début du tableau affichant le planning
if ($_GET['pview'] != 1){
    echo "<div id='planning2'>";
}
else{
	echo '<div id="print_planning">'.PHP_EOL;
}
echo '<table class="semaine table-bordered table-striped">',PHP_EOL;
// le titre de la table
echo "<caption>";
// liens semaine avant-après et imprimante si page non imprimable
if ((!isset($_GET['pview'])) or ($_GET['pview'] != 1))
{
	echo "\n
	<div class='ligne23'>
		<div class=\"left\">
			<button class=\"btn btn-default btn-xs\" onclick=\"charger();javascript: location.href='week_all.php?year=$yy&amp;month=$ym&amp;day=$yd&amp;area=$area';\"><span class=\"glyphicon glyphicon-backward\"></span> ".get_vocab("weekbefore")." </button>
		</div>";
		include "./include/trailer.inc.php";
		echo "<div class=\"right\">
			<button class=\"btn btn-default btn-xs\" onclick=\"charger();javascript: location.href='week_all.php?year=$ty&amp;month=$tm&amp;day=$td&amp;area=$area';\">".get_vocab('weekafter')." <span class=\"glyphicon glyphicon-forward\"></button>
		</div>
	</div>";
}
// montrer ou cacher le menu gauche
echo "<div>";
if ((!isset($_GET['pview'])) || ($_GET['pview'] != 1))
{
	echo "<div class=\"left\"> ";
    $mode = Settings::get("menu_gauche");
    $alt = $mode;
    if ($mode == 0) $alt = 1; // il faut bien que le menu puisse s'afficher, par défaut ce sera à gauche sauf choix autre par setting
    echo "<div id='voir'><button class=\"btn btn-default btn-sm\" onClick=\"afficheMenuGauche($alt)\" title='".get_vocab('show_left_menu')."'><span class=\"glyphicon glyphicon-chevron-right\"></span></button></div> ";
    echo "<div id='cacher'><button class=\"btn btn-default btn-sm\" onClick=\"afficheMenuGauche(0)\" title='".get_vocab('hide_left_menu')."'><span class=\"glyphicon glyphicon-chevron-left\"></span></button></div> "; 
	echo "</div>";
    if ($mode == 1){
        echo '<script type="text/javascript">
                document.getElementById("cacher").style.display = "inline";
                document.getElementById("voir").style.display = "none";
                document.getElementById("planning2").style.width = "75%"; 
            </script>
        ';
    }
    if ($mode == 2){
        echo '<script type="text/javascript">
                document.getElementById("cacher").style.display = "inline";
                document.getElementById("voir").style.display = "none";
            </script>
        ';
    }
}    
    echo '<h4 class="titre">'.$this_area_name.' - '.get_vocab("all_rooms").'<br> Du '.utf8_strftime($dformat, $date_start).' au '. utf8_strftime($dformat, $date_end). '</h4>'.PHP_EOL;
echo "</div>";
echo "</caption>";
echo '<thead>'.PHP_EOL;
echo '<tr>'.PHP_EOL;
echo '<th class="jour_sem">'.get_vocab('rooms').'</th>'.PHP_EOL;
$t = $time;
$num_week_day = $weekstarts;
//$ferie = getHolidays($year);
for ($weekcol = 0; $weekcol < 7; $weekcol++)
{
	$num_day = strftime("%d", $t);
	$temp_month = utf8_encode(strftime("%m", $t));
	$temp_month2 = utf8_strftime("%b", $t);
	$temp_year = strftime("%Y", $t);
	$tt = mktime(0, 0, 0, $temp_month, $num_day, $temp_year);
	$jour_cycle = grr_sql_query1("SELECT Jours FROM ".TABLE_PREFIX."_calendrier_jours_cycle WHERE day='$t'");
	$t += 86400;
	if (!isset($correct_heure_ete_hiver) || ($correct_heure_ete_hiver == 1))
	{
		if (heure_ete_hiver("hiver",$temp_year,0) == mktime(0, 0, 0, $temp_month, $num_day, $temp_year))
			$t += 3600;
		if (date("H", $t) == "01")
			$t -= 3600;
	}
	if ($display_day[$num_week_day] == 1)
	{
		$class = "";
		$title = "";
		if ($settings->get("show_holidays") == "Oui")
		{   
			if (isHoliday($tt)){
				$class .= 'ferie ';
			}
			elseif (isSchoolHoliday($tt)){
				$class .= 'vacance ';
			}
		}
		echo '<th class="jour_sem ';
        if ($class != '') echo $class;
        echo '">'.PHP_EOL;
		//echo '<a class="lienPlanning " href="day.php?year='.$temp_year.'&amp;month='.$temp_month.'&amp;day='.$num_day.'&amp;area='.$area.'" title="'.$title.'">'  . day_name(($weekcol + $weekstarts) % 7) . ' '.$num_day.' '.$temp_month2.'</a>'.PHP_EOL;
        echo '<a href="day.php?year='.$temp_year.'&amp;month='.$temp_month.'&amp;day='.$num_day.'&amp;area='.$area.'" title="'.$title.'">'  . day_name(($weekcol + $weekstarts) % 7) . ' '.$num_day.' '.$temp_month2.'</a>'.PHP_EOL;
		if (Settings::get("jours_cycles_actif") == "Oui" && intval($jour_cycle) >- 1)
		{
			if (intval($jour_cycle) > 0)
				echo "<br />".get_vocab("rep_type_6")." ".$jour_cycle;
			else
				echo "<br />".$jour_cycle;
		}
		echo '</th>'.PHP_EOL;
	}
	$num_week_day++;
	$num_week_day = $num_week_day % 7;
}
echo '</tr>'.PHP_EOL;
echo '</thead>'.PHP_EOL;
echo "<tbody>";
$li = 0;
for ($ir = 0; ($row = grr_sql_row($ressources, $ir)); $ir++)
{
	$verif_acces_ressource = verif_acces_ressource(getUserName(), $row['2']);
	if ($verif_acces_ressource)
	{
		$acces_fiche_reservation = verif_acces_fiche_reservation(getUserName(), $row['2']);
		$UserRoomMaxBooking = UserRoomMaxBooking(getUserName(), $row['2'], 1);
		$authGetUserLevel = authGetUserLevel(getUserName(), -1);
		$auth_visiteur = auth_visiteur(getUserName(), $row['2']);
		echo '<tr>'.PHP_EOL;
		if ($li % 2 == 1)
			echo tdcell("cell_hours");
		else
			echo tdcell("cell_hours2");
		echo '<a title="'.htmlspecialchars(get_vocab("see_week_for_this_room")).'" href="week.php?year='.$year.'&amp;month='.$month.'&amp;day='.$day.'&amp;area='.$area.'&amp;room='.$row['2'].'">' . htmlspecialchars($row[0]) .'</a><br />'.PHP_EOL;
		if ($row['1']  && $_GET['pview'] != 1)
			echo '<span class="small">('.$row['1'].' '.($row['1'] > 1 ? get_vocab("number_max2") : get_vocab("number_max")).')</span>'.PHP_EOL;
		if ($row['4'] == "0")
			echo '<span class="texte_ress_tempo_indispo">'.get_vocab("ressource_temporairement_indisponible").'</span><br />'.PHP_EOL;
		if (verif_display_fiche_ressource(getUserName(), $row['2']) && $_GET['pview'] != 1)
		{
			echo '<a href="javascript:centrerpopup(\'view_room.php?id_room='.$row['2'].'\',600,480,\'scrollbars=yes,statusbar=no,resizable=yes\')" title="'.get_vocab("fiche_ressource").'">'.PHP_EOL;
			echo '<span class="glyphcolor glyphicon glyphicon-search"></span></a>'.PHP_EOL;
		}
		if (authGetUserLevel(getUserName(),$row['2']) > 2 && $_GET['pview'] != 1)
			echo '<a href="./admin/admin_edit_room.php?room='.$row['2'].'"><span class="glyphcolor glyphicon glyphicon-cog"></span></a>'.PHP_EOL;
		affiche_ressource_empruntee($row['2']);
		echo '</td>'.PHP_EOL;
		$li++;
		$t = $time;
		$t2 = $time;
		$num_week_day = $weekstarts;
		for ($k = 0; $k <= 6; $k++)
		{
			$cday = date("j", $t2);
			$cmonth = strftime("%m", $t2);
			$cyear = strftime("%Y", $t2);
			$t2 += 86400;
			if (!isset($correct_heure_ete_hiver) || ($correct_heure_ete_hiver == 1))
			{
				$temp_day = strftime("%d", $t2);
				$temp_month = strftime("%m", $t2);
				$temp_year = strftime("%Y", $t2);
				if (heure_ete_hiver("hiver", $temp_year,0) == mktime(0, 0, 0, $temp_month, $temp_day, $temp_year))
					$t2 += 3600;
				if (date("H", $t2) == "01")
					$t2 -= 3600;
			}
			if ($display_day[$num_week_day] == 1)
			{
				$no_td = TRUE;
				if ((isset($d[$cday]["id"][0])) && !(est_hors_reservation(mktime(0, 0, 0, $cmonth, $cday, $cyear), $area)))
				{
					$n = count($d[$cday]["id"]);
					for ($i = 0; $i < $n; $i++)
					{
						if ($d[$cday]["id_room"][$i]==$row['2'])
						{
							if ($no_td)
							{
                                echo '<td >'.PHP_EOL;
								$no_td = FALSE;
							}
							if ($acces_fiche_reservation)
							{
								if (Settings::get("display_level_view_entry") == 0)
								{
									$currentPage = 'week_all';
									$id = $d[$cday]["id"][$i];
									echo '<a title="'.htmlspecialchars($d[$cday]["who"][$i]).'" data-width="675" onclick="request('.$id.','.$cday.','.$cmonth.','.$cyear.',\'all\',\''.$currentPage.'\',readData);" data-rel="popup_name" class="poplight lienCellule" style = "border-bottom:1px solid #FFF">'.PHP_EOL;
								}
								else
									echo '<a class="lienCellule" style = "border-bottom:1px solid #FFF" title="'.htmlspecialchars($d[$cday]["who"][$i]).'" href="view_entry.php?id='.$d[$cday]["id"][$i].'&amp;page=week_all&amp;day='.$cday.'&amp;month='.$cmonth.'&amp;year='.$cyear.'&amp;" >'.PHP_EOL;
								echo '<table class="pleine">'.PHP_EOL;
								echo '<tr>'.PHP_EOL;
								tdcell($d[$cday]["color"][$i]);
								if ($d[$cday]["res"][$i] !='-')
									echo '<img src="img_grr/buzy.png" alt="'.get_vocab("ressource actuellement empruntee").'" title="'.get_vocab("ressource actuellement empruntee").'" width="20" height="20" class="image" />'.PHP_EOL;
								if ((isset($d[$cday]["option_reser"][$i])) && ($d[$cday]["option_reser"][$i] != -1))
									echo '<img src="img_grr/small_flag.png" alt="',get_vocab("reservation_a_confirmer_au_plus_tard_le"),'" title="',get_vocab("reservation_a_confirmer_au_plus_tard_le"),' ',time_date_string_jma($d[$cday]["option_reser"][$i],$dformat),'" width="20" height="20" class="image" />',PHP_EOL;
								if ((isset($d[$cday]["moderation"][$i])) && ($d[$cday]["moderation"][$i] == 1))
									echo '<img src="img_grr/flag_moderation.png" alt="',get_vocab("en_attente_moderation"),'" title="',get_vocab("en_attente_moderation"),'" class="image" />',PHP_EOL;
								$Son_GenreRepeat = grr_sql_query1("SELECT ".TABLE_PREFIX."_type_area.type_name FROM ".TABLE_PREFIX."_type_area,".TABLE_PREFIX."_entry  WHERE  ".TABLE_PREFIX."_entry.type=".TABLE_PREFIX."_type_area.type_letter  AND ".TABLE_PREFIX."_entry.id = '".$d[$cday]["id"][$i]."';");
								if ($Son_GenreRepeat == -1)
                                    echo '<span class="small_planning">',$d[$cday]["data"][$i];
                                else
                                    if (Settings::get("type") == '1') {
                                        echo '<span class="small_planning">'. $d[$cday]["data"][$i].'<br>'. $Son_GenreRepeat.'<br>'.PHP_EOL; }
                                    else {
                                        echo '<span class="small_planning">'. $d[$cday]["data"][$i].'<br>'. PHP_EOL; }
								echo $d[$cday]["who1"][$i]. '<br/>'.PHP_EOL;
								if ($d[$cday]["description"][$i] != "")
									echo '<i>'.$d[$cday]["description"][$i].'</i>'.PHP_EOL;
								echo '</span>'.PHP_EOL;
							}
							else
							{
								echo PHP_EOL.'<table class="table table-bordered"><tr>';
								tdcell($d[$cday]["color"][$i]);
								if ($d[$cday]["res"][$i] != '-')
									echo '<img src="img_grr/buzy.png" alt="',get_vocab("ressource actuellement empruntee"),'" title="',get_vocab("ressource actuellement empruntee"),'" width="20" height="20" class="image" />',PHP_EOL;
								if ((isset($d[$cday]["option_reser"][$i])) && ($d[$cday]["option_reser"][$i] != -1))
									echo '<img src="img_grr/small_flag.png" alt="',get_vocab("reservation_a_confirmer_au_plus_tard_le"),'" title="',get_vocab("reservation_a_confirmer_au_plus_tard_le"),' ',time_date_string_jma($d[$cday]["option_reser"][$i],$dformat),'" width="20" height="20" class="image" />',PHP_EOL;
								if ((isset($d[$cday]["moderation"][$i])) && ($d[$cday]["moderation"][$i] == 1))
									echo '<img src="img_grr/flag_moderation.png" alt="',get_vocab("en_attente_moderation"),'" title="',get_vocab("en_attente_moderation"),'" class="image" />',PHP_EOL;
								$Son_GenreRepeat = grr_sql_query1("SELECT ".TABLE_PREFIX."_type_area.type_name FROM ".TABLE_PREFIX."_type_area,".TABLE_PREFIX."_entry  WHERE  ".TABLE_PREFIX."_entry.type=".TABLE_PREFIX."_type_area.type_letter  AND ".TABLE_PREFIX."_entry.id = '".$d[$cday]["id"][$i]."';");
								if ($Son_GenreRepeat == -1 )
								{
									echo '<span class="small_planning">',PHP_EOL,'<b>',$d[$cday]["data"][$i],'</b><br>';
								}
								else
								{
                                    if (Settings::get("type") == '1') 
                                        { echo '<span class="small_planning">'. $d[$cday]["data"][$i].'<br>'. $Son_GenreRepeat.'<br>'.PHP_EOL; }
                                    else { echo '<span class="small_planning">'. $d[$cday]["data"][$i].'<br>'. PHP_EOL; }
								}
								echo $d[$cday]["who1"][$i].'<br>'.PHP_EOL;
								if ($d[$cday]["description"][$i] != "")
									echo '<i>'.$d[$cday]["description"][$i].'</i>'.PHP_EOL;
								echo '</span>'.PHP_EOL;
							}
							echo '</td>'.PHP_EOL;
							echo '</tr>'.PHP_EOL;
							echo '</table>'.PHP_EOL;
							echo '</a>'.PHP_EOL;
						}
					}
				}
				if ($no_td)
				{
					if ($row['4'] == 1)
						echo '<td class="empty_cell">'.PHP_EOL;
					else
						echo '<td class="avertissement">'.PHP_EOL;
				}
				//else
				//	echo '<div class="empty_cell">'.PHP_EOL;
				$hour = date("H", $date_now);
				$date_booking = mktime(24, 0, 0, $cmonth, $cday, $cyear);
				if (est_hors_reservation(mktime(0, 0, 0, $cmonth, $cday, $cyear), $area))
					echo '<img src="img_grr/stop.png" alt="',get_vocab("reservation_impossible"),'" title="',get_vocab("reservation_impossible"),'" width="16" height="16" class="',$class_image,'" />',PHP_EOL;
				else
				{
					if ((($authGetUserLevel > 1) || ($auth_visiteur == 1)) && ($UserRoomMaxBooking != 0) && verif_booking_date(getUserName(), -1, $row['2'], $date_booking, $date_now, $enable_periods) && verif_delais_max_resa_room(getUserName(), $row['2'], $date_booking) && verif_delais_min_resa_room(getUserName(), $row['2'], $date_booking) && plages_libre_semaine_ressource($row['2'], $cmonth, $cday, $cyear) && (($row['4'] == "1") || (($row['4'] == "0") && (authGetUserLevel(getUserName(),$row['2']) > 2) )) && $_GET['pview'] != 1)
					{
						if ($enable_periods == 'y')
							echo '<a href="edit_entry.php?room=',$row["2"],'&amp;period=&amp;year=',$cyear,'&amp;month=',$cmonth,'&amp;day=',$cday,'&amp;page=week_all" title="',get_vocab("cliquez_pour_effectuer_une_reservation"),'"><span class="glyphicon glyphicon-plus"></span></a>',PHP_EOL;
						else
							echo '<a href="edit_entry.php?room=',$row["2"],'&amp;hour=',$hour,'&amp;minute=0&amp;year=',$cyear,'&amp;month=',$cmonth,'&amp;day=',$cday,'&amp;page=week_all" title="',get_vocab("cliquez_pour_effectuer_une_reservation"),'"><span class="glyphicon glyphicon-plus"></span></a>',PHP_EOL;;
					}
					else
						echo ' '.PHP_EOL;
				}
				//if (!$no_td)
				//	echo '</div>'.PHP_EOL;
				echo '</td>'.PHP_EOL;
			}
			$num_week_day++;
			$num_week_day = $num_week_day % 7;
		}
	
		echo '<script type="text/javascript">			
				jQuery(document).ready(function($){
					$("#popup_name").draggable({containment: "#container"});
					$("#popup_name").resizable(
					{
						animate: true
					}
					);
				});
			</script>';	
		echo '</tr>'.PHP_EOL;
	}
}
echo "</tbody>";
echo '</table>'.PHP_EOL;
if ($_GET['pview'] != 1)
{
	echo '<div id="toTop">',PHP_EOL,'<b>',get_vocab("top_of_page"),'</b>',PHP_EOL;
	bouton_retour_haut ();
	echo '</div>',PHP_EOL;
}
echo '</div>'.PHP_EOL; // planning2
echo '</section>'.PHP_EOL; // row

unset($row);

echo '<div id="popup_name" class="popup_block col-xs-12" ></div>'.PHP_EOL;

echo "</body></html>";
?>
