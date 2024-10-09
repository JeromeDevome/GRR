<?php
/**
 * edit_area.php
 * Interface de creation/modification des domaines de l'application GRR
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2024-10-09 11:12$
 * @author    Laurent Delineau & JeromeB & Marc-Henri PAMISEU & Yan Naessens & Daniel Antelme
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
$grr_script_name = "edit_area.php";

include "../include/admin.inc.php";

$ok = NULL;
$id_site = (int)getFormVar("id_site","int",-1);
$action = getFormVar("action","string");
$retour_page = clean_input(getFormVar("retour_page","string"));
$id_area = (int)getFormVar("id_area","int",-1);
$area_name = clean_input(getFormVar("area_name","string"));
$area_order = (int)getFormVar("area_order","int",0);
$access = getFormVar("access","string");
$ip_adr = clean_input(getFormVar("ip_adr","string"));
$enable_periods = clean_input(getFormVar("enable_periods","string","n"));
$number_periodes = (int)getFormVar("number_periodes","int");
$description = clean_input(getFormVar("description","string"));
$duree_max_resa_area1  = (int)getFormVar("duree_max_resa_area1","int");
$duree_max_resa_area2  = (int)getFormVar("duree_max_resa_area2","int");
$morningstarts_area = (int)getFormVar("morningstarts_area","int");
$eveningends_area = (int)getFormVar("eveningends_area","int");
$eveningends_minutes_area = (int)getFormVar("eveningends_minutes_area","int");
$resolution_area = (int)getFormVar("resolution_area","int");
$duree_par_defaut_reservation_area = (int)getFormVar("duree_par_defaut_reservation_area","int");
$twentyfourhour_format_area = (int)getFormVar("twentyfourhour_format_area","int",1);
$weekstarts_area = (int)getFormVar("weekstarts_area","int",1); // lundi par défaut
$max_booking = (int)getFormVar("max_booking","int",-1);
if ($max_booking<-1)
  $max_booking = -1;
$access_file = isset($_POST['access_file'])? 1:0;
$user_right = (int)getFormVar("user_right","int");
$upload_file = (int)getFormVar("upload_file","int");
$change_area = isset($_POST["change_area"]) ? $_POST["change_area"] : NULL;
$change_done = isset($_POST["change_done"]) ? $_POST["change_done"] : NULL;


$back = (isset($_SERVER['HTTP_REFERER']))? htmlspecialchars_decode($_SERVER['HTTP_REFERER'], ENT_QUOTES): "./admin_accueil.php";

if (isset($_POST["change_area_and_back"]))
{
  $change_area = "yes";
  $change_done = "yes";
}
// memorisation du chemin de retour
if (!isset($retour_page))
{
  $retour_page = $back;
  if(!strstr($retour_page,"login.php")){
    // on nettoie la chaine :
    $long_chaine_a_supprimer = strlen(strstr($retour_page, "&amp;msg=")); // longueur de la chaine à partir de la premiere occurrence de &amp;msg=
    if ($long_chaine_a_supprimer == 0)
      $long_chaine_a_supprimer = strlen(strstr($retour_page, "?msg="));
    $long = strlen($retour_page) - $long_chaine_a_supprimer;
    $retour_page = substr($retour_page, 0, $long);
  }
  else
    $retour_page = "./admin_accueil.php";
}
// modification d'un domaine : administrateur général ou du site
$user_id = getUserName();
if (authGetUserLevel($user_id,-1) < 6)
{
  if (isset($id_area))
    {
      // On verifie que le domaine $id_area existe
      $test = grr_sql_query1("SELECT id FROM ".TABLE_PREFIX."_area WHERE id=? ","i",[$id_area]);
      if ($test == -1)// ajout : $user est-il administrateur du site ?
      {
        if(($id_site == -1)||(authGetUserLevel($user_id,$id_site,'site')<5)){
          showAccessDenied($back);
          exit();
        }
      }
      // Il s'agit de la modif d'un domaine
      if ((authGetUserLevel($user_id, $id_area, 'area') < 4))
      {
        showAccessDenied($back);
        exit();
      }
    }
}
$msg ='';

$Area = array();
$Periodes = array();
if($id_area != -1){ // données du domaine (modification ou duplication)
  $res = grr_sql_query("SELECT * FROM ".TABLE_PREFIX."_area WHERE id=? ","i",[$id_area]);
  if (!$res)
    fatal_error(0, get_vocab('error_area') . $id_area . get_vocab('not_found'));
  $Area = grr_sql_row_keyed($res, 0);
  grr_sql_free($res);
  if ((Settings::get("module_multisite") == "Oui")&&($id_site == -1))
    $id_site=grr_sql_query1("select id_site from ".TABLE_PREFIX."_j_site_area where id_area=? ","i",[$id_area]);
  if($Area['enable_periods'] == "y") //Les creneaux de reservation sont bases sur des intitules pre-definis.
  {
    $resper = grr_sql_query("SELECT num_periode, nom_periode FROM ".TABLE_PREFIX."_area_periodes where id_area=? order by num_periode","i",[$id_area]);
    if(!$resper)
      fatal_error(0,grr_sql_error());
    else{
      $num_periodes = grr_sql_count($resper);
      if ($number_periodes < 1)
        if ($num_periodes == 0)
          $number_periodes = 10;
        else
          $number_periodes = $num_periodes;
      foreach($resper as $per){
        $Periodes[$per['num_periode']] = $per['nom_periode'];
      }
    }
    grr_sql_free($resper);
  }
}
else // ajout d'un domaine
{
  $Area = array(
      'id' => '',
      'area_name' => '',
      'access' => 'a',
      'order_display' => 0,
      'ip_adr' => '',
      'morningstarts_area' => 8,
      'eveningends_area' => 19,
      'duree_max_resa_area' => -1,
      'resolution_area' => 1800,
      'eveningends_minutes_area' => 0,
      'weekstarts_area' => 1,
      'twentyfourhour_format_area' => 1,
      'calendar_default_values' => 'n',
      'enable_periods' => 'n',
      'display_days' => 'yyyyyyy',
      'id_type_par_defaut' => -1,
      'duree_par_defaut_reservation_area' => 1800,
      'max_booking' => -1,
      'user_right' => '',
      'access_file' => '',
      'upload_file' => '');
}
// vérification des paramètres
if (isset($change_area))
{
// Affectation à un site
  if ((Settings::get("module_multisite") == "Oui") && ($id_site == -1)) // si aucun site n'a été affecté
  { // On affiche un message d'avertissement
    ?>
    <script type="text/javascript">
      alert("<?php echo get_vocab('choose_a_site'); ?>");
    </script>
    <?php
      // On empeche le retour à la page admin_room
    unset($change_done);
  }
  else
  { // Un site a ete affecte, on peut continuer
    // la valeur par defaut ne peut etre inférieure au plus petit bloc reservable
    if ($duree_par_defaut_reservation_area < $resolution_area)
      $duree_par_defaut_reservation_area = $resolution_area;
    // la valeur par defaut doit etre un multiple du plus petit bloc reservable
    $duree_par_defaut_reservation_area = intval($duree_par_defaut_reservation_area / $resolution_area) * $resolution_area;
    // Duree maximale de reservation
    if ($enable_periods == 'y')
      $duree_max_resa_area = $duree_max_resa_area2 * 1440;
    else
    {
      $duree_max_resa_area = $duree_max_resa_area1;
      if ($duree_max_resa_area >= 0)
        $duree_max_resa_area = max ($duree_max_resa_area, $resolution_area / 60, $duree_par_defaut_reservation_area / 60);
    }
    $duree_max_resa_area = intval($duree_max_resa_area);
    if ($duree_max_resa_area < 0)
      $duree_max_resa_area = -1;
    // jours affichés
    $display_days = "";
    for ($i = 0; $i < 7; $i++)
    {
      if (isset($_POST['display_day'][$i]))
        $display_days .= "y";
      else
        $display_days .= "n";
    }
    if ($display_days != "nnnnnnn")
    {
      while (!isset($_POST['display_day'][$_POST['weekstarts_area']]))
        $_POST['weekstarts_area']++;
    }
    // horaires
    if ($morningstarts_area > $eveningends_area)
      $eveningends_area = $morningstarts_area + $resolution_area; // au moins un créneau !
    // domaine restreint ?
    if ($access)
      $access='r';
    else
      $access='a';
    if (($id_area != -1) && ($action != "dupliquer")) // modification d'un domaine
    {
      // s'il y a changement de type de creneaux, on efface les reservations du domaines
      $old_enable_periods = grr_sql_query1("select enable_periods from ".TABLE_PREFIX."_area WHERE id=? ","i",[$id_area]);
      if ($old_enable_periods != $enable_periods)
      {
        $del = grr_sql_command("DELETE ".TABLE_PREFIX."_entry FROM ".TABLE_PREFIX."_entry, ".TABLE_PREFIX."_room, ".TABLE_PREFIX."_area WHERE
            ".TABLE_PREFIX."_entry.room_id = ".TABLE_PREFIX."_room.id and
            ".TABLE_PREFIX."_room.area_id = ".TABLE_PREFIX."_area.id and
            ".TABLE_PREFIX."_area.id =? ","i",[$id_area]);
        $del = grr_sql_command("DELETE ".TABLE_PREFIX."_repeat FROM ".TABLE_PREFIX."_repeat, ".TABLE_PREFIX."_room, ".TABLE_PREFIX."_area WHERE
            ".TABLE_PREFIX."_repeat.room_id = ".TABLE_PREFIX."_room.id and
            ".TABLE_PREFIX."_room.area_id = ".TABLE_PREFIX."_area.id and
            ".TABLE_PREFIX."_area.id =? ","i",[$id_area]);
      }
      $sql = "UPDATE ".TABLE_PREFIX."_area SET
        area_name='".protect_data_sql($area_name)."',
        access='".protect_data_sql($access)."',
        order_display='".protect_data_sql($area_order)."',
        ip_adr='".protect_data_sql($ip_adr)."',
        calendar_default_values = 'n',
        duree_max_resa_area = '".protect_data_sql($duree_max_resa_area)."',
        morningstarts_area = '".protect_data_sql($morningstarts_area)."',
        eveningends_area = '".protect_data_sql($eveningends_area)."',
        resolution_area = '".protect_data_sql($resolution_area)."',
        duree_par_defaut_reservation_area = '".protect_data_sql($duree_par_defaut_reservation_area)."',
        eveningends_minutes_area = '".protect_data_sql($eveningends_minutes_area)."',
        weekstarts_area = '".protect_data_sql($weekstarts_area)."',
        enable_periods = '".protect_data_sql($enable_periods)."',
        twentyfourhour_format_area = '".protect_data_sql($twentyfourhour_format_area)."',
        max_booking='".protect_data_sql($max_booking)."',
        display_days = '".$display_days."',
				user_right = '".$user_right."',
				access_file = ".$access_file.",
				upload_file = ".$upload_file."
        WHERE id=?";
      if (grr_sql_command($sql,"i",[$id_area]) < 0)
      {
        fatal_error(0, get_vocab('update_area_failed') . grr_sql_error());
        $ok = 'no';
      }
    }
    else // ajout ou duplication d'un domaine
    { 
      $sql = "INSERT INTO ".TABLE_PREFIX."_area SET
      area_name='".protect_data_sql($area_name)."',
      access='".protect_data_sql($access)."',
      order_display='".protect_data_sql($area_order)."',
      ip_adr='".protect_data_sql($ip_adr)."',
      calendar_default_values = 'n',
      duree_max_resa_area = '".protect_data_sql($duree_max_resa_area)."',
      morningstarts_area = '".protect_data_sql($morningstarts_area)."',
      eveningends_area = '".protect_data_sql($eveningends_area)."',
      resolution_area = '".protect_data_sql($resolution_area)."',
      duree_par_defaut_reservation_area = '".protect_data_sql($duree_par_defaut_reservation_area)."',
      eveningends_minutes_area = '".protect_data_sql($eveningends_minutes_area)."',
      weekstarts_area = '".protect_data_sql($weekstarts_area)."',
      enable_periods = '".protect_data_sql($enable_periods)."',
      twentyfourhour_format_area = '".protect_data_sql($twentyfourhour_format_area)."',
      display_days = '".$display_days."',
      max_booking='".protect_data_sql($max_booking)."',
      id_type_par_defaut = '-1',
      user_right = '".$user_right."',
      access_file = ".$access_file.",
      upload_file = ".$upload_file."
      ";
      if (grr_sql_command($sql) < 0)
        fatal_error(1, "<p>" . grr_sql_error());
      $id_area = grr_sql_insert_id();
    }
    // Affectation à un site
    if (Settings::get("module_multisite") == "Oui")
    {
      $sql = "delete from ".TABLE_PREFIX."_j_site_area where id_area=? ";
      if (grr_sql_command($sql,"i",[$id_area]) < 0)
        fatal_error(0, "<p>".grr_sql_error()."</p>");
      $sql = "INSERT INTO ".TABLE_PREFIX."_j_site_area SET id_site=? , id_area=? ";
      if (grr_sql_command($sql,"ii",[$id_site,$id_area]) < 0)
        fatal_error(0, "<p>".grr_sql_error()."</p>");
    }
    #Si area_name est vide on le change maintenant que l'on a l'id area
    if ($area_name == '')
    {
      $area_name = get_vocab("match_area")." ".$id_area;
      grr_sql_command("UPDATE ".TABLE_PREFIX."_area SET area_name=? WHERE id=? ","si",[protect_data_sql($area_name),$id_area]);
    }
    #on cree ou recree ".TABLE_PREFIX."_area_periodes pour le domaine
    if ($enable_periods == 'y')
    {
      if ($number_periodes)
      {
        if ($number_periodes < 1)
          $number_periodes = 1;
        $del_periode = grr_sql_command("delete from ".TABLE_PREFIX."_area_periodes where id_area=? ","i",[$id_area]);
        #on efface le modele par defaut avec area=0
        $del_periode = grr_sql_command("delete from ".TABLE_PREFIX."_area_periodes where id_area='0'");
        $i = 0;
        $num = 0;
        while ($i < $number_periodes)
        {
          $temp = "periode_".$i;
          if (isset($_POST[$temp]))
          {
            $nom_periode = corriger_caracteres($_POST[$temp]);
            $reg_periode = grr_sql_command("insert into ".TABLE_PREFIX."_area_periodes set id_area=?, num_periode=?, nom_periode=? ","iis",[$id_area,$num,protect_data_sql($nom_periode)]);
            #on cree un modele par defaut avec area=0
            $reg_periode = grr_sql_command("insert into ".TABLE_PREFIX."_area_periodes set id_area=?, num_periode=?, nom_periode=? ","iis",[0,$num,protect_data_sql($nom_periode)]);
            $num++;
          }
          $i++;
        }
      }
    }
    // accès restreint ?
    if ($access=='a')
    {
      $sql = "DELETE FROM ".TABLE_PREFIX."_j_user_area WHERE id_area=? ";
      if (grr_sql_command($sql,"i",[$id_area]) < 0)
        fatal_error(0, get_vocab('update_area_failed') . grr_sql_error());
    }
    $msg = get_vocab("message_records");
  }
}
if ((isset($change_done)) && (!isset($ok)))
{
  if ($msg != '') {
    $_SESSION['displ_msg'] = 'yes';
    if (strpos($retour_page, ".php?") == "")
      $param = "?msg=".$msg;
    else
      $param = "&msg=".$msg;
  } else
  $param = '';
  Header("Location: ".$retour_page.$param);
  exit();
}

// titre
if($id_area == -1)// Ajout
  $titre_action = get_vocab("addarea");
else{
  if($action == "dupliquer")
    $titre_action = get_vocab("duplique_domaine");
  else
    $titre_action = get_vocab("editarea");
}
// liste deroulante des sites administrables
$Sites = array();
if (authGetUserLevel($user_id, -1, 'area') >= 6){
  $sql = "SELECT id,sitecode,sitename FROM ".TABLE_PREFIX."_site ORDER BY sitename ASC";
  $res = grr_sql_query($sql);
}
else{
  $sql = "SELECT id,sitecode,sitename
          FROM ".TABLE_PREFIX."_site s JOIN ".TABLE_PREFIX."_j_useradmin_site u ON s.id=u.id_site
          WHERE u.login=? 
          ORDER BY s.sitename ASC";
  $res = grr_sql_query($sql,"s",[$user_id]);
}
if(!$res)
  fatal_error(0,grr_sql_error());
else{
  foreach($res as $row){
    $Sites[] = $row;
  }
  $nb_site = grr_sql_count($res);
}

$avertissement = get_vocab("avertissement_change_type");

# print the page header
start_page_w_header("", "", "", $type="with_session");
//print_r($Area);
/*print_r($_POST);
echo "<br/>";
print_r($_GET);
echo $id_site;
print_r($Sites);*/
affiche_pop_up($msg,"admin");
include "admin_col_gauche2.php";
echo "<div class=\"col-md-9 col-sm-8 col-xs-12\">";
echo "<h2>".$titre_action."</h2>";
echo '<form action="edit_area.php" method="post" id="main">';
if (isset($action))
  echo "<input type=\"hidden\" name=\"action\" value=\"".$action."\" />\n";
