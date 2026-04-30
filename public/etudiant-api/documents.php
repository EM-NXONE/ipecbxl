<?php
/** GET /api/documents.php → liste des documents publiés */
require_once __DIR__ . '/_bootstrap.php';
api_method('GET');
$u = api_require_etudiant();

$stmt = db()->prepare(
    "SELECT * FROM documents
     WHERE etudiant_id=? AND visible_etudiant=1 AND statut='publie'
     ORDER BY date_emission DESC, id DESC"
);
$stmt->execute([$u['id']]);
$docs = $stmt->fetchAll();

// Pour les documents de type "recap_candidature", on affiche la référence de
// la candidature elle-même (IPEC-CAND-AAAA-XXXXXX) plutôt que la référence
// interne du document (IPEC-DOC-...), pour rester cohérent avec le PDF généré
// et le numéro vérifiable sur ipec.school/verification.
foreach ($docs as &$d) {
    if (($d['template'] ?? '') === 'recap_candidature' && !empty($d['data_json'])) {
        $tmp = json_decode($d['data_json'], true);
        if (is_array($tmp) && !empty($tmp['reference'])) {
            $d['reference'] = (string)$tmp['reference'];
        }
    }
    unset($d['data_json']); // pas utile au front, et potentiellement volumineux
}
unset($d);

api_json(['documents' => $docs]);
