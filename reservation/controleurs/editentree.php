<?php
/**
 * editentree.php
 * Interface d'édition d'une réservation
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2024-06-23 12:00$
 * @author    Laurent Delineau & JeromeB & Yan Naessens & Daniel Antelme
 * @author 	  Eric Lemeur pour les champs additionnels de type checkbox
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
$grr_script_name = "editentree.php"; 

$trad = $vocab;

// les variables attendues et leur type
$form_vars = array(
  'create_by'          => 'string',
  'name'               => 'string', // brève description
  'description'        => 'string',
  'start_day'          => 'int',
  'start_month'        => 'int',
  'start_year'         => 'int',
  'start_'             => 'string', // par ex. 12:00 
  'hour'               => 'int', // depuis un planning
  'minute'             => 'int',
  'day'                => 'int',
  'month'              => 'int',
  'year'               => 'int',
  'start_time'         => 'int',
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
  'edit_type'          => 'string',  // séries ?
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
  'page'               => 'string', // page du planning, pour le lien de retour en cas de succès
  'room_back'          => 'string', // pallie l'absence de day_all
  'page_ret'           => 'string', // page d'appel, pour le retour en cas d'échec
  'type_affichage_reser' => 'int',
  'duration'           => 'int',
  'confirm_reservation'=> 'string',
  'period'             => 'int',
  'end_period'         => 'int',
  'dur_units'          => 'string',
  'del_entry_in_conflict' => 'string',
  'skip_entry_in_conflict' => 'string',
  'copier'             => 'string',
  'nbparticipantmax'   => 'int',
  'vacances'           => 'int', // 0: ts les jours, 1: jours de vacances scolaires, 2: jours hors vacances
  'feries'             => 'int'  // 0: ts les jours, 1: jours fériés, 2: jours ouvrés
);
// tableau à compléter autant que nécessaire
// récupération des valeurs des variables passées en paramètres
foreach($form_vars as $var => $var_type)
{
    if ($var_type != "array"){
        $$var = getFormVar($var, $var_type);
        if (($var_type == "string")&&($$var !== NULL)){$$var = trim($$var);}
    }
    else{ // traitement d'un tableau
        $$var = getFormVar($var,'');
        $$var = (array) $$var;
    }
}
// traiter aussi les champs additionnels (addon_x)!
$res = grr_sql_query("SELECT id FROM ".TABLE_PREFIX."_overload");
$overloadFields = array(); // contiendra, s'il en existe, les valeurs des champs additionnels définies dans le formulaire
if ($res){
    foreach($res as $row){
        $overloadField = 'addon_'.$row['id'];
        $overloadFields[$row['id']] = getFormVar($overloadField);
    }
}
grr_sql_free($res);

// traitement des données
/* URL de retour. À faire avant l'ouverture de session.
 En effet, nous pourrions passer par edit_entry plus d'une fois, par exemple si nous devons nous reconnecter par timeout. 
 Nous devons toujours conserver la page d'appel d'origine afin qu'une fois que nous avons quitté edit_entry_handler, nous puissions revenir à la page d'appel (plutôt que d'aller à la vue par défaut). 
 Si c'est la première fois, alors $_SERVER['HTTP_REFERER'] contient l'appelant d'origine. Si c'est la deuxième fois, nous l'aurons stocké dans $page_ret.*/
if (!isset($page_ret) || ($page_ret == ''))
{
    $referer = isset($_SERVER['HTTP_REFERER']) ? htmlspecialchars($_SERVER['HTTP_REFERER']) : '';
    $ref = explode('?',$referer);
    if (isset($ref[0]) && 
        ((strpos($ref[0],'edit_entry.php') !== FALSE)||
         (strpos($ref[0],'view_entry.php') !== FALSE)||
         (strpos($ref[0],'validation.php') !== FALSE)))
    {
        if (isset($page) && isset($month) && isset($day) && isset($year)){
            $page_ret = $page.'.php?month='.$month.'&amp;day='.$day.'&amp;year='.$year;
            if (isset($room))
                $page_ret .= '&amp;room='.$room;
            elseif ((!strpos($page,"all"))&&($room_back != 'all'))
                $page_ret .= "&amp;room=".$room_back;
            elseif (isset($area))
                $page_ret .= '&amp;area='.$area;
            elseif (($room_back !='')&&($room_back != 'all')){
                $area = mrbsGetRoomArea($room_back);
                $page_ret .= '&amp;area='.$area;
            }
            elseif (($room_back == 'all')&&(isset($id))){
                $room = grr_sql_query1("SELECT room_id FROM ".TABLE_PREFIX."_entry WHERE id=".$id."");
                if ($room != -1){
                    $area = mrbsGetRoomArea($room);
                    $page_ret .= '&amp;area='.$area;
                }
            }
        }
        else 
            $page_ret = page_accueil();
    }
    else 
        $page_ret = isset($referer)? $referer : page_accueil();
}

