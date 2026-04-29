/**
 * /etudiant — tableau de bord avec KPIs et derniers éléments.
 */
import { createFileRoute, Link } from "@tanstack/react-router";
import { useEffect, useState } from "react";
import { Receipt, FolderOpen, Wallet, CheckCircle2 } from "lucide-react";
import { useEtudiantAuth } from "@/lib/auth-etudiant";
import { etuApi } from "@/lib/api";
import { formatMoneyCents, formatDate, FACTURE_STATUTS } from "@/lib/format";

export const Route = createFileRoute("/etudiant/_authenticated/")({
  component: EtudiantDashboardPage,
});

interface Dashboard {
  kpis: { total_du_cents: number; total_paye_cents: number; nb_factures: number; nb_documents: number };
  last_factures: Array<{ id: number; numero: string; libelle: string; montant_ttc_cents: number; statut_paiement: string; date_emission: string }>;
  last_documents: Array<{ id: number; reference: string; type: string; titre: string; date_emission: string }>;
}

function EtudiantDashboardPage() {
  const { user } = useEtudiantAuth();
  const [data, setData] = useState<Dashboard | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    etuApi.get<Dashboard>("/dashboard.php").then(setData).catch((e) => setError(e.message));
  }, []);

  return (
    <div>
      <h1 className="font-display text-3xl text-cream mb-2">Bonjour {user?.prenom}.</h1>
      <p className="text-sm text-muted-foreground mb-8">Voici l'état de ton dossier IPEC.</p>

      {error && <div className="mb-6 px-4 py-3 rounded-sm bg-destructive/10 border border-destructive/30 text-sm text-destructive">{error}</div>}

      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <Kpi icon={<Wallet size={18} />} label="Total dû" value={data ? formatMoneyCents(data.kpis.total_du_cents) : "—"} />
        <Kpi icon={<CheckCircle2 size={18} />} label="Total payé" value={data ? formatMoneyCents(data.kpis.total_paye_cents) : "—"} />
        <Kpi icon={<Receipt size={18} />} label="Factures" value={data ? String(data.kpis.nb_factures) : "—"} />
        <Kpi icon={<FolderOpen size={18} />} label="Documents" value={data ? String(data.kpis.nb_documents) : "—"} />
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <Card title="Dernières factures" linkTo="/etudiant/factures" linkLabel="Voir tout">
          {data?.last_factures.length ? (
            <ul className="divide-y divide-border/30">
              {data.last_factures.map((f) => {
                const s = FACTURE_STATUTS[f.statut_paiement] ?? { label: f.statut_paiement, tone: "muted" as const };
                return (
                  <li key={f.id} className="py-2.5 flex items-center justify-between gap-3 text-sm">
                    <div className="min-w-0">
                      <div className="text-cream truncate">{f.libelle}</div>
                      <div className="text-xs text-muted-foreground font-mono">{f.numero} · {formatDate(f.date_emission)}</div>
                    </div>
                    <div className="text-right shrink-0">
                      <div className="text-cream font-medium">{formatMoneyCents(f.montant_ttc_cents)}</div>
                      <Badge tone={s.tone}>{s.label}</Badge>
                    </div>
                  </li>
                );
              })}
            </ul>
          ) : (<p className="text-sm text-muted-foreground">Aucune facture.</p>)}
        </Card>

        <Card title="Derniers documents" linkTo="/etudiant/documents" linkLabel="Voir tout">
          {data?.last_documents.length ? (
            <ul className="divide-y divide-border/30">
              {data.last_documents.map((d) => (
                <li key={d.id} className="py-2.5 text-sm">
                  <div className="text-cream truncate">{d.titre}</div>
                  <div className="text-xs text-muted-foreground font-mono">{d.reference} · {formatDate(d.date_emission)}</div>
                </li>
              ))}
            </ul>
          ) : (<p className="text-sm text-muted-foreground">Aucun document.</p>)}
        </Card>
      </div>
    </div>
  );
}

function Kpi({ icon, label, value }: { icon: React.ReactNode; label: string; value: string }) {
  return (
    <div className="bg-card border border-border/40 rounded-md p-4">
      <div className="flex items-center gap-2 text-xs uppercase tracking-wider text-muted-foreground mb-2">{icon}{label}</div>
      <div className="font-display text-2xl text-cream">{value}</div>
    </div>
  );
}

function Card({ title, children, linkTo, linkLabel }: { title: string; children: React.ReactNode; linkTo?: string; linkLabel?: string }) {
  return (
    <div className="bg-card border border-border/40 rounded-md p-5">
      <div className="flex items-center justify-between mb-3">
        <h2 className="font-display text-lg text-cream">{title}</h2>
        {linkTo && <Link to={linkTo} className="text-xs text-blue hover:underline">{linkLabel}</Link>}
      </div>
      {children}
    </div>
  );
}

function Badge({ children, tone }: { children: React.ReactNode; tone: "warn" | "ok" | "muted" }) {
  const cls = tone === "ok" ? "bg-green-500/10 text-green-400 border-green-500/30"
    : tone === "warn" ? "bg-amber-500/10 text-amber-300 border-amber-500/30"
    : "bg-muted/30 text-muted-foreground border-border/40";
  return <span className={`inline-block px-2 py-0.5 text-[10px] uppercase tracking-wider rounded-sm border ${cls}`}>{children}</span>;
}
