<?php
/**
 * page.php
 * Interface permettant à l'utilisateur de gérer son compte dans l'application GRR
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2018-02-10 20:00$
 * @author    JeromeB
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

include_once('include/pages.class.php');
$grr_script_name = 'page.php';

if (!Settings::load())
	die('Erreur chargement settings');
if (!Pages::load())
	die('Erreur chargement pages');
if (!isset($_GET['page']))
	die('Erreur choix de la page');

$ctnPage = $_GET['page'];

$d['CtnPage'] = Pages::get($ctnPage);


echo $twig->render('page.twig', array('trad' => $trad, 'd' => $d, 'settings' => $AllSettings));
?>