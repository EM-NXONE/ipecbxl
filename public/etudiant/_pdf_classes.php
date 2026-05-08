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
        $pdf->SetAutoPageBreak(true, 30);
        $pdf->SetTitle($tr("Lettre de préadmission IPEC"));
        $pdf->SetAuthor($tr("IPEC — Institut Privé des Études Commerciales"));
        $pdf->SetCreator('www.ipec.school');
        $pdf->AddPage();

        // ===== EN-TÊTE (identique candidature/facture) =====
        $logoPath = __DIR__ . '/ipec-logo-email.png';
        if (is_file($logoPath)) {
            try { $pdf->Image($logoPath, 20, 15, 18, 18); }
            catch (\Throwable $e) { /* ignore */ }
        }
        $pdf->SetXY(41, 19);
        $pdf->SetFont('Times', '', 18);
        $pdf->SetTextColor(15, 21, 37);
        $pdf->Cell(0, 7, $tr('IPEC'), 0, 2);
        $pdf->SetX(41);
        $pdf->SetFont('Helvetica', '', 6);
        $pdf->SetTextColor(120, 130, 150);
        $subtitle = 'INSTITUT PRIVÉ DES ÉTUDES COMMERCIALES';
        $spaced   = implode(' ', preg_split('//u', $subtitle, -1, PREG_SPLIT_NO_EMPTY));
        $pdf->Cell(0, 4, $tr($spaced), 0, 2);

        // Bloc identification document (à droite)
        $pdf->SetXY(120, 20);
        $pdf->SetFont('Helvetica', 'B', 13);
        $pdf->SetTextColor(44, 93, 219);
        $pdf->Cell(70, 7, $tr('PRÉADMISSION'), 0, 2, 'R');
        $pdf->SetX(120);
        $pdf->SetFont('Helvetica', '', 9);
        $pdf->SetTextColor(91, 100, 120);
        if ($refDoc !== '') {
            $pdf->Cell(70, 5, $tr('N° ' . $refDoc), 0, 2, 'R');
            $pdf->SetX(120);
        }
        $pdf->Cell(70, 5, $tr('Date : ' . $emis), 0, 2, 'R');
        if ($refCand !== '') {
            $pdf->SetX(120);
            $pdf->Cell(70, 5, $tr('Réf. cand. : ' . $refCand), 0, 2, 'R');
        }

        $pdf->SetY(40);
        $pdf->SetDrawColor(44, 93, 219);
        $pdf->SetLineWidth(0.6);
        $pdf->Line(20, 40, 190, 40);
        $pdf->Ln(10);

        // ---- Destinataire ----
        $pdf->SetFont('Helvetica', 'B', 11);
        $pdf->SetTextColor(15, 21, 37);
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

        $p("Dès réception de ce paiement, votre admission sera confirmée et nous vous transmettrons les documents nécessaires à la finalisation de votre inscription (attestation d'inscription, pièces requises pour la demande de visa étudiant le cas échéant, etc.).");

        $p("L'équipe de l'IPEC se réjouit de vous compter prochainement parmi ses étudiants et reste à votre disposition à l'adresse admission@ipec.school.");

        $p("Nous vous prions de croire, " . $civAccord . ", en l'expression de nos salutations distinguées.");

        $pdf->Ln(3);
        $pdf->SetFont('Helvetica', 'B', 11);
        $pdf->Cell(0, 6, $tr("Le Service des admissions"), 0, 1);
        $pdf->SetFont('Helvetica', 'I', 10);
        $pdf->SetTextColor(91, 100, 120);
        $pdf->Cell(0, 5, $tr("Institut Privé des Études Commerciales (IPEC)"), 0, 1);

        return (string)$pdf->Output('S');
    }
}

/* ============================================================
 * Helpers communs en-tête / pied (attestation + formulaire)
 * ============================================================ */
