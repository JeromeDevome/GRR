<?php
/**
 * admin_import_users_elycee.php
 * script d'importation d'utilisateurs à partir d'un fichier CSV issu de l'ENT elycée Rhône-Alpes, une variante de Kd'Ecole
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2017-12-16 14:00$
 * @author    JeromeB & Yan Naessens & Laurent Delineau
 * @copyright Copyright 2003-2020 Team DEVOME - JeromeB
 * @link      http://www.gnu.org/licenses/licenses.html
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 */

$grr_script_name = "admin_import_users_elycee.php";

if ((authGetUserLevel(getUserName(), -1) < 6) && (authGetUserLevel(getUserName(), -1, 'user') !=  1))
{
	showAccessDenied($back);
	exit();
}

get_vocab_admin("admin_import_users_fichier");
get_vocab_admin("back");
get_vocab_admin("admin_import_users_csv0");
get_vocab_admin("admin_import_users_csv1");
get_vocab_admin("submit");
get_vocab_admin("num_version_title");
get_vocab_admin("admin_import_users_csv2");
get_vocab_admin("admin_import_users_csv3");

get_vocab_admin("admin_import_users_csv11");
get_vocab_admin("admin_import_users_csv4");
get_vocab_admin("login");
get_vocab_admin("name");
get_vocab_admin("first_name");
get_vocab_admin("pwd");
get_vocab_admin("email");
get_vocab_admin("type");
get_vocab_admin("statut");
get_vocab_admin("authentification");
get_vocab_admin("admin_import_users_csv7");
get_vocab_admin("admin_import_users_csv9");
get_vocab_admin("admin_import_users_csv10");


$reg_data = isset($_POST["reg_data"]) ? $_POST["reg_data"] : NULL;
$is_posted = isset($_POST["is_posted"]) ? $_POST["is_posted"] : NULL;
$test_login_existant = '';
$test_nom_prenom_existant = '';
$test_login = '';
$utilisateursaimporter[] = array();

$trad['dFichierEnvoye'] = $is_posted;
$trad['dResultat'] = "";

