<?php
/**
 * admin_user.php
 * interface de gestion des utilisateurs de l'application GRR
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2024-11-06 10:53$
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
$grr_script_name = "admin_user.php";

include "../include/admin.inc.php";

$user_id = getUserName();
$back = (isset($_SERVER['HTTP_REFERER']))? htmlspecialchars_decode($_SERVER['HTTP_REFERER'], ENT_QUOTES) : "./admin_accueil.php" ;
$display = isset($_GET["display"]) ? $_GET["display"] : NULL;
$order_by = isset($_GET["order_by"]) ? clean_input($_GET["order_by"]) : NULL;
$msg = '';
if ((authGetUserLevel($user_id, -1) < 6) && (authGetUserLevel($user_id, -1,'user') != 1))
{
	showAccessDenied($back);
	exit();
}
if ((isset($_GET['action_del'])) && ($_GET['js_confirmed'] == 1))
{
	VerifyModeDemo();
}
// Enregistrement de allow_users_modify_profil
// Un gestionnaire d'utilisateurs ne peut pas Autoriser ou non la modification par un utilisateur de ses informations personnelles
if ((isset($_GET['action'])) && ($_GET['action'] == "modif_profil") && (authGetUserLevel($user_id, -1, 'user') !=  1))
{
	if (!Settings::set("allow_users_modify_profil", $_GET['allow_users_modify_profil']))
		$msg = get_vocab("message_records_error");
	else
		$msg = get_vocab("message_records");
}
// Enregistrement de allow_users_modify_email
// Un gestionnaire d'utilisateurs ne peut pas Autoriser ou non la modification par un utilisateur de ses informations personnelles
if ((isset($_GET['action'])) && ($_GET['action'] == "modif_email") && (authGetUserLevel($user_id, -1, 'user') !=  1))
{
	if (!Settings::set("allow_users_modify_email", $_GET['allow_users_modify_email']))
		$msg = get_vocab("message_records_error");
	else
		$msg = get_vocab("message_records");
}
// Enregistrement de allow_users_modify_mdp
// Un gestionnaire d'utilisateurs ne peut pas Autoriser ou non la modification par un utilisateur de son mot de passe
if ((isset($_GET['action'])) && ($_GET['action'] == "modif_mdp") && (authGetUserLevel($user_id, -1, 'user') !=  1))
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
                    grr_sql_command("DELETE FROM ".TABLE_PREFIX."_j_userbook_room WHERE login='".$user_login."'");
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
			// On insère le nouvel utilisteur
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
	if (authGetUserLevel($user_id, -1,'user') ==  1)
	{
		$test_statut = grr_sql_query1("SELECT statut FROM ".TABLE_PREFIX."_utilisateurs WHERE login='".$_GET['user_del']."'");
		if (($test_statut == "gestionnaire_utilisateur") || ($test_statut == "administrateur"))
			$can_delete = "no";
	}
	if (($temp != $user_id) && ($can_delete == "yes"))
	{
		$temp = str_replace('\\', '\\\\', $temp);
		$sql = "DELETE FROM ".TABLE_PREFIX."_utilisateurs WHERE login=?";
		if (grr_sql_command($sql,"s",[$temp]) < 0)
		{
			fatal_error(1, "<p>" . $_SESSION['display_msg']);
		}
		else
		{
			grr_sql_command("DELETE FROM ".TABLE_PREFIX."_j_mailuser_room WHERE login=?","s",[$temp]);
			grr_sql_command("DELETE FROM ".TABLE_PREFIX."_j_user_area WHERE login=?","s",[$temp]);
			grr_sql_command("DELETE FROM ".TABLE_PREFIX."_j_user_room WHERE login=?","s",[$temp]);
      grr_sql_command("DELETE FROM ".TABLE_PREFIX."_j_userbook_room WHERE login=?","s",[$temp]);
			grr_sql_command("DELETE FROM ".TABLE_PREFIX."_j_useradmin_area WHERE login=?","s",[$temp]);
			grr_sql_command("DELETE FROM ".TABLE_PREFIX."_j_useradmin_site WHERE login=?","s",[$temp]);
			$msg=get_vocab("del_user_succeed");
		}
	}
}
$tip = "A : administrateur 
E : destinataire de mails automatiques 
G : gestionnaire de ressource 
R : accède à un domaine restreint 
S : administrateur de site 
U : gestionnaire d'utilisateurs";
// voir avec le retour chariot &#13; et le code html
// calcul des éléments du tableau
$col = array();
$color = array();
$statut = array('administrateur'=>get_vocab("statut_administrator"),'visiteur'=>get_vocab("statut_visitor"),'utilisateur'=>get_vocab("statut_user"),'gestionnaire_utilisateur'=>get_vocab("statut_user_administrator"));
$style = array('administrateur'=>"style_admin",'visiteur'=>"style_visiteur",'utilisateur'=>"style_utilisateur",'gestionnaire_utilisateur'=>"style_gestionnaire_utilisateur");
$themessage = get_vocab("confirm_del");
if (empty($display))
{
	$display = 'actifs';
}
if (empty($order_by))
{
	$order_by = 'nom,prenom';
}
$sql = "SELECT nom, prenom, statut, login, etat, source FROM ".TABLE_PREFIX."_utilisateurs ORDER BY $order_by";
$res = grr_sql_query($sql);
if ($res)
{
	foreach($res as $row)
	{
    // afficher ou pas ?
    if(($row['etat'] == 'actif')&&(($display == 'tous')||($display == 'actifs')))
      $affiche = TRUE;
    elseif(($row['etat'] == 'inactif')&&(($display == 'tous')||($display == 'inactifs')))
      $affiche = TRUE;
    else 
      $affiche = FALSE;
    if($affiche){
      // statut
      $user_statut = $row['statut'];
      // privilèges
      $privileges = "";
      if (Settings::get("module_multisite") == "Oui")
      {
      // On teste si l'utilisateur administre un site
        $test_admin_site = grr_sql_query1("SELECT count(s.id) FROM ".TABLE_PREFIX."_site s
          left join ".TABLE_PREFIX."_j_useradmin_site j on s.id=j.id_site
          WHERE j.login =? ","s",[$row['login']]);
        if (($test_admin_site > 0) || ($user_statut == 'administrateur'))
          $privileges .= "S";
      }
      // On teste si l'utilisateur administre un domaine
      $test_admin = grr_sql_query1("SELECT count(a.area_name) FROM ".TABLE_PREFIX."_area a
        left join ".TABLE_PREFIX."_j_useradmin_area j on a.id=j.id_area
        WHERE j.login =? ","s",[$row['login']]);
      if (($test_admin > 0) or ($user_statut== 'administrateur'))
        $privileges .= " A";
    // Si le domaine est restreint, on teste si l'utilisateur a accès
      $test_restreint = grr_sql_query1("SELECT count(a.area_name) FROM ".TABLE_PREFIX."_area a
        left join ".TABLE_PREFIX."_j_user_area j on a.id = j.id_area
        WHERE j.login =? ","s",[$row['login']]);
      if (($test_restreint > 0) or ($user_statut == 'administrateur'))
        $privileges .= " R";
    // On teste si l'utilisateur administre une ressource
      $test_room = grr_sql_query1("SELECT count(r.room_name) FROM ".TABLE_PREFIX."_room r
        left join ".TABLE_PREFIX."_j_user_room j on r.id=j.id_room
        WHERE j.login =? ","s",[$row['login']]);
      if (($test_room > 0) or ($user_statut == 'administrateur'))
        $privileges .= " G";
    // On teste si l'utilisateur gère les utilisateurs
      if ($user_statut == "gestionnaire_utilisateur")
        $privileges .= " U";
    // On teste si l'utilisateur reçoit des mails automatiques
      $test_mail = grr_sql_query1("SELECT count(r.room_name) FROM ".TABLE_PREFIX."_room r
        left join ".TABLE_PREFIX."_j_mailuser_room j on r.id=j.id_room
        WHERE j.login =? ","s",[$row['login']]);
      if ($test_mail > 0)
        $privileges .= " E";
      // source
      if (($row['source'] == 'local') || ($row['source'] == ''))
				$user_source = "Locale";
			else
        $user_source = "Ext.";
      // mise en forme
      $color[$row['login']] = $style[$user_statut];
      // lien pour supprimer ?
      // un gestionnaire d'utilisateurs ne peut pas supprimer un administrateur général ou un gestionnaire d'utilisateurs
      // Un administrateur ne peut pas se supprimer lui-même
			if (((authGetUserLevel($user_id, -1, 'user') ==  1) && (($user_statut == "gestionnaire_utilisateur") || ($user_statut == "administrateur"))) || (strtolower($user_id) == strtolower($row['login'])))
				$lien = '';
			else
        $lien = "<a href='admin_user.php?user_del=".urlencode($row['login'])."&amp;action_del=yes&amp;display=$display' onclick='return confirmlink(this, \"".$row['login']."\", \"$themessage\")'>".get_vocab("delete")."</a>";
      // données à afficher
      $col[$row['login']] = [htmlspecialchars($row['nom']." ".$row['prenom']),$privileges,$statut[$user_statut],$row['etat'],$user_source,$lien];
    }
	}
}
else{
  $_SESSION['display_msg'] = "yes";
  $msg = get_vocab('failed_to_acquire');
}
// code html
start_page_w_header("", "", "", $type="with_session");
include "admin_col_gauche2.php";
if (isset($mess) and ($mess != ""))
	echo "<p>".$mess."</p>";
echo "<div class='col-md-9 col-sm-8 col-xs-12'>";
echo "<h2>".get_vocab('admin_user.php')."</h2>";

//menu de choix des traitements d'utilisateurs
echo "<table class='table table-noborder center'>\n";
echo "<tbody>\n";
echo "<tr>";
echo '
  <td><div class="onglet"><a href="admin_user_modify.php?display='.$display.'">'.get_vocab("display_add_user").'</a></div></td>
  <td><div class="onglet"><a href="admin_import_users_csv.php">'.get_vocab("display_add_user_list_csv").'</a></div></td>
  <td><div class="onglet"><a href="admin_import_users_elycee.php">Importer des utilisateurs depuis elycée</a></div></td>
  <td><div class="onglet"><a href="admin_user_mdp_facile.php">'.get_vocab("admin_user_mdp_facile").'</a></div></td>';
echo "</tr></tbody></table>".PHP_EOL;
echo "<h4>".get_vocab('admin_user1')."</h4>";
// On propose de supprimer les utilisateurs ext de GRR qui ne sont plus présents dans la base LCS
if (Settings::get("sso_statut") == "lcs")
{
	echo "<br />Opérations LCS : | <a href=\"admin_user.php?action=nettoyage\" onclick=\"return confirmlink(this, '".AddSlashes(get_vocab("mess_maj_base_locale"))."', '".get_vocab("maj_base_locale")."')\">".get_vocab("maj_base_locale")."</a> |";
	echo " <a href=\"admin_user.php?action=synchro\" onclick=\"return confirmlink(this, '".AddSlashes(get_vocab("mess_synchro_base_locale"))."', '".get_vocab("synchro_base_locale")."')\">".get_vocab("synchro_base_locale")."</a> |";
}
// Autoriser ou non la modification par un utilisateur de ses informations personnelles (nom, prénom)
// Par ailleurs un gestionnaire d'utilisateurs ne peut pas Autoriser ou non la modification par un utilisateur de ses informations personnelles
if (authGetUserLevel($user_id,-1,'user') !=  1)
{
	echo "<form action=\"admin_user.php\" method=\"get\">\n";
	echo "<label>".get_vocab("modification_parametres_personnels").get_vocab("deux_points")."<select name=\"allow_users_modify_profil\" size=\"1\">\n";
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
	echo "</select></label>";
	echo "<input type=\"submit\" value=\"".get_vocab("OK")."\" />\n";
	echo "<input type=\"hidden\" name=\"action\" value=\"modif_profil\" />\n
	<input type=\"hidden\" name=\"display\" value=\"$display\" /></form>\n";
}
// Autoriser ou non la modification par un utilisateur de son email
// Par ailleurs un gestionnaire d'utilisateurs ne peut pas Autoriser ou non la modification par un utilisateur de ses informations personnelles
if (authGetUserLevel($user_id, -1, 'user') != 1)
{
	echo "<form action=\"admin_user.php\" method=\"get\">\n";
	echo "<label>".get_vocab("modification_parametre_email").get_vocab("deux_points")."<select name=\"allow_users_modify_email\" size=\"1\">\n";
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
	echo "</select></label>";
	echo "<input type=\"submit\" value=\"".get_vocab("OK")."\" />\n";
	echo "<input type=\"hidden\" name=\"action\" value=\"modif_email\" />\n
	<input type=\"hidden\" name=\"display\" value=\"$display\" /></form>\n";
}
// Autoriser ou non la modification par un utilisateur de son mot de passe,
// Par ailleurs un gestionnaire d'utilisateurs ne peut pas Autoriser ou non la modification par un utilisateur de son mot de passe
if (authGetUserLevel($user_id, -1, 'user') != 1)
{
	echo "<form action=\"admin_user.php\" method=\"get\">\n";
	echo "<label>".get_vocab("modification_mdp").get_vocab("deux_points")."<select name=\"allow_users_modify_mdp\" size=\"1\">\n";
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
	echo "</select></label>";
	echo "<input type=\"submit\" value=\"".get_vocab("OK")."\" />\n";
	echo "<input type=\"hidden\" name=\"action\" value=\"modif_mdp\" />\n
	<input type=\"hidden\" name=\"display\" value=\"$display\" /></form>\n";
}
// quels utilisateurs afficher ?
echo "<form action=\"admin_user.php\" method=\"get\">\n";
echo "<table class='table table-noborder'>\n";
echo "<tr>\n<td><input type=\"submit\" value=\"".get_vocab('goto').get_vocab('deux_points')."\" /></td>";
echo "<td>".get_vocab("display_all_user.php")."&nbsp;<input type=\"radio\" name=\"display\" value=\"tous\"";
if ($display == 'tous')
	echo " checked=\"checked\"";
echo " /></td>";
echo "<td>";
echo get_vocab("display_user_on.php");
echo '&nbsp;<input type="radio" name="display" value="actifs" ';
if ($display == 'actifs') {echo " checked=\"checked\"";}
echo ' /></td>';
echo '<td>';
echo get_vocab("display_user_off.php");
echo '&nbsp;<input type="radio" name="display" value="inactifs" ';
if ($display == 'inactifs') {echo " checked=\"checked\"";}
echo ' /></td>';
echo '</tr>
  </table>
  <input type="hidden" name="order_by" value="'.$order_by.'" />
  </form>';
// Affichage du tableau
echo "<table class=\"table table-striped table-bordered\">";
echo "<thead>";
echo "<tr><th><b><a href='admin_user.php?order_by=login&amp;display=$display'>".get_vocab("login_name")."</a></b></th>";
echo "<th><b><a href='admin_user.php?order_by=nom,prenom&amp;display=$display'>".get_vocab("names")."</a></b></th>";
echo '<th><b><span data-html="true" title="'.$tip.'">'.get_vocab('privileges').'</span></b></th>';
echo "<th><b><a href='admin_user.php?order_by=statut,nom,prenom&amp;display=$display'>".get_vocab("statut")."</a></b></th>";
echo "<th><b><a href='admin_user.php?order_by=source,nom,prenom&amp;display=$display'>".get_vocab("authentification")."</a></b></th>";
echo "<th><b>".get_vocab("delete")."</b></th>";
echo "</tr>";
echo "</thead>\n<tbody>";
foreach($col as $key => $row)
{
  if($row[3] == 'actif')
    $fond = 'fond1';
  else
    $fond = 'fond2';
  echo "\n<tr><td class=\"".$fond."\">{$key}</td>\n";
  // un gestionnaire d'utilisateurs ne peut pas modifier un administrateur général ou un gestionnaire d'utilisateurs
  if ((authGetUserLevel($user_id, -1, 'user') ==  1) && (($user_statut == "gestionnaire_utilisateur") || ($user_statut == "administrateur")))
    echo "<td class=\"".$fond."\">".$row[0]."</td>\n";
  else
    echo "<td class=\"".$fond."\"><a href=\"admin_user_modify.php?user_login=".urlencode($key)."&amp;display=$display\">".$row[0]."</a></td>\n";
  echo "<td class=\"".$fond."\">".$row[1]."</td>\n";
  echo "<td class=\"".$fond."\"><span class=\"".$color[$key]."\">".$row[2]."</span></td>\n";
  echo "<td class=\"".$fond."\">".$row[4]."</td>\n";
  echo "<td class=\"".$fond."\">".$row[5]."</td>\n";
  // Fin de la ligne courante
  echo "</tr>";
}
echo "</tbody></table>";
// fin de l'affichage de la colonne de droite
echo "</div>";
// Affichage d'un pop-up
affiche_pop_up($msg,"admin");
end_page();
?>