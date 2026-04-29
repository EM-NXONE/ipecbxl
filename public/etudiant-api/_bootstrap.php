<?php
/**
 * IPEC — Bootstrap API étudiant (lms.ipec.school/api/*)
 *
 * - Renvoie systématiquement du JSON.
 * - Sessions : table `etudiant_sessions` (token 64 hex en cookie httpOnly IPEC_ETU),
 *   exactement comme l'ancien public/etudiant/_bootstrap.php.
 * - Auth : email + identité civile (prénom/nom/date_naissance) + mot de passe bcrypt.
 * - Tokens activation / reset : table `etudiant_tokens` (sha256, usage unique).
 * - Réutilise les helpers existants déposés dans ./_shared/ (db, mailer en lib, FPDF, etc.).
 */

declare(strict_types=1);

// ---------- Constantes ----------
const ETU_COOKIE_NAME      = 'IPEC_ETU';
const ETU_SESSION_LIFETIME = 30 * 24 * 3600;  // 30 jours
const ETU_RATE_LIMIT_DIR   = __DIR__ . '/../../.ipec-etu-ratelimit';
const ETU_CANONICAL_HOST   = 'lms.ipec.school';

// ---------- Chargement des dépendances partagées ----------
$SHARED = __DIR__ . '/_shared';
require_once $SHARED . '/db_config.php';

if (!defined('IPEC_MAILER_AS_LIB')) define('IPEC_MAILER_AS_LIB', true);
require_once $SHARED . '/mailer.php';

// FPDF + classes PDF déjà chargés par mailer.php en mode lib, mais on s'assure :
if (!class_exists('FPDF') && is_file($SHARED . '/FPDF/fpdf.php')) {
    if (!defined('FPDF_FONTPATH')) define('FPDF_FONTPATH', $SHARED . '/FPDF/font/');
    require_once $SHARED . '/FPDF/fpdf.php';
}
if (!class_exists('IpecCandidaturePdf') && is_file($SHARED . '/_pdf_classes.php')) {
    require_once $SHARED . '/_pdf_classes.php';
}

// ---------- CORS (mutualisé entre les 3 portails) ----------
require_once $SHARED . '/cors.php';
ipec_cors_apply();

// JSON par défaut (telecharger.php override avec Content-Type: application/pdf)
if (!isset($GLOBALS['ETU_RAW_OUTPUT'])) {
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: no-store');
}

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

// ---------- Rate limit (fichier, par IP, fenêtre glissante) ----------
function etu_rate_limit(string $bucket, int $max, int $windowSeconds): bool {
    if (!is_dir(ETU_RATE_LIMIT_DIR)) {
        @mkdir(ETU_RATE_LIMIT_DIR, 0700, true);
    }
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $key = preg_replace('/[^A-Za-z0-9_.-]/', '_', $bucket . '_' . $ip);
    $file = ETU_RATE_LIMIT_DIR . '/' . $key . '.json';
    $now = time();
    $stamps = [];
    if (is_file($file)) {
        $raw = @file_get_contents($file);
        $arr = $raw ? json_decode($raw, true) : null;
        if (is_array($arr)) $stamps = $arr;
    }
    $stamps = array_values(array_filter($stamps, fn($t) => is_int($t) && ($now - $t) < $windowSeconds));
    if (count($stamps) >= $max) return false;
    $stamps[] = $now;
    @file_put_contents($file, json_encode($stamps), LOCK_EX);
    return true;
}

