<?php
/**
 * admin_type_modify.php
 * interface de création/modification des types de réservations
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2018-07-23 21:10$
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

$grr_script_name = "admin_type_modify.php";

get_vocab_admin("admin_type_explications");
get_vocab_admin("type_name");
get_vocab_admin("type_num");
get_vocab_admin("choose");
get_vocab_admin("order_display");
get_vocab_admin("all");
get_vocab_admin("gestionnaires_et_administrateurs");
get_vocab_admin("only_administrators");
get_vocab_admin("disponible_pour");
get_vocab_admin("type_color_actuel");
get_vocab_admin("type_color_fond");
get_vocab_admin("type_color_texte");

get_vocab_admin("save");
get_vocab_admin("back");
get_vocab_admin("save_and_back");

$trad['SousTitrePage'] = 'Administration';

$ok = NULL;
check_access(6, $back);
// Initialisation
$id_type = isset($_GET["id_type"]) ? $_GET["id_type"] : 0;
$type_name = isset($_GET["type_name"]) ? $_GET["type_name"] : NULL;
$order_display = isset($_GET["order_display"]) ? $_GET["order_display"] : NULL;
$type_letter = isset($_GET["type_letter"]) ? $_GET["type_letter"] : NULL;
$couleur_hexa = isset($_GET["couleurhexa"]) ? $_GET["couleurhexa"] : NULL;
$couleur_txt = isset($_GET["couleurtexte"]) ? $_GET["couleurtexte"] : NULL;
$disponible = isset($_GET["disponible"]) ? $_GET["disponible"] : NULL;
$msg = "";

if (isset($_GET["change_room_and_back"]))
{
	$_GET['change_type'] = "yes";
	$_GET['change_done'] = "yes";
}
// Enregistrement
if (isset($_GET['change_type']))
{
	$_SESSION['displ_msg'] = "yes";
	if ($type_name == '')
		$type_name = "A définir";
	if ($type_letter == '')
		$type_letter = "A";
	if ($couleur_hexa == '')
		$couleur_hexa = "#2ECC71";
	if ($couleur_txt == '')
		$couleur_txt = "#000000";
	if ($disponible == '')
		$disponible = "2";
	if ($id_type > 0) // Modif
	{
		$test = grr_sql_query1("SELECT count(id) FROM ".TABLE_PREFIX."_type_area WHERE type_letter='".$type_letter."' AND id!='".$id_type."'");
		if ($test > 0)
		{
			$msg = "Enregistrement impossible : Un type portant la même lettre existe déjà.";
			$ok = 'no';
		}
		else
		{
			$sql = "UPDATE ".TABLE_PREFIX."_type_area SET
			type_name='".protect_data_sql($type_name)."',
			order_display =";
			if (is_numeric($order_display))
				$sql= $sql .intval($order_display).",";
			else
				$sql= $sql ."0,";
			$sql = $sql . 'type_letter="'.$type_letter.'",';
			$sql = $sql . 'couleur=\'1\',';
			$sql = $sql . 'couleurhexa="'.$couleur_hexa.'",';
			$sql = $sql . 'couleurtexte="'.$couleur_txt.'",';
			$sql = $sql . 'disponible="'.$disponible.'"';
			$sql = $sql . " WHERE id=$id_type";
			if (grr_sql_command($sql) < 0)
			{
				fatal_error(0, get_vocab('update_type_failed') . grr_sql_error());
				$ok = 'no';
			}
			else
				$msg = get_vocab("message_records");
		}
	}
	else // Ajout
	{
		$test = grr_sql_query1("SELECT count(id) FROM ".TABLE_PREFIX."_type_area WHERE type_letter='".$type_letter."'");
		if ($test > 0){
			$msg = "Enregistrement impossible : Un type portant la même lettre existe déjà !";
			$ok = 'no';
		}
		else
		{
			$sql = "INSERT INTO ".TABLE_PREFIX."_type_area SET
			type_name='".protect_data_sql($type_name)."',
			order_display =";
			if (is_numeric($order_display))
				$sql= $sql .intval($order_display).",";
			else
				$sql= $sql ."0,";
			$sql = $sql . 'type_letter="'.$type_letter.'",';
			$sql = $sql . 'couleur=\'1\',';
			$sql = $sql . 'couleurhexa="'.$couleur_hexa.'",';
			$sql = $sql . 'couleurtexte="'.$couleur_txt.'"';
			if (grr_sql_command($sql) < 0)
			{
				fatal_error(1, "<p>" . grr_sql_error());
				$ok = 'no';
			}
			else
				$msg = get_vocab("message_records");
		}

	}
}
// Si pas de problème, retour à la page d'accueil après enregistrement
if ((isset($_GET['change_done'])) && (!isset($ok)))
{
	$_SESSION['displ_msg'] = 'yes';
	Header("Location: "."?p=admin_type&msg=".$msg);
	exit();
}

affiche_pop_up($msg,"admin");

if ((isset($id_type)) && ($id_type > 0))
{
	$res = grr_sql_query("SELECT * FROM ".TABLE_PREFIX."_type_area WHERE id=$id_type");
	if (!$res)
		fatal_error(0, get_vocab('message_records_error'));
	$typeResa = grr_sql_row_keyed($res, 0);
	grr_sql_free($res);
	$change_type = 'modif';
	$trad['admin_type_titre'] = get_vocab("admin_type_modify_modify");
}
else
{
	$typeResa["id"] = '0';
	$typeResa["order_display"] = 0;
	$typeResa["disponible"] = 2;
	$trad['admin_type_titre'] = get_vocab('admin_type_modify_create');
}
	
$trad['TitrePage'] = $trad['admin_type_titre'];

$letter = "A";
for ($i = 1; $i <= 702; $i++)
{
	$lettres[$i] = array('lettre' => $letter);
	$letter++;
}

?>