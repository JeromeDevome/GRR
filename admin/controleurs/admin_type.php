<?php
/**
 * admin_type.php
 * Interface de gestion des types de réservations
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2026-08-26 21:10$
 * @author    JeromeB & Laurent Delineau
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
$grr_script_name = "admin_type.php";

// Accès à la page
SecuAccess::CheckAccess(6, $back);

// les variables attendues et leur type
$form_vars = array(
    'p_action' => 'int', // 1 : tri par lettre, 2 : tri par nom, 3 : Supression d'un type, 4 : fusion de deux types
	'p_jsconfirmed' => 'int', // 1 : confirmation suppression
	'p_type_del' => 'int', // id du type à supprimer
	'p_type1' => 'int', // id du type 1 pour fusion
	'p_type2' => 'int', // id du type 2 pour fusion
	'p_enregistrement' => 'int', // 1 : enregistrement effectué, 2 : erreur, 3 : avertissement
	'p_enregistrement_msg' => 'string' // message d'enregistrement
);
// récupération des valeurs des variables passées en paramètres
foreach($form_vars as $var => $var_type)
    $$var = SecuChaine::GetFormVarSecure($var, $var_type);


$trad['TitrePage']			= $trad['admin_type'];
$d['enregistrement']		= $p_enregistrement;
$d['enregistrement_msg']	= $p_enregistrement_msg;


/** Actions **/
	/* Action tri des types */
	if ($p_action == 1 || $p_action == 2) 
	{
		if ($p_action == 1)
			$order = "type_letter";
		elseif ($p_action == 2) 
			$order = "type_name";

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
	/* Action suppression d'un type */
	elseif ($p_action == 3 && $p_jsconfirmed == 1)
	{
		// Faire le test si il existe une réservation en cours avec ce type de réservation
		$type_id = grr_sql_query1("SELECT type_letter FROM ".TABLE_PREFIX."_type_area WHERE id = '".$p_type_del."'");
		$test1 = grr_sql_query1("SELECT count(id) FROM ".TABLE_PREFIX."_entry WHERE type= '".$type_id."'");
		$test2 = grr_sql_query1("SELECT count(id) FROM ".TABLE_PREFIX."_repeat WHERE type= '".$type_id."'");
		if (($test1 != 0) || ($test2 != 0))
		{
			$d['enregistrement'] = 3;
			$d['enregistrement_msg'] = "Suppression impossible : des réservations ont été enregistrées avec ce type.";
		}
		else
		{
			$sql = "DELETE FROM ".TABLE_PREFIX."_type_area WHERE id='".$p_type_del."'";
			if (grr_sql_command($sql) < 0)
				fatal_error(1, "<p>" . grr_sql_error());
			$sql = "DELETE FROM ".TABLE_PREFIX."_j_type_area WHERE id_type='".$p_type_del."'";
			if (grr_sql_command($sql) < 0)
				fatal_error(1, "<p>" . grr_sql_error());

			$d['enregistrement'] = 1;
			$d['enregistrement_msg'] = "Suppression effectuée avec succès.";
		}
	}
	/* Action fusionner */
	elseif ($p_action == 4)
	{
		if (isset($p_type1) && isset($p_type2) && ($p_type1 != $p_type2))
		{
			$type_letter_1 = grr_sql_query1("SELECT type_letter FROM ".TABLE_PREFIX."_type_area WHERE id = '".$p_type1."'");
			$type_letter_2 = grr_sql_query1("SELECT type_letter FROM ".TABLE_PREFIX."_type_area WHERE id = '".$p_type2."'");
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
				$sql = "DELETE FROM ".TABLE_PREFIX."_type_area WHERE id='".$p_type1."'";
				if (grr_sql_command($sql) < 0)
					fatal_error(1, "<p>" . grr_sql_error());
				$sql = "DELETE FROM ".TABLE_PREFIX."_j_type_area WHERE id_type='".$p_type1."'";
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

/** Affichage de la page **/
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

	// Chargement des types de réservation
	$sql = "SELECT id, type_name, order_display, couleurhexa, couleurtexte, type_letter, disponible FROM ".TABLE_PREFIX."_type_area ORDER BY order_display,type_letter";
	$typesResa = grr_sql_query($sql);

	echo $twig->render($page.'.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings, 'types' => $typesResa, 'listeManquant' => $listeManquant));

?>