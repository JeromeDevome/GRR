<?php
include("aconfig.php");

$reponse["erreur"] = false;
$reponse["message"] = [];
  
if(isset($_POST)){

	if( !empty($_POST['email']) && !empty($_POST['password']) ){

		$email = $bdd->prepare("SELECT `id`, `email`, `password` FROM `site2_users` WHERE `email` = ? LIMIT 1;");
		$email->execute(array($_POST['email']));
		
		if ($email->rowCount() == 1){
			$email = $email->fetch(PDO::FETCH_ASSOC);

			$hashed_password = crypt($_POST['password']);
			if(hash_equals($email['password'], crypt($_POST['password'], $email['password']))){

				// Verrif joueur existant
				$leJoueur = $bdd->prepare("SELECT `idutilisateurs` FROM `utilisateurs` WHERE `iddevome` = ? LIMIT 1;");
				$leJoueur->execute(array($email['id']));

				if ($leJoueur->rowCount() == 1){ 
					$leJoueur = $leJoueur->fetch(PDO::FETCH_ASSOC);
					$reponse["message"] = $leJoueur['idutilisateurs'];
				} else{
					$reponse["erreur"] = true;
					$reponse["message"] = "Aucun compte sur SGL";
				}
			} else{
				$reponse["erreur"] = true;
				$reponse["message"] = "Erreur de mot de passe.";
			}
		} else {
			$reponse["erreur"] = true;
			$reponse["message"] = "Compte inconnu";
		}

	} else{
		$reponse["erreur"] = true;
		$reponse["message"] = "Veuillez remplir tous les champs.";
	}

	echo json_encode($reponse);

}

$bdd = null;
?>