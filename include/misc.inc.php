<?php
/**
 * misc.inc.php
 * fichier de variables diverses
 * Dernière modification : $Date: 2018-07-20 14:00$
 * @author    JeromeB & Laurent Delineau & Yan Naessens
 * @copyright Copyright 2003-2020 Team DEVOME - JeromeB
 * @link      http://www.gnu.org/licenses/licenses.html
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 */


################################
# Development information
#################################
$grr_devel_url = "https://grr.devome.com/";

// Numéro de version actuel
# Format X.X.XRCX | exemples : 4.0.0a (alpha) ou 4.0.0b (beta) 3.4.0RC1 (Release Candidate) ou 3.4.0 (version OK)
$version_grr = "4.0.1";
# Version BDD, deux premirs chiffres = version majeur, les deux suivant la version, évolution de GRR, les 3 derniers une incrémentation à chaque changement
# Ex 0402003 : Version 4.2.X 3eme modification sur la branche 4.2.X
$version_bdd = "0400001";
// Version repository (GitHub) GitHub-Master / Release-v4.0.0-beta.1 / Release-v4.0.0
$versionReposite = "Release-v4.0.1";

################################
# Configuration Requise
#################################
// Version PHP minimum
$php_mini = "7.2.5";
// Version PHP maximum testé et validé par : JeromeB
$php_max_valide = "8.1.9";
// Version PHP maximum qui est sensé fonctionné, si compatible avec toutes les versions à ce jour laisser vide
$php_maxi = "8.1.11";

// Version MySQL minimum
$mysql_mini = "5.4.0";
// Version MySQL maximum testé et validé par : JeromeB
$mysql_max_valide = "5.7.39";
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
	"_j_mailuser_room",
	"_j_site_area",
	"_j_type_area",
	"_j_useradmin_area",
	"_j_useradmin_site",
	"_j_user_area",
	"_j_user_room",
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

# Liste des noms des langues
$liste_name_language = array(
	"Français",
	"Deutch",
	"English",
	"Italiano",
	"Spanish"
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
	"recherche_MAJ",
	"upload_Module",
	"nbMaxJoursLogConnexion",
	"motDePasseConfig",
	"sso_super_admin",
	"sso_restrictions",
	"ldap_restrictions",
	"imap_restrictions",
	"fonction_mail_restrictions",
	"Url_CAS_setFixedServiceURL",
	"dbsys",
	"structure",
	"donnees",
	"insertComplet"
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
