<?php
/**
 * vuedroitsressource.php
 * Liste des privilèges d'une ressource
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2023-12-29 21:20$
 * @author    Laurent Delineau & JeromeB
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

$grr_script_name = "vuedroitsressource.php";

$trad = $vocab;

if ((Settings::get("authentification_obli") == 0) && (getUserName() == ''))
	$type_session = "no_session";
else
	$type_session = "with_session";
$id_room = isset($_GET["id_room"]) ? $_GET["id_room"] : NULL;
if (isset($id_room))
	settype($id_room,"integer");
if ((authGetUserLevel(getUserName(),$id_room) < 4) || (!verif_acces_ressource(getUserName(), $id_room)))
{
	showAccessDenied('');
	exit();
}

$res = grr_sql_query("SELECT * FROM ".TABLE_PREFIX."_room WHERE id=$id_room");
if (!$res)
	fatal_error(0, get_vocab('error_room') . $id_room . get_vocab('not_found'));
$ressource = grr_sql_row_keyed($res, 0);
grr_sql_free($res);

$id_area = mrbsGetRoomArea($id_room);
$d['area_name'] = grr_sql_query1("SELECT area_name FROM ".TABLE_PREFIX."_area WHERE id='".$id_area."'");
$d['area_access'] = grr_sql_query1("SELECT access FROM ".TABLE_PREFIX."_area WHERE id='".$id_area."'");

// On affiche pour les administrateurs les utilisateurs ayant des privilèges sur cette ressource
$adminDomaine = array();
$adminRessource = array();
$mailRessource = array();
$accesDomaine = array();
$accesRessource = array();

// 1: On teste si des utilateurs administre le domaine
$req_admin = "SELECT u.login, u.nom, u.prenom, u.etat FROM ".TABLE_PREFIX."_utilisateurs u left join ".TABLE_PREFIX."_j_useradmin_area j on u.login=j.login WHERE j.id_area = '".$id_area."' order by u.nom, u.prenom";
$res_admin = grr_sql_query($req_admin);

if ($res_admin)
{
	for ($j = 0; ($domaine_admin = grr_sql_row($res_admin, $j)); $j++)
		$adminDomaine[] = array('login' => $domaine_admin[0], 'nom' => $domaine_admin[1], 'prenom' => $domaine_admin[2], 'etat' => $domaine_admin[3]);
}

// 2: On teste si des utilisateurs administrent la ressource
$req_room = "SELECT u.login, u.nom, u.prenom, u.etat FROM ".TABLE_PREFIX."_utilisateurs u left join ".TABLE_PREFIX."_j_user_room j on u.login=j.login WHERE j.id_room = '".$id_room."' order by u.nom, u.prenom";
$res_room = grr_sql_query($req_room);

if ($res_room)
{
	for ($j = 0; ($ressource_admin = grr_sql_row($res_room, $j)); $j++)
		$adminRessource[] = array('login' => $ressource_admin[0], 'nom' => $ressource_admin[1], 'prenom' => $ressource_admin[2], 'etat' => $ressource_admin[3]);
}

// 3: On teste si des utilisateurs reçoivent des mails automatiques
$req_mail = "SELECT u.login, u.nom, u.prenom, u.etat FROM ".TABLE_PREFIX."_utilisateurs u left join ".TABLE_PREFIX."_j_mailuser_room j on u.login=j.login WHERE j.id_room = '".$id_room."' order by u.nom, u.prenom";
$res_mail = grr_sql_query($req_mail);

if ($res_mail)
{
	for ($j = 0; ($ressource_mail = grr_sql_row($res_mail, $j)); $j++)
		$mailRessource[] = array('login' => $ressource_mail[0], 'nom' => $ressource_mail[1], 'prenom' => $ressource_mail[2], 'etat' => $ressource_mail[3]);
}

// 4: Si le domaine est restreint, on teste si des utilateurs y ont accès
if ($d['area_access'] == 'r')
{
	$req_restreint = "SELECT u.login, u.nom, u.prenom, u.etat FROM ".TABLE_PREFIX."_utilisateurs u left join ".TABLE_PREFIX."_j_user_area j on u.login=j.login WHERE j.id_area = '".$id_area."' order by u.nom, u.prenom";
	$res_restreint = grr_sql_query($req_restreint);
	if ($res_restreint)
	{
		for ($j = 0; ($domaine_restreint = grr_sql_row($res_restreint, $j)); $j++)
			$accesDomaine[] = array('login' => $domaine_restreint[0], 'nom' => $domaine_restreint[1], 'prenom' => $domaine_restreint[2], 'etat' => $domaine_restreint[3]);
	}
}

// 5: Si la ressource est restreinte, on affiche les utilisateurs ayant le droit de réserver
if ($ressource['who_can_book'] == 0){// la ressource est à accès restreint
    $req = "SELECT u.login, u.nom, u.prenom, u.etat FROM ".TABLE_PREFIX."_utilisateurs u left join ".TABLE_PREFIX."_j_userbook_room j on u.login=j.login WHERE j.id_room = '".$ressource['id']."' order by u.nom, u.prenom";
    $res = grr_sql_query($req);

    if ($res){
        foreach($res as $ressource_restreint){
			$accesRessource[] = array('login' => $ressource_restreint['login'], 'nom' => $ressource_restreint['nom'], 'prenom' => $ressource_restreint['prenom'], 'etat' => $ressource_restreint['etat']);
        }
    }
    grr_sql_free($res);
}


echo $twig->render('vuedroitsressource.twig', array('trad' => $trad, 'd' => $d, 'settings' => $AllSettings, 'ressource' => $ressource, 'admindomaine' => $adminDomaine, 'adminressource' => $adminRessource, 'mailressource' => $mailRessource, 'accesdomaine' => $accesDomaine, 'accesressource' => $accesRessource));
?>