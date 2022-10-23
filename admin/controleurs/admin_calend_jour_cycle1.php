<?php
/**
 * admin_config_calend1.php
 * Interface permettant à l'administrateur la configuration des paramètres pour le module Jours Cycles
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2017-12-16 14:00$
 * @author    Laurent Delineau & JeromeB
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


// Affichage du tableau de choix des sous-configuration
$grr_script_name = "admin_calend_jour_cycle1.php";

// Met à jour dans la BD le nombre de jours par cycle
if (isset($_GET['nombreJours']))
{
	if (!Settings::set("nombre_jours_Jours_Cycles", $_GET['nombreJours']))
		echo "Erreur lors de l'enregistrement de nombre_jours_Jours_Cycles ! <br />";
}
// Met à jour dans la BD le premier jour du premier cycle
if (isset($_GET['jourDebut']))
{
	if (!Settings::set("jour_debut_Jours_Cycles", $_GET['jourDebut']))
		echo "Erreur lors de l'enregistrement de jour_debut_Jours_Cycles ! <br />";
}

$AllSettings = Settings::getAll();
//
// Configurations du nombre de jours par Jours/Cycles et du premier jour du premier Jours/Cycles
//******************************
//
get_vocab_admin("titre_config_Jours_Cycles");

get_vocab_admin("admin_config_calend1");
get_vocab_admin("admin_config_calend2");
get_vocab_admin("admin_config_calend3");

get_vocab_admin("explication_Jours_Cycles1");
get_vocab_admin("explication_Jours_Cycles2");

get_vocab_admin("nombre_jours_Jours_Cycles");
get_vocab_admin("debut_Jours_Cycles");

get_vocab_admin("save");


echo $twig->render('admin_calend_jour_cycle1.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings));
?>
