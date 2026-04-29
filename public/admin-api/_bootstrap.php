<?php
/**
 * IPEC — API administrateur (JSON).
 *
 * Bootstrap commun à tous les endpoints sous /api/ de admin.ipec.school.
 *
 * Architecture :
 * - Le React buildé statique est servi à la racine de admin.ipec.school.
 * - Les endpoints PHP sont sous admin.ipec.school/api/*.php
 * - Le React appelle fetch('/api/login.php', { credentials: 'include' })
 *   → même origine, cookies SameSite=Lax suffisent.
 *
 * Si tu déploies le React ailleurs (ex: ipecbxl.lovable.app) qui appelle
 * cette API en cross-origin, ajuste CORS_ALLOWED_ORIGINS et passe les
 * cookies en SameSite=None; Secure (voir admin_session_create).
 */

declare(strict_types=1);

// Réutilise la BDD et helpers du dossier admin existant
require_once __DIR__ . '/../admin/_bootstrap.php';

// ---------------------------------------------------------------------------
// CORS — origines autorisées
// ---------------------------------------------------------------------------
const CORS_ALLOWED_ORIGINS = [
    'https://admin.ipec.school',
    'https://ipec.school',
    'https://www.ipec.school',
    // Dev Lovable (à retirer en prod stricte)
    'https://ipecbxl.lovable.app',
];

function api_cors(): void {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if (in_array($origin, CORS_ALLOWED_ORIGINS, true)) {
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Access-Control-Allow-Credentials: true');
        header('Vary: Origin');
    }
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Accept');
    header('Access-Control-Max-Age: 86400');
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}

// ---------------------------------------------------------------------------
// Helpers JSON
// ---------------------------------------------------------------------------

function api_json(array $data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function api_error(string $message, int $status = 400): void {
    api_json(['error' => $message], $status);
}

function api_body(): array {
    $raw = file_get_contents('php://input');
    if (!$raw) return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function api_method(string ...$allowed): void {
    $m = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if (!in_array($m, $allowed, true)) {
        api_error('Méthode non autorisée', 405);
    }
}

// ---------------------------------------------------------------------------
// Auth admin — réutilise les helpers admin_* de admin/_bootstrap.php
// (admin_is_logged_in, admin_current_user, ADMIN_USERS)
// ---------------------------------------------------------------------------

function api_require_admin(): string {
    if (!admin_is_logged_in()) {
        api_error('Non authentifié', 401);
    }
    return admin_current_user();
}

api_cors();