if (isset($retour_page))
  echo "<input type=\"hidden\" name=\"retour_page\" value=\"".$retour_page."\" />";
if ($Area['id'] != '')
  echo "<input type=\"hidden\" name=\"id_area\" value=\"".clean_input($Area["id"])."\" />";

echo "<table class='table table-bordered'>";
// Nom du domaine
echo "<tr><td>".get_vocab("name").get_vocab("deux_points")."</td>\n";
echo "<td><input class=\"form-control\" type=\"text\" name=\"area_name\" maxlength=\"30\" size=\"40\" value=\"".clean_input($Area["area_name"])."\" /></td></tr>\n";
// Ordre d'affichage du domaine
echo "<tr><td>".get_vocab("order_display").get_vocab("deux_points")."</td>\n";
echo "<td><input class=\"form-control\" type=\"text\" name=\"area_order\" size=\"1\" value=\"".clean_input($Area["order_display"])."\" /></td></tr>\n";
// Acces restreint ou non ?
echo "<tr><td>".get_vocab("access").get_vocab("deux_points")."</td>\n";
echo "<td><input type=\"checkbox\" name=\"access\"";
if ($Area["access"] == 'r')
  echo " checked ";
echo " /></td></tr>\n";
// Site
if (Settings::get("module_multisite") == "Oui")
{
  echo "<tr><td>".get_vocab('site').get_vocab('deux_points')."</td>\n";
  if ($nb_site >= 1)
  {
    echo "<td><select class=\"form-control\" name=\"id_site\" >\n
    <option value=\"-1\">".get_vocab('choose_a_site')."</option>\n";
    foreach($Sites as $site)
    {
      echo "<option value=\"".$site['id']."\"";
      if ($id_site == $site['id'])
        echo ' selected ';
      echo '>'.htmlspecialchars($site['sitename'], ENT_QUOTES);
      echo '</option>'."\n";
    }
    echo "</select></td></tr>";
  }
}
// Adresse IP client :
if (OPTION_IP_ADR == 1)
{
  echo "<tr><td>".get_vocab("ip_adr").get_vocab("deux_points")."</td>";
  echo "<td><input class=\"form-control\" type=\"text\" name=\"ip_adr\" value=\"".clean_input($Area["ip_adr"])."\" /></td></tr>\n";
  echo "<tr><td colspan=\"2\">".get_vocab("ip_adr_explain")."</td></tr>\n";
}
echo "</table>";

