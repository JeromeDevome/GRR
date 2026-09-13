<?php
/**
 * admin_user.php
 * interface de gestion des utilisateurs de l'application GRR
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2026-01-25 11:30$
 * @author    Laurent Delineau & JeromeB
 * @copyright Since 2003 Team DEVOME - JeromeB
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

// Accès à la page
if ((SecuAccess::UserLevel(getUserName(), -1) < 6) && (SecuAccess::UserLevel(getUserName(), -1,'user') != 1))
{
	showAccessDenied($back);
	exit();
}

include_once("modeles/suppression.class.php");

// les variables attendues et leur type
$form_vars = array(
    'p_action' => array('int', 0), // 1 : supression, 2 : rendre actif en masse, 3 : rendre inactif en masse, 4 : suppression en masse
	'p_utilisateurs' => array('array', array()), // tableau des utilisateurs sélectionnés pour l'action groupée
);
// récupération des valeurs des variables passées en paramètres
foreach($form_vars as $var => $params)
    $$var = SecuChaine::GetFormVarSecure($var, $params[0], $params[1]);


/** Actions **/
	if ($p_action > 0)
	{
		VerifyModeDemo();
		$processedUsers = 0;
		
		if($p_action == 1) // Suppression d'un unique utilisateur
		{

			$temp = SecuChaine::CleanLogin($_REQUEST['user_del']);
			// un gestionnaire d'utilisateurs ne peut pas supprimer un administrateur général ou un gestionnaire d'utilisateurs
			$can_delete = "yes";
			if (SecuAccess::UserLevel(getUserName(), -1,'user') ==  1)
			{
				$test_statut = grr_sql_query1("SELECT statut FROM ".TABLE_PREFIX."_utilisateurs WHERE login='".SecuChaine::CleanLogin($_GET['user_del'])."'");
				if (($test_statut == "gestionnaire_utilisateur") || ($test_statut == "administrateur"))
					$can_delete = "no";
			}
			if (($temp != getUserName()) && ($can_delete == "yes"))
			{
				$temp = str_replace('\\', '\\\\', $temp);
				Adm_Suppression::Utilisateur($temp);

				$d['enregistrement'] = 1;
				$d['msgToast'] = get_vocab("del_user_succeed");
			}
		}

		if($p_action == 2 || $p_action == 3 || $p_action == 4) // Actions groupées
		{
			$selectedUsers = array_unique(array_map(array('SecuChaine', 'CleanLogin'), $p_utilisateurs));

			foreach ($selectedUsers as $selectedUser)
			{
				if ($selectedUser === '' || strcasecmp($selectedUser, getUserName()) === 0)
					continue;

				$userStatus = grr_sql_query1("SELECT statut FROM ".TABLE_PREFIX."_utilisateurs WHERE login='".SecuChaine::ProtectDataSql($selectedUser)."'");
				$isProtected = SecuAccess::UserLevel(getUserName(), -1, 'user') == 1
					&& in_array($userStatus, array('gestionnaire_utilisateur', 'administrateur'), true);
				if ($isProtected)
					continue;
			
				if($p_action == 2) // Rendre actif en masse
				{
					grr_sql_query("UPDATE ".TABLE_PREFIX."_utilisateurs SET etat='actif' WHERE login='".SecuChaine::ProtectDataSql($selectedUser)."'");
					$processedUsers++;
				}
				elseif($p_action == 3) // Rendre inactif en masse
				{
					grr_sql_query("UPDATE ".TABLE_PREFIX."_utilisateurs SET etat='inactif' WHERE login='".SecuChaine::ProtectDataSql($selectedUser)."'");
					$processedUsers++;
				} 
				elseif($p_action == 4) // Suppression en masse
				{
					$login = SecuChaine::ProtectDataSql($selectedUser);
					Adm_Suppression::Utilisateur($login);
					$processedUsers++;
				}
			}

			$d['enregistrement'] = 1;
			$d['msgToast'] = $processedUsers." utilisateur(s) traité(s).";

		}

	}

/** Affichage de la page **/
	$trad['TitrePage']	= $trad['admin_user'];

	// Tableau des utilisateurs
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

			$col[$i][6] = $user_etat[$i];
			// Affichage des login, noms et prénoms
			$col[$i][1] = $user_login;
			$col[$i][2] = "$user_nom $user_prenom";

			// Affichage des ressources gérées
			$col[$i][3] = "";
			if (Settings::get("module_multisite") == 1)
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
			if ((SecuAccess::UserLevel(getUserName(), -1, 'user') ==  1) && (($user_statut == "gestionnaire_utilisateur") || ($user_statut == "administrateur")))
				$col[$i][8] = 0;
			else
				$col[$i][8] = 1;

			// Affichage du lien 'supprimer'
			// un gestionnaire d'utilisateurs ne peut pas supprimer un administrateur général ou un gestionnaire d'utilisateurs
			// Un administrateur ne peut pas se supprimer lui-même
			if (((SecuAccess::UserLevel(getUserName(), -1, 'user') ==  1) && (($user_statut == "gestionnaire_utilisateur") || ($user_statut == "administrateur"))) || (strtolower(getUserName()) == strtolower($user_login)))
				$col[$i][7] = 0;
			else
				$col[$i][7] = 1;

			// Affichage email
			$col[$i][9] = $user_mail;
		}
	}

	echo $twig->render($page.'.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings, 'utilisateurs' => $col));

?>