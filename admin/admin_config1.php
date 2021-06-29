<?php
/**
 * admin_config1.php
 * Interface permettant à l'administrateur la configuration de certains paramètres généraux
 * Ce script fait partie de l'application GRR.
 * Dernière modification : $Date: 2021-03-13 12:01$
 * @author    Laurent Delineau & JeromeB &  Bouteillier Nicolas & Yan Naessens
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
// cette page reste à scinder comme en version 4
$grr_script_name = "admin_config1.php";

include "../include/admin.inc.php";

$back = (isset($_SERVER['HTTP_REFERER']))? htmlspecialchars_decode($_SERVER['HTTP_REFERER'], ENT_QUOTES) : "./admin_accueil.php" ;
$_SESSION['chemin_retour'] = "admin_accueil.php";
$day   = date("d");
$month = date("m");
$year  = date("Y");
check_access(6, $back);
// vérifications 
if (isset($_POST['title_home_page'])) {
    if (!Settings::set('title_home_page', $_POST['title_home_page'])) {
        echo $vocab['save_err']." title_home_page !<br />";
        die();
    }
}
if (isset($_POST['show_courrier'])) {
    if (!Settings::set('show_courrier', $_POST['show_courrier'])) {
        echo $vocab['save_err']." show_courrier !<br />";
        die();
    }
}
if (isset($_POST['show_holidays'])) {
    if (!Settings::set('show_holidays', $_POST['show_holidays'])) {
        echo $vocab['save_err']." show_holidays !<br />";
        die();
    }
}
if (isset($_POST['holidays_zone'])) {
    if (!Settings::set('holidays_zone', $_POST['holidays_zone'])) {
        echo $vocab['save_err']." holidays_zone !<br />";
        die();
    }
}
if (isset($_POST['message_home_page'])) {
    if (!Settings::set('message_home_page', $_POST['message_home_page'])) {
        echo $vocab['save_err']." message_home_page !<br />";
        die();
    }
}
if (isset($_POST['company'])) {
    if (!Settings::set('company', $_POST['company'])) {
        echo $vocab['save_err']." company !<br />";
        die();
    }
}
if (isset($_POST['webmaster_name'])) {
    if (!Settings::set('webmaster_name', $_POST['webmaster_name'])) {
        echo $vocab['save_err']." webmaster_name !<br />";
        die();
    }
}
if (isset($_POST['webmaster_email'])) {
    if (!Settings::set('webmaster_email', $_POST['webmaster_email'])) {
        echo $vocab['save_err']." webmaster_email !<br />";
        die();
    }
}
if (isset($_POST['technical_support_email'])) {
    if (!Settings::set('technical_support_email', $_POST['technical_support_email'])) {
        echo $vocab['save_err']." technical_support_email !<br />";
        die();
    }
}
if (isset($_POST['message_accueil'])) {
    if (!Settings::set('message_accueil', $_POST['message_accueil'])) {
        echo $vocab['save_err']." message_accueil !<br />";
        die();
    }
}
if (isset($_POST['grr_url'])) {
    if (!Settings::set('grr_url', $_POST['grr_url'])) {
        echo $vocab['save_err']." grr_url !<br />";
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
        echo $vocab['save_err']." use_grr_url !<br />";
        die();
    }
}
// Style/thème
if (isset($_POST['default_css'])) {
    if (!Settings::set('default_css', $_POST['default_css'])) {
        echo $vocab['save_err']." default_css !<br />";
        die();
    }
}
// langage
if (isset($_POST['default_language'])) {
    if (!Settings::set('default_language', $_POST['default_language'])) {
        echo $vocab['save_err']." default_language !<br />";
        die();
    }
    unset($_SESSION['default_language']);
}
// Type d'affichage des listes des domaines et des ressources
if (isset($_POST['area_list_format'])) {
    if (!Settings::set('area_list_format', $_POST['area_list_format'])) {
        echo $vocab['save_err']." area_list_format !<br />";
        die();
    }
}
// site par défaut
if (isset($_POST['id_site'])) {
    if (!Settings::set('default_site', $_POST['id_site'])) {
        echo $vocab['save_err']." default_site !<br />";
        die();
    }
}
// domaine par défaut
if (isset($_POST['id_area'])) {
    if (!Settings::set('default_area', $_POST['id_area'])) {
        echo $vocab['save_err']." default_area !<br />";
        die();
    }
}
// ressource par défaut
if (isset($_POST['id_room'])) {
    if (!Settings::set('default_room', $_POST['id_room'])) {
        echo $vocab['save_err']." default_room !<br />";
        die();
    }
}
// Affichage de l'adresse email
if (isset($_POST['display_level_email'])) {
    if (!Settings::set('display_level_email', $_POST['display_level_email'])) {
        echo $vocab['save_err']." display_level_email !<br />";
        die();
    }
}
/*-----MAJ Loïs THOMAS  --> Affichage de la page view_entry pour les réservations  -----*/
if (isset($_POST['display_level_view_entry'])) {
    if (!Settings::set('display_level_view_entry', $_POST['display_level_view_entry'])) {
        echo $vocab['save_err']." display_level_view_entry !<br />";
        die();
    }
}
// display_info_bulle
if (isset($_POST['display_info_bulle'])) {
    if (!Settings::set('display_info_bulle', $_POST['display_info_bulle'])) {
        echo $vocab['save_err']." display_info_bulle !<br />";
        die();
    }
}
// menu_gauche
if (isset($_POST['menu_gauche'])) {
    if (!Settings::set('menu_gauche', $_POST['menu_gauche'])) {
        echo $vocab['save_err']." menu_gauche !<br />";
        die();
    }
}
// display_type
if (isset($_POST['display_type'])) {
    if (!Settings::set('display_type', $_POST['display_type'])) {
        echo $vocab["save_err"]." display_type !<br />";
        die();
    }
}
// display_beneficiaire
if (isset($_POST['display_beneficiaire'])) {
    if (!Settings::set('display_beneficiaire', $_POST['display_beneficiaire'])) {
        echo $vocab["save_err"]." display_beneficiaire !<br />";
        die();
    }
}
// display_full_description
if (isset($_POST['display_full_description'])) {
    if (!Settings::set('display_full_description', $_POST['display_full_description'])) {
        echo $vocab['save_err']." display_full_description !<br />";
        die();
    }
}
// display_short_description
if (isset($_POST['display_short_description'])) {
    if (!Settings::set('display_short_description', $_POST['display_short_description'])) {
        echo $vocab['save_err']." display_short_description !<br />";
        die();
    }
}
// remplissage de la description brève
if (isset($_POST['remplissage_description_breve'])) {
    if (!Settings::set('remplissage_description_breve', $_POST['remplissage_description_breve'])) {
        echo $vocab['save_err']." remplissage_description_breve !<br />";
        die();
    }
}
// remplissage de la description complète
if (isset($_POST['remplissage_description_complete'])) {
    if (!Settings::set('remplissage_description_complete', $_POST['remplissage_description_complete'])) {
        echo $vocab['save_err']." remplissage_description_complete !<br />";
        die();
    }
}
// pview_new_windows
if (isset($_POST['pview_new_windows'])) {
    if (!Settings::set('pview_new_windows', $_POST['pview_new_windows'])) {
        echo $vocab['save_err']." pview_new_windows !<br />";
        die();
    }
}
/*-----MAJ Loïs THOMAS  -->Affichage ou non de la legende -----*/
if (isset($_POST['legend'])) {
    if (!Settings::set('legend', $_POST['legend'])) {
        echo $vocab['save_err']." legend !<br />";
        die();
    }
}
// Affichage imprimante
if (isset($_POST['imprimante'])) {
    if (!Settings::set('imprimante', $_POST['imprimante'])) {
        echo $vocab['save_err']." imprimante !<br />";
        die();
    }
}
// Affichage pdf 
if (isset($_POST['pdf'])) {
    if (!Settings::set('pdf', $_POST['pdf'])) {
        echo $vocab['save_err']." affichage pdf !<br />";
        die();
    }
}
// Affichage type 
if (isset($_POST['type'])) {
    if (!Settings::set('type', $_POST['type'])) {
        echo $vocab['save_err']." affichage type !<br />";
        die();
    }
}
// Option peridodicite
if (isset($_POST['periodicite'])) {
    if (!Settings::set('periodicite', $_POST['periodicite'])) {
        echo $vocab['save_err']." periodicite !<br />";
        die();
    }
}
/*-----MAJ David VOUE 22/01/2014-->Affichage ou non du formulaire de contact et adresse mail du destinataire -----*/
if (isset($_POST['mail_destinataire'])) {
    if (!Settings::set('mail_destinataire', $_POST['mail_destinataire'])) {
        echo $vocab['save_err']." mail_destinataire !<br />";
        die();
    }
}

