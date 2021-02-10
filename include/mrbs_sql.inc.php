<?php
/**
 * mrbs_sql.inc.php
 * Bibliothèque de fonctions propres à l'application GRR
 * Dernière modification : $Date: 2019-02-16 16:40$
 * @author    JeromeB & Laurent Delineau & Marc-Henri PAMISEUX & Yan Naessens
 * @copyright Copyright 2003-2019 Team DEVOME - JeromeB
 * @link      http://www.gnu.org/licenses/licenses.html
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 */
/** mrbsCheckFree()
 *
 * Check to see if the time period specified is free
 *
 * $room_id   - Which room are we checking
 * $starttime - The start of period
 * $endtime   - The end of the period
 * $ignore    - An entry ID to ignore, 0 to ignore no entries
 * $repignore - A repeat ID to ignore everything in the series, 0 to ignore no series
 * $link      - prefix to link the pages called in "something"
 *
 * Returns:
 *   nothing   - The area is free
 *   something - An error occurred, the return value is human readable
 */
function mrbsCheckFree($room_id, $starttime, $endtime, $ignore, $repignore, $link="")
{
	global $vocab;
	//SELECT any meetings which overlap ($starttime,$endtime) for this room:
	$sql = "SELECT id, name, start_time FROM ".TABLE_PREFIX."_entry WHERE start_time < '".$endtime."' AND end_time > '".$starttime."' AND room_id = '".$room_id."' AND supprimer = 0";
	if ($ignore > 0)
		$sql .= " AND id <> $ignore";
	if ($repignore > 0)
		$sql .= " AND repeat_id <> $repignore";
	$sql .= " ORDER BY start_time";
	$res = grr_sql_query($sql);
	if (! $res)
		return grr_sql_error();
	if (grr_sql_count($res) == 0)
	{
		grr_sql_free($res);
		return "";
	}
	// Get the room's area ID for linking to day, week, and month views:
	$area = mrbsGetRoomArea($room_id);
	// Build a string listing all the conflicts:
	$err = "";
	for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
	{
		$starts = getdate($row[2]);
		$param_ym = "area=$area&amp;year=$starts[year]&amp;month=$starts[mon]";
		$param_ymd = $param_ym . "&amp;day=$starts[mday]";
		$err .= "<li><a href=\"".$link."view_entry.php?id=$row[0]\">$row[1]</a>"
		. " ( " . utf8_strftime('%A %d %B %Y %T', $row[2]) . ") "
		. "(<a href=\"".$link."day.php?$param_ymd\">".get_vocab("viewday")."</a>"
			. " | <a href=\"".$link."week.php?room=$room_id&amp;$param_ymd\">".get_vocab("viewweek")."</a>"
			. " | <a href=\"".$link."month.php?room=$room_id&amp;$param_ym\">".get_vocab("viewmonth")."</a>)</li>\n";
	}
	return $err;
}
/** grrCheckOverlap()
 *
 * Dans le cas d'une réservation avec périodicité,
 * Vérifie que les différents créneaux ne se chevaussent pas.
 *
 * $reps : tableau des débuts de réservation
 * $diff : durée d'une réservation
 */
function grrCheckOverlap($reps, $diff)
{
	$err = "";
	$total = count($reps);
	for($i = 1; $i < $total; $i++)
	{
		if ($reps[$i] < ($reps[0] + $diff))
			$err = "yes";
	}
	if ($err == "")
		return TRUE;
	else
		return FALSE;
}
/** grrDelEntryInConflict()
 *
 *  Efface les réservation qui sont en partie ou totalement dans le créneau $starttime<->$endtime
 *
 * $room_id   - Which room are we checking
 * $starttime - The start of period
 * $endtime   - The end of the period
 * $ignore    - An entry ID to ignore, 0 to ignore no entries
 * $repignore - A repeat ID to ignore everything in the series, 0 to ignore no series
 *
 * Returns:
 *   nothing   - The area is free
 *   something - An error occured, the return value is human readable
 *   if $flag = 1, return the number of erased entries.
 */
function grrDelEntryInConflict($room_id, $starttime, $endtime, $ignore, $repignore, $flag)
{
	global $vocab, $dformat;
	//Select any meetings which overlap ($starttime,$endtime) for this room:
	$sql = "SELECT id FROM ".TABLE_PREFIX."_entry WHERE start_time < '".$endtime."' AND end_time > '".$starttime."' AND room_id = '".$room_id."' AND supprimer = 0";
	if ($ignore > 0)
		$sql .= " AND id <> $ignore";
	if ($repignore > 0)
		$sql .= " AND repeat_id <> $repignore";
	$sql .= " ORDER BY start_time";
	$res = grr_sql_query($sql);
	if (!$res)
		return grr_sql_error();
	if (grr_sql_count($res) == 0)
	{
		grr_sql_free($res);
		return "";
	}
	//Efface les résas concernées
	for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
	{
		if (Settings::get("automatic_mail") == 'yes')
			$_SESSION['session_message_error'] = send_mail($row[0],3,$dformat);
		$result = mrbsDelEntry(getUserName(), $row[0], NULL , 1);
	}
	if ($flag == 1)
		return $result;
}
/** mrbsDelEntry()
 *
 * Delete an entry, or optionally all entrys.
 *
 * $user   - Who's making the request
 * $id     - The entry to delete
 * $series - If set, delete the series, except user modified entrys
 * $all    - If set, include user modified entrys in the series delete
 *
 * Returns:
 *   0        - An error occurred
 *   non-zero - The entry was deleted
 * @param integer $all
 */
