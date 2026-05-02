<?php
/**
 * IPEC — Classes PDF (extraites de mailer.php pour usage en mode librairie)
 *
 * Ce fichier est requis par mailer.php quand on l'inclut depuis l'admin
 * (IPEC_MAILER_AS_LIB) car le `goto IPEC_MAILER_END` saute la déclaration
 * procédurale d'origine. mailer.php en mode HTTP n'utilise PAS ce fichier
 * (la classe y est déclarée inline pour rester compatible avec l'historique).
 *
 * Doit être chargé APRÈS FPDF/fpdf.php.
 */

if (!class_exists('IpecCandiduature') && !class_exists('IpecCandidaturePdf') && class_exists('FPDF')) {
    if (!defined('FPDF_FONTPATH')) {
        define('FPDF_FONTPATH', __DIR__ . '/FPDF/font/');
    }

    class IpecCandidaturePdf extends FPDF {
        /** @var string 'candidature' | 'facture' | 'recu' */
        public $docKind = 'candidature';
        /** @var string */
        public $factureNumero = '';
        /** @var string Référence unique de candidature (IPEC-CAND-AAAA-XXXXXX) */
        public $reference = '';
        /** @var string Référence unique de facture (IPEC-FACT-AAAA-XXXXXX) */
        public $referenceFacture = '';
        /** @var string Référence unique de reçu de paiement (IPEC-RECU-AAAA-XXXXXX) */
        public $recuNumero = '';
        public function Footer() {
            $tr = function (string $s): string {
                $out = @iconv('UTF-8', 'CP1252//TRANSLIT//IGNORE', $s);
                return $out !== false ? $out : $s;
            };
            $this->SetY(-26);
            $this->SetDrawColor(220, 226, 240);
            $this->SetLineWidth(0.2);
            $this->Line(20, $this->GetY(), 190, $this->GetY());
            $this->Ln(2);
            $this->SetFont('Helvetica', '', 8);
            $this->SetTextColor(91, 100, 120);
            $this->Cell(0, 4, $tr("Institut Privé des Études Commerciales ASBL  ·  Chaussée d'Alsemberg 897, 1180 Uccle, Belgique"), 0, 1, 'C');
            $contactEmail = ($this->docKind === 'facture' || $this->docKind === 'recu') ? 'finance@ipec.school' : 'admission@ipec.school';
            $this->Cell(0, 4, $tr($contactEmail . "  ·  www.ipec.school"), 0, 1, 'C');
            if ($this->docKind === 'recu') {
                $refToShow = $this->recuNumero;
            } elseif ($this->docKind === 'facture') {
                $refToShow = $this->referenceFacture;
            } else {
                $refToShow = $this->reference;
            }
            // Candidature, facture & reçu : pas de libellé italique en bas,
            // la ligne d'authenticité descend avec un espace de séparation.
            if ($refToShow !== '') {
                $this->Ln(3);
                $this->SetFont('Helvetica', '', 7);
                $this->SetTextColor(44, 93, 219);
                $this->Cell(0, 4, $tr('Authenticité vérifiable sur ipec.school/verification — Réf. ' . $refToShow), 0, 1, 'C');
            }
        }
    }
}

