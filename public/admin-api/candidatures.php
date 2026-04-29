<?php
/** GET /api/candidatures.php → liste (stub initial, à enrichir) */
require_once __DIR__ . '/_bootstrap.php';
api_method('GET');
api_require_admin();

$pdo = db();
$rows = $pdo->query(
    "SELECT id, reference, prenom, nom, email, statut, programme, annee,
            facture_payee, created_at
     FROM candidatures
     ORDER BY created_at DESC
     LIMIT 200"
)->fetchAll();

api_json(['candidatures' => $rows]);
