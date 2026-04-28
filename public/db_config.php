<?php
/**
 * IPEC — Configuration de connexion à la base MySQL n0c
 *
 * ⚠️ FICHIER SENSIBLE — protégé par public/.htaccess (deny all).
 *    L'accès direct via HTTP renvoie une 403.
 *    PHP peut quand même le lire via require/include côté serveur.
 *
 * Fournit : db() → instance PDO unique (singleton).
 */

const IPEC_DB_HOST = 'localhost';
const IPEC_DB_NAME = 'txuxaqftdr_IPEC_Website';
const IPEC_DB_USER = 'txuxaqftdr_IPEC_WSAdmin';
const IPEC_DB_PASS = 'cCB63BEF9FA5231EDB58E98293B58F974EEF087FF76E70D9C7BC61FDEF2B548DB-';
const IPEC_DB_CHARSET = 'utf8mb4';

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . IPEC_DB_HOST
            . ';dbname=' . IPEC_DB_NAME
            . ';charset=' . IPEC_DB_CHARSET;
        $pdo = new PDO($dsn, IPEC_DB_USER, IPEC_DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}

/**
 * Génère une référence de candidature unique et lisible :
 *   IPEC-AAAA-XXXXXX
 * où XXXXXX = 6 caractères hexa aléatoires (majuscules).
 * Réessaie si collision (extrêmement improbable).
 */
function generateCandidatureReference(PDO $pdo): string {
    $year = date('Y');
    for ($i = 0; $i < 5; $i++) {
        $suffix = strtoupper(bin2hex(random_bytes(3))); // 6 hex chars
        $ref = 'IPEC-' . $year . '-' . $suffix;
        $stmt = $pdo->prepare('SELECT 1 FROM candidatures WHERE reference = ? LIMIT 1');
        $stmt->execute([$ref]);
        if (!$stmt->fetchColumn()) {
            return $ref;
        }
    }
    // Fallback ultra-improbable : on ajoute le timestamp
    return 'IPEC-' . $year . '-' . strtoupper(bin2hex(random_bytes(3))) . dechex(time());
}
