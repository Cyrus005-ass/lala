<?php
require_once 'includes/config.php';
$lang = detectLang();
$translations = getLangFile($lang);
$settings = getSettings();
$rtl = isRTL($lang);
$dir = $rtl ? 'rtl' : 'ltr';

// Programmes actifs
$db = getDB();
$stmt = $db->query("SELECT p.*, pt.title, pt.description FROM programs p LEFT JOIN program_translations pt ON p.id = pt.program_id AND pt.lang = '$lang' WHERE p.is_active = 1 LIMIT 6");
$programs = $stmt->fetchAll();
$progCount = count($programs);

// FAQ
$stmt2 = $db->prepare("SELECT * FROM faq WHERE lang = ? AND is_active = 1 ORDER BY sort_order LIMIT 8");
$stmt2->execute([$lang]);
$faqs = $stmt2->fetchAll();
if (!$faqs) {
    $stmt2->execute(['en']);
    $faqs = $stmt2->fetchAll();
}
$siteName = $settings['site_name'] ?? 'FINOVA';
$taglineKey = 'site_tagline_' . $lang;
$tagline = $settings[$taglineKey] ?? $settings['site_tagline_fr'] ?? '';
$totalFunded = $settings['total_funded'] ?? '0';
$totalOrgs = $settings['total_organizations'] ?? '0';
$totalCountries = $settings['total_countries'] ?? '0';
$accentColor = $settings['accent_color'] ?? '#C9A84C';
$heroColor = $settings['hero_color'] ?? '#0A2647';

$langNames = ['fr'=>'Français','en'=>'English','es'=>'Español','ar'=>'العربية','pt'=>'Português','zh'=>'中文','ru'=>'Русский'];
$langFlags = ['fr'=>'🇫🇷','en'=>'🇬🇧','es'=>'🇪🇸','ar'=>'🇸🇦','pt'=>'🇵🇹','zh'=>'🇨🇳','ru'=>'🇷🇺'];
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>" dir="<?= $dir ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $siteName ?> — <?= htmlspecialchars($tagline) ?></title>
<meta name="description" content="<?= t('hero_subtitle') ?>">
<link rel="stylesheet" href="assets/css/main.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<?php if($rtl): ?>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Arabic:wght@300;400;500&display=swap" rel="stylesheet">
<?php endif; ?>
<style>
:root { --gold: <?= htmlspecialchars($accentColor) ?>; --navy: <?= htmlspecialchars($heroColor) ?>; }
</style>
</head>
<body>

<!-- Loader -->
<div class="page-loader">
  <div style="text-align:center">
    <div style="font-family:var(--font-serif);font-size:32px;font-weight:600;color:var(--gold);letter-spacing:.1em;margin-bottom:16px"><?= $siteName ?></div>
    <div class="loader-ring"></div>
  </div>
</div>

<!-- Navbar -->
<nav class="navbar">
  <div class="container">
    <a href="index.php?lang=<?= $lang ?>" class="nav-logo">
      <div class="nav-logo-mark">
        <svg viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
      </div>
      <span class="nav-logo-text"><?= $siteName ?></span>
    </a>
    <div class="nav-links" id="navLinks">
      <a href="index.php?lang=<?= $lang ?>" class="nav-link"><?= t('nav_home') ?></a>
      <a href="#programs" class="nav-link"><?= t('nav_programs') ?></a>
      <a href="apply.php?lang=<?= $lang ?>" class="nav-link"><?= t('nav_apply') ?></a>
      <a href="#about" class="nav-link"><?= t('nav_about') ?></a>
      <a href="#faq" class="nav-link"><?= t('nav_faq') ?></a>
      <a href="#contact" class="nav-link"><?= t('nav_contact') ?></a>
      <a href="track.php?lang=<?= $lang ?>" class="nav-link"><?= t('nav_track') ?></a>
    </div>
    <div class="nav-actions">
      <div class="lang-selector">
        <button class="lang-btn" id="langBtn">
          <span><?= $langFlags[$lang] ?? '🌐' ?></span>
          <span><?= strtoupper($lang) ?></span>
          <span>▾</span>
        </button>
        <div class="lang-dropdown" id="langDropdown">
          <?php foreach(LANGUAGES as $l): ?>
          <a class="lang-option <?= $l === $lang ? 'active' : '' ?>" href="?lang=<?= $l ?>">
            <span><?= $langFlags[$l] ?? '🌐' ?></span>
            <span><?= $langNames[$l] ?></span>
          </a>
          <?php endforeach; ?>
        </div>
      </div>
      <a href="apply.php?lang=<?= $lang ?>" class="nav-apply-btn"><?= t('nav_apply') ?></a>
      <div class="burger" id="burger"><span></span><span></span><span></span></div>
    </div>
  </div>
