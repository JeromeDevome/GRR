<?php
/**
 * admin_right.php
 * Interface de gestion des droits de gestion des utilisateurs
 * Dernière modification : $Date: 2026-10-04 16:30$
 * @author    JeromeB & Laurent Delineau
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

$grr_script_name = "admin_right.php";

// Accès à la page
SecuAccess::CheckAccess(4, $back);

// les variables attendues et leur type
$form_vars = array(
    'p_action' => array('int', 0), // 1 : mise a jour utilisateurs, 2 : mise à jour groupes
    'p_id_area' => array('int', -1),
    'p_id_room' => array('int', -1),
	'p_utilisateurs' => array('array', array()), // tableau des utilisateurs sélectionnés
	'p_groupes' => array('array', array()), // tableau des groupes sélectionnés
);
// récupération des valeurs des variables passées en paramètres
foreach($form_vars as $var => $params)
    $$var = SecuChaine::GetFormVarSecure($var, $params[0], $params[1]);


// Contrôle droits supplémantaires pour l'accès à la page
$tab_rooms_noaccess = SecuAccess::UserResource(getUserName(), 'all');


/** Actions **/
	if($p_action == 1 && $p_id_area != -1) // Mise à jour des utilisateurs
	{
		if($p_id_room != -1) // Sur une ressource
		{
			// On vérifie que la ressource $p_id_room existe
			$test = grr_sql_query1("SELECT id FROM ".TABLE_PREFIX."_room WHERE id='".$p_id_room."'");
			if ($test == -1)
			{
				showAccessDenied($back);
				exit();
			}
			if (in_array($p_id_room,$tab_rooms_noaccess))
			{
				showAccessDenied($back);
				exit();
			}
			// La ressource existe : on vérifie les privilèges de l'utilisateur
			if (SecuAccess::UserLevel(getUserName(),$p_id_room) < 4)
			{
				showAccessDenied($back);
				exit();
			}

			// On efface tout les utilisateurs ayant les droits sur la ressource avant de les remettres
			$sql = "DELETE FROM ".TABLE_PREFIX."_j_user_room WHERE id_room = '$p_id_room'";
			if (grr_sql_command($sql) < 0)
				fatal_error(1, "<p>" . grr_sql_error());
			else
			{
				$selectedUsers = array_unique(array_map(array('SecuChaine', 'CleanLogin'), $p_utilisateurs));

				foreach ($selectedUsers as $selectedUser)
				{
					$sql = "SELECT * FROM ".TABLE_PREFIX."_j_user_room WHERE (login = '".$selectedUser."' AND id_room = '$p_id_room')";
					$res = grr_sql_query($sql);
					$test = grr_sql_count($res);
					if ($test == 0)
					{
						if ($selectedUser != '')
						{
							$sql = "INSERT INTO ".TABLE_PREFIX."_j_user_room SET login= '$selectedUser', id_room = '$p_id_room'";
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
		else // Sur toute les ressources du domaine
		{
			// On vérifie que le domaine $p_id_area existe
			$test = grr_sql_query1("SELECT id FROM ".TABLE_PREFIX."_area WHERE id='".$p_id_area."'");
			if ($test == -1)
			{
				showAccessDenied($back);
				exit();
			}
			// Le domaine existe : on vérifie les privilèges de l'utilisateur
			if (SecuAccess::UserLevel(getUserName(),$p_id_area,'area') < 4)
			{
				showAccessDenied($back);
				exit();
			}

			$selectedUsers = array_values(array_filter(array_unique(array_map(array('SecuChaine', 'CleanLogin'), $p_utilisateurs))));
			$sql = "SELECT id FROM ".TABLE_PREFIX."_room WHERE area_id=$p_id_area";
			// On ne cherche pas parmi les ressources invisibles pour l'utilisateur
			foreach ($tab_rooms_noaccess as $key)
				$sql .= " AND id != $key ";
			$res = grr_sql_query($sql);
			if ($res)
			{
				$roomIds = array();
				for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
					$roomIds[] = (int) $row[0];

				if (!empty($roomIds))
				{
					$roomIdList = implode(',', $roomIds);
					$sql2 = "SELECT login FROM ".TABLE_PREFIX."_j_user_room WHERE id_room IN ($roomIdList) GROUP BY login HAVING COUNT(DISTINCT id_room) = ".count($roomIds);
					$res2 = grr_sql_query($sql2);
					$managedUsers = array();
					if ($res2)
					{
						for ($i = 0; ($managedRow = grr_sql_row($res2, $i)); $i++)
							$managedUsers[] = SecuChaine::CleanLogin($managedRow[0]);
					}

					$usersToRemove = array_values(array_diff($managedUsers, $selectedUsers));
					if (!empty($usersToRemove))
					{
						$deleteSql = "DELETE FROM ".TABLE_PREFIX."_j_user_room WHERE id_room IN ($roomIdList) AND login IN ('".implode("','", $usersToRemove)."')";
						if (grr_sql_command($deleteSql) < 0)
							fatal_error(1, "<p>" . grr_sql_error());
					}

					foreach ($roomIds as $roomId)
					{
						foreach ($selectedUsers as $selectedUser)
						{
							$sql2 = "SELECT login FROM ".TABLE_PREFIX."_j_user_room WHERE (login = '".$selectedUser."' AND id_room = '$roomId')";
							$res2 = grr_sql_query($sql2);
							$nb = grr_sql_count($res2);
							if ($nb == 0)
							{
								$sql3 = "INSERT INTO ".TABLE_PREFIX."_j_user_room (login, id_room) VALUES ('".$selectedUser."','$roomId')";
								if (grr_sql_command($sql3) < 0)
									fatal_error(0, "<p>" . grr_sql_error());
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

		}

	}
	elseif($p_action == 2 && $p_id_area != -1) // Mise à jour des groupes
	{
		//TODO : à développer
	}


/** Affichage de la page **/
	$trad['TitrePage'] = $trad['admin_right'];
	$d['idDomaine'] = $p_id_area;
	$d['idRessource'] = $p_id_room;

	$utilisateursAdmin = array ();
	$utilisateursAjoutable = array ();
	$domaines = array ();
	$ressources = array ();

	// Liste des domaines administrables par l'utilisateur connecté
	$sql = "SELECT id, area_name FROM ".TABLE_PREFIX."_area ORDER BY order_display";
	$res = grr_sql_query($sql);
	if ($res)
	{
		for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
		{
			// On affiche uniquement les domaines administrés par l'utilisateur
			if (SecuAccess::UserLevel(getUserName(),$row[0],'area') >= 4)
				$domaines[] = array('id' => $row[0], 'nom' => $row[1]);
		}
	}

	// Liste des ressouces du domaine sélectionné
	$sql = "SELECT id, room_name, description FROM ".TABLE_PREFIX."_room WHERE area_id=$p_id_area ";
	foreach ($tab_rooms_noaccess as $key)
		$sql .= " AND id != $key ";
	$sql .= " ORDER BY order_display,room_name";
	$res = grr_sql_query($sql);
	if ($res)
	{
		for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
		{
			if ($row[2])
				$temp = " (".htmlspecialchars($row[2]).")";
			else
				$temp = "";
			$ressources[] = array('id' => $row[0], 'nom' => $row[1], 'description' => $row[2]);
		}
	}

	if($p_id_area != -1)
	{
		// Utilisateur déjà gestionnaire de la ressource
		if ($p_id_room != -1) // Sur une ressource
		{
			$sql = "SELECT u.login, u.nom, u.prenom FROM ".TABLE_PREFIX."_utilisateurs u, ".TABLE_PREFIX."_j_user_room j WHERE (j.id_room='$p_id_room' and u.login=j.login) order by u.nom, u.prenom";
			$res = grr_sql_query($sql);
			$nombre = grr_sql_count($res);
			if ($res)
			{
				for ($i = 0; ($row2 = grr_sql_row($res, $i)); $i++)
					$utilisateursAdmin[] = array('login' => $row2[0], 'nom' => $row2[1], 'prenom' => $row2[2]);
			}
		}
		else // Sur toute les ressources du domaine
		{
			$sql = "SELECT login, nom, prenom FROM ".TABLE_PREFIX."_utilisateurs WHERE (statut='utilisateur' or statut='gestionnaire_utilisateur')";
			$res = grr_sql_query($sql);
			if ($res)
			{
				for ($i = 0; ($row2 = grr_sql_row($res, $i)); $i++)
				{
					$is_admin = 'yes';
					$sql2 = "SELECT id, room_name, description FROM ".TABLE_PREFIX."_room WHERE area_id=$p_id_area ";
					foreach ($tab_rooms_noaccess as $key)
						$sql2 .= " AND id != $key ";
					$sql2 .= " ORDER BY order_display,room_name";
					$res2 = grr_sql_query($sql2);

					if ($res2)
					{
						$test = grr_sql_count($res2);
						if ($test != 0)
						{
							for ($j = 0; ($row4 = grr_sql_row($res2, $j)); $j++)
							{
								$sql3 = "SELECT login FROM ".TABLE_PREFIX."_j_user_room WHERE (id_room='".$row4[0]."' AND login='".$row2[0]."')";
								$res3 = grr_sql_query($sql3);
								$nombre = grr_sql_count($res3);
								if ($nombre == 0)
									$is_admin = 'no';
							}
						}
						else
							$is_admin = 'no';
					}
					if ($is_admin == 'yes')
					{
						$utilisateursAdmin[] = array('login' => $row2[0], 'nom' => $row2[1], 'prenom' => $row2[2]);
					}
				}
			}

		}

		// Utilisateurs pouvant être ajouté
		$sql = "SELECT login, nom, prenom FROM ".TABLE_PREFIX."_utilisateurs WHERE  (etat!='inactif' and (statut='utilisateur' or statut='gestionnaire_utilisateur')) order by nom, prenom";
		$res = grr_sql_query($sql);
		if ($res)
		{
			for ($i = 0; ($row3 = grr_sql_row($res, $i)); $i++)
			{
				if (SecuAccess::UserArea($row3[0],$p_id_area) == 1)
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
		}
	}



	echo $twig->render('admin_right.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings, 'domaines' => $domaines, 'ressources' => $ressources, 'utilisateursadmin' => $utilisateursAdmin, 'utilisateursajoutable' => $utilisateursAjoutable));
?>