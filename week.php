<?php
/**
 * week.php
 * Affichage du planning en mode "semaine" pour une ressource.
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2021-04-24 15:24$
 * @author    Laurent Delineau & JeromeB & Yan Naessens
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

$grr_script_name = "week.php";

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

// Initilisation des variables
//$affiche_pview = '1';
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
$day = (isset($_GET['day']))? $_GET['day'] : date("d"); 
$month = (isset($_GET['month']))? $_GET['month'] : date("m");
$year = (isset($_GET['year']))? $_GET['year'] : date("Y");
// définition de variables globales
global $racine, $racineAd, $desactive_VerifNomPrenomUser;

// Lien de retour
$back = (isset($_SERVER['HTTP_REFERER']))? htmlspecialchars_decode($_SERVER['HTTP_REFERER'], ENT_QUOTES): page_accueil();

$user_name = getUserName();
// Type de session
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
$debug_flag = FALSE;
// le paramètre $room est obligatoire
if (!isset($room) || ($room == 0)){
    $msg = get_vocab('choose_a_room');
    if ($area == 0) $area = 1;
    $lien = "week_all.php?area=".$area."&day=".$day."&month=".$month."&year=".$year;
    echo "<script type='text/javascript'>
        alert('$msg');
        document.location.href='$lien';
    </script>";
    echo "<p><br/>";
        echo $msg."<a href='week_all.php'>".get_vocab("link")."</a>";
    echo "</p>";
    die();
}
$time = mktime(0, 0, 0, $month, $day, $year);
// $time_old = $time;
if (($weekday = (date("w", $time) - $weekstarts + 7) % 7) > 0)
    $time = mktime(0,0,0,$month,$day-$weekday,$year); // recule de $weekday jours, php corrigera en fonction du changement d'heure
/*	$time -= $weekday * 86400; // recule de $weekday jours, puis corrige en fonction du changement d'heure
if (!isset($correct_heure_ete_hiver) or ($correct_heure_ete_hiver == 1))
{
	if ((heure_ete_hiver("ete",$year,0) <= $time_old) && (heure_ete_hiver("ete",$year,0) >= $time) && ($time_old != $time) && (date("H", $time) == 23))
		$decal = 3600;
	else
		$decal = 0;
	$time += $decal;
}*/

$day_week   = date("d", $time); // premier jour de la semaine
$month_week = date("m", $time);
$year_week  = date("Y", $time);

//Récupération des données concernant l'affichage du planning du domaine
get_planning_area_values($area);

$am7 = mktime($morningstarts, 0, 0, $month_week, $day_week, $year_week);
$pm7 = mktime($eveningends, $eveningends_minutes, 0, $month, $day_week, $year_week);
$week_midnight = mktime(0, 0, 0, $month_week, $day_week, $year_week);
$week_start = $am7;
$week_end = mktime($eveningends, $eveningends_minutes, 0, $month_week, $day_week + 6, $year_week);

$sql= "SELECT area_name, resolution_area FROM ".TABLE_PREFIX."_area WHERE id=$area";
$res = grr_sql_query($sql);
if ($res){
    $this_area = grr_sql_row($res,0);
}
$this_area_name = (isset($this_area[0]))? $this_area[0]:"";
$this_area_resolution = (isset($this_area[1]))? $this_area[1]:"";
grr_sql_free($res);

$sql = "SELECT * FROM ".TABLE_PREFIX."_room WHERE id=$room";
$res = grr_sql_query($sql);
if ($res){
    $this_room = grr_sql_row_keyed($res,0);
}
$this_room_name = (isset($this_room['room_name']))? $this_room['room_name']:"";
$this_room_max = (isset($this_room['capacity']))? $this_room['capacity']:0;
$this_room_name_des = (isset($this_room['description']))? $this_room['description']:'';
$this_statut_room = (isset($this_room['statut_room']))? $this_room['statut_room']:1;
$this_moderate_room = (isset($this_room['moderate']))? $this_room['moderate']:0;
$this_delais_option_reservation = (isset($this_room['delais_option_reservation']))? $this_room['delais_option_reservation']:0;
$this_room_comment = (isset($this_room['comment_room']))? $this_room['comment_room']:'';
$this_room_show_comment = (isset($this_room['show_comment']))? $this_room['show_comment']:'n';
$who_can_book = (isset($this_room['who_can_book']))? $this_room['who_can_book']:1;
grr_sql_free($res);

