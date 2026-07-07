<?php
/**
 * misc.inc.php
 * fichier de variables diverses
 * Dernière modification : $Date: 2026-03-22 11:10$
 * @author    JeromeB & Laurent Delineau & Yan Naessens
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

// On trouve le fichier .gitattributes à la racine du projet alors c'est un projet git, sinon c'est un projet sans git
if (file_exists("../.gitattributes")) {
	$gitHub = "-GitHub";
} else {
	$gitHub = "";
}

################################
# Development information
#################################
$grr_devel_url = "https://grr.devome.com/";

// Numéro de version actuel
# Format X.X.XRCX | exemples : 4.4.0a (alpha) ou 4.4.0b (beta) 4.4.0RC1 (Release Candidate) ou 4.4.0 (version OK)
$version_grr = "4.6.4";
# Version BDD, deux premirs chiffres = version majeur, les deux suivant la version, évolution de GRR, les 3 derniers une incrémentation à chaque changement
# Ex 0400003 : 3eme modification sur la branche 4.X.X
$version_bdd = "0400011";
// Version repository (GitHub) GitHub-Master / Release-v4.0.0-beta.1 / Release-v4.0.0-RC.1 / Release-v4.0.0
$versionReposite = "Release-v".$version_grr.$gitHub;

################################
# Configuration Requise
#################################
// Version PHP minimum
$php_mini = "8.1.0";
// Version PHP maximum testé et validé par : JeromeB
$php_max_valide = "8.5.8";
// Version PHP maximum qui est sensé fonctionné, si compatible avec toutes les versions à ce jour laisser vide
$php_maxi = "";

// Version MySQL minimum
$mysql_mini = "5.4.0";
// Version MySQL maximum testé et validé par : JeromeB
$mysql_max_valide = "9.6.0";
// Version MySQL maximum qui est sensé fonctionné
$mysql_maxi = "";


# Liste des tables
$liste_tables = array(
	"_area",
	"_area_periodes",
	"_calendar",
    "_calendrier_feries",
	"_calendrier_jours_cycle",
	"_calendrier_vacances",
	"_correspondance_statut",
	"_entry",
	"_entry_moderate",
	"_files",
	"_groupes",
	"_j_group_area",
	"_j_group_site",
	"_j_mailuser_room",
	"_j_site_area",
	"_j_type_area",
	"_j_useradmin_area",
	"_j_useradmin_site",
	"_j_userbook_room",
	"_j_user_area",
	"_j_user_room",
	"_j_user_site",
	"_log",
	"_log_mail",
	"_log_resa",
	"_modulesext",
	"_overload",
	"_page",
	"_participants",
	"_repeat",
	"_room",
	"_setting",
	"_site",
	"_type_area",
	"_utilisateurs",
	"_utilisateurs_demandes",
	"_utilisateurs_groupes",
	);

# Liste des feuilles de style
$liste_themes = array(
	"default",
    "grand_bleu",
	"vert",
	"violet",
	"orange",
	"bleu",
	"rouge",
	"rose",
	"fluo",
	"sombre",
	"perso"
	);

# Liste des noms des styles
$liste_name_themes = array(
    "Defaut",
	"Grand bleu",
	"Verdoyant",
	"Violeta",
	"Orange Talmont",
	"Bleu Talmont",
	"Rouge Feu",
	"Roseline",
	"Jaune Fluo",
	"Dark Mode",
	"Personnalisé via l'admin"
	);

# Liste des langues
$liste_language = array(
	"fr-fr",
	"de-de",
	"en-gb",
	"it-it",
	"es-es"
	);

# Liste des noms des variables de config
$config_variables = array(
	"nb_year_calendar",
	"correct_heure_ete_hiver",
	"max_rep_entrys",
	"unicode_encoding",
	"use_function_mysql_real_escape_string",
	"use_function_html_entity_decode",
	"connexionAdminMAJ",
	"restaureBBD",
	"debug_flag",
	"gRecherche_MAJ",
	"gWarningBackup",
	"gWarningDossierInstall",
	"gWarningSSL",
	"gWarningVersionTest",
	"upload_Module",
	"nbMaxJoursLogConnexion",
	"sso_super_admin",
	"sso_restrictions",
	"ldap_restrictions",
	"imap_restrictions",
	"fonction_mail_restrictions",
	"Url_CAS_setFixedServiceURL",
	"dbsys",
	"structure",
	"donnees",
	"gcTailleMaxDocResa",
	);

// Liste des paramètres de la table setting, avec leur valeur par défaut
$liste_settings = array(
	"acces_fiche_reservation" => "0",
	"ActiveModeDemo" => "0",
	"ActiveModeDiagnostic" => "0",
	"allow_my_connections" => "0",
	"allow_my_reservations" => "0",
	"allow_gestionnaire_modify_del" => "1",
	"allow_search_level" => "1",
	"allow_user_delete_after_begin" => "0",
	"allow_users_modify_affichage" => "2",
	"allow_users_modify_domaine" => "2",
	"allow_users_modify_email" => "2",
	"allow_users_modify_langue" => "2",
	"allow_users_modify_mdp" => "2",
	"allow_users_modify_profil" => "2",
	"allow_users_modify_theme" => "2",
	"area_list_format" => "item",
	"authentification_obli" => "1",
	"automatic_mail" => "1",
	"backup_date" => "",
	"begin_bookings" => mktime(0, 0, 0, 1, 1, date('Y')),
	"cacher_lien_deconnecter" => "0",
	"calcul_plus_mois" => "1",
	"calcul_plus_mois2_all" => "1",
	"calcul_plus_semaine_all" => "1",
	"cas_port" => "",
	"cas_racine" => "",
	"cas_serveur" => "",
	"cas_version" => "CAS_VERSION_2_0",
	"company" => "Nom de votre organisation",
	"ConvertLdapUtf8toIso" => "0",
	"default_css" => "default",
	"default_language" => "fr-fr",
	"default_report_days" => "30",
	"default_room" => "-1",
	"default_site" => "-1",
	"disable_login" => "0",
	"display_beneficiaire_ad" => "1",
	"display_beneficiaire_gr" => "1",
	"display_beneficiaire_nc" => "1",
	"display_beneficiaire_us" => "1",
	"display_beneficiaire_vi" => "1",
	"display_full_description_ad" => "1",
	"display_full_description_gr" => "1",
	"display_full_description_nc" => "1",
	"display_full_description_us" => "1",
	"display_full_description_vi" => "1",
	"display_horaires_ad" => "1",
	"display_horaires_gr" => "1",
	"display_horaires_nc" => "1",
	"display_horaires_us" => "1",
	"display_horaires_vi" => "1",
	"display_level_email" => "0",
	"display_level_view_entry" => "0",
	"display_short_description_ad" => "1",
	"display_short_description_gr" => "1",
	"display_short_description_nc" => "1",
	"display_short_description_us" => "1",
	"display_short_description_vi" => "1",
	"display_type_ad" => "1",
	"display_type_gr" => "1",
	"display_type_nc" => "1",
	"display_type_us" => "1",
	"display_type_vi" => "1",
	"end_bookings" => mktime(23, 59, 59, 12, 31, date('Y') + 3),
	"envoyer_email_avec_formulaire" => "0",
	"fct_crea_cpt" => "0",
	"fct_drag_drop" => "0",
	"fct_echange_resa" => "0",
	"file" => "1",
	"firstversion" => "",
	"gestion_lien_aide" => "ext",
	"grr_mail_Bcc" => "0",
	"grr_mail_method" => "bloque",
	"grr_mail_Password" => "",
	"grr_mail_smtp" => "",
	"grr_mail_Username" => "",
	"grr_url" => "",
	"holidays_zone" => "A",
	"horaireconnexiona" => "",
	"horaireconnexionde" => "",
	"imprimante" => "1",
	"ip_autorise" => "",
	"javascript_info_admin_disabled" => "0",
	"javascript_info_disabled" => "0",
	"jour_debut_Jours_Cycles" => "1",
	"jours_cycles_actif" => "0",
	"langues_dispo" => "fr-fr;en-gb;de-de;it-it;es-es",
	"ldap_champ_email" => "mail",
	"ldap_champ_nom" => "sn",
	"ldap_champ_prenom" => "givenname",
	"ldap_champ_recherche" => "uid",
	"legend" => "1",
	"lien_aide" => "",
	"log_mail" => "0",
	"login_logo" => "1",
	"login_nom" => "1",
	"login_template" => "1",
	"longueur_liste_ressources_max" => "20",
	"mail_contact_resa_captcha" => "0",
	"mail_destinataire" => "test@test.fr",
	"mail_etat_destinataire" => "0",
	"mail_user_destinataire" => "1",
	"mail_user_obligatoire" => "0",
	"maj194_champs_additionnels" => "1",
	"maj195_champ_rep_type_grr_repeat" => "1",
	"maj196_qui_peut_reserver_pour" => "1",
	"menu_gauche" => "1",
	"message_home_page" => "En raison du caractère personnel du contenu, ce site est soumis à des restrictions utilisateurs. Pour accéder aux outils de réservation, identifiez-vous :",
	"module_multisite" => "0",
	"nb_calendar" => "1",
	"nextalertemailhebdo" => "1735686000",
	"nombre_jours_Jours_Cycles" => "1",
	"pass_leng" => "8",
	"pass_nb_ch" => "1",
	"pass_nb_maj" => "1",
	"pass_nb_min" => "1",
	"pass_nb_sp" => "1",
	"periodicite" => "1",
	"pview_new_windows" => "1",
	"redirection_https" => "0",
	"select_date_directe" => "1",
	"sessionMaxLength" => "60",
	"show_courrier" => "0",
	"show_feries" => "0",
	"show_holidays" => "0",
	"smtp_allow_self_signed" => "false",
	"smtp_port" => "25",
	"smtp_secure" => "",
	"smtp_verify_depth" => "3",
	"smtp_verify_peer_name" => "true",
	"smtp_verify_peer" => "true",
	"sso_ac_corr_profil_statut" => "0",
	"sso_IsNotAllowedModify" => "0",
	"sso_redirection_accueil_grr" => "0",
	"technical_support_email" => "",
	"title_home_page" => "Gestion et Réservation de Ressources",
	"url_disconnect" => "",
	"use_fckeditor" => "1",
	"use_grr_url" => "0",
	"UserAllRoomsMaxBooking" => "-1",
	"verif_reservation_auto" => "0",
	"version" => $version_bdd,
	"visu_fiche_description" => "0",
	"webmaster_email" => "",
	"webmaster_name" => "Webmestre de GRR"
);

# Compatibilité avec les version inférieures à 1.9.6
if ((!isset($table_prefix)) or ($table_prefix==''))
	$table_prefix="grr";
# Définition de TABLE_PREFIX
define("TABLE_PREFIX",$table_prefix);


################################################
# Configuration du planning : valeurs par défaut
# Une interface en ligne permet une configuration domaine par domaine de ces valeurs
################################################
# Resolution - quel bloc peut être réservé, en secondes
# remarque : 1800 secondes = 1/2 heure.
$resolution = 1800;

# Durée maximale de réservation, en minutes
# -1 : désactivation de la limite
$duree_max_resa = -1 ;

# Début et fin d'une journée : valeur entières uniquement de 0 à 23
# morningstarts doit être inférieur à  < eveningends.
$morningstarts = 8;
$eveningends   = 19;

# Minutes à ajouter à l'heure $eveningends pour avoir la fin réelle d'une journée.
# Examples: pour que le dernier bloc réservable de la journée soit 16:30-17:00, mettre :
# eveningends=16 et eveningends_minutes=30.
# Pour avoir une journée de 24 heures avec un pas de 15 minutes mettre :
# morningstarts=0; eveningends=23;
# eveningends_minutes=45; et resolution=900.
$eveningends_minutes = 0;

# Début de la semaine: 0 pour dimanche, 1 pou lundi, etc.
$weekstarts = 1;

# Format d'affichage du temps : valeur 0 pour un affichage « 12 heures » et valeur 1 pour un affichage  « 24 heure ».
$twentyfourhour_format = 1;

# Ci-dessous des fonctions non officielles (non documentées) de GRR
# En attendant qu'elles soient implémentées dans GRR avec une interface en ligne

# Vous pouvez indiquer ci-dessous les identifiant de plusieurs ressources qui seront réservables, même par un simple visiteur
# Par exemple la ligne suivante autorise les simples visiteurs à réserver les ressoures 8, 4 et 5 :
# $id_room_autorise = array("8", "4", "5");
$id_room_autorise = array();

# Possibilité de désactiver le bandeau supérieur dans le cas de simples visiteurs
# Pour se connecter il est alors nécessaire de se rendre directement à l'adresse du type http://mon-site.fr/grr/login.php
# Mettre ci-dessous $desactive_bandeau_sup = 1;  pour désactiver le bandeau supérieur pour les simples visiteurs.
# Mettre ci-dessous $desactive_bandeau_sup = 0;  pour ne pas désactiver le bandeau supérieur pour les simples visiteurs.
$desactive_bandeau_sup = 0;
?>
