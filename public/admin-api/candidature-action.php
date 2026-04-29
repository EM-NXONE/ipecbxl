<?php
/**
 * POST /api/candidature-action.php { id, do, ...payload }
 *  do ∈ mark_paid | mark_unpaid | update_statut(statut) | resend_email
 *      | create_etudiant | sync_documents | regen_activation
 *
 * Réutilise la logique de public/admin/action.php mais en JSON.
 */
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../admin/_etudiants.php';
api_method('POST');
$adminUser = api_require_admin();

$body = api_body();
$id = (int)($body['id'] ?? 0);
$do = (string)($body['do'] ?? '');
if ($id <= 0) api_error('id invalide', 400);

$pdo = db();
$stmt = $pdo->prepare("SELECT * FROM candidatures WHERE id = ?");
$stmt->execute([$id]);
$c = $stmt->fetch();
if (!$c) api_error('Candidature introuvable', 404);

$adresse = trim(
    trim(($c['rue'] ?? '') . ' ' . ($c['numero'] ?? '')) .
    (($c['code_postal'] || $c['ville']) ? ', ' . trim(($c['code_postal'] ?? '') . ' ' . ($c['ville'] ?? '')) : '') .
    ($c['pays_residence'] ? ', ' . $c['pays_residence'] : '')
);

