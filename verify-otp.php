<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/otp.php';
require_auth();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Méthode non autorisée.']);
    exit;
}

try {
    verify_csrf();
    $draft = (array) ($_SESSION['draft'] ?? []);
    if (!$draft) throw new RuntimeException('Le formulaire à valider est introuvable.');
    $code = trim((string) ($_POST['otp'] ?? ''));
    if (!otp_verify_for_draft($draft, $code)) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'message' => 'Code de confirmation incorrect.']);
        exit;
    }
    echo json_encode(['ok' => true, 'message' => 'Validation confirmée.']);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
