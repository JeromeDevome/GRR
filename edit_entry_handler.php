<?php
/**
 * edit_entry_handler.php
 * Vérifie la validité des données de l'édition puis si OK crée une réservation (ou une série)
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2021-02-02 11:07$
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
 // les modifications apportées semblent OK avec la méthode GET, à vérifier quand on passera edit_entry en méthode POST
$grr_script_name = "edit_entry_handler.php";
include "include/connect.inc.php";
include "include/config.inc.php";
include "include/functions.inc.php";
include "include/$dbsys.inc.php";
include "include/mrbs_sql.inc.php";
include "include/misc.inc.php";
// Settings
require_once("./include/settings.class.php");
//Chargement des valeurs de la table settingS
if (!Settings::load())
	die("Erreur chargement settings");
// Session related functions
require_once("./include/session.inc.php");
// paramètres
//print_r($_COOKIE);
//print_r($_GET);
// die();
// Resume session
if (!grr_resumeSession())
{
	header("Location: ./logout.php?auto=1&url=$url"); // $url sort de session.inc.php
	die();
}
$user = getUserName(); // ici on devrait avoir un identifiant
// Paramètres langage
include "include/language.inc.php";
// on devrait arriver sur cette page depuis edit_entry ou edit_entry_handler, mais au cas où un accès direct serait tenté, on vérifie les droits d'accès
if (authGetUserLevel($user, -1) < 6)
{
	showAccessDenied(page_accueil());
	exit();
}
// et éventuellement la page d'appel

// fonctions locales
// récupère les variables passées par GET ou POST ou bien par COOKIE, et leur affecte le type indiqué (int ou string)
// rend NULL si la valeur recherchée n'est pas référencée
function getFormVar($nom,$type=''){
    $valeur = isset($_GET[$nom])? $_GET[$nom] : (isset($_POST[$nom])? $_POST[$nom] : (isset($_COOKIE['nom'])? $_COOKIE['nom'] : NULL));
    if ((isset($valeur)) && ($type !=''))
        settype($valeur,$type);
    return $valeur;
}
// ajoute le code html correspondant aux entrées cachées d'un formulaire (évite le passage par les cookies)
// ne fonctionne pas car les valeurs de $var ne sont pas connues de la fonction... code passé en ligne
function addHiddenInputs($form_vars){ // traite les champs réguliers, voir les champs additionnels
    $output = "chaîne des hidden inputs"; // chaîne des hidden inputs 
    foreach($form_vars as $var=>$var_type){
        if ($var_type == "array"){
            foreach($$var as $value){
                if(isset($value)){
                    $output .= "<input name='${var}[]' value='".$value."' >";
                }
            }
        }
        elseif(isset($$var)){
            $output .= "<input name='".$var."' value='".$$var."' >";
        }
    }
    echo $output;
}
// les variables attendues et leur type
$form_vars = array(
  'create_by'          => 'string',
  'name'               => 'string',
  'description'        => 'string',
  'start_day'          => 'int',
  'start_month'        => 'int',
  'start_year'         => 'int',
  'start_'             => 'string', // par ex. 12:00 
  'start_time'         => 'int',
  'hour'               => 'int', // depuis un planning
  'minute'             => 'int',
  'day'                => 'int',
  'month'              => 'int',
  'year'               => 'int',
  'end_day'            => 'int',
  'end_month'          => 'int',
  'end_year'           => 'int',
  'end_'               => 'string', // par ex. 14:00 
  'end_time'           => 'int',
  'all_day'            => 'string',  // yes ou vide
  'type'               => 'string',
  'rooms'              => 'array',
  'room'               => 'int',     // celle de la page d'appel
  'returl'             => 'string',  // là où aller en cas d'abandon
  'id'                 => 'int',     // id de la réservation en modification ou copie
  'rep_id'             => 'int',     // modification ou copie d'une série
  'edit_type'          => 'string',  // de quelle édition s'agit-il ? ne semble pas dans GRR
  'rep_type'           => 'int',
  'rep_end_date'       => 'string',
  'rep_day'            => 'array',   // array of bools 0|1
  'rep_opt'            => 'int',
  'rep_num_weeks'      => 'int',
  'entry_type'         => 'int',
  'repeat_id'          => 'int',
  'benef_ext_nom'      => 'string',
  'benef_ext_email'    => 'string',
  'beneficiaire'       => 'string',
  'statut_entry'       => 'string',
  'option_reservation' => 'int',
  'moderate'           => 'int',
  'courrier'           => 'string',
  'keys'               => 'string',
  'oldRessource'       => 'int', 
  'areas'              => 'int', 
  'rep_month'          => 'int', 
  'rep_month_abs1'     => 'int',
  'rep_month_abs2'     => 'int',
  'rep_end_day'        => 'int',
  'rep_end_month'      => 'int',
  'rep_end_year'       => 'int', 
  'rep_id'             => 'int',
  'rep_jour_'          => 'int',
  'cycle_cplt'         => 'string',
  'page'               => 'string', // récupérable dans page_ret, à filtrer parmi celles qui sont acceptables (plannings)
  'room_back'          => 'string', // palliatif de l'absence de day_all
  'page_ret'           => 'string',
  'type_affichage_reser' => 'int',
  'duration'           => 'int',
  'confirm_reservation'=> 'string',
  'period'             => 'int',
  'end_period'         => 'int',
  'dur_units'          => 'string',
  'areas'              => 'int',
  'del_entry_in_conflict' => 'string',
  'skip_entry_in_conflict' => 'string'
);
// tableau à compléter autant que nécessaire
// récupération des valeurs des variables passées en paramètres
foreach($form_vars as $var => $var_type)
{
    if ($var_type != "array"){
        $$var = getFormVar($var, $var_type);
        if ($var_type == "string"){$$var = trim($$var);}
    }
    else{ // traitement d'un tableau
        $$var = getFormVar($var,'');
        $$var = (array) $$var;
    }
}
// traiter aussi les champs additionnels (addon_x)!
// vérification
/*echo "<br>vérification<br>";
foreach($form_vars as $var => $var_type)
{
    echo $var.' -> ';print_r($$var);
    echo '<br/>';
}*/
//die();
// traitement des données
// données communes
$err_msg = ''; // contiendra, finalement, le message d'erreur, la variable erreur est inutile
if ((!isset($name) or (trim($name) == "")) && (Settings::get("remplissage_description_breve") == '1'))
{
	start_page_w_header();
	echo "<h2>".get_vocab("required")."</h2>";
	end_page();
	die();
}
$description = isset($description)? clean_input($description) : NULL;
if (isset($keys) && ($keys == 'y'))
    $keys = 1;
