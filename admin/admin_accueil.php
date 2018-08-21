<?php
/**
 * admin_accueil
 * Interface d'accueil de l'administration des domaines et des ressources
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2018-08-19 15:15$
 * @author    Laurent Delineau & JeromeB & Yan Naessens
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
$grr_script_name = "admin_accueil.php";
 
include "../include/admin.inc.php";
$back = '';
if (isset($_SERVER['HTTP_REFERER']))
	$back = htmlspecialchars($_SERVER['HTTP_REFERER']);
if ((authGetUserLevel(getUserName(), -1, 'area') < 4) && (authGetUserLevel(getUserName(), -1, 'user') !=  1))
{
	showAccessDenied($back);
	exit();
}
start_page_w_header("", "", "", $type="with_session"); // affiche le header et la balise <section>
echo "<div class='row2'>";
include "admin_col_gauche2.php";
?>
    <div class="col-md-3 col-sm-4 col-xs-12">
        <img src="../img_grr/totem_grr.png" alt="GRR !" class="image" />
    </div>
    <div class="col-md-3 col-sm-4 col-xs-12">
        <div class="center">
            <br /><br />
            <p style="font-size:20pt">
                <?php echo get_vocab("admin"); ?>
            </p>
            <p style="font-size:40pt">
                <i>GRR !</i>
            </p>
        </div>
    </div>
</div>
</section>
</body>
</html>