if (isset($_POST['allow_pdf'])) {
    if (!Settings::set('allow_pdf', $_POST['allow_pdf'])) {
        echo $vocab['save_err']." allow_pdf !<br />";
        die();
    }
}

if (isset($_POST['mail_etat_destinataire'])) {
    if (!Settings::set('mail_etat_destinataire', $_POST['mail_etat_destinataire'])) {
        echo $vocab['save_err']." mail_etat_destinataire !<br />";
        die();
    }
}
// limitation des réservations par formulaire 
if (isset($_POST['nb_max_resa_form'])){
    if (!Settings::set('nb_max_resa_form', clean_input($_POST['nb_max_resa_form']))) {
        echo $vocab['save_err']." nb_max_resa_form !<br />";
        die();
    }
}
// gestion_lien_aide
if (isset($_POST['gestion_lien_aide'])) {
    if (($_POST['gestion_lien_aide'] == 'perso') && (trim($_POST['lien_aide']) == '')) {
        $_POST['gestion_lien_aide'] = 'ext';
    } elseif ($_POST['gestion_lien_aide'] != 'perso') {
        $_POST['lien_aide'] = '';
    }
    if (!Settings::set('lien_aide', $_POST['lien_aide'])) {
        echo $vocab['save_err']." lien_aide !<br />";
        die();
    }
    if (!Settings::set('gestion_lien_aide', $_POST['gestion_lien_aide'])) {
        echo $vocab['save_err']." gestion_lien_aide !<br />";
        die();
    }
}
# Lors de l'édition d'un rapport, valeur par défaut en nombre de jours
# de l'intervalle de temps entre la date de début du rapport et la date de fin du rapport.
if (isset($_POST['default_report_days'])) {
    settype($_POST['default_report_days'], 'integer');
    if ($_POST['default_report_days'] <= 0) {
        $_POST['default_report_days'] = 0;
    }
    if (!Settings::set('default_report_days', $_POST['default_report_days'])) {
        echo $vocab['save_err']." default_report_days !<br />";
        die();
    }
}
if (isset($_POST['longueur_liste_ressources_max'])) {
    settype($_POST['longueur_liste_ressources_max'], 'integer');
    if ($_POST['longueur_liste_ressources_max'] <= 0) {
        $_POST['longueur_liste_ressources_max'] = 1;
    }
    if (!Settings::set('longueur_liste_ressources_max', $_POST['longueur_liste_ressources_max'])) {
        echo $vocab['save_err']." longueur_liste_ressources_max !<br />";
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
            $msg .= get_vocab('errSuppLogo')."\\n";
            $ok = 'no';
        } else {
            $nom_picture = '../images/'.Settings::get('logo');
            if (@file_exists($nom_picture)) {
                unlink($nom_picture);
            }
            if (!Settings::set('logo', '')) {
                $msg .= get_vocab('errRecLogo')."\\n";
                $ok = 'no';
            }
        }
    }
    // Enregistrement du logo
    $doc_file = isset($_FILES['doc_file']) ? $_FILES['doc_file'] : null;
    /* Test premier, juste pour bloquer les double extensions */
    if (count(explode('.', $doc_file['name'])) > 2) {

        $msg .= get_vocab('errTypeLogo')."\\n";
        $ok = 'no';

    } elseif (preg_match("`\.([^.]+)$`", $doc_file['name'], $match)) {
        /* normalement, si on arrive ici l'image n'a qu'une extension */

        $ext = strtolower($match[1]);
        if ($ext != 'jpg' && $ext != 'png' && $ext != 'gif') {
            $msg .= get_vocab('errTypeLogo')."\\n";
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
                    $msg .= get_vocab('errRecMimeLogo')."\\n";
                    $ok = 'no';
                    $extSafe = false;
                    break;
            }
            if (!$logoRecreated || $extSafe === false) {
                /* la fonction imagecreate a échoué, donc l'image est corrompue ou craftée */
                $msg .= get_vocab('errCorruptedFile')."\\n";
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
                        $msg .= get_vocab('errImgTransfer')."\\n";
                        $ok = 'no';
                    } else {
                        /* si c'est bon, je supprime l'image et je la remplace par l'image create avec gd */
                        $unlinkReturn = unlink($picturePath);
                        if (!$unlinkReturn) {
                            $msg .= get_vocab('errTempLogo')."\\n";
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
                                $msg .= get_vocab('errRecLogo')."\\n";
                                $ok = 'no';
                            }
                        }
                    }

                } else {
                    $msg .= get_vocab('errImgTransfer')."\\n";
                    $ok = 'no';
                }
            }
        }
    } elseif ($doc_file['name'] != '') {
        $msg .= get_vocab('errInvalidFile')."\\n";
        $ok = 'no';
    }
}
// nombre de calendriers
if (isset($_POST['nb_calendar'])) {
    settype($_POST['nb_calendar'], 'integer');
    if (!Settings::set('nb_calendar', $_POST['nb_calendar'])) {
        echo get_vocab('save_err')." nb_calendar !<br />";
        die();
    }
}
$demande_confirmation = 'no';
if (isset($_POST['begin_day']) && isset($_POST['begin_month']) && isset($_POST['begin_year'])) {
    $begin_day = clean_input($_POST['begin_day']);
    $begin_month = clean_input($_POST['begin_month']);
    $begin_year = clean_input($_POST['begin_year']);
    while (!checkdate($begin_month, $begin_day, $begin_year)) {
        $begin_day--;
    }
    $begin_bookings = mktime(0, 0, 0, $begin_month, $begin_day, $begin_year);
    $test_del1 = mysqli_num_rows(mysqli_query($GLOBALS['db_c'], 'SELECT * FROM '.TABLE_PREFIX."_entry WHERE (end_time < '$begin_bookings' )"));
    $test_del2 = mysqli_num_rows(mysqli_query($GLOBALS['db_c'], 'SELECT * FROM '.TABLE_PREFIX."_repeat WHERE (end_date < '$begin_bookings')"));
    if (($test_del1 != 0) || ($test_del2 != 0)) {
        $demande_confirmation = 'yes';
    } else {
        if (!Settings::set('begin_bookings', $begin_bookings)) {
            echo $vocab['save_err']." begin_bookings !<br />";
        }
    }

    if (isset($_POST['end_day']) && isset($_POST['end_month']) && isset($_POST['end_year'])) {
        $end_day = clean_input($_POST['end_day']);
        $end_month = clean_input($_POST['end_month']);
        $end_year = clean_input($_POST['end_year']);
        while (!checkdate($end_month, $end_day, $end_year)) {
            $end_day--;
        }
        $end_bookings = mktime(23, 59, 59, $end_month, $end_day, $end_year);
        if ($end_bookings < $begin_bookings) {
            $end_bookings = $begin_bookings;
        }
        $test_del1 = mysqli_num_rows(mysqli_query($GLOBALS['db_c'], 'SELECT * FROM '.TABLE_PREFIX."_entry WHERE (start_time > '$end_bookings' )"));
        $test_del2 = mysqli_num_rows(mysqli_query($GLOBALS['db_c'], 'SELECT * FROM '.TABLE_PREFIX."_repeat WHERE (start_time > '$end_bookings')"));
        if (($test_del1 != 0) || ($test_del2 != 0)) {
            $demande_confirmation = 'yes';
        } else {
            if (!Settings::set('end_bookings', $end_bookings)) {
                echo $vocab['save_err']." end_bookings !<br />";
            }
        }
    }

    if ($demande_confirmation == 'yes') {
        header("Location: ./admin_confirm_change_date_bookings.php?end_bookings=$end_bookings&begin_bookings=$begin_bookings");
        die();
    }
}
if (!Settings::load()) {
    die(get_vocab('error_settings_load'));
}
// Si pas de problème, message de confirmation
if (isset($_POST['ok'])) {
    $_SESSION['displ_msg'] = 'yes';
    if ($msg == '') {
        $msg = get_vocab('message_records');
    }
    Header('Location: '.'admin_config1.php?msg='.$msg);
    exit();
}
if ((isset($_GET['msg'])) && isset($_SESSION['displ_msg']) && ($_SESSION['displ_msg'] == 'yes')) {
    $msg = $_GET['msg'];
} else {
    $msg = '';
}
// début du code html
# print the page header
start_page_w_header('', '', '', $type = 'with_session');
affiche_pop_up($msg, 'admin');
// Affichage de la colonne de gauche
include 'admin_col_gauche2.php';
//echo "<p>".get_vocab('mess_avertissement_config')."</p>";
echo '<div class="col-md-9 col-sm-8 col-xs-12">';
echo "<h2>".get_vocab('admin_config1.php')."</h2>";
// Adapter les fichiers de langue
echo '<h3>'.get_vocab('adapter_fichiers_langue').'</h3>'.PHP_EOL;
echo get_vocab('adapter_fichiers_langue_explain').PHP_EOL;
//
// Config générale
//****************
//
echo '<form enctype="multipart/form-data" action="./admin_config1.php" id="nom_formulaire" method="post" >'.PHP_EOL;
echo '<h3>'.get_vocab('miscellaneous').'</h3>'.PHP_EOL;
?>
<table class="table_adm">
	<tr>
		<td>
			<?php echo get_vocab('title_home_page'); ?>
		</td>
		<td>
			<input class="form-control" type="text" name="title_home_page" id="title_home_page" size="40" value="<?php echo(Settings::get('title_home_page')); ?>" />
		</td>
	</tr>
	<tr>
		<td>
			<?php echo get_vocab('message_home_page'); ?>
		</td>
		<td>
			<textarea class="form-control" name="message_home_page" rows="3" cols="40"><?php echo(Settings::get('message_home_page')); ?>
			</textarea>
		</td>
	</tr>
	<tr>
		<td>
			<?php echo get_vocab('company'); ?>
		</td>
		<td>
			<input class="form-control" type="text" name="company" size="40" value="<?php echo(Settings::get('company')); ?>" />
		</td>
	</tr>
	<tr>
		<td>
			<?php echo get_vocab('grr_url'); ?>
		</td>
		<td>
			<input class="form-control" type="text" name="grr_url" size="40" value="<?php echo(Settings::get('grr_url')); ?>" />
		</td>
	</tr>
	<tr>
		<td colspan="2">
			<input type="checkbox" name="use_grr_url" value="y" <?php if (Settings::get('use_grr_url') == 'y') {
    echo ' checked="checked" ';
} ?> />
			<i><?php echo get_vocab('grr_url_explain'); ?></i>
		</td>
	</tr>
	<tr>
		<td>
			<?php echo get_vocab('webmaster_name'); ?>
		</td>
		<td>
			<input class="form-control" type="text" name="webmaster_name" size="40" value="<?php echo(Settings::get('webmaster_name')); ?>" />
		</td>
	</tr>
	<tr>
		<td>
			<?php echo get_vocab('webmaster_email').'<br /><i>'.get_vocab('plusieurs_adresses_separees_points_virgules').'</i>'; ?>
		</td>
		<td>
			<input class="form-control" type="text" id="webmaster_email" name="webmaster_email" size="40" value="<?php echo(Settings::get('webmaster_email')); ?>" />
		</td>
	</tr>
	<tr>
		<td>
			<?php echo get_vocab('technical_support_email').'<br /><i>'.get_vocab('plusieurs_adresses_separees_points_virgules').'</i>'; ?>
		</td>
		<td>
			<input class="form-control" type="text" id="technical_support_email" name="technical_support_email" size="40" value="<?php echo(Settings::get('technical_support_email')); ?>" />
		</td>
	</tr>
