<?php
declare(strict_types=1);

/**
 * Référentiel des représentants autorisés.
 * Remplacez les noms/e-mails ci-dessous par les coordonnées officielles.
 */
function ceec_representatives(): array
{
    return [
        'Jose Kasongo Ilunga (Direction provincial du Haut-Katanga)',
        'Jean Baptiste OTSHUDI',
    ];
}

function ceec_representative_emails(): array
{
    return [
        'Jean Baptiste OTSHUDI' => 'jb.otshudi@scandetect-ceec.cd',
    ];
}

function ceec_responsible_email(string $representative): string
{
    return (string) (ceec_representative_emails()[$representative] ?? '');
}

function mines_representatives(): array
{
    return [
        'Honore Kabange Numbi (Inspecteur des mines prépose de mines-CEEC)' => 'webmaster@scandetect-ceec.cd',
    ];
}

function mine_responsible_email(string $representative): string
{
    return (string) (mines_representatives()[$representative] ?? '');
}

function representative_is_allowed(string $field, string $value): bool
{
    if ($field === 'ceec_representative') {
        return in_array($value, ceec_representatives(), true);
    }
    if ($field === 'mines_representative') {
        return array_key_exists($value, mines_representatives());
    }
    return false;
}