//Pour vérifier si la plage de fin arrive sur un créneau ou non.
$minutesFinCreneaux = array();
for($h=0; $h<3600; $h+=$this_area_resolution) {
	$minutesFinCreneaux[] = date('i', $h);
}

if ($this_room_name_des != "")
	$this_room_name_des = " (".$this_room_name_des.")";

switch ($dateformat) {
	case "en":
	$dformat = "%A, %b %d";
	break;
	case "fr":
	$dformat = "%A %d %b";
	break;
}
$i = mktime(0, 0, 0, $month_week,$day_week - 7, $year_week);
$yy = date("Y", $i);
$ym = date("m", $i);
$yd = date("d", $i);
$i = mktime(0, 0, 0, $month_week, $day_week + 7, $year_week);
$ty = date("Y", $i);
$tm = date("m", $i);
$td = date("d", $i);

// Calcul du niveau de droit de réservation
$authGetUserLevel = authGetUserLevel($user_name, $room);
// Determine si un visiteur peut réserver une ressource
$auth_visiteur = auth_visiteur($user_name, $room);
// si la ressource est restreinte, l'utilisateur peut-il réserver ?
$user_can_book = $who_can_book || ($authGetUserLevel > 2) || (authBooking($user_name,$room));
// Calcul du niveau d'accès aux fiches de réservation détaillées des ressources
$acces_fiche_reservation = verif_acces_fiche_reservation($user_name, $room);
// Teste si l'utilisateur a la possibilité d'effectuer une réservation, compte tenu des limitations éventuelles de la ressource et du nombre de réservations déjà effectuées.
$UserRoomMaxBooking	= UserRoomMaxBooking($user_name, $room, 1);
// calcul des cellules du planning
$sql = "SELECT start_time, end_time, ".TABLE_PREFIX."_entry.id, name, beneficiaire, ".TABLE_PREFIX."_room.room_name,type, statut_entry, ".TABLE_PREFIX."_entry.description, ".TABLE_PREFIX."_entry.option_reservation, ".TABLE_PREFIX."_room.delais_option_reservation, ".TABLE_PREFIX."_entry.moderate, beneficiaire_ext, clef, ".TABLE_PREFIX."_entry.courrier, ".TABLE_PREFIX."_type_area.type_name, ".TABLE_PREFIX."_entry.overload_desc
FROM ".TABLE_PREFIX."_entry, ".TABLE_PREFIX."_room, ".TABLE_PREFIX."_area, ".TABLE_PREFIX."_type_area
WHERE
".TABLE_PREFIX."_entry.room_id=".TABLE_PREFIX."_room.id AND
".TABLE_PREFIX."_area.id = ".TABLE_PREFIX."_room.area_id AND
".TABLE_PREFIX."_room.id = '".$room."' AND
".TABLE_PREFIX."_type_area.type_letter = ".TABLE_PREFIX."_entry.type AND
start_time <= $week_end AND
end_time > $week_start
ORDER by start_time";
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
*/
if ($enable_periods == 'y')
{
    $first_slot = 0;
    $last_slot = count($periods_name)-1; 
}
else
{
    $first_slot = $morningstarts * 3600 / $resolution; 
    $last_slot = ($eveningends * 3600 + $eveningends_minutes * 60) / $resolution -1; 
}
if ($debug_flag)
	echo "<br />DEBUG: query=$sql <br />first_slot=$first_slot - last_slot=$last_slot\n";
$res = grr_sql_query($sql);
if (!$res)
	echo grr_sql_error();
