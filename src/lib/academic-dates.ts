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
  return `${d.getDate()} ${FR_MONTHS[d.getMonth()]} ${d.getFullYear()}`;
}

/**
 * Determines the "current" academic year start year based on today.
 * If we're already past the second Monday of September, the current academic year
 * has started; otherwise the next academic year is the upcoming one.
 */
export function getCurrentAcademicYearStart(today: Date = new Date()): number {
  const year = today.getFullYear();
  const septemberStart = nthMondayOf(year, 8, 2); // 8 = September
  // If we are still before the September rentrée, the current academic year started last September
  return today < septemberStart ? year - 1 : year;
}

/**
 * Returns the upcoming "rentrée principale" — second Monday of September.
 * If September's rentrée this year has already passed, returns next year's.
 */
export function getNextSeptemberRentree(today: Date = new Date()): Date {
  const year = today.getFullYear();
  const candidate = nthMondayOf(year, 8, 2);
  if (today <= candidate) return candidate;
  return nthMondayOf(year + 1, 8, 2);
}

/**
 * Returns the upcoming "rentrée décalée" — first Monday of February.
 * If February's rentrée this year has already passed, returns next year's.
 */
export function getNextFebruaryRentree(today: Date = new Date()): Date {
  const year = today.getFullYear();
  const candidate = nthMondayOf(year, 1, 1); // 1 = February
  if (today <= candidate) return candidate;
  return nthMondayOf(year + 1, 1, 1);
}

/**
 * Returns the academic year label for the upcoming September rentrée, e.g. "2026-2027".
 */
export function getUpcomingAcademicYearLabel(today: Date = new Date()): string {
  const sept = getNextSeptemberRentree(today);
  const startYear = sept.getFullYear();
  return `${startYear}-${startYear + 1}`;
}

export const formatRentreeDate = formatFrenchDate;
