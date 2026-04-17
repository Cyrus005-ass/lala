<?php
require_once '../includes/config.php';

// Redirect if already logged in
if (!empty($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$timeout = isset($_GET['timeout']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM admins WHERE (username = ? OR email = ?) AND is_active = 1");
        $stmt->execute([$username, $username]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password_hash'])) {
            $_SESSION['admin_id']   = $admin['id'];
            $_SESSION['admin_role'] = $admin['role'];
            $_SESSION['last_activity'] = time();
            // Update last login
            $db->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?")->execute([$admin['id']]);
            header('Location: index.php');
            exit;
        } else {
            $error = 'Identifiants incorrects. Veuillez réessayer.';
        }
    } else {
        $error = 'Veuillez remplir tous les champs.';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Administration — Connexion</title>
<link rel="stylesheet" href="../assets/css/main.css">
<style>
body { background: linear-gradient(135deg, var(--navy) 0%, #1a3a6e 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
.login-card { background: var(--white); border-radius: var(--radius-lg); padding: 48px 40px; width: 100%; max-width: 420px; box-shadow: 0 24px 64px rgba(0,0,0,.3); }
.login-logo { text-align: center; margin-bottom: 32px; }
.login-logo-mark { width: 52px; height: 52px; background: var(--navy); border-radius: 12px; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px; }
.login-logo-mark svg { width: 28px; height: 28px; fill: var(--gold); }
.login-title { font-family: var(--font-serif); font-size: 28px; font-weight: 600; color: var(--navy); }
.login-subtitle { font-size: 13px; color: var(--gray-400); margin-top: 4px; }
.input-group { position: relative; margin-bottom: 16px; }
.input-icon { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); font-size: 16px; pointer-events: none; }
.input-group .form-control { padding-left: 40px; }
.login-btn { width: 100%; justify-content: center; margin-top: 8px; padding: 13px; font-size: 15px; }
.forgot { text-align: center; margin-top: 16px; font-size: 13px; color: var(--gray-400); }
</style>
</head>
<body>
<div class="login-card">
  <div class="login-logo">
    <div class="login-logo-mark">
      <svg viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
    </div>
    <div class="login-title">FINOVA</div>
    <div class="login-subtitle">Espace Administration</div>
  </div>

  <?php if($timeout): ?>
  <div class="alert alert-info" style="margin-bottom:20px">⏱ Session expirée. Veuillez vous reconnecter.</div>
  <?php endif; ?>

  <?php if($error): ?>
  <div class="alert alert-danger" style="margin-bottom:20px">⚠️ <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST" novalidate>
    <div class="form-group" style="margin-bottom:16px">
      <label class="form-label">Identifiant ou email</label>
      <div class="input-group">
        <span class="input-icon">👤</span>
        <input type="text" name="username" class="form-control" placeholder="admin" value="<?= htmlspecialchars($_POST['username']??'') ?>" required autofocus>
      </div>
    </div>
    <div class="form-group">
      <label class="form-label">Mot de passe</label>
      <div class="input-group">
        <span class="input-icon">🔒</span>
        <input type="password" name="password" class="form-control" placeholder="••••••••" required>
      </div>
    </div>
    <button type="submit" class="btn btn-primary login-btn">Se connecter →</button>
  </form>

  <div class="forgot">
    <a href="../index.php" style="color:var(--navy)">← Retour au site public</a>
  </div>
</div>
</body>
</html>
