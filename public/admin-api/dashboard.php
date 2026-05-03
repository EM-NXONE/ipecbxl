<?php
/** GET /api/dashboard.php → KPIs admin + 5 dernières candidatures + KPIs paiements */
require_once __DIR__ . '/_bootstrap.php';
api_method('GET');
api_require_admin();
admin_require_db();

$pdo = db();

$row = $pdo->query("
    SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN statut = 'recue'    THEN 1 ELSE 0 END) AS recue,
        SUM(CASE WHEN statut = 'en_cours' THEN 1 ELSE 0 END) AS en_cours,
        SUM(CASE WHEN statut = 'validee'  THEN 1 ELSE 0 END) AS validee,
        SUM(CASE WHEN statut = 'refusee'  THEN 1 ELSE 0 END) AS refusee,
        SUM(CASE WHEN statut = 'annulee'  THEN 1 ELSE 0 END) AS annulee,
        SUM(CASE WHEN facture_payee = 1   THEN 1 ELSE 0 END) AS payees,
        SUM(CASE WHEN facture_payee = 0   THEN 1 ELSE 0 END) AS non_payees,
        SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) AS recent
    FROM candidatures
")->fetch() ?: [];

$nbEtu = (int)$pdo->query("SELECT COUNT(*) FROM etudiants")->fetchColumn();

// KPIs catégories
$cats = $pdo->query("
    SELECT
        SUM(CASE WHEN categorie = 'candidat'  THEN 1 ELSE 0 END) AS candidats,
        SUM(CASE WHEN categorie = 'preadmis'  THEN 1 ELSE 0 END) AS preadmis,
        SUM(CASE WHEN categorie = 'etudiant'  THEN 1 ELSE 0 END) AS etudiants
    FROM etudiants
")->fetch() ?: [];

// KPIs paiements (toutes factures, hors annulées)
$pay = $pdo->query("
    SELECT
        COUNT(*) AS total_factures,
        SUM(CASE WHEN statut_paiement='payee' THEN 1 ELSE 0 END) AS nb_payees,
        SUM(CASE WHEN statut_paiement='en_attente' THEN 1 ELSE 0 END) AS nb_attente,
        SUM(CASE WHEN statut_paiement='partiellement_payee' THEN 1 ELSE 0 END) AS nb_partielles,
        SUM(CASE WHEN statut_paiement='payee' THEN montant_ttc_cents ELSE 0 END) AS encaisse_cents,
        SUM(CASE WHEN statut_paiement IN ('en_attente','partiellement_payee') THEN montant_ttc_cents ELSE 0 END) AS attendu_cents,
        SUM(CASE WHEN statut_paiement IN ('en_attente','partiellement_payee') AND date_echeance IS NOT NULL AND date_echeance < CURDATE() THEN montant_ttc_cents ELSE 0 END) AS retard_cents,
        SUM(CASE WHEN statut_paiement IN ('en_attente','partiellement_payee') AND date_echeance IS NOT NULL AND date_echeance < CURDATE() THEN 1 ELSE 0 END) AS nb_retard,
        SUM(CASE WHEN statut_paiement='payee' AND paye_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN montant_ttc_cents ELSE 0 END) AS encaisse_30j_cents,
        SUM(CASE WHEN type='frais_dossier' AND statut_paiement='payee' THEN montant_ttc_cents ELSE 0 END) AS frais_dossier_cents,
        SUM(CASE WHEN type='scolarite' AND statut_paiement='payee' THEN montant_ttc_cents ELSE 0 END) AS scolarite_cents
    FROM factures
    WHERE statut_paiement <> 'annulee'
")->fetch() ?: [];

$last = $pdo->query("
    SELECT id, reference, prenom, nom, email, statut, programme, facture_payee, etudiant_id, created_at
    FROM candidatures ORDER BY created_at DESC LIMIT 5
")->fetchAll();

api_json([
    'kpis' => [
        'total'      => (int)($row['total'] ?? 0),
        'recue'      => (int)($row['recue'] ?? 0),
        'en_cours'   => (int)($row['en_cours'] ?? 0),
        'validee'    => (int)($row['validee'] ?? 0),
        'refusee'    => (int)($row['refusee'] ?? 0),
        'annulee'    => (int)($row['annulee'] ?? 0),
        'payees'     => (int)($row['payees'] ?? 0),
        'non_payees' => (int)($row['non_payees'] ?? 0),
        'recent_7j'  => (int)($row['recent'] ?? 0),
        'etudiants'  => $nbEtu,
        'cat_candidats' => (int)($cats['candidats'] ?? 0),
        'cat_preadmis'  => (int)($cats['preadmis'] ?? 0),
        'cat_etudiants' => (int)($cats['etudiants'] ?? 0),
    ],
    'paiements' => [
        'total_factures'      => (int)($pay['total_factures'] ?? 0),
        'nb_payees'           => (int)($pay['nb_payees'] ?? 0),
        'nb_attente'          => (int)($pay['nb_attente'] ?? 0),
        'nb_partielles'       => (int)($pay['nb_partielles'] ?? 0),
        'nb_retard'           => (int)($pay['nb_retard'] ?? 0),
        'encaisse_cents'      => (int)($pay['encaisse_cents'] ?? 0),
        'attendu_cents'       => (int)($pay['attendu_cents'] ?? 0),
        'retard_cents'        => (int)($pay['retard_cents'] ?? 0),
        'encaisse_30j_cents'  => (int)($pay['encaisse_30j_cents'] ?? 0),
        'frais_dossier_cents' => (int)($pay['frais_dossier_cents'] ?? 0),
        'scolarite_cents'     => (int)($pay['scolarite_cents'] ?? 0),
    ],
    'last_candidatures' => $last,
]);
