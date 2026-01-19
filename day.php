<?php
/*
 * day.php
 * Permet l'affichage de la page planning en mode d'affichage "jour".
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2026-01-19 16:35$
 * @author    Laurent Delineau & JeromeB & Yan Naessens
 * @copyright Copyright 2003-2026 Team DEVOME - JeromeB
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

// Initialisation des variables
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
$adm = 0;
$racine = "./";
$racineAd = "./admin/";

// Lien de retour
$back = (isset($_SERVER['HTTP_REFERER']))? htmlspecialchars_decode($_SERVER['HTTP_REFERER'], ENT_QUOTES) : page_accueil() ;
// Type de session
$user_name = getUserName();
if ((Settings::get("authentification_obli") == 0) && ($user_name == ''))
	$type_session = "no_session";
else
	$type_session = "with_session";

if (!($desactive_VerifNomPrenomUser))
    $desactive_VerifNomPrenomUser = 'n';
// On vérifie que les noms et prénoms ne sont pas vides
VerifNomPrenomUser($type_session);

//Récupération des données concernant l'affichage du planning du domaine
if($area >0){
  $test = grr_sql_query1("SELECT id FROM ".TABLE_PREFIX."_area WHERE id = ?","i",[$area]);
  if($test > 0)
    get_planning_area_values($area);
  else{
    $msg = get_vocab('unknown_area');
    $area = get_default_area($id_site);
  }
}
else{
  $msg = get_vocab('unknown_room');
  $area = get_default_area($id_site);
}
if((isset($msg))&&($msg != "")) // les paramètres ne sont pas valides, on renvoie alors vers une page par défaut
{
  $lien = "day.php?area=".$area."&day=".$day."&month=".$month."&year=".$year;
  echo "<script type='text/javascript'>
        alert('$msg');
        document.location.href='$lien';
    </script>";
  echo "<p><br/>";
  echo $msg."<a href='day.php'>".get_vocab("link")."</a>";
  echo "</p>";
  die();
}

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
$now = mktime(0,0,0,$month,$day,$year);
$i = $now;
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
// nom du domaine
$this_area_name = grr_sql_query1("SELECT area_name FROM ".TABLE_PREFIX."_area WHERE id='".protect_data_sql($area)."'");
// vérifie si la date est dans la période réservable
if (check_begin_end_bookings($day, $month, $year)){
    $date = utf8_strftime($dformat,mktime(12,0,0,$month,$day,$year));
    $alerte = '<h2>'.get_vocab("nobookings").' '.$date.'</h2>';
    $alerte .= '<p>'.get_vocab("begin_bookings").'<b>'.affiche_date(Settings::get("begin_bookings")).'</b></p>';
	$alerte .= '<p>'.get_vocab("end_bookings").'<b>'.affiche_date(Settings::get("end_bookings")).'</b></p>';
}
else{
    // Détermination des ressources à afficher
    if($room_back != 'all')
        $sql = "SELECT room_name, capacity, id, description, statut_room, show_fic_room, delais_option_reservation, moderate, who_can_book, comment_room, show_comment FROM ".TABLE_PREFIX."_room WHERE id = '".protect_data_sql($room_back)."' ";
    else 
        $sql = "SELECT room_name, capacity, id, description, statut_room, show_fic_room, delais_option_reservation, moderate, who_can_book, comment_room, show_comment FROM ".TABLE_PREFIX."_room WHERE area_id='".protect_data_sql($area)."' ORDER BY order_display, room_name";
    $ressources = grr_sql_query($sql);
    if (!$ressources)
        $alerte = get_vocab('erreur_lecture_BDD');
    elseif (grr_sql_count($ressources) == 0){ // vérification de l'existence de ressources à afficher
        $alerte = get_vocab("no_rooms_for_area");
    }
    else{// vérification des droits d'accès
        $rooms = array();
        foreach($ressources as $row){
            if(verif_acces_ressource($user_name, $row['id']))
                $rooms[] = intval($row['id']);
        }
        // $rooms est un tableau contenant les id des ressources à afficher, ordonnées selon l'ordre d'affichage
        if (count($rooms) == 0)
            $alerte = get_vocab("droits_insuffisants_pour_voir_ressources");
        else{
            $ress = "(";
            foreach($rooms as $room_id){
                if($ress !="(")
                    $ress .= ",".$room_id;
                else 
                    $ress .= $room_id;
            }
            $ress .= ")";
        // recherche des réservations dans ces ressources à la date étudiée
        // les paramètres $ress, $pm7, $resolution et $am7 ayant été calculés ou mis au format entier, ils devraient être sûrs
            $sql = "SELECT start_time, end_time, ".TABLE_PREFIX."_entry.id, name, beneficiaire, ".TABLE_PREFIX."_room.room_name,type, statut_entry, ".TABLE_PREFIX."_entry.description, ".TABLE_PREFIX."_entry.option_reservation, ".TABLE_PREFIX."_room.delais_option_reservation, ".TABLE_PREFIX."_entry.moderate, beneficiaire_ext, clef, ".TABLE_PREFIX."_entry.courrier, ".TABLE_PREFIX."_entry.overload_desc,".TABLE_PREFIX."_entry.room_id, ".TABLE_PREFIX."_entry.create_by, ".TABLE_PREFIX."_entry.nbparticipantmax 
            FROM (".TABLE_PREFIX."_entry JOIN ".TABLE_PREFIX."_room ON ".TABLE_PREFIX."_entry.room_id=".TABLE_PREFIX."_room.id) 
            WHERE ".TABLE_PREFIX."_room.id IN ".$ress." AND start_time < ".($pm7+$resolution)." AND end_time > ".$am7."
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
    $row[15]: Type_name , supprimé
    $row[16]: overload fields description
    $row[17]: room_id
    $row[18]: create_by
    $row[19]: nbparticipantmax
*/
            $res = grr_sql_query($sql);
            if(!$res){
                echo grr_sql_error();
                $alerte = get_vocab('erreur_lecture_BDD');
            }
            else{
                $overloadFieldList = mrbsOverloadGetFieldslist($area);
                $statut_room = array();
                foreach($ressources as $row){
                    $statut_room[$row['id']] = (int)$row['statut_room'];
                }
                $acces_fiche_reservation = array();
                foreach($ressources as $row){
                    $acces_fiche_reservation[$row['id']] = verif_acces_fiche_reservation($user_name, $row['id']);
                }
                $authLevel = array();
                foreach($ressources as $row){
                    $authLevel[$row['id']] = authGetUserLevel($user_name,$row['id']);
                }
                $today = array();
                foreach($res as $row) 
                {
                    $start_t = max(round_t_down($row["start_time"], $resolution, $am7), $am7);
                    $horaires = "";
                    if ($enable_periods != 'y') {
                        $end_t = min(round_t_up($row["end_time"], $resolution, $am7), $pm7);
                        $heure_fin = date('H:i',$end_t);
                        if ($heure_fin == '00:00') {$heure_fin = '24:00';}
                        $horaires = date('H:i',$start_t).get_vocab("to").$heure_fin;
                    }
                    else{
                        $end_t = min(round_t_up($row["end_time"], $resolution, $am7), $pm7 + 60);
                    }
                    $today[$row["room_id"]][$start_t]["id"]		= $row["id"];
                    $today[$row["room_id"]][$start_t]["color"]	= $row["type"];
                    $today[$row["room_id"]][$start_t]["slots"]  = ($end_t - $start_t) / $resolution; // à vérifier
                    //$today[$row["room_id"]][$start_t]["data"] = contenu_cellule($options, $overloadFieldList, 1, $row, $horaires);
                    $descr = contenu_cellule($options, $overloadFieldList, 1, $row, $horaires);
                    if ($settings->get("display_info_bulle") == 1)
                        $today[$row["room_id"]][$start_t]["who"] = contenu_popup($options_popup, 1, $row, $horaires);
                    else 
                        $today[$row["room_id"]][$start_t]["who"] = "";
                    
                    if (($statut_room[$row['room_id']] == "1") || (($statut_room[$row['room_id']] == "0") && ($authLevel[$row['room_id']] > 2) ))
                    {
                        if ($acces_fiche_reservation[$row['room_id']])
                        {
                            if ($settings->get("display_level_view_entry") == 0)
                            {
                                $temp = '<a title="'.htmlspecialchars($today[$row['room_id']][$start_t]["who"]).'" data-width="675" onclick="request('.$row['id'].','.$day.','.$month.','.$year.',\''.$room_back.'\',\'day\',readData);" data-rel="popup_name" class="poplight lienCellule">'.$descr.PHP_EOL;
                            }
                            else
                            {
                                $temp = '<a class="lienCellule" title="'.htmlspecialchars($today[$row['room_id']][$start_t]["who"]).'" href="view_entry.php?id='.$row['id'].'&amp;day='.$day.'&amp;month='.$month.'&amp;year='.$year.'&amp;page=day&amp;room_back='.$room_back.' ">'.$descr;
                            }
                        }
                        else
                        {
                            $temp = ' '.$descr;
                        }
                    }
                    else
                    {
                        $temp = ' '.$descr;
                    }
                    if ($acces_fiche_reservation)
                        $temp .= '</a>'.PHP_EOL;
                    $today[$row["room_id"]][$start_t]["data"] = $temp;
                
                }
                grr_sql_free($res);
                // complète le tableau $today avec une case vide ou un lien pour réserver
                // parcours par ressource en suivant les débuts des créneaux
                $who_can_book = array();
                foreach($ressources as $row){
                    $who_can_book[$row['id']] = $row["who_can_book"];
                }
                $hors_reservation = est_hors_reservation($now, $area);
                foreach($rooms as $room_id){
                    $user_can_book = $who_can_book[$room_id] || ($authLevel[$room_id] > 2) || (authBooking($user_name,$room_id));
                    $t = $am7;
                    while($t <= $pm7){
                        if(isset($today[$room_id][$t])) // il existe une réservation à cette heure
                            $t += $today[$room_id][$t]["slots"] * $resolution;
                        else{
                            if($statut_room[$room_id] == "0")
                                $today[$room_id][$t]["color"] = "avertissement";
                            else 
                                $today[$room_id][$t]["color"] = "empty_cell";
                            if($hors_reservation)
                            {
                                $temp = '<img src="img_grr/stop.png" alt="'.get_vocab("reservation_impossible").'"  title="'.get_vocab("reservation_impossible").'" width="16" height="16" class="'.$class_image.'" />'.PHP_EOL;
                            }
                            else // plage libre
                            {
                                if ((($authLevel[$room_id] > 1) || (auth_visiteur($user_name, $room_id) == 1)) 
                                    && (UserRoomMaxBooking($user_name, $room_id, 1) != 0) 
                                    && verif_booking_date($user_name, -1, $room_id, $t, $date_now, $enable_periods) 
                                    && verif_delais_max_resa_room($user_name, $room_id, $t) 
                                    && verif_delais_min_resa_room($user_name, $room_id, $t, $enable_periods) 
                                    && (($statut_room[$room_id] == "1") || (($statut_room[$room_id] == "0") && ($authLevel[$room_id]> 2) )) 
                                    && $user_can_book
                                    && $_GET['pview'] != 1)
                                {
                                    if ($enable_periods == 'y')
                                    {
                                        $time_t = date("i", $t);
                                        $time_t_stripped = preg_replace( "/^0/", "", $time_t );
                                        $temp = '<a href="edit_entry.php?room='.$room_id.'&amp;period='.$time_t_stripped.'&amp;year='.$year.'&amp;month='.$month.'&amp;day='.$day.'&amp;page=day&amp;room_back='.$room_back.'" title="'.get_vocab("cliquez_pour_effectuer_une_reservation").'" ><span class="glyphicon glyphicon-plus"></span></a>'.PHP_EOL;
                                    }
                                    else
                                    {
                                        $hour = date("H", $t);
                                        $minute = date("i", $t);
                                        $temp = '<a href="edit_entry.php?room='.$room_id.'&amp;hour='.$hour.'&amp;minute='.$minute.'&amp;year='.$year.'&amp;month='.$month.'&amp;day='.$day.'&amp;page=day&amp;room_back='.$room_back.'" title="'.get_vocab("cliquez_pour_effectuer_une_reservation").'" ><span class="glyphicon glyphicon-plus"></span></a>'.PHP_EOL;
                                    }
                                }
                                else
                                {
                                    $temp = ' ';
                                }
                            }
                            $today[$room_id][$t]["data"] = $temp;
                            $t += $resolution;
                        }
                    }
                }
            }
        // pour l'affichage : entête avec le type de créneau et les noms des ressources
            $Thead = array();
            $dcell= "<td class='cell_hours' style='width:8%;'>";
            if ($enable_periods == 'y')
                $dcell .= get_vocab("period");
            else
                $dcell .= get_vocab("time");
            $Thead[] = $dcell.'</td>'.PHP_EOL;
            $nb_col = count($rooms);
            $room_column_width = (int)(90 / $nb_col);
            foreach($ressources as $ress){
                if(in_array($ress['id'],$rooms)){
                    $dcell = '<th style="width:'.$room_column_width.'%;"';
                    if ($ress['statut_room'] == "0")
                        $dcell .= 'class="avertissement" ';
                    $dcell .= '>';
                    $dcell .= '<a id="afficherBoutonSelection'.$ress['id'].'" class="lienPlanning" href="#" onclick="afficherMoisSemaine('.$ress['id'].')" style="display:inline;">'.htmlspecialchars($ress["room_name"]).'</a>'.PHP_EOL;
                    $dcell .= '<a id="cacherBoutonSelection'.$ress['id'].'" class="lienPlanning" href="#" onclick="cacherMoisSemaine('.$ress['id'].')" style="display:none;">'.htmlspecialchars($ress["room_name"]).'</a>'.PHP_EOL;
                    $dcell .= '<br />';
                    if ($ress['statut_room'] == "0"  && $_GET['pview'] != 1)
                        $dcell .= '<span class="texte_ress_tempo_indispo">'.get_vocab("ressource_temporairement_indisponible").'</span><br />'.PHP_EOL;
                    if ($ress['moderate'] == "1"  && $_GET['pview'] != 1)
                        $dcell .= '<span class="texte_ress_moderee">'.get_vocab("reservations_moderees").'</span><br />'.PHP_EOL;
                    if ($ress['capacity']  && $_GET['pview'] != 1)
                        $dcell .= '<span class="small">('.$ress["capacity"].' '.($ress["capacity"] > 1 ? get_vocab("number_max2") : get_vocab("number_max")).')</span><br />'.PHP_EOL;
                    if (verif_display_fiche_ressource($user_name, $ress['id']) && $_GET['pview'] != 1)
                        $dcell .= '<a href="javascript:centrerpopup(\'view_room.php?id_room='.$ress['id'].'\',600,480,\'scrollbars=yes,statusbar=no,resizable=yes\')" title="'.get_vocab("fiche_ressource").'"><span class="glyphcolor glyphicon glyphicon-search"></span></a>'.PHP_EOL;
                    if (authGetUserLevel($user_name,$ress['id']) > 2 && $_GET['pview'] != 1)
                        $dcell .= '<a href="./admin/edit_room.php?room='.$ress['id'].'"><span class="glyphcolor glyphicon glyphicon-cog"></span></a><br/>'.PHP_EOL;
                    $temp = html_ressource_empruntee($ress['id']);
                    if($temp != "")
                        $dcell .= $temp;
                    if ($ress['show_comment'] == "y" && $_GET['pview'] != 1 && ($ress['comment_room'] != "") && ($ress['comment_room'] != -1))
                        $dcell .= '<div class="center">'.$ress['comment_room'].'</div>'.PHP_EOL;
                    $dcell .= '<span id="boutonSelection'.$ress['id'].'" style="display:none;">'.PHP_EOL;
                    $dcell .= '<input type="button" class="btn btn-default btn-xs" title="'.htmlspecialchars(get_vocab("see_week_for_this_room")).'" onclick="javascript: location.href=\'week.php?year='.$year.'&amp;month='.$month.'&amp;day='.$day.'&amp;room='.$ress['id'].'\';" value="'.get_vocab('week').'"/>'.PHP_EOL;
                    $dcell .= '<input type="button" class="btn btn-default btn-xs" title="'.htmlspecialchars(get_vocab("see_month_for_this_room")).'" onclick="javascript: location.href=\'month.php?year='.$year.'&amp;month='.$month.'&amp;day='.$day.'&amp;room='.$ress['id'].'\';" value="'.get_vocab('month').'"/>'.PHP_EOL;
                    $dcell .= '</span>'.PHP_EOL;
                    $dcell .= '</th>'.PHP_EOL;
                    $Thead[] = $dcell;
                }
            }
        // fin tableau pour l'entête
            grr_sql_free($ressources);
        // tableau pour la colonne des temps/périodes
            $Ttimes = array();
            if ($enable_periods == 'y'){$pm7++;} // correctif pour domaine sur créneaux prédéfinis
            for ($t = $am7; $t < $pm7; $t += $resolution){
                if ($enable_periods == 'y'){
                    $time_t = date("i", $t);
                    $time_t_stripped = preg_replace( "/^0/", "", $time_t );
                    $Ttimes[$t] = $periods_name[$time_t_stripped];
                }
                else
                    $Ttimes[$t] = affiche_heure_creneau($t,$resolution).'</td>'.PHP_EOL;
            }
        }
    }
}
// pour le traitement des modules
include $racine."/include/hook.class.php";
// code HTML
header('Content-Type: text/html; charset=utf-8');
if (!isset($_COOKIE['open']))
{
	header('Set-Cookie: open=true; SameSite=Strict');
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
if(isset($alerte)){
  echo $alerte;
  end_page();
  die();
}
echo "<table class='jour floatthead table-striped table-bordered'>";
echo "<caption>";
$class = "";
$title = "";
if ($settings->get("show_holidays") == "Oui")
{   
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
        echo '<div class="left">',PHP_EOL,'<button class="btn btn-default btn-xs" onclick="javascript: location.href=\'day.php?year='.$yy.'&amp;month='.$ym.'&amp;day='.$yd.'&amp;area='.$area.'\';"> <span class="glyphicon glyphicon-backward"></span> ',get_vocab("daybefore"),'</button>','</div>',PHP_EOL;
    }
    else {
        echo '<div class="left">',PHP_EOL,'<button class="btn btn-default btn-xs" onclick="javascript: location.href=\'day.php?year='.$yy.'&amp;month='.$ym.'&amp;day='.$yd.'&amp;area='.$area.'&amp;room='.$room_back.'\';"> <span class="glyphicon glyphicon-backward"></span> ',get_vocab("daybefore"),'</button>','</div>',PHP_EOL;
    }    
    include "include/trailer.inc.php";
    if ($room_back == 'all'){
        echo '<div class="right">',PHP_EOL,'<button class="btn btn-default btn-xs" onclick="javascript: location.href=\'day.php?year='.$ty.'&amp;month='.$tm.'&amp;day='.$td.'&amp;area='.$area.'\';">  '.get_vocab('dayafter').'  <span class="glyphicon glyphicon-forward"></span></button>','</div>',PHP_EOL;
    }
    else{
        echo '<div class="right">',PHP_EOL,'<button class="btn btn-default btn-xs" onclick="javascript: location.href=\'day.php?year='.$ty.'&amp;month='.$tm.'&amp;day='.$td.'&amp;area='.$area.'&amp;room='.$room_back.'\';">  '.get_vocab('dayafter').'  <span class="glyphicon glyphicon-forward"></span></button>','</div>',PHP_EOL;
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
if ($settings->get("jours_cycles_actif") == "Oui" && intval($jour_cycle) > -1)
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
        echo '<span id="lienPrecedent"><button class="btn btn-default btn-xs" onclick="javascript:history.back();">Précedent</button></span>'.PHP_EOL;
}
echo "</div>";
echo '</div>'.PHP_EOL;
echo "</caption>".PHP_EOL;
if (isset($alerte)){// pas de ressource accessible ou existante
    echo '<tbody><tr><td><strong>'.$alerte.'</strong></td></tr></tbody>';
}
else{
    echo "<thead>".PHP_EOL;
    echo '<tr>';
    foreach($Thead as $s){
        echo $s;
    }
    echo '</tr>'.PHP_EOL;
    echo "</thead>".PHP_EOL; // fin entête du planning

    echo "<tbody>".PHP_EOL;
    $iii = 0;
    for ($t = $am7; $t < $pm7; $t += $resolution){
        echo '<tr>'.PHP_EOL;
        if ($iii % 2 == 1)
            tdcell("cell_hours");
        else
            tdcell("cell_hours2");
        $iii++;
        echo $Ttimes[$t].'</td>'.PHP_EOL;
        // ici parcourir le tableau $today
        foreach($rooms as $room){
            if(isset($today[$room][$t]['id'])) // il existe une réservation qui commence alors
            {
                tdcell_rowspan($today[$room][$t]['color'],$today[$room][$t]['slots']);
                echo $today[$room][$t]['data'];
                echo '</td>';
            }
            elseif(isset($today[$room][$t]['data'])) // créneau libre pour cette ressource
            {
                tdcell($today[$room][$t]['color']);
                echo $today[$room][$t]['data'];
                echo '</td>';
            }
        }
        echo '</tr>'.PHP_EOL;
    }
    echo "</tbody>".PHP_EOL;
}
echo '</table>'.PHP_EOL;

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
</body>
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
</html>