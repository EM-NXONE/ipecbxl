<?php
/**
 * POST /api/etudiant-action.php
 * Body: { id: int (etudiant_id), action: 'reset_password' | 'regen_activation' }
 *
 * Permet à l'admin de :
 *  - réinitialiser le mot de passe d'un étudiant (sans connaître l'ancien)
 *  - régénérer un lien d'activation
 * Retourne le lien à communiquer à l'étudiant.
 */
require_once __DIR__ . '/_bootstrap.php';
api_method('POST');
api_require_admin();

$body   = api_body();
$id     = (int)($body['id'] ?? 0);
$action = (string)($body['action'] ?? '');
if ($id <= 0)       api_error('id invalide', 400);
if ($action === '') api_error('action requise', 400);

$pdo = db();
$stmt = $pdo->prepare("SELECT id, numero_etudiant, prenom, nom, email FROM etudiants WHERE id = ?");
$stmt->execute([$id]);
$etu = $stmt->fetch();
if (!$etu) api_error('Étudiant introuvable.', 404);

try {
    switch ($action) {
        case 'reset_password': {
            $pdo->prepare("UPDATE etudiant_tokens SET used_at=NOW()
                           WHERE etudiant_id=? AND type='reset_password' AND used_at IS NULL")
                ->execute([$id]);
            $pdo->prepare("UPDATE etudiants SET password_hash=NULL WHERE id=?")->execute([$id]);
            $token = etudiant_create_token($pdo, $id, 'reset_password', 7 * 24 * 3600);
            api_json([
                'ok' => true,
                'message' => "Mot de passe réinitialisé pour {$etu['prenom']} {$etu['nom']}.",
                'activation_url' => 'https://lms.ipec.school/etudiant/reset/' . $token,
            ]);
        }

        case 'regen_activation': {
            $pdo->prepare("UPDATE etudiant_tokens SET used_at=NOW()
                           WHERE etudiant_id=? AND type='activation' AND used_at IS NULL")
                ->execute([$id]);
            $token = etudiant_create_token($pdo, $id, 'activation', 14 * 24 * 3600);
            api_json([
                'ok' => true,
                'message' => "Nouveau lien d'activation généré.",
                'activation_url' => 'https://lms.ipec.school/etudiant/activer/' . $token,
            ]);
        }

        default:
            api_error('Action inconnue : ' . $action, 400);
    }
} catch (\Throwable $e) {
    error_log('[admin-api/etudiant-action] ' . $action . ' #' . $id . ' : ' . $e->getMessage());
    api_error('Erreur : ' . $e->getMessage(), 500);
}