else
{
    $overloadFieldList = mrbsOverloadGetFieldslist($area);
	// Pour toutes les réservations
	for ($i = 0; ($row = grr_sql_row_keyed($res, $i)); $i++)
    //foreach($res as $row) incompatible avec la fonction affichage_resa_planning_complet
	{
		if ($debug_flag)
			echo '<br />DEBUG: result $i, id $row[4], starts $row["start_time"] (".affiche_date($row["start_time"])."), ends $row["end_time"] (".affiche_date($row["end_time"]).")\n';
		$month_debut = date("m",$row["start_time"]);
		$day_debut = date("d",$row["start_time"]);
		$year_debut = date("Y",$row["start_time"]); 
        $debut_jour = mktime($morningstarts,0,0,$month_debut,$day_debut,$year_debut);
		$t = max(round_t_down($row["start_time"], $resolution, $debut_jour), $week_start); // instant de départ de la tranche de résa
        $month_current = date("m",$t);
		$day_current = date("d",$t);
		$year_current = date("Y",$t);
		$end_t = min(round_t_up($row["end_time"],$resolution, $debut_jour), $week_end+$this_area_resolution); // instant de fin de la tranche de résa
		$weekday = (date("w", $t) + 7 - $weekstarts) % 7;
		$prev_weekday = -1;
		$firstday = date("d", $t);
        $lastday = date("d", $end_t);
        // on sépare les plages définies par horaires et celles définies par créneaux
        if ($enable_periods != 'y') // cas des plages définies par horaires
        {
            $day_midnight = mktime(0,0,0,$month_current,$day_current,$year_current);
            $slot = ($t - $day_midnight) % 86400 / $this_area_resolution; // premier slot à afficher
       		$heigthSlotHoure = 60/($this_area_resolution/60);
       		do
            {
                if ($debug_flag)
                    echo "<br />DEBUG: t=$t (".affiche_date($t)."), end_t=$end_t (".affiche_date($end_t)."), weekday=$weekday, slot=$slot\n";
                if ($slot < $first_slot)
                {
                    $slot = $first_slot;
                    $t = $weekday * 86400 + $am7;
                    continue;
                }
                if ($slot <= $last_slot)
                {
                    $d[$weekday][$slot]["color"] = $row["type"];

                    if (($end_t) > mktime(24, 0, 0, date('m',$t), date('d',$t), date('Y',$t))) // fin de réservation dépassant la fin de journée
                    {
                        if (date("d", $t) == $firstday) // Premier jour de réservation, Hdebut = Heure debut résa / Hfin = heure fin de journée / duree = (nb bloc d'une journée - nb bloc vides)
                        {   
                            $d[$weekday][$slot]["horaireDebut"] = $t;
                            $d[$weekday][$slot]["horaireFin"] = mktime($eveningends, $eveningends_minutes, 0, date('m',$t), date('d',$t), date('Y',$t));
                            $d[$weekday][$slot]["duree"] = (mktime($eveningends, $eveningends_minutes, 0, date('m',$t), date('d',$t), date('Y',$t))-$t)/$this_area_resolution;
                        }
                        // Les jours entre les deux , Hdebut = Heure debut journée/ Hfin = heure fin journée / duree = ( h fin journée - h debut journée) * nb bloc pr 1h ) 
                        else
                        {
                            $d[$weekday][$slot]["horaireDebut"] = mktime($morningstarts, 0, 0, date('m',$t), date('d',$t), date('Y',$t));
                            $d[$weekday][$slot]["horaireFin"] = mktime($eveningends, $eveningends_minutes, 0, date('m',$t), date('d',$t), date('Y',$t));
                            $d[$weekday][$slot]["duree"] = (($eveningends+$eveningends_minutes/60-$morningstarts)*$heigthSlotHoure);
                        }
                    }
                    else // fin de réservation ne dépassant pas la fin de la journée ou dernière journée
                    {
                        $d[$weekday][$slot]["horaireDebut"] = $t;
                        $d[$weekday][$slot]["horaireFin"] = $end_t;
                        $d[$weekday][$slot]["duree"] = ($end_t- $t)/ $this_area_resolution;
                    }
           // affichage pour debug
                    if ($debug_flag)
                    { echo date('j-m-Y H:i:s',$d[$weekday][$slot]["horaireDebut"])." --- ";
                    echo date('j-m-Y H:i:s',$d[$weekday][$slot]["horaireFin"])." --- ";
                    echo $d[$weekday][$slot]["duree"]." --- ";
                    echo "C2C<br>";
                    }
                    //Si la plage de fin dépasse le créneau, augmenter la durée
                    if(!in_array(date('i', $d[$weekday][$slot]["horaireFin"]), $minutesFinCreneaux)) {
                        $d[$weekday][$slot]["duree"] += 1;
                    }
                    if ($prev_weekday != $weekday)
                    {
                        $prev_weekday = $weekday;
                        //$d[$weekday][$slot]["data"] = affichage_lien_resa_planning($row[3],$row[4]);
                        $heure_fin = date('H:i',$d[$weekday][$slot]["horaireFin"]);
                        if (($heure_fin == '00:00') && ((date('w',$d[$weekday][$slot]["horaireFin"]) - $weekday) != 1)) 
                            {$heure_fin = '24:00';}
                        $horaires = date('H:i',$d[$weekday][$slot]["horaireDebut"]).get_vocab("to"). $heure_fin."";
                        $d[$weekday][$slot]["data"] = affichage_resa_planning_complet($overloadFieldList, 1, $row, $horaires);
                        $d[$weekday][$slot]["id"] = $row["id"];
                        if (Settings::get("display_info_bulle") == 1)
                            $d[$weekday][$slot]["who"] = get_vocab("reservee au nom de").affiche_nom_prenom_email($row["beneficiaire"],$row["beneficiaire_ext"],"nomail");
                        else if (Settings::get("display_info_bulle") == 2)
                            $d[$weekday][$slot]["who"] = $row["description"];
                        else
                            $d[$weekday][$slot]["who"] = "";
                    }
                }
                $t += $this_area_resolution;
                $slot++;
                if ($slot > $last_slot)
                {
                    $weekday++;
                    $slot = $first_slot;
                    $t = $weekday * 86400 + $am7; // pb à prévoir avec changement d'heure ? YN
                }
            } while ($t < $end_t);
        }
        else  // cas des plages définies par créneaux
        {
            $slot = 0;
            do
            {
                if ($debug_flag)
                    echo "<br />DEBUG: t=$t (".affiche_date($t)."), end_t=$end_t (".affiche_date($end_t)."), weekday=$weekday, slot=$slot\n";
                $start_slot = date('i',$t)+60*date('H',$t)-720;
                $end_slot = date('i',$end_t)+60*date('H',$end_t)-720;
                if ($slot < $start_slot)
                {
                    $slot = $start_slot;
                    continue;
                }
                if ($slot <= $last_slot)
                {
                    $d[$weekday][$slot]["color"] = $row["type"];

                    if (($end_t) > mktime($eveningends, $eveningends_minutes, 0, date('m',$t), date('d',$t), date('Y',$t))) // fin de réservation dépassant la fin de journée
                    {
                        if (date("d", $t) == $firstday) // Premier jour de réservation, Hdebut = Heure debut résa / Hfin = heure fin de journée / duree = (nb bloc d'une journée - nb bloc vides)
                        {   
                            $d[$weekday][$slot]["horaireDebut"] = $t;
                            $d[$weekday][$slot]["horaireFin"] = mktime($eveningends, $eveningends_minutes, 0, date('m',$t), date('d',$t), date('Y',$t));
                            $d[$weekday][$slot]["duree"] = $last_slot+1-$start_slot; 
                        }
                        // Les jours entre les deux , Hdebut = Heure debut journée/ Hfin = heure fin journée / duree = nb de créneaux dans une journée 
                        else
                        {
                            $d[$weekday][$slot]["horaireDebut"] = mktime($morningstarts, 0, 0, date('m',$t), date('d',$t), date('Y',$t));
                            $d[$weekday][$slot]["horaireFin"] = mktime($eveningends, $eveningends_minutes, 0, date('m',$t), date('d',$t), date('Y',$t))+3600;
                            $d[$weekday][$slot]["duree"] = count($periods_name); 
                        }
                    }
                    else // fin de réservation ne dépassant pas la fin de la journée ou dernière journée
                    {
                        $d[$weekday][$slot]["horaireDebut"] = $t;
                        $d[$weekday][$slot]["horaireFin"] = $end_t;
                        $d[$weekday][$slot]["duree"] = $end_slot-$start_slot; 
                    } 
                    // affichage pour debug
                    if ($debug_flag)
                    { echo date('j-m-Y H:i:s',$d[$weekday][$slot]["horaireDebut"])." --- ";
                    echo date('j-m-Y H:i:s',$d[$weekday][$slot]["horaireFin"])." --- ";
                    echo $d[$weekday][$slot]["duree"]." --- ";
                    echo "C2C<br>";
                    }
            
                    if ($prev_weekday != $weekday)
                    {
                        $prev_weekday = $weekday;
                        $horaires = "";
                        $d[$weekday][$slot]["data"] = affichage_resa_planning_complet($overloadFieldList, 1, $row, $horaires);
                        $d[$weekday][$slot]["id"] = $row["id"];
                        if (Settings::get("display_info_bulle") == 1)
                            $d[$weekday][$slot]["who"] = get_vocab("reservee au nom de").affiche_nom_prenom_email($row["beneficiaire"],$row["beneficiaire_ext"],"nomail");
                        else if (Settings::get("display_info_bulle") == 2)
                            $d[$weekday][$slot]["who"] = $row["description"];
                        else
                            $d[$weekday][$slot]["who"] = "";
                    }
                }
                $t += 60; 
                $slot++;
                if ($slot > $last_slot) // passe au lendemain
                {
                    $weekday++;
                    $day_current++;
                    $slot = $first_slot;
                    $t = mktime(12,0,0,$month_current,$day_current,$year_current);
                }
            } while ($t < $end_t);
        }    
	}
}
grr_sql_free($res);

