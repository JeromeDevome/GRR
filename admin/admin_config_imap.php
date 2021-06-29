<?php
/**
 * admin_config_imap.php
 * Interface permettant l'activation de la configuration de l'authentification pop/imap  
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2021-03-13 11:55$
 * @author    Laurent Delineau & JeromeB & Gilles Martin & Yan Naessens
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
$grr_script_name = "admin_config_imap.php";
 
include "../include/admin.inc.php";

require_once("../include/settings.class.php");
$valid = isset($_POST["valid"]) ? $_POST["valid"] : 'no';
$valid = clean_input($valid);
$etape = isset($_POST["etape"]) ? $_POST["etape"] : '0';
$imap=array();
$imap['domaine']=isset($_POST["imap_domaine"]) ? $_POST["imap_domaine"] : "";
$imap['adresse'] = isset($_POST["imap_adresse"]) ? $_POST["imap_adresse"] : "";
$imap['port'] = isset($_POST["imap_port"]) ? $_POST["imap_port"] : "";
$imap['type'] = isset($_POST["server_type"]) ? $_POST["server_type"]:"";
$imap['ssl']  = isset($_POST["server_ssl"]) ? $_POST["server_ssl"]:"";
$imap['cert'] = isset($_POST["server_cert"]) ? $_POST["server_cert"]:"";
$imap['tls']  = isset($_POST["server_tls"]) ? $_POST["server_tls"]:"";
$imap['string']  = isset($_POST["imap_string"]) ? $_POST["imap_string"]:""; //utile ? YN le 19/03/2020
$imap['login']=isset($_POST["imap_login"]) ? $_POST["imap_login"]:""; //utile ? YN le 19/03/2020
$imap['password']=isset($_POST["imap_password"]) ? $_POST["imap_password"]:""; //utile ? YN le 19/03/2020
foreach ($imap as $key => $value){
    $imap[$key] = clean_input($value);
}

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
			echo encode_message_utf8($vocab['save_err']." imap_statut !<br />");
		$grrSettings['imap_statut'] = $_POST['imap_statut'];
	}
}
$back = (isset($_SERVER['HTTP_REFERER']))? htmlspecialchars_decode($_SERVER['HTTP_REFERER'], ENT_QUOTES) : "./admin_accueil.php" ;
if ((isset($imap_restrictions)) && ($imap_restrictions == true))
{
	showAccessDenied($back);
	exit();
}
if ((authGetUserLevel(getUserName(),-1) < 5) && ($valid != 'yes'))
{
	showAccessDenied($back);
	exit();
}
// début de la page HTML
start_page_w_header("", "", "", $type="with_session");
// Affichage de la colonne de gauche
include "admin_col_gauche2.php";
// colonne de droite
echo "<div class='col-md-9 col-sm-8 col-xs-12'>";
echo encode_message_utf8("<h2>".get_vocab('admin_config_imap_pop')."</h2>");
if ($etape == 1)
{
	if (isset($_POST["Valider1"]))
	{
		$imap['domaine'] = $_POST["imap_domaine"];
		$imap['adresse'] = $_POST["imap_adresse"];
		$imap['port'] = $_POST["imap_port"];
		$imap['type'] = $_POST["server_type"];
		$imap['ssl'] = $_POST["server_ssl"];
		$imap['cert'] = $_POST["server_cert"];
		$imap['tls'] = $_POST["server_tls"];
		$erreur = '';
		$nom_fic = "include/config_imap.inc.php";
		if (@file_exists($nom_fic))
		{
			$f = @fopen($nom_fic, "w+");
			if (!$f)
				$erreur = get_vocab('the_file').' "'.$nom_fic.'" '.get_vocab('not_writable_and_reload');
		}
		else
		{
			$f = @fopen($nom_fic, "wb");
			if (!$f)
			{
				$erreur = get_vocab('cant_create')." \"".$nom_fic."\".";
				if (@file_exists($nom_fic.".ori"))
					$erreur .= "<br />".get_vocab('renommer')." \"".$nom_fic.".ori\"".get_vocab('en')." \"".$nom_fic."\", ".get_vocab('donner_droits');
				else
					$erreur .= "<br />".get_vocab('modifier_droits');
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
				$erreur=get_vocab('cant_record')." \"".$nom_fic."\".";
		}
		if ($erreur == '')
			echo encode_message_utf8("<b><span style=\"color:green;\">".get_vocab('imap_record_success')." \"".$nom_fic."\".</span></b>");
		else
			echo encode_message_utf8("<p><b><span style=\"color:red;\">".$erreur."</span></b></p>");
		if ($erreur == '')
		{
			echo "<form action=\"admin_config_imap.php\" method=\"post\">";
			echo "<input type=\"hidden\" name=\"etape\" value=\"0\" />";
			echo "<input type=\"hidden\" name=\"valid\" value=\"$valid\" />";
			echo "<div class='center'><input type=\"submit\" name=\"Valider\" value=\"".get_vocab('Terminer')."\" /></div>";
			echo "</form>";
		}
	}
	else if (@file_exists("../include/config_imap.inc.php"))
		include("../include/config_imap.inc.php");
	echo "<form action=\"admin_config_imap.php\" method=\"post\"><div>";
	echo "<input type=\"hidden\" name=\"valid\" value=\"$valid\" />";
	echo encode_message_utf8("<h3>".get_vocab('nomDomaineImap')."</h3>");
	echo encode_message_utf8("<p>".get_vocab('nomDomaineImapComm')."</p>");
	echo "<input type=\"text\" name=\"imap_domaine\" value=\"$imap_domaine\" size=\"20\" />";
	echo encode_message_utf8("<h3>".get_vocab('adresseServeurImap')."</h3>");
	echo "<input type=\"text\" name=\"imap_adresse\" value=\"$imap_adresse\" size=\"20\" />";
	echo encode_message_utf8("<h3>".get_vocab('portNo')."</h3>* ".get_vocab('imapPort')."<br />* ".get_vocab('imapSPort')."<br />* ".get_vocab('pop3Port')."<br />* ".get_vocab('pop3SPort')."<br/>");
	echo "<input type='text' name='imap_port' value=\"$imap_port\" size=\"20\" />";
	echo encode_message_utf8("<h3>".get_vocab('optionsConnexion')."</h3>");
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
	echo ">".get_vocab('noSSL')."</option>";
	echo "<option value=\"/ssl\"";
	if ($imap_ssl == "/ssl")
		echo "selected=\"selected\"";
	echo ">".get_vocab('SSL')."</option>";
	echo "</select>";
	echo "<select name=\"server_cert\" >";
	echo "<option value=\"\" ";
	if ($imap_cert == "")
		echo "selected=\"selected\"";
	echo ">(".get_vocab('non_precise').")</option>";
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
	echo ">(".get_vocab('non_precise').")</option>";
	echo "<option value=\"/tls\" ";
	if ($imap_tls == "/tls")
		echo "selected=\"selected\"";
	echo ">TLS</option>";
	echo "<option value=\"/notls\" ";
	if ($imap_tls == "/notls")
		echo "selected=\"selected\"";
	echo ">NO-TLS</option>";
	echo "</select>";
	echo "<div style=\"text-align:center;\"><input type=\"submit\" name=\"Valider1\" value=\"".get_vocab('save')."\" /></div>";
	echo "<input type=\"hidden\" name=\"etape\" value=\"1\" />";
	echo "<input type=\"hidden\" name=\"valid\" value=\"$valid\" />";
	echo "</div></form>";
	echo "<h3>".get_vocab('Remarques')."</h3>\n";
	echo "<ul><li>".get_vocab('SSLexplain')."</li>\n";
	echo "<li>".get_vocab('TLSexplain')."</li>\n";
	echo "<li><b>validate-cert</b>".get_vocab('validate_cert_explain')."</li>\n";
	echo "<li><b>novalidate-cert</b>".get_vocab('no_validate_cert_explain')."</li></ul>\n";

}
else if ($etape ==0)
{
	if (!(function_exists("imap_open")))
	{
		echo encode_message_utf8("<p class=\"avertissement\">".get_vocab('noImapOnPhp').get_vocab('imapImpossible')."</p></div></section></body></html>");
		die();
	}
	if (Settings::get("imap_statut") != '')
	{
		echo "<form action=\"admin_config_imap.php\" method=\"post\"><div>";
		echo "<input type=\"hidden\" name=\"valid\" value=\"$valid\" />";
		echo encode_message_utf8("<h3>".get_vocab('imapActive')."</h3>");
		echo encode_message_utf8("<h3>".get_vocab('statutDefaut')."</h3>");
		echo "<input type=\"radio\" name=\"imap_statut\" value=\"visiteur\" ";
		if (Settings::get("imap_statut") == 'visiteur')
			echo "checked=\"checked\" ";
		echo "/>".get_vocab('statut_visitor')."<br />";
		echo "<input type=\"radio\" name=\"imap_statut\" value=\"utilisateur\" ";
		if (Settings::get("imap_statut") == 'utilisateur')
			echo "checked=\"checked\" ";
		echo "/>".get_vocab('statut_user')."<br />";
		echo get_vocab('Ou_bien')." <br />";
		echo "<input type=\"radio\" name=\"imap_statut\" value=\"no_imap\" />".encode_message_utf8(get_vocab('imapDesactiver'))."<br />";
		echo "<div style=\"text-align:center;\"><input type=\"submit\" value=\"".get_vocab('OK')."\" /></div></div></form>";
		if (@file_exists("../include/config_imap.inc.php"))
		{
			include("../include/config_imap.inc.php");
			if (($imap_adresse != '') && ($imap_port != ''))
			{
				echo "<hr/>";
				echo "<h3>".get_vocab('connexionTest')."</h3>";
				echo "<form action=\"admin_config_imap.php\" method=\"post\"><div>";
				echo encode_message_utf8(get_vocab('userName'));
				echo "<input type='text' name='imap_login' value=\"\" size=\"20\" /><br />";
				echo encode_message_utf8(get_vocab('userPwd'));
				echo "<input type='password' name='imap_password' value=\"\" size=\"20\" />";
				echo "<div style=\"text-align:center;\"><input type=\"submit\" name=\"Valider2\" value=\"".get_vocab('Test')."\"  /></div>";
				echo "<input type=\"hidden\" name=\"valid\" value=\"$valid\" />";
				echo "</div></form>";
				if ((isset($_POST['Valider2'])) && $_POST['Valider2'] == "Test")
					grr_connect_imap($imap_adresse,$imap_port,$imap_login,$imap_password,$imap_type,$imap_ssl,$imap_cert,$imap_tls,"diag");
			}
		}
		echo "<hr/>";
		if (@file_exists("../include/config_imap.inc.php"))
		{
			echo encode_message_utf8("<h3>".get_vocab('configurationActuelle')."</h3> (".get_vocab('infosinFile')." \"config_imap.inc.php\") :<br /><ul>");
			echo encode_message_utf8("<li>".get_vocab('nomDomaineImap')."<b>: ".$imap_domaine."</b></li>");
			echo encode_message_utf8("<li>".get_vocab('adresseServeurImap')."<b> : ".$imap_adresse."</b></li>");
			echo encode_message_utf8("<li>".get_vocab('portNo')."<b> : ".$imap_port."</b></li>");
			if (($imap_type == "/imap") || ($imap_adresse != "" && $imap['port'] != "" && $imap['type'] == ""))
				$use_type = "IMAP";
			else if ($imap_type == "/pop3")
				$use_type = "POP3";
			else
				$use_type = "(".get_vocab('non_precise').")";
			echo encode_message_utf8("<li>type : <b>".$use_type."</b></li>");

			if ($imap_ssl == "/ssl")
				$use_ssl = "oui";
			else
				$use_ssl = "non";
			echo encode_message_utf8("<li>".get_vocab('SSL')." : <b>".$use_ssl."</b></li>");
			if ($imap_cert == "/validate-cert")
				$use_cert = "oui";
			else if ($imap_cert == "/novalidate-cert")
				$use_cert = "non";
			else
				$use_cert = "(".get_vocab('non_precise').")";
			echo encode_message_utf8("<li>".get_vocab('useCert')."<b> : ".$use_cert."</b></li>");

			if ($imap_tls == "/tls" && $imap['adresse'] != "" && $imap['port'] != "")
				$use_tls = "oui";
			else if ($imap_tls == "/notls" && $imap['adresse'] != "" && $imap['port'] != "")
				$use_tls = "non";
			else
				$use_tls = "(non précisé)";
			echo encode_message_utf8("<li>".get_vocab('useTLS')."<b> : ".$use_tls."</b></li>");
			echo encode_message_utf8("</ul>".get_vocab('newImapConfig')."<br />");
		}
		else
			echo encode_message_utf8("<h3>".get_vocab('imapNonConfigure')."</h3><b>".get_vocab('imapImpossible')."</b>");
		echo "<form action=\"admin_config_imap.php\" method=\"post\"><div>";
		echo "<input type=\"hidden\" name=\"etape\" value=\"1\" />";
		echo "<input type=\"hidden\" name=\"valid\" value=\"$valid\" />";
		echo "<div style=\"text-align:center;\"><input type=\"submit\" value=\"".get_vocab('configureImap')."\" /></div></div></form>";
	}
	else
	{
		echo encode_message_utf8("<h3>".get_vocab('imapInactive')."</h3>");
		echo encode_message_utf8("<b>".get_vocab('imapImpossible')."</b> ".get_vocab('imapActiverExplain')."<br />");
		echo "<form action=\"admin_config_imap.php\" method=\"post\"><div>\n";
		echo "<input type=\"radio\" name=\"imap_statut\" value=\"visiteur\" />".get_vocab('statut_visitor')."<br />\n";
		echo "<input type=\"radio\" name=\"imap_statut\" value=\"utilisateur\" />".get_vocab('statut_user')."<br />\n";
		echo "<input type=\"radio\" name=\"imap_statut\" value=\"no_imap\" checked=\"checked\" />".get_vocab('nonActive')."<br />\n";
		echo "<input type=\"hidden\" name=\"valid\" value=\"$valid\" />\n";
		echo "<div style=\"text-align:center;\"><input type=\"submit\" name=\"Valider2\" value=\"".get_vocab('OK')."\"  /></div>\n";
		echo "</div></form>";
	}
}
// fin de l'affichage de la colonne de droite
if ($valid == 'no')
	echo "</div>";
// et de la page
end_page();
?>