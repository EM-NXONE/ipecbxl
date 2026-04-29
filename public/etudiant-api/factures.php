<?php
/** GET /api/factures.php → liste complète des factures de l'étudiant */
require_once __DIR__ . '/_bootstrap.php';
api_method('GET');
$u = api_require_etudiant();

$stmt = db()->prepare(
    "SELECT * FROM factures
     WHERE etudiant_id=? AND visible_etudiant=1
     ORDER BY date_emission DESC, id DESC"
);
$stmt->execute([$u['id']]);
$factures = $stmt->fetchAll();

$totalDu = 0; $totalPaye = 0;
foreach ($factures as $f) {
    if (in_array($f['statut_paiement'], ['en_attente','partiellement_payee'], true)) {
        $totalDu += (int)$f['montant_ttc_cents'];
    } elseif ($f['statut_paiement'] === 'payee') {
        $totalPaye += (int)$f['montant_ttc_cents'];
    }
}

api_json([
    'factures' => $factures,
    'totaux' => [
        'du_cents'   => $totalDu,
        'paye_cents' => $totalPaye,
        'count'      => count($factures),
    ],
]);
