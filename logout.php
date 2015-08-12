<?php
/**
 * logout.php
 * script de deconnexion
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2009-06-04 15:30:17 $
 * @author    Laurent Delineau <laurent.delineau@ac-poitiers.fr>
 * @copyright Copyright 2003-2008 Laurent Delineau
 * @link      http://www.gnu.org/licenses/licenses.html
 * @package   root
 * @version   $Id: logout.php,v 1.6 2009-06-04 15:30:17 grr Exp $
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
require_once("include/connect.inc.php");
require_once("include/config.inc.php");
include "include/misc.inc.php";
include "include/functions.inc.php";
require_once("include/$dbsys.inc.php");
include 'include/twigInit.php';
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
echo begin_page(get_vocab("mrbs"),"no_session");
?>
<div class="center">
	<h1>
		<?php
		if (!$_GET['auto'])
			echo (get_vocab("msg_logout1")."<br/>");
		else
			echo (get_vocab("msg_logout2")."<br/>");
		?>
	</h1><a href="login.php"><?php echo (get_vocab("msg_logout3")."<br/>"); ?></a>
</p>
</div>
</body>
</html>
