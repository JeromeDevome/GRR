<?php
/**
 * admin_config_ldap.php
 * Interface permettant la configuration de l'accès à un annuaire LDAP
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2021-11-28 18:31$
 * @author    Laurent Delineau & JeromeB & Yan Naessens & Daniel Antelme
 * @copyright Copyright 2003-2021 Team DEVOME - JeromeB
 * @link      http://www.gnu.org/licenses/licenses.html
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 */
$grr_script_name = "admin_config_ldap.php";

include "../include/connect.inc.php";
include "../include/config.inc.php";
include "../include/misc.inc.php";
include "../include/functions.inc.php";
include "../include/$dbsys.inc.php";

// Settings
require_once("../include/settings.class.php");
// Session related functions
require_once("../include/session.inc.php");
// Paramètres langage
include "../include/language.inc.php";
//Chargement des valeurs de la table settingS
if (!Settings::load())
	die(get_vocab('error_settings_load'));
$valid = isset($_POST["valid"]) ? $_POST["valid"] : 'no';
$etape = isset($_POST["etape"]) ? $_POST["etape"] : '0';
$adresse = isset($_POST["adresse"]) ? clean_input($_POST["adresse"]) : NULL;
$port = isset($_POST["port"]) ? clean_input($_POST["port"]) : NULL;
$login_ldap = isset($_POST["login_ldap"]) ? clean_input($_POST["login_ldap"]) : NULL;
$pwd_ldap = isset($_POST["pwd_ldap"]) ? clean_input($_POST["pwd_ldap"]) : NULL;

if (isset($_POST["use_tls"]))
{
	if ($_POST["use_tls"] == 'y')
		$use_tls = TRUE;
	else $use_tls = FALSE;
}
else
	$use_tls = FALSE;
$base_ldap = isset($_POST["base_ldap"]) ? clean_input($_POST["base_ldap"]) : NULL;
$base_ldap_autre = isset($_POST["base_ldap_autre"]) ? clean_input($_POST["base_ldap_autre"]) : NULL;
$ldap_filter = isset($_POST["ldap_filter"]) ? clean_input($_POST["ldap_filter"]) : NULL;

$ldap_group_member_attr = isset($_POST["ldap_group_member_attr"]) ? clean_input($_POST["ldap_group_member_attr"]) : NULL;
$ldap_group_base = isset($_POST["ldap_group_base"]) ? clean_input($_POST["ldap_group_base"]) : NULL;
$ldap_group_filter = isset($_POST["ldap_group_filter"]) ? clean_input($_POST["ldap_group_filter"]) : NULL;
$ldap_group_user_field = isset($_POST["ldap_group_user_field"]) ? clean_input($_POST["ldap_group_user_field"]) : NULL;

