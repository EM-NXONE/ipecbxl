<?php
/** GET /api/me.php */
require_once __DIR__ . '/_bootstrap.php';
api_method('GET');
$u = api_require_etudiant();
unset($u['session_token']);
api_json(['user' => $u]);