if (!function_exists('ipec_doc_header') && class_exists('IpecCandidaturePdf')) {
    function ipec_doc_header(IpecCandidaturePdf $pdf, string $titreBandeau, string $refDoc, string $emisFr, string $refCand = ''): void {
        $tr = function (string $s): string {
            $out = @iconv('UTF-8', 'CP1252//TRANSLIT//IGNORE', $s);
            return $out !== false ? $out : $s;
        };
        $logoPath = __DIR__ . '/ipec-logo-email.png';
        if (is_file($logoPath)) {
            try { $pdf->Image($logoPath, 20, 15, 18, 18); } catch (\Throwable $e) {}
        }
        $pdf->SetXY(41, 19);
        $pdf->SetFont('Times', '', 18);
        $pdf->SetTextColor(15, 21, 37);
        $pdf->Cell(0, 7, $tr('IPEC'), 0, 2);
        $pdf->SetX(41);
        $pdf->SetFont('Helvetica', '', 6);
        $pdf->SetTextColor(120, 130, 150);
        $subtitle = 'INSTITUT PRIVÉ DES ÉTUDES COMMERCIALES';
        $spaced   = implode(' ', preg_split('//u', $subtitle, -1, PREG_SPLIT_NO_EMPTY));
        $pdf->Cell(0, 4, $tr($spaced), 0, 2);

        $pdf->SetXY(110, 20);
        $pdf->SetFont('Helvetica', 'B', 13);
        $pdf->SetTextColor(44, 93, 219);
        $pdf->Cell(80, 7, $tr($titreBandeau), 0, 2, 'R');
        $pdf->SetX(110);
        $pdf->SetFont('Helvetica', '', 9);
        $pdf->SetTextColor(91, 100, 120);
        if ($refDoc !== '') { $pdf->Cell(80, 5, $tr('N° ' . $refDoc), 0, 2, 'R'); $pdf->SetX(110); }
        $pdf->Cell(80, 5, $tr('Date : ' . $emisFr), 0, 2, 'R');
        if ($refCand !== '') { $pdf->SetX(110); $pdf->Cell(80, 5, $tr('Réf. cand. : ' . $refCand), 0, 2, 'R'); }

        $pdf->SetY(40);
        $pdf->SetDrawColor(44, 93, 219);
        $pdf->SetLineWidth(0.6);
        $pdf->Line(20, 40, 190, 40);
        $pdf->Ln(10);
    }
}

/* ============================================================
 * buildAttestationInscriptionPdf
 * ============================================================ */
