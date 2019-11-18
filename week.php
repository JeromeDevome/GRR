<?php
/**
 * week.php
 * Permet l'affichage de la page d'accueil lorsque l'on est en mode d'affichage "semaine".
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2019-01-20 12:00$
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
$adm = 0;
$racine = "./";
$racineAd = "./admin/";
// pour le traitement des modules
include "./include/hook.class.php";

if (!($desactive_VerifNomPrenomUser))
    $desactive_VerifNomPrenomUser = 'n';
// On vérifie que les noms et prénoms ne sont pas vides
VerifNomPrenomUser($type_session);

$debug_flag = FALSE;
// le paramètre $room est obligatoire
if (!isset($room)){
    $msg = get_vocab('choose_a_room');
    $lien = "week_all.php?area=".$area."&day=".$day."&month=".$month."&year=".$year;
    echo "<script type='text/javascript'>
        alert('$msg');
        document.location.href='$lien';
    </script>";
    echo "<p><br/>";
        echo get_vocab('choose_room')."<a href='week_all.php'>".get_vocab("link")."</a>";
    echo "</p>";
    die();
}
$time = mktime(0, 0, 0, $month, $day, $year);
$time_old = $time;
if (($weekday = (date("w", $time) - $weekstarts + 7) % 7) > 0)
	$time -= $weekday * 86400;
//Fix début de semaine
if(date('H', $time) != date('H', $time_old)) {
    switch(date('H', $time)) {
        case '23':
            $time = strtotime(date("Y-m-d", strtotime("+ 1 day", $time)));
            break;
        case '01':
            $time = strtotime(date("Y-m-d", $time));
            break;
    }
}

$day_week   = date("d", $time);
$month_week = date("m", $time);
$year_week  = date("Y", $time);

$am7 = mktime($morningstarts, 0, 0, $month_week, $day_week, $year_week);
$pm7 = mktime($eveningends, $eveningends_minutes, 0, $month, $day_week, $year_week);
$week_midnight = mktime(0, 0, 0, $month_week, $day_week, $year_week);
$week_start = $am7;
$week_end = mktime($eveningends, $eveningends_minutes, 0, $month_week, $day_week + 6, $year_week);
$this_area_name = "";
$this_room_name = "";
$this_area_name = grr_sql_query1("SELECT area_name FROM ".TABLE_PREFIX."_area WHERE id=$area");
$this_area_resolution = grr_sql_query1("SELECT resolution_area FROM ".TABLE_PREFIX."_area WHERE id=$area");
$this_room_name = grr_sql_query1("SELECT room_name FROM ".TABLE_PREFIX."_room WHERE id=$room");
$this_room_max = grr_sql_query1("SELECT capacity FROM ".TABLE_PREFIX."_room WHERE id=$room");
$this_room_name_des = grr_sql_query1("SELECT description FROM ".TABLE_PREFIX."_room WHERE id=$room");
$this_statut_room = grr_sql_query1("SELECT statut_room FROM ".TABLE_PREFIX."_room WHERE id=$room");
$this_moderate_room = grr_sql_query1("SELECT moderate FROM ".TABLE_PREFIX."_room WHERE id=$room");
$this_delais_option_reservation = grr_sql_query1("SELECT delais_option_reservation FROM ".TABLE_PREFIX."_room WHERE id=$room");
$this_area_comment = grr_sql_query1("SELECT comment_room FROM ".TABLE_PREFIX."_room WHERE id=$room");
$this_area_show_comment = grr_sql_query1("SELECT show_comment FROM ".TABLE_PREFIX."_room WHERE id=$room");

//Pour vérifier si la plage de fin arrive sur un créneau ou non.
$minutesFinCrenaux = array();
for($h=0; $h<3600; $h+=$this_area_resolution) {
	$minutesFinCrenaux[] = date('i', $h);
}
if (($this_room_name_des) && ($this_room_name_des != "-1"))
	$this_room_name_des = " (".$this_room_name_des.")";
else
	$this_room_name_des = "";
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
// début du code HTML
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
echo "<section>".PHP_EOL;
// Calcul du niveau de droit de réservation
$authGetUserLevel = authGetUserLevel(getUserName(), -1);
// Determine si un visiteur peut réserver une ressource
$auth_visiteur = auth_visiteur(getUserName(), $room);
// Calcul du niveau d'accès aux fiche de réservation détaillées des ressources
$acces_fiche_reservation = verif_acces_fiche_reservation(getUserName(), $room);
// Calcul du test si l'utilisateur a la possibilité d'effectuer une réservation, compte tenu des limitations éventuelles de la ressources et du nombre de réservations déjà effectuées.
$UserRoomMaxBooking	= UserRoomMaxBooking(getUserName(), $room, 1);
// Affichage du menu
include("menu_gauche2.php");
include("chargement.php");

// calcul des cellules du planning
$sql = "SELECT start_time, end_time, type, name, id, beneficiaire, statut_entry, description, option_reservation, moderate, beneficiaire_ext
FROM ".TABLE_PREFIX."_entry
WHERE room_id=$room
AND start_time < ".$week_end." AND end_time > $week_start ORDER BY start_time";
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
	// Pour toutes les réservations
	for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
	{
		if ($debug_flag)
			echo "<br />DEBUG: result $i, id $row[4], starts $row[0] (".affiche_date($row[0])."), ends $row[1] (".affiche_date($row[1]).")\n";
		$month_debut = date("m",$row[0]);
		$day_debut = date("d",$row[0]);
		$year_debut = date("Y",$row[0]); 
        $debut_jour = mktime($morningstarts,0,0,$month_debut,$day_debut,$year_debut);
		$t = max(round_t_down($row[0], $resolution, $debut_jour), $week_start); // instant de départ de la tranche de résa
        $month_current = date("m",$t);
		$day_current = date("d",$t);
		$year_current = date("Y",$t);
		$end_t = min(round_t_up($row[1],$resolution, $debut_jour), $week_end+$this_area_resolution); // instant de fin de la tranche de résa
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
                    $d[$weekday][$slot]["color"] = $row[2];

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
                    if(!in_array(date('i', $d[$weekday][$slot]["horaireFin"]), $minutesFinCrenaux)) {
                        $d[$weekday][$slot]["duree"] += 1;
                    }
                    if ($prev_weekday != $weekday)
                    {
                        $prev_weekday = $weekday;
                        $d[$weekday][$slot]["data"] = affichage_lien_resa_planning($row[3],$row[4]);
                        $d[$weekday][$slot]["id"] = $row[4];
                        if (Settings::get("display_info_bulle") == 1)
                            $d[$weekday][$slot]["who"] = get_vocab("reservee au nom de").affiche_nom_prenom_email($row[5],$row[10],"nomail");
                        else if (Settings::get("display_info_bulle") == 2)
                            $d[$weekday][$slot]["who"] = $row[7];
                        else
                            $d[$weekday][$slot]["who"] = "";
                        $d[$weekday][$slot]["statut"] = $row[6];
                        $d[$weekday][$slot]["description"] = affichage_resa_planning($row[7],$row[4]);
                        $d[$weekday][$slot]["option_reser"] = $row[8];
                        $d[$weekday][$slot]["moderation"] = $row[9];
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
                    $d[$weekday][$slot]["color"] = $row[2];

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
                        $d[$weekday][$slot]["data"] = affichage_lien_resa_planning($row[3],$row[4]);
                        $d[$weekday][$slot]["id"] = $row[4];
                        if (Settings::get("display_info_bulle") == 1)
                            $d[$weekday][$slot]["who"] = get_vocab("reservee au nom de").affiche_nom_prenom_email($row[5],$row[10],"nomail");
                        else if (Settings::get("display_info_bulle") == 2)
                            $d[$weekday][$slot]["who"] = $row[7];
                        else
                            $d[$weekday][$slot]["who"] = "";
                        $d[$weekday][$slot]["statut"] = $row[6];
                        $d[$weekday][$slot]["description"] = affichage_resa_planning($row[7],$row[4]);
                        $d[$weekday][$slot]["option_reser"] = $row[8];
                        $d[$weekday][$slot]["moderation"] = $row[9];
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
if (verif_display_fiche_ressource(getUserName(), $room) && $_GET['pview'] != 1)
{
	echo '<a href="javascript:centrerpopup(\'view_room.php?id_room=',$room,'\',600,480,\'scrollbars=yes,statusbar=no,resizable=yes\')" title="',
	get_vocab("fiche_ressource"),'"><span class="glyphcolor glyphalign glyphicon glyphicon-search"></span></a>',PHP_EOL;
}
if (authGetUserLevel(getUserName(),$room) > 2 && $_GET['pview'] != 1)
{
	echo "<a href='./admin/admin.php?p=admin_edit_room&room=$room'><span class=\"glyphcolor glyphalign glyphicon glyphicon-cog\"></span></a>";
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
if ($this_area_show_comment == "y" && $_GET['pview'] != 1 && ($this_area_comment != "") && ($this_area_comment != -1))
{
	echo '<span style="text-align:center;">',$this_area_comment,'</span>',PHP_EOL;
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
		// while (list($w_k, $w_v) = each($d)) deprecated in php 7.2.0
        foreach($d as $w_k=>$w_v)
		{
			// while (list($t_k, $t_v) = each($w_v))
            foreach($w_v as $t_k=>$t_v)
			{
				// while (list($k_k, $k_v) = each($t_v))
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
                $class = 'ferie ';
            }
            elseif (isSchoolHoliday($tt)){
                $class = 'vacance ';
            }
        }
		/*echo "<th style=\"width:13%;\"><a onclick=\"charger()\" class=\"lienPlanning ".$class."\" title=\"".$title.htmlspecialchars(get_vocab("see_all_the_rooms_for_the_day"))."\" href=\"day.php?year=$year_actuel&amp;month=$month_actuel&amp;day=$num_day&amp;area=$area\">". utf8_strftime($dformat, $t)."</a>";*/
        echo "<th class = \"".$class."\" style=\"width:13%;\">";
        echo "<a onclick=\"charger()\" title=\"".$title.htmlspecialchars(get_vocab("see_all_the_rooms_for_the_day"))."\" href=\"day.php?year=$year_actuel&amp;month=$month_actuel&amp;day=$num_day&amp;area=$area\">". utf8_strftime($dformat, $t)."</a>";
		if (Settings::get("jours_cycles_actif") == "Oui" && intval($jour_cycle) >- 1)
			if (intval($jour_cycle) > 0)
				echo "<br />".get_vocab("rep_type_6")." ".$jour_cycle;
			else
				echo "<br />".$jour_cycle;
		echo "</th>\n";
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
		$empty_color = "empty_cell";
		$num_week_day = $weekstarts;

		// X colonnes (1 par jour)
		for ($weekday = 0; $weekday < 7; $weekday++)
		{
			$wday = date("d", $wt);
			$wmonth = date("m", $wt);
			$wyear = date("Y", $wt);
			$hour = date("H",$wt);
			$minute  = date("i",$wt);
			$decale_slot = 0;
			$insere_case = 'n';
			if (($insere_case == 'n') && ($display_day[$num_week_day] == 1))
			{
				if (!isset($d[$weekday][$slot - $decale_slot * $nb_case]["color"]))
				{
					$date_booking = mktime($hour, $minute, 0, $wmonth, $wday, $wyear);
					if ($this_statut_room == "0")
						tdcell("avertissement");
					else
						tdcell($empty_color);
					if (est_hors_reservation(mktime(0, 0, 0, $wmonth, $wday, $wyear), $area))
						echo "<img src=\"img_grr/stop.png\" alt=\"".get_vocab("reservation_impossible")."\"  title=\"".get_vocab("reservation_impossible")."\" width=\"16\" height=\"16\" class=\"".$class_image."\"  />";
					else
					{
						if ((($authGetUserLevel > 1) || ($auth_visiteur == 1)) && ($UserRoomMaxBooking != 0) && verif_booking_date(getUserName(), -1, $room, $date_booking, $date_now, $enable_periods) && verif_delais_max_resa_room(getUserName(), $room, $date_booking) && verif_delais_min_resa_room(getUserName(), $room, $date_booking) && (($this_statut_room == "1") || (($this_statut_room == "0") && (authGetUserLevel(getUserName(),$room) > 2) )) && $_GET['pview'] != 1)
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
						echo tdcell($empty_color)."<img src=\"img_grr/stop.png\" alt=\"".get_vocab("reservation_impossible")."\"  title=\"".get_vocab("reservation_impossible")."\" width=\"16\" height=\"16\" class=\"".$class_image."\"  />"."</td>";
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
									echo "<a title=\"".htmlspecialchars($d[$weekday][$slot - $decale_slot * $nb_case]["who"])."\"  data-width=\"675\" onclick=\"request($id,$wday,$wmonth,$wyear,$room,'$currentPage',readData);\" data-rel=\"popup_name\" class=\"poplight\">" ;
								}
								else
									echo "<a class=\"lienCellule\" title=\"".htmlspecialchars($d[$weekday][$slot-$decale_slot*$nb_case]["who"])."\"  href=\"view_entry.php?id=" . $d[$weekday][$slot - $decale_slot * $nb_case]["id"]."&amp;day=$wday&amp;month=$wmonth&amp;year=$wyear&amp;page=week\">";
							}
							echo $d[$weekday][$slot - $decale_slot * $nb_case]["data"]."";
							$Son_GenreRepeat = grr_sql_query1("SELECT type_name FROM ".TABLE_PREFIX."_type_area ,".TABLE_PREFIX."_entry  WHERE  ".TABLE_PREFIX."_entry.id= ". $d[$weekday][$slot - $decale_slot * $nb_case]['id']." AND ".TABLE_PREFIX."_entry.type= ".TABLE_PREFIX."_type_area.type_letter");
							if ($Son_GenreRepeat != -1)
							{
								if ($enable_periods != 'y'){
                                    $heure_fin = date('H:i',$d[$weekday][$slot-$decale_slot*$nb_case]["horaireFin"]);
                                    if (($heure_fin == '00:00') && ((date('w',$d[$weekday][$slot-$decale_slot*$nb_case]["horaireFin"]) - $weekday) != 1)) 
                                        {$heure_fin = '24:00';}
									echo "<br />" .date('H:i',$d[$weekday][$slot - $decale_slot * $nb_case]["horaireDebut"]).get_vocab("to"). $heure_fin."";
								}
								if (Settings::get("type") == '1') 
                                    echo " <br/>". $Son_GenreRepeat ." <br/><br/>" ;
							}
							if ($d[$weekday][$slot - $decale_slot * $nb_case]["description"]!= "")
								echo "<i>".$d[$weekday][$slot - $decale_slot * $nb_case]["description"]."</i><br>";
							$clef = grr_sql_query1("SELECT clef FROM ".TABLE_PREFIX."_entry WHERE  ".TABLE_PREFIX."_entry.id= ". $d[$weekday][$slot - $decale_slot * $nb_case]['id']."");
							if ($clef == 1)
								echo '<img src="img_grr/skey.png" alt="clef">';
							$courrier = grr_sql_query1("SELECT courrier FROM ".TABLE_PREFIX."_entry WHERE  ".TABLE_PREFIX."_entry.id= ". $d[$weekday][$slot - $decale_slot * $nb_case]['id']."");
							if (Settings::get('show_courrier') == 'y')
							{
								if ($courrier == 1)
									echo '<img src="img_grr/scourrier.png" alt="courrier">'.PHP_EOL;
								else
									echo '<img src="img_grr/hourglass.png" alt="buzy">'.PHP_EOL;
							}
							if ($acces_fiche_reservation)
								echo"</a>";
                            echo "</td>";
						}
						
						if ((isset($d[$weekday][$slot - $decale_slot * $nb_case]["statut"])) && ($d[$weekday][$slot - $decale_slot * $nb_case]["statut"] != '-'))
							echo '<img src="img_grr/buzy.png" alt="'.get_vocab("ressource actuellement empruntee").'" title="'.get_vocab("ressource actuellement empruntee").'" width="20" height="20" class="image" /></td>'.PHP_EOL;
						if (($this_delais_option_reservation > 0) && (isset($d[$weekday][$slot - $decale_slot * $nb_case]["option_reser"])) && ($d[$weekday][$slot - $decale_slot * $nb_case]["option_reser"] != -1))
							echo '<img src="img_grr/small_flag.png" alt="'.get_vocab("reservation_a_confirmer_au_plus_tard_le").'" title="'.get_vocab("reservation_a_confirmer_au_plus_tard_le").' '.time_date_string_jma($d[$weekday][$slot - $decale_slot * $nb_case]["option_reser"], $dformat).'" width="20" height="20" class="image" /></td>'.PHP_EOL;
						if ((isset($d[$weekday][$slot - $decale_slot * $nb_case]["moderation"])) && ($d[$weekday][$slot - $decale_slot * $nb_case]["moderation"] == '1'))
							echo '<img src="img_grr/flag_moderation.png" alt="'.get_vocab("en_attente_moderation").'" title="'.get_vocab("en_attente_moderation").'" class="image" /></td>'.PHP_EOL;
					}
                    // echo "</td>";
                }
                // echo "</td>";
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
        echo "</tr>";
	}
	echo '</tbody></table>',PHP_EOL;
	if ($_GET['pview'] != 1){
		echo '<div id="toTop">',PHP_EOL,'<b>',get_vocab("top_of_page"),'</b>',PHP_EOL;
		bouton_retour_haut ();
		echo '</div>',PHP_EOL;
	}
	affiche_pop_up(get_vocab("message_records"),"user");
	echo '
	<script type="text/javascript">
		$(document).ready(function(){
			$(\'table.table-bordered td\').each(function(){
				var $row = $(this);
				var height = $row.height();
				var h2 = $row.find(\'a\').height();
				$row.find(\'a\').css(\'min-height\', height);
				$row.find(\'a\').css(\'padding-top\', height/2 - h2/2);
			});
		});

		jQuery(document).ready(function($){
				$("#popup_name").draggable({containment: "#container"});
				$("#popup_name").resizable();
		});

	</script>';

	echo '</div>'.PHP_EOL; // fin de planning
	// echo '</div>'.PHP_EOL;
	echo '<div id="popup_name" class="popup_block" ></div>',PHP_EOL;

echo "</section>";
echo "</body></html>";
?>