function mrbsDelEntry($user, $id, $series, $all)
{
	global $correct_diff_time_local_serveur, $enable_periods;
	$date_now = time();
	$id_room = grr_sql_query1("SELECT room_id FROM ".TABLE_PREFIX."_entry WHERE id='".$id."'");
	$repeat_id = grr_sql_query1("SELECT repeat_id FROM ".TABLE_PREFIX."_entry WHERE id='".$id."'");
	if ($repeat_id < 0)
		return 0;
	$sql = "SELECT beneficiaire, id, entry_type FROM ".TABLE_PREFIX."_entry WHERE ";
	if (($series) and ($repeat_id > 0))
		$sql .= "repeat_id='".protect_data_sql($repeat_id)."'";
	else
		$sql .= "id='".$id."'";
	$res = grr_sql_query($sql);
	$removed = 0;
	for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
	{
		if (!getWritable($row[0], $user, $id))
			continue;
		if (!verif_booking_date($user, $row[1], $id_room, "", $date_now, $enable_periods, ""))
			continue;
		if ($series && $row[2] == 2 && !$all)
			continue;
		if (grr_sql_command("UPDATE ".TABLE_PREFIX."_entry SET supprimer = 1 WHERE id=" . $row[1]) > 0)
		{
			$removed++;
			insertLogResa($row[1], 5, "");
		}
		grr_sql_command("DELETE FROM ".TABLE_PREFIX."_entry_moderate WHERE id=" . $row[1]);
		grr_sql_command("DELETE FROM ".TABLE_PREFIX."_participants WHERE idresa=" . $row[1]);
	}
	if ($repeat_id > 0 &&
		grr_sql_query1("SELECT count(*) FROM ".TABLE_PREFIX."_entry WHERE repeat_id='".protect_data_sql($repeat_id)."'") == 0)
		grr_sql_command("DELETE FROM ".TABLE_PREFIX."_repeat WHERE id='".$repeat_id."'");
	return $removed > 0;
}
/**
*	mrbsGetAreaIdFromRoomId($room_id)
*/
function mrbsGetAreaIdFromRoomId($room_id)
{
		// Avec la room_id on récupère l'area_id
	$sqlstring = "SELECT area_id FROM ".TABLE_PREFIX."_room WHERE id=$room_id";
	$result = grr_sql_query($sqlstring);
	if (!$result)
		fatal_error(1, grr_sql_error());
	if (grr_sql_count($result) != 1)
		fatal_error(1, get_vocab('roomid') . $id_entry . get_vocab('not_found'));
	$area_id_row = grr_sql_row($result, 0);
	grr_sql_free($result);
	return $area_id_row[0];
}
/** mrbsOverloadGetFieldslist()
 *
 * Return an array with all fields name
 * $id_area : Id of the area
 * $room_id : Id of the room
 *
 */
function mrbsOverloadGetFieldslist($id_area="", $room_id = 0)
{
	if ($room_id > 0 )
	{
		// il faut rechercher le id_area en fonction du room_id
		$id_area = grr_sql_query1("SELECT area_id FROM ".TABLE_PREFIX."_room WHERE id='".$room_id."'");
		if ($id_area == -1)
		{
			fatal_error(1, get_vocab('error_room') . $room_id . get_vocab('not_found'));
			$id_area = "";
		}
	}
	// si l'id de l'area n'est pas précisé, on cherche tous les champs additionnels
	if ($id_area == "")
		$sqlstring = "SELECT fieldname ,fieldtype, ".TABLE_PREFIX."_overload.id, fieldlist, ".TABLE_PREFIX."_area.area_name, affichage, overload_mail, ".TABLE_PREFIX."_overload.obligatoire, ".TABLE_PREFIX."_overload.confidentiel FROM ".TABLE_PREFIX."_overload, ".TABLE_PREFIX."_area WHERE(".TABLE_PREFIX."_overload.id_area = ".TABLE_PREFIX."_area.id) ORDER BY fieldname,fieldtype ";
	else
		$sqlstring = "SELECT fieldname,fieldtype, id, fieldlist, affichage, overload_mail, obligatoire, confidentiel FROM ".TABLE_PREFIX."_overload WHERE id_area='".$id_area."' ORDER BY fieldname,fieldtype";
	$result = grr_sql_query($sqlstring);
	$fieldslist = array();
	if (!$result)
		fatal_error(1, grr_sql_error());
	if (grr_sql_count($result) <0)
		fatal_error(1, get_vocab('error_area') . $id_area . get_vocab('not_found'));
	for ($i = 0; ($field_row = grr_sql_row($result, $i)); $i++)
	{
		if ($id_area == "")
		{
			$fieldslist[$field_row[0]." (".$field_row[4].")"]["name"] = $field_row[0];																 
			$fieldslist[$field_row[0]." (".$field_row[4].")"]["type"] = $field_row[1];
			$fieldslist[$field_row[0]." (".$field_row[4].")"]["id"] = $field_row[2];
			if (trim($field_row[3]) != "")
			{
				$tab_list = explode("|", $field_row[3]);
				foreach ($tab_list as $value)
				{
					if (trim($value) != "")
						$fieldslist[$field_row[0]." (".$field_row[4].")"]["list"][] = trim($value);
				}
			}
			$fieldslist[$field_row[0]." (".$field_row[4].")"]["affichage"] = $field_row[5];
			$fieldslist[$field_row[0]." (".$field_row[4].")"]["overload_mail"] = $field_row[6];
			$fieldslist[$field_row[0]." (".$field_row[4].")"]["obligatoire"] = $field_row[7];
			$fieldslist[$field_row[0]." (".$field_row[4].")"]["confidentiel"] = $field_row[8];
		}
		else
		{
			$fieldslist[$field_row[0]]["name"] = $field_row[0];
			$fieldslist[$field_row[0]]["type"] = $field_row[1];
			$fieldslist[$field_row[0]]["id"] = $field_row[2];
			$fieldslist[$field_row[0]]["affichage"] = $field_row[4];
			$fieldslist[$field_row[0]]["overload_mail"] = $field_row[5];
			$fieldslist[$field_row[0]]["obligatoire"] = $field_row[6];
			$fieldslist[$field_row[0]]["confidentiel"] = $field_row[7];
			if (trim($field_row[3]) != "")
			{
				$tab_list = explode("|", $field_row[3]);
				foreach ($tab_list as $value)
				{
					if (trim($value) != "")
						$fieldslist[$field_row[0]]["list"][] = trim($value);
				}
			}
		}
	}
	return $fieldslist;
}
/** mrbsEntryGetOverloadDesc()
 *
 * Return an array with all additionnal fields
 * $id - Id of the entry
 *
 */
