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

// ---------- Chemins partagés ----------
$SHARED = __DIR__ . '/_shared';
$ADMIN_USERS_CACHE = null;

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
$corsFile = $SHARED . '/cors.php';
if (is_file($corsFile)) {
    require_once $corsFile;
}
if (function_exists('ipec_cors_apply')) {
    ipec_cors_apply();
} elseif (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

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

// ---------- Dépendances optionnelles ----------
function admin_shared_path(string $file): string {
    global $SHARED;
    return $SHARED . '/' . ltrim($file, '/');
}

function admin_require_db(): void {
    $path = admin_shared_path('db_config.php');
    if (!is_file($path)) api_error('Configuration base de données introuvable.', 500);
    require_once $path;
    if (!function_exists('db')) api_error('Configuration base de données invalide.', 500);
}

function admin_require_mailer(): void {
    admin_require_db();
    if (!defined('IPEC_MAILER_AS_LIB')) define('IPEC_MAILER_AS_LIB', true);
    $path = admin_shared_path('mailer.php');
    if (!is_file($path)) api_error('Librairie e-mail/PDF introuvable.', 500);
    require_once $path;
}

function admin_require_etudiants(): void {
    admin_require_db();
    $path = admin_shared_path('_etudiants.php');
    if (!is_file($path)) api_error('Librairie étudiants introuvable.', 500);
    require_once $path;
}

// ---------- Auth admin ----------
function admin_users(): array {
    global $ADMIN_USERS_CACHE;
    if (is_array($ADMIN_USERS_CACHE)) return $ADMIN_USERS_CACHE;

    // Comptes admin (fichier non versionné, à créer sur n0c) :
    //   <?php return ['admin' => '$2y$12$...hash_bcrypt...'];
    $path = admin_shared_path('admin_users.php');
    if (!is_file($path)) return $ADMIN_USERS_CACHE = [];

    ob_start();
    try {
        $users = require $path;
        return $ADMIN_USERS_CACHE = is_array($users) ? $users : [];
    } catch (\Throwable $e) {
        error_log('[admin-api] admin_users.php failed: ' . $e->getMessage());
        return $ADMIN_USERS_CACHE = [];
    } finally {
        if (ob_get_level() > 0) ob_end_clean();
    }
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
        admin_require_db();
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
