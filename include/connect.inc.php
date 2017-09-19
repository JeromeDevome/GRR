<?php
# Les quatre lignes suivantes sont à modifier selon votre configuration
# ligne suivante : le nom du serveur qui herberge votre base sql.
# Si c'est le même que celui qui heberge les scripts, mettre "localhost"
switch($_SERVER['SERVER_NAME']) {
    case 'grr-bkp.web-dev.flsh.usherbrooke.ca':
    case 'grr-svn.web-dev.flsh.usherbrooke.ca':
    case 'grr-git.web-dev.flsh.usherbrooke.ca':
    case 'grr.web-dev.flsh.usherbrooke.ca':
        $dbHost="sgbd1.flsh.usherbrooke.ca";
        # ligne suivante : le nom de votre base sql
        $dbDb="grr_dev";
        # ligne suivante : le nom de l'utilisateur sql qui a les droits sur la base
        $dbUser="GRRmusique";
        # ligne suivante : le mot de passe de l'utilisateur sql ci-dessus
        $dbPass="music2013";
        # ligne suivante : préfixe du nom des tables de données
        $table_prefix="";
        # ligne suivante : Port MySQL laissé par défaut
        $dbPort="";
        break;
    default:
        $dbHost="sgbd1.flsh.usherbrooke.ca";
        # ligne suivante : le nom de votre base sql
        $dbDb="grrmusique2016";
        # ligne suivante : le nom de l'utilisateur sql qui a les droits sur la base
        $dbUser="GRRmusique";
        # ligne suivante : le mot de passe de l'utilisateur sql ci-dessus
        $dbPass="music2013";
        # ligne suivante : préfixe du nom des tables de données
        $table_prefix="";
        # ligne suivante : Port MySQL laissé par défaut
        $dbPort="";
}
?>