else $keys = 0;
if (isset($courrier) &&($courrier == 'y'))
	$courrier = 1;
else
	$courrier = 0;
$duration = isset($duration)? clean_input($duration) : NULL;
$duration = str_replace(",", ".", "$duration ");
if (isset($start_)){
	$debut = array();
	$debut = explode(':', $start_);
	$start_hour = $debut[0];
	$start_minute = isset($debut[1])? $debut[1]:'00';
    $pos = strpos($start_minute," ");
    if ($pos !== false){
        $debmin = explode(' ',$start_minute);
        $start_minute = $debmin[0];
        if ($debmin[1] == "pm"){$start_hour += 12;}
    }
}
if (isset($start_hour)){
	settype($start_hour, "integer");
	if ($start_hour > 23)
		$start_hour = 23;
}
if (isset($start_minute)){
	settype($start_minute, "integer");
	if ($start_minute > 59)
		$start_minute = 59;
}
$statut_entry = isset($statut_entry)? $statut_entry : "-";
$rep_jour_c = isset($rep_jour_)? $rep_jour_ : 0;
$cycle_cplt = isset($cycle_cplt)? intval($cycle_cplt) : 0;
if ($cycle_cplt) $rep_jour_c =-1; // indique que le cycle complet est sélectionné
if (($rep_type == 3) && ($rep_month == 3))
	$rep_type = 3;
if (($rep_type == 3) && ($rep_month == 5))
	$rep_type = 5;
$create_by = isset($create_by)? clean_input($create_by) : NULL; // devrait être $user_name ?
$beneficiaire = isset($beneficiaire)? clean_input($beneficiaire) : "";
$benef_ext_nom = isset($benef_ext_nom)? clean_input($benef_ext_nom) : "";
$benef_ext_email = isset($benef_ext_email)? clean_input($benef_ext_email) : "";
$beneficiaire_ext = concat_nom_email($benef_ext_nom, $benef_ext_email);
if (isset($room_back) && ($room_back != 'all'))
    $room_back = ''; // room_back c'est NULL, '' ou 'all'
$page = verif_page(); // vérifie $_GET['page'], à revoir
if (!isset($option_reservation))
	$option_reservation = -1;
if (isset($confirm_reservation))
	$option_reservation = -1;
if ($beneficiaire == "-1")
	$beneficiaire = $user;
if (($beneficiaire) == "")
{
	if ($beneficiaire_ext == "-1")
	{
		start_page_w_header();
		echo "<h2>".get_vocab("required")."</h2>";
		end_page();
		die();
	}
	if ($beneficiaire_ext == "-2")
	{
		start_page_w_header();
		echo "<h2>".get_vocab('invalid_owner_email_address')."</h2>";
		end_page();
		die();
	}
}
else
	$beneficiaire_ext = "";    
if ((!isset($rooms[0])||(intval($rooms[0])==0)))
{
	start_page_w_header();
	echo "<h2>".get_vocab("choose_a_room")."</h2>";
	end_page();
	die();
}
else 
    $room = intval($rooms[0]); // si besoin on renvoie sur la première page de la sélection
