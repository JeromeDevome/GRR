<?php
/**
 * admin_accueil
 * Interface d'accueil de l'administration des domaines et des ressources
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2009-02-27 13:28:19 $
 * @author    Laurent Delineau <laurent.delineau@ac-poitiers.fr>
 * @copyright Copyright 2003-2008 Laurent Delineau
 * @link      http://www.gnu.org/licenses/licenses.html
 * @package   root
 * @version   $Id: admin_accueil.php,v 1.4 2009-02-27 13:28:19 grr Exp $
 * @filesource
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GRR is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GRR; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
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
