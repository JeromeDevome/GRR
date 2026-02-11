<?php
/**
 * editentree.php
 * script préparant le formulaire qui sera affiché par editentree.twig
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2026-01-24 11:20$
 * @author    Laurent Delineau & JeromeB & Yan Naessens & Daniel Antelme
 * @author    Eric Lemeur pour les champs additionnels de type checkbox
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
$grr_script_name = "editentree.php"; 

$trad = $vocab;

// les variables attendues et leur type
$form_vars = array(
  'Err'                => 'string',
  'envoyer_notif'      => 'string',
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
  'rep_day'            => 'array',   // tableau lacunaire indiquant "on" aux jours sélectionnés
  'rep_opt'            => 'string',
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
        if (($var_type == "string")&&($$var !== NULL)){
          $$var = trim($$var);}
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
// vérification
/*
echo "<br>vérification<br>";
foreach($form_vars as $var => $var_type)
{
    //echo $var.' -> ';print_r($$var);
    echo $var." -> "; var_dump($$var);
    echo '<br/>';
}
echo "<br>Champs additionnels<br>";
print_r($overloadFields);
*/
/* URL de retour. À faire avant l'ouverture de session.
 En effet, nous pourrions passer par editentree plus d'une fois, par exemple si nous devons nous reconnecter par timeout. 
 Nous devons toujours conserver la page d'appel d'origine afin qu'une fois que nous avons quitté editentreetrt, nous puissions revenir à la page d'appel (plutôt que d'aller à la vue par défaut). 
 Si c'est la première fois, alors $_SERVER['HTTP_REFERER'] contient l'appelant d'origine. Si c'est la deuxième fois, nous l'aurons stocké dans $page_ret.*/
if (!isset($page_ret) || ($page_ret == ''))
{
    $referer = isset($_SERVER['HTTP_REFERER']) ? htmlspecialchars($_SERVER['HTTP_REFERER']) : '';
    $page_ret = (!is_null($referer))? $referer : page_accueil();

    if (isset($_GET['p']) && 
        ((strpos($_GET['p'],'editentree') !== FALSE)||
         (strpos($_GET['p'],'vuereservation') !== FALSE)))
    {
        if (isset($page) && isset($month) && isset($day) && isset($year)){
            $queryRet = [
                'p'     => $page,
                'month' => $month,
                'day'   => $day,
                'year'  => $year,
            ];
            if (isset($room))
                $queryRet[ 'room' ] = $room;
            elseif ((!strpos($page,"all"))&&($room_back != 'all'))
                $queryRet[ 'room' ] = $room_back;
            elseif (isset($area))
                $queryRet[ 'area' ] = $area;
            elseif (($room_back !='')&&($room_back != 'all'))
                $queryRet[ 'area' ] = mrbsGetRoomArea($room_back);
            elseif (($room_back == 'all')&&(isset($id))){
                $area = grr_sql_query1("SELECT area_id FROM ".TABLE_PREFIX."_room r JOIN ".TABLE_PREFIX."_entry e ON r.id = e.room_id WHERE e.id=".$id."");
                if ($area != -1)
                    $queryRet[ 'area' ] = $area;
            }
            $page_ret = 'app.php?'. http_build_query( $queryRet );
        }
    }
}

// Resume session
if (!grr_resumeSession())
{
  header("Location: ./app.php?p=deconnexion&auto=1&url=$url"); // $url sort de session.inc.php, appelé par app.php
  die();
}

$user_name = getUserName(); // ici on devrait avoir un identifiant

// traitement des données entrées
if (isset($period))
  $end_period = $period;
if (!isset($edit_type))
  $edit_type = '';

$flag_periodicite = 'y'; // utilisé pour le non-affichage de la seconde colonne pour une réservation non périodique
$page = verif_page();

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
    if (!isset($room)){ 
        $room_back = 'all';
        $room_id = grr_sql_query1("SELECT min(id) FROM ".TABLE_PREFIX."_room WHERE area_id='".$area."' ORDER BY order_display,room_name");
        $room = $room_id; // à voir
    }
}

