<?php
require_once 'includes/config.php';
$lang = detectLang();
$translations = getLangFile($lang);
$settings = getSettings();
$rtl = isRTL($lang);
$dir = $rtl ? 'rtl' : 'ltr';
$siteName = $settings['site_name'] ?? 'FINOVA';

// Load programs
$db = getDB();
$stmt = $db->prepare("SELECT p.*, pt.title, pt.description FROM programs p LEFT JOIN program_translations pt ON p.id = pt.program_id AND pt.lang = ? WHERE p.is_active = 1");
$stmt->execute([$lang]);
$programs = $stmt->fetchAll();
if (!$programs) {
    $stmt->execute(['fr']);
    $programs = $stmt->fetchAll();
}
$selectedProgramId = (int)($_GET['program'] ?? 0);

$langNames = ['fr'=>'Français','en'=>'English','es'=>'Español','ar'=>'العربية','pt'=>'Português','zh'=>'中文','ru'=>'Русский'];
$langFlags = ['fr'=>'🇫🇷','en'=>'🇬🇧','es'=>'🇪🇸','ar'=>'🇸🇦','pt'=>'🇵🇹','zh'=>'🇨🇳','ru'=>'🇷🇺'];
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>" dir="<?= $dir ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= t('form_title') ?> — <?= $siteName ?></title>
<link rel="stylesheet" href="assets/css/main.css">
<?php if($rtl): ?><link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Arabic:wght@300;400;500&display=swap" rel="stylesheet"><?php endif; ?>
</head>
<body>
<div class="page-loader"><div style="text-align:center"><div style="font-family:var(--font-serif);font-size:32px;font-weight:600;color:var(--gold);letter-spacing:.1em;margin-bottom:16px"><?= $siteName ?></div><div class="loader-ring"></div></div></div>

<nav class="navbar">
  <div class="container">
    <a href="index.php?lang=<?= $lang ?>" class="nav-logo">
      <div class="nav-logo-mark"><svg viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg></div>
      <span class="nav-logo-text"><?= $siteName ?></span>
    </a>
    <div class="nav-links" id="navLinks">
      <a href="index.php?lang=<?= $lang ?>" class="nav-link"><?= t('nav_home') ?></a>
      <a href="index.php?lang=<?= $lang ?>#programs" class="nav-link"><?= t('nav_programs') ?></a>
      <a href="apply.php?lang=<?= $lang ?>" class="nav-link active"><?= t('nav_apply') ?></a>
      <a href="track.php?lang=<?= $lang ?>" class="nav-link"><?= t('nav_track') ?></a>
    </div>
    <div class="nav-actions">
      <div class="lang-selector">
        <button class="lang-btn" id="langBtn"><span><?= $langFlags[$lang] ?? '🌐' ?></span><span><?= strtoupper($lang) ?></span><span>▾</span></button>
        <div class="lang-dropdown" id="langDropdown">
          <?php foreach(LANGUAGES as $l): ?><a class="lang-option <?= $l===$lang?'active':'' ?>" href="?lang=<?= $l ?>"><?= ($langFlags[$l]??'🌐').' '.$langNames[$l] ?></a><?php endforeach; ?>
        </div>
      </div>
      <div class="burger" id="burger"><span></span><span></span><span></span></div>
    </div>
  </div>
</nav>

