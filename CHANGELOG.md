# Change Log
Changelog en markdown, en essayant de suivre les conseils de : http://keepachangelog.com/
Les tags :
###Added 
* for new features.
###Changed 
* for changes in existing functionality.
###Deprecated 
* for once-stable features removed in upcoming releases.
###Removed 
* for deprecated features removed in this release.
###Fixed 
* for any bug fixes.
###Security 
* to invite users to upgrade in case of vulnerabilities.

## [] - 2015-08-10
###Added
* composer à la racine du projet
* twig géré par composer, ajouté dans "vendor"
* répertoires :
    *'Les répertoires suivent la structure d'un projet symfony2'
    * app/cache
    * src/Main/Ressources/Views
###[Unrelaesed]
* separation de l'affichage dans le fichier day.php
* separation de l'affichage des erreurs de day.php
   
    

[correction] mktine() vers time()
[correction] Fonction mysql_ vers mysqli
[correction] Remise ne place des jours et heures des reservation lors de l'edition
[correction] Correction de minical sauvegarde du choix de la salle et du type d'affichage
[correction] Balise php courte dans view_entry.php
[correction] undefined index dans mincals.php
[correction] undefined index dans edit_entry.php
[correction] undefined index dans view_entry.php
[correction] undefined index dans menu_gauche.php
[correction] undefined index dans day.php
[correction] undefined index dans week.php
[correction] undefined index dans week_all.php
[correction] undefined index dans month.php
[correction] undefined index dans month_all.php
[correction] undefined index dans month_all2.php
[correction] undefined variable dans functions.inc.php
[correction] Encodage des caracteres UTF-8 des pages
[correction] Encodage des caracteres UTF-8 des pages en popup
[correction] Encodage des caracteres UTF-8 des fichier genere
[correction] affichage des pages de moderation avec l'option popup
[correction] CSS print
[correction] Traductions
[correction] session/traduction sur certaines pages
[correction] affichage du menu sur certaine page de l'admin
[correction] Balise php courte dans contactFormulaire.php
[correction] Affichage reservation sur 2 jours
[correction] boutton aujourd'hui de mincals
[ajout] gestion des droit dans contactFormulaire.php
[ajout] bootstrap twitter pour l'apparence
[ajout] fonction cle donne/rendu
[ajout] fonction courrier recu
[ajout] choix de reservation periodique chaque Xeme Yjour du mois
[ajout] Jour férié
[ajout] Vacance scolaire francaise
[ajout] Settings pour les jours feries et vacances
[ajout] Champs de recherche dans le select des beneficiare de edit_entry.php
[maj] lib Jquery 2.1.1
[maj] script popup.js compatibilité jquery
[maj] fontion bouton_retour_haut() compatibilité jquery
[maj] lib jquery.validate.js
[maj] lib ckeditor 4.4.5
[maj] lib Jquery-ui 1.11.1
[maj] lib datepicker
[maj] lib timepicker 1.5.0
[maj] lib phpmailer 5.2.9+
[maj] lib jspdf master-43eb081
[maj] Traductions
[change] Correction diverse du code HTML generer W3c validation
[change] Ordre de chargemet des fichier css et js
[change] Création et deplacemet des fichier js dans le dossier js
[change] Netoyage du code / Mise en forme
[change] Apparance utilisation de bootstrap et de glyphicon
[change] Simplification de la fonction grr_sql_version par l'utilisation de mysqli_get_server_info
[change] Horloge mise a jour pour fonctionner sur chrome
[change] Affichage du menu gauche dans toutes les pages de l'administration
[change] Passage de la verification des droit via une fonction
[change] timepicker to clockpicker
[change] Generation de pdf avancé
[change] optimisation du mincals
[change] suppression des variable inutile dans showaccessdenied
[change] Gestion des settings via une class settings
[suppression] Fichiers inutile
[suppression] fonction html_entity_decode_all_version
[suppression] des fragments de code pour l'upload des fichiers
