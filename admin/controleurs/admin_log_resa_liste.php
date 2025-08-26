<?php
/**
 * admin_log_resa_liste.php
 * Interface de gestion des connexions
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2023-12-03 17:48$
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

$grr_script_name = "admin_log_resa_liste.php";

check_access(6, $back);

get_vocab_admin('admin_view_emails');

// Afficher : Logs
get_vocab_admin('date2');
get_vocab_admin('mail_de');
get_vocab_admin('mail_a');
get_vocab_admin('mail_sujet');
get_vocab_admin('mail_message');

get_vocab_admin('users_connected');

$sql = "SELECT id, start_time, FROM_UNIXTIME(start_time,'%d-%m-%Y %H:%i:%s') as st, end_time, FROM_UNIXTIME(end_time,'%d-%m-%Y %H:%i:%s') as et, name, supprimer FROM ".TABLE_PREFIX."_entry ORDER by start_time desc";
$res = grr_sql_query($sql);

$logsMail = array ();

while ($row = mysqli_fetch_assoc($res)) {
  $logsMail[] = array('idresa' => $row["id"],
                      'debut' => $row["st"],
                      'fin' => $row["et"],
                      'debutts' => $row["start_time"],
                      'fints' => $row["end_time"],
                      'titre' => $row["name"],
                      'sup' => $row["supprimer"]);
}

$sql = "SELECT count(*) as cnt, FROM_UNIXTIME(date,'%d-%m-%Y') as date FROM ".TABLE_PREFIX."_log_resa ORDER BY date limit 1";
$res = grr_sql_query($sql);

if ($row = mysqli_fetch_assoc($res)) {
  $d['DatePlusAncienne'] = $row["date"];
  $d['NombreLog'] = $row["cnt"];
} else {
  $d['NombreLog'] = 0;
  $d['DatePlusAncienne'] = "-";
}

$d['TitreDateLog'] = get_vocab("log_mail").$d['DatePlusAncienne'];

echo $twig->render('admin_log_resa_liste.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings, 'logsmail' => $logsMail ));
?>
