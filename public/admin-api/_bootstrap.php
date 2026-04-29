<?php
/**
 * IPEC — Bootstrap API admin (admin.ipec.school/api/*)
 *
 * - Renvoie systématiquement du JSON (pas de HTML).
 * - Réutilise les helpers existants déposés dans ./_shared/ :
 *     db_config.php       → db()
 *     mailer.php (AS_LIB) → buildCandidaturePdf, buildFacturePdf, buildCandidateConfirmationHtml
 *     _pdf_classes.php    → IpecCandidaturePdf
 *     _etudiants.php      → etudiant_create_from_candidature, etudiant_create_token, etc.
 * - Sessions : PHP natives (cookie IPEC_ADMIN, httpOnly, SameSite=Lax).
 * - Authentification : ADMIN_USERS (login/hash bcrypt) — config dans ./_shared/admin_users.php
 *   pour pouvoir versionner ce bootstrap sans exposer les hashes.
 */

declare(strict_types=1);

// ---------- Constantes ----------
const ADMIN_SESSION_LIFETIME = 4 * 3600; // 4 h

// ---------- Chargement des dépendances partagées ----------
$SHARED = __DIR__ . '/_shared';
require_once $SHARED . '/db_config.php';

// Mailer + builders PDF en mode librairie (saute le pipeline HTTP du mailer)
if (!defined('IPEC_MAILER_AS_LIB')) define('IPEC_MAILER_AS_LIB', true);
require_once $SHARED . '/mailer.php';

require_once $SHARED . '/_etudiants.php';

// Comptes admin (fichier non versionné, à créer sur n0c) :
//   <?php return ['admin' => '$2y$12$...hash_bcrypt...'];
$adminUsersFile = $SHARED . '/admin_users.php';
$ADMIN_USERS = is_file($adminUsersFile) ? (array)require $adminUsersFile : [];

// Statuts métier (mêmes labels que l'ancien admin)
const ADMIN_STATUTS = [
    'recue'    => "Reçue",
    'en_cours' => "En cours d'étude",
    'validee'  => "Validée",
    'refusee'  => "Refusée",
    'annulee'  => "Annulée",
];

// ---------- Session admin ----------
ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
if (!empty($_SERVER['HTTPS'])) ini_set('session.cookie_secure', '1');
session_name('IPEC_ADMIN');
session_start();

// ---------- CORS (mutualisé entre les 3 portails) ----------
require_once $SHARED . '/cors.php';
ipec_cors_apply();

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

// ---------- Helpers JSON ----------
function api_json($data, int $status = 200): void {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function api_error(string $message, int $status = 400, array $extra = []): void {
    api_json(['error' => $message] + $extra, $status);
}

function api_method(string ...$allowed): void {
    if (!in_array($_SERVER['REQUEST_METHOD'] ?? '', $allowed, true)) {
        api_error('Method not allowed', 405);
    }
}

function api_body(): array {
    $raw = file_get_contents('php://input');
    if ($raw === '' || $raw === false) return [];
    $data = json_decode($raw, true);
    if (!is_array($data)) api_error('Invalid JSON body', 400);
    return $data;
}

// ---------- Auth admin ----------
function admin_users(): array {
    global $ADMIN_USERS;
    return $ADMIN_USERS ?: [];
}

function admin_is_logged_in(): bool {
    if (empty($_SESSION['admin_user']) || empty($_SESSION['admin_login_at'])) return false;
    if (time() - (int)$_SESSION['admin_login_at'] > ADMIN_SESSION_LIFETIME) {
        $_SESSION = [];
        session_destroy();
        return false;
    }
    return true;
}

function api_require_admin(): void {
    if (!admin_is_logged_in()) api_error('Non authentifié', 401);
}

function admin_current_user(): string {
    return (string)($_SESSION['admin_user'] ?? '');
}

function admin_log_action(int $candidatureId, string $action, ?string $detail = null): void {
    try {
        db()->prepare(
            "INSERT INTO admin_actions (candidature_id, action, detail, admin_user, ip)
             VALUES (?, ?, ?, ?, ?)"
        )->execute([
            $candidatureId,
            $action,
            $detail !== null ? mb_substr($detail, 0, 255) : null,
            admin_current_user(),
            $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    } catch (\Throwable $e) {
        error_log('[admin-api] log_action failed: ' . $e->getMessage());
    }
}
