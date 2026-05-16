<?php
/**
 * include/mail.class.php
 * fichier de définition d'une classe de traitement des e-mails
 * fait partie de l'application GRR
 * Dernière modification : $Date: 2024-06-10 18:01$
 * @author    JeromeB & Laurent Delineau & Marc-Henri PAMISEUX & NaldDev
 * @copyright Since 2003 Team DEVOME - JeromeB
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

	public static function Envois ($A, $sujet, $message, $DE, $cc1='', $cc2='', $repondre='', $template='', $id_entry = null, $type_destinataire = null) {
		global $gNbMail, $gMaxMail, $gMailExpediteur;

		if($gNbMail < $gMaxMail || $gMaxMail == -1){

			mb_internal_encoding('utf-8');
			$success = true;
			$error = '';

			// Définition $DE par paramètre fonction sinon settings webmaster_email
			if($DE == '' && !empty($webmaster_email))
				$DE = $webmaster_email;

		/**  Envois via serveur SMTP **/
			if (Settings::get('grr_mail_method') == 'smtp') {

				$smtp1				= Settings::get('grr_mail_smtp');
				$username			= Settings::get('grr_mail_Username');
				$password			= Settings::get('grr_mail_Password');
				$mailDe				= Settings::get('grr_mail_from');
				$smtpsecure			= Settings::get('smtp_secure');
				$port				= Settings::get('smtp_port');
				$ass				= Settings::get('smtp_allow_self_signed');
				$verify_peer_name	= Settings::get('smtp_verify_peer_name');
				$verify_peer		= Settings::get('smtp_verify_peer');
				$verify_depth		= Settings::get('smtp_verify_depth');
				$webmaster_email	= Settings::get("webmaster_email");

				

				if(!empty($mailDe))
					$DE = $mailDe;

				if($repondre == '')
					$repondre = $DE;

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
				$mail->addReplyTo($repondre, 'GRR');
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
						'verify_peer' => boolval($verify_peer),
						'allow_self_signed' => boolval($ass),
						'verify_depth' => intval($verify_depth),
						'verify_peer_name' => boolval($verify_peer_name),
					],
				);

				if(!$mail->send()) {
					$success = false;
					$error = $mail->ErrorInfo;
				}

		/**  Envois via méthode mail **/
			} elseif (Settings::get('grr_mail_method') == 'mail') {

				// Définition $DE, prioritaire sur configuration, sinon settings, sinon via paramètre fonction
				if($gMailExpediteur != ''){
					$DE = $gMailExpediteur;
				} else{
					$mail_serveur_from = Settings::get('mail_serveur_from');
					if(!empty($mail_serveur_from))
						$DE = $mail_serveur_from;
				}

				// Définition $repondre, via paramètre fonction, sinon $DE
				if($repondre == '')
					$repondre = $DE;

				// A
				$to = str_replace(";", ",", $A);

				// En-têtes
       			$headers  = 'MIME-Version: 1.0' . "\r\n";
      			$headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
				$headers .= "From: {$DE}" . "\r\n" .
					"Reply-To: {$repondre}" . "\r\n" .
					'X-Mailer: PHP/' . phpversion();

				if (!mail($to, $sujet, $message, $headers)) {
					$success = false;
					$error = error_get_last() ? error_get_last()['message'] : 'Erreur inconnue lors de l\'envoi du mail.';
				}
			}

			/** Log email **/
			if (Settings::get('grr_mail_method') != 'bloque') {
				$sql = "INSERT INTO ".TABLE_PREFIX."_log_mail ( date, de, a, sujet, message, template, idresa, type, erreur) values (
					'" . time() . "',
					'" . SecuChaine::ProtectDataSql($DE) . "',
					'" . SecuChaine::ProtectDataSql($A) . "',
					'" . SecuChaine::ProtectDataSql($sujet) . "',
					'" . SecuChaine::ProtectDataSql($message) . "',
					'" . SecuChaine::ProtectDataSql($template) . "',
					'" . SecuChaine::ProtectDataSql($id_entry) . "',
					'" . SecuChaine::ProtectDataSql($type_destinataire) . "',
					'" . SecuChaine::ProtectDataSql($error) . "'
					)
				;";
				grr_sql_query($sql);
			}

			$gNbMail++;
		}

		return array('success' => $success, 'error' => $error);

	}
}
?>