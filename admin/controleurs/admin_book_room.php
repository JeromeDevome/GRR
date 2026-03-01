<?php
/**
 * admin_book_room.php
 * Script gérant l'accès aux ressources restreintes de l'application GRR
 * L'affichage est réalisé par admin_book_room.twig
 * Dernière modification : $Date: 2025-11-27 10:39$
 * @author    JeromeB & Yan Naessens
 * @copyright Since 2003 Team DEVOME - JeromeB
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
$reg_groupe = isset($_POST["reg_groupe"]) ? $_POST["reg_groupe"] : NULL;
$reg_multi_user_login = isset($_POST["reg_multi_user_login"]) ? $_POST["reg_multi_user_login"] : NULL;
$actionPost =  isset($_POST["action"]) ? $_POST["action"] : NULL;
$action = isset($_GET["action"]) ? $_GET["action"] : NULL;
$user_name = getUserName();
$msg = '';

$ressources = array();
$userAcces = array();
$userAjout = array();
$groupesExep = array();
$groupesAjoutable = array();


$trad = $vocab;


$vocab['user_can_book'] = "peut réserver les ressources restreintes :";


if ($actionPost == "multi")
{	
    if ($d['id_room'] != -1)
    {
        if (authGetUserLevel(getUserName(), $d['id_room']) < 3)
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

if ($actionPost == "simple")
{
	if ($d['id_room'] != -1)
	{
		if (authGetUserLevel(getUserName(), $d['id_room']) < 3)
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
                {
					$d['enregistrement'] = 1;
					$d['msgToast'] = get_vocab("add_user_succeed");
				}
			}
		}
	}
}

if ($actionPost == "add_groupe")
{
	if ($d['id_room'] != -1)
	{
		if (authGetUserLevel(getUserName(), $d['id_room']) < 3)
		{
			showAccessDenied($back);
			exit();
		}
        // On commence par vérifier que le groupe n'est pas déjà présent dans cette liste.
		$sql = "SELECT * FROM ".TABLE_PREFIX."_j_group_room WHERE (idgroupes = '$reg_groupe' and id_room = '".$d['id_room']."')";
		$res = grr_sql_query($sql);
		$test = grr_sql_count($res);
		if ($test > 0)
		{
			$d['enregistrement'] = 2;
			$d['msgToast'] = get_vocab("warning_exist");
		}
		else
		{
			if ($reg_groupe != '')
			{
				$sql = "INSERT INTO ".TABLE_PREFIX."_j_group_room SET idgroupes= '$reg_groupe', id_room = '".$d['id_room']."'";
				if (grr_sql_command($sql) < 0)
					fatal_error(1, "<p>" . grr_sql_error());
				else
				{
					$d['enregistrement'] = 1;
					$d['msgToast'] = get_vocab("add_user_succeed");
				}

				synchro_groupe($reg_groupe, 1);
			}
		}
	}
}

if ($action=='del_user')
{
	if (authGetUserLevel(getUserName(), $d['id_room']) < 3)
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
    {
		$d['enregistrement'] = 1;
		$d['msgToast'] = get_vocab("del_user_succeed");
	}
} elseif ($action=='del_groupe')
{
	if (authGetUserLevel(getUserName(), $d['id_room']) < 3)
	{
		showAccessDenied($back);
		exit();
	}
	unset($login_user);
	$groupe = $_GET["groupe"];
	$sql = "DELETE FROM ".TABLE_PREFIX."_j_group_room WHERE (idgroupes='$groupe' and id_room = '".$d['id_room']."')";
	if (grr_sql_command($sql) < 0)
		fatal_error(1, "<p>" . grr_sql_error());
	else
	{
		$d['enregistrement'] = 1;
		$d['msgToast'] = get_vocab("del_user_succeed");
	}

	synchro_groupe($groupe, 1);
}

// première étape : choisir parmi les ressources restreintes
$multisite = Settings::get("module_multisite") == "Oui";
if($multisite)
  $sql = "SELECT r.id,room_name,area_name,sitename
          FROM ((`".TABLE_PREFIX."_room` r JOIN `".TABLE_PREFIX."_area` a ON r.area_id = a.id)
          JOIN ".TABLE_PREFIX."_j_site_area ON a.id = id_area)
          JOIN ".TABLE_PREFIX."_site s ON s.id = id_site
          WHERE r.who_can_book = 0
          ORDER BY room_name";
else
  $sql = "SELECT r.id,room_name,area_name
          FROM `".TABLE_PREFIX."_room` r JOIN `".TABLE_PREFIX."_area` a ON r.area_id = a.id
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
		if (authGetUserLevel($user_name,$row['id'])>2)
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

    // Groupes ayant accès a la ressource restreinte
	$sql = "SELECT g.idgroupes, g.nom FROM ".TABLE_PREFIX."_groupes g, ".TABLE_PREFIX."_j_group_room j WHERE (j.id_room='$id_room' and g.idgroupes=j.idgroupes)  order by g.nom";
	$res = grr_sql_query($sql);
	$nombre = grr_sql_count($res);

	if ($res)
		for ($i = 0; ($row2 = grr_sql_row($res, $i)); $i++)
		{
			$groupesExep[] = array('id' => $row2[0], 'nom' => $row2[1]);
		}

	// Groupes pouvant être ajouté
	$sql = "SELECT idgroupes, nom FROM ".TABLE_PREFIX."_groupes WHERE archive = 0 AND idgroupes NOT IN (SELECT idgroupes FROM ".TABLE_PREFIX."_j_group_room WHERE id_room = '$id_room') order by nom";
	$res = grr_sql_query($sql);
	$d['nbUserAjoutable'] = grr_sql_count($res);
	if ($res)
		for ($i = 0; ($row3 = grr_sql_row($res, $i)); $i++)
			$groupesAjoutable[] = array('id' => $row3[0], 'nom' => $row3[1]);

}
else
{
    if ($nb =0)
        $d['NoRoomRestriction'] = get_vocab("no_restricted_room");
    else
        $d['NoRoomRestriction'] = get_vocab("no_room_selected");
}



echo $twig->render('admin_book_room.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings, 'ressources' => $ressources, 'userAcces' => $userAcces, 'userAjout' => $userAjout, 'groupesexep' => $groupesExep, 'groupesajoutable' => $groupesAjoutable));
?>