<?php
/** POST /api/change-password.php { current, password } */
require_once __DIR__ . '/_bootstrap.php';
api_method('POST');
$u = api_require_etu();

if (!etu_rate_limit('change_pwd', 8, 600)) {
    api_error('Trop de tentatives. Réessaie plus tard.', 429);
}

$body = api_body();
$cur  = (string)($body['current'] ?? '');
$pwd  = (string)($body['password'] ?? '');

if ($cur === '' || $pwd === '') api_error('Mot de passe actuel et nouveau requis.', 400);

$pdo = db();
$stmt = $pdo->prepare("SELECT password_hash FROM etudiants WHERE id = ? LIMIT 1");
$stmt->execute([$u['id']]);
$row = $stmt->fetch();
if (!$row || !password_verify($cur, (string)$row['password_hash'])) {
    api_error('Mot de passe actuel incorrect.', 400);
}
if ($err = etu_password_validate($pwd)) api_error($err, 400);

$pdo->prepare("UPDATE etudiants SET password_hash = ? WHERE id = ?")
    ->execute([password_hash($pwd, PASSWORD_BCRYPT), $u['id']]);

// Conserve la session courante, invalide les autres
$token = $u['session_token'] ?? null;
if ($token) {
    $pdo->prepare("DELETE FROM etudiant_sessions WHERE etudiant_id = ? AND id <> ?")
        ->execute([$u['id'], $token]);
} else {
    $pdo->prepare("DELETE FROM etudiant_sessions WHERE etudiant_id = ?")
        ->execute([$u['id']]);
}
etu_log_action($u['id'], 'change_password', 'api');

api_json(['ok' => true]);
