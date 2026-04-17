<?php
// admin/messages.php
require_once '../includes/config.php';
if (empty($_SESSION['admin_id'])) { header('Location: login.php'); exit; }
if (isset($_SESSION['last_activity']) && (time()-$_SESSION['last_activity'])>SESSION_TIMEOUT) { session_destroy(); header('Location: login.php?timeout=1'); exit; }
$_SESSION['last_activity'] = time();

$db = getDB();
$adm = $db->prepare("SELECT * FROM admins WHERE id=?"); $adm->execute([$_SESSION['admin_id']]); $adm = $adm->fetch();

// Mark as read
if (isset($_GET['read'])) {
    $db->prepare("UPDATE contacts SET is_read=1 WHERE id=?")->execute([(int)$_GET['read']]);
    header('Location: messages.php');
    exit;
}

$messages = $db->query("SELECT * FROM contacts ORDER BY submitted_at DESC")->fetchAll();
$pendingCount = $db->query("SELECT COUNT(*) FROM applications WHERE status='pending'")->fetchColumn();
$unreadMsgs = $db->query("SELECT COUNT(*) FROM contacts WHERE is_read=0")->fetchColumn();
$viewId = (int)($_GET['view'] ?? 0);
$viewMsg = null;
if ($viewId) {
    $s = $db->prepare("SELECT * FROM contacts WHERE id=?"); $s->execute([$viewId]); $viewMsg = $s->fetch();
    $db->prepare("UPDATE contacts SET is_read=1 WHERE id=?")->execute([$viewId]);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Messages — Admin FINOVA</title>
<link rel="stylesheet" href="../assets/css/main.css">
<style>
.admin-layout{display:flex;min-height:100vh}.sidebar{width:240px;background:var(--navy);flex-shrink:0;display:flex;flex-direction:column;position:fixed;top:0;bottom:0;left:0;z-index:100}.sidebar-brand{padding:24px 20px;border-bottom:1px solid rgba(255,255,255,.08)}.sidebar-brand .brand{font-family:var(--font-serif);font-size:20px;color:var(--white);font-weight:600}.sidebar-brand .role{font-size:11px;color:var(--gold);text-transform:uppercase;letter-spacing:.08em;margin-top:2px}.sidebar-nav{flex:1;padding:16px 0;overflow-y:auto}.nav-item{display:flex;align-items:center;gap:10px;padding:10px 20px;color:rgba(255,255,255,.65);font-size:13.5px;cursor:pointer;text-decoration:none;transition:var(--transition);border-left:3px solid transparent}.nav-item:hover{color:var(--white);background:rgba(255,255,255,.06)}.nav-item.active{color:var(--white);background:rgba(255,255,255,.1);border-left-color:var(--gold)}.nav-item .icon{width:18px;text-align:center}.nav-sep{height:1px;background:rgba(255,255,255,.06);margin:8px 20px}.nav-badge{margin-left:auto;background:var(--gold);color:var(--navy);font-size:10px;padding:1px 6px;border-radius:10px;font-weight:600}.sidebar-bottom{padding:16px 20px;border-top:1px solid rgba(255,255,255,.08)}.admin-main{margin-left:240px;flex:1;background:var(--gray-50);min-height:100vh}.admin-topbar{background:var(--white);border-bottom:1px solid var(--gray-200);padding:0 28px;height:60px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:50}.topbar-title{font-weight:500;color:var(--navy);font-size:16px}.admin-content{padding:28px}.card{background:var(--white);border-radius:var(--radius-lg);border:1px solid var(--gray-200);overflow:hidden}.card-header{padding:16px 20px;border-bottom:1px solid var(--gray-200);display:flex;align-items:center;justify-content:space-between}.card-title{font-weight:500;color:var(--navy);font-size:14px}.table{width:100%;border-collapse:collapse}.table th{padding:10px 14px;text-align:left;font-size:11px;font-weight:500;color:var(--gray-400);text-transform:uppercase;letter-spacing:.06em;border-bottom:1px solid var(--gray-200);background:var(--gray-50)}.table td{padding:12px 14px;font-size:13.5px;border-bottom:1px solid var(--gray-100);color:var(--gray-700)}.table tr:last-child td{border-bottom:none}.table tr:hover td{background:var(--gray-50)}
.msg-view{background:var(--white);border-radius:var(--radius-lg);padding:28px;border:1px solid var(--gray-200);margin-bottom:20px}
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
      <a class="nav-item active" href="messages.php"><span class="icon">💬</span> Messages <?php if($unreadMsgs>0): ?><span class="nav-badge"><?= $unreadMsgs ?></span><?php endif; ?></a>
      <a class="nav-item" href="faq.php"><span class="icon">❓</span> FAQ</a>
      <div class="nav-sep"></div>
      <a class="nav-item" href="settings.php"><span class="icon">⚙️</span> Paramètres</a>
      <a class="nav-item" href="admins.php"><span class="icon">👥</span> Administrateurs</a>
    </nav>
    <div class="sidebar-bottom"><a href="logout.php" class="nav-item" style="padding:8px 0;border:none"><span class="icon">🚪</span> Déconnexion</a></div>
  </aside>
  <main class="admin-main">
    <div class="admin-topbar"><span class="topbar-title">Messages de contact</span></div>
    <div class="admin-content">
      <?php if($viewMsg): ?>
      <div class="msg-view">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:20px">
          <div>
            <h3 style="font-family:var(--font-serif);font-size:20px;color:var(--navy)"><?= htmlspecialchars($viewMsg['subject']??'(Sans objet)') ?></h3>
            <p style="font-size:13px;color:var(--gray-400);margin-top:4px">De: <strong><?= htmlspecialchars($viewMsg['full_name']) ?></strong> &lt;<?= htmlspecialchars($viewMsg['email']) ?>&gt; — <?= date('d/m/Y H:i', strtotime($viewMsg['submitted_at'])) ?></p>
          </div>
          <a href="messages.php" class="btn btn-outline btn-sm">← Retour</a>
        </div>
        <div style="background:var(--gray-50);border-radius:var(--radius);padding:20px;font-size:14px;line-height:1.8;white-space:pre-wrap"><?= htmlspecialchars($viewMsg['message']) ?></div>
        <div style="margin-top:20px">
          <a href="mailto:<?= htmlspecialchars($viewMsg['email']) ?>?subject=RE: <?= urlencode($viewMsg['subject']??'') ?>" class="btn btn-primary">📧 Répondre par email</a>
        </div>
      </div>
      <?php else: ?>
      <div class="card">
        <div style="overflow-x:auto">
          <table class="table">
            <thead><tr><th></th><th>Nom</th><th>Email</th><th>Sujet</th><th>Date</th><th></th></tr></thead>
            <tbody>
              <?php foreach($messages as $msg): ?>
              <tr style="<?= !$msg['is_read']?'font-weight:500;background:rgba(10,38,71,.02)':'' ?>">
                <td><?= !$msg['is_read'] ? '🔵' : '' ?></td>
                <td><?= htmlspecialchars($msg['full_name']) ?></td>
                <td><?= htmlspecialchars($msg['email']) ?></td>
                <td><?= htmlspecialchars(substr($msg['subject']??'(Sans objet)',0,40)) ?></td>
                <td style="font-size:12px"><?= date('d/m/Y H:i', strtotime($msg['submitted_at'])) ?></td>
                <td><a href="?view=<?= $msg['id'] ?>" style="color:var(--navy);font-size:12px;text-decoration:underline">Voir →</a></td>
              </tr>
              <?php endforeach; ?>
              <?php if(!$messages): ?><tr><td colspan="6" style="text-align:center;color:var(--gray-400);padding:40px">Aucun message.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </main>
</div>
</body>
</html>
