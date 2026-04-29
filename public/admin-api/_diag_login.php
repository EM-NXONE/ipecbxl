<?php
/**
 * GET/POST /api/_diag_login.php?u=IPEC&p=motdepasse
 * Reproduit la logique de login.php en capturant TOUTE erreur (notice, warning, fatal).
 * À supprimer après diagnostic.
 */
declare(strict_types=1);

ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$out = ['steps' => []];
$step = function(string $name, $value = 'OK') use (&$out) {
    $out['steps'][] = [$name => $value];
};

// Capture fatale
register_shutdown_function(function() use (&$out) {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        $out['FATAL'] = $err;
        echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
});

set_error_handler(function($no, $msg, $file, $line) use (&$out) {
    $out['steps'][] = ['PHP_ERROR' => "[$no] $msg @ " . basename($file) . ":$line"];
    return false;
});

try {
    $step('start', PHP_VERSION);

    $shared = __DIR__ . '/_shared';
    $step('shared_dir', $shared);

    // 1) admin_users
    $usersFile = $shared . '/admin_users.php';
    $users = require $usersFile;
    $step('admin_users_loaded', is_array($users) ? array_keys($users) : 'NOT_ARRAY');

    // 2) cors
    if (is_file($shared . '/cors.php')) {
        require_once $shared . '/cors.php';
        $step('cors_loaded', function_exists('ipec_cors_apply') ? 'fn_present' : 'fn_missing');
    }

    // 3) db_config
    require_once $shared . '/db_config.php';
    $step('db_config_loaded', function_exists('db') ? 'db_fn_present' : 'db_fn_MISSING');

    // 4) Test DB connection
    if (function_exists('db')) {
        try {
            $pdo = db();
            $step('db_connect', $pdo instanceof PDO ? 'OK' : 'NOT_PDO');
        } catch (\Throwable $e) {
            $step('db_connect', 'THROW: ' . $e->getMessage());
        }
    }

    // 5) session_start
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_httponly', '1');
    session_name('IPEC_ADMIN_DIAG');
    @session_start();
    $step('session_start', session_status() === PHP_SESSION_ACTIVE ? 'ACTIVE' : 'INACTIVE');

    // 6) password_verify
    $u = $_GET['u'] ?? $_POST['u'] ?? 'IPEC';
    $p = $_GET['p'] ?? $_POST['p'] ?? '';
    $hash = $users[$u] ?? null;
    $step('hash_found', $hash ? ('len=' . strlen($hash) . ' prefix=' . substr($hash, 0, 4)) : 'NO_HASH');
    if ($hash && $p !== '') {
        $ok = password_verify($p, $hash);
        $step('password_verify', $ok ? 'TRUE' : 'FALSE');
    } else {
        $step('password_verify', 'SKIPPED (pass ?u=IPEC&p=YOURPASSWORD)');
    }

    $step('end', 'reached');
} catch (\Throwable $e) {
    $out['EXCEPTION'] = [
        'class' => get_class($e),
        'message' => $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine(),
    ];
}

echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
