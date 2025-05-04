<?php
/**
 * admin_access_site.php
 * Interface de gestion des accès restreints aux sites
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2025-05-03 14:00$
 * @author    Laurent Delineau & JeromeB
 * @copyright Copyright 2003-2025 Team DEVOME - JeromeB
 * @link      http://www.gnu.org/licenses/licenses.html
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 */

$grr_script_name = "admin_access_site.php";

$id_site = isset($_POST["id_site"]) ? $_POST["id_site"] : (isset($_GET["id_site"]) ? $_GET["id_site"] : NULL);
if (!isset($id_site))
	settype($id_site,"integer");
$reg_user_login = isset($_POST["reg_user_login"]) ? $_POST["reg_user_login"] : NULL;
$reg_groupe = isset($_POST["reg_groupe"]) ? $_POST["reg_groupe"] : NULL;
$reg_multi_user_login = isset($_POST["reg_multi_user_login"]) ? $_POST["reg_multi_user_login"] : NULL;
$test_user =  isset($_POST["reg_multi_user_login"]) ? "multi" : (isset($_POST["reg_user_login"]) ? "simple" : NULL);
$action = isset($_GET["action"]) ? $_GET["action"] : NULL;
if($action == NULL)
	$action = isset($_POST["action"]) ? $_POST["action"] : NULL;
$msg = '';

check_access(4, $back);

