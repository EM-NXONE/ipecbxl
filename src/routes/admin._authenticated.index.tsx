/**
 * /admin — tableau de bord administrateur.
 * KPIs + 5 dernières candidatures.
 */
import { createFileRoute, Link } from "@tanstack/react-router";
import { useEffect, useState } from "react";
import { AdminCandidatureActions } from "@/components/AdminCandidatureActions";
import { adminApi } from "@/lib/api";
import { formatDateTime } from "@/lib/format";

export const Route = createFileRoute("/admin/_authenticated/")({
  component: AdminDashboardPage,
});

interface Kpis {
  total: number;
  recue: number;
  en_cours: number;
  validee: number;
  payees: number;
  non_payees: number;
  recent_7j: number;
  etudiants: number;
}
interface LastCandidature {
  id: number;
  reference: string;
  prenom: string;
  nom: string;
  email: string;
  statut: string;
  programme: string | null;
  facture_payee: number | boolean;
  etudiant_id: number | null;
  created_at: string;
}
interface DashboardData {
  kpis: Kpis;
  last_candidatures: LastCandidature[];
}

function AdminDashboardPage() {
  const [data, setData] = useState<DashboardData | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [msg, setMsg] = useState<string | null>(null);
  const [refreshKey, setRefreshKey] = useState(0);

  useEffect(() => {
    adminApi.get<DashboardData>("/dashboard.php").then(setData).catch((e) => setError(e.message));
  }, [refreshKey]);

  return (
    <div>
      <h1 className="font-display text-3xl text-cream mb-2">Tableau de bord</h1>
      <p className="text-sm text-muted-foreground mb-8">Vue d'ensemble des candidatures et étudiants.</p>

      {error && (
        <div className="mb-6 px-4 py-3 rounded-sm bg-destructive/10 border border-destructive/30 text-sm text-destructive">
          {error}
        </div>
      )}
      {msg && (
        <div className="mb-6 px-4 py-3 rounded-sm bg-emerald-500/10 border border-emerald-500/30 text-sm text-emerald-400">
          {msg}
        </div>
      )}

      {!data && !error && <p className="text-muted-foreground text-sm">Chargement…</p>}

      {data && (
        <>
          <div className="grid grid-cols-2 md:grid-cols-4 gap-3 mb-8">
            <Kpi label="Candidatures" value={data.kpis.total} />
            <Kpi label="7 derniers jours" value={data.kpis.recent_7j} accent />
            <Kpi label="Reçues" value={data.kpis.recue} />
            <Kpi label="En cours" value={data.kpis.en_cours} />
            <Kpi label="Validées" value={data.kpis.validee} />
            <Kpi label="Frais payés" value={data.kpis.payees} />
            <Kpi label="Frais en attente" value={data.kpis.non_payees} />
            <Kpi label="Étudiants" value={data.kpis.etudiants} />
          </div>

          <section>
            <div className="flex items-center justify-between mb-3">
              <h2 className="font-display text-xl text-cream">Dernières candidatures</h2>
              <Link to="/admin/candidatures" className="text-xs text-blue hover:underline">
                Voir toutes →
              </Link>
            </div>
            <div className="bg-card border border-border/40 rounded-md overflow-hidden">
              <table className="w-full text-sm">
                <thead className="text-xs uppercase tracking-wider text-muted-foreground border-b border-border/40">
                  <tr>
                    <th className="text-left px-4 py-2.5">Réf.</th>
                    <th className="text-left px-4 py-2.5">Candidat</th>
                    <th className="text-left px-4 py-2.5 hidden md:table-cell">Programme</th>
                    <th className="text-left px-4 py-2.5">Statut</th>
                    <th className="text-left px-4 py-2.5 hidden sm:table-cell">Frais</th>
                    <th className="text-left px-4 py-2.5 hidden lg:table-cell">Reçue le</th>
                    <th className="text-left px-4 py-2.5">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {data.last_candidatures.map((c) => (
                    <tr key={c.id} className="border-b border-border/20 hover:bg-secondary/30">
                      <td className="px-4 py-2.5">
                        <Link to="/admin/candidatures/$id" params={{ id: String(c.id) }} className="text-blue hover:underline font-mono text-xs">
                          {c.reference}
                        </Link>
                      </td>
                      <td className="px-4 py-2.5 text-cream">
                        {c.prenom} {c.nom}
                        <div className="text-xs text-muted-foreground">{c.email}</div>
                      </td>
                      <td className="px-4 py-2.5 hidden md:table-cell text-muted-foreground">{c.programme || "—"}</td>
                      <td className="px-4 py-2.5"><StatusBadge value={c.statut} /></td>
                      <td className="px-4 py-2.5 hidden sm:table-cell">
                        {Number(c.facture_payee) ? (
                          <span className="text-xs text-emerald-400">Payés</span>
                        ) : (
                          <span className="text-xs text-amber-400">En attente</span>
                        )}
                      </td>
                      <td className="px-4 py-2.5 hidden lg:table-cell text-muted-foreground text-xs">{formatDateTime(c.created_at)}</td>
                      <td className="px-4 py-2.5">
                        <AdminCandidatureActions
                          id={c.id}
                          paid={c.facture_payee}
                          hasEtudiant={Boolean(c.etudiant_id)}
                          compact
                          onDone={(res) => {
                            setError(null);
                            setMsg(res.message || "Action effectuée.");
                            setRefreshKey((v) => v + 1);
                          }}
                          onError={(message) => { setMsg(null); setError(message); }}
                        />
                      </td>
                    </tr>
                  ))}
                  {data.last_candidatures.length === 0 && (
                    <tr><td colSpan={7} className="px-4 py-6 text-center text-muted-foreground text-sm">Aucune candidature pour le moment.</td></tr>
                  )}
                </tbody>
              </table>
            </div>
          </section>
        </>
      )}
    </div>
  );
}

function Kpi({ label, value, accent }: { label: string; value: number; accent?: boolean }) {
  return (
    <div className={`bg-card border rounded-md p-4 ${accent ? "border-blue/40" : "border-border/40"}`}>
      <div className="text-xs uppercase tracking-wider text-muted-foreground mb-1">{label}</div>
      <div className={`font-display text-2xl ${accent ? "text-blue" : "text-cream"}`}>{value}</div>
    </div>
  );
}

export function StatusBadge({ value }: { value: string }) {
  const map: Record<string, { label: string; tone: string }> = {
    recue:    { label: "Reçue",    tone: "bg-blue/10 text-blue border-blue/30" },
    en_cours: { label: "En cours", tone: "bg-amber-500/10 text-amber-400 border-amber-500/30" },
    validee:  { label: "Validée",  tone: "bg-emerald-500/10 text-emerald-400 border-emerald-500/30" },
    refusee:  { label: "Refusée",  tone: "bg-destructive/10 text-destructive border-destructive/30" },
    archivee: { label: "Archivée", tone: "bg-muted text-muted-foreground border-border" },
  };
  const s = map[value] || { label: value, tone: "bg-muted text-muted-foreground border-border" };
  return <span className={`inline-block px-2 py-0.5 rounded-sm border text-xs ${s.tone}`}>{s.label}</span>;
}
