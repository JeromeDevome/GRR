<?php
/**
 * admin_page_compte.php
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

// les variables attendues et leur type
$form_vars = array(
    'submit' => 'int',
    'allow_users_modify_profil' => 'int',
    'allow_users_modify_email' => 'int',
    'allow_users_modify_mdp' => 'int',
    'allow_users_modify_affichage' => 'int',
    'allow_users_modify_domaine' => 'int',
    'allow_users_modify_theme' => 'int',
    'allow_users_modify_langue' => 'int',
    'mail_user_obligatoire' => 'int',
    'pass_leng' => 'int',
    'pass_nb_min' => 'int',
    'pass_nb_maj' => 'int',
    'pass_nb_ch' => 'int',
    'pass_nb_sp' => 'int',
    'pass_simple' => 'int',
    'pass_change_conditions' => 'int'
);
// récupération des valeurs des variables passées en paramètres
foreach($form_vars as $var => $var_type)
    $$var = SecuChaine::GetFormVarSecure($var, $var_type);

/** Accès & Droits **/
    if ($submit == 1) {

        // Un gestionnaire d'utilisateurs ne peut pas Autoriser ou non la modification de ces paramètres
        if(SecuAccess::UserLevel(getUserName(), -1, 'user') !=  1){
            $settings_results[] = Settings::set2("allow_users_modify_profil", $allow_users_modify_profil);
            $settings_results[] = Settings::set2("allow_users_modify_email", $allow_users_modify_email);
            $settings_results[] = Settings::set2("allow_users_modify_mdp", $allow_users_modify_mdp);
            $settings_results[] = Settings::set2("allow_users_modify_affichage", $allow_users_modify_affichage);
            $settings_results[] = Settings::set2("allow_users_modify_domaine", $allow_users_modify_domaine);
            $settings_results[] = Settings::set2("allow_users_modify_theme", $allow_users_modify_theme);
            $settings_results[] = Settings::set2("allow_users_modify_langue", $allow_users_modify_langue);
            $settings_results[] = Settings::set2("mail_user_obligatoire", $mail_user_obligatoire);
        }
    }

/** Mot de passe **/
    if ($submit == 1) {

        if($pass_leng >= ($pass_nb_min + $pass_nb_maj + $pass_nb_ch + $pass_nb_sp))
        {
            if($pass_leng < 1)
                $pass_leng = 8;

                $settings_results[] = Settings::set2("pass_leng", $pass_leng);
                $settings_results[] = Settings::set2("pass_nb_min", $pass_nb_min);
                $settings_results[] = Settings::set2("pass_nb_maj", $pass_nb_maj);
                $settings_results[] = Settings::set2("pass_nb_ch", $pass_nb_ch);
                $settings_results[] = Settings::set2("pass_nb_sp", $pass_nb_sp);
        } else {
            $settings_results[] = array(4, $trad['pass_leng_error']);
        }

        $settings_results[] = Settings::set2("pass_simple", $pass_simple, true);
        $settings_results[] = Settings::set2("pass_change_conditions", $pass_change_conditions);

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