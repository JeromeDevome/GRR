<?php
/**
 * admin_config_ldap.php
 * Interface permettant la configuration de l'accès à un annuaire LDAP
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2020-01-28 11:10$
 * @author    Laurent Delineau & JeromeB & Yan Naessens & Daniel Antelme
 * @copyright Copyright 2003-2020 Team DEVOME - JeromeB
 * @link      http://www.gnu.org/licenses/licenses.html
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 */
// cette page reste à internationaliser (et à restructurer ?)
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
	die("Erreur chargement settings");
$valid = isset($_POST["valid"]) ? $_POST["valid"] : 'no';
$etape = isset($_POST["etape"]) ? $_POST["etape"] : '0';
$adresse = isset($_POST["adresse"]) ? $_POST["adresse"] : NULL;
$port = isset($_POST["port"]) ? $_POST["port"] : NULL;
$login_ldap = isset($_POST["login_ldap"]) ? $_POST["login_ldap"] : NULL;
$pwd_ldap = isset($_POST["pwd_ldap"]) ? unslashes($_POST["pwd_ldap"]) : NULL;

if (isset($_POST["use_tls"]))
{
	if ($_POST["use_tls"] == 'y')
		$use_tls = TRUE;
	else $use_tls = FALSE;
}
else
	$use_tls = FALSE;
$base_ldap = isset($_POST["base_ldap"]) ? $_POST["base_ldap"] : NULL;
$base_ldap_autre = isset($_POST["base_ldap_autre"]) ? $_POST["base_ldap_autre"] : NULL;
$ldap_filter = isset($_POST["ldap_filter"]) ? $_POST["ldap_filter"] : NULL;

$ldap_group_member_attr = isset($_POST["ldap_group_member_attr"]) ? $_POST["ldap_group_member_attr"] : NULL;
$ldap_group_base = isset($_POST["ldap_group_base"]) ? $_POST["ldap_group_base"] : NULL;
$ldap_group_filter = isset($_POST["ldap_group_filter"]) ? $_POST["ldap_group_filter"] : NULL;
$ldap_group_user_field = isset($_POST["ldap_group_user_field"]) ? $_POST["ldap_group_user_field"] : NULL;

