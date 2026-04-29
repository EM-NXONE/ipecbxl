<?php
/**
 * POST /api/login.php
 * Body: { numero_etudiant, password }
 *  → { user: {id, email, prenom, nom, numero_etudiant} }
 */
require_once __DIR__ . '/_bootstrap.php';
api_method('POST');

if (!etu_rate_limit('login', 8, 600)) api_error('Trop de tentatives. Réessaie dans quelques minutes.', 429);

$body = api_body();
$numero   = trim((string)($body['numero_etudiant'] ?? ''));
$password = (string)($body['password'] ?? '');

if ($numero === '' || $password === '') {
    api_error('Numéro étudiant et mot de passe requis.', 400);
}

$pdo = db();
$stmt = $pdo->prepare("SELECT * FROM etudiants WHERE numero_etudiant = ? LIMIT 1");
$stmt->execute([$numero]);
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
