<?php
/**
 * install_mysql.php
 * Interface d'installation de GRR pour un environnement mysql
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2023-05-26 10:30$
 * @author    Laurent Delineau & JeromeB & Yan Naessens
 * @copyright Copyright 2003-2023 Team DEVOME - JeromeB
 * @link      http://www.gnu.org/licenses/licenses.html
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 */
$grr_script_name = "install_mysql.php";

require_once("../include/config.inc.php");
require_once("../include/misc.inc.php");
require_once("../include/functions.inc.php");

$nom_fic = "../include/connect.inc.php";
// récupération des données du formulaire
$etape = getFormVar("etape","int",1);
$adresse_db = getFormVar("adresse_db","string","localhost");
$port_db = getFormVar("port_db","int",3306);
$login_db = getFormVar("login_db","string","");
$pass_db = getFormVar("pass_db","string","");
$choix_db = getFormVar("choix_db","string","");
$table_new = getFormVar("table_new","string","");
$table_prefix = getFormVar("table_prefix","string","");
$company = getFormVar('company',"string","Nom de l'établissement");
$grr_url = getFormVar('grr_url',"string","");
$webmaster_email = getFormVar('webmaster_email',"string","webmaster@grr.test");
$technical_support_email = getFormVar('technical_support_email',"string","techsupport@grr.test");
$mdp1 = getFormVar('mdp1',"string","");
$mdp2 = getFormVar('mdp2',"string","");
$email = getFormVar('email',"string","administrateur@grr.test");
// nettoyage des chaînes dans le formulaire
$adresse_db = clean_input($adresse_db);
$login_db = clean_input($login_db);
$pass_db = clean_input($pass_db);
$choix_db = clean_input($choix_db);
$table_new = clean_input($table_new);
$table_prefix = clean_input($table_prefix);
$company = clean_input($company);
$grr_url = clean_input($grr_url);
$webmaster_email = clean_input($webmaster_email);
$technical_support_email = clean_input($technical_support_email);
$mdp1 = clean_input($mdp1);
$mdp2 = clean_input($mdp2);
$email = clean_input($email);

