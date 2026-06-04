<?php
/**
 * admin_page_stat.php
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

$trad   = $vocab;
$msg    = '';

// les variables attendues et leur type
$form_vars = array(
    'submit' => 'int',
    'allow_search_level' => 'int',
    'default_report_days' => 'int'
);
// récupération des valeurs des variables passées en paramètres
foreach($form_vars as $var => $var_type)
    $$var = SecuChaine::GetFormVarSecure($var, $var_type);



/** Accès & Droits **/
    if ($submit == 1) {

        if (!Settings::set("allow_search_level", $allow_search_level))
            $msg .= "Erreur lors de l'enregistrement de allow_search_level !<br />";
    }

/** Apparence **/
    if ($submit == 1) {

        if (!Settings::set("default_report_days", $default_report_days))
            $msg .= "Erreur lors de l'enregistrement de default_report_days !<br />";
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