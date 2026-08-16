<?php
/**
 * admin_cgu.php
 * Interface permettant à l'administrateur la configuration de certains paramètres généraux
 * Ce script fait partie de l'application GRR.
 * Dernière modification : $Date: 2026-08-26 17:00$
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


$grr_script_name = "admin_cgu.php";

require_once("../include/pages.class.php");

$msg = "";

// Accès à la page
SecuAccess::CheckAccess(6, $back);

if (!Pages::load()) {
    die('Erreur chargement pages');
}

// les variables attendues et leur type
$form_vars = array(
    'submit' => 'int',
    'titre' => 'string',
    'CGU' => ''
);
// récupération des valeurs des variables passées en paramètres
foreach($form_vars as $var => $var_type)
    $$var = SecuChaine::GetFormVarSecure($var, $var_type);

/** Enregistrement de la page **/
if (isset($CGU)) {
	VerifyModeDemo();
    if (!Pages::set("cgu", $titre, $CGU))
        $msg = "Erreur lors de l'enregistrement de CGU !<br />";
}
/**/


// Si pas de problème, message de confirmation
if (isset($_POST['ok'])) {
    if ($msg == '') {
        $d['enregistrement'] = 1;
    } else{
        $d['enregistrement'] = $msg;
    }
}

$pages = Pages::getAll();

echo $twig->render($page.'.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings, 'pages' => $pages));

?>