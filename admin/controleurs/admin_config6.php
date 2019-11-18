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

$msg = '';

if (isset($_GET['sync'])) {
    if ($_GET['sync'] == 1) {
		$sql = "UPDATE ".TABLE_PREFIX."_utilisateurs SET default_style=''"; // Vide = choix admin
		if (grr_sql_command($sql) < 0)
			fatal_error(0, grr_sql_error());
		else
			$msg .= "Synchronisation terminée !<br />";
    } elseif ($_GET['sync'] == 2) {
		$sql = "UPDATE ".TABLE_PREFIX."_utilisateurs SET default_language='".Settings::get('default_language')."'";
		if (grr_sql_command($sql) < 0)
			fatal_error(0, grr_sql_error());
		else
			$msg .= "Synchronisation terminée !<br />";
	}
}


if (isset($_POST['show_courrier'])) {
    if (!Settings::set('show_courrier', $_POST['show_courrier'])) {
        echo "Erreur lors de l'enregistrement de show_courrier !<br />";
        die();
    }
}
if (isset($_POST['show_holidays'])) {
    if (!Settings::set('show_holidays', $_POST['show_holidays'])) {
        echo "Erreur lors de l'enregistrement de show_holidays !<br />";
        die();
    }
}
if (isset($_POST['holidays_zone'])) {
    if (!Settings::set('holidays_zone', $_POST['holidays_zone'])) {
        echo "Erreur lors de l'enregistrement de holidays_zone !<br />";
        die();
    }
}
// Style/thème
if (isset($_POST['default_css'])) {
    if (!Settings::set('default_css', $_POST['default_css'])) {
        echo "Erreur lors de l'enregistrement de default_css !<br />";
        die();
    }
}
// langage
if (isset($_POST['default_language'])) {
    if (!Settings::set('default_language', $_POST['default_language'])) {
        echo "Erreur lors de l'enregistrement de default_language !<br />";
        die();
    }
    unset($_SESSION['default_language']);
}
// Type d'affichage des listes des domaines et des ressources
if (isset($_POST['area_list_format'])) {
    if (!Settings::set('area_list_format', $_POST['area_list_format'])) {
        echo "Erreur lors de l'enregistrement de area_list_format !<br />";
        die();
    }
}
// site par défaut
if (isset($_POST['id_site'])) {
    if (!Settings::set('default_site', $_POST['id_site'])) {
        echo "Erreur lors de l'enregistrement de default_site !<br />";
        die();
    }
}
// domaine par défaut
if (isset($_POST['id_area'])) {
    if (!Settings::set('default_area', $_POST['id_area'])) {
        echo "Erreur lors de l'enregistrement de default_area !<br />";
        die();
    }
}
if (isset($_POST['id_room'])) {
    if (!Settings::set('default_room', $_POST['id_room'])) {
        echo "Erreur lors de l'enregistrement de default_room !<br />";
        die();
    }
}
// Affichage de l'adresse email
if (isset($_POST['display_level_email'])) {
    if (!Settings::set('display_level_email', $_POST['display_level_email'])) {
        echo "Erreur lors de l'enregistrement de display_level_email !<br />";
        die();
    }
}
/*-----MAJ Loïs THOMAS  --> Affichage de la page view_entry pour les réservations  -----*/
if (isset($_POST['display_level_view_entry'])) {
    if (!Settings::set('display_level_view_entry', $_POST['display_level_view_entry'])) {
        echo "Erreur lors de l'enregistrement de display_level_view_entry !<br />";
        die();
    }
}
// display_info_bulle
if (isset($_POST['display_info_bulle'])) {
    if (!Settings::set('display_info_bulle', $_POST['display_info_bulle'])) {
        echo "Erreur lors de l'enregistrement de display_info_bulle !<br />";
        die();
    }
}
// menu_gauche
if (isset($_POST['menu_gauche'])) {
    if (!Settings::set('menu_gauche', $_POST['menu_gauche'])) {
        echo "Erreur lors de l'enregistrement de menu_gauche !<br />";
        die();
    }
}
// display_type
if (isset($_POST['display_type'])) {
    if (!Settings::set('display_type', $_POST['display_type'])) {
        echo "Erreur lors de l'enregistrement de display_type !<br />";
        die();
    }
}
// display_beneficicaire
if (isset($_POST['display_beneficicaire'])) {
    if (!Settings::set('display_beneficicaire', $_POST['display_beneficicaire'])) {
        echo "Erreur lors de l'enregistrement de display_beneficicaire !<br />";
        die();
    }
}
// display_full_description
if (isset($_POST['display_full_description'])) {
    if (!Settings::set('display_full_description', $_POST['display_full_description'])) {
        echo "Erreur lors de l'enregistrement de display_full_description !<br />";
        die();
    }
}
// display_short_description
if (isset($_POST['display_short_description'])) {
    if (!Settings::set('display_short_description', $_POST['display_short_description'])) {
        echo "Erreur lors de l'enregistrement de display_short_description !<br />";
        die();
    }
}
// remplissage de la description brève
if (isset($_POST['remplissage_description_breve'])) {
    if (!Settings::set('remplissage_description_breve', $_POST['remplissage_description_breve'])) {
        echo "Erreur lors de l'enregistrement de remplissage_description_breve !<br />";
        die();
    }
}
// remplissage de la description complète
if (isset($_POST['remplissage_description_complete'])) {
    if (!Settings::set('remplissage_description_complete', $_POST['remplissage_description_complete'])) {
        echo "Erreur lors de l'enregistrement de remplissage_description_complete !<br />";
        die();
    }
}
// pview_new_windows
if (isset($_POST['pview_new_windows'])) {
    if (!Settings::set('pview_new_windows', $_POST['pview_new_windows'])) {
        echo "Erreur lors de l'enregistrement de pview_new_windows !<br />";
        die();
    }
}
/*-----MAJ Loïs THOMAS  -->Affichage ou non de la legende -----*/
if (isset($_POST['legend'])) {
    if (!Settings::set('legend', $_POST['legend'])) {
        echo "Erreur lors de l'enregistrement de legend !<br />";
        die();
    }
}
// Affichage imprimante
if (isset($_POST['imprimante'])) {
    if (!Settings::set('imprimante', $_POST['imprimante'])) {
        echo "Erreur lors de l'enregistrement de imprimante !<br />";
        die();
    }
}
// Option peridodicite
if (isset($_POST['periodicite'])) {
    if (!Settings::set('periodicite', $_POST['periodicite'])) {
        echo "Erreur lors de l'enregistrement de periodicite !<br />";
        die();
    }
}
/*-----MAJ David VOUE 22/01/2014-->Affichage ou non du formulaire de contact et adresse mail du destinataire -----*/
if (isset($_POST['mail_destinataire'])) {
    if (!Settings::set('mail_destinataire', $_POST['mail_destinataire'])) {
        echo "Erreur lors de l'enregistrement de mail_destinataire !<br />";
        die();
    }
}