</table>
<?php
echo '<h3>'.get_vocab('logo_msg').'</h3>'.PHP_EOL;
echo '<table>'.PHP_EOL;
echo '<tr>'.PHP_EOL;
echo '<td>'.get_vocab('choisir_image_logo').'</td>'.PHP_EOL;
echo '<td><input type="file" name="doc_file" /></td>'.PHP_EOL;
echo '</tr>'.PHP_EOL;
$nom_picture = '../images/'.Settings::get('logo');
if ((Settings::get('logo') != '') && (@file_exists($nom_picture))) {
    echo '<tr>'.PHP_EOL;
    echo '<td>'.get_vocab('supprimer_logo').get_vocab('deux_points').PHP_EOL;
    echo '<img src="'.$nom_picture.'" class="image" alt="logo" title="'.$nom_picture.'"/>'.PHP_EOL;
    echo '</td>'.PHP_EOL;
    echo '<td><input type="checkbox" name="sup_img" /></td>'.PHP_EOL;
    echo '</tr>'.PHP_EOL;
}
echo '</table>'.PHP_EOL;
echo '<h3>'.get_vocab('affichage_calendriers').'</h3>'.PHP_EOL;
echo '<p>'.get_vocab('affichage_calendriers_msg').get_vocab('deux_points').PHP_EOL;
echo '<select class="form-control" name="nb_calendar" >'.PHP_EOL;
for ($k = 0; $k < 6; ++$k) {
    echo '<option value="'.$k.'" ';
    if (Settings::get('nb_calendar') == $k) {
        echo ' selected="selected" ';
    }
    echo '>'.$k.'</option>'.PHP_EOL;
}
echo '</select>'.PHP_EOL;
echo '</p>'.PHP_EOL;
if (Settings::get('use_fckeditor') == 1) {
    echo '<script type="text/javascript" src="../js/ckeditor/ckeditor.js"></script>'.PHP_EOL;
}
echo '<h3>'.get_vocab('message_perso').'</h3>'.PHP_EOL;
echo '<p>'.get_vocab('message_perso_explain').PHP_EOL;
if (Settings::get('use_fckeditor') != 1) {
    echo ' '.get_vocab('description complete2');
}
if (Settings::get('use_fckeditor') == 1) {
    echo '<textarea id="editor1" name="message_accueil" rows="8" cols="120">'.PHP_EOL;
    echo htmlspecialchars(Settings::get('message_accueil'));
    echo "</textarea>\n";
    ?>
	<script type="text/javascript">
		//<![CDATA[
		CKEDITOR.replace( 'editor1',
		{
            extraPlugins: 'colorbutton,colordialog',
			toolbar :
			[
			['Source'],
			['Cut','Copy','Paste','PasteText','PasteFromWord', 'SpellChecker', 'Scayt'],
			['Undo','Redo','-','Find','Replace','-','SelectAll','RemoveFormat'],
			['Bold','Italic','Underline','Strike','-','Subscript','Superscript','-','TextColor','BGColor'],
			['NumberedList','BulletedList','-','Outdent','Indent'],
			['JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock'],
			['Link','Unlink','Anchor'],
			['Image','Table','HorizontalRule','SpecialChar','PageBreak'],
			]
		});
		//]]>
	</script>
	<?php

} else {
    echo "\n<textarea name=\"message_accueil\" rows=\"8\" cols=\"120\">".htmlspecialchars(Settings::get('message_accueil')).'</textarea>'.PHP_EOL;
}
echo '</p>'.PHP_EOL;
//
// Début et fin des réservations
//******************************
//
echo '<hr /><h3>'.get_vocab('title_begin_end_bookings')."</h3>\n";
echo '<table class="table_adm">';
echo '	<tr>';
echo '		<td>';
			echo get_vocab('begin_bookings');
