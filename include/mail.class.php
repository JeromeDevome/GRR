<?php
/**
 * include/mail.class.php
 * fichier de définition d'une classe de traitement des e-mails
 * fait partie de l'application GRR
 * Dernière modification : $Date: 2018-02-23 18:00$
 * @author    JeromeB & Laurent Delineau & Marc-Henri PAMISEUX & NaldDev
 * @copyright Copyright 2003-2020 Team DEVOME - JeromeB
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

	public static function Envois ($A, $sujet, $message, $DE, $cc1='', $cc2='') {
		global $gNbMail, $gMaxMail;

		if($gNbMail < $gMaxMail || $gMaxMail == -1){

			mb_internal_encoding('utf-8');

			if (Settings::get('grr_mail_method') == 'smtp') {

				$smtp1				= Settings::get('grr_mail_smtp');
				$username			= Settings::get('grr_mail_Username');
				$password			= Settings::get('grr_mail_Password');
				$smtpsecure			= Settings::get('smtp_secure');
				$port				= Settings::get('smtp_port');
				$ass				= Settings::get('smtp_port');
				$verify_peer_name	= Settings::get('smtp_verify_peer_name');
				$verify_peer		= Settings::get('smtp_verify_peer');
				$verify_depth		= Settings::get('smtp_verify_depth');

				$mail = new PHPMailer\PHPMailer\PHPMailer;
				$mail->CharSet = 'UTF-8';
				$mail->SMTPDebug = 0;
				$mail->isSMTP();
				$mail->Host = $smtp1;
				$mail->Username = $username;
				$mail->Password = $password;
				$mail->SMTPSecure = $smtpsecure; // '', tls, ssl
				$mail->Port = $port;
				$mail->setFrom($DE, 'GRR');
				$mail->addReplyTo($DE, 'GRR');
				$mail->isHTML(true);
				$mail->Subject = $sujet;
				$mail->Body = nl2br($message);
				$mail->AltBody = 'Ce message ne peut-être affiché.';

				if ($username != '') {
					$mail->SMTPAuth = true;
				} else {
					$mail->SMTPAuth = false;
				}

				$lesDestinataires = explode(";", $A);
				for($i=0;$i<count($lesDestinataires);$i++){
					if (Settings::get("grr_mail_Bcc") == "y"){
						$mail->addCC($lesDestinataires[$i]);
					} else{
						$mail->AddAddress($lesDestinataires[$i]);
					}
				}
				
				$mail->SMTPOptions = array(
					'ssl' => [
						'verify_peer' => $verify_peer,
						'allow_self_signed' => $ass,
						'verify_depth' => $verify_depth,
						'verify_peer_name' => $verify_peer_name,
					],
				);

				if(!$mail->send()) {
					echo 'Message could not be sent.';
					echo 'Mailer Error: ' . $mail->ErrorInfo;
				}

			} elseif (Settings::get('grr_mail_method') == 'mail') {
				$headers = "From: {$DE}" . "\r\n" .
					"Reply-To: {$DE}" . "\r\n" .
					'X-Mailer: PHP/' . phpversion();

				mail(str_replace(";",",",$A), $sujet, utf8_decode(utf8_encode(str_replace("<br>","",$message))), $headers); //YN selon Rapace sur le forum
			}

			//Log mail
			if (Settings::get('grr_mail_method') != 'bloque') {
				$sql = "INSERT INTO ".TABLE_PREFIX."_log_mail ( date, de, a, sujet, message) values (
					'" . time() . "',
					'" . protect_data_sql($DE) . "',
					'" . protect_data_sql($A) . "',
					'" . protect_data_sql($sujet) . "',
					'" . protect_data_sql($message) . "'
					)
				;";
				grr_sql_query($sql);
			}

			$gNbMail++;
		}
	}

}
?>