if (isset($_POST['allow_pdf'])) {
    if (!Settings::set('allow_pdf', $_POST['allow_pdf'])) {
        echo "Erreur lors de l'enregistrement de allow_pdf !<br />";
        die();
    }
}

if (isset($_POST['mail_etat_destinataire'])) {
    if (!Settings::set('mail_etat_destinataire', $_POST['mail_etat_destinataire'])) {
        echo "Erreur lors de l'enregistrement de mail_etat_destinataire !<br />";
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
        echo "Erreur lors de l'enregistrement de lien_aide !<br />";
        die();
    }
    if (!Settings::set('gestion_lien_aide', $_POST['gestion_lien_aide'])) {
        echo "Erreur lors de l'enregistrement de gestion_lien_aide !<br />";
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
        echo "Erreur lors de l'enregistrement de default_report_days !<br />";
        die();
    }
}
if (isset($_POST['longueur_liste_ressources_max'])) {
    settype($_POST['longueur_liste_ressources_max'], 'integer');
    if ($_POST['longueur_liste_ressources_max'] <= 0) {
        $_POST['longueur_liste_ressources_max'] = 1;
    }
    if (!Settings::set('longueur_liste_ressources_max', $_POST['longueur_liste_ressources_max'])) {
        echo "Erreur lors de l'enregistrement de longueur_liste_ressources_max !<br />";
        die();
    }
}
//echo $_POST['default_area']."<br />";

if (!Settings::load()) {
    die('Erreur chargement settings');
}
// Si pas de problème, message de confirmation
if (isset($_POST['ok'])) {
    $_SESSION['displ_msg'] = 'yes';
    if ($msg == '') {
        $msg = get_vocab('message_records');
    }
    Header('Location: ?p=admin_config6&msg='.$msg);
    exit();
}
if ((isset($_GET['msg'])) && isset($_SESSION['displ_msg']) && ($_SESSION['displ_msg'] == 'yes')) {
    $msg = $_GET['msg'];
} else {
    $msg = '';
}

get_vocab_admin('default_parameter_values_title');
get_vocab_admin('explain_default_parameter');
get_vocab_admin('explain_area_list_format');
get_vocab_admin('liste_area_list_format');
get_vocab_admin('select_area_list_format');
get_vocab_admin('item_area_list_format');

if (Settings::get('module_multisite') == 'Oui') {
	$trad['dUse_site'] = 'y';
	$trad['explain_default_area_and_room'] = get_vocab('explain_default_area_and_room_and_site');
} else {
	$trad['dUse_site'] = 'n';
	get_vocab_admin('explain_default_area_and_room');
}

get_vocab_admin('explain_css');
get_vocab_admin('choose_language');
get_vocab_admin('default_site');
get_vocab_admin('choose_a_site');

get_vocab_admin('display_info_bulle_msg');
get_vocab_admin('info_bulle0');
get_vocab_admin('info_bulle1');
get_vocab_admin('info_bulle2');

get_vocab_admin('display_menu');
get_vocab_admin('display_menu_1');
get_vocab_admin('display_menu_2');
get_vocab_admin('display_menu_3');
get_vocab_admin('display_menu_4');

