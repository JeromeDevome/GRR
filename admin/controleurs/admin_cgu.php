<?php
/**
 * admin_config1.php
 * Interface permettant à l'administrateur la configuration de certains paramètres généraux
 * Ce script fait partie de l'application GRR.
 * Dernière modification : $Date: 2017-12-16 14:00$
 * @author    JeromeB
 * @copyright Copyright 2003-2018 Team DEVOME - JeromeB
 * @link      http://www.gnu.org/licenses/licenses.html
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 */

require_once("../include/pages.class.php");
$grr_script_name = "admin_cgu.php";

check_access(6, $back);

if (!Pages::load()) {
    die('Erreur chargement pages');
}

/* Enregistrement de la page */
if (isset($_POST['CGU'])) {
    if (!Pages::set("CGU", $_POST['CGU'])) {
        echo "Erreur lors de l'enregistrement de CGU !<br />";
        die();
    }
}
/**/
$msg = '';

// Si pas de problème, message de confirmation
if (isset($_POST['ok'])) {
    $_SESSION['displ_msg'] = 'yes';
    if ($msg == '') {
        $msg = get_vocab('message_records');
    }
    Header('Location: ?p=admin_cgu&msg='.$msg);
    exit();
}
if ((isset($_GET['msg'])) && isset($_SESSION['displ_msg']) && ($_SESSION['displ_msg'] == 'yes')) {
    $msg = $_GET['msg'];
} else {
    $msg = '';
}

affiche_pop_up($msg, 'admin');


get_vocab_admin('cgu_titre');
get_vocab_admin('cgu_grr');
get_vocab_admin('save');

$pages = Pages::getAll();

?>