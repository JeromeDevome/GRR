<?php
/**
 * moderate_entry_do.php
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
include "personnalisation/connect.inc.php";
include "include/config.inc.php";
include "include/functions.inc.php";
include "include/$dbsys.inc.php";
include "include/misc.inc.php";
include "include/mrbs_sql.inc.php";
// Settings
require_once("./include/settings.class.php");
//Chargement des valeurs de la table settingS
if (!Settings::load())
	die("Erreur chargement settings");
// Session related functions
require_once("./include/session.inc.php");
// Paramètres langage
include "include/language.inc.php";
// Resume session
$fin_session = 'n';
if (!grr_resumeSession())
	$fin_session = 'y';
if (($fin_session == 'y') && (Settings::get("authentification_obli") == 1))
{
	header("Location: ./logout.php?auto=1&url=$url");
	die();
}
if ((Settings::get("authentification_obli") == 0) && (getUserName() == ''))
	$type_session = "no_session";
else
	$type_session = "with_session";
// On vérifie que l'utilisateur a bien le droit d'être ici
$room_id = grr_sql_query1("SELECT room_id FROM ".TABLE_PREFIX."_entry WHERE id='".$_POST['id']."'");
$back = '';
if (isset($_SERVER['HTTP_REFERER']))
	$back = htmlspecialchars($_SERVER['HTTP_REFERER']);
if (authGetUserLevel(getUserName(), $room_id) < 3)
{
	showAccessDenied($back);
	exit();
}
// j'ai besoin de $repeat_id '
$sql = "SELECT repeat_id FROM ".TABLE_PREFIX."_entry WHERE id =".$_POST['id'];
$res = grr_sql_query($sql);
if (! $res)
	fatal_error(0, grr_sql_error());
$row = grr_sql_row($res, 0);
$repeat_id = $row['0'];
// Initialisation
$series = 0;
if ($_POST['moderate'] == "S1")
{
	$_POST['moderate'] = "1";
	$series = 1;
}
if ($_POST['moderate'] == "S0")
{
	$_POST['moderate'] = "0";
	$series = 1;
}
if ($series == 0)
{
	//moderation de la ressource
	if ($_POST['moderate'] == 1)
		$sql = "UPDATE ".TABLE_PREFIX."_entry SET moderate = 2 WHERE id = ".$_POST['id'];
	else
		$sql = "UPDATE ".TABLE_PREFIX."_entry SET moderate = 3 WHERE id = ".$_POST['id'];
	$res = grr_sql_query($sql);
	if (!$res)
		fatal_error(0, grr_sql_error());
	if (!(grr_add_ligne_moderation($_POST['id'],getUserName(),$_POST['description'])))
		fatal_error(0, grr_sql_error());
	$tab_id_moderes = array();
}
else
{
	// cas d'une série
	// on constitue le tableau des id de la périodicité
	$sql = "SELECT id FROM ".TABLE_PREFIX."_entry WHERE repeat_id=".$repeat_id;
	$res = grr_sql_query($sql);
	if (!$res)
		fatal_error(0, grr_sql_error());
	$tab_entry = array();
	for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
		$tab_entry[] = $row['0'];
	$tab_id_moderes = array();
	// Boucle sur les résas
	foreach ($tab_entry as $entry_tom)
	{
		$test = grr_sql_query1("SELECT count(id) FROM ".TABLE_PREFIX."_entry_moderate WHERE id = '".$entry_tom."'");
		// Si il existe déjà une entrée dans ".TABLE_PREFIX."_entry_moderate, cela signifie que la réservation a déjà été modérée.
		// Sinon :
		if ($test == 0)
		{
			//moderation de la ressource
			if ($_POST['moderate'] == 1)
				$sql = "UPDATE ".TABLE_PREFIX."_entry SET moderate = 2 WHERE id = '".$entry_tom."'";
			else
				$sql = "UPDATE ".TABLE_PREFIX."_entry SET moderate = 3 WHERE id = '".$entry_tom."'";
			$res = grr_sql_query($sql);
			if (!$res)
				fatal_error(0, grr_sql_error());
			if (!(grr_add_ligne_moderation($entry_tom,getUserName(),$_POST['description'])))
				fatal_error(0, grr_sql_error());
			// Backup : on enregistre les infos dans ".TABLE_PREFIX."_entry_moderate
			// On constitue un tableau des réservations modérées
			$tab_id_moderes[] = $entry_tom;
		}
	}
}
// Avant d'effacer la réservation, on procède à la notification par mail
send_mail($_POST['id'],6,$dformat,$tab_id_moderes);
//moderation de la ressource
if ($_POST['moderate'] != 1)
{
	// on efface l'entrée de la base
	if ($series == 0)
	{
		$sql = "UPDATE ".TABLE_PREFIX."_entry SET supprimer = 1 WHERE id = ".$_POST['id'];
		$res = grr_sql_query($sql);
		if (!$res)
			fatal_error(0, grr_sql_error());
		insertLogResa($_POST['id'], 5, "Via modération");
	}
	else
	{
		// On sélectionne toutes les réservation de la périodicité
		$res = grr_sql_query("SELECT id FROM ".TABLE_PREFIX."_entry WHERE repeat_id='".$repeat_id."'");
		if (! $res)
			fatal_error(0, grr_sql_error());
		for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
		{
			$entry_tom = $row['0'];
			// Pour chaque réservation, on teste si celle-ci a été refusée
			$test = grr_sql_query1("SELECT count(id) FROM ".TABLE_PREFIX."_entry_moderate WHERE id = '".$entry_tom."' and moderate='3'");
			// Si oui, on supprime la réservation
			if ($test > 0)
			{
				$del = grr_sql_query("UPDATE ".TABLE_PREFIX."_entry SET supprimer = 1 WHERE id = '".$entry_tom."'");
				insertLogResa($entry_tom, 5, "Via modération périodicité");
			}
		}
		// On supprime l'info de périodicité
		$del_repeat = grr_sql_query("DELETE FROM ".TABLE_PREFIX."_repeat WHERE id='".$repeat_id."'");
		$dupdate_repeat = grr_sql_query("UPDATE ".TABLE_PREFIX."_entry set repeat_id = '0' WHERE repeat_id='".$repeat_id."'");
	}
}
$back = 'view_entry.php?id='.$_POST['id'].'&page='.$_POST['page'];
// recuperation
header ('Location: '.$back);
exit();
?>
