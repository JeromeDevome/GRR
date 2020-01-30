<?php
/**
 * misc.inc.php
 * fichier de variables diverses
 * Dernière modification : $Date: 2020-01-28 17:15$
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
$grr_devel_email = "support@devome.com";
$grr_devel_url = "http://grr.devome.com/";
// Numéro de version actuel
$version_grr = "3.4.1";
// Numéro de sous-version actuel (a, b, ...)
// Utilisez cette variable pour des versions qui corrigent la la version finale sans toucher à la base.
$sous_version_grr = "b"; // a, b, c, ...
// Numéro de la release candidate (doit être strictement inférieure à 9). Laisser vide s'il s'agit de la version stable.
$version_grr_RC = "";
// Version repository (GitHub)
$versionReposite = "";

# Liste des tables
$liste_tables = array(
	"_area",
	"_area_periodes",
	"_calendar",
    "_calendrier_feries",
	"_calendrier_jours_cycle",
	"_calendrier_vacances",
	"_entry",
	"_entry_moderate",
	"_type_area",
	"_j_type_area",
	"_j_mailuser_room",
	"_j_user_area",
	"_j_user_room",
	"_log",
	"_repeat",
	"_room",
	"_setting",
	"_utilisateurs",
	"_j_useradmin_area",
	"_overload",
	"_site",
	"_j_useradmin_site",
	"_j_site_area",
	"_correspondance_statut",
	"_page",
	"_modulesext",
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
    "Perso"
	);

# Liste des langues
$liste_language = array(
	"fr",
	"de",
	"en",
	"it",
	"es"
	);

# Liste des noms des langues
$liste_name_language = array(
	"Français",
	"Deutsch",
	"English",
	"Italiano",
	"Spanish"
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

# Début de la semaine: 0 pour dimanche, 1 pour lundi, etc.
$weekstarts = 1;

# Format d'affichage du temps : valeur 0 pour un affichage « 12 heures » et valeur 1 pour un affichage  « 24 heures ».
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
