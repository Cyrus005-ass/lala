<?php
require_once '../includes/config.php';
$db = getDB();

/* =========================
   SECURITE ADMIN
========================= */
if (empty($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

/* =========================
   ACTUALISER ADMIN
========================= */
$adm = $db->prepare("SELECT * FROM admins WHERE id=?");
$adm->execute([$_SESSION['admin_id']]);
$adm = $adm->fetch();

$success = $error = '';

/* =========================
   DELETE FAQ
========================= */
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];

    $stmt = $db->prepare("DELETE FROM faq WHERE id=?");
    $stmt->execute([$id]);

    header("Location: faq.php?deleted=1");
    exit;
}

/* =========================
   ADD / UPDATE FAQ
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id = (int)($_POST['id'] ?? 0);
    $question = trim($_POST['question']);
    $answer = trim($_POST['answer']);
    $lang = $_POST['lang'] ?? 'fr';

    if ($question && $answer) {

        if ($id > 0) {
            // UPDATE
            $stmt = $db->prepare("
                UPDATE faq 
                SET question=?, answer=?, lang=? 
                WHERE id=?
            ");
            $stmt->execute([$question, $answer, $lang, $id]);

            $success = "FAQ mise à jour ✔️";
        } else {
            // INSERT
            $stmt = $db->prepare("
                INSERT INTO faq (question, answer, lang)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$question, $answer, $lang]);

            $success = "FAQ ajoutée ✔️";
        }

    } else {
        $error = "Question et réponse obligatoires";
    }
}

/* =========================
   EDIT MODE
========================= */
$editFaq = null;

if (isset($_GET['edit'])) {
    $id = (int) $_GET['edit'];

    $stmt = $db->prepare("SELECT * FROM faq WHERE id=?");
    $stmt->execute([$id]);

    $editFaq = $stmt->fetch();
}

/* =========================
   LIST FAQ
========================= */
$faqs = $db->query("SELECT * FROM faq ORDER BY id DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>FAQ Admin</title>

<style>
body{font-family:Arial;background:#f5f6fa}
.container{padding:30px}
.card{background:#fff;padding:20px;border-radius:10px;margin-bottom:20px}
table{width:100%;border-collapse:collapse}
th,td{padding:10px;border-bottom:1px solid #ddd}
th{background:#0A2647;color:#fff}
input,textarea,select{
    width:100%;
    padding:10px;
    margin:5px 0;
    border:1px solid #ccc;
    border-radius:5px;
}
button{
    padding:10px 15px;
    background:#0A2647;
    color:#fff;
    border:none;
    border-radius:5px;
    cursor:pointer;
}
.btn-del{background:red}
.btn-edit{background:orange}
.msg{padding:10px;margin-bottom:10px;border-radius:5px}
.success{background:#d4edda}
.error{background:#f8d7da}
.actions a{margin-right:10px;font-size:13px}
</style>
</head>

<body>

<div class="container">

<h1>❓ Gestion FAQ</h1>

<?php if(!empty($success)): ?>
<div class="msg success"><?= $success ?></div>
<?php endif; ?>

<?php if(!empty($error)): ?>
<div class="msg error"><?= $error ?></div>
<?php endif; ?>

<!-- FORM -->
<div class="card">
<h2><?= $editFaq ? "Modifier FAQ" : "Ajouter FAQ" ?></h2>

<form method="POST">

<?php if($editFaq): ?>
<input type="hidden" name="id" value="<?= $editFaq['id'] ?>">
<?php endif; ?>

<label>Question</label>
<input type="text" name="question" required
value="<?= htmlspecialchars($editFaq['question'] ?? '') ?>">

<label>Réponse</label>
<textarea name="answer" rows="4" required><?= htmlspecialchars($editFaq['answer'] ?? '') ?></textarea>

<label>Langue</label>
<select name="lang">
<option value="fr" <?= ($editFaq['lang'] ?? '')=='fr'?'selected':'' ?>>FR</option>
<option value="en" <?= ($editFaq['lang'] ?? '')=='en'?'selected':'' ?>>EN</option>
<option value="es" <?= ($editFaq['lang'] ?? '')=='es'?'selected':'' ?>>ES</option>
</select>

<button type="submit">
<?= $editFaq ? "Modifier" : "Ajouter" ?>
</button>

</form>
</div>

<!-- LIST -->
<div class="card">
<h2>📋 Liste FAQ</h2>

<table>
<tr>
<th>ID</th>
<th>Question</th>
<th>Réponse</th>
<th>Langue</th>
<th>Actions</th>
</tr>

<?php foreach($faqs as $f): ?>
<tr>
<td><?= $f['id'] ?></td>
<td><?= htmlspecialchars($f['question']) ?></td>
<td><?= htmlspecialchars(substr($f['answer'],0,80)) ?>...</td>
<td><?= strtoupper($f['lang']) ?></td>
<td class="actions">

<a href="?edit=<?= $f['id'] ?>" class="btn-edit">Modifier</a>

<a href="?delete=<?= $f['id'] ?>" class="btn-del"
onclick="return confirm('Supprimer cette FAQ ?')">
Supprimer
</a>

</td>
</tr>
<?php endforeach; ?>

</table>
</div>

</div>

</body>
</html>