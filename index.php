<?php
/**
 * index.php
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2010-04-07 15:38:14 $
 * @author    Laurent Delineau <laurent.delineau@ac-poitiers.fr>
 * @author    Marc-Henri PAMISEUX <marcori@users.sourceforge.net>
 * @copyright Copyright 2003-2008 Laurent Delineau
 * @copyright Copyright 2008 Marc-Henri PAMISEUX
 * @link      http://www.gnu.org/licenses/licenses.html
 * @package   admin
 * @version   $Id: index.php,v 1.10 2010-04-07 15:38:14 grr Exp $
 * @filesource
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GRR is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GRR; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */
if (!@file_exists("/var/www/lcs/includes/headerauth.inc.php"))
	error_reporting (E_ALL);
require_once("include/config.inc.php");
if (file_exists("include/connect.inc.php"))
	include "include/connect.inc.php";
require_once("include/misc.inc.php");
require_once("include/functions.inc.php");
require_once("include/settings.class.php");
require_once("include/twigInit.php");
// Paramètres langage
include "include/language.inc.php";
// Dans le cas d'une base mysql, on teste la bonne installation de la base et on propose une installation automatisée.
if ($dbsys == "mysql")
{
	$flag = '';
	$correct_install = '';
	$msg = '';
	if (file_exists("include/connect.inc.php"))
	{
		require_once("include/connect.inc.php");
		$db = @mysqli_connect("$dbHost", "$dbUser", "$dbPass", "$dbPort");
		if ($db)
		{
			if (mysqli_select_db($db, "$dbDb"))
			{
				// Premier test
				@mysql_query("SET NAMES utf8");

				$j = '0';
				while ($j < count($liste_tables))
				{
					$test = mysqli_query($db, "SELECT count(*) FROM ".$table_prefix.$liste_tables[$j]);
					if (!$test)
						$flag = 'yes';
					$j++;
				}
				if ($flag == 'yes')
				{
					$msg = "<p>La connection au serveur $dbsys est établie mais certaines tables sont absentes de la base $dbDb.</p>";
					$correct_install = 'no';
				}
			}
			else
			{
				$msg = "La connection au serveur $dbsys est établie mais impossible de sélectionner la base contenant les tables GRR.";
				$correct_install = 'no';
			}
		}
		else
		{
			$msg = "Erreur de connection au serveur $dbsys. Le fichier \"connect.inc.php\" ne contient peut-être pas les bonnes informations de connection.";
			$correct_install = 'no';
		}
	}
	else
	{
		$msg = "Le fichier \"connect.inc.php\" contenant les informations de connection est introuvable.";
		$correct_install = 'no';
	}
	if ($correct_install == 'no')
	{
		echo begin_page("GRR (Gestion et Réservation de Ressources) ");
		echo "<h1 class=\"center\">Gestion et Réservation de Ressources</h1>\n";
		echo "<div style=\"text-align:center;\"><span style=\"color:red;font-weight:bold\">".$msg."</span>\n";
		echo "<ul><li>Soit vous procédez à une mise à jour vers une nouvelle version de GRR. Dans ce cas, vous devez procéder à une mise à jour de la base de données MySql.<br />";
		echo "<b><a href='./admin/admin_maj.php'>Mettre à jour la base Mysql</a></b><br /></li>";
		echo "<li>Soit l'installation de GRR n'est peut-être pas terminée. Vous pouvez procéder à une installation/réinstallation de la base.<br />";
		echo "<a href='install_mysql.php'>Installer la base $dbsys</a></li></ul></div>";
		?>
	</body>
	</html>
	<?php
	die();
}
}
require_once("include/$dbsys.inc.php");
require_once("./include/session.inc.php");
//Settings
require_once("./include/settings.class.php");
//Chargement des valeurs de la table settingS
if (!Settings::load())
	die("Erreur chargement settings");
