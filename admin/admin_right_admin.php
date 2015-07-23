<?php
/**
 * admin_right_admin.php
 * Interface de gestion des droits d'administration des utilisateurs
 * Dernière modification : $Date: 2010-04-07 15:38:14 $
 * @author    Laurent Delineau <laurent.delineau@ac-poitiers.fr>
 * @copyright Copyright 2003-2008 Laurent Delineau
 * @link      http://www.gnu.org/licenses/licenses.html
 * @package   admin
 * @version   $Id: admin_right_admin.php,v 1.12 2010-04-07 15:38:14 grr Exp $
 * @filesource
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GRR is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GRR; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */
/**
 * $Log: admin_right_admin.php,v $
 * Revision 1.12  2010-04-07 15:38:14  grr
 * *** empty log message ***
 *
 * Revision 1.11  2009-04-14 12:59:17  grr
 * *** empty log message ***
 *
 * Revision 1.10  2009-04-09 14:52:31  grr
 * *** empty log message ***
 *
 * Revision 1.9  2009-02-27 13:28:19  grr
 * *** empty log message ***
 *
 *
 */
include "../include/admin.inc.php";
$grr_script_name = "admin_right_admin.php";
$id_area = isset($_POST["id_area"]) ? $_POST["id_area"] : (isset($_GET["id_area"]) ? $_GET["id_area"] : NULL);
if (!isset($id_area))
	settype($id_area,"integer");
$back = '';
if (isset($_SERVER['HTTP_REFERER']))
	$back = htmlspecialchars($_SERVER['HTTP_REFERER']);
