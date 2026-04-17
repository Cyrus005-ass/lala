<?php
// api/contact.php
require_once '../includes/config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['success'=>false,'message'=>'Méthode invalide.']); exit; }

$lang = $_POST['lang'] ?? 'fr';
$translations = getLangFile($lang);

$name    = sanitize($_POST['full_name'] ?? '');
$email   = sanitize($_POST['email'] ?? '');
$subject = sanitize($_POST['subject'] ?? '');
$message = sanitize($_POST['message'] ?? '');

if (!$name || !$email || !$message) { echo json_encode(['success'=>false,'message'=>'Champs requis manquants.']); exit; }
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { echo json_encode(['success'=>false,'message'=>$translations['form_error_email']??'Email invalide.']); exit; }

try {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO contacts (full_name, email, subject, message) VALUES (?,?,?,?)");
    $stmt->execute([$name, $email, $subject, $message]);
    echo json_encode(['success'=>true,'message'=>$translations['contact_success']??'Message envoyé.']);
} catch (Exception $e) {
    echo json_encode(['success'=>false,'message'=>'Erreur serveur.']);
}
