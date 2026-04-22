<?php
/**
 * IPEC — Relais SMTP authentifié pour le formulaire d'inscription
 * À déposer sur l'hébergement n0c (ex: public_html/inscription-mailer.php)
 *
 * Ce script est appelé DIRECTEMENT par le formulaire du site.
 * Il envoie les e-mails via SMTP authentifié (avec mot de passe),
 * ce qui garantit SPF/DKIM corrects et évite les dossiers spam.
 *
 * Sécurité :
 *   - CORS strict (liste blanche d'origines)
 *   - Honeypot anti-bot (champ "website" qui doit rester vide)
 *   - Rate-limit par IP (max RATE_LIMIT_MAX envois / RATE_LIMIT_WINDOW secondes)
 *   - Identifiants SMTP lus depuis un fichier .env hors du dossier web
 *   - Nettoyage des en-têtes (anti-injection)
 *
 * Prérequis sur n0c :
 *   1. Installer PHPMailer (via Composer ou en téléchargeant les fichiers)
 *      → cf. instructions en bas de ce fichier
 *   2. Créer un fichier ".ipec-mailer.env" UN NIVEAU AU-DESSUS du dossier
 *      public_html (donc hors web), contenant :
 *           SMTP_HOST=mail.ipec.school
 *           SMTP_PORT=465
 *           SMTP_SECURE=ssl
 *           SMTP_USER=process@ipec.school
 *           SMTP_PASS=le_mot_de_passe_de_la_boite
 */

// ============================================================
// CONFIGURATION
// ============================================================
const FROM_EMAIL = 'process@ipec.school';
const FROM_NAME  = 'IPEC — Inscriptions';
const TO_EMAIL   = 'admission@ipec.school';

// Rate-limit : 5 envois max par IP toutes les 10 minutes
const RATE_LIMIT_MAX    = 5;
const RATE_LIMIT_WINDOW = 600; // secondes

// Chemin du fichier .env (HORS du dossier public_html pour éviter
// qu'il soit téléchargeable via le web).
// __DIR__ = dossier de ce script (ex: /home/USER/public_html)
// dirname(__DIR__) = parent (ex: /home/USER) — non accessible via HTTP.
const ENV_FILE = __DIR__ . '/../.ipec-mailer.env';
// ============================================================

header('Content-Type: application/json; charset=utf-8');

// ----- CORS -----
$allowedOrigins = [
    'https://ipecbxl.lovable.app',
    'https://www.ipec.school',
    'https://ipec.school',
];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowedOrigins, true)) {
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    header('Vary: Origin');
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

if (!in_array($origin, $allowedOrigins, true)) {
    http_response_code(403);
    echo json_encode(['error' => 'Origin not allowed']);
    exit;
}

// ----- Rate-limit par IP -----
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$rateFile = sys_get_temp_dir() . '/ipec_inscr_' . md5($ip) . '.json';
$now = time();
$timestamps = [];
if (is_file($rateFile)) {
    $rawRate = @file_get_contents($rateFile);
    $decoded = json_decode($rawRate ?: '[]', true);
    if (is_array($decoded)) {
        $timestamps = array_filter(
            $decoded,
            fn($t) => is_int($t) && ($now - $t) < RATE_LIMIT_WINDOW
        );
    }
}
if (count($timestamps) >= RATE_LIMIT_MAX) {
    http_response_code(429);
    echo json_encode(['error' => 'Trop de tentatives. Réessayez plus tard.']);
    exit;
}

// ----- Parsing JSON -----
$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

// Honeypot anti-bot
if (!empty($data['website'])) {
    echo json_encode(['ok' => true]);
    exit;
}

// ----- Helpers de nettoyage -----
function clean(string $v, int $max = 250): string {
    $v = trim($v);
    $v = str_replace(["\r", "\n", "\0"], ' ', $v);
    return mb_substr($v, 0, $max);
}
function cleanMultiline(string $v, int $max = 2000): string {
    $v = trim($v);
    $v = str_replace(["\r\n", "\r"], "\n", $v);
    return mb_substr($v, 0, $max);
}

// ----- Champs attendus -----
$civilite       = clean($data['civilite']       ?? '', 30);
$prenom         = clean($data['prenom']         ?? '', 100);
$nom            = clean($data['nom']            ?? '', 100);
$dateNaissance  = clean($data['dateNaissance']  ?? '', 20);
$nationalite    = clean($data['nationalite']    ?? '', 100);
$email          = clean($data['email']          ?? '', 255);
$telephone      = clean($data['telephone']      ?? '', 30);
$adresse        = cleanMultiline($data['adresse'] ?? '', 250);
$paysResidence  = clean($data['paysResidence']  ?? '', 100);
$programme      = clean($data['programme']      ?? '', 10);
$annee          = clean($data['annee']          ?? '', 80);
$specialisation = clean($data['specialisation'] ?? '', 80);
$rentree        = clean($data['rentree']        ?? '', 120);
$message        = cleanMultiline($data['message'] ?? '', 1500);

if ($prenom === '' || $nom === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Champs obligatoires manquants ou e-mail invalide']);
    exit;
}

// ----- Lecture des identifiants SMTP depuis le .env -----
function loadEnv(string $path): array {
    if (!is_file($path)) {
        return [];
    }
    $env = [];
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        if (!str_contains($line, '=')) continue;
        [$k, $v] = explode('=', $line, 2);
        $env[trim($k)] = trim($v);
    }
    return $env;
}

