<?php
/**
 * admin_type.php
 * Interface de gestion des types de réservations
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2022-04-21 14:34$
 * @author    JeromeB & Laurent Delineau
 * @copyright Copyright 2003-2022 Team DEVOME - JeromeB
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

get_vocab_admin("admin_type");
get_vocab_admin("admin_type_explications");
get_vocab_admin("display_add_type");

get_vocab_admin("type_num");
get_vocab_admin("type_name");
get_vocab_admin("type_color");
get_vocab_admin("type_order");
get_vocab_admin("disponible_pour");
get_vocab_admin("action");
get_vocab_admin("all");
get_vocab_admin("gestionnaires_et_administrateurs");
get_vocab_admin("only_administrators");

get_vocab_admin("confirm_del");
get_vocab_admin("cancel");
get_vocab_admin("delete");

get_vocab_admin("YES");
get_vocab_admin("NO");

get_vocab_admin("type_resa_manquant_msg");
get_vocab_admin("type_resa_manquant_titre");
get_vocab_admin("type_resa_vide_msg");

$trad['TitrePage'] = $trad['admin_type'];
$trad['SousTitrePage'] = 'Administration';

if ((isset($_GET['action_del'])) && ($_GET['jsconfirmed'] == 1) && ($_GET['action_del'] = 'yes'))
{
	// faire le test si il existe une réservation en cours avec ce type de réservation
	$type_id = grr_sql_query1("SELECT type_letter FROM ".TABLE_PREFIX."_type_area WHERE id = '".$_GET['type_del']."'");
	$test1 = grr_sql_query1("SELECT count(id) FROM ".TABLE_PREFIX."_entry WHERE type= '".$type_id."'");
	$test2 = grr_sql_query1("SELECT count(id) FROM ".TABLE_PREFIX."_repeat WHERE type= '".$type_id."'");
	if (($test1 != 0) || ($test2 != 0))
	{
		$msg =  "Suppression impossible : des réservations ont été enregistrées avec ce type.";
		affiche_pop_up($msg,"admin");
	}
	else
	{
		$sql = "DELETE FROM ".TABLE_PREFIX."_type_area WHERE id='".$_GET['type_del']."'";
		if (grr_sql_command($sql) < 0)
			fatal_error(1, "<p>" . grr_sql_error());
		$sql = "DELETE FROM ".TABLE_PREFIX."_j_type_area WHERE id_type='".$_GET['type_del']."'";
		if (grr_sql_command($sql) < 0)
			fatal_error(1, "<p>" . grr_sql_error());
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

?>