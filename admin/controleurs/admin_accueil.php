<?php
/**
 * admin_accueil
 * Interface d'accueil de l'administration des domaines et des ressources
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2022-01-27 15:10$
 * @author    JeromeB & Yan Naessens
 * @copyright Copyright 2003-2022 Team DEVOME - JeromeB
 * @link      http://www.gnu.org/licenses/licenses.html
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 */

$grr_script_name = "admin_accueil.php";

get_vocab_admin("admin_accueil");

$trad['TitrePage'] = $trad['admin_accueil'];
$trad['SousTitrePage'] = 'Administration';

$trad['dLevel'] =  authGetUserLevel(getUserName(), -1, 'area');

// Widget connexion
get_vocab_admin("users_connected");
$trad['dNombreConnecte'] = AdminFonctions::NombreDeConnecter();
$trad['dNombreUtilisateur'] = AdminFonctions::NombreUtilisateurs();

// Widget mot de passe facile
get_vocab_admin("admin_user_mdp_facile");
$trad['dNombreMDPFacile'] = AdminFonctions::NombreUtilisateursMDPfacile();

// WARNING
list($trad['dTypeWarning'], $trad['dMessageWarning'], $trad['dTexteLienWarning'], $trad['dLienWarning'])  = AdminFonctions::Warning();

// Widget dernières connexions
get_vocab_admin("login_name");
get_vocab_admin("begining_of_session");
$trad['dDernieresConnexions'] = AdminFonctions::DernieresConnexion(5);

// Widget réservations à modérer
get_vocab_admin("room");
get_vocab_admin("start_date");
get_vocab_admin("created_by");
get_vocab_admin("nom_beneficiaire");
list($trad['dNombreModeration'], $trad['dListeModeration'])  = AdminFonctions::ReservationsAModerer(getUserName());


?>