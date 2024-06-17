<?php
/**
 * admin_book_room.php
 * Interface de gestion des accès restreints aux ressources restreintes
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2024-06-17 16:14$
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
$grr_script_name = "admin_book_room.php";

include "../include/admin.inc.php";

// fonction locale
function insereUser($user_id,$room_id){
    global $msg;
	// On commence par vérifier que l'utilisateur n'est pas déjà présent dans cette liste.
    if ($room_id != -1)
    {
        $sql = "SELECT * FROM ".TABLE_PREFIX."_j_userbook_room WHERE (login =? and id_room =? )";
        $res = grr_sql_query($sql,"si",[$user_id,$room_id]);
        if(!$res)
            fatal_error(1,"<p>".grr_sql_error());
        else{
            $test = grr_sql_count($res);
            if ($test > 0)
                $msg = get_vocab("warning_exist");
            else
            {
                if ($user_id != '')
                {
                    $sql = "INSERT INTO ".TABLE_PREFIX."_j_userbook_room SET login=? , id_room =? ";
                    $cmd = grr_sql_command($sql,"si",[$user_id,$room_id]);
                    if($cmd < 0)
                        fatal_error(1,"<p>".grr_sql_error());
                    else
                        $msg= get_vocab("add_user_succeed");
                }
            }
        }
    }
}

$id_room = getFormVar("id_room","int",-1);
$multisite = Settings::get("module_multisite") == "Oui";
$reg_user_login = isset($_POST["reg_user_login"]) ? $_POST["reg_user_login"] : NULL;
$reg_multi_user_login = isset($_POST["reg_multi_user_login"]) ? $_POST["reg_multi_user_login"] : NULL;
$test_user =  isset($_POST["reg_multi_user_login"]) ? "multi" : (isset($_POST["reg_user_login"]) ? "simple" : NULL);
$action = isset($_GET["action"]) ? $_GET["action"] : NULL;
$user_name = getUserName();
$msg = '';

$back = '';
if (isset($_SERVER['HTTP_REFERER']))
	$back = htmlspecialchars($_SERVER['HTTP_REFERER'], ENT_QUOTES);
check_access(4, $back); // accès à cette page pour les gestionnaires de domaines

if ($test_user == "multi")
{	
    if ($id_room != -1)
        foreach ($reg_multi_user_login as $valeur){
            insereUser($valeur,$id_room);
        }
}
elseif ($test_user == "simple")
{
    insereUser($reg_user_login,$id_room);
}

if ($action=='del_user')
{
	unset($login_user);
	$login_user = clean_input($_GET["login_user"]);
	$sql = "DELETE FROM ".TABLE_PREFIX."_j_userbook_room WHERE (login=? and id_room =? )";
	if (grr_sql_command($sql,"si",[$login_user,$id_room]) < 0)
		fatal_error(1, "<p>" . grr_sql_error());
	else
		$msg = get_vocab("del_user_succeed");
}
# choisir parmi les ressources restreintes
$this_room_name = "";
$sel_room = '';
if($multisite)
  $sql = "SELECT r.id,room_name,area_name,sitename
          FROM ((`grr_room` r JOIN `grr_area` a ON r.area_id = a.id)
          JOIN grr_j_site_area ON a.id = id_area)
          JOIN grr_site s ON s.id = id_site
          WHERE r.who_can_book = 0
          ORDER BY room_name";
else
  $sql = "SELECT r.id,room_name,area_name
          FROM `grr_room` r JOIN `grr_area` a ON r.area_id = a.id
          WHERE r.who_can_book = 0
          ORDER BY room_name";
$res = grr_sql_query($sql);
if (!$res)
    fatal_error(1,grr_sql_error($res));
else{
    $nb = grr_sql_count($res);
    $sel_room .= "\n<form id=\"room\" action=\"admin_book_room.php\" method=\"post\">\n";
    $sel_room .= "<label for='id_room'>".get_vocab('rooms').get_vocab('deux_points')."</label>";
    $sel_room .= "<select id='id_room' name=\"id_room\" onchange=\"room_go()\">";
    $sel_room .= "\n<option value=-1>".get_vocab('select')."</option>";
    foreach($res as $row)
    {
      // on vérifie que l'utilisateur connecté a les droits suffisants
      if (authGetUserLevel($user_name,$id_room)>2)
      {
        $selected = ($row['id'] == $id_room) ? "selected = \"selected\"" : "";
        $text = '';
        if($multisite)
          $text.= $row['sitename']." > ";
        $text.= $row['area_name']." > ".$row['room_name'];
        $sel_room .= "\n<option $selected value=\"".$row['id']."\">" . htmlspecialchars($text)."</option>";
      }
    }	
    $sel_room .= "</select>
    <script  type=\"text/javascript\" >
      <!--
      function room_go()
      {
        box = document.getElementById('room').id_room;
        destination = \"./admin_book_room.php\"+\"?id_room=\"+box.options[box.selectedIndex].value;
        if (destination) location.href = destination;
      }
    // -->
    </script>
    <noscript>
      <div><input type=\"submit\" value=\"Change\" /></div>
    </noscript>
    </form>";
}
// utilisateurs autorisés
$auth_users = "";
if ($id_room != -1)
{
	$sql = "SELECT u.login, u.nom, u.prenom FROM ".TABLE_PREFIX."_utilisateurs u JOIN ".TABLE_PREFIX."_j_userbook_room j ON u.login=j.login WHERE j.id_room=? ORDER BY u.nom, u.prenom";
	$res = grr_sql_query($sql,"i",[$id_room]);
    if (!$res)
        fatal_error(1,grr_sql_error($res));
    else {
        $nombre = grr_sql_count($res);
        if ($nombre == 0)
            $auth_users.= "<h3 class='avertissement'>".get_vocab("no_userbook_room")."</h3>\n";
        else{
            $auth_users.= "<h3>".get_vocab("user_book_room_list")."</h3>\n";
            foreach($res as $row)
            {
                $auth_users.= "<b>".htmlspecialchars($row['nom'])." ".htmlspecialchars($row['prenom'])."</b>";
                $auth_users.= " | <a href='admin_book_room.php?action=del_user&amp;login_user=".urlencode($row['login'])."&amp;id_room=$id_room'>".get_vocab("delete")."</a><br />\n";
            }
        }
    }
}
// sélecteurs d'utilisateurs
$add_one = "";
$add_multi = "";
if($id_room != -1){
    $sql = "SELECT login, nom, prenom FROM ".TABLE_PREFIX."_utilisateurs WHERE (etat!='inactif' AND (statut='utilisateur' OR statut='visiteur' OR statut='gestionnaire_utilisateur')) AND login NOT IN (SELECT DISTINCT login FROM ".TABLE_PREFIX."_j_userbook_room WHERE id_room =? ) ORDER BY nom, prenom";
    $res = grr_sql_query($sql,"i",[$id_room]);
    if(!$res)
        fatal_error(1,grr_sql_error());
    else{
        $add_one.= '<h3>'.get_vocab("add_user_to_list").'</h3>';
        $add_one.= '<form action="admin_book_room.php" method="post">';
        $add_one.= '<select size="1" name="reg_user_login">';
        $add_one.= '<option value="">'.get_vocab("nobody").'</option>';
        foreach($res as $row){
            // on n'affiche que les utilisateurs ayant accès à la ressource
            if (verif_acces_ressource($row['login'],$id_room))
                $add_one.= "<option value='".$row['login']."'>".htmlspecialchars($row['nom'])." ".htmlspecialchars($row['prenom'])." </option>\n";
        }
        $add_one.= '</select>';
        $add_one.= '<input type="hidden" name="id_room" value="'.$id_room.'" />';
        $add_one.= '<input type="submit" value="Enregistrer" />';
        $add_one.= '</form>';
        $nb_users = grr_sql_count($res);
        if ($nb_users > 0)
        {
            $add_multi.= '<h3>'.get_vocab("add_multiple_user_to_list").get_vocab("deux_points").'</h3>';
            $add_multi.= '<form action="admin_book_room.php" method="post">';
            $add_multi.= '<select name="agent" size="8" style="width:200px;" multiple="multiple" ondblclick="Deplacer(this.form.agent,this.form.elements[\'reg_multi_user_login[]\'])">';
            foreach($res as $row)
                // on n'affiche que les utilisateurs ayant accès à la ressource
                if (verif_acces_ressource($row['login'],$id_room))
                    $add_multi.= "<option value='".$row['login']."'>".htmlspecialchars($row['nom'])." ".htmlspecialchars($row['prenom'])." </option>\n";
            $add_multi.= '</select>';
            $add_multi.= '<input type="button" value="&lt;&lt;" onclick="Deplacer(this.form.elements[\'reg_multi_user_login[]\'],this.form.agent)"/>';
            $add_multi.= '<input type="button" value="&gt;&gt;" onclick="Deplacer(this.form.agent,this.form.elements[\'reg_multi_user_login[]\'])"/>';
            $add_multi.= '<select name="reg_multi_user_login[]" id="reg_multi_user_login" size="8" style="width:200px;" multiple="multiple" ondblclick="Deplacer(this.form.elements[\'reg_multi_user_login[]\'],this.form.agent)">';
            $add_multi.= '<option> </option>';
            $add_multi.= '</select>';
            $add_multi.= '<input type="hidden" name="id_room" value="'.$id_room.'" />';
            $add_multi.= '<input type="submit" value="Enregistrer"  onclick="selectionner_liste(this.form.reg_multi_user_login);"/>';
            $add_multi.= '<script type="text/javascript">
                vider_liste(document.getElementById(\'reg_multi_user_login\'));
            </script>';
            $add_multi.= '</form>';
        }
    }
}

// code HTML
# print the page header
start_page_w_header("", "", "", $type="with_session");
// Affichage de la colonne de gauche
include "admin_col_gauche2.php";
// colonne de droite
echo "<div class='col-md-9 col-sm-8 col-xs-12'>";
echo "<h2>".get_vocab('admin_book_room.php')."</h2>\n";
affiche_pop_up($msg,"admin");
echo "<div>";
echo $sel_room;
echo "</div>";
if($id_room != -1){
    echo "<div>";
    echo $auth_users;
    echo "</div>";
    echo "<div>";
    echo $add_one;
    echo "</div>";
    echo "<div>";
    echo $add_multi;
    echo "</div>";
}
else
{
    if ($nb =0)
        echo "<h3>".get_vocab("no_restricted_room")."</h3>";
    else
        echo "<h3>".get_vocab("no_room_selected")."</h3>";
}
// fin de la colonne de droite
echo "</div>";
// et de la page 
end_page();
?>