function mrbsEntryGetOverloadDesc($id_entry)
{
	$room_id = 0;
	$overload_array = array();
	$overload_desc = "";
	//On récupère les données overload desc dans ".TABLE_PREFIX."_entry.
	if ($id_entry != NULL)
	{
		$overload_array = array();
		$sqlstring = "SELECT overload_desc,room_id FROM ".TABLE_PREFIX."_entry WHERE id=".$id_entry.";";
		$result = grr_sql_query($sqlstring);
		if (!$result)
			fatal_error(1, grr_sql_error());
		if (grr_sql_count($result) != 1)
			fatal_error(1, get_vocab('entryid') . $id_entry . get_vocab('not_found'));
		$overload_desc_row = grr_sql_row($result, 0);
		grr_sql_free($result);
		$overload_desc = $overload_desc_row[0];
		$room_id = $overload_desc_row[1];
	}
	if ( $room_id >0 )
	{
		$area_id = mrbsGetAreaIdFromRoomId($room_id);
		// Avec l'id_area on récupère la liste des champs additionnels dans ".TABLE_PREFIX."_overload.
		$fieldslist = mrbsOverloadGetFieldslist($area_id);
		foreach ($fieldslist as $field=>$fieldtype)
		{
			//$begin_string = "<".$fieldslist[$field]["id"].">";
			//$end_string = "</".$fieldslist[$field]["id"].">";
			$begin_string = "@".$fieldslist[$field]["id"]."@";
			$end_string = "@/".$fieldslist[$field]["id"]."@";
			$l1 = strlen($begin_string);
			$l2 = strlen($end_string);
			$chaine = $overload_desc;
			$balise_fermante = 'n';
			$balise_ouvrante = 'n';
			$traitement1 = true;
			$traitement2 = true;
			while (($traitement1 !== false) || ($traitement2 !== false))
			{
				// le premier traitement cherche la prochaine occurence de $begin_string et retourne la portion de chaine après cette occurence
				if ($traitement1 != false)
				{
					$chaine1 = strstr ($chaine, $begin_string);
					// retourne la sous-chaîne de $chaine, allant de la première occurrence de $begin_string jusqu'à la fin de la chaîne.
					if ($chaine1 !== false)
					{
						// on a trouvé une occurence de $begin_string
						$balise_ouvrante = 'y';
						// on sait qu'il y a au moins une balise ouvrante
						$chaine = substr($chaine1, $l1, strlen($chaine1)- $l1);
						// on retourne la chaine en ayant éliminé le début de chaine correspondant à $begin_string
						$result = $chaine;
						// On mémorise la valeur précédente
					}
					else
						$traitement1 = false;
				}
				//le 2ème traitement cherche la dernière occurence de $end_string en partant de la fin et retourne la portion de chaine avant cette occurence
				if ($traitement2 != false)
				{
					//La boucle suivante a pour effet de déterminer la dernière occurence de $end_string
					$ind = 0;
					$end_pos = true;
					while ($end_pos !== false)
					{
						$end_pos = strpos($chaine,$end_string,$ind);
						if ($end_pos !== false)
						{
							$balise_fermante='y';
							$ind_old = $end_pos;
							$ind = $end_pos + $l2;
						}
						else
							break;
					}
					//a ce niveau, $ind_old est la dernière occurence de $end_string trouvée dans $chaine
					if ($ind != 0 )
					{
						$chaine = substr($chaine,0,$ind_old);
						$result = $chaine;
					}
					else
						$traitement2=false;
				}
			}
			// while
			if (($balise_fermante == 'n' ) || ($balise_ouvrante == 'n'))
				$overload_array[$field]["valeur"]='';
			else
				$overload_array[$field]["valeur"]=urldecode($result);
			$overload_array[$field]["id"] = $fieldslist[$field]["id"];
			$overload_array[$field]["affichage"] = grr_sql_query1("SELECT affichage FROM ".TABLE_PREFIX."_overload WHERE id = '".$fieldslist[$field]["id"]."'");
			$overload_array[$field]["overload_mail"] = grr_sql_query1("SELECT overload_mail FROM ".TABLE_PREFIX."_overload WHERE id = '".$fieldslist[$field]["id"]."'");
			$overload_array[$field]["obligatoire"] = grr_sql_query1("SELECT obligatoire FROM ".TABLE_PREFIX."_overload WHERE id = '".$fieldslist[$field]["id"]."'");
			$overload_array[$field]["confidentiel"] = grr_sql_query1("SELECT confidentiel FROM ".TABLE_PREFIX."_overload WHERE id = '".$fieldslist[$field]["id"]."'");
		}
		return $overload_array;
	}
	return $overload_array;
}
/** grrExtractValueFromOverloadDesc()
*
* Extrait la chaine correspondante au champ id de la chaine $chaine
*
*/
function grrExtractValueFromOverloadDesc($chaine,$id)
{
	$begin_string = "@".$id."@";
	$end_string = "@/".$id."@";
	$begin_pos = strpos($chaine,$begin_string);
	$end_pos = strpos($chaine,$end_string);
	if ( $begin_pos !== false && $end_pos !== false)
	{
		$first = $begin_pos + strlen($begin_string);
		$data = substr($chaine,$first,$end_pos-$first);
		//$data = base64_decode($data);
		$data = urldecode($data);
	}
	else
		$data = "";
	return $data;
}
/** mrbsCreateSingleEntry()
 *
 * Create a single (non-repeating) entry in the database
 *
 * $starttime   - Start time of entry
 * $endtime     - End time of entry
 * $entry_type  - Entry type
 * $repeat_id   - Repeat ID
 * $room_id     - Room ID
 * $beneficiaire       - beneficiaire
 * $beneficiaire_ext - bénéficiaire extérieur
 * $name        - Name
 * $type        - Type (Internal/External)
 * $description - Description
 *$rep_jour_c - Le jour cycle d'une réservation, si aucun 0
 *
 * Returns:
 *   0        - An error occured while inserting the entry
 *   non-zero - The entry's ID
 * @param integer $entry_type
 * @param integer $repeat_id
 * @param string $statut_entry
 * @param integer $keys
 */
