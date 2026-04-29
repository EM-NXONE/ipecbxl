<?php
/**
 * POST /api/candidature-action.php
 * Body: { id: int, action: string, ...params }
 *
 * Actions supportées (équivalent ancien action.php) :
 *   - mark_paid          (marque facture frais de dossier payée)
 *   - mark_unpaid        (annule)
 *   - change_statut      ({ statut })
 *   - resend_email       (renvoi e-mail candidat avec 2 PDF)
 *   - create_etudiant    (crée ou rattache + génère token activation)
 *   - sync_documents     (resync facture 400€ + récap dans espace étudiant)
 *   - reset_password_etudiant (réinitialise au mdp par défaut "Student1")
 */
require_once __DIR__ . '/_bootstrap.php';
api_method('POST');
api_require_admin();

$body   = api_body();
$id     = (int)($body['id'] ?? 0);
$action = (string)($body['action'] ?? '');
if ($id <= 0)        api_error('id invalide', 400);
if ($action === '')  api_error('action requise', 400);

$pdo = db();
$stmt = $pdo->prepare("SELECT * FROM candidatures WHERE id = ?");
$stmt->execute([$id]);
$c = $stmt->fetch();
if (!$c) api_error('Candidature introuvable', 404);

// Adresse postale recomposée pour les builders PDF
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

