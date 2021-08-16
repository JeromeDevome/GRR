<?php
/**
 * admin_config11.php
 * Interface permettant à l'administrateur la configuration de paramètres généraux présentant le site GRR
 * Ce script fait partie de l'application GRR.
 * Dernière modification : $Date: 2021-08-16 10:28$
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

$grr_script_name = "admin_config11.php";

include "../include/admin.inc.php";
if (!Settings::load())
    die(get_vocab('error_settings_load'));

$back = (isset($_SERVER['HTTP_REFERER']))? htmlspecialchars_decode($_SERVER['HTTP_REFERER'], ENT_QUOTES) : "./admin_accueil.php" ;
$_SESSION['chemin_retour'] = "admin_accueil.php";
$day   = date("d");
$month = date("m");
$year  = date("Y");
check_access(6, $back);
// enregistrement des données du formulaire
if (isset($_POST['title_home_page'])) {
    if (!Settings::set('title_home_page', $_POST['title_home_page'])) {
        echo $vocab['save_err']." title_home_page !<br />";
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
        } 
        else {
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
        } 
        else {
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
            } 
            else {
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
                    } 
                    else {
                        /* si c'est bon, je supprime l'image et je la remplace par l'image create avec gd */
                        $unlinkReturn = unlink($picturePath);
                        if (!$unlinkReturn) {
                            $msg .= get_vocab('errTempLogo')."\\n";
                            $ok = 'no';
                        } 
                        else {
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
                } 
                else {
                    $msg .= get_vocab('errImgTransfer')."\\n";
                    $ok = 'no';
                }
            }
        }
    } 
    elseif ($doc_file['name'] != '') {
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
// début et fin des réservations
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
if (isset($_POST['message_accueil'])) {
    if (!Settings::set('message_accueil', $_POST['message_accueil'])) {
        echo $vocab['save_err']." message_accueil !<br />";
        die();
    }
}
// Si pas de problème, message de confirmation
if (isset($_POST['ok'])) {
    $_SESSION['displ_msg'] = 'yes';
    if ($msg == '') 
        $msg = get_vocab('message_records');
    Header('Location: '.'admin_config11.php?msg='.$msg);
    exit();
}
if ((isset($_GET['msg'])) && isset($_SESSION['displ_msg']) && ($_SESSION['displ_msg'] == 'yes')) 
    $msg = $_GET['msg']; 
else 
    $msg = '';

// début du code html
# print the page header
start_page_w_header('', '', '', $type = 'with_session');
affiche_pop_up($msg, 'admin');
// Affichage de la colonne de gauche
include 'admin_col_gauche2.php';
echo '<div class="col-md-9 col-sm-8 col-xs-12">';
echo "<h2>".get_vocab('admin_config11.php')."</h2>";
//
// Config générale
//****************
//
echo '<form enctype="multipart/form-data" action="./admin_config11.php" id="mainForm" method="post" >'.PHP_EOL;
echo '<h3>'.get_vocab('miscellaneous').'</h3>'.PHP_EOL;
echo '<div class="form-group col-xs-12">'.PHP_EOL;
echo '<label class="col-sm-4 col-xs-12" for="title_home_page">'.get_vocab('title_home_page').'</label>'.PHP_EOL;
echo '<div class="col-sm-8 col-xs-12 control-label">'.PHP_EOL;
echo '<input class="form-control" type="text" name="title_home_page" id="title_home_page" size="40" value="'.Settings::get('title_home_page').'" />'.PHP_EOL;
echo '</div>'.PHP_EOL;
echo '</div>'.PHP_EOL;
echo '<div class="form-group col-xs-12">'.PHP_EOL;
echo '<label class="col-sm-4 col-xs-12" for="message_home_page">'.get_vocab('message_home_page').'</label>'.PHP_EOL;
echo '<div class="col-sm-8 col-xs-12 control-label">'.PHP_EOL;
echo '<textarea class="form-control" name="message_home_page" id="message_home_page" size="40" value="">'.PHP_EOL;
echo Settings::get('message_home_page');
echo '</textarea>';
echo '</div>'.PHP_EOL;
echo '</div>'.PHP_EOL;
echo '<div class="form-group col-xs-12">'.PHP_EOL;
echo '<label class="col-sm-4 col-xs-12" for="company">'.get_vocab('company').'</label>'.PHP_EOL;
echo '<div class="col-sm-8 col-xs-12 control-label">'.PHP_EOL;
echo '<input class="form-control" type="text" name="company" id="company" size="40" value="'.Settings::get('company').'" />'.PHP_EOL;
echo '</div>'.PHP_EOL;
echo '</div>'.PHP_EOL;
echo '<div class="form-group col-xs-12">'.PHP_EOL;
echo '<label class="col-sm-4 col-xs-12" for="grr_url">'.get_vocab('grr_url').'</label>'.PHP_EOL;
echo '<div class="col-sm-8 col-xs-12 control-label">'.PHP_EOL;
echo '<input class="form-control" type="text" name="grr_url" id="grr_url" size="40" value="'.Settings::get('grr_url').'" />'.PHP_EOL;
echo '</div>'.PHP_EOL;
echo '</div>'.PHP_EOL;
echo '<div class="col-xs-12">'.PHP_EOL;
echo '<p><input type="checkbox" name="use_grr_url" value="y" ';
if (Settings::get('use_grr_url') == 'y') 
    echo ' checked="checked" ';
echo ' />'.PHP_EOL;
echo '<em>'.get_vocab('grr_url_explain').'</em></p>'.PHP_EOL;
echo '</div>'.PHP_EOL;
echo '<div class="form-group col-xs-12">'.PHP_EOL;
echo '<label class="col-sm-4 col-xs-12" for="webmaster_name">'.get_vocab('webmaster_name').'</label>'.PHP_EOL;
echo '<div class="col-sm-8 col-xs-12 control-label">'.PHP_EOL;
echo '<input class="form-control" type="text" name="webmaster_name" id="webmaster_name" size="40" value="'.Settings::get('webmaster_name').'" />'.PHP_EOL;
echo '</div>'.PHP_EOL;
echo '</div>'.PHP_EOL;
echo '<div class="form-group col-xs-12">'.PHP_EOL;
echo '<label class="col-sm-4 col-xs-12" for="webmaster_email">'.get_vocab('webmaster_email').'</label>'.PHP_EOL;
echo '<div class="col-sm-8 col-xs-12 control-label">'.PHP_EOL;
echo '<input class="form-control" type="email" name="webmaster_email" id="webmaster_email" size="40" value="'.Settings::get('webmaster_email').'" />'.PHP_EOL;
echo '</div>'.PHP_EOL;
echo '<p><em>'.get_vocab('plusieurs_adresses_separees_points_virgules').'</em></p>'.PHP_EOL;
echo '</div>'.PHP_EOL;
echo '<div class="form-group col-xs-12">'.PHP_EOL;
echo '<label class="col-sm-4 col-xs-12" for="technical_support_email">'.get_vocab('technical_support_email').'</label>'.PHP_EOL;
echo '<div class="col-sm-8 col-xs-12 control-label">'.PHP_EOL;
echo '<input class="form-control" type="email" name="technical_support_email" id="technical_support_email" size="40" value="'.Settings::get('technical_support_email').'" />'.PHP_EOL;
echo '</div>'.PHP_EOL;
echo '<p><em>'.get_vocab('plusieurs_adresses_separees_points_virgules').'</em></p>'.PHP_EOL;
echo '</div>'.PHP_EOL;
// logo
echo '<h3>'.get_vocab('logo_msg').'</h3>'.PHP_EOL;
echo '<p>'.get_vocab('choisir_image_logo').'</p>'.PHP_EOL;
echo '<div class="form-group col-xs-12">'.PHP_EOL;
echo '<label class="col-sm-4 col-xs-12" for="doc_file">'.get_vocab('select_fichier').'</label>'.PHP_EOL;
echo '<div class="col-sm-8 col-xs-12">'.PHP_EOL;
echo '<input type="file" name="doc_file" />'.PHP_EOL;
echo '</div>'.PHP_EOL;
echo '</div>'.PHP_EOL;
echo '<div>'.PHP_EOL;
$nom_picture = '../images/'.Settings::get('logo');
if ((Settings::get('logo') != '') && (@file_exists($nom_picture))) {
    echo '<label for="sup_img">'.get_vocab('supprimer_logo').get_vocab('deux_points').'</label>'.PHP_EOL;
    echo '<input type="checkbox" name="sup_img" />'.PHP_EOL;
    echo '<img src="'.$nom_picture.'" class="image" alt="logo" title="'.$nom_picture.'"/>'.PHP_EOL;
}
echo '</div>'.PHP_EOL;
// nb de calendriers
echo '<h3>'.get_vocab('affichage_calendriers').'</h3>'.PHP_EOL;
echo '<div class="form-group col-xs-12">'.PHP_EOL;
echo '<label class="col-sm-8 col-xs-12" for="nb_calendar">'.get_vocab('affichage_calendriers_msg').get_vocab('deux_points').'</label>'.PHP_EOL;
echo '<div class="col-sm-4 col-xs-12">'.PHP_EOL;
echo '<input type="number" name="nb_calendar" value="'.Settings::get('nb_calendar').'" min="1" max="5" />'.PHP_EOL;
echo '</div>'.PHP_EOL;
echo '</div>'.PHP_EOL;
//
// Début et fin des réservations
//
echo '<hr /><h3>'.get_vocab('title_begin_end_bookings')."</h3>\n";
echo '<div class="form-group col-xs-12">'.PHP_EOL;
$typeDate = 'begin_';
$bday = strftime('%d', Settings::get('begin_bookings'));
$bmonth = strftime('%m', Settings::get('begin_bookings'));
$byear = strftime('%Y', Settings::get('begin_bookings'));

echo '<label class="col-sm-6 col-xs-12" for="mydate_begin_">'.get_vocab('begin_bookings').'</label>'.PHP_EOL;
echo '<div class="col-sm-6 col-xs-12 form-inline">'.PHP_EOL;
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
echo '<p><em>'.get_vocab('begin_bookings_explain').'</em></p>'.PHP_EOL;

echo '<div class="form-group col-xs-12">'.PHP_EOL;
$typeDate = 'end_';
$eday = strftime('%d', Settings::get('end_bookings'));
$emonth = strftime('%m', Settings::get('end_bookings'));
$eyear = strftime('%Y', Settings::get('end_bookings'));

echo '<label class="col-sm-6 col-xs-12" for="mydate_end_">'.get_vocab('end_bookings').'</label>'.PHP_EOL;
echo '<div class="col-sm-6 col-xs-12 form-inline">'.PHP_EOL;
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
echo '<p><em>'.get_vocab('end_bookings_explain').'</em></p>'.PHP_EOL;
// message personnalisé
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
// Adapter les fichiers de langue
echo '<h3>'.get_vocab('adapter_fichiers_langue').'</h3>'.PHP_EOL;
echo get_vocab('adapter_fichiers_langue_explain').PHP_EOL;
echo '<div id="fixe" style="text-align:center;">'.PHP_EOL;
echo '<input class="btn btn-primary" type="submit" name="ok" value="'.get_vocab('save').'" style="font-variant: small-caps;"/>'.PHP_EOL;
echo '</div>';
echo '</form>';
?>
<script type="text/javascript">
	document.getElementById('title_home_page').focus();
</script>
<!-- fin de l'affichage de la colonne de droite et fermeture de la page -->
</div></section></body></html>
