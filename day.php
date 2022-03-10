<?php
/**
 * day.php
 * Permet l'affichage de la page planning en mode d'affichage "jour".
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2022-02-08 12:01$
 * @author    Laurent Delineau & JeromeB & Yan Naessens
 * @copyright Copyright 2003-2022 Team DEVOME - JeromeB
 * @link      http://www.gnu.org/licenses/licenses.html
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 */
$grr_script_name = "day.php";

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

// paramètres temporels
$day = isset($_GET['day']) ? intval($_GET['day']) : date("d");
$month = isset($_GET['month']) ? intval($_GET['month']) : date("m");
$year = isset($_GET['year']) ? intval($_GET['year']) : date("Y");

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

// langue utilisée
$langue= isset($_SESSION['default_language'])? $_SESSION['default_language']: Settings::get('default_language');
// $room_back sert à pallier l'absence de page day_all => si room_back contient 'all', il ne faut pas passer room en paramètre
$room_back = isset($_GET['room']) ? intval($_GET['room']) : 'all';
// options pour l'affichage
$opt = array('horaires','beneficiaire','short_desc','description','create_by','type','participants');
$options = decode_options(Settings::get('cell_day'),$opt);
$options_popup = decode_options(Settings::get('popup_day'),$opt);
// mode d'affichage du menu
$mode = Settings::get("menu_gauche");
$alt = $mode;
if ($mode == 0) $alt = 1; // il faut bien que le menu puisse s'afficher, par défaut ce sera à gauche sauf choix autre par setting
// calcul des données à afficher
$date_now = time();
// jour précédent
$ind = 1;
$test = 0;
$i = 0;
while (($test == 0) && ($ind <= 7))
{
	$i = mktime(0, 0, 0, $month, $day - $ind, $year);
	$test = $display_day[date("w",$i)];
	$ind++;
}
$yy = date("Y", $i);
$ym = date("m", $i);
$yd = date("d", $i);
// jour d'un cycle ?
$i = mktime(0, 0, 0, $month, $day, $year);
$jour_cycle = grr_sql_query1("SELECT Jours FROM ".TABLE_PREFIX."_calendrier_jours_cycle WHERE day='$i'");
// jour suivant
$ind = 1;
$test = 0;
while (($test == 0) && ($ind <= 7))
{
	$i = mktime(0, 0, 0, $month, $day + $ind, $year);
	$test = $display_day[date("w", $i)];
	$ind++;
}
$ty = date("Y",$i);
$tm = date("m",$i);
$td = date("d",$i);
// début et fin du jour
$am7 = mktime($morningstarts, 0, 0, $month, $day, $year);
$pm7 = mktime($eveningends, $eveningends_minutes, 0, $month, $day, $year);
$this_area_name = grr_sql_query1("SELECT area_name FROM ".TABLE_PREFIX."_area WHERE id='".protect_data_sql($area)."'"); // nom du domaine
// les réservations associées à notre recherche, ce jour dans cette ressource ou ce domaine
$sql = "SELECT start_time, end_time, ".TABLE_PREFIX."_entry.id, name, beneficiaire, ".TABLE_PREFIX."_room.room_name,type, statut_entry, ".TABLE_PREFIX."_entry.description, ".TABLE_PREFIX."_entry.option_reservation, ".TABLE_PREFIX."_room.delais_option_reservation, ".TABLE_PREFIX."_entry.moderate, beneficiaire_ext, clef, ".TABLE_PREFIX."_entry.courrier, ".TABLE_PREFIX."_type_area.type_name, ".TABLE_PREFIX."_entry.overload_desc,".TABLE_PREFIX."_entry.room_id, ".TABLE_PREFIX."_entry.create_by, ".TABLE_PREFIX."_entry.nbparticipantmax 
FROM ".TABLE_PREFIX."_entry, ".TABLE_PREFIX."_room, ".TABLE_PREFIX."_area, ".TABLE_PREFIX."_type_area
WHERE
".TABLE_PREFIX."_entry.room_id=".TABLE_PREFIX."_room.id AND ".TABLE_PREFIX."_area.id = ".TABLE_PREFIX."_room.area_id";
if (isset($room)) 
    $sql .= " AND ".TABLE_PREFIX."_room.id = '".$room."' ";
