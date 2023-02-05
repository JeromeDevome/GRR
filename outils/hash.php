<?php


	if (isset($_POST['pwd']) && $_POST['pwd'] != "") {
		$mdpC = $_POST['pwd'];
		$hash = password_hash($_POST['pwd'], PASSWORD_DEFAULT);
	} else {
		$mdpC = "";
		$hash = "";
	}

?>

	<form method="post" action="hash.php">
		<table width="400">
			<tr>
				<th width="200">Mot de passe en clair :</th>
				<th width="200"><input type="text" name="pwd" value="<?php echo $mdpC; ?>"></th>
			</tr><tr>
				<th width="200">Mot de passe crypte :</th>
				<th width="200"><input type="text" name="hash" value="<?php echo $hash; ?>"></th>
			</tr><tr>
				<th width="200">&nbsp;</th>
				<th width="200"><input type="submit" name="ok" value="Crypter"></th>
			</tr>
		</table>
	</form>