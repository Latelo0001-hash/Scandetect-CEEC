<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/fields.php';
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/storage.php';
require_once __DIR__ . '/includes/representatives.php';
require_auth();

$fields = certificate_fields();
$error = take_flash_error();
$pageError = '';
$numberOptions = [];

try {
    $numberOptions = certificate_number_options();
} catch (Throwable $e) {
    $pageError = 'La liste des numéros de certificats n’a pas pu être chargée. Vérifiez les permissions du dossier storage.';
    error_log('[ScanDetect] certificat certificate_number_options: ' . $e->getMessage());
}

$sessionValues = (array) ($_SESSION['draft'] ?? []);
$values = $sessionValues;
$selectedNumber = trim((string) ($sessionValues['certificate_number'] ?? ''));
$queryNumber = trim((string) ($_GET['number'] ?? ''));
$availableNumbers = array_map(static function (array $option): string {
    return (string) ($option['number'] ?? '');
}, $numberOptions);

if ($queryNumber !== '') {
    // Autoriser un numéro présent dans la liste ou déjà enregistré.
    $queryRecord = null;
    try {
        $queryRecord = find_certificate_record_by_number($queryNumber);
    } catch (Throwable $e) {
        error_log('[ScanDetect] certificat find by number: ' . $e->getMessage());
    }
    if (in_array($queryNumber, $availableNumbers, true) || is_array($queryRecord)) {
        $selectedNumber = $queryNumber;
    }
}

$selectedStatus = 'red';
$selectedStatusLabel = 'Non imprimé';
$selectedToken = '';
$selectedLocked = false;
$selectedViewUrl = '';
$initialLoaded = false;

if ($selectedNumber !== '') {
    try {
        $state = certificate_processing_status($selectedNumber);
        $selectedStatus = (string) ($state['status'] ?? 'red');
        $selectedStatusLabel = (string) ($state['label'] ?? 'Non imprimé');
        $selectedToken = (string) ($state['token'] ?? '');
        $selectedLocked = !empty($state['printed']);
        $record = is_array($state['record'] ?? null) ? $state['record'] : null;

        // Si on arrive depuis le tableau de bord, précharger les données côté serveur.
        // Ainsi le formulaire fonctionne même si fetch/AJAX est filtré par l'hébergement.
        if ($queryNumber !== '' && $queryNumber === $selectedNumber) {
            if ($record) {
                $values = (array) ($record['data'] ?? []);
            } else {
                $values = ['certificate_number' => $selectedNumber];
            }
        } elseif (($sessionValues['certificate_number'] ?? '') !== $selectedNumber) {
            if ($record) {
                $values = (array) ($record['data'] ?? []);
            } else {
                $values = ['certificate_number' => $selectedNumber];
            }
        }

        if ($selectedToken !== '') {
            $selectedViewUrl = app_route('view-certificate.php?id=' . rawurlencode($selectedToken));
        }
        $initialLoaded = true;
    } catch (Throwable $e) {
        $pageError = $pageError !== '' ? $pageError : 'Le certificat sélectionné n’a pas pu être chargé.';
        error_log('[ScanDetect] certificat preload: ' . $e->getMessage());
    }
}

if ($selectedNumber !== '' && !in_array($selectedNumber, $availableNumbers, true)) {
    $numberOptions[] = [
        'number' => $selectedNumber,
        'status' => $selectedStatus,
        'label' => $selectedStatusLabel,
        'token' => $selectedToken,
    ];
}

page_start('Nouveau certificat');
?>
<section class="page-hero compact"><div class="container"><p class="eyebrow light">Nouveau dossier</p><h1>Certificat d’origine à l’exportation</h1><p>Sélectionnez d’abord le numéro du certificat. Les autres rubriques apparaîtront automatiquement.</p><div class="progress-track"><span></span></div></div></section>
<section class="container form-container">
<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
<?php if ($pageError): ?><div class="alert alert-danger"><?= e($pageError) ?></div><?php endif; ?>
<form method="post"
      action="<?= e(app_route('confirmation.php')) ?>"
      id="certificate-form"
      data-certificate-data-url="<?= e(app_route('certificate-data.php')) ?>"
      data-self-url="<?= e(app_route('certificat.php')) ?>"
      data-initial-number="<?= e($selectedNumber) ?>"
      data-initial-loaded="<?= $initialLoaded ? '1' : '0' ?>"
      data-initial-status="<?= e($selectedStatus) ?>"
      data-initial-status-label="<?= e($selectedStatusLabel) ?>"
      data-initial-locked="<?= $selectedLocked ? '1' : '0' ?>"
      data-initial-view-url="<?= e($selectedViewUrl) ?>">
<input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
<input type="hidden" name="record_token" id="record_token" value="<?= e($selectedToken !== '' ? $selectedToken : (string) ($_SESSION['draft_token'] ?? '')) ?>">

