<?php
/**
 * admin_book_room.php
 * Script de création/modification des ressources de l'application GRR
 * Dernière modification : $Date: 2024-06-17 17:09$
 * @author    JeromeB & Yan Naessens
 * @copyright Copyright 2003-2024 Team DEVOME - JeromeB
 * @link      http://www.gnu.org/licenses/licenses.html
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 */


$grr_script_name = "admin_book_room.php";

$ok = NULL;
$id_room = isset($_POST["id_room"]) ? $_POST["id_room"] : (isset($_GET["id_room"]) ? $_GET["id_room"] : -1);
$d['id_room'] = intval(clean_input($id_room));
$reg_user_login = isset($_POST["reg_user_login"]) ? $_POST["reg_user_login"] : NULL;
$reg_multi_user_login = isset($_POST["reg_multi_user_login"]) ? $_POST["reg_multi_user_login"] : NULL;
$test_user =  isset($_POST["reg_multi_user_login"]) ? "multi" : (isset($_POST["reg_user_login"]) ? "simple" : NULL);
$action = isset($_GET["action"]) ? $_GET["action"] : NULL;
$user_name = getUserName();
$msg = '';

$ressources = array();
$userAcces = array();
$userAjout = array();


$trad = $vocab;


$vocab['user_can_book'] = "peut réserver les ressources restreintes :";


if ($test_user == "multi")
{	
    if ($d['id_room'] != -1)
    {
        if (authGetUserLevel(getUserName(), $d['id_room']) < 4)
        {
            showAccessDenied($back);
            exit();
        }
        foreach ($reg_multi_user_login as $valeur)
        {
        // On commence par vérifier que l'utilisateur n'est pas déjà présent dans cette liste.
            $sql = "SELECT * FROM ".TABLE_PREFIX."_j_userbook_room WHERE (login = '".$valeur."' and id_room = '".$d['id_room']."')";
            $res = grr_sql_query($sql);
            $test = grr_sql_count($res);
            if ($test > 0)
                $msg = get_vocab("warning_exist");
            else
            {
                if ($valeur != '')
                {
                    $sql = "INSERT INTO ".TABLE_PREFIX."_j_userbook_room SET login= '$valeur', id_room = '".$d['id_room']."'";
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
	if ($d['id_room'] != -1)
	{
		if (authGetUserLevel(getUserName(), $d['id_room']) < 4)
		{
			showAccessDenied($back);
			exit();
		}
   // On commence par vérifier que l'utilisateur n'est pas déjà présent dans cette liste.
		$sql = "SELECT * FROM ".TABLE_PREFIX."_j_userbook_room WHERE (login = '$reg_user_login' and id_room = '".$d['id_room']."')";
		$res = grr_sql_query($sql);
		$test = grr_sql_count($res);
		if ($test > 0)
			$msg = get_vocab("warning_exist");
		else
		{
			if ($reg_user_login != '')
			{
				$sql = "INSERT INTO ".TABLE_PREFIX."_j_userbook_room SET login= '$reg_user_login', id_room = '".$d['id_room']."'";
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
	if (authGetUserLevel(getUserName(), $d['id_room']) < 4)
	{
		showAccessDenied($back);
		exit();
	}
	unset($login_user);
	$login_user = clean_input($_GET["login_user"]);
	$sql = "DELETE FROM ".TABLE_PREFIX."_j_userbook_room WHERE (login='$login_user' and id_room = '".$d['id_room']."')";
	if (grr_sql_command($sql) < 0)
		fatal_error(1, "<p>" . grr_sql_error());
	else
		$msg = get_vocab("del_user_succeed");
}


// première étape : choisir parmi les ressources restreintes
$multisite = Settings::get("module_multisite") == "Oui";
if($multisite)
  $sql = "SELECT r.id,room_name,area_name,sitename
          FROM ((`grr_room` r JOIN `grr_area` a ON r.area_id = a.id)
          JOIN grr_j_site_area ON a.id = id_area)
          JOIN grr_site s ON s.id = id_site
          WHERE r.who_can_book = 0
          ORDER BY room_name";
else
  $sql = "SELECT r.id,room_name,area_name
          FROM `grr_room` r JOIN `grr_area` a ON r.area_id = a.id
          WHERE r.who_can_book = 0
          ORDER BY room_name";
$res = grr_sql_query($sql);
$nb = grr_sql_count($res);

if (!$res)
    fatal_error(1,grr_sql_error($res));
else
{
	foreach($res as $row)
	{
		// on vérifie que l'utilisateur connecté a les droits suffisants
		if (authGetUserLevel($user_name,$d['id_room'])>2)
      if($multisite)
        $ressources[] = array($row['id'],($row['sitename']." > ".$row['area_name']." > ".$row['room_name']));
      else
        $ressources[] = array($row['id'],$row['area_name'].' > '.$row['room_name']);
	}
}

// Deuxième étape : la ressource étant choisie, afficher les utilisateurs autorisés à réserver et le formulaire de mise à jour de la liste

if ($d['id_room'] != -1)
{
    $sql = "SELECT u.login, u.nom, u.prenom FROM ".TABLE_PREFIX."_utilisateurs u JOIN ".TABLE_PREFIX."_j_userbook_room j ON u.login=j.login WHERE j.id_room='".$d['id_room']."' ORDER BY u.nom, u.prenom";
	$res = grr_sql_query($sql);
    if (!$res)
        grr_sql_error($res);
    else {
        $d['nombre'] = grr_sql_count($res);
        if ( $d['nombre'] > 0)
        {
            for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
                $userAcces[] = $row;
        }
    }

    $sql = "SELECT login, nom, prenom FROM ".TABLE_PREFIX."_utilisateurs WHERE (etat!='inactif' and (statut='utilisateur' or statut='visiteur' or statut='gestionnaire_utilisateur')) AND login NOT IN (SELECT DISTINCT login FROM ".TABLE_PREFIX."_j_userbook_room WHERE id_room = '".$d['id_room']."') order by nom, prenom";
    $res = grr_sql_query($sql);
    if ($res)
        for ($i = 0; ($row = grr_sql_row($res, $i)); $i++){
            // on n'affiche que les utilisateurs ayant accès à la ressource
             if (verif_acces_ressource($row[0],$d['id_room']))
                $userAjout[] = $row;
        }

}
else
{
    if ($nb =0)
        $d['NoRoomRestriction'] = get_vocab("no_restricted_room");
    else
        $d['NoRoomRestriction'] = get_vocab("no_room_selected");
}



echo $twig->render('admin_book_room.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings, 'ressources' => $ressources, 'userAcces' => $userAcces, 'userAjout' => $userAjout));
?>