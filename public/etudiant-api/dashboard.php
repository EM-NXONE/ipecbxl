<?php
/** GET /api/dashboard.php → KPIs résumé pour la page d'accueil étudiant */
require_once __DIR__ . '/_bootstrap.php';
api_method('GET');
$u = api_require_etu();

$pdo = db();

// Solde et nb factures
$f = $pdo->prepare(
    "SELECT
        SUM(CASE WHEN statut_paiement IN ('en_attente','partiellement_payee') THEN montant_ttc_cents ELSE 0 END) AS du,
        SUM(CASE WHEN statut_paiement = 'payee' THEN montant_ttc_cents ELSE 0 END) AS paye,
        COUNT(*) AS total
     FROM factures WHERE etudiant_id = ? AND visible_etudiant = 1"
);
$f->execute([$u['id']]);
$rowF = $f->fetch() ?: ['du' => 0, 'paye' => 0, 'total' => 0];

// Documents publiés
$d = $pdo->prepare(
    "SELECT COUNT(*) AS n FROM documents
     WHERE etudiant_id = ? AND visible_etudiant = 1 AND statut = 'publie'"
);
$d->execute([$u['id']]);
$nbDocs = (int)($d->fetchColumn() ?: 0);

// Dernières factures (3) et derniers documents (3)
$lf = $pdo->prepare(
    "SELECT id, numero, libelle, montant_ttc_cents, statut_paiement, date_emission
     FROM factures WHERE etudiant_id = ? AND visible_etudiant = 1
     ORDER BY date_emission DESC, id DESC LIMIT 3"
);
$lf->execute([$u['id']]);

$ld = $pdo->prepare(
    "SELECT id, reference, type, titre, date_emission
     FROM documents WHERE etudiant_id = ? AND visible_etudiant = 1 AND statut = 'publie'
     ORDER BY date_emission DESC, id DESC LIMIT 3"
);
$ld->execute([$u['id']]);

api_json([
    'kpis' => [
        'total_du_cents'   => (int)($rowF['du'] ?? 0),
        'total_paye_cents' => (int)($rowF['paye'] ?? 0),
        'nb_factures'      => (int)($rowF['total'] ?? 0),
        'nb_documents'     => $nbDocs,
    ],
    'last_factures'  => $lf->fetchAll(),
    'last_documents' => $ld->fetchAll(),
]);
