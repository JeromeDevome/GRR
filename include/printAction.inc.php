<?php
/**
 * Extrait de trailer, gérer les données pour l'affichage de bouton imprimer
 * @author: Bouteillier Nicolas
 */

if ((!isset($_GET['pview']) || ($_GET['pview'] != 1)) && (isset($affiche_pview))) {
    $tplArrayTrailer['affichePrintableViewNonGet'] = true;
    $query = queryStringSanitize($_SERVER['QUERY_STRING']);

    if (Settings::get("pview_new_windows") == 1) {
        $tplArrayTrailer['pviewNewWindows'] = true;

        $tplArrayTrailer['linkToScript'] = traite_grr_url($grr_script_name);
        if (isset($_SERVER['QUERY_STRING']) && ($_SERVER['QUERY_STRING'] != '')) {
            $tplArrayTrailer['linkToScript'] .= '?' . $query . '&';
        }
        $tplArrayTrailer['linkToScript'] .= 'pview=1';
    } else {
        $tplArrayTrailer['pviewNewWindows'] = false;

        $tplArrayTrailer['linkToScript'] = traite_grr_url($grr_script_name);
        if (isset($_SERVER['QUERY_STRING']) && ($_SERVER['QUERY_STRING'] != '')) {
            $tplArrayTrailer['linkToScript'] .= '?' . $query . '&';
        }
        $tplArrayTrailer['linkToScript'] .= 'pview=1&precedent=1';
    }

} else {
    $tplArrayTrailer['affichePrintableViewNonGet'] = false;
}