/**
 * /admin/candidatures/$id — détail d'une candidature (stub).
 */
import { createFileRoute } from "@tanstack/react-router";

export const Route = createFileRoute("/admin/_authenticated/candidatures/$id")({
  component: AdminCandidatureDetailPage,
});

function AdminCandidatureDetailPage() {
  const { id } = Route.useParams();
  return (
    <div>
      <h1 className="font-display text-3xl text-cream mb-2">Candidature #{id}</h1>
      <div className="bg-card border border-border/40 rounded-md p-8">
        <p className="text-muted-foreground">
          À brancher sur <code className="text-blue">/api/candidature.php?id={id}</code>.
        </p>
      </div>
    </div>
  );
}
