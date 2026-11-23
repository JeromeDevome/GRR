<?php
/**
 * admin_groupe_edit.php
 * interface de gestion des utilisateurs de l'application GRR
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2026-09-26 12:00$
 * @author    JeromeB
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


$grr_script_name = "admin_groupe_edit.php";

// Accès à la page
if ((SecuAccess::UserLevel(getUserName(), -1) < 6) && (SecuAccess::UserLevel(getUserName(), -1, 'user') !=  1))
{
	showAccessDenied($back);
	exit();
}

// les variables attendues et leur type
$form_vars = array(
    'p_idgroupe' => array('int', 0),
	'p_submit' => array('int', 0),
	'p_nom' => array('string', ''),
	'p_description' => array('string', ''),
	'p_archive' => array('int', 0),
	'p_membres_submit' => array('int', 0), // 1 : enregistrement des membres du groupe
	'p_utilisateurs' => array('array', array()) // logins sélectionnés dans la duallist

);
// récupération des valeurs des variables passées en paramètres
foreach($form_vars as $var => $params)
    $$var = SecuChaine::GetFormVarSecure($var, $params[0], $params[1]);


$groupe = array();
$retry = false;

/** Actions **/
	if($p_submit == 1)
	{
		VerifyModeDemo();

		if ($p_nom == "") // Nom du groupe obligatoire
		{
			$d['enregistrement'] = 3;
			$retry = true;
		}
		else
		{
			if ($p_idgroupe == 0) // Création
			{
				$sql = "SELECT * FROM ".TABLE_PREFIX."_groupes WHERE nom = '".$p_nom."'";
				$res = grr_sql_query($sql);
				$nombreligne = grr_sql_count ($res);
				if ($nombreligne != 0)
				{
					$d['enregistrement'] = 2;
					$retry = true;
				}
				else
				{
					$sql = "INSERT INTO ".TABLE_PREFIX."_groupes SET
					nom='".SecuChaine::ProtectDataSql($p_nom)."',
					description='".SecuChaine::ProtectDataSql($p_description)."',
					archive='".SecuChaine::ProtectDataSql($p_archive)."'";
					if (grr_sql_command($sql) < 0)
						fatal_error(0, get_vocab("msg_login_created_error") . grr_sql_error());
					else
					{
						$d['enregistrement'] = 1;
						// On reste sur le groupe créé (modification, gestion des membres) au lieu d'un nouveau formulaire vide
						$p_idgroupe = (int) grr_sql_insert_id();
					}
				}
			}
			else // Modification
			{
				$sql = "SELECT * FROM ".TABLE_PREFIX."_groupes WHERE nom = '".$p_nom."' AND idgroupes <> '".$p_idgroupe."'";
				$res = grr_sql_query($sql);
				$nombreligne = grr_sql_count ($res);
				if ($nombreligne != 0)
				{
					$d['enregistrement'] = 2;
					$retry = 'yes';
				}
				else
				{
					$sql = "UPDATE ".TABLE_PREFIX."_groupes SET nom='".SecuChaine::ProtectDataSql($p_nom)."',
					description='".SecuChaine::ProtectDataSql($p_description)."',
					archive='".SecuChaine::ProtectDataSql($p_archive)."'
					WHERE idgroupes='".SecuChaine::ProtectDataSql($p_idgroupe)."'";
					if (grr_sql_command($sql) < 0)
						fatal_error(0, get_vocab("message_records_error") . grr_sql_error());
					else
						$d['enregistrement'] = 1;
				}
			}

		}
	}


