<?php
// =============================================
// CONFIGURATION PRINCIPALE
// =============================================

define('DB_HOST', 'localhost');
define('DB_NAME', 'banksite');
define('DB_USER', 'root');        // Changer en production
define('DB_PASS', '');            // Changer en production
define('DB_PORT', '3306');

define('SITE_URL', 'http://localhost/banksite');  // Changer en production
define('SITE_NAME', 'Fonds International de Développement');
define('ADMIN_EMAIL', 'admin@fid.org');

define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('UPLOAD_URL', SITE_URL . '/uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB

define('SESSION_LIFETIME', 3600); // 1 heure
define('TIMEZONE', 'Europe/Paris');

define('SUPPORTED_LANGS', ['fr', 'en', 'es', 'ar', 'pt', 'zh', 'ru', 'de', 'it', 'tr']);
define('DEFAULT_LANG', 'fr');

date_default_timezone_set(TIMEZONE);

// Connexion PDO
function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4;port=" . DB_PORT;
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            die(json_encode(['error' => 'Connexion base de données impossible.']));
        }
    }
    return $pdo;
}

// Génération référence unique
function generateReference() {
    return 'FID-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 8)) . '-' . date('Y');
}

// Nettoyage input
function sanitize($val) {
    return htmlspecialchars(strip_tags(trim($val)), ENT_QUOTES, 'UTF-8');
}

// Langue active
function getLang() {
    $lang = $_GET['lang'] ?? $_SESSION['lang'] ?? DEFAULT_LANG;
    if (!in_array($lang, SUPPORTED_LANGS)) $lang = DEFAULT_LANG;
    $_SESSION['lang'] = $lang;
    return $lang;
}

session_start();