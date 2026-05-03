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
// Total dû    = solde restant à payer (en_attente + partiellement_payee)
// Total payé  = somme des factures dont statut_paiement='payee'
// Nb factures = toutes les factures visibles (KPI "Factures")
// Nb ouvertes = factures en attente / partiellement payées (compat)
$aStmt = $pdo->prepare("
    SELECT
        COUNT(*)                                                        AS nb_total,
        COALESCE(SUM(CASE WHEN statut_paiement IN ('en_attente','partiellement_payee')
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

// Derniers documents (joint la candidature pour récupérer la référence IPEC-CAND-...)
$dStmt = $pdo->prepare("SELECT d.*, c.reference AS candidature_reference
                        FROM documents d
                        LEFT JOIN candidatures c ON c.id = d.candidature_id
                        WHERE d.etudiant_id=? AND d.visible_etudiant=1 AND d.statut='publie'
                        ORDER BY d.date_emission DESC, d.id DESC LIMIT 5");
$dStmt->execute([$u['id']]);
$lastDocs = $dStmt->fetchAll();

// Nettoyage d'affichage des récapitulatifs de candidature (cf. /api/documents.php) :
// on n'affiche QUE la référence IPEC-CAND-... (jamais la référence interne IPEC-DOC-...).
foreach ($lastDocs as &$d) {
    if (($d['template'] ?? '') === 'recap_candidature') {
        $d['titre'] = 'Récapitulatif de candidature';
        if (!empty($d['candidature_reference'])) {
            $d['reference'] = $d['candidature_reference'];
        }
    }
    unset($d['data_json'], $d['candidature_reference']);
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
