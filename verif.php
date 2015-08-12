<?php
include "include/connect.inc.php";
include "include/config.inc.php";
include "include/functions.inc.php";
include "include/$dbsys.inc.php";
include "include/mrbs_sql.inc.php"; include 'include/twigInit.php';
include "include/misc.inc.php";
// Paramètres langage
include "include/language.inc.php";
// Initialisation du test d'erreur
$erreur = 'n';
// Initialisation
$message_error = "";
jQuery_DatePicker("test");
$day = $_POST['mydate_start'];
print("<center>Bonjour $day</center>");
?>
