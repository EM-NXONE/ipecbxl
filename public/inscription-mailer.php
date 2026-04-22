<?php
/**
 * IPEC — Relais SMTP pour le formulaire d'inscription
 * À déposer sur l'hébergement n0c (ex: public_html/inscription-mailer.php)
 *
 * Ce script est appelé DIRECTEMENT par le formulaire du site (sans intermédiaire),
 * afin que la production ne dépende d'aucun service tiers.
 *
 * Sécurité :
 *   - CORS strict (liste blanche d'origines autorisées)
 *   - Honeypot anti-bot (champ "website" qui doit rester vide)
 *   - Rate-limit par IP (max RATE_LIMIT_MAX envois / RATE_LIMIT_WINDOW secondes)
 *   - Nettoyage des en-têtes (anti-injection)
 */

// ============================================================
// CONFIGURATION
// ============================================================
const FROM_EMAIL    = 'process@ipec.school';
const FROM_NAME     = 'IPEC — Inscriptions';
const TO_EMAIL      = 'admission@ipec.school';

// Rate-limit : 5 envois max par IP toutes les 10 minutes
const RATE_LIMIT_MAX    = 5;
const RATE_LIMIT_WINDOW = 600; // secondes
// ============================================================

header('Content-Type: application/json; charset=utf-8');

// CORS — autorise uniquement les domaines du site
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

// Vérifie que l'origine est dans la liste blanche (sinon refus)
if (!in_array($origin, $allowedOrigins, true)) {
    http_response_code(403);
    echo json_encode(['error' => 'Origin not allowed']);
    exit;
}

// ----- Rate-limit par IP (fichier dans sys_get_temp_dir) -----
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$rateFile = sys_get_temp_dir() . '/ipec_inscr_' . md5($ip) . '.json';
$now = time();
$timestamps = [];
if (is_file($rateFile)) {
    $raw = @file_get_contents($rateFile);
    $decoded = json_decode($raw ?: '[]', true);
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

// Parse du JSON
$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

// Honeypot anti-bot : le champ "website" doit rester vide
if (!empty($data['website'])) {
    // On répond OK pour ne pas alerter le bot, mais on n'envoie rien
    echo json_encode(['ok' => true]);
    exit;
}

// Helpers de nettoyage
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

// Champs attendus
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

// Validation minimale
if ($prenom === '' || $nom === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Champs obligatoires manquants ou e-mail invalide']);
    exit;
}

// Construction du sujet et du corps
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
    Pour répondre au candidat, utilisez simplement la fonction « Répondre » de votre messagerie —
    le champ Reply-To est déjà configuré sur son adresse.
  </p>
</body></html>
HTML;

// En-têtes — encodage MIME et Reply-To sur l'adresse du candidat
$encodedFromName = '=?UTF-8?B?' . base64_encode(FROM_NAME) . '?=';
$encodedSubject  = '=?UTF-8?B?' . base64_encode($subject) . '?=';

$headers  = "From: $encodedFromName <" . FROM_EMAIL . ">\r\n";
$headers .= "Reply-To: " . $email . "\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/html; charset=UTF-8\r\n";
$headers .= "Content-Transfer-Encoding: 8bit\r\n";
$headers .= "X-Mailer: IPEC-Inscription/1.0\r\n";

$envelopeSender = '-f' . FROM_EMAIL;
$ok = mail(TO_EMAIL, $encodedSubject, $bodyHtml, $headers, $envelopeSender);

if (!$ok) {
    http_response_code(502);
    echo json_encode(['error' => "Échec de l'envoi"]);
    exit;
}

// Enregistre l'envoi pour le rate-limit (uniquement en cas de succès)
$timestamps[] = $now;
@file_put_contents($rateFile, json_encode(array_values($timestamps)), LOCK_EX);

echo json_encode(['ok' => true]);
