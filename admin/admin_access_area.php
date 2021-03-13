<?php
/**
 * admin_access_area.php
 * Interface de gestion des accès restreints aux domaines
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2021-03-13 12:10$
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
$grr_script_name = "admin_access_area.php";

include "../include/admin.inc.php";

$id_area = isset($_POST["id_area"]) ? $_POST["id_area"] : (isset($_GET["id_area"]) ? $_GET["id_area"] : NULL);
if (!isset($id_area))
	settype($id_area,"integer");
else 
    $id_area = intval(clean_input($id_area));
$reg_user_login = isset($_POST["reg_user_login"]) ? $_POST["reg_user_login"] : NULL;
$reg_multi_user_login = isset($_POST["reg_multi_user_login"]) ? $_POST["reg_multi_user_login"] : NULL;
$test_user =  isset($_POST["reg_multi_user_login"]) ? "multi" : (isset($_POST["reg_user_login"]) ? "simple" : NULL);
$action = isset($_GET["action"]) ? $_GET["action"] : NULL;
$msg = '';

$back = (isset($_SERVER['HTTP_REFERER']))? htmlspecialchars_decode($_SERVER['HTTP_REFERER'], ENT_QUOTES) : "./admin_accueil.php" ;

check_access(4, $back);

// Si la table j_user_area est vide, il faut modifier la requête
$test_grr_j_user_area = grr_sql_count(grr_sql_query("SELECT * from ".TABLE_PREFIX."_j_user_area"));
// la requête qui précède semble inutile
if ($test_user == "multi")
{
	foreach ($reg_multi_user_login as $valeur)
	{
	// On commence par vérifier que l'utilisateur n'est pas déjà présent dans cette liste.
		if ($id_area != -1)
		{
			if (authGetUserLevel(getUserName(), $id_area, 'area') < 4)
			{
				showAccessDenied($back);
				exit();
			}
			$sql = "SELECT * FROM ".TABLE_PREFIX."_j_user_area WHERE (login = '".$valeur."' and id_area = '$id_area')";
			$res = grr_sql_query($sql);
			$test = grr_sql_count($res);
			if ($test > 0)
				$msg = get_vocab("warning_exist");
			else
			{
				if ($valeur != '')
				{
					$sql = "INSERT INTO ".TABLE_PREFIX."_j_user_area SET login= '$valeur', id_area = '$id_area'";
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
   // On commence par vérifier que l'utilisateur n'est pas déjà présent dans cette liste.
	if ($id_area != -1)
	{
		if (authGetUserLevel(getUserName(), $id_area, 'area') < 4)
		{
			showAccessDenied($back);
			exit();
		}
		$sql = "SELECT * FROM ".TABLE_PREFIX."_j_user_area WHERE (login = '$reg_user_login' and id_area = '$id_area')";
		$res = grr_sql_query($sql);
		$test = grr_sql_count($res);
		if ($test > 0)
			$msg = get_vocab("warning_exist");
		else
		{
			if ($reg_user_login != '')
			{
				$sql = "INSERT INTO ".TABLE_PREFIX."_j_user_area SET login= '$reg_user_login', id_area = '$id_area'";
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
	if (authGetUserLevel(getUserName(), $id_area, 'area') < 4)
	{
		showAccessDenied($back);
		exit();
	}
	unset($login_user);
	$login_user = $_GET["login_user"];
	$sql = "DELETE FROM ".TABLE_PREFIX."_j_user_area WHERE (login='$login_user' and id_area = '$id_area')";
	if (grr_sql_command($sql) < 0)
		fatal_error(1, "<p>" . grr_sql_error());
	else
		$msg = get_vocab("del_user_succeed");
}

if (empty($id_area))
	$id_area = -1;
// code HTML
# print the page header
start_page_w_header("", "", "", $type="with_session");
// Affichage de la colonne de gauche
include "admin_col_gauche2.php";
// colonne de droite
echo "<div class='col-md-9 col-sm-8 col-xs-12'>";
echo "<h2>".get_vocab('admin_access_area.php')."</h2>\n";
affiche_pop_up($msg,"admin");
// echo "<table><tr>\n";
# Show all areas
$this_area_name = "";
$existe_domaine = 'no';
// echo "<td ><p><b>".get_vocab('areas')."</b></p>\n";
$out_html = "\n<form id=\"area\" action=\"admin_access_area.php\" method=\"post\">\n";
$out_html .= "<label>".get_vocab('areas')."</label>";
$out_html .= "<select name=\"area\" onchange=\"area_go()\">";
$out_html .= "\n<option value=\"admin_access_area.php?id_area=-1\">".get_vocab('select')."</option>";
$sql = "select id, area_name from ".TABLE_PREFIX."_area where access='r' order by area_name";
$res = grr_sql_query($sql);
$nb = grr_sql_count($res);
if ($res)
	for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
	{
		$selected = ($row[0] == $id_area) ? "selected = \"selected\"" : "";
		$link = "admin_access_area.php?id_area=$row[0]";
		// on affiche que les domaines que l'utilisateur connecté a le droit d'administrer
		if (authGetUserLevel(getUserName(),$row[0],'area') >= 4)
		{
			$out_html .= "\n<option $selected value=\"$link\">" . htmlspecialchars($row[1])."</option>";
			$existe_domaine = 'yes';
		}
	}
	$out_html .= "</select>
	<script  type=\"text/javascript\" >
		<!--
		function area_go()
		{
			box = document.getElementById('area').area;
			destination = box.options[box.selectedIndex].value;
			if (destination) location.href = destination;
		}
	// -->
	</script>
	<noscript>
		<div><input type=\"submit\" value=\"Change\" /></div>
	</noscript>
</form>";
if ($existe_domaine == 'yes')
	echo $out_html;
$this_area_name = grr_sql_query1("select area_name from ".TABLE_PREFIX."_area where id=$id_area");
//echo "</td>\n";
//echo "</tr></table>\n";
# Show area :
if ($id_area != -1)
{
	echo "<table class='table-noborder'><tr><td>";
	$sql = "SELECT u.login, u.nom, u.prenom FROM ".TABLE_PREFIX."_utilisateurs u, ".TABLE_PREFIX."_j_user_area j WHERE (j.id_area='$id_area' and u.login=j.login)  order by u.nom, u.prenom";
	$res = grr_sql_query($sql);
    if ($res)
        $nombre = grr_sql_count($res);
    else grr_sql_error($res);
	if ($nombre != 0)
		echo "<h3>".get_vocab("user_area_list")."</h3>\n";
	if ($res)
		for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
		{
			$login_user = $row[0];
			$nom_admin = htmlspecialchars($row[1]);
			$prenom_admin = htmlspecialchars($row[2]);
			echo "<b>";
			echo "$nom_admin $prenom_admin</b> | <a href='admin_access_area.php?action=del_user&amp;login_user=".urlencode($login_user)."&amp;id_area=$id_area'>".get_vocab("delete")."</a><br />\n";
		}
    if ($nombre == 0)
        echo "<h3 class='avertissement'>".get_vocab("no_user_area")."</h3>\n";
    echo '<h3>'.get_vocab("add_user_to_list").'</h3>';
	echo '	<form action="admin_access_area.php" method="post">';
	echo '		<select size="1" name="reg_user_login">';
	echo '			<option value="">'.get_vocab("nobody").'</option>';
        // Pour mysql >= 4.1
        $sql = "SELECT login, nom, prenom FROM ".TABLE_PREFIX."_utilisateurs WHERE (etat!='inactif' and (statut='utilisateur' or statut='visiteur' or statut='gestionnaire_utilisateur')) AND login NOT IN (SELECT login FROM ".TABLE_PREFIX."_j_user_area WHERE id_area = '$id_area') order by nom, prenom";
        // Pour mysql < 4.1
        $sql = "SELECT DISTINCT u.login, u.nom, u.prenom FROM ".TABLE_PREFIX."_utilisateurs u left join ".TABLE_PREFIX."_j_user_area on ".TABLE_PREFIX."_j_user_area.login=u.login WHERE ((etat!='inactif' and (statut='utilisateur' or statut='visiteur' or statut='gestionnaire_utilisateur')) AND (".TABLE_PREFIX."_j_user_area.login is null or (".TABLE_PREFIX."_j_user_area.login=u.login and ".TABLE_PREFIX."_j_user_area.id_area!=".$id_area.")))  order by u.nom, u.prenom";
        $res = grr_sql_query($sql);
        if ($res)
            for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
                echo "<option value=\"$row[0]\">".htmlspecialchars($row[1])." ".htmlspecialchars($row[2])." </option>\n";
	echo '		</select>';
	echo '		<input type="hidden" name="id_area" value="'.$id_area.'" />';
	echo '		<input type="submit" value="Enregistrer" />';
	echo '	</form>';
	echo '</td></tr>';
// sélection pour ajout de masse !-->
	// Pour mysql >= 4.1
    $sql = "SELECT login, nom, prenom FROM ".TABLE_PREFIX."_utilisateurs WHERE (etat!='inactif' and (statut='utilisateur' or statut='visiteur' or statut='gestionnaire_utilisateur')) AND login NOT IN (SELECT login FROM ".TABLE_PREFIX."_j_user_area WHERE id_area = '$id_area') order by nom, prenom";
    // Pour mysql < 4.1
    $sql = "SELECT DISTINCT u.login, u.nom, u.prenom FROM ".TABLE_PREFIX."_utilisateurs u left join ".TABLE_PREFIX."_j_user_area on ".TABLE_PREFIX."_j_user_area.login=u.login WHERE ((etat!='inactif' and (statut='utilisateur' or statut='visiteur' or statut='gestionnaire_utilisateur')) AND (".TABLE_PREFIX."_j_user_area.login is null or (".TABLE_PREFIX."_j_user_area.login=u.login and ".TABLE_PREFIX."_j_user_area.id_area!=".$id_area.")))  order by u.nom, u.prenom";
    $res = grr_sql_query($sql);
    $nb_users = grr_sql_count($res);
    if ($nb_users > 0)
    {
		echo '<tr><td>';
		echo '<h3>'.get_vocab("add_multiple_user_to_list").get_vocab("deux_points").'</h3>';
		echo '<form action="admin_access_area.php" method="post">';
		echo '<select name="agent" size="8" style="width:200px;" multiple="multiple" ondblclick="Deplacer(this.form.agent,this.form.elements[\'reg_multi_user_login[]\'])">';
        if ($res)
            for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
                echo "<option value=\"$row[0]\">".htmlspecialchars($row[1])." ".htmlspecialchars($row[2])." </option>\n";
        echo '</select>';
        echo '<input type="button" value="&lt;&lt;" onclick="Deplacer(this.form.elements[\'reg_multi_user_login[]\'],this.form.agent)"/>';
        echo '<input type="button" value="&gt;&gt;" onclick="Deplacer(this.form.agent,this.form.elements[\'reg_multi_user_login[]\'])"/>';
        echo '<select name="reg_multi_user_login[]" id="reg_multi_user_login" size="8" style="width:200px;" multiple="multiple" ondblclick="Deplacer(this.form.elements[\'reg_multi_user_login[]\'],this.form.agent)">';
        echo '<option> </option>';
        echo '</select>';
        echo '<input type="hidden" name="id_area" value="'.$id_area.'" />';
        echo '<input type="submit" value="Enregistrer"  onclick="selectionner_liste(this.form.reg_multi_user_login);"/>';
        echo '<script type="text/javascript">
            vider_liste(document.getElementById(\'reg_multi_user_login\'));
        </script>';
        echo '</form>';
        echo "</td></tr>";
    }
    echo "</table>";
}
else
{
    if (($nb =0) || ($existe_domaine != 'yes'))
        echo "<h3>".get_vocab("no_restricted_area")."</h3>";
    else
        echo "<h3>".get_vocab("no_area")."</h3>";
}
// fin de la colonne de droite
echo "</div>";
// et de la page 
end_page();
?>
