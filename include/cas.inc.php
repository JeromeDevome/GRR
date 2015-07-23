<?php
/**
 * cas.inc.php
 * script de redirection vers l'authentification CAS
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2010-04-07 15:38:14 $
 * @author    Laurent Delineau <laurent.delineau@ac-poitiers.fr>
 * @copyright Copyright 2008-2008 Laurent Delineau
 * @author    Olivier Mounier
 * @author    Laurent Delineau
 * @copyright Copyright 2005 Olivier Mounier
 * @link      http://www.gnu.org/licenses/licenses.html
 * @package   root
 * @version   $Id: cas.inc.php,v 1.7 2010-04-07 15:38:14 grr Exp $
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
// Le package phpCAS doit etre stocké dans un sous-répertoire « CAS »
// dans un répertoire correspondant a l'include_path du php.ini (exemple : /var/lib/php)
include_once('CAS/CAS.php');
// cas.sso est le fichier d'informations de connexions au serveur cas
// Le fichier cas.sso doit etre stocké dans un sous-répertoire « CAS »
// dans un répertoire correspondant a l'include_path du php.ini (exemple : /var/lib/php)
include('CAS/cas.sso');
/* declare le script comme un client CAS
 Si le dernier argument est à true, cela donne la possibilité à phpCAS d'ouvrir une session php.
*/
 phpCAS::client(CAS_VERSION_2_0,$serveurSSO,$serveurSSOPort,$serveurSSORacine,true);
 phpCAS::setLang('french');
//Set the fixed URL that will be set as the CAS service parameter. When this method is not called, a phpCAS script uses its own URL.
//Le paramètre $Url_CAS_setFixedServiceURL est défini dans le fichier config.inc.php
 if (isset($Url_CAS_setFixedServiceURL) && ($Url_CAS_setFixedServiceURL != ''))
 	phpCAS::setFixedServiceURL($Url_CAS_setFixedServiceURL);
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
