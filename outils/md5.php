<?php


	if (isset($_POST['md5e']) && $_POST['md5e'] != "") {
		$mdpC = $_POST['md5e'];
		$mdpE = md5($_POST['md5e']);
	} else {
		$mdpC = "";
		$mdpE = "";
	}

?>

	<form method="post" action="md5.php">
		<table width="305">
			<tr>
				<th width="130">Mot de passe en clair :</th>
				<th width="171"><input type="text" name="md5e" value="<?php echo $mdpC; ?>"></th>
			</tr><tr>
				<th width="130">Mot de passe crypte :</th>
				<th width="171"><input type="text" name="md5" value="<?php echo $mdpE; ?>"></th>
			</tr><tr>
				<th width="130">&nbsp;</th>
				<th width="171"><input type="submit" name="ok" value="Crypter"></th>
			</tr>
		</table>
	</form>