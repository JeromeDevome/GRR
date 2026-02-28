<?php
/**
 * admin_nettoyage_bdd.php
 * Interface permettant à l'administrateur la configuration de certains paramètres généraux
 * Ce script fait partie de l'application GRR.
 * Dernière modification : $Date: 2023-11-11 18:10$
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

$grr_script_name = "admin_nettoyage_bdd.php";

check_access(6, $back);


$dateMax = isset($_POST["1cleanlog"]) ? $_POST["1cleanlog"] : NULL;
$numForm = isset($_POST["numform"]) ? intval(clean_input($_POST["numform"])) : 0;
$numAction = isset($_POST["numaction"]) ? intval(clean_input($_POST["numaction"])) : 0;

$logconnexion1 = isset($_POST["1logconnexion"]) ? (($_POST["1logconnexion"])) : NULL;
$logmail1 = isset($_POST["1logmail"]) ? (($_POST["1logmail"])) : NULL;
$logresa1 = isset($_POST["1logresa"]) ? (($_POST["1logresa"])) : NULL;
$resa1 = isset($_POST["1resa"]) ? (($_POST["1resa"])) : NULL;

$logconnexion2 = isset($_POST["2logconnexion"]) ? (($_POST["2logconnexion"])) : NULL;
$logmail2 = isset($_POST["2logmail"]) ? (($_POST["2logmail"])) : NULL;
$logresa2 = isset($_POST["2logresa"]) ? (($_POST["2logresa"])) : NULL;
$vacances2 = isset($_POST["2vacances"]) ? (($_POST["2vacances"])) : NULL;
$feries2 = isset($_POST["2feries"]) ? (($_POST["2feries"])) : NULL;
$horsreservation2 = isset($_POST["2horsreservation"]) ? (($_POST["2horsreservation"])) : NULL;
$resasup2 = isset($_POST["2resasup"]) ? (($_POST["2resasup"])) : NULL;
$resa2 = isset($_POST["2resa"]) ? (($_POST["2resa"])) : NULL;
$uservisiteur2 = isset($_POST["2uservisiteur"]) ? (($_POST["2uservisiteur"])) : NULL;
$userusager2 = isset($_POST["2userusager"]) ? (($_POST["2userusager"])) : NULL;

$msg = "";
$trad = $vocab;

/* Enregistrement de la page */


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

