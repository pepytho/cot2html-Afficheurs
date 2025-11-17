<?php
/**
 * Configuration Example for BellePoule TV Display
 * 
 * Copiez ce fichier en config.php et modifiez les valeurs selon vos besoins
 * Copy this file to config.php and modify the values according to your needs
 */

// ============================================================================
// CHEMINS / PATHS
// ============================================================================

// Chemin vers les données BellePoule (fichiers XML)
// Path to BellePoule data (XML files)
define('BELLEPOULE_PATH', '/chemin/vers/bellepoule/data/');

// Chemin vers les drapeaux (optionnel)
// Path to flags (optional)
define('FLAGS_PATH', __DIR__ . '/images/flags/');

// ============================================================================
// CONFIGURATION GÉNÉRALE / GENERAL CONFIGURATION
// ============================================================================

// Intervalle de rafraîchissement automatique (en millisecondes)
// Auto-refresh interval (in milliseconds)
// 30000 = 30 secondes / 30 seconds
define('AUTO_REFRESH_INTERVAL', 30000);

// Zoom par défaut
// Default zoom level
define('DEFAULT_ZOOM', 1.0);

// Vitesse de scroll par défaut (0.2 à 2.4)
// Default scroll speed (0.2 to 2.4)
define('DEFAULT_SCROLL_SPEED', 1.0);

// Activer le scroll automatique par défaut
// Enable auto-scroll by default
define('AUTO_SCROLL_ENABLED', true);

// ============================================================================
// AFFICHAGE / DISPLAY
// ============================================================================

// Nombre maximum de caractères pour les noms de club
// Maximum characters for club names
define('MAX_CLUB_NAME_LENGTH', 10);

// Afficher les drapeaux de nationalité
// Show nationality flags
define('SHOW_FLAGS', true);

// Afficher les clubs
// Show clubs
define('SHOW_CLUBS', true);

// Thème (dark ou light)
// Theme (dark or light)
define('THEME', 'dark');

// ============================================================================
// TABLEAUX ÉLIMINATOIRES / ELIMINATION BRACKETS
// ============================================================================

// Taille de tableau par défaut (début)
// Default bracket size (start)
define('DEFAULT_TAB_START', 256);

// Taille de tableau par défaut (fin)
// Default bracket size (end)
define('DEFAULT_TAB_END', 2);

// Couleurs des matchs (FencingTimeLive style)
// Match colors (FencingTimeLive style)
$matchColors = [
    'tYellow',  // Jaune / Yellow
    'tGreen',   // Vert / Green
    'tBlue',    // Bleu / Blue
    'tRed',     // Rouge / Red
    'tPurple',  // Violet / Purple
    'tOrange'   // Orange / Orange
];

// ============================================================================
// COMPÉTITIONS / COMPETITIONS
// ============================================================================

// Liste des compétitions disponibles
// List of available competitions
$competitions = [
    'competition1' => [
        'name' => 'Championnat Régional Épée',
        'date' => '2024-01-15',
        'location' => 'Paris',
        'weapon' => 'épée',
        'category' => 'Senior',
        'file' => 'championnat_regional_epee.xml',
        'enabled' => true
    ],
    'competition2' => [
        'name' => 'Coupe Départementale Fleuret',
        'date' => '2024-01-20',
        'location' => 'Lyon',
        'weapon' => 'fleuret',
        'category' => 'Junior',
        'file' => 'coupe_departementale_fleuret.xml',
        'enabled' => true
    ],
    'competition3' => [
        'name' => 'Tournoi Sabre',
        'date' => '2024-01-25',
        'location' => 'Marseille',
        'weapon' => 'sabre',
        'category' => 'Cadet',
        'file' => 'tournoi_sabre.xml',
        'enabled' => false  // Désactivé / Disabled
    ]
];

// ============================================================================
// SÉCURITÉ / SECURITY
// ============================================================================

// Activer le mode debug (affiche les erreurs)
// Enable debug mode (shows errors)
define('DEBUG_MODE', false);

// Autoriser l'accès depuis des domaines externes (CORS)
// Allow access from external domains (CORS)
define('ALLOW_CORS', false);

// Domaines autorisés pour CORS (si ALLOW_CORS = true)
// Allowed domains for CORS (if ALLOW_CORS = true)
$allowedDomains = [
    'https://example.com',
    'https://www.example.com'
];

// ============================================================================
// CACHE
// ============================================================================

// Activer le cache
// Enable cache
define('CACHE_ENABLED', true);

// Durée du cache (en secondes)
// Cache duration (in seconds)
define('CACHE_DURATION', 300); // 5 minutes

// Dossier de cache
// Cache folder
define('CACHE_DIR', __DIR__ . '/cache/');

// ============================================================================
// LOGS
// ============================================================================

// Activer les logs
// Enable logging
define('LOGGING_ENABLED', false);

// Dossier des logs
// Logs folder
define('LOGS_DIR', __DIR__ . '/logs/');

// Niveau de log (DEBUG, INFO, WARNING, ERROR)
// Log level (DEBUG, INFO, WARNING, ERROR)
define('LOG_LEVEL', 'INFO');

// ============================================================================
// LANGUE / LANGUAGE
// ============================================================================

// Langue par défaut (fr ou en)
// Default language (fr or en)
define('DEFAULT_LANGUAGE', 'fr');

// Langues disponibles
// Available languages
$availableLanguages = ['fr', 'en'];

// ============================================================================
// PERSONNALISATION / CUSTOMIZATION
// ============================================================================

// Titre de l'application
// Application title
define('APP_TITLE', 'BellePoule TV Display');

// Logo (chemin vers l'image)
// Logo (path to image)
define('APP_LOGO', '/images/logo.png');

// Couleur principale (hex)
// Primary color (hex)
define('PRIMARY_COLOR', '#0A1E3F');

// Couleur secondaire (hex)
// Secondary color (hex)
define('SECONDARY_COLOR', '#5198E0');

// ============================================================================
// FONCTIONNALITÉS AVANCÉES / ADVANCED FEATURES
// ============================================================================

// Activer le mode plein écran automatique
// Enable automatic fullscreen mode
define('AUTO_FULLSCREEN', false);

// Activer les notifications sonores
// Enable sound notifications
define('SOUND_NOTIFICATIONS', false);

// Activer l'export PDF
// Enable PDF export
define('PDF_EXPORT_ENABLED', false);

// Activer les statistiques
// Enable statistics
define('STATISTICS_ENABLED', false);

// ============================================================================
// DÉVELOPPEMENT / DEVELOPMENT
// ============================================================================

// Mode développement (désactive le cache, active les logs)
// Development mode (disables cache, enables logs)
define('DEV_MODE', false);

// Afficher les informations de debug dans la console
// Show debug info in console
define('CONSOLE_DEBUG', false);

// ============================================================================
// FIN DE LA CONFIGURATION / END OF CONFIGURATION
// ============================================================================

// Ne modifiez pas en dessous de cette ligne
// Do not modify below this line

if (DEV_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    define('CACHE_ENABLED', false);
    define('LOGGING_ENABLED', true);
    define('CONSOLE_DEBUG', true);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Créer les dossiers nécessaires s'ils n'existent pas
// Create necessary folders if they don't exist
if (CACHE_ENABLED && !is_dir(CACHE_DIR)) {
    mkdir(CACHE_DIR, 0755, true);
}

if (LOGGING_ENABLED && !is_dir(LOGS_DIR)) {
    mkdir(LOGS_DIR, 0755, true);
}