$cook = session_get_cookie_params();
// Cas d'une authentification CAS
if ((Settings::get('sso_statut') == 'cas_visiteur') || (Settings::get('sso_statut') == 'cas_utilisateur'))
{
	require_once("./include/cas.inc.php");
	// A ce stade, l'utilisateur est authentifié par CAS
	$password = '';
	$user_ext_authentifie = 'cas';
	if (!isset($user_nom))
		$user_nom='';
	$cas_tab_login["user_nom"] = $user_nom;
	if (!isset($user_prenom))
		$user_prenom='';
	$cas_tab_login["user_prenom"] = $user_prenom;
	if (!isset($user_mail))
		$user_mail='';
	$cas_tab_login["user_email"] = $user_mail;
	if (!isset($user_code_fonction))
		$user_code_fonction='';
	$cas_tab_login["user_code_fonction"] = $user_code_fonction;
	if (!isset($user_libelle_fonction))
		$user_libelle_fonction='';
	$cas_tab_login["user_libelle_fonction"] = $user_libelle_fonction;
	if (!isset($user_language))
		$user_language='';
	$cas_tab_login["user_language"] = $user_language;
	if (!isset($user_default_style))
		$user_default_style='';
	$cas_tab_login["user_default_style"] = $user_default_style;
	$result = grr_opensession($login,$password,$user_ext_authentifie,$cas_tab_login);
	// On écrit les données de session et ferme la session
	session_write_close();
	$message = '';
	if ($result == "2")
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
		$message = get_vocab("echec_connexion_GRR");
		$message .= " ".get_vocab("causes_possibles");
		$message .= "<br />- ".get_vocab("wrong_pwd");
		$message .= "<br />- ". get_vocab("echec_authentification_ldap");
	}
	else if ($result != "1")
	{
		$message = get_vocab("echec_connexion_GRR");
		$message .= "<br />Cause inconnue.";
	}

	if ($message != '')
	{
		echo $message;
		die();
	}
	if (grr_resumeSession())
		header("Location: ".htmlspecialchars_decode(page_accueil())."");