$titre_ldap = "Configuration de l'authentification LDAP";
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
			echo encode_message_utf8("Erreur lors de l'enregistrement de ldap_statut !<br />");
		$grrSettings['ldap_statut'] = $_POST['ldap_statut'];
	}
	if (isset($_POST['Valider1']))
	{
		if (!isset($_POST['ConvertLdapUtf8toIso']))
			$ConvertLdapUtf8toIso = "n";
		else
			$ConvertLdapUtf8toIso = "y";
		if (!Settings::set("ConvertLdapUtf8toIso", $ConvertLdapUtf8toIso))
			echo "Erreur lors de l'enregistrement de ConvertLdapUtf8toIso !<br />";
		$grrSettings['ConvertLdapUtf8toIso'] = $ConvertLdapUtf8toIso;
		if (!isset($_POST['ActiveModeDiagnostic']))
			$ActiveModeDiagnostic = "n";
		else
			$ActiveModeDiagnostic = "y";
		if (!Settings::set("ActiveModeDiagnostic", $ActiveModeDiagnostic))
			echo "Erreur lors de l'enregistrement de ActiveModeDiagnostic !<br />";
		$grrSettings['ActiveModeDiagnostic'] = $ActiveModeDiagnostic;
		if (!Settings::set("ldap_champ_recherche", $_POST['ldap_champ_recherche']))
			echo "Erreur lors de l'enregistrement de ldap_champ_recherche !<br />";
		$grrSettings['ldap_champ_recherche'] = $_POST['ldap_champ_recherche'];
		if ($_POST['ldap_champ_nom'] == '')
			$_POST['ldap_champ_nom'] = "sn";
		if (!Settings::set("ldap_champ_nom", $_POST['ldap_champ_nom']))
			echo "Erreur lors de l'enregistrement de ldap_champ_nom !<br />";
		$grrSettings['ldap_champ_nom'] = $_POST['ldap_champ_nom'];
		if ($_POST['ldap_champ_prenom'] == '')
			$_POST['ldap_champ_prenom'] = "sn";
		if (!Settings::set("ldap_champ_prenom", $_POST['ldap_champ_prenom']))
			echo "Erreur lors de l'enregistrement de ldap_champ_prenom !<br />";
		$grrSettings['ldap_champ_prenom'] = $_POST['ldap_champ_prenom'];
		if ($_POST['ldap_champ_email'] == '')
			$_POST['ldap_champ_email'] = "sn";
		if (!Settings::set("ldap_champ_email", $_POST['ldap_champ_email']))
			echo "Erreur lors de l'enregistrement de ldap_champ_email !<br />";
		$grrSettings['ldap_champ_email'] = $_POST['ldap_champ_email'];
		if (!Settings::set("se3_liste_groupes_autorises", $_POST['se3_liste_groupes_autorises']))
			echo "Erreur lors de l'enregistrement de se3_liste_groupes_autorises !<br />";
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
			<link rel="stylesheet" href="style.css" type="text/css">
			<title> grr </title>
			<link rel="shortcut icon" href="./favicon.ico">
			</head>
			<body>';
    echo "<h2>Configuration de l'accès à LDAP</h2>";
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
$back = '';
if (isset($_SERVER['HTTP_REFERER']))
    $back = htmlspecialchars($_SERVER['HTTP_REFERER']);
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
    echo "<h2>".$titre_ldap."</h2>\n";
    echo "<h2>".encode_message_utf8("Enregistrement de la configuration.")."</h2>\n";
    if (!$base_ldap)
        $base_ldap = $base_ldap_autre;
    $ds = grr_connect_ldap($adresse,$port,$login_ldap,$pwd_ldap,$use_tls);
    // On verifie le chemin fourni
    $result = grr_ldap_search_user($ds, $base_ldap, "objectClass", "*",$ldap_filter,"y");
    if ($result == "error_1")
    {
        if ($ldap_filter == "")
            echo "<p>".encode_message_utf8("<b>Problème</b> : Le chemin que vous avez choisi <b>ne semble pas valide</b>.</p><br />");
        else
            echo "<p>".encode_message_utf8("<b>Problème</b> : Le chemin et/ou le filtre additionnel que vous avez choisi <b>ne semblent pas valides</b>.</p><br />");
    }
    else if ($result == "error_2")
    {
        if ($ldap_filter == "")
            echo "<p>".encode_message_utf8("<b>Problème</b> : Le chemin que vous avez choisi semble valide mais la recherche sur ce chemin ne renvoie aucun résultat.</p><br />");
        else
            echo "<p>".encode_message_utf8("<b>Problème</b> : Le chemin et le filtre additionnel que vous avez choisi semblent valides  mais la recherche sur ce chemin ne renvoie aucun résultat.</p><br />");
    }
    // Le cas "error_3" n'est pas analysé car on accepte les  cas où il y a plusieurs entrées dans l'annuaire à l'issue de la recherche
    $erreur = '';
    $nom_fic = "../include/config_ldap.inc.php";
    if (@file_exists($nom_fic))
    {
        unlink($nom_fic);
        if (@file_exists($nom_fic))
            $erreur = "Impossible de supprimer le fichier \"".$nom_fic."\" existant.<br />Vous devez modifier les permissions sur ce fichier puis recharger cette page.";
    }
    if ($erreur == '')
    {
        $f = @fopen($nom_fic, "wb");
        if (!$f)
        {
            $erreur = "Impossible de créer le fichier \"".$nom_fic."\".";
            if (@file_exists($nom_fic.".ori"))
                $erreur .= "<br />Vous pouvez renommer manuellement le fichier \"".$nom_fic.".ori\" en \"".$nom_fic."\", et lui donner les droits suffisants.";
            else
                $erreur .= "<br />Vous devez modifier les droits sur le répertoire include.";
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
        echo "<p>".encode_message_utf8("<b>Les données concernant l'accès à l'annuaire LDAP sont maintenant enregistrées dans le fichier \"".$nom_fic."\".</b></p>");
    else
        echo encode_message_utf8("<p>".$erreur."</p>");
    if ($erreur == '')
    {
        echo "<form action=\"admin_config_ldap.php\" method=\"post\">\n";
        echo "<div><input type=\"hidden\" name=\"etape\" value=\"0\" />\n";
        echo "<input type=\"hidden\" name=\"valid\" value=\"$valid\" />\n";
        echo "<div class=\"center\"><input type=\"submit\" name=\"Valider\" value=\"Terminer\" /></div>\n";
        echo "</div></form>";
    }
}
else if ($etape == 2)
{
    echo "<h2>".$titre_ldap."</h2>\n";
    echo "<h2>".encode_message_utf8("Connexion à l'annuaire LDAP.")."</h2>\n";
    // Connexion à l'annuaire
    $ds = grr_connect_ldap($adresse,$port,$login_ldap,$pwd_ldap,$use_tls);
    if ($ds)
        $connexion_ok = 'yes';
    else
        $connexion_ok = 'no';
    if ($connexion_ok == 'yes')
    {
        echo "<p>".encode_message_utf8("<b>La connexion LDAP a réussi.</b></p>\n");
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
            echo encode_message_utf8("<p>Sélectionnez ci-dessous le chemin d'accès dans l'annuaire :</p>");
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
            echo "<br />Ou bien \n";
        }
        echo "<br /><input name=\"base_ldap\" value=\"\" type='radio' id=\"autre\"";
        if (!$checked)
        {
            echo " checked=\"checked\"";
            $checked = true;
        }
        echo " />\n";
        echo "<label for=\"autre\">".encode_message_utf8("Précisez le chemin : ")."</label>\n ";
        if (isset($_POST["ldap_base"]))
            $ldap_base = $_POST["ldap_base"];
        else
            $ldap_base ="";
        if (isset($_POST["ldap_filter"]))
            $ldap_filter = $_POST["ldap_filter"];
        else $ldap_filter ="";
        echo "<input type=\"text\" name=\"base_ldap_autre\" value=\"$ldap_base\" size=\"40\" />\n";
        echo "<br /><br />".encode_message_utf8("Filtre LDAP supplémentaire (facultatif) :\n");
        echo "<br /><input type=\"text\" name=\"ldap_filter\" value=\"$ldap_filter\" size=\"50\" />\n";
        echo "<br /><br />\n";
        echo encode_message_utf8("<b>Remarque : pour le moment, aucune modification n'a été apportée au fichier de configuration \"config_ldap.inc.php\".</b><br />Pour enregistrer les informations, cliquez sur le bouton \"Enregistrer les informations\".<br /><br />\n");
        echo "<input type=\"hidden\" name=\"etape\" value=\"3\" />\n";
        echo "<input type=\"hidden\" name=\"adresse\" value=\"$adresse\" />\n";
        echo "<input type=\"hidden\" name=\"port\" value=\"$port\" />\n";
        echo "<input type=\"hidden\" name=\"login_ldap\" value=\"$login_ldap\" />\n";
        echo "<input type=\"hidden\" name=\"pwd_ldap\" value=\"$pwd_ldap\" />\n";
        echo "<input type=\"hidden\" name=\"valid\" value=\"$valid\" />\n";
        if ($use_tls)
            echo "<input type=\"hidden\" name=\"use_tls\" value=\"y\" />\n";
        echo "<div class=\"center\"><input type=\"submit\" name=\"Valider\" value=\"Enregistrer les informations\" /></div>\n";
        echo "</div></form>";
    }
    else
    {
        echo encode_message_utf8("<b>La connexion au serveur LDAP a échoué.</b><br />\n");
        echo encode_message_utf8("Revenez à la page précédente et vérifiez les informations fournies.");
        echo "<form method=\"post\" action=\"admin_config_ldap.php\">\n";
        echo "<div>\n<input type=\"hidden\" name=\"etape\" value=\"1\" />\n";
        echo "<input type=\"hidden\" name=\"ldap_adresse\" value=\"$adresse\" />\n";
        echo "<input type=\"hidden\" name=\"ldap_port\" value=\"$port\" />\n";
        echo "<input type=\"hidden\" name=\"ldap_login\" value=\"$login_ldap\" />\n";
        if ($use_tls)
            echo "<input type=\"hidden\" name=\"use_tls\" value=\"y\" />\n";
        echo "<input type=\"submit\" name=\"valider\" value=\"".encode_message_utf8("Page précédente")."\" />\n";
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
    echo encode_message_utf8("<h2>".$titre_ldap."</h2>\n");
    echo "<h2>".encode_message_utf8("Informations de connexion à l'annuaire LDAP.")."</h2>\n";
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
    echo encode_message_utf8("<h3>URI de l'annuaire</h3><div>Laissez «ldap://localhost» si l'annuaire est installé sur la même machine que GRR. Sinon, indiquez l'adresse du serveur.<br />Utilisez le protocole ldaps:// si c'est votre cas.<br/>");
    echo "<input type=\"text\" name=\"adresse\" value=\"".$ldap_adresse."\" size=\"20\" />";
    echo encode_message_utf8("<h3>Numéro de port de l'annuaire</h3>Dans le doute, laissez la valeur par défaut : 389<br />(3268 pour serveur de catalogues global AD, 636 pour pour ldaps (LDAP over SSH)<br />");
    echo "<input type='text' name='port' value=\"$ldap_port\" size=\"20\" /></div>";
    echo encode_message_utf8("<h3>Type d'accès</h3><div>Si le serveur LDAP n'accepte pas d'accès anonyme, veuillez préciser un identifiant (par exemple « cn=jean, o=lycée, c=fr »). Dans le doute, laissez les champs suivants vides pour un accès anonyme.<br /><b>Identifiant :</b><br />");
    echo "<input type=\"text\" name=\"login_ldap\" value=\"".$ldap_login."\" size=\"40\" /><br />";
    echo "<b>Mot de passe :</b><br />";
    echo encode_message_utf8("Remarque : des problèmes liés à un mot de passe contenant un ou plusieurs caractères accentués ont déjà été constatés.<br />");
    echo "<input type=\"password\" name=\"pwd_ldap\" value=\"".$ldap_pwd."\" size=\"40\" /><br /></div>\n";
    echo "<h3>Utiliser TLS :</h3>\n";
    echo "<div>\n<input type=\"radio\" name=\"use_tls\" value=\"y\" ";
    if ($use_tls)
        echo " checked=\"checked\" ";
    echo "/> Oui\n";
    echo "<input type=\"radio\" name=\"use_tls\" value=\"n\" ";
    if (!($use_tls))
        echo " checked=\"checked\" ";
    echo "/> Non\n";
    if (isset($ldap_filter))
        echo "<input type=\"hidden\" name=\"ldap_filter\" value=\"$ldap_filter\" />";
    if (isset($ldap_base))
        echo "<input type=\"hidden\" name=\"ldap_base\" value=\"$ldap_base\" />";
    //TODO: Ajouter les informations pour les groupes
    echo encode_message_utf8("<br /><br /><b>Remarque : pour le moment, aucune modification n'a été apportée au fichier de configuration \"config_ldap.inc.php\".</b><br />
        Les informations ne seront enregistrées qu'à la fin de la procédure de configuration.</div>");


    echo "<div style=\"text-align:center;\"><input type=\"submit\" value=\"Suivant\" /></div>";
    echo "</form>";

}
else if ($etape == 0)
{
    if (!(function_exists("ldap_connect")))
    {
		echo "<div class='col-sm-9 col-xs-12'>";
        echo encode_message_utf8("<h2>".$titre_ldap."</h2>\n");
        echo encode_message_utf8("<p class=\"avertissement\"><b>Attention </b> : les fonctions liées à l'authentification <b>LDAP</b> ne sont pas activées sur votre serveur PHP.
            <br />La configuration LDAP est donc actuellement impossible.</p></div></section></body></html>");
        die();
    }
    echo encode_message_utf8("<h2>".$titre_ldap."</h2>\n");
    echo "<p>".encode_message_utf8("Si vous avez accès à un annuaire <b>LDAP</b>, vous pouvez configurer GRR afin que cet annuaire soit utilisé pour importer automatiquement des utilisateurs.")."</p>";
    echo "<form action=\"admin_config_ldap.php\" method=\"post\">\n";
    echo "<div>\n<input type=\"hidden\" name=\"etape\" value=\"0\" />\n";
    echo "<input type=\"hidden\" name=\"valid\" value=\"$valid\" />\n";
    echo "<input type=\"hidden\" name=\"reg_ldap_statut\" value=\"yes\" /></div>\n";
    if (Settings::get("ldap_statut") != '')
    {
        echo encode_message_utf8("<h3>L'authentification LDAP est activée.</h3>\n");
        echo encode_message_utf8("<h3>Statut par défaut des utilisateurs importés</h3>\n");
        echo "<div>".encode_message_utf8("Choisissez le statut qui sera attribué aux personnes présentes dans l'annuaire LDAP lorsqu'elles se connectent pour la première fois.	Vous pourrez par la suite modifier cette valeur pour chaque utilisateur.<br />");
        echo "<input type=\"radio\" name=\"ldap_statut\" value=\"visiteur\" ";
        if (Settings::get("ldap_statut") == 'visiteur')
            echo " checked=\"checked\" ";
        echo "/>Visiteur<br />";
        echo "<input type=\"radio\" name=\"ldap_statut\" value=\"utilisateur\" ";
        if (Settings::get("ldap_statut") == 'utilisateur')
            echo " checked=\"checked\" ";
        echo "/>Usager<br />";
        echo "Ou bien <br />";
        echo "<input type=\"radio\" name=\"ldap_statut\" value=\"no_ldap\" />".encode_message_utf8("Désactiver l'authentification LDAP")."<br />";
        echo "<br />";
        echo "<input type=\"checkbox\" name=\"ConvertLdapUtf8toIso\" value=\"y\" ";
        if (Settings::get("ConvertLdapUtf8toIso") == "y")
            echo " checked=\"checked\"";
        echo " />";
        echo encode_message_utf8("Les données (noms, prénom...) sont stockées en UTF-8 dans l'annuaire (configuration par défaut)");
        echo "<br />";
        echo "<input type=\"checkbox\" name=\"ActiveModeDiagnostic\" value=\"y\" ";
        if (Settings::get("ActiveModeDiagnostic") == "y")
            echo " checked=\"checked\"";
        echo " />";
        echo encode_message_utf8("Activer le mode \"diagnostic\" en cas d'erreur de connexion, les messages renvoyés par GRR sont plus explicites. De cette façon, il peut être plus facile de déterminer la cause du problème.");
        echo "<br /><br />";
        if (Settings::get("ldap_champ_recherche") == '')
            echo "<span class=\"avertissement\">";
        echo encode_message_utf8("<b>Attribut utilisé pour la recherche dans l'annuaire</b> :");
        echo "<input class=\"form-control\" type=\"text\" name=\"ldap_champ_recherche\" value=\"".htmlentities( Settings::get("ldap_champ_recherche"))."\" size=\"50\" />";
        if (Settings::get("ldap_champ_recherche") == '')
            echo "<br />Le champ ci-dessous ne doit pas être vide.</span>";
        echo "<br />";
        echo encode_message_utf8("La valeur à indiquer ci-dessus varie selon le type d'annuaire utilisé et selon sa configuration
            <br /><span class='small'>Exemples de champs généralement utilisés pour les annuaires ldap : \"uid\", \"cn\", \"sn\".
            <br />Exemples de champs généralement utilisés pour les Active Directory : \"samaccountname\", \"userprincipalname\".
            <br />Même si cela n'est pas conseillé, vous pouvez indiquer plusieurs attributs séparés par le caractère | (exemple : uid|sn|cn).</span>
            ");
        echo "<br /><br /><b>Liaisons GRR/LDAP</b>";
        echo "<table><tr>";
        echo "<td>Nom de famille : </td>";
        echo "<td><input class=\"form-control\" type=\"text\" name=\"ldap_champ_nom\" value=\"".htmlentities( Settings::get("ldap_champ_nom"))."\" size=\"20\" /></td>";
        echo "<td>".encode_message_utf8("Prénom")." : </td>";
        echo "<td><input class=\"form-control\" type=\"text\" name=\"ldap_champ_prenom\" value=\"".htmlentities( Settings::get("ldap_champ_prenom"))."\" size=\"20\" /></td>";
        echo "<td>Email : </td>";
        echo "<td><input class=\"form-control\" type=\"text\" name=\"ldap_champ_email\" value=\"".htmlentities( Settings::get("ldap_champ_email"))."\" size=\"20\" /></td>";
        echo "</tr></table>";
        echo encode_message_utf8("<br /><br /><b>Cas particulier des serveur SE3</b> : <span class=\"small\">dans le champs ci-dessous, vous pouvez préciser la liste des groupes SE3 autorisés à accéder à GRR.
            Si le champ est laissé vide, il n'y a pas de restrictions.
            Dans le cas contraire, seuls les utilisateurs appartenant à au moins l'un des groupes listés seront autorisés à accéder à GRR.
            Ecrivez les groupes en les séparant par un point-vigule, par exemple : \"Profs;Administratifs\".
            Seuls les groupes de type \"posixGroup\" sont supportés (les groupes de type \"groupOfNames\" ne sont pas supportés).</span>");
        echo "<br />\n<input class=\"form-control\" type=\"text\" name=\"se3_liste_groupes_autorises\" value=\"".htmlentities( Settings::get("se3_liste_groupes_autorises"))."\" size=\"50\" />\n";
        echo "</div>\n";
        echo "<div class=\"center\">\n<input class=\"btn btn-primary\" type=\"submit\" name=\"Valider1\" value=\"Valider\" />\n</div>\n";
    }
    else
    {
        echo encode_message_utf8("<h3>L'authentification LDAP n'est pas activée.</h3>\n");
        echo "<div>".encode_message_utf8("<b>L'authentification LDAP est donc pour le moment impossible</b>. Activez l'authentification LDAP en choisissant le statut qui sera attribué aux personnes présentes
            dans l'annuaire LDAP lorsqu'elles se connectent pour la première fois.
            Vous pourrez par la suite modifier cette valeur pour chaque utilisateur.<br />");
        echo "<input type=\"radio\" name=\"ldap_statut\" value=\"visiteur\" />Visiteur<br />\n";
        echo "<input type=\"radio\" name=\"ldap_statut\" value=\"utilisateur\" />Usager<br />\n";
        echo "<input type=\"radio\" name=\"ldap_statut\" value=\"no_ldap\" checked=\"checked\" />Ne pas activer<br /></div>\n";
        echo "<div class=\"center\"><input type=\"submit\" name=\"Valider2\" value=\"Valider\"  /></div>\n";
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
            $failed = "Echec";
            echo "<hr />\n";
            $ds = grr_connect_ldap($ldap_adresse,$ldap_port,$ldap_login,$ldap_pwd,$use_tls,'y');
            if ($ds == "error_1")
            {
                returnmsg('danger','Test de connexion à l\'annuaire : ', $failed, 'Impossible d\'utiliser la norme LDAP V3');
            }
            else if ($ds == "error_2")
            {
                returnmsg('danger' ,'Test de connexion à l\'annuaire : ', $failed, 'Impossible d\'utiliser TLS');
            }
            else if ($ds == "error_3")
            {
                returnmsg('danger' ,'Test de connexion à l\'annuaire : ', $failed, 'Connexion établie mais l\'identification auprès du serveur a échoué');
            }
            else if ($ds == "error_4")
            {
                returnmsg('danger' ,'Test de connexion à l\'annuaire : ', $failed, 'Impossible d\'établir la connexion');
            }
            else if (!$ds)
                echo encode_message_utf8($failed)."</h3></div>";
            else
            {
                returnmsg('success','Test de connexion à l\'annuaire : ', $ok, '');
                //echo encode_message_utf8("<h3>Test de recherche sur l'annuaire avec le chemin spécifié : ");
                $result = "";
                $result = grr_ldap_search_user($ds, $ldap_base, "objectClass", "*",$ldap_filter,"y");
                if ($result == "error_1")
                {

                    $test_chemin = 'failed';
                    if ($ldap_filter == "")
                        returnmsg('danger','Test de recherche sur l\'annuaire avec le chemin spécifié : ', $failed, '<b>Problème</b> : Le chemin que vous avez choisi <b>ne semble pas valide</b>.');
                    else
                        returnmsg('danger','Test de recherche sur l\'annuaire avec le chemin spécifié : ', $failed, '<b>Problème</b> : Le chemin et/ou le filtre additionnel que vous avez choisi <b>ne semblent pas valides</b>.');
                }
                else if ($result == "error_2")
                {
                    $test_chemin = 'failed';
                    if ($ldap_filter == "")
                        returnmsg('danger','Test de recherche sur l\'annuaire avec le chemin spécifié : ', $failed, '<b>Problème</b> : Le chemin que vous avez choisi semble valide mais la recherche sur ce chemin ne renvoie aucun résultat.');
                    else
                        returnmsg('danger','Test de recherche sur l\'annuaire avec le chemin spécifié : ', $failed, '<b>Problème</b> : Le chemin et le filtre additionnel que vous avez choisi semblent valides  mais la recherche sur ce chemin ne renvoie aucun résultat.');
                }
                else
                    returnmsg('success','Test de recherche sur l\'annuaire avec le chemin spécifié : ', $ok, '');
            }
        }
    }
    echo "<hr />";
    if (@file_exists("../include/config_ldap.inc.php"))
    {
        echo encode_message_utf8("<h3>Configuration actuelle</h3> (Informations contenues dans le fichier \"config_ldap.inc.php\") :<br /><ul>");
        echo encode_message_utf8("<li>Adresse de l'annuaire LDAP <b>: ".$ldap_adresse."</b></li>");
        echo encode_message_utf8("<li>Port utilisé : <b>".$ldap_port."</b></li>");
        if ($test_chemin == 'failed')
            echo encode_message_utf8("<li><div class=\"alert alert-danger\" role=\"alert\">Chemin d'accès dans l'annuaire : <b> ".$ldap_base."</b></div></li>");
        else
            echo encode_message_utf8("<li>Chemin d'accès dans l'annuaire : <b> ".$ldap_base."</b></li>");
        if ($ldap_filter!="")
            $ldap_filter_text = $ldap_filter;
        else
            $ldap_filter_text = "non";
        if (($test_chemin == 'failed') && ($ldap_filter!=""))
            echo encode_message_utf8("<li><div class=\"alert alert-danger\" role=\"alert\">Filtre LDAP supplémentaire : <b> ".$ldap_filter_text."</b></div></li>");
        else
            echo encode_message_utf8("<li>Filtre LDAP supplémentaire : <b> ".$ldap_filter_text."</b></li>");
        if ($ldap_login) {
            echo encode_message_utf8("<li>Compte pour l'accès : <br />");
            echo "Identifiant : <b>".$ldap_login."</b><br />";
            $ldap_pwd_hide = "";
            for ($i=0;$i<strlen($ldap_pwd);$i++)
                $ldap_pwd_hide .= "*";
            echo "Mot de passe : <b>".$ldap_pwd_hide."</b></li>";
        }
        else
            echo encode_message_utf8("<li>Accès anonyme.</li>");
        if ($use_tls)
            $use_tls_text = "oui";
        else
            $use_tls_text = "non";
        echo encode_message_utf8("<li>Utiliser TLS : <b>".$use_tls_text."</b></li>");
        echo encode_message_utf8("</ul>Vous pouvez procéder à une nouvelle configuration LDAP.<br />");
    }
    else
        echo encode_message_utf8("<h3>L'accès à l'annuaire LDAP n'est pas configuré.</h3>\n<b>L'authentification LDAP est donc pour le moment impossible.</b>\n");
    echo "<form action=\"admin_config_ldap.php\" method=\"post\">\n";
    echo "<div><input type=\"hidden\" name=\"etape\" value=\"1\" />\n";
    echo "<input type=\"hidden\" name=\"valid\" value=\"$valid\" /></div>\n";
    echo "<div style=\"text-align:center;\"><input class=\"btn btn-primary\" type=\"submit\" value=\"Configurer LDAP\" /></div></form>\n";
}
if ($valid == 'no') echo "</div>";
end_page();
?>
