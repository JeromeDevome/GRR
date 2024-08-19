<?php
/**
 * month_all2.php
 * Interface d'accueil avec affichage par mois des réservations de toutes les ressources d'un domaine
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2024-08-19 15:29$
 * @author    Laurent Delineau & JeromeB & Yan Naessens
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
// cette page est partiellement internationalisée : à compléter

$grr_script_name = "month_all2.php";

include "include/connect.inc.php";
include "include/config.inc.php";
include "include/misc.inc.php";
include "include/$dbsys.inc.php";
include "include/mrbs_sql.inc.php";
include "include/functions.inc.php";
include "include/mincals.inc.php";
require_once("./include/settings.class.php");
$settings = new Settings();
if (!$settings)
	die(get_vocab('error_settings_load'));
require_once("./include/session.inc.php");
include "include/resume_session.php";
include "include/language.inc.php";

//Construction des identifiants de la ressource $room, du domaine $area, du site $id_site
Definition_ressource_domaine_site();

//Récupération des données concernant l'affichage du planning du domaine
if($area >0)
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
$back = (isset($_SERVER['HTTP_REFERER']))? htmlspecialchars_decode($_SERVER['HTTP_REFERER'], ENT_QUOTES) : page_accueil() ;
// Type de session
$user_name = getUserName();
if ((Settings::get("authentification_obli") == 0) && ($user_name == ''))
	$type_session = "no_session";
else
	$type_session = "with_session";
// autres initialisations
$adm = 0;
$racine = "./";
$racineAd = "./admin/";

if (!($desactive_VerifNomPrenomUser))
    $desactive_VerifNomPrenomUser = 'n';
// On vérifie que les noms et prénoms ne sont pas vides
VerifNomPrenomUser($type_session);
// Dans le cas d'une selection invalide
if ($area <= 0)
{
    start_page_w_header($day,$month,$year,$type_session);
	echo '<h1>'.get_vocab("noareas").'</h1>';
	echo '<a href="./admin/admin_accueil.php">'.get_vocab("admin").'</a>'.PHP_EOL.'</body>'.PHP_EOL.'</html>';
	exit();
}
// vérifie si la date est dans la période réservable
if (check_begin_end_bookings($day, $month, $year))
{
    start_page_w_header($day,$month,$year,$type_session);
	showNoBookings($day, $month, $year, $back);
	exit();
}
// Calcule les droits de l'utilisateur, si les droits sont insuffisants, l'utilisateur est averti.
$auth_user_level = authGetUserLevel($user_name, -1);
if ((($auth_user_level < 1) && (Settings::get("authentification_obli") == 1)) || authUserAccesArea($user_name, $area) == 0)
{
    start_page_w_header($day,$month,$year,$type_session);
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
$sql = "SELECT * FROM ".TABLE_PREFIX."_room WHERE area_id=? ORDER BY order_display, room_name";
$ressources = grr_sql_query($sql,"i",[$area]);
$nb_ressources = grr_sql_count($ressources);
if (!$ressources)
	fatal_error(0, grr_sql_error());

// options pour l'affichage
$opt = array('horaires','beneficiaire','short_desc','description','create_by','type','participants');
$options = decode_options(Settings::get('cell_month_all2'),$opt);
$options_popup = decode_options(Settings::get('popup_month_all2'),$opt);
// calcul du contenu du planning2
if ($enable_periods=='y')
{
	$resolution = 60;
	$morningstarts = 12;
	$eveningends = 12;
	$eveningends_minutes = count($periods_name)-1;
}
$month_start = mktime($morningstarts, 0, 0, $month, 1, $year);
$weekday_start = (date("w", $month_start) - $weekstarts + 7) % 7;
$days_in_month = date("t", $month_start);
$month_end = mktime(23, 59, 59, $month, $days_in_month, $year);
$this_area_name = "";
$this_room_name = "";
$this_area_name = grr_sql_query1("SELECT area_name FROM ".TABLE_PREFIX."_area WHERE id=?","i",[$area]);
$i = mktime(0,0,0,$month-1,1,$year);
$yy = date("Y",$i);
$ym = date("n",$i);
$i = mktime(0,0,0,$month+1,1,$year);
$ty = date("Y",$i);
$tm = date("n",$i);

$all_day = preg_replace("/ /", " ", get_vocab("all_day"));
// un type à exclure ?
$type_exclu = Settings::get('exclude_type_in_views_all'); // nom du type exclu
$sql = "SELECT type_letter FROM ".TABLE_PREFIX."_type_area WHERE ".TABLE_PREFIX."_type_area.type_name = ? ";
$res = grr_sql_query1($sql,"s",[$type_exclu]);
$typeExclu = ($res != -1)? $res :NULL; // lettre identifiant le type exclu
grr_sql_free($res);
//Get all meetings for this month in the area that we care about
$sql = "SELECT start_time, end_time, ".TABLE_PREFIX."_entry.id, name, beneficiaire, ".TABLE_PREFIX."_room.room_name,type, statut_entry, ".TABLE_PREFIX."_entry.description, ".TABLE_PREFIX."_entry.option_reservation, ".TABLE_PREFIX."_room.delais_option_reservation, ".TABLE_PREFIX."_entry.moderate, beneficiaire_ext, clef, ".TABLE_PREFIX."_entry.courrier, ".TABLE_PREFIX."_entry.overload_desc,".TABLE_PREFIX."_entry.room_id, ".TABLE_PREFIX."_entry.create_by, ".TABLE_PREFIX."_entry.nbparticipantmax 
FROM ((".TABLE_PREFIX."_entry JOIN ".TABLE_PREFIX."_room ON ".TABLE_PREFIX."_entry.room_id=".TABLE_PREFIX."_room.id)
JOIN ".TABLE_PREFIX."_area ON ".TABLE_PREFIX."_area.id = ".TABLE_PREFIX."_room.area_id) 
WHERE ".TABLE_PREFIX."_area.id = ? 
AND start_time <= ? AND end_time > ? 
ORDER BY ".TABLE_PREFIX."_room.order_display, room_name, start_time, end_time";
/* contenu de la réponse si succès :
    $row[0] : start_time
    $row[1] : end_time
    $row[2] : entry id
    $row[3] : name
    $row[4] : beneficiaire
    $row[5] : room name
    $row[6] : type
    $row[7] : statut_entry
    $row[8] : entry description
    $row[9] : entry option_reservation
    $row[10]: room delais_option_reservation
    $row[11]: entry moderate
    $row[12]: beneficiaire_ext
    $row[13]: clef
    $row[14]: courrier
	$row[15]: Type_name , supprimé le 07/11/22
    $row[16]: overload fields description
    $row[17]: room_id
    $row[18]: create_by
    $row[19]: nbparticipantmax
*/
$res = grr_sql_query($sql,"iii",[$area,$month_end,$month_start]);
if (!$res)
	echo grr_sql_error();
