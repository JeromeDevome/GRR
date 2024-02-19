<?php
/**
 * validation.php
 * script de redirection vers view_entry pour validation
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2024-02-19 18:24$
 * @author    Laurent Delineau & JeromeB & Yan Naessens
 * @copyright Copyright 2003-2024 Team DEVOME - JeromeB
 * @link      http://www.gnu.org/licenses/licenses.html
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 */
$grr_script_name = "validation.php";

include "include/connect.inc.php";
include "include/config.inc.php";
include "include/misc.inc.php";
include "include/functions.inc.php";
include "include/$dbsys.inc.php";
include "include/mrbs_sql.inc.php";

require_once("./include/settings.class.php");
$settings = new Settings();
if (!$settings)
	die("Erreur chargement settings");
require_once("./include/session.inc.php");
// Resume session
if (!grr_resumeSession())
{
	header("Location: ./login.php?url=$url");
	die();
}
// ici l'utilisateur devrait être connecté
include "include/language.inc.php";
// vérifie que la réservation existe et que le script est en appel initial
if (isset($_GET['id'])) // appel initial
{
	$id_resa = intval($_GET['id']);
	$room_id = grr_sql_query1("SELECT room_id FROM ".TABLE_PREFIX."_entry WHERE id=?","i",[$id_resa]);
	if ($room_id == -1)// erreur ou pas de résultat
	{
		start_page_w_header('', '', '', "with_session");
		echo '<h1>'.get_vocab("accessdenied").'</h1>';
		echo '<p class="avertissement larger">'.get_vocab("invalid_entry_id").'</p>';
		echo '<p class="center"><a class="btn btn-default" type="button" href="'.page_accueil().'">'.get_vocab("back").'</a></p>';
		end_page();
		die();
	}	
	else
	{	// On vérifie que l'utilisateur a bien le droit d'être ici
        $room_id = grr_sql_query1("SELECT room_id FROM ".TABLE_PREFIX."_entry WHERE id=? ","i",[$id_resa]);
        if (authGetUserLevel(getUserName(),$room_id) < 3)
        {
            start_page_w_header('', '', '', "with_session");
            echo '<h1>'.get_vocab("accessdenied").'</h1>';
            echo '<p class="avertissement larger">'.get_vocab("norights").'</p>';
            echo '<p class="center"><a class="btn btn-default" type="button" href="'.page_accueil().'">'.get_vocab("back").'</a></p>';
            end_page();
            die();
        }
        else
            header("Location: ./view_entry.php?id=$id_resa&mode=valide");
    }
}
else // on ne devrait jamais être ici
{
	header("Location: ".page_accueil());
	die();	
}
?>