function mrbsCreateSingleEntry($id, $starttime, $endtime, $entry_type, $repeat_id, $room_id, $creator, $beneficiaire, $beneficiaire_ext, $name, $type, $description, $option_reservation,$overload_data, $moderate, $rep_jour_c, $statut_entry, $keys, $courrier, $nbparticipantmax)
{
	$overload_data_string = "";
	$overload_fields_list = mrbsOverloadGetFieldslist(0,$room_id);
	foreach ($overload_fields_list as $field=>$fieldtype)
	{
		$id_field = $overload_fields_list[$field]["id"];

		if($overload_data != '')
			if (array_key_exists($id_field,$overload_data))
			{
				$begin_string = "@".$id_field."@";
				$end_string = "@/".$id_field."@";
				$overload_data_string .= $begin_string.urlencode($overload_data[$id_field]).$end_string;
			}
	}
	//Hugo - Commande sql insérant la nouvelle réservation dans la base de données
	if($id == 0 || $id == NULL)
		$sql = "INSERT INTO ".TABLE_PREFIX."_entry (start_time, end_time, entry_type, repeat_id, room_id, create_by, beneficiaire, beneficiaire_ext, name, type, description, statut_entry, option_reservation,overload_desc, moderate, jours, clef, courrier, nbparticipantmax) VALUES ($starttime, $endtime, '".protect_data_sql($entry_type)."', $repeat_id, $room_id, '".protect_data_sql($creator)."', '".protect_data_sql($beneficiaire)."', '".protect_data_sql($beneficiaire_ext)."', '".protect_data_sql($name)."', '".protect_data_sql($type)."', '".protect_data_sql($description)."', '".protect_data_sql($statut_entry)."', '".$option_reservation."','".protect_data_sql($overload_data_string)."', ".$moderate.",".$rep_jour_c.", $keys, $courrier, '".protect_data_sql($nbparticipantmax)."' )";
	else
		$sql = "UPDATE ".TABLE_PREFIX."_entry SET start_time = ".$starttime.", end_time = ".$endtime.", entry_type = '".protect_data_sql($entry_type)."', repeat_id = ".$repeat_id.", room_id = ".$room_id.", create_by = '".protect_data_sql($creator)."', beneficiaire = '".protect_data_sql($beneficiaire)."', beneficiaire_ext = '".protect_data_sql($beneficiaire_ext)."', name = '".protect_data_sql($name)."', type = '".protect_data_sql($type)."', description = '".protect_data_sql($description)."', statut_entry = '".protect_data_sql($statut_entry)."', option_reservation = '".$option_reservation."' ,overload_desc = '".protect_data_sql($overload_data_string)."', moderate = ".$moderate.", jours = ".$rep_jour_c.", clef = ".$keys.", courrier = ".$courrier.", nbparticipantmax = '".protect_data_sql($nbparticipantmax)."' WHERE id = ".$id."";

	if (grr_sql_command($sql) < 0)
		fatal_error(0, "Requete error  = ".$sql);
	// s'il s'agit d'une modification d'une ressource déjà modérée et acceptée : on met à jour les infos dans la table ".TABLE_PREFIX."_entry_moderate
	if($id == 0 || $id == NULL)
		$id = grr_sql_insert_id();
	if ($moderate == 2)
		moderate_entry_do($id, 1, "", "no");
}
/** mrbsCreateRepeatEntry()
 *
 * Creates a repeat entry in the data base
 *
 * $starttime   - Start time of entry
 * $endtime     - End time of entry
 * $rep_type    - The repeat type
 * $rep_enddate - When the repeating ends
 * $rep_opt     - Any options associated with the entry
 * $room_id     - Room ID
 * $beneficiaire       - beneficiaire
 * $beneficiaire_ext   - beneficiaire extérieur
 * $creator     - celui aui a créé ou modifié la réservation.
 * $name        - Name
 * $type        - Type (Internal/External)
 * $description - Description
 * $rep_jour_c - Le jour cycle d'une réservation, si aucun 0
 *
 * Returns:
 *   0        - An error occured while inserting the entry
 *   non-zero - The entry's ID
 */