// Cas d'une authentification Lemonldap
}
else if ((Settings::get('sso_statut') == 'lemon_visiteur') || (Settings::get('sso_statut') == 'lemon_utilisateur'))
{
	if (isset($_GET['login']))
		$login = $_GET['login'];
	else
		$login = "";
	if (isset($_COOKIE['user']))
		$cookie_user = $_COOKIE['user'];
	else
		$cookie_user = "";
	if (empty($cookie_user) || $cookie_user != $login)
	{
		if ((Settings::get("Url_cacher_page_login") != "") && ((!isset($sso_super_admin)) || ($sso_super_admin == false)))
			header("Location: ".Settings::get("Url_cacher_page_login"));
		else
			header("Location: ".htmlspecialchars_decode(page_accueil())."");
		//header("Location: ./login.php");
		// Echec de l'authentification lemonldap
		die();
		echo "</body></html>";
	}
	// A ce stade, l'utilisateur est authentifié par Lemonldap
	$user_ext_authentifie = 'lemon';
	$password = '';
	$result = grr_opensession($login,$password,$user_ext_authentifie) ;
	// On écrit les données de session et ferme la session
	session_write_close();
	$message = '';
	if ($result == "2")
	{
		$message = get_vocab("echec_connexion_GRR");
		$message .= " ".get_vocab("wrong_pwd");
	}
	else if ($result == "3")
	{
		$message = get_vocab("echec_connexion_GRR");
		$message .= "<br />". get_vocab("importation_impossible");
	}
	else if ($result != "1")
	{
		$message = get_vocab("echec_connexion_GRR");
		$message .= "<br />Cause inconnue.";
	}
	if ($message != '')
	{
		echo $message;
		die();
	}
	if (grr_resumeSession())
		header("Location: ".htmlspecialchars_decode(page_accueil())."");
// Cas d'une authentification LCS
}
else if (Settings::get('sso_statut') == 'lcs')
{
	include LCS_PAGE_AUTH_INC_PHP;
	include LCS_PAGE_LDAP_INC_PHP;
	list($idpers,$login) = isauth();
	if ($idpers)
	{
		list($user, $groups)=people_get_variables($login, true);
		$lcs_tab_login["nom"] = $user["nom"];
		$lcs_tab_login["email"] = $user["email"];
		$long = strlen($user["fullname"]) - strlen($user["nom"]);
		$lcs_tab_login["fullname"] = substr($user["fullname"], 0, $long) ;
		foreach ($groups as $value)
			$lcs_groups[] = $value["cn"];
		// A ce stade, l'utilisateur est authentifié par LCS
		// Etablir à nouveau la connexion à la base
		if (empty($db_nopersist))
			$db_c = mysql_pconnect($dbHost, $dbUser, $dbPass);
		else
			$db_c = mysql_connect($dbHost, $dbUser, $dbPass);
		if (!$db_c || !mysql_select_db ($dbDb))
		{
			echo "\n<p>\n" . get_vocab('failed_connect_db') . "\n";
			exit;
		}
		if (is_eleve($login))
			$user_ext_authentifie = 'lcs_eleve';
		else
			$user_ext_authentifie = 'lcs_non_eleve';
		$password = '';
		$result = grr_opensession($login,$password,$user_ext_authentifie,$lcs_tab_login,$lcs_groups) ;
		// On écrit les données de session et ferme la session
		session_write_close();
		$message = '';
		if ($result == "2")
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
		if ($message != '')
		{
			fatal_error(1, $message);
			die();
		}
		if (grr_resumeSession())
			header("Location: ".htmlspecialchars_decode(page_accueil())."");
	}
	else
	{
		// L'utilisateur n'a pas été identifié'
		if (Settings::get("authentification_obli") == 1)
		{
			// authentification obligatoire, l'utilisateur est renvoyé vers une page de connexion
			require_once("include/session.inc.php");
			grr_closeSession($_GET['auto']);
			header("Location:".LCS_PAGE_AUTHENTIF);
		}
		else
			header("Location: ".htmlspecialchars_decode(page_accueil())."");
		// authentification non obligatoire, l'utilisateur est simple visiteur
	}
}
// Cas d'une authentification Lasso
if ((Settings::get('sso_statut') == 'lasso_visiteur') || (Settings::get('sso_statut') == 'lasso_utilisateur'))
{
	require_once(SPKITLASSO.'/lassospkit_public_api.inc.php');
	if (lassospkit_nameid() == NULL)
	{
		// S'il y a eu une erreur et que l'on revient, afficher
		// l'erreur. Cela annule la redirection de header(), mais
		// l'utilisateur pourra quand même cliquer manuellement sur un
		// lien.
		$error = lassospkit_error();
		if (!empty($error))
			echo "SSO error:<br /><pre>$error</pre><br />";
		// Pas encore authentifié - on se connecte:
		$return_url = get_request_uri();
		lassospkit_redirect_federate($return_url);
		exit();
	}
	// A ce stade, l'utilisateur est authentifié par Lasso
	$password = '';
	$login = lassospkit_userid();
	// vide si pas encore fédéré
	if (empty($login))
	{
		// Construit un identifiant unique
		$sql = "SELECT login FROM ".TABLE_PREFIX."_utilisateurs WHERE login LIKE 'lasso_%'";
		$res = grr_sql_query($sql);
		$existing_users = array();
		for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
			$existing_users[] = $row[0];
		$max = 0;
		foreach ($existing_users as $user)
		{
			if (preg_match('/lasso_(\d+)/', $user, $matches))
			{
				if ($matches[1] > $max)
					$max = $matches[1];
			}
		}
		$login = 'lasso_'.($max + 1);
		// Stockage de la défération
		lassospkit_set_userid($login);
	}
	$user_ext_authentifie = 'lasso';
	$tab_login["fullname"] = "Anne";
	$tab_login["nom"] = "Nonyme";
	$tab_login["email"] = "";
	// S'il y a des attributs supplémentaires, on les utilise
	$attributes = lassospkit_get_assertion_attributes();
	if ($attributes)
	{
		// Get infos from the Identity Provider
		$user_infos = array();
		// Nom Prénom
		list($tab_login['nom'], $tab_login['fullname']) = split(' ', $attributes['cn'][0]);
		$tab_login['email'] = $attributes['mail'][0];
		// Pour l'instant on ne redéfinit pas le login
		//$tab_login['???'] = $attributes['username'][0];
	}
	$result = grr_opensession($login, $password, $user_ext_authentifie, $tab_login);
	// Stocker le nameid dans la session pour se souvenir que c'est
	// un login lasso
	$_SESSION['lasso_nameid'] = lassospkit_nameid();
	// Ne plus réutiliser la session spkitlasso courante, pour
	// éviter les problèmes de nettoyage au logout distant
	lassospkit_set_nameid(null);
	lassospkit_clean();
	// On écrit les données de session et ferme la session
	session_write_close();
	$message = '';
	if ($result == "2")
	{
		$message = get_vocab("echec_connexion_GRR");
		$message .= " ".get_vocab("wrong_pwd");
	}
	else if ($result == "3")
	{
		// L'utilisateur existe déjà
		$message = get_vocab("echec_connexion_GRR");
		$message .= "<br />". get_vocab("importation_impossible");
	}
	else if ($result == "4")
	{
		$message = get_vocab("echec_connexion_GRR");
		$message .= " ".get_vocab("causes_possibles");
		$message .= "<br />- ".get_vocab("wrong_pwd");
		$message .= "<br />- ". get_vocab("echec_authentification_ldap");
	}
	else if ($result != "1")
	{
		$message = get_vocab("echec_connexion_GRR");
		$message .= "<br />Cause inconnue.";
	}
	if ($message != '')
	{
		echo $message;
		die();
	}
	if (grr_resumeSession())
		header("Location: ".htmlspecialchars_decode(page_accueil())."");
	// Cas d'une authentification apache
}
else if ((Settings::get('sso_statut') == 'http_visiteur') || (Settings::get('sso_statut') == 'http_utilisateur'))
{
	// Nous utilisons les fonction d'authentification par PHP (plutôt que par Apache) à l'aide des lignes :
	// header('WWW-Authenticate: Basic realm="..."'); et header('HTTP/1.0 401 Unauthorized');
	// Mais ces fonctions ne sont disponibles que si PHP est exécuté comme module Apache,
	// et non pas sous la forme d'un CGI.
	// Si PHP est en mode cgi il faut utiliser une réecriture de l'url vie le module rewrite de apache :
	// Vous devez créer un fichier .htaccess ayant comme contenu
	//  <IfModule mod_rewrite.c>
	//  RewriteEngine on
	//  RewriteRule .* - [E=REMOTE_USER:%,L]
	//  </IfModule>
	// Cela permet de récupérer dans $_SERVER['REMOTE_USER'] le login et le mot de passe
	// mais crypté :
	// on obtient le login et le password sous la forme : user:password
	// Cas le plus courant :
	if (isset($_SERVER['PHP_AUTH_USER']) && !empty($_SERVER['PHP_AUTH_USER']))
	{
		$login = $_SERVER['PHP_AUTH_USER'];
		// Pour les versions plus anciennes de PHP < 4.1.0 (en fait inutile ici car GRR exige PHP > 4.3.1
	}
	else if (isset($HTTP_SERVER_VARS['PHP_AUTH_USER']) && !empty($HTTP_SERVER_VARS['PHP_AUTH_USER']))
	{
		$login = $HTTP_SERVER_VARS['PHP_AUTH_USER'];
		// L'utilisateur est authentifié mais $_SERVER['PHP_AUTH_USER'] est vide, on tente de récupérer le login dans $_SERVER['REMOTE_USER']
	}
	else if (isset($_SERVER['REMOTE_USER']) && !empty($_SERVER['REMOTE_USER']))
	{
		// Cas ou PHP est en mode cgi
		if (preg_match('/Basic+(.*)$/i', $_SERVER['REMOTE_USER'], $matches))
		{
			// Si PHP est en mode cgi il faut utiliser une réecriture de l'url vie le module rewrite de apache :
			// Vous devez créer un fichier .htaccess ayant comme contenu
			//  <IfModule mod_rewrite.c>
			//  RewriteEngine on
			//  RewriteRule .* - [E=REMOTE_USER:%,L]
			//  </IfModule>
			// Cela permet de récupérer dans $_SERVER['REMOTE_USER'] le login et le mot de passe
			// mais crypté :
			// on obtient le login et le password sous la forme : user:password
			$identifiers = base64_decode($matches[1]);
			// on l'exporte dans un tableau
			$identifiers_tab = explode(':', $identifiers);
			// on récupère le tout dans des variables
			$login = strip_tags($identifiers_tab[0]);
			// le mot de passe peut être récupéré dans strip_tags($identifiers_tab[1]) mais on n'en a pas besoin ici
		}
		else
		{
			// Cas normal
			$login = $_SERVER['REMOTE_USER'];
		}
		// Cas de PHP4 en mode CGI sur IIS
	}
	else if (isset($_SERVER['HTTP_AUTHORIZATION']) && !empty($_SERVER['HTTP_AUTHORIZATION']))
		list($login, $pw) = explode(':', base64_decode(substr($_SERVER['HTTP_AUTHORIZATION'], 6)));
	else
	{
		// on demande de s'identifier
		// A ce stade :
		// - soit l'utilisateur ne s'est pas encore identifié
		// - soit l'utilisateur s'est identifié mais de façon incorrecte
		// - soit l'utilisateur s'est identifié de façon correcte mais l'identifiant n'a pas pu être récupéré.
		$my_message = "Module d'authentification de GRR";
		header('WWW-Authenticate: Basic realm="' . $my_message . '"');
		header('HTTP/1.0 401 Unauthorized');
		// en cas d'annulation
		echo begin_page(get_vocab("mrbs"),"no_session");
		echo "<h3>".get_vocab("wrong_pwd")."</h3>";
		echo "<h3>".get_vocab("connexion_a_grr_non_autorisee")."</h3>";
		echo "</body></html>";
		exit();
	}
	// A ce stade, l'utilisateur est authentifié et $login n'est pas vide via le serveur apache
	$user_ext_authentifie = 'apache';
	$password = '';
	$result = grr_opensession($login,$password,$user_ext_authentifie);
		// On écrit les données de session et ferme la session
	session_write_close();
	$message = '';
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
	else if ($result != "1")
	{
		$message = get_vocab("echec_connexion_GRR");
		$message .= "<br />Cause inconnue.";
	}

	if ($message != '')
	{
		echo $message;
		die();
	}

	if (grr_resumeSession() )
	{
		header("Location: ".htmlspecialchars_decode(page_accueil())."");
	}
}
else
{
	if (Settings::get("authentification_obli") == 1)
	{
		if ($cook["path"] != '')
		{
			if (grr_resumeSession())
				header("Location: ".htmlspecialchars_decode(page_accueil())."");
			else
			{
				if ((Settings::get("Url_cacher_page_login") != "") && ((!isset($sso_super_admin)) || ($sso_super_admin == false)))
					header("Location: ".Settings::get("Url_cacher_page_login"));
				else
					header("Location: ./login.php");
			}
		}
		else
		{
			if ((Settings::get("Url_cacher_page_login") != "") && ((!isset($sso_super_admin)) || ($sso_super_admin == false)))
				header("Location: ".Settings::get("Url_cacher_page_login"));
			else
				header("Location: ./login.php");
		}
	}
	else
		header("Location: ".htmlspecialchars_decode(page_accueil())."");
}
?>
