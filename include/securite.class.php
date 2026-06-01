<?php
/**
 * include/securite.class.php
 * fichier Bibliothèque de fonctions de GRR
 * Dernière modification : $Date: 2026-06-01 10:12$
 * @author    JeromeB & Laurent Delineau & Yan Naessens
 * @copyright Since 2003 Team DEVOME - JeromeB
 * @link      http://www.gnu.org/licenses/licenses.html
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 */


/* SecuChaine : Classe de fonctions de sécurisation des données
 * Cette classe regroupe des fonctions de nettoyage et de validation de données, pour réduire les risques de vulnérabilités XSS et SQL injection.
 * Les fonctions de cette classe sont utilisées dans tout le code de GRR pour sécuriser les données entrantes (GET, POST, etc.) avant de les utiliser ou de les stocker.
 * L'utilisation de ces fonctions contribue à renforcer la sécurité globale de l'application en empêchant l'exécution de code malveillant ou l'injection de requêtes SQL dangereuses.
*/
class SecuChaine {

    /*
    CleanInput: Nettoie une chaîne pour limiter les risques XSS (trim, stripslashes, htmlspecialchars).
    CleanLogin: Supprime les caractères non autorisés d’un identifiant utilisateur.
    Alphanumeric: Conserve uniquement les caractères Alphanumericériques, - et _.
    GetFormVar: Récupère une variable GET, POST ou COOKIE avec typage optionnel.
    ProtectDataSql: Protège une donnée contre les injections SQL via mysqli_real_escape_string.
    Unslashes: Supprime les antislashs ajoutés par magic_quotes_gpc.
    ValideCouleur: Valide et reformate une couleur hexadécimale.
    ValideDate: Vérifie qu’une date respecte un format donné.
    ValideMail: Vérifie la validité syntaxique d’une adresse e-mail.
    ValideNetworkIp: Vérifie la validité d’une ou plusieurs adresses IP.
    ValideNetworkPort: Vérifie qu’un port réseau est compris entre 1 et 65535.
    */

    /* Fonction de nettoyage d'une chaîne de caractères pour réduire les risques de vulnérabilités XSS
     * @param string $data - The input data to clean
     * @return string - The cleaned data
    */
    public static function CleanInput($data){
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }
    
    /* Elimine d'une chaîne tous les caractères non autorisés dans un identifiant d'utilisateur [a-zA-Z0-9_@.-]
     * @param string $login - The user login to clean
     * @return string - The cleaned login
    */
    public static function CleanLogin($_login){
        $clean = preg_replace("/[^a-zA-Z0-9_@.-]*/",'',$_login);
        return $clean;
    }

    /* Elimine d'une chaîne tous les caractères non Alphanumericériques, à l'exception de - et _
     * @param string $data - The input data to clean
     * @return string - The cleaned data
    */
    public static function Alphanumeric($data){
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlentities($data);
        $data = preg_replace('/[^A-Za-z0-9\-_]/', '', $data);

        return $data;
    }

    /* récupère les variables passées par GET ou POST ou bien par COOKIE, et leur affecte le type indiqué (int ou string)
     * @param string $nom - The name of the variable to retrieve
     * @param string $type - The expected type of the variable (default is empty, can be 'int' or 'string')
     * @param mixed $default - The default value if the variable is not found
     * @return mixed - The retrieved and typed variable or the default value
    */
    public static function GetFormVar($nom,$type='',$default=NULL){
        $valeur = isset($_GET[$nom])? $_GET[$nom] : (isset($_POST[$nom])? $_POST[$nom] : (isset($_COOKIE[$nom])? $_COOKIE[$nom] : $default));
        if ((isset($valeur)) && (($type =='int')||($type =='string')))
            settype($valeur,$type);
        return $valeur;
    }

    /* Transforms a string into a format safe for SQL queries, preventing SQL injection.
     * @param string $_value - The input value to protect
     * @return string - The protected value safe for SQL queries
    */
    public static function ProtectDataSql($_value)
    {
        global $use_function_mysql_real_escape_string;
        //if (get_magic_quotes_gpc())
        if (function_exists('get_magic_quotes_gpc') && @get_magic_quotes_gpc())
            $_value = stripslashes($_value);
        if (!is_numeric($_value) && $_value != null)
            $_value = mysqli_real_escape_string($GLOBALS['db_c'], $_value);

        return $_value;
    }

