<?php
/**
 * admin_accueil
 * Interface d'accueil de l'administration des domaines et des ressources
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2017-12-16 14:00$
 * @author    Laurent Delineau & JeromeB
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
include "../include/admin.inc.php";
$grr_script_name = "admin_accueil.php";
$back = '';
if (isset($_SERVER['HTTP_REFERER']))
	$back = htmlspecialchars($_SERVER['HTTP_REFERER']);
if ((authGetUserLevel(getUserName(), -1, 'area') < 4) && (authGetUserLevel(getUserName(), -1, 'user') !=  1))
{
	showAccessDenied($back);
	exit();
}
print_header("", "", "", $type="with_session");
include "admin_col_gauche.php";
?>
<table>
	<tr>
		<td>
			<img src="../img_grr/totem_grr.png" alt="GRR !" class="image" />
		</td>
		<td align="center" >
			<br /><br />
			<p style="font-size:20pt">
				<?php echo get_vocab("admin"); ?>
			</p>
			<p style="font-size:40pt">
				<i>GRR !</i>
			</p>
		</td>
	</tr>
</table>
</td>
</tr>
</table>
</body>
</html>
