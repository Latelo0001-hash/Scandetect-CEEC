<?php
declare(strict_types=1);

require_once __DIR__ . '/mail-config.php';

function smtp_read_response($socket): array
{
    $lines = [];
    $code = 0;

    while (!feof($socket)) {
        $line = fgets($socket, 8192);
        if ($line === false) break;
        $line = rtrim($line, "\r\n");
        $lines[] = $line;

        if (preg_match('/^(\d{3})([ -])(.*)$/', $line, $m)) {
            $code = (int) $m[1];
            if ($m[2] === ' ') break;
        } else {
            break;
        }
    }

    return [$code, implode("\n", $lines)];
}

function smtp_expect($socket, array $accepted, string $context): string
{
    [$code, $message] = smtp_read_response($socket);
    if (!in_array($code, $accepted, true)) {
        throw new RuntimeException($context . " (SMTP {$code}) : " . $message);
    }
    return $message;
}

function smtp_command($socket, string $command, array $accepted, string $context): string
{
    if (fwrite($socket, $command . "\r\n") === false) {
        throw new RuntimeException('Impossible d’écrire vers le serveur SMTP.');
    }
    return smtp_expect($socket, $accepted, $context);
}

function smtp_header_encode(string $text): string
{
    if ($text === '' || preg_match('/^[\x20-\x7E]+$/', $text)) return $text;
    return '=?UTF-8?B?' . base64_encode($text) . '?=';
}

function smtp_send_message($recipient, string $subject, string $body): bool
{
    $cfg = scandetect_mail_config();

    $recipients = is_array($recipient) ? $recipient : [$recipient];
    $recipients = array_values(array_unique(array_filter(array_map(static function ($value): string {
        $value = trim((string) $value);
        return filter_var($value, FILTER_VALIDATE_EMAIL) ? $value : '';
    }, $recipients))));
    if (!$recipients) {
        throw new RuntimeException('Aucun destinataire SMTP valide n’est configuré.');
    }

    $password = (string) ($cfg['password'] ?? '');
    if ($password === '') {
        throw new RuntimeException('Mot de passe SMTP manquant. Renseignez includes/mail-secrets.local.php.');
    }

    $host = (string) $cfg['host'];
    $port = (int) $cfg['port'];
    $timeout = (int) ($cfg['timeout'] ?? 20);

    $context = stream_context_create([
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
            'peer_name' => $host,
            'SNI_enabled' => true,
            'allow_self_signed' => false,
        ],
    ]);

    $errno = 0;
    $errstr = '';
    $socket = @stream_socket_client(
        'ssl://' . $host . ':' . $port,
        $errno,
        $errstr,
        $timeout,
        STREAM_CLIENT_CONNECT,
        $context
    );

    if (!is_resource($socket)) {
        throw new RuntimeException("Connexion SMTP impossible vers {$host}:{$port} ({$errno}) {$errstr}");
    }

    stream_set_timeout($socket, $timeout);

    try {
        smtp_expect($socket, [220], 'Le serveur SMTP n’a pas accepté la connexion');
        smtp_command($socket, 'EHLO scandetect-ceec.cd', [250], 'EHLO refusé');
        smtp_command($socket, 'AUTH LOGIN', [334], 'Authentification SMTP indisponible');
        smtp_command($socket, base64_encode((string) $cfg['username']), [334], 'Nom d’utilisateur SMTP refusé');
        smtp_command($socket, base64_encode($password), [235], 'Mot de passe SMTP refusé');

        $from = (string) $cfg['from_email'];
        smtp_command($socket, 'MAIL FROM:<' . $from . '>', [250], 'Expéditeur SMTP refusé');
        foreach ($recipients as $smtpRecipient) {
            smtp_command($socket, 'RCPT TO:<' . $smtpRecipient . '>', [250, 251], 'Destinataire SMTP refusé : ' . $smtpRecipient);
        }
        smtp_command($socket, 'DATA', [354], 'Le serveur SMTP refuse le contenu du message');

        $messageId = sprintf('<%s.%s@scandetect-ceec.cd>', time(), bin2hex(random_bytes(8)));
        $headers = [
            'Date: ' . date(DATE_RFC2822),
            'Message-ID: ' . $messageId,
            'From: ' . smtp_header_encode((string) $cfg['from_name']) . ' <' . $from . '>',
            'To: ' . implode(', ', array_map(static function (string $address): string { return '<' . $address . '>'; }, $recipients)),
            'Subject: ' . smtp_header_encode($subject),
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
            'Reply-To: ' . $from,
            'Auto-Submitted: auto-generated',
            'X-Auto-Response-Suppress: All',
        ];

        // Normaliser les fins de lignes SMTP et protéger les lignes commençant par un point.
        $normalizedBody = preg_replace("~\r\n|\r|\n~", "\r\n", $body) ?? $body;
        $normalizedBody = preg_replace('/(?m)^\./', '..', $normalizedBody) ?? $normalizedBody;
        $payload = implode("\r\n", $headers) . "\r\n\r\n" . $normalizedBody . "\r\n.\r\n";

        if (fwrite($socket, $payload) === false) {
            throw new RuntimeException('Impossible d’envoyer le contenu du message SMTP.');
        }
        smtp_expect($socket, [250], 'Le message OTP a été refusé par le serveur SMTP');
        smtp_command($socket, 'QUIT', [221], 'Fermeture SMTP inattendue');
        fclose($socket);
        return true;
    } catch (Throwable $e) {
        @fwrite($socket, "QUIT\r\n");
        @fclose($socket);
        throw $e;
    }
}
