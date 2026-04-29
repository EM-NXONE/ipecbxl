<?php
/** GET /api/me.php → 200 { user } | 401 */
require_once __DIR__ . '/_bootstrap.php';
api_method('GET');

$u = etu_current();
if (!$u) api_error('Non authentifié', 401);

api_json([
    'user' => [
        'id'              => $u['id'],
        'email'           => $u['email'],
        'prenom'          => $u['prenom'],
        'nom'             => $u['nom'],
        'civilite'        => $u['civilite'] ?? '',
        'numero_etudiant' => $u['numero_etudiant'] ?? '',
    ],
]);
