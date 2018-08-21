<?php
/**
 * admin.inc.php
 *
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2017-12-16 14:00$
 * @author    JeromeB & Laurent Delineau
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

if (@file_exists('../include/connect.inc.php')){
	$racine = "../";
}else{
	$racine = "./";
}

include $racine."include/connect.inc.php";
include $racine."include/config.inc.php";
include $racine."include/mrbs_sql.inc.php";
include $racine."include/misc.inc.php";
include $racine."include/functions.inc.php";
include $racine."include/$dbsys.inc.php";

// Settings
require_once($racine."include/settings.class.php");
//Chargement des valeurs de la table settingS
if (!Settings::load())
	die("Erreur chargement settings");
// Session related functions
require_once($racine."include/session.inc.php");
// Resume session
if (!grr_resumeSession()) {
	header("Location: {$racine}logout.php?auto=1&url=$url");
	die();
};
// Paramètres langage
$use_admin = 'y';
include $racine."include/language.inc.php";
?>
