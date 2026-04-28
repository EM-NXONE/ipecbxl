<?php
/**
 * IPEC Admin — Bootstrap commun
 *
 * Inclus en tête de chaque page admin. Charge :
 *   - la config DB (db_config.php)
 *   - les builders PDF/HTML/PHPMailer (mailer.php en mode librairie)
 *   - démarre la session sécurisée
 *   - définit les helpers communs (auth, csrf, log, escape, etc.)
 *
 * À déposer dans public_html/admin/ sur n0c.
 * Le sous-domaine admin.ipec.school doit pointer vers ce dossier.
 */

declare(strict_types=1);

// ---------------------------------------------------------------------------
// Config — à éditer manuellement la PREMIÈRE FOIS
// ---------------------------------------------------------------------------

/**
 * Comptes admin autorisés.
 *
 *   ['identifiant' => 'hash_bcrypt_du_mot_de_passe']
 *
 * Pour générer un hash : connecte-toi en SSH ou crée un fichier temporaire
 * gen_hash.php contenant :
 *     <?php echo password_hash('TON_MOT_DE_PASSE_FORT', PASSWORD_BCRYPT);
 * Ouvre-le dans le navigateur, copie la sortie ici, puis SUPPRIME le fichier.
 */
const ADMIN_USERS = [
    // 'admin' => '$2y$12$REMPLACE_CE_HASH_PAR_LE_TIEN.................',
];

// Durée de session (secondes) avant déconnexion auto. Défaut : 4 h.
const ADMIN_SESSION_LIFETIME = 4 * 3600;

// Liste blanche d'IP optionnelle (vide = pas de filtrage IP).
const ADMIN_IP_ALLOWLIST = [];

// ---------------------------------------------------------------------------
// Bootstrap
// ---------------------------------------------------------------------------

// Charger db_config.php + builders de mailer.php SANS exécuter son pipeline HTTP
define('IPEC_MAILER_AS_LIB', true);
require_once __DIR__ . '/../mailer.php';

// Sécurité de session
ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
if (!empty($_SERVER['HTTPS'])) {
    ini_set('session.cookie_secure', '1');
}
session_name('IPEC_ADMIN');
session_start();

// Filtrage IP éventuel
if (!empty(ADMIN_IP_ALLOWLIST)) {
    $clientIp = $_SERVER['REMOTE_ADDR'] ?? '';
    if (!in_array($clientIp, ADMIN_IP_ALLOWLIST, true)) {
        http_response_code(403);
        exit('IP non autorisée.');
    }
}

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function admin_h(?string $s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function admin_is_logged_in(): bool {
    if (empty($_SESSION['admin_user']) || empty($_SESSION['admin_login_at'])) {
        return false;
    }
    if (time() - (int)$_SESSION['admin_login_at'] > ADMIN_SESSION_LIFETIME) {
        $_SESSION = [];
        session_destroy();
        return false;
    }
    return true;
}

function admin_require_login(): void {
    if (!admin_is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function admin_current_user(): string {
    return (string)($_SESSION['admin_user'] ?? '');
}

function admin_csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function admin_csrf_check(): void {
    $sent = $_POST['csrf'] ?? '';
    if (!is_string($sent) || !hash_equals($_SESSION['csrf'] ?? '', $sent)) {
        http_response_code(403);
        exit('Jeton CSRF invalide. Recharge la page et réessaie.');
    }
}

function admin_log_action(int $candidatureId, string $action, ?string $detail = null): void {
    try {
        $stmt = db()->prepare(
            "INSERT INTO admin_actions (candidature_id, action, detail, admin_user, ip)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $candidatureId,
            $action,
            $detail !== null ? mb_substr($detail, 0, 255) : null,
            admin_current_user(),
            $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    } catch (\Throwable $e) {
        error_log('[admin] log_action failed: ' . $e->getMessage());
    }
}

function admin_format_date(?string $iso): string {
    if (!$iso) return '—';
    $ts = strtotime($iso);
    return $ts ? date('d/m/Y H:i', $ts) : admin_h($iso);
}

const ADMIN_STATUTS = [
    'recue'    => 'Reçue',
    'en_cours' => 'En cours',
    'validee'  => 'Validée',
    'refusee'  => 'Refusée',
    'annulee'  => 'Annulée',
];
