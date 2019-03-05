<?php
/**
 * contactFormulaire.php
 * Formulaire d'envoi de mail
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2017-12-16 14:00$
 * @author    JeromeB
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
include "include/connect.inc.php";
include "include/config.inc.php";
include "include/misc.inc.php";
include "include/functions.inc.php";
include "include/$dbsys.inc.php";
include "include/mincals.inc.php";
include "include/mrbs_sql.inc.php";
$grr_script_name = "contactFormulaire.php";
require_once("./include/settings.class.php");
if (!Settings::load())
	die("Erreur chargement settings");
require_once("./include/session.inc.php");
include "include/resume_session.php";
include "include/language.inc.php";
?>

<?php

$type_session = "no_session";
print_header("", "", "", $type="no_session");
bouton_retour_haut();

// Création du JSON pour que le formulaire soit dynamique au niveau de la durée ou du date début/date fin
$type_resa = grr_sql_query("SELECT room_name,type_affichage_reser FROM ".TABLE_PREFIX."_room WHERE who_can_see=0");
$rows = array();
while($r = mysqli_fetch_assoc($type_resa)) {
    $rows[] = $r;
}
$json = json_encode($rows);
?>
<link rel="stylesheet" type="text/css" href="themes/default/css/clockpicker.css">
<script type="text/javascript" src="js/clockpicker.js"></script>

	<form id='frmContact' method='POST' action='traitementcontact.php' >
		<div id='formContact'>
			<div class='row'>
			<legend><b>Vos coordonnées</b></legend>
			<div class="col-lg-6 col-md-6 col-xs-12">
					<div class="form-group">
						<div class="input-group">
							<div class="input-group-addon"><span class="glyphicon glyphicon-user"></span></div>
							<input class="form-control" type="text" id="nom"  size="8" name="nom" placeholder="Votre nom" required />
						</div>
					</div>
							
					<div class="form-group">
						<div class="input-group">
							<div class="input-group-addon"><span class="glyphicon glyphicon-user"></span></div>
							<input class="form-control" type="text" size="8" id="prenom"  name="prenom" placeholder="Votre prénom" required />
						</div>
					</div>
					
				</div>
				<div class="col-lg-6 col-md-6 col-xs-12">
					<div class="form-group">
						<div class="input-group">
							<div class="input-group-addon"><span class="glyphicon glyphicon-envelope"></span></div>
							<input class="form-control" type="text" id="email" size="8" name="email" placeholder="Votre adresse de courriel" required />
						</div>
					</div>
					
					<div class="form-group">
						<div class="input-group">
							<div class="input-group-addon"><span class="glyphicon glyphicon-earphone"></span></div>
							<input class="form-control" type="text" size="8" maxlength="14" id="telephone" name="telephone" placeholder="Votre numéro de téléphone" required />
						</div>
					</div>
				</div>
			</div>
			<div class='row'>
				<legend><b>Réservation :</b></legend>
				<label for="subject">Sujet :</label>
				<textarea class="form-control" id="subject" name="sujet" cols="30" rows="4"></textarea><br/>
				<label>Domaines : </label>
					<select id="area" name="area" class="form-control" required>
					<option>SELECTIONNER UN DOMAINE </option>
					<?php
					$sql_areaName = "SELECT id, area_name,resolution_area FROM ".TABLE_PREFIX."_area ORDER BY area_name";
					$res_areaName = grr_sql_query($sql_areaName);
					for ($i = 0; ($row_areaName = grr_sql_row($res_areaName, $i)); $i++)
					{
						if (authUserAccesArea(getUserName(),$row_areaName[0]) == 1)
						{
							$id = $row_areaName[0];
							$area_name = $row_areaName[1];
							$resolution_area = $row_areaName[2];
							echo '<option onclick="" value="'.$id."/".$resolution_area.'"> '.$area_name.'</option>'.PHP_EOL;
							}
						}
						?>
					</select>
				<label>Ressources : </label>
				<select id="room" name="room" class="form-control" required>
					<option>SELECTIONNER UNE RESSOURCE</option>
				</select>
			</div><br>
		
			<div class='row' id='datediv'>
				<legend><b>Date :</b></legend>
				<div id="dategenerale">
					  <div class="col-lg-6 col-md-6 col-xs-12">
					  <label for="date">Date de début :</label>
					  <input class="form-control" id="datepicker" name="date" placeholder="Cliquez ici pour renseigner une date"</input><br/>
					  </div>
					  <div class="col-lg-6 col-md-6 col-xs-12">
					  <label for="date">Heure de début :</label>
					  <input class="form-control" id="clockpicker" name="time" placeholder="Cliquez ici pour renseigner un horaire" data-autoclose="true"></input><br/>
				      </div>
				      
		<div id='time' style='display:none;'>	
			<div class="col-lg-10 col-md-6 col-xs-12">
				 <label for="date">Durée :</label>
				 <input class="form-control" type="number" min=0 name="duree" placeholder="Durée en nombre (exemple : 1 journée)"></input><br/>
			 </div>
			 <div class="col-lg-2 col-md-6 col-xs-12">
				 <label for="selection">Durée en ....</label>
			     <select name="selection" class="form-control" >
					<option>Minutes</option>
					<option>Heure(s)</option>
					<option>Jour(s)</option>
					<option>Semaine(s)</option>
					<option>Mois</option>
				</select>
			</div>
			</div>
		</div>
		
		<div id='debfin' style='display:none;'>		
			<div class="col-lg-6 col-md-6 col-xs-12">
				<label for="date">Date de Fin :</label>
				<input class="form-control" id="datepickerFin" name="datefin" placeholder="Cliquez ici pour renseigner une date de fin"</input><br/>
			</div>
			<div class="col-lg-6 col-md-6 col-xs-12">
				<label for="date">Heure de Fin :</label>
				<input class="form-control" id="clockpickerFin" name="timefin" placeholder="Cliquez ici pour renseigner un horaire de fin" data-autoclose="true"></input><br/>
			</div>
		</div>
			
		
		<div id="buttonsReservation" style='text-align: center;'>
			<input class="btn btn-primary" type="submit" name="submit" value="Envoyer la demande de réservation">
			<input class="btn btn-primary" type="button" name="retouraccueil" value="Retour à l'accueil" onClick="javascript:location.href='javascript:history.go(-1)'">
		</div>
	</form>
	<script>
		var userLang = navigator.language || navigator.userLanguage;
		 
		var optionsResa = $.extend({},   
              $.datepicker.regional[userLang], {  
                    dateFormat: 'dd-mm-yy',
					inline: true,
					changeMonth: true,
					changeYear: true } );
		
		var json_obj = <?php echo $json; ?> ;
				
		$('#clockpicker').clockpicker({inline: true});

		$('#clockpickerFin').clockpicker({inline: true});
			
		$('#datepicker').datepicker(optionsResa);

		$('#datepickerFin').datepicker(optionsResa);
			
		$(document).ready(function()
					{
						var $domaine = $('#area');
						var $salle = $('#room');
						$domaine.on('change', function()
						{	
							var select = $(this);
							var values = select.find(":selected").attr("value");
							var value = values.split('/');
							var id = value[0] ;
							var resolution = value[1] ;
							
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
										var first = $('#room :selected').text();
										for(var i = 0; i < json_obj.length; i++){
											if(json_obj[i]["room_name"] == first && json_obj[i]["type_affichage_reser"]==1){
												$("#time").hide();
												$("#debfin").show();
											} else if(json_obj[i]["room_name"] == first && json_obj[i]["type_affichage_reser"]==0){
												$("#time").show();
												$("#debfin").hide();
											} 
										}
										
									},
									error: function(returnData)
									{
										alert('Erreur lors de l execution de la commande AJAX  ');
									}
								});
							}
						});
					});
			
			$('#room').on('change', function (first) {
				var selected = $('#room option:selected').val();
				for(var i = 0; i < json_obj.length; i++){
					if(json_obj[i]["room_name"] == selected && json_obj[i]["type_affichage_reser"]==1){
						$("#time").hide();
						$("#debfin").show();
					} else if(json_obj[i]["room_name"] == selected && json_obj[i]["type_affichage_reser"]==0){
						$("#time").show();
						$("#debfin").hide();
					} 
				}
			});
		</script>
</body>
</html>
