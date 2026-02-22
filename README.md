
# 🐶 GRR : Gestion et Réservation de Ressources 📅


![GitHub stars](https://img.shields.io/github/stars/JeromeDevome/GRR)
![GitHub forks](https://img.shields.io/github/forks/JeromeDevome/GRR)
![GitHub watchers](https://img.shields.io/github/watchers/JeromeDevome/GRR)
![GitHub followers](https://img.shields.io/github/followers/JeromeDevome?style=social)

![GitHub last commit](https://img.shields.io/github/last-commit/JeromeDevome/GRR)
![GitHub commit activity](https://img.shields.io/github/commit-activity/m/JeromeDevome/GRR)
![GitHub contributors](https://img.shields.io/github/contributors/JeromeDevome/GRR)

![GitHub language count](https://img.shields.io/github/languages/count/JeromeDevome/GRR)
![GitHub repo size](https://img.shields.io/github/repo-size/JeromeDevome/GRR)
![GitHub code size](https://img.shields.io/github/languages/code-size/JeromeDevome/GRR)
![GitHub top language](https://img.shields.io/github/languages/top/JeromeDevome/GRR)
![Symfony](https://img.shields.io/badge/Symfony-6-black?logo=symfony)
![PHP](https://img.shields.io/badge/PHP-8-777BB4?logo=php&logoColor=white)

![GRR](https://img.shields.io/badge/GRR-Open%20Source-blue)
![GitHub license](https://img.shields.io/github/license/JeromeDevome/GRR)

![Traductions](https://img.shields.io/badge/Languages-5-blue?style=flat-square)
![FR](https://img.shields.io/badge/🇫🇷-FR-blue?style=flat-square)
![EN](https://img.shields.io/badge/🇬🇧-EN-red?style=flat-square)
![ES](https://img.shields.io/badge/🇪🇸-ES-yellow?style=flat-square)
![IT](https://img.shields.io/badge/🇮🇹-IT-green?style=flat-square)
![DE](https://img.shields.io/badge/🇩🇪-DE-black?style=flat-square)

![GitHub Release Date](https://img.shields.io/github/release-date/JeromeDevome/GRR?label=date%20release)
![GitHub release (latest by date)](https://img.shields.io/github/v/release/JeromeDevome/GRR)

![GitHub (Pre-)Release Date](https://img.shields.io/github/release-date-pre/JeromeDevome/GRR?label=date%20pre-releases)
![GitHub release (latest by date including pre-releases)](https://img.shields.io/github/v/release/JeromeDevome/GRR?include_prereleases&label=pre-release)

[![Site GRR](https://img.shields.io/badge/Site-GRR-blue?style=for-the-badge&logo=google-chrome&logoColor=white)](https://grr.devome.com/)
[![Forum GRR](https://img.shields.io/badge/Forum-GRR-orange?style=for-the-badge&logo=discourse&logoColor=white)](https://site.devome.com/fr/grr/forum-grr)
[![Chat Status](https://img.shields.io/discord/484639573243068417.svg?style=for-the-badge)](https://discord.com/channels/484639573243068417/)
[![Documentation](https://img.shields.io/badge/Documentation-GRR-success?style=for-the-badge&logo=readthedocs&logoColor=white)](https://devome.com/GRR/DOC/)


> [!CAUTION]
> Pour toute mise en production, merci de ne pas utiliser directement le code issu des branches, celles-ci pouvant contenir des développements en cours de test ainsi que des librairies destinées uniquement à la compilation.
> Veuillez utiliser exclusivement les versions publiées via les releases.


> [!IMPORTANT]  
> Versions disposant de support et de correctifs: 4.4.X, les versions précédentes doivent être mises à jour.


----------

Installation
-------------

Pour obtenir une description complète de la procédure d'installation, veuillez vous reporter à la [documentation](https://devome.com/GRR/DOC/installation-et-mise-a-jour/installation)

> [!NOTE]  
>**Requiert :**
>
>PHP : >= 8.1.0 && <= 8.3; nécessite au moins les modules php-fileinfo, php-gd, php-mbstring, php-mysqli, php-mysqlnd, php-xml, php-intl (*)
> 
>MySQL: >= 5.4 && <= 9.4

Pour une installation simplifiée, décompressez simplement cette archive sur un serveur, et indiquez l'adresse où se trouvent les fichiers extraits dans un navigateur (ex: http://www.monsite.fr/grr).

>Préalables pour l'installation automatisée :
>disposer d'un espace FTP sur un serveur, pour y transférer les fichiers
>disposer d'une base de données MySQL (adresse du serveur MySQL, login, mot de passe)


Mise à jour
-------------

Vous devez faire une mise à jour classique en suivant la procédure selon votre version actuelle de GRR :
- Version < GRR 4.0.X [Documentation - Mise à jour - Depuis version < 4](https://devome.com/GRR/DOC/installation-et-mise-a-jour/mise-a-jour-depuis-version-less-than-4)
- Version >= GRR 4.0.X [documentation - Mise à jour - Depuis version >= 4](https://devome.com/GRR/DOC/installation-et-mise-a-jour/mise-a-jour-depuis-version-greater-than-4)

En plus de la mise à jour classique, veuillez rendre accessible le dossier "personnalisation" en écriture. C'est désormais dans ce dossier unique que vos personnalisations seront sauvegardées.


Licence
-------------
**GRR** est publié sous les termes de la **GNU General Public Licence**, dont le contenu est disponible dans le fichier "**LICENSE**", en anglais et dans le fichiers "**licence_fr.html**" en français. **GRR** est gratuit, vous pouvez le copier, le distribuer, et le modifier, à condition que chaque partie de **GRR** réutilisée ou modifiée reste sous licence **GNU GPL**. Par ailleurs et dans un soucis d'efficacité, merci de rester en contact avec le développeur de **GRR** pour éventuellement intégrer vos contributions à une distribution ultérieure.

Enfin, **GRR** est livré en l'état sans aucune garantie. Les auteurs de cet outil ne pourront en aucun cas être tenus pour responsables d'éventuels bugs.
**GRR** est une adaptation d'une application **MRBS**.


Remarques concernant la sécurité
-------------------

La sécurisation de **GRR** est dépendante de celle du serveur. Nous vous recommandons d'utiliser un serveur Apache ou Nginx sous Linux, en utilisant le protocole **https** (transferts de données cryptées), et en veillant à toujours utiliser les dernières versions des logiciels impliqués (notamment **Apache/Nginx** et **PHP**).

L'EQUIPE DE DEVELOPPEMENT DE GRR NE SAURAIT EN AUCUN CAS ETRE TENUE POUR RESPONSABLE EN CAS D'INTRUSION EXTERIEURE LIEE A UNE FAIBLESSE DE GRR OU DE SON SUPPORT SERVEUR.

(*) en cas de dysfonctionnement, il est possible que d'autres modules de PHP soient manquants. Merci d'en tenir l'équipe de développement informée.