else
{
    $overloadFieldList = mrbsOverloadGetFieldslist($area);
    foreach($res as $row)
	{
		if ($row["type"] <> $typeExclu)   // identifiant du type à exclure
		{
			if ($debug_flag)
				echo "<br />DEBUG: result $i, id ".$row['id'].", starts ".$row['start_time'].", ends ".$row['end_time']."\n";
			$t = max((int)$row['start_time'], $month_start);
			$end_t = min((int)$row['end_time'], $month_end);
			$day_num = date("j", $t);
			if ($enable_periods == 'y')
				$midnight = mktime(12,0,0,$month,$day_num,$year);
			else
				$midnight = mktime(0, 0, 0, $month, $day_num, $year);
			while ($t < $end_t)
			{
				if ($debug_flag)
					echo "<br />DEBUG: Entry ".$row['id']." day $day_num\n";
				$d[$day_num]["id"][] = $row["id"];
				$d[$day_num]["room"][] = $row["room_name"] ;
				$d[$day_num]["color"][] = $row["type"];
				$midnight_tonight = $midnight + 86400;
				if ($enable_periods == 'y')
				{
					$start_str = preg_replace("/ /", " ", period_time_string($row['start_time']));
					$end_str   = preg_replace("/ /", " ", period_time_string($row['end_time'], -1));
					switch (cmp3($row['start_time'], $midnight) . cmp3($row['end_time'], $midnight_tonight))
					{
						case "> < ":
						case "= < ":
						if ($start_str == $end_str)
							$horaires = $start_str;
						else
							$horaires = $start_str . get_vocab("to") . $end_str;
						break;
						case "> = ":
						$horaires = $start_str . get_vocab("to")."24:00";
						break;
						case "> > ":
						$horaires = $start_str . get_vocab("to")."==>";
						break;
						case "= = ":
						$horaires = $all_day;
						break;
						case "= > ":
						$horaires = $all_day . "==>";
						break;
						case "< < ":
						$horaires = "<==".get_vocab("to") . $end_str;
						break;
						case "< = ":
						$horaires = "<==" . $all_day;
						break;
						case "< > ":
						$horaires = "<==" . $all_day . "==>";
						break;
					}
				}
				else
				{
					switch (cmp3($row['start_time'], $midnight) . cmp3($row['end_time'], $midnight_tonight))
					{
						case "> < ":
						case "= < ":
						$horaires = date(hour_min_format(), $row['start_time']) . get_vocab("to") . date(hour_min_format(), $row['end_time']);
						break;
						case "> = ":
						$horaires = date(hour_min_format(), $row['start_time']) . get_vocab("to")."24:00";
						break;
						case "> > ":
						$horaires = date(hour_min_format(), $row['start_time']) . get_vocab("to")."==>";
						break;
						case "= = ":
						$horaires = $all_day;
						break;
						case "= > ":
						$horaires = $all_day . "==>";
						break;
						case "< < ":
						$horaires = "<==".get_vocab("to") . date(hour_min_format(), $row['end_time']);
						break;
						case "< = ":
						$horaires = "<==" . $all_day;
						break;
						case "< > ":
						$horaires = "<==" . $all_day . "==>";
						break;
					}
				}
                $d[$day_num]["data"][] = contenu_popup($options_popup, 1, $row, $horaires);
                $d[$day_num]["lien"][] = contenu_cellule($options, $overloadFieldList, 1, $row, $horaires);
                if ($row['end_time'] <= $midnight_tonight)
					break;
				$day_num++;
				$t = $midnight = $midnight_tonight;
            }
		}
	}
}
grr_sql_free($res);
// pour le traitement des modules
include $racine."/include/hook.class.php";
// code html de la page
header('Content-Type: text/html; charset=utf-8');
if (!isset($_COOKIE['open']))
{
	header('Set-Cookie: open=true; SameSite=Lax');
}
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
echo '<div id="chargement"></div>'.PHP_EOL; // à éliminer ?
// Debut de la page
echo '<section>'.PHP_EOL;
// Affichage du menu en haut ou à gauche
include("menuHG.php");

