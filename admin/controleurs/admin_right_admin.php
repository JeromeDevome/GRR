<?php
/**
 * admin_right_admin.php
 * Interface de gestion des droits d'administration des utilisateurs
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2026-10-04 10:10$
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

$grr_script_name = "admin_right_admin.php";

// Accès à la page
SecuAccess::CheckAccess(6, $back);

// les variables attendues et leur type
$form_vars = array(
    'p_action' => array('int', 0), // 1 : mise a jour utilisateurs, 2 : mise à jour groupes
    'p_id_area' => array('int', -1),
	'p_utilisateurs' => array('array', array()), // tableau des utilisateurs sélectionnés
	'p_groupes' => array('array', array()), // tableau des groupes sélectionnés
);
// récupération des valeurs des variables passées en paramètres
foreach($form_vars as $var => $params)
    $$var = SecuChaine::GetFormVarSecure($var, $params[0], $params[1]);


/** Actions **/
	if($p_action == 1 && $p_id_area != -1) // Mise à jour des utilisateurs
	{
		// On efface tout les utilisateurs ayant les droits sur le site avant de les remettres
		$sql = "DELETE FROM ".TABLE_PREFIX."_j_useradmin_area WHERE id_area = '$p_id_area'";
		if (grr_sql_command($sql) < 0)
			fatal_error(1, "<p>" . grr_sql_error());
		else
		{
			$selectedUsers = array_unique(array_map(array('SecuChaine', 'CleanLogin'), $p_utilisateurs));

			foreach ($selectedUsers as $selectedUser)
			{
				$sql = "SELECT * FROM ".TABLE_PREFIX."_j_useradmin_area WHERE (login = '".$selectedUser."' AND id_area = '$p_id_area)";
				$res = grr_sql_query($sql);
				$test = grr_sql_count($res);
				if ($test == 0)
				{
					if ($selectedUser != '')
					{
						$sql = "INSERT INTO ".TABLE_PREFIX."_j_useradmin_area SET login= '$selectedUser', id_area = '$p_id_area'";
						if (grr_sql_command($sql) < 0)
							fatal_error(1, "<p>" . grr_sql_error());
						else
						{
							$d['enregistrement'] = 1;
							$d['msgToast'] = get_vocab("add_multi_user_succeed");
						}
					}
				}
			}
		}
	}
	elseif($p_action == 2 && $p_id_area != -1) // Mise à jour des groupes
	{
		//TODO : à développer
	}



/** Affichage de la page **/
	$trad['TitrePage'] = $trad['admin_right_admin'];
	$d['idDomaine'] = $p_id_area;

	$utilisateursAdmin = array ();
	$utilisateursAjoutable = array ();

	// Liste des domaines
	$sql = "select id, area_name from ".TABLE_PREFIX."_area ORDER BY order_display";
	$res = grr_sql_query($sql);
	if ($res)
	{
		for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
			$domaines[] = array('id' => $row[0], 'nom' => $row[1]);
	}

	// Utilisateurs état admin du domaine
	$sql = "SELECT login, nom, prenom FROM ".TABLE_PREFIX."_utilisateurs WHERE (statut='utilisateur' OR statut='gestionnaire_utilisateur' OR statut='administrateur')";
	$res = grr_sql_query($sql);

	if ($res) for ($i = 0; ($row2 = grr_sql_row($res, $i)); $i++)
	{
		$sql3 = "SELECT login FROM ".TABLE_PREFIX."_j_useradmin_area WHERE (id_area='".$p_id_area."' AND login='".$row2[0]."')";
		$res3 = grr_sql_query($sql3);
		$nombre = grr_sql_count($res3);
		if ($nombre != 0)
			$utilisateursAdmin[] = array('login' => $row2[0], 'nom' => $row2[1], 'prenom' => $row2[2]);
	}

	// Utilisateurs pouvant être ajouté
	$sql = "SELECT login, nom, prenom FROM ".TABLE_PREFIX."_utilisateurs WHERE etat!='inactif' AND (statut='utilisateur' OR statut='administrateur' OR statut='gestionnaire_utilisateur') ORDER BY nom, prenom";
	$res = grr_sql_query($sql);

	if ($res)
	{
		for ($i = 0; ($row3 = grr_sql_row($res, $i)); $i++)
			if (SecuAccess::UserArea($row3[0], $p_id_area) == 1)
			{
				$ExisteDeja = false;
				foreach($utilisateursAdmin as $index => $user) {
					if($user['login'] == $row3[0])
						$ExisteDeja = true;
				}

				if($ExisteDeja == false)
					$utilisateursAjoutable[] = array('login' => $row3[0], 'nom' => $row3[1], 'prenom' => $row3[2]);
			}
	}


	echo $twig->render('admin_right_admin.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings, 'domaines' => $domaines, 'utilisateursadmin' => $utilisateursAdmin, 'utilisateursajoutable' => $utilisateursAjoutable));
?>