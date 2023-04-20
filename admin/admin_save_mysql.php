<?php
/**
 * admin_save_mysql.php
 * Script de sauvegarde de la base de donnée mysql
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2021-03-13 11:39$
 * @author    Laurent Delineau & JeromeB & Yan Naessens
 * @copyright Copyright 2003-2021 Team DEVOME - JeromeB
 * @link      http://www.gnu.org/licenses/licenses.html
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 */

$grr_script_name = "admin_save_mysql.php";
if ((!isset($_GET['mdp'])) && (!isset($argv[1])) && (!isset($_GET['flag_connect'])))
{
	echo "Il manque des arguments pour executer ce script. Reportez-vous a la documentation.";
	die();
}
if ((!isset($_GET['mdp'])) && isset($argv[1]))
	$_GET['mdp'] = $argv[1];
if (isset($_GET['mdp']))
{
	include(dirname(__FILE__).'/../include/connect.inc.php');
	include(dirname(__FILE__)."/../include/config.inc.php");
	include(dirname(__FILE__)."/../include/misc.inc.php");
	include(dirname(__FILE__)."/../include/functions.inc.php");
	include(dirname(__FILE__)."/../include/mysql.inc.php"); // remplace $dbsys par mysql, puisque c'est le seul système de BDD compatible avec GRR !
	include(dirname(__FILE__)."/../include/settings.class.php");
	if (!Settings::load())
		die("Erreur chargement settings");

	if ((($_GET['mdp'] != Settings::get("motdepasse_backup")) || (Settings::get("motdepasse_backup")== '' )))
	{
		if (!isset($argv[1]))
			echo start_page_wo_header("backup", $page = "no_session")."<p>";
		echo "Le mot de passe fourni est invalide.";
		if (!isset($argv[1]))
		{
			echo "</p>";
			end_page();
		}
		die();
	}
}
else
{
	include(dirname(__FILE__)."/../include/admin.inc.php");
	$back = (isset($_SERVER['HTTP_REFERER']))? htmlspecialchars_decode($_SERVER['HTTP_REFERER'], ENT_QUOTES) : "./admin_accueil.php" ;
	if (authGetUserLevel(getUserName(),-1) < 6)
	{
		showAccessDenied($back);
		exit();
	}
}

function php_version()
{
	preg_match('`([0-9]{1,2}).([0-9]{1,2})`', phpversion(), $match);
	if (isset($match) && !empty($match[1]))
	{
		if (!isset($match[2]))
			$match[2] = 0;
	}
	if (!isset($match[3]))
		$match[3] = 0;
	return $match[1] . "." . $match[2] . "." . $match[3];
}

function mysql_version()
{
	$result = mysqli_query($GLOBALS['db_c'], 'SELECT VERSION() AS version');
	if ($result != FALSE && mysqli_num_rows($result) > 0)
	{
		$row = mysqli_fetch_array($result);
		$match = explode('.', $row['version']);
	}
	else
	{
		$result = mysqli_query($GLOBALS['db_c'], 'SHOW VARIABLES LIKE \'version\'');
		if ($result != FALSE && mysqli_num_rows($result) > 0)
		{
			$row = mysqli_fetch_row($result);
			$match = explode('.', $row[1]);
		}
	}
	if (!isset($match) || !isset($match[0]))
		$match[0] = 3;
	if (!isset($match[1]))
		$match[1] = 21;
	if (!isset($match[2]))
		$match[2] = 0;
	return $match[0] . "." . $match[1] . "." . $match[2];
}

$nomsql = $dbDb."_le_".date("Y_m_d_\a_H\hi").".sql";
$now = date('D, d M Y H:i:s') . ' GMT';

header('Content-Type: text/x-csv');
header('Expires: ' . $now);
if (isset($_SERVER['HTTP_USER_AGENT']))
{
	if (preg_match('`MSIE`', $_SERVER['HTTP_USER_AGENT']))
	{
		header('Content-Disposition: inline; filename="' . $nomsql . '"');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Pragma: public');
	}
	else
	{
		header('Content-Disposition: attachment; filename="' . $nomsql . '"');
		header('Pragma: no-cache');
	}
}
$fd = '';
$fd .= "#**************** BASE DE DONNEES ".$dbDb." ****************"."\n".date("\#\ \L\\e\ \:\ d\ m\ Y\ \a\ H\h\ i")."\n";
if (isset($_SERVER['SERVER_NAME']))
	$fd .= "# Serveur : ".$_SERVER['SERVER_NAME']."\n";
$fd .= "# Version PHP : " . php_version()."\n";
$fd .= "# Version mySQL : " . mysql_version()."\n";
$fd .= "# Version GRR : " . affiche_version()."\n";
if (isset($_SERVER['REMOTE_ADDR']))
	$fd .= "# IP Client : ".$_SERVER['REMOTE_ADDR']."\n";
$fd .= "# Fichier SQL compatible PHPMyadmin\n#\n";
$fd .= "# ******* debut du fichier ********\n";
$j = '0';
while ($j < count($liste_tables))
{
	$temp = $table_prefix.$liste_tables[$j];
	if ($structure)
	{
		$fd .= "#\n# Structure de la table $temp\n#\n";
		$fd .= "DROP TABLE IF EXISTS `$temp`;\n";
		// requete de creation de la table
		$query = "SHOW CREATE TABLE $temp";
		$resCreate = mysqli_query($GLOBALS['db_c'], $query);
        if (!$resCreate)
            $fd.="Problème à la création de $temp !\n";
		else {
            $row = mysqli_fetch_array($resCreate);
            $schema = $row[1].";";
            $fd.="$schema\n";
        }
	}
	//On ne sauvegarde pas les données de la table ".TABLE_PREFIX."_log
	if ($donnees && $temp!="".TABLE_PREFIX."_log")
	{
		// les données de la table
		$fd.="#\n# Données de $temp\n#\n";
		$query = "SELECT * FROM $temp";
		$resData = mysqli_query($GLOBALS['db_c'], $query);
		//peut survenir avec la corruption d'une table, on prévient
		if (!$resData)
			$fd.="Problème avec les données de $temp, corruption possible !\n";
		else
		{
			if (mysqli_num_rows($resData) > 0)
			{
				$sFieldnames = "";
				$num_fields = mysqli_field_count($GLOBALS['db_c']);
				if ($insertComplet)
				{
					for ($k = 0; $k < $num_fields; $k++)
					{
						$sFieldnames .= "`".mysqli_fetch_field_direct($resData, $k)->name ."`";
						//on ajoute à la fin une virgule si nécessaire
						if ($k<$num_fields-1)
							$sFieldnames .= ", ";
					}
					$sFieldnames = "($sFieldnames)";
				}
				$sInsert = "INSERT INTO $temp $sFieldnames values ";
				while ($rowdata = mysqli_fetch_row($resData))
				{
					$lesDonnees = "";
					for ($mp = 0; $mp < $num_fields; $mp++)
					{
						$lesDonnees .= "'" . mysqli_real_escape_string($GLOBALS['db_c'], $rowdata[$mp]) . "'";
						//on ajoute à la fin une virgule si nécessaire
						if ($mp<$num_fields-1)
							$lesDonnees .= ", ";
					}
					$lesDonnees = "$sInsert($lesDonnees);\n";
					$fd.="$lesDonnees";
				}
			}
		}
	}
	$j++;
}
$fd.="#********* fin du fichier ***********";
echo $fd;
?>