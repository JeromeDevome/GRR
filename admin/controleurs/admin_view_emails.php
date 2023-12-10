<?php
/**
 * admin_view_emails.php
 * Interface de gestion des connexions
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2022-06-19 15:49$
 * @author    JeromeB & Yan Naessens
 * @copyright Copyright 2003-2022 Team DEVOME - JeromeB
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

check_access(6, $back);

get_vocab_admin('admin_view_emails');

// Afficher : Logs
get_vocab_admin('date2');
get_vocab_admin('mail_de');
get_vocab_admin('mail_a');
get_vocab_admin('mail_sujet');
get_vocab_admin('mail_message');

get_vocab_admin('users_connected');

$sql = "SELECT date, de, a, sujet, message FROM ".TABLE_PREFIX."_log_mail ORDER by date desc";
$res = grr_sql_query($sql);

$logsMail = array ();

if ($res)
{
	for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
	{
		$logsMail[] = array('date' => date("d-m-Y H:i:s", $row[0]), 'de' => $row[1], 'a' => $row[2], 'sujet' => $row[3], 'message' => substr($row[4], 0, 50));
	}
}


$sql = "select date from ".TABLE_PREFIX."_log_mail order by date";
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

$d['TitreDateLog'] = get_vocab("log_mail").$d['DatePlusAncienne'];

echo $twig->render('admin_view_emails.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings, 'logsmail' => $logsMail ));
?>