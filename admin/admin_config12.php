<?php
/**
 * admin_config12.php
 * Interface permettant à l'administrateur la configuration de certains paramètres d'affichage
 * Ce script fait partie de l'application GRR.
 * Dernière modification : $Date: 2025-05-06 17:40$
 * @author    Laurent Delineau & JeromeB &  Bouteillier Nicolas & Yan Naessens
 * @copyright Copyright 2003-2025 Team DEVOME - JeromeB
 * @link      http://www.gnu.org/licenses/licenses.html
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 */

$grr_script_name = "admin_config12.php";

include "../include/admin.inc.php";

$back = (isset($_SERVER['HTTP_REFERER']))? htmlspecialchars_decode($_SERVER['HTTP_REFERER'], ENT_QUOTES) : "./admin_accueil.php" ;
$_SESSION['chemin_retour'] = "admin_accueil.php";
$day   = date("d");
$month = date("m");
$year  = date("Y");
check_access(6, $back);
// fonctions locales
function encode_tableau($a,$modele){
    // suppose que l'on a un tableau $a dont les entrées sont parmi les éléments de $modele
    // rend une chaine de {V,F} correspondant à la présence/absence de l'entrée dans le tableau $a
    $out='';
    foreach ($modele as $i){
        if (isset($a[$i]))
            $out .= 'V';
        else $out .= 'F';
    }
    return $out;
}
// types de plannings
$plan=array('day','week','week_all','month','month_all','month_all2','year','year_all');
// options d'affichage
$opt = array('horaires','beneficiaire','short_desc','description','create_by','type','participants');

// enregistrement des données du formulaire
$msg="";
// Type d'affichage des listes des domaines et des ressources
if (isset($_POST['area_list_format'])) {
    if (!Settings::set('area_list_format', $_POST['area_list_format']))
        $msg.= $vocab['save_err']." area_list_format !<br />";
}
// site par défaut
if (isset($_POST['id_site'])) {
    if (!Settings::set('default_site', $_POST['id_site']))
        $msg.= $vocab['save_err']." default_site !<br />";
}
// domaine par défaut
if (isset($_POST['id_area'])) {
    if (!Settings::set('default_area', $_POST['id_area']))
        $msg.= $vocab['save_err']." default_area !<br />";
}
// planning par défaut
if (isset($_POST['id_room'])) {
    if (!Settings::set('default_room', $_POST['id_room']))
        $msg.= $vocab['save_err']." default_room !<br />";
}
// Style/thème
if (isset($_POST['default_css'])) {
    if (!Settings::set('default_css', $_POST['default_css']))
        $msg.= $vocab['save_err']." default_css !<br />";
}
// langage
if (isset($_POST['default_language'])) {
    if (!Settings::set('default_language', $_POST['default_language']))
        $msg.= $vocab['save_err']." default_language !<br />";
    unset($_SESSION['default_language']);
}
// menu_gauche
if (isset($_POST['menu_gauche'])) {
    if (!Settings::set('menu_gauche', $_POST['menu_gauche']))
        $msg.= $vocab['save_err']." menu_gauche !<br />";
}
// formulaire de contact
if (isset($_POST['mail_etat_destinataire'])) {
    if (!Settings::set('mail_etat_destinataire', $_POST['mail_etat_destinataire']))
        $msg.= $vocab['save_err']." mail_etat_destinataire !<br />";
}
if (isset($_POST['mail_destinataire'])) {
    if (!Settings::set('mail_destinataire', $_POST['mail_destinataire']))
        $msg.= $vocab['save_err']." mail_destinataire !<br />";
}
// limitation des réservations par formulaire 
if (isset($_POST['nb_max_resa_form'])){
    if (!Settings::set('nb_max_resa_form', clean_input($_POST['nb_max_resa_form'])))
        $msg.= $vocab['save_err']." nb_max_resa_form !<br />";
}
// Affichage de l'adresse email
if (isset($_POST['display_level_email'])) {
    if (!Settings::set('display_level_email', $_POST['display_level_email']))
        $msg.= $vocab['save_err']." display_level_email !<br />";
}
/*-----MAJ Loïs THOMAS  --> Affichage de la page view_entry pour les réservations  -----*/
if (isset($_POST['display_level_view_entry'])) {
    if (!Settings::set('display_level_view_entry', $_POST['display_level_view_entry']))
        $msg.= $vocab['save_err']." display_level_view_entry !<br />";
}
// remplissage de la description brève
if (isset($_POST['remplissage_description_breve'])) {
    if (!Settings::set('remplissage_description_breve', $_POST['remplissage_description_breve']))
        $msg.= $vocab['save_err']." remplissage_description_breve !<br />";
}
// remplissage de la description complète
if (isset($_POST['remplissage_description_complete'])) {
    if (!Settings::set('remplissage_description_complete', $_POST['remplissage_description_complete']))
        $msg.= $vocab['save_err']." remplissage_description_complete !<br />";
}
// pview_new_windows
if (isset($_POST['pview_new_windows'])) {
    if (!Settings::set('pview_new_windows', $_POST['pview_new_windows']))
        $msg.= $vocab['save_err']." pview_new_windows !<br />";
}

