<?php
/**
 * GET /api/candidature-pdf.php?id=N&kind=candidature|facture
 *   → PDF binaire (téléchargement direct) — réutilise les builders du mailer.
 */
require_once __DIR__ . '/_bootstrap.php';
api_require_admin();
admin_require_db();
admin_require_mailer();

$id   = (int)($_GET['id'] ?? 0);
$kind = (string)($_GET['kind'] ?? 'candidature');
if ($id <= 0) api_error('id invalide', 400);
if (!in_array($kind, ['candidature', 'facture', 'recu'], true)) api_error('kind invalide', 400);

$pdo = db();
$stmt = $pdo->prepare("SELECT * FROM candidatures WHERE id = ?");
$stmt->execute([$id]);
$c = $stmt->fetch();
if (!$c) api_error('Candidature introuvable', 404);

$adresse = trim(
    trim(($c['rue'] ?? '') . ' ' . ($c['numero'] ?? '')) .
    (($c['code_postal'] || $c['ville']) ? ', ' . trim(($c['code_postal'] ?? '') . ' ' . ($c['ville'] ?? '')) : '') .
    ($c['pays_residence'] ? ', ' . $c['pays_residence'] : '')
);

$base = [
    'reference' => $c['reference'], 'civilite' => $c['civilite'],
    'prenom' => $c['prenom'], 'nom' => $c['nom'],
    'dateNaissance' => $c['date_naissance'], 'nationalite' => $c['nationalite'],
    'email' => $c['email'], 'telephone' => $c['telephone'],
    'adresse' => $adresse, 'rue' => $c['rue'], 'numero' => $c['numero'],
    'codePostal' => $c['code_postal'], 'ville' => $c['ville'], 'paysResidence' => $c['pays_residence'],
    'programme' => $c['programme'], 'annee' => $c['annee'],
    'specialisation' => $c['specialisation'], 'rentree' => $c['rentree'],
    'message' => $c['message'], 'ip' => $c['ip'],
    'reference_facture' => $c['facture_numero'],
];

try {
    if ($kind === 'candidature') {
        $pdf = buildCandidaturePdf($base);
        $filename = 'candidature-' . preg_replace('/[^A-Za-z0-9_-]+/', '-', strtolower($c['prenom'].'-'.$c['nom'])) . '-' . $c['reference'] . '.pdf';
    } elseif ($kind === 'recu') {
        // Récupère les infos de paiement depuis la table factures, avec
        // repli sur les colonnes legacy de `candidatures` si besoin.
        $stmtF = $pdo->prepare(
            "SELECT numero, montant_ttc_cents, paye_at, moyen_paiement, reference_paiement, statut_paiement
             FROM factures WHERE candidature_id = ? AND type = 'frais_dossier' LIMIT 1"
        );
        $stmtF->execute([$id]);
        $fact = $stmtF->fetch() ?: [];

        $isPaid = (($fact['statut_paiement'] ?? '') === 'payee')
            || ((int)($c['facture_payee'] ?? 0) === 1);
        if (!$isPaid) {
            api_error('La facture n\'est pas encore marquée comme payée.', 409);
        }

        $base['reference_facture']   = $fact['numero']            ?? $c['facture_numero'];
        $base['paye_at']             = $fact['paye_at']           ?? $c['facture_payee_at'];
        $base['moyen_paiement']      = $fact['moyen_paiement']    ?? ($c['moyen_paiement'] ?? '');
        $base['reference_paiement']  = $fact['reference_paiement'] ?? '';
        $base['montant_ttc_cents']   = (int)($fact['montant_ttc_cents'] ?? 40000);

        if (empty($base['reference_facture'])) {
            api_error('Référence facture introuvable pour cette candidature.', 404);
        }
        [$pdf, $filename, $recuNumero] = buildRecuPaiementPdf($base);
    } else {
        [$pdf, $filename] = buildFacturePdf($base);
    }
    if ($pdf === '') api_error('PDF vide', 500);
    admin_log_action($id, 'download_' . $kind, $filename);

    // Override des headers JSON par défaut
    header_remove('Content-Type');
    header_remove('Cache-Control');
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($pdf));
    echo $pdf;
    exit;
} catch (\Throwable $e) {
    error_log('[admin-api/candidature-pdf] ' . $e->getMessage());
    api_error('Erreur génération PDF : ' . $e->getMessage(), 500);
}
