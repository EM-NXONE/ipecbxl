<?php
/** GET /api/candidature.php?id=123 → détail complet */
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

// Documents associés
$dstmt = $pdo->prepare("SELECT id, type, libelle, statut, created_at
                        FROM documents WHERE candidature_id = ?
                        ORDER BY created_at DESC");
$dstmt->execute([$id]);
$docs = $dstmt->fetchAll();

// Factures
$fstmt = $pdo->prepare("SELECT id, numero, type, montant_ttc_cents, statut_paiement, paye_at
                        FROM factures WHERE candidature_id = ?
                        ORDER BY date_emission DESC");
$fstmt->execute([$id]);
$facts = $fstmt->fetchAll();

api_json([
    'candidature' => $cand,
    'documents'   => $docs,
    'factures'    => $facts,
]);
