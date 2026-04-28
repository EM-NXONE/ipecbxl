<?php
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/_layout.php';

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = trim((string)($_POST['user'] ?? ''));
    $pass = (string)($_POST['pass'] ?? '');

    if ($user === '' || $pass === '') {
        $error = 'Identifiants requis.';
    } elseif (!isset(ADMIN_USERS[$user])) {
        usleep(random_int(200000, 400000));
        $error = 'Identifiants invalides.';
    } elseif (!password_verify($pass, ADMIN_USERS[$user])) {
        usleep(random_int(200000, 400000));
        $error = 'Identifiants invalides.';
    } else {
        session_regenerate_id(true);
        $_SESSION['admin_user'] = $user;
        $_SESSION['admin_login_at'] = time();
        header('Location: index.php');
        exit;
    }
}

if (admin_is_logged_in()) {
    header('Location: index.php');
    exit;
}

admin_layout_start('Connexion');
?>
<style>
.login-wrap {
    min-height: calc(100vh - 240px);
    display: flex; align-items: center; justify-content: center;
    padding: 24px 0;
}
.login-card {
    width: 100%; max-width: 420px;
    padding: 36px 32px;
}
.login-brand {
    display: flex; flex-direction: column; align-items: center;
    gap: 14px; margin-bottom: 28px;
    text-align: center;
}
.login-brand .logo { color: var(--primary); }
.login-brand .name {
    font-family: var(--font-display);
    font-size: 28px; font-weight: 400; letter-spacing: -0.02em;
    color: var(--ink); line-height: 1;
}
.login-brand .sub {
    font-size: 10px; text-transform: uppercase; letter-spacing: 0.22em;
    color: var(--muted);
}
.login-card h1 {
    font-size: 20px; text-align: center; margin: 0 0 6px;
}
.login-card .lead {
    text-align: center; color: var(--muted); font-size: 13px;
    margin: 0 0 24px;
}
</style>
<div class="login-wrap">
    <div class="card login-card">
        <div class="login-brand">
            <?= admin_logo_svg('logo', 56) ?>
            <div>
                <div class="name">IPEC</div>
                <div class="sub">Institut Privé des Études Commerciales</div>
            </div>
        </div>
        <h1>Espace administration</h1>
        <p class="lead">Accès restreint au personnel autorisé.</p>
        <?php if ($error): ?>
            <div class="flash flash-error"><?= admin_h($error) ?></div>
        <?php endif; ?>
        <form method="POST" autocomplete="off">
            <div class="form-row">
                <label for="user">Identifiant</label>
                <input type="text" id="user" name="user" required autofocus>
            </div>
            <div class="form-row">
                <label for="pass">Mot de passe</label>
                <input type="password" id="pass" name="pass" required>
            </div>
            <button type="submit" style="width:100%;justify-content:center;padding:11px;">Se connecter</button>
        </form>
    </div>
</div>
<?php admin_layout_end();
