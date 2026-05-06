/**
 * Dates de rentrée et année académique — VALEURS CODÉES EN DUR.
 *
 * ⚠️ À METTRE À JOUR MANUELLEMENT CHAQUE ANNÉE.
 *    Modifier uniquement les constantes ci-dessous, ET la copie côté
 *    PHP : public/_academic_dates.php (mêmes valeurs).
 */

// Année académique affichée partout (format AAAA-AAAA)
export const ACADEMIC_YEAR_LABEL = "2026-2027";

// Date de la rentrée principale (format jj/mm/aaaa)
export const SEPTEMBER_RENTREE_DATE = "14/09/2026";

// Date de la rentrée décalée (format jj/mm/aaaa)
export const FEBRUARY_RENTREE_DATE = "01/02/2027";

// Libellés symboliques utilisés dans les formulaires/BDD.
// On stocke ces libellés en BDD (candidatures.rentree) plutôt que des dates,
// pour qu'aucune date n'apparaisse dans les documents/UI.
export const RENTREE_PRINCIPALE_LABEL = "Rentrée principale";
export const RENTREE_DECALEE_LABEL = "Rentrée décalée";

// ───────────────────────────────────────────────────────────────
// Helpers (compat) — ne renvoient désormais que les libellés
// symboliques, plus jamais de dates.
// ───────────────────────────────────────────────────────────────

export function getNextSeptemberRentree(): string {
  return RENTREE_PRINCIPALE_LABEL;
}

export function getNextFebruaryRentree(): string {
  return RENTREE_DECALEE_LABEL;
}

export function getUpcomingAcademicYearLabel(): string {
  return ACADEMIC_YEAR_LABEL;
}

/** No-op : on renvoie tel quel. Conservé pour compatibilité. */
export function formatRentreeDate(d: string): string {
  return d;
}

/** Année de début (utilisée historiquement). Dérivée du label "AAAA-AAAA". */
export function getCurrentAcademicYearStart(): number {
  return parseInt(ACADEMIC_YEAR_LABEL.slice(0, 4), 10);
}

/**
 * Normalise un libellé d'année académique stocké en BDD.
 * Accepte "2026-2027", "2026/2027", null/"" → "Année non précisée".
 */
export function normalizeAcademicYear(s: string | null | undefined): string {
  const v = (s || "").trim();
  if (!v) return "Année non précisée";
  return v.replace("/", "-");
}

