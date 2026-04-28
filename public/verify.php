<?php
/**
 * IPEC — Endpoint public de vérification d'authenticité d'un document.
 *
 * Chaque document émis par l'IPEC porte une référence préfixée par son type :
 *   - IPEC-CAND-AAAA-XXXXXX  → confirmation de candidature
 *   - IPEC-FACT-AAAA-XXXXXX  → facture des frais de dossier
 *
 * Usage :
 *   GET  /verify.php?reference=IPEC-CAND-2026-A1B2C3
 *   POST /verify.php  (JSON: {"reference":"IPEC-FACT-2026-A1B2C3"})
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Max-Age: 86400');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/db_config.php';

// ----- Récupération de la référence (GET ou POST JSON) -----
$reference = '';
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $raw = file_get_contents('php://input') ?: '';
    $body = json_decode($raw, true);
    if (is_array($body) && isset($body['reference'])) {
        $reference = (string)$body['reference'];
    }
}
if ($reference === '' && isset($_GET['reference'])) {
    $reference = (string)$_GET['reference'];
}
$reference = strtoupper(trim($reference));

// ----- Validation du format -----
if ($reference === '') {
    http_response_code(400);
    echo json_encode(['valid' => false, 'error' => 'Référence manquante.']);
    exit;
}
// Nouveau format : IPEC-CAND-AAAA-XXXXXX  ou  IPEC-FACT-AAAA-XXXXXX
// Ancien format toléré : IPEC-AAAA-XXXXXX (assimilé à une candidature)
if (!preg_match('/^IPEC-(CAND|FACT)-\d{4}-[A-F0-9]{6,16}$/', $reference)
    && !preg_match('/^IPEC-\d{4}-[A-F0-9]{6,16}$/', $reference)) {
    echo json_encode([
        'valid' => false,
        'error' => 'Format de référence invalide. Format attendu : IPEC-CAND-AAAA-XXXXXX ou IPEC-FACT-AAAA-XXXXXX.',
    ]);
    exit;
}

// Détermine le type de document à partir du préfixe
$docType = 'candidature'; // défaut (et ancien format)
if (strpos($reference, 'IPEC-FACT-') === 0) {
    $docType = 'facture';
} elseif (strpos($reference, 'IPEC-CAND-') === 0) {
    $docType = 'candidature';
}

// ----- Lecture en base : on cherche dans la bonne colonne selon le type -----
try {
    $pdo = db();
    $column = $docType === 'facture' ? 'facture_numero' : 'reference';
    $stmt = $pdo->prepare(
        "SELECT reference, facture_numero, prenom, nom, programme, annee, annee_academique,
                specialisation, rentree, created_at
           FROM candidatures
          WHERE $column = ?
          LIMIT 1"
    );
    $stmt->execute([$reference]);
    $row = $stmt->fetch();
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['valid' => false, 'error' => 'Erreur serveur lors de la vérification.']);
    error_log('[verify.php] DB error: ' . $e->getMessage());
    exit;
}

if (!$row) {
    echo json_encode([
        'valid' => false,
        'error' => 'Aucun document ne correspond à cette référence. Le document est probablement falsifié ou la référence a été mal saisie.',
    ]);
    exit;
}

// ----- Anonymisation RGPD : prénom + nom masqué (X***Y) -----
function maskName(string $nom): string {
    $nom = trim($nom);
    $len = mb_strlen($nom, 'UTF-8');
    if ($len === 0) return '';
    if ($len === 1) return mb_strtoupper($nom, 'UTF-8');
    if ($len === 2) {
        return mb_strtoupper(mb_substr($nom, 0, 1, 'UTF-8'), 'UTF-8')
             . mb_strtoupper(mb_substr($nom, 1, 1, 'UTF-8'), 'UTF-8');
    }
    $first = mb_strtoupper(mb_substr($nom, 0, 1, 'UTF-8'), 'UTF-8');
    $last  = mb_strtoupper(mb_substr($nom, -1, 1, 'UTF-8'), 'UTF-8');
    $stars = str_repeat('*', max(1, $len - 2));
    return $first . $stars . $last;
}

$nomAffiche = trim($row['prenom']) . ' ' . maskName((string)$row['nom']);

// Mappage code programme → nom complet
$programmesFull = [
    'PAA' => 'Programme en Administration des Affaires',
    'PEA' => 'Programme Exécutif Avancé',
];
$programmeFull = $programmesFull[strtoupper((string)$row['programme'])] ?? (string)$row['programme'];

// Filtre spécialisation : on l'omet si vide ou "Je ne sais pas encore"
$specialisationRaw = trim((string)($row['specialisation'] ?? ''));
$hasSpec = $specialisationRaw !== ''
    && stripos($specialisationRaw, 'je ne sais pas') === false;

$docTypeLabels = [
    'candidature' => 'Confirmation de candidature',
    'facture'     => 'Facture — frais de dossier',
];

echo json_encode([
    'valid'             => true,
    'reference'         => $reference,
    'document_type'     => $docType,
    'document_label'    => $docTypeLabels[$docType] ?? $docType,
    'candidat'          => $nomAffiche,
    'programme_code'    => $row['programme'],
    'programme'         => $programmeFull,
    'annee'             => $row['annee'],
    'specialisation'    => $hasSpec ? $specialisationRaw : null,
    'annee_academique'  => $row['annee_academique'],
    'rentree'           => $row['rentree'],
    'date_creation'     => substr((string)$row['created_at'], 0, 10),
], JSON_UNESCAPED_UNICODE);