else 
    $sql .= " AND ".TABLE_PREFIX."_room.area_id = '".$area."' ";
$sql .= " AND ".TABLE_PREFIX."_type_area.type_letter = ".TABLE_PREFIX."_entry.type 
AND start_time < ".($pm7+$resolution)." AND end_time > ".$am7." 
ORDER BY start_time";
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
	$row[15]: Type_name
    $row[16]: overload fields description
    $row[17]: room_id
    $row[18]: create_by
    $row[19]: nbparticipantmax
*/
$res = grr_sql_query($sql);
if (!$res)
	echo grr_sql_error();
else
{
    $overloadFieldList = mrbsOverloadGetFieldslist($area);
    $cellules = array();
    $compteur = array();
    $today = array();
    foreach($res as $row) 
	{
		$start_t = max(round_t_down($row["start_time"], $resolution, $am7), $am7);
		$end_t = min(round_t_up($row["end_time"], $resolution, $am7) - $resolution, $pm7);
		$cellules[$row["id"]] = ($end_t - $start_t) / $resolution + 1; // à vérifier YN le 14/10/18
		$compteur[$row["id"]] = 0;
		for ($t = $start_t; $t <= $end_t; $t += $resolution) // à vérifier YN le 14/10/18
		{
			$today[$row["room_id"]][$t]["id"]		= $row["id"];
			$today[$row["room_id"]][$t]["color"]	= $row["type"];
			$today[$row["room_id"]][$t]["data"]		= "";
			$today[$row["room_id"]][$t]["who"]		= "";
		}
        $horaires = "";
        if ($enable_periods != 'y') {
            $heure_fin = date('H:i',min($pm7,$row["end_time"]));
            if ($heure_fin == '00:00') {$heure_fin = '24:00';}
            $horaires = date('H:i', max($am7,$row["start_time"])).get_vocab("to").$heure_fin;
        }
		if ($row["start_time"] < $am7)
		{
            $today[$row["room_id"]][$am7]["data"] = contenu_cellule($options, $overloadFieldList, 1, $row, $horaires);
			if ($settings->get("display_info_bulle") == 1)
                $today[$row["room_id"]][$am7]["who"] = contenu_popup($options_popup, 1, $row, $horaires);
		}
		else
		{
			$today[$row["room_id"]][$start_t]["data"] = contenu_cellule($options, $overloadFieldList, 1, $row, $horaires);
            if ($settings->get("display_info_bulle") == 1)
                $today[$row["room_id"]][$start_t]["who"] = contenu_popup($options_popup, 1, $row, $horaires);
		}
	}
}
grr_sql_free($res);

// Détermination des ressources à afficher
if($room_back != 'all'){
	$sql = "SELECT room_name, capacity, id, description, statut_room, show_fic_room, delais_option_reservation, moderate, who_can_book FROM ".TABLE_PREFIX."_room WHERE id = '".protect_data_sql($room_back)."' ";
}
else $sql = "SELECT room_name, capacity, id, description, statut_room, show_fic_room, delais_option_reservation, moderate, who_can_book FROM ".TABLE_PREFIX."_room WHERE area_id='".protect_data_sql($area)."' ORDER BY order_display, room_name";
$ressources = grr_sql_query($sql);
if (!$ressources)
	fatal_error(0, grr_sql_error());
