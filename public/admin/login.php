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
        // Délai constant pour éviter le timing-attack sur l'existence du compte
        usleep(random_int(200000, 400000));
        $error = 'Identifiants invalides.';
    } elseif (!password_verify($pass, ADMIN_USERS[$user])) {
        usleep(random_int(200000, 400000));
        $error = 'Identifiants invalides.';
    } else {
        // Auth OK — régénère l'ID de session pour parer la fixation
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
<div style="max-width:400px;margin:60px auto;">
    <div class="card">
        <h1 style="margin-bottom:6px;">Connexion admin</h1>
        <p class="muted" style="margin:0 0 20px;font-size:12px;">Accès restreint au personnel IPEC.</p>
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
            <button type="submit" style="width:100%;">Se connecter</button>
        </form>
    </div>
</div>
<?php admin_layout_end();
