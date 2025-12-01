
GRR
===================

GRR est un outil de gestion et de réservation de ressources. **GRR** est une adaptation d'une application **MRBS**.


![GitHub language count](https://img.shields.io/github/languages/count/JeromeDevome/GRR)
![GitHub top language](https://img.shields.io/github/languages/top/JeromeDevome/GRR)

![GitHub Release Date](https://img.shields.io/github/release-date/JeromeDevome/GRR?label=date%20release)
![GitHub release (latest by date)](https://img.shields.io/github/v/release/JeromeDevome/GRR)

![GitHub (Pre-)Release Date](https://img.shields.io/github/release-date-pre/JeromeDevome/GRR?label=date%20pre-releases)
![GitHub release (latest by date including pre-releases)](https://img.shields.io/github/v/release/JeromeDevome/GRR?include_prereleases&label=pre-release)

Site: https://grr.devome.com/

Forum : https://site.devome.com/fr/grr/forum-grr

Chat/Discord : https://discord.com/channels/484639573243068417/

Documentation : https://devome.com/GRR/DOC/

```diff
+ Versions disposant de support et de correctifs: 4.4.X, les versions précédentes doivent être mises à jour.
```

**Requiert :**

PHP : >= 8.1.0 && <= 8.3; nécessite au moins les modules php-fileinfo, php-gd, php-mbstring, php-mysqli, php-mysqlnd, php-xml, php-intl (*)
MySQL: >= 5.4 && <= 8.4



----------

Installation
-------------

Pour obtenir une description complète de la procédure d'installation, veuillez vous reporter à la [documentation](https://devome.com/GRR/DOC/installation-et-mise-a-jour/installation)

Pour une installation simplifiée, décompressez simplement cette archive sur un serveur, et indiquez l'adresse où se trouvent les fichiers extraits dans un navigateur (ex: http://www.monsite.fr/grr).

>Préalables pour l'installation automatisée :
>disposer d'un espace FTP sur un serveur, pour y transférer les fichiers
>disposer d'une base de données MySQL (adresse du serveur MySQL, login, mot de passe)


Mise à jour
-------------

Vous devez faire une mise à jour classique en suivant la procédure selon votre version actuelle de GRR : `Attention PHP 7.2.5 minimum !`
- Version < GRR 4.0.X [Documentation - Mise à jour - Depuis version < 4](https://devome.com/GRR/DOC/installation-et-mise-a-jour/mise-a-jour-depuis-version-less-than-4)
- Version >= GRR 4.0.X [documentation - Mise à jour - Depuis version >= 4](https://devome.com/GRR/DOC/installation-et-mise-a-jour/mise-a-jour-depuis-version-greater-than-4)

En plus de la mise à jour classique, veuillez rendre accessible le dossier "personnalisation" en écriture. C'est désormais dans ce dossier unique que vos personnalisations seront sauvegardées.


Licence
-------------
**GRR** est publié sous les termes de la **GNU General Public Licence**, dont le contenu est disponible dans le fichier "**LICENSE**", en anglais et dans le fichiers "**licence_fr.html**" en français. **GRR** est gratuit, vous pouvez le copier, le distribuer, et le modifier, à condition que chaque partie de **GRR** réutilisée ou modifiée reste sous licence **GNU GPL**. Par ailleurs et dans un soucis d'efficacité, merci de rester en contact avec le développeur de **GRR** pour éventuellement intégrer vos contributions à une distribution ultérieure.

Enfin, **GRR** est livré en l'état sans aucune garantie. Les auteurs de cet outil ne pourront en aucun cas être tenus pour responsables d'éventuels bugs.


Remarques concernant la sécurité
-------------------

La sécurisation de **GRR** est dépendante de celle du serveur. Nous vous recommandons d'utiliser un serveur Apache ou Nginx sous Linux, en utilisant le protocole **https** (transferts de données cryptées), et en veillant à toujours utiliser les dernières versions des logiciels impliqués (notamment **Apache/Nginx** et **PHP**).

L'EQUIPE DE DEVELOPPEMENT DE GRR NE SAURAIT EN AUCUN CAS ETRE TENUE POUR RESPONSABLE EN CAS D'INTRUSION EXTERIEURE LIEE A UNE FAIBLESSE DE GRR OU DE SON SUPPORT SERVEUR.

(*) en cas de dysfonctionnement, il est possible que d'autres modules de PHP soient manquants. Merci d'en tenir l'équipe de développement informée.

