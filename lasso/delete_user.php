<?php
/*
 * Copyright 2003-2005 Laurent Delineau
 * Copyright (C) 2008  Cliss XXI
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

function delete_user($login)
{
  $sql = "SELECT source FROM ".TABLE_PREFIX."_utilisateurs
	    WHERE login LIKE '$login'";
  $res = grr_sql_query($sql);
  $row = grr_sql_row($res, 0);
  $source = $row[0];

  if ($source == 'ext')
    {
      // Si l'utilisateur avait été créé automatiquement, on le
      // supprime

      // Cf. admin_user.php l99 et l203
      $sql = "DELETE FROM ".TABLE_PREFIX."_utilisateurs WHERE login='$login'";
      if (grr_sql_command($sql) < 0) {fatal_error(1, "<p>" . grr_sql_error());}  else {
	grr_sql_command("DELETE FROM ".TABLE_PREFIX."_j_mailuser_room  WHERE login='$login'");
	grr_sql_command("DELETE FROM ".TABLE_PREFIX."_j_user_area      WHERE login='$login'");
	grr_sql_command("DELETE FROM ".TABLE_PREFIX."_j_user_room      WHERE login='$login'");
	grr_sql_command("DELETE FROM ".TABLE_PREFIX."_j_useradmin_area WHERE login='$login'");
	grr_sql_command("DELETE FROM ".TABLE_PREFIX."_j_useradmin_site WHERE login='$login'");
      }

      // Fin de la session
      grr_closeSession($_GET['auto']);
    }
  // sinon c'est source="local": on le garde et il y a toujours accès
  // classique login/mot de passe).
}
