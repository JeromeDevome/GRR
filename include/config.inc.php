<?php
/**
 * config.inc.php
 * Fichier de configuration de GRR
 * Dernière modification : $Date: 2017-12-16 14:00$
 * @author    JeromeB & Laurent Delineau
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
 
### A LIRE ###
# Il est préférable de pas modifier ce fichier, car celui-ci sera écrasé lors des mises à jours
# Nous conseillons de creer un fichier dans ce dossier en l'appelant "configperso.inc.php"
# Dans ce dernier ajouter les variables souhaité avec les valeurs souhaité, vos valeurs écraserons ce de ce fichier
##############

/*
Problème de sessions qui expirent prématurément :
Chez certains prestataire qui utilisent des serveurs en clustering, il arrive que les sessions expirent aléatoirement.
Une solution consiste à enregistrer les sessions PHP dans un autre répertoire que le répertoire par défaut.
Pour cela, il suffit de décommenter la ligne suivante (en supprimant le premier caractère #)
et en indiquant à la place de "le_chemin_de_stockage_de_la_session", l'emplacement du nouveau dossier de stockage des sessions.
*/
# ini_set ('session.save_path' , 'le_chemin_de_stockage_de_la_session');


/*
$nb_year_calendar permet de fixer la plage de choix de l'année dans le choix des dates de début et fin des réservations
La plage s'étend de année_en_cours - $nb_year_calendar à année_en_cours + $nb_year_calendar
Par exemple, si on fixe $nb_year_calendar = 5 et que l'on est en 2005, la plage de choix de l'année s'étendra de 2000 à 2010
*/
$nb_year_calendar = 10;

# Avance en nombre d'heure du serveur sur les postes clients
# Le paramètre $correct_diff_time_local_serveur permet de corriger une différence d'heure entre le serveur et les postes clients
# Exemple : si Grr est installé sur un serveur configuré GMT+1 alors qu'il est utilisé dans un pays dont le fuseau horaire est GMT-5
# Le serveur a donc six heures d'avance sur les postes clients
# On indique alors : $correct_diff_time_local_serveur=6;
#$correct_diff_time_local_serveur= 2;

/* Paramétrage du fuseau horaire (imposer à GRR un fuseau horaire différent de celui du serveur)
 TZ (Time Zone) est une variable permettant de préciser dans quel fuseau horaire, GRR travaille.
 L'ajustement de cette variable TZ permet au programme GRR de travailler dans la zone de votre choix.
 la valeur à donner à TZ diffère d'un système à un autre (Windows, Linux, ...)
 Par exemple, sur un système Linux, si on désire retarder de 7 heures l'heure système de GRR, on aura :
 putenv("TZ=posix/Etc/GMT-7")
 Remarque : putenv() est la fonction php  qui permet de fixer la valeur d'une variable d'environnement.
 Cette valeur n'existe que durant la vie du script courant, et l'environnement initial sera restauré lorsque le script sera terminé.
 En résumé, pour activer cette fonctionnalité, décommentez la ligne suivante (en supprimant le premier caractère #,
 et remplacez -7 par +n ou -n où "n" est le nombre d'heures d'avance ou de retard de GRR sur l'heure système du serveur.
*/
 $timezone = 'America/Toronto';
date_default_timezone_set($timezone);

# Affichage d'un domaine par defaut en fonction de l'adresse IP de la machine cliente (voir documentation)
# Mettre 0 ou 1 pour désactiver ou activer la fonction dans la page de gestion des domaines
 define('OPTION_IP_ADR', 1);

# Nom de la session PHP.
# Le nom de session fait référence à l'identifiant de session dans les cookies.
# Il ne doit contenir que des caractères alpha-numériques; si possible, il doit être court et descriptif.
# Normalement, vous n'avez pas à modifier ce paramètre.
# Mais si un navigateur est amené à se connecter au cours de la même session, à deux sites GRR différents,
# ces deux sites GRR doivent avoir des noms de session différents.
# Dans ce cas, il vous faudra changer la valeur GRR ci-dessous par une autre valeur.
 define('SESSION_NAME', "GRR");

# Nombre maximum (+1) de réservations autorisés lors d'une réservation avec périodicité
 $max_rep_entrys = 365 + 1;

# Positionner la valeur $unicode_encoding à 1 pour utiliser l'UTF-8 dans toutes les pages et dans la base
# Dans le cas contraire, les textes stockés dans la base dépendent des différents encodage selon la langue selectionnée par l'utilisateur
# Il est fortement conseillé de lire le fichier notes-utf8.txt à la racine de cette archive
 $unicode_encoding = 1;