    /* Removes backslash-escape quoting if PHP is configured to do it with magic_quotes_gpc.
     * Use this whenever you need the actual value of a GET/POST form parameter (which might have special characters) regardless of PHP's magic_quotes_gpc setting.
     * @param string $s - The input string to unescape
     * @return string - The unescaped string
    */
    public static function Unslashes($s)
    {
        if (function_exists('get_magic_quotes_gpc') && @get_magic_quotes_gpc())
            return stripslashes($s);
        else
            return $s;
    }

    /* Transforms a string into a valid hexadecimal color format.
     * @param string $entry - The input string to validate as a color
     * @return string - The validated color in hexadecimal format
    */
    public static function ValideCouleur($entry)
    {
        $out = preg_replace('/[^a-fA-F0-9]/','',$entry);
        if (strlen($out)<4)
        {
            $out = '#'.substr($out.'000',0,3);
        }
        else
        {
            $out = '#'.substr($out.'000',0,6);
        }
        return($out);
    }

    /* Retourne true si la date est valide selon le format spécifié, false sinon
     * @param string $date - The input date string to validate
     * @param string $format - The expected date format (default 'Y-m-d H:i:s')
     * @return bool - True if the date is valid, false otherwise
    */
    public static function ValideDate($date, $format = 'Y-m-d H:i:s')
    {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) == $date;
    }

    /* Détermine si l'adresse mail en paramètre est syntaxiquement valable
     * @param string $email - The email address to validate
     * @return bool - True if the email is valid, false otherwise
    */
    public static function ValideMail($email)
    {
        $atom   = '[-a-z0-9!#$%&\'*+\\/=?^_`{|}~]';
            // caractères autorisés avant l'arobase
        $domain = '([a-z0-9]([-a-z0-9]*[a-z0-9]+)?)';
            // caractères autorisés après l'arobase (nom de domaine)
        $regex = '/^' . $atom . '+' . '(\.' . $atom . '+)*' . '@' . '(' . $domain . '{1,63}\.)+' . $domain . '{2,63}$/i';
        if (preg_match($regex, $email))
            return true;
        else {
            $regex2 = '/^' . $atom . '+' . '(\.' . $atom . '+)*' . '@' . 'localhost/i';
            return preg_match($regex2, $email);
        }
    }

    /* Contrôle validité ip
     * @param string $input - The IP address to validate
     * @return bool - True if the IP is valid, false otherwise
    */
    public static function ValideNetworkIp($input) {
        $pattern = '/^(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})(;\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})*$/';

        if (preg_match($pattern, $input)) {
            $ips = explode(';', $input);
            foreach ($ips as $ip) {
                if (!filter_var($ip, FILTER_VALIDATE_IP)) {
                    return false;
                }
            }
            return true;
        }
        return false;
    }

    /* Contrôle validité port réseau
     * @param string $port - The network port to validate
     * @return int|string - The valid port number if valid, empty string otherwise
    */
    public static function ValideNetworkPort($port) {
        $port = intval($port);
        if ($port >= 1 && $port <= 65535) {
            return $port;
        }
        return '';
    }

}

/* SecuAccess : Classe de fonctions de contrôle d'accès des utilisateurs
 * Cette classe regroupe des fonctions de vérification des droits d'accès des utilisateurs à différentes fonctionnalités de GRR, en fonction de leur rôle et de leurs permissions.
 * Les fonctions de cette classe sont utilisées dans tout le code de GRR pour contrôler l'accès aux pages, aux actions, et aux données sensibles, afin de garantir que seuls les utilisateurs autorisés peuvent effectuer certaines opérations.
 * L'utilisation de ces fonctions contribue à renforcer la sécurité globale de l'application en empêchant les accès non autorisés et en protégeant les données sensibles.
 * Elle ne gère pas les limites de réservations.
*/
class SecuAccess {

