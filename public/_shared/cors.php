<?php
/**
 * IPEC — CORS partagé entre les 3 portails.
 *
 * Inclus en tête de chaque _bootstrap.php (admin-api, etudiant-api, site).
 * Cookies = scope par sous-domaine (pas de cookie cross-subdomain).
 * CORS = liste blanche stricte des origines autorisées.
 *
 * Usage :
 *   require_once __DIR__ . '/cors.php';
 *   ipec_cors_apply(); // émet les headers + gère OPTIONS
 */

declare(strict_types=1);

/** Origines autorisées (prod + previews Lovable). */
function ipec_allowed_origins(): array {
    return [
        'https://www.ipec.school',
        'https://ipec.school',
        'https://admin.ipec.school',
        'https://lms.ipec.school',
        // Previews Lovable
        'https://ipecbxl.lovable.app',
        'https://id-preview--e680d373-9824-4b72-b3de-ec8be69b1869.lovable.app',
    ];
}

function ipec_cors_apply(): void {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if ($origin !== '' && in_array($origin, ipec_allowed_origins(), true)) {
        header("Access-Control-Allow-Origin: $origin");
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Accept');
        header('Vary: Origin');
    }
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}
