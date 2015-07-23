<?php
/**
 * misc.inc.php
 * fichier de variables diverses
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2010-04-07 17:49:56 $
 * @author    Laurent Delineau <laurent.delineau@ac-poitiers.fr>
 * @copyright Copyright 2003-2008 Laurent Delineau
 * @link      http://www.gnu.org/licenses/licenses.html
 * @package   root
 * @version   $Id: misc.inc.php,v 1.16 2010-04-07 17:49:56 grr Exp $
 * @filesource
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GRR is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GRR; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

################################
# Development information
#################################
$grr_devel_email = "support@devome.com";
$grr_devel_url = "http://grr.devome.com/";
// Numéro de version actuel
$version_grr = "3.0.0";
// Numéro de sous-version actuel (a, b, ...)
// Utilisez cette variable pour des versions qui corrigent la la version finale sans toucher à la base.
$sous_version_grr = "a";
// Numéro de la release candidate (doit être strictement inférieure à 9). Laisser vide s'il s'agit de la version stable.
$version_grr_RC = "1";

# Liste des tables
$liste_tables = array(
	"_area",
	"_area_periodes",
	"_calendar",
	"_calendrier_jours_cycle",
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
	);

# Liste des feuilles de style
$liste_themes = array(
	"default",
	"vert",
	"violet",
	"orange",
	"bleu",
	"rouge",
	"rose",
	"fluo"
	);

# Liste des noms des styles
$liste_name_themes = array(
	"Grand bleu",
	"Verdoyant",
	"Violeta",
	"Orange Talmont",
	"Bleu Talmont",
	"Rouge Feu",
	"Roseline",
	"Jaune Fluo"
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
	"Deutch",
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
$resolution = 900;

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
