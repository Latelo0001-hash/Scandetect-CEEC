<?php
declare(strict_types=1);

/**
 * Référentiel des représentants autorisés.
 * Remplacez les noms/e-mails ci-dessous par les coordonnées officielles.
 */
function ceec_representatives(): array
{
    return [
        'Représentant CEEC 1',
        'Représentant CEEC 2',
        'Représentant CEEC 3',
    ];
}

function mines_representatives(): array
{
    return [
        'Représentant Ministère des Mines 1' => 'webmaster@scandetect-ceec.cd',
        'Représentant Ministère des Mines 2' => 'webmaster@scandetect-ceec.cd',
        'Représentant Ministère des Mines 3' => 'webmaster@scandetect-ceec.cd',
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
