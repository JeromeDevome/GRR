<?php
/**
 * cas.inc.php
 * script de redirection vers l'authentification CAS
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2026-02-22 11:30$
 * @copyright Copyright 2008-2026 Team DEVOME - JeromeB
 * @author    JeromeB & Laurent Delineau & Olivier MOUNIER & Yan Naessens
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
include_once('vendor/autoload.php');
//include_once('./include/CAS.php');
// Journal et messages détaillés phpCAS uniquement si APP_DEBUG est actif (cf. .htaccess / public/index.php)
$grrCasDebug = filter_var(
	$_SERVER['APP_DEBUG'] ?? $_SERVER['REDIRECT_APP_DEBUG'] ?? $_ENV['APP_DEBUG'] ?? getenv('APP_DEBUG') ?: false,
	FILTER_VALIDATE_BOOL
);
if ($grrCasDebug) {
	phpCAS::setDebug(dirname(__DIR__).'/var/log/phpCAS.log');
}
phpCAS::setVerbose($grrCasDebug);

/**
 * Affiche une page d'erreur CAS proposant la connexion locale, puis termine le script.
 */
function grr_cas_echec(Throwable $e, string $casOutput, bool $debug): void
{
	// Redirection normale de phpCAS (vers le serveur CAS ou nettoyage du ticket)
	// ou requête de déconnexion envoyée par le serveur CAS : ce n'est pas une erreur
	$redirection = false;
	foreach (headers_list() as $header) {
		if (stripos($header, 'Location:') === 0) {
			$redirection = true;
			break;
		}
	}
	if ($e instanceof CAS_GracefullTerminationException && ($redirection || isset($_POST['logoutRequest']))) {
		echo $casOutput;
		exit;
	}

	error_log('GRR - Erreur CAS : '.$e->getMessage());

	// Détail technique affiché uniquement si APP_DEBUG est actif
	$detail = '';
	if ($debug) {
		$detail = trim(preg_replace('/\s+/', ' ', strip_tags($casOutput)));
		if (!($e instanceof CAS_GracefullTerminationException) || $detail === '')
			$detail = trim($detail.' '.$e->getMessage());
	}

	echo begin_page(get_vocab("mrbs"), "no_session");
	echo '<div class="container" style="max-width:640px;margin:40px auto;text-align:center;">';
	echo '<div class="alert alert-danger"><strong>'.get_vocab("echec_authentification_cas").'</strong>';
	if ($detail !== '')
		echo '<pre style="white-space:pre-wrap;text-align:left;margin-top:10px;">'.htmlspecialchars($detail, ENT_QUOTES, 'UTF-8').'</pre>';
	echo '</div>';
	echo '<p>'.get_vocab("proposition_connexion_locale").'</p>';
	echo '<p><a class="btn btn-primary" href="./login?local">'.get_vocab("se_connecter_localement").'</a></p>';
	echo '</div>';
	echo '</body></html>';
	exit;
}

// phpCAS lève une exception au lieu d'appeler exit(), afin de pouvoir proposer la connexion locale en cas d'erreur
CAS_GracefullTerminationException::throwInsteadOfExiting();
ob_start();
try {
// paramètres du serveur SSO
// désormais les paramètres sont définis en page d'administration admin_config_sso.php
$serveurSSO = Settings::get("cas_serveur");
$serveurSSOPort = intval(Settings::get("cas_port"));
$serveurSSORacine = Settings::get("cas_racine");

// Version du cas
$cas_version = Settings::get("cas_version");
if($cas_version == ""){ // Si rien on prend V2 (valeur défaut GRR <= v4.0.0 grr)
	$cas_version = "CAS_VERSION_2_0";
}

// paramètres du proxy (si GRR doit passer par un proxy pour accéder au serveur SSO)
$cas_proxy_server = Settings::get("cas_proxy_server"); //adresse IP du serveur proxy
$cas_proxy_port = Settings::get("cas_proxy_port"); // port utilisé par le protocole CAS, doit être autorisé sur le proxy

$client_service_name = Settings::get("grr_url");

/* declare le script comme un client CAS
 Si le dernier argument est à true, cela donne la possibilité à phpCAS d'ouvrir une session php.
*/
if( phpCAS::getVersion() < '1.6.0' ){
	phpCAS::client( constant( $cas_version ), $serveurSSO, $serveurSSOPort, $serveurSSORacine, true );
} else{
	$parseUrl       = parse_url( $client_service_name );
	$domain_service = $parseUrl['scheme'].'://'.$parseUrl['host'];
	if ( isset( $parseUrl['port'] ) )
		$domain_service .= ':'.$parseUrl['port'];
	phpCAS::client( constant( $cas_version ), $serveurSSO, $serveurSSOPort, $serveurSSORacine, $domain_service, true );
}
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
	if (Settings::get("sso_redirection_accueil_grr") == 1)
	{
		if (isset($_GET['force_authentification']))
			phpCAS::forceAuthentication();
		else
		{
			header("Location: ".htmlspecialchars_decode(page_accueil())."");
			exit;
		}
	}
	else
	{
		phpCAS::forceAuthentication();
	}
}
$login = phpCAS::getUser();
$user_ext_authentifie = 'cas';

if (file_exists("./personnalisation/config_CAS.inc.php"))
	include("./personnalisation/config_CAS.inc.php");
else
	include("./include/config_cas.inc.php");

} catch (Throwable $e) {
	grr_cas_echec($e, (string) ob_get_clean(), $grrCasDebug);
}
ob_end_flush();
unset($grrCasDebug);
?>