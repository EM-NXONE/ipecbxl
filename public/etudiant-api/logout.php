<?php
/** POST /api/logout.php */
require_once __DIR__ . '/_bootstrap.php';
api_method('POST');
etu_session_destroy();
api_json(['ok' => true]);
