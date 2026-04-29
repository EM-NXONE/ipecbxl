<?php
/**
 * POST /api/etudiant-action.php
 * Body: { id: int (etudiant_id), action: 'reset_password' }
 *
 * Permet à l'admin de réinitialiser le mot de passe d'un étudiant au mot
 * de passe par défaut "Student1". L'étudiant pourra le changer ensuite
 * depuis son espace.
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
            $pdo->prepare("UPDATE etudiants SET password_hash=?, statut='actif' WHERE id=?")
                ->execute([password_hash(ETU_DEFAULT_PASSWORD, PASSWORD_BCRYPT), $id]);
            $pdo->prepare("DELETE FROM etudiant_sessions WHERE etudiant_id=?")->execute([$id]);
            api_json([
                'ok' => true,
                'message' => "Mot de passe de {$etu['prenom']} {$etu['nom']} réinitialisé à : " . ETU_DEFAULT_PASSWORD,
                'default_password' => ETU_DEFAULT_PASSWORD,
            ]);
        }

        default:
            api_error('Action inconnue : ' . $action, 400);
    }
} catch (\Throwable $e) {
    error_log('[admin-api/etudiant-action] ' . $action . ' #' . $id . ' : ' . $e->getMessage());
    api_error('Erreur : ' . $e->getMessage(), 500);
}