// ouverture de session
require_once("./include/session.inc.php");
// Resume session
if (!grr_resumeSession())
{
	header("Location: ./app.php?p=deconnexion&auto=1&url=$url"); // $url sort de session.inc.php
	die();
}

$user_name = getUserName(); // ici on devrait avoir un identifiant

if (isset($period))
	$end_period = $period;
if (!isset($edit_type))
    $edit_type = '';
$page = verif_page();
$hour = getFormVar('hour','int'); // depuis un planning
$minute = getFormVar('minute','int');
if (!isset($hour) || !isset($minute)){
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
}
if ($hour < 10) $hour = "0".$hour;
if ($minute < 10) $minute = "0".$minute;

global $twentyfourhour_format;
if (!isset($day) || !isset($month) || !isset($year))
{
	$day   = date("d");
	$month = date("m");
	$year  = date("Y");
}

if (isset($id)){ // edition d'une réservation
	if ($info = mrbsGetEntryInfo($id)){
		$room = $info["room_id"];
        $area = mrbsGetRoomArea($room);
	}
	else{
		$area = -1;
		$room = -1;
	}
}
elseif(isset($room)){ // récupéré dans le formulaire
    $area = mrbsGetRoomArea($room);
}
elseif(isset($areas)){ // récupéré dans le formulaire
    $room = grr_sql_query1("SELECT min(id) FROM ".TABLE_PREFIX."_room WHERE area_id='".$areas."' ORDER BY order_display,room_name");
}
else{
	Definition_ressource_domaine_site(); // rend éventuellement $room -> NULL ce qui pose problème ensuite
    //echo "<br> ligne 265";
    if (!isset($room)){ 
        $room_back = 'all';
        $room_id = grr_sql_query1("SELECT min(id) FROM ".TABLE_PREFIX."_room WHERE area_id='".$area."' ORDER BY order_display,room_name");
        $room = $room_id; // à voir
    }
}

// l'utilisateur est-il autorisé à être ici ?
if (((authGetUserLevel($user_name,-1) < 2) && (auth_visiteur($user_name,$room) == 0))||(authUserAccesArea($user_name, $area) == 0))
{
    start_page_w_header('','','','with_session');
	showAccessDenied($page_ret);
	exit();
}
if (isset($room) && ($room != -1)){// on vérifie que la ressource n'est pas restreinte ou que l'accès est autorisé
    $who_can_book = grr_sql_query1("SELECT who_can_book FROM ".TABLE_PREFIX."_room WHERE id='".$room."' ");
    if (!($who_can_book || (authBooking($user_name,$room)) || (authGetUserLevel($user_name,$room) > 2))){
        start_page_w_header('','','','with_session');
        showAccessDenied($page_ret."&alerte=acces");
        exit();
    }
}
// récupérons les paramètres du domaine en cours
get_planning_area_values($area);
if (isset($room) && ($room != -1)){ // on récupère les propriétés de la ressource
    $sql = "SELECT * FROM ".TABLE_PREFIX."_room WHERE id='".$room."'";
    $res = grr_sql_query($sql);
    $Room = array();
    if ($res && (grr_sql_count($res) == 1))
        $Room = grr_sql_row_keyed($res,0);
    else 
        fatal_error(1,grr_sql_error()." ou erreur de lecture dans la base");
    grr_sql_free($res);
}

$type_affichage_reser = isset($Room['type_affichage_reser'])? $Room['type_affichage_reser']: -1;
$delais_option_reservation = isset($Room['delais_option_reservation'])? $Room['delais_option_reservation']: -1;
$qui_peut_reserver_pour = isset($Room['qui_peut_reserver_pour'])? $Room['qui_peut_reserver_pour']: -1;
$d['active_cle'] = isset($Room['active_cle'])? $Room['active_cle']: -1;
$d['active_ressource_empruntee'] = grr_sql_query1("SELECT active_ressource_empruntee FROM ".TABLE_PREFIX."_room WHERE id='".$room."'");
$d['active_participant'] = isset($Room['active_participant'])? $Room['active_participant']: -1;
$periodiciteConfig = Settings::get("periodicite");
$longueur_liste_ressources_max = Settings::get("longueur_liste_ressources_max");
if ($longueur_liste_ressources_max == '')
	$longueur_liste_ressources_max = 20;
// le jour est-il ouvert à la réservation ?
if (check_begin_end_bookings($day, $month, $year))
{
    start_page_w_header('','','','with_session');
	showNoBookings($day, $month, $year, $page_ret);
	exit();
}
// les droits à réserver sont-ils épuisés ?
if (isset($id) && ($id != 0))
	$compt = 0;
else
	$compt = 1;
// echo "<br>id résa".$id;
//die();
if (UserRoomMaxBooking($user_name, $room, $compt) == 0)
{
    echo "<br> user : ".$user_name." room: ".$room." compt : ".$compt;
    start_page_w_header('','','','with_session');
	showAccessDeniedMaxBookings($day, $month, $year, $room, $page_ret);
	exit();
}
$etype = 0;

