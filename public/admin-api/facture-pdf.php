<?php
/**
 * GET /api/facture-pdf.php?id=N&kind=facture|recu
 *   → PDF binaire (téléchargement) pour N'IMPORTE QUELLE facture (frais
 *     de dossier OU scolarité), identifiée par son ID dans la table factures.
 *   - kind=facture : génère la facture à partir des données stockées en BDD.
 *   - kind=recu    : génère le reçu de paiement (refusé si facture non payée).
 */
require_once __DIR__ . '/_bootstrap.php';
api_require_admin();
admin_require_db();
admin_require_mailer();

$id   = (int)($_GET['id'] ?? 0);
$kind = (string)($_GET['kind'] ?? 'facture');
if ($id <= 0) api_error('id invalide', 400);
if (!in_array($kind, ['facture', 'recu'], true)) api_error('kind invalide', 400);

$pdo = db();
$stmt = $pdo->prepare(
    "SELECT f.*, e.civilite, e.prenom, e.nom, e.email,
            c.rue, c.numero AS num_rue, c.code_postal, c.ville, c.pays_residence,
            c.programme, c.annee, c.specialisation, c.rentree, c.reference AS ref_candidature
     FROM factures f
     INNER JOIN etudiants e ON e.id = f.etudiant_id
     LEFT JOIN candidatures c ON c.id = f.candidature_id
     WHERE f.id = ?
     LIMIT 1"
);
$stmt->execute([$id]);
$f = $stmt->fetch();
if (!$f) api_error('Facture introuvable.', 404);

$adresse = trim(
    trim(($f['rue'] ?? '') . ' ' . ($f['num_rue'] ?? '')) .
    (($f['code_postal'] || $f['ville']) ? ', ' . trim(($f['code_postal'] ?? '') . ' ' . ($f['ville'] ?? '')) : '') .
    ($f['pays_residence'] ? ', ' . $f['pays_residence'] : '')
);

$base = [
    'reference'         => $f['numero'],
    'reference_facture' => $f['numero'],
    'civilite'          => $f['civilite'],
    'prenom'            => $f['prenom'],
    'nom'               => $f['nom'],
    'email'             => $f['email'],
    'adresse'           => $adresse,
    'rue'               => $f['rue'],
    'numero'            => $f['num_rue'],
    'codePostal'        => $f['code_postal'],
    'ville'             => $f['ville'],
    'paysResidence'     => $f['pays_residence'],
    'programme'         => $f['programme'],
    'annee'             => $f['annee'],
    'specialisation'    => $f['specialisation'],
    'rentree'           => $f['rentree'],
    'libelle'           => $f['libelle'],
    'description'       => $f['description'],
    'montant_ttc_cents' => (int)$f['montant_ttc_cents'],
    'tva_taux'          => (float)$f['tva_taux'],
    'devise'            => $f['devise'] ?? 'EUR',
    'date_emission'     => $f['date_emission'],
    'date_echeance'     => $f['date_echeance'],
];

try {
    if ($kind === 'recu') {
        if (($f['statut_paiement'] ?? '') !== 'payee') {
            api_error("La facture n'est pas encore marquée comme payée.", 409);
        }
        $base['paye_at']            = $f['paye_at'];
        $base['moyen_paiement']     = $f['moyen_paiement'];
        $base['reference_paiement'] = $f['reference_paiement'] ?? '';
        [$pdf, $filename, $recuNumero] = buildRecuPaiementPdf($base);
    } else {
        [$pdf, $filename] = buildFacturePdf($base);
    }
    if ($pdf === '') api_error('PDF vide', 500);

    $candId = (int)($f['candidature_id'] ?? 0);
    if ($candId > 0) {
        admin_log_action($candId, 'download_' . $kind, ($f['numero'] ?? '') . ' (#' . $id . ')');
    }

    header_remove('Content-Type');
    header_remove('Cache-Control');
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($pdf));
    echo $pdf;
    exit;
} catch (\Throwable $e) {
    error_log('[admin-api/facture-pdf] ' . $e->getMessage());
    api_error('Erreur génération PDF : ' . $e->getMessage(), 500);
}
