<?php
require_once __DIR__ . '/includes/router.php';
dispatch_clean_route();

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/layout.php';

page_start('Accueil', false);
?>
<section class="home-shell">
    <header class="public-header container">
        <a class="public-brand" href="<?= e(app_route('index.php')) ?>"><img src="<?= e(app_route('PNG/ScanDetect-CEEC - Logo-04.png')) ?>" alt="ScanDetect par CEEC"></a>
        <a class="btn-outline-app" href="<?= e(app_route('login.php')) ?>">Se connecter</a>
    </header>

    <div class="home-hero">
        <div class="home-hero-media" aria-hidden="true"></div>
        <div class="home-hero-overlay"></div>
        <div class="container home-hero-content">
            <div class="home-copy">
                <p class="eyebrow">scandetect par ceec</p>
                <h1>Escorte numérique certifiée de vos minéraux</h1>
                <p class="home-intro">Bienvenue chez ScanDetect-CEEC, où nous renforçons la sécurité de l'émission des certificats d'origine à l'exportation. Notre objectif est de lutter contre la fraude minière et d'accompagner nos clients grâce à un tableau de bord numérique certifié et mis à jour en temps réel, permettant de suivre leurs minéraux. ScanDetect assure le suivi et l'escorte de vos minéraux hors de la RDC en toute sécurité.</p>
                <a class="btn-primary-app inline-btn home-cta" href="<?= e(app_route('login.php')) ?>">accéder à l’espace sécurisé <span>→</span></a>
            </div>
            <aside class="home-visual-card">
                <img src="<?= e(app_route('images/home-tracking-square-2026.png?v=20260825')) ?>" alt="Professionnel congolais vérifiant un camion avec la solution ScanDetect-CEEC">
                <div><strong>ScanDetect</strong><span>Suivi et escorte numérique certifiée</span></div>
            </aside>
        </div>
    </div>
</section>
<?php page_end(); ?>
