<?php
require_once '../includes/config.php';
$db = getDB();

if (empty($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

/* =========================
   AJOUT ADMIN
========================= */
if (isset($_POST['add_admin'])) {

    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'];
    $full_name = trim($_POST['full_name']);

    if ($username && $email && $password) {

        $hash = password_hash($password, PASSWORD_BCRYPT);

        $stmt = $db->prepare("
            INSERT INTO admins (username, email, password_hash, full_name, role)
            VALUES (?, ?, ?, ?, ?)
        ");

        try {
            $stmt->execute([$username, $email, $hash, $full_name, $role]);
            $success = "Admin créé avec succès ✔️";
        } catch (Exception $e) {
            $error = "Erreur : username ou email déjà utilisé";
        }

    } else {
        $error = "Tous les champs obligatoires";
    }
}

/* =========================
   SUPPRESSION ADMIN
========================= */
if (isset($_POST['delete_admin'])) {

    $id = (int) $_POST['id'];

    // empêcher suppression superadmin
    $check = $db->prepare("SELECT role FROM admins WHERE id=?");
    $check->execute([$id]);
    $user = $check->fetch();

    if ($user && $user['role'] === 'superadmin') {
        $error = "Impossible de supprimer un Super Admin ❌";
    } else {
        $stmt = $db->prepare("DELETE FROM admins WHERE id=?");
        $stmt->execute([$id]);
        $success = "Admin supprimé ✔️";
    }
}

/* =========================
   EDIT ADMIN
========================= */
if (isset($_POST['edit_admin'])) {

    $id = (int) $_POST['id'];
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $full_name = trim($_POST['full_name']);
    $role = $_POST['role'];

    $stmt = $db->prepare("
        UPDATE admins 
        SET username=?, email=?, full_name=?, role=? 
        WHERE id=?
    ");

    $stmt->execute([$username, $email, $full_name, $role, $id]);
    $success = "Admin modifié ✔️";
}

/* =========================
   LISTE ADMINS
========================= */
$admins = $db->query("SELECT * FROM admins ORDER BY created_at DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Admins</title>

<style>
body{font-family:Arial;background:#f5f6fa}
.container{padding:30px}
.card{background:#fff;padding:20px;border-radius:10px;margin-bottom:20px}
table{width:100%;border-collapse:collapse}
th,td{padding:10px;border-bottom:1px solid #ddd}
th{background:#0A2647;color:#fff}
input,select{width:100%;padding:8px;margin:5px 0}
button{padding:8px 12px;border:none;border-radius:5px;cursor:pointer}
.btn-del{background:red;color:#fff}
.btn-edit{background:orange;color:#fff}
.btn-add{background:#0A2647;color:#fff}
.badge{padding:5px 10px;border-radius:5px;color:#fff}
.superadmin{background:#d4af37}
.admin{background:#1e90ff}
.reviewer{background:#888}
.msg{padding:10px;margin-bottom:10px;border-radius:5px}
.success{background:#d4edda}
.error{background:#f8d7da}
</style>
</head>

<body>
<div class="container">

<h1>👥 Admin Panel</h1>

<?php if(!empty($success)): ?>
<div class="msg success"><?= $success ?></div>
<?php endif; ?>

<?php if(!empty($error)): ?>
<div class="msg error"><?= $error ?></div>
<?php endif; ?>

<!-- AJOUT -->
<div class="card">
<h2>➕ Ajouter admin</h2>

<form method="POST">
<input name="username" placeholder="Username" required>
<input name="email" placeholder="Email" required>
<input name="full_name" placeholder="Nom complet">
<input type="password" name="password" placeholder="Password" required>

<select name="role">
<option value="reviewer">Reviewer</option>
<option value="admin">Admin</option>
<option value="superadmin">Super Admin</option>
</select>

<button class="btn-add" name="add_admin">Créer</button>
</form>
</div>

<!-- LISTE -->
<div class="card">
<h2>📋 Liste admins</h2>

<table>
<tr>
<th>ID</th><th>User</th><th>Email</th><th>Nom</th><th>Role</th><th>Actions</th>
</tr>

<?php foreach($admins as $a): ?>
<tr>
<td><?= $a['id'] ?></td>
<td><?= htmlspecialchars($a['username']) ?></td>
<td><?= htmlspecialchars($a['email']) ?></td>
<td><?= htmlspecialchars($a['full_name']) ?></td>

<td><span class="badge <?= $a['role'] ?>"><?= $a['role'] ?></span></td>

<td>

<!-- EDIT -->
<form method="POST" style="display:inline-block">
<input type="hidden" name="id" value="<?= $a['id'] ?>">
<input type="text" name="username" value="<?= $a['username'] ?>">
<input type="text" name="email" value="<?= $a['email'] ?>">
<input type="text" name="full_name" value="<?= $a['full_name'] ?>">

<select name="role">
<option <?= $a['role']=='reviewer'?'selected':'' ?>>reviewer</option>
<option <?= $a['role']=='admin'?'selected':'' ?>>admin</option>
<option <?= $a['role']=='superadmin'?'selected':'' ?>>superadmin</option>
</select>

<button class="btn-edit" name="edit_admin">Modifier</button>
</form>

<!-- DELETE -->
<form method="POST" style="display:inline-block">
<input type="hidden" name="id" value="<?= $a['id'] ?>">
<button class="btn-del" name="delete_admin">Supprimer</button>
</form>

</td>
</tr>
<?php endforeach; ?>

</table>
</div>

</div>
</body>
</html>