if (!function_exists('buildAttestationInscriptionPdf') && class_exists('IpecCandidaturePdf')) {
    function buildAttestationInscriptionPdf(array $data): string {
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
        $dateNaiss = $fmt((string)($data['date_naissance'] ?? ''));
        $nationalite = trim((string)($data['nationalite'] ?? ''));

        $programme      = trim((string)($data['programme'] ?? ''));
        $annee          = trim((string)($data['annee'] ?? ''));
        $specialisation = trim((string)($data['specialisation'] ?? ''));
        $rentree        = trim((string)($data['rentree'] ?? ''));
        $anneeAcad      = trim((string)($data['annee_academique'] ?? ''));
        if (function_exists('ipec_rentree_label_normalized') && $rentree !== '') {
            $rentree = ipec_rentree_label_normalized($rentree);
        }
        if (function_exists('ipec_academic_year_for')) {
            $anneeAcad = ipec_academic_year_for($anneeAcad);
        }

        $factT1Num = trim((string)($data['facture_t1_numero'] ?? ''));
        $factT1Paye = $fmt((string)($data['facture_t1_paye_at'] ?? ''));

        $refDoc = trim((string)($data['reference_doc'] ?? ''));
        $refCand = trim((string)($data['candidature_reference'] ?? ''));
        $emis = $fmt((string)($data['date_emission'] ?? date('Y-m-d')));

        $civAccord = (stripos($civ, 'mme') === 0 || stripos($civ, 'madame') === 0) ? 'Madame' : 'Monsieur';

        $pdf = new IpecCandidaturePdf('P', 'mm', 'A4');
        $pdf->docKind = 'document';
        $pdf->reference = $refDoc;
        $pdf->SetMargins(20, 20, 20);
        $pdf->SetAutoPageBreak(true, 30);
        $pdf->SetTitle($tr("Attestation d'inscription définitive — IPEC"));
        $pdf->SetAuthor($tr("IPEC — Institut Privé des Études Commerciales"));
        $pdf->SetCreator('www.ipec.school');
        $pdf->AddPage();

        ipec_doc_header($pdf, "ATTESTATION D'INSCRIPTION", $refDoc, $emis, $refCand);

        // Destinataire
        $pdf->SetFont('Helvetica', 'B', 11);
        $pdf->SetTextColor(15, 21, 37);
        $pdf->Cell(0, 6, $tr($fullName), 0, 1);
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->SetTextColor(91, 100, 120);
        if ($numEtu !== '')   $pdf->Cell(0, 5, $tr('N° étudiant : ' . $numEtu), 0, 1);
        if ($dateNaiss !== '') $pdf->Cell(0, 5, $tr('Né(e) le ' . $dateNaiss . ($nationalite !== '' ? ' — ' . $nationalite : '')), 0, 1);
        if ($email !== '')    $pdf->Cell(0, 5, $tr($email), 0, 1);
        $pdf->Ln(6);

        // Objet
        $pdf->SetFont('Helvetica', 'B', 12);
        $pdf->SetTextColor(27, 31, 42);
        $pdf->Cell(0, 7, $tr("Objet : Attestation d'inscription définitive"), 0, 1);
        $pdf->Ln(2);

        $pdf->SetFont('Helvetica', '', 11);
        $pdf->SetTextColor(27, 31, 42);
        $p = function (string $t) use ($pdf, $tr) { $pdf->MultiCell(0, 5.5, $tr($t)); $pdf->Ln(1.5); };

        $p("Je soussigné, le Directeur des admissions de l'Institut Privé des Études Commerciales (IPEC), atteste par la présente que " . $civAccord . " " . $prenom . " " . $nom
           . ($dateNaiss !== '' ? ", né(e) le " . $dateNaiss : '')
           . ($numEtu !== '' ? " (n° étudiant " . $numEtu . ")" : '')
           . ", est régulièrement inscrit(e) à l'IPEC pour l'année académique "
           . ($anneeAcad !== '' ? $anneeAcad : '\xC3\xA0 venir') . " dans le programme suivant :");

        $detailsProg = $programme;
        if ($annee !== '')          $detailsProg .= ' — ' . $annee;
        if ($specialisation !== '' && stripos($specialisation, 'sais') === false) {
            $detailsProg .= ' (spécialisation : ' . $specialisation . ')';
        }
        $pdf->SetFont('Helvetica', 'B', 11);
        $pdf->MultiCell(0, 5.5, $tr($detailsProg !== '' ? $detailsProg : 'Programme'));
        if ($rentree !== '') {
            $pdf->SetFont('Helvetica', '', 11);
            $pdf->MultiCell(0, 5.5, $tr('Rentrée : ' . $rentree));
        }
        $pdf->Ln(1.5);
        $pdf->SetFont('Helvetica', '', 11);

        $factLine = "Cette inscription est définitive : la première tranche des droits de scolarité (3 000 €) a été acquittée";
        if ($factT1Paye !== '') $factLine .= " le " . $factT1Paye;
        if ($factT1Num !== '')  $factLine .= " (facture " . $factT1Num . ")";
        $factLine .= ". L'étudiant(e) figure désormais sur les listes officielles de l'établissement.";
        $p($factLine);

        $p("La présente attestation est délivrée à l'intéressé(e) pour faire valoir ce que de droit, notamment auprès des administrations, ambassades et consulats (demande de visa étudiant), employeurs, banques et organismes d'assurance.");

        $p("Fait à Bruxelles, le " . $emis . ".");

        $pdf->Ln(10);
        $pdf->SetFont('Helvetica', 'B', 11);
        $pdf->Cell(0, 6, $tr("Le Service des admissions"), 0, 1);
        $pdf->SetFont('Helvetica', 'I', 10);
        $pdf->SetTextColor(91, 100, 120);
        $pdf->Cell(0, 5, $tr("Institut Privé des Études Commerciales (IPEC)"), 0, 1);

        return (string)$pdf->Output('S');
    }
}

/* ============================================================
 * buildFormulaireInscriptionPdf — formulaire standard pré-rempli
 * ============================================================ */
