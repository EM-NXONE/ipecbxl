<?php
/** GET /api/etudiants.php?q=... → liste des étudiants (max 200) */
require_once __DIR__ . '/_bootstrap.php';
api_method('GET');
api_require_admin();

$q = trim((string)($_GET['q'] ?? ''));
$where = '';
$params = [];
if ($q !== '') {
    $where = "WHERE prenom LIKE :q OR nom LIKE :q OR email LIKE :q OR numero_etudiant LIKE :q";
    $params[':q'] = '%' . $q . '%';
}

$pdo = db();
$stmt = $pdo->prepare("
    SELECT id, numero_etudiant, civilite, prenom, nom, email,
           date_naissance, statut,
           (password_hash IS NOT NULL) AS active,
           derniere_connexion, created_at, cree_par_admin
    FROM etudiants
    $where
    ORDER BY created_at DESC
    LIMIT 200
");
$stmt->execute($params);
api_json(['etudiants' => $stmt->fetchAll()]);