if ($debug_flag)
{
	echo '<p>DEBUG: Array of month day data:<p><pre>'.PHP_EOL;
	for ($i = 1; $i <= $days_in_month; $i++)
	{
		if (isset($d[$i]["id"]))
		{
			$n = count($d[$i]["id"]);
			echo 'Day '.$i.' has '.$n.' entries:'.PHP_EOL;
			for ($j = 0; $j < $n; $j++)
				echo "  ID: " . $d[$i]["id"][$j] .
			" Data: " . $d[$i]["data"][$j] . "\n";
		}
	}
	echo '</pre>'.PHP_EOL;
}
// affichage du contenu
if ($_GET['pview'] != 1){
    echo "<div id='planning2'>";
}
else{
	echo '<div id="print_planning">'.PHP_EOL;
}
echo "<table class='mois floatthead table-bordered table-striped'>";
// le titre de la table
echo "<caption>";
// liens mois avant-après et imprimante si page non imprimable
if ((!isset($_GET['pview'])) or ($_GET['pview'] != 1))
{
	echo "\n
	<div class='ligne23'>
		<div class=\"left\">
			<button class=\"btn btn-default btn-xs\" onclick=\"javascript: location.href='month_all2.php?year=$yy&amp;month=$ym&amp;area=$area';\" ><span class=\"glyphicon glyphicon-backward\"></span> ".get_vocab("monthbefore")." </button>
		</div>";
		include "./include/trailer.inc.php";
		echo "<div class=\"right\">
			<button class=\"btn btn-default btn-xs\" onclick=\"javascript: location.href='month_all2.php?year=$ty&amp;month=$tm&amp;area=$area';\" >".get_vocab('monthafter')." <span class=\"glyphicon glyphicon-forward\"></span></button>
		</div>
	</div>";
}
// montrer ou cacher le menu gauche
echo "<div>";
if ((!isset($_GET['pview'])) || ($_GET['pview'] != 1))
{
    echo "<div class=\"left\"> "; // afficher ou cacher le menu
    $mode = Settings::get("menu_gauche");
    $alt = ($mode != 0)? $mode : 1; // il faut bien que le menu puisse s'afficher, par défaut ce sera à gauche sauf choix autre par setting
    echo "<div id='voir'><button class=\"btn btn-default btn-sm\" onClick=\"afficheMenuHG($alt)\" title='".get_vocab('show_left_menu')."'><span class=\"glyphicon glyphicon-chevron-right\"></span></button></div> ";
    echo "<div id='cacher'><button class=\"btn btn-default btn-sm\" onClick=\"afficheMenuHG(0)\" title='".get_vocab('hide_left_menu')."'><span class=\"glyphicon glyphicon-chevron-left\"></span></button></div> "; 
	echo "</div>";
}    
echo '<h4 class="titre"> '. ucfirst($this_area_name).' - '.get_vocab("all_areas").'<br>'.ucfirst(utf8_strftime("%B ", $month_start)).'<a href="year.php?area='.$area.'&amp;year='.$year.'&amp;month='.$month.'" title="'.get_vocab('see_all_the_rooms_for_several_months').'">'.ucfirst(utf8_strftime("%Y", $month_start)).'</a></h4>'.PHP_EOL;
if ($_GET['pview'] != 1)
    echo " <a href=\"month_all.php?year=$year&amp;month=$month&amp;area=$area\" title=\" ".get_vocab('default_room_month_all')." \"><span class='glyphicon glyphicon-refresh'></span></a>";
