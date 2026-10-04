<?php
/**
 * admin_access_area.php
 * Interface de gestion des accès restreints aux domaines
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2026-10-04 11:00$
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

$grr_script_name = "admin_access_area.php";

// Accès à la page
SecuAccess::CheckAccess(4, $back);


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
		$sql = "DELETE FROM ".TABLE_PREFIX."_j_user_area WHERE id_area = '$p_id_area' AND idgroupes = 0";
		if (grr_sql_command($sql) < 0)
			fatal_error(1, "<p>" . grr_sql_error());
		else
		{
			$selectedUsers = array_unique(array_map(array('SecuChaine', 'CleanLogin'), $p_utilisateurs));

			foreach ($selectedUsers as $selectedUser)
			{
				$sql = "SELECT * FROM ".TABLE_PREFIX."_j_user_area WHERE (login = '".$selectedUser."' and id_area = '$p_id_area')";
				$res = grr_sql_query($sql);
				$test = grr_sql_count($res);
				if ($test == 0)
				{
					if ($selectedUser != '')
					{
						$sql = "INSERT INTO ".TABLE_PREFIX."_j_user_area SET login= '$selectedUser', id_area = '$p_id_area'";
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
		echo "11111";
		// On efface tout les utilisateurs ayant les droits sur le site VIA UN GROUPE avant de les remettres
		$sql = "DELETE FROM ".TABLE_PREFIX."_j_group_area WHERE id_area = '$p_id_area'";
		if (grr_sql_command($sql) < 0)
			fatal_error(1, "<p>" . grr_sql_error());

		$sql = "DELETE FROM ".TABLE_PREFIX."_j_user_area WHERE id_area = '$p_id_area' AND idgroupes <> 0";
		if (grr_sql_command($sql) < 0)
			fatal_error(1, "<p>" . grr_sql_error());
		else
		{
			$selectedGroups = array_unique(array_map(array('SecuChaine', 'CleanInput'), $p_groupes));
echo"zzzzzzzzzzzzzzzzzzzzzzzzzzzzzzz";print_r($p_groupes);
			foreach ($selectedGroups as $selectGroup)
			{
				if($selectGroup != '')
				{
					echo "11111";
					$sql = "INSERT INTO ".TABLE_PREFIX."_j_group_area SET idgroupes= '$selectGroup', id_area = '$p_id_area'";
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
	$trad['TitrePage'] = $trad['admin_access_area'];
	$d['idDomaine'] = $p_id_area;

	$utilisateursExep = array ();
	$utilisateursAjoutable = array ();
	$groupesExep = array();
	$groupesAjoutable = array();
	$domaines = array ();

	// Liste des domaines
	$sql = "SELECT id, area_name FROM ".TABLE_PREFIX."_area WHERE access='r' ORDER BY area_name";
	$res = grr_sql_query($sql);
	$nb = grr_sql_count($res);
	if ($res)
		for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
		{
			// on affiche que les domaines que l'utilisateur connecté a le droit d'administrer
			if (SecuAccess::UserLevel(getUserName(),$row[0],'area') >= 4)
			{
				$domaines[] = array('id' => $row[0], 'nom' => $row[1]);
			}
		}


	if ($p_id_area != -1)
	{
		// Utilisateurs ayant accès au domaine restreint
		$sql = "SELECT u.login, u.nom, u.prenom, j.idgroupes, p.nom FROM ".TABLE_PREFIX."_utilisateurs u, ".TABLE_PREFIX."_j_user_area j LEFT JOIN ".TABLE_PREFIX."_groupes p ON j.idgroupes = p.idgroupes WHERE (j.id_area='$p_id_area' AND u.login=j.login)  ORDER BY u.nom, u.prenom";
		$res = grr_sql_query($sql);
		$nombre = grr_sql_count($res);

		if ($res)
			for ($i = 0; ($row2 = grr_sql_row($res, $i)); $i++)
			{
				$utilisateursExep[] = array('login' => $row2[0], 'nom' => $row2[1], 'prenom' => $row2[2], 'groupeid' => $row2[3], 'groupenom' => $row2[4]);
			}

		// Utilisateurs pouvant être ajouté
		$sql = "SELECT login, nom, prenom FROM ".TABLE_PREFIX."_utilisateurs WHERE (etat!='inactif' AND (statut='utilisateur' OR statut='visiteur' OR statut='gestionnaire_utilisateur')) AND login NOT IN (SELECT login FROM ".TABLE_PREFIX."_j_user_area WHERE id_area = '$p_id_area') ORDER BY nom, prenom";
		$res = grr_sql_query($sql);
		$d['nbUserAjoutable'] = grr_sql_count($res);
		if ($res)
			for ($i = 0; ($row3 = grr_sql_row($res, $i)); $i++)
				$utilisateursAjoutable[] = array('login' => $row3[0], 'nom' => $row3[1], 'prenom' => $row3[2]);

		// Groupes ayant accès au domaine restreint
		$sql = "SELECT g.idgroupes, g.nom FROM ".TABLE_PREFIX."_groupes g, ".TABLE_PREFIX."_j_group_area j WHERE (j.id_area='$p_id_area' AND g.idgroupes=j.idgroupes) ORDER BY g.nom";
		$res = grr_sql_query($sql);
		$nombre = grr_sql_count($res);

		if ($res)
			for ($i = 0; ($row2 = grr_sql_row($res, $i)); $i++)
			{
				$groupesExep[] = array('id' => $row2[0], 'nom' => $row2[1]);
			}

		// Groupes pouvant être ajouté
		$sql = "SELECT idgroupes, nom FROM ".TABLE_PREFIX."_groupes WHERE archive = 0 AND idgroupes NOT IN (SELECT idgroupes FROM ".TABLE_PREFIX."_j_group_area WHERE id_area = '$p_id_area') ORDER BY nom";
		$res = grr_sql_query($sql);
		$d['nbUserAjoutable'] = grr_sql_count($res);
		if ($res)
			for ($i = 0; ($row3 = grr_sql_row($res, $i)); $i++)
				$groupesAjoutable[] = array('idgroupe' => $row3[0], 'nom' => $row3[1]);

	}

	echo $twig->render('admin_access_area.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings, 'domaines' => $domaines, 'utilisateursexep' => $utilisateursExep, 'groupesexep' => $groupesExep, 'utilisateursajoutable' => $utilisateursAjoutable, 'groupesajoutable' => $groupesAjoutable));
?>