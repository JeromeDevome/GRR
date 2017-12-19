<?php
/**
 * admin_config.php
 * Interface permettant à l'administrateur la configuration de certains paramètres généraux
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2017-12-16 14:00$
 * @author    Laurent Delineau & JeromeB
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
$grr_script_name = "admin_config.php";
$back = '';
if (isset($_SERVER['HTTP_REFERER']))
	$back = htmlspecialchars($_SERVER['HTTP_REFERER']);
$_SESSION['chemin_retour'] = "admin_config.php";
$day   = date("d");
$month = date("m");
$year  = date("Y");
check_access(6, $back);
$page_config = isset($_GET["page_config"]) ? $_GET["page_config"] : '1';
if ($page_config == 1)
	include "./admin_config1.php";
else if ($page_config == 2)
	include "./admin_config2.php";
else if ($page_config == 3)
	include "./admin_config3.php";
else if ($page_config == 4)
	include "./admin_config4.php";
else if ($page_config == 5)
	include "./admin_config5.php"; // pour la configuration des jours/cycles
else
	die();
?>
</body>
</html>
