<?php
/**
 * edit_entry.php
 * Interface d'édition d'une réservation
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2022-01-20 15:18$
 * @author    Laurent Delineau & JeromeB & Yan Naessens & Daniel Antelme
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
$grr_script_name = "edit_entry.php"; 
$racine = "./";
$racineAd = "./admin/";
// à décommenter si besoin de débogage
/*ini_set('display_errors', 'On');
error_reporting(E_ALL); */
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
// fonctions locales
function pageHead($title,$locale) // $locale est la langue utilisée
{
    if (isset($_SESSION['default_style']))
        $sheetcss = 'themes/'.$_SESSION['default_style'].'/css';
    else {
        if (Settings::get("default_css"))
        $sheetcss = 'themes/'.Settings::get("default_css").'/css'; // thème global par défaut
    else
        $sheetcss = 'themes/default/css'; // utilise le thème par défaut s'il n'a pas été défini
    }
    if (isset($_GET['default_language']))
    {
        $_SESSION['default_language'] = clean_input($_GET['default_language']);
        if (isset($_SESSION['chemin_retour']) && ($_SESSION['chemin_retour'] != ''))
            header("Location: ".$_SESSION['chemin_retour']);
        else
            header("Location: ".traite_grr_url());
        die();
    }
    echo '<head>
    <meta charset="UTF-8">
	<title>'.$title.'</title>
    <link rel="shortcut icon" href="./favicon.ico" />
	<link rel="stylesheet" href="bootstrap/css/bootstrap.min.css" type="text/css" />';
//    <link rel="stylesheet" href="./js/flatpickr/flatpickr.min.css">
//    <link rel="stylesheet" href="./js/flatpickr/airbnb.css">
    echo '<link rel="stylesheet" href="./js/select2/css/select2.min.css" />
    <link rel="stylesheet" href="./bootstrap/css/select2-bootstrap.css" />
	<link rel="stylesheet" type="text/css" href="./bootstrap/css/jquery-ui.min.css" />
	<link rel="stylesheet" type="text/css" href="./bootstrap/css/jquery.timepicker.min.css" >
    <link rel="stylesheet" type="text/css" href="themes/default/css/style.css" />
    <link rel="stylesheet" type="text/css" href="'.$sheetcss.'/style.css" />';
    echo '
        <script src="./js/jquery-3.4.1.min.js"></script>
        <script src="./js/jquery-ui.min.js"></script>
        <script src="./js/jquery-ui-i18n.min.js"></script>
        <script src="./js/jquery.validate.js"></script>
        <script src="./js/jquery-ui-timepicker-addon.js"></script>
        <script src="./bootstrap/js/bootstrap.min.js"></script>
        <script src="./js/popup.js" charset="utf-8"></script>
        <script src="./js/jquery.timepicker.min.js"></script>
        <script src="./js/bootstrap-clockpicker.js"></script>
        <script src="./js/bootstrap-multiselect.js"></script>
        <script src="./js/clock_'.$locale.'.js"></script>
        <script src="./js/select2/js/select2.min.js"></script>
        <script src="./js/select2/js/i18n/'.$locale.'.js"></script>
        <script src="./js/bandeau.js"></script>
        <script src="./js/functions.js"></script>'; 
    echo '</head>';
}

