<?php
/**
 * pdfgenerator.php
 * Générer les PDF
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2021-05-22 17:30$
 * @author    Laurent Delineau & JeromeB
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

$grr_script_name = "pdfgenerator.php";

if ("POST" == $_SERVER['REQUEST_METHOD']) 
{
	$orga = $_POST['orga'];
	$id = intval($_POST['id']);
	$d = $_POST;

	// Création PDF
	require_once('vendor/tecnickcom/tcpdf/tcpdf.php');
	$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
	$pdf->SetCreator(PDF_CREATOR);
	$pdf->SetTitle("Reservation ".$id);
	$pdf->setPrintHeader(false);
	$pdf->setPrintFooter(false);
	$pdf->SetMargins(PDF_MARGIN_LEFT, 1, PDF_MARGIN_RIGHT);
	$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
	$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
	$pdf->AddPage();

	$html = $twig->render('pdfconfirmation.twig', array('trad' => $trad, 'd' => $d, 'settings' => $AllSettings));

	$pdf->writeHTML($html, true, false, true, false, '');
	$pdf->lastPage();
	$pdf->Output("reservation".$id.".pdf", 'I');
}
else
{
	if (isset($_GET['id']))
	{
		$id = intval($_GET['id']);
	
		$sql = "SELECT * FROM ".TABLE_PREFIX."_entry WHERE id='".$id."'";
		$res = grr_sql_query($sql);
		if (!$res)
			fatal_error(0, grr_sql_error());

		$d['id'] = $id;
		$row = grr_sql_row($res, 0);
		$d['cle'] = $row[18];
		$sql = "SELECT room_name FROM ".TABLE_PREFIX."_room WHERE id='".$row[5]."'";
		$res = grr_sql_query($sql);
		$row2 = grr_sql_row($res, 0);

		$res2 = grr_sql_query("SELECT rep_type, end_date, rep_opt, rep_num_weeks, start_time, end_time FROM ".TABLE_PREFIX."_repeat WHERE id=$row[4]");
		if (!$res2)
			fatal_error(0, grr_sql_error());

		if (grr_sql_count($res2) == 1){	
			$row6 = grr_sql_row($res2, 0);
			//$rep_type = $row6[0];
			$d['rep_end_date'] = utf8_strftime($dformat,$row6[1]);
			//$rep_opt = $row6[2];
			//$rep_num_weeks = $row6[3];
			//$start_time = $row6[4];
			//$end_time = $row6[5];
			//$duration = $row6[5] - $row6[4];
		}

		$d['dossierImg'] = $gcDossierImg;
		$d['organisme'] = $row[10];
		$d['salle'] = $row2[0];
		$d['datedemande'] = utf8_encode(strftime('%A %d %B %Y' ,strtotime($row[6])));
		$d['jour'] = utf8_encode(strftime('%A %d %B %Y' ,$row[1]));
		$d['heure'] = strftime('%H:%M' ,$row[1]);
		$d['heure2'] = strftime('%H:%M' ,$row[2]);
		$d['jour2'] = utf8_encode(strftime('%A %d %B %Y' ,$row[2]));

		if ($row[4]!=0){
			$d['period'] = 1;
			$d['jourPeriode'] = utf8_encode(strftime('%A' ,$row6[1]));
			
		}else{
			$d['period'] = 0;
			$d['rep_end_date'] = 0;
			$d['jourPeriode'] = 0;
		}

		echo $twig->render('pdfgenerator.twig', array('trad' => $trad, 'd' => $d, 'settings' => $AllSettings));

	} else
		header('Location: '.Settings::get("grr_url"));

}
?>