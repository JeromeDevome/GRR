<?php
/**
 * admin_config6.php
 * Interface permettant à l'administrateur la configuration de certains paramètres généraux
 * Ce script fait partie de l'application GRR.
 * Dernière modification : $Date: 2024-01-14 19:40$
 * @author    Laurent Delineau & JeromeB &  Bouteillier Nicolas & Yan Naessens
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


$trad = $vocab;

$msg = '';

if (isset($_GET['sync'])) {
    if ($_GET['sync'] == 1) {
		$sql = "UPDATE ".TABLE_PREFIX."_utilisateurs SET default_style=''"; // Vide = choix admin
		if (grr_sql_command($sql) < 0)
			fatal_error(0, grr_sql_error());
		else
            $d['enregistrement'] = "Synchronisation terminée !<br />";
    } elseif ($_GET['sync'] == 2) {
		$sql = "UPDATE ".TABLE_PREFIX."_utilisateurs SET default_language='".Settings::get('default_language')."'";
		if (grr_sql_command($sql) < 0)
			fatal_error(0, grr_sql_error());
		else
            $d['enregistrement'] = "Synchronisation terminée !<br />";
    } elseif ($_GET['sync'] == 3) {
        $sql = "UPDATE ".TABLE_PREFIX."_utilisateurs SET default_list_type='".Settings::get('area_list_format')."'";
        if (grr_sql_command($sql) < 0)
            fatal_error(0, grr_sql_error());
        else
            $d['enregistrement'] = "Synchronisation terminée !<br />";
    } elseif ($_GET['sync'] == 4) {
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

if (isset($_POST['show_holidays'])) {
    if (!Settings::set('show_holidays', $_POST['show_holidays'])) {
        $msg .= "Erreur lors de l'enregistrement de show_holidays !<br />";
    }
}
if (isset($_POST['holidays_zone'])) {
    if (!Settings::set('holidays_zone', $_POST['holidays_zone'])) {
        $msg .= "Erreur lors de l'enregistrement de holidays_zone !<br />";
    }
}
// Style/thème
if (isset($_POST['default_css'])) {
    if (!Settings::set('default_css', $_POST['default_css'])) {
        $msg .= "Erreur lors de l'enregistrement de default_css !<br />";
    }
}
// langage
if (isset($_POST['default_language'])) {
    if (!Settings::set('default_language', $_POST['default_language'])) {
        $msg .= "Erreur lors de l'enregistrement de default_language !<br />";
    }
    unset($_SESSION['default_language']);
}
// Type d'affichage des listes des domaines et des ressources
if (isset($_POST['area_list_format'])) {
    if (!Settings::set('area_list_format', $_POST['area_list_format'])) {
        $msg .= "Erreur lors de l'enregistrement de area_list_format !<br />";
    }
}
// site par défaut
if (isset($_POST['id_site'])) {
    if (!Settings::set('default_site', $_POST['id_site'])) {
        $msg .= "Erreur lors de l'enregistrement de default_site !<br />";
    }
}
// domaine par défaut
if (isset($_POST['id_area'])) {
    if (!Settings::set('default_area', $_POST['id_area'])) {
        $msg .= "Erreur lors de l'enregistrement de default_area !<br />";
    }
}
if (isset($_POST['id_room'])) {
    if (!Settings::set('default_room', $_POST['id_room'])) {
        $msg .= "Erreur lors de l'enregistrement de default_room !<br />";
    }
}
// Affichage de l'adresse email
if (isset($_POST['display_level_email'])) {
    if (!Settings::set('display_level_email', $_POST['display_level_email'])) {
        $msg .= "Erreur lors de l'enregistrement de display_level_email !<br />";
    }
}
/*-----MAJ Loïs THOMAS  --> Affichage de la page view_entry pour les réservations  -----*/
if (isset($_POST['display_level_view_entry'])) {
    if (!Settings::set('display_level_view_entry', $_POST['display_level_view_entry'])) {
        $msg .= "Erreur lors de l'enregistrement de display_level_view_entry !<br />";
    }
}
// display_info_bulle
/*if (isset($_POST['display_info_bulle'])) {
    if (!Settings::set('display_info_bulle', $_POST['display_info_bulle'])) {
        $msg .= "Erreur lors de l'enregistrement de display_info_bulle !<br />";
    }
}*/
// menu_gauche
if (isset($_POST['menu_gauche'])) {
    if (!Settings::set('menu_gauche', $_POST['menu_gauche'])) {
        $msg .= "Erreur lors de l'enregistrement de menu_gauche !<br />";
    }
}
// display_type
if (isset($_POST['display_type_nc'])) {
    if (!Settings::set('display_type_nc', $_POST['display_type_nc'])) {
        $msg .= "Erreur lors de l'enregistrement de display_type_nc !<br />";
    }
}
if (isset($_POST['display_type_vi'])) {
    if (!Settings::set('display_type_vi', $_POST['display_type_vi'])) {
        $msg .= "Erreur lors de l'enregistrement de display_type_vi !<br />";
    }
}
if (isset($_POST['display_type_us'])) {
    if (!Settings::set('display_type_us', $_POST['display_type_us'])) {
        $msg .= "Erreur lors de l'enregistrement de display_type_us !<br />";
    }
}
if (isset($_POST['display_type_gr'])) {
    if (!Settings::set('display_type_gr', $_POST['display_type_gr'])) {
        $msg .= "Erreur lors de l'enregistrement de display_type_gr !<br />";
    }
}
if (isset($_POST['display_type_ad'])) {
    if (!Settings::set('display_type_ad', $_POST['display_type_ad'])) {
        $msg .= "Erreur lors de l'enregistrement de display_type_ad !<br />";
    }
}
// display_beneficiaire
if (isset($_POST['display_beneficiaire_nc'])) {
    if (!Settings::set('display_beneficiaire_nc', $_POST['display_beneficiaire_nc'])) {
        $msg .= "Erreur lors de l'enregistrement de display_beneficiaire_nc !<br />";
    }
}
if (isset($_POST['display_beneficiaire_vi'])) {
    if (!Settings::set('display_beneficiaire_vi', $_POST['display_beneficiaire_vi'])) {
        $msg .= "Erreur lors de l'enregistrement de display_beneficiaire_vi !<br />";
    }
}
if (isset($_POST['display_beneficiaire_us'])) {
    if (!Settings::set('display_beneficiaire_us', $_POST['display_beneficiaire_us'])) {
        $msg .= "Erreur lors de l'enregistrement de display_beneficiaire_us !<br />";
    }
}
if (isset($_POST['display_beneficiaire_gr'])) {
    if (!Settings::set('display_beneficiaire_gr', $_POST['display_beneficiaire_gr'])) {
        $msg .= "Erreur lors de l'enregistrement de display_beneficiaire_gr !<br />";
    }
}
if (isset($_POST['display_beneficiaire_ad'])) {
    if (!Settings::set('display_beneficiaire_ad', $_POST['display_beneficiaire_ad'])) {
        $msg .= "Erreur lors de l'enregistrement de display_beneficiaire_ad !<br />";
    }
}
// display_horaires
if (isset($_POST['display_horaires_nc'])) {
    if (!Settings::set('display_horaires_nc', $_POST['display_horaires_nc'])) {
        $msg .= "Erreur lors de l'enregistrement de display_horaires_nc !<br />";
    }
}
if (isset($_POST['display_horaires_vi'])) {
    if (!Settings::set('display_horaires_vi', $_POST['display_horaires_vi'])) {
        $msg .= "Erreur lors de l'enregistrement de display_horaires_vi !<br />";
    }
}
if (isset($_POST['display_horaires_us'])) {
    if (!Settings::set('display_horaires_us', $_POST['display_horaires_us'])) {
        $msg .= "Erreur lors de l'enregistrement de display_horaires_us !<br />";
    }
}
if (isset($_POST['display_horaires_gr'])) {
    if (!Settings::set('display_horaires_gr', $_POST['display_horaires_gr'])) {
        $msg .= "Erreur lors de l'enregistrement de display_horaires_gr !<br />";
    }
}
if (isset($_POST['display_horaires_ad'])) {
    if (!Settings::set('display_horaires_ad', $_POST['display_horaires_ad'])) {
        $msg .= "Erreur lors de l'enregistrement de display_horaires_gr !<br />";
    }
}
// display_full_description
if (isset($_POST['display_full_description_nc'])) {
    if (!Settings::set('display_full_description_nc', $_POST['display_full_description_nc'])) {
        $msg .= "Erreur lors de l'enregistrement de display_full_description_nc !<br />";
    }
}
if (isset($_POST['display_full_description_vi'])) {
    if (!Settings::set('display_full_description_vi', $_POST['display_full_description_vi'])) {
        $msg .= "Erreur lors de l'enregistrement de display_full_description_vi !<br />";
    }
}
if (isset($_POST['display_full_description_us'])) {
    if (!Settings::set('display_full_description_us', $_POST['display_full_description_us'])) {
        $msg .= "Erreur lors de l'enregistrement de display_full_description_us !<br />";
    }
}
if (isset($_POST['display_full_description_gr'])) {
    if (!Settings::set('display_full_description_gr', $_POST['display_full_description_gr'])) {
        $msg .= "Erreur lors de l'enregistrement de display_full_description_gr !<br />";
    }
}
if (isset($_POST['display_full_description_ad'])) {
    if (!Settings::set('display_full_description_ad', $_POST['display_full_description_ad'])) {
        $msg .= "Erreur lors de l'enregistrement de display_full_description_ad !<br />";
    }
}
// display_short_description
if (isset($_POST['display_short_description_nc'])) {
    if (!Settings::set('display_short_description_nc', $_POST['display_short_description_nc'])) {
        $msg .= "Erreur lors de l'enregistrement de display_short_description_nc !<br />";
    }
}
if (isset($_POST['display_short_description_vi'])) {
    if (!Settings::set('display_short_description_vi', $_POST['display_short_description_vi'])) {
        $msg .= "Erreur lors de l'enregistrement de display_short_description_vi !<br />";
    }
}
if (isset($_POST['display_short_description_us'])) {
    if (!Settings::set('display_short_description_us', $_POST['display_short_description_us'])) {
        $msg .= "Erreur lors de l'enregistrement de display_short_description_us !<br />";
    }
}
if (isset($_POST['display_short_description_gr'])) {
    if (!Settings::set('display_short_description_gr', $_POST['display_short_description_gr'])) {
        $msg .= "Erreur lors de l'enregistrement de display_short_description_gr !<br />";
    }
}
if (isset($_POST['display_short_description_ad'])) {
    if (!Settings::set('display_short_description_ad', $_POST['display_short_description_ad'])) {
        $msg .= "Erreur lors de l'enregistrement de display_short_description_ad !<br />";
    }
}
// remplissage de la description brève
if (isset($_POST['remplissage_description_breve'])) {
    if (!Settings::set('remplissage_description_breve', $_POST['remplissage_description_breve'])) {
        $msg .= "Erreur lors de l'enregistrement de remplissage_description_breve !<br />";
    }
}
// remplissage de la description complète
if (isset($_POST['remplissage_description_complete'])) {
    if (!Settings::set('remplissage_description_complete', $_POST['remplissage_description_complete'])) {
        $msg .= "Erreur lors de l'enregistrement de remplissage_description_complete !<br />";
    }
}
// pview_new_windows
if (isset($_POST['pview_new_windows'])) {
    if (!Settings::set('pview_new_windows', $_POST['pview_new_windows'])) {
        $msg .= "Erreur lors de l'enregistrement de pview_new_windows !<br />";
    }
}
// Affichage ou non de la legende
if (isset($_POST['legend'])) {
    if (!Settings::set('legend', $_POST['legend'])) {
        $msg .= "Erreur lors de l'enregistrement de legend !<br />";
    }
}
// Affichage imprimante
if (isset($_POST['imprimante'])) {
    if (!Settings::set('imprimante', $_POST['imprimante'])) {
        $msg .= "Erreur lors de l'enregistrement de imprimante !<br />";
    }
}
// Affichage pdf
if (isset($_POST['pdf'])) {
    if (!Settings::set('pdf', $_POST['pdf'])) {
        $msg .= "Erreur lors de l'enregistrement de pdf !<br />";
    }
}
// Option peridodicite
if (isset($_POST['periodicite'])) {
    if (!Settings::set('periodicite', $_POST['periodicite'])) {
        $msg .= "Erreur lors de l'enregistrement de periodicite !<br />";
    }
}
// Générer PDF
if (isset($_POST['allow_pdf'])) {
    if (!Settings::set('allow_pdf', $_POST['allow_pdf'])) {
        $msg .= "Erreur lors de l'enregistrement de allow_pdf !<br />";
    }
}

