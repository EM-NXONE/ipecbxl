<?php
/**
 * IPEC — API étudiant (JSON).
 *
 * Bootstrap commun aux endpoints sous /api/ de lms.ipec.school.
 * Réutilise les helpers etu_* du dossier etudiant existant (sessions,
 * mots de passe, tokens, validation, rate limit).
 */

declare(strict_types=1);

require_once __DIR__ . '/../etudiant/_bootstrap.php';

const CORS_ALLOWED_ORIGINS = [
    'https://lms.ipec.school',
    'https://ipec.school',
    'https://www.ipec.school',
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

function api_require_etu(): array {
    $u = etu_current();
    if (!$u) api_error('Non authentifié', 401);
    return $u;
}

api_cors();
