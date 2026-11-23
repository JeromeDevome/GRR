<?php

// vérifications de sécurité : page accessible si utilisateur connecté et usager
if ((SecuAccess::UserLevel(getUserName(),-1) < 2))
{
	http_response_code(403);
	showAccessDenied("");
	return;
}

/**
 * edit_entry_description_params.php
 * Récupère les paramètres de description pour une ressource
 */

// Récupération des paramètres
$area = isset($_GET['area']) ? intval($_GET['area']) : -1;

$description_breve = 0;
$description_complete = 0;

if ($area > 0) {
    // Récupérer les paramètres du domaine
    $sql = "SELECT description_breve, description_complete 
            FROM " . TABLE_PREFIX . "_area 
            WHERE id = " . $area;
    
    $res = grr_sql_query($sql);
    if ($res && grr_sql_count($res) > 0) {
        $row = grr_sql_row_keyed($res, 0);
        $description_breve = isset($row['description_breve']) ? intval($row['description_breve']) : 0;
        $description_complete = isset($row['description_complete']) ? intval($row['description_complete']) : 0;
    }
}

// Préparer la réponse
$response = array(
    'description_breve' => $description_breve,
    'description_complete' => $description_complete
);

// Headers
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Afficher le JSON
echo json_encode($response, JSON_FORCE_OBJECT | JSON_UNESCAPED_UNICODE);
