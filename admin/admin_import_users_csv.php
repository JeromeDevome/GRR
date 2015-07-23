<?php
/**
 * admin_import_user_csv.php
 * script d'importation d'utilisateurs à partir d'un fichier CSV
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2009-09-29 18:02:56 $
 * @author    Laurent Delineau <laurent.delineau@ac-poitiers.fr>
 * @copyright Copyright 2003-2008 Laurent Delineau
 * @link      http://www.gnu.org/licenses/licenses.html
 * @package   root
 * @version   $Id: admin_import_users_csv.php,v 1.8 2009-09-29 18:02:56 grr Exp $
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
include "../include/admin.inc.php";
$grr_script_name = "admin_import_users_csv.php";

$back = '';
if (isset($_SERVER['HTTP_REFERER']))
	$back = htmlspecialchars($_SERVER['HTTP_REFERER']);
if ((authGetUserLevel(getUserName(), -1) < 6) && (authGetUserLevel(getUserName(), -1, 'user') !=  1))
{
	showAccessDenied($back);
	exit();
}
# print the page header
print_header("", "", "", $type="with_session");
?>
<p>| <a href="admin_user.php"><?php echo get_vocab("back");?></a> |</p>
<?php
$reg_data = isset($_POST["reg_data"]) ? $_POST["reg_data"] : NULL;
$is_posted = isset($_POST["is_posted"]) ? $_POST["is_posted"] : NULL;
$test_login_existant = '';
$test_nom_prenom_existant = '';
$test_login = '';
if ($reg_data != 'yes')
{
// $long_max : doit être plus grand que la plus grande ligne trouvée dans le fichier CSV
	$long_max = 8000;
	if ($is_posted != '1')
	{
		?>
		<form enctype="multipart/form-data" action="admin_import_users_csv.php" method="post" >
			<?php $csvfile=""; ?>
			<p>
				<?php echo get_vocab("admin_import_users_csv0"); ?>
				<input type="file" name="csvfile" />
			</p>
			<div>
				<input type="hidden" name="is_posted" value="1" />
				<p>
					<?php echo get_vocab("admin_import_users_csv1"); ?> 
					<input type="checkbox" name="en_tete" value="yes" checked="checked" />
				</p>
				<input type="submit" value="<?php echo get_vocab("submit");?>" />
				<br />
				</div
				></form>
				<div>
					<?php
					echo get_vocab("admin_import_users_csv2");
					echo get_vocab("admin_import_users_csv3")."</div>";
				}
				if ($is_posted == '1')
				{
					$valid = isset($_POST["valid"]) ? $_POST["valid"] : NULL;
					$en_tete = isset($_POST["en_tete"]) ? $_POST["en_tete"] : NULL;
					$csv_file = isset($_FILES["csvfile"]) ? $_FILES["csvfile"] : NULL;

					echo "<form enctype=\"multipart/form-data\" action=\"admin_import_users_csv.php\" method=\"post\" >";
					if ($csv_file['tmp_name'] != "")
					{
						$fp = @fopen($csv_file['tmp_name'], "r");
						if (!$fp)
							echo get_vocab("admin_import_users_csv4")."</form>";
						else
						{
							$row = 0;
							echo "<table border=\"1\"><tr><td><p>".get_vocab("login")."</p></td>
							<td><p>".get_vocab("name")."</p></td>
							<td><p>".get_vocab("first_name")."</p></td>
							<td><p>".get_vocab("pwd")."</p></td>
							<td><p>".get_vocab("email")."</p></td>
							<td><p>".get_vocab("type")."</p></td>
							<td><p>".get_vocab("statut")."</p></td>
							<td><p>".get_vocab("authentification")."</p></td>
						</tr>";
						$valid = 1;
						while (!feof($fp))
						{
							if ($en_tete == 'yes')
							{
								$data = fgetcsv ($fp, $long_max, ";");
								$en_tete = 'no';
							}
							$data = fgetcsv ($fp, $long_max, ";");
							$num = count ($data);
							if ($num == 8)
							{
								$row++;
								echo "<tr>";
								for ($c = 0; $c < $num; $c++)
								{
									switch ($c)
									{
										case 0:
										//login
										$test_login = preg_replace("/([A-Za-z0-9_@. -])/","",$data[$c]);
										if ($test_login=="")
										{
											$data[$c] =    strtoupper($data[$c]);
											$test = grr_sql_count(grr_sql_query("SELECT login FROM ".TABLE_PREFIX."_utilisateurs WHERE login='$data[$c]'"));
											if ($test!='0')
											{
												echo "<td><p><font color=\"red\">$data[$c]</font></p>\n";
												echo "<input type=\"hidden\" name=\"reg_stat[$row]\" value=\"existant\" />\n";
												$test_login_existant = "oui";
												$login_exist = "oui";
												$login_valeur = $data[$c];
											}
											else
											{
												echo "<td><p>$data[$c]</p>\n";
												echo "<input type=\"hidden\" name=\"reg_stat[$row]\" value=\"nouveau\" />\n";
												$login_exist = "non";
											}
											$data_login = htmlentities($data[$c]);
											echo "<input type=\"hidden\" name=\"reg_login[$row]\" value=\"$data_login\" /></td>\n";
										}
										else
										{
											echo "<td><font color=\"red\">???</font></td>\n";
											$valid = 0;
										}
										break;
										case 1:
										//Nom
										$test_nom_prenom_existant = 'no';
										if (preg_match ("`^.{1,30}$`", $data[$c]))
										{
											$test_nom = protect_data_sql($data[$c]);
											$test_prenom = protect_data_sql($data[$c+1]);
											$test_nom_prenom = grr_sql_count(grr_sql_query("SELECT nom FROM ".TABLE_PREFIX."_utilisateurs WHERE (nom='$test_nom' and prenom = '$test_prenom')"));
											if ($test_nom_prenom != '0')
											{
												$test_nom_prenom_existant = 'yes';
												echo "<td><p><font color=\"blue\">$data[$c]</font></p>";
											}
											else
												echo "<td><p>$data[$c]</p>";
											$data_nom = htmlentities($data[$c]);
											echo "<input type=\"hidden\" name=\"reg_nom[$row]\" value=\"$data_nom\" /></td>";
										}
										else
											echo "<td><font color=\"red\">???</font></td>";
										break;
										case 2:
										//Prenom
										if (preg_match ("`^.{1,30}$`", $data[$c]))
										{
											if ($test_nom_prenom_existant == 'yes')
												echo "<td><p><font color=\"blue\">$data[$c]</font></p>";
											else 
												echo "<td><p>$data[$c]</p>";
											$data_prenom = htmlentities($data[$c]);
											echo "<input type=\"hidden\" name=\"reg_prenom[$row]\" value=\"$data_prenom\" /></td>";
										}
										else
										{
											echo "<td><font color=\"red\">???</font></td>";
											$valid = 0;
										}
										break;
										case 3:
										// Mot de passe
										if ((preg_match ("`^.{".$pass_leng.",30}$`", $data[$c])) || ($data[$c] == ''))
										{
											$data_mdp = htmlentities($data[$c]);
											echo "<td><p>$data[$c] </p>";
											echo "<input type=\"hidden\" name=\"reg_mdp[$row]\" value=\"$data_mdp\" /></td>";
										}
										else
										{
											echo "<td><font color=\"red\">???</font></td>";
											$valid = 0;
										}
										break;
										case 4:
										// Adresse E-mail
										if ((preg_match ("`^.{1,100}$`", $data[$c])) || ($data[$c] ==''))
										{
											$data_email = htmlentities($data[$c]);
											echo "<td><p>$data[$c] </p>";
											echo "<input type=\"hidden\" name=\"reg_email[$row]\" value=\"$data_email\" /></td>";
										}
										else if ($data[$c]=='-')
										{
											echo "<td><font color=\"red\">???</font>";
											echo "<input type=\"hidden\" name=\"reg_email[$row]\" value=\"\" /></td>";
										}
										else
										{
											echo "<td><font color=\"red\">???</font></td>";
											$valid = 0;
										}
										break;
										case 5:
										// Type d'utilisateur : quatre valeurs autorisées : visiteur, utilisateur, administrateur, gestionnaire_utilisateur
										// Si c'est un gestionnaire d'utilisateurs qui importe, seuls les types visiteur et utilisateur sont autorisés
										if (authGetUserLevel(getUserName(), -1) >= 6)
											$filtre = "(visiteur|utilisateur|administrateur|gestionnaire_utilisateur)";
										else
											$filtre = "`(visiteur|utilisateur)`";

										if (preg_match ($filtre, $data[$c]))
										{
											$data_type_user = htmlentities($data[$c]);
											echo "<td><p>$data[$c] </p>";
											echo "<input type=\"hidden\" name=\"reg_type_user[$row]\" value=\"$data_type_user\" /></td>";
										}
										else
										{
											echo "<td><font color=\"red\">???</font></td>";
											$valid = 0;
										}
										break;
										case 6:
										// statut: deux valeurs autorisées : actif ou inactif
										if (preg_match ("`(actif|inactif)`", $data[$c]))
										{
											$data_statut = htmlentities($data[$c]);
											echo "<td><p>$data[$c] </p>";
											echo "<input type=\"hidden\" name=\"reg_statut[$row]\" value=\"$data_statut\" /></td>";
										} else {
											echo "<td><font color=\"red\">???</font></td>";
											$valid = 0;
										}
										break;
										case 7:
										// Type d'authentification : deux valeurs autorisées : local ou ext
										if (preg_match ("`(local|ext)`", $data[$c])) {
											$data_type_auth = htmlentities($data[$c]);
											if (($data_mdp == "") && ($data_type_auth == "local"))
											{
												echo "<td><font color=\"red\">local -> mot de passe incorrect</font></td>";
												$valid = 0;
											}
											else if (($data_mdp != "") && ($data_type_auth == "ext"))
											{
												echo "<td><font color=\"red\">ext -> mot de passe incorrect</font></td>";
												$valid = 0;
											}
											else
											{
												echo "<td><p>$data[$c] </p>";
												echo "<input type=\"hidden\" name=\"reg_type_auth[$row]\" value=\"$data_type_auth\" /></td>";
											}
										}
										else
										{
											echo "<td><font color=\"red\">???</font></td>";
											$valid = 0;
										}
										break;
									}
								}
								echo "</tr>";
							}
						}
						fclose($fp);
						echo "</table>";
						echo "<p>".get_vocab("admin_import_users_csv5")."$row ".get_vocab("admin_import_users_csv6")."</p>\n";
						if ($row > 0)
						{
							if ($test_login_existant == "oui")
								echo get_vocab("admin_import_users_csv7");
							if ($test_nom_prenom_existant == 'yes')
								echo get_vocab("admin_import_users_csv8");
							if ($valid == '1')
							{
								echo "<div><input type=\"submit\" value=\"".get_vocab("submit")."\" />\n";
								echo "<input type=\"hidden\" name=\"nb_row\" value=\"$row\" />\n";
								echo "<input type=\"hidden\" name=\"reg_data\" value=\"yes\" />\n";
								echo "</div></form>";
							}
							else
							{
								echo get_vocab("admin_import_users_csv9");
								echo "</form>";
							}
						}
						else
							echo "<p>".get_vocab("admin_import_users_csv10")."</p></form>";
					}
				}
				else
					echo "<p>".get_vocab("admin_import_users_csv11")."</p></form>";
			}
		}
		else
		{
			// Restriction dans le cas d'une démo
			VerifyModeDemo();
			// Phase d'enregistrement des données
			$nb_row = isset($_POST["nb_row"]) ? $_POST["nb_row"] : NULL;
			$reg_stat = isset($_POST["reg_stat"]) ? $_POST["reg_stat"] : NULL;
			$reg_login = isset($_POST["reg_login"]) ? $_POST["reg_login"] : NULL;
			$reg_nom = isset($_POST["reg_nom"]) ? $_POST["reg_nom"] : NULL;
			$reg_prenom = isset($_POST["reg_prenom"]) ? $_POST["reg_prenom"] : NULL;
			$reg_email = isset($_POST["reg_email"]) ? $_POST["reg_email"] : NULL;
			$reg_mdp = isset($_POST["reg_mdp"]) ? $_POST["reg_mdp"] : NULL;
			$reg_type_user = isset($_POST["reg_type_user"]) ? $_POST["reg_type_user"] : NULL;
			$reg_statut = isset($_POST["reg_statut"]) ? $_POST["reg_statut"] : NULL;
			$reg_type_auth = isset($_POST["reg_type_auth"]) ? $_POST["reg_type_auth"] : NULL;
			$nb_row++;
			for ($row = 1; $row < $nb_row; $row++)
			{
				if ($reg_type_auth[$row] != "ext")
					$reg_mdp[$row] = md5(unslashes($reg_mdp[$row]));
				// On nettoie les windozeries
				$reg_nom[$row] = protect_data_sql(corriger_caracteres($reg_nom[$row]));
				$reg_prenom[$row] = protect_data_sql(corriger_caracteres($reg_prenom[$row]));
				$reg_email[$row] = protect_data_sql(corriger_caracteres($reg_email[$row]));
				$test_login = grr_sql_count(grr_sql_query("SELECT login FROM ".TABLE_PREFIX."_utilisateurs WHERE login='$reg_login[$row]'"));
				if ($test_login == 0)
					$regdata = grr_sql_query("INSERT INTO ".TABLE_PREFIX."_utilisateurs SET nom='".$reg_nom[$row]."',prenom='".$reg_prenom[$row]."',login='".$reg_login[$row]."',email='".$reg_email[$row]."',password='".protect_data_sql($reg_mdp[$row])."',statut='".$reg_type_user[$row]."',etat='".$reg_statut[$row]."',source='".$reg_type_auth[$row]."'");
				else
					$regdata = grr_sql_query("UPDATE ".TABLE_PREFIX."_utilisateurs SET nom='".$reg_nom[$row]."',prenom='".$reg_prenom[$row]."',email='".$reg_email[$row]."',password='".protect_data_sql($reg_mdp[$row])."',statut='".$reg_type_user[$row]."',etat='".$reg_statut[$row]."',source='".$reg_type_auth[$row]."' WHERE login='".$reg_login[$row]."'");
				if (!$regdata)
					echo "<p><font color=\"red\">".$reg_login[$row].get_vocab("deux_points").get_vocab("message_records_error")."</font></p>";
				else
				{
					if ($reg_stat[$row] == "nouveau")
						echo "<p>".$reg_login[$row].get_vocab("deux_points").get_vocab("admin_import_users_csv12")."</p>";
					else
						echo "<p>".$reg_login[$row].get_vocab("deux_points").get_vocab("message_records")."</p>";
				}
			}
		}
		?>
	</body>
	</html>