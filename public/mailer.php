<?php
/**
 * IPEC — Relais SMTP authentifié pour les formulaires du site
 * À déposer sur l'hébergement n0c (ex: public_html/mailer.php)
 *
 * Gère DEUX types de formulaires en un seul script :
 *   - type=inscription  → admission@ipec.school
 *   - type=contact      → contact@ipec.school
 *
 * Expéditeur (From) dans les deux cas : process@ipec.school
 * Reply-To = adresse e-mail saisie par le visiteur.
 *
 * Sécurité :
 *   - CORS strict (liste blanche d'origines)
 *   - Honeypot anti-bot (champ "website" qui doit rester vide)
 *   - Rate-limit par IP (max RATE_LIMIT_MAX envois / RATE_LIMIT_WINDOW secondes)
 *   - Identifiants SMTP lus depuis un fichier .env hors du dossier web
 *   - Nettoyage des en-têtes (anti-injection)
 *
 * Prérequis sur n0c :
 *   1. Uploader le dossier PHPMailer/ à côté de ce fichier
 *      (public_html/PHPMailer/src/{Exception,PHPMailer,SMTP}.php)
 *   2. Créer un fichier ".ipec-mailer.env" UN NIVEAU AU-DESSUS de
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
const FROM_NAME  = 'IPEC — Site web';

// Destinataires selon le type de formulaire
const RECIPIENTS = [
    'inscription' => 'admission@ipec.school',
    'contact'     => 'contact@ipec.school',
];

// Rate-limit : 5 envois max par IP toutes les 10 minutes
const RATE_LIMIT_MAX    = 5;
const RATE_LIMIT_WINDOW = 600;

// Chemin du fichier .env (HORS du dossier public_html).
const ENV_FILE = __DIR__ . '/../.ipec-mailer.env';
// ============================================================

// ----- Mode debug : ajoute ?debug=1 à l'URL pour voir les erreurs PHP -----
// (à utiliser ponctuellement pour diagnostiquer un 500, à laisser en place sinon)
$DEBUG = isset($_GET['debug']) && $_GET['debug'] === '1';
if ($DEBUG) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
}

// Capture toute erreur fatale et la renvoie en JSON lisible (au lieu d'un 500 vide)
register_shutdown_function(function () use (&$DEBUG) {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode([
            'error'   => 'Erreur serveur PHP',
            'details' => $DEBUG ? $err : 'Activez ?debug=1 pour voir le détail',
        ]);
    }
});

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
$rateFile = sys_get_temp_dir() . '/ipec_mailer_' . md5($ip) . '.json';
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

// ----- Routage par type de formulaire -----
$type = isset($data['type']) ? (string)$data['type'] : '';
if (!isset(RECIPIENTS[$type])) {
    http_response_code(400);
    echo json_encode(['error' => 'Type de formulaire invalide']);
    exit;
}
$toEmail = RECIPIENTS[$type];

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

$h = fn(string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');

// ----- Helpers de rendu HTML (email-safe, table-based, inline styles) -----
function emailShell(string $eyebrow, string $title, string $innerHtml): string {
    return <<<HTML
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>{$title}</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600&display=swap" rel="stylesheet">
</head>
<body style="margin:0;padding:0;background:#0F1525;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Helvetica,Arial,sans-serif;color:#111827;">
  <span style="display:none!important;visibility:hidden;opacity:0;color:transparent;height:0;width:0;font-size:1px;line-height:1px;overflow:hidden;">{$title} — IPEC Bruxelles</span>
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#0F1525;">
    <tr>
      <td align="center" style="padding:32px 16px;">
        <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width:100%;max-width:600px;">
          <tr>
            <td style="padding:0 0 20px 0;">
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                <tr>
                  <td valign="middle">
                    <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                      <tr>
                        <td valign="middle" style="background:#FFFFFF;border-radius:6px;padding:6px;">
                          <img src="cid:ipec-logo" alt="IPEC" width="40" height="40" style="display:block;width:40px;height:40px;border:0;outline:none;text-decoration:none;">
                        </td>
                        <td valign="middle" style="padding-left:14px;font-family:'Fraunces',Georgia,'Times New Roman',serif;font-size:22px;font-weight:500;color:#FFFFFF;letter-spacing:-0.01em;line-height:1;">
                          IPEC <span style="color:#9FB4E6;font-weight:400;">Bruxelles</span>
                        </td>
                      </tr>
                    </table>
                  </td>
                  <td align="right" valign="middle" style="font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',Helvetica,Arial,sans-serif;font-size:11px;color:#9FB4E6;letter-spacing:0.18em;text-transform:uppercase;">
                    Notification interne
                  </td>
                </tr>
              </table>
            </td>
          </tr>
          <tr>
            <td style="background:#FFFFFF;border-radius:8px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.25);">
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                <tr><td style="background:#2C5DDB;height:4px;line-height:4px;font-size:0;">&nbsp;</td></tr>
              </table>
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                <tr>
                  <td style="padding:36px 40px 8px 40px;">
                    <div style="font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',Helvetica,Arial,sans-serif;font-size:11px;letter-spacing:0.22em;text-transform:uppercase;color:#2C5DDB;font-weight:600;margin-bottom:10px;">
                      — {$eyebrow}
                    </div>
                    <h1 style="margin:0;font-family:'Fraunces',Georgia,'Times New Roman',serif;font-size:26px;line-height:1.25;font-weight:400;color:#111827;letter-spacing:-0.02em;">
                      {$title}
                    </h1>
                  </td>
                </tr>
                <tr>
                  <td style="padding:20px 40px 36px 40px;font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',Helvetica,Arial,sans-serif;font-size:14px;line-height:1.6;color:#111827;">
                    {$innerHtml}
                  </td>
                </tr>
              </table>
            </td>
          </tr>
          <tr>
            <td style="padding:24px 8px 0 8px;text-align:center;font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',Helvetica,Arial,sans-serif;font-size:11px;color:#7C8AA8;line-height:1.6;">
              IPEC — Institut privé des études commerciales · <a href="https://ipec.school" style="color:#9FB4E6;text-decoration:none;">ipec.school</a><br>
              E-mail automatique généré par le formulaire du site. Répondez directement pour contacter l'expéditeur.
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;
}

function emailRow(string $label, string $valueHtml): string {
    return <<<HTML
<tr>
  <td style="padding:10px 0;border-bottom:1px solid #EEF0F4;vertical-align:top;width:38%;font-size:11px;letter-spacing:0.14em;text-transform:uppercase;color:#5B6478;font-weight:600;">{$label}</td>
  <td style="padding:10px 0;border-bottom:1px solid #EEF0F4;vertical-align:top;font-size:14px;color:#111827;line-height:1.55;">{$valueHtml}</td>
</tr>
HTML;
}

function emailSectionTitle(string $title): string {
    return <<<HTML
<div style="margin:24px 0 8px 0;font-family:'Fraunces',Georgia,'Times New Roman',serif;font-size:16px;font-weight:500;color:#111827;border-left:3px solid #2C5DDB;padding-left:10px;letter-spacing:-0.01em;">
  {$title}
</div>
HTML;
}

function emailMessageBlock(string $messageHtml): string {
    return <<<HTML
<div style="margin-top:6px;background:#F7F9FC;border-left:3px solid #2C5DDB;padding:16px 18px;font-size:14px;line-height:1.65;color:#1F2937;border-radius:0 4px 4px 0;">
  {$messageHtml}
</div>
HTML;
}

/**
 * E-mail HTML envoyé AU CANDIDAT (accusé de réception + procédure à suivre).
 * Expédié depuis admission@ipec.school après chaque candidature reçue.
 */
