<?php
/** GET /api/etudiants.php?q=...&categorie=preadmis|etudiant|candidat → liste (max 200) */
require_once __DIR__ . '/_bootstrap.php';
api_method('GET');
api_require_admin();
admin_require_db();

$q = trim((string)($_GET['q'] ?? ''));
$categorie = (string)($_GET['categorie'] ?? '');
$where = [];
$params = [];
if ($q !== '') {
    $where[] = "(prenom LIKE :q OR nom LIKE :q OR email LIKE :q OR numero_etudiant LIKE :q)";
    $params[':q'] = '%' . $q . '%';
}
if (in_array($categorie, ['candidat','preadmis','etudiant'], true)) {
    $where[] = "categorie = :categorie";
    $params[':categorie'] = $categorie;
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$pdo = db();
$stmt = $pdo->prepare("
    SELECT id, numero_etudiant, civilite, prenom, nom, email,
           date_naissance, statut, categorie,
           (password_hash IS NOT NULL) AS active,
           derniere_connexion, created_at, cree_par_admin
    FROM etudiants
    $whereSql
    ORDER BY created_at DESC
    LIMIT 200
");
$stmt->execute($params);
api_json(['etudiants' => $stmt->fetchAll()]);
