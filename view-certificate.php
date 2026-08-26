<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/fields.php';
require_once __DIR__ . '/includes/storage.php';
require_once __DIR__ . '/includes/layout.php';
require_auth();
$id = strtolower(trim((string) ($_GET['id'] ?? '')));
$record = valid_public_token($id) ? load_certificate_record($id) : null;
if (!$record) { flash_error('Certificat introuvable.'); redirect('dashboard.php'); }
$fields = certificate_fields();
$certificate = (array) ($record['data'] ?? []);
$number = (string) ($certificate['certificate_number'] ?? '');
$publicPdfUrl = app_url('certificate-pdf.php?id=' . rawurlencode($id) . '&type=verification');
$numberLength = max(1, strlen($number));
$leftNumberFontMm = $numberLength <= 7 ? 15.8 : max(9.8, 15.8 * (7 / $numberLength));
$rightNumberFontMm = $numberLength <= 7 ? 8.8 : max(5.8, 8.8 * (7 / $numberLength));
page_start('Voir le certificat', true, ['css/certificate.css?v=20260825e']);
?>
<section class="page-hero compact"><div class="container"><p class="eyebrow light">Consultation</p><h1>Certificat No. <?= e($number) ?></h1><p>Aperçu en lecture seule. Aucune impression n’est disponible depuis cette page.</p></div></section>
<section class="container certificate-result-section readonly-certificate-view">
<div class="readonly-print-warning" aria-hidden="true"><h1>Impression interdite</h1><p>Ce certificat est disponible uniquement en consultation. L’impression unique a déjà été enregistrée par ScanDetect.</p></div>
<div class="certificate-scroll"><article class="certificate-stage" aria-label="Certificat complet généré — résultat PDF 3">
<img class="certificate-template" src="<?= e(app_route('images/certificate-template-pdf2-clean.png?v=20260825b')) ?>" alt="Certificat complet généré avec le fond PDF 2 et les éléments variables">
<div class="certificate-overlay">
<div class="certificate-label-correction" aria-label="Correction de l’intitulé anglais de la question 29">CEEC Representative:</div>
<?php foreach ($fields as $index => [$name]): $top=64.59+($index*4.70); $value=display_certificate_value($name,(string)($certificate[$name]??'')); ?>
<div class="certificate-value" style="top:<?= number_format($top,2,'.','') ?>mm"><?= e($value) ?></div>
<?php endforeach; ?>
<div class="certificate-number-mask certificate-number-mask-left"></div>
<div class="certificate-number-mask certificate-number-mask-stub"></div>
<div class="certificate-number-mask certificate-number-mask-right"></div>
<div class="certificate-number-copy certificate-number-left" style="font-size:<?= number_format($leftNumberFontMm,2,'.','') ?>mm"><?= e($number) ?></div>
<div class="certificate-number-copy certificate-number-stub" style="font-size:<?= number_format($rightNumberFontMm,2,'.','') ?>mm"><?= e($number) ?></div>
<div class="certificate-number-copy certificate-number-right" style="font-size:<?= number_format($rightNumberFontMm,2,'.','') ?>mm"><?= e($number) ?></div>
<div class="qr-slot qr-stub" data-qr-value="<?= e($publicPdfUrl) ?>" data-qr-color="#FFFFFF" data-qr-background="transparent" aria-label="QR code blanc du volet gauche"></div>
<div class="qr-slot qr-left" data-qr-value="<?= e($publicPdfUrl) ?>" data-qr-color="#2F3776" data-qr-background="#FFFFFF" aria-label="QR code bleu du corps du certificat"></div>
<div class="qr-slot qr-right" data-qr-value="<?= e($publicPdfUrl) ?>" data-qr-color="#FFFFFF" data-qr-background="transparent" aria-label="QR code blanc de la vignette droite"></div>
</div></article></div>
<div class="result-actions"><div><h2>Consultation uniquement</h2><p class="muted">Cet aperçu permet de vérifier le certificat sans autoriser une nouvelle impression.</p></div><div class="result-buttons"><a class="btn-secondary-app" href="<?= e(app_route('dashboard.php')) ?>">← Tableau de bord</a></div></div>
</section>
<script>
document.addEventListener('keydown', function (event) {
    if ((event.ctrlKey || event.metaKey) && String(event.key).toLowerCase() === 'p') {
        event.preventDefault();
        window.alert('Impression interdite : ce certificat est disponible uniquement en consultation.');
    }
});
</script>
<script src="<?= e(app_route('js/qrcode-browser.js')) ?>"></script>
<script src="<?= e(app_route('js/qr-render.js')) ?>"></script>
<?php page_end(); ?>