<div class="form-section certificate-selector-section">
    <div class="section-heading selector-heading"><span>◎</span><div><h2>Sélection du certificat</h2><p>Choisissez un numéro dans la liste officielle.</p></div></div>
    <div class="certificate-status-legend" aria-label="Légende des statuts">
        <span class="status-legend status-green">● Imprimé</span>
        <span class="status-legend status-red">● Non imprimé</span>
    </div>
    <div class="row">
        <div class="col-md-6 field-wrap">
            <label for="certificate_number"><b>1</b> Numéro du certificat <small>Certificate Number</small></label>
            <select class="form-control certificate-number-select" id="certificate_number" name="certificate_number" required>
                <option value="">— Sélectionner un numéro de certificat —</option>
                <?php foreach ($numberOptions as $option):
                    $status = (string) ($option['status'] ?? 'red');
                    $symbol = $status === 'green' ? '🟢' : '🔴';
                ?>
                    <option value="<?= e((string) $option['number']) ?>" data-status="<?= e($status) ?>" class="status-option status-<?= e($status) ?>" <?= $selectedNumber === (string) $option['number'] ? 'selected' : '' ?>><?= $symbol ?> <?= e((string) $option['number']) ?> — <?= e((string) ($option['label'] ?? 'Non imprimé')) ?></option>
                <?php endforeach; ?>
            </select>
            <div id="selected-certificate-status" class="selected-certificate-status status-<?= e($selectedStatus) ?>" <?= $selectedNumber === '' ? 'hidden' : '' ?>>● <?= e($selectedStatusLabel) ?></div>
            <?php if (!$numberOptions): ?>
                <p class="certificate-list-help">Aucun numéro officiel n’est encore listé. Ajoutez les numéros dans <code>storage/certificate-numbers.txt</code>, un numéro par ligne.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<div id="certificate-details" <?= $selectedNumber === '' ? 'hidden' : '' ?>>
<?php
$groups = [
    array_slice($fields, 1, 9, true),
    array_slice($fields, 10, 12, true),
    array_slice($fields, 22, 8, true),
];
$sectionTitles = [
    ['Identification et dates', 'Références principales du certificat'],
    ['Marchandise et valeurs', 'Informations relatives au chargement'],
    ['Origine et validation', 'Traçabilité et représentants officiels'],
];
foreach ($groups as $section => $group): ?>
<div class="form-section"><div class="section-heading"><span>0<?= $section + 1 ?></span><div><h2><?= e($sectionTitles[$section][0]) ?></h2><p><?= e($sectionTitles[$section][1]) ?></p></div></div><div class="row">
<?php foreach ($group as $index => [$name, $fr, $en, $type]): ?>
<div class="col-md-6 field-wrap"><label for="<?= e($name) ?>"><b><?= $index + 1 ?></b> <?= e($fr) ?> <small><?= e($en) ?></small></label>
<?php if ($type === 'textarea'): ?>
<textarea class="form-control certificate-detail-field<?= $selectedLocked ? ' field-readonly' : '' ?>" id="<?= e($name) ?>" name="<?= e($name) ?>" rows="2" required<?= $selectedLocked ? ' readonly' : '' ?>><?= e((string) ($values[$name] ?? '')) ?></textarea>
<?php elseif ($type === 'select_ceec' || $type === 'select_mines'):
    $choices = $type === 'select_ceec' ? ceec_representatives() : array_keys(mines_representatives());
    $current = (string) ($values[$name] ?? ''); ?>
<select class="form-control certificate-detail-field<?= $selectedLocked ? ' field-readonly' : '' ?>" id="<?= e($name) ?>" name="<?= e($name) ?>" required<?= $selectedLocked ? ' disabled' : '' ?>>
    <option value="">— Sélectionner —</option>
    <?php foreach ($choices as $choice): ?>
        <option value="<?= e((string) $choice) ?>" <?= $current === (string) $choice ? 'selected' : '' ?>><?= e((string) $choice) ?></option>
    <?php endforeach; ?>
</select>
<?php elseif (in_array($name, ['usd_value', 'total_usd_value'], true)): ?>
<div class="money-input-wrap">
    <span class="money-prefix" aria-hidden="true">$</span>
    <input class="form-control certificate-detail-field money-input<?= $selectedLocked ? ' field-readonly' : '' ?>" type="text" inputmode="decimal" id="<?= e($name) ?>" name="<?= e($name) ?>" value="<?= e(trim(str_replace('$', '', (string) ($values[$name] ?? '')))) ?>" required<?= $selectedLocked ? ' readonly' : '' ?>>
</div>
<?php else: ?>
<input class="form-control certificate-detail-field<?= $selectedLocked ? ' field-readonly' : '' ?>" type="<?= e($type) ?>" id="<?= e($name) ?>" name="<?= e($name) ?>" value="<?= e((string) ($values[$name] ?? '')) ?>" required<?= $selectedLocked ? ' readonly' : '' ?>>
<?php endif; ?>
</div><?php endforeach; ?></div></div>
<?php endforeach; ?>

<div id="completed-certificate-note" class="completed-certificate-note" <?= $selectedLocked ? '' : 'hidden' ?>>
    <strong>Certificat déjà imprimé.</strong>
    <span>Les informations sont affichées en lecture seule afin d’éviter toute modification accidentelle.</span>
    <a id="completed-certificate-link" class="btn-secondary-app" href="<?= e($selectedViewUrl !== '' ? $selectedViewUrl : '#') ?>" target="_blank" rel="noopener" <?= $selectedViewUrl !== '' ? '' : 'hidden' ?>>Voir le certificat</a>
</div>

<div class="form-actions"><a href="<?= e(app_route('dashboard.php')) ?>" class="btn-secondary-app">← Tableau de bord</a><button class="btn-primary-app inline-btn" id="review-certificate-button" type="submit"<?= $selectedLocked ? ' disabled' : '' ?>>Vérifier les informations →</button></div>
</div>
</form></section>
<?php page_end(); ?>
