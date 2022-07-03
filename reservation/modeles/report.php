<?php
/**
 * report.php
 * interface affichant un rapport des réservations
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2021-09-09
 * @author    JeromeB & Yan Naessens
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


// Report on one entry. See below for columns in $row[].
function reporton(&$row, $dformat)
{
	global $vocab, $enable_periods, $tablOverload, $gListeReservations;

	$domaine = htmlspecialchars($row[8]);
	$domainedesc = htmlspecialchars($row[10]);
	$ressource = htmlspecialchars($row[9]);

	// Breve description (title), avec un lien
	$descriC = affichage_lien_resa_planning($row[3],$row[0]);

	//Affichage de l'heure et de la durée de réservation
	if ($enable_periods == 'y')
		list($start_date, $start_time ,$duration, $dur_units) =  describe_period_span($row[1], $row[2]);
	else
		list($start_date, $start_time ,$duration, $dur_units) = describe_span($row[1], $row[2], $dformat);

	// Durée réservation
	//echo "<td>".$duration ." ". $dur_units ."</td>";
	//Description
	if ($row[4] != "")
		$descriL = nl2br(htmlspecialchars($row[4]));
	else
		$descriL = " ";

	//Type de réservation
	$type = grr_sql_query1("SELECT type_name FROM ".TABLE_PREFIX."_type_area WHERE type_letter='".$row[5]."'");
	if ($type == -1)
		$type = "?".$row[5]."?";

	// Bénéficiaire
	$sql_beneficiaire = "SELECT prenom, nom FROM ".TABLE_PREFIX."_utilisateurs WHERE login = '".$row[6]."'";
	$res_beneficiaire = grr_sql_query($sql_beneficiaire);
	$aff_beneficiaire = " ";
	if ($res_beneficiaire)
	{
		$row_user = grr_sql_row($res_beneficiaire, 0);
        if ($row_user)
            $aff_beneficiaire = htmlspecialchars($row_user[0]) ." ". htmlspecialchars($row_user[1]);
	}
	if ($aff_beneficiaire == " ")
	{
		$benef_ext = explode('|',$row[15]);
		$aff_beneficiaire = htmlspecialchars($benef_ext[0]);
	}
	//Affichage de la date de la dernière mise à jour
	$dateMAJ = date_time_string($row[7],$dformat);

	// X Colonnes champs additionnels
	$overload_data = mrbsEntryGetOverloadDesc($row[0]);

	$nbValeur = count($tablOverload);
	$AddReservation = array();
	$champAddValeur = array();

	foreach ($overload_data as $fieldname=>$fielddata) // Pour chaque champ additionnel de la réservation
	{
		// if ($fielddata["confidentiel"] == 'n') filtrage trop strict
        if ((authGetUserLevel(getUserName(),-1) > 5) || ($fielddata['affichage'] == 'y'))
        {
			$keyTab = array_search($fieldname, $tablOverload);
			$AddReservation[$keyTab] = $fielddata["valeur"];
		}
	}
	$j=1;

	while($j <= $nbValeur){
		if(isset($AddReservation[$j]))
			$champAddValeur[] = array('val' => $AddReservation[$j]);
		else
			$champAddValeur[] = array('val' => "-");
		$j++;
	}
	// Fin champs additionnels

	unset($tablOverload);
	
	$gListeReservations[] = array('idresa' => $row[0], 'datedebut' => $start_date, 'heuredebut' => $start_time, 'duree' => $duration, 'domaine' => $domaine, 'domainedesc' => $domainedesc, 'ressource' => $ressource, 'beneficiaire' => $aff_beneficiaire, 'descriptionc' => $descriC, 'descriptionl' => $descriL, 'type' => $type, 'datemaj' => $dateMAJ, 'supprimer' => $row[16], 'moderate' => $row[17], 'champaddvaleur' => $champAddValeur);

}


// $breve_description est soit une "description brève" soit un "bénéficiaire", selon la valeur de $_GET["sumby"]
// La fonction renvoie :
// $count[$room][$breve_description] : nombre de réservation pour $room et $breve_description donné
// $hours[$room][$breve_description] : nombre de d'heures de réservation pour $room et $breve_description donné
// $room_hash[$room]  : tableau des $room concernés par le décompte
// $breve_description_hash[$breve_description]  : tableau des $breve_description concernés par le décompte
// Cela va devenir la colonne et la ligne d'entête de la table de statistique.'
function accumulate(&$row, &$count, &$hours, $report_start, $report_end, &$room_hash, &$breve_description_hash, $csv = "n")
{
	global $vocab;
	if ($_GET["sumby"] == "5")
		$temp = grr_sql_query1("SELECT type_name FROM ".TABLE_PREFIX."_type_area WHERE type_letter = '".$row[$_GET["sumby"]]."'");
	else if (($_GET["sumby"] == "3") || ($_GET["sumby"] == "6"))
		$temp = $row[$_GET["sumby"]];
	else
		$temp = grrExtractValueFromOverloadDesc($row[12],$_GET["sumby"]);
	if ($temp == "")
		$temp = "(Autres)";
	if ($csv == "n")
	{
		//Description "Créer par":
		// [3]   Descrition brêve,(HTML) -> e.name
		// [4]   Descrition,(HTML) -> e.description
		// [5]   Type -> e.type
		// [6]   réservé par (nom ou IP), (HTML) -> e.beneficiaire
		// [12]  les champs additionnele -> e.overload_desc
		$breve_description = htmlspecialchars($temp);
		//$row[8] : Area , $row[9]:Room
		$room = htmlspecialchars($row[8]) .$vocab["deux_points"]. "<br />" . htmlspecialchars($row[9]);
	}
	else
	{
		$breve_description = ($temp);
	  //   $row[8] : Area , $row[9]:Room
		$room = removeMailUnicode($row[9]) ." (". removeMailUnicode($row[8]).")";
	}
	//Ajoute le nombre de réservations pour cette "room" et nom.
	@$count[$room][$breve_description]++;
	//Ajoute le nombre d'heure ou la ressource est utilisée.
	@$hours[$room][$breve_description] += (min((int)$row[2], $report_end)
		- max((int)$row[1], $report_start)) / 3600.0;
	$room_hash[$room] = 1;
	$breve_description_hash[$breve_description] = 1;
}


// Identique à la fonction accumulate mais adapté aux cas ou $enable_periode = 'y'
function accumulate_periods(&$row, &$count, &$hours, $report_start, $report_end, &$room_hash, &$breve_description_hash, $csv = "n")
{
	global $vocab, $periods_name;
	$max_periods = count($periods_name);
	if ($_GET["sumby"] == "5")
		$temp = grr_sql_query1("SELECT type_name FROM ".TABLE_PREFIX."_type_area WHERE type_letter = '".$row[$_GET["sumby"]]."'");
	else if (($_GET["sumby"] == "3") or ($_GET["sumby"] == "6"))
		$temp = $row[$_GET["sumby"]];
	else
		$temp = grrExtractValueFromOverloadDesc($row[12],$_GET["sumby"]);
	if ($temp == "")
		$temp = "(Autres)";
	if ($csv == "n")
	{
		$breve_description = htmlspecialchars($temp);
		// Area and room separated by break:
		$room = htmlspecialchars($row[8]) .$vocab["deux_points"]. "<br />" . htmlspecialchars($row[9]);
	}
	else
	{
		// Use brief description or created by as the name:
		$breve_description = ($temp);
		// Area and room separated by break:
		$room = ($row[9]) . " " . ($row[10]);
	}
	// Accumulate the number of bookings for this room and name:
	@$count[$room][$breve_description]++;
	// Accumulate hours used, clipped to report range dates:
	$dur = (min((int)$row[2], $report_end) - max((int)$row[1], $report_start))/60;
	if ($dur < (24*60))
		@$hours[$room][$breve_description] += $dur;
	else
		@$hours[$room][$breve_description] += ($dur % $max_periods) + floor( $dur/(24*60) ) * $max_periods;
	$room_hash[$room] = 1;
	$breve_description_hash[$breve_description] = 1;
}


//Table contenant un compteur (int) et une heure (float):
function cell($count, $hours, $csv = "n", $decompte = "heure")
{
	$val = "";
	if ($csv == "n")
		$val = "<td>($count) ". sprintf("%.2f", $hours) . "</td>";
	elseif (($csv == "y") && ($decompte == "heure"))
		$val = sprintf("%.2f", $hours) . ";";
	elseif (($csv == "y") && ($decompte == "resa"))
		$val = "$count;";
	
	return $val;
}


// Output the summary table (a "cross-tab report"). $count and $hours are
// 2-dimensional sparse arrays indexed by [area/room][name].
// $room_hash & $breve_description_hash are arrays with indexes naming unique rooms and names.
function do_summary(&$count, &$hours, &$room_hash, &$breve_description_hash, $enable_periods, $decompte, $csv = "n")
{
	global $vocab, $gListeResume;

    $rooms = array_keys($room_hash);    
	ksort($rooms);
    $breve_descriptions = array_keys($breve_description_hash);
	ksort($breve_descriptions);
	$n_rooms = sizeof($rooms);
	$n_names = sizeof($breve_descriptions);

	if ($_GET["sumby"] == "6")
		$premiere_cellule = get_vocab("sum_by_creator");
	else if ($_GET["sumby"] == "3")
		$premiere_cellule = get_vocab("sum_by_descrip");
	else if ($_GET["sumby"] == "5")
		$premiere_cellule = get_vocab("type");
	else
		$premiere_cellule = grr_sql_query1("SELECT fieldname FROM ".TABLE_PREFIX."_overload WHERE id='".$_GET["sumby"]."'");

	$ligne1 = "<th class=\"sorting\" tabindex=\"0\" aria-controls=\"resume\" rowspan=\"1\" colspan=\"1\">".$premiere_cellule." \ ".get_vocab("room")."</th>";
	$ligneZ = "<th rowspan=\"1\" colspan=\"1\">".$premiere_cellule." \ ".get_vocab("room")."</th>";

	$col_count_total = array();
	$col_hours_total = array();
	for ($c = 0; $c < $n_rooms; $c++)
	{
		$ligne1 .= "<th class=\"sorting\" tabindex=\"0\" aria-controls=\"resume\" rowspan=\"1\" colspan=\"1\">$rooms[$c]</th>";
		$ligneZ .= "<th rowspan=\"1\" colspan=\"1\">$rooms[$c]</th>";
		$col_count_total[$c] = 0;
		$col_hours_total[$c] = 0.0;
	}

	$ligne1 .=  "<th class=\"sorting\" tabindex=\"0\" aria-controls=\"resume\" rowspan=\"1\" colspan=\"1\">".get_vocab("total")."</th>";
	$ligneZ .=  "<th rowspan=\"1\" colspan=\"1\">".get_vocab("total")."</th>";

	$gListeResume[] = array('ligne' => $ligne1);
	$grand_count_total = 0;
	$grand_hours_total = 0;
	$gListeLigneX = array();
	for ($r = 0; $r < $n_names; $r++)
	{
		$row_count_total = 0;
		$row_hours_total = 0.0;
		$breve_description = $breve_descriptions[$r];

		$ligneX =  "<th>$breve_description</th>";

		for ($c = 0; $c < $n_rooms; $c++)
		{
			$room = $rooms[$c];
			if (isset($count[$room][$breve_description]))
			{
				$count_val = $count[$room][$breve_description];
				$hours_val = $hours[$room][$breve_description];
				$ligneX .= cell($count_val, $hours_val, $csv,$decompte);
				
				$row_count_total += $count_val;
				$row_hours_total += $hours_val;
				$col_count_total[$c] += $count_val;
				$col_hours_total[$c] += $hours_val;
			}
			else
			{
				$ligneX .= "<td> </td>";
			}
		}
		$ligneX .= cell($row_count_total, $row_hours_total, $csv,$decompte);
		$gListeLigneX[] = array('ligne' => $ligneX);
		$grand_count_total += $row_count_total;
		$grand_hours_total += $row_hours_total;
	}

	$ligneY = "<td>".get_vocab("total")."</td>";

	for ($c = 0; $c < $n_rooms; $c++)
		$ligneY .= cell($col_count_total[$c], $col_hours_total[$c], $csv,$decompte);
	$ligneY .= "<td>($grand_count_total) ". sprintf("%.2f", $grand_hours_total) . "</td>";


	$gListeResume['premLigne'] = $ligne1;
	$gListeResume['xLignes'] = $gListeLigneX;
	$gListeResume['dernLigne'] = $ligneY;
	$gListeResume['footLigne'] = $ligneZ;
}


?>