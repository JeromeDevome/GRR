<?php
/**
 * admin_calend.php
 * interface permettant de choisir des outils de réservation en blocs
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2017-12-16 14:00$
 * @author    Laurent Delineau & JeromeB & Marc-Henri PAMISEUX & Yan Naessens
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

$grr_script_name = "admin_calend.php";

// vérification des droits d'accès 
if(authGetUserLevel(getUserName(),-1,'area') < 5)
{
    showAccessDenied($day, $month, $year, '',$back);
    exit();
}

get_vocab_admin('admin_calendar_title');


echo $twig->render('admin_calend.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings));

?>