if (!function_exists('buildFormulaireInscriptionPdf') && class_exists('IpecCandidaturePdf')) {
    function buildFormulaireInscriptionPdf(array $data): string {
        $tr = function (string $s): string {
            $out = @iconv('UTF-8', 'CP1252//TRANSLIT//IGNORE', $s);
            return $out !== false ? $out : $s;
        };
        $fmt = function (?string $ymd): string {
            if (!$ymd) return '';
            $t = strtotime($ymd);
            return $t ? date('d/m/Y', $t) : (string)$ymd;
        };

        $refDoc  = trim((string)($data['reference_doc'] ?? ''));
        $refCand = trim((string)($data['candidature_reference'] ?? ''));
        $emis    = $fmt((string)($data['date_emission'] ?? date('Y-m-d')));

        $pdf = new IpecCandidaturePdf('P', 'mm', 'A4');
        $pdf->docKind = 'document';
        $pdf->reference = $refDoc;
        $pdf->SetMargins(20, 20, 20);
        $pdf->SetAutoPageBreak(true, 30);
        $pdf->SetTitle($tr("Formulaire standard d'inscription — IPEC"));
        $pdf->SetAuthor($tr("IPEC — Institut Privé des Études Commerciales"));
        $pdf->SetCreator('www.ipec.school');
        $pdf->AddPage();

        ipec_doc_header($pdf, "FORMULAIRE D'INSCRIPTION", $refDoc, $emis, $refCand);

        $pdf->SetFont('Helvetica', 'B', 12);
        $pdf->SetTextColor(27, 31, 42);
        $pdf->Cell(0, 7, $tr("Formulaire standard d'inscription — Année académique " . trim((string)($data['annee_academique'] ?? ''))), 0, 1);
        $pdf->Ln(1);
        $pdf->SetFont('Helvetica', 'I', 9);
        $pdf->SetTextColor(91, 100, 120);
        $pdf->MultiCell(0, 4.5, $tr("Document pré-rempli sur la base de votre dossier de candidature. Vérifiez les informations, complétez les rubriques manquantes et signez les deux engagements en dernière page."));
        $pdf->Ln(3);

        // Rendu de section
        $section = function (string $titre) use ($pdf, $tr) {
            $pdf->Ln(2);
            $pdf->SetFont('Helvetica', 'B', 11);
            $pdf->SetTextColor(44, 93, 219);
            $pdf->Cell(0, 6, $tr($titre), 0, 1);
            $pdf->SetDrawColor(220, 226, 240);
            $pdf->SetLineWidth(0.2);
            $y = $pdf->GetY();
            $pdf->Line(20, $y, 190, $y);
            $pdf->Ln(2);
            $pdf->SetTextColor(27, 31, 42);
        };
        $field = function (string $label, string $value) use ($pdf, $tr) {
            $pdf->SetFont('Helvetica', '', 9);
            $pdf->SetTextColor(91, 100, 120);
            $pdf->Cell(50, 5, $tr($label), 0, 0);
            $pdf->SetFont('Helvetica', 'B', 10);
            $pdf->SetTextColor(27, 31, 42);
            $pdf->MultiCell(0, 5, $tr($value !== '' ? $value : '—'));
        };
        $blank = function (string $label) use ($pdf, $tr) {
            $pdf->SetFont('Helvetica', '', 9);
            $pdf->SetTextColor(91, 100, 120);
            $pdf->Cell(50, 7, $tr($label), 0, 0);
            $pdf->SetDrawColor(180, 188, 204);
            $pdf->SetLineWidth(0.2);
            $y = $pdf->GetY() + 6;
            $pdf->Line(70, $y, 190, $y);
            $pdf->Ln(7);
        };

        // 1) Identité
        $section("1. Identité");
        $field("Civilité",        (string)($data['civilite'] ?? ''));
        $field("Nom",             (string)($data['nom'] ?? ''));
        $field("Prénom(s)",       (string)($data['prenom'] ?? ''));
        $field("Date de naissance", $fmt((string)($data['date_naissance'] ?? '')));
        $field("Nationalité",     (string)($data['nationalite'] ?? ''));
        $field("N° étudiant",     (string)($data['numero_etudiant'] ?? ''));
        $blank("Lieu de naissance");
        $blank("N° de registre national / passeport");

        // 2) Coordonnées
        $section("2. Coordonnées");
        $field("E-mail",          (string)($data['email'] ?? ''));
        $field("Téléphone",       (string)($data['telephone'] ?? ''));
        $rue = trim(trim((string)($data['rue'] ?? '')) . ' ' . trim((string)($data['numero_rue'] ?? '')));
        $field("Adresse",         $rue);
        $cpville = trim(trim((string)($data['code_postal'] ?? '')) . ' ' . trim((string)($data['ville'] ?? '')));
        $field("Code postal / Ville", $cpville);
        $field("Pays de résidence", (string)($data['pays_residence'] ?? ''));

        // 3) Programme
        $section("3. Programme et année");
        $field("Programme",       (string)($data['programme'] ?? ''));
        $field("Année",           (string)($data['annee'] ?? ''));
        $spec = trim((string)($data['specialisation'] ?? ''));
        $field("Spécialisation",  ($spec !== '' && stripos($spec, 'sais') === false) ? $spec : '');
        $field("Rentrée",         (string)($data['rentree'] ?? ''));
        $field("Année académique", (string)($data['annee_academique'] ?? ''));

        // 4) Engagements
        $section("4. Engagements de l'étudiant");
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->MultiCell(0, 5, $tr(
            "Je soussigné(e), candidat(e) à l'inscription définitive à l'IPEC, déclare :\n"
          . "  • avoir pris connaissance et accepter sans réserve le règlement intérieur, le règlement des études et les CGV publiés sur ipec.school ;\n"
          . "  • certifier l'exactitude des informations fournies dans le présent formulaire et dans mon dossier de candidature ;\n"
          . "  • m'engager à régler les tranches de scolarité restantes selon l'échéancier figurant dans mon espace étudiant ;\n"
          . "  • autoriser l'IPEC à traiter mes données personnelles dans le cadre de ma scolarité, conformément à la Politique de confidentialité de l'établissement."
        ));
        $pdf->Ln(4);

        // Signatures
        $pdf->SetFont('Helvetica', 'B', 10);
        $pdf->SetTextColor(27, 31, 42);
        $yStart = $pdf->GetY();
        $pdf->Cell(85, 5, $tr("Signature de l'étudiant(e)"), 0, 0);
        $pdf->Cell(0, 5, $tr("Pour l'IPEC — Service des admissions"), 0, 1);
        $pdf->SetFont('Helvetica', 'I', 9);
        $pdf->SetTextColor(91, 100, 120);
        $pdf->Cell(85, 5, $tr("(précédée de « Lu et approuvé »)"), 0, 0);
        $pdf->Cell(0, 5, $tr("Cachet et signature"), 0, 1);
        $pdf->Ln(20);
        $pdf->SetDrawColor(180, 188, 204);
        $pdf->SetLineWidth(0.2);
        $y = $pdf->GetY();
        $pdf->Line(20, $y, 95, $y);
        $pdf->Line(115, $y, 190, $y);
        $pdf->Ln(2);
        $pdf->SetFont('Helvetica', '', 9);
        $pdf->SetTextColor(91, 100, 120);
        $pdf->Cell(85, 4, $tr("Fait à _________________  le ___/___/______"), 0, 0);
        $pdf->Cell(0, 4, $tr("Bruxelles, le " . $emis), 0, 1);

        return (string)$pdf->Output('S');
    }
}

