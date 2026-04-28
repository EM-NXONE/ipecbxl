<?php
/**
 * IPEC — Espace étudiant : demande de réinitialisation
 *
 * Pour éviter de fuiter l'existence des comptes : message générique
 * dans tous les cas. Le token est journalisé côté serveur ; l'envoi
 * e-mail réel sera branché quand le pipeline mailer "étudiant" sera
 * configuré (pour l'instant le lien apparaît dans error_log).
 */
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/_layout.php';

$done  = false;
$error = null;
$pdo   = db();

function etudiant_find_by_email(PDO $pdo, string $email): ?array {
    $stmt = $pdo->prepare("SELECT * FROM etudiants WHERE email = ? LIMIT 1");
    $stmt->execute([trim(strtolower($email))]);
    $row = $stmt->fetch();
    return $row ?: null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        etu_csrf_check();
        if (!etu_rate_limit('reset', 5, 600)) {
            throw new RuntimeException('Trop de demandes. Réessaie dans quelques minutes.');
        }
        $email = trim(strtolower((string)($_POST['email'] ?? '')));
        if ($email === '') throw new RuntimeException('E-mail requis.');

        $etu = etudiant_find_by_email($pdo, $email);
        if ($etu && $etu['statut'] === 'actif') {
            $token = etu_create_or_reset_token($pdo, (int)$etu['id']);
            error_log('[etudiant] reset link for ' . $email . ' : ' . etu_absolute_url('/reset-mot-de-passe.php?token=' . $token));
            etu_log_action((int)$etu['id'], 'request_reset');
        }
        $done = true;
    } catch (\Throwable $e) {
        $error = $e->getMessage();
    }
}

/** Helper local : crée un token de reset (1 h). */
function etu_create_or_reset_token(PDO $pdo, int $etudiantId): string {
    $token = bin2hex(random_bytes(32));
    $hash  = hash('sha256', $token);
    $exp   = date('Y-m-d H:i:s', time() + 3600);
    $pdo->prepare(
        "INSERT INTO etudiant_tokens (etudiant_id, type, token_hash, expires_at)
         VALUES (?, 'reset_password', ?, ?)"
    )->execute([$etudiantId, $hash, $exp]);
    return $token;
}

etu_layout_start('Mot de passe oublié');
?>
<div class="auth-card">
    <h1>Mot de passe oublié</h1>
    <p class="lede">Indique ton e-mail : si un compte existe, un lien de réinitialisation te sera envoyé.</p>

    <?php if ($error): ?><div class="flash flash-error"><?= etu_h($error) ?></div><?php endif; ?>
    <?php if ($done): ?>
        <div class="flash flash-success">
            Si un compte est associé à cette adresse, tu recevras un e-mail dans quelques minutes.
        </div>
        <p><a class="btn btn-secondary" href="<?= etu_url('/login.php') ?>">← Retour à la connexion</a></p>
    <?php else: ?>
        <form method="POST" novalidate>
            <input type="hidden" name="csrf" value="<?= etu_h(etu_csrf_token()) ?>">
            <div class="form-row">
                <label for="email">Adresse e-mail</label>
                <input id="email" type="email" name="email" autocomplete="email" required>
            </div>
            <button type="submit" style="width:100%;">Envoyer le lien</button>
        </form>
        <p style="margin-top:20px; text-align:center; font-size:13px;">
            <a href="<?= etu_url('/login.php') ?>">← Retour à la connexion</a>
        </p>
    <?php endif; ?>
</div>
<?php etu_layout_end();