if (isset($_POST['reg_ldap_statut']))
{
	if ($_POST['ldap_statut'] == "no_ldap")
	{
		$req = grr_sql_query("delete from ".TABLE_PREFIX."_setting where NAME = 'ldap_statut'");
		$grrSettings['ldap_statut'] = '';
	}
	else
	{
		if (!Settings::set("ldap_statut", $_POST['ldap_statut']))
			echo encode_message_utf8($vocab['save_err']." ldap_statut !<br />");
		$grrSettings['ldap_statut'] = $_POST['ldap_statut'];
	}
	if (isset($_POST['Valider1']))
	{
		if (!isset($_POST['ConvertLdapUtf8toIso']))
			$ConvertLdapUtf8toIso = "n";
		else
			$ConvertLdapUtf8toIso = "y";
		if (!Settings::set("ConvertLdapUtf8toIso", $ConvertLdapUtf8toIso))
			echo $vocab['save_err']." ConvertLdapUtf8toIso !<br />";
		$grrSettings['ConvertLdapUtf8toIso'] = $ConvertLdapUtf8toIso;
		if (!isset($_POST['ActiveModeDiagnostic']))
			$ActiveModeDiagnostic = "n";
		else
			$ActiveModeDiagnostic = "y";
		if (!Settings::set("ActiveModeDiagnostic", $ActiveModeDiagnostic))
			echo $vocab['save_err']." ActiveModeDiagnostic !<br />";
		$grrSettings['ActiveModeDiagnostic'] = $ActiveModeDiagnostic;
		if (!Settings::set("ldap_champ_recherche", $_POST['ldap_champ_recherche']))
			echo $vocab['save_err']." ldap_champ_recherche !<br />";
		$grrSettings['ldap_champ_recherche'] = $_POST['ldap_champ_recherche'];
		if ($_POST['ldap_champ_nom'] == '')
			$_POST['ldap_champ_nom'] = "sn";
		if (!Settings::set("ldap_champ_nom", $_POST['ldap_champ_nom']))
			echo $vocab['save_err']." ldap_champ_nom !<br />";
		$grrSettings['ldap_champ_nom'] = $_POST['ldap_champ_nom'];
		if ($_POST['ldap_champ_prenom'] == '')
			$_POST['ldap_champ_prenom'] = "sn";
		if (!Settings::set("ldap_champ_prenom", $_POST['ldap_champ_prenom']))
			echo $vocab['save_err']." ldap_champ_prenom !<br />";
		$grrSettings['ldap_champ_prenom'] = $_POST['ldap_champ_prenom'];
		if ($_POST['ldap_champ_email'] == '')
			$_POST['ldap_champ_email'] = "sn";
		if (!Settings::set("ldap_champ_email", $_POST['ldap_champ_email']))
			echo $vocab['save_err']." ldap_champ_email !<br />";
		$grrSettings['ldap_champ_email'] = $_POST['ldap_champ_email'];
		if (!Settings::set("se3_liste_groupes_autorises", $_POST['se3_liste_groupes_autorises']))
			echo $vocab['save_err']." se3_liste_groupes_autorises !<br />";
		$grrSettings['se3_liste_groupes_autorises'] = $_POST['se3_liste_groupes_autorises'];
	}
}
if (isset($_POST['submit']))
{
	if (isset($_POST['login']) && isset($_POST['password']))
	{
		$sql = "select upper(login) login, password, prenom, nom, statut from ".TABLE_PREFIX."_utilisateurs where login = '" . $_POST['login'] . "' and password = md5('" . $_POST['password'] . "') and etat != 'inactif' and statut='administrateur' ";
		$res_user = grr_sql_query($sql);
		$num_row = grr_sql_count($res_user);
		if ($num_row == 1)
			$valid='yes';
		else
			$message = get_vocab("wrong_pwd");
	}
}

