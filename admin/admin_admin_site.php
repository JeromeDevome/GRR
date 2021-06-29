<?php
/**
 * admin_admin_site.php
 * Interface de gestion des administrateurs de sites de l'application GRR
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2021-03-13 12:09$
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

$reg_admin_login = isset($_GET["reg_admin_login"]) ? $_GET["reg_admin_login"] : NULL;
$reg_admin_login = clean_input($reg_admin_login);
$action = isset($_GET["action"]) ? $_GET["action"] : NULL;
$action = clean_input($action);
$msg = '';

if ($reg_admin_login)
{
	$res = grr_sql_query1("select login from ".TABLE_PREFIX."_j_useradmin_site where (login = '$reg_admin_login' and id_site = '$id_site')");
	if ($res == -1)
	{
		$sql = "insert into ".TABLE_PREFIX."_j_useradmin_site (login, id_site) values ('$reg_admin_login',$id_site)";
		if (grr_sql_command($sql) < 0)
			fatal_error(1, "<p>" . grr_sql_error());
		else
			$msg = get_vocab("add_user_succeed");
	}
}

if ($action)
{
	if ($action == "del_admin")
	{
		unset($login_admin);
		$login_admin = $_GET["login_admin"];
		$sql = "DELETE FROM ".TABLE_PREFIX."_j_useradmin_site WHERE (login='$login_admin' and id_site = '$id_site')";
		if (grr_sql_command($sql) < 0)
			fatal_error(1, "<p>" . grr_sql_error());
		else
			$msg = get_vocab("del_user_succeed");
	}
}
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
$this_site_name = "";
echo "<p>";
$out_html = "<form id=\"site\" action=\"admin_admin_site.php\" method=\"post\">\n<div>";
$out_html .= "<label>".get_vocab('sites').get_vocab('deux_points')."&nbsp; </label>";
$out_html .= "<select name=\"id_site\" onchange=\"site_go()\">\n";
$out_html .= "<option value=\"admin_admin_site.php?id_site=-1\">".get_vocab('select')."</option>";
$sql = "select id, sitename from ".TABLE_PREFIX."_site order by sitename";
$res = grr_sql_query($sql);
if ($res)
	for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
	{
		$selected = ($row[0] == $id_site) ? "selected=\"selected\"" : "";
		$link = "admin_admin_site.php?id_site=$row[0]";
		$out_html .= "<option $selected value=\"$link\">" . htmlspecialchars($row[1])."</option>";
	}
	$out_html .= "</select>
	<script type=\"text/javascript\" >
		<!--
		function site_go()
		{
			box = document.getElementById(\"site\").id_site;
			destination = box.options[box.selectedIndex].value;
			if (destination) location.href = destination;
		}
	-->
</script>
</div>
<noscript>
	<div><input type=\"submit\" value=\"Change\" /></div>
</noscript>
</form>";
echo $out_html;
$this_site_name = grr_sql_query1("select sitename from ".TABLE_PREFIX."_site where id=$id_site");
echo "</p>";
# Ne pas continuer si aucun site n'est défini
if ($id_site <= 0)
{
	echo "<h1>".get_vocab("no_site")."</h1>";
	// fin de l'affichage de la colonne de droite
	echo "</div></section></body></html>";
	exit;
}

echo "<table class='table-bordered'><tr><td>";
$is_admin = 'yes';
echo "<h3>".get_vocab("administration_site").get_vocab("deux_points")."</h3>";
echo "<b>".$this_site_name."</b>";
echo "</td><td>";
$exist_admin = 'no';
$sql = "select login, nom, prenom from ".TABLE_PREFIX."_utilisateurs where (statut='utilisateur' or statut='gestionnaire_utilisateur')";
$res = grr_sql_query($sql);
if ($res)
    for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
    {
        $is_admin = 'yes';
        $sql3 = "SELECT login FROM ".TABLE_PREFIX."_j_useradmin_site WHERE (id_site='".$id_site."' and login='".$row[0]."')";
        $res3 = grr_sql_query($sql3);
        $nombre = grr_sql_count($res3);
        if ($nombre == 0)
            $is_admin = 'no';
        if ($is_admin == 'yes')
        {
            if ($exist_admin == 'no')
            {
                echo "<h3>".get_vocab("user_admin_site_list").get_vocab("deux_points")."</h3>";
                $exist_admin='yes';
            }
            echo "<b>";
            echo "$row[1] $row[2]</b> | <a href='admin_admin_site.php?action=del_admin&amp;login_admin=".urlencode($row[0])."&amp;id_site=$id_site'>".get_vocab("delete")."</a><br />";
        }
    }
if ($exist_admin=='no')
    echo "<h3><span class=\"avertissement\">".get_vocab("no_admin_this_site")."</span></h3>";
echo '<h3>'.get_vocab("add_user_to_list").'</h3>';
echo '<form action="admin_admin_site.php" method="get">';
    echo '<select size="1" name="reg_admin_login">';
    echo '    <option value="">'.get_vocab("nobody").'</option>';
    $sql = "SELECT login, nom, prenom FROM ".TABLE_PREFIX."_utilisateurs WHERE  (etat!='inactif' and (statut='utilisateur' or statut='gestionnaire_utilisateur')) order by nom, prenom";
    $res = grr_sql_query($sql);
    if ($res)
        for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
            echo "<option value='$row[0]'>$row[1]  $row[2] </option>";
    echo '</select>';
    echo '<input type="hidden" name="id_site" value="'.$id_site.'"/>';
    echo '<input type="submit" value="Enregistrer" />';
    echo '</form>';
echo '</td></tr></table>';
// fin de l'affichage de la colonne de droite
echo "</div>";
// et de la page
end_page();
?>
