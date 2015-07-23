
<?php

echo '<!DOCTYPE html>
	<html lang="fr">
	<head>
		<meta charset="utf-8">
		<title></title>
		<link rel="stylesheet" type="text/css" href="./bootstrap/css/bootstrap.min.css">
		<link rel="stylesheet" type="text/css" href="./themes/default/css/pdf.css">
	</head>
	<body>
	<body>
		<div class="container">
			<br>
			<div class="row" id ="img">
			
				<div class="col-md-1">
					<img src="'.$logo.'" width="212" height="110" alt="logo">
				</div>
				<div class="col-md-4 col-md-offset-7">
					'.$etablisement.'
				</div>
			</div>
		
			<br>
			<div class="row">
				<div class="col-md-4 col-md-offset-8" id = "adresse">
						'.$civ.' '.$prenom.' '.$nom.'<br>
						'.$orga.'<br>
						'.$adresse.'<br>
						'.$cp.' '.$ville.'
				</div>
			</div>
			<br>
			<br>
			<br>
			<br>
			<br>
			
			<div class="row">
			<strong> Objet: Réservation de salle</strong>
			</div>

		
			<br>

			<div class="row">
				<div class="col-md-8 col-md-offset-1">
					'.$civ.',<br>
					Suite à votre demande du '.$date.' dernier,
					nous avons le plaisir de vous informer que la salle '.$salle.' sera mise à votre disposition chaques '.$jourPeriode.' de '.$heure.' à '.$heure2.' à compté du '.$jour.' jusqu\'au '.$finPeriode.'
				</div>
			</div>
			<div class="row">
				<div class="col-md-8 col-md-offset-1">
					Restant à votre disposition pour tous renseignements complémentaires,<br>
					Nous vous prions de croire, '.$civ.', à l\'assurance des nos sentiments les meilleurs.
				</div>
			</div>
			<br>
			<br>
			<br>
			<br>
			<br>
			<div class="row" id="signature">
				<div class="col-md-3 col-md-offset-7">
					Signature
				</div>
			</div>
		</div>
		<div style="height:400px"></div>
</body>
</html>';
?>