function mrbsCreateRepeatEntry($starttime, $endtime, $rep_type, $rep_enddate, $rep_opt, $room_id, $creator, $beneficiaire, $beneficiaire_ext, $name, $type, $description, $rep_num_weeks,$overload_data, $rep_jour_c, $courrier, $nbparticipantmax)
{
	$overload_data_string = "";
	$area_id = mrbsGetAreaIdFromRoomId($room_id);
	$overload_fields_list = mrbsOverloadGetFieldslist($area_id);
	foreach ($overload_fields_list as $field=>$fieldtype)
	{
		$id_field = $overload_fields_list[$field]["id"];
		if (array_key_exists($id_field,$overload_data))
		{
			//$begin_string = "<".$id_field.">";
			//$end_string = "</".$id_field.">";
			$begin_string = "@".$id_field."@";
			$end_string = "@/".$id_field."@";
			//$overload_data_string .= $begin_string.base64_encode($overload_data[$id_field]).$end_string;
			$overload_data_string .= $begin_string.urlencode($overload_data[$id_field]).$end_string;
		}
	}
	$sql = "INSERT INTO ".TABLE_PREFIX."_repeat (start_time, end_time, rep_type, end_date, rep_opt, room_id, create_by, beneficiaire, beneficiaire_ext, type, name, description, rep_num_weeks, overload_desc, jours, courrier, nbparticipantmax) VALUES ($starttime, $endtime,  $rep_type, $rep_enddate, '$rep_opt', $room_id,   '".protect_data_sql($creator)."','".protect_data_sql($beneficiaire)."','".protect_data_sql($beneficiaire_ext)."', '".protect_data_sql($type)."', '".protect_data_sql($name)."', '".protect_data_sql($description)."', '$rep_num_weeks','".protect_data_sql($overload_data_string)."',".$rep_jour_c." , ".$courrier.", '".protect_data_sql($nbparticipantmax)."')";
	if (grr_sql_command($sql) < 0)
		return 0;
	return grr_sql_insert_id();
}

function compareEntrys($id, $starttime, $endtime, $entry_type, $repeat_id, $room_id, $creator, $beneficiaire, $beneficiaire_ext, $name, $type, $description, $option_reservation,$overload_data, $moderate, $rep_jour_c, $statut_entry, $keys, $courrier, $nbparticipantmax)
{
	$differences = "";
	$sql = "SELECT name, beneficiaire, description, start_time, end_time, type, room_id, entry_type, repeat_id, option_reservation, jours, create_by, beneficiaire_ext, statut_entry, clef, courrier, nbparticipantmax FROM ".TABLE_PREFIX."_entry WHERE id=$id";
	$res = grr_sql_query($sql);
	if (!$res)
		fatal_error(1, grr_sql_error());
	if (grr_sql_count($res) != 1)
		fatal_error(1, get_vocab('entryid') . $id . get_vocab('not_found'));
	$row = grr_sql_row($res, 0);
	grr_sql_free($res);

	if($starttime <> $row[3])
		$differences .= "Heure début: ".date("d-m-Y H:i:s", $row[3])." => ".date("d-m-Y H:i:s", $starttime)."<br>";
	
	if($endtime <> $row[4])
		$differences .= "Heure fin: ".date("d-m-Y H:i:s", $row[4])." => ".date("d-m-Y H:i:s", $endtime)."<br>";
	
	if($entry_type <> $row[7])
		$differences .= "Type Resa: ".$row[7]." => ".$entry_type."<br>";

	if($room_id <> $row[6])
		$differences .= "Ressource: ".$row[6]." => ".$room_id."<br>";
	
	if($beneficiaire <> $row[1])
		$differences .= "Beneficiaire: ".$row[1]." => ".$beneficiaire."<br>";
	
	if($beneficiaire_ext <> $row[12])
		$differences .= "Beneficiaire Ext: ".$row[12]." => ".$beneficiaire_ext."<br>";

	if($name <> $row[0])
		$differences .= "Nom: ".$row[0]." => ".$name."<br>";

	if($type <> $row[5])
		$differences .= "Type: ".$row[5]." => ".$type."<br>";

	if($description <> $row[2])
		$differences .= "Description: ".$row[2]." => ".$description."<br>";

	if($keys <> $row[14])
		$differences .= "Clé: ".$row[14]." => ".$keys."<br>";

	if($statut_entry <> $row[13])
		$differences .= "Statut: ".$row[13]." => ".$statut_entry."<br>";

	if($courrier <> $row[15])
		$differences .= "Courrier: ".$row[15]." => ".$courrier."<br>";

	if($nbparticipantmax <> $row[16])
		$differences .= "Nb participant max: ".$row[16]." => ".$nbparticipantmax."<br>";

	return $differences;
}
/** same_day_next_month
 *  Return the number of days to step forward for a "monthly repeat,
 *  corresponding day" series - same week number and day of week next month.
 *  This function always returns either 28 or 35.
 *  For dates after the 28th day of a month, the results are undefined.
 * @param integer $time
 */