if ($test_user == "multi")
{
	foreach ($reg_multi_user_login as $valeur)
	{
	// On commence par vérifier que l'utilisateur n'est pas déjà présent dans cette liste.
		if ($id_site != -1)
		{
			if (authGetUserLevel(getUserName(), $id_site, 'site') < 4)
			{
				showAccessDenied($back);
				exit();
			}
			$sql = "SELECT * FROM ".TABLE_PREFIX."_j_user_site WHERE (login = '".$valeur."' and id_site = '$id_site')";
			$res = grr_sql_query($sql);
			$test = grr_sql_count($res);
			if ($test > 0)
				$msg = get_vocab("warning_exist");
			else
			{
				if ($valeur != '')
				{
					$sql = "INSERT INTO ".TABLE_PREFIX."_j_user_site SET login= '$valeur', id_site = '$id_site'";
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
   // On commence par vérifier que l'utilisateur n'est pas déjà présent dans cette liste.
	if ($id_site != -1)
	{
		if (authGetUserLevel(getUserName(), $id_site, 'site') < 4)
		{
			showAccessDenied($back);
			exit();
		}
		$sql = "SELECT * FROM ".TABLE_PREFIX."_j_user_site WHERE (login = '$reg_user_login' and id_site = '$id_site')";
		$res = grr_sql_query($sql);
		$test = grr_sql_count($res);
		if ($test > 0)
			$msg = get_vocab("warning_exist");
		else
		{
			if ($reg_user_login != '')
			{
				$sql = "INSERT INTO ".TABLE_PREFIX."_j_user_site SET login= '$reg_user_login', id_site = '$id_site'";
				if (grr_sql_command($sql) < 0)
					fatal_error(1, "<p>" . grr_sql_error());
				else
					$msg = get_vocab("add_user_succeed");
			}
		}
	}
}

if ($action == "add_groupe")
{
   // On commence par vérifier que le groupe n'est pas déjà présent dans cette liste.
	if ($id_site != -1)
	{
		if (authGetUserLevel(getUserName(), $id_site, 'site') < 4)
		{
			showAccessDenied($back);
			exit();
		}
		$sql = "SELECT * FROM ".TABLE_PREFIX."_j_group_site WHERE (idgroupes = '$reg_groupe' and id_site = '$id_site')";
		$res = grr_sql_query($sql);
		$test = grr_sql_count($res);
		if ($test > 0)
			$msg = get_vocab("warning_exist");
		else
		{
			if ($reg_groupe != '')
			{
				$sql = "INSERT INTO ".TABLE_PREFIX."_j_group_site SET idgroupes= '$reg_groupe', id_site = '$id_site'";
				if (grr_sql_command($sql) < 0)
					fatal_error(1, "<p>" . grr_sql_error());
				else
					$msg = get_vocab("add_user_succeed");

				synchro_groupe($reg_groupe, 1);
			}
		}
	}
}

if ($action=='del_user')
{
	if (authGetUserLevel(getUserName(), $id_site, 'site') < 4)
	{
		showAccessDenied($back);
		exit();
	}
	unset($login_user);
	$login_user = $_GET["login_user"];
	$sql = "DELETE FROM ".TABLE_PREFIX."_j_user_site WHERE (login='$login_user' and id_site = '$id_site')";
	if (grr_sql_command($sql) < 0)
		fatal_error(1, "<p>" . grr_sql_error());
	else
		$msg = get_vocab("del_user_succeed");

} elseif ($action=='del_groupe')
{
	if (authGetUserLevel(getUserName(), $id_site, 'site') < 4)
	{
		showAccessDenied($back);
		exit();
	}
	unset($login_user);
	$groupe = $_GET["groupe"];
	$sql = "DELETE FROM ".TABLE_PREFIX."_j_group_site WHERE (idgroupes='$groupe' and id_site = '$id_site')";
	if (grr_sql_command($sql) < 0)
		fatal_error(1, "<p>" . grr_sql_error());
	else
		$msg = get_vocab("del_user_succeed");

	synchro_groupe($groupe, 1);
}

if (empty($id_site))
	$id_site = -1;


$trad = $vocab;
$d['idSite'] = $id_site;

affiche_pop_up($msg,"admin");

$this_site_name = "";
$utilisateursExep = array ();
$utilisateursAjoutable = array ();
$groupesExep = array();
$groupesAjoutable = array();
$sites = array ();

# Show all sites
$existe_site = 'no';

$sql = "select id, sitename from ".TABLE_PREFIX."_site where access='r' order by sitename";
$res = grr_sql_query($sql);
$nb = grr_sql_count($res);
if ($res)
	for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
	{
		// on affiche que les sites que l'utilisateur connecté a le droit d'administrer
		if (authGetUserLevel(getUserName(),$row[0],'site') >= 4)
		{
			$sites[] = array('id' => $row[0], 'nom' => $row[1]);
			$existe_site = 'yes';
		}
	}


$this_site_name = grr_sql_query1("select sitename from ".TABLE_PREFIX."_site where id=$id_site");
# Show site :
if ($id_site != -1)
{

	// Utilisateurs ayant accès au site restreint
	$sql = "SELECT u.login, u.nom, u.prenom FROM ".TABLE_PREFIX."_utilisateurs u, ".TABLE_PREFIX."_j_user_site j WHERE (j.id_site='$id_site' and u.login=j.login)  order by u.nom, u.prenom";
	$res = grr_sql_query($sql);
	$nombre = grr_sql_count($res);

	if ($res)
		for ($i = 0; ($row2 = grr_sql_row($res, $i)); $i++)
		{
			$utilisateursExep[] = array('login' => $row2[0], 'nom' => $row2[1], 'prenom' => $row2[2]);
		}

	// Utilisateurs pouvant être ajouté
	$sql = "SELECT login, nom, prenom FROM ".TABLE_PREFIX."_utilisateurs WHERE (etat!='inactif' and (statut='utilisateur' or statut='visiteur' or statut='gestionnaire_utilisateur')) AND login NOT IN (SELECT login FROM ".TABLE_PREFIX."_j_user_site WHERE id_site = '$id_site') order by nom, prenom";
	$res = grr_sql_query($sql);
	$d['nbUserAjoutable'] = grr_sql_count($res);
	if ($res)
		for ($i = 0; ($row3 = grr_sql_row($res, $i)); $i++)
			$utilisateursAjoutable[] = array('login' => $row3[0], 'nom' => $row3[1], 'prenom' => $row3[2]);

	// Groupes ayant accès au site restreint
	$sql = "SELECT g.idgroupes, g.nom FROM ".TABLE_PREFIX."_groupes g, ".TABLE_PREFIX."_j_group_site j WHERE (j.id_site='$id_site' and g.idgroupes=j.idgroupes)  order by g.nom";
	$res = grr_sql_query($sql);
	$nombre = grr_sql_count($res);

	if ($res)
		for ($i = 0; ($row2 = grr_sql_row($res, $i)); $i++)
		{
			$groupesExep[] = array('id' => $row2[0], 'nom' => $row2[1]);
		}

	// Groupes pouvant être ajouté
	$sql = "SELECT idgroupes, nom FROM ".TABLE_PREFIX."_groupes WHERE archive = 0 AND idgroupes NOT IN (SELECT idgroupes FROM ".TABLE_PREFIX."_j_group_site WHERE id_site = '$id_site') order by nom";
	$res = grr_sql_query($sql);
	$d['nbUserAjoutable'] = grr_sql_count($res);
	if ($res)
		for ($i = 0; ($row3 = grr_sql_row($res, $i)); $i++)
			$groupesAjoutable[] = array('id' => $row3[0], 'nom' => $row3[1]);

}

	echo $twig->render('admin_access_site.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings, 'sites' => $sites, 'utilisateursexep' => $utilisateursExep, 'groupesexep' => $groupesExep, 'utilisateursajoutable' => $utilisateursAjoutable, 'groupesajoutable' => $groupesAjoutable));
?>