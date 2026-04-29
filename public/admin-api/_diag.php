<?php
/**
 * GET /api/_diag.php — diagnostic temporaire (à supprimer après usage).
 * N'inclut PAS _bootstrap.php pour éviter le fatal qu'on cherche à diagnostiquer.
 * Liste les fichiers de _shared/ et tente le require de chacun en attrapant les erreurs.
 */
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$shared = __DIR__ . '/_shared';
$expected = [
    'db_config.php',
    'mailer.php',
    '_etudiants.php',
    '_pdf_classes.php',
    'cors.php',
    'admin_users.php',
    'ipec-logo-email.png',
    'FPDF/fpdf.php',
    'PHPMailer/src/PHPMailer.php',
];

$out = [
    'php_version' => PHP_VERSION,
    'shared_dir'  => $shared,
    'shared_exists' => is_dir($shared),
    'files' => [],
    'require_test' => [],
    'session_save_path' => session_save_path(),
    'session_save_path_writable' => is_writable(session_save_path() ?: sys_get_temp_dir()),
];

foreach ($expected as $rel) {
    $path = $shared . '/' . $rel;
    $out['files'][$rel] = [
        'exists'   => file_exists($path),
        'readable' => is_readable($path),
        'size'     => file_exists($path) ? filesize($path) : null,
    ];
}

// Test de chargement du bootstrap lui-même (capture toute sortie / erreur)
$bootstrapPath = __DIR__ . '/_bootstrap.php';
if (is_file($bootstrapPath)) {
    ob_start();
    $prevError = null;
    set_error_handler(function($no, $msg, $file, $line) use (&$prevError) {
        $prevError = "ERR $no: $msg @ $file:$line";
        return true;
    });
    try {
        // On ne peut pas réellement require _bootstrap (il enverrait headers/exit).
        // À la place, on lit son contenu et vérifie un php -l logique : token_get_all + parse.
        $code = file_get_contents($bootstrapPath);
        $tokens = @token_get_all($code, TOKEN_PARSE);
        $out['bootstrap_parse'] = is_array($tokens) ? 'OK (' . count($tokens) . ' tokens)' : 'PARSE FAILED';
    } catch (\Throwable $e) {
        $out['bootstrap_parse'] = 'THROW: ' . $e->getMessage();
    }
    restore_error_handler();
    ob_end_clean();
    if ($prevError) $out['bootstrap_parse_error'] = $prevError;
} else {
    $out['bootstrap_parse'] = 'MISSING';
}

// Test require de db_config + admin_users (les deux suspects principaux)
foreach (['db_config.php', 'admin_users.php'] as $rel) {
    $path = $shared . '/' . $rel;
    if (!file_exists($path)) {
        $out['require_test'][$rel] = 'MISSING';
        continue;
    }
    try {
        $r = require $path;
        $out['require_test'][$rel] = is_array($r)
            ? 'OK (array, keys: ' . implode(',', array_keys($r)) . ')'
            : 'OK';
    } catch (\Throwable $e) {
        $out['require_test'][$rel] = 'THROW: ' . $e->getMessage();
    }
}

echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
