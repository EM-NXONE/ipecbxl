<?php
/** GET /api/dashboard.php → dashboard étudiant complet */
require_once __DIR__ . '/_bootstrap.php';
api_method('GET');
$u = api_require_etudiant();
$pdo = db();

// Candidatures rattachées
$cStmt = $pdo->prepare(
    "SELECT id, reference, statut, programme, annee, specialisation,
            annee_academique, rentree, created_at,
            facture_numero, facture_payee
     FROM candidatures WHERE etudiant_id = ?
     ORDER BY created_at DESC"
);
$cStmt->execute([$u['id']]);
$candidatures = $cStmt->fetchAll();

// --- Agrégats factures ----------------------------------------------------
// Total dû    = somme des factures visibles, hors annulées/remboursées
// Total payé  = somme des factures visibles dont statut_paiement='payee'
// Nb factures = toutes les factures visibles (sert au KPI "Factures")
// Nb ouvertes = factures en attente / partiellement payées (compat)
$aStmt = $pdo->prepare("
    SELECT
        COUNT(*)                                                        AS nb_total,
        COALESCE(SUM(CASE WHEN statut_paiement IN ('en_attente','partiellement_payee','payee')
                          THEN montant_ttc_cents ELSE 0 END), 0)        AS total_du,
        COALESCE(SUM(CASE WHEN statut_paiement = 'payee'
                          THEN montant_ttc_cents ELSE 0 END), 0)        AS total_paye,
        SUM(CASE WHEN statut_paiement IN ('en_attente','partiellement_payee')
                 THEN 1 ELSE 0 END)                                     AS nb_ouvertes,
        COALESCE(SUM(CASE WHEN statut_paiement IN ('en_attente','partiellement_payee')
                          THEN montant_ttc_cents ELSE 0 END), 0)        AS solde_du
    FROM factures
    WHERE etudiant_id = ? AND visible_etudiant = 1
");
$aStmt->execute([$u['id']]);
$agg = $aStmt->fetch() ?: [
    'nb_total' => 0, 'total_du' => 0, 'total_paye' => 0,
    'nb_ouvertes' => 0, 'solde_du' => 0,
];

// Compteur documents
$nStmt = $pdo->prepare("SELECT COUNT(*) FROM documents
                        WHERE etudiant_id=? AND visible_etudiant=1 AND statut='publie'");
$nStmt->execute([$u['id']]);
$nbDocs = (int)$nStmt->fetchColumn();

// Dernières factures
$fStmt = $pdo->prepare("SELECT * FROM factures
                        WHERE etudiant_id=? AND visible_etudiant=1
                        ORDER BY date_emission DESC, id DESC LIMIT 5");
$fStmt->execute([$u['id']]);
$lastFact = $fStmt->fetchAll();

// Normalisation d'affichage pour les frais de dossier (cf. /api/factures.php)
foreach ($lastFact as &$f) {
    if (($f['type'] ?? '') === 'frais_dossier') {
        $f['libelle'] = 'Frais de dossier IPEC';
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

// Derniers documents
$dStmt = $pdo->prepare("SELECT * FROM documents
                        WHERE etudiant_id=? AND visible_etudiant=1 AND statut='publie'
                        ORDER BY date_emission DESC, id DESC LIMIT 5");
$dStmt->execute([$u['id']]);
$lastDocs = $dStmt->fetchAll();

// Pour les documents recap_candidature, on expose la référence candidature
// (cf. /api/documents.php) plutôt que la référence interne IPEC-DOC-...
foreach ($lastDocs as &$d) {
    if (($d['template'] ?? '') === 'recap_candidature' && !empty($d['data_json'])) {
        $tmp = json_decode($d['data_json'], true);
        if (is_array($tmp) && !empty($tmp['reference'])) {
            $d['reference'] = (string)$tmp['reference'];
        }
    }
    unset($d['data_json']);
}
unset($d);

api_json([
    // Profil light pour l'en-tête (le React utilise déjà /me.php, c'est en bonus)
    'etudiant' => [
        'id'               => (int)$u['id'],
        'numero_etudiant'  => $u['numero_etudiant'] ?? null,
        'prenom'           => $u['prenom'] ?? null,
        'nom'              => $u['nom'] ?? null,
        'email'            => $u['email'] ?? null,
    ],
    'kpis' => [
        // Nouveaux champs attendus par le React
        'total_du_cents'        => (int)$agg['total_du'],
        'total_paye_cents'      => (int)$agg['total_paye'],
        'nb_factures'           => (int)$agg['nb_total'],
        'nb_documents'          => $nbDocs,

        // Champs historiques conservés pour rétro-compat (admin/PHP)
        'numero_etudiant'       => $u['numero_etudiant'] ?? null,
        'nb_candidatures'       => count($candidatures),
        'statut_dossier'        => $candidatures[0]['statut'] ?? null,
        'solde_du_cents'        => (int)$agg['solde_du'],
        'nb_factures_ouvertes'  => (int)$agg['nb_ouvertes'],
    ],
    'candidatures'   => $candidatures,
    'last_factures'  => $lastFact,
    'last_documents' => $lastDocs,
]);
