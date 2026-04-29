/**
 * /etudiant/factures — liste complète des factures + KPIs + téléchargement PDF.
 * Affiche le statut de paiement avec, le cas échéant, la date de paiement,
 * le moyen et la référence encodés par l'admin.
 */
import { createFileRoute } from "@tanstack/react-router";
import { useEffect, useState } from "react";
import { Download } from "lucide-react";
import { etuApi, etuUrl } from "@/lib/api";
import { formatMoneyCents, formatDate, FACTURE_STATUTS } from "@/lib/format";

export const Route = createFileRoute("/etudiant/_authenticated/factures")({
  component: EtudiantFacturesPage,
});

interface Facture {
  id: number;
  numero: string;
  type: string;
  libelle: string;
  description?: string | null;
  montant_ht_cents?: number;
  tva_taux?: string | number | null;
  montant_ttc_cents: number;
  devise: string;
  date_emission: string;
  date_echeance: string | null;
  statut_paiement: string;
  paye_at?: string | null;
  moyen_paiement?: string | null;
  reference_paiement?: string | null;
}

interface Resp {
  factures: Facture[];
  kpis?: { total_du_cents: number; total_paye_cents: number; count: number };
  // Rétro-compat ancien shape
  totaux?: { du_cents: number; paye_cents: number; count: number };
}

const MOYEN_LABELS: Record<string, string> = {
  virement: "Virement bancaire",
  cb: "Carte bancaire",
  carte: "Carte bancaire",
  especes: "Espèces",
  cheque: "Chèque",
  autre: "Autre",
};

function moyenLabel(m?: string | null): string {
  if (!m) return "";
  const key = m.toLowerCase();
  return MOYEN_LABELS[key] ?? m;
}

/** Texte secondaire "Payée le X par virement (réf …)" si applicable. */
function paiementInfo(f: Facture): string | null {
  if (f.statut_paiement === "payee" || f.statut_paiement === "partiellement_payee") {
    const parts: string[] = [];
    if (f.paye_at) parts.push(`Payée le ${formatDate(f.paye_at)}`);
    const m = moyenLabel(f.moyen_paiement);
    if (m) parts.push(`par ${m}`);
    if (f.reference_paiement) parts.push(`réf. ${f.reference_paiement}`);
    return parts.length ? parts.join(" · ") : null;
  }
  if (f.statut_paiement === "en_attente" && f.date_echeance) {
    return `Échéance ${formatDate(f.date_echeance)}`;
  }
  return null;
}