// Configuration des jours affichés ...
echo "<h3>".get_vocab("configuration_plages_horaires")."</h3>";
// Debut de la semaine: 0 pour dimanche, 1 pour lundi, etc.
echo "<table class='table table-bordered'>";
echo "<tr><td>".get_vocab("weekstarts_area").get_vocab("deux_points")."</td>\n";
echo "<td><select class=\"form-control\" name=\"weekstarts_area\" size=\"1\">\n";
$k = 0;
while ($k < 7)
{
  $tmp=mktime(0, 0, 0, 10, 2 + $k, 2005);
  echo "<option value=\"".$k."\" ";
  if ($k == $Area['weekstarts_area'])
    echo " selected=\"selected\"";
  echo ">".utf8_strftime("%A", $tmp)."</option>\n";
  $k++;
}
echo "</select></td></tr>\n";
// Definition des jours de la semaine à afficher sur les plannings et calendriers
echo "<tr><td>".get_vocab("cocher_jours_a_afficher")."</td>\n";
echo "<td>\n";
for ($i = 0; $i < 7; $i++)
{
  echo "<label><input name=\"display_day[".$i."]\" type=\"checkbox\"";
  if (substr($Area["display_days"], $i, 1) == 'y')
    echo " checked ";
  echo " />" . day_name($i) . "</label><br />\n";
}
echo "</td></tr></table>\n";

