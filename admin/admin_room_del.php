<?php
/**
 * admin_room_del
 * Interface de suppression d'un domaine ou d'une ressource : confirmation puis suppression
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2024-10-28 16:23$
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
$grr_script_name = "admin_room_del.php";

include "../include/admin.inc.php";

$type = isset($_GET["type"]) ? $_GET["type"] : NULL;
$confirm = isset($_GET["confirm"]) ? $_GET["confirm"] : NULL;
$room = isset($_GET["room"]) ? $_GET["room"] : -1;
$id_area = isset($_POST["id_area"]) ? $_POST["id_area"] : (isset($_GET["id_area"]) ? $_GET["id_area"] : -1);
$id_site = isset($_POST['id_site']) ? $_POST['id_site'] : (isset($_GET['id_site']) ? $_GET['id_site'] : -1);
$room = intval($room);
$id_area = intval($id_area);
$id_site = intval($id_site);
$user_id = getUserName();
$back = (isset($_SERVER['HTTP_REFERER']))? htmlspecialchars_decode($_SERVER['HTTP_REFERER'], ENT_QUOTES) : "./admin_accueil.php" ;

if ($type == "room")
{
	if ((authGetUserLevel($user_id,$room) < 4) || (!verif_acces_ressource($user_id, $room)))
	{
		showAccessDenied($back);
		exit();
	}
	if (isset($confirm))
	{
		//They have confirmed it already, so go blast!
		//First take out all appointments for this room
		grr_sql_command("DELETE FROM ".TABLE_PREFIX."_entry WHERE room_id=?","i",[$room]);
		grr_sql_command("DELETE FROM ".TABLE_PREFIX."_entry_moderate WHERE room_id=?","i",[$room]);
		grr_sql_command("DELETE FROM ".TABLE_PREFIX."_j_mailuser_room  WHERE id_room=?","i",[$room]);
		grr_sql_command("DELETE FROM ".TABLE_PREFIX."_j_user_room WHERE id_room=?","i",[$room]);
        grr_sql_command("DELETE FROM ".TABLE_PREFIX."_j_userbook_room WHERE id_room=?","i",[$room]);
		//Now take out the room itself
		grr_sql_command("DELETE FROM ".TABLE_PREFIX."_room WHERE id=?","i",[$room]);
		//Go back to the admin page
		Header("Location: admin_room.php?id_area=$id_area&id_site=$id_site");
	}
	else
	{
    $cas = 0;
    $resas = array();
		//Find out how many appointments would be deleted
		$sql = "SELECT name, start_time, end_time FROM ".TABLE_PREFIX."_entry WHERE room_id=?";
		$res = grr_sql_query($sql,"i",[$room]);
		if (!$res)
			fatal_error(1,grr_sql_error());
		else if (grr_sql_count($res) > 0)
		{
			foreach($res as $row)
			{
        $resas[] = $row;
			}
		}

		echo "</div>";
	}
}
if ($type == "area")
{
	// Seul l'admin peut supprimer un domaine
	if (authGetUserLevel($user_id, $id_area, 'area') < 5)
	{
		showAccessDenied($back);
		exit();
	}
	//We are only going to let them delete an area if there are
	//no rooms. its easier
	$n = grr_sql_query1("SELECT count(*) FROM ".TABLE_PREFIX."_room WHERE area_id=?","i",[$id_area]);
  echo $n;
	if ($n == 0)
	{
		// Suppression des champ additionnels
		$sqlstring = "SELECT id FROM ".TABLE_PREFIX."_overload WHERE id_area=?";
		$result = grr_sql_query($sqlstring,"i",[$id_area]);
    if(!$result)
      fatal_error(1,grr_sql_error());
    else{
      foreach($result as $field_row){
        $id_overload = $field_row['id'];
        // Suppression des données dans les réservations déjà effectuées
        grrDelOverloadFromEntries($id_overload);
        grr_sql_command("DELETE FROM ".TABLE_PREFIX."_overload WHERE id=?","i",[$id_overload]);
      }
      //OK, nothing there, lets blast it away
      grr_sql_command("DELETE FROM ".TABLE_PREFIX."_area WHERE id=?","i",[$id_area]);
      grr_sql_command("update ".TABLE_PREFIX."_utilisateurs set default_area = '-1', default_room = '-1' WHERE default_area=?","i",[$id_area]);
      grr_sql_command("DELETE FROM ".TABLE_PREFIX."_area_periodes WHERE id_area=?","i",[$id_area]);
      grr_sql_command("DELETE FROM ".TABLE_PREFIX."_j_useradmin_area WHERE id_area=?","i",[$id_area]);
      grr_sql_command("DELETE FROM ".TABLE_PREFIX."_j_type_area WHERE id_area=?","i",[$id_area]);
      grr_sql_command("DELETE FROM ".TABLE_PREFIX."_j_user_area WHERE id_area=?","i",[$id_area]);
      grr_sql_command("DELETE FROM ".TABLE_PREFIX."_j_site_area WHERE id_area=?","i",[$id_area]);
      $test = grr_sql_query1("select VALUE from ".TABLE_PREFIX."_setting WHERE NAME='default_area'");
      if ($test == $id_area)
      {
        grr_sql_command("DELETE FROM ".TABLE_PREFIX."_setting WHERE NAME='default_area'");
        grr_sql_command("DELETE FROM ".TABLE_PREFIX."_setting WHERE NAME='default_room'");
        // Settings
        require_once("../include/settings.class.php");
        //Chargement des valeurs de la table settingS
        if (!Settings::load())
          die("Erreur chargement settings");
      }
      //Redirect back to the admin page
      header("Location: admin_room.php?id_site=$id_site");
      die();
    }
	}
	else
	{//There are rooms left in the area
		$cas = 1;
	}
}
// html
start_page_w_header("", "", "", $type="with_session");
echo "<div class=\"container\">";
if($cas == 1){
  echo "<p class='avertissement larger'>".get_vocab('delarea')."</p>";
  echo "<p><a class='btn btn-default' href=\"admin_room.php?id_area=$id_area&amp;id_site=$id_site\">" . get_vocab('back') . "</a></p></div>";
}
if($cas == 0){
  // demande confirmation car les réservations vont être supprimées
  if(count($resas) > 0){
    echo "<p class='avertissement larger'>".get_vocab("deletefollowing")." :</p>";
    echo "<ul>";
    foreach($resas as $row){
    	echo "<li>".$row['name']." (".time_date_string($row['start_time'],$dformat)." -> ".time_date_string($row['end_time'],$dformat) . ")</li>";
    }
    echo "</ul>";
  }
  echo "<h1 class='center'>" . get_vocab("sure") . "</h1>";
  echo "<p class='center larger'>";
  echo "<a class=\"btn btn-danger\" type=\"button\" href=\"admin_room_del.php?type=room&amp;room=$room&amp;confirm=Y&amp;id_area=$id_area\">".get_vocab("YES")."!</a>";
  echo "&nbsp;&nbsp;<a class=\"btn btn-default\" type=\"button\" href=\"admin_room.php?id_area=$id_area\">".get_vocab("NO") . "!</a>";
  echo "</p>";
}
echo "</div>";
end_page();
?>