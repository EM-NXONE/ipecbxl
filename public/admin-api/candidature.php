<?php
/**
 * GET /api/candidature.php?id=N
 *   → { candidature, etudiant, homonyme, historique, statuts }
 */
require_once __DIR__ . '/_bootstrap.php';
api_method('GET');
api_require_admin();
admin_require_db();
admin_require_etudiants();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) api_error('id invalide', 400);

$pdo = db();
$stmt = $pdo->prepare("SELECT * FROM candidatures WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$cand = $stmt->fetch();
if (!$cand) api_error('Candidature introuvable', 404);

// Détails de la facture frais de dossier (moyen, dates) — pour le carton paiement
$fStmt = $pdo->prepare("SELECT moyen_paiement, paye_at, statut_paiement
                        FROM factures
                        WHERE candidature_id = ? AND type = 'frais_dossier'
                        ORDER BY id DESC LIMIT 1");
$fStmt->execute([$id]);
if ($f = $fStmt->fetch()) {
    $cand['moyen_paiement']      = $f['moyen_paiement'] ?? null;
    $cand['facture_statut']      = $f['statut_paiement'] ?? null;
    if (!empty($f['paye_at']) && empty($cand['facture_payee_at'])) {
        $cand['facture_payee_at'] = $f['paye_at'];
    }
} else {
    $cand['moyen_paiement'] = null;
    $cand['facture_statut'] = null;
}

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

// Factures de scolarité (si générées)
$sStmt = $pdo->prepare("SELECT id, numero, libelle, montant_ttc_cents, date_emission,
                               date_echeance, statut_paiement, paye_at, moyen_paiement
                        FROM factures
                        WHERE candidature_id = ? AND type = 'scolarite'
                        ORDER BY date_echeance ASC, id ASC");
$sStmt->execute([$id]);
$facturesScolarite = $sStmt->fetchAll();

// Historique
$histStmt = $pdo->prepare(
    "SELECT id, action, detail, admin_user, ip, created_at
     FROM admin_actions WHERE candidature_id = ?
     ORDER BY created_at DESC LIMIT 100"
);
$histStmt->execute([$id]);

api_json([
    'candidature'         => $cand,
    'etudiant'            => $etudiant,
    'homonyme'            => $homonyme,
    'factures_scolarite'  => $facturesScolarite,
    'historique'          => $histStmt->fetchAll(),
    'statuts'             => ADMIN_STATUTS,
]);
