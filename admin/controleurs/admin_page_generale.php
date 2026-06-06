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

$trad   = $vocab;
$msg    = '';
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

        if (!Settings::set("authentification_obli", $authentification_obli))
            $msg .= "Erreur lors de l'enregistrement de authentification_obli !<br />";
    }

/** Configuration **/
    if ($submit == 1) {

        // Nom de l'établissement
        if (!Settings::set("company", $company))
            $msg .= "Erreur lors de l'enregistrement de company !<br />";

        // URL de GRR
        if (!Settings::set("grr_url", $grr_url))
            $msg .= "Erreur lors de l'enregistrement de grr_url !<br />";

        if (!Settings::set("use_grr_url", $use_grr_url))
            $msg .= "Erreur lors de l'enregistrement de use_grr_url !<br />";

        // Nom du gestionnaire
        if (!Settings::set("webmaster_name", $webmaster_name))
            $msg .= "Erreur lors de l'enregistrement de webmaster_name !<br />";

        // Email du gestionnaire
        if (!Settings::set("webmaster_email", $webmaster_email))
            $msg .= "Erreur lors de l'enregistrement de webmaster_email !<br />";

        // Email du support technique
        if (!Settings::set("technical_support_email", $technical_support_email))
            $msg .= "Erreur lors de l'enregistrement de technical_support_email !<br />";
    }

/** Apparence **/
    if ($submit == 1) {

        // Enregistrement du logo
        if (!empty($_FILES['doc_file']['tmp_name']))
        {
            list($nomImage, $resultImport) = Import::Image($dossier, 'logo');

            if($resultImport == ""){
                if (!Settings::set('logo', $nomImage)) {
                    $msg .= "Erreur lors de l'enregistrement du logo (1) !\\n";
                    $ok = 'no';
                }
            } else {
                $msg .= $resultImport;
                $ok = 'no';
            }
        }

        // Message personnalisé dans le bandeau
        if (!Settings::set('message_accueil', $message_accueil))
            $msg .= "Erreur lors de l'enregistrement de message_accueil !<br />";

        // Langues disponibles
        if (!empty($langues_dispo_array)) { // Obligé d'avoir une langue de disponible
            $langues_str = implode(';', $langues_dispo_array);
            if (!Settings::set('langues_dispo', $langues_str))
                $msg .= "Erreur lors de l'enregistrement de langues_dispo !<br />";
        }

        // Style/thème
        if (!Settings::set('default_css', $default_css))
            $msg .= "Erreur lors de l'enregistrement de default_css !<br />";

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
                Settings::set('langues_dispo', implode(';', $langues_valides));
            }
        }
        unset($_SESSION['default_language']);

        // Lien mail ou fomulaire de contact
        if (!Settings::set('envoyer_email_avec_formulaire', $envoyer_email_avec_formulaire))
            $msg .= "Erreur lors de l'enregistrement de envoyer_email_avec_formulaire !<br />";
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
            if (!Settings::set('logo', '')) {
                $msg .= "Erreur lors de l'enregistrement du logo (2) !\\n";
                $ok = 'no';
            }
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
    $_SESSION['displ_msg'] = 'yes';
    if ($msg == '')
        $d['enregistrement'] = 1;
    else
        $d['enregistrement'] = $msg;
}

/** Affichage de la page **/
if (!Settings::load()) {
    die('Erreur chargement settings');
}
$AllSettings = Settings::getAll();

// Choix de la feuille de style
$i = 0;
$d['optionTheme'] = "";
while ($i < count($liste_themes)) {
	$d['optionTheme'] .= "<option value='".$liste_themes[$i]."'";
	if (Settings::get('default_css') == $liste_themes[$i]) {
		$d['optionTheme'] .= ' selected="selected"';
	}
	$d['optionTheme'] .= ' >'.encode_message_utf8($liste_name_themes[$i]).'</option>';
	++$i;
}

// Choix de la langue
$i = 0;
$d['optionLangue'] = "";
while ($i < count($liste_language)) {
    $d['optionLangue'] .= "<option value='".$liste_language[$i]."'";
    if (Settings::get('default_language') == $liste_language[$i]) {
        $d['optionLangue'] .= ' selected="selected"';
    }
    $d['optionLangue'] .= ' >'.encode_message_utf8($trad['langue_' . $liste_language[$i]]).'</option>'.PHP_EOL;
    ++$i;
}

// Langues disponibles (pour la sélection multiple)
$langues_dispo_value = Settings::get('langues_dispo');
$d['langues_dispo_saved'] = array_filter(explode(';', $langues_dispo_value));

echo $twig->render($page.'.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings, 'liste_language' => $liste_language));

?>