try {
    switch ($action) {

        case 'mark_paid': {
            $moyen = trim((string)($body['moyen_paiement'] ?? ''));
            $date  = trim((string)($body['date_paiement'] ?? ''));
            $allowedMoyens = ['virement', 'carte', 'especes', 'cheque', 'autre'];
            if ($moyen === '' || !in_array($moyen, $allowedMoyens, true)) {
                api_error('Moyen de paiement requis (virement, carte, especes, cheque, autre).', 400);
            }
            // Date au format YYYY-MM-DD ; on prend NOW() à défaut
            $payeAt = null;
            if ($date !== '') {
                $d = DateTime::createFromFormat('Y-m-d', $date);
                if (!$d) api_error('Date de paiement invalide (format YYYY-MM-DD).', 400);
                $payeAt = $d->format('Y-m-d') . ' 12:00:00';
            } else {
                $payeAt = date('Y-m-d H:i:s');
            }
            $pdo->prepare("UPDATE candidatures
                           SET facture_payee=1, facture_payee_at=?, facture_payee_par=?
                           WHERE id=?")
                ->execute([$payeAt, admin_current_user(), $id]);
            $pdo->prepare("UPDATE factures
                           SET statut_paiement='payee', paye_at=?, paye_par_admin=?, moyen_paiement=?
                           WHERE candidature_id=? AND type='frais_dossier'")
                ->execute([$payeAt, admin_current_user(), $moyen, $id]);
            admin_log_action($id, 'mark_paid', 'Facture ' . ($c['facture_numero'] ?? '') . ' — ' . $moyen . ' le ' . substr($payeAt, 0, 10));
            api_json(['ok' => true, 'message' => 'Facture marquée comme payée (' . $moyen . ' le ' . substr($payeAt, 0, 10) . ').']);
        }

        case 'mark_unpaid': {
            $pdo->prepare("UPDATE candidatures
                           SET facture_payee=0, facture_payee_at=NULL, facture_payee_par=NULL
                           WHERE id=?")->execute([$id]);
            $pdo->prepare("UPDATE factures
                           SET statut_paiement='en_attente', paye_at=NULL, paye_par_admin=NULL
                           WHERE candidature_id=? AND type='frais_dossier'")->execute([$id]);
            admin_log_action($id, 'mark_unpaid', 'Facture ' . ($c['facture_numero'] ?? ''));
            api_json(['ok' => true, 'message' => 'Paiement annulé.']);
        }

        case 'change_statut': {
            $newStatut = (string)($body['statut'] ?? '');
            if (!isset(ADMIN_STATUTS[$newStatut])) api_error('Statut invalide', 400);
            $pdo->prepare("UPDATE candidatures SET statut=? WHERE id=?")
                ->execute([$newStatut, $id]);
            admin_log_action($id, 'update_statut', $c['statut'] . ' → ' . $newStatut);
            api_json(['ok' => true, 'message' => 'Statut mis à jour : ' . ADMIN_STATUTS[$newStatut]]);
        }

        case 'create_etudiant': {
            $res = etudiant_create_from_candidature($pdo, $c, admin_current_user());
            $pwd = $res['default_password'];
            if ($res['deja_existant']) {
                admin_log_action($id, 'link_etudiant', 'Étudiant #' . $res['etudiant_id'] . ' (' . $res['numero'] . ')');
                api_json([
                    'ok' => true,
                    'message' => "Compte existant pour {$c['prenom']} {$c['nom']} — candidature rattachée ({$res['numero']}).",
                    'etudiant_id' => $res['etudiant_id'],
                    'numero' => $res['numero'],
                    'default_password' => $pwd,
                ]);
            }
            admin_log_action($id, 'create_etudiant', '#' . $res['etudiant_id'] . ' ' . $res['numero']);
            api_json([
                'ok' => true,
                'message' => "Compte étudiant créé : {$res['numero']}. Mot de passe par défaut : {$pwd}.",
                'etudiant_id' => $res['etudiant_id'],
                'numero' => $res['numero'],
                'default_password' => $pwd,
            ]);
        }

        case 'sync_documents': {
            if (empty($c['etudiant_id'])) api_error('Aucun compte étudiant rattaché.', 400);
            etudiant_sync_documents_historiques($pdo, (int)$c['etudiant_id'], $c, admin_current_user());
            admin_log_action($id, 'sync_documents', 'Étudiant #' . $c['etudiant_id']);
            api_json(['ok' => true, 'message' => 'Documents synchronisés (facture 400€ + récap candidature).']);
        }

        case 'reset_password_etudiant': {
            if (empty($c['etudiant_id'])) api_error('Aucun compte étudiant rattaché.', 400);
            $etuId = (int)$c['etudiant_id'];
            // Réinitialise au mot de passe par défaut "Student1" et invalide les sessions.
            $pdo->prepare("UPDATE etudiants SET password_hash=?, statut='actif' WHERE id=?")
                ->execute([password_hash(ETU_DEFAULT_PASSWORD, PASSWORD_BCRYPT), $etuId]);
            $pdo->prepare("DELETE FROM etudiant_sessions WHERE etudiant_id=?")->execute([$etuId]);
            admin_log_action($id, 'reset_password_etudiant', 'Étudiant #' . $etuId);
            api_json([
                'ok' => true,
                'message' => "Mot de passe réinitialisé à : " . ETU_DEFAULT_PASSWORD,
                'default_password' => ETU_DEFAULT_PASSWORD,
            ]);
        }

        case 'resend_email': {
            $envFile = __DIR__ . '/../../.ipec-mailer.env';
            if (!is_file($envFile)) api_error('Fichier .ipec-mailer.env introuvable.', 500);
            $env = [];
            foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                $line = trim($line);
                if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) continue;
                [$k, $v] = explode('=', $line, 2);
                $env[trim($k)] = trim($v);
            }
            $smtpHost   = $env['SMTP_HOST'] ?? '';
            $smtpPort   = (int)($env['SMTP_PORT'] ?? 465);
            $smtpSecure = $env['SMTP_SECURE'] ?? 'ssl';
            $admUser    = $env['ADMISSION_SMTP_USER'] ?? ($env['SMTP_USER'] ?? '');
            $admPass    = $env['ADMISSION_SMTP_PASS'] ?? ($env['SMTP_PASS'] ?? '');

            $pdfCand = buildCandidaturePdf($candidatureFields);
            $candFilename = 'candidature-' . preg_replace('/[^A-Za-z0-9_-]+/', '-', strtolower($c['prenom'].'-'.$c['nom'])) . '-' . $c['reference'] . '.pdf';
            [$pdfFact, $factFilename] = buildFacturePdf($factureFields);

            $msgId = sprintf('<%s@ipec.school>', bin2hex(random_bytes(16)));
            $html = buildCandidateConfirmationHtml([
                'prenom' => $c['prenom'], 'nom' => $c['nom'], 'civilite' => $c['civilite'],
                'date_naissance' => $c['date_naissance'], 'nationalite' => $c['nationalite'],
                'email' => $c['email'], 'telephone' => $c['telephone'], 'adresse' => $adresse,
                'pays_residence' => $c['pays_residence'],
                'programme' => $c['programme'], 'annee' => $c['annee'],
                'specialisation' => $c['specialisation'], 'rentree' => $c['rentree'],
            ], $msgId);

            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = $smtpHost;
            $mail->SMTPAuth   = true;
            $mail->Username   = $admUser;
            $mail->Password   = $admPass;
            $mail->SMTPSecure = $smtpSecure === 'tls'
                ? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS
                : PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port      = $smtpPort;
            $mail->CharSet   = 'UTF-8';
            $mail->Encoding  = 'base64';
            $mail->MessageID = $msgId;
            $mail->setFrom($admUser, 'IPEC — Service des admissions');
            $mail->addAddress($c['email'], $c['prenom'] . ' ' . $c['nom']);
            $mail->addReplyTo($admUser, 'IPEC — Service des admissions');
            $mail->isHTML(true);
            $mail->Subject = "Votre demande d'admission à l'IPEC — procédure à suivre (renvoi)";
            $mail->Body    = $html;

            $logoPath = __DIR__ . '/_shared/ipec-logo-email.png';
            if (is_file($logoPath)) {
                $mail->addEmbeddedImage($logoPath, 'ipec-logo', 'ipec-logo.png', 'base64', 'image/png');
            }
            if ($pdfCand !== '') $mail->addStringAttachment($pdfCand, $candFilename, 'base64', 'application/pdf');
            if ($pdfFact !== '') $mail->addStringAttachment($pdfFact, $factFilename, 'base64', 'application/pdf');

            $mail->send();
            admin_log_action($id, 'resend_email', 'Renvoyé à ' . $c['email']);
            api_json(['ok' => true, 'message' => 'E-mail renvoyé à ' . $c['email'] . '.']);
        }

        default:
            api_error('Action inconnue : ' . $action, 400);
    }
} catch (\Throwable $e) {
    error_log('[admin-api/action] ' . $action . ' #' . $id . ' : ' . $e->getMessage());
    api_error('Erreur : ' . $e->getMessage(), 500);
}