// vérification de l'existence de ressources à afficher
$alerte = '';
if (grr_sql_count($ressources) == 0){
    $alerte = get_vocab("no_rooms_for_area");
}
else{
    // vérification des droits d'accès
    $acces = FALSE;
    foreach($ressources as $row){
        $acces = $acces || verif_acces_ressource($user_name, $row['id']);
    }
    if (!$acces){
        $alerte = get_vocab("droits_insuffisants_pour_voir_ressources");
    }
}
// pour le traitement des modules
include $racine."/include/hook.class.php";
// code HTML
header('Content-Type: text/html; charset=utf-8');
if (!isset($_COOKIE['open']))
{
	header('Set-Cookie: open=true; SameSite=Lax');
}
echo '<!DOCTYPE html>'.PHP_EOL;
echo '<html lang="fr">'.PHP_EOL;
// section <head>
if ($type_session == "with_session")
    echo pageHead2(Settings::get("company"),"with_session"); // voir le lancement de floatthead
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
// planning
if ($_GET['pview'] != 1){
    echo "<div id='planning2'>";
}
else{
	echo '<div id="print_planning">'.PHP_EOL;
}
echo "<table class='jour floatthead table-striped table-bordered'>";
echo "<caption>";
$class = "";
$title = "";
if ($settings->get("show_holidays") == "Oui")
{   
    $now = mktime(0,0,0,$month,$day,$year);
    if (isHoliday($now)){
        $class .= 'ferie ';
    }
    elseif (isSchoolHoliday($now)){
        $class .= 'vacance ';
    }
}
echo '<div class="'.$class.'">'.PHP_EOL;
if ((!isset($_GET['pview'])) || ($_GET['pview'] != 1))
{ // menu de navigation dans les jours avant/après en tête du planning
    echo "<div class='ligne23'>";
    if ($room_back == 'all'){
        echo '<div class="left">',PHP_EOL,'<button class="btn btn-default btn-xs" onclick="charger();javascript: location.href=\'day.php?year='.$yy.'&amp;month='.$ym.'&amp;day='.$yd.'&amp;area='.$area.'\';"> <span class="glyphicon glyphicon-backward"></span> ',get_vocab("daybefore"),'</button>','</div>',PHP_EOL;
    }
    else {
        echo '<div class="left">',PHP_EOL,'<button class="btn btn-default btn-xs" onclick="charger();javascript: location.href=\'day.php?year='.$yy.'&amp;month='.$ym.'&amp;day='.$yd.'&amp;area='.$area.'&amp;room='.$room_back.'\';"> <span class="glyphicon glyphicon-backward"></span> ',get_vocab("daybefore"),'</button>','</div>',PHP_EOL;
    }    
    include "include/trailer.inc.php";
    if ($room_back == 'all'){
        echo '<div class="right">',PHP_EOL,'<button class="btn btn-default btn-xs" onclick="charger();javascript: location.href=\'day.php?year='.$ty.'&amp;month='.$tm.'&amp;day='.$td.'&amp;area='.$area.'\';">  '.get_vocab('dayafter').'  <span class="glyphicon glyphicon-forward"></span></button>','</div>',PHP_EOL;
    }
    else{
        echo '<div class="right">',PHP_EOL,'<button class="btn btn-default btn-xs" onclick="charger();javascript: location.href=\'day.php?year='.$ty.'&amp;month='.$tm.'&amp;day='.$td.'&amp;area='.$area.'&amp;room='.$room_back.'\';">  '.get_vocab('dayafter').'  <span class="glyphicon glyphicon-forward"></span></button>','</div>',PHP_EOL;
    }
    echo "</div>".PHP_EOL;
}
echo "<div>".PHP_EOL;
if ((!isset($_GET['pview'])) || ($_GET['pview'] != 1))
{ // afficher ou cacher le menu
    echo "<div class=\"left\"> ";
    echo "<div id='voir'><button class=\"btn btn-default btn-sm\" onClick=\"afficheMenuHG($alt)\" title='".get_vocab('show_left_menu')."'><span class=\"glyphicon glyphicon-chevron-right\"></span></button></div> ";
    echo "<div id='cacher'><button class=\"btn btn-default btn-sm\" onClick=\"afficheMenuHG(0)\" title='".get_vocab('hide_left_menu')."'><span class=\"glyphicon glyphicon-chevron-left\"></span></button></div> "; 
    echo "</div>";
}
echo '<h4>' . ucfirst($this_area_name).' - '.get_vocab("all_areas");
if ($settings->get("jours_cycles_actif") == "Oui" && intval($jour_cycle) >- 1)
{
    if (intval($jour_cycle) > 0)
        echo ' - '.get_vocab("rep_type_6")." ".$jour_cycle;
    else
        echo ' - '.$jour_cycle;
}
echo '<br>'.ucfirst(utf8_strftime($dformat, $am7)).'</h4>'.PHP_EOL;
if (isset($_GET['precedent']))
{
    if ($_GET['pview'] == 1 && $_GET['precedent'] == 1)
        echo '<span id="lienPrecedent"><button class="btn btn-default btn-xs" onclick="charger();javascript:history.back();">Précedent</button></span>'.PHP_EOL;
}
echo "</div>";
echo '</div>'.PHP_EOL;
echo "</caption>";
if ($alerte != ''){// pas de ressource accessible ou existante
    echo '<tbody><tr><td><strong>'.$alerte.'</strong></td></tr></tbody>';
}
else{
    echo "<thead>";
    echo '<tr>';
    tdcell("cell_hours","8"); 
    if ($enable_periods == 'y')
        echo get_vocab("period");
    else
        echo get_vocab("time");
    echo  '</td>'.PHP_EOL;

    if(grr_sql_count($ressources) != 0)
        $room_column_width = (int)(90 / grr_sql_count($ressources));
    else
        $room_column_width = 90;
    $nbcol = 0;
    $rooms = array();
    $a = 0;
    for ($i = 0; ($row = grr_sql_row_keyed($ressources, $i)); $i++)
    {
        $id_room[$i] = $row["id"];
        $nbcol++;
        if (verif_acces_ressource($user_name, $id_room[$i]))
        {
            $room_name[$i] = $row["room_name"];
            $statut_room[$id_room[$i]] =  $row["statut_room"];
            $statut_moderate[$id_room[$i]] =  $row["moderate"];
            $who_can_book[$id_room[$i]] = $row["who_can_book"];
            $acces_fiche_reservation = verif_acces_fiche_reservation($user_name, $id_room[$i]);
            if ($row['1']  && $_GET['pview'] != 1)
                $temp = '<br /><span class="small">('.$row["capacity"].' '.($row["capacity"] > 1 ? get_vocab("number_max2") : get_vocab("number_max")).')</span>'.PHP_EOL;
            else
                $temp = '';
            if ($statut_room[$id_room[$i]] == "0"  && $_GET['pview'] != 1)
                $temp .= '<br /><span class="texte_ress_tempo_indispo">'.get_vocab("ressource_temporairement_indisponible").'</span>'.PHP_EOL;
            if ($statut_moderate[$id_room[$i]] == "1"  && $_GET['pview'] != 1)
                $temp .= '<br /><span class="texte_ress_moderee">'.get_vocab("reservations_moderees").'</span>'.PHP_EOL;
            $a++;
            echo '<th style="width:'.$room_column_width.'%;" ';
            if ($statut_room[$id_room[$i]] == "0")
                echo 'class="avertissement" ';
            echo '>'.PHP_EOL;
            echo '<a id="afficherBoutonSelection'.$a.'" class="lienPlanning" href="#" onclick="afficherMoisSemaine('.$a.')" style="display:inline;">'.htmlspecialchars($row["room_name"]).'</a>'.PHP_EOL;
            echo '<a id="cacherBoutonSelection'.$a.'" class="lienPlanning" href="#" onclick="cacherMoisSemaine('.$a.')" style="display:none;">'.htmlspecialchars($row["room_name"]).'</a>'.PHP_EOL;
            if (htmlspecialchars($row["description"]).$temp != '')
            {
                if (htmlspecialchars($row["description"]) != '')
                    $saut = '<br />';
                else
                    $saut = '';
                echo $saut.htmlspecialchars($row["description"]).$temp."\n";
            }
            echo '<br />';
            if (verif_display_fiche_ressource($user_name, $id_room[$i]) && $_GET['pview'] != 1)
                echo '<a href="javascript:centrerpopup(\'view_room.php?id_room='.$id_room[$i].'\',600,480,\'scrollbars=yes,statusbar=no,resizable=yes\')" title="'.get_vocab("fiche_ressource").'">
            <span class="glyphcolor glyphicon glyphicon-search"></span></a>'.PHP_EOL;
            if (authGetUserLevel($user_name,$id_room[$i]) > 2 && $_GET['pview'] != 1)
                echo '<a href="./admin/admin_edit_room.php?room='.$id_room[$i].'"><span class="glyphcolor glyphicon glyphicon-cog"></span></a><br/>'.PHP_EOL;
            affiche_ressource_empruntee($id_room[$i]);
            echo '<span id="boutonSelection'.$a.'" style="display:none;">'.PHP_EOL;
            echo '<input type="button" class="btn btn-default btn-xs" title="'.htmlspecialchars(get_vocab("see_week_for_this_room")).'" onclick="charger();javascript: location.href=\'week.php?year='.$year.'&amp;month='.$month.'&amp;day='.$day.'&amp;room='.$id_room[$i].'\';" value="'.get_vocab('week').'"/>'.PHP_EOL;
            echo '<input type="button" class="btn btn-default btn-xs" title="'.htmlspecialchars(get_vocab("see_month_for_this_room")).'" onclick="charger();javascript: location.href=\'month.php?year='.$year.'&amp;month='.$month.'&amp;day='.$day.'&amp;room='.$id_room[$i].'\';" value="'.get_vocab('month').'"/>'.PHP_EOL;
            echo '</span>'.PHP_EOL;
            if (htmlspecialchars($row["description"]).$temp != '')
            {
                if (htmlspecialchars($row["description"]) != '')
                    $saut = '<br />';
                else
                    $saut = '';
            }
            $rooms[] = $row["id"];
            $delais_option_reservation[$row["id"]] = $row["delais_option_reservation"];
            echo '</th>'.PHP_EOL;
        }
    }
    if (count($rooms) == 0)
    {
        echo '<br /><h1>'.get_vocab("droits_insuffisants_pour_voir_ressources").'</h1><br />'.PHP_EOL;
        die();
    }
    echo '</tr>'.PHP_EOL;
    echo "</thead>"; // fin de l'affichage des ressources
    echo "<tbody>";
    $tab_ligne = 3;
    $iii = 0;
    if ($enable_periods == 'y'){$pm7++;} // correctif pour domaine sur créneaux prédéfinis

    for ($t = $am7; $t < $pm7; $t += $resolution)
    {
        echo '<tr>'.PHP_EOL;
        if ($iii % 2 == 1)
            tdcell("cell_hours");
        else
            tdcell("cell_hours2");
        $iii++;
        if ($enable_periods == 'y')
        {
            $time_t = date("i", $t);
            $time_t_stripped = preg_replace( "/^0/", "", $time_t );
            echo $periods_name[$time_t_stripped] .'</td>'.PHP_EOL;
        }
        else
        {
            echo affiche_heure_creneau($t,$resolution).'</td>'.PHP_EOL;
        }
        foreach($rooms as $key=>$room)
        {
            if (verif_acces_ressource($user_name, $room))
            {
                $authLevel = authGetUserLevel($user_name,$room);
                $user_can_book = $who_can_book[$room] || ($authLevel > 2) || (authBooking($user_name,$room));
                if (isset($today[$room][$t]["id"]))
                {
                    $id    = $today[$room][$t]["id"];
                    $color = $today[$room][$t]["color"];
                    $descr = $today[$room][$t]["data"];
                }
                else
                    unset($id);
                if ((isset($id)) && (!est_hors_reservation(mktime(0, 0, 0, $month, $day, $year), $area)))
                    $c = $color;
                else if ($statut_room[$room] == "0")
                    $c = "avertissement";
                else
                    $c = "empty_cell";
                if ((isset($id)) && (!est_hors_reservation(mktime(0, 0, 0, $month, $day, $year), $area)))
                {
                    if ( $compteur[$id] == 0 )
                    {
                        if ($cellules[$id] != 1)
                        {
                            if (isset($today[$room][$t + ($cellules[$id] - 1) * $resolution]["id"]))
                            {
                                $id_derniere_ligne_du_bloc = $today[$room][$t + ($cellules[$id] - 1) * $resolution]["id"];
                                if ($id_derniere_ligne_du_bloc != $id)
                                    $cellules[$id] = $cellules[$id]-1;
                            }
                        }
                        tdcell_rowspan ($c, $cellules[$id]);
                    }
                    $compteur[$id] = 1;
                }
                else
                    tdcell($c);
                if ((!isset($id)) || (est_hors_reservation(mktime(0, 0, 0, $month, $day, $year), $area)))
                {
                    $hour = date("H", $t);
                    $minute = date("i", $t);
                    $date_booking = mktime($hour, $minute, 0, $month, $day, $year);
                    if ($enable_periods == 'y')
                        $date_booking = mktime(23,59,0,$month,$day,$year);
                    if (est_hors_reservation(mktime(0, 0, 0, $month, $day, $year), $area))
                    {
                        echo '<img src="img_grr/stop.png" alt="'.get_vocab("reservation_impossible").'"  title="'.get_vocab("reservation_impossible").'" width="16" height="16" class="'.$class_image.'" />'.PHP_EOL;
                    }
                    else // plage libre
                    {
                        if ((($authLevel > 1) || (auth_visiteur($user_name, $room) == 1)) 
                            && (UserRoomMaxBooking($user_name, $room, 1) != 0) 
                            && verif_booking_date($user_name, -1, $room, $date_booking, $date_now, $enable_periods) 
                            && verif_delais_max_resa_room($user_name, $room, $date_booking) 
                            && verif_delais_min_resa_room($user_name, $room, $date_booking, $enable_periods) 
                            && (($statut_room[$room] == "1") || (($statut_room[$room] == "0") && ($authLevel > 2) )) 
                            && $user_can_book
                            && $_GET['pview'] != 1)
                        {
                            if ($enable_periods == 'y')
                            {
                                echo '<a href="edit_entry.php?room='.$room.'&amp;period='.$time_t_stripped.'&amp;year='.$year.'&amp;month='.$month.'&amp;day='.$day.'&amp;page=day&amp;room_back='.$room_back.'" title="'.get_vocab("cliquez_pour_effectuer_une_reservation").'" ><span class="glyphicon glyphicon-plus"></span></a>'.PHP_EOL;
                            }
                            else
                            {
                                echo '<a href="edit_entry.php?room='.$room.'&amp;hour='.$hour.'&amp;minute='.$minute.'&amp;year='.$year.'&amp;month='.$month.'&amp;day='.$day.'&amp;page=day&amp;room_back='.$room_back.'" title="'.get_vocab("cliquez_pour_effectuer_une_reservation").'" ><span class="glyphicon glyphicon-plus"></span></a>'.PHP_EOL;
                            }
                        }
                        else
                        {
                            echo ' ';
                        }
                    }
                    echo '</td>'.PHP_EOL;
                }
                else //if ($descr != "")
                {
                    if (($statut_room[$room] == "1") || (($statut_room[$room] == "0") && ($authLevel > 2) ))
                    {
                        if ($acces_fiche_reservation)
                        {
                            if ($settings->get("display_level_view_entry") == 0)
                            {
                                $currentPage = 'day';
                                echo '<a title="'.htmlspecialchars($today[$room][$t]["who"]).'" data-width="675" onclick="request('.$id.','.$day.','.$month.','.$year.',\''.$room_back.'\',\''.$currentPage.'\',readData);" data-rel="popup_name" class="poplight lienCellule">'.$descr.PHP_EOL;
                            }
                            else
                            {
                                echo '<a class="lienCellule" title="',htmlspecialchars($today[$room][$t]["who"]),'" href="view_entry.php?id=',$id,'&amp;day=',$day,'&amp;month=',$month,'&amp;year=',$year,'&amp;page=day&amp;room_back=',$room_back,' ">',$descr;
                            }
                        }
                        else
                        {
                            echo ' '.$descr;
                        }
                    }
                    else
                    {
                        echo ' '.$descr;
                    }
                    if ($acces_fiche_reservation)
                        echo '</a>'.PHP_EOL;
                    echo '</td>'.PHP_EOL;
                }
            }
        }
        echo '</tr>'.PHP_EOL;
        reset($rooms);
    }
    echo "</tbody>";
}
echo '</table>'.PHP_EOL;

grr_sql_free($res);
if ($_GET['pview'] != 1)
{
	echo '<div id="toTop">'.PHP_EOL;
	echo '<b>'.get_vocab('top_of_page').'</b>'.PHP_EOL;
	bouton_retour_haut ();
	echo '</div>'.PHP_EOL;
}
echo '</div>'.PHP_EOL; // fin planning2
affiche_pop_up(get_vocab('message_records'), 'user');
unset($row);
echo '<div id="popup_name" class="popup_block"></div>'.PHP_EOL;
echo "</section>";
?>
<script type="text/javascript">
	$(document).ready(function(){
        afficheMenuHG(<?php echo $mode; ?>);
		$('table.table-bordered td').each(function(){
			var $row = $(this);
			var height = $row.height();
			var h2 = $row.find('a').height();
			$row.find('a').css('min-height', height);
			$row.find('a').css('padding-top', height/2 - h2/2);

		});
        $("#popup_name").draggable({containment: "#container"});
		$("#popup_name").resizable();
        if ( $(window).scrollTop() == 0 )
            $("#toTop").hide(1);
	});
</script>
</body>
</html>