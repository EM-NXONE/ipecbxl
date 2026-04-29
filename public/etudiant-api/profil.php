<?php
/** GET /api/profil.php → infos détaillées de l'étudiant connecté */
require_once __DIR__ . '/_bootstrap.php';
api_method('GET');
$u = api_require_etu();

$stmt = db()->prepare(
    "SELECT id, numero_etudiant, civilite, prenom, nom, email,
            date_naissance, nationalite, telephone,
            statut, email_verifie, derniere_connexion, created_at
     FROM etudiants WHERE id = ? LIMIT 1"
);
$stmt->execute([$u['id']]);
$etu = $stmt->fetch();
if (!$etu) api_error('Profil introuvable', 404);

api_json(['profil' => $etu]);
