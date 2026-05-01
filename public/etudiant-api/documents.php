<?php
/** GET /api/documents.php → liste des documents publiés */
require_once __DIR__ . '/_bootstrap.php';
api_method('GET');
$u = api_require_etudiant();

$stmt = db()->prepare(
    "SELECT d.*, c.reference AS candidature_reference
     FROM documents d
     LEFT JOIN candidatures c ON c.id = d.candidature_id
     WHERE d.etudiant_id = ? AND d.visible_etudiant = 1 AND d.statut = 'publie'
     ORDER BY d.date_emission DESC, d.id DESC"
);
$stmt->execute([$u['id']]);
$docs = $stmt->fetchAll();

// Pour le récapitulatif de candidature : on affiche la référence réelle de la
// candidature (IPEC-CAND-...) plutôt que la référence interne du document,
// et on retire la phrase descriptive.
foreach ($docs as &$d) {
    if (($d['template'] ?? '') === 'recap_candidature') {
        $d['titre'] = 'Récapitulatif de candidature';
        $d['description'] = null;
        if (!empty($d['candidature_reference'])) {
            $d['reference'] = $d['candidature_reference'];
        }
    }
    unset($d['data_json'], $d['candidature_reference']);
}
unset($d);

api_json(['documents' => $docs]);
