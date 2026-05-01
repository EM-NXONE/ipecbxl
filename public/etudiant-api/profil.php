<?php
/**
 * GET  /api/profil.php             → étudiant + adresse depuis la dernière candidature
 * POST /api/change-password.php    → cf. fichier dédié
 */
require_once __DIR__ . '/_bootstrap.php';
api_method('GET');
$u = api_require_etudiant();
$pdo = db();

$stmt = $pdo->prepare(
    "SELECT id, email, civilite, prenom, nom, date_naissance, nationalite,
            telephone, numero_etudiant, statut, derniere_connexion, created_at
     FROM etudiants WHERE id=?"
);
$stmt->execute([$u['id']]);
$etu = $stmt->fetch();
if (!$etu) api_error('Compte introuvable', 404);

// Compléter avec l'adresse (et téléphone/nationalité si manquants) depuis la
// dernière candidature liée à cet étudiant.
$cStmt = $pdo->prepare(
    "SELECT rue, numero, code_postal, ville, pays_residence,
            telephone AS cand_telephone, nationalite AS cand_nationalite
     FROM candidatures
     WHERE etudiant_id = ?
     ORDER BY id DESC LIMIT 1"
);
$cStmt->execute([$u['id']]);
$cand = $cStmt->fetch();

$etu['rue']            = $cand['rue']            ?? null;
$etu['numero']         = $cand['numero']         ?? null;
$etu['code_postal']    = $cand['code_postal']    ?? null;
$etu['ville']          = $cand['ville']          ?? null;
$etu['pays_residence'] = $cand['pays_residence'] ?? null;
if (empty($etu['telephone'])   && !empty($cand['cand_telephone']))   $etu['telephone']   = $cand['cand_telephone'];
if (empty($etu['nationalite']) && !empty($cand['cand_nationalite'])) $etu['nationalite'] = $cand['cand_nationalite'];

// On expose les deux clés (`profil` attendu par le React, `etudiant` historique).
api_json(['profil' => $etu, 'etudiant' => $etu]);
