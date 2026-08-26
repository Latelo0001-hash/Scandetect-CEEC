<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/storage.php';
require_auth();
$checks = [];
foreach (['certificates','generated','verification'] as $dir) {
    try { $path=storage_dir($dir); $checks[$dir]=['path'=>$path,'ok'=>is_dir($path)&&is_writable($path)]; }
    catch (Throwable $e) { $checks[$dir]=['path'=>'','ok'=>false]; }
}
$routes = ['dashboard.php','certificat.php','certificate-data.php','confirmation.php','generate-pdf.php','view-certificate.php','request-otp.php','verify-otp.php','save-generated-pdf.php','print-certificate-once.php','mark-printed.php'];
page_start('Diagnostic');
?>
<section class="page-hero compact"><div class="container"><p class="eyebrow light">Diagnostic</p><h1>État du serveur</h1><p>Contrôle rapide de l’installation locale ou en ligne.</p></div></section>
<section class="container section-space">
<div class="card-panel" style="padding:24px">
<h2>Environnement</h2>
<p><strong>PHP :</strong> <?= e(PHP_VERSION) ?></p>
<p><strong>Chemin détecté :</strong> <code><?= e(app_base_path() === '' ? '/' : app_base_path()) ?></code></p>
<p><strong>URL de l’application :</strong> <code><?= e(app_url()) ?></code></p>
<h2>Dossiers de stockage</h2>
<?php foreach ($checks as $name=>$check): ?><p><?= $check['ok'] ? '✅' : '❌' ?> <strong><?= e($name) ?></strong> — <?= $check['ok'] ? 'écriture autorisée' : 'écriture impossible' ?></p><?php endforeach; ?>
<h2>Fichiers essentiels</h2>
<?php foreach ($routes as $route): ?><p><?= is_file(__DIR__.'/'.$route) ? '✅' : '❌' ?> <code><?= e($route) ?></code> → <code><?= e(app_route($route)) ?></code></p><?php endforeach; ?>
<div class="form-actions"><a class="btn-secondary-app" href="<?= e(app_route('dashboard.php')) ?>">← Tableau de bord</a><a class="btn-primary-app inline-btn" href="<?= e(app_route('certificat.php')) ?>">Tester le formulaire →</a></div>
</div></section>
<?php page_end(); ?>
