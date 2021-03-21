<?php
/**
 * edit_entry_handler.php
 * Vérifie la validité des données de l'édition puis si OK crée une réservation (ou une série)
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2021-03-14 11:28$
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
// on devrait arriver sur cette page depuis edit_entry ou edit_entry_handler, 
// il faudrait vérifier la page d'appel en pensant au timeout qui renvoie vers login.php

// fonctions locales
// récupère les variables passées par GET ou POST ou bien par COOKIE, et leur affecte le type indiqué (int ou string)
// rend NULL si la valeur recherchée n'est pas référencée
function getFormVar($nom,$type=''){
    $valeur = isset($_GET[$nom])? $_GET[$nom] : (isset($_POST[$nom])? $_POST[$nom] : (isset($_COOKIE['nom'])? $_COOKIE['nom'] : NULL));
    if ((isset($valeur)) && ($type !=''))
        settype($valeur,$type);
    return $valeur;
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
  'duration'           => 'string',
  'confirm_reservation'=> 'string',
  'period'             => 'int',
  'end_period'         => 'int',
  'dur_units'          => 'string',
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
$err_type = ''; // contient la partie du message dans le <h2>
$err_msg = ''; // contient la partie complémentaire du message d'erreur
try {
    if ((!isset($name) or (trim($name) == "")) && (Settings::get("remplissage_description_breve") != '0')){
        $err_type = 'required';
        throw new Exception('erreur');
    }
    $beneficiaire = isset($beneficiaire)? clean_input($beneficiaire) : "";
    $benef_ext_nom = isset($benef_ext_nom)? clean_input($benef_ext_nom) : "";
    $benef_ext_email = isset($benef_ext_email)? clean_input($benef_ext_email) : "";
    $beneficiaire_ext = concat_nom_email($benef_ext_nom, $benef_ext_email);
    if ($beneficiaire == "-1")// est-ce possible ?
        $beneficiaire = $user;
    if (($beneficiaire) == "")
    {
        if ($beneficiaire_ext == "-1")// est-ce possible ?
        {
            $err_type="required";
            throw new Exception('erreur');
        }
        if ($beneficiaire_ext == "-2")// est-ce possible ?
        {
            $err_type='invalid_owner_email_address';
            throw new Exception('erreur');
        }
    }
    else
        $beneficiaire_ext = "";
    if ((!isset($rooms[0])||(intval($rooms[0])==0))){
        $err_type="choose_a_room";
        throw new Exception('erreur');
    }
    else {
        $room = intval($rooms[0]); // si besoin on renvoie sur la première ressource de la sélection
        $area = mrbsGetRoomArea($room);
    }
    // les champs additionnels dépendant du domaine, on ne peut les traiter avant 
    $overload_data = array();
    $overload_fields_list = mrbsOverloadGetFieldslist($area);
    foreach ($overload_fields_list as $overfield=>$fieldtype)
    {
        $id_field = $overload_fields_list[$overfield]["id"];
        $fieldname = "addon_".$id_field;
        $$fieldname = getFormVar($fieldname,'string'); 
        if (($overload_fields_list[$overfield]["type"] == "numeric") && 
            (isset($$fieldname) && ($$fieldname != '') && (!preg_match("`^[0-9]*\.{0,1}[0-9]*$`",$$fieldname))))
        {
            $err_type = 'invalid_parameters';
            $err_msg = $overload_fields_list[$overfield]["name"].get_vocab("deux_points").get_vocab("is_not_numeric");
            throw new Exception('erreur');
        }
        if (isset($$fieldname))
            $overload_data[$id_field] = $$fieldname;
        else
            $overload_data[$id_field] = "";
    }
    get_planning_area_values($area);

    if (!isset($start_day) || !isset($start_month) || !isset($start_year)) // erreur fatale : date incomplète
    {
        $err_type="incomplete_date";
        throw new Exception('erreur');
    }
    if (check_begin_end_bookings($start_day, $start_month, $start_year))
    {
        $date = mktime(0, 0, 0, $month, $day,$year);
        $err_type = "nobookings";
        $err_msg = '<h3> '.affiche_date($date).'</h3>';
        $err_msg .= get_vocab("begin_bookings").'<b>'.affiche_date(Settings::get("begin_bookings")).'</b><br />';
        $err_msg .= get_vocab("end_bookings").'<b>'.affiche_date(Settings::get("end_bookings")).'</b>';
        throw new Exception('erreur');
    }
    if (($enable_periods == 'y')&&(!isset($period))){
        $err_type = 'missing_period';
        throw new Exception('erreur');
    }
    
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
    if ($type_affichage_reser == 0) // définition par start_time et duration
    {
        if ($enable_periods == 'y') // par créneaux
        {
            $resolution = 60;
            $start_hour = 12;
            $start_minute = $period;
            $max_periods = count($periods_name);
            if ( $dur_units == "periods" && (((int)$start_minute + (int)$duration) > $max_periods))
                $duration = (24 * 60 * floor($duration / $max_periods)) + ($duration % $max_periods); // cas d'un changement d'heure été/hiver ???
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
        $end_day = date("d",$end_time);
        $end_month = date("m",$end_time);
        $end_year = date("Y",$end_time);
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
            else{
                $err_type = 'missing_end_period';
                throw new Exception('erreur');
            }
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
        if (!isset($end_day) || !isset($end_month) || !isset($end_year) || !isset($end_hour) || !isset($end_minute) ){
            $err_type = 'incomplete_end_date';
            throw new Exception('erreur');
        }
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
        $err_type = 'error_end_date';
        $err_msg = 'debut '.$start_time.' fin '.$end_time;
        $err_msg .= '<br />date de fin '.$end_day.'/'.$end_month.'/'.$end_year;
        throw new Exception('erreur');
    }
    $starttime_midnight = mktime(0, 0, 0, $start_month, $start_day, $start_year);
    $endtime_midnight = mktime(0, 0, 0, $end_month, $end_day, $end_year);
    if (resa_est_hors_reservation($starttime_midnight , $endtime_midnight))
    {
        $err_type = 'error_begin_end_date';
        throw new Exception('erreur');
    }
    // ici on doit avoir start_time et end_time validés
    $keys = (isset($keys) && ($keys == 'y'))? 1 : 0;
    $courrier = (isset($courrier) &&($courrier == 'y'))? 1 : 0;
    $duration = str_replace(",", ".", "$duration ");
    $statut_entry = isset($statut_entry)? $statut_entry : "-";
    $rep_jour_c = isset($rep_jour_)? $rep_jour_ : 0;
    $cycle_cplt = isset($cycle_cplt)? intval($cycle_cplt) : 0;
    if ($cycle_cplt) $rep_jour_c =-1; // indique que le cycle complet est sélectionné
    if (($rep_type == 3) && ($rep_month == 3))
        $rep_type = 3;
    if (($rep_type == 3) && ($rep_month == 5))
        $rep_type = 5;
    // paramètres pour une série
    if (isset($rep_type) && ($rep_type != 0) && isset($rep_end_month) && isset($rep_end_day) && isset($rep_end_year))
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
    $create_by = isset($create_by)? clean_input($create_by) : $user;
    if (isset($room_back) && ($room_back != 'all'))
        $room_back = ''; // room_back c'est NULL, '' ou 'all'
    if (!isset($option_reservation))
        $option_reservation = -1;
    if (isset($confirm_reservation))
        $option_reservation = -1;
   // echo $page;
    $page = verif_page();
  //  echo $page;
    $referer = (isset($_SERVER['HTTP_REFERER']))? htmlspecialchars_decode($_SERVER['HTTP_REFERER'],ENT_QUOTES) :'';
    // $_SERVER['HTTP_REFERER'] ne contient pas les informations correctes s'il y a eu changement de ressource/domaine lors de l'édition de la réservation : il vaut mieux calculer la page précédente et peut-être plus tôt
    $referer = explode('?',$referer);
    if (!$referer[0])
    {
        $back = traite_grr_url()."edit_entry.php";
    }
    else 
        $back = $referer[0]; 
    // les autres paramètres devraient être dans les hiddenInputs
    // print_r($back);
    // page de retour vers un planning en cas d'abandon
    $page_ret = (isset($page_ret))? $page_ret : page_accueil();
    // les ressources
    foreach ($rooms as $key=>$room_id){
        $rooms[$key] = intval($room_id);
    }
    $date_now = time();
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
    if (!grr_sql_mutex_lock("".TABLE_PREFIX."_entry")){
        $err_type = 'failed_to_acquire';
        throw new Exception('erreur');
    }
    // teste s'il est autorisé et possible de réserver
    foreach ( $rooms as $room_id )
    {
        if ($rep_type != 0 && !empty($reps)) // périodicité
        {
            $diff = $end_time - $start_time;
            if (!grrCheckOverlap($reps, $diff)){
                $err_type = 'error_chevauchement';
                throw new Exception('erreur');
            }
            $i = 0;
            while ($i < count($reps)) // s'arrête à la première erreur par les exceptions
            {
                if ((authGetUserLevel($user,-1) < 2) && (auth_visiteur($user,$room_id) == 0)){
                    $err_type = 'reservation_impossible';
                    $err_msg = get_vocab('norights');
                    throw new Exception('erreur');
                }
                if (!(verif_booking_date($user, -1, $room_id, $reps[$i], $date_now, $enable_periods))){
                    $err_type = 'booking_in_past';
                    $err_msg = get_vocab('booking_in_past_explain_with_periodicity');
                    throw new Exception('erreur');
                }
                if (!(verif_duree_max_resa_area($user, $room_id, $start_time, $end_time))){
                    $err_type = 'duree_max_resa_aera';
                    throw new Exception('erreur');
                }
                if (!(verif_delais_max_resa_room($user, $room_id, $reps[$i]))){
                    $err_type = 'error_delais_max_resa_room';
                    throw new Exception('erreur');
                }
                if (!(verif_delais_min_resa_room($user, $room_id, $reps[$i], $enable_periods))){
                    $err_type = 'error_delais_min_resa_room';
                    throw new Exception('erreur');
                }
                if (!(verif_date_option_reservation($option_reservation, $reps[$i]))){
                    $err_type = 'error_date_confirm_reservation';
                    throw new Exception('erreur');
                }
                if (!(verif_qui_peut_reserver_pour($room_id, $user, $beneficiaire))){
                    $err_type = 'error_qui_peut_reserver_pour';
                    throw new Exception('erreur');
                }
                if (!(verif_heure_debut_fin($reps[$i], $reps[$i]+$diff, $area))){
                    $err_type = 'error_heure_debut_fin';
                    throw new Exception('erreur');
                }
                $i++;
            }
        }
        else // réservation unique
        {
            if ((authGetUserLevel($user,$room_id,'room') < 2) && (auth_visiteur($user,$room_id) == 0)){
                    $err_type = 'booking_room_out';
                    throw new Exception('erreur');
            }
            if (isset($id) && ($id != 0))
            {
                if (!(verif_booking_date($user, $id, $room_id, $start_time, $date_now, $enable_periods, $end_time))){
                    $err_type = 'booking_in_past';
                    $err_msg = get_vocab('booking_in_past_explain');
                    throw new Exception('erreur');
                }
            }
            else
            {
                if (!(verif_booking_date($user, -1, $room_id, $start_time, $date_now, $enable_periods))){
                    $err_type = 'booking_in_past';
                    $err_msg = get_vocab('booking_in_past_explain');
                    throw new Exception('erreur');
                }
            }
            if (!(verif_duree_max_resa_area($user, $room_id, $start_time, $end_time))){
                $err_type = 'duree_max_resa_aera';
                throw new Exception('erreur');
            }
            if (!(verif_delais_max_resa_room($user, $room_id, $start_time))){
                $err_type = 'error_delais_max_resa_room';
                throw new Exception('erreur');
            }
            if (!(verif_delais_min_resa_room($user, $room_id, $start_time, $enable_periods))){
                $err_type = 'error_delais_min_resa_room';
                throw new Exception('erreur');
            }
            if (!(verif_date_option_reservation($option_reservation, $start_time))){
                $err_type = 'date_confirm_reservation';
                throw new Exception('erreur');
            }
            if (!(verif_qui_peut_reserver_pour($room_id, $user, $beneficiaire))){
                $err_type = 'error_qui_peut_reserver_pour';
                throw new Exception('erreur');
            }
            if (!(verif_heure_debut_fin($start_time, $end_time, $area))){
                $err_type = 'error_heure_debut_fin';
                throw new Exception('erreur');
            }
            if (resa_est_hors_reservation2($start_time, $end_time, $area)){
                $err_type = 'error_heure_debut_fin';
                throw new Exception('erreur');
            }
        }
        $statut_room = grr_sql_query1("SELECT statut_room from ".TABLE_PREFIX."_room where id = '$room_id'");
        if (($statut_room == "0") && authGetUserLevel($user,$room_id) < 3)
        {
            $err_type = 'booking_room_out';
            throw new Exception('erreur');
        }
        if (!verif_acces_ressource($user, $room_id))
        {
            $err_type = 'booking_room_out';
            throw new Exception('erreur');
        }
    }
    if (isset($id) && ($id != 0)){
        if (!getWritable($beneficiaire, $user, $id)){
            $err_type = "accessdenied";
            $err_msg = get_vocab("norights");
            throw new Exception('erreur');
        }
    }
    if (authUserAccesArea($user, $area) == 0){
        $err_type = "accessdenied";
        $err_msg = get_vocab("norights");
        throw new Exception('erreur');
    }
   // echo '1er tests passés';
    // en cas de conflit, on forme une chaîne à afficher pour la gestion des conflits
    $conflits = "";
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
                }
                for ($i = 0; $i < count($reps); $i++)
                {
                    if (isset($del_entry_in_conflict) && ($del_entry_in_conflict == 'yes'))
                        grrDelEntryInConflict($room_id, $reps[$i], $reps[$i] + $diff, $ignore_id, $repeat_id, 0);
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
    if ($conflits != ''){
        throw new Exception('conflit');
    }
// pas de conflit, pas d'erreur, on peut envisager de poser les réservations...
// quelques vérifications supplémentaires
// reprendre ligne 658 sqq
    $compt_room = 0;
	foreach ($rooms as $room_id) // vérification des quotas
	{
		if (isset($id) and ($id != 0))
			$compt = 0; // modification : le nombre de réservations est inchangé
		else
			$compt = 1; // création
		if ($rep_type != 0 && !empty($reps))
		{
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
    // déverrouille la table
    grr_sql_mutex_unlock("".TABLE_PREFIX."_entry");
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
}// fin try
catch (Exception $e){
    $ex = $e->getMessage();
    // dans les deux cas, calcul des hidden inputs
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
    if (isset($overload_fields_list)){ // devrait être superflu
        foreach ($overload_fields_list as $overfield=>$fieldtype){
            $id_field = $overload_fields_list[$overfield]["id"];
            $fieldname = "addon_".$id_field;
            $$fieldname = getFormVar($fieldname,'string');
            if ($$fieldname != NULL){
                $hiddenInputs .= "<input type='hidden' name='".$fieldname."' value='".$$fieldname."' >";
            }
        }
    }
    if ($ex == 'erreur'){
        start_page_w_header();
        echo '<form method="post" action="edit_entry.php">';// ramène à la page d'édition
        echo $hiddenInputs;
        echo "<input type='hidden' name='Err' value='yes' >";
        echo '<h2>'.get_vocab($err_type).'</h2>';
        if ($err_msg != ''){
            echo '<p>'.$err_msg.'</p>';
        }
        echo "<input class='btn btn-primary' type='submit' value='".get_vocab('returnprev')."' />";
        echo '</form>';
    }
    elseif ($ex == 'conflit'){
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
            echo '<center>';
            echo '<form method="POST">';
            echo $hiddenInputs;
            echo '<input type="hidden" name="del_entry_in_conflict" value="yes" />';
            echo '<input class="btn btn-danger" type="submit" value="'.get_vocab("del_entry_in_conflict").'" />';
            echo '</form>';
            if ($rep_type != 0){
                echo '<form method="POST">';
                echo $hiddenInputs;
                echo '<input type="hidden" name="skip_entry_in_conflict" value="yes" />';
                echo '<input class="btn btn-success" type="submit" value="'.get_vocab("skip_entry_in_conflict").'" />';
                echo '</form>';
            }
            echo '</center><br />';
        }
        echo '<form action="./edit_entry.php" method="GET">'; // retour à la page d'édition
        echo $hiddenInputs;
        echo "<input class='btn btn-primary' type='submit' value='".get_vocab('returnprev')."' />";
        echo '</form>';
        // bouton pour abandonner
        echo '<form action="'.$page_ret.'">';
        echo '<input class="btn btn-warning" type="submit" value="'.get_vocab('cancel').'" />';
        echo '</form>';
    }
}
// code commun
end_page();
?>