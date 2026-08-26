<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/storage.php';
require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Méthode non autorisée.');
}

verify_csrf();

$token = strtolower(trim((string) ($_POST['id'] ?? '')));
$paperNumber = trim((string) ($_POST['paper_number'] ?? ''));
$record = valid_public_token($token) ? load_certificate_record($token) : null;

if (!$record) {
    http_response_code(404);
    exit('Certificat introuvable.');
}

$data = (array) ($record['data'] ?? []);
$certificateNumber = trim((string) ($data['certificate_number'] ?? ''));
if ($certificateNumber === '' || !hash_equals($certificateNumber, $paperNumber)) {
    http_response_code(409);
    exit('Le numéro du papier officiel ne correspond pas au certificat. Impression bloquée.');
}

if (trim((string) ($record['printed_at'] ?? '')) !== '') {
    http_response_code(409);
    exit('Ce certificat est déjà imprimé. Toute réimpression est interdite.');
}

$path = generated_pdf_path($token);
if (!is_file($path) || !is_readable($path)) {
    http_response_code(409);
    exit('Le calque PDF 4 doit être préparé avant l’impression.');
}

$size = filesize($path);
if ($size === false || $size < 100) {
    http_response_code(500);
    exit('Le calque PDF 4 est invalide.');
}

// L'accès au fichier d'impression constitue l'unique émission autorisée.
$record['printed_at'] = gmdate('c');
$record['printed_by'] = (string) ($_SESSION['user'] ?? '');
$record['paper_number_confirmed'] = $paperNumber;
save_certificate_record($token, $record);

@ini_set('zlib.output_compression', '0');
@ini_set('output_buffering', '0');
while (ob_get_level() > 0) {
    @ob_end_clean();
}

$filename = safe_certificate_pdf_filename($certificateNumber, 'print');
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . $filename . '"; filename*=UTF-8\'\'' . rawurlencode($filename));
header('Content-Length: ' . $size);
header('Cache-Control: private, no-store, max-age=0');
header('X-Content-Type-Options: nosniff');

$handle = fopen($path, 'rb');
if ($handle === false) {
    http_response_code(500);
    exit('Impossible d’ouvrir le PDF d’impression.');
}
fpassthru($handle);
fclose($handle);
exit;