function same_day_next_month($time)
{
	$days_in_month = date("t", $time);
	$day = date("d", $time);
	$weeknumber = (int)(($day - 1) / 7) + 1;
	if ($day + 7 * (5 - $weeknumber) <= $days_in_month)
		return 35;
	else
		return 28;
}

function get_day_of_month($time, $rep_month_abs1, $rep_month_abs2)
{
	$days = array('monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday');
	$rep = array('first', 'second', 'third', 'fourth', 'five', 'last');
	$time = strtotime('+1 month', $time);
	$str = $rep[$rep_month_abs1].' '.$days[$rep_month_abs2 - 1].' of '.date("F", $time).' '.date("Y", $time);
	return strtotime($str);

}
/** mrbsGetRepeatEntryList
 *
 * Returns a list of the repeating entrys
 *
 * $time     - The start time
 * $enddate  - When the repeat ends
 * $rep_type - What type of repeat is it
 * $rep_opt  - The repeat entrys
 * $max_ittr - After going through this many entrys assume an error has occured
 * *$rep_jour_c - Le jour cycle d'une réservation, si aucun 0
 *
 * Returns:
 *   empty     - The entry does not repeat
 *   an array  - This is a list of start times of each of the repeat entrys
 */
function mrbsGetRepeatEntryList($time, $enddate, $rep_type, $rep_opt, $max_ittr, $rep_num_weeks, $rep_jour_c, $area, $rep_month_abs1, $rep_month_abs2)
{
	$sec   = date("s", $time);
	$min   = date("i", $time);
	$hour  = date("G", $time);
	$day   = date("d", $time);
	$month = date("m", $time);
	$year  = date("Y", $time);
	$entrys = array();
	$entrys_return = array();
	$k = 0;
	for($i = 0; $i < $max_ittr; $i++)
	{
		$time = mktime($hour, $min, $sec, $month, $day, $year);
		if ($time > $enddate)
			break;
		$time2 = mktime(0, 0, 0, $month, $day, $year);
		if (!(est_hors_reservation($time2,$area)))
		{
			$entrys_return[$k] = $time;
			$k++;
		}
		$entrys[$i] = $time;
		switch($rep_type)
		{
			//Daily repeat
			case 1:
			$day += 1;
			break;
			//Weekly repeat
			case 2:
			$j = $cur_day = date("w", $entrys[$i]);
			//Skip over days of the week which are not enabled:
			while ((($j = ($j + 1) % (7 * $rep_num_weeks)) != $cur_day && $j < 7 && !$rep_opt[$j]) or ($j >= 7))
				$day += 1;
			$day += 1;
			break;
			//Monthly repeat
			case 3:
			$month += 1;
			break;
			//Yearly repeat
			case 4:
			$year += 1;
			break;
			//Monthly repeat on same week number and day of week
			case 5:
			$day += same_day_next_month($time);
			break;
			//Si la périodicité est par Jours/Cycle
			case 6:
			$sql = "SELECT * FROM ".TABLE_PREFIX."_calendrier_jours_cycle WHERE DAY >= '".$time2."' AND DAY <= '".$enddate."' AND Jours = '".$rep_jour_c."'";
			$result = mysqli_query($GLOBALS['db_c'], $sql);
			$kk = 0;
			$tableFinale = array();
			while ($table = mysqli_fetch_array($result))
			{
				$day   = date("d", $table['DAY']);
				$month = date("m", $table['DAY']);
				$year  = date("Y", $table['DAY']);
				$tableFinale[$kk] = mktime($hour, $min, $sec, $month, $day, $year);
				$kk++;
			}
			return $tableFinale;
			case 7:
			$your_date = get_day_of_month($time, $rep_month_abs1, $rep_month_abs2);
			$datediff = $your_date - $time;
			$day += floor($datediff / (60 * 60 * 24)) + 1;
			break;
			//Unknown repeat option
			default:
			return;
		}
	}
	return $entrys_return;
}
/** mrbsCreateRepeatingEntrys()
 *
 * Creates a repeat entry in the data base + all the repeating entrys
 *
 * $starttime   - Start time of entry
 * $endtime     - End time of entry
 * $rep_type    - The repeat type
 * $rep_enddate - When the repeating ends
 * $rep_opt     - Any options associated with the entry
 * $room_id     - Room ID
 * $beneficiaire       - beneficiaire
 * $beneficiaire_ext - bénéficiaire extérieur
 * $name        - Name
 * $type        - Type (Internal/External)
 * $description - Description
 * $rep_jour_c - Le jour cycle d'une réservation, si aucun 0
 *
 * Returns:
 *   0        - An error occured while inserting the entry
 *   non-zero - The entry's ID
 */
