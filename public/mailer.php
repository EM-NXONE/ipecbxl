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

/**
 * Formate une date au format jj/mm/aaaa.
 * Accepte les formats ISO (aaaa-mm-jj), aaaa/mm/jj, jj-mm-aaaa, jj/mm/aaaa, etc.
 * Renvoie la chaîne d'origine si le parsing échoue, et '' si l'entrée est vide.
 */
function formatDateFr(string $value): string {
    $value = trim($value);
    if ($value === '') return '';
    $formats = ['Y-m-d', 'Y/m/d', 'd/m/Y', 'd-m-Y', 'd.m.Y', 'Y-m-d\TH:i:s', 'Y-m-d H:i:s'];
    foreach ($formats as $fmt) {
        $dt = DateTimeImmutable::createFromFormat($fmt, $value);
        if ($dt instanceof DateTimeImmutable) {
            return $dt->format('d/m/Y');
        }
    }
    try {
        $dt = new DateTimeImmutable($value);
        return $dt->format('d/m/Y');
    } catch (\Throwable $e) {
        return $value;
    }
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
              IPEC — Institut privé des études commerciales · <a href="https://www.ipec.school" style="color:#9FB4E6;text-decoration:none;">www.ipec.school</a><br>
              Chaussée d'Alsemberg 897, 1180 Uccle, Belgique<br>
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
 * Mini moteur de template HTML.
 *  - {{var}}                  → remplacé par la valeur (échappée HTML)
 *  - {{#var}}...{{/var}}      → bloc conservé uniquement si var est "truthy"
 *                                (non vide, et != "0"/"false"/"non")
 *
 * Les variables non fournies sont remplacées par une chaîne vide.
 */
function renderEmailTemplate(string $templatePath, array $vars): string {
    $html = @file_get_contents($templatePath);
    if ($html === false) {
        // Fallback minimal si le template est introuvable (ne doit pas casser l'envoi)
        $h = fn(string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
        $prenom = $h($vars['prenom'] ?? '');
        return "<p>Bonjour {$prenom},</p><p>Nous avons bien reçu votre candidature à l'IPEC. "
             . "Notre équipe revient vers vous très prochainement.</p>"
             . "<p>— Le service des admissions de l'IPEC Bruxelles</p>";
    }

    // 1) Blocs conditionnels {{#var}}...{{/var}}
    $html = preg_replace_callback(
        '/\{\{#([a-zA-Z0-9_]+)\}\}(.*?)\{\{\/\1\}\}/s',
        function ($m) use ($vars) {
            $key = $m[1];
            $val = $vars[$key] ?? '';
            if (is_string($val)) {
                $val = trim($val);
                $low = mb_strtolower($val, 'UTF-8');
                if ($val === '' || in_array($low, ['0', 'false', 'non'], true)) {
                    return '';
                }
            } elseif (!$val) {
                return '';
            }
            return $m[2];
        },
        $html
    );

    // 2) Variables {{var}} — échappement HTML systématique
    $html = preg_replace_callback(
        '/\{\{([a-zA-Z0-9_]+)\}\}/',
        function ($m) use ($vars) {
            $val = $vars[$m[1]] ?? '';
            if (!is_string($val)) {
                $val = (string)$val;
            }
            return htmlspecialchars($val, ENT_QUOTES, 'UTF-8');
        },
        $html
    );

    return $html;
}

/**
 * E-mail HTML envoyé AU CANDIDAT (accusé de réception + procédure à suivre).
 * Charge le template public/templates/admission_candidat.html et y injecte
 * les données du candidat. La mention de spécialisation n'apparaît que si
 * un choix réel a été fait (différent de "Je ne sais pas encore").
 */
function buildCandidateConfirmationHtml(array $f, string $messageId = ''): string {
    // Données brutes (le moteur de template échappe lui-même)
    $specialisationRaw = trim((string)($f['specialisation'] ?? ''));
    $specLower = mb_strtolower($specialisationRaw, 'UTF-8');
    $hasSpec = $specialisationRaw !== ''
        && strpos($specLower, 'je ne sais pas') === false;

    // ----- Adaptation à la civilité -----
    // Mr → masculin, Mme/Mlle → féminin, Mx (non binaire) → inclusif (·e)
    $civiliteRaw = trim((string)($f['civilite'] ?? ''));
    $civLower    = mb_strtolower($civiliteRaw, 'UTF-8');
    if ($civLower === 'mr' || $civLower === 'm.' || $civLower === 'm') {
        $genre = 'm';
    } elseif (str_starts_with($civLower, 'mme') || str_starts_with($civLower, 'mlle')) {
        $genre = 'f';
    } else {
        // Mx — non binaire, ou tout autre cas → inclusif
        $genre = 'x';
    }

    switch ($genre) {
        case 'm':
            $salutation = 'Cher';
            $titre      = 'M.';
            $ne         = 'né';
            $invite     = 'invité';
            break;
        case 'f':
            $salutation = 'Chère';
            $titre      = 'Mme';
            $ne         = 'née';
            $invite     = 'invitée';
            break;
        default:
            $salutation = 'Cher·e';
            $titre      = 'Mx';
            $ne         = 'né·e';
            $invite     = 'invité·e';
    }

    // ----- Lien CTA : réponse directe au mail courant -----
    // Les clients mail (Gmail, Outlook, Apple Mail, Thunderbird) reconnaissent
    // les paramètres In-Reply-To / References dans un mailto: et rattachent
    // alors le nouveau message au fil de discussion existant.
    $subject = 'Re: Votre demande d\'admission à l\'IPEC — procédure à suivre';
    $mailtoParams = ['subject=' . rawurlencode($subject)];
    if ($messageId !== '') {
        $mailtoParams[] = 'In-Reply-To=' . rawurlencode($messageId);
        $mailtoParams[] = 'References=' . rawurlencode($messageId);
    }
    $ctaHref = 'mailto:admission@ipec.school?' . implode('&', $mailtoParams);

    $vars = [
        'prenom'              => (string)($f['prenom'] ?? ''),
        'nom'                 => (string)($f['nom'] ?? ''),
        'civilite'            => $civiliteRaw,
        'titre'               => $titre,
        'salutation'          => $salutation,
        'ne'                  => $ne,
        'invite'              => $invite,
        'date_naissance'      => (string)($f['date_naissance'] ?? ''),
        'nationalite'         => (string)($f['nationalite'] ?? ''),
        'email'               => (string)($f['email'] ?? ''),
        'telephone'           => (string)($f['telephone'] ?? ''),
        'adresse'             => (string)($f['adresse'] ?? ''),
        'pays_residence'      => (string)($f['pays_residence'] ?? ''),
        'rentree'             => (string)($f['rentree'] ?? ''),
        'programme'           => (string)($f['programme'] ?? ''),
        'annee'               => (string)($f['annee'] ?? ''),
        'specialisation'      => $hasSpec ? $specialisationRaw : '',
        'has_specialisation'  => $hasSpec ? '1' : '',
        'cta_href'            => $ctaHref,
    ];

    $templatePath = __DIR__ . '/templates/admission_candidat.html';
    return renderEmailTemplate($templatePath, $vars);
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
        /** @var string 'candidature' | 'facture' */
        public $docKind = 'candidature';
        /** @var string */
        public $factureNumero = '';
        public function Footer() {
            $tr = function (string $s): string {
                $out = @iconv('UTF-8', 'CP1252//TRANSLIT//IGNORE', $s);
                return $out !== false ? $out : $s;
            };
            $this->SetY(-22);
            // Filet
            $this->SetDrawColor(220, 226, 240);
            $this->SetLineWidth(0.2);
            $this->Line(20, $this->GetY(), 190, $this->GetY());
            $this->Ln(2);
            $this->SetFont('Helvetica', '', 8);
            $this->SetTextColor(91, 100, 120);
            $this->Cell(0, 4, $tr("Institut privé des études commerciales  ·  Chaussée d'Alsemberg 897, 1180 Uccle, Belgique"), 0, 1, 'C');
            $this->Cell(0, 4, $tr("admission@ipec.school  ·  www.ipec.school"), 0, 1, 'C');
            $this->SetFont('Helvetica', 'I', 8);
            $this->SetTextColor(124, 138, 168);
            if ($this->docKind === 'facture') {
                $label = $this->factureNumero !== ''
                    ? 'Facture n° ' . $this->factureNumero
                    : 'Facture';
            } else {
                $label = "Document généré automatiquement — preuve de candidature.";
            }
            $this->Cell(0, 4, $tr($label), 0, 1, 'C');
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
    $pdf->SetAutoPageBreak(true, 30);
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
    $pdf->SetXY(44, 20);
    $pdf->SetFont('Helvetica', 'B', 18);
    $pdf->SetTextColor(15, 21, 37);
    $pdf->Cell(0, 7, $tr('IPEC'), 0, 2);
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
    $row('Date de naissance', formatDateFr($f['dateNaissance'] ?? ''));
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

/**
 * Génère la facture des frais de dossier (400 €) pour le candidat.
 * Numéro de facture déterministe = horodatage + initiales (lisible, unique
 * en pratique pour un même candidat à la seconde près).
 * Communication structurée belge (12 chiffres formatés +++AAA/BBBB/CCCDD+++)
 * dérivée de l'horodatage pour faciliter le rapprochement bancaire.
 */
function buildFacturePdf(array $f): array {
    if (!class_exists('IpecCandidaturePdf')) {
        return ['', '', ''];
    }

    $tr = function (string $s): string {
        $out = @iconv('UTF-8', 'CP1252//TRANSLIT//IGNORE', $s);
        return $out !== false ? $out : $s;
    };

    $now        = new DateTimeImmutable('now', new DateTimeZone('Europe/Brussels'));
    $dateStr    = $now->format('d/m/Y');
    $numFacture = 'IPEC-' . $now->format('Ymd-His');

    // Communication structurée belge : 12 chiffres → +++XXX/XXXX/XXXYY+++
    // YY = (10 premiers chiffres) mod 97 (97 → 00). Standard belge.
    $base10 = substr($now->format('YmdHis'), 0, 10); // 10 chiffres
    $check  = (int)$base10 % 97;
    if ($check === 0) { $check = 97; }
    $digits12 = $base10 . str_pad((string)$check, 2, '0', STR_PAD_LEFT);
    $commStruct = '+++' . substr($digits12, 0, 3) . '/'
                . substr($digits12, 3, 4) . '/'
                . substr($digits12, 7, 5) . '+++';

    $iban = 'BE53 3770 8630 2553';
    $bic  = 'BBRUBEBB';

    $montant = 400.00;

    $pdf = new IpecCandidaturePdf('P', 'mm', 'A4');
    $pdf->docKind = 'facture';
    $pdf->factureNumero = $numFacture;
    $pdf->SetMargins(20, 20, 20);
    $pdf->SetAutoPageBreak(true, 30);
    $pdf->SetTitle($tr('Facture frais de dossier IPEC'));
    $pdf->SetAuthor($tr('IPEC — Institut privé des études commerciales'));
    $pdf->SetCreator('www.ipec.school');
    $pdf->AddPage();

    // En-tête : logo + IPEC (les coordonnées vont dans le footer)
    $logoPath = __DIR__ . '/ipec-logo-email.png';
    if (is_file($logoPath)) {
        try { $pdf->Image($logoPath, 20, 15, 18, 18); }
        catch (\Throwable $e) { error_log('[mailer.php] Logo facture ignoré : ' . $e->getMessage()); }
    }
    // En-tête identique à celui du site : "IPEC" + sous-titre uppercase tracking-wide muted.
    $pdf->SetXY(44, 19);
    $pdf->SetFont('Helvetica', 'B', 20);
    $pdf->SetTextColor(15, 21, 37);
    $pdf->Cell(0, 7, $tr('IPEC'), 0, 2);
    $pdf->SetX(44);
    $pdf->SetFont('Helvetica', '', 6.5);
    $pdf->SetTextColor(120, 130, 150);
    // Faux letter-spacing : on insère une fine espace entre chaque caractère
    // pour reproduire le tracking-[0.2em] uppercase du header du site.
    $subtitle = 'INSTITUT PRIVÉ DES ÉTUDES COMMERCIALES';
    $spaced   = implode(' ', preg_split('//u', $subtitle, -1, PREG_SPLIT_NO_EMPTY));
    $pdf->Cell(0, 4, $tr($spaced), 0, 2);

    // Bloc identification facture (à droite)
    $pdf->SetXY(130, 20);
    $pdf->SetFont('Helvetica', 'B', 13);
    $pdf->SetTextColor(44, 93, 219);
    $pdf->Cell(60, 7, $tr('FACTURE'), 0, 2, 'R');
    $pdf->SetX(130);
    $pdf->SetFont('Helvetica', '', 9);
    $pdf->SetTextColor(91, 100, 120);
    $pdf->Cell(60, 5, $tr('N° ' . $numFacture), 0, 2, 'R');
    $pdf->SetX(130);
    $pdf->Cell(60, 5, $tr('Date : ' . $dateStr), 0, 2, 'R');

    $pdf->SetY(40);
    $pdf->SetDrawColor(44, 93, 219);
    $pdf->SetLineWidth(0.6);
    $pdf->Line(20, 40, 190, 40);

    // Bloc "Facturé à"
    $pdf->Ln(8);
    $pdf->SetFont('Helvetica', 'B', 9);
    $pdf->SetTextColor(44, 93, 219);
    $pdf->Cell(0, 6, $tr('FACTURÉ À'), 0, 1);
    $pdf->SetFont('Helvetica', '', 10);
    $pdf->SetTextColor(15, 21, 37);
    $pdf->Cell(0, 5, $tr(trim(($f['civilite'] ?? '') . ' ' . ($f['prenom'] ?? '') . ' ' . ($f['nom'] ?? ''))), 0, 1);
    if (!empty($f['adresse'])) {
        $pdf->MultiCell(0, 5, $tr((string)$f['adresse']), 0, 'L');
    }
    if (!empty($f['paysResidence'])) {
        $pdf->Cell(0, 5, $tr((string)$f['paysResidence']), 0, 1);
    }
    if (!empty($f['email'])) {
        $pdf->Cell(0, 5, $tr((string)$f['email']), 0, 1);
    }
    $pdf->Ln(8);

    // Tableau facture
    $pdf->SetFillColor(44, 93, 219);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Helvetica', 'B', 9);
    $pdf->Cell(120, 9, '  ' . $tr('DESCRIPTION'), 0, 0, 'L', true);
    $pdf->Cell(25, 9, $tr('QUANTITÉ'), 0, 0, 'C', true);
    $pdf->Cell(25, 9, $tr('MONTANT'), 0, 1, 'R', true);

    $pdf->SetFont('Helvetica', '', 10);
    $pdf->SetTextColor(15, 21, 37);
    $programmeLabel = trim((string)($f['programme'] ?? ''));
    $anneeLabel     = trim((string)($f['annee'] ?? ''));
    $rentreeLabel   = trim((string)($f['rentree'] ?? ''));

    // Année académique : déduite de la rentrée si possible (ex : "Septembre 2026" → "2026-2027").
    $academicYear = '';
    if ($rentreeLabel !== '' && preg_match('/(\d{4})/', $rentreeLabel, $m)) {
        $startY = (int)$m[1];
        // Si la rentrée mentionne février/janvier, l'année académique a démarré l'année précédente.
        if (preg_match('/janv|f[ée]vr/i', $rentreeLabel)) {
            $startY -= 1;
        }
        $academicYear = $startY . '-' . ($startY + 1);
    }

    // Première ligne : "Frais de dossier IPEC — Programme XXX, 1ère année, 2026-2027"
    $firstLineParts = ['Frais de dossier IPEC'];
    if ($programmeLabel !== '') {
        $firstLineParts[] = 'Programme ' . $programmeLabel;
    }
    if ($anneeLabel !== '') {
        $firstLineParts[] = $anneeLabel;
    }
    if ($academicYear !== '') {
        $firstLineParts[] = $academicYear;
    }
    $firstLine = implode(', ', $firstLineParts);
    // remplace la première virgule par un tiret pour garder "Frais de dossier IPEC — Programme ..."
    $firstLine = preg_replace('/, /', ' — ', $firstLine, 1);

    $pdf->Ln(2);
    $startYRow = $pdf->GetY();
    $pdf->SetX(22);
    $pdf->SetFont('Helvetica', '', 10);
    $pdf->SetTextColor(15, 21, 37);
    $pdf->MultiCell(118, 6, $tr($firstLine), 0, 'L');
    $pdf->SetX(22);
    $pdf->SetFont('Helvetica', '', 9);
    $pdf->SetTextColor(91, 100, 120);
    $pdf->Cell(118, 5, $tr('Frais unique'), 0, 1, 'L');
    $endY = $pdf->GetY();
    $pdf->SetXY(140, $startYRow);
    $pdf->SetFont('Helvetica', '', 10);
    $pdf->SetTextColor(15, 21, 37);
    $pdf->Cell(25, 6, '1', 0, 0, 'C');
    $pdf->Cell(25, 6, number_format($montant, 2, ',', ' ') . ' EUR', 0, 1, 'R');
    $pdf->SetY($endY);

    $pdf->SetDrawColor(220, 226, 240);
    $pdf->SetLineWidth(0.2);
    $pdf->Line(20, $pdf->GetY() + 3, 190, $pdf->GetY() + 3);
    $pdf->Ln(8);

    // Total
    $pdf->SetFillColor(247, 249, 252);
    $pdf->SetFont('Helvetica', 'B', 11);
    $pdf->SetTextColor(15, 21, 37);
    $pdf->Cell(120, 10, '', 0, 0);
    $pdf->Cell(25, 10, $tr('TOTAL'), 0, 0, 'R', true);
    $pdf->SetTextColor(44, 93, 219);
    $pdf->Cell(25, 10, number_format($montant, 2, ',', ' ') . ' EUR', 0, 1, 'R', true);

    $pdf->SetFont('Helvetica', '', 8);
    $pdf->SetTextColor(91, 100, 120);
    $pdf->Cell(0, 4, $tr('TVA non applicable (art. 44 §2 4° CTVA, enseignement).'), 0, 1, 'R');
    $pdf->Ln(10);

    // Bloc paiement
    $startY = $pdf->GetY();
    $pdf->SetFillColor(247, 249, 252);
    $pdf->SetDrawColor(44, 93, 219);
    $pdf->SetLineWidth(0.3);
    $pdf->Rect(20, $startY, 170, 54, 'DF');
    $pdf->SetXY(24, $startY + 5);
    $pdf->SetFont('Helvetica', 'B', 9);
    $pdf->SetTextColor(44, 93, 219);
    $pdf->Cell(0, 5, $tr('MODALITÉS DE PAIEMENT'), 0, 1);
    $pdf->Ln(1);
    $pdf->SetX(24);
    $pdf->SetFont('Helvetica', '', 10);
    $pdf->SetTextColor(91, 100, 120);
    $pdf->Cell(50, 6, $tr('Bénéficiaire'), 0, 0);
    $pdf->SetTextColor(15, 21, 37);
    $pdf->Cell(0, 6, $tr('Institut privé des études commerciales'), 0, 1);
    $pdf->SetX(24);
    $pdf->SetTextColor(91, 100, 120);
    $pdf->Cell(50, 6, $tr('IBAN'), 0, 0);
    $pdf->SetFont('Helvetica', 'B', 10);
    $pdf->SetTextColor(15, 21, 37);
    $pdf->Cell(0, 6, $tr($iban), 0, 1);
    $pdf->SetX(24);
    $pdf->SetFont('Helvetica', '', 10);
    $pdf->SetTextColor(91, 100, 120);
    $pdf->Cell(50, 6, $tr('BIC'), 0, 0);
    $pdf->SetTextColor(15, 21, 37);
    $pdf->Cell(0, 6, $tr($bic), 0, 1);
    $pdf->SetX(24);
    $pdf->SetTextColor(91, 100, 120);
    $pdf->Cell(50, 6, $tr('Communication structurée'), 0, 0);
    $pdf->SetFont('Helvetica', 'B', 10);
    $pdf->SetTextColor(44, 93, 219);
    $pdf->Cell(0, 6, $tr($commStruct), 0, 1);

    $pdf->SetY($startY + 60);
    $pdf->SetFont('Helvetica', '', 9);
    $pdf->SetTextColor(91, 100, 120);
    $pdf->MultiCell(0, 5, $tr(
        'Vous pouvez procéder au virement à votre convenance. Pensez à reporter la communication structurée '
        . 'pour faciliter le rapprochement, puis joignez la preuve de paiement à votre dossier de candidature.'
    ), 0, 'L');

    return [$pdf->Output('S'), 'facture-frais-dossier-IPEC-' . $now->format('Ymd-His') . '.pdf', $numFacture];
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

/**
 * Archive une copie du message envoyé dans le dossier IMAP "Sent" de la
 * boîte d'authentification SMTP utilisée pour l'envoi.
 *
 * - Non bloquant : toute erreur est capturée et retournée sous forme de string.
 * - Renvoie [bool $archived, ?string $errorMessage].
 * - Nécessite l'extension PHP `imap`. Sans elle, retourne (false, "...").
 */
function archiveToImapSent(
    PHPMailer\PHPMailer\PHPMailer $sentMail,
    string $imapHost,
    int $imapPort,
    string $imapSentBox,
    string $imapUser,
    string $imapPass
): array {
    if (!function_exists('imap_open')) {
        return [false, "Extension PHP 'imap' non disponible — pas d'archivage Sent."];
    }
    try {
        $rawMessage = $sentMail->getSentMIMEMessage();
        $mailbox    = '{' . $imapHost . ':' . $imapPort . '/imap/ssl}' . $imapSentBox;
        $imap       = @imap_open($mailbox, $imapUser, $imapPass, OP_HALFOPEN);
        if ($imap === false) {
            $err = imap_last_error() ?: 'raison inconnue';
            @imap_errors(); @imap_alerts();
            return [false, 'imap_open a échoué : ' . $err];
        }
        $appended = @imap_append($imap, $mailbox, $rawMessage, '\\Seen');
        @imap_close($imap);
        if (!$appended) {
            $err = imap_last_error() ?: 'raison inconnue';
            @imap_errors(); @imap_alerts();
            return [false, 'imap_append a échoué : ' . $err];
        }
        @imap_errors(); @imap_alerts();
        return [true, null];
    } catch (\Throwable $imapErr) {
        error_log('[mailer.php] Archivage IMAP échoué : ' . $imapErr->getMessage());
        return [false, $imapErr->getMessage()];
    }
}

// ----- Archivage IMAP du mail interne (process@ → Sent) -----
// Pour conserver une trace du mail envoyé à admission@ / contact@ dans
// Roundcube, dossier "Sent" de la boîte process@.
$processImapHost    = $env['PROCESS_IMAP_HOST'] ?? $smtpHost;
$processImapPort    = (int)($env['PROCESS_IMAP_PORT'] ?? 993);
$processImapSentBox = $env['PROCESS_IMAP_SENT_FOLDER'] ?? 'Sent';
[$processImapArchived, $processImapError] = archiveToImapSent(
    $mail,
    $processImapHost,
    $processImapPort,
    $processImapSentBox,
    $smtpUser,
    $smtpPass
);

// ============================================================
// 2e e-mail : ACCUSÉ DE RÉCEPTION envoyé AU CANDIDAT
// (uniquement pour les candidatures, pas pour le formulaire de contact)
//
// Expéditeur SMTP    : admission@ipec.school (vraie auth, pas d'usurpation)
//                      Fallback : on réutilise les creds SMTP_* (process@)
//                      tant que ADMISSION_SMTP_* ne sont pas configurés.
// Reply-To           : admission@ipec.school
// Archivage IMAP     : copie du message dans le dossier "Sent" de la boîte
//                      admission@ → visible dans Roundcube comme un mail
//                      envoyé normal. Nécessite l'extension PHP `imap`.
//                      Non-bloquant : si ça échoue, le mail part quand même.
//
// Variables d'env attendues dans .ipec-mailer.env (toutes optionnelles) :
//   ADMISSION_SMTP_USER=admission@ipec.school
//   ADMISSION_SMTP_PASS=...
//   ADMISSION_IMAP_HOST=mail.ipec.school   (défaut : SMTP_HOST)
//   ADMISSION_IMAP_PORT=993                (défaut : 993)
//   ADMISSION_IMAP_SENT_FOLDER=Sent        (défaut : Sent)
// ============================================================
$candidateMailError = null;
$candidateImapError = null;
$candidateImapArchived = false;

if ($type === 'inscription') {
    // Creds dédiés admission@ avec fallback sur process@ pour ne rien casser
    $admissionUser = $env['ADMISSION_SMTP_USER'] ?? $smtpUser;
    $admissionPass = $env['ADMISSION_SMTP_PASS'] ?? $smtpPass;
    $imapHost      = $env['ADMISSION_IMAP_HOST'] ?? $smtpHost;
    $imapPort      = (int)($env['ADMISSION_IMAP_PORT'] ?? 993);
    $imapSentBox   = $env['ADMISSION_IMAP_SENT_FOLDER'] ?? 'Sent';

    try {
        // On fixe le Message-ID nous-mêmes pour pouvoir l'injecter dans le
        // mailto: du bouton CTA → ainsi le clic sur "Soumettez votre dossier
        // complet" est traité par le client mail comme une RÉPONSE au
        // message courant (et non comme un nouvel e-mail détaché du fil).
        $candidateMessageId = sprintf('<%s@ipec.school>', bin2hex(random_bytes(16)));

        $candidateHtml = buildCandidateConfirmationHtml([
            'prenom'         => $prenom,
            'nom'            => $nom,
            'civilite'       => $civilite,
            'date_naissance' => $dateNaissance,
            'nationalite'    => $nationalite,
            'email'          => $email,
            'telephone'      => $telephone,
            'adresse'        => $adresse,
            'pays_residence' => $paysResidence,
            'programme'      => $programme,
            'annee'          => $annee,
            'specialisation' => $specialisation,
            'rentree'        => $rentree,
        ], $candidateMessageId);

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

        // PHPMailer dédié au candidat — auth en tant qu'admission@
        $candidateMail = new PHPMailer\PHPMailer\PHPMailer(true);
        $candidateMail->isSMTP();
        $candidateMail->Host       = $smtpHost;
        $candidateMail->SMTPAuth   = true;
        $candidateMail->Username   = $admissionUser;
        $candidateMail->Password   = $admissionPass;
        $candidateMail->SMTPSecure = $smtpSecure === 'tls'
            ? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS
            : PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        $candidateMail->Port       = $smtpPort;
        $candidateMail->CharSet    = 'UTF-8';
        $candidateMail->Encoding   = 'base64';
        // Message-ID figé : doit être strictement identique à celui injecté
        // dans le mailto: du CTA pour que la réponse s'attache au fil.
        $candidateMail->MessageID  = $candidateMessageId;

        // From = même adresse que l'auth SMTP (pas d'usurpation, SPF/DKIM OK)
        $candidateMail->setFrom($admissionUser, 'IPEC — Service des admissions');
        $candidateMail->addAddress($email, "$prenom $nom");
        $candidateMail->addReplyTo($admissionUser, 'IPEC — Service des admissions');

        $candidateMail->isHTML(true);
        $candidateMail->Subject = "Votre demande d'admission à l'IPEC — procédure à suivre";
        $candidateMail->Body    = $candidateHtml;
        $candidateMail->AltBody = $candidateText;

        // Logo embarqué (CID identique au mail interne)
        if (is_file($logoPath)) {
            $candidateMail->addEmbeddedImage($logoPath, 'ipec-logo', 'ipec-logo.png', 'base64', 'image/png');
        }

        // PJ #1 : récapitulatif de candidature (le même PDF que celui envoyé
        // au service admission) — le candidat doit y vérifier ses informations.
        if ($pdfAttachment !== '' && $pdfFilename !== '') {
            $candidateMail->addStringAttachment($pdfAttachment, $pdfFilename, 'base64', 'application/pdf');
        }

        // PJ #2 : facture des frais de dossier (400 €).
        $factureError = null;
        try {
            [$facturePdf, $factureFilename, $factureNumero] = buildFacturePdf([
                'civilite'      => $civilite,
                'prenom'        => $prenom,
                'nom'           => $nom,
                'adresse'       => $adresse,
                'paysResidence' => $paysResidence,
                'email'         => $email,
                'programme'     => $programme,
                'annee'         => $annee,
                'rentree'       => $rentree,
            ]);
            if ($facturePdf !== '' && $factureFilename !== '') {
                $candidateMail->addStringAttachment($facturePdf, $factureFilename, 'base64', 'application/pdf');
            } else {
                $factureError = 'buildFacturePdf a renvoyé un résultat vide';
            }
        } catch (\Throwable $factErr) {
            $factureError = $factErr->getMessage() . ' @ ' . $factErr->getFile() . ':' . $factErr->getLine();
            error_log('[mailer.php] Échec génération facture PDF : ' . $factureError);
        }

        $candidateMail->send();


        // Archivage IMAP : copie dans le dossier "Sent" de admission@
        [$candidateImapArchived, $candidateImapError] = archiveToImapSent(
            $candidateMail,
            $imapHost,
            $imapPort,
            $imapSentBox,
            $admissionUser,
            $admissionPass
        );
    } catch (\Throwable $e) {
        // On NE bloque PAS la réponse : l'admission a déjà été notifiée.
        $candidateMailError = isset($candidateMail) ? ($candidateMail->ErrorInfo ?: $e->getMessage()) : $e->getMessage();
        error_log('[mailer.php] Échec envoi accusé candidat : ' . $candidateMailError);
    }
}

$response = ['ok' => true];
if ($DEBUG) {
    $response['debug'] = [
        'pdf_attached'             => $pdfAttachment !== '',
        'pdf_size_bytes'           => strlen($pdfAttachment),
        'pdf_filename'             => $pdfFilename,
        'pdf_error'                => $pdfError ?? null,
        'fpdf_loaded'              => class_exists('FPDF'),
        'iconv_loaded'             => function_exists('iconv'),
        'logo_exists'              => is_file(__DIR__ . '/ipec-logo-email.png'),
        'process_imap_archived'    => $processImapArchived ?? false,
        'process_imap_error'       => $processImapError ?? null,
        'candidate_mail_error'     => $candidateMailError ?? null,
        'candidate_imap_archived'  => $candidateImapArchived ?? false,
        'candidate_imap_error'     => $candidateImapError ?? null,
        'facture_error'            => $factureError ?? null,
        'facture_numero'           => $factureNumero ?? null,
        'imap_extension_loaded'    => function_exists('imap_open'),
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
 *
 *      # Archivage IMAP des mails envoyés par process@ (notifs internes)
 *      # → visibles dans Roundcube, dossier Sent de process@.
 *      PROCESS_IMAP_HOST=mail.ipec.school       # défaut : SMTP_HOST
 *      PROCESS_IMAP_PORT=993                    # défaut : 993
 *      PROCESS_IMAP_SENT_FOLDER=Sent            # défaut : Sent
 *
 *      # Compte admission@ — utilisé pour l'accusé de réception envoyé
 *      # au candidat (vraie auth, pas d'usurpation) et pour archiver
 *      # une copie dans le dossier "Sent" visible dans Roundcube.
 *      ADMISSION_SMTP_USER=admission@ipec.school
 *      ADMISSION_SMTP_PASS=...
 *      ADMISSION_IMAP_HOST=mail.ipec.school     # défaut : SMTP_HOST
 *      ADMISSION_IMAP_PORT=993                  # défaut : 993
 *      ADMISSION_IMAP_SENT_FOLDER=Sent          # défaut : Sent
 *
 *      → chmod 600 .ipec-mailer.env
 *
 * 3) Si l'ancien fichier inscription-mailer.php existe encore sur n0c,
 *    SUPPRIMEZ-LE (il a été remplacé par mailer.php).
 *
 * 4) Vérifier les DNS sur ipec.school : SPF, DKIM, DMARC
 *
 * 5) Vérifier que l'extension PHP `imap` est activée (php.ini → extension=imap)
 *    pour que les mails apparaissent dans Roundcube → dossier Sent.
 *    Sans elle, les mails partent quand même mais ne sont pas archivés.
 *    Pour diagnostiquer : appeler ?debug=1 et lire `imap_extension_loaded`,
 *    `process_imap_error` et `candidate_imap_error` dans la réponse JSON.
 * ========================================================================= */
