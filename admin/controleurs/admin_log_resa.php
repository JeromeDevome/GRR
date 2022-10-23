<?php
/**
 * admin_log_resa.php
 * Interface de gestion des connexions
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2020-01-30 21:00$
 * @author    JeromeB
 * @copyright Copyright 2003-2021 Team DEVOME - JeromeB
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

check_access(6, $back);


get_vocab_admin('admin_view_emails');

// Afficher : Logs
get_vocab_admin('date2');
get_vocab_admin('mail_de');
get_vocab_admin('mail_a');
get_vocab_admin('mail_sujet');
get_vocab_admin('mail_message');

get_vocab_admin('users_connected');

$idresa = $_GET['idresa'];

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
AND ".TABLE_PREFIX."_entry.id='".$idresa."'";
$res = grr_sql_query($sql);
if (!$res)
	fatal_error(0, grr_sql_error());

$resa = grr_sql_row_keyed($res, 0);
grr_sql_free($res);


// Historique de la réservation
$sql = "SELECT idlogresa, date, identifiant, action, infoscomp FROM ".TABLE_PREFIX."_log_resa WHERE idresa = '".$idresa."' ORDER by date asc";
$res = grr_sql_query($sql);

$logsResa = array ();

if ($res)
{
	for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
	{
		$logsResa[] = array('date' => $row[1], 'identifiant' => $row[2], 'action' => $row[3], 'infos' => $row[4]);
	}
}



echo $twig->render('admin_log_resa.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings,  'resa' => $resa, 'logsresa' => $logsResa ));
?>