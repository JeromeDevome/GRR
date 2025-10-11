<?php
/**
 * admin_type.php
 * Interface de gestion des types de réservations
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2025-10-11 17:45$
 * @author    JeromeB & Laurent Delineau
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
$grr_script_name = "admin_type.php";

$trad = $vocab;

$action = isset($_GET["action"]) ? $_GET["action"] : 0;
$actionpost = isset($_POST["actionpost"]) ? $_POST["actionpost"] : 0;

$d['enregistrement'] = isset($_GET["enregistrement"]) ? $_GET["enregistrement"] : 0;
$d['enregistrement_msg'] = isset($_GET["enregistrement_msg"]) ? $_GET["enregistrement_msg"] : "";

$trad['TitrePage'] = $trad['admin_type'];
$trad['SousTitrePage'] = 'Administration';

// Action supression
if ((isset($_GET['action_del'])) && ($_GET['jsconfirmed'] == 1) && ($_GET['action_del'] = 'yes'))
{
	// faire le test si il existe une réservation en cours avec ce type de réservation
	$type_id = grr_sql_query1("SELECT type_letter FROM ".TABLE_PREFIX."_type_area WHERE id = '".$_GET['type_del']."'");
	$test1 = grr_sql_query1("SELECT count(id) FROM ".TABLE_PREFIX."_entry WHERE type= '".$type_id."'");
	$test2 = grr_sql_query1("SELECT count(id) FROM ".TABLE_PREFIX."_repeat WHERE type= '".$type_id."'");
	if (($test1 != 0) || ($test2 != 0))
	{
		$d['enregistrement'] = 3;
		$d['enregistrement_msg'] = "Suppression impossible : des réservations ont été enregistrées avec ce type.";
	}
	else
	{
		$sql = "DELETE FROM ".TABLE_PREFIX."_type_area WHERE id='".$_GET['type_del']."'";
		if (grr_sql_command($sql) < 0)
			fatal_error(1, "<p>" . grr_sql_error());
		$sql = "DELETE FROM ".TABLE_PREFIX."_j_type_area WHERE id_type='".$_GET['type_del']."'";
		if (grr_sql_command($sql) < 0)
			fatal_error(1, "<p>" . grr_sql_error());

		$d['enregistrement'] = 1;
		$d['enregistrement_msg'] = "Suppression effectuée avec succès.";
	}
}

// Action tris
if ($action == 1)
	$order = "type_letter";
elseif ($action == 2) 
	$order = "type_name";


if ($action == 1 || $action == 2) 
{
	$res = grr_sql_query("SELECT id, type_name, order_display FROM ".TABLE_PREFIX."_type_area ORDER BY $order");
	if ($res)
	{
		for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
		{
			$sql = "UPDATE ".TABLE_PREFIX."_type_area SET order_display = $i WHERE id=".$row[0];
			grr_sql_command($sql);

		}
		$d['enregistrement'] = 1;
		$d['enregistrement_msg'] = "Types triés avec succès.";
	}
}

// Action fusionner
if ($actionpost == 'fusionner')
{
	if (isset($_POST['type1']) && isset($_POST['type2']) && ($_POST['type1'] != $_POST['type2']))
	{
		$type1 = intval($_POST['type1']);
		$type2 = intval($_POST['type2']);
		$type_letter_1 = grr_sql_query1("SELECT type_letter FROM ".TABLE_PREFIX."_type_area WHERE id = '".$type1."'");
		$type_letter_2 = grr_sql_query1("SELECT type_letter FROM ".TABLE_PREFIX."_type_area WHERE id = '".$type2."'");
		if ($type_letter_1 != '' && $type_letter_2 != '')
		{
			// On met à jour les réservations
			$sql = "UPDATE ".TABLE_PREFIX."_entry SET type = '".$type_letter_2."' WHERE type = '".$type_letter_1."'";
			if (grr_sql_command($sql) < 0)
				fatal_error(1, "<p>" . grr_sql_error());
			$sql = "UPDATE ".TABLE_PREFIX."_repeat SET type = '".$type_letter_2."' WHERE type = '".$type_letter_1."'";
			if (grr_sql_command($sql) < 0)
				fatal_error(1, "<p>" . grr_sql_error());
			// On supprime le type 2
			$sql = "DELETE FROM ".TABLE_PREFIX."_type_area WHERE id='".$type1."'";
			if (grr_sql_command($sql) < 0)
				fatal_error(1, "<p>" . grr_sql_error());
			$sql = "DELETE FROM ".TABLE_PREFIX."_j_type_area WHERE id_type='".$type1."'";
			if (grr_sql_command($sql) < 0)
				fatal_error(1, "<p>" . grr_sql_error());

			$d['enregistrement'] = 1;
			$d['enregistrement_msg'] = "Types fusionnés avec succès.";
		}
		else
		{
			$d['enregistrement'] = 3;
			$d['enregistrement_msg'] = "Erreur lors de la fusion des types.";
		}
	}
	else
	{
		$d['enregistrement'] = 3;
		$d['enregistrement_msg'] = "Erreur : vous devez choisir deux types différents.";
	}

}



// Test de cohérence des types de réservation
$res = grr_sql_query("SELECT DISTINCT type FROM ".TABLE_PREFIX."_entry ORDER BY type");
if ($res)
{
	$listeManquant = "";
	for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
	{
		$test = grr_sql_query1("SELECT type_letter FROM ".TABLE_PREFIX."_type_area where type_letter='".$row[0]."'");
		if ($test == -1)
			$listeManquant .= $row[0]." ";
	}
}


$sql = "SELECT id, type_name, order_display, couleurhexa, couleurtexte, type_letter, disponible FROM ".TABLE_PREFIX."_type_area ORDER BY order_display,type_letter";
$typesResa = grr_sql_query($sql);

echo $twig->render($page.'.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings, 'types' => $typesResa, 'listeManquant' => $listeManquant));

?>