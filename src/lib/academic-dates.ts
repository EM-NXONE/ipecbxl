// Helpers to compute the next academic year's key dates.
// "Année académique" runs from September of year N to summer of year N+1.

const FR_MONTHS = [
  "janvier", "février", "mars", "avril", "mai", "juin",
  "juillet", "août", "septembre", "octobre", "novembre", "décembre",
];

/**
 * Returns the date of the Nth Monday of the given month (0-indexed) in the given year.
 * occurrence: 1 for first Monday, 2 for second, etc.
 */
function nthMondayOf(year: number, monthIndex: number, occurrence: number): Date {
  const firstOfMonth = new Date(year, monthIndex, 1);
  const dayOfWeek = firstOfMonth.getDay(); // 0 = Sunday, 1 = Monday, ...
  const offsetToFirstMonday = (8 - dayOfWeek) % 7; // days to add to reach first Monday
  const day = 1 + offsetToFirstMonday + (occurrence - 1) * 7;
  return new Date(year, monthIndex, day);
}

function formatFrenchDate(d: Date): string {
  const dd = String(d.getDate()).padStart(2, "0");
  const mm = String(d.getMonth() + 1).padStart(2, "0");
  return `${dd}/${mm}/${d.getFullYear()}`;
}

/**
 * Determines the "current" academic year start year based on today.
 * Bascule : le lendemain de la rentrée décalée (1er lundi de février),
 * on considère que l'année académique suivante est désormais "en cours"
 * pour les besoins d'affichage et des formulaires d'admission.
 *
 * Règle :
 *  - jusqu'au lundi de la rentrée décalée (inclus) de l'année N → année N-1 / N
 *  - à partir du mardi suivant → année N / N+1
 */
export function getCurrentAcademicYearStart(today: Date = new Date()): number {
  const year = today.getFullYear();
  const februaryRentree = nthMondayOf(year, 1, 1); // 1er lundi de février année courante
  // Date de bascule = lendemain de la rentrée décalée
  const switchDate = new Date(
    februaryRentree.getFullYear(),
    februaryRentree.getMonth(),
    februaryRentree.getDate() + 1,
  );
  return today < switchDate ? year - 1 : year;
}

/**
 * Returns the upcoming "rentrée principale" — second Monday of September.
 * Bascule le lendemain de la rentrée décalée : on cible alors le septembre suivant.
 */
export function getNextSeptemberRentree(today: Date = new Date()): Date {
  const startYear = getCurrentAcademicYearStart(today);
  return nthMondayOf(startYear, 8, 2); // septembre = mois 8
}

/**
 * Returns the upcoming "rentrée décalée" — first Monday of February.
 * Bascule le lendemain de la rentrée décalée : on cible alors le février suivant.
 */
export function getNextFebruaryRentree(today: Date = new Date()): Date {
  const startYear = getCurrentAcademicYearStart(today);
  return nthMondayOf(startYear + 1, 1, 1); // février de l'année suivant la rentrée de septembre
}

/**
 * Returns the academic year label for the upcoming September rentrée, e.g. "2026-2027".
 */
export function getUpcomingAcademicYearLabel(today: Date = new Date()): string {
  const startYear = getCurrentAcademicYearStart(today);
  return `${startYear}-${startYear + 1}`;
}

export const formatRentreeDate = formatFrenchDate;
