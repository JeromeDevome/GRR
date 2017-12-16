<?php
/**
 * admin_calend.php
 * interface permettant de choisir des outils de réservation en blocs
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2017-12-16 14:00$
 * @author    Laurent Delineau & JeromeB & Marc-Henri PAMISEUX & Yan Naessens
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

include "../include/admin.inc.php";
$grr_script_name = "admin_calend.php";

$back = '';
if (isset($_SERVER['HTTP_REFERER'])) $back = htmlspecialchars($_SERVER['HTTP_REFERER']);

// vérification des droits d'accès 
if(authGetUserLevel(getUserName(),-1,'area') < 5)
{
    showAccessDenied($day, $month, $year, '',$back);
    exit();
}

# print the page header
print_header("","","","",$type="with_session", $page="admin");
// Affichage de la colonne de gauche
include "admin_col_gauche.php";

echo "<h2>".get_vocab('admin_calendar_title.php')."</h2>\n";

echo "<h3> Choisissez le type d'action à réaliser : </h3>\n";

echo "\n<table>";
echo "\n<tr><td><a href='admin_calend2.php'>".get_vocab('admin_calendar_title.php')."</a></td></tr>";
echo "\n<tr><td><a href='admin_delete_entry_after.php'>Supprimer toutes les réservations après une date donnée</a></td></tr>";
echo "\n<tr><td><a href='admin_delete_entry_before.php'>Supprimer toutes les réservations avant une date donnée</a></td></tr>";
echo "\n<tr><td><a href='admin_import_entries_csv_udt.php'>Importer un fichier d'occupation de salles au format CSV provenant de UnDeuxTemps</a></td></tr>";
echo "\n<tr><td><a href='admin_import_entries_csv_direct.php'>Importer un fichier d'occupation de salles au format CSV</a></td></tr>";
echo "\n</table>";

?>
