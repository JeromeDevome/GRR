<?php
 include("aconfig.php");

	$reponse["erreur"] = false;
	$reponse["message"] = [];

	// Valeur Debug;
	//$_POST['idjoueur'] = 1;

	if( !empty($_POST['idjoueur']) ){
		
		$idjoueur = $_POST['idjoueur'];

		$msg = $bdd->prepare("SELECT * FROM `multi__messages` WHERE `message_owner` = '".$idjoueur."' ORDER BY `message_time` DESC;");
		$msg->execute();
		
		if ($msg->rowCount() > 0){

		//	$msg = $bdd->prepare("SELECT `pseudo` FROM `multi__joueurs` WHERE `message_owner` = '".$_REQUEST['iduser']."' AND `message_read` = '0';");
		//	$msg->execute();

			$reponse["message"] = "";
			//$i = 0;

			while($message = $msg->fetch(PDO::FETCH_ASSOC)){

				//$reponse = $message;
				//if($i == 0){
					$reponse["message"] .= $message['message_id']."COUPDETAILDEVOME".$message['message_from'].": ".$message['message_subject']."COUPDETAILDEVOME".date('d/m', $message['message_time'])." à ".date('H:i', $message['message_time'])."COUPDETAILDEVOME".$message['AccLecture']."COUPDETAILDEVOME"."COUPMESSAGEDEVOME";
			///	} else{
			//		$reponse["message"] .= $message['message_subject'];
			//	}
			//	$i++;
			}

			//$reponse4 = "{ \"messages\": [".$reponse3."] }";

		} else {
			$reponse["message"] = "Aucun message";

			//$reponse2[] = $reponse;
			//$reponse4 = json_encode($reponse2);
		}

		//echo $reponse["reussi"]."|#|".$reponse["message"]."|#|";
		//print($reponse4);
		
		
		echo json_encode($reponse);
	}

	$bdd = null;
?>