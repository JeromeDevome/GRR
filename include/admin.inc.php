<?php
/**
 * admin.inc.php
 *
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2021-10-06 19:17$
 * @author    JeromeB & Laurent Delineau & Yan Naessens
 * @copyright Copyright 2003-2019 Team DEVOME - JeromeB
 * @link      http://www.gnu.org/licenses/licenses.html
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 */

if (@file_exists('../personnalisation/connect.inc.php')){
	include "../personnalisation/connect.inc.php";
}else{
	include "./personnalisation/connect.inc.php";
}

include "config.inc.php";
include "$dbsys.inc.php";
include "mrbs_sql.inc.php";
include "misc.inc.php";
include "functions.inc.php";


// Settings
require_once("settings.class.php");
//Chargement des valeurs de la table settingS
if (!Settings::load())
	die("Erreur chargement settings");
// Session related functions
require_once("session.inc.php");
// Resume session
if (!grr_resumeSession()) {
	header("Location: ../logout.php?auto=1&url=$url");
	die();
};
// Paramètres langage
$use_admin = 'y';
include "language.inc.php";
?>