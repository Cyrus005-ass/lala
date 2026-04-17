<?php
// admin/settings.php
require_once '../includes/config.php';
if (empty($_SESSION['admin_id'])) { header('Location: login.php'); exit; }
if (isset($_SESSION['last_activity']) && (time()-$_SESSION['last_activity'])>SESSION_TIMEOUT) { session_destroy(); header('Location: login.php?timeout=1'); exit; }
$_SESSION['last_activity'] = time();

$db = getDB();
$adm = $db->prepare("SELECT * FROM admins WHERE id=?"); $adm->execute([$_SESSION['admin_id']]); $adm = $adm->fetch();

$success = '';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $fields = ['site_name','site_tagline_fr','site_tagline_en','site_tagline_es','site_tagline_ar','site_tagline_pt','site_tagline_zh','site_tagline_ru','contact_email','contact_phone','contact_address','hero_color','accent_color','total_funded','total_organizations','total_countries'];
    foreach($fields as $field) {
        if (isset($_POST[$field])) {
            $db->prepare("INSERT INTO settings(`key`,`value`) VALUES(?,?) ON DUPLICATE KEY UPDATE `value`=?")->execute([$field,$_POST[$field],$_POST[$field]]);
        }
    }
    $success = 'Paramètres enregistrés.';
}

