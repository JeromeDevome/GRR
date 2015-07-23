<?php
/*
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
chdir('..');
require_once("./include/config.inc.php");
include "./include/connect.inc.php";
include "./include/misc.inc.php";
require_once("./include/$dbsys.inc.php");
require_once("./include/session.inc.php");
require_once("./include/settings.class.php");
grr_resumeSession();
require_once(SPKITLASSO.'/lassospkit_public_api.inc.php');
require_once('./lasso/delete_user.php');

$defederated = false;
if (lassospkit_nameid() != NULL && (getUserName()!=''))
{
  $login = getUserName();
  $auto = 0;
  grr_closeSession($auto);
  delete_user($login);

  lassospkit_soap_defederate();
  // s'assurer de supprimer toute trace locale
  lassospkit_clean();
  $defederated = true;
}


//Chargement des valeurs de la table settingS
require_once("./include/language.inc.php");
require_once("./include/functions.inc.php");

echo begin_page(Settings::get("company").get_vocab("deux_points").get_vocab("mrbs"),"no_session");

if ((getUserName()=='') && !$defederated) {
  echo get_vocab('lasso_please_connect');
} else if (lassospkit_nameid() == NULL) {
  echo get_vocab('lasso_defederated');
}
echo "<br />";
echo "<a href='{$_SERVER['HTTP_REFERER']}'>".get_vocab('back').'</a>';
