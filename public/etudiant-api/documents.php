<?php
/** GET /api/documents.php — documents publiés (stub minimal) */
require_once __DIR__ . '/_bootstrap.php';
api_method('GET');
$u = api_require_etu();

$stmt = db()->prepare(
    "SELECT id, type, libelle, date_emission, created_at
     FROM documents
     WHERE etudiant_id = ? AND visible_etudiant = 1 AND statut = 'publie'
     ORDER BY date_emission DESC, id DESC"
);
$stmt->execute([$u['id']]);
api_json(['documents' => $stmt->fetchAll()]);
