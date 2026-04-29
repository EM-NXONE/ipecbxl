<?php
/**
 * GET  /api/check-token.php?token=...&type=activation|reset_password
 *   → { valid: bool, email?, prenom? }
 *
 * Sert à pré-vérifier un token côté React avant d'afficher le formulaire.
 */
require_once __DIR__ . '/_bootstrap.php';
api_method('GET');

$token = (string)($_GET['token'] ?? '');
$type  = (string)($_GET['type'] ?? '');
if (!in_array($type, ['activation', 'reset_password'], true)) api_error('type invalide', 400);

$row = etu_token_consume_check(db(), $token, $type);
if (!$row) api_json(['valid' => false]);

api_json([
    'valid'  => true,
    'email'  => $row['email'],
    'prenom' => $row['prenom'],
    'nom'    => $row['nom'],
]);
