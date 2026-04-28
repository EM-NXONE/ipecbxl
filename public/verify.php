<?php
/**
 * IPEC — Endpoint public de vérification d'authenticité d'un document.
 *
 * Permet à toute autorité (université, employeur, ambassade, police…) de
 * vérifier qu'un PDF reçu correspond bien à une candidature enregistrée
 * par l'IPEC.
 *
 * Usage :
 *   GET  /verify.php?reference=IPEC-2026-A1B2C3
 *   POST /verify.php  (JSON: {"reference":"IPEC-2026-A1B2C3"})
 *
 * Réponse JSON :
 *   { "valid": true,
 *     "reference": "IPEC-2026-A1B2C3",
 *     "candidat": "Jean D.",         (initiale du nom uniquement = RGPD)
 *     "programme": "PAA",
 *     "annee": "1ère année",
 *     "annee_academique": "2026/2027",
 *     "rentree": "Septembre 2026",
 *     "statut": "recue",
 *     "date_creation": "2026-04-28" }
 *
 *   { "valid": false, "error": "..." }   en cas d'échec
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
// CORS large : l'endpoint est strictement public (lecture seule, données limitées)
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
$reference = trim($reference);

// ----- Validation du format -----
if ($reference === '') {
    http_response_code(400);
    echo json_encode(['valid' => false, 'error' => 'Référence manquante.']);
    exit;
}
if (!preg_match('/^IPEC-\d{4}-[A-F0-9]{6,16}$/i', $reference)) {
    echo json_encode([
        'valid' => false,
        'error' => 'Format de référence invalide. Format attendu : IPEC-AAAA-XXXXXX.',
    ]);
    exit;
}

$reference = strtoupper($reference);

// ----- Lecture en base -----
try {
    $pdo = db();
    $stmt = $pdo->prepare(
        'SELECT reference, prenom, nom, programme, annee, annee_academique,
                specialisation, rentree, statut, created_at
           FROM candidatures
          WHERE reference = ?
          LIMIT 1'
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

// ----- Anonymisation partielle (RGPD) : prénom + initiale du nom -----
$nomAffiche = trim($row['prenom']) . ' ' . strtoupper(substr(trim($row['nom']), 0, 1)) . '.';

// Mappage code programme → nom complet
$programmesFull = [
    'PAA' => 'Programme en Administration des Affaires',
    'PEA' => 'Programme Exécutif Avancé',
];
$programmeFull = $programmesFull[strtoupper((string)$row['programme'])] ?? (string)$row['programme'];

$statutLabels = [
    'recue'    => 'Reçue',
    'en_cours' => 'En cours d\'examen',
    'validee'  => 'Validée',
    'refusee'  => 'Refusée',
    'annulee'  => 'Annulée',
];

echo json_encode([
    'valid'             => true,
    'reference'         => $row['reference'],
    'candidat'          => $nomAffiche,
    'programme_code'    => $row['programme'],
    'programme'         => $programmeFull,
    'annee'             => $row['annee'],
    'specialisation'    => $row['specialisation'] ?: null,
    'annee_academique'  => $row['annee_academique'],
    'rentree'           => $row['rentree'],
    'statut'            => $row['statut'],
    'statut_label'      => $statutLabels[$row['statut']] ?? $row['statut'],
    'date_creation'     => substr((string)$row['created_at'], 0, 10),
], JSON_UNESCAPED_UNICODE);
