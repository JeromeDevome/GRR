<?php
/**
 * admin_page_generale.php
 * Interface permettant à l'administrateur la configuration de certains paramètres généraux
 * Ce script fait partie de l'application GRR.
 * Dernière modification : $Date: 2026-05-30 17:30$
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

$grr_script_name = 'admin_page_generale.php';

// Accès à la page
SecuAccess::CheckAccess(6, $back);

include('../include/import.class.php');

$msg        = '';
$dossier    = '../personnalisation/'.$gcDossierImg.'/logos/';


$d['dossierLogo'] = $dossier;


// les variables attendues et leur type
$form_vars = array(
    'submit' => 'int',
    'sync' => 'int',
    'sup_img' => 'int',
    'authentification_obli' => 'int',
    'company' => 'string',
    'grr_url' => 'string',
    'use_grr_url' => 'int',
    'webmaster_name' => 'string',
    'webmaster_email' => 'string',
    'technical_support_email' => 'string',
    'message_accueil' => '',
    'langues_dispo_array' => 'array',
    'default_css' => 'alphanumeric',
    'default_language'   => 'string',
    'envoyer_email_avec_formulaire' => 'int'
);
// récupération des valeurs des variables passées en paramètres
foreach($form_vars as $var => $var_type)
    $$var = SecuChaine::GetFormVarSecure($var, $var_type);


/** Accès & Droits **/
    if ($submit == 1) {
         $settings_results[] = Settings::set2("authentification_obli", $authentification_obli);
    }

/** Configuration **/
    if ($submit == 1) {

        // Nom de l'établissement
        $settings_results[] = Settings::set2("company", $company);

        // URL de GRR
        $settings_results[] = Settings::set2("grr_url", $grr_url);
        $settings_results[] = Settings::set2("use_grr_url", $use_grr_url);

        // Nom du gestionnaire
        $settings_results[] = Settings::set2("webmaster_name", $webmaster_name);

        // Email du gestionnaire
        $settings_results[] = Settings::set2("webmaster_email", $webmaster_email);

        // Email du support technique
        $settings_results[] = Settings::set2("technical_support_email", $technical_support_email);
    }

/** Apparence **/
    if ($submit == 1) {

        // Enregistrement du logo
        if (!empty($_FILES['doc_file']['tmp_name']))
        {
            list($nomImage, $resultImport) = Import::Image($dossier, 'logo');

            if($resultImport == ""){
                $settings_results[] = Settings::set2("logo", $nomImage);
            } else {
                $settings_results[] = array(3, "Le logo n'a pas pu être importé : $resultImport");
            }
        }

        // Message personnalisé dans le bandeau
        $settings_results[] = Settings::set2("message_accueil", $message_accueil);

        // Langues disponibles
        if (!empty($langues_dispo_array)) { // Obligé d'avoir une langue de disponible
            $langues_str = implode(';', $langues_dispo_array);
            $settings_results[] = Settings::set2("langues_dispo", $langues_str);
        }

        // Style/thème
        $settings_results[] = Settings::set2("default_css", $default_css);

        // Langue par défaut
        if (!Settings::set('default_language', $default_language))
            $msg .= "Erreur lors de l'enregistrement de default_language !<br />";
        else {
            // S'assurer que la langue par défaut est toujours dans les langues disponibles
            // et que seules les langues valides (définies dans $liste_language) sont conservées
            $langues_dispo_value = Settings::get('langues_dispo');
            $langues_dispo_list = array_filter(explode(';', $langues_dispo_value));

            // Filtrer pour ne garder que les langues valides
            $langues_valides = array_intersect($langues_dispo_list, $liste_language);

            // Ajouter la langue par défaut si elle est valide et pas déjà présente
            if (in_array($default_language, $liste_language) && !in_array($default_language, $langues_valides)) {
                $langues_valides[] = $default_language;
            }

            // Réenregistrer les langues filtrées
            if (!empty($langues_valides)) {
                $settings_results[] = Settings::set2("langues_dispo", implode(';', $langues_valides));
            }
        }
        unset($_SESSION['default_language']);

        // Lien mail ou fomulaire de contact
        $settings_results[] = Settings::set2("envoyer_email_avec_formulaire", $envoyer_email_avec_formulaire);

    }

    // Suppression du logo
    if (isset($sup_img) && Settings::get('logo') != '') {
        $ok1 = false;
        if ($f = @fopen("$dossier/.test", 'w')) {
            @fputs($f, '<'.'?php $ok1 = true; ?'.'>');
            @fclose($f);
            include "$dossier/.test";
        }
        if (!$ok1) {
            $msg .= "L\'image n\'a pas pu être supprimée : problème d\'écriture sur le répertoire. Veuillez signaler ce problème à l\'administrateur du serveur.\\n";
            $ok = 'no';
        } else {
            $nom_picture = $dossier.Settings::get('logo');
            if (@file_exists($nom_picture)) {
                unlink($nom_picture);
            }
            $settings_results[] = Settings::set2("logo", '');
        }
    }

    // Synchronisation des langues et thèmes pour les utilisateurs
    if ($sync == 1) {
        $sql = "UPDATE ".TABLE_PREFIX."_utilisateurs SET default_style=''"; // Vide = choix admin
        if (grr_sql_command($sql) < 0)
            fatal_error(0, grr_sql_error());
        else
            $d['enregistrement'] = "Synchronisation terminée !<br />";
    } elseif ($sync == 2) {
        $sql = "UPDATE ".TABLE_PREFIX."_utilisateurs SET default_language='".Settings::get('default_language')."'";
        if (grr_sql_command($sql) < 0)
            fatal_error(0, grr_sql_error());
        else
            $d['enregistrement'] = "Synchronisation terminée !<br />";
    }

/** Résultat de l'enregistrement **/
if ($submit == 1){
    $d['settings_results'] = $settings_results;
}

/** Affichage de la page **/
if (!Settings::load()) {
    die('Erreur chargement settings');
}
$AllSettings = Settings::getAll();

// Choix de la feuille de style
$themesDispo = [];
foreach ($liste_themes as $i => $theme) {
    $themesDispo[] = ["id"  => $theme, "nom" => $liste_name_themes[$i]];
}

// Langues disponibles (pour la sélection multiple)
$langues_dispo_value = Settings::get('langues_dispo');
$d['langues_dispo_saved'] = array_filter(explode(';', $langues_dispo_value));

echo $twig->render($page.'.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings, 'liste_language' => $liste_language, 'themesDispo' => $themesDispo));

?>