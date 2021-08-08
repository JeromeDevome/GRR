<?php
/**
 * contactFormulaire.php
 * Formulaire d'envoi de mail demandant une réservation
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2021-08-08 16:58$
 * @author    JeromeB & Yan Naessens
 * @copyright Copyright 2003-2021 Team DEVOME - JeromeB
 * @link      http://www.gnu.org/licenses/licenses.html
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 */
$grr_script_name = "contactFormulaire.php";
include "include/connect.inc.php";
include "include/config.inc.php";
include "include/misc.inc.php";
include "include/functions.inc.php";
include "include/$dbsys.inc.php";
include "include/mincals.inc.php";
include "include/mrbs_sql.inc.php";

require_once("./include/settings.class.php");
if (!Settings::load())
	die("Erreur chargement settings");
require_once("./include/session.inc.php");
include "./include/resume_session.php";
include "./include/language.inc.php";

// pour le traitement des modules
include "./include/hook.class.php";
// contrôle d'accès pour limiter les demandes
if (!acces_formulaire_reservation()){
    begin_page('','','','no_session');
    showAccessDenied(page_accueil());
    die();
}
// code HTML
header('Content-Type: text/html; charset=utf-8');
if (!isset($_COOKIE['open']))
{
	//setcookie("open", "true", time()+3600, "", "", false, false);
    header('Set-Cookie: open=true; SameSite=Lax');
}
echo '<!DOCTYPE html>'.PHP_EOL;
echo '<html lang="fr">'.PHP_EOL;
// section <head>
echo pageHead2(Settings::get("company"),"no_session");
// section <body>
echo "<body>";
// Menu du haut = section <header>
echo "<header>";
pageHeader2($day, $month, $year, "no_session");
echo "</header>";
// Debut de la page
echo '<section>'.PHP_EOL;
bouton_retour_haut();
echo '	<form id="frmContact" method="post" action="traitementcontact.php">'.PHP_EOL;
echo '	<div id="formContact" class="container">'.PHP_EOL;
echo '		<div class="row">'.PHP_EOL;
echo '			<fieldset>'.PHP_EOL;
echo '				<legend><b>'.get_vocab('Vos_coordonnees').'</b></legend>'.PHP_EOL;
echo '				<div class="col-md-6 col-xs-12">'.PHP_EOL;
echo '					<div class="form-group">'.PHP_EOL;
echo '						<div class="input-group">'.PHP_EOL;
echo '							<div class="input-group-addon"><span class="glyphicon glyphicon-user"></span></div>'.PHP_EOL;
echo '							<input class="form-control" type="text" id="nom"  size="8" name="nom" placeholder="'.get_vocab('Votre_nom').'" required />'.PHP_EOL;
echo '						</div>'.PHP_EOL;
 echo '					</div>'.PHP_EOL;
 echo '					<div class="form-group">'.PHP_EOL;
 echo '						<div class="input-group">'.PHP_EOL;
 echo '							<div class="input-group-addon"><span class="glyphicon glyphicon-user"></span></div>'.PHP_EOL;
 echo '							<input class="form-control" type="text" size="8" id="prenom"  name="prenom" placeholder="'.get_vocab('Votre_prenom').'" />'.PHP_EOL;
 echo '						</div>'.PHP_EOL;
 echo '					</div>'.PHP_EOL;
 echo '				</div>'.PHP_EOL;
 echo '				<div class="col-md-6 col-xs-12">'.PHP_EOL;
 echo '					<div class="form-group">'.PHP_EOL;
 echo '						<div class="input-group">'.PHP_EOL;
 echo '							<span class="input-group-addon">@</span>'.PHP_EOL;
 echo '							<input class="form-control" type="email" id="email" size="8" name="email" placeholder="'.get_vocab('Your_email').'" required />'.PHP_EOL;
 echo '						</div>'.PHP_EOL;
 echo '					</div>'.PHP_EOL;
 echo '					<div class="form-group">'.PHP_EOL;
 echo '						<div class="input-group">'.PHP_EOL;
 echo '							<div class="input-group-addon"><span class="glyphicon glyphicon-earphone"></span></div>'.PHP_EOL;
 echo '							<input class="form-control" type="text" size="8" maxlength="14" id="telephone" name="telephone" placeholder="'.get_vocab('YourPhoneNr').'" />'.PHP_EOL;
 echo '						</div>'.PHP_EOL;
 echo '					</div>'.PHP_EOL;
 echo '				</div>'.PHP_EOL;
 echo '            </fieldset>'.PHP_EOL;
 echo '        </div>'.PHP_EOL;
 echo '		<div class="row">'.PHP_EOL;
 echo '            <fieldset>'.PHP_EOL;
 echo '				<legend><b>'.get_vocab('entry').'</b></legend>'.PHP_EOL;
 echo '				<div class="col-md-6 col-sm-12">'.PHP_EOL;
 echo '				<label for="subject">'.get_vocab('Sujet').get_vocab('deux_points').'</label>'.PHP_EOL;
 echo '				<textarea class="form-control" id="subject" name="sujet" cols="30" rows="4" required></textarea><br/>'.PHP_EOL;
 echo '				<label>'.get_vocab('areas').'</label>'.PHP_EOL;
 echo '				<select id="area" name="area" class="form-control" required>'.PHP_EOL;
 echo '					<option selected disabled>'.get_vocab('SELECT_AREA').'</option>'.PHP_EOL;
                        // $sql_areaName = "SELECT id, area_name FROM ".TABLE_PREFIX."_area ORDER BY area_name";
						$sql_areaName = "SELECT id, area_name FROM ".TABLE_PREFIX."_area WHERE access LIKE 'a' ORDER BY area_name";
						// si on ne veut pas montrer les domaines à accès restreint
                        $res_areaName = grr_sql_query($sql_areaName);
                        for ($i = 0; ($row_areaName = grr_sql_row($res_areaName, $i)); $i++)
                        {
                            if (authUserAccesArea(getUserName(),$row_areaName[0]) == 1)
                            {
                                $id = $row_areaName[0];
                                $area_name = $row_areaName[1];
								echo '<option value="'.$id.'"> '.$area_name.'</option>'.PHP_EOL;
                            }
                        }
