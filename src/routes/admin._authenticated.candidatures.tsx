/**
 * /admin/candidatures — liste filtrable + paginée.
 */
import { createFileRoute, Link } from "@tanstack/react-router";
import { useEffect, useState } from "react";
import { Search } from "lucide-react";
import { AdminCandidatureActions, adminActionMessage } from "@/components/AdminCandidatureActions";
import { adminApi } from "@/lib/api";
import { formatDateTime } from "@/lib/format";
import { StatusBadge } from "./admin._authenticated.index";

export const Route = createFileRoute("/admin/_authenticated/candidatures")({
  component: AdminCandidaturesListPage,
});

interface Cand {
  id: number;
  reference: string;
  statut: string;
  prenom: string;
  nom: string;
  email: string;
  programme: string | null;
  annee: string | null;
  annee_academique: string | null;
  facture_numero: string | null;
  facture_payee: number | boolean;
  facture_payee_at: string | null;
  etudiant_id: number | null;
  created_at: string;
}
interface ListResp {
  candidatures: Cand[];
  total: number;
  page: number;
  perPage: number;
  pages: number;
  statuts: Record<string, string>;
}

function AdminCandidaturesListPage() {
  const [q, setQ] = useState("");
  const [statut, setStatut] = useState("");
  const [payee, setPayee] = useState("");
  const [page, setPage] = useState(1);
  const [data, setData] = useState<ListResp | null>(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [msg, setMsg] = useState<string | null>(null);
  const [refreshKey, setRefreshKey] = useState(0);

  useEffect(() => {
    setLoading(true);
    setError(null);
    const params = new URLSearchParams();
    if (q) params.set("q", q);
    if (statut) params.set("statut", statut);
    if (payee) params.set("payee", payee);
    params.set("page", String(page));
    params.set("perPage", "30");
    const t = setTimeout(() => {
      adminApi.get<ListResp>(`/candidatures.php?${params}`)
        .then(setData)
        .catch((e) => setError(e.message))
        .finally(() => setLoading(false));
    }, q ? 250 : 0);
    return () => clearTimeout(t);
  }, [q, statut, payee, page, refreshKey]);

  return (
    <div>
      <h1 className="font-display text-3xl text-cream mb-2">Candidatures</h1>
      <p className="text-sm text-muted-foreground mb-6">
        {data ? `${data.total} candidature${data.total > 1 ? "s" : ""} au total.` : "Chargement…"}
      </p>

      <div className="bg-card border border-border/40 rounded-md p-4 mb-4 grid sm:grid-cols-[1fr_auto_auto] gap-3">
        <div className="relative">
          <Search size={14} className="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground" />
          <input
            type="search"
            placeholder="Nom, email, référence, n° facture…"
            value={q}
            onChange={(e) => { setPage(1); setQ(e.target.value); }}
            className="w-full pl-9 pr-3 py-2 bg-input/40 border border-border rounded-sm text-cream text-sm focus:outline-none focus:ring-2 focus:ring-ring"
          />
        </div>
        <select value={statut} onChange={(e) => { setPage(1); setStatut(e.target.value); }}
          className="px-3 py-2 bg-input/40 border border-border rounded-sm text-cream text-sm focus:outline-none focus:ring-2 focus:ring-ring">
          <option value="">Tous statuts</option>
          {data && Object.entries(data.statuts).map(([k, v]) => (
            <option key={k} value={k}>{v}</option>
          ))}
        </select>
        <select value={payee} onChange={(e) => { setPage(1); setPayee(e.target.value); }}
          className="px-3 py-2 bg-input/40 border border-border rounded-sm text-cream text-sm focus:outline-none focus:ring-2 focus:ring-ring">
          <option value="">Frais (tous)</option>
          <option value="1">Payés</option>
          <option value="0">En attente</option>
        </select>
      </div>

      {msg && <div className="mb-4 px-4 py-3 rounded-sm bg-emerald-500/10 border border-emerald-500/30 text-sm text-emerald-400">{msg}</div>}
      {error && <div className="mb-4 px-4 py-3 rounded-sm bg-destructive/10 border border-destructive/30 text-sm text-destructive">{error}</div>}

      <div className="bg-card border border-border/40 rounded-md overflow-x-auto">
        <table className="w-full text-sm min-w-[860px]">
          <thead className="text-xs uppercase tracking-wider text-muted-foreground border-b border-border/40">
            <tr>
              <th className="text-left px-4 py-2.5">Réf.</th>
              <th className="text-left px-4 py-2.5">Candidat</th>
              <th className="text-left px-4 py-2.5">Programme</th>
              <th className="text-left px-4 py-2.5">Statut</th>
              <th className="text-left px-4 py-2.5">Frais</th>
              <th className="text-left px-4 py-2.5">Reçue le</th>
              <th className="text-left px-4 py-2.5">Actions</th>
            </tr>
          </thead>
          <tbody>
            {data?.candidatures.map((c) => (
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
                <td className="px-4 py-2.5 text-muted-foreground text-xs">
                  {c.programme || "—"}
                  {c.annee_academique && <div>{c.annee_academique}</div>}
                </td>
                <td className="px-4 py-2.5"><StatusBadge value={c.statut} /></td>
                <td className="px-4 py-2.5 text-xs">
                  {Number(c.facture_payee) ? (
                    <span className="text-emerald-400">Payés</span>
                  ) : (
                    <span className="text-amber-400">En attente</span>
                  )}
                  {c.facture_numero && <div className="text-muted-foreground font-mono">{c.facture_numero}</div>}
                </td>
                <td className="px-4 py-2.5 text-muted-foreground text-xs">{formatDateTime(c.created_at)}</td>
                <td className="px-4 py-2.5">
                  <AdminCandidatureActions
                    id={c.id}
                    paid={c.facture_payee}
                    hasEtudiant={Boolean(c.etudiant_id)}
                    compact
                    onDone={(res) => {
                      setError(null);
                      setMsg(adminActionMessage(res));
                      setRefreshKey((v) => v + 1);
                    }}
                    onError={(message) => { setMsg(null); setError(message); }}
                  />
                </td>
              </tr>
            ))}
            {data && data.candidatures.length === 0 && (
              <tr><td colSpan={7} className="px-4 py-8 text-center text-muted-foreground text-sm">Aucun résultat.</td></tr>
            )}
            {loading && !data && (
              <tr><td colSpan={7} className="px-4 py-8 text-center text-muted-foreground text-sm">Chargement…</td></tr>
            )}
          </tbody>
        </table>
      </div>

      {data && data.pages > 1 && (
        <div className="flex items-center justify-between mt-4 text-sm">
          <span className="text-muted-foreground">Page {data.page} / {data.pages}</span>
          <div className="flex gap-2">
            <button disabled={page <= 1} onClick={() => setPage((p) => p - 1)}
              className="px-3 py-1.5 border border-border/40 rounded-sm text-cream disabled:opacity-40 hover:border-blue/40">
              Précédent
            </button>
            <button disabled={page >= data.pages} onClick={() => setPage((p) => p + 1)}
              className="px-3 py-1.5 border border-border/40 rounded-sm text-cream disabled:opacity-40 hover:border-blue/40">
              Suivant
            </button>
          </div>
        </div>
      )}
    </div>
  );
}
