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

// Action : Suppression des logs
if (isset($_POST['cleanlog']))
{
	
	$dateMax = strtotime( $_POST['cleanlog'] );
	$sql = "DELETE FROM ".TABLE_PREFIX."_log_mail WHERE date < '".$dateMax."' and date < now()";
	$res = grr_sql_query($sql);
}

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

// Afficher : Sup logs
get_vocab_admin("cleaning_log");
get_vocab_admin("logs_number");
get_vocab_admin("older_date_log");
get_vocab_admin("erase_log");
get_vocab_admin("delete_up_to");
get_vocab_admin("del");

$sql = "select date from ".TABLE_PREFIX."_log_mail order by date";
$res = grr_sql_query($sql);
if($res) {
	$trad['dNombreLog'] = grr_sql_count($res);
    if ($trad['dNombreLog']>0){
        $row = grr_sql_row($res, 0);
        $trad['dDatePlusAncienne'] = date("d-m-Y", $row[0]);
    }
	else 
        $trad['dDatePlusAncienne'] = "-";
} else{
	$trad['dNombreLog'] = 0;
	$trad['dDatePlusAncienne'] = "-";
}

$trad['dTitreDateLog'] = get_vocab("log_mail").$trad['dDatePlusAncienne'];

echo $twig->render('admin_view_emails.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings, 'logsmail' => $logsMail ));
?>