<?php
/**
 * IPEC — Espace étudiant : téléchargement PDF à la volée
 *
 *   /etudiant/telecharger.php?type=facture&id=42
 *   /etudiant/telecharger.php?type=document&id=12
 *
 * Le PDF est régénéré depuis SQL + builders FPDF. Aucun fichier disque.
 */
require_once __DIR__ . '/_bootstrap.php';
$user = etu_require_login();
$pdo  = db();

$type = (string)($_GET['type'] ?? '');
$id   = (int)($_GET['id'] ?? 0);
if ($id <= 0 || !in_array($type, ['facture', 'document'], true)) {
    http_response_code(400); exit('Requête invalide.');
}

function safe_filename(string $base, string $ref): string {
    $ref = preg_replace('/[^A-Za-z0-9_-]+/', '-', $ref);
    return $base . '-' . trim($ref, '-') . '.pdf';
}

try {
    if ($type === 'facture') {
        // Charge la facture + vérifie qu'elle appartient à l'étudiant connecté
        $stmt = $pdo->prepare(
            "SELECT f.*, e.civilite, e.prenom, e.nom, e.email,
                    c.rue, c.numero AS num_rue, c.code_postal, c.ville, c.pays_residence,
                    c.programme, c.annee, c.rentree
             FROM factures f
             INNER JOIN etudiants e ON e.id = f.etudiant_id
             LEFT JOIN candidatures c ON c.id = f.candidature_id
             WHERE f.id = ? AND f.etudiant_id = ? AND f.visible_etudiant = 1
             LIMIT 1"
        );
        $stmt->execute([$id, $user['id']]);
        $f = $stmt->fetch();
        if (!$f) { http_response_code(404); exit('Facture introuvable.'); }

        $adresse = trim(
            trim(($f['rue'] ?? '') . ' ' . ($f['num_rue'] ?? '')) .
            (($f['code_postal'] || $f['ville']) ? ', ' . trim(($f['code_postal'] ?? '') . ' ' . ($f['ville'] ?? '')) : '') .
            ($f['pays_residence'] ? ', ' . $f['pays_residence'] : '')
        );

        $factureFields = [
            'reference'         => $f['numero'],          // pas de candidature_ref obligatoire ici
            'reference_facture' => $f['numero'],
            'civilite'      => $f['civilite'],
            'prenom'        => $f['prenom'],
            'nom'           => $f['nom'],
            'adresse'       => $adresse,
            'rue'           => $f['rue'],
            'numero'        => $f['num_rue'],
            'codePostal'    => $f['code_postal'],
            'ville'         => $f['ville'],
            'paysResidence' => $f['pays_residence'],
            'email'         => $f['email'],
            'programme'     => $f['programme'],
            'annee'         => $f['annee'],
            'rentree'       => $f['rentree'],
            // Surcharges spécifiques à cette facture
            'libelle'       => $f['libelle'],
            'description'   => $f['description'],
            'montant_ttc_cents' => (int)$f['montant_ttc_cents'],
            'tva_taux'      => (float)$f['tva_taux'],
            'devise'        => $f['devise'],
            'date_emission' => $f['date_emission'],
            'date_echeance' => $f['date_echeance'],
        ];

        [$pdf, $filename] = buildFacturePdf($factureFields);
        if ($pdf === '') throw new RuntimeException('PDF vide.');
        $filename = safe_filename('facture', $f['numero']);

        $pdo->prepare("INSERT INTO etudiant_actions (etudiant_id, action, detail, ip)
                       VALUES (?, 'view_facture', ?, ?)")
            ->execute([$user['id'], 'Facture ' . $f['numero'], $_SERVER['REMOTE_ADDR'] ?? null]);

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($pdf));
        echo $pdf; exit;
    }

    // type === 'document'
    $stmt = $pdo->prepare(
        "SELECT d.*, e.civilite, e.prenom, e.nom, e.email, e.numero_etudiant
         FROM documents d
         INNER JOIN etudiants e ON e.id = d.etudiant_id
         WHERE d.id = ? AND d.etudiant_id = ? AND d.visible_etudiant = 1 AND d.statut = 'publie'
         LIMIT 1"
    );
    $stmt->execute([$id, $user['id']]);
    $d = $stmt->fetch();
    if (!$d) { http_response_code(404); exit('Document introuvable.'); }

    // Régénération générique d'un document à partir de FPDF.
    $data = [];
    if (!empty($d['data_json'])) {
        $tmp = json_decode($d['data_json'], true);
        if (is_array($tmp)) $data = $tmp;
    }

    require_once __DIR__ . '/../FPDF/fpdf.php';
    require_once __DIR__ . '/../_pdf_classes.php';
    if (!defined('IPEC_MAILER_AS_LIB')) define('IPEC_MAILER_AS_LIB', true);
    require_once __DIR__ . '/../mailer.php'; // mode lib : expose buildCandidaturePdf, buildFacturePdf

    // ---- Template "recap_candidature" → builder dédié (PDF identique au mail historique) ----
    if ($d['template'] === 'recap_candidature' && function_exists('buildCandidaturePdf')) {
        $out = buildCandidaturePdf($data);
        if ($out === '') { http_response_code(500); exit('PDF vide.'); }

        $pdo->prepare("UPDATE documents
                       SET vu_etudiant_at = COALESCE(vu_etudiant_at, NOW()),
                           nb_telechargements = nb_telechargements + 1
                       WHERE id = ?")->execute([$id]);
        $pdo->prepare("INSERT INTO etudiant_actions (etudiant_id, action, detail, ip)
                       VALUES (?, 'download_doc', ?, ?)")
            ->execute([$user['id'], 'Document ' . $d['reference'], $_SERVER['REMOTE_ADDR'] ?? null]);

        $filename = safe_filename('candidature', (string)($data['reference'] ?? $d['reference']));
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($out));
        echo $out; exit;
    }


    $tr = function (string $s): string {
        $out = @iconv('UTF-8', 'CP1252//TRANSLIT//IGNORE', $s);
        return $out !== false ? $out : $s;
    };

    $pdf = new IpecCandidaturePdf('P', 'mm', 'A4');
    $pdf->docKind = 'document';
    $pdf->reference = $d['reference'];
    $pdf->SetMargins(20, 22, 20);
    $pdf->SetAutoPageBreak(true, 28);
    $pdf->AddPage();

    // Titre
    $pdf->SetFont('Helvetica', 'B', 18);
    $pdf->SetTextColor(27, 31, 42);
    $pdf->Cell(0, 10, $tr($d['titre']), 0, 1);
    $pdf->SetFont('Helvetica', '', 9);
    $pdf->SetTextColor(91, 100, 120);
    $pdf->Cell(0, 5, $tr('Référence : ' . $d['reference'] . '   ·   Émis le ' . date('d/m/Y', strtotime($d['date_emission']))), 0, 1);
    $pdf->Ln(8);

    // Destinataire
    $pdf->SetFont('Helvetica', 'B', 11);
    $pdf->SetTextColor(27, 31, 42);
    $pdf->Cell(0, 6, $tr('Destinataire'), 0, 1);
    $pdf->SetFont('Helvetica', '', 11);
    $pdf->Cell(0, 6, $tr(trim(($d['civilite'] ?: '') . ' ' . $d['prenom'] . ' ' . $d['nom'])), 0, 1);
    if ($d['numero_etudiant']) {
        $pdf->Cell(0, 6, $tr('N° étudiant : ' . $d['numero_etudiant']), 0, 1);
    }
    $pdf->Cell(0, 6, $tr($d['email']), 0, 1);
    $pdf->Ln(6);

    // Corps
    if ($d['description']) {
        $pdf->SetFont('Helvetica', '', 11);
        $pdf->MultiCell(0, 6, $tr($d['description']));
        $pdf->Ln(4);
    }

    if ($data) {
        $pdf->SetFont('Helvetica', 'B', 11);
        $pdf->Cell(0, 6, $tr('Détails'), 0, 1);
        $pdf->SetFont('Helvetica', '', 10);
        foreach ($data as $k => $v) {
            if (is_scalar($v)) {
                $pdf->Cell(60, 6, $tr(ucfirst(str_replace('_',' ', (string)$k))), 0, 0);
                $pdf->MultiCell(0, 6, $tr((string)$v));
            }
        }
    }

    if ($d['valide_jusqu_au']) {
        $pdf->Ln(4);
        $pdf->SetFont('Helvetica', 'I', 10);
        $pdf->SetTextColor(91, 100, 120);
        $pdf->Cell(0, 6, $tr('Valide jusqu\'au ' . date('d/m/Y', strtotime($d['valide_jusqu_au']))), 0, 1);
    }

    $pdf->Ln(10);
    $pdf->SetFont('Helvetica', '', 10);
    $pdf->SetTextColor(27, 31, 42);
    $pdf->Cell(0, 6, $tr('Fait à Bruxelles, le ' . date('d/m/Y')), 0, 1);
    $pdf->Ln(2);
    $pdf->Cell(0, 6, $tr('Pour l\'IPEC — Service administratif'), 0, 1);

    $out = $pdf->Output('S');

    // Compteurs
    $pdo->prepare("UPDATE documents
                   SET vu_etudiant_at = COALESCE(vu_etudiant_at, NOW()),
                       nb_telechargements = nb_telechargements + 1
                   WHERE id = ?")->execute([$id]);
    $pdo->prepare("INSERT INTO etudiant_actions (etudiant_id, action, detail, ip)
                   VALUES (?, 'download_doc', ?, ?)")
        ->execute([$user['id'], 'Document ' . $d['reference'], $_SERVER['REMOTE_ADDR'] ?? null]);

    $filename = safe_filename('document', $d['reference']);
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($out));
    echo $out; exit;

} catch (\Throwable $e) {
    error_log('[etudiant/telecharger] ' . $e->getMessage());
    http_response_code(500);
    exit('Erreur lors de la génération du PDF.');
}