$referer = (isset($_SERVER['HTTP_REFERER']))? htmlspecialchars($_SERVER['HTTP_REFERER']) :'';
// $_SERVER['HTTP_REFERER'] ne contient pas les informations correctes s'il y a eu changement de ressource/domaine lors de l'édition de la réservation : il vaut mieux calculer la page précédente
$referer = explode('?',$referer);
if (!$referer[0])
{
    $back = traite_grr_url()."edit_entry.php?room=".$room;
}
else 
    $back = $referer[0]."?room=".$room; // les autres paramètres devraient être dans le cookie
//print_r($back);
// page de retour
$page_ret = (isset($page_ret))? $page_ret : $back;
$area = mrbsGetRoomArea(intval($rooms[0]));
// on vérifie la cohérence des paramètres
if ($area != $areas) $err_msg .='le domaine ne contient pas les ressources sélectionnées !';
// les champs additionnels dépendant du domaine, on ne peut les traiter avant 
$overload_data = array();
$overload_fields_list = mrbsOverloadGetFieldslist($area);
foreach ($overload_fields_list as $overfield=>$fieldtype)
{
	$id_field = $overload_fields_list[$overfield]["id"];
	$fieldname = "addon_".$id_field;
    $$fieldname = getFormVar($fieldname,'string'); 
	/*if (($overload_fields_list[$overfield]["obligatoire"] == 'y') && ((!isset($_GET[$fieldname])) || (trim($_GET[$fieldname]) == "")))
	{
		start_page_w_header();
		echo "<h2>".get_vocab("required")."</h2>";
		// echo "<a href=\"".$back."&amp;Err=yes\">".get_vocab('returnprev')."</a>";
        echo "<a href=\"".$back."\">".get_vocab('returnprev')."</a>";
		end_page();
		die();
	}*/
	if (($overload_fields_list[$overfield]["type"] == "numeric") && 
        (isset($$fieldname) && ($$fieldname != '') && (!preg_match("`^[0-9]*\.{0,1}[0-9]*$`",$$fieldname))))
	{
		start_page_w_header();
		echo "<h2>".$overload_fields_list[$overfield]["name"].get_vocab("deux_points").get_vocab("is_not_numeric")."</h2>";
		echo "<a href=\"".$back."&amp;Err=yes\">".get_vocab('returnprev')."</a>";
        //echo "<a href=\"".$back."\">".get_vocab('returnprev')."</a>";
		end_page();
		die();
	}
	if (isset($$fieldname))
		$overload_data[$id_field] = $$fieldname;
	else
		$overload_data[$id_field] = "";
}
get_planning_area_values($area);

if (!isset($start_day) || !isset($start_month) || !isset($start_year)) // erreur fatale : date incomplète
{
    start_page_w_header();
    echo "<h2>"."date incomplète"."</h2>";
    end_page();
    die();
}
if (check_begin_end_bookings($start_day, $start_month, $start_year))
{
	showNoBookings($start_day, $start_month, $start_year, $back."&amp;Err=yes");  // mettre un haut de page, ou réserver l'erreur ?
	exit();
}
//echo 'day'.$start_day.' month '.$start_month.' year '.$start_year;
//die();
if (($enable_periods == 'y')&&(!isset($period)))
    fatal_error('paramètre créneau manquant');