if ($reg_data != 'yes')
{
	// $long_max : doit être plus grand que la plus grande ligne trouvée dans le fichier CSV
	$long_max = 8000;

	if ($is_posted == '1')
	{
		$valid = isset($_POST["valid"]) ? $_POST["valid"] : NULL;
		$en_tete = isset($_POST["en_tete"]) ? $_POST["en_tete"] : NULL;
		$csv_file = isset($_FILES["csvfile"]) ? $_FILES["csvfile"] : NULL;

		$trad['dTmpName'] = $csv_file['tmp_name'];

		if ($csv_file['tmp_name'] != "")
		{
			$fp = @fopen($csv_file['tmp_name'], "r");
			if (!$fp)
				$trad['dFp'] = get_vocab("admin_import_users_csv4");
			else
			{
				$row = 0;
				$valid = 1;
				while (!feof($fp))
				{
					if ($en_tete == 'yes')
					{
						$data = fgetcsv ($fp, $long_max, ";");
						$en_tete = 'no';
					}
					$data = fgetcsv ($fp, $long_max, ";");
					
					if($data != "")
						$num = count ($data);
					else
						$num = 0;

					if ($num == 19)
					{
						$row++;
						// Nouvelle ligne
						for ($c = 0; $c < $num; $c++)
						{
							switch ($c)
							{
								case 6:
								//login
								$test_login = preg_replace("/([A-Za-z0-9_@. -])/","",$data[$c]);
								if ($test_login=="")
								{
									$data[$c] =    strtoupper($data[$c]);
									$test = grr_sql_count(grr_sql_query("SELECT login FROM ".TABLE_PREFIX."_utilisateurs WHERE login='$data[$c]'"));
									if ($test!='0')
									{
										$uLogin = $data[$c];
										$uExistant = 1;
										$test_login_existant = "oui";
										$login_exist = "oui";
										$login_valeur = $data[$c];
									}
									else
									{
										$uLogin = $data[$c];
										$uExistant = 0;
										$login_exist = "non";
									}
								}
								else
								{
									$uLogin = "???";
									$valid = 0;
								}
								break;

								case 3:
								// profil : élève ou parent => visiteur, enseignant ou administratif => utilisateur
								// Type d'utilisateur : quatre valeurs autorisées : visiteur, utilisateur, administrateur, gestionnaire_utilisateur
								// Si c'est un gestionnaire d'utilisateurs qui importe, seuls les types visiteur et utilisateur sont autorisés
/* 										if (authGetUserLevel(getUserName(), -1) >= 6)
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
								} */
								// en attendant de filtrer, statut visiteur par défaut
									$uType = 'visiteur';
								break;

								case 4:
								//Prenom
								if (preg_match ("`^.{1,30}$`", $data[$c]))
								{
									if ($test_nom_prenom_existant == 'yes')
										$uPrenom = $data[$c];
									else 
										$uPrenom = $data[$c];
								}
								else
								{
									$uPrenom  = "???";
									$valid = 0;
								}
								break;

								case 5:
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
										$uNom = $data[$c];
									}
									else
										$uNom = $data[$c];

									$data_nom = htmlentities($data[$c]);
								}
								else
									$uNom = "???";
								break;

								case 7:
								// Mot de passe
								if ((preg_match ("`^.{".$pass_leng.",30}$`", $data[$c])) || ($data[$c] == ''))
								{
									$data_mdp = htmlentities($data[$c]);
									$uMDP = $data[$c];
								}
								else
								{
									$data_mdp = "";
									$uMDP = "???";
									$valid = 0;
								}
								break;

								default:
								// tous les autres champs ne sont pas traités
								break;
							}
						}
						$utilisateursaimporter[] = array('num' => $row, 'existant' => $uExistant, 'login' => $uLogin, 'name' => $uNom, 'first_name' => $uPrenom, 'pwd' => $uMDP, 'email' => '', 'type' => $uType, 'statut' => 'actif', 'authentification' => 'local');
					} // peut-être filtrer si le nombre de données par ligne n'est pas correct
				}
				fclose($fp);
				$trad['dInfosImport'] = get_vocab("admin_import_users_csv5")."$row ".get_vocab("admin_import_users_csv6");
				$trad['dValideImport'] = $valid;
				$trad['dRow'] = $row;
				$trad['dLoginExistant'] = $test_login_existant;

			}
		}
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
	$reg_type_user = isset($_POST["reg_type_user"]) ? $_POST["reg_type_user"] : NULL;
	$reg_statut = isset($_POST["reg_statut"]) ? $_POST["reg_statut"] : NULL;
	$reg_type_auth = isset($_POST["reg_type_auth"]) ? $_POST["reg_type_auth"] : NULL;
	$nb_row++;
	for ($row = 1; $row < $nb_row; $row++)
	{
		// On nettoie les windozeries
		$reg_nom[$row] = protect_data_sql(corriger_caracteres($reg_nom[$row]));
		$reg_prenom[$row] = protect_data_sql(corriger_caracteres($reg_prenom[$row]));
		$test_login = grr_sql_count(grr_sql_query("SELECT login FROM ".TABLE_PREFIX."_utilisateurs WHERE login='$reg_login[$row]'"));
		if ($test_login == 0) {
			$sqlQuery = "INSERT INTO ".TABLE_PREFIX."_utilisateurs SET nom='".$reg_nom[$row]."',prenom='".$reg_prenom[$row]."',login='".$reg_login[$row]."',statut='".$reg_type_user[$row]."',etat='actif',source='ext'";
			$regdata = grr_sql_query($sqlQuery); 
		}
		else {
			$sqlQuery = "UPDATE ".TABLE_PREFIX."_utilisateurs SET nom='".$reg_nom[$row]."',prenom='".$reg_prenom[$row]."',login='".$reg_login[$row]."',statut='".$reg_type_user[$row]."',etat='actif',source='ext'"."WHERE login='".$reg_login[$row]."'";
			$regdata = grr_sql_query($sqlQuery);
		}
		if (!$regdata)
			$trad['dResultat'] .=  "<p><font color=\"red\">".$reg_login[$row].get_vocab("deux_points").get_vocab("message_records_error")."</font></p>";
		else
		{
			if ($reg_stat[$row] == "nouveau")
				$trad['dResultat'] .=  "<p>".$reg_login[$row].get_vocab("deux_points").get_vocab("admin_import_users_csv12")."</p>";
			else
				$trad['dResultat'] .=  "<p>".$reg_login[$row].get_vocab("deux_points").get_vocab("message_records")."</p>";
		}
	}
}

	echo $twig->render('admin_import_users_elycee.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings, 'utilisateursaimporter' => $utilisateursaimporter));
?>