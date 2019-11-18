<?php
/**
 * login.php
 * interface de connexion
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2019-10-10 10:20$
 * @author    Laurent Delineau & JeromeB & Yan Naessens
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
include "include/connect.inc.php";
include "include/config.inc.php";
include "include/misc.inc.php";
include "include/functions.inc.php";
include "include/$dbsys.inc.php";
// Settings
require_once("./include/settings.class.php");
//Chargement des valeurs de la table settingS
if (!Settings::load())
	die("Erreur chargement settings");
// Paramètres langage
include "include/language.inc.php";
// Session related functions
require_once("./include/session.inc.php");

if(Settings::get("redirection_https") == "yes"){
	if(!isset($_SERVER["HTTPS"]) || $_SERVER["HTTPS"] != "on")
	{
		header("Location: https://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"], true, 301);
		exit;
	}
}

// Vérification du numéro de version et renvoi automatique vers la page de mise à jour
if (verif_version())
{
	header("Location: ./installation/maj.php");
	exit();
}

// Si Token Installation n'est pas initialisé on le fait ici car s'est la 1ere page affiché 
if(Settings::get("tokeninstallation") == ""){
	Settings::set("tokeninstallation",  generationToken());
}

// User wants to be authentified
if (isset($_POST['login']) && isset($_POST['password']))
{
	// Détruit toutes les variables de session au cas où une session existait auparavant
	$_SESSION = array();
	$result = grr_opensession($_POST['login'], unslashes($_POST['password']));
	// On écrit les données de session et ferme la session
	session_write_close();
	if ($result=="2")
	{
		$message = get_vocab("echec_connexion_GRR");
		$message .= " ".get_vocab("wrong_pwd");
	}
	else if ($result == "3")
	{
		$message = get_vocab("echec_connexion_GRR");
		$message .= "<br />". get_vocab("importation_impossible");
	}
	else if ($result == "4")
	{
		//$message = get_vocab("importation_impossible");
		$message = get_vocab("echec_connexion_GRR");
		$message .= " ".get_vocab("causes_possibles");
		$message .= "<br />- ".get_vocab("wrong_pwd");
		$message .= "<br />- ". get_vocab("echec_authentification_ldap");
	}
	else if ($result == "5")
	{
		$message = get_vocab("echec_connexion_GRR");
		$message .= "<br />". get_vocab("connexion_a_grr_non_autorisee");
	}
	else if ($result == "6")
	{
		$message = get_vocab("echec_connexion_GRR");
		$message .= "<br />". get_vocab("connexion_a_grr_non_autorisee");
		$message .= "<br />". get_vocab("format identifiant incorrect");
	}
	else if ($result == "7")
	{
		$message = get_vocab("echec_connexion_GRR");
		$message .= "<br />". get_vocab("connexion_a_grr_non_autorisee");
		$message .= "<br />". get_vocab("echec_authentification_ldap");
		$message .= "<br />". get_vocab("ldap_chemin_invalide");
	}
	else if ($result == "8")
	{
		$message = get_vocab("echec_connexion_GRR");
		$message .= "<br />". get_vocab("connexion_a_grr_non_autorisee");
		$message .= "<br />". get_vocab("echec_authentification_ldap");
		$message .= "<br />". get_vocab("ldap_recherche_identifiant_aucun_resultats");
	}
	else if ($result == "9")
	{
		$message = get_vocab("echec_connexion_GRR");
		$message .= "<br />". get_vocab("connexion_a_grr_non_autorisee");
		$message .= "<br />". get_vocab("echec_authentification_ldap");
		$message .= "<br />". get_vocab("ldap_doublon_identifiant");
	}
	else if ($result == "10")
	{
		$message = get_vocab("echec_connexion_GRR");
		$message .= "<br />". get_vocab("connexion_a_grr_non_autorisee");
		$message .= "<br />". get_vocab("echec_authentification_imap");
	}
	else if ($result == "11")
	{
		$message = get_vocab("echec_connexion_GRR");
		$message .= "<br />". get_vocab("connexion_a_grr_ip");
	}
	else if ($result == "12")
	{
		header("Location: ./changepwd.php");
	}
	else // la session est ouverte
	{
        // si c'est un administrateur qui se connecte, on efface les données anciennes du journal
        nettoieLogConnexion($nbMaxJoursLogConnexion);
		if (isset($_POST['url']))
		{
			$url=rawurldecode($_POST['url']);
			header("Location: ".$url);
			die();
		}
		else
		{
			header("Location: ./".htmlspecialchars_decode(page_accueil())."");
			die();
		}
	}
}
// Dans le cas d'une démo, on met à jour la base une fois par jour.
MajMysqlModeDemo();
//si on a interdit l'acces a la page login
if ((Settings::get("Url_cacher_page_login") != "") && ((!isset($sso_super_admin)) || ($sso_super_admin == false)) && (!isset($_GET["local"])))
	header("Location: ./index.php");
echo begin_page(get_vocab("mrbs").get_vocab("deux_points").Settings::get("company"),"no_session");
?>
<script type="text/javascript" src="js/functions.js" ></script>
<div class="center">
	<?php
	$nom_picture = "./images/".Settings::get("logo");
	if ((Settings::get("logo") != '') && (@file_exists($nom_picture)))
		echo "<a href=\"javascript:history.back()\"><img src=\"".$nom_picture."\" alt=\"logo\" /></a>\n";"";
	?>
	<h1>
		<?php
		echo Settings::get("title_home_page");
		?>
	</h1>
	<h2>
		<?php
		echo Settings::get("company");
		?>
	</h2>
	<br />
	<p>
		<?php echo Settings::get("message_home_page");
		if ((Settings::get("disable_login")) == 'yes')
			echo "<br /><br /><span class='avertissement'>".get_vocab("msg_login3")."</span>";
		?>
	</p>
	<form action="login.php" method='post' style="width: 100%; margin-top: 24px; margin-bottom: 48px;">
		<?php
		if ((isset($message)) && (Settings::get("disable_login")) != 'yes')
			echo("<p><span class='avertissement'>" . $message . "</span></p>");
		if ((Settings::get('sso_statut') == 'cas_visiteur') || (Settings::get('sso_statut') == 'cas_utilisateur'))
		{
			echo "<p><span style=\"font-size:1.4em\"><a href=\"./index.php\">".get_vocab("authentification_CAS")."</a></span></p>";
			echo "<p><b>".get_vocab("authentification_locale")."</b></p>";
		}
		if ((Settings::get('sso_statut') == 'lemon_visiteur') || (Settings::get('sso_statut') == 'lemon_utilisateur'))
		{
			echo "<p><span style=\"font-size:1.4em\"><a href=\"./index.php\">".get_vocab("authentification_lemon")."</a></span></p>";
			echo "<p><b>".get_vocab("authentification_locale")."</b></p>";
		}
		if ((Settings::get('sso_statut') == 'lasso_visiteur') || (Settings::get('sso_statut') == 'lasso_utilisateur'))
		{
			echo "<p><span style=\"font-size:1.4em\"><a href=\"./index.php\">".get_vocab("authentification_lasso")."</a></span></p>";
			echo "<p><b>".get_vocab("authentification_locale")."</b></p>";
		}
		if ((Settings::get('sso_statut') == 'http_visiteur') || (Settings::get('sso_statut') == 'http_utilisateur'))
		{
			echo "<p><span style=\"font-size:1.4em\"><a href=\"./index.php\">".get_vocab("authentification_http")."</a></span></p>";
			echo "<p><b>".get_vocab("authentification_locale")."</b></p>";
		}
		?>
		<fieldset style="padding-top: 8px; padding-bottom: 8px; width: 40%; margin-left: auto; margin-right: auto;">
			<legend class="fontcolor3" style="font-variant: small-caps;"><?php echo get_vocab("identification"); ?></legend>
			<?php echo "<p>".get_vocab("mentions_legal_connexion")."</p>"; ?>
			<table style="width: 100%; border: 0;" cellpadding="5" cellspacing="0">
				<tr>
					<td style="text-align: right; width: 40%; font-variant: small-caps;"><?php echo get_vocab("login"); ?></td>
					<td style="text-align: center; width: 60%;"><input type="text" id="login" name="login" /></td>
				</tr>
				<tr>
					<td style="text-align: right; width: 40%; font-variant: small-caps;"><?php echo get_vocab("pwd"); ?></td>
					<td style="text-align: center; width: 60%;"><input type="password" name="password" /></td>
				</tr>
			</table>
			<?php
			if (isset($_GET['url']))
			{
				$url = rawurlencode($_GET['url']);
				echo "<input type=\"hidden\" name=\"url\" value=\"".$url."\" />\n";
			}
			?>
			<input type="submit" name="submit" value="<?php echo get_vocab("OK"); ?>" style="font-variant: small-caps;" />
		</fieldset>
	</form>
	<script type="text/javascript">
		document.getElementById('login').focus();
	</script>
	<?php
	if (Settings::get("webmaster_email") != "")
	{
		$lien = affiche_lien_contact("contact_administrateur","identifiant:non","seulement_si_email");
		if ($lien != "")
			echo "<p>[".$lien."]</p>";
	}
	echo "<p>[<a href='page.php?page=CGU' target='_blank'>".get_vocab("cgu")."</a>]</p>";
	echo "<a href=\"javascript:history.back()\">".get_vocab("previous")." - <b>".Settings::get("company")."</b></a>";
	?>
	<br />
	<br />
	<?php
	echo "<br /><p class=\"small\"><a href=\"".$grr_devel_url."\">".get_vocab("mrbs")."</a> - ".get_vocab("grr_version").affiche_version();
	$email = explode('@',$grr_devel_email);
	$person = $email[0];
	$domain = $email[1];
	echo "<br />".get_vocab("msg_login1")."<a href=\"".$grr_devel_url."\">".$grr_devel_url."</a>";
	?>
</div>
</body>
</html>