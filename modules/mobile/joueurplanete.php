<?php
 include("aconfig.php");

	$reponse["erreur"] = false;
	$reponse["message"] = [];

	// Valeur Debug;
	$_POST['idjoueur'] = 1;

	if( !empty($_POST['idjoueur']) ){
		
		$idjoueur = $_POST['idjoueur'];

		$JoueurEtPlanete = $bdd->prepare("SELECT * FROM `multi__joueurs` INNER JOIN `multi__planetes` ON `multi__planetes`.`idplanetes` = `multi__joueurs`.`planetecourante` WHERE `multi__joueurs`.`idjoueur` = ? ;");
		$JoueurEtPlanete->execute(array($idjoueur));
		
		if ($JoueurEtPlanete->rowCount() > 0){

			$JoueurEtPlanete = $JoueurEtPlanete->fetch(PDO::FETCH_ASSOC);

			$reponse["message"] .= $JoueurEtPlanete['pseudo']."COUPDETAILDEVOME".$JoueurEtPlanete['metal']."COUPDETAILDEVOME".$JoueurEtPlanete['gateone']."COUPDETAILDEVOME".$JoueurEtPlanete['naquadah']."COUPDETAILDEVOME".$JoueurEtPlanete['homme']."COUPDETAILDEVOME".$JoueurEtPlanete['nom']."COUPDETAILDEVOME".$JoueurEtPlanete['image']."COUPDETAILDEVOME";


		} else {
			$reponse["erreur"] = true;
			$reponse["message"] = "Planète non trouvée.";
		}

		
		echo json_encode($reponse);
	}

	$bdd = null;
?>