// Configuration plages horaires ou créneaux prédéfinis
echo "<h3>".get_vocab("type_de_creneaux")."</h3>";
echo "<div class='left'>";
echo "<p class=\"bg-warning\">".get_vocab("avertissement_change_type")."</p>";
echo "<label><input type=\"radio\" name=\"enable_periods\" value=\"n\" onclick=\"bascule()\" ";
if ($Area["enable_periods"] == 'n')
  echo " checked ";
echo " />".get_vocab("creneaux_de_reservation_temps")."</label><br />";
echo "<label><input type=\"radio\" name=\"enable_periods\" value=\"y\" onclick=\"bascule()\" ";
if ($Area["enable_periods"] == 'y')
  echo " checked ";
echo " />".get_vocab("creneaux_de_reservation_pre_definis")."</label></div>";
//Les creneaux de reservation sont bases sur des intitules pre-definis.
if ($Area["enable_periods"] == 'y')
  echo "<table id=\"menu2\" class=\"table table-bordered\">";
else
  echo "<table style=\"display:none\" id=\"menu2\" class='table table-bordered'>";
echo "<tr><td>".get_vocab("nombre_de_creneaux").get_vocab("deux_points")."</td>";
echo "<td><input type=\"text\" id=\"nb_per\" name=\"number_periodes\" size=\"1\" onkeypress=\"if (event.keyCode==13) return aff_creneaux()\" value=\"$number_periodes\" /><a href=\"#Per\" onclick=\"javascript:return(aff_creneaux())\">".get_vocab("goto")."</a>\n";
echo "</td></tr>\n";
echo "<tr><td colspan=\"2\">";
$i = 0;
while ($i < 50)
{
  $nom_periode = isset($Periodes[$i])? $Periodes[$i] : "";
  echo "<table style=\"display:none\" id=\"c".($i+1)."\"><tr><td>".get_vocab("intitule_creneau").($i+1).get_vocab("deux_points")."</td>";
  echo "<td style=\"width:30%;\"><input type=\"text\" name=\"periode_".$i."\" value=\"".htmlentities($nom_periode)."\" size=\"20\" /></td></tr></table>\n";
  $i++;
}
// L'utilisateur ne peut reserver qu'une duree limitee (-1 desactivee), exprimee en jours
if ($Area["duree_max_resa_area"] > 0)
  $nb_jour = max(round($Area["duree_max_resa_area"]/1440,0),1);
