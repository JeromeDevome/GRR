<?php
/**
 * admin_config3.php
 * Interface permettant à l'administrateur la configuration de certains paramètres généraux
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2017-12-16 14:00$
 * @author    Laurent Delineau & JeromeB
 * @copyright Since 2003 Team DEVOME - JeromeB
 * @link      http://www.gnu.org/licenses/licenses.html
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 */


$msg = "";
$trad = $vocab;

if (isset($_GET['envoyer_email_avec_formulaire']))
{
	if (!Settings::set("envoyer_email_avec_formulaire", $_GET['envoyer_email_avec_formulaire']))
		$msg .= "Erreur lors de l'enregistrement de envoyer_email_avec_formulaire !<br />";
}
// javascript_info_disabled
if (isset($_GET['javascript_info_disabled']))
{
	if (!Settings::set("javascript_info_disabled", $_GET['javascript_info_disabled']))
		$msg .= "Erreur lors de l'enregistrement de javascript_info_disabled !<br />";
}

if (isset($_GET['verif_reservation_auto']))
{
	if (!Settings::set("verif_reservation_auto", $_GET['verif_reservation_auto']))
		$msg .= "Erreur lors de l'enregistrement de verif_reservation_auto !<br />";

	if ($_GET['verif_reservation_auto'] == 0)
	{
		$_GET['motdepasse_verif_auto_grr'] = "";
		$_GET['chemin_complet_grr'] = "";
	}
}

if (isset($_GET['motdepasse_verif_auto_grr']))
{
	if (($_GET['verif_reservation_auto'] == 1) && ($_GET['motdepasse_verif_auto_grr'] == ""))
		$msg .= "l'exécution du script verif_auto_grr.php requiert un mot de passe !\\n";
	if (!Settings::set("motdepasse_verif_auto_grr", $_GET['motdepasse_verif_auto_grr']))
		$msg .= "Erreur lors de l'enregistrement de motdepasse_verif_auto_grr !<br />";

}
if (isset($_GET['chemin_complet_grr']))
{
	if (!Settings::set("chemin_complet_grr", $_GET['chemin_complet_grr']))
		$msg .= "Erreur lors de l'enregistrement de chemin_complet_grr !<br />";
}
if (!Settings::load())
	die("Erreur chargement settings");

// Si pas de problème, message de confirmation
if (isset($_GET['ok'])) {
    $_SESSION['displ_msg'] = 'yes';
    if ($msg == '') {
        $d['enregistrement'] = 1;
    } else{
        $d['enregistrement'] = $msg;
    }
}


// Affichage
$AllSettings = Settings::getAll();


$d['gMailExpediteur'] = $gMailExpediteur;

$trad['dFctMailRestriction'] = $fonction_mail_restrictions;

echo $twig->render($page.'.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings));

?>