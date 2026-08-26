<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/storage.php';
require_auth();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
$number = trim((string) ($_GET['number'] ?? ''));
if ($number === '') { http_response_code(400); echo json_encode(['ok'=>false,'message'=>'Numéro de certificat manquant.']); exit; }
try {
    $state = certificate_processing_status($number);
    $record = is_array($state['record']) ? $state['record'] : null;
    $data = $record ? (array) ($record['data'] ?? []) : [];
    $token = (string) ($state['token'] ?? '');
    echo json_encode([
        'ok'=>true,'number'=>$number,'status'=>$state['status'],'status_label'=>$state['label'],
        'token'=>$token,'data'=>$data,'locked'=>!empty($state['printed']),
        'certificate_url'=>$token !== '' ? app_route('view-certificate.php?id='.rawurlencode($token)) : '',
    ], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    error_log('[ScanDetect] certificate-data: '.$e->getMessage());
    http_response_code(500);
    echo json_encode(['ok'=>false,'message'=>'Impossible de charger le certificat sur le serveur.'], JSON_UNESCAPED_UNICODE);
}