get_vocab_admin('display_mail_etat_destinataire');
get_vocab_admin('display_mail_etat_destinataire_1');
get_vocab_admin('display_mail_etat_destinataire_2');
get_vocab_admin('display_mail_etat_destinataire_3');
get_vocab_admin('display_mail_etat_destinataire_4');
get_vocab_admin('display_mail_destinataire');

// Affichage des réservations dans les vues journées, semaine et mois
get_vocab_admin('display_planning_resa');
get_vocab_admin('sum_by_creator');
get_vocab_admin('namebooker');
get_vocab_admin('match_descr');
get_vocab_admin('type');

/*
get_vocab_admin('display_short_description_msg');
get_vocab_admin('display_short_description0');
get_vocab_admin('display_short_description1');

get_vocab_admin('display_full_description_msg');
get_vocab_admin('display_full_description0');
get_vocab_admin('display_full_description1');
*/
get_vocab_admin('display_level_email_msg1');
get_vocab_admin('display_level_email_msg2');
get_vocab_admin('visu_fiche_description0');
get_vocab_admin('visu_fiche_description1');
get_vocab_admin('visu_fiche_description2');
get_vocab_admin('visu_fiche_description3');
get_vocab_admin('visu_fiche_description4');
get_vocab_admin('visu_fiche_description5');
get_vocab_admin('visu_fiche_description6');

get_vocab_admin('display_level_view_entry');
get_vocab_admin('display_level_view_entry_0');
get_vocab_admin('display_level_view_entry_1');

get_vocab_admin('remplissage_description_breve_msg');
get_vocab_admin('remplissage_description_breve0');
get_vocab_admin('remplissage_description_breve1');
get_vocab_admin('remplissage_description_breve2');

get_vocab_admin('remplissage_description_complete_msg');
get_vocab_admin('remplissage_description_complete0');
get_vocab_admin('remplissage_description_complete1');

get_vocab_admin('pview_new_windows_msg');
get_vocab_admin('pview_new_windows0');
get_vocab_admin('pview_new_windows1');

get_vocab_admin('legend_msg');
get_vocab_admin('imprimante_msg');
get_vocab_admin('periodicite_msg');
get_vocab_admin('courrier_msg');
get_vocab_admin('holidays_msg');

get_vocab_admin('holidays_zone_msg');
get_vocab_admin('holidays_zone_msg');
get_vocab_admin('holidays_zone_msg');

get_vocab_admin('default_report_days_msg');
get_vocab_admin('default_report_days_explain');

get_vocab_admin('formulaire_reservation');
get_vocab_admin('longueur_liste_ressources');

get_vocab_admin('YES');
get_vocab_admin('NO');
get_vocab_admin('save');


// Liste des sites
if (Settings::get('module_multisite') == 'Oui') {
    $sql = 'SELECT id,sitecode,sitename
	FROM '.TABLE_PREFIX.'_site
	ORDER BY id ASC';
    $resultat = grr_sql_query($sql);

	$trad['dOptionSite'] = "";
    for ($enr = 0; ($row = grr_sql_row($resultat, $enr)); ++$enr) {
		$trad['dOptionSite'] .= '<option value="'.$row[0].'"';
        if (Settings::get('default_site') == $row[0]) {
            $trad['dOptionSite'] .= ' selected="selected" ';
        }
        $trad['dOptionSite'] .= '>'.htmlspecialchars($row[2]);
        $trad['dOptionSite'] .= '</option>'."\n";
    }
}

// Choix de la feuille de style
$i = 0;
$trad['dOptionTheme'] = "";
while ($i < count($liste_themes)) {
	$trad['dOptionTheme'] .= "<option value='".$liste_themes[$i]."'";
	if (Settings::get('default_css') == $liste_themes[$i]) {
		$trad['dOptionTheme'] .= ' selected="selected"';
	}
	$trad['dOptionTheme'] .= ' >'.encode_message_utf8($liste_name_themes[$i]).'</option>';
	++$i;
}

// Choix de la langue
$i = 0;
$trad['dOptionLangue'] = "";
while ($i < count($liste_language)) {
    $trad['dOptionLangue'] .= "<option value='".$liste_language[$i]."'";
    if (Settings::get('default_language') == $liste_language[$i]) {
        $trad['dOptionLangue'] .= ' selected="selected"';
    }
    $trad['dOptionLangue'] .= ' >'.encode_message_utf8($liste_name_language[$i]).'</option>'.PHP_EOL;
    ++$i;
}

// Choix de la zone de vacances scolaires (France), uniquement si l'affichage des vacances et fériés est activé
if (Settings::get('show_holidays') == 'Oui'){
	$trad['dOptionVavances'] = "";
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
        $trad['dOptionVavances'] .= '<option value="'.$value.'"';
        if (Settings::get('holidays_zone') == $value) {
            $trad['dOptionVavances'] .= ' selected';
        }
        $trad['dOptionVavances'] .= '>'.$value.'</option>'.PHP_EOL;
    }

}

affiche_pop_up($msg, 'admin');

?>