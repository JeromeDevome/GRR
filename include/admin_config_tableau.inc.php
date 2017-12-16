<?php
/**
 * admin_config_tableau.inc.php
 *
 * Dernière modification : $Date: 2017-12-16 14:00$
 * @author    JeromeB & Laurent Delineau
 * @copyright Copyright 2003-2018 Team DEVOME - JeromeB
 * @link      http://www.gnu.org/licenses/licenses.html
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 */

?>
<script type="text/javascript">
	function changeclass(objet, myClass)
	{
		objet.className = myClass;
	}
</script>
<?php
echo "<table class=\"table_adm\">\n";
echo "<tbody>\n";
echo "<tr>";
for ($k = 1; $k < 6; $k++)
{
	echo "<td style=\"width:170px;\">";
	if ($page_config == $k)
	{
		echo "<div style=\"position: relative;\"><div class=\"onglet_off\" style=\"position: relative; top: 0px; padding-left: 20px; padding-right: 20px; min-height: 2.5em;\">".
		get_vocab('admin_config'.$k.'.php')."</div></div>";
	}
	else
	{
		echo "<div style=\"position: relative;\">".PHP_EOL;
		echo "<div onmouseover=\"changeclass(this, 'onglet_on');\" onmouseout=\"changeclass(this, 'onglet');\" class=\"onglet\" style=\"position: relative; top: 0px; padding-left: 20px; padding-right: 20px; min-height: 2.5em;\">".PHP_EOL;
		echo "<a href=\"./admin_config.php?page_config=".$k."\">".get_vocab('admin_config'.$k.'.php')."</a></div></div>".PHP_EOL;
	}
	echo "</td>\n";
}
echo "</tr></tbody></table>".PHP_EOL;
?>