function divBeneficiaire($id_resa=0,$id_user='',$id_room=-1,$id_area=-1){
    $qui_peut_reserver_pour  = grr_sql_query1("SELECT qui_peut_reserver_pour FROM ".TABLE_PREFIX."_room WHERE id='".$id_room."'");
    $flag_qui_peut_reserver_pour = (authGetUserLevel($id_user, $id_room, "room") >= $qui_peut_reserver_pour); // accès à la ressource
    $flag_qui_peut_reserver_pour = $flag_qui_peut_reserver_pour || (authGetUserLevel($id_user, $id_area, "area") >= $qui_peut_reserver_pour); // accès au domaine
    $flag_qui_peut_reserver_pour = $flag_qui_peut_reserver_pour && (($id_resa == 0) || (authGetUserLevel($id_user, $id_room) > 2) ); // création d'une nouvelle réservation ou usager 
    if ($flag_qui_peut_reserver_pour ) // on crée les sélecteurs à afficher 
    {
        $benef = "";
        $benef_ext_nom = "";
        $benef_ext_email = "";
        if ($id_resa == 0 && isset($_COOKIE['beneficiaire_default']))
            $benef = $_COOKIE['beneficiaire_default'];
        elseif ($id_resa != 0) 
            $benef = grr_sql_query1("SELECT beneficiaire FROM ".TABLE_PREFIX."_entry WHERE id='".$id_resa."' ");
        //echo 'benef :'.$benef;
        if ($benef == -1){
            $benef = "";
            $benef_ext = grr_sql_query1("SELECT beneficiaire_ext FROM ".TABLE_PREFIX."_entry WHERE id='".$id_resa."' ");
            $tab_benef = explode('|',$benef_ext);
            $benef_ext_nom = $tab_benef[0];
            $benef_ext_email = (isset($tab_benef[1]))? $tab_benef[1]:"";
            //print_r($tab_benef);
        }
        $bnf = array(); // tableau des bénéficiaires autorisés (login,nom,prénom)
        $sql = "SELECT DISTINCT login, nom, prenom FROM ".TABLE_PREFIX."_utilisateurs WHERE (etat!='inactif' and statut!='visiteur' ) OR (login='".$id_user."') ORDER BY nom, prenom";
        $res = grr_sql_query($sql);
        if ($res){
            for ($i = 0; ($row = grr_sql_row($res, $i)); $i++) {$bnf[$i] = $row;}
        }
        //print_r($bnf);
        grr_sql_free($res);
        $option = "";
        if (!isset($benef_ext_nom))
            $option .= '<option value="" >'.get_vocab("personne exterieure").'</option>'.PHP_EOL;
        else
            $option .= '<option value="" selected="selected">'.get_vocab("personne exterieure").'</option>'.PHP_EOL;
        foreach ($bnf as $b){
            $option .= '<option value="'.$b[0].'" ';
            if (((!$benef && !$benef_ext_nom) && strtolower($id_user) == strtolower($b[0])) || ($benef && $benef == $b[0]))
                {
                    $option .= ' selected="selected" ';
                }
            $option .= '>'.$b[1].' '.$b[2].'</option>'.PHP_EOL;
        }
        $test = grr_sql_query1("SELECT login FROM ".TABLE_PREFIX."_utilisateurs WHERE login='".$id_user."'");
        if (($test == -1) && ($id_user != ''))
        {
            $option .= '<option value="-1" selected="selected" >'.get_vocab("utilisateur_inconnu").$id_user.')</option>'.PHP_EOL;
        }
        echo '<div id="choix_beneficiaire" class="row">'.PHP_EOL;
        //echo '<label for="beneficiaire" >'.ucfirst(trim(get_vocab("reservation_au_nom_de"))).get_vocab("deux_points").'</label>'.PHP_EOL;
        echo '<div class="col-sm-9">'.PHP_EOL;
		echo '<label for="beneficiaire" >'.ucfirst(trim(get_vocab("reservation_au_nom_de"))).get_vocab("deux_points").'</label><br />'.PHP_EOL;
        echo '<select class="select2" name="beneficiaire" id="beneficiaire" onchange="check_4();">'.$option.'</select>'.PHP_EOL;
        echo '</div>';
        echo '<div class="col-sm-3">'.PHP_EOL;
        echo '<br /><input type="button" id="bnfdef" class="btn btn-primary" value="'.get_vocab("definir par defaut").'" onclick="setdefault(\'beneficiaire_default\',document.getElementById(\'main\').beneficiaire.options[document.getElementById(\'main\').beneficiaire.options.selectedIndex].value)" />'.PHP_EOL;
        echo '</div></div>'.PHP_EOL;
        echo '<div id="menu4" class="form-inline" ';
        if (!$benef_ext_nom) 
            echo ' style="display:none"';
        echo '>';
        echo '<div class="form-group col-sm-6">'.PHP_EOL;
        echo '    <div class="input-group">'.PHP_EOL;
        echo '      <div class="input-group-addon"><span class="glyphicon glyphicon-user"></span></div>'.PHP_EOL;
        echo '      <input class="form-control" type="text" name="benef_ext_nom" value="'.$benef_ext_nom.'" placeholder="'.get_vocab("nom beneficiaire").'" required onchange="check_4()">'.PHP_EOL;
        echo '    </div>'.PHP_EOL;
        echo '  </div>'.PHP_EOL;
        if (Settings::get("automatic_mail") == 'yes')
        {
            echo '<div class="form-group col-sm-6">'.PHP_EOL;
            echo '    <div class="input-group">'.PHP_EOL;
            echo '      <div class="input-group-addon"><span class="glyphicon glyphicon-envelope" ></span></div>'.PHP_EOL;
            echo '      <input class="form-control" type="email" name="benef_ext_email" value="'.$benef_ext_email.'" placeholder="'.get_vocab("email beneficiaire").'">'.PHP_EOL;
            echo '    </div>'.PHP_EOL;
            echo '</div>'.PHP_EOL;
        }
        echo '</div>'.PHP_EOL; // fin menu4
    }
    else // "réservation au nom de" impossible
    {
        echo '<input type="hidden" name="beneficiaire" value="'.$id_user.'" />'.PHP_EOL;
    }
}

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
  'nbparticipantmax'   => 'integer'
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
$res = grr_sql_query("SELECT id FROM ".TABLE_PREFIX."_overload");
$overloadFields = array(); // contiendra, s'il en existe, les valeurs des champs additionnels définies dans le formulaire
if ($res){
    foreach($res as $row){
        $overloadField = 'addon_'.$row['id'];
        $overloadFields[$row['id']] = getFormVar($overloadField);
    }
}
grr_sql_free($res);
//$overloadFields = mrbsOverloadGetFieldslist();
//print_r($overloadFields);
// vérification
/*
echo "<br>vérification<br>";
foreach($form_vars as $var => $var_type)
{
    echo $var.' -> ';print_r($$var);
    echo '<br/>';
}
echo "<br>Champs additionnels<br>";
print_r($overloadFields);
*/
//die();
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
//echo "referer : ".$_SERVER['HTTP_REFERER']." ref : ".$ref[0]." page retour : ".$page_ret."accueil : ".page_accueil();
// ouverture de session
require_once("./include/session.inc.php");
// Resume session
if (!grr_resumeSession())
{
	header("Location: ./logout.php?auto=1&url=$url"); // $url sort de session.inc.php
	die();
}
$user_name = getUserName(); // ici on devrait avoir un identifiant
// Paramètres langage
include "include/language.inc.php";
//echo $locale;
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
// echo "<br>heure : ".$hour." : ".$minute;
//die();
//$rep_num_weeks = '';
//$rep_month_abs1 = 0;
//$rep_month_abs2 = 1;
global $twentyfourhour_format;
if (!isset($day) || !isset($month) || !isset($year))
{
	$day   = date("d");
	$month = date("m");
	$year  = date("Y");
}

// l'utilisateur est-il autorisé à être ici ?
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
    //echo "<br>".$sql."<br>";
    //print_r($res);
    $Room = array();
    if ($res && (grr_sql_count($res) == 1))
        $Room = grr_sql_row_keyed($res,0);
    else 
        fatal_error(1,grr_sql_error()." ou erreur de lecture dans la base");
    grr_sql_free($res);
    //print_r($Room);
    //die();
}
if (@file_exists("language/lang_subst_".$area.".".$locale))
	include "language/lang_subst_".$area.".".$locale;

