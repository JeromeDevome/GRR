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
         if (!Settings::set("disable_login", $disable_login))
            $msg .= "Erreur lors de l'enregistrement de disable_login !<br />";

        // Restriction iP
        $ctrlIp = true;
        if($ip_autorise != "")
            $ctrlIp = SecuChaine::ValideNetworkIp($ip_autorise);

        if ($ctrlIp == false || !Settings::set("ip_autorise", $ip_autorise))
            $msg .= "Erreur lors de l'enregistrement de ip_autorise !<br />";

        // Heure de connexion
        if (!Settings::set("horaireconnexionde", $horaireconnexionde))
                $msg .= "Erreur lors de l'enregistrement de horaireconnexionde !<br />";

        if (!Settings::set("horaireconnexiona", $horaireconnexiona))
            $msg .= "Erreur lors de l'enregistrement de horaireconnexiona !<br />";

        // Durée de session
        if (isset($_POST['sessionMaxLength']))
        {
            settype($_POST['sessionMaxLength'], "integer");
            if ($_POST['sessionMaxLength'] < 1)
                $_POST['sessionMaxLength'] = 30;
            if (!Settings::set("sessionMaxLength", $_POST['sessionMaxLength']))
                $msg .= "Erreur lors de l'enregistrement de sessionMaxLength !<br />";
        }

        // URL de déconnexion
	    if (!Settings::set("url_disconnect", $_POST['url_disconnect']))
	    	$msg .= "Erreur lors de l'enregistrement de url_disconnect ! <br />";

    }

/** Apparence **/
    if ($submit == 1) {

        // Template de connexion
         if (!Settings::set("login_template", $_POST['login_template']))
            $msg .= "Erreur lors de l'enregistrement de login_template !<br />";

        // Titre de la page de connexion
        if (!Settings::set('title_home_page', $_POST['title_home_page']))
            echo "Erreur lors de l'enregistrement de title_home_page !<br />";

        // Message de la page de connexion
         if (!Settings::set('message_home_page', $_POST['message_home_page']))
            echo "Erreur lors de l'enregistrement de message_home_page !<br />";

        // Affichage du logo sur la page de connexion
         if (!Settings::set('login_logo', $_POST['login_logo']))
            echo "Erreur lors de l'enregistrement de login_logo !<br />";

         // Affichage du nom de l'établissement sur la page de connexion
        if (!Settings::set('login_nom', $_POST['login_nom']))
            $msg .= "Erreur lors de l'enregistrement de login_nom !<br />";

        // Enregistrement de l'image de connexion
        if (!empty($_FILES['doc_file']['tmp_name']))
        {
            list($nomImage, $resultImport) = Import::Image($dossier, 'image_connexion');

            if($resultImport == ""){
                if (!Settings::set('image_connexion', $nomImage)) {
                    $msg .= "Erreur lors de l'enregistrement du l\'image de connexion (1) !\\n";
                    $ok = 'no';
                }
            } else {
                $msg .= $resultImport;
                $ok = 'no';
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
            $msg .= "L\'image n\'a pas pu être supprimée : problème d\'écriture sur le répertoire. Veuillez signaler ce problème à l\'administrateur du serveur.\\n";
            $ok = 'no';
        } else {
            $nom_picture = $dossier.Settings::get('image_connexion');
            if (@file_exists($nom_picture)) {
                unlink($nom_picture);
            }
            if (!Settings::set('image_connexion', '')) {
                $msg .= "Erreur lors de l'enregistrement l\'image de connexion (2) !\\n";
                $ok = 'no';
            }
        }
    }

/** Demande de création de compte **/
    if ($submit == 1) {

        // Activer lea fonction de création de compte
        if (!Settings::set('fct_crea_cpt', $fct_crea_cpt))
            $msg .= "Erreur lors de l'enregistrement de fct_crea_cpt !<br />";

        // Identifiant par défaut
        if (!Settings::set('fct_crea_cpt_login', $fct_crea_cpt_login))
            $msg .= "Erreur lors de l'enregistrement de fct_crea_cpt_login !<br />";

        // Statut par défaut
        if (!Settings::set('fct_crea_cpt_statut', $fct_crea_cpt_statut))
            $msg .= "Erreur lors de l'enregistrement de fct_crea_cpt_statut !<br />";

        // Activer le captcha pour la création de compte
        if (!Settings::set("fct_crea_cpt_captcha", $fct_crea_cpt_captcha))
            $msg .= "Erreur lors de l'enregistrement de fct_crea_cpt_captcha !<br />";   

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

echo $twig->render($page.'.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings));

?>