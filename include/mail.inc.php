<?php
/**
 * mail.inc.php
 * Configuration de phpmailer
 * Ce script fait partie de l'application GRR
 * Dernière modification : $Date: 2008-11-16 22:00:59 $
 * @author    Laurent Delineau <laurent.delineau@ac-poitiers.fr>
 * @copyright Copyright 2003-2008 Laurent Delineau
 * @link      http://www.gnu.org/licenses/licenses.html
 * @package   root
 * @version   $Id: mail.inc.php,v 1.2 2008-11-16 22:00:59 grr Exp $
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
/**
 * $Log: mail.inc.php,v $
 * Revision 1.2  2008-11-16 22:00:59  grr
 * *** empty log message ***
 *
 *
 */

    error_reporting (E_ALL ^ E_NOTICE ^ E_WARNING);
    // Appel de la classe phpmailer
    require "./phpmailer/class.phpmailer.php";

    define("GRR_FROM",Settings::get("grr_mail_from"));
    define("GRR_FROMNAME",Settings::get("grr_mail_fromname"));
    class my_phpmailer extends phpmailer {
        var $From = GRR_FROM;
        var $FromName = GRR_FROMNAME;
        var $Port = 25;
        var $Priority = 3;
        var $Encoding = "8bit";
        var $CharSet = "utf-8";
        var $checkAddress = false;
        var $IsHTML= false;
        var $WordWrap = 75;
        function my_phpmailer() {
            if (Settings::get("grr_mail_method")  == "smtp") {
                $this->Host = Settings::get("grr_mail_smtp");
                $this->isSMTP();
                if (Settings::get("grr_mail_Username")!="") {
                    $this->SMTPAuth  = true;
                    $this->Username = Settings::get("grr_mail_Username");
                    $this->Password = Settings::get("grr_mail_Password");
                } else {
                    $this->SMTPAuth  = false;
                }
            }
        }
    }
?>
