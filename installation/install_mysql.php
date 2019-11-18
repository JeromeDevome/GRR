<?php
/**
 * install_mysql.php
 * Interface d'installation de GRR pour un environnement mysql
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2019-09-09 12:20$
 * @author    Laurent Delineau & JeromeB & Yan Naessens
 * @copyright Copyright 2003-2019 Team DEVOME - JeromeB
 * @link      http://www.gnu.org/licenses/licenses.html
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 */

require_once("../include/config.inc.php");
require_once("../include/misc.inc.php");
require_once("../include/functions.inc.php");

require '../vendor/autoload.php';
require '../include/twiggrr.class.php';

// Template Twig
$loader = new Twig_Loader_Filesystem(__DIR__ . '/templates');
$twig = new Twig_Environment($loader,['charset']);
$twig->addExtension(new TwigGRR());

$nom_fic = "../include/connect.inc.php";
$etape = isset($_GET["etape"]) ? $_GET["etape"] : NULL;
$adresse_db = isset($_GET["adresse_db"]) ? $_GET["adresse_db"] : NULL;
$port_db = isset($_GET["port_db"]) ? $_GET["port_db"] : NULL;
$login_db = isset($_GET["login_db"]) ? $_GET["login_db"] : NULL;
$pass_db = isset($_GET["pass_db"]) ? $_GET["pass_db"] : NULL;
$choix_db = isset($_GET["choix_db"]) ? $_GET["choix_db"] : NULL;
$table_new = isset($_GET["table_new"]) ? $_GET["table_new"] : NULL;
$table_prefix = isset($_GET["table_prefix"]) ? $_GET["table_prefix"] : NULL;

// Pour cette page uniquement, on désactive l'UTF8 et on impose l'ISO-8859-1
$unicode_encoding = 1;
$charset_html = "ISO-8859-1";

$d['dbsys']			= $dbsys;
$d['nom_fic']		= $nom_fic;
$d['adresse_db']	= $adresse_db;
$d['port_db']		= $port_db;
$d['login_db']		= $login_db;
$d['pass_db']		= $pass_db;
$d['choix_db']		= $choix_db;
$d['table_new']		= $table_new;
$d['table_prefix']	= $table_prefix;


function begin_html()
{
	echo '<div style="margin-left:15%;margin-right:15%;"><table><tr><td>';
}
function end_html()
{
	echo '</td></tr></table></div></body></html>';
}
/**
 * @param integer $row
 */
