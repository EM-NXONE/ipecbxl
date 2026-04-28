<?php
/**
 * IPEC — Espace étudiant : bootstrap commun
 *
 * - Session côté serveur stockée en BDD (table `etudiant_sessions`),
 *   cookie httpOnly opaque (token aléatoire 64 hex).
 * - Auth : email + bcrypt (table `etudiants`).
 * - Tokens d'activation / reset : table `etudiant_tokens` (sha256, usage unique).
 * - PDF JAMAIS stockés : régénérés à la volée depuis SQL + FPDF.
 *
 * Inclus en tête de chaque page de /etudiant/. Charge également
 * mailer.php en mode librairie pour profiter des builders PDF.
 */

declare(strict_types=1);

// Builders PDF + DB (mailer.php expose buildCandidaturePdf / buildFacturePdf via db_config.php)
define('IPEC_MAILER_AS_LIB', true);
require_once __DIR__ . '/../mailer.php';

// ---------------------------------------------------------------------------
// Constantes
// ---------------------------------------------------------------------------
const ETU_COOKIE_NAME      = 'IPEC_ETU';
const ETU_SESSION_LIFETIME = 30 * 24 * 3600;  // 30 jours
const ETU_SESSION_REFRESH  = 7 * 24 * 3600;   // refresh expiration toutes les 7 jours
const ETU_RATE_LIMIT_DIR   = __DIR__ . '/../../.ipec-etu-ratelimit';

// Hôte canonique du portail étudiant (LMS). Sert pour générer les liens
// absolus envoyés par email (activation, reset).
const ETU_CANONICAL_HOST   = 'lms.ipec.school';

// ---------------------------------------------------------------------------
// Détection du contexte (sous-domaine LMS vs /etudiant/ sur le site principal)
// ---------------------------------------------------------------------------

/**
 * Renvoie le préfixe d'URL du portail étudiant pour le contexte courant.
 *  - Sur lms.ipec.school                → ""           (les pages sont à la racine)
 *  - Sur ipec.school (legacy)           → "/etudiant"
 */
function etu_base_path(): string {
    $host = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
    if ($host === ETU_CANONICAL_HOST || str_starts_with($host, 'lms.')) {
        return '';
    }
    return '/etudiant';
}

/**
 * Construit une URL relative correcte pour le contexte courant.
 *   etu_url('/login.php') → "/login.php" (LMS) ou "/etudiant/login.php" (legacy)
 */
function etu_url(string $path): string {
    if ($path === '' || $path[0] !== '/') $path = '/' . $path;
    return etu_base_path() . $path;
}

/**
 * URL absolue canonique (toujours sur le sous-domaine LMS).
 * À utiliser pour les liens envoyés par e-mail (activation, reset).
 */
function etu_absolute_url(string $path): string {
    if ($path === '' || $path[0] !== '/') $path = '/' . $path;
    $scheme = !empty($_SERVER['HTTPS']) ? 'https' : 'http';
    return $scheme . '://' . ETU_CANONICAL_HOST . $path;
}


// ---------------------------------------------------------------------------
// PHP session minimale (pour CSRF + flash)
// ---------------------------------------------------------------------------
ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
if (!empty($_SERVER['HTTPS'])) {
    ini_set('session.cookie_secure', '1');
}
session_name('IPEC_ETU_S');
session_start();

// ---------------------------------------------------------------------------
// Helpers communs
// ---------------------------------------------------------------------------