//$room_back = (isset($_GET['room_back']))? $_GET['room_back']: ((isset($_GET['room']))? $_GET['room'] :'all') ;
$type_affichage_reser = isset($Room['type_affichage_reser'])? $Room['type_affichage_reser']: -1;
$delais_option_reservation = isset($Room['delais_option_reservation'])? $Room['delais_option_reservation']: -1;
$qui_peut_reserver_pour = isset($Room['qui_peut_reserver_pour'])? $Room['qui_peut_reserver_pour']: -1;
$active_cle = isset($Room['active_cle'])? $Room['active_cle']: -1;
$active_participant  = isset($Room['active_participant'])? $Room['active_participant']: -1;
$periodiciteConfig = Settings::get("periodicite");
//$back = isset($_SERVER['HTTP_REFERER'])? htmlspecialchars( $_SERVER['HTTP_REFERER']): '';
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
	//$sql = "SELECT name, beneficiaire, description, start_time, end_time, type, room_id, entry_type, repeat_id, option_reservation, jours, create_by, beneficiaire_ext, statut_entry, clef, courrier FROM ".TABLE_PREFIX."_entry WHERE id=$id";
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
	$create_by = $row['create_by'];
	$description = $row['description'];
	$statut_entry = $row['statut_entry'];
	$day = strftime('%d', $row['start_time']);
	$month = strftime('%m', $row['start_time']);
	$year = strftime('%Y', $row['start_time']);
	$start_hour = strftime('%H', $row['start_time']);
	$start_min = strftime('%M', $row['start_time']);
	$end_day = strftime('%d', $row['end_time']);
	$end_month = strftime('%m', $row['end_time']);
	$end_year = strftime('%Y', $row['end_time']);
	$end_hour = strftime('%H', $row['end_time']);
	$end_min  = strftime('%M', $row['end_time']);
	$duration = $row['end_time']-$row['start_time'];
	$etype = $row['type'];
	$room_id = $row['room_id'];
	$entry_type = $row['entry_type'];
	$rep_id = $row['repeat_id'];
	$option_reservation = $row['option_reservation'];
	$jours_c = $row['jours'];
	$clef = $row['clef'];
	$courrier = $row['courrier'];
    $nbparticipantmax = $row['nbparticipantmax'];
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
			$day   = strftime('%d', $row[1]);
			$month = strftime('%m', $row[1]);
			$year  = strftime('%Y', $row[1]);
			$start_hour  = strftime('%H', $row[1]);
			$start_min   = strftime('%M', $row[1]);
			$duration    = $row[5]-$row[1];
			//$end_day   = strftime('%d', $row[5]);
			//$end_month = strftime('%m', $row[5]);
			//$end_year  = strftime('%Y', $row[5]);
			//$end_hour  = strftime('%H', $row[5]);
			//$end_min   = strftime('%M', $row[5]);
			$rep_end_day   = strftime('%d', $row[2]);
			$rep_end_month = strftime('%m', $row[2]);
			$rep_end_year  = strftime('%Y', $row[2]);
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
			$rep_end_date = utf8_encode(strftime($dformat,$row[2]));
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
	/*$tab_benef["nom"] = "";
	$tab_benef["email"] = "";*/
	$create_by    = $user_name;
	$description = "";
	$start_hour  = $hour;
	$start_min = (isset($minute)) ? $minute : '00';
	if ($enable_periods == 'y')
	{
		$end_day   = $day;
		$end_month = $month;
		$end_year  = $year;
		$end_hour  = $hour;
		$end_min = (isset($minute)) ? $minute : '00';
	}
	else
	{
		$now = mktime($hour, $minute, 0, $month, $day, $year);
		$fin = $now + $duree_par_defaut_reservation_area;
		$end_day   = date("d",$fin);
		$end_month = date("m",$fin);
		$end_year  = date("Y",$fin);
		$end_hour  = date("H",$fin);
		$end_min = date("i",$fin);
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
	$nbparticipantmax = 0;
}
// fin nouvelle réservation
$Err = getFormVar("Err",'string'); // utilité ?
if ($enable_periods == 'y')
	toPeriodString($start_min, $duration, $dur_units);