// Form1 : Supression avant une date
if ($numForm == 1 && valideDate($dateMax,"d/m/Y") ) {

    $dateMax = str_replace('/', '-', $dateMax);

    if ($logconnexion1 == "on" ) {
        $dateMax = date( 'Y-m-d', strtotime( $dateMax ) );
        $sql = "DELETE FROM ".TABLE_PREFIX."_log WHERE START < '".$dateMax."'";
        $res = grr_sql_query($sql);
    }

    if ($logmail1 == "on" ) {
        $sql = "DELETE FROM ".TABLE_PREFIX."_log_mail WHERE date < '".strtotime($dateMax)."'";
        $res = grr_sql_query($sql);
    }

    if ($logresa1 == "on" ) {
        $sql = "DELETE FROM ".TABLE_PREFIX."_log_resa WHERE date < '".strtotime($dateMax)."'";
        $res = grr_sql_query($sql);
    }

    if ($resa1 == "on" ) {
		$del = grr_sql_query("DELETE FROM ".TABLE_PREFIX."_entry WHERE end_time < ".strtotime($dateMax));
		$del = grr_sql_query("DELETE FROM ".TABLE_PREFIX."_repeat WHERE end_date < ".strtotime($dateMax));
		$del = grr_sql_query("DELETE FROM ".TABLE_PREFIX."_entry_moderate WHERE end_time < ".strtotime($dateMax));
        $del = grr_sql_query("DELETE FROM ".TABLE_PREFIX."_log_resa WHERE DAY < ".strtotime($dateMax));
    }

}
// Form 2 : Supression complete par element
elseif ($numForm == 2) {

    if ($logconnexion2 == "on" ) {
        $sql = "TRUNCATE TABLE ".TABLE_PREFIX."_log";
        $res = grr_sql_query($sql);
    }

    if ($logmail2 == "on" ) {
        $sql = "TRUNCATE TABLE ".TABLE_PREFIX."_log_mail";
        $res = grr_sql_query($sql);
    }

    if ($logresa2 == "on" ) {
        $sql = "TRUNCATE TABLE ".TABLE_PREFIX."_log_resa";
        $res = grr_sql_query($sql);
    }

    if ($vacances2 == "on" ) {
        $sql = "TRUNCATE TABLE ".TABLE_PREFIX."_calendrier_vacances";
        $res = grr_sql_query($sql);
    }

    if ($feries2 == "on" ) {
        $sql = "TRUNCATE TABLE ".TABLE_PREFIX."_calendrier_feries";
        $res = grr_sql_query($sql);
    }

    if ($horsreservation2 == "on" ) {
        $sql = "TRUNCATE TABLE ".TABLE_PREFIX."_calendar";
        $res = grr_sql_query($sql);
    }

    if ($resasup2 == "on" ) {
        $sql = "SELECT id, repeat_id FROM ".TABLE_PREFIX."_entry WHERE supprimer = 1";
        $res = grr_sql_query($sql);
        if ($res)
        {
            for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
            {
                $del = grr_sql_query("DELETE FROM ".TABLE_PREFIX."_entry WHERE id = ".$row[0]);
                $del = grr_sql_query("DELETE FROM ".TABLE_PREFIX."_entry_moderate WHERE id = ".$row[0]);
                $del = grr_sql_query("DELETE FROM ".TABLE_PREFIX."_log_resa WHERE idresa = ".$row[0]);
                if($row[1] != 0)
                    $del = grr_sql_query("DELETE FROM ".TABLE_PREFIX."_repeat WHERE id = ".$row[1]);
            }
        }
    }

    if ($resa2 == "on" ) {
		$del = grr_sql_query("TRUNCATE TABLE ".TABLE_PREFIX."_entry");
		$del = grr_sql_query("TRUNCATE TABLE ".TABLE_PREFIX."_repeat");
		$del = grr_sql_query("TRUNCATE TABLE ".TABLE_PREFIX."_entry_moderate");
        $del = grr_sql_query("TRUNCATE TABLE ".TABLE_PREFIX."_log_resa");
    }

    if ($uservisiteur2 == "on" ) {   
        $sql = "SELECT login FROM ".TABLE_PREFIX."_utilisateurs WHERE statut = 'visiteur'";
        $res = grr_sql_query($sql);
        if ($res)
        {
            for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
            {
                grr_sql_command("DELETE FROM ".TABLE_PREFIX."_utilisateurs WHERE login='$row[0]'");
                grr_sql_command("DELETE FROM ".TABLE_PREFIX."_j_mailuser_room WHERE login='$row[0]'");
                grr_sql_command("DELETE FROM ".TABLE_PREFIX."_j_user_area WHERE login='$row[0]'");
                grr_sql_command("DELETE FROM ".TABLE_PREFIX."_j_user_room WHERE login='$row[0]'");
                grr_sql_command("DELETE FROM ".TABLE_PREFIX."_j_useradmin_area WHERE login='$row[0]'");
                grr_sql_command("DELETE FROM ".TABLE_PREFIX."_j_useradmin_site WHERE login='$row[0]'");
                grr_sql_command("DELETE FROM ".TABLE_PREFIX."_j_userbook_room WHERE login='$row[0]'");
                grr_sql_command("DELETE FROM ".TABLE_PREFIX."_utilisateurs_groupes WHERE login='$row[0]'");
            }
        }
    }

    if ($userusager2 == "on" ) {
        $sql = "SELECT login FROM ".TABLE_PREFIX."_utilisateurs WHERE statut = 'utilisateur'";
        $res = grr_sql_query($sql);
        if ($res)
        {
            for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
            {
                grr_sql_command("DELETE FROM ".TABLE_PREFIX."_utilisateurs WHERE login='$row[0]'");
                grr_sql_command("DELETE FROM ".TABLE_PREFIX."_j_mailuser_room WHERE login='$row[0]'");
                grr_sql_command("DELETE FROM ".TABLE_PREFIX."_j_user_area WHERE login='$row[0]'");
                grr_sql_command("DELETE FROM ".TABLE_PREFIX."_j_user_room WHERE login='$row[0]'");
                grr_sql_command("DELETE FROM ".TABLE_PREFIX."_j_useradmin_area WHERE login='$row[0]'");
                grr_sql_command("DELETE FROM ".TABLE_PREFIX."_j_useradmin_site WHERE login='$row[0]'");
                grr_sql_command("DELETE FROM ".TABLE_PREFIX."_j_userbook_room WHERE login='$row[0]'");
                grr_sql_command("DELETE FROM ".TABLE_PREFIX."_utilisateurs_groupes WHERE login='$row[0]'");
            }
        }
    }

}

if ($numAction == 1) {
    grr_sql_query("UPDATE ".TABLE_PREFIX."_entry SET moderate = 2 WHERE moderate = 1");
}

$j = 0;
$listeTables = array();
while ($j < count($liste_tables))
{
    $nbEnregistrement = grr_sql_query1("SELECT count(*) AS Nb FROM ".$table_prefix.$liste_tables[$j]);
    
    $listeTables[] = array($table_prefix.$liste_tables[$j], $nbEnregistrement);
    $j++;
}





echo $twig->render($page.'.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings, 'listeTables' => $listeTables));

?>