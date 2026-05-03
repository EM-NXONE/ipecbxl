/**
 * Tableau partagé étudiants/préadmis/candidats par catégorie.
 */
import { useCallback, useEffect, useState } from "react";
import { KeyRound, Search } from "lucide-react";
import { adminApi } from "@/lib/api";
import { formatDate, formatDateTime } from "@/lib/format";

interface Etu {
  id: number;
  numero_etudiant: string;
  civilite: string | null;
  prenom: string;
  nom: string;
  email: string;
  date_naissance: string | null;
  statut: string | null;
  categorie: "candidat" | "preadmis" | "etudiant";
  active: number | boolean;
  derniere_connexion: string | null;
  created_at: string;
  cree_par_admin: string | null;
}

interface ActionResult { message?: string; default_password?: string | null; }

const CAT_LABEL: Record<string, string> = {
  candidat: "Candidat",
  preadmis: "Préadmis",
  etudiant: "Étudiant",
};
const CAT_CLASS: Record<string, string> = {
  candidat: "text-amber-400 bg-amber-500/10 border-amber-500/30",
  preadmis: "text-blue bg-blue/10 border-blue/30",
  etudiant: "text-emerald-400 bg-emerald-500/10 border-emerald-500/30",
};

export function CategorieBadge({ value }: { value: string }) {
  const cls = CAT_CLASS[value] || "text-muted-foreground bg-secondary/20 border-border/40";
  return (
    <span className={`inline-flex items-center gap-1 px-2 py-0.5 rounded-sm text-xs border ${cls}`}>
      {CAT_LABEL[value] || value}
    </span>
  );
}

export function ComptesTable({
  title,
  subtitle,
  categorie,
  showCategorie = false,
}: {
  title: string;
  subtitle: string;
  categorie?: "candidat" | "preadmis" | "etudiant";
  showCategorie?: boolean;
}) {
  const [q, setQ] = useState("");
  const [data, setData] = useState<{ etudiants: Etu[] } | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [msg, setMsg] = useState<string | null>(null);
  const [busy, setBusy] = useState<string | null>(null);

  const reload = useCallback(() => {
    const params = new URLSearchParams();
    if (q) params.set("q", q);
    if (categorie) params.set("categorie", categorie);
    adminApi.get<{ etudiants: Etu[] }>(`/etudiants.php?${params}`)
      .then(setData)
      .catch((e) => setError(e.message));
  }, [q, categorie]);

  useEffect(() => {
    setError(null);
    const t = setTimeout(reload, q ? 250 : 0);
    return () => clearTimeout(t);
  }, [q, reload]);

  const runAction = async (etuId: number, action: "reset_password", confirmMsg: string) => {
    if (!confirm(confirmMsg)) return;
    setBusy(`${etuId}:${action}`);
    setError(null);
    setMsg(null);
    try {
      const res = await adminApi.post<ActionResult>("/etudiant-action.php", { id: etuId, action });
      setMsg(res.message || "Action effectuée.");
      reload();
    } catch (e) {
      setError(e instanceof Error ? e.message : "Échec de l'action.");
    } finally {
      setBusy(null);
    }
  };

  return (
    <div>
      <h1 className="font-display text-3xl text-cream mb-2">{title}</h1>
      <p className="text-sm text-muted-foreground mb-6">
        {data ? `${data.etudiants.length} compte${data.etudiants.length > 1 ? "s" : ""} — ${subtitle}` : "Chargement…"}
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

      {msg && (
        <div className="mb-4 px-4 py-3 rounded-sm bg-emerald-500/10 border border-emerald-500/30 text-sm text-emerald-400 break-all">
          {msg}
        </div>
      )}
      {error && <div className="mb-4 px-4 py-3 rounded-sm bg-destructive/10 border border-destructive/30 text-sm text-destructive">{error}</div>}

      <div className="bg-card border border-border/40 rounded-md overflow-x-auto">
        <table className="w-full text-sm min-w-[860px]">
          <thead className="text-xs uppercase tracking-wider text-muted-foreground border-b border-border/40">
            <tr>
              <th className="text-left px-4 py-2.5">N°</th>
              <th className="text-left px-4 py-2.5">Compte</th>
              {showCategorie && <th className="text-left px-4 py-2.5">Catégorie</th>}
              <th className="text-left px-4 py-2.5">Né(e) le</th>
              <th className="text-left px-4 py-2.5">État</th>
              <th className="text-left px-4 py-2.5">Dernière connexion</th>
              <th className="text-right px-4 py-2.5">Actions</th>
            </tr>
          </thead>
          <tbody>
            {data?.etudiants.map((e) => {
              const active = Boolean(Number(e.active));
              return (
                <tr key={e.id} className="border-b border-border/20 hover:bg-secondary/30">
                  <td className="px-4 py-2.5 font-mono text-xs text-blue">{e.numero_etudiant}</td>
                  <td className="px-4 py-2.5 text-cream">
                    {e.prenom} {e.nom}
                    <div className="text-xs text-muted-foreground">{e.email}</div>
                  </td>
                  {showCategorie && (
                    <td className="px-4 py-2.5"><CategorieBadge value={e.categorie} /></td>
                  )}
                  <td className="px-4 py-2.5 text-muted-foreground text-xs">{formatDate(e.date_naissance)}</td>
                  <td className="px-4 py-2.5 text-xs">
                    {active
                      ? <span className="text-emerald-400">Activé</span>
                      : <span className="text-amber-400">Non activé</span>}
                    {e.cree_par_admin && <div className="text-muted-foreground">par {e.cree_par_admin}</div>}
                  </td>
                  <td className="px-4 py-2.5 text-muted-foreground text-xs">
                    {e.derniere_connexion ? formatDateTime(e.derniere_connexion) : "Jamais"}
                  </td>
                  <td className="px-4 py-2.5">
                    <div className="flex justify-end gap-1.5">
                      <button
                        type="button"
                        onClick={() => runAction(e.id, "reset_password", `Réinitialiser le mot de passe de ${e.prenom} ${e.nom} au mot de passe par défaut "Student1" ?`)}
                        disabled={busy !== null}
                        title='Réinitialiser au mot de passe "Student1"'
                        className="inline-flex h-8 w-8 items-center justify-center rounded-sm border border-border/40 text-muted-foreground hover:text-blue hover:border-blue/40 disabled:opacity-50"
                      >
                        <KeyRound size={14} />
                      </button>
                    </div>
                  </td>
                </tr>
              );
            })}
            {data && data.etudiants.length === 0 && (
              <tr><td colSpan={showCategorie ? 7 : 6} className="px-4 py-8 text-center text-muted-foreground text-sm">Aucun compte.</td></tr>
            )}
          </tbody>
        </table>
      </div>
    </div>
  );
}
