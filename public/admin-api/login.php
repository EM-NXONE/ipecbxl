<?php
/** POST /api/login.php — { username, password } → 200 { user } | 401 */
require_once __DIR__ . '/_bootstrap.php';
api_method('POST');

$body = api_body();
$user = trim((string)($body['username'] ?? ''));
$pass = (string)($body['password'] ?? '');

if ($user === '' || $pass === '') {
    api_error('Identifiants requis', 400);
}

// Léger délai contre le timing
if (!isset(ADMIN_USERS[$user]) || !password_verify($pass, ADMIN_USERS[$user])) {
    usleep(random_int(200000, 400000));
    api_error('Identifiants invalides', 401);
}

session_regenerate_id(true);
$_SESSION['admin_user'] = $user;
$_SESSION['admin_login_at'] = time();

api_json(['user' => ['username' => $user]]);
