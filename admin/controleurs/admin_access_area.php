<?php
/**
 * admin_access_area.php
 * Interface de gestion des accès restreints aux domaines
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2017-12-16 14:00$
 * @author    Laurent Delineau & JeromeB
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

$grr_script_name = "admin_access_area.php";

$id_area = isset($_POST["id_area"]) ? $_POST["id_area"] : (isset($_GET["id_area"]) ? $_GET["id_area"] : NULL);
if (!isset($id_area))
	settype($id_area,"integer");
$reg_user_login = isset($_POST["reg_user_login"]) ? $_POST["reg_user_login"] : NULL;
$reg_multi_user_login = isset($_POST["reg_multi_user_login"]) ? $_POST["reg_multi_user_login"] : NULL;
$test_user =  isset($_POST["reg_multi_user_login"]) ? "multi" : (isset($_POST["reg_user_login"]) ? "simple" : NULL);
$action = isset($_GET["action"]) ? $_GET["action"] : NULL;
$msg = '';

check_access(4, $back);

// Si la table j_user_area est vide, il faut modifier la requête
$test_grr_j_user_area = grr_sql_count(grr_sql_query("SELECT * from ".TABLE_PREFIX."_j_user_area"));

if ($test_user == "multi")
{
	foreach ($reg_multi_user_login as $valeur)
	{
	// On commence par vérifier que le professeur n'est pas déjà présent dans cette liste.
		if ($id_area != -1)
		{
			if (authGetUserLevel(getUserName(), $id_area, 'area') < 4)
			{
				showAccessDenied($back);
				exit();
			}
			$sql = "SELECT * FROM ".TABLE_PREFIX."_j_user_area WHERE (login = '".$valeur."' and id_area = '$id_area')";
			$res = grr_sql_query($sql);
			$test = grr_sql_count($res);
			if ($test > 0)
				$msg = get_vocab("warning_exist");
			else
			{
				if ($valeur != '')
				{
					$sql = "INSERT INTO ".TABLE_PREFIX."_j_user_area SET login= '$valeur', id_area = '$id_area'";
					if (grr_sql_command($sql) < 0)
						fatal_error(1, "<p>" . grr_sql_error());
					else
						$msg= get_vocab("add_multi_user_succeed");
				}
			}
		}
	}
}


if ($test_user == "simple")
{
   // On commence par vérifier que le professeur n'est pas déjà présent dans cette liste.
	if ($id_area != -1)
	{
		if (authGetUserLevel(getUserName(), $id_area, 'area') < 4)
		{
			showAccessDenied($back);
			exit();
		}
		$sql = "SELECT * FROM ".TABLE_PREFIX."_j_user_area WHERE (login = '$reg_user_login' and id_area = '$id_area')";
		$res = grr_sql_query($sql);
		$test = grr_sql_count($res);
		if ($test > 0)
			$msg = get_vocab("warning_exist");
		else
		{
			if ($reg_user_login != '')
			{
				$sql = "INSERT INTO ".TABLE_PREFIX."_j_user_area SET login= '$reg_user_login', id_area = '$id_area'";
				if (grr_sql_command($sql) < 0)
					fatal_error(1, "<p>" . grr_sql_error());
				else
					$msg = get_vocab("add_user_succeed");
			}
		}
	}
}

if ($action=='del_user')
{
	if (authGetUserLevel(getUserName(), $id_area, 'area') < 4)
	{
		showAccessDenied($back);
		exit();
	}
	unset($login_user);
	$login_user = $_GET["login_user"];
	$sql = "DELETE FROM ".TABLE_PREFIX."_j_user_area WHERE (login='$login_user' and id_area = '$id_area')";
	if (grr_sql_command($sql) < 0)
		fatal_error(1, "<p>" . grr_sql_error());
	else
		$msg = get_vocab("del_user_succeed");
}

if (empty($id_area))
	$id_area = -1;


get_vocab_admin('admin_access_area');
get_vocab_admin('areas');
get_vocab_admin('select');
get_vocab_admin('add_user_to_list');
get_vocab_admin('user_area_list');
get_vocab_admin('add_multiple_user_to_list');

get_vocab_admin('add');

$trad['dIdDomaine'] = $id_area;

affiche_pop_up($msg,"admin");

$this_area_name = "";
$utilisateursExep = array ();
$utilisateursAjoutable = array ();
$domaines = array ();

# Show all areas
$existe_domaine = 'no';

$sql = "select id, area_name from ".TABLE_PREFIX."_area where access='r' order by area_name";
$res = grr_sql_query($sql);
$nb = grr_sql_count($res);
if ($res)
	for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
	{
		// on affiche que les domaines que l'utilisateur connecté a le droit d'administrer
		if (authGetUserLevel(getUserName(),$row[0],'area') >= 4)
		{
			$domaines[] = array('id' => $row[0], 'nom' => $row[1]);
			$existe_domaine = 'yes';
		}
	}


$this_area_name = grr_sql_query1("select area_name from ".TABLE_PREFIX."_area where id=$id_area");
# Show area :
if ($id_area != -1)
{

	$sql = "SELECT u.login, u.nom, u.prenom FROM ".TABLE_PREFIX."_utilisateurs u, ".TABLE_PREFIX."_j_user_area j WHERE (j.id_area='$id_area' and u.login=j.login)  order by u.nom, u.prenom";
	$res = grr_sql_query($sql);
	$nombre = grr_sql_count($res);

	if ($res)
		for ($i = 0; ($row2 = grr_sql_row($res, $i)); $i++)
		{
			$utilisateursExep[] = array('login' => $row2[0], 'nom' => $row2[1], 'prenom' => $row2[2]);
		}

	// Pour mysql >= 4.1
	$sql = "SELECT login, nom, prenom FROM ".TABLE_PREFIX."_utilisateurs WHERE (etat!='inactif' and (statut='utilisateur' or statut='visiteur' or statut='gestionnaire_utilisateur')) AND login NOT IN (SELECT login FROM ".TABLE_PREFIX."_j_user_area WHERE id_area = '$id_area') order by nom, prenom";
	// Pour mysql < 4.1
	//$sql = "SELECT DISTINCT u.login, u.nom, u.prenom FROM ".TABLE_PREFIX."_utilisateurs u left join ".TABLE_PREFIX."_j_user_area on ".TABLE_PREFIX."_j_user_area.login=u.login WHERE ((etat!='inactif' and (statut='utilisateur' or statut='visiteur' or statut='gestionnaire_utilisateur')) AND (".TABLE_PREFIX."_j_user_area.login is null or (".TABLE_PREFIX."_j_user_area.login=u.login and ".TABLE_PREFIX."_j_user_area.id_area!=".$id_area.")))  order by u.nom, u.prenom";
	$res = grr_sql_query($sql);
	$trad['dNbUserAjoutable'] = grr_sql_count($res);
	if ($res)
		for ($i = 0; ($row3 = grr_sql_row($res, $i)); $i++)
			$utilisateursAjoutable[] = array('login' => $row3[0], 'nom' => $row3[1], 'prenom' => $row3[2]);

}

	echo $twig->render('admin_access_area.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings, 'domaines' => $domaines, 'utilisateursexep' => $utilisateursExep, 'utilisateursajoutable' => $utilisateursAjoutable));
?>