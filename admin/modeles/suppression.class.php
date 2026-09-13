<?php
/**
 * suppression.class.php
 * Interface d'accueil de Gestion des sites de l'application GRR
 * Dernière modification : $Date: 2026-09-13 16:00$
 * @author    JeromeB
 * @copyright Since 2003 Team DEVOME - JeromeB
 * @link      http://www.gnu.org/licenses/licenses.html
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 */


Class Adm_Suppression
{

	public static function Utilisateur($identifiant)
	{
		grr_sql_command("DELETE FROM ".TABLE_PREFIX."_utilisateurs WHERE login='$identifiant'");
		grr_sql_command("DELETE FROM ".TABLE_PREFIX."_j_mailuser_room WHERE login='$identifiant'");
		grr_sql_command("DELETE FROM ".TABLE_PREFIX."_j_user_area WHERE login='$identifiant'");
		grr_sql_command("DELETE FROM ".TABLE_PREFIX."_j_user_room WHERE login='$identifiant'");
		grr_sql_command("DELETE FROM ".TABLE_PREFIX."_j_userbook_room WHERE login='".$identifiant."'");
		grr_sql_command("DELETE FROM ".TABLE_PREFIX."_j_useradmin_area WHERE login='$identifiant'");
		grr_sql_command("DELETE FROM ".TABLE_PREFIX."_j_useradmin_site WHERE login='$identifiant'");
		grr_sql_command("DELETE FROM ".TABLE_PREFIX."_utilisateurs_groupes WHERE login='$identifiant'");



	}

}

?>