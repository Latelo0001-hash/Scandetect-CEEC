<?php
declare(strict_types=1);

/**
 * Données MOCK ScanDetect-CEEC.
 * Elles servent uniquement aux tests de l'interface et du workflow.
 * Désactivation globale : SCANDETECT_MOCK_DATA_ENABLED = false dans config.php.
 */
function scandetect_mock_records(): array
{
    if (!defined('SCANDETECT_MOCK_DATA_ENABLED') || SCANDETECT_MOCK_DATA_ENABLED !== true) {
        return [];
    }

    $records = [
        [
            'token' => '00000000000000000000000000000007',
            'created_at' => '2026-08-20T13:00:00+00:00',
            'created_by' => 'admin@scandetect-ceec.cd',
            'mock' => true,
            'truck_location' => 'Kasumbalesa',
            'data' => [
                'certificate_number' => '00007',
                'loading_number' => '926CC0607',
                'assessment_date' => '2026-08-17',
                'issue_date' => '2026-08-18',
                'expiry_date' => '2026-10-02',
                'exporter' => 'SOMIKA / C-LINK',
                'export_license' => 'DEC1750409-7E15AD4D-07',
                'vehicle_type' => 'Camion',
                'horse_plate' => 'T684EJV',
                'cart_plate' => 'T584EJV',
                'consignee' => 'Vin Metal Synergies FZCO',
                'consignee_address' => 'JFZA View 18, Office 908, Sheikh Zayed Road, Jebel Ali, Dubaï, UAE',
                'product' => 'Cathode de cuivre',
                'containing' => '14 lots',
                'drums_bags' => '400 sacs',
                'major_components' => 'Cuivre',
                'net_weight' => '30 000,00 MT',
                'content_percentage' => '99.99 %',
                'metal_content' => '29 997,00',
                'usd_value' => '408 906,00 $',
                'total_net_weight' => '30 000,00 MT',
                'total_usd_value' => '408 906,00 $',
                'country_issue' => 'République Démocratique du Congo',
                'place_origin' => 'Kolwezi',
                'place_issue' => 'Lubumbashi',
                'exit_post' => 'Kasumbalesa',
                'border_exit' => 'Kasumbalesa',
                'by_transit' => 'Zambie',
                'ceec_representative' => 'Jose Kasongo Ilunga (Direction provincial du Haut-Katanga)',
                'mines_representative' => 'Honore Kabange Numbi (Inspecteur des mines prépose de mines-CEEC)',
            ],
        ],
        [
            'token' => '00000000000000000000000000000008',
            'created_at' => '2026-08-18T10:00:00+00:00',
            'created_by' => 'admin@scandetect-ceec.cd',
            'mock' => true,
            'truck_location' => 'Lukangaba, Sakania',
            'data' => [
                'certificate_number' => '00008',
                'loading_number' => '926CC0618',
                'assessment_date' => '2026-06-22',
                'issue_date' => '2026-06-22',
                'expiry_date' => '2026-08-06',
                'exporter' => 'SOMIKA / C-LINK',
                'export_license' => 'DEC1750409-7E15AD4D-EB',
                'vehicle_type' => 'Camion',
                'horse_plate' => 'T684EJV',
                'cart_plate' => 'T584EJV',
                'consignee' => 'Vin Metal Synergies FZCO',
                'consignee_address' => 'JFZA View 18, Office 908, Sheikh Zayed Road, Jebel Ali, Dubaï, UAE',
                'product' => 'Cathode de cuivre',
                'containing' => '14 lots',
                'drums_bags' => '400 sacs',
                'major_components' => 'Cuivre',
                'net_weight' => '30 000,00 MT',
                'content_percentage' => '99.99 %',
                'metal_content' => '29 997,00',
                'usd_value' => '408 906,00 $',
                'total_net_weight' => '30 000,00 MT',
                'total_usd_value' => '408 906,00 $',
                'country_issue' => 'République Démocratique du Congo',
                'place_origin' => 'République Démocratique du Congo',
                'place_issue' => 'Lubumbashi',
                'exit_post' => 'Kasumbalesa',
                'border_exit' => 'Lukangaba, Sakania',
                'by_transit' => 'Zambie',
                'ceec_representative' => 'Jean Baptiste OTSHUDI',
                'mines_representative' => 'Honore Kabange Numbi (Inspecteur des mines prépose de mines-CEEC)',
            ],
        ],
        [
            'token' => '00000000000000000000000000000009',
            'created_at' => '2026-08-20T12:55:00+00:00',
            'created_by' => 'admin@scandetect-ceec.cd',
            'mock' => true,
            'truck_location' => 'Kasumbalesa',
            'data' => [
                'certificate_number' => '00009',
                'loading_number' => 'HK-260819-09',
                'assessment_date' => '2026-08-19',
                'issue_date' => '2026-08-19',
                'expiry_date' => '2026-10-03',
                'exporter' => 'Katanga Minerals Démo SARL',
                'export_license' => 'EXP-HK-2026-009',
                'vehicle_type' => 'Camion semi-remorque',
                'horse_plate' => 'HK 3097 AB',
                'cart_plate' => 'HK 7412 CD',
                'consignee' => 'Global Metals Demo Ltd',
                'consignee_address' => 'Copper Trade Zone, Ndola, Zambie',
                'product' => 'Hydroxyde de cobalt',
                'containing' => '20 lots',
                'drums_bags' => '500 sacs',
                'major_components' => 'Cobalt',
                'net_weight' => '25 000,00 kg',
                'content_percentage' => '32.50 %',
                'metal_content' => '8 125,00 kg',
                'usd_value' => '325 000,00 $',
                'total_net_weight' => '25 000,00 kg',
                'total_usd_value' => '325 000,00 $',
                'country_issue' => 'République Démocratique du Congo',
                'place_origin' => 'Likasi',
                'place_issue' => 'Lubumbashi',
                'exit_post' => 'Kasumbalesa',
                'border_exit' => 'Kasumbalesa',
                'by_transit' => 'Zambie',
                'ceec_representative' => 'Jose Kasongo Ilunga (Direction provincial du Haut-Katanga)',
                'mines_representative' => 'Honore Kabange Numbi (Inspecteur des mines prépose de mines-CEEC)',
            ],
        ],
        [
            'token' => '00000000000000000000000000000010',
            'created_at' => '2026-08-16T10:00:00+00:00',
            'created_by' => 'admin@scandetect-ceec.cd',
            'mock' => true,
            'truck_location' => 'Lubumbashi',
            'data' => [
                'certificate_number' => '00010',
                'loading_number' => 'LSH-2026-010',
                'assessment_date' => '2026-08-19',
                'issue_date' => '2026-08-20',
                'expiry_date' => '2026-10-04',
                'exporter' => 'Congo Export Démo SA',
                'export_license' => 'CEEC-DEMO-010',
                'vehicle_type' => 'Camion',
                'horse_plate' => 'CGO 1010',
                'cart_plate' => 'CGO 2020',
                'consignee' => 'African Copper Demo Trading',
                'consignee_address' => 'Lusaka Industrial Park, Zambie',
                'product' => 'Concentré de cuivre',
                'containing' => '10 lots',
                'drums_bags' => '250 sacs',
                'major_components' => 'Cuivre',
                'net_weight' => '18 500,00 kg',
                'content_percentage' => '28.40 %',
                'metal_content' => '5 254,00 kg',
                'usd_value' => '190 500,00 $',
                'total_net_weight' => '18 500,00 kg',
                'total_usd_value' => '190 500,00 $',
                'country_issue' => 'République Démocratique du Congo',
                'place_origin' => 'Lubumbashi',
                'place_issue' => 'Lubumbashi',
                'exit_post' => 'Kasumbalesa',
                'border_exit' => 'Kasumbalesa',
                'by_transit' => 'Zambie',
                'ceec_representative' => 'Jean Baptiste OTSHUDI',
                'mines_representative' => 'Honore Kabange Numbi (Inspecteur des mines prépose de mines-CEEC)',
            ],
        ],
    ];

    // Nouveaux dossiers préremplis destinés aux tests du parcours complet.
    $record11 = $records[1];
    $record11['token'] = '00000000000000000000000000000011';
    $record11['created_at'] = '2026-08-25T09:00:00+01:00';
    $record11['truck_location'] = 'Kasumbalesa';
    $record11['data']['certificate_number'] = '00011';
    $record11['data']['loading_number'] = 'TEST-NOUVEAU-MODELE-00011';
    $record11['data']['assessment_date'] = '2026-08-25';
    $record11['data']['issue_date'] = '2026-08-25';
    $record11['data']['expiry_date'] = '2026-10-09';
    $record11['data']['horse_plate'] = 'TEST 1101';
    $record11['data']['cart_plate'] = 'TEST 1102';
    $records[] = $record11;

    $record12 = $records[3];
    $record12['token'] = '00000000000000000000000000000012';
    $record12['created_at'] = '2026-08-25T08:55:00+01:00';
    $record12['truck_location'] = 'Lukangaba, Sakania';
    $record12['data']['certificate_number'] = '00012';
    $record12['data']['loading_number'] = 'TEST-NOUVEAU-MODELE-00012';
    $record12['data']['assessment_date'] = '2026-08-25';
    $record12['data']['issue_date'] = '2026-08-25';
    $record12['data']['expiry_date'] = '2026-10-09';
    $record12['data']['horse_plate'] = 'TEST 1201';
    $record12['data']['cart_plate'] = 'TEST 1202';
    $records[] = $record12;

    // Dossier neuf pour vérifier l'impression directe du PDF sans en-têtes du navigateur.
    $record13 = $record11;
    $record13['token'] = '00000000000000000000000000000013';
    $record13['created_at'] = '2026-08-25T09:30:00+01:00';
    $record13['truck_location'] = 'Kasumbalesa';
    $record13['data']['certificate_number'] = '00013';
    $record13['data']['loading_number'] = 'TEST-IMPRESSION-PDF-00013';
    $record13['data']['horse_plate'] = 'TEST 1301';
    $record13['data']['cart_plate'] = 'TEST 1302';
    $records[] = $record13;

    // Test final des trois sorties : aperçu PDF 2, impression PDF 4 et QR vers PDF 1.
    $record14 = $record12;
    $record14['token'] = '00000000000000000000000000000014';
    $record14['created_at'] = '2026-08-25T10:00:00+01:00';
    $record14['truck_location'] = 'Lukangaba, Sakania';
    $record14['data']['certificate_number'] = '00014';
    $record14['data']['loading_number'] = 'TEST-PDF1-PDF2-PDF4-00014';
    $record14['data']['horse_plate'] = 'TEST 1401';
    $record14['data']['cart_plate'] = 'TEST 1402';
    $records[] = $record14;

    // Dossier vierge pour valider la correspondance stricte des pages 1 à 4.
    $record15 = $record11;
    $record15['token'] = '00000000000000000000000000000015';
    $record15['created_at'] = '2026-08-25T10:30:00+01:00';
    $record15['truck_location'] = 'Kasumbalesa';
    $record15['data']['certificate_number'] = '00015';
    $record15['data']['loading_number'] = 'TEST-MODELE-4-PAGES-00015';
    $record15['data']['assessment_date'] = '2026-08-25';
    $record15['data']['issue_date'] = '2026-08-25';
    $record15['data']['expiry_date'] = '2026-10-09';
    $record15['data']['horse_plate'] = 'TEST 1501';
    $record15['data']['cart_plate'] = 'TEST 1502';
    $record15['data']['consignee'] = 'Copper Verification Test Ltd';
    $record15['data']['product'] = 'Cathode de cuivre — test final';
    $records[] = $record15;

    // Nouveau dossier vierge pour tester le parcours serveur d'impression unique.
    $record16 = $record12;
    $record16['token'] = '00000000000000000000000000000016';
    $record16['created_at'] = '2026-08-25T11:00:00+01:00';
    $record16['truck_location'] = 'Lukangaba, Sakania';
    $record16['data']['certificate_number'] = '00016';
    $record16['data']['loading_number'] = 'TEST-PARCOURS-IMPRESSION-00016';
    $record16['data']['assessment_date'] = '2026-08-25';
    $record16['data']['issue_date'] = '2026-08-25';
    $record16['data']['expiry_date'] = '2026-10-09';
    $record16['data']['horse_plate'] = 'TEST 1601';
    $record16['data']['cart_plate'] = 'TEST 1602';
    $record16['data']['consignee'] = 'CEEC Print Workflow Test';
    $record16['data']['product'] = 'Concentré de cuivre — test impression';
    $records[] = $record16;

    return $records;
}

function scandetect_mock_record_by_token(string $token): ?array
{
    foreach (scandetect_mock_records() as $record) {
        if (($record['token'] ?? '') === $token) {
            return $record;
        }
    }
    return null;
}
