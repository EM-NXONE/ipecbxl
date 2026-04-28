<?php
/**
 * IPEC — Espace étudiant : profil + changement de mot de passe
 */
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/_layout.php';
$user = etu_require_login();
$pdo  = db();

$stmt = $pdo->prepare("SELECT * FROM etudiants WHERE id=?");
$stmt->execute([$user['id']]);
$etu = $stmt->fetch();

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['do'] ?? '') === 'change_password') {
    try {
        etu_csrf_check();
        $cur  = (string)($_POST['current'] ?? '');
        $pwd  = (string)($_POST['password'] ?? '');
        $pwd2 = (string)($_POST['password2'] ?? '');
        if (!password_verify($cur, $etu['password_hash'] ?? '')) {
            throw new RuntimeException('Mot de passe actuel incorrect.');
        }
        if ($pwd !== $pwd2) throw new RuntimeException('Les deux nouveaux mots de passe ne correspondent pas.');
        if ($err = etu_password_validate($pwd)) throw new RuntimeException($err);

        $pdo->prepare("UPDATE etudiants SET password_hash=? WHERE id=?")
            ->execute([password_hash($pwd, PASSWORD_BCRYPT), $user['id']]);
        // Conserve la session courante, invalide les autres
        $pdo->prepare("DELETE FROM etudiant_sessions WHERE etudiant_id=? AND id<>?")
            ->execute([$user['id'], $user['session_token']]);
        etu_log_action($user['id'], 'change_password');
        etu_set_flash('Mot de passe mis à jour. Les autres sessions ont été déconnectées.', 'success');
        header('Location: profil.php'); exit;
    } catch (\Throwable $e) {
        $error = $e->getMessage();
    }
}

etu_layout_start('Mon profil', $user);
?>
<h1>Mon profil</h1>
<p class="muted" style="margin-bottom:24px;">Tes informations personnelles et la sécurité de ton compte.</p>

<?php if ($error): ?><div class="flash flash-error"><?= etu_h($error) ?></div><?php endif; ?>

<div class="card">
    <h2>Identité</h2>
    <table>
        <tbody>
            <tr><th style="width:220px;">Numéro étudiant</th><td class="mono"><?= etu_h($etu['numero_etudiant'] ?: '—') ?></td></tr>
            <tr><th>Civilité</th><td><?= etu_h($etu['civilite'] ?: '—') ?></td></tr>
            <tr><th>Prénom · Nom</th><td><?= etu_h($etu['prenom']) ?> <?= etu_h($etu['nom']) ?></td></tr>
            <tr><th>Date de naissance</th><td><?= etu_h($etu['date_naissance'] ?: '—') ?></td></tr>
            <tr><th>Nationalité</th><td><?= etu_h($etu['nationalite'] ?: '—') ?></td></tr>
            <tr><th>E-mail</th><td><?= etu_h($etu['email']) ?></td></tr>
            <tr><th>Téléphone</th><td><?= etu_h($etu['telephone'] ?: '—') ?></td></tr>
        </tbody>
    </table>
    <p class="muted" style="margin-top:12px; font-size:12px;">
        Pour modifier ces informations, contacte <a href="mailto:admission@ipec.school">admission@ipec.school</a>.
    </p>
</div>

<div class="card">
    <h2>Changer mon mot de passe</h2>
    <form method="POST" novalidate style="max-width:480px;">
        <input type="hidden" name="csrf" value="<?= etu_h(etu_csrf_token()) ?>">
        <input type="hidden" name="do" value="change_password">
        <div class="form-row">
            <label for="current">Mot de passe actuel</label>
            <input id="current" type="password" name="current" autocomplete="current-password" required>
        </div>
        <div class="form-row">
            <label for="password">Nouveau mot de passe</label>
            <input id="password" type="password" name="password" autocomplete="new-password" required>
            <div class="help">10 caractères min. avec majuscule, minuscule et chiffre.</div>
        </div>
        <div class="form-row">
            <label for="password2">Confirmer</label>
            <input id="password2" type="password" name="password2" autocomplete="new-password" required>
        </div>
        <button type="submit">Mettre à jour</button>
    </form>
</div>

<?php etu_layout_end($user);
