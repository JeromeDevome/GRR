<?php
/**
 * include/resume_session.php
 * Si session obligatoire ou expiré renvois à la deconnexion
 * Dernière modification : $Date: 2017-12-16 14:00$
 * @author    JeromeB & Laurent Delineau
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

if (!grr_resumeSession())
{
	if ((Settings::get("authentification_obli") == 1) || ((Settings::get("authentification_obli") == 0) && (isset($_SESSION['login']))))
	{
		header("Location: ./logout.php?auto=1&url=$url");
		die();
	}
};
?>
