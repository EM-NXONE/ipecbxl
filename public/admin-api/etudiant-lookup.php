<?php
/**
 * GET /api/etudiant-lookup.php?numero=ETU...
 *   → { etudiant_id, numero_etudiant, candidature_id|null }
 *
 * Permet d'ouvrir la fiche détaillée d'un étudiant à partir de son numéro
 * ETU (et non du numéro de candidature). On renvoie l'id de la candidature
 * la plus récente liée, pour réutiliser la page /admin/candidatures/$id.
 */
require_once __DIR__ . '/_bootstrap.php';
api_method('GET');
api_require_admin();
admin_require_db();

$numero = trim((string)($_GET['numero'] ?? ''));
if ($numero === '') api_error('numero requis', 400);

$pdo = db();
$stmt = $pdo->prepare("SELECT id, numero_etudiant FROM etudiants WHERE numero_etudiant = ? LIMIT 1");
$stmt->execute([$numero]);
$etu = $stmt->fetch();
if (!$etu) api_error('Étudiant introuvable', 404);

$cStmt = $pdo->prepare("SELECT id FROM candidatures WHERE etudiant_id = ? ORDER BY created_at DESC LIMIT 1");
$cStmt->execute([(int)$etu['id']]);
$candId = $cStmt->fetchColumn();

api_json([
    'etudiant_id'     => (int)$etu['id'],
    'numero_etudiant' => (string)$etu['numero_etudiant'],
    'candidature_id'  => $candId ? (int)$candId : null,
]);
