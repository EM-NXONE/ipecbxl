<?php
/** POST /api/activer.php { token, password } — type 'activation' */
require_once __DIR__ . '/_bootstrap.php';
api_method('POST');

if (!etu_rate_limit('activate', 6, 600)) {
    api_error('Trop de tentatives. Réessaie plus tard.', 429);
}

$body = api_body();
$token = (string)($body['token'] ?? '');
$pwd = (string)($body['password'] ?? '');

if ($token === '' || $pwd === '') api_error('Token et mot de passe requis.', 400);
if ($err = etu_password_validate($pwd)) api_error($err, 400);

$pdo = db();
$row = etu_token_consume_check($pdo, $token, 'activation');
if (!$row) api_error('Lien invalide, expiré ou déjà utilisé.', 400);

$hash = password_hash($pwd, PASSWORD_BCRYPT);
$pdo->beginTransaction();
try {
    $pdo->prepare("UPDATE etudiants SET password_hash = ?, email_verifie = 1 WHERE id = ?")
        ->execute([$hash, (int)$row['e_id']]);
    etu_token_mark_used($pdo, (int)$row['id']);
    $pdo->commit();
} catch (\Throwable $e) {
    $pdo->rollBack();
    api_error('Erreur lors de l\'activation.', 500);
}

etu_session_create((int)$row['e_id']);
etu_log_action((int)$row['e_id'], 'activate', 'api');

api_json([
    'ok' => true,
    'user' => [
        'id'     => (int)$row['e_id'],
        'email'  => (string)$row['email'],
        'prenom' => (string)$row['prenom'],
    ],
]);
