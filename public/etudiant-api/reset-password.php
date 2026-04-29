<?php
/** POST /api/reset-password.php { token, password } — utilise type 'reset_password' */
require_once __DIR__ . '/_bootstrap.php';
api_method('POST');

if (!etu_rate_limit('reset_set', 6, 600)) {
    api_error('Trop de tentatives. Réessaie plus tard.', 429);
}

$body = api_body();
$token = (string)($body['token'] ?? '');
$pwd = (string)($body['password'] ?? '');

if ($token === '' || $pwd === '') api_error('Token et mot de passe requis.', 400);
if ($err = etu_password_validate($pwd)) api_error($err, 400);

$pdo = db();
$row = etu_token_consume_check($pdo, $token, 'reset_password');
if (!$row) api_error('Lien invalide, expiré ou déjà utilisé.', 400);

$hash = password_hash($pwd, PASSWORD_BCRYPT);
$pdo->beginTransaction();
try {
    $pdo->prepare("UPDATE etudiants SET password_hash = ?, email_verifie = 1 WHERE id = ?")
        ->execute([$hash, (int)$row['e_id']]);
    etu_token_mark_used($pdo, (int)$row['id']);
    // Invalide toutes les sessions
    $pdo->prepare("DELETE FROM etudiant_sessions WHERE etudiant_id = ?")->execute([(int)$row['e_id']]);
    $pdo->commit();
} catch (\Throwable $e) {
    $pdo->rollBack();
    api_error('Erreur lors de la mise à jour.', 500);
}

etu_log_action((int)$row['e_id'], 'reset_password', 'api');
api_json(['ok' => true, 'email' => $row['email']]);
