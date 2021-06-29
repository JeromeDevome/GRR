<?php
/**
 * admin_room.php
 * Interface d'accueil de Gestion des domaines et ressources de l'application GRR
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2021-03-13 11:42$
 * @author    JeromeB & Laurent Delineau & Marc-Henri PAMISEUX & Yan Naessens
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
$grr_script_name = "admin_room.php";

include "../include/admin.inc.php";

$id_area = isset($_POST["id_area"]) ? $_POST["id_area"] : (isset($_GET["id_area"]) ? $_GET["id_area"] : NULL);
if ((isset($id_area))&&($id_area != -1))
{
	settype($id_area,"integer");
	$id_site = mrbsGetAreaSite($id_area);
}
if (!isset($id_site))
	$id_site = isset($_POST['id_site']) ? $_POST['id_site'] : (isset($_GET['id_site']) ? $_GET['id_site'] : -1);
settype($id_site,"integer");
$back = (isset($_SERVER['HTTP_REFERER']))? htmlspecialchars_decode($_SERVER['HTTP_REFERER'], ENT_QUOTES) : "./admin_accueil.php" ;
check_access(4, $back);
// If area is set but area name is not known, get the name.
if ((isset($id_area)) && ($id_area != -1))
{
	if (empty($area_name))
	{
		$res = grr_sql_query("SELECT area_name, access FROM ".TABLE_PREFIX."_area WHERE id=$id_area");
		if (!$res)
			fatal_error(0, grr_sql_error());
		if (grr_sql_count($res) == 1)
		{
			$row = grr_sql_row($res, 0);
			$area_name = $row[0];
		}
		else
			$area_name='';
		grr_sql_free($res);
	}
	else
		$area_name = unslashes($area_name);
}
else
	$area_name='';
// code HTML
//print the page header
start_page_w_header("", "", "", $type="with_session");
// Afffichage d'un éventuel message
if (isset($_GET['msg']))
{
	$msg = $_GET['msg'];
	affiche_pop_up($msg,"admin");
}
// Affichage de la colonne de gauche
include "admin_col_gauche2.php";
// Affichage de la colonne de droite
echo "<div class='col-md-9 col-sm-8 col-xs-12'>";
echo "<h2>".get_vocab("admin_room.php")."</h2>";

if (Settings::get("module_multisite") == "Oui")
{
	if (authGetUserLevel(getUserName(),-1,'area') >= 6)
		$sql = "SELECT id,sitecode,sitename FROM ".TABLE_PREFIX."_site ORDER BY sitename ASC";
	else
	{
		// Administrateur de sites ou de domaines
		$sql = "SELECT DISTINCT id,sitecode,sitename FROM ".TABLE_PREFIX."_site s ";
		// l'utilisateur est-il administrateur d'un site ?
		$test1 = grr_sql_query1("SELECT count(login) FROM ".TABLE_PREFIX."_j_useradmin_site WHERE login='".getUserName()."'");
		if ($test1 > 0)
			$sql .=", ".TABLE_PREFIX."_j_useradmin_site u";
		// l'utilisateur est-il administrateur d'un domaine ?
		$test2 = grr_sql_query1("SELECT count(login) FROM ".TABLE_PREFIX."_j_useradmin_area WHERE login='".getUserName()."'");
		if ($test2 > 0)
			$sql .=", ".TABLE_PREFIX."_j_useradmin_area a, ".TABLE_PREFIX."_j_site_area j";
		$sql .=" WHERE (";
			if ($test1 > 0)
				$sql .= "(s.id=u.id_site AND u.login='".getUserName()."') ";
			if (($test1 > 0) && ($test2 > 0))
				$sql .= " or ";
			if ($test2 > 0)
				$sql .= "(j.id_site=s.id AND j.id_area=a.id_area AND a.login='".getUserName()."')";
			$sql .= ") ORDER BY s.sitename ASC";
    }
    $res = grr_sql_query($sql);
    $nb_site = grr_sql_count($res);
    if ($nb_site > 1)
    {
        echo '<table class="table-bordered">
        <tr>
            <th>
                <b>'.get_vocab('sites').'</b>
            </th>
        </tr>
        <tr>
            <td>
                <form id="liste_site" action="'.$_SERVER['PHP_SELF'].'">
                    <div><select name="id_site" onchange="site_go()">
                        <option value="-1">'.get_vocab('choose_a_site').'</option>'."\n";
                        for ($enr = 0; ($row = grr_sql_row($res, $enr)); $enr++)
                        {
                            echo '            <option value="'.$row[0].'"';
                            if ($id_site == $row[0])
                                echo ' selected="selected"';
                            echo '>'.htmlspecialchars($row[2]);
                            echo '            </option>'."\n";
                        }
                        grr_sql_free($res);
                        echo '          </select></div>
                        <script type="text/javascript">
						<!--
                            function site_go()
                            {
                                box = document.getElementById("liste_site").id_site;
                                destination = "'.$_SERVER['PHP_SELF'].'"+"?id_site="+box.options[box.selectedIndex].value;
                                location.href = destination;
                            }
                        // -->
                        </script>
                        <noscript>
                            <div><input type="submit" value="change" /></div>
                        </noscript>
                </form>
            </td>
        </tr>
    </table>
	<br />';
    }
    else
    {
	// un seul site
        $row = grr_sql_row($res, 0);
        echo '<table class="table">
        <tr>
            <th>
                <b>'.get_vocab('site').get_vocab('deux_points').$row[2].'</b>
            </th>
        </tr>
        </table>
    <br />';
    $id_site = $row[0];
    }
}
echo '<table class="table table-bordered">';
echo '<tr>';
    echo '<th  style="text-align:center; width:50%;"><b class="titre">'.get_vocab('areas').'</b></th>';
	echo '<th  style="text-align:center; width:50%;"><b class="titre">'.get_vocab('rooms');
    if ((isset($id_area)) && ($id_area != -1)) { echo " ".get_vocab('in')." ".htmlspecialchars($area_name); }
    echo '</b></th>';
echo '</tr>';
		
		// Seul l'administrateur a le droit d'ajouter des domaines
		if (authGetUserLevel(getUserName(),-1,'area') >= 5)
		{
			if ((Settings::get("module_multisite") == "Oui") && ($id_site <= 0))
				echo "<tr><td>".get_vocab('choose_a_site')."</td>"."\n";
			else
                if ($id_area != -1)
                    echo "<tr><td><a href=\"admin_edit_room.php?id_site=".$id_site."&amp;add_area='yes'\">".get_vocab('addarea')."</a></td>";
                else 
                    echo "<tr><td> </td>";
		}
		else
		{
			if ((Settings::get("module_multisite") == "Oui") && ($id_site <= 0))
				echo "<tr><td>".get_vocab('choose_a_site')."</td>"."\n";
			else
				echo "<tr><td> </td>";
		}
		if ((isset($id_area))&&($id_area != -1))
			echo "<td><a href=\"admin_edit_room.php?id_site=".$id_site."&amp;area_id=$id_area\">".get_vocab('addroom')."</a></td></tr>";
		else
			echo "<td> </td></tr>";
		// Pas de site selectionné donc pas de domaine, et encore moins de ressources.
		if ((Settings::get("module_multisite") == "Oui") && ($id_site <= 0))
		{
			echo "</table>\n";
		// fin de l'affichage de la colonne de droite et fin de la page
			echo "</div>\n</section>\n</body>\n</html>";
			die();
		}
		// A partir de ce niveau, on sait qu'il existe un site
		if ((Settings::get("module_multisite") == "Oui") && ($id_site > 0))
			$sql="SELECT ".TABLE_PREFIX."_area.id,".TABLE_PREFIX."_area.area_name,".TABLE_PREFIX."_area.access
		FROM ".TABLE_PREFIX."_j_site_area,".TABLE_PREFIX."_area
		WHERE ".TABLE_PREFIX."_j_site_area.id_site='".$id_site."'
		AND ".TABLE_PREFIX."_area.id=".TABLE_PREFIX."_j_site_area.id_area
		ORDER BY order_display";
		else
			$sql="select id, area_name, access from ".TABLE_PREFIX."_area order by order_display";
		$res = grr_sql_query($sql);
		if (!$res)
			fatal_error(0, grr_sql_error());
		if (grr_sql_count($res) != 0)
		{
            // on détermine les domaines accessibles à l'utilisateur -> rangés dans $tareas
            $tareas = array();
            for ($i = 0; ($row = grr_sql_row($res, $i)); $i++){
                if ((authGetUserLevel(getUserName(),$row[0],'area') >= 4)){
                    $tareas[] = $row ;
                }
            }
            // cas où le domaine n'est pas choisi ou UN domaine est sélectionné
            if (!isset($id_area) ||($id_area != -1)){
                echo "<tr><td>\n";
                echo "<table class=\"table\">\n";
                foreach($tareas as $row)
                {
                    echo "<tr>";
                    if ($row[2] == 'r') // domaine restreint ?
                        echo "<td><a href='admin_access_area.php?id_area=$row[0]' title='".get_vocab('admin_access_area.php')."'><span class='glyphicon glyphicon-lock'></span></a></td>\n";
                    else
                        echo "<td> </td>\n";
                    if (isset($id_area) && ($id_area == $row[0])) // domaine sélectionné ?
                    {
                        echo "<td><span class=\"bground\"><b>&gt;&gt;&gt; ".htmlspecialchars($row[1])." &lt;&lt;&lt; </b></span>";
                    }
                    else
                    {
                        echo "<td><a href=\"admin_room.php?id_site=".$id_site."&amp;id_area=$row[0]\">".htmlspecialchars($row[1])."</a> ";
                    }
                    echo "</td>\n";
                    echo "<td><a href=\"admin_edit_room.php?id_area=$row[0]\"><span class='glyphicon glyphicon-edit'></span></a></td>\n";
                    if (authGetUserLevel(getUserName(),$row[0],'area') >= 5)
                    {
                        echo "<td><a href=\"admin_edit_room.php?id_area=$row[0]&amp;action=duplique_area\"><img src=\"../img_grr/duplique.png\" alt=\"".get_vocab('duplique_domaine')."\" title=\"".get_vocab('duplique_domaine')."\" class=\"image\" /></a></td>\n";
                        echo "<td><a href=\"admin_room_del.php?id_site=".$id_site."&amp;type=area&amp;id_area=$row[0]\"><span class='glyphicon glyphicon-trash'></span></a></td>\n";
                    }
                    echo "<td><a href=\"admin_type_area.php?id_area=$row[0]\"><img src=\"../img_grr/type.png\" alt=\"".get_vocab('edittype')."\" title=\"".get_vocab('edittype')."\" class=\"image\" /></a></td>\n";
                    echo "<td><a href='javascript:centrerpopup(\"../view_rights_area.php?area_id=$row[0]\",600,480,\"scrollbars=yes,statusbar=no,resizable=yes\")' title=\"".get_vocab("privileges")."\">
                    <img src=\"../img_grr/rights.png\" alt=\"".get_vocab("privileges")."\" class=\"image\" /></a></td>";
                    echo "</tr>\n";
                }
                echo "</table>";
                echo "</td>";
                echo "<td>";
                if (!isset($id_area)){ // cas où le domaine n'est pas choisi
                    echo get_vocab('noarea');
                    echo "<br />".get_vocab("OU")."<br />";
                    echo "<a href='admin_room.php?id_site=".$id_site."&amp id_area=-1'>".get_vocab('show_all_rooms');
                    echo "</a>";
                }
                else { // cas où UN domaine est choisi, on affiche toutes les ressources de ce domaine
                    $sql = "SELECT id, room_name, description, capacity, max_booking, statut_room from ".TABLE_PREFIX."_room where area_id=$id_area ";
                    // on ne cherche pas parmi les ressources invisibles pour l'utilisateur
                    $tab_rooms_noaccess = verif_acces_ressource(getUserName(), 'all');
                    foreach ($tab_rooms_noaccess as $key){
                        $sql .= " and id != $key ";
                    }
                    $sql .= "order by order_display, room_name";
                    $res = grr_sql_query($sql);
                    if (!$res)
                        fatal_error(0, grr_sql_error());
                    if (grr_sql_count($res) != 0){
                        echo "<table class='table'>";
                        for ($i = 0; ($row = grr_sql_row($res, $i)); $i++){
                            $color = '';
                            if ($row[5] == "0")
                                $color =  " class=\"texte_ress_tempo_indispo\"";
                            echo "<tr><td ".$color.">" . htmlspecialchars($row[1]) . "<i> - " . htmlspecialchars($row[2]);
                            if ($row[3] > 0)
                                echo " ($row[3] max.)";
                            echo "</i></td>\n<td><a href=\"admin_edit_room.php?room=$row[0]\"><span class='glyphicon glyphicon-edit'></span></a></td>\n";
                            echo "<td><a href=\"admin_edit_room.php?room=$row[0]&amp;action=duplique_room\"><img src=\"../img_grr/duplique.png\" alt=\"".get_vocab('duplique_ressource')."\" title=\"".get_vocab('duplique_ressource')."\" class=\"image\" /></a></td>";
                            echo "<td><a href=\"admin_room_del.php?type=room&amp;room=$row[0]&amp;id_area=$id_area\"><span class='glyphicon glyphicon-trash'></span></a></td>";
                            echo "<td><a href='javascript:centrerpopup(\"../view_rights_room.php?id_room=$row[0]\",600,480,\"scrollbars=yes,statusbar=no,resizable=yes\")' title=\"".get_vocab("privileges")."\"><img src=\"../img_grr/rights.png\" alt=\"".get_vocab("privileges")."\" class=\"image\" /></a></td>";
                            echo "<td><a href='javascript:centrerpopup(\"../view_room.php?id_room=$row[0]\",600,480,\"scrollbars=yes,statusbar=no,resizable=yes\")' title=\"".get_vocab("fiche_ressource")."\"><img src=\"../img_grr/details_s.png\" alt=\"d&eacute;tails\" class=\"image\" /></a></td>";
                            echo "</tr>\n";
                        }
                        echo "</table>";
                    }  
                    else echo get_vocab("no_rooms_for_area");
                }
                echo "</td></tr>";
            }
            // cas où il faut afficher toutes les ressources de tous les domaines
            if ($id_area == -1){
                foreach($tareas as $row)
                {
                    echo "<tr><td><table class='table'><tr>";
                    if ($row[2] == 'r') // domaine restreint ?
                        echo "<td><a href='admin_access_area.php?id_area=$row[0]' title='".get_vocab('admin_access_area.php')."'><span class='glyphicon glyphicon-lock'></span></a></td>\n";
                    else
                        echo "<td> </td>\n";
                    echo "<td><a href=\"admin_room.php?id_site=".$id_site."&amp;id_area=$row[0]\">".htmlspecialchars($row[1])."</a> ";
                    echo "</td>\n";
                    echo "<td><a href=\"admin_edit_room.php?id_area=$row[0]\"><span class='glyphicon glyphicon-edit'></span></a></td>\n";
                    if (authGetUserLevel(getUserName(),$row[0],'area') >= 5)
                    {
                        echo "<td><a href=\"admin_edit_room.php?id_area=$row[0]&action=duplique_area\"><img src=\"../img_grr/duplique.png\" alt=\"".get_vocab('duplique_domaine')."\" title=\"".get_vocab('duplique_domaine')."\" class=\"image\" /></a></td>\n";
                        echo "<td><a href=\"admin_room_del.php?id_site=".$id_site."&amp;type=area&amp;id_area=$row[0]\"><span class='glyphicon glyphicon-trash'></span></a></td>\n";
                    }
                    echo "<td><a href=\"admin_type_area.php?id_area=$row[0]\"><img src=\"../img_grr/type.png\" alt=\"".get_vocab('edittype')."\" title=\"".get_vocab('edittype')."\" class=\"image\" /></a></td>\n";
                    echo "<td><a href='javascript:centrerpopup(\"../view_rights_area.php?area_id=$row[0]\",600,480,\"scrollbars=yes,statusbar=no,resizable=yes\")' title=\"".get_vocab("privileges")."\">
                    <img src=\"../img_grr/rights.png\" alt=\"".get_vocab("privileges")."\" class=\"image\" /></a></td>";
                    echo "</tr></table></td>"; // fin affichage domaine
                    echo "<td>";
                    // echo "affichage des ressources";
                    $sql = "SELECT id, room_name, description, capacity, max_booking, statut_room from ".TABLE_PREFIX."_room where area_id=$row[0] ";
                    // on ne cherche pas parmi les ressources invisibles pour l'utilisateur
                    $tab_rooms_noaccess = verif_acces_ressource(getUserName(), 'all');
                    foreach ($tab_rooms_noaccess as $key){
                        $sql .= " and id != $key ";
                    }
                    $sql .= "order by order_display, room_name";
                    $res = grr_sql_query($sql);
                    if (!$res)
                        fatal_error(0, grr_sql_error());
                    if (grr_sql_count($res) != 0){
                        echo "<table class='table'>";
                        for ($i = 0; ($row = grr_sql_row($res, $i)); $i++){
                            $color = '';
                            if ($row[5] == "0")
                                $color =  " class=\"texte_ress_tempo_indispo\"";
                            echo "<tr><td ".$color.">" . htmlspecialchars($row[1]) . "<i> - " . htmlspecialchars($row[2]);
                            if ($row[3] > 0)
                                echo " ($row[3] max.)";
                            echo "</i></td>\n<td><a href=\"admin_edit_room.php?room=$row[0]\"><span class='glyphicon glyphicon-edit'></span></a></td>\n";
                            echo "<td><a href=\"admin_edit_room.php?room=$row[0]&amp;action=duplique_room\"><img src=\"../img_grr/duplique.png\" alt=\"".get_vocab('duplique_ressource')."\" title=\"".get_vocab('duplique_ressource')."\" class=\"image\" /></a></td>";
                            echo "<td><a href=\"admin_room_del.php?type=room&amp;room=$row[0]&amp;id_area=$id_area\"><span class='glyphicon glyphicon-trash'></span></a></td>";
                            echo "<td><a href='javascript:centrerpopup(\"../view_rights_room.php?id_room=$row[0]\",600,480,\"scrollbars=yes,statusbar=no,resizable=yes\")' title=\"".get_vocab("privileges")."\"><img src=\"../img_grr/rights.png\" alt=\"".get_vocab("privileges")."\" class=\"image\" /></a></td>";
                            echo "<td><a href='javascript:centrerpopup(\"../view_room.php?id_room=$row[0]\",600,480,\"scrollbars=yes,statusbar=no,resizable=yes\")' title=\"".get_vocab("fiche_ressource")."\"><img src=\"../img_grr/details_s.png\" alt=\"d&eacute;tails\" class=\"image\" /></a></td>";
                            echo "</tr>\n";
                        }
                        echo "</table>";
                    }  
                    else echo get_vocab("no_rooms_for_area");
                    echo "</td></tr>";
                }                
            }
        }
	echo  "</table>\n";
	// fin de l'affichage de la colonne de droite
echo "</div>";
// et de la page 
end_page();
?>
