<?php

/**
 * admin.inc.php.
 *
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2009-01-20 07:19:17 $
 *
 * @author    Laurent Delineau <laurent.delineau@ac-poitiers.fr>
 * @copyright Copyright 2003-2008 Laurent Delineau
 *
 * @link      http://www.gnu.org/licenses/licenses.html
 *
 * @version   $Id: admin.inc.php,v 1.3 2009-01-20 07:19:17 grr Exp $
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
if (@file_exists('../include/connect.inc.php')) {
    $racine = '../';
} else {
    $racine = './';
}

    include $racine.'include/connect.inc.php';
    include $racine.'include/config.inc.php';
    include $racine.'include/mrbs_sql.inc.php';
    include $racine.'include/misc.inc.php';
    include $racine.'include/functions.inc.php';
    include $racine."include/$dbsys.inc.php";
    include $racine."include/twigInit.php";

// Settings
require_once $racine.'include/settings.class.php';
//Chargement des valeurs de la table settingS
if (!Settings::load()) {
    die('Erreur chargement settings');
}
// Session related functions
require_once $racine.'include/session.inc.php';
// Resume session
if (!grr_resumeSession()) {
    header("Location: {$racine}logout.php?auto=1&url=$url");
    die();
};
// Paramètres langage
$use_admin = 'y';
include $racine.'include/language.inc.php';