/** Utilisateurs proposés : comptes actifs, plus les membres actuels quel que soit leur état **/
	$utilisateursMembres = array();
	$utilisateursAjoutables = array();
	if ($p_idgroupe != 0)
	{
		$chargerUtilisateurs = function () use ($p_idgroupe) {
			$liste = array();
			$sql = "SELECT u.login, u.nom, u.prenom, u.etat, ug.idgroupes FROM ".TABLE_PREFIX."_utilisateurs u
				LEFT JOIN ".TABLE_PREFIX."_utilisateurs_groupes ug ON ug.login = u.login AND ug.idgroupes = '".intval($p_idgroupe)."'
				WHERE u.etat = 'actif' OR ug.idgroupes IS NOT NULL
				ORDER BY u.nom, u.prenom";
			$res = grr_sql_query($sql);
			if (!$res)
				fatal_error(0, get_vocab('message_records_error') . grr_sql_error());
			for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
				$liste[$row[0]] = array('login' => $row[0], 'nom' => $row[1], 'prenom' => $row[2], 'actif' => ($row[3] == 'actif'), 'membre' => ($row[4] !== null));
			grr_sql_free($res);
			return $liste;
		};
		$utilisateurs = $chargerUtilisateurs();

		/** Enregistrement des membres **/
		if ($p_membres_submit == 1)
		{
			VerifyModeDemo();

			// Seuls les logins proposés dans la liste sont pris en compte
			$selection = array_intersect(array_unique(array_map(array('SecuChaine', 'CleanLogin'), $p_utilisateurs)), array_keys($utilisateurs));
			$membresActuels = array_keys(array_filter($utilisateurs, function ($u) { return $u['membre']; }));
			$aAjouter = array_diff($selection, $membresActuels);
			$aRetirer = array_diff($membresActuels, $selection);

			foreach ($aRetirer as $login)
			{
				$sql = "DELETE FROM ".TABLE_PREFIX."_utilisateurs_groupes WHERE login='".SecuChaine::ProtectDataSql($login)."' AND idgroupes='".intval($p_idgroupe)."'";
				if (grr_sql_command($sql) < 0)
					fatal_error(0, get_vocab('message_records_error') . grr_sql_error());
			}
			foreach ($aAjouter as $login)
			{
				$sql = "INSERT INTO ".TABLE_PREFIX."_utilisateurs_groupes SET login='".SecuChaine::ProtectDataSql($login)."', idgroupes='".intval($p_idgroupe)."'";
				if (grr_sql_command($sql) < 0)
					fatal_error(0, get_vocab('message_records_error') . grr_sql_error());
			}

			// Répercute les droits du groupe (sites, domaines, ressources) sur ses membres
			if (!empty($aAjouter) || !empty($aRetirer))
			{
				synchro_groupe(intval($p_idgroupe), 0);
				$utilisateurs = $chargerUtilisateurs();
			}

			$d['enregistrement'] = 1;
		}

		foreach ($utilisateurs as $utilisateur)
		{
			if ($utilisateur['membre'])
				$utilisateursMembres[] = $utilisateur;
			else
				$utilisateursAjoutables[] = $utilisateur;
		}
	}

/** Affichage de la page **/
	$trad['TitrePage']	= $trad['admin_groupe'];

	// On appelle les informations du groupe pour les afficher
	if ($p_idgroupe != 0)
	{
		$res = grr_sql_query("SELECT idgroupes, nom, description, archive FROM ".TABLE_PREFIX."_groupes WHERE idgroupes='$p_idgroupe'");
		if (!$res)
			fatal_error(0, get_vocab('message_records_error'));
		$groupe = grr_sql_row_keyed($res, 0);
		grr_sql_free($res);

		$trad['SousTitrePage'] = get_vocab('change');
	}
	else
	{
		$trad['SousTitrePage'] = get_vocab('add');
	}

	if($retry == true)
	{
		$groupe["nom"] = $p_nom;
		$groupe["description"] = $p_description;
		$groupe["archive"] = $p_archive;
	}

	echo $twig->render('admin_groupe_edit.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings, 'groupe' => $groupe, 'utilisateursmembres' => $utilisateursMembres, 'utilisateursajoutables' => $utilisateursAjoutables));
?>