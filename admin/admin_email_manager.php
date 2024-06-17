<?php
/**
 * admin_email_manager.php
 * Interface de gestion des mails automatiques
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2024-06-17 18:00$
 * @author    Laurent Delineau & JeromeB & Yan Naessens
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
$grr_script_name = "admin_email_manager.php";

include "../include/admin.inc.php";

$id_area = isset($_GET["id_area"]) ? intval($_GET["id_area"]) : NULL;
$room = isset($_GET["room"]) ? intval($_GET["room"]) : NULL;

$back = (isset($_SERVER['HTTP_REFERER']))? htmlspecialchars_decode($_SERVER['HTTP_REFERER'], ENT_QUOTES) : "./admin_accueil.php" ;
check_access(4, $back);
// tableau des ressources auxquelles l'utilisateur n'a pas accès
$user_id = getUserName();
$tab_rooms_noaccess = verif_acces_ressource($user_id, 'all');
$msg="";
if (isset($_POST['mail1']))
{
	if (isset($_POST['send_always_mail_to_creator']))
		$temp = '1';
	else
		$temp = '0';
	if (!Settings::set("send_always_mail_to_creator", $temp))
		$msg.= get_vocab("save_error")." send_always_mail_to_creator !<br />";
}
$reg_admin_login = isset($_GET["reg_admin_login"]) ? clean_input($_GET["reg_admin_login"]) : NULL;
$action = isset($_GET["action"]) ? clean_input($_GET["action"]) : NULL;

if ($reg_admin_login) {
	// On commence par vérifier que l'utilisateur n'est pas déjà présent dans cette liste.
	if ($room !=-1)
	{
		// Ressource
		// On vérifie que la ressource $room existe
		$test = grr_sql_query1("select id from ".TABLE_PREFIX."_room where id=? ","i",[$room]);
		if ($test == -1)
		{
			showAccessDenied($back);
			exit();
		}
		if (in_array($room,$tab_rooms_noaccess))
		{
			showAccessDenied($back);
			exit();
		}
		// La ressource existe : on vérifie les privilèges de l'utilisateur
		$sql = "SELECT * FROM ".TABLE_PREFIX."_j_mailuser_room WHERE (login =? and id_room =? )";
		$res = grr_sql_query($sql,"si",[$reg_admin_login,$room]);
    if(!$res)
      fatal_error(0,grr_sql_error());
    else{
      $test = grr_sql_count($res);
      if ($test != "0")
			$msg = get_vocab("warning_exist");
      else
      {
        if ($reg_admin_login != '')
        {
          $sql = "INSERT INTO ".TABLE_PREFIX."_j_mailuser_room SET login=? , id_room =? ";
          if (grr_sql_command($sql,"si",[$reg_admin_login,$room]) < 0)
            fatal_error(1, "<p>" . grr_sql_error());
          else
            $msg = get_vocab("add_user_succeed");
        }
      }
    }
	}
}
if ($action)
{
	if ($action == "del_admin")
	{
		if (authGetUserLevel($user_id,$room) < 4)
		{
			showAccessDenied($back);
			exit();
		}
		$sql = "DELETE FROM ".TABLE_PREFIX."_j_mailuser_room WHERE (login=? and id_room =? )";
		if (grr_sql_command($sql,"si",[clean_input($_GET['login_admin']),$room]) < 0)
			fatal_error(1, "<p>" . grr_sql_error());
		else
			$msg = get_vocab("del_user_succeed");
	}
}
if (empty($room))
	$room = -1;
// données pour le formulaire
$sql = "select id, area_name from ".TABLE_PREFIX."_area order by area_name";
$res = grr_sql_query($sql);
if(!$res)
  fatal_error(0,grr_sql_error());
else{
  $all_areas = array();
  foreach($res as $row){
    if (authGetUserLevel($user_id, $row['id'], 'area') >= 4)
      $all_areas[$row['id']] = $row['area_name'];
  }
}
grr_sql_free($res);
if($id_area > 0){
  $this_area_name = grr_sql_query1("select area_name from ".TABLE_PREFIX."_area where id=$id_area");
  $this_room_name = grr_sql_query1("select room_name from ".TABLE_PREFIX."_room where id=$room");
  $this_room_name_des = grr_sql_query1("select description from ".TABLE_PREFIX."_room where id=$room");
  $sql = "select id, room_name, description from ".TABLE_PREFIX."_room where area_id=$id_area ";
    // on ne cherche pas parmi les ressources invisibles pour l'utilisateur
  foreach ($tab_rooms_noaccess as $key)
  {
    $sql .= " and id != $key ";
  };
  $sql .= "order by room_name";
  $res = grr_sql_query($sql);
  if (!$res)
    fatal_error(0,grr_sql_error());
  else{
    $all_rooms = array();
    foreach($res as $row){
      if ($row['description'])
        $temp = " (".htmlspecialchars($row['description']).")";
      else
        $temp="";
      $all_rooms[$row['id']] = htmlspecialchars($row['room_name'].$temp);
    }
  }
  grr_sql_free($res);
}
$sql = "SELECT u.login, u.nom, u.prenom FROM ".TABLE_PREFIX."_utilisateurs u JOIN ".TABLE_PREFIX."_j_mailuser_room j ON u.login=j.login WHERE j.id_room=? ORDER BY u.nom, u.prenom";
$res = grr_sql_query($sql,"i",[$room]);
if(!$res)
  fatal_error(0,grr_sql_error());
else{
  $nombre = grr_sql_count($res);
  if($nombre != 0){
    $mailed_users = array();
    foreach($res as $row){
      $mailed_users[$row['login']] = htmlspecialchars($row['nom']).' '.htmlspecialchars($row['prenom']);
    }
  }
    
}
grr_sql_free($res);
$sql = "SELECT DISTINCT login, nom, prenom FROM ".TABLE_PREFIX."_utilisateurs WHERE (etat!='inactif' and email!='' and statut!='visiteur' and login NOT IN(SELECT login FROM ".TABLE_PREFIX."_j_mailuser_room WHERE id_room=? )) ORDER BY nom, prenom";
$res = grr_sql_query($sql,"i",[$room]);
if (!$res)
  fatal_error(0,grr_sql_error());
else{
  $available_users = array();
  foreach($res as $row){
    if (authUserAccesArea($row['login'], $id_area) == 1)
      $available_users[$row['login']] = htmlspecialchars($row['nom']).' '.htmlspecialchars($row['prenom']);
  }
}
grr_sql_free($res);
// code HTML
# print the page header
start_page_w_header("", "", "", $type="with_session");
// Affichage de la colonne de gauche
include "admin_col_gauche2.php";
// colonne de droite
echo "<div class='col-md-9 col-sm-8 col-xs-12'>";
affiche_pop_up($msg,"admin");
echo "<h2>".get_vocab('admin_email_manager.php')."</h2>\n";
if (Settings::get("automatic_mail") != 'yes')
	echo "<h3 class=\"avertissement\">".get_vocab("attention_mail_automatique_désactive")."</h3>";
echo get_vocab("explain_automatic_mail3")."<br /><hr />\n";
echo "<form action=\"admin_email_manager.php\" method=\"post\">\n";
echo "<input type=\"checkbox\" name=\"send_always_mail_to_creator\" value=\"y\" ";
if (Settings::get('send_always_mail_to_creator')=='1')
	echo ' checked="checked" ';
echo ' />'."\n";
echo get_vocab("explain_automatic_mail1");
echo "\n".'<br /><div class="center"><input type="submit" name="mail1" value="'.get_vocab('save').'" /></div></form><hr />';
echo "<div>\n";
echo "<p>".get_vocab("explain_automatic_mail2")."<br />";

# Show all areas
echo "<form  id=\"area\" action=\"admin_email_manager.php\" method=\"post\">\n<div>";
echo "<label for='area_sel'>".get_vocab('areas')."&nbsp;</label>";
echo "<select name=\"area\" id='area_sel' onchange=\"area_go()\">\n";
echo "<option value=\"admin_email_manager.php?id_area=-1\">".get_vocab('select')."</option>\n";
foreach($all_areas as $id => $area_name){
  $selected = ($id == $id_area) ? "selected=\"selected\"" : "";
  $link = "admin_email_manager.php?id_area=$id";
  echo "<option $selected value=\"$link\">" . htmlspecialchars($area_name)."</option>\n";
}
echo "</select></div>".PHP_EOL;
echo "<script type=\"text/javascript\" >
	<!--
	function area_go()
	{
		box = document.getElementById(\"area\").area;
		destination = box.options[box.selectedIndex].value;
		if (destination) location.href = destination;
	}
	// -->
</script>
<noscript>
	<div><input type=\"submit\" value=\"Change\" /></div>
</noscript>
</form>";

# Show all rooms in the current area
# should we show a drop-down for the room list, or not?
echo "<form id=\"room\" action=\"admin_email_manager.php\" method=\"post\">\n<div>";
echo "<label for='room_sel'>".get_vocab('rooms')."&nbsp;</label>";
echo "<select name=\"room\" id='room_sel' onchange=\"room_go()\">\n";
echo "<option value=\"admin_email_manager.php?id_area=$id_area&amp;room=-1\">".get_vocab('select')."</option>\n";
foreach($all_rooms as $id => $data){
  $selected = ($id == $room) ? "selected=\"selected\"" : "";
  $link = "admin_email_manager.php?id_area=$id_area&amp;room=$id";
  echo "<option $selected value=\"$link\">" .$data."</option>\n";
}
echo "</select></div>\n
<script type=\"text/javascript\" >
	<!--
	function room_go()
	{
		box = document.getElementById(\"room\").room;
		destination = box.options[box.selectedIndex].value;
		if (destination) location.href = destination;
	}
		// -->
</script>
<noscript>
	<div><input type=\"submit\" value=\"Change\" /></div>
</noscript>
</form>";
echo "</p></div>\n";

# Don't continue if no area is selected :
if ($id_area <= 0)
{
	echo "<h3 class='avertissement'>".get_vocab("no_area")."</h3>";
	// fin de l'affichage de la colonne de droite
	echo "</div></section></body></html>";
	exit;
}
# Show area and room:
if ($this_room_name_des != '-1')
	$this_room_name_des = " (".$this_room_name_des.")";
else
	$this_room_name_des='';
if ($room == '-1')
{
	echo "<h3 class='avertissement'>".get_vocab("no_room")."</h3>";
	// fin de l'affichage de la colonne de droite
	echo "</div></section></body></html>";
	exit;
}
else
{
	echo "<p>";
	if ($nombre != 0){
    echo "<h3>".get_vocab("mail_user_list")."</h3>";
    foreach($mailed_users as $login => $nomp){
      echo "<b>".$nomp."</b> | ";
      echo "<a href='admin_email_manager.php?action=del_admin&amp;login_admin=".urlencode($login)."&amp;room=$room&amp;id_area=$id_area'>";
      echo get_vocab("delete")."</a><br />";
    }
  }
  else 
    echo "<h3><span class=\"avertissement\">".get_vocab("no_mail_user_list")."</span></h3>";
}
echo '<h3>'.get_vocab("add_user_to_list").'</h3>';
echo '<form action="admin_email_manager.php" method="get">';
echo '<select size="1" name="reg_admin_login">';
echo '<option value="">'.get_vocab("nobody").'</option>';
foreach($available_users as $login => $nomp){
  echo "<option value=\"$login\">".$nomp." </option>";
    }
echo '</select>';
echo '<input type="hidden" name="add_admin" value="yes" />';
echo '<input type="hidden" name="id_area" value="'.$id_area.'" />';
echo '<input type="hidden" name="room" value="'.$room.'" />';
echo '<input type="submit" value="Enregistrer" />';
echo '</form>';
echo '</p>';
// fin de l'affichage de la colonne de droite
echo "</div>";
end_page();
?>