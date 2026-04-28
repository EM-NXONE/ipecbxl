<?php
/**
 * IPEC Admin — Contrôleur d'actions (download PDF, renvoi email, paiement, statut)
 */
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/_layout.php';
require_once __DIR__ . '/_etudiants.php';
admin_require_login();

$do = (string)($_REQUEST['do'] ?? '');
$id = (int)($_REQUEST['id'] ?? 0);

if ($id <= 0) { header('Location: index.php'); exit; }

$pdo = db();
$stmt = $pdo->prepare("SELECT * FROM candidatures WHERE id = ?");
$stmt->execute([$id]);
$c = $stmt->fetch();
if (!$c) { header('Location: index.php'); exit; }

// Adresse postale recomposée pour les builders
$adresse = trim(
    trim(($c['rue'] ?? '') . ' ' . ($c['numero'] ?? '')) .
    (($c['code_postal'] || $c['ville']) ? ', ' . trim(($c['code_postal'] ?? '') . ' ' . ($c['ville'] ?? '')) : '') .
    ($c['pays_residence'] ? ', ' . $c['pays_residence'] : '')
);

$candidatureFields = [
    'reference'      => $c['reference'],
    'civilite'       => $c['civilite'],
    'prenom'         => $c['prenom'],
    'nom'            => $c['nom'],
    'dateNaissance'  => $c['date_naissance'],
    'nationalite'    => $c['nationalite'],
    'email'          => $c['email'],
    'telephone'      => $c['telephone'],
    'adresse'        => $adresse,
    'rue'            => $c['rue'],
    'numero'         => $c['numero'],
    'codePostal'     => $c['code_postal'],
    'ville'          => $c['ville'],
    'paysResidence'  => $c['pays_residence'],
    'programme'      => $c['programme'],
    'annee'          => $c['annee'],
    'specialisation' => $c['specialisation'],
    'rentree'        => $c['rentree'],
    'message'        => $c['message'],
    'ip'             => $c['ip'],
];

$factureFields = [
    'reference'         => $c['reference'],
    'reference_facture' => $c['facture_numero'],
    'civilite'      => $c['civilite'],
    'prenom'        => $c['prenom'],
    'nom'           => $c['nom'],
    'adresse'       => $adresse,
    'rue'           => $c['rue'],
    'numero'        => $c['numero'],
    'codePostal'    => $c['code_postal'],
    'ville'         => $c['ville'],
    'paysResidence' => $c['pays_residence'],
    'email'         => $c['email'],
    'programme'     => $c['programme'],
    'annee'         => $c['annee'],
    'rentree'       => $c['rentree'],
];

function safe_filename(string $base, array $c, string $ext = 'pdf'): string {
    $name = preg_replace('/[^A-Za-z0-9_-]+/', '-', strtolower($c['prenom'] . '-' . $c['nom']));
    return $base . '-' . trim($name, '-') . '-' . $c['reference'] . '.' . $ext;
}