else
  $nb_jour = -1;
echo "</td></tr>\n<tr><td>".get_vocab("duree_max_resa_area2").get_vocab("deux_points")."</td>\n";
echo "<td><input class=\"form-control\" type=\"text\" name=\"duree_max_resa_area2\" size=\"5\" value=\"".$nb_jour."\" /></td></tr>\n";
// Nombre max de reservation par domaine
echo "<tr><td>".get_vocab("max_booking")." -  ".get_vocab("all_rooms_of_area").get_vocab("deux_points")."</td>\n";
echo "<td><input class=\"form-control\" type=\"text\" name=\"max_booking\" value=\"".clean_input($Area['max_booking'])."\" /></td></tr>\n";
echo "</table>";
// Cas ou les creneaux de reservations sont bases sur le temps
if ($Area["enable_periods"] == 'n')
  echo "<table id=\"menu1\" class='table table-bordered'>";
else
  echo "<table style=\"display:none\" id=\"menu1\" class='table table-bordered'>";
// Heure de debut de reservation
echo "<tr><td>".get_vocab("morningstarts_area").get_vocab("deux_points")."</td>\n";
echo "<td><select class=\"form-control\" name=\"morningstarts_area\" size=\"1\">\n";
$k = 0;
while ($k < 24)
{
  echo "<option value=\"".$k."\" ";
  if ($k == $Area['morningstarts_area']) echo " selected ";
  echo ">".$k."</option>\n";
  $k++;
}
echo "</select></td></tr>\n";
// Heure de fin de reservation
echo "<tr><td>".get_vocab("eveningends_area").get_vocab("deux_points")."</td>\n";
echo "<td><select class=\"form-control\" name=\"eveningends_area\" size=\"1\">\n";
$k = 0;
while ($k < 24)
{
  echo "<option value=\"".$k."\" ";
  if ($k == $Area['eveningends_area']) echo " selected ";
  echo ">".$k."</option>\n";
  $k++;
}
echo "</select></td></tr>\n";
// Minutes à ajouter à l'heure $eveningends pour avoir la fin réelle d'une journée.
echo "<tr><td>".get_vocab("eveningends_minutes_area").get_vocab("deux_points")."</td>\n";
echo "<td><input class=\"form-control\" type=\"text\" name=\"eveningends_minutes_area\" size=\"5\" value=\"".clean_input($Area["eveningends_minutes_area"])."\" /></td></tr>\n";
// Resolution - quel bloc peut etre reserve, en secondes
echo "<tr><td>".get_vocab("resolution_area").get_vocab("deux_points")."</td>\n";
echo "<td><input class=\"form-control\" type=\"text\" name=\"resolution_area\" size=\"5\" value=\"".clean_input($Area["resolution_area"])."\" /></td></tr>\n";
// Valeur par defaut de la duree d'une reservation
echo "<tr><td>".get_vocab("duree_par_defaut_reservation_area").get_vocab("deux_points")."</td>\n";
echo "<td><input class=\"form-control\" type=\"text\" name=\"duree_par_defaut_reservation_area\" size=\"5\" value=\"".clean_input($Area["duree_par_defaut_reservation_area"])."\" /></td></tr>\n";
// Format d'affichage du temps : valeur 0 pour un affichage sur 12 heures et valeur 1 pour un affichage sur 24 heures.
echo "<tr><td>".get_vocab("twentyfourhour_format_area").get_vocab("deux_points")."</td>\n";
echo "<td><label><input type=\"radio\" name=\"twentyfourhour_format_area\" value=\"0\" ";
if ($Area['twentyfourhour_format_area'] == 0)
  echo " checked ";