    /*
    CheckAccess: Vérifie qu’un utilisateur possède un niveau d’accès minimal.
    DisplayMail: Vérifie si l’utilisateur peut voir les adresses e-mail.
    IsAllowedModify_SSO: Vérifie si un utilisateur SSO peut modifier ses informations.
    IsAllowedToModifyEmail: Vérifie si l’utilisateur peut modifier son e-mail.
    IsAllowedToModifyMdp: Vérifie si l’utilisateur peut modifier son mot de passe.
    IsAllowedToModifyProfil: Vérifie si l’utilisateur peut modifier son profil.
    IsAllowedToModifyResa: Vérifie si l’utilisateur peut modifier/supprimer une réservation.
    ResourcesNotBookingForUser: Retourne les ressources où l’utilisateur ne peut pas réserver.
    UserArea: Vérifie l’accès d’un utilisateur à un domaine/zone.
    UserBookingResourceRestrict: Vérifie l’autorisation de réservation sur une ressource restreinte.
    UserLevel: Retourne le niveau de droits d’un utilisateur.
    UserResource: Vérifie l’accès d’un utilisateur à une ressource.
    UserSearch: Vérifie l’accès à l’outil de recherche.
    UserSheetReservation: Vérifie l’accès à la fiche de réservation d’une ressource.
    UserSheetResource: Vérifie l’accès à la fiche descriptive d’une ressource.
    UserSite: Vérifie l’accès d’un utilisateur à un site.
    VisitorBookingResource: Vérifie si un visiteur peut réserver une ressource.
    */

    /* Fonction de verification d'access
     * @param int $level
     * @param string $back
     * @return void
    */
    public static function CheckAccess($level, $back)
    {
        if (SecuAccess::UserLevel(getUserName(), -1, 'area') < $level)
        {
            showAccessDenied($back);
            exit();
        }
    }

    /* Vérifier l'accès à l'adresse email
     * @param string $user : le login de l'utilisateur
     * @param int $id_room : l'id de la ressource
     * @return bool
    */
    public static function DisplayMail($user, $id_room)
    {
        if (SecuAccess::UserLevel($user,$id_room) >= Settings::get("display_level_email"))
            return true;
        else
            return false;
    }

    /* Vérifie si utilisateur autorisé à changer ses noms et prénoms et mail
     * @return bool - True if allowed, false otherwise
    */
    private static function IsAllowedModify_SSO()
    {
        if (Settings::get("sso_IsNotAllowedModify")=="y")
        {
            $source = grr_sql_query1("SELECT source FROM ".TABLE_PREFIX."_utilisateurs WHERE login = '".getUserName()."'");
            if ($source == "ext")
                return false;
            else
                return true;
        }
        else
            return true;
    }

    /* Vérifie que l'utilisateur est autorisé à changer son email
     * @return bool - True if allowed, false otherwise
    */
    public static function IsAllowedToModifyEmail()
    {
        if (!(SecuAccess::IsAllowedModify_SSO()))
            return false;
            // l'utilisateur connecté n'a pas le niveau suffisant pour modifier son compte
        if (SecuAccess::UserLevel(getUserName(),-1) < Settings::get("allow_users_modify_email"))
            return false;
        else
            return true;
    }

    /* Vérifie que l'utilisateur est autorisé à changer son mot de passe
     * @return bool - True if allowed, false otherwise
    */
    public static function IsAllowedToModifyMdp() {
            // l'utilisateur connecté n'a pas le niveau suffisant pour modifier son compte
        if (SecuAccess::UserLevel(getUserName(), -1) < Settings::get("allow_users_modify_mdp"))
            return false;
        else if ((Settings::get("sso_statut") != "") or (Settings::get("ldap_statut") != '') or (Settings::get("imap_statut") != ''))
        {
                // ou bien on est dans un environnement SSO ou ldap et l'utilisateur n'est pas un utilisateur local
            $source = grr_sql_query1("SELECT source FROM ".TABLE_PREFIX."_utilisateurs WHERE login = '".getUserName()."'");
            if ($source == "ext")
                return false;
            else
                return true;
        }
        else
            return true;
    }
    /* Vérifie que l'utilisateur est autorisé à changer ses noms et prénoms
     * @return bool - True if allowed, false otherwise
    */
    public static function IsAllowedToModifyProfil()
    {
        if (!(SecuAccess::IsAllowedModify_SSO()))
            return false;
            // l'utilisateur connecté n'a pas le niveau suffisant pour modifier son compte
        if (SecuAccess::UserLevel(getUserName(),-1) < Settings::get("allow_users_modify_profil"))
            return false;
        else
            return true;
    }