<div class="form-page">
  <div class="form-container">
    <div class="form-header reveal">
      <span class="badge badge-gold">⬡ <?= t('hero_badge') ?></span>
      <h1 class="section-title" style="margin-top:16px"><?= t('form_title') ?></h1>
      <p style="color:var(--gray-600);margin-top:8px"><?= t('form_subtitle') ?></p>
    </div>

    <!-- Success card -->
    <div class="success-card" id="successCard">
      <div class="success-icon">✅</div>
      <h2 style="font-family:var(--font-serif);font-size:26px;color:var(--navy);margin-bottom:12px"><?= t('form_success_title') ?></h2>
      <p style="color:var(--gray-600);margin-bottom:16px"><?= tr('form_success_msg', ['ref'=>'']) ?></p>
      <div class="success-ref">—</div>
      <p style="font-size:13px;color:var(--gray-400);margin-top:12px"><?= t('form_success_email') ?></p>
      <div style="margin-top:28px;display:flex;gap:12px;justify-content:center;flex-wrap:wrap">
        <a href="track.php?lang=<?= $lang ?>" class="btn btn-primary"><?= t('nav_track') ?></a>
        <a href="index.php?lang=<?= $lang ?>" class="btn btn-outline"><?= t('nav_home') ?></a>
      </div>
    </div>

    <form id="applicationForm" enctype="multipart/form-data" novalidate>
      <input type="hidden" name="lang" value="<?= $lang ?>">

      <!-- PROGRAMME -->
      <div class="form-card reveal">
        <div class="form-section-title"><span class="step-icon">1</span><?= t('form_section_program') ?></div>
        <div class="form-group">
          <div class="program-selector">
            <?php foreach($programs as $prog): ?>
            <label class="program-option <?= $prog['id']==$selectedProgramId?'selected':'' ?>">
              <input type="radio" name="program_id" value="<?= $prog['id'] ?>" <?= $prog['id']==$selectedProgramId?'checked':'' ?> required
                data-min="<?= $prog['min_amount'] ?>" data-max="<?= $prog['max_amount'] ?>">
              <h4><?= htmlspecialchars($prog['title'] ?? 'Programme') ?></h4>
              <p><?= htmlspecialchars(substr($prog['description'] ?? '', 0, 60)) ?>...</p>
              <div class="amount"><?= t('programs_up_to') ?> <?= formatAmount((float)$prog['max_amount'], $prog['currency']) ?></div>
            </label>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- ORGANISATION -->
      <div class="form-card reveal">
        <div class="form-section-title"><span class="step-icon">2</span><?= t('form_section_org') ?></div>
        <div class="form-grid">
          <div class="form-group full">
            <label class="form-label"><?= t('form_org_name') ?></label>
            <input type="text" name="org_name" class="form-control" placeholder="Ex: Association Espoir Sahel" required>
            <span class="error-msg"><?= t('form_error_required') ?></span>
          </div>
          <div class="form-group">
            <label class="form-label"><?= t('form_org_type') ?></label>
            <select name="org_type" class="form-control" required>
              <option value="">— <?= t('form_org_type') ?> —</option>
              <?php foreach(['ngo','association','cooperative','sme','startup','public','other'] as $ot): ?>
              <option value="<?= $ot ?>"><?= t('form_org_type_'.$ot) ?></option>
              <?php endforeach; ?>
            </select>
            <span class="error-msg"><?= t('form_error_required') ?></span>
          </div>
          <div class="form-group">
            <label class="form-label"><?= t('form_org_country') ?></label>
            <input type="text" name="org_country" class="form-control" placeholder="Ex: Bénin, France, Sénégal..." required>
            <span class="error-msg"><?= t('form_error_required') ?></span>
          </div>
          <div class="form-group full">
            <label class="form-label"><?= t('form_org_address') ?></label>
            <textarea name="org_address" class="form-control" rows="2" placeholder="Numéro, rue, ville, code postal, pays" required></textarea>
            <span class="error-msg"><?= t('form_error_required') ?></span>
          </div>
          <div class="form-group">
            <label class="form-label"><?= t('form_org_reg_num') ?></label>
            <input type="text" name="org_registration_number" class="form-control" placeholder="Ex: W751234567" required>
            <span class="error-msg"><?= t('form_error_required') ?></span>
          </div>
          <div class="form-group">
            <label class="form-label"><?= t('form_org_founded') ?></label>
            <input type="number" name="org_founded_year" class="form-control" min="1900" max="<?= date('Y') ?>" placeholder="<?= date('Y') ?>">
          </div>
          <div class="form-group full">
            <label class="form-label"><?= t('form_org_website') ?></label>
            <input type="url" name="org_website" class="form-control" placeholder="https://www.votre-organisation.org">
          </div>
        </div>
      </div>

      <!-- CONTACT -->
      <div class="form-card reveal">
        <div class="form-section-title"><span class="step-icon">3</span><?= t('form_section_contact') ?></div>
        <div class="form-grid">
          <div class="form-group">
            <label class="form-label"><?= t('form_contact_name') ?></label>
            <input type="text" name="contact_name" class="form-control" required>
            <span class="error-msg"><?= t('form_error_required') ?></span>
          </div>
          <div class="form-group">
            <label class="form-label"><?= t('form_contact_title') ?></label>
            <input type="text" name="contact_title" class="form-control" placeholder="Ex: Directeur exécutif" required>
            <span class="error-msg"><?= t('form_error_required') ?></span>
          </div>
          <div class="form-group">
            <label class="form-label"><?= t('form_contact_email') ?></label>
            <input type="email" name="contact_email" class="form-control" required>
            <span class="error-msg"><?= t('form_error_email') ?></span>
          </div>
          <div class="form-group">
            <label class="form-label"><?= t('form_contact_phone') ?></label>
            <input type="tel" name="contact_phone" class="form-control" placeholder="+229 XX XX XX XX" required>
            <span class="error-msg"><?= t('form_error_required') ?></span>
          </div>
        </div>
      </div>

      <!-- PROJET -->
      <div class="form-card reveal">
        <div class="form-section-title"><span class="step-icon">4</span><?= t('form_section_project') ?></div>
        <div class="form-grid">
          <div class="form-group full">
            <label class="form-label"><?= t('form_project_title') ?></label>
            <input type="text" name="project_title" class="form-control" required>
            <span class="error-msg"><?= t('form_error_required') ?></span>
          </div>
          <div class="form-group full">
            <label class="form-label"><?= t('form_project_desc') ?></label>
            <textarea name="project_description" class="form-control" rows="6" required placeholder="Décrivez votre projet en détail (500 mots minimum recommandés)..."></textarea>
            <div class="form-hint">500 <?= t('form_char_limit') ?></div>
            <span class="error-msg"><?= t('form_error_required') ?></span>
          </div>
          <div class="form-group full">
            <label class="form-label"><?= t('form_project_objectives') ?></label>
            <textarea name="project_objectives" class="form-control" rows="3" required placeholder="Ex: 1. Construire un centre de santé communautaire&#10;2. Former 50 agents de santé..."></textarea>
            <span class="error-msg"><?= t('form_error_required') ?></span>
          </div>
          <div class="form-group full">
            <label class="form-label"><?= t('form_project_beneficiaries') ?></label>
            <textarea name="project_beneficiaries" class="form-control" rows="3" required placeholder="Qui bénéficiera de ce projet ? Combien de personnes ? Quel impact attendu ?"></textarea>
            <span class="error-msg"><?= t('form_error_required') ?></span>
          </div>
          <div class="form-group">
            <label class="form-label"><?= t('form_project_location') ?></label>
            <input type="text" name="project_location" class="form-control" placeholder="Ville, région, pays" required>
            <span class="error-msg"><?= t('form_error_required') ?></span>
          </div>
          <div class="form-group">
            <label class="form-label"><?= t('form_project_duration') ?></label>
            <input type="text" name="project_duration" class="form-control" placeholder="Ex: 18 mois" required>
            <span class="error-msg"><?= t('form_error_required') ?></span>
          </div>
          <div class="form-group">
            <label class="form-label"><?= t('form_project_start') ?></label>
            <input type="date" name="project_start_date" class="form-control" min="<?= date('Y-m-d') ?>">
          </div>
        </div>
      </div>

      <!-- BUDGET -->
      <div class="form-card reveal">
        <div class="form-section-title"><span class="step-icon">5</span><?= t('form_section_budget') ?></div>
        <div class="form-grid">
          <div class="form-group">
            <label class="form-label"><?= t('form_project_budget') ?></label>
            <input type="number" name="project_budget" class="form-control" min="0" step="100" placeholder="0">
          </div>
          <div class="form-group">
            <label class="form-label"><?= t('form_requested_amount') ?></label>
            <input type="number" id="requested_amount" name="requested_amount" class="form-control" min="0" step="100" required placeholder="Montant en USD">
            <div class="form-hint">Respectez les limites du programme sélectionné.</div>
            <span class="error-msg"><?= t('form_error_amount') ?></span>
          </div>
        </div>
      </div>

      <!-- DOCUMENTS -->
      <div class="form-card reveal">
        <div class="form-section-title"><span class="step-icon">6</span><?= t('form_section_docs') ?></div>
        <div class="form-grid">
          <?php
          $docs = [
            ['field'=>'doc_registration','label'=>t('form_doc_registration'),'required'=>true],
            ['field'=>'doc_statutes','label'=>t('form_doc_statutes'),'required'=>true],
            ['field'=>'doc_budget_plan','label'=>t('form_doc_budget_plan'),'required'=>true],
            ['field'=>'doc_project_plan','label'=>t('form_doc_project_plan'),'required'=>true],
            ['field'=>'doc_last_report','label'=>t('form_doc_last_report'),'required'=>false],
          ];
          foreach($docs as $doc): ?>
          <div class="form-group">
            <label class="form-label"><?= $doc['label'] ?></label>
            <div class="file-upload">
              <input type="file" name="<?= $doc['field'] ?>" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" <?= $doc['required']?'required':'' ?>>
              <div class="file-upload-icon">📎</div>
              <div class="file-upload-text"><strong>Cliquez pour choisir</strong> ou glisser-déposer</div>
              <div class="file-upload-text" style="font-size:11px;margin-top:4px">PDF, DOC, JPG — max 5MB</div>
              <div class="file-name" style="display:none"></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- SUBMIT -->
      <div class="form-footer reveal">
        <p style="font-size:13px;color:var(--gray-400);margin-bottom:16px">En soumettant ce formulaire, vous certifiez que toutes les informations fournies sont exactes et complètes.</p>
        <button type="submit" class="btn btn-primary form-submit-btn"><?= t('form_submit') ?> →</button>
      </div>
    </form>
  </div>
</div>

<footer class="footer" style="padding:32px 0">
  <div class="container">
    <div class="footer-bottom">
      <span>© <?= date('Y') ?> <?= $siteName ?>. <?= t('footer_rights') ?></span>
      <div class="footer-bottom-links">
        <a href="index.php?lang=<?= $lang ?>"><?= t('nav_home') ?></a>
        <a href="track.php?lang=<?= $lang ?>"><?= t('nav_track') ?></a>
      </div>
    </div>
  </div>
</footer>

<script src="assets/js/main.js"></script>
</body>
</html>
