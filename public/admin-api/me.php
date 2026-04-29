<?php
/** GET /api/me.php → { user: {username} } ou 401 */
require_once __DIR__ . '/_bootstrap.php';
api_method('GET');
api_require_admin();
api_json(['user' => ['username' => admin_current_user()]]);
