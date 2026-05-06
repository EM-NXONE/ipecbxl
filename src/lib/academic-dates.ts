/**
 * Dates de rentrée et année académique — VALEURS CODÉES EN DUR.
 *
 * ⚠️  À METTRE À JOUR MANUELLEMENT CHAQUE ANNÉE.
 *     Modifier uniquement les constantes ci-dessous.
 */

// Année académique affichée partout (format AAAA-AAAA)
export const ACADEMIC_YEAR_LABEL = "2026-2027";

// Date de la rentrée principale (format jj/mm/aaaa)
export const SEPTEMBER_RENTREE_DATE = "14/09/2026";

// Date de la rentrée décalée (format jj/mm/aaaa)
export const FEBRUARY_RENTREE_DATE = "01/02/2027";

// ───────────────────────────────────────────────────────────────
// Helpers conservés pour compatibilité — ils renvoient désormais
// directement les chaînes ci-dessus (plus aucun calcul).
// ───────────────────────────────────────────────────────────────

export function getNextSeptemberRentree(): string {
  return SEPTEMBER_RENTREE_DATE;
}

export function getNextFebruaryRentree(): string {
  return FEBRUARY_RENTREE_DATE;
}

export function getUpcomingAcademicYearLabel(): string {
  return ACADEMIC_YEAR_LABEL;
}

/** No-op : la date est déjà formatée. Conservé pour compatibilité. */
export function formatRentreeDate(d: string): string {
  return d;
}

/** Année de début (utilisée historiquement). Dérivée du label "AAAA-AAAA". */
export function getCurrentAcademicYearStart(): number {
  return parseInt(ACADEMIC_YEAR_LABEL.slice(0, 4), 10);
}
