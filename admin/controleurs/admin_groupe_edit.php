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
	'p_archive' => array('int', 0)

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
						$d['enregistrement'] = 1;
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

	echo $twig->render('admin_groupe_edit.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings, 'groupe' => $groupe));
?>