<?php
/**
 * admin_right_admin.php
 * Interface de gestion des droits d'administration des utilisateurs
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

$grr_script_name = "admin_right_admin.php";

$id_area = isset($_POST["id_area"]) ? $_POST["id_area"] : (isset($_GET["id_area"]) ? $_GET["id_area"] : NULL);
if (!isset($id_area))
	settype($id_area,"integer");

check_access(6, $back);

$reg_admin_login = isset($_POST["reg_admin_login"]) ? $_POST["reg_admin_login"] : NULL;
$reg_multi_admin_login = isset($_POST["reg_multi_admin_login"]) ? $_POST["reg_multi_admin_login"] : NULL;
$test_user =  isset($_POST["reg_multi_admin_login"]) ? "multi" : (isset($_POST["reg_admin_login"]) ? "simple" : NULL);
$action = isset($_GET["action"]) ? $_GET["action"] : NULL;
$msg = '';
if ($test_user == "multi")
{
	foreach ($reg_multi_admin_login as $valeur)
	{
	// On commence par vérifier que l'utilisateur n'est pas déjà présent dans cette liste.
		$res = grr_sql_query1("select login from ".TABLE_PREFIX."_j_useradmin_area where (login = '$valeur' and id_area = '$id_area')");
		if ($res == -1)
		{
			$sql = "insert into ".TABLE_PREFIX."_j_useradmin_area (login, id_area) values ('$valeur',$id_area)";
			if (grr_sql_command($sql) < 0)
				fatal_error(1, "<p>" . grr_sql_error());
			else
				$msg = get_vocab("add_multi_user_succeed");
		}
		else
			$msg = get_vocab("warning_exist");
	}
}
if ($test_user == "simple")
{
   // On commence par vérifier que l'utilisateur n'est pas déjà présent dans cette liste.
	if ($reg_admin_login)
	{
		$sql = "select login from ".TABLE_PREFIX."_j_useradmin_area where (login = '$reg_admin_login' and id_area = '$id_area')";
		$res = grr_sql_query1($sql);
		if ($res == -1)
		{
			$sql = "insert into ".TABLE_PREFIX."_j_useradmin_area (login, id_area) values ('$reg_admin_login',$id_area)";
			if (grr_sql_command($sql) < 0)
				fatal_error(1, "<p>" . grr_sql_error());
			else
				$msg = get_vocab("add_user_succeed");
		}
		else
			$msg = get_vocab("warning_exist");
	}
}
if ($action)
{
	if ($action == "del_admin")
	{
		unset($login_admin); $login_admin = $_GET["login_admin"];
		$sql = "DELETE FROM ".TABLE_PREFIX."_j_useradmin_area WHERE (login='$login_admin' and id_area = '$id_area')";
		if (grr_sql_command($sql) < 0)
			fatal_error(1, "<p>" . grr_sql_error());
		else
			$msg = get_vocab("del_user_succeed");
	}
}

get_vocab_admin('admin_right_admin');
get_vocab_admin('admin_right_admin_explain');
get_vocab_admin('areas');
get_vocab_admin('select');
get_vocab_admin('add_user_to_list');
get_vocab_admin('user_admin_area_list');
get_vocab_admin('add_multiple_user_to_list');

get_vocab_admin('add');

$trad['dIdDomaine'] = $id_area;

// Affichage d'un pop-up
affiche_pop_up($msg,"admin");

$this_area_name = "";
$utilisateursAdmin = array ();
$utilisateursAjoutable = array ();

//Show all areas
$sql = "select id, area_name from ".TABLE_PREFIX."_area order by order_display";
$res = grr_sql_query($sql);
if ($res)
{
	for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
		$domaines[] = array('id' => $row[0], 'nom' => $row[1]);
}

$this_area_name = grr_sql_query1("SELECT area_name FROM ".TABLE_PREFIX."_area WHERE id=$id_area");

$sql = "SELECT login, nom, prenom FROM ".TABLE_PREFIX."_utilisateurs WHERE (statut='utilisateur' OR statut='gestionnaire_utilisateur' OR statut='administrateur')";
$res = grr_sql_query($sql);

if ($res) for ($i = 0; ($row2 = grr_sql_row($res, $i)); $i++)
{
	$sql3 = "SELECT login FROM ".TABLE_PREFIX."_j_useradmin_area WHERE (id_area='".$id_area."' AND login='".$row2[0]."')";
	$res3 = grr_sql_query($sql3);
	$nombre = grr_sql_count($res3);
	if ($nombre != 0)
		$utilisateursAdmin[] = array('login' => $row2[0], 'nom' => $row2[1], 'prenom' => $row2[2]);
}

$sql = "SELECT login, nom, prenom FROM ".TABLE_PREFIX."_utilisateurs
		WHERE etat!='inactif' and (statut='utilisateur' or statut='administrateur' or statut='gestionnaire_utilisateur')
		ORDER BY nom, prenom";

$res = grr_sql_query($sql);
$trad['dNbUserAjoutable'] = grr_sql_count($res);

if ($res)
{
	for ($i = 0; ($row3 = grr_sql_row($res, $i)); $i++)
		if (authUserAccesArea($row3[0], $id_area) == 1)
		{
			$ExisteDeja = false;
			foreach($utilisateursAdmin as $index => $user) {
				if($user['login'] == $row3[0])
					$ExisteDeja = true;
			}

			if($ExisteDeja == false)
				$utilisateursAjoutable[] = array('login' => $row3[0], 'nom' => $row3[1], 'prenom' => $row3[2]);
		}
}

	echo $twig->render('admin_right_admin.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings, 'domaines' => $domaines, 'utilisateursadmin' => $utilisateursAdmin, 'utilisateursajoutable' => $utilisateursAjoutable));
?>