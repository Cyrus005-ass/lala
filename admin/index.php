<?php
require_once '../includes/config.php';

// ---- AUTH CHECK ----
function requireAdmin() {
    if (empty($_SESSION['admin_id'])) {
        header('Location: login.php');
        exit;
    }
    // Session timeout
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
        session_destroy();
        header('Location: login.php?timeout=1');
        exit;
    }
    $_SESSION['last_activity'] = time();
}

function getCurrentAdmin(): array {
    if (empty($_SESSION['admin_id'])) return [];
    static $admin = null;
    if (!$admin) {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM admins WHERE id = ?");
        $stmt->execute([$_SESSION['admin_id']]);
        $admin = $stmt->fetch() ?: [];
    }
    return $admin;
}

function canAccess(string $role): bool {
    $admin = getCurrentAdmin();
    $levels = ['reviewer'=>1,'admin'=>2,'superadmin'=>3];
    return ($levels[$admin['role']??''] ?? 0) >= ($levels[$role] ?? 99);
}

requireAdmin();
$admin = getCurrentAdmin();
$db = getDB();

// Stats
$totalApps    = $db->query("SELECT COUNT(*) FROM applications")->fetchColumn();
$pendingApps  = $db->query("SELECT COUNT(*) FROM applications WHERE status='pending'")->fetchColumn();
$approvedApps = $db->query("SELECT COUNT(*) FROM applications WHERE status='approved'")->fetchColumn();
$disbursed    = $db->query("SELECT COUNT(*) FROM applications WHERE status='disbursed'")->fetchColumn();
$totalAmountQ = $db->query("SELECT SUM(disbursement_amount) FROM applications WHERE status='disbursed'")->fetchColumn();
$totalAmount  = $totalAmountQ ? number_format((float)$totalAmountQ, 0, ',', ' ') : '0';
try {
    $unreadMsgs = $db->query("SELECT COUNT(*) FROM contacts WHERE is_read=0")->fetchColumn();
} catch (Exception $e) {
    $unreadMsgs = 0;
}