function buildCandidateConfirmationHtml(array $f): string {
    $h = fn(string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    $prenom    = $h($f['prenom']);
    $programme = $h($f['programme']);
    $annee     = $h($f['annee']);
    $specialisation = $h($f['specialisation']);
    $rentree   = $h($f['rentree']);

    $programmeBanner = <<<HTML
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#EAF0FF;border-radius:6px;margin-bottom:8px;">
  <tr>
    <td style="padding:14px 18px;">
      <div style="font-size:11px;letter-spacing:0.2em;text-transform:uppercase;color:#2C5DDB;font-weight:600;margin-bottom:4px;">Votre candidature</div>
      <div style="font-family:'Fraunces',Georgia,'Times New Roman',serif;font-size:18px;color:#0F1525;font-weight:500;letter-spacing:-0.01em;">
        {$programme} <span style="color:#5B6478;font-weight:400;">— {$annee}</span>
      </div>
      <div style="font-size:13px;color:#374151;margin-top:4px;">
        Spécialisation : <strong>{$specialisation}</strong> · Rentrée : <strong>{$rentree}</strong>
      </div>
    </td>
  </tr>
</table>
HTML;

    $intro = <<<HTML
<p style="margin:0 0 16px 0;">
  Bonjour {$prenom},
</p>
<p style="margin:0 0 16px 0;">
  L'<strong>Institut Privé des Études Commerciales</strong> (IPEC) vous remercie pour
  l'intérêt que vous portez à nos programmes. Votre candidature a bien été enregistrée :
  vous trouverez ci-dessous la procédure à suivre pour finaliser votre dossier.
</p>
HTML;

    $step1 = emailSectionTitle('1. Préparez votre dossier')
        . <<<HTML
<p style="margin:0 0 12px 0;">
  En réponse à cet e-mail, faites-nous parvenir votre dossier de candidature complet.
  Veuillez y inclure les documents suivants :
</p>
<ul style="margin:0 0 16px 18px;padding:0;color:#1F2937;">
  <li style="margin-bottom:6px;">un curriculum vitae à jour ;</li>
  <li style="margin-bottom:6px;">une lettre de motivation exposant votre projet d'études ;</li>
  <li style="margin-bottom:6px;">une copie de votre carte d'identité ou de votre passeport ;</li>
  <li style="margin-bottom:6px;">les diplômes et relevés de notes relatifs à vos études antérieures ;</li>
  <li style="margin-bottom:6px;">le cas échéant, les justificatifs des stages ou expériences professionnelles ;</li>
  <li style="margin-bottom:6px;">la preuve de paiement des frais de dossier (400 €).</li>
</ul>
<p style="margin:0 0 16px 0;color:#5B6478;font-size:13px;">
  Pour un traitement optimal, merci de n'inclure que les documents demandés.
</p>
<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:8px 0 8px 0;">
  <tr>
    <td style="background:#2C5DDB;border-radius:6px;">
      <a href="mailto:admission@ipec.school?subject=Dossier%20de%20candidature%20-%20{$prenom}"
         style="display:inline-block;padding:11px 22px;font-family:'Inter',Helvetica,Arial,sans-serif;font-size:14px;font-weight:600;color:#FFFFFF;text-decoration:none;letter-spacing:0.02em;">
        Envoyer mon dossier de candidature
      </a>
    </td>
  </tr>
</table>
HTML;

    $step2 = emailSectionTitle('2. Réglez les frais de dossier')
        . <<<HTML
<p style="margin:0 0 12px 0;">
  Acquittez-vous des frais de dossier d'un montant de <strong>400 €</strong>
  (non remboursables). Leur versement vous donne droit à l'examen de votre
  candidature par la commission pédagogique et, le cas échéant, à un certificat
  de préadmission.
</p>
<p style="margin:0 0 6px 0;font-weight:600;color:#0F1525;">Par virement bancaire :</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#F7F9FC;border-radius:6px;margin:0 0 12px 0;">
  <tr><td style="padding:14px 18px;font-size:14px;line-height:1.7;color:#1F2937;">
    <strong>Bénéficiaire :</strong> IPEC Bruxelles<br>
    <strong>IBAN :</strong> à demander à <a href="mailto:admission@ipec.school" style="color:#2C5DDB;text-decoration:none;">admission@ipec.school</a><br>
    <strong>Montant :</strong> 400 €<br>
    <strong>Communication :</strong> {$h($f['nom'])} — {$prenom} — {$programme} — {$specialisation}
  </td></tr>
</table>
<p style="margin:0 0 16px 0;">
  Dès réception de votre paiement et de votre dossier complet, votre candidature
  sera examinée par la commission pédagogique de l'IPEC. La décision vous sera
  communiquée par e-mail.
</p>
HTML;

    $infos = emailSectionTitle('Informations générales')
        . <<<HTML
<p style="margin:0 0 6px 0;font-weight:600;color:#0F1525;">Dates de rentrée :</p>
<ul style="margin:0 0 16px 18px;padding:0;color:#1F2937;">
  <li style="margin-bottom:6px;">Rentrée académique : <strong>début octobre</strong></li>
  <li style="margin-bottom:6px;">Rentrée décalée : <strong>début février</strong></li>
</ul>
<p style="margin:0 0 6px 0;font-weight:600;color:#0F1525;">Frais de scolarité annuels :</p>
<ul style="margin:0 0 16px 18px;padding:0;color:#1F2937;">
  <li style="margin-bottom:6px;">Programme <strong>PAA</strong> (BAC+1 à BAC+3) — <strong>4 900 €/an</strong></li>
  <li style="margin-bottom:6px;">Programme <strong>PEA</strong> (BAC+4 et BAC+5) — <strong>5 900 €/an</strong></li>
</ul>
<p style="margin:0 0 16px 0;color:#5B6478;font-size:13px;">
  Le règlement peut être effectué en deux tranches, selon un échéancier convenu
  avec l'administration.
</p>
<p style="margin:0 0 16px 0;">
  Notre équipe reste à votre disposition pour toute information,
  du lundi au vendredi de 9 h 00 à 12 h 30 et de 13 h 30 à 17 h 00.
</p>
<p style="margin:24px 0 4px 0;font-family:'Fraunces',Georgia,'Times New Roman',serif;font-size:17px;color:#0F1525;">
  Nous espérons vous compter parmi nos étudiants très bientôt.
</p>
<p style="margin:0;color:#5B6478;font-size:13px;">
  — Le service des admissions de l'IPEC Bruxelles
</p>
HTML;

    $inner = $programmeBanner . $intro . $step1 . $step2 . $infos;

    // On réutilise la coquille existante (header + footer + logo) mais en remplaçant
    // l'eyebrow "Notification interne" par quelque chose de candidat-friendly.
    $shell = emailShell('Votre candidature', "Votre demande d'admission à l'IPEC", $inner);
    return str_replace('Notification interne', 'Accusé de réception', $shell);
}

// ----- Génération du PDF de candidature (preuve signée) -----
// Définition de la sous-classe au niveau global (PAS dans un eval — souvent bloqué chez les hébergeurs).
// On la déclare via require conditionnel : la classe est chargée seulement si FPDF l'est aussi.
if (!class_exists('IpecCandidaturePdf') && is_file(__DIR__ . '/FPDF/fpdf.php')) {
    // Force FPDF à chercher ses polices dans FPDF/font/ (slash final OBLIGATOIRE)
    if (!defined('FPDF_FONTPATH')) {
        define('FPDF_FONTPATH', __DIR__ . '/FPDF/font/');
    }
    require_once __DIR__ . '/FPDF/fpdf.php';
    class IpecCandidaturePdf extends FPDF {
        public function Footer() {
            $this->SetY(-18);
            $this->SetFont('Helvetica', 'I', 8);
            $this->SetTextColor(124, 138, 168);
            $this->Cell(0, 5, iconv('UTF-8', 'CP1252//TRANSLIT//IGNORE', "IPEC \xE2\x80\x94 Institut priv\xC3\xA9 des \xC3\xA9tudes commerciales \xC2\xB7 ipec.school"), 0, 1, 'C');
            $this->Cell(0, 5, iconv('UTF-8', 'CP1252//TRANSLIT//IGNORE', "Document g\xC3\xA9n\xC3\xA9r\xC3\xA9 automatiquement \xE2\x80\x94 preuve de candidature."), 0, 1, 'C');
        }
    }
}

function buildCandidaturePdf(array $f): string {
    if (!class_exists('IpecCandidaturePdf')) {
        return '';
    }

    // Conversion UTF-8 → CP1252 (Windows-1252) qui supporte le tiret cadratin —,
    // les guillemets typographiques, etc. — contrairement à ISO-8859-1 strict.
    // FPDF avec polices intégrées gère le CP1252 nativement.
    $tr = function (string $s): string {
        $out = @iconv('UTF-8', 'CP1252//TRANSLIT//IGNORE', $s);
        return $out !== false ? $out : $s;
    };

    $pdf = new IpecCandidaturePdf('P', 'mm', 'A4');
    $pdf->SetMargins(20, 20, 20);
    $pdf->SetAutoPageBreak(true, 25);
    $pdf->SetTitle($tr('Dossier de candidature IPEC'));
    $pdf->SetAuthor($tr('IPEC — Institut privé des études commerciales'));
    $pdf->SetCreator('ipec.school');
    $pdf->AddPage();

    // En-tête : logo + titre — Image() peut planter sur certains PNG (palette / alpha
     // / profondeur de bits incompatibles avec FPDF). On l'isole pour ne jamais bloquer
     // la génération entière du PDF à cause du logo.
    $logoPath = __DIR__ . '/ipec-logo-email.png';
    if (is_file($logoPath)) {
        try {
            $pdf->Image($logoPath, 20, 15, 18, 18);
        } catch (\Throwable $logoErr) {
            error_log('[mailer.php] Logo PDF ignoré : ' . $logoErr->getMessage());
        }
    }
    $pdf->SetXY(44, 18);
    $pdf->SetFont('Helvetica', 'B', 16);
    $pdf->SetTextColor(15, 21, 37);
    $pdf->Cell(0, 6, $tr('IPEC Bruxelles'), 0, 2);
    $pdf->SetX(44);
    $pdf->SetFont('Helvetica', '', 9);
    $pdf->SetTextColor(91, 100, 120);
    $pdf->Cell(0, 5, $tr('Institut privé des études commerciales'), 0, 2);

    $pdf->SetY(40);
    $pdf->SetDrawColor(44, 93, 219);
    $pdf->SetLineWidth(0.6);
    $pdf->Line(20, 40, 190, 40);

    // Titre du document
    $pdf->Ln(6);
    $pdf->SetFont('Helvetica', 'B', 18);
    $pdf->SetTextColor(15, 21, 37);
    $pdf->Cell(0, 9, $tr('Dossier de candidature'), 0, 1);

    // Date de soumission — chaque caractère littéral des lettres "à", "heure de Bruxelles"
    // doit être échappé avec \\ dans le format() pour ne pas être interprété comme un code.
    $pdf->SetFont('Helvetica', '', 10);
    $pdf->SetTextColor(91, 100, 120);
    $submittedAt = (new DateTimeImmutable('now', new DateTimeZone('Europe/Brussels')))
        ->format('d/m/Y \\à H:i \\(\\h\\e\\u\\r\\e \\d\\e \\B\\r\\u\\x\\e\\l\\l\\e\\s\\)');
    $pdf->Cell(0, 5, $tr('Soumis le ' . $submittedAt), 0, 1);
    $pdf->Ln(4);

    // Helper : section
    $section = function (string $title) use ($pdf, $tr) {
        $pdf->Ln(2);
        $pdf->SetFillColor(234, 240, 255);
        $pdf->SetTextColor(44, 93, 219);
        $pdf->SetFont('Helvetica', 'B', 10);
        $pdf->Cell(0, 7, '  ' . $tr(mb_strtoupper($title, 'UTF-8')), 0, 1, 'L', true);
        $pdf->Ln(2);
    };

    // Helper : ligne label / valeur
    $row = function (string $label, string $value) use ($pdf, $tr) {
        $pdf->SetFont('Helvetica', 'B', 9);
        $pdf->SetTextColor(91, 100, 120);
        $pdf->Cell(50, 6, $tr($label), 0, 0, 'L');
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->SetTextColor(15, 21, 37);
        $pdf->MultiCell(0, 6, $tr($value !== '' ? $value : '—'), 0, 'L');
        $pdf->Ln(0.5);
    };

    // Programme
    $section('Programme demandé');
    $row('Programme', $f['programme'] . ' — ' . $f['annee']);
    $row('Spécialisation', $f['specialisation']);
    $row('Rentrée envisagée', $f['rentree']);

    // Identité
    $section('Identité du candidat');
    $row('Civilité', $f['civilite']);
    $row('Prénom', $f['prenom']);
    $row('Nom', $f['nom']);
    $row('Date de naissance', $f['dateNaissance']);
    $row('Nationalité', $f['nationalite']);

    // Coordonnées
    $section('Coordonnées');
    $row('E-mail', $f['email']);
    $row('Téléphone', $f['telephone']);
    $row('Pays de résidence', $f['paysResidence']);
    $row('Adresse', $f['adresse']);

    // Message
    if ($f['message'] !== '') {
        $section('Message du candidat');
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->SetTextColor(15, 21, 37);
        $pdf->MultiCell(0, 5.5, $tr($f['message']), 0, 'L');
    }

    // Engagements signés
    $section('Engagements acceptés par le candidat');
    $clauses = [
        "Le candidat déclare avoir pris connaissance et accepte sans réserve les conditions particulières d'admission de l'IPEC, publiées sur https://ipec.school/cgv. Cette acceptation, formalisée par la validation électronique du formulaire de candidature, vaut signature au sens des articles XII.15 et suivants du Code de droit économique belge.",
        "Le candidat autorise l'IPEC à traiter les informations personnelles transmises dans le cadre de l'examen de sa candidature, conformément au Règlement Général sur la Protection des Données (RGPD — UE 2016/679) et à la politique de confidentialité publiée sur https://ipec.school/confidentialite.",
        "Le candidat certifie sur l'honneur l'exactitude des informations communiquées. Toute déclaration inexacte ou incomplète pourra entraîner le rejet de la candidature ou l'annulation d'une admission déjà prononcée.",
    ];
    foreach ($clauses as $i => $c) {
        $pdf->SetFont('Helvetica', 'B', 10);
        $pdf->SetTextColor(15, 21, 37);
        $pdf->Cell(6, 6, ($i + 1) . '.', 0, 0);
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->MultiCell(0, 5.5, $tr($c), 0, 'L');
        $pdf->Ln(2);
    }

    // Bloc signature électronique — saute en page suivante si pas assez de place
    $pdf->Ln(2);
    if ($pdf->GetY() > 230) {
        $pdf->AddPage();
    }
    $startY = $pdf->GetY();
    $pdf->SetFillColor(247, 249, 252);
    $pdf->SetDrawColor(44, 93, 219);
    $pdf->SetLineWidth(0.3);
    $pdf->Rect(20, $startY, 170, 34, 'DF');
    $pdf->SetXY(24, $startY + 4);
    $pdf->SetFont('Helvetica', 'B', 9);
    $pdf->SetTextColor(44, 93, 219);
    $pdf->Cell(0, 5, $tr('SIGNATURE ÉLECTRONIQUE'), 0, 1);
    $pdf->SetX(24);
    $pdf->SetFont('Helvetica', '', 9);
    $pdf->SetTextColor(15, 21, 37);
    $pdf->MultiCell(160, 5, $tr(
        'Signé électroniquement par ' . $f['prenom'] . ' ' . $f['nom']
        . ' le ' . $submittedAt . '.'
        . ' Adresse e-mail confirmée : ' . $f['email'] . '.'
        . ' Adresse IP de soumission : ' . $f['ip'] . '.'
    ), 0, 'L');

    return $pdf->Output('S');
}

// ----- Construction du message selon le type -----
$pdfAttachment = '';
$pdfFilename   = '';
if ($type === 'inscription') {
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

    $subject = "Nouvelle candidature — $prenom $nom — $programme";
    $replyToEmail = $email;
    $replyToName  = "$prenom $nom";

    $adresseHtml = $adresse !== '' ? nl2br($h($adresse)) : '<span style="color:#9CA3AF;">—</span>';
    $messageHtml = $message !== ''
        ? nl2br($h($message))
        : '<em style="color:#9CA3AF;">Aucun message accompagnant la candidature.</em>';

    $programmeBanner = <<<HTML
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#EAF0FF;border-radius:6px;margin-bottom:8px;">
  <tr>
    <td style="padding:14px 18px;">
      <div style="font-size:11px;letter-spacing:0.2em;text-transform:uppercase;color:#2C5DDB;font-weight:600;margin-bottom:4px;">Programme demandé</div>
      <div style="font-family:'Fraunces',Georgia,'Times New Roman',serif;font-size:18px;color:#0F1525;font-weight:500;letter-spacing:-0.01em;">
        {$h($programme)} <span style="color:#5B6478;font-weight:400;">— {$h($annee)}</span>
      </div>
      <div style="font-size:13px;color:#374151;margin-top:4px;">
        Spécialisation : <strong>{$h($specialisation)}</strong> · Rentrée : <strong>{$h($rentree)}</strong>
      </div>
    </td>
  </tr>
</table>
HTML;

    $identite = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">'
        . emailRow('Civilité',          $h($civilite) ?: '—')
        . emailRow('Nom complet',       '<strong>' . $h($prenom) . ' ' . $h($nom) . '</strong>')
        . emailRow('Date de naissance', $h($dateNaissance) ?: '—')
        . emailRow('Nationalité',       $h($nationalite) ?: '—')
        . '</table>';

    $contact = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">'
        . emailRow('E-mail',     '<a href="mailto:' . $h($email) . '" style="color:#2C5DDB;text-decoration:none;font-weight:600;">' . $h($email) . '</a>')
        . emailRow('Téléphone',  $telephone !== '' ? '<a href="tel:' . $h($telephone) . '" style="color:#111827;text-decoration:none;">' . $h($telephone) . '</a>' : '—')
        . emailRow('Pays',       $h($paysResidence) ?: '—')
        . emailRow('Adresse',    $adresseHtml)
        . '</table>';

    $inner = $programmeBanner
        . emailSectionTitle('Identité')
        . $identite
        . emailSectionTitle('Contact')
        . $contact
        . emailSectionTitle('Message du candidat')
        . emailMessageBlock($messageHtml);

    $bodyHtml = emailShell('Nouvelle candidature', "$prenom $nom — $programme", $inner);

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

    // Génération du PDF de candidature (preuve signée jointe à l'e-mail)
    // Non-bloquant : si FPDF est manquant ou plante, l'e-mail part quand même sans PJ.
    $pdfAttachment = '';
    $pdfFilename   = '';
    $pdfError      = null;
    try {
        $pdfAttachment = buildCandidaturePdf([
            'civilite'       => $civilite,
            'prenom'         => $prenom,
            'nom'            => $nom,
            'dateNaissance'  => $dateNaissance,
            'nationalite'    => $nationalite,
            'email'          => $email,
            'telephone'      => $telephone,
            'adresse'        => $adresse,
            'paysResidence'  => $paysResidence,
            'programme'      => $programme,
            'annee'          => $annee,
            'specialisation' => $specialisation,
            'rentree'        => $rentree,
            'message'        => $message,
            'ip'             => $ip,
        ]);
        if ($pdfAttachment !== '') {
            $safeName = preg_replace('/[^A-Za-z0-9_-]+/', '-', strtolower($prenom . '-' . $nom));
            $pdfFilename = 'candidature-IPEC-' . trim($safeName, '-') . '.pdf';
        } else {
            $pdfError = 'buildCandidaturePdf a renvoyé une chaîne vide (FPDF non chargé ?)';
        }
    } catch (\Throwable $pdfErr) {
        $pdfError = $pdfErr->getMessage() . ' @ ' . $pdfErr->getFile() . ':' . $pdfErr->getLine();
        error_log('[mailer.php] Échec génération PDF : ' . $pdfError);
        $pdfAttachment = '';
        $pdfFilename   = '';
    }

} else { // type === 'contact'
    $prenom  = clean($data['prenom']  ?? '', 100);
    $nom     = clean($data['nom']     ?? '', 100);
    $email   = clean($data['email']   ?? '', 255);
    $sujet   = clean($data['sujet']   ?? '', 150);
    $message = cleanMultiline($data['message'] ?? '', 2000);

    if ($prenom === '' || $nom === '' || $sujet === '' || $message === ''
        || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['error' => 'Champs obligatoires manquants ou e-mail invalide']);
        exit;
    }

    $subject = "Contact site — $sujet — $prenom $nom";
    $replyToEmail = $email;
    $replyToName  = "$prenom $nom";

    $messageHtml = nl2br($h($message));

    $expediteur = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">'
        . emailRow('Nom',    '<strong>' . $h($prenom) . ' ' . $h($nom) . '</strong>')
        . emailRow('E-mail', '<a href="mailto:' . $h($email) . '" style="color:#2C5DDB;text-decoration:none;font-weight:600;">' . $h($email) . '</a>')
        . '</table>';

    $inner = emailSectionTitle('Expéditeur')
        . $expediteur
        . emailSectionTitle('Message')
        . emailMessageBlock($messageHtml);

    $bodyHtml = emailShell('Formulaire de contact', $sujet, $inner);

    $bodyText = "Nouveau message — Formulaire de contact IPEC\n\n"
        . "Sujet : $sujet\n\n"
        . "De : $prenom $nom <$email>\n\n"
        . "Message :\n$message\n";
}

