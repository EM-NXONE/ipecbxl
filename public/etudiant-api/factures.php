<?php
/** GET /api/factures.php → liste complète des factures de l'étudiant */
require_once __DIR__ . '/_bootstrap.php';
api_method('GET');
$u = api_require_etudiant();

$stmt = db()->prepare(
    "SELECT id, numero, type, libelle, description,
            montant_ht_cents, tva_taux, montant_ttc_cents, devise,
            date_emission, date_echeance,
            statut_paiement, paye_at, moyen_paiement, reference_paiement,
            created_at, updated_at
     FROM factures
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
    // Forme attendue par le React (KPIs)
    'kpis' => [
        'total_du_cents'   => $totalDu,
        'total_paye_cents' => $totalPaye,
        'count'            => count($factures),
    ],
    // Rétro-compat (anciens consommateurs)
    'totaux' => [
        'du_cents'   => $totalDu,
        'paye_cents' => $totalPaye,
        'count'      => count($factures),
    ],
]);
