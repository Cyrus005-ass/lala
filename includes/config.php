<?php
// ============================================
// CONFIGURATION PRINCIPALE — À MODIFIER
// ============================================

define('DB_HOST', 'localhost');
define('DB_NAME', 'finova_bank');
define('DB_USER', 'root');          // ← Votre utilisateur MySQL
define('DB_PASS', '');              // ← Votre mot de passe MySQL
define('DB_CHARSET', 'utf8mb4');

define('SITE_URL', 'http://localhost/banksite'); // ← Votre URL
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('UPLOAD_URL', SITE_URL . '/uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5 MB

// Langues disponibles
define('LANGUAGES', ['fr', 'en', 'es', 'ar', 'pt', 'zh', 'ru']);
define('DEFAULT_LANG', 'fr');
define('RTL_LANGS', ['ar']);

// Session
define('SESSION_TIMEOUT', 3600); // 1 heure

// ============================================
// CONNEXION PDO
// ============================================
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
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

// ============================================
// GESTION DES LANGUES
// ============================================
function detectLang(): string {
    if (isset($_GET['lang']) && in_array($_GET['lang'], LANGUAGES)) {
        $_SESSION['lang'] = $_GET['lang'];
    }
    if (isset($_SESSION['lang']) && in_array($_SESSION['lang'], LANGUAGES)) {
        return $_SESSION['lang'];
    }
    $browser = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'fr', 0, 2);
    return in_array($browser, LANGUAGES) ? $browser : DEFAULT_LANG;
}

function getLangFile(string $lang): array {
    $file = __DIR__ . "/../lang/{$lang}.php"; // ✅ remonter d’un dossier

    if (!file_exists($file)) {
        $file = __DIR__ . "/../lang/fr.php";
    }

    if (!file_exists($file)) {
        die("Fichier langue introuvable !");
    }

    $translations = include $file;

    return is_array($translations) ? $translations : [];
}

function t(string $key, array $vars = []): string {
    global $translations;
    $text = $translations[$key] ?? $key;
    foreach ($vars as $k => $v) {
        $text = str_replace('{' . $k . '}', $v, $text);
    }
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

function tr(string $key, array $vars = []): string {
    global $translations;
    $text = $translations[$key] ?? $key;
    foreach ($vars as $k => $v) {
        $text = str_replace('{' . $k . '}', $v, $text);
    }
    return $text; // raw (no escape)
}

// ============================================
// FONCTIONS UTILITAIRES
// ============================================
function generateRef(): string {
    return 'FNV-' . strtoupper(substr(md5(uniqid()), 0, 8)) . '-' . date('Y');
}

function sanitize(string $val): string {
    return htmlspecialchars(trim($val), ENT_QUOTES, 'UTF-8');
}

function formatAmount(float $amount, string $currency = 'USD'): string {
    return number_format($amount, 0, ',', ' ') . ' ' . $currency;
}

function isRTL(string $lang): bool {
    return in_array($lang, RTL_LANGS);
}

function getSetting(string $key, string $default = ''): string {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT `value` FROM settings WHERE `key` = ?");
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        return $row ? $row['value'] : $default;
    } catch (Exception $e) {
        return $default;
    }
}

function getSettings(): array {
    try {
        $db = getDB();
        $stmt = $db->query("SELECT `key`, `value` FROM settings");
        $result = [];
        while ($row = $stmt->fetch()) {
            $result[$row['key']] = $row['value'];
        }
        return $result;
    } catch (Exception $e) {
        return [];
    }
}

// ============================================
// UPLOAD DE FICHIER SÉCURISÉ
// ============================================
function handleUpload(string $field): ?string {
    if (!isset($_FILES[$field]) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) return null;
    $allowed = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
    $ext = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) return null;
    if ($_FILES[$field]['size'] > MAX_FILE_SIZE) return null;
    if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
    $name = uniqid() . '_' . time() . '.' . $ext;
    $dest = UPLOAD_DIR . $name;
    if (move_uploaded_file($_FILES[$field]['tmp_name'], $dest)) return $name;
    return null;
}

// Démarrage session
if (session_status() === PHP_SESSION_NONE) session_start();