// ----- Lecture des identifiants SMTP -----
function loadEnv(string $path): array {
    if (!is_file($path)) return [];
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
$smtpSecure = strtolower($env['SMTP_SECURE'] ?? 'ssl');
$smtpUser   = $env['SMTP_USER']   ?? '';
$smtpPass   = $env['SMTP_PASS']   ?? '';

if ($smtpHost === '' || $smtpUser === '' || $smtpPass === '') {
    http_response_code(500);
    echo json_encode(['error' => 'Configuration SMTP manquante côté serveur']);
    exit;
}

// ----- Chargement de PHPMailer -----
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

// ----- Envoi SMTP authentifié -----
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
    $mail->addAddress($toEmail);
    $mail->addReplyTo($replyToEmail, $replyToName);

    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body    = $bodyHtml;
    $mail->AltBody = $bodyText;

    // Logo IPEC embarqué (CID) — référencé dans le HTML par cid:ipec-logo
    $logoPath = __DIR__ . '/ipec-logo-email.png';
    if (is_file($logoPath)) {
        $mail->addEmbeddedImage($logoPath, 'ipec-logo', 'ipec-logo.png', 'base64', 'image/png');
    }

    // PDF de candidature en pièce jointe (preuve signée)
    if ($pdfAttachment !== '' && $pdfFilename !== '') {
        $mail->addStringAttachment($pdfAttachment, $pdfFilename, 'base64', 'application/pdf');
    }

    $mail->send();
} catch (\Throwable $e) {
    http_response_code(502);
    echo json_encode([
        'error'   => "Échec de l'envoi SMTP",
        'details' => $mail->ErrorInfo ?: $e->getMessage(),
    ]);
    exit;
}

