<?php
/**
 * ressourcefiche.php
 * Formulaire d'envoi de mail
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2023-12-28 18:30$
 * @author    JeromeB
 * @copyright Copyright 2003-2023 Team DEVOME - JeromeB
 * @link      http://www.gnu.org/licenses/licenses.html
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 */

$grr_script_name = "ressourcefiche.php";

$trad = $vocab;

$id_room = isset($_GET["id_room"]) ? $_GET["id_room"] : NULL;
if (isset($id_room))
	settype($id_room,"integer");
else
	$print = "all";

if ((Settings::get("authentification_obli") == 0) && (getUserName() == ''))
	$type_session = "no_session";
else
	$type_session = "with_session";

if (((authGetUserLevel(getUserName(),-1) < 1) && (Settings::get("authentification_obli") == 1)) || (!verif_acces_ressource(getUserName(), $id_room)))
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
$d['area_name'] = grr_sql_query1("select area_name from ".TABLE_PREFIX."_area where id='".$id_area."'");
$d['area_access'] = grr_sql_query1("select access from ".TABLE_PREFIX."_area where id='".$id_area."'");

// Description
if ((authGetUserLevel(getUserName(),-1) >= Settings::get("visu_fiche_description")) && ($ressource["description"] != ''))
	$d['visuDescription'] = 1;


// Description complète
if ((authGetUserLevel(getUserName(),-1) >= Settings::get("acces_fiche_reservation")) && ($ressource["comment_room"] != ''))
	$d['visuDescriptionComplete'] = 1;

// Limitation par domaine
$d['max_booking_per_area'] = grr_sql_query1("SELECT max_booking FROM ".TABLE_PREFIX."_area WHERE id = '".protect_data_sql($id_area)."'");


//Image de la ressource
$nom_picture = '';

$cledDossier = hash('ripemd128', $ressource["id"].Settings::get("tokenprivee"));
$dossier = './personnalisation/'.$gcDossierImg.'/ressources/'.$ressource["id"].'-'.$cledDossier.'/';

if(file_exists($dossier.$ressource['picture_room']))
	$d['lienImage'] = $dossier.$ressource['picture_room'];



echo $twig->render('ressourcefiche.twig', array('trad' => $trad, 'd' => $d, 'settings' => $AllSettings, 'ressource' => $ressource));
?>