function mysqli_result($res, $row, $field = 0)
{
	$res->data_seek($row);
	$datarow = $res->fetch_array();
	return $datarow[$field];
}
if (@file_exists($nom_fic))
{
	/* fix prefix missing */
	if ( $table_prefix != NULL ) {
		$table_prefix_from_user = $table_prefix;
	} else {
		$table_prefix_from_user = false;
	}
	require_once($nom_fic);
	if ( empty($table_prefix) &&  $table_prefix_from_user !== false) {
		$table_prefix = $table_prefix_from_user;
	}
	$db = @mysqli_connect("$dbHost", "$dbUser", "$dbPass", "$dbDb", "$dbPort");
	if ($db)
	{
		if (mysqli_select_db($db, "$dbDb"))
		{
			// Premier test
			$j = '0';
			$test1 = 'yes';
			$total = count($liste_tables);
			$tableManquantes = "";
			while ($j < $total)
			{
				$test = mysqli_query($db, "SELECT count(*) FROM ".$table_prefix.$liste_tables[$j]);
				if (!$test)
				{
					$tableManquantes .= " ". $table_prefix.$liste_tables[$j];
					$correct_install='no';
					$test1 = 'no';
				}
				$j++;
			}
			$call_test = mysqli_query($db, "SELECT * FROM ".$table_prefix."_setting WHERE NAME='sessionMaxLength'");
			$test2 = mysqli_num_rows($call_test);

			$d['etape'] = 5;

			if ($test1 == 'no'){
				$d['erreurE5'] = 1;
				$d['tableManquantes'] = $tableManquantes;
			} elseif($test2 == 0){
				$d['erreurE5'] = 2;
			} else{
				if ($etape != 5)
				{
					$d['erreurE5'] = 3;
				}
			}

			echo $twig->render('installation_e5.twig', array('d' => $d));
		}
	}
}
if ($etape == 4)
{

	if(isset($_GET['mdp1']) && isset($_GET['mdp2']) && strlen($_GET['mdp1']) > 7 && $_GET['mdp1'] == $_GET['mdp2']){

		$company = isset($_GET["company"]) ? $_GET["company"] : 'Nom du GRR';
		$grr_url = isset($_GET["grr_url"]) ? $_GET["grr_url"] : 'https://mygrr.net/';
		$webmaster_email = isset($_GET["webmaster_email"]) ? $_GET["webmaster_email"] : 'webmaster_grr@test.fr';
		$support_email = isset($_GET["technical_support_email"]) ? $_GET["technical_support_email"] : 'support_grr@test.fr';
		$mdp = isset($_GET["mdp1"]) ? md5($_GET["mdp1"]) : 'azerty';
		$email = isset($_GET["email"]) ? $_GET["email"] : 'testgrr@test.fr';

		$db = mysqli_connect("$adresse_db", "$login_db", "$pass_db", "", "$port_db");

		if (mysqli_select_db($db, "$choix_db"))
		{
			$d['etape'] = 4;

			$fd = fopen("tables.my.sql", "r");
			$result_ok = 'yes';
			while (!feof($fd))
			{
				$query = fgets($fd, 5000);
				$query = trim($query);
				$query = preg_replace("/DROP TABLE IF EXISTS grr/","DROP TABLE IF EXISTS ".$table_prefix,$query);
				$query = preg_replace("/CREATE TABLE grr/","CREATE TABLE ".$table_prefix,$query);
				$query = preg_replace("/INSERT INTO grr/","INSERT INTO ".$table_prefix,$query);
				$query = preg_replace("/VariableInstal01/",$company,$query);
				$query = preg_replace("/VariableInstal02/",$grr_url,$query);
				$query = preg_replace("/VariableInstal03/",$webmaster_email,$query);
				$query = preg_replace("/VariableInstal04/",$support_email,$query);
				$query = preg_replace("/VariableInstal05/",$mdp,$query);
				$query = preg_replace("/VariableInstal06/",$email,$query);

				if ($query != '')
				{
					$reg = mysqli_query($db, $query);
					
					if (!$reg)
					{
						echo "<br /><font color=\"red\">ERROR</font> : '$query'";
						$result_ok = 'no';
					}
					//else
					//	echo "<br /><font color=\"green\">OK</font> : '$query'";
				}
			}
			fclose($fd);
			if ($result_ok == 'yes')
			{
				$ok = 'yes';
				if (@file_exists($nom_fic))
					unlink($nom_fic);
				$f = @fopen($nom_fic, "wb");
				if (!$f)
				{
					$ok = 'no';
				}
				else
				{
					$conn = "<"."?php\n";
					$conn .= "# Les quatre lignes suivantes sont à modifier selon votre configuration\n";
					$conn .= "# ligne suivante : le nom du serveur qui herberge votre base sql.\n";
					$conn .= "# Si c'est le même que celui qui heberge les scripts, mettre \"localhost\"\n";
					$conn .= "\$dbHost=\"$adresse_db\";\n";
					$conn .= "# ligne suivante : le nom de votre base sql\n";
					$conn .= "\$dbDb=\"$choix_db\";\n";
					$conn .= "# ligne suivante : le nom de l'utilisateur sql qui a les droits sur la base\n";
					$conn .= "\$dbUser=\"$login_db\";\n";
					$conn .= "# ligne suivante : le mot de passe de l'utilisateur sql ci-dessus\n";
					$conn .= "\$dbPass=\"$pass_db\";\n";
					$conn .= "# ligne suivante : préfixe du nom des tables de données\n";
					$conn .= "\$table_prefix=\"$table_prefix\";\n";
					$conn .= "# ligne suivante : Port MySQL laissé par défaut\n";
					$conn .= "\$dbPort=\"$port_db\";\n";
					$conn .= "# ligne suivante : adaptation EnvOLE\n";
					$conn .= "\$apikey=\"mypassphrase\"\n";
					$conn .= "?".">";
					@fputs($f, $conn);
					if (!@fclose($f))
						$ok = 'no';
				}
				if ($ok == 'yes')
				{
					$d['etape'] = 4;

					echo $twig->render('installation_e4.twig', array('d' => $d));
				}
			}
			if (($result_ok != 'yes') || ($ok != 'yes'))
			{
				$d['etape'] = 2;
				$d['erreurCreationBase'] = 1;

				echo $twig->render('installation_e2.twig', array('d' => $d));
			}
		}
		else
		{
			$d['etape'] = 2;
			$d['erreurSelectBase'] = 1;

			echo $twig->render('installation_e2.twig', array('d' => $d));
		}
	} else{
		$d['etape'] = 3;
		$d['erreurMDP'] = 1;

		echo $twig->render('installation_e3.twig', array('d' => $d));
	}
}
else if ($etape == 3)
{

	$db = mysqli_connect("$adresse_db", "$login_db", "$pass_db", "", "$port_db");
	if ($choix_db == "new_grr")
	{
		$sel_db = $table_new;
		$result = mysqli_query($db, "CREATE DATABASE $sel_db;");
	}
	else
	{
		$sel_db = $choix_db;
	}
	if (mysqli_select_db($db, "$sel_db"))
	{
		$d['etape'] = 3;
		$d['SelectBase'] = 1;
		$d['choix_db'] = $sel_db;
		$d['adresseServeur'] = explode('installation/', "https://".$_SERVER['SERVER_NAME'].$_SERVER["REQUEST_URI"])[0];

		echo $twig->render('installation_e3.twig', array('d' => $d));
	}
	else
	{
		$d['etape'] = 2;
		$d['erreurSelectBase'] = 1;

		echo $twig->render('installation_e2.twig', array('d' => $d));
	}

	
}
else if ($etape == 2)
{

	$db = @mysqli_connect($adresse_db,$login_db,$pass_db,"",$port_db);
	$db_connect = mysqli_errno($db);
	if (($db_connect != "0") && (!$db))
	{
		if ($adresse_db == "localhost")
			$adresse_db = "";
		$db = mysqli_connect($adresse_db,$login_db,$pass_db,"",$port_db);
		$db_connect = mysqli_errno($db);
	}
	if (($db_connect=="0") && $db)
	{
		$d['etape']				= 2;
		$d['connexionReussi']	= 1;


		$db = mysqli_connect("$adresse_db","$login_db","$pass_db","","$port_db");
		$result = mysqli_query($db, "SHOW DATABASES");
		if ($result && (($n = mysqli_num_rows($result)) > 0))
		{
			$d['lectureBase'] = 1;
			$bases = "";
			for ($i = 0; $i < $n; $i++)
			{
				$table_nom = mysqli_result($result, $i);
				$bases .= "<li><input name=\"choix_db\" value=\"".$table_nom."\" type=\"radio\" id='tab$i' /> <label for='tab$i'>".$table_nom."</label></li>\n";
			}
			$d['bases'] = $bases;
		}
		else
		{
			$d['lectureBase'] = 0;
		}

		echo $twig->render('installation_e2.twig', array('d' => $d));
	}
	else
	{
		$d['etape'] = 1;
		$d['connexionEchoue'] = 1;

		echo $twig->render('installation_e1.twig', array('d' => $d));
	}
}
else if ($etape == 1)
{
	$d['etape'] = 1;

	echo $twig->render('installation_e1.twig', array('d' => $d));
}
else if ($etape == 0)
{
	$d['etape'] = 0;

	echo $twig->render('installation.twig', array('d' => $d));
}
else if (!$etape)
{
	$erreur = '';
	if (@file_exists($nom_fic))
	{
		$f = @fopen($nom_fic, "r+");
		if (!$f)
			$erreur = "<p>Le fichier \"".$nom_fic."\" n'est pas accessible en écriture.</p>";
	}
	else
	{
		$f = @fopen($nom_fic, "w");
		if (!$f)
			$erreur = "<p>Impossible de créer le fichier \"".$nom_fic."\".</p>";
	}
	if ($f)
	{
		if (!@fclose($f))
			$erreur = "<p>Impossible de sauvegarder le fichier \"".$nom_fic."\".</p>";
	}
	if ($erreur != '')
	{
		echo begin_page("Installation de GRR");
		begin_html();
		echo "<h2>Installation de la base $dbsys : problème de droits d'accès</h2>";
		echo $erreur;
		if (@file_exists($nom_fic))
			echo "<p>Vous pouvez également renseigner manuellement le fichier \"".$nom_fic."\".</p>";
		else if (@file_exists($nom_fic.".ori"))
		{
			echo "<p>Vous pouvez renommer manuellement le fichier \"".$nom_fic.".ori\" en \"".$nom_fic."\", et lui donner les droits suffisants.</p>";
			echo "<p>Une fois le fichier \"".$nom_fic.".ori\" renommé en \"".$nom_fic."\", vous pouvez également renseigner manuellement le fichier \"".$nom_fic."\".</p>";
		}
		echo "<p>Vous pouvez par exemple utiliser votre client FTP afin de régler ce problème ou bien contacter l'administrateur technique. Une fois cette manipulation effectuée, vous pourrez continuer.</p>";
		echo "<p><form action='install_mysql.php' method='get'>";
		echo "<input type='hidden' name='etape' value='' />";
		echo "<input type='submit' class='fondl' name='Continuer' />";
		echo "</form>";
		end_html();
	}
	else
	{
		header("Location: ./install_mysql.php?etape=0");
	}
}
?>