// pour le traitement des checkboxes
if (!empty($_POST)) // évite d'effacer les enregistrements lors du deuxième passage
{
    // display_info_bulle
    $display_info_bulle = 1;
    if (isset($_POST['display_info_bulle']))
        $display_info_bulle = 0;
    if (!Settings::set('display_info_bulle', $display_info_bulle))
        $msg.= $vocab['save_err']." display_info_bulle !<br />";
    // contenu des cellules
    foreach ($plan as $p){
        $name = 'cell_'.$p;
        if (isset($_POST[$name])){
            $choix = encode_tableau($_POST[$name],$opt);
            if (!Settings::set($name, $choix))
                $msg.= $vocab['save_err'].' '.$name." !<br />";
        }
        else{ // tout est décoché
            if(!Settings::set($name, 'FFFFFFF'))
                $msg.= $vocab['save_err'].' '.$name.' !<br />';
        }
    }
    // info-bulles
    foreach ($plan as $p){
        $name = 'popup_'.$p;
        if (isset($_POST[$name])){
            $choix = encode_tableau($_POST[$name],$opt);
            if (!Settings::set($name, $choix))
                $msg.= $vocab['save_err'].' '.$name." !<br />";
        }
        else{ // tout est décoché
            if(!Settings::set($name, 'FFFFFFF'))
                $msg.= $vocab['save_err'].' '.$name.' !<br />';
        }
    }
    // Option periodicite
    $option_periodicite = 'n';
    if (isset($_POST['periodicite']))
        $option_periodicite = 'y';
    if (!Settings::set('periodicite', $option_periodicite))
        $msg.= $vocab['save_err']." periodicite !<br />";
    // Selecteur de date direct
    $select_date_directe = 'n';
    if(isset($_POST['select_date_directe']))
        $select_date_directe = 'y';
    if (!Settings::set('select_date_directe', $select_date_directe))
        $msg.= $vocab['save_err']." select_date_directe !<br />";
    /*-----MAJ Loïs THOMAS  -->Affichage ou non de la legende -----*/
    $affiche_legende = 1; // i.e. ne pas afficher
    if (isset($_POST['legend'])) 
        $affiche_legende = 0;
    if (!Settings::set('legend', $affiche_legende))
        $msg.= $vocab['save_err']." legend !<br />";
    // Affichage imprimante
    $affiche_imprimante = 1; // i.e. ne pas afficher
    if (isset($_POST['imprimante']))
        $affiche_imprimante = 0;
    if (!Settings::set('imprimante', $affiche_imprimante))
        $msg.= $vocab['save_err']." imprimante !<br />";
    // Affichage pdf 
    $affiche_pdf = 0; // i.e. ne pas afficher
    if (isset($_POST['pdf']))
        $affiche_pdf = 1;
    if (!Settings::set('pdf', $affiche_pdf))
        $msg.= $vocab['save_err']." affichage pdf !<br />";
    // courrier
    $affiche_courrier = 'n';
    if (isset($_POST['show_courrier']))
        $affiche_courrier = 'y';
    if (!Settings::set('show_courrier', $affiche_courrier))
        $msg.= $vocab['save_err']." show_courrier !<br />";
    // vacances et jours fériés
    $affiche_vacances = 'Non';
    if (isset($_POST['show_holidays'])) 
        $affiche_vacances = 'Oui';
    if (!Settings::set('show_holidays', $affiche_vacances))
        $msg.= $vocab['save_err']." show_holidays !<br />";
}
// zone de vacances scolaires
if (isset($_POST['holidays_zone'])) {
    if (!Settings::set('holidays_zone', $_POST['holidays_zone']))
        $msg.= $vocab['save_err']." holidays_zone !<br />";
}
# Lors de l'édition d'un rapport, valeur par défaut en nombre de jours
# de l'intervalle de temps entre la date de début du rapport et la date de fin du rapport.
if (isset($_POST['default_report_days'])) {
    $default_report_days = intval($_POST['default_report_days']);
    if ($default_report_days <= 0) {
        $default_report_days = 0;
    }
    if (!Settings::set('default_report_days', $default_report_days))
        $msg.= $vocab['save_err']." default_report_days !<br />";
}
if (isset($_POST['longueur_liste_ressources_max'])) {
    $longueur_liste_ressources_max = intval($_POST['longueur_liste_ressources_max']);
    if ($longueur_liste_ressources_max <= 0) {
        $longueur_liste_ressources_max = 1;
    }
    if (!Settings::set('longueur_liste_ressources_max', $_POST['longueur_liste_ressources_max']))
        $msg.= $vocab['save_err']." longueur_liste_ressources_max !<br />";
}
/* valeurs existant dans la base, mais pas définies ici
// Affichage type 
if (isset($_POST['type'])) {
    if (!Settings::set('type', $_POST['type'])) {
        echo $vocab['save_err']." affichage type !<br />";
        die();
    }
}

if (isset($_POST['allow_pdf'])) {
    if (!Settings::set('allow_pdf', $_POST['allow_pdf'])) {
        echo $vocab['save_err']." allow_pdf !<br />";
        die();
    }
}
*/
$msg = '';
// Si pas de problème, message de confirmation
if (isset($_POST['ok'])) {
    $_SESSION['displ_msg'] = 'yes';
    if ($msg == '') {
        $msg = get_vocab('message_records');
    }
    Header('Location: '.'admin_config12.php?msg='.$msg);
    exit();
}
if ((isset($_GET['msg'])) && isset($_SESSION['displ_msg']) && ($_SESSION['displ_msg'] == 'yes')) 
    $msg = $_GET['msg'];