    /* Fonction qui retourne 1 si l'utilisateur a le droit de modifier ou supprimer la réservation d'id $id, 0 sinon
     * @param string $user
     * @param int $id
     * @return int
    */
    public static function IsAllowedToModifyResa($user, $id)
    {
        if (Settings::get("allow_gestionnaire_modify_del") == 0)
            $temp = 3;
        else
            $temp = 2;
        $sql = "SELECT room_id, create_by, beneficiaire, dont_allow_modify, who_can_book, qui_peut_reserver_pour 
                FROM ".TABLE_PREFIX."_entry JOIN ".TABLE_PREFIX."_room ON room_id = ".TABLE_PREFIX."_room.id
                WHERE ".TABLE_PREFIX."_entry.id ='".SecuChaine::ProtectDataSql($id)."'";
        $res = grr_sql_query($sql);
        if (!$res)
            fatal_error(0, grr_sql_error());
        elseif (grr_sql_count($res) == 0) // réservation inconnue
            fatal_error(1, get_vocab('invalid_entry_id'));
        else {
            $data = grr_sql_row_keyed($res,0);
            grr_sql_free($res);
            if (SecuAccess::UserLevel($user,$data['room_id']) > $temp)
                return 1; // Modifications permises si l'utilisateur a les droits suffisants
            else {
                $user_can_book = $data['who_can_book'] || SecuAccess::UserBookingResourceRestrict($user,$data['room_id']);
                $createur = strtolower($data['create_by']);
                $beneficiaire = strtolower($data['beneficiaire']);
                $utilisateur = strtolower($user);
                /* Dans l'étude du cas d'un utilisateur sans droits particuliers, quatre possibilités :
                Cas 1 : l'utilisateur (U) n'est ni le créateur (C) ni le bénéficiaire (B)
                    R1 -> on retourne 0
                Cas 2 : U=B et U<>C  ou ...
                Cas 3 : U=B et U=C
                    R2 -> on retourne 0 si personne hormis les gestionnaires et les administrateurs ne peut modifier ou supprimer ses propres réservations.
                    R3 -> on retourne $user_can_book selon les droits de l'utilisateur sur la ressource
                Cas 4 : U=C et U<>B
                    R4 -> on retourne 0 si personne hormis les gestionnaires et les administrateurs ne peut modifier ou supprimer ses propres réservations.
                    -> sinon
                        R5 -> on retourne $user_can_book selon les droits de l'utilisateur U sur la ressource et s'il peut réserver la ressource pour B
                        R6 -> on retourne 0 sinon (si on permettait à U d'éditer la résa, il ne pourrait de toute façon pas la modifier)*/
                if (($utilisateur != $beneficiaire) && ($utilisateur != $createur)) // cas 1
                    return 0;
                elseif ($utilisateur == $beneficiaire) // cas 2 et 3
                {
                    if (SecuAccess::UserLevel($user, $data['room_id']) > 2) 
                        return 1; // un gestionnaire de ressource peut toujours modifier ses propres réservations
                    elseif ($data['dont_allow_modify'] == 'y')
                        return 0; // un simple utilisateur ne peut pas modifier ses propres réservations
                    else 
                        return $user_can_book;
                }
                elseif ($utilisateur == $createur) // cas 4
                {
                    if (SecuAccess::UserLevel($user, $data['room_id']) > 2) 
                        return 1; // un gestionnaire de ressource peut toujours modifier ses propres réservations
                    elseif ($data['dont_allow_modify'] == 'y')
                        return 0; // un simple utilisateur ne peut pas modifier ses propres réservations
                    else
                    {
                        if (SecuAccess::UserLevel($user, $data['room_id']) >= $data['qui_peut_reserver_pour'])
                            return $user_can_book;
                        else
                            return 0;
                    }
                }
            }
        }
    }

