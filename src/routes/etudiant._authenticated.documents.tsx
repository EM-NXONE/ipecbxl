/**
 * /etudiant/documents — liste des documents administratifs (stub).
 */
import { createFileRoute } from "@tanstack/react-router";

export const Route = createFileRoute("/etudiant/_authenticated/documents")({
  component: EtudiantDocumentsPage,
});

function EtudiantDocumentsPage() {
  return (
    <div>
      <h1 className="font-display text-3xl text-cream mb-2">Mes documents</h1>
      <p className="text-sm text-muted-foreground mb-8">
        Documents administratifs publiés par l'IPEC.
      </p>
      <div className="bg-card border border-border/40 rounded-md p-8">
        <p className="text-muted-foreground">À brancher sur <code className="text-blue">/api/documents.php</code>.</p>
      </div>
    </div>
  );
}
