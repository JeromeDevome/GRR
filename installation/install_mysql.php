<?php
/**
 * install_mysql.php
 * Interface d'installation de GRR pour un environnement mysql
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2026-11-15 16:30$
 * @author    Laurent Delineau & JeromeB & Yan Naessens
 * @copyright Since 2003 Team DEVOME - JeromeB
 * @link      http://www.gnu.org/licenses/licenses.html
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Toute l'installation est affichée par une seule page, templates/installation.twig,
 * l'étape en cours étant donnée par $d['etape'] :
 *   'droits' (connect.inc.php non modifiable), 0 (prérequis), 1 à 4, 5 (terminé / erreur / déjà installé).
 */

require_once("../include/config.inc.php");
require_once("../include/misc.inc.php");
require_once("../include/securite.class.php");
require_once("../include/functions.inc.php");

require '../vendor/autoload.php';

$twig = new \Twig\Environment(new \Twig\Loader\FilesystemLoader(__DIR__ . '/templates'));

$nom_fic	= "../personnalisation/connect.inc.php";
// Étape demandée : null (arrivée sur l'assistant) ou 0 à 5
$etape		= (isset($_GET["etape"]) && ctype_digit((string) $_GET["etape"])) ? (int) $_GET["etape"] : NULL;
$adresse_db = isset($_POST["adresse_db"]) ? (string) $_POST["adresse_db"] : NULL;
$port_db	= isset($_POST["port_db"]) ? SecuChaine::ValideNetworkPort($_POST["port_db"]) : NULL;
$login_db	= isset($_POST["login_db"]) ? (string) $_POST["login_db"] : NULL;
$pass_db	= isset($_POST["pass_db"]) ? (string) $_POST["pass_db"] : NULL;
$choix_db	= isset($_POST["choix_db"]) ? (string) $_POST["choix_db"] : NULL;
$table_new	= isset($_POST["table_new"]) ? (string) $_POST["table_new"] : NULL;
$table_prefix = isset($_POST["table_prefix"]) ? (string) $_POST["table_prefix"] : NULL;

$d = array(
	'dbsys'			=> $dbsys,
	'nom_fic'		=> $nom_fic,
	'adresse_db'	=> $adresse_db,
	'port_db'		=> $port_db,
	'login_db'		=> $login_db,
	'pass_db'		=> $pass_db,
	'choix_db'		=> $choix_db,
	'table_new'		=> $table_new,
	'table_prefix'	=> $table_prefix,
	'version_grr'	=> $version_grr,
);

if ( stristr($version_grr, 'a') || stristr($version_grr, 'b') || stristr($version_grr, 'RC') || stristr($versionReposite, 'github') || stristr($versionReposite, 'alpha')|| stristr($versionReposite, 'beta') || stristr($versionReposite, 'RC') ){
	$d['alerteVersion'] = 1;
}

/** Affiche la page unique de l'assistant à l'étape $d['etape'], puis termine le script. */
function afficher_installation(\Twig\Environment $twig, array $d): never
{
	echo $twig->render('installation.twig', array('d' => $d));
	exit;
}

/** Connexion au serveur SQL (sans base sélectionnée), ou null si elle échoue. */
function connexion_serveur(?string $adresse, ?string $login, ?string $motDePasse, $port): ?mysqli
{
	try {
		$db = @mysqli_connect((string) $adresse, (string) $login, (string) $motDePasse, "", (int) $port);
	} catch (mysqli_sql_exception $e) {
		$db = false;
	}
	return $db ?: null;
}

/** Sélectionne une base ; false si elle n'existe pas ou n'est pas accessible. */
function selection_base(mysqli $db, string $base): bool
{
	try {
		return $base !== '' && mysqli_select_db($db, $base);
	} catch (mysqli_sql_exception $e) {
		return false;
	}
}

/**
 * Noms des bases visibles par l'utilisateur SQL (vide si SHOW DATABASES est interdit).
 * @return list<string>
 */
function liste_bases(mysqli $db): array
{
	$bases = array();
	try {
		$result = mysqli_query($db, "SHOW DATABASES");
		while ($result && ($row = mysqli_fetch_row($result)))
			$bases[] = $row[0];
	} catch (mysqli_sql_exception $e) {
	}
	return $bases;
}

/** Le nom de base et le préfixe sont insérés tels quels dans les requêtes : lettres, chiffres et « _ » seulement. */
function nom_sql_valide(?string $nom): bool
{
	return is_string($nom) && preg_match('/^[A-Za-z0-9_]+$/D', $nom) === 1;
}