</nav>

<!-- Hero -->
<section class="hero">
  <div class="container">
    <div class="hero-content reveal">
      <div class="hero-eyebrow">
        <span class="badge badge-gold">⬡ <?= t('hero_badge') ?></span>
      </div>
      <h1 class="hero-title"><?= tr('hero_title') ?></h1>
      <p class="hero-subtitle"><?= t('hero_subtitle') ?></p>
      <div class="hero-cta">
        <a href="apply.php?lang=<?= $lang ?>" class="btn btn-gold"><?= t('hero_cta_apply') ?> →</a>
        <a href="#programs" class="btn btn-outline" style="color:#fff;border-color:rgba(255,255,255,.4)"><?= t('hero_cta_programs') ?></a>
      </div>
    </div>
    <div class="hero-card reveal">
      <div class="hero-stats">
        <div class="stat-item">
          <div class="stat-value" data-counter data-target="<?= (int)$totalFunded ?>" data-prefix="$" data-suffix="M">$0M</div>
          <div class="stat-label"><?= t('stats_funded') ?></div>
        </div>
        <div class="stat-item">
          <div class="stat-value" data-counter data-target="<?= (int)$totalOrgs ?>">0</div>
          <div class="stat-label"><?= t('stats_organizations') ?></div>
        </div>
        <div class="stat-item">
          <div class="stat-value" data-counter data-target="<?= (int)$totalCountries ?>">0</div>
          <div class="stat-label"><?= t('stats_countries') ?></div>
        </div>
        <div class="stat-item">
          <div class="stat-value" data-counter data-target="<?= $progCount ?>">0</div>
          <div class="stat-label"><?= t('stats_programs') ?></div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Programs -->
<section class="programs section-pad" id="programs">
  <div class="container">
    <div class="section-header reveal">
      <h2 class="section-title"><?= t('programs_title') ?></h2>
      <p class="section-subtitle"><?= t('programs_subtitle') ?></p>
      <div class="section-line"></div>
    </div>
    <div class="programs-grid">
      <?php foreach($programs as $i => $prog): ?>
      <div class="program-card reveal" style="transition-delay:<?= $i * 80 ?>ms">
        <div class="program-card-header">
          <h3 class="program-title"><?= htmlspecialchars($prog['title'] ?? 'Programme') ?></h3>
          <div class="program-amount">
            <small><?= t('programs_up_to') ?></small>
            <?= formatAmount((float)$prog['max_amount'], $prog['currency']) ?>
          </div>
        </div>
        <p class="program-desc"><?= htmlspecialchars(substr($prog['description'] ?? '', 0, 180)) ?>...</p>
        <div class="program-meta">
          <?php if($prog['target_sector']): ?>
          <div class="program-meta-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M8 12l2 2 4-4"/></svg>
            <?= htmlspecialchars($prog['target_sector']) ?>
          </div>
          <?php endif; ?>
          <?php if($prog['deadline']): ?>
          <div class="program-meta-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
            <?= date('d/m/Y', strtotime($prog['deadline'])) ?>
          </div>
          <?php endif; ?>
        </div>
        <div class="program-actions">
          <a href="apply.php?lang=<?= $lang ?>&program=<?= $prog['id'] ?>" class="btn btn-primary btn-sm"><?= t('programs_apply_btn') ?></a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Process -->
