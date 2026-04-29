/**
 * /etudiant/factures — liste complète des factures + KPIs + téléchargement PDF.
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
  id: number; numero: string; type: string; libelle: string;
  montant_ttc_cents: number; devise: string; statut_paiement: string;
  date_emission: string; date_echeance: string | null;
}
interface Resp { factures: Facture[]; kpis: { total_du_cents: number; total_paye_cents: number; count: number } }

function EtudiantFacturesPage() {
  const [data, setData] = useState<Resp | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    etuApi.get<Resp>("/factures.php").then(setData).catch((e) => setError(e.message));
  }, []);

  return (
    <div>
      <h1 className="font-display text-3xl text-cream mb-2">Mes factures</h1>
      <p className="text-sm text-muted-foreground mb-8">Toutes les factures émises par l'IPEC à ton nom.</p>

      {error && <div className="mb-6 px-4 py-3 rounded-sm bg-destructive/10 border border-destructive/30 text-sm text-destructive">{error}</div>}

      <div className="grid grid-cols-3 gap-4 mb-8">
        <Kpi label="Total dû" value={data ? formatMoneyCents(data.kpis.total_du_cents) : "—"} />
        <Kpi label="Total payé" value={data ? formatMoneyCents(data.kpis.total_paye_cents) : "—"} />
        <Kpi label="Nombre" value={data ? String(data.kpis.count) : "—"} />
      </div>

      <div className="bg-card border border-border/40 rounded-md overflow-hidden">
        {!data ? (
          <div className="p-8 text-sm text-muted-foreground">Chargement…</div>
        ) : data.factures.length === 0 ? (
          <div className="p-8 text-sm text-muted-foreground">Aucune facture pour l'instant.</div>
        ) : (
          <div className="overflow-x-auto">
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
                  return (
                    <tr key={f.id} className="hover:bg-secondary/20">
                      <td className="px-4 py-3 font-mono text-xs text-cream">{f.numero}</td>
                      <td className="px-4 py-3 text-cream">{f.libelle}</td>
                      <td className="px-4 py-3 text-muted-foreground">{formatDate(f.date_emission)}</td>
                      <td className="px-4 py-3 text-right font-medium text-cream">{formatMoneyCents(f.montant_ttc_cents, f.devise)}</td>
                      <td className="px-4 py-3"><Badge tone={s.tone}>{s.label}</Badge></td>
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
