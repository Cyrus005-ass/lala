<?php
// api/track.php
require_once '../includes/config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['html'=>'<p>Méthode invalide.</p>']); exit; }

$reference = strtoupper(trim($_POST['reference'] ?? ''));
$email = strtolower(trim($_POST['email'] ?? ''));
$lang = $_POST['lang'] ?? 'fr';
$translations = getLangFile($lang);

if (!$reference || !$email) { echo json_encode(['html'=>'<p style="color:var(--danger)">Veuillez remplir tous les champs.</p>']); exit; }

$db = getDB();
$stmt = $db->prepare("SELECT a.*, pt.title as program_title FROM applications a LEFT JOIN programs p ON a.program_id = p.id LEFT JOIN program_translations pt ON p.id = pt.program_id AND pt.lang = ? WHERE a.reference = ? AND LOWER(a.contact_email) = ?");
$stmt->execute([$lang, $reference, $email]);
$app = $stmt->fetch();

if (!$app) {
    $translations_en = getLangFile('en');
    echo json_encode(['html' => '<div class="alert alert-danger">' . htmlspecialchars($translations['track_not_found'] ?? 'Non trouvé.') . '</div>']);
    exit;
}

$statusKey = 'track_status_' . $app['status'];
$statusLabel = $translations[$statusKey] ?? $app['status'];
$badgeClass = 'badge-' . $app['status'];

$statusColors = [
    'pending'      => '#D97706',
    'under_review' => '#2563EB',
    'approved'     => '#059669',
    'rejected'     => '#DC2626',
    'waitlisted'   => '#6B7280',
    'disbursed'    => '#065F46',
];
$color = $statusColors[$app['status']] ?? '#6B7280';

$progressMap = ['pending'=>10,'under_review'=>40,'approved'=>75,'waitlisted'=>50,'disbursed'=>100,'rejected'=>100];
$progress = $progressMap[$app['status']] ?? 10;
$progressColor = $app['status'] === 'rejected' ? '#DC2626' : 'var(--success)';

$html = '<div class="track-result">';
$html .= '<div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:20px">';
$html .= '<div><div style="font-size:11px;color:var(--gray-400);text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px">' . htmlspecialchars($translations['track_reference'] ?? 'Référence') . '</div>';
$html .= '<div style="font-family:monospace;font-size:20px;font-weight:600;color:var(--navy)">' . htmlspecialchars($app['reference']) . '</div></div>';
$html .= '<span class="badge badge-status ' . $badgeClass . '">' . htmlspecialchars($statusLabel) . '</span>';
$html .= '</div>';

// Progress bar
if ($app['status'] !== 'rejected') {
    $html .= '<div style="margin-bottom:20px">';
    $html .= '<div style="height:6px;background:var(--gray-200);border-radius:3px;overflow:hidden">';
    $html .= '<div style="height:100%;width:' . $progress . '%;background:' . $progressColor . ';border-radius:3px;transition:width 1s ease"></div>';
    $html .= '</div></div>';
}

$html .= '<div class="track-info-grid">';
$fields = [
    ['label' => $translations['program_label'] ?? 'Programme', 'value' => $app['program_title'] ?? '—'],
    ['label' => $translations['form_org_name'] ?? 'Organisation', 'value' => $app['org_name']],
    ['label' => $translations['form_project_title'] ?? 'Projet', 'value' => $app['project_title']],
    ['label' => $translations['form_requested_amount'] ?? 'Montant demandé', 'value' => '$' . number_format($app['requested_amount'], 0, ',', ' ')],
    ['label' => $translations['submitted_on'] ?? 'Soumis le', 'value' => date('d/m/Y', strtotime($app['submitted_at']))],
    ['label' => $translations['form_org_country'] ?? 'Pays', 'value' => $app['org_country']],
];
foreach ($fields as $field) {
    $html .= '<div class="track-info-item"><label>' . htmlspecialchars($field['label']) . '</label><p>' . htmlspecialchars($field['value']) . '</p></div>';
}
$html .= '</div>';

// Disbursement info
if ($app['status'] === 'disbursed' && $app['disbursement_amount']) {
    $html .= '<div class="alert alert-success" style="margin-top:16px">✅ Montant décaissé: <strong>$' . number_format($app['disbursement_amount'], 0, ',', ' ') . '</strong>';
    if ($app['disbursement_date']) $html .= ' le ' . date('d/m/Y', strtotime($app['disbursement_date']));
    $html .= '</div>';
}

$html .= '</div>';

echo json_encode(['html' => $html]);
