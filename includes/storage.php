<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/mock-data.php';

function storage_root(): string
{
    return dirname(__DIR__) . '/storage';
}

function storage_dir(string $name): string
{
    $allowed = ['certificates', 'generated', 'verification'];
    if (!in_array($name, $allowed, true)) {
        throw new InvalidArgumentException('Dossier de stockage invalide.');
    }

    $path = storage_root() . '/' . $name;
    if (!is_dir($path) && !mkdir($path, 0775, true) && !is_dir($path)) {
        throw new RuntimeException('Impossible de créer le dossier de stockage.');
    }
    return $path;
}

function valid_public_token(string $token): bool
{
    return (bool) preg_match('/^[a-f0-9]{32}$/', $token);
}

function certificate_record_path(string $token): string
{
    if (!valid_public_token($token)) {
        throw new InvalidArgumentException('Jeton de certificat invalide.');
    }
    return storage_dir('certificates') . '/' . $token . '.json';
}

function save_certificate_record(string $token, array $record): void
{
    $json = json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        throw new RuntimeException('Impossible de sérialiser le certificat.');
    }
    if (file_put_contents(certificate_record_path($token), $json, LOCK_EX) === false) {
        throw new RuntimeException('Impossible d’enregistrer le certificat.');
    }
}

function load_certificate_record(string $token): ?array
{
    if (!valid_public_token($token)) return null;

    // Une vraie donnée enregistrée sur disque est toujours prioritaire sur le MOCK.
    $path = certificate_record_path($token);
    if (is_file($path)) {
        $raw = file_get_contents($path);
        if ($raw !== false) {
            $record = json_decode($raw, true);
            if (is_array($record)) return $record;
        }
    }

    return scandetect_mock_record_by_token($token);
}

function list_certificate_records(): array
{
    static $cachedRecords = null;
    if (is_array($cachedRecords)) return $cachedRecords;

    $records = [];
    $knownTokens = [];
    $knownNumbers = [];

    // Les données réelles enregistrées sur le serveur restent prioritaires.
    foreach (glob(storage_dir('certificates') . '/*.json') ?: [] as $path) {
        $raw = file_get_contents($path);
        if ($raw === false) continue;
        $record = json_decode($raw, true);
        if (!is_array($record) || empty($record['token']) || empty($record['data'])) continue;
        $records[] = $record;
        $knownTokens[(string) $record['token']] = true;
        $number = trim((string) (($record['data']['certificate_number'] ?? '')));
        if ($number !== '') $knownNumbers[$number] = true;
    }

    // Injection des données MOCK uniquement quand aucun vrai certificat équivalent n'existe.
    foreach (scandetect_mock_records() as $record) {
        $token = (string) ($record['token'] ?? '');
        $number = trim((string) (($record['data']['certificate_number'] ?? '')));
        if ($token === '' || isset($knownTokens[$token]) || ($number !== '' && isset($knownNumbers[$number]))) continue;
        $records[] = $record;
    }

    usort($records, static fn(array $a, array $b): int => strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? '')));
    $cachedRecords = $records;
    return $cachedRecords;
}

function generated_pdf_path(string $token): string
{
    if (!valid_public_token($token)) throw new InvalidArgumentException('Jeton de certificat invalide.');
    return storage_dir('generated') . '/' . $token . '.pdf';
}

function verification_pdf_path(string $token): string
{
    if (!valid_public_token($token)) throw new InvalidArgumentException('Jeton de certificat invalide.');
    return storage_dir('verification') . '/' . $token . '.pdf';
}

function generated_pdf_exists(string $token): bool
{
    return valid_public_token($token) && is_file(generated_pdf_path($token));
}

function verification_pdf_exists(string $token): bool
{
    return valid_public_token($token) && is_file(verification_pdf_path($token));
}

function safe_certificate_pdf_filename(string $certificateNumber, string $type = 'verification'): string
{
    $name = trim($certificateNumber);
    $name = preg_replace('/[^A-Za-z0-9._-]+/', '-', $name) ?? '';
    $name = trim($name, '.-_');
    if ($name === '') $name = 'certificat';

    return $type === 'print'
        ? 'certificat-impression-' . $name . '.pdf'
        : 'certificat-verification-' . $name . '.pdf';
}

function certificate_number_list_path(): string
{
    return storage_root() . '/certificate-numbers.txt';
}

function listed_certificate_numbers(): array
{
    $numbers = [];
    $path = certificate_number_list_path();
    if (is_file($path)) {
        $lines = file($path, FILE_IGNORE_NEW_LINES) ?: [];
        foreach ($lines as $line) {
            $line = trim((string) $line);
            if ($line === '' || str_starts_with($line, '#')) continue;
            $numbers[] = $line;
        }
    }

    // Les certificats déjà enregistrés restent toujours visibles, même s'ils
    // ne figurent pas encore dans la liste officielle.
    foreach (list_certificate_records() as $record) {
        $number = trim((string) (($record['data']['certificate_number'] ?? '')));
        if ($number !== '') $numbers[] = $number;
    }

    $numbers = array_values(array_unique($numbers));
    natcasesort($numbers);
    return array_values($numbers);
}

function find_certificate_record_by_number(string $number): ?array
{
    $number = trim($number);
    if ($number === '') return null;

    foreach (list_certificate_records() as $record) {
        $candidate = trim((string) (($record['data']['certificate_number'] ?? '')));
        if ($candidate === $number) return $record;
    }
    return null;
}

function certificate_processing_status(string $number): array
{
    $record = find_certificate_record_by_number($number);
    if (!$record) {
        return [
            'status' => 'red',
            'label' => 'Non imprimé',
            'token' => '',
            'record' => null,
            'printed' => false,
        ];
    }

    $token = (string) ($record['token'] ?? '');
    $printedAt = trim((string) ($record['printed_at'] ?? ''));
    // printed_at est l'autorité de verrouillage : même si un PDF est déplacé ou
    // momentanément indisponible, un certificat déjà imprimé ne redevient jamais imprimable.
    $printed = $printedAt !== '';

    return [
        'status' => $printed ? 'green' : 'red',
        'label' => $printed ? 'Imprimé' : 'Non imprimé',
        'token' => $token,
        'record' => $record,
        'printed' => $printed,
    ];
}

function certificate_truck_location(array $record): string
{
    $explicit = trim((string) ($record['truck_location'] ?? ''));
    if ($explicit !== '') return $explicit;

    $data = (array) ($record['data'] ?? []);
    // En l'absence d'un module GPS, on affiche la dernière position déclarée
    // dans le dossier, en privilégiant la sortie de frontière puis le poste de sortie.
    foreach (['border_exit', 'exit_post', 'by_transit', 'place_issue'] as $field) {
        $value = trim((string) ($data[$field] ?? ''));
        if ($value !== '') return $value;
    }
    return 'Non renseignée';
}

function certificate_number_options(): array
{
    $options = [];
    foreach (listed_certificate_numbers() as $number) {
        $state = certificate_processing_status($number);
        $options[] = [
            'number' => $number,
            'status' => $state['status'],
            'label' => $state['label'],
            'token' => $state['token'],
        ];
    }
    return $options;
}