// pour le traitement des modules
include "./include/hook.class.php";

// début du code HTML
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
echo "<section>".PHP_EOL;
// Affichage du menu en haut ou à gauche
include("menuHG.php");
// affichage du planning
// Début du tableau affichant le planning
if ($_GET['pview'] != 1){
    echo "<div id='planning2'>";
}
else{
	echo '<div id="print_planning">'.PHP_EOL;
}
echo '<table class="semaine floatthead table-bordered table-striped">',PHP_EOL;
// le titre de la table
echo "<caption>";
echo "<div class=ligne23>";
if ((!isset($_GET['pview'])) || ($_GET['pview'] != 1))
{
	echo '<div class="left">',PHP_EOL,
	'<button class="btn btn-default btn-xs" onclick="charger();javascript: location.href=\'week.php?year=',$yy,'&amp;month=',$ym,'&amp;day=',$yd,'&amp;room=',$room,
	'\';"><span class="glyphicon glyphicon-backward"></span>',get_vocab("weekbefore"),'</button></div>';
	include "include/trailer.inc.php";
	echo '<div class="right">',PHP_EOL,'<button class="btn btn-default btn-xs" onclick="charger();javascript: location.href=\'week.php?year=',$ty,
	'&amp;month=',$tm,'&amp;day=',$td,'&amp;room=',$room,'\';">',get_vocab('weekafter'),'<span class="glyphicon glyphicon-forward"></span></button></div>';
}
echo "</div>";
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
if (verif_display_fiche_ressource($user_name, $room) && $_GET['pview'] != 1)
{
	echo '<a href="javascript:centrerpopup(\'view_room.php?id_room=',$room,'\',600,480,\'scrollbars=yes,statusbar=no,resizable=yes\')" title="',
	get_vocab("fiche_ressource"),'"><span class="glyphcolor glyphalign glyphicon glyphicon-search"></span></a>',PHP_EOL;
}
if ($authGetUserLevel > 2 && $_GET['pview'] != 1)
{
	echo "<a href='./admin/admin_edit_room.php?room=$room'><span class=\"glyphcolor glyphalign glyphicon glyphicon-cog\"></span></a>";
}
affiche_ressource_empruntee($room);
if ($this_statut_room == "0" && $_GET['pview'] != 1)
{
	echo '<br><span class="texte_ress_tempo_indispo">',get_vocab("ressource_temporairement_indisponible"),'</span>',PHP_EOL;
}
if ($this_moderate_room == "1" && $_GET['pview'] != 1)
{
	echo '<br><span class="texte_ress_moderee">',get_vocab("reservations_moderees"),'</span>',PHP_EOL;
}
if ($this_room_show_comment == "y" && $_GET['pview'] != 1 && ($this_room_comment != "") && ($this_room_comment != -1))
{
	echo '<span style="text-align:center;">',$this_room_comment,'</span>',PHP_EOL;
}

