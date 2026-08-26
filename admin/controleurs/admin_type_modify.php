<?php
/**
 * admin_type_modify.php
 * interface de création/modification des types de réservations
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2026-08-26 21:10$
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

$grr_script_name = "admin_type_modify.php";

// Accès à la page
SecuAccess::CheckAccess(6, $back);

// les variables attendues et leur type
$form_vars = array(
	'p_submit' => 'int',
    'p_id_type' => 'int',
    'p_type_name' => 'string',
    'p_order_display' => 'int',
	'p_type_letter' => 'string',
	'p_couleurhexa' => 'color',
	'p_couleurtexte' => 'color',
	'p_couleuricone' => 'color',
	'p_disponible' => 'int',
	'p_change_type' => 'int',
	'p_change_type_and_back' => 'int',
);
// récupération des valeurs des variables passées en paramètres
foreach($form_vars as $var => $var_type)
    $$var = SecuChaine::GetFormVarSecure($var, $var_type);


// Initialisation
$trad['TitrePage']		= $trad['admin_type'];
$trad['SousTitrePage']	= $trad['add'];
$ok = true;


/** Actions **/
	if (isset($p_change_type) || isset($p_change_type_and_back))
	{
		if ($p_type_name == '')
			$type_name = "A définir";
		if ($p_type_letter == '')
			$p_type_letter = "A";
		if ($p_couleurhexa == '')
			$p_couleurhexa = "#2ECC71";
		if ($p_couleurtexte == '')
			$p_couleurtexte = "#000000";
		if ($p_couleuricone == '')
			$p_couleuricone = "#000000";
		if ($p_disponible == '')
			$p_disponible = "2";

		if ($p_id_type > 0) // Modif
		{
			$test = grr_sql_query1("SELECT count(id) FROM ".TABLE_PREFIX."_type_area WHERE type_letter='".$p_type_letter."' AND id!='".$p_id_type."'");
			if ($test > 0)
			{
				$d['enregistrement'] = 3;
				$d['enregistrement_msg'] = "Enregistrement impossible : Un type portant la même lettre existe déjà.";
				$ok = false;
			}
			else
			{
				$sql = "UPDATE ".TABLE_PREFIX."_type_area SET
				type_name='".SecuChaine::ProtectDataSql($p_type_name)."',
				order_display =";
				if (is_numeric($p_order_display))
					$sql= $sql .intval($p_order_display).",";
				else
					$sql= $sql ."0,";
				$sql = $sql . 'type_letter="'.$p_type_letter.'",';
				$sql = $sql . 'couleur=\'1\',';
				$sql = $sql . 'couleurhexa="'.$p_couleurhexa.'",';
				$sql = $sql . 'couleurtexte="'.$p_couleurtexte.'",';
				$sql = $sql . 'couleuricone="'.$p_couleuricone.'",';
				$sql = $sql . 'disponible="'.$p_disponible.'"';
				$sql = $sql . " WHERE id=$p_id_type";
				if (grr_sql_command($sql) < 0)
				{
					fatal_error(0, get_vocab('update_type_failed') . grr_sql_error());
					$ok = 'no';
				}
				else
				{
					$d['enregistrement'] = 1;
					$d['enregistrement_msg'] = get_vocab("message_records");
				}
			}
		}
		else // Ajout
		{
			$test = grr_sql_query1("SELECT count(id) FROM ".TABLE_PREFIX."_type_area WHERE type_letter='".$p_type_letter."'");
			if ($test > 0){
				$d['enregistrement'] = 3;
				$d['enregistrement_msg'] = "Enregistrement impossible : Un type portant la même lettre existe déjà !";
				$ok = 'no';
			}
			else
			{
				$sql = "INSERT INTO ".TABLE_PREFIX."_type_area SET
				type_name='".SecuChaine::ProtectDataSql($p_type_name)."',
				order_display =";
				if (is_numeric($p_order_display))
					$sql= $sql .intval($p_order_display).",";
				else
					$sql= $sql ."0,";
				$sql = $sql . 'type_letter="'.$p_type_letter.'",';
				$sql = $sql . 'couleur=\'1\',';
				$sql = $sql . 'couleurhexa="'.$p_couleurhexa.'",';
				$sql = $sql . 'couleurtexte="'.$p_couleurtexte.'",';
				$sql = $sql . 'couleuricone="'.$p_couleuricone.'"';
				if (grr_sql_command($sql) < 0)
				{
					fatal_error(1, "<p>" . grr_sql_error());
					$ok = false;
				}
				else
				{
					$d['enregistrement'] = 1;
					$d['enregistrement_msg'] = get_vocab("message_records");
				}
			}

		}
	}
	// Si pas de problème, retour à la page d'accueil après enregistrement
	if ((isset($p_change_type_and_back)) && $ok == true)
	{
		Header("Location: "."?p=admin_type&p_enregistrement=".$d['enregistrement']."&p_enregistrement_msg=".$d['enregistrement_msg']);
		exit();
	}

/** Affichage de la page **/
	// Données du type de réservation à modifier
	if ((isset($p_id_type)) && ($p_id_type > 0))
	{
		$res = grr_sql_query("SELECT * FROM ".TABLE_PREFIX."_type_area WHERE id=$p_id_type");
		if (!$res)
			fatal_error(0, get_vocab('message_records_error'));
		$typeResa = grr_sql_row_keyed($res, 0);
		grr_sql_free($res);
		$change_type = 'modif';
		$trad['SousTitrePage'] = get_vocab("change");
	}
	else // Création d'un nouveau type de réservation
	{
		$typeResa["id"] = '0';
		$typeResa["order_display"] = 0;
		$typeResa["disponible"] = 2;
	}

	// On charge les lettres
	$letter = "A";
	for ($i = 1; $i <= 702; $i++)
	{
		$lettres[$i] = array('lettre' => $letter);
		$letter++;
	}

	echo $twig->render($page.'.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings, 'type' => $typeResa, 'lettres' => $lettres));
?>