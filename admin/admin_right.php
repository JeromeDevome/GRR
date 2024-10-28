<?php
/**
 * admin_right.php
 * Interface de gestion des droits de gestion des ressources par les utilisateurs sélectionnés
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2024-10-28 11:48$
 * @author    JeromeB & Laurent Delineau & Yan Naessens
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
$grr_script_name = "admin_right.php";

include "../include/admin.inc.php";

$back = (isset($_SERVER['HTTP_REFERER']))? htmlspecialchars_decode($_SERVER['HTTP_REFERER'], ENT_QUOTES) : "./admin_accueil.php" ;
$id_area = intval(getFormVar('id_area','int',-1));
$room = intval(getFormVar('room','int',-1));
check_access(4, $back);
$user_id = getUserName();

// tableau des ressources auxquelles l'utilisateur n'a pas accès
$tab_rooms_noaccess = verif_acces_ressource($user_id, 'all');
$reg_admin_login = isset($_POST["reg_admin_login"]) ? clean_input($_POST["reg_admin_login"]) : NULL;
$reg_multi_admin_login = isset($_POST["reg_multi_admin_login"]) ? $_POST["reg_multi_admin_login"] : NULL;
$test_user =  isset($_POST["reg_multi_admin_login"]) ? "multi" : (isset($_POST["reg_admin_login"]) ? "simple" : NULL);
$action = isset($_GET["action"]) ? clean_input($_GET["action"]) : NULL;
$msg = '';
if (($test_user == "multi")&& !empty($reg_multi_admin_login)){
	foreach ($reg_multi_admin_login as $valeur)
	{
	// On commence par vérifier que l'utilisateur n'est pas déjà présent dans cette liste.
	// ajout pour une ressource d'un domaine
		if ($room != -1)
		{
		// Ressource
		// On vérifie que la ressource $room existe
			$test = grr_sql_query1("SELECT id FROM ".TABLE_PREFIX."_room WHERE id=?","i",[$room]);
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
			if (authGetUserLevel($user_id,$room) < 4)
			{
				showAccessDenied($back);
				exit();
			}
			$sql = "SELECT * FROM ".TABLE_PREFIX."_j_user_room WHERE (login = ? and id_room = ?)";
			$res = grr_sql_query($sql,"si",[$valeur,$room]);
			$test = grr_sql_count($res);
			if ($test != "0")
			{
				$msg = get_vocab("warning_exist");
			}
			else
			{
				if ($valeur != '')
				{
					$sql = "INSERT INTO ".TABLE_PREFIX."_j_user_room (login,id_room) VALUES (?,?)";
					if (grr_sql_command($sql,"si",[$valeur,$room]) < 0)
						fatal_error(0, "<p>" . grr_sql_error());
					else
						$msg = get_vocab("add_multi_user_succeed");
				}
			}
		}
		else
		{
		//ajout pour toutes les ressources du domaine
		// Domaine
		// On vérifie que le domaine $id_area existe
			$test = grr_sql_query1("SELECT id FROM ".TABLE_PREFIX."_area WHERE id=?","i",[$id_area]);
			if ($test == -1)
			{
				showAccessDenied($back);
				exit();
			}
		// Le domaine existe : on vérifie les privilèges de l'utilisateur
			if (authGetUserLevel($user_id,$id_area,'area') < 4)
			{
				showAccessDenied($back);
				exit();
			}
			$sql = "SELECT id FROM ".TABLE_PREFIX."_room WHERE area_id=$id_area";
		// on ne cherche pas parmi les ressources invisibles pour l'utilisateur
			foreach ($tab_rooms_noaccess as $key)
				$sql .= " and id != $key ";
			$res = grr_sql_query($sql);
			if ($res)
			{
				foreach($res as $row)
				{
					$sql2 = "SELECT login FROM ".TABLE_PREFIX."_j_user_room WHERE (login = ? and id_room = ?)";
					$res2 = grr_sql_query($sql2,"si",[$valeur,$row['id']]);
					$nb = grr_sql_count($res2);
					if ($nb == 0)
					{
						$sql3 = "INSERT INTO ".TABLE_PREFIX."_j_user_room (login, id_room) VALUES (?,?)";
						if (grr_sql_command($sql3,"si",[$valeur,$row['id']]) < 0)
							fatal_error(0, "<p>" . grr_sql_error());
						else
							$msg = get_vocab("add_multi_user_succeed");
					}
				}
			}
		}
	}
}
if ($test_user == "simple")
{
	if ($reg_admin_login)
	{
	// On commence par vérifier que l'utilisateur n'est pas déjà présent dans cette liste.
	// ajout pour une ressource d'un domaine
		if ($room != -1)
		{
		// Ressource
		// On vérifie que la ressource $room existe
			$test = grr_sql_query1("SELECT id FROM ".TABLE_PREFIX."_room WHERE id=?","i",[$room]);
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
			if (authGetUserLevel($user_id,$room) < 4)
			{
				showAccessDenied($back);
				exit();
			}
			$sql = "SELECT * FROM ".TABLE_PREFIX."_j_user_room WHERE (login = ? and id_room = ?)";
			$res = grr_sql_query($sql,"si",[$reg_admin_login,$room]);
			$test = grr_sql_count($res);
			if ($test != "0")
				$msg = get_vocab("warning_exist");
			else
			{
				if ($reg_admin_login != '')
				{
					$sql = "INSERT INTO ".TABLE_PREFIX."_j_user_room (login,id_room) VALUES (?,?)";
					if (grr_sql_command($sql,"si",[$reg_admin_login,$room]) < 0)
						fatal_error(0, "<p>" . grr_sql_error());
					else
						$msg = get_vocab("add_user_succeed");
				}
			}
		}
		else
		{
			//ajout pour toutes les ressources du domaine
			// Domaine
			// On vérifie que le domaine $id_area existe
			$test = grr_sql_query1("SELECT id FROM ".TABLE_PREFIX."_area WHERE id=?","i",[$id_area]);
			if ($test == -1)
			{
				showAccessDenied($back);
				exit();
			}
			// Le domaine existe : on vérifie les privilèges de l'utilisateur
			if (authGetUserLevel($user_id,$id_area,'area') < 4)
			{
				showAccessDenied($back);
				exit();
			}
			$sql = "SELECT id FROM ".TABLE_PREFIX."_room WHERE area_id=$id_area";
			// on ne cherche pas parmi les ressources invisibles pour l'utilisateur
			foreach ($tab_rooms_noaccess as $key)
				$sql .= " and id != $key ";
			$res = grr_sql_query($sql);
			if ($res)
			{
				foreach($res as $row)
				{
					$sql2 = "SELECT login FROM ".TABLE_PREFIX."_j_user_room WHERE (login = ? AND id_room = ?)";
					$res2 = grr_sql_query($sql2,"si",[$reg_admin_login,$row['id']]);
					$nb = grr_sql_count($res2);
					if ($nb==0)
					{
						$sql3 = "INSERT INTO ".TABLE_PREFIX."_j_user_room (login, id_room) values (?,?)";
						if (grr_sql_command($sql3,"si",[$reg_admin_login,$row['id']]) < 0)
							fatal_error(0, "<p>" . grr_sql_error());
						else
							$msg = get_vocab("add_user_succeed");
					}
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
		unset($login_admin); $login_admin = $_GET["login_admin"];
		$sql = "DELETE FROM ".TABLE_PREFIX."_j_user_room WHERE (login=? and id_room = ?)";
		if (grr_sql_command($sql,"si",[$login_admin,$room]) < 0)
			fatal_error(0, "<p>" . grr_sql_error());
		else
			$msg = get_vocab("del_user_succeed");
	}
	if ($action == "del_admin_all")
	{
		if (authGetUserLevel($user_id,$id_area,'area') < 4)
		{
			showAccessDenied($back);
			exit();
		}
		$sql = "SELECT id FROM ".TABLE_PREFIX."_room WHERE area_id=$id_area ";
		// on ne cherche pas parmi les ressources invisibles pour l'utilisateur
		foreach ($tab_rooms_noaccess as $key)
			$sql .= " and id != $key ";
		$sql .= " order by room_name";
		$res = grr_sql_query($sql);
		if ($res)
		{
			foreach($res as $row)
			{
				$sql2 = "DELETE FROM ".TABLE_PREFIX."_j_user_room WHERE (login=? and id_room = ?)";
				if (grr_sql_command($sql2,"si",[protect_data_sql($_GET['login_admin']),$row['id']]) < 0)
					fatal_error(0, "<p>" . grr_sql_error());
				else
					$msg = get_vocab("del_user_succeed");
			}
		}
	}
}
if (($id_area == -1) && (isset($row['id'])))
{
	if (authGetUserLevel($user_id,$row['id'],'area') >= 6)
		$id_area = get_default_area();
	else
	{
		//Retourne le domaine par défaut; Utilisé si aucun domaine n'a été défini.
		// On cherche le premier domaine à accès non restreint
		$id_area = grr_sql_query1("SELECT a.id FROM ".TABLE_PREFIX."_area a, ".TABLE_PREFIX."_j_useradmin_area j
			WHERE a.id=j.id_area and j.login=? 
			ORDER BY a.access, a.order_display, a.area_name
			LIMIT 1","s",[$user_id]);
	}
}

$this_area_name = grr_sql_query1("SELECT area_name FROM ".TABLE_PREFIX."_area WHERE id=?","i",[$id_area]);
$this_room_name = grr_sql_query1("SELECT room_name FROM ".TABLE_PREFIX."_room WHERE id=?","i",[$room]);
$this_room_name_des = grr_sql_query1("SELECT description FROM ".TABLE_PREFIX."_room WHERE id=?","i",[$room]);
if ($this_room_name_des != '-1')
	$this_room_name_des = " (".$this_room_name_des.")";
else
	$this_room_name_des = '';
// sélecteur de domaines
$sel_area = "<div><SELECT name=\"area\" onchange=\"area_go()\">\n";
$sel_area .= "<option value=\"admin_right.php?id_area=-1\">".get_vocab('select')."</option>\n";
$sql = "SELECT id, area_name FROM ".TABLE_PREFIX."_area ORDER BY order_display";
$res = grr_sql_query($sql);
if ($res)
{
	foreach($res as $row)
	{
		$selected = ($row['id'] == $id_area) ? "selected" : "";
		$link = "admin_right.php?id_area=".$row['id'];
		// On affiche uniquement les domaines administrés par l'utilisateur
		if (authGetUserLevel($user_id,$row['id'],'area') >= 4)
			$sel_area .= "<option $selected value=\"$link\">" . htmlspecialchars($row['area_name'])."</option>\n";
	}
}
$sel_area .= "</select></div>\n";
// sélecteur de ressources
$sel_room = "<div><SELECT name=\"room\" onchange=\"room_go()\">\n";
$sel_room .= "<option value=\"admin_right.php?id_area=$id_area&amp;room=-1\">".get_vocab('select_all')."</option>\n";
$sql = "SELECT id, room_name, description FROM ".TABLE_PREFIX."_room WHERE area_id=$id_area ";
foreach ($tab_rooms_noaccess as $key)
	$sql .= " and id != $key ";
$sql .= " order by order_display,room_name";
$res = grr_sql_query($sql);
if ($res)
{
	foreach($res as $row)
	{
		if ($row['description'])
			$temp = " (".htmlspecialchars($row['description']).")";
		else
			$temp = "";
		$selected = ($row['id'] == $room) ? "selected" : "";
		$link = "admin_right.php?id_area=$id_area&amp;room=".$row['id'];
		$sel_room .= "<option $selected value=\"$link\">" . htmlspecialchars($row['room_name'].$temp)."</option>\n";
	}
}
$sel_room .= "</select></div>\n";
// utilisateurs ayant droits
$tab_users_w_right = array();
$list_users_w_right = "";
$nombre = 0;
// sur une ressource ?
if($room != -1){
	$sql = "SELECT u.login, u.nom, u.prenom FROM ".TABLE_PREFIX."_utilisateurs u JOIN ".TABLE_PREFIX."_j_user_room j ON u.login=j.login WHERE j.id_room=? ORDER BY u.nom, u.prenom";
	$res = grr_sql_query($sql,"i",[$room]);
  if($res){
    $nombre = grr_sql_count($res);
    if($nombre != 0)
      foreach($res as $row){
        $login_admin = urlencode($row['login']);
        $nom_admin = htmlspecialchars($row['nom']);
        $prenom_admin = htmlspecialchars($row['prenom']);
        $tab_users_w_right[$login_admin] = [$nom_admin,$prenom_admin];
        $list_users_w_right .= "<b> $nom_admin $prenom_admin </b> | ";
        $list_users_w_right .= "<a href='admin_right.php?action=del_admin&amp;login_admin=$login_admin&amp;room=$room&amp;id_area=$id_area'>".get_vocab("delete")."</a>";
        $list_users_w_right .= "<br />";
      }
  }
}
// sur toutes les ressources du domaine ?
else {
  $titre_auth = "";
	$exist_admin='no';
	$sql = "SELECT login, nom, prenom FROM ".TABLE_PREFIX."_utilisateurs WHERE (statut='utilisateur' or statut='gestionnaire_utilisateur')";
	$res = grr_sql_query($sql);
	if ($res)
	{
		foreach($res as $row)
		{
			$is_admin = 'yes';
			$sql2 = "SELECT id, room_name, description FROM ".TABLE_PREFIX."_room WHERE area_id=$id_area ";
			foreach ($tab_rooms_noaccess as $key)
				$sql2 .= " and id != $key ";
			$sql2 .= " ORDER BY order_display,room_name";
			$res2 = grr_sql_query($sql2);
			if ($res2)
			{
				$test = grr_sql_count($res2);
				if ($test != 0)
				{
					foreach($res2 as $row2)
					{
						$sql3 = "SELECT login FROM ".TABLE_PREFIX."_j_user_room WHERE (id_room=? and login=?)";
						$res3 = grr_sql_query($sql3,"is",[$row2['id'],$row['login']]);
						$nombre = grr_sql_count($res3);
						if ($nombre == 0)
							$is_admin = 'no';
					}
				}
				else
					$is_admin = 'no';
			}
			if ($is_admin == 'yes')
			{
				if ($exist_admin == 'no')
				{
					$titre_auth = get_vocab("user_list");
					$exist_admin = 'yes';
				}
        $login_admin = urlencode($row['login']);
        $nom_admin = htmlspecialchars($row['nom']);
        $prenom_admin = htmlspecialchars($row['prenom']);
        $tab_users_w_right[$login_admin] = [$nom_admin,$prenom_admin];
        $list_users_w_right .= "<b> $nom_admin $prenom_admin </b> | ";
        $list_users_w_right .= "<a href='admin_right.php?action=del_admin_all&amp;login_admin=$login_admin&amp;room=$room&amp;id_area=$id_area'>".get_vocab("delete")."</a>";
        $list_users_w_right .= "<br />";
			}
		}
	}
	if ($exist_admin=='no')
		$titre_auth = get_vocab("no_admin_all");
}
// utilisateurs candidats
$all_users = array();
$candidats = array();
if($id_area != -1){
  $sql = "SELECT login, nom, prenom FROM ".TABLE_PREFIX."_utilisateurs WHERE  (etat!='inactif' and (statut='utilisateur' or statut='gestionnaire_utilisateur')) order by nom, prenom";
  $res = grr_sql_query($sql);
  if($res){
    foreach($res as $row){
      if(authUserAccesArea($row['login'],$id_area) == 1)
        $all_users[$row['login']]=[$row['nom'],$row['prenom']];
    }
    $candidats = array_diff_key($all_users,$tab_users_w_right);
  }
}
  
// code HTML
//print the page header
start_page_w_header("", "", "", $type="with_session");
// Affichage de la colonne de gauche
include "admin_col_gauche2.php";
// colonne de droite
echo "<div class='col-md-9 col-sm-8 col-xs-12'>";
echo "<h2>".get_vocab('admin_right.php')."</h2>\n";
echo "<p><i>".get_vocab("admin_right_explain")."</i></p>\n";
// Affichage d'un pop-up
affiche_pop_up($msg,"admin");
//Table with areas, rooms.
echo "<table><tr>\n";
//Show all areas
echo "<td ><p><b>".get_vocab("areas")."</b></p>\n";
echo "<form id=\"area\" action=\"admin_right.php\" method=\"post\">\n";
echo $sel_area;
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
</noscript>";
echo "</form>\n";
echo "</td>\n";
//Show all rooms in the current area
echo "<td><p><b>".get_vocab('rooms').get_vocab('deux_points')."</b></p>";
//should we show a drop-down for the room list, or not?
echo "<form id=\"room\" action=\"admin_right.php\" method=\"post\">\n";
echo $sel_room;
echo "<script type=\"text/javascript\" >
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
echo "</td>\n";
echo "</tr></table>\n";
//Don't continue if no area is selected:
if ($id_area <= 0)
{
	echo "<h1>".get_vocab("no_area")."</h1>";
	// fin de l'affichage de la colonne de droite
	echo "</div></section></body></html>";
	exit;
}
// utilisateurs ayant droits
echo "<table class='table table-noborder'><tr><td>";
if ($room != -1)
{
  if($nombre != 0){
    echo "<h3>".get_vocab("user_list")."</h3>";
    echo $list_users_w_right;
  }
  else 
    echo "<h3><span class=\"avertissement\">".get_vocab("no_admin")."</span></h3>";
}
else
{
  if($exist_admin == 'no'){
    echo "<h3><span class=\"avertissement\">".$titre_auth."</span></h3>";
  }
  else{
    echo "<h3>".$titre_auth."</h3>";
    echo $list_users_w_right;
  }
}
// sélection d'un utilisateur
echo '<h3>'.get_vocab("add_user_to_list").'</h3>';
echo '<form  action="admin_right.php" method="post">';
echo '	<select size="1" name="reg_admin_login">';
echo '		<option value="">'.get_vocab("nobody").'</option>';
foreach($candidats as $login => $value){
  echo "<option value=\"$login\">".htmlspecialchars($value[0])." ".htmlspecialchars($value[1])." </option>";
}
echo '	</select>';
echo '	<input type="hidden" name="id_area" value="'.$id_area.'" />';
echo '	<input type="hidden" name="room" value="'.$room.'" />';
echo '	<input type="submit" value="Enregistrer" />';
echo '</form>';
echo '</td></tr>';
// selection pour ajout de masse
if (count($candidats) > 0)
{
	echo '<tr><td>';
	echo '<h3>'.get_vocab("add_multiple_user_to_list").get_vocab("deux_points").'</h3>';
	echo '	<form action="admin_right.php" method="post">';
	echo '		<select name="agent" size="8" style="width:200px;" multiple="multiple" ondblclick="Deplacer(this.form.agent,this.form.elements[\'reg_multi_admin_login[]\'])">';
  foreach($candidats as $login => $value){
    echo "<option value=\"$login\">".htmlspecialchars($value[0])." ".htmlspecialchars($value[1])." </option>";
  }
  echo '</select>';
	echo '	<input type="button" value="&lt;&lt;" onclick="Deplacer(this.form.elements[\'reg_multi_admin_login[]\'],this.form.agent)"/>';
	echo '	<input type="button" value="&gt;&gt;" onclick="Deplacer(this.form.agent,this.form.elements[\'reg_multi_admin_login[]\'])"/>';
	echo '<select name="reg_multi_admin_login[]" id="reg_multi_admin_login" size="8" style="width:200px;" multiple="multiple" ondblclick="Deplacer(this.form.elements[\'reg_multi_admin_login[]\'],this.form.agent)">';
	echo '	<option> </option>';
	echo '</select>';
	echo '	<input type="hidden" name="id_area" value="'.$id_area.'" />';
	echo '	<input type="hidden" name="room" value="'.$room.'" />';
	echo '	<input type="submit" value="Enregistrer"  onclick="selectionner_liste(this.form.reg_multi_admin_login);" />';
	echo '<script type="text/javascript">';
    echo '	vider_liste(document.getElementById(\'reg_multi_admin_login\')); ';
	echo '</script>';
    echo '</form>';
	echo "</td></tr>";
}
echo "</table>";
// fin de l'affichage de la colonne de droite
echo "</div>";
// et de la page
end_page();
?>