if (isset($id)) // édition d'une réservation existante
{
    if (!getWritable($user_name,$id) && ($copier == ''))
    {
        start_page_w_header('','','','with_session');
        showAccessDenied($page_ret);
        exit;
    }
	$sql = "SELECT * FROM ".TABLE_PREFIX."_entry WHERE id=$id";
    $res = grr_sql_query($sql);
	if (!$res)
		fatal_error(1, grr_sql_error());
	if (grr_sql_count($res) != 1)
		fatal_error(1, get_vocab('entryid') . $id . get_vocab('not_found'));
	$row = grr_sql_row_keyed($res, 0);
    $data = array_merge(array(), $row);
	grr_sql_free($res);
	$name = $row['name'];
	$beneficiaire = $row['beneficiaire'];
	$beneficiaire_ext = $row['beneficiaire_ext'];
	$tab_benef = donne_nom_email($beneficiaire_ext);
	$create_by = ($copier == 'copier')? $user_name : $row['create_by'];
	$description = $row['description'];
	$d['statut_entry'] = $row['statut_entry'];
	$day = date('d', $row['start_time']);
	$month = date('m', $row['start_time']);
	$year = date('Y', $row['start_time']);
	$start_hour = date('H', $row['start_time']);
	$start_min = date('i', $row['start_time']);
	$end_day = date('d', $row['end_time']);
	$end_month = date('m', $row['end_time']);
	$end_year = date('Y', $row['end_time']);
	$end_hour = date('H', $row['end_time']);
	$end_min  = date('i', $row['end_time']);
	$duration = $row['end_time']-$row['start_time'];
	$etype = $row['type'];
	$room_id = $row['room_id'];
	$entry_type = $row['entry_type'];
	$rep_id = $row['repeat_id'];
	$option_reservation = $row['option_reservation'];
	$jours_c = $row['jours'];
	$d['clef'] = $row['clef'];
	$d['courrier'] = $row['courrier'];
    $d['nbparticipantmax'] = $row['nbparticipantmax'];
	$modif_option_reservation = 'n';

	if ($entry_type >= 1) // entrée associée à une périodicité
	{
		$sql = "SELECT rep_type, start_time, end_date, rep_opt, rep_num_weeks, end_time, type, name, beneficiaire, description
		FROM ".TABLE_PREFIX."_repeat WHERE id='".protect_data_sql($rep_id)."'";
		$res = grr_sql_query($sql);
		if (!$res)
			fatal_error(1, grr_sql_error());
		if (grr_sql_count($res) != 1)
			fatal_error(1, get_vocab('repeat_id') . $rep_id . get_vocab('not_found'));
		$row = grr_sql_row($res, 0);
		grr_sql_free($res);
		$rep_type = $row[0];
		if ($rep_type == 2) // périodicité chaque semaine, etc.
			$rep_num_weeks = $row[4];
		if ($rep_type == 7) // périodicité X Y du mois
		{
			$rep_month_abs1 = $row[4];
			$rep_month_abs2 = $row[3];
		}
		if ($edit_type == "series")
		{
			$day   = date('d', $row[1]);
			$month = date('m', $row[1]);
			$year  = date('Y', $row[1]);
			$start_hour  = date('H', $row[1]);
			$start_min   = date('i', $row[1]);
			$duration    = $row[5]-$row[1];
			//$end_day   = strftime('%d', $row[5]);
			//$end_month = strftime('%m', $row[5]);
			//$end_year  = strftime('%Y', $row[5]);
			//$end_hour  = strftime('%H', $row[5]);
			//$end_min   = strftime('%M', $row[5]);
			$rep_end_day   = date('d', $row[2]);
			$rep_end_month = date('m', $row[2]);
			$rep_end_year  = date('Y', $row[2]);
			$type = $row[6];
			$name = $row[7];
			$beneficiaire = $row[8];
			$description = $row[9];
			if ($rep_type==2)
			{
				$rep_day[0] = $row[3][0] != '0';
				$rep_day[1] = $row[3][1] != '0';
				$rep_day[2] = $row[3][2] != '0';
				$rep_day[3] = $row[3][3] != '0';
				$rep_day[4] = $row[3][4] != '0';
				$rep_day[5] = $row[3][5] != '0';
				$rep_day[6] = $row[3][6] != '0';
			}
			else
				$rep_day = array(0, 0, 0, 0, 0, 0, 0);
		}
		else
		{
			$rep_end_date = utf8_strftime($dformat,$row[2]);
			$rep_opt      = $row[3];
			$start_time = $row[1];
			$end_time = $row[5];
		}
	}
	else
	{
		$flag_periodicite = 'y'; // utilisé pour le non-affichage de la seconde colonne pour une réservation non périodique
		$rep_id        = 0;
		$rep_type      = 0;
		$rep_end_day   = $day;
		$rep_end_month = $month;
		$rep_end_year  = $year;
		$rep_day       = array(0, 0, 0, 0, 0, 0, 0);
		$rep_jour      = 0;
	}
}
else // nouvelle réservation
{
	if ($enable_periods == 'y')
		$duration = 60; // une période = une minute à partir de midi
	else
	{
		$duree_par_defaut_reservation_area = grr_sql_query1("SELECT duree_par_defaut_reservation_area FROM ".TABLE_PREFIX."_area WHERE id='".$area."'");
		if ($duree_par_defaut_reservation_area == 0)
			$duree_par_defaut_reservation_area = $resolution;
		$duration = $duree_par_defaut_reservation_area ;
	}
	$edit_type = "series";
	if (Settings::get("remplissage_description_breve") == '2')
		$name = $_SESSION['prenom']." ".$_SESSION['nom'];
	else
		$name = "";

	$beneficiaire   = $user_name;
	$create_by      = $user_name;
	$description    = "";
	$start_hour     = $hour;
	$start_min      = (isset($minute)) ? $minute : '00';
	if ($enable_periods == 'y')
	{
		$end_day    = $day;
		$end_month  = $month;
		$end_year   = $year;
		$end_hour   = $hour;
		$end_min    = (isset($minute)) ? $minute : '00';
	}
	else
	{
		$now        = mktime($hour, $minute, 0, $month, $day, $year);
		$fin        = $now + $duree_par_defaut_reservation_area;
		$end_day    = date("d",$fin);
		$end_month  = date("m",$fin);
		$end_year   = date("Y",$fin);
		$end_hour   = date("H",$fin);
		$end_min    = date("i",$fin);
	}
    $etype          = isset($type)? $type : 0;
	$type        	= "";
	$room_id     	= $room;
	$id				= 0;
	$rep_id        	= 0;
	$rep_type      	= 0;
	$rep_end_day   	= $day;
	$rep_end_month 	= $month;
	$rep_end_year  	= $year;
	$rep_day       	= array(0, 0, 0, 0, 0, 0, 0);
	$rep_jour      	= 0;
	$option_reservation = -1;
	$modif_option_reservation = 'y';
	$d['nbparticipantmax'] = 0;
}
// fin nouvelle réservation
$Err = getFormVar("Err",'string'); // utilité ?
if ($enable_periods == 'y')
	toPeriodString($start_min, $duration, $dur_units);