function EtudiantFacturesPage() {
  const [data, setData] = useState<Resp | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    etuApi.get<Resp>("/factures.php").then(setData).catch((e) => setError(e.message));
  }, []);

  // Tolérance : accepte le nouveau shape `kpis` ou l'ancien `totaux`.
  const totalDu = data?.kpis?.total_du_cents ?? data?.totaux?.du_cents;
  const totalPaye = data?.kpis?.total_paye_cents ?? data?.totaux?.paye_cents;
  const count = data?.kpis?.count ?? data?.totaux?.count ?? data?.factures.length;

  return (
    <div>
      <h1 className="font-display text-3xl text-cream mb-2">Mes factures</h1>
      <p className="text-sm text-muted-foreground mb-8">Toutes les factures émises par l'IPEC à ton nom.</p>

      {error && <div className="mb-6 px-4 py-3 rounded-sm bg-destructive/10 border border-destructive/30 text-sm text-destructive">{error}</div>}

      <div className="grid grid-cols-3 gap-4 mb-8">
        <Kpi label="Total dû" value={totalDu !== undefined ? formatMoneyCents(totalDu) : "—"} />
        <Kpi label="Total payé" value={totalPaye !== undefined ? formatMoneyCents(totalPaye) : "—"} />
        <Kpi label="Nombre" value={count !== undefined ? String(count) : "—"} />
      </div>

      <div className="bg-card border border-border/40 rounded-md overflow-hidden">
        {!data ? (
          <div className="p-8 text-sm text-muted-foreground">Chargement…</div>
        ) : data.factures.length === 0 ? (
          <div className="p-8 text-sm text-muted-foreground">Aucune facture pour l'instant.</div>
        ) : (
          <>
            {/* Tableau (desktop) */}
            <div className="hidden md:block overflow-x-auto">
              <table className="w-full text-sm">
                <thead className="bg-secondary/30 text-xs uppercase tracking-wider text-muted-foreground">
                  <tr>
                    <th className="text-left px-4 py-3">Numéro</th>
                    <th className="text-left px-4 py-3">Libellé</th>
                    <th className="text-left px-4 py-3">Émise le</th>
                    <th className="text-right px-4 py-3">Montant</th>
                    <th className="text-left px-4 py-3">Statut</th>
                    <th className="px-4 py-3"></th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-border/30">
                  {data.factures.map((f) => {
                    const s = FACTURE_STATUTS[f.statut_paiement] ?? { label: f.statut_paiement, tone: "muted" as const };
                    const info = paiementInfo(f);
                    return (
                      <tr key={f.id} className="hover:bg-secondary/20 align-top">
                        <td className="px-4 py-3 font-mono text-xs text-cream whitespace-nowrap">{f.numero}</td>
                        <td className="px-4 py-3 text-cream">
                          <div>{f.libelle}</div>
                          {f.description && (
                            <div className="text-xs text-muted-foreground mt-0.5">{f.description}</div>
                          )}
                        </td>
                        <td className="px-4 py-3 text-muted-foreground whitespace-nowrap">{formatDate(f.date_emission)}</td>
                        <td className="px-4 py-3 text-right font-medium text-cream whitespace-nowrap">{formatMoneyCents(f.montant_ttc_cents, f.devise)}</td>
                        <td className="px-4 py-3">
                          <Badge tone={s.tone}>{s.label}</Badge>
                          {info && (
                            <div className="text-xs text-muted-foreground mt-1">{info}</div>
                          )}
                        </td>
                        <td className="px-4 py-3 text-right">
                          <a
                            href={etuUrl(`/telecharger.php?type=facture&id=${f.id}`)}
                            className="inline-flex items-center gap-1.5 text-xs text-blue hover:underline"
                          >
                            <Download size={12} /> PDF
                          </a>
                        </td>
                      </tr>
                    );
                  })}
                </tbody>
              </table>
            </div>

            {/* Cartes (mobile) */}
            <ul className="md:hidden divide-y divide-border/30">
              {data.factures.map((f) => {
                const s = FACTURE_STATUTS[f.statut_paiement] ?? { label: f.statut_paiement, tone: "muted" as const };
                const info = paiementInfo(f);
                return (
                  <li key={f.id} className="p-4 space-y-2">
                    <div className="flex items-start justify-between gap-3">
                      <div className="min-w-0">
                        <div className="text-cream font-medium truncate">{f.libelle}</div>
                        <div className="font-mono text-[11px] text-muted-foreground">{f.numero}</div>
                      </div>
                      <div className="text-right shrink-0">
                        <div className="text-cream font-medium">{formatMoneyCents(f.montant_ttc_cents, f.devise)}</div>
                        <div className="text-[11px] text-muted-foreground">{formatDate(f.date_emission)}</div>
                      </div>
                    </div>
                    <div className="flex items-center justify-between gap-3">
                      <div>
                        <Badge tone={s.tone}>{s.label}</Badge>
                        {info && <div className="text-[11px] text-muted-foreground mt-1">{info}</div>}
                      </div>
                      <a
                        href={etuUrl(`/telecharger.php?type=facture&id=${f.id}`)}
                        className="inline-flex items-center gap-1.5 text-xs text-blue hover:underline shrink-0"
                      >
                        <Download size={12} /> PDF
                      </a>
                    </div>
                  </li>
                );
              })}
            </ul>
          </>
        )}
      </div>
    </div>
  );
}

function Kpi({ label, value }: { label: string; value: string }) {
  return (
    <div className="bg-card border border-border/40 rounded-md p-4">
      <div className="text-xs uppercase tracking-wider text-muted-foreground mb-2">{label}</div>
      <div className="font-display text-2xl text-cream">{value}</div>
    </div>
  );
}
function Badge({ children, tone }: { children: React.ReactNode; tone: "warn" | "ok" | "muted" }) {
  const cls = tone === "ok" ? "bg-green-500/10 text-green-400 border-green-500/30"
    : tone === "warn" ? "bg-amber-500/10 text-amber-300 border-amber-500/30"
    : "bg-muted/30 text-muted-foreground border-border/40";
  return <span className={`inline-block px-2 py-0.5 text-[10px] uppercase tracking-wider rounded-sm border ${cls}`}>{children}</span>;
}
