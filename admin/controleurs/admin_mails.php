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

check_access(6, $back);

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
                $msg = "Erreur lors de l'enregistrement de mails_test_".$langue." !<br />";
        }

        if (isset($_POST['mails_resacreation_'.$langue])) {
            if (!Pages::set("mails_resacreation_".$langue, $_POST['mails_resacreation_titre_'.$langue], $_POST['mails_resacreation_'.$langue]))
                $msg = "Erreur lors de l'enregistrement de mails_resacreation_".$langue." !<br />";
        }

        if (isset($_POST['mails_resacreation2_'.$langue])) {
            if (!Pages::set("mails_resacreation2_".$langue, $_POST['mails_resacreation2_titre_'.$langue], $_POST['mails_resacreation2_'.$langue]))
                $msg = "Erreur lors de l'enregistrement de mails_resacreation2_".$langue." !<br />";
        }

        if (isset($_POST['mails_resamodification_'.$langue])) {
            if (!Pages::set("mails_resamodification_".$langue, $_POST['mails_resamodification_titre_'.$langue], $_POST['mails_resamodification_'.$langue]))
                $msg = "Erreur lors de l'enregistrement de mails_resamodification_".$langue." !<br />";
        }

        if (isset($_POST['mails_resamodification2_'.$langue])) {
            if (!Pages::set("mails_resamodification2_".$langue, $_POST['mails_resamodification2_titre_'.$langue], $_POST['mails_resamodification2_'.$langue]))
                $msg = "Erreur lors de l'enregistrement de mails_resamodification2_".$langue." !<br />";
        }

        if (isset($_POST['mails_resasuppression_'.$langue])) {
            if (!Pages::set("mails_resasuppression_".$langue, $_POST['mails_resasuppression_titre_'.$langue], $_POST['mails_resasuppression_'.$langue]))
                $msg = "Erreur lors de l'enregistrement de mails_resasuppression_".$langue." !<br />";
        }

        if (isset($_POST['mails_resasuppression2_'.$langue])) {
            if (!Pages::set("mails_resasuppression2_".$langue, $_POST['mails_resasuppression2_titre_'.$langue], $_POST['mails_resasuppression2_'.$langue]))
                $msg = "Erreur lors de l'enregistrement de mails_resasuppression2_".$langue." !<br />";
        }

        if (isset($_POST['mails_resasuppression3_'.$langue])) {
            if (!Pages::set("mails_resasuppression3_".$langue, $_POST['mails_resasuppression3_titre_'.$langue], $_POST['mails_resasuppression3_'.$langue]))
                $msg = "Erreur lors de l'enregistrement de mails_resasuppression3_".$langue." !<br />";
        }

        if (isset($_POST['mails_resamoderation_'.$langue])) {
            if (!Pages::set("mails_resamoderation_".$langue, $_POST['mails_resamoderation_titre_'.$langue], $_POST['mails_resamoderation_'.$langue]))
                $msg = "Erreur lors de l'enregistrement de mails_resamoderation_".$langue." !<br />";
        }

        if (isset($_POST['mails_resamoderation2_'.$langue])) {
            if (!Pages::set("mails_resamoderation2_".$langue, $_POST['mails_resamoderation2_titre_'.$langue], $_POST['mails_resamoderation2_'.$langue]))
                $msg = "Erreur lors de l'enregistrement de mails_resamoderation2_".$langue." !<br />";
        }

        if (isset($_POST['mails_resamoderation3_'.$langue])) {
            if (!Pages::set("mails_resamoderation3_".$langue, $_POST['mails_resamoderation3_titre_'.$langue], $_POST['mails_resamoderation3_'.$langue]))
                $msg = "Erreur lors de l'enregistrement de mails_resamoderation3_".$langue." !<br />";
        }

        if (isset($_POST['mails_resamoderation4_'.$langue])) {
            if (!Pages::set("mails_resamoderation4_".$langue, $_POST['mails_resamoderation4_titre_'.$langue], $_POST['mails_resamoderation4_'.$langue]))
                $msg = "Erreur lors de l'enregistrement de mails_resamoderation4_".$langue." !<br />";
        }

        if (isset($_POST['mails_resamoderation5_'.$langue])) {
            if (!Pages::set("mails_resamoderation5_".$langue, $_POST['mails_resamoderation5_titre_'.$langue], $_POST['mails_resamoderation5_'.$langue]))
                $msg = "Erreur lors de l'enregistrement de mails_resamoderation5_".$langue." !<br />";
        }

        if (isset($_POST['mails_resamoderation6_'.$langue])) {
            if (!Pages::set("mails_resamoderation6_".$langue, $_POST['mails_resamoderation6_titre_'.$langue], $_POST['mails_resamoderation6_'.$langue]))
                $msg = "Erreur lors de l'enregistrement de mails_resamoderation6_".$langue." !<br />";
        }

        if (isset($_POST['mails_retardrestitution_'.$langue])) {
            if (!Pages::set("mails_retardrestitution_".$langue, $_POST['mails_retardrestitution_titre_'.$langue], $_POST['mails_retardrestitution_'.$langue]))
                $msg = "Erreur lors de l'enregistrement de mails_retardrestitution_".$langue." !<br />";
        }

        if (isset($_POST['mails_retardrestitution2_'.$langue])) {
            if (!Pages::set("mails_retardrestitution2_".$langue, $_POST['mails_retardrestitution2_titre_'.$langue], $_POST['mails_retardrestitution2_'.$langue]))
                $msg = "Erreur lors de l'enregistrement de mails_retardrestitution2_".$langue." !<br />";
        }

        if (isset($_POST['mails_demandecompte_'.$langue])) {
            if (!Pages::set("mails_demandecompte_".$langue, $_POST['mails_demandecompte_titre_'.$langue], $_POST['mails_demandecompte_'.$langue]))
                $msg = "Erreur lors de l'enregistrement de mails_demandecompte_".$langue." !<br />";
        }

        if (isset($_POST['mails_demandecompte2_'.$langue])) {
            if (!Pages::set("mails_demandecompte2_".$langue, $_POST['mails_demandecompte2_titre_'.$langue], $_POST['mails_demandecompte2_'.$langue]))
                $msg = "Erreur lors de l'enregistrement de mails_demandecompte2_".$langue." !<br />";
        }

        if (isset($_POST['mails_demandecompte3_'.$langue])) {
            if (!Pages::set("mails_demandecompte3_".$langue, $_POST['mails_demandecompte3_titre_'.$langue], $_POST['mails_demandecompte3_'.$langue]))
                $msg = "Erreur lors de l'enregistrement de mails_demandecompte3_".$langue." !<br />";
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

get_vocab_admin('langue_de-de');
get_vocab_admin('langue_en-gb');
get_vocab_admin('langue_es-es');
get_vocab_admin('langue_fr-fr');
get_vocab_admin('langue_it-it');

get_vocab_admin('mail_type_adm_test');
get_vocab_admin('mail_type_compte_demande');
get_vocab_admin('mail_type_resa_creation');
get_vocab_admin('mail_type_resa_moderation');
get_vocab_admin('mail_type_resa_modif');
get_vocab_admin('mail_type_resa_sup');
get_vocab_admin('mail_type_resa_retard');
get_vocab_admin('mail_type_resa_resultat');

get_vocab_admin('mail_desc_admin_test');
get_vocab_admin('mail_desc_dest_adm');
get_vocab_admin('mail_desc_dest_gestionnaire');
get_vocab_admin('mail_desc_dest_beneficiaire');
get_vocab_admin('mail_desc_dest_supauto');
get_vocab_admin('mail_desc_compte_creation');
get_vocab_admin('mail_desc_compte_decision1');
get_vocab_admin('mail_desc_compte_decision2');

get_vocab_admin('save');
get_vocab_admin('message_records');

$pages = Pages::getAll();

echo $twig->render($page.'.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings, 'pages' => $pages, 'liste_language' => $liste_language));

?>