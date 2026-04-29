<?php
/** GET /api/me.php → 200 { user } | 401 */
require_once __DIR__ . '/_bootstrap.php';
api_method('GET');

if (!admin_is_logged_in()) {
    api_error('Non authentifié', 401);
}
api_json(['user' => ['username' => admin_current_user()]]);
