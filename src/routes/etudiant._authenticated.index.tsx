/**
 * /etudiant — tableau de bord (squelette).
 */
import { createFileRoute } from "@tanstack/react-router";
import { useEtudiantAuth } from "@/lib/auth-etudiant";

export const Route = createFileRoute("/etudiant/_authenticated/")({
  component: EtudiantDashboardPage,
});

function EtudiantDashboardPage() {
  const { user } = useEtudiantAuth();
  return (
    <div>
      <h1 className="font-display text-3xl text-cream mb-2">Bonjour {user?.prenom}.</h1>
      <p className="text-sm text-muted-foreground mb-8">Voici l'état de ton dossier IPEC.</p>

      <div className="bg-card border border-border/40 rounded-md p-8">
        <p className="text-muted-foreground">
          ⚙️ Squelette en place. Les KPIs (numéro étudiant, statut dossier, solde dû,
          documents) seront branchés sur <code className="text-blue">/api/dashboard.php</code>.
        </p>
      </div>
    </div>
  );
}
