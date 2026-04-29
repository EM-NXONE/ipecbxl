/**
 * /admin/candidatures — liste des candidatures (stub).
 */
import { createFileRoute } from "@tanstack/react-router";

export const Route = createFileRoute("/admin/_authenticated/candidatures")({
  component: AdminCandidaturesListPage,
});

function AdminCandidaturesListPage() {
  return (
    <div>
      <h1 className="font-display text-3xl text-cream mb-2">Candidatures</h1>
      <p className="text-sm text-muted-foreground mb-8">
        Liste complète des candidatures reçues.
      </p>
      <div className="bg-card border border-border/40 rounded-md p-8">
        <p className="text-muted-foreground">À brancher sur <code className="text-blue">/api/candidatures.php</code>.</p>
      </div>
    </div>
  );
}