function mrbsCreateRepeatingEntrys($starttime, $endtime, $rep_type, $rep_enddate, $rep_opt, $room_id, $creator, $beneficiaire, $beneficiaire_ext, $name, $type, $description, $rep_num_weeks, $option_reservation,$overload_data, $moderate, $rep_jour_c, $courrier, $nbparticipantmax, $rep_month_abs1, $rep_month_abs2)
{
	global $max_rep_entrys, $id_first_resa;
	$area = mrbsGetRoomArea($room_id);
	$reps = mrbsGetRepeatEntryList($starttime, $rep_enddate, $rep_type, $rep_opt, $max_rep_entrys, $rep_num_weeks, $rep_jour_c, $area, $rep_month_abs1, $rep_month_abs2);
	if (count($reps) > $max_rep_entrys)
		return 0;
	if (empty($reps))
	{
		mrbsCreateSingleEntry(0, $starttime, $endtime, 0, 0, $room_id, $creator, $beneficiaire, $beneficiaire_ext, $name, $type, $description, $option_reservation,$overload_data,$moderate, $rep_jour_c,"-", 0, $courrier, $nbparticipantmax);
		$id_first_resa = grr_sql_insert_id();
		return;
	}
	$ent = mrbsCreateRepeatEntry($starttime, $endtime, $rep_type, $rep_enddate, $rep_opt, $room_id, $creator, $beneficiaire, $beneficiaire_ext, $name, $type, $description, $rep_num_weeks,$overload_data, $rep_jour_c, $courrier, $nbparticipantmax);
	if ($ent)
	{
		$diff = $endtime - $starttime;
		$total_reps = count($reps);
		for($i = 0; $i < $total_reps; $i++)
		{
			mrbsCreateSingleEntry(0, $reps[$i], $reps[$i] + $diff, 1, $ent, $room_id, $creator, $beneficiaire, $beneficiaire_ext, $name, $type, $description, $option_reservation,$overload_data, $moderate, $rep_jour_c,"-", 0, $courrier, $nbparticipantmax);
			$id_new_resa = grr_sql_insert_id();
				// s'il s'agit d'une modification d'une ressource déjà modérée et acceptée : on met à jour les infos dans la table ".TABLE_PREFIX."_entry_moderate
			if ($moderate == 2)
				moderate_entry_do($id_new_resa,1,"","no");
				// On récupère l'id de la première réservation de la série et qui sera utilisé pour l'enoi d'un mail
			if ($i == 0)
				$id_first_resa = $id_new_resa;
		}
	}
	return $ent;
}
/* mrbsGetEntryInfo()
 *
 * Get the booking's entrys
 *
 * @param integer $id : The ID for which to get the info for.
 * @return variant    : nothing = The ID does not exist
 *    array   = The bookings info
 */
function mrbsGetEntryInfo($id)
{
	$sql = "SELECT start_time, end_time, entry_type, repeat_id, room_id,
	timestamp, beneficiaire, name, type, description
	FROM ".TABLE_PREFIX."_entry
	WHERE id = '".$id."'";
	$res = grr_sql_query($sql);
	if (!$res)
		return;

	$ret = array();

	if (grr_sql_count($res) > 0)
	{
		$row = grr_sql_row($res, 0);
		$ret["start_time"]  = $row[0];
		$ret["end_time"]    = $row[1];
		$ret["entry_type"]  = $row[2];
		$ret["repeat_id"]   = $row[3];
		$ret["room_id"]     = $row[4];
		$ret["timestamp"]   = $row[5];
		$ret["beneficiaire"]   = $row[6];
		$ret["name"]        = $row[7];
		$ret["type"]        = $row[8];
		$ret["description"] = $row[9];
	}
	grr_sql_free($res);
	return $ret;
}
function mrbsGetRoomArea($id)
{
	$id = grr_sql_query1("SELECT area_id FROM ".TABLE_PREFIX."_room WHERE (id = '".$id."')");
	if ($id <= 0)
		return 0;
	return $id;
}
function mrbsGetAreaSite($id)
{
	if (Settings::get("module_multisite") == "Oui")
	{
		$id = grr_sql_query1("SELECT id_site FROM ".TABLE_PREFIX."_j_site_area WHERE (id_area = '".$id."')");
		return $id;
	}
	else
		return -1;
}
/**
 * @param integer $_id
 * @param integer $_moderate
 * @param string $_description
 */
