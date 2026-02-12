<?php
/**
 * erreur.php
 * Page de gestion des erreurs HTTP 404, 500, etc.
 * Ce script fait partie de l'application GRR
 */

// Récupérer le code d'erreur
$error_code = 500;
$error_title = "Erreur serveur";
$error_message = "Une erreur est survenue sur le serveur.";

// Parser l'URL pour déterminer le code d'erreur s'il est défini
if (isset($_SERVER['REDIRECT_STATUS']) && is_numeric($_SERVER['REDIRECT_STATUS'])) {
    $error_code = (int)$_SERVER['REDIRECT_STATUS'];
}

// Déterminer le message selon le code d'erreur
switch ($error_code) {
    case 400:
        $error_title = "Mauvaise requête";
        $error_message = "La requête envoyée n'est pas valide. Vérifiez les paramètres.";
        break;
    case 401:
        $error_title = "Authentification requise";
        $error_message = "Vous devez vous authentifier pour accéder à cette ressource.";
        break;
    case 403:
        $error_title = "Accès refusé";
        $error_message = "Vous n'avez pas les permissions pour accéder à cette ressource.";
        break;
    case 404:
        $error_title = "Page non trouvée";
        $error_message = "Désolé, la page que vous recherchez n'existe pas ou a été supprimée.";
        break;
    case 405:
        $error_title = "Méthode non autorisée";
        $error_message = "La méthode HTTP utilisée n'est pas autorisée pour cette ressource.";
        break;
    case 408:
        $error_title = "Délai d'attente écoulé";
        $error_message = "La requête a pris trop de temps. Veuillez réessayer.";
        break;
    case 410:
        $error_title = "Ressource supprimée";
        $error_message = "Cette ressource n'existe plus et ne sera pas restaurée.";
        break;
    case 500:
        $error_title = "Erreur serveur interne";
        $error_message = "Oups ! Une erreur est survenue sur le serveur.";
        break;
    case 501:
        $error_title = "Non implémenté";
        $error_message = "Cette fonctionnalité n'est pas encore implémentée sur ce serveur.";
        break;
    case 502:
        $error_title = "Mauvaise passerelle";
        $error_message = "Le serveur a reçu une réponse invalide.";
        break;
    case 503:
        $error_title = "Service indisponible";
        $error_message = "Le serveur est temporairement indisponible.";
        break;
    case 504:
        $error_title = "Dépassement du délai de la passerelle";
        $error_message = "La passerelle n'a pas reçu de réponse à temps.";
        break;
    default:
        $error_title = "Erreur HTTP " . $error_code;
        $error_message = "Une erreur HTTP est survenue.";
}

// Définir le code HTTP de réponse
http_response_code($error_code);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GRR - Erreur <?php echo $error_code; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        .error-container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            text-align: center;
            max-width: 600px;
            width: 100%;
        }
        
        .error-code {
            font-size: 100px;
            font-weight: bold;
            color: #667eea;
            margin: 0 0 10px 0;
            line-height: 1;
        }
        
        .error-title {
            font-size: 28px;
            color: #333;
            margin: 10px 0 20px 0;
        }
        
        .error-message {
            font-size: 16px;
            color: #666;
            margin: 20px 0;
            line-height: 1.6;
        }
        
        .error-details {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            text-align: left;
            font-size: 13px;
            color: #666;
            word-break: break-all;
            border-left: 4px solid #667eea;
        }
        
        .error-details strong {
            display: block;
            margin-bottom: 5px;
            color: #333;
        }
        
        .alert {
            background: #fff3cd;
            border: 1px solid #ffc107;
            color: #856404;
            padding: 12px;
            border-radius: 5px;
            margin: 20px 0;
            font-size: 14px;
        }
        
        .button-group {
            margin-top: 30px;
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 30px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s ease;
            cursor: pointer;
            border: none;
            font-size: 16px;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #764ba2;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: #e0e0e0;
            color: #333;
        }
        
        .btn-secondary:hover {
            background: #d0d0d0;
            transform: translateY(-2px);
        }
        
        .app-name {
            font-size: 12px;
            color: #999;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-code"><?php echo $error_code; ?></div>
        <h2 class="error-title"><?php echo htmlspecialchars($error_title); ?></h2>
        
        <p class="error-message">
            <?php echo htmlspecialchars($error_message); ?>
        </p>
        
        <?php if ($error_code >= 500): ?>
        <div class="alert">
            <strong>⚠️ Important :</strong> Si le problème persiste, veuillez contacter l'administrateur du système.
        </div>
        <?php endif; ?>
        
        <div class="error-details">
            <strong>Informations techniques :</strong>
            Date/Heure : <?php echo date('d/m/Y à H:i:s'); ?><br>
            URL demandée : <?php echo htmlspecialchars($_SERVER['REQUEST_URI'] ?? 'Non disponible'); ?>
        </div>
        
        <div class="button-group">
            <a href="./" class="btn btn-primary">← Retour à l'accueil</a>
            <button onclick="history.back()" class="btn btn-secondary">← Page précédente</button>
        </div>
        
        <div class="app-name">
            GRR - Gestion de Réservation de Ressources
        </div>
    </div>
</body>
</html>