// l'utilisateur est-il autorisé à être ici ?
if (((authGetUserLevel($user_name,-1) < 2) && (auth_visiteur($user_name,$room) == 0))||(authUserAccesArea($user_name, $area) == 0))
{
  $d['messageErreur'] = showAccessDenied_twig($page_ret);
  echo $twig->render('erreur.twig', array('trad' => $trad, 'd' => $d, 'settings' => $AllSettings));
  exit();
}
if (isset($room) && ($room != -1)){// on vérifie que la ressource n'est pas restreinte ou que l'accès est autorisé
    $who_can_book = grr_sql_query1("SELECT who_can_book FROM ".TABLE_PREFIX."_room WHERE id='".$room."' ");
    if (!($who_can_book || (authBooking($user_name,$room)) || (authGetUserLevel($user_name,$room) > 2))){
        $d['messageErreur'] = showAccessDenied_twig($page_ret."&alerte=acces");
    echo $twig->render('erreur.twig', array('trad' => $trad, 'd' => $d, 'settings' => $AllSettings));
        exit();
    }
}
// récupérons les paramètres du domaine en cours
get_planning_area_values($area);
$d['enable_periods'] = $enable_periods;

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
$qui_peut_reserver_pour = isset($Room['qui_peut_reserver_pour'])? $Room['qui_peut_reserver_pour']: 5;
$d['active_cle'] = isset($Room['active_cle'])? $Room['active_cle']: -1;
$d['active_ressource_empruntee'] = isset($Room['active_ressource_empruntee'])? $Room['active_ressource_empruntee']:"y";
$periodiciteConfig = Settings::get("periodicite");
$longueur_liste_ressources_max = Settings::get("longueur_liste_ressources_max");
if ($longueur_liste_ressources_max == '')
  $longueur_liste_ressources_max = 20;
// horaires
// depuis un planning
$hour = getFormVar('hour','int'); 
$minute = getFormVar('minute','int');
$start_hour = $hour;
$start_min = $minute;
if ($hour < 10) $hour = "0".$hour;
if ($minute < 10) $minute = "0".$minute;
// édition (modification, copie, retour d'erreur depuis editentreetrt.php) start_ et end_ devraient être définis
// voir ici s'il ne vaudrait pas mieux passer par start_time
if (isset($start_)){
  $debut = array();
  $debut = explode(':', $start_);
  $start_hour = $debut[0];
  $start_min = isset($debut[1])? $debut[1]:'00';
  $pos = strpos($start_min," ");
  if ($pos !== false){
    $debmin = explode(' ',$start_min);
    $start_min = $debmin[0];
    if ($debmin[1] == "pm"){$start_hour += 12;}
  }
}
// et par end_time ?
if (isset($end_)){
  $debut = array();
  $debut = explode(':', $end_);
  $end_hour = $debut[0];
  $end_min = isset($debut[1])? $debut[1]:'00';
  $pos = strpos($end_min," ");
  if ($pos !== false){
    $debmin = explode(' ',$end_min);
    $end_min = $debmin[0];
    if ($debmin[1] == "pm"){$end_hour += 12;}
  }
}

//var_dump($hour);
//print_r($d);
global $twentyfourhour_format;
if (!isset($day) || !isset($month) || !isset($year))
{
  $day   = (isset($start_day))? $start_day : date("d");
  $month = (isset($start_month))? $start_month : date("m");
  $year  = (isset($start_year))? $start_year : date("Y");
}

// le jour est-il ouvert à la réservation ?
if (check_begin_end_bookings($day, $month, $year))
{
   $d['messageErreur'] = showNoBookings_twig($day, $month, $year, $page_ret);

  echo $twig->render('erreur.twig', array('trad' => $trad, 'd' => $d, 'settings' => $AllSettings));
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
    $d['messageErreur'] = "<br> user : ".$user_name." room: ".$room." compt : ".$compt;
  $d['messageErreur'] .= showAccessDeniedMaxBookings_twig($day, $month, $year, $room, $page_ret);
  echo $twig->render('erreur.twig', array('trad' => $trad, 'd' => $d, 'settings' => $AllSettings));
  exit();
}