else{
    $duree_sec = $duration; // durée en secondes
    toTimeString($duration, $dur_units, true); // durée convertie en clair
}
/*if (!getWritable($user_name,$id))
{
    start_page_w_header('','','','with_session');
    showAccessDenied($page_ret);
    exit;
}*/
$nb_areas = 0;
$sql = "SELECT id, area_name FROM ".TABLE_PREFIX."_area";
$res = grr_sql_query($sql);
$allareas_id = array();
if ($res)
{
	//for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
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
	$titre = get_vocab("addentry");
else
{
	if ($edit_type == "series")
		$titre = get_vocab("editseries");
	else
	{
		if (isset($_GET["copier"]))
			$titre = get_vocab("copyentry");
		else
			$titre = get_vocab("editentry");
	}
}
$Booker = get_vocab("namebooker");
if (Settings::get("remplissage_description_breve") != '0') {$Booker .= " *";}
$Booker .= get_vocab("deux_points");
$C = htmlspecialchars($name); // brève description
$D = get_vocab("fulldescription");
if (Settings::get("remplissage_description_complete") == '1') {$D .= " *";}
$D .= get_vocab("deux_points");
$E = htmlspecialchars ( $description );
$date_debut = get_vocab("date").get_vocab("deux_points");
$area_id = mrbsGetAreaIdFromRoomId($room_id);
$moderate = isset($Room['moderate'])? $Room['moderate']: -1;
//echo "<br>domaine : ".$area_id;
//echo "<br>titre : ".$titre;
//die();
// pour le traitement des modules
include_once "./include/hook.class.php";
// début du code html
header('Content-Type: text/html; charset=utf-8');
if (!isset($_COOKIE['open']))
{
	header('Set-Cookie: open=true; SameSite=Lax');
}
echo '<!DOCTYPE html>'.PHP_EOL;
echo '<html lang="'.$locale.'">'.PHP_EOL;
// section <head>
//echo pageHead2(Settings::get("company"),$type_session="with_session");
pageHead(Settings::get("company"),$locale);
// section <body>
echo "<body>";
//echo $C;
//print_r($data);
// Menu du haut = section <header>
echo "<header>";
pageHeader2($day, $month, $year, $type_session="with_session");
echo "</header>";
// Debut de la page
echo '<section>'.PHP_EOL;
//echo 'GET<br>';
//print_r($_GET);
//echo '<br>POST<br>';
//print_r($_POST);
echo '<h2>'.$titre.'</h2>'.PHP_EOL;
//end_page();
//die();
if ($moderate)
	echo '<h3><span class="texte_ress_moderee">'.$vocab["reservations_moderees"].'</span></h3>'.PHP_EOL;
echo '<form id="main" action="edit_entry_handler.php" method="get">'.PHP_EOL;
echo '<input type="hidden" name="oldRessource" value="'.$room_id.'">'.PHP_EOL; // oldRessource utile ?
echo '<div id="error"></div>';

echo '<div class="row2">';
echo '<div class="col-sm-6 col-xs-12">';
// bloc choix du bénéficiaire
//echo '<div id="choix_beneficiaire"></div>';
divBeneficiaire($id,$user_name,$room,$area_id);
// description brève
echo '<div>'.PHP_EOL;
echo '<label for="name">'.$Booker.'</label>'.PHP_EOL;
echo '<input id="name" class="form-control" name="name" maxlength="80" size="60" value="'.$C.'" />'.PHP_EOL;
echo '</div>'.PHP_EOL;
// description complète
echo '<div>'.PHP_EOL;
echo '<label for="description">'.$D.'</label>'.PHP_EOL;
echo '<textarea name="description" class="form-control" rows="4">'.$E.'</textarea>'.PHP_EOL;
echo '</div>'.PHP_EOL;
// date et heure de début
echo '<div class="E form-inline"><b>'.$date_debut.'</b>'.PHP_EOL;
echo '<div class="form-group">'.PHP_EOL;
jQuery_DatePicker('start');

if ($enable_periods == 'y')
{
	echo '<label for="period">'.get_vocab("period").'</label>'.PHP_EOL;
	echo '<select name="period">'.PHP_EOL;
	foreach ($periods_name as $p_num => $p_val)
	{
		echo '<option value="'.$p_num.'"';
		if ((isset( $period ) && $period == $p_num ) || $p_num == $start_min)
			echo ' selected="selected"';
		echo '>'.$p_val.'</option>'.PHP_EOL;
	}
	echo '</select>'.PHP_EOL;
}
else
{
	if (isset($id) && ($id != 0))
	{
		jQuery_TimePicker2('start_', $start_hour, $start_min,$duree_sec,$resolution,$morningstarts,$eveningends,$eveningends_minutes,$twentyfourhour_format);
	}
	else
	{
		jQuery_TimePicker2('start_', '', '',$duree_par_defaut_reservation_area,$resolution,$morningstarts,$eveningends,$eveningends_minutes,$twentyfourhour_format);
	}
}
echo '</div></div>'.PHP_EOL; // fin début
if ($type_affichage_reser == 0) // sélection de la durée
{
	echo '<div class="E form-inline">'.PHP_EOL;
    echo '<label for="duration">'.get_vocab("duration").'</label>'.PHP_EOL;
	echo '<input class="form-control" id="duree" name="duration" type="number" value="'.$duration.'" min="1">'; 
    echo '<select class="form-control" name="dur_units">'.PHP_EOL;
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
    foreach($units as $unit)
	{
		echo '<option value="'.$unit.'"';
		if ($dur_units ==  get_vocab($unit))
			echo ' selected="selected"';
		echo '>'.get_vocab($unit).'</option>'.PHP_EOL;
	}
	echo '</select>'.PHP_EOL;
    echo "</div>";

	// l'heure de fin du jour est définie par eveningends et eveningends_minutes
	// on suppose les données vérifiées : eveningends:eveningends_minutes <= 24:00
    $af_fin_hr = substr("0".$eveningends,-2,2);
    $af_fin_min = substr("0".$eveningends_minutes,-2,2);
    if ($af_fin_min =='60'){
        $af_fin_hr++;
        $af_fin_min = '00';
    }
	$af_fin_jour = $af_fin_hr." H ".$af_fin_min;
	echo '<b>
          <input name="all_day" type="checkbox" value="yes" />'.get_vocab("all_day");
	if ($enable_periods != 'y')
		echo ' ('.$morningstarts.' H - '.$af_fin_jour.')';
        echo '</b>'.PHP_EOL;
}
else // sélection de l'heure ou du créneau de fin
{
	echo '<div class="E form-inline"><b>'.get_vocab("fin_reservation").get_vocab("deux_points").'</b>'.PHP_EOL;
	
	echo '<div class="form-group">'.PHP_EOL;
	jQuery_DatePicker('end');

	if ($enable_periods=='y')
	{
		echo "<b>".get_vocab("period")."</b>".PHP_EOL;
	    echo "<select name=\"end_period\" class='form-control'>";
		foreach ($periods_name as $p_num => $p_val)
		{
			echo "<option value=\"".$p_num."\"";
			if ( ( isset( $end_period ) && $end_period == $p_num ) || ($p_num+1) == $end_min)
				echo " selected=\"selected\"";
			echo ">$p_val</option>\n";
		}
		echo '</select>'.PHP_EOL;
	}
	else
	{
		if (isset($id) && ($id != 0))
		{
			jQuery_TimePicker2('end_', $end_hour, $end_min,$duree_sec,$resolution,$morningstarts,$eveningends,$eveningends_minutes,$twentyfourhour_format);
		}
		else
		{
			jQuery_TimePicker2('end_', '', '',$duree_par_defaut_reservation_area,$resolution,$morningstarts,$eveningends,$eveningends_minutes,$twentyfourhour_format);
		}
	}
	echo '</div></div>'.PHP_EOL; // fin heure de fin
}
// domaines et ressources
echo "<div ";
if ($nb_areas == 1)
	echo "style=\"display:none\" ";
echo "class=\"E form-inline\">";
echo "<label for='areas' class='control-label'>".get_vocab("match_area").get_vocab("deux_points")."</label>".PHP_EOL;
echo "<select class=\"form-control\" id=\"areas\" name=\"areas\" onchange=\"changeRooms(this.form);\" >";
if ($enable_periods == 'y')
	$sql = "SELECT id, area_name FROM ".TABLE_PREFIX."_area WHERE id='".$area."' ORDER BY area_name";
else
	$sql = "SELECT id, area_name FROM ".TABLE_PREFIX."_area WHERE enable_periods != 'y' ORDER BY order_display,area_name";
$res = grr_sql_query($sql);
if ($res)
{
    foreach($res as $row)
	{
		if (authUserAccesArea($user_name,$row['id']) == 1)
		{
			$selected = "";
			if ($row['id'] == $area)
				$selected = 'selected="selected"';
			echo '<option '.$selected.' value="'.$row['id'].'">'.$row['area_name'].'</option>'.PHP_EOL;
		}
	}
}
grr_sql_free($res);
echo '</select>',PHP_EOL,'</div>',PHP_EOL; // fin domaines

echo '<!-- ************* Ressources edition ***************** -->',PHP_EOL;
echo "<div class=\"E form-inline\"><label for='rooms[]' class='control-label'>".get_vocab("rooms").get_vocab("deux_points")."</label>".PHP_EOL;
$tab_rooms_noaccess = no_book_rooms($user_name);
$sql = "SELECT id, room_name, description FROM ".TABLE_PREFIX."_room WHERE area_id=$area_id ";
foreach ($tab_rooms_noaccess as $key)
{
	$sql .= " and id != $key ";
}
$sql .= " ORDER BY order_display,room_name";
$res = grr_sql_query($sql);
$len = grr_sql_count($res);
//sélection des ressources (rooms[]) dans le domaine (area)
//echo "<select class='form-control' name=\"rooms[]\" size=\"".min($longueur_liste_ressources_max,$len)."\" multiple=\"multiple\" onchange=\"changeRoom(this.form) ;\">";
echo "<select class='form-control' name=\"rooms[]\" size=\"".min($longueur_liste_ressources_max,$len)."\" multiple=\"multiple\" >";
if ($res)
{
	//for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
    foreach($res as $row)
	{
		$selected = "";
		if ($row['id'] == $room_id)
			$selected = 'selected="selected"';
		echo '<option ',$selected,' value="',$row['id'],'" title="',$row['description'],'">',$row['room_name'],'</option>',PHP_EOL;
	}
}
grr_sql_free($res);
echo '</select>',PHP_EOL,'&nbsp; &nbsp;',get_vocab("ctrl_click"),'</div>',PHP_EOL;

// réservation conditionnelle
if (($delais_option_reservation > 0) && (($modif_option_reservation == 'y') || ((($modif_option_reservation == 'n') && ($option_reservation != -1)))))
{
	$day   = date("d");
	$month = date("m");
	$year  = date("Y");
	echo '<div class="E"><br><div class="col-xs-12"><div class="alert alert-danger" role="alert"><b>'.get_vocab("reservation_a_confirmer_au_plus_tard_le").'</div>'.PHP_EOL;
	if ($modif_option_reservation == 'y')
	{
		echo '<select class="form-control" name="option_reservation" size="1">'.PHP_EOL;
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
		echo "<option value = \"-1\">".get_vocab("Reservation confirmee")."</option>\n";
		if (($selected == 'n') and ($option_reservation != -1))
		{
			echo "<option value = \"".$option_reservation."\" selected=\"selected\">".time_date_string_jma($option_reservation, $dformat)."</option>\n";
		}
		echo $aff_options;
		echo "</select>";
	}
	else
	{
		echo "<input type=\"hidden\" name=\"option_reservation\" value=\"".$option_reservation."\" /> <b>".
		time_date_string_jma($option_reservation,$dformat)."</b>\n";
		echo "<br /><input type=\"checkbox\" name=\"confirm_reservation\" value=\"y\" />".get_vocab("confirmer reservation")."\n";
	}
	echo '<br /><div class="alert alert-danger" role="alert">'.get_vocab("avertissement_reservation_a_confirmer").'</b></div>'.PHP_EOL;
	echo "</div></div>\n";
}

// types
echo '<div id="div_types">',PHP_EOL;
echo '</div>',PHP_EOL;
// champs additionnels
echo '<div id="div_champs_add">'.PHP_EOL;
echo '</div>'.PHP_EOL;
// participants
if($active_participant > 0){
	echo '<div class="E">'.PHP_EOL;
	echo '<label for="nbparticipantmax">'.get_vocab("nb_participant_max").get_vocab("deux_points").'</label>'.PHP_EOL;
	echo '<input name="nbparticipantmax" type="number" value="'.$nbparticipantmax.'" > '.get_vocab("nb_participant_zero");
	echo '</div>'.PHP_EOL;
}
else{
    echo '<input name="nbparticipantmax" type="hidden" value=0 />';
}
// clé
if($active_cle == 'y'){
	echo '<div class="E">'.PHP_EOL;
	echo '<label for="keys">'.get_vocab("status_clef").get_vocab("deux_points").PHP_EOL;
	echo '</label>'.PHP_EOL;
	echo '<input name="keys" type="checkbox" value="y" ';
	if (isset($clef) && $clef == 1)
		echo 'checked';
	echo ' > '.get_vocab("msg_clef");
	echo '</div>'.PHP_EOL;
}
// courrier
if (Settings::get("show_courrier") == 'y'){ // proposition scoubinaire le 12/03/2018
    echo '<div class="E">'.PHP_EOL;
    echo '<label for="courrier">'.get_vocab("status_courrier").get_vocab("deux_points").PHP_EOL;
    echo '</label>'.PHP_EOL;
    echo '<input name="courrier" type="checkbox" value="y" ';
    if (isset($courrier) && $courrier == 1)
        echo 'checked';
    echo ' > '.get_vocab("msg_courrier");
    echo '</div>'.PHP_EOL;
}

echo '<div class="bg-info">',PHP_EOL;
echo '<p><b>'.get_vocab("required").'</b></p></div>'.PHP_EOL;
echo "</div>",PHP_EOL; 
// fin du bloc de "gauche"

echo "<div class='col-sm-6 col-xs-12 form-inline'>";
//$sql = "SELECT id FROM ".TABLE_PREFIX."_area;";
//$res = grr_sql_query($sql);
echo '<!-- ************* Periodic edition ***************** -->',PHP_EOL;
$weeklist = array("unused","every week","week 1/2","week 1/3","week 1/4","week 1/5");
$monthlist = array("firstofmonth","secondofmonth","thirdofmonth","fouthofmonth","fiveofmonth","lastofmonth");
if($periodiciteConfig == 'y')
{
	if ( ($edit_type == "series") || (isset($flag_periodicite)))
	{
        echo '<div id="ouvrir" class="CC">',PHP_EOL,
                '<input type="button" class="btn btn-primary" value="',get_vocab("click_here_for_series_open"),'" onclick="clicMenu(1);check_5();" />',PHP_EOL,
			'</div>',PHP_EOL,
			'<div style="display:none" id="fermer" class="CC">',PHP_EOL,
					'<input type="button" class="btn btn-primary" value="',get_vocab("click_here_for_series_close"),'" onclick="clicMenu(1);check_5();" />',PHP_EOL,
			'</div>',PHP_EOL;
		echo '<div id="menu1" style="display:none;">',PHP_EOL; // choix de la périodicité
        echo '<p class="F"><b>',get_vocab("rep_type"),'</b></p>',PHP_EOL;
        for ($i = 0; $i < 8 ; $i++)
		{
            if ($i == 6 && Settings::get("jours_cycles_actif") == "Non")
                continue;
            elseif ($i != 5)
            {
                echo PHP_EOL,'<div><label><input name="rep_type" type="radio" value="',$i,'"';
                if (($i == $rep_type) || (($i == 3) && ($rep_type == 5)))
					echo ' checked="checked"';
                echo ' onclick="check_1()" />',PHP_EOL; // fin input
                if (($i != 2) && ($i != 3))
					echo get_vocab("rep_type_$i").'</label>'.PHP_EOL;
                if ($i == '2') // semaine
				{
					echo '&nbsp;</label><select class="form-control" name="rep_num_weeks" size="1" onfocus="check_2()" onclick="check_2()">',PHP_EOL;
					echo '<option value="1" >',get_vocab("every week"),'</option>',PHP_EOL;
					for ($weekit = 2; $weekit < 6; $weekit++)
					{
						echo '<option value="',$weekit,'"';
						if ($rep_num_weeks == $weekit)
							echo ' selected="selected"';
						echo '>',get_vocab($weeklist[$weekit]),'</option>',PHP_EOL;
					}
					echo '</select>',PHP_EOL;
                    echo "<div style=\"display:none\" id=\"menu2\" width=\"100%\">\n"; // jour(s) de la semaine
                    echo "<div class='F'><b>".get_vocab("rep_rep_day")."</b></div>\n";
                    echo "<div class=\"CL\">";
                    for ($d = 0; $d < 7; $d++)
                    {
                        $wday = ($d + $weekstarts) % 7;
                        echo "<input name=\"rep_day[$wday]\" type=\"checkbox\"";
                        if ($rep_day[$wday])
                            echo " checked=\"checked\"";
                        echo " onclick=\"check_1()\" />" . day_name($wday) . "\n";
                    }
                    echo "</div>\n</div>\n";
				}
                if ($i == '3') // mensuel
				{
					$monthrep3 = ($rep_type == 3)? " selected=\"selected\" ": "";
					$monthrep5 = ($rep_type == 5)? " selected=\"selected\" ": "";
					echo '&nbsp;</label><select class="form-control" name="rep_month" size="1" onfocus="check_3()" onclick="check_3()">'.PHP_EOL;
					echo "<option value=\"3\" $monthrep3>".get_vocab("rep_type_3")."</option>\n";
					echo "<option value=\"5\" $monthrep5>".get_vocab("rep_type_5")."</option>\n";
					echo "</select>\n";
				}
                if ($i == '7') // X Y du mois
				{
					echo '<select class="form-control" name="rep_month_abs1" size="1" onfocus="check_7()" onclick="check_7()">'.PHP_EOL;
					for ($weekit = 0; $weekit < 6; $weekit++)
					{
						echo "<option value=\"".$weekit."\"";
						if ($weekit == $rep_month_abs1) echo " selected='selected' ";
						echo ">".get_vocab($monthlist[$weekit])."</option>\n";
					}
					echo '</select>'.PHP_EOL;
					echo '<select class="form-control" name="rep_month_abs2" size="1" onfocus="check_8()" onclick="check_8()">'.PHP_EOL;
					for ($weekit = 1; $weekit < 8; $weekit++)
					{
						echo "<option value=\"".$weekit."\"";
						if ($weekit == $rep_month_abs2) echo " selected='selected' ";
						echo ">".day_name($weekit)."</option>\n";
					}
					echo "</select>\n";
					echo get_vocab("ofmonth");
				}
                if ($i == 6)
                {
                    echo "<div id='menuP'>\n"; // choix des jours cycle
                    echo "<b>Jours/Cycle</b><br />\n";
                    echo "<div class='form-inline'>";
                    for ($d = 1; $d <= (Settings::get("nombre_jours_Jours_Cycles")); $d++)
                    {
                        $wday = $d;
                        echo "<input type=\"radio\" name=\"rep_jour_\" value=\"$wday\"";
                        if (isset($jours_c))
                        {
                            if ($d == $jours_c)
                                echo ' checked="checked"';
                        }
                        echo ' onclick="check_1()" />',get_vocab("rep_type_6"),' ',$wday,PHP_EOL;
                    }
                    echo '</div>',PHP_EOL;
                    echo '<input type="checkbox" name="cycle_cplt" value=1'; // case pour choisir tous les jours du cycle
                    if (isset($cycle_cplt) && ($cycle_cplt == 1))
                        echo ' checked="checked"';
                    echo ' onclick="check_1()" />'.get_vocab('cycle_cplt').PHP_EOL;
                    echo '</div>',PHP_EOL;
                }
                echo '</div>'.PHP_EOL;
            }
        }
		echo "<div class=\"F\"><b>".get_vocab("rep_end_date")."</b>".PHP_EOL;
		jQuery_DatePicker('rep_end');
        echo '</div>'.PHP_EOL;
		echo "</div>\n"; // fin menu1
	}
	else
	{
		echo "<p><b>".get_vocab('periodicite_associe').get_vocab('deux_points')."</b></p>\n";
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
echo "</div> </div>"; // fin colonne de "droite" et du bloc de réservation

echo '<div id="fixe">';
// définit l'adresse de retour, à passer à edit_entry_handler et à cancel
// $ret_page = ($back) ?: $page.".php?year=".$year."&amp;month=".$month."&amp;day=".$day."&amp;area=".$area."&amp;room=".$room; 
// $ret_page = $page.".php?year=".$year."&amp;month=".$month."&amp;day=".$day."&amp;area=".$area."&amp;room=".$room; // robuste ? YN le 20/03/2018
//$ret_page = $page.".php?year=".$year."&amp;month=".$month."&amp;day=".$day."&amp;area=".$area;
/*if ((!strpos($page,"all"))&&($room_back != 'all')){
    $page_ret .= "&amp;room=".$room_back;
}*/
//echo "page retour".$page_ret;
echo '<input type="button" class="btn btn-primary" value="'.get_vocab("cancel").'" onclick=\'window.location.href="'.$page_ret.'"\' />';
echo '<input type="button" class="btn btn-primary" value="'.get_vocab("save").'" onclick="validate_and_submit();" />';// enlevé Save_entry();
echo '<input type="hidden" name="rep_id" value="'.$rep_id.'" />';
echo '<input type="hidden" name="edit_type" value="'.$edit_type.'" />';
echo '<input type="hidden" name="page" value="'.$page.'" />';
echo '<input type="hidden" name="room_back" value="'.$room_back.'" />';
echo '<input type="hidden" name="page_ret" value="'.$page_ret.'" />';
if (!isset($statut_entry) || ($statut_entry == ""))
	$statut_entry = "-";
echo '<input type="hidden" name="statut_entry" value="'.$statut_entry.'" />'.PHP_EOL;
echo '<input type="hidden" name="create_by" value="'.$create_by.'" />'.PHP_EOL;
if (($id!=0)&&(!isset($_GET["copier"])))
    echo '<input type="hidden" name="id" value="'.$id.'" />'.PHP_EOL;
echo '<input type="hidden" name="type_affichage_reser" value="'.$type_affichage_reser.'" />'.PHP_EOL;
echo '</div>'.PHP_EOL;
echo '</form>'.PHP_EOL;
echo '<div id="footer"></div>'.PHP_EOL;
?>

<script type="text/javascript" >
function insertBeneficiaires(area_,room_,user_,id_){
// cette fonction donne la liste des items du sélecteur
    jQuery.ajax({
        type: 'GET',
        url : 'edit_entry_beneficiaires.php',
        data: {
            area: area_,
            room: room_,
            user: user_,
            id  : id_
        },
        success: function(returnData)
        {
            $("#beneficiaire").select2({
                data: returnData,
                dataType: 'json',
            })
        },
        error: function(data)
		{
			alert('Erreur lors de l execution de la commande AJAX pour edit_entry_beneficiaires.php ');
		},
        dataType: 'json',
    })
}
function insertChampsAdd(area_,id_,room_,olf_){
	jQuery.ajax({
		type: 'GET',
		url: 'edit_entry_champs_add.php',
		data: {
			area: area_,
			id: id_,
			room: room_,
            overloadFields: olf_,
		},
		success: function(returnData)
		{
			$("#div_champs_add").html(returnData);
		},
		error: function(data)
		{
			alert('Erreur lors de l execution de la commande AJAX pour le edit_entry_champs_add.php ');
		}
    });
}
function insertTypes(area_,room_){
    jQuery.ajax({
        type: 'GET',
        url: 'edit_entry_types.php',
        data: {
            area: area_,
            type: '<?php echo $etype; ?>',
            room: room_,
        },
        success: function(returnData){
            $('#div_types').html(returnData);
        },
        error: function(data){
            alert('Erreur lors de l execution de la commande AJAX pour le edit_entry_types.php ');
        }
    });
}
function check_1(){
    menu = document.getElementById('menu2');
    if (menu)
    {
        if (!document.forms["main"].rep_type[2].checked)
        {
            document.forms["main"].elements['rep_day[0]'].checked=false;
            document.forms["main"].elements['rep_day[1]'].checked=false;
            document.forms["main"].elements['rep_day[2]'].checked=false;
            document.forms["main"].elements['rep_day[3]'].checked=false;
            document.forms["main"].elements['rep_day[4]'].checked=false;
            document.forms["main"].elements['rep_day[5]'].checked=false;
            document.forms["main"].elements['rep_day[6]'].checked=false;
            menu.style.display = "none";
        }
        else
        {
            menu.style.display = "";
        }
    }
    <?php
    if (Settings::get("jours_cycles_actif") == "Oui") {
        ?>
        menu = document.getElementById('menuP');
        if (menu)
        {
            if (!document.forms["main"].rep_type[5].checked)
            {
                menu.style.display = "none";
            }
            else
            {
                menu.style.display = "";
            }
        }
        <?php
    }
    ?>
}
function check_2(){
    document.forms["main"].rep_type[2].checked=true;
    check_1 ();
}
function check_3(){
    document.forms["main"].rep_type[3].checked=true;
}
function check_4(){
    menu = document.getElementById('menu4');
    if (menu)
    {
        if (!document.forms["main"].beneficiaire.options[0].selected)
        //if (document.forms["main"].beneficiaire.value != '')
        {
            menu.style.display = "none";
            <?php
            if (Settings::get("remplissage_description_breve") == '2')
            {
                ?>
               document.forms["main"].name.value=document.forms["main"].beneficiaire.options[document.forms["main"].beneficiaire.options.selectedIndex].text;
              // document.forms["main"].name.value=document.forms["main"].beneficiaire.value;
                <?php
            }
            ?>
        }
        else
        {
            menu.style.display = "block";
            <?php
            if (Settings::get("remplissage_description_breve") == '2')
            {
                ?>
                document.forms["main"].name.value=document.forms["main"].benef_ext_nom.value;
                <?php
            }
            ?>
        }
    }
}
function check_5 (){
    var menu; var menup; var menu2;
    menu = document.getElementById('menu1');
    menup = document.getElementById('menuP');
    menu2 = document.getElementById('menu2');
    if ((menu)&&(menu.style.display == "none"))
    {
        menup.style.display = "none";
        menu2.style.display = "none";
    }
    else
        check_1();
}
function setdefault (name,input){
    document.cookie = escape(name) + "=" + escape(input) +
    ( "" ? ";expires=" + ( new Date( ( new Date() ).getTime() + ( 1000 * lifeTime ) ) ).toGMTString() : "" ) +
    ( "" ? ";path=" + path : "") +
    ( "" ? ";domain=" + domain : "") +
    ( "" ? ";secure" : "") + 
    "; SameSite=Lax";
}
function validate_and_submit (){
    var err;
    $("#error").html("");
    if (document.forms["main"].benef_ext_nom)
    {
        if ((document.forms["main"].beneficiaire.options[0].selected) &&(document.forms["main"].benef_ext_nom.value == ""))
        //if ((document.forms["main"].beneficiaire.value == "") &&(document.forms["main"].benef_ext_nom.value == ""))
        {
            $("#error").append('<div class="alert alert-danger alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button><?php echo get_vocab("you_have_not_entered").get_vocab("deux_points").lcfirst(get_vocab("nom beneficiaire")) ?></div>');
            err = 1;
        }
    }
    <?php if (Settings::get("remplissage_description_breve") == '1' || Settings::get("remplissage_description_breve") == '2')
    {
        ?>
        if (document.forms["main"].name.value == "")
        {
            $("#error").append('<div class="alert alert-danger alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button><?php echo get_vocab("you_have_not_entered").get_vocab("deux_points").get_vocab("brief_description") ?></div>');
            err = 1;
        }
        <?php
    }
     if (Settings::get("remplissage_description_complete") == '1')
    {
        ?>
        if (document.forms["main"].description.value == "")
        {
            $("#error").append('<div class="alert alert-danger alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button><?php echo get_vocab("you_have_not_entered").get_vocab("deux_points").get_vocab("fulldescription") ?></div>');
            err = 1;
        }
        <?php
    }
    foreach ($allareas_id as $idtmp)
    {
        $overload_fields = mrbsOverloadGetFieldslist($idtmp);
        foreach ($overload_fields as $fieldname=>$fieldtype)
        {
            if ($overload_fields[$fieldname]["obligatoire"] == 'y')
            {
                if ($overload_fields[$fieldname]["type"] != "list")
                {
                    echo "if ((document.getElementById('id_".$idtmp."_".$overload_fields[$fieldname]["id"]."')) && (document.forms[\"main\"].addon_".$overload_fields[$fieldname]["id"].".value == \"\")) {\n";
                }
                else
                {
                    echo "if ((document.getElementById('id_".$idtmp."_".$overload_fields[$fieldname]["id"]."')) && (document.forms[\"main\"].addon_".$overload_fields[$fieldname]["id"].".options[0].selected == true)) {\n";
                }
                ?>
					$("#error").append("<div class=\"alert alert-danger alert-dismissible\" role=\"alert\"><button type=\"button\" class=\"close\" data-dismiss=\"alert\"><span aria-hidden=\"true\">&times;</span><span class=\"sr-only\">Close</span></button><?php echo get_vocab('required'); ?></div>");
					err = 1;
				}
				<?php
			}
			if ($overload_fields[$fieldname]["type"] == "numeric")
			{
            ?>
				if (isNaN((document.getElementById('id_".$idtmp."_".$overload_fields[$fieldname]["id"]."')) && (document.forms['main'].addon_<?php echo $overload_fields[$fieldname]['id']?>.value))) 
                {
					$("#error").append('<div class="alert alert-danger alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button><?php echo addslashes($overload_fields[$fieldname]["name"]).get_vocab("deux_points"). get_vocab("is_not_numeric") ?></div>');
					err = 1;
				}
                <?php
            }
        }
    }
?>
    if  (document.forms["main"].type.value=='0')
    {
        $("#error").append('<div class="alert alert-danger alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button><?php echo get_vocab("choose_a_type"); ?></div>');
        err = 1;
    }
    <?php
    if (($edit_type == "series") && ($periodiciteConfig == 'y'))
    {
        ?>
        i1 = parseInt(document.forms["main"].id.value);
        i2 = parseInt(document.forms["main"].rep_id.value);
        n = parseInt(document.forms["main"].rep_num_weeks.value);
        if ((document.forms["main"].elements['rep_day[0]'].checked || document.forms["main"].elements['rep_day[1]'].checked || document.forms["main"].elements['rep_day[2]'].checked || document.forms["main"].elements['rep_day[3]'].checked || document.forms["main"].elements['rep_day[4]'].checked || document.forms["main"].elements['rep_day[5]'].checked || document.forms["main"].elements['rep_day[6]'].checked) && (!document.forms["main"].rep_type[2].checked))
        {
            $("#error").append('<div class="alert alert-danger alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button><?php echo get_vocab("no_compatibility_with_repeat_type"); ?></div>');
            err = 1;
        }
        if ((!document.forms["main"].elements['rep_day[0]'].checked && !document.forms["main"].elements['rep_day[1]'].checked && !document.forms["main"].elements['rep_day[2]'].checked && !document.forms["main"].elements['rep_day[3]'].checked && !document.forms["main"].elements['rep_day[4]'].checked && !document.forms["main"].elements['rep_day[5]'].checked && !document.forms["main"].elements['rep_day[6]'].checked) && (document.forms["main"].rep_type[2].checked))
        {
            $("#error").append('<div class="alert alert-danger alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button><?php echo get_vocab("choose_a_day"); ?></div>');
            err = 1;
        }
        <?php
    }
    ?>
    if (err == 1)
        return false;
    document.forms["main"].submit();
    return true;
}
function changeRooms( formObj )
{
    areasObj = eval( "formObj.areas" );
    area = areasObj[areasObj.selectedIndex].value
    roomsObj = eval( "formObj.elements['rooms[]']" )
    l = roomsObj.length;
    for (i = l; i > 0; i-- )
    {
        roomsObj.options[i] = null
    }
    switch (area)
    {
        <?php
        if ($enable_periods == 'y')
            $sql = "SELECT id, area_name FROM ".TABLE_PREFIX."_area WHERE id='".$area."' ORDER BY area_name";
        else
            $sql = "SELECT id, area_name FROM ".TABLE_PREFIX."_area WHERE enable_periods != 'y' ORDER BY area_name";
        $res = grr_sql_query($sql);
        if ($res)
        {
            $ids = [];
            for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
            {
                if (authUserAccesArea(getUserName(), $row[0]) == 1)
                {
                    $ids[] = $row[0];
                }
            }
            // modification proposée par Eric Marie (Github)
            $sql2 = "SELECT area_id, id, room_name FROM ".TABLE_PREFIX."_room WHERE area_id IN ('" . implode("', '", $ids) . "')";
            $tab_rooms_noaccess = verif_acces_ressource($user_name, 'all');
            foreach($tab_rooms_noaccess as $key)
            {
                $sql2 .= " AND id != $key ";
            }
            $sql2 .= " ORDER BY area_id, room_name";
            
            $res2 = grr_sql_query($sql2);
            $results = [];
            if ($res2)
            {
                for ($j = 0; ($row2 = grr_sql_row($res2, $j)); $j++)
                {
                    $results[$row2[0]][] = [$row2[1], $row2[2]];
                }
                foreach($results as $areaId => $rows2) {
                    print "      case \"".$areaId."\":\n";
                    print "roomsObj.size=" . min($longueur_liste_ressources_max, count($rows2)) . ";\n";
                    $i = 0;
                    foreach($rows2 as $row2) {
                        print "roomsObj.options[$i] = new Option(\"".str_replace('"','\\"',$row2[1])."\",".$row2[0] .")\n";
                        $i++;
                    }
                    print "roomsObj.options[0].selected = true\n";
                    print "break\n";
                }
            }
            grr_sql_free($res2);
        }
        grr_sql_free($res);
        ?>
    }
    roomsObj = eval( "formObj.elements['rooms[]']" );
    room = roomsObj[roomsObj.selectedIndex].value;
    insertBeneficiaires(area,room,<?php echo json_encode($user_name).','.$id;?>);
    insertChampsAdd(area,<?php echo $id;?>,room);
    insertTypes(area,room);
}
function changeRoom(formObj)
{	
    areasObj = eval( "formObj.areas" );
    area = areasObj[areasObj.selectedIndex].value
    roomsObj = eval("formObj.elements['rooms[]']");
    room = roomsObj[roomsObj.selectedIndex].value;
    insertBeneficiaires(area,room,<?php echo json_encode($user_name).','.$id;?>);
    insertChampsAdd(area,<?php echo $id;?>,room,<?php echo json_encode($overloadFields);?>);
    insertTypes(area,room);
    $(".select2").select2();
}
</script>

	<script type="text/javascript">
		$('#areas').on('change', function(){
			$('.multiselect').multiselect('destroy');
			$('.multiselect').multiselect();
		});
		$(document).ready(function() {
            insertBeneficiaires(<?php echo $area?>,<?php echo $room?>,<?php echo json_encode($user_name);?>,<?php echo $id?>);
            insertChampsAdd(<?php echo $area?>,<?php echo $id ?>,<?php echo $room?>,<?php echo json_encode($overloadFields);?>);
            insertTypes(<?php echo $area?>,<?php echo $room?>);
            //check_4();
		});
		document.getElementById('main').name.focus();
		<?php
		//if (isset($cookie) && $cookie)
		//	echo "check_4();";
		if (($id <> "") && (!isset($flag_periodicite)))
			echo "clicMenu('1'); check_5();\n";
		//if (isset($Err) && $Err == "yes")
		//	echo "timeoutID = window.setTimeout(\"Load_entry();check_5();\",500);\n";
		?>
	</script>
</section>
</body>
</html>