// ---------- Sessions étudiantes (cookie ↔ etudiant_sessions) ----------
function etu_session_create(int $etudiantId): string {
    $token = bin2hex(random_bytes(32));
    $exp = date('Y-m-d H:i:s', time() + ETU_SESSION_LIFETIME);
    db()->prepare(
        "INSERT INTO etudiant_sessions (id, etudiant_id, ip, user_agent, expires_at)
         VALUES (?, ?, ?, ?, ?)"
    )->execute([
        $token,
        $etudiantId,
        $_SERVER['REMOTE_ADDR'] ?? null,
        mb_substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
        $exp,
    ]);
    setcookie(ETU_COOKIE_NAME, $token, [
        'expires'  => time() + ETU_SESSION_LIFETIME,
        'path'     => '/',
        'secure'   => !empty($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    return $token;
}

function etu_session_destroy(): void {
    $token = $_COOKIE[ETU_COOKIE_NAME] ?? null;
    if ($token) {
        try { db()->prepare("DELETE FROM etudiant_sessions WHERE id = ?")->execute([$token]); } catch (\Throwable $e) {}
    }
    setcookie(ETU_COOKIE_NAME, '', [
        'expires' => time() - 3600, 'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']),
        'httponly' => true, 'samesite' => 'Lax',
    ]);
}

function etu_current(): ?array {
    static $cached = false; static $value = null;
    if ($cached) return $value;
    $cached = true;

    $token = $_COOKIE[ETU_COOKIE_NAME] ?? null;
    if (!$token || !preg_match('/^[a-f0-9]{64}$/', $token)) return $value = null;

    $pdo = db();
    $stmt = $pdo->prepare(
        "SELECT s.id AS session_token,
                e.id, e.email, e.prenom, e.nom, e.civilite, e.numero_etudiant,
                e.statut, e.password_hash
         FROM etudiant_sessions s
         INNER JOIN etudiants e ON e.id = s.etudiant_id
         WHERE s.id = ? AND s.expires_at > NOW() LIMIT 1"
    );
    $stmt->execute([$token]);
    $row = $stmt->fetch();
    if (!$row || ($row['statut'] ?? '') !== 'actif') return $value = null;

    try {
        $pdo->prepare(
            "UPDATE etudiant_sessions
             SET last_seen_at = NOW(), expires_at = DATE_ADD(NOW(), INTERVAL ? SECOND)
             WHERE id = ?"
        )->execute([ETU_SESSION_LIFETIME, $token]);
    } catch (\Throwable $e) {}

    return $value = [
        'id'              => (int)$row['id'],
        'email'           => (string)$row['email'],
        'prenom'          => (string)$row['prenom'],
        'nom'             => (string)$row['nom'],
        'civilite'        => (string)($row['civilite'] ?? ''),
        'numero_etudiant' => (string)($row['numero_etudiant'] ?? ''),
        'session_token'   => (string)$token,
    ];
}

function api_require_etudiant(): array {
    $u = etu_current();
    if (!$u) api_error('Non authentifié', 401);
    return $u;
}

function etu_log_action(int $etudiantId, string $action, ?string $detail = null): void {
    try {
        db()->prepare(
            "INSERT INTO etudiant_actions (etudiant_id, action, detail, ip, user_agent)
             VALUES (?, ?, ?, ?, ?)"
        )->execute([
            $etudiantId,
            $action,
            $detail !== null ? mb_substr($detail, 0, 255) : null,
            $_SERVER['REMOTE_ADDR'] ?? null,
            mb_substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
        ]);
    } catch (\Throwable $e) {
        error_log('[etudiant-api] log_action failed: ' . $e->getMessage());
    }
}

// ---------- Tokens activation / reset ----------
function etu_token_consume_check(PDO $pdo, string $token, string $type): ?array {
    if (!preg_match('/^[a-f0-9]{64}$/', $token)) return null;
    $hash = hash('sha256', $token);
    $stmt = $pdo->prepare(
        "SELECT t.id AS token_id, t.etudiant_id,
                e.id AS e_id, e.email, e.prenom, e.nom, e.statut
         FROM etudiant_tokens t
         INNER JOIN etudiants e ON e.id = t.etudiant_id
         WHERE t.token_hash = ? AND t.type = ?
           AND t.used_at IS NULL AND t.expires_at > NOW()
         LIMIT 1"
    );
    $stmt->execute([$hash, $type]);
    $row = $stmt->fetch();
    return $row ?: null;
}
function etu_token_mark_used(PDO $pdo, int $tokenId): void {
    $pdo->prepare("UPDATE etudiant_tokens SET used_at = NOW() WHERE id = ?")->execute([$tokenId]);
}

// ---------- Validation mot de passe ----------
function etu_password_validate(string $pwd): ?string {
    if (mb_strlen($pwd) < 10) return 'Le mot de passe doit contenir au moins 10 caractères.';
    if (!preg_match('/[A-Z]/', $pwd)) return 'Au moins une majuscule requise.';
    if (!preg_match('/[a-z]/', $pwd)) return 'Au moins une minuscule requise.';
    if (!preg_match('/[0-9]/', $pwd)) return 'Au moins un chiffre requis.';
    return null;
}

// ---------- URL absolue (pour les liens d'activation/reset envoyés par mail) ----------
function etu_absolute_url(string $path): string {
    if ($path === '' || $path[0] !== '/') $path = '/' . $path;
    return 'https://' . ETU_CANONICAL_HOST . $path;
}
