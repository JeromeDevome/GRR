<?php
/**
 * admin_corresp_statut.php
 * interface de gestion de la correspondance entre profil LDAP et statut GRR
 * Dernière modification : $Date: 2017-12-16 14:00$
 * @author    Laurent Delineau & JeromeB & Christian Daviau
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

$grr_script_name = "admin_corresp_statut.php";

if (Settings::get("sso_ac_corr_profil_statut") != 'y')
{
	showAccessDenied($back);
	exit();
}
check_access(5, $back);


get_vocab_admin('admin_corresp_statut');
get_vocab_admin('admin_corresp_statut_desc');
get_vocab_admin('ajout_correspondance_profil_statut');
get_vocab_admin('code_fonction');
get_vocab_admin('deux_points');
get_vocab_admin('libelle_fonction');
get_vocab_admin('statut_grr');
get_vocab_admin('statut_visitor');
get_vocab_admin('statut_user');
get_vocab_admin('statut_user_administrator');
get_vocab_admin('statut_administrator');
get_vocab_admin('statut_grr_modif');
get_vocab_admin('OK');
get_vocab_admin('edit');
get_vocab_admin('delete');
get_vocab_admin('confirm_del');
get_vocab_admin('cancel');

$identifiantsLDAP = array();
//
// Ajout d'une correspondance fonction/statut
//
$msg = "";
if ( isset($_GET['action_add']) && ($_GET['action_add'] == 'yes'))
{
	if (($_POST['codefonc'] != "") && ($_POST['libfonc'] != "") && ($_POST['statutgrr'] != ""))
	{
		$sql = "INSERT INTO ".TABLE_PREFIX."_correspondance_statut (code_fonction, libelle_fonction, statut_grr) VALUES ('".strtoupper(protect_data_sql($_POST['codefonc']))."', '".ucfirst(protect_data_sql($_POST['libfonc']))."','".$_POST['statutgrr']."')";
		if (grr_sql_command($sql) < 0)
			fatal_error(0, "<p>" . grr_sql_error());
		else
			$msg = get_vocab("message_records");
	}
	else
		$msg = get_vocab("champs_non_remplis");
}
//
// Modification d'une correspondance fonction/statut
//
if (isset($_GET['action_mod']) && ($_GET['action_mod'] = 'yes'))
{
	if (isset($_POST['idselect']))
	{
		$select = "statut".$_POST['idselect'];
		if (($_POST['idfonc'] != "") && ($_POST[$select] != ""))
		{
			$sql = "UPDATE ".TABLE_PREFIX."_correspondance_statut SET statut_grr = '".$_POST[$select]."' WHERE id='".$_POST['idfonc']."'";
			if (grr_sql_command($sql) < 0)
				fatal_error(0, "<p>" . grr_sql_error());
			else
				$msg = get_vocab("message_records");
		}
		else
			$msg = get_vocab("champs_non_remplis");
	}
}
//
// Suppression d'une correspondance fonction/statut
//
if ((isset($_GET['action_del']) && isset($_GET['js_confirmed'])) && ($_GET['js_confirmed'] == 1) && ($_GET['action_del'] = 'yes'))
{
	$sql = "DELETE FROM ".TABLE_PREFIX."_correspondance_statut WHERE id='".$_GET['id']."'";
	if (grr_sql_command($sql) < 0)
		fatal_error(0, "<p>" . grr_sql_error());
	else
		$msg = get_vocab("message_records");
}


$sql = "SELECT code_fonction, libelle_fonction, statut_grr, id FROM  ".TABLE_PREFIX."_correspondance_statut";
$res = grr_sql_query($sql);
$nb_lignes = grr_sql_count($res);

if ($nb_lignes == 0) // Si aucune ligne à afficher
	get_vocab_admin('aucune_correspondance');
else
{
	// S'il y a des lignes à afficher
	if ($res)
	{
		for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
		{
			$identifiantsLDAP[] = array('i' => $i, 'id' => $row[3], 'codefonc' => $row[0], 'libfonc' => $row[1],'statutgrr' => $row[2]);
		}
	}

}

// Affichage d'un pop-up
affiche_pop_up($msg,"admin");

echo $twig->render('admin_corresp_statut.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings, 'identifiantsldap' => $identifiantsLDAP));
?>