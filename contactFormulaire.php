<?php
include "include/connect.inc.php";
include "include/config.inc.php";
include "include/misc.inc.php";
include "include/functions.inc.php";
include "include/$dbsys.inc.php";
include "include/mincals.inc.php";
include "include/mrbs_sql.inc.php";
include 'include/twigInit.php';
$grr_script_name = "contactFormulaire.php";
require_once("./include/settings.class.php");
if (!Settings::load())
	die("Erreur chargement settings");
require_once("./include/session.inc.php");
include "include/resume_session.php";
include "include/language.inc.php";
?>

<?php
if ((Settings::get("authentification_obli") == 0) && (getUserName() == ''))
	$type_session = "no_session";
print_header("", "", "", $type="with_session");
bouton_retour_haut();
bouton_aller_bas();
?>
	<script>
	
		function remplirdureemin(res)
		  { 
		  
				frmContact.dureemin.options.length = 0;
				frmContact.debdureemin.options.length = 0;
				resmin = res/60;
				nbiteration = 60/resmin;
				
				var y= document.getElementById("debdureemin");
				var x = document.getElementById("dureemin");
				valeur = 0;
				
				for (i=0;i<nbiteration;i++){
					frmContact.dureemin.options[i] = document.createElement("option");
					frmContact.debdureemin.options[i] = document.createElement("option");
					
					if(i==0){
						valeur = 00;
					}else{
						valeur = valeur + resmin;
					}
					
						frmContact.dureemin.options[i].text = valeur +" min";
						frmContact.dureemin.options[i].value = valeur;
						x.add(frmContact.dureemin.options[i]);
					
						frmContact.debdureemin.options[i].text = valeur +" min";
						frmContact.debdureemin.options[i].value = valeur;
						y.add(frmContact.debdureemin.options[i]);
					
					}
		
				frmContact.dureemin.options.selectedIndex = 0;
			}
	</script>
	
	
	
	<form id="frmContact" method="post" action="traitementcontact.php">
	<div id="formContact">
		<div class="row">
			
			<fieldset>
				
				<legend><b>Vos coordonnées</b></legend>
					
				<div class="col-lg-6 col-md-6 col-xs-6">
					
					<div class="form-group">
						<div class="input-group">
							<div class="input-group-addon"><span class="glyphicon glyphicon-user"></span></div>
							<input class="form-control" type="text" id="nom"  size="8" name="nom" placeholder="Votre nom" required/>
						</div>
					</div>
						
					<div class="form-group">
						<div class="input-group">
							<div class="input-group-addon"><span class="glyphicon glyphicon-user"></span></div>
							<input class="form-control" type="text" size="8" id="prenom"  name="prenom" placeholder="Votre prénom" />
						</div>
					</div>
					
				</div>
				<div class="col-lg-6 col-md-6 col-xs-6">
					<div class="form-group">
						<div class="input-group">
							<div class="input-group-addon"><span class="glyphicon glyphicon-envelope"></span></div>
							<input class="form-control" type="text" id="email" size="8" name="email" placeholder="Votre adresse de courriel" required />
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
						
							
						
						
						<script>
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
									
									remplirdureemin(resolution);
									//~ remplirdebdureemin(resolution);
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
									}
								});
							});
						</script>

						<label>Ressources : </label>
						<select id="room" name="room" class="form-control" required>
							<optgroup label="Salles">
								<option>SELECTIONNER UN DOMAINE </option>
							</select>
				</fieldset>
				
		</div>
				
				
		<div class="row">	
				
				<br>
				
				<legend><b> Date :</b></legend>
				<fieldset>

						<?php
						jQuery_DatePicker('start');
						?>

						<label >  Heure début :</label>
						<?php
							echo " <select class =\"test\" name=\"heure\"> ";
							for ($h = 1 ; $h < 24 ; $h++)
							{
								echo "<option value =\"$h\"> ".sprintf("%02d",$h)."h </option>".PHP_EOL;
							}
							echo "</select>";
							echo " <select id = 'debdureemin' class =\"test\" name=\"minutes\"> </select>";
						
					
						//~ jQuery_TimePicker('start_','','','');
						?>
								
							<input class="form-control" type="text" id="duree" size="8" name="duree" placeholder="Durée en heure" />
								
							<select id="dureemin" name="dureemin" class="form-control">

								<option> </option>
								<option> </option>
								
							</select>

			</fieldset>
		<br/>
		<br/>

			<div id="buttonsReservation" style="">
				<input class="btn btn-primary" type="submit" name="submit" value="Envoyer la demande de réservation">

				<input class="btn btn-primary" type="button" name="retouraccueil" value="Retour à l'accueil" onClick="javascript:location.href='javascript:history.go(-1)'">
			</div>
	</div>

	<div id="toTop">
	<?php echo get_vocab('top_of_page'); ?>
	</div>

	
	<div id="toBot">
	<?php echo get_vocab('bot_of_page'); ?>
	</div>
	
	<script>
	jQuery(document).ready(function() {
	jQuery("#formStep").validate({
      rules: {
        "email": {
			"email": true,
			"maxlength": 255
        }}
	})
});
jQuery.extend(jQuery.validator.messages, {
	required: "votre message",
	remote: "votre message",
	email: "votre message",
});
</script>
	
</div>
</form>

</body>
</html>