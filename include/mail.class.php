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

use Kigkonsult\Icalcreator\Vcalendar;
class Email{

	public static function Envois ($A, $sujet, $message, $DE, $cc1='', $cc2='', $repondre='', $template='', $id_entry = null, $type_destinataire = null, $mail_invite = 0, $resa_info = array()){
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
				$ical = null;

				// DEBUG: A RETIRER APRES /!\ **************************************************************
				// ini_set('display_errors', 1);
				// error_reporting(E_ALL);
				// *****************************************************************************************

				// ICS: Ajout résa OK, Modif OK, Suppr OK, reste a ajouter la configuration pour activer ou non l'envoi d'invitation ICS en fonction du domaine / user
				if($mail_invite == 1 && !empty($resa_info)){ // Si l'envoi d'invitation via ICS est activé et que les infos de résas sont ok on prep un ics
					// Préparation d'un GUID
					$hash = strtolower(hash('ripemd128', $resa_info['id_entry'])); # GUID retrouvable avec l'id de la résa (Pour modifier la résa notamment)
    				$guid = substr($hash,  0,  8).'-'.substr($hash,  8,  4).'-'.substr($hash, 12,  4).'-'.substr($hash, 16,  4).'-'.substr($hash, 20, 12);
					// Préparation du vcalendar
					$ical = new Vcalendar();
					$ical->setUid($guid);
					$ical->setXprop('X-WR-CALNAME', 'Réservation GRR');
					$ical->setXprop('X-WR-CALDESC', $resa_info['nom_resa']);
					$ical->setXprop('X-WR-TIMEZONE', date_default_timezone_get());
					if ($resa_info['action'] != 3 && $resa_info['action'] != 4) {
						$ical->setMethod('REQUEST');
					} else { 
						$ical->setMethod('CANCEL');
					}

					// Préparation du vevent
					$event = $ical->newVevent();
					$event->setDtstart(new \DateTime('@'.$resa_info['start_time'], new \DateTimeZone(date_default_timezone_get())));
					$event->setDtend(new \DateTime('@'.$resa_info['end_time'], new \DateTimeZone(date_default_timezone_get())));
					$event->setSummary($resa_info['nom_resa']);
					$event->setLocation($resa_info['location']);
					$event->setDescription($resa_info['nom_resa']);
					$event->setAttendee($A);
					$event->setUid($guid.'-event');
					// Si périodicité
					error_log(json_encode($resa_info)); // placeholder pour voir y'a quoi dans cette valeur le temps de finir ça


					// Préparation de la valarm
					$alarm = $event->newValarm();
					$alarm->setAction(Vcalendar::DISPLAY);
					$alarm->setDescription($event->getDescription());
					$alarm->setTrigger('-PT15M'); // 15 minutes avant l'événement
					$alarm->setUid($guid.'-alarm');

					$icalstr = $ical->vtimezonePopulate()->createCalendar();
					$mail->Ical = $icalstr;
				} // Fin de prep de l'ICS
				
				if ($username != '') {
					$mail->SMTPAuth = true;
				} else {
					$mail->SMTPAuth = false;
				}

				$lesDestinataires = explode(";", $A);
				for($i=0;$i<count($lesDestinataires);$i++){
					if (Settings::get("grr_mail_Bcc") == 1){
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