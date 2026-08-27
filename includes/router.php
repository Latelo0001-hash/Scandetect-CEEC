<?php
declare(strict_types=1);

/**
 * Routeur de secours pour les serveurs locaux qui renvoient toutes les URL
 * sans extension vers index.php et qui ne prennent pas en charge .htaccess.
 */
function dispatch_clean_route(): void
{
    $requestPath = (string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?? '/');
    $requestPath = '/' . trim(rawurldecode($requestPath), '/');

    $routes = [
        'certificate/confirmation' => 'confirmation.php',
        'certificate/generate' => 'generate-pdf.php',
        'certificate/view' => 'view-certificate.php',
        'certificate/pdf' => 'certificate-pdf.php',
        'api/certificate/save-pdf' => 'save-generated-pdf.php',
        'api/certificate/mark-printed' => 'mark-printed.php',
        'api/certificate/print' => 'print-certificate-once.php',
        'api/certificate' => 'certificate-data.php',
        'api/otp/request' => 'request-otp.php',
        'api/otp/verify' => 'verify-otp.php',
        'dashboard' => 'dashboard.php',
        'certificate' => 'certificat.php',
        'diagnostic' => 'diagnostic.php',
        'logout' => 'logout.php',
        'login' => 'login.php',
    ];

    foreach ($routes as $publicPath => $file) {
        $suffix = '/' . $publicPath;
        if ($requestPath !== $suffix && substr($requestPath, -strlen($suffix)) !== $suffix) {
            continue;
        }

        require dirname(__DIR__) . '/' . $file;
        exit;
    }
}

