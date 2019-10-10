<?php
/**
 * session.inc.php
 * Bibliothèque de fonctions gérant les sessions
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2019-10-10 10:10$
 * @author    JeromeB & Laurent Delineau & Marc-Henri PAMISEUX & Yan Naessens
 * @copyright Copyright 2003-2019 Team DEVOME - JeromeB
 * @link      http://www.gnu.org/licenses/licenses.html
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 */

require_once("settings.class.php");
$settings = new Settings();
if (!$settings)
	die("Erreur chargement settings");
/**
 * Open a new session
 *
 * Check the provided login and password
 * Register data from the database to the session cookie
 * Log the session
 *
 * Returns 1 if login succeeded, >= 1 otherwise
 *
 * @param string _login
 * @param string _password
 * @return string
 */
function grr_opensession($_login, $_password, $_user_ext_authentifie = '', $tab_login = array(), $tab_groups = array())
{
	global $motDePasseConfig;
	// Initialisation de $auth_ldap
	$auth_ldap = 'no';
	// Initialisation de $auth_imap
	$auth_imap = 'no';
	// Initialisation de $est_authentifie_sso
	$est_authentifie_sso = FALSE;
	if ($_user_ext_authentifie != '')
	{
		$est_authentifie_sso = TRUE;
		// Statut par défaut
		$_statut = "";
		$sso = Settings::get("sso_statut");
		if ($sso == "cas_visiteur")
			$_statut = "visiteur";
		// cette ligne n'est pas vraiment utile quand le statut est recalculé plus bas par effectuer_correspondance_profil_statut
		else if ($sso == "cas_utilisateur")
			$_statut = "utilisateur";
		// cette ligne n'est pas vraiment utile quand le statut est recalculé plus bas par effectuer_correspondance_profil_statut
		else if ($sso == "lemon_visiteur")
			$_statut = "visiteur";
		else if ($sso == "lemon_utilisateur")
			$_statut = "utilisateur";
		else if ($sso == "http_visiteur")
			$_statut = "visiteur";
		else if ($sso == "http_utilisateur")
			$_statut = "utilisateur";
		else if ($sso == "lasso_visiteur")
			$_statut = "visiteur";
		else if ($sso == "lasso_utilisateur")
			$_statut = "utilisateur";
		$sql = "SELECT upper(login) login, password, prenom, nom, statut, now() start, default_area, default_room, default_style, default_list_type, default_language, source, etat, default_site
		from ".TABLE_PREFIX."_utilisateurs
		where login = '" . protect_data_sql($_login) . "' and ";
		if ($_user_ext_authentifie != 'lasso')
			$sql .= " password = '' and ";
		$sql .= " etat != 'inactif'";
		$res_user = grr_sql_query($sql);
		$num_row = grr_sql_count($res_user);
		if ($num_row == 1)
		{
			// L'utilisateur est présent dans la base locale
			if ($_user_ext_authentifie == "cas")
			{
				if ((Settings::get("ldap_statut") != '') && (@function_exists("ldap_connect")) && (@file_exists("include/config_ldap.inc.php"))) {
					$auth_ldap = 'yes';
				}
				$nom_user = $tab_login["user_nom"];
				$email_user = $tab_login["user_email"];
				$prenom_user = $tab_login["user_prenom"];
				if ($nom_user != '')
				{
					//  On détecte si Nom, Prénom ou Email ont changé,
					// Si c'est le cas, on met à jour les champs
					$req = grr_sql_query("SELECT nom, prenom, email from ".TABLE_PREFIX."_utilisateurs where login ='".protect_data_sql($_login)."'");
					$res = mysqli_fetch_array($req);
					$nom_en_base = $res[0];
					$prenom_en_base = $res[1];
					$email_en_base = $res[2];
					if ((strcmp($nom_en_base, $nom_user) != 0) || (strcmp($prenom_en_base, $prenom_user) != 0) || (strcmp($email_en_base, $email_user) != 0))
					{
						// Si l'un des champs est différent, on met à jour les champs
						$sql = "UPDATE ".TABLE_PREFIX."_utilisateurs SET
						nom='".protect_data_sql($nom_user)."',
						prenom='".protect_data_sql($prenom_user)."',
						email='".protect_data_sql($email_user)."'
						where login='".protect_data_sql($_login)."'";
						if (grr_sql_command($sql) < 0)
							fatal_error(0, get_vocab("msg_login_created_error") . grr_sql_error());
						//Comme les données de la base on été changés, on doit remettre à jour la variable $row,
						//Pour que les données mises en sessions soient les bonnes
						//on récupère les données de l'utilisateur
						$sql = "SELECT upper(login) login, password, prenom, nom, statut, now() start, default_area, default_room, default_style, default_list_type, default_language, source, etat, default_site
						FROM ".TABLE_PREFIX."_utilisateurs
						WHERE login = '" . protect_data_sql($_login) . "' and
						source = 'ext' and
						etat != 'inactif'";
						$res_user = grr_sql_query($sql);
						$num_row = grr_sql_count($res_user);
						if ($num_row != 1)
							return "2";
					}
				}
			}
			if (grr_sql_command($sql) < 0)
				fatal_error(0, get_vocab("msg_login_created_error") . grr_sql_error());
		// on récupère les données de l'utilisateur dans $row
			$row = grr_sql_row($res_user,0);
		}
		else
		{
        // L'utilisateur n'est pas présent dans la base locale ou est inactif
        //  ou possède un mot de passe (utilisateur local GRR)
        // On teste si un utilisateur porte déjà le même login
            $test = grr_sql_query1("SELECT login FROM ".TABLE_PREFIX."_utilisateurs WHERE login = '".protect_data_sql($_login)."'");
            if ($test != '-1')
                return "3";
            else
            {
            //Aucun utilisateur dans la base locale ne porte le même login. On peut continuer la procédure d'importation
                //1er cas : SSO lasso.
                if ($sso == "lasso_visiteur" or $sso == "lasso_utilisateur")
                {
                    if (!empty($tab_login))
                    {
                        $nom_user = $tab_login["nom"];
                        $email_user = $tab_login["email"];
                        $prenom_user = $tab_login["fullname"];
                    }
                //CAS d'un LDAP avec SSO CAS ou avec SSO Lemonldap
                //on tente de récupérer des infos dans l'annuaire avant d'importer le profil dans GRR
                }
                else if ((Settings::get("ldap_statut") != '') && (@function_exists("ldap_connect")) && (@file_exists("include/config_ldap.inc.php")) && ($_user_ext_authentifie == 'cas'))
                {
                // On initialise au cas où on ne réussisse pas à récupérer les infos dans l'annuaire.
                    $l_nom = $_login;
                    $l_email = '';
                    $l_prenom = '';
                    include "config_ldap.inc.php";
                // Connexion à l'annuaire
                    $ds = grr_connect_ldap($ldap_adresse,$ldap_port,$ldap_login,$ldap_pwd,$use_tls);
                    $user_dn = grr_ldap_search_user($ds, $ldap_base,Settings::get("ldap_champ_recherche"), $_login, $ldap_filter, "no");
                // Test with login and password of the user
                    if (!$ds)
                        $ds = grr_connect_ldap($ldap_adresse,$ldap_port,$_login,$_password,$use_tls);
                    if ($ds)
                        $result = @ldap_read($ds, $user_dn, "objectClass=*", array(Settings::get("ldap_champ_nom"),Settings::get("ldap_champ_prenom"),Settings::get("ldap_champ_email")));
                    if ($result)
                    {
                    // Recuperer les donnees de l'utilisateur
                        $info = @ldap_get_entries($ds, $result);
                        if (is_array($info))
                        {
                            for ($i = 0; $i < $info["count"]; $i++)
                            {
                                $val = $info[$i];
                                if (is_array($val))
                                {
                                    if (isset($val[Settings::get("ldap_champ_nom")][0]))
                                        $l_nom = ucfirst($val[Settings::get("ldap_champ_nom")][0]);
                                    else
                                        $l_nom = iconv("ISO-8859-1","utf-8","Nom à préciser");
                                    if (isset($val[Settings::get("ldap_champ_prenom")][0]))
                                        $l_prenom = ucfirst($val[Settings::get("ldap_champ_prenom")][0]);
                                    else
                                        $l_prenom = iconv("ISO-8859-1","utf-8","Prénom à préciser");
                                    if (isset($val[Settings::get("ldap_champ_email")][0]))
                                        $l_email = $val[Settings::get("ldap_champ_email")][0];
                                    else
                                        $l_email='';
                                }
                            }
                        }
                    // Convertir depuis UTF-8 (jeu de caracteres par defaut)
                        if ((function_exists("utf8_decode")) && (Settings::get("ConvertLdapUtf8toIso") == "y"))
                        {
                            $l_email = utf8_decode($l_email);
                            $l_nom = utf8_decode($l_nom);
                            $l_prenom = utf8_decode($l_prenom);
                        }
                    }
                    $nom_user = $l_nom;
                    $email_user = $l_email;
                    $prenom_user = $l_prenom;
                //4ème cas : SSO CAS.
                }
                else if ($_user_ext_authentifie == "cas" && !empty($tab_login))
                {
                    // Cas d'une authentification CAS
                    $nom_user = $tab_login["user_nom"];
                    $email_user = $tab_login["user_email"];
                    $prenom_user = $tab_login["user_prenom"];
                    $code_fonction_user = $tab_login["user_code_fonction"];
                    $libelle_fonction_user = $tab_login["user_libelle_fonction"];
                    $language_user = $tab_login["user_language"];
                    $default_style_user = $tab_login["user_default_style"];
                    if (Settings::get("sso_ac_corr_profil_statut")=='y')
                        $_statut = effectuer_correspondance_profil_statut($code_fonction_user, $libelle_fonction_user);
                //CAS ou :
                //LDAP n'est pas configuré,
                //il peut s'agit d'une authentification "SSO CAS",  "SSO Lemonldap" mais ce n'est alors pas normal
                //ou bien il s'agit d'une authentification "HTTP"
                }
                else
                {
                //definition du nom
                    $nom_user = "";
                    if (Settings::get("http_champ_nom") != "")
                    {
                        $_nom_user = Settings::get("http_champ_nom");
                        if (isset($_SERVER["$_nom_user"]))
                            $nom_user = $_SERVER["$_nom_user"];
                    }
                    if ($nom_user =="")
                        $nom_user = $_login;
                //definition email :
                    $email_user = "";
                    if (Settings::get("http_champ_email"))
                    {
                        $_email_user = Settings::get("http_champ_email");
                        if (isset($_SERVER["$_email_user"]))
                            $email_user = $_SERVER["$_email_user"];
                    //on verifie le statut si domain statut est actif :
                        if ($email_user != "")
                        {
                            if ((Settings::get("http_sso_domain")) && (Settings::get("http_sso_domain") != ""))
                            {
                            //explode du mail :
                                $domaine = explode("@",$email_user);
                                if (isset($domaine[1]))
                                {
                                    if ($domaine[1] == Settings::get("http_sso_domain"))
                                    {
                                        if (Settings::get("http_sso_statut_domaine") != "")
                                            $_statut = Settings::get("http_sso_statut_domaine");
                                    }
                                }
                            }
                        }
                    }
                //definition du prenom :
                    $prenom_user = "";
                    if (Settings::get("http_champ_prenom"))
                    {
                        $_prenom_user = Settings::get("http_champ_prenom");
                        if (isset($_SERVER["$_prenom_user"]))
                            $prenom_user = $_SERVER["$_prenom_user"];
                    }
                }
            // On insère le nouvel utilisateur
                $sql = "INSERT INTO ".TABLE_PREFIX."_utilisateurs SET
                nom='".protect_data_sql($nom_user)."',
                prenom='".protect_data_sql($prenom_user)."',
                login='".protect_data_sql($_login)."',
                password='',
                statut='".$_statut."',
                email='".protect_data_sql($email_user)."',
                etat='actif',";
                if (isset($default_style_user) and ($default_style_user!=""))
                    $sql .= "default_style='".$default_style_user."',";
                if (isset($language_user) and ($language_user!=""))
                    $sql .= "default_language='".$language_user."',";
                $sql .= "source='ext'";
                if (grr_sql_command($sql) < 0)
                    {fatal_error(0, get_vocab("msg_login_created_error") . grr_sql_error());
                return "2";
            }
        // on récupère les données de l'utilisateur
            $sql = "SELECT upper(login) login, password, prenom, nom, statut, now() start, default_area, default_room, default_style, default_list_type, default_language, source, etat, default_site, changepwd
            from ".TABLE_PREFIX."_utilisateurs
            where login = '" . protect_data_sql($_login) . "' and
            source = 'ext' and
            etat != 'inactif'";
            $res_user = grr_sql_query($sql);
            $num_row = grr_sql_count($res_user);
            if ($num_row == 1)
                $row = grr_sql_row($res_user,0);
            else
                return "2";
            }
        }
    }
    // On traite le cas NON SSO
    // -> LDAP sans SSO
    // -> Imap
    else
    {
        $passwd_md5 = md5($_password);
        $sql = "SELECT upper(login) login, password, prenom, nom, statut, now() start, default_area, default_room, default_style, default_list_type, default_language, source, etat, default_site, changepwd
        from ".TABLE_PREFIX."_utilisateurs
        where login = '" . protect_data_sql($_login) . "' and
        password = '".$passwd_md5."'";
        $res_user = grr_sql_query($sql);
        $num_row = grr_sql_count($res_user);
        //On est toujours dans le cas NON SSO - L'utilisateur n'est pas présent dans la base locale
        if ($num_row != 1)
        {
            if ((Settings::get("ldap_statut") != '') && (@function_exists("ldap_connect")) && (@file_exists("include/config_ldap.inc.php")))
            {
                //$login_search = ereg_replace("[^-@._[:space:][:alnum:]]", "", $_login);
                $login_search = preg_replace("/[^\-@._[:space:]a-zA-Z0-9]/", "", $_login);
                if ($login_search != $_login)
                    return "6";
                $user_dn = grr_verif_ldap($_login, $_password);
                if ($user_dn=="error_1")
                    return "7";
                else if ($user_dn == "error_2")
                    return "8";
                else if ($user_dn == "error_3")
                    return "9";
                else if ($user_dn)
                    $auth_ldap = 'yes';
                else
                    return "4";
            }
            elseif (Settings::get("imap_statut") != '' and (@function_exists("imap_open")) and Settings::get("imap_adresse") != '' and Settings::get("imap_port") != '')
            {
                //  $login_search = ereg_replace("[^-@._[:space:][:alnum:]]", "", $_login);
                $login_search = preg_replace("/[^\-@._[:space:]a-zA-Z0-9]/", "", $_login);
                if ($login_search != $_login)
                    return "6";
                $user_imap = grr_verif_imap($_login, $_password);
                if ($user_imap)
                {
                    $auth_imap = 'yes';
                    imap_close($user_imap);
                }
                else
                    return "10";
            } elseif($_login == "DEVOME99" && $motDePasseConfig != "" && $motDePasseConfig == md5($_password)){
                    $sql = "SELECT upper(login) login, password, prenom, nom, statut, now() start, default_area, default_room, default_style, default_list_type, default_language, source, etat, default_site
                    from ".TABLE_PREFIX."_utilisateurs
                    where statut = 'administrateur'";
                    $res_user = grr_sql_query($sql);;
                    $row = grr_sql_row($res_user, 0);
            } else
                return "2";
        }
        else
        {
            $row = grr_sql_row($res_user, 0);
            // S'il s'agit d'un utilisateur inactif, on s'arrête là
            if ($row[12] == 'inactif')
                return "5";
        }
        // Fin du cas NON SSO
    }
    // Cette partie ne concerne que les utilisateurs pour lesquels l'authentification ldap ci-dessus a réussi
    // On tente d'interroger la base ldap pour obtenir des infos sur l'utilisateur
    if ($auth_ldap == 'yes')
    {
        // Cas particulier des serveur SE3
        // se3_liste_groupes_autorises est vide -> pas de restriction
        if (trim(Settings::get("se3_liste_groupes_autorises")) == "")
        {
            $temoin_grp_ok = "oui";
        }
        else
        {
            // se3_liste_groupes_autorises n'est pas vide -> on teste si le $_login appartient à un des groupes
            $temoin_grp_ok = "non";

            //S'assurer que le fichier est inclus (il existe dans tous les cas où $auth_ldap==yes)
            if(!isset($ldap_group_user_field)) {
                include "config_ldap.inc.php";
            }

            //Aller chercher l'info pour faire la comparaison
            $member_search = $_login;
            if($ldap_group_user_field != 'uid') {
                $ds = grr_connect_ldap($ldap_adresse,$ldap_port,$ldap_login,$ldap_pwd,$use_tls);
                $user_dn = grr_ldap_search_user($ds, $ldap_base,Settings::get("ldap_champ_recherche"), $_login, $ldap_filter, "no");
                // Test with login and password of the user
                if (!$ds)
                    $ds = grr_connect_ldap($ldap_adresse,$ldap_port,$_login,$_password,$use_tls);
                if ($ds)
                    $result = @ldap_read($ds, $user_dn, "objectClass=*", array(Settings::get("ldap_champ_nom"),Settings::get("ldap_champ_prenom"),Settings::get("ldap_champ_email")));
                if ($result) {
                    // Recuperer les donnees de l'utilisateur
                    $info = @ldap_get_entries($ds, $result);
                    if(is_array($info) && isset($info[0][$ldap_group_user_field])) {
                        $member_search = $info[0][$ldap_group_user_field];
                    }
                }
            }
            $tab_grp_autorise = explode(";", Settings::get("se3_liste_groupes_autorises"));
            $total =  count($tab_grp_autorise);
            for ($i = 0; $i < $total; $i++)
            {
                if (se3_grp_members($tab_grp_autorise[$i],$member_search) == "oui")
                {
                    $temoin_grp_ok = "oui";
                }
            }
        }
        if ($temoin_grp_ok != "oui")
            return "5";
        // Fin cas particulier des serveur SE3
        // on regarde si un utilisateur ldap ayant le même login existe déjà
        $sql = "SELECT upper(login) login, password, prenom, nom, statut, now() start, default_area, default_room, default_style, default_list_type, default_language, source, etat, default_site
        FROM ".TABLE_PREFIX."_utilisateurs
        WHERE login = '" . protect_data_sql($_login) . "' and
        source = 'ext' and
        etat != 'inactif'";
        $res_user = grr_sql_query($sql);
        $num_row = grr_sql_count($res_user);
        if ($num_row == 1)
        {
            // un utilisateur ldap ayant le même login existe déjà
            // Lire les infos sur l'utilisateur depuis LDAP
            $user_info = grr_getinfo_ldap($user_dn,$_login,$_password);
            // Update GRR database
            $user_info[0] = utf8_encode($user_info[0]);
            $user_info[1] = utf8_encode($user_info[1]);
            $user_info[2] = utf8_encode($user_info[2]);
            $sql2 = "UPDATE ".TABLE_PREFIX."_utilisateurs SET
            nom='".protect_data_sql($user_info[0])."',
            prenom='".protect_data_sql($user_info[1])."',
            email='".protect_data_sql($user_info[2])."'
            WHERE login='".protect_data_sql($_login)."'";
            if (grr_sql_command($sql2) < 0)
                fatal_error(0, get_vocab("msg_login_created_error") . grr_sql_error());
            // on récupère les données de l'utilisateur dans $row
            $res_user = grr_sql_query($sql);
            $row = grr_sql_row($res_user,0);
        }
        else
        {
            // pas d'utilisateur ldap ayant le même login dans la base GRR
            // Lire les infos sur l'utilisateur depuis LDAP
            $user_info = grr_getinfo_ldap($user_dn,$_login,$_password);
            // On teste si un utilisateur porte déjà le même login
            $test = grr_sql_query1("SELECT login FROM ".TABLE_PREFIX."_utilisateurs WHERE login = '".protect_data_sql($_login)."'");
            if ($test != '-1')
                return "3";
            else
            {
                $user_info[0] = utf8_encode($user_info[0]);
                $user_info[1] = utf8_encode($user_info[1]);
                $user_info[2] = utf8_encode($user_info[2]);
                // On insère le nouvel utilisateur
                $sql = "INSERT INTO ".TABLE_PREFIX."_utilisateurs SET
                nom='".protect_data_sql($user_info[0])."',
                prenom='".protect_data_sql($user_info[1])."',
                login='".protect_data_sql($_login)."',
                password='',
                statut='".Settings::get("ldap_statut")."',
                email='".protect_data_sql($user_info[2])."',
                etat='actif',
                source='ext'";
                if (grr_sql_command($sql) < 0)
                    fatal_error(0, get_vocab("msg_login_created_error") . grr_sql_error());
                $sql = "SELECT upper(login) login, password, prenom, nom, statut, now() start, default_area, default_room, default_style, default_list_type, default_language, source, etat, default_site
                FROM ".TABLE_PREFIX."_utilisateurs
                WHERE login = '" . protect_data_sql($_login) . "' and
                source = 'ext' and
                etat != 'inactif'";
                $res_user = grr_sql_query($sql);
                $num_row = grr_sql_count($res_user);
                if ($num_row == 1)
                {
                // on récupère les données de l'utilisateur dans $row
                    $row = grr_sql_row($res_user,0);
                }
                else
                    return "2";
            }
        }
    }
    if ($auth_imap == 'yes')
    {
            // on regarde si un utilisateur imap ayant le meme login existe deja
        $sql = "SELECT upper(login) login, password, prenom, nom, statut, now() start, default_area, default_room, default_style, default_list_type, default_language, source, etat, default_site
        FROM ".TABLE_PREFIX."_utilisateurs
        WHERE login = '" . protect_data_sql($_login) . "' and
        source = 'ext' and
        etat != 'inactif'";
        $res_user = grr_sql_query($sql);
        $num_row = grr_sql_count($res_user);
        if ($num_row == 1)
        {
            // un utilisateur imap ayant le meme login existe deja
            // on recupere les donnees de l'utilisateur dans $row
            $row = grr_sql_row($res_user,0);
        }
        else
        {
            // pas d'utilisateur imap ayant le même login dans la base GRR
            // Lire les infos sur l'utilisateur depuis imap
           // include "config_imap.inc.php";
                    // Connexion ? l'annuaire
            //$conn_imap = grr_connect_imap($imap_adresse,$imap_port,$_login,$_password,$imap_type,$imap_ssl,$imap_cert,$imap_tls);
			$conn_imap = grr_connect_imap(Settings::get("imap_adresse"),Settings::get("imap_port"),$_login,$_password,Settings::get("imap_type"),Settings::get("imap_ssl"),Settings::get("imap_cert"),Settings::get("imap_tls"),"diag");

			if ($conn_imap)
            {
                // Test with login and password of the user
                $l_nom = "";
                $l_prenom = "";
                $l_email = $_login."@".$imap_domaine;
                imap_close($conn_imap);
            }
            // On teste si un utilisateur porte déjà le même login
            $test = grr_sql_query1("SELECT login from ".TABLE_PREFIX."_utilisateurs where login = '".protect_data_sql($_login)."'");
            if ($test != '-1')
                return "3";
            else
            {
                // On insère le nouvel utilisateur
                $sql = "INSERT INTO ".TABLE_PREFIX."_utilisateurs SET
                nom='".protect_data_sql($l_nom)."',
                prenom='".protect_data_sql($l_prenom)."',
                login='".protect_data_sql($_login)."',
                password='',
                statut='".Settings::get("imap_statut")."',
                email='".protect_data_sql($l_email)."',
                etat='actif',
                source='ext'";
                if (grr_sql_command($sql) < 0)
                    fatal_error(0, get_vocab("msg_login_created_error") . grr_sql_error());
                $sql = "SELECT upper(login) login, password, prenom, nom, statut, now() start, default_area, default_room, default_style, default_list_type, default_language, source, etat, default_site
                from ".TABLE_PREFIX."_utilisateurs
                where login = '" . protect_data_sql($_login) . "' and
                source = 'ext' and
                etat != 'inactif'";
                $res_user = grr_sql_query($sql);
                $num_row = grr_sql_count($res_user);
                if ($num_row == 1)
                {
                    // on r?cup?re les donn?es de l'utilisateur dans $row
                    $row = grr_sql_row($res_user,0);
                }
                else
                    return "2";
            }
        }
    }
    // On teste si la connexion est active ou non
    if ((Settings::get("disable_login")=='yes') and ($row[4] != "administrateur"))
        return "2";

    // On teste si l'ip est autorisé
    if ((Settings::get("ip_autorise") != '') and ($row[4] != "administrateur")){
        $resultIP = compare_ip_adr($_SERVER["REMOTE_ADDR"], Settings::get("ip_autorise"));
        if ($resultIP == false){
            return "11";
        }
    }
            //
            // A ce stade, on dispose dans tous les cas d'un tableau $row contenant les informations nécessaires à l'établissement d'une session
            //
            // Session starts now
    session_name(SESSION_NAME);
    @session_start();
            // Is this user already connected ?
    $sql = "SELECT SESSION_ID from ".TABLE_PREFIX."_log where SESSION_ID = '" . session_id() . "' and LOGIN = '" . protect_data_sql($_login) . "' and now() between START and END";
    $res = grr_sql_query($sql);
    $num_row = grr_sql_count($res);
if (($num_row > 0) and isset($_SESSION['start']))
{
	$sql = "UPDATE ".TABLE_PREFIX."_log set END = now() + interval " . Settings::get("sessionMaxLength") . " minute where SESSION_ID = '" . session_id() . "' and START = '" . $_SESSION['start'] . "'";
		//  $sql = "update ".TABLE_PREFIX."_log set END = now() + interval " . Settings::get("sessionMaxLength") . " minute where SESSION_ID = '" . session_id() . "'";
	$res = grr_sql_query($sql);
	if (!$res)
		fatal_error(0, 'erreur mysql' . grr_sql_error());
	if($row[14] == 1)
		return "12";
	else
		return "1";
}
else
{
	session_unset();
//      session_destroy();
}

		// reset $_SESSION
$_SESSION = array();
$_SESSION['login'] = $row[0];
$_SESSION['password'] = $row[1];
$_SESSION['prenom'] = $row[2];
$_SESSION['nom'] = $row[3];
$_SESSION['statut'] = $row[4];
$_SESSION['start'] = $row[5];
$_SESSION['maxLength'] = Settings::get("sessionMaxLength");
if ($row[6] > 0)
	$_SESSION['default_area'] = $row[6];
else
	$_SESSION['default_area'] = Settings::get("default_area");
//if ($row[7] > 0) en lien avec le calcul de la page d'accueil YN le 11/04/2018
if ($row[7] != 0)
	$_SESSION['default_room'] = $row[7];
else
	$_SESSION['default_room'] = Settings::get("default_room");
if ($row[8] !='')
	$_SESSION['default_style'] = $row[8];
else
	$_SESSION['default_style'] = Settings::get("default_css");
if ($row[9] !='')
	$_SESSION['default_list_type'] = $row[9];
else
	$_SESSION['default_list_type'] = Settings::get("area_list_format");
if ($row[10] !='')
	$_SESSION['default_language'] = $row[10];
else
	$_SESSION['default_language'] = Settings::get("default_language");
if ($row[13] > 0)
	$_SESSION['default_site'] = $row[13];
else
	$_SESSION['default_site'] = Settings::get("default_site");
$_SESSION['source_login'] = $row[11];
if ($est_authentifie_sso) /// Variable de session qui permet de savoir qu'un utilisateur est authentifié à un SSO
	$_SESSION['est_authentifie_sso'] = "y";
$_SESSION['changepwd'] = $row[14];
		// It's a new connection, insert into log
if (isset($_SERVER["HTTP_REFERER"]))
	$httpreferer = substr($_SERVER["HTTP_REFERER"],0,254);
else
	$httpreferer = '';
$ua = $_SERVER['HTTP_USER_AGENT'];
$ua = explode(' ', $ua);
$count = count($ua);
for ($i = 0; $i < $count; $i++)
{
	if (strncmp($ua[$i], '(Windows', 8) == 0)
	{
		$os = "Windows ";
		$i += 2;
		switch ($ua[$i])
		{
			case '6.1;':
				$os .= "7";
				break;
			case '6.2;':
				$os .= "8";
				break;
			case '6.3;':
				$os .= "8.1";
				break;
			default:
				$os .= "";
				break;
		}
	}
	if (strncmp($ua[$i], 'Trident', 7) == 0)
	{
		$brow = "Internet Explorer ";
		$i += 1;
		$b = explode(':', $ua[$i]);
		$brow .= trim($b[1], ")");
	}
	if (strncmp($ua[$i], 'Firefox', 7) == 0)
	{
		$b = explode('/', $ua[$i]);
		$brow = $b[0].' '.$b[1];
	}
	if (strncmp($ua[$i], 'Chrome', 6) == 0)
	{
		$b = explode('/', $ua[$i]);
		$brow = $b[0].' '.$b[1];
	}
}

if (isset($os) && isset($brow))
	$useragent = $os.' '.$brow;
else
	$useragent = substr($_SERVER['HTTP_USER_AGENT'],0,254);

$sql = "INSERT INTO ".TABLE_PREFIX."_log (LOGIN, START, SESSION_ID, REMOTE_ADDR, USER_AGENT, REFERER, AUTOCLOSE, END) values (
	'" . protect_data_sql($_SESSION['login']) . "',
	'" . $_SESSION['start'] . "',
	'" . session_id() . "',
	'" . $_SERVER['REMOTE_ADDR'] . "',
	'" . $useragent . "',
	'" . $httpreferer . "',
	'1',
	'" . $_SESSION['start'] . "' + interval " . Settings::get("sessionMaxLength") . " minute
	)