// Enregistre l'envoi pour le rate-limit
$timestamps[] = $now;
@file_put_contents($rateFile, json_encode(array_values($timestamps)), LOCK_EX);

// ============================================================
// 2e e-mail : ACCUSÉ DE RÉCEPTION envoyé AU CANDIDAT
// (uniquement pour les candidatures, pas pour le formulaire de contact)
// Expéditeur : admission@ipec.school — Reply-To : admission@ipec.school
// Non-bloquant : si l'envoi échoue, l'API renvoie quand même ok=true
// (l'admission a déjà été notifiée), mais on log l'erreur en debug.
// ============================================================
$candidateMailError = null;
if ($type === 'inscription') {
    try {
        $candidateHtml = buildCandidateConfirmationHtml([
            'prenom'         => $prenom,
            'nom'            => $nom,
            'programme'      => $programme,
            'annee'          => $annee,
            'specialisation' => $specialisation,
            'rentree'        => $rentree,
        ]);

        $candidateText = "Bonjour $prenom,\n\n"
            . "L'IPEC vous remercie pour votre candidature au programme $programme — $annee "
            . "(spécialisation : $specialisation, rentrée : $rentree).\n\n"
            . "Pour finaliser votre dossier :\n"
            . "1. En réponse à cet e-mail, transmettez votre CV, lettre de motivation, "
            . "copie de pièce d'identité, diplômes et relevés de notes, justificatifs "
            . "de stages éventuels, et la preuve de paiement des frais de dossier (400 €).\n"
            . "2. Réglez les frais de dossier de 400 € (non remboursables) par virement "
            . "à l'IPEC Bruxelles. Demandez-nous l'IBAN à admission@ipec.school. "
            . "Communication : $nom — $prenom — $programme — $specialisation.\n\n"
            . "Dès réception du dossier complet et du paiement, votre candidature sera "
            . "examinée par la commission pédagogique. La décision vous sera communiquée par e-mail.\n\n"
            . "— Le service des admissions de l'IPEC Bruxelles\n"
            . "admission@ipec.school\n";

        // Nouveau PHPMailer dédié au candidat — on N'envoie PAS le PDF de candidature.
        $candidateMail = new PHPMailer\PHPMailer\PHPMailer(true);
        $candidateMail->isSMTP();
        $candidateMail->Host       = $smtpHost;
        $candidateMail->SMTPAuth   = true;
        $candidateMail->Username   = $smtpUser;
        $candidateMail->Password   = $smtpPass;
        $candidateMail->SMTPSecure = $smtpSecure === 'tls'
            ? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS
            : PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        $candidateMail->Port       = $smtpPort;
        $candidateMail->CharSet    = 'UTF-8';
        $candidateMail->Encoding   = 'base64';

        // From = admission@ipec.school (mailbox dédiée, expéditeur "humain"
        // côté candidat). Reply-To pareil pour que la réponse atterrisse au
        // bon endroit (le candidat va répondre avec ses pièces jointes).
        $candidateMail->setFrom('admission@ipec.school', 'IPEC — Service des admissions');
        $candidateMail->addAddress($email, "$prenom $nom");
        $candidateMail->addReplyTo('admission@ipec.school', 'IPEC — Service des admissions');

        $candidateMail->isHTML(true);
        $candidateMail->Subject = "Votre demande d'admission à l'IPEC — procédure à suivre";
        $candidateMail->Body    = $candidateHtml;
        $candidateMail->AltBody = $candidateText;

        // Logo embarqué (CID identique au mail interne)
        if (is_file($logoPath)) {
            $candidateMail->addEmbeddedImage($logoPath, 'ipec-logo', 'ipec-logo.png', 'base64', 'image/png');
        }

        $candidateMail->send();
    } catch (\Throwable $e) {
        // On NE bloque PAS la réponse : l'admission a été notifiée, c'est ce qui compte.
        $candidateMailError = isset($candidateMail) ? ($candidateMail->ErrorInfo ?: $e->getMessage()) : $e->getMessage();
        error_log('[mailer.php] Échec envoi accusé candidat : ' . $candidateMailError);
    }
}