<section class="section-pad" style="background:var(--white)" id="process">
  <div class="container">
    <div class="section-header reveal">
      <h2 class="section-title"><?= t('process_title') ?></h2>
      <p class="section-subtitle"><?= t('process_subtitle') ?></p>
      <div class="section-line"></div>
    </div>
    <div class="process-grid">
      <?php
      $steps = [
        ['num'=>'1','title'=>t('process_step1_title'),'desc'=>t('process_step1_desc')],
        ['num'=>'2','title'=>t('process_step2_title'),'desc'=>t('process_step2_desc')],
        ['num'=>'3','title'=>t('process_step3_title'),'desc'=>t('process_step3_desc')],
        ['num'=>'4','title'=>t('process_step4_title'),'desc'=>t('process_step4_desc')],
      ];
      foreach($steps as $i => $step): ?>
      <div class="process-step reveal" style="transition-delay:<?= $i * 100 ?>ms">
        <div class="step-num"><?= $step['num'] ?></div>
        <h3 class="step-title"><?= $step['title'] ?></h3>
        <p class="step-desc"><?= $step['desc'] ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- About -->
<section class="section-pad" style="background:var(--cream)" id="about">
  <div class="container">
    <div class="about-grid">
      <div class="about-text reveal">
        <h2 class="section-title"><?= t('about_title') ?></h2>
        <div class="section-line"></div>
        <p style="margin-top:20px"><?= t('about_mission') ?></p>
        <p><?= t('about_vision') ?></p>
        <div class="about-highlights">
          <div class="highlight-item">
            <strong>100%</strong>
            <span><?= t('hero_badge') ?></span>
          </div>
          <div class="highlight-item">
            <strong><?= $progCount ?>+</strong>
            <span><?= t('stats_programs') ?></span>
          </div>
        </div>
      </div>
      <div class="about-visual reveal">
        <ul class="values-list">
          <li><div class="val-icon">🌍</div><div class="val-text"><strong>Impact global</strong><span>Nous finançons des projets dans le monde entier</span></div></li>
          <li><div class="val-icon">🔍</div><div class="val-text"><strong>Transparence totale</strong><span>Chaque décision est documentée et justifiée</span></div></li>
          <li><div class="val-icon">⚡</div><div class="val-text"><strong>Processus rapide</strong><span>Décision sous 30 à 60 jours ouvrables</span></div></li>
          <li><div class="val-icon">🤝</div><div class="val-text"><strong>Accompagnement</strong><span>Nos experts vous guident tout au long du processus</span></div></li>
          <li><div class="val-icon">📊</div><div class="val-text"><strong>Suivi de l'impact</strong><span>Nous mesurons les résultats de chaque projet financé</span></div></li>
        </ul>
      </div>
    </div>
  </div>
</section>

<!-- FAQ -->
<?php if($faqs): ?>
<section class="section-pad" id="faq">
  <div class="container">
    <div class="section-header reveal">
      <h2 class="section-title"><?= t('faq_title') ?></h2>
      <div class="section-line"></div>
    </div>
    <div class="faq-list">
      <?php foreach($faqs as $i => $faq): ?>
      <div class="faq-item reveal" style="transition-delay:<?= $i * 60 ?>ms">
        <div class="faq-question">
          <span><?= htmlspecialchars($faq['question']) ?></span>
          <div class="faq-chevron">▾</div>
        </div>
        <div class="faq-answer">
          <div class="faq-answer-inner"><?= htmlspecialchars($faq['answer']) ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- Contact -->
