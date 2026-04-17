<?php
// api/submit_application.php
require_once '../includes/config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode invalide.']);
    exit;
}

$lang = sanitize($_POST['lang'] ?? 'fr');
$translations = getLangFile($lang);

$required = ['program_id', 'org_name', 'org_type', 'org_country', 'org_address', 'org_registration_number',
             'contact_name', 'contact_title', 'contact_email', 'contact_phone',
             'project_title', 'project_description', 'project_objectives', 'project_beneficiaries',
             'project_location', 'project_duration', 'requested_amount'];

foreach ($required as $field) {
    if (empty(trim($_POST[$field] ?? ''))) {
        echo json_encode(['success' => false, 'message' => 'Champ manquant: ' . $field]);
        exit;
    }
}

if (!filter_var($_POST['contact_email'], FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => $translations['form_error_email'] ?? 'Email invalide.']);
    exit;
}

// Validate program + amount
$db = getDB();
$stmt = $db->prepare("SELECT * FROM programs WHERE id = ? AND is_active = 1");
$stmt->execute([(int)$_POST['program_id']]);
$program = $stmt->fetch();
if (!$program) {
    echo json_encode(['success' => false, 'message' => 'Programme invalide.']);
    exit;
}

$amount = (float)$_POST['requested_amount'];
if ($amount < $program['min_amount'] || $amount > $program['max_amount']) {
    echo json_encode(['success' => false, 'message' => $translations['form_error_amount'] ?? 'Montant invalide.']);
    exit;
}

// Handle file uploads
$docs = ['doc_registration', 'doc_statutes', 'doc_budget_plan', 'doc_project_plan', 'doc_last_report'];
$uploadedFiles = [];
$requiredDocs = ['doc_registration', 'doc_statutes', 'doc_budget_plan', 'doc_project_plan'];

foreach ($docs as $doc) {
    if (isset($_FILES[$doc]) && $_FILES[$doc]['error'] === UPLOAD_ERR_OK) {
        $filename = handleUpload($doc);
        if (!$filename && in_array($doc, $requiredDocs)) {
            echo json_encode(['success' => false, 'message' => $translations['form_error_file'] ?? 'Fichier invalide: ' . $doc]);
            exit;
        }
        $uploadedFiles[$doc] = $filename;
    } elseif (in_array($doc, $requiredDocs)) {
        echo json_encode(['success' => false, 'message' => 'Document requis manquant: ' . $doc]);
        exit;
    }
}

$reference = generateRef();

try {
    $insert = $db->prepare("INSERT INTO applications 
        (reference, program_id, org_name, org_type, org_country, org_address, org_registration_number, org_founded_year, org_website,
         contact_name, contact_title, contact_email, contact_phone,
         project_title, project_description, project_objectives, project_beneficiaries, project_location, project_duration, project_start_date,
         project_budget, requested_amount, doc_registration, doc_statutes, doc_budget_plan, doc_project_plan, doc_last_report, lang)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
    
    $insert->execute([
        $reference,
        (int)$_POST['program_id'],
        sanitize($_POST['org_name']),
        sanitize($_POST['org_type']),
        sanitize($_POST['org_country']),
        sanitize($_POST['org_address']),
        sanitize($_POST['org_registration_number']),
        !empty($_POST['org_founded_year']) ? (int)$_POST['org_founded_year'] : null,
        sanitize($_POST['org_website'] ?? ''),
        sanitize($_POST['contact_name']),
        sanitize($_POST['contact_title']),
        sanitize($_POST['contact_email']),
        sanitize($_POST['contact_phone']),
        sanitize($_POST['project_title']),
        sanitize($_POST['project_description']),
        sanitize($_POST['project_objectives']),
        sanitize($_POST['project_beneficiaries']),
        sanitize($_POST['project_location']),
        sanitize($_POST['project_duration']),
        !empty($_POST['project_start_date']) ? $_POST['project_start_date'] : null,
        !empty($_POST['project_budget']) ? (float)$_POST['project_budget'] : null,
        $amount,
        $uploadedFiles['doc_registration'] ?? null,
        $uploadedFiles['doc_statutes'] ?? null,
        $uploadedFiles['doc_budget_plan'] ?? null,
        $uploadedFiles['doc_project_plan'] ?? null,
        $uploadedFiles['doc_last_report'] ?? null,
        $lang,
    ]);

    echo json_encode(['success' => true, 'reference' => $reference]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur serveur. Veuillez réessayer.']);
}
