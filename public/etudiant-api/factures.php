<?php
/** GET /api/factures.php — factures de l'étudiant connecté + KPIs */
require_once __DIR__ . '/_bootstrap.php';
api_method('GET');
$u = api_require_etu();

$stmt = db()->prepare(
    "SELECT id, numero, type, libelle, description,
            montant_ttc_cents, devise, tva_taux,
            statut_paiement, date_emission, date_echeance, paye_at
     FROM factures
     WHERE etudiant_id = ? AND visible_etudiant = 1
     ORDER BY date_emission DESC, id DESC"
);
$stmt->execute([$u['id']]);
$factures = $stmt->fetchAll();

$totalDu = 0; $totalPaye = 0;
foreach ($factures as $f) {
    if (in_array($f['statut_paiement'], ['en_attente', 'partiellement_payee'], true)) {
        $totalDu += (int)$f['montant_ttc_cents'];
    } elseif ($f['statut_paiement'] === 'payee') {
        $totalPaye += (int)$f['montant_ttc_cents'];
    }
}

api_json([
    'factures' => $factures,
    'kpis' => [
        'total_du_cents'   => $totalDu,
        'total_paye_cents' => $totalPaye,
        'count'            => count($factures),
    ],
]);