try {
    switch ($do) {
        case 'mark_paid':
            $pdo->prepare("UPDATE candidatures SET facture_payee=1, facture_payee_at=NOW(), facture_payee_par=? WHERE id=?")
                ->execute([$adminUser, $id]);
            $pdo->prepare("UPDATE factures SET statut_paiement='payee', paye_at=NOW(), paye_par_admin=?
                           WHERE candidature_id=? AND type='frais_dossier'")
                ->execute([$adminUser, $id]);
            admin_log_action($id, 'mark_paid', 'Facture ' . $c['facture_numero']);
            api_json(['ok' => true, 'message' => 'Facture marquée comme payée.']);
            break;

        case 'mark_unpaid':
            $pdo->prepare("UPDATE candidatures SET facture_payee=0, facture_payee_at=NULL, facture_payee_par=NULL WHERE id=?")
                ->execute([$id]);
            $pdo->prepare("UPDATE factures SET statut_paiement='en_attente', paye_at=NULL, paye_par_admin=NULL
                           WHERE candidature_id=? AND type='frais_dossier'")
                ->execute([$id]);
            admin_log_action($id, 'mark_unpaid', 'Facture ' . $c['facture_numero']);
            api_json(['ok' => true, 'message' => 'Paiement annulé.']);
            break;

        case 'update_statut':
            $newStatut = (string)($body['statut'] ?? '');
            if (!isset(ADMIN_STATUTS[$newStatut])) api_error('Statut invalide.', 400);
            $pdo->prepare("UPDATE candidatures SET statut=? WHERE id=?")->execute([$newStatut, $id]);
            admin_log_action($id, 'update_statut', $c['statut'] . ' → ' . $newStatut);
            api_json(['ok' => true, 'message' => 'Statut mis à jour : ' . ADMIN_STATUTS[$newStatut] . '.']);
            break;

        case 'resend_email':
            // Logique d'envoi mail (réutilise PHPMailer + builders chargés via _bootstrap → mailer.php)
            $envFile = __DIR__ . '/../../.ipec-mailer.env';
            if (!is_file($envFile)) api_error('Fichier .ipec-mailer.env introuvable.', 500);
            $env = [];
            foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                if ($line[0] === '#') continue;
                if (strpos($line, '=') === false) continue;
                [$k, $v] = explode('=', $line, 2);
                $env[trim($k)] = trim($v);
            }
            $smtpHost   = $env['SMTP_HOST'] ?? '';
            $smtpPort   = (int)($env['SMTP_PORT'] ?? 465);
            $smtpSecure = $env['SMTP_SECURE'] ?? 'ssl';
            $admUser    = $env['ADMISSION_SMTP_USER'] ?? ($env['SMTP_USER'] ?? '');
            $admPass    = $env['ADMISSION_SMTP_PASS'] ?? ($env['SMTP_PASS'] ?? '');

            $candFields = [
                'reference' => $c['reference'], 'civilite' => $c['civilite'],
                'prenom' => $c['prenom'], 'nom' => $c['nom'],
                'dateNaissance' => $c['date_naissance'], 'nationalite' => $c['nationalite'],
                'email' => $c['email'], 'telephone' => $c['telephone'],
                'adresse' => $adresse,
                'rue' => $c['rue'], 'numero' => $c['numero'],
                'codePostal' => $c['code_postal'], 'ville' => $c['ville'],
                'paysResidence' => $c['pays_residence'],
                'programme' => $c['programme'], 'annee' => $c['annee'],
                'specialisation' => $c['specialisation'], 'rentree' => $c['rentree'],
                'message' => $c['message'], 'ip' => $c['ip'],
            ];
            $factFields = [
                'reference' => $c['reference'], 'reference_facture' => $c['facture_numero'],
                'civilite' => $c['civilite'], 'prenom' => $c['prenom'], 'nom' => $c['nom'],
                'adresse' => $adresse, 'rue' => $c['rue'], 'numero' => $c['numero'],
                'codePostal' => $c['code_postal'], 'ville' => $c['ville'],
                'paysResidence' => $c['pays_residence'], 'email' => $c['email'],
                'programme' => $c['programme'], 'annee' => $c['annee'], 'rentree' => $c['rentree'],
            ];

            $pdfCand = buildCandidaturePdf($candFields);
            $candFilename = 'candidature-' . preg_replace('/[^A-Za-z0-9_-]+/', '-', strtolower($c['prenom'] . '-' . $c['nom'])) . '-' . $c['reference'] . '.pdf';
            [$pdfFact, $factFilename] = buildFacturePdf($factFields);

            $msgId = sprintf('<%s@ipec.school>', bin2hex(random_bytes(16)));
            $html = buildCandidateConfirmationHtml([
                'prenom' => $c['prenom'], 'nom' => $c['nom'], 'civilite' => $c['civilite'],
                'date_naissance' => $c['date_naissance'], 'nationalite' => $c['nationalite'],
                'email' => $c['email'], 'telephone' => $c['telephone'],
                'adresse' => $adresse, 'pays_residence' => $c['pays_residence'],
                'programme' => $c['programme'], 'annee' => $c['annee'],
                'specialisation' => $c['specialisation'], 'rentree' => $c['rentree'],
            ], $msgId);

            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $smtpHost;
            $mail->SMTPAuth = true;
            $mail->Username = $admUser;
            $mail->Password = $admPass;
            $mail->SMTPSecure = $smtpSecure === 'tls'
                ? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS
                : PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = $smtpPort;
            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'base64';
            $mail->MessageID = $msgId;
            $mail->setFrom($admUser, 'IPEC — Service des admissions');
            $mail->addAddress($c['email'], $c['prenom'] . ' ' . $c['nom']);
            $mail->addReplyTo($admUser, 'IPEC — Service des admissions');
            $mail->isHTML(true);
            $mail->Subject = "Votre demande d'admission à l'IPEC — procédure à suivre (renvoi)";
            $mail->Body = $html;

            $logoPath = __DIR__ . '/../ipec-logo-email.png';
            if (is_file($logoPath)) {
                $mail->addEmbeddedImage($logoPath, 'ipec-logo', 'ipec-logo.png', 'base64', 'image/png');
            }
            if ($pdfCand !== '') $mail->addStringAttachment($pdfCand, $candFilename, 'base64', 'application/pdf');
            if ($pdfFact !== '') $mail->addStringAttachment($pdfFact, $factFilename, 'base64', 'application/pdf');

            $mail->send();
            admin_log_action($id, 'resend_email', 'Renvoyé à ' . $c['email']);
            api_json(['ok' => true, 'message' => 'E-mail renvoyé à ' . $c['email'] . '.']);
            break;

        case 'create_etudiant':
            $res = etudiant_create_from_candidature($pdo, $c, $adminUser);
            if ($res['deja_existant']) {
                admin_log_action($id, 'link_etudiant', 'Étudiant #' . $res['etudiant_id'] . ' (' . $res['numero'] . ')');
                api_json([
                    'ok' => true,
                    'message' => 'Compte étudiant existant rattaché (' . $res['numero'] . ').',
                    'etudiant_id' => $res['etudiant_id'],
                    'numero' => $res['numero'],
                    'deja_existant' => true,
                ]);
            } else {
                admin_log_action($id, 'create_etudiant', '#' . $res['etudiant_id'] . ' ' . $res['numero']);
                api_json([
                    'ok' => true,
                    'message' => 'Compte étudiant créé : ' . $res['numero'],
                    'etudiant_id' => $res['etudiant_id'],
                    'numero' => $res['numero'],
                    'activation_url' => 'https://lms.ipec.school/etudiant/activer/' . $res['token'],
                    'deja_existant' => false,
                ]);
            }
            break;

        case 'sync_documents':
            if (empty($c['etudiant_id'])) api_error('Aucun compte étudiant rattaché.', 400);
            etudiant_sync_documents_historiques($pdo, (int)$c['etudiant_id'], $c, $adminUser);
            admin_log_action($id, 'sync_documents', 'Étudiant #' . $c['etudiant_id']);
            api_json(['ok' => true, 'message' => 'Documents synchronisés.']);
            break;

        case 'regen_activation':
            if (empty($c['etudiant_id'])) api_error('Aucun compte étudiant rattaché.', 400);
            $etuId = (int)$c['etudiant_id'];
            $pdo->prepare("UPDATE etudiant_tokens SET used_at=NOW()
                           WHERE etudiant_id=? AND type='activation' AND used_at IS NULL")
                ->execute([$etuId]);
            $token = etudiant_create_token($pdo, $etuId, 'activation', 14 * 24 * 3600);
            admin_log_action($id, 'regen_activation', 'Étudiant #' . $etuId);
            api_json([
                'ok' => true,
                'message' => 'Nouveau lien d\'activation généré.',
                'activation_url' => 'https://lms.ipec.school/etudiant/activer/' . $token,
            ]);
            break;

        default:
            api_error('Action inconnue : ' . $do, 400);
    }
} catch (\Throwable $e) {
    error_log('[admin-api/candidature-action] ' . $do . ' #' . $id . ' : ' . $e->getMessage());
    api_error('Erreur : ' . $e->getMessage(), 500);
}