else{
    $duree_sec = $duration; // durée en secondes
    toTimeString($duration, $dur_units, true); // durée convertie en clair
}

$nb_areas = 0;
$sql = "SELECT id, area_name FROM ".TABLE_PREFIX."_area";
$res = grr_sql_query($sql);
$allareas_id = array();
if ($res)
{
    foreach($res as $row)
	{
		array_push($allareas_id, $row['id']);
		if (authUserAccesArea($user_name, $row['id'])==1)
		{
			$nb_areas++;
		}
	}
}
else 
    fatal_error(1,grr_sql_error());
grr_sql_free($res);

if ($id == 0)
	$d['titre'] = get_vocab("addentry");
else
{
	if ($edit_type == "series")
		$d['titre'] = get_vocab("editseries");
	else
	{
		if (isset($_GET["copier"]))
			$d['titre'] = get_vocab("copyentry");
		else
			$d['titre'] = get_vocab("editentry");
	}
}

$area_id = mrbsGetAreaIdFromRoomId($room_id);

$d['resaBreveDescription'] = htmlspecialchars($name); // brève description
$d['resaDescription'] = htmlspecialchars ( $description );
$d['moderate'] = isset($Room['moderate'])? $Room['moderate']: -1;
$d['domaine'] = $area_id;
$d['roomid'] = $room_id;
$d['idresa'] = $id;


/**
 * Partie Benéficiaire
*/

$qui_peut_reserver_pour  = grr_sql_query1("SELECT qui_peut_reserver_pour FROM ".TABLE_PREFIX."_room WHERE id='".$room."'");
$flag_qui_peut_reserver_pour = (authGetUserLevel($user_name, $room, "room") >= $qui_peut_reserver_pour); // accès à la ressource
$flag_qui_peut_reserver_pour = $flag_qui_peut_reserver_pour || (authGetUserLevel($user_name, $area_id, "area") >= $qui_peut_reserver_pour); // accès au domaine
$flag_qui_peut_reserver_pour = $flag_qui_peut_reserver_pour && (($id == 0) || (authGetUserLevel($user_name, $room) > 2) ); // création d'une nouvelle réservation ou usager 

