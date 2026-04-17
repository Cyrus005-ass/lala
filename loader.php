<?php
// =============================================
// CHARGEUR DE LANGUE
// =============================================
function loadLang($lang) {
    require_once __DIR__ . '/translations.php';
    $varName = 'lang_' . $lang;
    if (isset($$varName)) return $$varName;
    // Fallback FR
    require_once __DIR__ . '/fr.php';
    return $lang_fr;
}

function t($key) {
    global $T;
    return $T[$key] ?? $key;
}

// Liste des langues disponibles
function getLangList() {
    return [
        'fr' => ['name' => 'Français',    'flag' => '🇫🇷', 'dir' => 'ltr'],
        'en' => ['name' => 'English',     'flag' => '🇬🇧', 'dir' => 'ltr'],
        'es' => ['name' => 'Español',     'flag' => '🇪🇸', 'dir' => 'ltr'],
        'ar' => ['name' => 'العربية',    'flag' => '🇸🇦', 'dir' => 'rtl'],
        'pt' => ['name' => 'Português',   'flag' => '🇵🇹', 'dir' => 'ltr'],
        'zh' => ['name' => '中文',         'flag' => '🇨🇳', 'dir' => 'ltr'],
        'ru' => ['name' => 'Русский',     'flag' => '🇷🇺', 'dir' => 'ltr'],
        'de' => ['name' => 'Deutsch',     'flag' => '🇩🇪', 'dir' => 'ltr'],
        'it' => ['name' => 'Italiano',    'flag' => '🇮🇹', 'dir' => 'ltr'],
        'tr' => ['name' => 'Türkçe',      'flag' => '🇹🇷', 'dir' => 'ltr'],
    ];
}