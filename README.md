GRR
version 3.5.1, a vocation à rester compatible avec php5 pour les serveurs non encore mis à jour en php7
===================


-------------------
Qu'est-ce que GRR ?
-------------------

**GRR** est un système de Gestion et de Réservations de Ressources, particulièrement adapté à la gestion et la 
réservation de salles et de matériels, mais peut également être utilisé comme mini-agenda partagé.

Les ressources sont organisées en domaines et les domaines peuvent être regroupés par site.

**Fonctionnalités de GRR**

* accès à l'outil de gestion partagée via n'importe quel navigateur web,
* visualisation publique ou soumise à authentification,
* administration de plusieurs types de ressources partagées (salles, voitures, matériels, ...),
* gestion des réservations de ces ressources (création, modification, suppression, copie, échange),
* notion de domaine restreint,
* paramétrage de l'accès à une ressource (visibilité et réservation),
* configuration d'une fiche de présentation avec une image pour chaque ressource,
* définition de créneaux horaires ou personnalisés,
* définition de réservations périodiques,
* définition de "jours cycle" (extension des réservations périodiques),
* configuration d'un calendrier des jours inaccessibles aux réservations,
* définition des jours fériés et des jours de vacances, mis en évidence sur les plannings, et recopie dans le calendrier hors réservations,
* définition de types de réservation avec un code couleur,
* définition de champs additionnels, domaine par domaine,
* définition de délais minimal ou maximal pour qu'un utilisateur puisse poser une réservation,
* gestion fine d'envoi de mails automatiques,
* gestion des droits des utilisateurs : gestion des ressources, domaines, sites, utilisateurs,
* possibilité de définir des réservations sur une journée entière,
* définition de réservations en blocs, sur plusieurs jours, plusieurs ressources,
* réservations « sous réserve », à confirmer avant une date fixée,
* réservations « avec modération », à confirmer par un gestionnaire,
* réservations au nom d'autres utilisateurs ou de personnes extérieures,
* possibilité de rendre temporairement indisponible une ressource pour maintenance par exemple,
* possibilité d'afficher une ressource "occupée",
* planning journalier, hebdomadaire, mensuel ou annuel, pour une ressource ou un domaine,
* personnalisation des paramètres d'affichage,
* gestion des clés,
* importation de fichiers de données pour poser des réservations (csv, emplois du temps UDT ou EDT),


Site de présentation : https://grr.devome.com/

Forum d'entraide : https://site.devome.com/fr/grr/forum-grr

Chat/Discord : https://discord.com/channels/484639573243068417/

-------------
Installation
-------------

**Requiert :**

PHP : >= 5.6 && <= 8.2, nécessite au moins les modules php-fileinfo, php-gd, php-iconv, php-mbstring, php-mysqli, php-mysqlnd, php-xml (*)
si vous utilisez php 8.1+, il faut soit inclure le module php-intl, soit désactiver les messages E_DEPRECATED

MySQL: > 5.4 && <= 5.6, compatibilité vraisemblable avec MySQL 5.7, compatible avec MariaDB 10+

GRR v3.5.1 requiert moins de 20 MO d'espace disque pour les scripts.

Pour obtenir une description complète de la procédure d'installation, veuillez vous reporter au fichier "**INSTALL.txt**", notamment pour régler les droits d'accès à certains fichiers ou dossiers.

Pour une installation simplifiée, décompressez cette archive sur un serveur, gérez les droits d'accès comme indiqué dans INSTALL.txt et indiquez l'adresse où se trouvent les fichiers extraits dans un navigateur (ex: http://www.monsite.fr/grr).

>Préalables pour l'installation automatisée :
>disposer d'un espace FTP sur un serveur, pour y transférer les fichiers et régler les droits d'accès,
>disposer d'une base de données MySQL (adresse du serveur MySQL, login, mot de passe).

Licence
-------------
**GRR** est publié sous les termes de la **GNU General Public Licence**, dont le contenu est disponible dans le fichier "**LICENSE**", en anglais et dans le fichier "**licence_fr.html**" en français. **GRR** est gratuit, vous pouvez le copier, le distribuer, et le modifier, à condition que chaque partie de **GRR** réutilisée ou modifiée reste sous licence **GNU GPL**. Par ailleurs et dans un souci d'efficacité, merci de rester en contact avec le développeur de **GRR** pour éventuellement intégrer vos contributions à une distribution ultérieure.

Enfin, **GRR** est livré en l'état sans aucune garantie. Les auteurs de cet outil ne pourront en aucun cas être tenus pour responsables d'éventuels bugs.


Remarques concernant la sécurité
-------------------

La sécurisation de **GRR** est dépendante de celle du serveur. Nous vous recommandons d'utiliser un serveur Apache ou Nginx sous Linux, en utilisant le protocole **https** (transferts de données cryptées), et en veillant à toujours utiliser les dernières versions des logiciels impliqués (notamment **Apache/Nginx** et **PHP**).

L'EQUIPE DE DEVELOPPEMENT DE GRR NE SAURAIT EN AUCUN CAS ETRE TENUE POUR RESPONSABLE EN CAS D'INTRUSION EXTERIEURE LIEE A UNE FAIBLESSE DE GRR OU DE SON SUPPORT SERVEUR.

(*) en cas de dysfonctionnement, il est possible que d'autres modules de PHP soient manquants. Merci d'en tenir l'équipe de développement informée.
