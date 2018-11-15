<?php
/**
 * admin_config.php
 * Interface permettant à l'administrateur la configuration de certains paramètres généraux
 * Ce script fait partie de l'application GRR.
 * Dernière modification : $Date: 2018-03-30 16:00$
 * @author    Laurent Delineau & JeromeB &  Bouteillier Nicolas & Yan Naessens
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
if (isset($_POST['ok'])) {
    // Suppression du logo
    if (isset($_POST['sup_img'])) {
        $dest = '../images/';
        $ok1 = false;
        if ($f = @fopen("$dest/.test", 'w')) {
            @fputs($f, '<'.'?php $ok1 = true; ?'.'>');
            @fclose($f);
            include "$dest/.test";
        }
        if (!$ok1) {
            $msg .= "L\'image n\'a pas pu être supprimée : problème d\'écriture sur le répertoire. Veuillez signaler ce problème à l\'administrateur du serveur.\\n";
            $ok = 'no';
        } else {
            $nom_picture = '../images/'.Settings::get('logo');
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
    $doc_file = isset($_FILES['doc_file']) ? $_FILES['doc_file'] : null;
    /* Test premier, juste pour bloquer les double extensions */
    if (count(explode('.', $doc_file['name'])) > 2) {

        $msg .= "L\'image n\'a pas pu être enregistrée : les seules extentions autorisées sont gif, png et jpg.\\n";
        $ok = 'no';

    } elseif (preg_match("`\.([^.]+)$`", $doc_file['name'], $match)) {
        /* normalement, si on arrive ici l'image n'a qu'une extension */

        $ext = strtolower($match[1]);
        if ($ext != 'jpg' && $ext != 'png' && $ext != 'gif') {
            $msg .= "L\'image n\'a pas pu être enregistrée : les seules extentions autorisées sont gif, png et jpg.\\n";
            $ok = 'no';
        } else {
            /* deuxième test passé, l'extension est autorisée */

            /* je fais le 3ème test avec fileinfo */
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $fileType = finfo_file($finfo, $doc_file['tmp_name']);

            /* 4ème test avec gd pour valider que c'est bien une image malgré tout - nécessaire ou parano ? */
            switch($fileType) {
                case "image/gif":
                    /* recreate l'image, supprime les data exif */
                    $logoRecreated = @imagecreatefromgif ( $doc_file['tmp_name'] );
                    /* fix pour la transparence */
                    imageAlphaBlending($logoRecreated, true);
                    imageSaveAlpha($logoRecreated, true);
                    $extSafe = "gif";
                    break;
                case "image/jpeg":
                    $logoRecreated = @imagecreatefromjpeg ( $doc_file['tmp_name'] );
                    $extSafe = "jpg";
                    break;
                case "image/png":
                    $logoRecreated = @imagecreatefrompng ( $doc_file['tmp_name'] );
                    /* fix pour la transparence */
                    imageAlphaBlending($logoRecreated, true);
                    imageSaveAlpha($logoRecreated, true);
                    $extSafe = "png";
                    break;
                default:
                    $msg .= "L\'image n\'a pas pu être enregistrée : type mime incompatible.\\n";
                    $ok = 'no';
                    $extSafe = false;
                    break;
            }
            if (!$logoRecreated || $extSafe === false) {
                /* la fonction imagecreate a échoué, donc l'image est corrompue ou craftée */
                $msg .= "L\'image n\'a pas pu être enregistrée : fichier corrompu.\\n";
                $ok = 'no';
            } else {
                /* j'ai une image valide, sans data exif, avec un bon type mime */

                /* je test si la destination est writable */
                $dest = '../images/';
                $randName = md5(uniqid(rand(), true));

                $pictureName = $randName.'.'.$extSafe;
                $picturePath = $dest.$pictureName;

                if (is_writable($dest)) {
                    /* je copie le logo pour valider avec la fonction move_uploaded_file */
                    $moveUploadReturn = move_uploaded_file($doc_file['tmp_name'], $picturePath);
                    if (!$moveUploadReturn) {
                        $msg .= "L\'image n\'a pas pu être enregistrée : problème de transfert. Le fichier n\'a pas pu être transféré sur le répertoire IMAGES. Veuillez signaler ce problème à l\'administrateur du serveur.\\n";
                        $ok = 'no';
                    } else {
                        /* si c'est bon, je supprime l'image et je la remplace par l'image create avec gd */
                        $unlinkReturn = unlink($picturePath);
                        if (!$unlinkReturn) {
                            $msg .= "Erreur lors de l'enregistrement du logo ! (suppression du fichier temporaire)\\n";
                            $ok = 'no';
                        } else {
                            /* j'ai supprimé le logo copié, je  vais enregistrer l'image à la place */
                            switch($extSafe) {
                                case "gif":
                                    $retourSaveImage = imagegif($logoRecreated, $picturePath);
                                    break;
                                case "jpg":
                                    $retourSaveImage = imagejpeg($logoRecreated, $picturePath);
                                    break;
                                case "png":
                                    $retourSaveImage = imagepng($logoRecreated, $picturePath);
                                    break;
                            }
                            $retourDestroy = imagedestroy($logoRecreated);
                            if (!$retourSaveImage || !$retourDestroy) {
                                /* gérer un warning juste ? */
                                $msg .= " (Erreur de imagedestroy)\\n";
                            }
                            if (!Settings::set('logo', $pictureName)) {
                                $msg .= "Erreur lors de l'enregistrement du logo !\\n";
                                $ok = 'no';
                            }
                        }
                    }

                } else {
                    $msg .= "L\'image n\'a pas pu être enregistrée : problème d\'écriture sur le répertoire \"images\". Veuillez signaler ce problème à l\'administrateur du serveur.\\n";
                    $ok = 'no';
                }
            }
        }
    } elseif ($doc_file['name'] != '') {
        $msg .= "L\'image n\'a pas pu être enregistrée : le fichier image sélectionné n'est pas valide !\\n";
        $ok = 'no';
    }
}
// nombre de calendriers
if (isset($_POST['nb_calendar'])) {
    settype($_POST['nb_calendar'], 'integer');
    if (!Settings::set('nb_calendar', $_POST['nb_calendar'])) {
        echo "Erreur lors de l'enregistrement de nb_calendar !<br />";
        die();
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
        $msg = get_vocab('message_records');
    }
    Header('Location: ?p=admin_config&msg='.$msg);
    exit();
}
if ((isset($_GET['msg'])) && isset($_SESSION['displ_msg']) && ($_SESSION['displ_msg'] == 'yes')) {
    $msg = $_GET['msg'];
} else {
    $msg = '';
}

affiche_pop_up($msg, 'admin');

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

$nom_picture = '../images/'.Settings::get('logo');

if ((Settings::get('logo') != '') && (@file_exists($nom_picture))) {
	$trad["dLogo"] = $nom_picture;
}

// Début et fin des réservations
$bday	= strftime('%d', Settings::get('begin_bookings'));
$bmonth	= strftime('%m', Settings::get('begin_bookings'));
$byear	= strftime('%Y', Settings::get('begin_bookings'));

$eday	= strftime('%d', Settings::get('end_bookings'));
$emonth	= strftime('%m', Settings::get('end_bookings'));
$eyear	= strftime('%Y', Settings::get('end_bookings'));

$trad["dBegin_bookings"] = $bday."/".$bmonth."/".$byear;
$trad["dEnd_bookings"] = $eday."/".$emonth."/".$eyear;
?>