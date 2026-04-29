<?php
/** POST /api/logout.php */
require_once __DIR__ . '/_bootstrap.php';
api_method('POST');

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();

api_json(['ok' => true]);
