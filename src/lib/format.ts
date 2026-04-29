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
