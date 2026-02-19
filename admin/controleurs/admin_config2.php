<?php
/**
 * admin_config2.php
 * Interface permettant à l'administrateur la configuration de certains paramètres généraux
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2026-02-19 16:30$
 * @author    Laurent Delineau & JeromeB
 * @copyright Copyright 2003-2026 Team DEVOME - JeromeB
 * @link      http://www.gnu.org/licenses/licenses.html
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 */

$trad = $vocab;

$msg = "";

// Nombre maximum de réservation (tous domaines confondus)
if (isset($_GET['UserAllRoomsMaxBooking']))
{
    settype($_GET['UserAllRoomsMaxBooking'],"integer");
    if ($_GET['UserAllRoomsMaxBooking']=='')
        $_GET['UserAllRoomsMaxBooking'] = -1;
    if ($_GET['UserAllRoomsMaxBooking']<-1)
        $_GET['UserAllRoomsMaxBooking'] = -1;
    if (!Settings::set("UserAllRoomsMaxBooking", $_GET['UserAllRoomsMaxBooking']))
        $msg .= "Erreur lors de l'enregistrement de UserAllRoomsMaxBooking !<br />";
}
// Type d'accès
if (isset($_GET['authentification_obli']))
{
    if (!Settings::set("authentification_obli", $_GET['authentification_obli']))
        $msg .= "Erreur lors de l'enregistrement de authentification_obli !<br />";
}
// Visualisation de la fiche de description d'une ressource.
if (isset($_GET['visu_fiche_description']))
{
    if (!Settings::set("visu_fiche_description", $_GET['visu_fiche_description']))
        $msg .= "Erreur lors de l'enregistrement de visu_fiche_description !<br />";
}
// Accès fiche de réservation d'une ressource.
if (isset($_GET['acces_fiche_reservation']))
{
    if (!Settings::set("acces_fiche_reservation", $_GET['acces_fiche_reservation']))
        $msg .= "Erreur lors de l'enregistrement de acces_fiche_reservation !<br />";
}
// Accès page d'édition d'une ressource.
if (isset($_GET['acces_config']))
{
    if (!Settings::set("acces_config", $_GET['acces_config']))
        $msg .= "Erreur lors de l'enregistrement de acces_config !<br />";
}
// Accès à l'outil de recherche/rapport/stat
if (isset($_GET['allow_search_level']))
{
    if (!Settings::set("allow_search_level", $_GET['allow_search_level']))
        $msg .= "Erreur lors de l'enregistrement de allow_search_level !<br />";

}
// allow_user_delete_after_begin
if (isset($_GET['allow_user_delete_after_begin']))
{
    if (!Settings::set("allow_user_delete_after_begin", $_GET['allow_user_delete_after_begin']))
        $msg .= "Erreur lors de l'enregistrement de allow_user_delete_after_begin !<br />";
}
// allow_gestionnaire_modify_del
if (isset($_GET['allow_gestionnaire_modify_del']))
{
    if (!Settings::set("allow_gestionnaire_modify_del", $_GET['allow_gestionnaire_modify_del']))
        $msg .= "Erreur lors de l'enregistrement de allow_gestionnaire_modify_del !<br />";
}

// Enregistrement de allow_users_modify_profil
// Un gestionnaire d'utilisateurs ne peut pas Autoriser ou non la modification par un utilisateur de ses informations personnelles
if ((isset($_GET['allow_users_modify_profil'])) && (authGetUserLevel(getUserName(), -1, 'user') !=  1))
{
	if (!Settings::set("allow_users_modify_profil", $_GET['allow_users_modify_profil']))
		$msg .= get_vocab("message_records_error");
}
// Enregistrement de allow_users_modify_email
// Un gestionnaire d'utilisateurs ne peut pas Autoriser ou non la modification par un utilisateur de ses informations personnelles
if ((isset($_GET['allow_users_modify_email'])) && (authGetUserLevel(getUserName(), -1, 'user') !=  1))
{
	if (!Settings::set("allow_users_modify_email", $_GET['allow_users_modify_email']))
		$msg .= get_vocab("message_records_error");;
}
// Enregistrement de allow_users_modify_mdp
// Un gestionnaire d'utilisateurs ne peut pas Autoriser ou non la modification par un utilisateur de son mot de passe
if ((isset($_GET['allow_users_modify_mdp'])) && (authGetUserLevel(getUserName(), -1, 'user') !=  1))
{
	if (!Settings::set("allow_users_modify_mdp", $_GET['allow_users_modify_mdp']))
		$msg .= get_vocab("message_records_error");
}
// Enregistrement de allow_users_modify_affichage
// Un gestionnaire d'utilisateurs ne peut pas Autoriser ou non la modification par un utilisateur de son affichage par défaut
if ((isset($_GET['allow_users_modify_affichage'])) && (authGetUserLevel(getUserName(), -1, 'user') !=  1))
{
	if (!Settings::set("allow_users_modify_affichage", $_GET['allow_users_modify_affichage']))
		$msg .= get_vocab("message_records_error");
}
// Enregistrement de allow_users_modify_domaine
// Un gestionnaire d'utilisateurs ne peut pas Autoriser ou non la modification par un utilisateur de son domaine par defaut
if ((isset($_GET['allow_users_modify_domaine'])) && (authGetUserLevel(getUserName(), -1, 'user') !=  1))
{
	if (!Settings::set("allow_users_modify_domaine", $_GET['allow_users_modify_domaine']))
		$msg .= get_vocab("message_records_error");
}
// Enregistrement de allow_users_modify_theme
// Un gestionnaire d'utilisateurs ne peut pas Autoriser ou non la modification par un utilisateur de son theme
if ((isset($_GET['allow_users_modify_theme'])) && (authGetUserLevel(getUserName(), -1, 'user') !=  1))
{
	if (!Settings::set("allow_users_modify_theme", $_GET['allow_users_modify_theme']))
		$msg .= get_vocab("message_records_error");
}
// Enregistrement de allow_users_modify_langue
// Un gestionnaire d'utilisateurs ne peut pas Autoriser ou non la modification par un utilisateur de sa langue
if ((isset($_GET['allow_users_modify_langue'])) && (authGetUserLevel(getUserName(), -1, 'user') !=  1))
{
	if (!Settings::set("allow_users_modify_langue", $_GET['allow_users_modify_langue']))
		$msg .= get_vocab("message_records_error");
}

// Enregistrement de mail_user_obligatoire
// Un gestionnaire d'utilisateurs ne peut pas modifier ce paramètre
$mailUserObligatoire= isset($_GET["mail_user_obligatoire"]) ? "y" : "n";
if (isset($_GET['ok']) && authGetUserLevel(getUserName(), -1, 'user') !=  1)
{
	if (!Settings::set("mail_user_obligatoire", $mailUserObligatoire))
		$msg .= get_vocab("message_records_error");
}


if (!Settings::load())
    die("Erreur chargement settings");
 $AllSettings = Settings::getAll();

// Si pas de problème, message de confirmation
if (isset($_GET['ok'])) {
    $_SESSION['displ_msg'] = 'yes';
    if ($msg == '') {
        $d['enregistrement'] = 1;
    } else{
        $d['enregistrement'] = $msg;
    }
}

$AllSettings = Settings::getAll();

echo $twig->render($page.'.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings));
?>