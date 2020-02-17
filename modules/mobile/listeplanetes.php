<?php
 include("aconfig.php");

	$reponse["erreur"] = false;
	$reponse["message"] = "";

	// Valeur Debug;
	//$_POST['idjoueur'] = 1;

	if( !empty($_POST['idjoueur']) ){
		
		$idjoueur = $_POST['idjoueur'];

		$joueurInfos = $bdd->prepare("SELECT `alliance`, `planetecourante`, `planete_ordre_type`, `planete_ordre` FROM multi__joueurs WHERE `idjoueur` = ? ;");
		$joueurInfos->execute(array($idjoueur));
		$joueurInfos = $joueurInfos->fetch(PDO::FETCH_ASSOC);
		

		$Order	= ( $joueurInfos['planete_ordre_type'] == 1 ) ? " `type`, " : "" ;
		$Sort	= $joueurInfos['planete_ordre'];

		$QryPlanets  = "SELECT `idplanetes`, `nom`, `galaxie`, `systeme`, `planete`, `image`, `type`, `derniere_maj` FROM multi__planetes WHERE `idjoueur` = ? AND `deplacement` = 'non' or `ally_id` = ? AND `ally_id` != '0' ORDER BY ";
		if       ( $Sort == 0 ) {
			$QryPlanets .= $Order."`idplanetes`";
		} elseif ( $Sort == 1 ) {
			$QryPlanets .= $Order."`galaxie`, `systeme`, `planete`";
		} elseif ( $Sort == 2 ) {
			$QryPlanets .= $Order."`nom`";
		}

		$Planets = $bdd->prepare($QryPlanets);
		$Planets->execute(array($idjoueur,$joueurInfos['alliance']));


		while($planete = $Planets->fetch(PDO::FETCH_ASSOC)){
			
			$coordonnees = "[".$planete['galaxie'].":".$planete['systeme'].":".$planete['planete']."]";

			$reponse["message"] .= $planete['idplanetes']."COUPDETAILDEVOME".$planete['nom']."COUPDETAILDEVOME".$coordonnees."COUPDETAILDEVOME".$planete['image']."COUPDETAILDEVOME".$planete['type']."COUPDETAILDEVOME".$planete['derniere_maj']."COUPDETAILDEVOME"."COUPMESSAGEDEVOME";

		}


		echo json_encode($reponse);
	}

	$bdd = null;
?>