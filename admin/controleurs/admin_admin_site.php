<?php
/**
 * admin_admin_site.php
 * Interface de gestion des administrateurs de sites de l'application GRR
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

$grr_script_name = "admin_admin_site.php";

$id_site = isset($_POST["id_site"]) ? $_POST["id_site"] : (isset($_GET["id_site"]) ? $_GET["id_site"] : NULL);
if (empty($id_site))
	$id_site = get_default_site();
if (!isset($id_site))
	settype($id_site, "integer");

check_access(6, $back);
if (Settings::get("module_multisite") != "Oui")
{
	showAccessDenied($back);
	exit();
}

$reg_admin_login = isset($_GET["reg_admin_login"]) ? $_GET["reg_admin_login"] : NULL;
$action = isset($_GET["action"]) ? $_GET["action"] : NULL;
$msg = '';

if ($reg_admin_login)
{
	$res = grr_sql_query1("select login from ".TABLE_PREFIX."_j_useradmin_site where (login = '$reg_admin_login' and id_site = '$id_site')");
	if ($res == -1)
	{
		$sql = "insert into ".TABLE_PREFIX."_j_useradmin_site (login, id_site) values ('$reg_admin_login',$id_site)";
		if (grr_sql_command($sql) < 0)
			fatal_error(1, "<p>" . grr_sql_error());
		else
			$msg = get_vocab("add_user_succeed");
	}
}

if ($action)
{
	if ($action == "del_admin")
	{
		unset($login_admin);
		$login_admin = $_GET["login_admin"];
		$sql = "DELETE FROM ".TABLE_PREFIX."_j_useradmin_site WHERE (login='$login_admin' and id_site = '$id_site')";
		if (grr_sql_command($sql) < 0)
			fatal_error(1, "<p>" . grr_sql_error());
		else
			$msg = get_vocab("del_user_succeed");
	}
}

get_vocab_admin('admin_admin_site');
get_vocab_admin('admin_admin_site_explain');
get_vocab_admin('sites');
get_vocab_admin('select');
get_vocab_admin('add_user_to_list');
get_vocab_admin('user_admin_site_list');
get_vocab_admin('admin');

get_vocab_admin('add');

$trad['dIdSite'] = $id_site;

// Affichage d'un pop-up
affiche_pop_up($msg,"admin");

$utilisateursAdmin = array ();
$utilisateursAjoutable = array ();
$sites = array ();

# liste des sites
$sql = "select id, sitename from ".TABLE_PREFIX."_site order by sitename";
$res = grr_sql_query($sql);
if ($res)
	for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
	{
		$sites[] = array('id' => $row[0], 'nom' => $row[1]);
	}

$is_admin = 'yes';

	$sql = "select login, nom, prenom from ".TABLE_PREFIX."_utilisateurs where (statut='utilisateur' or statut='gestionnaire_utilisateur')";
	$res = grr_sql_query($sql);
	if ($res)
		for ($i = 0; ($row2 = grr_sql_row($res, $i)); $i++)
		{
			$is_admin = 'yes';
			$sql3 = "SELECT login FROM ".TABLE_PREFIX."_j_useradmin_site WHERE (id_site='".$id_site."' and login='".$row2[0]."')";
			$res3 = grr_sql_query($sql3);
			$nombre = grr_sql_count($res3);
			if ($nombre == 0)
				$is_admin = 'no';
			if ($is_admin == 'yes')
			{
				$utilisateursAdmin[] = array('login' => $row2[0], 'nom' => $row2[1], 'prenom' => $row2[2]);
			}
		}

	$sql = "SELECT login, nom, prenom FROM ".TABLE_PREFIX."_utilisateurs WHERE  (etat!='inactif' and (statut='utilisateur' or statut='gestionnaire_utilisateur')) order by nom, prenom";
	$res = grr_sql_query($sql);
	if ($res)
		for ($i = 0; ($row3 = grr_sql_row($res, $i)); $i++)
			$utilisateursAjoutable[] = array('login' => $row3[0], 'nom' => $row3[1], 'prenom' => $row3[2]);

	echo $twig->render('admin_admin_site.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings, 'sites' => $sites, 'utilisateursadmin' => $utilisateursAdmin, 'utilisateursajoutable' => $utilisateursAjoutable));
?>