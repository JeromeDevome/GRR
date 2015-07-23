<?php
/*
 * Copyright (C) 2008  Entr'ouvert
 * Copyright (C) 2008  Cliss XXI
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

require_once('../include/config.inc.php');
require_once('../include/misc.inc.php');
require_once(SPKITLASSO.'/lassospkit_saml2_endpoint.inc.php');
require_once(SPKITLASSO.'/lassospkit_public_api.inc.php');

class EndpointExemple extends LassoSPKitSaml2Endpoint {
    function dispatchAndExit() {
        try {
            parent::dispatchAndExit();
        } catch (Exception $e) {
            $exceptionText = $e->__toString();
            $lines = explode("\n",$exceptionText);
            foreach ($lines as $line) {
                error_log($line);
            }
            die('There was an error, shoot yourself !');
        }
    }
    function headerHtml($title, $url) {
        ?>
        <html>
        <head>
        <title><?php echo $title ?></title>
        <meta http-equiv="refresh" content="5; url=<?php echo $url?>" />
        </head>
        <body> <?php
    }
    function footerHtml() {
        ?>
        </body>
        </html>
        <?php
    }
    function handleSso($ret) {
        $r = $this->relayState;
        //$this->headerHtml("AssertionConsumer endpoint", $r);
        if ($ret == 0) {
            if (isset($r))
	      header("Location: $r");
	    else
	      header("Location: ../.."); // 1 for "lasso/", 1 for "saml2.php_/_assertionConsumer"
            echo "Ouais on est loggé, aller <a href='$r'>$r</a>";
	    exit();
        } else {
            echo "Une erreur lasso s'est produite de code $ret" . strError($ret);
        }
        return $ret;
    }
    function handleSlo($ret) {
        //error_log("handleSlo");
        $r = $this->relayState;
        if ($this->currentHttpMethod == LASSO_HTTP_METHOD_GET &&
            $this->currentHttpMethod == LASSO_HTTP_METHOD_POST) {
            $this->headerHtml("SLO endpoint", $r);
            if ($ret) {
                echo "Demande de slo échoué: " . strError($ret) . "($ret)";
            } else {
                echo "Demande de slo réussie";
            }
            echo "Go to <a href='$r'>$r</a>";
            $this->footerHtml();
            lassospkit_clean();
	    grr_closeSession($_GET['auto']);
        } else {
            # Specialized
            $id = lassospkit_userid();

	    if (isset($id)) {
	      //error_log("Trying to destroy session $id");

	      chdir("..");
	      global $dbsys;
	      require_once("./include/config.inc.php");
	      include "./include/connect.inc.php";
	      require_once("./include/$dbsys.inc.php");
	      require_once("./include/functions.inc.php");
	      require_once("./include/session.inc.php");

	      // See admin_view_connexions.php:67
	      $sql = "SELECT session_id FROM ".TABLE_PREFIX."_log
                      WHERE login = '" . protect_data_sql($id) . "'
                      AND end > NOW()";
	      $res = grr_sql_query($sql);
	      if ($res) {
		for ($i = 0; ($row = grr_sql_row($res, $i)); $i++)
		  {
		    $php_session_id = $row[0];
		    //error_log("Erasing GRR session $php_session_id");
		    session_id($php_session_id);
		    // delete spkitlasso session if necessary
		    @session_start();
		    lassospkit_set_nameid(@$_SESSION['lasso_nameid']);
		    lassospkit_clean();
		    // delete GRR session
		    $auto = 0;
		    grr_closeSession($auto);
		    // Done by grr_closeSession:
		    //session_start();
		    //session_destroy();
		  }
	      }
	    }
        }
        if ($ret) {
            error_log("Demande de slo échoué: $ret");
        } else {
	    //error_log("Demande de slo réussie: $ret");
        }

        return $ret;
    }
    function handleNameIdManagement($ret) {
        if ($ret) {
            error_log('Erreur lors du nid: ' . $ret . ': ' . strError($ret));
        }
        return $ret;
    }
}


$endpoint = new EndpointExemple();
$endpoint->dispatchAndExit();
