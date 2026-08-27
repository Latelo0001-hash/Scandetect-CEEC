<?php
declare(strict_types=1);

const APP_NAME = 'ScanDetect par CEEC';
// Laisser vide : le domaine ET le sous-dossier éventuel sont détectés automatiquement.
// Exemple local : http://localhost/ScanDetect-CEEC
// Exemple production : https://scandetect-ceec.cd
const APP_BASE_URL = '';

// MODE MOCK : mettre à false avant la mise en production définitive.
const SCANDETECT_MOCK_DATA_ENABLED = true;

const APP_USERS = [
    'admin@scandetect-ceec.cd' => [
        'name' => 'Admin',
        'password_hash' => '$2y$12$5G16c4uLt.6RX0As/BvoV.OdFjmI6bIn3g5nXDGy3vUxmgGRbwUJy',
    ],
    'jb.otshudi@scandetect-ceec.cd' => [
        'name' => 'Jean Baptist Otshudi Disahi',
        'password_hash' => '$2y$12$QkIzVJw8/qk7SBG9pXP4Suo2T6EP39AoU4cemRhJZeJ.vbxYcFCK2',
    ],
];

// Compatibilité avec les versions PHP antérieures à PHP 8 pour les fonctions utilisées.
if (!function_exists('str_starts_with')) {
    function str_starts_with(string $haystack, string $needle): bool
    {
        return $needle === '' || substr($haystack, 0, strlen($needle)) === $needle;
    }
}
if (!function_exists('str_contains')) {
    function str_contains(string $haystack, string $needle): bool
    {
        return $needle === '' || strpos($haystack, $needle) !== false;
    }
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';

    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Strict',
        'cookie_secure' => $secure,
        'use_strict_mode' => true,
    ]);
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function authenticate_user(string $email, string $password): ?array
{
    $email = strtolower(trim($email));
    $user = APP_USERS[$email] ?? null;
    if (!is_array($user) || !password_verify($password, (string) ($user['password_hash'] ?? ''))) {
        return null;
    }

    return [
        'email' => $email,
        'name' => (string) ($user['name'] ?? $email),
    ];
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function verify_csrf(): void
{
    if (!isset($_POST['csrf']) || !hash_equals(csrf_token(), (string) $_POST['csrf'])) {
        http_response_code(419);
        exit('Session expirée. Veuillez revenir à la page précédente et réessayer.');
    }
}

/**
 * Retourne le sous-dossier dans lequel l'application est installée.
 * Production à la racine => "".
 * Local dans /ScanDetect-CEEC => "/ScanDetect-CEEC".
 */
function app_base_path(): string
{
    static $cached = null;
    if ($cached !== null) return $cached;

    $configured = trim(APP_BASE_URL);
    if ($configured !== '') {
        $path = (string) (parse_url($configured, PHP_URL_PATH) ?? '');
        $path = '/' . trim(str_replace('\\', '/', $path), '/');
        $cached = $path === '/' ? '' : rtrim($path, '/');
        return $cached;
    }

    // Déduire d'abord le sous-dossier depuis le système de fichiers. Cette
    // méthode reste correcte même avec une URL réécrite comme /certificate/view.
    $documentRoot = realpath((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''));
    $applicationRoot = realpath(dirname(__DIR__));
    if (is_string($documentRoot) && $documentRoot !== '' && is_string($applicationRoot)) {
        $documentRoot = rtrim(str_replace('\\', '/', $documentRoot), '/');
        $applicationRoot = rtrim(str_replace('\\', '/', $applicationRoot), '/');
        if ($applicationRoot === $documentRoot) {
            $cached = '';
            return $cached;
        }
        if (str_starts_with($applicationRoot . '/', $documentRoot . '/')) {
            $relativeRoot = trim(substr($applicationRoot, strlen($documentRoot)), '/');
            $cached = $relativeRoot === '' ? '' : '/' . $relativeRoot;
            return $cached;
        }
    }

    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $dir = str_replace('\\', '/', dirname($script));
    if ($dir === '/' || $dir === '.' || $dir === '\\' || $dir === '') {
        $cached = '';
    } else {
        $cached = '/' . trim($dir, '/');
    }
    return $cached;
}

/**
 * Associe les fichiers PHP internes à des URL publiques courtes et stables.
 * Les fichiers restent accessibles directement pour préserver la compatibilité.
 */
function app_public_path(string $path): string
{
    $query = '';
    $queryPosition = strpos($path, '?');
    if ($queryPosition !== false) {
        $query = substr($path, $queryPosition);
        $path = substr($path, 0, $queryPosition);
    }

    $routes = [
        'index.php' => '',
        'login.php' => 'login',
        'logout.php' => 'logout',
        'dashboard.php' => 'dashboard',
        'certificat.php' => 'certificate',
        'confirmation.php' => 'certificate/confirmation',
        'generate-pdf.php' => 'certificate/generate',
        'view-certificate.php' => 'certificate/view',
        'certificate-pdf.php' => 'certificate/pdf',
        'certificate-data.php' => 'api/certificate',
        'request-otp.php' => 'api/otp/request',
        'verify-otp.php' => 'api/otp/verify',
        'save-generated-pdf.php' => 'api/certificate/save-pdf',
        'print-certificate-once.php' => 'api/certificate/print',
        'mark-printed.php' => 'api/certificate/mark-printed',
        'diagnostic.php' => 'diagnostic',
    ];

    return ($routes[ltrim($path, '/')] ?? ltrim($path, '/')) . $query;
}

/** Construit un chemin interne fiable, à la racine ou dans un sous-dossier. */
function app_route(string $path = ''): string
{
    $path = trim($path);
    if ($path === '') return app_base_path() === '' ? '/' : app_base_path() . '/';

    if (preg_match('#^(?:https?:)?//#i', $path) || str_starts_with($path, 'mailto:') || str_starts_with($path, '#')) {
        return $path;
    }

    $publicPath = app_public_path($path);
    if ($publicPath === '') return app_base_path() === '' ? '/' : app_base_path() . '/';

    return app_base_path() . '/' . $publicPath;
}

function redirect(string $path): void
{
    $location = preg_match('#^https?://#i', $path) ? $path : app_route($path);
    header('Location: ' . $location);
    exit;
}

function app_url(string $path = ''): string
{
    $configured = rtrim(APP_BASE_URL, '/');
    if ($configured !== '') {
        return $path === '' ? $configured : $configured . '/' . ltrim($path, '/');
    }

    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
    $scheme = $https ? 'https' : 'http';
    $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
    if (!preg_match('/^[A-Za-z0-9.\-:\[\]]+$/', $host)) {
        $host = 'localhost';
    }

    return $scheme . '://' . $host . app_route($path);
}

function flash_error(string $message): void
{
    $_SESSION['flash_error'] = $message;
}

function take_flash_error(): string
{
    $message = (string) ($_SESSION['flash_error'] ?? '');
    unset($_SESSION['flash_error']);
    return $message;
}
