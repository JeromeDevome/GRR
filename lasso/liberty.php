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
require_once(SPKITLASSO.'/lassospkit_helper.inc.php');
require_once(SPKITLASSO.'/lassospkit_dispatcher.inc.php');
require_once(SPKITLASSO.'/lassospkit_metadata.inc.php');
require_once(SPKITLASSO.'/lassospkit_utils.inc.php');

dispatch(array('/login' => login,
               '/federate' => federate,
               '/ssoAssertionConsumer' => ssoAssertionConsumer,
               '/sloInit' => sloInit,
               '/sloSoap' => sloSoap,
               '/sloRedirect' => sloRedirect,
               '/sloResponse' => sloResponse,
               '/defederate' => defederate,
               '/defederateReturn' => defederateReturn, 
               '/defederateNotification' => defederateNotification,
               '/metadata' => metadata));
// TODO fill implementation
function login() {
    $saml2 = new LassoSPKitLiberty(new LassoSPKitDummySession());
    $params = LassoSPKitUtilsSession::getParams('login');
    $federate = TRUE;
    if (isset($params['federate'])) {
        $federate = $params['federate'];
    }
    $saml2->sso(TRUE, $federate);
}
function federate() {
    $saml2 = new LassoSPKitLiberty(new LassoSPKitDummySession());
    $saml2->sso(TRUE, TRUE);
}
function ssoAssertionConsumer() {
}
function sloInit() {
}
function sloSoap() {
}
function sloRedirect() {
}
function sloResponse() {
}
function defederate() {
}
function defederateReturn() {
}
function defederateNotification() {
}
function metadata() {
    header('Content-type: text/xml');
    $datadir = LassoSPKitHelper::getMetadataDir(LASSO_PROTOCOL_LIBERTY_1_2);
    $pkey = $datadir . "/" . PRIVATE_KEY;
    LassoSPKitUtils::extractPublicKey($pkey, $publickey, $error);
    try { 
        $content = LassoSPKitMetadataLiberty::generateMetadata(dirname(LassoSPKitUtils::mydir()), LassoSPKitConfig::get('organization'), $publickey);
        if ($content) {
            header('Content-type: text/xml');
            echo $content;
        }
    } catch (Exception $e) {
        throw $e;
    }
}
