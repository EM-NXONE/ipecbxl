/**
 * /admin — tableau de bord administrateur (squelette).
 */
import { createFileRoute } from "@tanstack/react-router";

export const Route = createFileRoute("/admin/_authenticated/")({
  component: AdminDashboardPage,
});

function AdminDashboardPage() {
  return (
    <div>
      <h1 className="font-display text-3xl text-cream mb-2">Tableau de bord</h1>
      <p className="text-sm text-muted-foreground mb-8">
        Vue d'ensemble des candidatures et étudiants.
      </p>

      <div className="bg-card border border-border/40 rounded-md p-8">
        <p className="text-muted-foreground">
          ⚙️ Squelette en place. La logique (KPIs, liste candidatures) sera branchée
          sur l'API PHP <code className="text-blue">/api/candidatures.php</code> à la prochaine itération.
        </p>
      </div>
    </div>
  );
}
