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
