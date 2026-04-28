<?php
/**
 * IPEC — Espace étudiant : réinitialisation du mot de passe
 *  /etudiant/reset-mot-de-passe.php?token=...
 */
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/_layout.php';

$pdo   = db();
$token = (string)($_GET['token'] ?? $_POST['token'] ?? '');
$error = null;
$row   = etu_token_consume_check($pdo, $token, 'reset_password');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $row) {
    try {
        etu_csrf_check();
        if (!etu_rate_limit('reset_set', 6, 600)) {
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
            // Invalide toutes les sessions précédentes
            $pdo->prepare("DELETE FROM etudiant_sessions WHERE etudiant_id=?")->execute([(int)$row['e_id']]);
            $pdo->commit();
        } catch (\Throwable $e) { $pdo->rollBack(); throw $e; }

        etu_log_action((int)$row['e_id'], 'reset_password', 'mot de passe réinitialisé');
        etu_set_flash('Mot de passe mis à jour. Tu peux te connecter.', 'success');
        header('Location: login.php'); exit;
    } catch (\Throwable $e) {
        $error = $e->getMessage();
    }
}

etu_layout_start('Réinitialiser mon mot de passe');
?>
<div class="auth-card">
    <?php if (!$row): ?>
        <h1>Lien invalide</h1>
        <p class="lede">Ce lien de réinitialisation est invalide, expiré ou déjà utilisé.</p>
        <p><a class="btn btn-secondary" href="<?= etu_url('/mot-de-passe-oublie.php') ?>">Demander un nouveau lien</a></p>
    <?php else: ?>
        <h1>Nouveau mot de passe</h1>
        <p class="lede">Pour <?= etu_h($row['email']) ?>, choisis un nouveau mot de passe.</p>

        <?php if ($error): ?><div class="flash flash-error"><?= etu_h($error) ?></div><?php endif; ?>

        <form method="POST" novalidate>
            <input type="hidden" name="csrf" value="<?= etu_h(etu_csrf_token()) ?>">
            <input type="hidden" name="token" value="<?= etu_h($token) ?>">
            <div class="form-row">
                <label for="password">Nouveau mot de passe</label>
                <input id="password" type="password" name="password" autocomplete="new-password" required>
                <div class="help">10 caractères min. avec majuscule, minuscule et chiffre.</div>
            </div>
            <div class="form-row">
                <label for="password2">Confirmer</label>
                <input id="password2" type="password" name="password2" autocomplete="new-password" required>
            </div>
            <button type="submit" style="width:100%;">Mettre à jour le mot de passe</button>
        </form>
    <?php endif; ?>
</div>
<?php etu_layout_end();
