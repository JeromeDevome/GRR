<?php
/**
 * admin_import_user_csv.php
 * script d'importation d'utilisateurs à partir d'un fichier CSV
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2022-03-17 14:14$
 * @author    Laurent Delineau & JeromeB
 * @copyright Copyright 2003-2022 Team DEVOME - JeromeB
 * @link      http://www.gnu.org/licenses/licenses.html
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 */

$grr_script_name = "admin_import_users_csv.php";

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
get_vocab_admin("pwd_change");
get_vocab_admin("admin_import_users_csv7");
get_vocab_admin("admin_import_users_csv9");
get_vocab_admin("admin_import_users_csv10");

get_vocab_admin("submit");


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
						$num = count($data);
					else
						$num = 0;

					if ($num == 9)
					{
						$row++;
						// Nouvelle ligne
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
										$uNom = $data[$c];
									}
									else
										$uNom = $data[$c];

									$data_nom = htmlentities($data[$c]);
								}
								else
									$uNom = "???";
								break;

								case 2:
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

								case 3:
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

								case 4:
								// Adresse E-mail
								if ((preg_match ("`^.{1,100}$`", $data[$c])) || ($data[$c] ==''))
								{
									$uMail = $data[$c];
								}
								else if ($data[$c]=='-')
								{
									$uMail = "???";
								}
								else
								{
									$uMail = "???";
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
									$uType = $data[$c];
								}
								else
								{
									$uType = "???";
									$valid = 0;
								}
								break;

								case 6:
								// statut: deux valeurs autorisées : actif ou inactif
								if (preg_match ("`(actif|inactif)`", $data[$c]))
								{
									$data_statut = htmlentities($data[$c]);
									$uStatut = $data[$c];
								} else {
									$uStatut = "???";
									$valid = 0;
								}
								break;

								case 7:
								// Type d'authentification : deux valeurs autorisées : local ou ext
								if (preg_match ("`(local|ext)`", $data[$c])) {
									$data_type_auth = htmlentities($data[$c]);
									if (($data_mdp == "") && ($data_type_auth == "local"))
									{
										$uAuth = "local -> mot de passe incorrect";
										$valid = 0;
									}
									else if (($data_mdp != "") && ($data_type_auth == "ext"))
									{
										$uAuth = "ext -> mot de passe incorrect";
										$valid = 0;
									}
									else
									{
										$uAuth = $data[$c];
									}
								}
								else
								{
									$uAuth = "???";
									$valid = 0;
								}
								break;

								case 8:
								// Doit changer mot de passe à la connexion: deux valeurs autorisées : 1 (oui) ou 0 (non)changerpwd
								if ($data[$c] == "1")
									$uChangerpwd = "1";
								else
									$uChangerpwd = "0";
								break;
							}
						}
						$utilisateursaimporter[] = array('num' => $row, 'existant' => $uExistant, 'login' => $uLogin, 'name' => $uNom, 'first_name' => $uPrenom, 'pwd' => $uMDP, 'email' => $uMail, 'type' => $uType, 'statut' => $uStatut, 'authentification' => $uAuth, 'changerpwd' => $uChangerpwd);
					}
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
	$reg_email = isset($_POST["reg_email"]) ? $_POST["reg_email"] : NULL;
	$reg_mdp = isset($_POST["reg_mdp"]) ? $_POST["reg_mdp"] : NULL;
	$reg_type_user = isset($_POST["reg_type_user"]) ? $_POST["reg_type_user"] : NULL;
	$reg_statut = isset($_POST["reg_statut"]) ? $_POST["reg_statut"] : NULL;
	$reg_type_auth = isset($_POST["reg_type_auth"]) ? $_POST["reg_type_auth"] : NULL;
	$reg_changer_pwd = isset($_POST["reg_changer_pwd"]) ? $_POST["reg_changer_pwd"] : 0;
	$nb_row++;
	for ($row = 1; $row < $nb_row; $row++)
	{
		if ($reg_type_auth[$row] != "ext")
			$reg_mdp[$row] = password_hash(unslashes($reg_mdp[$row]), PASSWORD_DEFAULT);
		// On nettoie les windozeries
		$reg_nom[$row] = protect_data_sql(corriger_caracteres($reg_nom[$row]));
		$reg_prenom[$row] = protect_data_sql(html_entity_decode($reg_prenom[$row]));
		$reg_email[$row] = protect_data_sql(corriger_caracteres($reg_email[$row]));
		$reg_changer_pwd[$row] = protect_data_sql(corriger_caracteres($reg_changer_pwd[$row]));
		$test_login = grr_sql_count(grr_sql_query("SELECT login FROM ".TABLE_PREFIX."_utilisateurs WHERE login='$reg_login[$row]'"));
		if ($test_login == 0)
			$regdata = grr_sql_query("INSERT INTO ".TABLE_PREFIX."_utilisateurs SET nom='".$reg_nom[$row]."',prenom='".$reg_prenom[$row]."',login='".$reg_login[$row]."',email='".$reg_email[$row]."',password='".protect_data_sql($reg_mdp[$row])."',statut='".$reg_type_user[$row]."',etat='".$reg_statut[$row]."',source='".$reg_type_auth[$row]."',changepwd='".$reg_changer_pwd[$row]."'");
		else
			$regdata = grr_sql_query("UPDATE ".TABLE_PREFIX."_utilisateurs SET nom='".$reg_nom[$row]."',prenom='".$reg_prenom[$row]."',email='".$reg_email[$row]."',password='".protect_data_sql($reg_mdp[$row])."',statut='".$reg_type_user[$row]."',etat='".$reg_statut[$row]."',source='".$reg_type_auth[$row]."',changepwd='".$reg_changer_pwd[$row]."' WHERE login='".$reg_login[$row]."'");
		if (!$regdata)
			$trad['dResultat'] .= "<p><font color=\"red\">".$reg_login[$row].get_vocab("deux_points").get_vocab("message_records_error")."</font></p>";
		else
		{
			if ($reg_stat[$row] == "nouveau")
				$trad['dResultat'] .= "<p>".$reg_login[$row].get_vocab("deux_points").get_vocab("admin_import_users_csv12")."</p>";
			else
				$trad['dResultat'] .= "<p>".$reg_login[$row].get_vocab("deux_points").get_vocab("message_records")."</p>";
		}
	}
}

echo $twig->render('admin_import_users_csv.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings, 'utilisateursaimporter' => $utilisateursaimporter));
?>