/* ============================================================
 * buildAttestationReussitePdf — fin de cursus / diplomation
 * ============================================================ */
if (!function_exists('buildAttestationReussitePdf') && class_exists('IpecCandidaturePdf')) {
    function buildAttestationReussitePdf(array $data): string {
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
        $dateNaiss   = $fmt((string)($data['date_naissance'] ?? ''));
        $programme   = trim((string)($data['programme'] ?? ''));
        $annee       = trim((string)($data['annee'] ?? ''));
        $specialisation = trim((string)($data['specialisation'] ?? ''));
        $anneeAcad   = trim((string)($data['annee_academique'] ?? ''));
        if (function_exists('ipec_academic_year_for')) { $anneeAcad = ipec_academic_year_for($anneeAcad); }
        $refDoc      = trim((string)($data['reference_doc'] ?? ''));
        $refCand     = trim((string)($data['candidature_reference'] ?? ''));
        $emis        = $fmt((string)($data['date_emission'] ?? date('Y-m-d')));
        $civAccord   = (stripos($civ, 'mme') === 0 || stripos($civ, 'madame') === 0) ? 'Madame' : 'Monsieur';

        $pdf = new IpecCandidaturePdf('P', 'mm', 'A4');
        $pdf->docKind = 'document';
        $pdf->reference = $refDoc;
        $pdf->SetMargins(20, 20, 20);
        $pdf->SetAutoPageBreak(true, 30);
        $pdf->SetTitle($tr("Attestation de réussite — IPEC"));
        $pdf->SetAuthor($tr("IPEC — Institut Privé des Études Commerciales"));
        $pdf->AddPage();

        ipec_doc_header($pdf, "ATTESTATION DE RÉUSSITE", $refDoc, $emis, $refCand);

        $pdf->SetFont('Helvetica', 'B', 11);
        $pdf->SetTextColor(15, 21, 37);
        $pdf->Cell(0, 6, $tr(trim($civ . ' ' . $prenom . ' ' . $nom)), 0, 1);
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->SetTextColor(91, 100, 120);
        if ($dateNaiss !== '') $pdf->Cell(0, 5, $tr('Né(e) le ' . $dateNaiss), 0, 1);
        $pdf->Ln(6);

        $pdf->SetFont('Helvetica', 'B', 12);
        $pdf->SetTextColor(27, 31, 42);
        $pdf->Cell(0, 7, $tr("Objet : Attestation de réussite — fin de cursus"), 0, 1);
        $pdf->Ln(2);

        $pdf->SetFont('Helvetica', '', 11);
        $p = function (string $t) use ($pdf, $tr) { $pdf->MultiCell(0, 5.5, $tr($t)); $pdf->Ln(1.5); };

        $detail = $programme . ($annee !== '' ? ' — ' . $annee : '')
                . ($specialisation !== '' && stripos($specialisation, 'sais') === false
                    ? ' (spécialisation : ' . $specialisation . ')' : '');

        $p("Je soussigné, le Directeur de l'Institut Privé des Études Commerciales (IPEC), atteste que "
            . $civAccord . " " . $prenom . " " . $nom
            . ($dateNaiss !== '' ? ", né(e) le " . $dateNaiss : '')
            . ", a suivi avec succès et validé l'intégralité du cursus suivant à l'IPEC :");

        $pdf->SetFont('Helvetica', 'B', 11);
        $pdf->MultiCell(0, 5.5, $tr($detail !== '' ? $detail : 'Programme'));
        if ($anneeAcad !== '') {
            $pdf->SetFont('Helvetica', '', 11);
            $pdf->MultiCell(0, 5.5, $tr("Année académique de fin de cursus : " . $anneeAcad));
        }
        $pdf->Ln(2);
        $pdf->SetFont('Helvetica', '', 11);

        $p("L'ensemble des modules d'enseignement, examens et travaux requis pour l'obtention du diplôme correspondant ont été validés. La présente attestation est délivrée à titre officiel pour faire valoir ce que de droit, dans l'attente de la remise du diplôme.");

        $p("Fait à Bruxelles, le " . $emis . ".");

        $pdf->Ln(12);
        $pdf->SetFont('Helvetica', 'B', 11);
        $pdf->Cell(0, 6, $tr("La Direction"), 0, 1);
        $pdf->SetFont('Helvetica', 'I', 10);
        $pdf->SetTextColor(91, 100, 120);
        $pdf->Cell(0, 5, $tr("Institut Privé des Études Commerciales (IPEC)"), 0, 1);

        return (string)$pdf->Output('S');
    }
}

