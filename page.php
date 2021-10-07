<?php
/**
 * page.php
 * Script chargeant les pages enregistrées (CGU)
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2018-02-10 20:00$
 * @author    JeromeB
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
$grr_script_name = 'page.php';

include_once('include/connect.inc.php');
include_once('include/config.inc.php');
include_once('include/misc.inc.php');
include_once('include/functions.inc.php');
require_once('include/'.$dbsys.'.inc.php');
require_once('include/session.inc.php');
include_once('include/settings.class.php');
include_once('include/pages.class.php');

if (!Settings::load())
	die('Erreur chargement settings');
if (!Pages::load())
	die('Erreur chargement pages');
if (!isset($_GET['page']))
	die('Erreur choix de la page');

/* if (!grr_resumeSession())
{
	header('Location: logout.php?auto=1&url=$url');
	die();
}; */

echo Pages::get('CGU');
?>