function etu_h(?string $s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function etu_csrf_token(): string {
    if (empty($_SESSION['etu_csrf'])) {
        $_SESSION['etu_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['etu_csrf'];
}

function etu_csrf_check(): void {
    $sent = $_POST['csrf'] ?? '';
    if (!is_string($sent) || !hash_equals($_SESSION['etu_csrf'] ?? '', $sent)) {
        http_response_code(403);
        exit('Jeton CSRF invalide. Recharge la page et réessaie.');
    }
}

function etu_set_flash(string $msg, string $type = 'success'): void {
    $_SESSION['etu_flash'] = ['type' => $type, 'msg' => $msg];
}

function etu_take_flash(): ?array {
    if (empty($_SESSION['etu_flash'])) return null;
    $f = $_SESSION['etu_flash'];
    unset($_SESSION['etu_flash']);
    return $f;
}

function etu_format_date(?string $iso, bool $withTime = false): string {
    if (!$iso) return '—';
    $ts = strtotime($iso);
    if (!$ts) return etu_h($iso);
    return date($withTime ? 'd/m/Y H:i' : 'd/m/Y', $ts);
}

function etu_money_cents(int $cents, string $devise = 'EUR'): string {
    $sign = $devise === 'EUR' ? '€' : etu_h($devise);
    return number_format($cents / 100, 2, ',', ' ') . ' ' . $sign;
}

// ---------------------------------------------------------------------------
// Rate limit fichier (par IP, fenêtre glissante)
// ---------------------------------------------------------------------------

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

// ---------------------------------------------------------------------------
// Sessions étudiantes (cookie ⇄ table etudiant_sessions)
// ---------------------------------------------------------------------------

function etu_session_create(int $etudiantId): string {
    $token = bin2hex(random_bytes(32)); // 64 hex
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
        'path'     => '/etudiant/',
        'secure'   => !empty($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    return $token;
}

function etu_session_destroy(): void {
    $token = $_COOKIE[ETU_COOKIE_NAME] ?? null;
    if ($token) {
        try {
            db()->prepare("DELETE FROM etudiant_sessions WHERE id = ?")->execute([$token]);
        } catch (\Throwable $e) {}
    }
    setcookie(ETU_COOKIE_NAME, '', [
        'expires'  => time() - 3600,
        'path'     => '/etudiant/',
        'secure'   => !empty($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

/** @return array|null Étudiant connecté ou null */
function etu_current(): ?array {
    static $cached = false;
    static $value = null;
    if ($cached) return $value;
    $cached = true;

    $token = $_COOKIE[ETU_COOKIE_NAME] ?? null;
    if (!$token || !preg_match('/^[a-f0-9]{64}$/', $token)) return $value = null;

    $pdo = db();
    $stmt = $pdo->prepare(
        "SELECT s.*, e.id AS e_id, e.email, e.prenom, e.nom, e.civilite,
                e.numero_etudiant, e.statut, e.password_hash
         FROM etudiant_sessions s
         INNER JOIN etudiants e ON e.id = s.etudiant_id
         WHERE s.id = ? AND s.expires_at > NOW() LIMIT 1"
    );
    $stmt->execute([$token]);
    $row = $stmt->fetch();
    if (!$row) return $value = null;
    if (($row['statut'] ?? '') !== 'actif') return $value = null;

    // Refresh last_seen + expires (rolling window) au max une fois par requête
    try {
        $pdo->prepare(
            "UPDATE etudiant_sessions
             SET last_seen_at = NOW(), expires_at = DATE_ADD(NOW(), INTERVAL ? SECOND)
             WHERE id = ?"
        )->execute([ETU_SESSION_LIFETIME, $token]);
    } catch (\Throwable $e) {}

    return $value = [
        'id'              => (int)$row['e_id'],
        'email'           => (string)$row['email'],
        'prenom'          => (string)$row['prenom'],
        'nom'             => (string)$row['nom'],
        'civilite'        => (string)($row['civilite'] ?? ''),
        'numero_etudiant' => (string)($row['numero_etudiant'] ?? ''),
        'session_token'   => $token,
    ];
}

function etu_require_login(): array {
    $u = etu_current();
    if (!$u) {
        $next = $_SERVER['REQUEST_URI'] ?? '/etudiant/';
        header('Location: /etudiant/login.php?next=' . urlencode($next));
        exit;
    }
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
        error_log('[etudiant] log_action failed: ' . $e->getMessage());
    }
}

// ---------------------------------------------------------------------------
// Tokens activation / reset
// ---------------------------------------------------------------------------

/** Vérifie un token et renvoie l'étudiant + ligne token, ou null. */
function etu_token_consume_check(PDO $pdo, string $token, string $type): ?array {
    if (!preg_match('/^[a-f0-9]{64}$/', $token)) return null;
    $hash = hash('sha256', $token);
    $stmt = $pdo->prepare(
        "SELECT t.*, e.id AS e_id, e.email, e.prenom, e.nom, e.statut
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
    $pdo->prepare("UPDATE etudiant_tokens SET used_at = NOW() WHERE id = ?")
        ->execute([$tokenId]);
}

// ---------------------------------------------------------------------------
// Validation mot de passe
// ---------------------------------------------------------------------------

function etu_password_validate(string $pwd): ?string {
    if (mb_strlen($pwd) < 10) return 'Le mot de passe doit contenir au moins 10 caractères.';
    if (!preg_match('/[A-Z]/', $pwd)) return 'Au moins une majuscule requise.';
    if (!preg_match('/[a-z]/', $pwd)) return 'Au moins une minuscule requise.';
    if (!preg_match('/[0-9]/', $pwd)) return 'Au moins un chiffre requis.';
    return null;
}
