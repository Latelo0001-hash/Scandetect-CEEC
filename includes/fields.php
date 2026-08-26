<?php

function certificate_fields(): array
{
    return [
        ['certificate_number', 'Numéro du certificat', 'Certificate Number', 'text'],
        ['loading_number', 'Numéro de chargement', 'Loading Number', 'text'],
        ['assessment_date', "Date d’évaluation", 'Assessment Date', 'date'],
        ['issue_date', 'Date de délivrance', 'Date of Issue', 'date'],
        ['expiry_date', "Date d’expiration", 'Expiry Date', 'date'],
        ['exporter', 'Exportateur', 'Exporter', 'text'],
        ['export_license', "Numéro de licence d’exportation", 'Export License Number', 'text'],
        ['vehicle_type', 'Type de véhicule', 'Vehicle Type', 'text'],
        ['horse_plate', 'Numéro de plaque (Horse)', 'License Plate Number (Horse)', 'text'],
        ['cart_plate', 'Numéro de plaque (Trailer)', 'License Plate Number (Trailer)', 'text'],
        ['consignee', 'Nom du destinataire', 'Consumer Name', 'text'],
        ['consignee_address', 'Adresse du destinataire', 'Consumer Address', 'textarea'],
        ['product', 'Produit', 'Product', 'text'],
        ['containing', 'Contenant', 'Containing', 'text'],
        ['drums_bags', 'Fûts ou sacs', 'Drums or Bags', 'text'],
        ['major_components', 'Éléments majeurs', 'Major Components', 'text'],
        ['net_weight', 'Poids net', 'Net Weight', 'text'],
        ['content_percentage', 'Teneur (Pourcentage)', 'Content (Percentage)', 'text'],
        ['metal_content', 'Métal contenu', 'Metal Content', 'text'],
        ['usd_value', 'Valeur USD', 'USD Value', 'text'],
        ['total_net_weight', 'Total poids Net', 'Total Net Weight', 'text'],
        ['total_usd_value', 'Total valeur USD', 'Total USD Value', 'text'],
        ['country_issue', 'Pays de délivrance', 'Country of Issue', 'text'],
        ['place_origin', "Lieu d’origine", 'Place of Origin', 'text'],
        ['place_issue', 'Lieu de délivrance', 'Place of Issue', 'text'],
        ['exit_post', 'Poste de sorti', 'Exit Post', 'text'],
        ['border_exit', 'Sortie de frontière', 'Border exit', 'text'],
        ['by_transit', 'Transitant par', 'By Transit', 'text'],
        ['ceec_representative', 'Représentant CEEC', 'CEEC Representative', 'select_ceec'],
        ['mines_representative', 'Représentant Ministère des Mines', 'Ministry of Mines Representative', 'select_mines'],
    ];
}

function certificate_field_type(string $name): string
{
    foreach (certificate_fields() as [$fieldName, , , $type]) {
        if ($fieldName === $name) return $type;
    }
    return 'text';
}

function display_certificate_value(string $name, string $value): string
{
    if ($value === '') return '';
    if (certificate_field_type($name) === 'date') {
        $dt = DateTimeImmutable::createFromFormat('Y-m-d', $value);
        if ($dt instanceof DateTimeImmutable) return $dt->format('d/m/Y');
    }
    if (in_array($name, ['usd_value', 'total_usd_value'], true)) {
        $clean = trim($value);
        return str_contains($clean, '$') ? $clean : $clean . ' $';
    }
    return $value;
}
