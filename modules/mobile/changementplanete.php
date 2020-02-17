<?php
 include("aconfig.php");

	$reponse["erreur"] = false;
	$reponse["message"] = "";

	// Valeur Debug;
	//$_POST['idjoueur'] = 1;
	//$_POST['idplanete'] = 1;

	if( !empty($_POST['idjoueur']) && !empty($_POST['idplanete'] ) ){
		
		$idjoueur = $_POST['idjoueur'];
		$idplanete = $_POST['idplanete'];

		$planete = $bdd->prepare("SELECT `idjoueur` FROM multi__planetes WHERE `idplanetes` = ?;");
		$planete->execute(array($idplanete));
		$VerrifId = $planete->fetch(PDO::FETCH_ASSOC);

		if($VerrifId['idjoueur'] == $idjoueur){
			$planetecourante = $bdd->prepare("UPDATE multi__joueurs SET `planetecourante` = ? WHERE `idjoueur` = ?;");
			$planetecourante->execute(array($idplanete, $idjoueur));
		}


		echo json_encode($reponse);
	}

	$bdd = null;
?>