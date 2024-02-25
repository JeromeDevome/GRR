<?php
/**
 * admin_admin_site.php
 * Interface de gestion des administrateurs de sites de l'application GRR
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2024-02-25 15:20$
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
$grr_script_name = "admin_admin_site.php";

include "../include/admin.inc.php";

$id_site = isset($_POST["id_site"]) ? $_POST["id_site"] : (isset($_GET["id_site"]) ? $_GET["id_site"] : NULL);
if (empty($id_site))
	$id_site = get_default_site();
if (!isset($id_site))
	settype($id_site, "integer");
else 
    $id_site = intval(clean_input($id_site));
$back = (isset($_SERVER['HTTP_REFERER']))? htmlspecialchars_decode($_SERVER['HTTP_REFERER'], ENT_QUOTES) : "./admin_accueil.php" ;
check_access(6, $back);
if (Settings::get("module_multisite") != "Oui")
{
	showAccessDenied($back);
	exit();
}

$reg_admin_login = isset($_POST["reg_admin_login"]) ? clean_input($_POST["reg_admin_login"]) : NULL;
$action = isset($_POST["action"]) ? clean_input($_POST["action"]) : (isset($_GET["action"]) ? clean_input($_GET["action"]) : NULL);
$msg = '';

if ($reg_admin_login)
{
	$res = grr_sql_query1("SELECT login FROM ".TABLE_PREFIX."_j_useradmin_site WHERE (login =? AND id_site =? )","si",[protect_data_sql($reg_admin_login),$id_site]);
	if ($res == -1)
	{
		$sql = "INSERT INTO ".TABLE_PREFIX."_j_useradmin_site (login, id_site) VALUES (?,?)";
		if (grr_sql_command($sql,"si",[protect_data_sql($reg_admin_login),$id_site]) < 0)
			fatal_error(1, "<p>" . grr_sql_error());
		else
            $msg = get_vocab("add_user_succeed");
	}
    else $msg = "échec de l'inscription";
}

if ($action)
{
	if ($action == "del_admin")
	{
		unset($login_admin);
		$login_admin = clean_input($_GET["login_admin"]);
		$sql = "DELETE FROM ".TABLE_PREFIX."_j_useradmin_site WHERE (login=? AND id_site =? )";
		if (grr_sql_command($sql,"si",[protect_data_sql($login_admin),$id_site]) < 0)
			fatal_error(1, "<p>" . grr_sql_error());
		else
			$msg = get_vocab("del_user_succeed");
	}
}
// sélecteur de site
$sel_site = "";
$sql = "SELECT id, sitename FROM ".TABLE_PREFIX."_site order by sitename";
$res = grr_sql_query($sql);
if(!$res)
    fatal_error(1,grr_sql_error());
else{
    $sel_site = "<form id=\"site\" action=\"admin_admin_site.php\" method=\"POST\">\n<div>";
    $sel_site .= "<label>".get_vocab('sites').get_vocab('deux_points');
    $sel_site .= "<select name=\"id_site\" onchange=\"site_go()\">\n";
    $sel_site .= "<option value=\"admin_admin_site.php?id_site=-1\">".get_vocab('select')."</option>";
	foreach($res as $row)
	{
		$selected = ($row['id'] == $id_site) ? "selected=\"selected\"" : "";
		$link = "admin_admin_site.php?id_site=".$row['id'];
		$sel_site .= "<option $selected value=\"$link\">" . htmlspecialchars($row['sitename'])."</option>";
	}
	$sel_site.= "</select></label>";
	$sel_site.= "<script type=\"text/javascript\" >
		<!--
		function site_go()
		{
			box = document.getElementById(\"site\").id_site;
			destination = box.options[box.selectedIndex].value;
			if (destination) location.href = destination;
		}
        -->
    </script>";
    $sel_site.="<noscript>
        <div><input type=\"submit\" value=\"Change\" /></div>
        </noscript>
        </form>";
}
$this_site_name = grr_sql_query1("SELECT sitename FROM ".TABLE_PREFIX."_site WHERE id=? ","i",[$id_site]);

// sélecteurs des utilisateurs
$auth_users = "";
$sql = "SELECT u.login, u.nom, u.prenom FROM ".TABLE_PREFIX."_utilisateurs u, ".TABLE_PREFIX."_j_useradmin_site j WHERE (j.id_site=? AND u.login=j.login) ORDER BY u.nom, u.prenom";
$res = grr_sql_query($sql,"i",[$id_site]);
if (!$res)
    fatal_error(1, grr_sql_error());
else{
    $nombre = grr_sql_count($res);
    if ($nombre == 0)
        $auth_users.= "<h3 class='avertissement'>".get_vocab("no_admin_this_site")."</h3>\n";
    else{
        $auth_users.= "<h3>".get_vocab("user_admin_site_list")."</h3>\n";    
        foreach($res as $row)
        {
            $auth_users.="<b>".htmlspecialchars($row['nom']);
            $auth_users.=" ".htmlspecialchars($row['prenom'])."</b> | ";
            $auth_users.="<a href='admin_admin_site.php?action=del_admin&amp;login_admin=".urlencode($row['login'])."&amp;id_site=$id_site'>".get_vocab("delete")."</a><br />\n";
        }
    }
}
grr_sql_free($res);

$add_one = "";
$sql = "SELECT login, nom, prenom FROM ".TABLE_PREFIX."_utilisateurs WHERE (etat!='inactif' AND (statut='utilisateur' OR statut='gestionnaire_utilisateur')) AND login NOT IN (SELECT login FROM ".TABLE_PREFIX."_j_useradmin_site WHERE id_site =? ) ORDER BY nom, prenom";
$res = grr_sql_query($sql,"i",[$id_site]);
if(!$res)
    fatal_error(1,grr_sql_error());
else{
    $add_one.= '<h3>'.get_vocab("add_user_to_list").'</h3>';
    $add_one.= '<form action="admin_admin_site.php" method="POST">';
    $add_one.= '<select size="1" name="reg_admin_login">';
    $add_one.= '<option value="">'.get_vocab("nobody").'</option>';
    foreach($res as $row){
        $add_one.= "<option value='".$row['login']."'>".htmlspecialchars($row['nom'])." ".htmlspecialchars($row['prenom'])." </option>\n";
    }
    $add_one.= '</select>';
    $add_one.= '<input type="hidden" name="id_site" value="'.$id_site.'" />';
    $add_one.= '<input type="submit" value="'.get_vocab('save').'" />';
    $add_one.= "</form>";
}
grr_sql_free($res);

# print the page header
start_page_w_header("", "", "", $type="with_session");
// Affichage de la colonne de gauche
include "admin_col_gauche2.php";
// colonne de droite
echo "<div class='col-md-9 col-sm-8 col-xs-12'>";
echo "<h2>".get_vocab('admin_admin_site.php')."</h2>";
echo "<p><i>".get_vocab("admin_admin_site_explain")."</i></p>";
// Affichage d'un pop-up
affiche_pop_up($msg,"admin");
# liste des sites
echo "<p>";
echo $sel_site;
echo "</p>";
# Ne pas continuer si aucun site n'est défini
if ($id_site <= 0)
{
	echo "<h1>".get_vocab("no_site")."</h1>";
}
else{
    echo "<div>";
    echo "<h3>".get_vocab("administration_site").get_vocab("deux_points")."</h3>";
    echo "<b>".$this_site_name."</b>";
    echo "</div>";
    echo "<div>";
    echo $auth_users;
    echo "</div>";
    echo "<div>";
    echo $add_one;
    echo "</div>";
}
// fin de l'affichage de la colonne de droite
echo "</div>";
// et de la page
end_page();
?>