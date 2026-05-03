/**
 * /admin/etudiants/$numero — résout le numéro ETU vers la fiche détaillée
 * (réutilise la page /admin/candidatures/$id puisque c'est le même contenu).
 */
import { createFileRoute, useNavigate, Link } from "@tanstack/react-router";
import { useEffect, useState } from "react";
import { ArrowLeft } from "lucide-react";
import { adminApi } from "@/lib/api";

export const Route = createFileRoute("/admin/_authenticated/etudiants/$numero")({
  component: EtudiantLookupPage,
  head: () => ({ meta: [{ title: "IPEC | Étudiant" }] }),
});

function EtudiantLookupPage() {
  const { numero } = Route.useParams();
  const navigate = useNavigate();
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    adminApi
      .get<{ etudiant_id: number; candidature_id: number | null }>(`/etudiant-lookup.php?numero=${encodeURIComponent(numero)}`)
      .then((r) => {
        if (r.candidature_id) {
          navigate({ to: "/admin/candidatures/$id", params: { id: String(r.candidature_id) }, replace: true });
        } else {
          setError("Aucune candidature liée à ce compte étudiant.");
        }
      })
      .catch((e) => setError(e instanceof Error ? e.message : "Étudiant introuvable."));
  }, [numero, navigate]);

  return (
    <div>
      <Link to="/admin/etudiants" className="inline-flex items-center gap-2 text-sm text-muted-foreground hover:text-blue mb-4">
        <ArrowLeft size={14} /> Retour aux étudiants
      </Link>
      {error ? (
        <div className="bg-destructive/10 border border-destructive/30 rounded-sm px-4 py-3 text-sm text-destructive">{error}</div>
      ) : (
        <p className="text-sm text-muted-foreground">Chargement de la fiche {numero}…</p>
      )}
    </div>
  );
}
