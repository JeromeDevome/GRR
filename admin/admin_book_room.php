<?php
/**
 * admin_book_room.php
 * Interface de gestion des accès restreints aux ressources restreintes
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2020-05-02 13:40$
 * @author    Laurent Delineau & JeromeB & Yan Naessens
 * @copyright Copyright 2003-2020 Team DEVOME - JeromeB
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

$id_room = isset($_POST["id_room"]) ? $_POST["id_room"] : (isset($_GET["id_room"]) ? $_GET["id_room"] : -1);
$id_room = intval(clean_input($id_room));
$reg_user_login = isset($_POST["reg_user_login"]) ? $_POST["reg_user_login"] : NULL;
$reg_multi_user_login = isset($_POST["reg_multi_user_login"]) ? $_POST["reg_multi_user_login"] : NULL;
$test_user =  isset($_POST["reg_multi_user_login"]) ? "multi" : (isset($_POST["reg_user_login"]) ? "simple" : NULL);
$action = isset($_GET["action"]) ? $_GET["action"] : NULL;
$user_name = getUserName();
$msg = '';

$back = '';
if (isset($_SERVER['HTTP_REFERER']))
	$back = htmlspecialchars($_SERVER['HTTP_REFERER'], ENT_QUOTES);
check_access(4, $back);

// Si la table j_user_area est vide, il faut modifier la requête
// $test_grr_j_user_area = grr_sql_count(grr_sql_query("SELECT * from ".TABLE_PREFIX."_j_user_area"));
// la requête qui précède semble inutile
if ($test_user == "multi")
{	
    if ($id_room != -1)
    {
        if (authGetUserLevel(getUserName(), $id_room) < 4)
        {
            showAccessDenied($back);
            exit();
        }
        foreach ($reg_multi_user_login as $valeur)
        {
        // On commence par vérifier que l'utilisateur n'est pas déjà présent dans cette liste.
            $sql = "SELECT * FROM ".TABLE_PREFIX."_j_userbook_room WHERE (login = '".$valeur."' and id_room = '$id_room')";
            $res = grr_sql_query($sql);
            $test = grr_sql_count($res);
            if ($test > 0)
                $msg = get_vocab("warning_exist");
            else
            {
                if ($valeur != '')
                {
                    $sql = "INSERT INTO ".TABLE_PREFIX."_j_userbook_room SET login= '$valeur', id_room = '$id_room'";
                    if (grr_sql_command($sql) < 0)
                        fatal_error(1, "<p>" . grr_sql_error());
                    else
                        $msg= get_vocab("add_multi_user_succeed");
                }
            }
        }
	}
}

if ($test_user == "simple")
{
	if ($id_room != -1)
	{
		if (authGetUserLevel(getUserName(), $id_room) < 4)
		{
			showAccessDenied($back);
			exit();
		}
   // On commence par vérifier que l'utilisateur n'est pas déjà présent dans cette liste.
		$sql = "SELECT * FROM ".TABLE_PREFIX."_j_userbook_room WHERE (login = '$reg_user_login' and id_room = '$id_room')";
		$res = grr_sql_query($sql);
		$test = grr_sql_count($res);
		if ($test > 0)
			$msg = get_vocab("warning_exist");
		else
		{
			if ($reg_user_login != '')
			{
				$sql = "INSERT INTO ".TABLE_PREFIX."_j_userbook_room SET login= '$reg_user_login', id_room = '$id_room'";
				if (grr_sql_command($sql) < 0)
					fatal_error(1, "<p>" . grr_sql_error());
				else
					$msg = get_vocab("add_user_succeed");
			}
		}
	}
}

if ($action=='del_user')
{
	if (authGetUserLevel(getUserName(), $id_room) < 4)
	{
		showAccessDenied($back);
		exit();
	}
	unset($login_user);
	$login_user = clean_input($_GET["login_user"]);
	$sql = "DELETE FROM ".TABLE_PREFIX."_j_userbook_room WHERE (login='$login_user' and id_room = '$id_room')";
	if (grr_sql_command($sql) < 0)
		fatal_error(1, "<p>" . grr_sql_error());
	else
		$msg = get_vocab("del_user_succeed");
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
# première étape : choisir parmi les ressources restreintes
$this_room_name = "";
$out_html = '';
$out_html .= "\n<form id=\"room\" action=\"admin_book_room.php\" method=\"post\">\n";
$out_html .= "<label>".get_vocab('rooms')."</label>";
$out_html .= "<select name=\"room\" onchange=\"room_go()\">";
$out_html .= "\n<option value=\"admin_book_room.php?id_room=-1\">".get_vocab('select')."</option>";
$sql = "select id, room_name from ".TABLE_PREFIX."_room where who_can_book =0 order by room_name";
$res = grr_sql_query($sql);
$nb = grr_sql_count($res);
if (!$res)
    grr_sql_error($res);
else
	for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
	{
		// on vérifie que l'utilisateur connecté a les droits suffisants
		if (authGetUserLevel($user_name,$id_room)>2)
		{
            $selected = ($row[0] == $id_room) ? "selected = \"selected\"" : "";
            $link = "admin_book_room.php?id_room=$row[0]";
			$out_html .= "\n<option $selected value=\"$link\">" . htmlspecialchars($row[1])."</option>";
			//$existe_domaine = 'yes';
		}
	}
	$out_html .= "</select>
	<script  type=\"text/javascript\" >
		<!--
		function room_go()
		{
			box = document.getElementById('room').room;
			destination = box.options[box.selectedIndex].value;
			if (destination) location.href = destination;
		}
	// -->
	</script>
	<noscript>
		<div><input type=\"submit\" value=\"Change\" /></div>
	</noscript>
</form>";
echo $out_html;

# deuxième étape : la ressource étant choisie, afficher les utilisateurs autorisés à réserver et le formulaire de mise à jour de la liste
if ($id_room != -1)
{
	$sql = "SELECT u.login, u.nom, u.prenom FROM ".TABLE_PREFIX."_utilisateurs u JOIN ".TABLE_PREFIX."_j_userbook_room j ON u.login=j.login WHERE j.id_room='$id_room' ORDER BY u.nom, u.prenom";
	$res = grr_sql_query($sql);
    if (!$res)
        grr_sql_error($res);
    else {
        $nombre = grr_sql_count($res);
        if ($nombre == 0)
            echo "<h3 class='avertissement'>".get_vocab("no_userbook_room")."</h3>\n";
        else{
            echo "<h3>".get_vocab("user_book_room_list")."</h3>\n";
            for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
            {
                $login_user = $row[0];
                $nom_admin = htmlspecialchars($row[1]);
                $prenom_admin = htmlspecialchars($row[2]);
                echo "<b>";
                echo "$nom_admin $prenom_admin</b> | <a href='admin_book_room.php?action=del_user&amp;login_user=".urlencode($login_user)."&amp;id_room=$id_room'>".get_vocab("delete")."</a><br />\n";
            }
        }
    }
    echo '<h3>'.get_vocab("add_user_to_list").'</h3>';
	echo '	<form action="admin_book_room.php" method="post">';
	echo '		<select size="1" name="reg_user_login">';
	echo '			<option value="">'.get_vocab("nobody").'</option>';
        $sql = "SELECT login, nom, prenom FROM ".TABLE_PREFIX."_utilisateurs WHERE (etat!='inactif' and (statut='utilisateur' or statut='visiteur' or statut='gestionnaire_utilisateur')) AND login NOT IN (SELECT DISTINCT login FROM ".TABLE_PREFIX."_j_userbook_room WHERE id_room = '$id_room') order by nom, prenom";
        $res = grr_sql_query($sql);
        if ($res)
            for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
                // on n'affiche que les utilisateurs ayant accès à la ressource
                if (verif_acces_ressource($row[0],$id_room))
                    echo "<option value=\"$row[0]\">".htmlspecialchars($row[1])." ".htmlspecialchars($row[2])." </option>\n";
	echo '		</select>';
	echo '		<input type="hidden" name="id_room" value="'.$id_room.'" />';
	echo '		<input type="submit" value="Enregistrer" />';
	echo '	</form>';
// sélection pour ajout de masse !-->
    $sql = "SELECT login, nom, prenom FROM ".TABLE_PREFIX."_utilisateurs WHERE (etat!='inactif' and (statut='utilisateur' or statut='visiteur' or statut='gestionnaire_utilisateur')) AND login NOT IN (SELECT login FROM ".TABLE_PREFIX."_j_userbook_room WHERE id_room = '$id_room') order by nom, prenom";
    $res = grr_sql_query($sql);
    if (!$res)
        grr_sql_error($res);
    else {
        $nb_users = grr_sql_count($res);
        if ($nb_users > 0)
        {
            echo '<h3>'.get_vocab("add_multiple_user_to_list").get_vocab("deux_points").'</h3>';
            echo '<form action="admin_book_room.php" method="post">';
            echo '<select name="agent" size="8" style="width:200px;" multiple="multiple" ondblclick="Deplacer(this.form.agent,this.form.elements[\'reg_multi_user_login[]\'])">';
            for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
                // on n'affiche que les utilisateurs ayant accès à la ressource
                if (verif_acces_ressource($row[0],$id_room))
                    echo "<option value=\"$row[0]\">".htmlspecialchars($row[1])." ".htmlspecialchars($row[2])." </option>\n";
            echo '</select>';
            echo '<input type="button" value="&lt;&lt;" onclick="Deplacer(this.form.elements[\'reg_multi_user_login[]\'],this.form.agent)"/>';
            echo '<input type="button" value="&gt;&gt;" onclick="Deplacer(this.form.agent,this.form.elements[\'reg_multi_user_login[]\'])"/>';
            echo '<select name="reg_multi_user_login[]" id="reg_multi_user_login" size="8" style="width:200px;" multiple="multiple" ondblclick="Deplacer(this.form.elements[\'reg_multi_user_login[]\'],this.form.agent)">';
            echo '<option> </option>';
            echo '</select>';
            echo '<input type="hidden" name="id_room" value="'.$id_room.'" />';
            echo '<input type="submit" value="Enregistrer"  onclick="selectionner_liste(this.form.reg_multi_user_login);"/>';
            echo '<script type="text/javascript">
                vider_liste(document.getElementById(\'reg_multi_user_login\'));
            </script>';
            echo '</form>';
        }
    }
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