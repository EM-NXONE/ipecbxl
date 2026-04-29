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

// Factures ouvertes
$oStmt = $pdo->prepare(
    "SELECT COUNT(*) AS n, COALESCE(SUM(montant_ttc_cents),0) AS s
     FROM factures
     WHERE etudiant_id = ? AND visible_etudiant=1
       AND statut_paiement IN ('en_attente','partiellement_payee')"
);
$oStmt->execute([$u['id']]);
$ouvertes = $oStmt->fetch() ?: ['n' => 0, 's' => 0];

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

// Derniers documents
$dStmt = $pdo->prepare("SELECT * FROM documents
                        WHERE etudiant_id=? AND visible_etudiant=1 AND statut='publie'
                        ORDER BY date_emission DESC, id DESC LIMIT 5");
$dStmt->execute([$u['id']]);
$lastDocs = $dStmt->fetchAll();

api_json([
    'kpis' => [
        'numero_etudiant' => $u['numero_etudiant'],
        'nb_candidatures' => count($candidatures),
        'statut_dossier'  => $candidatures[0]['statut'] ?? null,
        'solde_du_cents'  => (int)$ouvertes['s'],
        'nb_factures_ouvertes' => (int)$ouvertes['n'],
        'nb_documents'    => $nbDocs,
    ],
    'candidatures'   => $candidatures,
    'last_factures'  => $lastFact,
    'last_documents' => $lastDocs,
]);
