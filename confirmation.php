<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/fields.php';
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/storage.php';
require_once __DIR__ . '/includes/representatives.php';
require_auth();

$fields = certificate_fields();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['certificate_number'])) {
    verify_csrf();
    $draft = [];
    foreach ($fields as [$name]) {
        $draft[$name] = trim((string) ($_POST[$name] ?? ''));
        if ($draft[$name] === '') {
            $_SESSION['draft'] = $draft;
            $_SESSION['draft_token'] = strtolower(trim((string) ($_POST['record_token'] ?? '')));
            flash_error('Toutes les 30 rubriques doivent être complétées.');
            redirect('certificat.php');
        }
    }

    if (!representative_is_allowed('ceec_representative', (string) $draft['ceec_representative'])
        || !representative_is_allowed('mines_representative', (string) $draft['mines_representative'])) {
        $_SESSION['draft'] = $draft;
        flash_error('Veuillez sélectionner les représentants dans les listes autorisées.');
        redirect('certificat.php');
    }

    $number = (string) $draft['certificate_number'];
    if (!in_array($number, listed_certificate_numbers(), true)) {
        flash_error('Le numéro de certificat sélectionné ne figure pas dans la liste autorisée.');
        redirect('certificat.php');
    }

    $state = certificate_processing_status($number);
    if ($state['status'] === 'green') {
        flash_error('Ce certificat est déjà imprimé. Il est disponible en consultation uniquement.');
        redirect('certificat.php');
    }

    $postedToken = strtolower(trim((string) ($_POST['record_token'] ?? '')));
    $expectedToken = (string) ($state['token'] ?? '');
    if ($postedToken !== '' && (!valid_public_token($postedToken) || $postedToken !== $expectedToken)) {
        flash_error('La référence du certificat a changé. Veuillez le sélectionner à nouveau.');
        redirect('certificat.php');
    }

    $_SESSION['draft'] = $draft;
    $_SESSION['draft_token'] = $expectedToken;
    unset($_SESSION['certificate_otp'], $_SESSION['otp_verified']);
}

if (empty($_SESSION['draft'])) {
    redirect('certificat.php');
}

$draft = (array) $_SESSION['draft'];
$mineRepresentative = (string) ($draft['mines_representative'] ?? '');
page_start('Vérifiez les informations');
?>
<section class="page-hero compact"><div class="container"><a class="back-link" href="<?= e(app_route('certificat.php')) ?>">← Modifier le formulaire</a><p class="eyebrow light">Étape finale</p><h1>Vérifiez les informations</h1><p>Après validation par OTP, le certificat final sera généré sur le nouveau modèle officiel avec ses trois codes QR.</p></div></section>
<section class="container form-container">
<div class="review-note"><strong>Vérifiez avant de valider</strong><span>Le responsable de mine devra confirmer cette opération avec un code reçu par e-mail.</span></div>
<div class="review-card">
    <div class="review-header"><div><span class="eyebrow">Certificat</span><h2><?= e((string) $draft['certificate_number']) ?></h2></div><span class="status-badge">À valider</span></div>
    <div class="review-grid">
    <?php foreach ($fields as $index => [$name, $fr]): ?><div class="review-item"><span><?= $index + 1 ?>. <?= e($fr) ?></span><strong><?= e(display_certificate_value($name, (string) $draft[$name])) ?></strong></div><?php endforeach; ?>
    </div>
</div>

<div class="form-actions">
    <a class="btn-secondary-app" href="<?= e(app_route('certificat.php')) ?>">← Modifier le formulaire</a>
    <a class="btn-secondary-app" href="<?= e(app_route('dashboard.php')) ?>">Annuler</a>
    <button class="btn-primary-app inline-btn" type="button" id="validate-otp-start" data-csrf="<?= e(csrf_token()) ?>" data-request-url="<?= e(app_route('request-otp.php')) ?>" data-verify-url="<?= e(app_route('verify-otp.php')) ?>">Valider</button>
</div>

<form id="final-generation-form" method="post" action="<?= e(app_route('generate-pdf.php')) ?>" hidden>
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
</form>
</section>

<div class="otp-modal" id="otp-modal" hidden aria-hidden="true">
    <div class="otp-modal-backdrop" data-otp-close></div>
    <div class="otp-modal-card" role="dialog" aria-modal="true" aria-labelledby="otp-modal-title">
        <button class="otp-modal-close" type="button" data-otp-close aria-label="Fermer">×</button>
        <div class="otp-icon">✉</div>
        <p class="eyebrow">Validation sécurisée</p>
        <h2 id="otp-modal-title">Code de confirmation</h2>
        <p>Un même code OTP à 6 chiffres est envoyé aux adresses de test configurées pour le responsable de mine sélectionné :</p>
        <strong class="otp-responsible-name"><?= e($mineRepresentative) ?></strong>
        <p id="otp-recipient" class="otp-recipient">Préparation de l’envoi…</p>

        <form id="otp-form" autocomplete="off">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <label for="otp-code">Code OTP</label>
            <input class="form-control otp-code-input" id="otp-code" name="otp" type="text" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" placeholder="000000" required>
            <div id="otp-message" class="otp-message" aria-live="polite"></div>
            <button class="btn-primary-app otp-confirm-button" type="submit" disabled>Confirmer et générer le certificat</button>
        </form>
        <button class="otp-resend" id="otp-resend" type="button" disabled>Renvoyer le code</button>
    </div>
</div>

<script src="<?= e(app_route('js/otp-confirmation.js')) ?>"></script>
<?php page_end(); ?>