if ($flag_qui_peut_reserver_pour ) // on crée les sélecteurs à afficher 
{
    $d['flag_qui_peut_reserver_pour'] = true;
    $benef = "";
    $benef_ext_nom = "";
    if ($id == 0 && isset($_COOKIE['beneficiaire_default']))
        $benef = $_COOKIE['beneficiaire_default'];
    elseif ($id != 0) 
        $benef = grr_sql_query1("SELECT beneficiaire FROM ".TABLE_PREFIX."_entry WHERE id='".$id."' ");
    //echo 'benef :'.$benef;
    if ($benef == -1){
        $benef = "";
        $benef_ext = grr_sql_query1("SELECT beneficiaire_ext FROM ".TABLE_PREFIX."_entry WHERE id='".$id."' ");
        $tab_benef = explode('|',$benef_ext);
        $benef_ext_nom = $tab_benef[0];
        $d['benef_ext_email'] = (isset($tab_benef[1]))? $tab_benef[1]:"";
        //print_r($tab_benef);
    }
    $bnf = array(); // tableau des bénéficiaires autorisés (login,nom,prénom)
    $sql = "SELECT DISTINCT login, nom, prenom FROM ".TABLE_PREFIX."_utilisateurs WHERE (etat!='inactif') OR (login='".$user_name."') ORDER BY nom, prenom"; // login = $user_name superflu ?
    $res = grr_sql_query($sql);
    if ($res){
        for ($i = 0; ($row = grr_sql_row($res, $i)); $i++) {$bnf[$i] = $row;}
    }
    //print_r($bnf);
    grr_sql_free($res);
    $option = "";
    if (!isset($benef_ext_nom))
        $option .= '<option value="" >'.get_vocab("personne_exterieure").'</option>'.PHP_EOL;
    else
        $option .= '<option value="" selected="selected">'.get_vocab("personne_exterieure").'</option>'.PHP_EOL;
    foreach ($bnf as $b){
        $option .= '<option value="'.$b[0].'" ';
        if (((!$benef && !$benef_ext_nom) && strtolower($user_name) == strtolower($b[0])) || ($benef && $benef == $b[0]))
            {
                $option .= ' selected="selected" ';
            }
        $option .= '>'.$b[1].' '.$b[2].'</option>'.PHP_EOL;
    }
    $test = grr_sql_query1("SELECT login FROM ".TABLE_PREFIX."_utilisateurs WHERE login='".$user_name."'");
    if (($test == -1) && ($user_name != ''))
    {
        $option .= '<option value="-1" selected="selected" >'.get_vocab("utilisateur_inconnu").$user_name.'</option>'.PHP_EOL;
    }
    $d['selectBeneficiare'] = $option;

    if (!$benef_ext_nom)
        $d['selectBeneficiareExt'] = $benef_ext_nom;
}


$d['selectionDateDebut'] = jQuery_DatePickerTwig('start_');

if ($enable_periods == 'y')
{
    $d['optionHeureDebut'] = "";
	foreach ($periods_name as $p_num => $p_val)
	{
        $d['optionHeureDebut'] .= '<option value="'.$p_num.'"';
		if ((isset( $period ) && $period == $p_num ) || $p_num == $start_min)
			$d['optionHeureDebut'] .= ' selected="selected"';
		$d['optionHeureDebut'] .= '>'.$p_val.'</option>';
	}
}
else
{
	if (isset($id) && ($id != 0))
	{
		$d['jQuery_TimePickerStart'] = jQuery_TimePickerTwig('start_', $start_hour, $start_min,$duree_sec,$resolution,$morningstarts,$eveningends,$eveningends_minutes,$twentyfourhour_format);
	}
	else
	{
		$d['jQuery_TimePickerStart'] = jQuery_TimePickerTwig('start_', '', '',$duree_par_defaut_reservation_area,$resolution,$morningstarts,$eveningends,$eveningends_minutes,$twentyfourhour_format);
	}
}

