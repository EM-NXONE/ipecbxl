<?php
/** POST /api/mot-de-passe-oublie.php { email, prenom, nom, date_naissance } */
require_once __DIR__ . '/_bootstrap.php';
api_method('POST');

if (!etu_rate_limit('forgot', 5, 900)) {
    api_error('Trop de demandes. Réessaie plus tard.', 429);
}

$body = api_body();
$email = trim(strtolower((string)($body['email'] ?? '')));
$prenom = trim((string)($body['prenom'] ?? ''));
$nom = trim((string)($body['nom'] ?? ''));
$dn = trim((string)($body['date_naissance'] ?? ''));

if ($email === '' || $prenom === '' || $nom === '' || $dn === '') {
    api_error('E-mail, prénom, nom et date de naissance requis.', 400);
}

$pdo = db();
$stmt = $pdo->prepare(
    "SELECT * FROM etudiants
     WHERE email = ?
       AND LOWER(TRIM(prenom)) = LOWER(TRIM(?))
       AND LOWER(TRIM(nom))    = LOWER(TRIM(?))
       AND date_naissance = ?
     LIMIT 1"
);
$stmt->execute([$email, $prenom, $nom, $dn]);
$etu = $stmt->fetch();

// Réponse uniforme : ne révèle pas l'existence du compte
if ($etu && ($etu['statut'] ?? '') === 'actif') {
    $token = bin2hex(random_bytes(32));
    $hash  = hash('sha256', $token);
    $exp   = date('Y-m-d H:i:s', time() + 3600);
    $pdo->prepare(
        "INSERT INTO etudiant_tokens (etudiant_id, type, token_hash, expires_at)
         VALUES (?, 'reset_password', ?, ?)"
    )->execute([(int)$etu['id'], $hash, $exp]);
    error_log('[etudiant-api] reset link for ' . $email . ' : https://lms.ipec.school/reset/' . $token);
    etu_log_action((int)$etu['id'], 'request_reset', 'api');
}

api_json(['ok' => true]);