echo '		</td>';
echo '  	<td>';
            $typeDate = 'begin_';
            $bday = strftime('%d', Settings::get('begin_bookings'));
            $bmonth = strftime('%m', Settings::get('begin_bookings'));
            $byear = strftime('%Y', Settings::get('begin_bookings'));
            echo '<div class="col-xs-12">'.PHP_EOL;
            echo '<div class="form-inline">'.PHP_EOL;
            genDateSelector('begin_', $bday, $bmonth, $byear, 'more_years');
            echo '<input type="hidden" disabled="disabled" id="mydate_'.$typeDate.'">'.PHP_EOL;
            echo '<script>'.PHP_EOL;
            echo '$(function() {'.PHP_EOL;
            echo '$.datepicker.setDefaults( $.datepicker.regional[\'fr\'] );'.PHP_EOL;
            echo '$(\'#mydate_'.$typeDate.'\').datepicker({'.PHP_EOL;
            echo 'beforeShow: readSelected, onSelect: updateSelected,'.PHP_EOL;
            echo 'showOn: \'both\', buttonImageOnly: true, buttonImage: \'../img_grr/calendar.png\',buttonText: "Choisir la date"});'.PHP_EOL;
            echo 'function readSelected()'.PHP_EOL;
            echo '{'.PHP_EOL;
            echo '$(\'#mydate_'.$typeDate.'\').val($(\'#'.$typeDate.'_day\').val() + \'/\' +'.PHP_EOL;
            echo '$(\'#'.$typeDate.'_month\').val() + \'/\' + $(\'#'.$typeDate.'_year\').val());'.PHP_EOL;
            echo 'return {};'.PHP_EOL;
            echo '}'.PHP_EOL;
            echo 'function updateSelected(date)'.PHP_EOL;
            echo '{'.PHP_EOL;
            echo '$(\'#'.$typeDate.'_day\').val(date.substring(0, 2));'.PHP_EOL;
            echo '$(\'#'.$typeDate.'_month\').val(date.substring(3, 5));'.PHP_EOL;
            echo '$(\'#'.$typeDate.'_year\').val(date.substring(6, 10));'.PHP_EOL;
            echo '}'.PHP_EOL;
            echo '});'.PHP_EOL;
            echo '</script>'.PHP_EOL;
            echo '</div>'.PHP_EOL;
            echo '</div>'.PHP_EOL;
?>
</td>
<td> </td>
</tr>
</table>
<?php echo '<p><i>'.get_vocab('begin_bookings_explain').'</i>'; ?>
<br /><br />
</p>
<table class="table_adm">
	<tr
	><td>
	<?php echo get_vocab('end_bookings'); ?>
</td>
<td>
	<?php
    $typeDate = 'end_';
    $eday = strftime('%d', Settings::get('end_bookings'));
    $emonth = strftime('%m', Settings::get('end_bookings'));
    $eyear = strftime('%Y', Settings::get('end_bookings'));
    echo '<div class="col-xs-12">'.PHP_EOL;
    echo '<div class="form-inline">'.PHP_EOL;
    genDateSelector('end_', $eday, $emonth, $eyear, 'more_years');
    echo '<input type="hidden" disabled="disabled" id="mydate_'.$typeDate.'">'.PHP_EOL;
    echo '<script>'.PHP_EOL;
    echo '$(function() {'.PHP_EOL;
    echo '$.datepicker.setDefaults( $.datepicker.regional[\'fr\'] );'.PHP_EOL;
    echo '$(\'#mydate_'.$typeDate.'\').datepicker({'.PHP_EOL;
    echo 'beforeShow: readSelected, onSelect: updateSelected,'.PHP_EOL;
    echo 'showOn: \'both\', buttonImageOnly: true, buttonImage: \'../img_grr/calendar.png\',buttonText: "Choisir la date"});'.PHP_EOL;
    echo 'function readSelected()'.PHP_EOL;
    echo '{'.PHP_EOL;
    echo '$(\'#mydate_'.$typeDate.'\').val($(\'#'.$typeDate.'_day\').val() + \'/\' +'.PHP_EOL;
    echo '$(\'#'.$typeDate.'_month\').val() + \'/\' + $(\'#'.$typeDate.'_year\').val());'.PHP_EOL;
    echo 'return {};'.PHP_EOL;
    echo '}'.PHP_EOL;
    echo 'function updateSelected(date)'.PHP_EOL;
    echo '{'.PHP_EOL;
    echo '$(\'#'.$typeDate.'_day\').val(date.substring(0, 2));'.PHP_EOL;
    echo '$(\'#'.$typeDate.'_month\').val(date.substring(3, 5));'.PHP_EOL;
    echo '$(\'#'.$typeDate.'_year\').val(date.substring(6, 10));'.PHP_EOL;
    echo '}'.PHP_EOL;
    echo '});'.PHP_EOL;
    echo '</script>'.PHP_EOL;
    echo '</div>'.PHP_EOL;
    echo '</div>'.PHP_EOL;
?>
</td>
</tr>
</table>
<?php echo '<p><i>'.get_vocab('end_bookings_explain').'</i></p>'.PHP_EOL;
//
// Configuration de l'affichage par défaut
//****************************************
//
?>
<hr />
<?php echo '<h3>'.get_vocab('default_parameter_values_title').'</h3>'.PHP_EOL;
echo '<p>'.get_vocab('explain_default_parameter').'</p>'.PHP_EOL;
//
// Choix du type d'affichage
//
echo '<h4>'.get_vocab('explain_area_list_format').'</h4>'.PHP_EOL;
echo '<table><tr><td>'.get_vocab('liste_area_list_format').'</td><td>&nbsp; '.PHP_EOL;
echo '<input type="radio" name="area_list_format" value="list" ';
if (Settings::get('area_list_format') == 'list') {
    echo 'checked="checked"';
}
echo ' />'.PHP_EOL;
echo '</td></tr>'.PHP_EOL;
echo '<tr><td>'.get_vocab('select_area_list_format').'</td><td>&nbsp; '.PHP_EOL;
echo '<input type="radio" name="area_list_format" value="select" ';
if (Settings::get('area_list_format') == 'select') {
    echo 'checked="checked"';
}
echo ' />'.PHP_EOL;
echo '</td></tr>'.PHP_EOL;
echo '<tr><td>'.get_vocab('item_area_list_format').'</td><td>&nbsp; '.PHP_EOL;
echo '<input type="radio" name="area_list_format" value="item" ';
if (Settings::get('area_list_format') == 'item') {
    echo 'checked="checked"';
}
echo ' />'.PHP_EOL;
echo '</td>'.PHP_EOL;
echo '</tr>'.PHP_EOL;
echo '</table>'.PHP_EOL;
if (Settings::get('module_multisite') == 'Oui') {
    $use_site = 'y';
} else {
    $use_site = 'n';
}
?>
<script type="text/javascript">
	function modifier_liste_domaines(){
		$.ajax({
			url: "../my_account_modif_listes.php",
			type: "get",
			dataType: "html",
			data: {
				id_site: $('#id_site').val(),
				default_area : '<?php echo Settings::get('default_area'); ?>',
				session_login:'<?php echo getUserName(); ?>',
				use_site:'<?php echo $use_site; ?>',
				type:'domaine',
			},
			success: function(returnData){
				$("#div_liste_domaines").html(returnData);
			},
			error: function(e){
				alert(e);
			}
		});
	}
	function modifier_liste_ressources(action){
		$.ajax({
			url: "../my_account_modif_listes.php",
			type: "get",
			dataType: "html",
			data: {
				id_area:$('id_area').serialize(true),
				default_room : '<?php echo Settings::get('default_room'); ?>',
				type:'ressource',
				action:+action,
			},
			success: function(returnData){
				$("#div_liste_ressources").html(returnData);
			},
			error: function(e){
				alert(e);
			}
		});
	}
</script>
<?php
if (Settings::get('module_multisite') == 'Oui') {
    echo('<h4>'.get_vocab('explain_default_area_and_room_and_site').'</h4>');
} else {
    echo('<h4>'.get_vocab('explain_default_area_and_room').'</h4>');
}
/*
 * Liste des sites
 */
