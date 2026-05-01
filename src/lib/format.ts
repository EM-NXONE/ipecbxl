/** Formatage centralisé pour les portails. */
export function formatMoneyCents(cents: number, devise = "EUR"): string {
  return new Intl.NumberFormat("fr-BE", { style: "currency", currency: devise }).format((cents || 0) / 100);
}

export function formatDate(iso?: string | null): string {
  if (!iso) return "—";
  const d = new Date(iso);
  if (isNaN(d.getTime())) return iso;
  return d.toLocaleDateString("fr-BE", { day: "2-digit", month: "2-digit", year: "numeric" });
}

export function formatDateTime(iso?: string | null): string {
  if (!iso) return "—";
  const d = new Date(iso);
  if (isNaN(d.getTime())) return iso;
  return d.toLocaleString("fr-BE", { day: "2-digit", month: "2-digit", year: "numeric", hour: "2-digit", minute: "2-digit" });
}

export const FACTURE_STATUTS: Record<string, { label: string; tone: "warn" | "ok" | "muted" }> = {
  en_attente:          { label: "En attente", tone: "warn" },
  partiellement_payee: { label: "Partiel",    tone: "warn" },
  payee:               { label: "Payée",      tone: "ok" },
  annulee:             { label: "Annulée",    tone: "muted" },
  remboursee:          { label: "Remboursée", tone: "muted" },
};

export const CANDIDATURE_STATUTS: Record<
  string,
  { label: string; tone: "info" | "warn" | "ok" | "danger" | "muted"; description: string; step: number }
> = {
  recue:    { label: "Reçue",            tone: "info",   step: 1, description: "Votre candidature a bien été reçue. Elle entrera bientôt en étude." },
  en_cours: { label: "En cours d'étude", tone: "warn",   step: 2, description: "Le jury d'admission étudie actuellement votre dossier." },
  validee:  { label: "Validée",          tone: "ok",     step: 3, description: "Félicitations, votre candidature a été validée par l'IPEC." },
  refusee:  { label: "Refusée",          tone: "danger", step: 3, description: "Votre candidature n'a pas été retenue. Vous recevrez un courrier détaillé." },
  annulee:  { label: "Annulée",          tone: "muted",  step: 0, description: "Cette candidature a été annulée." },
};

export const CANDIDATURE_STEPS: Array<{ key: string; label: string }> = [
  { key: "recue",    label: "Reçue" },
  { key: "en_cours", label: "En étude" },
  { key: "validee",  label: "Décision" },
];
