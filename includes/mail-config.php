<?php
declare(strict_types=1);

/**
 * Configuration SMTP ScanDetect-CEEC.
 * Le mot de passe ne doit jamais être enregistré ici.
 */
function scandetect_mail_config(): array
{
    $password = (string) getenv('SCANDETECT_SMTP_PASSWORD');

    $secretFile = __DIR__ . '/mail-secrets.local.php';
    if ($password === '' && is_file($secretFile)) {
        $secret = require $secretFile;
        if (is_array($secret)) {
            $password = (string) ($secret['smtp_password'] ?? '');
        }
    }

    return [
        'host' => 'mail.scandetect-ceec.cd',
        'port' => 465,
        'encryption' => 'ssl',
        'username' => 'noreply@scandetect-ceec.cd',
        'password' => $password,
        'from_email' => 'noreply@scandetect-ceec.cd',
        'from_name' => 'ScanDetect-CEEC',
        // Pendant cette phase de test, le même OTP est envoyé aux deux boîtes ci-dessous.
        'test_recipients' => [
            'webmaster@scandetect-ceec.cd',
            'admin@scandetect-ceec.cd',
        ],
        // Conservé pour compatibilité avec d'anciennes versions du code.
        'test_recipient' => 'webmaster@scandetect-ceec.cd',
        'timeout' => 20,
    ];
}
