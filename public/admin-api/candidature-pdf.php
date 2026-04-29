<?php
/**
 * GET /api/candidature-pdf.php?id=N&kind=candidature|facture
 * Téléchargement direct PDF (côté admin).
 */
require_once __DIR__ . '/_bootstrap.php';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    http_response_code(405); exit('Méthode non autorisée');
}
if (!admin_is_logged_in()) { http_response_code(401); exit('Non authentifié'); }

$id   = (int)($_GET['id'] ?? 0);
$kind = (string)($_GET['kind'] ?? '');
if ($id <= 0 || !in_array($kind, ['candidature', 'facture'], true)) {
    http_response_code(400); exit('Requête invalide');
}

$pdo = db();
$stmt = $pdo->prepare("SELECT * FROM candidatures WHERE id = ?");
$stmt->execute([$id]);
$c = $stmt->fetch();
if (!$c) { http_response_code(404); exit('Introuvable'); }

$adresse = trim(
    trim(($c['rue'] ?? '') . ' ' . ($c['numero'] ?? '')) .
    (($c['code_postal'] || $c['ville']) ? ', ' . trim(($c['code_postal'] ?? '') . ' ' . ($c['ville'] ?? '')) : '') .
    ($c['pays_residence'] ? ', ' . $c['pays_residence'] : '')
);

$slug = preg_replace('/[^A-Za-z0-9_-]+/', '-', strtolower($c['prenom'] . '-' . $c['nom']));

try {
    if ($kind === 'candidature') {
        $fields = [
            'reference' => $c['reference'], 'civilite' => $c['civilite'],
            'prenom' => $c['prenom'], 'nom' => $c['nom'],
            'dateNaissance' => $c['date_naissance'], 'nationalite' => $c['nationalite'],
            'email' => $c['email'], 'telephone' => $c['telephone'],
            'adresse' => $adresse,
            'rue' => $c['rue'], 'numero' => $c['numero'],
            'codePostal' => $c['code_postal'], 'ville' => $c['ville'],
            'paysResidence' => $c['pays_residence'],
            'programme' => $c['programme'], 'annee' => $c['annee'],
            'specialisation' => $c['specialisation'], 'rentree' => $c['rentree'],
            'message' => $c['message'], 'ip' => $c['ip'],
        ];
        $pdf = buildCandidaturePdf($fields);
        if ($pdf === '') { http_response_code(500); exit('PDF vide'); }
        $filename = 'candidature-' . $slug . '-' . $c['reference'] . '.pdf';
        admin_log_action($id, 'download_candidature', $filename);
    } else {
        $fields = [
            'reference' => $c['reference'], 'reference_facture' => $c['facture_numero'],
            'civilite' => $c['civilite'], 'prenom' => $c['prenom'], 'nom' => $c['nom'],
            'adresse' => $adresse, 'rue' => $c['rue'], 'numero' => $c['numero'],
            'codePostal' => $c['code_postal'], 'ville' => $c['ville'],
            'paysResidence' => $c['pays_residence'], 'email' => $c['email'],
            'programme' => $c['programme'], 'annee' => $c['annee'], 'rentree' => $c['rentree'],
        ];
        [$pdf, $filename] = buildFacturePdf($fields);
        if ($pdf === '') { http_response_code(500); exit('PDF vide'); }
        admin_log_action($id, 'download_facture', $filename);
    }

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($pdf));
    echo $pdf; exit;
} catch (\Throwable $e) {
    error_log('[admin-api/candidature-pdf] ' . $e->getMessage());
    http_response_code(500); exit('Erreur génération PDF');
}
