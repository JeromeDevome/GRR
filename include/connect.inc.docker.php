<?php
/**
 * Fichier de connexion à la base de données dynamique pour Docker.
 * Ce fichier lit les informations depuis les variables d'environnement.
 */

$dbHost = getenv("DB_HOST") ?: "db";
$dbDb   = getenv("DB_NAME") ?: "grr";
$dbUser = getenv("DB_USER") ?: "grr";
$dbPass = getenv("DB_PASSWORD") ?: "grr_password";
$dbPort = getenv("DB_PORT") ?: "3306";
?>
