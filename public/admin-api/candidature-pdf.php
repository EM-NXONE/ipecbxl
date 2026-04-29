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
if (!in_array($kind, ['candidature', 'facture'], true)) api_error('kind invalide', 400);

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