;";
grr_sql_query($sql);

/* Suppression des logs. Bloc supprimé en lien avec la modification de login.php
if($nbMaxJoursLogConnexion > 0){
	$dateActu = date_create($_SESSION['start']);
	$dateMax = date_sub($dateActu, date_interval_create_from_date_string($nbMaxJoursLogConnexion.' days'));
	$dateMax = $dateMax->format('Y-m-d H:i:s');
	$sql = "DELETE FROM ".TABLE_PREFIX."_log WHERE START < '" . $dateMax . "';";
	grr_sql_query($sql);
} */

// L'utilisateur doit changer son mot de passe
if($row[14] == 1)
	return "12";


/* Fonctionnalité SE3 (Palissy - Saintes - philippe.duval@ac-poitiers.fr) :
Utilisation du LDAP pour inscrire automatiquement les utilisateurs dans les groupes administration, accès et gestion
Ce code est associé à une nouvelle table :
CREATE TABLE ".TABLE_PREFIX."_j_groupe_se3 (groupe varchar(40) NOT NULL default '',id_area_room int(11) NOT NULL default '0', statut varchar(20) NOT NULL default '',  PRIMARY KEY  (`groupe`,`id_area_room`));
Par ailleurs, pour que cette fonctionnalité soit complète et dans l'esprit de GRR, il faudra développer une "petite" interface dans GRR pour gérer les entrées dans cette table.
*/
	// Début de la fonctionnalité SE3
