/**
 * /admin/etudiants — liste des étudiants (stub).
 */
import { createFileRoute } from "@tanstack/react-router";

export const Route = createFileRoute("/admin/_authenticated/etudiants")({
  component: AdminEtudiantsPage,
});

function AdminEtudiantsPage() {
  return (
    <div>
      <h1 className="font-display text-3xl text-cream mb-2">Étudiants</h1>
      <div className="bg-card border border-border/40 rounded-md p-8">
        <p className="text-muted-foreground">À brancher sur <code className="text-blue">/api/etudiants.php</code>.</p>
      </div>
    </div>
  );
}
