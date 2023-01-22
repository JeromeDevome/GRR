<?php
/**
 * admin_site.php
 * Interface d'accueil de Gestion des sites de l'application GRR
 * Dernière modification : $Date: 2017-12-16 14:00$
 * @author    JeromeB & Laurent Delineau & Marc-Henri PAMISEUX
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


/**
 * Compte le nombre de sites définis
 * @return integer number of rows
 */
function count_sites()
{
	$sql = "SELECT COUNT(*)
	FROM ".TABLE_PREFIX."_site";
	$res = grr_sql_query($sql);
	if ($res)
	{
		$sites = grr_sql_row($res,0);
		if (is_array($sites))
			return $sites[0];
		else
			$trad['dMesgSysteme'] = "Une erreur est survenue pendant le comptage des sites.";
	}
	else
		$trad['dMesgSysteme'] = "Une erreur est survenue pendant la préparation de la requète de comptage des sites.";
}


function create_site($id_site)
{
	global $twig, $menuAdminT, $menuAdminTN2, $d, $trad, $AllSettings;

	$trad['dAction'] = 'create';

	if ((isset($_POST['back']) || isset($_GET['back'])))
	{
		// On affiche le tableau des sites
		read_sites();
		exit();
	}
	// Initialisation des variables du formulaire
	if (!isset($id_site))
		$site['id_site'] = isset($_POST['id']) ? $_POST['id'] :  NULL;
	if (!isset($sitecode))
		$sitecode = isset($_POST['sitecode']) ? $_POST['sitecode'] : NULL;
	if (!isset($sitename))
		$sitename = isset($_POST['sitename']) ? $_POST['sitename'] :  NULL;
	if (!isset($adresse_ligne1))
		$adresse_ligne1 = isset($_POST['adresse_ligne1']) ? $_POST['adresse_ligne1'] :  NULL;
	if (!isset($adresse_ligne2))
		$adresse_ligne2 = isset($_POST['adresse_ligne2']) ? $_POST['adresse_ligne2'] :  NULL;
	if (!isset($adresse_ligne3))
		$adresse_ligne3 = isset($_POST['adresse_ligne3']) ? $_POST['adresse_ligne3'] :  NULL;
	if (!isset($cp))
		$cp = isset($_POST['cp']) ? $_POST['cp'] :  NULL;
	if (!isset($ville))
		$ville = isset($_POST['ville']) ? $_POST['ville'] :  NULL;
	if (!isset($pays))
		$pays = isset($_POST['pays']) ? $_POST['pays'] :  NULL;
	if (!isset($tel))
		$tel = isset($_POST['tel']) ? $_POST['tel'] :  NULL;
	if (!isset($fax))
		$fax = isset($_POST['fax']) ? $_POST['fax'] :  NULL;
	// On affiche le formulaire de saisie quand l'appel de la fonction ne provient pas de la validation de ce même formulaire
	if ((! (isset($_POST['save']) || isset($_GET['save']))) && ($id_site==0))
	{
		get_vocab_admin('addsite');
		get_vocab_admin('required');
		get_vocab_admin('site_code');
		get_vocab_admin('site_name');
		get_vocab_admin('site_adresse_ligne1');
		get_vocab_admin('site_adresse_ligne2');
		get_vocab_admin('site_adresse_ligne3');
		get_vocab_admin('site_cp');
		get_vocab_admin('site_ville');
		get_vocab_admin('site_pays');
		get_vocab_admin('site_tel');
		get_vocab_admin('site_fax');

		get_vocab_admin('save');
		get_vocab_admin('back');
	}
	else
	{
		// On vérifie que le code et le nom du site ont été renseignés
		if ($sitecode == '' || $sitecode == NULL || $sitename == '' || $sitename == NULL)
		{
			$_POST['save'] = 'no';
			$_GET['save'] = 'no';
			echo '<span class="avertissement">'.get_vocab('required').'</span>';
		}
		// Sauvegarde du record
		if ((isset($_POST['save']) && ($_POST['save'] != 'no')) || ((isset($_GET['save'])) && ($_GET['save'] != 'no')))
		{
			$sql="INSERT INTO ".TABLE_PREFIX."_site
			SET sitecode='".strtoupper(protect_data_sql($sitecode))."',
			sitename='".protect_data_sql($sitename)."',
			adresse_ligne1='".protect_data_sql($adresse_ligne1)."',
			adresse_ligne2='".protect_data_sql($adresse_ligne2)."',
			adresse_ligne3='".protect_data_sql($adresse_ligne3)."',
			cp='".protect_data_sql($cp)."',
			ville='".strtoupper(protect_data_sql($ville))."',
			pays='".strtoupper(protect_data_sql($pays))."',
			tel='".protect_data_sql($tel)."',
			fax='".protect_data_sql($fax)."'";
			if (grr_sql_command($sql) < 0)
				fatal_error(0,'<p>'.grr_sql_error().'</p>');
			mysqli_insert_id($GLOBALS['db_c']);
		}
		// On affiche le tableau des sites
		read_sites();
	}

	echo $twig->render('admin_site_modif.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings));
}


function read_sites()
{
	global $twig, $menuAdminT, $menuAdminTN2, $d, $trad, $AllSettings;

	$sites = array();
	get_vocab_admin('admin_site');
	get_vocab_admin('admin_site_explications');
	get_vocab_admin('display_add_site');

	get_vocab_admin('action');
	get_vocab_admin('site_code');
	get_vocab_admin('site_name');
	get_vocab_admin('site_cp');
	get_vocab_admin('site_ville');

	get_vocab_admin('supprimer_site');
	get_vocab_admin('confirm_del');
	get_vocab_admin('cancel');
	get_vocab_admin('delete');

	//<a href="admin_site.php?action=delete&amp;id='.$id.'&amp;confirm=yes">' . get_vocab('YES') . '!</a>


	if (count_sites() > 0)
	{
		$sql = "SELECT id,sitecode,sitename,cp,ville FROM ".TABLE_PREFIX."_site ORDER BY sitename,ville,id";
		$res = grr_sql_query($sql);
		if ($res)
		{
			for ($i = 0; ($row=grr_sql_row($res,$i));$i++){
				$sites[] = array('idsite' => $row[0], 'code' => $row[1], 'nomsite' => $row[2], 'cp' => $row[3], 'ville' => $row[4]);
			}
		}
		else
			$trad['dMesgSysteme'] = 'Une erreur est survenue pendant la préparation de la requète de lecture des sites.';
	}

	echo $twig->render('admin_site.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings, 'sites' => $sites));
}


function update_site($id)
{
	global $twig, $menuAdminT, $menuAdminTN2, $trad, $AllSettings;

	if ((isset($_POST['back']) || isset($_GET['back'])))
	{
		read_sites();
		exit();
	}

	$trad['addsite'] = get_vocab('modifier_site');
	$trad['dIdSite'] = $id;
	$trad['dAction'] = 'update';
	get_vocab_admin('required');
	get_vocab_admin('site_code');
	get_vocab_admin('site_name');
	get_vocab_admin('site_adresse_ligne1');
	get_vocab_admin('site_adresse_ligne2');
	get_vocab_admin('site_adresse_ligne3');
	get_vocab_admin('site_cp');
	get_vocab_admin('site_ville');
	get_vocab_admin('site_pays');
	get_vocab_admin('site_tel');
	get_vocab_admin('site_fax');

	get_vocab_admin('save');
	get_vocab_admin('back');
	
	// On affiche le formulaire de saisie quand l'appel de la fonction ne provient pas de la validation de ce même formulaire
	if (!(isset($_POST['save']) || isset($_GET['save'])))
	{

		// Initialisation
		$res = grr_sql_query("SELECT * FROM ".TABLE_PREFIX."_site WHERE id='".$id."'");
		if (!$res)
			fatal_error(0,'<p>'.grr_sql_error().'</p>');
		$row = grr_sql_row_keyed($res, 0);
		grr_sql_free($res);
		$site['code'] = $row['sitecode'];
		$site['nom'] = $row['sitename'];
		$site['adresse_ligne1'] = $row['adresse_ligne1'];
		$site['adresse_ligne2'] = $row['adresse_ligne2'];
		$site['adresse_ligne3'] = $row['adresse_ligne3'];
		$site['cp'] = $row['cp'];
		$site['ville'] = $row['ville'];
		$site['pays'] = $row['pays'];
		$site['tel'] = $row['tel'];
		$site['fax'] = $row['fax'];

	}
	else // Sinon, il faut valider le formulaire
	{
		if (!isset($id))
			$id = isset($_POST['id']) ? $_POST['id'] :  NULL;
		if (!isset($sitecode))
			$sitecode = isset($_POST['sitecode']) ? $_POST['sitecode'] : NULL;
		if (!isset($sitename))
			$sitename = isset($_POST['sitename']) ? $_POST['sitename'] :  NULL;
		if (!isset($adresse_ligne1))
			$adresse_ligne1 = isset($_POST['adresse_ligne1']) ? $_POST['adresse_ligne1'] :  NULL;
		if (!isset($adresse_ligne2))
			$adresse_ligne2 = isset($_POST['adresse_ligne2']) ? $_POST['adresse_ligne2'] :  NULL;
		if (!isset($adresse_ligne3))
			$adresse_ligne3 = isset($_POST['adresse_ligne3']) ? $_POST['adresse_ligne3'] :  NULL;
		if (!isset($cp))
			$cp = isset($_POST['cp']) ? $_POST['cp'] :  NULL;
		if (!isset($ville))
			$ville = isset($_POST['ville']) ? $_POST['ville'] :  NULL;
		if (!isset($pays))
			$pays = isset($_POST['pays']) ? $_POST['pays'] :  NULL;
		if (!isset($tel))
			$tel = isset($_POST['tel']) ? $_POST['tel'] :  NULL;
		if (!isset($fax))
			$fax = isset($_POST['fax']) ? $_POST['fax'] :  NULL;
		// On vérifie que le code et le nom du site ont été renseignés
		if ($sitecode == '' || $sitecode == NULL || $sitename == '' || $sitename==NULL)
		{
			$_POST['save'] = 'no';
			$_GET['save'] = 'no';
			echo '<span class="avertissement">'.get_vocab('required').'</span>';
		}
		// Sauvegarde du record
		if ((isset($_POST['save']) && ($_POST['save']!='no')) || ((isset($_GET['save'])) && ($_GET['save']!='no')))
		{
			$sql = "UPDATE ".TABLE_PREFIX."_site
			SET sitecode='".strtoupper(protect_data_sql($sitecode))."',
			sitename='".protect_data_sql($sitename)."',
			adresse_ligne1='".protect_data_sql($adresse_ligne1)."',
			adresse_ligne2='".protect_data_sql($adresse_ligne2)."',
			adresse_ligne3='".protect_data_sql($adresse_ligne3)."',
			cp='".protect_data_sql($cp)."',
			ville='".strtoupper(protect_data_sql($ville))."',
			pays='".strtoupper(protect_data_sql($pays))."',
			tel='".protect_data_sql($tel)."',
			fax='".protect_data_sql($fax)."'
			WHERE id='".$id."'";
			if (grr_sql_command($sql) < 0)
				fatal_error(0,'<p>'.grr_sql_error().'</p>');
			mysqli_insert_id($GLOBALS['db_c']);
		}
		// On affiche le tableau des sites
		read_sites();
	}

	echo $twig->render('admin_site_modif.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings, 'site' => $site));
}


function delete_site($id)
{
	if (isset($_GET['confirm']))
	{
		if ($_GET['confirm'] == 'yes')
		{
			grr_sql_command("delete from ".TABLE_PREFIX."_site where id='".$_GET['id']."'");
			grr_sql_command("delete from ".TABLE_PREFIX."_j_site_area where id_site='".$_GET['id']."'");
			grr_sql_command("delete from ".TABLE_PREFIX."_j_useradmin_site where id_site='".$_GET['id']."'");
			grr_sql_command("update ".TABLE_PREFIX."_utilisateurs set default_site = '-1' where default_site='".$_GET['id']."'");
			$test = grr_sql_query1("select VALUE from ".TABLE_PREFIX."_setting where NAME='default_site'");
			if ($test == $_GET['id'])
				grr_sql_command("delete from ".TABLE_PREFIX."_setting where NAME='default_site'");
			// On affiche le tableau des sites
			read_sites();
		}
		else // On affiche le tableau des sites
			read_sites();
	}
}


function check_right($id)
{
	echo 'Vous voulez vérifier les droits pour l\'identifiant '.$id;
}


	$grr_script_name = 'admin_site.php';

	if (authGetUserLevel(getUserName(), -1, 'site') < 4)
	{
		showAccessDenied($back);
		exit();
	}

	if ((isset($_GET['msg'])) && isset($_SESSION['displ_msg']) && ($_SESSION['displ_msg'] == 'yes') )
	{
		$msg = $_GET['msg'];
		affiche_pop_up($msg,'admin');
	}
	else
		$msg = '';

	// Lecture des paramètres passés à la page
	$id_site = isset($_POST['id']) ? $_POST['id'] : (isset($_GET['id']) ? $_GET['id'] : NULL);
	$action = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : NULL);

	if ($action == NULL)
		$action = 'read';

	switch($action)
	{
		case 'create':
		create_site($id_site);
		break;
		case 'read':
		read_sites();
		break;
		case 'update':
		update_site($id_site);
		break;
		case 'delete':
		delete_site($id_site);
		break;
		case 'right':
		check_right($id_site);
		break;
		default:
		read_sites();
		break;
	}

?>