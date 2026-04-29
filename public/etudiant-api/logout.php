<?php
/** POST /api/logout.php */
require_once __DIR__ . '/_bootstrap.php';
api_method('POST');

$u = etu_current();
if ($u) etu_log_action($u['id'], 'logout', 'api');
etu_session_destroy();

api_json(['ok' => true]);