// ------------------------------------------------------------------------------------------
// GRR déjà configuré (connect.inc.php présent et base joignable) : état de l'installation
// ------------------------------------------------------------------------------------------
if (@file_exists($nom_fic))
{
	$table_prefix_from_user = $table_prefix;
	include $nom_fic;	// définit $dbHost, $dbDb, $dbUser, $dbPass, $table_prefix, $dbPort
	if (empty($table_prefix) && $table_prefix_from_user !== NULL)
		$table_prefix = $table_prefix_from_user;

	$db = isset($dbHost, $dbUser, $dbPass, $dbDb) ? connexion_serveur($dbHost, $dbUser, $dbPass, $dbPort ?? 3306) : null;
	if ($db && selection_base($db, (string) $dbDb))
	{
		$tableManquantes = "";
		foreach ($liste_tables as $table)
		{
			try {
				$test = mysqli_query($db, "SELECT count(*) FROM ".$table_prefix.$table);
			} catch (mysqli_sql_exception $e) {
				$test = false;
			}
			if (!$test)
				$tableManquantes .= " ". $table_prefix.$table;
		}
		try {
			$call_test = mysqli_query($db, "SELECT * FROM ".$table_prefix."_setting WHERE NAME='sessionMaxLength'");
			$reglagesPresents = $call_test && mysqli_num_rows($call_test) > 0;
		} catch (mysqli_sql_exception $e) {
			$reglagesPresents = false;
		}

		$d['table_prefix'] = $table_prefix;
		if ($tableManquantes != "")
		{
			$d['erreurE5'] = 1;
			$d['tableManquantes'] = $tableManquantes;
		}
		elseif (!$reglagesPresents)
			$d['erreurE5'] = 2;
		elseif ($etape !== 5)
			$d['erreurE5'] = 3;	// installation complète : l'assistant n'est plus accessible

		$d['etape'] = 5;
		afficher_installation($twig, $d);
	}
	// Fichier présent mais connexion impossible : on laisse l'assistant le réécrire
	$table_prefix = $table_prefix_from_user;
}

