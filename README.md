GRR
version 3.4.3, a vocation à rester compatible avec php5 pour les serveurs non encore mis à jour en php7
===================

**Requiert :**

PHP : >= 5.6 && <= 7.4, compatibilité en cours de vérification avec php8.0, nécessite au moins les modules php-gd, php-mbstring, php-mysqli, php-mysqlnd, php-xml (*)
MySQL: > 5.4 && <= 5.6, compatibilité vraisemblable avec MySQL 5.7

Site: https://grr.devome.com/

Forum : https://site.devome.com/fr/grr/forum-grr

Chat/Discord : https://discord.com/channels/484639573243068417/

-------------
Installation
-------------

Pour obtenir une description complète de la procédure d'installation, veuillez vous reporter au fichier "**INSTALL.txt**".

Pour une installation simplifiée, décompressez simplement cette archive sur un serveur, et indiquez l'adresse où se trouvent les fichiers extraits dans un navigateur (ex: http://www.monsite.fr/grr).

>Préalables pour l'installation automatisée :
>disposer d'un espace FTP sur un serveur, pour y transférer les fichiers
>disposer d'une base de données MySQL (adresse du serveur MySQL, login, mot de passe)

Licence
-------------
**GRR** est publié sous les termes de la **GNU General Public Licence**, dont le contenu est disponible dans le fichier "**LICENSE**", en anglais et dans le fichiers "**licence_fr.html**" en français. **GRR** est gratuit, vous pouvez le copier, le distribuer, et le modifier, à condition que chaque partie de **GRR** réutilisée ou modifiée reste sous licence **GNU GPL**. Par ailleurs et dans un soucis d'efficacité, merci de rester en contact avec le développeur de **GRR** pour éventuellement intégrer vos contributions à une distribution ultérieure.

Enfin, **GRR** est livré en l'état sans aucune garantie. Les auteurs de cet outil ne pourront en aucun cas être tenus pour responsables d'éventuels bugs.


Remarques concernant la sécurité
-------------------

La sécurisation de **GRR** est dépendante de celle du serveur. Nous vous recommandons d'utiliser un serveur Apache ou Nginx sous Linux, en utilisant le protocole **https** (transferts de données cryptées), et en veillant à toujours utiliser les dernières versions des logiciels impliqués (notamment **Apache/Nginx** et **PHP**).

L'EQUIPE DE DEVELOPPEMENT DE GRR NE SAURAIT EN AUCUN CAS ETRE TENUE POUR RESPONSABLE EN CAS D'INTRUSION EXTERIEURE LIEE A UNE FAIBLESSE DE GRR OU DE SON SUPPORT SERVEUR.

(*) en cas de dysfonctionnement, il est possible que d'autres modules de PHP soient manquants. Merci d'en tenir l'équipe de développement informée.