/* ============================================================
 * buildAttestationReussiteAnneePdf — réussite d'une année (passage)
 * ============================================================ */
if (!function_exists('buildAttestationReussiteAnneePdf') && class_exists('IpecCandidaturePdf')) {
    function buildAttestationReussiteAnneePdf(array $data): string {
        $tr = function (string $s): string {
            $out = @iconv('UTF-8', 'CP1252//TRANSLIT//IGNORE', $s);
            return $out !== false ? $out : $s;
        };
        $fmt = function (?string $ymd): string {
            if (!$ymd) return '';
            $t = strtotime($ymd);
            return $t ? date('d/m/Y', $t) : (string)$ymd;
        };

        $civ        = trim((string)($data['civilite'] ?? ''));
        $prenom     = trim((string)($data['prenom'] ?? ''));
        $nom        = trim((string)($data['nom'] ?? ''));
        $dateNaiss  = $fmt((string)($data['date_naissance'] ?? ''));
        $programme  = trim((string)($data['programme'] ?? ''));
        $annee      = trim((string)($data['annee'] ?? ''));
        $specialisation = trim((string)($data['specialisation'] ?? ''));
        $anneeAcad  = trim((string)($data['annee_academique'] ?? ''));
        if (function_exists('ipec_academic_year_for')) { $anneeAcad = ipec_academic_year_for($anneeAcad); }
        $anneeSuiv  = trim((string)($data['annee_suivante'] ?? ''));
        $refDoc     = trim((string)($data['reference_doc'] ?? ''));
        $refCand    = trim((string)($data['candidature_reference'] ?? ''));
        $emis       = $fmt((string)($data['date_emission'] ?? date('Y-m-d')));
        $civAccord  = (stripos($civ, 'mme') === 0 || stripos($civ, 'madame') === 0) ? 'Madame' : 'Monsieur';

        $pdf = new IpecCandidaturePdf('P', 'mm', 'A4');
        $pdf->docKind = 'document';
        $pdf->reference = $refDoc;
        $pdf->SetMargins(20, 20, 20);
        $pdf->SetAutoPageBreak(true, 30);
        $pdf->SetTitle($tr("Attestation de réussite d'année — IPEC"));
        $pdf->SetAuthor($tr("IPEC — Institut Privé des Études Commerciales"));
        $pdf->AddPage();

        ipec_doc_header($pdf, "ATTESTATION DE RÉUSSITE", $refDoc, $emis, $refCand);

        $pdf->SetFont('Helvetica', 'B', 11);
        $pdf->SetTextColor(15, 21, 37);
        $pdf->Cell(0, 6, $tr(trim($civ . ' ' . $prenom . ' ' . $nom)), 0, 1);
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->SetTextColor(91, 100, 120);
        if ($dateNaiss !== '') $pdf->Cell(0, 5, $tr('Né(e) le ' . $dateNaiss), 0, 1);
        $pdf->Ln(6);

        $pdf->SetFont('Helvetica', 'B', 12);
        $pdf->SetTextColor(27, 31, 42);
        $pdf->Cell(0, 7, $tr("Objet : Attestation de réussite d'année"), 0, 1);
        $pdf->Ln(2);

        $pdf->SetFont('Helvetica', '', 11);
        $p = function (string $t) use ($pdf, $tr) { $pdf->MultiCell(0, 5.5, $tr($t)); $pdf->Ln(1.5); };

        $detail = $programme . ($annee !== '' ? ' — ' . $annee : '')
                . ($specialisation !== '' && stripos($specialisation, 'sais') === false
                    ? ' (spécialisation : ' . $specialisation . ')' : '');

        $p("Je soussigné, le Directeur de l'Institut Privé des Études Commerciales (IPEC), atteste que "
            . $civAccord . " " . $prenom . " " . $nom
            . ($dateNaiss !== '' ? ", né(e) le " . $dateNaiss : '')
            . ", a validé avec succès l'année d'études suivante :");

        $pdf->SetFont('Helvetica', 'B', 11);
        $pdf->MultiCell(0, 5.5, $tr($detail !== '' ? $detail : 'Programme'));
        if ($anneeAcad !== '') {
            $pdf->SetFont('Helvetica', '', 11);
            $pdf->MultiCell(0, 5.5, $tr("Année académique : " . $anneeAcad));
        }
        $pdf->Ln(2);
        $pdf->SetFont('Helvetica', '', 11);

        $p("Tous les modules d'enseignement, examens et travaux requis pour la validation de cette année académique ont été obtenus."
            . ($anneeSuiv !== '' ? " L'étudiant(e) est autorisé(e) à poursuivre son cursus en " . $anneeSuiv . "." : ""));

        $p("La présente attestation est délivrée à titre officiel pour faire valoir ce que de droit.");

        $p("Fait à Bruxelles, le " . $emis . ".");

        $pdf->Ln(12);
        $pdf->SetFont('Helvetica', 'B', 11);
        $pdf->Cell(0, 6, $tr("La Direction"), 0, 1);
        $pdf->SetFont('Helvetica', 'I', 10);
        $pdf->SetTextColor(91, 100, 120);
        $pdf->Cell(0, 5, $tr("Institut Privé des Études Commerciales (IPEC)"), 0, 1);

        return (string)$pdf->Output('S');
    }
}

