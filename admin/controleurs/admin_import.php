<?php
/**
 * admin_import.php
 * Interface de gestion des types de réservations
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2026-09-06 11:00$
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
$grr_script_name = "admin_import.php";

// Accès à la page
SecuAccess::CheckAccess(6, $back);

include_once "modeles/import.class.php";

// les variables attendues et leur type
$form_vars = array(
    'p_etape' => array('int', 1), // 1 : selection type/format/fichier, 2 : Sélection des données, 3 : Contrôle avant import
    'p_type' => array('alphanumeric', ''), // id du type à importer
    'p_format' => array('alphanumeric', ''), // id du format à importer
    'p_sepateur_champs' => array('string', ';'), // séparateur de champs
    'p_sepateur_multiples' => array('string', ','), // séparateur de valeurs multiples
    'p_ignorer_premiere_ligne' => array('int', 0), // 1 : ignorer la première ligne, 0 : ne pas l'ignorer
);
// récupération des valeurs des variables passées en paramètres
foreach($form_vars as $var => $params)
    $$var = SecuChaine::GetFormVarSecure($var, $params[0], $params[1]);

$p_ignorer_premiere_ligne = (int) $p_ignorer_premiere_ligne;
$p_etape = (int) $p_etape;
$types_importation_connus = array('reservations', 'utilisateurs', 'groupes', 'types');
$type_importation_connu = in_array($p_type, $types_importation_connus, true);

if (in_array($p_etape, array(2, 3, 4), true) && !$type_importation_connu) {
    $d['erreurImportation'] = 'Le type d’importation sélectionné est inconnu.';
    $p_etape = 1;
}

if($p_etape == 2)
{
    // Lire le fichier et conserver toutes les lignes pour le contrôle de l'étape 3.
    $toutes_les_lignes = array();
    $lignes_apercu = array();
    $nombre_colonnes_apercu = 0;
    $fichier_transmis = isset($_FILES['p_import_file']) ? $_FILES['p_import_file'] : null;
    $separateur = isset($_POST['p_sepateur_champs']) && $_POST['p_sepateur_champs'] !== ''
        ? substr((string) $_POST['p_sepateur_champs'], 0, 1)
        : ';';

    if ($fichier_transmis && $fichier_transmis['error'] === UPLOAD_ERR_OK) {
        $handle_fichier = fopen($fichier_transmis['tmp_name'], 'r');
        if ($handle_fichier !== false) {
            while (($ligne = fgetcsv($handle_fichier, 8000, $separateur)) !== false) {
                $toutes_les_lignes[] = $ligne;
                if (count($lignes_apercu) < 10) {
                    $lignes_apercu[] = $ligne;
                }
                $nombre_colonnes_apercu = max($nombre_colonnes_apercu, count($ligne));
            }
            fclose($handle_fichier);
        }
    }

    $_SESSION['admin_import'] = array(
        'rows' => $toutes_les_lignes,
        'columnCount' => $nombre_colonnes_apercu,
        'fileName' => $fichier_transmis['name'] ?? '',
        'separator' => $separateur,
    );

    $d['lignesApercu'] = $lignes_apercu;
    $d['nombreColonnesApercu'] = $nombre_colonnes_apercu;
    $d['nomFichierApercu'] = $fichier_transmis['name'] ?? '';
}
elseif ($p_etape == 3)
{
    $donnees_importation = $_SESSION['admin_import'] ?? array();
    $correspondance = isset($_POST['p_mapping']) && is_array($_POST['p_mapping']) ? $_POST['p_mapping'] : array();
    $colonnes_correspondantes = array();

    foreach ($correspondance as $colonne => $champ) {
        if ($champ !== '') {
            $colonnes_correspondantes[(int) $colonne] = (string) $champ;
        }
    }

    $lignes_validees = array();
    $nombre_lignes_valides = 0;
    $lignes_corrigees = array();
    foreach ($d['lignesApercu'] ?? $donnees_importation['rows'] ?? array() as $index_ligne => $ligne) {
        $ligne = Adm_Import::CorrectionLigne($p_type, $colonnes_correspondantes, $ligne);
        $lignes_corrigees[$index_ligne] = $ligne;
        $ligne_validee = array();
        $authentification = '';
        foreach ($colonnes_correspondantes as $colonne_correspondante => $champ_correspondant) {
            if (Adm_Import::NomsChamps($champ_correspondant) === 'user_authentification') {
                $authentification = trim((string) ($ligne[$colonne_correspondante] ?? ''));
                break;
            }
        }
        foreach ($colonnes_correspondantes as $colonne => $champ) {
            $ligne_validee[$colonne] = Adm_Import::ValidationCellule($champ, $ligne[$colonne] ?? '', $p_type, $p_sepateur_multiples, $authentification);
        }
        if ($p_type === 'reservations' && Adm_Import::LigneValide($ligne_validee)) {
            $erreur_disponibilite = Adm_Import::ReservationPossible($colonnes_correspondantes, $ligne);
            if ($erreur_disponibilite !== '') {
                foreach ($colonnes_correspondantes as $colonne => $champ) {
                    if (in_array(Adm_Import::NomsChamps($champ), array('date', 'heure_debut', 'ressource'), true)) {
                        $ligne_validee[$colonne]['valid'] = false;
                        $ligne_validee[$colonne]['error'] = $erreur_disponibilite;
                        break;
                    }
                }
            }
        }
        if (($p_ignorer_premiere_ligne !== 1 || $index_ligne !== 0) && Adm_Import::LigneValide($ligne_validee)) {
            $nombre_lignes_valides++;
        }
        $lignes_validees[] = $ligne_validee;
    }

    $d['lignesApercu'] = $lignes_corrigees;
    $d['nombreColonnesApercu'] = $donnees_importation['columnCount'] ?? 0;
    $d['nomFichierApercu'] = $donnees_importation['fileName'] ?? '';
    $d['nombreTotalLignes'] = count($d['lignesApercu']) - ($p_ignorer_premiere_ligne === 1 ? 1 : 0);
    if ($d['nombreTotalLignes'] < 0) {
        $d['nombreTotalLignes'] = 0;
    }
    $d['ligneCorrespondanceIgnoree'] = $p_ignorer_premiere_ligne === 1;
    $d['colonnesCorrespondantes'] = $colonnes_correspondantes;
    $d['lignesValidees'] = $lignes_validees;
    $d['nombreLignesValides'] = $nombre_lignes_valides;
    $d['lignesInvalides'] = array();
    foreach ($lignes_validees as $index_ligne => $ligne_validee) {
        $d['lignesInvalides'][$index_ligne] = !Adm_Import::LigneValide($ligne_validee);
    }
    $d['lignesExistantes'] = array();
    foreach ($d['lignesApercu'] as $index_ligne => $ligne) {
        $d['lignesExistantes'][$index_ligne] = Adm_Import::CreationOuModification($p_type, $colonnes_correspondantes, $ligne);
    }
    $_SESSION['admin_import']['mapping'] = $colonnes_correspondantes;
    $_SESSION['admin_import']['rows'] = $lignes_corrigees;
    $_SESSION['admin_import']['validatedRows'] = $lignes_validees;
    $_SESSION['admin_import']['existingRows'] = $d['lignesExistantes'];
}
elseif ($p_etape == 4)
{
    VerifyModeDemo();
    $donnees_importation = $_SESSION['admin_import'] ?? array();
    $lignes = $donnees_importation['rows'] ?? array();
    $correspondance = $donnees_importation['mapping'] ?? array();
    $lignes_validees = $donnees_importation['validatedRows'] ?? array();
    $lignes_existantes = $donnees_importation['existingRows'] ?? array();
    $nombre_ignorees = 0;
    $nombre_importees = 0;
    $nombre_echecs = 0;

    foreach ($lignes as $index_ligne => $ligne) {
        if ($p_ignorer_premiere_ligne === 1 && $index_ligne === 0) {
            continue;
        }
        if (!isset($lignes_validees[$index_ligne]) || !Adm_Import::LigneValide($lignes_validees[$index_ligne])) {
            $nombre_ignorees++;
            continue;
        }
        $erreur_ligne = '';
        if (Adm_Import::EnregistrementLigne($p_type, $correspondance, $ligne, !empty($lignes_existantes[$index_ligne]), $p_sepateur_multiples, $erreur_ligne)) {
            $nombre_importees++;
        } else {
            $nombre_echecs++;
            $d['erreursImportation'][$index_ligne] = $erreur_ligne;
        }
    }

    $d['resultatImportation'] = array('importees' => $nombre_importees, 'ignorees' => $nombre_ignorees, 'echecs' => $nombre_echecs);
    $d['erreursImportation'] = $d['erreursImportation'] ?? array();
    $d['nombreTotalLignes'] = count($lignes) - ($p_ignorer_premiere_ligne === 1 ? 1 : 0);
    if ($d['nombreTotalLignes'] < 0) {
        $d['nombreTotalLignes'] = 0;
    }
    $d['ligneCorrespondanceIgnoree'] = $p_ignorer_premiere_ligne === 1;
}
$trad['TitrePage']  = $trad['admin_import'];
$d['etape']         = $p_etape;
$d['p_type']        = $p_type;
$d['p_format']      = $p_format;
$d['p_sepateur_champs']      = $p_sepateur_champs;
$d['p_sepateur_multiples']   = $p_sepateur_multiples;
$d['p_ignorer_premiere_ligne'] = $p_ignorer_premiere_ligne;

echo $twig->render($page.'.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings));

?>