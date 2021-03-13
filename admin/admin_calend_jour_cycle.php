<?php
/**
 * admin_calend_jour_cycle.php
 * Interface permettant à l'administrateur la configuration des jours cycles
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2021-03-13 12:05$
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
$grr_script_name = "admin_calend_jour_cycle.php";

include "../include/admin.inc.php";

$back = (isset($_SERVER['HTTP_REFERER']))? htmlspecialchars_decode($_SERVER['HTTP_REFERER'], ENT_QUOTES) : "./admin_accueil.php" ;
$_SESSION['chemin_retour'] = "admin_calend_jour_cycle.php";
check_access(6, $back);
$page_calend = isset($_GET["page_calend"]) ? $_GET["page_calend"] : '3';
if ($page_calend == 3)
	include "./admin_config_calend3.php";
else if ($page_calend == 2)
	include "./admin_config_calend2.php";
else if ($page_calend == 1)
	include "./admin_config_calend1.php";
else
	die();
?>