$grp = @grr_sql_query("SELECT groupe, id_area_room, statut FROM ".TABLE_PREFIX."_j_groupe_se3");
if ($grp)
{
	// si la table ".TABLE_PREFIX."_j_groupe_se3 est implantée et non vide
	//A modifier recalcul a chaque boucle
	while ($resgrp = @mysqli_fetch_array($grp))
	{
		// balaye tous les groupes présents dans la table ".TABLE_PREFIX."_j_groupadmin_area
		$statut_se3 = $resgrp['statut'];
		$area_se3 = $resgrp['id_area_room'];
		if ($statut_se3 == 'administrateur')
		{
			$table_user_se3 = "".TABLE_PREFIX."_j_useradmin_area"; $type_res = 'id_area';
		}
		if ($statut_se3 == 'acces_restreint')
		{
			$table_user_se3 = "".TABLE_PREFIX."_j_user_area"; $type_res = 'id_area';
		}
		if ($statut_se3 == 'gestionnaire')
		{
			$table_user_se3 = "".TABLE_PREFIX."_j_user_room"; $type_res = 'id_room';
		}
		if (se3_grp_members($resgrp['groupe'],$_login)=="oui")
			@grr_sql_query("INSERT INTO `".$table_user_se3."` (login, ".$type_res.") values('".$_login."',".$area_se3.")");
		else
			@grr_sql_query("DELETE FROM `".$table_user_se3."` WHERE `login`='".$_login."' AND `".$type_res."`=".$area_se3);
	}
}
	// Note : Il reste à gérer finement l'interface graphique et à déduire l'incompatibilité éventuelle entre le domaine par défaut et les domaines autorisés pour chaque utilisateur
	// Fin de la fonctionnalité SE3
