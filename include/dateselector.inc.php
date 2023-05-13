<?php
/* 
 * dateselector.inc.php
 * Portion de code affichant un sélecteur de date à accès direct, couplé à un calendrier JQuery-datepicker
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2023-05-13 15:49$
 * @author    Laurent Delineau & Yan Naessens
 * @copyright Copyright 2003-2023 Team DEVOME - JeromeB
 * @link      http://www.gnu.org/licenses/licenses.html
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
*/
?>
<script type="text/javascript">
   	function onsubmitForm()
	{
	if(document.pressed == 'a')
	{
        document.getElementById('day').selectedIndex=<?php echo (date("d")-1); ?>;
		document.getElementById('month').selectedIndex=<?php echo (date("m")-1);?>;
		document.getElementById('year').selectedIndex=<?php echo (date("Y")-strftime("%Y", Settings::get("begin_bookings")));?>;
  	var p=location.pathname;
	   	if(!p.match("day.php") && !p.match("week.php") && !p.match("week_all.php") && !p.match("month.php") && !p.match("month_all.php") && !p.match("month_all2.php") && !p.match("year.php"))
    document.getElementById('myform').action ="day.php";
	}
    if(document.pressed == 'd')
      document.getElementById('myform').action ="day.php";
    if(document.pressed == 'w')
    <?php
    echo "		document.getElementById('myform').action = \"";
    if ($room=="")
      echo "week_all.php";
		else
      echo "week.php";
    echo "\";\n";
    ?>
    if(document.pressed == 'm')
    <?php
    echo "		document.getElementById('myform').action = \"";
    if ($room=="") {
      if (isset($_SESSION['type_month_all'])) {echo $_SESSION['type_month_all'].".php";}
      else {echo "month_all.php";}
    } else
      echo "month.php";
    echo "\";\n";
    ?>
    return true;
	}
</script>
<?php
echo '<form id="myform" action="" method="get" onsubmit="return onsubmitForm();"><div class="panel panel-default center ">';
jQuery_DatePicker(""); // mettre au point la visibilité par rapport à l'entête du planning, pages day, week et month_all2
echo '<p>';
if ((isset($area)) and ($area > 0))
    echo "<input type=\"hidden\" id=\"area_\" name=\"area\" value=\"$area\" />";
if ((isset($room)) and ($room > 0))
    echo "<input type=\"hidden\" id=\"room_\" name=\"room\" value=\"$room\" />";
/* le test suivant sera utile pour le menu ruban
if(Settins::get() != )
    echo '<input type="submit" value="'.get_vocab("gototoday").'" onclick="document.pressed=\'a\'" />'.PHP_EOL;
echo '<br />';*/
echo '<input type="submit" value="'.get_vocab("allday").'" onclick="document.pressed=\'d\'" />';
echo '<input type="submit" value="'.get_vocab("week").'" onclick="document.pressed=\'w\'" />';
echo '<input type="submit" value="'.get_vocab("month").'" onclick="document.pressed=\'m\'" />';
echo '</p></div></form>';