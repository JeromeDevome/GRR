<?php
/**
 * admin_config2.php
 * Interface permettant à l'administrateur la configuration de certains paramètres généraux
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2017-12-16 14:00$
 * @author    Laurent Delineau & JeromeB
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

get_vocab_admin("admin_config1");
get_vocab_admin("admin_config2");
get_vocab_admin("admin_config3");
get_vocab_admin("admin_config4");
get_vocab_admin("admin_config5");
get_vocab_admin("admin_config6");

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

get_vocab_admin("authentification_obli_msg");
get_vocab_admin("authentification_obli0");
get_vocab_admin("authentification_obli1");

get_vocab_admin("visu_fiche_description_msg");
get_vocab_admin("visu_fiche_description0");
get_vocab_admin("visu_fiche_description1");
get_vocab_admin("visu_fiche_description2");
get_vocab_admin("visu_fiche_description3");
get_vocab_admin("visu_fiche_description4");
get_vocab_admin("visu_fiche_description5");
get_vocab_admin("visu_fiche_description6");

get_vocab_admin("acces_fiche_reservation_msg");

get_vocab_admin("allow_search_level_msg");
get_vocab_admin("allow_search_level0");
get_vocab_admin("allow_search_level1");
get_vocab_admin("allow_search_level2");
get_vocab_admin("allow_search_level5");

get_vocab_admin("allow_user_delete_after_beginning_msg");
get_vocab_admin("allow_user_delete_after_beginning0");
get_vocab_admin("allow_user_delete_after_beginning1");
get_vocab_admin("allow_user_delete_after_beginning2");
get_vocab_admin("allow_gestionnaire_modify_del0");
get_vocab_admin("allow_gestionnaire_modify_del1");

get_vocab_admin("modification_parametres_personnels");
get_vocab_admin("modification_parametre_email");
get_vocab_admin("modification_mdp");
get_vocab_admin("mail_user_obligatoire");
get_vocab_admin("all");
get_vocab_admin("all_but_visitors");
get_vocab_admin("only_administrators");

get_vocab_admin("max_booking");

get_vocab_admin("save");
get_vocab_admin('message_records');

echo $twig->render($page.'.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings));
?>