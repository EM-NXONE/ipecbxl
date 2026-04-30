<?php
/**
 * GET  /api/profil.php             → étudiant + candidature(s) liée(s)
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

// On expose les deux clés (`profil` attendu par le React, `etudiant` historique).
api_json(['profil' => $etu, 'etudiant' => $etu]);
