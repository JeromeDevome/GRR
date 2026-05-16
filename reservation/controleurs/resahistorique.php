<?php
/**
 * resahistorique.php
 * Interface de visualisation d'une réservation
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2026-03-21 14:15$
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
$grr_script_name = 'resahistorique.php';

$trad = $vocab;



$idresa = intval($_GET['idresa']);

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

// Vérifier que la réservation existe
if (!$resa || empty($resa))
{
	fatal_error(0, get_vocab('invalid_entry_id'));
}

// Contrôle d'accès - l'utilisateur doit être au minimum gestionnaire de la ressource (niveau >= 2)
$user_level = SecuAccess::UserLevel(getUserName(), $resa['room_id']);
if ($user_level <= 2)
{
	showAccessDenied($back);
	exit();
}


// Historique de la réservation
$sql = "SELECT idlogresa, date, identifiant, action, infoscomp FROM ".TABLE_PREFIX."_log_resa WHERE idresa = '".$idresa."' ORDER by date asc";
$res1 = grr_sql_query($sql);

$logsResa = array ();

if ($res1)
{
	for ($i = 0; ($row = grr_sql_row($res1, $i)); $i++)
	{
		$logsResa[] = array('date' => $row[1], 'identifiant' => $row[2], 'action' => $row[3], 'infos' => $row[4]);
	}
}

// Historique des notifications liées à la réservation
$sql = "SELECT date, sujet, type, erreur FROM ".TABLE_PREFIX."_log_mail WHERE idresa = '".$idresa."' ORDER by date asc";
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

		$logsResa[] = array('date' => date('Y-m-d H:i:s', $row[0]), 'identifiant' => $type, 'action' => 8, 'infos' => $row[1], 'erreur' => $row[3]);
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


echo $twig->render('resahistorique.twig', array('trad' => $trad, 'd' => $d, 'settings' => $AllSettings, 'resa' => $resa, 'logsresa' => $logsResa));
?>