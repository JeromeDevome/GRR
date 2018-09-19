<?php
/**
 * include/functions.inc.php
 * fichier Bibliothèque de fonctions de GRR
 * Dernière modification : $Date: 2010-04-07 15:38:14 $
 * @author		Laurent Delineau <laurent.delineau@ac-poitiers.fr>
 * @author		Marc-Henri PAMISEUX <marcori@users.sourceforge.net>
 * @copyright	Copyright 2003-2005 Laurent Delineau
 * @copyright	Copyright 2008 Marc-Henri PAMISEUX
 * @link		http://www.gnu.org/licenses/licenses.html
 * @package		include
 * @version		$Id: functions.inc.php,v 1.33 2010-04-07 15:38:14 grr Exp $
 * @filesource
 *
 * This file is part of GRR.
 *
 * GRR is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GRR is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GRR; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

class Email{

	public static function Envois ($A, $sujet, $message, $DE, $cc1='', $cc2='') {

		if (Settings::get('grr_mail_method') == 'smtp') {

			$smtp1		= Settings::get('grr_mail_smtp');
			$username	= Settings::get('grr_mail_Username');
			$password	= Settings::get('grr_mail_Password');
			$smtpsecure	= Settings::get('smtp_secure');
			$port		= Settings::get('smtp_port');

			$mail = new PHPMailer;
			$mail->CharSet = 'UTF-8';

			$mail->SMTPDebug = 0;

			$mail->isSMTP();
			//$mail->SMTPOptions = array( 'ssl' => array( 'verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true ) );
			$mail->Host = $smtp1;
			$mail->Username = $username;
			$mail->Password = $password;
			$mail->SMTPSecure = $smtpsecure;	// Enable TLS encryption, `ssl` also accepted
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

			$mail->addReplyTo($DE, 'GRR');


			$mail->isHTML(true);

			$mail->Subject = $sujet;
			$mail->Body = $message;
			$mail->AltBody = 'Ce message ne peut-être affiché.';

			if(!$mail->send()) {
				echo 'Message could not be sent.';
				echo 'Mailer Error: ' . $mail->ErrorInfo;
			} else {
				//echo 'Message has been sent';
			}

		} else{
			$headers = "From: {$DE}" . "\r\n" .
				"Reply-To: {$DE}" . "\r\n" .
				'X-Mailer: PHP/' . phpversion();

			//boip2402
			//mail($A, $sujet, utf8_decode(utf8_encode($message)), $headers);
            mail(str_replace(';', ',', $A), $sujet, $message, $headers);
		}

	}

}
?>
