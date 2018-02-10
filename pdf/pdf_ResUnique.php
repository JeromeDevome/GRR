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

		
			<br/>

			<div class="row">
				<div class="col-md-8 col-md-offset-1">
					Bonjour,<br>
					Suite à votre demande du '.$date.' dernier,
					nous avons le plaisir de vous informer que la ressource <b>'.$salle.'</b> sera mise à votre disposition,
				</div>
			</div>
			<div class="row">
				<div class="col-md-4 col-md-offset-4">
					<br>
					<b> Du '.$jour.' à '.$heure.'</b>
					<br>
					<b> Au  '.$jour2.' à '.$heure2.'</b>
					<br>
					<br>
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