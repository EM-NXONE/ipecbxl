<?php
/**
 * IPEC — Espace étudiant : page de connexion
 */
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/_layout.php';

// Déjà connecté ?
if ($u = etu_current()) {
    header('Location: index.php');
    exit;
}

$defaultNext = etu_url('/index.php');
$next  = (string)($_GET['next'] ?? $_POST['next'] ?? $defaultNext);
// Sécurité : on n'accepte que des redirections internes (relatives sous /etudiant/ OU /index.php, /factures.php, etc.)
if (!preg_match('#^/(?:etudiant/)?[A-Za-z0-9_./?=&-]*$#', $next)) {
    $next = $defaultNext;
}

$error = null;
$emailPrefill = '';
$nomPrefill = '';
$prenomPrefill = '';
$dateNaissancePrefill = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        etu_csrf_check();
        if (!etu_rate_limit('login', 8, 600)) {
            throw new RuntimeException('Trop de tentatives. Réessaie dans quelques minutes.');
        }
        $email = trim(strtolower((string)($_POST['email'] ?? '')));
        $prenom = trim((string)($_POST['prenom'] ?? ''));
        $nom = trim((string)($_POST['nom'] ?? ''));
        $dateNaissance = trim((string)($_POST['date_naissance'] ?? ''));
        $pwd   = (string)($_POST['password'] ?? '');
        $emailPrefill = $email;
        $prenomPrefill = $prenom;
        $nomPrefill = $nom;
        $dateNaissancePrefill = $dateNaissance;
        if ($email === '' || $prenom === '' || $nom === '' || $dateNaissance === '' || $pwd === '') {
            throw new RuntimeException('E-mail, identité complète et mot de passe requis.');
        }

        $stmt = db()->prepare("SELECT * FROM etudiants
                               WHERE email = ?
                                 AND LOWER(TRIM(prenom)) = LOWER(TRIM(?))
                                 AND LOWER(TRIM(nom)) = LOWER(TRIM(?))
                                 AND date_naissance = ?
                               LIMIT 1");
        $stmt->execute([$email, $prenom, $nom, $dateNaissance]);
        $etu = $stmt->fetch();

        // Message générique pour ne pas révéler l'existence du compte
        $genericFail = 'Identifiants invalides ou compte non activé.';
        if (!$etu || !$etu['password_hash'] || !password_verify($pwd, $etu['password_hash'])) {
            throw new RuntimeException($genericFail);
        }
        if ($etu['statut'] !== 'actif') {
            throw new RuntimeException('Ce compte est suspendu. Contacte admission@ipec.school.');
        }

        // Refresh hash si nécessaire
        if (password_needs_rehash($etu['password_hash'], PASSWORD_BCRYPT)) {
            $newHash = password_hash($pwd, PASSWORD_BCRYPT);
            db()->prepare("UPDATE etudiants SET password_hash=? WHERE id=?")->execute([$newHash, (int)$etu['id']]);
        }

        // Mise à jour suivi connexion
        db()->prepare("UPDATE etudiants SET derniere_connexion=NOW(), derniere_ip=? WHERE id=?")
            ->execute([$_SERVER['REMOTE_ADDR'] ?? null, (int)$etu['id']]);

        etu_session_create((int)$etu['id']);
        etu_log_action((int)$etu['id'], 'login', 'OK');

        header('Location: ' . $next);
        exit;
    } catch (\Throwable $e) {
        $error = $e->getMessage();
    }
}

etu_layout_start('Connexion');
?>
<div class="auth-card">
    <h1>Connexion</h1>
    <p class="lede">Accède à ton dossier, tes factures et tes documents administratifs.</p>

    <?php if ($error): ?>
        <div class="flash flash-error"><?= etu_h($error) ?></div>
    <?php endif; ?>

    <form method="POST" novalidate>
        <input type="hidden" name="csrf" value="<?= etu_h(etu_csrf_token()) ?>">
        <input type="hidden" name="next" value="<?= etu_h($next) ?>">
        <div class="form-row">
            <label for="email">Adresse e-mail</label>
            <input id="email" type="email" name="email" autocomplete="email" required value="<?= etu_h($emailPrefill) ?>">
        </div>
        <div class="form-row">
            <label for="prenom">Prénom de l'étudiant</label>
            <input id="prenom" type="text" name="prenom" autocomplete="given-name" required value="<?= etu_h($prenomPrefill) ?>">
        </div>
        <div class="form-row">
            <label for="nom">Nom de l'étudiant</label>
            <input id="nom" type="text" name="nom" autocomplete="family-name" required value="<?= etu_h($nomPrefill) ?>">
        </div>
        <div class="form-row">
            <label for="date_naissance">Date de naissance</label>
            <input id="date_naissance" type="date" name="date_naissance" autocomplete="bday" required value="<?= etu_h($dateNaissancePrefill) ?>">
        </div>
        <div class="form-row">
            <label for="password">Mot de passe</label>
            <input id="password" type="password" name="password" autocomplete="current-password" required>
        </div>
        <button type="submit" style="width:100%;">Se connecter</button>
    </form>

    <p style="margin-top:20px; text-align:center; font-size:13px;">
        <a href="<?= etu_url('/mot-de-passe-oublie.php') ?>">Mot de passe oublié ?</a>
    </p>
    <p style="text-align:center; font-size:12px; color:var(--muted); margin-top:24px;">
        Pas encore de compte ? Il est créé par l'administration de l'IPEC<br>
        après réception de ta candidature.
    </p>
</div>
<?php etu_layout_end();
