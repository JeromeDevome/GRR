<?php
/**
 * admin_cgu.php
 * Interface permettant à l'administrateur de paramétrer les pages personnalisables : conditions générales d'utilisation, page d'accueil
 * Ce script fait partie de l'application GRR.
 * Dernière modification : $Date: 2024-10-10 11:32$
 * @author    JeromeB & Yan Naessens
 * @copyright Copyright 2003-2024 Team DEVOME - JeromeB
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
  if(($_POST['page']=='CGU')||($_POST['page']=='accueil')){
    if (!Pages::set($_POST['page'], $_POST['CGU'])) {
      echo get_vocab('admin_cgu_record_error').$_POST['page']." !<br />";
      die();
    }
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
echo "<h2>".get_vocab('admin_cgu_h2')."</h2>";

if(isset($_POST['page'])&&($_POST['page']=='CGU')){
  echo '<h3>'.get_vocab('cgu_titre').'</h3>'.PHP_EOL;
  echo "<p>".get_vocab('cgu_grr')."</p>";
}
elseif(isset($_POST['page'])&&($_POST['page']=='accueil')){
  echo "<h3>".get_vocab('admin_cgu_h3_accueil')."</h3>";
  echo "<p>".get_vocab('admin_cgu_accueil_explain')."</p>";
}
else{
  echo "<form action='./admin_cgu.php' id='choix' method='POST'>".PHP_EOL;
  echo "<p>".get_vocab('admin_cgu_choix').get_vocab('deux_points')."&nbsp;";
  echo "<label><input type='radio' name='page' value='CGU'/>".get_vocab('admin_cgu_cgu')."</label>&nbsp;";
  echo "<label><input type='radio' name='page' value='accueil'/>".get_vocab('admin_cgu_accueil')."</label>";
  echo "</p>";
  echo "<p class='center'><input type='submit' value='".get_vocab('OK')."'></p>";
  echo "</form>";
}
if(isset($_POST['page'])&&(($_POST['page']=='CGU')||($_POST['page']=='accueil'))){
  echo '<form action="./admin_cgu.php" id="contenu" method="post" >'.PHP_EOL;
  if (Settings::get('use_fckeditor') == 1) {
      echo '<script type="text/javascript" src="../js/ckeditor/ckeditor.js"></script>'.PHP_EOL;
  }
  if (Pages::get('use_fckeditor') != 1) {
      echo ' '.get_vocab('description_complete2');
  }
  if (Settings::get('use_fckeditor') == 1)
    echo '<textarea class="ckeditor" id="editor1" name="CGU" rows="20" cols="120">'.PHP_EOL;
  else
    echo "\n<textarea name=\"CGU\" rows=\"8\" cols=\"120\">";
  echo htmlspecialchars(Pages::get($_POST['page']));
  echo "</textarea>\n";
  echo "<input type='hidden' name='page' value='".$_POST['page']."'/>";
  echo '<div class="center">'.PHP_EOL;
  echo '<input class="btn btn-primary" type="submit" name="ok" value="'.get_vocab('save').'" style="font-variant: small-caps;"/>'.PHP_EOL;
  echo '</div>';
  echo '</form>';

}
// fin de l'affichage de la colonne de droite
echo '</div>';
end_page();
?>