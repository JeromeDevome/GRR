<?php
/**
 * admin_right.php
 * Interface de gestion des droits de gestion des utilisateurs
 * Dernière modification : $Date: 2017-12-16 14:00$
 * @author    JeromeB & Laurent Delineau
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

$grr_script_name = "admin_right.php";

$id_area = isset($_POST["id_area"]) ? $_POST["id_area"] : (isset($_GET["id_area"]) ? $_GET["id_area"] : NULL);
$room = isset($_POST["room"]) ? $_POST["room"] : (isset($_GET["room"]) ? $_GET["room"] : NULL);
if (isset($room))
	settype($room,"integer");
if (!isset($id_area))
	settype($id_area,"integer");

check_access(4, $back);

// tableau des ressources auxquelles l'utilisateur n'a pas accès
$tab_rooms_noaccess = verif_acces_ressource(getUserName(), 'all');
$reg_admin_login = isset($_POST["reg_admin_login"]) ? $_POST["reg_admin_login"] : NULL;
$reg_multi_admin_login = isset($_POST["reg_multi_admin_login"]) ? $_POST["reg_multi_admin_login"] : NULL;
$test_user =  isset($_POST["reg_multi_admin_login"]) ? "multi" : (isset($_POST["reg_admin_login"]) ? "simple" : NULL);
$action = isset($_GET["action"]) ? $_GET["action"] : NULL;
$msg = '';
if ($test_user == "multi")
{
	foreach ($reg_multi_admin_login as $valeur)
	{
	// On commence par vérifier que le professeur n'est pas déjà présent dans cette liste.
	// ajout pour une ressource d'un domaine
		if ($room != -1)
		{
		// Ressource
		// On vérifie que la ressource $room existe
			$test = grr_sql_query1("SELECT id FROM ".TABLE_PREFIX."_room WHERE id='".$room."'");
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
			if (authGetUserLevel(getUserName(),$room) < 4)
			{
				showAccessDenied($back);
				exit();
			}
			$sql = "SELECT * FROM ".TABLE_PREFIX."_j_user_room WHERE (login = '$valeur' and id_room = '$room')";
			$res = grr_sql_query($sql);
			$test = grr_sql_count($res);
			if ($test != "0")
			{
				$msg = get_vocab("warning_exist");
			}
			else
			{
				if ($valeur != '')
				{
					$sql = "INSERT INTO ".TABLE_PREFIX."_j_user_room SET login= '$valeur', id_room = '$room'";
					if (grr_sql_command($sql) < 0)
						fatal_error(0, "<p>" . grr_sql_error());
					else
						$msg = get_vocab("add_multi_user_succeed");
				}
			}
		}
		else
		{
		//ajout pour toutes les ressources du domaine
		// Domaine
		// On vérifie que le domaine $id_area existe
			$test = grr_sql_query1("SELECT id FROM ".TABLE_PREFIX."_area WHERE id='".$id_area."'");
			if ($test == -1)
			{
				showAccessDenied($back);
				exit();
			}
		// Le domaine existe : on vérifie les privilèges de l'utilisateur
			if (authGetUserLevel(getUserName(),$id_area,'area') < 4)
			{
				showAccessDenied($back);
				exit();
			}
			$sql = "SELECT id FROM ".TABLE_PREFIX."_room WHERE area_id=$id_area";
		// on ne cherche pas parmi les ressources invisibles pour l'utilisateur
			foreach ($tab_rooms_noaccess as $key)
				$sql .= " and id != $key ";
			$res = grr_sql_query($sql);
			if ($res)
			{
				for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
				{
					$sql2 = "SELECT login FROM ".TABLE_PREFIX."_j_user_room WHERE (login = '$valeur' and id_room = '$row[0]')";
					$res2 = grr_sql_query($sql2);
					$nb = grr_sql_count($res2);
					if ($nb == 0)
					{
						$sql3 = "INSERT INTO ".TABLE_PREFIX."_j_user_room (login, id_room) VALUES ('$valeur','$row[0]')";
						if (grr_sql_command($sql3) < 0)
							fatal_error(0, "<p>" . grr_sql_error());
						else
							$msg = get_vocab("add_multi_user_succeed");
					}
				}
			}
		}
	}
}
if ($test_user == "simple")
{
	if ($reg_admin_login)
	{
	// On commence par vérifier que le professeur n'est pas déjà présent dans cette liste.
	// ajout pour une ressource d'un domaine
		if ($room != -1)
		{
		// Ressource
		// On vérifie que la ressource $room existe
			$test = grr_sql_query1("SELECT id FROM ".TABLE_PREFIX."_room WHERE id='".$room."'");
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
			if (authGetUserLevel(getUserName(),$room) < 4)
			{
				showAccessDenied($back);
				exit();
			}
			$sql = "SELECT * FROM ".TABLE_PREFIX."_j_user_room WHERE (login = '$reg_admin_login' and id_room = '$room')";
			$res = grr_sql_query($sql);
			$test = grr_sql_count($res);
			if ($test != "0")
				$msg = get_vocab("warning_exist");
			else
			{
				if ($reg_admin_login != '')
				{
					$sql = "INSERT INTO ".TABLE_PREFIX."_j_user_room SET login= '$reg_admin_login', id_room = '$room'";
					if (grr_sql_command($sql) < 0)
						fatal_error(0, "<p>" . grr_sql_error());
					else
						$msg = get_vocab("add_user_succeed");
				}
			}
		}
		else
		{
			//ajout pour toutes les ressources du domaine
			// Domaine
			// On vérifie que le domaine $id_area existe
			$test = grr_sql_query1("SELECT id FROM ".TABLE_PREFIX."_area WHERE id='".$id_area."'");
			if ($test == -1)
			{
				showAccessDenied($back);
				exit();
			}
			// Le domaine existe : on vérifie les privilèges de l'utilisateur
			if (authGetUserLevel(getUserName(),$id_area,'area') < 4)
			{
				showAccessDenied($back);
				exit();
			}
			$sql = "SELECT id FROM ".TABLE_PREFIX."_room WHERE area_id=$id_area";
			// on ne cherche pas parmi les ressources invisibles pour l'utilisateur
			foreach ($tab_rooms_noaccess as $key)
				$sql .= " and id != $key ";
			$res = grr_sql_query($sql);
			if ($res)
			{
				for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
				{
					$sql2 = "SELECT login FROM ".TABLE_PREFIX."_j_user_room WHERE (login = '$reg_admin_login' and id_room = '$row[0]')";
					$res2 = grr_sql_query($sql2);
					$nb = grr_sql_count($res2);
					if ($nb==0)
					{
						$sql3 = "INSERT INTO ".TABLE_PREFIX."_j_user_room (login, id_room) values ('$reg_admin_login','$row[0]')";
						if (grr_sql_command($sql3) < 0)
							fatal_error(0, "<p>" . grr_sql_error());
						else
							$msg = get_vocab("add_user_succeed");
					}
				}
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
		unset($login_admin); $login_admin = $_GET["login_admin"];
		$sql = "DELETE FROM ".TABLE_PREFIX."_j_user_room WHERE (login='$login_admin' and id_room = '$room')";
		if (grr_sql_command($sql) < 0)
			fatal_error(0, "<p>" . grr_sql_error());
		else
			$msg = get_vocab("del_user_succeed");
	}
	if ($action == "del_admin_all")
	{
		if (authGetUserLevel(getUserName(),$id_area,'area') < 4)
		{
			showAccessDenied($back);
			exit();
		}
		$sql = "SELECT id FROM ".TABLE_PREFIX."_room WHERE area_id=$id_area ";
		// on ne cherche pas parmi les ressources invisibles pour l'utilisateur
		foreach ($tab_rooms_noaccess as $key)
			$sql .= " and id != $key ";
		$sql .= " order by room_name";
		$res = grr_sql_query($sql);
		if ($res)
		{
			for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
			{
				$sql2 = "DELETE FROM ".TABLE_PREFIX."_j_user_room WHERE (login='".$_GET['login_admin']."' and id_room = '$row[0]')";
				if (grr_sql_command($sql2) < 0)
					fatal_error(0, "<p>" . grr_sql_error());
				else
					$msg = get_vocab("del_user_succeed");
			}
		}
	}
}
if ((empty($id_area)) && (isset($row[0])))
{
	if (authGetUserLevel(getUserName(),$row[0],'area') >= 6)
		$id_area = get_default_area();
	else
	{
		//Retourne le domaine par défaut; Utilisé si aucun domaine n'a été défini.
		// On cherche le premier domaine à accès non restreint
		$id_area = grr_sql_query1("SELECT a.id FROM ".TABLE_PREFIX."_area a, ".TABLE_PREFIX."_j_useradmin_area j
			WHERE a.id=j.id_area and j.login='".getUserName()."'
			ORDER BY a.access, a.order_display, a.area_name
			LIMIT 1");
	}
}
if (empty($room))
	$room = -1;

get_vocab_admin('admin_right');
get_vocab_admin('admin_right_explain');

get_vocab_admin('areas');
get_vocab_admin('select');
get_vocab_admin('rooms');
get_vocab_admin('select_all');
get_vocab_admin('user_list');
get_vocab_admin('add_user_to_list');
get_vocab_admin('add_multiple_user_to_list');
get_vocab_admin('add');

$trad['dIdDomaine'] = $id_area;
$trad['dIdRessource'] = $room;


// Affichage d'un pop-up
affiche_pop_up($msg,"admin");

$this_area_name = "";
$this_room_name = "";
$utilisateursAdmin = array ();
$utilisateursAjoutable = array ();
$ressources = array ();

//Show all areas
$sql = "SELECT id, area_name FROM ".TABLE_PREFIX."_area order by order_display";
$res = grr_sql_query($sql);
if ($res)
{
	for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
	{
		// On affiche uniquement les domaines administrés par l'utilisateur
		if (authGetUserLevel(getUserName(),$row[0],'area') >= 4)
			$domaines[] = array('id' => $row[0], 'nom' => $row[1]);
	}
}

$this_area_name = grr_sql_query1("SELECT area_name FROM ".TABLE_PREFIX."_area WHERE id=$id_area");
$this_room_name = grr_sql_query1("SELECT room_name FROM ".TABLE_PREFIX."_room WHERE id=$room");
$this_room_name_des = grr_sql_query1("SELECT description FROM ".TABLE_PREFIX."_room WHERE id=$room");

//Show all rooms in the current area
$sql = "SELECT id, room_name, description FROM ".TABLE_PREFIX."_room WHERE area_id=$id_area ";
foreach ($tab_rooms_noaccess as $key)
	$sql .= " and id != $key ";
$sql .= " order by order_display,room_name";
$res = grr_sql_query($sql);
if ($res)
{
	for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
	{
		if ($row[2])
			$temp = " (".htmlspecialchars($row[2]).")";
		else
			$temp = "";
		$ressources[] = array('id' => $row[0], 'nom' => $row[1], 'description' => $row[2]);
	}
}

//Show area and room:
if ($this_room_name_des != '-1')
	$this_room_name_des = " (".$this_room_name_des.")";
else
	$this_room_name_des = '';

if ($room != -1) // Sur une ressource
{
	$sql = "SELECT u.login, u.nom, u.prenom FROM ".TABLE_PREFIX."_utilisateurs u, ".TABLE_PREFIX."_j_user_room j WHERE (j.id_room='$room' and u.login=j.login) order by u.nom, u.prenom";
	$res = grr_sql_query($sql);
	$nombre = grr_sql_count($res);
	if ($res)
	{
		for ($i = 0; ($row2 = grr_sql_row($res, $i)); $i++)
			$utilisateursAdmin[] = array('login' => $row2[0], 'nom' => $row2[1], 'prenom' => $row2[2]);
	}
}
else // Sur toute les ressources du domaine
{
	$exist_admin='no';
	$sql = "SELECT login, nom, prenom FROM ".TABLE_PREFIX."_utilisateurs WHERE (statut='utilisateur' or statut='gestionnaire_utilisateur')";
	$res = grr_sql_query($sql);
	if ($res)
	{
		for ($i = 0; ($row2 = grr_sql_row($res, $i)); $i++)
		{
			$is_admin = 'yes';
			$sql2 = "SELECT id, room_name, description FROM ".TABLE_PREFIX."_room WHERE area_id=$id_area ";
			foreach ($tab_rooms_noaccess as $key)
				$sql2 .= " and id != $key ";
			$sql2 .= " order by order_display,room_name";
			$res2 = grr_sql_query($sql2);

			if ($res2)
			{
				$test = grr_sql_count($res2);
				if ($test != 0)
				{
					for ($j = 0; ($row4 = grr_sql_row($res2, $j)); $j++)
					{
						$sql3 = "SELECT login FROM ".TABLE_PREFIX."_j_user_room WHERE (id_room='".$row4[0]."' and login='".$row2[0]."')";
						$res3 = grr_sql_query($sql3);
						$nombre = grr_sql_count($res3);
						if ($nombre == 0)
							$is_admin = 'no';
					}
				}
				else
					$is_admin = 'no';
			}
			if ($is_admin == 'yes')
			{
				$utilisateursAdmin[] = array('login' => $row2[0], 'nom' => $row2[1], 'prenom' => $row2[2]);
			}
		}
	}

}

	$sql = "SELECT login, nom, prenom FROM ".TABLE_PREFIX."_utilisateurs WHERE  (etat!='inactif' and (statut='utilisateur' or statut='gestionnaire_utilisateur')) order by nom, prenom";
	$res = grr_sql_query($sql);
	$trad['dNbUserAjoutable'] = grr_sql_count($res);
	if ($res)
	{
		for ($i = 0; ($row3 = grr_sql_row($res, $i)); $i++)
		{
			if (authUserAccesArea($row3[0],$id_area) == 1)
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
	}

	echo $twig->render('admin_right.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings, 'domaines' => $domaines, 'ressources' => $ressources, 'utilisateursadmin' => $utilisateursAdmin, 'utilisateursajoutable' => $utilisateursAjoutable));
?>