<?php
/**
 * POST /api/mot-de-passe-oublie.php
 * Body: { email, prenom, nom, date_naissance }
 *  → message générique (ne révèle pas l'existence du compte)
 *
 * Le lien de reset est journalisé via error_log pour debug ; l'envoi e-mail
 * réel sera ajouté quand on plug PHPMailer côté étudiant.
 */
require_once __DIR__ . '/_bootstrap.php';
api_method('POST');
if (!etu_rate_limit('reset', 5, 600)) api_error('Trop de demandes. Réessaie dans quelques minutes.', 429);

$body = api_body();
$email         = trim(strtolower((string)($body['email'] ?? '')));
$prenom        = trim((string)($body['prenom'] ?? ''));
$nom           = trim((string)($body['nom'] ?? ''));
$dateNaissance = trim((string)($body['date_naissance'] ?? ''));

if ($email === '' || $prenom === '' || $nom === '' || $dateNaissance === '') {
    api_error('E-mail, prénom, nom et date de naissance requis.', 400);
}

$pdo = db();
$stmt = $pdo->prepare(
    "SELECT id, statut FROM etudiants
     WHERE email = ?
       AND LOWER(TRIM(prenom)) = LOWER(TRIM(?))
       AND LOWER(TRIM(nom))    = LOWER(TRIM(?))
       AND date_naissance      = ?
     LIMIT 1"
);
$stmt->execute([$email, $prenom, $nom, $dateNaissance]);
$etu = $stmt->fetch();

if ($etu && $etu['statut'] === 'actif') {
    $token = bin2hex(random_bytes(32));
    $pdo->prepare(
        "INSERT INTO etudiant_tokens (etudiant_id, type, token_hash, expires_at)
         VALUES (?, 'reset_password', ?, ?)"
    )->execute([(int)$etu['id'], hash('sha256', $token), date('Y-m-d H:i:s', time() + 3600)]);
    error_log('[etudiant-api] reset link for ' . $email . ' : ' . etu_absolute_url('/etudiant/reset/' . $token));
    etu_log_action((int)$etu['id'], 'request_reset');
}

api_json([
    'ok' => true,
    'message' => "Si un compte est associé à cette adresse, tu recevras un e-mail dans quelques minutes.",
]);
