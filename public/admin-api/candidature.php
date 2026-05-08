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
admin_require_cursus();

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
                date_naissance, statut, categorie, motif_inactif, date_fin_cursus,
                etape_courante, annee_academique_courante, rentree_courante,
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

// Toutes les factures liées à cette candidature (frais de dossier + scolarité)
// + les factures liées à l'étudiant rattaché s'il y en a un (au cas où certaines
// scolarités auraient été générées sans candidature_id).
$factureIds = [];
$factures   = [];
$pushFact = function (array $row) use (&$factureIds, &$factures) {
    $fid = (int)$row['id'];
    if (isset($factureIds[$fid])) return;
    $factureIds[$fid] = true;
    $factures[] = $row;
};

$qf = "SELECT f.id, f.numero, f.type, f.libelle, f.description, f.montant_ttc_cents, f.tva_taux,
              f.devise, f.date_emission, f.date_echeance, f.statut_paiement, f.paye_at,
              f.moyen_paiement, f.paye_par_admin, f.reference_paiement,
              f.candidature_id, f.annee_academique, f.etape_cursus,
              f.annee_academique AS candidature_annee_academique
         FROM factures f
        WHERE f.candidature_id = ?
        ORDER BY FIELD(f.type,'frais_dossier','scolarite'), f.date_echeance ASC, f.id ASC";
$st = $pdo->prepare($qf);
$st->execute([$id]);
foreach ($st->fetchAll() as $r) $pushFact($r);

if ($etudiant) {
    $qfe = "SELECT f.id, f.numero, f.type, f.libelle, f.description, f.montant_ttc_cents, f.tva_taux,
                   f.devise, f.date_emission, f.date_echeance, f.statut_paiement, f.paye_at,
                   f.moyen_paiement, f.paye_par_admin, f.reference_paiement,
                   f.candidature_id, f.annee_academique, f.etape_cursus,
                   f.annee_academique AS candidature_annee_academique
              FROM factures f
             WHERE f.etudiant_id = ?
             ORDER BY FIELD(f.type,'frais_dossier','scolarite'), f.date_echeance ASC, f.id ASC";
    $st2 = $pdo->prepare($qfe);
    $st2->execute([(int)$etudiant['id']]);
    foreach ($st2->fetchAll() as $r) $pushFact($r);
}

// Historique
$histStmt = $pdo->prepare(
    "SELECT id, action, detail, admin_user, ip, created_at
     FROM admin_actions WHERE candidature_id = ?
     ORDER BY created_at DESC LIMIT 100"
);
$histStmt->execute([$id]);

// Historique cursus : reconstruit depuis les années académiques distinctes
// présentes sur les factures de scolarité (1 ligne = 1 année validée/payée).
$cursus_history = [];
if ($etudiant) {
    $hStmt = $pdo->prepare(
        "SELECT MIN(f.id) AS id,
                f.annee_academique,
                f.etape_cursus,
                COUNT(*) AS nb_factures,
                SUM(f.statut_paiement = 'payee') AS nb_payees,
                MIN(f.date_emission) AS started_at
           FROM factures f
          WHERE f.etudiant_id = ? AND f.type = 'scolarite' AND f.annee_academique IS NOT NULL
          GROUP BY f.annee_academique, f.etape_cursus
          ORDER BY f.annee_academique DESC, f.etape_cursus DESC"
    );
    $hStmt->execute([(int)$etudiant['id']]);
    $cursus_history = $hStmt->fetchAll();
}

api_json([
    'candidature'    => $cand,
    'etudiant'       => $etudiant,
    'homonyme'       => $homonyme,
    'factures'       => $factures,
    'historique'     => $histStmt->fetchAll(),
    'statuts'        => ADMIN_STATUTS,
    'cursus'         => cursus_describe_for($etudiant, $cand),
    'cursus_history' => $cursus_history,
    'latest_candidature_id' => (int)$cand['id'],
]);
