<?php
/**
 * admin_page.php
 * Interface permettant à l'administrateur la configuration de certains paramètres généraux
 * Ce script fait partie de l'application GRR.
 * Dernière modification : $Date: 2024-12-14 12:05$
 * @author    JeromeB
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

$grr_script_name = "admin_page.php";

$trad = $vocab;

check_access(6, $back);

if (isset($_POST["action"]))
	$action = $_POST["action"];
else
	$action = "default";

//
if ($action == "add")
{

	if (isset($_POST["titre"]))
		$titre = $_POST["titre"];
	else
		$titre = "";

	if (isset($_POST["statutmini"]))
		$statutmini = $_POST["statutmini"];
	else
		$statutmini = "";

	if (isset($_POST["lien"]))
		$lien = $_POST["lien"];
	else
		$lien = "";

    if (isset($_POST["nouveauonglet"]))
        $nouveauonglet = intval($_POST["nouveauonglet"]);
    else
        $nouveauonglet = 1;

     if (isset($_POST["ordre"]))
        $ordre = intval($_POST["ordre"]);
    else
        $ordre = 0;


    if (isset($_POST["emplacement"]))
        $emplacement = intval($_POST["emplacement"]);
    else
        $emplacement = 0;

	$sql = "INSERT INTO ".TABLE_PREFIX."_page (nom, titre, systeme, statutmini, lien, nouveauonglet, ordre, emplacement) VALUES ('".uniqid()."', '".protect_data_sql($titre)."', 0, '".protect_data_sql($statutmini)."', '".protect_data_sql($lien)."', $nouveauonglet,  $ordre, '".protect_data_sql($emplacement)."');";
	if (grr_sql_command($sql) < 0)
		fatal_error(0, "$sql \n\n" . grr_sql_error());
}
elseif ($action == "change")
{
	$arearight = false ;
	if (isset($_POST["nom"]))
		$nom = $_POST["nom"];
	else
		$nom = "";

	if (isset($_POST["titre"]))
		$titre = $_POST["titre"];
	else
		$titre = "";

	if (isset($_POST["statutmini"]))
		$statutmini = $_POST["statutmini"];
	else
		$statutmini = "";

	if (isset($_POST["lien"]))
		$lien = $_POST["lien"];
	else
		$lien = "";

    if (isset($_POST["nouveauonglet"]))
		$nouveauonglet = $_POST["nouveauonglet"];
	else
		$nouveauonglet = "0";

	if (isset($_POST["ordre"]))
		$ordre = $_POST["ordre"];
	else
		$ordre = "0";



    $sql = "UPDATE ".TABLE_PREFIX."_page SET
    titre='".protect_data_sql($titre)."',
    statutmini='".protect_data_sql($statutmini)."',
    lien='".$lien."',
    nouveauonglet='".$nouveauonglet."',
    ordre='".$ordre."'
    WHERE nom='".$nom."';";
    if (grr_sql_command($sql) < 0)
        fatal_error(0, "$sql \n\n" . grr_sql_error());

}
elseif ($action == "delete")
{
    if (isset($_POST["nom"]))
    {
        $nom = $_POST["nom"];

        $sql = "DELETE FROM ".TABLE_PREFIX."_page WHERE nom='".$nom."';";
        if (grr_sql_command($sql) < 0)
            fatal_error(0, "$sql \n\n" . grr_sql_error());
    }
}
        


// Si pas de problème, message de confirmation
if (isset($_POST['ok'])) {
    $_SESSION['displ_msg'] = 'yes';
    if ($msg == '') {
        $d['enregistrement'] = 1;
    } else{
        $d['enregistrement'] = $msg;
    }
}
if ((isset($_GET['msg'])) && isset($_SESSION['displ_msg']) && ($_SESSION['displ_msg'] == 'yes')) {
    $msg = $_GET['msg'];
} else {
    $msg = '';
}


get_vocab_admin('cgu_titre');
get_vocab_admin('cgu_grr');
get_vocab_admin('save');
get_vocab_admin('message_records');

$lesPages = array();
$res = grr_sql_query("SELECT nom, titre, valeur, systeme, statutmini, lien, nouveauonglet, ordre, emplacement FROM ".TABLE_PREFIX."_page WHERE emplacement > 0 ORDER BY ordre ASC;");
if (!$res)
    fatal_error(0, grr_sql_error());

if (grr_sql_count($res) != 0)
{
    for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
    {	
        $lesPages[] = array('nom' => $row[0], 'titre' => $row[1], 'valeur' => $row[2], 'systeme' => $row[3], 'statutmini' => $row[4], 'lien' => $row[5], 'nouveauonglet' => $row[6], 'ordre' => $row[7], 'emplacement' => $row[8]);
    }
}

echo $twig->render($page.'.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings, 'lesPages' => $lesPages));

?>