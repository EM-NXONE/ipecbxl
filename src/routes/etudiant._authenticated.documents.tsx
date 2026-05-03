/**
 * /etudiant/documents — documents administratifs publiés.
 */
import { createFileRoute } from "@tanstack/react-router";
import { useEffect, useState } from "react";
import { Download } from "lucide-react";
import { etuApi, etuUrl } from "@/lib/api";
import { formatDate } from "@/lib/format";

export const Route = createFileRoute("/etudiant/_authenticated/documents")({
  component: EtudiantDocumentsPage,
  head: () => ({ meta: [{ title: "IPEC | Documents" }] }),
});

interface Doc {
  id: number; reference: string; type: string; titre: string;
  description: string | null; date_emission: string; valide_jusqu_au: string | null;
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

      <div className="bg-card border border-border/40 rounded-md overflow-hidden">
        {!docs ? (
          <div className="p-8 text-sm text-muted-foreground">Chargement…</div>
        ) : docs.length === 0 ? (
          <div className="p-8 text-sm text-muted-foreground">Aucun document pour l'instant.</div>
        ) : (
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
                {docs.map((d) => (
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
        )}
      </div>
    </div>
  );
}