function begin_html()
{
	echo '<div style="margin-left:15%;margin-right:15%;"><table><tr><td>';
}
function end_html()
{
	echo '</td></tr></table></div></body></html>';
}
/**
 * @param $res : MySQL query result
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
	require_once($nom_fic);
	/* fix prefix missing */
	if ( $table_prefix != NULL ) {
		$table_prefix_from_user = $table_prefix;
	} else {
		$table_prefix_from_user = false;
	}
	if ( empty($table_prefix) &&  $table_prefix_from_user !== false) {
		$table_prefix = $table_prefix_from_user;
	}
    // vérification des tables
	$db = @mysqli_connect("$dbHost", "$dbUser", "$dbPass", "$dbDb", $dbPort);
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
			if ($call_test = mysqli_query($db, "SELECT * FROM ".$table_prefix."_setting WHERE NAME='sessionMaxLength'")){
                $test2 = mysqli_num_rows($call_test);
                mysqli_free_result($call_test);
            }
            else
                $test2 = 0;
			if (($test2 != 0) && ($test1 != 'no'))
			{
				echo begin_simple_page("Installation de GRR");
				begin_html();
                if ($etape == 6)// vérifier que les personnalisations ont bien été prises en compte et lancer GRR
                {
                    if ((strlen($mdp1)>7)&&($mdp1 == $mdp2)){ // les mots de passe sont acceptables, on met à jour la table setting
                        $test = TRUE;
                        $req = "UPDATE ".$table_prefix."_setting SET value ='".$company."' WHERE ".$table_prefix."_setting.name = 'company' ";
                        $test .= mysqli_query($db, $req);
                        $req = "UPDATE ".$table_prefix."_setting SET value ='".$grr_url."' WHERE ".$table_prefix."_setting.name = 'grr_url' ";
                        $test .= mysqli_query($db, $req);
                        $req = "UPDATE ".$table_prefix."_setting SET value ='".$webmaster_email."' WHERE ".$table_prefix."_setting.name = 'webmaster_email' ";
                        $test .= mysqli_query($db, $req);
                        $req = "UPDATE ".$table_prefix."_setting SET value ='".$technical_support_email."' WHERE ".$table_prefix."_setting.name = 'technical_support_email' ";
                        $test .= mysqli_query($db, $req);
                        $mdp = md5($mdp1);
                        $req = "UPDATE ".$table_prefix."_utilisateurs SET password = '".$mdp."' WHERE ".$table_prefix."_utilisateurs.login = 'ADMINISTRATEUR' ";
                        $test .= mysqli_query($db, $req); 
                        $req = "UPDATE ".$table_prefix."_utilisateurs SET email = '".$email."' WHERE ".$table_prefix."_utilisateurs.login = 'ADMINISTRATEUR' ";
                        $test .= mysqli_query($db, $req);
                        if ($test)
                        {
                            echo "<br /><h2>Dernière étape : C'est terminé !</h2>";
                            echo "<p>Vous pouvez maintenant commencer à utiliser le système de réservation de ressources ...</p>";
                            echo "<p>Pour vous connecter la première fois en tant qu'administrateur, utilisez le nom de connexion <b>\"ADMINISTRATEUR\"</b> et le mot de passe renseigné à l'étape précédente</p>";
                                        echo "<br /><center><a href = '../login.php'>Se connecter à GRR</a></center>";
                        }
                        else
                        {
                            echo "<p>Les personnalisations ont échoué. Vérifiez le serveur de bases de données ou revenez à l'étape précédente, ou recommencez l'installation.</p>";
                        }
                    }
                    else // mots de passe non acceptables
                    {
                        echo "Les mots de passe sont trop courts ou différents, veuillez retourner à l'étape précédente";
                        echo '<form action="install_mysql.php" method="POST" role="form">';
                        echo "<input type='hidden' name='etape' value='5' />";
                        echo "<input type='hidden' name='adresse_db' value='$adresse_db' />";
                        echo "<input type='hidden' name='port_db' value='$port_db' />";
                        echo "<input type='hidden' name='login_db' value='$login_db' />";
                        echo "<input type='hidden' name='pass_db' value='$pass_db' />";
                        echo "<input type='hidden' name='choix_db' value='$choix_db' />";
                        echo "<input type='hidden' name='table_prefix' value='$table_prefix' />";
                        echo "<input type=\"hidden\" name=\"company\" value=\"$company\" />";
                        echo "<input type='hidden' name='grr_url' value='$grr_url'/>";
                        echo "<input type='hidden' name='webmaster_email' value='$webmaster_email' />";
                        echo "<input type='hidden' name='technical_support_email' value='$technical_support_email' />";
                        echo "<input type='hidden' name='email' value='$email' />";
                        echo "<div style=\"text-align:right;\">";
                        echo '<input type="submit" name="Retour5" value="<< Précédent" />';
                        echo "</div>";
                        echo "</form>";
                    }
                }
				else if ($etape == 5)
				{// personnalisation de GRR, passer à l'étape 6
                    echo '<h2>Cinquième étape : Personnalisation de votre GRR</h2>';
                    echo '<form action="install_mysql.php" method="POST" role="form">';
                    echo "<input type='hidden' name='etape' value='6' />";
                    echo "<input type='hidden' name='adresse_db' value='$adresse_db' />";
                    echo "<input type='hidden' name='port_db' value='$port_db' />";
                    echo "<input type='hidden' name='login_db' value='$login_db' />";
                    echo "<input type='hidden' name='pass_db' value='$pass_db' />";
                    echo "<input type='hidden' name='choix_db' value='$choix_db' />";
                    echo "<input type='hidden' name='table_prefix' value='$table_prefix' />";
                    echo "<div>";
                    echo "<p>Vous pourrez modifier les informations dans la configuration générale après avoir terminé l'installation.</p>";
                    echo "<p><label for='company'>Nom de l'établissement : </label><input type=\"text\" name=\"company\" value=\"$company\" /></p>";
                    echo "<p><label for='grr_url'>URL de GRR : </label><input type='text' name='grr_url' value='$grr_url'/></p>";
                    echo "<p><label for='webmaster_email'>Adresse mail du webmestre : </label><input type='email' name='webmaster_email' value='$webmaster_email' /></p>";
                    echo "<p><label for='technical_support_email'>Adresse mail du support technique : </label><input type='email' name='technical_support_email' value='$technical_support_email' /></p>";
                    echo "<h3>Le compte administrateur : </h3>";
                    echo "<p>Identifiant du compte Administrateur : ADMINISTRATEUR</p>";
                    echo "<p><label for='mdp1'>Mot de passe : </label><input type='password' name='mdp1' required /></p>";
                    echo "<p><label for='mdp2'>Confirmer le mot de passe : </label><input type='password' name='mdp2' required /></p>";
                    echo "<p><label for='email'>Adresse mail de l'administrateur : </label><input type='email' name='email' value='$email' /></p>";
                    echo "</div>";
                    echo "<div style=\"text-align:right;\">";
                    echo '<input type="submit" name="Valider" value="Suivant >> " />';
                    echo "</div>";
                    echo "</form>";
				}
				else
				{
					echo "<h2>Espace interdit - GRR est déjà installé.</h2>";
				}
				end_html();
				die();
			}
			else
			{
				if ($etape == 5)
				{
					echo begin_simple_page("Installation de GRR");
					begin_html();
					if ($test1 == 'no')
					{
						echo "<p>L'installation n'a pas pu se terminer normalement : des tables sont manquantes.".$tableManquantes."</p>";
					}
					if ($test2 == 0)
					{
						echo "<p>L'installation n'a pas pu se terminer normalement : la table ".$table_prefix."_setting est vide ou bien n'existe pas.</p>";
					}
					end_html();
				}
			}
		}
	}
}
if ($etape == 4)
{
	echo begin_simple_page("Installation de GRR");
	begin_html();
	echo "<br /><h2>Quatrième étape : Création des tables de la base</h2>";
	$db = mysqli_connect("$adresse_db", "$login_db", "$pass_db", "", "$port_db");
    if (!$db){ 
        echo "Erreur de connexion à la base de données\n Reprenez à l'étape précédente";
    }
    else {
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
            $fd = fopen("tables.my.sql", "r");
            $result_ok = 'yes';
            mysqli_set_charset( $db, 'utf8mb4');
            while (!feof($fd))
            {
                $query = fgets($fd, 5000);
                $query = trim($query);
                $query = preg_replace("/DROP TABLE IF EXISTS grr/","DROP TABLE IF EXISTS ".$table_prefix,$query);
                $query = preg_replace("/CREATE TABLE grr/","CREATE TABLE ".$table_prefix,$query);
                $query = preg_replace("/INSERT INTO grr/","INSERT INTO ".$table_prefix,$query);

                if ($query != '')
                {
                    $reg = mysqli_query($db, $query);
                    if (!$reg)
                    {
                        echo "<br /><font color=\"red\">ERROR</font> : '$query'";
                        $result_ok = 'no';
                    }
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
                    $conn .= "\$dbDb=\"$sel_db\";\n";
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
                    echo "<b>La structure de votre base de données est installée.</b><br />Vous pouvez passer à l'étape suivante.";
                    echo "<form action='install_mysql.php' method='POST'>";
                    echo "<input type='hidden' name='etape' value='5' />";
                    echo "<div style=\"text-align:right;\"><input type='submit' class='fondl' name='Valider' value='Suivant &gt;&gt;' /><div>";
                    echo "</form>";
                }
            }
            if (($result_ok != 'yes') || ($ok != 'yes'))
            {
                echo "<p><b>L'opération a échoué.</b> Retournez à la page précédente, sélectionnez une autre base ou créez-en une nouvelle. Vérifiez les informations fournies par votre hébergeur.</p>";
            }
        }
        else
        {
            echo "<p><b>Impossible de sélectionner la base. GRR n'a peut-être pas pu créer la base.</b></p>";
        }
    }
	end_html();
}
else if ($etape == 3)
{
	echo begin_simple_page("Installation de GRR");
	begin_html();
	echo "<br /><h2>Troisième étape : Choix de votre base</h2>\n";
	echo "<form action='install_mysql.php' method='POST'><div>\n";
	echo "<input type='hidden' name='etape' value='4' />\n";
	echo "<input type='hidden' name='adresse_db'  value=\"$adresse_db\" size='40' />\n";
    echo "<input type='hidden' name='port_db' value=\"$port_db\" />\n";
	echo "<input type='hidden' name='login_db' value=\"$login_db\" />\n";
	echo "<input type='hidden' name='pass_db' value=\"$pass_db\" />\n";
	$db = mysqli_connect("$adresse_db","$login_db","$pass_db","","$port_db");
	$result = mysqli_query($db, "SHOW DATABASES");
	echo "<fieldset><label><b>Choisissez votre base :</b><br /></label>\n";
	if ($result && (($n = mysqli_num_rows($result)) > 0))
	{
		echo "<p><b>Le serveur $dbsys contient plusieurs bases de données.<br />Sélectionnez celle dans laquelle vous voulez implanter GRR</b></p>\n";
		echo "<ul>\n";
		$bases = "";
		$checked = FALSE;
		for ($i = 0; $i < $n; $i++)
		{
			$table_nom = mysqli_result($result, $i);
			$base = "<li><input name=\"choix_db\" value=\"".$table_nom."\" type=\"radio\" id='tab$i'";
			$base_fin = " /><label for='tab$i'>".$table_nom."</label></li>\n";
			if ($table_nom == $login_db)
			{
				$bases = "$base checked=\"checked\"".$bases;
				$checked = TRUE;
			}
			else
			{
				$bases .= "$base$base_fin\n";
			}
		}
		echo $bases."</ul>\n";
		echo "ou... ";
	}
	else
	{
		echo "<b>Le programme d'installation n'a pas pu lire les noms des bases de données installées.</b>Soit aucune base n'est disponible, soit la fonction permettant de lister les bases a été désactivée pour des raisons de sécurité.<br />\n";
		if ($login_db)
		{
			echo "Dans la seconde alternative, il est probable qu'une base portant votre nom de login soit utilisable :";
			echo "<ul>\n";
			echo "<input name=\"choix_db\" value=\"".$login_db."\" type=\"radio\" id=\"stand\" checked=\"checked\" />\n";
			echo "<label for='stand'>".$login_db."</label><br />\n";
			echo "</ul>\n";
			echo "ou... ";
			$checked = TRUE;
		}
	}
	echo "<input name=\"choix_db\" value=\"new_grr\" type=\"radio\" id='nou'";
	if (!$checked)
		echo " checked=\"checked\"";
	echo " />\n<label for='nou'>Créer une nouvelle base de données :</label>\n";
	echo "<input type='text' name='table_new' class='fondo' value=\"grr\" size='20' /></fieldset>\n";
	echo "<br /><fieldset><label><b>Préfixe des tables :</b><br /></label>\n";
	echo "Vous pouvez modifier le préfixe du nom des tables de données (ceci est indispensable lorsque l'on souhaite installer plusieurs sites GRR dans la même base de données). Ce préfixe s'écrit en <b>lettres minuscules, non accentuées, et sans espace</b>.";
	echo "<br /><input type='text' name='table_prefix' class='fondo' value=\"grr\" size='10' />\n";
	echo "</fieldset>\n";
	echo "<br /><b>Attention</b> : lors de la prochaine étape :\n";
	echo "<ul>\n";
	echo "<li>le fichier \"".$nom_fic."\" sera actualisé avec les données que vous avez fourni,</li>\n";
	echo "<li>les tables GRR seront créées dans la base sélectionnée. Si celle-ci contient déjà des tables GRR, ces tables, ainsi que les données qu'elles contiennent, seront supprimées et remplacées par une nouvelle structure.</li>\n</ul>\n";
	echo "<div style=\"text-align:right;\"><input type='submit' class='fondl' name='Valider' value='Suivant &gt;&gt;' /></div>\n";
	echo "</div></form>\n";
	end_html();
}
else if ($etape == 2)
{
	echo begin_simple_page("Installation de GRR");
	begin_html();
	echo "<br /><h2>Deuxième étape : Essai de connexion au serveur $dbsys</h2>\n";
	mysqli_report(MYSQLI_REPORT_OFF);
	$db = @mysqli_connect($adresse_db,$login_db,$pass_db,"",$port_db);
	$db_connect = mysqli_connect_errno();
	if (($db_connect != "0") && (!$db))
	{
		if ($adresse_db == "localhost")
			$adresse_db = "";
		$db = @mysqli_connect($adresse_db,$login_db,$pass_db,"",$port_db);
		$db_connect = mysqli_connect_errno();
	}
	if (($db_connect=="0") && $db)
	{
		echo "<b>La connexion a réussi.</b><p> Vous pouvez passer à l'étape suivante.</p>\n";
		echo "<form action='install_mysql.php' method='POST'>\n";
		echo "<div><input type='hidden' name='etape' value='3' />\n";
		echo "<input type='hidden' name='adresse_db'  value=\"$adresse_db\" size='40' />\n";
        echo "<input type='hidden' name='port_db' value=\"$port_db\" />\n";
		echo "<input type='hidden' name='login_db' value=\"$login_db\" />\n";
		echo "<input type='hidden' name='pass_db' value=\"$pass_db\" />\n";
		echo "<div style=\"text-align:right;\"><input type='submit' class='fondl' name='Valider' value='Suivant &gt;&gt;' /></div>\n";
		echo "</div></form>\n";
	}
	else
	{
		echo "<b>La connexion au serveur $dbsys a échoué.</b>";
		echo "<p>Revenez à la page précédente, et vérifiez les informations que vous avez fournies.</p>";
	}
	end_html();
}
else if ($etape == 1)
{
	echo begin_simple_page("Installation de GRR");
	begin_html();
	echo "<br /><h2>Première étape : la connexion $dbsys</h2>";
	echo "<p>Vous devez avoir en votre possession les codes de connexion au serveur $dbsys. Si ce n'est pas le cas, contactez votre hébergeur ou bien l'administrateur technique du serveur sur lequel vous voulez implanter GRR.</p>";
	/* $adresse_db = 'localhost';
	$login_db = '';
	$pass_db = '';
    $port_db = 3306;*/
	echo "<form action='install_mysql.php' method='POST'>\n";
	echo "<div><input type='hidden' name='etape' value='2' />\n";
	echo "<fieldset><label><b>Adresse de la base de données</b><br /></label>\n";
	echo "(Souvent cette adresse correspond à celle de votre site, parfois elle correspond à la mention &laquo;localhost&raquo;, parfois elle est laissée totalement vide.)<br />\n";
	echo "<input type='text' name='adresse_db' class='formo' value=\"$adresse_db\" size='40' /></fieldset>\n";
    echo "<fieldset><label><b>Port du serveur de la base de données</b><br /></label>\n";
	echo "(Si vous ne le connaissez pas, laissez la valeur par défaut.)<br />\n";
	echo "<input type='text' name='port_db' class='formo' value=\"$port_db\" size='40' /></fieldset>\n";
	echo "<fieldset><label><b>Le login de connexion</b><br /></label>\n";
	echo "<input type='text' name='login_db' class='formo' value=\"$login_db\" size='40' /></fieldset>\n";
	echo "<fieldset><label><b>Le mot de passe de connexion</b><br /></label>\n";
	echo "<input type='password' name='pass_db' class='formo' value=\"$pass_db\" size='40' /></fieldset>\n";
	echo "<div style=\"text-align:right;\"><input type='submit' class='fondl' name='Valider' value='Suivant &gt;&gt;' /></div>\n";
	echo "</div></form>\n";
	end_html();
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
		echo begin_simple_page("Installation de GRR");
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
		echo "<p>Vous pouvez par exemple utilisez votre client FTP afin de régler ce problème ou bien contactez l'administrateur technique. Une fois cette manipulation effectuée, vous pourrez continuer.</p>";
		echo "<p><form action='install_mysql.php' method='POST'>";
		echo "<input type='hidden' name='etape' value='' />";
		echo "<input type='submit' class='fondl' name='Continuer' />";
		echo "</form>";
		end_html();
	}
	else
	{
		header("Location: ./install_mysql.php?etape=1");
	}
}
?>
