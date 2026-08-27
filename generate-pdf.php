<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/fields.php';
require_once __DIR__ . '/includes/storage.php';
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/otp.php';
require_auth();

$fields = certificate_fields();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if (empty($_SESSION['draft'])) redirect('certificat.php');

    $draft = (array) $_SESSION['draft'];
    if (!otp_is_verified_for_draft($draft)) {
        flash_error('La génération du certificat nécessite la validation du code OTP envoyé au responsable de mine.');
        redirect('confirmation.php');
    }
    $number = trim((string) ($draft['certificate_number'] ?? ''));
    $existingToken = strtolower(trim((string) ($_SESSION['draft_token'] ?? '')));
    $existingRecord = $existingToken !== '' && valid_public_token($existingToken) ? load_certificate_record($existingToken) : null;
    if ($existingRecord && trim((string) ($existingRecord['printed_at'] ?? '')) !== '') {
        otp_consume_verification();
        unset($_SESSION['draft'], $_SESSION['draft_token']);
        flash_error('Ce certificat est déjà imprimé et verrouillé. Il est disponible uniquement en consultation.');
        redirect('dashboard.php');
    }

    // Si le numéro a été commencé auparavant (orange), on poursuit le même dossier
    // au lieu de créer un doublon. Un numéro rouge reçoit un nouveau jeton.
    $token = $existingRecord ? $existingToken : bin2hex(random_bytes(16));

    try {
        $createdAt = $existingRecord ? (string) ($existingRecord['created_at'] ?? gmdate('c')) : gmdate('c');
        $createdBy = $existingRecord ? (string) ($existingRecord['created_by'] ?? ($_SESSION['user'] ?? '')) : (string) ($_SESSION['user'] ?? '');
        save_certificate_record($token, [
            'token' => $token,
            'created_at' => $createdAt,
            'updated_at' => gmdate('c'),
            'created_by' => $createdBy,
            'otp_validated_at' => gmdate('c'),
            'otp_validated_by' => (string) (($_SESSION['otp_verified']['representative'] ?? '')),
            'otp_recipient' => (string) (($_SESSION['otp_verified']['recipient'] ?? '')),
            'data' => $draft,
        ]);

        // Toute modification d'un dossier en cours invalide les anciens PDF,
        // qui seront recréés avec les nouvelles informations.
        if ($existingRecord) {
            $oldVerification = verification_pdf_path($token);
            $oldPrint = generated_pdf_path($token);
            if (is_file($oldVerification)) @unlink($oldVerification);
            if (is_file($oldPrint)) @unlink($oldPrint);
        }

        otp_consume_verification();
        unset($_SESSION['draft'], $_SESSION['draft_token']);
        $destination = 'generate-pdf.php?id=' . urlencode($token);
        redirect($destination);
    } catch (Throwable $e) {
        flash_error('La génération a échoué : ' . $e->getMessage());
        redirect('certificat.php');
    }
}

$id = strtolower(trim((string) ($_GET['id'] ?? '')));
$record = load_certificate_record($id);
if (!$record) redirect('dashboard.php');

$isPrinted = trim((string) ($record['printed_at'] ?? '')) !== '';
$certificate = (array) ($record['data'] ?? []);
$publicPdfUrl = app_url('certificate-pdf.php?id=' . rawurlencode($id) . '&type=verification');
$number = (string) ($certificate['certificate_number'] ?? '');
$numberLength = max(1, strlen($number));
$leftNumberFontMm = $numberLength <= 7 ? 15.8 : max(9.8, 15.8 * (7 / $numberLength));
$rightNumberFontMm = $numberLength <= 7 ? 8.8 : max(5.8, 8.8 * (7 / $numberLength));

page_start('Certificat généré', true, ['css/certificate.css?v=20260825e']);
?>
<section class="page-hero compact"><div class="container"><p class="eyebrow light"><?= $isPrinted ? 'Certificat imprimé' : 'Certificat généré' ?></p><h1>Certificat No. <?= e($number) ?></h1><p><?= $isPrinted ? 'Ce certificat est verrouillé et disponible uniquement en consultation.' : 'Félicitations, vous avez généré le certificat avec succès.' ?></p></div></section>

<?php if ($isPrinted): ?>
<section class="container certificate-result-section">
    <div class="printed-lock-card">
        <div>
            <p class="eyebrow">Statut</p>
            <h2>Certificat imprimé</h2>
            <p>La première impression a été enregistrée. Aucune nouvelle génération, téléchargement d’impression ou réimpression n’est autorisé depuis ScanDetect.</p>
        </div>
        <div class="result-buttons">
            <a class="btn-primary-app inline-btn" href="<?= e(app_route('view-certificate.php?id=' . rawurlencode($id))) ?>" target="_blank" rel="noopener">Voir le certificat</a>
            <a class="btn-secondary-app" href="<?= e(app_route('dashboard.php')) ?>">← Tableau de bord</a>
        </div>
    </div>
</section>
<?php page_end(); exit; endif; ?>