else 
    $msg = '';
// données
if (Settings::get('module_multisite') == 'Oui') {
    $use_site = 'y';
} else {
    $use_site = 'n';
}
/*
 * Liste des sites
 */
if (Settings::get('module_multisite') == 'Oui') {
    $sql = 'SELECT id,sitecode,sitename
	FROM '.TABLE_PREFIX.'_site
	ORDER BY id ASC';
    $Sites = grr_sql_query($sql);
}
// liste des zones de vacances scolaires
$vacances = simplexml_load_file('../vacances.xml');
$libelle = $vacances->academies->children();
$acad = array();
foreach ($libelle as $key => $value) {
    if (!in_array($value['zone'], $acad)) {
        $acad[] .= $value['zone'];
    }
}
sort($acad);
// contenu des cellules
$choix = array('cell_day' => array ( 'horaires' => 1));
$choix = array();
foreach ($plan as $p){
    $name = 'cell_'.$p;
    if (Settings::get($name) != NULL)
        $choix[$name] = decode_options(Settings::get($name),$opt);
    $name = 'popup_'.$p;
    if (Settings::get($name) != NULL)
        $choix[$name] = decode_options(Settings::get($name),$opt);
}

// début du code html
// haut de page et début de section
start_page_w_header('', '', '', $type = 'with_session');
affiche_pop_up($msg, 'admin');

// Affichage de la colonne de gauche
include 'admin_col_gauche2.php';
//echo "<p>".get_vocab('mess_avertissement_config')."</p>";
echo '<div class="col col-md-9 col-sm-8 col-xs-12">'; // colonne de droite
echo "<h2>".get_vocab('admin_config12.php')."</h2>";
echo '<form action="./admin_config12.php" id="mainForm" method="post" >'.PHP_EOL;
//
// Configuration de l'affichage par défaut
//****************************************
//
echo '<h3>'.get_vocab('default_parameter_values_title').'</h3>'.PHP_EOL;
echo '<p>'.get_vocab('explain_default_parameter').'</p>'.PHP_EOL;
//
// Choix du type d'affichage
//
echo '<h4>'.get_vocab('explain_area_list_format').'</h4>'.PHP_EOL;
echo '<div>'.PHP_EOL;
echo '<input type="radio" id="alf1" name="area_list_format" value="list" ';
if (Settings::get('area_list_format') == 'list') {
    echo 'checked';
}
echo ' />'.PHP_EOL;
echo '<label for="alf1">'.get_vocab('liste_area_list_format').'</label>'.PHP_EOL;
echo '<br />'.PHP_EOL;
echo '<input type="radio" id="alf2" name="area_list_format" value="select" ';
if (Settings::get('area_list_format') == 'select') {
    echo 'checked';
}
echo ' />'.PHP_EOL;
echo '<label for="alf2">'.get_vocab('select_area_list_format').'</label>'.PHP_EOL;
echo '<br />'.PHP_EOL;
echo '<input type="radio" id="alf3" name="area_list_format" value="item" ';
if (Settings::get('area_list_format') == 'item') {
    echo 'checked';
}
echo ' />'.PHP_EOL;
echo '<label for="alf3">'.get_vocab('item_area_list_format').'</label>'.PHP_EOL;
echo '<br />'.PHP_EOL;
echo '</div>'.PHP_EOL;
if ($use_site == 'y') 
    echo('<h4>'.get_vocab('explain_default_area_and_room_and_site').'</h4>');
else 
    echo('<h4>'.get_vocab('explain_default_area_and_room').'</h4>');
