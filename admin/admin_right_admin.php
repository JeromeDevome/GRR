<?php
/**
 * admin_right_admin.php
 * Interface de gestion des droits d'administration des domaines par les utilisateurs sélectionnés
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2021-09-18 11:15$
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

$back = (isset($_SERVER['HTTP_REFERER']))? htmlspecialchars_decode($_SERVER['HTTP_REFERER'], ENT_QUOTES) : "./admin_accueil.php" ;
check_access(5, $back); // ouvre le droit de modification aux administrateurs de site
$user = getUserName();
$multisite = Settings::get("module_multisite") == "Oui";

$id_area = isset($_POST["id_area"]) ? $_POST["id_area"] : (isset($_GET["id_area"]) ? $_GET["id_area"] : NULL);
if (isset($id_area))
	$id_area = intval($id_area);

$id_site = isset($_POST["id_site"]) ? $_POST["id_site"] : (isset($_GET["id_site"]) ? $_GET["id_site"] : NULL);
if (isset($id_site))
	$id_site = intval($id_site);
elseif (isset($id_area) && $multisite)
    $id_site = mrbsGetAreaSite($id_area);

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
// sites
if ($multisite)
{
	if (authGetUserLevel($user,-1,'area') >= 6)
		$sql = "SELECT id,sitecode,sitename FROM ".TABLE_PREFIX."_site ORDER BY sitename ASC";
	else
	{ // sites administrables
        $sql = "SELECT id,sitecode,sitename FROM ".TABLE_PREFIX."_site s 
        JOIN ".TABLE_PREFIX."_j_useradmin_site j 
        ON s.id = j.id_site
        WHERE j.login = $user
        ORDER BY sitename ASC";
    }
    $sites = grr_sql_query($sql);
}
// domaines
if ($multisite)
{
    if (isset($id_site))
        $sql = "SELECT id, area_name FROM ".TABLE_PREFIX."_area a 
        JOIN ".TABLE_PREFIX."_j_site_area j
        ON a.id = j.id_area
        WHERE j.id_site = $id_site
        ORDER BY order_display";
    else
        $sql ="";
}
else {
    $sql = "SELECT id, area_name FROM ".TABLE_PREFIX."_area ORDER BY order_display";
}
if ($sql != "")
    $areas = grr_sql_query($sql);
else 
    $areas = array();
// administrateurs existants
if($id_area >0){
    $sql = "SELECT ua.login, u.nom, u.prenom FROM ".TABLE_PREFIX."_j_useradmin_area ua LEFT JOIN ".TABLE_PREFIX."_utilisateurs u ON (u.login=ua.login) WHERE ua.id_area='$id_area' ORDER BY u.nom, u.prenom";
    $admins = grr_sql_query($sql);
    $exist_admin = grr_sql_count($admins)>0;
}
// utilisateurs sélectionnables
if($id_area>0){
    $sql = "SELECT u.login, u.nom, u.prenom FROM ".TABLE_PREFIX."_utilisateurs u
            WHERE (etat='actif') 
            AND (statut!='visiteur')
            AND u.login NOT IN (SELECT ua.login FROM ".TABLE_PREFIX."_j_useradmin_area ua WHERE ua.id_area=$id_area)
            ORDER BY u.nom, u.prenom";
    $res = grr_sql_query($sql);
    $selus = array();
    foreach($res as $u){
        if (authUserAccesArea($u['login'], $id_area) == 1)
            $selus[] = $u;
    }
}
// code HTML
//print the page header
start_page_w_header("", "", "", $type="with_session");
// Affichage d'un pop-up
affiche_pop_up($msg,"admin");
// Affichage de la colonne de gauche
include "admin_col_gauche2.php";
// colonne de droite
echo "<div class='col-md-9 col-sm-8 col-xs-12'>";
echo "<h2>".get_vocab('admin_right_admin.php')."</h2>\n";
echo "<p><i>".get_vocab("admin_right_admin_explain")."</i></p>\n";
// sélecteur de site si multisite
if ($multisite){
    $nb_site = grr_sql_count($sites);
    if ($nb_site > 1)
    {
        echo '<div >
        <label for="liste_site">'.get_vocab('sites').'</label>
        <form id="liste_site" name="liste_site" action="./admin_right_admin.php">
             <select name="id_site" onchange="site_go()">
               <option value="-1">'.get_vocab('choose_a_site').'</option>'."\n";
                foreach($sites as $s)
                {
                    echo '<option value="'.$s['id'].'"';
                    if ($id_site == $s['id'])
                        echo ' selected ';
                    echo '>'.htmlspecialchars($s['sitename']);
                    echo '</option>'."\n";
                }
        echo '</select>
        <script type="text/javascript">
        <!--
            function site_go()
            {
                box = document.getElementById("liste_site").id_site;
                destination = "./admin_right_admin.php"+"?id_site="+box.options[box.selectedIndex].value;
                location.href = destination;
            }
        // -->
        </script>
        </form>
        </div>
        <br />';
    }
    else
    { // un seul site accessible
        $row = grr_sql_row($sites, 0);
        echo '<p>
                <b>'.get_vocab('site').get_vocab('deux_points').$row[2].'</b>
            </p>
    <br />';
    $id_site = $row[0];
    }
    grr_sql_free($sites);
}
// sélecteur de domaine (en mode multisite, si le site est choisi)
if (!empty($areas)){
    echo "<label for='area'>".get_vocab("areas")."&nbsp;</label>";
    echo '<form id="area" name="area" action="./admin_right_admin.php">';
    echo "<select name=\"area\" onchange=\"area_go()\">\n";
    echo "<option value=\"admin_right_admin.php?id_area=-1\">".get_vocab('select')."</option>\n";
    foreach($areas as $a){
        $selected = ($a['id'] == $id_area) ? " selected " : "";
		echo "<option $selected value=\"".$a['id']."\">" . htmlspecialchars($a['area_name'])."</option>\n";
    }
    echo "</select>";
    grr_sql_free($areas);
}
echo '  
    <script type="text/javascript">
    <!--
        function area_go()
        {
            box = document.getElementById("area").area;
            destination = "./admin_right_admin.php"+"?id_area="+box.options[box.selectedIndex].value;
            location.href = destination;
        }
    // -->
    </script>
</form>';
// pas de domaine choisi ?
if ($id_area <= 0)
{
	echo "<h1>".get_vocab("no_area")."</h1>";
	// fin de l'affichage de la colonne de droite
	echo "</div></section></body></html>";
	exit;
}
// tableau des administrateurs
if($exist_admin){
    echo "<h3>".get_vocab("user_admin_area_list")."</h3>";
    echo "<table>".PHP_EOL;
    foreach($admins as $adm){
        echo '<tr>'.PHP_EOL;
        echo '<td><b>'.htmlspecialchars($adm['nom']." ".$adm['prenom'])."</b></td>";
        echo "<td>&nbsp;| <a href='admin_right_admin.php?action=del_admin&amp;login_admin=".urlencode($adm['login'])."&amp;id_area=$id_area'>".get_vocab("delete")."</a></td>".PHP_EOL;
        echo '</tr>'.PHP_EOL;
    }
    echo "</table>";
}
else{
    echo "<h3><span class=\"avertissement\">".get_vocab("no_admin_this_area")."</span></h3>";
}
// sélection d'un utilisateur
echo '<h3>'.get_vocab("add_user_to_list").'</h3>';
echo '<div>';
echo '<form action="admin_right_admin.php" method="post">';
echo '<select size="1" name="reg_admin_login">';
echo '<option value="">'.get_vocab("nobody").'</option>';
if($selus){
    foreach($selus as $u){
		echo "<option value='".$u['login']."'>".htmlspecialchars($u['nom']." ".$u['prenom'])."</option>";
    }
}
echo '</select>';
echo '<input type="hidden" name="id_area" value="'.$id_area.'" />';
echo '<input type="submit" value="Enregistrer" />';
echo '</form>';
echo '</div>';
// selection pour ajout en masse !-->
$nb_users = count($selus);
if ($nb_users > 0)
{
	echo '<div>';
	echo '<h3>'.get_vocab("add_multiple_user_to_list").get_vocab("deux_points").'</h3>';
	echo '<form action="admin_right_admin.php" method="post">';
	echo '<select name="agent" size="8" style="width:200px;" multiple="multiple" ondblclick="Deplacer(this.form.agent,this.form.elements[\'reg_multi_admin_login[]\'])">';
    foreach($selus as $u){
		echo "<option value='".$u['login']."'>".htmlspecialchars($u['nom']." ".$u['prenom'])."</option>";
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
    echo "</div>";
}
// fin de l'affichage de la colonne de droite
echo "</div>";
// et de la page
end_page();
?>