// ------------------------------------------------------------------------------------------
// Étape 4 : création des tables et écriture de connect.inc.php
// ------------------------------------------------------------------------------------------
if ($etape === 4)
{
	$mdp1 = isset($_POST["mdp1"]) ? (string) $_POST["mdp1"] : '';
	$mdp2 = isset($_POST["mdp2"]) ? (string) $_POST["mdp2"] : '';
	$company = isset($_POST["company"]) ? (string) $_POST["company"] : 'Nom du GRR';
	$grr_url = isset($_POST["grr_url"]) ? (string) $_POST["grr_url"] : 'https://mygrr.net/';
	$webmaster_email = isset($_POST["webmaster_email"]) ? (string) $_POST["webmaster_email"] : 'webmaster_grr@test.fr';
	$support_email = isset($_POST["technical_support_email"]) ? (string) $_POST["technical_support_email"] : 'support_grr@test.fr';
	$email = isset($_POST["email"]) ? (string) $_POST["email"] : 'testgrr@test.fr';
	// Réaffichées si l'étape 3 doit être corrigée
	$d['saisie'] = array('company' => $company, 'grr_url' => $grr_url, 'webmaster_email' => $webmaster_email, 'technical_support_email' => $support_email, 'email' => $email);

	if (!(strlen($mdp1) > 7 && $mdp1 == $mdp2 && $mdp1 != $email && $mdp1 != $webmaster_email && $mdp1 != $support_email))
	{
		$d['etape'] = 3;
		$d['erreurMDP'] = 1;
		afficher_installation($twig, $d);
	}

	$db = connexion_serveur($adresse_db, $login_db, $pass_db, $port_db);
	if (!$db || !nom_sql_valide($table_prefix) || !selection_base($db, (string) $choix_db))
	{
		$d['etape'] = 2;
		$d['erreurSelectBase'] = 1;
		$d['bases'] = $db ? liste_bases($db) : array();
		afficher_installation($twig, $d);
	}

	mysqli_set_charset($db, 'utf8mb4');
	$remplacements = array(
		"DROP TABLE IF EXISTS grr"	=> "DROP TABLE IF EXISTS ".$table_prefix,
		"CREATE TABLE grr"			=> "CREATE TABLE ".$table_prefix,
		"INSERT INTO grr"			=> "INSERT INTO ".$table_prefix,
		"VariableInstal01"			=> mysqli_real_escape_string($db, $company),
		"VariableInstal02"			=> mysqli_real_escape_string($db, $grr_url),
		"VariableInstal03"			=> mysqli_real_escape_string($db, $webmaster_email),
		"VariableInstal04"			=> mysqli_real_escape_string($db, $support_email),
		"VariableInstal05"			=> mysqli_real_escape_string($db, password_hash($mdp1, PASSWORD_DEFAULT)),
		"VariableInstal06"			=> mysqli_real_escape_string($db, $email),
		"VariableInstal07"			=> mysqli_real_escape_string($db, $version_grr),
	);

	// Structure et données initiales : une requête par ligne de tables.my.sql
	$erreursSql = array();
	$fd = fopen(__DIR__."/tables.my.sql", "r");
	while ($fd && !feof($fd))
	{
		$query = trim((string) fgets($fd, 5000));
		if ($query == '')
			continue;
		$query = strtr($query, $remplacements);
		try {
			$reg = mysqli_query($db, $query);
		} catch (mysqli_sql_exception $e) {
			$reg = false;
		}
		if (!$reg)
			$erreursSql[] = mb_strimwidth($query, 0, 300, '…').' — '.mysqli_error($db);
	}
	if ($fd)
		fclose($fd);
	else
		$erreursSql[] = "Fichier tables.my.sql illisible.";

	// Réglages par défaut absents du script SQL
	if (!$erreursSql)
	{
		try {
			$existe = mysqli_prepare($db, "SELECT COUNT(*) FROM ".$table_prefix."_setting WHERE NAME = ?");
			$ajout = mysqli_prepare($db, "INSERT INTO ".$table_prefix."_setting (NAME, VALUE) VALUES (?, ?)");
			foreach ($liste_settings as $param_name => $param_value)
			{
				$param_name = (string) $param_name;
				$param_value = (string) $param_value;
				mysqli_stmt_bind_param($existe, 's', $param_name);
				mysqli_stmt_execute($existe);
				mysqli_stmt_bind_result($existe, $nombre);
				mysqli_stmt_fetch($existe);
				mysqli_stmt_free_result($existe);
				if ($nombre == 0)
				{
					mysqli_stmt_bind_param($ajout, 'ss', $param_name, $param_value);
					mysqli_stmt_execute($ajout);
				}
			}
		} catch (mysqli_sql_exception $e) {
			$erreursSql[] = "Réglages par défaut : ".$e->getMessage();
		}
	}

	// Fichier de connexion
	$ok = !$erreursSql;
	if ($ok)
	{
		$conn = "<"."?php\n";
		$conn .= "# Les quatre lignes suivantes sont à modifier selon votre configuration\n";
		$conn .= "# ligne suivante : le nom du serveur qui herberge votre base sql.\n";
		$conn .= "# Si c'est le même que celui qui heberge les scripts, mettre \"localhost\"\n";
		$conn .= '$dbHost=' . var_export($adresse_db, true) . ";\n";
		$conn .= "# ligne suivante : le nom de votre base sql\n";
		$conn .= '$dbDb=' . var_export($choix_db, true) . ";\n";
		$conn .= "# ligne suivante : le nom de l'utilisateur sql qui a les droits sur la base\n";
		$conn .= '$dbUser=' . var_export($login_db, true) . ";\n";
		$conn .= "# ligne suivante : le mot de passe de l'utilisateur sql ci-dessus\n";
		$conn .= '$dbPass=' . var_export($pass_db, true) . ";\n";
		$conn .= "# ligne suivante : préfixe du nom des tables de données\n";
		$conn .= '$table_prefix=' . var_export($table_prefix, true) . ";\n";
		$conn .= "# ligne suivante : Port MySQL laissé par défaut\n";
		$conn .= '$dbPort=' . var_export($port_db, true) . ";\n";
		$conn .= "# ligne suivante : adaptation EnvOLE\n";
		$conn .= "\$apikey=\"mypassphrase\";\n";
		$conn .= "# ligne suivante : secret Symfony (APP_SECRET), propre à cette installation\n";
		$conn .= '$appSecret=' . var_export(bin2hex(random_bytes(32)), true) . ";\n";
		$conn .= "?".">";
		$ok = @file_put_contents($nom_fic, $conn, LOCK_EX) !== false;
	}

	if ($ok)
	{
		$d['etape'] = 4;
		afficher_installation($twig, $d);
	}

	$d['etape'] = 2;
	$d['erreurCreationBase'] = 1;
	$d['erreursSql'] = array_slice($erreursSql, 0, 10);
	$d['bases'] = liste_bases($db);
	afficher_installation($twig, $d);
}