# Après installation de GRR, si vous avez le message "Fatal error: Call to undefined function: mysql_real_escape_string() ...",
# votre version de PHP est inférieure à 4.3.0.
# En effet, la fonction mysql_real_escape_string() est disponible à partir de la version 4.3.0 de php.
# Vous devriez mettre à jour votre version de php.
# Sinon, positionnez la variable suivante à "0"; (valeur par défaut = 1)
 $use_function_mysql_real_escape_string = 1;

# Apres installation de GRR, si vous avez le message "Fatal error: Call to undefined function: html_entity_decode() ...",
# votre version de PHP est inferieure a 4.3.0.
# En effet, la fonction html_entity_decode() est disponible a partir de la version 4.3.0 de php.
# Vous devriez mettre a jour votre version de php.
# Sinon, positionnez la variable suivante a "0"; (valeur par defaut = 1)
 $use_function_html_entity_decode = 1;

#Requiert Connexion pour mettre à jour la BDD || 0: non ; 1: oui - Defaut 1
$connexionAdminMAJ = 1;

#L'admin peut restaurer une base depuis l'administration || 0: non ; 1: oui - Defaut 1
$restaureBBD = 1;

#Mode debug || 0: non ; 1: oui - Defaut 0
$debug_flag = 0;

#Rechercher des MAJ sur le serveur grr.devome.com || 0: non ; 1: oui - Defaut 1
$recherche_MAJ = 1;

#Possibilité d'upload de module || 0: non ; 1: oui - Defaut 1
$upload_Module = 1;

# Nb de jour maximum que l'on garde les logs de connexions, 0 = aucune limite
$nbMaxJoursLogConnexion = 365;

# Mot de passe simple
	# Valeurs  3- azerty  || 4- Vide || 6- 123456  || 7- 1234567 || 8- 12345678 || 9- 000000 || 10- 00000000 
	$mdpFacile = array("ab4f63f9ac65152575886860dde480a1", "", "e10adc3949ba59abbe56e057f20f883e", "fcea920f7412b5da7be0cf42b8c93759", "25d55ad283aa400af464c76d713c07ad", "670b14728ad9902aecba32e22fa4f6bd", "dd4b21e9ef71e1291183a46b913ae6f2");


##################################################
# Cas d'une authentification via config.inc.php  #
##################################################
$motDePasseConfig = ""; // vide = ignoré

###################################
# Cas d'une authentification SSO  #
###################################

/*
$sso_super_admin : false|true
Mettre la valeur du paramètre $sso_super_admin à "true" pour rendre possible l'accès à la page login.php même si l'administrateur a coché dans l'interface en ligne le choix "Empêcher l'accès à la page de login".
*/
$sso_super_admin = false;

/*
 $sso_restrictions : false|true
 Mettre la valeur du paramètre $sso_restrictions à "true" permet de cacher dans l'interface de GRR l'affichage de la rubrique "Configuration SSO"
*/
 $sso_restrictions = false;

/*
 $ldap_restrictions : false|true
 Mettre la valeur du paramètre $ldap_restrictions à "true" permet de cacher dans l'interface de GRR l'affichage de la rubrique "Configuration LDAP"
*/
 $ldap_restrictions = false;

/*
 $imap_restrictions : false|true
 Mettre la valeur du paramètre $imap_restrictions à "true" permet de cacher dans l'interface de GRR l'affichage de la rubrique "Configuration IMAP"
*/
 $imap_restrictions = false;

// Le paramètre $Url_CAS_setFixedServiceURL est le paramètre utilisé dans la méthode phpCAS::setFixedServiceURL(), dans le fichier cas.inc.php
// Si ce paramètre est non vide, il sera utilisé par le service CAS
// Set the fixed URL that will be set as the CAS service parameter. When this method is not called, a phpCAS script uses its own URL.
 $Url_CAS_setFixedServiceURL = '';


#####################################################
# Paramètres propres à une authentification SSO LASSO
#####################################################
// Indiquez ci-dessous le répertoire d'installation du package spkitlasso
// (la valeur par défaut le cherche dans le 'include_path' de PHP)
 define('SPKITLASSO',"spkitlasso");

###################
# Database settings
###################

# Quel système de base de données : "pgsql"=PostgreSQL, "mysql"=MySQL
# Actuellement, GRR ne supporte que mysql.
$dbsys = "mysql";
# Uncomment this to NOT use PHP persistent (pooled) database connections:
$db_nopersist = 1;

################################
# Backup information
#################################
#true=sauvegarde la structure des tables
$structure = true;
#true=sauvegarde les donnees des tables
$donnees = true;
#clause INSERT avec nom des champs
$insertComplet = false;

# Global settings array
$grrSettings = array();
$grrPages = array();

# Make sure notice errors are not reported
#error_reporting (E_ALL ^ E_NOTICE);

if(file_exists('../include/configperso.inc.php'))
	include('../include/configperso.inc.php');
if(file_exists('./include/configperso.inc.php'))
	include('./include/configperso.inc.php');

?>
