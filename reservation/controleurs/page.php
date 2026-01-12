<?php
/**
 * page.php
 * Interface permettant à l'utilisateur de gérer son compte dans l'application GRR
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2018-02-10 20:00$
 * @author    JeromeB
 * @copyright Copyright 2003-2023 Team DEVOME - JeromeB
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

$nomPage = alphanum($_GET['pageaffiche']);

if (!Settings::load())
	die('Erreur chargement settings');
if (!Pages::load())
	die('Erreur chargement pages');
if (!isset($nomPage))
	die('Erreur choix de la page');

$infosPage = Pages::get($nomPage);

if($infosPage[2]){
	$d['CtnPage'] = "Impossible d'y accèder.";
} else{
	//$infosPage = Pages::get($nomPage);
	$d['TitrePage'] = $infosPage[0];
	$d['CtnPage'] =  $infosPage[1];
}


echo $twig->render('page.twig', array('trad' => $trad, 'd' => $d, 'settings' => $AllSettings));
?>