//Participants
$active_participant = isset($Room['active_participant'])? $Room['active_participant']: 0;
if($active_participant > 0)
  if (authGetUserLevel($user_name,$room) >= $Room['active_participant'])
    $d['active_participant'] = 1;

$d['etype'] = 0;
if (isset($id) && $id !=0) // édition d'une réservation existante
{
    if (!getWritable($user_name,$id) && ($copier == ''))
    {
        $d['messageErreur'] = showAccessDenied_twig($page_ret);
        echo $twig->render('erreur.twig', array('trad' => $trad, 'd' => $d, 'settings' => $AllSettings));
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
  $start_day = date('d', $row['start_time']);
  $start_month = date('m', $row['start_time']);
  $start_year = date('Y', $row['start_time']);
  $start_hour = date('H', $row['start_time']);
  $start_min = date('i', $row['start_time']);
  $end_day = date('d', $row['end_time']);
  $end_month = date('m', $row['end_time']);
  $end_year = date('Y', $row['end_time']);
  $end_hour = date('H', $row['end_time']);
  $end_min  = date('i', $row['end_time']);
  $duration = $row['end_time']-$row['start_time'];
  $d['etype'] = $row['type'];
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
    if (($edit_type == "series")||($copier == 'copier'))
    {
      $rep_start_day = date('d', $row[1]);
      $rep_start_month = date('m', $row[1]);
      $rep_start_year = date('Y', $row[1]);
      $rep_start_hour  = date('H', $row[1]);
      $rep_start_min   = date('i', $row[1]);
      $duration    = $row[5]-$row[1];
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
      $flag_periodicite = 'n';
      $rep_end_date = utf8_strftime($dformat,$row[2]);
      $rep_opt      = $row[3];
      $start_time = $row[1];
      $end_time = $row[5];
    }
  }
  else
  {
    $rep_id        = 0;
    $rep_type      = 0;
    $rep_end_day   = $start_day;
    $rep_end_month = $start_month;
    $rep_end_year  = $start_year;
    $rep_day       = array(0, 0, 0, 0, 0, 0, 0);
    $rep_jour      = 0;
  }
}
elseif(isset($Err) && $Err == 'y') // traitement d'une erreur sur une nouvelle réservation
{
  $room_id = (isset($room))? $room : -1;
  if(!isset($description))
    $description    = "";
  if ($rep_type==2)
  {
    $rep_day[0] = $rep_opt[0] != '0';
    $rep_day[1] = $rep_opt[1] != '0';
    $rep_day[2] = $rep_opt[2] != '0';
    $rep_day[3] = $rep_opt[3] != '0';
    $rep_day[4] = $rep_opt[4] != '0';
    $rep_day[5] = $rep_opt[5] != '0';
    $rep_day[6] = $rep_opt[6] != '0';
  }
  $d['etype']   = isset($type)? $type : 0;
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
  if(!isset($name))
    if (Settings::get("remplissage_description_breve") == '2')
      $name = $_SESSION['prenom']." ".$_SESSION['nom'];
    else
      $name = "";

  $beneficiaire   = $user_name;
  $create_by      = $user_name;
  if(!isset($description))
    $description    = "";
  $start_hour     = (isset($start_hour))? $start_hour : $hour;
  $start_min      = (isset($start_min))? $start_min : ((isset($minute)) ? $minute : '00');
  if ($enable_periods == 'y')
  {
    $end_day    = $day;
    $end_month  = $month;
    $end_year   = $year;
    $end_hour   = $hour;
    $end_min    = $start_min + 1; // propose au moins un créneau
  }
  else
  {
    $debut      = mktime($start_hour, $start_min, 0, $month, $day, $year);
    $fin        = $debut + $duree_par_defaut_reservation_area;
    $end_day    = date("d",$fin);
    $end_month  = date("m",$fin);
    $end_year   = date("Y",$fin);
    $end_hour   = date("H",$fin);
    $end_min    = date("i",$fin);
  }
  $d['etype']   = isset($type)? $type : 0;
  $type         = "";
  $room_id      = $room;
  $id           = 0;
  $rep_id         = 0;
  $rep_type       = 0;
  $rep_end_day    = $day;
  $rep_end_month  = $month;
  $rep_end_year   = $year;
  $rep_day        = array(0, 0, 0, 0, 0, 0, 0);
  $rep_jour       = 0;
  $option_reservation = -1;
  $modif_option_reservation = 'y';
  $d['nbparticipantmax'] = $Room['nb_participant_defaut'];
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
$d['idresa'] = (isset($id))? $id : 0;


/** éléments à insérer dans le formulaire
 * Partie Benéficiaire
*/

$qui_peut_reserver_pour  = isset($Room['qui_peut_reserver_pour'])? $Room['qui_peut_reserver_pour']: 5;
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
        //for ($i = 0; ($row = grr_sql_row($res, $i)); $i++) {$bnf[$i] = $row;}
        foreach($res as $row){
          $bnf[] = $row;
        }
    }
    //print_r($bnf);
    grr_sql_free($res);
    $option = "";
    if (!isset($benef_ext_nom))
        $option .= '<option value="0">'.get_vocab("personne_exterieure").'</option>'.PHP_EOL;
    else
        $option .= '<option value="0" selected="selected">'.get_vocab("personne_exterieure").'</option>'.PHP_EOL;
    foreach ($bnf as $b){
        $option .= '<option value="'.$b['login'].'" ';
        if (((!$benef && !$benef_ext_nom) && strtolower($user_name) == strtolower($b['login'])) || ($benef && $benef == $b['login']))
            {
                $option .= ' selected="selected" ';
            }
        $option .= '>'.$b['nom'].' '.$b['prenom'].'</option>'.PHP_EOL;
    }
    $test = grr_sql_query1("SELECT login FROM ".TABLE_PREFIX."_utilisateurs WHERE login='".$user_name."'");
    if (($test == -1) && ($user_name != ''))
    {
        $option .= '<option value="-1" selected="selected" >'.get_vocab("utilisateur_inconnu").$user_name.'</option>'.PHP_EOL;
    }
    $d['selectBeneficiare'] = $option;
    $d['selectBeneficiaireExt'] = $benef_ext_nom;
}
// début de la réservation
$d['selectionDateDebut'] = genDateSelectorForm('start_',$day,$month,$year,"");

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
 // echo 'temps  ';var_dump($start_hour);echo '   ';var_dump($start_min);
  $d['jQuery_TimePickerStart'] = jQuery_TimePickerTwig('start_', $start_hour, $start_min);
}

