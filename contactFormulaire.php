<?php
/**
 * contactFormulaire.php
 * Formulaire d'envoi de mail demandant une réservation
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2020-12-16 18:00$
 * @author    JeromeB & Yan Naessens
 * @copyright Copyright 2003-2020 Team DEVOME - JeromeB
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
//echo '<div id="toTop">',PHP_EOL,'<b>',get_vocab("top_of_page"),'</b>',PHP_EOL;
//echo '</div>',PHP_EOL;
bouton_retour_haut ();
?>	
	<form id="frmContact" method="post" action="traitementcontact.php">
	<div id="formContact" class="container">
		<div class="row">
			<fieldset>
				<legend><b>Vos coordonnées</b></legend>
				<div class="col-md-6 col-xs-12">
					<div class="form-group">
						<div class="input-group">
							<div class="input-group-addon"><span class="glyphicon glyphicon-user"></span></div>
							<input class="form-control" type="text" id="nom"  size="8" name="nom" placeholder="Votre nom" required />
						</div>
					</div>
					<div class="form-group">
						<div class="input-group">
							<div class="input-group-addon"><span class="glyphicon glyphicon-user"></span></div>
							<input class="form-control" type="text" size="8" id="prenom"  name="prenom" placeholder="Votre prénom" />
						</div>
					</div>
				</div>
				<div class="col-md-6 col-xs-12">
					<div class="form-group">
						<div class="input-group">
							<span class="input-group-addon">@</span>
							<input class="form-control" type="email" id="email" size="8" name="email" placeholder="Votre adresse de courriel" required />
						</div>
					</div>
					<div class="form-group">
						<div class="input-group">
							<div class="input-group-addon"><span class="glyphicon glyphicon-earphone"></span></div>
							<input class="form-control" type="text" size="8" maxlength="14" id="telephone" name="telephone" placeholder="Votre numéro de téléphone" />
						</div>
					</div>
				</div>
            </fieldset>
        </div>
		<div class="row">
            <fieldset>
				<legend><b>Réservation</b></legend>
				<div class="col-md-6 col-sm-12">
				<label for="subject">Sujet :</label>
				<textarea class="form-control" id="subject" name="sujet" cols="30" rows="4" required></textarea><br/>
				<label>Domaines : </label>
				<select id="area" name="area" class="form-control" required>
					<option selected disabled>SELECTIONNER UN DOMAINE </option>
					<?php
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
                    ?>
				</select>
				<label for="room">Ressources : </label>
                <select id="room" name="room" class="form-control" required>
                        <option>SELECTIONNER UNE RESSOURCE </option>
                </select>
				</div>
				<div class="col-md-6 col-sm-12">	
                <div class="form-group">
                    <div class="input-group">
						<br />
                        <label><b> Date :</b></label>
						<?php
						jQuery_DatePicker('start');
						?>
                        <br />
                    </div>
                </div>
				<div id="intervalle"> </div>
			</fieldset>
	        </div>
        </div>
    </div>
    </form>
</section>
<footer>
<div id="toTop"><b><?php echo get_vocab("top_of_page"); ?></b>
</footer>

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