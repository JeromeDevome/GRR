<?php
/**
 * mesreservations.php
 * interface de purge des comptes et réservations
 * Dernière modification : $Date: 2020-05-03 15:00$
 * @author    JeromeB & Yan Naessens
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

class MesReservations
{
	public static function mes_resas($login,$span,$dformat){
		global $gListeReservations;

		$sql = "SELECT distinct e.id, e.start_time, e.end_time, e.name, e.description, "
		. "e.type, e.beneficiaire, "
		.  grr_sql_syntax_timestamp_to_unix("e.timestamp")
		. ", a.area_name, r.room_name, r.description, a.id, e.overload_desc, r.order_display, t.type_name"
		. ", e.beneficiaire_ext, e.moderate, e.supprimer"
		. " FROM ".TABLE_PREFIX."_entry e, ".TABLE_PREFIX."_area a, ".TABLE_PREFIX."_room r, ".TABLE_PREFIX."_type_area t"
		. " WHERE e.room_id = r.id AND r.area_id = a.id"
		. " AND e.beneficiaire = '".$login."' ";
		if ($span)
			$sql .= "AND e.end_time >= ".time() ;
		$sql .= " AND  t.type_letter = e.type ";
		$sql .= "ORDER BY e.start_time ASC ";

		$res = grr_sql_query($sql);
		if (!$res)
			fatal_error(0, grr_sql_error());

		$nmatch = grr_sql_count($res);
		$gListeReservations = array();

		if ($nmatch > 0)
		{
			for ($i = 0; ($row = grr_sql_row($res, $i)); $i++){
				get_planning_area_values($row[11]); // détermine le format d'affichage $dformat
				MesReservations::reportone($row, $dformat);
			}
			
		}
		grr_sql_free($res);

		return $nmatch;
	}


	/* met en forme une ligne du tableau des réservations à partir des données SQL et du paramètre $dformat */
	// Report on one entry. See below for columns in $row[].
	public static function reportone(&$row, $dformat)
	{
		global $vocab, $enable_periods, $gListeReservations;

		//Affichage de l'heure et de la durée de réservation
		if ($enable_periods == 'y')
			list($start_date, $start_time ,$duration, $dur_units) =  describe_period_span($row[1], $row[2]);
		else
			list($start_date, $start_time ,$duration, $dur_units) = describe_span($row[1], $row[2], $dformat);

		// Couleur texte (noir antérieur a ce passé, vert à venir et/ou modération accepté, orange en attente de modération, rouge refusé) 
		if($row[8] == 1)
			$couleur = 'orange';
		elseif($row[2] > time())
			$couleur = 'green';
		else
			$couleur = 'black';

		//Affiche "Domaine"
		$domaine = htmlspecialchars($row[8]);
		$domainedesc = htmlspecialchars($row[10]);

		//Affiche "Ressource"
		$ressource = htmlspecialchars($row[9]);


		// Breve description (title), avec un lien
		$descriC = affichage_lien_resa_planning($row[3],$row[0]);
		$descriC = "<a href=\"../view_entry.php?id=$row[0]&amp;mode=page\">". $descriC . "</a>";

		//Description complète
		if ($row[4] != "")
			$descriL = nl2br(htmlspecialchars($row[4]));
		else
			$descriL = " ";

		//Type de réservation
		$type = grr_sql_query1("SELECT type_name FROM ".TABLE_PREFIX."_type_area WHERE type_letter='".$row[5]."'");
		if ($type == -1)
			$type = "?".$row[5]."?";

		//Affichage de la date de la dernière mise à jour
		$dateMAJ = date_time_string($row[7],$dformat);

		$gListeReservations[] = array('couleur' => $couleur, 'datedebut' => $start_date, 'heuredebut' => $start_time, 'duree' => $duration, 'domaine' => $domaine, 'domainedesc' => $domainedesc, 'ressource' => $ressource, 'descriptionc' => $descriC, 'descriptionl' => $descriL, 'type' => $type, 'datemaj' => $dateMAJ, 'supprimer' => $row[17]);

	}

}

?>