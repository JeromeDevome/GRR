<?php
/**
 * admin_page_reservation.php
 * Interface permettant à l'administrateur la configuration de certains paramètres généraux
 * Ce script fait partie de l'application GRR.
 * Dernière modification : $Date: 2026-05-30 17:30$
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

$grr_script_name = 'admin_page_reservation.php';

// Accès à la page
SecuAccess::CheckAccess(6, $back);

require_once("../include/pages.class.php");

$trad   = $vocab;
$msg    = '';

if (!Pages::load()) {
    die('Erreur chargement pages');
}

// les variables attendues et leur type
$form_vars = array(
    'submit' => 'int',
    'sync' => 'int',
    'plageresa' => 'string',
    'visu_fiche_description' => 'int',
    'acces_fiche_reservation' => 'int',
    'acces_config' => 'int',
    'allow_user_delete_after_begin' => 'int',
    'allow_gestionnaire_modify_del' => 'int',
    'UserAllRoomsMaxBooking' => 'int',
    'select_date_directe' => 'int',
    'periodicite' => 'int',
    'show_courrier' => 'int',
    'fct_echange_resa' => 'int',
    'fct_drag_drop' => 'int',
    'jours_cycles_actif' => 'int',
    'mail_etat_destinataire' => 'int',
    'mail_destinataire' => 'string',
    'mail_user_destinataire' => 'int',
    'mail_contact_resa_captcha' => 'int',
    'textecontactresa' => '',
    'area_list_format' => 'string',
    'default_site' => 'int',
    'id_area' => 'int',
    'id_room' => 'int',
    'menu_gauche' => 'int',
    'display_beneficiaire_nc' => 'int',
    'display_beneficiaire_vi' => 'int',
    'display_beneficiaire_us' => 'int',
    'display_beneficiaire_gr' => 'int',
    'display_beneficiaire_ad' => 'int',
    'display_horaires_nc' => 'int',
    'display_horaires_vi' => 'int',
    'display_horaires_us' => 'int',
    'display_horaires_gr' => 'int',
    'display_horaires_ad' => 'int',
    'display_short_description_nc' => 'int',
    'display_short_description_vi' => 'int',
    'display_short_description_us' => 'int',
    'display_short_description_gr' => 'int',
    'display_short_description_ad' => 'int',
    'display_full_description_nc' => 'int',
    'display_full_description_vi' => 'int',
    'display_full_description_us' => 'int',
    'display_full_description_gr' => 'int',
    'display_full_description_ad' => 'int',
    'display_type_nc' => 'int',
    'display_type_vi' => 'int',
    'display_type_us' => 'int',
    'display_type_gr' => 'int',
    'display_type_ad' => 'int',
    'display_participants_nc' => 'int',
    'display_participants_vi' => 'int',
    'display_participants_us' => 'int',
    'display_participants_gr' => 'int',
    'display_participants_ad' => 'int',
    'display_level_email' => 'int',
    'display_level_view_entry' => 'int',
    'remplissage_description_breve' => 'int',
    'remplissage_description_complete' => 'int',
    'pview_new_windows' => 'int',
    'javascript_info_disabled' => 'int',
    'legend' => 'int',
    'imprimante' => 'int',
    'pdf' => 'int',
    'show_feries' => 'int',
    'show_holidays' => 'int',
    'holidays_zone' => 'alphanumeric',
    'nb_calendar' => 'int',
    'max_resa_affiche' => 'int',
    'longueur_liste_ressources_max' => 'int',
    'calcul_plus_semaine_all' => 'int',
    'calcul_plus_mois' => 'int',
    'calcul_plus_mois2_all' => 'int'
);
// récupération des valeurs des variables passées en paramètres
foreach($form_vars as $var => $var_type)
    $$var = SecuChaine::GetFormVarSecure($var, $var_type);

/** Accès & Droits **/
    if ($submit == 1) {

        // Dates de réservations
        if (isset($plageresa)) {

            $demande_confirmation = 'no';
            $datesLimites	= explode(" - ", $plageresa);
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

        // Visualisation de la fiche de description d'une ressource.
        if (!Settings::set("visu_fiche_description", $visu_fiche_description))
            $msg .= "Erreur lors de l'enregistrement de visu_fiche_description !<br />";

        // Accès fiche de réservation d'une ressource.
        if (!Settings::set("acces_fiche_reservation", $acces_fiche_reservation))
            $msg .= "Erreur lors de l'enregistrement de acces_fiche_reservation !<br />";

        // Accès page d'édition d'une ressource.
        if (!Settings::set("acces_config", $acces_config))
            $msg .= "Erreur lors de l'enregistrement de acces_config !<br />";

        // Suppression & modification des réservations passées
        if (!Settings::set("allow_user_delete_after_begin", $allow_user_delete_after_begin))
            $msg .= "Erreur lors de l'enregistrement de allow_user_delete_after_begin !<br />";

        // Droit de suppression et de modification des réservations passées pour les gestionnaires
        if (!Settings::set("allow_gestionnaire_modify_del", $allow_gestionnaire_modify_del))
            $msg .= "Erreur lors de l'enregistrement de allow_gestionnaire_modify_del !<br />";

        // Nombre maximum de réservation (tous domaines confondus)
        if ($UserAllRoomsMaxBooking=='')
            $UserAllRoomsMaxBooking = -1;
        if ($UserAllRoomsMaxBooking<-1)
            $UserAllRoomsMaxBooking = -1;
        if (!Settings::set("UserAllRoomsMaxBooking", $UserAllRoomsMaxBooking))
            $msg .= "Erreur lors de l'enregistrement de UserAllRoomsMaxBooking !<br />";
    }

/** Fonctionnalités **/
    if ($submit == 1) {

        // Selection date directe
        if (!Settings::set('select_date_directe', $select_date_directe))
            $msg .= "Erreur lors de l'enregistrement de select_date_directe !<br />";

        // Périodicité
        if (!Settings::set('periodicite', $periodicite))
            $msg .= "Erreur lors de l'enregistrement de periodicite !<br />";

        // Gestion courrier
        if (!Settings::set('show_courrier', $show_courrier))
            $msg .= "Erreur lors de l'enregistrement de show_courrier !<br />";

        // Echange de réservation
        if (!Settings::set('fct_echange_resa', $fct_echange_resa))
            $msg .= "Erreur lors de l'enregistrement de fct_echange_resa !<br />";

        // Drag & Drop
        if (!Settings::set('fct_drag_drop', $fct_drag_drop))
            $msg .= "Erreur lors de l'enregistrement de fct_drag_drop !<br />";

        if (!Settings::set("jours_cycles_actif", $jours_cycles_actif))
		    $msg .= "Erreur lors de l'enregistrement de jours_cycles_actif ! <br />";

        // Formulaire de contact pour réservation 
        if (!Settings::set('mail_etat_destinataire', $mail_etat_destinataire))
            $msg .= "Erreur lors de l'enregistrement de mail_etat_destinataire !<br />";

        if (!Settings::set('mail_destinataire', $mail_destinataire))
            $msg .= "Erreur lors de l'enregistrement de mail_destinataire !<br />";

        if (!Settings::set("mail_user_destinataire", $mail_user_destinataire))
            $msg .= "Erreur lors de l'enregistrement de mail_user_destinataire !<br />";

        if (!Settings::set("mail_contact_resa_captcha", $mail_contact_resa_captcha))
            $msg .= "Erreur lors de l'enregistrement de mail_contact_resa_captcha !<br />";

        if (!Pages::set("contactresa", "contactresa", $textecontactresa))
            $msg .= "Erreur lors de l'enregistrement du texte contactresa !<br />".$textecontactresa;

    }

/** Apparence **/
    if ($submit == 1) {

        // Type d'affichage des listes des domaines et des ressources
        if (!Settings::set('area_list_format', $area_list_format))
            $msg .= "Erreur lors de l'enregistrement de area_list_format !<br />";

        // Site par défaut
        if (!Settings::set('default_site', $default_site))
            $msg .= "Erreur lors de l'enregistrement de default_site !<br />";

        // Domaine par défaut
        if (!Settings::set('default_area', $id_area))
            $msg .= "Erreur lors de l'enregistrement de default_area !<br />";

        // Page par défaut
        if (!Settings::set('default_room', $id_room))
            $msg .= "Erreur lors de l'enregistrement de default_room !<br />";

        // Menu de "gauche"
        if (!Settings::set('menu_gauche', $menu_gauche))
            $msg .= "Erreur lors de l'enregistrement de menu_gauche !<br />";

        // Affichage du bénéficiaire de la réservation
        if (!Settings::set('display_beneficiaire_nc', $display_beneficiaire_nc))
            $msg .= "Erreur lors de l'enregistrement de display_beneficiaire_nc !<br />";

        if (!Settings::set('display_beneficiaire_vi', $display_beneficiaire_vi))
            $msg .= "Erreur lors de l'enregistrement de display_beneficiaire_vi !<br />";

        if (!Settings::set('display_beneficiaire_us', $display_beneficiaire_us))
            $msg .= "Erreur lors de l'enregistrement de display_beneficiaire_us !<br />";
 
        if (!Settings::set('display_beneficiaire_gr', $display_beneficiaire_gr))
            $msg .= "Erreur lors de l'enregistrement de display_beneficiaire_gr !<br />";

        if (!Settings::set('display_beneficiaire_ad', $display_beneficiaire_ad))
            $msg .= "Erreur lors de l'enregistrement de display_beneficiaire_ad !<br />";

        // Affichage du créateur de la réservation
        if (!Settings::set('display_horaires_nc', $display_horaires_nc))
            $msg .= "Erreur lors de l'enregistrement de display_horaires_nc !<br />";

        if (!Settings::set('display_horaires_vi', $display_horaires_vi))
            $msg .= "Erreur lors de l'enregistrement de display_horaires_vi !<br />";

        if (!Settings::set('display_horaires_us', $display_horaires_us))
            $msg .= "Erreur lors de l'enregistrement de display_horaires_us !<br />";

        if (!Settings::set('display_horaires_gr', $display_horaires_gr))
            $msg .= "Erreur lors de l'enregistrement de display_horaires_gr !<br />";

        if (!Settings::set('display_horaires_ad', $display_horaires_ad))
            $msg .= "Erreur lors de l'enregistrement de display_horaires_ad !<br />";

        // Affichage de la brève description
        if (!Settings::set('display_short_description_nc', $display_short_description_nc))
            $msg .= "Erreur lors de l'enregistrement de display_short_description_nc !<br />";
  
        if (!Settings::set('display_short_description_vi', $display_short_description_vi))
            $msg .= "Erreur lors de l'enregistrement de display_short_description_vi !<br />";

        if (!Settings::set('display_short_description_us', $display_short_description_us))
            $msg .= "Erreur lors de l'enregistrement de display_short_description_us !<br />";

        if (!Settings::set('display_short_description_gr', $display_short_description_gr))
            $msg .= "Erreur lors de l'enregistrement de display_short_description_gr !<br />";

        if (!Settings::set('display_short_description_ad', $display_short_description_ad))
            $msg .= "Erreur lors de l'enregistrement de display_short_description_ad !<br />";

        // Affichage de la description complète
        if (!Settings::set('display_full_description_nc', $display_full_description_nc))
            $msg .= "Erreur lors de l'enregistrement de display_full_description_nc !<br />";

        if (!Settings::set('display_full_description_vi', $display_full_description_vi))
            $msg .= "Erreur lors de l'enregistrement de display_full_description_vi !<br />";

        if (!Settings::set('display_full_description_us', $display_full_description_us))
            $msg .= "Erreur lors de l'enregistrement de display_full_description_us !<br />";

        if (!Settings::set('display_full_description_gr', $display_full_description_gr))
            $msg .= "Erreur lors de l'enregistrement de display_full_description_gr !<br />";

        if (!Settings::set('display_full_description_ad', $display_full_description_ad))
            $msg .= "Erreur lors de l'enregistrement de display_full_description_ad !<br />";

        // Affichage du type de réservation
        if (!Settings::set('display_type_nc', $display_type_nc))
            $msg .= "Erreur lors de l'enregistrement de display_type_nc !<br />";

        if (!Settings::set('display_type_vi', $display_type_vi))
            $msg .= "Erreur lors de l'enregistrement de display_type_vi !<br />";

        if (!Settings::set('display_type_us', $display_type_us))
            $msg .= "Erreur lors de l'enregistrement de display_type_us !<br />";
    
        if (!Settings::set('display_type_gr', $display_type_gr))
            $msg .= "Erreur lors de l'enregistrement de display_type_gr !<br />";

        if (!Settings::set('display_type_ad', $display_type_ad))
            $msg .= "Erreur lors de l'enregistrement de display_type_ad !<br />";

        // Affichage du nombre de participants
        if (!Settings::set('display_participants_nc', $display_participants_nc))
            $msg .= "Erreur lors de l'enregistrement de display_participants_nc !<br />";

        if (!Settings::set('display_participants_vi', $display_participants_vi))
            $msg .= "Erreur lors de l'enregistrement de display_participants_vi !<br />";

        if (!Settings::set('display_participants_us', $display_participants_us))
            $msg .= "Erreur lors de l'enregistrement de display_participants_us !<br />";

        if (!Settings::set('display_participants_gr', $display_participants_gr))
            $msg .= "Erreur lors de l'enregistrement de display_participants_gr !<br />";

        if (!Settings::set('display_participants_ad', $display_participants_ad))
            $msg .= "Erreur lors de l'enregistrement de display_participants_ad !<br />";

        // Affichage des liens email
        if (!Settings::set('display_level_email', $display_level_email))
            $msg .= "Erreur lors de l'enregistrement de display_level_email !<br />";

        // Affichage de la fiche de réservation
        if (!Settings::set('display_level_view_entry', $display_level_view_entry))
            $msg .= "Erreur lors de l'enregistrement de display_level_view_entry !<br />";

        // Remplissage de la description brève -> A migrer au niveau du domaine
        if (!Settings::set('remplissage_description_breve', $remplissage_description_breve))
            $msg .= "Erreur lors de l'enregistrement de remplissage_description_breve !<br />";

        // Remplissage de la description complète -> A migrer au niveau du domaine
        if (!Settings::set('remplissage_description_complete', $remplissage_description_complete)) 
                $msg .= "Erreur lors de l'enregistrement de remplissage_description_complete !<br />";

        // Format d'ouverture de la fenêtre d'impression
        if (!Settings::set('pview_new_windows', $pview_new_windows))
            $msg .= "Erreur lors de l'enregistrement de pview_new_windows !<br />";

        // Popup javascript
        if (!Settings::set('javascript_info_disabled', $javascript_info_disabled))
            $msg .= "Erreur lors de l'enregistrement de javascript_info_disabled !<br />";

        // Affichage ou non de la legende
        if (!Settings::set('legend', $legend))
            $msg .= "Erreur lors de l'enregistrement de legend !<br />";

        // Affichage imprimante
        if (!Settings::set('imprimante', $imprimante))
            $msg .= "Erreur lors de l'enregistrement de imprimante !<br />";

        // Affichage pdf
        if (!Settings::set('pdf', $pdf))
            $msg .= "Erreur lors de l'enregistrement de pdf !<br />";

        // Affichage des jours fériés
        if (!Settings::set('show_feries', $show_feries))
            $msg .= "Erreur lors de l'enregistrement de show_feries !<br />";

        // Affichage des vacances
        if (!Settings::set('show_holidays', $show_holidays))
            $msg .= "Erreur lors de l'enregistrement de show_holidays !<br />";

        if (!Settings::set('holidays_zone', $holidays_zone)) 
            $msg .= "Erreur lors de l'enregistrement de holidays_zone !<br />";

        // Nombre de mini-calendriers à afficher
        if (!Settings::set('nb_calendar', $nb_calendar))
            $msg .= "Erreur lors de l'enregistrement de nb_calendar !<br />";

        // Nombre max de réservations à afficher
        if ($max_resa_affiche <= 1)
             $max_resa_affiche = 1;

        if (!Settings::set('max_resa_affiche', $max_resa_affiche))
            $msg .= "Erreur lors de l'enregistrement de max_resa_affiche !<br />";
  
        // Nombre max de ressources à afficher dans les listes de ressources
        if ($longueur_liste_ressources_max <= 0)
            $longueur_liste_ressources_max = 1;

        if (!Settings::set('longueur_liste_ressources_max', $longueur_liste_ressources_max))
            $msg .= "Erreur lors de l'enregistrement de longueur_liste_ressources_max !<br />";

    }


/** Performance **/
    if ($submit == 1) {

        if (!Settings::set('calcul_plus_semaine_all', $calcul_plus_semaine_all))
            $msg .= "Erreur lors de l'enregistrement de calcul_plus_semaine_all !<br />";

        if (!Settings::set('calcul_plus_mois', $calcul_plus_mois))
            $msg .= "Erreur lors de l'enregistrement de calcul_plus_mois !<br />";

        if (!Settings::set('calcul_plus_mois2_all', $calcul_plus_mois2_all))
            $msg .= "Erreur lors de l'enregistrement de calcul_plus_mois2_all !<br />";

    }


if (isset($sync)) {
    if ($sync == 1) {
		$sql = "UPDATE ".TABLE_PREFIX."_utilisateurs SET default_style=''"; // Vide = choix admin
		if (grr_sql_command($sql) < 0)
			fatal_error(0, grr_sql_error());
		else
            $d['enregistrement'] = "Synchronisation terminée !<br />";
    } elseif ($sync == 2) {
		$sql = "UPDATE ".TABLE_PREFIX."_utilisateurs SET default_language='".Settings::get('default_language')."'";
		if (grr_sql_command($sql) < 0)
			fatal_error(0, grr_sql_error());
		else
            $d['enregistrement'] = "Synchronisation terminée !<br />";
    } elseif ($sync == 3) {
        $sql = "UPDATE ".TABLE_PREFIX."_utilisateurs SET default_list_type='".Settings::get('area_list_format')."'";
        if (grr_sql_command($sql) < 0)
            fatal_error(0, grr_sql_error());
        else
            $d['enregistrement'] = "Synchronisation terminée !<br />";
    } elseif ($sync == 4) {
        $sql = "UPDATE ".TABLE_PREFIX."_utilisateurs SET default_site='".Settings::get('default_site')."'";
        if (grr_sql_command($sql) < 0)
            fatal_error(0, grr_sql_error());
        else
            $sql = "UPDATE ".TABLE_PREFIX."_utilisateurs SET default_area='".Settings::get('default_area')."'";
            if (grr_sql_command($sql) < 0)
                fatal_error(0, grr_sql_error());
            else
                $sql = "UPDATE ".TABLE_PREFIX."_utilisateurs SET default_room='".Settings::get('default_room')."'";
                if (grr_sql_command($sql) < 0)
                    fatal_error(0, grr_sql_error());
                else
                    $d['enregistrement'] = "Synchronisation terminée !<br />";
    }
}

/** Résultat de l'enregistrement **/
if ($submit == 1){
    $_SESSION['displ_msg'] = 'yes';
    if ($msg == '')
        $d['enregistrement'] = 1;
    else
        $d['enregistrement'] = $msg;
}

/** Affichage de la page **/
if (!Settings::load()) {
    die('Erreur chargement settings');
}
$AllSettings = Settings::getAll();

// Début et fin des réservations
$bday	= date('d', Settings::get('begin_bookings'));
$bmonth	= date('m', Settings::get('begin_bookings'));
$byear	= date('Y', Settings::get('begin_bookings'));

$eday	= date('d', Settings::get('end_bookings'));
$emonth	= date('m', Settings::get('end_bookings'));
$eyear	= date('Y', Settings::get('end_bookings'));

$d["begin_bookings"] = $bday."/".$bmonth."/".$byear;
$d["end_bookings"] = $eday."/".$emonth."/".$eyear;

if (!Pages::load()) {
    die('Erreur chargement pages');
}
$d['contactresa'] = Pages::get('contactresa');

// Liste des sites
if (Settings::get('module_multisite') == 1) {
    $sql = 'SELECT id,sitecode,sitename
	FROM '.TABLE_PREFIX.'_site
	ORDER BY id ASC';
    $resultat = grr_sql_query($sql);

	$d['optionSite'] = "";
    for ($enr = 0; ($row = grr_sql_row($resultat, $enr)); ++$enr) {
		$d['optionSite'] .= '<option value="'.$row[0].'"';
        if (Settings::get('default_site') == $row[0]) {
            $d['optionSite'] .= ' selected="selected" ';
        }
        $d['optionSite'] .= '>'.htmlspecialchars($row[2]);
        $d['optionSite'] .= '</option>'."\n";
    }
}

// Choix de la zone de vacances scolaires (France), uniquement si l'affichage des vacances et fériés est activé
if (Settings::get('show_holidays') == 1){
	$d['optionVacances'] = "";
    $vacances = simplexml_load_file('../include/vacances.xml');
    $libelle = $vacances->academies->children();
    $acad = array();
    foreach ($libelle as $key => $value) {
        if (!in_array($value['zone'], $acad)) {
            $acad[] .= $value['zone'];
        }
    }
    sort($acad);

    foreach ($acad as $key => $value) {
        $d['optionVacances'] .= '<option value="'.$value.'"';
        if (Settings::get('holidays_zone') == $value) {
            $d['optionVacances'] .= ' selected';
        }
        $d['optionVacances'] .= '>'.$value.'</option>'.PHP_EOL;
    }

}

echo $twig->render($page.'.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings));

?>