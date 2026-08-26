<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/storage.php';
require_auth();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Méthode non autorisée.']);
    exit;
}

verify_csrf();
$token = strtolower(trim((string) ($_POST['id'] ?? '')));
$record = valid_public_token($token) ? load_certificate_record($token) : null;
if (!$record) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'message' => 'Certificat introuvable.']);
    exit;
}
if (trim((string) ($record['printed_at'] ?? '')) !== '') {
    http_response_code(409);
    echo json_encode(['ok' => false, 'message' => 'Ce certificat est déjà imprimé. Toute réimpression est interdite.']);
    exit;
}

if (!verification_pdf_exists($token) || !generated_pdf_exists($token)) {
    http_response_code(409);
    echo json_encode(['ok' => false, 'message' => 'Les PDF doivent être générés avant l’impression.']);
    exit;
}

$record['printed_at'] = gmdate('c');
$record['printed_by'] = (string) ($_SESSION['user'] ?? '');
save_certificate_record($token, $record);

echo json_encode(['ok' => true, 'printed_at' => $record['printed_at']], JSON_UNESCAPED_SLASHES);
