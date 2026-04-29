<?php
/** GET /api/check-token.php?token=...&type=activation|reset_password
 *  Vérifie qu'un token est valide sans le consommer. */
require_once __DIR__ . '/_bootstrap.php';
api_method('GET');

$token = (string)($_GET['token'] ?? '');
$type  = (string)($_GET['type'] ?? '');
if ($token === '' || !in_array($type, ['activation', 'reset_password'], true)) {
    api_error('Paramètres invalides', 400);
}

$pdo = db();
$row = etu_token_consume_check($pdo, $token, $type);
if (!$row) api_json(['valid' => false]);

api_json([
    'valid' => true,
    'email' => (string)$row['email'],
    'prenom' => (string)$row['prenom'],
]);