// sélecteur de site
if ($use_site == 'y') {
    echo '<div id="div_liste_sites" class="col col-xs-12">'.PHP_EOL;
    echo '<div class="form-group">'.PHP_EOL;
    echo '<label for="id_site" class="control-label col-md-3 col-sm-3 col-xs-4">'.get_vocab('default_site').get_vocab('deux_points').'</label>'.PHP_EOL;
    echo '<div class="col col-md-4 col-sm-6 col-xs-8">'.PHP_EOL;
    echo '<select class="form-control" id="id_site" name="id_site" onchange="modifier_liste_domaines();modifier_liste_ressources(2)">'.PHP_EOL;
    echo '<option value="-1">'.get_vocab('choose_a_site').'</option>'.PHP_EOL;
    foreach ($Sites as $row) {
        echo '<option value="'.$row['id'].'"';
        if (Settings::get('default_site') == $row['id']) {
            echo ' selected="selected" ';
        }
        echo '>'.htmlspecialchars($row['sitename']);
        echo '</option>'."\n";
    }
    echo '</select>'.PHP_EOL;
    echo '</div>'.PHP_EOL;
    echo '</div>'.PHP_EOL;
    echo '</div>'.PHP_EOL;
} 
else {
    echo '<input class="form-control" type="hidden" id="id_site" name="id_site" value="-1" />';
}
/*
 * Liste des domaines
 */
echo '<div id="div_liste_domaines" class="col col-xs-12">'.PHP_EOL;
// Ici, on insère la liste des domaines avec de l'ajax !
echo '</div>'.PHP_EOL;
/*
 * Liste des ressources
 */