/** Affichage de la page de connexion **/
    // Template page login
    if (isset($_POST['login_template'])) {
        if (!Settings::set('login_template', $_POST['login_template'])) {
            $msg .= "Erreur lors de l'enregistrement de login_template !<br />";
        }
    }
    // Aficher logo
    if (isset($_POST['login_logo'])) {
        if (!Settings::set('login_logo', $_POST['login_logo'])) {
            $msg .= "Erreur lors de l'enregistrement de login_logo !<br />";
        }
    }
    // Afficher nom établissement
    if (isset($_POST['login_nom'])) {
        if (!Settings::set('login_nom', $_POST['login_nom'])) {
            $msg .= "Erreur lors de l'enregistrement de login_nom !<br />";
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
        $msg .= "Erreur lors de l'enregistrement de lien_aide !<br />";
    }
    if (!Settings::set('gestion_lien_aide', $_POST['gestion_lien_aide'])) {
        $msg .= "Erreur lors de l'enregistrement de gestion_lien_aide !<br />";
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
        $msg .= "Erreur lors de l'enregistrement de default_report_days !<br />";
    }
}
if (isset($_POST['longueur_liste_ressources_max'])) {
    settype($_POST['longueur_liste_ressources_max'], 'integer');
    if ($_POST['longueur_liste_ressources_max'] <= 0) {
        $_POST['longueur_liste_ressources_max'] = 1;
    }
    if (!Settings::set('longueur_liste_ressources_max', $_POST['longueur_liste_ressources_max'])) {
        $msg .= "Erreur lors de l'enregistrement de longueur_liste_ressources_max !<br />";
    }
}
//echo $_POST['default_area']."<br />";

