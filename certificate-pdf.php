<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/storage.php';

$token = strtolower(trim((string) ($_GET['id'] ?? '')));
$type = (string) ($_GET['type'] ?? 'verification');
if (!in_array($type, ['print', 'verification'], true)) {
    $type = 'verification';
}

$record = load_certificate_record($token);
if (!$record) {
    http_response_code(404);
    exit('Certificat introuvable.');
}
if ($type === 'print' && trim((string) ($record['printed_at'] ?? '')) !== '') {
    http_response_code(403);
    exit('Réimpression interdite : ce certificat est déjà marqué comme imprimé. Utilisez la version de consultation.');
}

try {
    $path = $type === 'print' ? generated_pdf_path($token) : verification_pdf_path($token);
} catch (Throwable $e) {
    http_response_code(404);
    exit('Certificat introuvable.');
}

if (!is_file($path)) {
    http_response_code(503);
    header('Retry-After: 5');
    exit('Le certificat PDF est en cours de génération. Veuillez réessayer dans quelques secondes.');
}

if (!is_readable($path)) {
    http_response_code(500);
    exit('Le certificat existe mais le serveur ne peut pas le lire. Vérifiez les permissions du dossier storage.');
}

$data = (array) ($record['data'] ?? []);
$filename = safe_certificate_pdf_filename((string) ($data['certificate_number'] ?? 'certificat'), $type);
$size = filesize($path);
if ($size === false || $size < 5) {
    http_response_code(500);
    exit('Le fichier PDF est invalide ou vide.');
}

// Les hébergeurs partagés peuvent activer compression et buffering en production.
// On les neutralise ici pour éviter qu'ils ne cassent le flux binaire du PDF.
@ini_set('zlib.output_compression', '0');
@ini_set('output_buffering', '0');
while (ob_get_level() > 0) {
    @ob_end_clean();
}

$start = 0;
$end = $size - 1;
$status = 200;

// Support des requêtes HTTP Range utilisées par les lecteurs PDF des navigateurs.
$range = (string) ($_SERVER['HTTP_RANGE'] ?? '');
if ($range !== '' && preg_match('/^bytes=(\d*)-(\d*)$/', trim($range), $m)) {
    if ($m[1] === '' && $m[2] === '') {
        http_response_code(416);
        header('Content-Range: bytes */' . $size);
        exit;
    }

    if ($m[1] === '') {
        $suffix = (int) $m[2];
        if ($suffix <= 0) {
            http_response_code(416);
            header('Content-Range: bytes */' . $size);
            exit;
        }
        $start = max(0, $size - $suffix);
    } else {
        $start = (int) $m[1];
        if ($m[2] !== '') {
            $end = min($end, (int) $m[2]);
        }
    }

    if ($start > $end || $start >= $size) {
        http_response_code(416);
        header('Content-Range: bytes */' . $size);
        exit;
    }
    $status = 206;
}

$length = $end - $start + 1;
http_response_code($status);
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . $filename . '"; filename*=UTF-8\'\'' . rawurlencode($filename));
header('Accept-Ranges: bytes');
header('Content-Length: ' . $length);
if ($status === 206) {
    header('Content-Range: bytes ' . $start . '-' . $end . '/' . $size);
}
header('X-Content-Type-Options: nosniff');
header('X-Robots-Tag: noindex, nofollow, noarchive');
header('Cache-Control: private, no-transform, max-age=300');
header('X-Accel-Buffering: no');

$handle = fopen($path, 'rb');
if ($handle === false) {
    http_response_code(500);
    exit('Impossible d’ouvrir le certificat PDF.');
}

if ($start > 0) {
    fseek($handle, $start);
}

$remaining = $length;
while ($remaining > 0 && !feof($handle)) {
    $chunk = fread($handle, min(1024 * 1024, $remaining));
    if ($chunk === false || $chunk === '') {
        break;
    }
    echo $chunk;
    $remaining -= strlen($chunk);
    flush();
}
fclose($handle);
exit;