echo '				</select>'.PHP_EOL;
 echo '				<label for="room">'.get_vocab('rooms').get_vocab('deux_points').'</label>'.PHP_EOL;
 echo '                <select id="room" name="room" class="form-control" required>'.PHP_EOL;
 echo '                        <option>'.get_vocab('SELECT_ROOM').'</option>'.PHP_EOL;
 echo '                </select>'.PHP_EOL;
 echo '				</div>'.PHP_EOL;
 echo '				<div class="col-md-6 col-sm-12">	'.PHP_EOL;
 echo '                <div class="form-group">'.PHP_EOL;
 echo '                    <div class="input-group">'.PHP_EOL;
 echo '						<br />'.PHP_EOL;
 echo '                        <label><b>'.get_vocab('Date').get_vocab('deux_points').'</b></label>'.PHP_EOL;
						jQuery_DatePicker('start');
echo '                        <br />'.PHP_EOL;
 echo '                    </div>'.PHP_EOL;
 echo '                </div>'.PHP_EOL;
 echo '				<div id="intervalle"> </div>'.PHP_EOL;
 echo '			</fieldset>'.PHP_EOL;
 echo '	        </div>'.PHP_EOL;
 echo '        </div>'.PHP_EOL;
 echo '    </div>'.PHP_EOL;
 echo '    </form>'.PHP_EOL;
 echo '</section>'.PHP_EOL;
 echo '<footer>'.PHP_EOL;
 echo '<div id="toTop"><b><?php echo get_vocab("top_of_page"); ?></b>'.PHP_EOL;
 echo '</footer>'.PHP_EOL;
?>
<script>
    $(document).ready(function()
    {
        var $domaine = $('#area');
        var $salle = $('#room');
		var $range = $('#intervalle');
        $domaine.on('change', function()
        {	
            var select = $(this);
            var id = select.find(":selected").attr("value");
            if (id != '')
            {
                $salle.empty();
                jQuery.ajax({
                    type: 'GET',
                    url: 'frmcontactlist.php',
                    data: {
                        id: id
                    },
                    success: function(returnData)
                    {
                        $("#room").html(returnData);
                    },
                    error: function(returnData)
                    {
                        alert('Erreur lors de l execution de la commande AJAX  ');
                    }
                });
				$range.empty();
				jQuery.ajax({
                    type: 'GET',
                    url: 'frmcontactrange.php',
                    data: {
                        id: id
                    },
                    success: function(returnData)
                    {
                        $("#intervalle").html(returnData);
                    },
                    error: function(returnData)
                    {
                        alert('Erreur lors de l execution de la commande AJAX  ');
                    }
                });
            }
        });
        if ( $(window).scrollTop() == 0 )
            $("#toTop").hide(1);
    });
</script>
</body>
</html>