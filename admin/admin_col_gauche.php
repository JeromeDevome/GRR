<?php
/**
 * admin_col_gauche.php
 * colonne de gauche des écrans d'administration
 * des sites, des domaines et des ressources de l'application GRR
 * Dernière modification : $Date: 2010-04-07 15:38:13 $
 * @author    Laurent Delineau <laurent.delineau@ac-poitiers.fr>
 * @author    Marc-Henri PAMISEUX <marcori@users.sourceforge.net>
 * @copyright Copyright 2003-2008 Laurent Delineau
 * @copyright Copyright 2008 Marc-Henri PAMISEUX
 * @link      http://www.gnu.org/licenses/licenses.html
 * @package   admin
 * @version   $Id: admin_col_gauche.php,v 1.13 2010-04-07 15:38:13 grr Exp $
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

function affichetableau($liste,$titre='')
{
	global $chaine, $vocab;
	if (count($liste) > 0)
	{
		echo "<fieldset>\n";
		echo "<legend>$titre</legend><ul>\n";
		$k = 0;
		foreach ($liste as $key)
		{
			if ($chaine == $key)
				echo "<li><span class=\"bground\"><b>".get_vocab($key)."</b></span></li>\n";
			else
				echo "<li><a href='".$key."'>".get_vocab($key)."</a></li>\n";
			$k++;
		}
		echo "</ul></fieldset>\n";
	}
}

echo "<table class=\"table_adm4\">";
// Affichage de la colonne de gauche
?>
<tr>
	<td class="colgauche_admin">
		<?php
		if (get_request_uri() != '')
		{
			$url_ = parse_url(get_request_uri());
			$pos = strrpos($url_['path'], "/") + 1;
			$chaine = substr($url_['path'], $pos);
		}
		else
			$chaine = '';
		echo "<div id=\"colgauche\">\n";
		$liste = array();
		if (authGetUserLevel(getUserName(), -1, 'area') >= 6)
			$liste[] = 'admin_config.php';
		if (authGetUserLevel(getUserName(), -1, 'area') >= 6)
			$liste[] = 'admin_type.php';
		if (authGetUserLevel(getUserName(), -1, 'area') >= 6)
			$liste[] = 'admin_calend_ignore.php';
		if (Settings::get("jours_cycles_actif") == "Oui")
		{
			if (authGetUserLevel(getUserName(), -1, 'area') >= 6)
				$liste[] = 'admin_calend_jour_cycle.php';
		}
		affichetableau($liste,get_vocab("admin_menu_general"));
		$liste = array();
		if (Settings::get("module_multisite") == "Oui")
		{
			if (authGetUserLevel(getUserName(), -1, 'area') >= 6)
				$liste[] = 'admin_site.php';
		}
		if (authGetUserLevel(getUserName(), -1, 'area') >= 4)
			$liste[] = 'admin_room.php';
		if (authGetUserLevel(getUserName(), -1, 'area') >= 4)
			$liste[] = 'admin_overload.php';
		if (Settings::get("module_multisite") == "Oui")
			affichetableau($liste,get_vocab("admin_menu_site_area_room"));
		else
			affichetableau($liste,get_vocab("admin_menu_arearoom"));

		$liste = array();
		if ((authGetUserLevel(getUserName(), -1, 'area') >= 6) || (authGetUserLevel(getUserName(), -1, 'user') == 1))
			$liste[] = 'admin_user.php';
		if (Settings::get("module_multisite") == "Oui")
			if (authGetUserLevel(getUserName(), -1, 'area') >= 6)
				$liste[] = 'admin_admin_site.php';
			if (authGetUserLevel(getUserName(), -1, 'area') >= 6)
				$liste[] = 'admin_right_admin.php';
			if (authGetUserLevel(getUserName(), -1, 'area') >= 4)
				$liste[] = 'admin_access_area.php';
			if (authGetUserLevel(getUserName(), -1, 'area') >= 4)
				$liste[] = 'admin_right.php' ;
			if ((Settings::get("ldap_statut") != "") || (Settings::get("sso_statut") != "") || (Settings::get("imap_statut") != ""))
			{
				if (authGetUserLevel(getUserName(), -1, 'area') >= 6)
					$liste[] = 'admin_purge_accounts.php';
			}
			affichetableau($liste,get_vocab("admin_menu_user"));
			$liste = array();
			if (authGetUserLevel(getUserName(), -1, 'area') >= 4)
				$liste[] = 'admin_email_manager.php';
			if (authGetUserLevel(getUserName(), -1, 'area') >= 6)
				$liste[] = 'admin_view_connexions.php';
			if (authGetUserLevel(getUserName(), -1, 'area') >= 4)
				$liste[] = 'admin_calend.php';
			if (authGetUserLevel(getUserName(), -1, 'area') >= 6)
				$liste[] = 'admin_maj.php';
			if (Settings::get("sso_ac_corr_profil_statut") == 'y') {
				if (authGetUserLevel(getUserName(), -1, 'area') >= 5)
					$liste[] = 'admin_corresp_statut.php';
			}
			affichetableau($liste,get_vocab("admin_menu_various"));
		// Possibilité de bloquer l'affichage de la rubrique "Authentification et ldap"
			if ((!isset($sso_restrictions)) || ($sso_restrictions == false))
			{
				$liste = array();
				if (authGetUserLevel(getUserName(), -1, 'area') >= 6)
					$liste[] = 'admin_config_ldap.php';
				if (authGetUserLevel(getUserName(), -1, 'area') >= 6)
					$liste[] = 'admin_config_sso.php';
	 		//ajout page admin_config_imap.php
				if (authGetUserLevel(getUserName(), -1, 'area') >= 6)
					$liste[] = 'admin_config_imap.php';
				affichetableau($liste,get_vocab("admin_menu_auth"));
			}
		// début affichage de la colonne de gauche
			echo "</div>\n";
			?>
		</td>
		<td>