try {
    switch ($do) {
        case 'download_candidature': {
            $pdf = buildCandidaturePdf($candidatureFields);
            if ($pdf === '') throw new RuntimeException('Génération PDF candidature vide.');
            $filename = safe_filename('candidature', $c);
            admin_log_action($id, 'download_candidature', $filename);
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . strlen($pdf));
            echo $pdf; exit;
        }

        case 'download_facture': {
            [$pdf, $filename] = buildFacturePdf($factureFields);
            if ($pdf === '') throw new RuntimeException('Génération PDF facture vide.');
            admin_log_action($id, 'download_facture', $filename);
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . strlen($pdf));
            echo $pdf; exit;
        }

        case 'mark_paid': {
            admin_csrf_check();
            $pdo->prepare("UPDATE candidatures SET facture_payee=1, facture_payee_at=NOW(), facture_payee_par=? WHERE id=?")
                ->execute([admin_current_user(), $id]);
            admin_log_action($id, 'mark_paid', 'Facture ' . $c['facture_numero']);
            admin_set_flash('Facture marquée comme payée.');
            header('Location: detail.php?id=' . $id); exit;
        }

        case 'mark_unpaid': {
            admin_csrf_check();
            $pdo->prepare("UPDATE candidatures SET facture_payee=0, facture_payee_at=NULL, facture_payee_par=NULL WHERE id=?")
                ->execute([$id]);
            admin_log_action($id, 'mark_unpaid', 'Facture ' . $c['facture_numero']);
            admin_set_flash('Paiement annulé.');
            header('Location: detail.php?id=' . $id); exit;
        }

        case 'update_statut': {
            admin_csrf_check();
            $newStatut = (string)($_POST['statut'] ?? '');
            if (!isset(ADMIN_STATUTS[$newStatut])) throw new RuntimeException('Statut invalide.');
            $pdo->prepare("UPDATE candidatures SET statut=? WHERE id=?")->execute([$newStatut, $id]);
            admin_log_action($id, 'update_statut', $c['statut'] . ' → ' . $newStatut);
            admin_set_flash('Statut mis à jour : ' . ADMIN_STATUTS[$newStatut] . '.');
            header('Location: detail.php?id=' . $id); exit;
        }

        case 'resend_email': {
            admin_csrf_check();

            // Charge le .env (mêmes credentials que mailer.php)
            $envFile = __DIR__ . '/../../.ipec-mailer.env';
            if (!is_file($envFile)) throw new RuntimeException('Fichier .ipec-mailer.env introuvable.');
            $env = [];
            foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                if ($line[0] === '#') continue;
                if (strpos($line, '=') === false) continue;
                [$k, $v] = explode('=', $line, 2);
                $env[trim($k)] = trim($v);
            }

            $smtpHost      = $env['SMTP_HOST'] ?? '';
            $smtpPort      = (int)($env['SMTP_PORT'] ?? 465);
            $smtpSecure    = $env['SMTP_SECURE'] ?? 'ssl';
            $admissionUser = $env['ADMISSION_SMTP_USER'] ?? ($env['SMTP_USER'] ?? '');
            $admissionPass = $env['ADMISSION_SMTP_PASS'] ?? ($env['SMTP_PASS'] ?? '');

            // Génère les 2 PDF (mêmes données que l'envoi initial)
            $pdfCand = buildCandidaturePdf($candidatureFields);
            $candFilename = safe_filename('candidature', $c);
            [$pdfFact, $factFilename] = buildFacturePdf($factureFields);

            // Message-ID figé pour le CTA mailto: (cf. logique d'origine)
            $msgId = sprintf('<%s@ipec.school>', bin2hex(random_bytes(16)));
            $html = buildCandidateConfirmationHtml([
                'prenom'         => $c['prenom'],
                'nom'            => $c['nom'],
                'civilite'       => $c['civilite'],
                'date_naissance' => $c['date_naissance'],
                'nationalite'    => $c['nationalite'],
                'email'          => $c['email'],
                'telephone'      => $c['telephone'],
                'adresse'        => $adresse,
                'pays_residence' => $c['pays_residence'],
                'programme'      => $c['programme'],
                'annee'          => $c['annee'],
                'specialisation' => $c['specialisation'],
                'rentree'        => $c['rentree'],
            ], $msgId);

            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = $smtpHost;
            $mail->SMTPAuth   = true;
            $mail->Username   = $admissionUser;
            $mail->Password   = $admissionPass;
            $mail->SMTPSecure = $smtpSecure === 'tls'
                ? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS
                : PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port      = $smtpPort;
            $mail->CharSet   = 'UTF-8';
            $mail->Encoding  = 'base64';
            $mail->MessageID = $msgId;
            $mail->setFrom($admissionUser, 'IPEC — Service des admissions');
            $mail->addAddress($c['email'], $c['prenom'] . ' ' . $c['nom']);
            $mail->addReplyTo($admissionUser, 'IPEC — Service des admissions');
            $mail->isHTML(true);
            $mail->Subject = "Votre demande d'admission à l'IPEC — procédure à suivre (renvoi)";
            $mail->Body    = $html;

            $logoPath = __DIR__ . '/../ipec-logo-email.png';
            if (is_file($logoPath)) {
                $mail->addEmbeddedImage($logoPath, 'ipec-logo', 'ipec-logo.png', 'base64', 'image/png');
            }
            if ($pdfCand !== '') $mail->addStringAttachment($pdfCand, $candFilename, 'base64', 'application/pdf');
            if ($pdfFact !== '') $mail->addStringAttachment($pdfFact, $factFilename, 'base64', 'application/pdf');

            $mail->send();
            admin_log_action($id, 'resend_email', 'Renvoyé à ' . $c['email']);
            admin_set_flash('E-mail renvoyé à ' . $c['email'] . '.');
            header('Location: detail.php?id=' . $id); exit;
        }

        case 'create_etudiant': {
            admin_csrf_check();
            $res = etudiant_create_from_candidature($pdo, $c, admin_current_user());
            if ($res['deja_existant']) {
                admin_log_action($id, 'link_etudiant', 'Étudiant #' . $res['etudiant_id'] . ' (' . $res['numero'] . ')');
                admin_set_flash('Un compte étudiant existait déjà pour ' . $c['email'] . ' — candidature rattachée (' . $res['numero'] . ').');
            } else {
                admin_log_action($id, 'create_etudiant', '#' . $res['etudiant_id'] . ' ' . $res['numero']);
                // Le token d'activation est conservé en flash pour copie manuelle
                // (l'envoi e-mail automatique sera ajouté quand l'espace étudiant sera en ligne).
                $msg = 'Compte étudiant créé : ' . $res['numero']
                     . '. Lien d\'activation (à transmettre) : '
                     . '/etudiant/activer.php?token=' . $res['token'];
                admin_set_flash($msg);
            }
            header('Location: detail.php?id=' . $id); exit;
        }

        default:
            header('Location: detail.php?id=' . $id); exit;
    }
} catch (\Throwable $e) {
    error_log('[admin/action] ' . $do . ' #' . $id . ' : ' . $e->getMessage());
    admin_set_flash('Erreur : ' . $e->getMessage(), 'error');
    header('Location: detail.php?id=' . $id); exit;
}