if ($type_affichage_reser == 0) // définition par start_time et duration
{
	if ($enable_periods == 'y') // par créneaux
	{
		$resolution = 60;
		$start_hour = 12;
		$start_minute = $period;
		$max_periods = count($periods_name);
		if ( $dur_units == "periods" && (((int)$start_minute + (int)$duration) > $max_periods))
			$duration = (24 * 60 * floor($duration / $max_periods)) + ($duration % $max_periods);
	}
	$units = 1.0;
	switch($dur_units)
	{
		case "years":
		$units *= 52;
		case "weeks":
		$units *= 7;
		case "days":
		$units *= 24;
		case "hours":
		$units *= 60;
		case "periods":
		case "minutes":
		$units *= 60;
		case "seconds":
		break;
	}
	if (isset($all_day) && ($all_day == "yes") && ($dur_units != "days"))
	{
		if ($enable_periods == 'y')
		{
			$start_time = mktime(12, 0, 0, $start_month, $start_day, $start_year);
			$end_time   = mktime(12, $max_periods, 0, $start_month, $start_day, $start_year); // pointe sur 12:n où n est le nombre de périodes, ce n'est pas un créneau valide
		}
		else
		{
			$start_time = mktime($morningstarts, 0, 0, $start_month, $start_day, $start_year);
			$end_time   = mktime($eveningends, $eveningends_minutes, 0, $start_month, $start_day, $start_year);
		}
	}
	else
	{
		$start_time = mktime($start_hour, $start_minute, 0, $start_month, $start_day, $start_year);
		$end_time   = mktime($start_hour, $start_minute, 0, $start_month, $start_day, $start_year) + intval($units) * floatval($duration);
		if ($end_time >= $start_time){
			$diff = $end_time - $start_time;
            if (($tmp = $diff % $resolution) != 0 || $diff == 0)
                $end_time += $resolution - $tmp; // arrondi à la résolution supérieure
        }// sinon, erreur récupérée ensuite
	}
}
else // définition par début et fin
{
	if ($enable_periods == 'y')
	{
		$resolution = 60;
		$start_hour = 12;
		$end_hour = 12;
		if (isset($end_period))
			$end_minute = $end_period + 1;
		else
			fatal_error('paramètre créneau de fin manquant');
	}
	else 
    {
		$fin = array();
		$fin = explode(':', clean_input($end_));
		$end_hour = $fin[0];
		$end_minute = $fin[1];
        $pos = strpos($fin[1],' ');
        if ($pos !== false){
            $finmin = explode(" ",$fin[1]);
            if ($finmin[1] == "pm"){$end_hour += 12;}
        }
	}

	if (!isset($end_day) || !isset($end_month) || !isset($end_year) || !isset($end_hour) || !isset($end_minute) )
		fatal_error('date de fin incomplète');
	else
	{
        $minyear = strftime("%Y", Settings::get("begin_bookings"));
		$maxyear = strftime("%Y", Settings::get("end_bookings"));
		if ($end_day < 1)
			$end_day = 1;
		if ($end_day > 31)
			$end_day = 31;
		if ($end_month < 1)
			$end_month = 1;
		if ($end_month > 12)
			$end_month = 12;
		if ($end_year < $minyear)
			$end_year = $minyear;
		if ($end_year > $maxyear)
			$end_year = $maxyear;
		$start_time = mktime($start_hour, $start_minute, 0, $start_month, $start_day, $start_year);
		$end_time   = mktime($end_hour, $end_minute, 0, $end_month, $end_day, $end_year);
		if ($end_time >= $start_time){
            $diff = $end_time - $start_time;
            if (($tmp = $diff % $resolution) != 0 || $diff == 0)
                $end_time += $resolution - $tmp; // arrondi à la résolution
        }
	}
}
if (($end_time <= $start_time)||(!checkdate($end_month, $end_day, $end_year)))
{
	start_page_w_header();
	echo "<h2>".get_vocab('error_end_date')."</h2>";
	echo "<a href=\"".$back."&amp;Err=yes\">".get_vocab('returnprev')."</a>";
	end_page();
	die();
}
$starttime_midnight = mktime(0, 0, 0, $start_month, $start_day, $start_year);
$endtime_midnight = mktime(0, 0, 0, $end_month, $end_day, $end_year);
if (resa_est_hors_reservation($starttime_midnight , $endtime_midnight))
{
	start_page_w_header();
	echo "<h2>".get_vocab('error_begin_end_date')."</h2>";
	echo "<a href=\"".$back."&amp;Err=yes\">".get_vocab('returnprev')."</a>";
	end_page();
	die();
}
// ici on doit avoir start_time et end_time validés
// paramètres pour une série
if (isset($rep_type) && isset($rep_end_month) && isset($rep_end_day) && isset($rep_end_year))
{
	$rep_enddate = mktime($end_hour, $end_minute, 0, $rep_end_month, $rep_end_day, $rep_end_year);
	if ($rep_enddate > Settings::get("end_bookings"))
		$rep_enddate = Settings::get("end_bookings");
}
else
	$rep_type = 0; // ce n'est pas une série

if (!isset($rep_day))
	$rep_day = array();
$rep_opt = ""; // chaîne des jours choisis
if ($rep_type == 2)
{
	for ($i = 0; $i < 7; $i++)
		$rep_opt .= empty($rep_day[$i])? "0" : "1";
}
if ($rep_type != 0)
	$reps = mrbsGetRepeatEntryList($start_time, isset($rep_enddate)? $rep_enddate : 0, $rep_type, $rep_opt, $max_rep_entrys, $rep_num_weeks, $rep_jour_c, $area, $rep_month_abs1, $rep_month_abs2);

foreach ($rooms as $key=>$room_id){
    $rooms[$key] = intval($room_id);
}
//echo "<br/> salles :";
//print_r($rooms);
//die(); //OK jusqu'ici, si on déverrouille, on revient au planning avec une réservation effectuée, testé sur week_all, donnait une erreur, corrigée, à voir avec les autres plannings

$date_now = time();
$error_ = array( 
    'booking_in_past' => FALSE,
    'booking_room_out' => FALSE,
    'duree_max_resa_area' => FALSE,
    'delais_max_resa_room' => FALSE,
    'delais_min_resa_room' => FALSE,
    'date_option_reservation' => FALSE,
    'chevauchement' => FALSE,
    'qui_peut_reserver_pour' => FALSE,
    'heure_debut_fin' => FALSE
);

