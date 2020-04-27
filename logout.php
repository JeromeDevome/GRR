<?php
/**
 * logout.php
 * script de deconnexion
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2020-04-27 15:12$
 * @author    Laurent Delineau & JeromeB & Yan Naessens
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
require_once("include/connect.inc.php");
require_once("include/config.inc.php");
include "include/misc.inc.php";
include "include/functions.inc.php";
require_once("include/$dbsys.inc.php");
// Settings
require_once("./include/settings.class.php");
//Chargement des valeurs de la table settingS
if (!Settings::load())
	die("Erreur chargement settings");
// Paramètres langage
include "include/language.inc.php";
require_once("./include/session.inc.php");
if ((Settings::get('sso_statut') == 'lasso_visiteur') || (Settings::get('sso_statut') == 'lasso_utilisateur'))
{
	require_once(SPKITLASSO.'/lassospkit_public_api.inc.php');
	session_name(SESSION_NAME);
	@session_start();
	if (@$_SESSION['lasso_nameid'] != NULL)
	{
		// Nous sommes authentifiés: on se déconnecte, puis on revient
		lassospkit_set_userid(getUserName());
		lassospkit_set_nameid($_SESSION['lasso_nameid']);
		lassospkit_soap_logout();
		lassospkit_clean();
	}
}
grr_closeSession($_GET['auto']);
if (isset($_GET['url']))
{
	$url = rawurlencode($_GET['url']);
	header("Location: login.php?url=".$url);
	exit;
}
//redirection vers l'url de déconnexion
$url = Settings::get("url_disconnect");
if ($url != '')
{
	header("Location: $url");
	exit;
}
if (isset($_GET['redirect_page_accueil']) && ($_GET['redirect_page_accueil'] == 'yes'))
{
	header("Location: ./".htmlspecialchars_decode(page_accueil())."");
	exit;
}
// echo begin_page(get_vocab("mrbs"),"no_session");
header('Content-Type: text/html; charset=utf-8');
echo '<!DOCTYPE html>'.PHP_EOL.'<html lang="fr">';
echo pageHead2(get_vocab("mrbs"),"no_session");
?>
<body>
<div class="center">
	<h1>
		<?php
		if (!$_GET['auto'])
			echo (get_vocab("msg_logout1")."<br/>");
		else
			echo (get_vocab("msg_logout2")."<br/>");
		?>
	</h1>
    <a href="login.php"><?php echo (get_vocab("msg_logout3")."<br/>"); ?></a>
</div>
</body>
</html>
