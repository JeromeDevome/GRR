<?php
/**
 * admin_config_imap.php
 * Interface permettant l'activation de la configuration de l'authentification pop/imap  
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2017-12-16 14:00$
 * @author    Laurent Delineau & JeromeB & Gilles Martin
 * @copyright Copyright 2003-2018 Team DEVOME - JeromeB
 * @link      http://www.gnu.org/licenses/licenses.html
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 */
include "../include/admin.inc.php";
$grr_script_name = "admin_config_imap.php";
require_once("../include/settings.class.php");
$valid = isset($_POST["valid"]) ? $_POST["valid"] : 'no';
$etape = isset($_POST["etape"]) ? $_POST["etape"] : '0';
$imap_domaine=isset($_POST["imap_domaine"]) ? $_POST["imap_domaine"] : "";
$imap_adresse = isset($_POST["imap_adresse"]) ? $_POST["imap_adresse"] : "";
$imap_port = isset($_POST["imap_port"]) ? $_POST["imap_port"] : "";
$imap_type = isset($_POST["server_type"]) ? $_POST["server_type"]:"";
$imap_ssl  = isset($_POST["server_ssl"]) ? $_POST["server_ssl"]:"";
$imap_cert = isset($_POST["server_cert"]) ? $_POST["server_cert"]:"";
$imap_tls  = isset($_POST["server_tls"]) ? $_POST["server_tls"]:"";
$imap_string  = isset($_POST["imap_string"]) ? $_POST["imap_string"]:"";
$imap_login=isset($_POST["imap_login"]) ? $_POST["imap_login"]:"";
$imap_password=isset($_POST["imap_password"]) ? $_POST["imap_password"]:"";

if (isset($_POST['imap_statut']))
{
	if ($_POST['imap_statut'] == "no_imap")
	{
		$req = grr_sql_query("delete from ".TABLE_PREFIX."_setting where NAME = 'imap_statut'");
		$grrSettings['imap_statut'] = '';
	}
	else
	{
		if (!Settings::set("imap_statut", $_POST['imap_statut']))
			echo encode_message_utf8("Erreur lors de l'enregistrement de imap_statut !<br />");
		$grrSettings['imap_statut'] = $_POST['imap_statut'];
	}
}
$back = '';
if (isset($_SERVER['HTTP_REFERER']))
	$back = htmlspecialchars($_SERVER['HTTP_REFERER']);
