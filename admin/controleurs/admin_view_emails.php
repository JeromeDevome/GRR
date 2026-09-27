<?php
/**
 * admin_view_emails.php
 * Interface de gestion des connexions
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2026-09-27 16:55$
 * @author    JeromeB & Yan Naessens
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

$grr_script_name = "admin_view_emails.php";

// Accès à la page
SecuAccess::CheckAccess(6, $back);

// les variables attendues et leur type
$form_vars = array(
    'p_idlogmail' => array('int', 0)
);
// récupération des valeurs des variables passées en paramètres
foreach($form_vars as $var => $params)
    $$var = SecuChaine::GetFormVarSecure($var, $params[0], $params[1]);



$logsMail = array ();
$visuMail = array();

/** Actions **/
	if($p_idlogmail > 0) // Voir un log en particulier
	{
		$sql = "SELECT date, de, a, sujet, message, erreur, template, type, idresa FROM ".TABLE_PREFIX."_log_mail WHERE idlogmail = '$p_idlogmail'";
		$res = grr_sql_query($sql);
		if ($res)
		{
			$row = grr_sql_row($res, 0);
			$visuMail = array('datets' => $row[0], 'date' => date("d-m-Y H:i:s", $row[0]), 'de' => $row[1], 'a' => $row[2], 'sujet' => iconv_mime_decode($row[3], ICONV_MIME_DECODE_CONTINUE_ON_ERROR, 'UTF-8'), 'message' => $row[4], 'erreur' => $row[5], 'template' => $row[6], 'type' => $row[7], 'idresa' => $row[8]);
		}
	}


/** Affichage de la page **/
	$sql = "SELECT date, de, a, sujet, message, idlogmail, erreur, idresa FROM ".TABLE_PREFIX."_log_mail ORDER BY date DESC";
	$res = grr_sql_query($sql);

	$logsMail = array ();

	if ($res)
	{
		for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
		{
			$logsMail[] = array('idlogmail' => $row[5], 'datets' => $row[0], 'date' => date("d-m-Y H:i:s", $row[0]), 'de' => $row[1], 'a' => $row[2], 'sujet' => iconv_mime_decode($row[3], ICONV_MIME_DECODE_CONTINUE_ON_ERROR, 'UTF-8'), 'message' => substr($row[4], 0, 50), 'erreur' => $row[6], 'idresa' => $row[7]);
		}
	}


	$sql = "SELECT date FROM ".TABLE_PREFIX."_log_mail ORDER BY date";
	$res = grr_sql_query($sql);
	if($res) {
		$d['NombreLog'] = grr_sql_count($res);
		if ($d['NombreLog']>0){
			$row = grr_sql_row($res, 0);
			$d['DatePlusAncienne'] = date("d-m-Y", $row[0]);
		}
		else 
			$d['DatePlusAncienne'] = "-";
	} else{
		$d['NombreLog'] = 0;
		$d['DatePlusAncienne'] = "-";
	}

	$trad['TitrePage'] = $trad["admin_view_emails"];
	$d["TitreDateLog"] = $trad["log_mail"].$d['DatePlusAncienne'];

	echo $twig->render('admin_view_emails.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings, 'logsmail' => $logsMail, 'visuMail' => $visuMail ));
?>