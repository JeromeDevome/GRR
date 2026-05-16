<?php
/**
 * admin_mails.php
 * Interface permettant à l'administrateur la configuration de certains paramètres généraux
 * Ce script fait partie de l'application GRR.
 * Dernière modification : $Date: 2026-02-28 12:10$
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

require_once("../include/pages.class.php");
$grr_script_name = "admin_mails.php";

SecuAccess::CheckAccess(6, $back);

if (!Pages::load()) {
    die('Erreur chargement pages');
}
$msg = "";
/* Enregistrement de la page */
if(isset($_POST['ok']))
{
    foreach($liste_language as $langue)
    {
        VerifyModeDemo();

        if (isset($_POST['mails_test_'.$langue])) {
            if (!Pages::set("mails_test_".$langue, $_POST['mails_test_titre_'.$langue], $_POST['mails_test_'.$langue]))
                $msg .= "Erreur lors de l'enregistrement de mails_test_".$langue." !<br />";
        }

        if (isset($_POST['mails_resa_1_1_'.$langue])) {
            if (!Pages::set("mails_resa_1_1_".$langue, $_POST['mails_resa_1_1_titre_'.$langue], $_POST['mails_resa_1_1_'.$langue]))
                $msg .= "Erreur lors de l'enregistrement de mails_resa_1_1_".$langue." !<br />";
        }

        if (isset($_POST['mails_resa_2_1_'.$langue])) {
            if (!Pages::set("mails_resa_2_1_".$langue, $_POST['mails_resa_2_1_titre_'.$langue], $_POST['mails_resa_2_1_'.$langue]))
                $msg .= "Erreur lors de l'enregistrement de mails_resa_2_1_".$langue." !<br />";
        }

        if (isset($_POST['mails_resa_3_1_'.$langue])) {
            if (!Pages::set("mails_resa_3_1_".$langue, $_POST['mails_resa_3_1_titre_'.$langue], $_POST['mails_resa_3_1_'.$langue]))
                $msg .= "Erreur lors de l'enregistrement de mails_resa_3_1_".$langue." !<br />";
        }

        if (isset($_POST['mails_resa_4_1_'.$langue])) {
            if (!Pages::set("mails_resa_4_1_".$langue, $_POST['mails_resa_4_1_titre_'.$langue], $_POST['mails_resa_4_1_'.$langue]))
                $msg .= "Erreur lors de l'enregistrement de mails_resa_4_1_".$langue." !<br />";
        }

        if (isset($_POST['mails_resa_5_1_'.$langue])) {
            if (!Pages::set("mails_resa_5_1_".$langue, $_POST['mails_resa_5_1_titre_'.$langue], $_POST['mails_resa_5_1_'.$langue]))
                $msg .= "Erreur lors de l'enregistrement de mails_resa_5_1_".$langue." !<br />";
        }

        if (isset($_POST['mails_resa_6_1_'.$langue])) {
            if (!Pages::set("mails_resa_6_1_".$langue, $_POST['mails_resa_6_1_titre_'.$langue], $_POST['mails_resa_6_1_'.$langue]))
                $msg .= "Erreur lors de l'enregistrement de mails_resa_6_1_".$langue." !<br />";
        }

        if (isset($_POST['mails_resa_7_1_'.$langue])) {
            if (!Pages::set("mails_resa_7_1_".$langue, $_POST['mails_resa_7_1_titre_'.$langue], $_POST['mails_resa_7_1_'.$langue]))
                $msg .= "Erreur lors de l'enregistrement de mails_resa_7_1_".$langue." !<br />";
        }

        if (isset($_POST['mails_resa_2_1_'.$langue])) {
            if (!Pages::set("mails_resa_2_1_".$langue, $_POST['mails_resa_2_1_titre_'.$langue], $_POST['mails_resa_2_1_'.$langue]))
                $msg .= "Erreur lors de l'enregistrement de mails_resa_2_1_".$langue." !<br />";
        }

        if (isset($_POST['mails_resa_2_2_'.$langue])) {
            if (!Pages::set("mails_resa_2_2_".$langue, $_POST['mails_resa_2_2_titre_'.$langue], $_POST['mails_resa_2_2_'.$langue]))
                $msg .= "Erreur lors de l'enregistrement de mails_resa_2_2_".$langue." !<br />";
        }

        if (isset($_POST['mails_resa_2_3_'.$langue])) {
            if (!Pages::set("mails_resa_2_3_".$langue, $_POST['mails_resa_2_3_titre_'.$langue], $_POST['mails_resa_2_3_'.$langue]))
                $msg .= "Erreur lors de l'enregistrement de mails_resa_2_3_".$langue." !<br />";
        }

        if (isset($_POST['mails_resa_2_4_'.$langue])) {
            if (!Pages::set("mails_resa_2_4_".$langue, $_POST['mails_resa_2_4_titre_'.$langue], $_POST['mails_resa_2_4_'.$langue]))
                $msg .= "Erreur lors de l'enregistrement de mails_resa_2_4_".$langue." !<br />";
        }

        if (isset($_POST['mails_resa_2_5_'.$langue])) {
            if (!Pages::set("mails_resa_2_5_".$langue, $_POST['mails_resa_2_5_titre_'.$langue], $_POST['mails_resa_2_5_'.$langue]))
                $msg .= "Erreur lors de l'enregistrement de mails_resa_2_5_".$langue." !<br />";
        }

        if (isset($_POST['mails_resa_2_6_'.$langue])) {
            if (!Pages::set("mails_resa_2_6_".$langue, $_POST['mails_resa_2_6_titre_'.$langue], $_POST['mails_resa_2_6_'.$langue]))
                $msg .= "Erreur lors de l'enregistrement de mails_resa_2_6_".$langue." !<br />";
        }

        if (isset($_POST['mails_resa_2_7_'.$langue])) {
            if (!Pages::set("mails_resa_2_7_".$langue, $_POST['mails_resa_2_7_titre_'.$langue], $_POST['mails_resa_2_7_'.$langue]))
                $msg .= "Erreur lors de l'enregistrement de mails_resa_2_7_".$langue." !<br />";
        }

        if (isset($_POST['mails_resa_3_2_'.$langue])) {
            if (!Pages::set("mails_resa_3_2_".$langue, $_POST['mails_resa_3_2_titre_'.$langue], $_POST['mails_resa_3_2_'.$langue]))
                $msg .= "Erreur lors de l'enregistrement de mails_resa_3_2_".$langue." !<br />";
        }

        if (isset($_POST['mails_resa_3_3_'.$langue])) {
            if (!Pages::set("mails_resa_3_3_".$langue, $_POST['mails_resa_3_3_titre_'.$langue], $_POST['mails_resa_3_3_'.$langue]))
                $msg .= "Erreur lors de l'enregistrement de mails_resa_3_3_".$langue." !<br />";
        }

        if (isset($_POST['mails_resa_3_5_'.$langue])) {
            if (!Pages::set("mails_resa_3_5_".$langue, $_POST['mails_resa_3_5_titre_'.$langue], $_POST['mails_resa_3_5_'.$langue]))
                $msg .= "Erreur lors de l'enregistrement de mails_resa_3_5_".$langue." !<br />";
        }

        if (isset($_POST['mails_resa_3_7_'.$langue])) {
            if (!Pages::set("mails_resa_3_7_".$langue, $_POST['mails_resa_3_7_titre_'.$langue], $_POST['mails_resa_3_7_'.$langue]))
                $msg .= "Erreur lors de l'enregistrement de mails_resa_3_7_".$langue." !<br />";
        }

        if (isset($_POST['mails_demandecompte_'.$langue])) {
            if (!Pages::set("mails_demandecompte_".$langue, $_POST['mails_demandecompte_titre_'.$langue], $_POST['mails_demandecompte_'.$langue]))
                $msg .= "Erreur lors de l'enregistrement de mails_demandecompte_".$langue." !<br />";
        }

        if (isset($_POST['mails_demandecompte2_'.$langue])) {
            if (!Pages::set("mails_demandecompte2_".$langue, $_POST['mails_demandecompte2_titre_'.$langue], $_POST['mails_demandecompte2_'.$langue]))
                $msg .= "Erreur lors de l'enregistrement de mails_demandecompte2_".$langue." !<br />";
        }

        if (isset($_POST['mails_demandecompte3_'.$langue])) {
            if (!Pages::set("mails_demandecompte3_".$langue, $_POST['mails_demandecompte3_titre_'.$langue], $_POST['mails_demandecompte3_'.$langue]))
                $msg .= "Erreur lors de l'enregistrement de mails_demandecompte3_".$langue." !<br />";
        }

    }
}
/**/


// Si pas de problème, message de confirmation
if (isset($_POST['ok'])) {
    $_SESSION['displ_msg'] = 'yes';
    if ($msg == '') {
        $d['enregistrement'] = 1;
    } else{
        $d['enregistrement'] = $msg;
    }
}
if ((isset($_GET['msg'])) && isset($_SESSION['displ_msg']) && ($_SESSION['displ_msg'] == 'yes')) {
    $msg = $_GET['msg'];
} else {
    $msg = '';
}

$trad = $vocab;

$pages = Pages::getAll();

echo $twig->render($page.'.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings, 'pages' => $pages, 'liste_language' => $liste_language));

?>