<?php
/** GET /api/factures.php — factures de l'étudiant connecté (stub minimal) */
require_once __DIR__ . '/_bootstrap.php';
api_method('GET');
$u = api_require_etu();

$stmt = db()->prepare(
    "SELECT id, numero, type, montant_ttc_cents, statut_paiement,
            date_emission, paye_at
     FROM factures
     WHERE etudiant_id = ? AND visible_etudiant = 1
     ORDER BY date_emission DESC, id DESC"
);
$stmt->execute([$u['id']]);
api_json(['factures' => $stmt->fetchAll()]);
