<?php
require_once '../includes/config.php';
// Auth
if (empty($_SESSION['admin_id'])) { header('Location: login.php'); exit; }
if (isset($_SESSION['last_activity']) && (time()-$_SESSION['last_activity'])>SESSION_TIMEOUT) { session_destroy(); header('Location: login.php?timeout=1'); exit; }
$_SESSION['last_activity'] = time();

$db = getDB();
$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: applications.php'); exit; }

$stmt = $db->prepare("SELECT a.*, pt.title as prog_title, p.max_amount, p.currency FROM applications a LEFT JOIN programs p ON a.program_id=p.id LEFT JOIN program_translations pt ON p.id=pt.program_id AND pt.lang='fr' WHERE a.id=?");
$stmt->execute([$id]);
$app = $stmt->fetch();
if (!$app) { header('Location: applications.php'); exit; }

// Handle status update
$success = $error = '';
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action'])) {
    $adminUser = $db->prepare("SELECT * FROM admins WHERE id=?"); $adminUser->execute([$_SESSION['admin_id']]); $adm = $adminUser->fetch();
    
    if ($_POST['action']==='update_status') {
        $newStatus = $_POST['new_status'] ?? '';
        $notes     = trim($_POST['admin_notes'] ?? '');
        $score     = !empty($_POST['internal_score']) ? (int)$_POST['internal_score'] : null;
        $validStatuses = ['pending','under_review','approved','rejected','waitlisted','disbursed'];
        if (in_array($newStatus, $validStatuses)) {
            $upd = $db->prepare("UPDATE applications SET status=?, admin_notes=?, internal_score=?, reviewed_by=?, reviewed_at=NOW() WHERE id=?");
            $upd->execute([$newStatus, $notes, $score, $adm['username'], $id]);
            $success = 'Statut mis à jour avec succès.';
            $app['status'] = $newStatus;
            $app['admin_notes'] = $notes;
        }
    } elseif ($_POST['action']==='disburse') {
        $amount = (float)($_POST['disbursement_amount'] ?? 0);
        $date   = $_POST['disbursement_date'] ?? date('Y-m-d');
        if ($amount > 0) {
            $db->prepare("UPDATE applications SET status='disbursed', disbursement_amount=?, disbursement_date=?, reviewed_by=?, reviewed_at=NOW() WHERE id=?")->execute([$amount, $date, $adm['username'], $id]);
            $success = 'Décaissement enregistré.';
            $app['status'] = 'disbursed';
            $app['disbursement_amount'] = $amount;
        }
    }
    header("Location: application_detail.php?id=$id&msg=" . urlencode($success));
    exit;
}

if (isset($_GET['msg'])) $success = htmlspecialchars($_GET['msg']);

