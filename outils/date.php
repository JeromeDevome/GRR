<?php
echo "Time : " .time()."<br>";
echo "Date du serveur : " .date('d-m-Y')." (Jour-Mois-Annee)<br>";
echo "Heure du serveur : " .date("H:i")."<br>";
echo "Timezone : ".date_default_timezone_get()." (date_default_timezone_set)<br>";
?>