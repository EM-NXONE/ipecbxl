/**
 * /admin/etudiants — liste des étudiants avec recherche.
 */
import { createFileRoute } from "@tanstack/react-router";
import { useEffect, useState } from "react";
import { Search } from "lucide-react";
import { adminApi } from "@/lib/api";
import { formatDate, formatDateTime } from "@/lib/format";

export const Route = createFileRoute("/admin/_authenticated/etudiants")({
  component: AdminEtudiantsPage,
});

interface Etu {
  id: number;
  numero_etudiant: string;
  civilite: string | null;
  prenom: string;
  nom: string;
  email: string;
  date_naissance: string | null;
  statut: string | null;
  active: number | boolean;
  derniere_connexion: string | null;
  created_at: string;
  cree_par_admin: string | null;
}

function AdminEtudiantsPage() {
  const [q, setQ] = useState("");
  const [data, setData] = useState<{ etudiants: Etu[] } | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    setError(null);
    const t = setTimeout(() => {
      const params = new URLSearchParams();
      if (q) params.set("q", q);
      adminApi.get<{ etudiants: Etu[] }>(`/etudiants.php?${params}`)
        .then(setData)
        .catch((e) => setError(e.message));
    }, q ? 250 : 0);
    return () => clearTimeout(t);
  }, [q]);

  return (
    <div>
      <h1 className="font-display text-3xl text-cream mb-2">Étudiants</h1>
      <p className="text-sm text-muted-foreground mb-6">
        {data ? `${data.etudiants.length} étudiant${data.etudiants.length > 1 ? "s" : ""}` : "Chargement…"}
      </p>

      <div className="bg-card border border-border/40 rounded-md p-4 mb-4">
        <div className="relative">
          <Search size={14} className="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground" />
          <input
            type="search"
            placeholder="Nom, prénom, email, n° étudiant…"
            value={q}
            onChange={(e) => setQ(e.target.value)}
            className="w-full pl-9 pr-3 py-2 bg-input/40 border border-border rounded-sm text-cream text-sm focus:outline-none focus:ring-2 focus:ring-ring"
          />
        </div>
      </div>

      {error && <div className="mb-4 px-4 py-3 rounded-sm bg-destructive/10 border border-destructive/30 text-sm text-destructive">{error}</div>}

      <div className="bg-card border border-border/40 rounded-md overflow-x-auto">
        <table className="w-full text-sm min-w-[700px]">
          <thead className="text-xs uppercase tracking-wider text-muted-foreground border-b border-border/40">
            <tr>
              <th className="text-left px-4 py-2.5">N°</th>
              <th className="text-left px-4 py-2.5">Étudiant</th>
              <th className="text-left px-4 py-2.5">Né(e) le</th>
              <th className="text-left px-4 py-2.5">Compte</th>
              <th className="text-left px-4 py-2.5">Dernière connexion</th>
            </tr>
          </thead>
          <tbody>
            {data?.etudiants.map((e) => (
              <tr key={e.id} className="border-b border-border/20 hover:bg-secondary/30">
                <td className="px-4 py-2.5 font-mono text-xs text-blue">{e.numero_etudiant}</td>
                <td className="px-4 py-2.5 text-cream">
                  {e.prenom} {e.nom}
                  <div className="text-xs text-muted-foreground">{e.email}</div>
                </td>
                <td className="px-4 py-2.5 text-muted-foreground text-xs">{formatDate(e.date_naissance)}</td>
                <td className="px-4 py-2.5 text-xs">
                  {Number(e.active)
                    ? <span className="text-emerald-400">Activé</span>
                    : <span className="text-amber-400">Non activé</span>}
                  {e.cree_par_admin && <div className="text-muted-foreground">par {e.cree_par_admin}</div>}
                </td>
                <td className="px-4 py-2.5 text-muted-foreground text-xs">
                  {e.derniere_connexion ? formatDateTime(e.derniere_connexion) : "Jamais"}
                </td>
              </tr>
            ))}
            {data && data.etudiants.length === 0 && (
              <tr><td colSpan={5} className="px-4 py-8 text-center text-muted-foreground text-sm">Aucun étudiant.</td></tr>
            )}
          </tbody>
        </table>
      </div>
    </div>
  );
}