/* ============================================================
 * buildDiplomeBachelierPdf — fin de PAA-3 (Bachelier)
 * ============================================================ */
if (!function_exists('buildDiplomeBachelierPdf') && class_exists('IpecCandidaturePdf')) {
    function buildDiplomeBachelierPdf(array $data): string {
        $tr = function (string $s): string {
            $out = @iconv('UTF-8', 'CP1252//TRANSLIT//IGNORE', $s);
            return $out !== false ? $out : $s;
        };
        $fmt = function (?string $ymd): string {
            if (!$ymd) return '';
            $t = strtotime($ymd);
            return $t ? date('d/m/Y', $t) : (string)$ymd;
        };

        $civ        = trim((string)($data['civilite'] ?? ''));
        $prenom     = trim((string)($data['prenom'] ?? ''));
        $nom        = trim((string)($data['nom'] ?? ''));
        $dateNaiss  = $fmt((string)($data['date_naissance'] ?? ''));
        $specialisation = trim((string)($data['specialisation'] ?? ''));
        $anneeAcad  = trim((string)($data['annee_academique'] ?? ''));
        if (function_exists('ipec_academic_year_for')) { $anneeAcad = ipec_academic_year_for($anneeAcad); }
        $refDoc     = trim((string)($data['reference_doc'] ?? ''));
        $refCand    = trim((string)($data['candidature_reference'] ?? ''));
        $emis       = $fmt((string)($data['date_emission'] ?? date('Y-m-d')));

        $pdf = new IpecCandidaturePdf('P', 'mm', 'A4');
        $pdf->docKind = 'document';
        $pdf->reference = $refDoc;
        $pdf->SetMargins(20, 20, 20);
        $pdf->SetAutoPageBreak(true, 30);
        $pdf->SetTitle($tr("Diplôme de Bachelier — IPEC"));
        $pdf->SetAuthor($tr("IPEC — Institut Privé des Études Commerciales"));
        $pdf->AddPage();

        ipec_doc_header($pdf, "DIPLÔME", $refDoc, $emis, $refCand);

        $pdf->Ln(6);
        $pdf->SetFont('Times', 'B', 22);
        $pdf->SetTextColor(15, 21, 37);
        $pdf->Cell(0, 12, $tr("DIPLÔME DE BACHELIER"), 0, 1, 'C');
        $pdf->SetFont('Helvetica', '', 11);
        $pdf->SetTextColor(91, 100, 120);
        $pdf->Cell(0, 6, $tr("Programme d'Apprentissage Approfondi (PAA — BAC+3)"), 0, 1, 'C');
        $pdf->Ln(10);

        $pdf->SetFont('Helvetica', '', 12);
        $pdf->SetTextColor(27, 31, 42);
        $pdf->MultiCell(0, 6, $tr("Le Directeur de l'Institut Privé des Études Commerciales (IPEC) confère le présent diplôme à :"), 0, 'C');
        $pdf->Ln(4);

        $pdf->SetFont('Times', 'B', 18);
        $pdf->SetTextColor(15, 21, 37);
        $pdf->Cell(0, 10, $tr(trim($civ . ' ' . $prenom . ' ' . $nom)), 0, 1, 'C');

        if ($dateNaiss !== '') {
            $pdf->SetFont('Helvetica', '', 10);
            $pdf->SetTextColor(91, 100, 120);
            $pdf->Cell(0, 5, $tr('Né(e) le ' . $dateNaiss), 0, 1, 'C');
        }
        $pdf->Ln(6);

        $pdf->SetFont('Helvetica', '', 11);
        $pdf->SetTextColor(27, 31, 42);
        $pdf->MultiCell(0, 6, $tr("ayant satisfait à l'ensemble des épreuves, modules et travaux du cycle de Bachelier en Études Commerciales (BAC+3)"
            . ($specialisation !== '' && stripos($specialisation, 'sais') === false
                ? ", spécialisation : " . $specialisation : "")
            . ($anneeAcad !== '' ? ", au titre de l'année académique " . $anneeAcad : "")
            . "."), 0, 'C');
        $pdf->Ln(6);

        $pdf->MultiCell(0, 6, $tr("Le présent diplôme est délivré à Bruxelles, le " . $emis . ", pour valoir ce que de droit."), 0, 'C');

        $pdf->Ln(20);
        $pdf->SetFont('Helvetica', 'B', 11);
        $pdf->SetTextColor(15, 21, 37);
        $pdf->Cell(0, 6, $tr("La Direction"), 0, 1, 'C');
        $pdf->SetFont('Helvetica', 'I', 10);
        $pdf->SetTextColor(91, 100, 120);
        $pdf->Cell(0, 5, $tr("Institut Privé des Études Commerciales (IPEC)"), 0, 1, 'C');

        return (string)$pdf->Output('S');
    }
}

