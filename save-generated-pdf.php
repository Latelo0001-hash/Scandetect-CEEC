<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/storage.php';
require_auth();

function ini_bytes(string $value): int
{
    $value = trim($value);
    if ($value === '') return 0;
    $last = strtolower(substr($value, -1));
    $number = (float) $value;
    switch ($last) {
        case 'g': return (int) ($number * 1024 * 1024 * 1024);
        case 'm': return (int) ($number * 1024 * 1024);
        case 'k': return (int) ($number * 1024);
        default: return (int) $number;
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Méthode non autorisée.');
}

// Si post_max_size est dépassé, PHP peut vider complètement $_POST et $_FILES.
$contentLength = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
$postMax = ini_bytes((string) ini_get('post_max_size'));
if ($postMax > 0 && $contentLength > $postMax) {
    http_response_code(413);
    exit('Le PDF généré dépasse post_max_size (' . ini_get('post_max_size') . '). Augmentez cette limite dans .user.ini ou dans le panneau PHP de l’hébergement.');
}

verify_csrf();

$token = strtolower(trim((string) ($_POST['id'] ?? '')));
$type = (string) ($_POST['type'] ?? 'print');
if (!in_array($type, ['print', 'verification'], true)) {
    http_response_code(400);
    exit('Type de PDF invalide.');
}
$record = valid_public_token($token) ? load_certificate_record($token) : null;
if (!$record) {
    http_response_code(404);
    exit('Certificat introuvable.');
}
if (trim((string) ($record['printed_at'] ?? '')) !== '') {
    http_response_code(409);
    exit('Ce certificat est déjà imprimé et verrouillé. Les PDF ne peuvent plus être remplacés.');
}
if (!isset($_FILES['pdf']) || !is_array($_FILES['pdf'])) {
    http_response_code(400);
    exit('PDF manquant. Vérifiez upload_max_filesize (' . ini_get('upload_max_filesize') . ') et post_max_size (' . ini_get('post_max_size') . ').');
}

$file = $_FILES['pdf'];
$error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
if ($error !== UPLOAD_ERR_OK) {
    $messages = [
        UPLOAD_ERR_INI_SIZE => 'Le PDF dépasse upload_max_filesize (' . ini_get('upload_max_filesize') . ').',
        UPLOAD_ERR_FORM_SIZE => 'Le PDF dépasse la taille maximale autorisée par le formulaire.',
        UPLOAD_ERR_PARTIAL => 'Le PDF n’a été reçu que partiellement.',
        UPLOAD_ERR_NO_FILE => 'Aucun PDF n’a été reçu par le serveur.',
        UPLOAD_ERR_NO_TMP_DIR => 'Le dossier temporaire PHP est indisponible.',
        UPLOAD_ERR_CANT_WRITE => 'PHP ne peut pas écrire le fichier temporaire sur le serveur.',
        UPLOAD_ERR_EXTENSION => 'Une extension PHP a bloqué le téléversement.',
    ];
    http_response_code($error === UPLOAD_ERR_INI_SIZE ? 413 : 400);
    exit($messages[$error] ?? ('Erreur de téléversement PDF, code ' . $error . '.'));
}

$size = (int) ($file['size'] ?? 0);
if ($size < 100 || $size > 30 * 1024 * 1024) {
    http_response_code(400);
    exit('Taille du PDF invalide.');
}
$tmp = (string) ($file['tmp_name'] ?? '');
if ($tmp === '' || !is_uploaded_file($tmp)) {
    http_response_code(400);
    exit('Fichier temporaire invalide.');
}
$handle = fopen($tmp, 'rb');
$magic = $handle ? fread($handle, 5) : false;
if ($handle) fclose($handle);
if ($magic !== '%PDF-') {
    http_response_code(400);
    exit('Le fichier reçu n’est pas un PDF valide.');
}

$destination = $type === 'verification' ? verification_pdf_path($token) : generated_pdf_path($token);
$directory = dirname($destination);
if (!is_dir($directory) || !is_writable($directory)) {
    http_response_code(500);
    exit('Le dossier ' . basename($directory) . ' n’est pas accessible en écriture par PHP. Vérifiez les permissions du dossier storage.');
}

if (!move_uploaded_file($tmp, $destination)) {
    http_response_code(500);
    exit('Impossible d’enregistrer le certificat PDF sur le serveur.');
}
@chmod($destination, 0644);
clearstatcache(true, $destination);

if (!is_file($destination) || filesize($destination) < 100) {
    http_response_code(500);
    exit('Le PDF a été reçu mais son enregistrement final a échoué.');
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'ok' => true,
    'type' => $type,
    'size' => filesize($destination),
    'url' => app_url('certificate-pdf.php?id=' . rawurlencode($token) . '&type=' . $type),
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
