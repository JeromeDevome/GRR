<?php
/**
 * admin_access_area.php
 * Interface de gestion des accès aux domaines restreints
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2024-02-25 11:43$
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
$grr_script_name = "admin_access_area.php";

include "../include/admin.inc.php";

// fonction locale
function insereUser($user_id,$area_id){
	// On commence par vérifier que l'utilisateur n'est pas déjà présent dans cette liste.
    if ($area_id != -1)
    {
        $sql = "SELECT * FROM ".TABLE_PREFIX."_j_user_area WHERE (login =? and id_area =? )";
        $res = grr_sql_query($sql,"si",[$user_id,$area_id]);
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
                    $sql = "INSERT INTO ".TABLE_PREFIX."_j_user_area SET login=? , id_area =? ";
                    if (grr_sql_command($sql,"si",[$user_id,$area_id]) < 0)
                        fatal_error(1,"<p>".grr_sql_error());
                    else
                        $msg= get_vocab("add_user_succeed");
                }
            }
        }
    }
}

$id_area = isset($_POST["id_area"]) ? $_POST["id_area"] : (isset($_GET["id_area"]) ? $_GET["id_area"] : NULL);
$id_area = intval(clean_input($id_area));
$reg_user_login = isset($_POST["reg_user_login"]) ? $_POST["reg_user_login"] : NULL;
$reg_multi_user_login = isset($_POST["reg_multi_user_login"]) ? $_POST["reg_multi_user_login"] : NULL;
$test_user =  isset($_POST["reg_multi_user_login"]) ? "multi" : (isset($_POST["reg_user_login"]) ? "simple" : NULL);
$action = isset($_GET["action"]) ? $_GET["action"] : NULL;
$msg = '';

$back = (isset($_SERVER['HTTP_REFERER']))? htmlspecialchars_decode($_SERVER['HTTP_REFERER'], ENT_QUOTES) : "./admin_accueil.php" ;

check_access(4, $back);

if ($test_user == "multi")
	foreach ($reg_multi_user_login as $valeur){
        insereUser($valeur,$id_area);
	}
elseif($test_user == "simple")
{
    insereUser($reg_user_login,$id_area);
}

if ($action=='del_user')
{
	unset($login_user);
	$login_user = clean_input($_GET["login_user"]);
	$sql = "DELETE FROM ".TABLE_PREFIX."_j_user_area WHERE (login=? and id_area =? )";
	if (grr_sql_command($sql,"si",[$login_user, $id_area]) < 0)
		fatal_error(1, "<p>" . grr_sql_error());
	else
		$msg = get_vocab("del_user_succeed");
}

if (empty($id_area))
	$id_area = -1;

$this_area_name = grr_sql_query1("SELECT area_name FROM ".TABLE_PREFIX."_area WHERE id=? ","i",[$id_area]);
$existe_domaine = 'no';
$nb = 0;
// sélecteur de domaine
$sql = "SELECT id, area_name FROM ".TABLE_PREFIX."_area WHERE access='r' ORDER BY area_name";
$res = grr_sql_query($sql);
if(!$res)
    fatal_error(1,grr_sql_error());
else{
    $nb = grr_sql_count($res);
    $sel_dom = "\n<form id=\"area\" action=\"admin_access_area.php\" method=\"post\">\n";
    $sel_dom .= "<label>".get_vocab('areas');
    $sel_dom .= "<select name=\"area\" onchange=\"area_go()\">";
    $sel_dom .= "\n<option value=\"admin_access_area.php?id_area=-1\">".get_vocab('select')."</option>";
    foreach($res as $row)
	{
		$selected = ($row['id'] == $id_area) ? "selected = \"selected\"" : "";
		$link = "admin_access_area.php?id_area=".$row['id'];
		// on n'affiche que les domaines que l'utilisateur connecté a le droit d'administrer
		if (authGetUserLevel(getUserName(),$row['id'],'area') >= 4)
		{
			$sel_dom .= "\n<option $selected value=\"$link\">" . htmlspecialchars($row['area_name'])."</option>";
			$existe_domaine = 'yes';
		}
	}
	$sel_dom .= "</select>"."</label>";
    $sel_dom .= "<script  type=\"text/javascript\" >
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
}
// sélecteurs des utilisateurs
$auth_users = "";
$sql = "SELECT u.login, u.nom, u.prenom FROM ".TABLE_PREFIX."_utilisateurs u, ".TABLE_PREFIX."_j_user_area j WHERE (j.id_area=? AND u.login=j.login) ORDER BY u.nom, u.prenom";
$res = grr_sql_query($sql,"i",[$id_area]);
if (!$res)
    fatal_error(1, grr_sql_error());
else{
    $nombre = grr_sql_count($res);
    if ($nombre == 0)
        $auth_users.= "<h3 class='avertissement'>".get_vocab("no_user_area")."</h3>\n";
    else{
        $auth_users.= "<h3>".get_vocab("user_area_list")."</h3>\n";    
        foreach($res as $row)
        {
            $auth_users.="<b>".htmlspecialchars($row['nom']);
            $auth_users.=" ".htmlspecialchars($row['prenom'])."</b> | ";
            $auth_users.="<a href='admin_access_area.php?action=del_user&amp;login_user=".urlencode($row['login'])."&amp;id_area=$id_area'>".get_vocab("delete")."</a><br />\n";
        }
    }
}
grr_sql_free($res);

$add_one = "";
$add_multi = "";
$sql = "SELECT login, nom, prenom FROM ".TABLE_PREFIX."_utilisateurs WHERE (etat!='inactif' AND (statut='utilisateur' or statut='visiteur' OR statut='gestionnaire_utilisateur')) AND login NOT IN (SELECT login FROM ".TABLE_PREFIX."_j_user_area WHERE id_area =? ) ORDER BY nom, prenom";
$res = grr_sql_query($sql,"i",[$id_area]);
if(!$res)
    fatal_error(1,grr_sql_error());
else{
    $add_one.= '<h3>'.get_vocab("add_user_to_list").'</h3>'; // ajout simple
    $add_one.= '<form action="admin_access_area.php" method="post">';
    $add_one.= '<select size="1" name="reg_user_login">';
    $add_one.= '<option value="">'.get_vocab("nobody").'</option>';
    foreach($res as $row){
        $add_one.= "<option value='".$row['login']."'>".htmlspecialchars($row['nom'])." ".htmlspecialchars($row['prenom'])." </option>\n";
    }
    $add_one.= '</select>';
    $add_one.= '<input type="hidden" name="id_area" value="'.$id_area.'" />';
    $add_one.= '<input type="submit" value="'.get_vocab('save').'" />';
    $add_one.= "</form>";
    // ajout en masse
    $nb_users = grr_sql_count($res);
    if ($nb_users > 0)
    {
        $add_multi.= '<h3>'.get_vocab("add_multiple_user_to_list").get_vocab("deux_points").'</h3>';
		$add_multi.= '<form action="admin_access_area.php" method="post">';
		$add_multi.= '<select name="agent" size="8" style="width:200px;" multiple="multiple" ondblclick="Deplacer(this.form.agent,this.form.elements[\'reg_multi_user_login[]\'])">';
        foreach($res as $row)
            $add_multi.= "<option value='".$row['login']."'>".htmlspecialchars($row['nom'])." ".htmlspecialchars($row['prenom'])." </option>\n";
        $add_multi.= '</select>';
        $add_multi.= '<input type="button" value="&lt;&lt;" onclick="Deplacer(this.form.elements[\'reg_multi_user_login[]\'],this.form.agent)"/>';
        $add_multi.= '<input type="button" value="&gt;&gt;" onclick="Deplacer(this.form.agent,this.form.elements[\'reg_multi_user_login[]\'])"/>';
        $add_multi.= '<select name="reg_multi_user_login[]" id="reg_multi_user_login" size="8" style="width:200px;" multiple="multiple" ondblclick="Deplacer(this.form.elements[\'reg_multi_user_login[]\'],this.form.agent)">';
        $add_multi.= '<option> </option>';
        $add_multi.= '</select>';
        $add_multi.= '<input type="hidden" name="id_area" value="'.$id_area.'" />';
        $add_multi.= '<input type="submit" value="'.get_vocab('save').'"  onclick="selectionner_liste(this.form.reg_multi_user_login);"/>';
        $add_multi.= '<script type="text/javascript">
            vider_liste(document.getElementById(\'reg_multi_user_login\'));
        </script>';
        $add_multi.= '</form>';
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
echo "<h2>".get_vocab('admin_access_area.php')."</h2>\n";
affiche_pop_up($msg,"admin");
// sélecteur de domaine
if ($existe_domaine == 'yes')
	echo "<div>".$sel_dom."</div>";

// sélecteurs des utilisateurs
if ($id_area != -1)
{
	echo "<div>".$auth_users."</div>";
    echo "<div>".$add_one."</div>";
    echo "<div>".$add_multi."</div>";
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