if ($type_affichage_reser == 0) // sélection de la durée
{
	//echo '<div class="E form-inline">'.PHP_EOL;
    //echo '<label for="duration">'.get_vocab("duration").'</label>'.PHP_EOL;
	//echo '<input class="form-control" id="duration" name="duration" type="number" value="'.$duration.'" min="1">'; 
    ///echo '<select class="form-control" name="dur_units">'.PHP_EOL;
    if ($enable_periods == 'y')
		$units = array("periods", "days");
	else
	{
		$duree_max_resa_area = grr_sql_query1("SELECT duree_max_resa_area FROM ".TABLE_PREFIX."_area WHERE id='".$area."'");
		if ($duree_max_resa_area < 0) // pas de limite
			$units = array("minutes", "hours", "days", "weeks");
		else if ($duree_max_resa_area < 60) // limite inférieure à une heure, etc.
			$units = array("minutes");
		else if ($duree_max_resa_area < 60*24)
			$units = array("minutes", "hours");
		else if ($duree_max_resa_area < 60*24*7)
			$units = array("minutes", "hours", "days");
		else
			$units = array("minutes", "hours", "days", "weeks");
	}
    $d['option_unite_temps'] = "";
    foreach($units as $unit)
	{
		$d['option_unite_temps'] .= '<option value="'.$unit.'"';
		if ($dur_units ==  get_vocab($unit))
            $d['option_unite_temps'] .= ' selected="selected"';
        $d['option_unite_temps'] .= '>'.get_vocab($unit).'</option>'.PHP_EOL;
	}

	// l'heure de fin du jour est définie par eveningends et eveningends_minutes
	// on suppose les données vérifiées : eveningends:eveningends_minutes <= 24:00
    $af_fin_hr = substr("0".$eveningends,-2,2);
    $af_fin_min = substr("0".$eveningends_minutes,-2,2);
    if ($af_fin_min =='60'){
        $af_fin_hr++;
        $af_fin_min = '00';
    }
	$af_fin_jour = $af_fin_hr." H ".$af_fin_min;
    $d['enable_periods'] = $enable_periods;

	if ($enable_periods != 'y')
    {
        $d['morningstarts'] = $morningstarts;
        $d['af_fin_jour'] = $af_fin_jour;
    }
}
else // sélection de l'heure ou du créneau de fin
{
	$d['selectionDateFin'] = jQuery_DatePickerTwig('end_');

	if ($enable_periods=='y')
	{
        $d['optionHeureFin'] = "";
		foreach ($periods_name as $p_num => $p_val)
		{
			$d['optionHeureFin'] .= "<option value=\"".$p_num."\"";
			if ( ( isset( $end_period ) && $end_period == $p_num ) || ($p_num+1) == $end_min)
				$d['optionHeureFin'] .= " selected=\"selected\"";
            $d['optionHeureFin'] .= ">$p_val</option>\n";
		}
	}
	else
	{
		if (isset($id) && ($id != 0))
		{
			$d['jQuery_TimePickerEnd'] = jQuery_TimePickerTwig('end_', $end_hour, $end_min,$duree_sec,$resolution,$morningstarts,$eveningends,$eveningends_minutes,$twentyfourhour_format);
		}
		else
		{
			$d['jQuery_TimePickerEnd'] = jQuery_TimePickerTwig('end_', '', '',$duree_par_defaut_reservation_area,$resolution,$morningstarts,$eveningends,$eveningends_minutes,$twentyfourhour_format);
		}
	}
// fin heure de fin
}
/*
* Domaines
*/
if ($enable_periods == 'y')
	$sql = "SELECT id, area_name FROM ".TABLE_PREFIX."_area WHERE id='".$area."' ORDER BY area_name";
else
	$sql = "SELECT id, area_name FROM ".TABLE_PREFIX."_area WHERE enable_periods != 'y' ORDER BY order_display,area_name";
$res = grr_sql_query($sql);
if ($res)
{
    $d['optionsDomaine'] = '';
    foreach($res as $row)
	{
		if (authUserAccesArea($user_name,$row['id']) == 1)
		{
			$selected = "";
			if ($row['id'] == $area)
				$selected = 'selected="selected"';
			$d['optionsDomaine'] .='<option '.$selected.' value="'.$row['id'].'">'.$row['area_name'].'</option>';
		}
	}
}
grr_sql_free($res);
/*
* Ressources
*/
$tab_rooms_noaccess = no_book_rooms($user_name);
$sql = "SELECT id, room_name, description FROM ".TABLE_PREFIX."_room WHERE area_id=$area_id ";
foreach ($tab_rooms_noaccess as $key)
{
	$sql .= " and id != $key ";
}
$sql .= " ORDER BY order_display,room_name";
$res = grr_sql_query($sql);
$len = grr_sql_count($res);
if ($res)
{
    $d['taille_champ_res'] = min($longueur_liste_ressources_max,$len);
    $d['optionsRessource'] = "";
    foreach($res as $row)
	{
		$selected = "";
		if ($row['id'] == $room_id)
			$selected = 'selected="selected"';
        $d['optionsRessource'] .= '<option '.$selected.' value="'.$row['id'].'" title="'.$row['description'].'">'.$row['room_name'].'</option>';
	}
}
grr_sql_free($res);

// réservation conditionnelle
if (($delais_option_reservation > 0) && (($modif_option_reservation == 'y') || ((($modif_option_reservation == 'n') && ($option_reservation != -1)))))
{
	$day   = date("d");
	$month = date("m");
	$year  = date("Y");

    $d['resaAconfirmer'] = 1;

	//echo '<div class="E"><br><div class="col col-xs-12"><div class="alert alert-danger" role="alert"><b>'.get_vocab("reservation_a_confirmer_au_plus_tard_le").'</div>'.PHP_EOL;
	if ($modif_option_reservation == 'y')
	{
        $d['resaAconfirmer'] = 2;
        $d['optionsResaAconfirmer'] = "";
		$k = 0;
		$selected = 'n';
		$aff_options = "";
		while ($k < $delais_option_reservation + 1)
		{
			$day_courant = $day + $k;
			$date_courante = mktime(0, 0, 0, $month, $day_courant,$year);
			$aff_date_courante = time_date_string_jma($date_courante,$dformat);
			$aff_options .= "<option value = \"".$date_courante."\" ";
			if ($option_reservation == $date_courante)
			{
				$aff_options .= " selected=\"selected\" ";
				$selected = 'y';
			}
			$aff_options .= ">".$aff_date_courante."</option>\n";
			$k++;
		}

		if (($selected == 'n') and ($option_reservation != -1))
		{
			$d['optionsResaAconfirmer'] = "<option value = \"".$option_reservation."\" selected=\"selected\">".time_date_string_jma($option_reservation, $dformat)."</option>\n";
		}
		$d['optionsResaAconfirmer'] = $aff_options;
	}
	else
	{
        $d['resaAconfirmer'] = 3;
        $d['option_reservation'] = $option_reservation;
        $d['resaAconfirmerDate'] = time_date_string_jma($option_reservation,$dformat);
	}
}

