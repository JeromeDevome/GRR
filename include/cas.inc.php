<?php
/**
 * cas.inc.php
 * script de redirection vers l'authentification CAS
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2017-12-16 14:00$
 * @author    Laurent Delineau <laurent.delineau@ac-poitiers.fr>
 * @copyright Copyright 2008-2008 Laurent Delineau
 * @author    JeromeB & Laurent Delineau & Olivier MOUNIER
 * @author    Laurent Delineau
 * @copyright Copyright 2003-2018 Team DEVOME - JeromeB
 * @author    Yan Naessens
 * @copyright Copyright 2017 Yan Naessens
 * @link      http://www.gnu.org/licenses/licenses.html
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 */
// Le package phpCAS doit etre stocké dans un sous-répertoire « CAS » du répertoire contenant CAS.php
// charger le script CAS.php, désormais inclus dans GRR
include_once('./include/CAS.php');

// paramètres du serveur SSO
// désormais les paramètres sont définis en page d'administration admin_config_sso.php
$serveurSSO = Settings::get("cas_serveur");
$serveurSSOPort = intval(Settings::get("cas_port"));
$serveurSSORacine = Settings::get("cas_racine");

// paramètres du proxy (si GRR doit passer par un proxy pour accéder au serveur SSO)
$cas_proxy_server = Settings::get("cas_proxy_server"); //adresse IP du serveur proxy
$cas_proxy_port = Settings::get("cas_proxy_port"); // port utilisé par le protocole CAS, doit être autorisé sur le proxy

/* declare le script comme un client CAS
 Si le dernier argument est à true, cela donne la possibilité à phpCAS d'ouvrir une session php.
*/
 phpCAS::client(CAS_VERSION_2_0,$serveurSSO,$serveurSSOPort,$serveurSSORacine,true);
 phpCAS::setLang(PHPCAS_LANG_FRENCH);

//            phpCAS::setCasServerCACert();
//Set the fixed URL that will be set as the CAS service parameter. When this method is not called, a phpCAS script uses its own URL.
//Le paramètre $Url_CAS_setFixedServiceURL est défini dans le fichier config.inc.php
 if (isset($Url_CAS_setFixedServiceURL) && ($Url_CAS_setFixedServiceURL != ''))
 	phpCAS::setFixedServiceURL($Url_CAS_setFixedServiceURL);
// ajout de la définition du proxy
if((isset($cas_proxy_server))&&($cas_proxy_server!="")&&(isset($cas_proxy_port))&&($cas_proxy_port!="")) {
 phpCAS::setExtraCurlOption(CURLOPT_PROXY     , $cas_proxy_server);
 phpCAS::setExtraCurlOption(CURLOPT_PROXYPORT , $cas_proxy_port);
 phpCAS::setExtraCurlOption(CURLOPT_PROXYTYPE , CURLPROXY_HTTP);
 }
/*
Commentez la ligne suivante si vous avez une erreur du type
PHP Fatal error:  Call to undefined method phpCAS::setnocasservervalidation() in /var/www/html/grr/include/cas.inc.php
Nécessite une version de phpCAS supérieure ou égale à 1.0.0.
*/
phpCAS::setNoCasServerValidation();
/*
Gestion du single sign-out (version 1.0.0 de phpcas)
Commentez la ligne suivante si vous avez une erreur du type
PHP Fatal error:  Call to undefined method phpCAS::handlelogoutrequests() in /var/www/html/grr/include/cas.inc.php
*/
phpCAS::handleLogoutRequests(false);

if (phpCAS::checkAuthentication())
{
	// L'utilisateur est déjà authentifié, on continue
}
else
{
	// L'utilisateur n'est pas authentifié. Que fait-on ?
	if (Settings::get("sso_redirection_accueil_grr") == 'y')
	{
		if (isset($_GET['force_authentification']))
			phpCAS::forceAuthentication();
		else
			header("Location: ".htmlspecialchars_decode(page_accueil())."");
	}
	else
	{
		phpCAS::forceAuthentication();
	}
}
$login = phpCAS::getUser();
$user_ext_authentifie = 'cas';
if (file_exists("./include/config_CAS.inc.php"))
	include("./include/config_CAS.inc.php");
?>
