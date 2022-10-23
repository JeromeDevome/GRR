<?php
/**
 * admin_config.php
 * Interface permettant à l'administrateur la configuration de certains paramètres généraux
 * Ce script fait partie de l'application GRR.
 * Dernière modification : $Date: 2022-06-19 15:45$
 * @author    Laurent Delineau & JeromeB &  Bouteillier Nicolas & Yan Naessens
 * @copyright Copyright 2003-2022 Team DEVOME - JeromeB
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

get_vocab_admin("admin_config1");
get_vocab_admin("admin_config2");
get_vocab_admin("admin_config3");
get_vocab_admin("admin_config4");
get_vocab_admin("admin_config5");
get_vocab_admin("admin_config6");

if (isset($_POST['title_home_page'])) {
    if (!Settings::set('title_home_page', $_POST['title_home_page'])) {
        echo "Erreur lors de l'enregistrement de title_home_page !<br />";
        die();
    }
}
if (isset($_POST['message_home_page'])) {
    if (!Settings::set('message_home_page', $_POST['message_home_page'])) {
        echo "Erreur lors de l'enregistrement de message_home_page !<br />";
        die();
    }
}
if (isset($_POST['company'])) {
    if (!Settings::set('company', $_POST['company'])) {
        echo "Erreur lors de l'enregistrement de company !<br />";
        die();
    }
}
if (isset($_POST['webmaster_name'])) {
    if (!Settings::set('webmaster_name', $_POST['webmaster_name'])) {
        echo "Erreur lors de l'enregistrement de webmaster_name !<br />";
        die();
    }
}
if (isset($_POST['webmaster_email'])) {
    if (!Settings::set('webmaster_email', $_POST['webmaster_email'])) {
        echo "Erreur lors de l'enregistrement de webmaster_email !<br />";
        die();
    }
}
if (isset($_POST['technical_support_email'])) {
    if (!Settings::set('technical_support_email', $_POST['technical_support_email'])) {
        echo "Erreur lors de l'enregistrement de technical_support_email !<br />";
        die();
    }
}
if (isset($_POST['message_accueil'])) {
    if (!Settings::set('message_accueil', $_POST['message_accueil'])) {
        echo "Erreur lors de l'enregistrement de message_accueil !<br />";
        die();
    }
}
if (isset($_POST['grr_url'])) {
    if (!Settings::set('grr_url', $_POST['grr_url'])) {
        echo "Erreur lors de l'enregistrement de grr_url !<br />";
        die();
    }
}
if (isset($_POST['ok'])) {
    if (isset($_POST['use_grr_url'])) {
        $use_grr_url = 'y';
    } else {
        $use_grr_url = 'n';
    }
    if (!Settings::set('use_grr_url', $use_grr_url)) {
        echo "Erreur lors de l'enregistrement de use_grr_url !<br />";
        die();
    }
}

$msg = '';
$dossier = '../personnalisation/'.$gcDossierImg.'/logos/';

if (isset($_POST['ok'])) {
    // Suppression du logo
    if (isset($_POST['sup_img'])) {
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
                $msg .= "Erreur lors de l'enregistrement du logo !\\n";
                $ok = 'no';
            }
        }
    }

    // Enregistrement du logo
	if (!empty($_FILES['doc_file']['tmp_name']))
	{
		list($nomImage, $resultImport) = Import::Image($dossier, 'logo');

		if($resultImport == ""){
			if (!Settings::set('logo', $nomImage)) {
				$msg .= "Erreur lors de l'enregistrement du logo !\\n";
				$ok = 'no';
			}
		} else {
			$msg .= $resultImport;
			$ok = 'no';
		}
	}
}
// nombre de calendriers
if (isset($_POST['nb_calendar'])) {
    settype($_POST['nb_calendar'], 'integer');
    if (!Settings::set('nb_calendar', $_POST['nb_calendar'])) {
        $msg .= "Erreur lors de l'enregistrement de nb_calendar !<br />";
    }
}

// Dates de réservations
if (isset($_POST['plageresa'])) {

	$demande_confirmation = 'no';
	$datesLimites	= explode(" - ", $_POST['plageresa']);
	$begin_bookings = strtotime(str_replace('/', '-', $datesLimites[0]));
	$end_bookings	= strtotime(str_replace('/', '-', $datesLimites[1]));

	if ($end_bookings < $begin_bookings) {
		$end_bookings = $begin_bookings;
	}
	$test_del1 = mysqli_num_rows(mysqli_query($GLOBALS['db_c'], 'SELECT * FROM '.TABLE_PREFIX."_entry WHERE (end_time < '$begin_bookings' OR start_time > '$end_bookings' )"));
	$test_del2 = mysqli_num_rows(mysqli_query($GLOBALS['db_c'], 'SELECT * FROM '.TABLE_PREFIX."_repeat WHERE (end_date < '$begin_bookings' OR start_time > '$end_bookings')"));
	if (($test_del1 != 0) || ($test_del2 != 0)) {
		$demande_confirmation = 'yes';
	} else {
		if (!Settings::set('begin_bookings', $begin_bookings)) {
			echo "Erreur lors de l'enregistrement de begin_bookings !<br />";
		}
		if (!Settings::set('end_bookings', $end_bookings)) {
			echo "Erreur lors de l'enregistrement de end_bookings !<br />";
		}
	}

    if ($demande_confirmation == 'yes') {
        header("Location: ?p=admin_change_date_bookings&end_bookings=$end_bookings&begin_bookings=$begin_bookings");
        die();
    }
}

if (!Settings::load()) {
    die('Erreur chargement settings');
}
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


// Config générale
//****************

get_vocab_admin('miscellaneous');
get_vocab_admin('title_home_page');
get_vocab_admin('message_home_page');
get_vocab_admin('company');
get_vocab_admin('grr_url');
get_vocab_admin('grr_url_explain');
get_vocab_admin('webmaster_name');
get_vocab_admin('webmaster_email');
get_vocab_admin('plusieurs_adresses_separees_points_virgules');
get_vocab_admin('technical_support_email');
get_vocab_admin('logo_msg');
get_vocab_admin('choisir_image_logo');
get_vocab_admin('select_fichier');
get_vocab_admin('supprimer_logo');
get_vocab_admin('affichage_calendriers');
get_vocab_admin('affichage_calendriers_msg');
get_vocab_admin('title_begin_end_bookings');
get_vocab_admin('begin_bookings_explain');
get_vocab_admin('message_perso');
get_vocab_admin('message_perso_explain');

get_vocab_admin('adapter_fichiers_langue');
get_vocab_admin('adapter_fichiers_langue_explain');

get_vocab_admin('save');
get_vocab_admin('message_records');

$nom_picture = $dossier.Settings::get('logo');

if ((Settings::get('logo') != '') && (@file_exists($nom_picture))) {
	$trad["dLogo"] = $nom_picture;
}

// Début et fin des réservations
$bday	= date('d', Settings::get('begin_bookings'));
$bmonth	= date('m', Settings::get('begin_bookings'));
$byear	= date('Y', Settings::get('begin_bookings'));

$eday	= date('d', Settings::get('end_bookings'));
$emonth	= date('m', Settings::get('end_bookings'));
$eyear	= date('Y', Settings::get('end_bookings'));

$trad["dBegin_bookings"] = $bday."/".$bmonth."/".$byear;
$trad["dEnd_bookings"] = $eday."/".$emonth."/".$eyear;

echo $twig->render($page.'.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings));
?>