echo '<div id="div_liste_ressources" class="col col-xs-12">'.PHP_EOL;
//echo '<input class="form-control" type="hidden" id="id_area" name="id_area" value="'.Settings::get('default_area').'" />'.PHP_EOL;
// Ici, on insère la liste des ressouces avec de l'ajax !
echo '</div>'.PHP_EOL;
//
// Choix de la feuille de style
//
echo '<h4>'.get_vocab('explain_css').'</h4>'.PHP_EOL;
echo '<div class="form-group col-xs-12" >'.PHP_EOL;
echo '<label for="default_css" class="control-label col-sm-4 col-xs-12">'.get_vocab('choose_css').'</label>'.PHP_EOL;
echo '<div class="col col-sm-4 col-xs-12"><select class="form-control" name="default_css" id="default_css">'.PHP_EOL;
$i = 0;
while ($i < count($liste_themes)) {
    echo "<option value='".$liste_themes[$i]."'";
    if (Settings::get('default_css') == $liste_themes[$i]) {
        echo ' selected="selected"';
    }
    echo ' >'.encode_message_utf8($liste_name_themes[$i]).'</option>';
    ++$i;
}
echo "</select></div>".PHP_EOL;
echo '</div>'.PHP_EOL;
//
// Choix de la langue
//
echo '<h4>'.get_vocab('choose_language').'</h4>'.PHP_EOL;
echo '<div class="form-group col-xs-12" >'.PHP_EOL;
echo '<label for="default_language" class="control-label col-sm-4 col-xs-12">'.get_vocab('choose_css').'</label>'.PHP_EOL;
echo '<div class="col col-sm-4 col-xs-12"><select class="form-control" name="default_language" id="default_language">'.PHP_EOL;
$i = 0;
while ($i < count($liste_language)) {
    echo "<option value='".$liste_language[$i]."'";
    if (Settings::get('default_language') == $liste_language[$i]) {
        echo ' selected="selected"';
    }
    echo ' >'.encode_message_utf8($liste_name_language[$i]).'</option>'.PHP_EOL;
    ++$i;
}
echo '</select></div>'.PHP_EOL;
echo '</div>'.PHP_EOL;
#mail_etat_destinataire = 0 //Le formulaire de contact est désactivé (0 par défaut)
#mail_etat_destinataire = 1 //Le formulaire de contact est activé
#mail_etat_destinataire = 2 //Le formulaire de contact est activé uniquement pour les visiteurs connectés
#mail_etat_destinataire = 3 //Le formulaire de contact est activé et accessible depuis la page de login
echo '<hr />'.PHP_EOL;
echo '<h3>'.get_vocab('display_mail_etat_destinataire').'</h3>'.PHP_EOL;
echo '<p>'.get_vocab('display_mail_etat_destinataire_1').'</p>'.PHP_EOL;
echo '<div class="form-inline">'.PHP_EOL;
echo '<input type="radio" id="dmed0" name="mail_etat_destinataire" value="0" ';
if (Settings::get('mail_etat_destinataire') == '0') {
    echo 'checked';
}
echo ' />'.PHP_EOL;
echo '<label for="dmed0">'.get_vocab('display_mail_etat_destinataire_2').'</label>'.PHP_EOL;
echo '<br />'.PHP_EOL;
echo '<input type="radio" id="dmed1" name="mail_etat_destinataire" value="1" ';
if (Settings::get('mail_etat_destinataire') == '1') {
    echo 'checked';
}
echo ' />'.PHP_EOL;
echo '<label for="dmed1">'.get_vocab('display_mail_etat_destinataire_3').'</label>'.PHP_EOL;
echo '<br />'.PHP_EOL;
echo '<input type="radio" id="dmed3" name="mail_etat_destinataire" value="3" ';
if (Settings::get('mail_etat_destinataire') == '3') {
    echo 'checked';
}
echo ' />'.PHP_EOL;
echo '<label for="dmed3">'.get_vocab('display_mail_etat_destinataire_5').'</label>'.PHP_EOL;
echo '<br />'.PHP_EOL;
echo '<input type="radio" id="dmed2" name="mail_etat_destinataire" value="2" ';
if (Settings::get('mail_etat_destinataire') == '2') {
    echo 'checked';
}
echo ' />'.PHP_EOL;
echo '<label for="dmed2">'.get_vocab('display_mail_etat_destinataire_4').'</label>'.PHP_EOL;
echo '<br />'.PHP_EOL;
echo '<label for="mail_destinataire">'.get_vocab('display_mail_destinataire').'</label>'.PHP_EOL;
echo '<input class="form-control" type="text" id="mail_destinataire" name="mail_destinataire" value="'.Settings::get('mail_destinataire').'" size="30">'.PHP_EOL;
echo '<br />'.PHP_EOL;
echo '<label for="nb_max_resa_form">'.get_vocab('nb_max_resa_form').'</label>'.PHP_EOL;
echo '<input type="number" name="nb_max_resa_form" id="nb_max_resa_form" value="'.Settings::get('nb_max_resa_form').'" size="5" min="-1" />'.PHP_EOL;
echo '</div>'.PHP_EOL;
#Choix  de l'affichage du bouton "afficher le menu de gauche ou non"
#SQL : menu_gauche==1  //le bouton s'affiche par default
# menu_gauche==0 //le bouton ne s'affiche pas par default
# menu_gauche==2 //le menu s'affiche en haut
echo '<hr />'.PHP_EOL;
echo '<h3>'.get_vocab('display_menu').'</h3>'.PHP_EOL;
echo '<p>'.get_vocab('display_menu_1').'</p>'.PHP_EOL;
echo '<div>'.PHP_EOL;
echo '<input type="radio" id="mg0" name="menu_gauche" value="0" ';
if (Settings::get('menu_gauche') == '0') {
    echo 'checked';
}
echo ' />'.PHP_EOL;
echo '<label for="mg0">'.get_vocab('display_menu_2').'</label>'.PHP_EOL;
echo '<br />'.PHP_EOL;
echo '<input type="radio" id="mg1" name="menu_gauche" value="1" ';
if (Settings::get('menu_gauche') == '1') {
    echo 'checked';
}
echo ' />'.PHP_EOL;
echo '<label for="mg1">'.get_vocab('display_menu_3').'</label>'.PHP_EOL;
echo '<br />'.PHP_EOL;
echo '<input type="radio" id="mg2" name="menu_gauche" value="2" ';
if (Settings::get('menu_gauche') == '2') {
    echo 'checked';
}
echo ' />'.PHP_EOL;
echo '<label for="mg2">'.get_vocab('display_menu_4').'</label>'.PHP_EOL;
echo '<br />'.PHP_EOL;
echo '</div>'.PHP_EOL;
#
# Affichage du contenu des "info-bulles" des réservations, dans les vues journées, semaine et mois.
# display_info_bulle = 0 : pas d'info-bulle.
# display_info_bulle = 1 : affichage des info-bulles
echo '<hr />'.PHP_EOL;
echo '<h3>'.get_vocab('display_info_bulle_msg').'</h3>'.PHP_EOL;
echo '<div>'.PHP_EOL;
echo '<input type="checkbox" id="display_info_bulle" name="display_info_bulle" value="0" ';
if (Settings::get('display_info_bulle') == '0') {
    echo 'checked';
}
echo ' />'.PHP_EOL;
echo '<label for="display_info_bulle">'.get_vocab('info_bulle0').'</label>'.PHP_EOL;
echo '<br />';
echo '</div>'.PHP_EOL;
// contenu des info-bulles
echo '<div id="contenu_popup">'.PHP_EOL;
echo '<table class="table">'.PHP_EOL;
echo '    <thead>'.PHP_EOL;
echo '    <tr>'.PHP_EOL;
echo '      <th>'.'options \\ planning'.'</th>';
foreach($plan as $t){
    echo '<th>'.$t.'</th>';
}    
echo '    </tr>'.PHP_EOL;
echo '  </thead>'.PHP_EOL;
echo '  <tbody>'.PHP_EOL;
foreach($opt as $op){
    echo '<tr>';
    echo '<td>'.$op.'</td>';
    foreach($plan as $t){
        $name = 'popup_'.$t;
        $check = (isset($choix[$name][$op]) && $choix[$name][$op])? 'checked': '';
        echo '<td><input type="checkbox" name="popup_'.$t.'['.$op.']" value="'.$op.'" '.$check.'/></td>';
    }
    echo '</tr>';
}
echo '  </tbody>'.PHP_EOL;
echo '</table>'.PHP_EOL;
echo '</div>'.PHP_EOL;
// contenu des cellules
echo '<div id="contenu_cellules">'.PHP_EOL;
echo '<h3>'.get_vocab('display_planning_resa').'</h3>';
echo '<p><em>'.get_vocab('display_planning_resa_msg').'</em></p>';
echo '<table class="table">'.PHP_EOL;
echo '    <thead>'.PHP_EOL;
echo '    <tr>'.PHP_EOL;
echo '      <th>'.'options \\ planning'.'</th>';
foreach($plan as $t){
    echo '<th>'.$t.'</th>';
}    
echo '    </tr>'.PHP_EOL;
echo '  </thead>'.PHP_EOL;
echo '  <tbody>'.PHP_EOL;
foreach($opt as $op){
    echo '<tr>';
    echo '<td>'.$op.'</td>';
    foreach($plan as $t){
        $name = 'cell_'.$t;
        $check = (isset($choix[$name][$op]) && $choix[$name][$op])? 'checked': '';
        echo '<td><input type="checkbox" name=" cell_'.$t.'['.$op.']" value="'.$op.'" '.$check.'/></td>';
    }
    echo '</tr>';
}
echo '  </tbody>'.PHP_EOL;
echo '</table>'.PHP_EOL;
echo '</div>'.PHP_EOL;

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
echo '<div class="form-inline">'.PHP_EOL;
echo '<input type="radio" id="dle0" name="display_level_email" value="0" ';
if (Settings::get('display_level_email') == '0') {
    echo 'checked';
}
echo ' />'.PHP_EOL;
echo '<label for="dle0">'.get_vocab('visu_fiche_description0').'</label>'.PHP_EOL;
echo '<br />'.PHP_EOL;
echo '<input type="radio" id="dle1" name="display_level_email" value="1" ';
if (Settings::get('display_level_email') == '1') {
    echo 'checked';
}
echo ' />'.PHP_EOL;
echo '<label for="dle1">'.get_vocab('visu_fiche_description1').'</label>'.PHP_EOL;
echo '<br />'.PHP_EOL;
echo '<input type="radio" id="dle2" name="display_level_email" value="2" ';
if (Settings::get('display_level_email') == '2') {
    echo 'checked';
}
echo ' />'.PHP_EOL;
echo '<label for="dle2">'.get_vocab('visu_fiche_description2').'</label>'.PHP_EOL;
echo '<br />'.PHP_EOL;
echo '<input type="radio" id="dle3" name="display_level_email" value="3" ';
if (Settings::get('display_level_email') == '3') {
    echo 'checked';
}
echo ' />'.PHP_EOL;
echo '<label for="dle3">'.get_vocab('visu_fiche_description3').'</label>'.PHP_EOL;
echo '<br />'.PHP_EOL;
echo '<input type="radio" id="dle4" name="display_level_email" value="4" ';
if (Settings::get('display_level_email') == '4') {
    echo 'checked';
}
echo ' />'.PHP_EOL;
echo '<label for="dle4">'.get_vocab('visu_fiche_description4').'</label>'.PHP_EOL;
echo '<br />'.PHP_EOL;