$response = ['ok' => true];
if ($DEBUG) {
    $response['debug'] = [
        'pdf_attached'   => $pdfAttachment !== '',
        'pdf_size_bytes' => strlen($pdfAttachment),
        'pdf_filename'   => $pdfFilename,
        'pdf_error'      => $pdfError ?? null,
        'fpdf_loaded'    => class_exists('FPDF'),
        'iconv_loaded'   => function_exists('iconv'),
        'logo_exists'    => is_file(__DIR__ . '/ipec-logo-email.png'),
    ];
}
echo json_encode($response);

/* =========================================================================
 * INSTALLATION SUR n0c
 * =========================================================================
 *
 * 1) Uploader dans public_html/ :
 *      - mailer.php (ce fichier)
 *      - ipec-logo-email.png  (logo embarqué dans les emails — IMPORTANT)
 *      - PHPMailer/src/Exception.php
 *      - PHPMailer/src/PHPMailer.php
 *      - PHPMailer/src/SMTP.php
 *
 * 2) Créer /home/VOTRE_USER/.ipec-mailer.env (HORS public_html) :
 *      SMTP_HOST=mail.ipec.school
 *      SMTP_PORT=465
 *      SMTP_SECURE=ssl
 *      SMTP_USER=process@ipec.school
 *      SMTP_PASS=...
 *      → chmod 600 .ipec-mailer.env
 *
 * 3) Si l'ancien fichier inscription-mailer.php existe encore sur n0c,
 *    SUPPRIMEZ-LE (il a été remplacé par mailer.php).
 *
 * 4) Vérifier les DNS sur ipec.school : SPF, DKIM, DMARC
 * ========================================================================= */