$env = loadEnv(ENV_FILE);
$smtpHost   = $env['SMTP_HOST']   ?? '';
$smtpPort   = (int)($env['SMTP_PORT'] ?? 465);
$smtpSecure = strtolower($env['SMTP_SECURE'] ?? 'ssl'); // 'ssl' ou 'tls'
$smtpUser   = $env['SMTP_USER']   ?? '';
$smtpPass   = $env['SMTP_PASS']   ?? '';

if ($smtpHost === '' || $smtpUser === '' || $smtpPass === '') {
    http_response_code(500);
    echo json_encode(['error' => 'Configuration SMTP manquante côté serveur']);
    exit;
}

// ----- Construction du message -----
$subject = "Nouvelle candidature — $prenom $nom — $programme";

$adresseHtml = nl2br(htmlspecialchars($adresse, ENT_QUOTES, 'UTF-8'));
$messageHtml = $message !== ''
    ? nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'))
    : '<em>Aucun message</em>';

$h = fn(string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');

$bodyHtml = <<<HTML
<!doctype html>
<html lang="fr"><body style="font-family:Arial,sans-serif;color:#222;line-height:1.5;">
  <h2 style="color:#0a4a8a;">Nouvelle candidature IPEC</h2>
  <p><strong>Programme :</strong> {$h($programme)} — {$h($annee)}<br>
     <strong>Spécialisation :</strong> {$h($specialisation)}<br>
     <strong>Rentrée :</strong> {$h($rentree)}</p>
  <hr>
  <h3>Identité</h3>
  <p><strong>Civilité :</strong> {$h($civilite)}<br>
     <strong>Nom :</strong> {$h($prenom)} {$h($nom)}<br>
     <strong>Date de naissance :</strong> {$h($dateNaissance)}<br>
     <strong>Nationalité :</strong> {$h($nationalite)}</p>
  <h3>Contact</h3>
  <p><strong>E-mail :</strong> <a href="mailto:{$h($email)}">{$h($email)}</a><br>
     <strong>Téléphone :</strong> {$h($telephone)}<br>
     <strong>Pays de résidence :</strong> {$h($paysResidence)}<br>
     <strong>Adresse :</strong><br>{$adresseHtml}</p>
  <h3>Message</h3>
  <p>{$messageHtml}</p>
  <hr>
  <p style="color:#888;font-size:12px;">
    Pour répondre au candidat, utilisez la fonction « Répondre » de votre messagerie —
    le champ Reply-To est configuré sur son adresse.
  </p>
</body></html>
HTML;

// Version texte brut (améliore le score anti-spam)
$bodyText = "Nouvelle candidature IPEC\n"
    . "Programme : $programme — $annee\n"
    . "Spécialisation : $specialisation\n"
    . "Rentrée : $rentree\n\n"
    . "Identité\n"
    . "Civilité : $civilite\n"
    . "Nom : $prenom $nom\n"
    . "Date de naissance : $dateNaissance\n"
    . "Nationalité : $nationalite\n\n"
    . "Contact\n"
    . "E-mail : $email\n"
    . "Téléphone : $telephone\n"
    . "Pays de résidence : $paysResidence\n"
    . "Adresse : $adresse\n\n"
    . "Message :\n" . ($message !== '' ? $message : '(aucun)') . "\n";

// ----- Envoi via PHPMailer (SMTP authentifié) -----
// Tente d'abord le chargement Composer, sinon les fichiers PHPMailer manuels.
$composer = __DIR__ . '/vendor/autoload.php';
if (is_file($composer)) {
    require_once $composer;
} else {
    $base = __DIR__ . '/PHPMailer/src/';
    if (!is_file($base . 'PHPMailer.php')) {
        http_response_code(500);
        echo json_encode(['error' => 'PHPMailer non installé sur le serveur']);
        exit;
    }
    require_once $base . 'Exception.php';
    require_once $base . 'PHPMailer.php';
    require_once $base . 'SMTP.php';
}

$mail = new PHPMailer\PHPMailer\PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host       = $smtpHost;
    $mail->SMTPAuth   = true;
    $mail->Username   = $smtpUser;
    $mail->Password   = $smtpPass;
    $mail->SMTPSecure = $smtpSecure === 'tls'
        ? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS
        : PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = $smtpPort;
    $mail->CharSet    = 'UTF-8';
    $mail->Encoding   = 'base64';

    $mail->setFrom(FROM_EMAIL, FROM_NAME);
    $mail->addAddress(TO_EMAIL);
    $mail->addReplyTo($email, "$prenom $nom");

    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body    = $bodyHtml;
    $mail->AltBody = $bodyText;

    $mail->send();
} catch (\Throwable $e) {
    http_response_code(502);
    echo json_encode([
        'error'   => "Échec de l'envoi SMTP",
        // En production tu peux retirer ce détail si tu ne veux rien exposer :
        'details' => $mail->ErrorInfo ?: $e->getMessage(),
    ]);
    exit;
}