if (!Settings::load()) {
    die('Erreur chargement settings');
}
$AllSettings = Settings::getAll();

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


if (Settings::get('module_multisite') == 'Oui') {
	$d['use_site'] = 'y';
	$trad['explain_default_area_and_room'] = get_vocab('explain_default_area_and_room_and_site');
} else {
	$d['use_site'] = 'n';
	get_vocab_admin('explain_default_area_and_room');
}


// Liste des sites
if (Settings::get('module_multisite') == 'Oui') {
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

// Choix de la feuille de style
$i = 0;
$d['optionTheme'] = "";
while ($i < count($liste_themes)) {
	$d['optionTheme'] .= "<option value='".$liste_themes[$i]."'";
	if (Settings::get('default_css') == $liste_themes[$i]) {
		$d['optionTheme'] .= ' selected="selected"';
	}
	$d['optionTheme'] .= ' >'.encode_message_utf8($liste_name_themes[$i]).'</option>';
	++$i;
}

// Choix de la langue
$i = 0;
$d['optionLangue'] = "";
while ($i < count($liste_language)) {
    $d['optionLangue'] .= "<option value='".$liste_language[$i]."'";
    if (Settings::get('default_language') == $liste_language[$i]) {
        $d['optionLangue'] .= ' selected="selected"';
    }
    $d['optionLangue'] .= ' >'.encode_message_utf8($liste_name_language[$i]).'</option>'.PHP_EOL;
    ++$i;
}

// Choix de la zone de vacances scolaires (France), uniquement si l'affichage des vacances et fériés est activé
if (Settings::get('show_holidays') == 'Oui'){
	$d['optionVacances'] = "";
    $vacances = simplexml_load_file('../vacances.xml');
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