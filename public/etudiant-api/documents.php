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

// Nettoyage d'affichage pour les récapitulatifs de candidature : on retire
// la référence candidature qui était accolée au titre (« Récapitulatif de
// candidature IPEC-CAND-AAAA-XXXXXX »). La référence du document
// (IPEC-DOC-...) reste celle exposée pour l'affichage.
foreach ($docs as &$d) {
    if (($d['template'] ?? '') === 'recap_candidature') {
        $d['titre'] = 'Récapitulatif de candidature';
    }
    unset($d['data_json']); // pas utile au front, et potentiellement volumineux
}
unset($d);

api_json(['documents' => $docs]);