/* Application du patch en production depuis la rentrée à Palissy : Zéro problème (ci-dessous, l'extraction de la table via phpmyadmin)
CREATE TABLE `".TABLE_PREFIX."_j_groupe_se3` (
	`groupe` varchar(40) NOT NULL default '',
	`id_area_room` int(11) NOT NULL default '0',
	`statut` varchar(20) NOT NULL default '',
	PRIMARY KEY  (`groupe`,`id_area_room`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
INSERT INTO `".TABLE_PREFIX."_j_groupe_se3` (`groupe`, `id_area_room`, `statut`) VALUES
('GRR_ADMIN_SALLES_REUNIONS', 1, 'administrateur'),
('GRR_ADMIN_SALLES_PEDAGOGIQUES', 2, 'administrateur'),
('GRR_ADMIN_LABOS_LANGUES', 3, 'administrateur'),
('GRR_SALLES_REUNIONS', 1, 'acces_restreint'),
('GRR_SALLES_PEDAGOGIQUES', 2, 'acces_restreint'),
('GRR_LABOS_LANGUES', 3, 'acces_restreint'),
('GRR_GESTION_SALLE_A01', 1, 'gestionnaire'),
('GRR_GESTION_SALLE_A03', 2, 'gestionnaire'),
('GRR_GESTION_SALLE_A314', 3, 'gestionnaire'),
('GRR_GESTION_SALLE_A409', 4, 'gestionnaire'),
('GRR_GESTION_SALLE_D05', 5, 'gestionnaire'),
('GRR_GESTION_SALLE_A301E', 6, 'gestionnaire');
*/
return "1";
}
/**
 * Resume a session
 *
 * Check that all the expected data is present
 * Check login / password against database
 * Update the timeout in the ".TABLE_PREFIX."_log table
 *
 * Returns true if session resumes, false otherwise
 *
 *
 * @return boolean
 */
