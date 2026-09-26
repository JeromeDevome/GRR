<?php
/**
 * admin_type_area.php
 * interface de gestion des types de réservations pour un domaine
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2026-09-26 17:00$
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

// Accès à la page
SecuAccess::CheckAccess(4, $back);


// les variables attendues et leur type
$form_vars = array(
    'p_id_area' => array('int', 0),
	'p_id_type_par_defaut' => array('int', -1),
	'p_submit' => array('int', 0),
	'p_types' => array('array', array()), // tableau des types sélectionnés pour le domaine
);
// récupération des valeurs des variables passées en paramètres
foreach($form_vars as $var => $params)
    $$var = SecuChaine::GetFormVarSecure($var, $params[0], $params[1]);

/** Actions **/
	if($p_submit == 1)
	{
		$typesSelect = array_unique(array_map('strval', array_map(array('SecuChaine', 'Numeric'), $p_types)));
		$nb_Select = count($typesSelect);
		$typesInvalide = array();
		$sql = "SELECT id FROM ".TABLE_PREFIX."_type_area";
		$res = grr_sql_query($sql);
		$typeDefaut = $p_id_type_par_defaut;
		$erreurTypeDefaut = false;

		if ($res)
		{
			foreach ($res as $row)
			{
				if (!in_array((string) $row['id'], $typesSelect, true))
					$typesInvalide[] = $row['id'];
			}
		}

		if ($nb_Select > 0)
		{
			// On supprime tout avant d'insérer les nouveaux
			$del = grr_sql_query("DELETE FROM ".TABLE_PREFIX."_j_type_area WHERE id_area='".$p_id_area."'");

			foreach ($typesInvalide as $typeI)
			{
				$sql1 = "INSERT INTO ".TABLE_PREFIX."_j_type_area SET id_area='".$p_id_area."', id_type = '".$typeI."'";
				if (grr_sql_command($sql1) < 0)
					fatal_error(1, "<p>" . grr_sql_error());

				if($typeI == $typeDefaut) // On n'accepte pas que le type par défaut soit pas valide
				{
					$erreurTypeDefaut = true;
					$typeDefaut = -1;
				}
			}
		}

		if ($nb_Select == 0)
		{
			$d['enregistrement'] = 3;
			$d['msgToast'] = get_vocab("def_type_non_valide");
		}
		elseif($erreurTypeDefaut == true)
		{
			$d['enregistrement'] = 2;
			$d['msgToast'] = "La valeur par défaut doit-être un type valide pour le domaine !";
		}
		else
		{
			$d['enregistrement'] = 1;
			$d['msgToast'] = get_vocab('modify_succeed');
		}

		// On enregistre le nouveau type par défaut :
		$reg_type_par_defaut = grr_sql_query("UPDATE ".TABLE_PREFIX."_area SET id_type_par_defaut='".$typeDefaut."' WHERE id='".$p_id_area."'");
	}


/** Affichage de la page **/
	$trad['TitrePage']		= $trad["admin_type"];
	$trad['SousTitrePage']	= grr_sql_query1("SELECT area_name FROM ".TABLE_PREFIX."_area WHERE id='".$p_id_area."'");

	$types				= array();
	$d['idArea']		= $p_id_area;
	$d['droitsAdmin']	= SecuAccess::UserLevel(getUserName(),-1);

	$sql = "SELECT id, type_name, order_display, couleurhexa, type_letter, couleurtexte FROM ".TABLE_PREFIX."_type_area ORDER BY order_display, type_letter";
	$res = grr_sql_query($sql);
	$nb_lignes = grr_sql_count($res);

	if ($res && $nb_lignes > 0)
	{
		foreach($res as $row)
		{
			$dispoDomaine = grr_sql_query1("SELECT count(id_type) FROM ".TABLE_PREFIX."_j_type_area WHERE id_area = '".$p_id_area."' AND id_type = '".$row['id']."'");

			$types[] = array('id' => $row['id'], 'type_letter' => $row['type_letter'], 'type_name' => $row['type_name'], 'couleurhexa' => $row['couleurhexa'], 'couleurtexte' => $row['couleurtexte'], 'order_display' => $row['order_display'], 'dispodomaine' => $dispoDomaine);
		}

		$d['defautType'] = grr_sql_query1("SELECT id_type_par_defaut FROM ".TABLE_PREFIX."_area WHERE id = '".$p_id_area."'");
	}

	echo $twig->render('admin_type_area.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings, 'types' => $types));
?>