if (Settings::get('module_multisite') == 'Oui') {
    echo '<input type="radio" id="dle5" name="display_level_email" value="5" ';
    if (Settings::get('display_level_email') == '5') {
        echo 'checked';
    }
    echo ' />'.PHP_EOL;
    echo '<label for="dle5">'.get_vocab('visu_fiche_description5').'</label>'.PHP_EOL;
    echo '<br />'.PHP_EOL;
}
echo '<input type="radio" id="dle6" name="display_level_email" value="6" ';
if (Settings::get('display_level_email') == '6') {
    echo 'checked';
}
echo ' />'.PHP_EOL;
echo '<label for="dle6">'.get_vocab('visu_fiche_description6').'</label>'.PHP_EOL;
echo '<br />'.PHP_EOL;
echo '</div>'.PHP_EOL;
#Affichage de view_entry sous forme de page ou de popup
$dlve = intval(Settings::get('display_level_view_entry'));
echo '<hr />'.PHP_EOL;
echo '<h3>'.get_vocab('display_level_view_entry').'</h3>'.PHP_EOL;
echo '<div>'.PHP_EOL;
echo '<input type="radio" id="dlve0" name="display_level_view_entry" value="0" ';
if ($dlve == 0) {
    echo 'checked';
}
echo ' />'.PHP_EOL;
echo '<label for="dlve0">'.get_vocab('display_level_view_entry_0').'</label>'.PHP_EOL;
echo '<br />'.PHP_EOL;
echo '<input type="radio" id="dlve1" name="display_level_view_entry" value="1" ';
if ($dlve == 1) {
    echo 'checked';
}
echo ' />'.PHP_EOL;
echo '<label for="dlve1">'.get_vocab('display_level_view_entry_1').'</label>'.PHP_EOL;
echo '<br />'.PHP_EOL;
echo ' </div>'.PHP_EOL;
# Remplissage de la description brève
echo '<hr />'.PHP_EOL;
echo '<h3>'.get_vocab('remplissage_description_breve_msg').'</h3>'.PHP_EOL;
echo '<div>'.PHP_EOL;
echo '<input type="radio" id="rdb0" name="remplissage_description_breve" value="0" ';
if (Settings::get('remplissage_description_breve') == '0') {
    echo 'checked';
}
echo ' />'.PHP_EOL;
echo '<label for="rdb0">'.get_vocab('remplissage_description_breve0').'</label>'.PHP_EOL;
echo '<br />'.PHP_EOL;
echo '<input type="radio" id="rdb1" name="remplissage_description_breve" value="1" ';
if (Settings::get('remplissage_description_breve') == '1') {
    echo 'checked';
}
echo ' />'.PHP_EOL;
echo '<label for="rdb1">'.get_vocab('remplissage_description_breve1').'</label>'.PHP_EOL;
echo '<br />'.PHP_EOL;
echo '<input type="radio" id="rdb2" name="remplissage_description_breve" value="2" ';
if (Settings::get('remplissage_description_breve') == '2') {
    echo 'checked';
}
echo ' />'.PHP_EOL;
echo '<label for="rdb2">'.get_vocab('remplissage_description_breve2').'</label>'.PHP_EOL;
echo '<br />'.PHP_EOL;
echo '</div>'.PHP_EOL;
# Remplissage de la description complète
echo '<hr />'.PHP_EOL;
echo '<h3>'.get_vocab('remplissage_description_complete_msg').'</h3>'.PHP_EOL;
echo '<div>'.PHP_EOL;
echo '<input type="radio" id="rdc0" name="remplissage_description_complete" value="0" ';
if (Settings::get('remplissage_description_complete') == '0') {
    echo 'checked';
}
echo ' />'.PHP_EOL;
echo '<label for="rdc0">'.get_vocab('remplissage_description_complete0').'</label>'.PHP_EOL;
echo '<br />'.PHP_EOL;
echo '<input type="radio" id="rdc1" name="remplissage_description_complete" value="1" ';
if (Settings::get('remplissage_description_complete') == '1') {
    echo 'checked';
}
echo ' />'.PHP_EOL;
echo '<label for="rdc1">'.get_vocab('remplissage_description_complete1').'</label>'.PHP_EOL;
echo '<br />'.PHP_EOL;
echo '</div>'.PHP_EOL;
# Ouvrir les pages au format imprimable dans une nouvelle fenêtre du navigateur (0 pour non et 1 pour oui)
echo '<hr />'.PHP_EOL;
echo '<h3>'.get_vocab('pview_new_windows_msg').'</h3>'.PHP_EOL;
echo '<div>'.PHP_EOL;
echo '<input type="radio" id="pnw0" name="pview_new_windows" value="0" ';
if (Settings::get('pview_new_windows') == '0') {
    echo 'checked';
}
echo ' />'.PHP_EOL;
echo '<label for="pnw0">'.get_vocab('pview_new_windows0').'</label>'.PHP_EOL;
echo '<br />'.PHP_EOL;
echo '<input type="radio" id="pnw1" name="pview_new_windows" value="1" ';
if (Settings::get('pview_new_windows') == '1') {
    echo 'checked';
}
echo ' />'.PHP_EOL;
echo '<label for="pnw1">'.get_vocab('pview_new_windows1').'</label>'.PHP_EOL;
echo '<br />'.PHP_EOL;
echo '</div>'.PHP_EOL;
# Autoriser la periodicité
echo '<hr />'.PHP_EOL;
echo '<h3>'.get_vocab('periodicite_msg').'</h3>'.PHP_EOL;
echo '<div>'.PHP_EOL;
echo "<input type='checkbox' name='periodicite' id='periodicite' value='y' ";
if (Settings::get('periodicite') == 'y') {
    echo 'checked';
}
echo ' />'.PHP_EOL;
echo '<label for="periodicite">'.get_vocab('periodicite_msg1').'</label>'.PHP_EOL;
echo '</div>'.PHP_EOL;
# affichages divers
echo '<hr />'.PHP_EOL;
echo '<h3>'.get_vocab('affichages_divers').'</h3>'.PHP_EOL;
echo '<p>'.get_vocab('affichages_divers_explain').'</p>'.PHP_EOL;
# Afficher un sélecteur de date direct avec calendrier JQuery-datepicker
echo '<div>'.PHP_EOL;
echo "<input type='checkbox' name='select_date_directe' id='select_date_directe' value='y' ";
if (Settings::get('select_date_directe') == 'y') {
    echo 'checked';
}
echo ' />'.PHP_EOL;
echo '<label for="select_date_directe">'.get_vocab('select_date_directe').'</label>'.PHP_EOL;
echo '<br />';
echo '</div>'.PHP_EOL;
echo '<hr />'.PHP_EOL;
# Afficher la legende en couleur dans le menu gauche
echo '<div>'.PHP_EOL;
echo "<input type='checkbox' id='legend' name='legend' value='0' ";
if (Settings::get('legend') == '0') {
    echo 'checked';
}
echo ' />'.PHP_EOL;
echo '<label for="legend">'.get_vocab('legend_msg').'</label>'.PHP_EOL;
echo '<br />';
echo '</div>'.PHP_EOL;
echo '<hr />'.PHP_EOL;
# Afficher l'imprimante
echo '<div>'.PHP_EOL;
echo '<input type="checkbox" name="imprimante" id="imprimante" value="0" ';
if (Settings::get('imprimante') == '0') {
    echo 'checked';
}
echo ' />'.PHP_EOL;
echo '<label for="imprimante">'.get_vocab('imprimante_msg').'</label>'.PHP_EOL;
echo '<br />';
echo '</div>'.PHP_EOL;
echo '<hr />'.PHP_EOL;
# Affichage pdf 
echo '<div>'.PHP_EOL;
echo '<input type="checkbox" name="pdf" id="pdf" value="1" ';
if (Settings::get('pdf') == '1') {
    echo 'checked';
}
echo ' />'.PHP_EOL;
echo '<label for="pdf">'.get_vocab('affichage_pdf').'</label>'.PHP_EOL;
echo '<br />';
echo '</div>'.PHP_EOL;
echo '<hr />'.PHP_EOL;
# Afficher courrier de validation
echo '<div>'.PHP_EOL;
echo '<input type="checkbox" name="show_courrier" id="show_courrier" value="y" ';
if (Settings::get('show_courrier') == 'y') {
    echo 'checked';
}
echo ' />'.PHP_EOL;
echo '<label for="show_courrier">'.get_vocab('courrier_msg').'</label>'.PHP_EOL;
echo '<br />';
echo '</div>'.PHP_EOL;
echo '<hr />'.PHP_EOL;
# Afficher vacances et jours fériés
echo '<div>'.PHP_EOL;
echo '<input type="checkbox" id="show_holidays" name="show_holidays" value="Oui" ';
if (Settings::get('show_holidays') == 'Oui') {
    echo 'checked';
}
echo ' />'.PHP_EOL;
echo '<label for="show_holidays">'.get_vocab('holidays_msg').'</label>'.PHP_EOL;
echo '<br />';
echo '</div>'.PHP_EOL;
# Choix de la zone de vacances scolaires (France), uniquement si l'affichage des vacances et fériés est activé
echo '<div id="vacances_scolaires"';
echo '>'.PHP_EOL;
echo '<p>'.get_vocab('holidays_zone_msg').PHP_EOL;
echo '<select class="form-control" name="holidays_zone">'.PHP_EOL;
foreach ($acad as $key => $value) {
    echo '<option value="'.$value.'"';
    if (Settings::get('holidays_zone') == $value) {
        echo ' selected="selected"';
    }
    echo '>'.$value.'</option>'.PHP_EOL;
}
echo '</select></p>'.PHP_EOL;
echo '</div>'.PHP_EOL;
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
<script>
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
				id_area:$('#id_area').val(),
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
<script type="text/javascript">
  $(document).ready(function(){
    if ($('#display_info_bulle').is(':checked'))
        $('#contenu_popup').hide();
    else 
        $('#contenu_popup').show();
    $('#display_info_bulle').click(function(){
      $('#contenu_popup').toggle();
    });
    if ($('#show_holidays').is(':checked'))
        $('#vacances_scolaires').show();
    else 
        $('#vacances_scolaires').hide();
    $('#show_holidays').click(function(){
      $('#vacances_scolaires').toggle();
    });
    modifier_liste_domaines();
    modifier_liste_ressources(1);
  });
</script>
<!-- fin de l'affichage de la colonne de droite et fermeture de la page -->
</div></section></body></html>