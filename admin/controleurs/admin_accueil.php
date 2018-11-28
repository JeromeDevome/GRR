<?php
/**
 * admin_accueil
 * Interface d'accueil de l'administration des domaines et des ressources
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2017-12-16 14:00$
 * @author    Laurent Delineau & JeromeB
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

$grr_script_name = "admin_accueil.php";

get_vocab_admin("admin_accueil");

$trad['TitrePage'] = $trad['admin_accueil'];
$trad['SousTitrePage'] = 'Administration';

// Widget connection
get_vocab_admin("users_connected");
$trad['dNombreConnecte'] = AdminFonctions::NombreDeConnecter();
$trad['dNombreUtilisateur'] = AdminFonctions::NombreUtilisateurs();

// Widget mot de passe facile
get_vocab_admin("admin_user_mdp_facile");
$trad['dNombreMDPFacile'] = AdminFonctions::NombreUtilisateursMDPfacile();



?>
