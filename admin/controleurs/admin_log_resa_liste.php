<?php
/**
 * admin_log_resa_liste.php
 * Interface de gestion des connexions
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2026-09-27 18:30$
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

$grr_script_name = "admin_log_resa_liste.php";

// Accès à la page
SecuAccess::CheckAccess(6, $back);


/** Affichage de la page **/
  $trad['TitrePage'] = $trad["admin_log_resa_liste"];
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

  echo $twig->render('admin_log_resa_liste.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings, 'logsmail' => $logsMail ));
?>