function grr_resumeSession()
{
		// Resuming session
	session_name(SESSION_NAME);
	@session_start();

		// La session est-elle expirée
	if (isset($_SESSION['login']))
	{
		$test_session = grr_sql_query1("SELECT count(LOGIN) from ".TABLE_PREFIX."_log where END > now() and LOGIN = '".protect_data_sql($_SESSION['login'])."'");
		if ($test_session == 0)
			$_SESSION = array();
	}
	if ((!isset($_SESSION)) or (!isset($_SESSION['login'])))
		return (false);
	if ((Settings::get("disable_login")=='yes') and ($_SESSION['statut'] != "administrateur"))
		return (false);
		// To be removed
		// Validating session data
	$sql = "SELECT password = '" . $_SESSION['password'] . "' PASSWORD, login = '" . protect_data_sql($_SESSION['login']) . "' LOGIN, statut = '" . $_SESSION['statut'] . "' STATUT
	from ".TABLE_PREFIX."_utilisateurs where login = '" . protect_data_sql($_SESSION['login']) . "'";
	$res = grr_sql_query($sql);
	$row = grr_sql_row($res, 0);
		// Checking for a timeout
	$sql2 = "SELECT now() > END TIMEOUT from ".TABLE_PREFIX."_log where SESSION_ID = '" . session_id() . "' and START = '" . $_SESSION['start'] . "'";
	if ($row[0] != "1" || $row[1] != "1" || $row[2] != "1")
		return (false);
	else if (grr_sql_query1($sql2))
	{
		return (false);
	}
	else
	{
		$sql = "UPDATE ".TABLE_PREFIX."_log set END = now() + interval " . $_SESSION['maxLength'] . " minute where SESSION_ID = '" . session_id() . "' and START = '" . $_SESSION['start'] . "'";
		$res = grr_sql_query($sql);
		if (!$res)
			fatal_error(0, 'erreur mysql' . grr_sql_error());
		return (true);
	}
}
/**
 * Close a session
 *
 * Set the closing time in the logs
 * Destroy all session data
 * @_auto               string                  Session auto-close flag
 * @return              nothing
 */
