<?php
 include("aconfig.php");

	$reponse["erreur"] = false;
	//$reponse["message"] = "";

	// Valeur Debug;
	$_POST['tokenuser'] = 1;

	if( !empty($_POST['tokenuser']) ){

		//$login = $bdd->query("SELECT `login` FROM {$prefixe}_utilisateurs WHERE `tokenuser` = '".$_POST['tokenuser']."' ;")->fetch(PDO::FETCH_ASSOC);
		$login = $bdd->query("SELECT `login` FROM {$table_prefix}_utilisateurs WHERE `prenom` = 'grr2' ;")->fetch(PDO::FETCH_ASSOC);

		$lesResa = $bdd->query("SELECT * FROM {$table_prefix}_entry WHERE `beneficiaire` = '".$login['login']."' ;");

		while($resa = $lesResa->fetch(PDO::FETCH_ASSOC)){
			
			//$reponse["message"] .= $planete['idplanetes']."COUPDETAILDEVOME".$planete['nom']."COUPDETAILDEVOME".$coordonnees."COUPDETAILDEVOME".$planete['image']."COUPDETAILDEVOME".$planete['type']."COUPDETAILDEVOME".$planete['derniere_maj']."COUPDETAILDEVOME"."COUPMESSAGEDEVOME";

			$reponse[] = ['timestamp' => $resa['timestamp'], 'name' => $resa['name']];
		}


		echo json_encode($reponse);
	}

	$bdd = null;
?>