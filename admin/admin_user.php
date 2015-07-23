<?php
/**
 * admin_user.php
 * interface de gestion des utilisateurs de l'application GRR
 * Dernière modification : $Date: 2009-09-29 18:02:56 $
 * @author    Laurent Delineau <laurent.delineau@ac-poitiers.fr>
 * @copyright Copyright 2003-2008 Laurent Delineau
 * @link      http://www.gnu.org/licenses/licenses.html
 * @package   admin
 * @version   $Id: admin_user.php,v 1.11 2009-09-29 18:02:56 grr Exp $
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
$grr_script_name = "admin_user.php";
$back = '';
if (isset($_SERVER['HTTP_REFERER']))
	$back = htmlspecialchars($_SERVER['HTTP_REFERER']);
$display = isset($_GET["display"]) ? $_GET["display"] : NULL;
$order_by = isset($_GET["order_by"]) ? $_GET["order_by"] : NULL;
$msg = '';
if ((authGetUserLevel(getUserName(), -1) < 6) && (authGetUserLevel(getUserName(), -1,'user') != 1))
{
	showAccessDenied($back);
	exit();
}
if ((isset($_GET['action_del'])) && ($_GET['js_confirmed'] == 1))
{
	VerifyModeDemo();
}
print_header("", "", "", $type="with_session");
include "admin_col_gauche.php";
// Enregistrement de allow_users_modify_profil
// Un gestionnaire d'utilisateurs ne peut pas Autoriser ou non la modification par un utilisateur de ses informations personnelles
if ((isset($_GET['action'])) && ($_GET['action'] == "modif_profil") && (authGetUserLevel(getUserName(), -1, 'user') !=  1))
{
	if (!Settings::set("allow_users_modify_profil", $_GET['allow_users_modify_profil']))
		$msg = get_vocab("message_records_error");
	else
		$msg = get_vocab("message_records");
}
// Enregistrement de allow_users_modify_email
// Un gestionnaire d'utilisateurs ne peut pas Autoriser ou non la modification par un utilisateur de ses informations personnelles
if ((isset($_GET['action'])) && ($_GET['action'] == "modif_email") && (authGetUserLevel(getUserName(), -1, 'user') !=  1))
{
	if (!Settings::set("allow_users_modify_email", $_GET['allow_users_modify_email']))
		$msg = get_vocab("message_records_error");
	else
		$msg = get_vocab("message_records");
}
// Enregistrement de allow_users_modify_mdp
// Un gestionnaire d'utilisateurs ne peut pas Autoriser ou non la modification par un utilisateur de son mot de passe
if ((isset($_GET['action'])) && ($_GET['action'] == "modif_mdp") && (authGetUserLevel(getUserName(), -1, 'user') !=  1))
{
	if (!Settings::set("allow_users_modify_mdp", $_GET['allow_users_modify_mdp']))
		$msg = get_vocab("message_records_error");
	else
		$msg = get_vocab("message_records");
}
// Nettoyage de la base locale
// On propose de supprimer les utilisateurs ext de GRR qui ne sont plus présents dans la base LCS
if ((isset($_GET['action'])) && ($_GET['action'] == "nettoyage") && (Settings::get("sso_statut") == "lcs"))
{
	// Sélection des utilisateurs non locaux
	$sql = "SELECT login, etat, source FROM ".TABLE_PREFIX."_utilisateurs where source='ext'";
	$res = grr_sql_query($sql);
	if ($res) {
		include LCS_PAGE_AUTH_INC_PHP;
		include LCS_PAGE_LDAP_INC_PHP;
		for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
		{
			$user_login = $row[0];
			$user_etat[$i] = $row[1];
			$user_source = $row[2];
			list($user, $groups) = people_get_variables($user_login, false);
			$flag = 1;
			if ($user["uid"] == "")
			{
				if ($flag == 1)
					$msg = get_vocab("mess2_maj_base_locale");
				$flag = 0;
				// L'utilisateur n'est plus présent dans la base LCS, on le supprime
				// Etablir à nouveau la connexion à la base
				if (empty($db_nopersist))
					$db_c = mysqli_connect('p:'.$dbHost, $dbUser, $dbPass, $dbPort);
				else
					$db_c = mysqli_connect($dbHost, $dbUser, $dbPass, $dbPort);
				if (!$db_c || !mysqli_select_db($db_c, $dbDb))
					echo "\n<p>\n" . get_vocab('failed_connect_db') . "\n";
				$sql = "DELETE FROM ".TABLE_PREFIX."_utilisateurs WHERE login='".$user_login."'";
				if (grr_sql_command($sql) < 0)
					fatal_error(1, "<p>" . grr_sql_error());
				else
				{
					grr_sql_command("DELETE FROM ".TABLE_PREFIX."_j_mailuser_room WHERE login='".$user_login."'");
					grr_sql_command("DELETE FROM ".TABLE_PREFIX."_j_user_area WHERE login='".$user_login."'");
					grr_sql_command("DELETE FROM ".TABLE_PREFIX."_j_user_room WHERE login='".$user_login."'");
					grr_sql_command("DELETE FROM ".TABLE_PREFIX."_j_useradmin_area WHERE login='".$user_login."'");
					grr_sql_command("DELETE FROM ".TABLE_PREFIX."_j_useradmin_site WHERE login='".$user_login."'");
					$msg .= "\\n".$user_login;
				}
			}
		}
		if ($flag == 1)
			$msg = get_vocab("mess3_maj_base_locale");
	}
}
// Nettoyage de la base locale
// On propose de supprimer les utilisateurs ext de GRR qui ne sont plus présents dans la base LCS
if ((isset($_GET['action'])) && ($_GET['action'] == "synchro") && (Settings::get("sso_statut") == "lcs"))
{
	$statut_eleve = Settings::get("lcs_statut_eleve");
	$statut_non_eleve = Settings::get("lcs_statut_prof");
	include LCS_PAGE_AUTH_INC_PHP;
	include LCS_PAGE_LDAP_INC_PHP;
	$users = search_people("(cn=*)");
	$total_user = count($users);
	$liste_nouveaux = "";
	$liste_pb_insertion = "";
	$liste_update = "";
	$liste_pb_update = "";
	// Etablir à nouveau la connexion à la base
	if (empty($db_nopersist))
		$db_c = mysqli_connect('p:'.$dbHost, $dbUser, $dbPass, $dbPort);
	else
		$db_c = mysqli_connect($dbHost, $dbUser, $dbPass, $dbPort);
	if (!$db_c || !mysqli_select_db ($db_c, $dbDb))
		echo "\n<p>\n" . get_vocab('failed_connect_db') . "\n";
	for ($loop=0; $loop < $total_user; $loop++ )
	{
		$user_login = $users[$loop]["uid"];
		list($user, $groups) = people_get_variables($user_login, true);
		$user_nom = $user["nom"];
		$user_fullname = $user["fullname"];
		$user_email = $user["email"];
		$long = strlen($user_fullname) - strlen($user_nom);
		$user_prenom = substr($user_fullname, 0, $long) ;
		if (is_eleve($user_login))
			$user_statut = $statut_eleve;
		else
			$user_statut = $statut_non_eleve;
		$groupe = "";
		for ($loop2 = 0; $loop2 < count($groups); $loop2++ )
		{
			if (($groups[$loop2]["cn"] == "Profs") || ($groups[$loop2]["cn"] == "Administratifs") || ($groups[$loop2]["cn"] == "Eleves"))
				$groupe .= $groups[$loop2]["cn"].", ";
		}
		if ($groupe == "")
			$groupe = "vide";
		$test = grr_sql_query1("SELECT count(login) FROM ".TABLE_PREFIX."_utilisateurs WHERE login = '".$user_login."'");
		if ($test == 0)
		{
			// On insert le nouvel utilisteur
			$sql = "INSERT INTO ".TABLE_PREFIX."_utilisateurs SET
			nom='".protect_data_sql($user_nom)."',
			prenom='".protect_data_sql($user_prenom)."',
			statut='".protect_data_sql($user_statut)."',
			email='".protect_data_sql($user_email)."',
			source='ext',
			etat='actif',
			login='".protect_data_sql($user_login)."'";
			if (grr_sql_command($sql) < 0)
				$liste_pb_insertion .= $user_login." (".$user_prenom." ".$user_nom.")<br />";
			else
				$liste_nouveaux .= $user_login." (".$user_prenom." ".$user_nom.")<br />";
		}
		else
		{
			$test2 = grr_sql_query1("SELECT source FROM ".TABLE_PREFIX."_utilisateurs WHERE login = '".$user_login."'");
			if ($test2 == 'ext')
			{
				// On met à jour
				$sql = "UPDATE ".TABLE_PREFIX."_utilisateurs SET
				nom='".protect_data_sql($user_nom)."',
				prenom='".protect_data_sql($user_prenom)."',
				email='".protect_data_sql($user_email)."'
				where login='".protect_data_sql($user_login)."'";
			}
			if (grr_sql_command($sql) < 0)
				$liste_pb_update .= $user_login." (".$user_prenom." ".$user_nom.")<br />";
			else
				$liste_update .= $user_login." (".$user_prenom." ".$user_nom.")<br />";
		}
	//echo "login : ".$user_login." Nom : ".$user_nom." Prénom : ".$user_prenom." Email : ".$user_email." Etat : ".$etat." Groupes : ".$groupe;
	//echo "<br />";
	}
	$mess = "";
	if ($liste_pb_insertion != "")
		$mess .= "<b><span class=\"avertissement\">".get_vocab("liste_pb_insertion")."</b><br />".$liste_pb_insertion."</span><br />";
	if ($liste_pb_update != "")
		$mess .= "<b><font class=\"avertissement\">".get_vocab("liste_pb_update")."</b><br />".$liste_pb_update."</span><br />";
	if ($liste_nouveaux != "")
		$mess .= "<b>".get_vocab("liste_nouveaux_utilisateurs")."</b><br />".$liste_nouveaux."<br />";
	if ($liste_update != "")
		$mess .= "<b>".get_vocab("liste_utilisateurs_modifie")."</b><br />".$liste_update."<br />";
}
//
// Supression d'un utilisateur
//
if ((isset($_GET['action_del'])) and ($_GET['js_confirmed'] == 1))
{
	$temp = $_GET['user_del'];
	// un gestionnaire d'utilisateurs ne peut pas supprimer un administrateur général ou un gestionnaire d'utilisateurs
	$can_delete = "yes";
	if (authGetUserLevel(getUserName(), -1,'user') ==  1)
	{
		$test_statut = grr_sql_query1("SELECT statut FROM ".TABLE_PREFIX."_utilisateurs WHERE login='".$_GET['user_del']."'");
		if (($test_statut == "gestionnaire_utilisateur") || ($test_statut == "administrateur"))
			$can_delete = "no";
	}
	if (($temp != getUserName()) && ($can_delete == "yes"))
	{
		$temp = str_replace('\\', '\\\\', $temp);
		$sql = "DELETE FROM ".TABLE_PREFIX."_utilisateurs WHERE login='$temp'";
		if (grr_sql_command($sql) < 0)
		{
			fatal_error(1, "<p>" . grr_sql_error());
		}
		else
		{
			grr_sql_command("DELETE FROM ".TABLE_PREFIX."_j_mailuser_room WHERE login='$temp'");
			grr_sql_command("DELETE FROM ".TABLE_PREFIX."_j_user_area WHERE login='$temp'");
			grr_sql_command("DELETE FROM ".TABLE_PREFIX."_j_user_room WHERE login='$temp'");
			grr_sql_command("DELETE FROM ".TABLE_PREFIX."_j_useradmin_area WHERE login='$temp'");
			grr_sql_command("DELETE FROM ".TABLE_PREFIX."_j_useradmin_site WHERE login='$temp'");
			$msg=get_vocab("del_user_succeed");
		}
	}
}
if (isset($mess) and ($mess != ""))
	echo "<p>".$mess."</p>";
echo "<h2>".get_vocab('admin_user.php')."</h2>";
if (empty($display))
{
	$display = 'actifs';
}
if (empty($order_by))
{
	$order_by = 'nom,prenom';
}
?>
| <a href="admin_user_modify.php?display=<?php echo $display; ?>"><?php echo get_vocab("display_add_user"); ?></a> |
<a href="admin_import_users_csv.php"><?php echo get_vocab("display_add_user_list_csv"); ?></a> |
<?php
// On propose de supprimer les utilisateurs ext de GRR qui ne sont plus présents dans la base LCS
if (Settings::get("sso_statut") == "lcs")
{
	echo "<br />Opérations LCS : | <a href=\"admin_user.php?action=nettoyage\" onclick=\"return confirmlink(this, '".AddSlashes(get_vocab("mess_maj_base_locale"))."', '".get_vocab("maj_base_locale")."')\">".get_vocab("maj_base_locale")."</a> |";
	echo " <a href=\"admin_user.php?action=synchro\" onclick=\"return confirmlink(this, '".AddSlashes(get_vocab("mess_synchro_base_locale"))."', '".get_vocab("synchro_base_locale")."')\">".get_vocab("synchro_base_locale")."</a> |";
}
// Autoriser ou non la modification par un utilisateur de ses informations personnelles (nom, prénom)
// Par ailleurs un gestionnaire d'utilisateurs ne peut pas Autoriser ou non la modification par un utilisateur de ses informations personnelles
if (authGetUserLevel(getUserName(),-1,'user') !=  1)
{
	echo "<form action=\"admin_user.php\" method=\"get\">\n";
	echo "<table border=\"1\">\n";
	echo "<tr>\n";
	echo "<td>".get_vocab("modification_parametres_personnels").get_vocab("deux_points")."<select name=\"allow_users_modify_profil\" size=\"1\">\n";
	echo "<option value = '1' ";
	if (Settings::get("allow_users_modify_profil") == '1')
		echo " selected=\"selected\"";
	echo ">".get_vocab("all")."</option>\n";
	echo "<option value = '2' ";
	if (Settings::get("allow_users_modify_profil") == '2')
		echo " selected=\"selected\"";
	echo ">".get_vocab("all_but_visitors")."</option>\n";
	echo "<option value = '5' ";
	if (Settings::get("allow_users_modify_profil") == '5')
		echo " selected=\"selected\"";
	echo ">".get_vocab("only_administrators")."</option>\n";
	echo "</select>";
	echo "</td>\n<td><div><input type=\"submit\" value=\"".get_vocab("OK")."\" /></div></td></tr></table>\n";
	echo "<div><input type=\"hidden\" name=\"action\" value=\"modif_profil\" />\n
	<input type=\"hidden\" name=\"display\" value=\"$display\" /></div>
</form>\n";
}
// Autoriser ou non la modification par un utilisateur de son email
// Par ailleurs un gestionnaire d'utilisateurs ne peut pas Autoriser ou non la modification par un utilisateur de ses informations personnelles
if (authGetUserLevel(getUserName(), -1, 'user') != 1)
{
	echo "<form action=\"admin_user.php\" method=\"get\">\n";
	echo "<table border=\"1\">\n";
	echo "<tr>\n";
	echo "<td>".get_vocab("modification_parametre_email").get_vocab("deux_points")."<select name=\"allow_users_modify_email\" size=\"1\">\n";
	echo "<option value = '1' ";
	if (Settings::get("allow_users_modify_email") == '1')
		echo " selected=\"selected\"";
	echo ">".get_vocab("all")."</option>\n";
	echo "<option value = '2' ";
	if (Settings::get("allow_users_modify_email") == '2')
		echo " selected=\"selected\"";
	echo ">".get_vocab("all_but_visitors")."</option>\n";
	echo "<option value = '5' ";
	if (Settings::get("allow_users_modify_email") == '5')
		echo " selected=\"selected\"";
	echo ">".get_vocab("only_administrators")."</option>\n";
	echo "</select>";
	echo "</td>\n<td><div><input type=\"submit\" value=\"".get_vocab("OK")."\" /></div></td></tr></table>\n";
	echo "<div><input type=\"hidden\" name=\"action\" value=\"modif_email\" />\n
	<input type=\"hidden\" name=\"display\" value=\"$display\" /></div>
</form>\n";
}
// Autoriser ou non la modification par un utilisateur de son mot de passe,
// Par ailleurs un gestionnaire d'utilisateurs ne peut pas Autoriser ou non la modification par un utilisateur de son mot de passe
if (authGetUserLevel(getUserName(), -1, 'user') != 1)
{
	echo "<form action=\"admin_user.php\" method=\"get\">\n";
	echo "<table border=\"1\">\n";
	echo "<tr>\n";
	echo "<td>".get_vocab("modification_mdp").get_vocab("deux_points")."<select name=\"allow_users_modify_mdp\" size=\"1\">\n";
	echo "<option value = '1' ";
	if (Settings::get("allow_users_modify_mdp") == '1')
		echo " selected=\"selected\"";
	echo ">".get_vocab("all")."</option>\n";
	echo "<option value = '2' ";
	if (Settings::get("allow_users_modify_mdp") == '2')
		echo " selected=\"selected\"";
	echo ">".get_vocab("all_but_visitors")."</option>\n";
	echo "<option value = '5' ";
	if (Settings::get("allow_users_modify_mdp") == '5')
		echo " selected=\"selected\"";
	echo ">".get_vocab("only_administrators")."</option>\n";
	echo "</select>";
	echo "</td>\n<td><div><input type=\"submit\" value=\"".get_vocab("OK")."\" /></div></td></tr></table>\n";
	echo "<div><input type=\"hidden\" name=\"action\" value=\"modif_mdp\" />\n
	<input type=\"hidden\" name=\"display\" value=\"$display\" />
</div></form>\n";
}
echo "<form action=\"admin_user.php\" method=\"get\">\n";
echo "<table border=\"1\">\n";
echo "<tr>\n";
echo "<td>".get_vocab("display_all_user.php")."<input type=\"radio\" name=\"display\" value=\"tous\"";
if ($display == 'tous')
	echo " checked=\"checked\"";
echo " /></td>";
?>
<td>
	<?php echo get_vocab("display_user_on.php"); ?><input type="radio" name="display" value='actifs' <?php if ($display == 'actifs') {echo " checked=\"checked\"";} ?> /></td>
	<td>
		<?php echo get_vocab("display_user_off.php"); ?><input type="radio" name="display" value='inactifs' <?php if ($display == 'inactifs') {echo " checked=\"checked\"";} ?> /></td>
		<td><input type="submit" value="<?php echo get_vocab("OK");?>" /></td>
	</tr>
</table>
<div><input type="hidden" name="order_by" value="<?php echo $order_by;?>" /></div>
</form>
<?php
// Affichage du tableau
echo "<table class=\"table table-striped table-bordered\">";
echo "<tr><td><b><a href='admin_user.php?order_by=login&amp;display=$display'>".get_vocab("login_name")."</a></b></td>";
echo "<td><b><a href='admin_user.php?order_by=nom,prenom&amp;display=$display'>".get_vocab("names")."</a></b></td>";
echo "<td><b>".get_vocab("privileges")."</b></td>";
echo "<td><b><a href='admin_user.php?order_by=statut,nom,prenom&amp;display=$display'>".get_vocab("statut")."</a></b></td>";
echo "<td><b><a href='admin_user.php?order_by=source,nom,prenom&amp;display=$display'>".get_vocab("authentification")."</a></b></td>";
echo "<td><b>".get_vocab("delete")."</b></td>";
echo "</tr>";
$sql = "SELECT nom, prenom, statut, login, etat, source FROM ".TABLE_PREFIX."_utilisateurs ORDER BY $order_by";
$res = grr_sql_query($sql);
if ($res)
{
	for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
	{
		$user_nom = htmlspecialchars($row[0]);
		$user_prenom = htmlspecialchars($row[1]);
		$user_statut = $row[2];
		$user_login = $row[3];
		$user_etat[$i] = $row[4];
		$user_source = $row[5];
		if (($user_etat[$i] == 'actif') && (($display == 'tous') || ($display == 'actifs')))
			$affiche = 'yes';
		else if (($user_etat[$i] != 'actif') && (($display == 'tous') || ($display == 'inactifs')))
			$affiche = 'yes';
		else
			$affiche = 'no';
		if ($affiche == 'yes')
		{
		// Affichage des login, noms et prénoms
			$col[$i][1] = $user_login;
			$col[$i][2] = "$user_nom $user_prenom";
		// Affichage des ressources gérées
			$col[$i][3] = "";
			if (Settings::get("module_multisite") == "Oui")
			{
			// On teste si l'utilisateur administre un site
				$test_admin_site = grr_sql_query1("SELECT count(s.id) FROM ".TABLE_PREFIX."_site s
					left join ".TABLE_PREFIX."_j_useradmin_site j on s.id=j.id_site
					WHERE j.login = '".$user_login."'");
				if (($test_admin_site > 0) || ($user_statut == 'administrateur'))
					$col[$i][3] = "<span class=\"style_privilege\">S</span>";
				else
					$col[$i][3] = "";
			}
		// On teste si l'utilisateur administre un domaine
			$test_admin = grr_sql_query1("SELECT count(a.area_name) FROM ".TABLE_PREFIX."_area a
				left join ".TABLE_PREFIX."_j_useradmin_area j on a.id=j.id_area
				WHERE j.login = '".$user_login."'");
			if (($test_admin > 0) or ($user_statut== 'administrateur'))
				$col[$i][3] .= "<span class=\"style_privilege\"> A</span>";
			else
				$col[$i][3] .= "";
		// Si le domaine est restreint, on teste si l'utilateur a accès
			$test_restreint = grr_sql_query1("SELECT count(a.area_name) FROM ".TABLE_PREFIX."_area a
				left join ".TABLE_PREFIX."_j_user_area j on a.id = j.id_area
				WHERE j.login = '".$user_login."'");
			if (($test_restreint > 0) or ($user_statut == 'administrateur'))
				$col[$i][3] .= "<span class=\"style_privilege\"> R</span>";
			else
				$col[$i][3] .= "";
		// On teste si l'utilisateur administre une ressource
			$test_room = grr_sql_query1("SELECT count(r.room_name) FROM ".TABLE_PREFIX."_room r
				left join ".TABLE_PREFIX."_j_user_room j on r.id=j.id_room
				WHERE j.login = '".$user_login."'");
			if (($test_room > 0) or ($user_statut == 'administrateur'))
				$col[$i][3] .= "<span class=\"style_privilege\"> G</span>";
			else
				$col[$i][3] .= "";
		// On teste si l'utilisateur gère les utilisateurs
			if ($user_statut == "gestionnaire_utilisateur")
				$col[$i][3] .= "<span class=\"style_privilege\"> U</span>";
			else
				$col[$i][3] .= "";
		// On teste si l'utilisateur reçoit des mails automatiques
			$test_mail = grr_sql_query1("SELECT count(r.room_name) FROM ".TABLE_PREFIX."_room r
				left join ".TABLE_PREFIX."_j_mailuser_room j on r.id=j.id_room
				WHERE j.login = '".$user_login."'");
			if ($test_mail > 0)
				$col[$i][3] .= "<span class=\"style_privilege\"> E</span>";
			else
				$col[$i][3] .= " ";
		// Affichage du statut
			if ($user_statut == "administrateur")
			{
				$color[$i] = 'style_admin';
				$col[$i][4] = get_vocab("statut_administrator");
			}
			if ($user_statut == "visiteur")
			{
				$color[$i] = 'style_visiteur';
				$col[$i][4] = get_vocab("statut_visitor");
			}
			if ($user_statut == "utilisateur")
			{
				$color[$i] = 'style_utilisateur';
				$col[$i][4] = get_vocab("statut_user");
			}
			if ($user_statut == "gestionnaire_utilisateur")
			{
				$color[$i] = 'style_gestionnaire_utilisateur';
				$col[$i][4] = get_vocab("statut_user_administrator");
			}
			if ($user_etat[$i] == 'actif')
				$fond = 'fond1';
			else
				$fond = 'fond2';
			// Affichage de la source
			if (($user_source == 'local') || ($user_source == ''))
			{
				$col[$i][5] = "Locale";
			}
			else
			{
				$col[$i][5] = "Ext.";
			}
			echo "\n<tr><td class=\"".$fond."\">{$col[$i][1]}</td>\n";
		// un gestionnaire d'utilisateurs ne peut pas modifier un administrateur général ou un gestionnaire d'utilisateurs
			if ((authGetUserLevel(getUserName(), -1, 'user') ==  1) && (($user_statut == "gestionnaire_utilisateur") || ($user_statut == "administrateur")))
				echo "<td class=\"".$fond."\">{$col[$i][2]}</td>\n";
			else
				echo "<td class=\"".$fond."\"><a href=\"admin_user_modify.php?user_login=".urlencode($user_login)."&amp;display=$display\">{$col[$i][2]}</a></td>\n";
			echo "<td class=\"".$fond."\">{$col[$i][3]}</td>\n";
			echo "<td class=\"".$fond."\"><span class=\"".$color[$i]."\">{$col[$i][4]}</span></td>\n";
			echo "<td class=\"".$fond."\">{$col[$i][5]}</td>\n";
		// Affichage du lien 'supprimer'
		// un gestionnaire d'utilisateurs ne peut pas supprimer un administrateur général ou un gestionnaire d'utilisateurs
		// Un administrateur ne peut pas se supprimer lui-même
			if (((authGetUserLevel(getUserName(), -1, 'user') ==  1) && (($user_statut == "gestionnaire_utilisateur") || ($user_statut == "administrateur"))) || (strtolower(getUserName()) == strtolower($user_login)))
				echo "<td class=\"".$fond."\"> </td>";
			else
			{
				$themessage = get_vocab("confirm_del");
				echo "<td class=\"".$fond."\"><a href='admin_user.php?user_del=".urlencode($col[$i][1])."&amp;action_del=yes&amp;display=$display' onclick='return confirmlink(this, \"$user_login\", \"$themessage\")'>".get_vocab("delete")."</a></td>";
			}
		// Fin de la ligne courante
			echo "</tr>";
		}
	}
}
echo "</table>";
// fin de l'affichage de la colonne de droite
echo "</td></tr></table>";
// Affichage d'un pop-up
affiche_pop_up($msg,"admin");
?>
</body>
</html>