$day   = date("d");
$month = date("m");
$year  = date("Y");
check_access(6, $back);
//print the page header
print_header("", "", "", $type="with_session");
// Affichage de la colonne de gauche
include "admin_col_gauche.php";
$reg_admin_login = isset($_POST["reg_admin_login"]) ? $_POST["reg_admin_login"] : NULL;
$reg_multi_admin_login = isset($_POST["reg_multi_admin_login"]) ? $_POST["reg_multi_admin_login"] : NULL;
$test_user =  isset($_POST["reg_multi_admin_login"]) ? "multi" : (isset($_POST["reg_admin_login"]) ? "simple" : NULL);
$action = isset($_GET["action"]) ? $_GET["action"] : NULL;
$msg = '';
if ($test_user == "multi")
{
	foreach ($reg_multi_admin_login as $valeur)
	{
	// On commence par vérifier que le professeur n'est pas déjà présent dans cette liste.
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
   // On commence par vérifier que le professeur n'est pas déjà présent dans cette liste.
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
echo "<h2>".get_vocab('admin_right_admin.php')."</h2>\n";
echo "<p><i>".get_vocab("admin_right_admin_explain")."</i></p>\n";
// Affichage d'un pop-up
affiche_pop_up($msg,"admin");
//Table with areas.
echo "<table><tr>\n";
$this_area_name = "";
//Show all areas
echo "<td ><p><b>".get_vocab("areas")."</b></p>\n";
$out_html = "<form id=\"area\" action=\"admin_right_admin.php\" method=\"post\"><div><select name=\"area\" onchange=\"area_go()\">\n";
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
</div>
<noscript>
	<div><input type=\"submit\" value=\"Change\" /></div>
</noscript>
</form>";
echo $out_html;
$this_area_name = grr_sql_query1("SELECT area_name FROM ".TABLE_PREFIX."_area WHERE id=$id_area");
echo "</td>\n";
echo "</tr></table>\n";
if ($id_area <= 0)
{
	echo "<h1>".get_vocab("no_area")."</h1>";
	// fin de l'affichage de la colonne de droite
	echo "</td></tr></table></body></html>";
	exit;
}
//Show area:
echo "<table border=\"1\" cellpadding=\"5\"><tr>";
$is_admin = 'yes';
?>
<td>
	<?php
	$exist_admin='no';
	$sql = "SELECT login, nom, prenom FROM ".TABLE_PREFIX."_utilisateurs WHERE (statut='utilisateur' OR statut='gestionnaire_utilisateur' OR statut='administrateur')";
	$res = grr_sql_query($sql);
	if ($res) for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
	{
		$is_admin='yes';
		$sql3 = "SELECT login FROM ".TABLE_PREFIX."_j_useradmin_area WHERE (id_area='".$id_area."' AND login='".$row[0]."')";
		$res3 = grr_sql_query($sql3);
		$nombre = grr_sql_count($res3);
		if ($nombre == 0)
			$is_admin = 'no';
		if ($is_admin == 'yes')
		{
			if ($exist_admin == 'no')
			{
				echo "<h3>".get_vocab("user_admin_area_list")."</h3>";
				$exist_admin = 'yes';
			}
			echo "<b>";
			echo htmlspecialchars($row[1])." ".htmlspecialchars($row[2])."</b> | <a href='admin_right_admin.php?action=del_admin&amp;login_admin=".urlencode($row[0])."&amp;id_area=$id_area'>".get_vocab("delete")."</a><br />";
		}
	}
	if ($exist_admin == 'no')
	{
		echo "<h3><span class=\"avertissement\">".get_vocab("no_admin_this_area")."</span></h3>";
	}
	?>
	<h3><?php echo get_vocab("add_user_to_list");?></h3>
	<form action="admin_right_admin.php" method='post'>
		<div><select size="1" name="reg_admin_login">
			<option value=''><?php echo get_vocab("nobody"); ?></option>
			<?php
			$sql = "SELECT u.login, u.nom, u.prenom FROM ".TABLE_PREFIX."_utilisateurs u
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
?>
</select>
<input type="hidden" name="id_area" value="<?php echo $id_area;?>" />
<input type="submit" value="Enregistrer" />
</div></form>
</td></tr>
<!-- selection pour ajout de masse !-->
<?php
$sql = "SELECT u.login, u.nom, u.prenom FROM ".TABLE_PREFIX."_utilisateurs u
left join ".TABLE_PREFIX."_j_useradmin_area on ".TABLE_PREFIX."_j_useradmin_area.login=u.login
WHERE ((etat!='inactif' and (statut='utilisateur' or statut='administrateur' or statut='gestionnaire_utilisateur'))
	AND (".TABLE_PREFIX."_j_useradmin_area.login is null or (".TABLE_PREFIX."_j_useradmin_area.login=u.login and ".TABLE_PREFIX."_j_useradmin_area.id_area!=".$id_area.")))  order by u.nom, u.prenom";
$res = grr_sql_query($sql);
$nb_users = grr_sql_count($res);
if ($nb_users > 0)
{
	?>
	<tr><td>
		<h3><?php echo get_vocab("add_multiple_user_to_list").get_vocab("deux_points");?></h3>
		<form action="admin_right_admin.php" method='post'>
			<div><select name="agent" size="8" style="width:200px;" multiple="multiple" ondblclick="Deplacer(this.form.agent,this.form.elements['reg_multi_admin_login[]'])">
				<?php
				if ($res)
				{
					for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
					{
						if (authUserAccesArea($row[0], $id_area) == 1)
							echo "<option value='$row[0]'>".htmlspecialchars($row[1])." ".htmlspecialchars($row[2])."</option>";
					}
				}
				?>
			</select>
			<input type="button" value="&lt;&lt;" onclick="Deplacer(this.form.elements['reg_multi_admin_login[]'],this.form.agent)"/>
			<input type="button" value="&gt;&gt;" onclick="Deplacer(this.form.agent,this.form.elements['reg_multi_admin_login[]'])"/>
			<select name="reg_multi_admin_login[]" id="reg_multi_admin_login" size="8" style="width:200px;" multiple="multiple" ondblclick="Deplacer(this.form.elements['reg_multi_admin_login[]'],this.form.agent)">
				<option> </option>
			</select>
			<input type="hidden" name="id_area" value="<?php echo $id_area;?>" />
			<input type="submit" value="Enregistrer"  onclick="selectionner_liste(this.form.reg_multi_admin_login);"/></div>
			<script type="text/javascript">
				vider_liste(document.getElementById('reg_multi_admin_login'));
			</script> </form>
			<?php
			echo "</td></tr>";
		}
		echo "</table>";
// fin de l'affichage de la colonne de droite
		echo "</td></tr></table>";
		?>
	</body>
	</html>