// Enregistre l'envoi pour le rate-limit (uniquement en cas de succès)
$timestamps[] = $now;
@file_put_contents($rateFile, json_encode(array_values($timestamps)), LOCK_EX);

echo json_encode(['ok' => true]);

/* =========================================================================
 * INSTALLATION DE PHPMAILER SUR n0c — DEUX OPTIONS
 * =========================================================================
 *
 * Option A — Avec Composer (recommandé si dispo via SSH ou panneau n0c)
 *   1. cd public_html
 *   2. composer require phpmailer/phpmailer
 *   → crée public_html/vendor/ (le script le détecte automatiquement)
 *
 * Option B — Sans Composer (manuel, 100% panneau de fichiers)
 *   1. Télécharger PHPMailer :
 *      https://github.com/PHPMailer/PHPMailer/archive/refs/tags/v6.9.1.zip
 *   2. Décompresser et uploader le dossier "src" à cet endroit :
 *      public_html/PHPMailer/src/
 *      → contenant Exception.php, PHPMailer.php, SMTP.php
 *
 * ===== FICHIER .ipec-mailer.env À CRÉER =====
 * Emplacement : UN NIVEAU AU-DESSUS de public_html
 *   ex: /home/TON_USER/.ipec-mailer.env
 *
 * Contenu (à adapter — demande les infos SMTP exactes au support n0c) :
 *   SMTP_HOST=mail.ipec.school
 *   SMTP_PORT=465
 *   SMTP_SECURE=ssl
 *   SMTP_USER=process@ipec.school
 *   SMTP_PASS=LE_MOT_DE_PASSE_DE_LA_BOITE
 *
 * Permissions recommandées : chmod 600 .ipec-mailer.env
 *
 * ===== POUR ENCORE MIEUX ÉVITER LES SPAMS =====
 * Demande à n0c de vérifier que ces enregistrements DNS sont actifs sur
 * ipec.school :
 *   - SPF   : v=spf1 include:_spf.n0c.com ~all (ou équivalent fourni par n0c)
 *   - DKIM  : activé dans le panneau n0c pour la boîte process@ipec.school
 *   - DMARC : v=DMARC1; p=none; rua=mailto:postmaster@ipec.school
 * ========================================================================= */