echo '<h4 class="titre">'.ucfirst($this_area_name).' - '.$this_room_name.' '.$this_room_name_des;
if ($this_room_max  && $_GET['pview'] != 1)
	echo '('.$this_room_max.' '.($this_room_max > 1 ? get_vocab("number_max2") : get_vocab("number_max")).')'.PHP_EOL;
echo '<br>'.get_vocab("week").get_vocab("deux_points").utf8_strftime($dformat, $week_start).' - '.utf8_strftime($dformat, $week_end).'</h4>'.PHP_EOL;

if (isset($_GET['precedent']))
{
	if ($_GET['pview'] != 1 AND $_GET['precedent'] == 1){
		echo '<span id="lienPrecedent">',PHP_EOL,'<button class="btn btn-default btn-xs" onclick="charger();javascript:history.back();">'.get_vocab('previous').'</button>',PHP_EOL,'</span>',PHP_EOL;
	}
}
echo '</div>'.PHP_EOL;
echo "</caption>";

if ($debug_flag)
{
	echo "<p>DEBUG:<p><pre>\n";
	if (gettype($d) == "array")
	{
        foreach($d as $w_k=>$w_v)
		{
	        foreach($w_v as $t_k=>$t_v)
			{
	            foreach($t_v as $k_k=>$k_v)
                    echo "$d[$w_k][$t_k][$k_k] =", $k_v ,"<br/>";
			}
		}
	}
	else echo "d is not an array!\n";
	echo "</pre><p>\n";
} 

