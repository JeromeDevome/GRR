<?php
/**
 * admin_config_ldap.php
 * Interface permettant la configuration de l'accès à un annuaire LDAP
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2017-12-16 14:00$
 * @author    Laurent Delineau & JeromeB
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

$grr_script_name = "admin_config_ldap.php";

if (authGetUserLevel(getUserName(), -1) < 6)
{
	showAccessDenied($back);
	exit();
}

$valid		= isset($_POST["valid"]) ? $_POST["valid"] : 'no';
$etape		= isset($_POST["etape"]) ? $_POST["etape"] : '0';
$adresse	= isset($_POST["adresse"]) ? $_POST["adresse"] : NULL;
$port		= isset($_POST["port"]) ? $_POST["port"] : NULL;
$login_ldap	= isset($_POST["login_ldap"]) ? $_POST["login_ldap"] : NULL;
$pwd_ldap	= isset($_POST["pwd_ldap"]) ? $_POST["pwd_ldap"] : NULL;
$pwd_ldap	= unslashes($pwd_ldap);
$use_tls	= FALSE;
if (isset($_POST["use_tls"]) && $_POST["use_tls"] == 'y')
	$use_tls = TRUE;

$base_ldap				= isset($_POST["base_ldap"]) ? $_POST["base_ldap"] : NULL;
$base_ldap_autre		= isset($_POST["base_ldap_autre"]) ? $_POST["base_ldap_autre"] : NULL;
$ldap_filter			= isset($_POST["ldap_filter"]) ? $_POST["ldap_filter"] : NULL;
$ldap_group_member_attr = isset($_POST["ldap_group_member_attr"]) ? $_POST["ldap_group_member_attr"] : NULL;
$ldap_group_base		= isset($_POST["ldap_group_base"]) ? $_POST["ldap_group_base"] : NULL;
$ldap_group_filter		= isset($_POST["ldap_group_filter"]) ? $_POST["ldap_group_filter"] : NULL;
$ldap_group_user_field	= isset($_POST["ldap_group_user_field"]) ? $_POST["ldap_group_user_field"] : NULL;

$trad['titre_ldap'] = "Configuration de l'authentification LDAP";
$trad['dConfigImpossible'] = 0;

// Etape 0
get_vocab_admin('Statut_par_defaut_utilisateurs_importes');
get_vocab_admin('statut_visitor');
get_vocab_admin('statut_user');
get_vocab_admin('last_name');
get_vocab_admin('first_name');
get_vocab_admin('mail_user');

// Etape 1
get_vocab_admin('login');
get_vocab_admin('pwd');
get_vocab_admin('YES');
get_vocab_admin('NO');
get_vocab_admin('next');

// Etape 2
get_vocab_admin('back');

get_vocab_admin('save');


if (isset($_POST['reg_ldap_statut']))
{
	if ($_POST['ldap_statut'] == "no_ldap")
	{
		$req = grr_sql_query("delete from ".TABLE_PREFIX."_setting where NAME = 'ldap_statut'");
		$grrSettings['ldap_statut'] = '';
	}
	else
	{
		if (!Settings::set("ldap_statut", $_POST['ldap_statut']))
			echo encode_message_utf8("Erreur lors de l'enregistrement de ldap_statut !<br />");
		$grrSettings['ldap_statut'] = $_POST['ldap_statut'];
	}
	if (isset($_POST['Valider1']))
	{
		VerifyModeDemo();

		if (!isset($_POST['ConvertLdapUtf8toIso']))
			$ConvertLdapUtf8toIso = "n";
		else
			$ConvertLdapUtf8toIso = "y";
		if (!Settings::set("ConvertLdapUtf8toIso", $ConvertLdapUtf8toIso))
			echo "Erreur lors de l'enregistrement de ConvertLdapUtf8toIso !<br />";
		$grrSettings['ConvertLdapUtf8toIso'] = $ConvertLdapUtf8toIso;
		if (!isset($_POST['ActiveModeDiagnostic']))
			$ActiveModeDiagnostic = "n";
		else
			$ActiveModeDiagnostic = "y";
		if (!Settings::set("ActiveModeDiagnostic", $ActiveModeDiagnostic))
			echo "Erreur lors de l'enregistrement de ActiveModeDiagnostic !<br />";
		$grrSettings['ActiveModeDiagnostic'] = $ActiveModeDiagnostic;
		if (!Settings::set("ldap_champ_recherche", $_POST['ldap_champ_recherche']))
			echo "Erreur lors de l'enregistrement de ldap_champ_recherche !<br />";
		$grrSettings['ldap_champ_recherche'] = $_POST['ldap_champ_recherche'];
		if ($_POST['ldap_champ_nom'] == '')
			$_POST['ldap_champ_nom'] = "sn";
		if (!Settings::set("ldap_champ_nom", $_POST['ldap_champ_nom']))
			echo "Erreur lors de l'enregistrement de ldap_champ_nom !<br />";
		$grrSettings['ldap_champ_nom'] = $_POST['ldap_champ_nom'];
		if ($_POST['ldap_champ_prenom'] == '')
			$_POST['ldap_champ_prenom'] = "sn";
		if (!Settings::set("ldap_champ_prenom", $_POST['ldap_champ_prenom']))
			echo "Erreur lors de l'enregistrement de ldap_champ_prenom !<br />";
		$grrSettings['ldap_champ_prenom'] = $_POST['ldap_champ_prenom'];
		if ($_POST['ldap_champ_email'] == '')
			$_POST['ldap_champ_email'] = "sn";
		if (!Settings::set("ldap_champ_email", $_POST['ldap_champ_email']))
			echo "Erreur lors de l'enregistrement de ldap_champ_email !<br />";
		$grrSettings['ldap_champ_email'] = $_POST['ldap_champ_email'];
		if (!Settings::set("se3_liste_groupes_autorises", $_POST['se3_liste_groupes_autorises']))
			echo "Erreur lors de l'enregistrement de se3_liste_groupes_autorises !<br />";
		$grrSettings['se3_liste_groupes_autorises'] = $_POST['se3_liste_groupes_autorises'];
	}
}

	if ((isset($ldap_restrictions)) && ($ldap_restrictions == true))
	{
		showAccessDenied($back);
		exit();
	}
	if ((authGetUserLevel(getUserName(), -1) < 6) && ($valid != 'yes'))
	{
		showAccessDenied($back);
		exit();
	}

	$AllSettings = Settings::getAll();

	if ($etape == 3)
	{

		if (!$base_ldap)
			$base_ldap = $base_ldap_autre;
		$ds = grr_connect_ldap($adresse,$port,$login_ldap,$pwd_ldap,$use_tls);
		// On verifie le chemin fourni
		$result = grr_ldap_search_user($ds, $base_ldap, "objectClass", "*",$ldap_filter,"y");
		$trad['dResultat'] = "";

		if ($result == "error_1")
		{
			if ($ldap_filter == "")
				$trad['dResultat'] .= "<b>Problème</b> : Le chemin que vous avez choisi <b>ne semble pas valide.<br />";
			else
				$trad['dResultat'] .= "<b>Problème</b> : Le chemin et/ou le filtre additionnel que vous avez choisi <b>ne semblent pas valides.<br />";
		}
		else if ($result == "error_2")
		{
			if ($ldap_filter == "")
				$trad['dResultat'] .= "<b>Problème</b> : Le chemin que vous avez choisi semble valide mais la recherche sur ce chemin ne renvoie aucun résultat.<br />";
			else
				$trad['dResultat'] .= "<b>Problème</b> : Le chemin et le filtre additionnel que vous avez choisi semblent valides  mais la recherche sur ce chemin ne renvoie aucun résultat.<br />";
		}
		// Le cas "error_3" n'est pas analusé car on accepte les  cas où il y a plusieurs entrées dans l'annuaire à l'issus de la recherche
		$erreur = '';
		$nom_fic = "../personnalisation/config_ldap.inc.php";
		if (@file_exists($nom_fic))
		{
			unlink($nom_fic);
			if (@file_exists($nom_fic))
				$erreur = "Impossible de supprimer le fichier \"".$nom_fic."\" existant.<br />Vous devez modifier les permissions sur ce fichier puis recharger cette page.";
		}
		if ($erreur == '')
		{
			$f = @fopen($nom_fic, "wb");
			if (!$f)
			{
				$erreur = "Impossible de créer le fichier \"".$nom_fic."\".";
				if (@file_exists($nom_fic.".ori"))
					$erreur .= "<br />Vous pouvez renommer manuellement le fichier \"".$nom_fic.".ori\" en \"".$nom_fic."\", et lui donner les droits suffisants.";
				else
					$erreur .= "<br />Vous devez modifier les droits sur le répertoire include.";
			}
		}
		if ($erreur == '')
		{
			// On a ouvert un fichier config_ldap.inc.php
			$conn = "<"."?php\n";
			$conn .= "# Les quatre lignes suivantes sont à modifier selon votre configuration\n";
			$conn .= "# ligne suivante : l'adresse de l'annuaire LDAP.\n";
			$conn .= "# Si c'est le même que celui qui heberge les scripts, mettre \"localhost\"\n";
			$conn .= "\$ldap_adresse='".$adresse."';\n";
			$conn .= "# ligne suivante : le port utilisé\n";
			$conn .= "\$ldap_port='".$port."';\n";
			$conn .= "# ligne suivante : l'identifiant et le mot de passe dans le cas d'un accès non anonyme\n";
			$conn .= "\$ldap_login='".$login_ldap."';\n";
			$conn .= "# Remarque : des problèmes liés à un mot de passe contenant un ou plusieurs caractères accentués ont déjà été constatés.\n";
			$conn .= "\$ldap_pwd='".addslashes($pwd_ldap)."';\n";
			$conn .= "# ligne suivante : le chemin d'accès dans l'annuaire\n";
			$conn .= "\$ldap_base='".$base_ldap."';\n";
			$conn .= "# ligne suivante : filtre LDAP supplémentaire (facultatif)\n";
			$conn .= "\$ldap_filter='".$ldap_filter."';\n";
			$conn .= "# ligne suivante : utiliser TLS\n";
			if ($use_tls)
				$conn .= "\$use_tls=TRUE;\n";
			else
				$conn .= "\$use_tls=FALSE;\n";
			$conn .= "# Attention : si vous configurez manuellement ce fichier (sans passer par la configuration en ligne)\n";
			$conn .= "# vous devez tout de même activer LDAP en choisissant le \"statut par défaut des utilisateurs importés\".\n";
			$conn .= "# Pour cela, rendez-vous sur la page : configuration -> Configuration LDAP.\n";
			$conn .= "\n#SE3 variables\n";
			$conn .= "\$ldap_group_member_attr=\"{$ldap_group_member_attr}\";\n";
			$conn .= "\$ldap_group_base=\"{$ldap_group_base}\";\n";
			$conn .= "\$ldap_group_filter=\"{$ldap_group_filter}\";\n";
			$conn .= "\$ldap_group_user_field=\"{$ldap_group_user_field}\";\n";
			$conn .= "?".">";
			@fputs($f, $conn);
			if (!@fclose($f))
				$erreur = "Impossible d'enregistrer le fichier \"".$nom_fic."\".";
		}
		if ($erreur == '')
			$trad['dResultat'] .= "<p>".encode_message_utf8("<b>Les données concernant l'accès à l'annuaire LDAP sont maintenant enregistrées dans le fichier \"".$nom_fic."\".</b></p>");
		else
			$trad['dResultat'] .= $erreur;
/*		if ($erreur == '')
		{
			echo "<form action=\"admin_config_ldap.php\" method=\"post\">\n";
			echo "<div><input type=\"hidden\" name=\"etape\" value=\"0\" />\n";
			echo "<input type=\"hidden\" name=\"valid\" value=\"$valid\" />\n";
			echo "<div style=\"text-align:center;\"><input type=\"submit\" name=\"Valider\" value=\"Terminer\" /></div>\n";
			echo "</div></form>";
		}*/

		echo $twig->render('admin_config_ldap3.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings));
	}
	else if ($etape == 2)
	{
		$ds = grr_connect_ldap($adresse,$port,$login_ldap,$pwd_ldap,$use_tls);
		if ($ds)
			$connexion_ok = 'yes';
		else
			$connexion_ok = 'no';
		if ($connexion_ok == 'yes')
		{
			$trad['dConnexionReussi'] = 1;

			// On lit toutes les infos (objectclass=*) dans le dossier
			// Retourne un identifiant de résultat ($result), ou bien FALSE en cas d'erreur.
			$result = ldap_read($ds, "", "objectclass=*", array("namingContexts"));
			$info = ldap_get_entries($ds, $result);
			// Retourne un tableau associatif multi-dimensionnel ou FALSE en cas d'erreur. :
			// $info["count"] = nombre d'entrées dans le résultat
			// $info[0] : sous-tableau renfermant les infos de la première entrée
			// $info[n]["dn"] : dn de la n-ième entrée du résultat
			// $info[n]["count"] : nombre d'attributs de la n-ième entrée
			// $info[n][m] : m-ième attribut de la n-ième entrée
			// info[n]["attribut"]["count"] : nombre de valeur de cet attribut pour la n-ième entrée
			// $info[n]["attribut"][m] : m-ième valeur de l'attribut pour la n-ième entrée
			$checked = false;
			if (is_array($info) && $info["count"] > 0)
			{
				$trad['dSelect_chemin_ldap'] = "<p>Sélectionnez ci-dessous le chemin d'accès dans l'annuaire :</p>";
				$n = 0;
				for ($i = 0; $i < $info["count"]; $i++)
				{
					$names[] = $info[$i]["dn"];
					if (is_array($names))
					{
						for ($j = 0; $j < count($names); $j++)
						{
							$n++;
							$trad['dSelect_chemin_ldap'] .= "<br /><input name=\"base_ldap\" value=\"".htmlspecialchars($names[$j])."\" type='radio' id='tab$n'";
							if (!$checked)
							{
								$trad['dSelect_chemin_ldap'] .= " checked=\"checked\"";
								$checked = true;
							}
							$trad['dSelect_chemin_ldap'] .= " />\n";
							$trad['dSelect_chemin_ldap'] .= "<label for='tab$n'>".htmlspecialchars($names[$j])."</label>\n";
						}
					}
				}
				$trad['dSelect_chemin_ldap'] .= "<br />Ou bien \n";
			}
			$trad['dSelect_chemin_ldap'] .= "<br /><input name=\"base_ldap\" value=\"\" type='radio' id=\"autre\"";
			if (!$checked)
			{
				$trad['dSelect_chemin_ldap'] .= " checked=\"checked\"";
				$checked = true;
			}
			$trad['dSelect_chemin_ldap'] .= " />\n";
			$trad['dSelect_chemin_ldap'] .= "<label for=\"autre\">".encode_message_utf8("Précisez le chemin : ")."</label>\n ";
			if (isset($_POST["ldap_base"]))
				$ldap_base = $_POST["ldap_base"];
			else
				$ldap_base ="";
			if (isset($_POST["ldap_filter"]))
				$ldap_filter = $_POST["ldap_filter"];
			else $ldap_filter ="";
			$trad['dSelect_chemin_ldap'] .= "<input type=\"text\" name=\"base_ldap_autre\" value=\"$ldap_base\" size=\"40\" />\n";

			$trad['dAdresseLDAP']	= $adresse;
			$trad['dPortLDAP']		= $port;
			$trad['dLoginLDAP']		= $login_ldap;
			$trad['dPwdLDAP']		= $pwd_ldap;
			$trad['dFilterLDAP']	= $ldap_filter;
			if ($use_tls)
				$trad['dUseTLS']	= 'y';

		}
		else
		{
			$trad['dConnexionReussi'] = 0;
			$trad['dAdresseLDAP']	= $adresse;
			$trad['dPortLDAP']		= $port;
			$trad['dLoginLDAP']		= $login_ldap;
			if ($use_tls)
				$trad['dUseTLS']	= 'y';
		}

		echo $twig->render('admin_config_ldap2.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings));
	}
	else if ($etape == 1)
	{
		if (isset($_POST["valider"]))
		{
			$ldap_adresse = $_POST["ldap_adresse"];
			$ldap_port = $_POST["ldap_port"];
			$ldap_login = $_POST["ldap_login"];
		}
		else if (@file_exists("../personnalisation/config_ldap.inc.php"))
			include("../personnalisation/config_ldap.inc.php");

		if ((!(isset($ldap_adresse))) || ($ldap_adresse == ""))
			$ldap_adresse = 'localhost';
		if ((!(isset($ldap_port))) || ($ldap_port == ""))
			$ldap_port = 389;
		if (!(isset($ldap_login)))
			$ldap_login = "";
		if (!(isset($ldap_pwd)))
			$ldap_pwd = "";

		$trad['dAdresseLDAP']	= $ldap_adresse;
		$trad['dPortLDAP']		= $ldap_port;
		$trad['dLoginLDAP']		= $ldap_login;
		$trad['dPwdLDAP']		= $ldap_pwd;
		$trad['dUseTLS']		= $use_tls;
		if (isset($ldap_filter))
			$trad['dFilterLDAP']	= $ldap_filter;
		if (isset($ldap_base))
			$trad['dBaseLDAP']		= $ldap_base;
		//TODO: Ajouter les informations pour les groupes

		echo $twig->render('admin_config_ldap1.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings));
	}
	else if ($etape == 0)
	{
		if (!(function_exists("ldap_connect")))
		{
			$trad['dMesgSysteme'] = "Attention : les fonctions liées à l'authentification LDAP ne sont pas activées sur votre serveur PHP. La configuration LDAP est donc actuellement impossible.";
			$trad['dMesgTitreSysteme'] = $trad['titre_ldap'];
			$trad['dConfigImpossible'] = 1;
		}
		else
		{
			$trad['dValid'] = $valid;

			if (@file_exists("../personnalisation/config_ldap.inc.php"))
			{
				$test_chemin = '';
				include("../personnalisation/config_ldap.inc.php");
				if (($ldap_adresse != '') && ($ldap_port != ''))
				{
					$ok = "OK";
					$failed = "Echec";

					$ds = grr_connect_ldap($ldap_adresse,$ldap_port,$ldap_login,$ldap_pwd,$use_tls,'y');
					if ($ds == "error_1")
					{
						$trad['dMesgTitreSystemeDanger'] = "Test de connexion à l'annuaire : ". $failed;
						$trad['dMesgSystemeDanger'] = "Impossible d'utiliser la norme LDAP V3";
					}
					else if ($ds == "error_2")
					{
						$trad['dMesgTitreSystemeDanger'] = "Test de connexion à l'annuaire : ". $failed;
						$trad['dMesgSystemeDanger'] = "Impossible d'utiliser TLS";
					}
					else if ($ds == "error_3")
					{
						$trad['dMesgTitreSystemeDanger'] = "Test de connexion à l'annuaire : ". $failed;
						$trad['dMesgSystemeDanger'] = "Connexion établie mais l'identification auprès du serveur a échoué";
					}
					else if ($ds == "error_4")
					{
						$trad['dMesgTitreSystemeDanger'] = "Test de connexion à l'annuaire : ". $failed;
						$trad['dMesgSystemeDanger'] = "Impossible d'établir la connexion";
					}
					else if (!$ds)
						echo encode_message_utf8($failed)."</h3></div>";
					else
					{
						$trad['dMesgTitreOkConnexion'] = "Test de connexion à l'annuaire : ". $ok;

						$result = "";
						$result = grr_ldap_search_user($ds, $ldap_base, "objectClass", "*",$ldap_filter,"y");
						if ($result == "error_1")
						{

							$test_chemin = 'failed';
							if ($ldap_filter == "")
							{
								$trad['dMesgTitreSystemeDanger'] = "Test de recherche sur l'annuaire avec le chemin spécifié : ". $failed;
								$trad['dMesgSystemeDanger'] = "Le chemin que vous avez choisi ne semble pas valide";
							}
							else
							{
								$trad['dMesgTitreSystemeDanger'] = "Test de recherche sur l'annuaire avec le chemin spécifié : ". $failed;
								$trad['dMesgSystemeDanger'] = "Le chemin et/ou le filtre additionnel que vous avez choisi ne semblent pas valides";
							}
						}
						else if ($result == "error_2")
						{
							$test_chemin = 'failed';
							if ($ldap_filter == "")
							{
								$trad['dMesgTitreSystemeDanger'] = "Test de recherche sur l'annuaire avec le chemin spécifié : ". $failed;
								$trad['dMesgSystemeDanger'] = "Le chemin que vous avez choisi semble valide mais la recherche sur ce chemin ne renvoie aucun résultat.";
							}
							else
							{
								$trad['dMesgTitreSystemeDanger'] = "Test de recherche sur l'annuaire avec le chemin spécifié : ". $failed;
								$trad['dMesgSystemeDanger'] = "Le chemin et le filtre additionnel que vous avez choisi semblent valides  mais la recherche sur ce chemin ne renvoie aucun résultat.";
							}
						}
						else
							$trad['dMesgTitreOkChemin'] = "Test de recherche sur l'annuaire avec le chemin spécifié : ". $ok;
						}
				}
			}

			if (@file_exists("../personnalisation/config_ldap.inc.php"))
			{
				$trad['dFichierCongLDAP'] = 1;
				$trad['dMesgTitreConfigActuel'] = "Configuration actuelle";
				$trad['dMesgConfActuel'] = "(Informations contenues dans le fichier \"config_ldap.inc.php\") :";
				$trad['dMesgConfActuel'] .= "<li>Adresse de l'annuaire LDAP <b>: ".$ldap_adresse."</b></li>";
				$trad['dMesgConfActuel'] .= "<li>Port utilisé : <b>".$ldap_port."</b></li>";
				if ($test_chemin == 'failed')
					$trad['dMesgConfActuel'] .= "<li><div class=\"alert alert-danger\" role=\"alert\">Chemin d'accès dans l'annuaire : <b> ".$ldap_base."</b></div></li>";
				else
					$trad['dMesgConfActuel'] .= "<li>Chemin d'accès dans l'annuaire : <b> ".$ldap_base."</b></li>";
				if ($ldap_filter!="")
					$ldap_filter_text = $ldap_filter;
				else
					$ldap_filter_text = "non";
				if (($test_chemin == 'failed') && ($ldap_filter!=""))
					$trad['dMesgConfActuel'] .= "<li><div class=\"alert alert-danger\" role=\"alert\">Filtre LDAP supplémentaire : <b> ".$ldap_filter_text."</b></div></li>";
				else
					$trad['dMesgConfActuel'] .= "<li>Filtre LDAP supplémentaire : <b> ".$ldap_filter_text."</b></li>";
				if ($ldap_login) {
					$trad['dMesgConfActuel'] .= "<li>Compte pour l'accès : <br />";
					$trad['dMesgConfActuel'] .= "Identifiant : <b>".$ldap_login."</b><br />";
					$ldap_pwd_hide = "";
					for ($i=0;$i<strlen($ldap_pwd);$i++)
						$ldap_pwd_hide .= "*";
					$trad['dMesgConfActuel'] .= "Mot de passe : <b>".$ldap_pwd_hide."</b></li>";
				}
				else
					$trad['dMesgConfActuel'] .= "<li>Accès anonyme.</li>";
				if ($use_tls)
					$use_tls_text = "oui";
				else
					$use_tls_text = "non";
				$trad['dMesgConfActuel'] .= "<li>Utiliser TLS : <b>".$use_tls_text."</b></li>";
				$trad['dMesgConfActuel'] .= "</ul>Vous pouvez procéder à une nouvelle configuration LDAP.<br />";
			}
			else
			{
				$trad['dFichierCongLDAP'] = 0;
			}
		}

		echo $twig->render('admin_config_ldap0.twig', array('liensMenu' => $menuAdminT, 'liensMenuN2' => $menuAdminTN2, 'd' => $d, 'trad' => $trad, 'settings' => $AllSettings));
	}

?>