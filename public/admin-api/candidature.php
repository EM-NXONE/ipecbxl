<?php
/**
 * GET /api/candidature.php?id=N
 *   → { candidature, etudiant, homonyme, historique, statuts }
 */
require_once __DIR__ . '/_bootstrap.php';
api_method('GET');
api_require_admin();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) api_error('id invalide', 400);

$pdo = db();
$stmt = $pdo->prepare("SELECT * FROM candidatures WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$cand = $stmt->fetch();
if (!$cand) api_error('Candidature introuvable', 404);

// Étudiant rattaché
$etudiant = null;
if (!empty($cand['etudiant_id'])) {
    $eStmt = $pdo->prepare(
        "SELECT id, numero_etudiant, civilite, prenom, nom, email,
                date_naissance, statut,
                (password_hash IS NOT NULL) AS active,
                derniere_connexion, cree_par_admin, created_at
         FROM etudiants WHERE id = ?"
    );
    $eStmt->execute([(int)$cand['etudiant_id']]);
    $etudiant = $eStmt->fetch() ?: null;
}

// Détection homonyme par identité civile (si pas déjà rattaché)
$homonyme = null;
if (!$etudiant) {
    $h = etudiant_find_by_identity($pdo, (string)$cand['prenom'], (string)$cand['nom'], (string)($cand['date_naissance'] ?? ''));
    if ($h) {
        $homonyme = [
            'id' => (int)$h['id'],
            'numero_etudiant' => $h['numero_etudiant'],
            'prenom' => $h['prenom'], 'nom' => $h['nom'],
            'date_naissance' => $h['date_naissance'],
        ];
    }
}

// Historique
$histStmt = $pdo->prepare(
    "SELECT id, action, detail, admin_user, ip, created_at
     FROM admin_actions WHERE candidature_id = ?
     ORDER BY created_at DESC LIMIT 100"
);
$histStmt->execute([$id]);

api_json([
    'candidature' => $cand,
    'etudiant'    => $etudiant,
    'homonyme'    => $homonyme,
    'historique'  => $histStmt->fetchAll(),
    'statuts'     => ADMIN_STATUTS,
]);
