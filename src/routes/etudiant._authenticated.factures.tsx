/**
 * /etudiant/factures — liste des factures (stub).
 */
import { createFileRoute } from "@tanstack/react-router";

export const Route = createFileRoute("/etudiant/_authenticated/factures")({
  component: EtudiantFacturesPage,
});

function EtudiantFacturesPage() {
  return (
    <div>
      <h1 className="font-display text-3xl text-cream mb-2">Mes factures</h1>
      <p className="text-sm text-muted-foreground mb-8">
        Toutes tes factures et leur statut de paiement.
      </p>
      <div className="bg-card border border-border/40 rounded-md p-8">
        <p className="text-muted-foreground">À brancher sur <code className="text-blue">/api/factures.php</code>.</p>
      </div>
    </div>
  );
}
