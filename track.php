<?php
require_once 'includes/config.php';
$lang = detectLang();
$translations = getLangFile($lang);
$settings = getSettings();
$rtl = isRTL($lang);
$siteName = $settings['site_name'] ?? 'FINOVA';
$langNames = ['fr'=>'Français','en'=>'English','es'=>'Español','ar'=>'العربية','pt'=>'Português','zh'=>'中文','ru'=>'Русский'];
$langFlags = ['fr'=>'🇫🇷','en'=>'🇬🇧','es'=>'🇪🇸','ar'=>'🇸🇦','pt'=>'🇵🇹','zh'=>'🇨🇳','ru'=>'🇷🇺'];
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>" dir="<?= $rtl?'rtl':'ltr' ?>">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?= t('track_title') ?> — <?= $siteName ?></title>
<link rel="stylesheet" href="assets/css/main.css">
<?php if($rtl): ?><link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Arabic:wght@300;400;500&display=swap" rel="stylesheet"><?php endif; ?>
</head>
<body>
<div class="page-loader"><div style="text-align:center"><div style="font-family:var(--font-serif);font-size:32px;font-weight:600;color:var(--gold);letter-spacing:.1em;margin-bottom:16px"><?= $siteName ?></div><div class="loader-ring"></div></div></div>

<nav class="navbar">
  <div class="container">
    <a href="index.php?lang=<?= $lang ?>" class="nav-logo"><div class="nav-logo-mark"><svg viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg></div><span class="nav-logo-text"><?= $siteName ?></span></a>
    <div class="nav-links" id="navLinks">
      <a href="index.php?lang=<?= $lang ?>" class="nav-link"><?= t('nav_home') ?></a>
      <a href="apply.php?lang=<?= $lang ?>" class="nav-link"><?= t('nav_apply') ?></a>
      <a href="track.php?lang=<?= $lang ?>" class="nav-link active"><?= t('nav_track') ?></a>
    </div>
    <div class="nav-actions">
      <div class="lang-selector"><button class="lang-btn" id="langBtn"><span><?= $langFlags[$lang]??'🌐' ?></span><span><?= strtoupper($lang) ?></span><span>▾</span></button><div class="lang-dropdown" id="langDropdown"><?php foreach(LANGUAGES as $l): ?><a class="lang-option <?= $l===$lang?'active':'' ?>" href="?lang=<?= $l ?>"><?= ($langFlags[$l]??'🌐').' '.$langNames[$l] ?></a><?php endforeach; ?></div></div>
      <div class="burger" id="burger"><span></span><span></span><span></span></div>
    </div>
  </div>
</nav>

<section class="track-page">
  <div class="container">
    <div class="section-header reveal"><h1 class="section-title"><?= t('track_title') ?></h1><p class="section-subtitle"><?= t('track_subtitle') ?></p><div class="section-line"></div></div>
    <div class="track-card reveal">
      <form id="trackForm">
        <input type="hidden" name="lang" value="<?= $lang ?>">
        <div class="form-group" style="margin-bottom:16px">
          <label class="form-label"><?= t('track_reference') ?></label>
          <input type="text" name="reference" class="form-control" placeholder="FNV-XXXXXXXX-2025" required style="font-family:monospace;letter-spacing:.05em">
        </div>
        <div class="form-group" style="margin-bottom:20px">
          <label class="form-label"><?= t('track_email') ?></label>
          <input type="email" name="email" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary"><?= t('track_btn') ?> →</button>
      </form>
      <div id="trackResult"></div>
    </div>
  </div>
</section>

<footer class="footer" style="padding:32px 0"><div class="container"><div class="footer-bottom"><span>© <?= date('Y') ?> <?= $siteName ?>. <?= t('footer_rights') ?></span></div></div></footer>
<script src="assets/js/main.js"></script>
</body>
</html>
