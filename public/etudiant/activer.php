<?php
/**
 * IPEC — Espace étudiant : activation du compte (premier mot de passe)
 *  /etudiant/activer.php?token=...
 */
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/_layout.php';

$pdo   = db();
$token = (string)($_GET['token'] ?? $_POST['token'] ?? '');
$error = null;
$row   = etu_token_consume_check($pdo, $token, 'activation');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $row) {
    try {
        etu_csrf_check();
        if (!etu_rate_limit('activate', 6, 600)) {
            throw new RuntimeException('Trop de tentatives. Réessaie plus tard.');
        }
        $pwd  = (string)($_POST['password'] ?? '');
        $pwd2 = (string)($_POST['password2'] ?? '');
        if ($pwd !== $pwd2) throw new RuntimeException('Les deux mots de passe ne correspondent pas.');
        if ($err = etu_password_validate($pwd)) throw new RuntimeException($err);

        $hash = password_hash($pwd, PASSWORD_BCRYPT);
        $pdo->beginTransaction();
        try {
            $pdo->prepare("UPDATE etudiants SET password_hash=?, email_verifie=1 WHERE id=?")
                ->execute([$hash, (int)$row['e_id']]);
            etu_token_mark_used($pdo, (int)$row['id']);
            $pdo->commit();
        } catch (\Throwable $e) { $pdo->rollBack(); throw $e; }

        etu_session_create((int)$row['e_id']);
        etu_log_action((int)$row['e_id'], 'activate', 'compte activé');
        etu_set_flash('Bienvenue ' . $row['prenom'] . ' ! Ton compte est activé.', 'success');
        header('Location: index.php'); exit;
    } catch (\Throwable $e) {
        $error = $e->getMessage();
    }
}

etu_layout_start('Activer mon compte');
?>
<div class="auth-card">
    <?php if (!$row): ?>
        <h1>Lien invalide</h1>
        <p class="lede">Ce lien d'activation est invalide, expiré ou déjà utilisé.</p>
        <p>Contacte l'administration : <a href="mailto:admission@ipec.school">admission@ipec.school</a>.</p>
        <p style="margin-top:20px;"><a class="btn btn-secondary" href="<?= etu_url('/login.php') ?>">Retour à la connexion</a></p>
    <?php else: ?>
        <h1>Activer mon compte</h1>
        <p class="lede">Bonjour <?= etu_h($row['prenom']) ?>, choisis un mot de passe pour activer ton espace étudiant.</p>

        <?php if ($error): ?><div class="flash flash-error"><?= etu_h($error) ?></div><?php endif; ?>

        <form method="POST" novalidate>
            <input type="hidden" name="csrf" value="<?= etu_h(etu_csrf_token()) ?>">
            <input type="hidden" name="token" value="<?= etu_h($token) ?>">
            <div class="form-row">
                <label>E-mail du compte</label>
                <input type="email" value="<?= etu_h($row['email']) ?>" disabled>
            </div>
            <div class="form-row">
                <label for="password">Nouveau mot de passe</label>
                <input id="password" type="password" name="password" autocomplete="new-password" required>
                <div class="help">10 caractères min. avec majuscule, minuscule et chiffre.</div>
            </div>
            <div class="form-row">
                <label for="password2">Confirmer le mot de passe</label>
                <input id="password2" type="password" name="password2" autocomplete="new-password" required>
            </div>
            <button type="submit" style="width:100%;">Activer mon compte</button>
        </form>
    <?php endif; ?>
</div>
<?php etu_layout_end();
