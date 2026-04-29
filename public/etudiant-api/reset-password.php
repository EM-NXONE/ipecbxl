<?php
/** Stub — POST /api/reset-password.php { token, password } */
require_once __DIR__ . '/_bootstrap.php';
api_method('POST');

$body = api_body();
$token = (string)($body['token'] ?? '');
$pwd = (string)($body['password'] ?? '');

if ($err = etu_password_validate($pwd)) api_error($err, 400);

$pdo = db();
$row = etu_token_consume_check($pdo, $token, 'reset');
if (!$row) api_error('Lien invalide ou expiré.', 400);

$hash = password_hash($pwd, PASSWORD_BCRYPT);
$pdo->prepare("UPDATE etudiants SET password_hash = ? WHERE id = ?")
    ->execute([$hash, (int)$row['e_id']]);
etu_token_mark_used($pdo, (int)$row['id']);
etu_log_action((int)$row['e_id'], 'password_reset', 'api');

api_json(['ok' => true]);