if ($type_affichage_reser == 0) // sélection de la durée
{
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

  if ($enable_periods != 'y')
    {
        $d['morningstarts'] = $morningstarts;
        $d['af_fin_jour'] = $af_fin_jour;
    }
}
else // sélection de l'heure ou du créneau de fin
{
  $d['selectionDateFin'] = genDateSelectorForm('end_',$end_day,$end_month,$end_year,"");

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
    $d['jQuery_TimePickerEnd'] = jQuery_TimePickerTwig('end_', $end_hour, $end_min);
  }
}
// fin heure de fin
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


$d['complementJSchangeRooms'] = "";


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
  $tab_rooms_noaccess = no_book_rooms($user_name);
  foreach($tab_rooms_noaccess as $key)
  {
    $sql2 .= " AND id != $key ";
  }
  $sql2 .= " ORDER BY area_id, room_name";

  $res2 = grr_sql_query($sql2);
  $results = [];
  if ($res2)
  {
    foreach($res2 as $row2)
    {
      $results[$row2['area_id']][] = [$row2['id'], $row2['room_name']];
    }
    foreach($results as $areaId => $rows2) {
      $d['complementJSchangeRooms'] .= "      case \"".$areaId."\":\n";
      $d['complementJSchangeRooms'] .= "roomsObj.size=" . min($longueur_liste_ressources_max, count($rows2)) . ";\n";
      $i = 0;
      foreach($rows2 as $row2) {
        $d['complementJSchangeRooms'] .= "roomsObj.options[$i] = new Option(\"".str_replace('"','\\"',$row2[1])."\",".$row2[0] .")\n";
        $i++;
      }
      $d['complementJSchangeRooms'] .= "roomsObj.options[0].selected = true\n";
      $d['complementJSchangeRooms'] .= "break\n";
    }
  }
  grr_sql_free($res2);
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

