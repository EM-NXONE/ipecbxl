<?php
/** GET /api/factures.php → liste complète des factures de l'étudiant */
require_once __DIR__ . '/_bootstrap.php';
api_method('GET');
$u = api_require_etudiant();

$stmt = db()->prepare(
    "SELECT f.id, f.numero, f.type, f.libelle, f.description,
            f.montant_ht_cents, f.tva_taux, f.montant_ttc_cents, f.devise,
            f.date_emission, f.date_echeance,
            f.statut_paiement, f.paye_at, f.moyen_paiement, f.reference_paiement,
            f.created_at, f.updated_at,
            f.annee_academique, f.etape_cursus,
            f.annee_academique AS candidature_annee_academique
     FROM factures f
     WHERE f.etudiant_id=? AND f.visible_etudiant=1
     ORDER BY f.annee_academique DESC, f.date_emission DESC, f.id DESC"
);
$stmt->execute([$u['id']]);
$factures = $stmt->fetchAll();

// Normalisation d'affichage pour les frais de dossier (anciennes factures
// pouvant porter l'ancien libellé verbeux).
foreach ($factures as &$f) {
    if (($f['type'] ?? '') === 'frais_dossier') {
        $f['libelle'] = 'Frais de dossier IPEC';
        // Tente d'extraire la référence candidature de l'ancienne description
        $ref = '';
        if (!empty($f['description']) && preg_match('/IPEC-CAND-\d{4}-[A-F0-9]+/i', (string)$f['description'], $m)) {
            $ref = strtoupper($m[0]);
        }
        $f['description'] = $ref
            ? 'Traitement de la candidature ' . $ref
            : 'Traitement de votre candidature';
    }
}
unset($f);

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
