<?php
require_once '../includes/config.php';
if (empty($_SESSION['admin_id'])) { header('Location: login.php'); exit; }
if (isset($_SESSION['last_activity']) && (time()-$_SESSION['last_activity'])>SESSION_TIMEOUT) { session_destroy(); header('Location: login.php?timeout=1'); exit; }
$_SESSION['last_activity'] = time();

$db = getDB();
$adm = $db->prepare("SELECT * FROM admins WHERE id=?"); $adm->execute([$_SESSION['admin_id']]); $adm = $adm->fetch();

$success = $error = '';

// Toggle active
if (isset($_GET['toggle'])) {
    $db->prepare("UPDATE programs SET is_active = 1 - is_active WHERE id=?")->execute([(int)$_GET['toggle']]);
    header('Location: programs.php'); exit;
}

// Add/edit program
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $pid = (int)($_POST['program_id'] ?? 0);
    $slug = trim($_POST['slug'] ?? '');
    $max = (float)$_POST['max_amount'];
    $min = (float)$_POST['min_amount'];
    $cur = $_POST['currency'] ?? 'USD';
    $sector = trim($_POST['target_sector'] ?? '');
    $deadline = !empty($_POST['deadline']) ? $_POST['deadline'] : null;

    if ($pid) {
        $db->prepare("UPDATE programs SET max_amount=?, min_amount=?, currency=?, target_sector=?, deadline=? WHERE id=?")->execute([$max,$min,$cur,$sector,$deadline,$pid]);
    } else {
        if (!$slug) $slug = strtolower(str_replace(' ','-',$sector)).'-'.date('Y');
        $db->prepare("INSERT INTO programs (slug,max_amount,min_amount,currency,target_sector,deadline) VALUES (?,?,?,?,?,?)")->execute([$slug,$max,$min,$cur,$sector,$deadline]);
        $pid = $db->lastInsertId();
    }
    // Save translations
    foreach(['fr','en','es','ar','pt','zh','ru'] as $l) {
        $title = trim($_POST["title_$l"] ?? '');
        $desc  = trim($_POST["description_$l"] ?? '');
        if ($title) {
            $db->prepare("INSERT INTO program_translations (program_id,lang,title,description,objectives,eligibility_criteria) VALUES (?,?,?,?,?,?) ON DUPLICATE KEY UPDATE title=VALUES(title),description=VALUES(description),objectives=VALUES(objectives),eligibility_criteria=VALUES(eligibility_criteria)")
               ->execute([$pid,$l,$title,$desc, trim($_POST["objectives_$l"]??''), trim($_POST["eligibility_$l"]??'')]);
        }
    }
    $success = 'Programme enregistré.';
}