if ((!grr_resumeSession()) && $valid != 'yes')
{
    echo "<!DOCTYPE html>";
    echo '<html>
            <head>
                <link rel="stylesheet" href="../themes/default/css/style.css" type="text/css">
                <title> grr </title>
                <link rel="shortcut icon" href="./favicon.ico">
			</head>
			<body>';
    echo "<h2>".get_vocab('configurerLdap')."</h2>";
	echo '<form action="admin_config_ldap.php" method="post">';
	if (isset($message))
		echo("<p class=\"avertissement\">" . $message . "</p>");
	echo '<fieldset>';
	echo '<legend style="font-variant: small-caps;">'.get_vocab("identification").'</legend>';
	echo '<table class="table-noborder">';
	echo '	<tr>
				<td style="text-align: right; width: 40%; font-variant: small-caps;"><label for="login">';
                echo get_vocab("login");
                echo '</label></td>';
        echo '<td style="text-align: center; width: 60%;"><input type="text" name="login" size="16" /></td>';
	echo '	</tr>
			<tr>
				<td style="text-align: right; width: 40%; font-variant: small-caps;"><label for="password">';
                echo get_vocab("pwd");
    echo '      </label></td>';
	echo '		<td style="text-align: center; width: 60%;"><input type="password" name="password" size="16" /></td>';
	echo '	</tr>
		</table>';
	echo '<input type="submit" name="submit" value="'.get_vocab("OK").'" style="font-variant: small-caps;" />';
	echo '</fieldset>';
	echo '</form>';
	echo '</body>';
	echo '</html>';
	die();
};
$back = (isset($_SERVER['HTTP_REFERER']))? htmlspecialchars_decode($_SERVER['HTTP_REFERER'], ENT_QUOTES) : "./admin_accueil.php" ;
if ((isset($ldap_restrictions)) && ($ldap_restrictions == true))
{
    showAccessDenied($back);
    exit();
}
if ((authGetUserLevel(getUserName(), -1) < 6) && ($valid != 'yes'))
{
    showAccessDenied($back);
    exit();
}
if ($valid == 'no')
{
    start_page_w_header("", "", "", $type="with_session");
    // Affichage de la colonne de gauche
    include "admin_col_gauche2.php";
}
else
{
    echo "<!DOCTYPE html>";
    echo '<html>
        <head>
            <link rel="stylesheet" href="style.css" type="text/css">
            <link rel="shortcut icon" href="favicon.ico">
                <title> grr </title>
            </head>
            <body>';
}
if ($etape == 3)
{
    echo "<h2>".get_vocab('titreLdap')."</h2>\n";
    echo "<h2>".encode_message_utf8(get_vocab('enregistrerConfig'))."</h2>\n";
    if (!$base_ldap)
        $base_ldap = $base_ldap_autre;
    $ds = grr_connect_ldap($adresse,$port,$login_ldap,$pwd_ldap,$use_tls);
    // On verifie le chemin fourni
    $result = grr_ldap_search_user($ds, $base_ldap, "objectClass", "*",$ldap_filter,"y");
    if ($result == "error_1")
    {
        if ($ldap_filter == "")
            echo "<p>".encode_message_utf8(get_vocab('cheminNonValide'))."</p><br />";
        else
            echo "<p>".encode_message_utf8(get_vocab('cheminOuFiltreNonValide'))."</p><br />";
    }
    else if ($result == "error_2")
    {
        if ($ldap_filter == "")
            echo "<p>".encode_message_utf8(get_vocab('cheminOKetResultatKO'))."</p><br />";
        else
            echo "<p>".encode_message_utf8(get_vocab('cheminFiltreOKetResultatKO'))."</p><br />";
    }
    // Le cas "error_3" n'est pas analysé car on accepte les  cas où il y a plusieurs entrées dans l'annuaire à l'issue de la recherche
    $erreur = '';
    $nom_fic = "../include/config_ldap.inc.php";
    if (@file_exists($nom_fic))
    {
        unlink($nom_fic);
        if (@file_exists($nom_fic))
            $erreur = get_vocab('cantDeleteFile')." \"".$nom_fic."\" ".get_vocab('cantDeleteFileComm');
    }
    if ($erreur == '')
    {
        $f = @fopen($nom_fic, "wb");
        if (!$f)
        {
            $erreur = get_vocab('cant_cerate')." \"".$nom_fic."\".";
            if (@file_exists($nom_fic.".ori"))
                $erreur .= "<br />".get_vocab('renommer')." \"".$nom_fic.".ori\" ".get_vocab('en')." \"".$nom_fic."\", ".get_vocab('donner_droits');
            else
                $erreur .= "<br />".get_vocab('modifier_droits');
        }
    }
    if ($erreur == '')
    {
        // On a ouvert un fichier config_ldap.inc.php
        $conn = "<"."?php\n";
        $conn .= "# Les quatre éléments suivants sont à modifier selon votre configuration\n"
                ."\n";
        $conn .= "# 1. l'adresse (URI) de l'annuaire LDAP.\n";
        $conn .= "# Si c'est le même que celui qui heberge les scripts, mettre \"ldap://localhost\"\n";
        $conn .= "\$ldap_adresse='".$adresse."';\n"
                ."\n";
        $conn .= "# 2. le port utilisé\n";
        $conn .= "\$ldap_port='".$port."';\n"
                ."\n";
        $conn .= "# 3. l'identifiant et le mot de passe dans le cas d'un accès non anonyme\n";
        $conn .= "\$ldap_login='".$login_ldap."';\n";
        $conn .= "# Remarque : des problèmes liés à un mot de passe contenant un ou plusieurs caractères accentués ont déjà été constatés.\n";
        $conn .= "\$ldap_pwd='".addslashes($pwd_ldap)."';\n"
                ."\n";
        $conn .= "# 4. le chemin d'accès dans l'annuaire\n";
        $conn .= "\$ldap_base='".$base_ldap."';\n";
        $conn .= "# filtre LDAP supplémentaire (facultatif)\n";
        $conn .= "\$ldap_filter='".$ldap_filter."';\n";
        $conn .= "# ligne suivante : utiliser TLS\n";
        if ($use_tls)
            $conn .= "\$use_tls=TRUE;\n";
        else
            $conn .= "\$use_tls=FALSE;\n";
        $conn .= "# Attention : si vous configurez manuellement ce fichier (sans passer par la configuration en ligne)\n";
        $conn .= "# vous devez tout de même activer LDAP en choisissant le \"statut par défaut des utilisateurs importés\".\n";
        $conn .= "# Pour cela, rendez-vous sur la page : configuration -> Configuration LDAP.\n";
        $conn .= "\n#SE3 variables\n";
        $conn .= "\$ldap_group_member_attr=\"{$ldap_group_member_attr}\";\n";
        $conn .= "\$ldap_group_base=\"{$ldap_group_base}\";\n";
        $conn .= "\$ldap_group_filter=\"{$ldap_group_filter}\";\n";
        $conn .= "\$ldap_group_user_field=\"{$ldap_group_user_field}\";\n";
        $conn .= "?".">";
        @fputs($f, $conn);
        if (!@fclose($f))
            $erreur = "Impossible d'enregistrer le fichier \"".$nom_fic."\".";
    }
    if ($erreur == '')
        echo "<p>".encode_message_utf8("<b>".get_vocab('ldap_record_success'))." \"".$nom_fic."\".</b></p>";
    else
        echo encode_message_utf8("<p>".$erreur."</p>");
    if ($erreur == '')
    {
        echo "<form action=\"admin_config_ldap.php\" method=\"post\">\n";
        echo "<div><input type=\"hidden\" name=\"etape\" value=\"0\" />\n";
        echo "<input type=\"hidden\" name=\"valid\" value=\"$valid\" />\n";
        echo "<div class=\"center\"><input type=\"submit\" name=\"Valider\" value=\"".get_vocab('Terminer')."\" /></div>\n";
        echo "</div></form>";
    }
}
else if ($etape == 2)
{
    echo "<h2>".get_vocab('titreLdap')."</h2>\n";
    echo "<h2>".encode_message_utf8(get_vocab('ldapConnexion'))."</h2>\n";
    // Connexion à l'annuaire
    $ds = grr_connect_ldap($adresse,$port,$login_ldap,$pwd_ldap,$use_tls);
    if ($ds)
        $connexion_ok = 'yes';
    else
        $connexion_ok = 'no';
    if ($connexion_ok == 'yes')
    {
        echo "<p><b>".encode_message_utf8(get_vocab('ldapConnectSuccess'))."</b></p>\n";
        echo "<form action=\"admin_config_ldap.php\" method=\"post\"><div>\n";
        // On lit toutes les infos (objectclass=*) dans le dossier
        // Retourne un identifiant de résultat ($result), ou bien FALSE en cas d'erreur.
        $result = ldap_read($ds, "", "objectclass=*", array("namingContexts"));
        $info = ldap_get_entries($ds, $result);
        // Retourne un tableau associatif multi-dimensionnel ou FALSE en cas d'erreur. :
        // $info["count"] = nombre d'entrées dans le résultat
        // $info[0] : sous-tableau renfermant les infos de la première entrée
        // $info[n]["dn"] : dn de la n-ième entrée du résultat
        // $info[n]["count"] : nombre d'attributs de la n-ième entrée
        // $info[n][m] : m-ième attribut de la n-ième entrée
        // info[n]["attribut"]["count"] : nombre de valeur de cet attribut pour la n-ième entrée
        // $info[n]["attribut"][m] : m-ième valeur de l'attribut pour la n-ième entrée
        $checked = false;
        if (is_array($info) && $info["count"] > 0)
        {
            echo "<p>".encode_message_utf8(get_vocab('selectLdapPath'))."</p>";
            $n = 0;
            for ($i = 0; $i < $info["count"]; $i++)
            {
                $names[] = $info[$i]["dn"];
                if (is_array($names))
                {
                    for ($j = 0; $j < count($names); $j++)
                    {
                        $n++;
                        echo "<br /><input name=\"base_ldap\" value=\"".htmlspecialchars($names[$j])."\" type='radio' id='tab$n'";
                        if (!$checked)
                        {
                            echo " checked=\"checked\"";
                            $checked = true;
                        }
                        echo " />\n";
                        echo "<label for='tab$n'>".htmlspecialchars($names[$j])."</label>\n";
                    }
                }
            }
            echo "<br />".get_vocab('Ou_bien')." \n";
        }
        echo "<br /><input name=\"base_ldap\" value=\"\" type='radio' id=\"autre\"";
        if (!$checked)
        {
            echo " checked=\"checked\"";
            $checked = true;
        }
        echo " />\n";
        echo "<label for=\"autre\">".encode_message_utf8(get_vocab('preciserChemin'))."</label>\n ";
        if (isset($_POST["ldap_base"]))
            $ldap_base = $_POST["ldap_base"];
        else
            $ldap_base ="";
        if (isset($_POST["ldap_filter"]))
            $ldap_filter = $_POST["ldap_filter"];
        else $ldap_filter ="";
        echo "<input type=\"text\" name=\"base_ldap_autre\" value=\"$ldap_base\" size=\"40\" />\n";
        echo "<br /><br />".encode_message_utf8(get_vocab('ldapAddFilter'));
        echo "<br /><input type=\"text\" name=\"ldap_filter\" value=\"$ldap_filter\" size=\"50\" />\n";
        echo "<br /><br />\n";
        echo "<b>".encode_message_utf8(get_vocab('saveInfosLDAPWarning')."</b><br /><br />".get_vocab('clickToSave')." \"".get_vocab('saveInfos')."\".<br /><br />\n");
        echo "<input type=\"hidden\" name=\"etape\" value=\"3\" />\n";
        echo "<input type=\"hidden\" name=\"adresse\" value=\"$adresse\" />\n";
        echo "<input type=\"hidden\" name=\"port\" value=\"$port\" />\n";
        echo "<input type=\"hidden\" name=\"login_ldap\" value=\"$login_ldap\" />\n";
        echo "<input type=\"hidden\" name=\"pwd_ldap\" value=\"$pwd_ldap\" />\n";
        echo "<input type=\"hidden\" name=\"valid\" value=\"$valid\" />\n";
        if ($use_tls)
            echo "<input type=\"hidden\" name=\"use_tls\" value=\"y\" />\n";
        echo "<div class=\"center\"><input type=\"submit\" name=\"Valider\" value=\"".get_vocab('saveInfos')."\" /></div>\n";
        echo "</div></form>";
    }
    else
    {
        echo "<b>".encode_message_utf8(get_vocab('ldapConnFailed'))."</b><br />\n";
        echo encode_message_utf8(get_vocab('goBackVerify'));
        echo "<form method=\"post\" action=\"admin_config_ldap.php\">\n";
        echo "<div>\n<input type=\"hidden\" name=\"etape\" value=\"1\" />\n";
        echo "<input type=\"hidden\" name=\"ldap_adresse\" value=\"$adresse\" />\n";
        echo "<input type=\"hidden\" name=\"ldap_port\" value=\"$port\" />\n";
        echo "<input type=\"hidden\" name=\"ldap_login\" value=\"$login_ldap\" />\n";
        if ($use_tls)
            echo "<input type=\"hidden\" name=\"use_tls\" value=\"y\" />\n";
        echo "<input type=\"submit\" name=\"valider\" value=\"".encode_message_utf8(get_vocab('pagePrecedente'))."\" />\n";
        echo "</div></form>\n";
    }
}
else if ($etape == 1)
{
    if (isset($_POST["valider"]))
    {
        $ldap_adresse = $_POST["ldap_adresse"];
        $ldap_port = $_POST["ldap_port"];
        $ldap_login = $_POST["ldap_login"];
    }
    else if (@file_exists("../include/config_ldap.inc.php"))
        include("../include/config_ldap.inc.php");
    echo encode_message_utf8("<h2>".get_vocab('titreLdap')."</h2>\n");
    echo "<h2>".encode_message_utf8(get_vocab('ldapConnInfo'))."</h2>\n";
    echo "<form action=\"admin_config_ldap.php\" method=\"post\">\n";
    if ((!(isset($ldap_adresse))) || ($ldap_adresse == ""))
        $ldap_adresse = 'ldap://localhost';
    if ((!(isset($ldap_port))) || ($ldap_port == ""))
        $ldap_port = 389;
    if (!(isset($ldap_login)))
        $ldap_login = "";
    if (!(isset($ldap_pwd)))
        $ldap_pwd = "";
    echo "<div>\n<input type=\"hidden\" name=\"etape\" value=\"2\" />\n";
    echo "<input type=\"hidden\" name=\"valid\" value=\"$valid\" /></div>\n";
    echo "<h3>".encode_message_utf8(get_vocab('dirURI'))."</h3><div>".get_vocab('dirURIexpl');
    echo "<input type=\"text\" name=\"adresse\" value=\"".$ldap_adresse."\" size=\"20\" />";
    echo "<h3>".encode_message_utf8(get_vocab('dirURIPort'))."</h3>".get_vocab('dirURIPortExpl');
    echo "<input type='text' name='port' value=\"$ldap_port\" size=\"20\" /></div>";
    echo "<h3>".encode_message_utf8(get_vocab('typeAcces'))."</h3><div>".get_vocab('typeAccesExpl')."<br /><b>".get_vocab('login').get_vocab('deux_points')."</b><br />";
    echo "<input type=\"text\" name=\"login_ldap\" value=\"".$ldap_login."\" size=\"40\" /><br />";
    echo "<b>".get_vocab('pwd').get_vocab('deux_points')."</b><br />";
    echo encode_message_utf8(get_vocab('ldapPwdWarning'))."<br />";
    echo "<input type=\"password\" name=\"pwd_ldap\" value=\"".$ldap_pwd."\" size=\"40\" /><br /></div>\n";
    echo "<h3>".get_vocab('useTLS').get_vocab('deux_points')."</h3>\n";
    echo "<div>\n<input type=\"radio\" name=\"use_tls\" value=\"y\" ";
    if ($use_tls)
        echo " checked=\"checked\" ";
    echo "/>".get_vocab('Oui')."\n";
    echo "<input type=\"radio\" name=\"use_tls\" value=\"n\" ";
    if (!($use_tls))
        echo " checked=\"checked\" ";
    echo "/>".get_vocab('Non')."\n";
    if (isset($ldap_filter))
        echo "<input type=\"hidden\" name=\"ldap_filter\" value=\"$ldap_filter\" />";
    if (isset($ldap_base))
        echo "<input type=\"hidden\" name=\"ldap_base\" value=\"$ldap_base\" />";
    //TODO: Ajouter les informations pour les groupes
    echo "<br /><br /><b>".encode_message_utf8(get_vocab('saveInfosLDAPWarning')."</b><br />".get_vocab('saveInfosLDAPWarning2')."</div>");


    echo "<div style=\"text-align:center;\"><input type=\"submit\" value=\"".get_vocab('next')."\" /></div>";
    echo "</form>";

}
else if ($etape == 0)
{
    if (!(function_exists("ldap_connect")))
    {
		echo "<div class='col-sm-9 col-xs-12'>";
        echo encode_message_utf8("<h2>".get_vocab('titreLdap')."</h2>\n");
        echo encode_message_utf8("<p class=\"avertissement\">".get_vocab('LDAPinactive')."<br />".get_vocab('LDAPimpossible')."</p></div></section></body></html>");
        die();
    }
    echo encode_message_utf8("<h2>".get_vocab('titreLdap')."</h2>\n");
    echo "<p>".encode_message_utf8(get_vocab('LDAPpossible'))."</p>";
    echo "<form action=\"admin_config_ldap.php\" method=\"post\">\n";
    echo "<div>\n<input type=\"hidden\" name=\"etape\" value=\"0\" />\n";
    echo "<input type=\"hidden\" name=\"valid\" value=\"$valid\" />\n";
    echo "<input type=\"hidden\" name=\"reg_ldap_statut\" value=\"yes\" /></div>\n";
    if (Settings::get("ldap_statut") != '')
    {
        echo "<h3>".encode_message_utf8(get_vocab('LDAPauthActive'))."</h3>\n";
        echo "<h3>".encode_message_utf8(get_vocab('statutDefaut'))."</h3>\n";
        echo "<div>".encode_message_utf8(get_vocab('choixStatutDefaut'))."<br />";
        echo "<input type=\"radio\" name=\"ldap_statut\" value=\"visiteur\" ";
        if (Settings::get("ldap_statut") == 'visiteur')
            echo " checked=\"checked\" ";
        echo "/>".get_vocab('statut_visitor')."<br />";
        echo "<input type=\"radio\" name=\"ldap_statut\" value=\"utilisateur\" ";
        if (Settings::get("ldap_statut") == 'utilisateur')
            echo " checked=\"checked\" ";
        echo "/>".get_vocab('statut_user')."<br />";
        echo get_vocab('Ou_bien')."<br />";
        echo "<input type=\"radio\" name=\"ldap_statut\" value=\"no_ldap\" />".encode_message_utf8(get_vocab('desactiverLDAPauth'))."<br />";
        echo "<br />";
        echo "<input type=\"checkbox\" name=\"ConvertLdapUtf8toIso\" value=\"y\" ";
        if (Settings::get("ConvertLdapUtf8toIso") == "y")
            echo " checked=\"checked\"";
        echo " />";
        echo encode_message_utf8(get_vocab('UTF8storage'));
        echo "<br />";
        echo "<input type=\"checkbox\" name=\"ActiveModeDiagnostic\" value=\"y\" ";
        if (Settings::get("ActiveModeDiagnostic") == "y")
            echo " checked=\"checked\"";
        echo " />";
        echo encode_message_utf8(get_vocab('modeDiagnostic'));
        echo "<br /><br />";
        if (Settings::get("ldap_champ_recherche") == '')
            echo "<span class=\"avertissement\">";
        echo "<b>".encode_message_utf8(get_vocab('searchAttr'))."</b>".get_vocab('deux_points');
        echo "<input class=\"form-control\" type=\"text\" name=\"ldap_champ_recherche\" value=\"".htmlentities( Settings::get("ldap_champ_recherche"))."\" size=\"50\" />";
        if (Settings::get("ldap_champ_recherche") == '')
            echo "<br />".get_vocab('champNonVide')."</span>";
        echo "<br />";
        echo encode_message_utf8(get_vocab('ldapFieldExpl'));
        echo "<br /><br /><b>".get_vocab('liaisonsGrrLdap')."</b>";
        echo "<table><tr>";
        echo "<td>".get_vocab('familyName')."</td>";
        echo "<td><input class=\"form-control\" type=\"text\" name=\"ldap_champ_nom\" value=\"".htmlentities( Settings::get("ldap_champ_nom"))."\" size=\"20\" /></td>";
        echo "<td>".encode_message_utf8(get_vocab('first_name'))." : </td>";
        echo "<td><input class=\"form-control\" type=\"text\" name=\"ldap_champ_prenom\" value=\"".htmlentities( Settings::get("ldap_champ_prenom"))."\" size=\"20\" /></td>";
        echo "<td>".get_vocab('mail_user').get_vocab('deux_points')."</td>";
        echo "<td><input class=\"form-control\" type=\"text\" name=\"ldap_champ_email\" value=\"".htmlentities( Settings::get("ldap_champ_email"))."\" size=\"20\" /></td>";
        echo "</tr></table>";
        echo "<br /><br />".encode_message_utf8(get_vocab('ldapSE3Expl'));
        echo "<br />\n<input class=\"form-control\" type=\"text\" name=\"se3_liste_groupes_autorises\" value=\"".htmlentities( Settings::get("se3_liste_groupes_autorises"))."\" size=\"50\" />\n";
        echo "</div>\n";
        echo "<div class=\"center\">\n<input class=\"btn btn-primary\" type=\"submit\" name=\"Valider1\" value=\"".get_vocab('OK')."\" />\n</div>\n";
    }
    else
    {
        echo "<h3>".encode_message_utf8(get_vocab('ldapAuthInactive'))."</h3>\n";
        echo "<div><b>".encode_message_utf8(get_vocab('LDAPimpossible'))."</b>".encode_message_utf8(get_vocab('LDAPstatutExpl'))."<br />";
        echo "<input type=\"radio\" name=\"ldap_statut\" value=\"visiteur\" />".get_vocab('statut_visitor')."<br />\n";
        echo "<input type=\"radio\" name=\"ldap_statut\" value=\"utilisateur\" />".get_vocab('statut_user')."<br />\n";
        echo "<input type=\"radio\" name=\"ldap_statut\" value=\"no_ldap\" checked=\"checked\" />".get_vocab('nonActive')."<br /></div>\n";
        echo "<div class=\"center\"><input type=\"submit\" name=\"Valider2\" value=\"".get_vocab('OK')."\"  /></div>\n";
        // fin de l'affichage de la colonne de droite
        if ($valid == 'no')
            echo "</div>";
        end_page();
        die();
    }
    echo "</form>\n";
    if (@file_exists("../include/config_ldap.inc.php"))
    {
        $test_chemin = '';
        include("../include/config_ldap.inc.php");
        if (($ldap_adresse != '') && ($ldap_port != ''))
        {
            $ok = "OK";
            $failed = get_vocab("Echec");
            echo "<hr />\n";
            $ds = grr_connect_ldap($ldap_adresse,$ldap_port,$ldap_login,$ldap_pwd,$use_tls,'y');
            if ($ds == "error_1")
            {
                returnmsg('danger',get_vocab('testConnAnnuaire').get_vocab('deux_points'), $failed, get_vocab('LDAPV3impossible'));
            }
            else if ($ds == "error_2")
            {
                returnmsg('danger' ,get_vocab('testConnAnnuaire').get_vocab('deux_points'), $failed, get_vocab('TLSimpossible'));
            }
            else if ($ds == "error_3")
            {
                returnmsg('danger' ,get_vocab('testConnAnnuaire').get_vocab('deux_points'), $failed, get_vocab('connOK_idKO'));
            }
            else if ($ds == "error_4")
            {
                returnmsg('danger' ,get_vocab('testConnAnnuaire').get_vocab('deux_points'), $failed, get_vocab('connKO'));
            }
            else if (!$ds)
                echo encode_message_utf8($failed)."</h3></div>";
            else
            {
                returnmsg('success',get_vocab('testConnAnnuaire').get_vocab('deux_points'), $ok, '');
                $result = "";
                $result = grr_ldap_search_user($ds, $ldap_base, "objectClass", "*",$ldap_filter,"y");
                if ($result == "error_1")
                {

                    $test_chemin = 'failed';
                    if ($ldap_filter == "")
                        returnmsg('danger',get_vocab('testConnChemAnn').get_vocab('deux_points'), $failed, get_vocab('testConnChemAnnErr1'));
                    else
                        returnmsg('danger',get_vocab('testConnChemAnn').get_vocab('deux_points'), $failed, get_vocab('testConnChemAnnErr1bis'));
                }
                else if ($result == "error_2")
                {
                    $test_chemin = 'failed';
                    if ($ldap_filter == "")
                        returnmsg('danger',get_vocab('testConnChemAnn').get_vocab('deux_points'), $failed, get_vocab('testConnChemAnnErr2'));
                    else
                        returnmsg('danger',get_vocab('testConnChemAnn').get_vocab('deux_points'), $failed, get_vocab('testConnChemAnnErr2bis'));
                }
                else
                    returnmsg('success',get_vocab('testConnChemAnn').get_vocab('deux_points'), $ok, '');
            }
        }
    }
    echo "<hr />";
    if (@file_exists("../include/config_ldap.inc.php"))
    {
        echo "<h3>".encode_message_utf8(get_vocab('configurationActuelle'))."</h3>(".get_vocab('infosinFile')." \"config_ldap.inc.php\") :<br /><ul>";
        echo "<li>".encode_message_utf8(get_vocab('LDAPadresse'))." <b>: ".$ldap_adresse."</b></li>";
        echo "<li>".encode_message_utf8(get_vocab('portUtilise').get_vocab('deux_points'))." <b>".$ldap_port."</b></li>";
        if ($test_chemin == 'failed')
            echo "<li><div class=\"alert alert-danger\" role=\"alert\">".encode_message_utf8(get_vocab('ldapPath').get_vocab('deux_points'))."<b> ".$ldap_base."</b></div></li>";
        else
            echo "<li>".encode_message_utf8(get_vocab('ldapPath').get_vocab('deux_points'))."<b> ".$ldap_base."</b></li>";
        if ($ldap_filter!="")
            $ldap_filter_text = $ldap_filter;
        else
            $ldap_filter_text = "non";
        if (($test_chemin == 'failed') && ($ldap_filter!=""))
            echo "<li><div class=\"alert alert-danger\" role=\"alert\">".encode_message_utf8(get_vocab('filtreLdapSupp').get_vocab('deux_points'))."<b> ".$ldap_filter_text."</b></div></li>";
        else
            echo "<li>".encode_message_utf8(get_vocab('filtreLdapSupp').get_vocab('deux_points'))."<b> ".$ldap_filter_text."</b></li>";
        if ($ldap_login) {
            echo "<li>".encode_message_utf8(get_vocab('ldapLogin'))."<br />";
            echo get_vocab('login').get_vocab('deux_points')."<b>".$ldap_login."</b><br />";
            $ldap_pwd_hide = "";
            for ($i=0;$i<strlen($ldap_pwd);$i++)
                $ldap_pwd_hide .= "*";
            echo get_vocab('pwd').get_vocab('deux_points')."<b>".$ldap_pwd_hide."</b></li>";
        }
        else
            echo "<li>".encode_message_utf8(get_vocab('accesAnonyme'))."</li>";
        if ($use_tls)
            $use_tls_text = "oui";
        else
            $use_tls_text = "non";
        echo "<li>".encode_message_utf8(get_vocab('useTLS').get_vocab('deux_points'))."<b>".$use_tls_text."</b></li></ul>";
        echo encode_message_utf8(get_vocab('newLdapConfig'))."<br />";
    }
    else
        echo "<h3>".encode_message_utf8(get_vocab('accesLdapNonConfigure'))."</h3>\n<b>".get_vocab('ldapAuthImpossible')."</b>\n";
    echo "<form action=\"admin_config_ldap.php\" method=\"post\">\n";
    echo "<div><input type=\"hidden\" name=\"etape\" value=\"1\" />\n";
    echo "<input type=\"hidden\" name=\"valid\" value=\"$valid\" /></div>\n";
    echo "<div style=\"text-align:center;\"><input class=\"btn btn-primary\" type=\"submit\" value=\"".get_vocab('configureLdap')."\" /></div></form>\n";
}
if ($valid == 'no') echo "</div>";
end_page();
?>