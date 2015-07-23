<?php
/**
 * admin_access_area.php
 * Interface de gestion des accès restreints aux domaines
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2009-12-16 14:52:31 $
 * @author    Laurent Delineau <laurent.delineau@ac-poitiers.fr>
 * @copyright Copyright 2003-2008 Laurent Delineau
 * @link      http://www.gnu.org/licenses/licenses.html
 * @package   root
 * @version   $Id: admin_access_area.php,v 1.10 2009-12-16 14:52:31 grr Exp $
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

include "../include/admin.inc.php";
$grr_script_name = "admin_access_area.php";

$id_area = isset($_POST["id_area"]) ? $_POST["id_area"] : (isset($_GET["id_area"]) ? $_GET["id_area"] : NULL);
if (!isset($id_area))
	settype($id_area,"integer");
$reg_user_login = isset($_POST["reg_user_login"]) ? $_POST["reg_user_login"] : NULL;
$reg_multi_user_login = isset($_POST["reg_multi_user_login"]) ? $_POST["reg_multi_user_login"] : NULL;
$test_user =  isset($_POST["reg_multi_user_login"]) ? "multi" : (isset($_POST["reg_user_login"]) ? "simple" : NULL);
$action = isset($_GET["action"]) ? $_GET["action"] : NULL;
$msg = '';


$back = '';
if (isset($_SERVER['HTTP_REFERER']))
	$back = htmlspecialchars($_SERVER['HTTP_REFERER']);
$day   = date("d");
$month = date("m");
$year  = date("Y");
check_access(4, $back);
# print the page header
print_header("", "", "", $type="with_session");
// Affichage de la colonne de gauche
include "admin_col_gauche.php";

// Si la table j_user_area est vide, il faut modifier la requête
$test_grr_j_user_area = grr_sql_count(grr_sql_query("SELECT * from ".TABLE_PREFIX."_j_user_area"));

if ($test_user == "multi")
{
	foreach ($reg_multi_user_login as $valeur)
	{
	// On commence par vérifier que le professeur n'est pas déjà présent dans cette liste.
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
   // On commence par vérifier que le professeur n'est pas déjà présent dans cette liste.
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
echo "<h2>".get_vocab('admin_access_area.php')."</h2>\n";
affiche_pop_up($msg,"admin");
echo "<table><tr>\n";
$this_area_name = "";
# Show all areas
$existe_domaine = 'no';
echo "<td ><p><b>".get_vocab('areas')."</b></p>\n";
$out_html = "\n<form id=\"area\" action=\"admin_access_area.php\" method=\"post\">\n<div><select name=\"area\" onchange=\"area_go()\">";
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
	$out_html .= "</select></div>
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
echo "</td>\n";
echo "</tr></table>\n";
# Show area :
if ($id_area != -1)
{
	echo "<table border=\"1\" cellpadding=\"5\"><tr><td>";
	$sql = "SELECT u.login, u.nom, u.prenom FROM ".TABLE_PREFIX."_utilisateurs u, ".TABLE_PREFIX."_j_user_area j WHERE (j.id_area='$id_area' and u.login=j.login)  order by u.nom, u.prenom";
	$res = grr_sql_query($sql);
	$nombre = grr_sql_count($res);
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
		?>
		<h3><?php echo get_vocab("add_user_to_list"); ?></h3>
		<form action="admin_access_area.php" method='post'>
			<div><select size="1" name="reg_user_login">
				<option value=''><?php echo get_vocab("nobody"); ?></option>
				<?php
				// Pour mysql >= 4.1
				$sql = "SELECT login, nom, prenom FROM ".TABLE_PREFIX."_utilisateurs WHERE (etat!='inactif' and (statut='utilisateur' or statut='visiteur' or statut='gestionnaire_utilisateur')) AND login NOT IN (SELECT login FROM ".TABLE_PREFIX."_j_user_area WHERE id_area = '$id_area') order by nom, prenom";
				// Pour mysql < 4.1
				$sql = "SELECT DISTINCT u.login, u.nom, u.prenom FROM ".TABLE_PREFIX."_utilisateurs u left join ".TABLE_PREFIX."_j_user_area on ".TABLE_PREFIX."_j_user_area.login=u.login WHERE ((etat!='inactif' and (statut='utilisateur' or statut='visiteur' or statut='gestionnaire_utilisateur')) AND (".TABLE_PREFIX."_j_user_area.login is null or (".TABLE_PREFIX."_j_user_area.login=u.login and ".TABLE_PREFIX."_j_user_area.id_area!=".$id_area.")))  order by u.nom, u.prenom";
				$res = grr_sql_query($sql);
				if ($res)
					for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
						echo "<option value=\"$row[0]\">".htmlspecialchars($row[1])." ".htmlspecialchars($row[2])." </option>\n";
					?>
				</select>
				<input type="hidden" name="id_area" value="<?php echo $id_area;?>" />
				<input type="submit" value="Enregistrer" /></div>
			</form>
		</td></tr>
		<!-- selection pour ajout de masse !-->
		<?php
		// Pour mysql >= 4.1
		$sql = "SELECT login, nom, prenom FROM ".TABLE_PREFIX."_utilisateurs WHERE (etat!='inactif' and (statut='utilisateur' or statut='visiteur' or statut='gestionnaire_utilisateur')) AND login NOT IN (SELECT login FROM ".TABLE_PREFIX."_j_user_area WHERE id_area = '$id_area') order by nom, prenom";
		// Pour mysql < 4.1
		$sql = "SELECT DISTINCT u.login, u.nom, u.prenom FROM ".TABLE_PREFIX."_utilisateurs u left join ".TABLE_PREFIX."_j_user_area on ".TABLE_PREFIX."_j_user_area.login=u.login WHERE ((etat!='inactif' and (statut='utilisateur' or statut='visiteur' or statut='gestionnaire_utilisateur')) AND (".TABLE_PREFIX."_j_user_area.login is null or (".TABLE_PREFIX."_j_user_area.login=u.login and ".TABLE_PREFIX."_j_user_area.id_area!=".$id_area.")))  order by u.nom, u.prenom";
		$res = grr_sql_query($sql);
		$nb_users = grr_sql_count($res);
		if ($nb_users > 0)
		{
			?>
			<tr><td>
				<h3><?php echo get_vocab("add_multiple_user_to_list").get_vocab("deux_points"); ?></h3>
				<form action="admin_access_area.php" method='post'>
					<div><select name="agent" size="8" style="width:200px;" multiple="multiple" ondblclick="Deplacer(this.form.agent,this.form.elements['reg_multi_user_login[]'])">
						<?php
						if ($res)
							for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
								echo "<option value=\"$row[0]\">".htmlspecialchars($row[1])." ".htmlspecialchars($row[2])." </option>\n";
							?>
						</select>
						<input type="button" value="&lt;&lt;" onclick="Deplacer(this.form.elements['reg_multi_user_login[]'],this.form.agent)"/>
						<input type="button" value="&gt;&gt;" onclick="Deplacer(this.form.agent,this.form.elements['reg_multi_user_login[]'])"/>
						<select name="reg_multi_user_login[]" id="reg_multi_user_login" size="8" style="width:200px;" multiple="multiple" ondblclick="Deplacer(this.form.elements['reg_multi_user_login[]'],this.form.agent)">
							<option> </option>
						</select>
						<input type="hidden" name="id_area" value="<?php echo $id_area; ?>" />
						<input type="submit" value="Enregistrer"  onclick="selectionner_liste(this.form.reg_multi_user_login);"/></div>
						<script type="text/javascript">
							vider_liste(document.getElementById('reg_multi_user_login'));
						</script> </form>
						<?php
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
				echo "</td></tr>";
				echo "</table>";
				?>
			</body>
			</html>
