<html>
<head>
<title>Script pour tester la fonction mail()</title>
<style type="text/css">
font,th,td,p { font-family: Verdana, Arial, Helvetica, sans-serif }
.bodyline	{ background-color: #FFFFFF; border: 1px #98AAB1 solid; }
.forumline	{ background-color: #FFFFFF; border: 2px #006699 solid; }
td.row1	{ background-color: #EFEFEF; }
th	{
	color: #FFA34F; font-size: 11px; font-weight : bold;
	background-color: #006699; height: 25px;
}
th.thHead { font-size: 12px; border-width: 1px 1px 0px 1px; }
.maintitle	{
	font-weight: bold; font-size: 22px; font-family: "Trebuchet MS",Verdana, Arial, Helvetica, sans-serif;
	text-decoration: none; line-height : 120%; color : #000000;
}
.gen { font-size : 12px; color : #000000; }
input,textarea, select {
	color : #000000;
	font: normal 11px Verdana, Arial, Helvetica, sans-serif;
	border-color : #000000;
}
input.post, textarea.post, select {
	background-color : #FFFFFF;
}
input { text-indent : 2px; }
</style>
</head>
<body bgcolor="#e5e5e5" link="#006699" text="#000000" vlink="#5493b4">
<table align="center" border="0" cellpadding="10" cellspacing="0" width="100%">
	<tbody>
		<tr>
			<td class="bodyline">
      			<table border="0" cellpadding="0" cellspacing="0" width="100%">
        			<tbody>
        				<tr>
          					<td align="center" valign="middle" width="100%"><span class="maintitle"><span class="maintitle">Script pour tester la fonction mail() du serveur</span></span></td>
        				</tr>
        			</tbody>
      			</table>
				<br />
				<form action="test_mail.php" method="post" target="_top">
					<table class="forumline" align="center" border="0" cellpadding="4" cellspacing="1" width="100%">
  						<tbody>
							<tr>
								<th class="thHead" height="25" nowrap>Si la fonction mail() n'est pas activ&eacute;e, une erreur appara&icirc;tra.<br /> Un email sera envoy&eacute; &agrave; l'adresse indiqu&eacute;e.</th>
  							</tr>
  							<tr>
								<td class="row1">
              						<table border="0" cellpadding="3" cellspacing="1" width="100%">
                						<tbody>
                							<tr>
                  								<td colspan="2" align="center"> </td>
                							</tr>
                							<tr>
                  								<td align="right" width="45%"><span class="gen">Adresse email:</span></td>
                  								<td><input type="email" name="destinataire"></td>
                							</tr>
                							<tr align="center">
                  								<td colspan="2"> <input type="submit" value="Envoyer l'email" name="submit" /></td>
                							</tr>
                						</tbody>
              						</table>
            					</td>
  							</tr>
						</tbody>
					</table>
				</form>
<?php
if (isset($_POST['destinataire']))
{
$destinataire = $_POST['destinataire'];
$destinataire = filter_var($destinataire,FILTER_VALIDATE_EMAIL);
if (!$destinataire){
    echo "L'adresse mail entrée est non valide !";
    die();
}
$sujet = "Test de la fonction mail() de PHP";
$message = "Félicitations, la fonction mail() de votre hebergeur fonctionne!";

$envoi = mail($destinataire, $sujet, $message);

	if ($envoi == true)
	{
	echo "<font color='green'><b>L'email a été envoyé avec succès.<br /><br />La fonction mail est bien activé.<br />Un email vous a été envoyé à l'adresse que vous avez indiqué.</b></font><br />";
	}
	else
	{
	echo "<font color='red'><b>La fonction mail() est d&eacute;sactiv&eacute;e!</b></font><br />";
	}
}

?>
				<br />
			</td>
		</tr>
	</tbody>
</table>
</body>
</html>