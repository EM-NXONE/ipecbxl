<?php
/**
 * GET /api/candidatures.php
 * ?q=... &statut=... &payee=1|0 &page=1 &perPage=30
 *   → { candidatures, total, page, perPage, pages, statuts }
 */
require_once __DIR__ . '/_bootstrap.php';
api_method('GET');
api_require_admin();
admin_require_db();

$q       = trim((string)($_GET['q'] ?? ''));
$statut  = (string)($_GET['statut'] ?? '');
$payee   = (string)($_GET['payee'] ?? '');
// vue : 'actives' (défaut, exclut refusee/annulee) | 'refuses' (uniquement refusee+annulee) | 'all'
$vue     = (string)($_GET['vue'] ?? 'actives');
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = max(1, min(100, (int)($_GET['perPage'] ?? 30)));
$offset  = ($page - 1) * $perPage;

$where  = [];
$params = [];
if ($q !== '') {
    $where[] = '(prenom LIKE :q OR nom LIKE :q OR email LIKE :q OR reference LIKE :q OR facture_numero LIKE :q)';
    $params[':q'] = '%' . $q . '%';
}
if ($statut !== '' && isset(ADMIN_STATUTS[$statut])) {
    $where[] = 'statut = :statut';
    $params[':statut'] = $statut;
}
if ($vue === 'refuses') {
    $where[] = "statut IN ('refusee','annulee')";
} elseif ($vue === 'actives') {
    $where[] = "statut NOT IN ('refusee','annulee')";
}
if ($payee === '1') $where[] = 'facture_payee = 1';
elseif ($payee === '0') $where[] = 'facture_payee = 0';
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Pour la vue 'actives' : exclure aussi les candidats déjà passés en preadmis/etudiant
$joinSql = '';
if ($vue === 'actives') {
    $joinSql = "LEFT JOIN etudiants e ON e.id = candidatures.etudiant_id";
    $extra = "(e.categorie IS NULL OR e.categorie = 'candidat')";
    $whereSql .= ($whereSql ? ' AND ' : 'WHERE ') . $extra;
}


$pdo = db();
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM candidatures $whereSql");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$pages = max(1, (int)ceil($total / $perPage));

$sql = "SELECT id, reference, statut, prenom, nom, email, programme, annee, specialisation,
               annee_academique, facture_numero, facture_payee, facture_payee_at,
               etudiant_id, created_at
        FROM candidatures
        $whereSql
        ORDER BY created_at DESC
        LIMIT $perPage OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);

api_json([
    'candidatures' => $stmt->fetchAll(),
    'total'        => $total,
    'page'         => $page,
    'perPage'      => $perPage,
    'pages'        => $pages,
    'statuts'      => ADMIN_STATUTS,
]);