$statusList = ['pending'=>'En attente','under_review'=>'En examen','approved'=>'Approuvée','rejected'=>'Rejetée','waitlisted'=>'Liste attente','disbursed'=>'Décaissé'];
$adminUser = $db->prepare("SELECT * FROM admins WHERE id=?"); $adminUser->execute([$_SESSION['admin_id']]); $adm = $adminUser->fetch();
$pendingCount = $db->query("SELECT COUNT(*) FROM applications WHERE status='pending'")->fetchColumn();
$unreadMsgs = $db->query("SELECT COUNT(*) FROM contacts WHERE is_read=0")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Dossier <?= htmlspecialchars($app['reference']) ?> — Admin</title>
<link rel="stylesheet" href="../assets/css/main.css">
<style>
.admin-layout{display:flex;min-height:100vh}.sidebar{width:240px;background:var(--navy);flex-shrink:0;display:flex;flex-direction:column;position:fixed;top:0;bottom:0;left:0;z-index:100}.sidebar-brand{padding:24px 20px;border-bottom:1px solid rgba(255,255,255,.08)}.sidebar-brand .brand{font-family:var(--font-serif);font-size:20px;color:var(--white);font-weight:600}.sidebar-brand .role{font-size:11px;color:var(--gold);text-transform:uppercase;letter-spacing:.08em;margin-top:2px}.sidebar-nav{flex:1;padding:16px 0;overflow-y:auto}.nav-item{display:flex;align-items:center;gap:10px;padding:10px 20px;color:rgba(255,255,255,.65);font-size:13.5px;cursor:pointer;text-decoration:none;transition:var(--transition);border-left:3px solid transparent}.nav-item:hover{color:var(--white);background:rgba(255,255,255,.06)}.nav-item.active{color:var(--white);background:rgba(255,255,255,.1);border-left-color:var(--gold)}.nav-item .icon{width:18px;text-align:center}.nav-sep{height:1px;background:rgba(255,255,255,.06);margin:8px 20px}.nav-badge{margin-left:auto;background:var(--gold);color:var(--navy);font-size:10px;padding:1px 6px;border-radius:10px;font-weight:600}.sidebar-bottom{padding:16px 20px;border-top:1px solid rgba(255,255,255,.08)}.admin-main{margin-left:240px;flex:1;background:var(--gray-50);min-height:100vh}.admin-topbar{background:var(--white);border-bottom:1px solid var(--gray-200);padding:0 28px;height:60px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:50}.topbar-title{font-weight:500;color:var(--navy);font-size:16px}.topbar-right{display:flex;align-items:center;gap:14px}.admin-content{padding:28px}.card{background:var(--white);border-radius:var(--radius-lg);border:1px solid var(--gray-200);overflow:hidden}.card-header{padding:16px 20px;border-bottom:1px solid var(--gray-200);display:flex;align-items:center;justify-content:space-between}.card-title{font-weight:500;color:var(--navy);font-size:14px}.detail-grid{display:grid;grid-template-columns:1fr 1fr;gap:0}.detail-item{padding:14px 20px;border-bottom:1px solid var(--gray-100)}.detail-item:nth-child(odd){border-right:1px solid var(--gray-100)}.detail-label{font-size:11px;color:var(--gray-400);text-transform:uppercase;letter-spacing:.06em;margin-bottom:3px}.detail-value{font-size:14px;color:var(--gray-800)}.two-col{display:grid;grid-template-columns:2fr 1fr;gap:24px;align-items:start}.doc-link{display:inline-flex;align-items:center;gap:6px;padding:7px 14px;background:var(--gray-50);border:1px solid var(--gray-200);border-radius:var(--radius);font-size:12px;color:var(--navy);margin:4px;text-decoration:none}.doc-link:hover{background:var(--navy);color:var(--white)}
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
    </nav>
    <div class="sidebar-bottom">
      <a href="logout.php" class="nav-item" style="padding:8px 0;border:none"><span class="icon">🚪</span> Déconnexion</a>
    </div>
  </aside>

  <main class="admin-main">
    <div class="admin-topbar">
      <div style="display:flex;align-items:center;gap:12px">
        <a href="applications.php" style="color:var(--gray-400);font-size:13px">← Candidatures</a>
        <span style="color:var(--gray-300)">/</span>
        <span style="font-family:monospace;font-size:14px;font-weight:600;color:var(--navy)"><?= htmlspecialchars($app['reference']) ?></span>
        <span class="badge badge-status badge-<?= $app['status'] ?>"><?= $statusList[$app['status']]??$app['status'] ?></span>
      </div>
      <div class="topbar-right"><span style="font-size:13px;color:var(--gray-600)">👤 <?= htmlspecialchars($adm['full_name']??$adm['username']) ?></span></div>
    </div>

    <div class="admin-content">
      <?php if($success): ?><div class="alert alert-success" style="margin-bottom:20px">✅ <?= $success ?></div><?php endif; ?>

      <div class="two-col">
        <!-- Left: details -->
        <div style="display:flex;flex-direction:column;gap:20px">

          <!-- Organisation -->
          <div class="card">
            <div class="card-header"><span class="card-title">🏢 Organisation</span></div>
            <div class="detail-grid">
              <?php
              $orgFields = [
                'Nom' => $app['org_name'],
                'Type' => $app['org_type'],
                'Pays' => $app['org_country'],
                'N° Enregistrement' => $app['org_registration_number'],
                'Fondée en' => $app['org_founded_year'] ?? '—',
                'Site web' => $app['org_website'] ? '<a href="'.htmlspecialchars($app['org_website']).'" target="_blank" style="color:var(--navy)">'.$app['org_website'].'</a>' : '—',
              ];
              foreach($orgFields as $k=>$v): ?>
              <div class="detail-item"><div class="detail-label"><?= $k ?></div><div class="detail-value"><?= is_string($v)?htmlspecialchars($v):$v ?></div></div>
              <?php endforeach; ?>
              <div class="detail-item" style="grid-column:1/-1"><div class="detail-label">Adresse</div><div class="detail-value"><?= nl2br(htmlspecialchars($app['org_address'])) ?></div></div>
            </div>
          </div>

          <!-- Contact -->
          <div class="card">
            <div class="card-header"><span class="card-title">👤 Contact</span></div>
            <div class="detail-grid">
              <div class="detail-item"><div class="detail-label">Nom</div><div class="detail-value"><?= htmlspecialchars($app['contact_name']) ?></div></div>
              <div class="detail-item"><div class="detail-label">Titre</div><div class="detail-value"><?= htmlspecialchars($app['contact_title']??'—') ?></div></div>
              <div class="detail-item"><div class="detail-label">Email</div><div class="detail-value"><a href="mailto:<?= htmlspecialchars($app['contact_email']) ?>" style="color:var(--navy)"><?= htmlspecialchars($app['contact_email']) ?></a></div></div>
              <div class="detail-item"><div class="detail-label">Téléphone</div><div class="detail-value"><?= htmlspecialchars($app['contact_phone']) ?></div></div>
            </div>
          </div>

          <!-- Projet -->
          <div class="card">
            <div class="card-header"><span class="card-title">📁 Projet</span></div>
            <div style="padding:20px;display:flex;flex-direction:column;gap:16px">
              <div><div class="detail-label">Titre</div><div style="font-size:16px;font-weight:500;color:var(--navy);margin-top:4px"><?= htmlspecialchars($app['project_title']) ?></div></div>
              <div><div class="detail-label">Programme</div><div class="detail-value"><?= htmlspecialchars($app['prog_title']??'—') ?></div></div>
              <div><div class="detail-label">Description</div><div style="font-size:14px;color:var(--gray-700);line-height:1.7;margin-top:4px;white-space:pre-wrap"><?= htmlspecialchars($app['project_description']) ?></div></div>
              <div><div class="detail-label">Objectifs</div><div style="font-size:14px;color:var(--gray-700);line-height:1.7;margin-top:4px;white-space:pre-wrap"><?= htmlspecialchars($app['project_objectives']) ?></div></div>
              <div><div class="detail-label">Bénéficiaires</div><div style="font-size:14px;color:var(--gray-700);line-height:1.7;margin-top:4px"><?= htmlspecialchars($app['project_beneficiaries']) ?></div></div>
              <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px">
                <div><div class="detail-label">Lieu</div><div class="detail-value"><?= htmlspecialchars($app['project_location']) ?></div></div>
                <div><div class="detail-label">Durée</div><div class="detail-value"><?= htmlspecialchars($app['project_duration']) ?></div></div>
                <div><div class="detail-label">Démarrage</div><div class="detail-value"><?= $app['project_start_date'] ? date('d/m/Y', strtotime($app['project_start_date'])) : '—' ?></div></div>
              </div>
            </div>
          </div>

          <!-- Budget -->
          <div class="card">
            <div class="card-header"><span class="card-title">💰 Budget</span></div>
            <div class="detail-grid">
              <div class="detail-item"><div class="detail-label">Budget total projet</div><div class="detail-value" style="font-size:18px;font-weight:600;color:var(--navy)">$<?= $app['project_budget'] ? number_format($app['project_budget'],0,',',' ') : '—' ?></div></div>
              <div class="detail-item"><div class="detail-label">Montant demandé</div><div class="detail-value" style="font-size:18px;font-weight:600;color:var(--gold)">$<?= number_format($app['requested_amount'],0,',',' ') ?></div></div>
              <?php if($app['disbursement_amount']): ?>
              <div class="detail-item"><div class="detail-label">Montant décaissé</div><div class="detail-value" style="font-size:18px;font-weight:600;color:var(--success)">$<?= number_format($app['disbursement_amount'],0,',',' ') ?></div></div>
              <div class="detail-item"><div class="detail-label">Date décaissement</div><div class="detail-value"><?= $app['disbursement_date'] ? date('d/m/Y', strtotime($app['disbursement_date'])) : '—' ?></div></div>
              <?php endif; ?>
            </div>
          </div>

          <!-- Documents -->
          <div class="card">
            <div class="card-header"><span class="card-title">📎 Documents</span></div>
            <div style="padding:16px 20px">
              <?php
              $docs = ['doc_registration'=>'Attestation d\'enregistrement','doc_statutes'=>'Statuts juridiques','doc_budget_plan'=>'Plan budgétaire','doc_project_plan'=>'Plan de projet','doc_last_report'=>'Rapport d\'activité'];
              foreach($docs as $field=>$label):
                if(!empty($app[$field])): ?>
              <a class="doc-link" href="../uploads/<?= htmlspecialchars($app[$field]) ?>" target="_blank">📄 <?= $label ?></a>
              <?php endif; endforeach; ?>
            </div>
          </div>
        </div>

        <!-- Right: actions panel -->
        <div style="display:flex;flex-direction:column;gap:20px;position:sticky;top:80px">
          <!-- Submission info -->
          <div class="card">
            <div class="card-header"><span class="card-title">ℹ️ Informations</span></div>
            <div style="padding:16px">
              <div style="margin-bottom:12px"><div class="detail-label">Soumis le</div><div style="font-size:14px;font-weight:500"><?= date('d/m/Y à H:i', strtotime($app['submitted_at'])) ?></div></div>
              <div style="margin-bottom:12px"><div class="detail-label">Langue</div><div style="font-size:14px"><?= strtoupper($app['lang']) ?></div></div>
              <?php if($app['reviewed_by']): ?><div><div class="detail-label">Examiné par</div><div style="font-size:14px"><?= htmlspecialchars($app['reviewed_by']) ?> — <?= date('d/m/Y', strtotime($app['reviewed_at'])) ?></div></div><?php endif; ?>
            </div>
          </div>

          <!-- Update status -->
          <div class="card">
            <div class="card-header"><span class="card-title">⚡ Mettre à jour le statut</span></div>
            <form method="POST" style="padding:20px">
              <input type="hidden" name="action" value="update_status">
              <div class="form-group" style="margin-bottom:14px">
                <label class="form-label">Nouveau statut</label>
                <select name="new_status" class="form-control">
                  <?php foreach(['pending','under_review','approved','rejected','waitlisted'] as $s): ?>
                  <option value="<?= $s ?>" <?= $app['status']===$s?'selected':'' ?>><?= $statusList[$s] ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group" style="margin-bottom:14px">
                <label class="form-label">Score interne (1–10)</label>
                <input type="number" name="internal_score" class="form-control" min="1" max="10" value="<?= $app['internal_score']??'' ?>">
              </div>
              <div class="form-group" style="margin-bottom:16px">
                <label class="form-label">Notes internes</label>
                <textarea name="admin_notes" class="form-control" rows="4" placeholder="Commentaires, points forts, points faibles..."><?= htmlspecialchars($app['admin_notes']??'') ?></textarea>
              </div>
              <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center">Enregistrer le statut</button>
            </form>
          </div>

          <!-- Disbursement -->
          <?php if($app['status']==='approved' || $app['status']==='disbursed'): ?>
          <div class="card">
            <div class="card-header"><span class="card-title">💸 Enregistrer un décaissement</span></div>
            <form method="POST" style="padding:20px">
              <input type="hidden" name="action" value="disburse">
              <div class="form-group" style="margin-bottom:14px">
                <label class="form-label">Montant décaissé (USD)</label>
                <input type="number" name="disbursement_amount" class="form-control" step="100" min="0" max="<?= $app['max_amount']??9999999 ?>" value="<?= $app['disbursement_amount']??$app['requested_amount'] ?>">
              </div>
              <div class="form-group" style="margin-bottom:16px">
                <label class="form-label">Date du décaissement</label>
                <input type="date" name="disbursement_date" class="form-control" value="<?= $app['disbursement_date']??date('Y-m-d') ?>">
              </div>
              <button type="submit" class="btn btn-success" style="width:100%;justify-content:center">Confirmer le décaissement</button>
            </form>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </main>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>