if (!isset($id)){ // nouvelle réservation
    $ignore_id = 0;
    $repeat_id = 0;
}
else { // modification d'une réservation existante
    $ignore_id = $id;
	$repeat_id = grr_sql_query1("SELECT repeat_id FROM ".TABLE_PREFIX."_entry WHERE id=$id");
	if ($repeat_id < 0)
		$repeat_id = 0;
}
// bloque les accès concurrents à la table
if (!grr_sql_mutex_lock("".TABLE_PREFIX."_entry"))
	fatal_error(1, get_vocab('failed_to_acquire'));
// teste s'il est autorisé et possible de réserver
foreach ( $rooms as $room_id )
{
	if ($rep_type != 0 && !empty($reps)) // périodicité
	{
		$diff = $end_time - $start_time;
		if (!grrCheckOverlap($reps, $diff))
			$error_['chevauchement'] = TRUE;
		$i = 0;
		while (($i < count($reps)) && (!in_array(TRUE,$error_))) // s'arrête à la première erreur
		{
			if ((authGetUserLevel($user,-1) < 2) && (auth_visiteur($user,$room_id) == 0))
				$error_['booking_room_out'] = TRUE;
			if (!(verif_booking_date($user, -1, $room_id, $reps[$i], $date_now, $enable_periods)))
				$error_['booking_in_past'] = TRUE;
			if (!(verif_duree_max_resa_area($user, $room_id, $start_time, $end_time)))
				$error_['duree_max_resa_aera'] = TRUE;
			if (!(verif_delais_max_resa_room($user, $room_id, $reps[$i])))
				$error_['delais_max_resa_room'] = TRUE;
			if (!(verif_delais_min_resa_room($user, $room_id, $reps[$i], $enable_periods)))
				$error_['delais_min_resa_room'] = TRUE;
			if (!(verif_date_option_reservation($option_reservation, $reps[$i])))
				$error_['date_option_reservation'] = TRUE;
			if (!(verif_qui_peut_reserver_pour($room_id, $user, $beneficiaire)))
				$error_['qui_peut_reserver_pour'] = TRUE;
			if (!(verif_heure_debut_fin($reps[$i], $reps[$i]+$diff, $area)))
				$error_['heure_debut_fin'] = TRUE;
			$i++;
		}
	}
	else // réservation unique
	{
		if ((authGetUserLevel($user,-1) < 2) && (auth_visiteur($user,$room_id) == 0))
			$error_['booking_room_out'] = TRUE;
		if (isset($id) && ($id != 0))
		{
			if (!(verif_booking_date($user, $id, $room_id, $start_time, $date_now, $enable_periods, $end_time)))
				$error_['booking_in_past'] = TRUE;
		}
		else
		{
			if (!(verif_booking_date($user, -1, $room_id, $start_time, $date_now, $enable_periods)))
				$error_['booking_in_past'] = TRUE;
		}
		if (!(verif_duree_max_resa_area($user, $room_id, $start_time, $end_time)))
			$error_['duree_max_resa_area'] = TRUE;
		if (!(verif_delais_max_resa_room($user, $room_id, $start_time)))
			$error_['delais_max_resa_room'] = TRUE;
		if (!(verif_delais_min_resa_room($user, $room_id, $start_time, $enable_periods)))
			$error_['delais_min_resa_room'] = TRUE;
		if (!(verif_date_option_reservation($option_reservation, $start_time)))
			$error_['date_option_reservation'] = TRUE;
		if (!(verif_qui_peut_reserver_pour($room_id, $user, $beneficiaire)))
			$error_['qui_peut_reserver_pour'] = TRUE;
		if (!(verif_heure_debut_fin($start_time, $end_time, $area)))
			$error_['heure_debut_fin'] = TRUE;
		if (resa_est_hors_reservation2($start_time, $end_time, $area))
			$error_['heure_debut_fin'] = TRUE;
	}
	$statut_room = grr_sql_query1("SELECT statut_room from ".TABLE_PREFIX."_room where id = '$room_id'");
	if (($statut_room == "0") && authGetUserLevel($user,$room_id) < 3)
		$error_['booking_room_out'] = TRUE;
	if (!verif_acces_ressource($user, $room_id))
		$error_['booking_room_out'] = TRUE;
}
// en cas de conflit, on forme une chaîne à afficher pour la gestion des conflits
$conflits = "";
if (!in_array(TRUE,$error_))
{
	foreach ($rooms as $room_id)
	{
		if ($rep_type != 0 && !empty($reps))
		{
            $diff = $end_time - $start_time;
            $ignore = array();
			if (count($reps) < $max_rep_entrys)
			{
                if (isset($skip_entry_in_conflict) && ($skip_entry_in_conflict == 'yes')){
                    // stocke dans $ignore la liste des nouvelles réservations en conflit
                    for ($i = 0; $i < count($reps); $i++){
                        $tmp = mrbsCheckFree($room_id, $reps[$i], $reps[$i] + $diff, $ignore_id, $repeat_id);
                        if (!empty($tmp)){
                            $ignore[] = $reps[$i];
                        }
                    }
                    $reps = array_diff($reps,$ignore); // ôte de la liste les nouvelles réservations en conflit
                    $reps = array_values($reps);
                    //print_r($reps);echo "i ".$ignore_id."r ".$repeat_id;die();
                }
				for ($i = 0; $i < count($reps); $i++)
				{
					if (isset($del_entry_in_conflict) && ($del_entry_in_conflict == 'yes'))
						grrDelEntryInConflict($room_id, $reps[$i], $reps[$i] + $diff, $ignore_id, $repeat_id, 0);
					/*if ($i == (count($reps) - 1))
						$tmp = mrbsCheckFree($room_id, $reps[$i], $reps[$i] + $diff, $ignore_id, $repeat_id);
					else*/
					$tmp = mrbsCheckFree($room_id, $reps[$i], $reps[$i] + $diff, $ignore_id, $repeat_id);
					if (!empty($tmp))
						$conflits = $conflits . $tmp;
				}
			}
			else
			{
				$conflits .= get_vocab("too_may_entrys") . "<p>";
				$hide_title  = 1;
			}
		}
		else
		{
			if (isset($del_entry_in_conflict) && ($del_entry_in_conflict == 'yes'))
				grrDelEntryInConflict($room_id, $start_time, $end_time-1, $ignore_id, $repeat_id, 0);
			$conflits .= mrbsCheckFree($room_id, $start_time, $end_time - 1, $ignore_id, $repeat_id);
		}
	}
}
if (empty($conflits) && (!in_array(TRUE, $error_)))
{
    if (isset($id) && ($id != 0))
    {
        if (!getWritable($beneficiaire, $user, $id))
        {
            showAccessDenied($back);
            exit;
        }
    }
    if (authUserAccesArea($user, $area) == 0)
    {
        showAccessDenied($back);
        exit();
    }
	$compt_room = 0;
	foreach ($rooms as $room_id) // vérification des quotas
	{
		if (isset($id) and ($id != 0))
			$compt = 0; // modification : le nombre de réservations est inchangé
		else
			$compt = 1; // création
		if ($rep_type != 0 && !empty($reps))
		{
			//if (UserRoomMaxBooking($user, $room_id, count($reps) - 1 + $compt + $compt_room) == 0)
            if (UserRoomMaxBookingRange($user, $room_id, count($reps) - 1 + $compt + $compt_room,$start_time) == 0)
			{
				showAccessDeniedMaxBookings($start_day, $start_month, $start_year, $room_id, $back);
				exit();
			}
			else
				$compt_room += 1;
		}
		else
		{
			//if (UserRoomMaxBooking($user, $room_id, $compt + $compt_room) == 0)
            if (UserRoomMaxBookingRange($user, $room_id, $compt + $compt_room,$start_time) == 0)
			{
				showAccessDeniedMaxBookings($start_day, $start_month, $start_year, $room_id, $back);
				exit();
			}
			else
				$compt_room += 1;
		}
	}
	foreach ($rooms as $room_id) // modération
	{
		$moderate = grr_sql_query1("SELECT moderate FROM ".TABLE_PREFIX."_room WHERE id = '".$room_id."'");
		if ($moderate == 1)
		{
			$send_mail_moderate = 1;
			if (isset($id))
			{
				$old_entry_moderate =  grr_sql_query1("SELECT moderate FROM ".TABLE_PREFIX."_entry where id='".$id."'");
				if (authGetUserLevel($user,$room_id) < 3)
					$entry_moderate = 1;
				else
					$entry_moderate = $old_entry_moderate;
				if ($entry_moderate != 1)
					$send_mail_moderate = 0;
			}
			else
			{
				if (authGetUserLevel($user,$room_id) < 3)
					$entry_moderate = 1;
				else
				{
					$entry_moderate = 0;
					$send_mail_moderate = 0;
				}
			}
		}
		else
		{
			$entry_moderate = 0;
			$send_mail_moderate = 0;
		}
		if ($rep_type != 0)
		{                    //print_r($reps);print_r($ignore);die();
			$id_first_resa = mrbsCreateRepeatingEntrys($start_time, $end_time, $rep_type, $rep_enddate, $rep_opt, $room_id, $create_by, $beneficiaire, $beneficiaire_ext, $name, $type, $description, $rep_num_weeks, $option_reservation, $overload_data, $entry_moderate, $rep_jour_c, $courrier, $rep_month_abs1, $rep_month_abs2, $ignore);
			if (Settings::get("automatic_mail") == 'yes')
			{
				if (isset($id_first_resa) && ($id_first_resa != 0))
				{
					if ($send_mail_moderate)
						$message_error = send_mail($id_first_resa, 5, $dformat);
					else
						$message_error = send_mail($id_first_resa, 2, $dformat, array(), $oldRessource);
				}
				else // ici $id_first_resa n'est pas défini ou nul, i.e. la série de réservations n'est pas posée => message à modifier ?
				{/*
					if ($send_mail_moderate)
						$message_error = send_mail($id_first_resa, 5, $dformat);
					else
						$message_error = send_mail($id_first_resa, 1, $dformat);*/
				}
			}
		}
		else
		{
			if ($repeat_id > 0)
				$entry_type = 2;
			else
				$entry_type = 0;
			$new_id = mrbsCreateSingleEntry($start_time, $end_time, $entry_type, $repeat_id, $room_id, $create_by, $beneficiaire, $beneficiaire_ext, $name, $type, $description, $option_reservation, $overload_data, $entry_moderate, $rep_jour_c, $statut_entry, $keys, $courrier);
			//$new_id = grr_sql_insert_id();
			if (Settings::get("automatic_mail") == 'yes')
			{
				if (isset($new_id) && ($new_id != 0))
				{
					if ($send_mail_moderate)
						$message_error = send_mail($new_id,5,$dformat);
					else
						$message_error = send_mail($new_id,2,$dformat, array(), $oldRessource);
				}
				else // ici $new_id n'est pas défini ou nul, i.e. la réservation n'est pas posée => message à modifier ?
				{/*
					if ($send_mail_moderate)
						$message_error = send_mail($new_id,5,$dformat);
					else
						$message_error = send_mail($new_id,1,$dformat);*/
				}
			}
		}
	}
	if (isset($id) && ($id != 0)) // quand on fait une modification, on commence par effacer la réservation ou la série existante
	{
		if ($rep_type != 0)
			mrbsDelEntry($user, $id, "series", 1);
		else
			mrbsDelEntry($user, $id, NULL, 1);
	}
	$_SESSION['displ_msg'] = 'yes';
	if ($message_error != "") // si erreur d'envoi des messages, retour à la page d'appel
    {
        $_SESSION['session_message_error'] = $message_error;
        display_mail_msg();
            if (($room_back != 'all')&&(strpos($page, 'all') === false)){
                Header("Location: ".$page.".php?year=$start_year&month=$start_month&day=$start_day&area=$area&room=$room"); // à voir
            }
            else Header("Location: ".$page.".php?year=$start_year&month=$start_month&day=start_$day&area=$area"); // à voir si $ret_page n'est pas mieux
    }
	else // sinon, retour sur la page de la réservation validée
    {
        if (($room_back != 'all')&&(strpos($page, 'all') === false))
        {
            Header("Location: ".$page.".php?year=$start_year&month=$start_month&day=$start_day&area=$area&room=$room");
        }
        else Header("Location: ".$page.".php?year=$start_year&month=$start_month&day=$start_day&area=$area");        
    }        
	exit;
}
// déverrouille la table
grr_sql_mutex_unlock("".TABLE_PREFIX."_entry");

