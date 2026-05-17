<?php
/**
 * POST /api/test-email.php  { to: "email@…" }
 *
 * Envoi de test depuis admission@ipec.school avec archivage dans le dossier
 * Sent (IMAP). Renvoie un rapport détaillé pour diagnostic.
 */
require_once __DIR__ . '/_bootstrap.php';
api_method('POST');
api_require_admin();

// Charger _etu_notify.php (packagé dans _shared/ en prod, ou public/ en dev).
$candidates = [
    __DIR__ . '/_shared/_etu_notify.php',
    __DIR__ . '/../_etu_notify.php',
];
$loaded = false;
foreach ($candidates as $p) {
    if (is_file($p)) { require_once $p; $loaded = true; break; }
}
if (!$loaded || !function_exists('etu_notify_send_test_email')) {
    api_error('Librairie de notification introuvable.', 500);
}

$body = api_body();
$to   = trim((string)($body['to'] ?? ''));
if ($to === '') api_error('Champ "to" requis.', 400);

$report = etu_notify_send_test_email($to);
api_json($report, $report['ok'] ? 200 : 200);