echo "<thead>";
echo "<tr><td class=\"cell_hours\" style=\"width:8%;\">";
if ($enable_periods == 'y')
	echo get_vocab("period");
else
	echo get_vocab("time");
echo "</td>";
$num_week_day = $weekstarts;
$k = $day_week;
$i = $time;

for ($t = $week_start; $t < $week_end; $t += 86400)
{
	$num_day = strftime("%d", $t);
	$month_actuel = strftime("%m", $t);
	$year_actuel  = date("Y",$t);
	$tt = mktime(0, 0, 0, $month_actuel, $num_day,$year_actuel);
	$jour_cycle = grr_sql_query1("SELECT Jours FROM ".TABLE_PREFIX."_calendrier_jours_cycle WHERE DAY='$i'");
	if ($display_day[$num_week_day] == 1)
	{
		$class = "cell_hours";
		$title = "";
        if ($settings->get("show_holidays") == "Oui")
        {   
            if (isHoliday($tt)){
                $class .= ' ferie';
            }
            elseif (isSchoolHoliday($tt)){
                $class .= ' vacance';
            }
        }
        echo "<th class = \"".$class."\" style=\"width:13%;\">";
        echo "<a onclick=\"charger()\" title=\"".$title.htmlspecialchars(get_vocab("see_all_the_rooms_for_the_day"))."\" href=\"day.php?year=$year_actuel&amp;month=$month_actuel&amp;day=$num_day&amp;area=$area\">". utf8_strftime($dformat, $t)."</a>";
		if (Settings::get("jours_cycles_actif") == "Oui" && intval($jour_cycle) >- 1)
			if (intval($jour_cycle) > 0)
				echo "<br />".get_vocab("rep_type_6")." ".$jour_cycle;
			else
				echo "<br />".$jour_cycle;
		echo "</th>\n";
    }
    if (!isset($correct_heure_ete_hiver) || ($correct_heure_ete_hiver == 1))
    {
        $num_day = strftime("%d", $t);
        if (heure_ete_hiver("hiver", $year, 0) == mktime(0, 0, 0, $month, $num_day, $year))
            $t += 3600;
        if ((date("H",$t) == "13") || (date("H",$t) == "02"))
            $t -= 3600;
    }
    $i += 86400;
    $k++;
    $num_week_day++;
    $num_week_day = $num_week_day % 7;
}
echo "</tr></thead>"; // fin d'affichage de la ligne des jours
echo "<tbody>";
$t = $am7;
$nb_case = 0;
$semaine_changement_heure_ete = 'no';
$semaine_changement_heure_hiver = 'no';

	// Pour l'ensemble des horaires ou des créneaux
	for ($slot = $first_slot; $slot <= $last_slot; $slot++)
	{
		echo "<tr>";
        // 1ere colonne heure ou nom créneau
		if ($slot % 2 == 1)
			tdcell("cell_hours");
		else
			tdcell("cell_hours2");
		if ($enable_periods=='y')
		{
			$time_t = date("i", $t);
			$time_t_stripped = preg_replace( "/^0/", "", $time_t );
			echo $periods_name[$time_t_stripped] . "</td>\n";
        }
		else
			echo affiche_heure_creneau($t,$this_area_resolution)."</td>\n";

		$wt = $t;
		$num_week_day = $weekstarts;

		// X colonnes (1 par jour)
		for ($weekday = 0; $weekday < 7; $weekday++)
		{
			$wday = date("d", $wt);
			$wmonth = date("m", $wt);
			$wyear = date("Y", $wt);
			$hour = date("H",$wt);
			$minute  = date("i",$wt);
			$heureete1 = heure_ete_hiver("ete", $wyear,0);
			$heurehiver1 = heure_ete_hiver("hiver",$wyear, 0);
			$heureete2 = heure_ete_hiver("ete", $wyear,2);
			$heurehiver2 = heure_ete_hiver("hiver", $wyear, 2);

			if (!isset($correct_heure_ete_hiver) || ($correct_heure_ete_hiver == 1))
			{
				$temp =   mktime(0, 0, 0, $wmonth, $wday,$wyear);
				if ($heureete1 == $temp)
				{
					$semaine_changement_heure_ete = 'yes';
					$temp2 =   mktime($hour, 0, 0, $wmonth, $wday, $wyear);
					if ($heureete2 == $temp2)
					{
						if ($display_day[$num_week_day] == 1)
                            echo tdcell("empty_cell")."-";
						$nb_case++;
						$insere_case = 'y';
					}
					else if ($heureete2 < $temp2)
					{
						$hour = date("H", $wt - 3600);
						$decale_slot = 1;
						$insere_case = 'n';
					}
				}
				else if ($heurehiver1 == $temp)
				{
					$semaine_changement_heure_hiver = 'yes';
					$temp2 =   mktime($hour, 0, 0, $wmonth, $wday, $wyear);
					if ($heurehiver2 == $temp2)
					{
						$nb_case = $nb_case + 0.5;
						$insere_case = 'n';
					}
					else if ($heurehiver2 < $temp2)
					{
						$hour = date("H", $wt + 3600);
						$decale_slot = -1;
						$insere_case = 'n';
					}
				}
				else
				{
					$decale_slot = 0;
					$insere_case = 'n';
					if (($semaine_changement_heure_ete == 'yes') && ($heureete1 < $temp))
					{
						$decale_slot = 1;
						$hour = date("H", $wt - 3600);
					}
					if (($semaine_changement_heure_hiver == 'yes') && ($heurehiver1 < $temp))
					{
						$decale_slot = -1;
						$hour = date("H", $wt + 3600);
					}
				}
			}
			else
			{
				$decale_slot = 0;
				$insere_case = 'n';
			}
			if (($insere_case == 'n') && ($display_day[$num_week_day] == 1))
			{
				if (!isset($d[$weekday][$slot - $decale_slot * $nb_case]["color"])) // pas de réservation sur ce slot
				{
					$date_booking = mktime($hour, $minute, 0, $wmonth, $wday, $wyear);
                    if ($enable_periods == 'y')
                        $date_booking = mktime(23,59,0,$wmonth,$wday,$wyear);
					if ($this_statut_room == "0")
						tdcell("avertissement");
					else
						tdcell("empty_cell");
					if (est_hors_reservation(mktime(0, 0, 0, $wmonth, $wday, $wyear), $area))
						echo "<img src=\"img_grr/stop.png\" alt=\"".get_vocab("reservation_impossible")."\"  title=\"".get_vocab("reservation_impossible")."\" width=\"16\" height=\"16\" class=\"".$class_image."\"  />";
					else
					{
						if ((($authGetUserLevel > 1) || ($auth_visiteur == 1)) && ($UserRoomMaxBooking != 0) && verif_booking_date($user_name, -1, $room, $date_booking, $date_now, $enable_periods) && verif_delais_max_resa_room($user_name, $room, $date_booking) && verif_delais_min_resa_room($user_name, $room, $date_booking, $enable_periods) && (($this_statut_room == "1") || (($this_statut_room == "0") && ($authGetUserLevel > 2))) && $user_can_book && $_GET['pview'] != 1)
						{
							if ($enable_periods == 'y')
							{
								echo "<a href=\"edit_entry.php?room=$room"
								. "&amp;period=$time_t_stripped&amp;year=$wyear&amp;month=$wmonth"
								. "&amp;day=$wday&amp;page=week\" title=\"".get_vocab("cliquez_pour_effectuer_une_reservation")."\"><span class=\"glyphicon glyphicon-plus\"></span>";
								echo "</a>";
							}
							else
							{
								echo "<a href=\"edit_entry.php?room=$room"
								. "&amp;hour=$hour&amp;minute=$minute&amp;year=$wyear&amp;month=$wmonth"
								. "&amp;day=$wday&amp;page=week\" title=\"".get_vocab("cliquez_pour_effectuer_une_reservation")."\"><span class=\"glyphicon glyphicon-plus\"></span>";
								echo "</a>";
							}
						}
						else
							echo " ";
					}
                    echo "</td>";
				}
				else
				{
					if (est_hors_reservation(mktime(0, 0, 0, $wmonth, $wday, $wyear), $area))
						echo tdcell("empty_cell")."<img src=\"img_grr/stop.png\" alt=\"".get_vocab("reservation_impossible")."\"  title=\"".get_vocab("reservation_impossible")."\" width=\"16\" height=\"16\" class=\"".$class_image."\"  />"."</td>";
					else
					{
						if (isset($d[$weekday][$slot - $decale_slot * $nb_case]["id"]))
						{
							if ($enable_periods == 'y'){ // Nb de case pour créneau
								$nbrow = $d[$weekday][$slot - $decale_slot * $nb_case]["duree"];
							} 
                            else {
								$nbrow = $d[$weekday][$slot - $decale_slot * $nb_case]["duree"];
							}
							tdcell_rowspan($d[$weekday][$slot - $decale_slot * $nb_case]["color"], $nbrow);

							if ($acces_fiche_reservation)
							{
								if (Settings::get("display_level_view_entry") == 0)
								{
									$currentPage = 'week';
									$id =  $d[$weekday][$slot - $decale_slot * $nb_case]["id"];
									echo "<a title=\"".htmlspecialchars($d[$weekday][$slot - $decale_slot * $nb_case]["who"])."\"  data-width=\"675\" onclick=\"request($id,$wday,$wmonth,$wyear,$room,'$currentPage',readData);\" data-rel=\"popup_name\" class=\"poplight lienCellule\">" ;
								}
								else
									echo "<a class=\"lienCellule\" title=\"".htmlspecialchars($d[$weekday][$slot-$decale_slot*$nb_case]["who"])."\"  href=\"view_entry.php?id=" . $d[$weekday][$slot - $decale_slot * $nb_case]["id"]."&amp;day=$wday&amp;month=$wmonth&amp;year=$wyear&amp;page=week\">";
							}
							echo $d[$weekday][$slot - $decale_slot * $nb_case]["data"]."";
							if ($acces_fiche_reservation)
								echo"</a>";
                            echo "</td>".PHP_EOL;
						}
					}
                }
            }
			$wt += 86400;
			$num_week_day++; // Pour le calcul des jours à afficher
			$num_week_day = $num_week_day % 7; // Pour le calcul des jours à afficher
		} // Fin colonne du jour
		if ($enable_periods == 'y')
		{
			$time_t = date("i", $t);
			$time_t_stripped = preg_replace( "/^0/", "", $time_t);
		}
		$t += $resolution;
        echo "</tr>".PHP_EOL;
	}
	echo '</tbody></table>',PHP_EOL;
	if ($_GET['pview'] != 1){
		echo '<div id="toTop">',PHP_EOL,'<b>',get_vocab("top_of_page"),'</b>',PHP_EOL;
		bouton_retour_haut ();
		echo '</div>',PHP_EOL;
	}
	affiche_pop_up(get_vocab("message_records"),"user");
	echo '</div>'.PHP_EOL; // fin de planning
	echo '<div id="popup_name" class="popup_block" ></div>',PHP_EOL;
echo "</section>";
?>
<script type="text/javascript">
	$(document).ready(function(){
		$('table.table-bordered td').each(function(){
			var $row = $(this);
			var height = $row.height();
			var h2 = $row.find('a').height();
			$row.find('a').css('min-height', height);
			$row.find('a').css('padding-top', height/2 - h2/2);

		});
        $("#popup_name").draggable({containment: "#container"});
		$("#popup_name").resizable();
        afficheMenuHG(<?php echo $mode; ?>);
        if ( $(window).scrollTop() == 0 )
            $("#toTop").hide(1);
	});
</script>
</body>
</html>