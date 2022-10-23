<?php
/**
 * admin_email_manager.php
 * Interface de gestion des mails automatiques
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

$grr_script_name = "admin_email_manager.php";

$id_area = isset($_GET["id_area"]) ? $_GET["id_area"] : NULL;
$room = isset($_GET["room"]) ? $_GET["room"] : NULL;
if (isset($room))
	settype($room,"integer");
if (!isset($id_area))
	settype($id_area,"integer");

check_access(4, $back);
// tableau des ressources auxquelles l'utilisateur n'a pas accès
$tab_rooms_noaccess = verif_acces_ressource(getUserName(), 'all');
if (isset($_POST['mail1']))
{
	if (isset($_POST['send_always_mail_to_creator']))
		$temp = '1';
	else
		$temp = '0';
	if (!Settings::set("send_always_mail_to_creator", $temp))
	{
		echo "Erreur lors de l'enregistrement de send_always_mail_to_creator !<br />";
		die();
	}
}
$reg_admin_login = isset($_GET["reg_admin_login"]) ? $_GET["reg_admin_login"] : NULL;
$action = isset($_GET["action"]) ? $_GET["action"] : NULL;
$msg='';

if ($reg_admin_login) {
	// On commence par vérifier que le professeur n'est pas déjà présent dans cette liste.
	if ($room !=-1)
	{
		// Ressource
		// On vérifie que la ressource $room existe
		$test = grr_sql_query1("select id from ".TABLE_PREFIX."_room where id='".$room."'");
		if ($test == -1)
		{
			showAccessDenied($back);
			exit();
		}
		if (in_array($room,$tab_rooms_noaccess))
		{
			showAccessDenied($back);
			exit();
		}
		// La ressource existe : on vérifie les privilèges de l'utilisateur
		check_access(4, $back);
		$sql = "SELECT * FROM ".TABLE_PREFIX."_j_mailuser_room WHERE (login = '$reg_admin_login' and id_room = '$room')";
		$res = grr_sql_query($sql);
		$test = grr_sql_count($res);
		if ($test != "0")
			$msg = get_vocab("warning_exist");
		else
		{
			if ($reg_admin_login != '')
			{
				$sql = "INSERT INTO ".TABLE_PREFIX."_j_mailuser_room SET login= '$reg_admin_login', id_room = '$room'";
				if (grr_sql_command($sql) < 0)
					fatal_error(1, "<p>" . grr_sql_error());
				else
					$msg = get_vocab("add_user_succeed");
			}
		}
	}
}
if ($action)
{
	if ($action == "del_admin")
	{
		if (authGetUserLevel(getUserName(),$room) < 4)
		{
			showAccessDenied($back);
			exit();
		}
		$sql = "DELETE FROM ".TABLE_PREFIX."_j_mailuser_room WHERE (login='".$_GET['login_admin']."' and id_room = '$room')";
		if (grr_sql_command($sql) < 0)
			fatal_error(1, "<p>" . grr_sql_error());
		else
			$msg = get_vocab("del_user_succeed");
	}
}

//affiche_pop_up($msg,"admin");

if (empty($room))
	$room = -1;

$AllSettings = Settings::getAll();

get_vocab_admin('admin_email_manager');
get_vocab_admin('attention_mail_automatique_désactive');
get_vocab_admin('explain_automatic_mail3');

get_vocab_admin('explain_automatic_mail1');

get_vocab_admin('explain_automatic_mail2');
get_vocab_admin('areas');
get_vocab_admin('rooms');
get_vocab_admin('select');

get_vocab_admin("mail_user_list");
get_vocab_admin("add_user_to_list");
get_vocab_admin("nobody");
get_vocab_admin("login");
get_vocab_admin("last_name");
get_vocab_admin("first_name");
get_vocab_admin("action");

get_vocab_admin('add');
get_vocab_admin('save');

$trad['dIdDomaine'] = $id_area;
$trad['dIdRessource'] = $room;
$trad['dMessage'] = $msg;

$ressources = array();
$utilisateurs = array();
$utilisateursNotifier = array();

$this_area_name = "";
$this_room_name = "";

$sql = "select id, area_name from ".TABLE_PREFIX."_area order by area_name";
$res = grr_sql_query($sql);
if ($res)
{
	for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
	{
		if (authGetUserLevel(getUserName(), $row[0], 'area') >= 4)
			$domaines[] = array('id' => $row[0], 'nom' => $row[1]);
	}
}

$this_area_name = grr_sql_query1("select area_name from ".TABLE_PREFIX."_area where id=$id_area");
$this_room_name = grr_sql_query1("select room_name from ".TABLE_PREFIX."_room where id=$room");

$sql = "select id, room_name, description from ".TABLE_PREFIX."_room where area_id=$id_area ";
	// on ne cherche pas parmi les ressources invisibles pour l'utilisateur
foreach ($tab_rooms_noaccess as $key)
{
	$sql .= " and id != $key ";
}
$sql .= "order by room_name";
$res = grr_sql_query($sql);
if ($res)
{
	for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
	{
		$ressources[] = array('id' => $row[0], 'nom' => $row[1], 'description' => $row[2]);
	}
}

# Don't continue if this area has no rooms:
if ($id_area <= 0)
	$trad['mail_user_list'] = get_vocab("no_area");
elseif ($room <= 0)
	$trad['mail_user_list'] = get_vocab("no_room");
else
{
	$sql = "SELECT u.login, u.nom, u.prenom FROM ".TABLE_PREFIX."_utilisateurs u, ".TABLE_PREFIX."_j_mailuser_room j WHERE (j.id_room='$room' and u.login=j.login)  order by u.nom, u.prenom";
	$res = grr_sql_query($sql);
	$nombre = grr_sql_count($res);
	
	if ($nombre == 0)
		$trad['mail_user_list'] = get_vocab("no_mail_user_list");

	if ($res)
	{
		for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
			$utilisateursNotifier[] = array('login' => $row[0], 'nom' => $row[1], 'prenom' => $row[2]);
	}

	// Formulaire Ajout
	$sql = "SELECT DISTINCT login, nom, prenom FROM ".TABLE_PREFIX."_utilisateurs WHERE  (etat!='inactif' and email!='' and statut!='visiteur' ) order by nom, prenom";
	$res = grr_sql_query($sql);
	if ($res)
	{
		for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
		{
			if (authUserAccesArea($row[0], $id_area) == 1)
				$utilisateurs[] = array('login' => $row[0], 'nom' => $row[1], 'prenom' => $row[2]);
		}
	}

}

	echo $twig->render('admin_email_manager.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings, 'domaines' => $domaines, 'ressources' => $ressources, 'utilisateurs' => $utilisateurs, 'utilisateursnotifier' => $utilisateursNotifier));
?>