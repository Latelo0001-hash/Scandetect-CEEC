<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/layout.php';

if (!empty($_SESSION['user'])) {
    redirect('dashboard.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    $user = filter_var($email, FILTER_VALIDATE_EMAIL) ? authenticate_user($email, $password) : null;

    if ($user) {
        session_regenerate_id(true);
        $_SESSION['user'] = (string) $user['email'];
        $_SESSION['user_name'] = (string) $user['name'];
        redirect('dashboard.php');
    }
    $error = 'Adresse e-mail ou mot de passe incorrect.';
}

page_start('Connexion', false);
?>
<section class="login-page">
    <div class="login-panel login-visual">
        <div class="visual-content">
            <img class="login-logo" src="<?= e(app_route('PNG/ScanDetect-CEEC - Logo-07.png')) ?>" alt="ScanDetect par CEEC">
            <p class="eyebrow light">Scandetect par CEEC</p>
            <h1>Escorte numérique certifiée de vos minéraux</h1>
        </div>
    </div>
    <div class="login-panel login-form-wrap">
        <form class="login-card" method="post" action="<?= e(app_route('login.php')) ?>">
            <a class="login-brand" href="<?= e(app_route('index.php')) ?>"><img src="<?= e(app_route('PNG/ScanDetect-CEEC - Logo-03.png')) ?>" alt="ScanDetect par CEEC"></a>
            <p class="eyebrow">Espace sécurisé</p>
            <h2>Bienvenue</h2>
            <p class="muted">Connectez-vous pour générer des certificats</p>
            <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <label for="email">Adresse e-mail :</label>
            <input class="form-control" type="email" id="email" name="email" autocomplete="email" required autofocus>
            <label for="password">Mot de passe :</label>
            <div class="password-field">
                <input class="form-control" type="password" id="password" name="password" autocomplete="current-password" required>
                <button class="password-toggle" type="button" data-password-toggle="password" aria-label="Afficher le mot de passe" aria-pressed="false">
                    <svg class="password-eye" viewBox="0 0 24 24" aria-hidden="true"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.7"/><path class="password-eye-slash" d="m4 4 16 16"/></svg>
                    <span class="password-toggle-label">Afficher</span>
                </button>
            </div>
            <button class="btn-primary-app" type="submit">Se connecter <span>→</span></button>
        </form>
    </div>
</section>
<?php page_end(); ?>