    /* Détermine les ressources dans lesquelles l'utilisateur ne peut pas réserver (droits insuffisants ou ressource restreinte)
     * @param string $user - l'identifiant de l'utilisateur
     * @return array - la liste des identifiants des ressources dans lesquelles l'utilisateur ne peut
    */
    public static function ResourcesNotBookingForUser($user){
        $rooms_no_book = array();
        $sql = "SELECT id,who_can_see,who_can_book FROM ".TABLE_PREFIX."_room";
        $rooms = grr_sql_query($sql);
        if (!$rooms)
            fatal_error(0,grr_sql_error());
        while($room = mysqli_fetch_array($rooms)){
            $auth_level = SecuAccess::UserLevel($user,$room['id']);
            if ($auth_level < $room['who_can_see'])
                $rooms_no_book[] = $room['id'];
            elseif (!$room['who_can_book']){ // ressource restreinte
                $sql = "SELECT login FROM ".TABLE_PREFIX."_j_userbook_room j WHERE j.login = '".$user."' AND j.id_room = '".$room['id']."'";
                $login = grr_sql_query1($sql);
                if ((strtoupper($login) != strtoupper($user)) && ($auth_level < 3)){ // un gestionnaire de ressource peut toujours accéder !
                    $rooms_no_book[] = $room['id'];
                }
            }
        }
        return $rooms_no_book;
    }

    /* Determines if the user access area
     * @param string $user - The user login
     * @param int $id - Which area are we checking
     * @return int - 1 if access is granted, 0 otherwise
    */
    public static function UserArea($user,$id)
    {
        if ($id == '')
            return 0;
        $sql = "SELECT login FROM ".TABLE_PREFIX."_utilisateurs WHERE (login = '".SecuChaine::ProtectDataSql($user)."' and statut='administrateur')";
        $res = grr_sql_query($sql);
        if (grr_sql_count($res) != "0")
            return 1;
        if (Settings::get("module_multisite") == "Oui")
        {
            $id_site = mrbsGetAreaSite($id);
            $sql = "SELECT login FROM ".TABLE_PREFIX."_j_useradmin_site j WHERE j.id_site='".$id_site."' AND j.login='".SecuChaine::ProtectDataSql($user)."'";
            $res = grr_sql_query($sql);
            if (grr_sql_count($res) != "0")
                return 1;
        }
        $sql = "SELECT id FROM ".TABLE_PREFIX."_area WHERE (id = '".SecuChaine::ProtectDataSql($id)."' and access='r')";
        $res = grr_sql_query($sql);
        $test = grr_sql_count($res);
        if ($test == "0")
            return 1;
        else
        {
            $sql2 = "SELECT login FROM ".TABLE_PREFIX."_j_user_area WHERE (login = '".SecuChaine::ProtectDataSql($user)."' and id_area = '".SecuChaine::ProtectDataSql($id)."')";
            $res2 = grr_sql_query($sql2);
            $test2 = grr_sql_count($res2);
            if ($test2 != "0")
                return 1;
            else
                return 0;
        }
    }

    /* A utiliser avec une ressource restreinte : détermine si $user est autorisé à réserver dans $room
     * @param string $user - l'identifiant de l'utilisateur
     * @param int $room - l'identifiant de la ressource
     * @return int - 1 si l'utilisateur a le droit de réserver la ressource, 0 sinon
    */
    public static function UserBookingResourceRestrict($user,$room){
        $sql = "SELECT COUNT(*) FROM ".TABLE_PREFIX."_j_userbook_room WHERE (login = '".SecuChaine::ProtectDataSql($user)."' AND id_room = '".SecuChaine::ProtectDataSql($room)."')";
        $test = grr_sql_query1($sql);
        return ($test > 0);
    }