$programs = $db->query("
    SELECT p.*, 
    (SELECT title 
     FROM program_translations 
     WHERE program_id = p.id 
     AND lang='fr' 
     LIMIT 1) as title_fr 
    FROM programs p 
    ORDER BY p.id DESC
")->fetchAll();
$editProg = null;
$editTrans = [];
if (isset($_GET['edit'])) {
    $s = $db->prepare("SELECT * FROM programs WHERE id=?"); $s->execute([(int)$_GET['edit']]); $editProg = $s->fetch();
    $s2 = $db->prepare("SELECT * FROM program_translations WHERE program_id=?"); $s2->execute([(int)$_GET['edit']]); 
    foreach($s2->fetchAll() as $t) $editTrans[$t['lang']] = $t;
}
$pendingCount = $db->query("SELECT COUNT(*) FROM applications WHERE status='pending'")->fetchColumn();
$unreadMsgs = $db->query("SELECT COUNT(*) FROM contacts WHERE is_read=0")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Programmes — Admin FINOVA</title>
<link rel="stylesheet" href="../assets/css/main.css">
<style>
.admin-layout{display:flex;min-height:100vh}.sidebar{width:240px;background:var(--navy);flex-shrink:0;display:flex;flex-direction:column;position:fixed;top:0;bottom:0;left:0;z-index:100}.sidebar-brand{padding:24px 20px;border-bottom:1px solid rgba(255,255,255,.08)}.sidebar-brand .brand{font-family:var(--font-serif);font-size:20px;color:var(--white);font-weight:600}.sidebar-brand .role{font-size:11px;color:var(--gold);text-transform:uppercase;letter-spacing:.08em;margin-top:2px}.sidebar-nav{flex:1;padding:16px 0;overflow-y:auto}.nav-item{display:flex;align-items:center;gap:10px;padding:10px 20px;color:rgba(255,255,255,.65);font-size:13.5px;cursor:pointer;text-decoration:none;transition:var(--transition);border-left:3px solid transparent}.nav-item:hover{color:var(--white);background:rgba(255,255,255,.06)}.nav-item.active{color:var(--white);background:rgba(255,255,255,.1);border-left-color:var(--gold)}.nav-item .icon{width:18px;text-align:center}.nav-sep{height:1px;background:rgba(255,255,255,.06);margin:8px 20px}.nav-badge{margin-left:auto;background:var(--gold);color:var(--navy);font-size:10px;padding:1px 6px;border-radius:10px;font-weight:600}.sidebar-bottom{padding:16px 20px;border-top:1px solid rgba(255,255,255,.08)}.admin-main{margin-left:240px;flex:1;background:var(--gray-50);min-height:100vh}.admin-topbar{background:var(--white);border-bottom:1px solid var(--gray-200);padding:0 28px;height:60px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:50}.topbar-title{font-weight:500;color:var(--navy);font-size:16px}.admin-content{padding:28px}.card{background:var(--white);border-radius:var(--radius-lg);border:1px solid var(--gray-200);overflow:hidden}.card-header{padding:16px 20px;border-bottom:1px solid var(--gray-200);display:flex;align-items:center;justify-content:space-between}.card-title{font-weight:500;color:var(--navy);font-size:14px}.table{width:100%;border-collapse:collapse}.table th{padding:10px 14px;text-align:left;font-size:11px;font-weight:500;color:var(--gray-400);text-transform:uppercase;letter-spacing:.06em;border-bottom:1px solid var(--gray-200);background:var(--gray-50)}.table td{padding:12px 14px;font-size:13.5px;border-bottom:1px solid var(--gray-100);color:var(--gray-700)}.table tr:last-child td{border-bottom:none}
.lang-tabs{display:flex;gap:4px;flex-wrap:wrap;margin-bottom:16px}.lang-tab{padding:6px 12px;border-radius:var(--radius);font-size:12px;cursor:pointer;border:1.5px solid var(--gray-200);background:var(--white);color:var(--gray-600)}.lang-tab.active,.lang-tab:hover{background:var(--navy);color:var(--white);border-color:var(--navy)}.lang-panel{display:none}.lang-panel.active{display:block}
</style>
</head>
<body>
<div class="admin-layout">
  <aside class="sidebar">
    <div class="sidebar-brand"><div class="brand">FINOVA Admin</div><div class="role"><?= htmlspecialchars($adm['role']??'') ?></div></div>
    <nav class="sidebar-nav">
      <a class="nav-item" href="index.php"><span class="icon">📊</span> Tableau de bord</a>
      <a class="nav-item" href="applications.php"><span class="icon">📋</span> Candidatures <span class="nav-badge"><?= $pendingCount ?></span></a>
      <a class="nav-item active" href="programs.php"><span class="icon">🎯</span> Programmes</a>
      <div class="nav-sep"></div>
      <a class="nav-item" href="messages.php"><span class="icon">💬</span> Messages <?php if($unreadMsgs>0): ?><span class="nav-badge"><?= $unreadMsgs ?></span><?php endif; ?></a>
      <a class="nav-item" href="faq.php"><span class="icon">❓</span> FAQ</a>
      <div class="nav-sep"></div>
      <a class="nav-item" href="settings.php"><span class="icon">⚙️</span> Paramètres</a>
      <a class="nav-item" href="admins.php"><span class="icon">👥</span> Administrateurs</a>
    </nav>
    <div class="sidebar-bottom"><a href="logout.php" class="nav-item" style="padding:8px 0;border:none"><span class="icon">🚪</span> Déconnexion</a></div>
  </aside>
  <main class="admin-main">
    <div class="admin-topbar">
      <span class="topbar-title">Gestion des programmes</span>
      <a href="programs.php?new=1" class="btn btn-primary btn-sm">+ Nouveau programme</a>
    </div>
    <div class="admin-content">
      <?php if($success): ?><div class="alert alert-success" style="margin-bottom:20px">✅ <?= $success ?></div><?php endif; ?>

      <!-- Form add/edit -->
      <?php if(isset($_GET['new']) || $editProg): ?>
      <div class="card" style="margin-bottom:24px">
        <div class="card-header"><span class="card-title"><?= $editProg ? 'Modifier le programme' : 'Nouveau programme' ?></span></div>
        <form method="POST" style="padding:24px">
          <?php if($editProg): ?><input type="hidden" name="program_id" value="<?= $editProg['id'] ?>"><?php endif; ?>
          <div style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:14px;margin-bottom:16px">
            <div class="form-group"><label class="form-label">Montant min (USD)</label><input type="number" name="min_amount" class="form-control" value="<?= $editProg['min_amount']??1000 ?>" required></div>
            <div class="form-group"><label class="form-label">Montant max (USD)</label><input type="number" name="max_amount" class="form-control" value="<?= $editProg['max_amount']??500000 ?>" required></div>
            <div class="form-group"><label class="form-label">Devise</label><input type="text" name="currency" class="form-control" value="<?= htmlspecialchars($editProg['currency']??'USD') ?>"></div>
            <div class="form-group"><label class="form-label">Date limite</label><input type="date" name="deadline" class="form-control" value="<?= $editProg['deadline']??'' ?>"></div>
            <div class="form-group" style="grid-column:1/-1"><label class="form-label">Secteur cible</label><input type="text" name="target_sector" class="form-control" value="<?= htmlspecialchars($editProg['target_sector']??'') ?>" placeholder="Ex: Social, Éducation, Santé"></div>
          </div>
          <!-- Lang tabs -->
          <div style="margin-top:16px">
            <div style="font-size:13px;font-weight:500;color:var(--navy);margin-bottom:10px">Traductions</div>
            <div class="lang-tabs">
              <?php foreach(['fr'=>'🇫🇷 FR','en'=>'🇬🇧 EN','es'=>'🇪🇸 ES','ar'=>'🇸🇦 AR','pt'=>'🇵🇹 PT','zh'=>'🇨🇳 ZH','ru'=>'🇷🇺 RU'] as $lcode=>$lname): ?>
              <button type="button" class="lang-tab <?= $lcode==='fr'?'active':'' ?>" onclick="showLang('<?= $lcode ?>')"><?= $lname ?></button>
              <?php endforeach; ?>
            </div>
            <?php foreach(['fr','en','es','ar','pt','zh','ru'] as $lcode): ?>
            <div class="lang-panel <?= $lcode==='fr'?'active':'' ?>" id="lang-<?= $lcode ?>">
              <div style="display:grid;grid-template-columns:1fr;gap:12px">
                <div class="form-group"><label class="form-label">Titre (<?= strtoupper($lcode) ?>)</label><input type="text" name="title_<?= $lcode ?>" class="form-control" value="<?= htmlspecialchars($editTrans[$lcode]['title']??'') ?>"></div>
                <div class="form-group"><label class="form-label">Description</label><textarea name="description_<?= $lcode ?>" class="form-control" rows="3"><?= htmlspecialchars($editTrans[$lcode]['description']??'') ?></textarea></div>
                <div class="form-group"><label class="form-label">Objectifs</label><textarea name="objectives_<?= $lcode ?>" class="form-control" rows="2"><?= htmlspecialchars($editTrans[$lcode]['objectives']??'') ?></textarea></div>
                <div class="form-group"><label class="form-label">Critères d'éligibilité</label><textarea name="eligibility_<?= $lcode ?>" class="form-control" rows="2"><?= htmlspecialchars($editTrans[$lcode]['eligibility_criteria']??'') ?></textarea></div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <div style="margin-top:20px;display:flex;gap:10px">
            <button type="submit" class="btn btn-primary">💾 Enregistrer</button>
            <a href="programs.php" class="btn btn-outline">Annuler</a>
          </div>
        </form>
      </div>
      <?php endif; ?>

      <!-- Programs list -->
      <div class="card">
        <div style="overflow-x:auto">
          <table class="table">
            <thead><tr><th>ID</th><th>Titre (FR)</th><th>Secteur</th><th>Min</th><th>Max</th><th>Deadline</th><th>Statut</th><th>Actions</th></tr></thead>
            <tbody>
              <?php foreach($programs as $prog): ?>
              <tr>
                <td><?= $prog['id'] ?></td>
                <td style="font-weight:500"><?= htmlspecialchars($prog['title_fr']??'—') ?></td>
                <td><?= htmlspecialchars(substr($prog['target_sector']??'',0,30)) ?></td>
                <td>$<?= number_format($prog['min_amount'],0,',',' ') ?></td>
                <td>$<?= number_format($prog['max_amount'],0,',',' ') ?></td>
                <td style="font-size:12px"><?= $prog['deadline'] ? date('d/m/Y',strtotime($prog['deadline'])) : '—' ?></td>
                <td><span class="badge badge-status <?= $prog['is_active']?'badge-approved':'badge-rejected' ?>"><?= $prog['is_active']?'Actif':'Inactif' ?></span></td>
                <td style="display:flex;gap:8px">
                  <a href="?edit=<?= $prog['id'] ?>" style="font-size:12px;color:var(--navy);text-decoration:underline">Modifier</a>
                  <a href="?toggle=<?= $prog['id'] ?>" style="font-size:12px;color:var(--warning);text-decoration:underline" onclick="return confirm('Changer le statut ?')"><?= $prog['is_active']?'Désactiver':'Activer' ?></a>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </main>
</div>
<script>
function showLang(l) {
  document.querySelectorAll('.lang-panel').forEach(p=>p.classList.remove('active'));
  document.querySelectorAll('.lang-tab').forEach(t=>t.classList.remove('active'));
  document.getElementById('lang-'+l).classList.add('active');
  event.target.classList.add('active');
}
</script>
</body>
</html>
