<?php
/**
 * admin_groupe_edit.php
 * interface de gestion des utilisateurs de l'application GRR
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2022-05-18 22:00$
 * @author    Laurent Delineau & JeromeB
 * @copyright Copyright 2003-2022 Team DEVOME - JeromeB
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

if ((authGetUserLevel(getUserName(), -1) < 6) && (authGetUserLevel(getUserName(), -1, 'user') !=  1))
{
	showAccessDenied($back);
	exit();
}

$idgroupe = isset($_GET["idgroupe"]) && $_GET["idgroupe"] != '' ? $_GET["idgroupe"] : 0;
$valid = isset($_GET["valid"]) ? $_GET["valid"] : NULL;

$user_nom = '';
$user_description= '';
$user_archive = '';
$display = "";
$retry = '';

$groupe = array();

if ($valid == "yes")
{
	// Restriction dans le cas d'une démo
	VerifyModeDemo();
	$reg_nom = isset($_GET["reg_nom"]) ? $_GET["reg_nom"] : NULL;
	$reg_description = isset($_GET["reg_description"]) ? $_GET["reg_description"] : NULL;
	$reg_archive= isset($_GET["reg_archive"]) ? $_GET["reg_archive"] : "off";

	if($reg_archive == "on")
		$archive = 1;
	else
		$archive = 0;

	if ($reg_nom == '')
	{
		$d['enregistrement'] = 3;
		$retry = 'yes';
	}
	else
	{
		// Création d'un groupe
		if ($idgroupe == 0)
		{

			$sql = "SELECT * FROM ".TABLE_PREFIX."_groupes WHERE nom = '".$reg_nom."'";
			$res = grr_sql_query($sql);
			$nombreligne = grr_sql_count ($res);
			if ($nombreligne != 0)
			{
				$d['enregistrement'] = 2;
				$retry = 'yes';
			}
			else
			{
				$sql = "INSERT INTO ".TABLE_PREFIX."_groupes SET
				nom='".protect_data_sql($reg_nom)."',
				description='".protect_data_sql($reg_description)."',
				archive='".protect_data_sql($archive)."'";
				if (grr_sql_command($sql) < 0)
					fatal_error(0, get_vocab("msg_login_created_error") . grr_sql_error());
				else
					$d['enregistrement'] = 1;
			}
		}
		// Modification d'un groupe
		else
		{
			$sql = "SELECT * FROM ".TABLE_PREFIX."_groupes WHERE nom = '".$reg_nom."' AND idgroupes <> '".$idgroupe."'";
			$res = grr_sql_query($sql);
			$nombreligne = grr_sql_count ($res);
			if ($nombreligne != 0)
			{
				$d['enregistrement'] = 2;
				$retry = 'yes';
			}
			else
			{
				$sql = "UPDATE ".TABLE_PREFIX."_groupes SET nom='".protect_data_sql($reg_nom)."',
				description='".protect_data_sql($reg_description)."',
				archive='".protect_data_sql($archive)."'
				WHERE idgroupes='".protect_data_sql($idgroupe)."'";
				if (grr_sql_command($sql) < 0)
					fatal_error(0, get_vocab("message_records_error") . grr_sql_error());
				else
					$d['enregistrement'] = 1;
			}
		}
	}
	if ($retry == 'yes')
	{
		$user_nom = $reg_nom;
		$user_description = $reg_description;
		$user_archive = $reg_archive;
	}
}
// On appelle les informations de l'utilisateur pour les afficher :
if (isset($idgroupe) && ($idgroupe != 0))
{
	$res = grr_sql_query("SELECT idgroupes, nom, description, archive FROM ".TABLE_PREFIX."_groupes WHERE idgroupes='$idgroupe'");
	if (!$res)
		fatal_error(0, get_vocab('message_records_error'));
	$groupe = grr_sql_row_keyed($res, 0);
	grr_sql_free($res);
}
if ((authGetUserLevel(getUserName(), -1) < 1) && (Settings::get("authentification_obli") == 1))
{
	showAccessDenied($back);
	exit();
}


$trad['dDisplay'] = $display;

get_vocab_admin('groupe_add');
get_vocab_admin('groupe_modifier');

get_vocab_admin("name");
get_vocab_admin("description");
get_vocab_admin("archiver");


get_vocab_admin("groupe_exist");
get_vocab_admin("please_enter_name");
get_vocab_admin("message_records");

get_vocab_admin("back");
get_vocab_admin("save");

echo $twig->render('admin_groupe_edit.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings, 'groupe' => $groupe));
?>