echo " />".get_vocab("twentyfourhour_format_12")."</label>\n<br />";
echo "<label><input type=\"radio\" name=\"twentyfourhour_format_area\" value=\"1\" ";
if ($Area['twentyfourhour_format_area'] == 1)
  echo " checked ";
echo " />".get_vocab("twentyfourhour_format_24")."</label>\n";
echo "</td></tr>\n";
// L'utilisateur ne peut reserver qu'une duree limitee (-1 desactivee), exprimee en minutes
echo "<tr><td>".get_vocab("duree_max_resa_area").get_vocab("deux_points")."</td>\n";
echo "<td><input class=\"form-control\" type=\"text\" name=\"duree_max_resa_area1\" size=\"5\" value=\"".clean_input($Area["duree_max_resa_area"])."\" /></td></tr>\n";
// Nombre max de reservation par domaine
echo "<tr><td>".get_vocab("max_booking")." -  ".get_vocab("all_rooms_of_area").get_vocab("deux_points")."</td>\n";
echo "<td><input class=\"form-control\" type=\"text\" name=\"max_booking\" value=\"".clean_input($Area['max_booking'])."\" /></td></tr>\n";
echo "</table>";
// Activer la fonctionnalité "fichier joint"
echo "<h3>".get_vocab('ajout_fichier_joint')."</h3>";
echo "<table class='table table-bordered'>";
echo "<tr><td>".get_vocab("ajout_fichier_joint_explain")."</td>\n";
echo "<td><input type=\"checkbox\" name=\"access_file\" value='1' ";
if ($Area["access_file"] == 1)
  echo " checked ";
