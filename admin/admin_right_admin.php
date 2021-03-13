<?php
/**
 * admin_right_admin.php
 * Interface de gestion des droits d'administration des utilisateurs
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2021-03-13 11:42$
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
$grr_script_name = "admin_right_admin.php";

include "../include/admin.inc.php";

$id_area = isset($_POST["id_area"]) ? $_POST["id_area"] : (isset($_GET["id_area"]) ? $_GET["id_area"] : NULL);
if (!isset($id_area))
	settype($id_area,"integer");
$back = (isset($_SERVER['HTTP_REFERER']))? htmlspecialchars_decode($_SERVER['HTTP_REFERER'], ENT_QUOTES) : "./admin_accueil.php" ;
check_access(6, $back);

$reg_admin_login = isset($_POST["reg_admin_login"]) ? $_POST["reg_admin_login"] : NULL;
$reg_multi_admin_login = isset($_POST["reg_multi_admin_login"]) ? $_POST["reg_multi_admin_login"] : NULL;
$test_user =  isset($_POST["reg_multi_admin_login"]) ? "multi" : (isset($_POST["reg_admin_login"]) ? "simple" : NULL);
$action = isset($_GET["action"]) ? $_GET["action"] : NULL;
$msg = '';
if ($test_user == "multi")
{
	foreach ($reg_multi_admin_login as $valeur)
	{
	// On commence par vérifier que l'utilisateur n'est pas déjà présent dans cette liste.
		$res = grr_sql_query1("select login from ".TABLE_PREFIX."_j_useradmin_area where (login = '$valeur' and id_area = '$id_area')");
		if ($res == -1)
		{
			$sql = "insert into ".TABLE_PREFIX."_j_useradmin_area (login, id_area) values ('$valeur',$id_area)";
			if (grr_sql_command($sql) < 0)
				fatal_error(1, "<p>" . grr_sql_error());
			else
				$msg = get_vocab("add_multi_user_succeed");
		}
		else
			$msg = get_vocab("warning_exist");
	}
}
if ($test_user == "simple")
{
   // On commence par vérifier que l'utilisateur n'est pas déjà présent dans cette liste.
	if ($reg_admin_login)
	{
		$sql = "select login from ".TABLE_PREFIX."_j_useradmin_area where (login = '$reg_admin_login' and id_area = '$id_area')";
		$res = grr_sql_query1($sql);
		if ($res == -1)
		{
			$sql = "insert into ".TABLE_PREFIX."_j_useradmin_area (login, id_area) values ('$reg_admin_login',$id_area)";
			if (grr_sql_command($sql) < 0)
				fatal_error(1, "<p>" . grr_sql_error());
			else
				$msg = get_vocab("add_user_succeed");
		}
		else
			$msg = get_vocab("warning_exist");
	}
}
if ($action)
{
	if ($action == "del_admin")
	{
		unset($login_admin); $login_admin = $_GET["login_admin"];
		$sql = "DELETE FROM ".TABLE_PREFIX."_j_useradmin_area WHERE (login='$login_admin' and id_area = '$id_area')";
		if (grr_sql_command($sql) < 0)
			fatal_error(1, "<p>" . grr_sql_error());
		else
			$msg = get_vocab("del_user_succeed");
	}
}
// code HTML
//print the page header
start_page_w_header("", "", "", $type="with_session");
// Affichage de la colonne de gauche
include "admin_col_gauche2.php";
// colonne de droite
echo "<div class='col-md-9 col-sm-8 col-xs-12'>";
echo "<h2>".get_vocab('admin_right_admin.php')."</h2>\n";
echo "<p><i>".get_vocab("admin_right_admin_explain")."</i></p>\n";
// Affichage d'un pop-up
affiche_pop_up($msg,"admin");
//Show all areas
$this_area_name = "";
$out_html = "<form id=\"area\" action=\"admin_right_admin.php\" method=\"post\">";
$out_html .= "<label>".get_vocab("areas")."&nbsp;</label>";
$out_html .= "<select name=\"area\" onchange=\"area_go()\">\n";
$out_html .= "<option value=\"admin_right_admin.php?id_area=-1\">".get_vocab('select')."</option>\n";
$sql = "select id, area_name from ".TABLE_PREFIX."_area order by order_display";
$res = grr_sql_query($sql);
if ($res)
{
	for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
	{
		$selected = ($row[0] == $id_area) ? "selected=\"selected\"" : "";
		$link = "admin_right_admin.php?id_area=$row[0]";
		$out_html .= "<option $selected value=\"$link\">" . htmlspecialchars($row[1])."</option>\n";
	}
}
$out_html .= "</select>
<script type=\"text/javascript\" >
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
echo $out_html;
$this_area_name = grr_sql_query1("SELECT area_name FROM ".TABLE_PREFIX."_area WHERE id=$id_area");
if ($id_area <= 0)
{
	echo "<h1>".get_vocab("no_area")."</h1>";
	// fin de l'affichage de la colonne de droite
	echo "</div></section></body></html>";
	exit;
}
//Show area:
echo "<table class='table table-noborder'><tr>";
$is_admin = 'yes';
echo '<td>';
// modification proposée par darxmurf sur le forum le 16/10/2019
$exist_admin = 'no';
        $sql = "SELECT ua.login, u.nom, u.prenom FROM ".TABLE_PREFIX."_j_useradmin_area ua LEFT JOIN ".TABLE_PREFIX."_utilisateurs u ON (u.login=ua.login) WHERE ua.id_area='$id_area'";
        $res = grr_sql_query($sql);
        while ($row = mysqli_fetch_row($res)) {
          $is_admin='yes';
          if ($exist_admin == 'no'){
            echo "<h3>".get_vocab("user_admin_area_list")."</h3>";
            $exist_admin = 'yes';
          }
          echo "<b>";
          echo htmlspecialchars($row[1])." ".htmlspecialchars($row[2])."</b> | <a href='admin_right_admin.php?action=del_admin&amp;login_admin=".urlencode($row[0])."&amp;id_area=$id_area'>".get_vocab("delete")."</a><br />";
        }
// fin modification darxmurf
if ($exist_admin == 'no')
{
    echo "<h3><span class=\"avertissement\">".get_vocab("no_admin_this_area")."</span></h3>";
}
echo '</td></tr><tr><td>';
echo '<h3>'.get_vocab("add_user_to_list").'</h3>';
echo '<form action="admin_right_admin.php" method="post">';
echo '<select size="1" name="reg_admin_login">';
	echo '<option value="">'.get_vocab("nobody").'</option>';
    $sql = "SELECT distinct u.login, u.nom, u.prenom FROM ".TABLE_PREFIX."_utilisateurs u
    left join ".TABLE_PREFIX."_j_useradmin_area on ".TABLE_PREFIX."_j_useradmin_area.login=u.login
    WHERE ((etat!='inactif' and (statut='utilisateur' or statut='administrateur' or statut='gestionnaire_utilisateur'))
        AND (".TABLE_PREFIX."_j_useradmin_area.login is null or (".TABLE_PREFIX."_j_useradmin_area.login=u.login and ".TABLE_PREFIX."_j_useradmin_area.id_area!=".$id_area.")))  order by u.nom, u.prenom";
$res = grr_sql_query($sql);
if ($res)
{
	for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
	{
		if (authUserAccesArea($row[0], $id_area) == 1)
			echo "<option value='$row[0]'>".htmlspecialchars($row[1])." ".htmlspecialchars($row[2])."</option>";
	}
}
echo '</select>';
echo '<input type="hidden" name="id_area" value="'.$id_area.'" />';
echo '<input type="submit" value="Enregistrer" />';
echo '</form>';
echo '</td></tr>';
// selection pour ajout de masse !-->
$sql = "SELECT distinct u.login, u.nom, u.prenom FROM ".TABLE_PREFIX."_utilisateurs u
left join ".TABLE_PREFIX."_j_useradmin_area on ".TABLE_PREFIX."_j_useradmin_area.login=u.login
WHERE ((etat!='inactif' and (statut='utilisateur' or statut='administrateur' or statut='gestionnaire_utilisateur'))
	AND (".TABLE_PREFIX."_j_useradmin_area.login is null or (".TABLE_PREFIX."_j_useradmin_area.login=u.login and ".TABLE_PREFIX."_j_useradmin_area.id_area!=".$id_area.")))  order by u.nom, u.prenom";
$res = grr_sql_query($sql);
$nb_users = grr_sql_count($res);
if ($nb_users > 0)
{
	echo '<tr><td>';
	echo '<h3>'.get_vocab("add_multiple_user_to_list").get_vocab("deux_points").'</h3>';
	echo '<form action="admin_right_admin.php" method="post">';
	echo '<select name="agent" size="8" style="width:200px;" multiple="multiple" ondblclick="Deplacer(this.form.agent,this.form.elements[\'reg_multi_admin_login[]\'])">';
	if ($res)
        {
            for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
            {
                if (authUserAccesArea($row[0], $id_area) == 1)
                    echo "<option value='$row[0]'>".htmlspecialchars($row[1])." ".htmlspecialchars($row[2])."</option>";
            }
        }
    echo '</select>';
    echo '<input type="button" value="&lt;&lt;" onclick="Deplacer(this.form.elements[\'reg_multi_admin_login[]\'],this.form.agent)"/>';
    echo '<input type="button" value="&gt;&gt;" onclick="Deplacer(this.form.agent,this.form.elements[\'reg_multi_admin_login[]\'])"/>';
    echo '<select name="reg_multi_admin_login[]" id="reg_multi_admin_login" size="8" style="width:200px;" multiple="multiple" ondblclick="Deplacer(this.form.elements[\'reg_multi_admin_login[]\'],this.form.agent)">';
    echo '<option> </option>';
    echo '</select>';
    echo '<input type="hidden" name="id_area" value="'.$id_area.'" />';
    echo '<input type="submit" value="Enregistrer"  onclick="selectionner_liste(this.form.reg_multi_admin_login);" />';
    echo '<script type="text/javascript">';
    echo '    vider_liste(document.getElementById(\'reg_multi_admin_login\'));';
    echo '</script> </form>';
    echo "</td></tr>";
}
echo "</table>";
// fin de l'affichage de la colonne de droite
echo "</div>";
// et de la page
end_page();
?>
