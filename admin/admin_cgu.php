<?php
/**
 * admin_config1.php
 * Interface permettant à l'administrateur de renseigner la page des conditions générales d'utilisation
 * Ce script fait partie de l'application GRR.
 * Dernière modification : $Date: 2021-03-13 12:04$
 * @author    JeromeB & Yan Naessens
 * @copyright Copyright 2003-2021 Team DEVOME - JeromeB
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

include "../include/admin.inc.php";
require_once("../include/pages.class.php");

$back = (isset($_SERVER['HTTP_REFERER']))? htmlspecialchars_decode($_SERVER['HTTP_REFERER'], ENT_QUOTES) : "./admin_accueil.php" ;
$_SESSION['chemin_retour'] = "admin_cgu.php";
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

$msg = '';
// Si pas de problème, message de confirmation
if (isset($_POST['ok'])) {
    $_SESSION['displ_msg'] = 'yes';
    if ($msg == '') {
        $msg = get_vocab('message_records');
    }
    Header('Location: '.'admin_cgu.php?msg='.$msg);
    exit();
}
if ((isset($_GET['msg'])) && isset($_SESSION['displ_msg']) && ($_SESSION['displ_msg'] == 'yes')) {
    $msg = clean_input($_GET['msg']);
} 
else {
    $msg = '';
}
// début de la page
start_page_w_header('', '', '', $type = 'with_session');
affiche_pop_up($msg, 'admin');
// Affichage de la colonne de gauche
include 'admin_col_gauche2.php';
// colonne de droite
echo "<div class='col-md-9 col-sm-8 col-xs-12'>";
echo '<h2>'.get_vocab('cgu_titre').'</h2>'.PHP_EOL;
echo get_vocab('cgu_grr');
echo '<form action="./admin_cgu.php" id="nom_formulaire" method="post" >'.PHP_EOL;
if (Settings::get('use_fckeditor') == 1) {
    echo '<script type="text/javascript" src="../js/ckeditor/ckeditor.js"></script>'.PHP_EOL;
}
if (Pages::get('use_fckeditor') != 1) {
    echo ' '.get_vocab('description complete2');
}
if (Settings::get('use_fckeditor') == 1) {
    echo '<textarea class="ckeditor" id="editor1" name="CGU" rows="20" cols="120">'.PHP_EOL;
    echo htmlspecialchars(Pages::get('CGU'));
    echo "</textarea>\n";
} 
else {
    echo "\n<textarea name=\"CGU\" rows=\"8\" cols=\"120\">".htmlspecialchars(Pages::get('CGU')).'</textarea>'.PHP_EOL;
}
echo '<div id="fixe" style="text-align:center;">'.PHP_EOL;
echo '<input class="btn btn-primary" type="submit" name="ok" value="'.get_vocab('save').'" style="font-variant: small-caps;"/>'.PHP_EOL;
echo '</div>';
echo '</form>';
// fin de l'affichage de la colonne de droite
echo '</div>';
end_page();
?>