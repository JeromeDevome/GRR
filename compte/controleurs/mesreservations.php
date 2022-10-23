<?php
/**
 * mesreservations.php
 * Interface permettant à l'utilisateur de gérer son compte dans l'application GRR
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2020-05-03 14:20$
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

include_once('./modeles/mesreservations.php');
$grr_script_name = 'mesreservations.php';
if (!Settings::load())
	die('Erreur chargement settings');
$desactive_VerifNomPrenomUser='y';
if (!grr_resumeSession())
{
	header('Location: logout.php?auto=1&url=$url');
	die();
};

include_once('../include/language.inc.php');

### !!! Definitions des variables !!! ###
	$pChoix = intval((isset($_POST['choix'])&&(($_POST['choix']==0)||($_POST['choix']==1)))? $_POST['choix'] : 1);

#########################################


$back = '';
if (isset($_SERVER['HTTP_REFERER']))
	$back = htmlspecialchars($_SERVER['HTTP_REFERER']);
$user_login = isset($_POST['user_login']) ? $_POST['user_login'] : ($user_login = isset($_GET['user_login']) ? $_GET['user_login'] : NULL);
$valid = isset($_POST['valid']) ? $_POST['valid'] : NULL;
$msg = '';



// mes_resas génère la variable global gListeReservations
$d['nbResultat'] = MesReservations::mes_resas(getUserName(),$pChoix,$dformat);
$d['choix'] = $pChoix;


//list($nbResult, $reservations) = MesReservations::mes_resas(getUserName(),0,$dformat);
get_vocab_admin('resa_menu_explain');

get_vocab_admin('resas_toutes');
get_vocab_admin('resas_a_venir');
get_vocab_admin('goto');

get_vocab_admin('start_date');
get_vocab_admin('time');
get_vocab_admin('duration');
get_vocab_admin('match_area');
get_vocab_admin('room');
get_vocab_admin('sum_by_descrip');
get_vocab_admin('fulldescription');
get_vocab_admin('type');
get_vocab_admin('lastupdate');
get_vocab_admin('statut');
get_vocab_admin('entry_found');
get_vocab_admin('entries_found');
get_vocab_admin('nothing_found');


	echo $twig->render('mesreservations.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings, 'reservations' => $gListeReservations));
?>