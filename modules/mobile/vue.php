<?php
 include("aconfig.php");

	$joueur = $bdd->prepare("SELECT * FROM `multi__joueurs` WHERE `idjoueur` = '".$_REQUEST['iduser']."' LIMIT 1;");
	$joueur->execute();

	if ($joueur->rowCount() == 1){

		$utilisateur = $joueur->fetch(PDO::FETCH_ASSOC);
		$reponse["reussi"]	= "1";
		$reponse["utilisateur"] = array();
		$reponse["utilisateur"] = $utilisateur['idjoueur'];

		$reponse["message"] = array();
		$reponse["message"] = $bdd->query("SELECT COUNT(*) FROM multi__messages WHERE `message_owner` = '".$utilisateur['idjoueur']."' AND `message_read` = '0';")->fetchColumn();

		$Radar = $bdd->prepare("SELECT * FROM `multi__fleets` WHERE `fleet_owner` != '".$utilisateur['idjoueur']."' AND `fleet_target_owner` = '".$utilisateur['idjoueur']."';");
		$Radar->execute();

		$attaque = 0;
		$espionage = 0;
		$transport = 0;
		$mouvement = 0;
		$msgAttaque = "";
		$msgEspionage = "";
		$msgTransport = "";
				
		while($radar = $Radar->fetch(PDO::FETCH_ASSOC)){

			if ($radar['fleet_mission'] == 1 || $radar['fleet_mission'] == 2 || $radar['fleet_mission'] == 9 || $radar['fleet_mission'] == 10){
				$attaque++;
			} elseif ($radar['fleet_mission'] == 6){
					$espionnage++;
			} elseif ($radar['fleet_mission'] == 3 || $radar['fleet_mission'] == 5){
					$transport++;
				}
			}
				
			if($attaque > 0){
				$msgAttaque = "Attention '".$attaque."' attaque(s) en cours sur vos planetes !";
				$mouvement++;
			}

			if($espionage > 0){
				$msgEspionage = "Nous détectons '".$espionage."' espionages actuellement sur vos planetes !";
				$mouvement++;
			}

			if($transport > 0){
				$msgTransport = " '".$attaque."' flotte alliez se dirige vers nous !";
				$mouvement++;
			}
			$reponse["flotte"] = array();
		if($mouvement > 0){
			$reponse["flotte"] = $msgAttaque." ".$msgEspionage." ".$msgTransport;
		} else{
			$reponse["flotte"] = "Aucun mouvement detecte.";
		}

	} else {
		$reponse["reussi"] = "0";
        $reponse["message"] = "Compte inconnu";
		$reponse["flotte"] = "";
	}

	//$reponse2[]=$reponse;
	//print(json_encode($reponse2));
	echo $reponse["reussi"].";".$reponse["message"].";".$reponse["flotte"].";";

	$bdd = null;
?>