function grr_closeSession(&$_auto)
{
	settype($_auto,"integer");
	session_name(SESSION_NAME);
	@session_start();
		// Sometimes 'start' may not exist, because the session was previously closed by another window
		// It's not necessary to ".TABLE_PREFIX."_log this, then
	if (isset($_SESSION['start']))
	{
		$sql = "update ".TABLE_PREFIX."_log set AUTOCLOSE = '" . $_auto . "', END = now() where SESSION_ID = '" . session_id() . "' and START = '" . $_SESSION['start'] . "'";
		grr_sql_query($sql);
	}
		// Détruit toutes les variables de session
	$_SESSION = array();
		// Détruit le cookie sur le navigateur
	$CookieInfo = session_get_cookie_params();
	@setcookie(session_name(), '', time()-3600, $CookieInfo['path']);
		// On détruit la session
	session_destroy();
}
function grr_verif_ldap($_login, $_password)
{
	global $ldap_filter;
	if ($_password == '')
		return false;
	include "config_ldap.inc.php";
	$ds = grr_connect_ldap($ldap_adresse,$ldap_port,$ldap_login,$ldap_pwd,$use_tls);
		// Test with login and password of the user
	if (!$ds)
	{
		$ds = grr_connect_ldap($ldap_adresse,$ldap_port,$_login,$_password,$use_tls);
	}
	if ($ds)
	{
				// Attributs testés pour egalite avec le login
		$atts = explode("|",Settings::get("ldap_champ_recherche"));
				//$atts = array('uid', 'login', 'userid', 'cn', 'sn', 'samaccountname', 'userprincipalname');
			//$login_search = ereg_replace("[^-@._[:space:][:alnum:]]", "", $_login);
		$login_search = preg_replace("/[^\-@._[:space:]a-zA-Z0-9]/", "", $_login);
				// Tenter une recherche pour essayer de retrouver le DN
		reset($atts);
		foreach ($atts as $att)
		{
			$dn = grr_ldap_search_user($ds, $ldap_base, $att, $login_search, $ldap_filter);
			if (($dn=="error_1") or ($dn=="error_2") or ($dn=="error_3"))
				return $dn;
			else if ($dn)
			{
				// on a le dn
				if (@ldap_bind($ds, $dn, $_password))
				{
					@ldap_unbind($ds);
					return $dn;
				}
			}
		}
				// Si echec, essayer de deviner le DN, dans le cas où il n'y a pas de filtre supplémentaires
		reset($atts);
		if (!isset($ldap_filter) or ($ldap_filter=""))
		{
			foreach ($atts as $att)
			{
				$dn = $att."=".$login_search.",".$ldap_base;
				if (@ldap_bind($ds, $dn, $_password))
				{
					@ldap_unbind($ds);
					return $dn;
				}
			}
		}
		return false;
	}
	else
		return false;
}
function grr_connect_ldap($l_adresse,$l_port,$l_login,$l_pwd, $use_tls, $msg_error = "no")
{
	$ds = @ldap_connect($l_adresse, $l_port);
	if ($ds)
	{
			 // On dit qu'on utilise LDAP V3, sinon la V2 par défaut est utilisé et le bind ne passe pas.
		if (!(ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3)))
		{
			if ($msg_error != "no") return "error_1";
				return "error_1";
			die();
		}
			 // Option LDAP_OPT_REFERRALS à désactiver dans le cas d'active directory
		@ldap_set_option($ds, LDAP_OPT_REFERRALS, 0);
		if ($use_tls)
		{
			if (!@ldap_start_tls($ds))
			{
				if ($msg_error != "no")
					return "error_2";
				return false;
			}
		}
			 // Accès non anonyme
		if ($l_login != '')
		{
					// On tente un bind
			$b = @ldap_bind($ds, $l_login, $l_pwd);
		}
		else
		{
					// Accès anonyme
			$b = @ldap_bind($ds);
		}
		if ($b)
		{
			return $ds;
		}
		else
		{
			if ($msg_error != "no")
				return "error_3";
			return false;
		}
	}
	else
	{
		if ($msg_error != "no")
			return "error_4";
		return false;
	}
}
/*
Effectue une recherche dans l'annuaire
$ds : identifiant de serveur ldap
$basedn : chemin de recherche
$login_attr : attribut de recherche
$login : valeur prise par l'attribut
$filtre_sup : filtre supplémentaire de recherche
$diagnostic : "no" ou "yes"
$diagnostic="yes" :
-> mode utilisé dans les tests de connexion à l'annuaire (admin_config_ldap.php) et quand le mode diagnostic est activé.
-> On renvoie différents codes d'erreur pour aider à l'analyse des résultats
$diagnostic="no" :
-> mode "normal" utilisé lors des connexions à l'annuaire pour se connecter à GRR.
*/
function grr_ldap_search_user($ds, $basedn, $login_attr, $login, $filtre_sup = "", $diagnostic = "no")
{
	if (Settings::get("ActiveModeDiagnostic") == "y")
		$diagnostic = "yes";
	// Construction du filtre
	$filter = "(".$login_attr."=".$login.")";
	if (!empty ($filtre_sup))
	{
		$filter = "(& ".$filter.$filtre_sup.")";
	}
	$res = @ldap_search($ds, $basedn, $filter, array ("dn", $login_attr),0,0);
	if ($res)
	{
		$info = @ldap_get_entries($ds, $res);
		if ((!is_array($info)) or ($info['count'] == 0))
		{
					// Mode diagnostic
			if ($diagnostic!="no")
				return "error_2";
			else
				return false;
		}
		else if ($info['count'] > 1)
		{
			// Si plusieurs entrées, on accepte uniquement en mode diagnostic
			if ($diagnostic!="no")
				return "error_3";
			else
				return false;
		}
		else
			return $info[0]['dn'];
	}
	else
	{
				// Mode diagnostic
		if ($diagnostic != "no")
			return "error_1";
		else
					 // Mode normal
			return false;
	}
}
/**
 * @return resource|boolean
 */