$d['levelUserRessource'] = authGetUserLevel($user_name,$room_id);

/** 
* fin du bloc de "gauche"
* Début du droit  (Périodicités)
*/

$weeklist = array("unused","every_week","week_1_of_2","week_1_of_3","week_1_of_4","week_1_of_5");
$monthlist = array("firstofmonth","secondofmonth","thirdofmonth","fouthofmonth","fiveofmonth","lastofmonth");

if($periodiciteConfig == 'y')
{
	if ( ($edit_type == "series") || (isset($flag_periodicite)))
	{
        $d['periodiciteAttache'] = 0;
        $d['repHTML'] = "";

//		echo '<div id="menu1" style="display:none;">',PHP_EOL; // choix de la périodicité
//        echo '<p class="F"><b>',get_vocab("rep_type"),'</b></p>',PHP_EOL;
		
        for ($i = 0; $i < 8 ; $i++)
		{
            if ($i == 6 && Settings::get("jours_cycles_actif") == "Non")
                continue;
            elseif ($i != 5)
            {
                $d['repHTML'] .= PHP_EOL.'<div><label><input name="rep_type" type="radio" value="'.$i.'"';
                if (($i == $rep_type) || (($i == 3) && ($rep_type == 5)))
                    $d['repHTML'] .= ' checked="checked"';
                $d['repHTML'] .= ' onclick="check_1()" />'.PHP_EOL; // fin input
                if (($i != 2) && ($i != 3))
                    $d['repHTML'] .= get_vocab("rep_type_$i").'</label>'.PHP_EOL;
                if ($i == '2') // semaine
				{
                    $d['repHTML'] .= '&nbsp;</label><select class="form-control" name="rep_num_weeks" size="1" onfocus="check_2()" onclick="check_2()">'.PHP_EOL;
                    $d['repHTML'] .= '<option value="1" >'.get_vocab("every_week").'</option>'.PHP_EOL;
					for ($weekit = 2; $weekit < 6; $weekit++)
					{
                        $d['repHTML'] .= '<option value="'.$weekit.'"';
						if ($rep_num_weeks == $weekit)
                            $d['repHTML'] .= ' selected="selected"';
                        $d['repHTML'] .= '>'.get_vocab($weeklist[$weekit]).'</option>'.PHP_EOL;
					}
                    $d['repHTML'] .= '</select>'.PHP_EOL;
                    $d['repHTML'] .= "<div style=\"display:none\" id=\"menu2\" width=\"100%\">\n"; // jour(s) de la semaine
                    $d['repHTML'] .= "<div class='F'><b>".get_vocab("rep_rep_day")."</b></div>\n";
                    $d['repHTML'] .= "<div class=\"CL\">";
                    for ($da = 0; $da < 7; $da++)
                    {
                        $wday = ($da + $weekstarts) % 7;
                        $d['repHTML'] .= "<input name=\"rep_day[$wday]\" type=\"checkbox\"";
                        if ($rep_day[$wday])
                            $d['repHTML'] .= " checked=\"checked\"";
                        $d['repHTML'] .= " onclick=\"check_1()\" />" . day_name($wday) . "\n";
                    }
                    $d['repHTML'] .= "</div>\n</div>\n";
				}
                if ($i == '3') // mensuel
				{
					$monthrep3 = ($rep_type == 3)? " selected=\"selected\" ": "";
					$monthrep5 = ($rep_type == 5)? " selected=\"selected\" ": "";
                    $d['repHTML'] .= '&nbsp;</label><select class="form-control" name="rep_month" size="1" onfocus="check_3()" onclick="check_3()">'.PHP_EOL;
                    $d['repHTML'] .= "<option value=\"3\" $monthrep3>".get_vocab("rep_type_3")."</option>\n";
                    $d['repHTML'] .= "<option value=\"5\" $monthrep5>".get_vocab("rep_type_5")."</option>\n";
                    $d['repHTML'] .= "</select>\n";
				}
                if ($i == '7') // X Y du mois
				{
                    $d['repHTML'] .= '<select class="form-control" name="rep_month_abs1" size="1" onfocus="check_7()" onclick="check_7()">'.PHP_EOL;
					for ($weekit = 0; $weekit < 6; $weekit++)
					{
                        $d['repHTML'] .= "<option value=\"".$weekit."\"";
						if ($weekit == $rep_month_abs1)
                            $d['repHTML'] .= " selected='selected' ";
                        $d['repHTML'] .= ">".get_vocab($monthlist[$weekit])."</option>\n";
					}
                    $d['repHTML'] .= '</select>'.PHP_EOL;
                    $d['repHTML'] .= '<select class="form-control" name="rep_month_abs2" size="1" onfocus="check_8()" onclick="check_8()">'.PHP_EOL;
					for ($weekit = 1; $weekit < 8; $weekit++)
					{
                        $d['repHTML'] .= "<option value=\"".$weekit."\"";
						if ($weekit == $rep_month_abs2)
                            $d['repHTML'] .= " selected='selected' ";
                        $d['repHTML'] .= ">".day_name($weekit)."</option>\n";
					}
                    $d['repHTML'] .= "</select>\n";
                    $d['repHTML'] .= get_vocab("ofmonth");
				}
                if ($i == 6)
                {
                    $d['repHTML'] .= "<div id='menuP'>\n"; // choix des jours cycle
                    $d['repHTML'] .= "<b>Jours/Cycle</b><br />\n";
                    $d['repHTML'] .= "<div class='form-inline'>";
                    for ($da = 1; $da <= (Settings::get("nombre_jours_Jours_Cycles")); $da++)
                    {
                        $wday = $da;
                        $d['repHTML'] .= "<input type=\"radio\" name=\"rep_jour_\" value=\"$wday\"";
                        if (isset($jours_c))
                        {
                            if ($da == $jours_c)
                            $d['repHTML'] .= ' checked="checked"';
                        }
                        $d['repHTML'] .= ' onclick="check_1()" />'.get_vocab("rep_type_6").' '.$wday.PHP_EOL;
                    }
                    $d['repHTML'] .= '</div>'.PHP_EOL;
                    $d['repHTML'] .= '</div>'.PHP_EOL;
                }
                $d['repHTML'] .= '</div>'.PHP_EOL;
            }
        }
	//	echo "<div class=\"F\"><b>".get_vocab("rep_end_date")."</b>".PHP_EOL;
        $d['jQuery_DatePickerRepEnd'] = jQuery_DatePickerTwig('rep_end_');
   //     echo '</div>'.PHP_EOL;
	//	echo "</div>\n"; // fin menu1
	}
	else
	{
        $d['periodiciteAttache'] = 1 ;
		//echo "<p><b>".get_vocab('periodicite_associe').get_vocab('deux_points')."</b></p>\n";
		if ($rep_type == 2)
			$affiche_period = get_vocab($weeklist[$rep_num_weeks]);
		else
			$affiche_period = get_vocab('rep_type_'.$rep_type);
		echo '<p><b>'.get_vocab('rep_type').'</b> '.$affiche_period.'</p>'."\n";
		if ($rep_type != 0)
		{
			$opt = '';
			if ($rep_type == 2)
			{
				$nb = 0;
				for ($i = 0; $i < 7; $i++)
				{
					$wday = ($i + $weekstarts) % 7;
					if ($rep_opt[$wday])
					{
						if ($opt != '')
							$opt .=', ';
						$opt .= day_name($wday);
						$nb++;
					}
				}
			}
			if ($rep_type == 6)
			{
				$nb = 1;
				$opt .= get_vocab('jour_cycle').' '.$jours_c;
			}
			if ($opt)
				if ($nb == 1)
					echo '<p><b>'.get_vocab('rep_rep_day').'</b> '.$opt.'</p>'."\n";
				else
					echo '<p><b>'.get_vocab('rep_rep_days').'</b> '.$opt.'</p>'."\n";
				if ($enable_periods=='y') list( $start_period, $start_date) =  period_date_string($start_time);
				else $start_date = time_date_string($start_time,$dformat);
				$duration = $end_time - $start_time;
				if ($enable_periods=='y') toPeriodString($start_period, $duration, $dur_units);
				else toTimeString($duration, $dur_units, true);
				echo '<p><b>'.get_vocab("date").get_vocab("deux_points").'</b> '.$start_date.'</p>'."\n";
				echo '<p><b>'.get_vocab("duration").'</b> '.$duration .' '. $dur_units.'</p>'."\n";
				echo '<p><b>'.get_vocab('rep_end_date').'</b> '.$rep_end_date.'</p>'."\n";
		}
	}
}
// fin colonne de "droite" et du bloc de réservation

$d['rep_id'] = $rep_id;
$d['edit_type'] = $edit_type;
$d['page'] = $page;
$d['room_back'] = $room_back;
$d['page_ret'] = $page_ret;
$d['create_by'] = $create_by;
$d['type_affichage_reser'] = $type_affichage_reser;

if (isset($_GET["copier"]))
	$d['copier'] = 1;

if (isset($Err))
	$d['Err'] = $Err;

if (isset($cookie))
	$d['cookie'] = $cookie;

echo $twig->render('editentree.twig', array('trad' => $trad, 'd' => $d, 'settings' => $AllSettings));
?>