if (Settings::get('module_multisite') == 'Oui') {
    $sql = 'SELECT id,sitecode,sitename
	FROM '.TABLE_PREFIX.'_site
	ORDER BY id ASC';
    $resultat = grr_sql_query($sql);
    echo('
		<table>
			<tr>
				<td>'.get_vocab('default_site').get_vocab('deux_points').'</td><td>
					<select class="form-control" id="id_site" name="id_site" onchange="modifier_liste_domaines();modifier_liste_ressources(2)">
						<option value="-1">'.get_vocab('choose_a_site').'</option>'."\n");
    for ($enr = 0; ($row = grr_sql_row($resultat, $enr)); ++$enr) {
        echo '<option value="'.$row[0].'"';
        if (Settings::get('default_site') == $row[0]) {
            echo ' selected="selected" ';
        }
        echo '>'.htmlspecialchars($row[2]);
        echo '</option>'."\n";
    }
    echo('</select>
</td>
</tr></table>');
} else {
    echo '<input class="form-control" type="hidden" id="id_site" name="id_site" value="-1" />';
}
/*
  * Liste des domaines
 */
echo "<table>";
echo '<tr>'.PHP_EOL;
echo '<td>'.PHP_EOL;
echo '<div id="div_liste_domaines">'.PHP_EOL;
// Ici, on insère la liste des domaines avec de l'ajax !
echo '</div>'.PHP_EOL;
echo '</td>'.PHP_EOL;
echo '</tr>'.PHP_EOL;
/*
 * Liste des ressources
 */
echo '<tr>'.PHP_EOL;
echo '<td>'.PHP_EOL;
echo '<div id="div_liste_ressources">'.PHP_EOL;
echo '<input class="form-control" type="hidden" id="id_area" name="id_area" value="'.Settings::get('default_area').'" />'.PHP_EOL;
// Ici, on insère la liste des ressouces avec de l'ajax !
echo '</div>'.PHP_EOL;
echo '</td>'.PHP_EOL;
echo '</tr>'.PHP_EOL;
echo '</table>'.PHP_EOL;
echo '<script type="text/javascript">modifier_liste_domaines();</script>'.PHP_EOL;
echo '<script type="text/javascript">modifier_liste_ressources(1);</script>'.PHP_EOL;
//
// Choix de la feuille de style
//
echo '<h4>'.get_vocab('explain_css').'</h4>'.PHP_EOL;
echo '<table><tr><td>'.get_vocab('choose_css').'</td><td>'.PHP_EOL;
echo "<select class=\"form-control\" name='default_css'>".PHP_EOL;
$i = 0;
while ($i < count($liste_themes)) {
    echo "<option value='".$liste_themes[$i]."'";
    if (Settings::get('default_css') == $liste_themes[$i]) {
        echo ' selected="selected"';
    }
    echo ' >'.encode_message_utf8($liste_name_themes[$i]).'</option>';
    ++$i;
}
echo "</select></td></tr></table>\n";
//
// Choix de la langue
//
echo '<h4>'.get_vocab('choose_language').'</h4>'.PHP_EOL;
echo '<table>'.PHP_EOL;
echo '<tr>'.PHP_EOL;
echo '<td>'.get_vocab('choose_css').'</td>'.PHP_EOL;
echo '<td>'.PHP_EOL;
echo '<select class="form-control" name="default_language">'.PHP_EOL;
$i = 0;
while ($i < count($liste_language)) {
    echo "<option value='".$liste_language[$i]."'";
    if (Settings::get('default_language') == $liste_language[$i]) {
        echo ' selected="selected"';
    }
    echo ' >'.encode_message_utf8($liste_name_language[$i]).'</option>'.PHP_EOL;
    ++$i;
}
echo '</select>'.PHP_EOL;
echo '</td>'.PHP_EOL;
echo '</tr>'.PHP_EOL;
echo '</table>'.PHP_EOL;
#
# Affichage du contenu des "info-bulles" des réservations, dans les vues journées, semaine et mois.
# display_info_bulle = 0 : pas d'info-bulle.
# display_info_bulle = 1 : affichage des noms et prénoms du bénéficiaire de la réservation.
# display_info_bulle = 2 : affichage de la description complète de la réservation.
echo '<hr />'.PHP_EOL;
echo '<h3>'.get_vocab('display_info_bulle_msg').'</h3>'.PHP_EOL;
echo '<table>'.PHP_EOL;
echo '<tr>'.PHP_EOL;
echo '<td>'.get_vocab('info_bulle0').'</td>'.PHP_EOL;
echo '<td>'.PHP_EOL;
echo '<input type="radio" name="display_info_bulle" value="0" ';
if (Settings::get('display_info_bulle') == '0') {
    echo 'checked="checked"';
}
echo ' />'.PHP_EOL;
echo '</td>'.PHP_EOL;
echo '</tr>'.PHP_EOL;
echo '<tr>'.PHP_EOL;
echo '<td>'.get_vocab('info_bulle1').'</td>'.PHP_EOL;
echo '<td>'.PHP_EOL;
echo '<input type="radio" name="display_info_bulle" value="1" ';
if (Settings::get('display_info_bulle') == '1') {
    echo 'checked="checked"';
}
echo ' />'.PHP_EOL;
echo '</td>'.PHP_EOL;
echo '</tr>'.PHP_EOL;
echo '<tr>'.PHP_EOL;
echo '<td>'.get_vocab('info_bulle2').'</td>'.PHP_EOL;
echo '<td>'.PHP_EOL;
echo '<input type="radio" name="display_info_bulle" value="2" ';
if (Settings::get('display_info_bulle') == '2') {
    echo 'checked="checked"';
}
echo ' />'.PHP_EOL;
echo '</td>'.PHP_EOL;
echo '</tr>'.PHP_EOL;
echo '</table>'.PHP_EOL;
#Choix  de l'affichage du bouton "afficher le menu de gauche ou non"
#SQL : menu_gauche==1  //le bouton s'affiche par default
# menu_gauche==0 //le bouton ne s'affiche pas par default
# menu_gauche==2 //le menu s'affiche en haut
#Test pour savoir la valeur présente dans la base de données : echo Settings::get("menu_gauche");
echo '<hr />'.PHP_EOL;
echo '<h3>'.get_vocab('display_menu').'</h3>'.PHP_EOL;
echo '<p>'.get_vocab('display_menu_1').'</p>'.PHP_EOL;
echo '<table>'.PHP_EOL;
echo '<tr>'.PHP_EOL;
echo '<td>'.get_vocab('display_menu_2').'</td>'.PHP_EOL;
echo '<td>'.PHP_EOL;
echo '<input type="radio" name="menu_gauche" value="0" ';
if (Settings::get('menu_gauche') == '0') {
    echo 'checked="checked"';
}
echo ' />'.PHP_EOL;
echo '</td>'.PHP_EOL;
echo '</tr>'.PHP_EOL;
echo '<tr>'.PHP_EOL;
echo '<td>'.get_vocab('display_menu_3').'</td>'.PHP_EOL;
echo '<td>'.PHP_EOL;
echo '<input type="radio" name="menu_gauche" value="1" ';
if (Settings::get('menu_gauche') == '1') {
    echo ' checked="checked"';
}
echo ' />'.PHP_EOL;
echo '</td>'.PHP_EOL;
echo '</tr>'.PHP_EOL;
echo '<tr>'.PHP_EOL;
echo '<td>'.get_vocab('display_menu_4').'</td>'.PHP_EOL;
echo '<td>'.PHP_EOL;
echo '<input type="radio" name="menu_gauche" value="2" ';
if (Settings::get('menu_gauche') == '2') {
    echo ' checked="checked"';
}
echo ' />'.PHP_EOL;
echo '</td>'.PHP_EOL;
echo '</tr>'.PHP_EOL;
echo '</table>'.PHP_EOL;
#mail_etat_destinataire = 0 //Le formulaire de contact est désactivé (0 par défaut)
#mail_etat_destinataire = 1 //Le formulaire de contact est activé
#mail_etat_destinataire = 2 //Le formulaire de contact est activé uniquement pour les visiteurs connectés
echo '<hr />'.PHP_EOL;
echo '<h3>'.get_vocab('display_mail_etat_destinataire').'</h3>'.PHP_EOL;
echo '<p>'.get_vocab('display_mail_etat_destinataire_1').'</p>'.PHP_EOL;
echo '<table>'.PHP_EOL;
echo '<tr>'.PHP_EOL;
echo '<td>'.get_vocab('display_mail_etat_destinataire_2').'</td>'.PHP_EOL;
echo '<td>'.PHP_EOL;
echo '<input type="radio" name="mail_etat_destinataire" value="0" ';
if (Settings::get('mail_etat_destinataire') == '0') {
    echo ' checked="checked" ';
}
echo ' />'.PHP_EOL;
echo '</td>'.PHP_EOL;
echo '</tr>'.PHP_EOL;
echo '<tr>'.PHP_EOL;
echo '<td>'.get_vocab('display_mail_etat_destinataire_3').'</td>'.PHP_EOL;
echo '<td>'.PHP_EOL;
echo '<input type="radio" name="mail_etat_destinataire" value="1" ';
if (Settings::get('mail_etat_destinataire') == '1') {
    echo 'checked="checked"';
}
echo ' />'.PHP_EOL;
echo '</td>'.PHP_EOL;
echo '</tr>'.PHP_EOL;
echo '<tr>'.PHP_EOL;
echo '<td>'.get_vocab('display_mail_etat_destinataire_4').'</td>'.PHP_EOL;
echo '<td>'.PHP_EOL;
echo '<input type="radio" name="mail_etat_destinataire" value="2" ';
if (Settings::get('mail_etat_destinataire') == '2') {
    echo 'checked="checked"';
}
echo ' />'.PHP_EOL;
echo '</td>'.PHP_EOL;
echo '</tr>'.PHP_EOL;
echo '<tr><td>'.get_vocab('display_mail_destinataire').'</td>'.PHP_EOL;
echo '</tr>'.PHP_EOL;
echo '<tr>'.PHP_EOL;
echo '<td>'.PHP_EOL;
echo '<input class="form-control" type="text" id="mail_destinataire" name="mail_destinataire" value="'.Settings::get('mail_destinataire').'" size="30">'.PHP_EOL;
echo '</td>'.PHP_EOL;
echo '</tr>'.PHP_EOL;
echo '<tr><td>'.PHP_EOL;
echo get_vocab('nb_max_resa_form');
echo '</td><td>'.PHP_EOL;
echo '<input type="number" name="nb_max_resa_form" value="'.Settings::get('nb_max_resa_form').'" size="5" min="-1" />'.PHP_EOL;
echo '</td></tr>'.PHP_EOL;
echo '</table>'.PHP_EOL;
// modification du formulaire de choix
echo '<section id="display_planning_resa">';
echo '<h3>'.get_vocab('display_planning_resa').'</h3>';
echo '	<table class="table table-condensed">';
echo '		<thead><tr>';
echo '				<th style="width: 200px"></th>';
echo '				<th style="width: 150px">'.get_vocab('dontDisplay').'</th>';
echo '				<th>'.get_vocab('Display').'</th>';
echo '		</tr></thead>';
echo '		<tbody>';
echo '			<tr>';
echo '				<td>'.get_vocab('sum_by_creator').'</td>';
echo '				<td><input type="radio" name="display_beneficiaire" value="0"';
if (Settings::get('display_beneficiaire') == '0') echo 'checked="checked"';
echo '				 /></td>';
echo '				<td><input type="radio" name="display_beneficiaire" value="1"';
if (Settings::get('display_beneficiaire') == '1') echo 'checked="checked"';
echo '				 /></td>';
echo '			</tr>';
echo '			<tr>';
echo '				<td>'.get_vocab('namebooker').'</td>';
echo '				<td><input type="radio" name="display_short_description" value="0"';
if (Settings::get('display_short_description') == '0') echo 'checked="checked"';
echo '				/></td>';
echo '				<td><input type="radio" name="display_short_description" value="1"';
if (Settings::get('display_short_description') == '1') echo 'checked="checked"';
echo ' 				/></td>';
echo '			</tr>';
echo '			<tr>';
echo '				<td>'.get_vocab('match_descr').'</td>';
echo '				<td><input type="radio" name="display_full_description" value="0"';
if (Settings::get('display_full_description') == '0') echo 'checked="checked"';
echo '				/></td>';
echo '				<td><input type="radio" name="display_full_description" value="1"';
if (Settings::get('display_full_description') == '1') echo 'checked="checked"';
echo '				/></td>';
echo '			</tr>';
echo '			<tr>';
echo '				<td>'.get_vocab('type').'</td>';
echo '				<td><input type="radio" name="display_type" value="0"';
if (Settings::get('display_type') == '0') echo'checked="checked"';
echo ' 				/></td>';
echo '				<td><input type="radio" name="display_type" value="1"';
if (Settings::get('display_type') == '1') echo 'checked="checked"';
echo '				/></td>';
echo '			</tr>';
echo '		</tbody>';
echo '	</table>';
echo '</section>';

###########################################################
# Affichage des  adresses email dans la fiche de réservation
###########################################################
# Qui peut voir les adresse email ?
# display_level_email  = 0 : N'importe qui allant sur le site, meme s'il n'est pas connecté
# display_level_email  = 1 : Il faut obligatoirement se connecter, même en simple visiteur.
# display_level_email  = 2 : Il faut obligatoirement se connecter et avoir le statut "utilisateur"
# display_level_email  = 3 : Il faut obligatoirement se connecter et être au moins gestionnaire d'une ressource
# display_level_email  = 4 : Il faut obligatoirement se connecter et être au moins administrateur du domaine
# display_level_email  = 5 : Il faut obligatoirement se connecter et être administrateur de site
# display_level_email  = 6 : Il faut obligatoirement se connecter et être administrateur général
echo '<hr />'.PHP_EOL;
echo '<h3>'.get_vocab('display_level_email_msg1').'</h3>'.PHP_EOL;
echo '<p>'.get_vocab('display_level_email_msg2').'</p>'.PHP_EOL;
echo '<table>'.PHP_EOL;
echo '<tr>'.PHP_EOL;
echo '<td>'.get_vocab('visu_fiche_description0').'</td>'.PHP_EOL;
echo '<td>'.PHP_EOL;
echo "<input type='radio' name='display_level_email' value='0' ";
if (Settings::get('display_level_email') == '0') {
    echo 'checked="checked"';
}
echo ' />'.PHP_EOL;
echo '</td>'.PHP_EOL;
echo '</tr>'.PHP_EOL;
echo '<tr>'.PHP_EOL;
echo '<td>'.get_vocab('visu_fiche_description1').'</td>'.PHP_EOL;
echo '<td>'.PHP_EOL;
echo "<input type='radio' name='display_level_email' value='1' ";
if (Settings::get('display_level_email') == '1') {
    echo 'checked="checked"';
}
echo ' />'.PHP_EOL;
echo '</td>'.PHP_EOL;
echo '</tr>'.PHP_EOL;
echo '<tr>'.PHP_EOL;
echo '<td>'.get_vocab('visu_fiche_description2').'</td>'.PHP_EOL;
echo '<td>'.PHP_EOL;
echo "<input type='radio' name='display_level_email' value='2' ";
if (Settings::get('display_level_email') == '2') {
    echo 'checked="checked"';
}
echo ' />'.PHP_EOL;
echo '</td>'.PHP_EOL;
echo '</tr>'.PHP_EOL;
echo '<tr>'.PHP_EOL;
echo '<td>'.get_vocab('visu_fiche_description3').'</td>'.PHP_EOL;
echo '<td>'.PHP_EOL;
echo "<input type='radio' name='display_level_email' value='3' ";
if (Settings::get('display_level_email') == '3') {
    echo 'checked="checked"';
}
echo ' />'.PHP_EOL;
echo '</td>'.PHP_EOL;
echo '</tr>'.PHP_EOL;
echo '<tr>'.PHP_EOL;
echo '<td>'.get_vocab('visu_fiche_description4').'</td>'.PHP_EOL;
echo '<td>'.PHP_EOL;
echo "<input type='radio' name='display_level_email' value='4' ";
if (Settings::get('display_level_email') == '4') {
    echo 'checked="checked"';
}
echo ' />'.PHP_EOL;
echo '</td>'.PHP_EOL;
echo '</tr>'.PHP_EOL;
if (Settings::get('module_multisite') == 'Oui') {
    echo '<tr>'.PHP_EOL;
    echo '<td>'.get_vocab('visu_fiche_description5').'</td>'.PHP_EOL;
    echo '<td>'.PHP_EOL;
    echo "<input type='radio' name='display_level_email' value='5' ";
    if (Settings::get('display_level_email') == '5') {
        echo 'checked="checked"';
    }
    echo ' />'.PHP_EOL;
    echo '</td>'.PHP_EOL;
    echo '</tr>'.PHP_EOL;
}
echo '<tr>'.PHP_EOL;
echo '<td>'.get_vocab('visu_fiche_description6').'</td>'.PHP_EOL;
echo '<td>'.PHP_EOL;
echo "<input type='radio' name='display_level_email' value='6' ";
if (Settings::get('display_level_email') == '6') {
    echo 'checked="checked"';
}
echo ' />'.PHP_EOL;
echo '</td>'.PHP_EOL;
echo '</tr>'.PHP_EOL;
echo '</table>'.PHP_EOL;
#Affichage de view_entry sous forme de page ou de popup
echo '<hr />'.PHP_EOL;
echo '<h3>'.get_vocab('display_level_view_entry').'</h3>'.PHP_EOL;
echo '<table>'.PHP_EOL;
echo '<tr>'.PHP_EOL;
echo '<td>'.get_vocab('display_level_view_entry_0').'</td>'.PHP_EOL;
echo '<td>'.PHP_EOL;
echo "<input type='radio' name='display_level_view_entry' value='0' ";
if (Settings::get('display_level_view_entry') == '0') {
    echo 'checked="checked"';
}
echo ' />'.PHP_EOL;
echo '</td>'.PHP_EOL;
echo '</tr>'.PHP_EOL;
echo '<tr>'.PHP_EOL;
echo '<td>'.get_vocab('display_level_view_entry_1').'</td>'.PHP_EOL;
echo '<td>'.PHP_EOL;
echo "<input type='radio' name='display_level_view_entry' value='1' ";
if (Settings::get('display_level_view_entry') == '1') {
    echo 'checked="checked"';
}
echo ' />'.PHP_EOL;
echo '</td>'.PHP_EOL;
echo '</tr>'.PHP_EOL;
echo ' </table>'.PHP_EOL;
# Remplissage de la description courte
echo '<hr />'.PHP_EOL;
echo '<h3>'.get_vocab('remplissage_description_breve_msg').'</h3>'.PHP_EOL;
echo '<table>'.PHP_EOL;
echo '<tr>'.PHP_EOL;
echo '<td>'.get_vocab('remplissage_description_breve0').'</td>'.PHP_EOL;
echo '<td>'.PHP_EOL;
echo "<input type='radio' name='remplissage_description_breve' value='0' ";
if (Settings::get('remplissage_description_breve') == '0') {
    echo 'checked="checked"';
}
echo ' />'.PHP_EOL;
echo '</td>'.PHP_EOL;
echo '</tr>'.PHP_EOL;
echo '<tr>'.PHP_EOL;
echo '<td>'.get_vocab('remplissage_description_breve1').'</td>'.PHP_EOL;
echo '<td>'.PHP_EOL;
echo "<input type='radio' name='remplissage_description_breve' value='1' ";
if (Settings::get('remplissage_description_breve') == '1') {
    echo 'checked="checked"';
}
echo ' />'.PHP_EOL;
echo '</td>'.PHP_EOL;
echo '</tr>'.PHP_EOL;
echo '<tr>'.PHP_EOL;
echo '<td>'.get_vocab('remplissage_description_breve2').'</td>'.PHP_EOL;
echo '<td>'.PHP_EOL;
echo "<input type='radio' name='remplissage_description_breve' value='2' ";
if (Settings::get('remplissage_description_breve') == '2') {
    echo 'checked="checked"';
}
echo ' />'.PHP_EOL;
echo '</td>'.PHP_EOL;
echo '</tr>'.PHP_EOL;
echo '</table>'.PHP_EOL;
# Remplissage de la description complète
echo '<hr />'.PHP_EOL;
echo '<h3>'.get_vocab('remplissage_description_complete_msg').'</h3>'.PHP_EOL;
echo '<table>'.PHP_EOL;
echo '<tr>'.PHP_EOL;
echo '<td>'.get_vocab('remplissage_description_complete0').'</td>'.PHP_EOL;
echo '<td>'.PHP_EOL;
echo "<input type='radio' name='remplissage_description_complete' value='0' ";
if (Settings::get('remplissage_description_complete') == '0') {
    echo 'checked="checked"';
}
echo ' />'.PHP_EOL;
echo '</td>'.PHP_EOL;
echo '</tr>'.PHP_EOL;
echo '<tr>'.PHP_EOL;
echo '<td>'.get_vocab('remplissage_description_complete1').'</td>'.PHP_EOL;
echo '<td>'.PHP_EOL;
echo "<input type='radio' name='remplissage_description_complete' value='1' ";
if (Settings::get('remplissage_description_complete') == '1') {
    echo 'checked="checked"';
}
echo ' />'.PHP_EOL;
echo '</td>'.PHP_EOL;
echo '</tr>'.PHP_EOL;
echo '</table>'.PHP_EOL;
# Ouvrir les pages au format imprimable dans une nouvelle fenêtre du navigateur (0 pour non et 1 pour oui)
echo '<hr />'.PHP_EOL;
echo '<h3>'.get_vocab('pview_new_windows_msg').'</h3>'.PHP_EOL;
echo '<table>'.PHP_EOL;
echo '<tr>'.PHP_EOL;
echo '<td>'.get_vocab('pview_new_windows0').'</td>'.PHP_EOL;
echo '<td>'.PHP_EOL;
echo "<input type='radio' name='pview_new_windows' value='0' ";
if (Settings::get('pview_new_windows') == '0') {
    echo 'checked="checked"';
}
echo ' />';
echo '</td>'.PHP_EOL;
echo '</tr>'.PHP_EOL;
echo '<tr>'.PHP_EOL;
echo '<td>'.get_vocab('pview_new_windows1').'</td>'.PHP_EOL;
echo '<td>'.PHP_EOL;
echo "<input type='radio' name='pview_new_windows' value='1' ";
if (Settings::get('pview_new_windows') == '1') {
    echo 'checked="checked"';
}
echo ' />'.PHP_EOL;
echo '</td>'.PHP_EOL;
echo '</tr>'.PHP_EOL;
echo '</table>'.PHP_EOL;
# Afficher la legende en couleur dans le menu gauche
echo '<hr />'.PHP_EOL;
echo '<h3>'.get_vocab('legend_msg').'</h3>'.PHP_EOL;
echo '<table>'.PHP_EOL;
echo '<tr>'.PHP_EOL;
echo '<td>'.get_vocab('YES').'</td>'.PHP_EOL;
echo '<td>'.PHP_EOL;
echo "<input type='radio' name='legend' value='0' ";
if (Settings::get('legend') == '0') {
    echo 'checked="checked"';
}
echo ' />'.PHP_EOL;
echo '</td>'.PHP_EOL;
echo '</tr>'.PHP_EOL;
echo '<tr>'.PHP_EOL;
echo '<td>'.get_vocab('NO').'</td>'.PHP_EOL;
echo '<td>'.PHP_EOL;
echo "<input type='radio' name='legend' value='1' ";
if (Settings::get('legend') == '1') {
    echo 'checked="checked"';
}
echo ' />'.PHP_EOL;
echo '</td>'.PHP_EOL;
echo '</tr>'.PHP_EOL;
echo '</table>'.PHP_EOL;
# Afficher l'imprimante
echo '<hr />'.PHP_EOL;
echo '<h3>'.get_vocab('imprimante_msg').'</h3>'.PHP_EOL;
echo '<table>'.PHP_EOL;
echo '<tr>'.PHP_EOL;
echo '<td>'.get_vocab('YES').'</td>'.PHP_EOL;
echo '<td>'.PHP_EOL;
echo "<input type='radio' name='imprimante' value='0' ";
if (Settings::get('imprimante') == '0') {
    echo 'checked="checked"';
}
echo ' />'.PHP_EOL;
echo '</td>'.PHP_EOL;
echo '</tr>'.PHP_EOL;
echo '<tr>'.PHP_EOL;
echo '<td>'.get_vocab('NO').'</td>'.PHP_EOL;
echo '<td>'.PHP_EOL;
echo "<input type='radio' name='imprimante' value='1' ";
if (Settings::get('imprimante') == '1') {
    echo 'checked="checked"';
}
echo ' />'.PHP_EOL;
echo '</td>'.PHP_EOL;
echo '</tr>'.PHP_EOL;
echo '</table>'.PHP_EOL;

# Affichage pdf 
echo '<hr />'.PHP_EOL;
echo '<h3>'.get_vocab('affichage_pdf').'</h3>'.PHP_EOL;
echo '<table>'.PHP_EOL;
echo '<tr>'.PHP_EOL;
echo '<td>'.get_vocab('YES').'</td>'.PHP_EOL;
echo '<td>'.PHP_EOL;
echo "<input type='radio' name='pdf' value='1' ";
if (Settings::get('pdf') == '1') {
    echo 'checked="checked"';
}
echo ' />'.PHP_EOL;
echo '</td>'.PHP_EOL;
echo '</tr>'.PHP_EOL;
echo '<tr>'.PHP_EOL;
echo '<td>'.get_vocab('NO').'</td>'.PHP_EOL;
echo '<td>'.PHP_EOL;
echo "<input type='radio' name='pdf' value='0' ";
if (Settings::get('pdf') == '0') {
    echo 'checked="checked"';
}
echo ' />'.PHP_EOL;
echo '</td>'.PHP_EOL;
echo '</tr>'.PHP_EOL;
echo '</table>'.PHP_EOL;
# Autoriser la periodicité
echo '<hr />'.PHP_EOL;
echo '<h3>'.get_vocab('periodicite_msg').'</h3>'.PHP_EOL;
echo '<table>'.PHP_EOL;
echo '<tr>'.PHP_EOL;
echo '<td>'.get_vocab('YES').'</td>'.PHP_EOL;
echo '<td>'.PHP_EOL;
echo "<input type='radio' name='periodicite' value='y' ";
if (Settings::get('periodicite') == 'y') {
    echo 'checked="checked"';
}
echo ' />'.PHP_EOL;
echo '</td>'.PHP_EOL;
echo '</tr>'.PHP_EOL;
echo '<tr>'.PHP_EOL;
echo '<td>'.get_vocab('NO').'</td>'.PHP_EOL;
echo '<td>'.PHP_EOL;
echo "<input type='radio' name='periodicite' value='n' ";
if (Settings::get('periodicite') == 'n') {
    echo 'checked="checked"';
}
echo ' />'.PHP_EOL;
echo '</td>'.PHP_EOL;
echo '</tr>'.PHP_EOL;
echo '</table>'.PHP_EOL;
# Afficher courrier de validation
echo '<hr />'.PHP_EOL;
echo '<h3>'.get_vocab('courrier_msg').'</h3>'.PHP_EOL;
echo '<table>'.PHP_EOL;
echo '<tr>'.PHP_EOL;
echo '<td>'.get_vocab('YES').'</td>'.PHP_EOL;
echo '<td>'.PHP_EOL;
echo "<input type='radio' name='show_courrier' value='y' ";
if (Settings::get('show_courrier') == 'y') {
    echo 'checked="checked"';
}
echo ' />'.PHP_EOL;
echo '</td>'.PHP_EOL;
echo '</tr>'.PHP_EOL;
echo '<tr>'.PHP_EOL;
echo '<td>'.get_vocab('NO').'</td>'.PHP_EOL;
echo '<td>'.PHP_EOL;
echo "<input type='radio' name='show_courrier' value='n' ";
if (Settings::get('show_courrier') == 'n') {
    echo 'checked="checked"';
}
echo ' />'.PHP_EOL;
echo '</td>'.PHP_EOL;
echo '</tr>'.PHP_EOL;
echo '</table>'.PHP_EOL;
# Afficher vacances et jours fériés
echo '<hr />'.PHP_EOL;
echo '<h3 id="vacances_feries">'.get_vocab('holidays_msg').'</h3>'.PHP_EOL;
echo '<table>'.PHP_EOL;
echo '<tr>'.PHP_EOL;
echo '<td>'.get_vocab('YES').'</td>'.PHP_EOL;
echo '<td>'.PHP_EOL;
echo "<input type='radio' name='show_holidays' value='Oui' ";
if (Settings::get('show_holidays') == 'Oui') {
    echo 'checked="checked"';
}
echo ' />'.PHP_EOL;
echo '</td>'.PHP_EOL;
echo '</tr>'.PHP_EOL;
echo '<tr>'.PHP_EOL;
echo '<td>'.get_vocab('NO').'</td>'.PHP_EOL;
echo '<td>'.PHP_EOL;
echo "<input type='radio' name='show_holidays' value='Non' ";
if (Settings::get('show_holidays') == 'Non') {
    echo 'checked="checked"';
}
echo ' />'.PHP_EOL;
echo '</td>'.PHP_EOL;
echo '</tr>'.PHP_EOL;
echo '</table>'.PHP_EOL;

# Choix de la zone de vacances scolaires (France), uniquement si l'affichage des vacances et fériés est activé
if (Settings::get('show_holidays') == 'Oui'){
    echo '<hr />'.PHP_EOL;
    echo '<h3 id="vacances_scolaires">'.get_vocab('holidays_zone_msg').'</h3>'.PHP_EOL;
    echo '<table>'.PHP_EOL;
    echo '<tr>'.PHP_EOL;
    echo '<td>'.PHP_EOL;
    $vacances = simplexml_load_file('../vacances.xml');
    $libelle = $vacances->academies->children();
    $acad = array();
    foreach ($libelle as $key => $value) {
        if (!in_array($value['zone'], $acad)) {
            $acad[] .= $value['zone'];
        }
    }
    sort($acad);
    echo '<select class="form-control" name="holidays_zone">'.PHP_EOL;
    foreach ($acad as $key => $value) {
        echo '<option value="'.$value.'"';
        if (Settings::get('holidays_zone') == $value) {
            echo ' selected="selected"';
        }
        echo '>'.$value.'</option>'.PHP_EOL;
    }

    echo '</select>'.PHP_EOL;
    echo '</td>'.PHP_EOL;
    echo '</tr>'.PHP_EOL;
    echo '</table>'.PHP_EOL;
}
# Lors de l'édition d'un rapport, valeur par défaut en nombre de jours
# de l'intervalle de temps entre la date de début du rapport et la date de fin du rapport.
echo '<hr />'.PHP_EOL;
echo '<h3>'.get_vocab('default_report_days_msg')."</h3>\n";
echo '<p>'.get_vocab('default_report_days_explain').get_vocab('deux_points').PHP_EOL;
echo '<input type="number" name="default_report_days" value="'.Settings::get('default_report_days').'" size="5" min="1" />'.PHP_EOL;
# Formulaire de réservation
echo '</p>'.PHP_EOL;
echo '<hr />'.PHP_EOL;
echo '<h3>'.get_vocab('formulaire_reservation').'</h3>'.PHP_EOL;
echo '<p>'.get_vocab('longueur_liste_ressources').get_vocab('deux_points').PHP_EOL;
echo '<input type="number" name="longueur_liste_ressources_max" value="'.Settings::get('longueur_liste_ressources_max').'" size="5" min="2" />'.PHP_EOL;
/*
# nb_year_calendar permet de fixer la plage de choix de l'année dans le choix des dates de début et fin des réservations
# La plage s'étend de année_en_cours - $nb_year_calendar à année_en_cours + $nb_year_calendar
# Par exemple, si on fixe $nb_year_calendar = 5 et que l'on est en 2005, la plage de choix de l'année s'étendra de 2000 à 2010
echo "<hr /><h3>".get_vocab("nb_year_calendar_msg")."</h3>\n";
echo get_vocab("nb_year_calendar_explain").get_vocab("deux_points");
echo "<select name=\"nb_year_calendar\" size=\"1\">\n";
$i = 1;
while ($i < 101) {
    echo "<option value=\".$i.\"";
    if (Settings::get("nb_year_calendar") == $i)
        echo " selected=\"selected\" ";
    echo ">".(date("Y") - $i)." - ".(date("Y") + $i)."</option>\n";
    $i++;
}
echo "</select>\n";
*/
echo '<br />'.PHP_EOL;
echo '<br />'.PHP_EOL;
echo '</p>'.PHP_EOL;
echo '<div id="fixe" style="text-align:center;">'.PHP_EOL;
echo '<input class="btn btn-primary" type="submit" name="ok" value="'.get_vocab('save').'" style="font-variant: small-caps;"/>'.PHP_EOL;
echo '</div>';
echo '</form>';
?>
<!--MAJ David VOUE 23/01/2014 Script de validation du mail du destinataire -->
<script>
	jQuery.validator.setDefaults(
	{
		debug: false,
		success: "valid"
	});
	$( "#nom_formulaire" ).validate(
	{
		rules: {
			mail_destinataire:
			{
				required: true,
				email: true
			},
			webmaster_email:
			{
				required: true,
				email: false
			},
			technical_support_email:
			{
				required: true,
				email: false
			}
		}
	});
</script>
<script type="text/javascript">
	document.getElementById('title_home_page').focus();
</script>
<!-- fin de l'affichage de la colonne de droite et fermeture de la page -->
</div></section></body></html>