if ((isset($sso_restrictions)) && ($sso_restrictions == true))
{
	showAccessDenied($back);
	exit();
}
if ((authGetUserLevel(getUserName(),-1) < 5) && ($valid != 'yes'))
{
	showAccessDenied($back);
	exit();
}
print_header("", "", "", $type="with_session");
// Affichage de la colonne de gauche
include "admin_col_gauche.php";
echo encode_message_utf8("<h2>Configuration de l'authentification IMAP/POP"."</h2>");
?>
<script type="text/javascript" src="../js/functions.js"></script>
<?php
if ($etape == 1)
{
	if (isset($_POST["Valider1"]))
	{
		$imap_domaine = $_POST["imap_domaine"];
		$imap_adresse = $_POST["imap_adresse"];
		$imap_port = $_POST["imap_port"];
		$imap_type = $_POST["server_type"];
		$imap_ssl = $_POST["server_ssl"];
		$imap_cert = $_POST["server_cert"];
		$imap_tls = $_POST["server_tls"];
		$erreur = '';
		$nom_fic = "include/config_imap.inc.php";
		if (@file_exists($nom_fic))
		{
			$f = @fopen($nom_fic, "w+");
			if (!$f)
				$erreur = "Le fichier \"".$nom_fic."\" n'est pas accessible en écriture.<br />Vous devez modifier les permissions sur ce fichier puis recharger cette page.";
		}
		else
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
			// On a ouvert un fichier config_imap.inc.php
			$conn = "<"."?php\n";
			$conn .= "# ligne suivante : Domaine utilise(necessaire pour le mail)\n";
			$conn .= "\$imap_domaine=\"$imap_domaine\";\n";
			$conn .= "# ligne suivante : Adresse du serveur IMAP/POP\n";
			$conn .= "\$imap_adresse=\"$imap_adresse\";\n";
			$conn .= "# ligne suivante : le port utilise\n";
			$conn .= "\$imap_port=\"$imap_port\";\n";
			$conn .= "# ligne suivante : IMAP ou POP\n";
			$conn .= "\$imap_type=\"$imap_type\";\n";
			$conn .= "# ligne suivante : SSL\n";
			$conn .= "\$imap_ssl=\"$imap_ssl\";\n";
			$conn .= "# ligne suivante : Certificat\n";
			$conn .= "\$imap_cert=\"$imap_cert\";\n";
			$conn .= "# ligne suivante : TLS\n";
			$conn .= "\$imap_tls=\"$imap_tls\";\n";
			$conn .= "?".">";
			@fputs($f, $conn);
			if (!@fclose($f))
				$erreur="Impossible d'enregistrer le fichier \"".$nom_fic."\".";
		}
		if ($erreur == '')
			echo encode_message_utf8("<b><span style=\"color:green;\">Les donnees concernant l'accès a l'annuaire IMAP/POP sont maintenant enregistrées dans le fichier \"".$nom_fic."\".</span></b>");
		else
			echo encode_message_utf8("<p><b><span style=\"color:red;\">".$erreur."</span></b></p>");
		if ($erreur == '')
		{
			echo "<form action=\"admin_config_imap.php\" method=\"post\"><div>";
			echo "<input type=\"hidden\" name=\"etape\" value=\"0\" />";
			echo "<input type=\"hidden\" name=\"valid\" value=\"$valid\" />";
			echo "<div style=\"text-align:center;\"><input type=\"submit\" name=\"Valider\" value=\"Terminer\" /></div>";
			echo "</div></form>";
		}
	}
	else if (@file_exists("../include/config_imap.inc.php"))
		include("../include/config_imap.inc.php");
	echo "<form action=\"admin_config_imap.php\" method=\"post\"><div>";
	echo "<input type=\"hidden\" name=\"valid\" value=\"$valid\" />";
	echo encode_message_utf8("<h3>Nom domaine IMAP/POP</h3>");
	echo encode_message_utf8("<p>(Utilisé pour compléter l'adresse email)</p>");
	echo "<input type=\"text\" name=\"imap_domaine\" value=\"$imap_domaine\" size=\"20\" />";
	echo encode_message_utf8("<h3>Adresse Serveur IMAP/POP</h3>");
	echo "<input type=\"text\" name=\"imap_adresse\" value=\"$imap_adresse\" size=\"20\" />";
	echo encode_message_utf8("<h3>Numero de port</h3>* IMAP, port par défaut : 143<br />* IMAP sécurisé, port par défaut 993<br />* POP3, port par défaut 110<br />* POP3 sécurisé, port par défaut<br/>");
	echo "<input type='text' name='imap_port' value=\"$imap_port\" size=\"20\" />";
	echo encode_message_utf8("<h3>Options de connexion:</h3>");
	// Imap ou pop
	echo "<select name=\"server_type\">";
	echo "<option value=\"/imap\" ";
	if ($imap_type == "/imap")
		echo "selected=\"selected\"";
	echo ">IMAP</option>";
	echo "<option value=\"/pop3\"";
	if ($imap_type == "/pop3")
		echo "selected=\"selected\"";
	echo ">POP</option>";
	echo "</select>";


	echo "<select name=\"server_ssl\">";
	echo " <option value=\"\" ";
	if ($imap_ssl == "")
		echo "selected=\"selected\"";
	echo ">Ne pas utiliser SSL</option>";
	echo "<option value=\"/ssl\"";
	if ($imap_ssl == "/ssl")
		echo "selected=\"selected\"";
	echo ">Utiliser SSL</option>";
	echo "</select>";
	echo "<select name=\"server_cert\" >";
	echo "<option value=\"\" ";
	if ($imap_cert == "")
		echo "selected=\"selected\"";
	echo ">(non précisé)</option>";
	echo "<option value=\"/novalidate-cert\" ";
	if ($imap_cert == "/novalidate-cert")
		echo "selected=\"selected\"";
	echo ">NO-VALIDATE-CERT</option>";
	echo "<option value=\"/validate-cert\" ";
	if ($imap_cert == "/validate-cert")
		echo "selected=\"selected\"";
	echo ">VALIDATE-CERT</option>";
	echo "</select>";
	echo "<select name=\"server_tls\">";
	echo "<option value=\"\" ";
	if ($imap_tls == "")
		echo "selected=\"selected\"";
	echo ">(non précisé)</option>";
	echo "<option value=\"/tls\" ";
	if ($imap_tls == "/tls")
		echo "selected=\"selected\"";
	echo ">TLS</option>";
	echo "<option value=\"/notls\" ";
	if ($imap_tls == "/notls")
		echo "selected=\"selected\"";
	echo ">NO-TLS</option>";
	echo "</select>";
	echo "<div style=\"text-align:center;\"><input type=\"submit\" name=\"Valider1\" value=\"Enregistrer\" /></div>";
	echo "<input type=\"hidden\" name=\"etape\" value=\"1\" />";
	echo "<input type=\"hidden\" name=\"valid\" value=\"$valid\" />";
	echo "</div></form>";
	echo "<h3>Remarques</h3>\n";
	echo "<ul><li><b>SSL</b> : utilise Secure Socket Layer pour crypter la session</li>\n";
	echo "<li><b>TLS</b> : force l'utilisation de start-TLS pour crypter la session et rejète les connexions aux serveurs qui ne le supporte pas.</li>\n";
	echo "<li><b>validate-cert</b> : valide les certificats depuis le serveur TLS/SSL (c'est le comportement par défaut)</li>\n";
	echo "<li><b>novalidate-cert</b> : ne pas valider les certificats depuis le serveur TLS/SSL, nécessaire si le serveur utilise des certificats auto-signés</li></ul>\n";

}
else if ($etape ==0)
{
	if (!(function_exists("imap_open")))
	{
		echo encode_message_utf8("<p class=\"avertissement\"><b>Attention </b> : les fonctions liées à l'authentification <b>IMAP/POP</b> ne sont pas activées sur votre serveur PHP.<br />La configuration IMAP/POP est donc actuellement impossible.</p></td></tr></table></body></html>");
		die();
	}
	if (Settings::get("imap_statut") != '')
	{
		echo "<form action=\"admin_config_imap.php\" method=\"post\"><div>";
		echo "<input type=\"hidden\" name=\"valid\" value=\"$valid\" />";
		echo encode_message_utf8("<h3>L'authentification IMAP/POP est activée.</h3>");
		echo encode_message_utf8("<h3>Statut par défaut des utilisateurs importés: </h3>");
		echo "<input type=\"radio\" name=\"imap_statut\" value=\"visiteur\" ";
		if (Settings::get("imap_statut") == 'visiteur')
			echo "checked=\"checked\" ";
		echo "/>Visiteur<br />";
		echo "<input type=\"radio\" name=\"imap_statut\" value=\"utilisateur\" ";
		if (Settings::get("imap_statut") == 'utilisateur')
			echo "checked=\"checked\" ";
		echo "/>Usager<br />";
		echo "Ou bien <br />";
		echo "<input type=\"radio\" name=\"imap_statut\" value=\"no_imap\" />".encode_message_utf8("Désactiver l'authentification IMAP/POP")."<br />";
		echo "<div style=\"text-align:center;\"><input type=\"submit\" value=\"Valider\" /></div></div></form>";
		if (@file_exists("../include/config_imap.inc.php"))
		{
			include("../include/config_imap.inc.php");
			if (($imap_adresse != '') && ($imap_port != ''))
			{
				echo "<hr/>";
				echo "<h3>Test de connexion:</h3>";
				echo "<form action=\"admin_config_imap.php\" method=\"post\"><div>";
				echo encode_message_utf8("Nom d'utilisateur : ");
				echo "<input type='text' name='imap_login' value=\"\" size=\"20\" /><br />";
				echo encode_message_utf8("Mot de passe de l'utilisateur : ");
				echo "<input type='password' name='imap_password' value=\"\" size=\"20\" />";
				echo "<div style=\"text-align:center;\"><input type=\"submit\" name=\"Valider2\" value=\"Test\"  /></div>";
				echo "<input type=\"hidden\" name=\"valid\" value=\"$valid\" />";
				echo "</div></form>";
				if ((isset($_POST['Valider2'])) && $_POST['Valider2'] == "Test")
					grr_connect_imap($imap_adresse,$imap_port,$imap_login,$imap_password,$imap_type,$imap_ssl,$imap_cert,$imap_tls,"diag");
			}
		}
		echo "<hr/>";
		if (@file_exists("../include/config_imap.inc.php"))
		{
			echo encode_message_utf8("<h3>Configuration actuelle</h3> (Informations contenues dans le fichier \"config_imap.inc.php\") :<br /><ul>");
			echo encode_message_utf8("<li>Nom de domaine IMAP/POP <b>: ".$imap_domaine."</b></li>");
			echo encode_message_utf8("<li>Adresse Serveur IMAP/POP <b>: ".$imap_adresse."</b></li>");
			echo encode_message_utf8("<li>Port utilise : <b>".$imap_port."</b></li>");
			if (($imap_type == "/imap") || ($imap_adresse != "" && $imap_port != "" && $imap_type == ""))
				$use_type = "IMAP";
			else if ($imap_type == "/pop3")
				$use_type = "POP3";
			else
				$use_type = "(non précisé)";
			echo encode_message_utf8("<li>type : <b>".$use_type."</b></li>");

			if ($imap_ssl == "/ssl")
				$use_ssl = "oui";
			else
				$use_ssl = "non";
			echo encode_message_utf8("<li>Utiliser SSL : <b>".$use_ssl."</b></li>");
			if ($imap_cert == "/validate-cert")
				$use_cert = "oui";
			else if ($imap_cert == "/novalidate-cert")
				$use_cert = "non";
			else
				$use_cert = "(non précisé)";
			echo encode_message_utf8("<li>Utiliser Certificat : <b>".$use_cert."</b></li>");

			if ($imap_tls == "/tls" && $imap_adresse != "" && $imap_port != "")
				$use_tls = "oui";
			else if ($imap_tls == "/notls" && $imap_adresse != "" && $imap_port != "")
				$use_tls = "non";
			else
				$use_tls = "(non précisé)";
			echo encode_message_utf8("<li>Utiliser TLS : <b>".$use_tls."</b></li>");
			echo encode_message_utf8("</ul>Vous pouvez proceder a une nouvelle configuration IMAP/POP.<br />");
		}
		else
			echo encode_message_utf8("<h3>L'accès a l'annuaire IMAP/POP n'est pas configuré.</h3><b>L'authentification IMAP/POP est donc pour le moment impossible.</b>");
		echo "<form action=\"admin_config_imap.php\" method=\"post\"><div>";
		echo "<input type=\"hidden\" name=\"etape\" value=\"1\" />";
		echo "<input type=\"hidden\" name=\"valid\" value=\"$valid\" />";
		echo "<div style=\"text-align:center;\"><input type=\"submit\" value=\"Configurer IMAP/POP\" /></div></div></form>";
	}
	else
	{
		echo encode_message_utf8("<h3>L'authentification IMAP/POP n'est pas activée.</h3>");
		echo encode_message_utf8("<b>L'authentification IMAP/POP est donc pour le moment impossible</b>. Activez l'authentification IMAP/POP en choisissant le statut qui sera attribué aux personnes présentes dans l'annuaire IMAP/POP lorsqu'elles se connectent pour la première fois. Vous pourrez par la suite modifier cette valeur pour chaque utilisateur.<br />");
		echo "<form action=\"admin_config_imap.php\" method=\"post\"><div>\n";
		echo "<input type=\"radio\" name=\"imap_statut\" value=\"visiteur\" />Visiteur<br />\n";
		echo "<input type=\"radio\" name=\"imap_statut\" value=\"utilisateur\" />Usager<br />\n";
		echo "<input type=\"radio\" name=\"imap_statut\" value=\"no_imap\" checked=\"checked\" />Ne pas activer<br />\n";
		echo "<input type=\"hidden\" name=\"valid\" value=\"$valid\" />\n";
		echo "<div style=\"text-align:center;\"><input type=\"submit\" name=\"Valider2\" value=\"Valider\"  /></div>\n";
		echo "</div></form>";
	}
}
// fin de l'affichage de la colonne de droite
if ($valid == 'no')
	echo "</td></tr></table>";
?>
</body>
</html>