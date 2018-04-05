<?php
/**
 * admin_col_gauche.php
 * colonne de gauche des écrans d'administration des sites, des domaines et des ressources de l'application GRR
 * Dernière modification : $Date: 2017-12-16 14:00$
 * @author    JeromeB & Laurent Delineau & Marc-Henri PAMISEUX
 * @copyright Copyright 2003-2018 Team DEVOME - JeromeB
 * @link      http://www.gnu.org/licenses/licenses.html
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
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
				echo "<li><a href='".$key."' style='color:blue;'>".get_vocab($key)."</a></li>\n";
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
		if ((authGetUserLevel(getUserName(), -1, 'area') >= 6)&&(Settings::get('show_holidays') == 'Oui'))
			$liste[] = 'admin_calend_vacances_feries.php';
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
				$liste[] = 'admin_cgu.php';
			if (authGetUserLevel(getUserName(), -1, 'area') >= 6)
				$liste[] = 'admin_maj.php';
			if (Settings::get("sso_ac_corr_profil_statut") == 'y') {
				if (authGetUserLevel(getUserName(), -1, 'area') >= 5)
					$liste[] = 'admin_corresp_statut.php';
			}
			affichetableau($liste,get_vocab("admin_menu_various"));

			$liste = array();
			if ( (authGetUserLevel(getUserName(), -1, 'area') >= 6) && ((!isset($sso_restrictions)) || ($ldap_restrictions == false)) )
				$liste[] = 'admin_config_ldap.php';
			if ( (authGetUserLevel(getUserName(), -1, 'area') >= 6) && ((!isset($sso_restrictions)) || ($sso_restrictions == false)) )
				$liste[] = 'admin_config_sso.php';
			if ( (authGetUserLevel(getUserName(), -1, 'area') >= 6) && ((!isset($sso_restrictions)) || ($imap_restrictions == false)) )
				$liste[] = 'admin_config_imap.php';
			affichetableau($liste,get_vocab("admin_menu_auth"));

			echo "</div>\n";
?>
		</td>
		<td>
