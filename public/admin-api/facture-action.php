<?php
/**
 * POST /api/facture-action.php
 * Body : { id: int (facture_id), action: 'mark_paid' | 'mark_unpaid', moyen_paiement?, date_paiement? }
 *
 * Permet de marquer payée n'importe quelle facture (typiquement frais de
 * scolarité). Met à jour le statut + auto-promeut l'étudiant en
 * categorie='etudiant' si la facture est de type 'scolarite' marquée payée.
 */
require_once __DIR__ . '/_bootstrap.php';
api_method('POST');
api_require_admin();
admin_require_db();
admin_require_etudiants();

$body   = api_body();
$id     = (int)($body['id'] ?? 0);
$action = (string)($body['action'] ?? '');
if ($id <= 0)       api_error('id invalide', 400);
if ($action === '') api_error('action requise', 400);

$pdo = db();
$stmt = $pdo->prepare("SELECT * FROM factures WHERE id = ?");
$stmt->execute([$id]);
$f = $stmt->fetch();
if (!$f) api_error('Facture introuvable.', 404);

$candidatureId = (int)($f['candidature_id'] ?? 0);
$etudiantId    = (int)($f['etudiant_id'] ?? 0);

try {
    switch ($action) {

        case 'mark_paid': {
            $allowedMoyens = ['virement', 'carte', 'especes', 'cheque', 'autre'];
            $moyen = trim((string)($body['moyen_paiement'] ?? ''));
            if ($moyen === '' || !in_array($moyen, $allowedMoyens, true)) {
                api_error('Moyen de paiement requis (virement, carte, especes, cheque, autre).', 400);
            }
            $date = trim((string)($body['date_paiement'] ?? ''));
            if ($date !== '') {
                $d = DateTime::createFromFormat('Y-m-d', $date);
                if (!$d) api_error('Date de paiement invalide (format YYYY-MM-DD).', 400);
                $payeAt = $d->format('Y-m-d') . ' 12:00:00';
            } else {
                $payeAt = date('Y-m-d H:i:s');
            }
            $pdo->prepare("UPDATE factures
                           SET statut_paiement='payee', paye_at=?, paye_par_admin=?, moyen_paiement=?
                           WHERE id=?")
                ->execute([$payeAt, admin_current_user(), $moyen, $id]);

            // Si c'est la facture des frais de dossier, on synchronise aussi
            // la candidature (pour l'historique + déclencheur factures scolarité).
            $preadmisCreated = '';
            if (($f['type'] ?? '') === 'frais_dossier' && $candidatureId > 0) {
                $pdo->prepare("UPDATE candidatures
                               SET facture_payee=1, facture_payee_at=?, facture_payee_par=?
                               WHERE id=?")
                    ->execute([$payeAt, admin_current_user(), $candidatureId]);
                // Tente de générer les 3 factures de scolarité (et promotion en 'preadmis')
                // si la candidature est aussi en statut 'validee' et rattachée à un étudiant.
                try {
                    $cs = $pdo->prepare("SELECT * FROM candidatures WHERE id = ?");
                    $cs->execute([$candidatureId]);
                    $cand = $cs->fetch();
                    if ($cand) {
                        $res = etudiant_create_factures_scolarite($pdo, $cand, admin_current_user());
                        if (!empty($res['created'])) {
                            $preadmisCreated = ' Compte promu en « préadmis » — ' . $res['count'] . ' factures de scolarité créées.';
                            admin_log_action($candidatureId, 'create_factures_scolarite',
                                $res['count'] . ' factures (3 tranches) générées');
                        }
                    }
                } catch (\Throwable $e) {
                    error_log('[facture-action] generate scolarite #' . $candidatureId . ' : ' . $e->getMessage());
                }
            }

            // Si c'est une facture de scolarité → promotion en 'etudiant'
            $promoted = false;
            if (($f['type'] ?? '') === 'scolarite' && $etudiantId > 0) {
                $promoted = etudiant_promote_if_scolarite_paid($pdo, $etudiantId);
            }

            $msg = 'Facture ' . ($f['numero'] ?? '') . ' marquée comme payée (' . $moyen . ' le ' . substr($payeAt, 0, 10) . ').';
            if ($preadmisCreated) $msg .= $preadmisCreated;
            if ($promoted) $msg .= ' Compte promu en « étudiant ».';

            if ($candidatureId > 0) {
                admin_log_action($candidatureId, 'mark_facture_paid',
                    ($f['numero'] ?? '#' . $id) . ' — ' . $moyen);
            }
            api_json(['ok' => true, 'message' => $msg, 'promoted' => $promoted]);
        }

        case 'mark_unpaid': {
            $pdo->prepare("UPDATE factures
                           SET statut_paiement='en_attente', paye_at=NULL, paye_par_admin=NULL
                           WHERE id=?")->execute([$id]);
            if (($f['type'] ?? '') === 'frais_dossier' && $candidatureId > 0) {
                $pdo->prepare("UPDATE candidatures
                               SET facture_payee=0, facture_payee_at=NULL, facture_payee_par=NULL
                               WHERE id=?")->execute([$candidatureId]);
            }
            if ($candidatureId > 0) {
                admin_log_action($candidatureId, 'mark_facture_unpaid', $f['numero'] ?? '#' . $id);
            }
            api_json(['ok' => true, 'message' => 'Paiement annulé.']);
        }

        default:
            api_error('Action inconnue : ' . $action, 400);
    }
} catch (\Throwable $e) {
    error_log('[admin-api/facture-action] ' . $action . ' #' . $id . ' : ' . $e->getMessage());
    api_error('Erreur : ' . $e->getMessage(), 500);
}