$settings = getSettings();
$pendingCount = $db->query("SELECT COUNT(*) FROM applications WHERE status='pending'")->fetchColumn();
$unreadMsgs = $db->query("SELECT COUNT(*) FROM contacts WHERE is_read=0")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Paramètres — Admin FINOVA</title>
<link rel="stylesheet" href="../assets/css/main.css">
<style>
.admin-layout{display:flex;min-height:100vh}.sidebar{width:240px;background:var(--navy);flex-shrink:0;display:flex;flex-direction:column;position:fixed;top:0;bottom:0;left:0;z-index:100}.sidebar-brand{padding:24px 20px;border-bottom:1px solid rgba(255,255,255,.08)}.sidebar-brand .brand{font-family:var(--font-serif);font-size:20px;color:var(--white);font-weight:600}.sidebar-brand .role{font-size:11px;color:var(--gold);text-transform:uppercase;letter-spacing:.08em;margin-top:2px}.sidebar-nav{flex:1;padding:16px 0;overflow-y:auto}.nav-item{display:flex;align-items:center;gap:10px;padding:10px 20px;color:rgba(255,255,255,.65);font-size:13.5px;cursor:pointer;text-decoration:none;transition:var(--transition);border-left:3px solid transparent}.nav-item:hover{color:var(--white);background:rgba(255,255,255,.06)}.nav-item.active{color:var(--white);background:rgba(255,255,255,.1);border-left-color:var(--gold)}.nav-item .icon{width:18px;text-align:center}.nav-sep{height:1px;background:rgba(255,255,255,.06);margin:8px 20px}.nav-badge{margin-left:auto;background:var(--gold);color:var(--navy);font-size:10px;padding:1px 6px;border-radius:10px;font-weight:600}.sidebar-bottom{padding:16px 20px;border-top:1px solid rgba(255,255,255,.08)}.admin-main{margin-left:240px;flex:1;background:var(--gray-50);min-height:100vh}.admin-topbar{background:var(--white);border-bottom:1px solid var(--gray-200);padding:0 28px;height:60px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:50}.topbar-title{font-weight:500;color:var(--navy);font-size:16px}.topbar-right{display:flex;align-items:center;gap:14px}.admin-content{padding:28px}.card{background:var(--white);border-radius:var(--radius-lg);border:1px solid var(--gray-200);overflow:hidden}.card-header{padding:16px 20px;border-bottom:1px solid var(--gray-200);display:flex;align-items:center;justify-content:space-between}.card-title{font-weight:500;color:var(--navy);font-size:14px}
.settings-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;padding:24px}.settings-section{margin-bottom:24px}.section-heading{font-weight:500;color:var(--navy);font-size:13px;text-transform:uppercase;letter-spacing:.06em;margin-bottom:14px;padding-bottom:8px;border-bottom:2px solid var(--gold)}
</style>
</head>
<body>
<div class="admin-layout">
  <aside class="sidebar">
    <div class="sidebar-brand"><div class="brand">FINOVA Admin</div><div class="role"><?= htmlspecialchars($adm['role']??'') ?></div></div>
    <nav class="sidebar-nav">
      <a class="nav-item" href="index.php"><span class="icon">📊</span> Tableau de bord</a>
      <a class="nav-item" href="applications.php"><span class="icon">📋</span> Candidatures <span class="nav-badge"><?= $pendingCount ?></span></a>
      <a class="nav-item" href="programs.php"><span class="icon">🎯</span> Programmes</a>
      <div class="nav-sep"></div>
      <a class="nav-item" href="messages.php"><span class="icon">💬</span> Messages <?php if($unreadMsgs>0): ?><span class="nav-badge"><?= $unreadMsgs ?></span><?php endif; ?></a>
      <a class="nav-item" href="faq.php"><span class="icon">❓</span> FAQ</a>
      <div class="nav-sep"></div>
      <a class="nav-item active" href="settings.php"><span class="icon">⚙️</span> Paramètres</a>
      <a class="nav-item" href="admins.php"><span class="icon">👥</span> Administrateurs</a>
    </nav>
    <div class="sidebar-bottom"><a href="logout.php" class="nav-item" style="padding:8px 0;border:none"><span class="icon">🚪</span> Déconnexion</a></div>
  </aside>
  <main class="admin-main">
    <div class="admin-topbar"><span class="topbar-title">Paramètres du site</span></div>
    <div class="admin-content">
      <?php if($success): ?><div class="alert alert-success" style="margin-bottom:20px">✅ <?= $success ?></div><?php endif; ?>
      <form method="POST">
        <!-- Identité -->
        <div class="card" style="margin-bottom:20px">
          <div class="card-header"><span class="card-title">🏦 Identité de l'organisation</span></div>
          <div class="settings-grid">
            <div class="form-group"><label class="form-label">Nom du site</label><input type="text" name="site_name" class="form-control" value="<?= htmlspecialchars($settings['site_name']??'FINOVA') ?>"></div>
            <div class="form-group"><label class="form-label">Couleur principale (HEX)</label><div style="display:flex;gap:8px;align-items:center"><input type="color" id="hero_color_pick" value="<?= $settings['hero_color']??'#0A2647' ?>" oninput="document.getElementById('hero_color').value=this.value" style="width:44px;height:38px;border:none;cursor:pointer"><input type="text" id="hero_color" name="hero_color" class="form-control" value="<?= htmlspecialchars($settings['hero_color']??'#0A2647') ?>"></div></div>
            <div class="form-group"><label class="form-label">Couleur accent (or)</label><div style="display:flex;gap:8px;align-items:center"><input type="color" id="accent_color_pick" value="<?= $settings['accent_color']??'#C9A84C' ?>" oninput="document.getElementById('accent_color').value=this.value" style="width:44px;height:38px;border:none;cursor:pointer"><input type="text" id="accent_color" name="accent_color" class="form-control" value="<?= htmlspecialchars($settings['accent_color']??'#C9A84C') ?>"></div></div>
          </div>
        </div>
        <!-- Taglines multilingues -->
        <div class="card" style="margin-bottom:20px">
          <div class="card-header"><span class="card-title">🌍 Slogans (multilingue)</span></div>
          <div class="settings-grid">
            <?php foreach(['fr'=>'🇫🇷 Français','en'=>'🇬🇧 English','es'=>'🇪🇸 Español','ar'=>'🇸🇦 العربية','pt'=>'🇵🇹 Português','zh'=>'🇨🇳 中文','ru'=>'🇷🇺 Русский'] as $l=>$label): ?>
            <div class="form-group"><label class="form-label"><?= $label ?></label><input type="text" name="site_tagline_<?= $l ?>" class="form-control" value="<?= htmlspecialchars($settings['site_tagline_'.$l]??'') ?>"></div>
            <?php endforeach; ?>
          </div>
        </div>
        <!-- Contact -->
        <div class="card" style="margin-bottom:20px">
          <div class="card-header"><span class="card-title">📞 Coordonnées de contact</span></div>
          <div class="settings-grid">
            <div class="form-group"><label class="form-label">Email de contact</label><input type="email" name="contact_email" class="form-control" value="<?= htmlspecialchars($settings['contact_email']??'') ?>"></div>
            <div class="form-group"><label class="form-label">Téléphone</label><input type="tel" name="contact_phone" class="form-control" value="<?= htmlspecialchars($settings['contact_phone']??'') ?>"></div>
            <div class="form-group" style="grid-column:1/-1"><label class="form-label">Adresse physique</label><textarea name="contact_address" class="form-control" rows="2"><?= htmlspecialchars($settings['contact_address']??'') ?></textarea></div>
          </div>
        </div>
        <!-- Compteurs hero -->
        <div class="card" style="margin-bottom:24px">
          <div class="card-header"><span class="card-title">📊 Compteurs (section héros)</span></div>
          <div class="settings-grid">
            <div class="form-group"><label class="form-label">Total financé (millions USD — chiffre uniquement)</label><input type="number" name="total_funded" class="form-control" value="<?= htmlspecialchars($settings['total_funded']??'0') ?>"></div>
            <div class="form-group"><label class="form-label">Nombre d'organisations soutenues</label><input type="number" name="total_organizations" class="form-control" value="<?= htmlspecialchars($settings['total_organizations']??'0') ?>"></div>
            <div class="form-group"><label class="form-label">Nombre de pays</label><input type="number" name="total_countries" class="form-control" value="<?= htmlspecialchars($settings['total_countries']??'0') ?>"></div>
          </div>
        </div>
        <button type="submit" class="btn btn-primary">💾 Enregistrer les paramètres</button>
      </form>
    </div>
  </main>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>
