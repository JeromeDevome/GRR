<?php
/**
 * include/mail.class.php
 * fichier de définition d'une classe de traitement des e-mails
 * fait partie de l'application GRR
 * Dernière modification : $Date: 2021-04-10 19:05$
 * @author    JeromeB & Laurent Delineau & Marc-Henri PAMISEUX & Yan Naessens
 * @copyright Copyright 2003-2021 Team DEVOME - JeromeB
 * @link      http://www.gnu.org/licenses/licenses.html
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 */

class Email{

	public static function Envois ($A, $sujet, $message, $DE, $cc1='', $cc2='', $RE='') {
		
		mb_internal_encoding('utf-8');
		
		if (Settings::get('grr_mail_method') == 'smtp') {

			$smtp1		= Settings::get('grr_mail_smtp');
			$username	= Settings::get('grr_mail_Username');
			$password	= Settings::get('grr_mail_Password');
			$smtpsecure	= Settings::get('smtp_secure');
			$port		= Settings::get('smtp_port');

			//encodage du sujet pour affichage des accents 1/3, YN sur proposition de podz sur le forum
			$sujet = mb_encode_mimeheader($sujet);

			$mail = new PHPMailer;
			$mail->CharSet = 'UTF-8';

			$mail->SMTPDebug = 0;

			$mail->isSMTP();
			//$mail->SMTPOptions = array( 'ssl' => array( 'verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true ) );
			$mail->Host = $smtp1;
			$mail->Username = $username;
			$mail->Password = $password;
            if ($smtpsecure != '')
                $mail->SMTPSecure = $smtpsecure;	// Enable TLS encryption, `ssl` also accepted
            else {
                $mail->SMTPSecure = false;
                $mail->SMTPAutoTLS = false; // évite l'envoi en SMTPS par défaut
            }
			$mail->Port = $port;

			if (Settings::get('grr_mail_Username') != "") {
				$mail->SMTPAuth = true;
			} else {
				$mail->SMTPAuth = false;
			}

			$mail->setFrom($DE, 'GRR');

			$lesDestinataires = explode(";", $A);
			for($i=0;$i<count($lesDestinataires);$i++){
				if (Settings::get("grr_mail_Bcc") == "y"){
					$mail->addCC($lesDestinataires[$i]);
				} else{
					$mail->AddAddress($lesDestinataires[$i]);
				}
			}
            if ($RE != '')
            {
                $lesRepondreA = explode(";", $RE);
                for($i=0;$i<count($lesRepondreA);$i++){
                    $mail->addReplyTo($lesRepondreA[$i],'GRR');
                }
            }
			else
                $mail->addReplyTo($DE, 'GRR');

			$mail->isHTML(true);

			$mail->Subject = $sujet;
			$mail->Body = nl2br($message);
			$mail->AltBody = 'Ce message ne peut-être affiché.';

			if(!$mail->send()) 
			{
				echo 'Message could not be sent.';
				echo 'Mailer Error: ' . $mail->ErrorInfo;
			} 
			else {
				//echo 'Message has been sent';
			}
		} 
		else
		{	
            if ($RE == '') $RE = $DE;
			$sujet = mb_encode_mimeheader($sujet);
			$headers = "From: {$DE}" . "\r\n" .
			"Reply-To: {$RE}" . "\r\n" .
			//encodage du sujet pour affichage des accents 2/3
			'MIME-Version: 1.0'."\r\n".
			'Content-Type: text/plain; charset=utf-8'."\r\n" .
			'Content-Transfer-Encoding:8bit'."\r\n".
			'X-Mailer: PHP/' . phpversion()."\r\n";

            //encodage du sujet pour affichage des accents 3/3
			$lesDestinataires = explode(";", $A);
			for($i=0;$i<count($lesDestinataires);$i++){
				mail(str_replace(";", ",", $lesDestinataires[$i]), $sujet, str_replace("<br>", "", $message), $headers);
			} //YN selon Rapace et Boblegal sur le forum
		}
	}
}
?>