    /* Determines the user's access level
     * @param string $user - The user login
     * @param int $id - The resource or area ID to check
     * @param string $type - Optional argument: 'room' (default) if $id refers to a resource, 'area' if $id refers to an area
     * @return int - The user's access level (0: No access, 1: Visitor, 2: User, 3: Resource manager, 4: Area administrator, 5: Site administrator, 6: General admin)
    */
    public static function UserLevel($user, $id, $type = 'room')
    {
        //user level '0': User not logged in, or User value is NULL (getUserName()='')
        if (!isset($user) || ($user == ''))
            return 0;
        // On vient lire le statut de l'utilisateur courant dans la database
        $sql = "SELECT statut FROM ".TABLE_PREFIX."_utilisateurs WHERE login='".SecuChaine::ProtectDataSql($user)."' "." AND etat='actif'";
        $res = grr_sql_query($sql);
        $nbraw = grr_sql_count($res);
        //user level '0': User not defined in database
        if (!$res || $nbraw == 0)
            return 0;
        // On vient lire le résultat de la requète
        $status = grr_sql_row($res,$nbraw-1);
        //user level '0': Same User defined multiple time in database !!!
        if ($status === 0)
            return 0;
        // Teste si le type concerne la gestion des utilisateurs
        if ($type === 'user')
        {
            if (strtolower($status[0]) == 'gestionnaire_utilisateur')
                return 1;
            else
                return 0;
        }
        switch (strtolower($status[0]))
        {
            case 'visiteur':
            return 1;
            case 'administrateur':
            return 6;
            default:
            break;
        }
        if ((strtolower($status[0]) == 'utilisateur') || (strtolower($status[0]) == 'gestionnaire_utilisateur'))
        {
            if ($type == 'room')
            {
                // On regarde si l'utilisateur est administrateur du site auquel la ressource $id appartient
                // calcul de l'id du domaine
                $id_area = grr_sql_query1("SELECT area_id FROM ".TABLE_PREFIX."_room WHERE id='".SecuChaine::ProtectDataSql($id)."'");
                // calcul de l'id du site
                $id_site = grr_sql_query1("SELECT id_site FROM ".TABLE_PREFIX."_j_site_area  WHERE id_area='".SecuChaine::ProtectDataSql($id_area)."'");
                if (Settings::get("module_multisite") == "Oui")
                {
                    $res3 = grr_sql_query("SELECT login FROM ".TABLE_PREFIX."_j_useradmin_site j WHERE j.id_site='".SecuChaine::ProtectDataSql($id_site)."' AND j.login='".SecuChaine::ProtectDataSql($user)."'");
                    if (grr_sql_count($res3) > 0)
                    {
                        grr_sql_free($res3);
                        return 5;
                    }
                }
                // On regarde si l'utilisateur est administrateur du domaine auquel la ressource $id appartient
                $res3 = grr_sql_query("SELECT u.login
                    FROM ".TABLE_PREFIX."_utilisateurs u, ".TABLE_PREFIX."_j_useradmin_area j
                    WHERE (u.login=j.login AND j.id_area='".SecuChaine::ProtectDataSql($id_area)."' AND u.login='".SecuChaine::ProtectDataSql($user)."')");
                if (grr_sql_count($res3) > 0)
                    return 4;
                // On regarde si l'utilisateur est gestionnaire des réservations pour une ressource
                $str_res2 = "SELECT *
                FROM ".TABLE_PREFIX."_utilisateurs u, ".TABLE_PREFIX."_j_user_room j
                WHERE u.login=j.login and u.login='".SecuChaine::ProtectDataSql($user)."' ";
                if ($id!=-1)
                    $str_res2.="AND j.id_room='".SecuChaine::ProtectDataSql($id)."'";
                $res2 = grr_sql_query($str_res2);
                if (grr_sql_count($res2) > 0)
                    return 3;
                // Sinon il s'agit d'un simple utilisateur
                return 2;
            }
            // On regarde si l'utilisateur est administrateur d'un domaine
            if ($type == 'area')
            {
                if ($id == '-1')
                {
                    if (Settings::get("module_multisite") == "Oui")
                    {
                    //On regarde si l'utilisateur est administrateur d'un site quelconque
                        $res2 = grr_sql_query("SELECT u.login
                            FROM ".TABLE_PREFIX."_utilisateurs u JOIN ".TABLE_PREFIX."_j_useradmin_site j ON u.login=j.login
                            WHERE u.login='".SecuChaine::ProtectDataSql($user)."'");
                        if (grr_sql_count($res2) > 0)
                            return 5;
                    }
                    //On regarde si l'utilisateur est administrateur d'un domaine quelconque
                    $res2 = grr_sql_query("SELECT u.login
                        FROM ".TABLE_PREFIX."_utilisateurs u JOIN ".TABLE_PREFIX."_j_useradmin_area j ON u.login=j.login
                        WHERE u.login='".SecuChaine::ProtectDataSql($user)."'");
                    if (grr_sql_count($res2) > 0)
                        return 4;
                }
                else
                {
                    if (Settings::get("module_multisite") == "Oui")
                    {
                    // On regarde si l'utilisateur est administrateur du site auquel le domaine $id appartient
                        $id_site = grr_sql_query1("SELECT id_site FROM ".TABLE_PREFIX."_j_site_area  WHERE id_area='".SecuChaine::ProtectDataSql($id)."'");
                        $res3 = grr_sql_query("SELECT login FROM ".TABLE_PREFIX."_j_useradmin_site j WHERE j.id_site='".SecuChaine::ProtectDataSql($id_site)."' AND j.login='".SecuChaine::ProtectDataSql($user)."'");
                        if (grr_sql_count($res3) > 0)
                            return 5;
                    }
                    //On regarde si l'utilisateur est administrateur du domaine dont l'id est $id
                    $res3 = grr_sql_query("SELECT u.login
                        FROM ".TABLE_PREFIX."_utilisateurs u JOIN ".TABLE_PREFIX."_j_useradmin_area j ON u.login=j.login
                        WHERE (j.id_area='".SecuChaine::ProtectDataSql($id)."' and u.login='".SecuChaine::ProtectDataSql($user)."')");
                    if (grr_sql_count($res3) > 0)
                        return 4;
                }
                // Sinon il s'agit d'un simple utilisateur
                return 2;
            }
            // On regarde si l'utilisateur est administrateur d'un site
            if (($type == 'site') and (Settings::get("module_multisite") == "Oui"))
            {
                if ($id == '-1')
                {
                    //On regarde si l'utilisateur est administrateur d'un site quelconque
                    $res2 = grr_sql_query("SELECT u.login
                        FROM ".TABLE_PREFIX."_utilisateurs u JOIN ".TABLE_PREFIX."_j_useradmin_site j ON u.login=j.login
                        WHERE (u.login='".SecuChaine::ProtectDataSql($user)."')");
                    if (grr_sql_count($res2) > 0)
                        return 5;
                }
                else
                {
                    //On regarde si l'utilisateur est administrateur du site dont l'id est $id
                    $res3 = grr_sql_query("SELECT u.login
                        FROM ".TABLE_PREFIX."_utilisateurs u JOIN ".TABLE_PREFIX."_j_useradmin_site j ON u.login=j.login
                        WHERE (j.id_site='".SecuChaine::ProtectDataSql($id)."' and u.login='".SecuChaine::ProtectDataSql($user)."')");
                    if (grr_sql_count($res3) > 0)
                        return 5;
                }
                // Sinon il s'agit d'un simple utilisateur
                return 2;
            }
        }
    }

    /* Vérifier l'accès à la ressource
    * @param string $user : le login de l'utilisateur
    * @param int $id_room : l'id de la ressource
    * @return bool|array
    */
    public static function UserResource($user, $id_room)
    {
        if ($id_room != 'all')
        {
            $who_can_see = grr_sql_query1("SELECT who_can_see FROM ".TABLE_PREFIX."_room WHERE id='".$id_room."'");
            if (SecuAccess::UserLevel($user,$id_room) >= $who_can_see)
                return true;
            else
                return false;
        }
        else
        {
            $tab_rooms_noaccess = array();
            $sql = "SELECT id, who_can_see FROM ".TABLE_PREFIX."_room";
            $res = grr_sql_query($sql);
            if (!$res)
                fatal_error(0, grr_sql_error());
            foreach($res as $row)
            {
                if (SecuAccess::UserLevel($user,$row['id']) < $row['who_can_see'])
                    $tab_rooms_noaccess[] = $row['id'];
            }
            return $tab_rooms_noaccess;
        }
    }

    /* Vérifier l'accès à l'outil de recherche
     * @param string $user : le login de l'utilisateur
     * @param int $id_room : l'id de la ressource
     * @return bool
     */
    public static function UserSearch($user)
    {
        if (SecuAccess::UserLevel($user,-1) >= Settings::get("allow_search_level"))
            return true;
        return false;
    }

    /* Vérifier l'accès à la fiche de réservation d'une ressource
     * @param string $user : le login de l'utilisateur
     * @param int $id_room : l'id de la ressource
     * @return bool
     */
    public static function UserSheetReservation($user, $id_room)
    {
            if (SecuAccess::UserLevel($user, $id_room) >= Settings::get("acces_fiche_reservation"))
            return true;
        return false;
    }


    /* Vérifier l'accès à la visualisation de la fiche d'une ressource
     * @param string $user : le login de l'utilisateur
     * @param int $id_room : l'id de la ressource
     * @return bool
     */
    public static function UserSheetResource($user, $id_room)
    {
        $show_fic_room = grr_sql_query1("SELECT show_fic_room FROM ".TABLE_PREFIX."_room WHERE id='".$id_room."'");
        if ($show_fic_room == "y")
        {
            if (SecuAccess::UserLevel($user,$id_room) >= Settings::get("visu_fiche_description"))
                return true;
            return false;
        }
        return false;
    }

    /* Determines if the user access site
     * @param string $user - The user login
     * @param int $id - Which site are we checking
     * @return int - 1 if access is granted, 0 otherwise
    */
    public static function UserSite($user,$id)
    {
        if ($id == '')
            return 0;
        $sql = "SELECT login FROM ".TABLE_PREFIX."_utilisateurs WHERE (login = '".SecuChaine::ProtectDataSql($user)."' and statut='administrateur')";
        $res = grr_sql_query($sql);
        if (grr_sql_count($res) != "0")
            return 1;
        if (Settings::get("module_multisite") == "Oui")
        {
            $id_site = mrbsGetAreaSite($id);
            $sql = "SELECT login FROM ".TABLE_PREFIX."_j_useradmin_site j WHERE j.id_site='".$id_site."' AND j.login='".SecuChaine::ProtectDataSql($user)."'";
            $res = grr_sql_query($sql);
            if (grr_sql_count($res) != "0")
                return 1;
        }
        $sql = "SELECT id FROM ".TABLE_PREFIX."_site WHERE (id = '".SecuChaine::ProtectDataSql($id)."' and access='r')";
        $res = grr_sql_query($sql);
        $test = grr_sql_count($res);
        if ($test == "0")
            return 1;
        else
        {
            $sql2 = "SELECT login FROM ".TABLE_PREFIX."_j_user_site WHERE (login = '".SecuChaine::ProtectDataSql($user)."' and id_site = '".SecuChaine::ProtectDataSql($id)."')";
            $res2 = grr_sql_query($sql2);
            $test2 = grr_sql_count($res2);
            if ($test2 != "0")
                return 1;
            else
            {
                $sql3 = "SELECT login FROM ".TABLE_PREFIX."_j_group_site gs 
                JOIN ".TABLE_PREFIX."_utilisateurs_groupes ug on ug.idgroupes = gs.idgroupes 
                WHERE (ug.login = '".SecuChaine::ProtectDataSql($user)."' and gs.id_site = '".SecuChaine::ProtectDataSql($id)."')";
                $res3 = grr_sql_query($sql3);
                $test3 = grr_sql_count($res3);
                if ($test3 != "0")
                    return 1;
                else
                    return 0;
            }
        }
    }

    /* Determine si un utitilisateur avec le statut visiteur peut réserver une ressource
    * @param string $user - l'identifiant de l'utilisateur
    * @param int $id_room - l'identifiant de la ressource
    * @return int - 1 si l'utilisateur a le droit de réserver la ressource, 0 sinon
    */
    public static function VisitorBookingResource($user,$id_room)
    {
        global $id_room_autorise;

        if ((!isset($user)) || (!isset($id_room))) //
            return 0;
        $res = grr_sql_query("SELECT statut FROM ".TABLE_PREFIX."_utilisateurs WHERE login ='".SecuChaine::ProtectDataSql($user)."'");
        if (!$res || grr_sql_count($res) == 0) // L'utilisateur n'existe pas ou erreur de requete
            return 0;
        $status = mysqli_fetch_row($res);
        if (strtolower($status[0]) == 'visiteur') // L'utilisateur est un visiteur, on regarde s'il a accès à la ressource
        {
            if ((in_array($id_room,$id_room_autorise)) && ($id_room_autorise != ""))
                return 1;
            else
                return 0;
        }
        return 0;
    }

}

?>