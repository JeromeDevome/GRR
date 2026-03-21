<?php
/**
 * moderationsliste.php
 * Formulaire d'envoi de mail
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2025-07-20 12:00$
 * @author    JeromeB
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

$grr_script_name = "moderationsliste.php";

$trad = $vocab;


$acces = false;
$listeModeration = array();

// Utiliser la fonction resaToModerate() optimisée
$resasAModerer = resaToModerate($d['gNomUser']);

if (!empty($resasAModerer)) {
    $acces = true;
    $d['acces'] = 1;

    foreach($resasAModerer as $resa) {
        $link = "?p=vuereservation&id=".$resa['id']."&mode=page";
        if (Settings::get("module_multisite") == "Oui")
        {
            $listeModeration[] = array(
                'site' => $resa['site'],
                'ressource' => $resa['room'],
                'debut' => time_date_string($resa['start_time'], $dformat),
                'createur' => $resa['create_by'],
                'beneficiaire' => $resa['beneficiaire'],
                'lien' => $link
            );
        }
        else
        {
            $listeModeration[] = array(
                'domaine' => $resa['area'],
                'ressource' => $resa['room'],
                'debut' => time_date_string($resa['start_time'], $dformat),
                'createur' => $resa['create_by'],
                'beneficiaire' => $resa['beneficiaire'],
                'lien' => $link
            );
        }
    }

    $d['nbResaAModerer'] = count($listeModeration);
}



echo $twig->render('moderationsliste.twig', array('trad' => $trad, 'd' => $d, 'settings' => $AllSettings, 'resas' => $listeModeration));
?>