function moderate_entry_do($_id,$_moderate,$_description,$send_mail="yes")
{
	global $dformat;
	// On vérifie que l'utilisateur a bien le droit d'être ici
	$room_id = grr_sql_query1("SELECT room_id FROM ".TABLE_PREFIX."_entry WHERE id='".$_id."'");
	if (authGetUserLevel(getUserName(),$room_id) < 3)
	{
		fatal_error(0,"Opération interdite");
		exit();
	}
	// j'ai besoin de $repeat_id '
	$sql = "SELECT repeat_id FROM ".TABLE_PREFIX."_entry WHERE id =".$_id;
	$res = grr_sql_query($sql);
	if (!$res)
		fatal_error(0, grr_sql_error());
	$row = grr_sql_row($res, 0);
	$repeat_id = $row['0'];
	// Initialisation
	$series = 0;
	if ($_moderate == "S1")
	{
		$_moderate = "1";
		$series = 1;
	}
	if ($_moderate == "S0")
	{
		$_moderate = "0";
		$series = 1;
	}
	if ($series==0)
	{
		//moderation de la ressource
		if ($_moderate == 1)
		{
			$sql = "UPDATE ".TABLE_PREFIX."_entry SET moderate = 2 WHERE id = ".$_id;
			insertLogResa($_id, 3, 'Réservation validée');
		}
		else
		{
			$sql = "UPDATE ".TABLE_PREFIX."_entry SET moderate = 3 WHERE id = ".$_id;
			insertLogResa($_id, 3, 'Réservation refusée');
		}
		$res = grr_sql_query($sql);
		if (!$res)
			fatal_error(0, grr_sql_error());
		if (!(grr_add_ligne_moderation($_id,$_SESSION['login'],$_description)))
			fatal_error(0, grr_sql_error());
		$tab_id_moderes = array();
	}
	else
	{
		// cas d'une série
		// on constitue le tableau des id de la périodicité
		$sql = "SELECT id FROM ".TABLE_PREFIX."_entry WHERE repeat_id=".$repeat_id;
		$res = grr_sql_query($sql);
		if (!$res)
			fatal_error(0, grr_sql_error());
		$tab_entry = array();
		for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
			$tab_entry[] = $row['0'];
		$tab_id_moderes = array();
		// Boucle sur les résas
		foreach ($tab_entry as $entry_tom)
		{
			$test = grr_sql_query1("SELECT count(id) FROM ".TABLE_PREFIX."_entry_moderate WHERE id = '".$entry_tom."'");
			// Si il existe déjà une entrée dans ".TABLE_PREFIX."_entry_moderate, cela signifie que la réservation a déjà été modérée.
			// Sinon :
			if ($test == 0)
			{
				//moderation de la ressource
				if ($_moderate == 1)
				{
					$sql = "UPDATE ".TABLE_PREFIX."_entry SET moderate = 2 WHERE id = '".$entry_tom."'";
					insertLogResa($entry_tom, 3, 'Réservation validée');
				}
				else
				{
					$sql = "UPDATE ".TABLE_PREFIX."_entry SET moderate = 3 WHERE id = '".$entry_tom."'";
					insertLogResa($entry_tom, 3, 'Réservation refusée');
				}
				$res = grr_sql_query($sql);
				if (! $res)
					fatal_error(0, grr_sql_error());
				if (!(grr_add_ligne_moderation($entry_tom,$_SESSION['login'],$_description)))
					fatal_error(0, grr_sql_error());
				// Backup : on enregistre les infos dans ".TABLE_PREFIX."_entry_moderate
				// On constitue un tableau des réservations modérées
				$tab_id_moderes[] = $entry_tom;
			}
		}
	}
	// Avant d'effacer la réservation, on procède à la notification par mail, uniquement si la salle n'a pas déjà été modérée.
	if ($send_mail=="yes")
		send_mail($_id,6,$dformat,$tab_id_moderes);
	//moderation de la ressource
	if ($_moderate != 1)
	{
		// on efface l'entrée de la base
		if ($series == 0)
		{
			$sql = "UPDATE ".TABLE_PREFIX."_entry SET supprimer = 1 WHERE id = ".$_id;
			$res = grr_sql_query($sql);
			if (! $res)
				fatal_error(0, grr_sql_error());
			insertLogResa($_id, 5, "");
		}
		else
		{
			// On sélectionne toutes les réservations de la périodicité
			$res = grr_sql_query("SELECT id FROM ".TABLE_PREFIX."_entry WHERE repeat_id='".$repeat_id."'");
			if (! $res)
				fatal_error(0, grr_sql_error());
			for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
			{
				$entry_tom = $row['0'];
				// Pour chaque réservation, on teste si celle-ci a été refusée
				$test = grr_sql_query1("SELECT count(id) FROM ".TABLE_PREFIX."_entry_moderate WHERE id = '".$entry_tom."' AND moderate='3'");
				// Si oui, on supprime la réservation
				if ($test > 0)
				{
					grr_sql_query("UPDATE ".TABLE_PREFIX."_entry SET supprimer = 1 WHERE id = '".$entry_tom."'");
					insertLogResa($entry_tom, 5, "");
				}
			}
			// On supprime l'info de périodicité
			grr_sql_query("DELETE FROM ".TABLE_PREFIX."_repeat WHERE id='".$repeat_id."'");
			grr_sql_query("UPDATE ".TABLE_PREFIX."_entry SET repeat_id = '0' WHERE repeat_id='".$repeat_id."'");
		}
	}
}

function PaticipationAjout($entry_id, $creator, $beneficiaire, $beneficiaire_ext)
{

	$sql = "INSERT INTO ".TABLE_PREFIX."_participants (idresa, cree_par, beneficiaire, beneficiaire_ext) VALUES (".$entry_id.", '".$creator."', '".protect_data_sql($beneficiaire)."', '".protect_data_sql($beneficiaire_ext)."' )";

	if (grr_sql_command($sql) < 0)
		fatal_error(0, "Requete error  = ".$sql);

	insertLogResa($entry_id, 4, 'Participe');

}

function PaticipationAnnulation($entry_id, $beneficiaire)
{
	
	grr_sql_query("DELETE FROM ".TABLE_PREFIX."_participants WHERE idresa=$entry_id AND beneficiaire='$beneficiaire'");

	insertLogResa($entry_id, 4, 'Ne participe plus');
}

?>
