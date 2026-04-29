<?php
/** GET /api/documents.php — documents publiés de l'étudiant connecté */
require_once __DIR__ . '/_bootstrap.php';
api_method('GET');
$u = api_require_etu();

$stmt = db()->prepare(
    "SELECT id, reference, type, titre, description,
            date_emission, valide_jusqu_au, template
     FROM documents
     WHERE etudiant_id = ? AND visible_etudiant = 1 AND statut = 'publie'
     ORDER BY date_emission DESC, id DESC"
);
$stmt->execute([$u['id']]);
api_json(['documents' => $stmt->fetchAll()]);
