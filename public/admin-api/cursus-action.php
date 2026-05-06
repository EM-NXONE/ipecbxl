<?php
/**
 * POST /api/cursus-action.php
 * Body : { candidature_id: int, action: string, ...params }
 *
 * Actions :
 *   - passer_annee   { annee_academique?, rentree? }
 *   - redoubler      { annee_academique?, rentree? }
 *   - diplomer       (depuis PEA-2 uniquement)
 *   - set_inactif    { motif: string }
 *   - set_actif
 *
 * Toutes les actions sont journalisées dans admin_actions et idempotentes
 * autant que possible.
 */
require_once __DIR__ . '/_bootstrap.php';
api_method('POST');
api_require_admin();
admin_require_db();
admin_require_cursus();

$body  = api_body();
$id    = (int)($body['candidature_id'] ?? 0);
$act   = (string)($body['action'] ?? '');
if ($id <= 0) api_error('candidature_id invalide', 400);
if ($act === '') api_error('action requise', 400);

$pdo = db();
$stmt = $pdo->prepare("SELECT * FROM candidatures WHERE id = ?");
$stmt->execute([$id]);
$cand = $stmt->fetch();
if (!$cand) api_error('Candidature introuvable', 404);
if (empty($cand['etudiant_id'])) api_error('Aucun compte étudiant rattaché', 400);

$etuId = (int)$cand['etudiant_id'];
$user  = admin_current_user();

try {
    switch ($act) {
        case 'passer_annee':
        case 'redoubler': {
            $mode = ($act === 'passer_annee') ? 'passage' : 'redoublement';
            $annee   = trim((string)($body['annee_academique'] ?? ''));
            $rentree = trim((string)($body['rentree'] ?? ''));
            if ($annee !== '' && !preg_match('/^\d{4}-\d{4}$/', $annee)) {
                api_error('annee_academique attendue au format AAAA-AAAA', 400);
            }
            $res = cursus_evoluer($pdo, $id, $mode, $annee ?: null, $rentree ?: null, $user);
            $newCand = $res['candidature'];
            $label = $newCand['programme'] . ' ' . $newCand['annee']
                   . ($newCand['annee_academique'] ? ' — ' . $newCand['annee_academique'] : '');
            $detail = ($mode === 'passage' ? 'Passage' : 'Redoublement') . ' → ' . $label
                    . ' (candidature #' . (int)$newCand['id'] . ', '
                    . $res['factures_count'] . ' factures scolarité)';
            admin_log_action($id, 'cursus_' . $mode, $detail);
            api_json([
                'ok' => true,
                'message' => ($mode === 'passage' ? 'Étudiant inscrit pour ' : 'Redoublement inscrit pour ')
                    . $label . '. Les ' . $res['factures_count']
                    . ' factures de scolarité ont été générées.',
                'new_candidature_id' => (int)$newCand['id'],
            ]);
        }

        case 'diplomer': {
            $res = cursus_diplomer($pdo, $id, $user);
            admin_log_action($id, 'cursus_diplomer',
                'Étudiant #' . $etuId . ' diplômé'
                . ($res['attestation_creee'] ? ' (attestation générée)' : ' (attestation déjà présente)'));
            api_json([
                'ok' => true,
                'message' => "Étudiant diplômé. L'attestation de réussite est disponible dans son espace.",
            ]);
        }

        case 'set_inactif': {
            $motif = (string)($body['motif'] ?? '');
            cursus_set_inactif($pdo, $etuId, $motif);
            admin_log_action($id, 'cursus_set_inactif', 'Motif : ' . trim($motif));
            api_json(['ok' => true, 'message' => 'Étudiant marqué inactif.']);
        }

        case 'set_actif': {
            $cat = cursus_set_actif($pdo, $etuId);
            admin_log_action($id, 'cursus_set_actif', 'Catégorie restaurée : ' . $cat);
            api_json(['ok' => true, 'message' => "Étudiant réactivé (catégorie : $cat).", 'categorie' => $cat]);
        }

        default:
            api_error('Action cursus inconnue : ' . $act, 400);
    }
} catch (\Throwable $e) {
    error_log('[cursus-action] ' . $act . ' #' . $id . ' : ' . $e->getMessage());
    api_error($e->getMessage(), 400);
}