$d['rep_type'] = $rep_type;
$d['weekstarts'] = $weekstarts;
$d['rep_month_abs1'] = $rep_month_abs1;
$d['rep_month_abs2'] = $rep_month_abs2;

// Selon 1er jour de la semaine afficher les jours
for ($da = 0; $da < 7; $da++)
{
  $wday = ($da + $weekstarts) % 7;
  $d['day'.$wday] = day_name($wday);
}

if($periodiciteConfig == 'y')
{

  if ( ($edit_type == "series") || ($flag_periodicite == 'y') || ($copier == "copier"))
  { // Formulaire périodicité
        $d['periodiciteAttache'] = 0;
        $d['jQuery_DatePickerRepEnd'] = genDateSelectorForm('rep_end_',$rep_end_day,$rep_end_month,$rep_end_year,"");
  }
  else
  {
        $d['periodiciteAttache'] = 1 ;
    $d['repHTML'] = "";
    //echo "<p><b>".get_vocab('periodicite_associe').get_vocab('deux_points')."</b></p>\n";
    if ($rep_type == 2)
      $affiche_period = get_vocab($weeklist[$rep_num_weeks]);
    else
      $affiche_period = get_vocab('rep_type_'.$rep_type);

    $d['repHTML'] .= '<p><b>'.get_vocab('rep_type').'</b> '.$affiche_period.'</p>'."\n";
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
          $d['repHTML'] .= '<p><b>'.get_vocab('rep_rep_day').'</b> '.$opt.'</p>'."\n";
        else
          $d['repHTML'] .= '<p><b>'.get_vocab('rep_rep_days').'</b> '.$opt.'</p>'."\n";
        if ($enable_periods=='y') list( $start_period, $start_date) =  period_date_string($start_time);
        else $start_date = time_date_string($start_time,$dformat);
        $duration = $end_time - $start_time;
        if ($enable_periods=='y') toPeriodString($start_period, $duration, $dur_units);
        else toTimeString($duration, $dur_units, true);
        $d['repHTML'] .= '<p><b>'.get_vocab("date").get_vocab("deux_points").'</b> '.$start_date.'</p>'."\n";
        $d['repHTML'] .= '<p><b>'.get_vocab("duration").'</b> '.$duration .' '. $dur_units.'</p>'."\n";
        $d['repHTML'] .= '<p><b>'.get_vocab('rep_end_date').'</b> '.$rep_end_date.'</p>'."\n";
    }
  }
}
// fin colonne de "droite" et du bloc de réservation

$d['rep_id'] = $rep_id;
$d['duration'] = $duration;
$d['edit_type'] = $edit_type;
$d['page'] = $page;
$d['room_back'] = $room_back;
$d['page_ret'] = $page_ret;
$d['create_by'] = $create_by;
$d['type_affichage_reser'] = $type_affichage_reser;
$d['rep_day'] = $rep_day;
$d['rep_num_weeks'] = $rep_num_weeks;

if (isset($_GET["copier"]))
  $d['copier'] = 1;

if (isset($Err))
  $d['Err'] = $Err;

if (isset($cookie))
  $d['cookie'] = $cookie;
// echo "tableau d : ";print_r($d);
echo $twig->render('editentree.twig', array('trad' => $trad, 'd' => $d, 'settings' => $AllSettings));
?>