// ------------------------------------------------------------------------------------------
// Étape 3 : sélection ou création de la base, puis formulaire « Votre GRR »
// ------------------------------------------------------------------------------------------
if ($etape === 3)
{
	$db = connexion_serveur($adresse_db, $login_db, $pass_db, $port_db);
	$sel_db = ($choix_db == "new_grr") ? (string) $table_new : (string) $choix_db;
	$nomsValides = nom_sql_valide($table_prefix) && ($choix_db != "new_grr" || nom_sql_valide($sel_db));

	if ($db && $nomsValides && $choix_db == "new_grr")
	{
		try {
			mysqli_query($db, "CREATE DATABASE `".$sel_db."`");
		} catch (mysqli_sql_exception $e) {
			// base déjà existante ou droits insuffisants : la sélection ci-dessous tranche
		}
	}

	if ($db && $nomsValides && selection_base($db, $sel_db))
	{
		$d['etape'] = 3;
		$d['SelectBase'] = 1;
		$d['choix_db'] = $sel_db;
		$d['adresseServeur'] = explode('installation/', "https://".$_SERVER['SERVER_NAME'].$_SERVER["REQUEST_URI"])[0];
		afficher_installation($twig, $d);
	}

	$d['etape'] = 2;
	$d[$nomsValides ? 'erreurSelectBase' : 'erreurNom'] = 1;
	$d['bases'] = $db ? liste_bases($db) : array();
	afficher_installation($twig, $d);
}

// ------------------------------------------------------------------------------------------
// Étape 2 : connexion au serveur SQL, puis choix de la base
// ------------------------------------------------------------------------------------------
if ($etape === 2)
{
	$db = connexion_serveur($adresse_db, $login_db, $pass_db, $port_db);
	if (!$db && $adresse_db == "localhost")
	{
		// Certains hébergeurs attendent une adresse vide plutôt que « localhost »
		$db = connexion_serveur("", $login_db, $pass_db, $port_db);
		if ($db)
			$d['adresse_db'] = "";
	}

	if ($db)
	{
		$d['etape'] = 2;
		$d['connexionReussi'] = 1;
		$d['bases'] = liste_bases($db);
		afficher_installation($twig, $d);
	}

	$d['etape'] = 1;
	$d['connexionEchoue'] = 1;
	afficher_installation($twig, $d);
}

// ------------------------------------------------------------------------------------------
// Étape 1 : formulaire de connexion au serveur SQL
// ------------------------------------------------------------------------------------------
if ($etape === 1)
{
	$d['etape'] = 1;
	afficher_installation($twig, $d);
}

// ------------------------------------------------------------------------------------------
// Arrivée sur l'assistant : connect.inc.php doit pouvoir être écrit
// ------------------------------------------------------------------------------------------
if ($etape === NULL)
{
	$fichierExiste = @file_exists($nom_fic);
	$erreur = '';
	if ($fichierExiste && !is_writable($nom_fic))
		$erreur = "Le fichier \"".$nom_fic."\" n'est pas accessible en écriture.";
	elseif (!$fichierExiste && !is_writable(dirname($nom_fic)))
		$erreur = "Impossible de créer le fichier \"".$nom_fic."\".";

	if ($erreur != '')
	{
		$d['etape'] = 'droits';
		$d['erreurDroits'] = $erreur;
		$d['fichierExiste'] = $fichierExiste;
		$d['fichierOriExiste'] = @file_exists($nom_fic.".ori");
		afficher_installation($twig, $d);
	}
}

// ------------------------------------------------------------------------------------------
// Prérequis (étape 0, et toute étape inconnue)
// ------------------------------------------------------------------------------------------
$d['etape'] = 0;

$d['phpserveur'] = phpversion();
$d['phpmini'] = $php_mini;
$d['phpmaxi'] = $php_maxi;
$d['phpmaxitest'] = $php_max_valide;

if (version_compare(phpversion(), $php_mini, '<')) { // Version inférieur
	$d['checkPHP'] = "bg-red";
	$d['commentairePHP'] = "Votre version PHP est inférieur au prérequis";
} elseif (version_compare(phpversion(), $php_max_valide, '<=')) { // Testé et validé c'est vert !
	$d['checkPHP'] = "bg-green";
} elseif ($php_maxi == "" && version_compare(phpversion(), $php_max_valide, '>')) { // On dépasse la limite mais c'est pas testé
	$d['checkPHP'] = "bg-orange";
	$d['commentairePHP'] = "Votre version PHP semble être correcte mais n'a pas était validé.";
} elseif ($php_maxi != "" && version_compare(phpversion(), $php_maxi, '<=')) {
	$d['checkPHP'] = "bg-orange";
	$d['commentairePHP'] = "Votre version PHP semble être correcte mais n'a pas était validé.";
} elseif ($php_maxi != "" && version_compare(phpversion(), $php_maxi, '>')) {
	$d['checkPHP'] = "bg-red";
	$d['commentairePHP'] = "GRR n'est pas encore compatible avec cette version de PHP.";
}

$d['mysqlmini'] = $mysql_mini;
$d['mysqlmaxi'] = $mysql_maxi;
$d['mysqltest'] = $mysql_max_valide;

$d['extensions'] = array();
foreach (array('fileinfo', 'mbstring', 'mysqli', 'mysqlnd', 'xml', 'gd', 'intl') as $extension)
	$d['extensions'][$extension] = extension_loaded($extension);

afficher_installation($twig, $d);
