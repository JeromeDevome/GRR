<?php
/**
 * admin_type_area.php
 * interface de gestion des types de réservations pour un domaine
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2026-01-27 09:57$
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

$grr_script_name = "admin_type_area.php";

// Initialisation
$id_area = isset($_GET["id_area"]) ? intval($_GET["id_area"]) : NULL; // id_area est un entier
$types = array();

check_access(4, $back);

get_vocab_admin("admin_type");
get_vocab_admin("display_add_type");
get_vocab_admin("explications_active_type");

get_vocab_admin("type_num");
get_vocab_admin("type_name");
get_vocab_admin("type_color");
get_vocab_admin("type_order");
get_vocab_admin("type_valide_domaine");
get_vocab_admin("type_par_defaut");

get_vocab_admin("nobody");

get_vocab_admin("save");
get_vocab_admin("back");

$d['droitsAdmin'] = authGetUserLevel(getUserName(),-1);
$d['idArea'] = $id_area;

// Gestion du retour à la page précédente sans enregistrement
if (isset($_GET['change_done']))
{
	Header("Location: "."?p=admin_room&id_area=".$id_area);
	exit();
}

$sql = "SELECT id, type_name, order_display, couleurhexa, type_letter, couleurtexte FROM ".TABLE_PREFIX."_type_area
ORDER BY order_display, type_letter";

//
// Enregistrement
//
if (isset($_GET['valider']))
{
	$res = grr_sql_query($sql);
	$nb_types_valides = 0;
	if ($res)
	{
		foreach($res as $row)
		{
			if (isset($_GET[$row['id']]))
			{
				$nb_types_valides ++;
				$del = grr_sql_query("DELETE FROM ".TABLE_PREFIX."_j_type_area WHERE id_area='".$id_area."' AND id_type = '".$row['id']."'");
			}
			else
			{
				$type_si_aucun = $row['id'];
				$test = grr_sql_query1("SELECT count(id_type) FROM ".TABLE_PREFIX."_j_type_area WHERE id_area = '".$id_area."' AND id_type = '".$row['id']."'");
				if ($test == 0)
				{
					$sql1 = "INSERT INTO ".TABLE_PREFIX."_j_type_area SET id_area='".$id_area."', id_type = '".$row['id']."'";
					if (grr_sql_command($sql1) < 0)
						fatal_error(1, "<p>" . grr_sql_error());
				}
			}
		}
	}
	if ($nb_types_valides == 0)
	{
		// Aucun type n'a été sélectionné. Dans ce cas, on impose au moins un type :
		$del = grr_sql_query("DELETE FROM ".TABLE_PREFIX."_j_type_area WHERE id_area='".$id_area."' AND id_type = '".$type_si_aucun."'");
		$d['enregistrement'] = 3;
		$d['msgToast'] = get_vocab("def_type_non_valide");
	}
	else
	{
		$d['enregistrement'] = 1;
		$d['msgToast'] = get_vocab('modify_succeed');
	}
	// Type par défaut :
	// On enregistre le nouveau type par défaut :
	$reg_type_par_defaut = grr_sql_query("UPDATE ".TABLE_PREFIX."_area SET id_type_par_defaut='".$_GET['id_type_par_defaut']."' WHERE id='".$id_area."'");
}

//
// Affichage
//
$d['nomDomaine'] = grr_sql_query1("SELECT area_name FROM ".TABLE_PREFIX."_area WHERE id='".$id_area."'");

$res = grr_sql_query($sql);
$nb_lignes = grr_sql_count($res);

if ($res && $nb_lignes > 0)
{
	foreach($res as $row)
	{
		$dispoDomaine = grr_sql_query1("SELECT count(id_type) FROM ".TABLE_PREFIX."_j_type_area WHERE id_area = '".$id_area."' AND id_type = '".$row['id']."'");

		$types[] = array('id' => $row['id'], 'type_letter' => $row['type_letter'], 'type_name' => $row['type_name'], 'couleurhexa' => $row['couleurhexa'], 'couleurtexte' => $row['couleurtexte'], 'order_display' => $row['order_display'], 'dispodomaine' => $dispoDomaine);
	}

	$d['defautType'] = grr_sql_query1("SELECT id_type_par_defaut FROM ".TABLE_PREFIX."_area WHERE id = '".$id_area."'");
}


echo $twig->render('admin_type_area.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings, 'types' => $types));
?>