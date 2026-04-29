<?php
/** POST /api/login.php — body: {username, password} */
require_once __DIR__ . '/_bootstrap.php';
api_method('POST');

$body = api_body();
$username = trim((string)($body['username'] ?? ''));
$password = (string)($body['password'] ?? '');
if ($username === '' || $password === '') api_error('Identifiant et mot de passe requis.', 400);

$users = admin_users();
$hash  = $users[$username] ?? null;
if (!$hash || !password_verify($password, $hash)) {
    usleep(random_int(200000, 400000));
    api_error('Identifiants invalides.', 401);
}

session_regenerate_id(true);
$_SESSION['admin_user']     = $username;
$_SESSION['admin_login_at'] = time();

api_json(['user' => ['username' => $username]]);
