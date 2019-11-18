<?php
/**
 * pdfgenerator.php
 * Générer les PDF
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2017-12-17 19:00$
 * @author    Laurent Delineau & JeromeB
 * @copyright Copyright 2003-2018 Team DEVOME - JeromeB
 * @link      http://www.gnu.org/licenses/licenses.html
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 */
include "include/connect.inc.php";
include "include/config.inc.php";
include "include/misc.inc.php";
include "include/functions.inc.php";
include "include/$dbsys.inc.php";
include "include/mincals.inc.php";
include "include/mrbs_sql.inc.php";
$grr_script_name = "pdfgenerator.php";
require_once("./include/settings.class.php");
if (!Settings::load())
	die("Erreur chargement settings");
require_once("./include/session.inc.php");
include "include/resume_session.php";
include "include/language.inc.php";
setlocale(LC_TIME, 'french');

if ("POST" == $_SERVER['REQUEST_METHOD']) 
{
	$logo = $_POST['logo'];
	$etablisement = $_POST['etat'];
	$nom = $_POST['nom'];
	$orga = $_POST['orga'];
	$adresse = $_POST['adresse'];
	$adresse2 = $_POST['adresse2'];
	$ville = $_POST['ville'];
	$cp = $_POST['cp'];
	$id = $_POST['id'];
	$salle = $_POST['salle'];
	$jour = $_POST['jour'];
	$date = $_POST['date'];
	$heure = $_POST['heure'];
	$heure2 = $_POST['heure2'];
	$jour2 = $_POST['jour2'];
	$period = $_POST['period'];
	$finPeriode= $_POST['finPeriode'];
	$jourPeriode = $_POST['jourPeriode'];
	$cle = $_POST['cle'];
	
	if ($period == 0){
		include 'pdf/pdf_ResUnique.php';
	}else{
		include 'pdf/pdf_ResPeriode_Sem.php';
	}
	include 'pdf/printPDF.php';
}
else
{
	if (isset($_GET['id']))
		$id = $_GET['id'];
	else
		header('Location: '.Settings::get("grr_url"));

	$sql = "SELECT * FROM ".TABLE_PREFIX."_entry WHERE id='".intval($id)."'";
	$res = grr_sql_query($sql);
	if (!$res)
		fatal_error(0, grr_sql_error());

	$row = grr_sql_row($res, 0);
	$cle = $row[18];
	$sql = "SELECT room_name FROM ".TABLE_PREFIX."_room WHERE id='".$row[5]."'";
	$res = grr_sql_query($sql);
	$row2 = grr_sql_row($res, 0);

	$res2 = grr_sql_query("SELECT rep_type, end_date, rep_opt, rep_num_weeks, start_time, end_time FROM ".TABLE_PREFIX."_repeat WHERE id=$row[4]");
	if (!$res2)
		fatal_error(0, grr_sql_error());

	if (grr_sql_count($res2) == 1){	
		$row6 = grr_sql_row($res2, 0);
		$rep_type = $row6[0];
		$rep_end_date = utf8_strftime($dformat,$row6[1]);
		$rep_opt = $row6[2];
		$rep_num_weeks = $row6[3];
		$start_time = $row6[4];
		$end_time = $row6[5];
		$duration = $row6[5] - $row6[4];
	}

	if ($row[4]!=0){
		$period = 1;
	}else{
		$period = 0;
	};

	if ($period == 0){
		$rep_end_date = 0;
		$row6[1] = 0;
	}

	include 'pdf/form_infoPDF.html';

}
?>