if (!function_exists('buildPreadmissionPdf') && class_exists('IpecCandidaturePdf')) {
    /**
     * Génère la lettre de préadmission de l'étudiant en PDF (string FPDF 'S').
     *
     * Champs attendus dans $data :
     *   - reference_doc           IPEC-DOC-AAAA-XXXXXX
     *   - date_emission           YYYY-MM-DD (par défaut : aujourd'hui)
     *   - civilite, prenom, nom
     *   - numero_etudiant         (optionnel)
     *   - email                   (optionnel)
     *   - programme               libellé complet (ex : "PAA — Programme en Administration des Affaires")
     *   - annee                   ex : "1ʳᵉ année (BAC+1)"
     *   - specialisation          (optionnel)
     *   - rentree                 ex : "Septembre — 08/09/2025"
     *   - facture_t1_numero       IPEC-FACT-AAAA-XXXXXX (1ʳᵉ tranche 3 000 €)
     *   - facture_t1_echeance     YYYY-MM-DD
     *   - candidature_reference   (optionnel) IPEC-CAND-AAAA-XXXXXX
     */
    function buildPreadmissionPdf(array $data): string {
        $tr = function (string $s): string {
            $out = @iconv('UTF-8', 'CP1252//TRANSLIT//IGNORE', $s);
            return $out !== false ? $out : $s;
        };
        $fmt = function (?string $ymd): string {
            if (!$ymd) return '';
            $t = strtotime($ymd);
            return $t ? date('d/m/Y', $t) : (string)$ymd;
        };

        $civ      = trim((string)($data['civilite'] ?? ''));
        $prenom   = trim((string)($data['prenom'] ?? ''));
        $nom      = trim((string)($data['nom'] ?? ''));
        $fullName = trim($civ . ' ' . $prenom . ' ' . $nom);
        $numEtu   = trim((string)($data['numero_etudiant'] ?? ''));
        $email    = trim((string)($data['email'] ?? ''));

        $programme      = trim((string)($data['programme'] ?? ''));
        $annee          = trim((string)($data['annee'] ?? ''));
        $specialisation = trim((string)($data['specialisation'] ?? ''));
        $rentree        = trim((string)($data['rentree'] ?? ''));

        $factT1Num = trim((string)($data['facture_t1_numero'] ?? ''));
        $factT1Ech = $fmt((string)($data['facture_t1_echeance'] ?? ''));

        $refDoc = trim((string)($data['reference_doc'] ?? ''));
        $refCand = trim((string)($data['candidature_reference'] ?? ''));
        $emisYmd = (string)($data['date_emission'] ?? date('Y-m-d'));
        $emis = $fmt($emisYmd);

        $civAccord = (stripos($civ, 'mme') === 0 || stripos($civ, 'madame') === 0) ? 'Madame' : 'Monsieur';

        $pdf = new IpecCandidaturePdf('P', 'mm', 'A4');
        $pdf->docKind = 'document';
        $pdf->reference = $refDoc;
        $pdf->SetMargins(20, 20, 20);
        $pdf->SetAutoPageBreak(true, 28);
        $pdf->AddPage();

        // ---- En-tête : bandeau IPEC ----
        $pdf->SetFont('Helvetica', 'B', 22);
        $pdf->SetTextColor(27, 31, 42);
        $pdf->Cell(0, 10, $tr('IPEC'), 0, 1);
        $pdf->SetFont('Helvetica', '', 9);
        $pdf->SetTextColor(91, 100, 120);
        $pdf->Cell(0, 4, $tr("Institut Privé des Études Commerciales"), 0, 1);
        $pdf->Cell(0, 4, $tr("Service des admissions"), 0, 1);
        $pdf->Ln(6);
        $pdf->SetDrawColor(220, 226, 240);
        $pdf->SetLineWidth(0.3);
        $pdf->Line(20, $pdf->GetY(), 190, $pdf->GetY());
        $pdf->Ln(8);

        // ---- Bloc référence + date ----
        $pdf->SetFont('Helvetica', '', 9);
        $pdf->SetTextColor(91, 100, 120);
        $pdf->Cell(0, 5, $tr('Bruxelles, le ' . $emis), 0, 1, 'R');
        if ($refDoc !== '') {
            $pdf->Cell(0, 5, $tr('Référence document : ' . $refDoc), 0, 1, 'R');
        }
        if ($refCand !== '') {
            $pdf->Cell(0, 5, $tr('Référence candidature : ' . $refCand), 0, 1, 'R');
        }
        $pdf->Ln(6);

        // ---- Destinataire ----
        $pdf->SetFont('Helvetica', 'B', 11);
        $pdf->SetTextColor(27, 31, 42);
        $pdf->Cell(0, 6, $tr($fullName), 0, 1);
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->SetTextColor(91, 100, 120);
        if ($numEtu !== '') $pdf->Cell(0, 5, $tr('N° étudiant : ' . $numEtu), 0, 1);
        if ($email !== '')  $pdf->Cell(0, 5, $tr($email), 0, 1);
        $pdf->Ln(6);

        // ---- Objet ----
        $pdf->SetFont('Helvetica', 'B', 12);
        $pdf->SetTextColor(27, 31, 42);
        $pdf->Cell(0, 7, $tr("Objet : Lettre de préadmission à l'IPEC"), 0, 1);
        $pdf->Ln(2);

        // ---- Corps de la lettre ----
        $pdf->SetFont('Helvetica', '', 11);
        $pdf->SetTextColor(27, 31, 42);

        $p = function (string $text) use ($pdf, $tr) {
            $pdf->MultiCell(0, 5.5, $tr($text));
            $pdf->Ln(1.5);
        };

        $p($civAccord . ',');

        $detailsProg = $programme;
        if ($annee !== '')          $detailsProg .= ' — ' . $annee;
        if ($specialisation !== '' && stripos($specialisation, 'sais') === false) {
            $detailsProg .= ' (spécialisation : ' . $specialisation . ')';
        }

        $p("Nous avons le plaisir de vous informer que votre dossier de candidature a été examiné avec attention par la Commission pédagogique de l'IPEC. À l'issue de ses délibérations, la Commission a émis un avis favorable et a déclaré votre candidature recevable pour intégrer le programme suivant :");

        $pdf->SetFont('Helvetica', 'B', 11);
        $pdf->MultiCell(0, 5.5, $tr($detailsProg !== '' ? $detailsProg : 'Programme demandé'));
        if ($rentree !== '') {
            $pdf->SetFont('Helvetica', '', 11);
            $pdf->MultiCell(0, 5.5, $tr('Rentrée envisagée : ' . $rentree));
        }
        $pdf->Ln(1.5);
        $pdf->SetFont('Helvetica', '', 11);

        $p("Cette préadmission constitue une étape déterminante de votre parcours d'inscription, mais elle ne vaut pas encore admission définitive. Conformément aux conditions générales de l'IPEC, votre inscription ne deviendra effective qu'après réception du paiement de la première tranche des droits de scolarité, d'un montant de 3 000 € (trois mille euros).");

        if ($factT1Num !== '') {
            $factLine = "Cette première tranche fait l'objet de la facture " . $factT1Num;
            if ($factT1Ech !== '') $factLine .= ", à régler avant le " . $factT1Ech;
            $factLine .= ". Vous la retrouvez dès à présent dans votre espace étudiant, à la rubrique « Mes factures ».";
            $p($factLine);
        } else {
            $p("La facture correspondant à cette première tranche est dès à présent disponible dans votre espace étudiant, à la rubrique « Mes factures ».");
        }

        $p("Dès réception de ce paiement, votre admission sera confirmée et nous vous transmettrons l'ensemble des documents nécessaires à la finalisation de votre inscription, ainsi que, le cas échéant, les pièces requises pour vos démarches administratives (attestation d'inscription, demande de visa étudiant, etc.).");

        $p("L'équipe pédagogique et administrative de l'IPEC se réjouit de la perspective de vous compter prochainement parmi ses étudiants. Nous restons à votre disposition pour toute question relative à cette préadmission ou à votre future rentrée à l'adresse admission@ipec.school.");

        $p("Nous vous prions de croire, " . $civAccord . ", en l'expression de nos salutations distinguées.");

        $pdf->Ln(6);
        $pdf->SetFont('Helvetica', 'B', 11);
        $pdf->Cell(0, 6, $tr("Le Service des admissions"), 0, 1);
        $pdf->SetFont('Helvetica', 'I', 10);
        $pdf->SetTextColor(91, 100, 120);
        $pdf->Cell(0, 5, $tr("Institut Privé des Études Commerciales (IPEC)"), 0, 1);

        return (string)$pdf->Output('S');
    }
}

