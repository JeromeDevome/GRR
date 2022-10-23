<?php
/**
 * admin_delete_entry_before.php
 * Interface permettant à l'administrateur de supprimer des réservations avant une date donnée
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2017-12-16 14:00$
 * @author    JeromeB & Yan Naessens & Denis Monasse
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

$grr_script_name = "admin_delete_entry_before.php";

if (!Settings::load()) {
    die('Erreur chargement settings');
}

get_vocab_admin('back');

$trad['dMessageSup'] = "";

if(isset($_POST['delete'])) {
	$endtime=mktime(23, 59, 0, $_POST['end_month'],$_POST['end_day'],$_POST['end_year']);
	$sql="select id, area_name from ".TABLE_PREFIX."_area order by order_display";
	$res = grr_sql_query($sql);
	if (! $res) fatal_error(0, grr_sql_error());
	$nb_areas=grr_sql_count($res);
	for($i=0;$i < $nb_areas;$i++)
		if(isset($_POST["area".$i])){
			$rooms=grr_sql_query("SELECT id, room_name FROM `".TABLE_PREFIX."_room` WHERE `area_id`=".$_POST["area".$i]);
			for ($j = 0; ($row = grr_sql_row($rooms, $j)); $j++) 
				if(grr_sql_query("DELETE FROM `".TABLE_PREFIX."_entry` WHERE `end_time`<=".$endtime." AND `room_id`=".$row[0]))
					$trad['dMessageSup'] .= "Les réservations antérieures au ".$_POST['end_day']."/".$_POST['end_month']."/".$_POST['end_year']." à 23 heures 59 dans la ressource ".$row[1]." ont été supprimées<br/>";
				else 
					$trad['dMessageSup'] .= "Erreur dans la suppression des réservations dans la ressource ".$row[1]."<br/>";
		}
}

// affichage et sélection des domaines concernés par la suppression 
$sql="select id, area_name from ".TABLE_PREFIX."_area order by order_display";
$res = grr_sql_query($sql);
if (! $res) fatal_error(0, grr_sql_error());

if (grr_sql_count($res) != 0) {
	for ($i = 0; ($row = grr_sql_row($res, $i)); $i++) {
		$domaines[] = array('i' => $i, 'id' => $row[0], 'nom' => $row[1]);
	}
}

$day   = date("d");
$month = date("m");
$year  = date("Y"); //par défaut on propose la date du jour
$trad['dDate'] = genDateSelectorForm('end_', $day, $month, $year, 'more_years');


echo $twig->render('admin_delete_entry_before.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings, 'domaines' => $domaines));


?>