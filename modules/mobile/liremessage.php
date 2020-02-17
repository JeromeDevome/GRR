<?php
 include("aconfig.php");
 
 	$reponse["erreur"] = false;
	$reponse["message"] = [];

	// Valeur Debug;
//	$_POST['idjoueur'] = 1;
//	$_POST['idmessage'] = 1216115;

	if( !empty($_POST['idjoueur']) && !empty($_POST['idmessage']) ){
		
		$idjoueur = $_POST['idjoueur'];
		$idmessage = $_POST['idmessage'];

		$msg = $bdd->prepare("SELECT * FROM `multi__messages` WHERE `message_id` = ? AND `message_owner` = ? LIMIT 1;");
		$msg->execute(array($idmessage, $idjoueur));
		
		$majMsg = $bdd->prepare("UPDATE `multi__messages` SET `AccLecture` = '1' WHERE `message_id` = ? LIMIT 1;");
		$majMsg->execute(array($idmessage));

		if ($msg->rowCount() == 1){

			$message = $msg->fetch(PDO::FETCH_ASSOC);
			$msgtxt	= $message['message_text'];
			$auteur	= $message['message_from'];
			$date	= $message['message_time'];
			$sujet	= $message['message_subject'];

			$reponse["message"] = $auteur."COUPMESSAGEDEVOME".$sujet."COUPMESSAGEDEVOME".$msgtxt."COUPMESSAGEDEVOME"."Le ".date('d/m', $message['message_time'])." à ".date('H:i', $message['message_time'])."COUPMESSAGEDEVOME";

		} else{
			$reponse["erreur"] = true;
			$reponse["message"] = "Ce message n'existe pas !";
		}


		
		echo json_encode($reponse);
		$bdd = null;
	}
?>