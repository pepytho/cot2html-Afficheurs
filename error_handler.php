<?php
/**
 * Gestionnaire d'erreurs personnalisé pour faciliter le débogage
 */

// Définir le mode de débogage
$debug_mode = false; // Mettre à true pour activer le débogage

// Configurer la journalisation des erreurs
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

// Fonction de gestion des erreurs
function custom_error_handler($errno, $errstr, $errfile, $errline) {
    global $debug_mode;
    
    // Préparer le message d'erreur
    $error_message = date('[Y-m-d H:i:s]') . " - Erreur ($errno): $errstr dans $errfile à la ligne $errline";
    
    // Enregistrer dans le journal
    error_log($error_message);
    
    // En mode débogage, afficher l'erreur
    if ($debug_mode) {
        echo "<div style='background-color: #f8d7da; color: #721c24; padding: 10px; margin: 10px; border: 1px solid #f5c6cb;'>";
        echo "<h3>Erreur détectée :</h3>";
        echo "<p><strong>Type :</strong> $errno</p>";
        echo "<p><strong>Message :</strong> $errstr</p>";
        echo "<p><strong>Fichier :</strong> $errfile</p>";
        echo "<p><strong>Ligne :</strong> $errline</p>";
        echo "</div>";
    }
    
    // Ne pas exécuter le gestionnaire d'erreurs interne de PHP
    return true;
}

// Définir le gestionnaire d'erreurs
if ($debug_mode) {
    // Afficher toutes les erreurs en mode débogage
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    // Masquer les erreurs en production mais les journaliser
    ini_set('display_errors', 0);
    error_reporting(E_ALL);
}

// Enregistrer le gestionnaire d'erreurs
set_error_handler("custom_error_handler");

/**
 * Gestion des exceptions non capturées
 */
function exception_handler($exception) {
    global $debug_mode;
    
    // Journaliser l'exception
    error_log("Exception non attrapée: " . $exception->getMessage() . " dans " . $exception->getFile() . " ligne " . $exception->getLine());
    
    if ($debug_mode) {
        echo "<div style='background-color: #f8d7da; color: #721c24; padding: 10px; margin: 10px; border: 1px solid #f5c6cb;'>";
        echo "<h3>Exception non attrapée :</h3>";
        echo "<p><strong>Message :</strong> " . $exception->getMessage() . "</p>";
        echo "<p><strong>Fichier :</strong> " . $exception->getFile() . "</p>";
        echo "<p><strong>Ligne :</strong> " . $exception->getLine() . "</p>";
        echo "<p><strong>Trace :</strong><pre>" . $exception->getTraceAsString() . "</pre></p>";
        echo "</div>";
    } else {
        // Afficher une page d'erreur conviviale en production
        header("HTTP/1.1 500 Internal Server Error");
        echo '<!DOCTYPE html>
        <html>
        <head>
            <title>Erreur temporaire</title>
            <style>
                body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
                .error-container { max-width: 600px; margin: 0 auto; }
                h1 { color: #0A1E3F; }
                p { color: #333; }
                .back-button { display: inline-block; margin: 20px; padding: 10px 20px; 
                    background-color: #0A1E3F; color: white; text-decoration: none; border-radius: 4px; }
            </style>
        </head>
        <body>
            <div class="error-container">
                <h1>Oups ! Une erreur temporaire est survenue</h1>
                <p>Notre système a rencontré un problème lors du traitement de votre demande.</p>
                <p>Veuillez réessayer dans quelques instants.</p>
                <a class="back-button" href="javascript:history.back()">Retour</a>
            </div>
        </body>
        </html>';
    }
    
    exit;
}

// Définir le gestionnaire d'exceptions
set_exception_handler('exception_handler');
?>
