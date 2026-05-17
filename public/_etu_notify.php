<?php
/**
 * IPEC — Notifications e-mail aux étudiants (LMS).
 *
 * Deux notifications transactionnelles :
 *   - etu_notify_send_welcome(...)   : compte étudiant créé (mdp par défaut)
 *   - etu_notify_send_documents(...) : nouveaux documents/factures disponibles
 *
 * Style aligné sur l'e-mail de confirmation de candidature
 * (public/templates/admission_candidat.html) — bandeau bleu, logo IPEC en CID,
 * pied de page mention légale. Templates inline (pas de fichier HTML séparé).
 *
 * Non bloquant : tout échec est capturé et loggé via error_log().
 *
 * Localisation des dépendances :
 *   - PHPMailer : __DIR__ . '/PHPMailer/src/'
 *   - Logo CID  : __DIR__ . '/ipec-logo-email.png'
 *   - .env SMTP : __DIR__ . '/../.ipec-mailer.env'
 *
 * Ces chemins sont valides aussi bien dans le repo (public/_etu_notify.php)
 * qu'après packaging dans _shared/ des portails admin/lms (cf. scripts/package-portails.sh).
 */

declare(strict_types=1);

if (!defined('IPEC_ETU_NOTIFY_LOADED')) {
    define('IPEC_ETU_NOTIFY_LOADED', true);

    /** Charge les credentials SMTP depuis .ipec-mailer.env. */
    function etu_notify_load_smtp(): ?array {
        $envPath = __DIR__ . '/../.ipec-mailer.env';
        if (!is_file($envPath)) return null;
        $env = [];
        foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
            [$k, $v] = explode('=', $line, 2);
            $env[trim($k)] = trim($v);
        }
        $host = $env['SMTP_HOST'] ?? '';
        $user = $env['SMTP_USER'] ?? '';
        $pass = $env['SMTP_PASS'] ?? '';
        if ($host === '' || $user === '' || $pass === '') return null;
        // Credentials admission@ (fallback sur SMTP_* si non définis).
        // Permet d'envoyer From: admission@ avec auth SMTP cohérente (SPF/DKIM OK)
        // et d'archiver dans le dossier Sent de admission@ via IMAP.
        $admissionUser = $env['ADMISSION_SMTP_USER'] ?? $user;
        $admissionPass = $env['ADMISSION_SMTP_PASS'] ?? $pass;
        return [
            'host'           => $host,
            'port'           => (int)($env['SMTP_PORT'] ?? 465),
            'secure'         => strtolower($env['SMTP_SECURE'] ?? 'ssl'),
            'user'           => $user,
            'pass'           => $pass,
            'admission_user' => $admissionUser,
            'admission_pass' => $admissionPass,
            'imap_host'      => $env['ADMISSION_IMAP_HOST'] ?? $host,
            'imap_port'      => (int)($env['ADMISSION_IMAP_PORT'] ?? 993),
            'imap_sent_box'  => $env['ADMISSION_IMAP_SENT_FOLDER'] ?? 'Sent',
        ];
    }

    /** Archive le mail envoyé dans le dossier Sent IMAP de admission@. Non bloquant. */
    function etu_notify_archive_imap_sent(PHPMailer\PHPMailer\PHPMailer $sentMail, array $smtp): void {
        if (!function_exists('imap_open')) {
            error_log('[etu_notify] Extension PHP imap non disponible — pas d\'archivage Sent.');
            return;
        }
        try {
            $rawMessage = $sentMail->getSentMIMEMessage();
            $mailbox    = '{' . $smtp['imap_host'] . ':' . $smtp['imap_port'] . '/imap/ssl}' . $smtp['imap_sent_box'];
            $imap       = @imap_open($mailbox, $smtp['admission_user'], $smtp['admission_pass'], OP_HALFOPEN);
            if ($imap === false) {
                error_log('[etu_notify] imap_open échoué : ' . (imap_last_error() ?: 'raison inconnue'));
                @imap_errors(); @imap_alerts();
                return;
            }
            $appended = @imap_append($imap, $mailbox, $rawMessage, '\\Seen');
            @imap_close($imap);
            if (!$appended) {
                error_log('[etu_notify] imap_append échoué : ' . (imap_last_error() ?: 'raison inconnue'));
            }
            @imap_errors(); @imap_alerts();
        } catch (\Throwable $e) {
            error_log('[etu_notify] Archivage IMAP échoué : ' . $e->getMessage());
        }
    }

    /** Charge PHPMailer si pas déjà chargé. */
    function etu_notify_require_phpmailer(): bool {
        if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) return true;
        $base = __DIR__ . '/PHPMailer/src/';
        if (!is_file($base . 'PHPMailer.php')) return false;
        require_once $base . 'Exception.php';
        require_once $base . 'PHPMailer.php';
        require_once $base . 'SMTP.php';
        return true;
    }

    /** Récupère l'étudiant (id, email, prenom, nom, civilite, numero_etudiant) — null si KO. */
    function etu_notify_load_etudiant(PDO $pdo, int $etudiantId): ?array {
        if ($etudiantId <= 0) return null;
        $st = $pdo->prepare("SELECT id, email, prenom, nom, civilite, numero_etudiant
                             FROM etudiants WHERE id = ? LIMIT 1");
        $st->execute([$etudiantId]);
        $row = $st->fetch();
        return $row ?: null;
    }

    /** Échappe pour HTML. */
    function etu_h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

    /** Salutation adaptée à la civilité. */
    function etu_notify_salutation(string $civilite, string $nom): string {
        $c = mb_strtolower(trim($civilite), 'UTF-8');
        if ($c === 'mr' || $c === 'm.' || $c === 'm') return 'Cher M. ' . etu_h($nom);
        if (str_starts_with($c, 'mme') || str_starts_with($c, 'mlle')) return 'Chère Mme ' . etu_h($nom);
        return 'Cher·e ' . etu_h($nom);
    }

    /**
     * Construit le HTML d'un e-mail étudiant avec le branding IPEC
     * (bandeau bleu + logo CID + bloc contenu + pied de page).
     *
     * @param string $title       Titre principal (H1) — ex: "Bienvenue sur votre espace IPEC"
     * @param string $bodyInner   HTML interne (paragraphes, listes…)
     * @param string $ctaLabel    Libellé du bouton (vide → pas de bouton)
     * @param string $ctaHref     URL du bouton
     */
    function etu_notify_render_html(string $title, string $bodyInner, string $ctaLabel = '', string $ctaHref = ''): string {
        $titleH = etu_h($title);
        $cta = '';
        if ($ctaLabel !== '' && $ctaHref !== '') {
            $cta = '<tr><td align="center" style="background:transparent;font-size:0px;padding:10px 25px;word-break:break-word;">'
                 . '<table border="0" cellpadding="0" cellspacing="0" role="presentation" style="border-collapse:separate;line-height:100%;"><tbody><tr>'
                 . '<td align="center" bgcolor="#2371cd" role="presentation" style="border:none;border-radius:5px;cursor:auto;mso-padding-alt:12px 28px;background:#2371cd;" valign="middle">'
                 . '<a href="' . etu_h($ctaHref) . '" style="display:inline-block;background:#2371cd;color:#ffffff;font-family:Verdana,Helvetica,Arial,sans-serif;font-size:14px;font-weight:bold;line-height:120%;margin:0;text-decoration:none;text-transform:none;padding:12px 28px;border-radius:5px;">'
                 . etu_h($ctaLabel) . '</a></td></tr></tbody></table></td></tr>';
        }
        return <<<HTML
<html lang="fr" dir="ltr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>{$titleH}</title></head>
<body style="margin:0;padding:0;background-color:#c3e0f5;word-spacing:normal;">
<div style="background-color:#c3e0f5;" role="main" lang="fr" dir="ltr">

<!-- Bandeau bleu top -->
<div style="background:#FFFFFF;margin:0px auto;max-width:600px;"><table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="background:#FFFFFF;width:100%;"><tbody><tr><td style="padding:0;text-align:center;"><table border="0" cellpadding="0" cellspacing="0" role="presentation" style="width:100%"><tbody><tr><td align="center" style="font-size:0;padding:10px 25px;"><p style="border-top:solid 10px #2371cd;font-size:1px;margin:0px auto;width:100%;"></p></td></tr></tbody></table></td></tr></tbody></table></div>

<!-- Logo -->
<div style="background:#FFFFFF;margin:0px auto;max-width:600px;"><table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="background:#FFFFFF;width:100%;"><tbody><tr><td style="padding:20px 0 0 0;text-align:center;"><table border="0" cellpadding="0" cellspacing="0" role="presentation" style="border-collapse:collapse;border-spacing:0px;margin:0 auto;"><tbody><tr><td style="width:180px;"><img alt="Logo IPEC" src="cid:ipec-logo" width="180" style="border:none;display:block;outline:none;text-decoration:none;height:auto;width:180px;"></td></tr></tbody></table></td></tr></tbody></table></div>

<!-- Contenu principal -->
<div style="background:#FFFFFF;margin:0px auto;max-width:600px;"><table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="background:#FFFFFF;width:100%;"><tbody><tr><td style="padding:20px 0 0 0;"><table border="0" cellpadding="0" cellspacing="0" role="presentation" style="width:100%"><tbody>

<tr><td align="left" style="padding:10px 25px 0 25px;"><h1 style="text-align:center;margin:10px 0;font-weight:normal;"><span style="color:#2c5ddb;font-family:Arial;font-size:30px;line-height:36px;"><b>{$titleH}</b></span></h1></td></tr>

<tr><td align="left" style="padding:0 25px 10px 25px;"><div style="font-family:Arial,Helvetica,sans-serif;color:#787878;font-size:14px;line-height:22px;">{$bodyInner}</div></td></tr>

{$cta}

<tr><td align="center" style="padding:15px 25px 10px 25px;"><div style="font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#787878;text-align:center;line-height:20px;"><i>Notre équipe reste à votre disposition du lundi au vendredi de 9h00 à 12h30 et de 13h30 à 17h00.</i></div></td></tr>

</tbody></table></td></tr></tbody></table></div>

<!-- Séparateur -->
<div style="background:#FFFFFF;margin:0px auto;max-width:600px;"><table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="background:#FFFFFF;width:100%;"><tbody><tr><td style="padding:20px 0 0 0;"><table border="0" cellpadding="0" cellspacing="0" role="presentation" style="width:100%"><tbody><tr><td style="padding:10px 25px;"><p style="border-top:dotted 1px #c2c2c2;font-size:1px;margin:0 auto;width:100%;"></p></td></tr></tbody></table></td></tr></tbody></table></div>

<!-- Mention légale -->
<div style="background:#FFFFFF;margin:0px auto;max-width:600px;"><table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="background:#FFFFFF;width:100%;"><tbody><tr><td style="padding:0 0 20px 0;"><table border="0" cellpadding="0" cellspacing="0" role="presentation" style="width:100%"><tbody><tr><td align="center" style="padding:10px 25px;"><div style="font-family:Arial,Helvetica,sans-serif;font-size:11px;color:#a8a8a8;line-height:16px;text-align:center;">IPEC — Institut Privé des Études Commerciales · <a href="https://www.ipec.school" style="color:#2371cd;text-decoration:none;">www.ipec.school</a><br>Chaussée d'Alsemberg 897, 1180 Uccle, Belgique<br>Cet e-mail est généré automatiquement, merci de ne pas y répondre.</div></td></tr></tbody></table></td></tr></tbody></table></div>

</div></body></html>
HTML;
    }

    /** Envoi PHPMailer (helper interne). Renvoie true/false. */
    function etu_notify_send_mail(string $toEmail, string $toName, string $subject, string $html, string $altText): bool {
        $smtp = etu_notify_load_smtp();
        if (!$smtp) { error_log('[etu_notify] SMTP non configuré, mail ignoré'); return false; }
        if (!etu_notify_require_phpmailer()) { error_log('[etu_notify] PHPMailer introuvable'); return false; }

        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = $smtp['host'];
            $mail->Port       = $smtp['port'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $smtp['user'];
            $mail->Password   = $smtp['pass'];
            $mail->SMTPSecure = $smtp['secure'] === 'tls'
                ? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS
                : PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            $mail->CharSet  = 'UTF-8';
            $mail->Encoding = 'base64';

            // From = boîte SMTP authentifiée (process@ipec.school) — reply-To noreply
            $mail->setFrom($smtp['user'], 'IPEC — Espace étudiant');
            $mail->addAddress($toEmail, $toName);
            $mail->addReplyTo('admission@ipec.school', 'IPEC — Service des admissions');

            $logoPath = __DIR__ . '/ipec-logo-email.png';
            if (is_file($logoPath)) {
                $mail->addEmbeddedImage($logoPath, 'ipec-logo', 'ipec-logo.png', 'base64', 'image/png');
            }

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $html;
            $mail->AltBody = $altText;

            $mail->send();
            return true;
        } catch (\Throwable $e) {
            error_log('[etu_notify] envoi échoué : ' . (isset($mail) ? ($mail->ErrorInfo ?: $e->getMessage()) : $e->getMessage()));
            return false;
        }
    }

    /** URL du portail étudiant. */
    function etu_notify_lms_url(): string { return 'https://lms.ipec.school/'; }

    /**
     * Mail de bienvenue : compte créé avec mot de passe par défaut.
     * Idempotent au sens où l'appelant ne déclenche cette fonction qu'à la
     * création initiale (pas à chaque rattachement).
     */
    function etu_notify_send_welcome(PDO $pdo, int $etudiantId, string $defaultPassword, ?string $firstLoginUrl = null): bool {
        $etu = etu_notify_load_etudiant($pdo, $etudiantId);
        if (!$etu || !filter_var($etu['email'], FILTER_VALIDATE_EMAIL)) return false;

        $salut    = etu_notify_salutation((string)$etu['civilite'], (string)$etu['nom']);
        $emailH   = etu_h((string)$etu['email']);
        $numeroH  = etu_h((string)$etu['numero_etudiant']);
        $pwdH     = etu_h($defaultPassword);
        $lmsUrl   = etu_notify_lms_url();

        $firstLoginBlock = '';
        $altFirstLogin   = '';
        if ($firstLoginUrl) {
            $urlH = etu_h($firstLoginUrl);
            $firstLoginBlock =
                  "<p style=\"margin:18px 0 8px 0;\"><b>Première connexion :</b> cliquez sur le bouton ci-dessous pour "
                . "<b>définir votre propre mot de passe</b> et accéder directement à votre espace. "
                . "Ce lien est valable <b>7 jours</b> et à usage unique.</p>"
                . "<p style=\"font-size:12px;color:#a8a8a8;margin:6px 0 0 0;\">Si le bouton ne fonctionne pas, copiez ce lien dans votre navigateur :<br>"
                . "<a href=\"{$urlH}\" style=\"color:#2371cd;word-break:break-all;\">{$urlH}</a></p>";
            $altFirstLogin = "Première connexion (définir votre mot de passe, valable 7 jours) : {$firstLoginUrl}\n";
        }

        $body = "<p>{$salut},</p>"
              . "<p>Votre <b>espace étudiant IPEC</b> vient d'être créé. "
              . "Vous pouvez désormais y accéder pour suivre l'état de votre dossier, télécharger vos documents officiels et consulter vos factures.</p>"
              . "<div style=\"background:#f4f8fd;border-left:4px solid #2371cd;padding:14px 18px;margin:14px 0;border-radius:0 4px 4px 0;\">"
              . "<div style=\"margin:0 0 6px 0;\"><b>Identifiant (e-mail)</b> : {$emailH}</div>"
              . "<div style=\"margin:0 0 6px 0;\"><b>Numéro étudiant</b> : {$numeroH}</div>"
              . "<div style=\"margin:0;\"><b>Mot de passe provisoire</b> : <code style=\"background:#fff;padding:2px 6px;border-radius:3px;border:1px solid #d8e1ee;color:#2c5ddb;\">{$pwdH}</code></div>"
              . "</div>"
              . $firstLoginBlock
              . "<p>Pour des raisons de sécurité, <b>changez ce mot de passe dès votre première connexion</b>"
              . ($firstLoginUrl ? " (le bouton ci-dessous vous mène directement à l'écran de création d'un mot de passe personnel)" : " depuis la rubrique <i>Profil</i>")
              . ".</p>"
              . "<p>Vous trouverez dans votre espace : votre récapitulatif de candidature, votre facture des frais de dossier, ainsi que tout document officiel ajouté ultérieurement par notre équipe (lettre de préadmission, attestation d'inscription, attestations de réussite, etc.).</p>";

        $alt = "Bonjour,\n\nVotre espace étudiant IPEC a été créé.\n"
             . "Identifiant : {$etu['email']}\n"
             . "Numéro étudiant : {$etu['numero_etudiant']}\n"
             . "Mot de passe provisoire : {$defaultPassword}\n\n"
             . $altFirstLogin
             . "Connexion : {$lmsUrl}\n\n"
             . "Pensez à modifier votre mot de passe lors de votre première connexion.\n\n"
             . "— L'équipe IPEC\n";

        $ctaLabel = $firstLoginUrl ? "Définir mon mot de passe et me connecter" : "Accéder à mon espace étudiant";
        $ctaHref  = $firstLoginUrl ?: $lmsUrl;

        $html = etu_notify_render_html(
            "Bienvenue sur votre espace IPEC",
            $body,
            $ctaLabel,
            $ctaHref
        );

        return etu_notify_send_mail(
            (string)$etu['email'],
            trim($etu['prenom'] . ' ' . $etu['nom']),
            "Votre espace étudiant IPEC est prêt",
            $html,
            $alt
        );
    }

    /**
     * Notification : nouveaux documents disponibles dans l'espace étudiant.
     *
     * @param array $items Liste d'items, chacun ['titre' => '...', 'kind' => 'document'|'facture']
     */
    function etu_notify_send_documents(PDO $pdo, int $etudiantId, array $items): bool {
        $items = array_values(array_filter($items, fn($it) => is_array($it) && !empty($it['titre'])));
        if (empty($items)) return false;

        $etu = etu_notify_load_etudiant($pdo, $etudiantId);
        if (!$etu || !filter_var($etu['email'], FILTER_VALIDATE_EMAIL)) return false;

        $salut  = etu_notify_salutation((string)$etu['civilite'], (string)$etu['nom']);
        $lmsUrl = etu_notify_lms_url();

        $hasDocs    = false; $hasFactures = false;
        $listHtml   = '';
        $listText   = '';
        foreach ($items as $it) {
            $kind  = ($it['kind'] ?? 'document') === 'facture' ? 'facture' : 'document';
            if ($kind === 'facture') $hasFactures = true; else $hasDocs = true;
            $icon   = $kind === 'facture' ? '💳' : '📄';
            $titleH = etu_h((string)$it['titre']);
            $listHtml .= "<li style=\"margin:6px 0;\">{$icon}&nbsp;&nbsp;{$titleH}</li>";
            $listText .= " - " . $it['titre'] . "\n";
        }

        // Sujet & intro adaptés au type
        if ($hasDocs && $hasFactures) {
            $subject = "Nouveaux documents et factures disponibles";
            $intro   = "De nouveaux <b>documents officiels</b> et <b>factures</b> viennent d'être ajoutés à votre espace étudiant IPEC.";
            $cta     = "Consulter mon espace";
        } elseif ($hasFactures) {
            $subject = count($items) > 1 ? "Nouvelles factures disponibles" : "Nouvelle facture disponible";
            $intro   = "De nouvelles <b>factures</b> viennent d'être ajoutées à votre espace étudiant IPEC.";
            $cta     = "Consulter mes factures";
        } else {
            $subject = count($items) > 1 ? "Nouveaux documents disponibles" : "Nouveau document disponible";
            $intro   = "De nouveaux <b>documents officiels</b> viennent d'être ajoutés à votre espace étudiant IPEC.";
            $cta     = "Consulter mes documents";
        }

        $body = "<p>{$salut},</p>"
              . "<p>{$intro}</p>"
              . "<ul style=\"margin:14px 0;padding-left:20px;color:#2c5ddb;font-weight:600;\">{$listHtml}</ul>"
              . "<p>Connectez-vous à votre espace pour les consulter et les télécharger au format PDF. "
              . "Pour rappel, chaque document est nominatif et signé par la direction de l'IPEC, et son authenticité peut être vérifiée à tout moment sur "
              . "<a href=\"https://www.ipec.school/verification\" style=\"color:#2371cd;\">ipec.school/verification</a>.</p>";

        $alt = "Bonjour,\n\n{$intro}\n\n{$listText}\nAccédez à votre espace : {$lmsUrl}\n\n— L'équipe IPEC\n";

        $html = etu_notify_render_html(
            $subject,
            $body,
            $cta,
            $lmsUrl
        );

        return etu_notify_send_mail(
            (string)$etu['email'],
            trim($etu['prenom'] . ' ' . $etu['nom']),
            $subject,
            $html,
            $alt
        );
    }
}
