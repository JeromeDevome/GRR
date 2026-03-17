<?php
/**
 * admin_accueil
 * Interface d'accueil de l'administration des domaines et des ressources
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2026-03-17 20:00$
 * @author    JeromeB & Yan Naessens
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

$grr_script_name = "admin_accueil.php";

$trad = $vocab;

$trad['TitrePage'] = $trad['admin_accueil'];
$trad['SousTitrePage'] = 'Administration';

$d['level'] =  authGetUserLevel(getUserName(), -1, 'area');

// Widget connexion
$d['nombreConnecte'] = AdminFonctions::NombreDeConnecter();
$d['nombreUtilisateur'] = AdminFonctions::NombreUtilisateurs();

// Widget mot de passe facile
$d['nombreMDPFacile'] = "N/A"; //AdminFonctions::NombreUtilisateursMDPfacile();

// WARNING
$d['alerteTDB'] = AdminFonctions::Warning();

// Widget dernières connexions
$d['dernieresConnexions'] = AdminFonctions::DernieresConnexion(5);

// Widget réservations à modérer
list($d['nombreModeration'], $d['listeModeration'])  = AdminFonctions::ReservationsAModerer(getUserName());

// Widget recherche de mise à jour GRR
list($d['maj_SiteGRR'], $d['maj_SiteGRR_Num'], $d['maj_SiteGRR_Version']) = AdminFonctions::RechercheMajGRR();
$d['gRecherche_MAJ'] = $gRecherche_MAJ;

// Widget news devome
$url = "https://grr.devome.com/API/information.php?flux=".$gFluxNewsDevome;
$opts = [
        'http' => [
                'method' => 'GET',
                'timeout' => 2,
                'header' => [
                        'User-Agent: PHP'
                ]
        ]
];
$ctx = stream_context_create($opts);
$d['newsDevome'] = @file_get_contents( $url, 0, $ctx );

//
echo $twig->render('admin_accueil.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings));
?>