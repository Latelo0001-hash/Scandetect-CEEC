<?php
require_once __DIR__ . '/config.php';

function page_start(string $title, bool $authenticated = true, array $extraStyles = []): void
{
    $userName = (string) ($_SESSION['user_name'] ?? $_SESSION['user'] ?? '');
    ?><!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> | <?= e(APP_NAME) ?></title>
    <link rel="icon" type="image/png" sizes="64x64" href="<?= e(app_route('images/favicon.png?v=20260825')) ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= e(app_route('images/apple-touch-icon.png?v=20260825')) ?>">
    <link rel="stylesheet" href="<?= e(app_route('css/bootstrap.min.css')) ?>">
    <link rel="stylesheet" href="<?= e(app_route('css/app.css?v=20260826a')) ?>">
    <link rel="stylesheet" href="<?= e(app_route('css/accessibility.css?v=20260820c')) ?>">
    <?php foreach ($extraStyles as $style): ?>
    <link rel="stylesheet" href="<?= e(app_route((string) $style)) ?>">
    <?php endforeach; ?>
</head>
<body>
<?php if ($authenticated): ?>
<header class="app-header">
    <div class="container header-inner">
        <a class="brand" href="<?= e(app_route('dashboard.php')) ?>" aria-label="ScanDetect par CEEC">
            <img src="<?= e(app_route('images/scandetect-logo.png')) ?>" alt="ScanDetect par CEEC">
            <span class="brand-copy"><strong>ScanDetect par CEEC</strong><small>Escorte numérique certifiée de vos minéraux</small></span>
        </a>
        <div class="user-nav"><span><?= e($userName) ?></span><a href="<?= e(app_route('logout.php')) ?>">Déconnexion</a></div>
    </div>
</header>
<?php endif; ?>
<main><?= "\n" ?>
<?php
}

function page_end(): void
{
    ?></main>
<footer class="app-footer"><div class="container">© <?= date('Y') ?> ScanDetect par CEEC — Centre d’Expertise, d’Évaluation et de Certification</div></footer>
<script src="<?= e(app_route('js/app.js?v=20260820g')) ?>"></script>
</body>
</html><?php
}
