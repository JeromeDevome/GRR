<?php
/**
 * admin_user.php
 * interface de gestion des utilisateurs de l'application GRR
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2017-12-16 14:00$
 * @author    Laurent Delineau & JeromeB
 * @copyright Copyright 2003-2020 Team DEVOME - JeromeB
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
//$order_by = isset($_GET["order_by"]) ? $_GET["order_by"] : NULL;
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
/* if (empty($order_by))
{
	$order_by = 'nom,prenom';
} */

$trad['dDisplay'] = $display;

get_vocab_admin('admin_user');
get_vocab_admin("display_add_user");
get_vocab_admin("via_fichier");
get_vocab_admin("admin_menu_various");
get_vocab_admin("admin_user_mdp_facile");
get_vocab_admin("admin_purge_accounts");

get_vocab_admin("maj_base_locale");
get_vocab_admin("mess_maj_base_locale");
get_vocab_admin("synchro_base_locale");
get_vocab_admin("mess_synchro_base_locale");
get_vocab_admin("confirm_del");


get_vocab_admin("login_name");
get_vocab_admin("mail_user");
get_vocab_admin("names");
get_vocab_admin("privileges");
get_vocab_admin("statut");
get_vocab_admin("activ_user");
get_vocab_admin("authentification");
get_vocab_admin("action");

get_vocab_admin("confirm_del");
get_vocab_admin("cancel");
get_vocab_admin("delete");

if (authGetUserLevel(getUserName(),-1) >= 6)
	$trad['dEstAdministrateur'] = 1;

$display == 'tous';

// Affichage du tableau

$sql = "SELECT nom, prenom, statut, login, etat, source, email FROM ".TABLE_PREFIX."_utilisateurs ORDER BY nom,prenom";
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
		$user_mail = $row[6];
		//if (($user_etat[$i] == 'actif') && (($display == 'tous') || ($display == 'actifs')))
		//	$affiche = 'yes';
		//else if (($user_etat[$i] != 'actif') && (($display == 'tous') || ($display == 'inactifs')))
		//	$affiche = 'yes';
		//else
		//	$affiche = 'no';
		//if ($affiche == 'yes')
		//{
			$col[$i][6] = $user_etat[$i];
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
				$col[$i][4] = "<span class=\"text-red\">".get_vocab("statut_administrator")."</span>";

			if ($user_statut == "visiteur")
				$col[$i][4] = "<span class=\"text-green\">".get_vocab("statut_visitor")."</span>";

			if ($user_statut == "utilisateur")
				$col[$i][4] = "<span class=\"text-light-blue\">".get_vocab("statut_user")."</span>";

			if ($user_statut == "gestionnaire_utilisateur")
				$col[$i][4] = "<span class=\"text-yellow\">".get_vocab("statut_user_administrator")."</span>";

			// Affichage de la source
			if (($user_source == 'local') || ($user_source == ''))
				$col[$i][5] = "Locale";
			else
				$col[$i][5] = "Ext.";


			// un gestionnaire d'utilisateurs ne peut pas modifier un administrateur général ou un gestionnaire d'utilisateurs
			if ((authGetUserLevel(getUserName(), -1, 'user') ==  1) && (($user_statut == "gestionnaire_utilisateur") || ($user_statut == "administrateur")))
				$col[$i][8] = 0;
			else
				$col[$i][8] = 1;

			// Affichage du lien 'supprimer'
			// un gestionnaire d'utilisateurs ne peut pas supprimer un administrateur général ou un gestionnaire d'utilisateurs
			// Un administrateur ne peut pas se supprimer lui-même
			if (((authGetUserLevel(getUserName(), -1, 'user') ==  1) && (($user_statut == "gestionnaire_utilisateur") || ($user_statut == "administrateur"))) || (strtolower(getUserName()) == strtolower($user_login)))
				$col[$i][7] = 0;
			else
				$col[$i][7] = 1;
				//echo "<a href='admin_user.php?user_del=".urlencode($col[$i][1])."&amp;action_del=yes&amp;display=$display' onclick='return confirmlink(this, \"$user_login\", \"$themessage\")'>".get_vocab("delete")."</a>";

			// Affichage email
			$col[$i][9] = $user_mail;
			//}
	}
}

affiche_pop_up($msg,"admin");
?>