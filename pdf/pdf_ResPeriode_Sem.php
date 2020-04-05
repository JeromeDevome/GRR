<?php

echo '<!DOCTYPE html>
	<html lang="fr">
	<head>
		<meta charset="UTF-8">
		<title></title>
		<link rel="stylesheet" type="text/css" href="./bootstrap/css/bootstrap.min.css">
		<link rel="stylesheet" type="text/css" href="./themes/default/css/pdf.css">
	</head>
	<body>
	<body>
		<div class="container">
			<br/>
			<div class="row">
				<div class="col-md-12" id="img">
					<img src="'.$logo.'" height="110" alt="logo"><br>
					'.$etablisement.'
				</div>
			</div>
			<div class="row">
				<div class="col-md-4 col-md-offset-8" id="adressedestinataire">
						'.$nom.'<br>
						'.$adresse.'<br>
						'.$adresse2.'<br/>
						'.$cp.' '.$ville.'
				</div>
			</div>
			<br/>
			<br/>
			<br/>
			<br/>
			<br/>
			
			<div class="row">
			<strong> Objet: Confirmation de réservation</strong>
			</div>

		
			<br>

			<div class="row">
				<div class="col-md-8 col-md-offset-1">
					Bonjour,<br>
					Suite à votre demande du '.$date.' dernier,
					nous avons le plaisir de vous informer que la salle '.$salle.' sera mise à votre disposition chaque '.$jourPeriode.' de '.$heure.' à '.$heure2.' à compter du '.$jour.' jusqu\'au '.$finPeriode.'
				</div>
			</div>
			<div class="row">
				<div class="col-md-8 col-md-offset-1">
					Restant à votre disposition pour tous renseignements complémentaires.
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