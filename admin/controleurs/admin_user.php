<?php
/**
 * admin_user.php
 * interface de gestion des utilisateurs de l'application GRR
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2017-12-16 14:00$
 * @author    Laurent Delineau & JeromeB
 * @copyright Copyright 2003-2018 Team DEVOME - JeromeB
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

$display = isset($_GET["display"]) ? $_GET["display"] : NULL;
$order_by = isset($_GET["order_by"]) ? $_GET["order_by"] : NULL;
$msg = '';

if ((authGetUserLevel(getUserName(), -1) < 6) && (authGetUserLevel(getUserName(), -1,'user') != 1))
{
	showAccessDenied($back);
	exit();
}
if ((isset($_GET['action_del'])) && isset($_GET['js_confirmed']) && ($_GET['js_confirmed'] == 1))
{
	VerifyModeDemo();
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
if ((isset($_GET['action_del'])) and (isset($_GET['js_confirmed'])) and ($_GET['js_confirmed'] == 1))
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

if (empty($display))
{
	$display = 'actifs';
}
if (empty($order_by))
{
	$order_by = 'nom,prenom';
}

$trad['dDisplay'] = $display;

get_vocab_admin('admin_user');
get_vocab_admin("display_add_user");
get_vocab_admin("via_fichier");
get_vocab_admin("admin_user_mdp_facile");
get_vocab_admin("admin_menu_various");

get_vocab_admin("maj_base_locale");
get_vocab_admin("mess_maj_base_locale");
get_vocab_admin("synchro_base_locale");
get_vocab_admin("mess_synchro_base_locale");
get_vocab_admin("confirm_del");


get_vocab_admin("login_name");
get_vocab_admin("names");
get_vocab_admin("privileges");
get_vocab_admin("statut");
get_vocab_admin("authentification");
get_vocab_admin("action");


$display == 'tous';

// Affichage du tableau

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
					$col[$i][3] = "<span class=\"text-red\">S</span>";
			}
			// On teste si l'utilisateur administre un domaine
			$test_admin = grr_sql_query1("SELECT count(a.area_name) FROM ".TABLE_PREFIX."_area a
				left join ".TABLE_PREFIX."_j_useradmin_area j on a.id=j.id_area
				WHERE j.login = '".$user_login."'");
			if (($test_admin > 0) or ($user_statut== 'administrateur'))
				$col[$i][3] .= "<span class=\"text-red\"> A</span>";

			// Si le domaine est restreint, on teste si l'utilateur a accès
			$test_restreint = grr_sql_query1("SELECT count(a.area_name) FROM ".TABLE_PREFIX."_area a
				left join ".TABLE_PREFIX."_j_user_area j on a.id = j.id_area
				WHERE j.login = '".$user_login."'");
			if (($test_restreint > 0) or ($user_statut == 'administrateur'))
				$col[$i][3] .= "<span class=\"text-red\"> R</span>";

			// On teste si l'utilisateur administre une ressource
			$test_room = grr_sql_query1("SELECT count(r.room_name) FROM ".TABLE_PREFIX."_room r
				left join ".TABLE_PREFIX."_j_user_room j on r.id=j.id_room
				WHERE j.login = '".$user_login."'");
			if (($test_room > 0) or ($user_statut == 'administrateur'))
				$col[$i][3] .= "<span class=\"text-red\"> G</span>";

			// On teste si l'utilisateur gère les utilisateurs
			if ($user_statut == "gestionnaire_utilisateur")
				$col[$i][3] .= "<span class=\"text-red\"> U</span>";

			// On teste si l'utilisateur reçoit des mails automatiques
			$test_mail = grr_sql_query1("SELECT count(r.room_name) FROM ".TABLE_PREFIX."_room r
				left join ".TABLE_PREFIX."_j_mailuser_room j on r.id=j.id_room
				WHERE j.login = '".$user_login."'");
			if ($test_mail > 0)
				$col[$i][3] .= "<span class=\"text-red\"> E</span>";

			// Affichage du statut
			if ($user_statut == "administrateur")
				$col[$i][4] = "<span class=\"text-red\">".get_vocab("statut_administrator")."</a>";

			if ($user_statut == "visiteur")
				$col[$i][4] = "<span class=\"text-green\">".get_vocab("statut_visitor")."</a>";

			if ($user_statut == "utilisateur")
				$col[$i][4] = "<span class=\"text-light-blue\">".get_vocab("statut_user")."</a>";

			if ($user_statut == "gestionnaire_utilisateur")
				$col[$i][4] = "<span class=\"text-yellow\">".get_vocab("statut_user_administrator")."</a>";

			// Affichage de la source
			if (($user_source == 'local') || ($user_source == ''))
				$col[$i][5] = "Locale";
			else
				$col[$i][5] = "Ext.";


			// un gestionnaire d'utilisateurs ne peut pas modifier un administrateur général ou un gestionnaire d'utilisateurs
			if ((authGetUserLevel(getUserName(), -1, 'user') ==  1) && (($user_statut == "gestionnaire_utilisateur") || ($user_statut == "administrateur")))
				$trad['dAuthorisationModif'] = 0;
			else
				$trad['dAuthorisationModif'] = 1;

			// Affichage du lien 'supprimer'
			// un gestionnaire d'utilisateurs ne peut pas supprimer un administrateur général ou un gestionnaire d'utilisateurs
			// Un administrateur ne peut pas se supprimer lui-même
			if (((authGetUserLevel(getUserName(), -1, 'user') ==  1) && (($user_statut == "gestionnaire_utilisateur") || ($user_statut == "administrateur"))) || (strtolower(getUserName()) == strtolower($user_login)))
				$trad['dAuthorisationSup'] = 0;
			else
				$trad['dAuthorisationSup'] = 1;
				//echo "<a href='admin_user.php?user_del=".urlencode($col[$i][1])."&amp;action_del=yes&amp;display=$display' onclick='return confirmlink(this, \"$user_login\", \"$themessage\")'>".get_vocab("delete")."</a>";
		}
	}
}

affiche_pop_up($msg,"admin");
?>