/**
 * /etudiant/documents — documents administratifs publiés.
 */
import { createFileRoute } from "@tanstack/react-router";
import { useEffect, useState } from "react";
import { Download } from "lucide-react";
import { etuApi, etuUrl } from "@/lib/api";
import { formatDate } from "@/lib/format";
import { normalizeAcademicYear } from "@/lib/academic-dates";

export const Route = createFileRoute("/etudiant/_authenticated/documents")({
  component: EtudiantDocumentsPage,
  head: () => ({ meta: [{ title: "IPEC | Documents" }] }),
});

interface Doc {
  id: number; reference: string; type: string; titre: string;
  description: string | null; date_emission: string; valide_jusqu_au: string | null;
  annee_academique?: string | null;
}

function groupByAcademicYear(docs: Doc[]): Array<[string, Doc[]]> {
  const map = new Map<string, Doc[]>();
  for (const d of docs) {
    const key = normalizeAcademicYear(d.annee_academique);
    if (!map.has(key)) map.set(key, []);
    map.get(key)!.push(d);
  }
  return Array.from(map.entries()).sort(([a], [b]) => {
    if (a === "Année non précisée") return 1;
    if (b === "Année non précisée") return -1;
    return b.localeCompare(a);
  });
}

function EtudiantDocumentsPage() {
  const [docs, setDocs] = useState<Doc[] | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    etuApi.get<{ documents: Doc[] }>("/documents.php").then((r) => setDocs(r.documents)).catch((e) => setError(e.message));
  }, []);

  return (
    <div>
      <h1 className="font-display text-3xl text-cream mb-2">Mes documents</h1>
      <p className="text-sm text-muted-foreground mb-8">
        Attestations, conventions, courriers — régénérés à la demande au format PDF.
      </p>

      {error && <div className="mb-6 px-4 py-3 rounded-sm bg-destructive/10 border border-destructive/30 text-sm text-destructive">{error}</div>}

      <div className="space-y-8">
        {!docs ? (
          <div className="bg-card border border-border/40 rounded-md p-8 text-sm text-muted-foreground">Chargement…</div>
        ) : docs.length === 0 ? (
          <div className="bg-card border border-border/40 rounded-md p-8 text-sm text-muted-foreground">Aucun document pour l'instant.</div>
        ) : (
          groupByAcademicYear(docs).map(([year, list]) => (
            <section key={year}>
              <h2 className="font-display text-lg text-cream mb-2 px-1">
                Année académique {year} <span className="text-muted-foreground/70 text-sm">· {list.length}</span>
              </h2>
              <div className="bg-card border border-border/40 rounded-md overflow-hidden">
                <div className="overflow-x-auto">
                  <table className="w-full text-sm">
                    <thead className="bg-secondary/30 text-xs uppercase tracking-wider text-muted-foreground">
                      <tr>
                        <th className="text-left px-4 py-3">Référence</th>
                        <th className="text-left px-4 py-3">Titre</th>
                        <th className="text-left px-4 py-3">Émis le</th>
                        <th className="px-4 py-3"></th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-border/30">
                      {list.map((d) => (
                        <tr key={d.id} className="hover:bg-secondary/20">
                          <td className="px-4 py-3 font-mono text-xs text-cream">{d.reference}</td>
                          <td className="px-4 py-3">
                            <div className="text-cream">{d.titre}</div>
                            {d.description && <div className="text-xs text-muted-foreground">{d.description}</div>}
                          </td>
                          <td className="px-4 py-3 text-muted-foreground">{formatDate(d.date_emission)}</td>
                          <td className="px-4 py-3 text-right">
                            <a
                              href={etuUrl(`/telecharger.php?type=document&id=${d.id}`)}
                              className="inline-flex items-center gap-1.5 text-xs text-blue hover:underline"
                            >
                              <Download size={12} /> PDF
                            </a>
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              </div>
            </section>
          ))
        )}
      </div>
    </div>
  );
}
