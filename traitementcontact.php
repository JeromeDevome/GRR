<?php
/*-----MAJ Loïs THOMAS  --> Page de traitement du formulaire contact.php -----*/
include "include/connect.inc.php";
include "include/config.inc.php";
include "include/misc.inc.php";
include "include/$dbsys.inc.php";
include "include/mrbs_sql.inc.php"; include 'include/twigInit.php';
include "phpmailer/class.phpmailer.php";

$grr_script_name = "week_all.php";
// Settings
require_once("./include/settings.class.php");
if (!Settings::load())
	die("Erreur chargement settings");
$msg_erreur = "Erreur. Les champs suivants doivent être obligatoirement
remplis :<br/><br/>";
$msg_ok = "Votre demande a bien été prise en compte.";
$message = $msg_erreur;
define('MAIL_DESTINATAIRE','informatique@talmontsainthilaire.fr');
define('MAIL_SUJET','GRR : Réservation d\'une salle ');


//~ 
if (empty($_POST['nom']))
	$message .= "Votre nom";
if (empty($_POST['prenom']))
	$message .= "Votre prénom<br/>";
if (empty($_POST['email']))
	$message .= "Votre adresse email<br/>";
if (empty($_POST['subject']))
	$message .= "Le sujet de votre demande<br/>";
if (empty($_POST['area']))
	$message .= "Le domaine n'est pas rempli<br/>";
if (empty($_POST['room']))
	$message .= "Aucune salle de choisie<br/>";
if (empty($_POST['jours']))
	$message .= "Aucune jours choisi <br/>";
if (empty($_POST['mois']))
	$message .= "Aucune mois choisi <br/>";
if (empty($_POST['année']))
	$message .= "Aucune année choisie <br/>";
if (empty($_POST['duree']))
	$message .= "Aucune durée choisie <br/>";
foreach ($_POST as $index => $valeur)
	$index = stripslashes(trim($valeur));



$mail_entete  = "MIME-Version: 1.0\r\n";
$mail_entete .= "From: {$_POST['nom']} "
."<{$_POST['email']}>\r\n";
$mail_entete .= 'Reply-To: '.$_POST['email']."\r\n";
$mail_entete .= 'Content-Type: text/plain; charset="iso-8859-1"';
$mail_entete .= "\r\nContent-Transfer-Encoding: 8bit\r\n";
$mail_entete .= 'X-Mailer:PHP/' . phpversion()."\r\n";




$mail_corps  = "<html><head></head><body> Message de :" .$_POST['prenom']." " .$_POST['nom'] . "<br/>";
$mail_corps  .= "Email : ".$_POST['email']. "<br/>";
$mail_corps  .= "Téléphone : ".$_POST['telephone']. "<br/><br/>";
$mail_corps  .= "<b> Sujet de la réservation :".$_POST['sujet']. "</b><br/><br/>";

$id .= $_POST['area'] ;
$sql_areaName .= "SELECT area_name FROM ".TABLE_PREFIX."_area where id = \"$id\" ";
$res_areaName .= grr_sql_query1($sql_areaName);
$mail_corps  .= "Domaines : ".$res_areaName. "<br/> ";
$mail_corps  .= "Salle : ".$_POST['room']. "<br/><br/>";
$mail_corps  .= "Date  :".$_POST['start_day']."/".$_POST['start_month']."/".$_POST['start_year']. " <br/>";
$mail_corps  .= "Heure réservation  : ".$_POST['heure']. "h  ".$_POST['minutes']. "min<br/>";
$mail_corps  .= "Durée de la réservation : ".$_POST['duree']. " \n";






















$mail_corps  .= " h ".$_POST['dureemin']. " \n</body></html>";

$mail_destinataire = Settings::get("mail_destinataire");
$mail_method= Settings::get("grr_mail_method");

if($mail_method =='mail'){

	if(mail($mail_destinataire, 'Demande de réservation', $mail_corps,$mail_entete )){
	header('Location: week_all.php');
	}else{
	echo "le message n'a pas été envoyé et donc mail n'est pas installé";
	}		

}else{
	require 'phpmailer/PHPMailerAutoload.php';
	define("GRR_FROM",Settings::get("grr_mail_from"));
	define("GRR_FROMNAME",Settings::get("grr_mail_fromname"));
	$mail = new PHPMailer();
	$mail->isSMTP();
	$mail->SMTPDebug = 0;
	$mail->Debugoutput = 'html';
	$mail->Host = Settings::get("grr_mail_smtp");
	$mail->Port = 25;
	$mail->SMTPAuth = false;
	$mail->CharSet = 'UTF-8';
	$mail->setFrom(GRR_FROM, GRR_FROMNAME);
	$mail->SetLanguage("fr", "./phpmailer/language/");
	setlocale(LC_ALL, $locale);

	$sujet ="Réservation d'une salle";
	$mail->AddAddress($mail_destinataire);
	$mail->Subject = $sujet;
	$mail->MsgHTML($mail_corps);
	$mail->AddReplyTo( $email_reponse );
	if (!$mail->Send())
		{
			$message_erreur .= $mail->ErrorInfo;
			echo $message_erreur;
		}
	else
	header('Location: week_all.php');
	}
?>
