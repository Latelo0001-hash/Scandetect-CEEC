<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/representatives.php';
require_once __DIR__ . '/smtp-mailer.php';

const OTP_TTL_SECONDS = 600;
const OTP_RESEND_COOLDOWN_SECONDS = 60;
const OTP_MAX_ATTEMPTS = 5;

function otp_is_local_debug(): bool
{
    // Les OTP sont désormais envoyés réellement par SMTP, même en local.
    return false;
}

function otp_mask_email(string $email): string
{
    [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');
    if ($domain === '') return 'adresse configurée';
    $visible = substr($local, 0, min(2, strlen($local)));
    return $visible . str_repeat('•', max(3, strlen($local) - strlen($visible))) . '@' . $domain;
}


function otp_delivery_recipients(string $fallbackEmail): array
{
    $cfg = scandetect_mail_config();
    $configured = $cfg['test_recipients'] ?? [];
    if (!is_array($configured)) $configured = [];

    // Compatibilité avec l'ancien réglage à un seul destinataire.
    if (!$configured) {
        $legacy = trim((string) ($cfg['test_recipient'] ?? ''));
        if ($legacy !== '') $configured[] = $legacy;
    }
    if (!$configured && $fallbackEmail !== '') $configured[] = $fallbackEmail;

    $valid = [];
    foreach ($configured as $email) {
        $email = strtolower(trim((string) $email));
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) $valid[$email] = $email;
    }
    return array_values($valid);
}

function otp_mask_recipients(array $recipients): string
{
    return implode(' et ', array_map('otp_mask_email', $recipients));
}

function otp_send_mail(string $email, string $representative, string $certificateNumber, string $otp): bool
{
    $recipients = otp_delivery_recipients($email);
    if (!$recipients) {
        throw new RuntimeException('Aucune adresse de réception OTP n’est configurée.');
    }

    $subject = 'Code de validation ScanDetect - Certificat ' . $certificateNumber;
    $body = "Bonjour,\r\n\r\n"
        . "Une demande de validation a été initiée pour le certificat {$certificateNumber}.\r\n"
        . "Responsable sélectionné : {$representative}.\r\n\r\n"
        . "Votre code de confirmation est : {$otp}\r\n\r\n"
        . "Ce code expire dans 10 minutes et ne doit être communiqué à personne.\r\n\r\n"
        . "ScanDetect par CEEC\r\n"
        . "Message automatique — ne pas répondre.\r\n";

    return smtp_send_message($recipients, $subject, $body);
}

function otp_create_for_draft(array $draft): array
{
    $number = trim((string) ($draft['certificate_number'] ?? ''));
    $representative = trim((string) ($draft['mines_representative'] ?? ''));
    $email = mine_responsible_email($representative);
    $deliveryRecipients = otp_delivery_recipients($email);

    if ($number === '' || $email === '' || !$deliveryRecipients) {
        throw new RuntimeException('Le responsable de mine ou son adresse e-mail n’est pas configuré.');
    }

    $existing = (array) ($_SESSION['certificate_otp'] ?? []);
    $lastSent = (int) ($existing['sent_at'] ?? 0);
    $sameCertificate = ($existing['certificate_number'] ?? '') === $number;
    $stillValid = (int) ($existing['expires_at'] ?? 0) > time();
    if ($sameCertificate && $stillValid && $lastSent > 0 && (time() - $lastSent) < OTP_RESEND_COOLDOWN_SECONDS) {
        return [
            'email' => implode(', ', $deliveryRecipients),
            'masked_email' => otp_mask_recipients($deliveryRecipients),
            'representative' => $representative,
            'debug_otp' => otp_is_local_debug() ? (string) ($existing['debug_otp'] ?? '') : '',
            'expires_in' => max(0, (int) ($existing['expires_at'] ?? time()) - time()),
            'already_sent' => true,
        ];
    }

    $otp = (string) random_int(100000, 999999);
    $sessionRecord = [
        'certificate_number' => $number,
        'representative' => $representative,
        'recipient' => implode(', ', $deliveryRecipients),
        'hash' => password_hash($otp, PASSWORD_DEFAULT),
        'expires_at' => time() + OTP_TTL_SECONDS,
        'sent_at' => time(),
        'attempts' => 0,
        'debug_otp' => otp_is_local_debug() ? $otp : '',
    ];

    if (!otp_send_mail($email, $representative, $number, $otp)) {
        throw new RuntimeException('Le serveur n’a pas pu envoyer l’e-mail OTP. Vérifiez la configuration e-mail de l’hébergement.');
    }

    $_SESSION['certificate_otp'] = $sessionRecord;
    unset($_SESSION['otp_verified']);

    return [
        'email' => implode(', ', $deliveryRecipients),
        'masked_email' => otp_mask_recipients($deliveryRecipients),
        'representative' => $representative,
        'debug_otp' => otp_is_local_debug() ? $otp : '',
        'expires_in' => OTP_TTL_SECONDS,
    ];
}

function otp_verify_for_draft(array $draft, string $code): bool
{
    $otp = (array) ($_SESSION['certificate_otp'] ?? []);
    $number = trim((string) ($draft['certificate_number'] ?? ''));

    if ($number === '' || ($otp['certificate_number'] ?? '') !== $number) {
        throw new RuntimeException('Aucun code OTP valide n’est associé à ce certificat.');
    }
    if ((int) ($otp['expires_at'] ?? 0) < time()) {
        unset($_SESSION['certificate_otp']);
        throw new RuntimeException('Le code a expiré. Demandez un nouveau code.');
    }

    $attempts = (int) ($otp['attempts'] ?? 0) + 1;
    $_SESSION['certificate_otp']['attempts'] = $attempts;
    if ($attempts > OTP_MAX_ATTEMPTS) {
        unset($_SESSION['certificate_otp']);
        throw new RuntimeException('Nombre maximal de tentatives atteint. Demandez un nouveau code.');
    }

    if (!preg_match('/^\d{6}$/', $code) || !password_verify($code, (string) ($otp['hash'] ?? ''))) {
        return false;
    }

    $_SESSION['otp_verified'] = [
        'certificate_number' => $number,
        'verified_at' => time(),
        'representative' => (string) ($otp['representative'] ?? ''),
        'recipient' => (string) ($otp['recipient'] ?? ''),
    ];
    unset($_SESSION['certificate_otp']);
    return true;
}

function otp_is_verified_for_draft(array $draft): bool
{
    $verified = (array) ($_SESSION['otp_verified'] ?? []);
    $number = trim((string) ($draft['certificate_number'] ?? ''));
    return $number !== ''
        && ($verified['certificate_number'] ?? '') === $number
        && (int) ($verified['verified_at'] ?? 0) >= (time() - OTP_TTL_SECONDS);
}

function otp_consume_verification(): void
{
    unset($_SESSION['otp_verified'], $_SESSION['certificate_otp']);
}