// page en cas d'erreur ou de conflit
$hiddenInputs = ""; // chaîne des hidden inputs 
foreach($form_vars as $var=>$var_type){
    if ($var_type == "array"){
        foreach($$var as $value){
            if(isset($value)){
                $hiddenInputs .= "<input type='hidden' name='${var}[]' value='".$value."' >";
            }
        }
    }
    elseif(isset($$var)&& ($$var != NULL)){
        $hiddenInputs .= "<input type='hidden' name='".$var."' value='".$$var."' >";
    }
}
foreach ($overload_fields_list as $overfield=>$fieldtype){
    $id_field = $overload_fields_list[$overfield]["id"];
    $fieldname = "addon_".$id_field;
    $$fieldname = getFormVar($fieldname,'string');
    if ($$fieldname != NULL){
        $hiddenInputs .= "<input type='hidden' name='".$fieldname."' value='".$$fieldname."' >";
    }
}
// cas 1 : erreur
if (in_array(TRUE,$error_)){// il existe une ou des erreurs dans les paramètres
    start_page_w_header();
    echo "<form action='".$back."' method='GET'>";
    echo $hiddenInputs;
    echo "<input type='hidden' name='Err' value='yes' >";
    //suivent les différents cas
    if ($error_['booking_in_past']){
        $str_date = utf8_strftime("%d %B %Y, %H:%M", $date_now);
        echo "<h2>" . get_vocab("booking_in_past") . "</h2>";
        if ($rep_type != 0 && !empty($reps))
            echo "<p>" . get_vocab("booking_in_past_explain_with_periodicity") . $str_date."</p>";
        else
            echo "<p>" . get_vocab("booking_in_past_explain") . $str_date."</p>";
    }
    if ($error_['duree_max_resa_area']){
        $area_id = grr_sql_query1("SELECT area_id FROM ".TABLE_PREFIX."_room WHERE id='".protect_data_sql($room)."'");
        $duree_max_resa_area = grr_sql_query1("SELECT duree_max_resa_area FROM ".TABLE_PREFIX."_area WHERE id='".$area_id."'");
        $temps_format = $duree_max_resa_area*60;
        toTimeString($temps_format, $dur_units, true);
        echo "<h2>" . get_vocab("error_duree_max_resa_area").$temps_format ." " .$dur_units."</h2>";
    }
    if ($error_['delais_max_resa_room']){
        echo "<h2>" . get_vocab("error_delais_max_resa_room") ."</h2>";
    }
    if ($error_['chevauchement']){
        echo "<h2>" . get_vocab("error_chevauchement") ."</h2>";
    }
    if ($error_['delais_min_resa_room']){
        echo "<h2>" . get_vocab("error_delais_min_resa_room") ."</h2>";
    }
    if ($error_['date_option_reservation']){
        echo "<h2>" . get_vocab("error_date_confirm_reservation") ."</h2>";
    }
    if ($error_['booking_room_out']){
        echo "<h2>" . get_vocab("norights") . "</h2>";
        echo "<p><b>" . get_vocab("tentative_reservation_ressource_indisponible") . "</b></p>";
    }
    if ($error_['qui_peut_reserver_pour']){
        echo "<h2>" . get_vocab("error_qui_peut_reserver_pour") ."</h2>";
    }
    if ($error_['heure_debut_fin']){
        echo "<h2>" . get_vocab("error_heure_debut_fin") ."</h2>";
        echo strftime($start_time);
    }
    // bouton de retour vers edit_entry et fin du code de la page
    echo "<input class='btn btn-primary' type='submit' value='".get_vocab('returnprev')."' />";
    echo '</form>';
	end_page();
	die();
}
if (strlen($conflits))
{
    start_page_w_header();
	echo "<h2>" . get_vocab("sched_conflict") . "</h2>";
	if (!isset($hide_title))
	{
		echo get_vocab("conflict");
		echo "<UL>";
	}
	echo $conflits;
	if (!isset($hide_title))
		echo "</UL>";
	if (authGetUserLevel($user,$area,'area') >= 4){
		// echo "<center><table border=\"1\" cellpadding=\"10\" cellspacing=\"1\"><tr><td class='avertissement'><h3><a href='".traite_grr_url("","y")."edit_entry_handler.php?".$_SERVER['QUERY_STRING']."&amp;del_entry_in_conflict=yes'>".get_vocab("del_entry_in_conflict")."</a></h4></td></tr></table></center><br />"; modifié le 15/03/2018 YN
        // echo "<center><table border=\"1\" cellpadding=\"10\" cellspacing=\"1\"><tr><td class='avertissement'><h3><a href='edit_entry_handler.php?".$_SERVER['QUERY_STRING']."&amp;del_entry_in_conflict=yes'>".get_vocab("del_entry_in_conflict")."</a></h3></td></tr></table></center><br />";
        echo '<center>';
        //echo '<a class="btn btn-danger" type="button" href="edit_entry_handler.php?'.$_SERVER['QUERY_STRING'].'&amp;del_entry_in_conflict=yes">'.get_vocab("del_entry_in_conflict").'</a>';
        echo '<form method="POST">';
        echo $hiddenInputs;
        echo '<input type="hidden" name="del_entry_in_conflict" value="yes" />';
        echo '<input class="btn btn-danger" type="submit" value="'.get_vocab("del_entry_in_conflict").'" />';
        echo '</form>';
        //echo '<a class="btn btn-success" type="button" href="edit_entry_handler.php?'.$_SERVER['QUERY_STRING'].'&amp;skip_entry_in_conflict=yes">'.get_vocab('skip_entry_in_conflict').'</a>';
        echo '<form method="POST">';
        echo $hiddenInputs;
        echo '<input type="hidden" name="skip_entry_in_conflict" value="yes" />';
        echo '<input class="btn btn-success" type="submit" value="'.get_vocab("skip_entry_in_conflict").'" />';
        echo '</form>';
        echo '</center><br />';
    }
    // echo "<a class='btn btn-primary' type='button' href='edit_entry.php?".$_SERVER['QUERY_STRING']."&amp;Err=yes'>".get_vocab('returnprev')."</a>"; // pose pb avec une erreur de réservation non trouvée YN le 17/01/2021
    //echo "<a class='btn btn-primary' type='button' href=\"".$back."\">".get_vocab('returnprev')."</a>"; // ici on a perdu toutes les données du formulaire
    echo '<form action="./edit_entry.php" method="GET">'; 
    echo $hiddenInputs;
    echo "<input class='btn btn-primary' type='submit' value='".get_vocab('returnprev')."' />";
    echo '</form>';
    // bouton pour abandonner
    //echo '<a class="btn btn-warning" type="button" href="'.$page_ret.'">'.get_vocab('cancel').'</a>'; // ramène à un planning
    echo '<form action="'.$page_ret.'">';
    echo '<input class="btn btn-warning" type="submit" value="'.get_vocab('cancel').'" />';
    echo '</form>';
    end_page();
} // fin de traitement des conflits
?>