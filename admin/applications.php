<?php
require_once '../includes/config.php';
if (empty($_SESSION['admin_id'])) { header('Location: login.php'); exit; }
if (isset($_SESSION['last_activity']) && (time()-$_SESSION['last_activity'])>SESSION_TIMEOUT) { session_destroy(); header('Location: login.php?timeout=1'); exit; }
$_SESSION['last_activity'] = time();

$db = getDB();
$adm = $db->prepare("SELECT * FROM admins WHERE id=?"); $adm->execute([$_SESSION['admin_id']]); $adm = $adm->fetch();

function canAccessRole(string $role): bool {
    global $adm;
    $levels = ['reviewer'=>1,'admin'=>2,'superadmin'=>3];
    return ($levels[$adm['role']??''] ?? 0) >= ($levels[$role] ?? 99);
}

$statusFilter  = $_GET['status'] ?? '';
$programFilter = (int)($_GET['program'] ?? 0);
$searchQuery   = trim($_GET['q'] ?? '');
$page          = max(1, (int)($_GET['page'] ?? 1));
$perPage       = 20;
$offset        = ($page - 1) * $perPage;

$where  = ['1=1'];
$params = [];
if ($statusFilter) { $where[] = 'a.status = ?'; $params[] = $statusFilter; }
if ($programFilter) { $where[] = 'a.program_id = ?'; $params[] = $programFilter; }
if ($searchQuery) {
    $where[] = '(a.reference LIKE ? OR a.org_name LIKE ? OR a.contact_email LIKE ? OR a.project_title LIKE ?)';
    $s = "%$searchQuery%";
    array_push($params, $s, $s, $s, $s);
}

$whereStr   = implode(' AND ', $where);
$totalStmt  = $db->prepare("SELECT COUNT(*) FROM applications a WHERE $whereStr");
$totalStmt->execute($params);
$totalCount = $totalStmt->fetchColumn();
$totalPages = ceil($totalCount / $perPage);

