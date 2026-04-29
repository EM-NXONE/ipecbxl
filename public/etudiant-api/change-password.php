<?php
/**
 * POST /api/change-password.php
 * Body: { current, password, password2 }
 */
require_once __DIR__ . '/_bootstrap.php';
api_method('POST');
$u = api_require_etudiant();
if (!etu_rate_limit('change_pwd', 6, 600)) api_error('Trop de tentatives. Réessaie plus tard.', 429);

$body = api_body();
$cur  = (string)($body['current'] ?? '');
$pwd  = (string)($body['password'] ?? '');
$pwd2 = (string)($body['password2'] ?? '');

$pdo  = db();
$stmt = $pdo->prepare("SELECT password_hash FROM etudiants WHERE id=?");
$stmt->execute([$u['id']]);
$row = $stmt->fetch();
if (!$row || !password_verify($cur, $row['password_hash'] ?? '')) {
    api_error('Mot de passe actuel incorrect.', 400);
}
if ($pwd !== $pwd2) api_error('Les deux nouveaux mots de passe ne correspondent pas.', 400);
if ($err = etu_password_validate($pwd)) api_error($err, 400);

$pdo->prepare("UPDATE etudiants SET password_hash=? WHERE id=?")
    ->execute([password_hash($pwd, PASSWORD_BCRYPT), $u['id']]);
// Conserve la session courante, invalide les autres
$pdo->prepare("DELETE FROM etudiant_sessions WHERE etudiant_id=? AND id<>?")
    ->execute([$u['id'], $u['session_token']]);
etu_log_action($u['id'], 'change_password');

api_json(['ok' => true, 'message' => 'Mot de passe mis à jour. Les autres sessions ont été déconnectées.']);
