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
    $where[] = "(e.prenom LIKE :q OR e.nom LIKE :q OR e.email LIKE :q OR e.numero_etudiant LIKE :q)";
    $params[':q'] = '%' . $q . '%';
}
if (in_array($categorie, ['candidat','preadmis','etudiant'], true)) {
    $where[] = "e.categorie = :categorie";
    $params[':categorie'] = $categorie;
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$pdo = db();
$stmt = $pdo->prepare("
    SELECT e.id, e.numero_etudiant, e.civilite, e.prenom, e.nom, e.email,
           e.date_naissance, e.statut, e.categorie,
           (e.password_hash IS NOT NULL) AS active,
           e.derniere_connexion, e.created_at, e.cree_par_admin,
           c.programme, c.annee, c.specialisation
    FROM etudiants e
    LEFT JOIN candidatures c ON c.id = (
        SELECT id FROM candidatures
        WHERE etudiant_id = e.id
        ORDER BY created_at DESC
        LIMIT 1
    )
    $whereSql
    ORDER BY e.created_at DESC
    LIMIT 200
");
$stmt->execute($params);
api_json(['etudiants' => $stmt->fetchAll()]);