echo "</div>";
echo "</caption>";
if ($_GET['pview'] == 1 && (isset($_GET['precedent']) && $_GET['precedent'] == 1))
{
	echo "<span id=\"lienPrecedent\">
	<button class=\"btn btn-default btn-xs\" onclick=\"javascript:history.back();\">Précedent</button>
</span>";
}
// ruban des jours
$html_jours ="";
for ($k = 1; $k <= $days_in_month; $k++)
{
    $t2 = mktime(0, 0, 0, $month, $k, $year);
	$cday = date("j", $t2);
	$cweek = date("w", $t2);
	$name_day = ucfirst(utf8_strftime("%a %d", $t2));
	$temp = mktime(0, 0, 0, $month,$cday,$year);
	$jour_cycle = grr_sql_query1("SELECT Jours FROM ".TABLE_PREFIX."_calendrier_jours_cycle WHERE DAY=?","i",[$temp]);
    if ($display_day[$cweek] == 1)
	{
        if (isHoliday($temp)) {$html_jours .= '<td class="ferie cell_hours">';}
        else if (isSchoolHoliday($temp)) {$html_jours .= '<td class="cell_hours vacance">';}
        else {$html_jours .= '<td class="cell_hours">';}
        $html_jours .= $name_day;
        if (Settings::get("jours_cycles_actif") == "Oui" && intval($jour_cycle) > -1)
        {
            if (intval($jour_cycle) > 0)
                $html_jours .= "<br /></><i> ".ucfirst(substr(get_vocab("rep_type_6"), 0, 1)).$jour_cycle."</i>";
            else
            {
                if (strlen($jour_cycle) > 5)
                    $jour_cycle = substr($jour_cycle, 0, 3)."..";
                $html_jours .= "<br /></><i>".$jour_cycle."</i>";
            }
        }
        $html_jours .= "</td>";
	}
}
// entête du planning
echo "<thead><tr>";
echo "<th class='cell_hours'>".get_vocab('rooms');
echo "</th>";
echo $html_jours;
echo "</tr></thead>";
// pied du planning
echo "<tfoot><tr>";
echo tdcell("cell_hours");
echo "</td>";
echo $html_jours;
echo "</tr></tfoot>";
// corps du planning
echo "<tbody>";
if ($nb_ressources == 0){
	echo "<tr><td><strong>".get_vocab("no_rooms_for_area")."</strong></td></tr>";
}
else{
    $li = 0;
    for ($ir = 0; ($row = grr_sql_row_keyed($ressources, $ir)); $ir++) // traitement d'une ressource sur le mois
    {
        $verif_acces_ressource = verif_acces_ressource($user_name, $row["id"]);
        if ($verif_acces_ressource)
        {
            $acces_fiche_reservation = verif_acces_fiche_reservation($user_name, $row["id"]);
            $auth_user_level = authGetUserLevel($user_name, $row["id"]);
            // si la ressource est restreinte, l'utilisateur peut-il réserver ?
            $user_can_book = $row["who_can_book"] || ($auth_user_level > 2) || (authBooking($user_name,$row['id']));
            echo "<tr><th >" .htmlspecialchars($row["room_name"]);
            if (verif_display_fiche_ressource($user_name, $row['id']) && $_GET['pview'] != 1)
            {
                echo '<a href="javascript:centrerpopup(\'view_room.php?id_room='.$row['id'].'\',600,480,\'scrollbars=yes,statusbar=no,resizable=yes\')" title="'.get_vocab("fiche_ressource").'">'.PHP_EOL;
                echo '<span class="glyphcolor glyphicon glyphicon-search"></span></a>'.PHP_EOL;
            }
            if (authGetUserLevel($user_name,$row['id']) > 2 && $_GET['pview'] != 1)
                echo '<a href="./admin/admin_edit_room.php?room='.$row['id'].'"><span class="glyphcolor glyphicon glyphicon-cog"></span></a>'.PHP_EOL;
            if ($row['show_comment'] == "y" && $_GET['pview'] != 1 && ($row['comment_room'] != "") && ($row['comment_room'] != -1))
                echo '<div class="center">',$row['comment_room'],'</div>',PHP_EOL;
            echo "</th>\n";
            $li++;
            for ($k = 1; $k <= $days_in_month; $k++)
            {
                $t2 = mktime(0, 0, 0,$month, $k, $year);
                $cday = date("j", $t2);
                $cweek = date("w", $t2);
                if ($display_day[$cweek] == 1)
                {
                    echo "<td > ";
                    if (est_hors_reservation(mktime(0, 0, 0, $month, $cday, $year), $area))
                    {
                        echo "<div class=\"empty_cell\">";
                        echo "<img src=\"img_grr/stop.png\" alt=\"".get_vocab("reservation_impossible")."\"  title=\"".get_vocab("reservation_impossible")."\" width=\"16\" height=\"16\" class=\"".$class_image."\"  /></div>\n";
                    }
                    else
                    {
                        if (isset($d[$cday]["id"][0])) // il y a une réservation au moins à afficher
                        {
                            echo "<table class='pleine table-bordered'>";
                            $n = count($d[$cday]["id"]);
                            for ($i = 0; $i < $n; $i++)
                            {
                                if ($i == 11 && $n > 12)
                                {
                                    echo " ...\n";
                                    break;
                                }
                                for ($i = 0; $i < $n; $i++)
                                {
                                    if ($d[$cday]["room"][$i] == $row["room_name"])
                                    {
                                        echo "<tr>";
                                        tdcell($d[$cday]["color"][$i]);
                                        echo "<span class=\"small_planning\">";
                                        if ($acces_fiche_reservation)
                                        {
                                            if (Settings::get("display_level_view_entry") == 0)
                                            {
                                                $currentPage = 'month_all2';
                                                $id =   $d[$cday]["id"][$i];
                                                echo "<a title=\"".htmlspecialchars($d[$cday]["data"][$i])."\" data-width=\"675\" onclick=\"request($id,$cday,$month,$year,'all','$currentPage',readData);\" data-rel=\"popup_name\" class=\"poplight lienCellule\">" .$d[$cday]["lien"][$i]."</a>";
                                            }
                                            else
                                            {
                                                echo "<a class=\"lienCellule\" title=\"".htmlspecialchars($d[$cday]["data"][$i])."\" href=\"view_entry.php?id=" . $d[$cday]["id"][$i]."&amp;page=month_all2\">"
                                                .$d[$cday]["lien"][$i]. "</a>";
                                            }
                                        }
                                        else
                                            echo $d[$cday]["lien"][$i];
                                        echo "</span></td></tr>";
                                    }
                                }
                            }
                            echo '</table>';
                        }
                        // la ressource est-elle accessible en réservation ? on affiche le lien vers edit_entry
                        $date_booking = mktime(23,59,0,$month,$k,$year) ; // le jour courant à presque minuit
                        $hour =  date("H",$date_now); // l'heure courante, par défaut
                        if ((($auth_user_level > 1) || (auth_visiteur($user_name, $row["id"]) == 1)) 
                            && (UserRoomMaxBooking($user_name, $row["id"], 1) != 0) 
                            && verif_booking_date($user_name, -1, $row["id"], $date_booking, $date_now, $enable_periods) 
                            && verif_delais_max_resa_room($user_name, $row["id"], $date_booking) 
                            && verif_delais_min_resa_room($user_name, $row["id"], $date_booking, $enable_periods) 
                            && plages_libre_semaine_ressource($row["id"], $month, $cday, $year) 
                            && (($row["statut_room"] == "1") || (($row["statut_room"] == "0") && (authGetUserLevel($user_name,$row["id"]) > 2) )) 
                            && $user_can_book
                            && $_GET['pview'] != 1){
                            if ($enable_periods == 'y')
                                echo '<a href="edit_entry.php?room=',$row["id"],'&amp;period=&amp;year=',$year,'&amp;month=',$month,'&amp;day=',$cday,'&amp;page=month_all2" title="',get_vocab("cliquez_pour_effectuer_une_reservation"),'"><span class="glyphicon glyphicon-plus"></span></a>',PHP_EOL;
                            else
                                echo '<a href="edit_entry.php?room=',$row["id"],'&amp;hour=',$hour,'&amp;minute=0&amp;year=',$year,'&amp;month=',$month,'&amp;day=',$cday,'&amp;page=month_all2" title="',get_vocab("cliquez_pour_effectuer_une_reservation"),'"><span class="glyphicon glyphicon-plus"></span></a>',PHP_EOL;;
                        }
                    }
                    echo "</td>\n";
                }
            }
            echo "</tr>";
        }
    }// fin  du traitement de la ressource
}
grr_sql_free($ressources);
echo "</tbody>";
echo "</table>";
echo " </div>"; // fermeture du div planning2
echo  "<div id=\"popup_name\" class=\"popup_block\" ></div>";
if ($_GET['pview'] != 1)
{
	echo "<div id=\"toTop\"><b>".get_vocab('top_of_page').'</b>'.PHP_EOL;
    bouton_retour_haut ();
    echo " </div>";
}
affiche_pop_up(get_vocab("message_records"),"user");
?>
<script type="text/javascript">
	$(document).ready(function(){
        $("#popup_name").draggable({containment: "#container"});
		$("#popup_name").resizable();
        afficheMenuHG(0);
        if ( $(window).scrollTop() == 0 )
            $("#toTop").hide(1);
	});
</script>
<?php 
// a priori on n'affiche pas le menu en page month_all2
end_page();
?>