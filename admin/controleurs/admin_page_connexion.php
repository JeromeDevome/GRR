<?php
/**
 * admin_page_connexion.php
 * Interface permettant à l'administrateur la configuration de certains paramètres généraux
 * Ce script fait partie de l'application GRR.
 * Dernière modification : $Date: 2026-05-30 14:20$
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

$grr_script_name = 'admin_page_connexion.php';

// Accès à la page
SecuAccess::CheckAccess(6, $back);

include('../include/import.class.php');

$trad       = $vocab;
$msg        = '';
$dossier    = '../personnalisation/'.$gcDossierImg.'/logos/';

$d['dossierLogo'] = $dossier;

// les variables attendues et leur type
$form_vars = array(
    'submit' => 'int',
    'disable_login' => 'int',
    'ip_autorise'   => 'string',
    'horaireconnexionde' => 'string',
    'horaireconnexiona' => 'string',
    'sessionMaxLength' => 'int',
    'url_disconnect' => 'string',
    'login_template' => 'int',
    'title_home_page' => 'string',
    'message_home_page' => 'string',
    'login_logo' => 'int',
    'login_nom' => 'int',
    'sup_img' => 'int',
    'fct_crea_cpt' => 'int',
    'fct_crea_cpt_login' => 'int',
    'fct_crea_cpt_statut' => 'int',
    'fct_crea_cpt_captcha' => 'int'
);
// récupération des valeurs des variables passées en paramètres
foreach($form_vars as $var => $var_type)
    $$var = SecuChaine::GetFormVarSecure($var, $var_type);

/** Accès & Droits **/
    if ($submit == 1) {

        // Désactivation de la connexion
        $settings_results[] = Settings::set2("disable_login", $disable_login);

        // Restriction iP
        $ctrlIp = true;
        if($ip_autorise != "")
            $ctrlIp = SecuChaine::ValideNetworkIp($ip_autorise);

        if ($ctrlIp == false)
            $settings_results[] = array(4, "Erreur lors de l'enregistrement de ip_autorise : format incorrect !");
        else
            $settings_results[] = Settings::set2("ip_autorise", $ip_autorise, true);

        // Heure de connexion
        $settings_results[] = Settings::set2("horaireconnexionde", $horaireconnexionde, true);
        $settings_results[] = Settings::set2("horaireconnexiona", $horaireconnexiona, true);

        // Durée de session
        if (isset($_POST['sessionMaxLength']))
        {
            settype($_POST['sessionMaxLength'], "integer");
            if ($_POST['sessionMaxLength'] < 1)
                $_POST['sessionMaxLength'] = 30;
            $settings_results[] = Settings::set2("sessionMaxLength", $sessionMaxLength, true);
        }

        // URL de déconnexion
        $settings_results[] = Settings::set2("url_disconnect", $url_disconnect, true);

    }

/** Apparence **/
    if ($submit == 1) {

        // Template de connexion
        $settings_results[] = Settings::set2("login_template", $login_template);

        // Titre de la page de connexion
        $settings_results[] = Settings::set2("title_home_page", $title_home_page);

        // Message de la page de connexion
        $settings_results[] = Settings::set2("message_home_page", $message_home_page);

        // Affichage du logo sur la page de connexion
        $settings_results[] = Settings::set2("login_logo", $login_logo);

        // Affichage du nom de l'établissement sur la page de connexion
        $settings_results[] = Settings::set2("login_nom", $login_nom);

        // Enregistrement de l'image de connexion
        if (!empty($_FILES['doc_file']['tmp_name']))
        {
            list($nomImage, $resultImport) = Import::Image($dossier, 'image_connexion');

            if($resultImport == ""){
                $settings_results[] = Settings::set2("image_connexion", $nomImage);
            } else {
                $settings_results[]= array(3, "L'image n'a pas pu être importée : $resultImport");
            }
        }
    }

    // Suppression de l'image de connexion
    if (isset($sup_img) && Settings::get('image_connexion') != '') {
        $ok1 = false;
        if ($f = @fopen("$dossier/.test", 'w')) {
            @fputs($f, '<'.'?php $ok1 = true; ?'.'>');
            @fclose($f);
            include "$dossier/.test";
        }
        if (!$ok1) {
            $settings_results[] = array(3, "L'image n'a pas pu être supprimée : problème d'écriture sur le répertoire. Veuillez signaler ce problème à l'administrateur du serveur.");
        } else {
            $nom_picture = $dossier.Settings::get('image_connexion');
            if (@file_exists($nom_picture)) {
                unlink($nom_picture);
            }
            $settings_results[] = Settings::set2("image_connexion", '');
        }
    }

/** Demande de création de compte **/
    if ($submit == 1) {

        // Activer lea fonction de création de compte
        $settings_results[] = Settings::set2("fct_crea_cpt", $fct_crea_cpt);

        // Identifiant par défaut
        $settings_results[] = Settings::set2("fct_crea_cpt_login", $fct_crea_cpt_login);

        // Statut par défaut
        $settings_results[] = Settings::set2("fct_crea_cpt_statut", $fct_crea_cpt_statut);

        // Activer le captcha pour la création de compte
        $settings_results[] = Settings::set2("fct_crea_cpt_captcha", $fct_crea_cpt_captcha);
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

echo $twig->render($page.'.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings));

?>