$stmt = $db->prepare("SELECT a.*, pt.title as prog_title FROM applications a LEFT JOIN programs p ON a.program_id=p.id LEFT JOIN program_translations pt ON p.id=pt.program_id AND pt.lang='fr' WHERE $whereStr ORDER BY a.submitted_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$applications = $stmt->fetchAll();

$programs    = $db->query("SELECT p.id, pt.title FROM programs p LEFT JOIN program_translations pt ON p.id=pt.program_id AND pt.lang='fr'")->fetchAll();
$statusList  = ['pending'=>'En attente','under_review'=>'En examen','approved'=>'Approuvée','rejected'=>'Rejetée','waitlisted'=>'Liste attente','disbursed'=>'Décaissé'];
$pendingCount = $db->query("SELECT COUNT(*) FROM applications WHERE status='pending'")->fetchColumn();
$unreadMsgs  = $db->query("SELECT COUNT(*) FROM contacts WHERE is_read=0")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Candidatures — Admin FINOVA</title>
<link rel="stylesheet" href="../assets/css/main.css">
<style>
.admin-layout{display:flex;min-height:100vh}.sidebar{width:240px;background:var(--navy);flex-shrink:0;display:flex;flex-direction:column;position:fixed;top:0;bottom:0;left:0;z-index:100}.sidebar-brand{padding:24px 20px;border-bottom:1px solid rgba(255,255,255,.08)}.sidebar-brand .brand{font-family:var(--font-serif);font-size:20px;color:var(--white);font-weight:600}.sidebar-brand .role{font-size:11px;color:var(--gold);text-transform:uppercase;letter-spacing:.08em;margin-top:2px}.sidebar-nav{flex:1;padding:16px 0;overflow-y:auto}.nav-item{display:flex;align-items:center;gap:10px;padding:10px 20px;color:rgba(255,255,255,.65);font-size:13.5px;cursor:pointer;text-decoration:none;transition:var(--transition);border-left:3px solid transparent}.nav-item:hover{color:var(--white);background:rgba(255,255,255,.06)}.nav-item.active{color:var(--white);background:rgba(255,255,255,.1);border-left-color:var(--gold)}.nav-item .icon{width:18px;text-align:center}.nav-sep{height:1px;background:rgba(255,255,255,.06);margin:8px 20px}.nav-badge{margin-left:auto;background:var(--gold);color:var(--navy);font-size:10px;padding:1px 6px;border-radius:10px;font-weight:600}.sidebar-bottom{padding:16px 20px;border-top:1px solid rgba(255,255,255,.08)}.admin-main{margin-left:240px;flex:1;background:var(--gray-50);min-height:100vh}.admin-topbar{background:var(--white);border-bottom:1px solid var(--gray-200);padding:0 28px;height:60px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:50}.topbar-title{font-weight:500;color:var(--navy);font-size:16px}.admin-content{padding:28px}.card{background:var(--white);border-radius:var(--radius-lg);border:1px solid var(--gray-200);overflow:hidden}.table{width:100%;border-collapse:collapse}.table th{padding:10px 14px;text-align:left;font-size:11px;font-weight:500;color:var(--gray-400);text-transform:uppercase;letter-spacing:.06em;border-bottom:1px solid var(--gray-200);background:var(--gray-50)}.table td{padding:12px 14px;font-size:13.5px;border-bottom:1px solid var(--gray-100);color:var(--gray-700)}.table tr:last-child td{border-bottom:none}.table tr:hover td{background:var(--gray-50)}.ref{font-family:monospace;font-size:12px;color:var(--navy);font-weight:600}.filters{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:20px}.filter-select{padding:8px 12px;border:1.5px solid var(--gray-200);border-radius:var(--radius);font-size:13px;outline:none;background:var(--white)}.search-input{padding:8px 14px;border:1.5px solid var(--gray-200);border-radius:var(--radius);font-size:13px;outline:none;min-width:240px}.pagination{display:flex;align-items:center;gap:6px;margin-top:20px;justify-content:center}.page-btn{width:34px;height:34px;display:flex;align-items:center;justify-content:center;border-radius:var(--radius);font-size:13px;text-decoration:none;color:var(--gray-600);border:1px solid var(--gray-200);background:var(--white)}.page-btn.active{background:var(--navy);color:var(--white);border-color:var(--navy)}
</style>
</head>
<body>
<div class="admin-layout">
  <aside class="sidebar">
    <div class="sidebar-brand"><div class="brand">FINOVA Admin</div><div class="role"><?= htmlspecialchars($adm['role']??'') ?></div></div>
    <nav class="sidebar-nav">
      <a class="nav-item" href="index.php"><span class="icon">📊</span> Tableau de bord</a>
      <a class="nav-item active" href="applications.php"><span class="icon">📋</span> Candidatures <span class="nav-badge"><?= $pendingCount ?></span></a>
      <a class="nav-item" href="programs.php"><span class="icon">🎯</span> Programmes</a>
      <div class="nav-sep"></div>
      <a class="nav-item" href="messages.php"><span class="icon">💬</span> Messages <?php if($unreadMsgs>0): ?><span class="nav-badge"><?= $unreadMsgs ?></span><?php endif; ?></a>
      <a class="nav-item" href="faq.php"><span class="icon">❓</span> FAQ</a>
      <div class="nav-sep"></div>
      <a class="nav-item" href="settings.php"><span class="icon">⚙️</span> Paramètres</a>
      <a class="nav-item" href="admins.php"><span class="icon">👥</span> Administrateurs</a>
    </nav>
    <div class="sidebar-bottom"><a href="logout.php" class="nav-item" style="padding:8px 0;border:none"><span class="icon">🚪</span> Déconnexion</a><a href="../index.php" target="_blank" class="nav-item" style="padding:8px 0;border:none;font-size:12px"><span class="icon">🌐</span> Voir le site</a></div>
  </aside>
  <main class="admin-main">
    <div class="admin-topbar"><span class="topbar-title">Candidatures (<?= $totalCount ?>)</span><div style="display:flex;align-items:center;gap:14px"><span style="font-size:13px;color:var(--gray-600)">👤 <?= htmlspecialchars($adm['full_name']??$adm['username']) ?></span></div></div>
    <div class="admin-content">
      <form method="GET" class="filters">
        <input type="text" name="q" class="search-input" placeholder="Référence, organisation, email..." value="<?= htmlspecialchars($searchQuery) ?>">
        <select name="status" class="filter-select" onchange="this.form.submit()">
          <option value="">Tous les statuts</option>
          <?php foreach($statusList as $k=>$v): ?><option value="<?= $k ?>" <?= $statusFilter===$k?'selected':'' ?>><?= $v ?></option><?php endforeach; ?>
        </select>
        <select name="program" class="filter-select" onchange="this.form.submit()">
          <option value="">Tous les programmes</option>
          <?php foreach($programs as $prog): ?><option value="<?= $prog['id'] ?>" <?= $programFilter===$prog['id']?'selected':'' ?>><?= htmlspecialchars($prog['title']??'#'.$prog['id']) ?></option><?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-primary btn-sm">Filtrer</button>
        <a href="applications.php" class="btn btn-outline btn-sm">Réinitialiser</a>
      </form>
      <div class="card">
        <div style="overflow-x:auto">
          <table class="table">
            <thead><tr><th>Référence</th><th>Organisation</th><th>Programme</th><th>Pays</th><th>Montant</th><th>Statut</th><th>Date</th><th></th></tr></thead>
            <tbody>
              <?php if($applications): foreach($applications as $app): ?>
              <tr>
                <td><span class="ref"><?= htmlspecialchars($app['reference']) ?></span></td>
                <td><div style="font-weight:500"><?= htmlspecialchars(substr($app['org_name'],0,30)) ?></div><div style="font-size:11px;color:var(--gray-400)"><?= htmlspecialchars($app['contact_email']) ?></div></td>
                <td><?= htmlspecialchars(substr($app['prog_title']??'—',0,22)) ?></td>
                <td><?= htmlspecialchars($app['org_country']) ?></td>
                <td style="font-weight:500">$<?= number_format($app['requested_amount'],0,',',' ') ?></td>
                <td><span class="badge badge-status badge-<?= $app['status'] ?>"><?= $statusList[$app['status']]??$app['status'] ?></span></td>
                <td style="font-size:12px"><?= date('d/m/Y', strtotime($app['submitted_at'])) ?></td>
                <td><a href="application_detail.php?id=<?= $app['id'] ?>" style="color:var(--navy);font-size:12px;text-decoration:underline">Voir →</a></td>
              </tr>
              <?php endforeach; else: ?><tr><td colspan="8" style="text-align:center;color:var(--gray-400);padding:40px">Aucune candidature.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
      <?php if($totalPages>1): ?>
      <div class="pagination"><?php for($i=1;$i<=$totalPages;$i++): ?><a class="page-btn <?= $i===$page?'active':'' ?>" href="?page=<?= $i ?>&status=<?= urlencode($statusFilter) ?>&program=<?= $programFilter ?>&q=<?= urlencode($searchQuery) ?>"><?= $i ?></a><?php endfor; ?></div>
      <?php endif; ?>
    </div>
  </main>
</div>
</body>
</html>