// Recent applications
$recentApps = $db->query("SELECT a.*, pt.title as prog_title FROM applications a LEFT JOIN programs p ON a.program_id=p.id LEFT JOIN program_translations pt ON p.id=pt.program_id AND pt.lang='fr' ORDER BY a.submitted_at DESC LIMIT 8")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Administration — FINOVA</title>
<link rel="stylesheet" href="../assets/css/main.css">
<style>
.admin-layout { display:flex; min-height:100vh; }
.sidebar { width:240px; background:var(--navy); flex-shrink:0; display:flex; flex-direction:column; position:fixed; top:0; bottom:0; left:0; z-index:100; }
.sidebar-brand { padding:24px 20px; border-bottom:1px solid rgba(255,255,255,.08); }
.sidebar-brand .brand { font-family:var(--font-serif); font-size:20px; color:var(--white); font-weight:600; }
.sidebar-brand .role { font-size:11px; color:var(--gold); text-transform:uppercase; letter-spacing:.08em; margin-top:2px; }
.sidebar-nav { flex:1; padding:16px 0; overflow-y:auto; }
.nav-item { display:flex; align-items:center; gap:10px; padding:10px 20px; color:rgba(255,255,255,.65); font-size:13.5px; cursor:pointer; text-decoration:none; transition:var(--transition); border-left:3px solid transparent; }
.nav-item:hover { color:var(--white); background:rgba(255,255,255,.06); }
.nav-item.active { color:var(--white); background:rgba(255,255,255,.1); border-left-color:var(--gold); }
.nav-item .icon { width:18px; text-align:center; }
.nav-sep { height:1px; background:rgba(255,255,255,.06); margin:8px 20px; }
.nav-badge { margin-left:auto; background:var(--gold); color:var(--navy); font-size:10px; padding:1px 6px; border-radius:10px; font-weight:600; }
.sidebar-bottom { padding:16px 20px; border-top:1px solid rgba(255,255,255,.08); }
.admin-main { margin-left:240px; flex:1; background:var(--gray-50); min-height:100vh; }
.admin-topbar { background:var(--white); border-bottom:1px solid var(--gray-200); padding:0 28px; height:60px; display:flex; align-items:center; justify-content:space-between; position:sticky; top:0; z-index:50; }
.topbar-title { font-weight:500; color:var(--navy); font-size:16px; }
.topbar-right { display:flex; align-items:center; gap:14px; }
.topbar-admin { font-size:13px; color:var(--gray-600); }
.admin-content { padding:28px; }
.stats-grid { display:grid; grid-template-columns:repeat(5,1fr); gap:16px; margin-bottom:28px; }
.stat-card { background:var(--white); border-radius:var(--radius); padding:20px; border:1px solid var(--gray-200); }
.stat-card-value { font-family:var(--font-serif); font-size:28px; font-weight:600; line-height:1; }
.stat-card-label { font-size:12px; color:var(--gray-400); margin-top:4px; }
.stat-card-icon { font-size:20px; margin-bottom:8px; }
.card { background:var(--white); border-radius:var(--radius-lg); border:1px solid var(--gray-200); overflow:hidden; }
.card-header { padding:16px 20px; border-bottom:1px solid var(--gray-200); display:flex; align-items:center; justify-content:space-between; }
.card-title { font-weight:500; color:var(--navy); font-size:14px; }
.table { width:100%; border-collapse:collapse; }
.table th { padding:10px 14px; text-align:left; font-size:11px; font-weight:500; color:var(--gray-400); text-transform:uppercase; letter-spacing:.06em; border-bottom:1px solid var(--gray-200); background:var(--gray-50); }
.table td { padding:12px 14px; font-size:13.5px; border-bottom:1px solid var(--gray-100); color:var(--gray-700); }
.table tr:last-child td { border-bottom:none; }
.table tr:hover td { background:var(--gray-50); }
.table .ref { font-family:monospace; font-size:12px; color:var(--navy); font-weight:600; }
.action-link { color:var(--navy); font-size:12px; font-weight:500; text-decoration:underline; cursor:pointer; }
.form-row { display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px; }
.form-row.cols-1 { grid-template-columns:1fr; }
.form-row.cols-3 { grid-template-columns:1fr 1fr 1fr; }
</style>
</head>
<body>
<div class="admin-layout">
  <!-- Sidebar -->
  <aside class="sidebar">
    <div class="sidebar-brand">
      <div class="brand">FINOVA Admin</div>
      <div class="role"><?= htmlspecialchars($admin['role'] ?? '') ?></div>
    </div>
    <nav class="sidebar-nav">
      <a class="nav-item active" href="index.php"><span class="icon">📊</span> Tableau de bord</a>
      <a class="nav-item" href="applications.php"><span class="icon">📋</span> Candidatures <span class="nav-badge"><?= $pendingApps ?></span></a>
      <a class="nav-item" href="programs.php"><span class="icon">🎯</span> Programmes</a>
      <div class="nav-sep"></div>
      <a class="nav-item" href="messages.php"><span class="icon">💬</span> Messages <?php if($unreadMsgs > 0): ?><span class="nav-badge"><?= $unreadMsgs ?></span><?php endif; ?></a>
      <a class="nav-item" href="faq.php"><span class="icon">❓</span> FAQ</a>
      <?php if(canAccess('admin')): ?>
      <div class="nav-sep"></div>
      <a class="nav-item" href="settings.php"><span class="icon">⚙️</span> Paramètres</a>
      <?php if(canAccess('superadmin')): ?>
      <a class="nav-item" href="admins.php"><span class="icon">👥</span> Administrateurs</a>
      <?php endif; ?>
      <?php endif; ?>
    </nav>
    <div class="sidebar-bottom">
      <a href="logout.php" class="nav-item" style="padding:8px 0;border:none"><span class="icon">🚪</span> Déconnexion</a>
      <a href="../index.php" target="_blank" class="nav-item" style="padding:8px 0;border:none;font-size:12px"><span class="icon">🌐</span> Voir le site</a>
    </div>
  </aside>

  <!-- Main -->
  <main class="admin-main">
    <div class="admin-topbar">
      <span class="topbar-title">Tableau de bord</span>
      <div class="topbar-right">
        <span class="topbar-admin">👤 <?= htmlspecialchars($admin['full_name'] ?? $admin['username']) ?></span>
        <span style="font-size:12px;color:var(--gray-400)"><?= date('d/m/Y H:i') ?></span>
      </div>
    </div>
    <div class="admin-content">

      <!-- Stats -->
      <div class="stats-grid">
        <div class="stat-card"><div class="stat-card-icon">📋</div><div class="stat-card-value"><?= $totalApps ?></div><div class="stat-card-label">Total candidatures</div></div>
        <div class="stat-card" style="border-top:3px solid var(--warning)"><div class="stat-card-icon">⏳</div><div class="stat-card-value" style="color:var(--warning)"><?= $pendingApps ?></div><div class="stat-card-label">En attente</div></div>
        <div class="stat-card" style="border-top:3px solid var(--info)"><div class="stat-card-icon">🔍</div><div class="stat-card-value" style="color:var(--info)"><?= $db->query("SELECT COUNT(*) FROM applications WHERE status='under_review'")->fetchColumn() ?></div><div class="stat-card-label">En cours d'examen</div></div>
        <div class="stat-card" style="border-top:3px solid var(--success)"><div class="stat-card-icon">✅</div><div class="stat-card-value" style="color:var(--success)"><?= $approvedApps ?></div><div class="stat-card-label">Approuvées</div></div>
        <div class="stat-card" style="border-top:3px solid var(--gold)"><div class="stat-card-icon">💰</div><div class="stat-card-value" style="color:var(--gold);font-size:22px">$<?= $totalAmount ?></div><div class="stat-card-label">Décaissés (<?= $disbursed ?> dossiers)</div></div>
      </div>

      <!-- Recent applications -->
      <div class="card">
        <div class="card-header">
          <span class="card-title">Candidatures récentes</span>
          <a href="applications.php" class="btn btn-outline btn-sm">Voir tout</a>
        </div>
        <div style="overflow-x:auto">
          <table class="table">
            <thead>
              <tr><th>Référence</th><th>Organisation</th><th>Programme</th><th>Pays</th><th>Montant</th><th>Statut</th><th>Date</th><th></th></tr>
            </thead>
            <tbody>
              <?php foreach($recentApps as $app): ?>
              <tr>
                <td><span class="ref"><?= htmlspecialchars($app['reference']) ?></span></td>
                <td><?= htmlspecialchars(substr($app['org_name'],0,30)) ?></td>
                <td><?= htmlspecialchars(substr($app['prog_title']??'—',0,24)) ?></td>
                <td><?= htmlspecialchars($app['org_country']) ?></td>
                <td>$<?= number_format($app['requested_amount'],0,',',' ') ?></td>
                <td><span class="badge badge-status badge-<?= $app['status'] ?>"><?= $app['status'] ?></span></td>
                <td><?= date('d/m/Y', strtotime($app['submitted_at'])) ?></td>
                <td><a href="application_detail.php?id=<?= $app['id'] ?>" class="action-link">Voir →</a></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <?php if($unreadMsgs > 0): ?>
      <div class="alert alert-info" style="margin-top:20px">
        💬 Vous avez <strong><?= $unreadMsgs ?></strong> message(s) non lu(s). <a href="messages.php" style="font-weight:600;text-decoration:underline">Consulter →</a>
      </div>
      <?php endif; ?>
    </div>
  </main>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>
