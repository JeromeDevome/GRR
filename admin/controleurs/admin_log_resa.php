<?php
/**
 * admin_log_resa.php
 * Interface de gestion des connexions
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2026-09-27 18:40$
 * @author    JeromeB
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

$grr_script_name = "admin_log_resa.php";

// Accès à la page
SecuAccess::CheckAccess(6, $back);


// les variables attendues et leur type
$form_vars = array(
    'p_idresa' => array('int', 0)
);
// récupération des valeurs des variables passées en paramètres
foreach($form_vars as $var => $params)
    $$var = SecuChaine::GetFormVarSecure($var, $params[0], $params[1]);


/** Affichage de la page **/
	// Infos actuel de la réservation
	$sql = "SELECT ".TABLE_PREFIX."_entry.name,
	".TABLE_PREFIX."_entry.id,
	".TABLE_PREFIX."_entry.description,
	".TABLE_PREFIX."_entry.beneficiaire,
	".TABLE_PREFIX."_room.room_name,
	".TABLE_PREFIX."_area.area_name,
	".TABLE_PREFIX."_entry.type,
	".TABLE_PREFIX."_entry.room_id,
	".TABLE_PREFIX."_entry.repeat_id,
	".grr_sql_syntax_timestamp_to_unix("".TABLE_PREFIX."_entry.timestamp").",
	(".TABLE_PREFIX."_entry.end_time - ".TABLE_PREFIX."_entry.start_time),
	".TABLE_PREFIX."_entry.start_time,
	".TABLE_PREFIX."_entry.end_time,
	".TABLE_PREFIX."_entry.statut_entry,
	".TABLE_PREFIX."_room.delais_option_reservation,
	".TABLE_PREFIX."_entry.option_reservation, " .
	"".TABLE_PREFIX."_entry.moderate,
	".TABLE_PREFIX."_entry.beneficiaire_ext,
	".TABLE_PREFIX."_entry.create_by,
	".TABLE_PREFIX."_entry.jours,
	".TABLE_PREFIX."_room.active_ressource_empruntee,
	".TABLE_PREFIX."_entry.clef,
	".TABLE_PREFIX."_entry.courrier,
	".TABLE_PREFIX."_room.active_cle,
	".TABLE_PREFIX."_entry.nbparticipantmax
	FROM ".TABLE_PREFIX."_entry, ".TABLE_PREFIX."_room, ".TABLE_PREFIX."_area
	WHERE ".TABLE_PREFIX."_entry.room_id = ".TABLE_PREFIX."_room.id
	AND ".TABLE_PREFIX."_room.area_id = ".TABLE_PREFIX."_area.id
	AND ".TABLE_PREFIX."_entry.id='".$p_idresa."'";
	$res = grr_sql_query($sql);
	if (!$res)
		fatal_error(0, grr_sql_error());

	$resa = grr_sql_row_keyed($res, 0);
	grr_sql_free($res);


	// Historique de la réservation
	$sql = "SELECT idlogresa, date, identifiant, action, infoscomp FROM ".TABLE_PREFIX."_log_resa WHERE idresa = '".$p_idresa."' ORDER BY date ASC";
	$res = grr_sql_query($sql);

	$logsResa = array ();

	if ($res)
	{
		for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
		{
			$logsResa[] = array('date' => $row[1], 'identifiant' => $row[2], 'action' => $row[3], 'infos' => $row[4]);
		}
	}

	// Historique des notifications liées à la réservation
	$sql = "SELECT date, sujet, type, erreur, idlogmail FROM ".TABLE_PREFIX."_log_mail WHERE idresa = '".$p_idresa."' ORDER BY date ASC";
	$res2 = grr_sql_query($sql);


	if ($res2)
	{
		for ($i = 0; ($row = grr_sql_row($res2, $i)); $i++)
		{
			$type = "";
			if($row[2] == 1) {
				$type = get_vocab("mail_desc_dest_adm");
			} elseif($row[2] == 2) {
				$type = get_vocab("mail_desc_dest_beneficiaire");
			} elseif($row[2] == 3) {
				$type = get_vocab("mail_desc_dest_gestionnaire");
			}

			$logsResa[] = array('date' => date('Y-m-d H:i:s', $row[0]), 'identifiant' => $type, 'action' => 8, 'infos' => $row[1], 'erreur' => $row[3], 'idlogmail' => $row[4]);
		}
	}

	// Tri global par date
	usort($logsResa, function($a, $b) {

		$dateA = is_numeric($a['date'])
			? (int)$a['date']
			: strtotime($a['date']);

		$dateB = is_numeric($b['date'])
			? (int)$b['date']
			: strtotime($b['date']);

		return $dateA <=> $dateB;
	});


	echo $twig->render('admin_log_resa.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings,  'resa' => $resa, 'logsresa' => $logsResa ));
?>