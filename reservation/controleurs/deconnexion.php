<?php
/**
 * deconnexion.php
 * script de deconnexion
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2023-12-30 11:10$
 * @author    Laurent Delineau & JeromeB
 * @copyright Copyright 2003-2023 Team DEVOME - JeromeB
 * @link      http://www.gnu.org/licenses/licenses.html
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 */

$trad = $vocab;

$auto = isset($_GET["auto"]) ? $_GET["auto"] : 0;
if (isset($auto))
	settype($auto,"integer");

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
grr_closeSession($auto);
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

if (!$auto)
    $d['msgLogout'] = get_vocab("msg_logout1");
else
    $d['msgLogout'] = get_vocab("msg_logout2");

echo $twig->render('deconnexion.twig', array('trad' => $trad, 'd' => $d, 'settings' => $AllSettings));
?>