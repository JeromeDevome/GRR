<?php
 include("aconfig.php");

	$reponse["erreur"] = false;
	$reponse["message"] = [];

	// Valeur Debug;
	//$_POST['idjoueur'] = 1;

	if( !empty($_POST['idjoueur']) ){
		
		$idjoueur = $_POST['idjoueur'];

		$flottes = $bdd->prepare("SELECT * FROM multi__fleets WHERE `fleet_owner` = '". $idjoueur ."' OR `fleet_target_owner` = '".$idjoueur."';");
		$flottes->execute();
		
		if ($flottes->rowCount() > 0){

			$reponse["message"] = "";

			while($laFlotte = $flottes->fetch(PDO::FETCH_ASSOC)){

				if($laFlotte['fleet_owner'] == $idjoueur){
					$owner = 1;
				} else{
					$owner = 2;
				}
// laFlotte['']
				$dureeAller = $laFlotte['fleet_end_time'] - $laFlotte['start_time'];
				$faitAller = time() - $laFlotte['start_time'];
				$pourcentAller = 
				
				// Position du vsx selon la durée
				//if ($Status == 0) { // Aller
					$dureeA	= $laFlotte['fleet_start_time'] - $laFlotte['start_time'];
					$RestA	= $laFlotte['fleet_start_time'] - time();

				/*} elseif ($Status == 1) { // Stationnement
					$duree	= $FleetRow['fleet_end_time'] - $FleetRow['start_time'];
					$Time	= $FleetRow['fleet_end_stay'];
					$Rest	= $Time - time();

				} elseif ($Status == 2) { // Retour
					$duree	= $FleetRow['fleet_end_time'] - $FleetRow['start_time'];
					$Time	= $FleetRow['fleet_end_time'];
					$Rest	= $Time - time();
				}*/

				$pourcentageA = 100 - (($RestA / $dureeA) * 100);
				
				if($pourcentageA < 0){
					$pourcentageA = 0;
				} elseif($pourcentageA > 100){
					$pourcentageA = 100;
				}


				$reponse["message"] .= 
					$owner."COUPDETAILDEVOME". // 1 - 1: Flotte appartient au joueur 2: appartient a un autre
					$laFlotte['fleet_id']."COUPDETAILDEVOME". // 2 - ID Flotte
					$laFlotte['type']."COUPDETAILDEVOME". // 3 - 1: Flotte 2: Porte
					$laFlotte['fleet_mission']."COUPDETAILDEVOME". // 4 - Mission
					$laFlotte['fleet_start_galaxy']."COUPDETAILDEVOME". // 5 - Galaxie depart
					$laFlotte['fleet_start_system']."COUPDETAILDEVOME". // 6 - SS depart
					$laFlotte['fleet_start_planet']."COUPDETAILDEVOME". // 7 - Position depart
					$laFlotte['fleet_start_type']."COUPDETAILDEVOME". // 8 - Type depart
					$laFlotte['fleet_end_galaxy']."COUPDETAILDEVOME". // 9 - Galaxie destination
					$laFlotte['fleet_end_system']."COUPDETAILDEVOME". // 10 - SS destination
					$laFlotte['fleet_end_planet']."COUPDETAILDEVOME". // 11 - Position destination
					$laFlotte['fleet_end_type']."COUPDETAILDEVOME". // 12 - Type destination
					date('d/m', $laFlotte['start_time'])." à ".date('H:i', $laFlotte['start_time'])."COUPDETAILDEVOME". // 13 - Depart
					date('d/m', $laFlotte['fleet_end_time'])." à ".date('H:i', $laFlotte['fleet_end_time'])."COUPDETAILDEVOME". // 14 - Fin
					date('d/m', $laFlotte['fleet_start_time'])." à ".date('H:i', $laFlotte['fleet_start_time'])."COUPDETAILDEVOME". // 15 -
					$laFlotte['occultation']."COUPDETAILDEVOME". // 16 - occultation
					$laFlotte['fleet_resource_metal']."COUPDETAILDEVOME". // 17 - metal
					$laFlotte['fleet_resource_gateone']."COUPDETAILDEVOME". // 18 - gate one
					$laFlotte['fleet_resource_naquadah']."COUPDETAILDEVOME". // 19 - naquadah
					$laFlotte['fleet_resource_homme']."COUPDETAILDEVOME". // 20 - homme
					intval($pourcentageA)."COUPDETAILDEVOME". // 21 - % arrive
					"0COUPDETAILDEVOME". // 22 - % mission
					$laFlotte['fleet_resource_homme']."COUPDETAILDEVOME". // 23 - % retour
					
				"COUPMESSAGEDEVOME";
			}

		} else {
			$reponse["message"] = "Aucune flotte";
		}
		
		
		echo json_encode($reponse);
	}

	$bdd = null;
?>