function grr_verif_imap($_login, $_password)
{
	if ($_password == '')
		return false;
	//include "config_imap.inc.php";
	$imap_connection = grr_connect_imap(Settings::get("imap_adresse"),Settings::get("imap_port"),$_login,$_password,Settings::get("imap_type"),Settings::get("imap_ssl"),Settings::get("imap_cert"),Settings::get("imap_tls"),"diag");
	if ($imap_connection)
		return $imap_connection;
	else
		return false;
}
/**
 * @return resource|boolean
 */
function grr_connect_imap($i_adresse,$i_port,$i_login,$i_pwd,$use_type,$use_ssl,$use_cert,$use_tls,$mode = "normal")
{
	$string1="";
	if (isset($i_adresse) && !empty($i_adresse))
		$string1.="{".$i_adresse;
	else
		return $out;
	if (isset($i_port) && !empty($i_port))
		$string1 .= ":".$i_port;
	if (isset($use_type))
		$string1 .= $use_type;
	if (isset($use_ssl))
		$string1 .= $use_ssl;
	if (isset($use_cert))
		$string1 .= $use_cert;
	if (isset($use_tls))
		$string1 .= $use_tls;
	$string1 .= "}";
	 // $connect_imap=imap_open($i_string,$i_login,$i_pwd,OP_HALFOPEN);
	//$string1 = "{pop.free.fr:110/pop3}";
	if ($use_type == "/imap")
	{
		$connect_imap = @imap_open($string1,$i_login,$i_pwd,OP_HALFOPEN);
		$string = $string1.",".$i_login.",".$i_pwd.",OP_HALFOPEN";
	}
	else
	{
		$connect_imap = @imap_open($string1,$i_login,$i_pwd);
		$string = $string1.",".$i_login.",".$i_pwd;
	}
	if ($connect_imap)
	{
		if ($mode == "diag")
		{
			echo "<h2><span style=\"color:green;\">La connexion a réussi !</span></h2>";
			@imap_close($connect_imap);
			return true;
		}
		else
			return $connect_imap;
	}
	if ($mode == "diag")
	{
		echo "<h2><span style=\"color:red;\">La connexion a échoué !</span></h2>";
		echo "<span style=\"color:red;\">La chaîne de connexion testée était : $string</span>";
		$errors = imap_errors();
		if (is_array($errors))
		{
			$num = 0;
			foreach ($errors as $key)
			{
				$num++;
				echo "<br /><span style=\"color:red;\">Erreur $num : ".$key. " </span>";
			}
		}
		$alert = imap_alerts();
		if (is_array($alert))
		{
			$num = 0;
			foreach ($alert as $key)
			{
				$num++;
				echo "<br /><span style=\"color:red;\">Alerte $num : ".$key. " </span>";
			}
		}
	}
	return false;
}
function grr_getinfo_ldap($_dn, $_login, $_password)
{
	// Lire les infos sur l'utilisateur depuis LDAP
	include "config_ldap.inc.php";
	// Connexion à l'annuaire
	$ds = grr_connect_ldap($ldap_adresse,$ldap_port,$ldap_login,$ldap_pwd,$use_tls);
	// Test with login and password of the user
	if (!$ds)
	{
		$ds = grr_connect_ldap($ldap_adresse,$ldap_port,$_login,$_password,$use_tls);
	}
	if ($ds)
	{
		$result = @ldap_read($ds, $_dn, "objectClass=*", array(Settings::get("ldap_champ_nom"),Settings::get("ldap_champ_prenom"),Settings::get("ldap_champ_email")));
	}
	if (!$result)
		return "2";
	// Recuperer les donnees de l'utilisateur
	$info = @ldap_get_entries($ds, $result);
	if (!is_array($info))
		return "2";
	for ($i = 0; $i < $info["count"]; $i++)
	{
		$val = $info[$i];
		if (is_array($val))
		{
			if (isset($val[Settings::get("ldap_champ_nom")][0]))
				$l_nom = ucfirst($val[Settings::get("ldap_champ_nom")][0]);
			else
				$l_nom = iconv("ISO-8859-1","utf-8","Nom à préciser");
			if (isset($val[Settings::get("ldap_champ_prenom")][0]))
				$l_prenom = ucfirst($val[Settings::get("ldap_champ_prenom")][0]);
			else
				$l_prenom = iconv("ISO-8859-1","utf-8","Prénom à préciser");
			if (isset($val[Settings::get("ldap_champ_email")][0]))
				$l_email = $val[Settings::get("ldap_champ_email")][0];
			else
				$l_email = '';
		}
	}
	// Convertir depuis UTF-8 (jeu de caracteres par defaut)
	if ((function_exists("utf8_decode")) and (Settings::get("ConvertLdapUtf8toIso") == "y"))
	{
		$l_email = utf8_decode($l_email);
		$l_nom = utf8_decode($l_nom);
		$l_prenom = utf8_decode($l_prenom);
	}
	// Return infos
	return array($l_nom, $l_prenom, $l_email);
}
// On fabrique l'url
$url = rawurlencode(str_replace('&amp;','&',get_request_uri()));
?>
