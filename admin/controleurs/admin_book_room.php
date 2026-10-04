<?php
/**
 * admin_book_room.php
 * Script gérant l'accès aux ressources restreintes de l'application GRR
 * L'affichage est réalisé par admin_book_room.twig
 * Dernière modification : $Date: 2026-10-04 11:50$
 * @author    JeromeB & Yan Naessens
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


$grr_script_name = "admin_book_room.php";


// les variables attendues et leur type
$form_vars = array(
    'p_action' => array('int', 0), // 1 : mise a jour utilisateurs, 2 : mise à jour groupes
    'p_id_room' => array('int', -1),
	'p_utilisateurs' => array('array', array()), // tableau des utilisateurs sélectionnés
	'p_groupes' => array('array', array()), // tableau des groupes sélectionnés
);
// récupération des valeurs des variables passées en paramètres
foreach($form_vars as $var => $params)
    $$var = SecuChaine::GetFormVarSecure($var, $params[0], $params[1]);



if (SecuAccess::UserLevel(getUserName(), $p_id_room) < 3)
{
	showAccessDenied($back);
	exit();
}


/** Actions **/
	if($p_action == 1 && $p_id_room != -1) // Mise à jour des utilisateurs
	{
		// On efface tout les utilisateurs ayant les droits sur la ressource avant de les remettres
		$sql = "DELETE FROM ".TABLE_PREFIX."_j_userbook_room WHERE id_room = '$p_id_room' AND idgroupes = 0";
		if (grr_sql_command($sql) < 0)
			fatal_error(1, "<p>" . grr_sql_error());
		else
		{
			$selectedUsers = array_unique(array_map(array('SecuChaine', 'CleanLogin'), $p_utilisateurs));

			foreach ($selectedUsers as $selectedUser)
			{
				$sql = "SELECT * FROM ".TABLE_PREFIX."_j_userbook_room WHERE (login = '".$selectedUser."' and id_room = '$p_id_room')";
				$res = grr_sql_query($sql);
				$test = grr_sql_count($res);
				if ($test == 0)
				{
					if ($selectedUser != '')
					{
						$sql = "INSERT INTO ".TABLE_PREFIX."_j_userbook_room SET login= '$selectedUser', id_room = '$p_id_room'";
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
	elseif($p_action == 2 && $p_id_room != -1) // Mise à jour des groupes
	{
		// On efface tout les utilisateurs ayant les droits sur le site VIA UN GROUPE avant de les remettres
		$sql = "DELETE FROM ".TABLE_PREFIX."_j_group_room WHERE id_room = '$p_id_room'";
		if (grr_sql_command($sql) < 0)
			fatal_error(1, "<p>" . grr_sql_error());

		$sql = "DELETE FROM ".TABLE_PREFIX."_j_userbook_room WHERE id_room = '$p_id_room' AND idgroupes <> 0";
		if (grr_sql_command($sql) < 0)
			fatal_error(1, "<p>" . grr_sql_error());
		else
		{
			$selectedGroups = array_unique(array_map(array('SecuChaine', 'CleanInput'), $p_groupes));

			foreach ($selectedGroups as $selectGroup)
			{
				if($selectGroup != '')
				{
					$sql = "INSERT INTO ".TABLE_PREFIX."_j_group_room SET idgroupes= '$selectGroup', id_room = '$p_id_room'";
					if (grr_sql_command($sql) < 0)
						fatal_error(1, "<p>" . grr_sql_error());
					else
					{
						$d['enregistrement'] = 1;
						$d['msgToast'] = get_vocab("add_user_succeed");
					}

					synchro_groupe($selectGroup, 1);
				}
			}
		}
	}

/** Affichage de la page **/
	$trad['TitrePage'] = $trad['admin_book_room'];
	$d['id_room'] = $p_id_room;

	$ressources = array();
	$userAcces = array();
	$utilisateursAjoutable = array();
	$groupesExep = array();
	$groupesAjoutable = array();
	$user_name = getUserName();

	// Liste des ressources restreintes
	$multisite = Settings::get("module_multisite") == 1;
	if($multisite)
	$sql = "SELECT r.id,room_name,area_name,sitename
			FROM ((".TABLE_PREFIX."_room r JOIN ".TABLE_PREFIX."_area a ON r.area_id = a.id)
			JOIN ".TABLE_PREFIX."_j_site_area ON a.id = id_area)
			JOIN ".TABLE_PREFIX."_site s ON s.id = id_site
			WHERE r.who_can_book = 0
			ORDER BY room_name";
	else
	$sql = "SELECT r.id,room_name,area_name
			FROM ".TABLE_PREFIX."_room r JOIN ".TABLE_PREFIX."_area a ON r.area_id = a.id
			WHERE r.who_can_book = 0
			ORDER BY room_name";
	$res = grr_sql_query($sql);
	$nb = grr_sql_count($res);

	if (!$res)
		fatal_error(1,grr_sql_error($res));
	else
	{
		foreach($res as $row)
		{
			// on vérifie que l'utilisateur connecté a les droits suffisants
			if (SecuAccess::UserLevel($user_name,$row['id'])>2)
			{
				if($multisite)
					$ressources[] = array($row['id'],($row['sitename']." > ".$row['area_name']." > ".$row['room_name']));
				else
					$ressources[] = array($row['id'],$row['area_name'].' > '.$row['room_name']);
			}
		}
	}

	// La ressource étant choisie, afficher les utilisateurs autorisés à réserver et le formulaire de mise à jour de la liste
	if ($p_id_room != -1)
	{
		// Utilisateurs ayant accès à la ressource restreinte
		$sql = "SELECT u.login, u.nom, u.prenom, j.idgroupes, p.nom FROM ".TABLE_PREFIX."_utilisateurs u JOIN ".TABLE_PREFIX."_j_userbook_room j ON u.login=j.login LEFT JOIN ".TABLE_PREFIX."_groupes p ON j.idgroupes = p.idgroupes WHERE j.id_room='".$p_id_room."' ORDER BY u.nom, u.prenom";
		$res = grr_sql_query($sql);
		if (!$res)
			grr_sql_error($res);
		else {
			$d['nombre'] = grr_sql_count($res);
			if ( $d['nombre'] > 0)
			{
				for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
					$userAcces[] = array('login' => $row[0], 'nom' => $row[1], 'prenom' => $row[2], 'groupeid' => $row[3], 'groupenom' => $row[4]);
			}
		}

		// Utilisateurs pouvant être ajouté
		$sql = "SELECT login, nom, prenom FROM ".TABLE_PREFIX."_utilisateurs WHERE (etat!='inactif' and (statut='utilisateur' or statut='visiteur' or statut='gestionnaire_utilisateur')) AND login NOT IN (SELECT DISTINCT login FROM ".TABLE_PREFIX."_j_userbook_room WHERE id_room = '".$p_id_room."') order by nom, prenom";
		$res = grr_sql_query($sql);
		if ($res)
			for ($i = 0; ($row = grr_sql_row($res, $i)); $i++){
				// on n'affiche que les utilisateurs ayant accès à la ressource
				if (SecuAccess::UserResource($row[0],$p_id_room))
					$utilisateursAjoutable[] = array('login' => $row[0], 'nom' => $row[1], 'prenom' => $row[2]);
			}

		// Groupes ayant accès a la ressource restreinte
		$sql = "SELECT g.idgroupes, g.nom FROM ".TABLE_PREFIX."_groupes g, ".TABLE_PREFIX."_j_group_room j WHERE (j.id_room='$p_id_room' and g.idgroupes=j.idgroupes)  order by g.nom";
		$res = grr_sql_query($sql);
		$nombre = grr_sql_count($res);

		if ($res)
			for ($i = 0; ($row2 = grr_sql_row($res, $i)); $i++)
			{
				$groupesExep[] = array('id' => $row2[0], 'nom' => $row2[1]);
			}

		// Groupes pouvant être ajouté
		$sql = "SELECT idgroupes, nom FROM ".TABLE_PREFIX."_groupes WHERE archive = 0 AND idgroupes NOT IN (SELECT idgroupes FROM ".TABLE_PREFIX."_j_group_room WHERE id_room = '$p_id_room') order by nom";
		$res = grr_sql_query($sql);
		if ($res)
			for ($i = 0; ($row3 = grr_sql_row($res, $i)); $i++)
				$groupesAjoutable[] = array('idgroupe' => $row3[0], 'nom' => $row3[1]);

	}
	else
	{
		if ($nb == 0)
			$d['NoRoomRestriction'] = get_vocab("no_restricted_room");
		else
			$d['NoRoomRestriction'] = get_vocab("no_room_selected");
	}

	echo $twig->render('admin_book_room.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings, 'ressources' => $ressources, 'userAcces' => $userAcces, 'utilisateursajoutable' => $utilisateursAjoutable, 'groupesexep' => $groupesExep, 'groupesajoutable' => $groupesAjoutable));
?>