<section class="container certificate-result-section">
    <div class="certificate-result-copy">
        <p>Vérifiez que le papier contenant le certificat correspondant, avec le numéro de certificat approprié, est correctement inséré dans l'imprimante avant de lancer l'impression.</p>
    </div>

    <div class="certificate-scroll">
        <article class="certificate-stage" id="certificate-to-download" aria-label="Aperçu du certificat sur le nouveau modèle officiel">
            <img class="certificate-template certificate-template-pdf2" src="<?= e(app_route('images/certificate-template-pdf2-clean.png?v=20260825b')) ?>" alt="PDF 2 — aperçu du papier officiel avec étiquettes bleues">
            <img class="certificate-template certificate-template-pdf1" src="<?= e(app_route('images/certificate-template-pdf1.png?v=20260825a')) ?>" alt="PDF 1 — page 1 exacte du modèle à quatre pages">
            <div class="certificate-overlay">
                <div class="certificate-label-correction" aria-label="Correction de l’intitulé anglais de la question 29">CEEC Representative:</div>
                <?php foreach ($fields as $index => [$name]):
                    $top = 64.59 + ($index * 4.70);
                    $value = display_certificate_value($name, (string) ($certificate[$name] ?? ''));
                ?>
                    <div class="certificate-value" style="top:<?= number_format($top, 2, '.', '') ?>mm"><?= e($value) ?></div>
                <?php endforeach; ?>

                <!-- Le nouveau modèle contient un numéro d’exemple à trois emplacements fixes.
                     Les masques permettent de le remplacer proprement par le numéro réel. -->
                <div class="certificate-number-mask certificate-number-mask-left" aria-hidden="true"></div>
                <div class="certificate-number-mask certificate-number-mask-stub" aria-hidden="true"></div>
                <div class="certificate-number-mask certificate-number-mask-right" aria-hidden="true"></div>

                <!-- Numéro réel : ligne 1 du tableau + vertical + volet gauche + vignette droite. -->
                <div class="certificate-number-copy certificate-number-left" style="font-size:<?= number_format($leftNumberFontMm, 2, '.', '') ?>mm"><?= e($number) ?></div>
                <div class="certificate-number-copy certificate-number-stub" style="font-size:<?= number_format($rightNumberFontMm, 2, '.', '') ?>mm"><?= e($number) ?></div>
                <div class="certificate-number-copy certificate-number-right" style="font-size:<?= number_format($rightNumberFontMm, 2, '.', '') ?>mm"><?= e($number) ?></div>

                <!-- Les QR suivent le modèle officiel : blancs sur les étiquettes bleues, bleu sur le fond blanc. -->
                <div class="qr-slot qr-stub" data-qr-value="<?= e($publicPdfUrl) ?>" data-qr-color="#FFFFFF" data-qr-background="#2F3776" aria-label="QR code blanc du volet gauche sur fond bleu"></div>
                <div class="qr-slot qr-left" data-qr-value="<?= e($publicPdfUrl) ?>" data-qr-color="#2F3776" data-qr-background="#FFFFFF" aria-label="QR code bleu du corps du certificat"></div>
                <div class="qr-slot qr-right" data-qr-value="<?= e($publicPdfUrl) ?>" data-qr-color="#FFFFFF" data-qr-background="#2F3776" aria-label="QR code blanc de la vignette droite sur fond bleu"></div>
            </div>
        </article>
    </div>

    <div class="result-actions">
        <div>
            <h2>Imprimez le certificat</h2>
            <p id="pdf-status" class="muted">Le certificat est prêt à être préparé pour l’impression.</p>
        </div>
        <div class="result-buttons">
            <span id="download-pdf" hidden data-certificate-number="<?= e($number) ?>" data-record-id="<?= e($id) ?>" data-csrf="<?= e(csrf_token()) ?>" data-auto-download="0" data-save-url="<?= e(app_route('save-generated-pdf.php')) ?>" data-mark-printed-url="<?= e(app_route('mark-printed.php')) ?>" data-dashboard-url="<?= e(app_route('dashboard.php')) ?>"></span>
            <a class="btn-secondary-app" href="<?= e(app_route('dashboard.php')) ?>">← Retour au tableau de bord</a>
            <button class="btn-primary-app inline-btn" type="button" id="print-certificate" disabled>Imprimer le certificat →</button>
        </div>
    </div>
</section>

<div class="paper-verification-modal" id="paper-verification-modal" hidden aria-hidden="true">
    <div class="paper-verification-backdrop" data-paper-modal-close></div>
    <div class="paper-verification-card" role="dialog" aria-modal="true" aria-labelledby="paper-verification-title">
        <button class="paper-verification-close" type="button" data-paper-modal-close aria-label="Fermer">×</button>
        <div class="paper-verification-icon" aria-hidden="true">✓</div>
        <p class="eyebrow">Contrôle avant impression</p>
        <h2 id="paper-verification-title">Vérification du papier officiel</h2>
        <p class="paper-verification-help">Insérez le papier officiel correspondant au certificat, puis saisissez le numéro visible sur ce papier.</p>
        <div class="paper-expected-number">
            <span>Numéro attendu</span>
            <strong id="paper-expected-number"><?= e($number) ?></strong>
        </div>
        <form id="paper-verification-form" autocomplete="off">
            <label for="paper-number-input">Numéro inscrit sur le papier</label>
            <input class="form-control paper-number-input" id="paper-number-input" type="text" inputmode="numeric" autocomplete="off" placeholder="Ex. <?= e($number) ?>" required>
            <div class="paper-verification-error" id="paper-verification-error" role="alert" hidden></div>
            <label class="paper-print-confirmation">
                <input id="paper-print-confirmation" type="checkbox" required>
                <span>Je confirme que ce papier est placé dans l’imprimante et que cette impression unique est définitive.</span>
            </label>
            <div class="paper-verification-actions">
                <button class="btn-secondary-app" type="button" data-paper-modal-close>Annuler</button>
                <button class="btn-primary-app inline-btn" type="submit">Confirmer et imprimer →</button>
            </div>
        </form>
    </div>
</div>

<script src="<?= e(app_route('js/qrcode-browser.js')) ?>"></script>
<script src="<?= e(app_route('js/qr-render.js')) ?>"></script>
<script src="<?= e(app_route('js/html2pdf.bundle.min.js')) ?>"></script>
<script src="<?= e(app_route('js/certificate-download.js?v=20260827g')) ?>"></script>
<?php page_end(); ?>
