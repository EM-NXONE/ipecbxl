<?php
/** GET /api/dashboard.php → KPIs admin + 5 dernières candidatures */
require_once __DIR__ . '/_bootstrap.php';
api_method('GET');
api_require_admin();

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

$last = $pdo->query("
    SELECT id, reference, prenom, nom, email, statut, programme, facture_payee, created_at
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
    ],
    'last_candidatures' => $last,
]);
