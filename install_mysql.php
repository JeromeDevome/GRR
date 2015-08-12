<?php
/**
 * install_mysql.php
 * Interface d'installation de GRR pour un environnement mysql
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2009-10-09 07:55:48 $
 * @author    Laurent Delineau <laurent.delineau@ac-poitiers.fr>
 * @copyright Copyright 2003-2008 Laurent Delineau
 * @link      http://www.gnu.org/licenses/licenses.html
 * @package   root
 * @version   $Id: install_mysql.php,v 1.9 2009-10-09 07:55:48 grr Exp $
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
require_once("include/config.inc.php");
require_once("include/misc.inc.php");
require_once("include/functions.inc.php");
require_once("include/twigInit.php");
$nom_fic = "include/connect.inc.php";
$etape = isset($_GET["etape"]) ? $_GET["etape"] : NULL;
$adresse_db = isset($_GET["adresse_db"]) ? $_GET["adresse_db"] : NULL;
$login_db = isset($_GET["login_db"]) ? $_GET["login_db"] : NULL;
$pass_db = isset($_GET["pass_db"]) ? $_GET["pass_db"] : NULL;
$choix_db = isset($_GET["choix_db"]) ? $_GET["choix_db"] : NULL;
$table_new = isset($_GET["table_new"]) ? $_GET["table_new"] : NULL;
$table_prefix = isset($_GET["table_prefix"]) ? $_GET["table_prefix"] : NULL;

// Pour cette page uniquement, on désactive l'UTF8 et on impose l'ISO-8859-1
$unicode_encoding = 1;
$charset_html = "utf-8";
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
	require_once("include/connect.inc.php");
	if ( empty($table_prefix) &&  $table_prefix_from_user !== false) {
		$table_prefix = $table_prefix_from_user;
	}
	$db = @mysqli_connect("$dbHost", "$dbUser", "$dbPass", "$dbPort");
	if ($db)
	{
		if (mysqli_select_db($db, "$dbDb"))
		{
			// Premier test
			$j = '0';
			$test1 = 'yes';
			$total = count($liste_tables);
			while ($j < $total)
			{
				$test = mysqli_query($db, "SELECT count(*) FROM ".$table_prefix.$liste_tables[$j]);
				if (!$test)
				{
					$correct_install='no';
					$test1 = 'no';
				}
				$j++;
			}
			$call_test = mysqli_query($db, "SELECT * FROM ".$table_prefix."_setting WHERE NAME='sessionMaxLength'");
			$test2 = mysqli_num_rows($call_test);
			if (($test2 != 0) && ($test1 != 'no'))
			{
				echo begin_page("Installation de GRR");
				begin_html();
				if ($etape == 5)
				{
					echo "<br /><h2>Dernière étape : C'est terminé !</h2>";
					echo "<p>";
					echo "<p>Vous pouvez maintenant commencer à utiliser le système de réservation de ressources ...</p>";
					echo "<p>Pour vous connecter la première fois en tant qu'administrateur, utilisez le nom de connection <b>\"administrateur\"</b> et le mot de passe <b>\"azerty\"</b>. N'oubliez pas de changer le mot de passe !</p>";
					echo "<br /><center><a href = 'login.php'>Se connecter à GRR</a></center>";
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
					echo begin_page("Installation de GRR");
					begin_html();
					if ($test1 == 'no')
					{
						echo "<p>L'installation n'a pas pu se terminer normalement : des tables sont manquantes.</p>";
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
	echo begin_page("Installation de GRR");
	begin_html();
	echo "<br /><h2>Quatrième étape : Création des tables de la base</h2>";
	$db = mysqli_connect("$adresse_db", "$login_db", "$pass_db");
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
				$conn .= "?".">";
				@fputs($f, $conn);
				if (!@fclose($f))
					$ok = 'no';
			}
			if ($ok == 'yes')
			{
				echo "<b>La structure de votre base de données est installée.</b><br />Vous pouvez passer à l'étape suivante.";
				echo "<form action='install_mysql.php' method='get'>";
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
	end_html();
}
else if ($etape == 3)
{
	echo begin_page("Installation de GRR");
	begin_html();
	echo "<br /><h2>Troisième étape : Choix de votre base</h2>\n";
	echo "<form action='install_mysql.php' method='get'><div>\n";
	echo "<input type='hidden' name='etape' value='4' />\n";
	echo "<input type='hidden' name='adresse_db'  value=\"$adresse_db\" size='40' />\n";
	echo "<input type='hidden' name='login_db' value=\"$login_db\" />\n";
	echo "<input type='hidden' name='pass_db' value=\"$pass_db\" />\n";
	$db = mysqli_connect("$adresse_db","$login_db","$pass_db");
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
	echo begin_page("Installation de GRR");
	begin_html();
	echo "<br /><h2>Deuxième étape : Essai de connexion au serveur $dbsys</h2>\n";
	//echo "<!--";
	$db = mysqli_connect($adresse_db,$login_db,$pass_db);
	$db_connect = mysqli_errno($db);
	if (($db_connect != "0") && (!$db))
	{
		if ($adresse_db == "localhost")
			$adresse_db = "";
		$db = mysqli_connect($adresse_db,$login_db,$pass_db);
		$db_connect = mysqli_errno($db);
	}
	if (($db_connect=="0") && $db)
	{
		echo "<b>La connexion a réussi.</b><p> Vous pouvez passer à l'étape suivante.</p>\n";
		echo "<form action='install_mysql.php' method='get'>\n";
		echo "<div><input type='hidden' name='etape' value='3' />\n";
		echo "<input type='hidden' name='adresse_db'  value=\"$adresse_db\" size='40' />\n";
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
	echo begin_page("Installation de GRR");
	begin_html();
	echo "<br /><h2>Première étape : la connexion $dbsys</h2>";
	echo "<p>Vous devez avoir en votre possession les codes de connexion au serveur $dbsys. Si ce n'est pas le cas, contactez votre hébergeur ou bien l'administrateur technique du serveur sur lequel vous voulez implanter GRR.</p>";
	$adresse_db = 'localhost';
	$login_db = '';
	$pass_db = '';
	echo "<form action='install_mysql.php' method='get'>\n";
	echo "<div><input type='hidden' name='etape' value='2' />\n";
	echo "<fieldset><label><b>Adresse de la base de donnée</b><br /></label>\n";
	echo "(Souvent cette adresse correspond à celle de votre site, parfois elle correspond à la mention &laquo;localhost&raquo;, parfois elle est laissée totalement vide.)<br />\n";
	echo "<input type='text' name='adresse_db' class='formo' value=\"$adresse_db\" size='40' /></fieldset>\n";
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
		echo "<p>Vous pouvez par exemple utilisez votre client FTP afin de régler ce problème ou bien contactez l'administrateur technique. Une fois cette manipulation effectuée, vous pourrez continuer.</p>";
		echo "<p><form action='install_mysql.php' method='get'>";
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
