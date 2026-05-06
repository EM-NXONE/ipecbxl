<?php
/**
 * IPEC — Dates de rentrée et année académique (CODÉES EN DUR).
 *
 * ⚠️ MODIFIER CHAQUE ANNÉE — copie côté PHP des valeurs de
 *    src/lib/academic-dates.ts. Garder les deux fichiers synchronisés.
 *
 *  - IPEC_ACADEMIC_YEAR_LABEL : "AAAA-AAAA" affiché dans tous les PDFs/Email.
 *  - IPEC_RENTREE_PRINCIPALE_DATE  : date jj/mm/aaaa de la rentrée principale.
 *  - IPEC_RENTREE_DECALEE_DATE     : date jj/mm/aaaa de la rentrée décalée.
 *
 * Les libellés stockés en BDD (candidatures.rentree) sont volontairement
 * symboliques : "Rentrée principale" ou "Rentrée décalée". Les helpers
 * ci-dessous résolvent vers la date réelle pour le calcul des échéances.
 */

const IPEC_ACADEMIC_YEAR_LABEL       = '2026-2027';
const IPEC_RENTREE_PRINCIPALE_DATE   = '14/09/2026';
const IPEC_RENTREE_DECALEE_DATE      = '01/02/2027';

const IPEC_RENTREE_PRINCIPALE_LABEL  = 'Rentrée principale';
const IPEC_RENTREE_DECALEE_LABEL     = 'Rentrée décalée';

if (!function_exists('ipec_rentree_is_decalee')) {
    /** Vrai si le libellé désigne la rentrée décalée (février). */
    function ipec_rentree_is_decalee(?string $label): bool {
        $s = (string)$label;
        if ($s === '') return false;
        if (stripos($s, 'décal') !== false || stripos($s, 'decal') !== false) return true;
        // Compatibilité historique : anciens libellés "Février — JJ/MM/AAAA"
        if (preg_match('/f[ée]vrier|janvier|mars|avril|mai|juin|juillet|ao[ûu]t/i', $s)) return true;
        return false;
    }
}

if (!function_exists('ipec_rentree_label_normalized')) {
    /** Renvoie un libellé standard "Rentrée principale" ou "Rentrée décalée". */
    function ipec_rentree_label_normalized(?string $label): string {
        return ipec_rentree_is_decalee($label) ? IPEC_RENTREE_DECALEE_LABEL : IPEC_RENTREE_PRINCIPALE_LABEL;
    }
}

if (!function_exists('ipec_rentree_date_for')) {
    /** Renvoie la date jj/mm/aaaa de la rentrée correspondante. */
    function ipec_rentree_date_for(?string $label): string {
        return ipec_rentree_is_decalee($label) ? IPEC_RENTREE_DECALEE_DATE : IPEC_RENTREE_PRINCIPALE_DATE;
    }
}

if (!function_exists('ipec_academic_year_for')) {
    /**
     * Renvoie l'année académique à utiliser :
     *   - si fournie explicitement (annee_academique du dossier), on la garde ;
     *   - sinon on retombe sur la constante IPEC_ACADEMIC_YEAR_LABEL.
     * Tolère les anciens formats "AAAA/AAAA" et les normalise en "AAAA-AAAA".
     */
    function ipec_academic_year_for(?string $stored): string {
        $s = trim((string)$stored);
        if ($s !== '') {
            return str_replace('/', '-', $s);
        }
        return IPEC_ACADEMIC_YEAR_LABEL;
    }
}