<section class="section-pad" style="background:var(--cream)" id="contact">
  <div class="container">
    <div class="section-header reveal">
      <h2 class="section-title"><?= t('contact_title') ?></h2>
      <p class="section-subtitle"><?= t('contact_subtitle') ?></p>
      <div class="section-line"></div>
    </div>
    <div class="contact-grid">
      <div class="contact-info reveal">
        <h3><?= $siteName ?></h3>
        <div class="contact-detail">
          <div class="contact-detail-icon">📧</div>
          <div class="contact-detail-text">
            <label>Email</label>
            <p><?= htmlspecialchars($settings['contact_email'] ?? 'contact@finova.org') ?></p>
          </div>
        </div>
        <div class="contact-detail">
          <div class="contact-detail-icon">📞</div>
          <div class="contact-detail-text">
            <label>Téléphone</label>
            <p><?= htmlspecialchars($settings['contact_phone'] ?? '+1 (555) 000-0000') ?></p>
          </div>
        </div>
        <div class="contact-detail">
          <div class="contact-detail-icon">📍</div>
          <div class="contact-detail-text">
            <label>Adresse</label>
            <p><?= htmlspecialchars($settings['contact_address'] ?? 'À compléter') ?></p>
          </div>
        </div>
        <div style="margin-top:28px;padding-top:28px;border-top:1px solid rgba(255,255,255,.1)">
          <a href="track.php?lang=<?= $lang ?>" class="btn btn-gold" style="width:100%;justify-content:center"><?= t('nav_track') ?> →</a>
        </div>
      </div>
      <div class="contact-form-card reveal">
        <div id="contactAlert"></div>
        <form id="contactForm">
          <input type="hidden" name="lang" value="<?= $lang ?>">
          <div class="form-grid">
            <div class="form-group">
              <label class="form-label"><?= t('contact_name') ?></label>
              <input type="text" name="full_name" class="form-control" required>
              <span class="error-msg"><?= t('form_error_required') ?></span>
            </div>
            <div class="form-group">
              <label class="form-label"><?= t('contact_email_field') ?></label>
              <input type="email" name="email" class="form-control" required>
              <span class="error-msg"><?= t('form_error_email') ?></span>
            </div>
            <div class="form-group full">
              <label class="form-label"><?= t('contact_subject') ?></label>
              <input type="text" name="subject" class="form-control">
            </div>
            <div class="form-group full">
              <label class="form-label"><?= t('contact_message') ?></label>
              <textarea name="message" class="form-control" rows="5" required></textarea>
              <span class="error-msg"><?= t('form_error_required') ?></span>
            </div>
          </div>
          <div style="margin-top:20px">
            <button type="submit" class="btn btn-primary"><?= t('contact_send') ?></button>
          </div>
        </form>
      </div>
    </div>
  </div>
</section>

<!-- Footer -->
<footer class="footer">
  <div class="container">
    <div class="footer-grid">
      <div class="footer-brand">
        <a href="index.php?lang=<?= $lang ?>" class="nav-logo" style="text-decoration:none">
          <div class="nav-logo-mark"><svg viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg></div>
          <span class="nav-logo-text"><?= $siteName ?></span>
        </a>
        <p style="margin-top:14px"><?= t('footer_desc') ?></p>
      </div>
      <div class="footer-col">
        <h4><?= t('nav_programs') ?></h4>
        <ul class="footer-links">
          <?php foreach($programs as $prog): ?>
          <li><a href="apply.php?lang=<?= $lang ?>&program=<?= $prog['id'] ?>"><?= htmlspecialchars($prog['title'] ?? '') ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <div class="footer-col">
        <h4><?= t('nav_about') ?></h4>
        <ul class="footer-links">
          <li><a href="#about"><?= t('nav_about') ?></a></li>
          <li><a href="#process"><?= t('process_title') ?></a></li>
          <li><a href="#faq"><?= t('nav_faq') ?></a></li>
          <li><a href="#contact"><?= t('nav_contact') ?></a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h4>Langues</h4>
        <ul class="footer-links">
          <?php foreach(LANGUAGES as $l): ?>
          <li><a href="?lang=<?= $l ?>" <?= $l === $lang ? 'style="color:var(--gold-light)"' : '' ?>><?= $langFlags[$l] ?? '' ?> <?= $langNames[$l] ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
    <div class="footer-bottom">
      <span>© <?= date('Y') ?> <?= $siteName ?>. <?= t('footer_rights') ?></span>
      <div class="footer-bottom-links">
        <a href="#"><?= t('footer_privacy') ?></a>
        <a href="#"><?= t('footer_terms') ?></a>
        <a href="#"><?= t('footer_legal') ?></a>
      </div>
    </div>
  </div>
</footer>

<script src="assets/js/main.js"></script>
</body>
</html>