echo "</td></tr>\n";
echo "<tr><td>Droit pour consulter les fichiers</td>\n";
echo "<td>";
echo "<select class=\"form-control\" name='user_right'>";
echo "<option value='1' ";
if($Area['user_right'] == 1)
  echo " selected ";
echo " />".get_vocab("visu_fiche_description1")."</option>";
echo "<option value='2' ";
if($Area['user_right'] == 2)
  echo " selected ";
echo " />".get_vocab("visu_fiche_description2")."</option>";
echo "<option value='3' ";
if($Area['user_right'] == 3)
  echo " selected ";
echo " />".get_vocab("visu_fiche_description3")."</option>";
echo "<option value='4' ";
if($Area['user_right'] == 4)
  echo " selected ";
echo " />".get_vocab("visu_fiche_description4")."</option>";
echo "<option value='5' ";
if($Area['user_right'] == 5)
  echo " selected ";
echo " />".get_vocab("visu_fiche_description5")."</option>";
echo "<option value='6' ";
if($Area['user_right'] == 6)
  echo " selected ";
echo " />".get_vocab("visu_fiche_description6")."</option>";
echo "</select>";
echo "</td></tr>\n";
echo "<tr><td>Droit pour téléverser les fichiers</td>\n";
echo "<td >";
echo "<select class=\"form-control\" name='upload_file'>";
echo "<option value='1' ";
if($Area['upload_file'] == 1)
  echo " selected ";
