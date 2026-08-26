<?php
declare(strict_types=1);

/**
 * Référentiel des représentants autorisés.
 * Remplacez les noms/e-mails ci-dessous par les coordonnées officielles.
 */
function ceec_representatives(): array
{
    return [
        'Herve Kadiayi',
        'Jose Kasongo Ilunga (Direction provinciale du Haut-Katanga)',
        'Agent CEEC Démonstration',
    ];
}

function mines_representatives(): array
{
    return [
        'Musoga Kethia' => 'webmaster@scandetect-ceec.cd',
        'Honore Kabange Numbi (Inspecteur des mines préposé de mines-CEEC)' => 'webmaster@scandetect-ceec.cd',
        'Inspecteur des Mines Démonstration' => 'webmaster@scandetect-ceec.cd',
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
