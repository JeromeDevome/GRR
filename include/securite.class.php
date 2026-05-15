<?php
/**
 * include/securite.class.php
 * fichier Bibliothèque de fonctions de GRR
 * Dernière modification : $Date: 2026-05-15 17:50$
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

class SecuChaine {

    // fonction clean_input pour réduire le risque XSS
    public static function clean_input($data){
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }
    
    // fonction clean_login élimine d'une chaîne tous les caractères non autorisés dans un identifiant d'utilisateur [a-zA-Z0-9_@.-]
    public static function clean_login($_login){
        $clean = preg_replace("/[^a-zA-Z0-9_@.-]*/",'',$_login);
        return $clean;
    }

    // fonction alphanum pour réduire les vulnérabilités
    public static function alphanum($data){
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlentities($data);
        $data = preg_replace('/[^A-Za-z0-9\-_]/', '', $data);

        return $data;
    }

    # Remove backslash-escape quoting if PHP is configured to do it with
    # magic_quotes_gpc. Use this whenever you need the actual value of a GET/POST
    # form parameter (which might have special characters) regardless of PHP's
    # magic_quotes_gpc setting.
    public static function unslashes($s)
    {
        if (function_exists('get_magic_quotes_gpc') && @get_magic_quotes_gpc())
            return stripslashes($s);
        else
            return $s;
    }

    // Traite les données avant insertion dans une requête SQL
    public static function protect_data_sql($_value)
    {
        global $use_function_mysql_real_escape_string;
        //if (get_magic_quotes_gpc())
        if (function_exists('get_magic_quotes_gpc') && @get_magic_quotes_gpc())
            $_value = stripslashes($_value);
        if (!is_numeric($_value) && $_value != null)
            $_value = mysqli_real_escape_string($GLOBALS['db_c'], $_value);

        return $_value;
    }

    // Transforme une chaine de caractères en couleur hexadécimale valide
    public static function Valide_couleur($entry)
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

    // Détermine si l'adresse mail en paramètre est syntaxiquement valable
    public static function Valide_email($email)
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

    // Contrôle validité ip
    public static function Valide_ip_adr($input) {
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

}



?>