echo " />".get_vocab("visu_fiche_description1")."</option>";
echo "<option value='2' ";
if($Area['upload_file'] == 2)
  echo " selected ";
echo " />".get_vocab("visu_fiche_description2")."</option>";
echo "<option value='3' ";
if($Area['upload_file'] == 3)
  echo " selected ";
echo " />".get_vocab("visu_fiche_description3")."</option>";
echo "<option value='4' ";
if($Area['upload_file'] == 4)
  echo " selected ";
echo " />".get_vocab("visu_fiche_description4")."</option>";
echo "<option value='5' ";
if($Area['upload_file'] == 5)
  echo " selected ";
echo " />".get_vocab("visu_fiche_description5")."</option>";
echo "<option value='6' ";
if($Area['upload_file'] == 6)
  echo " selected ";
echo " />".get_vocab("visu_fiche_description6")."</option>";
echo "</select>";
echo "</td></tr>\n";
echo "</table>";

Hook::Appel("hookEditArea1");
echo "<div class=\"center\">\n";
echo "<input class=\"btn btn-primary\" type=\"submit\" name=\"change_area\" value=\"".get_vocab("save")."\" />\n";
echo "<input class=\"btn btn-primary\" type=\"submit\" name=\"change_done\" value=\"".get_vocab("back")."\" />\n";
echo "<input class=\"btn btn-primary\" type=\"submit\" name=\"change_area_and_back\" value=\"".get_vocab("save_and_back")."\" />";
echo "</div>\n";
echo "</form>";
echo "</div>";
echo "</section></body>"
?>
<script type="text/javascript">
  function bascule()
  {
    menu_1 = document.getElementById('menu1');
    menu_2 = document.getElementById('menu2');
    if (document.getElementById('main').enable_periods[0].checked)
    {
      menu_1.style.display = "";
      menu_2.style.display = "none";
    }
    if (document.getElementById('main').enable_periods[1].checked)
    {
      menu_1.style.display = "none";
      menu_2.style.display = "";
    }
    alert("<?php echo $avertissement; ?>");
  }

  function aff_creneaux()
  {
    nb_cr = document.getElementById('nb_per');
    if (isNaN(Number(nb_cr.value)))
      nb_cr.value = 1;
    if (nb_cr.value > 50)
      nb_cr.value = 50;
    if (nb_cr.value < 1)
      nb_cr.value = 1;
    for (var i = 1; i <= nb_cr.value; i++)
    {
      document.getElementById('c' + i).style.display = '';
    }
    for (var i; i <= 50; i++)
    {
      document.getElementById('c' + i).style.display = 'none';
    }
    return false;
  }
</script>
</html>