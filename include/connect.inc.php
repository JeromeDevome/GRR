<?php
# Les quatre lignes suivantes sont à modifier selon votre configuration
# ligne suivante : le nom du serveur qui herberge votre base sql.
# Si c'est le même que celui qui heberge les scripts, mettre "localhost"
switch($_SERVER['CONTEXT']) {
    case 'CONTEXT_DEV':
        define('IS_PROD', false);
        $dbHost="bdweb-dev01.si.sti.usherbrooke.ca";
        # ligne suivante : le nom de votre base sql
        $dbDb="boip2402_grr";
        # ligne suivante : le nom de l'utilisateur sql qui a les droits sur la base
        $dbUser="boip2402_grr";
        # ligne suivante : le mot de passe de l'utilisateur sql ci-dessus
        $dbPass="VaJZXADW8tWkKraJ";
        # ligne suivante : préfixe du nom des tables de données
        $table_prefix="";
        # ligne suivante : Port MySQL laissé par défaut
        $dbPort=3306;
        break;
    case 'CONTEXT_AQ':
        define('IS_PROD', false);
        $dbHost="bdweb1-master-prod01.si.sti.usherbrooke.ca";
        # ligne suivante : le nom de votre base sql
        $dbDb="flsh-grr-aq";
        # ligne suivante : le nom de l'utilisateur sql qui a les droits sur la base
        $dbUser="flsh-grr-aq";
        # ligne suivante : le mot de passe de l'utilisateur sql ci-dessus
        $dbPass="FLuvTL36qxNzbNoE";
        # ligne suivante : préfixe du nom des tables de données
        $table_prefix="";
        # ligne suivante : Port MySQL laissé par défaut
        $dbPort=3306;
        break;
    default:
        define('IS_PROD', true);
        $dbHost="bdweb1-master-prod01.si.sti.usherbrooke.ca";
        # ligne suivante : le nom de votre base sql
        $dbDb="flsh-grr";
        # ligne suivante : le nom de l'utilisateur sql qui a les droits sur la base
        $dbUser="flsh-grr";
        # ligne suivante : le mot de passe de l'utilisateur sql ci-dessus
        $dbPass="hpXsqXZogjpD6ZYX";
        # ligne suivante : préfixe du nom des tables de données
        $table_prefix="";
        # ligne suivante : Port MySQL laissé par défaut
        $dbPort=3306;
}
?>