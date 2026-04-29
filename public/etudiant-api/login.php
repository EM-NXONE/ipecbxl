<?php
/**
 * POST /api/login.php
 * Body: { email, prenom, nom, date_naissance, password }
 *  → { user: {id, email, prenom, nom, numero_etudiant} }
 */
require_once __DIR__ . '/_bootstrap.php';
api_method('POST');

if (!etu_rate_limit('login', 8, 600)) api_error('Trop de tentatives. Réessaie dans quelques minutes.', 429);

$body = api_body();
$email         = trim(strtolower((string)($body['email'] ?? '')));
$prenom        = trim((string)($body['prenom'] ?? ''));
$nom           = trim((string)($body['nom'] ?? ''));
$dateNaissance = trim((string)($body['date_naissance'] ?? ''));
$password      = (string)($body['password'] ?? '');

if ($email === '' || $prenom === '' || $nom === '' || $dateNaissance === '' || $password === '') {
    api_error('E-mail, identité complète et mot de passe requis.', 400);
}

$pdo = db();
$stmt = $pdo->prepare(
    "SELECT * FROM etudiants
     WHERE email = ?
       AND LOWER(TRIM(prenom)) = LOWER(TRIM(?))
       AND LOWER(TRIM(nom))    = LOWER(TRIM(?))
       AND date_naissance      = ?
     LIMIT 1"
);
$stmt->execute([$email, $prenom, $nom, $dateNaissance]);
$etu = $stmt->fetch();

$generic = 'Identifiants invalides ou compte non activé.';
if (!$etu || !$etu['password_hash'] || !password_verify($password, $etu['password_hash'])) {
    api_error($generic, 401);
}
if ($etu['statut'] !== 'actif') {
    api_error('Ce compte est suspendu. Contacte admission@ipec.school.', 403);
}

if (password_needs_rehash($etu['password_hash'], PASSWORD_BCRYPT)) {
    $pdo->prepare("UPDATE etudiants SET password_hash=? WHERE id=?")
        ->execute([password_hash($password, PASSWORD_BCRYPT), (int)$etu['id']]);
}

$pdo->prepare("UPDATE etudiants SET derniere_connexion=NOW(), derniere_ip=? WHERE id=?")
    ->execute([$_SERVER['REMOTE_ADDR'] ?? null, (int)$etu['id']]);

etu_session_create((int)$etu['id']);
etu_log_action((int)$etu['id'], 'login', 'OK');

api_json(['user' => [
    'id'              => (int)$etu['id'],
    'email'           => (string)$etu['email'],
    'prenom'          => (string)$etu['prenom'],
    'nom'             => (string)$etu['nom'],
    'civilite'        => (string)($etu['civilite'] ?? ''),
    'numero_etudiant' => (string)($etu['numero_etudiant'] ?? ''),
]]);
